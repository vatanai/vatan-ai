<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\AiModel; // برای دسترسی به مدل‌های هوش مصنوعی ساخته‌شده در پنل ادمین
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * نمایش لیست محصولات با جستجو، فیلترهای پیشرفته، مرتب‌سازی و صفحه‌بندی
     * تمام فیلترها روی فیلدهای واقعاً موجود در دیتابیس اعمال می‌شوند (بدون افزودن ستون جدید)
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // ─── جستجوی چندفیلده: نام فارسی، نام انگلیسی، Slug، تگ، شناسه محصول ───
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

        // ─── فیلترهای پایه (چیپ‌های سریع + فیلتر پیشرفته) ───
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }
        if ($subcategory = $request->get('subcategory')) {
            $query->where('subcategory', $subcategory);
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

        // ─── بازه‌ی تاریخ ایجاد/ویرایش ───
        if ($createdFrom = $request->get('created_from')) $query->whereDate('created_at', '>=', $createdFrom);
        if ($createdTo   = $request->get('created_to'))   $query->whereDate('created_at', '<=', $createdTo);
        if ($updatedFrom = $request->get('updated_from')) $query->whereDate('updated_at', '>=', $updatedFrom);
        if ($updatedTo   = $request->get('updated_to'))   $query->whereDate('updated_at', '<=', $updatedTo);

        // ─── مرتب‌سازی ───
        // «بیشترین/کمترین استفاده» و «بیشترین درآمد» فعلاً داده‌ی بک‌اند ندارند
        // (در UI به‌صورت غیرفعال + برچسب «نیاز به بررسی برنامه» نمایش داده می‌شوند)
        switch ($request->get('sort')) {
            case 'oldest': $query->oldest(); break;
            case 'az':     $query->orderBy('name_fa'); break;
            case 'newest':
            default:       $query->latest(); break;
        }

        // ─── تعداد آیتم در هر صفحه ───
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->paginate($perPage)->withQueryString();

        // شمارش وضعیت‌ها برای کارت‌های آماری بالای صفحه (مستقل از فیلترهای فعلی)
        $activeCount   = Product::where('status', 'active')->count();
        $draftCount    = Product::where('status', 'draft')->count();
        $inactiveCount = Product::where('status', 'inactive')->count();

        // برای پر کردن سلکت «مدل هوش مصنوعی» در فیلتر پیشرفته
        $aiModels = AiModel::orderBy('name')->get();

        // برای پر کردن سلکت «دسته‌بندی» و «زیردسته» بر اساس داده‌های واقعی موجود
        $categories = Product::whereNotNull('category')->distinct()->pluck('category')->filter()->values();
        $subcategories = Product::whereNotNull('subcategory')->distinct()->pluck('subcategory')->filter()->values();

        // آخرین محصولات ویرایش‌شده (ویجت «Recently Edited» — بر اساس updated_at واقعی)
        $recentlyEdited = Product::orderByDesc('updated_at')->take(3)->get();

        return view('admin.products.index', compact(
            'products', 'activeCount', 'draftCount', 'inactiveCount',
            'aiModels', 'categories', 'subcategories', 'recentlyEdited'
        ));
    }

    /**
     * نمایش فرم ساخت محصول جدید
     */
    public function create()
    {
        // واکشی مدل‌های فعالی که در پنل "مدل‌های هوش مصنوعی" ساخته شده‌اند
        // تا در فرم محصول، برای انتخاب Primary و اولویت‌بندی Fallback استفاده شوند
        $aiModels = AiModel::where('is_active', true)->latest()->get();

        return view('admin.products.create', compact('aiModels'));
    }

    /**
     * ذخیره محصول جدید در دیتابیس
     */
    public function store(Request $request)
    {
        // ۱. اعتبارسنجی داده‌های ارسالی فرم ۳ مرحله‌ای
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
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'sample_outputs' => 'nullable|array',
            'sample_outputs.*' => 'image|mimes:jpeg,png,jpg,webp|max:4096',
            'media_type' => 'required|in:photo,video,both',
            'preview_video_url' => 'nullable|url',

            // گام دوم: تنظیمات هوش مصنوعی
            // primary_model و fallback_models باید واقعاً در جدول ai_models موجود باشند
            'primary_model' => 'required|string|exists:ai_models,model_id',
            'timeout' => 'required|integer|min:1',
            'pipeline_type' => 'required|in:image_generation,image_editing,text_generation',
            'fallback_models' => 'nullable|array',
            'fallback_models.*' => 'string|exists:ai_models,model_id',
            'prompt_template' => 'required|string',
            'input_schema' => 'nullable|array',
            'input_schema.*.field_id' => 'required|string',
            'input_schema.*.label_fa' => 'required|string',
            'input_schema.*.type' => 'required|string',
            'input_schema.*.required' => 'required|in:0,1',

            // گام سوم: خروجی و قیمت
            'watermark_enabled' => 'nullable|boolean',
            'watermark_position' => 'required|in:corner,center,none',
            'pricing_model' => 'required|in:free,per_credit,subscription',
            'credit_cost' => 'nullable|required_if:pricing_model,per_credit|integer|min:0',
            'display_mode' => 'required|in:card,featured,simple',
            'card_shape' => 'required|in:portrait,landscape,square',
            'gallery_layout' => 'required|in:grid,masonry,slider',
            'card_label' => 'nullable|string|max:100',
        ], [
            'primary_model.required' => 'انتخاب مدل اصلی هوش مصنوعی الزامی است.',
            'primary_model.exists' => 'مدل اصلی انتخاب‌شده در سیستم ثبت نشده است.',
            'fallback_models.*.exists' => 'یکی از مدل‌های جایگزین انتخاب‌شده معتبر نیست.',
            'prompt_template.required' => 'وارد کردن قالب پرامپت الزامی است.',
            'thumbnail.required' => 'تصویر کارت (Thumbnail) الزامی است.',
            'slug.unique' => 'این آدرس URL قبلاً برای محصول دیگری استفاده شده است.',
        ]);

        // ۲. آپلود تصاویر در دیسک public
        $thumbnailPath = $request->file('thumbnail')->store('products/thumbnails', 'public');

        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('products/covers', 'public');
        }

        $samplePaths = [];
        if ($request->hasFile('sample_outputs')) {
            foreach ($request->file('sample_outputs') as $file) {
                $samplePaths[] = $file->store('products/samples', 'public');
            }
        }

        // ۳. آماده‌سازی آرایه نهایی جهت ذخیره‌سازی
        $productData = [
            'name_fa' => $validated['name_fa'],
            'name_en' => $validated['name_en'],
            'slug' => Str::slug($validated['slug']),
            'description_fa' => $validated['description_fa'],
            'description_en' => $validated['description_en'],
            'category' => $validated['category'],
            'subcategory' => $validated['subcategory'],
            'status' => $validated['status'],

            'tags' => $validated['tags'] ?? [],
            // ترتیب آرایه fallback_models = اولویت تست مدل‌ها در زمان تولید خروجی
            'fallback_models' => $validated['fallback_models'] ?? [],
            'input_schema' => $validated['input_schema'] ?? [],
            'sample_outputs' => $samplePaths,

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

        Product::create($productData);

        return redirect()
            ->route('admin.products')
            ->with('success', 'محصول هوش مصنوعی جدید با موفقیت ثبت شد.');
    }

    /**
     * نمایش فرم ویرایش محصول به همراه مدل‌های هوشمند داینامیک
     */
    public function edit(Product $product)
    {
        // واکشی مدل‌های هوشمند فعال جهت پر شدن بخش آپشن‌های ویرایش محصول
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
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'sample_outputs' => 'nullable|array',
            'sample_outputs.*' => 'image|mimes:jpeg,png,jpg,webp|max:4096',
            'media_type' => 'required|in:photo,video,both',
            'preview_video_url' => 'nullable|url',

            'primary_model' => 'required|string|exists:ai_models,model_id',
            'timeout' => 'required|integer|min:1',
            'pipeline_type' => 'required|in:image_generation,image_editing,text_generation',
            'fallback_models' => 'nullable|array',
            'fallback_models.*' => 'string|exists:ai_models,model_id',
            'prompt_template' => 'required|string',
            'input_schema' => 'nullable|array',
            'input_schema.*.field_id' => 'required|string',
            'input_schema.*.label_fa' => 'required|string',
            'input_schema.*.type' => 'required|string',
            'input_schema.*.required' => 'required|in:0,1',

            'watermark_enabled' => 'nullable|boolean',
            'watermark_position' => 'required|in:corner,center,none',
            'pricing_model' => 'required|in:free,per_credit,subscription',
            'credit_cost' => 'nullable|required_if:pricing_model,per_credit|integer|min:0',
            'display_mode' => 'required|in:card,featured,simple',
            'card_shape' => 'required|in:portrait,landscape,square',
            'gallery_layout' => 'required|in:grid,masonry,slider',
            'card_label' => 'nullable|string|max:100',
        ], [
            'primary_model.required' => 'انتخاب مدل اصلی هوش مصنوعی الزامی است.',
            'primary_model.exists' => 'مدل اصلی انتخاب‌شده در سیستم ثبت نشده است.',
            'fallback_models.*.exists' => 'یکی از مدل‌های جایگزین انتخاب‌شده معتبر نیست.',
            'slug.unique' => 'این آدرس URL قبلاً برای محصول دیگری استفاده شده است.',
        ]);

        // آپلود/جایگزینی تصویر Thumbnail در صورت ارسال فایل جدید
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        } else {
            unset($validated['thumbnail']);
        }

        // آپلود/جایگزینی تصویر Cover در صورت ارسال فایل جدید
        if ($request->hasFile('cover')) {
            if ($product->cover) {
                Storage::disk('public')->delete($product->cover);
            }
            $validated['cover'] = $request->file('cover')->store('products/covers', 'public');
        } else {
            unset($validated['cover']);
        }

        // افزودن نمونه خروجی‌های جدید به لیست قبلی (بدون حذف نمونه‌های قدیمی)
        if ($request->hasFile('sample_outputs')) {
            $newSamples = [];
            foreach ($request->file('sample_outputs') as $file) {
                $newSamples[] = $file->store('products/samples', 'public');
            }
            $existingSamples = is_array($product->sample_outputs) ? $product->sample_outputs : [];
            $validated['sample_outputs'] = array_merge($existingSamples, $newSamples);
        }

        $validated['tags'] = $validated['tags'] ?? [];
        $validated['fallback_models'] = $validated['fallback_models'] ?? [];
        $validated['input_schema'] = $validated['input_schema'] ?? [];
        $validated['is_featured'] = $request->has('is_featured');
        $validated['is_new'] = $request->has('is_new');
        $validated['is_trending'] = $request->has('is_trending');
        $validated['watermark_enabled'] = $request->has('watermark_enabled');
        $validated['slug'] = Str::slug($validated['slug']);
        $validated['credit_cost'] = $validated['pricing_model'] === 'per_credit' ? ($validated['credit_cost'] ?? 0) : 0;

        $product->update($validated);

        return redirect()
            ->route('admin.products')
            ->with('success', 'تغییرات محصول با موفقیت اعمال شد.');
    }

    /**
     * حذف محصول به همراه فایل‌های فیزیکی آن
     */
    public function destroy(Product $product)
    {
        if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
        if ($product->cover) Storage::disk('public')->delete($product->cover);

        if (is_array($product->sample_outputs)) {
            foreach ($product->sample_outputs as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $product->delete();

        return redirect()
            ->route('admin.products')
            ->with('success', 'محصول با موفقیت حذف شد.');
    }

    /**
     * کپی کامل یک محصول (بدون فایل‌های رسانه‌ای که به دیسک آپلود شده‌اند تا
     * فایل فیزیکی بین دو محصول به اشتراک گذاشته نشود؛ فقط thumbnail کپی می‌شود)
     */
    public function duplicate(Product $product)
    {
        $clone = $product->replicate();

        $clone->name_fa = $product->name_fa . ' (کپی)';
        $clone->name_en = $product->name_en . '-copy';

        // اسلاگ یکتا برای نسخه‌ی کپی‌شده
        $baseSlug = Str::slug($product->slug . '-copy');
        $slug = $baseSlug;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$i);
        }
        $clone->slug = $slug;

        // کپی همیشه به‌صورت پیش‌نویس ذخیره می‌شود تا مستقیم منتشر نشود
        $clone->status = 'draft';
        $clone->is_featured = false;
        $clone->is_trending = false;

        $clone->save();

        return redirect()
            ->route('admin.products')
            ->with('success', 'کپی محصول با موفقیت ساخته شد.');
    }

    /**
     * تغییر سریع وضعیت محصول (فعال ⇄ غیرفعال) بدون ورود به صفحه ویرایش
     */
    public function toggleStatus(Product $product)
    {
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        if (request()->wantsJson()) {
            return response()->json(['status' => $product->status]);
        }

        return redirect()
            ->route('admin.products')
            ->with('success', 'وضعیت محصول با موفقیت تغییر کرد.');
    }

    /**
     * عملیات گروهی روی چند محصول انتخاب‌شده (فعال/غیرفعال/حذف/تغییر دسته)
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action'     => 'required|in:activate,deactivate,delete,change_category',
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer|exists:products,id',
            'category'   => 'nullable|string|required_if:action,change_category',
        ]);

        $products = Product::whereIn('id', $validated['ids']);

        switch ($validated['action']) {
            case 'activate':
                $products->update(['status' => 'active']);
                $message = 'محصولات انتخاب‌شده فعال شدند.';
                break;

            case 'deactivate':
                $products->update(['status' => 'inactive']);
                $message = 'محصولات انتخاب‌شده غیرفعال شدند.';
                break;

            case 'change_category':
                $products->update(['category' => $validated['category']]);
                $message = 'دسته‌بندی محصولات انتخاب‌شده تغییر کرد.';
                break;

            case 'delete':
                foreach ($products->get() as $product) {
                    if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
                    if ($product->cover) Storage::disk('public')->delete($product->cover);
                    if (is_array($product->sample_outputs)) {
                        foreach ($product->sample_outputs as $path) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                }
                $products->delete();
                $message = 'محصولات انتخاب‌شده حذف شدند.';
                break;
        }

        return redirect()
            ->route('admin.products')
            ->with('success', $message);
    }
}