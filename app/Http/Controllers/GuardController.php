<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\SentMessage;
use App\Models\Student;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardController extends Controller
{
    /**
     * Display Guard Dashboard
     */
    public function dashboard(Request $request)
    {
        // Security and Admin may open this page
        $role = strtolower(trim(Auth::user()->role));
        if ($role !== 'security' && $role !== 'admin') {
            abort(403);
        }

        $today = today();

        // Live feed: Show today's last scanned students (unique per student/log)
        $entryLogs = EntryLog::whereDate('date', $today)
            ->with(['student', 'visitor'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->unique(function ($item) {
                return $item->student_id ? 'student_'.$item->student_id : 'log_'.$item->id;
            })
            ->take(10);

        $studentsVerifiedToday = EntryLog::whereDate('date', $today)
            ->where('status', 'granted')
            ->distinct()
            ->count('student_id');

        $entriesDenied = EntryLog::whereDate('date', $today)
            ->whereIn('status', ['denied', 'unknown', 'expired'])
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

        $sentMessagesCount = SentMessage::whereDate('sent_at', $today)->count();

        $sentMessages = SentMessage::whereDate('sent_at', $today)
            ->with('student')
            ->orderByDesc('sent_at')
            ->limit(25)
            ->get();

        // Alerts box: the 5 most recent denied/unknown scans
        $deniedEntries = EntryLog::whereDate('date', $today)
            ->whereIn('status', ['denied', 'unknown', 'expired'])
            ->with(['student', 'visitor'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                $name = $log->student
                    ? trim($log->student->first_name.' '.$log->student->last_name)
                    : ($log->visitor?->visitor_name ?? 'UID: '.($log->card_uid ?? 'Unknown'));

                return [
                    'time' => $name
                        .' at '.Carbon::parse($log->time_in)->timezone(config('app.timezone'))->format('g:i A'),
                ];
            })
            ->all();

        $visitorsOnCampus = Visitor::active()->count();

        $assignedRfidOwners = Student::query()
            ->whereNotNull('rfid_tag')
            ->where('rfid_tag', '!=', '')
            ->get(['first_name', 'last_name', 'rfid_tag'])
            ->mapWithKeys(function (Student $student) {
                $tag = strtoupper(preg_replace('/\s+/', '', $student->rfid_tag));

                return [$tag => trim($student->first_name.' '.$student->last_name)];
            })
            ->all();

        $visitorRfidOwners = Visitor::active()
            ->whereNotNull('rfid_tag')
            ->where('rfid_tag', '!=', '')
            ->get(['visitor_name', 'rfid_tag'])
            ->mapWithKeys(function (Visitor $visitor) {
                $tag = strtoupper(preg_replace('/\s+/', '', $visitor->rfid_tag));

                return [$tag => $visitor->visitor_name.' (visitor)'];
            })
            ->all();

        // Students take precedence when a tag appears in both tables.
        $assignedRfidOwners += $visitorRfidOwners;

        return view('guards.dashboard', [
            'entryLogs' => $entryLogs,
            'studentsVerifiedToday' => $studentsVerifiedToday,
            'visitorsOnCampus' => $visitorsOnCampus,
            'entriesDenied' => $entriesDenied,
            'totalEntries' => $totalEntries,
            'currentlyIn' => $currentlyIn,
            'currentlyOut' => $currentlyOut,
            'sentMessagesCount' => $sentMessagesCount,
            'sentMessages' => $sentMessages,
            'deniedEntries' => $deniedEntries,
            'assignedRfidOwners' => $assignedRfidOwners,
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
        $role = strtolower(trim(Auth::user()->role));
        if ($role !== 'security' && $role !== 'admin') {
            abort(403);
        }

        // Newest activity first
        $log = EntryLog::whereDate('date', today())
            ->with(['student', 'visitor'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        // Nothing scanned today yet
        if (! $log) {
            return response()->json(['scan' => null]);
        }

        $student = $log->student;
        $visitor = $log->visitor;
        $hasPerson = ! is_null($student) || ! is_null($visitor);
        $isUnknown = ! $hasPerson || $log->status === 'unknown';
        $isExpired = ! $isUnknown && $log->status === 'expired';
        $isDenied = ! $isUnknown && ! $isExpired && $log->status === 'denied';
        $granted = ! $isUnknown && ! $isDenied && ! $isExpired;
        $isOut = $granted && $log->time_out !== null;
        $isVisitor = ! $isUnknown && ! is_null($visitor);

        if ($student) {
            $name = trim($student->first_name.' '.$student->last_name);
            $number = $student->student_number;
        } elseif ($visitor) {
            $name = $visitor->visitor_name;
            $number = 'Visitor ID: '.$visitor->id_number;
        } else {
            $name = 'Unregistered Tag';
            $number = 'UID: '.($log->card_uid ?? 'N/A');
        }

        return response()->json([
            'scan' => [
                'key' => $log->id.'-'.$log->updated_at->timestamp,
                'result' => ($isUnknown || $isDenied || $isExpired) ? 'denied' : 'granted',
                'reason' => $log->reason,
                'is_unknown' => $isUnknown,
                'is_expired' => $isExpired,
                'is_visitor' => $isVisitor,
                'card_uid' => $log->card_uid ?? 'Unknown',
                'action' => $isUnknown
                    ? 'UNKNOWN'
                    : ($isExpired
                        ? ($isVisitor ? 'EXPIRED VISITOR ID' : 'EXPIRED')
                        : ($isOut ? 'OUT' : 'IN')),
                'time' => Carbon::parse($log->time_in)->format('h:i:s A'),
                'name' => $isUnknown ? 'Unregistered Tag' : $name,
                'student_number' => $isUnknown ? ('UID: '.($log->card_uid ?? 'N/A')) : $number,
                'initial' => $isUnknown ? '⚠️' : strtoupper(substr($name, 0, 1)),
                'photo' => ($student && $student->photo) ? '/storage/'.ltrim($student->photo, '/') : null,
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
            'rfid_tag' => 'required|string|max:32',
        ]);

        $uid = strtoupper(preg_replace('/\s+/', '', $validated['rfid_tag']));

        $tagAlreadyUsedByStudent = Student::whereRaw(
            "UPPER(REPLACE(rfid_tag, ' ', '')) = ?",
            [$uid]
        )->exists();

        if ($tagAlreadyUsedByStudent) {
            return redirect()->back()->withInput()->withErrors([
                'rfid_tag' => 'This RFID tag is already assigned to a student.',
            ]);
        }

        $visitor = Visitor::whereRaw(
            "UPPER(REPLACE(rfid_tag, ' ', '')) = ?",
            [$uid]
        )->first();

        if ($visitor && $visitor->isActive()) {
            return redirect()->back()->withInput()->withErrors([
                'rfid_tag' => 'This RFID tag is already assigned to an active visitor.',
            ]);
        }

        $data = [
            'visitor_name' => $validated['visitor_name'],
            'id_type' => $validated['id_type'],
            'id_number' => $validated['id_number'],
            'visiting_department' => $validated['visiting_department'],
            'rfid_tag' => $uid,
            'removed_at' => null,
        ];

        if ($visitor) {
            // Reuse the old record when a returned RFID tag is issued again.
            $visitor->update($data);
        } else {
            Visitor::create($data);
        }

        return redirect()->back()->with('success', 'Visitor registered and added to the Visitor\'s List.');
    }

    /**
     * Display the active Visitor's List.
     */
    public function visitors()
    {
        $role = strtolower(trim(Auth::user()->role));
        if ($role !== 'security' && $role !== 'admin') {
            abort(403);
        }

        return view('guards.visitors', [
            'visitors' => Visitor::active()->latest()->get(),
            'removedCount' => Visitor::whereNotNull('removed_at')->count(),
        ]);
    }

    /**
     * Remove a visitor. The record is kept for history, but the RFID tag is no
     * longer treated as an expired visitor ID when scanned. Removed visitors
     * are skipped by the scan lookup, and their old tag can be assigned again.
     */
    public function visitorDestroy(Visitor $visitor)
    {
        $role = strtolower(trim(Auth::user()->role));
        if ($role !== 'security' && $role !== 'admin') {
            abort(403);
        }

        $visitor->update(['removed_at' => now()]);

        return redirect()->back()->with(
            'success',
            "{$visitor->visitor_name} was removed. Their RFID tag is now available for a new visitor."
        );
    }
}
