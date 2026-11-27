<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_name',
        'id_type',
        'id_number',
        'visiting_department',
        'rfid_tag',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'removed_at' => 'datetime',
        ];
    }

    public function entryLogs(): HasMany
    {
        return $this->hasMany(EntryLog::class);
    }

    public function isActive(): bool
    {
        return is_null($this->removed_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('removed_at');
    }

    /**
     * Normalize a student/visitor name so the same credentials entered with
     * different casing or spacing are treated as the same person.
     */
    public static function normalizeName(?string $name): string
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', (string) $name) ?? '';

        return Str::lower(trim(preg_replace('/\s+/', ' ', $cleaned) ?? ''));
    }

    public static function normalizeIdNumber(?string $idNumber): string
    {
        return Str::lower(trim((string) $idNumber));
    }
}
