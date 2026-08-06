<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductBuildSchema;
use App\Services\ProductPromptBuilder;
use Tests\TestCase;

class ProductIdentityModeTest extends TestCase
{
    public function test_identity_prompt_is_only_appended_when_user_enables_it(): void
    {
        $product = new Product([
            'prompt_template' => 'Create a studio portrait.',
            'identity_preservation' => true,
            'identity_instructions' => 'KEEP-THIS-IDENTITY',
            'input_schema' => [],
        ]);

        $builder = app(ProductPromptBuilder::class);

        $this->assertStringNotContainsString('KEEP-THIS-IDENTITY', $builder->build($product, [], false));
        $this->assertStringContainsString('KEEP-THIS-IDENTITY', $builder->build($product, [], true));
    }

    public function test_identity_configuration_is_exposed_unchecked_with_three_image_cap(): void
    {
        $product = new Product([
            'identity_preservation' => true,
            'identity_credit_cost' => 8,
            'max_reference_images' => 10,
            'credit_cost' => 12,
            'input_schema' => [],
            'prompt_template' => 'Portrait',
            'status' => 'active',
        ]);
        $product->id = 999;
        $product->slug = 'identity-test';
        $product->product_code = '999999';

        $data = app(ProductBuildSchema::class)->pageData($product);

        $this->assertTrue($data['identity']['available']);
        $this->assertSame(8, $data['identity']['extra_cost']);
        $this->assertSame(3, $data['identity']['max_images']);
    }
}
