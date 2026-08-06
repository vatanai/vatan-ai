<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trend_occasions', function (Blueprint $table) {
            $table->id();
            $table->string('title_fa', 120);
            $table->string('query', 120)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'created_at']);
        });

        $now = now();
        $defaults = [
            ['title_fa' => 'تولد', 'query' => 'تولد', 'sort_order' => 3],
            ['title_fa' => 'ازدواج', 'query' => 'ازدواج', 'sort_order' => 2],
            ['title_fa' => 'مناسبت‌ها', 'query' => 'مناسبت', 'sort_order' => 1],
        ];

        foreach ($defaults as $default) {
            $categoryId = DB::table('categories')
                ->where('name_fa', $default['title_fa'])
                ->value('id');

            DB::table('trend_occasions')->insert([
                ...$default,
                'category_id' => $categoryId,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trend_occasions');
    }
};
