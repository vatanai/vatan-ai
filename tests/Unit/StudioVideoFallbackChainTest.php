<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\VideoGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class StudioVideoFallbackChainTest extends TestCase
{
    use RefreshDatabase;

    private function videoModel(string $id, string $provider = 'openrouter', array $capabilities = [], float $cost = 0.1): AiModel
    {
        return AiModel::query()->create([
            'name' => $id,
            'provider' => $provider,
            'openrouter_model_id' => $id,
            'external_model_id' => $id,
            'output_modality' => 'video',
            'task_type' => 'image_to_video',
            'is_active' => true,
            'cost_per_generation_usd' => $cost,
            'capability_config' => $capabilities + [
                'supports_text_to_video' => true,
                'supports_image_to_video' => true,
                'supported_durations' => [4, 5, 6, 8],
                'supported_resolutions' => ['720p'],
                'supported_aspect_ratios' => ['16:9', '9:16'],
            ],
        ]);
    }

    private function invokePrivate(string $method, ...$args)
    {
        $service = app(VideoGenerationService::class);
        $reflection = new ReflectionMethod($service, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($service, ...$args);
    }

    private function pngDataUri(): string
    {
        $image = imagecreatetruecolor(40, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 30));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function test_data_uri_inputs_are_materialized_as_public_jpeg_files(): void
    {
        Storage::fake('public');

        $prepared = $this->invokePrivate('prepareProviderImages', [
            'source_image_data_list' => [$this->pngDataUri()],
        ]);

        $this->assertCount(1, $prepared['refs']);
        $this->assertSame($prepared['refs'], $prepared['created_paths']);
        $this->assertStringEndsWith('.jpg', $prepared['refs'][0]);
        Storage::disk('public')->assertExists($prepared['refs'][0]);
        $this->assertSame('image/jpeg', getimagesizefromstring(Storage::disk('public')->get($prepared['refs'][0]))['mime']);
    }

    public function test_chain_has_selected_two_similar_openrouter_models_and_fal_last(): void
    {
        config(['services.openrouter.api_key' => 'test-key', 'services.fal.api_key' => 'fal-key']);
        $primary = $this->videoModel('kwaivgi/kling-v3.0-pro', cost: 0.14);
        $this->videoModel('kwaivgi/kling-v3.0-std', cost: 0.11);
        $this->videoModel('google/veo-3.1-lite', cost: 0.05);
        $this->videoModel('openai/sora-2-pro', cost: 0.5);
        $this->videoModel('heygen/avatar-iv', cost: 0.14); // خارج از فهرست معروف
        $this->videoModel('bytedance/seedance-2.0/fast/image-to-video', 'fal');

        $chain = $this->invokePrivate('buildModelChain', new Product(), $primary, [
            'workflow' => 'image_to_video',
            'has_images' => true,
            'image_count' => 1,
            'has_video' => false,
            'duration' => 5,
            'resolution' => '720p',
            'aspect_ratio' => '16:9',
        ]);

        $this->assertSame([
            ['provider' => 'openrouter', 'model' => 'kwaivgi/kling-v3.0-pro'],
            ['provider' => 'openrouter', 'model' => 'kwaivgi/kling-v3.0-std'],
            ['provider' => 'openrouter', 'model' => 'google/veo-3.1-lite'],
            ['provider' => 'fal', 'model' => 'bytedance/seedance-2.0/fast/image-to-video'],
        ], $chain);
    }

    public function test_rejected_model_moves_to_next_model_and_keeps_inputs_until_finished(): void
    {
        Storage::fake('public');
        config(['services.openrouter.api_key' => 'test-key', 'services.openrouter.base_url' => 'https://openrouter.ai/api/v1', 'queue.default' => 'sync']);
        $this->videoModel('kwaivgi/kling-v3.0-pro');
        $this->videoModel('google/veo-3.1-lite', capabilities: ['supported_frame_images' => ['first_frame', 'last_frame'], 'supported_durations' => [4, 6, 8], 'supports_audio' => true]);
        Storage::disk('public')->put('uploads/video-inputs/provider/in.jpg', 'jpeg-bytes');

        $sent = [];
        Http::fake(function (HttpRequest $request) use (&$sent) {
            $sent[] = $request->data();
            return ($request->data()['model'] ?? '') === 'kwaivgi/kling-v3.0-pro'
                ? Http::response(['error' => ['code' => 400, 'message' => 'frame_images not supported']], 400)
                : Http::response(['id' => 'job-2', 'status' => 'pending'], 200);
        });

        $user = User::factory()->create();
        $product = Product::query()->forceCreate([
            'name_fa' => 'ویدیو', 'name_en' => 'video', 'slug' => 'video-test', 'category' => 'video', 'status' => 'active', 'output_type' => 'video', 'media_type' => 'video', 'thumbnail' => 't.jpg', 'prompt_template' => '{prompt}',
            'primary_model' => 'kwaivgi/kling-v3.0-pro', 'ai_provider' => 'openrouter',
        ]);
        $order = Order::query()->forceCreate([
            'user_id' => $user->id, 'product_id' => $product->id, 'status' => 'processing',
            'payment_status' => 'paid', 'processing_status' => 'queued',
        ]);
        $generation = GeneratedVideo::query()->forceCreate([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'status' => 'submitting', 'user_prompt' => 'A bottle on a table', 'duration_seconds' => 5,
            'input_payload' => [
                'aspect_ratio' => '16:9', 'resolution' => '720p', 'workflow' => 'image_to_video',
                'provider_image_refs' => ['uploads/video-inputs/provider/in.jpg'],
                'temporary_upload_paths' => ['uploads/video-inputs/provider/in.jpg'],
                'model_chain' => [
                    ['provider' => 'openrouter', 'model' => 'kwaivgi/kling-v3.0-pro'],
                    ['provider' => 'openrouter', 'model' => 'google/veo-3.1-lite'],
                ],
            ],
        ]);

        app(VideoGenerationService::class)->submitQueued($generation->id);

        $generation->refresh();
        $this->assertSame('queued', $generation->status);
        $this->assertSame('job-2', $generation->external_request_id);
        $this->assertSame('google/veo-3.1-lite', $generation->input_payload['active_model']);
        $this->assertStringContainsString('frame_images not supported', $generation->input_payload['attempt_errors'][0]['message']);
        Storage::disk('public')->assertExists('uploads/video-inputs/provider/in.jpg');

        $veo = collect($sent)->firstWhere('model', 'google/veo-3.1-lite');
        $this->assertSame(6, $veo['duration']); // ۵ ثانیه پشتیبانی نمی‌شود → نزدیک‌ترین بالاتر
        $this->assertSame('first_frame', $veo['frame_images'][0]['frame_type']);
        $this->assertFalse($veo['generate_audio'] ?? false);
        $this->assertTrue($order->events()->where('type', 'provider_attempt_failed')->exists());
    }

    public function test_safety_rejection_does_not_cascade(): void
    {
        $service = app(VideoGenerationService::class);

        $this->assertFalse($service->shouldTryNextModel('InputSensitiveContentDetected: real person'));
        $this->assertTrue($service->shouldTryNextModel('OpenRouter HTTP 400: duration not supported'));
    }
}
