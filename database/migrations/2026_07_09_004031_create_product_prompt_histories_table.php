<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prompt_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->text('prompt_text');
            $table->integer('version_number');
            $table->foreignId('user_id')->nullable()->constrained(); // شناسه ادمین تغییر دهنده
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prompt_histories');
    }
};