<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * نمایش لیست محصولات با فیلتر، جستجو و صفحه‌بندی
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_fa', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        return view('admin.products.index', [
            'products'      => $products,
            'activeCount'   => Product::where('status', 'active')->count(),
            'draftCount'    => Product::where('status', 'draft')->count(),
            'inactiveCount' => Product::where('status', 'inactive')->count(),
        ]);
    }

    /**
     * نمایش فرم ثبت محصول جدید
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * ذخیره محصول جدید در دیتابیس
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name_fa'           => 'required|string|max:255',
            'name_en'           => 'required|string|max:255',
            'slug'              => 'required|string|max:255|unique:products,slug',
            'description_fa'    => 'nullable|string',
            'description_en'    => 'nullable|string',
            'category'          => 'required|string',
            'subcategory'       => 'nullable|string',
            'status'            => 'required|in:draft,active,inactive',
            'tags'              => 'nullable|array',
            'is_featured'       => 'boolean',
            'is_new'            => 'boolean',
            'is_trending'       => 'boolean',

            'thumbnail'         => 'nullable|image|max:4096',
            'cover_image'       => 'nullable|image|max:4096',
            'sample_images.*'   => 'nullable|image|max:4096',
            'media_type'        => 'nullable|string',
            'preview_video_url' => 'nullable|url',

            'primary_model'     => 'required|string',
            'fallback_models'   => 'nullable|array',
            'prompt_template'   => 'required|string',
            'negative_prompt'   => 'nullable|string',
            'show_prompt_to_user'   => 'boolean',
            'face_swap_enabled'     => 'boolean',
            'multi_step_pipeline'   => 'boolean',
            'input_schema'      => 'nullable|array',

            'output_type'       => 'required|string',
            'output_format'     => 'nullable|string',
            'output_count'      => 'nullable|integer|min:1|max:10',
            'resolution'        => 'nullable|string',
            'aspect_ratio'      => 'nullable|string',
            'delivery_method'   => 'nullable|string',
            'estimated_time'    => 'nullable|integer',
            'watermark_enabled' => 'boolean',
            'watermark_position'=> 'nullable|string',

            'pricing_model'     => 'required|in:per_credit,free,subscription_only',
            'credit_cost'       => 'nullable|integer|min:0',
            'price_tier'        => 'nullable|string',
            'discount_percent'  => 'nullable|integer|min:0|max:100',
            'is_free'           => 'boolean',

            'display_mode'      => 'nullable|string',
            'card_shape'        => 'nullable|string',
            'gallery_layout'    => 'nullable|string',
            'card_badge'        => 'nullable|string',
            'platform'          => 'nullable|string',
            'accent_color'      => 'nullable|string',
            'show_before_after' => 'boolean',
        ]);

        if (empty($validatedData['slug'])) {
            $validatedData['slug'] = Str::slug($validatedData['name_en']);
        }

        if ($request->hasFile('thumbnail')) {
            $validatedData['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        if ($request->hasFile('cover_image')) {
            $validatedData['cover_image'] = $request->file('cover_image')->store('products/covers', 'public');
        }

        if ($request->hasFile('sample_images')) {
            $paths = [];
            foreach ($request->file('sample_images') as $img) {
                $paths[] = $img->store('products/samples', 'public');
            }
            $validatedData['sample_images'] = $paths;
        }

        foreach (['tags', 'fallback_models', 'input_schema', 'sample_images'] as $jsonField) {
            if (isset($validatedData[$jsonField]) && is_array($validatedData[$jsonField])) {
                $validatedData[$jsonField] = json_encode($validatedData[$jsonField]);
            }
        }

        Product::create($validatedData);

        return redirect()->route('admin.products.index')
                         ->with('success', 'محصول جدید با موفقیت ثبت و پیکربندی شد.');
    }

    /**
     * نمایش جزئیات یک محصول
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
     * بروزرسانی محصول
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'name_fa'        => 'required|string|max:255',
            'name_en'        => 'required|string|max:255',
            'slug'           => 'required|string|max:255|unique:products,slug,' . $product->id,
            'description_fa' => 'nullable|string',
            'description_en' => 'nullable|string',
            'category'       => 'required|string',
            'subcategory'    => 'nullable|string',
            'status'         => 'required|in:draft,active,inactive',
            'is_featured'    => 'boolean',
            'is_new'         => 'boolean',
            'is_trending'    => 'boolean',
            'pricing_model'  => 'required|in:per_credit,free,subscription_only',
            'credit_cost'    => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('thumbnail')) {
            $validatedData['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        $product->update($validatedData);

        return redirect()->route('admin.products.index')
                         ->with('success', 'محصول با موفقیت بروزرسانی شد.');
    }

    /**
     * حذف محصول
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
                         ->with('success', 'محصول با موفقیت حذف شد.');
    }
}