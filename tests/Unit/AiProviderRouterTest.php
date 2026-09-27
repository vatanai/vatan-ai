<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\AiProviderRouter;
use App\Services\OpenRouterService;
use App\Services\Providers\FalImageProvider;
use App\Support\ProviderStatus;
use Mockery;
use Tests\TestCase;

class AiProviderRouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        ProviderStatus::setEnabled('openrouter', true);
    }

    public function test_product_explicitly_assigned_to_openrouter_uses_openrouter_even_for_shared_model_id(): void
    {
        $product = new Product([
            'primary_model' => 'openai/gpt-image-1-mini',
            'ai_provider' => 'openrouter',
        ]);

        $openRouter = Mockery::mock(OpenRouterService::class);
        $openRouter->shouldReceive('generateForProduct')->once()->andReturn(['provider' => 'openrouter']);

        $result = (new AiProviderRouter($openRouter))
            ->generateForProduct($product, 'test', '1K', '1:1');

        $this->assertSame('openrouter', $result['provider']);
    }

    public function test_repeated_failures_from_one_provider_are_not_retried_for_every_fallback_model(): void
    {
        ProviderStatus::setEnabled('replicate', true);
        ProviderStatus::setEnabled('openrouter', false);

        $product = new Product([
            'primary_model' => 'google/nano-banana-2-lite',
            'ai_provider' => 'replicate',
            'fallback_models' => ['google/nano-banana'],
            'fallback_model_providers' => ['replicate'],
        ]);

        $replicate = Mockery::mock(\App\Services\Providers\ReplicateImageProvider::class);
        $replicate->shouldReceive('generateForProduct')->once()->andThrow(new \RuntimeException('Replicate HTTP 402: Insufficient credit'));
        $openRouter = Mockery::mock(OpenRouterService::class);
        $openRouter->shouldNotReceive('generateForProduct');

        $this->expectException(\RuntimeException::class);
        (new AiProviderRouter($openRouter, null, $replicate))
            ->generateForProduct($product, 'test', '1K', '1:1');
    }

    public function test_slow_primary_model_is_capped_so_configured_fallback_can_run(): void
    {
        config([
            'services.openrouter.timeout' => 300,
            'services.openrouter.image_attempt_timeout' => 90,
            'services.openrouter.image_request_budget' => 240,
        ]);

        $product = new Product([
            'primary_model' => 'google/gemini-3.1-flash-lite-image',
            'ai_provider' => 'openrouter',
            'fallback_models' => ['openai/gpt-image-1-mini'],
            'fallback_model_providers' => ['openrouter'],
            'timeout' => 300,
        ]);

        $openRouter = Mockery::mock(OpenRouterService::class);
        $openRouter->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->withArgs(fn (Product $candidate): bool =>
                $candidate->primary_model === 'google/gemini-3.1-flash-lite-image'
                && (int) $candidate->timeout === 90
            )
            ->andThrow(new \RuntimeException('Connection timed out'));
        $openRouter->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->withArgs(fn (Product $candidate): bool =>
                $candidate->primary_model === 'openai/gpt-image-1-mini'
                && (int) $candidate->timeout === 90
            )
            ->andReturn(['model' => 'openai/gpt-image-1-mini', 'data' => []]);

        $result = (new AiProviderRouter($openRouter))
            ->generateForProduct($product, 'test', '1K', '1:1');

        $this->assertSame('openai/gpt-image-1-mini', $result['model']);
    }

    public function test_configured_fal_fallback_runs_after_openrouter_primary_fails(): void
    {
        ProviderStatus::setEnabled('fal', true);

        $product = new Product([
            'primary_model' => 'google/gemini-3.1-flash-lite-image',
            'ai_provider' => 'openrouter',
            'fallback_models' => ['fal-ai/nano-banana/edit'],
            'fallback_model_providers' => ['fal'],
            'timeout' => 90,
        ]);
        $product->strict_model_priority = true;

        $openRouter = Mockery::mock(OpenRouterService::class);
        $openRouter->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->andThrow(new \RuntimeException('OpenRouter timed out'));

        $fal = Mockery::mock(FalImageProvider::class);
        $fal->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->withArgs(fn (Product $candidate): bool =>
                $candidate->primary_model === 'fal-ai/nano-banana/edit'
                && $candidate->ai_provider === 'fal'
            )
            ->andReturn(['model' => 'fal-ai/nano-banana/edit', 'data' => []]);

        $result = (new AiProviderRouter($openRouter, $fal))
            ->generateForProduct($product, 'test', '1K', '1:1');

        $this->assertSame('fal-ai/nano-banana/edit', $result['model']);
    }

    public function test_openrouter_primary_is_retried_after_strict_fallback_is_exhausted(): void
    {
        ProviderStatus::setEnabled('fal', true);

        $product = new Product([
            'primary_model' => 'google/gemini-3.1-flash-lite-image',
            'ai_provider' => 'openrouter',
            'fallback_models' => ['fal-ai/nano-banana/edit'],
            'fallback_model_providers' => ['fal'],
            'timeout' => 90,
        ]);
        $product->strict_model_priority = true;

        $openRouter = Mockery::mock(OpenRouterService::class);
        $openRouter->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->withArgs(fn (Product $candidate): bool =>
                $candidate->primary_model === 'google/gemini-3.1-flash-lite-image'
                && $candidate->ai_provider === 'openrouter'
            )
            ->andThrow(new \RuntimeException('OpenRouter timed out'));
        $openRouter->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->withArgs(fn (Product $candidate): bool =>
                $candidate->primary_model === 'google/gemini-3.1-flash-lite-image'
                && $candidate->ai_provider === 'openrouter'
                && empty($candidate->fallback_models)
            )
            ->andReturn(['model' => 'google/gemini-3.1-flash-lite-image', 'data' => []]);

        $fal = Mockery::mock(FalImageProvider::class);
        $fal->shouldReceive('generateForProduct')
            ->once()
            ->ordered()
            ->withArgs(fn (Product $candidate): bool =>
                $candidate->primary_model === 'fal-ai/nano-banana/edit'
                && $candidate->ai_provider === 'fal'
            )
            ->andThrow(new \RuntimeException('Fal HTTP 402: Insufficient credit'));

        $result = (new AiProviderRouter($openRouter, $fal))
            ->generateForProduct($product, 'test', '1K', '1:1');

        $this->assertSame('google/gemini-3.1-flash-lite-image', $result['model']);
    }
}
