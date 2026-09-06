<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AiModel;
use App\Models\ProductMetricEvent;
use App\Models\GeneratedImage;
use App\Models\UserUpload;
use App\Models\Order;
use App\Models\AiProviderRequest;
use App\Models\Discount;
use App\Models\FaceProfile;
use App\Models\ReferralSetting;
use App\Models\TelegramUser;
use App\Services\AiProviderRouter;
use App\Services\CreditWalletService;
use App\Services\ProductBuildSchema;
use App\Services\ProductPromptBuilder;
use App\Services\SmsEventService;
use App\Services\ModelTierService;
use App\Services\VideoProductConfigService;
use App\Services\StudioCostService;
use App\Http\Requests\GenerateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductGenerateController extends Controller
{
    public function __construct(
        protected AiProviderRouter $openRouter,
        protected CreditWalletService $creditWallet,
        protected ModelTierService $modelTiers,
    )
    {
    }

    public function create(Request $request, ProductBuildSchema $schema)
    {
        $slug = $request->query('product');
        // هر دو لینک قدیمی و جدید را پشتیبانی می‌کنیم:
        // ?product=slug و ?product=123456-slug باید همان محصول را باز کنند.
        // اگر محصول مشخص شده باشد، هرگز به‌صورت بی‌صدا محصول دیگری جایگزین نشود.
        if ($slug !== null && $slug !== '') {
            $product = (new Product())->resolveRouteBinding($slug, 'route_slug');
            abort_unless($product && $product->status === 'active', 404);
            if ($product->isVideoProduct()) {
                return app(VideoProductController::class)->show(
                    $request,
                    $product,
                    $schema,
                    app(\App\Services\VideoModelSchemaService::class),
                );
            }
        } else {
            // لینک عمومی «بساز» ابتدا همه‌ی محصولات فعال را برای انتخاب نمایش می‌دهد.
            $products = Product::query()
                ->where('status', 'active')
                ->latest()
                ->get();

            return view('app.create-products', compact('products'));
        }

        $user = $request->user();
        $tierKey = $this->modelTiers->tierKeyForUser($user);
        return view('app.create-product', [
            'product' => $product,
            'buildProduct' => $schema->pageData(
                $product,
                $this->modelTiers->tierMeta($tierKey, $user?->plan),
                $this->modelTiers->outputQualityOptions($user, $product),
            ),
        ]);
    }

    /**
     * استودیوی عمومی ساخت، تنظیماتش را از دو محصول واقعی فعال می‌گیرد تا
     * فرم عمومی هم از همان مسیر اعتبارسنجی و اجرای محصول استفاده کند.
     */
    public function createStudio(ProductBuildSchema $schema)
    {
        return view('app.create-studio', $this->studioViewData($schema));
    }

    /**
     * داده‌ی مشترک استودیوی اصلی و نسخه‌ی آزمایشی گردش‌کارها.
     */
    public function studioViewData(ProductBuildSchema $schema): array
    {
        $imageProduct = Product::query()
            ->where('status', 'active')
            ->where('slug', 'ai-fashion-portrait')
            ->first()
            ?: Product::query()->where('status', 'active')->where('output_type', 'image')->latest()->first();

        $videoProduct = Product::query()
            ->where('status', 'active')
            ->where('slug', 'ai-cinematic-short-video')
            ->first()
            ?: Product::query()->where('status', 'active')->where('output_type', 'video')->latest()->first();

        abort_unless($imageProduct && $videoProduct, 404);

        $imageData = $schema->pageData($imageProduct);
        // استودیوی اصلی نسبت‌های استاندارد را نمایش می‌دهد؛ مدل انتخاب‌شده
        // در رابط کاربری گزینه‌های ناسازگار را فیلتر می‌کند.
        $imageData['output_aspect_ratios'] = Product::supportedAspectRatios();
        $imageData['default_output_aspect_ratio'] = '3:4';
        $videoData = $schema->pageData($videoProduct);
        $videoData['video'] = $videoProduct->videoConfiguration();
        // استودیوی عمومی دامنه‌ی کامل تنظیمات ساخت را در اختیار کاربر می‌گذارد؛
        // محدودیت‌های کوتاه فرم قدیمی محصول نباید رابط استودیو را محدود کند.
        $videoData['video']['durations'] = range(1, 15);
        $videoData['video']['default_duration'] = 4;
        $videoData['video']['resolutions'] = VideoProductConfigService::RESOLUTIONS;
        $videoData['video']['default_resolution'] = '720p';
        $videoData['video']['aspect_ratios'] = array_values(array_intersect(
            VideoProductConfigService::STUDIO_ASPECT_RATIOS,
            array_values(array_unique(array_merge((array) ($videoData['video']['aspect_ratios'] ?? []), VideoProductConfigService::STUDIO_ASPECT_RATIOS))),
        ));
        $defaultVideoDuration = max(1, (int) ($videoData['video']['default_duration'] ?? 4));
        $defaultVideoCredit = max(0, app(VideoProductConfigService::class)->creditCost($videoProduct, $defaultVideoDuration));
        $videoData['video']['credit_costs_by_duration'] = collect(range(1, 15))
            ->mapWithKeys(fn (int $duration): array => [(string) $duration => $defaultVideoCredit * (int) ceil($duration / $defaultVideoDuration)])
            ->all();
        $videoData['video']['motion_presets'] = collect(app(VideoProductConfigService::class)->motionPresetCatalog())
            ->map(fn (array $preset, string $key): array => ['key' => $key] + $preset)
            ->values()->all();

        return [
            'studioConfig' => [
                'image' => $this->studioProductConfig($imageProduct, $imageData, $schema, 'image'),
                'video' => $this->studioProductConfig($videoProduct, $videoData, $schema, 'video'),
                'authenticated' => auth()->check(),
                'login_url' => route('login', ['redirect' => request()->fullUrl()]),
            ],
        ];
    }

    public function studioQuote(Request $request, StudioCostService $studioCosts): \Illuminate\Http\JsonResponse
    {
        $mode = $request->query('mode') === 'video' ? 'video' : 'image';
        $product = Product::query()->where('status', 'active')->where('output_type', $mode)->where('slug', $mode === 'video' ? 'ai-cinematic-short-video' : 'ai-fashion-portrait')->first()
            ?: Product::query()->where('status', 'active')->where('output_type', $mode)->latest()->firstOrFail();
        $model = $this->studioQuoteModel($request, $product, $mode);
        $quote = $studioCosts->quote($product, [
            'media_type' => $mode,
            'resolution' => (string) $request->query('resolution', ''),
            'aspect_ratio' => (string) $request->query('aspect_ratio', ''),
            'duration' => $mode === 'video' ? max(1, min(15, (int) $request->query('duration', 4))) : null,
            'count' => $mode === 'image' ? max(1, min(6, (int) $request->query('count', 1))) : 1,
        ], $model);

        return response()->json($quote);
    }

    private function studioQuoteModel(Request $request, Product $product, string $mode): ?AiModel
    {
        $requestedModelId = trim((string) $request->query('model', ''));
        $modelId = $requestedModelId ?: (string) $product->primary_model;
        if ($modelId === '') return null;

        $primary = AiModel::query()->where('is_active', true)->where('output_modality', $mode)
            ->whereIn('task_type', $this->studioTaskTypes($mode))
            ->where('openrouter_model_id', $modelId)
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', (string) $request->query('provider')), fn ($query) => $query->where('provider', (string) $product->ai_provider))
            ->first();
        if ($requestedModelId !== '' || $this->hasStoredModelPrice($primary)) return $primary;

        return AiModel::query()->where('is_active', true)->where('output_modality', $mode)
            ->whereIn('task_type', $this->studioTaskTypes($mode))
            ->whereIn('provider', ['fal', 'replicate'])
            ->whereNotNull('openrouter_model_id')->where('openrouter_model_id', '<>', '')
            ->orderByRaw($mode === 'video'
                ? "CASE task_type WHEN 'text_to_video' THEN 0 WHEN 'image_to_video' THEN 1 WHEN 'video_to_video' THEN 2 ELSE 3 END"
                : "CASE task_type WHEN 'text_to_image' THEN 0 WHEN 'image_to_image' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE provider WHEN 'fal' THEN 0 WHEN 'replicate' THEN 1 ELSE 2 END")
            ->orderByDesc('lab_priority')->first() ?: $primary;
    }

    private function studioProductConfig(Product $product, array $data, ProductBuildSchema $schema, string $modality): array
    {
        $fields = $schema->fields($product);
        $defaults = [];
        foreach ($fields as $field) {
            if (($field['id'] ?? '') === '' || ($field['type'] ?? '') === 'file_upload') continue;
            $options = (array) ($field['options'] ?? []);
            if ($options !== []) {
                $defaults[$field['id']] = (string) ($options[0]['value'] ?? '');
            } elseif (($field['type'] ?? '') === 'text') {
                $defaults[$field['id']] = 'استودیوی وطن';
            } elseif (($field['type'] ?? '') === 'number') {
                $defaults[$field['id']] = (string) ($field['min'] ?? 1);
            }
        }

        return [
            'id' => $product->id,
            'route_slug' => $product->route_slug,
            'generate_url' => route('app.create.generate', $product->route_slug),
            'name' => $data['name'],
            'cover' => $data['cover'],
            'description' => $data['description'],
            'cost' => (int) ($data['cost'] ?? 0),
            'estimated_time' => $data['estimated_time'] ?? 'حدود یک دقیقه',
            'model' => $product->primary_model ?: 'مدل پیش‌فرض وطن',
            'model_options' => $this->studioModelOptions($product, $modality),
            'fields' => $data['fields'] ?? [],
            'reference_upload_key' => data_get(collect($fields)->first(fn (array $field): bool => in_array($field['type'] ?? '', ['image_upload', 'multi_image'], true)), 'id'),
            'requires_reference' => (int) ($product->min_reference_images ?? 0) > 0,
            'defaults' => $defaults,
            'output_aspect_ratios' => $data['output_aspect_ratios'] ?? [],
            'default_output_aspect_ratio' => $data['default_output_aspect_ratio'] ?? null,
            'output_resolutions' => $modality === 'image'
                ? array_values(array_unique(array_merge((array) ($data['output_resolutions'] ?? []), ['2160'])))
                : ($data['output_resolutions'] ?? []),
            'default_output_resolution' => $data['default_output_resolution'] ?? null,
            'main_quality_options' => $data['main_quality_options'] ?? [],
            'default_main_quality' => data_get($data, 'main_quality_options.0.key', 'standard'),
            'output_count' => max(1, (int) ($data['output_count'] ?? 1)),
            'video' => $data['video'] ?? null,
        ];
    }

    private function studioModelOptions(Product $product, string $modality): array
    {
        $models = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', $modality)
            ->whereIn('task_type', $this->studioTaskTypes($modality))
            ->whereNotNull('openrouter_model_id')
            ->where('openrouter_model_id', '<>', '')
            ->orderByRaw($modality === 'video'
                ? "CASE task_type WHEN 'text_to_video' THEN 0 WHEN 'image_to_video' THEN 1 WHEN 'video_to_video' THEN 2 ELSE 3 END"
                : "CASE task_type WHEN 'text_to_image' THEN 0 WHEN 'image_to_image' THEN 1 ELSE 2 END")
            ->orderByDesc('lab_priority')
            ->orderBy('id')
            ->get([
                'name',
                'openrouter_model_id',
                'provider',
                'capability_config',
                'pricing_config',
            ]);

        $options = $models->map(fn (AiModel $model): array => [
            'value' => (string) $model->openrouter_model_id,
            'label' => (string) ($model->name ?: $model->openrouter_model_id),
            'meta' => strtoupper((string) $model->provider),
            'provider' => (string) $model->provider,
            'supported_aspect_ratios' => $this->studioModelSupportedOptions($model, 'aspect_ratios', $modality),
            'supported_resolutions' => $this->studioModelSupportedOptions($model, 'resolutions', $modality),
        ])->unique('value')->values();

        $primary = (string) $product->primary_model;
        if ($primary !== '' && !$options->contains('value', $primary)) {
            $options->prepend([
                'value' => $primary,
                'label' => $primary,
                'meta' => strtoupper((string) $product->ai_provider),
                'provider' => (string) $product->ai_provider,
                'supported_aspect_ratios' => [],
                'supported_resolutions' => [],
            ]);
        }

        return $options->map(function (array $option) use ($primary): array {
            $isPrimary = (string) $option['value'] === $primary;
            return [
                ...$option,
                'meta' => ($isPrimary ? 'مدل اصلی' : 'مدل جایگزین') . ' · ' . ($option['meta'] ?: 'سرویس ساخت'),
                'role' => $isPrimary ? 'primary' : 'fallback',
            ];
        })->values()->all();
    }

    /**
     * قابلیت‌های مؤثر مدل را به رابط استودیو می‌دهد تا گزینه‌های ناسازگار
     * پیش از ارسال درخواست حذف شوند. برای مدل‌های قدیمی که هنوز این دو
     * مقدار را در دیتابیس ندارند، فقط خانواده‌هایی که محدودیت شناخته‌شده
     * دارند fallback مشخص دریافت می‌کنند.
     */
    private function studioModelSupportedOptions(AiModel $model, string $kind, string $modality): array
    {
        $key = 'supported_' . $kind;
        $capabilities = (array) ($model->capability_config ?? []);
        $pricing = (array) ($model->pricing_config ?? []);
        $values = data_get($capabilities, $key, data_get($pricing, $key, []));

        if (is_array($values) && $values !== []) {
            return array_values(array_map('strval', $values));
        }

        $modelId = strtolower((string) $model->openrouter_model_id);
        if ($modality === 'image' && $kind === 'aspect_ratios' && str_starts_with($modelId, 'black-forest-labs/flux.2-')) {
            return ['1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16', '21:9'];
        }

        return [];
    }

    private function applyStudioModel(Product $product, Request $request, string $modality): void
    {
        $modelId = trim((string) $request->input('studio_model', ''));
        $query = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', $modality)
            ->whereIn('task_type', $this->studioTaskTypes($modality))
            ->when($modelId !== '', fn ($builder) => $builder->where('openrouter_model_id', $modelId))
            ->when($request->filled('studio_provider'), fn ($builder) => $builder->where('provider', (string) $request->input('studio_provider')),
                fn ($builder) => $modelId === '' ? $builder->orderByRaw("CASE provider WHEN 'fal' THEN 0 WHEN 'replicate' THEN 1 ELSE 2 END")->orderByDesc('lab_priority') : $builder);
        $model = $query->first();
        if (!$model) {
            throw ValidationException::withMessages(['studio_model' => 'مدل انتخاب‌شده برای این نوع ساخت فعال نیست.']);
        }

        $candidates = collect([(string) $product->primary_model => (string) $product->ai_provider]);
        foreach ((array) $product->fallback_models as $index => $fallback) {
            $fallback = (string) $fallback;
            if ($fallback !== '') {
                $fallbackProviders = (array) $product->fallback_model_providers;
                $candidates->put($fallback, (string) ($fallbackProviders[$index] ?? 'openrouter'));
            }
        }
        $candidates->forget((string) $model->openrouter_model_id);
        if ($model->allowsPromotionalCredits()) {
            $candidates = $candidates->filter(function (string $provider, string $candidateId): bool {
                $candidate = AiModel::query()
                    ->where('is_active', true)
                    ->where('provider', $provider)
                    ->where('openrouter_model_id', $candidateId)
                    ->first();

                return $candidate?->allowsPromotionalCredits() === true;
            });
        }
        $product->primary_model = (string) $model->openrouter_model_id;
        $product->ai_provider = (string) $model->provider;
        $product->fallback_models = $candidates->keys()->values()->all();
        $product->fallback_model_providers = $candidates->values()->all();
    }

    private function studioTaskTypes(string $modality): array
    {
        return $modality === 'video'
            ? ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation']
            : ['text_to_image', 'image_to_image', 'face_consistency'];
    }

    private function hasStoredModelPrice(?AiModel $model): bool
    {
        $pricing = (array) ($model?->pricing_config ?? []);

        return $model !== null && (
            (float) $model->cost_per_generation_usd > 0
            || (float) data_get($pricing, 'unit_price', 0) > 0
            || (float) data_get($pricing, 'price', 0) > 0
            || (array) data_get($pricing, 'resolution_tiers', []) !== []
            || (array) data_get($pricing, 'tiers', []) !== []
            || (array) data_get($pricing, 'duration_prices', []) !== []
        );
    }

    public function build(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        // مسیر قدیمی را به مسیر اصلی منتقل می‌کنیم تا تمام لینک‌ها یک تجربه و
        // یک فرم ساخت داشته باشند و مشخصات محصول در صفحه‌ی «بساز» از بین نرود.
        return redirect()->route('app.create', ['product' => $product->route_slug]);
    }

    /**
     * پیش‌نمایش ایزوله رابط کاربری «بساز»؛ تا پیش از تأیید نهایی به محصول یا
     * مسیر عملیاتی متصل نمی‌شود و تمام حالت‌های شِمای ورودی را نمایش می‌دهد.
     */
    public function createPreview()
    {
        $previewProduct = $this->previewProductData();

        return view('app.create-preview', compact('previewProduct'));
    }

    private function previewProductData(): array
    {
        return [
            'name' => 'پرتره سینمایی فوق‌واقعی',
            'description' => 'چهره شما با حفظ دقیق هویت، نورپردازی سینمایی و جزئیات طبیعی بازآفرینی می‌شود.',
            'cover' => asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif'),
            'cost' => 18,
            'estimated_time' => 'حدود ۴۵ ثانیه',
            'output_count' => 4,
            'fields' => $this->previewInputSchema(),
        ];
    }

    private function previewInputSchema(): array
    {
        return [
            ['id' => 'guide', 'type' => 'info', 'label' => 'برای بیشترین شباهت، یک عکس واضح و روبه‌رو با نور طبیعی انتخاب کنید.'],
            ['id' => 'identity_section', 'type' => 'section', 'label' => 'تصاویر هویتی', 'help' => 'تصویری انتخاب کنید که جزئیات صورت در آن کاملاً مشخص باشد.'],
            ['id' => 'portrait', 'type' => 'image_upload', 'label' => 'تصویر اصلی چهره', 'help' => 'JPG، PNG یا WebP · حداکثر ۱۰ مگابایت', 'required' => true],
            ['id' => 'references', 'type' => 'multi_image', 'label' => 'تصاویر مرجع بیشتر', 'help' => 'اختیاری · تا ۳ زاویه دیگر برای حفظ بهتر هویت'],
            ['id' => 'identity_divider', 'type' => 'divider', 'label' => ''],
            ['id' => 'creative_section', 'type' => 'section', 'label' => 'جزئیات خلاقانه', 'help' => 'این موارد ظاهر و فضای خروجی را شخصی‌سازی می‌کنند.'],
            ['id' => 'scene', 'type' => 'textarea', 'label' => 'فضایی که در ذهن دارید', 'placeholder' => 'مثلاً: یک خیابان خیس در شب با نورهای نئونی...'],
            ['id' => 'custom_prompt', 'type' => 'prompt', 'label' => 'دستور اختصاصی شما', 'placeholder' => 'جزئیات خاصی که دوست دارید در خروجی دیده شود...'],
            ['id' => 'title', 'type' => 'text', 'label' => 'متن کوتاه روی تصویر', 'placeholder' => 'اختیاری'],
            ['id' => 'age', 'type' => 'number', 'label' => 'سن ظاهری', 'min' => 18, 'max' => 80, 'value' => 30],
            ['id' => 'mood', 'type' => 'radio', 'label' => 'حس‌وحال چهره', 'value' => 'confident', 'options' => [['value'=>'confident','label'=>'بااعتمادبه‌نفس'],['value'=>'calm','label'=>'آرام'],['value'=>'serious','label'=>'جدی'],['value'=>'smile','label'=>'لبخند ملایم']]],
            ['id' => 'camera', 'type' => 'select', 'label' => 'نوع قاب دوربین', 'value' => 'portrait', 'options' => [['value'=>'close','label'=>'نمای نزدیک'],['value'=>'portrait','label'=>'پرتره نیم‌تنه'],['value'=>'full','label'=>'تمام‌قد']]],
            ['id' => 'details', 'type' => 'multi_select', 'label' => 'جزئیات تکمیلی', 'options' => [['value'=>'rain','label'=>'باران'],['value'=>'fog','label'=>'مه'],['value'=>'grain','label'=>'گرین فیلم'],['value'=>'bokeh','label'=>'بوکه پس‌زمینه']]],
            ['id' => 'lighting', 'type' => 'button_group', 'label' => 'نورپردازی', 'value' => 'cinematic', 'options' => [['value'=>'soft','label'=>'نرم'],['value'=>'cinematic','label'=>'سینمایی'],['value'=>'studio','label'=>'استودیویی']]],
            ['id' => 'identity', 'type' => 'strength', 'label' => 'میزان حفظ شباهت', 'value' => 90, 'min' => 50, 'max' => 100, 'unit' => '٪'],
            ['id' => 'cinematic_depth', 'type' => 'slider', 'label' => 'عمق سینمایی', 'value' => 65, 'min' => 0, 'max' => 100, 'unit' => '٪'],
            ['id' => 'background', 'type' => 'color', 'label' => 'رنگ غالب پس‌زمینه', 'value' => '#18221f'],
            ['id' => 'preserve_body', 'type' => 'switch', 'label' => 'فرم بدن نیز حفظ شود', 'value' => true],
            ['id' => 'confirm', 'type' => 'checkbox', 'label' => 'حق استفاده از تصاویر بارگذاری‌شده را دارم', 'required' => true],
            ['id' => 'style', 'type' => 'style_preset', 'label' => 'استایل خروجی', 'value' => 'cinematic', 'options' => [
                ['value'=>'cinematic','label'=>'سینمایی','image'=>asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg')],
                ['value'=>'editorial','label'=>'ادیتوریال','image'=>asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif')],
                ['value'=>'classic','label'=>'کلاسیک','image'=>asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp')],
            ]],
            ['id' => 'ratio', 'type' => 'aspect_ratio', 'label' => 'نسبت تصویر', 'value' => '4:5', 'options' => [['value'=>'1:1','label'=>'۱:۱'],['value'=>'4:5','label'=>'۴:۵'],['value'=>'9:16','label'=>'۹:۱۶'],['value'=>'16:9','label'=>'۱۶:۹']]],
            ['id' => 'resolution', 'type' => 'resolution', 'label' => 'کیفیت خروجی', 'value' => '2K', 'options' => [['value'=>'1K','label'=>'1K','meta'=>'استاندارد'],['value'=>'2K','label'=>'2K','meta'=>'پیشنهادی'],['value'=>'4K','label'=>'4K','meta'=>'+ ۶ اعتبار']]],
            ['id' => 'negative', 'type' => 'negative_prompt', 'label' => 'موارد ناخواسته', 'placeholder' => 'مثلاً: عینک، نوشته، تاری...'],
            ['id' => 'seed', 'type' => 'seed', 'label' => 'Seed', 'placeholder' => 'تصادفی'],
            ['id' => 'source_file', 'type' => 'file_upload', 'label' => 'فایل مرجع تکمیلی', 'help' => 'اختیاری'],
        ];
    }

    public function show(Product $product)
    {
        if ($product->isVideoProduct()) {
            return app(VideoProductController::class)->show(request(), $product, app(ProductBuildSchema::class));
        }

        $metricPayload = [
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        ProductMetricEvent::create($metricPayload + ['event_type' => 'view']);
        if (request()->query('source') === 'trends') {
            ProductMetricEvent::create($metricPayload + ['event_type' => 'trend_open']);
        }

        $product->loadCount('likedByUsers');

        $similar = Product::where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->latest()->limit(6)->get();

        // وضعیت سیو بودن محصول برای کاربر لاگین‌کرده فعلی (برای رنگ‌آمیزی اولیه دکمه سیو)
        $isSaved = auth()->check() ? auth()->user()->hasSavedProduct($product->id) : false;

        // وضعیت لایک بودن محصول (try/catch: اگر مایگریشن liked_products هنوز اجرا نشده باشد صفحه نمی‌شکند)
        $isLiked = false;
        if (auth()->check()) {
            try { $isLiked = auth()->user()->hasLikedProduct($product->id); } catch (\Throwable $e) {}
        }

        $referralSettings = ReferralSetting::current();
        $productReferralUrl = auth()->check() && $referralSettings->referralIsActive()
            ? route('referral.product', [
                'code' => auth()->user()->referral_code,
                'product' => $product->route_slug,
            ])
            : null;

        return view('app.product', compact(
            'product', 'similar', 'isSaved', 'isLiked', 'referralSettings', 'productReferralUrl'
        ));
    }

    public function generate(GenerateProductRequest $request, Product $product, ProductBuildSchema $schema, ProductPromptBuilder $promptBuilder, StudioCostService $studioCosts)
    {
        if ($product->isVideoProduct()) {
            return app(VideoProductController::class)->generate(
                $request,
                $product,
                $schema,
                app(\App\Services\VideoGenerationService::class),
            );
        }

        $this->applyStudioModel($product, $request, 'image');
        $user = auth()->user();
        $faceProfile = $this->selectedFaceProfile($request, $user);
        $requestedMainQuality = (string) $request->input('output.main_quality', 'standard');
        $mainQuality = $this->modelTiers->resolveOutputQuality($user, $requestedMainQuality, $product);
        if (! $mainQuality) {
            return response()->json([
                'success' => false,
                'message' => 'برای انتخاب این کیفیت، ابتدا یکی از پلن‌های اعتباری را فعال کنید.',
            ], 403);
        }

        $tierKey = $mainQuality['execution_tier_key'];
        $tierMeta = $this->modelTiers->tierMeta($tierKey, $user?->plan);
        $creditReservation = ['total' => 0, 'promotional' => 0, 'paid' => 0];
        $creditReservationSettled = false;
        $order = null;
        $fieldValues = (array) $request->input('fields', []);
        $identityRequested = (bool) $product->identity_preservation && $request->boolean('identity_preservation');
        $creditCost = max(0, (int) $mainQuality['credits'])
            + $schema->additionalCredit($product, $fieldValues)
            + ($identityRequested ? max(0, (int) $product->identity_credit_cost) : 0);

        $studioModel = $this->selectedStudioImageModel($request, $product);
        $studioQuote = $request->boolean('studio_mode')
            ? $studioCosts->quote($product, [
                'media_type' => 'image',
                'resolution' => (string) $request->input('output.quality', ''),
                'aspect_ratio' => (string) $request->input('output.aspect_ratio', ''),
                'count' => 1,
            ], $studioModel)
            : null;
        if ($studioQuote && !$studioQuote['cost_known']) {
            throw ValidationException::withMessages(['studio_model' => 'قیمت مدل انتخاب‌شده هنوز در کاتالوگ پروایدر ثبت نشده است.']);
        }
        if ($studioQuote && $studioQuote['credits'] !== null) {
            $creditCost = (int) ceil((int) $studioQuote['credits'] / max(1, (int) $request->input('output.count', 1)));
        }

        // ۰. مدل‌های خروجی چندگانه (Output Variants) — اگر محصول واریانت دارد،
        // کاربر باید حداقل یکی را انتخاب کرده باشد و هزینه اعتبار در تعداد انتخاب ضرب می‌شود.
        $variantList = $product->outputVariantList();
        $selectedVariants = [];
        if (!empty($variantList)) {
            $requestedKeys = array_map('strval', (array) $request->input('variants', []));
            foreach ($variantList as $v) {
                if (in_array($v['key'], $requestedKeys, true)) {
                    $selectedVariants[] = $v;
                }
            }
            if (empty($selectedVariants)) {
                return response()->json([
                    'success' => false,
                    'message' => 'حداقل یک مدل خروجی را انتخاب کنید.',
                ], 422);
            }
        }
        $requestedOutputCount = max(1, min(6, (int) $request->input('output.count', 1)));
        $runCount = max(1, count($selectedVariants) ?: $requestedOutputCount);
        $originalCreditCost = $creditCost * $runCount;
        $totalCreditCost = $originalCreditCost;
        $discount = null;
        $discountCredits = 0;

        if ($request->filled('discount_code')) {
            $code = strtoupper(trim((string) $request->input('discount_code')));
            $discount = Discount::available()->where('code', $code)->first();
            if (!$discount) {
                return response()->json(['success' => false, 'message' => 'کد تخفیف معتبر یا فعال نیست.'], 422);
            }
            if ($discount->first_order_only && Order::where('user_id', $user?->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'این کد فقط برای اولین سفارش قابل استفاده است.'], 422);
            }
            if ($user && Order::where('user_id', $user->id)->where('discount_id', $discount->id)->count() >= $discount->usage_limit_per_user) {
                return response()->json(['success' => false, 'message' => 'سقف استفاده شما از این کد تخفیف تکمیل شده است.'], 422);
            }
            $inScope = $discount->scope === 'all'
                || ($discount->scope === 'products' && in_array($product->id, $discount->product_ids ?? [], true))
                || ($discount->scope === 'categories' && $product->categories()->whereIn('categories.id', $discount->category_ids ?? [])->exists());
            if (!$inScope) {
                return response()->json(['success' => false, 'message' => 'این کد برای محصول انتخاب‌شده قابل استفاده نیست.'], 422);
            }
            $discountCredits = $discount->calculateCredits($originalCreditCost);
            if ($discountCredits < 1) {
                return response()->json(['success' => false, 'message' => 'حداقل اعتبار لازم برای این کد تخفیف تأمین نشده است.'], 422);
            }
            $totalCreditCost = max(0, $originalCreditCost - $discountCredits);
        }

        // اصلاح دریافت فایل‌ها بر اساس ساختار ارسالی جاوااسکریپت (uploads)
        $allFiles = $schema->flattenUploads($request);
        if (in_array($product->subject_type, ['face', 'body'], true) && count($allFiles) > 3) {
            return response()->json([
                'success' => false,
                'message' => 'برای محصولات چهره‌محور حداکثر ۳ عکس مرجع قابل استفاده است.',
            ], 422);
        }

        // ۲. بررسی سخت‌گیرانه سقف فضای ذخیره‌سازی (حداکثر ۱۰۰ مگابایت)
        if ($user) {
            $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
            $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;
            $faceProfilesSize = Schema::hasTable('face_profiles')
                ? $user->faceProfiles()->active()->get()->sum(function (FaceProfile $profile): int {
                    return collect($profile->referenceImageEntries())->sum(fn (array $image) => (int) ($image['size'] ?? 0));
                })
                : 0;
            $currentUsedBytes = $createdImagesSize + $personalImagesSize + $faceProfilesSize;

            $newUploadsSize = 0;
            foreach ($allFiles as $file) {
                if ($file) {
                    $newUploadsSize += $file->getSize();
                }
            }

            $maxStorageBytes = 100 * 1024 * 1024;
            $estimatedAiImageSize = (2 * 1024 * 1024) * $runCount;

            if (($currentUsedBytes + $newUploadsSize + $estimatedAiImageSize) > $maxStorageBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'فضای ذخیره‌سازی ۱۰۰ مگابایتی شما کافی نیست! لطفاً ابتدا فایل‌های قبلی خود را مدیریت یا پاک کنید.',
                ], 400);
            }
        }

        // ۳. ساخت پرامپت نهایی: system_prompt + قالب (با جایگذاری متغیرها) + دستور حفظ هویت
        $finalPrompt = $promptBuilder->build($product, $fieldValues, $identityRequested);
        $studioPrompt = trim((string) $request->input('studio_prompt', ''));
        if ($studioPrompt !== '') {
            $finalPrompt .= "\n\nUser creative direction: {$studioPrompt}";
        }

        // مقدار فیلدهای اختیاری را بر اساس نوع فیلد پیدا می‌کنیم. این closure
        // باید قبل از ساخت payload provider تعریف شود؛ در غیر این صورت هر
        // محصولی که به فیلد منفی، seed یا strength برسد با خطای PHP متوقف می‌شود.
        $valueForType = function (string $type) use ($schema, $product, $fieldValues) {
            $field = collect($schema->fields($product))->firstWhere('type', $type);
            return $field ? data_get($fieldValues, $field['id']) : null;
        };

        // ۴. پردازش و ذخیره‌سازی عکس‌های آپلودی کاربر
        $base64Images  = [];
        $uploadedPaths = [];

        foreach ($allFiles as $file) {
            if (!$file) continue;

            $path = $file->store('uploads/personal', 'public');
            $uploadedPaths[] = [
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];

            $mime = $file->getMimeType();
            if (str_starts_with((string) $mime, 'image/')) {
                $b64 = base64_encode(file_get_contents($file->getRealPath()));
                $base64Images[] = "data:{$mime};base64,{$b64}";
            }
        }

        if ($faceProfile) {
            foreach ($faceProfile->referenceImageEntries() as $image) {
                $path = (string) $image['path'];
                $disk = Storage::disk('public');
                if (! $disk->exists($path)) {
                    continue;
                }

                $mime = (string) ($image['mime'] ?: $disk->mimeType($path));
                if (! str_starts_with($mime, 'image/')) {
                    continue;
                }

                $base64Images[] = "data:{$mime};base64," . base64_encode($disk->get($path));
            }
        }

        // ۴.۱ الزام تصویر مرجع برای محصولات هویت‌محور/ویرایشی
        $minRefs = (int) ($product->min_reference_images ?? 0);
        if ($identityRequested && ! $faceProfile && $minRefs > 0 && count($base64Images) < $minRefs) {
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            return response()->json([
                'success' => false,
                'message' => "این محصول برای نتیجهٔ دقیق به حداقل {$minRefs} تصویر ورودی نیاز دارد.",
            ], 422);
        }

        // ۵. مشخصات خروجی تصویر هوش مصنوعی
        // کیفیت و نسبت تصویر از گزینه‌های فعال همان محصول می‌آیند؛ شِمای قدیمی
        // فقط تنظیمات داخلی محصول را نگه می‌دارد و نباید انتخاب کاربر را بازنویسی کند.
        // مقدار خالی نیز باید به کیفیت استاندارد محصول برگردد تا کلیک مستقیم روی
        // «بساز» هیچ‌وقت درخواست تولید بدون کیفیت نفرستد.
        $allowedAspectRatios = $request->boolean('studio_mode') && !$product->isVideoProduct()
            ? Product::supportedAspectRatios()
            : $product->allowedAspectRatioList();
        $requestedAspectRatio = (string) $request->input('output.aspect_ratio', '');
        $aspectRatio = in_array($requestedAspectRatio, $allowedAspectRatios, true)
            ? $requestedAspectRatio
            : $product->defaultOutputAspectRatio();

        // در استودیوی عمومی، کیفیت انتخاب‌شده از کشوی خود صفحه به مسیر واقعی
        // تولید منتقل می‌شود؛ فرم‌های قدیمی همچنان از سیاست پیش‌فرض محصول و
        // پلن کاربر استفاده می‌کنند.
        $requestedQuality = (string) $request->input('output.quality', '');
        $quality = $request->routeIs('app.create.generate') && in_array($requestedQuality, array_values(array_unique(array_merge(
            $product->allowedResolutionList(), ['2160']
        ))), true)
            ? $requestedQuality
            : $product->defaultOutputResolutionForUser($user);

        $executionProduct = $this->modelTiers->executionProductForQuality(
            $product,
            $user,
            $mainQuality['key'],
            $tierKey,
        );
        if ($identityRequested && $product->identity_model && empty($product->qualityModelConfiguration($mainQuality['key'], ! $this->modelTiers->hasPaidPlan($user))) && empty($product->modelTierConfiguration($tierKey))) {
            $executionProduct = $product->replicate();
            $executionProduct->primary_model = $product->identity_model;
            $executionProduct->ai_provider = $product->identity_model_provider ?: $product->ai_provider;
            $executionProduct->fallback_models = [];
            $executionProduct->fallback_model_providers = [];
        }

        $promotionalCreditsAllowed = false;
        if ($product->pricing_model === 'per_credit' && $totalCreditCost > 0) {
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'برای ساخت این محصول ابتدا وارد حساب کاربری شوید.',
                ], 401);
            }

            // این کنترل باید پس از تعیین مدل هویتی و مدل‌های جایگزین انجام شود؛
            // در غیر این صورت اعتبار هدیه می‌تواند ناخواسته به مدل گران‌تر برسد.
            // پس از فعال‌شدن پلن پرداختی، کل موجودی قابل نمایش کاربر باید برای
            // هر سه کیفیت قابل مصرف باشد؛ وگرنه اعتبار باقی‌مانده‌ای که منشأ
            // هدیه دارد، حرفه‌ای و بهترین را با خطای گمراه‌کننده متوقف می‌کند.
            $promotionalCreditsAllowed = $this->modelTiers->hasPaidPlan($user)
                || $this->creditWallet->productAllowsPromotionalCredits($executionProduct);
        }

        try {
            // هر درخواست ساخت، یک سفارش قابل‌پیگیری در پنل مدیریت ایجاد می‌کند.
            // این ثبت مستقل از خروجی‌های چندگانه است تا کاربر یک سفارش واحد ببیند.
            $order = Order::create([
                'user_id' => $user?->id,
                'product_id' => $product->id,
                'plan_id' => $user?->plan_id,
                'plan_name' => $user?->plan?->name ?: 'رایگان',
                'model_tier_key' => $tierKey,
                'model_tier_name' => $tierMeta['name'],
                'discount_id' => $discount?->id,
                'status' => 'processing',
                'payment_status' => 'paid',
                'processing_status' => 'processing',
                'original_credits' => $originalCreditCost,
                'discount_credits' => $discountCredits,
                'final_credits' => $totalCreditCost,
                'discount_code' => $discount?->code,
                'ai_model' => $executionProduct->primary_model,
                'ai_provider' => $executionProduct->ai_provider,
                'attempts' => 1,
                'input_payload' => [
                    'fields' => $fieldValues,
                    'variants' => array_column($selectedVariants, 'key'),
                    'aspect_ratio' => $aspectRatio,
                    'quality' => $quality,
                    'output_count' => $requestedOutputCount,
                    'main_quality' => $mainQuality['key'],
                    'identity_preservation' => $identityRequested,
                    'face_profile_id' => $faceProfile?->id,
                    'project_name' => trim((string) $request->input('studio_project_name', '')) ?: null,
                ],
                'source' => 'app',
                'paid_at' => now(),
                'processing_started_at' => now(),
            ]);
            $order->recordEvent('created', 'سفارش ثبت شد', 'پردازش سفارش هوش مصنوعی آغاز شد.');

            // رزرو اتمیک اعتبار؛ از ساخت هم‌زمان بیش از موجودی جلوگیری می‌کند و
            // سهم اعتبار هدیه/خریداری‌شده را تا انتهای سفارش نگه می‌دارد.
            if ($product->pricing_model === 'per_credit' && $totalCreditCost > 0) {
                try {
                    $creditReservation = $this->creditWallet->reserve(
                        $user,
                        $totalCreditCost,
                        $promotionalCreditsAllowed,
                        $order,
                    );
                } catch (ValidationException $exception) {
                    foreach ($uploadedPaths as $up) Storage::disk('public')->delete($up['path']);
                    $order->update([
                        'status' => 'review',
                        'payment_status' => 'failed',
                        'processing_status' => 'stopped',
                        'error_message' => 'موجودی اعتبار هنگام رزرو نهایی کافی نبود.',
                    ]);
                    $order->recordEvent('payment_failed', 'رزرو اعتبار ناموفق بود', 'موجودی اعتبار برای مسیر انتخاب‌شده کافی نبود.');
                    return response()->json([
                        'success' => false,
                        'message' => collect($exception->errors())->flatten()->first() ?? 'اعتبارهای شما کافی نیست.',
                    ], 402);
                }
            }

            // پیامک سفارش فقط بعد از تولید موفق خروجی ارسال می‌شود. ارسال پیامک
            // در ابتدای پردازش، در صورت تنظیم اشتباه قالب‌ها، باعث پیامک پلن می‌شد.
            if ($user?->phone) {
                app(SmsEventService::class)->notifyLowCredit($user->fresh());
            }

            $extraPayload = [];
            // اتصال مستقیم مصرف provider به سفارش کاربر؛ گزارش اعتبار سرویس‌ها
            // از همین رابطه نام کاربر، محصول، سفارش و هزینه را نمایش می‌دهد.
            $extraPayload['order_id'] = $order?->id;
            if (!empty($base64Images)) {
                $extraPayload['input_references'] = array_map(fn($b64) => [
                    'type'      => 'image_url',
                    'image_url' => ['url' => $b64],
                ], $base64Images);
            }
            if ($identityRequested) {
                $extraPayload['input_fidelity'] = 'high';
            }

            // هم مقدار کسب‌وکاری رزولوشن و هم نسبت تصویر در لاگ/ردیابی اجرای
            // provider نگه داشته می‌شوند؛ خود adapter آن‌ها را به نام فیلد
            // معتبر همان مدل تبدیل می‌کند.
            $extraPayload['requested_output_resolution'] = $quality;
            $extraPayload['requested_aspect_ratio'] = $aspectRatio;

            // پارامترهای واقعی مؤثر بر کیفیت — فقط در صورت مقداردهی ارسال می‌شوند
            $userNegativePrompt = $valueForType('negative_prompt');
            $studioNegativePrompt = trim((string) $request->input('studio_negative_prompt', ''));
            if (!empty($userNegativePrompt) || !empty($product->negative_prompt)) {
                $extraPayload['negative_prompt'] = trim(implode(', ', array_filter([$product->negative_prompt, $userNegativePrompt, $studioNegativePrompt])));
            } elseif ($studioNegativePrompt !== '') {
                $extraPayload['negative_prompt'] = $studioNegativePrompt;
            }
            $userSeed = $valueForType('seed');
            if ($userSeed !== null || !is_null($product->seed)) {
                $extraPayload['seed'] = (int) ($userSeed ?? $product->seed);
            }
            $strength = $valueForType('strength');
            if ($strength !== null) {
                $extraPayload['strength'] = max(0, min(1, ((float) $strength) / 100));
            }
            if (!empty($product->output_format)) {
                $extraPayload['output_format'] = $product->output_format;
            }
            if (is_array($product->provider_options) && !empty($product->provider_options)) {
                $extraPayload['provider'] = $product->provider_options;
            }

            $outputCount = $selectedVariants === [] ? $requestedOutputCount : 1;

            // ۵.۱ لیست اجراها: بدون واریانت = یک اجرا با پرامپت اصلی (رفتار قبلی دست‌نخورده)؛
            // با واریانت = دقیقاً یک اجرا به‌ازای هر مدل تیک‌خورده، با پرامپت اختصاصی همان مدل.
            $runs = [];
            $finalPromptWithOutput = rtrim($finalPrompt)
                . "\n\nOutput requirements: target output resolution {$quality}; preserve the requested aspect ratio {$aspectRatio}.";
            if (!empty($selectedVariants)) {
                foreach ($selectedVariants as $v) {
                    $variantPrompt = $finalPromptWithOutput;
                    if ($v['prompt'] !== '') {
                        $variantPrompt .= "\n\n" . $v['prompt'];
                    } else {
                        $variantPrompt .= "\n\nOutput style/scene variant: " . $v['title'];
                    }
                    $runs[] = ['prompt' => $variantPrompt, 'key' => $v['key'], 'title' => $v['title'], 'n' => 1];
                }
            } else {
                $runs[] = ['prompt' => $finalPromptWithOutput, 'key' => null, 'title' => null, 'n' => $outputCount];
            }

            $generated = [];   // [{key, title, url, path, size, cost, prompt}]
            $failed    = [];
            $usedModels = [];

            foreach ($runs as $run) {
                try {
                    $attempt = $this->openRouter->generateForProduct(
                        $executionProduct,
                        $run['prompt'],
                        $quality,
                        $aspectRatio,
                        $run['n'],
                        $extraPayload
                    );
                    $result = $attempt['data'];
                    $usedModels[] = $attempt['model'];
                    $providerRequestId = $order
                        ? AiProviderRequest::query()->where('order_id', $order->id)->latest('id')->value('id')
                        : null;

                    // ۶. ذخیره همه تصاویر پاسخ (نه فقط اولین تصویر آرایه data)
                    $items = !empty($result['data']) && is_array($result['data']) ? $result['data'] : [$result];
                    // Replicate مبلغ را در پاسخ prediction برنمی‌گرداند؛ روتر
                    // قیمت snapshot‌شده‌ی رسمی مدل را در estimated_cost_usd
                    // قرار می‌دهد. Fal نیز در صورت آماده‌بودن billing event
                    // actual_cost_usd را برمی‌گرداند.
                    $totalApiCost = (float) (
                        $result['usage']['actual_cost_usd']
                        ?? $result['usage']['estimated_cost_usd']
                        ?? $result['usage']['cost']
                        ?? 0
                    );
                    $perImageApiCost = $totalApiCost / max(1, count($items));
                    foreach ($items as $item) {
                        $singleResult = isset($item['b64_json']) || isset($item['url']) ? ['data' => [$item]] : $item;
                        $imageUrl  = $this->saveGeneratedImage($singleResult);
                        $imagePath = $this->urlToStoragePath($imageUrl);
                        $imageSize = Storage::disk('public')->exists($imagePath)
                            ? Storage::disk('public')->size($imagePath)
                            : 1024 * 1024;

                        $generated[] = [
                            'key'    => $run['key'],
                            'title'  => $run['title'],
                            'url'    => $imageUrl,
                            'path'   => $imagePath,
                            'size'   => $imageSize,
                            'cost'   => $perImageApiCost,
                            'prompt' => $run['prompt'],
                            'provider_request_id' => $providerRequestId,
                        ];
                    }
                } catch (Exception $e) {
                    Log::error('ProductGenerateController Variant Error [' . ($run['title'] ?? 'default') . ']: ' . $e->getMessage());
                    $failed[] = $run['title'] ?? 'خروجی';
                    // اگر تک‌اجرایی بود (رفتار قبلی)، خطا مثل قبل به بیرون پرتاب می‌شود
                    if (count($runs) === 1) {
                        throw $e;
                    }
                }
            }

            if (empty($generated)) {
                throw new Exception('هیچ‌کدام از مدل‌های خروجی انتخاب‌شده با موفقیت ساخته نشد. لطفاً دوباره تلاش کنید.');
            }

            // ۷. ثبت نهایی سوابق در دیتابیس در صورت لاگین بودن کاربر
            if ($user) {
                foreach ($uploadedPaths as $up) {
                    UserUpload::create([
                        'user_id'   => $user->id,
                        'file_path' => $up['path'],
                        'size'      => $up['size'],
                        'mime_type' => $up['mime'],
                    ]);
                }

                foreach ($generated as $g) {
                    GeneratedImage::create([
                        'user_id'     => $user->id,
                        'product_id'  => $product->id,
                        'order_id' => $order?->id,
                        'ai_provider_request_id' => $g['provider_request_id'],
                        'image_path'  => $g['path'],
                        'user_prompt' => $g['prompt'],
                        'cost'        => $g['cost'],
                        'size'        => $g['size'],
                    ]);
                }

            }

            // اگر بعضی واریانت‌ها شکست خوردند، اعتبار همان خروجی‌ها بازگردانده می‌شود.
            $actualOriginalCredit = $product->pricing_model === 'per_credit' ? $creditCost * count($generated) : 0;
            $actualDiscount = $discount?->calculateCredits($actualOriginalCredit) ?? 0;
            $actualCredit = max(0, $actualOriginalCredit - $actualDiscount);
            if ($creditReservation['total'] > 0 && $user) {
                $creditReservation = $this->creditWallet->settle($user, $creditReservation, $actualCredit);
                $creditReservationSettled = true;
            }

            $failedMsg = !empty($failed)
                ? 'ساخت این مدل‌ها ناموفق بود: ' . implode('، ', $failed)
                : null;

            $order?->update([
                'status' => 'completed',
                'processing_status' => 'completed',
                'ai_model' => $usedModels[0] ?? $executionProduct->primary_model,
                'final_credits' => $actualCredit,
                'original_credits' => $actualOriginalCredit,
                'discount_credits' => $actualDiscount,
                'promotional_credits_used' => $creditReservation['promotional'],
                'paid_credits_used' => $creditReservation['paid'],
                'output_payload' => array_map(fn ($g) => ['key' => $g['key'], 'title' => $g['title'], 'path' => $g['path']], $generated),
                'completed_at' => now(),
                'processing_duration_ms' => $order?->processing_started_at ? $order->processing_started_at->diffInMilliseconds(now()) : null,
            ]);
            $order?->recordEvent('completed', 'پردازش با موفقیت تکمیل شد', $failedMsg);
            $this->markTelegramBuildCompleted($request, $product);
            if ($user?->phone && $order) app(SmsEventService::class)->send('order_completed', $user->phone, [
                'name'=>$user->name, 'phone'=>$user->phone, 'order_number'=>$order->order_number,
                'product_name'=>$product->name_fa ?? $product->name ?? '', 'balance'=>(string)($user->fresh()->tokens ?? 0),
            ]);
            if ($discount) $discount->increment('used_count');

            return response()->json([
                'success'          => true,
                'image_url'        => $generated[0]['url'],
                'images'           => array_map(fn ($g) => [
                    'key'   => $g['key'],
                    'title' => $g['title'],
                    'url'   => $g['url'],
                ], $generated),
                'failed_message'   => $failedMsg,
                'used_model'       => $usedModels[0] ?? $executionProduct->primary_model,
                'model_tier'       => $tierMeta,
                'remaining_tokens' => $user ? $user->fresh()->tokens : 0,
            ]);

        } catch (\Throwable $e) {
            if ($creditReservation['total'] > 0 && $user) {
                $this->creditWallet->restore(
                    $user,
                    $creditReservation['promotional'],
                    $creditReservation['paid'],
                    $creditReservationSettled,
                    $creditReservation['ledger_key'] ?? null,
                    $creditReservation['grant_allocations'] ?? [],
                );
            }
            foreach ($uploadedPaths as $up) {
                Storage::disk('public')->delete($up['path']);
            }
            Log::error('ProductGenerateController Error: ' . $e->getMessage());
            if ($order) {
                $order->update([
                    'status' => 'review', 'processing_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processing_duration_ms' => $order->processing_started_at ? $order->processing_started_at->diffInMilliseconds(now()) : null,
                ]);
                $order->recordEvent('failed', 'پردازش ناموفق بود', $e->getMessage());
            }
            $providerFailure = str_contains(strtolower($e->getMessage()), 'provider')
                || str_contains(strtolower($e->getMessage()), 'fal.ai')
                || str_contains(strtolower($e->getMessage()), 'replicate')
                || str_contains(strtolower($e->getMessage()), 'timeout')
                || str_contains(strtolower($e->getMessage()), 'cURL');
            return response()->json([
                'success' => false,
                'message' => $providerFailure
                    ? 'مدل انتخاب‌شده در زمان مقرر پاسخ نداد. اگر مدل جایگزین برای محصول ثبت شده باشد، سیستم آن را هم امتحان کرده است؛ لطفاً چند لحظه بعد دوباره تلاش کنید.'
                    : 'ساخت تصویر انجام نشد. لطفاً دوباره تلاش کنید.',
                'error_code' => $providerFailure ? 'AI_PROVIDER_UNAVAILABLE' : 'IMAGE_GENERATION_FAILED',
            ], $providerFailure ? 503 : 422);
        }
    }

    private function markTelegramBuildCompleted(Request $request, Product $product): void
    {
        $telegramUserId = (int) $request->session()->get('telegram_mini_app_user_id');
        $launchToken = trim((string) $request->session()->get('telegram_mini_app_launch_token'));
        if ($telegramUserId < 1 || $launchToken === '') {
            return;
        }

        $telegramUser = TelegramUser::query()->find($telegramUserId);
        if (! $telegramUser) {
            return;
        }

        $telegramUser->productClicks()
            ->where('launch_token', $launchToken)
            ->where('product_id', $product->id)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);
    }

    private function selectedStudioImageModel(Request $request, Product $product): ?AiModel
    {
        $requestedModelId = trim((string) $request->input('studio_model', ''));
        $modelId = $requestedModelId ?: (string) $product->primary_model;
        if ($modelId === '') return null;

        $primary = AiModel::query()->where('is_active', true)->where('output_modality', 'image')
            ->whereIn('task_type', $this->studioTaskTypes('image'))->where('openrouter_model_id', $modelId)
            ->when($request->filled('studio_provider'), fn ($query) => $query->where('provider', (string) $request->input('studio_provider')), fn ($query) => $query->where('provider', (string) $product->ai_provider))
            ->first();
        if ($requestedModelId !== '' || $this->hasStoredModelPrice($primary)) return $primary;

        return AiModel::query()->where('is_active', true)->where('output_modality', 'image')
            ->whereIn('task_type', $this->studioTaskTypes('image'))
            ->whereIn('provider', ['fal', 'replicate'])
            ->whereNotNull('openrouter_model_id')->where('openrouter_model_id', '<>', '')
            ->orderByRaw("CASE task_type WHEN 'text_to_image' THEN 0 WHEN 'image_to_image' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE provider WHEN 'fal' THEN 0 WHEN 'replicate' THEN 1 ELSE 2 END")
            ->orderByDesc('lab_priority')->first() ?: $primary;
    }

    /**
     * مدیریت هوشمند پاسخ‌های مبتنی بر URL یا Base64 از سمت مدل‌های OpenRouter
     */
    private function selectedFaceProfile(Request $request, $user): ?FaceProfile
    {
        if (! $request->filled('face_profile_id')) {
            return null;
        }

        if (! $user || ! Schema::hasTable('face_profiles')) {
            throw ValidationException::withMessages([
                'face_profile_id' => 'پروفایل چهره انتخاب‌شده در دسترس نیست.',
            ]);
        }

        $profile = $user->faceProfiles()
            ->active()
            ->whereKey($request->integer('face_profile_id'))
            ->first();

        if (! $profile || $profile->referenceImageEntries() === []) {
            throw ValidationException::withMessages([
                'face_profile_id' => 'پروفایل چهره انتخاب‌شده معتبر نیست.',
            ]);
        }

        $hasUsableImage = collect($profile->referenceImageEntries())->contains(function (array $image): bool {
            return str_starts_with((string) ($image['mime'] ?? ''), 'image/')
                && Storage::disk('public')->exists((string) $image['path']);
        });

        if (! $hasUsableImage) {
            throw ValidationException::withMessages([
                'face_profile_id' => 'تصاویر مرجع این پروفایل در دسترس نیستند. یک پروفایل چهره جدید بسازید.',
            ]);
        }

        return $profile;
    }

    protected function saveGeneratedImage(array $responseData): string
    {
        // ۱. تلاش برای پیدا کردن ساختار Base64 مرسوم
        $base64Image = $responseData['data'][0]['b64_json']
            ?? $responseData[0]['b64_json']
            ?? null;

        $filename = 'generated/' . uniqid('gen_') . '.png';

        if ($base64Image) {
            if (str_contains($base64Image, 'base64,')) {
                $base64Image = explode('base64,', $base64Image)[1];
            }
            $binary = base64_decode(trim($base64Image), true);
            if ($binary === false) throw new Exception('خطا در رمزگشایی کدهای مربوط به تصویر Base64.');
            
            Storage::disk('public')->put($filename, $binary);
            return asset('storage/' . $filename);
        }

        // ۲. پشتیبانی از ساختارهای مبتنی بر لینک مستقیم (بسیاری از پلتفرم‌های OpenRouter لینک تصویر برمی‌گردانند)
        $directUrl = $responseData['data'][0]['url'] 
            ?? $responseData[0]['url'] 
            ?? null;
        $downloadHeaders = $responseData['data'][0]['headers']
            ?? $responseData[0]['headers']
            ?? [];

        if ($directUrl) {
            try {
                $download = Http::withHeaders(is_array($downloadHeaders) ? $downloadHeaders : [])
                    ->connectTimeout(15)->timeout(120)->get($directUrl);
                if ($download->successful()) {
                    Storage::disk('public')->put($filename, $download->body());
                    return asset('storage/' . $filename);
                }
            } catch (Exception $e) {
                Log::warning("امکان دانلود مستقیم تصویر فراهم نشد، بازگشت لینک اصلی: " . $e->getMessage());
                return $directUrl; // در صورت عدم امکان دانلود، خود آدرس مستقیم را برگردان
            }
            return $directUrl;
        }

        throw new Exception('هیچ داده تصویری معتبری (بایری یا لینک خروجی) در پاسخ سرور یافت نشد. پاسخ خام: ' . json_encode($responseData));
    }

    protected function urlToStoragePath(string $url): string
    {
        $base = asset('storage/');
        return str_replace($base, '', $url);
    }
}
