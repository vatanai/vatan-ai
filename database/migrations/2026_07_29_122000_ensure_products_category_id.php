<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')
            && Schema::hasTable('categories')
            && !Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // ترمیم ساختار محیط عملیاتی است و rollback مخرب ندارد.
    }
};
