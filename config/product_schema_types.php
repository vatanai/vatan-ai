<?php

/*
|--------------------------------------------------------------------------
| رجیستری مرکزی «انواع ویژگی‌های خاص محصول» (Product Input Schema Types)
|--------------------------------------------------------------------------
| این فایل تنها منبع حقیقت (Single Source of Truth) برای انواع فیلدهایی است
| که مدیر در گام ۳ ثبت محصول («ویژگی‌های خاص محصول») تعریف می‌کند و کاربر
| نهایی در صفحه ساخت محصول (بساز) با آن‌ها کار می‌کند.
|
| هر نوع (type) این کلیدها را دارد:
|   label       نام فارسی نمایشی برای مدیر
|   icon        کلاس آیکون FontAwesome
|   group       گروه نمایش در کتابخانه انواع (کلیدهای groups پایین)
|   desc        توضیح کوتاه برای مدیر (در کتابخانه انواع)
|   control     نحوه نمایش برای کاربر نهایی (متن توضیحی برای مدیر)
|   caps        قابلیت‌های تنظیماتی که در سازنده برای این نوع نمایش داده می‌شود:
|     options      true = ویرایشگر گزینه‌ها دارد (select/radio/...)
|     opt_prompt   true = هر گزینه می‌تواند «متن پرامپت انگلیسی» اختصاصی داشته باشد
|     opt_credit   true = هر گزینه می‌تواند کردیت اضافه داشته باشد
|     opt_image    true = هر گزینه می‌تواند تصویر داشته باشد (کارت تصویری)
|     placeholder  true = فیلد Placeholder دارد
|     default      نوع مقدار پیش‌فرض: text | number | bool | color | option | null
|     validation   نوع اعتبارسنجی: text (min/max طول + Regex) | number (min/max/step)
|                  | range (اسلایدر: min/max/step/unit) | files (تعداد/حجم/فرمت) | null
|     promptable   true = تنظیمات «تاثیر در پرامپت» (توکن/الحاق) دارد
|     layout       true = فیلد صرفاً چیدمانی است (مقداری از کاربر نمی‌گیرد)
|   presets     مقادیر اولیه هنگام افزودن فیلد از این نوع (گزینه‌های آماده و...)
|
| نکته مهم: کلید هر نوع دقیقاً همان مقداری است که در ستون input_schema محصول
| ذخیره می‌شود؛ تغییر کلیدها بدون مهاجرت داده، محصولات قبلی را می‌شکند.
| انواع قدیمی (text, textarea, number, select, radio, checkbox, switch, color,
| image_upload, file_upload) عمداً با همان کلید قبلی حفظ شده‌اند.
*/

