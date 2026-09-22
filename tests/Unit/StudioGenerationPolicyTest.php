<?php

namespace Tests\Unit;

use App\Jobs\ProcessOpenRouterVideoWebhook;
use App\Models\AiModel;
use App\Models\FinanceExchangeRate;
use App\Models\StudioPricingSetting;
use App\Services\Providers\OpenRouterVideoProvider;
use App\Services\StudioCostService;
use App\Services\VideoGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudioGenerationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_actual_provider_cost_is_converted_with_ten_percent_profit(): void
    {
        FinanceExchangeRate::query()->create([
            'currency' => 'USD',
            'rate_date' => now()->toDateString(),
            'rate_to_irr' => 1_000_000,
            'rate_to_toman' => 100_000,
            'source' => 'test',
            'is_manual' => true,
        ]);
        StudioPricingSetting::query()->updateOrCreate(['id' => 1], [
            'image_profit_percent' => 30,
            'video_profit_percent' => 30,
        ]);

        // تسویهٔ واقعی باید مستقل از تنظیم قدیمی فروش و دقیقاً با سود قطعی ۱۰٪ باشد.
        $this->assertSame(55, app(StudioCostService::class)->creditsForActualCost(0.5, 'video'));
    }

    public function test_openrouter_completion_without_unsigned_url_uses_content_endpoint(): void
    {
        config(['services.openrouter.base_url' => 'https://openrouter.test/api/v1']);
        $model = new AiModel(['provider' => 'openrouter', 'openrouter_model_id' => 'google/veo-3.1']);

        $normalized = app(OpenRouterVideoProvider::class)->normalizeResponse($model, [
            'id' => 'job-123',
            'status' => 'completed',
            'usage' => ['cost' => 0.25],
        ]);

        $this->assertSame('https://openrouter.test/api/v1/videos/job-123/content?index=0', $normalized['output_urls'][0]['url']);
        $this->assertSame(0.25, $normalized['actual_cost_usd']);
        $this->assertArrayNotHasKey('headers', $normalized['output_urls'][0]);
    }

    public function test_fallback_policy_retries_only_transient_provider_errors(): void
    {
        $service = app(VideoGenerationService::class);

        $this->assertTrue($service->isRetryableProviderError('OpenRouter HTTP 429: rate limit'));
        $this->assertTrue($service->isRetryableProviderError('OpenRouter HTTP 503: unavailable'));
        $this->assertFalse($service->isRetryableProviderError('OpenRouter HTTP 422: unsupported input'));
        $this->assertFalse($service->isRetryableProviderError('InputSensitiveContentDetected'));
    }

    public function test_openrouter_webhook_signature_and_delivery_key_are_accepted(): void
    {
        Queue::fake();
        config(['services.ai.webhook_secret' => 'test-secret']);
        $payload = json_encode([
            'type' => 'video.generation.completed',
            'data' => ['id' => 'job-123', 'status' => 'completed', 'model' => 'google/veo-3.1'],
        ], JSON_UNESCAPED_SLASHES);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp . ',' . $payload, 'test-secret');

        $this->call('POST', route('webhooks.ai.openrouter'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_OPENROUTER_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'HTTP_X_OPENROUTER_IDEMPOTENCY_KEY' => 'job-123-completed',
        ], $payload)->assertAccepted()->assertJson(['received' => true]);

        Queue::assertPushed(ProcessOpenRouterVideoWebhook::class, fn ($job) => $job->deliveryKey === 'job-123-completed');
    }
}
