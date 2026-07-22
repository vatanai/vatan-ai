<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'images_optimized_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->timestamp('images_optimized_at')->nullable()->after('before_images');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'images_optimized_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('images_optimized_at');
            });
        }
    }
};
