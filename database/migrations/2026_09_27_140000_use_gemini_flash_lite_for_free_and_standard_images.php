<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRESET_KEY = 'preset_or_fal';

    private const GEMINI_MODEL = 'google/gemini-3.1-flash-lite-image';

    private const GPT_MINI_MODEL = 'openai/gpt-image-1-mini';

    /**
     * مسیر کاربران بدون پلن را روی دو مدل اقتصادی OpenRouter می‌گذارد و
     * مدل اصلی کیفیت استاندارد را نیز با Gemini Flash Lite یکسان می‌کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        $this->assertRequiredModelsAreActive();

        DB::transaction(function (): void {
            $this->updatePreset();
            $this->updateTierDefaults();
            $this->updateExistingProducts();
        });
    }

    public function down(): void
    {
        // تنظیم قبلی محصولات ممکن است اختصاصی بوده باشد و بازگردانی جمعی امن نیست.
    }

    private function updatePreset(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        $preset = DB::table('model_quality_presets')
            ->where('preset_key', self::PRESET_KEY)
            ->first(['id', 'configuration']);

        if (! $preset) {
            throw new \RuntimeException('OpenRouter + Fal.ai quality preset was not found.');
        }

        $configuration = $this->decode($preset->configuration);
        data_set($configuration, 'free_quality_models.standard', $this->freeRoute());
        data_set($configuration, 'quality_models.standard.primary', $this->standardPrimary());

        DB::table('model_quality_presets')->where('id', $preset->id)->update([
            'configuration' => $this->encode($configuration),
            'updated_at' => now(),
        ]);
    }

    private function updateTierDefaults(): void
    {
        if (! Schema::hasTable('model_tier_defaults')) {
            return;
        }

        DB::table('model_tier_defaults')->where('tier_key', 'free')->update([
            'primary_model_id' => self::GEMINI_MODEL,
            'primary_provider' => 'openrouter',
            'fallback_model_id' => self::GPT_MINI_MODEL,
            'fallback_provider' => 'openrouter',
            'updated_at' => now(),
        ]);

        DB::table('model_tier_defaults')->where('tier_key', 'economy')->update([
            'primary_model_id' => self::GEMINI_MODEL,
            'primary_provider' => 'openrouter',
            'updated_at' => now(),
        ]);
    }

    private function updateExistingProducts(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'model_configuration')) {
            return;
        }

        $query = DB::table('products')->whereNotNull('model_configuration');
        if (Schema::hasColumn('products', 'output_type')) {
            $query->where(function ($builder): void {
                $builder->whereNull('output_type')->orWhere('output_type', '!=', 'video');
            });
        } elseif (Schema::hasColumn('products', 'media_type')) {
            $query->where(function ($builder): void {
                $builder->whereNull('media_type')->orWhere('media_type', '!=', 'video');
            });
        }

        $query->select(['id', 'model_configuration'])
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    $configuration = $this->decode($product->model_configuration);
                    if (! $this->belongsToTargetPreset($configuration)) {
                        continue;
                    }

                    data_set($configuration, 'free_quality_models.standard', $this->freeRoute());
                    data_set($configuration, 'quality_models.standard.primary', $this->standardPrimary());

                    if (data_get($configuration, 'tiers.free') !== null) {
                        data_set($configuration, 'tiers.free', $this->freeRoute());
                    }
                    if (data_get($configuration, 'tiers.economy') !== null) {
                        data_set($configuration, 'tiers.economy.primary', $this->standardPrimary());
                    }

                    DB::table('products')->where('id', $product->id)->update([
                        'model_configuration' => $this->encode($configuration),
                        'updated_at' => now(),
                    ]);
                }
            }, 'id');
    }

    private function belongsToTargetPreset(array $configuration): bool
    {
        if (data_get($configuration, 'quality_preset_key') === self::PRESET_KEY) {
            return true;
        }

        return data_get($configuration, 'quality_models.standard.primary.model_id') === self::GPT_MINI_MODEL
            && data_get($configuration, 'quality_models.standard.primary.provider') === 'openrouter'
            && data_get($configuration, 'quality_models.standard.fallback.provider') === 'fal';
    }

    private function assertRequiredModelsAreActive(): void
    {
        foreach ([self::GEMINI_MODEL, self::GPT_MINI_MODEL] as $modelId) {
            $exists = DB::table('ai_models')
                ->where('provider', 'openrouter')
                ->where('openrouter_model_id', $modelId)
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                throw new \RuntimeException("Required active OpenRouter model is missing: {$modelId}");
            }
        }
    }

    private function freeRoute(): array
    {
        return [
            'primary' => $this->standardPrimary(),
            'fallback' => [
                'model_id' => self::GPT_MINI_MODEL,
                'provider' => 'openrouter',
            ],
        ];
    }

    private function standardPrimary(): array
    {
        return [
            'model_id' => self::GEMINI_MODEL,
            'provider' => 'openrouter',
        ];
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_string($value) ? (json_decode($value, true) ?: []) : [];
    }

    private function encode(array $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
};
