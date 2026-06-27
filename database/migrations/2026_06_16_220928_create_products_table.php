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
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');             // نام سبک (مثلا: کارتونی و انیمه)
        $table->string('description');      // توضیحات کوتاه فرانت
        $table->text('prompt');             // پرامپت انگلیسی اصلی برای هوش مصنوعی
        $table->string('image');            // آدرس عکس نمونه سبک در فولدر storage
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
