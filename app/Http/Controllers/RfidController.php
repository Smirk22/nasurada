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

        $uid = strtoupper(trim($request->uid));

        // Matches regardless of case or spaces stored in rfid_tag
        $student = Student::whereRaw("UPPER(REPLACE(rfid_tag, ' ', '')) = ?", [$uid])->first();

        if (!$student) {
            return response()->json(['status' => 'unknown_card'], 404);
        }

        $now   = now();
        $today = $now->toDateString();

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