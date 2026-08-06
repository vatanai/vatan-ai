<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** نمونه‌های بررسی‌شده را به انتهای صفحه Home منتقل می‌کند. */
return new class extends Migration
{
    public function up(): void
    {
        $reviewedLayouts = [
            ['product_slider', 'intro'],
            ['product_slider', 'intro_dual'],
            ['product_slider', 'large'],
            ['category_slider', 'tabs'],
            ['product_grid', 'hover_showcase'],
            ['product_slider', 'minimal'],
        ];

        $sections = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'type', 'layout']);

        $reviewedIds = collect($reviewedLayouts)->flatMap(function (array $layout) use ($sections) {
            return $sections
                ->where('type', $layout[0])
                ->where('layout', $layout[1])
                ->pluck('id');
        });

        $orderedIds = $sections->pluck('id')->diff($reviewedIds)->values()->concat($reviewedIds)->values();

        foreach ($orderedIds as $index => $id) {
            DB::table('home_sections')->where('id', $id)->update(['position' => $index + 1]);
        }
    }

    public function down(): void
    {
        // ترتیب قبلی داده‌ها قابل بازسازی قطعی نیست.
    }
};
