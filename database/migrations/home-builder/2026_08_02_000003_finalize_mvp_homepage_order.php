<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** ترتیب نهایی: ویدیوی داستانی بلافاصله بعد از ترندها و نمونه‌های مخفی پس از سکشن‌های زنده. */
return new class extends Migration
{
    public function up(): void
    {
        $published = DB::table('home_sections')->where('page_key', 'app_home')->where('status', 'published')
            ->orderBy('position')->orderBy('id')->get(['id', 'layout']);

        $video = $published->firstWhere('layout', 'video_spotlight');
        $ordered = $published->reject(fn ($section) => $section->layout === 'video_spotlight')->values();
        $trendIndex = $ordered->search(fn ($section) => $section->layout === 'peek');
        if ($video && $trendIndex !== false) $ordered->splice($trendIndex + 1, 0, [$video]);

        foreach ($ordered as $index => $section) {
            DB::table('home_sections')->where('id', $section->id)->update(['position' => $index + 1]);
        }

        $offset = $ordered->count();
        DB::table('home_sections')->where('page_key', 'app_home')->where('status', '!=', 'published')
            ->orderBy('position')->orderBy('id')->pluck('id')->each(
                fn (int $id, int $index) => DB::table('home_sections')->where('id', $id)->update(['position' => $offset + $index + 1])
            );
    }

    public function down(): void
    {
        // ترتیب قبلی داده‌های مدیریتی قابل بازسازی قطعی نیست.
    }
};
