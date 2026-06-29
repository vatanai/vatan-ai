<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * نمایش لیست محصولات در پنل مدیریت
     */
    public function index()
    {
        $products = Product::latest()->get();
        return view('admin.products.index', compact('products'));
    }

    /**
     * نمایش فرم ساخت محصول جدید (۳ مرحله‌ای)
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * ذخیره داده‌های ارسالی از فرم جادویی هوش مصنوعی
     */
    public function store(Request $request)
    {
        // ۱. اعتبارسنجی دقیق تمام داده‌های فرم بر اساس گام‌های ۱ تا ۳
        $validated = $request->validate([
            // گام اول: هویت و رسانه
            'name_fa' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'description_fa' => 'nullable|string',
            'description_en' => 'nullable|string',
            'category' => 'required|string',
            'subcategory' => 'nullable|string',
            'status' => 'required|in:active,draft,inactive',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'is_featured' => 'nullable|boolean',
            'is_new' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096', // حداکثر ۴ مگابایت
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',     // حداکثر ۸ مگابایت
            'sample_outputs' => 'nullable|array',
            'sample_outputs.*' => 'image|mimes:jpeg,png,jpg,webp|max:4096',
            'media_type' => 'required|in:photo,video,both',
            'preview_video_url' => 'nullable|url',

            // گام دوم: پایپ‌لاین هوش مصنوعی
            'primary_model' => 'required|string',
            'timeout' => 'required|integer|min:1',
            'pipeline_type' => 'required|in:image_generation,image_editing,text_generation',
            'fallback_models' => 'nullable|array',
            'fallback_models.*' => 'string',
            'prompt_template' => 'required|string',
            'input_schema' => 'nullable|array',
            'input_schema.*.field_id' => 'required|string',
            'input_schema.*.label_fa' => 'required|string',
            'input_schema.*.type' => 'required|string',
            'input_schema.*.required' => 'required|in:0,1,true,false',

            // گام سوم: خروجی و قیمت
            'watermark_enabled' => 'nullable|boolean',
            'watermark_position' => 'required|in:corner,center,none',
            'pricing_model' => 'required|in:free,per_credit,subscription',
            'credit_cost' => 'nullable|required_if:pricing_model,per_credit|integer|min:0',
            'display_mode' => 'required|in:card,featured,simple',
            'card_shape' => 'required|in:portrait,landscape,square',
            'gallery_layout' => 'required|in:grid,masonry,slider',
            'card_label' => 'nullable|string|max:100',
        ]);

        // ۲. آپلود اختصاصی تصاویر در دیسک public
        $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');
        
        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('products/covers', 'public');
        }

        // پردازش تصاویر چندگانه نمونه خروجی (Sample Outputs)
        $samplePaths = [];
        if ($request->hasFile('sample_outputs')) {
            foreach ($request->file('sample_outputs') as $file) {
                $samplePaths[] = $file->store('products/samples', 'public');
            }
        }

        // ۳. ساخت آرایه نهایی داده‌ها و بررسی وضعیت چک‌باکس‌ها
        $productData = [
            'name_fa' => $validated['name_fa'],
            'name_en' => $validated['name_en'],
            'slug' => Str::slug($validated['slug']),
            'description_fa' => $validated['description_fa'],
            'description_en' => $validated['description_en'],
            'category' => $validated['category'],
            'subcategory' => $validated['subcategory'],
            'status' => $validated['status'],
            
            // آرایه‌ها به صورت خام ذخیره می‌شوند (توسط کست دیتابیس در مدل هندل می‌شوند)
            'tags' => $validated['tags'] ?? [],
            'fallback_models' => $validated['fallback_models'] ?? [],
            'input_schema' => $validated['input_schema'] ?? [],
            'sample_outputs' => $samplePaths,

            // مدیریت مقادیر سوئیچ‌ها و چک‌باکس‌ها
            'is_featured' => $request->has('is_featured'),
            'is_new' => $request->has('is_new'),
            'is_trending' => $request->has('is_trending'),
            'watermark_enabled' => $request->has('watermark_enabled'),

            'thumbnail' => $thumbnailPath,
            'cover' => $coverPath,
            'media_type' => $validated['media_type'],
            'preview_video_url' => $validated['preview_video_url'],

            'primary_model' => $validated['primary_model'],
            'timeout' => $validated['timeout'],
            'pipeline_type' => $validated['pipeline_type'],
            'prompt_template' => $validated['prompt_template'],

            'watermark_position' => $validated['watermark_position'],
            'pricing_model' => $validated['pricing_model'],
            'credit_cost' => $validated['pricing_model'] === 'per_credit' ? ($validated['credit_cost'] ?? 0) : 0,
            
            'display_mode' => $validated['display_mode'],
            'card_shape' => $validated['card_shape'],
            'gallery_layout' => $validated['gallery_layout'],
            'card_label' => $validated['card_label'],
        ];

        // ۴. ایجاد و ذخیره محصول جدید
        Product::create($productData);

        // ۵. بازگشت به لیست محصولات همراه با پیام موفقیت
        return redirect()
            ->route('admin.products.index')
            ->with('success', 'محصول هوش مصنوعی جدید با موفقیت ثبت شد.');
    }

    /**
     * نمایش جزئیات یک محصول (در صورت نیاز)
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * نمایش فرم ویرایش محصول
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * به‌روزرسانی محصول در دیتابیس
     */
    public function update(Request $request, Product $product)
    {
        // در مراحل بعدی پیاده‌سازی خواهد شد
    }

    /**
     * حذف محصول از سیستم
     */
    public function destroy(Product $product)
    {
        // حذف فایل‌های فیزیکی محصول از استوریج جهت بهینه‌سازی فضا
        if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
        if ($product->cover) Storage::disk('public')->delete($product->cover);
        
        if (is_array($product->sample_outputs)) {
            foreach ($product->sample_outputs as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'محصول و تمامی فایل‌های مربوطه با موفقیت حذف شدند.');
    }
}