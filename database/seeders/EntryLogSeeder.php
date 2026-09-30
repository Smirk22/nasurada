<?php

namespace Database\Seeders;

use App\Models\EntryLog;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EntryLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::where('status', 'Enrolled')->get();

        if ($students->isEmpty()) {
            $this->command->info('No enrolled students found. Run StudentSeeder first.');
            return;
        }

        // Generate sample entry logs for the last 7 days
        foreach ($students as $student) {
            for ($i = 0; $i < 7; $i++) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');

                // Create separate times for Time In (7-9 AM) and Time Out (4-6 PM)
                $timeIn = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . rand(7, 8) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00');
                $timeOut = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . rand(16, 17) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00');

                EntryLog::create([
                    'student_id' => $student->id,
                    'date' => $date,
                    'time_in' => $timeIn->format('H:i:s'),
                    'time_out' => $timeOut->format('H:i:s'),
                ]);
            }
        }

        $this->command->info('Entry logs seeded successfully!');
    }
}