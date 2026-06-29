<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * نمایش لیست محصولات هوش مصنوعی
     */
    public function index()
    {
        $products = Product::latest()->paginate(20);
        
        // ارجاع به ویو جدید در پوشه prompts
        return view('admin.prompts.products', compact('products'));
    }

    /**
     * نمایش فرم ساخت مرحله‌ای محصول جدید
     */
    public function create()
    {
        return view('admin.prompts.products-create', [
            'categories' => Product::categoriesList(),
            'aiModels' => Product::aiModelsList(),
        ]);
    }

    /**
     * ذخیره محصول جدید در دیتابیس (فرم ۴ مرحله‌ای)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // گام ۱: اطلاعات پایه
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug', 'alpha_dash'],
            'description_fa' => ['required', 'string'],
            'category' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,inactive'],

            // گام ۲: رسانه و دموها
            'thumbnail' => ['required', 'image', 'max:5120'],
            'sample_images.*' => ['nullable', 'image', 'max:5120'],
            'show_before_after' => ['nullable', 'boolean'],

            // گام ۳: تنظیمات هوش مصنوعی core
            'primary_model' => ['required', 'string'],
            'media_type' => ['required', 'string'],
            'prompt_template' => ['required', 'string'],
            'negative_prompt' => ['nullable', 'string'],
            'button_text' => ['required', 'string', 'max:100'],

            // گام ۴: قیمت‌گذاری و توکن
            'credit_cost' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
        ]);

        // فرآوری تگ‌ها از رشته به آرایه
        $tags = $request->filled('tags')
            ? array_values(array_filter(array_map('trim', explode(',', $request->input('tags')))))
            : [];

        // آپلود فایل کاور اصلی محصول
        $thumbnailPath = $request->file('thumbnail')?->store('products/thumbnails', 'public');

        // آپلود گالری تصاویر نمونه
        $sampleImagesPaths = [];
        if ($request->hasFile('sample_images')) {
            foreach ($request->file('sample_images') as $file) {
                $sampleImagesPaths[] = $file->store('products/samples', 'public');
            }
        }

        // ایجاد نهایی رکورد در دیتابیس
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
            
            // مقادیر فیلدهای غیرفعال انتخابی (ذخیره پیش‌فرض جهت حفظ ساختار دیتابیس)
            'single_price' => 0,
            'required_subscription_tier' => null,
        ]);

        // ریدایرکت به صفحه اصلی لیست همراه با پیام موفقیت برای دکمه/مودال جدید
        return redirect()
            ->route('admin.products')
            ->with('success', 'محصول جدید با ساختار نهایی با موفقیت در سیستم وطن AI ثبت و فعال گردید.');
    }

    /**
     * نمایش فرم ویرایش محصول
     */
    public function edit(Product $product)
    {
        return view('admin.prompts.products-edit', [
            'product' => $product,
            'categories' => Product::categoriesList(),
            'aiModels' => Product::aiModelsList(),
        ]);
    }

    /**
     * نمایش جزییات تک محصول (در صورت نیاز)
     */
    public function show(Product $product)
    {
        return view('admin.prompts.products-show', compact('product'));
    }

    /**
     * بروزرسانی محصول ویرایش شده در دیتابیس
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            // گام ۱ (قانون یکتایی اسلاگ برای خود این محصول استثنا شده است)
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug,' . $product->id, 'alpha_dash'],
            'description_fa' => ['required', 'string'],
            'category' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,inactive'],

            // گام ۲ (در ویرایش، آپلود تصویر کاور اجباری نیست و می‌تواند خالی باشد)
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'sample_images.*' => ['nullable', 'image', 'max:5120'],
            'show_before_after' => ['nullable', 'boolean'],

            // گام ۳
            'primary_model' => ['required', 'string'],
            'media_type' => ['required', 'string'],
            'prompt_template' => ['required', 'string'],
            'negative_prompt' => ['nullable', 'string'],
            'button_text' => ['required', 'string', 'max:100'],

            // گام ۴
            'credit_cost' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
        ]);

        // فرآوری تگ‌ها
        $tags = $request->filled('tags')
            ? array_values(array_filter(array_map('trim', explode(',', $request->input('tags')))))
            : [];

        // مدیریت آپلود تصویر کاور جدید (اگر فایل جدید انتخاب نشده، همان آدرس قبلی حفظ می‌شود)
        $thumbnailPath = $product->thumbnail_path;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        // مدیریت گالری تصاویر نمونه جدید
        $sampleImagesPaths = $product->sample_images ?? [];
        if ($request->hasFile('sample_images')) {
            // در صورت تمایل به پاک کردن عکس‌های قدیمی، این خط را فعال کنید: $sampleImagesPaths = [];
            foreach ($request->file('sample_images') as $file) {
                $sampleImagesPaths[] = $file->store('products/samples', 'public');
            }
        }

        // بروزرسانی اطلاعات در دیتابیس
        $product->update([
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
        ]);

        return redirect()
            ->route('admin.products')
            ->with('success', 'تغییرات ابزار هوش مصنوعی با موفقیت اعمال و بروزرسانی شد.');
    }

    /**
     * حذف کامل محصول از سیستم
     */
    public function destroy(Product $product)
    {
        $product->delete();
        
        return redirect()
            ->route('admin.products')
            ->with('success', 'محصول مورد نظر با موفقیت از پایگاه داده سیستم حذف گردید.');
    }
}