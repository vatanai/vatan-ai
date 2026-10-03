<?php

/**
 * رجیستری مرکزی فیچر Home Builder.
 * افزودن نوع Section جدید در آینده فقط با اضافه‌کردن یک آیتم به آرایه‌ی «types» زیر
 * + ساخت partial رندر فرانت متناظر انجام می‌شود — هیچ migration یا تغییر کد کنترلر لازم نیست.
 * هر فیلد settings_fields به‌صورت داینامیک در Drawer تنظیمات ادمین (resources/views/admin/home-builder)
 * و در HomeSectionRenderService خوانده می‌شود.
 */

// فیلدهای مشترک منبع محصول — در product_slider / product_grid / collection استفاده می‌شود.
// category_value/subcategory_value برای سازگاری با ستون‌های قدیمی category/subcategory جدول products
// (که هنوز پایه‌ی فیلتر ردیف‌های فعلی صفحه Home هستند)، در کنار category_id (taxonomy جدید).
$productSourceFields = [
    ['key' => 'source', 'label' => 'منبع محصولات', 'type' => 'select', 'options' => [
        'latest' => 'جدیدترین‌ها',
        'trending' => 'ترندها',
        'featured' => 'ویژه',
        'category' => 'دسته‌بندی خاص',
        'video' => 'محتوای ویدیویی',
        'manual' => 'انتخاب دستی محصولات',
    ], 'default' => 'latest'],
    ['key' => 'category_id', 'label' => 'دسته‌بندی (جدید)', 'type' => 'category_select', 'show_if_source' => ['category']],
    ['key' => 'category_value', 'label' => 'دسته‌بندی (کد قدیمی — مثل BUSINESS)', 'type' => 'text', 'show_if_source' => ['category']],
    ['key' => 'subcategory_value', 'label' => 'زیردسته (کد قدیمی — اختیاری)', 'type' => 'text', 'show_if_source' => ['category']],
    ['key' => 'product_ids', 'label' => 'انتخاب محصولات (جستجو و انتخاب چندتایی)', 'type' => 'product_multiselect', 'show_if_source' => ['manual']],
];

$viewAllFields = [
    ['key' => 'show_view_all', 'label' => 'نمایش دکمه «مشاهده همه»', 'type' => 'checkbox', 'default' => true],
    ['key' => 'view_all_link_mode', 'label' => 'نوع لینک «مشاهده همه»', 'type' => 'select', 'options' => [
        'auto' => 'خودکار (بر اساس منبع محصولات)',
        'manual' => 'دستی',
    ], 'default' => 'auto'],
    ['key' => 'view_all_link', 'label' => 'لینک دستی «مشاهده همه»', 'type' => 'text', 'placeholder' => 'مثلاً /products یا https://example.com'],
];

$hoverEffectField = ['key' => 'hover_effect', 'label' => 'حالت هاور کارت‌ها', 'type' => 'select', 'options' => [
    'neon_glow' => 'درخشش نئونی',
    'grayscale_color' => 'سیاه‌وسفید به رنگی',
    'zoom_soft' => 'زوم نرم',
    'lift_shadow' => 'شناور با سایه',
    'overlay_reveal' => 'نمایش اطلاعات',
    'tilt' => 'چرخش سه‌بعدی',
    'shine' => 'عبور نور',
    'blur_focus' => 'فوکوس از محو',
    'border_draw' => 'ترسیم قاب',
    'pulse' => 'ضربان نرم',
    'slide_caption' => 'ورود توضیحات',
    'darken' => 'تاریک سینمایی',
    'saturate' => 'تقویت رنگ',
    'rotate_soft' => 'چرخش نرم',
    'token_bounce' => 'حرکت آیکون توکن',
], 'default' => 'neon_glow'];

$fillEmptySpacesField = [
    'key' => 'fill_empty_spaces',
    'label' => 'پر کردن فضاهای خالی با تکرار محصولات موجود',
    'type' => 'checkbox',
    'default' => false,
];
$fillCategoryTabsSpacesField = [...$fillEmptySpacesField, 'show_if_layout' => ['tabs']];

