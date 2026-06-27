<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
   Schema::create('prompts', function (Blueprint $table) { //  شکل درست
        $table->id();
        $table->string('name'); // نام سبک
        $table->text('prompt'); // متن پرامپت هوش مصنوعی
        $table->text('description')->nullable(); // توضیحات (اختیاری)
        $table->string('image'); // مسیر ذخیره شده عکس کاور
        $table->boolean('is_active')->default(true); // وضعیت انتشار (فعال/غیرفعال)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
