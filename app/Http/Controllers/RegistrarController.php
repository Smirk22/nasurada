<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use Illuminate\Http\Request;

class RegistrarController extends Controller
{
    public function dashboard()
    {
        $totalStudents = Student::count();
        $unassignedRfids = Student::whereNull('rfid_tag')->orWhere('rfid_tag', '')->count();

        return view('rdashboard', compact('totalStudents', 'unassignedRfids'));
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

        return view('registrar.students', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_number' => 'required|unique:students,student_number',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'course' => 'required|string|max:100',
            'year_level' => 'required|integer',
            'section' => 'required|string|max:50',
            'rfid_tag' => 'nullable|string|unique:students,rfid_tag',
        ]);

        $validated['status'] = 'Enrolled';

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
            'student_number' => 'required|unique:students,student_number,'.$student->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'course' => 'required|string|max:100',
            'year_level' => 'required|integer',
            'section' => 'required|string|max:50',
            'rfid_tag' => 'nullable|string|unique:students,rfid_tag,'.$student->id,
            'status' => 'required|string',
        ]);

        $student->update($validated);

        ActivityLog::log(
            'Updated Student Record',
            "Updated record for {$student->first_name} {$student->last_name} ({$student->student_number}) - Status: {$student->status}"
        );

        return redirect()->back()->with('success', 'Student record updated!');
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
