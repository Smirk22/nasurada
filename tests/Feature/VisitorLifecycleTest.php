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
            ->assertSee('Temporary Pass List', false)
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

    public function test_guard_cannot_register_the_same_credentials_twice_with_a_different_rfid_tag(): void
    {
        $guard = User::create([
            'username' => 'guard-duplicate',
            'email' => 'guard-duplicate@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => 'Juan Cruz',
                'id_type' => 'registration_card',
                'id_number' => '2026-00123',
                'visiting_department' => 'loss_of_rfid_id',
                'rfid_tag' => 'PASS-ONE',
            ])
            ->assertRedirect();

        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => 'Juan Cruz',
                'id_type' => 'registration_card',
                'id_number' => '2026-00123',
                'visiting_department' => 'loss_of_rfid_id',
                'rfid_tag' => 'PASS-TWO',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('visitor_name');

        $this->assertSame(1, Visitor::count());
        $this->assertDatabaseMissing('visitors', ['rfid_tag' => 'PASS-TWO']);
    }

    public function test_name_variations_and_id_numbers_are_treated_as_duplicate_credentials(): void
    {
        $guard = User::create([
            'username' => 'guard-duplicate-variants',
            'email' => 'guard-duplicate-variants@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => 'Maria Santos',
                'id_type' => 'passport',
                'id_number' => 'P1234567',
                'visiting_department' => 'registrar',
                'rfid_tag' => 'MARIA-1',
            ])
            ->assertRedirect();

        // Different ID, same name after normalization.
        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => '  maria   santos  ',
                'id_type' => 'passport',
                'id_number' => 'P9999999',
                'visiting_department' => 'registrar',
                'rfid_tag' => 'MARIA-2',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('visitor_name');

        // Different name, same ID type and number.
        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => 'Someone Else',
                'id_type' => 'passport',
                'id_number' => 'p1234567',
                'visiting_department' => 'registrar',
                'rfid_tag' => 'MARIA-3',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('visitor_name');

        $this->assertSame(1, Visitor::count());
    }

    public function test_credentials_can_be_reused_after_the_pass_is_removed(): void
    {
        $guard = User::create([
            'username' => 'guard-reuse',
            'email' => 'guard-reuse@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => 'Juan Cruz',
                'id_type' => 'registration_card',
                'id_number' => '2026-00123',
                'visiting_department' => 'loss_of_rfid_id',
                'rfid_tag' => 'OLD-TAG',
            ])
            ->assertRedirect();

        $visitor = Visitor::firstOrFail();

        $this->actingAs($guard)
            ->delete("/Security/visitors/{$visitor->id}")
            ->assertRedirect();

        $this->actingAs($guard)
            ->post('/Security/temporary-pass', [
                'visitor_name' => 'Juan Cruz',
                'id_type' => 'registration_card',
                'id_number' => '2026-00123',
                'visiting_department' => 'loss_of_rfid_id',
                'rfid_tag' => 'NEW-TAG',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // The removed pass stays in history; a fresh active pass is created.
        $this->assertSame(2, Visitor::count());
        $this->assertNotNull($visitor->fresh()->removed_at);
        $this->assertDatabaseHas('visitors', [
            'rfid_tag' => 'NEW-TAG',
            'visitor_name' => 'Juan Cruz',
            'removed_at' => null,
        ]);
    }
}
