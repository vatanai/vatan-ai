<?php

namespace App\Services;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiProviderConnectionTester
{
    public function test(string $provider): array
    {
        $provider = strtolower(trim($provider));
        $credentials = app(AiProviderCredentials::class)->for($provider);
        if (blank($credentials['api_key'])) {
            throw new RuntimeException('کلید دسترسی این provider ثبت نشده است.');
        }

        $response = match ($provider) {
            'replicate' => Http::withToken($credentials['api_key'])
                ->connectTimeout(15)->timeout($credentials['timeout'])
                ->get(rtrim($credentials['base_url'], '/') . '/account'),
            'fal' => Http::withHeaders(['Authorization' => 'Key ' . $credentials['api_key']])
                ->connectTimeout(15)->timeout($credentials['timeout'])
                ->get((string) config('services.fal.platform_base_url', 'https://api.fal.ai') . '/v1/models/pricing', [
                    'endpoint_id' => 'fal-ai/flux/schnell',
                ]),
            'liara' => Http::withToken($credentials['api_key'])
                ->connectTimeout(15)->timeout($credentials['timeout'])
                ->get(rtrim($credentials['base_url'], '/') . '/models'),
            default => Http::withToken($credentials['api_key'])
                ->connectTimeout(15)->timeout($credentials['timeout'])
                ->get(rtrim($credentials['base_url'], '/') . '/models'),
        };

        if ($response->failed()) {
            throw new RuntimeException('HTTP ' . $response->status() . ': ' . str($response->body())->limit(500));
        }

        $setting = AiProviderSetting::firstOrCreate(['provider' => $provider]);
        $setting->update(['settings' => array_merge((array) $setting->settings, [
            'last_tested_at' => now()->toIso8601String(),
            'last_test_status' => 'success',
        ])]);

        return ['provider' => $provider, 'status' => 'success', 'http_status' => $response->status()];
    }
}
