<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AccessLogsTest extends TestCase
{
    use DatabaseMigrations;

    private function user(string $role, string $username): User
    {
        return User::create([
            'username' => $username,
            'email' => $username.'@example.com',
            'password' => bcrypt('password123'),
            'role' => $role,
        ]);
    }

    public function test_admin_dashboard_does_not_have_access_logs(): void
    {
        $admin = $this->user('Admin', 'admin-access');

        $this->actingAs($admin)
            ->get(route('adashboard'))
            ->assertOk()
            ->assertDontSee('Access Logs')
            ->assertDontSee('#access-logs', false)
            ->assertViewMissing('accessLogs');
    }

    public function test_super_admin_dashboard_has_access_logs_tab(): void
    {
        $superAdmin = $this->user('SuperAdmin', 'super-access');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Access Logs')
            ->assertSee('id="tab-access"', false)
            ->assertViewHas('accessLogs');
    }

    public function test_successful_login_is_recorded(): void
    {
        $admin = $this->user('Admin', 'admin-login');

        $this->withSession(['login.captcha' => 7])
            ->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'password123',
                'captcha' => '7',
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'admin-login',
            'role' => 'Admin',
            'action' => ActivityLog::ACTION_LOGGED_IN,
        ]);
    }

    public function test_failed_login_is_recorded(): void
    {
        $admin = $this->user('Admin', 'admin-failed');

        $this->from(route('login'))
            ->withSession(['login.captcha' => 7])
            ->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'wrong-password',
                'captcha' => '7',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'admin-failed',
            'role' => 'Admin',
            'action' => ActivityLog::ACTION_FAILED_LOGIN,
        ]);
    }

    public function test_logout_is_recorded(): void
    {
        $admin = $this->user('Admin', 'admin-logout');

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertStatus(302);

        $this->assertDatabaseHas('activity_logs', [
            'user_name' => 'admin-logout',
            'role' => 'Admin',
            'action' => ActivityLog::ACTION_LOGGED_OUT,
        ]);
    }

    public function test_access_events_do_not_appear_in_super_admin_audit_log(): void
    {
        $superAdmin = $this->user('SuperAdmin', 'super-audit');

        ActivityLog::logAs($superAdmin, ActivityLog::ACTION_LOGGED_IN, 'Signed in.');
        ActivityLog::logAs($superAdmin, 'Updated System Settings', 'Changed site name.');

        $activities = $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->viewData('activities');

        $this->assertSame(['Updated System Settings'], $activities->pluck('action')->all());
    }

    public function test_access_history_scope_only_returns_access_events(): void
    {
        $admin = $this->user('Admin', 'admin-scope');

        ActivityLog::logAs($admin, ActivityLog::ACTION_LOGGED_IN, 'Signed in.');
        ActivityLog::logAs($admin, 'Updated User Account', 'Unrelated activity.');

        $actions = ActivityLog::accessHistory()->pluck('action')->all();

        $this->assertSame([ActivityLog::ACTION_LOGGED_IN], $actions);
    }
}
