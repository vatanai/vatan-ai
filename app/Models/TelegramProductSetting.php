<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramProductSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function value(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');
        return $value !== null && trim((string) $value) !== '' ? (string) $value : $default;
    }

    public static function put(string $key, string $value): self
    {
        return static::query()->updateOrCreate(['key' => $key], ['value' => trim($value)]);
    }
}
