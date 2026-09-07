<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\Product;
use App\Services\Providers\FalImageProvider;
use App\Services\Providers\OpenRouterVideoProvider;
use App\Services\VideoModelSchemaService;
use App\Services\VideoProductConfigService;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;

class VideoProductConfigurationTest extends TestCase
{
    public function test_video_schema_resolves_references_and_removes_unsupported_inputs(): void
    {
        $model = new AiModel([
            'task_type' => 'image_to_video',
            'supports_image_input' => true,
            'input_schema' => [
                'paths' => ['/generate' => ['post' => ['requestBody' => ['content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/VideoInput']]]]]]],
                'components' => ['schemas' => ['VideoInput' => ['properties' => [
                    'prompt' => ['type' => 'string'],
                    'image_url' => ['type' => 'string'],
                    'resolution' => ['type' => 'string', 'enum' => ['480p', '720p']],
                ]]]],
            ],
        ]);

        $service = new VideoModelSchemaService();
        $summary = $service->summarize($model);
        $input = $service->sanitizeInput($model, [
            'prompt' => 'A calm portrait',
            'image_url' => 'data:image/png;base64,abc',
            'resolution' => '480p',
            'unsupported' => true,
        ]);

        $this->assertTrue($summary['supports_image']);
        $this->assertSame(['480p', '720p'], $summary['resolutions']);
        $this->assertArrayNotHasKey('unsupported', $input);
        $this->assertSame('480p', $input['resolution']);
    }

    public function test_video_configuration_normalizes_options_and_duration_cost(): void
    {
        $service = new VideoProductConfigService();
        $normalized = $service->normalize([
            'workflow' => 'image_to_video',
            'face_profile_mode' => 'optional',
            'durations' => [8, 4, 4],
            'default_duration' => 8,
            'aspect_ratios' => ['9:16', 'bad'],
            'default_aspect_ratio' => '9:16',
            'resolutions' => ['480p', 'bad'],
            'default_resolution' => '480p',
            'motion_presets' => ['orbit', 'unknown'],
            'credit_costs_by_duration' => ['4' => 6, '8' => 12],
            'allow_promotional_credits' => true,
        ]);
        $product = new Product([
            'credit_cost' => 20,
            'provider_options' => ['video' => $normalized],
        ]);

        $this->assertSame([4, 8], $normalized['durations']);
        $this->assertSame(['9:16'], $normalized['aspect_ratios']);
        $this->assertSame('orbit', $normalized['motion_presets'][0]['key']);
        $this->assertTrue($normalized['allow_promotional_credits']);
        $this->assertSame(12, $service->creditCost($product, 8));
        $this->assertSame(22, $service->creditCost($product, 8, '4K'));
    }

    public function test_fal_normalizes_video_output_without_affecting_image_contract(): void
    {
        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/test-video',
            'openrouter_model_id' => 'fal-ai/test-video',
            'cost_per_generation_usd' => 0.025,
        ]);

        $normalized = app(FalImageProvider::class)->normalizeResponse($model, [
            'status' => 'COMPLETED',
            'result' => ['video' => ['url' => 'https://fal.media/files/result.mp4']],
        ]);

        $this->assertSame('completed', $normalized['status']);
        $this->assertSame('https://fal.media/files/result.mp4', $normalized['output_urls'][0]['url']);
    }

    public function test_fal_reads_queue_links_after_metadata_is_nested(): void
    {
        $request = new AiProviderRequest([
            'raw_response' => ['response' => [
                'status_url' => 'https://queue.fal.run/fal-ai/wan/requests/abc/status',
                'response_url' => 'https://queue.fal.run/fal-ai/wan/requests/abc',
            ]],
        ]);
        $method = new ReflectionMethod(app(FalImageProvider::class), 'remoteRequestPayload');
        $method->setAccessible(true);

        $payload = $method->invoke(app(FalImageProvider::class), $request);

        $this->assertSame('https://queue.fal.run/fal-ai/wan/requests/abc/status', $payload['status_url']);
        $this->assertSame('https://queue.fal.run/fal-ai/wan/requests/abc', $payload['response_url']);
    }

    public function test_openrouter_video_normalizes_async_job_and_content_url(): void
    {
        $model = new AiModel(['provider' => 'openrouter', 'openrouter_model_id' => 'google/veo-3.1']);
        $normalized = app(OpenRouterVideoProvider::class)->normalizeResponse($model, [
            'id' => 'job-123', 'status' => 'completed',
            'unsigned_urls' => ['https://openrouter.ai/api/v1/videos/job-123/content?index=0'],
            'usage' => ['cost' => 0.5],
        ]);
        $this->assertSame('completed', $normalized['status']);
        $this->assertSame('job-123', $normalized['external_request_id']);
        $this->assertSame(0.5, $normalized['actual_cost_usd']);
    }

    public function test_fal_video_uses_public_uploaded_image_url_instead_of_data_uri(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/video-inputs/images/source.jpg', 'fake-image');

        $model = new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'fal-ai/wan/v2.2-a14b/image-to-video/turbo',
            'openrouter_model_id' => 'fal-ai/wan/v2.2-a14b/image-to-video/turbo',
            'input_schema' => ['properties' => [
                'prompt' => ['type' => 'string'],
                'image_url' => ['type' => 'string'],
                'resolution' => ['type' => 'string'],
            ]],
        ]);

        $service = app(\App\Services\VideoGenerationService::class);
        $method = new ReflectionMethod($service, 'buildProviderInput');
        $method->setAccessible(true);
        $input = $method->invoke($service, $model, new Product(['provider_options' => ['video' => []]]), 'A calm shot', [
            'duration' => 1,
            'aspect_ratio' => '16:9',
            'resolution' => '480p',
            'source_image_data_list' => ['data:image/jpeg;base64,ZmFrZQ=='],
            'source_upload_paths' => ['uploads/video-inputs/images/source.jpg'],
        ]);

        $this->assertSame(asset('storage/uploads/video-inputs/images/source.jpg'), $input['image_url']);
        $this->assertStringStartsWith('http', $input['image_url']);
    }

}
