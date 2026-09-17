<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_settings')
            || Schema::hasColumn('referral_settings', 'referral_rewards_require_admin_approval')) {
            return;
        }

        Schema::table('referral_settings', function (Blueprint $table): void {
            $table->boolean('referral_rewards_require_admin_approval')
                ->default(false)
                ->after('referral_enabled');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('referral_settings')
            && Schema::hasColumn('referral_settings', 'referral_rewards_require_admin_approval')) {
            Schema::table('referral_settings', function (Blueprint $table): void {
                $table->dropColumn('referral_rewards_require_admin_approval');
            });
        }
    }
};
