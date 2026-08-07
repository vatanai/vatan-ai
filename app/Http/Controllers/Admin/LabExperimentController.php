<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunLabModelJob;
use App\Models\AiModel;
use App\Models\LabAuditLog;
use App\Models\LabExperiment;
use App\Models\LabManagerScore;
use App\Models\LabRun;
use App\Models\LabRunOutput;
use App\Models\Product;
use App\Services\ExchangeRateService;
use App\Services\ProductPromptBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LabExperimentController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRate,
        private readonly ProductPromptBuilder $promptBuilder,
    ) {}

    public function index(Request $request)
    {
        $query = LabExperiment::with(['product', 'runs.outputs.managerScore'])->withCount(['runs', 'images'])->latest();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('product', fn ($q) => $q->where('name_fa', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"));
        }
        if ($request->filled('product_id')) $query->where('product_id', $request->integer('product_id'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));

        return view('admin.lab.index', [
            'experiments' => $query->paginate(20)->withQueryString(),
            'products' => Product::orderBy('name_fa')->get(['id', 'name_fa', 'name_en']),
            'exchange' => $this->exchangeRate->usdToIrr(),
            'totalProductsTested' => (int) LabExperiment::whereIn('status', ['completed', 'partially_failed', 'evaluated', 'finalized'])->distinct()->count('product_id'),
            'totalExperiments' => (int) LabExperiment::count(),
            'completedExperiments' => (int) LabExperiment::whereIn('status', ['completed', 'evaluated', 'finalized'])->count(),
            'failedExperiments' => (int) LabExperiment::whereIn('status', ['failed', 'partially_failed'])->count(),
            'totalOutputs' => (int) DB::table('lab_run_outputs')->where('status', 'completed')->count(),
            'totalCostUsd' => (float) LabExperiment::sum(DB::raw('COALESCE(total_cost_usd, actual_cost_usd, estimated_cost_usd)')),
            'totalCostToman' => (float) LabExperiment::sum(DB::raw('COALESCE(total_cost_toman, actual_cost_toman, estimated_cost_toman)')),
        ]);
    }

    public function create(Request $request)
    {
        $products = Product::with('categories')->latest()->get();
        $models = AiModel::where('is_active', true)->where('output_modality', 'image')->orderBy('name')->get();
        $scoringModels = AiModel::where('provider', 'openrouter')->where('is_active', true)->where('output_modality', 'text')->orderBy('name')->get();
        $duplicateExperiment = $request->filled('duplicate_id')
            ? LabExperiment::with('runs')->find($request->integer('duplicate_id'))
            : null;
        $selectedProductId = $request->integer('product_id') ?: $duplicateExperiment?->product_id;
        $duplicateModelIds = $duplicateExperiment?->runs->pluck('ai_model_id')->filter()->unique()->values()->all() ?: [];
        $productOptions = $products->mapWithKeys(fn (Product $product) => [$product->id => [
            'name' => $product->name_fa,
            'code' => $product->product_code,
            'model' => $product->primary_model,
            'prompt' => $this->promptBuilder->build($product, []),
            'negative_prompt' => $product->negative_prompt,
            'aspect_ratio' => $product->aspect_ratio ?: '1:1',
            'aspect_ratios' => $product->allowedAspectRatioList(),
            'category_ids' => $this->productCategoryIds($product),
            'category_names' => $product->categories->pluck('name_fa')->filter()->values()->all(),
            'grade_config' => $product->lab_grade_config ?: [],
            'images' => $this->imageCatalog($product),
        ]])->all();

        return view('admin.lab.create', [
            'products' => $products,
            'models' => $models,
            'scoringModels' => $scoringModels,
            'productOptions' => $productOptions,
            'selectedProductId' => $selectedProductId,
            'duplicateModelIds' => $duplicateModelIds,
            'duplicateExperiment' => $duplicateExperiment,
            'exchange' => $this->exchangeRate->usdToIrr(),
        ]);
    }

    public function productImages(Product $product)
    {
        return response()->json(['product' => ['id' => $product->id, 'name' => $product->name_fa], 'images' => $this->imageCatalog($product)]);
    }

    public function productSummary(Product $product)
    {
        $experiment = $product->labExperiments()
            ->whereIn('status', ['completed', 'partially_failed', 'evaluated', 'finalized'])
            ->with(['images', 'runs.aiModel', 'runs.outputs.managerScore', 'runs.outputs.scores'])
            ->latest('completed_at')
            ->latest('id')
            ->first();

        if (!$experiment) {
            return response()->json(['message' => 'برای این محصول هنوز آزمایش ذخیره‌شده‌ای وجود ندارد.'], 404);
        }

        return response()->json($this->experimentPayload($experiment));
    }

    public function quickRun(Request $request, Product $product)
    {
        $payloadModels = json_decode((string) $request->input('models'), true);
        $payloadModels = is_array($payloadModels) ? array_values($payloadModels) : [];
        if (empty($payloadModels)) {
            return response()->json(['message' => 'حداقل یک مدل برای آزمایش انتخاب کنید.'], 422);
        }

        $modelIds = collect($payloadModels)->pluck('id')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($modelIds->isEmpty() || $modelIds->count() > 8) {
            return response()->json(['message' => 'تعداد مدل‌های انتخابی معتبر نیست.'], 422);
        }

        $models = AiModel::whereIn('id', $modelIds)->where('is_active', true)->where('output_modality', 'image')->get()->keyBy('id');
        if ($models->count() !== $modelIds->count()) {
            return response()->json(['message' => 'یکی از مدل‌های انتخاب‌شده فعال یا تصویری نیست.'], 422);
        }

        $inputImage = $this->resolveQuickRunInputImage($request, $product);
        if (!$inputImage) {
            return response()->json(['message' => 'برای اجرای آزمایش، یک عکس ورودی لازم است.'], 422);
        }

        $rate = $this->exchangeRate->usdToIrr();
        $rateIrr = (float) ($rate['rate'] ?? 0);
        $rateToman = $rateIrr / 10;
        $estimatedUsd = collect($payloadModels)->sum(fn ($item) => (float) ($models->get((int) ($item['id'] ?? 0))?->cost_per_generation_usd ?? 0));
        $basePrompt = trim((string) $this->promptBuilder->build($product, [], false));
        if ($basePrompt === '') $basePrompt = trim((string) $product->prompt_template) ?: 'Create a high quality image.';
        $negativePrompt = trim((string) ($product->negative_prompt ?? '')) ?: null;

        $experiment = DB::transaction(function () use ($request, $product, $payloadModels, $models, $inputImage, $rateIrr, $rateToman, $estimatedUsd, $basePrompt, $negativePrompt) {
            $experiment = LabExperiment::create([
                'product_id' => $product->id,
                'admin_id' => $request->user('admin')?->id,
                'title' => 'آزمایش ' . $product->name_fa . ($product->product_code ? ' (' . $product->product_code . ')' : ''),
                'status' => 'queued',
                'prompt_snapshot' => $basePrompt,
                'negative_prompt' => $negativePrompt,
                'settings' => ['count' => 1, 'source' => 'product_step_lab', 'scoring_model' => '', 'evaluator_enabled' => false],
                'estimated_cost_usd' => $estimatedUsd,
                'estimated_cost_irr' => $estimatedUsd * $rateIrr,
                'estimated_cost_toman' => $estimatedUsd * $rateToman,
                'total_cost_usd' => $estimatedUsd,
                'total_cost_toman' => $estimatedUsd * $rateToman,
                'exchange_rate_irr' => $rateIrr,
                'exchange_rate_usd' => $rateToman,
                'models_count' => count($payloadModels),
                'report_code' => 'LAB-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'product_name_snapshot' => $product->name_fa,
                'product_code_snapshot' => $product->product_code,
                'input_image_path' => $inputImage['path'],
                'input_image_original_name' => $inputImage['name'] ?? null,
                'input_image_width' => $inputImage['width'],
                'input_image_height' => $inputImage['height'],
                'input_image_ratio' => $inputImage['ratio'],
                'input_image_size' => $inputImage['size'],
                'input_image_format' => $inputImage['format'],
                'input_image_color' => $inputImage['color'],
            ]);

            $experiment->images()->create([
                'image_path' => $inputImage['path'],
                'role' => 'reference',
                'source' => $inputImage['source'],
                'width' => $inputImage['width'],
                'height' => $inputImage['height'],
                'size' => $inputImage['size'],
                'mime_type' => $inputImage['mime_type'],
                'metadata' => ['ratio' => $inputImage['ratio'], 'format' => $inputImage['format'], 'color' => $inputImage['color']],
            ]);

            foreach ($payloadModels as $index => $item) {
                $model = $models->get((int) ($item['id'] ?? 0));
                if (!$model) continue;
                $quality = in_array((string) ($item['quality'] ?? ''), ['480', '720', '1080', '1440', '2160'], true) ? (string) $item['quality'] : '720';
                $size = in_array((string) ($item['size'] ?? ''), ['4:5', '9:16', '3:4', '1:1', '2:3', '16:9', '3:2'], true) ? (string) $item['size'] : '4:5';
                $preserveFace = filter_var($item['preserve_face'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                $preserveFace = $preserveFace ?? true;
                $prompt = trim((string) $this->promptBuilder->build($product, [], $preserveFace));
                if ($prompt === '') $prompt = $basePrompt;
                $cost = (float) $model->cost_per_generation_usd;
                $experiment->runs()->create([
                    'ai_model_id' => $model->id,
                    'model_id' => $model->openrouter_model_id,
                    'provider' => $model->provider,
                    'alias' => $model->name,
                    'model_name_snapshot' => $model->name,
                    'provider_name_snapshot' => $model->provider,
                    'grade_key' => 'model_' . ($index + 1),
                    'grade_label' => 'آزمایش مدل ' . ($index + 1),
                    'role' => 'primary',
                    'attempt_order' => $index + 1,
                    'status' => 'queued',
                    'prompt_snapshot' => $prompt,
                    'model_snapshot' => $model->toArray(),
                    'parameters' => ['count' => 1, 'resolution' => $quality, 'aspect_ratio' => $size, 'grade_resolution' => $quality, 'grade_aspect_ratio' => $size, 'preserve_face' => $preserveFace, 'scoring_model' => '', 'evaluator_enabled' => false],
                    'estimated_cost_usd' => $cost,
                    'estimated_cost_toman' => $cost * $rateToman,
                    'quality' => $quality,
                    'size' => $size,
                    'preserve_face' => $preserveFace,
                    'exchange_rate_irr' => $rateIrr,
                ]);
            }

            $this->audit($experiment, $request, 'quick_created', ['models_count' => count($payloadModels), 'input_source' => $inputImage['source']]);
            return $experiment;
        });

        foreach ($experiment->runs as $run) RunLabModelJob::dispatch($run->id);

        return response()->json([
            'ok' => true,
            'experiment' => $this->experimentPayload($experiment->fresh(['product', 'images', 'runs.aiModel', 'runs.outputs.managerScore', 'runs.outputs.scores'])),
            'status_url' => route('admin.lab.status', $experiment),
            'summary_url' => route('admin.lab.products.summary', $product),
        ], 201);
    }

    public function store(Request $request)
    {
        if ($request->filled('ai_lab_payload')) {
            $payload = json_decode((string) $request->input('ai_lab_payload'), true) ?: [];
            $product = Product::find($request->integer('product_id'));
            $catalog = $product ? $this->imageCatalog($product) : [];
            $inputPath = data_get($payload, 'input.path');
            if (!$inputPath || !collect($catalog)->pluck('path')->contains($inputPath)) $inputPath = data_get($catalog, '0.path');
            $request->merge([
                'images' => $inputPath ? [$inputPath] : [],
                'models' => collect(data_get($payload, 'models', []))->pluck('id')->filter()->values()->all(),
                'count' => 1, 'resolution' => data_get($payload, 'models.0.quality', '720'), 'aspect_ratio' => data_get($payload, 'models.0.size', '4:5'),
                'scoring_model' => data_get($payload, 'evaluator', 'openai/gpt-4o-mini'),
            ]);
        }
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'string', 'max:500'],
            'models' => ['nullable', 'array', 'max:8'],
            'models.*' => ['integer', 'distinct', 'exists:ai_models,id'],
            'grades' => ['nullable', 'array'],
            'grades.*.primary_model' => ['nullable', 'integer', 'exists:ai_models,id'],
            'grades.*.fallback_models' => ['nullable', 'array', 'max:7'],
            'grades.*.fallback_models.*' => ['integer', 'distinct', 'exists:ai_models,id'],
            'grades.*.resolution' => ['nullable', 'string', 'max:10'],
            'grades.*.aspect_ratio' => ['nullable', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:180'],
            'prompt_override' => ['nullable', 'string', 'max:12000'],
            'negative_prompt' => ['nullable', 'string', 'max:4000'],
            'count' => ['required', 'integer', 'min:1', 'max:4'],
            'resolution' => ['required', 'string', 'max:10'],
            'aspect_ratio' => ['required', 'string', 'max:10'],
            'seed' => ['nullable', 'integer'],
            'scoring_model' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $catalog = collect($this->imageCatalog($product))->keyBy('path');
        $selectedImages = collect($data['images'])->map(fn ($path) => $catalog->get($path))->filter();
        if ($selectedImages->count() !== count($data['images'])) {
            return back()->withInput()->withErrors(['images' => 'یکی از تصاویر انتخاب‌شده متعلق به این محصول نیست.']);
        }

        $globalModelIds = collect($data['models'] ?? [])->map(fn ($id) => (int) $id);
        $gradeInputs = (array) ($data['grades'] ?? []);
        $gradeDefinitions = $this->gradeDefinitions($data, $gradeInputs, $globalModelIds);
        $effectiveModelIds = collect($gradeDefinitions)->flatMap(fn ($grade) => $grade['model_ids'])->merge($globalModelIds)->unique()->values();
        if ($effectiveModelIds->isEmpty()) {
            return back()->withInput()->withErrors(['models' => 'حداقل یک مدل برای آزمایش انتخاب کنید.']);
        }

        $models = AiModel::whereIn('id', $effectiveModelIds)->where('is_active', true)->where('output_modality', 'image')->get()->keyBy('id');
        if ($models->count() !== $effectiveModelIds->count()) {
            return back()->withInput()->withErrors(['models' => 'یکی از مدل‌های انتخاب‌شده فعال یا تصویری نیست.']);
        }

        $rate = $this->exchangeRate->usdToIrr();
        $prompt = trim((string) ($data['prompt_override'] ?: $this->promptBuilder->build($product, [])));
        if ($prompt === '') $prompt = trim((string) $product->prompt_template);
        $negativePrompt = trim((string) ($data['negative_prompt'] ?? $product->negative_prompt ?? '')) ?: null;
        $count = (int) $data['count'];
        $estimatedUsd = collect($gradeDefinitions)->flatMap(fn ($grade) => $grade['model_ids'])
            ->map(fn ($modelId) => $models->get($modelId))
            ->filter()
            ->sum(fn (AiModel $model) => (float) $model->cost_per_generation_usd * $count);

        $experiment = DB::transaction(function () use ($request, $data, $product, $selectedImages, $models, $rate, $prompt, $negativePrompt, $count, $estimatedUsd, $gradeDefinitions, $effectiveModelIds) {
            $baseSettings = [
                'count' => $count,
                'resolution' => $data['resolution'],
                'aspect_ratio' => $data['aspect_ratio'],
                'seed' => $data['seed'] ?? null,
                'scoring_model' => $data['scoring_model'] ?: 'openai/gpt-4o-mini',
                'prompt_version' => $product->promptHistories()->latest('version_number')->value('version_number'),
                'product_categories' => $this->productCategoryIds($product),
                'product_category_names' => $product->categories->pluck('name_fa')->filter()->values()->all(),
                'grades' => $gradeDefinitions,
            ];
            $experiment = LabExperiment::create([
                'product_id' => $product->id,
                'admin_id' => $request->user('admin')?->id,
                'title' => $data['title'] ?: 'آزمایش ' . $product->name_fa . ($product->product_code ? ' (' . $product->product_code . ')' : ''),
                'status' => 'queued',
                'prompt_snapshot' => $prompt,
                'negative_prompt' => $negativePrompt,
                'settings' => $baseSettings,
                'estimated_cost_usd' => $estimatedUsd,
                'estimated_cost_irr' => $estimatedUsd * (float) ($rate['rate'] ?? 0),
                'estimated_cost_toman' => $estimatedUsd * (float) ($rate['rate'] ?? 0) / 10,
                'total_cost_usd' => $estimatedUsd,
                'total_cost_toman' => $estimatedUsd * (float) ($rate['rate'] ?? 0) / 10,
                'exchange_rate_irr' => (float) ($rate['rate'] ?? 0),
                'exchange_rate_usd' => (float) ($rate['rate'] ?? 0) / 10,
                'models_count' => $effectiveModelIds->count(),
                'report_code' => 'LAB-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'product_name_snapshot' => $product->name_fa,
                'product_code_snapshot' => $product->product_code,
                'evaluator_provider' => 'openrouter',
            ]);

            foreach ($selectedImages as $image) {
                $experiment->images()->create([
                    'image_path' => $image['path'], 'role' => 'reference', 'source' => 'product',
                    'width' => $image['width'], 'height' => $image['height'], 'size' => $image['size'], 'mime_type' => $image['mime_type'],
                ]);
            }
            $firstImage = $selectedImages->first();
            if ($firstImage) $experiment->update(['input_image_path' => $firstImage['path'], 'input_image_width' => $firstImage['width'], 'input_image_height' => $firstImage['height'], 'input_image_size' => $firstImage['size'], 'input_image_format' => strtoupper(pathinfo($firstImage['path'], PATHINFO_EXTENSION)), 'input_image_ratio' => ($firstImage['width'] && $firstImage['height']) ? round($firstImage['width'] / $firstImage['height'], 3) : null]);

            foreach ($gradeDefinitions as $gradeKey => $grade) {
                foreach ($grade['model_ids'] as $attemptOrder => $modelId) {
                    $model = $models->get($modelId);
                    $run = $experiment->runs()->create([
                        'ai_model_id' => $model->id,
                        'model_id' => $model->openrouter_model_id,
                        'provider' => $model->provider,
                        'alias' => $model->name,
                        'model_name_snapshot' => $model->name,
                        'provider_name_snapshot' => $model->provider,
                        'grade_key' => $gradeKey,
                        'grade_label' => $grade['label'],
                        'role' => $attemptOrder === 0 ? 'primary' : 'fallback',
                        'attempt_order' => $attemptOrder + 1,
                        'status' => 'queued',
                        'prompt_snapshot' => $prompt,
                        'model_snapshot' => $model->toArray(),
                        'parameters' => array_merge($baseSettings, [
                            'grade_key' => $gradeKey,
                            'grade_label' => $grade['label'],
                            'grade_resolution' => $grade['resolution'],
                            'grade_aspect_ratio' => $grade['aspect_ratio'],
                        ]),
                        'estimated_cost_usd' => (float) $model->cost_per_generation_usd * $count,
                        'estimated_cost_toman' => (float) $model->cost_per_generation_usd * $count * (float) ($rate['rate'] ?? 0) / 10,
                        'quality' => $grade['resolution'], 'size' => $grade['aspect_ratio'], 'preserve_face' => true,
                        'exchange_rate_irr' => $experiment->exchange_rate_irr,
                    ]);
                    RunLabModelJob::dispatch($run->id);
                }
            }

            $this->audit($experiment, $request, 'created', ['grades' => $gradeDefinitions, 'images_count' => $selectedImages->count()]);
            return $experiment;
        });

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'id' => $experiment->id,
                'status' => $experiment->status,
                'status_url' => route('admin.lab.status', $experiment),
                'redirect_url' => route('admin.lab.show', $experiment),
            ], 201);
        }

        return redirect()->route('admin.lab.show', $experiment)->with('success', 'آزمایش ساخته شد و مدل‌ها در صف اجرا قرار گرفتند.');
    }

    public function status(LabExperiment $experiment)
    {
        $experiment->load(['product', 'images', 'runs.aiModel', 'runs.outputs.scores', 'runs.outputs.managerScore']);
        if ($experiment->status === 'completed') {
            $this->recalculateRanking($experiment);
            $experiment->load(['product', 'images', 'runs.aiModel', 'runs.outputs.scores', 'runs.outputs.managerScore']);
        }

        return response()->json($this->experimentPayload($experiment) + [
            'id' => $experiment->id,
            'status' => $experiment->status,
            'runs' => $experiment->runs->map(fn (LabRun $run) => [
                'id' => $run->id,
                'alias' => $run->alias,
                'model_id' => $run->model_id,
                'provider' => $run->provider,
                'status' => $run->status,
                'status_label' => $run->status_label,
                'duration_ms' => $run->duration_ms,
                'actual_cost_usd' => (float) $run->actual_cost_usd,
                'estimated_cost_usd' => (float) $run->estimated_cost_usd,
                'retry_count' => (int) $run->retry_count,
                'max_retries' => (int) $run->max_retries,
                'error_message' => $run->error_message,
                'grade_key' => $run->grade_key,
                'grade_label' => $run->grade_label,
                'role' => $run->role,
                'attempt_order' => $run->attempt_order,
                'final_score' => $run->final_score,
                'rank' => $run->rank,
                'is_selected' => (bool) $run->is_selected,
                'outputs' => $run->outputs->map(fn ($output) => [
                    'id' => $output->id,
                    'url' => $output->url,
                    'manual_score' => $output->manual_score,
                    'note' => $output->note,
                    'is_winner' => (bool) $output->is_winner,
                    'scores' => $output->scores->where('evaluator_type', 'ai')->map(fn ($score) => [
                        'criterion' => $score->criterion,
                        'score' => $score->score,
                    ])->values(),
                    'ai_evaluation' => data_get($output->metadata, 'ai_evaluation'),
                ])->values(),
            ])->values(),
        ]);
    }

    public function show(LabExperiment $experiment)
    {
        $experiment->load(['product', 'images', 'runs.aiModel', 'runs.outputs.scores', 'runs.outputs.managerScore', 'admin', 'auditLogs.admin']);
        $this->recalculateRanking($experiment);
        $experiment->load(['runs.aiModel', 'runs.outputs.scores', 'runs.outputs.managerScore']);
        return view('admin.lab.show', ['experiment' => $experiment, 'exchange' => $this->exchangeRate->usdToIrr()]);
    }

    public function duplicate(Request $request, LabExperiment $experiment)
    {
        $experiment->load(['images', 'runs.aiModel']);
        $sourceRuns = $experiment->runs->groupBy('grade_key');
        $rate = $this->exchangeRate->usdToIrr();
        $new = DB::transaction(function () use ($request, $experiment, $sourceRuns, $rate) {
            $settings = (array) $experiment->settings;
            $new = LabExperiment::create([
                'product_id' => $experiment->product_id,
                'parent_experiment_id' => $experiment->id,
                'admin_id' => $request->user('admin')?->id,
                'title' => 'کپی ' . ($experiment->title ?: 'آزمایش محصول'),
                'status' => 'queued',
                'prompt_snapshot' => $experiment->prompt_snapshot,
                'negative_prompt' => $experiment->negative_prompt,
                'settings' => $settings,
                'exchange_rate_irr' => (float) ($rate['rate'] ?? $experiment->exchange_rate_irr),
            ]);
            foreach ($experiment->images as $image) $new->images()->create($image->only(['image_path', 'role', 'source', 'width', 'height', 'size', 'mime_type', 'metadata']));

            $estimatedUsd = 0;
            foreach ($sourceRuns as $gradeKey => $runs) {
                foreach ($runs as $sourceRun) {
                    $model = $sourceRun->aiModel;
                    if (!$model || !$model->is_active || $model->output_modality !== 'image') continue;
                    $cost = (float) $model->cost_per_generation_usd * (int) data_get($settings, 'count', 1);
                    $estimatedUsd += $cost;
                    $new->runs()->create([
                        'ai_model_id' => $model->id,
                        'model_id' => $sourceRun->model_id,
                        'provider' => $sourceRun->provider,
                        'alias' => $sourceRun->alias,
                        'grade_key' => $sourceRun->grade_key,
                        'grade_label' => $sourceRun->grade_label,
                        'role' => $sourceRun->role,
                        'attempt_order' => $sourceRun->attempt_order,
                        'status' => 'queued',
                        'prompt_snapshot' => $experiment->prompt_snapshot,
                        'model_snapshot' => $sourceRun->model_snapshot,
                        'parameters' => $sourceRun->parameters,
                        'estimated_cost_usd' => $cost,
                        'exchange_rate_irr' => (float) ($rate['rate'] ?? $experiment->exchange_rate_irr),
                    ]);
            }
            }
            $new->update(['estimated_cost_usd' => $estimatedUsd, 'estimated_cost_irr' => $estimatedUsd * (float) ($rate['rate'] ?? $experiment->exchange_rate_irr)]);
            foreach ($new->runs as $run) RunLabModelJob::dispatch($run->id);
            $this->audit($new, $request, 'duplicated', ['parent_experiment_id' => $experiment->id]);
            return $new;
        });
        return redirect()->route('admin.lab.show', $new)->with('success', 'آزمایش تکثیر شد و با همان مدل‌ها در صف اجرا قرار گرفت.');
    }

    public function apply(Request $request, LabExperiment $experiment)
    {
        $experiment->load(['product', 'runs.outputs.scores', 'runs.aiModel']);
        if ($experiment->status !== 'completed') return back()->with('error', 'فقط آزمایش تکمیل‌شده قابل اعمال روی محصول است.');
        $this->recalculateRanking($experiment);
        $experiment->load(['runs.aiModel']);
        $gradeConfig = [];
        foreach ($this->gradeDefinitions([], (array) data_get($experiment->settings, 'grades', []), collect()) as $gradeKey => $grade) {
            $runs = $experiment->runs->where('grade_key', $gradeKey)->sortBy('rank')->values();
            $winner = $runs->firstWhere('is_selected', true) ?: $runs->first();
            if (!$winner) continue;
            $gradeConfig[$gradeKey] = [
                'key' => $gradeKey,
                'label' => $grade['label'],
                'resolution' => $grade['resolution'],
                'aspect_ratio' => $grade['aspect_ratio'],
                'primary' => $this->runModelSnapshot($winner),
                'fallbacks' => $runs->skip(1)->map(fn (LabRun $run) => $this->runModelSnapshot($run))->values()->all(),
            ];
        }
        if (!$gradeConfig) return back()->with('error', 'برای اعمال نتیجه، حداقل یک گرید باید رتبه‌بندی شده باشد.');
        $standard = $gradeConfig['standard'] ?? reset($gradeConfig);
        $product = $experiment->product;
        $product->forceFill([
            'lab_grade_config' => $gradeConfig,
            'primary_model' => data_get($standard, 'primary.model_id', $product->primary_model),
            'ai_provider' => data_get($standard, 'primary.provider', $product->ai_provider),
            'fallback_models' => collect(data_get($standard, 'fallbacks', []))->pluck('model_id')->values()->all(),
            'fallback_model_providers' => collect(data_get($standard, 'fallbacks', []))->pluck('provider')->values()->all(),
        ])->save();
        $experiment->forceFill(['overall_score' => $experiment->overall_score, 'applied_at' => now(), 'applied_by' => $request->user('admin')?->id])->save();
        $this->audit($experiment, $request, 'applied_to_product', ['grade_config' => $gradeConfig]);
        return back()->with('success', 'نتیجه‌ی آزمایش برای سه گرید روی محصول اعمال شد.');
    }

    public function retry(Request $request, LabRun $run)
    {
        if ($run->status !== 'failed' || $run->retry_count >= $run->max_retries) {
            return back()->with('error', 'این اجرا قابل تلاش مجدد نیست یا سقف تلاش‌ها پر شده است.');
        }
        $run->forceFill(['status' => 'queued', 'retry_count' => $run->retry_count + 1, 'error_message' => null, 'completed_at' => null])->save();
        RunLabModelJob::dispatch($run->id);
        $this->audit($run->experiment, $request, 'retry', ['run_id' => $run->id, 'retry_count' => $run->retry_count]);
        return back()->with('success', 'اجرا دوباره در صف قرار گرفت.');
    }

    public function cancel(Request $request, LabExperiment $experiment)
    {
        $experiment->runs()->whereIn('status', ['queued', 'processing'])->update(['status' => 'cancelled', 'completed_at' => now()]);
        $experiment->update(['status' => 'cancelled', 'completed_at' => now()]);
        $this->audit($experiment, $request, 'cancelled');
        return back()->with('success', 'آزمایش لغو شد.');
    }

    public function score(Request $request, \App\Models\LabRunOutput $output)
    {
        $data = $request->validate(['manual_score' => ['nullable', 'numeric', 'min:1', 'max:5'], 'note' => ['nullable', 'string', 'max:2000'], 'is_winner' => ['nullable', 'boolean']]);
        $output->update(['manual_score' => $data['manual_score'] ?? null, 'note' => $data['note'] ?? null, 'is_winner' => false]);
        $experiment = $output->run->experiment;
        $experiment->load(['runs.outputs.scores']);
        $this->recalculateRanking($experiment, (bool) ($data['is_winner'] ?? false) ? $output->id : null);
        $this->audit($experiment, $request, 'score_updated', ['output_id' => $output->id, 'score' => $output->manual_score]);
        return back()->with('success', 'ارزیابی خروجی ذخیره شد.');
    }

    public function managerScore(Request $request, LabRunOutput $output)
    {
        $data = $request->validate([
            'overall_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'similarity_score' => ['nullable', 'string', 'max:24'],
            'detail_quality' => ['nullable', 'string', 'max:24'],
            'usage_priority' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $experiment = $output->loadMissing('run.experiment.runs.outputs.managerScore')->run->experiment;
        $maxPriority = $experiment->runs->count();
        if (!empty($data['usage_priority']) && $data['usage_priority'] > $maxPriority) {
            return back()->withErrors(['usage_priority' => 'اولویت استفاده نمی‌تواند بیشتر از تعداد مدل‌های آزمایش باشد.']);
        }

        if (!empty($data['usage_priority'])) {
            $duplicate = LabManagerScore::query()
                ->where('usage_priority', $data['usage_priority'])
                ->whereHas('output.run', fn ($query) => $query->where('lab_experiment_id', $experiment->id))
                ->where('lab_run_output_id', '!=', $output->id)
                ->exists();
            if ($duplicate) {
                return back()->withErrors(['usage_priority' => 'این اولویت برای خروجی دیگری ثبت شده است.']);
            }
        }

        LabManagerScore::updateOrCreate(
            ['lab_run_output_id' => $output->id],
            [
                'admin_id' => $request->user('admin')?->id,
                'overall_score' => $data['overall_score'] ?? null,
                'similarity_score' => $data['similarity_score'] ?? null,
                'detail_quality' => $data['detail_quality'] ?? null,
                'usage_priority' => $data['usage_priority'] ?? null,
                'notes' => $data['notes'] ?? null,
                'rated_at' => now(),
            ]
        );

        if (array_key_exists('overall_score', $data)) {
            $output->forceFill([
                'manual_score' => $data['overall_score'] !== null ? ((float) $data['overall_score'] / 2) : null,
                'note' => $data['notes'] ?? $output->note,
            ])->save();
        }

        $experiment->load(['runs.outputs.scores', 'runs.outputs.managerScore']);
        $this->recalculateRanking($experiment);
        $experiment->forceFill([
            'status' => in_array($experiment->status, ['completed', 'evaluated', 'finalized'], true) ? 'evaluated' : $experiment->status,
            'evaluated_at' => now(),
            'report_status' => 'ready',
        ])->save();
        $this->audit($experiment, $request, 'manager_score_updated', ['output_id' => $output->id, 'score' => $data]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'experiment' => $this->experimentPayload($experiment->fresh(['product', 'images', 'runs.aiModel', 'runs.outputs.managerScore', 'runs.outputs.scores']))]);
        }

        return back()->with('success', 'نمره مدیر سایت ذخیره شد و جدول محاسبه دقیق به‌روزرسانی شد.');
    }

    public function reports()
    {
        $experiments = LabExperiment::query();
        $runs = LabRun::query();
        return view('admin.lab.reports', [
            'totalExperiments' => $experiments->count(),
            'completedExperiments' => (clone $experiments)->where('status', 'completed')->count(),
            'failedRuns' => (clone $runs)->where('status', 'failed')->count(),
            'totalCostUsd' => (float) $runs->sum('actual_cost_usd'),
            'exchange' => $this->exchangeRate->usdToIrr(),
        ]);
    }

    private function imageCatalog(Product $product): array
    {
        $paths = array_values(array_unique(array_filter(array_merge([$product->cover, $product->thumbnail], (array) $product->sample_outputs, (array) $product->before_images))));
        return collect($paths)->map(function (string $path) {
            $disk = Storage::disk('public');
            if (!$disk->exists($path)) return null;
            $absolute = method_exists($disk, 'path') ? $disk->path($path) : null;
            $size = (int) $disk->size($path);
            $mime = $disk->mimeType($path) ?: 'image/jpeg';
            $width = $height = null;
            if ($absolute && is_file($absolute)) {
                $dimensions = @getimagesize($absolute);
                $width = $dimensions[0] ?? null; $height = $dimensions[1] ?? null;
            }
            return compact('path', 'size', 'mime', 'width', 'height') + ['url' => asset('storage/' . $path)];
        })->filter()->values()->all();
    }

    private function resolveQuickRunInputImage(Request $request, Product $product): ?array
    {
        if ($request->hasFile('input_image')) {
            $request->validate(['input_image' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288']]);
            $file = $request->file('input_image');
            $path = $file->store('lab-inputs', 'public');
            return $this->storedImageMeta($path, 'upload', $file->getClientOriginalName());
        }

        $requestedPath = trim((string) $request->input('input_path'));
        $catalog = collect($this->imageCatalog($product));
        $image = $requestedPath !== '' ? $catalog->firstWhere('path', $requestedPath) : $catalog->first();
        return $image ? $this->storedImageMeta($image['path'], 'product', basename($image['path'])) : null;
    }

    private function storedImageMeta(string $path, string $source, ?string $name = null): array
    {
        $disk = Storage::disk('public');
        $absolute = method_exists($disk, 'path') ? $disk->path($path) : null;
        $width = $height = null;
        if ($absolute && is_file($absolute)) {
            $dimensions = @getimagesize($absolute);
            $width = $dimensions[0] ?? null;
            $height = $dimensions[1] ?? null;
        }
        $mime = $disk->mimeType($path) ?: 'image/jpeg';
        return [
            'path' => $path,
            'name' => $name,
            'source' => $source,
            'width' => $width,
            'height' => $height,
            'ratio' => $width && $height ? round($width / $height, 4) : null,
            'size' => $disk->exists($path) ? (int) $disk->size($path) : null,
            'mime_type' => $mime,
            'format' => strtoupper(pathinfo($path, PATHINFO_EXTENSION) ?: last(explode('/', $mime))),
            'color' => 'RGB',
        ];
    }

    private function gradeDefinitions(array $data, array $gradeInputs, $globalModelIds): array
    {
        $defaultResolution = (string) ($data['resolution'] ?? '720');
        $defaultAspectRatio = (string) ($data['aspect_ratio'] ?? '4:5');
        $definitions = [
            'economic' => ['label' => 'اقتصادی', 'resolution' => '720', 'aspect_ratio' => '4:5'],
            'standard' => ['label' => 'استاندارد', 'resolution' => '1080', 'aspect_ratio' => '4:5'],
            'professional' => ['label' => 'حرفه‌ای', 'resolution' => '2160', 'aspect_ratio' => '4:5'],
        ];
        $globalModelIds = collect($globalModelIds)->map(fn ($id) => (int) $id)->filter()->values();

        foreach ($definitions as $index => $definition) {
            $input = (array) ($gradeInputs[$index] ?? []);
            $primary = (int) ($input['primary_model'] ?? 0);
            $fallbacks = collect((array) ($input['fallback_models'] ?? []))->map(fn ($id) => (int) $id)->filter()->values();
            $storedIds = collect((array) ($input['model_ids'] ?? []))->map(fn ($id) => (int) $id)->filter()->values();
            $modelIds = collect([$primary])->filter()->merge($fallbacks)->merge($storedIds)->unique()->values();

            if ($modelIds->isEmpty() && $globalModelIds->isNotEmpty()) {
                $primary = (int) ($globalModelIds->get($index) ?: $globalModelIds->first());
                $modelIds = collect([$primary]);
            }

            $definitions[$index] = [
                'key' => $index,
                'label' => (string) ($input['label'] ?? $definition['label']),
                'resolution' => (string) ($input['resolution'] ?? ($data ? $definition['resolution'] : $defaultResolution)),
                'aspect_ratio' => (string) ($input['aspect_ratio'] ?? ($data ? $definition['aspect_ratio'] : $defaultAspectRatio)),
                'primary_model_id' => $primary ?: (int) $modelIds->first(),
                'fallback_model_ids' => $modelIds->skip(1)->values()->all(),
                'model_ids' => $modelIds->values()->all(),
            ];
        }

        return $definitions;
    }

    private function runModelSnapshot(LabRun $run): array
    {
        return [
            'run_id' => $run->id,
            'model_id' => $run->model_id,
            'provider' => $run->provider,
            'name' => $run->alias,
            'score' => $run->final_score,
            'rank' => $run->rank,
        ];
    }

    private function recalculateRanking(LabExperiment $experiment, ?int $preferredOutputId = null): void
    {
        if (!$experiment->relationLoaded('runs')) $experiment->load('runs.outputs.scores');
        $runs = $experiment->runs;
        $bestScores = [];

        foreach ($runs->groupBy(fn (LabRun $run) => $run->grade_key ?: 'standard') as $gradeKey => $gradeRuns) {
            $ranked = $gradeRuns->map(function (LabRun $run) {
                $scores = $run->outputs->map(function ($output) {
                    $aiScores = $output->scores->where('evaluator_type', 'ai');
                    $aiAverage = $aiScores->isNotEmpty() ? (float) $aiScores->avg('score') : null;
                    $manual = $output->manual_score !== null ? (float) $output->manual_score : null;
                    return $manual !== null && $aiAverage !== null
                        ? ($manual + $aiAverage) / 2
                        : ($manual ?? $aiAverage);
                })->filter(fn ($score) => $score !== null);
                $run->forceFill(['final_score' => $scores->isNotEmpty() ? $scores->avg() : null, 'rank' => null, 'is_selected' => false])->save();
                return $run->fresh(['outputs.scores']);
            })->sortByDesc('final_score')->values();

            $preferredRun = $preferredOutputId
                ? $ranked->first(fn (LabRun $run) => $run->outputs->contains('id', $preferredOutputId))
                : null;
            if ($preferredRun) $ranked = collect([$preferredRun])->merge($ranked->reject(fn ($run) => $run->id === $preferredRun->id))->values();

            $ranked->each(function (LabRun $run, int $index) use ($gradeKey, &$bestScores) {
                $run->forceFill(['rank' => $index + 1, 'is_selected' => $index === 0])->save();
                $run->outputs()->update(['is_winner' => false]);
                if ($index === 0 && $run->final_score !== null) {
                    $bestScores[$gradeKey] = (float) $run->final_score;
                    $bestOutput = $run->outputs->sortByDesc(function ($output) {
                        $aiScores = $output->scores->where('evaluator_type', 'ai');
                        $aiAverage = $aiScores->isNotEmpty() ? (float) $aiScores->avg('score') : null;
                        $manual = $output->manual_score !== null ? (float) $output->manual_score : null;
                        return $manual !== null && $aiAverage !== null ? ($manual + $aiAverage) / 2 : ($manual ?? $aiAverage ?? 0);
                    })->first();
                    if ($bestOutput) $bestOutput->forceFill(['is_winner' => true])->save();
                }
            });
        }

        $experiment->forceFill(['overall_score' => $bestScores ? collect($bestScores)->avg() : null])->save();
    }

    private function productCategoryIds(Product $product): array
    {
        $ids = collect();
        foreach ($product->categories as $category) {
            while ($category) {
                $ids->push((int) $category->id);
                $category = $category->parent;
            }
        }

        if ($product->category_id) $ids->push((int) $product->category_id);
        return $ids->filter()->unique()->values()->all();
    }

    private function experimentPayload(LabExperiment $experiment): array
    {
        $experiment->loadMissing(['product', 'images', 'runs.aiModel', 'runs.outputs.scores', 'runs.outputs.managerScore']);
        return [
            'id' => $experiment->id,
            'uuid' => $experiment->uuid,
            'report_code' => $experiment->report_code,
            'title' => $experiment->title,
            'status' => $experiment->status,
            'status_label' => $experiment->status_label,
            'product' => ['id' => $experiment->product?->id, 'name' => $experiment->product?->name_fa, 'code' => $experiment->product?->product_code],
            'input' => ['path' => $experiment->input_image_path, 'url' => $experiment->input_image_path ? asset('storage/' . $experiment->input_image_path) : null, 'width' => $experiment->input_image_width, 'height' => $experiment->input_image_height, 'ratio' => $experiment->input_image_ratio, 'size' => $experiment->input_image_size, 'format' => $experiment->input_image_format, 'color' => $experiment->input_image_color],
            'models_count' => (int) ($experiment->models_count ?: $experiment->runs->count()),
            'evaluator' => ['model_id' => $experiment->evaluator_model_id, 'provider' => $experiment->evaluator_provider],
            'cost' => ['usd' => (float) ($experiment->total_cost_usd ?? $experiment->actual_cost_usd ?? $experiment->estimated_cost_usd), 'toman' => (float) ($experiment->total_cost_toman ?? $experiment->actual_cost_toman ?? $experiment->estimated_cost_toman), 'lab_usd' => (float) $experiment->lab_cost_usd, 'lab_toman' => (float) $experiment->lab_cost_toman],
            'overall_score' => $experiment->overall_score,
            'tested_at' => optional($experiment->tested_at ?: $experiment->completed_at)->toIso8601String(),
            'runs' => $experiment->runs->map(function (LabRun $run) {
                return ['id' => $run->id, 'model' => $run->model_name_snapshot ?: $run->alias ?: $run->model_id, 'provider' => $run->provider_name_snapshot ?: $run->provider, 'status' => $run->status, 'quality' => $run->quality, 'size' => $run->size, 'preserve_face' => (bool) $run->preserve_face, 'seconds' => $run->build_seconds !== null ? (float) $run->build_seconds : ($run->duration_ms !== null ? round($run->duration_ms / 1000, 2) : null), 'tokens' => $run->tokens_used, 'cost_usd' => (float) ($run->actual_cost_usd ?? $run->estimated_cost_usd), 'cost_toman' => (float) ($run->actual_cost_toman ?? $run->estimated_cost_toman), 'score' => $run->final_score, 'rank' => $run->rank, 'outputs' => $run->outputs->map(fn ($output) => ['id' => $output->id, 'url' => $output->url, 'manager' => $output->managerScore ? ['overall' => $output->managerScore->overall_score, 'similarity' => $output->managerScore->similarity_score, 'detail' => $output->managerScore->detail_quality, 'priority' => $output->managerScore->usage_priority, 'notes' => $output->managerScore->notes] : null, 'ai_scores' => $output->scores->where('evaluator_type', 'ai')->map(fn ($score) => ['criterion' => $score->criterion, 'score' => $score->score])->values()])->values()];
            })->values(),
        ];
    }

    private function audit(LabExperiment $experiment, Request $request, string $action, array $metadata = []): void
    {
        LabAuditLog::create([
            'lab_experiment_id' => $experiment->id,
            'admin_id' => $request->user('admin')?->id,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);
    }
}
