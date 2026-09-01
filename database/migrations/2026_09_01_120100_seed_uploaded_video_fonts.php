<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_fonts')) {
            return;
        }

        $fonts = [
            ['name' => 'پیدا', 'slug' => 'Peyda', 'file_path' => 'fonts/video/Peyda-Medium.ttf', 'is_default' => false],
            ['name' => 'مدام', 'slug' => 'Modam', 'file_path' => 'fonts/video/Modam-Medium.ttf', 'is_default' => false],
            ['name' => 'دوران', 'slug' => 'Doran', 'file_path' => 'fonts/video/Doran-Regular.ttf', 'is_default' => false],
            ['name' => 'ابر', 'slug' => 'Abar', 'file_path' => 'fonts/video/AbarMid-Regular.ttf', 'is_default' => false],
            ['name' => 'یکان‌بخ', 'slug' => 'YekanBakh', 'file_path' => 'fonts/video/YekanBakh-Medium.ttf', 'is_default' => false],
        ];

        foreach ($fonts as $font) {
            DB::table('video_studio_fonts')->updateOrInsert(
                ['slug' => $font['slug']],
                $font + ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('video_studio_fonts')) {
            DB::table('video_studio_fonts')->whereIn('slug', ['Peyda', 'Modam', 'Doran', 'Abar'])->delete();
        }
    }
};
