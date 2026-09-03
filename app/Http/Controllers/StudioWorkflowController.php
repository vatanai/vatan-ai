<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Product;
use App\Models\UserUpload;
use App\Services\ProductBuildSchema;
use App\Services\StudioCostService;
use App\Services\VideoGenerationService;
use App\Services\VideoModelSchemaService;
use App\Services\VideoProductConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudioWorkflowController extends Controller
{
    public function show(ProductBuildSchema $schema, VideoModelSchemaService $modelSchemas)
    {
        $data = app(ProductGenerateController::class)->studioViewData($schema);
        $data['experimental'] = true;
        $data['studioConfig']['workflow_generate_url'] = route('app.create.studio.workflows.generate');
        $data['studioConfig']['workflow_quote_url'] = route('app.create.studio.workflows.quote');
        $data['studioConfig']['workflow_models'] = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'video')
            ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
            ->whereNotNull('openrouter_model_id')
            ->where('openrouter_model_id', '<>', '')
            ->orderByDesc('lab_priority')
            ->orderBy('id')
            ->get()
            ->map(function (AiModel $model) use ($modelSchemas): array {
                $summary = $modelSchemas->summarize($model);
                $capabilities = (array) ($model->capability_config ?? []);
                $maxImages = max(1, min(4, (int) data_get(
                    $capabilities,
                    'max_reference_images',
                    data_get($capabilities, 'max_images', 1),
                )));

                return [
                    'value' => (string) $model->openrouter_model_id,
                    'provider' => (string) $model->provider,
                    'task_type' => (string) $model->task_type,
                    'supports_text' => $model->task_type === 'text_to_video' || data_get($model->capability_config, 'supports_text_to_video') === true,
                    'supports_image' => (bool) $summary['supports_image'],
                    'supports_video' => (bool) $summary['supports_video'],
                    'supports_multiple_images' => $maxImages > 1,
                    'max_images' => $maxImages,
                    'supported_durations' => array_values(array_map('strval', data_get($capabilities, 'supported_durations', $summary['durations'] ?? []))),
                    'supported_resolutions' => array_values(array_map('strval', data_get($capabilities, 'supported_resolutions', $summary['resolutions'] ?? []))),
                    'supported_aspect_ratios' => array_values(array_map('strval', data_get($capabilities, 'supported_aspect_ratios', $summary['aspect_ratios'] ?? []))),
                ];
            })
            ->values()
            ->all();

        return view('app.create-studio', $data);
    }

    public function quote(Request $request, StudioCostService $studioCosts): \Illuminate\Http\JsonResponse
    {
        $workflow = $this->workflow($request->query('workflow'));
        $product = $this->videoProduct();
        $model = $this->selectedModel($request, $product, $workflow);
        $quote = $studioCosts->quote($product, [
            'media_type' => 'video',
            'resolution' => (string) $request->query('resolution', '720p'),
            'aspect_ratio' => (string) $request->query('aspect_ratio', '16:9'),
            'duration' => max(1, min(15, (int) $request->query('duration', 4))),
            'count' => 1,
        ], $model);

        return response()->json($quote + [
            'workflow' => $workflow,
            'input_count' => max(0, min(4, (int) $request->query('input_count', 0))),
        ]);
    }

    public function generate(Request $request, VideoGenerationService $videos, VideoModelSchemaService $modelSchemas): \Illuminate\Http\JsonResponse
    {
        $workflow = $this->workflow($request->input('workflow'));
        $product = $this->videoProduct();
        $model = $this->selectedModel($request, $product, $workflow);
        $isImageWorkflow = in_array($workflow, ['image_to_video', 'image_sequence_to_video'], true);
        $isVideoWorkflow = $workflow === 'video_to_video';

        $request->validate([
            'workflow' => ['required', Rule::in(['text_to_video', 'image_to_video', 'image_sequence_to_video', 'video_to_video'])],
            'prompt' => ['required', 'string', 'max:5000'],
            'video.duration' => ['required', 'integer', 'min:1', 'max:15'],
            'video.aspect_ratio' => ['required', Rule::in(VideoProductConfigService::STUDIO_ASPECT_RATIOS)],
            'video.resolution' => ['required', Rule::in(VideoProductConfigService::RESOLUTIONS)],
            'video.motion_preset' => ['nullable', 'string', 'max:80'],
            'source_images' => [$isImageWorkflow ? 'required' : 'nullable', 'array', 'min:' . ($workflow === 'image_sequence_to_video' ? 2 : 1), 'max:4'],
            'source_images.*' => ['image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'source_video' => [$isVideoWorkflow ? 'required' : 'nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
            'rights_confirmed' => ['accepted'],
        ]);
        $this->ensureSupportedOptions($model, $request, $modelSchemas);

        $runner = clone $product;
        $runner->primary_model = $model->openrouter_model_id;
        $runner->ai_provider = $model->provider;
        $runner->fallback_models = [];
        $runner->fallback_model_providers = [];
        $providerOptions = (array) $runner->provider_options;
        $videoConfig = $runner->videoConfiguration();
        $videoConfig['workflow'] = $isImageWorkflow ? 'image_to_video' : $workflow;
        $videoConfig['durations'] = range(1, 15);
        $videoConfig['resolutions'] = VideoProductConfigService::RESOLUTIONS;
        $videoConfig['aspect_ratios'] = VideoProductConfigService::STUDIO_ASPECT_RATIOS;
        $providerOptions['video'] = $videoConfig;
        $runner->provider_options = $providerOptions;

        $imageData = [];
        $imagePaths = [];
        foreach ((array) $request->file('source_images', []) as $file) {
            $path = $file->store('uploads/video-inputs/images', 'public');
            $imagePaths[] = $path;
            $imageData[] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            UserUpload::create([
                'user_id' => $request->user()->id,
                'file_path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        $sourceVideoUrl = null;
        $sourceVideoPath = null;
        if ($request->hasFile('source_video')) {
            $file = $request->file('source_video');
            $sourceVideoPath = $file->store('uploads/video-inputs/videos', 'public');
            $sourceVideoUrl = asset('storage/' . $sourceVideoPath);
            UserUpload::create([
                'user_id' => $request->user()->id,
                'file_path' => $sourceVideoPath,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        try {
            $generation = $videos->start($runner, $request->user(), [
                'prompt' => trim((string) $request->input('prompt')),
                'negative_prompt' => '',
                'fields' => [],
                'duration' => (int) $request->input('video.duration'),
                'aspect_ratio' => (string) $request->input('video.aspect_ratio'),
                'resolution' => (string) $request->input('video.resolution'),
                'motion_preset' => (string) $request->input('video.motion_preset', ''),
                'generate_audio' => false,
                'source_image_data' => $imageData[0] ?? null,
                'source_image_data_list' => $imageData,
                'source_upload_path' => $imagePaths[0] ?? null,
                'source_upload_paths' => $imagePaths,
                'source_video_url' => $sourceVideoUrl,
                'workflow' => $workflow,
                'reference_mode' => $workflow === 'image_sequence_to_video' || count($imageData) > 1 ? 'input_references' : null,
                'studio_mode' => true,
            ]);

            return response()->json([
                'success' => true,
                'status' => $generation->status,
                'generation_id' => $generation->id,
                'status_url' => route('app.video-generation.status', $generation),
                'message' => 'درخواست وارد صف ساخت شد.',
            ], 202);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'ساخت ویدیو انجام نشد؛ مدل یا اتصال سرویس را بررسی کنید.',
                'error_code' => 'STUDIO_WORKFLOW_UNAVAILABLE',
            ], 503);
        }
    }

    private function videoProduct(): Product
    {
        $product = Product::query()
            ->where('status', 'active')
            ->where('slug', 'ai-cinematic-short-video')
            ->first()
            ?: Product::query()->where('status', 'active')->where('output_type', 'video')->latest()->first();

        abort_unless($product && $product->isVideoProduct(), 404);

        return $product;
    }

    private function workflow(?string $workflow): string
    {
        return in_array($workflow, ['text_to_video', 'image_to_video', 'image_sequence_to_video', 'video_to_video'], true)
            ? $workflow
            : 'text_to_video';
    }

    private function selectedModel(Request $request, Product $product, string $workflow): AiModel
    {
        $modelId = trim((string) $request->input('studio_model', $request->query('model', $product->primary_model)));
        $provider = trim((string) $request->input('studio_provider', $request->query('provider', $product->ai_provider)));
        $model = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'video')
            ->where('openrouter_model_id', $modelId)
            ->when($provider !== '', fn ($query) => $query->where('provider', $provider))
            ->first();

        $capabilities = (array) ($model?->capability_config ?? []);
        $compatible = match ($workflow) {
            'text_to_video' => $model && ($model->task_type === 'text_to_video' || data_get($capabilities, 'supports_text_to_video') === true),
            'video_to_video' => $model && ($model->task_type === 'video_to_video' || data_get($capabilities, 'supports_video_to_video') === true),
            default => $model && ($model->task_type === 'image_to_video' || $model->task_type === 'face_animation' || data_get($capabilities, 'supports_image_to_video') === true),
        };

        if (!$compatible) {
            throw ValidationException::withMessages(['studio_model' => 'مدل انتخاب‌شده برای این نوع ورودی سازگار نیست.']);
        }

        return $model;
    }

    private function ensureSupportedOptions(AiModel $model, Request $request, VideoModelSchemaService $modelSchemas): void
    {
        $capabilities = (array) ($model->capability_config ?? []);
        $summary = $modelSchemas->summarize($model);
        $checks = [
            [
                'input' => 'video.duration',
                'capability' => 'supported_durations',
                'schema' => $summary['durations'] ?? [],
                'message' => 'مدل انتخاب‌شده این زمان ویدیو را پشتیبانی نمی‌کند؛ یک زمان سازگار انتخاب کنید.',
                'normalize' => static fn (mixed $value): string => (string) ((int) $value),
            ],
            [
                'input' => 'video.resolution',
                'capability' => 'supported_resolutions',
                'schema' => $summary['resolutions'] ?? [],
                'message' => 'مدل انتخاب‌شده این کیفیت خروجی را پشتیبانی نمی‌کند؛ کیفیت دیگری انتخاب کنید.',
                'normalize' => static fn (mixed $value): string => match (strtolower(trim((string) $value))) {
                    '2160', '2160p', '4k' => '4k',
                    '1440', '1440p', '2k' => '2k',
                    '1080', '1080p' => '1080p',
                    '720', '720p' => '720p',
                    '480', '480p' => '480p',
                    default => strtolower(trim((string) $value)),
                },
            ],
            [
                'input' => 'video.aspect_ratio',
                'capability' => 'supported_aspect_ratios',
                'schema' => $summary['aspect_ratios'] ?? [],
                'message' => 'مدل انتخاب‌شده این نسبت تصویر را پشتیبانی نمی‌کند؛ نسبت دیگری انتخاب کنید.',
                'normalize' => static fn (mixed $value): string => strtolower(trim((string) $value)),
            ],
        ];

        foreach ($checks as $check) {
            $supported = data_get($capabilities, $check['capability']);
            if (!is_array($supported) || $supported === []) $supported = $check['schema'];
            if (!is_array($supported) || $supported === []) continue;

            $value = ($check['normalize'])($request->input($check['input']));
            $allowed = array_map($check['normalize'], $supported);
            if (!in_array($value, $allowed, true)) {
                throw ValidationException::withMessages([$check['input'] => $check['message']]);
            }
        }
    }
}
