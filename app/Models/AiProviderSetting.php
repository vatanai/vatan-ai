<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderSetting extends Model
{
    protected $fillable = [
        'provider',
        'api_key',
        'base_url',
        'webhook_secret',
        'timeout',
        'max_retries',
        'webhook_enabled',
        'settings',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'timeout' => 'integer',
        'max_retries' => 'integer',
        'webhook_enabled' => 'boolean',
        'settings' => 'array',
    ];

    public static function forProvider(string $provider): ?self
    {
        return static::query()->where('provider', strtolower(trim($provider)))->first();
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key);
    }

    public function maskedApiKey(): string
    {
        if (!$this->hasApiKey()) {
            return 'ثبت نشده';
        }

        $value = (string) $this->api_key;
        return strlen($value) > 8
            ? substr($value, 0, 4) . '••••••••' . substr($value, -4)
            : '••••••••';
    }
}