return [

    'default_page_key' => 'app_home',

    'statuses' => [
        'draft' => 'پیش‌نویس',
        'published' => 'منتشرشده',
        'hidden' => 'مخفی',
    ],

    'product_source_fields' => $productSourceFields,

    'types' => [
        // ══════════ ویترین (سیستم نمایشی یکدست اپ هوم — سبک هیگزفیلد) ══════════
        // همه‌ی انواع vt_* از یک کارت و یک هاور مشترک استفاده می‌کنند (public/css/home-vitrine.css).
        // محصولات تکراری بین سکشن‌های ویترین یک صفحه خودکار حذف می‌شوند (avoid_duplicates).

        'vt_hero' => [
            'label' => 'ویترین · هیرو اسپات‌لایت',
            'description' => 'اسلاید بزرگ با ویدیو/تصویر و دکمه «همین رو بساز»؛ دسکتاپ ۱ بزرگ + ۲ کوچک، موبایل اسلایدی',
            'icon' => 'fa-solid fa-clapperboard',
            'layouts' => [
                'default' => ['label' => 'یک بزرگ + دو کوچک', 'thumb' => 'hero-default.svg'],
            ],
            'settings_fields' => [
                ['key' => 'placement', 'label' => 'جای نمایش', 'type' => 'select', 'options' => [
                    'top' => 'بالای صفحه (قبل از جست‌وجو)',
                    'flow' => 'به ترتیب سکشن‌ها',
                ], 'default' => 'top'],
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد اسلاید', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 5],
                ['key' => 'kicker', 'label' => 'برچسب کوچک اسلاید اول', 'type' => 'text', 'placeholder' => 'مثلاً ویدیو با هوش مصنوعی'],
                ['key' => 'heading', 'label' => 'تیتر اسلاید اول (خالی = نام محصول)', 'type' => 'text'],
                ['key' => 'subheading', 'label' => 'توضیح اسلاید اول', 'type' => 'textarea'],
                ['key' => 'video_url', 'label' => 'ویدیوی اسلاید اول (اختیاری؛ خالی = ویدیوی پیش‌نمایش محصول)', 'type' => 'text', 'placeholder' => '/assets/videos/...mp4'],
                ['key' => 'cta_label', 'label' => 'متن دکمه', 'type' => 'text', 'default' => 'همین رو بساز'],
                ['key' => 'autoplay', 'label' => 'چرخش خودکار اسلایدها در موبایل', 'type' => 'checkbox', 'default' => true],
            ],
        ],

        'vt_tools' => [
            'label' => 'ویترین · نوار ابزارها',
            'description' => 'کاشی‌های مربعی برای دسترسی سریع به گروه‌های محصول؛ هر کاشی = یک دسته‌بندی',
            'icon' => 'fa-solid fa-grip',
            'layouts' => [
                'default' => ['label' => 'کاشی مربعی', 'thumb' => 'grid-4.svg'],
            ],
            'settings_fields' => [
                ['key' => 'category_ids', 'label' => 'دسته‌بندی‌ها (به همین ترتیب؛ تا ۱۰ مورد)', 'type' => 'category_multiselect'],
                ['key' => 'tile_overrides', 'label' => 'عنوان/برچسب سفارشی — هر خط: نام دسته | عنوان نمایشی | برچسب', 'type' => 'textarea', 'placeholder' => "کاربردی | افزایش کیفیت | پرطرفدار"],
                ['key' => 'hide_quick_chips', 'label' => 'مخفی‌کردن چیپ‌های کوچک زیر جست‌وجو', 'type' => 'checkbox', 'default' => true],
            ],
        ],

        'vt_row' => [
            'label' => 'ویترین · ردیف محصول',
            'description' => 'ردیف افقی با کارت استاندارد ویترین (۳:۴) یا حرکت پیوسته',
            'icon' => 'fa-solid fa-film',
            'layouts' => [
                'default' => ['label' => 'ردیف اسکرولی', 'thumb' => 'row-default.svg'],
                'marquee' => ['label' => 'حرکت پیوسته', 'thumb' => 'row-compact.svg'],
            ],
            'settings_fields' => [
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 12, 'min' => 1, 'max' => 24],
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین', 'popular' => 'محبوب‌ترین', 'expensive' => 'گران‌ترین (کردیت)', 'cheap' => 'ارزان‌ترین (کردیت)',
                ], 'default' => 'latest'],
                ['key' => 'avoid_duplicates', 'label' => 'حذف محصولاتی که در سکشن‌های ویترین بالاتر آمده‌اند', 'type' => 'checkbox', 'default' => true],
                ...$viewAllFields,
            ],
        ],

        'vt_tabs' => [
            'label' => 'ویترین · ردیف تب‌دار',
            'description' => 'تب‌های دسته‌بندی (مثل صنف‌ها) + ردیف محصول هر تب',
            'icon' => 'fa-solid fa-table-columns',
            'layouts' => [
                'default' => ['label' => 'تب + ردیف', 'thumb' => 'row-tabs.svg'],
            ],
            'settings_fields' => [
                ['key' => 'category_ids', 'label' => 'تب‌ها (دسته‌بندی‌ها؛ به همین ترتیب)', 'type' => 'category_multiselect'],
                ['key' => 'tab_overrides', 'label' => 'نام سفارشی تب — هر خط: نام دسته | نام تب', 'type' => 'textarea'],
                ['key' => 'products_per_tab', 'label' => 'تعداد محصول هر تب', 'type' => 'number', 'default' => 10, 'min' => 2, 'max' => 20],
                ['key' => 'min_products_per_tab', 'label' => 'تب‌های با محصول کمتر از این عدد مخفی شوند', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10],
                ['key' => 'show_all_tab', 'label' => 'نمایش تب «همه»', 'type' => 'checkbox', 'default' => true],
                ['key' => 'avoid_duplicates', 'label' => 'حذف محصولاتی که در سکشن‌های ویترین بالاتر آمده‌اند', 'type' => 'checkbox', 'default' => false],
                ...$viewAllFields,
            ],
        ],

        'vt_before_after' => [
            'label' => 'ویترین · قبل / بعد',
            'description' => 'کارت مقایسه‌ی کشیدنی؛ «قبل» از تصاویر قبل محصول و «بعد» از تصویر اصلی محصول',
            'icon' => 'fa-solid fa-left-right',
            'layouts' => [
                'default' => ['label' => 'سه کارت مقایسه', 'thumb' => 'grid-3.svg'],
            ],
            'settings_fields' => [
                ['key' => 'source', 'label' => 'منبع محصولات', 'type' => 'select', 'options' => [
                    'with_before' => 'خودکار: محصولاتی که تصویر «قبل» دارند',
                    'manual' => 'انتخاب دستی محصولات',
                ], 'default' => 'with_before'],
                ['key' => 'product_ids', 'label' => 'انتخاب محصولات (فقط محصولات دارای تصویر «قبل» نمایش داده می‌شوند)', 'type' => 'product_multiselect', 'show_if_source' => ['manual']],
                ['key' => 'limit', 'label' => 'تعداد کارت', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 9],
                ['key' => 'cta_label', 'label' => 'متن دکمه', 'type' => 'text', 'default' => 'بساز'],
            ],
        ],

        'vt_video_row' => [
            'label' => 'ویترین · ویدیوهای عمودی',
            'description' => 'کارت‌های ۹:۱۶ که با هاور (دسکتاپ) یا رسیدن به صفحه (موبایل) پخش می‌شوند',
            'icon' => 'fa-solid fa-circle-play',
            'layouts' => [
                'default' => ['label' => 'ردیف ۹:۱۶', 'thumb' => 'row-large.svg'],
            ],
            'settings_fields' => [
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 10, 'min' => 1, 'max' => 24],
                ['key' => 'min_items', 'label' => 'اگر ویدیو کمتر از این عدد بود سکشن نمایش داده نشود', 'type' => 'number', 'default' => 3, 'min' => 1, 'max' => 10],
                ...$viewAllFields,
            ],
        ],

        'vt_cta_banner' => [
            'label' => 'ویترین · بنر دعوت',
            'description' => 'بنر دو ستونه با متن، دو دکمه و سه تصویر از محصولات انتخابی',
            'icon' => 'fa-solid fa-bullhorn',
            'layouts' => [
                'default' => ['label' => 'متن + کلاژ', 'thumb' => 'banner-rounded.svg'],
            ],
            'settings_fields' => [
                ['key' => 'kicker', 'label' => 'برچسب کوچک', 'type' => 'text'],
                ['key' => 'heading', 'label' => 'تیتر', 'type' => 'text'],
                ['key' => 'body', 'label' => 'متن', 'type' => 'textarea'],
                ['key' => 'cta_label', 'label' => 'متن دکمه اصلی', 'type' => 'text'],
                ['key' => 'cta_link', 'label' => 'لینک دکمه اصلی', 'type' => 'text'],
                ['key' => 'cta2_label', 'label' => 'متن دکمه دوم (اختیاری)', 'type' => 'text'],
                ['key' => 'cta2_link', 'label' => 'لینک دکمه دوم', 'type' => 'text'],
                ['key' => 'product_ids', 'label' => 'سه محصول برای کلاژ تصویر', 'type' => 'product_multiselect'],
            ],
        ],

        'vt_occasions' => [
            'label' => 'ویترین · کارت دسته‌ها (مناسبت‌ها)',
            'description' => 'کارت‌های تصویری دسته‌بندی با تعداد قالب',
            'icon' => 'fa-solid fa-gift',
            'layouts' => [
                'default' => ['label' => 'کارت دسته', 'thumb' => 'grid-4.svg'],
            ],
            'settings_fields' => [
                ['key' => 'category_ids', 'label' => 'دسته‌بندی‌ها (به همین ترتیب)', 'type' => 'category_multiselect'],
                ['key' => 'tile_overrides', 'label' => 'عنوان سفارشی — هر خط: نام دسته | عنوان نمایشی', 'type' => 'textarea'],
                ['key' => 'min_products', 'label' => 'دسته‌های با محصول کمتر از این عدد مخفی شوند', 'type' => 'number', 'default' => 2, 'min' => 1, 'max' => 10],
            ],
        ],

        'vt_masonry' => [
            'label' => 'ویترین · اکسپلور موزاییکی',
            'description' => 'شبکه‌ی آجری از همه محصولات + دکمه «مشاهده همه»',
            'icon' => 'fa-solid fa-border-all',
            'layouts' => [
                'default' => ['label' => 'موزاییک', 'thumb' => 'grid-bento.svg'],
            ],
            'settings_fields' => [
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 30, 'min' => 6, 'max' => 48],
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین', 'popular' => 'محبوب‌ترین', 'random' => 'تصادفی (هر بار متفاوت)',
                ], 'default' => 'random'],
                ['key' => 'avoid_duplicates', 'label' => 'حذف محصولاتی که در سکشن‌های ویترین بالاتر آمده‌اند', 'type' => 'checkbox', 'default' => true],
                ['key' => 'all_button_label', 'label' => 'متن دکمه پایین (‎:count‎ = تعداد کل محصولات)', 'type' => 'text', 'default' => 'مشاهده همه :count محصول'],
                ['key' => 'all_button_link', 'label' => 'لینک دکمه پایین', 'type' => 'text', 'default' => '/app/products'],
            ],
        ],


        'hero' => [
            'label' => 'هدر / بنر اصلی',
            'description' => 'بنر تمام‌عرض با تصویر، عنوان و دکمه دعوت به اقدام',
            'icon' => 'fa-solid fa-panorama',
            'layouts' => [
                'default' => ['label' => 'تصویر + متن', 'thumb' => 'hero-default.svg'],
                'centered' => ['label' => 'وسط‌چین', 'thumb' => 'hero-centered.svg'],
            ],
            'settings_fields' => [
                ['key' => 'heading', 'label' => 'عنوان', 'type' => 'text'],
                ['key' => 'subheading', 'label' => 'زیرعنوان', 'type' => 'textarea'],
                ['key' => 'image', 'label' => 'تصویر پس‌زمینه', 'type' => 'image'],
                ['key' => 'cta_label', 'label' => 'متن دکمه', 'type' => 'text'],
                ['key' => 'cta_link', 'label' => 'لینک دکمه', 'type' => 'text'],
            ],
        ],

        'product_slider' => [
            'label' => 'اسلایدر محصولات',
            'description' => 'ردیف افقی اسکرول‌شونده از کارت‌های محصول',
            'icon' => 'fa-solid fa-images',
            'layouts' => [
                'default' => ['label' => 'کارت متوسط', 'thumb' => 'row-default.svg'],
                'compact' => ['label' => 'کارت فشرده', 'thumb' => 'row-compact.svg'],
                'peek' => ['label' => 'اسلایدر لبه‌نما (هاور لیمویی)', 'thumb' => 'row-peek.svg'],
                'intro' => ['label' => 'اسلایدر با کارت معرفی', 'thumb' => 'row-intro.svg'],
                'intro_dual' => ['label' => 'کارت معرفی + دو ردیف نئونی', 'thumb' => 'row-intro.svg'],
                'large' => ['label' => 'کارت بزرگ', 'thumb' => 'row-large.svg'],
                'glass' => ['label' => 'کارت شیشه‌ای', 'thumb' => 'row-glass.svg'],
                'neon' => ['label' => 'قاب نئونی', 'thumb' => 'row-neon.svg'],
                'cinema' => ['label' => 'سینمایی و عریض', 'thumb' => 'row-large.svg'],
                'minimal' => ['label' => 'مینیمال روشن', 'thumb' => 'row-compact.svg'],
                'motion_token' => ['label' => 'متحرک: توکن جهنده', 'thumb' => 'row-neon.svg'],
                'motion_float' => ['label' => 'متحرک: کارت‌های شناور', 'thumb' => 'row-glass.svg'],
                'motion_shimmer' => ['label' => 'متحرک: موج نور', 'thumb' => 'row-neon.svg'],
                'motion_orbit' => ['label' => 'متحرک: مدار آیکون', 'thumb' => 'row-large.svg'],
                'motion_wave' => ['label' => 'متحرک: موج کارت‌ها', 'thumb' => 'row-default.svg'],
                'video_loop' => ['label' => 'ویدیوی حلقه‌ای خودکار', 'thumb' => 'row-large.svg'],
                'video_spotlight' => ['label' => 'ویدیوی ویژه داستانی', 'thumb' => 'row-large.svg'],
                'scroll_vertical' => ['label' => 'اسکرول عمودی', 'thumb' => 'row-default.svg'],
                'scroll_marquee' => ['label' => 'اسکرول پیوسته', 'thumb' => 'row-compact.svg'],
                'scroll_stack' => ['label' => 'اسکرول کارت پشته‌ای', 'thumb' => 'row-large.svg'],
                'scroll_wheel' => ['label' => 'اسکرول چرخ‌وفلکی', 'thumb' => 'row-peek.svg'],
            ],
            'settings_fields' => [
                ['key' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'زیرعنوان', 'type' => 'text'],
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 8, 'min' => 1, 'max' => 24],
                $fillEmptySpacesField,
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین',
                    'popular' => 'محبوب‌ترین',
                    'expensive' => 'گران‌ترین (کردیت)',
                    'cheap' => 'ارزان‌ترین (کردیت)',
                ], 'default' => 'latest'],
                ['key' => 'display_mode', 'label' => 'نوع چیدمان', 'type' => 'select', 'options' => [
                    'scroll' => 'ردیفی (اسکرول افقی)',
                    'grid' => 'گرید (ستون و ردیف)',
                ], 'default' => 'scroll'],
                ['key' => 'grid_cols', 'label' => 'تعداد ستون در حالت گرید', 'type' => 'select', 'options' => [
                    '2' => '۲ ستون',
                    '3' => '۳ ستون',
                    '4' => '۴ ستون',
                ], 'default' => '3'],
                ['key' => 'show_credit', 'label' => 'نمایش کردیت زیر کارت', 'type' => 'checkbox', 'default' => true],
                $hoverEffectField,
                ['key' => 'show_title', 'label' => 'نمایش عنوان محصول', 'type' => 'checkbox', 'default' => true, 'show_if_layout' => ['intro', 'intro_dual', 'large']],
                ['key' => 'show_category', 'label' => 'نمایش دسته‌بندی محصول', 'type' => 'checkbox', 'default' => true, 'show_if_layout' => ['intro', 'intro_dual', 'large']],
                ...$viewAllFields,
                ['key' => 'card_aspect_ratio', 'label' => 'نسبت تصویر کارت محصول', 'type' => 'select', 'options' => [
                    '1:1' => 'مربع ۱:۱', '4:5' => 'عمودی ۴:۵', '3:4' => 'عمودی ۳:۴',
                    '9:16' => 'استوری ۹:۱۶', '2:3' => 'عمودی ۲:۳',
                ], 'default' => '4:5', 'show_if_layout' => ['intro']],
                ['key' => 'intro_scroll_mode', 'label' => 'رفتار کارت معرفی هنگام اسکرول', 'type' => 'select', 'options' => [
                    'fixed' => 'ثابت بماند و محصولات از زیر آن رد شوند',
                    'together' => 'همراه محصولات اسکرول شود',
                ], 'default' => 'together', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_badge', 'label' => 'برچسب بالای کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_heading', 'label' => 'عنوان کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_heading_accent', 'label' => 'بخش رنگی عنوان', 'type' => 'text', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_desc', 'label' => 'توضیح کارت معرفی', 'type' => 'textarea', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_steps', 'label' => 'مراحل (هر خط = یک مرحله)', 'type' => 'textarea', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_note', 'label' => 'یادداشت کوچک', 'type' => 'text', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_cta_label', 'label' => 'متن دکمه کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'intro_cta_link', 'label' => 'لینک دکمه کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro', 'intro_dual']],
                ['key' => 'large_show_status_badge', 'label' => 'نمایش نشان وضعیت روی کارت بزرگ', 'type' => 'checkbox', 'default' => true, 'show_if_layout' => ['large']],
                ['key' => 'large_show_ribbon', 'label' => 'نمایش روبان روی کارت بزرگ', 'type' => 'checkbox', 'default' => false, 'show_if_layout' => ['large']],
                ['key' => 'large_ribbon_text', 'label' => 'متن روبان کارت بزرگ', 'type' => 'text', 'placeholder' => 'مثلاً پیشنهاد ویژه', 'show_if_layout' => ['large'], 'show_if_setting' => ['key' => 'large_show_ribbon', 'values' => ['true']]],
            ],
        ],

        'product_grid' => [
            'label' => 'گرید محصولات',
            'description' => 'نمایش شبکه‌ای (چند ستونه) محصولات',
            'icon' => 'fa-solid fa-table-cells-large',
            'layouts' => [
                'default' => ['label' => 'سه ستونه', 'thumb' => 'grid-3.svg'],
                'two_col' => ['label' => 'دو ستونه', 'thumb' => 'grid-2.svg'],
                'four_col' => ['label' => 'چهار ستونه', 'thumb' => 'grid-4.svg'],
                'bento' => ['label' => 'بنتو آینه‌ای', 'thumb' => 'grid-bento.svg'],
                'family_duo' => ['label' => 'دو قاب احساسی خانواده', 'thumb' => 'grid-2.svg'],
                'editorial' => ['label' => 'ادیتوریال ویژه', 'thumb' => 'grid-bento.svg'],
                'hover_showcase' => ['label' => 'آزمایشگاه هاور کارت‌ها', 'thumb' => 'grid-4.svg'],
                'hover_library' => ['label' => 'مدل‌های هاور (۱۵ نمونه)', 'thumb' => 'grid-4.svg'],
            ],
            'settings_fields' => [
                ['key' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'زیرعنوان', 'type' => 'text'],
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 8, 'min' => 1, 'max' => 24],
                $fillEmptySpacesField,
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین',
                    'popular' => 'محبوب‌ترین',
                    'expensive' => 'گران‌ترین (کردیت)',
                    'cheap' => 'ارزان‌ترین (کردیت)',
                ], 'default' => 'latest'],
                ['key' => 'show_credit', 'label' => 'نمایش کردیت زیر کارت', 'type' => 'checkbox', 'default' => true],
                $hoverEffectField,
                ['key' => 'hover_grid_cols', 'label' => 'تعداد ستون آزمایشگاه هاور', 'type' => 'select', 'options' => [
                    '2' => '۲ ستون', '3' => '۳ ستون', '4' => '۴ ستون', '5' => '۵ ستون',
                ], 'default' => '4', 'show_if_layout' => ['hover_showcase', 'hover_library']],
                ['key' => 'hover_grid_rows', 'label' => 'تعداد ردیف آزمایشگاه هاور', 'type' => 'number', 'default' => 4, 'min' => 1, 'max' => 8, 'show_if_layout' => ['hover_showcase', 'hover_library']],
                ...$viewAllFields,
            ],
        ],

        'category_slider' => [
            'label' => 'اسلایدر دسته‌بندی',
            'description' => 'ردیف افقی از دسته‌بندی‌های محصولات',
            'icon' => 'fa-solid fa-layer-group',
            'layouts' => [
                'default' => ['label' => 'پیش‌فرض', 'thumb' => 'row-compact.svg'],
                'tabs' => ['label' => 'تب دسته‌بندی + اسلایدر محصول', 'thumb' => 'row-tabs.svg'],
            ],
            'settings_fields' => [
                ['key' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'زیرعنوان', 'type' => 'text'],
                ['key' => 'limit', 'label' => 'تعداد آیتم (تعداد تب دسته‌بندی)', 'type' => 'number', 'default' => 10, 'min' => 1, 'max' => 30],
                ['key' => 'products_per_tab', 'label' => 'تعداد محصول هر تب', 'type' => 'number', 'default' => 8, 'min' => 1, 'max' => 20, 'show_if_layout' => ['tabs']],
                $fillCategoryTabsSpacesField,
                $hoverEffectField,
                ...$viewAllFields,
            ],
        ],

        'banner' => [
            'label' => 'بنر تبلیغاتی',
            'description' => 'یک تصویر تمام‌عرض با لینک',
            'icon' => 'fa-solid fa-rectangle-ad',
            'layouts' => [
                'default' => ['label' => 'تمام‌عرض', 'thumb' => 'banner-full.svg'],
                'rounded' => ['label' => 'گردشده با حاشیه', 'thumb' => 'banner-rounded.svg'],
            ],
            'settings_fields' => [
                ['key' => 'image', 'label' => 'تصویر بنر', 'type' => 'image'],
                ['key' => 'alt_text', 'label' => 'متن جایگزین تصویر', 'type' => 'text'],
                ['key' => 'link', 'label' => 'لینک مقصد', 'type' => 'text'],
                ['key' => 'height', 'label' => 'ارتفاع', 'type' => 'select', 'options' => [
                    'small' => 'کوتاه',
                    'medium' => 'متوسط',
                    'large' => 'بلند',
                ], 'default' => 'medium'],
            ],
        ],

        'collection' => [
            'label' => 'مجموعه منتخب',
            'description' => 'مجموعه‌ای از محصولات منتخب یک دسته‌بندی با عنوان اختصاصی',
            'icon' => 'fa-solid fa-star',
            'layouts' => [
                'default' => ['label' => 'پیش‌فرض', 'thumb' => 'row-default.svg'],
            ],
            'settings_fields' => [
                ['key' => 'title', 'label' => 'عنوان مجموعه', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'زیرعنوان', 'type' => 'text'],
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 8, 'min' => 1, 'max' => 24],
                $fillEmptySpacesField,
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین',
                    'popular' => 'محبوب‌ترین',
                    'expensive' => 'گران‌ترین (کردیت)',
                    'cheap' => 'ارزان‌ترین (کردیت)',
                ], 'default' => 'latest'],
                ['key' => 'show_credit', 'label' => 'نمایش کردیت زیر کارت', 'type' => 'checkbox', 'default' => true],
                $hoverEffectField,
                ...$viewAllFields,
            ],
        ],

        'text' => [
            'label' => 'بلوک متنی',
            'description' => 'یک تیتر و متن ساده، بدون محصول',
            'icon' => 'fa-solid fa-align-right',
            'layouts' => [
                'default' => ['label' => 'راست‌چین', 'thumb' => 'text-right.svg'],
                'center' => ['label' => 'وسط‌چین', 'thumb' => 'text-center.svg'],
            ],
            'settings_fields' => [
                ['key' => 'heading', 'label' => 'تیتر', 'type' => 'text'],
                ['key' => 'body', 'label' => 'متن', 'type' => 'textarea'],
                ['key' => 'align', 'label' => 'چینش متن', 'type' => 'select', 'options' => [
                    'right' => 'راست',
                    'center' => 'وسط',
                    'left' => 'چپ',
                ], 'default' => 'right'],
            ],
        ],

        'spacer' => [
            'label' => 'سکشن فاصله',
            'description' => 'ایجاد فاصله خالی استاندارد یا سفارشی بین دو سکشن',
            'icon' => 'fa-solid fa-arrows-up-down',
            'layouts' => [
                'default' => ['label' => 'پیش‌فرض', 'thumb' => 'spacer.svg'],
            ],
            'settings_fields' => [
                ['key' => 'spacing_mode', 'label' => 'نوع فاصله', 'type' => 'select', 'options' => [
                    'standard' => 'استاندارد (۳۱px)',
                    'manual' => 'سفارشی (ورود دستی)',
                ], 'default' => 'standard'],
                ['key' => 'desktop_height', 'label' => 'فاصله دسکتاپ (px)', 'type' => 'number', 'default' => 31, 'min' => 0, 'max' => 300, 'show_if_setting' => ['key' => 'spacing_mode', 'values' => ['manual']]],
                ['key' => 'tablet_height', 'label' => 'فاصله تبلت (px)', 'type' => 'number', 'default' => 31, 'min' => 0, 'max' => 250, 'show_if_setting' => ['key' => 'spacing_mode', 'values' => ['manual']]],
                ['key' => 'mobile_height', 'label' => 'فاصله موبایل (px)', 'type' => 'number', 'default' => 31, 'min' => 0, 'max' => 200, 'show_if_setting' => ['key' => 'spacing_mode', 'values' => ['manual']]],
            ],
        ],


    ],

    // ── آماده‌سازی معماری برای قابلیت‌های آینده (طبق فایل مشخصات فیچر) ──
    // این موارد فعلاً پیاده‌سازی نشده‌اند؛ فقط این‌جا به‌عنوان نقشه‌ی راه ثبت شده‌اند
    // تا وقتی توسعه داده شدند، به ستون settings (JSON) موجود اضافه شوند بدون تغییر ساختار جدول.
    'planned_future_settings_keys' => [
        'ab_test_variant',
        'analytics_tracking_id',
        'schedule_publish_at',
        'condition_display_rules',
        'personalization_segment',
    ],

];
