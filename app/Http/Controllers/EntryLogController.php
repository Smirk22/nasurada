<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Http\Request;

class EntryLogController extends Controller
{
    /**
     * Display Entry Logs page
     */
    public function index()
    {
        return view('registrar.entry-logs');
    }

    /**
     * Get entry logs by section (API endpoint for AJAX)
     */
    public function getBySection($section)
    {
        $logs = EntryLog::whereHas('student', function ($query) use ($section) {
            $query->where('section', $section);
        })
        ->with('student')
        ->orderBy('date', 'desc')
        ->get();

        return response()->json($logs);
    }

    /**
     * Store a new entry log (when RFID is scanned)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfid_tag' => 'required|string',
        ]);

        $student = Student::where('rfid_tag', $validated['rfid_tag'])->first();

        if (!$student) {
            return response()->json(['error' => 'Invalid RFID tag'], 404);
        }

        // Check if student has entry log for today
        $today = today();
        $log = EntryLog::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        if (!$log) {
            // Create new entry (Time In)
            $log = EntryLog::create([
                'student_id' => $student->id,
                'date' => $today,
                'time_in' => now()->format('H:i:s'),
            ]);
        } else {
            // Update existing entry (Time Out)
            $log->update(['time_out' => now()->format('H:i:s')]);
        }

        return response()->json(['message' => 'Entry logged successfully', 'log' => $log]);
    }
}