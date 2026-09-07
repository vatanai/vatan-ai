<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('home_page_galleries', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->json('items')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('home_page_galleries')->insert([
            [
                'key' => 'hero-gallery',
                'title' => 'گالری اول',
                'description' => 'نمای متحرک ابتدای صفحه نخست',
                'items' => json_encode([]),
                'is_active' => true,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'inspiration-gallery',
                'title' => 'گالری الهام',
                'description' => 'کارت‌های بخش الهام بگیر و بساز',
                'items' => json_encode([]),
                'is_active' => true,
                'position' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_page_galleries');
    }
};
