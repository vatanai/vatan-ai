<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->paginate(20);
        return view('admin.products', compact('products'));
    }

    public function create()
    {
        return view('admin.prompts.products-create', [
            'categories' => Product::categoriesList(),
            'subcategoriesMap' => Product::subcategoriesMap(),
            'aiModels' => Product::aiModelsList(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // هویت
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug', 'alpha_dash'],
            'description_fa' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'subcategory' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,inactive'],
            'tags' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],

            // رسانه
            'thumbnail' => ['required', 'image', 'max:5120'], // اجباری برای ساخت محصول اولیه
            'cover' => ['nullable', 'image', 'max:5120'],
            'sample_images.*' => ['nullable', 'image', 'max:5120'],
            'media_type' => ['required', 'in:photo,video,both'],
            'preview_video_url' => ['nullable', 'url'],

            // هوش مصنوعی
            'primary_model' => ['required', 'string'],
            'primary_timeout' => ['required', 'integer', 'min:1'],
            'primary_type' => ['required', 'string'],
            'fallback_models' => ['nullable', 'array'],
            'prompt_template' => ['required', 'string'],
            'negative_prompt' => ['nullable', 'string'],
            'show_prompt_to_user' => ['nullable', 'boolean'],
            'face_swap_enabled' => ['nullable', 'boolean'],
            'multi_step_pipeline' => ['nullable', 'boolean'],

            // تنظیمات خروجی
            'output_type' => ['required', 'in:image,video,image+video'],
            'output_format' => ['required', 'string'],
            'output_count' => ['required', 'integer', 'min:1', 'max:10'],
            'resolution' => ['required', 'string'],
            'aspect_ratio' => ['required', 'string'],

            // قیمت‌گذاری (فقط هزینه توکن گرفته می‌شود)
            'credit_cost' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],

            // فیلدهای غیرفعال اما الزامی برای دیتابیس (از بخش hidden ارسال می‌شوند)
            'delivery_method' => ['required', 'string'],
            'estimated_time' => ['required', 'integer'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'watermark_position' => ['required', 'string'],
            'pricing_model' => ['required', 'string'],
            'price_tier' => ['required', 'string'],
            'discount_percent' => ['required', 'integer'],
            'display_mode' => ['required', 'string'],
            'card_shape' => ['required', 'string'],
            'gallery_layout' => ['required', 'string'],
            'card_badge' => ['nullable', 'string'],
            'platform' => ['required', 'string'],
            'accent_color' => ['required', 'string'],
            'show_before_after' => ['nullable', 'boolean'],
        ]);

        // تبدیل تگ‌ها به آرایه
        $tags = $request->filled('tags')
            ? array_values(array_filter(array_map('trim', explode(',', $request->input('tags')))))
            : [];

        // فال‌بک مدل‌ها
        $fallbackModels = array_values(array_filter($request->input('fallback_models', [])));

        // پردازش آپلود فایل‌ها
        $thumbnailPath = $request->file('thumbnail')?->store('products/thumbnails', 'public');
        $coverPath = $request->file('cover')?->store('products/covers', 'public');

        $sampleImagesPaths = [];
        if ($request->hasFile('sample_images')) {
            foreach ($request->file('sample_images') as $file) {
                $sampleImagesPaths[] = $file->store('products/samples', 'public');
            }
        }

        // ایجاد رکورد در دیتابیس
        Product::create([
            'name_fa' => $validated['name_fa'],
            'name_en' => $validated['name_en'],
            'slug' => Str::slug($validated['slug']),
            'description_fa' => $validated['description_fa'] ?? null,
            'description_en' => $validated['description_en'] ?? null,
            'category' => $validated['category'],
            'subcategory' => $validated['subcategory'] ?? null,
            'status' => $validated['status'],
            'tags' => $tags,
            'is_featured' => $request->boolean('is_featured'),
            'is_new' => $request->boolean('is_new'),
            'is_trending' => $request->boolean('is_trending'),

            'thumbnail_path' => $thumbnailPath,
            'cover_path' => $coverPath,
            'sample_images' => $sampleImagesPaths,
            'media_type' => $validated['media_type'],
            'preview_video_url' => $validated['preview_video_url'] ?? null,

            'primary_model' => $validated['primary_model'],
            'primary_timeout' => $validated['primary_timeout'],
            'primary_type' => $validated['primary_type'],
            'fallback_models' => $fallbackModels,
            'prompt_template' => $validated['prompt_template'],
            'negative_prompt' => $validated['negative_prompt'] ?? null,
            'show_prompt_to_user' => $request->boolean('show_prompt_to_user'),
            'face_swap_enabled' => $request->boolean('face_swap_enabled'),
            'multi_step_pipeline' => $request->boolean('multi_step_pipeline'),
            'input_schema' => [], // طبق درخواست خالی رد می‌شود

            'output_type' => $validated['output_type'],
            'output_format' => $validated['output_format'],
            'output_count' => $validated['output_count'],
            'resolution' => $validated['resolution'],
            'aspect_ratio' => $validated['aspect_ratio'],
            'delivery_method' => $validated['delivery_method'],
            'estimated_time' => $validated['estimated_time'],
            'watermark_enabled' => $request->boolean('watermark_enabled'),
            'watermark_position' => $validated['watermark_position'],

            'pricing_model' => $request->boolean('is_free') ? 'free' : $validated['pricing_model'],
            'credit_cost' => $request->boolean('is_free') ? 0 : ($validated['credit_cost'] ?? 0),
            'price_tier' => $validated['price_tier'],
            'discount_percent' => $validated['discount_percent'],
            'is_free' => $request->boolean('is_free'),

            'display_mode' => $validated['display_mode'],
            'card_shape' => $validated['card_shape'],
            'gallery_layout' => $validated['gallery_layout'],
            'card_badge' => $validated['card_badge'] ?? null,
            'platform' => $validated['platform'],
            'accent_color' => $validated['accent_color'],
            'show_before_after' => $request->boolean('show_before_after'),
        ]);

        return redirect()->route('admin.products')->with('success', 'محصول با موفقیت ثبت شد.');
    }
}