<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Services\UniSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRfidScanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'student_number' => 'DEV-0001',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'year_level' => 1,
            'section' => 'S1A',
            'rfid_tag' => 'DEVICE1',
            'phone_number' => '09171234567',
            'status' => 'Enrolled',
            'enrollment_confirmed' => true,
            'school_year' => Student::currentSchoolYear(),
        ], $overrides));
    }

    public function test_gsm_channel_returns_the_sms_payload_without_calling_unisms(): void
    {
        $student = $this->student();

        $uniSms = new RecordingUniSmsService;
        $this->app->instance(UniSmsService::class, $uniSms);

        $response = $this->postJson('/api/rfid-scan', [
            'uid' => ' device1 ',
            'sms_channel' => 'gsm',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('action', 'time_in')
            ->assertJsonPath('beep', false)
            ->assertJsonPath('sms.phone', '+639171234567')
            ->assertJsonPath('sms.student_id', $student->id);

        $this->assertStringContainsString('Ana', $response->json('sms.message'));
        $this->assertSame(0, $uniSms->apiSends);
        $this->assertDatabaseCount('sent_messages', 0);
    }

    public function test_unknown_card_tells_the_device_to_beep(): void
    {
        $this->postJson('/api/rfid-scan', ['uid' => 'NO-SUCH-TAG', 'sms_channel' => 'gsm'])
            ->assertNotFound()
            ->assertJsonPath('reason', 'unknown_card')
            ->assertJsonPath('beep', true);
    }

    public function test_denied_student_tells_the_device_to_beep(): void
    {
        $this->student(['status' => 'Unenrolled']);

        $this->postJson('/api/rfid-scan', ['uid' => 'DEVICE1', 'sms_channel' => 'gsm'])
            ->assertForbidden()
            ->assertJsonPath('reason', 'enrollment_not_confirmed')
            ->assertJsonPath('beep', true);
    }

    public function test_time_out_scan_is_valid_but_has_no_sms_to_send(): void
    {
        $this->student();

        $this->postJson('/api/rfid-scan', ['uid' => 'DEVICE1', 'sms_channel' => 'gsm'])
            ->assertOk()
            ->assertJsonPath('action', 'time_in');

        $this->postJson('/api/rfid-scan', ['uid' => 'DEVICE1', 'sms_channel' => 'gsm'])
            ->assertOk()
            ->assertJsonPath('action', 'time_out')
            ->assertJsonPath('beep', false)
            ->assertJsonPath('sms', null);
    }

    public function test_default_channel_still_uses_the_unisms_api(): void
    {
        $this->student();

        $uniSms = new RecordingUniSmsService;
        $this->app->instance(UniSmsService::class, $uniSms);

        $this->postJson('/api/rfid-scan', ['uid' => 'DEVICE1'])
            ->assertOk()
            ->assertJsonPath('beep', false)
            ->assertJsonMissingPath('sms');

        $this->assertSame(1, $uniSms->apiSends);
    }

    public function test_a_retried_scan_id_replays_the_first_decision(): void
    {
        $this->student();

        $payload = [
            'uid' => 'DEVICE1',
            'sms_channel' => 'gsm',
            'scan_id' => 'retry-123',
        ];

        $first = $this->postJson('/api/rfid-scan', $payload);
        $second = $this->postJson('/api/rfid-scan', $payload);

        $first->assertOk()->assertJsonPath('action', 'time_in');
        $second->assertOk()
            ->assertJsonPath('action', 'time_in')
            ->assertJsonPath('beep', false);

        $this->assertDatabaseCount('entry_logs', 1);
    }

    public function test_invalid_sms_channel_is_rejected(): void
    {
        $this->postJson('/api/rfid-scan', ['uid' => 'DEVICE1', 'sms_channel' => 'carrier-pigeon'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sms_channel');
    }
}

class RecordingUniSmsService extends UniSmsService
{
    public int $apiSends = 0;

    public function sendEntryNotification(Student $student): bool
    {
        $this->apiSends++;

        return true;
    }
}
