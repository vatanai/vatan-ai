<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductBuildSchema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductAspectRatioSchemaTest extends TestCase
{
    public function test_aspect_ratio_is_a_real_base_a_schema_field(): void
    {
        $product = new Product([
            'input_schema' => [[
                'field_id' => 'output_aspect_ratio', 'type' => 'aspect_ratio', 'label_fa' => 'نسبت تصویر خروجی',
                'required' => '1', 'options' => [
                    ['value' => '1:1', 'label' => 'مربع'], ['value' => '9:16', 'label' => 'عمودی'],
                    ['value' => '16:9', 'label' => 'افقی'],
                ],
            ]],
        ]);

        $ratioFields = collect(app(ProductBuildSchema::class)->fields($product))->where('type', 'aspect_ratio')->values();

        $this->assertCount(1, $ratioFields);
        $this->assertSame('output_aspect_ratio', $ratioFields[0]['id']);
        $this->assertSame(['1:1', '9:16', '16:9'], collect($ratioFields[0]['options'])->pluck('value')->all());
    }

    public function test_unconfigured_ratio_is_rejected(): void
    {
        $product = new Product(['input_schema' => [[
            'field_id' => 'ratio', 'type' => 'aspect_ratio', 'label_fa' => 'نسبت', 'required' => '1',
            'options' => [['value' => '1:1', 'label' => 'مربع'], ['value' => '4:5', 'label' => 'عمودی']],
        ]] ]);
        $validator = Validator::make(
            ['output' => ['aspect_ratio' => '16:9']],
            app(ProductBuildSchema::class)->rules($product)
        );

        $this->assertTrue($validator->fails());
    }
}
