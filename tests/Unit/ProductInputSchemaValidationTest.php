<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class ProductInputSchemaValidationTest extends TestCase
{
    private function rules(): array
    {
        $method = new ReflectionMethod(ProductController::class, 'inputSchemaRules');

        return $method->invoke(app(ProductController::class));
    }

    public function test_complete_schema_builder_payload_is_valid(): void
    {
        $payload = ['input_schema' => [[
            'field_id' => 'image_style',
            'label_fa' => 'سبک تصویر',
            'type' => 'select',
            'required' => '1',
            'hidden' => '0',
            'description' => 'سبک دلخواه را انتخاب کنید',
            'prompt_mode' => 'token',
            'prompt_wrap' => 'in {value} style',
            'options' => [[
                'label' => 'کلاسیک',
                'value' => 'classic',
                'prompt' => 'classic portrait style',
                'credit' => 2,
            ]],
        ]]];

        $this->assertFalse(Validator::make($payload, $this->rules())->fails());
    }

    public function test_duplicate_or_unsafe_field_ids_are_rejected(): void
    {
        $payload = ['input_schema' => [
            ['field_id' => 'bad id', 'label_fa' => 'اول', 'type' => 'text', 'required' => '0'],
            ['field_id' => 'bad id', 'label_fa' => 'دوم', 'type' => 'text', 'required' => '0'],
        ]];

        $this->assertTrue(Validator::make($payload, $this->rules())->fails());
    }
}
