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
    // آدرس ویو از admin.products به admin.prompts.products تغییر یافت:
    return view('admin.prompts.products', compact('products')); 
}

    public function create()
    {
        return view('admin.prompts.products-create', [
            'categories' => Product::categoriesList(),
            'aiModels' => Product::aiModelsList(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // گام ۱
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug', 'alpha_dash'],
            'description_fa' => ['required', 'string'],
            'category' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,inactive'],

            // گام ۲
            'thumbnail' => ['required', 'image', 'max:5120'],
            'sample_images.*' => ['nullable', 'image', 'max:5120'],
            'show_before_after' => ['nullable', 'boolean'],

            // ... گام ۳
            'primary_model' => ['required', 'string'],
            'media_type' => ['required', 'string'],
            'prompt_template' => ['required', 'string'],
            'negative_prompt' => ['nullable', 'string'],
            'button_text' => ['required', 'string', 'max:100'],

            // گام ۴ (سیستم توکنی فعلی)
            'credit_cost' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
        ]);

        // فرآوری تگ‌ها
        $tags = $request->filled('tags')
            ? array_values(array_filter(array_map('trim', explode(',', $request->input('tags')))))
            : [];

        // آپلود فایل کاور محصول
        $thumbnailPath = $request->file('thumbnail')?->store('products/thumbnails', 'public');

        // آپلود گالری تصاویر نمونه
        $sampleImagesPaths = [];
        if ($request->hasFile('sample_images')) {
            foreach ($request->file('sample_images') as $file) {
                $sampleImagesPaths[] = $file->store('products/samples', 'public');
            }
        }

        // ایجاد نهایی محصول در دیتابیس
        Product::create([
            'name_fa' => $validated['name_fa'],
            'name_en' => $validated['name_en'],
            'slug' => Str::slug($validated['slug']),
            'description_fa' => $validated['description_fa'],
            'category' => $validated['category'],
            'tags' => $tags,
            'status' => $validated['status'],

            'thumbnail_path' => $thumbnailPath,
            'sample_images' => $sampleImagesPaths,
            'show_before_after' => $request->boolean('show_before_after'),

            'primary_model' => $validated['primary_model'],
            'media_type' => $validated['media_type'],
            'prompt_template' => $validated['prompt_template'],
            'negative_prompt' => $validated['negative_prompt'] ?? null,
            'button_text' => $validated['button_text'],

            'is_free' => $request->boolean('is_free'),
            'credit_cost' => $request->boolean('is_free') ? 0 : ($validated['credit_cost'] ?? 0),
            
            // مقادیر فیلدهای غیرفعال انتخابی آقا محسن (ذخیره با مقادیر امن جهت جلوگیری از خرابی ساختار)
            'single_price' => 0,
            'required_subscription_tier' => null,
        ]);

        return redirect()->route('admin.products')->with('success', 'محصول جدید با ساختار نهایی با موفقیت ایجاد شد.');
    }
}