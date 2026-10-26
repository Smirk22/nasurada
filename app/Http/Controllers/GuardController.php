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

        // Live feed: Get ALL scans (granted, denied, and unknown), most recently scanned first
        $entryLogs = EntryLog::whereDate('date', $today)
            ->with('student')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $studentsVerifiedToday = EntryLog::whereDate('date', $today)
            ->where('status', 'granted')
            ->distinct()
            ->count('student_id');

        $entriesDenied = EntryLog::whereDate('date', $today)
            ->whereIn('status', ['denied', 'unknown'])
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

        // Alerts box: the 5 most recent denied/unknown scans
        $deniedEntries = EntryLog::whereDate('date', $today)
            ->whereIn('status', ['denied', 'unknown'])
            ->with('student')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn ($log) => [
                'time' => ($log->student ? ($log->student->first_name . ' ' . $log->student->last_name) : ('UID: ' . ($log->card_uid ?? 'Unknown')))
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
     * Return the most recent scan as JSON.
     * The Entry Logs display page asks for this every second.
     */
    public function latestScan()
    {
        // Same role check as the dashboard
        if (strtolower(trim(Auth::user()->role)) !== 'security') {
            abort(403);
        }

        // Newest activity first
        $log = EntryLog::whereDate('date', today())
            ->with('student')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        // Nothing scanned today yet
        if (!$log) {
            return response()->json(['scan' => null]);
        }

        $student = $log->student;
        $isUnknown = is_null($student) || $log->status === 'unknown';
        $isDenied  = !$isUnknown && $log->status === 'denied';
        $granted   = !$isUnknown && !$isDenied;
        $isOut     = $granted && $log->time_out !== null;

        return response()->json([
            'scan' => [
                'key'            => $log->id . '-' . $log->updated_at->timestamp,
                'result'         => ($isUnknown || $isDenied) ? 'denied' : 'granted',
                'is_unknown'     => $isUnknown,
                'card_uid'       => $log->card_uid ?? 'Unknown',
                'action'         => $isUnknown ? 'UNKNOWN' : ($isOut ? 'OUT' : 'IN'),
                'time'           => Carbon::parse($log->time_in)->format('h:i:s A'),
                'name'           => $isUnknown ? 'Unregistered Tag' : ($student->first_name . ' ' . $student->last_name),
                'student_number' => $isUnknown ? ('UID: ' . ($log->card_uid ?? 'N/A')) : $student->student_number,
                'initial'        => $isUnknown ? '⚠️' : strtoupper(substr($student->first_name, 0, 1)),
                'photo'          => (!$isUnknown && $student->photo) ? '/storage/' . ltrim($student->photo, '/') : null,
            ],
        ]);
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