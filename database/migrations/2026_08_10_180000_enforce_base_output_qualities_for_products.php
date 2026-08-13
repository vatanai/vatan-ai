<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'allowed_resolutions')) {
            return;
        }

        DB::table('products')
            ->select(['id', 'allowed_resolutions'])
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    DB::table('products')->where('id', $product->id)->update([
                        // در این نسخه رابط کاربر دقیقاً سه گزینه ۴۸۰، ۷۲۰ و ۱۰۸۰ دارد.
                        'allowed_resolutions' => json_encode(Product::DEFAULT_OUTPUT_RESOLUTIONS, JSON_UNESCAPED_UNICODE),
                        'resolution' => '720',
                    ]);
                }
            });
    }

    public function down(): void
    {
        // کیفیت‌های انتخاب‌شده‌ی محصولات هنگام rollback حذف نمی‌شوند.
    }
};
