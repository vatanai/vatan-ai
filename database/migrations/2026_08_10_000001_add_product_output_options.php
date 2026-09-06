<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'allowed_resolutions')) {
                $table->json('allowed_resolutions')->nullable()->after('resolution');
            }
        });

        $allRatios = json_encode(['auto', '1:1', '9:16', '16:9', '2:3', '3:2', '3:4', '4:3'], JSON_UNESCAPED_UNICODE);
        $allResolutions = json_encode(['720', '1080'], JSON_UNESCAPED_UNICODE);

        // محصولات قدیمی هم از همان گزینه‌های جدید استفاده می‌کنند؛ مدیر می‌تواند
        // بعداً هر گزینه‌ای را از صفحه ثبت محصول خاموش کند.
        DB::table('products')->update([
            'allowed_aspect_ratios' => $allRatios,
            'allowed_resolutions' => $allResolutions,
            'aspect_ratio' => '3:4',
            'resolution' => '720',
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'allowed_resolutions')) {
                $table->dropColumn('allowed_resolutions');
            }
        });
    }
};
