<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\AiProviderRouter;
use App\Services\OpenRouterService;
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
}
