<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'promotional_tokens')) {
            Schema::table('users', function (Blueprint $table): void {
                // اعتبارهای موجودِ پیش از این تغییر، عمداً هدیه فرض نمی‌شوند؛
                // منبع تاریخی آن‌ها قابل تشخیص قطعی نیست و نباید ناگهان محدود شوند.
                $table->unsignedInteger('promotional_tokens')->default(0);
            });
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'promotional_credits_used')) {
                $table->unsignedInteger('promotional_credits_used')->default(0);
            }
            if (! Schema::hasColumn('orders', 'paid_credits_used')) {
                $table->unsignedInteger('paid_credits_used')->default(0);
            }
            if (! Schema::hasColumn('orders', 'promotional_credits_refunded')) {
                $table->unsignedInteger('promotional_credits_refunded')->default(0);
            }
            if (! Schema::hasColumn('orders', 'paid_credits_refunded')) {
                $table->unsignedInteger('paid_credits_refunded')->default(0);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'promotional_tokens')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('promotional_tokens');
            });
        }

        Schema::table('orders', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('orders', 'promotional_credits_used') ? 'promotional_credits_used' : null,
                Schema::hasColumn('orders', 'paid_credits_used') ? 'paid_credits_used' : null,
                Schema::hasColumn('orders', 'promotional_credits_refunded') ? 'promotional_credits_refunded' : null,
                Schema::hasColumn('orders', 'paid_credits_refunded') ? 'paid_credits_refunded' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
