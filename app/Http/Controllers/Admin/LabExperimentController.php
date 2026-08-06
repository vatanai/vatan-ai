<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunLabModelJob;
use App\Models\AiModel;
use App\Models\LabAuditLog;
use App\Models\LabExperiment;
use App\Models\LabRun;
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
        $query = LabExperiment::with('product')->withCount(['runs', 'images'])->latest();
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

    public function store(Request $request)
    {
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

        $experiment = DB::transaction(function () use ($request, $data, $product, $selectedImages, $models, $rate, $prompt, $negativePrompt, $count, $estimatedUsd, $gradeDefinitions) {
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
                'exchange_rate_irr' => (float) ($rate['rate'] ?? 0),
            ]);

            foreach ($selectedImages as $image) {
                $experiment->images()->create([
                    'image_path' => $image['path'], 'role' => 'reference', 'source' => 'product',
                    'width' => $image['width'], 'height' => $image['height'], 'size' => $image['size'], 'mime_type' => $image['mime_type'],
                ]);
            }

            foreach ($gradeDefinitions as $gradeKey => $grade) {
                foreach ($grade['model_ids'] as $attemptOrder => $modelId) {
                    $model = $models->get($modelId);
                    $run = $experiment->runs()->create([
                        'ai_model_id' => $model->id,
                        'model_id' => $model->openrouter_model_id,
                        'provider' => $model->provider,
                        'alias' => $model->name,
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
        $experiment->load(['runs.outputs.scores']);
        if ($experiment->status === 'completed') {
            $this->recalculateRanking($experiment);
            $experiment->load(['runs.outputs.scores']);
        }

        return response()->json([
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
        $experiment->load(['product', 'images', 'runs.aiModel', 'runs.outputs.scores', 'admin', 'auditLogs.admin']);
        $this->recalculateRanking($experiment);
        $experiment->load(['runs.aiModel', 'runs.outputs.scores']);
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
