<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_fa', 'name_en', 'slug', 'description_fa', 'description_en', 'category', 'subcategory',
        'status', 'tags', 'is_featured', 'is_new', 'is_trending', 'thumbnail', 'cover',
        'sample_outputs', 'media_type', 'preview_video_url', 'primary_model', 'timeout',
        'pipeline_type', 'fallback_models', 'prompt_template', 'negative_prompt',
        'show_prompt_to_user', 'face_swap_enabled', 'multi_step_pipeline', 'input_schema',
        'output_type', 'output_format', 'output_count', 'resolution', 'aspect_ratio',
        'delivery_method', 'estimated_time', 'watermark_enabled', 'watermark_position',
        'pricing_model', 'credit_cost', 'price_tier', 'discount_percentage', 'is_free',
        'display_mode', 'card_shape', 'gallery_layout', 'card_label', 'platform',
        'accent_color', 'show_before_after'
    ];

    // کست کردن فیلدهای متنی ساختاریافته به آرایه PHP و فیلدهای صفر و یک به Boolean
    protected $casts = [
        'tags' => 'array',
        'sample_outputs' => 'array',
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
}