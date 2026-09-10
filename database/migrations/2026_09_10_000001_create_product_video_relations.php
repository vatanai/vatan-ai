<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_video_relations')) {
            return;
        }

        Schema::create('product_video_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('video_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('photo_product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['video_product_id', 'photo_product_id']);
            $table->index(['photo_product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_video_relations');
    }
};
