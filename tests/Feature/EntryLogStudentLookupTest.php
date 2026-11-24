<?php

namespace Tests\Feature;

use App\Models\EntryLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class EntryLogStudentLookupTest extends TestCase
{
    use DatabaseMigrations;

    private function registrar(): User
    {
        return User::create([
            'username' => 'registrar-entry-lookup',
            'email' => 'registrar-entry-lookup@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);
    }

    private function student(string $number, string $firstName, string $section): Student
    {
        return Student::create([
            'student_number' => $number,
            'first_name' => $firstName,
            'last_name' => 'Sample',
            'course' => 'BSIT',
            'year_level' => 1,
            'section' => $section,
            'status' => 'Enrolled',
        ]);
    }

    public function test_entry_logs_page_offers_student_number_lookup(): void
    {
        $response = $this->actingAs($this->registrar())->get(route('entry-logs.index'));

        $response->assertOk()
            ->assertSee('Find one student')
            ->assertSee('id="studentNumberInput"', false)
            ->assertSee('id="recentStudentNumberHint"', false)
            ->assertSee('id="recentStudentNumber"', false)
            ->assertSee('Most recent student number searched:')
            ->assertSee('View student logs');
    }

    public function test_lookup_returns_only_the_requested_students_logs_across_all_dates(): void
    {
        $target = $this->student('TST00000001', 'Ashley', 'S1A1');
        $other = $this->student('TST00000002', 'Carlos', 'S1B1');

        for ($i = 0; $i < 16; $i++) {
            EntryLog::create([
                'student_id' => $target->id,
                'date' => today()->subDays($i),
                'time_in' => '08:00:00',
                'status' => 'granted',
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            EntryLog::create([
                'student_id' => $other->id,
                'date' => today()->subDays($i),
                'time_in' => '09:00:00',
                'status' => 'granted',
            ]);
        }

        $this->actingAs($this->registrar());

        $firstPage = $this->getJson(route('entry-logs.getByStudentNumber', [
            'student_number' => 'tst-00000001',
        ]));

        $firstPage->assertOk()
            ->assertJsonPath('student.student_number', 'TST00000001')
            ->assertJsonPath('student.first_name', 'Ashley')
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('total', 16)
            ->assertJsonCount(15, 'data');

        $numbers = collect($firstPage->json('data'))
            ->pluck('student.student_number')
            ->unique()
            ->all();

        $this->assertSame(['TST00000001'], $numbers);

        $secondPage = $this->getJson(route('entry-logs.getByStudentNumber', [
            'student_number' => 'TST00000001',
            'page' => 2,
        ]));

        $secondPage->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(1, 'data');
    }

    public function test_lookup_accepts_a_unique_partial_number_and_reports_ambiguous_or_unknown_numbers(): void
    {
        $this->student('TST00000001', 'Ashley', 'S1A1');
        $this->student('TST00000002', 'Carlos', 'S1B1');

        $this->actingAs($this->registrar());

        $partial = $this->getJson(route('entry-logs.getByStudentNumber', ['student_number' => '00000001']));
        $partial->assertOk()->assertJsonPath('student.student_number', 'TST00000001');

        $ambiguous = $this->getJson(route('entry-logs.getByStudentNumber', ['student_number' => 'TST0000000']));
        $ambiguous->assertStatus(422)->assertJsonPath('error', 'More than one student matches that number. Enter the full student number.');

        $unknown = $this->getJson(route('entry-logs.getByStudentNumber', ['student_number' => 'NOPE12345678']));
        $unknown->assertStatus(404)->assertJsonPath('error', 'No student found with that student number.');

        $empty = $this->getJson(route('entry-logs.getByStudentNumber', ['student_number' => '   ']));
        $empty->assertStatus(422)->assertJsonPath('error', 'Enter a student number.');
    }
}
