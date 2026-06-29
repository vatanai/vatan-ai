<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        // هویت
        'name_fa', 'name_en', 'slug', 'description_fa', 'description_en',
        'category', 'subcategory', 'status', 'tags',
        'is_featured', 'is_new', 'is_trending',

        // رسانه
        'thumbnail_path', 'cover_path', 'sample_images',
        'media_type', 'preview_video_url',

        // هوش مصنوعی
        'primary_model', 'primary_timeout', 'primary_type',
        'fallback_models', 'prompt_template', 'negative_prompt',
        'show_prompt_to_user', 'face_swap_enabled', 'multi_step_pipeline',
        'input_schema',

        // خروجی
        'output_type', 'output_format', 'output_count', 'resolution',
        'aspect_ratio', 'delivery_method', 'estimated_time',
        'watermark_enabled', 'watermark_position',

        // قیمت‌گذاری
        'pricing_model', 'credit_cost', 'price_tier',
        'discount_percent', 'is_free',

        // نمایش
        'display_mode', 'card_shape', 'gallery_layout', 'card_badge',
        'platform', 'accent_color', 'show_before_after',
    ];

    protected $casts = [
        'tags' => 'array',
        'sample_images' => 'array',
        'fallback_models' => 'array',
        'input_schema' => 'array',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_trending' => 'boolean',
        'show_prompt_to_user' => 'boolean',
        'face_swap_enabled' => 'boolean',
        'multi_step_pipeline' => 'boolean',
        'watermark_enabled' => 'boolean',
        'is_free' => 'boolean',
        'show_before_after' => 'boolean',
    ];

    public static function categoriesList(): array
    {
        return [
            'PEOPLE' => 'شخصی',
            'BUSINESS' => 'کسب‌وکار',
            'EVENTS' => 'مناسبت‌ها',
            'FAMILY' => 'خانواده',
            'KIDS' => 'کودکان',
            'PETS' => 'حیوانات',
            'ENTERTAINMENT' => 'سرگرمی',
            'PRODUCTS' => 'محصولات',
            'AVATARS' => 'آواتار',
            'VIDEOS' => 'ویدیو',
        ];
    }

    public static function subcategoriesMap(): array
    {
        return [
            'PEOPLE' => ['Professional', 'Fashion', 'Lifestyle', 'Fitness', 'Beauty'],
            'BUSINESS' => ['Real Estate', 'Medical', 'Lawyer', 'Coach', 'Education', 'Entrepreneur'],
            'EVENTS' => ['Birthday', 'Wedding', 'Graduation', 'Valentine', 'Nowruz', 'Yalda', 'Eid'],
            'FAMILY' => ['خانواده کامل', 'والدین', 'نوزاد'],
            'KIDS' => ['کودک', 'نوجوان'],
            'PETS' => ['سگ', 'گربه', 'سایر'],
            'ENTERTAINMENT' => ['Anime / Manga', 'Disney / Pixar', 'Superhero / Fantasy'],
            'PRODUCTS' => ['محصول تجاری', 'فود', 'فشن'],
            'AVATARS' => ['واقع‌گرایانه', 'کارتونی', 'سه‌بعدی'],
            'VIDEOS' => ['Personal', 'Business', 'Social Media', 'Kids', 'AI Tools'],
        ];
    }

  public static function aiModelsList(): array
    {
        // این بخش بعداً به صفحه و جدول اختصاصی مدل‌های هوش مصنوعی در دیتابیس متصل خواهد شد.
        // در حال حاضر طبق درخواست شما، لیست کاملاً خالی بازگردانده می‌شود.
        return [
            // هرموقع مدل جدیدی ساختید، یا به صورت داینامیک از دیتابیس اینجا لود می‌شود یا دستی اضافه می‌کنید.
        ];
    }
}