<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('video_studio_fonts')) {
            Schema::create('video_studio_fonts', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 80)->unique();
                $table->string('file_path', 255);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        $fonts = [
            ['name' => 'یکان', 'slug' => 'B_Yekan', 'file_path' => 'fonts/B_Yekan.ttf', 'is_default' => true],
            ['name' => 'یکان‌بخ', 'slug' => 'YekanBakh', 'file_path' => 'fonts/YekanBakh-Regular.ttf', 'is_default' => false],
            ['name' => 'ایران‌سنس', 'slug' => 'IRANSansX', 'file_path' => 'fonts/IRANSansXFaNum-RegularD4.ttf', 'is_default' => false],
        ];
        foreach ($fonts as $font) {
            DB::table('video_studio_fonts')->updateOrInsert(
                ['slug' => $font['slug']],
                $font + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('video_studio_fonts');
    }
};
