<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── موتور فید: نقطه‌ی اتصال عمومی — هر آیتمی که ممکن است در فید ظاهر شود از این‌جا عبور می‌کند.
//    محصول/دسته → با content_type + content_id (morph) به جدول واقعی وصل می‌شوند.
//    کمپین/بنر → همینطور با content_id به feed_campaigns.
//    هیچ ستون/رابطه‌ای به products یا سایر جداول موجود اضافه نمی‌شود.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_content_items', function (Blueprint $table) {
            $table->id();
            $table->string('content_type'); // product / category / campaign (morphMap alias)
            $table->unsignedBigInteger('content_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['content_type', 'content_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_content_items');
    }
};
