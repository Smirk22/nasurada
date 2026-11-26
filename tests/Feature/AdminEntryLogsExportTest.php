<?php

namespace Tests\Feature;

use App\Models\EntryLog;
use App\Models\Student;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminEntryLogsExportTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): User
    {
        return User::create([
            'username' => 'admin-export',
            'email' => 'admin-export@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
        ]);
    }

    private function security(): User
    {
        return User::create([
            'username' => 'guard-export',
            'email' => 'guard-export@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);
    }

    private function createLogs(): void
    {
        $juan = Student::create([
            'student_number' => 'MAR87654321',
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'course' => 'BSIT',
            'year_level' => 1,
            'section' => 'S1A1',
            'status' => 'Enrolled',
        ]);

        $maria = Student::create([
            'student_number' => 'ENG12345678',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'course' => 'BSIT',
            'year_level' => 2,
            'section' => 'S3B1',
            'status' => 'Enrolled',
        ]);

        $visitor = Visitor::create([
            'visitor_name' => 'Pedro Pan',
            'id_type' => 'Student ID',
            'id_number' => 'PED999',
            'visiting_department' => 'Registrar',
            'rfid_tag' => 'VISITOR-999',
        ]);

        EntryLog::create([
            'student_id' => $juan->id,
            'date' => '2026-11-20',
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
            'status' => 'granted',
        ]);

        EntryLog::create([
            'student_id' => $maria->id,
            'date' => '2026-11-21',
            'time_in' => '09:30:00',
            'status' => 'denied',
            'reason' => 'Lost RFID',
        ]);

        EntryLog::create([
            'visitor_id' => $visitor->id,
            'date' => '2026-11-22',
            'time_in' => '10:15:00',
            'status' => 'granted',
        ]);
    }

    public function test_reports_tab_exposes_the_export_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('adashboard'))
            ->assertOk()
            ->assertSee('id="admin-tab-reports"', false)
            ->assertSee(route('admin.reports.entry-logs.export'), false)
            ->assertSee('name="from"', false)
            ->assertSee('name="to"', false)
            ->assertSee('Export Entry Logs (CSV)')
            ->assertSee(route('admin.reports.summary.export'), false)
            ->assertSee('Export Reports (CSV)');
    }

    public function test_admin_can_download_entry_logs_as_csv(): void
    {
        $this->createLogs();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.entry-logs.export'));

        $response->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('entry_logs_', $disposition);
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

        $this->assertSame([
            'Student No', 'Name', 'Year', 'Section', 'Time In', 'Time Out', 'Status', 'Date', 'Reason for access denied',
        ], $rows[0]);
        $this->assertContains([
            'MAR87654321', 'Juan Cruz', '1', 'S1A1', '08:00:00', '17:00:00', 'granted', '2026-11-20', 'N/A',
        ], $rows);
        $this->assertContains([
            'ENG12345678', 'Maria Santos', '2', 'S3B1', '09:30:00', '', 'denied', '2026-11-21', 'Lost RFID',
        ], $rows);
        $this->assertContains([
            '', 'Pedro Pan', '', '', '10:15:00', '', 'granted', '2026-11-22', 'N/A',
        ], $rows);
    }

    public function test_export_honors_the_date_range(): void
    {
        $this->createLogs();

        $content = $this->actingAs($this->admin())
            ->get(route('admin.reports.entry-logs.export', [
                'from' => '2026-11-21',
                'to' => '2026-11-21',
            ]))
            ->streamedContent();

        $this->assertStringNotContainsString('MAR87654321', $content);
        $this->assertStringContainsString('ENG12345678', $content);
        $this->assertStringNotContainsString('Pedro Pan', $content);
    }

    public function test_export_is_recorded_in_the_activity_monitor(): void
    {
        $this->createLogs();

        $this->actingAs($this->admin())
            ->get(route('admin.reports.entry-logs.export'))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'admin-export',
            'role' => 'Admin',
            'action' => 'Exported Entry Logs',
        ]);
    }

    public function test_admin_can_download_summary_report_as_csv(): void
    {
        $this->createLogs();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.summary.export'));

        $response->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('summary_report_', $disposition);
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

        $this->assertSame(['Section', 'Metric', 'Value'], $rows[0]);
        $this->assertContains(['Accounts', 'Total Users', '1'], $rows);
        $this->assertContains(['Students', 'Total Students', '2'], $rows);
        $this->assertContains(['Students', 'With Registered RFID', '0'], $rows);
        $this->assertContains(['Students', 'Without Registered RFID', '2'], $rows);
        $this->assertContains(['Scans', 'Valid Scans (all time)', '2'], $rows);
        $this->assertContains(['Scans', 'Invalid Scans (all time)', '1'], $rows);
        $this->assertContains(['Scans', 'Invalid Scans Today', '0'], $rows);
        $this->assertContains(['Today Status', 'Denied', '0'], $rows);
    }

    public function test_summary_report_is_recorded_in_the_activity_monitor(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.summary.export'))
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'admin-export',
            'role' => 'Admin',
            'action' => 'Exported Summary Report',
        ]);
    }

    public function test_export_rejects_invalid_dates(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.entry-logs.export', ['from' => 'not-a-date']))
            ->assertRedirect()
            ->assertSessionHasErrors('from');
    }

    public function test_security_cannot_export_entry_logs(): void
    {
        $this->actingAs($this->security())
            ->get(route('admin.reports.entry-logs.export'))
            ->assertRedirect(route('guard.dashboard'));
    }

    public function test_security_cannot_export_summary_reports(): void
    {
        $this->actingAs($this->security())
            ->get(route('admin.reports.summary.export'))
            ->assertRedirect(route('guard.dashboard'));
    }
}
