<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        if (! Schema::hasColumn('plans', 'referral_commission_percent')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->decimal('referral_commission_percent', 5, 2)
                    ->unsigned()
                    ->nullable()
                    ->after('tokens');
            });
        }

        DB::table('plans')
            ->whereNull('referral_commission_percent')
            ->where('price', 0)
            ->update(['referral_commission_percent' => 0]);

        foreach ([
            10 => ['start', 'pro', 'pro-test', 'vatan-professional'],
            12 => ['premium', 'vatan-advanced'],
            15 => ['business', 'vatan-business', 'enterprise'],
        ] as $percentage => $slugs) {
            DB::table('plans')
                ->whereNull('referral_commission_percent')
                ->whereIn('slug', $slugs)
                ->update(['referral_commission_percent' => $percentage]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'referral_commission_percent')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->dropColumn('referral_commission_percent');
            });
        }
    }
};
