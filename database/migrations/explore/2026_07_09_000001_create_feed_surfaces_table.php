<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── موتور فید: جدول بسترهای نمایش (Explore/Home/Trending/...) ──
// بخشی از سیستم مستقل «موتور فید» — به هیچ جدول دیگری در پروژه دست نمی‌زند.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_surfaces', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // explore / home / trending / ...
            $table->string('title_fa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_surfaces');
    }
};
