<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::latest()->get();
        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ۱. ولیدیشن فیلدها اولیه
        $validatedData = $request->validate([
            'name_fa' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug'    => 'required|string|unique:products,slug',
            'category'=> 'required|string',
            'status'  => 'required|in:draft,active,inactive',
        ]);

        // ۲. ایجاد رکورد اولیه محصول برای به دست آوردن ID
        $product = new Product();
        $product->name_fa = $request->input('name_fa');
        $product->name_en = $request->input('name_en');
        $product->slug = $request->input('slug');
        $product->description_fa = $request->input('description_fa');
        $product->description_en = $request->input('description_en');
        $product->category = $request->input('category');
        $product->subcategory = $request->input('subcategory');
        $product->status = $request->input('status');
        
        // ذخیره فیلدهای کاستوم و سویچ‌ها
        $product->is_featured = $request->has('is_featured') ? 1 : 0;
        $product->is_new = $request->has('is_new') ? 1 : 0;
        $product->is_trending = $request->has('is_trending') ? 1 : 0;
        $product->show_prompt_to_user = $request->has('show_prompt_to_user') ? 1 : 0;
        $product->face_swap_enabled = $request->has('face_swap_enabled') ? 1 : 0;
        $product->multi_step_pipeline = $request->has('multi_step_pipeline') ? 1 : 0;
        $product->watermark_enabled = $request->has('watermark_enabled') ? 1 : 0;
        $product->is_free = $request->has('is_free') ? 1 : 0;
        $product->show_before_after = $request->has('show_before_after') ? 1 : 0;

        // ذخیره فیلدهای متنی و تنظیمی هوش مصنوعی و خروجی
        $product->primary_model = $request->input('primary_model');
        $product->prompt_template = $request->input('prompt_template');
        $product->negative_prompt = $request->input('negative_prompt');
        $product->output_type = $request->input('output_type');
        $product->output_format = $request->input('output_format');
        $product->output_count = $request->input('output_count', 1);
        $product->resolution = $request->input('resolution');
        $product->aspect_ratio = $request->input('aspect_ratio');
        $product->delivery_method = $request->input('delivery_method');
        $product->estimated_time = $request->input('estimated_time', 30);
        $product->watermark_position = $request->input('watermark_position');
        $product->pricing_model = $request->input('pricing_model');
        $product->price_credit = $request->input('price_credit', 0);
        $product->price_tier = $request->input('price_tier');
        $product->discount_percentage = $request->input('discount_percentage', 0);
        $product->display_mode = $request->input('display_mode');
        $product->card_shape = $request->input('card_shape');
        $product->gallery_layout = $request->input('gallery_layout');
        $product->card_label = $request->input('card_label');
        $product->platform = $request->input('platform');
        $product->accent_color = $request->input('accent_color', '#a07af5');

        // پردازش ساختارهای داینامیک ارسالی به صورت JSON از فرانت‌اند
        $product->tags = $request->input('tags'); // رشته یا آرایه به صورت کاما پارتد
        $product->fallback_models = $request->input('fallback_models'); // رشته JSON آرایه
        $product->input_schema = $request->input('input_schema'); // رشته JSON کامپایل شده اسکیما

        $product->save(); // ایجاد اولیه محصول در دیتابیس

        // ۳. پردازش و ذخیره تصاویر در ساختار پوشه‌بندی اختصاصی با شناسه محصول (Product ID)
        $productId = $product->id;

        // آپلود تامبنیل (Thumbnail)
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store("products/{$productId}/thumbnail", 'public');
            $product->thumbnail = $thumbnailPath;
        }

        // آپلود کاور (Cover)
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store("products/{$productId}/cover", 'public');
            $product->cover = $coverPath;
        }

        // آپلود نمونه خروجی‌های گالری (Multiple Output Samples)
        if ($request->hasFile('samples')) {
            $samplePaths = [];
            foreach ($request->file('samples') as $sampleFile) {
                $path = $sampleFile->store("products/{$productId}/samples", 'public');
                $samplePaths[] = $path;
            }
            // ذخیره مسیرها به صورت آرایه JSON در دیتابیس
            $product->samples = json_encode($samplePaths);
        }

        // ذخیره نهایی فیلدهای مربوط به آدرس فایل‌ها
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت همراه با تصاویر در ساختار درختی ذخیره شد.',
            'product_id' => $productId
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // منطق مشابه استور با ویرایش مسیرها در صورت آپلود فایل جدید
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // حذف فایلهای داخل پوشه استوریج محصول قبل از حذف رکورد
        Storage::disk('public')->deleteDirectory("products/{$product->id}");
        $product->delete();
        return redirect()->route('admin.products.index');
    }
}