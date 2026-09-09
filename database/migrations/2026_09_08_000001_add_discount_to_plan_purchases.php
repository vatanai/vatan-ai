<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_purchases')) {
            return;
        }

        Schema::table('plan_purchases', function (Blueprint $table): void {
            if (! Schema::hasColumn('plan_purchases', 'discount_id')) {
                $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete()->after('discount_amount');
            }
            if (! Schema::hasColumn('plan_purchases', 'discount_code')) {
                $table->string('discount_code', 40)->nullable()->after('discount_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plan_purchases')) {
            return;
        }

        Schema::table('plan_purchases', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('plan_purchases', 'discount_id')) {
                $columns[] = 'discount_id';
            }
            if (Schema::hasColumn('plan_purchases', 'discount_code')) {
                $columns[] = 'discount_code';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
