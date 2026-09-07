<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * پنجره‌ی عملیات گروهی به ردیف‌های پیش‌فرض کیفیت نیاز دارد. بعضی
     * productionها migration اصلی را ثبت کرده‌اند اما ردیف‌های جدول را ندارند
     * یا بعداً حذف شده‌اند؛ این migration داده‌ی پایه را بدون ایجاد ردیف تکراری
     * برمی‌گرداند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        $configuration = [
            'quality_models' => [
                'standard' => $this->pair('google/nano-banana-2-lite', 'google/nano-banana'),
                'professional' => $this->pair('bytedance/seedream-5-pro', 'bytedance/seedream-4.5'),
                'best' => $this->pair('google/nano-banana-pro', 'black-forest-labs/flux-kontext-pro'),
            ],
            'free_quality_models' => [
                'standard' => $this->pair('google/nano-banana-2-lite', 'google/nano-banana'),
                'best' => $this->pair('google/nano-banana-pro', 'black-forest-labs/flux-kontext-pro'),
            ],
        ];
        $encoded = json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hasDefaultColumn = Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation');

        foreach ([1, 2, 3, 4] as $number) {
            $values = [
                'name' => $number === 3 ? 'آزمایش شده' : "پیش‌فرض {$number}",
                'configuration' => $encoded,
                'updated_at' => now(),
                'created_at' => now(),
            ];
            if ($hasDefaultColumn) {
                $values['is_default_for_product_creation'] = $number === 3;
            }
            DB::table('model_quality_presets')->updateOrInsert(
                ['preset_key' => "preset_{$number}"],
                $values
            );
        }

        if ($hasDefaultColumn) {
            DB::table('model_quality_presets')->update(['is_default_for_product_creation' => false]);
            DB::table('model_quality_presets')->where('preset_key', 'preset_3')->update([
                'name' => 'آزمایش شده',
                'is_default_for_product_creation' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // این داده‌ی پایه برای پایداری عملیات گروهی لازم است و حذف آن امن نیست.
    }

    private function pair(string $primary, string $fallback): array
    {
        return [
            'primary' => ['model_id' => $primary, 'provider' => 'replicate'],
            'fallback' => ['model_id' => $fallback, 'provider' => 'replicate'],
        ];
    }
};
