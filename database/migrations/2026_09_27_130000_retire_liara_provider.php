<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_models')) {
            $updates = ['is_active' => false, 'updated_at' => now()];
            if (Schema::hasColumn('ai_models', 'featured_in_lab')) $updates['featured_in_lab'] = false;
            if (Schema::hasColumn('ai_models', 'lab_status')) $updates['lab_status'] = 'archived';
            DB::table('ai_models')->where('provider', 'liara')->update($updates);
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'ai_provider')) {
            DB::table('products')->where('ai_provider', 'liara')->update([
                'ai_provider' => 'openrouter',
                'updated_at' => now(),
            ]);

            if (Schema::hasColumn('products', 'fallback_model_providers')) {
                DB::table('products')
                    ->whereNotNull('fallback_model_providers')
                    ->orderBy('id')
                    ->each(function ($product): void {
                        $providers = json_decode((string) $product->fallback_model_providers, true);
                        if (!is_array($providers) || !in_array('liara', $providers, true)) return;

                        DB::table('products')->where('id', $product->id)->update([
                            'fallback_model_providers' => json_encode(array_map(
                                fn ($provider) => $provider === 'liara' ? 'openrouter' : $provider,
                                $providers
                            ), JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                    });
            }
        }

        if (Schema::hasTable('service_credit_accounts')) {
            DB::table('service_credit_accounts')->where('slug', 'liara')->update([
                'is_active' => false,
                'show_on_dashboard' => false,
                'alerts_enabled' => false,
                'sync_driver' => 'manual',
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('ai_provider_settings')) {
            DB::table('ai_provider_settings')->where('provider', 'liara')->delete();
        }

        Cache::forget('provider.liara.enabled');
        Cache::forget('finance.liara_credits');
        Cache::forget('finance.admin_credit_overview');
        Cache::forget('finance.dashboard_credit_overview');
    }

    public function down(): void
    {
        // داده‌های تاریخی عمداً خودکار فعال نمی‌شوند.
    }
};
