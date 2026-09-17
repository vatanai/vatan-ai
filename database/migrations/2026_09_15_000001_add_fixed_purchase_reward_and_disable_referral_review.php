<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_settings')) {
            return;
        }

        if (! Schema::hasColumn('referral_settings', 'purchase_reward_tokens')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                $table->unsignedInteger('purchase_reward_tokens')
                    ->default(5)
                    ->after('inviter_reward_tokens');
            });
        }

        DB::table('referral_settings')->update([
            'registration_gift_tokens' => 30,
            'invitee_reward_tokens' => 3,
            'inviter_reward_tokens' => 5,
            'purchase_reward_tokens' => 5,
            'reward_trigger' => 'registration',
            'referral_discount_percent' => 10,
            'purchase_commission_percent' => 10,
            'attribution_window_days' => 30,
            'registration_gift_review_repeated_ip' => false,
            'registration_gift_review_repeated_device' => false,
            'review_repeated_ip' => false,
            'review_repeated_device' => false,
            'daily_inviter_reward_limit' => null,
            'monthly_inviter_reward_limit' => null,
            'campaign_token_budget' => null,
            'campaign_starts_at' => null,
            'campaign_ends_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('referral_settings') && Schema::hasColumn('referral_settings', 'purchase_reward_tokens')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                $table->dropColumn('purchase_reward_tokens');
            });
        }
    }
};
