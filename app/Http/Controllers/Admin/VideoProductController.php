<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Product;
use App\Services\VideoModelSchemaService;
use App\Services\VideoProductConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VideoProductController extends Controller
{
    public function __construct(
        private readonly VideoModelSchemaService $modelSchemas,
        private readonly VideoProductConfigService $videoConfig,
    ) {}

    public function create(Request $request, ?Product $product = null)
    {
        return $this->renderCreateForm($request, $product, 'admin.products.video-create');
    }

    public function createV2(Request $request, ?Product $product = null)
    {
        return $this->renderCreateForm($request, $product, 'admin.products.video-create-v2');
    }

    private function renderCreateForm(Request $request, ?Product $product, string $view)
    {
        if ($product) abort_unless($product->isVideoProduct(), 404);

        $models = AiModel::query()->selectableForVideoProduct()->get()
            ->sortBy(fn (AiModel $model): array => [$model->provider === 'openrouter' ? 0 : 1, -((int) ($model->lab_priority ?? 0)), (string) $model->name])
            ->values();
        $models->each(fn (AiModel $model) => $model->setAttribute(
            'video_capabilities',
            $this->modelSchemas->summarize($model),
        ));
        $categories = Category::query()->orderBy('name_fa')->orderBy('name')->get();
        $configuration = $product?->videoConfiguration() ?? $this->videoConfig->normalize([
            'workflow' => 'image_to_video',
            'product_family' => 'shop',
            'input_product_image' => true,
            'input_face_image' => false,
            'allow_face_profile' => false,
            'prompt_mode' => 'locked',
            'show_prompt_to_user' => false,
            'face_profile_mode' => 'disabled',
            'video_structure' => 'single_shot',
            'max_shots' => 1,
            'durations' => [4, 6, 8],
            'default_duration' => 4,
            'aspect_ratios' => ['9:16', '16:9', '1:1'],
            'default_aspect_ratio' => '9:16',
            'resolutions' => ['480p', '720p', '1080p', '4K'],
            'default_resolution' => '720p',
            'fps' => 24,
            'motion_presets' => ['static', 'dolly_in', 'orbit', 'pan'],
            'credit_costs_by_duration' => ['4' => 10, '6' => 14, '8' => 18],
            'allow_promotional_credits' => true,
        ]);

        return view($view, [
            'product' => $product,
            'models' => $models,
            'categories' => $categories,
            'configuration' => $configuration,
            'motionCatalog' => $this->videoConfig->motionPresetCatalog(),
            'familyCatalog' => $this->videoConfig->productFamilyCatalog(),
        ]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new Product());
    }

    public function storeV2(Request $request)
    {
        return $this->persist($request, new Product());
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->isVideoProduct(), 404);

        return $this->persist($request, $product);
    }

    public function updateV2(Request $request, Product $product)
    {
        abort_unless($product->isVideoProduct(), 404);

        return $this->persist($request, $product);
    }

    private function persist(Request $request, Product $product)
    {
        $isPublishing = $request->input('status') === 'active';
        $data = $request->validate([
            'name_fa' => [Rule::requiredIf($isPublishing), 'nullable', 'string', 'max:255'],
            'name_en' => [Rule::requiredIf($isPublishing), 'nullable', 'string', 'max:255'],
            'slug' => [Rule::requiredIf($isPublishing), 'nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'description_fa' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'category_ids' => [Rule::requiredIf($isPublishing), 'nullable', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'draft', 'inactive'])],
            'model_id' => [Rule::requiredIf($isPublishing), 'nullable', 'integer', 'exists:ai_models,id'],
            'fallback_model_ids' => ['nullable', 'array', 'max:3'],
            'fallback_model_ids.*' => ['integer', 'distinct', 'exists:ai_models,id'],
            'prompt_template' => [Rule::requiredIf($isPublishing), 'nullable', 'string', 'max:12000'],
            'negative_prompt' => ['nullable', 'string', 'max:5000'],
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'features_json' => ['nullable', 'string', 'max:524288', 'json'],
            'workflow' => ['required', Rule::in(VideoProductConfigService::WORKFLOWS)],
            'product_family' => ['required', Rule::in(VideoProductConfigService::PRODUCT_FAMILIES)],
            'input_product_image' => ['nullable', 'boolean'],
            'input_face_image' => ['nullable', 'boolean'],
            'allow_face_profile' => ['nullable', 'boolean'],
            'prompt_mode' => ['required', Rule::in(['locked', 'custom'])],
            'show_prompt_to_user' => ['nullable', 'boolean'],
            'music_mode' => ['nullable', Rule::in(['disabled', 'optional', 'required'])],
            // فرم قدیمی پشتیبان این فیلد را ندارد؛ برای حفظ سازگاری، مقدار خالی
            // از ساختار قدیمی multi_shot_enabled به‌صورت خودکار استنتاج می‌شود.
            'video_structure' => ['nullable', Rule::in(['single_shot', 'multi_shot'])],
            'multi_shot_enabled' => ['nullable', 'boolean'],
            'max_shots' => ['nullable', 'integer', 'min:1', 'max:10'],
            'timeline_json' => ['nullable', 'string', 'max:524288', 'json'],
            'face_profile_mode' => ['required', Rule::in(['disabled', 'optional', 'required'])],
            'durations' => ['required', 'array', 'min:1', 'max:8'],
            'durations.*' => ['integer', 'min:1', 'max:30', 'distinct'],
            'default_duration' => ['required', 'integer', 'min:1', 'max:30'],
            'aspect_ratios' => ['required', 'array', 'min:1'],
            'aspect_ratios.*' => [Rule::in(VideoProductConfigService::ASPECT_RATIOS)],
            'default_aspect_ratio' => ['required', Rule::in(VideoProductConfigService::ASPECT_RATIOS)],
            'resolutions' => ['required', 'array', 'min:1'],
            'resolutions.*' => [Rule::in(VideoProductConfigService::RESOLUTIONS)],
            'default_resolution' => ['required', Rule::in(VideoProductConfigService::RESOLUTIONS)],
            'quality_tier' => ['nullable', Rule::in(['standard', 'professional', 'best'])],
            'fps' => ['required', 'integer', 'min:4', 'max:60'],
            'motion_presets' => ['nullable', 'array'],
            'motion_presets.*' => [Rule::in(array_keys($this->videoConfig->motionPresetCatalog()))],
            'credit_costs_by_duration' => ['nullable', 'array'],
            'credit_costs_by_duration.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'audio_allowed' => ['nullable', 'boolean'],
            'audio_default' => ['nullable', 'boolean'],
            'prompt_enhance' => ['nullable', 'boolean'],
            'allow_promotional_credits' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            // گام ۱ نسخه‌ی جدید از همان آپلودر چندتصویری ثبت محصول عکس استفاده می‌کند؛
            // اولین تصویر انتخاب‌شده به‌عنوان کاور ویدیو ذخیره می‌شود.
            'main_images' => ['nullable', 'array', 'max:10'],
            'main_images.*' => ['image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'before_images' => ['nullable', 'array', 'max:10'],
            'before_images.*' => ['image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'preview_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
            'preview_video_url' => ['nullable', 'string', 'max:2048'],
            'estimated_time' => ['nullable', 'integer', 'min:10', 'max:3600'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'base_likes_count' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'new_display_order' => ['nullable', 'integer'],
            'new_internal_code' => ['nullable', 'string', 'max:100'],
            'new_admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $model = !empty($data['model_id'])
            ? AiModel::query()->selectableForVideoProduct()->find($data['model_id'])
            : null;
        if ($isPublishing && !$model) {
            throw ValidationException::withMessages(['model_id' => 'مدل ویدیویی انتخاب‌شده فعال یا معتبر نیست.']);
        }
        if ($model && $model->provider !== 'openrouter') {
            throw ValidationException::withMessages(['model_id' => 'مدل اصلی محصولات ویدیویی باید از پرووایدر OpenRouter انتخاب شود.']);
        }
        if ($model && !$this->videoConfig->compatible($model, $data['workflow'])) {
            throw ValidationException::withMessages(['model_id' => 'مدل انتخاب‌شده با سناریوی تولید این محصول سازگار نیست.']);
        }
        if ($data['face_profile_mode'] !== 'disabled' && $data['workflow'] !== 'image_to_video') {
            throw ValidationException::withMessages(['face_profile_mode' => 'پروفایل چهره فقط در سناریوی عکس به ویدیو قابل استفاده است.']);
        }
        $family = (string) $data['product_family'];
        if ($family !== 'music_ready' && $data['workflow'] !== 'image_to_video') {
            throw ValidationException::withMessages(['workflow' => 'محصولات فروشگاهی، چهره‌محور و ترکیبی باید در سناریوی عکس به ویدیو ثبت شوند.']);
        }
        if ($family === 'music_ready' && $data['workflow'] !== 'image_to_video') {
            throw ValidationException::withMessages(['workflow' => 'ویدیوی آماده با موزیک در فاز فعلی به ورودی عکس و سناریوی عکس به ویدیو نیاز دارد.']);
        }
        $hasProductInput = $request->boolean('input_product_image');
        $hasFaceInput = $request->boolean('input_face_image');
        if ($family === 'shop' && !$hasProductInput) {
            throw ValidationException::withMessages(['input_product_image' => 'محصول فروشگاهی باید ورودی عکس محصول داشته باشد.']);
        }
        if ($family === 'face' && !$hasFaceInput) {
            throw ValidationException::withMessages(['input_face_image' => 'محصول چهره‌محور باید ورودی عکس چهره داشته باشد.']);
        }
        if ($family === 'hybrid' && (!$hasProductInput || !$hasFaceInput)) {
            throw ValidationException::withMessages(['product_family' => 'محصول ترکیبی باید هر دو ورودی عکس محصول و عکس چهره را داشته باشد.']);
        }
        if ($isPublishing && !$request->hasFile('cover_image') && !$request->hasFile('main_images.0') && !$product->cover) {
            throw ValidationException::withMessages(['main_images' => 'برای انتشار محصول، تصویر اصلی/کاور الزامی است.']);
        }

        $fallbacks = AiModel::query()
            ->selectableForVideoProduct()
            ->whereIn('id', (array) ($data['fallback_model_ids'] ?? []))
            ->get()
            ->filter(fn (AiModel $candidate): bool => $this->videoConfig->compatible($candidate, $data['workflow']))
            ->values();
        if (count((array) ($data['fallback_model_ids'] ?? [])) !== $fallbacks->count()) {
            throw ValidationException::withMessages(['fallback_model_ids' => 'یکی از مدل‌های جایگزین با سناریوی محصول سازگار نیست.']);
        }

        $features = json_decode((string) ($data['features_json'] ?? '[]'), true);
        $features = $this->normalizeFeatures(is_array($features) ? $features : []);
        $timeline = json_decode((string) ($data['timeline_json'] ?? '[]'), true);
        $configuration = $this->videoConfig->normalize($data + ['timeline' => is_array($timeline) ? $timeline : []]);
        if ($configuration['product_family'] === 'music_ready' && $configuration['timeline'] === []) {
            throw ValidationException::withMessages(['timeline_json' => 'برای محصول ویدیویی آماده با موزیک حداقل یک پلان تعریف کنید.']);
        }
        $categoryIds = array_values(array_unique(array_map('intval', (array) ($data['category_ids'] ?? []))));
        $category = $categoryIds ? Category::find($categoryIds[0]) : null;
        $existingProviderOptions = (array) ($product->provider_options ?? []);

        $slug = Str::slug((string) (($data['slug'] ?? '') ?: ($data['name_en'] ?? '') ?: ($data['name_fa'] ?? '')));
        if ($slug === '') $slug = 'video-product-' . Str::lower(Str::random(8));

        $product->forceFill([
            'name_fa' => trim((string) ($data['name_fa'] ?? 'محصول ویدیویی')),
            'name_en' => trim((string) ($data['name_en'] ?? 'Video Product')),
            'slug' => $slug,
            'product_code' => $product->product_code ?: Product::generateUniqueProductCode(),
            'description_fa' => $data['description_fa'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'category_id' => $category?->id,
            'category' => $category?->name_fa ?: $category?->name ?: 'ویدیوهای آماده هوش مصنوعی',
            'status' => $data['status'],
            'tags' => collect(preg_split('/[,،\n]+/u', (string) ($data['tags'] ?? '')))->map(fn ($tag) => trim($tag))->filter()->unique()->values()->all(),
            'media_type' => 'video',
            'primary_model' => $model?->openrouter_model_id ?: $product->primary_model ?: 'unconfigured/video-model',
            'ai_provider' => $model?->provider ?: $product->ai_provider ?: 'fal',
            'fallback_models' => $fallbacks->pluck('openrouter_model_id')->values()->all(),
            'fallback_model_providers' => $fallbacks->pluck('provider')->values()->all(),
            'pipeline_type' => 'video_generation',
            'prompt_template' => $data['prompt_template'] ?: 'Create a cinematic video: {prompt}',
            'negative_prompt' => $data['negative_prompt'] ?? null,
            'system_prompt' => $data['system_prompt'] ?? null,
            'input_schema' => $features,
            'provider_options' => array_replace($existingProviderOptions, ['video' => $configuration]),
            'subject_type' => $data['face_profile_mode'] !== 'disabled' ? 'face' : 'generic',
            'identity_preservation' => $data['face_profile_mode'] !== 'disabled',
            'min_reference_images' => $data['face_profile_mode'] === 'required' ? 1 : 0,
            'max_reference_images' => 3,
            'output_type' => 'video',
            'output_format' => 'mp4',
            'output_count' => 1,
            'resolution' => rtrim($configuration['default_resolution'], 'p'),
            'allowed_resolutions' => array_map(fn (string $value): string => rtrim($value, 'p'), $configuration['resolutions']),
            'aspect_ratio' => $configuration['default_aspect_ratio'],
            'allowed_aspect_ratios' => array_values(array_intersect(Product::supportedAspectRatios(), $configuration['aspect_ratios'])),
            'delivery_method' => 'queued',
            'estimated_time' => (int) ($data['estimated_time'] ?? 180),
            'pricing_model' => 'per_credit',
            'credit_cost' => (int) ($configuration['credit_costs_by_duration'][(string) $configuration['default_duration']] ?? 0),
            'is_featured' => $request->boolean('is_featured'),
            'is_new' => $request->boolean('is_new'),
            'is_trending' => $request->boolean('is_trending'),
            'base_likes_count' => $request->filled('base_likes_count') ? max(0, (int) $data['base_likes_count']) : ($product->base_likes_count ?? 0),
            'new_display_order' => (int) ($data['new_display_order'] ?? $product->new_display_order ?? 1),
            'new_internal_code' => $data['new_internal_code'] ?? $product->new_internal_code,
            'new_admin_note' => $data['new_admin_note'] ?? $product->new_admin_note,
            'watermark_enabled' => false,
            'timeout' => max(180, (int) ($data['estimated_time'] ?? 180) + 120),
        ]);

        if ($request->hasFile('cover_image')) {
            $product->cover = $request->file('cover_image')->store('products/videos/covers', 'public');
            $product->thumbnail = $product->cover;
        } elseif ($request->hasFile('main_images.0')) {
            $product->cover = $request->file('main_images.0')->store('products/videos/covers', 'public');
            $product->thumbnail = $product->cover;
        } elseif (!$product->thumbnail) {
            $product->thumbnail = $product->cover ?: 'products/thumbnails/default_placeholder.jpg';
        }

        if ($request->hasFile('preview_video')) {
            $product->preview_video_url = $request->file('preview_video')->store('products/videos/previews', 'public');
        } elseif (array_key_exists('preview_video_url', $data)) {
            $product->preview_video_url = trim((string) ($data['preview_video_url'] ?? '')) ?: null;
        }
        if ($request->hasFile('before_images')) {
            $product->before_images = collect($request->file('before_images'))
                ->map(fn ($file) => $file->store('products/videos/before', 'public'))
                ->values()->all();
        }
        $product->sample_outputs = array_values(array_filter([$product->preview_video_url]));

        DB::transaction(function () use ($product, $categoryIds): void {
            $product->save();
            if ($categoryIds !== []) $product->categories()->sync($categoryIds);
        });

        $message = $data['status'] === 'active' ? 'محصول ویدیویی با موفقیت منتشر شد.' : 'پیش‌نویس محصول ویدیویی ذخیره شد.';
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'product_id' => $product->id, 'redirect' => route('admin.products.video.v2.create', $product)]);
        }

        return redirect()->route('admin.products.video.v2.create', $product)->with('success', $message);
    }

    private function normalizeFeatures(array $features): array
    {
        $allowedTypes = ['text', 'textarea', 'prompt', 'negative_prompt', 'number', 'select', 'radio', 'multi_select', 'button_group', 'slider', 'switch', 'checkbox', 'image_upload', 'file_upload'];

        return collect($features)
            ->filter(fn ($feature): bool => is_array($feature))
            ->take(30)
            ->map(function (array $feature, int $index) use ($allowedTypes): array {
                $id = Str::snake((string) ($feature['field_id'] ?? $feature['label_fa'] ?? 'feature_' . $index));
                if (!preg_match('/^[a-z][a-z0-9_]{1,79}$/', $id)) $id = 'feature_' . ($index + 1);
                $type = in_array($feature['type'] ?? null, $allowedTypes, true) ? $feature['type'] : 'text';
                $options = collect((array) ($feature['options'] ?? []))->take(30)->map(function ($option): array {
                    $option = is_array($option) ? $option : ['label' => (string) $option, 'value' => (string) $option];
                    return [
                        'label' => trim((string) ($option['label'] ?? $option['value'] ?? '')),
                        'value' => trim((string) ($option['value'] ?? $option['label'] ?? '')),
                        'prompt' => trim((string) ($option['prompt'] ?? '')),
                        'credit' => max(0, (int) ($option['credit'] ?? 0)),
                    ];
                })->filter(fn (array $option): bool => $option['label'] !== '' && $option['value'] !== '')->values()->all();

                return [
                    'field_id' => $id,
                    'type' => $type,
                    'label_fa' => trim((string) ($feature['label_fa'] ?? 'ویژگی')),
                    'description' => trim((string) ($feature['description'] ?? '')),
                    'placeholder' => trim((string) ($feature['placeholder'] ?? '')),
                    'required' => !empty($feature['required']) ? '1' : '0',
                    'hidden' => '0',
                    'default' => $feature['default'] ?? '',
                    'min' => $feature['min'] ?? '',
                    'max' => $feature['max'] ?? '',
                    'step' => $feature['step'] ?? '',
                    'unit' => trim((string) ($feature['unit'] ?? '')),
                    'accept' => trim((string) ($feature['accept'] ?? ($type === 'image_upload' ? 'image/*' : ''))),
                    'max_size_mb' => max(1, min(100, (int) ($feature['max_size_mb'] ?? 10))),
                    'credit_cost' => max(0, (int) ($feature['credit_cost'] ?? 0)),
                    'prompt_mode' => in_array($feature['prompt_mode'] ?? null, ['token', 'append', 'off'], true) ? $feature['prompt_mode'] : 'append',
                    'prompt_wrap' => trim((string) ($feature['prompt_wrap'] ?? '')),
                    'options' => $options,
                    'order' => $index,
                ];
            })
            ->filter(fn (array $feature): bool => $feature['label_fa'] !== '')
            ->values()->all();
    }
}
