<?php

namespace App\Services\ProductShots;

use App\Models\AiModel;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ProductShotSetting;
use App\Models\ShotLibrary;
use App\Support\ProviderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ثبت محصول پروداکتی (پک شات) بدون فرم وب؛ برای بات تلگرام ثبت محصول.
 *
 * خروجی دقیقاً همان ساختاری است که فرم «استودیو محصول» می‌سازد
 * (product_mode=product، shot_settings، صنف‌ها، دسته‌ها و product_shots)
 * تا محصول بعداً بدون هیچ تفاوتی در پنل ادمین ویرایش شود.
 */
class ShotProductRegistrar
{
    /** شات‌های «عمومی» (product-*) برای همه‌ی صنف‌ها معتبرند. */
    public const GENERAL_PREFIX = 'product-';

    /** صنف‌هایی که حداقل یک شغل فعال دارند، به ترتیب Occupation::GROUPS. */
    public function groups(): array
    {
        $active = Occupation::query()->active()->distinct()->pluck('group_key')->map(fn ($key) => (string) $key)->all();

        return collect(Occupation::GROUPS)
            ->filter(fn ($label, $key) => in_array($key, $active, true))
            ->all();
    }

    public function occupationsForGroup(string $group): Collection
    {
        return Occupation::query()->active()->where('group_key', $group)->ordered()->get(['id', 'name_fa', 'group_key']);
    }

    /** گروه‌های صنفی شغل‌های انتخاب‌شده. */
    public function groupsOfOccupations(array $occupationIds): array
    {
        if ($occupationIds === []) {
            return [];
        }

        return Occupation::query()->whereIn('id', $occupationIds)->distinct()->pluck('group_key')->map(fn ($key) => (string) $key)->values()->all();
    }

    /**
     * شات‌های قابل‌انتخاب برای صنف‌ها: اول شات‌های تخصصی همان صنف، بعد شات‌های عمومی.
     *
     * @return Collection<int, ShotLibrary>
     */
    public function shotsForGroups(array $groups): Collection
    {
        $groups = array_values(array_filter(array_map('strval', $groups)));

        return ShotLibrary::query()->library()->active()->ordered()->get()
            ->filter(function (ShotLibrary $shot) use ($groups): bool {
                if (Str::startsWith($shot->key, self::GENERAL_PREFIX)) {
                    return true;
                }

                return array_intersect($groups, (array) $shot->niche_tags) !== [];
            })
            ->sortBy(fn (ShotLibrary $shot) => [Str::startsWith($shot->key, self::GENERAL_PREFIX) ? 1 : 0, $shot->sort, $shot->id])
            ->values();
    }

    /**
     * بسته‌ی آماده‌ی پیشنهادی: پیشنهاد AI (اگر معتبر بود) و بعد تنوع دسته‌ها (هیرو، لایف‌استایل، جزئیات…).
     *
     * @return array<int,int> شناسه‌ی شات‌ها
     */
    public function suggestDefaults(Collection $shots, array $preferredKeys = [], ?int $limit = null): array
    {
        $limit ??= $this->defaultPackSize();
        $chosen = [];
        $byKey = $shots->keyBy('key');
        foreach ($preferredKeys as $key) {
            $shot = $byKey->get((string) $key);
            if ($shot && ! in_array($shot->id, $chosen, true)) {
                $chosen[] = (int) $shot->id;
            }
            if (count($chosen) >= $limit) {
                return $chosen;
            }
        }

        // یک شات از هر دسته، به ترتیب اولویت؛ سپس بقیه.
        $usedCategories = $shots->whereIn('id', $chosen)->pluck('category')->all();
        foreach ($shots as $shot) {
            if (count($chosen) >= $limit) {
                break;
            }
            if (! in_array($shot->id, $chosen, true) && ! in_array($shot->category, $usedCategories, true)) {
                $chosen[] = (int) $shot->id;
                $usedCategories[] = $shot->category;
            }
        }
        foreach ($shots as $shot) {
            if (count($chosen) >= $limit) {
                break;
            }
            if (! in_array($shot->id, $chosen, true)) {
                $chosen[] = (int) $shot->id;
            }
        }

        return $chosen;
    }

    public function defaultPackSize(): int
    {
        $max = (int) (ProductShotSetting::current()->max_shots_per_run ?: 6);

        return max(1, min(4, $max));
    }

