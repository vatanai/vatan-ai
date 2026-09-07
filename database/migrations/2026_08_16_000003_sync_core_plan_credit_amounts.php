<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $credits = [
            'free' => ['tokens' => 30, 'sort_order' => 1, 'featured' => false],
            'pro' => ['tokens' => 100, 'sort_order' => 3, 'featured' => true],
            'premium' => ['tokens' => 200, 'sort_order' => 4, 'featured' => false],
            'business' => ['tokens' => 500, 'sort_order' => 5, 'featured' => false],
        ];

        foreach ($credits as $slug => $values) {
            $plan = DB::table('plans')->where('slug', $slug)->first();
            if (!$plan) {
                continue;
            }

            $overrides = json_decode($plan->audience_overrides ?: '[]', true);
            $overrides = is_array($overrides) ? $overrides : [];
            $loyal = is_array($overrides['loyal'] ?? null) ? $overrides['loyal'] : [];
            $loyal['tokens'] = $values['tokens'];
            $loyal['bonus_tokens'] = 0;
            $overrides['loyal'] = $loyal;

            DB::table('plans')->where('id', $plan->id)->update([
                'tokens' => $values['tokens'],
                'audience_overrides' => json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'active',
                'archived_at' => null,
                'card_style' => 'landing',
                'is_featured' => $values['featured'],
                'sort_order' => $values['sort_order'],
                'updated_at' => $now,
            ]);
        }

        DB::table('plans')->where('slug', 'start')->update(['sort_order' => 2, 'updated_at' => $now]);

        $display = DB::table('plan_settings')->where('key', 'display')->value('value');
        $display = json_decode($display ?: '[]', true);
        $display = is_array($display) ? $display : [];
        $display['home_limit'] = 5;

        DB::table('plan_settings')->updateOrInsert(
            ['key' => 'display'],
            ['value' => json_encode($display, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        // مقادیر قدیمی هر محیط متفاوت بوده‌اند؛ بازگردانی داده‌ی قبلی به‌صورت خودکار امن نیست.
    }
};
