<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, ?string $type = null, ?string $group = null, ?string $description = null): self
    {
        if ($type === null) {
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_array($value)) {
                $type = 'json';
            } else {
                $type = 'string';
            }
        }

        $storedValue = $type === 'json' ? json_encode($value) : (string)$value;
        if ($type === 'boolean') {
            $storedValue = $value ? '1' : '0';
        }

        $attributes = [
            'value' => $storedValue,
            'type' => $type,
        ];
        if ($group !== null) {
            $attributes['group'] = $group;
        }
        if ($description !== null) {
            $attributes['description'] = $description;
        }

        $setting = static::updateOrCreate(
            ['key' => $key],
            $attributes
        );

        Cache::forget("setting_{$key}");

        return $setting;
    }

    /**
     * Helper to check if Customer Credit Limit enforcement is enabled.
     */
    public static function isCreditLimitEnabled(): bool
    {
        return (bool) static::get('enable_customer_credit_limit', false);
    }

    /**
     * Helper to check if credit limit 0 means strict zero credit (no dues allowed).
     */
    public static function isCreditLimitStrictZero(): bool
    {
        return (bool) static::get('credit_limit_strict_zero', false);
    }

    /**
     * Cast string value to its configured type.
     */
    protected static function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
