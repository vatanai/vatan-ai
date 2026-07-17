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
     * نمایش فرم ساخت محصول جدید — و همچنین فرم ویرایش محصول موجود.
     * مسیر ویرایش جداگانه (products/{product}/edit) کامل حذف شده؛ ویرایش هم از همین
     * صفحه با پارامتر اختیاری محصول انجام می‌شود، مثلاً: /admin/products/create/52
     */
    public function create(Request $request, ?Product $product = null)
    {
        $aiModels = AiModel::where('is_active', true)->latest()->get();

        $duplicateFrom = null;
        $isEdit = false;

        if ($product) {
            // حالت ویرایش — همان فرم ثبت محصول، با مقادیر از پیش پرشده از روی محصول موجود
            $duplicateFrom = $product;
            $isEdit = true;
        } elseif ($request->filled('duplicate')) {
            $duplicateFrom = Product::find($request->get('duplicate'));
        }

        return view('admin.products.create', [
            'aiModels' => $aiModels,
            'duplicateFrom' => $duplicateFrom,
            'product' => $product,
            'isEdit' => $isEdit,
        ]);
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
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
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

        // ۳.۱ ساخت خودکار کد ۶ رقمی یکتا برای محصول — این کد پیش از اسلاگ در لینک عمومی محصول قرار می‌گیرد
        // مثال: aivatan.com/app/product/546834-{$slug}
        do {
            $productCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Product::where('product_code', $productCode)->exists());
        $product->product_code = $productCode;

        // ۴. دسته‌بندی چندگانه (سرشاخه + زیرشاخه‌ها)
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('category_ids', [])))));
        if (empty($categoryIds) && $request->filled('category_id')) {
            $categoryIds = [(int) $request->input('category_id')];
        }
        $primaryId = $categoryIds[0] ?? (Category::first()->id ?? null);
        $product->category_id = $primaryId;
        $product->category = $primaryId ? (Category::where('id', $primaryId)->value('name') ?? 'عمومی') : 'عمومی';
        $product->subcategory = $request->input('subcategory');

        // ۵. مدیریت فایل‌ها و تصاویر با جایگزین امن
        $duplicateSource = $request->filled('duplicate_from') ? Product::find($request->input('duplicate_from')) : null;

        if ($request->hasFile('cover')) {
            $product->cover = $request->file('cover')->store('products/covers', 'public');
        } elseif ($duplicateSource && $duplicateSource->cover && Storage::disk('public')->exists($duplicateSource->cover)) {
            $product->cover = $this->copyDuplicateFile($duplicateSource->cover, 'products/covers');
        }

        if ($request->hasFile('thumbnail')) {
            $product->thumbnail = $request->file('thumbnail')->store('products/thumbnails', 'public');
        } elseif ($duplicateSource && $duplicateSource->thumbnail && Storage::disk('public')->exists($duplicateSource->thumbnail)) {
            $product->thumbnail = $this->copyDuplicateFile($duplicateSource->thumbnail, 'products/thumbnails');
        } elseif ($product->cover) {
            // به جای مسیر جعلی قبلی (default_placeholder.jpg که اصلا وجود نداشت و باعث سیاه شدن عکس در هوم/اکسپلور می شد)،
            // همان تصویر Cover را به عنوان Thumbnail هم کپی می کنیم تا کارت ها همیشه عکس واقعی داشته باشند.
            $product->thumbnail = $this->copyDuplicateFile($product->cover, 'products/thumbnails');
        } else {
            $product->thumbnail = null;
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

        // ۵.۱ عکس‌های قبل — تصاویر خامی که مدل با آن‌ها ساخته شده (جایگزین فیلد قدیمی Thumbnail در فرم)
        $beforeImagePaths = [];
        if ($request->hasFile('before_images')) {
            foreach ($request->file('before_images') as $file) {
                $beforeImagePaths[] = $file->store('products/before_images', 'public');
            }
        } elseif ($duplicateSource && !empty($duplicateSource->before_images)) {
            foreach ($duplicateSource->before_images as $existingPath) {
                if (Storage::disk('public')->exists($existingPath)) {
                    $beforeImagePaths[] = $this->copyDuplicateFile($existingPath, 'products/before_images');
                }
            }
        }
        $product->before_images = $beforeImagePaths;

        // ۶. فیلدهای سیستمی و هوش مصنوعی
        $product->primary_model = $request->input('primary_model') ?? AiModel::first()?->openrouter_model_id ?? 'stabilityai/stable-diffusion-3';
        $product->fallback_models = $request->input('fallback_models', []);
        $product->prompt_template = $request->input('prompt_template') ?? 'A high tech digital art illustration of {prompt}';
        $product->input_schema = $request->input('input_schema', []);
        $product->timeout = $request->input('timeout') ?? 60;
        $product->pipeline_type = $request->input('pipeline_type') ?? 'image_generation';

        // ۶.۱ پارامترهای واقعی کیفیت + پرامپت‌های تکمیلی
        $product->system_prompt    = $request->input('system_prompt');
        $product->negative_prompt  = $request->input('negative_prompt');
        $product->seed             = $request->filled('seed') ? (int) $request->input('seed') : null;
        $providerOptionsRaw        = $request->input('provider_options');
        $product->provider_options = $providerOptionsRaw ? (json_decode($providerOptionsRaw, true) ?: null) : null;

        // ۶.۲ نوع سوژه و حفظ هویت (چهره/هیکل)
        $product->subject_type          = $request->input('subject_type') ?? 'generic';
        $product->identity_preservation = $request->has('identity_preservation');
        $product->identity_strength     = $request->input('identity_strength') ?? 80;
        $product->preserve_body         = $request->has('preserve_body');
        $product->identity_instructions = $request->input('identity_instructions');
        $product->min_reference_images  = $request->input('min_reference_images') ?? 0;
        $product->max_reference_images  = $request->input('max_reference_images') ?? 1;

        // ۶.۳ سئو
        $product->meta_title       = $request->input('meta_title');
        $product->meta_description = $request->input('meta_description');
        $product->meta_keywords    = $request->input('meta_keywords');
        if ($request->hasFile('og_image')) {
            $product->og_image = $request->file('og_image')->store('products/seo', 'public');
        } elseif ($duplicateSource && $duplicateSource->og_image && Storage::disk('public')->exists($duplicateSource->og_image)) {
            $product->og_image = $this->copyDuplicateFile($duplicateSource->og_image, 'products/seo');
        }

        // ۷. وضعیت‌ها و چک‌باکس‌ها
        $product->status = $request->input('status') ?? 'draft';
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
        $product->output_variants = $this->buildOutputVariants($request, (bool) $duplicateSource);
        $product->resolution = $request->input('resolution') ?? '1024×1024';
        $product->aspect_ratio = $request->input('aspect_ratio') ?? '1:1';
        $product->delivery_method = $request->input('delivery_method') ?? 'instant';
        $product->estimated_time = $request->input('estimated_time') ?? 30;
        $product->price_tier = $request->input('price_tier') ?? 'standard';
        $product->discount_percentage = $request->input('discount_percentage') ?? 0;
        $product->platform = $request->input('platform') ?? 'both';
        $product->accent_color = $request->input('accent_color') ?? '#a07af5';
        $product->tags = $request->input('tags', []);

        // حالت‌های نمایش کاشی در اکسپلور — حداقل یکی، در غیر این صورت همه
        $exploreTiles = array_values(array_intersect(['1x1','2x2','1x2','2x1'], (array) $request->input('explore_tiles', [])));
        $product->explore_tiles = $exploreTiles ?: ['1x1','2x2','1x2','2x1'];

        // ۹. فیلدهای فاز جدید توسعه
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

        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }

        return redirect()->route('admin.products')->with('success', 'محصول جدید با موفقیت و بدون خطای ساختاری ثبت شد.');
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
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'primary_model' => 'nullable|string',
            'prompt_template' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'negative_prompt' => 'nullable|string',
            'seed' => 'nullable|integer',
            'provider_options' => 'nullable|string',
            'subject_type' => 'nullable|in:generic,face,body,product,scene',
            'identity_strength' => 'nullable|integer|min:0|max:100',
            'identity_instructions' => 'nullable|string',
            'min_reference_images' => 'nullable|integer|min:0|max:20',
            'max_reference_images' => 'nullable|integer|min:0|max:20',
            'description_fa' => 'nullable|string',
            'description_en' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
            'meta_keywords' => 'nullable|string|max:255',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'explore_tiles' => 'nullable|array',
            'explore_tiles.*' => 'in:1x1,2x2,1x2,2x1',
            'status' => 'nullable|in:active,draft,inactive',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'sample_outputs' => 'nullable|array',
            'before_images' => 'nullable|array',
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
            'output_variants' => 'nullable|array',
            'output_variants.*.title' => 'nullable|string|max:150',
            'output_variants.*.prompt' => 'nullable|string|max:2000',
            'output_variants.*.key' => 'nullable|string|max:40',
            'output_variants.*.image' => 'nullable|string|max:500',
            'output_variants.*.image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('category_ids', [])))));
        if (!empty($categoryIds)) {
            $validated['category_id'] = $categoryIds[0];
        }
        if (isset($validated['category_id'])) {
            $validated['category'] = Category::where('id', $validated['category_id'])->value('name') ?? 'عمومی';
        }

        if ($request->hasFile('cover')) {
            if ($product->cover) Storage::disk('public')->delete($product->cover);
            $validated['cover'] = $request->file('cover')->store('products/covers', 'public');
        }

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
            $validated['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        } elseif ((!$product->thumbnail || !Storage::disk('public')->exists($product->thumbnail)) && !empty($validated['cover'] ?? null)) {
            // اگر Thumbnail فعلی خراب/غایب است ولی همین الان یک Cover جدید آپلود شد، از همان به عنوان Thumbnail هم استفاده کن
            $validated['thumbnail'] = $this->copyDuplicateFile($validated['cover'], 'products/thumbnails');
        }

        if ($request->hasFile('new_product_icon')) {
            if ($product->new_product_icon) Storage::disk('public')->delete($product->new_product_icon);
            $validated['new_product_icon'] = $request->file('new_product_icon')->store('product_icons', 'public');
        }

        if ($request->hasFile('og_image')) {
            if ($product->og_image) Storage::disk('public')->delete($product->og_image);
            $validated['og_image'] = $request->file('og_image')->store('products/seo', 'public');
        }

        if ($request->hasFile('sample_outputs')) {
            $newSamples = [];
            foreach ($request->file('sample_outputs') as $file) {
                $newSamples[] = $file->store('products/samples', 'public');
            }
            $existingSamples = is_array($product->sample_outputs) ? $product->sample_outputs : [];
            $validated['sample_outputs'] = array_merge($existingSamples, $newSamples);
        }

        if ($request->hasFile('before_images')) {
            $newBeforeImages = [];
            foreach ($request->file('before_images') as $file) {
                $newBeforeImages[] = $file->store('products/before_images', 'public');
            }
            $existingBeforeImages = is_array($product->before_images) ? $product->before_images : [];
            $validated['before_images'] = array_merge($existingBeforeImages, $newBeforeImages);
        }

        $validated['is_featured'] = $request->has('is_featured');
        $validated['is_new'] = $request->has('is_new');
        $validated['is_trending'] = $request->has('is_trending');
        $validated['watermark_enabled'] = $request->has('watermark_enabled');
        $validated['new_is_premium'] = $request->has('new_is_premium');
        $validated['new_is_recommended'] = $request->has('new_is_recommended');
        $validated['new_is_beta'] = $request->has('new_is_beta');
        $validated['new_show_free_badge'] = $request->has('new_show_free_badge');

        // حفظ هویت — چک‌باکس‌ها و provider_options
        $validated['identity_preservation'] = $request->has('identity_preservation');
        $validated['preserve_body'] = $request->has('preserve_body');
        $providerOptionsRaw = $request->input('provider_options');
        $validated['provider_options'] = $providerOptionsRaw ? (json_decode($providerOptionsRaw, true) ?: null) : null;
        $validated['seed'] = $request->filled('seed') ? (int) $request->input('seed') : null;

        $exploreTiles = array_values(array_intersect(['1x1','2x2','1x2','2x1'], (array) $request->input('explore_tiles', [])));
        $validated['explore_tiles'] = $exploreTiles ?: ['1x1','2x2','1x2','2x1'];

        // مدل‌های خروجی چندگانه — بازسازی کامل از روی فرم (حذف/افزودن/جایگزینی عکس)
        $validated['output_variants'] = $this->buildOutputVariants($request);

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

        // new_min_credit_required در دیتابیس NOT NULL است (پیش‌فرض ۰)؛ اگر فرم خالی فرستاد، صفر جایگزین شود
        $validated['new_min_credit_required'] = $validated['new_min_credit_required'] ?? 0;

        $product->update($validated);

        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }

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
        if ($product->og_image) Storage::disk('public')->delete($product->og_image);

        if (is_array($product->sample_outputs)) {
            foreach ($product->sample_outputs as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        if (is_array($product->before_images)) {
            foreach ($product->before_images as $path) {
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

        // کد ۶ رقمی باید برای کپی هم جدید و یکتا باشد (replicate مقدار کد اصلی را کپی می‌کند)
        do {
            $cloneCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Product::where('product_code', $cloneCode)->exists());
        $clone->product_code = $cloneCode;

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

    /**
     * ساخت آرایه تمیز «مدل‌های خروجی چندگانه» (Output Variants) از ورودی فرم ادمین.
     * هر ردیف: title (اجباری)، prompt (اختیاری)، image (مسیر موجود) یا image_file (آپلود جدید).
     * $copySharedImages فقط هنگام تکثیر محصول true است تا فایل عکس واریانت‌ها برای محصول جدید کپی شود.
     */
    private function buildOutputVariants(Request $request, bool $copySharedImages = false): array
    {
        $rows = $request->input('output_variants', []);
        if (!is_array($rows)) return [];

        $files = $request->file('output_variants', []);
        $out = [];

        foreach ($rows as $i => $row) {
            if (!is_array($row)) continue;
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') continue;

            $imagePath = trim((string) ($row['image'] ?? '')) ?: null;

            // آپلود جدید همیشه اولویت دارد
            $file = $files[$i]['image_file'] ?? null;
            if ($file && $file->isValid()) {
                $imagePath = $file->store('products/variants', 'public');
            } elseif ($imagePath && $copySharedImages && Storage::disk('public')->exists($imagePath)) {
                // در حالت تکثیر، فایل عکس واریانت هم برای محصول جدید کپی می‌شود تا اشتراکی نماند
                $imagePath = $this->copyDuplicateFile($imagePath, 'products/variants');
            } elseif ($imagePath && !Storage::disk('public')->exists($imagePath)) {
                $imagePath = null;
            }

            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                $key = 'v_' . Str::random(8);
            }

            $out[] = [
                'key'    => $key,
                'title'  => Str::limit($title, 120, ''),
                'image'  => $imagePath,
                'prompt' => trim((string) ($row['prompt'] ?? '')),
            ];
        }

        return array_values($out);
    }

    private function copyDuplicateFile(string $sourcePath, string $targetDir): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = $targetDir . '/' . (string) Str::uuid() . '.' . $extension;
        Storage::disk('public')->copy($sourcePath, $newPath);
        return $newPath;
    }
}