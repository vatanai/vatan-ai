<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function create()
    {
return view('admin.prompts.products-create');
    }

    public function store(Request $request)
    {
        // آماده‌سازی داده‌های Boolean برای فیلدهای تگ سوئیچ (چون تگ‌های ارسال نشده، تهی می‌مانند)
        $request->merge([
            'is_featured' => $request->has('is_featured'),
            'is_new' => $request->has('is_new'),
            'is_trending' => $request->has('is_trending'),
            'show_prompt_to_user' => $request->has('show_prompt_to_user'),
            'face_swap_enabled' => $request->has('face_swap_enabled'),
            'multi_step_pipeline' => $request->has('multi_step_pipeline'),
            'watermark_enabled' => $request->has('watermark_enabled'),
            'is_free' => $request->has('is_free'),
            'show_before_after' => $request->has('show_before_after'),
        ]);

        // ولیدیشن مستقیم در کنترلر بر اساس ساختار ارسالی جاوااسکریپت فرانت‌اند
        $validatedData = $request->validate([
            // گام اول: هویت و رسانه
            'name_fa' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug|max:255',
            'description_fa' => 'nullable|string',
            'description_en' => 'nullable|string',
            'category' => 'required|string',
            'subcategory' => 'nullable|string',
            'status' => 'required|in:draft,active,inactive',
            'tags' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_trending' => 'boolean',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', 
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'sample_outputs' => 'nullable|array|max:10',
            'sample_outputs.*' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
            'media_type' => 'required|string',
            'preview_video_url' => 'nullable|url',

            // گام دوم: تنظیمات هوش مصنوعی
            'primary_model' => 'required|string',
            'timeout' => 'required|integer|min:10|max:300',
            'pipeline_type' => 'required|string',
            'fallback_models' => 'nullable|array',
            'prompt_template' => 'required|string',
            'negative_prompt' => 'nullable|string',
            'show_prompt_to_user' => 'boolean',
            'face_swap_enabled' => 'boolean',
            'multi_step_pipeline' => 'boolean',
            
            // ولیدیشن آرایه داینامیک فیلدهای ورودی (Input Schema)
            'input_schema' => 'nullable|array',
            'input_schema.*.field_id' => 'required_with:input_schema|string|alpha_dash',
            'input_schema.*.label_fa' => 'required_with:input_schema|string',
            'input_schema.*.type' => 'required_with:input_schema|string',
            'input_schema.*.required' => 'nullable|boolean',

            // گام سوم: خروجی و قیمت‌گذاری
            'output_type' => 'required|string',
            'output_format' => 'required|string',
            'output_count' => 'required|integer|min:1|max:10',
            'resolution' => 'required|string',
            'aspect_ratio' => 'required|string',
            'delivery_method' => 'required|string',
            'estimated_time' => 'required|integer|min:1',
            'watermark_enabled' => 'boolean',
            'watermark_position' => 'required|string',
            'pricing_model' => 'required|string',
            'credit_cost' => 'required_if:pricing_model,per_credit|integer|min:0',
            'price_tier' => 'required|string',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'is_free' => 'boolean',
            'display_mode' => 'required|string',
            'card_shape' => 'required|string',
            'gallery_layout' => 'required|string',
            'card_label' => 'nullable|string',
            'platform' => 'required|string',
            'accent_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6})$/',
            'show_before_after' => 'boolean',
        ]);

        // فرآیند آپلود فایل تک تصویر تیوپ‌نیل (Thumbnail)
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('products/thumbnails', 'public');
            $validatedData['thumbnail'] = '/storage/' . $path;
        }

        // فرآیند آپلود فایل تصویر کاور (Cover)
        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('products/covers', 'public');
            $validatedData['cover'] = '/storage/' . $path;
        }

        // فرآیند آپلود تصاویر نمونه خروجی متعدد گالری (Sample Outputs)
        if ($request->hasFile('sample_outputs')) {
            $uploadedSamples = [];
            foreach ($request->file('sample_outputs') as $file) {
                $path = $file->store('products/samples', 'public');
                $uploadedSamples[] = '/storage/' . $path;
            }
            $validatedData['sample_outputs'] = $uploadedSamples;
        }

        // ذخیره محصول نهایی در دیتابیس
        Product::create($validatedData);

return redirect()->route('admin.products')
                         ->with('success', 'محصول جدید با موفقیت ثبت و پیکربندی شد.');
    }
}