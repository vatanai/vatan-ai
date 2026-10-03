<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * چیدمان «ویترین» اپ هوم (تایید کاربر — ۱۱ مهر ۱۴۰۵).
 *
 * - سکشن‌های منتشرشده‌ی فعلی حذف نمی‌شوند؛ فقط «مخفی» می‌شوند و وضعیت/ترتیب قبلی‌شان
 *   در settings._vitrine_prev ذخیره می‌شود تا down() دقیقاً همان صفحه‌ی قبلی را برگرداند.
 * - سکشن‌های جدید با نشان settings._vitrine = v1 ساخته می‌شوند (idempotent).
 * - شناسه‌ی دسته‌ها و محصولات با نام/اسلاگ پیدا می‌شود، چون دیتابیس لوکال و آنلاین id یکسان ندارند؛
 *   هر موردی که پیدا نشود بی‌صدا کنار گذاشته می‌شود و سکشن خالی هم در فرانت نمایش داده نمی‌شود.
 * - اگر کمتر از ۴ محصول ویدیویی فعال باشد، سکشن ویدیوی داستانی فعلی (video_spotlight) منتشر می‌ماند
 *   و «ویدیوهای عمودی» ویترین به‌صورت پیش‌نویس ساخته می‌شود تا بعداً از داشبورد منتشر شود.
 */
