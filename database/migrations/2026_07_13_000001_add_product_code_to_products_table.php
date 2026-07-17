<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کد ۶ رقمی یکتای هر محصول — پیش از اسلاگ در لینک عمومی محصول قرار می‌گیرد.
     * مثال: aivatan.com/app/product/546834-concept-sketchbook-portrait
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_code', 6)->nullable()->unique()->after('slug');
        });

        // بک‌فیل: به تمام محصولات موجود که کد ندارند یک کد ۶ رقمی یکتا اختصاص بده
        $products = DB::table('products')
            ->whereNull('product_code')
            ->orWhere('product_code', '')
            ->get(['id']);

        $usedCodes = [];

        foreach ($products as $product) {
            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (in_array($code, $usedCodes, true) || DB::table('products')->where('product_code', $code)->exists());

            $usedCodes[] = $code;

            DB::table('products')->where('id', $product->id)->update(['product_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_code');
        });
    }
};
