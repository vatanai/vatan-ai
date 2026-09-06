<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Services\Providers\FalImageProvider;
use App\Services\Providers\ReplicateImageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class QueuedImageProviderInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_replicate_gpt_image_two_receives_enum_quality_values(): void
    {
        $model = new AiModel([
            'provider' => 'replicate',
            'external_model_id' => 'openai/gpt-image-2',
            'openrouter_model_id' => 'openai/gpt-image-2',
            'input_schema' => [
                'properties' => [
                    'prompt' => ['type' => 'string'],
                    'quality' => ['type' => 'string'],
                    'aspect_ratio' => ['type' => 'string'],
                ],
            ],
            'capability_config' => [
                'allowed_inputs' => ['prompt', 'quality', 'aspect_ratio'],
            ],
        ]);

        $provider = app(ReplicateImageProvider::class);
        $buildInput = new ReflectionMethod($provider, 'buildInput');
        $buildInput->setAccessible(true);

        $low = $buildInput->invoke($provider, $model, 'test', '480', '1:1', 1, []);
        $medium = $buildInput->invoke($provider, $model, 'test', '1080', '1:1', 1, []);
        $high = $buildInput->invoke($provider, $model, 'test', '2160', '1:1', 1, []);

        $this->assertSame('low', $low['quality']);
        $this->assertSame('medium', $medium['quality']);
        $this->assertSame('high', $high['quality']);
    }

    public function test_fal_uses_provider_returned_queue_urls_for_flux_variants(): void
    {
        config(['services.fal.api_key' => 'test-fal-key']);

        $model = AiModel::forceCreate([
            'name' => 'FLUX.1 [schnell]',
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/flux/schnell',
            'openrouter_model_id' => 'fal-ai/flux/schnell',
            'is_active' => true,
        ]);

        $requestId = 'fal-request-1';
        $statusUrl = 'https://queue.fal.run/fal-ai/flux/requests/' . $requestId . '/status';
        $responseUrl = 'https://queue.fal.run/fal-ai/flux/requests/' . $requestId;
        AiProviderRequest::create([
            'provider' => 'fal',
            'ai_model_id' => $model->id,
            'external_request_id' => $requestId,
            'status' => 'queued',
            'raw_response' => [
                'status_url' => $statusUrl,
                'response_url' => $responseUrl,
                'cancel_url' => 'https://queue.fal.run/fal-ai/flux/requests/' . $requestId . '/cancel',
            ],
        ]);

        Http::fake([
            $statusUrl => Http::response([
                'status' => 'COMPLETED',
                'request_id' => $requestId,
                'response_url' => $responseUrl,
            ]),
            $responseUrl => Http::response([
                'images' => [['url' => 'https://fal.media/files/result.png']],
            ]),
        ]);

        $provider = app(FalImageProvider::class);
        $pollRemote = new ReflectionMethod($provider, 'pollRemote');
        $pollRemote->setAccessible(true);
        $result = $pollRemote->invoke($provider, $model, $requestId);

        $this->assertSame('COMPLETED', $result['status']);
        $this->assertSame('https://fal.media/files/result.png', $result['result']['images'][0]['url']);
        Http::assertSent(fn (Request $sent) => $sent->url() === $statusUrl);
        Http::assertSent(fn (Request $sent) => $sent->url() === $responseUrl);
        Http::assertNotSent(fn (Request $sent) => str_contains($sent->url(), '/flux/schnell/requests/'));
    }

    public function test_fal_normalizes_image_output_nested_inside_data(): void
    {
        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
            'cost_per_generation_usd' => 0.08,
        ]);

        $normalized = app(FalImageProvider::class)->normalizeResponse($model, [
            'status' => 'COMPLETED',
            'result' => [
                'data' => [
                    'images' => [['url' => 'https://fal.media/files/nested-result.png']],
                ],
            ],
        ]);

        $this->assertSame('completed', $normalized['status']);
        $this->assertSame('https://fal.media/files/nested-result.png', $normalized['output_urls'][0]['url']);
    }

    public function test_fal_rejects_completed_response_without_an_image(): void
    {
        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
            'cost_per_generation_usd' => 0.08,
        ]);

        $normalized = app(FalImageProvider::class)->normalizeResponse($model, [
            'status' => 'COMPLETED',
            'result' => ['data' => ['description' => 'done']],
        ]);

        $this->assertSame('failed', $normalized['status']);
        $this->assertSame('provider_output_missing', $normalized['error_code']);
        $this->assertStringContainsString('هیچ فایل تصویری', $normalized['error_message']);
    }

    public function test_fal_does_not_lookup_or_record_cost_for_completed_response_without_output(): void
    {
        config(['services.fal.api_key' => 'test-fal-key']);
        Http::fake(['https://api.fal.test/*' => Http::response([
            'billing_events' => [['request_id' => 'missing-output', 'cost_total' => 1]],
        ])]);
        config(['services.fal.platform_base_url' => 'https://api.fal.test']);

        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
        ]);
        $normalized = app(FalImageProvider::class)->normalizeResponse($model, [
            'status' => 'COMPLETED',
            'request_id' => 'missing-output',
            'result' => [],
        ]);

        $this->assertSame('failed', $normalized['status']);
        $this->assertNull($normalized['actual_cost_usd']);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/billing-events'));
    }

    public function test_fal_result_endpoint_failure_is_not_silently_treated_as_completed(): void
    {
        config(['services.fal.api_key' => 'test-fal-key']);

        $model = AiModel::forceCreate([
            'name' => 'Fal result failure',
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
            'is_active' => true,
        ]);
        $requestId = 'fal-result-failure';
        $statusUrl = 'https://queue.fal.run/model/requests/' . $requestId . '/status';
        $responseUrl = 'https://queue.fal.run/model/requests/' . $requestId;
        AiProviderRequest::create([
            'provider' => 'fal',
            'ai_model_id' => $model->id,
            'external_request_id' => $requestId,
            'status' => 'queued',
            'raw_response' => ['status_url' => $statusUrl, 'response_url' => $responseUrl],
        ]);
        Http::fake([
            $statusUrl => Http::response(['status' => 'COMPLETED', 'response_url' => $responseUrl]),
            $responseUrl => Http::response(['message' => 'temporary failure'], 503),
        ]);

        $pollRemote = new ReflectionMethod(app(FalImageProvider::class), 'pollRemote');
        $pollRemote->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fal.ai result HTTP 503');
        $pollRemote->invoke(app(FalImageProvider::class), $model, $requestId);
    }

    public function test_fal_paid_submit_is_never_automatically_repeated(): void
    {
        config(['services.fal.api_key' => 'test-fal-key']);
        Http::fake([ '*' => Http::response(['message' => 'failure'], 500) ]);
        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
        ]);
        $submitRemote = new ReflectionMethod(app(FalImageProvider::class), 'submitRemote');
        $submitRemote->setAccessible(true);

        try {
            $submitRemote->invoke(app(FalImageProvider::class), $model, ['prompt' => 'test'], null);
            $this->fail('Fal submit should fail for an HTTP 500 response.');
        } catch (\ReflectionException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $this->assertStringContainsString('Fal.ai HTTP 500', $error->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_fal_sets_generation_guard_when_endpoint_schema_supports_it(): void
    {
        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/nano-banana-2/edit',
            'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
            'input_schema' => ['properties' => [
                'prompt' => ['type' => 'string'],
                'limit_generations' => ['type' => 'boolean'],
            ]],
            'capability_config' => ['allowed_inputs' => ['prompt']],
        ]);
        $buildInput = new ReflectionMethod(app(FalImageProvider::class), 'buildInput');
        $buildInput->setAccessible(true);

        $input = $buildInput->invoke(app(FalImageProvider::class), $model, 'test', '1K', '1:1', 1, []);

        $this->assertTrue($input['limit_generations']);
    }
}
