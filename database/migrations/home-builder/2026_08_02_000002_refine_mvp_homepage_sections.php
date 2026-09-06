<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** اصلاح دوم چیدمان MVP: بنتو آینه‌ای، خانواده، ویدیوی داستانی و حذف پروفایل انتهایی. */
return new class extends Migration
{
    private const MVP_MARKER = 'hb_mvp_home_v1';
    private const REVISION_MARKER = 'hb_mvp_revision_v2';

    public function up(): void
    {
        if (DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::REVISION_MARKER . '%')->exists()) return;

        DB::table('home_sections')->where('page_key', 'app_home')
            ->where('layout', 'scroll_stack')->where('settings', 'like', '%' . self::MVP_MARKER . '%')->delete();

        DB::table('home_sections')->where('page_key', 'app_home')->where('layout', 'bento')
            ->where('settings', 'like', '%' . self::MVP_MARKER . '%')
            ->update(['subtitle_fa' => 'چیدمان بنتو آینه‌ای؛ یک قاب بزرگ و دو قاب مکمل در هر ردیف']);

        $this->normalizePositions();
        $this->insertAt(2, [
            'type' => 'product_slider', 'layout' => 'video_spotlight',
            'title_fa' => 'قصه‌ها را به حرکت درآور',
            'subtitle_fa' => 'یک روایت تصویری متفاوت؛ ویدیوهای منتخب در قاب اصلی و مکمل',
            'settings' => $this->productSettings('video_spotlight', [3, 7, 10], '/app/products?video=1', ['hover_effect' => 'neon_glow']),
        ]);

        $tabsPosition = (int) DB::table('home_sections')->where('page_key', 'app_home')->where('layout', 'tabs')
            ->where('settings', 'like', '%' . self::MVP_MARKER . '%')->value('position');
        $this->insertAt(max(1, $tabsPosition), [
            'type' => 'product_grid', 'layout' => 'family_duo',
            'title_fa' => 'خانه، جایی که عشق می‌ماند',
            'subtitle_fa' => 'پرفروش‌ترین انتخاب‌های خانوادگی برای قاب‌های مادر، پدر و فرزند',
            'settings' => $this->productSettings('family_duo', [4, 1], '/category/portrait/family', ['hover_effect' => 'neon_glow']),
        ]);

        $this->normalizePositions();
    }

    private function productSettings(string $layout, array $ids, string $viewAllLink, array $extra = []): string
    {
        $products = DB::table('products')->whereIn('id', $ids)->get(['id', 'name_fa'])->keyBy('id');
        $picked = collect($ids)->map(fn (int $id) => ['id' => $id, 'name' => $products[$id]->name_fa ?? ('محصول ' . $id)])->all();

        return json_encode(array_merge([
            '_sample' => self::REVISION_MARKER . ':' . $layout,
            'source' => 'manual', 'product_ids' => $picked, 'limit' => count($ids), 'sort' => 'latest',
            'show_credit' => true, 'show_title' => true, 'show_category' => true,
            'show_view_all' => true, 'view_all_link_mode' => 'manual', 'view_all_link' => $viewAllLink,
        ], $extra), JSON_UNESCAPED_UNICODE);
    }

    private function insertAt(int $position, array $section): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->where('position', '>=', $position)->increment('position');
        $now = now();
        DB::table('home_sections')->insert(array_merge($section, [
            'page_key' => 'app_home', 'status' => 'published', 'position' => $position,
            'responsive' => json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]),
            'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]));
    }

    private function normalizePositions(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->orderBy('position')->orderBy('id')->pluck('id')
            ->each(fn (int $id, int $index) => DB::table('home_sections')->where('id', $id)->update(['position' => $index + 1]));
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::REVISION_MARKER . '%')->delete();
        $this->normalizePositions();
    }
};
