<?php

namespace App\Http\Controllers;

use App\Models\FaceProfile;
use App\Models\GeneratedVideo;
use App\Models\Product;
use App\Models\AiModel;
use App\Models\ProductMetricEvent;
use App\Models\UserUpload;
use App\Services\ProductBuildSchema;
use App\Services\VideoGenerationService;
use App\Services\VideoProductConfigService;
use App\Services\VideoModelSchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VideoProductController extends Controller
{
    public function show(Request $request, Product $product, ProductBuildSchema $schema, VideoModelSchemaService $modelSchemas)
    {
        abort_unless($product->status === 'active' && $product->isVideoProduct(), 404);
        ProductMetricEvent::create([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_type' => $request->query('source') === 'trends' ? 'trend_open' : 'view',
        ]);

        $buildProduct = $schema->pageData($product);
        $config = $product->videoConfiguration();
        $model = \App\Models\AiModel::where('provider', $product->ai_provider)->where('openrouter_model_id', $product->primary_model)->first();
        $supported = (array) data_get($model ? $modelSchemas->properties($model) : [], 'resolution.enum', []);
        if ($supported !== []) {
            $config['resolutions'] = array_values(array_intersect((array) $config['resolutions'], $supported)) ?: [$supported[0]];
            if (!in_array($config['default_resolution'], $config['resolutions'], true)) $config['default_resolution'] = $config['resolutions'][0];
        }
        if (in_array($config['workflow'], ['image_to_video', 'video_to_video'], true)) {
            $dedicatedTypes = $config['workflow'] === 'image_to_video'
                ? ['image_upload', 'multi_image']
                : ['file_upload'];
            $buildProduct['fields'] = collect($buildProduct['fields'])
                ->reject(fn (array $field): bool => in_array($field['type'], $dedicatedTypes, true))
                ->values()
                ->all();
        }
        $buildProduct['video'] = $config + [
            'preview_url' => $product->previewVideoUrl(),
            'status_url_template' => route('app.video-generation.status', ['generatedVideo' => '__ID__']),
        ];
        $buildProduct['cost'] = (int) data_get($config, 'credit_costs_by_duration.' . $config['default_duration'], $product->credit_cost);

        return view('app.create-video-product', compact('product', 'buildProduct'));
    }

    public function generate(Request $request, Product $product, ProductBuildSchema $schema, VideoGenerationService $videos)
    {
        abort_unless($product->status === 'active' && $product->isVideoProduct(), 404);
        $this->applyStudioModel($product, $request);
        $config = $product->videoConfiguration();
        $studioMode = $request->boolean('studio_mode');
        $studioConfig = app(VideoProductConfigService::class);
        $allowedDurations = $studioMode ? range(1, 15) : array_map('intval', (array) $config['durations']);
        $allowedResolutions = $studioMode ? VideoProductConfigService::RESOLUTIONS : (array) $config['resolutions'];
        $allowedMotions = $studioMode
            ? array_keys($studioConfig->motionPresetCatalog())
            : collect((array) $config['motion_presets'])->pluck('key')->all();
        $request->validate([
            ...$schema->rules($product),
            'prompt' => ['nullable', 'string', 'max:5000'],
            'negative_prompt' => ['nullable', 'string', 'max:2000'],
            'studio_project_name' => ['nullable', 'string', 'max:120'],
            'video' => ['required', 'array'],
            'video.duration' => ['required', 'integer', Rule::in($allowedDurations)],
            'video.aspect_ratio' => ['required', Rule::in($studioMode ? VideoProductConfigService::STUDIO_ASPECT_RATIOS : (array) $config['aspect_ratios'])],
            'video.resolution' => ['required', Rule::in($allowedResolutions)],
            'video.motion_preset' => ['nullable', Rule::in($allowedMotions)],
            'video.generate_audio' => ['nullable', 'boolean'],
            'source_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'source_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
            'source_audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg', 'max:20480'],
            'face_profile_id' => ['nullable', 'integer'],
            'rights_confirmed' => ['accepted'],
        ]);

        $user = $request->user();
        $sourceImageData = null;
        $sourceUploadPath = null;
        $faceProfile = $this->selectedFaceProfile($request, $user);
        if ($faceProfile) {
            $entry = collect($faceProfile->referenceImageEntries())->first(fn (array $image): bool => Storage::disk('public')->exists((string) ($image['path'] ?? '')));
            if ($entry) $sourceImageData = $this->imageDataUri((string) $entry['path'], $entry['mime'] ?? null);
        }
        if (!$sourceImageData && $request->hasFile('source_image')) {
            $file = $request->file('source_image');
            $sourceUploadPath = $file->store('uploads/video-inputs/images', 'public');
            $sourceImageData = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            UserUpload::create(['user_id' => $user->id, 'file_path' => $sourceUploadPath, 'size' => $file->getSize(), 'mime_type' => $file->getMimeType()]);
        }

        $sourceVideoUrl = null;
        if ($request->hasFile('source_video')) {
            $file = $request->file('source_video');
            $path = $file->store('uploads/video-inputs/videos', 'public');
            $sourceVideoUrl = asset('storage/' . $path);
            UserUpload::create(['user_id' => $user->id, 'file_path' => $path, 'size' => $file->getSize(), 'mime_type' => $file->getMimeType()]);
        }
        $audioUrl = null;
        if ($request->hasFile('source_audio')) {
            $file = $request->file('source_audio');
            $path = $file->store('uploads/video-inputs/audio', 'public');
            $audioUrl = asset('storage/' . $path);
            UserUpload::create(['user_id' => $user->id, 'file_path' => $path, 'size' => $file->getSize(), 'mime_type' => $file->getMimeType()]);
        }

        try {
            $generation = $videos->start($product, $user, [
                'prompt' => (string) $request->input('prompt', ''),
                'negative_prompt' => (string) $request->input('negative_prompt', ''),
                'project_name' => trim((string) $request->input('studio_project_name', '')) ?: null,
                'fields' => (array) $request->input('fields', []),
                'duration' => (int) $request->input('video.duration'),
                'aspect_ratio' => (string) $request->input('video.aspect_ratio'),
                'resolution' => (string) $request->input('video.resolution'),
                'motion_preset' => (string) $request->input('video.motion_preset', ''),
                'generate_audio' => $request->boolean('video.generate_audio'),
                'face_profile_id' => $faceProfile?->id,
                'source_image_data' => $sourceImageData,
                'source_upload_path' => $sourceUploadPath,
                'source_video_url' => $sourceVideoUrl,
                'audio_url' => $audioUrl,
                'studio_mode' => $studioMode,
            ]);

            return response()->json([
                'success' => true,
                'status' => $generation->status,
                'generation_id' => $generation->id,
                'status_url' => route('app.video-generation.status', $generation),
                'message' => 'ویدیو وارد صف ساخت شد. این صفحه را باز نگه دارید؛ نتیجه خودکار نمایش داده می‌شود.',
                'remaining_tokens' => $user->fresh()->tokens,
            ], 202);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json([
                'success' => false,
                'message' => 'ارسال درخواست ویدیو انجام نشد. تنظیم مدل یا اتصال سرویس را بررسی کنید.',
                'error_code' => 'VIDEO_PROVIDER_UNAVAILABLE',
            ], 503);
        }
    }

    public function quote(Request $request, Product $product, VideoGenerationService $videos)
    {
        abort_unless($product->status === 'active' && $product->isVideoProduct(), 404);
        $quote = $videos->quote($product, $request->user(), [
            'duration' => $request->integer('duration'), 'resolution' => (string) $request->input('resolution'),
            'generate_audio' => $request->boolean('generate_audio'), 'fields' => (array) $request->input('fields', []),
        ]);
        return response()->json($quote);
    }

    public function status(Request $request, GeneratedVideo $generatedVideo, VideoGenerationService $videos)
    {
        abort_unless((int) $generatedVideo->user_id === (int) $request->user()->id, 403);
        try {
            $generatedVideo = $videos->refresh($generatedVideo);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'success' => true,
            'status' => $generatedVideo->status,
            'video_url' => $generatedVideo->playbackUrl(),
            'error_message' => $generatedVideo->error_message,
            'remaining_tokens' => $request->user()->fresh()->tokens,
        ]);
    }

    private function selectedFaceProfile(Request $request, $user): ?FaceProfile
    {
        if (!$request->filled('face_profile_id')) return null;
        $profile = $user?->faceProfiles()->active()->whereKey($request->integer('face_profile_id'))->first();
        if (!$profile || $profile->referenceImageEntries() === []) {
            throw ValidationException::withMessages(['face_profile_id' => 'پروفایل چهره انتخاب‌شده معتبر نیست.']);
        }

        return $profile;
    }

    private function imageDataUri(string $path, ?string $mime = null): ?string
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($path)) return null;
        $mime = $mime ?: $disk->mimeType($path);
        if (!str_starts_with((string) $mime, 'image/')) return null;

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
    }

    private function applyStudioModel(Product $product, Request $request): void
    {
        $modelId = trim((string) $request->input('studio_model', ''));
        $query = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'video')
            ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
            ->when($modelId !== '', fn ($builder) => $builder->where('openrouter_model_id', $modelId))
            ->when($request->filled('studio_provider'), fn ($builder) => $builder->where('provider', (string) $request->input('studio_provider')),
                fn ($builder) => $modelId === '' ? $builder->orderByRaw("CASE task_type WHEN 'text_to_video' THEN 0 WHEN 'image_to_video' THEN 1 WHEN 'video_to_video' THEN 2 ELSE 3 END")->orderByRaw("CASE provider WHEN 'fal' THEN 0 WHEN 'replicate' THEN 1 ELSE 2 END")->orderByDesc('lab_priority') : $builder);
        $model = $query->first();
        if (!$model) {
            throw ValidationException::withMessages(['studio_model' => 'مدل انتخاب‌شده برای ساخت ویدیو فعال نیست.']);
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
}
