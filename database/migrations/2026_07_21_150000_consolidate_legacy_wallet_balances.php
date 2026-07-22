<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets') || ! Schema::hasColumn('users', 'tokens')) {
            return;
        }

        // برای حساب‌های قدیمی که فقط wallet داشته‌اند، موجودی از دست نرود.
        // مقدار بیشتر انتخاب می‌شود چون در دوره گذار بعضی مسیرها فقط یکی از دو منبع را کم کرده‌اند.
        DB::table('wallets')->orderBy('id')->each(function ($wallet) {
            $user = DB::table('users')->where('id', $wallet->user_id)->first();

            if ($user && (int) $wallet->tokens_balance > (int) $user->tokens) {
                DB::table('users')->where('id', $wallet->user_id)->update([
                    'tokens' => (int) $wallet->tokens_balance,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // انتقال موجودی برگشت‌پذیر نیست؛ جدول قدیمی عمداً حذف نشده است.
    }
};
