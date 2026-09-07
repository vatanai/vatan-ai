<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_credit_accounts')) {
            return;
        }

        Schema::table('service_credit_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('service_credit_accounts', 'critical_balance_threshold')) {
                $table->decimal('critical_balance_threshold', 18, 6)->default(0)->after('low_balance_threshold');
            }
            if (!Schema::hasColumn('service_credit_accounts', 'alerts_enabled')) {
                $table->boolean('alerts_enabled')->default(true)->after('show_on_dashboard');
            }
        });

        $knownUsdAccounts = ['openrouter', 'cloudiva', 'fal', 'replicate'];
        DB::table('service_credit_accounts')
            ->whereIn('slug', $knownUsdAccounts)
            ->where('currency', 'USD')
            ->update([
                'low_balance_threshold' => 5,
                'critical_balance_threshold' => 1,
                'alerts_enabled' => true,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('sms_providers')) {
            DB::table('service_credit_accounts')->updateOrInsert(
                ['slug' => 'melipayamak'],
                [
                    'name' => 'ملی‌پیامک',
                    'currency' => 'IRR',
                    'manual_balance' => 0,
                    'low_balance_threshold' => 500000,
                    'critical_balance_threshold' => 100000,
                    'show_on_dashboard' => true,
                    'alerts_enabled' => true,
                    'is_active' => true,
                    'sync_driver' => 'melipayamak',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('service_credit_accounts')) {
            return;
        }

        DB::table('service_credit_accounts')->where('slug', 'melipayamak')->delete();

        Schema::table('service_credit_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('service_credit_accounts', 'alerts_enabled')) {
                $table->dropColumn('alerts_enabled');
            }
            if (Schema::hasColumn('service_credit_accounts', 'critical_balance_threshold')) {
                $table->dropColumn('critical_balance_threshold');
            }
        });
    }
};