    public function maxPackSize(): int
    {
        return max(1, (int) (ProductShotSetting::current()->max_shots_per_run ?: 6));
    }

    /**
     * مدل‌های سه سطح کیفیت: همان تنظیم آخرین محصول پروداکتی (انتخاب‌شده توسط مدیر در پنل)،
     * وگرنه اولین مدل تصویری فعال و مناسب برای همه‌ی سطح‌ها.
     */
    public function defaultQualityModels(): array
    {
        $levels = array_keys((array) config('product_shots.quality_levels', ['standard' => '']));
        $last = Product::query()
            ->where('product_mode', Product::MODE_PRODUCT)
            ->whereNotNull('shot_settings')
            ->latest('id')
            ->get(['id', 'shot_settings'])
            ->first(fn (Product $product) => $this->hasAllLevels((array) data_get($product->shot_settings, 'quality_models', []), $levels));
        if ($last) {
            return (array) data_get($last->shot_settings, 'quality_models');
        }

        $model = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'image')
            ->where('supports_image_input', true)
            ->whereIn('task_type', ['image_to_image', 'face_consistency'])
            ->whereIn('provider', ProviderStatus::enabled() ?: ['__none__'])
            ->orderByDesc('featured_in_image_studio')
            ->orderBy('studio_image_priority')
            ->first();
        if (! $model) {
            return [];
        }

        $row = [
            'primary_id' => $model->id,
            'primary_model' => (string) $model->openrouter_model_id,
            'primary_provider' => (string) $model->provider,
            'fallback_ids' => [],
            'fallback_models' => [],
            'fallback_providers' => [],
        ];

