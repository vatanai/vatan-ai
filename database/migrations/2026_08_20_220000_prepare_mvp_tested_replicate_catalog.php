<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مسیر کم‌ریسک MVP را روی مدل‌هایی می‌گذارد که در آزمایش واقعی خروجی داده‌اند.
     * مدل‌های Fal.ai و OpenRouter حذف نمی‌شوند؛ فقط تا تأیید سلامت provider از
     * انتخاب محصول و پیش‌فرض فعال کنار گذاشته می‌شوند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        $testedReplicate = [
            'google/nano-banana-2-lite',
            'google/nano-banana',
            'google/nano-banana-2',
            'google/nano-banana-pro',
            'bytedance/seedream-5-pro',
            'bytedance/seedream-4.5',
            'black-forest-labs/flux-kontext-pro',
        ];

        // فقط مدل‌های موفق در تست واقعی در انتخاب محصول/آزمایشگاه دیده شوند.
        DB::table('ai_models')
            ->where('provider', 'replicate')
            ->where('output_modality', 'image')
            ->update([
                'featured_in_lab' => false,
                'lab_priority' => 999,
                'updated_at' => now(),
            ]);

        DB::table('ai_models')
            ->where('provider', 'replicate')
            ->whereIn('openrouter_model_id', $testedReplicate)
            ->update([
                'is_active' => true,
                'featured_in_lab' => true,
                'lab_status' => 'verified',
                'updated_at' => now(),
            ]);

        // مسیرهای پرخطای فعلی در انتخاب MVP دیده نمی‌شوند، اما برای بررسی و
        // استفاده‌ی تاریخی در جدول باقی می‌مانند.
        DB::table('ai_models')
            ->whereIn('provider', ['fal', 'openrouter'])
            ->where('output_modality', 'image')
            ->update([
                'featured_in_lab' => false,
                'lab_priority' => 999,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('ai_provider_settings')) {
            $this->setProviderEnabled('replicate', true);
            $this->setProviderEnabled('openrouter', false);
            $this->setProviderEnabled('fal', false);
        }

        $configuration = $this->testedConfiguration();

        if (Schema::hasTable('model_tier_defaults')) {
            $tiers = [
                'free' => $configuration['free_quality_models']['standard'],
                'economy' => $configuration['quality_models']['standard'],
                'pro' => $configuration['quality_models']['professional'],
                'business' => $configuration['quality_models']['best'],
            ];

            foreach ($tiers as $tierKey => $selection) {
                DB::table('model_tier_defaults')
                    ->where('tier_key', $tierKey)
                    ->update([
                        'primary_model_id' => $selection['primary']['model_id'],
                        'primary_provider' => $selection['primary']['provider'],
                        'fallback_model_id' => $selection['fallback']['model_id'],
                        'fallback_provider' => $selection['fallback']['provider'],
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
            }
        }

        if (Schema::hasTable('model_quality_presets')) {
            $encoded = json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // بعضی productionها جدول را دارند اما ردیف‌های پیش‌فرض در آن
            // وجود ندارد (مثلاً بعد از import ناقص یا حذف دستی). در این حالت
            // فقط update هیچ اثری ندارد و پنجره‌ی عملیات گروهی کاملاً خالی
            // می‌ماند؛ بنابراین این migration باید داده‌ی پایه را هم
            // به‌صورت idempotent بسازد.
            $hasDefaultColumn = Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation');
            foreach ([1, 2, 3, 4] as $number) {
                $values = [
                    'name' => $number === 3 ? 'آزمایش شده' : "پیش‌فرض {$number}",
                    'configuration' => $encoded,
                    'updated_at' => now(),
                ];
                if ($hasDefaultColumn) {
                    $values['is_default_for_product_creation'] = $number === 3;
                }
                DB::table('model_quality_presets')->updateOrInsert(
                    ['preset_key' => "preset_{$number}"],
                    $values + ['created_at' => now()]
                );
            }

            $defaultId = DB::table('model_quality_presets')
                ->where('preset_key', 'preset_3')
                ->value('id')
                ?: DB::table('model_quality_presets')->orderBy('id')->value('id');

            if ($defaultId && $hasDefaultColumn) {
                DB::table('model_quality_presets')->update(['is_default_for_product_creation' => false]);
                DB::table('model_quality_presets')->where('id', $defaultId)->update([
                    'name' => 'آزمایش شده',
                    'is_default_for_product_creation' => true,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // مدل‌ها و تنظیمات MVP عمداً به حالت قبلی برنمی‌گردند؛ rollback آن‌ها
        // می‌تواند یک محصول فعال را به provider پرخطا متصل کند.
    }

    private function setProviderEnabled(string $provider, bool $enabled): void
    {
        $row = DB::table('ai_provider_settings')->where('provider', $provider)->first();
        $settings = json_decode((string) ($row->settings ?? '{}'), true);
        $settings = is_array($settings) ? $settings : [];
        $settings['admin_enabled'] = $enabled;
        $values = [
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        if (! $row) {
            $values += [
                'timeout' => 180,
                'max_retries' => 2,
                'webhook_enabled' => true,
                'created_at' => now(),
            ];
        }

        DB::table('ai_provider_settings')->updateOrInsert(['provider' => $provider], $values);
        Cache::forget('provider.' . $provider . '.enabled');
    }

    private function testedConfiguration(): array
    {
        $pair = static function (string $primaryModel, string $fallbackModel): array {
            return [
                'primary' => ['model_id' => $primaryModel, 'provider' => 'replicate'],
                'fallback' => ['model_id' => $fallbackModel, 'provider' => 'replicate'],
            ];
        };

        return [
            'quality_models' => [
                'standard' => $pair('google/nano-banana-2-lite', 'google/nano-banana'),
                'professional' => $pair('bytedance/seedream-5-pro', 'bytedance/seedream-4.5'),
                'best' => $pair('google/nano-banana-pro', 'black-forest-labs/flux-kontext-pro'),
            ],
            'free_quality_models' => [
                'standard' => $pair('google/nano-banana-2-lite', 'google/nano-banana'),
                // این سطح تا زمان خرید پلن قفل است، اما باید تنظیم معتبر داشته
                // باشد تا فعال‌سازی بعدی از مسیر خراب/خالی عبور نکند.
                'best' => $pair('google/nano-banana-pro', 'black-forest-labs/flux-kontext-pro'),
            ],
            'provider_policy' => [
                'replicate' => 'active_verified',
                'fal' => 'blocked_pending_network_ticket',
                'openrouter' => 'disabled_mvp',
            ],
            'verification' => [
                'source' => 'admin_lab_live_smoke_test',
                'verified_at' => '2026-08-20',
                'notes' => 'هفت مدل Replicate خروجی موفق داده‌اند؛ Fal.ai و OpenRouter تا رفع مشکل provider فعال نیستند.',
            ],
        ];
    }
};
