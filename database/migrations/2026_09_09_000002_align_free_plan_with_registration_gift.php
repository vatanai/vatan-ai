<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        $giftTokens = 30;

        if (Schema::hasTable('referral_settings')) {
            DB::table('referral_settings')->update([
                'registration_gift_tokens' => $giftTokens,
                'updated_at' => now(),
            ]);
        }

        foreach (['free', 'vatan-gift'] as $slug) {
            $plan = DB::table('plans')->where('slug', $slug)->first();
            if (! $plan) {
                continue;
            }

            $update = [
                'tokens' => $giftTokens,
                'updated_at' => now(),
            ];

            if ($slug === 'vatan-gift' && Schema::hasColumn('plans', 'home_pricing_config')) {
                $config = json_decode((string) $plan->home_pricing_config, true);
                $config = is_array($config) ? $config : [];
                $config['credit'] = '۳۰ اعتبار هدیه برای شروع';
                $config['secondary_stat'] = is_array($config['secondary_stat'] ?? null)
                    ? $config['secondary_stat']
                    : [];
                $config['secondary_stat']['value'] = '۳۰٬۰۰۰ تومان هدیه';
                $update['home_pricing_config'] = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            DB::table('plans')->where('id', $plan->id)->update($update);
        }
    }

    public function down(): void
    {
        // مقدار تنظیمات و متن پلن ممکن است بعد از استقرار توسط مدیر تغییر کرده باشد؛ بازگردانی خودکار امن نیست.
    }
};
