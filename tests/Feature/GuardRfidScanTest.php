<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardRfidScanTest extends TestCase
{
    use RefreshDatabase;

    private function guard(): User
    {
        return User::create([
            'username' => 'guard-rfid',
            'email' => 'guard-rfid@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'username' => 'admin-rfid',
            'email' => 'admin-rfid@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
        ]);
    }

    public function test_a_scan_during_visitor_registration_is_captured_instead_of_logged(): void
    {
        $guard = $this->guard();

        $this->actingAs($guard)
            ->postJson('/Security/rfid/scan-mode')
            ->assertOk()
            ->assertJsonPath('context', 'visitor');

        $this->postJson('/api/rfid-scan', ['uid' => 'abc 123'])
            ->assertOk()
            ->assertJsonPath('status', 'registration')
            ->assertJsonPath('uid', 'ABC123');

        $this->assertDatabaseCount('entry_logs', 0);

        $this->actingAs($guard)
            ->getJson('/Security/rfid/scan-latest')
            ->assertOk()
            ->assertJsonPath('uid', 'ABC123');

        // Once the scan has been handed to the form, the next tap is a normal
        // gate scan and should reach the live feed again.
        $this->postJson('/api/rfid-scan', ['uid' => 'ABC123'])
            ->assertNotFound()
            ->assertJsonPath('reason', 'unknown_card');

        $this->assertDatabaseCount('entry_logs', 1);
    }

    public function test_visitor_scan_mode_reports_the_owner_of_an_active_visitor_tag(): void
    {
        Visitor::create([
            'visitor_name' => 'Visitor One',
            'id_type' => 'passport',
            'id_number' => 'P1234567',
            'visiting_department' => 'registrar',
            'rfid_tag' => 'DUPTAG',
        ]);

        $this->actingAs($this->guard())
            ->postJson('/Security/rfid/scan-mode')
            ->assertOk();

        $this->postJson('/api/rfid-scan', ['uid' => 'duptag'])
            ->assertOk()
            ->assertJsonPath('status', 'registration')
            ->assertJsonPath('owner', 'Visitor One');

        $this->assertDatabaseCount('entry_logs', 0);
    }

    public function test_registrar_and_guard_scan_modes_do_not_read_each_others_scans(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/Security/rfid/scan-mode')
            ->assertOk();

        $this->postJson('/api/rfid-scan', ['uid' => 'GUARD1'])
            ->assertOk()
            ->assertJsonPath('context', 'visitor');

        $this->actingAs($admin)
            ->getJson('/registrar/rfid/scan-latest')
            ->assertOk()
            ->assertJsonPath('uid', null);

        $this->actingAs($admin)
            ->getJson('/Security/rfid/scan-latest')
            ->assertOk()
            ->assertJsonPath('uid', 'GUARD1');
    }
}
