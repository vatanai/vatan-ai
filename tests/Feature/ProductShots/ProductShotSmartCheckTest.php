<?php

namespace Tests\Feature\ProductShots;

use App\Services\OpenRouterService;
use App\Services\ProductShots\ProductShotFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * صفحه‌ی ساخت جدید (/app/crepro): آپلود بدون هوش مصنوعی (ai=0)
 * و «بررسی هوشمند تصویر ورودی» فقط با یک درخواست برای همه‌ی عکس‌ها (smart=1).
 */
class ProductShotSmartCheckTest extends TestCase
{
    use RefreshDatabase;
    use ProductShotTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Bus::fake();
        ProductShotFeature::resetSchemaCache();
    }

    private function countVisionCalls(array $smartAnswer): \stdClass
    {
        $counter = new \stdClass();
        $counter->calls = 0;
        $counter->instructions = [];
        $mock = Mockery::mock(OpenRouterService::class);
        $mock->shouldReceive('analyzeImagesJson')->andReturnUsing(function (string $model, string $instruction, array $images) use ($counter, $smartAnswer) {
            $counter->calls++;
            $counter->instructions[] = $instruction;
            $counter->images = count($images);

            return ['content' => $smartAnswer, 'usage' => ['prompt_tokens' => 1200, 'completion_tokens' => 60], 'model' => $model];
        });
        $this->app->instance(OpenRouterService::class, $mock);

        return $counter;
    }

    public function test_upload_with_ai_off_does_not_call_vision_model(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $counter = $this->countVisionCalls([]);
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(1);

        $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => $this->productImage('front.jpg', 1000, 1200),
            'role' => 'main',
            'ai' => '0',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('checked_by', 'local');

        $this->assertSame(0, $counter->calls);
    }

    public function test_smart_check_uses_exactly_one_vision_call_for_all_photos(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $counter = $this->countVisionCalls([
            'usable' => true,
            'same_product' => true,
            'issues' => ['duplicate_angle', 'not_a_real_code'],
            'angle_coverage' => ['front', 'side', 'nonsense'],
            'product_description' => 'amber perfume bottle',
            'summary_fa' => 'عکس‌ها خوب‌اند، فقط زاویه‌ی پشت کم است.',
            'suggestion_fa' => 'یک عکس از پشت شیشه اضافه کن.',
        ]);
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(1);

        $uploads = [];
        foreach (range(1, 3) as $index) {
            $uploads[] = ['id' => $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
                'image' => $this->productImage("angle-{$index}.jpg", 1000 + $index, 1200 + $index),
                'role' => $index === 1 ? 'main' : 'angle',
                'ai' => '0',
            ], ['Accept' => 'application/json'])->assertOk()->json('upload_id')];
        }
        $this->assertSame(0, $counter->calls);

        $this->actingAs($user)->postJson(route('app.product-shots.preflight-set', $product->slug), ['uploads' => $uploads, 'smart' => true])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('verdict', 'yellow')
            ->assertJsonPath('usable', true)
            ->assertJsonPath('checked_by', 'vision')
            ->assertJsonPath('summary', 'عکس‌ها خوب‌اند، فقط زاویه‌ی پشت کم است.')
            ->assertJsonPath('suggestion', 'یک عکس از پشت شیشه اضافه کن.')
            ->assertJsonPath('coverage', ['front', 'side'])
            ->assertJsonMissingPath('product_sheet_url');

        $this->assertSame(1, $counter->calls);
        $this->assertSame(3, $counter->images);
    }

    public function test_smart_check_blocks_different_products(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $this->countVisionCalls(['usable' => true, 'same_product' => false, 'issues' => [], 'summary_fa' => '', 'suggestion_fa' => '']);
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(1);

        $uploads = [];
        foreach (range(1, 2) as $index) {
            $uploads[] = ['id' => $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
                'image' => $this->productImage("p-{$index}.jpg", 1000, 1200),
                'ai' => '0',
            ], ['Accept' => 'application/json'])->json('upload_id')];
        }

        $response = $this->actingAs($user)->postJson(route('app.product-shots.preflight-set', $product->slug), ['uploads' => $uploads, 'smart' => true])
            ->assertOk()
            ->assertJsonPath('verdict', 'red')
            ->assertJsonPath('usable', false);
        $this->assertNotSame('', $response->json('summary'));
    }

    public function test_old_set_check_without_smart_flag_keeps_product_sheet(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $this->mockVision();
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(1);
        $id = $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => $this->productImage('serum.jpg', 1000, 1200),
        ], ['Accept' => 'application/json'])->json('upload_id');

        $this->actingAs($user)->postJson(route('app.product-shots.preflight-set', $product->slug), ['uploads' => [['id' => $id]]])
            ->assertOk()
            ->assertJsonStructure(['product_sheet_url', 'coverage', 'issues']);
    }

    public function test_link_images_reads_product_page_and_blocks_internal_addresses(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(1);
        \Illuminate\Support\Facades\Http::fake([
            'example.com/*' => \Illuminate\Support\Facades\Http::response(
                '<html><head><title>Serum</title><meta property="og:image" content="/img/serum-front.jpg"></head>'
                . '<body><img src="/img/logo.svg"><img class="product-gallery" src="https://cdn.example.com/serum-side.jpg?width=100"></body></html>',
                200,
                ['Content-Type' => 'text/html; charset=utf-8'],
            ),
        ]);

        $this->actingAs($user)->postJson(route('app.product-shots.link-images', $product->slug), ['url' => 'https://example.com/p/serum'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('images.0', 'https://example.com/img/serum-front.jpg')
            ->assertJsonPath('images.1', 'https://cdn.example.com/serum-side.jpg?width=1200')
            ->assertJsonCount(2, 'images');

        $this->actingAs($user)->postJson(route('app.product-shots.link-images', $product->slug), ['url' => 'http://127.0.0.1/admin'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }
}
