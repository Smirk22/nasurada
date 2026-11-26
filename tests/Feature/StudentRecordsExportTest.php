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
            ->assertSee(route('registrar.students.export'), false);
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
}
