<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductPromptBuilder;
use Tests\TestCase;

class ProductRequiredFieldRenderingTest extends TestCase
{
    public function test_required_number_field_has_native_and_workspace_validation_metadata(): void
    {
        $html = view('app.partials.create-field', [
            'instance' => 'test',
            'field' => [
                'type' => 'number',
                'id' => 'age',
                'label' => 'سن',
                'required' => true,
                'min' => 1,
                'max' => 120,
                'step' => 1,
            ],
        ])->render();

        $this->assertStringContainsString('data-field-required="1"', $html);
        $this->assertStringContainsString('name="fields[age]"', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('min="1"', $html);
        $this->assertStringContainsString('max="120"', $html);
        $this->assertStringContainsString('step="1"', $html);
        $this->assertMatchesRegularExpression('/\srequired(?:\s|>)/', $html);
    }

    public function test_age_value_is_appended_as_an_explicit_number_replacement_instruction(): void
    {
        $product = new Product([
            'prompt_template' => 'Create the birthday portrait with the original composition.',
            'input_schema' => [[
                'type' => 'number',
                'field_id' => 'age',
                'label_fa' => 'سن',
                'required' => true,
                'prompt_mode' => 'append',
                'prompt_wrap' => 'Replace the existing birthday age number shown behind the subject (currently 22) with exactly {value}. The visible 3D age number must be {value}; do not keep 22.',
            ]],
        ]);

        $prompt = app(ProductPromptBuilder::class)->build($product, ['age' => 35]);

        $this->assertStringContainsString('with exactly 35', $prompt);
        $this->assertStringContainsString('must be 35', $prompt);
        $this->assertStringNotContainsString('{value}', $prompt);
    }
}
