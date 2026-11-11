<?php

namespace App\Services;

use App\Models\SentMessage;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Taliffsss\UniSms\Exceptions\ApiException;
use Taliffsss\UniSms\UniSms;
use Throwable;

class UniSmsService
{
    /**
     * Send an "entered premises" text to the student's registered number.
     *
     * Failures are logged but never bubble up, so an SMS problem can never
     * block an RFID gate scan.
     */
    public function sendEntryNotification(Student $student): bool
    {
        $recipient = $this->normalizeRecipient((string) $student->phone_number);

        if ($recipient === '') {
            return false;
        }

        $key = (string) config('services.unisms.key');

        if (trim($key) === '') {
            Log::warning('UniSMS secret key is not configured; skipping entry notification.');

            return false;
        }

        $now = now();
        $message = sprintf(
            'Dear Parent/Guardian, %s %s has entered the school premises at %s on %s.',
            $student->first_name,
            $student->last_name,
            $now->format('g:i A'),
            $now->format('F j, Y')
        );

        try {
            (new UniSms($key))
                ->to($recipient)
                ->message($message)
                ->from((string) config('services.unisms.sender', 'UniSMS'))
                ->withMetadata([
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                ])
                ->send();

            try {
                SentMessage::create([
                    'student_id' => $student->id,
                    'recipient' => $recipient,
                    'message' => $message,
                    'sent_at' => $now,
                ]);
            } catch (Throwable $exception) {
                Log::warning('Could not record the sent UniSMS notification.', [
                    'student_id' => $student->id,
                    'recipient' => $recipient,
                    'error' => $exception->getMessage(),
                ]);
            }

            return true;
        } catch (ApiException $exception) {
            Log::warning('UniSMS entry notification rejected by the API.', [
                'student_id' => $student->id,
                'recipient' => $recipient,
                'sender' => config('services.unisms.sender'),
                'status' => $exception->getStatusCode(),
                'response' => $exception->getResponseBody(),
            ]);

            return false;
        } catch (Throwable $exception) {
            Log::warning('UniSMS entry notification failed.', [
                'student_id' => $student->id,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Convert a stored number to the +63 international format the API prefers.
     */
    private function normalizeRecipient(string $number): string
    {
        $number = preg_replace('/[^0-9+]/', '', trim($number)) ?? '';

        if ($number === '') {
            return '';
        }

        if (str_starts_with($number, '+63')) {
            return $number;
        }

        if (str_starts_with($number, '63') && strlen($number) === 12) {
            return '+'.$number;
        }

        if (str_starts_with($number, '0') && strlen($number) === 11) {
            return '+63'.substr($number, 1);
        }

        return $number;
    }
}
