<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مدل‌های provider حذف‌شده فقط برای سابقه نگه داشته می‌شوند و دیگر قابل اجرا نیستند.
        if (Schema::hasTable('ai_models')) {
            $updates = ['is_active' => false];
            if (Schema::hasColumn('ai_models', 'featured_in_lab')) $updates['featured_in_lab'] = false;
            if (Schema::hasColumn('ai_models', 'lab_status')) $updates['lab_status'] = 'archived';
            DB::table('ai_models')->where('provider', 'liara')->update($updates);
        }

        // محصولاتی که از provider قدیمی استفاده می‌کردند به نزدیک‌ترین مدل فعال منتقل می‌شوند.
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'ai_provider') && Schema::hasTable('ai_models')) {
            foreach (DB::table('products')->where('ai_provider', 'liara')->get(['id', 'primary_model', 'fallback_model_providers']) as $product) {
                $replacement = DB::table('ai_models')
                    ->where('is_active', true)
                    ->where('provider', '!=', 'liara')
                    ->where(function ($query) use ($product) {
                        $query->where('openrouter_model_id', $product->primary_model)
                            ->orWhere('external_model_id', $product->primary_model);
                    })
                    ->orderByRaw("CASE provider WHEN 'replicate' THEN 0 WHEN 'fal' THEN 1 WHEN 'openrouter' THEN 2 ELSE 3 END")
                    ->value('provider');

                $providers = json_decode((string) $product->fallback_model_providers, true);
                if (is_array($providers)) {
                    $providers = array_map(fn ($provider) => $provider === 'liara' ? ($replacement ?: 'openrouter') : $provider, $providers);
                }

                DB::table('products')->where('id', $product->id)->update([
                    'ai_provider' => $replacement ?: 'openrouter',
                    'fallback_model_providers' => is_array($providers) ? json_encode(array_values($providers), JSON_UNESCAPED_UNICODE) : null,
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('service_credit_accounts')) {
            DB::table('service_credit_accounts')->where('slug', 'liara')->update([
                'is_active' => false,
                'show_on_dashboard' => false,
                'sync_driver' => 'manual',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // اطلاعات تاریخی عمداً دوباره فعال نمی‌شوند.
    }
};
