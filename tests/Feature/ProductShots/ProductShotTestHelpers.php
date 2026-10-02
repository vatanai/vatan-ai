<?php

namespace Tests\Feature\ProductShots;

use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ProductShotSetting;
use App\Models\ShotLibrary;
use App\Models\User;
use App\Services\AiProviderRouter;
use App\Services\OpenRouterService;
use App\Services\ProductShots\ProductShotFeature;
use Mockery;

trait ProductShotTestHelpers
{
    protected function pngBase64(): string
    {
        $img = imagecreatetruecolor(64, 80);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 80));
        ob_start();
        imagepng($img);

        return base64_encode((string) ob_get_clean());
    }

    protected function enableShots(string $audience = 'public', array $overrides = []): ProductShotSetting
    {
        ProductShotFeature::resetSchemaCache();
        $settings = ProductShotSetting::query()->firstOrFail();
        $settings->update(array_merge([
            'enabled' => true,
            'audience' => $audience,
            'preflight_enabled' => true,
            'qc_enabled' => true,
            'qc_auto_retry' => true,
            'daily_cost_cap_usd' => 0,
        ], $overrides));
        ProductShotSetting::forgetCache();

        return $settings->fresh();
    }

    protected function makeUser(int $tokens = 100, array $extra = []): User
    {
        return User::query()->forceCreate(array_merge([
            'name' => 'کاربر تست',
            'phone' => '0912' . random_int(1000000, 9999999),
            'tokens' => $tokens,
            'promotional_tokens' => 0,
            'status' => 'active',
        ], $extra));
    }

    protected function makePortraitProduct(array $extra = []): Product
    {
        return Product::query()->forceCreate(array_merge([
            'name_fa' => 'پرتره', 'name_en' => 'Portrait', 'slug' => 'portrait-' . uniqid(),
            'category' => 'عمومی', 'thumbnail' => 'x.jpg', 'primary_model' => 'm/portrait',
            'prompt_template' => 'portrait', 'status' => 'active', 'product_code' => (string) random_int(100000, 999999),
        ], $extra));
    }

    protected function makeShotProduct(int $shotCount = 3, array $extra = []): Product
    {
        $product = Product::query()->forceCreate(array_merge([
            'name_fa' => 'سرم ویتامین سی', 'name_en' => 'Vitamin C serum', 'slug' => 'serum-' . uniqid(),
            'category' => 'آرایشی', 'thumbnail' => 'x.jpg', 'primary_model' => 'google/test-image-edit',
            'ai_provider' => 'openrouter', 'prompt_template' => 'shot pack', 'status' => 'active',
            'product_mode' => 'product', 'product_code' => (string) random_int(100000, 999999),
            'shot_settings' => ['product_description' => 'amber serum bottle'],
        ], $extra));

        ShotLibrary::query()->ordered()->take($shotCount)->get()->each(function (ShotLibrary $shot, int $i) use ($product) {
            ProductShot::create(['product_id' => $product->id, 'shot_id' => $shot->id, 'enabled' => true, 'is_default' => true, 'credits_override' => 10, 'sort' => $i]);
        });

        return $product;
    }

    /** @param array<int, \Throwable|null> $failures به‌ترتیب هر فراخوانی؛ null یعنی موفق */
    protected function mockRouter(array $failures = [], float $cost = 0.03): void
    {
        $b64 = $this->pngBase64();
        $calls = 0;
        $mock = Mockery::mock(AiProviderRouter::class);
        $mock->shouldReceive('generateForProduct')->andReturnUsing(function () use (&$calls, $failures, $b64, $cost) {
            $failure = $failures[$calls++] ?? null;
            if ($failure) {
                throw $failure;
            }

            return ['data' => ['data' => [['b64_json' => $b64]], 'usage' => ['cost' => $cost]], 'model' => 'google/test-image-edit'];
        });
        $this->app->instance(AiProviderRouter::class, $mock);
    }

    /** @param array<int, array> $qcResults پاسخ‌های QC به‌ترتیب؛ پاسخ پیش‌فرض preflight سبز است */
    protected function mockVision(array $qcResults = [], ?array $preflight = null): void
    {
        $qcCalls = 0;
        $mock = Mockery::mock(OpenRouterService::class);
        $mock->shouldReceive('analyzeImagesJson')->andReturnUsing(function (string $model, string $instruction) use (&$qcCalls, $qcResults, $preflight) {
            if (str_contains($instruction, 'quality inspector')) {
                return ['content' => $preflight ?? ['usable' => true, 'issues' => [], 'crop_box' => ['x' => 0.1, 'y' => 0.1, 'w' => 0.8, 'h' => 0.8], 'product_description' => 'amber serum bottle', 'suggestion_fa' => ''], 'usage' => [], 'model' => $model];
            }
            $res = $qcResults[$qcCalls++] ?? ['same_product' => true, 'fidelity_score' => 5, 'issues' => [], 'summary' => 'ok'];

            return ['content' => $res, 'usage' => [], 'model' => $model];
        });
        $this->app->instance(OpenRouterService::class, $mock);
    }
}
