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

        $sectionCounts = $this->countsFor();

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
        $date = $this->resolveDate($request);

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
            $query->whereDate('date', $date);
        }

        return response()->json($query->paginate(self::LOGS_PER_PAGE));
    }

    /**
     * Verified entry counts for the selected date (today by default),
     * grouped by "year|section". Polled by the Entry Logs page.
     */
    public function summary(Request $request)
    {
        $date = $this->resolveDate($request);
        $counts = $this->countsFor($date);

        return response()->json([
            'counts' => (object) $counts,
            'total' => array_sum($counts),
            'date' => $date,
            'is_today' => $date === today()->toDateString(),
        ]);
    }

    /**
     * Count of students who entered on $date, keyed by "year|section".
     *
     * @return array<string, int>
     */
    private function countsFor(?string $date = null): array
    {
        return EntryLog::query()
            ->whereDate('date', $date ?? today()->toDateString())
            ->where('status', 'granted')
            ->with('student:id,year_level,section')
            ->get()
            ->filter(fn ($log) => $log->student !== null)
            ->groupBy(fn ($log) => $log->student->year_level.'|'.$log->student->section)
            ->map(fn ($logs) => $logs->count())
            ->all();
    }

    /**
     * Resolve a safe Y-m-d date from the request, falling back to today.
     */
    private function resolveDate(Request $request): string
    {
        $date = $request->input('date');

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $year = (int) substr($date, 0, 4);
            $month = (int) substr($date, 5, 2);
            $day = (int) substr($date, 8, 2);

            if (checkdate($month, $day, $year)) {
                return $date;
            }
        }

        return today()->toDateString();
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
