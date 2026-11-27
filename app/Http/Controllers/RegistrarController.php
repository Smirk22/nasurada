<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrarController extends Controller
{
    /**
     * Number of student records shown per page.
     */
    private const STUDENTS_PER_PAGE = 25;

    /**
     * Allowed sections for each year level.
     *
     * @var array<int, array<int, string>>
     */
    private const SECTIONS_BY_YEAR = [
        1 => ['S1A1', 'S1B1', 'S1C1'],
        2 => ['S3B1', 'S3C1'],
        3 => ['S5B2', 'S5C2'],
        4 => ['S7C3', 'S7C4'],
    ];

    public function dashboard()
    {
        $totalStudents = Student::count();
        $sections = collect(self::SECTIONS_BY_YEAR)->flatten()->all();
        $entriesToday = EntryLog::whereDate('date', today())
            ->where('status', 'granted')
            ->count();

        // Year > Section breakdown
        $sectionBreakdown = Student::query()
            ->selectRaw('year_level, section, COUNT(*) as total')
            ->whereNotNull('section')
            ->where('section', '<>', '')
            ->groupBy('year_level', 'section')
            ->orderBy('year_level')
            ->orderBy('section')
            ->get()
            ->groupBy('year_level');

        // Today's granted entries grouped by section
        $entrySummaryToday = EntryLog::query()
            ->whereDate('date', today())
            ->where('status', 'granted')
            ->with('student:id,year_level,section')
            ->get()
            ->filter(fn ($log) => $log->student !== null)
            ->groupBy(fn ($log) => $log->student->year_level.' — '.$log->student->section)
            ->map->count()
            ->sortKeys();

        // Enrollment status overview
        $enrollmentStatus = Student::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Recent student-related activity
        $recentActivities = ActivityLog::query()
            ->where(function ($q) {
                $q->where('action', 'like', '%Student%')
                    ->orWhere('action', 'End of Semester Triggered');
            })
            // Administrative actions belong in the Admin / Super Admin audit
            // logs, not in the Registrar's recent student activity.
            ->whereRaw("LOWER(COALESCE(role, '')) NOT IN (?, ?)", ['admin', 'superadmin'])
            ->latest()
            ->take(10)
            ->get();

        return view('rdashboard', compact(
            'totalStudents',
            'sections',
            'entriesToday',
            'sectionBreakdown',
            'entrySummaryToday',
            'enrollmentStatus',
            'recentActivities'
        ));
    }

    public function students(Request $request)
    {
        $students = $this->filteredStudents($request)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(self::STUDENTS_PER_PAGE)
            ->withQueryString();

        $assignedRfidOwners = Student::query()
            ->whereNotNull('rfid_tag')
            ->where('rfid_tag', '!=', '')
            ->get(['first_name', 'last_name', 'rfid_tag'])
            ->mapWithKeys(function (Student $student) {
                $tag = strtoupper(preg_replace('/\s+/', '', $student->rfid_tag));

                return [$tag => trim($student->first_name.' '.$student->last_name)];
            })
            ->all();

        $latestStudentNumber = Student::query()
            ->latest('id')
            ->value('student_number');

        $totalStudents = Student::count();

        return view('registrar.students', compact(
            'students',
            'assignedRfidOwners',
            'latestStudentNumber',
            'totalStudents'
        ));
    }

    /**
     * Download student records as an Excel-compatible file for offline use.
     */
    public function exportStudents(Request $request): StreamedResponse
    {
        $query = $this->filteredStudents($request)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');

        $count = (clone $query)->count();

        ActivityLog::log(
            'Exported Student Records',
            "Downloaded {$count} student record(s) as an Excel file."
        );

        $filename = 'student_records_'.now()->format('Ymd_His').'.xls';

        return response()->streamDownload(function () use ($query) {
            $escape = static function (string|int|float|null $value): string {
                return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
            };

            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            echo '<table border="1"><thead><tr>';

            foreach ([
                'Student Number',
                'First Name',
                'Last Name',
                'Course',
                'Year Level',
                'Section',
                'Phone Number',
                'RFID Tag',
                'Status',
                'School Year',
                'Enrollment Confirmed',
                'Card Type',
                'Valid Until',
                'Photo',
                'Registered',
                'Updated',
            ] as $heading) {
                echo '<th>'.$escape($heading).'</th>';
            }

            echo '</tr></thead><tbody>';

            $query->chunk(500, function ($students) use ($escape) {
                foreach ($students as $student) {
                    echo '<tr>';
                    echo '<td style="mso-number-format:\@">'.$escape($student->student_number).'</td>';
                    echo '<td>'.$escape($student->first_name).'</td>';
                    echo '<td>'.$escape($student->last_name).'</td>';
                    echo '<td>'.$escape($student->course).'</td>';
                    echo '<td>'.$escape($student->year_level).'</td>';
                    echo '<td>'.$escape($student->section).'</td>';
                    echo '<td style="mso-number-format:\@">'.$escape($student->phone_number).'</td>';
                    echo '<td style="mso-number-format:\@">'.$escape($student->rfid_tag).'</td>';
                    echo '<td>'.$escape($student->status).'</td>';
                    echo '<td>'.$escape($student->school_year).'</td>';
                    echo '<td>'.$escape($student->enrollment_confirmed ? 'Yes' : 'No').'</td>';
                    echo '<td>'.$escape($student->card_type).'</td>';
                    echo '<td>'.$escape($student->valid_until).'</td>';
                    echo '<td>'.$escape($student->photo).'</td>';
                    echo '<td>'.$escape($student->created_at?->format('M j, Y g:i A')).'</td>';
                    echo '<td>'.$escape($student->updated_at?->format('M j, Y g:i A')).'</td>';
                    echo '</tr>';
                }
            });

            echo '</tbody></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    /**
     * Download a grouped CSV report of the student records, honouring the
     * same section filter used by the student list and records export.
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $generatedAt = now();
        $query = $this->filteredStudents($request);

        $section = $request->input('section');
        $scope = $section && $section !== 'All Sections' ? (string) $section : 'All Sections';

        $totalStudents = (clone $query)->count();
        $confirmedStudents = (clone $query)->where('enrollment_confirmed', true)->count();
        $studentsWithRfid = (clone $query)
            ->whereNotNull('rfid_tag')
            ->where('rfid_tag', '!=', '')
            ->count();

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sectionBreakdown = (clone $query)
            ->selectRaw('year_level, section, COUNT(*) as total')
            ->whereNotNull('section')
            ->where('section', '<>', '')
            ->groupBy('year_level', 'section')
            ->orderBy('year_level')
            ->orderBy('section')
            ->get();

        $cardTypeCounts = (clone $query)
            ->selectRaw('card_type, COUNT(*) as total')
            ->groupBy('card_type')
            ->pluck('total', 'card_type');

        $rows = [
            ['Generated At', $generatedAt->format('Y-m-d H:i:s')],
            ['Scope', $scope],
            [],
            ['Enrollment Summary'],
            ['Metric', 'Students'],
            ['Total Students', $totalStudents],
            ['Enrollment Confirmed', $confirmedStudents],
            ['Enrollment Not Confirmed', $totalStudents - $confirmedStudents],
        ];

        foreach ($statusCounts as $status => $count) {
            $rows[] = ['Status: '.($status ?: 'Unspecified'), $count];
        }

        $rows[] = [];
        $rows[] = ['Students by Year and Section'];
        $rows[] = ['Year Level', 'Section', 'Students'];

        foreach ($sectionBreakdown as $breakdown) {
            $rows[] = [$breakdown->year_level, $breakdown->section, $breakdown->total];
        }

        $rows[] = [];
        $rows[] = ['RFID and Access Card Summary'];
        $rows[] = ['Metric', 'Students'];
        $rows[] = ['Total Students', $totalStudents];
        $rows[] = ['With Registered RFID', $studentsWithRfid];
        $rows[] = ['Without Registered RFID', $totalStudents - $studentsWithRfid];

        foreach ($cardTypeCounts as $cardType => $count) {
            $rows[] = ['Card Type: '.($cardType ?: 'Unspecified'), $count];
        }

        return $this->streamCsv(
            'student_reports_'.$generatedAt->format('Ymd_His').'.csv',
            ['IETI SEVS - STUDENT REPORTS'],
            $rows,
            'Exported Student Reports',
            "Downloaded the student records report for {$scope} as a CSV file."
        );
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function streamCsv(string $filename, array $headers, iterable $rows, string $action, string $details): StreamedResponse
    {
        ActivityLog::log($action, $details);

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($value) => $this->csvValue($value), $row));
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function csvValue(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^\s*[=+\-@]/', $text) ? "'".$text : $text;
    }

    /**
     * Apply the same search and filter inputs used by the student list.
     *
     * @return Builder<Student>
     */
    private function filteredStudents(Request $request): Builder
    {
        $query = Student::query();

        if ($request->filled('search')) {
            // Match each word against the name/number columns so searches such
            // as "Juan Cruz" or "MAR-87654321" find the right student.
            $terms = preg_split('/[^\p{L}\p{N}]+/u', trim((string) $request->input('search'))) ?: [];

            foreach ($terms as $term) {
                if ($term === '') {
                    continue;
                }

                $query->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('student_number', 'like', "%{$term}%");
                });
            }
        }

        if ($request->filled('year') && $request->input('year') !== 'All Years') {
            $query->where('year_level', $request->input('year'));
        }

        if ($request->filled('section') && $request->input('section') !== 'All Sections') {
            $query->where('section', $request->input('section'));
        }

        return $query;
    }

    public function store(Request $request)
    {
        $request->merge([
            'student_number' => strtoupper(trim((string) $request->input('student_number'))),
        ]);

        $validated = $request->validate([
            // Student Number - Required, Unique, Format: 3 letters + 8 numbers (e.g., MAR87654320)
            'student_number' => 'required|unique:students,student_number|regex:/^[A-Z]{3}\d{8}$/',

            // First Name - Letters only, no numbers or special characters
            'first_name' => 'required|string|max:255|regex:/^[a-zA-Z\s\'-]+$/',

            // Last Name - Letters only, no numbers or special characters
            'last_name' => 'required|string|max:255|regex:/^[a-zA-Z\s\'-]+$/',

            // Course - Only BSIT allowed (pilot program)
            'course' => 'required|in:BSIT',

            // Year Level - Only 1, 2, 3, or 4
            'year_level' => 'required|integer|in:1,2,3,4',

            // Section - Must match the selected year level
            'section' => ['required', 'string', Rule::in($this->sectionsForYear($request->input('year_level')))],

            // Phone Number - Optional, Philippine mobile number (e.g., 09171234567 or +639171234567)
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^(?:\+?63|0)9\d{9}$/'],

            // RFID Tag - Optional, unique
            'rfid_tag' => 'nullable|string|unique:students,rfid_tag',
        ], [
            'student_number.regex' => 'Student Number must be 3 letters followed by 8 numbers (e.g., MAR87654320)',
            'student_number.unique' => 'This student number already exists in the system.',
            'first_name.regex' => 'First Name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last Name can only contain letters, spaces, hyphens, and apostrophes.',
            'course.in' => 'Only BSIT course is available for this pilot program.',
            'year_level.in' => 'Year Level must be between 1st and 4th year.',
            'section.in' => 'Selected section is not valid for the chosen year level.',
            'phone_number.regex' => 'Phone Number must be a valid PH mobile number (e.g., 09171234567 or +639171234567).',
            'rfid_tag.unique' => 'This RFID tag is already assigned to another student.',
        ]);

        $validated['status'] = 'Enrolled';
        $validated['school_year'] = Student::currentSchoolYear();
        $validated['enrollment_confirmed'] = true;

        $student = Student::create($validated);

        ActivityLog::log(
            'Added Student Record',
            "Added student {$student->first_name} {$student->last_name} ({$student->student_number})"
        );

        return redirect()->back()->with('success', 'Student added successfully!');
    }

    public function update(Request $request, Student $student)
    {
        $request->merge([
            'student_number' => strtoupper(trim((string) $request->input('student_number'))),
        ]);

        $validated = $request->validate([
            'student_number' => 'required|unique:students,student_number,'.$student->id.'|regex:/^[A-Z]{3}\d{8}$/',
            'first_name' => 'required|string|max:255|regex:/^[a-zA-Z\s\'-]+$/',
            'last_name' => 'required|string|max:255|regex:/^[a-zA-Z\s\'-]+$/',
            'course' => 'required|in:BSIT',
            'year_level' => 'required|integer|in:1,2,3,4',
            'section' => ['required', 'string', Rule::in($this->sectionsForYear($request->input('year_level')))],
            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^(?:\+?63|0)9\d{9}$/'],
            'rfid_tag' => 'nullable|string|unique:students,rfid_tag,'.$student->id,
            'status' => 'required|string',
        ], [
            'student_number.regex' => 'Student Number must be 3 letters followed by 8 numbers (e.g., MAR87654320)',
            'student_number.unique' => 'This student number already exists in the system.',
            'first_name.regex' => 'First Name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last Name can only contain letters, spaces, hyphens, and apostrophes.',
            'course.in' => 'Only BSIT course is available for this pilot program.',
            'year_level.in' => 'Year Level must be between 1st and 4th year.',
            'section.in' => 'Selected section is not valid for the chosen year level.',
            'phone_number.regex' => 'Phone Number must be a valid PH mobile number (e.g., 09171234567 or +639171234567).',
            'rfid_tag.unique' => 'This RFID tag is already assigned to another student.',
        ]);

        $student->update($validated);

        ActivityLog::log(
            'Updated Student Record',
            "Updated record for {$student->first_name} {$student->last_name} ({$student->student_number}) - Status: {$student->status}"
        );

        return redirect()->back()->with('success', 'Student record updated!');
    }

    public function updatePhoto(Request $request, Student $student): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|file|max:2048',
        ], [
            'photo.required' => 'Please choose a photo to upload.',
            'photo.file' => 'The uploaded photo is not a valid file.',
            'photo.max' => 'The photo must not be larger than 2 MB.',
        ]);

        $file = $request->file('photo');
        $extension = strtolower($file->getClientOriginalExtension());
        $imageInfo = @getimagesize($file->getRealPath());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || ! $imageInfo) {
            return redirect()->back()->withErrors([
                'photo' => 'The photo must be a valid JPG, JPEG, PNG, or WebP image.',
            ]);
        }

        $directory = storage_path('app/public/student-photos');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if ($student->photo) {
            $oldPath = storage_path('app/public/'.$student->photo);

            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $filename = Str::random(40).'.'.$extension;

        $file->move($directory, $filename);

        $student->update([
            'photo' => 'student-photos/'.$filename,
        ]);

        ActivityLog::log(
            'Updated Student Photo',
            "Updated photo for {$student->first_name} {$student->last_name} ({$student->student_number})"
        );

        return redirect()->back()->with('success', 'Student photo updated!');
    }

    public function destroy(Student $student)
    {
        $details = "Removed student {$student->first_name} {$student->last_name} ({$student->student_number})";

        $student->delete();

        ActivityLog::log('Unenrolled / Deleted Student', $details);

        return redirect()->back()->with('success', 'Student record deleted.');
    }

    public function unenrollAll()
    {
        Student::query()->update([
            'status' => 'Unenrolled',
            'rfid_tag' => null,
        ]);

        ActivityLog::log(
            'End of Semester Triggered',
            'Unenrolled all students and unassigned active RFID tags.'
        );

        return redirect()->back()->with('success', 'End of Semester process completed. All students un-enrolled and RFID tags unassigned.');
    }

    /**
     * @return array<int, string>
     */
    private function sectionsForYear(mixed $yearLevel): array
    {
        $year = (int) $yearLevel;

        return self::SECTIONS_BY_YEAR[$year] ?? [];
    }
}
