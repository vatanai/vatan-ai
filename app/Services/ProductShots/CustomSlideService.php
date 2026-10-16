<?php

namespace App\Services\ProductShots;

use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ShotLibrary;
use Illuminate\Support\Str;

/**
 * اسلاید اختصاصی محصول = عکس نمونه + پرامپت ساخت مخصوص همان محصول.
 *
 * هم فرم «استودیو محصول» داشبورد و هم بات تلگرام از همین سرویس استفاده می‌کنند تا
 * یک محصول همیشه از یک مسیر ذخیره شود. هر اسلاید یک ردیف shot_library با product_id
 * (پنهان از کتابخانه‌ی عمومی) و یک ردیف product_shots دارد؛ پس صفحه‌ی بساز، ساخت،
 * پیش‌نمایش و کنترل کیفیت بدون هیچ تغییری با آن کار می‌کنند.
 */
class CustomSlideService
{
    public const CATEGORY = 'hero';

    /**
     * @param array{
     *   name:string, prompt:string, original_prompt?:?string, sample_path?:?string, enabled?:bool, sort?:int,
     *   credits?:array<string,int>, allowed_aspect_ratios?:array, aspect_ratio_default?:?string,
     *   aspect_ratio_user_selectable?:bool, input_image_slide?:bool
     * } $slide
     */
    public function create(Product $product, array $slide): ProductShot
    {
        $name = Str::limit(trim((string) ($slide['name'] ?? '')), 120, '') ?: 'اسلاید محصول';
        $prompt = trim((string) ($slide['prompt'] ?? ''));
        $original = trim((string) ($slide['original_prompt'] ?? '')) ?: $prompt;
        $levels = array_keys((array) config('product_shots.quality_levels', ['standard' => '']));
        $credits = (array) ($slide['credits'] ?? []);
        $standardCredits = max(0, (int) ($credits['standard'] ?? reset($credits) ?: 12));
        $supported = array_values((array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']));
        $ratios = array_values(array_intersect($supported, (array) ($slide['allowed_aspect_ratios'] ?? $supported))) ?: [$supported[0]];
        $defaultRatio = in_array($slide['aspect_ratio_default'] ?? null, $ratios, true) ? (string) $slide['aspect_ratio_default'] : $ratios[0];
        $sample = trim((string) ($slide['sample_path'] ?? '')) ?: null;

        $library = ShotLibrary::query()->create([
            'product_id' => $product->id,
            'key' => $this->uniqueKey($product),
            'name_fa' => ! empty($slide['input_image_slide']) ? 'عکس ورودی' : $name,
            'name_en' => null,
            'description_fa' => null,
            'category' => self::CATEGORY,
            'niche_tags' => [],
            'tokens' => [],
            // پرامپت اصلی مدیر برای مرجع؛ پرامپت نهایی (اصلاح‌شده) روی product_shots است.
            'prompt_template' => ! empty($slide['input_image_slide']) ? '' : $original,
            'default_credits' => $standardCredits,
            'aspect_ratio_default' => $defaultRatio,
            'sample_image' => $sample,
            'is_active' => true,
            'sort' => 0,
        ]);

        return ProductShot::query()->create([
            'product_id' => $product->id,
            'shot_id' => $library->id,
            'name_override' => null,
            'enabled' => (bool) ($slide['enabled'] ?? true),
            // هیچ اسلایدی از پیش برای کاربر تیک نمی‌خورد.
            'is_default' => false,
            'credits_override' => null,
            'sample_image' => $sample,
            'sort' => (int) ($slide['sort'] ?? 0),
            'prompt_override' => $prompt !== '' ? $prompt : null,
            'model_configuration' => [
                'quality_credits' => collect($levels)->mapWithKeys(fn ($level) => [$level => max(0, (int) ($credits[$level] ?? $standardCredits))])->all(),
                'quality_models' => collect($levels)->mapWithKeys(fn ($level) => [$level => []])->all(),
            ],
            'allowed_aspect_ratios' => $ratios,
            'aspect_ratio_default' => $defaultRatio,
            'aspect_ratio_user_selectable' => (bool) ($slide['aspect_ratio_user_selectable'] ?? true),
            'options_enabled' => ['prompt' => true, 'model' => true, 'ratio' => true, 'credits' => true, 'preview' => true, 'input_image_slide' => (bool) ($slide['input_image_slide'] ?? false)],
        ]);
    }

    /** حذف کامل اسلاید اختصاصی (اتصال + ردیف پنهان کتابخانه). شات عمومی کتابخانه دست نمی‌خورد. */
    public function delete(ProductShot $productShot): void
    {
        $library = $productShot->shot;
        $productShot->delete();
        if ($library && $library->isCustom() && ! $library->productShots()->exists()) {
            $library->delete();
        }
    }

    private function uniqueKey(Product $product): string
    {
        do {
            $key = 'p' . $product->id . '-' . Str::lower(Str::random(10));
        } while (ShotLibrary::query()->where('key', $key)->exists());

        return $key;
    }
}
