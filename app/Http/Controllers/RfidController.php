<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\Student;
use App\Models\Visitor;
use App\Services\UniSmsService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RfidController extends Controller
{
    private const REGISTRATION_CONTEXT_KEY = 'rfid.registration.context';

    private const REGISTRATION_UNTIL_KEY = 'rfid.registration.until';

    private const REGISTRATION_LAST_KEY = 'rfid.registration.last';

    private const CONTEXT_REGISTRAR = 'registrar';

    private const CONTEXT_VISITOR = 'visitor';

    public function __construct(private readonly UniSmsService $uniSms) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|max:32',
        ]);

        $uid = strtoupper(preg_replace('/\s+/', '', $request->uid));

        $student = Student::whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$uid])->first();

        // A card tapped while a staff member is registering a student or a
        // visitor is not a gate entry, so hand it to that form instead of the
        // guard's live entry feed.
        $registrationContext = $this->registrationContextActive();

        if ($registrationContext !== null) {
            return $this->captureRegistrationScan($registrationContext, $uid, $student);
        }

        $now = now();
        $today = $now->toDateString();

        if (! $student) {
            // Removed visitors no longer own their old tag, so a scan of it is
            // treated like any other unregistered card.
            $visitor = Visitor::active()
                ->whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$uid])
                ->first();

            if ($visitor) {
                $todayLog = EntryLog::where('visitor_id', $visitor->id)
                    ->where('date', $today)
                    ->where('status', 'granted')
                    ->latest('id')
                    ->first();

                if ($todayLog) {
                    if (is_null($todayLog->time_out)) {
                        $todayLog->update([
                            'time_out' => $now->format('H:i:s'),
                        ]);
                        $action = 'time_out';
                    } else {
                        $todayLog->update([
                            'time_in' => $now->format('H:i:s'),
                            'time_out' => null,
                        ]);
                        $action = 'time_in';
                    }
                } else {
                    EntryLog::create([
                        'visitor_id' => $visitor->id,
                        'card_uid' => $uid,
                        'date' => $today,
                        'time_in' => $now->format('H:i:s'),
                        'time_out' => null,
                        'status' => 'granted',
                    ]);
                    $action = 'time_in';
                }

                return response()->json([
                    'status' => 'success',
                    'action' => $action,
                    'student' => $visitor->visitor_name,
                    'visitor' => true,
                ]);
            }

            return $this->reject($uid, 'unknown_card', 'Unregistered RFID tag detected', 404, $now, $today, null, 'unknown');
        }

        if ($student->status !== 'Enrolled') {
            return $this->reject($uid, 'enrollment_not_confirmed', 'Student is not enrolled for the current school year', 403, $now, $today, $student, 'denied');
        }

        if (! $student->enrollment_confirmed || $student->school_year !== Student::currentSchoolYear()) {
            return $this->reject($uid, 'enrollment_not_confirmed', 'Enrollment is not confirmed for the current school year', 403, $now, $today, $student, 'denied');
        }

        if ($student->valid_until && Carbon::parse($student->valid_until)->lt(today())) {
            return $this->reject($uid, 'expired_card', 'This temporary/visitor card has expired', 403, $now, $today, $student, 'expired');
        }

        $todayLog = EntryLog::where('student_id', $student->id)
            ->where('date', $today)
            ->where('status', 'granted')
            ->latest('id')
            ->first();

        if ($todayLog) {
            if (is_null($todayLog->time_out)) {
                $todayLog->update([
                    'time_out' => $now->format('H:i:s'),
                ]);
                $action = 'time_out';
            } else {
                $todayLog->update([
                    'time_in' => $now->format('H:i:s'),
                    'time_out' => null,
                ]);
                $action = 'time_in';
            }
        } else {
            EntryLog::create([
                'student_id' => $student->id,
                'card_uid' => $uid,
                'date' => $today,
                'time_in' => $now->format('H:i:s'),
                'time_out' => null,
                'status' => 'granted',
            ]);
            $action = 'time_in';
        }

        if ($action === 'time_in') {
            $this->uniSms->sendEntryNotification($student);
        }

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'student' => $student->first_name.' '.$student->last_name,
        ]);
    }

    public function latest(): JsonResponse
    {
        // The guard's live entry feed is backed by EntryLog, so the registrar
        // reads the newest scan from the same source instead of a second store.
        $log = EntryLog::whereDate('date', today())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (! $log) {
            return response()->json(['uid' => null, 'nonce' => null, 'at' => null]);
        }

        $owner = null;

        if ($log->card_uid) {
            $cardUid = strtoupper(preg_replace('/\s+/', '', $log->card_uid));
            $student = Student::whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$cardUid])->first();

            if ($student) {
                $owner = trim($student->first_name.' '.$student->last_name);
            }
        }

        return response()->json([
            'uid' => $log->card_uid,
            'nonce' => $log->id.'-'.$log->updated_at->timestamp,
            'at' => $log->updated_at->toIso8601String(),
            'owner' => $owner,
        ]);
    }

    /**
     * Arm registration scan mode from the registrar's Add Student modal.
     * While armed, taps are captured for enrollment instead of logged as entries.
     */
    public function beginRegistration(): JsonResponse
    {
        return $this->beginRegistrationScan(self::CONTEXT_REGISTRAR);
    }

    /**
     * Disarm registration scan mode (e.g. the registrar closed the modal).
     */
    public function endRegistration(): JsonResponse
    {
        return $this->endRegistrationScan(self::CONTEXT_REGISTRAR);
    }

    /**
     * Return the last card captured while registration scan mode was active.
     */
    public function latestRegistrationScan(): JsonResponse
    {
        return $this->latestRegistrationScanFor(self::CONTEXT_REGISTRAR);
    }

    /**
     * Arm scan mode from the guard's Register Visitor form. While armed,
     * taps are captured for the visitor pass instead of the live entry feed.
     */
    public function beginVisitorRegistration(): JsonResponse
    {
        return $this->beginRegistrationScan(self::CONTEXT_VISITOR);
    }

    public function endVisitorRegistration(): JsonResponse
    {
        return $this->endRegistrationScan(self::CONTEXT_VISITOR);
    }

    public function latestVisitorRegistrationScan(): JsonResponse
    {
        return $this->latestRegistrationScanFor(self::CONTEXT_VISITOR);
    }

    private function beginRegistrationScan(string $context): JsonResponse
    {
        $until = now()->addSeconds(120);

        Cache::put(self::REGISTRATION_CONTEXT_KEY, $context, $until);
        Cache::put(self::REGISTRATION_UNTIL_KEY, $until->timestamp, $until);
        Cache::forget(self::REGISTRATION_LAST_KEY);

        return response()->json([
            'context' => $context,
            'active_until' => $until->toIso8601String(),
        ]);
    }

    private function endRegistrationScan(string $context): JsonResponse
    {
        if ($this->registrationContextActive() === $context) {
            Cache::forget(self::REGISTRATION_UNTIL_KEY);
            Cache::forget(self::REGISTRATION_CONTEXT_KEY);
        }

        return response()->json(['ok' => true]);
    }

    private function latestRegistrationScanFor(string $context): JsonResponse
    {
        $scan = Cache::get(self::REGISTRATION_LAST_KEY);

        if (! is_array($scan) || ($scan['context'] ?? null) !== $context) {
            return response()->json(['uid' => null, 'nonce' => null, 'at' => null, 'owner' => null]);
        }

        return response()->json($scan);
    }

    private function registrationContextActive(): ?string
    {
        $until = Cache::get(self::REGISTRATION_UNTIL_KEY);

        if (! is_numeric($until) || now()->timestamp >= (int) $until) {
            return null;
        }

        $context = Cache::get(self::REGISTRATION_CONTEXT_KEY);

        return is_string($context) ? $context : null;
    }

    private function captureRegistrationScan(string $context, string $uid, ?Student $student): JsonResponse
    {
        Cache::forget(self::REGISTRATION_UNTIL_KEY);
        Cache::forget(self::REGISTRATION_CONTEXT_KEY);

        $owner = $student ? trim($student->first_name.' '.$student->last_name) : null;

        // For visitor passes, warn when the tag already belongs to an active
        // visitor. Tags from removed visitors are reusable.
        if ($owner === null && $context === self::CONTEXT_VISITOR) {
            $visitor = Visitor::active()
                ->whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$uid])
                ->first();

            $owner = $visitor?->visitor_name;
        }

        $scan = [
            'uid' => $uid,
            'nonce' => $uid.'-'.Str::random(10),
            'at' => now()->toIso8601String(),
            'owner' => $owner,
            'context' => $context,
        ];

        Cache::put(self::REGISTRATION_LAST_KEY, $scan, now()->addMinutes(2));

        return response()->json([
            'status' => 'registration',
            'uid' => $uid,
            'owner' => $scan['owner'],
            'nonce' => $scan['nonce'],
            'context' => $context,
        ]);
    }

    private function reject(
        string $uid,
        string $reason,
        string $message,
        int $httpStatus,
        CarbonInterface $now,
        string $today,
        ?Student $student = null,
        string $logStatus = 'denied'
    ): JsonResponse {
        EntryLog::create([
            'student_id' => $student?->id,
            'card_uid' => $uid,
            'date' => $today,
            'time_in' => $now->format('H:i:s'),
            'status' => $logStatus,
            'reason' => $reason,
        ]);

        $name = $student
            ? $student->first_name.' '.$student->last_name
            : null;

        return response()->json([
            'status' => $reason,
            'reason' => $reason,
            'message' => $message,
            'uid' => $uid,
            'student' => $name,
            'visitor' => false,
        ], $httpStatus);
    }
}
