<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|max:32',
        ]);

        $uid = strtoupper(preg_replace('/\s+/', '', $request->uid));

        $student = Student::whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$uid])->first();

        $now = now();
        $today = $now->toDateString();

        if (! $student) {
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

        return response()->json([
            'status' => $reason,
            'reason' => $reason,
            'message' => $message,
            'uid' => $uid,
            'student' => $student ? $student->first_name.' '.$student->last_name : null,
        ], $httpStatus);
    }
}
