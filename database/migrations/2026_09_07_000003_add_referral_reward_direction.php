<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_rewards')) {
            return;
        }

        Schema::table('referral_rewards', function (Blueprint $table): void {
            if (! Schema::hasColumn('referral_rewards', 'direction')) {
                $table->string('direction', 12)->default('credit')->after('currency');
            }
            if (! Schema::hasColumn('referral_rewards', 'reversal_of_id')) {
                $table->foreignId('reversal_of_id')->nullable()->constrained('referral_rewards')->nullOnDelete()->after('plan_purchase_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('referral_rewards')) {
            return;
        }

        Schema::table('referral_rewards', function (Blueprint $table): void {
            if (Schema::hasColumn('referral_rewards', 'reversal_of_id')) {
                $table->dropForeign(['reversal_of_id']);
            }
            $columns = array_filter([
                Schema::hasColumn('referral_rewards', 'direction') ? 'direction' : null,
                Schema::hasColumn('referral_rewards', 'reversal_of_id') ? 'reversal_of_id' : null,
            ]);
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
