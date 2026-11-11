<?php

namespace Tests\Feature;

use App\Models\SentMessage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SentMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_dashboard_shows_sent_message_count_and_dropdown_list(): void
    {
        $user = User::create([
            'username' => 'guard3',
            'email' => 'guard3@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $student = Student::create([
            'student_number' => 'TES12345678',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'BSIT',
            'year_level' => 1,
            'section' => 'S1B1',
            'phone_number' => '09171234567',
        ]);

        SentMessage::create([
            'student_id' => $student->id,
            'recipient' => '+639171234567',
            'message' => 'Dear Parent/Guardian, Test Student has entered the school premises.',
            'sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/Security/dashboard')
            ->assertOk()
            ->assertSee('Sent Messages')
            ->assertSee('id="sent-messages-count"', false)
            ->assertSee('Dear Parent/Guardian, Test Student has entered the school premises.')
            ->assertSee('+639171234567');
    }

    public function test_guard_dashboard_shows_empty_state_when_no_messages_were_sent(): void
    {
        $user = User::create([
            'username' => 'guard4',
            'email' => 'guard4@example.com',
            'password' => bcrypt('password'),
            'role' => 'Security',
        ]);

        $this->actingAs($user)
            ->get('/Security/dashboard')
            ->assertOk()
            ->assertSee('No messages sent today.');
    }
}
