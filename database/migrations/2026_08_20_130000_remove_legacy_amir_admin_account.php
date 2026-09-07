<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admins')
            ->where('email', 'amirtojar86@gmail.com')
            ->delete();
    }

    public function down(): void
    {
        // حساب حذف‌شده عمداً قابل بازگردانی خودکار نیست.
    }
};
