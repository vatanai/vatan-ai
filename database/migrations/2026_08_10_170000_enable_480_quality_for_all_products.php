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

        $supported = Product::supportedOutputResolutions();

        DB::table('products')
            ->select(['id', 'allowed_resolutions'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($supported): void {
                foreach ($products as $product) {
                    $configured = json_decode((string) $product->allowed_resolutions, true);
                    $configured = is_array($configured) ? array_map('strval', $configured) : [];

                    // سه کیفیت پایه برای تمام محصولات فعال‌اند و ۷۲۰ همیشه
                    // انتخاب پیش‌فرض صفحه ساخت است.
                    $enabled = array_values(array_intersect(
                        $supported,
                        array_unique(array_merge(Product::DEFAULT_OUTPUT_RESOLUTIONS, $configured))
                    ));

                    DB::table('products')->where('id', $product->id)->update([
                        'allowed_resolutions' => json_encode($enabled, JSON_UNESCAPED_UNICODE),
                        'resolution' => '720',
                    ]);
                }
            });
    }

    public function down(): void
    {
        // بازگردانی نباید انتخاب کیفیت ثبت‌شده توسط مدیر را از محصولات حذف کند.
    }
};
