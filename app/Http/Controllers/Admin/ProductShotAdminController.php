<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductShotController;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ProductShotSetting;
use App\Models\ShotBatchItem;
use App\Models\ShotLibrary;
use App\Services\ExchangeRateService;
use App\Services\ProductShots\ProductShotFeature;
use App\Services\ProductShots\ShotGenerationService;
use App\Services\ProductShots\ShotGrammar;
use App\Services\ProductShots\ShotImageStore;
use App\Support\ProviderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * پنل «استودیو محصول»: تنظیمات و فیچر فلگ، کتابخانه‌ی شات (CRUD)،
 * ثبت/ویرایش محصول پروداکتی و پیش‌نمایش شات با عکس تست.
 * فرم ۵ گامی محصول چهره‌محور (ProductController) دست نخورده است.
 */
class ProductShotAdminController extends Controller
{
    public const NICHES = [
        'beauty' => 'آرایشی، عطر و زیبایی',
        'fashion' => 'پوشاک و مزون',
        'bags-shoes' => 'کیف و کفش',
        'jewelry' => 'طلا، جواهر و اکسسوری',
    ];

    private const PREVIEW_DIR = 'uploads/product-shots/admin-previews';
    private const SAMPLE_DIR = 'products/shot-samples';

    public function __construct(
        private ProductShotFeature $feature,
        private ShotImageStore $images,
    ) {}

