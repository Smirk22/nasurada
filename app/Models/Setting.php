<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Read a single setting value, returning the default when it is missing.
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            $value = static::query()->where('key', $key)->value('value');

            return $value === null ? $default : $value;
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * Read a setting as a boolean (handles "1", "0", "true", "false").
     */
    public static function boolean(string $key, bool $default = false): bool
    {
        return filter_var(static::value($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Insert or update a setting value.
     */
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value],
        );
    }

    /**
     * All settings as a key => value map.
     *
     * @return array<string, string|null>
     */
    public static function allSettings(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return static::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            return [];
        }
    }
}
