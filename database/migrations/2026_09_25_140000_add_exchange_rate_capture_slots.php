<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_exchange_rates', function (Blueprint $table): void {
            $table->string('capture_slot', 20)->default('legacy')->after('rate_date');
            $table->timestamp('captured_at')->nullable()->after('rate_to_irr');
            $table->dropUnique('finance_exchange_rates_currency_rate_date_unique');
            $table->unique(['currency', 'rate_date', 'capture_slot'], 'finance_exchange_rates_currency_date_slot_unique');
            $table->index(['currency', 'captured_at'], 'finance_exchange_rates_currency_captured_at_index');
        });

        DB::table('finance_exchange_rates')
            ->whereNull('captured_at')
            ->update(['captured_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('finance_exchange_rates', function (Blueprint $table): void {
            $table->dropIndex('finance_exchange_rates_currency_captured_at_index');
            $table->dropUnique('finance_exchange_rates_currency_date_slot_unique');
            $table->unique(['currency', 'rate_date'], 'finance_exchange_rates_currency_rate_date_unique');
            $table->dropColumn(['capture_slot', 'captured_at']);
        });
    }
};
