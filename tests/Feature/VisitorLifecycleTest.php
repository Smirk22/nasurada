<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_dashboard_shows_visitor_list_link_and_no_pass_duration(): void
    {
        $user = User::create([
            'username' => 'guard2',
            'email' => 'guard2@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($user)
            ->get('/Security/dashboard')
            ->assertOk()
            ->assertSee("Visitor's List", false)
            ->assertDontSee('Pass Valid For');
    }

    public function test_visitor_can_be_registered_listed_removed_and_then_reassigned(): void
    {
        $user = User::create([
            'username' => 'guard1',
            'email' => 'guard1@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($user)
            ->post('/Security/visitor', [
                'visitor_name' => 'Visitor One',
                'id_type' => 'passport',
                'id_number' => 'P1234567',
                'visiting_department' => 'registrar',
                'rfid_tag' => 'test-1',
            ])
            ->assertRedirect();

        $visitor = Visitor::firstOrFail();

        $this->assertSame('TEST-1', $visitor->rfid_tag);
        $this->assertNull($visitor->removed_at);

        $this->actingAs($user)
            ->get('/Security/visitors')
            ->assertOk()
            ->assertSee('Visitor One');

        $this->postJson('/api/rfid-scan', ['uid' => 'test-1'])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('entry_logs', [
            'visitor_id' => $visitor->id,
            'status' => 'granted',
        ]);

        $this->actingAs($user)
            ->delete("/Security/visitors/{$visitor->id}")
            ->assertRedirect();

        $this->assertNotNull($visitor->fresh()->removed_at);

        $this->postJson('/api/rfid-scan', ['uid' => 'test-1'])
            ->assertNotFound()
            ->assertJson(['reason' => 'unknown_card']);

        $this->assertDatabaseHas('entry_logs', [
            'card_uid' => 'TEST-1',
            'status' => 'unknown',
            'reason' => 'unknown_card',
        ]);

        // The removed visitor no longer owns the tag, so it can be assigned
        // to a new visitor instead of staying stuck as an expired ID.
        $this->actingAs($user)
            ->get('/Security/dashboard')
            ->assertOk()
            ->assertSee('Unregistered Tag');

        $this->actingAs($user)
            ->getJson('/Security/kiosk/latest')
            ->assertOk()
            ->assertJsonPath('scan.action', 'UNKNOWN')
            ->assertJsonPath('scan.is_visitor', false)
            ->assertJsonPath('scan.reason', 'unknown_card');

        $this->actingAs($user)
            ->post('/Security/visitor', [
                'visitor_name' => 'Visitor Two',
                'id_type' => 'passport',
                'id_number' => 'P7654321',
                'visiting_department' => 'admin',
                'rfid_tag' => 'test-1',
            ])
            ->assertRedirect();

        $visitor->refresh();

        $this->assertSame('Visitor Two', $visitor->visitor_name);
        $this->assertNull($visitor->removed_at);
        $this->assertSame(1, Visitor::count());
    }
}
