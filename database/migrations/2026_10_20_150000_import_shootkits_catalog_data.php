<?php

use App\Models\AiModel;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ProductShotSetting;
use App\Models\ShotLibrary;
use App\Support\ProviderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PACKAGE = 'data-import/shootkits';
    private const MAP_TABLE = 'shootkits_product_import_map';
    private const STATE_TABLE = 'shootkits_import_state';

    public function up(): void
    {
        $catalogPath = database_path(self::PACKAGE . '/catalog.json');
        if (! is_file($catalogPath)) {
            throw new RuntimeException('The ShootKits catalog package is missing.');
        }

        $catalog = json_decode((string) file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);
        $productFiles = array_values(array_filter(array_map('strval', (array) ($catalog['product_files'] ?? []))));
        if ($productFiles === [] || (int) ($catalog['product_count'] ?? 0) < 1) {
            throw new RuntimeException('The ShootKits catalog package has no product data files.');
        }
        if (! Schema::hasTable(self::MAP_TABLE)) {
            Schema::create(self::MAP_TABLE, function (Blueprint $table): void {
                $table->id();
                $table->char('source_key', 64);
                $table->unsignedSmallInteger('part_index');
                $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
                $table->unique(['source_key', 'part_index']);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable(self::STATE_TABLE)) {
            Schema::create(self::STATE_TABLE, function (Blueprint $table): void {
                $table->string('state_key', 80)->primary();
                $table->longText('state_value')->nullable();
            });
        }

        $this->copyAssets((array) ($catalog['assets'] ?? []));
        $processedProducts = 0;
        foreach ($productFiles as $relativeFile) {
            if (! str_starts_with($relativeFile, 'products/') || str_contains($relativeFile, '..')) {
                throw new RuntimeException('The ShootKits catalog contains an invalid product-data path.');
            }
            $productPath = database_path(self::PACKAGE . '/' . $relativeFile);
            if (! is_file($productPath)) {
                throw new RuntimeException('A bundled ShootKits product-data file is missing.');
            }
            $products = json_decode((string) file_get_contents($productPath), true, 512, JSON_THROW_ON_ERROR);
            foreach ((array) $products as $entry) {
                $this->importProduct((array) $entry);
                $processedProducts++;
            }
            unset($products);
        }
        if ($processedProducts !== (int) $catalog['product_count']) {
            throw new RuntimeException('The ShootKits catalog package has an inconsistent product count.');
        }

        // Imported products are meant to be public. Save the previous flag so
        // rolling this data migration back restores the earlier access policy.
        if (Schema::hasTable('product_shot_settings')) {
            $setting = ProductShotSetting::current();
            if ($setting) {
                DB::table(self::STATE_TABLE)->updateOrInsert(
                    ['state_key' => 'product_shot_settings'],
                    ['state_value' => json_encode([
                        'id' => $setting->id,
                        'enabled' => (bool) $setting->enabled,
                        'audience' => (string) $setting->audience,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
                );
                $setting->forceFill(['enabled' => true, 'audience' => 'public'])->save();
                ProductShotSetting::forgetCache();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable(self::MAP_TABLE)) {
            $productIds = DB::table(self::MAP_TABLE)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
            if ($productIds !== []) {
                DB::table('product_shots')->whereIn('product_id', $productIds)->delete();
                if (Schema::hasColumn('shot_library', 'product_id')) {
                    DB::table('shot_library')->whereIn('product_id', $productIds)->delete();
                }
                DB::table('category_product')->whereIn('product_id', $productIds)->delete();
                if (Schema::hasTable('occupation_product')) {
                    DB::table('occupation_product')->whereIn('product_id', $productIds)->delete();
                }
                DB::table('products')->whereIn('id', $productIds)->delete();
            }
        }

        if (Schema::hasTable(self::STATE_TABLE) && Schema::hasTable('product_shot_settings')) {
            $state = DB::table(self::STATE_TABLE)->where('state_key', 'product_shot_settings')->value('state_value');
            $previous = json_decode((string) $state, true) ?: [];
            if (! empty($previous['id'])) {
                ProductShotSetting::query()->whereKey($previous['id'])->update([
                    'enabled' => (bool) ($previous['enabled'] ?? false),
                    'audience' => (string) ($previous['audience'] ?? 'admins'),
                ]);
                ProductShotSetting::forgetCache();
            }
        }

        Schema::dropIfExists(self::STATE_TABLE);
        Schema::dropIfExists(self::MAP_TABLE);
    }

    private function copyAssets(array $assets): void
    {
        $disk = Storage::disk('public');
        foreach ($assets as $asset) {
            $relativePath = (string) ($asset['path'] ?? '');
            $fileName = basename((string) ($asset['file'] ?? ''));
            if ($relativePath === '' || $fileName === '' || ! str_ends_with(strtolower($relativePath), '.webp')) {
                throw new RuntimeException('The ShootKits catalog contains an invalid image path.');
            }
            $sourcePath = database_path(self::PACKAGE . '/assets/' . $fileName);
            if (! is_file($sourcePath)) {
                throw new RuntimeException('A bundled ShootKits image is missing: ' . $fileName);
            }
            if (! $disk->exists($relativePath)) {
                $disk->put($relativePath, file_get_contents($sourcePath));
            }
        }
    }

    private function importProduct(array $entry): void
    {
        $sourceKey = (string) ($entry['source_key'] ?? '');
        $partIndex = (int) ($entry['part_index'] ?? -1);
        if (! preg_match('/^[a-f0-9]{64}$/', $sourceKey) || $partIndex < 0) {
            throw new RuntimeException('A ShootKits product has an invalid import key.');
        }
        if (DB::table(self::MAP_TABLE)->where('source_key', $sourceKey)->where('part_index', $partIndex)->exists()) {
            return;
        }

        $attributes = (array) ($entry['attributes'] ?? []);
        $slides = array_values((array) ($entry['slides'] ?? []));
        if ($slides === [] || trim((string) ($attributes['name_fa'] ?? '')) === '') {
            throw new RuntimeException('A ShootKits product is missing its name or slides.');
        }

        $categorySlugMap = [
            // A few source taxonomy values are missing from Vatan or describe
            // a visual style rather than a product category. Resolve them to
            // the closest existing Vatan categories before attaching products.
            'cartoon' => 'کارتونی',
            'id-photo' => 'product-photo',
            'ششسی' => 'product-photo',
            'luxury' => 'product-photo',
        ];
        $categorySlugs = array_values(array_unique(array_filter(array_map(
            fn ($slug) => $categorySlugMap[(string) $slug] ?? (string) $slug,
            (array) ($entry['category_slugs'] ?? [])
        ))));
        $occupationSlugs = array_values(array_filter(array_map('strval', (array) ($entry['occupation_slugs'] ?? []))));
        $categories = Category::query()->whereIn('slug', $categorySlugs)->get()->keyBy('slug');
        $occupations = Occupation::query()->whereIn('slug', $occupationSlugs)->get()->keyBy('slug');
        if ($categories->count() !== count(array_unique($categorySlugs)) || $occupations->count() !== count(array_unique($occupationSlugs))) {
            throw new RuntimeException('A required Vatan category or occupation slug is missing for an imported product.');
        }

        $model = $this->resolveImageModel((string) ($attributes['ai_provider'] ?? ''), (string) ($attributes['primary_model'] ?? ''));
        $attributes['primary_model'] = (string) $model->openrouter_model_id;
        $attributes['ai_provider'] = (string) $model->provider;
        $attributes['product_mode'] = Product::MODE_PRODUCT;
        $attributes['status'] = 'active';

        $shotSettings = (array) ($attributes['shot_settings'] ?? []);
        // A scraped static description can describe a different example product
        // or mention the source prompt library. ProductShots derives this from
        // the user's actual uploaded image during preflight, so never carry it
        // across with an imported kit.
        unset($shotSettings['product_description']);
        $shotSettings['occupation_ids'] = $occupations->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $shotSettings['quality_models'] = $this->remapQualityModels((array) ($shotSettings['quality_models'] ?? []), $model);
        $attributes['shot_settings'] = $shotSettings;

        $columns = Schema::getColumnListing('products');
        $attributes = array_intersect_key($attributes, array_flip($columns));
        unset($attributes['id'], $attributes['created_by'], $attributes['updated_by'], $attributes['category_id'], $attributes['deleted_at']);

        DB::transaction(function () use ($attributes, $categories, $occupations, $slides, $sourceKey, $partIndex): void {
            $product = new Product();
            $product->forceFill($attributes);
            $product->slug = $this->uniqueSlug((string) ($attributes['slug'] ?? 'product-campaign'));
            $product->product_code = $this->uniqueProductCode((string) ($attributes['product_code'] ?? ''));
            $categoryIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $product->category_id = $categoryIds[0] ?? null;
            $product->save();
            $product->categories()->sync($categoryIds);
            $product->occupations()->sync($occupations->pluck('id')->map(fn ($id) => (int) $id)->all());

            foreach ($slides as $index => $slide) {
                $name = Str::limit(trim((string) ($slide['name_fa'] ?? '')), 120, '') ?: 'اسلاید محصول';
                $prompt = trim((string) ($slide['prompt'] ?? ''));
                $sample = trim((string) ($slide['sample_image'] ?? '')) ?: null;
                $aspectDefault = in_array((string) ($slide['aspect_ratio_default'] ?? '4:5'), ['4:5', '1:1', '9:16'], true)
                    ? (string) $slide['aspect_ratio_default'] : '4:5';
                $key = 'sk-' . substr(hash('sha256', $sourceKey . ':' . $partIndex . ':' . $index), 0, 56);
                $library = ShotLibrary::query()->create([
                    'product_id' => $product->id,
                    'key' => $key,
                    'name_fa' => $name,
                    'name_en' => null,
                    'description_fa' => null,
                    'category' => (string) ($slide['category'] ?? 'hero'),
                    'niche_tags' => [],
                    'tokens' => [],
                    'prompt_template' => $prompt,
                    'default_credits' => max(0, (int) ($slide['default_credits'] ?? 12)),
                    'aspect_ratio_default' => $aspectDefault,
                    'sample_image' => $sample,
                    'is_active' => true,
                    'sort' => $index + 1,
                ]);
                ProductShot::query()->create([
                    'product_id' => $product->id,
                    'shot_id' => $library->id,
                    'name_override' => null,
                    'enabled' => (bool) ($slide['enabled'] ?? true),
                    'is_default' => (bool) ($slide['is_default'] ?? false),
                    'credits_override' => $slide['credits_override'] ?? null,
                    'sample_image' => $sample,
                    'sort' => (int) ($slide['sort'] ?? ($index + 1)),
                    'prompt_override' => $prompt,
                    'model_configuration' => (array) ($slide['model_configuration'] ?? []),
                    'allowed_aspect_ratios' => (array) ($slide['allowed_aspect_ratios'] ?? [$aspectDefault]),
                    'aspect_ratio_default' => $aspectDefault,
                    'aspect_ratio_user_selectable' => (bool) ($slide['aspect_ratio_user_selectable'] ?? true),
                    'options_enabled' => (array) ($slide['options_enabled'] ?? ['prompt' => true, 'model' => true, 'ratio' => true, 'credits' => true, 'preview' => true]),
                ]);
            }

            DB::table(self::MAP_TABLE)->insert([
                'source_key' => $sourceKey,
                'part_index' => $partIndex,
                'product_id' => $product->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    private function uniqueSlug(string $base): string
    {
        $base = Str::slug($base) ?: 'product-campaign';
        $slug = $base;
        $suffix = 2;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = Str::limit($base, 235, '') . '-' . $suffix++;
        }

        return $slug;
    }

    private function uniqueProductCode(string $code): string
    {
        if ($code !== '' && ! Product::withTrashed()->where('product_code', $code)->exists()) {
            return $code;
        }

        return Product::generateUniqueProductCode();
    }

    private function resolveImageModel(string $provider, string $modelId): AiModel
    {
        $model = AiModel::query()->where('provider', $provider)->where('openrouter_model_id', $modelId)
            ->where('is_active', true)->where('output_modality', 'image')->where('supports_image_input', true)->first();
        if ($model) return $model;

        $enabledProviders = ProviderStatus::enabled() ?: ['__none__'];
        $model = AiModel::query()->where('is_active', true)->where('output_modality', 'image')->where('supports_image_input', true)
            ->whereIn('task_type', ['image_to_image', 'face_consistency'])->whereIn('provider', $enabledProviders)
            ->orderByDesc('featured_in_image_studio')->orderBy('studio_image_priority')->first();
        if (! $model) {
            throw new RuntimeException('No active image-to-image model is available for the imported catalog.');
        }

        return $model;
    }

    private function remapQualityModels(array $qualityModels, AiModel $primary): array
    {
        foreach ($qualityModels as $quality => $configuration) {
            if (! is_array($configuration)) continue;
            $configuration['primary_id'] = $primary->id;
            $configuration['primary_model'] = (string) $primary->openrouter_model_id;
            $configuration['primary_provider'] = (string) $primary->provider;
            $fallbackModels = array_values((array) ($configuration['fallback_models'] ?? []));
            $fallbackProviders = array_values((array) ($configuration['fallback_providers'] ?? []));
            $fallbackIds = [];
            foreach ($fallbackModels as $index => $fallbackModel) {
                $fallback = AiModel::query()->where('openrouter_model_id', (string) $fallbackModel)
                    ->where('provider', (string) ($fallbackProviders[$index] ?? ''))->where('is_active', true)->first();
                if ($fallback) $fallbackIds[] = (int) $fallback->id;
            }
            $configuration['fallback_ids'] = $fallbackIds;
            $qualityModels[$quality] = $configuration;
        }

        return $qualityModels;
    }
};
