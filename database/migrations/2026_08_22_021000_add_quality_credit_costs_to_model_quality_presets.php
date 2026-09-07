<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        DB::table('model_quality_presets')->orderBy('id')->get()->each(function ($preset): void {
            $configuration = json_decode((string) $preset->configuration, true);
            $configuration = is_array($configuration) ? $configuration : [];
            $existing = (array) ($configuration['quality_credit_costs'] ?? []);

            $configuration['quality_credit_costs'] = collect(Product::DEFAULT_QUALITY_CREDIT_COSTS)
                ->mapWithKeys(function (int $default, string $key) use ($existing): array {
                    $value = $existing[$key] ?? null;
                    return [$key => (is_numeric($value) && (int) $value > 0) ? (int) $value : $default];
                })
                ->all();

            DB::table('model_quality_presets')
                ->where('id', $preset->id)
                ->update([
                    'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        // این اطلاعات داخل JSON تنظیمات پیش‌فرض مدل نگه‌داری می‌شوند؛ حذف آن‌ها
        // در rollback می‌تواند مقدارهای ثبت‌شده‌ی مدیر را از بین ببرد، بنابراین
        // migration عمداً برگشت‌پذیری مخرب ندارد.
    }
};
