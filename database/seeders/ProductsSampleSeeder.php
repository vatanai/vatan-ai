<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ══════════════════════════════════════════════════════════════════
 * Seeder داده‌ی نمونه — فقط برای تست بصری صفحه‌ی «لیست محصولات» در حالت لوکال
 * ──────────────────────────────────────────────────────────────────
 * این Seeder هیچ Migration یا ساختار جدولی را تغییر نمی‌دهد؛ فقط ۵ رکورد
 * نمونه در جدول products درج می‌کند تا کارت‌های آماری، فیلترها، چیپ‌های
 * سریع و جدول با داده‌ی واقعی (نه خالی) قابل بررسی باشند.
 *
 * نحوه‌ی اجرا (روی سیستم لوکال خودتان، جایی که به دیتابیس MySQL دسترسی دارید):
 *   php artisan db:seed --class=Database\\Seeders\\ProductsSampleSeeder
 *
 * تصاویر thumbnail نمونه از قبل در مسیر زیر قرار داده شده‌اند:
 *   storage/app/public/products/thumbnails/sample-*.jpg
 * (در صورت نبود لینک storage، یک بار دستور `php artisan storage:link` را
 * اجرا کنید تا تصاویر در مرورگر نمایش داده شوند.)
 * ══════════════════════════════════════════════════════════════════
 */
class ProductsSampleSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            [
                'name_fa' => 'عکس پروفایل هوش مصنوعی',
                'name_en' => 'AI Profile Photo',
                'thumbnail' => 'products/thumbnails/sample-profile-ai.jpg',
                'category' => 'PEOPLE',
                'subcategory' => 'Professional',
                'status' => 'active',
                'media_type' => 'photo',
                'pricing_model' => 'per_credit',
                'credit_cost' => 12,
                'is_featured' => true,
                'is_new' => false,
                'is_trending' => true,
                'primary_model' => 'flux-pro-1.1',
                'prompt_template' => 'یک عکس پروفایل حرفه‌ای با پس‌زمینه‌ی {{background}} از {{input_photo}} بساز.',
            ],
            [
                'name_fa' => 'ساخت پوستر تبلیغاتی',
                'name_en' => 'Ad Poster Generator',
                'thumbnail' => 'products/thumbnails/sample-poster-ai.jpg',
                'category' => 'BUSINESS',
                'subcategory' => 'Real Estate',
                'status' => 'active',
                'media_type' => 'photo',
                'pricing_model' => 'subscription',
                'credit_cost' => 0,
                'is_featured' => false,
                'is_new' => true,
                'is_trending' => true,
                'primary_model' => 'gemini-3.1-flash-image',
                'prompt_template' => 'یک پوستر تبلیغاتی برای محصول {{product_name}} با سبک {{style}} طراحی کن.',
            ],
            [
                'name_fa' => 'حذف پس‌زمینه تصویر',
                'name_en' => 'Background Removal',
                'thumbnail' => 'products/thumbnails/sample-bg-remove.jpg',
                'category' => 'PEOPLE',
                'subcategory' => 'Lifestyle',
                'status' => 'active',
                'media_type' => 'photo',
                'pricing_model' => 'free',
                'credit_cost' => 0,
                'is_featured' => false,
                'is_new' => false,
                'is_trending' => false,
                'primary_model' => 'rembg-v2',
                'prompt_template' => 'پس‌زمینه‌ی تصویر {{input_photo}} را به‌صورت کامل حذف کن.',
            ],
            [
                'name_fa' => 'استایل عکس عروسی',
                'name_en' => 'Wedding Photo Style',
                'thumbnail' => 'products/thumbnails/sample-wedding-style.jpg',
                'category' => 'EVENTS',
                'subcategory' => 'Wedding',
                'status' => 'draft',
                'media_type' => 'photo',
                'pricing_model' => 'per_credit',
                'credit_cost' => 20,
                'is_featured' => false,
                'is_new' => true,
                'is_trending' => false,
                'primary_model' => 'flux-pro-1.1',
                'prompt_template' => 'عکس {{input_photo}} را با استایل لباس و آرایش عروسی {{style}} بازسازی کن.',
            ],
            [
                'name_fa' => 'تبدیل عکس به نقاشی',
                'name_en' => 'Photo to Painting',
                'thumbnail' => 'products/thumbnails/sample-photo-to-paint.jpg',
                'category' => 'AVATARS',
                'subcategory' => 'Fantasy',
                'status' => 'inactive',
                'media_type' => 'both',
                'pricing_model' => 'per_credit',
                'credit_cost' => 15,
                'is_featured' => false,
                'is_new' => false,
                'is_trending' => false,
                'primary_model' => 'sdxl-art-v1',
                'prompt_template' => 'تصویر {{input_photo}} را به سبک نقاشی {{art_style}} تبدیل کن.',
            ],
        ];

        foreach ($samples as $data) {
            $slug = Str::slug($data['name_en']);

            // اگر قبلاً با همین slug ثبت شده باشد، دوباره درج نمی‌شود (اجرای امن و تکرارپذیر)
            if (Product::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }

            Product::create([
                'name_fa' => $data['name_fa'],
                'name_en' => $data['name_en'],
                'slug' => $slug,
                'description_fa' => 'این یک محصول نمونه برای تست نمایش صفحه‌ی لیست محصولات در حالت لوکال است.',
                'description_en' => 'Sample product seeded for local UI testing of the products list page.',
                'category' => $data['category'],
                'subcategory' => $data['subcategory'],
                'status' => $data['status'],
                'tags' => ['نمونه', 'تست', $data['category']],
                'is_featured' => $data['is_featured'],
                'is_new' => $data['is_new'],
                'is_trending' => $data['is_trending'],
                'thumbnail' => $data['thumbnail'],
                'cover' => null,
                'sample_outputs' => [],
                'media_type' => $data['media_type'],
                'preview_video_url' => null,

                'primary_model' => $data['primary_model'],
                'timeout' => 60,
                'pipeline_type' => 'image_generation',
                'fallback_models' => [],
                'prompt_template' => $data['prompt_template'],
                'negative_prompt' => null,
                'show_prompt_to_user' => false,
                'face_swap_enabled' => false,
                'multi_step_pipeline' => false,
                'input_schema' => [],

                'watermark_enabled' => true,
                'watermark_position' => 'corner',
                'pricing_model' => $data['pricing_model'],
                'credit_cost' => $data['credit_cost'],
                'display_mode' => 'card',
                'card_shape' => 'portrait',
                'gallery_layout' => 'grid',
                'card_label' => null,
            ]);
        }

        $this->command?->info('✔ ۵ محصول نمونه (یا باقی‌مانده‌ی آن‌ها) با موفقیت درج شد.');
    }
}
