<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'course',
        'year_level',
        'section',
        'phone_number',
        'rfid_tag',
        'photo',
        'status',
        'school_year',
        'enrollment_confirmed',
        'card_type',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_confirmed' => 'boolean',
            'valid_until' => 'date',
        ];
    }

    // Helper attribute to get full name easily
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public static function currentSchoolYear(): string
    {
        $configured = config('app.school_year');

        if ($configured) {
            return $configured;
        }

        $start = now()->month >= 6 ? now()->year : now()->year - 1;

        return $start.'-'.($start + 1);
    }
}
