<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'cf_settings';
    protected $guarded = ['id'];
    protected $casts = ['value' => 'array'];

    public static function get(string $key, mixed $default = null, int $ws = 1): mixed
    {
        return static::where('workspace_id', $ws)->where('key', $key)->first()?->value ?? $default;
    }
    public static function put(string $key, mixed $value, int $ws = 1): void
    {
        static::updateOrCreate(['workspace_id' => $ws, 'key' => $key], ['value' => $value]);
    }
}
