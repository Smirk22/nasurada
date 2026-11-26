<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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

    private function student(int $index, string $section = 'S1B1', string $firstName = 'Student', string $lastName = 'Sample'): Student
    {
        return Student::create([
            'student_number' => 'TST'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
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

    public function test_student_search_matches_full_names(): void
    {
        $this->student(1, firstName: 'Ashley', lastName: 'Santos');
        $this->student(2, firstName: 'Carlos', lastName: 'Reyes');

        $registrar = $this->registrar();

        foreach (['Ashley Santos', 'Santos Ashley', 'ashley santos', '  Ashley   Santos  ', 'Santos, Ashley'] as $term) {
            $response = $this->actingAs($registrar)
                ->get(route('registrar.students', ['search' => $term]));

            $response->assertOk();
            $this->assertSame(1, $response->viewData('students')->total(), $term);
        }
    }

    public function test_student_search_matches_student_numbers(): void
    {
        $this->student(1, firstName: 'Ashley', lastName: 'Santos');
        $this->student(2, firstName: 'Carlos', lastName: 'Reyes');

        $registrar = $this->registrar();

        foreach (['TST00000001', 'tst00000001', '00000001', 'TST 00000001', 'TST-00000001', '#TST00000001'] as $term) {
            $response = $this->actingAs($registrar)
                ->get(route('registrar.students', ['search' => $term]));

            $response->assertOk();
            $this->assertSame(1, $response->viewData('students')->total(), $term);
        }
    }

    public function test_student_search_accepts_punctuation_in_names(): void
    {
        $this->student(1, firstName: 'Jean-Luc', lastName: "D'Angelo");
        $this->student(2, firstName: 'Ashley', lastName: 'Santos');

        $response = $this->actingAs($this->registrar())
            ->get(route('registrar.students', ['search' => "Jean-Luc D'Angelo"]));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('students')->total());
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

    public function test_superadmin_audit_log_is_paginated_and_keeps_filters_and_tab(): void
    {
        for ($i = 1; $i <= 22; $i++) {
            ActivityLog::create([
                'user_name' => 'Audit User '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'role' => 'Admin',
                'action' => 'Audited',
                'details' => 'Entry '.$i,
            ]);
        }

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin-pagination',
            'email' => 'superadmin-pagination@example.com',
            'password' => bcrypt('password'),
            'role' => 'SuperAdmin',
        ]);

        $firstPage = $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard', ['q' => 'Audit User']));

        $firstPage->assertOk()
            ->assertSee('page=2', false);

        $paginator = $firstPage->viewData('activities');

        $this->assertSame(22, $paginator->total());
        $this->assertSame(10, $paginator->perPage());
        $this->assertCount(10, $paginator->items());
        $this->assertStringContainsString('q=', $paginator->url(2));
        $this->assertStringContainsString('page=2', $paginator->url(2));
        $this->assertStringEndsWith('#activity', $paginator->url(2));

        $secondPage = $this->get(route('superadmin.dashboard', ['q' => 'Audit User', 'page' => 2]));

        $secondPage->assertOk();
        $this->assertCount(10, $secondPage->viewData('activities')->items());

        $thirdPage = $this->get(route('superadmin.dashboard', ['q' => 'Audit User', 'page' => 3]));

        $thirdPage->assertOk();
        $this->assertCount(2, $thirdPage->viewData('activities')->items());
    }
}
