<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\AiProviderSetting;
use App\Services\AiProviderLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AiProviderLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fal_has_safe_limits_even_when_saved_settings_are_incomplete(): void
    {
        AiProviderSetting::query()->updateOrCreate(
            ['provider' => 'fal'],
            ['settings' => ['admin_enabled' => true]]
        );

        $limits = app(AiProviderLimitService::class)->config('fal');

        $this->assertTrue($limits['enabled']);
        $this->assertSame(1, $limits['max_outputs']);
        $this->assertSame(3, $limits['max_failed_requests']);
        $this->assertGreaterThan(0, $limits['max_cost_usd']);
    }

    public function test_fal_failure_circuit_blocks_more_paid_requests_in_same_window(): void
    {
        $setting = AiProviderSetting::query()->updateOrCreate(['provider' => 'fal']);
        $setting->settings = ['usage_limits' => [
            'enabled' => true,
            'window_minutes' => 60,
            'max_requests' => 30,
            'max_cost_usd' => 2,
            'max_concurrent' => 2,
            'max_outputs' => 1,
            'max_failed_requests' => 2,
        ]];
        $setting->save();
        $model = AiModel::forceCreate([
            'name' => 'Fal guarded model',
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
            'is_active' => true,
        ]);
        foreach (range(1, 2) as $index) {
            AiProviderRequest::create([
                'provider' => 'fal',
                'ai_model_id' => $model->id,
                'external_request_id' => 'failed-' . $index,
                'status' => 'failed',
                'estimated_cost_usd' => 0.08,
                'submitted_at' => now(),
            ]);
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('مدار محافظ');
        app(AiProviderLimitService::class)->reserve($model, 0.08, 1);
    }
}
