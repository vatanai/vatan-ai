<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trend_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('image_desktop');
            $table->string('image_mobile')->nullable();
            $table->string('display_target', 20)->default('both');
            $table->unsignedSmallInteger('row_number')->default(4);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'display_target', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trend_banners');
    }
};