    public function index(): View
    {
        $settings = ProductShotSetting::current();
        $shots = ShotLibrary::query()->ordered()->withCount('productShots')->get();
        $products = Product::query()
            ->where('product_mode', Product::MODE_PRODUCT)
            ->withCount(['productShots as enabled_shots_count' => fn ($q) => $q->where('enabled', true)])
            ->latest('id')
            ->get();

        return view('admin.product-shots.index', [
            'settings' => $settings,
            'shots' => $shots,
            'products' => $products,
            'stats' => $this->stats($settings),
            'grammar' => ShotGrammar::vocabulary(),
            'axisLabels' => ShotGrammar::AXIS_LABELS,
            'multiAxes' => ShotGrammar::MULTI_AXES,
            'categories' => ShotGrammar::CATEGORIES,
            'niches' => self::NICHES,
            'moduleEnabled' => $this->feature->enabled(),
            'masterSwitch' => (bool) config('product_shots.enabled', true),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', Rule::in(ProductShotSetting::AUDIENCES)],
            'whitelist_user_ids' => ['nullable', 'string', 'max:2000'],
            'whitelist_phones' => ['nullable', 'string', 'max:4000'],
            'max_shots_per_run' => ['required', 'integer', 'min:1', 'max:10'],
            'client_concurrency' => ['required', 'integer', 'min:1', 'max:3'],
            'daily_cost_cap_usd' => ['required', 'numeric', 'min:0', 'max:10000'],
            'preflight_model' => ['nullable', 'string', 'max:120'],
            'preflight_prompt' => ['nullable', 'string', 'max:4000'],
            'preflight_blocking_issues' => ['nullable', 'array'],
            'preflight_blocking_issues.*' => [Rule::in(array_keys(\App\Services\ProductShots\ShotVisionService::ISSUES))],
            'preflight_min_side' => ['required', 'integer', 'min:500', 'max:3000'],
            'product_sheet_size' => ['required', 'integer', 'min:1024', 'max:3072'],
            'qc_model' => ['nullable', 'string', 'max:120'],
            'credit_price_toman' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $settings = ProductShotSetting::current();
        $settings->fill([
            'enabled' => $request->boolean('enabled'),
            'audience' => $data['audience'],
            'whitelist_user_ids' => $this->splitList($data['whitelist_user_ids'] ?? '', true),
            'whitelist_phones' => $this->splitList($data['whitelist_phones'] ?? ''),
            'max_shots_per_run' => (int) $data['max_shots_per_run'],
            'client_concurrency' => (int) $data['client_concurrency'],
            'daily_cost_cap_usd' => (float) $data['daily_cost_cap_usd'],
            'preflight_enabled' => $request->boolean('preflight_enabled'),
            'preflight_model' => ($data['preflight_model'] ?? null) ?: null,
            'preflight_prompt' => trim((string) ($data['preflight_prompt'] ?? '')) ?: null,
            'preflight_blocking_issues' => array_values((array) ($data['preflight_blocking_issues'] ?? [])),
            'preflight_min_side' => (int) $data['preflight_min_side'],
            'product_sheet_enabled' => $request->boolean('product_sheet_enabled'),
            'product_sheet_size' => (int) $data['product_sheet_size'],
            'qc_enabled' => $request->boolean('qc_enabled'),
            'qc_model' => ($data['qc_model'] ?? null) ?: null,
            'qc_auto_retry' => $request->boolean('qc_auto_retry'),
            'credit_price_toman' => (int) $data['credit_price_toman'],
        ])->save();
        ProductShotSetting::forgetCache();

        return redirect()->route('admin.product-shots.index')->with('success', 'تنظیمات استودیو محصول ذخیره شد.');
    }

    // ── کتابخانه‌ی شات ─────────────────────────────────────────────

    public function storeShot(Request $request): RedirectResponse
    {
        $data = $this->validateShot($request);
        $shot = new ShotLibrary();
        $this->fillShot($shot, $data, $request);
        $shot->save();

        return redirect()->route('admin.product-shots.index', ['tab' => 'library'])->with('success', 'شات «' . $shot->name_fa . '» به کتابخانه اضافه شد.');
    }

    public function updateShot(Request $request, ShotLibrary $shot): RedirectResponse
    {
        $data = $this->validateShot($request, $shot);
        $this->fillShot($shot, $data, $request);
        $shot->save();

        return redirect()->route('admin.product-shots.index', ['tab' => 'library'])->with('success', 'شات «' . $shot->name_fa . '» ذخیره شد.');
    }

    public function toggleShot(ShotLibrary $shot): RedirectResponse
    {
        $shot->update(['is_active' => ! $shot->is_active]);

        return redirect()->route('admin.product-shots.index', ['tab' => 'library'])
            ->with('success', $shot->is_active ? 'شات فعال شد.' : 'شات غیرفعال شد و در صفحه‌ی ساخت دیده نمی‌شود.');
    }

    // ── ثبت محصول پروداکتی ─────────────────────────────────────────

    public function createProduct(?Product $product = null): View
    {
        if ($product) {
            abort_unless($product->isShotProduct(), 404);
            $product->load('productShots', 'categories', 'occupations');
        }

        $productShots = $product ? $product->productShots->keyBy('shot_id') : collect();
        $shots = ShotLibrary::query()->ordered()->get()->filter(fn (ShotLibrary $s) => $s->is_active || $productShots->has($s->id))->values();
        $generalShotIds = $shots
            ->filter(fn (ShotLibrary $shot) => Str::startsWith($shot->key, 'product-'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        if (! $product && $generalShotIds->isNotEmpty()) {
            $shots = $shots->sortBy(fn (ShotLibrary $shot) => [
                $generalShotIds->contains($shot->id) ? 0 : 1,
                $shot->sort,
                $shot->id,
            ])->values();
        }

        return view('admin.product-shots.product-form', [
            'product' => $product,
            'shots' => $shots,
            'productShots' => $productShots,
            'generalShotIds' => $generalShotIds->all(),
            'models' => $this->imageModels(),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'name_fa', 'parent_id']),
            'occupations' => Occupation::query()->active()->ordered()->get(),
            'occupationGroups' => Occupation::GROUPS,
            'qualityLevels' => (array) config('product_shots.quality_levels'),
            'niches' => self::NICHES,
            'settings' => (array) ($product?->shot_settings ?? []),
            'globalSettings' => ProductShotSetting::current(),
            'aspectRatios' => (array) config('product_shots.aspect_ratios'),
        ]);
    }

    /** پیش‌نمایش واقعی صفحه‌ی کاربر برای محصول پیش‌نویس، فقط داخل پنل ادمین. */
    public function previewProductPage(Product $product, ProductShotController $controller): View
    {
        abort_unless($product->isShotProduct(), 404);

        return $controller->page($product);
    }

    public function storeProduct(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateProduct($request);
        $product = new Product();
        $product->product_mode = Product::MODE_PRODUCT;
        $product->product_code = Product::generateUniqueProductCode();
        $product->base_likes_count = random_int(120, 250);
        $product->slug = $this->uniqueSlug($data['name_en']);
        $this->fillProduct($product, $data, $request);

        DB::transaction(function () use ($product, $data, $request) {
            $product->save();
            $this->syncCategories($product, $data);
            $this->syncOccupations($product, $data);
            $this->syncShots($product, $request);
            $this->ensureCover($product);
        });

        return $this->afterSave($request, $product, 'محصول پروداکتی «' . $product->name_fa . '» ثبت شد.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        abort_unless($product->isShotProduct(), 404);
        $data = $this->validateProduct($request, $product);
        $this->fillProduct($product, $data, $request);

        DB::transaction(function () use ($product, $data, $request) {
            $product->save();
            $this->syncCategories($product, $data);
            $this->syncOccupations($product, $data);
            $this->syncShots($product, $request);
            $this->ensureCover($product);
        });

        return $this->afterSave($request, $product, 'تغییرات محصول «' . $product->name_fa . '» ذخیره شد.');
    }

    /** پیش‌نمایش یک شات با عکس تست ادمین؛ بدون سفارش و بدون کسر اعتبار. */
    public function preview(Request $request, ShotGenerationService $generator): JsonResponse
    {
        $data = $request->validate([
            'shot_id' => ['required', 'integer', 'exists:shot_library,id'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'ai_model_id' => ['required', 'integer', 'exists:ai_models,id'],
            'product_description' => ['nullable', 'string', 'max:300'],
            'brand_palette' => ['nullable', 'string', 'max:120'],
            'brand_style' => ['nullable', 'string', 'max:200'],
            'brand_identity_enabled' => ['nullable', 'boolean'],
            'brand_identity_prompt' => ['nullable', 'string', 'max:2000'],
            'aspect_ratio' => ['nullable', Rule::in((array) config('product_shots.aspect_ratios'))],
        ]);

        $sourcePaths = [];
        foreach ((array) $request->file('images', []) as $file) {
            $sourcePaths[] = $this->images->storeUpload($file, self::PREVIEW_DIR . '/sources')['path'];
        }
        if ($sourcePaths === [] && $request->hasFile('image')) {
            $stored = $this->images->storeUpload($request->file('image'), self::PREVIEW_DIR . '/sources');
            $sourcePaths[] = $stored['path'];
        } elseif (! empty($data['image_path']) && $this->isSafePreviewPath($data['image_path'])) {
            $sourcePaths[] = $data['image_path'];
        }
        if ($sourcePaths === []) {
            return response()->json(['ok' => false, 'message' => 'یک عکس تست از محصول انتخاب کنید.'], 422);
        }

        $sheet = count($sourcePaths) > 1 ? $this->images->createProductSheet($sourcePaths, self::PREVIEW_DIR . '/sheets', 2048) : null;
        $previewReferences = array_values(array_filter(array_merge($sheet ? [$sheet['path']] : [], $sourcePaths)));

        $model = AiModel::query()->findOrFail($data['ai_model_id']);
        $product = new Product([
            'name_fa' => 'پیش‌نمایش',
            'primary_model' => $model->openrouter_model_id,
            'ai_provider' => $model->provider,
            'fallback_models' => [],
            'fallback_model_providers' => [],
            'timeout' => 180,
            'shot_settings' => [
                'brand_palette' => $data['brand_palette'] ?? null,
                'brand_style' => $data['brand_style'] ?? null,
                'brand_identity_enabled' => $request->boolean('brand_identity_enabled'),
                'brand_identity_prompt' => trim((string) ($data['brand_identity_prompt'] ?? '')) ?: null,
                'product_description' => $data['product_description'] ?? null,
            ],
        ]);
        $product->product_mode = Product::MODE_PRODUCT;

        try {
            @set_time_limit(300);
            $result = $generator->preview(
                $product,
                ShotLibrary::query()->findOrFail($data['shot_id']),
                $previewReferences,
                $data['aspect_ratio'] ?? '4:5',
                $data['product_description'] ?? null,
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'ساخت پیش‌نمایش انجام نشد: ' . Str::limit($e->getMessage(), 160), 'source_path' => $sourcePaths[0]], 422);
        }

        return response()->json([
            'ok' => true,
            'source_path' => $sourcePaths[0],
            'source_url' => asset('storage/' . $sourcePaths[0]),
            'product_sheet_url' => $sheet ? asset('storage/' . $sheet['path']) : null,
            'image_path' => $result['path'],
            'image_url' => asset('storage/' . $result['path']),
            'cost_usd' => round($result['cost'], 4),
            'model' => $result['model'],
            'qc' => $result['qc'],
            'prompt' => $result['prompt'],
        ]);
    }

    /** ذخیره‌ی خروجی پیش‌نمایش به‌عنوان تصویر نمونه‌ی همین شات برای همین محصول. */
    public function saveSample(Request $request, Product $product, ShotLibrary $shot): JsonResponse
    {
        abort_unless($product->isShotProduct(), 404);
        $data = $request->validate(['image_path' => ['required', 'string', 'max:255']]);
        $path = $this->copySample($data['image_path']);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'تصویر پیش‌نمایش پیدا نشد.'], 422);
        }
        $productShot = ProductShot::query()->firstOrCreate(
            ['product_id' => $product->id, 'shot_id' => $shot->id],
            ['enabled' => true, 'is_default' => false, 'sort' => 999]
        );
        $productShot->update(['sample_image' => $path]);
        $this->ensureCover($product->fresh());

        return response()->json(['ok' => true, 'sample_url' => asset('storage/' . $path)]);
    }

