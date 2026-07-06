<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')
                  ->nullable() // الزامی برای عملکرد SET NULL
                  ->after('id') // یا هر ستونی که تمایل داری بعد از آن قرار گیرد
                  ->constrained('categories')
                  ->onDelete('set null'); // محصولات دست‌نخورده می‌مانند
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};