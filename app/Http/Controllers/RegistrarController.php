<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrarController extends Controller
{
    public function dashboard()
    {
        $totalStudents = Student::count();
        $unassignedRfids = Student::whereNull('rfid_tag')->orWhere('rfid_tag', '')->count();
        $entriesToday = EntryLog::whereDate('date', today())
            ->where('status', 'granted')
            ->count();

        return view('rdashboard', compact('totalStudents', 'unassignedRfids', 'entriesToday'));
    }

    public function students(Request $request)
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('year') && $request->year !== 'All Years') {
            $query->where('year_level', $request->input('year'));
        }

        if ($request->filled('section') && $request->section !== 'All Sections') {
            $query->where('section', $request->input('section'));
        }

        $students = $query->get();

        $assignedRfids = Student::pluck('rfid_tag')->filter()->toArray();
        $availableRfids = $this->generateAvailableRfids($assignedRfids);

        return view('registrar.students', compact('students', 'availableRfids'));
    }

    /**
     * Generate the pool of RFID tags that are not assigned to any student.
     */
    private function generateAvailableRfids(array $assignedTags): array
    {
        $availableTags = [];

        for ($i = 1001; $i <= 1050; $i++) {
            $tag = 'RFID-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            if (! in_array($tag, $assignedTags, true)) {
                $availableTags[] = $tag;
            }
        }

        return $availableTags;
    }

    public function store(Request $request)
    {
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

            // Section - Only predefined sections
            'section' => 'required|in:S1B1,S1B2,S4B1,S7C3',

            // RFID Tag - Optional, unique
            'rfid_tag' => 'nullable|string|unique:students,rfid_tag',
        ], [
            'student_number.regex' => 'Student Number must be 3 letters followed by 8 numbers (e.g., MAR87654320)',
            'student_number.unique' => 'This student number already exists in the system.',
            'first_name.regex' => 'First Name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last Name can only contain letters, spaces, hyphens, and apostrophes.',
            'course.in' => 'Only BSIT course is available for this pilot program.',
            'year_level.in' => 'Year Level must be between 1st and 4th year.',
            'section.in' => 'Selected section is not valid. Only predefined sections are allowed.',
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
        $validated = $request->validate([
            'student_number' => 'required|unique:students,student_number,'.$student->id.'|regex:/^[A-Z]{3}\d{8}$/',
            'first_name' => 'required|string|max:255|regex:/^[a-zA-Z\s\'-]+$/',
            'last_name' => 'required|string|max:255|regex:/^[a-zA-Z\s\'-]+$/',
            'course' => 'required|in:BSIT',
            'year_level' => 'required|integer|in:1,2,3,4',
            'section' => 'required|in:S1B1,S1B2,S4B1,S7C3',
            'rfid_tag' => 'nullable|string|unique:students,rfid_tag,'.$student->id,
            'status' => 'required|string',
        ], [
            'student_number.regex' => 'Student Number must be 3 letters followed by 8 numbers (e.g., MAR87654320)',
            'student_number.unique' => 'This student number already exists in the system.',
            'first_name.regex' => 'First Name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last Name can only contain letters, spaces, hyphens, and apostrophes.',
            'course.in' => 'Only BSIT course is available for this pilot program.',
            'year_level.in' => 'Year Level must be between 1st and 4th year.',
            'section.in' => 'Selected section is not valid. Only predefined sections are allowed.',
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
}
