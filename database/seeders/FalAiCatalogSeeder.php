<?php

namespace Database\Seeders;

use App\Models\AiProviderSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * کاتالوگ کامل Fal.ai را از بسته نسخه‌بندی‌شده داخل پروژه وارد می‌کند.
 *
 * این Seeder عمداً idempotent است: اجرای مجدد مدل تکراری نمی‌سازد و
 * تنظیمات مدیریتی مدل‌های موجود (فعال‌بودن، قیمت و دسته‌بندی پیشنهادی)
 * را بازنویسی نمی‌کند.
 */
class FalAiCatalogSeeder extends Seeder
{
    private const CATALOG_FILE = 'data-import/fal_ai_models.json.gz';

    private const CATALOG_SHA256 = '16931871381642085236dc59715339124bf90f6a68457325eb38c797b83987a1';

    public function run(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        $path = database_path(self::CATALOG_FILE);
        if (! is_file($path)) {
            throw new RuntimeException('بسته پایدار کاتالوگ fal.ai در پروژه وجود ندارد.');
        }

        if (! hash_equals(self::CATALOG_SHA256, (string) hash_file('sha256', $path))) {
            throw new RuntimeException('صحت بسته کاتالوگ fal.ai تأیید نشد.');
        }

        $decoded = gzdecode((string) file_get_contents($path));
        $payload = is_string($decoded) ? json_decode($decoded, true) : null;
        $models = is_array($payload['models'] ?? null) ? $payload['models'] : [];
        $expectedCount = (int) ($payload['count'] ?? 0);

        if ($expectedCount < 1 || count($models) !== $expectedCount) {
            throw new RuntimeException('بسته کاتالوگ fal.ai ناقص یا نامعتبر است.');
        }

        $columns = array_flip(Schema::getColumnListing('ai_models'));
        $preservedOnUpdate = [
            'is_active',
            'cost_per_generation',
            'cost_per_generation_usd',
            'recommended_category_ids',
        ];

        DB::transaction(function () use ($models, $columns, $preservedOnUpdate, $expectedCount): void {
            $now = now();

            foreach ($models as $model) {
                $row = array_intersect_key((array) $model, $columns);
                $row['provider'] = 'fal';
                $row['provider_name'] = 'Fal.ai';
                $row['external_model_id'] = trim((string) ($row['external_model_id'] ?? $row['openrouter_model_id'] ?? ''));
                $row['openrouter_model_id'] = trim((string) ($row['openrouter_model_id'] ?? $row['external_model_id']));

                if ($row['external_model_id'] === '' || $row['openrouter_model_id'] === '') {
                    continue;
                }

                $existingId = DB::table('ai_models')
                    ->where('provider', 'fal')
                    ->where(function ($query) use ($row) {
                        $query->where('external_model_id', $row['external_model_id'])
                            ->orWhere('openrouter_model_id', $row['openrouter_model_id']);
                    })
                    ->value('id');

                if ($existingId) {
                    foreach ($preservedOnUpdate as $field) {
                        unset($row[$field]);
                    }

                    $row['updated_at'] = $now;
                    DB::table('ai_models')->where('id', $existingId)->update($row);
                    continue;
                }

                $row['is_active'] = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                DB::table('ai_models')->insert($row);
            }

            $importedCount = DB::table('ai_models')->where('provider', 'fal')->count();
            if ($importedCount < $expectedCount) {
                throw new RuntimeException("فقط {$importedCount} مدل از {$expectedCount} مدل fal.ai وارد شد.");
            }

            $this->upsertProviderSetting($expectedCount, $now);
        }, 3);

        $this->command?->info("✔ {$expectedCount} مدل Fal.ai ثبت/به‌روزرسانی شدند.");
    }

    private function upsertProviderSetting(int $modelCount, mixed $now): void
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            return;
        }

        $setting = AiProviderSetting::firstOrNew(['provider' => 'fal']);

        if (! $setting->exists || blank($setting->base_url)) {
            $setting->base_url = 'https://queue.fal.run';
        }
        if (! $setting->exists || ! $setting->timeout) {
            $setting->timeout = 600;
        }
        if (! $setting->exists || $setting->max_retries === null) {
            $setting->max_retries = 2;
        }
        if (! $setting->exists) {
            $setting->webhook_enabled = true;
        }

        $settings = (array) $setting->settings;
        $settings['catalog_source'] = 'fal.ai official catalog';
        $settings['catalog_models_count'] = $modelCount;
        $settings['catalog_imported_at'] = $now->toIso8601String();
        $setting->settings = $settings;
        $setting->save();
    }
}
