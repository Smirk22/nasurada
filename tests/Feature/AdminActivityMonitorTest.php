<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminActivityMonitorTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): User
    {
        return User::create([
            'username' => 'admin-activity',
            'email' => 'admin-activity@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
        ]);
    }

    public function test_activity_monitor_is_a_separate_admin_tab(): void
    {
        $this->actingAs($this->admin())
            ->get(route('adashboard'))
            ->assertOk()
            ->assertSee('Activity Monitor')
            ->assertSee('data-admin-tab="activity"', false)
            ->assertSee('#activity', false)
            ->assertSee('id="admin-tab-activity"', false)
            ->assertDontSee('Real-time audit log of Registrar & Security actions.');
    }

    public function test_activity_monitor_supports_search_and_role_filters(): void
    {
        ActivityLog::create([
            'user_name' => 'registrar-one',
            'role' => 'Registrar',
            'action' => 'Added Student Record',
            'details' => 'Juan Cruz',
        ]);
        ActivityLog::create([
            'user_name' => 'guard-one',
            'role' => 'Security',
            'action' => 'Granted Entry',
            'details' => 'Maria Santos',
        ]);
        ActivityLog::create([
            'user_name' => 'super-one',
            'role' => 'SuperAdmin',
            'action' => 'Secret Action',
            'details' => 'Should stay in Super Admin audit',
        ]);

        $searched = $this->actingAs($this->admin())
            ->get(route('adashboard', ['q' => 'Juan']));

        $this->assertSame(
            ['registrar-one'],
            $searched->viewData('activities')->pluck('user_name')->all()
        );

        $roleFiltered = $this->get(route('adashboard', ['role' => 'Security']));

        $this->assertSame(
            ['guard-one'],
            $roleFiltered->viewData('activities')->pluck('user_name')->all()
        );

        $all = $this->get(route('adashboard'));

        $this->assertNotContains('super-one', $all->viewData('activities')->pluck('user_name')->all());
    }

    public function test_access_events_do_not_appear_in_activity_monitor(): void
    {
        ActivityLog::create([
            'user_name' => 'admin-logout',
            'role' => 'Admin',
            'action' => ActivityLog::ACTION_LOGGED_OUT,
            'details' => 'Signed out via web from 127.0.0.1.',
        ]);
        ActivityLog::create([
            'user_name' => 'registrar-action',
            'role' => 'Registrar',
            'action' => 'Added Student Record',
            'details' => 'Juan Cruz',
        ]);

        $activities = $this->actingAs($this->admin())
            ->get(route('adashboard'))
            ->viewData('activities');

        $this->assertSame(['Added Student Record'], $activities->pluck('action')->all());
    }

    public function test_activity_monitor_is_paginated_and_keeps_filters_and_tab(): void
    {
        for ($i = 1; $i <= 22; $i++) {
            ActivityLog::create([
                'user_name' => 'Activity User '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'role' => 'Registrar',
                'action' => 'Audited',
                'details' => 'Entry '.$i,
            ]);
        }

        $response = $this->actingAs($this->admin())
            ->get(route('adashboard', ['q' => 'Activity User']));

        $response->assertOk()->assertSee('page=2', false);

        $paginator = $response->viewData('activities');

        $this->assertSame(22, $paginator->total());
        $this->assertSame(10, $paginator->perPage());
        $this->assertCount(10, $paginator->items());
        $this->assertStringContainsString('q=', $paginator->url(2));
        $this->assertStringContainsString('page=2', $paginator->url(2));
        $this->assertStringEndsWith('#activity', $paginator->url(2));
    }

    public function test_super_admin_sees_admin_sidebar_buttons_only_on_admin_dashboard(): void
    {
        $superAdmin = User::create([
            'username' => 'super-sidebar',
            'email' => 'super-sidebar@example.com',
            'password' => bcrypt('password'),
            'role' => 'SuperAdmin',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-tab="overview"', false)
            ->assertSee('data-sa-tab="backups"', false)
            ->assertSee('data-sa-tab="access"', false)
            ->assertDontSee('data-admin-tab="activity"', false)
            ->assertDontSee('data-admin-tab="backups"', false)
            ->assertDontSee('data-admin-tab="reports"', false)
            ->assertDontSee('data-admin-tab="users"', false)
            ->assertDontSee('data-admin-tab="access"', false);

        $this->get(route('adashboard'))
            ->assertOk()
            ->assertSee('data-admin-tab="activity"', false)
            ->assertDontSee('data-admin-tab="backups"', false)
            ->assertDontSee('data-admin-tab="access"', false);
    }

    public function test_admin_sees_only_remaining_admin_sidebar_buttons(): void
    {
        $this->actingAs($this->admin())
            ->get(route('adashboard'))
            ->assertOk()
            ->assertSee('data-admin-tab="overview"', false)
            ->assertSee('data-admin-tab="activity"', false)
            ->assertSee('data-admin-tab="reports"', false)
            ->assertSee('data-admin-tab="users"', false)
            ->assertDontSee('data-admin-tab="backups"', false)
            ->assertDontSee('data-admin-tab="access"', false);
    }

    public function test_admin_sees_only_the_admin_dashboard_link_on_other_dashboards(): void
    {
        $this->actingAs($this->admin())
            ->get(route('rdashboard'))
            ->assertOk()
            ->assertSee('data-admin-tab="overview"', false)
            ->assertDontSee('data-admin-tab="activity"', false)
            ->assertDontSee('data-admin-tab="reports"', false)
            ->assertDontSee('data-admin-tab="users"', false);
    }

    public function test_registrar_buttons_appear_before_the_guard_dashboard_link(): void
    {
        $response = $this->actingAs($this->admin())->get(route('rdashboard'));
        $response->assertOk();

        $html = $response->getContent();

        $studentRecords = strpos($html, '>Student Records</span>');
        $entryLogs = strpos($html, '>Entry Logs</span>');
        $guardDashboard = strpos($html, '>Guard Dashboard</span>');

        $this->assertNotFalse($studentRecords);
        $this->assertNotFalse($entryLogs);
        $this->assertNotFalse($guardDashboard);
        $this->assertLessThan($guardDashboard, $studentRecords);
        $this->assertLessThan($guardDashboard, $entryLogs);
    }
}
