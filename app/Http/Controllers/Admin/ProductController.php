<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\AiModel;
use App\Models\ProductPromptHistory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * نمایش لیست محصولات با جستجو، فیلترهای پیشرفته، مرتب‌سازی و صفحه‌بندی
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name_fa', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhereJsonContains('tags', $search);
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }
       
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($mediaType = $request->get('media_type')) {
            $query->where('media_type', $mediaType);
        }
        if ($aiModel = $request->get('ai_model')) {
            $query->where('primary_model', $aiModel);
        }
        if ($pricingModel = $request->get('pricing_model')) {
            if ($pricingModel === 'paid') {
                $query->where('pricing_model', '!=', 'free');
            } else {
                $query->where('pricing_model', $pricingModel);
            }
        }
        if ($request->filled('featured'))  $query->where('is_featured', true);
        if ($request->filled('is_new'))    $query->where('is_new', true);
        if ($request->filled('trending'))  $query->where('is_trending', true);

        if ($createdFrom = $request->get('created_from')) $query->whereDate('created_at', '>=', $createdFrom);
        if ($createdTo   = $request->get('created_to'))   $query->whereDate('created_at', '<=', $createdTo);
        if ($updatedFrom = $request->get('updated_from')) $query->whereDate('updated_at', '>=', $updatedFrom);
        if ($updatedTo   = $request->get('updated_to'))   $query->whereDate('updated_at', '<=', $updatedTo);

        switch ($request->get('sort')) {
            case 'oldest': $query->oldest(); break;
            case 'az':     $query->orderBy('name_fa'); break;
            case 'newest':
            default:       $query->latest(); break;
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->paginate($perPage)->withQueryString();

        $activeCount   = Product::where('status', 'active')->count();
        $draftCount    = Product::where('status', 'draft')->count();
        $inactiveCount = Product::where('status', 'inactive')->count();

        $aiModels = AiModel::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $recentlyEdited = Product::orderByDesc('updated_at')->take(3)->get();

        return view('admin.products.index', compact(
            'products', 'activeCount', 'draftCount', 'inactiveCount',
            'aiModels', 'categories', 'recentlyEdited'
        ));
    }

    /**
     * نمایش فرم ساخت محصول جدید
     */
    public function create(Request $request)
    {
        $aiModels = AiModel::where('is_active', true)->latest()->get();

        $duplicateFrom = null;
        if ($request->filled('duplicate')) {
            $duplicateFrom = Product::find($request->get('duplicate'));
        }

        return view('admin.products.create', compact('aiModels', 'duplicateFrom'));
    }

    /**
     * ذخیره محصول جدید در دیتابیس (حل خطای ولیدیشن‌های ساختاری)
     */
   public function store(Request $request)
    {
        // ۱. ولیدیشن کاملاً آزاد و منعطف برای تست سریع
        $request->validate([
            'name_fa' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
        ]);

        // ۲. ساخت یک نمونه جدید از مدل (برای دور زدن محدودیت fillable دیتابیس)
        $product = new Product();

        // ۳. مقداردهی فیلدهای اصلی (اگر در فرم خالی باشند، مقدار پیش‌فرض تست قرار می‌گیرد)
        $product->name_fa = $request->input('name_fa') ?? 'محصول تست ' . rand(100, 999);
        $product->name_en = $request->input('name_en') ?? 'Test Product ' . rand(100, 999);
        
        $slugSource = $request->input('slug') ?? $product->name_en;
        $baseSlug = Str::slug($slugSource);
        $slug = $baseSlug ?: 'test-product-' . rand(100, 999);
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$i);
        }
        $product->slug = $slug;

        // ۴. دسته‌بندی و ریلیشن‌ها
        $categoryId = $request->input('category_id');
        if (!$categoryId) {
            $firstCategory = Category::first();
            $categoryId = $firstCategory ? $firstCategory->id : 1;
            $categoryName = $firstCategory ? $firstCategory->name : 'عمومی';
        } else {
            $categoryName = Category::where('id', $categoryId)->value('name') ?? 'عمومی';
        }
        $product->category_id = $categoryId;
        $product->category = $categoryName;
        $product->subcategory = $request->input('subcategory');

        // ۵. مدیریت فایل‌ها و تصاویر با جایگزین امن
        $duplicateSource = $request->filled('duplicate_from') ? Product::find($request->input('duplicate_from')) : null;

        if ($request->hasFile('thumbnail')) {
            $product->thumbnail = $request->file('thumbnail')->store('products/thumbnails', 'public');
        } elseif ($duplicateSource && $duplicateSource->thumbnail && Storage::disk('public')->exists($duplicateSource->thumbnail)) {
            $product->thumbnail = $this->copyDuplicateFile($duplicateSource->thumbnail, 'products/thumbnails');
        } else {
            $product->thumbnail = 'products/thumbnails/default_placeholder.jpg'; 
        }

        if ($request->hasFile('cover')) {
            $product->cover = $request->file('cover')->store('products/covers', 'public');
        } elseif ($duplicateSource && $duplicateSource->cover && Storage::disk('public')->exists($duplicateSource->cover)) {
            $product->cover = $this->copyDuplicateFile($duplicateSource->cover, 'products/covers');
        }

        if ($request->hasFile('new_product_icon')) {
            $product->new_product_icon = $request->file('new_product_icon')->store('product_icons', 'public');
        } elseif ($duplicateSource && $duplicateSource->new_product_icon && Storage::disk('public')->exists($duplicateSource->new_product_icon)) {
            $product->new_product_icon = $this->copyDuplicateFile($duplicateSource->new_product_icon, 'product_icons');
        }

        $samplePaths = [];
        if ($request->hasFile('sample_outputs')) {
            foreach ($request->file('sample_outputs') as $file) {
                $samplePaths[] = $file->store('products/samples', 'public');
            }
        }
        $product->sample_outputs = $samplePaths;

        // ۶. فیلدهای سیستمی و هوش مصنوعی
        $product->primary_model = $request->input('primary_model') ?? AiModel::first()?->openrouter_model_id ?? 'stabilityai/stable-diffusion-3';
        $product->fallback_models = $request->input('fallback_models', []);
        $product->prompt_template = $request->input('prompt_template') ?? 'A high tech digital art illustration of {prompt}';
        $product->input_schema = $request->input('input_schema', []);
        $product->timeout = $request->input('timeout') ?? 60;
        $product->pipeline_type = $request->input('pipeline_type') ?? 'image_generation';

        // ۷. وضعیت‌ها و چک‌باکس‌ها
        $product->status = $request->input('status') ?? 'draft';
        $product->new_status = $request->input('new_status') ?? 'draft';
        $product->new_display_order = $request->input('new_display_order') ?? 1;
        $product->new_internal_code = $request->input('new_internal_code');
        $product->new_admin_note = $request->input('new_admin_note');
        
        $product->is_featured = $request->has('is_featured');
        $product->is_new = $request->has('is_new');
        $product->is_trending = $request->has('is_trending');
        $product->watermark_enabled = $request->has('watermark_enabled');
        $product->new_is_premium = $request->has('new_is_premium');
        $product->new_is_recommended = $request->has('new_is_recommended');
        $product->new_is_beta = $request->has('new_is_beta');
        $product->new_show_free_badge = $request->has('new_show_free_badge');

        // ۸. تنظیمات ظاهری، فنی و قیمت‌گذاری
        $product->media_type = $request->input('media_type') ?? 'photo';
        $product->preview_video_url = $request->input('preview_video_url');
        $product->watermark_position = $request->input('watermark_position') ?? 'corner';
        $product->pricing_model = $request->input('pricing_model') ?? 'per_credit';
        $product->credit_cost = $request->input('credit_cost') ?? 5;
        $product->display_mode = $request->input('display_mode') ?? 'card';
        $product->card_shape = $request->input('card_shape') ?? 'portrait';
        $product->gallery_layout = $request->input('gallery_layout') ?? 'grid';
        $product->card_label = $request->input('card_label');
        $product->output_type = $request->input('output_type') ?? 'image';
        $product->output_format = $request->input('output_format') ?? 'jpg';
        $product->output_count = $request->input('output_count') ?? 1;
        $product->resolution = $request->input('resolution') ?? '1024×1024';
        $product->aspect_ratio = $request->input('aspect_ratio') ?? '1:1';
        $product->delivery_method = $request->input('delivery_method') ?? 'instant';
        $product->estimated_time = $request->input('estimated_time') ?? 30;
        $product->price_tier = $request->input('price_tier') ?? 'standard';
        $product->discount_percentage = $request->input('discount_percentage') ?? 0;
        $product->platform = $request->input('platform') ?? 'both';
        $product->accent_color = $request->input('accent_color') ?? '#a07af5';
        $product->tags = $request->input('tags', []);

        // ۹. فیلدهای فاز جدید توسعه
        $product->new_card_color = $request->input('new_card_color') ?? '#A07AF5';
        $product->new_gallery_preview_mode = $request->input('new_gallery_preview_mode') ?? 'grid';
        $product->new_watermark_corner_precise = $request->input('new_watermark_corner_precise') ?? 'tr';
        $product->new_watermark_opacity = $request->input('new_watermark_opacity') ?? 70;
        $product->new_watermark_size = $request->input('new_watermark_size') ?? 30;
        $product->new_watermark_type = $request->input('new_watermark_type') ?? 'logo';
        $product->new_watermark_text_color = $request->input('new_watermark_text_color') ?? '#FFFFFF';
        $product->new_min_credit_required = $request->input('new_min_credit_required') ?? 0;
        $product->new_max_run_per_user = $request->input('new_max_run_per_user');
        $product->new_price_custom_label = $request->input('new_price_custom_label');

        // ۱۰. ذخیره نهایی در دیتابیس
        $product->save();

        return redirect()->route('admin.products')->with('success', 'محصول جدید با موفقیت و بدون خطای ساختاری ثبت شد.');
    }

    /**
     * نمایش فرم ویرایش محصول
     */
    public function edit(Product $product)
    {
        $aiModels = AiModel::where('is_active', true)->latest()->get();
        return view('admin.products.edit', compact('product', 'aiModels'));
    }

    /**
     * به‌روزرسانی اطلاعات محصول
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name_fa' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
            'category_id' => 'nullable|integer',
            'primary_model' => 'nullable|string',
            'prompt_template' => 'nullable|string',
            'description_fa' => 'nullable|string',
            'description_en' => 'nullable|string',
            'status' => 'nullable|in:active,draft,inactive',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'sample_outputs' => 'nullable|array',
            'media_type' => 'nullable|in:photo,video,both',
            'preview_video_url' => 'nullable|url',
            'pipeline_type' => 'nullable|string',
            'timeout' => 'nullable|integer',
            'watermark_position' => 'nullable|string',
            'pricing_model' => 'nullable|in:free,per_credit,subscription',
            'credit_cost' => 'nullable|integer',
            'display_mode' => 'nullable|string',
            'card_shape' => 'nullable|string',
            'gallery_layout' => 'nullable|string',
            'card_label' => 'nullable|string|max:100',
            'new_status' => 'nullable|in:draft,active,inactive',
            'new_display_order' => 'nullable|integer',
            'new_internal_code' => 'nullable|string|max:100',
            'new_admin_note' => 'nullable|string',
            'new_product_icon' => 'nullable|file|mimes:svg,png|max:2048',
            'new_card_color' => 'nullable|string',
            'new_gallery_preview_mode' => 'nullable|string',
            'new_watermark_corner_precise' => 'nullable|string',
            'new_watermark_opacity' => 'nullable|integer',
            'new_watermark_size' => 'nullable|integer',
            'new_watermark_type' => 'nullable|string',
            'new_watermark_text_color' => 'nullable|string',
            'new_min_credit_required' => 'nullable|integer',
            'new_max_run_per_user' => 'nullable|integer',
            'new_price_custom_label' => 'nullable|string|max:100',
        ]);

        if (isset($validated['category_id'])) {
            $validated['category'] = Category::where('id', $validated['category_id'])->value('name') ?? 'عمومی';
        }

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
            $validated['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        if ($request->hasFile('cover')) {
            if ($product->cover) Storage::disk('public')->delete($product->cover);
            $validated['cover'] = $request->file('cover')->store('products/covers', 'public');
        }

        if ($request->hasFile('new_product_icon')) {
            if ($product->new_product_icon) Storage::disk('public')->delete($product->new_product_icon);
            $validated['new_product_icon'] = $request->file('new_product_icon')->store('product_icons', 'public');
        }

        if ($request->hasFile('sample_outputs')) {
            $newSamples = [];
            foreach ($request->file('sample_outputs') as $file) {
                $newSamples[] = $file->store('products/samples', 'public');
            }
            $existingSamples = is_array($product->sample_outputs) ? $product->sample_outputs : [];
            $validated['sample_outputs'] = array_merge($existingSamples, $newSamples);
        }

        $validated['is_featured'] = $request->has('is_featured');
        $validated['is_new'] = $request->has('is_new');
        $validated['is_trending'] = $request->has('is_trending');
        $validated['watermark_enabled'] = $request->has('watermark_enabled');
        $validated['new_is_premium'] = $request->has('new_is_premium');
        $validated['new_is_recommended'] = $request->has('new_is_recommended');
        $validated['new_is_beta'] = $request->has('new_is_beta');
        $validated['new_show_free_badge'] = $request->has('new_show_free_badge');

        $validated['slug'] = Str::slug($validated['slug']);

        if ($request->filled('prompt_template') && $product->prompt_template !== $request->input('prompt_template')) {
            $currentMaxVersion = ProductPromptHistory::where('product_id', $product->id)->max('version_number') ?? 0;
            ProductPromptHistory::create([
                'product_id'     => $product->id,
                'prompt_text'    => $product->prompt_template,
                'version_number' => $currentMaxVersion + 1,
                'user_id'        => auth()->id(),
            ]);
        }

        $product->update($validated);

        return redirect()->route('admin.products')->with('success', 'تغییرات با موفقیت ثبت شد.');
    }

    /**
     * حذف محصول به همراه فایل‌های فیزیکی
     */
    public function destroy(Product $product)
    {
        if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
        if ($product->cover) Storage::disk('public')->delete($product->cover);
        if ($product->new_product_icon) Storage::disk('public')->delete($product->new_product_icon);

        if (is_array($product->sample_outputs)) {
            foreach ($product->sample_outputs as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $product->delete();
        return redirect()->route('admin.products')->with('success', 'محصول حذف شد.');
    }

    /**
     * کپی محصول
     */
    public function duplicate(Product $product)
    {
        $clone = $product->replicate();
        $clone->name_fa = $product->name_fa . ' (کپی)';
        $clone->name_en = $product->name_en . '-copy';

        $baseSlug = Str::slug($product->slug . '-copy');
        $slug = $baseSlug;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$i);
        }
        $clone->slug = $slug;
        $clone->status = 'draft';
        $clone->save();

        return redirect()->route('admin.products')->with('success', 'کپی محصول ساخته شد.');
    }

    public function toggleStatus(Product $product)
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return request()->wantsJson() 
            ? response()->json(['status' => $product->status]) 
            : redirect()->route('admin.products');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action'     => 'required|in:activate,deactivate,delete,change_category',
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $products = Product::whereIn('id', $validated['ids']);

        switch ($validated['action']) {
            case 'activate':
                $products->update(['status' => 'active']);
                break;
            case 'deactivate':
                $products->update(['status' => 'inactive']);
                break;
            case 'change_category':
                $categoryName = Category::where('id', $validated['category_id'])->value('name') ?? 'عمومی';
                $products->update(['category_id' => $validated['category_id'], 'category' => $categoryName]);
                break;
            case 'delete':
                foreach ($products->get() as $product) {
                    if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
                }
                $products->delete();
                break;
        }

        return redirect()->route('admin.products')->with('success', 'عملیات گروهی انجام شد.');
    }

    private function copyDuplicateFile(string $sourcePath, string $targetDir): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = $targetDir . '/' . (string) Str::uuid() . '.' . $extension;
        Storage::disk('public')->copy($sourcePath, $newPath);
        return $newPath;
    }
}