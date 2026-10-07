<?php

/*
| «استودیو محصول» — محصول پروداکتی سبک جدید (پک شات).
| کلید اصلی env قطع اضطراری است؛ روشن/خاموش روزمره و مخاطب از پنل
| (جدول product_shot_settings) کنترل می‌شود و پیش‌فرض آن خاموش است.
*/
return [
    'enabled' => (bool) env('PRODUCT_SHOTS_ENABLED', true),

    // مدل بینای ارزان برای فیلتر کیفیت ورودی و کنترل کیفیت خروجی (OpenRouter)
    'vision_model' => env('PRODUCT_SHOTS_VISION_MODEL', 'google/gemini-2.5-flash-lite'),

    'max_upload_mb' => 12,
    // یک تصویر اصلی + دو زاویه‌ی مکمل = حداکثر سه زاویه از یک محصول واحد.
    'max_extra_angles' => 2,
    'quality_levels' => [
        'standard' => 'استاندارد',
        'professional' => 'حرفه‌ای',
        'best' => 'بهترین خروجی',
    ],
    'default_quality' => 'standard',
    'preflight_min_side' => 900,
    'product_sheet_size' => 2048,
    'aspect_ratios' => ['4:5', '1:1', '9:16'],
    'default_aspect_ratio' => '4:5',
    'output_resolution' => env('PRODUCT_SHOTS_RESOLUTION', '1080'),

    // پوشه‌ی عکس‌های ورودی پک (روی دیسک public، بعد از اتمام پاک می‌شود)
    'upload_dir' => 'uploads/product-shots',
    'source_retention_hours' => 48,
];
