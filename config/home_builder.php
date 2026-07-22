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

return [

    'default_page_key' => 'app_home',

    'statuses' => [
        'draft' => 'پیش‌نویس',
        'published' => 'منتشرشده',
        'hidden' => 'مخفی',
    ],

    'product_source_fields' => $productSourceFields,

    'types' => [

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
                'large' => ['label' => 'کارت بزرگ', 'thumb' => 'row-large.svg'],
                'glass' => ['label' => 'کارت شیشه‌ای', 'thumb' => 'row-glass.svg'],
                'neon' => ['label' => 'قاب نئونی', 'thumb' => 'row-neon.svg'],
            ],
            'settings_fields' => [
                ['key' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'زیرعنوان', 'type' => 'text'],
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 8, 'min' => 1, 'max' => 24],
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
                ['key' => 'show_view_all', 'label' => 'نمایش دکمه «مشاهده همه»', 'type' => 'checkbox', 'default' => true],
                ['key' => 'intro_badge', 'label' => 'برچسب بالای کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro']],
                ['key' => 'intro_heading', 'label' => 'عنوان کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro']],
                ['key' => 'intro_heading_accent', 'label' => 'بخش رنگی عنوان', 'type' => 'text', 'show_if_layout' => ['intro']],
                ['key' => 'intro_desc', 'label' => 'توضیح کارت معرفی', 'type' => 'textarea', 'show_if_layout' => ['intro']],
                ['key' => 'intro_steps', 'label' => 'مراحل (هر خط = یک مرحله)', 'type' => 'textarea', 'show_if_layout' => ['intro']],
                ['key' => 'intro_note', 'label' => 'یادداشت کوچک', 'type' => 'text', 'show_if_layout' => ['intro']],
                ['key' => 'intro_cta_label', 'label' => 'متن دکمه کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro']],
                ['key' => 'intro_cta_link', 'label' => 'لینک دکمه کارت معرفی', 'type' => 'text', 'show_if_layout' => ['intro']],
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
                'bento' => ['label' => 'بنتو (نامتقارن)', 'thumb' => 'grid-bento.svg'],
            ],
            'settings_fields' => [
                ['key' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'زیرعنوان', 'type' => 'text'],
                ...$productSourceFields,
                ['key' => 'limit', 'label' => 'تعداد آیتم', 'type' => 'number', 'default' => 8, 'min' => 1, 'max' => 24],
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین',
                    'popular' => 'محبوب‌ترین',
                    'expensive' => 'گران‌ترین (کردیت)',
                    'cheap' => 'ارزان‌ترین (کردیت)',
                ], 'default' => 'latest'],
                ['key' => 'show_credit', 'label' => 'نمایش کردیت زیر کارت', 'type' => 'checkbox', 'default' => true],
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
                ['key' => 'sort', 'label' => 'مرتب‌سازی', 'type' => 'select', 'options' => [
                    'latest' => 'جدیدترین',
                    'popular' => 'محبوب‌ترین',
                    'expensive' => 'گران‌ترین (کردیت)',
                    'cheap' => 'ارزان‌ترین (کردیت)',
                ], 'default' => 'latest'],
                ['key' => 'show_credit', 'label' => 'نمایش کردیت زیر کارت', 'type' => 'checkbox', 'default' => true],
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
            'label' => 'فاصله‌گذار',
            'description' => 'یک فضای خالی برای تنظیم چیدمان عمودی صفحه',
            'icon' => 'fa-solid fa-arrows-up-down',
            'layouts' => [
                'default' => ['label' => 'پیش‌فرض', 'thumb' => 'spacer.svg'],
            ],
            'settings_fields' => [
                ['key' => 'height', 'label' => 'ارتفاع فاصله', 'type' => 'select', 'options' => [
                    'small' => 'کوچک (۱۶px)',
                    'medium' => 'متوسط (۳۲px)',
                    'large' => 'بزرگ (۶۴px)',
                ], 'default' => 'medium'],
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
