<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'role',
        'action',
        'details',
    ];

    /**
     * Optional helper method to record activity logs cleanly from controllers.
     */
    public static function log(string $action, string $details): self
    {
        return static::create([
            'user_name' => auth()->user()->username ?? 'System',
            'role'      => auth()->user()->role ?? 'System',
            'action'    => $action,
            'details'   => $details,
        ]);
    }
}