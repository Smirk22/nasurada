<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required|string|max:32',
        ]);

        // Remove ALL whitespace and uppercase, so "BB 42 04 23" becomes "BB420423"
        $uid = strtoupper(preg_replace('/\s+/', '', $request->uid));

        // Matches regardless of case or spaces stored in rfid_tag
        $student = Student::whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$uid])->first();

        $now   = now();
        $today = $now->toDateString();

        if (!$student) {
            // Log the unknown/unregistered card so it shows up in the live feed
            EntryLog::create([
                'student_id' => null, 
                'card_uid'   => $request->uid, 
                'date'       => $today,
                'time_in'    => $now->format('H:i:s'),
                'status'     => 'unknown', 
            ]);

            return response()->json([
                'status'  => 'unknown_card',
                'message' => 'Unregistered RFID tag detected',
                'uid'     => $uid
            ], 404);
        }

        // Unenrolled or graduated students are denied and logged
        if ($student->status !== 'Enrolled') {
            EntryLog::create([
                'student_id' => $student->id,
                'date'       => $today,
                'time_in'    => $now->format('H:i:s'),
                'status'     => 'denied',
            ]);

            return response()->json([
                'status'  => 'denied',
                'student' => $student->first_name . ' ' . $student->last_name,
            ], 403);
        }

        // ==========================================
        // SINGLE ROW PER STUDENT / TOGGLE TIME IN & OUT
        // ==========================================
        
        // Find if this student already has an entry log for today
        $todayLog = EntryLog::where('student_id', $student->id)
            ->where('date', $today)
            ->where('status', 'granted')
            ->latest('id')
            ->first();

        if ($todayLog) {
            // If they already have a log for today:
            if (is_null($todayLog->time_out)) {
                // If they haven't timed out yet, set the time out (Time Out action)
                $todayLog->update([
                    'time_out' => $now->format('H:i:s')
                ]);
                $action = 'time_out';
            } else {
                // If they already completed a cycle today and tap again, 
                // reset/update time_in to the new tap and clear time_out for a re-entry
                $todayLog->update([
                    'time_in'  => $now->format('H:i:s'),
                    'time_out' => null
                ]);
                $action = 'time_in';
            }
        } else {
            // First tap of the day: Create the single daily record for this student
            EntryLog::create([
                'student_id' => $student->id,
                'date'       => $today,
                'time_in'    => $now->format('H:i:s'),
                'time_out'   => null,
                'status'     => 'granted',
            ]);
            $action = 'time_in';
        }

        return response()->json([
            'status'  => 'success',
            'action'  => $action,
            'student' => $student->first_name . ' ' . $student->last_name,
        ]);
    }
}