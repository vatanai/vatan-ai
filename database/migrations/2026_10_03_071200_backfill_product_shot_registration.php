<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * داده‌های محصولات پروداکتی قدیمی را با فرم پنج‌مرحله‌ای جدید سازگار می‌کند.
 * این مهاجرت فقط فیلدهای خالی را پر می‌کند و تنظیمات سفارشی قبلی را تغییر نمی‌دهد.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('product_shots')) return;

        $occupationsByGroup = Schema::hasTable('occupations')
            ? DB::table('occupations')->where('is_active', true)->orderBy('sort')->get()->groupBy('group_key')
            : collect();
        $fallbackOccupation = $occupationsByGroup->get('other')?->first()?->id
            ?? $occupationsByGroup->flatten()->first()?->id;

        DB::table('products')->where('product_mode', 'product')->orderBy('id')->chunkById(100, function ($products) use ($occupationsByGroup, $fallbackOccupation): void {
            foreach ($products as $product) {
                $settings = json_decode((string) ($product->shot_settings ?? ''), true);
                $settings = is_array($settings) ? $settings : [];
                $niche = (string) ($settings['niche'] ?? 'other');
                $occupationId = $occupationsByGroup->get($niche)?->first()?->id ?? $fallbackOccupation;

                if ($occupationId && Schema::hasTable('occupation_product')) {
                    DB::table('occupation_product')->insertOrIgnore([
                        'occupation_id' => $occupationId,
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if (empty($settings['occupation_ids'])) $settings['occupation_ids'] = [(int) $occupationId];
                }

                if (empty($settings['quality_models'])) {
                    $model = DB::table('ai_models')
                        ->where('openrouter_model_id', (string) $product->primary_model)
                        ->when($product->ai_provider ?? null, fn ($query, $provider) => $query->where('provider', $provider))
                        ->first();
                    if ($model) {
                        $configuration = [
                            'primary_id' => (int) $model->id,
                            'primary_model' => (string) $model->openrouter_model_id,
                            'primary_provider' => (string) $model->provider,
                            'fallback_ids' => [],
                            'fallback_models' => array_values((array) json_decode((string) ($product->fallback_models ?? '[]'), true)),
                            'fallback_providers' => array_values((array) json_decode((string) ($product->fallback_model_providers ?? '[]'), true)),
                        ];
                        $settings['quality_models'] = array_fill_keys(['standard', 'professional', 'best'], $configuration);
                    }
                }
                if (! array_key_exists('brand_identity_enabled', $settings)) $settings['brand_identity_enabled'] = true;

                DB::table('products')->where('id', $product->id)->update([
                    'shot_settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'max_reference_images' => 4,
                    'updated_at' => $product->updated_at,
                ]);
            }
        });

        DB::table('product_shots')->orderBy('id')->chunkById(200, function ($rows): void {
            $shotDefaults = DB::table('shot_library')->whereIn('id', $rows->pluck('shot_id'))->pluck('default_credits', 'id');
            $shotRatios = DB::table('shot_library')->whereIn('id', $rows->pluck('shot_id'))->pluck('aspect_ratio_default', 'id');
            foreach ($rows as $row) {
                $configuration = json_decode((string) ($row->model_configuration ?? ''), true);
                $configuration = is_array($configuration) ? $configuration : [];
                if (empty($configuration['quality_credits'])) {
                    $standard = max(0, (int) ($row->credits_override ?? $shotDefaults[$row->shot_id] ?? 0));
                    $configuration['quality_credits'] = [
                        'standard' => $standard,
                        'professional' => (int) ceil($standard * 1.5),
                        'best' => $standard * 2,
                    ];
                }
                $defaultRatio = (string) ($row->aspect_ratio_default ?: ($shotRatios[$row->shot_id] ?? '4:5'));

                DB::table('product_shots')->where('id', $row->id)->update([
                    'model_configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'allowed_aspect_ratios' => $row->allowed_aspect_ratios ?: json_encode([$defaultRatio]),
                    'aspect_ratio_default' => $defaultRatio,
                    'options_enabled' => $row->options_enabled ?: json_encode(['prompt' => true, 'model' => true, 'ratio' => true, 'credits' => true, 'preview' => true]),
                ]);
            }
        });
    }

    public function down(): void
    {
        // بازگردانی داده ممکن است تنظیمات ذخیره‌شده‌ی بعدی کاربر را حذف کند؛ عمداً بدون عملیات است.
    }
};
