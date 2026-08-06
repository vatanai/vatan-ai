<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductPromptBuilder;
use Tests\TestCase;

class ProductGenderPromptTest extends TestCase
{
    public function test_gender_field_is_rendered_for_the_customer_with_male_and_female_options(): void
    {
        $product = new Product([
            'name_fa' => 'محصول تست جنسیت',
            'prompt_template' => 'Create a studio portrait.',
            'input_schema' => [[
                'field_id' => 'face_gender', 'type' => 'gender', 'label_fa' => 'جنسیت چهره',
                'required' => '1', 'default' => 'male', 'prompt_mode' => 'append',
                'options' => [
                    ['value' => 'male', 'label' => 'مرد', 'prompt' => 'male portrait prompt'],
                    ['value' => 'female', 'label' => 'زن', 'prompt' => 'female portrait prompt'],
                ],
            ]],
        ]);
        $product->slug = 'gender-render-test';
        $product->product_code = '999999';
        $product->status = 'active';

        $pageData = app(\App\Services\ProductBuildSchema::class)->pageData($product);
        $html = view('app.partials.create-workspace', [
            'product' => $pageData,
            'previewMode' => false,
        ])->render();

        $this->assertStringContainsString('name="fields[face_gender]"', $html);
        $this->assertStringContainsString('value="male"', $html);
        $this->assertStringContainsString('value="female"', $html);
        $this->assertStringContainsString('>مرد<', $html);
        $this->assertStringContainsString('>زن<', $html);
    }

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

        $this->assertStringEndsWith("\n\nfemale portrait prompt", $prompt);
    }
}
