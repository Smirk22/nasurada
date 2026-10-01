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
        // Redirect non-guard users
        if (strtolower(Auth::user()->role) !== 'guard') {
            return redirect('/rdashboard');
        }

        $today = today();

        // Get entry logs for today
        $entryLogs = EntryLog::whereDate('date', $today)
            ->with('student')
            ->orderBy('time_in', 'desc')
            ->limit(10)
            ->get();

        // Count students verified today
        $studentsVerifiedToday = EntryLog::whereDate('date', $today)
            ->distinct('student_id')
            ->count('student_id');

        // Count visitors on campus
        $visitorsOnCampus = 0; // TODO: Connect to visitors table

        // Count entries denied
        $entriesDenied = 0; // TODO: Implement denied entries tracking

        // Calculate currently in/out
        $currentlyIn = EntryLog::whereDate('date', $today)
            ->whereNull('time_out')
            ->count();

        $currentlyOut = EntryLog::whereDate('date', $today)
            ->whereNotNull('time_out')
            ->count();

        $totalEntries = $entryLogs->count();

        // Get available RFID tags (unassigned to students)
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
            'deniedEntries' => [],
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