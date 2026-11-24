<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StudentRecordConfirmationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_destructive_actions_use_confirmation_modals(): void
    {
        $student = Student::create([
            'student_number' => 'TST00000001',
            'first_name' => 'Ashley',
            'last_name' => 'Santos',
            'course' => 'BSIT',
            'year_level' => 1,
            'section' => 'S1B1',
            'status' => 'Enrolled',
        ]);

        $registrar = User::create([
            'username' => 'registrar-confirmation',
            'email' => 'registrar-confirmation@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);

        $response = $this->actingAs($registrar)->get(route('registrar.students'));

        $response->assertOk()
            ->assertSee('id="endSemesterModal"', false)
            ->assertSee('id="deleteStudentModal"', false)
            ->assertSee('Yes, end semester')
            ->assertSee('Yes, delete student')
            ->assertSee('data-student-name="Ashley Santos"', false)
            ->assertSee('data-delete-url="'.route('registrar.students.destroy', $student->id).'"', false)
            ->assertSee('action="'.route('registrar.students.unenrollAll').'"', false)
            ->assertDontSee('onsubmit="return confirm(', false);
    }
}
