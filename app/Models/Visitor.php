<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
