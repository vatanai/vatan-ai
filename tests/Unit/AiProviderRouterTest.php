<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\AiProviderRouter;
use App\Services\OpenRouterService;
use App\Services\Providers\FalImageProvider;
use App\Services\Providers\ReplicateImageProvider;
use App\Support\ProviderStatus;
use Mockery;
use RuntimeException;
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

    public function test_failed_fal_model_does_not_trigger_a_second_paid_fal_fallback(): void
    {
        ProviderStatus::setEnabled('fal', true);
        ProviderStatus::setEnabled('replicate', true);
        $product = new Product([
            'primary_model' => 'fal-ai/nano-banana-2/edit',
            'ai_provider' => 'fal',
            'fallback_models' => ['fal-ai/nano-banana-pro/edit', 'google/nano-banana'],
            'fallback_model_providers' => ['fal', 'replicate'],
        ]);

        $openRouter = Mockery::mock(OpenRouterService::class);
        $openRouter->shouldNotReceive('generateForProduct');
        $fal = Mockery::mock(FalImageProvider::class);
        $fal->shouldReceive('generateForProduct')->once()->andThrow(new RuntimeException('Fal output missing'));
        $replicate = Mockery::mock(ReplicateImageProvider::class);
        $replicate->shouldReceive('generateForProduct')->once()->andReturn(['provider' => 'replicate']);

        $result = (new AiProviderRouter($openRouter, $fal, $replicate))
            ->generateForProduct($product, 'test', '1K', '1:1');

        $this->assertSame('replicate', $result['provider']);
    }
}
