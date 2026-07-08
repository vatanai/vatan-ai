<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * افزودن پلن فعلی کاربر (رابطه واقعی با جدول plans) و
     * موجودی درآمد رفرال (تومان) — برای باکس‌های آمار واقعی صفحه پروفایل.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('status')
                    ->constrained('plans')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'referral_earnings')) {
                $table->unsignedBigInteger('referral_earnings')->default(0)->after('plan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'plan_id')) {
                $table->dropConstrainedForeignId('plan_id');
            }
            if (Schema::hasColumn('users', 'referral_earnings')) {
                $table->dropColumn('referral_earnings');
            }
        });
    }
};