return [

    'groups' => [
        'basic'  => ['label' => 'پایه A',           'icon' => 'fa-font'],
        'choice' => ['label' => 'انتخابی',          'icon' => 'fa-list-check'],
        'media'  => ['label' => 'تصویر و فایل',     'icon' => 'fa-image'],
        'output' => ['label' => 'تنظیمات خروجی',    'icon' => 'fa-crop-simple'],
        'ai'     => ['label' => 'هوش مصنوعی',       'icon' => 'fa-wand-magic-sparkles'],
        'layout' => ['label' => 'چیدمان و راهنما',  'icon' => 'fa-table-cells-large'],
    ],

    'types' => [

        // ═══ گروه پایه ═══
        'text' => [
            'label' => 'متن کوتاه', 'icon' => 'fa-font', 'group' => 'basic',
            'desc' => 'یک خط متن آزاد — مثل نام، عنوان یا توضیح کوتاه',
            'control' => 'Input تک‌خطی',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => 'text', 'validation' => 'text', 'promptable' => true],
        ],
        'textarea' => [
            'label' => 'متن بلند', 'icon' => 'fa-align-right', 'group' => 'basic',
            'desc' => 'متن چندخطی — مثل توضیح صحنه یا جزئیات دلخواه',
            'control' => 'Textarea چندخطی',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => 'text', 'validation' => 'text', 'promptable' => true],
        ],
        'number' => [
            'label' => 'عدد', 'icon' => 'fa-hashtag', 'group' => 'basic',
            'desc' => 'ورودی عددی با حداقل/حداکثر — مثل سن یا تعداد',
            'control' => 'Number Input',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => 'number', 'validation' => 'number', 'promptable' => true],
        ],
        'slider' => [
            'label' => 'اسلایدر (بازه)', 'icon' => 'fa-sliders', 'group' => 'basic',
            'desc' => 'انتخاب مقدار در یک بازه با کشیدن — مثل شدت یا درصد',
            'control' => 'Range Slider',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => 'number', 'validation' => 'range', 'promptable' => true],
            'presets' => ['min' => 0, 'max' => 100, 'step' => 5, 'default' => 50],
        ],

        // ═══ گروه انتخابی ═══
        'select' => [
            'label' => 'لیست کشویی', 'icon' => 'fa-caret-down', 'group' => 'choice',
            'desc' => 'انتخاب یک گزینه از لیست بازشو — مناسب گزینه‌های زیاد',
            'control' => 'Dropdown',
            'caps' => ['options' => true, 'opt_prompt' => true, 'opt_credit' => true, 'placeholder' => true, 'default' => 'option', 'validation' => null, 'promptable' => true],
        ],
        'radio' => [
            'label' => 'تک‌انتخاب (Radio)', 'icon' => 'fa-circle-dot', 'group' => 'choice',
            'desc' => 'انتخاب یک گزینه با نمایش همه گزینه‌ها — مناسب ۲ تا ۶ گزینه',
            'control' => 'Radio / چیپ‌های انتخابی',
            'caps' => ['options' => true, 'opt_prompt' => true, 'opt_credit' => true, 'placeholder' => false, 'default' => 'option', 'validation' => null, 'promptable' => true],
        ],
        'multi_select' => [
            'label' => 'چندانتخابی', 'icon' => 'fa-list-check', 'group' => 'choice',
            'desc' => 'انتخاب هم‌زمان چند گزینه — مثل چند حالت یا چند رنگ',
            'control' => 'Checkbox List / چیپ‌های چندانتخابی',
            'caps' => ['options' => true, 'opt_prompt' => true, 'opt_credit' => true, 'placeholder' => false, 'default' => null, 'validation' => 'number', 'promptable' => true],
        ],
        'button_group' => [
            'label' => 'دکمه‌های انتخابی', 'icon' => 'fa-grip', 'group' => 'choice',
            'desc' => 'چند دکمه چسبیده (Segmented) — انتخاب سریع یک حالت',
            'control' => 'Segmented Control',
            'caps' => ['options' => true, 'opt_prompt' => true, 'opt_credit' => true, 'placeholder' => false, 'default' => 'option', 'validation' => null, 'promptable' => true],
        ],
        'switch' => [
            'label' => 'روشن / خاموش', 'icon' => 'fa-toggle-on', 'group' => 'choice',
            'desc' => 'یک سوییچ ساده — مثل «پس‌زمینه حذف شود؟»',
            'control' => 'Toggle Switch',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => 'bool', 'validation' => null, 'promptable' => true],
        ],
        'checkbox' => [
            'label' => 'تایید تکی', 'icon' => 'fa-square-check', 'group' => 'choice',
            'desc' => 'یک چک‌باکس برای تایید یک مورد مشخص',
            'control' => 'Checkbox',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => 'bool', 'validation' => null, 'promptable' => true],
        ],
        'gender' => [
            'label' => 'جنسیت چهره', 'icon' => 'fa-venus-mars', 'group' => 'basic',
            'desc' => 'انتخاب زن یا مرد با پرامپت مستقل برای هر گزینه',
            'control' => 'کارت انتخاب زن / مرد',
            'caps' => ['options' => true, 'opt_prompt' => true, 'opt_credit' => false, 'placeholder' => false, 'default' => 'option', 'validation' => null, 'promptable' => true],
            'presets' => [
                'label_fa' => 'جنسیت چهره', 'required' => '1', 'default' => 'male', 'prompt_mode' => 'append',
                'options' => [
                    ['value' => 'male', 'label' => 'مرد', 'prompt' => 'male subject, masculine facial structure and natural male features'],
                    ['value' => 'female', 'label' => 'زن', 'prompt' => 'female subject, feminine facial structure and natural female features'],
                ],
            ],
        ],

        // ═══ گروه تصویر و فایل ═══
        'image_upload' => [
            'label' => 'آپلود تصویر', 'icon' => 'fa-image', 'group' => 'media',
            'desc' => 'دریافت یک تصویر از کاربر — مثل عکس چهره یا محیط',
            'control' => 'Upload Box با پیش‌نمایش',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => null, 'validation' => 'files', 'promptable' => false],
            'presets' => ['max_files' => 1, 'max_size_mb' => 10, 'accept' => 'image/*'],
        ],
        'multi_image' => [
            'label' => 'چند تصویر', 'icon' => 'fa-images', 'group' => 'media',
            'desc' => 'دریافت چند تصویر هم‌زمان — مثل چند زاویه از چهره',
            'control' => 'Gallery Upload',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => null, 'validation' => 'files', 'promptable' => false],
            'presets' => ['max_files' => 4, 'max_size_mb' => 10, 'accept' => 'image/*'],
        ],
        'file_upload' => [
            'label' => 'آپلود فایل', 'icon' => 'fa-file-arrow-up', 'group' => 'media',
            'desc' => 'دریافت فایل غیرتصویری از کاربر',
            'control' => 'Upload Box',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => null, 'validation' => 'files', 'promptable' => false],
            'presets' => ['max_files' => 1, 'max_size_mb' => 10, 'accept' => ''],
        ],
        'color' => [
            'label' => 'انتخاب رنگ', 'icon' => 'fa-palette', 'group' => 'media',
            'desc' => 'انتخاب یک رنگ — مثل رنگ پس‌زمینه یا لباس',
            'control' => 'Color Picker',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => 'color', 'validation' => null, 'promptable' => true],
        ],

        // ═══ گروه تنظیمات خروجی ═══
        'aspect_ratio' => [
            'label' => 'نسبت تصویر خروجی', 'icon' => 'fa-crop-simple', 'group' => 'basic',
            'desc' => 'انتخاب نسبت خروجی (۱:۱، ۱۶:۹ و...) به شکل کارت',
            'control' => 'Card Selector',
            'caps' => ['options' => true, 'opt_prompt' => false, 'opt_credit' => true, 'placeholder' => false, 'default' => 'option', 'validation' => null, 'promptable' => false],
            'presets' => ['label_fa' => 'نسبت تصویر خروجی', 'required' => '1', 'options' => [
                ['value' => '1:1',  'label' => 'مربع ۱:۱'],
                ['value' => '4:5',  'label' => 'پرتره ۴:۵'],
                ['value' => '3:4',  'label' => 'عمودی ۳:۴'],
                ['value' => '2:3',  'label' => 'عمودی ۲:۳'],
                ['value' => '9:16', 'label' => 'استوری ۹:۱۶'],
                ['value' => '16:9', 'label' => 'عریض ۱۶:۹'],
                ['value' => '3:2',  'label' => 'افقی ۳:۲'],
            ], 'default' => '1:1'],
        ],
        'resolution' => [
            'label' => 'کیفیت نهایی خروجی', 'icon' => 'fa-expand', 'group' => 'basic',
            'desc' => 'انتخاب کیفیت/رزولوشن خروجی — می‌تواند کردیت اضافه داشته باشد',
            'control' => 'Card / Select',
            'caps' => ['options' => true, 'opt_prompt' => false, 'opt_credit' => true, 'placeholder' => false, 'default' => 'option', 'validation' => null, 'promptable' => false],
            'presets' => ['label_fa' => 'کیفیت نهایی خروجی', 'required' => '1', 'options' => [
                ['value' => '1K', 'label' => 'Medium', 'credit' => 0],
                ['value' => '2K', 'label' => 'High',   'credit' => 2],
                ['value' => '4K', 'label' => 'Ultra',  'credit' => 5],
            ], 'default' => '1K'],
        ],
        'style_preset' => [
            'label' => 'استایل آماده', 'icon' => 'fa-wand-magic-sparkles', 'group' => 'output',
            'desc' => 'کارت‌های تصویری استایل — هر استایل پرامپت اختصاصی خودش را دارد',
            'control' => 'کارت تصویری',
            'caps' => ['options' => true, 'opt_prompt' => true, 'opt_credit' => true, 'opt_image' => true, 'placeholder' => false, 'default' => 'option', 'validation' => null, 'promptable' => true],
        ],

        // ═══ گروه هوش مصنوعی ═══
        'prompt' => [
            'label' => 'پرامپت کاربر', 'icon' => 'fa-keyboard', 'group' => 'ai',
            'desc' => 'متن آزاد کاربر که مستقیم وارد پرامپت نهایی می‌شود',
            'control' => 'Textarea بزرگ',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => 'text', 'validation' => 'text', 'promptable' => true],
        ],
        'negative_prompt' => [
            'label' => 'پرامپت منفی', 'icon' => 'fa-ban', 'group' => 'ai',
            'desc' => 'چیزهایی که کاربر نمی‌خواهد در خروجی باشد',
            'control' => 'Textarea جمع‌شونده',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => 'text', 'validation' => 'text', 'promptable' => true],
        ],
        'seed' => [
            'label' => 'Seed', 'icon' => 'fa-dice', 'group' => 'ai',
            'desc' => 'عدد Seed برای تکرارپذیری خروجی — مخصوص کاربر حرفه‌ای',
            'control' => 'Number Input',
            'caps' => ['options' => false, 'placeholder' => true, 'default' => 'number', 'validation' => 'number', 'promptable' => false],
        ],
        'strength' => [
            'label' => 'شدت تاثیر', 'icon' => 'fa-gauge-high', 'group' => 'ai',
            'desc' => 'اسلایدر شدت اعمال تغییرات نسبت به عکس ورودی',
            'control' => 'Slider',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => 'number', 'validation' => 'range', 'promptable' => true],
            'presets' => ['min' => 0, 'max' => 100, 'step' => 5, 'default' => 80, 'unit' => '%'],
        ],

        // ═══ گروه چیدمان و راهنما (مقداری از کاربر نمی‌گیرند) ═══
        'section' => [
            'label' => 'عنوان بخش', 'icon' => 'fa-heading', 'group' => 'layout',
            'desc' => 'تیتر و توضیح برای گروه‌بندی فیلدهای بعدی',
            'control' => 'Heading + Description',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => null, 'validation' => null, 'promptable' => false, 'layout' => true],
        ],
        'divider' => [
            'label' => 'جداکننده', 'icon' => 'fa-minus', 'group' => 'layout',
            'desc' => 'یک خط افقی برای جداسازی بصری',
            'control' => 'خط افقی',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => null, 'validation' => null, 'promptable' => false, 'layout' => true],
        ],
        'info' => [
            'label' => 'پیام راهنما', 'icon' => 'fa-circle-info', 'group' => 'layout',
            'desc' => 'باکس اطلاع‌رسانی/هشدار برای راهنمایی کاربر',
            'control' => 'Alert / Info Box',
            'caps' => ['options' => false, 'placeholder' => false, 'default' => null, 'validation' => null, 'promptable' => false, 'layout' => true],
        ],
    ],
];
