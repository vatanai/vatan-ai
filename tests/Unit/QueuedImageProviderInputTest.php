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
}
