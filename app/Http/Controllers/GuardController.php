<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GuardController extends Controller
{
    /**
     * Display Guard Dashboard
     */
    public function dashboard()
   {
    // Only security accounts may open this page
    if (strtolower(trim(Auth::user()->role)) !== 'security') {
        abort(403);
    }

    $today = today();

    // Live feed: granted scans only, most recently scanned first
    $entryLogs = EntryLog::whereDate('date', $today)
        ->where('status', 'granted')
        ->with('student')
        ->orderByDesc('updated_at')
        ->limit(10)
        ->get();

    $studentsVerifiedToday = EntryLog::whereDate('date', $today)
        ->where('status', 'granted')
        ->distinct()
        ->count('student_id');

    $entriesDenied = EntryLog::whereDate('date', $today)
        ->where('status', 'denied')
        ->count();

    $currentlyIn = EntryLog::whereDate('date', $today)
        ->where('status', 'granted')
        ->whereNull('time_out')
        ->count();

    $currentlyOut = EntryLog::whereDate('date', $today)
        ->where('status', 'granted')
        ->whereNotNull('time_out')
        ->count();

    $totalEntries = EntryLog::whereDate('date', $today)
        ->where('status', 'granted')
        ->count();

    // Alerts box: the 5 most recent denied scans
    $deniedEntries = EntryLog::whereDate('date', $today)
        ->where('status', 'denied')
        ->with('student')
        ->orderByDesc('id')
        ->limit(5)
        ->get()
        ->map(fn ($log) => [
            'time' => optional($log->student)->first_name . ' ' . optional($log->student)->last_name
                    . ' at ' . Carbon::parse($log->time_in)->format('H:i'),
        ])
        ->all();

    $visitorsOnCampus = 0; // TODO: connect to a visitors table

    $allStudents = Student::pluck('rfid_tag')->filter()->toArray();
    $availableRfids = $this->generateAvailableRfids($allStudents);

    return view('guards.dashboard', [
        'entryLogs' => $entryLogs,
        'studentsVerifiedToday' => $studentsVerifiedToday,
        'visitorsOnCampus' => $visitorsOnCampus,
        'entriesDenied' => $entriesDenied,
        'totalEntries' => $totalEntries,
        'currentlyIn' => $currentlyIn,
        'currentlyOut' => $currentlyOut,
        'deniedEntries' => $deniedEntries,
        'availableRfids' => $availableRfids,
    ]);
   }

    /**
     * Display Portable Kiosk Screen
     */
    public function kiosk()
    {
        return view('kiosk.display');
    }

    /**
     * Store visitor pass
     */
    public function visitorStore(Request $request)
    {
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'id_type' => 'required|in:drivers_license,passport,national_id',
            'id_number' => 'required|string|max:50',
            'visiting_department' => 'required|string',
            'pass_duration' => 'required|integer|in:1,2,4,8',
            'rfid_tag' => 'required|string|unique:students,rfid_tag',
        ]);

        return redirect()->back()->with('success', 'Visitor pass issued successfully!');
    }

    /**
     * Generate available RFID tags
     */
    private function generateAvailableRfids($assignedTags)
    {
        $availableTags = [];
        
        for ($i = 1001; $i <= 1050; $i++) {
            $tag = 'RFID-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            if (!in_array($tag, $assignedTags)) {
                $availableTags[] = $tag;
            }
        }

        return array_slice($availableTags, 0, 20);
    }
}