return new class extends Migration
{
    private const MARKER = 'v1';
    private const PAGE = 'app_home';

    public function up(): void
    {
        $schema = DB::getSchemaBuilder();
        if (! $schema->hasTable('home_sections') || ! $schema->hasTable('categories') || ! $schema->hasTable('products')) {
            return;
        }

        // فیلتر JSON در PHP انجام می‌شود (ستون JSON در MySQL با فاصله سریال می‌شود و LIKE قابل اتکا نیست).
        $alreadyApplied = $this->vitrineRows()->isNotEmpty();
        if ($alreadyApplied) {
            return;
        }

        $videoCount = DB::table('products')->where('status', 'active')->whereIn('media_type', ['video', 'both'])->count();
        $keepSpotlight = $videoCount < 4
            ? DB::table('home_sections')->where('page_key', self::PAGE)->where('status', 'published')
                ->where('type', 'product_slider')->where('layout', 'video_spotlight')->whereNull('deleted_at')
                ->orderBy('position')->first()
            : null;

        $now = now();

        DB::transaction(function () use ($videoCount, $keepSpotlight, $now) {
            // ۱) پنهان‌کردن چیدمان فعلی با حفظ وضعیت قبلی
            $current = DB::table('home_sections')->where('page_key', self::PAGE)->whereNull('deleted_at')->get();
            foreach ($current as $row) {
                $settings = $this->decode($row->settings);
                $settings['_vitrine_prev'] = ['status' => $row->status, 'position' => (int) $row->position];
                $isKept = $keepSpotlight && (int) $row->id === (int) $keepSpotlight->id;
                DB::table('home_sections')->where('id', $row->id)->update([
                    'settings' => $this->encode($settings),
                    'status' => $isKept ? 'published' : ($row->status === 'published' ? 'hidden' : $row->status),
                    'position' => 1000 + (int) $row->position,
                    'updated_at' => $now,
                ]);
            }

            // ۲) ساخت چیدمان ویترین
            $position = 1;
            foreach ($this->blueprint($videoCount) as $section) {
                if ($section === 'keep_spotlight') {
                    if ($keepSpotlight) {
                        DB::table('home_sections')->where('id', $keepSpotlight->id)->update([
                            'position' => $position++,
                            'title_fa' => 'ویدیو برای ریلز و استوری',
                            'updated_at' => $now,
                        ]);
                    }
                    continue;
                }

                $status = $section['status'] ?? 'published';
                DB::table('home_sections')->insert([
                    'page_key' => self::PAGE,
                    'type' => $section['type'],
                    'layout' => $section['layout'] ?? 'default',
                    'title_fa' => $section['title'] ?? null,
                    'subtitle_fa' => $section['subtitle'] ?? null,
                    'settings' => $this->encode(array_merge($section['settings'], ['_vitrine' => self::MARKER])),
                    'responsive' => json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]),
                    'status' => $status,
                    'position' => $status === 'published' ? $position++ : 900 + $position,
                    'published_at' => $status === 'published' ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('home_sections')) {
            return;
        }

        DB::transaction(function () {
            $created = $this->vitrineRows()->pluck('id');
            if ($created->isNotEmpty()) {
                DB::table('home_sections')->whereIn('id', $created)->delete();
            }

            $rows = DB::table('home_sections')->where('page_key', self::PAGE)->get()
                ->filter(fn ($row) => array_key_exists('_vitrine_prev', $this->decode($row->settings)));
            foreach ($rows as $row) {
                $settings = $this->decode($row->settings);
                $prev = $settings['_vitrine_prev'] ?? null;
                unset($settings['_vitrine_prev']);
                DB::table('home_sections')->where('id', $row->id)->update(array_filter([
                    'settings' => $this->encode($settings),
                    'status' => $prev['status'] ?? null,
                    'position' => isset($prev['position']) ? (int) $prev['position'] : null,
                    'title_fa' => ($row->type === 'product_slider' && $row->layout === 'video_spotlight' && $row->title_fa === 'ویدیو برای ریلز و استوری')
                        ? 'قصه‌ها را به حرکت درآور' : null,
                    'updated_at' => now(),
                ], fn ($v) => $v !== null));
            }
        });
    }

    private function vitrineRows(): Collection
    {
        return DB::table('home_sections')->where('page_key', self::PAGE)->get(['id', 'settings'])
            ->filter(fn ($row) => ($this->decode($row->settings)['_vitrine'] ?? null) === self::MARKER)
            ->values();
    }

    /** ترتیب نهایی تأییدشده‌ی صفحه. */
    private function blueprint(int $videoCount): array
    {
        $cat = fn (array $names) => $this->categoryIds($names);
        $products = fn (array $slugs) => $this->productRefs($slugs);

        $heroProducts = $products(['cinematic-commercial-for-luxury-sneakers', 'luxury-perfume-editorial-poster', 'luxury-bag-showcase']);
        $businessTabs = $this->businessTabIds();
        $productShotPath = $this->categoryPath('عکس محصول');
        $businessPath = $this->categoryPath('محصولات و کسب‌وکارها') ?? $this->categoryPath('کسب‌وکار و برند');
        $portraitPath = $this->categoryPath('پرتره');

        $sections = [
            [
                'type' => 'vt_hero',
                'settings' => count($heroProducts) >= 2
                    ? ['placement' => 'top', 'source' => 'manual', 'product_ids' => $heroProducts, 'limit' => 3]
                    : ['placement' => 'top', 'source' => 'featured', 'limit' => 3],
            ],
            [
                'type' => 'vt_tools',
                'title' => 'با وطن چی می‌تونی بسازی؟',
                'subtitle' => 'همه ابزارها یک‌جا؛ هر کدوم رو بزنی مستقیم می‌ری سراغ قالب‌هاش',
                'settings' => [
                    'category_ids' => $cat(['عکس محصول', 'ویدیوهای آماده هوش مصنوعی', 'محصول تبلیغاتی', 'استوری', 'کاربردی', 'پرتره', 'کاراکتر', 'تولد']),
                    'tile_overrides' => implode("\n", [
                        'عکس محصول | عکس محصول | کسب‌وکار',
                        'ویدیوهای آماده هوش مصنوعی | ویدیو هوش مصنوعی | جدید',
                        'محصول تبلیغاتی | پوستر تبلیغاتی',
                        'استوری | استوری و پست',
                        'کاربردی | ابزار کاربردی | پرطرفدار',
                        'پرتره | پرتره حرفه‌ای',
                        'کاراکتر | کاراکتر و شخصیت',
                        'تولد | تولد و مناسبت',
                    ]),
                    'hide_quick_chips' => true,
                ],
            ],
            [
                'type' => 'vt_row',
                'title' => 'ترندهای امروز',
                'subtitle' => 'پرطرفدارترین‌های این هفته؛ از عکس محصول تا پرتره',
                'settings' => ['source' => 'trending', 'limit' => 12, 'sort' => 'latest', 'avoid_duplicates' => true, 'show_view_all' => true, 'view_all_link_mode' => 'auto'],
            ],
            [
                'type' => 'vt_tabs',
                'title' => 'عکس فروش برای صنف خودت',
                'subtitle' => 'صنفت رو انتخاب کن؛ قالب‌های همون کسب‌وکار رو ببین',
                'settings' => array_filter([
                    'category_ids' => $businessTabs,
                    'products_per_tab' => 10,
                    'min_products_per_tab' => 3,
                    'show_all_tab' => true,
                    'avoid_duplicates' => false,
                    'show_view_all' => true,
                    'view_all_link_mode' => $businessPath ? 'manual' : 'auto',
                    'view_all_link' => $businessPath,
                ], fn ($v) => $v !== null),
            ],
            [
                'type' => 'vt_before_after',
                'title' => 'از عکس ساده تا عکس فروش',
                'subtitle' => 'بکش و تفاوت رو ببین',
                'settings' => ['source' => 'with_before', 'limit' => 3, 'cta_label' => 'بساز'],
            ],
        ];

        $videoRow = [
            'type' => 'vt_video_row',
            'title' => 'ویدیو برای ریلز و استوری',
            'subtitle' => 'روی کارت برو تا پیش‌نمایش پخش بشه',
            'settings' => ['source' => 'video', 'limit' => 10, 'min_items' => 3, 'show_view_all' => true, 'view_all_link_mode' => 'manual', 'view_all_link' => '/app/products?video=1'],
        ];
        if ($videoCount >= 4) {
            $sections[] = $videoRow;
        } else {
            $sections[] = 'keep_spotlight';
        }

        $sections = array_merge($sections, [
            [
                'type' => 'vt_cta_banner',
                'settings' => [
                    'kicker' => 'ویژه فروشگاه‌ها و برندها',
                    'heading' => 'عکس فروش حرفه‌ای، بدون عکاس و استودیو',
                    'body' => 'عکس محصولت رو بفرست و از بین قالب‌های آماده‌ی صنف خودت انتخاب کن؛ خروجی آماده‌ی اینستاگرام و فروشگاه تحویل بگیر.',
                    'cta_label' => 'شروع عکس محصول',
                    'cta_link' => $productShotPath ?? '/app/products',
                    'cta2_label' => 'همه قالب‌های کسب‌وکار',
                    'cta2_link' => $businessPath ?? '/app/products',
                    'product_ids' => $products(['cinematic-commercial-for-luxury-sneakers', 'elegant-gems', 'store-products']),
                ],
            ],
            [
                'type' => 'vt_row',
                'layout' => 'marquee',
                'title' => 'استوری و پست اینستاگرام',
                'subtitle' => 'قالب‌های آماده برای پیج برند یا پیج شخصی',
                'settings' => array_filter([
                    'source' => $cat(['استوری']) ? 'category' : 'latest',
                    'category_id' => $cat(['استوری'])[0] ?? null,
                    'limit' => 14,
                    'sort' => 'latest',
                    'avoid_duplicates' => true,
                    'show_view_all' => true,
                    'view_all_link_mode' => 'auto',
                ], fn ($v) => $v !== null),
            ],
            [
                'type' => 'vt_occasions',
                'title' => 'مناسبت‌ها و لحظه‌های خاص',
                'subtitle' => 'قالب آماده برای روزهای مهم',
                'settings' => ['category_ids' => $cat(['تولد', 'ازدواج', 'عاشقانه', 'کودک', 'خانوادگی', 'پاییز', 'کریسمس']), 'min_products' => 2],
            ],
            [
                'type' => 'vt_tabs',
                'title' => 'پرتره و عکس پروفایل',
                'subtitle' => 'پرتره حرفه‌ای، سینمایی و استایل شخصی',
                'settings' => array_filter([
                    'category_ids' => $cat(['استایل مردانه', 'استایل زنانه', 'تصویر پروفایل', 'سلفی', 'کودک', 'خانوادگی']),
                    'products_per_tab' => 10,
                    'min_products_per_tab' => 3,
                    'show_all_tab' => true,
                    'avoid_duplicates' => false,
                    'show_view_all' => true,
                    'view_all_link_mode' => $portraitPath ? 'manual' : 'auto',
                    'view_all_link' => $portraitPath,
                ], fn ($v) => $v !== null),
            ],
            [
                'type' => 'vt_masonry',
                'title' => 'همه ایده‌ها',
                'subtitle' => 'بچرخ و قالب موردعلاقه‌ات رو پیدا کن',
                'settings' => ['source' => 'latest', 'sort' => 'random', 'limit' => 30, 'avoid_duplicates' => true, 'all_button_label' => 'مشاهده همه :count محصول', 'all_button_link' => '/app/products'],
            ],
        ]);

        if ($videoCount < 4) {
            $sections[] = array_merge($videoRow, ['status' => 'draft']);
        }

        return $sections;
    }

    /** شناسه‌ی دسته‌های فعال به ترتیب نام‌های داده‌شده (نام‌های پیدانشده حذف می‌شوند). */
    private function categoryIds(array $names): array
    {
        $rows = DB::table('categories')->where('is_active', true)->whereIn('name_fa', $names)->get(['id', 'name_fa']);

        return collect($names)
            ->map(fn (string $name) => optional($rows->firstWhere('name_fa', $name))->id)
            ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** مسیر نسبی صفحه‌ی یک دسته برای لینک‌ها (بدون وابستگی به دامنه‌ی محیط اجرا). */
    private function categoryPath(string $name): ?string
    {
        $id = $this->categoryIds([$name])[0] ?? null;
        $category = $id ? \App\Models\Category::query()->find($id) : null;
        if (! $category) {
            return null;
        }
        try {
            $url = $category->url();
        } catch (\Throwable) {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH);

        return $path ? $path . (($q = parse_url($url, PHP_URL_QUERY)) ? '?' . $q : '') : null;
    }

    /** زیردسته‌های «محصولات و کسب‌وکارها» که دست‌کم ۳ محصول فعال دارند؛ پرمحصول‌ترها اول. */
    private function businessTabIds(): array
    {
        $root = DB::table('categories')->where('is_active', true)
            ->whereIn('name_fa', ['محصولات و کسب‌وکارها', 'محصولات و کسب و کارها'])->value('id');
        $children = $root
            ? DB::table('categories')->where('is_active', true)->where('parent_id', $root)->pluck('id')
            : collect($this->categoryIds(['فروشگاه کیف و کفش', 'فروشگاه عطر', 'فروشگاه لوازم آرایشی', 'پوشاک و مد', 'فروشگاه ساعت', 'باشگاه ورزشی', 'سالن زیبایی', 'کافی‌شاپ']));

        return $this->rankByProductCount($children, 3)->take(8)->values()->all();
    }

    private function rankByProductCount(Collection $categoryIds, int $min): Collection
    {
        if ($categoryIds->isEmpty()) {
            return collect();
        }
        $all = DB::table('categories')->where('is_active', true)->get(['id', 'parent_id']);
        $hasPivot = DB::getSchemaBuilder()->hasTable('category_product');

        return $categoryIds->mapWithKeys(function ($id) use ($all, $hasPivot) {
            $ids = collect([(int) $id]);
            $frontier = collect([(int) $id]);
            while ($frontier->isNotEmpty()) {
                $children = $all->whereIn('parent_id', $frontier)->pluck('id')->map(fn ($v) => (int) $v)->diff($ids)->values();
                $ids = $ids->concat($children);
                $frontier = $children;
            }
            $query = DB::table('products')->where('status', 'active')
                ->where(function ($q) use ($ids, $hasPivot) {
                    $q->whereIn('category_id', $ids);
                    if ($hasPivot) {
                        $q->orWhereIn('id', DB::table('category_product')->whereIn('category_id', $ids)->select('product_id'));
                    }
                });

            return [(int) $id => $query->count()];
        })->filter(fn (int $count) => $count >= $min)->sortDesc()->keys();
    }

    /** ارجاع محصولات با اسلاگ — همان قالب {id, name} که فرم داشبورد ذخیره می‌کند. */
    private function productRefs(array $slugs): array
    {
        $rows = DB::table('products')->where('status', 'active')->whereIn('slug', $slugs)->get(['id', 'slug', 'name_fa']);

        return collect($slugs)
            ->map(fn (string $slug) => $rows->firstWhere('slug', $slug))
            ->filter()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name_fa])
            ->values()->all();
    }

    private function decode(?string $json): array
    {
        $value = json_decode((string) $json, true);

        return is_array($value) ? $value : [];
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
};
