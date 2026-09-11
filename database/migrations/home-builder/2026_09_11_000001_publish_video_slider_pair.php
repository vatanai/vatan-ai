<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** فعال‌سازی دو مدل متفاوت نمایش ویدیو در هوم اپ. */
return new class extends Migration
{
    private const MARKER = 'hb_video_pair_v1';

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('home_sections')) {
            return;
        }

        $spotlight = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('type', 'product_slider')
            ->where('layout', 'video_spotlight')
            ->where('title_fa', 'قصه‌ها را به حرکت درآور')
            ->first();

        if (! $spotlight) {
            return;
        }

        $loop = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('type', 'product_slider')
            ->where('layout', 'video_loop')
            ->where('title_fa', 'ویدیو برای لحظه‌های ماندگار')
            ->first();

        $now = now();
        if (! $loop) {
            $loopId = DB::table('home_sections')->insertGetId([
                'page_key' => 'app_home',
                'type' => 'product_slider',
                'layout' => 'video_loop',
                'title_fa' => 'ویدیو برای لحظه‌های ماندگار',
                'subtitle_fa' => 'ویدیوهای منتخب، بی‌صدا و آماده تماشا',
                'settings' => json_encode([
                    '_video_pair' => self::MARKER,
                    'source' => 'video',
                    'limit' => 8,
                    'sort' => 'latest',
                    'show_credit' => true,
                    'show_view_all' => true,
                    'view_all_link_mode' => 'manual',
                    'view_all_link' => '/app/products?video=1',
                    'hover_effect' => 'neon_glow',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'responsive' => json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]),
                'status' => 'published',
                'position' => (int) $spotlight->position + 1,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $loop = DB::table('home_sections')->where('id', $loopId)->first();
        } else {
            $settings = json_decode((string) $loop->settings, true);
            $settings = is_array($settings) ? $settings : [];
            if (($settings['_video_pair'] ?? null) === self::MARKER) {
                return;
            }

            $settings['source'] = 'video';
            $settings['limit'] = 8;
            $settings['sort'] = $settings['sort'] ?? 'latest';
            $settings['show_view_all'] = true;
            $settings['view_all_link_mode'] = 'manual';
            $settings['view_all_link'] = '/app/products?video=1';
            $settings['_video_pair'] = self::MARKER;

            DB::table('home_sections')->where('id', $loop->id)->update([
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'published',
                'updated_at' => $now,
            ]);
        }

        $published = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('status', 'published')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        $ordered = $published->reject(fn (object $section): bool => (int) $section->id === (int) $loop->id)->values();
        $spotlightIndex = $ordered->search(fn (object $section): bool => (int) $section->id === (int) $spotlight->id);
        if ($spotlightIndex === false) {
            return;
        }

        $ordered->splice($spotlightIndex + 1, 0, [$loop]);
        foreach ($ordered as $index => $section) {
            DB::table('home_sections')->where('id', $section->id)->update([
                'position' => $index + 1,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // تغییرات محتوایی صفحه هوم عمداً حذف نمی‌شوند تا تنظیمات مدیر از بین نرود.
    }
};
