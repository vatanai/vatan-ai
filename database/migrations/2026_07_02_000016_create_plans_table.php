<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام پلن (مثلا: برنزی، نقره‌ای، ویژه)
            $table->unsignedBigInteger('price'); // قیمت به تومان
            $table->unsignedInteger('tokens'); // تعداد توکن‌های تخصیصی این پلن
            $table->string('image_path')->nullable(); // تصویر پلن
            $table->boolean('is_active')->default(true); // وضعیت فعال/غیرفعال
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};