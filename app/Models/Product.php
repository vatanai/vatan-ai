<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    // ۱. اضافه کردن تمام فیلدهای اصلی و جدید به fillable جهت پشتیبانی از متدهای دیتابیس
    protected $fillable = [
        'name_fa',
        'name_en',
        'slug',
        'description_fa',
        'description_en',
        'category_id',
        'category',
        'subcategory',
        'status',
        'thumbnail',
        'cover',
        'sample_outputs',
        'media_type',
        'preview_video_url',
        'primary_model',
        'fallback_models',
        'prompt_template',
        'system_prompt',
        'negative_prompt',
        'seed',
        'provider_options',
        'input_schema',
        'timeout',
        'pipeline_type',

        // نوع سوژه و حفظ هویت
        'subject_type',
        'identity_preservation',
        'identity_strength',
        'preserve_body',
        'identity_instructions',
        'min_reference_images',
        'max_reference_images',

        'watermark_enabled',
        'watermark_position',
        'pricing_model',
        'credit_cost',
        'display_mode',
        'card_shape',
        'gallery_layout',
        'card_label',
        'output_type',
        'output_format',
        'output_count',
        'resolution',
        'aspect_ratio',
        'delivery_method',
        'estimated_time',
        'price_tier',
        'discount_percentage',
        'platform',
        'accent_color',
        'tags',

        // فیلدهای فاز جدید توسعه
        'new_display_order',
        'new_internal_code',
        'new_admin_note',
        'new_is_premium',
        'new_is_recommended',
        'new_is_beta',
        'new_product_icon',
        'new_watermark_corner_precise',
        'new_watermark_opacity',
        'new_watermark_size',
        'new_watermark_type',
        'new_watermark_text_color',
        'new_min_credit_required',
        'new_max_run_per_user',
        'new_show_free_badge',
        'new_price_custom_label'
    ];

    // ۲. تعریف کست‌ها برای تبدیل خودکار آرایه‌ها به JSON موقع ذخیره در دیتابیس
    protected $casts = [
        'sample_outputs'    => 'array',
        'fallback_models'   => 'array',
        'input_schema'      => 'array',
        'provider_options'  => 'array',
        'tags'              => 'array',
        'is_featured'       => 'boolean',
        'is_new'            => 'boolean',
        'is_trending'       => 'boolean',
        'watermark_enabled' => 'boolean',
        'identity_preservation' => 'boolean',
        'preserve_body'     => 'boolean',
        'identity_strength' => 'integer',
        'min_reference_images' => 'integer',
        'max_reference_images' => 'integer',
        'seed'              => 'integer',
        'new_is_premium'    => 'boolean',
        'new_is_recommended'=> 'boolean',
        'new_is_beta'       => 'boolean',
        'new_show_free_badge'=> 'boolean',
    ];

    /**
     * ارتباط با تاریخچه تغییرات پرامپت محصول
     */
    public function promptHistories(): HasMany
    {
        return $this->hasMany(ProductPromptHistory::class)->orderBy('version_number', 'desc');
    }
}
