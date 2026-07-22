<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\AiModel;
use App\Models\ProductPromptHistory;
use App\Models\Category;
use App\Models\Generation;
use App\Models\ProductTestRun;
use App\Services\ProductImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private readonly ProductImageOptimizer $imageOptimizer) {}
    /**
     * نمایش لیست محصولات با جستجو، فیلترهای پیشرفته، مرتب‌سازی و صفحه‌بندی
     */
    public function index(Request $request)
    {
        // آمار واقعی اجرا برای هر محصول از جدول generations:
        // - generations_count      : تعداد کل اجراهای همان محصول
        // - unique_users_count     : تعداد کاربران یکتایی که محصول را اجرا کرده‌اند
        // - last_run_at            : تاریخ/ساعت آخرین اجرای محصول
        $query = Product::query()
            ->with('categories')
            ->withCount('generations')
            ->addSelect([
                'unique_users_count' => Generation::selectRaw('count(distinct user_id)')
                    ->whereColumn('generations.product_id', 'products.id'),
                'last_run_at' => Generation::select('created_at')
                    ->whereColumn('generations.product_id', 'products.id')
                    ->latest('created_at')
                    ->limit(1),
            ]);

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name_fa', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%")
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
            case 'oldest':     $query->oldest(); break;
            case 'az':         $query->orderBy('name_fa'); break;
            // مرتب‌سازی واقعی بر اساس آمار اجرا (ستون محاسبه‌شده‌ی withCount بالا)
            case 'most_used':  $query->orderByDesc('generations_count')->latest(); break;
            case 'least_used': $query->orderBy('generations_count')->latest(); break;
            case 'newest':
            default:           $query->latest(); break;
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->paginate($perPage)->withQueryString();

        $activeCount   = Product::where('status', 'active')->count();
        $draftCount    = Product::where('status', 'draft')->count();
        $inactiveCount = Product::where('status', 'inactive')->count();

        // ── آمار واقعی اجراها برای کارت‌ها و نوار محبوبیت جدول ──
        // کل اجراها: تعداد کل رکوردهای جدول generations (هر رکورد = یک اجرا)
        $totalRuns = Generation::count();
        // بیشترین تعداد اجرای یک محصول (مبنای درصد نوار محبوبیت هر ردیف جدول)
        $maxRuns = (int) (Generation::selectRaw('count(*) as runs_count')
            ->groupBy('product_id')
            ->orderByDesc('runs_count')
            ->limit(1)
            ->value('runs_count') ?? 0);
        // محبوب‌ترین محصول (بیشترین اجرا) — فقط وقتی حداقل یک اجرا ثبت شده باشد
        $topProduct = $maxRuns > 0
            ? Product::withCount('generations')->orderByDesc('generations_count')->first()
            : null;

        $aiModels = AiModel::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $recentlyEdited = Product::orderByDesc('updated_at')->take(3)->get();

        return view('admin.products.index', compact(
            'products', 'activeCount', 'draftCount', 'inactiveCount',
            'totalRuns', 'maxRuns', 'topProduct',
            'aiModels', 'categories', 'recentlyEdited'
        ));
    }

    /**
     * نمایش فرم ساخت محصول جدید — یا فرم ویرایش محصول موجود، وقتی از دکمه‌ی «ویرایش»
     * با آی‌دی محصول (پارامتر اختیاری route) وارد این صفحه شده باشیم.
     *
     * نکته مهم (رفع باگ ساختاری): پیش‌تر این متد پارامتر {product} را نادیده می‌گرفت —
     * یعنی کلیک روی «ویرایش» یک فرم کاملاً خالی باز می‌کرد و ثبتِ آن (چون فرم همیشه به
     * store() ارسال می‌شود، نه update()) به‌جای ویرایش محصول، یک محصول تکراریِ جدید می‌ساخت.
     */
    public function create(Request $request, ?Product $product = null)
    {
        $aiModels = AiModel::where('is_active', true)->latest()->get();

        $duplicateFrom = null;
        if ($request->filled('duplicate')) {
            $duplicateFrom = Product::find($request->get('duplicate'));
        }

        // ═══════════════════════════════════════════════════════════════════════
        // حالت ویرایش واقعی: به‌جای تکرار دستیِ هر فیلد در Blade (ریسک بالای فراموش‌شدن
        // یک فیلد و از‌دست‌رفتن دوباره‌ی داده — دقیقاً همان خانواده‌ی باگی که مشکل خالی‌شدن
        // توضیحات محصولات را ایجاد کرده بود)، مقادیر واقعی محصول را برای «همین یک درخواست»
        // به‌عنوان ورودی قبلی (old()) در دسترس قرار می‌دهیم؛ همان مکانیزمی که Laravel برای
        // نگه‌داشتن مقادیر فرم بعد از خطای ولیدیشن استفاده می‌کند. نتیجه: تمام old('field', ...)
        // های موجود در هر ۵ گام ویزارد — بدون نیاز به تغییر دستیِ آن‌ها — مقدار واقعی محصول را
        // نشان می‌دهند. از session()->now() استفاده می‌شود (نه flashInput()) تا این مقداردهی
        // فقط مخصوص همین درخواست بماند و به صفحه/محصول بعدی نشتی نکند.
        if ($product && !$request->session()->hasOldInput()) {
            $product->loadMissing('categories');
            $productData = $product->toArray();
            $productData['category_ids'] = $product->categories->pluck('id')->all();
            $productData['provider_options'] = (is_array($product->provider_options) && !empty($product->provider_options))
                ? json_encode($product->provider_options, JSON_UNESCAPED_UNICODE)
                : null;
            $request->session()->now('_old_input', $productData);
        }

        return view('admin.products.create', compact('aiModels', 'duplicateFrom', 'product'));
    }

    /**
     * ذخیره محصول جدید در دیتابیس (حل خطای ولیدیشن‌های ساختاری)
     */
   public function store(Request $request)
    {
        $this->mergeInputSchemaJson($request);

        // ۱. ولیدیشن کاملاً آزاد و منعطف برای تست سریع
        $validated = $request->validate([
            'name_fa' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'description_fa' => 'nullable|string',
            'description_en' => 'nullable|string',
            'new_min_credit_required' => 'nullable|integer|min:0',
            'new_max_run_per_user' => 'nullable|integer|min:1',
            'new_price_custom_label' => 'nullable|string|max:100',
            'main_images' => 'nullable|array|max:20',
            'main_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:12288',
            'before_images' => 'nullable|array|max:20',
            'before_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:12288',
            'skip_image_optimization' => 'nullable|boolean',
            ...$this->inputSchemaRules(),
        ]);

        // ۲. ساخت یک نمونه جدید از مدل (برای دور زدن محدودیت fillable دیتابیس)
        $product = new Product();

        // ۳. مقداردهی فیلدهای اصلی (اگر در فرم خالی باشند، مقدار پیش‌فرض تست قرار می‌گیرد)
        $product->name_fa = self::stripCopySuffix($request->input('name_fa')) ?? 'محصول تست ' . rand(100, 999);
        $product->name_en = $request->input('name_en') ?? 'Test Product ' . rand(100, 999);
        
        $slugSource = $request->input('slug') ?? $product->name_en;
        $baseSlug = Str::slug($slugSource);
        $slug = $baseSlug ?: 'test-product-' . rand(100, 999);
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$i);
        }
        $product->slug = $slug;

        // ۳.۰.۱ کد ۶ رقمی یکتای محصول — همان کدی که در لینک عمومی محصول و ستون «کد محصول»
        // لیست ادمین استفاده می‌شود؛ برای هر محصول جدید همین‌جا ساخته می‌شود.
        $product->product_code = Product::generateUniqueProductCode();

        // ۳.۱ توضیحات فارسی/انگلیسی محصول (متن کامل نمایش داده‌شده در صفحه محصول)
        // نکته مهم: این دو خط عمداً اضافه شده‌اند — پیش‌تر در این متد اصلاً ذخیره نمی‌شدند
        // و همین باعث می‌شد توضیحات محصولات جدید با وجود پرشدن فرم، هرگز در دیتابیس ثبت نشود.
        $product->description_fa = $request->input('description_fa');
        $product->description_en = $request->input('description_en');

        // ۴. دسته‌بندی چندگانه (سرشاخه + زیرشاخه‌ها)
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('category_ids', [])))));
        if (empty($categoryIds) && $request->filled('category_id')) {
            $categoryIds = [(int) $request->input('category_id')];
        }
        $primaryId = $categoryIds[0] ?? (Category::first()->id ?? null);
        $product->category_id = $primaryId;
        $product->category = $primaryId ? (Category::where('id', $primaryId)->value('name') ?? 'عمومی') : 'عمومی';
        $product->subcategory = $request->input('subcategory');

        // ۵. تصاویر محصول: اولین عکس اصلی = کاور/تصویر کارت، بقیه = گالری.
        // ستون legacy thumbnail عمداً حذف نمی‌شود تا داده محصولات قدیمی دست‌نخورده بماند.
        $duplicateSource = $request->filled('duplicate_from') ? Product::find($request->input('duplicate_from')) : null;

        $mainPaths = $this->storeOptimizedImages($request->file('main_images', []), 'products/main', $request->boolean('skip_image_optimization'));
        if ($mainPaths) {
            $product->cover = array_shift($mainPaths);
            $product->sample_outputs = $mainPaths;
        } elseif ($duplicateSource && $duplicateSource->cover && Storage::disk('public')->exists($duplicateSource->cover)) {
            $product->cover = $this->copyDuplicateFile($duplicateSource->cover, 'products/covers');
            $product->sample_outputs = $this->copyDuplicateFiles((array) $duplicateSource->sample_outputs, 'products/samples');
        }
        // ستون thumbnail در دیتابیس قدیمی NOT NULL است؛ برای محصول جدید فقط همان مسیر
        // عکس اصلی را alias می‌کنیم و هیچ فایل دوم یا سایز دومی ساخته نمی‌شود.
        $product->thumbnail = $product->cover ?: 'products/thumbnails/default_placeholder.jpg';

        if ($request->hasFile('new_product_icon')) {
            $product->new_product_icon = $request->file('new_product_icon')->store('product_icons', 'public');
        } elseif ($duplicateSource && $duplicateSource->new_product_icon && Storage::disk('public')->exists($duplicateSource->new_product_icon)) {
            $product->new_product_icon = $this->copyDuplicateFile($duplicateSource->new_product_icon, 'product_icons');
        }

        $product->before_images = $this->storeOptimizedImages($request->file('before_images', []), 'products/before_images', $request->boolean('skip_image_optimization'));
        if ($request->hasFile('main_images') || $request->hasFile('before_images')) {
            $product->images_optimized_at = $request->boolean('skip_image_optimization') ? null : now();
        } elseif ($duplicateSource) {
            $product->images_optimized_at = $duplicateSource->images_optimized_at;
        }

        // ۶. فیلدهای سیستمی و هوش مصنوعی
        $product->primary_model = $request->input('primary_model') ?? AiModel::first()?->openrouter_model_id ?? 'stabilityai/stable-diffusion-3';
        $product->fallback_models = $request->input('fallback_models', []);
        $product->prompt_template = $request->input('prompt_template') ?? 'A high tech digital art illustration of {prompt}';
        $product->input_schema = $validated['input_schema'] ?? [];
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
        $product->resolution = $request->input('resolution') ?? '1024×1024';
        $product->allowed_aspect_ratios = $this->aspectRatiosFromSchema($product->input_schema, ['1:1']);
        $product->aspect_ratio = $product->allowed_aspect_ratios[0];
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
        $this->attachDraftTests($request, $product);

        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }

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
        $this->mergeInputSchemaJson($request);

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
            'main_images' => 'nullable|array|max:20',
            'main_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:12288',
            'before_images' => 'nullable|array|max:20',
            'before_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:12288',
            'skip_image_optimization' => 'nullable|boolean',
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
            'new_min_credit_required' => 'nullable|integer|min:0',
            'new_max_run_per_user' => 'nullable|integer|min:1',
            'new_price_custom_label' => 'nullable|string|max:100',
            ...$this->inputSchemaRules(),
        ]);

        // ═══════════════════════════════════════════════════════════════════════════
        // محافظ حیاتی در برابر خالی‌شدن ناخواسته‌ی فیلدها (Phantom-Null Guard):
        // در Laravel، وقتی یک کلید در آرایه‌ی قوانین ولیدیشن با قانون nullable تعریف شده باشد
        // ولی آن کلید اصلاً در بدنه‌ی درخواست ارسالی وجود نداشته باشد (نه اینکه خالی باشد، بلکه
        // اصلاً ارسال نشده باشد)، Validator آن را معتبر تشخیص می‌دهد و $validated آن کلید را با
        // مقدار null برمی‌گرداند. اگر این $validated مستقیماً به $product->update() داده شود،
        // مقدار واقعی و قبلاً ذخیره‌شده‌ی آن ستون در دیتابیس با NULL جایگزین می‌شود — دقیقاً همین
        // باگ باعث خالی‌شدن توضیحات فارسی/انگلیسی محصولات موجود شده بود (فرمی که این فیلد را
        // شامل نمی‌شد، هنگام ثبت هر تغییر دیگری آن را بی‌سروصدا پاک می‌کرد).
        // راه‌حل قطعی: فقط کلیدهایی که واقعاً در درخواست ارسالی حاضرند اجازه‌ی ورود به آرایه‌ی
        // نهایی آپدیت را دارند؛ فیلدهای چک‌باکس/فایل/محاسبه‌شده که در ادامه‌ی این متد صراحتاً
        // دوباره مقداردهی می‌شوند (is_featured, category, thumbnail و غیره) از این فیلتر تأثیر
        // نمی‌پذیرند چون بعداً به‌صورت مستقیم روی $validated بازنویسی می‌شوند.
        $validated = array_intersect_key($validated, $request->all());

        // نرمال‌سازی فیلدهای NOT NULL: فرم گاهی این فیلدها را خالی می‌فرستد،
        // قانون nullable|integer آن را به null تبدیل می‌کند و چون ستون در دیتابیس
        // NOT NULL است خطای 1048 (cannot be null) رخ می‌دهد. null را با پیش‌فرض جایگزین می‌کنیم.
        $notNullDefaults = [
            'new_display_order'            => 1,
            'new_card_color'               => '#A07AF5',
            'new_gallery_preview_mode'     => 'grid',
            'new_watermark_corner_precise' => 'tr',
            'new_watermark_opacity'        => 70,
            'new_watermark_size'           => 30,
            'new_watermark_type'           => 'logo',
            'new_watermark_text_color'     => '#FFFFFF',
            'new_min_credit_required'      => 0,
        ];
        foreach ($notNullDefaults as $__nnKey => $__nnDefault) {
            if (array_key_exists($__nnKey, $validated) && $validated[$__nnKey] === null) {
                $validated[$__nnKey] = $__nnDefault;
            }
        }

        // گارد قطعی باگ «کپی»: هنگام ویرایش/ثبت هیچ‌وقت نباید پسوند «(کپی)» به نام محصول
        // بچسبد (این پسوند فقط محصول باگ‌های قبلیِ فرم تکثیر/پیش‌نویس محلی بود). اگر به هر
        // مسیری در نام ارسالی باقی مانده باشد، همین‌جا پاک می‌شود.
        if (array_key_exists('name_fa', $validated)) {
            $validated['name_fa'] = self::stripCopySuffix($validated['name_fa']);
        }

        // اگر محصول قدیمی هنوز کد ۶ رقمی نداشته باشد، هنگام اولین ویرایش برایش ساخته می‌شود
        if (!$product->product_code) {
            $validated['product_code'] = Product::generateUniqueProductCode();
        }

        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('category_ids', [])))));
        if (!empty($categoryIds)) {
            $validated['category_id'] = $categoryIds[0];
        }
        if (isset($validated['category_id'])) {
            $validated['category'] = Category::where('id', $validated['category_id'])->value('name') ?? 'عمومی';
        }

        if ($request->hasFile('main_images')) {
            $mainPaths = $this->storeOptimizedImages($request->file('main_images'), 'products/main', $request->boolean('skip_image_optimization'));
            $oldMainPaths = array_filter(array_merge([$product->cover], (array) $product->sample_outputs));
            $validated['cover'] = array_shift($mainPaths);
            $validated['sample_outputs'] = $mainPaths;
            Storage::disk('public')->delete($oldMainPaths);
        }

        if ($request->hasFile('before_images')) {
            $beforePaths = $this->storeOptimizedImages($request->file('before_images'), 'products/before_images', $request->boolean('skip_image_optimization'));
            $oldBeforePaths = array_filter((array) $product->before_images);
            $validated['before_images'] = $beforePaths;
            Storage::disk('public')->delete($oldBeforePaths);
        }

        if ($request->hasFile('main_images') || $request->hasFile('before_images')) {
            $validated['images_optimized_at'] = $request->boolean('skip_image_optimization') ? null : now();
        }

        if ($request->hasFile('new_product_icon')) {
            if ($product->new_product_icon) Storage::disk('public')->delete($product->new_product_icon);
            $validated['new_product_icon'] = $request->file('new_product_icon')->store('product_icons', 'public');
        }

        if ($request->hasFile('og_image')) {
            if ($product->og_image) Storage::disk('public')->delete($product->og_image);
            $validated['og_image'] = $request->file('og_image')->store('products/seo', 'public');
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
        // مدل‌های جایگزین و فیلدهای ورودی پویا — قبلاً اصلاً در به‌روزرسانی مقداردهی نمی‌شدند
        // (فقط در store() ثبت می‌شدند)، در نتیجه ویرایش این دو مورد هیچ‌وقت واقعاً ذخیره نمی‌شد
        $validated['fallback_models'] = $request->input('fallback_models', []);
        $validated['input_schema'] = $validated['input_schema'] ?? [];
        $validated['allowed_aspect_ratios'] = $this->aspectRatiosFromSchema(
            $validated['input_schema'],
            $product->allowedAspectRatioList()
        );
        $validated['aspect_ratio'] = $validated['allowed_aspect_ratios'][0];

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
        $this->attachDraftTests($request, $product);

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
            foreach ($product->before_images as $path) Storage::disk('public')->delete($path);
        }

        $product->delete();
        return redirect()->route('admin.products')->with('success', 'محصول حذف شد.');
    }

    /**
     * میانبر جدول محصولات برای بهینه‌سازی امن تمام عکس‌های اصلی و قبل.
     * تا قبل از موفقیت کامل هیچ مسیر قبلی حذف یا در دیتابیس جایگزین نمی‌شود.
     */
    public function optimizeImages(Product $product)
    {
        $lock = Cache::lock('product-image-optimization:' . $product->id, 180);
        if (!$lock->get()) {
            return response()->json(['message' => 'بهینه‌سازی این محصول توسط مدیر دیگری در حال انجام است.'], 409);
        }

        $newlyCreated = [];
        $committed = false;
        try {
            $product->refresh();
            $oldCover = $product->cover;
            $oldSamples = array_values(array_filter((array) $product->sample_outputs));
            $oldBefore = array_values(array_filter((array) $product->before_images));
            $allOldPaths = array_values(array_unique(array_filter(array_merge([$oldCover], $oldSamples, $oldBefore))));

            if ($allOldPaths === []) {
                return response()->json(['message' => 'این محصول تصویری برای بهینه‌سازی ندارد.'], 422);
            }

            $beforeBytes = 0;
            foreach ($allOldPaths as $path) {
                if (Storage::disk('public')->exists($path)) $beforeBytes += (int) Storage::disk('public')->size($path);
            }

            $mapped = [];
            $optimize = function (?string $path, string $directory) use (&$mapped, &$newlyCreated): ?string {
                if (!$path) return null;
                if (isset($mapped[$path])) return $mapped[$path];
                $newPath = $this->imageOptimizer->optimizeStored($path, $directory);
                $mapped[$path] = $newPath;
                if ($newPath !== $path) $newlyCreated[] = $newPath;
                return $newPath;
            };

            $newCover = $optimize($oldCover, 'products/main');
            $newSamples = array_map(fn (string $path) => $optimize($path, 'products/main'), $oldSamples);
            $newBefore = array_map(fn (string $path) => $optimize($path, 'products/before_images'), $oldBefore);

            DB::transaction(function () use ($product, $oldCover, $newCover, $newSamples, $newBefore) {
                $product->cover = $newCover;
                $product->sample_outputs = $newSamples;
                $product->before_images = $newBefore;
                $product->images_optimized_at = now();
                if ($oldCover && $product->thumbnail === $oldCover) $product->thumbnail = $newCover;
                $product->save();
            });
            $committed = true;

            $keptPaths = array_values(array_unique(array_filter(array_merge(
                [$newCover, $product->thumbnail], $newSamples, $newBefore
            ))));
            $obsolete = array_values(array_diff($allOldPaths, $keptPaths));
            if ($obsolete) Storage::disk('public')->delete($obsolete);

            $afterBytes = 0;
            foreach ($keptPaths as $path) {
                if (Storage::disk('public')->exists($path)) $afterBytes += (int) Storage::disk('public')->size($path);
            }

            return response()->json([
                'message' => $newlyCreated
                    ? 'تمام تصاویر محصول با موفقیت بهینه شدند.'
                    : 'تصاویر این محصول از قبل استاندارد بودند.',
                'optimized_count' => count($newlyCreated),
                'image_count' => count($allOldPaths),
                'before_bytes' => $beforeBytes,
                'after_bytes' => $afterBytes,
                'cover_url' => $product->fresh()->displayImageUrl(),
            ]);
        } catch (\Throwable $error) {
            if (!$committed && $newlyCreated) Storage::disk('public')->delete($newlyCreated);
            report($error);
            return response()->json(['message' => 'بهینه‌سازی تصاویر انجام نشد؛ فایل‌های قبلی بدون تغییر حفظ شدند.'], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * کپی محصول
     */
    public function duplicate(Product $product)
    {
        // نام محصول کپی‌شده دقیقاً همان نام محصول اصلی می‌ماند — هیچ پسوند «(کپی)» یا «-copy»
        // به نام اضافه نمی‌شود (خواسته صریح مدیر). فقط slug و کد محصول، چون باید یکتا باشند،
        // مقدار جدید می‌گیرند.
        $clone = $product->replicate();
        $clone->name_fa = $product->name_fa;
        $clone->name_en = $product->name_en;

        $baseSlug = Str::slug($product->slug . '-2');
        $slug = $baseSlug;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$i);
        }
        $clone->slug = $slug;
        $clone->product_code = Product::generateUniqueProductCode();
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

    /** اتصال آزمایش‌هایی که پیش از اولین ذخیره محصول اجرا شده‌اند به رکورد محصول. */
    private function attachDraftTests(Request $request, Product $product): void
    {
        $draftUuid = $request->input('test_draft_uuid');
        if (!$draftUuid || !Str::isUuid($draftUuid)) return;

        ProductTestRun::whereNull('product_id')->where('draft_uuid', $draftUuid)->update(['product_id' => $product->id]);
        $latestDuration = ProductTestRun::where('product_id', $product->id)->where('status', 'completed')->latest()->value('duration_ms');
        $totalTokens = ProductTestRun::where('product_id', $product->id)->where('status', 'completed')->sum('total_tokens');
        $product->forceFill(['last_test_duration_ms' => $latestDuration, 'total_test_tokens' => $totalTokens])->saveQuietly();
    }

    /**
     * حذف پسوند سیستمی «(کپی)» از انتهای نام فارسی محصول.
     * این پسوند فقط توسط باگ‌های قبلی فرم تکثیر/بازیابی پیش‌نویس محلی ساخته می‌شد؛
     * حالت چندباره «نام (کپی) (کپی)» هم پوشش داده می‌شود. عمداً فقط شکل پرانتزدار
     * حذف می‌شود تا نام‌هایی که واقعاً به کلمه «کپی» ختم می‌شوند دست‌نخورده بمانند.
     */
    private static function stripCopySuffix(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $clean = trim($name);
        while (preg_match('/^(.*?)\s*\(\s*کپی\s*\)$/u', $clean, $m) && trim($m[1]) !== '') {
            $clean = trim($m[1]);
        }

        return $clean !== '' ? $clean : trim($name);
    }

    /**
     * قرارداد بک‌اند سازنده «ویژگی‌های خاص».
     * فقط کلیدهای شناخته‌شده را وارد JSON محصول می‌کند تا ساختار ناقص یا داده
     * دلخواه سمت کلاینت، رندر فرم کاربر و ساخت پرامپت را خراب نکند.
     */
    private function inputSchemaRules(): array
    {
        return [
            'input_schema' => ['nullable', 'array', 'max:50'],
            'input_schema.*' => ['array'],
            'input_schema.*.field_id' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct:strict'],
            'input_schema.*.label_fa' => ['required', 'string', 'max:150'],
            'input_schema.*.type' => ['required', 'string', Rule::in(array_keys(config('product_schema_types.types', [])))],
            'input_schema.*.required' => ['required', Rule::in(['0', '1', 0, 1])],
            'input_schema.*.hidden' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'input_schema.*.order' => ['nullable', 'integer', 'min:0', 'max:49'],
            'input_schema.*.description' => ['nullable', 'string', 'max:500'],
            'input_schema.*.help_text' => ['nullable', 'string', 'max:500'],
            'input_schema.*.placeholder' => ['nullable', 'string', 'max:255'],
            'input_schema.*.default' => ['nullable'],
            'input_schema.*.credit_cost' => ['nullable', 'integer', 'min:0'],
            'input_schema.*.variant' => ['nullable', Rule::in(['info', 'warning', 'success'])],
            'input_schema.*.min' => ['nullable', 'numeric'],
            'input_schema.*.max' => ['nullable', 'numeric'],
            'input_schema.*.step' => ['nullable', 'numeric', 'gt:0'],
            'input_schema.*.regex' => ['nullable', 'string', 'max:255'],
            'input_schema.*.unit' => ['nullable', 'string', 'max:30'],
            'input_schema.*.max_files' => ['nullable', 'integer', 'min:1', 'max:20'],
            'input_schema.*.max_size_mb' => ['nullable', 'integer', 'min:1', 'max:100'],
            'input_schema.*.accept' => ['nullable', 'string', 'max:255'],
            'input_schema.*.prompt_mode' => ['nullable', Rule::in(['token', 'append', 'off'])],
            'input_schema.*.prompt_wrap' => ['nullable', 'string', 'max:500'],
            'input_schema.*.show_if' => ['nullable', 'array'],
            'input_schema.*.show_if.field' => ['nullable', 'string', 'max:80'],
            'input_schema.*.show_if.op' => ['nullable', Rule::in(['eq', 'neq', 'has', 'not_empty'])],
            'input_schema.*.show_if.value' => ['nullable', 'string', 'max:255'],
            'input_schema.*.options' => ['nullable', 'array', 'max:100'],
            'input_schema.*.options.*' => ['array'],
            'input_schema.*.options.*.label' => ['nullable', 'string', 'max:150'],
            'input_schema.*.options.*.value' => ['nullable', 'string', 'max:150'],
            'input_schema.*.options.*.prompt' => ['nullable', 'string', 'max:500'],
            'input_schema.*.options.*.credit' => ['nullable', 'integer', 'min:0'],
            'input_schema.*.options.*.image' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * JSON واحد سازنده ویژگی‌ها را به قرارداد داخلی input_schema تبدیل می‌کند.
     *
     * این مسیر تعداد متغیرهای multipart را مستقل از تعداد گزینه‌های هر ویژگی
     * نگه می‌دارد و از قطع/ناقص‌شدن درخواست توسط max_input_vars جلوگیری می‌کند.
     * اگر کلاینت قدیمی JSON نفرستد، input_schema تو‌در‌تو بدون تغییر پذیرفته
     * می‌شود تا سازگاری عقب‌رو حفظ شود.
     */
    private function mergeInputSchemaJson(Request $request): void
    {
        if (!$request->exists('input_schema_json')) {
            return;
        }

        $request->validate([
            'input_schema_json' => ['required', 'string', 'max:524288', 'json'],
        ]);

        $schema = json_decode((string) $request->input('input_schema_json'), true);
        $request->merge(['input_schema' => $schema]);
    }

    private function copyDuplicateFile(string $sourcePath, string $targetDir): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = $targetDir . '/' . (string) Str::uuid() . '.' . $extension;
        Storage::disk('public')->copy($sourcePath, $newPath);
        return $newPath;
    }

    private function copyDuplicateFiles(array $sourcePaths, string $targetDir): array
    {
        $copies = [];
        foreach ($sourcePaths as $sourcePath) {
            if (is_string($sourcePath) && Storage::disk('public')->exists($sourcePath)) {
                $copies[] = $this->copyDuplicateFile($sourcePath, $targetDir);
            }
        }
        return $copies;
    }

    private function storeOptimizedImages(array $files, string $directory, bool $skipOptimization = false): array
    {
        $paths = [];
        try {
            foreach ($files as $file) {
                $paths[] = $skipOptimization
                    ? $file->store($directory, 'public')
                    : $this->imageOptimizer->store($file, $directory);
            }
        } catch (\Throwable $e) {
            if ($paths) Storage::disk('public')->delete($paths);
            throw $e;
        }
        return $paths;
    }

    private function normalizedAspectRatios(mixed $ratios): array
    {
        $allowed = ['1:1', '4:5', '3:4', '9:16', '16:9', '3:2', '2:3'];
        $selected = array_values(array_unique(array_intersect($allowed, array_map('strval', (array) $ratios))));
        return $selected ?: ['1:1'];
    }

    private function aspectRatiosFromSchema(array $schema, array $fallback): array
    {
        foreach ($schema as $field) {
            if (!is_array($field) || ($field['type'] ?? null) !== 'aspect_ratio') continue;
            $ratios = array_map(
                fn ($option) => is_array($option) ? ($option['value'] ?? null) : null,
                (array) ($field['options'] ?? [])
            );
            return $this->normalizedAspectRatios(array_filter($ratios));
        }

        return $this->normalizedAspectRatios($fallback);
    }
}