    // ── کمکی‌ها ─────────────────────────────────────────────────────

    private function validateShot(Request $request, ?ShotLibrary $shot = null): array
    {
        $vocab = ShotGrammar::vocabulary();
        $rules = [
            'key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('shot_library', 'key')->ignore($shot?->id)],
            'name_fa' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'description_fa' => ['nullable', 'string', 'max:600'],
            'category' => ['required', Rule::in(array_keys(ShotGrammar::CATEGORIES))],
            'niche_tags' => ['nullable', 'string', 'max:300'],
            'prompt_template' => ['nullable', 'string', 'max:4000'],
            'default_credits' => ['required', 'integer', 'min:0', 'max:1000'],
            'aspect_ratio_default' => ['required', Rule::in((array) config('product_shots.aspect_ratios'))],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'sample' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
            'tokens' => ['nullable', 'array'],
        ];
        foreach (ShotGrammar::AXES as $axis) {
            $allowed = array_keys($vocab[$axis]);
            if (in_array($axis, ShotGrammar::MULTI_AXES, true)) {
                $rules["tokens.$axis"] = ['nullable', 'array'];
                $rules["tokens.$axis.*"] = [Rule::in($allowed)];
            } else {
                $rules["tokens.$axis"] = ['nullable', Rule::in($allowed)];
            }
        }

        return $request->validate($rules, [
            'key.regex' => 'کلید فقط حروف کوچک انگلیسی، عدد و خط تیره.',
            'key.unique' => 'این کلید قبلاً استفاده شده.',
            'name_fa.required' => 'نام فارسی شات را وارد کنید.',
        ]);
    }

