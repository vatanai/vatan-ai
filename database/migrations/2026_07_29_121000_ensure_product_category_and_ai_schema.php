<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مهاجرت صرفاً ترمیمی و idempotent است؛ هیچ ردیف محصول یا دسته‌بندی
        // را تغییر یا حذف نمی‌کند و فقط ساختارهای مفقود محیط عملیاتی را می‌سازد.
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'ai_provider')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('ai_provider', 30)->default('openrouter')->after('primary_model')->index();
            });
        }

        if (!Schema::hasTable('category_product')
            && Schema::hasTable('products')
            && Schema::hasTable('categories')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->primary(['product_id', 'category_id']);
            });
        }
    }

    public function down(): void
    {
        // این مهاجرت ترمیمی عمداً rollback مخرب ندارد؛ ممکن است ساختار پیش از
        // اجرای آن نیز در محیط وجود داشته باشد و حذف آن به محصولات آسیب بزند.
    }
};
