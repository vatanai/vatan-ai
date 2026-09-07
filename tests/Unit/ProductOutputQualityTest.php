<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Plan;
use App\Models\User;
use App\Services\ProductBuildSchema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductOutputQualityTest extends TestCase
{
    public function test_output_resolution_is_plan_based_and_not_selectable_on_public_build_page(): void
    {
        $product = $this->product([]);

        $this->assertSame(['480', '720', '1080'], $product->allowedResolutionList());
        $this->assertSame('720', $product->defaultOutputResolution());

        $pageData = app(ProductBuildSchema::class)->pageData($product);

        $this->assertSame(['480', '720', '1080'], $pageData['output_resolutions']);
        $this->assertSame('720', $pageData['default_output_resolution']);

        $html = view('app.partials.create-samples-workspace', [
            'product' => $pageData,
            'previewMode' => false,
            'instance' => 'redesign',
        ])->render();

        $this->assertStringContainsString('data-sample-only="0"', $html);
        $this->assertStringContainsString('name="output[aspect_ratio]" value="3:4"', $html);
        $this->assertStringContainsString('data-ratio-label="۳:۴ عمودی"', $html);
        $this->assertStringContainsString('data-default-output-aspect-ratio="3:4"', $html);
        $this->assertStringNotContainsString('name="output[quality]"', $html);
        $this->assertStringContainsString('data-default-output-quality="720"', $html);
        $this->assertTrue($pageData['show_output_quality_selector']);
        $this->assertStringContainsString('data-credit-cost="12"', $html);
        $this->assertStringContainsString('name="output[main_quality]" value="standard"', $html);
    }

    public function test_customer_can_select_1080_but_unsupported_quality_is_rejected(): void
    {
        $rules = app(ProductBuildSchema::class)->rules($this->product());

        foreach (Product::supportedOutputResolutions() as $resolution) {
            $product = $this->product(['allowed_resolutions' => [$resolution], 'resolution' => $resolution]);
            $accepted = Validator::make(
                ['output' => ['quality' => $resolution]],
                app(ProductBuildSchema::class)->rules($product)
            );
            $this->assertFalse($accepted->fails(), "کیفیت {$resolution} باید معتبر باشد.");
        }

        $rejected = Validator::make(['output' => ['quality' => '2160']], $rules);

        $this->assertTrue($rejected->fails());
    }

    public function test_every_product_keeps_the_three_base_qualities_and_can_add_higher_ones(): void
    {
        $product = $this->product(['allowed_resolutions' => ['1440'], 'resolution' => '1440']);
        $pageData = app(ProductBuildSchema::class)->pageData($product);
        $html = view('app.partials.create-samples-workspace', [
            'product' => $pageData,
            'previewMode' => false,
            'instance' => 'redesign',
        ])->render();

        $this->assertSame(['720', '1080', '1440'], $pageData['output_resolutions']);
        $this->assertSame('720', $pageData['default_output_resolution']);
        $this->assertStringNotContainsString('name="output[quality]"', $html);
    }

    public function test_product_name_and_cta_fields_are_not_rendered_or_required_for_generation(): void
    {
        $product = $this->product([
            'input_schema' => [
                ['field_id' => 'product_name', 'type' => 'text', 'label_fa' => 'Product Name', 'required' => true],
                ['field_id' => 'cta_text', 'type' => 'text', 'label_fa' => 'CTA Text', 'required' => false],
                ['field_id' => 'style', 'type' => 'select', 'label_fa' => 'سبک', 'required' => false, 'options' => ['مدرن']],
            ],
        ]);

        $schema = app(ProductBuildSchema::class);

        $this->assertSame(['style'], array_column($schema->fields($product), 'id'));
        $this->assertSame(['style'], array_column($schema->promptFields($product), 'id'));
        $this->assertFalse(Validator::make(['fields' => []], $schema->rules($product))->fails());
    }

    public function test_free_user_starts_with_standard_quality_when_the_selector_is_enabled(): void
    {
        $product = $this->product(['output_quality_selector_enabled' => true]);
        $pageData = app(ProductBuildSchema::class)->pageData($product);
        $html = view('app.partials.create-samples-workspace', [
            'product' => $pageData,
            'previewMode' => false,
            'instance' => 'redesign',
        ])->render();

        $this->assertTrue($pageData['show_output_quality_selector']);
        $this->assertStringContainsString('کیفیت خروجی مدل', $html);
        $this->assertStringContainsString('name="output[main_quality]" value="standard"', $html);
        $this->assertStringContainsString('value="professional"', $html);
        $this->assertStringContainsString('value="best"', $html);
        $this->assertStringContainsString('نیازمند پلن اعتباری', $html);
    }

    public function test_paid_user_sees_the_quality_selector_when_the_product_enabled_it(): void
    {
        $plan = new Plan(['billing_type' => 'one_time', 'price' => 970000]);
        $user = new User();
        $user->setRelation('plan', $plan);
        auth()->setUser($user);

        try {
            $pageData = app(ProductBuildSchema::class)->pageData($this->product(['output_quality_selector_enabled' => true]));
            $html = view('app.partials.create-samples-workspace', [
                'product' => $pageData,
                'previewMode' => false,
                'instance' => 'redesign',
            ])->render();

            $this->assertTrue($pageData['show_output_quality_selector']);
            $this->assertStringContainsString('کیفیت خروجی مدل', $html);
            $this->assertSame(1, preg_match_all('/name="output\[main_quality\]"[^>]*value="standard"[^>]*checked/', $html));
        } finally {
            auth()->forgetUser();
        }
    }

    public function test_free_and_paid_users_receive_product_configured_resolution_defaults(): void
    {
        $product = $this->product([
            'model_configuration' => [
                'output_resolution_defaults' => ['free' => '720', 'paid' => '1080'],
            ],
        ]);

        $this->assertSame('720', $product->defaultOutputResolutionForUser(null));

        $plan = new Plan(['billing_type' => 'one_time', 'price' => 970000]);
        $user = new User();
        $user->setRelation('plan', $plan);

        $this->assertSame('1080', $product->defaultOutputResolutionForUser($user));
    }

    public function test_admin_can_customize_plan_resolution_defaults_without_migration(): void
    {
        $product = $this->product([
            'model_configuration' => [
                'output_resolution_defaults' => ['free' => '480', 'paid' => '2160'],
            ],
            'allowed_resolutions' => ['480', '2160'],
        ]);

        $this->assertSame(['free' => '480', 'paid' => '2160'], $product->outputResolutionDefaults());
        $this->assertSame('480', $product->defaultOutputResolutionForUser(null));

        $plan = new Plan(['billing_type' => 'one_time', 'price' => 970000]);
        $user = new User();
        $user->setRelation('plan', $plan);
        $this->assertSame('2160', $product->defaultOutputResolutionForUser($user));
    }

    private function product(array $overrides = ['allowed_resolutions' => ['480', '720', '1080']]): Product
    {
        $product = new Product(array_merge([
            'name_fa' => 'محصول تست کیفیت',
            'slug' => 'output-quality-test',
            'product_code' => '999998',
            'status' => 'active',
            'credit_cost' => 1,
            'estimated_time' => 30,
            'input_schema' => [],
            'allowed_aspect_ratios' => ['3:4'],
            'allowed_resolutions' => null,
            'resolution' => '720',
        ], $overrides));
        $product->id = 999998;

        return $product;
    }
}
