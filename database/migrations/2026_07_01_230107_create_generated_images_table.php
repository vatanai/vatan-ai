<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generated_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('image_path');
            $table->text('user_prompt')->nullable();
            $table->decimal('cost', 10, 6)->default(0);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('generated_images'); }
};