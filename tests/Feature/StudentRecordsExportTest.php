<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StudentRecordsExportTest extends TestCase
{
    use DatabaseMigrations;

    private function registrar(): User
    {
        return User::create([
            'username' => 'registrar-export',
            'email' => 'registrar-export@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);
    }

    private function createStudents(): void
    {
        Student::create([
            'student_number' => 'MAR87654321',
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'course' => 'BSIT',
            'year_level' => 1,
            'section' => 'S1A1',
            'phone_number' => '09171234567',
            'rfid_tag' => 'ABC123',
            'status' => 'Enrolled',
        ]);

        Student::create([
            'student_number' => 'ENG12345678',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'course' => 'BSIT',
            'year_level' => 2,
            'section' => 'S3B1',
            'status' => 'Enrolled',
        ]);
    }

    public function test_sidebar_link_opens_the_export_records_tab(): void
    {
        $this->createStudents();

        $this->actingAs($this->registrar())
            ->get(route('rdashboard'))
            ->assertOk()
            ->assertSee('Export Records')
            ->assertSee('data-registrar-tab-link="export"', false)
            ->assertSee(route('rdashboard').'#export-records', false)
            ->assertDontSee('role="tablist"', false)
            ->assertDontSee('Manage Records')
            ->assertSee('Filter by section')
            ->assertSee('id="export_section"', false)
            ->assertSee('value="S1A1"', false)
            ->assertSee('value="S3B1"', false)
            ->assertSee('value="S7C4"', false)
            ->assertSee(route('registrar.students.export'), false)
            ->assertSee('Export Reports (CSV)')
            ->assertSee(route('registrar.reports.export'), false);
    }

    public function test_registrar_can_download_student_records(): void
    {
        $this->createStudents();

        $response = $this->actingAs($this->registrar())
            ->get(route('registrar.students.export'));

        $response->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('student_records_', $disposition);
        $this->assertStringContainsString('.xls', $disposition);

        $content = $response->streamedContent();
        $this->assertStringContainsString('Student Number', $content);
        $this->assertStringContainsString('MAR87654321', $content);
        $this->assertStringContainsString('Juan', $content);
        $this->assertStringContainsString('ENG12345678', $content);
    }

    public function test_registrar_can_download_reports_as_csv(): void
    {
        $this->createStudents();

        $response = $this->actingAs($this->registrar())
            ->get(route('registrar.reports.export'));

        $response->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('student_reports_', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', $content) ?: [],
            fn (string $line) => trim($line) !== ''
        ));
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
        $rows = array_map(fn (string $line) => str_getcsv($line), $lines);

        $this->assertSame(['IETI SEVS - STUDENT REPORTS'], $rows[0]);
        $this->assertContains(['Scope', 'All Sections'], $rows);
        $this->assertContains(['Metric', 'Students'], $rows);
        $this->assertContains(['Total Students', '2'], $rows);
        $this->assertContains(['Enrollment Confirmed', '0'], $rows);
        $this->assertContains(['Enrollment Not Confirmed', '2'], $rows);
        $this->assertContains(['Status: Enrolled', '2'], $rows);
        $this->assertContains(['Students by Year and Section'], $rows);
        $this->assertContains(['Year Level', 'Section', 'Students'], $rows);
        $this->assertContains(['1', 'S1A1', '1'], $rows);
        $this->assertContains(['2', 'S3B1', '1'], $rows);
        $this->assertContains(['RFID and Access Card Summary'], $rows);
        $this->assertContains(['With Registered RFID', '1'], $rows);
        $this->assertContains(['Without Registered RFID', '1'], $rows);
        $this->assertContains(['Card Type: student', '2'], $rows);
    }

    public function test_report_honors_section_filter(): void
    {
        $this->createStudents();

        $content = $this->actingAs($this->registrar())
            ->get(route('registrar.reports.export', ['section' => 'S1A1']))
            ->streamedContent();

        $this->assertStringContainsString('Scope,S1A1', $content);
        $this->assertStringContainsString('"Total Students",1', $content);
        $this->assertStringContainsString('1,S1A1,1', $content);
        $this->assertStringNotContainsString('S3B1', $content);
    }

    public function test_report_export_is_recorded_in_the_activity_monitor(): void
    {
        $this->createStudents();

        $this->actingAs($this->registrar())
            ->get(route('registrar.reports.export'))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'registrar-export',
            'role' => 'Registrar',
            'action' => 'Exported Student Reports',
        ]);
    }

    public function test_export_honors_student_list_filters(): void
    {
        $this->createStudents();

        $content = $this->actingAs($this->registrar())
            ->get(route('registrar.students.export', ['search' => 'Juan']))
            ->streamedContent();

        $this->assertStringContainsString('MAR87654321', $content);
        $this->assertStringNotContainsString('ENG12345678', $content);
    }

    public function test_export_honors_section_filter(): void
    {
        $this->createStudents();

        $content = $this->actingAs($this->registrar())
            ->get(route('registrar.students.export', ['section' => 'S1A1']))
            ->streamedContent();

        $this->assertStringContainsString('MAR87654321', $content);
        $this->assertStringNotContainsString('ENG12345678', $content);
    }

    public function test_security_cannot_export_student_records(): void
    {
        $guard = User::create([
            'username' => 'guard-export',
            'email' => 'guard-export@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($guard)
            ->get(route('registrar.students.export'))
            ->assertRedirect(route('guard.dashboard'));
    }

    public function test_security_cannot_export_reports(): void
    {
        $guard = User::create([
            'username' => 'guard-report-export',
            'email' => 'guard-report-export@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($guard)
            ->get(route('registrar.reports.export'))
            ->assertRedirect(route('guard.dashboard'));
    }
}
