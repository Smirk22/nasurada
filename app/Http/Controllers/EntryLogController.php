<?php

namespace App\Http\Controllers;

use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Http\Request;

class EntryLogController extends Controller
{
    /**
     * Number of entry logs shown per page inside the section modal.
     */
    private const LOGS_PER_PAGE = 15;

    /**
     * Display Entry Logs page
     */
    public function index()
    {
        // Build the Year > Section list from the students that actually exist,
        // so scans from the guard's live feed always land in the right bucket.
        $sectionsByYear = Student::query()
            ->whereNotNull('section')
            ->where('section', '<>', '')
            ->select('year_level', 'section')
            ->distinct()
            ->orderBy('year_level')
            ->orderBy('section')
            ->get()
            ->groupBy('year_level')
            ->map(fn ($rows) => $rows->pluck('section')->unique()->values());

        // Always keep years 1-4 visible, in order, even when a year is empty.
        $yearLevels = collect([1, 2, 3, 4])
            ->merge($sectionsByYear->keys())
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn ($year) => [
                $year => $sectionsByYear->get($year, collect()),
            ]);

        $sectionCounts = $this->todayCounts();

        return view('registrar.entry-logs', [
            'yearLevels' => $yearLevels,
            'sectionCounts' => $sectionCounts,
            'totalToday' => array_sum($sectionCounts),
        ]);
    }

    /**
     * Get entry logs by section (API endpoint for AJAX).
     * Optionally narrowed down by year level and date.
     */
    public function getBySection(Request $request, $section)
    {
        $query = EntryLog::query()
            ->whereHas('student', function ($query) use ($request, $section) {
                $query->where('section', $section);

                if ($request->filled('year')) {
                    $query->where('year_level', $request->input('year'));
                }
            })
            ->with('student')
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->orderByDesc('id');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        return response()->json($query->paginate(self::LOGS_PER_PAGE));
    }

    /**
     * Today's verified entry counts grouped by "year|section".
     * Polled by the Entry Logs page to stay in sync with the guard's live feed.
     */
    public function summary()
    {
        $counts = $this->todayCounts();

        return response()->json([
            'counts' => (object) $counts,
            'total' => array_sum($counts),
        ]);
    }

    /**
     * Count of students who entered today, keyed by "year|section".
     *
     * @return array<string, int>
     */
    private function todayCounts(): array
    {
        return EntryLog::query()
            ->whereDate('date', today())
            ->where('status', 'granted')
            ->with('student:id,year_level,section')
            ->get()
            ->filter(fn ($log) => $log->student !== null)
            ->groupBy(fn ($log) => $log->student->year_level.'|'.$log->student->section)
            ->map(fn ($logs) => $logs->count())
            ->all();
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

        if (! $student) {
            return response()->json(['error' => 'Invalid RFID tag'], 404);
        }

        // Check if student has entry log for today
        $today = today();
        $log = EntryLog::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        if (! $log) {
            // Create new entry (Time In)
            $log = EntryLog::create([
                'student_id' => $student->id,
                'date' => $today,
                'time_in' => now()->format('H:i:s'),
                'status' => 'granted',
            ]);
        } else {
            // Update existing entry (Time Out)
            $log->update(['time_out' => now()->format('H:i:s')]);
        }

        return response()->json(['message' => 'Entry logged successfully', 'log' => $log]);
    }
}
