<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public const ACTION_CREATED_BACKUP = 'Created Database Backup';

    public const ACTION_DOWNLOADED_BACKUP = 'Downloaded Database Backup';

    public const ACTION_RESTORED_BACKUP = 'Restored Database Backup';

    public const ACTION_DELETED_BACKUP = 'Deleted Database Backup';

    public const ACTION_FAILED_BACKUP = 'Failed Database Backup Action';

    public const ACTION_LOGGED_IN = 'Logged In';

    public const ACTION_FAILED_LOGIN = 'Failed Login Attempt';

    public const ACTION_LOGGED_OUT = 'Logged Out';

    /** @var array<int, string> */
    public const BACKUP_ACTIONS = [
        self::ACTION_CREATED_BACKUP,
        self::ACTION_DOWNLOADED_BACKUP,
        self::ACTION_RESTORED_BACKUP,
        self::ACTION_DELETED_BACKUP,
        self::ACTION_FAILED_BACKUP,
    ];

    /** @var array<int, string> */
    public const ACCESS_ACTIONS = [
        self::ACTION_LOGGED_IN,
        self::ACTION_FAILED_LOGIN,
        self::ACTION_LOGGED_OUT,
    ];

    protected $fillable = [
        'user_name',
        'role',
        'action',
        'details',
    ];

    /**
     * Backup create, download, restore, delete, and failure activity.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeBackupHistory(Builder $query): Builder
    {
        return $query->whereIn('action', self::BACKUP_ACTIONS);
    }

    /**
     * Sign-in, failed sign-in, and sign-out activity.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeAccessHistory(Builder $query): Builder
    {
        return $query->whereIn('action', self::ACCESS_ACTIONS);
    }

    /**
     * General staff/system activity, excluding sign-in and sign-out events.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeWithoutAccessActions(Builder $query): Builder
    {
        return $query->whereNotIn('action', self::ACCESS_ACTIONS);
    }

    /**
     * Optional helper method to record activity logs cleanly from controllers.
     */
    public static function log(string $action, string $details): self
    {
        $user = auth()->user();

        return static::record(
            (string) ($user->username ?? 'System'),
            (string) ($user->role ?? 'System'),
            $action,
            $details
        );
    }

    /**
     * Record activity for an explicit user, e.g. an auth event where the
     * current guard has already been cleared on logout.
     */
    public static function logAs(?User $user, string $action, string $details): self
    {
        return static::record(
            (string) ($user->username ?? 'System'),
            (string) ($user->role ?? 'System'),
            $action,
            $details
        );
    }

    public static function record(string $userName, string $role, string $action, string $details): self
    {
        return static::create([
            'user_name' => $userName,
            'role' => $role,
            'action' => $action,
            'details' => $details,
        ]);
    }
}
