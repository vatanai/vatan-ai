<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name_fa', 'name_en', 'slug', 'description_fa', 'category', 'tags', 'status',
        'thumbnail_path', 'sample_images', 'show_before_after',
        'primary_model', 'media_type', 'prompt_template', 'negative_prompt', 'button_text',
        'is_free', 'credit_cost', 'single_price', 'required_subscription_tier',
        
        // فیلدهای سیستمی پیش‌فرض
        'description_en', 'subcategory', 'is_featured', 'is_new', 'is_trending',
        'output_type', 'output_format', 'output_count', 'resolution', 'aspect_ratio',
        'delivery_method', 'estimated_time', 'watermark_enabled', 'watermark_position',
        'pricing_model', 'price_tier', 'discount_percent', 'display_mode', 'card_shape',
        'gallery_layout', 'platform', 'accent_color'
    ];

    protected $casts = [
        'tags' => 'array',
        'sample_images' => 'array',
        'show_before_after' => 'boolean',
        'is_free' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_trending' => 'boolean',
        'watermark_enabled' => 'boolean',
    ];

    // دیتای تستی دسته‌بندی جهت تایید فرم بدون نقص
    public static function categoriesList(): array
    {
        return [
            'AVATAR' => 'تبدیل چهره و آواتار',
            'PHOTOSHOOT' => 'عکاسی آتلیه‌ای هوشمند',
            'WALLPAPER' => 'تصویر زمینه و فانتزی',
            'BUSINESS' => 'تصاویر تجاری و کسب‌وکار'
        ];
    }

    // مدل تستی هوش مصنوعی در حال حاضر (تا بعداً صفحه مستقل آن را بسازید)
    public static function aiModelsList(): array
    {
        return [
            'vatan-flux-v1' => 'مدل اختصاصی وطن AI (Flux Core)',
            'stability-sd-3.5' => 'Stable Diffusion 3.5 Ultra'
        ];
    }
}