<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول محصولات لایک‌شده توسط کاربر — دکمه قلب (لایک) در صفحه محصول.
     * دقیقاً هم‌ساختار با saved_products.
     */
    public function up(): void
    {
        Schema::create('liked_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liked_products');
    }
};