    private function fillShot(ShotLibrary $shot, array $data, Request $request): void
    {
        $shot->key = ($data['key'] ?? null) ?: ($shot->key ?: $this->uniqueShotKey(($data['name_en'] ?? null) ?: $data['name_fa']));
        $shot->name_fa = $data['name_fa'];
        $shot->name_en = $data['name_en'] ?? null;
        $shot->description_fa = $data['description_fa'] ?? null;
        $shot->category = $data['category'];
        $shot->niche_tags = $this->splitList($data['niche_tags'] ?? '');
        $shot->tokens = ShotGrammar::normalize((array) ($data['tokens'] ?? []));
        $shot->prompt_template = trim((string) ($data['prompt_template'] ?? '')) ?: null;
        $shot->default_credits = (int) $data['default_credits'];
        $shot->aspect_ratio_default = $data['aspect_ratio_default'];
        $shot->sort = (int) ($data['sort'] ?? $shot->sort ?? 0);
        $shot->is_active = $shot->exists ? $shot->is_active : true;
        if ($request->hasFile('sample')) {
            $stored = $this->images->storeUpload($request->file('sample'), 'shot-library');
            $shot->sample_image = $stored['path'];
        }
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $publishing = $request->input('status') === 'active';

        $data = $request->validate([
            'name_fa' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_fa' => ['nullable', 'string', 'max:3000'],
            'occupation_ids' => ['required', 'array', 'min:1'],
            'occupation_ids.*' => ['integer', Rule::exists('occupations', 'id')],
            'product_description' => ['nullable', 'string', 'max:300'],
            'brand_palette' => ['nullable', 'string', 'max:120'],
            'brand_style' => ['nullable', 'string', 'max:200'],
            'brand_identity_enabled' => ['nullable', 'boolean'],
            'brand_identity_prompt' => ['nullable', 'string', 'max:2000'],
            'quality_models' => ['required', 'array'],
            'quality_models.*.primary_id' => ['required', 'integer', Rule::exists('ai_models', 'id')],
            'quality_models.*.fallback_ids' => ['nullable', 'array', 'max:3'],
            'quality_models.*.fallback_ids.*' => ['integer', Rule::exists('ai_models', 'id')],
            'preflight_enabled' => ['nullable', 'boolean'],
            'preflight_model' => ['nullable', 'string', 'max:160'],
            'preflight_prompt' => ['nullable', 'string', 'max:4000'],
            'preflight_blocking_issues' => ['nullable', 'array'],
            'preflight_blocking_issues.*' => [Rule::in(array_keys(\App\Services\ProductShots\ShotVisionService::ISSUES))],
            'preflight_min_side' => ['required', 'integer', 'min:500', 'max:3000'],
            'product_sheet_size' => ['required', 'integer', 'min:1024', 'max:3072'],
            'category_ids' => [Rule::requiredIf($publishing), 'nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
            'shots' => ['required', 'array', 'min:1'],
            'shots.*.enabled' => ['nullable', 'boolean'],
            'shots.*.is_default' => ['nullable', 'boolean'],
            'shots.*.credits' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'shots.*.quality_credits' => ['required', 'array'],
            'shots.*.quality_credits.*' => ['required', 'integer', 'min:0', 'max:1000'],
            'shots.*.prompt_override' => ['nullable', 'string', 'max:5000'],
            'shots.*.model_overrides' => ['nullable', 'array'],
            'shots.*.model_overrides.*.primary_id' => ['nullable', 'integer', Rule::exists('ai_models', 'id')],
            'shots.*.model_overrides.*.fallback_ids' => ['nullable', 'array', 'max:3'],
            'shots.*.model_overrides.*.fallback_ids.*' => ['integer', Rule::exists('ai_models', 'id')],
            'shots.*.allowed_aspect_ratios' => ['required', 'array', 'min:1'],
            'shots.*.allowed_aspect_ratios.*' => [Rule::in((array) config('product_shots.aspect_ratios'))],
            'shots.*.aspect_ratio_default' => [Rule::in((array) config('product_shots.aspect_ratios'))],
            'shots.*.aspect_ratio_user_selectable' => ['nullable', 'boolean'],
            'shots.*.option_prompt' => ['nullable', 'boolean'],
            'shots.*.option_model' => ['nullable', 'boolean'],
            'shots.*.option_ratio' => ['nullable', 'boolean'],
            'shots.*.option_credits' => ['nullable', 'boolean'],
            'shots.*.option_preview' => ['nullable', 'boolean'],
            'shots.*.sort' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'shots.*.sample_path' => ['nullable', 'string', 'max:255'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'watermark_position' => ['nullable', Rule::in(['corner', 'center', 'none'])],
            'display_mode' => ['nullable', Rule::in(['card', 'slider'])],
            'card_shape' => ['nullable', Rule::in(['portrait', 'landscape', 'square'])],
            'gallery_layout' => ['nullable', Rule::in(['grid', 'masonry', 'slider'])],
            'card_label_enabled' => ['nullable', 'boolean'],
            'card_label' => ['nullable', 'string', 'max:100'],
            'explore_tiles' => ['nullable', 'array', 'min:1'],
            'explore_tiles.*' => [Rule::in(['1x1', '2x2', '1x2', '2x1'])],
        ], [
            'name_fa.required' => 'نام فارسی محصول را وارد کنید.',
            'name_en.required' => 'نام انگلیسی محصول را وارد کنید.',
            'occupation_ids.required' => 'حداقل یک صنف انتخاب کنید.',
            'quality_models.*.primary_id.required' => 'مدل اصلی هر سه سطح کیفیت را انتخاب کنید.',
            'category_ids.required' => 'برای انتشار، حداقل یک دسته انتخاب کنید.',
            'shots.required' => 'حداقل یک شات انتخاب کنید.',
        ]);

        $qualityKeys = array_keys((array) config('product_shots.quality_levels'));
        foreach ($qualityKeys as $quality) {
            if (empty($data['quality_models'][$quality]['primary_id'])) {
                throw ValidationException::withMessages(['quality_models' => 'مدل اصلی هر سه سطح کیفیت را انتخاب کنید.']);
            }
        }

        $enabled = collect((array) ($data['shots'] ?? []))->filter(fn ($row) => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN));
        if ($enabled->isEmpty()) {
            throw ValidationException::withMessages(['shots' => 'حداقل یک شات را فعال کنید.']);
        }
        if (! $enabled->contains(fn ($row) => filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
            throw ValidationException::withMessages(['shots' => 'حداقل یک شات فعال را در بسته‌ی آماده قرار دهید.']);
        }
        if ($publishing && empty($data['explore_tiles'])) {
            throw ValidationException::withMessages(['explore_tiles' => 'برای انتشار، حداقل یک قاب اکسپلور انتخاب کنید.']);
        }

        return $data;
    }

    private function fillProduct(Product $product, array $data, Request $request): void
    {
        $qualityModels = $this->resolveQualityModels((array) $data['quality_models']);
        $standard = $qualityModels['standard'] ?? reset($qualityModels);
        $occupationIds = array_values(array_unique(array_map('intval', (array) $data['occupation_ids'])));
        $occupations = Occupation::query()->whereIn('id', $occupationIds)->get();

        $product->name_fa = $data['name_fa'];
        $product->name_en = $data['name_en'];
        $product->description_fa = $data['description_fa'] ?? null;
        $product->status = $data['status'];
        $product->primary_model = (string) ($standard['primary_model'] ?? '');
        $product->ai_provider = (string) ($standard['primary_provider'] ?? 'openrouter');
        $product->fallback_models = (array) ($standard['fallback_models'] ?? []);
        $product->fallback_model_providers = (array) ($standard['fallback_providers'] ?? []);
        $product->shot_settings = [
            'niche' => (string) ($occupations->first()?->group_key ?? 'other'),
            'occupation_ids' => $occupationIds,
            'product_description' => trim((string) ($data['product_description'] ?? '')) ?: null,
            'brand_palette' => trim((string) ($data['brand_palette'] ?? '')) ?: null,
            'brand_style' => trim((string) ($data['brand_style'] ?? '')) ?: null,
            'brand_identity_enabled' => $request->boolean('brand_identity_enabled'),
            'brand_identity_prompt' => trim((string) ($data['brand_identity_prompt'] ?? '')) ?: null,
            'quality_models' => $qualityModels,
            'preflight' => [
                'enabled' => $request->boolean('preflight_enabled'),
                'model' => trim((string) ($data['preflight_model'] ?? '')) ?: null,
                'prompt' => trim((string) ($data['preflight_prompt'] ?? '')) ?: null,
                'blocking_issues' => array_values((array) ($data['preflight_blocking_issues'] ?? [])),
                'min_side' => (int) $data['preflight_min_side'],
                'product_sheet_enabled' => $request->boolean('product_sheet_enabled'),
                'product_sheet_size' => (int) $data['product_sheet_size'],
            ],
        ];

        // ستون‌های اجباری/قدیمی با مقادیر امن؛ هیچ‌کدام در مسیر پک خوانده نمی‌شوند
        // ولی فهرست‌ها، گزارش‌ها و سفارش‌ها به آن‌ها تکیه دارند.
        $product->prompt_template = $product->prompt_template ?: 'Product shot pack (see shot library).';
        $product->subject_type = 'product';
        $product->identity_preservation = false;
        $product->min_reference_images = 1;
        $product->max_reference_images = 4;
        $product->pricing_model = 'per_credit';
        $product->media_type = 'photo';
        $product->output_type = 'image';
        $product->output_format = $product->output_format ?: 'png';
        $product->output_count = 1;
        $product->allowed_aspect_ratios = array_values((array) config('product_shots.aspect_ratios'));
        $product->aspect_ratio = (string) config('product_shots.default_aspect_ratio', '4:5');
        $product->allowed_resolutions = $product->allowed_resolutions ?: Product::DEFAULT_OUTPUT_RESOLUTIONS;
        $product->resolution = $product->resolution ?: '1080';
        $product->timeout = max(120, (int) $product->timeout);
        $product->watermark_enabled = $request->boolean('watermark_enabled');
        $product->watermark_position = (string) ($data['watermark_position'] ?? 'corner');
        $product->display_mode = (string) ($data['display_mode'] ?? 'card');
        $product->card_shape = (string) ($data['card_shape'] ?? 'portrait');
        $product->gallery_layout = (string) ($data['gallery_layout'] ?? 'slider');
        $product->explore_tiles = array_values((array) ($data['explore_tiles'] ?? ['1x1']));
        $product->estimated_time = $product->estimated_time ?: 45;
        $product->tags = array_values(array_unique(array_merge((array) $product->tags, ['کسب‌وکار'], $occupations->pluck('name_fa')->all())));
        $product->card_label = trim((string) ($data['card_label'] ?? '')) ?: 'پک';
        $product->card_label_enabled = $request->boolean('card_label_enabled');

        $primaryCategoryId = (int) (($data['category_ids'] ?? [])[0] ?? 0) ?: null;
        if ($primaryCategoryId) {
            $product->category_id = $primaryCategoryId;
            $product->category = Category::query()->whereKey($primaryCategoryId)->value('name') ?: 'کسب‌وکار';
        } else {
            $product->category = $product->category ?: 'کسب‌وکار';
        }

        if ($request->hasFile('cover')) {
            $stored = $this->images->storeUpload($request->file('cover'), 'products/main');
            $product->cover = $stored['path'];
            $product->thumbnail = $stored['path'];
            $product->images_optimized_at = now();
        }
        $product->thumbnail = $product->thumbnail ?: ($product->cover ?: 'products/thumbnails/default_placeholder.jpg');
    }

    private function syncCategories(Product $product, array $data): void
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($data['category_ids'] ?? []))));
        $product->categories()->sync($ids);
    }

    private function syncOccupations(Product $product, array $data): void
    {
        $product->occupations()->sync(array_values(array_unique(array_map('intval', (array) ($data['occupation_ids'] ?? [])))));
    }

    private function syncShots(Product $product, Request $request): void
    {
        $rows = (array) $request->input('shots', []);
        $existing = ProductShot::query()->where('product_id', $product->id)->get()->keyBy('shot_id');
        $libraryIds = ShotLibrary::query()->whereIn('id', array_map('intval', array_keys($rows)))->pluck('id')->all();
        $minCredits = null;

        foreach ($libraryIds as $shotId) {
            $row = (array) ($rows[$shotId] ?? []);
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $productShot = $existing->get($shotId);
            if (! $enabled && ! $productShot) {
                continue;
            }
            $productShot ??= new ProductShot(['product_id' => $product->id, 'shot_id' => $shotId]);
            $productShot->enabled = $enabled;
            $productShot->is_default = $enabled && filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $productShot->credits_override = isset($row['credits']) && $row['credits'] !== '' ? max(0, (int) $row['credits']) : null;
            $productShot->prompt_override = trim((string) ($row['prompt_override'] ?? '')) ?: null;
            $productShot->model_configuration = [
                'quality_credits' => array_map('intval', (array) ($row['quality_credits'] ?? [])),
                'quality_models' => $this->resolveQualityModels((array) ($row['model_overrides'] ?? []), false),
            ];
            $allowedRatios = array_values(array_intersect((array) config('product_shots.aspect_ratios'), (array) ($row['allowed_aspect_ratios'] ?? [])));
            $productShot->allowed_aspect_ratios = $allowedRatios ?: [(string) config('product_shots.default_aspect_ratio', '4:5')];
            $defaultRatio = (string) ($row['aspect_ratio_default'] ?? '');
            $productShot->aspect_ratio_default = in_array($defaultRatio, $productShot->allowed_aspect_ratios, true) ? $defaultRatio : $productShot->allowed_aspect_ratios[0];
            $productShot->aspect_ratio_user_selectable = filter_var($row['aspect_ratio_user_selectable'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $productShot->options_enabled = [
                'prompt' => filter_var($row['option_prompt'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'model' => filter_var($row['option_model'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'ratio' => filter_var($row['option_ratio'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'credits' => filter_var($row['option_credits'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'preview' => filter_var($row['option_preview'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
            $productShot->sort = (int) ($row['sort'] ?? 0);
            if (! empty($row['sample_path'])) {
                $copied = $this->copySample((string) $row['sample_path']);
                if ($copied) {
                    $productShot->sample_image = $copied;
                }
            }
            $productShot->save();
            if ($enabled) {
                $credits = $productShot->fresh('shot')->credits('standard');
                $minCredits = $minCredits === null ? $credits : min($minCredits, $credits);
            }
        }

        // نمایش «از X کردیت» در کارت‌ها و فهرست ادمین
        $product->forceFill(['credit_cost' => $minCredits ?? 0])->saveQuietly();
    }

    private function ensureCover(Product $product): void
    {
        if ($product->cover && Storage::disk('public')->exists($product->cover)) {
            return;
        }
        $sample = ProductShot::query()->where('product_id', $product->id)->whereNotNull('sample_image')->orderBy('sort')->value('sample_image');
        if ($sample) {
            $product->forceFill(['cover' => $sample, 'thumbnail' => $sample])->saveQuietly();
        }
    }

    private function afterSave(Request $request, Product $product, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect' => route('admin.product-shots.products.create', $product->id),
                'product_id' => $product->id,
            ]);
        }

        return redirect()->route('admin.product-shots.products.create', $product->id)->with('success', $message);
    }

    private function copySample(string $path): ?string
    {
        if (! $this->isSafePreviewPath($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        $target = self::SAMPLE_DIR . '/' . Str::uuid() . '.' . (pathinfo($path, PATHINFO_EXTENSION) ?: 'png');
        Storage::disk('public')->copy($path, $target);

        return $target;
    }

    /** فقط فایل‌های خروجی/پیش‌نمایش همین ماژول قابل کپی هستند (جلوگیری از path traversal). */
    private function isSafePreviewPath(string $path): bool
    {
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            return false;
        }

        return str_starts_with($path, self::PREVIEW_DIR . '/') || preg_match('#^generated/shot_[A-Za-z0-9]+\.png$#', $path) === 1;
    }

    /** شناسه‌های فرم را به تنظیم پایدار مدل/ارائه‌دهنده تبدیل می‌کند. */
    private function resolveQualityModels(array $rows, bool $requireAll = true): array
    {
        $resolved = [];
        foreach (array_keys((array) config('product_shots.quality_levels')) as $quality) {
            $row = (array) ($rows[$quality] ?? []);
            $primaryId = (int) ($row['primary_id'] ?? 0);
            if ($primaryId <= 0) {
                if ($requireAll) continue;
                $resolved[$quality] = [];
                continue;
            }
            $primary = AiModel::query()->find($primaryId);
            if (! $primary) continue;
            $fallbacks = AiModel::query()
                ->whereIn('id', array_values(array_unique(array_map('intval', (array) ($row['fallback_ids'] ?? [])))))
                ->where('id', '!=', $primary->id)
                ->get();
            $resolved[$quality] = [
                'primary_id' => $primary->id,
                'primary_model' => (string) $primary->openrouter_model_id,
                'primary_provider' => (string) $primary->provider,
                'fallback_ids' => $fallbacks->pluck('id')->all(),
                'fallback_models' => $fallbacks->pluck('openrouter_model_id')->map(fn ($v) => (string) $v)->values()->all(),
                'fallback_providers' => $fallbacks->pluck('provider')->map(fn ($v) => (string) $v)->values()->all(),
            ];
        }

        return $resolved;
    }

    private function imageModels(): Collection
    {
        return AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'image')
            ->where('supports_image_input', true)
            ->where('featured_in_lab', true)
            ->whereIn('task_type', ['image_to_image', 'face_consistency'])
            ->whereIn('provider', ProviderStatus::enabled() ?: ['__none__'])
            ->orderByDesc('featured_in_image_studio')
            ->orderBy('studio_image_priority')
            ->orderBy('provider')
            ->orderBy('name')
            ->get(['id', 'name', 'provider', 'openrouter_model_id', 'cost_per_generation_usd', 'task_type']);
    }

    private function stats(ProductShotSetting $settings): array
    {
        $since = now()->subDays(7);
        $items = ShotBatchItem::query()->where('updated_at', '>=', $since)->whereIn('status', ['completed', 'failed'])->get(['status', 'credits_charged', 'credits_refunded', 'cost_usd', 'qc', 'qc_retries']);
        $completed = $items->where('status', 'completed');
        $costUsd = (float) $items->sum('cost_usd');
        $credits = (int) $items->sum('credits_charged');
        $rate = (float) (app(ExchangeRateService::class)->usdToIrr()['rate'] ?? 0);
        $costToman = $rate > 0 ? $costUsd * $rate / 10 : null;
        $revenueToman = $credits * max(1, (int) $settings->credit_price_toman);
        $qcChecked = $completed->filter(fn ($i) => ! empty($i->qc['checked']));

        return [
            'today_cost_usd' => round((float) ShotBatchItem::query()->whereDate('finished_at', today())->sum('cost_usd'), 3),
            'cap_usd' => (float) $settings->daily_cost_cap_usd,
            'shots_7d' => $items->count(),
            'success_rate' => $items->count() ? round($completed->count() * 100 / $items->count()) : null,
            'credits_7d' => $credits,
            'refunds_7d' => (int) $items->sum('credits_refunded'),
            'cost_usd_7d' => round($costUsd, 3),
            'cost_toman_7d' => $costToman !== null ? (int) round($costToman) : null,
            'revenue_toman_7d' => $revenueToman,
            'cost_ratio' => ($costToman !== null && $revenueToman > 0) ? round($costToman * 100 / $revenueToman) : null,
            'qc_pass_rate' => $qcChecked->count() ? round($qcChecked->filter(fn ($i) => ! empty($i->qc['passed']))->count() * 100 / $qcChecked->count()) : null,
            'qc_retries_7d' => (int) $items->sum('qc_retries'),
        ];
    }

    private function splitList(string $value, bool $integers = false): array
    {
        $parts = preg_split('/[\s,،]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_unique(array_map('trim', $parts)));

        return $integers ? array_values(array_filter(array_map('intval', $parts))) : $parts;
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'shot-pack';
        $slug = $base;
        $i = 1;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function uniqueShotKey(string $source): string
    {
        $base = Str::slug($source) ?: 'shot';
        $key = $base;
        $i = 1;
        while (ShotLibrary::query()->where('key', $key)->exists()) {
            $key = $base . '-' . (++$i);
        }

        return $key;
    }
}
