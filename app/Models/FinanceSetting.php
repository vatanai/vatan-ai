<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        $fallback = config("finance.setting_defaults.{$key}", $default);
        $setting = static::query()->where('key', $key)->first();

        return $setting ? $setting->value : $fallback;
    }
}
