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
            // Log the unknown/unregistered card so it shows up in the live feed with details!
            EntryLog::create([
                'student_id' => null, // No student linked
                'card_uid'   => $request->uid, // Save the raw UID if your table has a card_uid column, or omit/adjust based on your schema
                'date'       => $today,
                'time_in'    => $now->format('H:i:s'),
                'status'     => 'unknown', // Triggers red warning row
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

        // Is there an open entry today (time_in but no time_out yet)?
        $openLog = EntryLog::where('student_id', $student->id)
            ->where('date', $today)
            ->where('status', 'granted')
            ->whereNull('time_out')
            ->latest('id')
            ->first();

        if ($openLog) {
            $openLog->update(['time_out' => $now->format('H:i:s')]);
            $action = 'time_out';
        } else {
            EntryLog::create([
                'student_id' => $student->id,
                'date'       => $today,
                'time_in'    => $now->format('H:i:s'),
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