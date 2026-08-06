<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** نمونه نئونی تأییدشده را به انتهای صفحه Home منتقل می‌کند. */
return new class extends Migration
{
    public function up(): void
    {
        $sections = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'type', 'layout']);

        $neonIds = $sections
            ->where('type', 'product_slider')
            ->where('layout', 'neon')
            ->pluck('id');

        $orderedIds = $sections->pluck('id')->diff($neonIds)->values()->concat($neonIds)->values();

        foreach ($orderedIds as $index => $id) {
            DB::table('home_sections')->where('id', $id)->update(['position' => $index + 1]);
        }
    }

    public function down(): void
    {
        // ترتیب قبلی داده‌ها قابل بازسازی قطعی نیست.
    }
};