        return collect($levels)->mapWithKeys(fn ($level) => [$level => $row])->all();
    }

    private function hasAllLevels(array $models, array $levels): bool
    {
        foreach ($levels as $level) {
            if (empty($models[$level]['primary_model'])) {
                return false;
            }
        }

        return true;
    }

    /** هزینه‌ی کردیت هر شات در سطح استاندارد (بدون override محصول). */
    public function shotCredits(ShotLibrary $shot): int
    {
        return max(0, (int) $shot->default_credits);
    }

    /**
     * ساخت محصول پروداکتی.
     *
     * @param array{
     *   name_fa:string, name_en:string, description_fa?:?string, tags?:array, status:string,
     *   occupation_ids:array, category_ids?:array, product_description?:?string, brand_style?:?string,
     *   brand_palette?:?string, image_paths:array, shots?:array<int,array{enabled:bool,sort:int}>,
     *   custom_slides?:array<int,array>, input_image?:?string, quality_models?:array, preflight?:array,
     *   display?:array, explore_tiles?:array, created_by?:?int
     * } $data
     */
    public function create(array $data): Product
    {
        // مدل‌های تنظیم‌شده در داشبورد (اگر هست) روی پیش‌فرض عمومی؛ سطح خالی از پیش‌فرض پر می‌شود.
        $qualityModels = array_merge($this->defaultQualityModels(), array_filter((array) ($data['quality_models'] ?? [])));
        if ($qualityModels === []) {
            throw new RuntimeException('هیچ مدل تصویری فعالی برای استودیو محصول پیدا نشد؛ ابتدا مدل‌ها را در پنل تنظیم کنید.');
        }
        $occupationIds = array_values(array_unique(array_map('intval', (array) ($data['occupation_ids'] ?? []))));
        if ($occupationIds === []) {
            throw new RuntimeException('حداقل یک صنف باید انتخاب شود.');
        }
        $shotRows = collect((array) ($data['shots'] ?? []))->filter(fn ($row) => ! empty($row['enabled']));
        $customSlides = array_values(array_filter((array) ($data['custom_slides'] ?? []), fn ($slide) => trim((string) ($slide['prompt'] ?? '')) !== ''));
        if ($shotRows->isEmpty() && $customSlides === []) {
            throw new RuntimeException('حداقل یک اسلاید باید فعال باشد.');
        }

        $settings = ProductShotSetting::current();
        $occupations = Occupation::query()->whereIn('id', $occupationIds)->get();
        $standard = $qualityModels['standard'] ?? reset($qualityModels);
        $paths = array_values(array_filter(array_map('strval', (array) ($data['image_paths'] ?? []))));
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['category_ids'] ?? [])))));
        $primaryCategory = $categoryIds ? Category::query()->find($categoryIds[0]) : null;
        $status = in_array($data['status'] ?? 'draft', ['draft', 'active', 'inactive'], true) ? $data['status'] : 'draft';

        $preflight = (array) ($data['preflight'] ?? []);
        $display = (array) ($data['display'] ?? []);

        return DB::transaction(function () use ($data, $qualityModels, $occupationIds, $occupations, $standard, $paths, $categoryIds, $primaryCategory, $status, $shotRows, $customSlides, $settings, $preflight, $display): Product {
            $product = new Product();
            $product->product_mode = Product::MODE_PRODUCT;
            $product->product_code = Product::generateUniqueProductCode();
            $product->base_likes_count = random_int(120, 250);
            $product->slug = $this->uniqueSlug((string) $data['name_en']);
            $product->name_fa = trim((string) $data['name_fa']);
            $product->name_en = trim((string) $data['name_en']);
            $product->description_fa = trim((string) ($data['description_fa'] ?? '')) ?: null;
            $product->status = $status;
            $product->primary_model = (string) ($standard['primary_model'] ?? '');
            $product->ai_provider = (string) ($standard['primary_provider'] ?? 'openrouter');
            $product->fallback_models = (array) ($standard['fallback_models'] ?? []);
            $product->fallback_model_providers = (array) ($standard['fallback_providers'] ?? []);
            $product->shot_settings = [
                'niche' => (string) ($occupations->first()?->group_key ?? 'other'),
                'occupation_ids' => $occupationIds,
                'product_description' => Str::limit(trim((string) ($data['product_description'] ?? '')), 300, '') ?: null,
                'brand_palette' => Str::limit(trim((string) ($data['brand_palette'] ?? '')), 120, '') ?: null,
                'brand_style' => Str::limit(trim((string) ($data['brand_style'] ?? '')), 200, '') ?: null,
                'brand_identity_enabled' => false,
                'brand_identity_prompt' => null,
                'quality_models' => $qualityModels,
                'preflight' => [
                    'enabled' => (bool) ($preflight['enabled'] ?? $settings->preflight_enabled),
                    'model' => null,
                    'prompt' => null,
                    'blocking_issues' => array_values(array_unique((array) ($preflight['blocking_issues'] ?? array_merge(
                        (array) ($settings->preflight_blocking_issues ?? []),
                        ShotVisionService::BLOCKING,
                    )))),
                    'min_side' => (int) ($preflight['min_side'] ?? ($settings->preflight_min_side ?: config('product_shots.preflight_min_side', 900))),
                    'product_sheet_enabled' => (bool) ($preflight['product_sheet_enabled'] ?? $settings->product_sheet_enabled),
                    'product_sheet_size' => (int) ($preflight['product_sheet_size'] ?? ($settings->product_sheet_size ?: config('product_shots.product_sheet_size', 2048))),
                ],
                // عکس ورودی‌ای که اسلایدها با آن ساخته شده‌اند (فقط صفحه‌ی محصول؛ نه صفحه‌ی بساز)
                'input_image' => trim((string) ($data['input_image'] ?? '')) ?: null,
                'registered_via' => 'telegram_bot',
            ];
            $product->prompt_template = 'Product shot pack (see shot library).';
            $product->subject_type = 'product';
            $product->identity_preservation = false;
            $product->min_reference_images = 1;
            $product->max_reference_images = 3;
            $product->pricing_model = 'per_credit';
            $product->media_type = 'photo';
            $product->output_type = 'image';
            $product->output_format = 'png';
            $product->output_count = 1;
            $product->allowed_aspect_ratios = array_values((array) config('product_shots.aspect_ratios'));
            $product->aspect_ratio = (string) config('product_shots.default_aspect_ratio', '4:5');
            $product->allowed_resolutions = Product::DEFAULT_OUTPUT_RESOLUTIONS;
            $product->resolution = '1080';
            $product->timeout = 120;
            $product->watermark_enabled = (bool) ($display['watermark_enabled'] ?? false);
            $product->watermark_position = 'corner';
            $product->display_mode = in_array($display['display_mode'] ?? 'card', ['card', 'slider'], true) ? (string) ($display['display_mode'] ?? 'card') : 'card';
            $product->card_shape = in_array($display['card_shape'] ?? 'portrait', ['portrait', 'landscape', 'square'], true) ? (string) ($display['card_shape'] ?? 'portrait') : 'portrait';
            $product->gallery_layout = in_array($display['gallery_layout'] ?? 'slider', ['grid', 'masonry', 'slider'], true) ? (string) ($display['gallery_layout'] ?? 'slider') : 'slider';
            $product->explore_tiles = array_values((array) ($data['explore_tiles'] ?? ['1x1'])) ?: ['1x1'];
            $product->estimated_time = 45;
            $product->tags = array_values(array_unique(array_filter(array_merge(
                array_map(fn ($tag) => trim((string) $tag), (array) ($data['tags'] ?? [])),
                ['کسب‌وکار'],
                $occupations->pluck('name_fa')->all(),
            ))));
            $product->card_label = trim((string) ($display['card_label'] ?? '')) ?: 'پک';
            $product->card_label_enabled = true;
            $product->category_id = $primaryCategory?->id;
            $product->category = $primaryCategory?->name ?: 'کسب‌وکار';
            $product->cover = $paths[0] ?? null;
            $product->thumbnail = $paths[0] ?? 'products/thumbnails/default_placeholder.jpg';
            $product->sample_outputs = array_slice($paths, 1);
            if ($paths !== []) {
                $product->images_optimized_at = now();
            }
            if (! empty($data['created_by'])) {
                $product->created_by = (int) $data['created_by'];
                $product->updated_by = (int) $data['created_by'];
            }
            $product->save();

            $product->categories()->sync($categoryIds);
            $product->occupations()->sync($occupationIds);

            $ratios = array_values((array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']));
            $levels = array_keys((array) config('product_shots.quality_levels', ['standard' => '']));
            $library = ShotLibrary::query()->whereIn('id', $shotRows->keys()->map(fn ($id) => (int) $id)->all())->get()->keyBy('id');
            $minCredits = null;
            foreach ($shotRows as $shotId => $row) {
                $shot = $library->get((int) $shotId);
                if (! $shot) {
                    continue;
                }
                $credits = $this->shotCredits($shot);
                $defaultRatio = in_array($shot->aspect_ratio_default, $ratios, true) ? $shot->aspect_ratio_default : $ratios[0];
                ProductShot::query()->create([
                    'product_id' => $product->id,
                    'shot_id' => $shot->id,
                    'name_override' => null,
                    'enabled' => true,
                    // هیچ اسلایدی از پیش برای کاربر تیک نمی‌خورد.
                    'is_default' => false,
                    'credits_override' => null,
                    'prompt_override' => null,
                    'model_configuration' => [
                        'quality_credits' => collect($levels)->mapWithKeys(fn ($level) => [$level => $credits])->all(),
                        'quality_models' => collect($levels)->mapWithKeys(fn ($level) => [$level => []])->all(),
                    ],
                    'allowed_aspect_ratios' => $ratios,
                    'aspect_ratio_default' => $defaultRatio,
                    'aspect_ratio_user_selectable' => true,
                    'options_enabled' => ['prompt' => true, 'model' => true, 'ratio' => true, 'credits' => true, 'preview' => true],
                    'sort' => (int) ($row['sort'] ?? 0),
                ]);
                $minCredits = $minCredits === null ? $credits : min($minCredits, $credits);
            }

            foreach ($customSlides as $index => $slide) {
                $productShot = app(CustomSlideService::class)->create($product, array_merge($slide, [
                    'sort' => (int) ($slide['sort'] ?? (1000 + $index)),
                ]));
                $credits = $productShot->credits('standard');
                $minCredits = $minCredits === null ? $credits : min($minCredits, $credits);
            }

            // نمایش «از X کردیت» در کارت‌ها و فهرست ادمین — همان رفتار فرم وب.
            $product->forceFill(['credit_cost' => $minCredits ?? 0])->saveQuietly();

            // کاور: اگر عکسی نیامده بود، اولین نمونه‌ی شات‌ها.
            if (! $product->cover) {
                $sample = ProductShot::query()->where('product_id', $product->id)->with('shot')->orderBy('sort')->get()
                    ->map(fn (ProductShot $ps) => $ps->sample_image ?: $ps->shot?->sample_image)
                    ->filter()
                    ->first();
                if ($sample) {
                    $product->forceFill(['cover' => $sample, 'thumbnail' => $sample])->saveQuietly();
                }
            }

            return $product->fresh();
        });
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'shot-pack';
        $slug = $base;
        $i = 1;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
