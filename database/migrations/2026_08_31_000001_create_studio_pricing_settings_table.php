<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('studio_pricing_settings')) {
            Schema::create('studio_pricing_settings', function (Blueprint $table): void {
                $table->id();
                $table->decimal('image_profit_percent', 8, 2)->default(20);
                $table->decimal('video_profit_percent', 8, 2)->default(30);
                $table->timestamps();
            });
        }

        if (!DB::table('studio_pricing_settings')->where('id', 1)->exists()) {
            DB::table('studio_pricing_settings')->insert([
                'id' => 1,
                'image_profit_percent' => 20,
                'video_profit_percent' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_pricing_settings');
    }
};
