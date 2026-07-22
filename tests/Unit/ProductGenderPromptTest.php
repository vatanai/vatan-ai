<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductPromptBuilder;
use Tests\TestCase;

class ProductGenderPromptTest extends TestCase
{
    public function test_selected_gender_option_prompt_is_appended_to_final_prompt(): void
    {
        $product = new Product([
            'prompt_template' => 'Create a studio portrait.',
            'input_schema' => [[
                'field_id' => 'face_gender', 'type' => 'gender', 'label_fa' => 'جنسیت چهره',
                'required' => '1', 'prompt_mode' => 'append',
                'options' => [
                    ['value' => 'male', 'label' => 'مرد', 'prompt' => 'male portrait prompt'],
                    ['value' => 'female', 'label' => 'زن', 'prompt' => 'female portrait prompt'],
                ],
            ]],
        ]);

        $prompt = app(ProductPromptBuilder::class)->build($product, ['face_gender' => 'female']);

        $this->assertStringContainsString('female portrait prompt', $prompt);
        $this->assertStringNotContainsString('male portrait prompt', $prompt);
    }
}
