<?php

namespace Tests\Feature;

use App\Models\EntryLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use DatabaseMigrations;

    private function registrar(): User
    {
        return User::create([
            'username' => 'registrar-pagination',
            'email' => 'registrar-pagination@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);
    }

    private function student(int $index, string $section = 'S1B1', string $firstName = 'Student'): Student
    {
        return Student::create([
            'student_number' => 'TST'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => 'Sample',
            'course' => 'BSIT',
            'year_level' => 1,
            'section' => $section,
            'status' => 'Enrolled',
        ]);
    }

    public function test_student_records_are_paginated(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->student($i);
        }

        $registrar = $this->registrar();

        $firstPage = $this->actingAs($registrar)->get(route('registrar.students'));
        $firstPage->assertOk();

        $paginator = $firstPage->viewData('students');
        $this->assertSame(30, $paginator->total());
        $this->assertCount(25, $paginator->items());

        // The compact paginator override is used instead of the framework default.
        $firstPage->assertSee('student records')
            ->assertSee('page=2', false)
            ->assertDontSee('results');

        $secondPage = $this->get(route('registrar.students', ['page' => 2]));
        $secondPage->assertOk();
        $this->assertCount(5, $secondPage->viewData('students')->items());
    }

    public function test_student_search_is_kept_in_pagination_links(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->student($i, firstName: $i <= 3 ? 'Juan' : 'Maria');
        }

        $response = $this->actingAs($this->registrar())
            ->get(route('registrar.students', ['search' => 'Juan']));

        $response->assertOk();

        $paginator = $response->viewData('students');
        $this->assertSame(3, $paginator->total());
        $this->assertStringContainsString('search=Juan', $paginator->url(1));
    }

    public function test_entry_logs_by_section_are_paginated(): void
    {
        $student = $this->student(1);

        for ($i = 0; $i < 20; $i++) {
            EntryLog::create([
                'student_id' => $student->id,
                'date' => today()->subDays($i),
                'time_in' => '07:30:00',
                'status' => 'granted',
            ]);
        }

        $registrar = $this->registrar();

        $firstPage = $this->actingAs($registrar)
            ->getJson(route('entry-logs.getBySection', ['section' => 'S1B1']));

        $firstPage->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('total', 20)
            ->assertJsonCount(15, 'data');

        $secondPage = $this->getJson(route('entry-logs.getBySection', ['section' => 'S1B1', 'page' => 2]));

        $secondPage->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data');
    }
}
