<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('base_likes_count')->default(0);
        });

        DB::table('products')->select('id')->orderBy('id')->chunkById(200, function ($products) {
            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update([
                    'base_likes_count' => random_int(120, 250),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('base_likes_count');
        });
    }
};
