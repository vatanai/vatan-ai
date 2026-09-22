<?php

namespace App\Http\Controllers;

use App\Models\FaceProfile;
use App\Models\GeneratedVideo;
use App\Models\GeneratedImage;
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
use App\Jobs\PollStudioVideoGeneration;
use Illuminate\Support\Facades\Log;

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
        $capabilities = $model ? $modelSchemas->summarize($model) : [];
        $supportedResolutions = array_values(array_map('strval', (array) ($capabilities['resolutions'] ?? [])));
        $supportedDurations = array_values(array_map('intval', (array) ($capabilities['durations'] ?? [])));
        $supportedAspects = array_values(array_map('strval', (array) ($capabilities['aspect_ratios'] ?? [])));
        if ($supportedResolutions !== []) {
            $config['resolutions'] = array_values(array_intersect((array) $config['resolutions'], $supportedResolutions)) ?: [$supportedResolutions[0]];
            if (!in_array($config['default_resolution'], $config['resolutions'], true)) $config['default_resolution'] = $config['resolutions'][0];
        }
        if ($supportedDurations !== []) {
            $config['durations'] = array_values(array_intersect(array_map('intval', (array) $config['durations']), $supportedDurations)) ?: [$supportedDurations[0]];
            if (!in_array((int) $config['default_duration'], $config['durations'], true)) $config['default_duration'] = $config['durations'][0];
        }
        if ($supportedAspects !== []) {
            $config['aspect_ratios'] = array_values(array_intersect((array) $config['aspect_ratios'], $supportedAspects)) ?: [$supportedAspects[0]];
            if (!in_array((string) $config['default_aspect_ratio'], $config['aspect_ratios'], true)) $config['default_aspect_ratio'] = $config['aspect_ratios'][0];
        }
        $config['quality_tiers'] = collect((array) ($config['quality_tiers'] ?? []))->map(function (array $tier) use ($config): array {
            if (!in_array((string) ($tier['resolution'] ?? ''), (array) $config['resolutions'], true)) {
                $tier['resolution'] = end($config['resolutions']) ?: $config['default_resolution'];
            }
            return $tier;
        })->values()->all();
        if (in_array($config['workflow'], ['image_to_video', 'video_to_video'], true)) {
            $dedicatedTypes = $config['workflow'] === 'image_to_video'
                ? ['image_upload', 'multi_image']
                : ['file_upload'];
            $buildProduct['fields'] = collect($buildProduct['fields'])
                ->reject(fn (array $field): bool => in_array($field['type'], $dedicatedTypes, true))
                ->values()
                ->all();
        }
        $prefilledImage = $this->prefilledGeneratedImage($request, $product);
        $buildProduct['video'] = $config + [
            'preview_url' => $product->previewVideoUrl(),
            'status_url_template' => route('app.video-generation.status', ['generatedVideo' => '__ID__']),
            'prefill_source_image' => $prefilledImage ? ['id' => $prefilledImage->id, 'url' => $prefilledImage->imageUrl()] : null,
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
        $allowedAspectRatios = $studioMode ? VideoProductConfigService::STUDIO_ASPECT_RATIOS : (array) $config['aspect_ratios'];
        if (!$studioMode && !empty($config['preserve_source_aspect_ratio'])) {
            $allowedAspectRatios[] = 'source';
        }
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
            'video.aspect_ratio' => ['required', Rule::in($allowedAspectRatios)],
            'video.resolution' => ['required', Rule::in($allowedResolutions)],
            'video.quality' => ['nullable', Rule::in(VideoProductConfigService::QUALITY_KEYS)],
            'video.motion_preset' => ['nullable', Rule::in($allowedMotions)],
            'video.generate_audio' => ['nullable', 'boolean'],
            'source_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'source_face_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'source_product_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:12288'],
            'source_generated_image_id' => ['nullable', 'integer'],
            'source_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
            'source_audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg', 'max:20480'],
            'face_profile_id' => ['nullable', 'integer'],
            'rights_confirmed' => ['accepted'],
        ]);

        $selectedQuality = (string) $request->input('video.quality', 'standard');
        $qualityTier = collect((array) ($config['quality_tiers'] ?? []))->firstWhere('key', $selectedQuality);
        $selectedResolution = (string) $request->input('video.resolution');
        if (!$studioMode && is_array($qualityTier) && in_array((string) ($qualityTier['resolution'] ?? ''), $allowedResolutions, true)) {
            $selectedResolution = (string) $qualityTier['resolution'];
        }

        $user = $request->user();
        $sourceImageData = null;
        $sourceUploadPath = null;
        $sourceImageDataList = [];
        $sourceUploadPaths = [];
        $contract = (array) ($config['input_contract'] ?? []);
        $faceProfile = $this->selectedFaceProfile($request, $user);
        if ($faceProfile) {
            $entry = collect($faceProfile->referenceImageEntries())->first(fn (array $image): bool => Storage::disk('public')->exists($this->storagePath((string) ($image['path'] ?? ''))));
            if ($entry) {
                $profileData = $this->imageDataUri((string) $entry['path'], $entry['mime'] ?? null);
                if ($profileData) $sourceImageDataList[] = $profileData;
            }
        }
        $storeImage = function (string $field) use ($request, $user, &$sourceUploadPath, &$sourceImageDataList, &$sourceUploadPaths): void {
            if (!$request->hasFile($field)) return;
            $file = $request->file($field);
            $path = $file->store('uploads/video-inputs/images', 'public');
            $sourceUploadPath ??= $path;
            $sourceUploadPaths[] = $path;
            $sourceImageDataList[] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
            UserUpload::create(['user_id' => $user->id, 'file_path' => $path, 'size' => $file->getSize(), 'mime_type' => $file->getMimeType()]);
        };
        $storeImage('source_face_image');
        $storeImage('source_product_image');
        // محصولات قدیمی و فرم‌های تک‌ورودی همچنان از نام قبلی پشتیبانی می‌کنند.
        if ($sourceImageDataList === [] || (!$request->hasFile('source_face_image') && !$request->hasFile('source_product_image'))) {
            $storeImage('source_image');
        }
        if ($request->filled('source_generated_image_id') && !$request->hasFile('source_product_image')) {
            $generatedImage = $this->prefilledGeneratedImage($request, $product);
            if ($generatedImage) {
                $sourceUploadPath ??= $generatedImage->image_path;
                $sourceUploadPaths[] = $generatedImage->image_path;
                $generatedData = $this->imageDataUri($generatedImage->image_path);
                if ($generatedData) $sourceImageDataList[] = $generatedData;
            }
        }
        $sourceImageData = $sourceImageDataList[0] ?? null;
        if (($contract['product_required'] ?? false) && !$request->hasFile('source_product_image') && !$request->hasFile('source_image') && !$request->filled('source_generated_image_id')) {
            throw ValidationException::withMessages(['source_product_image' => 'تصویر محصول برای این محصول الزامی است.']);
        }
        if (($contract['face_required'] ?? false) && !$faceProfile && !$request->hasFile('source_face_image')) {
            throw ValidationException::withMessages(['source_face_image' => 'تصویر چهره یا پروفایل چهره برای این محصول الزامی است.']);
        }
        if ($request->input('video.aspect_ratio') === 'source' && !$sourceImageData) {
            throw ValidationException::withMessages(['source_image' => 'برای حفظ نسبت اصلی، خروجی عکس مرتبط در دسترس نیست.']);
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
                'resolution' => $selectedResolution,
                'quality' => $selectedQuality,
                'motion_preset' => (string) $request->input('video.motion_preset', ''),
                'generate_audio' => $request->boolean('video.generate_audio'),
                'face_profile_id' => $faceProfile?->id,
                'source_image_data' => $sourceImageData,
                'source_image_data_list' => $sourceImageDataList,
                'source_upload_path' => $sourceUploadPath,
                'source_upload_paths' => $sourceUploadPaths,
                'source_video_url' => $sourceVideoUrl,
                'audio_url' => $audioUrl,
                'timeline' => (array) ($config['timeline'] ?? []),
                'studio_mode' => $studioMode,
                'idempotency_key' => (string) $request->header('Idempotency-Key', $request->input('idempotency_key', '')),
                'correlation_id' => (string) $request->attributes->get('correlation_id', \Illuminate\Support\Str::uuid()),
            ]);

            return response()->json([
                'success' => true,
                'status' => $generation->status,
                'message' => 'ویدیو وارد صف ساخت شد. این صفحه را باز نگه دارید؛ نتیجه خودکار نمایش داده می‌شود.',
                'error_code' => null,
                'retryable' => false,
                'generation_id' => $generation->id,
                'credits_reserved' => (int) $generation->credits_reserved,
                'credits_settled' => (int) $generation->credits_settled,
                'credits_refunded' => (int) $generation->credits_refunded,
                'poll_url' => route('app.video-generation.status', $generation),
                'status_url' => route('app.video-generation.status', $generation),
                'remaining_tokens' => $user->fresh()->tokens,
            ], 202);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => 'ارسال درخواست ویدیو انجام نشد. تنظیم مدل یا اتصال سرویس را بررسی کنید.',
                'error_code' => 'VIDEO_PROVIDER_UNAVAILABLE',
                'retryable' => true,
                'generation_id' => null,
                'credits_reserved' => 0,
                'credits_settled' => 0,
                'credits_refunded' => 0,
                'poll_url' => null,
            ], 503);
        }
    }

    public function quote(Request $request, Product $product, VideoGenerationService $videos)
    {
        abort_unless($product->status === 'active' && $product->isVideoProduct(), 404);
        $quote = $videos->quote($product, $request->user(), [
            'duration' => $request->integer('duration'), 'resolution' => (string) $request->input('resolution'),
            'quality' => (string) $request->input('quality', 'standard'),
            'generate_audio' => $request->boolean('generate_audio'), 'fields' => (array) $request->input('fields', []),
        ]);
        return response()->json($quote);
    }

    public function status(Request $request, GeneratedVideo $generatedVideo, VideoGenerationService $videos)
    {
        abort_unless((int) $generatedVideo->user_id === (int) $request->user()->id, 403);
        Log::withContext([
            'correlation_id' => $generatedVideo->correlation_id,
            'generation_id' => $generatedVideo->id,
            'user_id' => $request->user()->id,
        ]);
        if (!in_array($generatedVideo->status, ['completed', 'failed', 'canceled', 'needs_review'], true)
            && (!$generatedVideo->next_poll_at || $generatedVideo->next_poll_at->isPast())) {
            PollStudioVideoGeneration::dispatchAfterResponse($generatedVideo->id);
            $generatedVideo->update(['next_poll_at' => now()->addSeconds(20)]);
        }

        $generatedVideo->refresh();

        return response()->json([
            'success' => true,
            'status' => $generatedVideo->status,
            'message' => $this->videoStatusMessage($generatedVideo),
            'error_code' => $generatedVideo->error_code,
            'retryable' => (bool) $generatedVideo->retryable,
            'generation_id' => $generatedVideo->id,
            'video_url' => $generatedVideo->status === 'completed' ? $generatedVideo->playbackUrl() : null,
            'error_message' => $generatedVideo->error_message,
            'credits_reserved' => (int) $generatedVideo->credits_reserved,
            'credits_settled' => (int) $generatedVideo->credits_settled,
            'credits_refunded' => (int) $generatedVideo->credits_refunded,
            'credits_returned' => (int) $generatedVideo->credits_refunded,
            'poll_url' => route('app.video-generation.status', $generatedVideo),
            'cancel_url' => $generatedVideo->cancel_requested_at || in_array($generatedVideo->status, ['completed', 'failed', 'canceled', 'needs_review'], true)
                ? null
                : route('app.video-generation.cancel', $generatedVideo),
            'retry_url' => route('app.video-generation.retry', $generatedVideo),
            'remaining_tokens' => $request->user()->fresh()->tokens,
        ]);
    }

    public function cancel(Request $request, GeneratedVideo $generatedVideo, VideoGenerationService $videos)
    {
        abort_unless((int) $generatedVideo->user_id === (int) $request->user()->id, 403);
        $generatedVideo = $videos->cancel($generatedVideo);
        return response()->json([
            'success' => true, 'status' => $generatedVideo->status,
            'message' => $generatedVideo->status === 'canceled'
                ? 'ساخت ویدیو لغو شد و اعتبار رزروشده بازگشت.'
                : 'درخواست لغو ثبت شد؛ نتیجه تحویل نمی‌شود و تسویه پس از اعلام هزینهٔ واقعی سرویس انجام خواهد شد.',
            'error_code' => $generatedVideo->error_code,
            'retryable' => false,
            'generation_id' => $generatedVideo->id,
            'credits_reserved' => (int) $generatedVideo->credits_reserved,
            'credits_settled' => (int) $generatedVideo->credits_settled,
            'credits_refunded' => (int) $generatedVideo->credits_refunded,
            'poll_url' => route('app.video-generation.status', $generatedVideo),
        ]);
    }

    public function retry(Request $request, GeneratedVideo $generatedVideo, VideoGenerationService $videos)
    {
        abort_unless((int) $generatedVideo->user_id === (int) $request->user()->id, 403);
        $generatedVideo = $videos->retry($generatedVideo);
        return response()->json([
            'success' => true, 'status' => $generatedVideo->status,
            'message' => 'درخواست دوباره وارد صف شد.', 'error_code' => null, 'retryable' => false,
            'generation_id' => $generatedVideo->id,
            'credits_reserved' => (int) $generatedVideo->credits_reserved,
            'credits_settled' => 0, 'credits_refunded' => 0,
            'poll_url' => route('app.video-generation.status', $generatedVideo),
        ], 202);
    }

    private function videoStatusMessage(GeneratedVideo $video): string
    {
        if ($video->cancel_requested_at && !in_array($video->status, ['canceled', 'needs_review'], true)) {
            return 'درخواست لغو ثبت شده و تسویه پس از اعلام وضعیت نهایی سرویس انجام می‌شود.';
        }

        return match ($video->status) {
            'submitting', 'queued' => 'درخواست در صف ساخت قرار دارد.',
            'processing' => 'مدل در حال ساخت ویدیو است.',
            'downloading' => 'خروجی آماده شده و در حال ذخیره‌سازی امن است.',
            'completed' => 'ویدیو با موفقیت آماده شد.',
            'canceled' => 'ساخت ویدیو لغو شد.',
            'needs_review' => $video->error_message ?: 'این درخواست برای بررسی ایمن متوقف شده است.',
            default => $video->error_message ?: 'ساخت ویدیو کامل نشد.',
        };
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

    private function prefilledGeneratedImage(Request $request, Product $videoProduct): ?GeneratedImage
    {
        $id = $request->integer('source_generated_image_id', $request->integer('source_generated_image'));
        if ($id < 1 || !$request->user()) return null;

        return GeneratedImage::query()
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->whereHas('product.relatedVideoProducts', fn ($query) => $query->whereKey($videoProduct->id)->wherePivot('is_active', true))
            ->first();
    }

    private function imageDataUri(string $path, ?string $mime = null): ?string
    {
        $disk = Storage::disk('public');
        $path = $this->storagePath($path);
        if (!$disk->exists($path)) return null;
        $mime = $mime ?: $disk->mimeType($path);
        if (!str_starts_with((string) $mime, 'image/')) return null;

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
    }

    private function storagePath(string $path): string
    {
        return ltrim($path, '/');
    }

    private function applyStudioModel(Product $product, Request $request): void
    {
        $modelId = trim((string) $request->input('studio_model', ''));
        $query = AiModel::query()
            ->where('is_active', true)
            ->where('output_modality', 'video')
            ->where('provider', 'openrouter')
            ->whereNotNull('capability_config')
            ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
            ->when($modelId !== '', fn ($builder) => $builder->where('openrouter_model_id', $modelId))
            ->when($request->filled('studio_provider') && $request->input('studio_provider') === 'openrouter', fn ($builder) => $builder->where('provider', 'openrouter'),
                fn ($builder) => $modelId === '' ? $builder->orderByRaw("CASE task_type WHEN 'text_to_video' THEN 0 WHEN 'image_to_video' THEN 1 WHEN 'video_to_video' THEN 2 ELSE 3 END")->orderByDesc('lab_priority') : $builder);
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
        $candidates = $candidates->filter(fn (string $provider): bool => $provider === 'openrouter');
        $candidates->forget((string) $model->openrouter_model_id);
        $product->primary_model = (string) $model->openrouter_model_id;
        $product->ai_provider = (string) $model->provider;
        $product->fallback_models = $candidates->keys()->values()->all();
        $product->fallback_model_providers = $candidates->values()->all();
    }
}
