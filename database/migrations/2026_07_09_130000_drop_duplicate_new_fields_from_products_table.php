<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حذف ستون‌های تکراریِ new_* که کارِ فیلدهای اصلی را دوباره‌کاری می‌کردند:
 * new_status ↔ status، new_card_color ↔ accent_color، new_gallery_preview_mode ↔ gallery_layout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['new_status', 'new_card_color', 'new_gallery_preview_mode'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('new_status')->default('draft');
            $table->string('new_card_color')->default('#A07AF5');
            $table->string('new_gallery_preview_mode')->default('grid');
        });
    }
};
