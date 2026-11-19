<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed initial registrar account
        User::create([
            'username' => 'admin',
            'email' => 'admin@ieti.edu.ph',
            'password' => 'password123',
            'role' => 'registrar',
        ]);

        // Seed initial student records
        $students = [
            ['student_number' => 'MAR87654321', 'first_name' => 'Ashley', 'last_name' => 'Santos', 'course' => 'BSIT', 'year_level' => 3, 'section' => 'S2B1', 'status' => 'Enrolled'],
            ['student_number' => 'MAR87654320', 'first_name' => 'Louie',  'last_name' => 'Reyes',  'course' => 'BSIT', 'year_level' => 2, 'section' => 'S3B1', 'status' => 'Enrolled'],
            ['student_number' => 'MAR87654323', 'first_name' => 'Mark',   'last_name' => 'Cruz',   'course' => 'BSIT', 'year_level' => 4, 'section' => 'S1B1', 'status' => 'Enrolled'],
            ['student_number' => 'MAR87654324', 'first_name' => 'John',   'last_name' => 'Dizon',  'course' => 'BSIT', 'year_level' => 1, 'section' => 'S4B2', 'status' => 'Enrolled'],
            ['student_number' => 'MAR22345678', 'first_name' => 'Greg',   'last_name' => 'Torres', 'course' => 'BSIT', 'year_level' => 1, 'section' => 'S4B1', 'status' => 'Enrolled'],
        ];

        foreach ($students as $student) {
            // The RFID gate rejects students whose enrollment is not confirmed
            // for the current school year, so seed those fields explicitly.
            Student::create($student + [
                'school_year' => Student::currentSchoolYear(),
                'enrollment_confirmed' => true,
            ]);
        }

        // This runs automatically when seeding
        $this->call([
            AccountSeeder::class,
            SuperAdminSeeder::class,
        ]);

    }
}
