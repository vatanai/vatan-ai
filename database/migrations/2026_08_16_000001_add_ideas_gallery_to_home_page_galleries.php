<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('home_page_galleries')->updateOrInsert(
            ['key' => 'ideas-gallery'],
            [
                'title' => 'ایده‌های آماده برای ساخت',
                'description' => 'تصاویر کارت‌های بخش ایده‌های آماده',
                'items' => json_encode([]),
                'is_active' => true,
                'position' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('home_page_galleries')
            ->where('key', 'inspiration-gallery')
            ->update([
                'title' => 'الهام بگیر و بساز',
                'description' => 'کارت‌های بخش الهام بگیر و بساز',
                'position' => 3,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('home_page_galleries')->where('key', 'ideas-gallery')->delete();

        DB::table('home_page_galleries')
            ->where('key', 'inspiration-gallery')
            ->update([
                'title' => 'گالری الهام',
                'description' => 'کارت‌های بخش الهام بگیر و بساز',
                'position' => 2,
                'updated_at' => now(),
            ]);
    }
};
