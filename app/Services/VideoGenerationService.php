<?php

namespace App\Services;

use App\Contracts\AiAsyncImageProviderInterface;
use App\Jobs\PollStudioVideoGeneration;
use App\Jobs\SubmitStudioVideoGeneration;
use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class VideoGenerationService
{
    public function __construct(
        private readonly AiProviderRouter $providers,
        private readonly CreditWalletService $wallet,
        private readonly ProductBuildSchema $buildSchema,
        private readonly ProductPromptBuilder $promptBuilder,
        private readonly VideoModelSchemaService $modelSchemas,
        private readonly VideoProductConfigService $videoConfig,
        private readonly StudioCostService $studioCosts,
        private readonly AiProviderCredentials $credentials,
        private readonly ProductCreatorRewardService $creatorRewards,
    ) {}

    public function start(Product $product, User $user, array $options): GeneratedVideo
    {
        $rawKey = trim((string) ($options['idempotency_key'] ?? ''));
        if ($rawKey === '') {
            return $this->startOnce($product, $user, $options);
        }

        $resolvedKey = hash('sha256', $user->getKey() . '|' . $rawKey);
        return Cache::lock('studio-video:' . $resolvedKey, 30)->block(10, function () use ($product, $user, $options, $resolvedKey): GeneratedVideo {
            $existing = GeneratedVideo::query()
                ->where('user_id', $user->getKey())
                ->where('idempotency_key', $resolvedKey)
                ->first();
            if ($existing) {
                $existing->setAttribute('was_idempotent_duplicate', true);
                return $existing->loadMissing(['product', 'order']);
            }

            return $this->startOnce($product, $user, $options + ['resolved_idempotency_key' => $resolvedKey]);
        });
    }

    private function startOnce(Product $product, User $user, array $options): GeneratedVideo
    {
        if (!$product->isVideoProduct() || $product->status !== 'active') {
            throw ValidationException::withMessages(['product' => 'محصول ویدیویی انتخاب‌شده فعال نیست.']);
        }

        $idempotencyKey = (string) ($options['resolved_idempotency_key'] ?? '');
        $correlationId = (string) ($options['correlation_id'] ?? Str::uuid());

        $config = $product->videoConfiguration();
        $studioMode = (bool) ($options['studio_mode'] ?? false);
        $duration = (int) ($options['duration'] ?? $config['default_duration']);
        $aspectRatio = (string) ($options['aspect_ratio'] ?? $config['default_aspect_ratio']);
        $resolution = (string) ($options['resolution'] ?? $config['default_resolution']);
        $quality = (string) ($options['quality'] ?? 'standard');
        $allowedDurations = $studioMode ? range(1, 15) : array_map('intval', (array) $config['durations']);
        $allowedResolutions = $studioMode ? VideoProductConfigService::RESOLUTIONS : (array) $config['resolutions'];
        $motionCatalog = $studioMode
            ? collect($this->videoConfig->motionPresetCatalog())->map(fn (array $preset, string $key): array => ['key' => $key] + $preset)->all()
            : (array) $config['motion_presets'];
        if (!in_array($duration, $allowedDurations, true)) {
            throw ValidationException::withMessages(['video.duration' => 'مدت انتخاب‌شده برای این محصول فعال نیست.']);
        }
        $allowedAspectRatios = $studioMode ? VideoProductConfigService::STUDIO_ASPECT_RATIOS : (array) $config['aspect_ratios'];
        if (!$studioMode && !empty($config['preserve_source_aspect_ratio'])) {
            $allowedAspectRatios[] = 'source';
        }
        if (!in_array($aspectRatio, $allowedAspectRatios, true)) {
            throw ValidationException::withMessages(['video.aspect_ratio' => 'نسبت تصویر انتخاب‌شده معتبر نیست.']);
        }
        if (!in_array($resolution, $allowedResolutions, true)) {
            throw ValidationException::withMessages(['video.resolution' => 'کیفیت انتخاب‌شده معتبر نیست.']);
        }

        $faceMode = (string) ($config['face_profile_mode'] ?? 'disabled');
        $sourceImageDataList = array_values(array_filter(
            (array) ($options['source_image_data_list'] ?? []),
            fn ($value): bool => is_string($value) && trim($value) !== '',
        ));
        if ($sourceImageDataList === [] && !empty($options['source_image_data'])) {
            $sourceImageDataList = [(string) $options['source_image_data']];
        }
        $hasSourceImage = $sourceImageDataList !== [];

        if ($faceMode === 'required' && !$hasSourceImage) {
            throw ValidationException::withMessages(['source_image' => 'برای این محصول یک پروفایل چهره یا عکس جدید انتخاب کنید.']);
        }
        if ($config['workflow'] === 'image_to_video' && !$hasSourceImage) {
            throw ValidationException::withMessages(['source_image' => 'سناریوی عکس به ویدیو به یک تصویر ورودی نیاز دارد.']);
        }
        if ($aspectRatio === 'source') {
            $aspectRatio = $this->sourceAspectRatio($sourceImageDataList[0] ?? null);
            if ($aspectRatio === null) {
                throw ValidationException::withMessages(['video.aspect_ratio' => 'نسبت تصویر عکس ورودی خوانده نشد.']);
            }
            $options['preserve_source_aspect_ratio'] = true;
            $options['source_aspect_ratio'] = $aspectRatio;
        }
        $options['aspect_ratio'] = $aspectRatio;
        if ($config['workflow'] === 'video_to_video' && empty($options['source_video_url'])) {
            throw ValidationException::withMessages(['source_video' => 'سناریوی ویدیو به ویدیو به فایل ورودی نیاز دارد.']);
        }
        if (!in_array($quality, VideoProductConfigService::QUALITY_KEYS, true)) {
            throw ValidationException::withMessages(['video.quality' => 'سطح کیفیت انتخاب‌شده معتبر نیست.']);
        }

        $model = $this->resolveModel($product);
        $providerCredentials = $this->credentials->for($model->provider);
        if (blank($providerCredentials['api_key'] ?? null)) {
            throw ValidationException::withMessages([
                'studio_model' => sprintf('کلید دسترسی پروایدر %s تنظیم نشده است؛ این مدل فعلاً قابل اجرا نیست.', $model->provider),
            ]);
        }
        $supportedResolutions = (array) data_get($this->modelSchemas->properties($model), 'resolution.enum', []);
        if (!$studioMode && $supportedResolutions !== [] && !in_array($resolution, $supportedResolutions, true)) {
            throw ValidationException::withMessages(['video.resolution' => 'این مدل کیفیت انتخاب‌شده را پشتیبانی نمی‌کند؛ یک کیفیت سازگار انتخاب کنید.']);
        }
        $capabilityChecks = [
            [
                'input' => 'video.duration',
                'values' => (array) data_get($model->capability_config, 'supported_durations', []),
                'value' => (string) $duration,
                'normalize' => static fn (mixed $value): string => (string) ((int) $value),
                'message' => 'مدل انتخاب‌شده این زمان ویدیو را پشتیبانی نمی‌کند؛ یک زمان سازگار انتخاب کنید.',
            ],
            [
                'input' => 'video.resolution',
                'values' => (array) data_get($model->capability_config, 'supported_resolutions', []),
                'value' => $resolution,
                'normalize' => static fn (mixed $value): string => match (strtolower(trim((string) $value))) {
                    '2160', '2160p', '4k' => '4k',
                    '1440', '1440p', '2k' => '2k',
                    default => strtolower(trim((string) $value)),
                },
                'message' => 'مدل انتخاب‌شده این کیفیت خروجی را پشتیبانی نمی‌کند؛ کیفیت دیگری انتخاب کنید.',
            ],
            [
                'input' => 'video.aspect_ratio',
                'values' => (array) data_get($model->capability_config, 'supported_aspect_ratios', []),
                'value' => $aspectRatio,
                'normalize' => static fn (mixed $value): string => strtolower(trim((string) $value)),
                'message' => 'مدل انتخاب‌شده این نسبت تصویر را پشتیبانی نمی‌کند؛ نسبت دیگری انتخاب کنید.',
            ],
        ];
        foreach ($capabilityChecks as $check) {
            if ($check['values'] === []) continue;
            $normalize = $check['normalize'];
            $allowed = array_map($normalize, $check['values']);
            if (!in_array($normalize($check['value']), $allowed, true)) {
                throw ValidationException::withMessages([$check['input'] => $check['message']]);
            }
        }
        $fieldValues = (array) ($options['fields'] ?? []);
        $promptAllowed = (bool) ($config['customer_prompt_allowed'] ?? (($config['prompt_mode'] ?? 'custom') === 'custom'));
        $fieldValues['prompt'] = $promptAllowed
            ? trim((string) ($options['prompt'] ?? $fieldValues['prompt'] ?? ''))
            : '';
        $identityRequested = $faceMode !== 'disabled' && $hasSourceImage;
        $prompt = $this->promptBuilder->build($product, $fieldValues, $identityRequested);
        $motion = collect($motionCatalog)->firstWhere('key', (string) ($options['motion_preset'] ?? ''));
        if ($motion && !empty($motion['prompt'])) $prompt .= "\n\nCamera direction: " . $motion['prompt'];
        if (!empty($config['multi_shot_enabled']) && !empty($config['timeline'])) {
            $timelinePrompt = collect((array) $config['timeline'])->map(function (array $shot): string {
                $title = trim((string) ($shot['title'] ?? 'Shot'));
                $duration = (int) ($shot['duration'] ?? 1);
                $shotPrompt = trim((string) ($shot['prompt'] ?? ''));
                return "{$title} ({$duration}s)" . ($shotPrompt !== '' ? ": {$shotPrompt}" : '');
            })->implode("\n");
            if ($timelinePrompt !== '') $prompt .= "\n\nShot plan:\n" . $timelinePrompt;
        }
        if ($promptAllowed && !empty($options['prompt']) && !str_contains($prompt, trim((string) $options['prompt']))) {
            $prompt .= "\n\nUser direction: " . trim((string) $options['prompt']);
        }
        $negativePrompt = trim((string) ($options['negative_prompt'] ?? ''));
        if ($negativePrompt !== '') $prompt .= "\n\nAvoid: {$negativePrompt}";

        $featureCost = $this->buildSchema->additionalCredit($product, $fieldValues);
        if ($studioMode) {
            $defaultDuration = max(1, (int) ($config['default_duration'] ?? 4));
            $baseCost = $this->videoConfig->creditCost($product, $defaultDuration);
            $qualityCosts = (array) data_get($config, 'quality_costs', []);
            $creditCost = max(0, ($baseCost * (int) ceil($duration / $defaultDuration))
                + (int) ($qualityCosts[$resolution] ?? 0)
                + ((bool) ($options['generate_audio'] ?? false) ? 3 : 0)
                + ($identityRequested ? 2 : 0)
                + $featureCost);
        } else {
            $creditCost = max(0, $this->videoConfig->creditCost($product, $duration, $resolution, (bool) ($options['generate_audio'] ?? false), $identityRequested, $quality) + $featureCost);
        }
        $studioQuote = $studioMode
            ? $this->studioCosts->quote($product, [
                'media_type' => 'video',
                'workflow' => (string) ($options['workflow'] ?? $config['workflow'] ?? 'text_to_video'),
                'resolution' => $resolution,
                'quality' => $quality,
                'source_aspect_ratio' => $options['source_aspect_ratio'] ?? null,
                'aspect_ratio' => $aspectRatio,
                'duration' => $duration,
                'count' => 1,
            ], $model)
            : null;
        if ($studioQuote && !$studioQuote['cost_known']) {
            throw ValidationException::withMessages(['video.resolution' => 'قیمت مدل انتخاب‌شده هنوز در کاتالوگ پروایدر ثبت نشده است.']);
        }
        if ($studioQuote && $studioQuote['credits_per_output'] !== null) {
            $creditCost = (int) $studioQuote['credits_per_output'] + $featureCost + ($identityRequested ? 2 : 0);
        }
        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'plan_id' => $user->plan_id,
            'plan_name' => $user->plan?->name ?: 'رایگان',
            'model_tier_key' => $user->plan?->model_tier_key ?: 'free',
            'model_tier_name' => $user->plan?->name ?: 'رایگان',
            'status' => 'processing',
            'payment_status' => 'paid',
            'processing_status' => 'queued',
            'original_credits' => $creditCost,
            'final_credits' => $creditCost,
            'ai_model' => $model->openrouter_model_id,
            'ai_provider' => $model->provider,
            'attempts' => 1,
            'input_payload' => [
                'media_type' => 'video',
                'fields' => $fieldValues,
                'prompt' => $fieldValues['prompt'] ?: null,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'source_aspect_ratio' => $options['source_aspect_ratio'] ?? null,
                'resolution' => $resolution,
                'quality' => $quality,
                'product_family' => $config['product_family'] ?? null,
                'music_mode' => $config['music_mode'] ?? 'disabled',
                'timeline' => (array) ($config['timeline'] ?? []),
                'motion_preset' => $options['motion_preset'] ?? null,
                'project_name' => $options['project_name'] ?? null,
                'face_profile_id' => $options['face_profile_id'] ?? null,
                'source_upload_path' => $options['source_upload_path'] ?? null,
                'source_upload_paths' => array_values(array_filter((array) ($options['source_upload_paths'] ?? []))),
                'source_video_path' => $options['source_video_path'] ?? null,
                'source_video_url' => $options['source_video_url'] ?? null,
                'negative_prompt' => $negativePrompt ?: null,
                'workflow' => $options['workflow'] ?? $config['workflow'],
                'reference_mode' => $options['reference_mode'] ?? null,
                'source_image_data' => $hasSourceImage && strlen($sourceImageDataList[0]) <= 600000 ? $sourceImageDataList[0] : null,
            ],
            'source' => 'app',
            'paid_at' => now(),
            'processing_started_at' => now(),
        ]);
        $order->recordEvent('created', 'سفارش ویدیویی ثبت شد', 'درخواست برای صف تولید ویدیو آماده شد.');

        // متن، عکس‌ها و ویدیوی ورودی همین آزمایش قبل از ارسال به پرووایدر
        // در گالری خصوصی کاربر نگهداری می‌شوند.
        $gallery = app(UserGalleryService::class);
        $galleryPrompt = trim((string) ($options['prompt'] ?? $fieldValues['prompt'] ?? ''));
        if ($galleryPrompt !== '') {
            $gallery->captureText($user, $galleryPrompt, $order->id, [
                'order_id' => $order->id,
                'source' => 'app.create.studio',
            ]);
        }
        foreach (array_values(array_filter((array) ($options['source_upload_paths'] ?? []))) as $path) {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                continue;
            }
            $gallery->capture(
                $user,
                'input_image',
                $order->id,
                $path,
                'public',
                (int) $disk->size($path),
                $disk->mimeType($path),
                ['order_id' => $order->id, 'source' => 'app.create.studio'],
            );
        }
        $sourceVideoPath = (string) ($options['source_video_path'] ?? '');
        $publicDisk = Storage::disk('public');
        if ($sourceVideoPath !== '' && $publicDisk->exists($sourceVideoPath)) {
            $gallery->capture(
                $user,
                'input_video',
                $order->id,
                $sourceVideoPath,
                'public',
                (int) $publicDisk->size($sourceVideoPath),
                $publicDisk->mimeType($sourceVideoPath),
                ['order_id' => $order->id, 'source' => 'app.create.studio'],
            );
        }

        $reservation = ['total' => 0, 'promotional' => 0, 'paid' => 0, 'ledger_key' => null];
        try {
            if ($creditCost > 0) $reservation = $this->wallet->reserve($user, $creditCost, $order);
        } catch (ValidationException $exception) {
            $order->update(['status' => 'review', 'payment_status' => 'failed', 'processing_status' => 'stopped', 'error_message' => 'اعتبار کافی نیست.']);
            throw $exception;
        }

        $generation = GeneratedVideo::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'status' => 'submitting',
            'idempotency_key' => $idempotencyKey ?: null,
            'correlation_id' => $correlationId,
            'user_prompt' => $prompt,
            'input_payload' => $order->input_payload,
            'credit_reservation' => $reservation,
            'credits_reserved' => (int) ($reservation['total'] ?? 0),
            'duration_seconds' => $duration,
        ]);

        SubmitStudioVideoGeneration::dispatchAfterResponse($generation->id);

        return $generation->fresh(['product', 'order']);
    }

    public function submitQueued(int $generationId): void
    {
        $generation = GeneratedVideo::query()->with(['product', 'order'])->findOrFail($generationId);
        if ($generation->external_request_id || in_array($generation->status, ['queued', 'processing', 'downloading', 'completed', 'failed', 'canceled'], true)) {
            return;
        }

        $payload = (array) $generation->input_payload;
        $options = $payload + [
            'duration' => (int) $generation->duration_seconds,
            'aspect_ratio' => (string) ($payload['aspect_ratio'] ?? '16:9'),
            'resolution' => (string) ($payload['resolution'] ?? '720p'),
            'source_upload_paths' => (array) ($payload['source_upload_paths'] ?? []),
            'source_video_url' => $payload['source_video_url'] ?? null,
            'reference_mode' => $payload['reference_mode'] ?? null,
        ];

        $lastError = null;
        foreach ($this->candidateModels($generation->product) as $candidate) {
            try {
                $this->submitCandidate($generation, $generation->order, $candidate, $generation->product, (string) $generation->user_prompt, $options);
                $generation->update(['submitted_at' => now(), 'next_poll_at' => now()->addSeconds(15)]);
                if (config('queue.default') !== 'sync') {
                    PollStudioVideoGeneration::dispatch($generation->id)->delay(now()->addSeconds(15));
                }
                return;
            } catch (Throwable $error) {
                $lastError = $error;
                $this->markAttempted($generation, $candidate);
                Log::warning('Video submission failed', [
                    'generated_video_id' => $generation->id,
                    'correlation_id' => $generation->correlation_id,
                    'provider' => $candidate->provider,
                    'model' => $candidate->openrouter_model_id,
                    'retryable' => $this->isRetryableProviderError($error->getMessage()),
                ]);
                if (!$this->isRetryableProviderError($error->getMessage())) {
                    break;
                }
            }
        }

        throw $lastError ?: new RuntimeException('هیچ مدل ویدیویی آمادهٔ ارسال نیست.');
    }

    public function failQueuedSubmission(int $generationId, Throwable $exception): void
    {
        $generation = GeneratedVideo::query()->find($generationId);
        if (!$generation || $generation->external_request_id || in_array($generation->status, ['completed', 'failed', 'canceled'], true)) {
            return;
        }
        $retryable = $this->isRetryableProviderError($exception->getMessage());
        $this->failAndRestore($generation, $this->publicErrorMessage($exception->getMessage()), 'failed', null, $retryable ? 'PROVIDER_UNAVAILABLE' : 'PROVIDER_REJECTED', $retryable);
    }

    public function pollQueued(int $generationId): void
    {
        $generation = GeneratedVideo::query()->find($generationId);
        if (!$generation || in_array($generation->status, ['completed', 'failed', 'canceled', 'needs_review'], true)) return;

        $generation = $this->refresh($generation);
        if (!in_array($generation->status, ['completed', 'failed', 'canceled', 'needs_review'], true)) {
            $generation->update(['next_poll_at' => now()->addSeconds(30)]);
            if (config('queue.default') !== 'sync') {
                PollStudioVideoGeneration::dispatch($generation->id)->delay(now()->addSeconds(30));
            }
        }
    }

    public function failQueuedPolling(int $generationId, Throwable $exception): void
    {
        $generation = GeneratedVideo::query()->find($generationId);
        if (!$generation || in_array($generation->status, ['completed', 'failed', 'canceled', 'needs_review'], true)) return;

        Log::warning('Video status polling temporarily exhausted', [
            'generated_video_id' => $generation->id,
            'correlation_id' => $generation->correlation_id,
            'exception' => $exception::class,
        ]);
        if ($generation->submitted_at?->isBefore(now()->subHours(6))) {
            $this->holdForCostReview($generation, 'وضعیت نهایی سرویس در زمان مقرر دریافت نشد؛ درخواست برای بررسی امن نگه داشته شد.');
            return;
        }

        $generation->update([
            'status' => 'processing',
            'error_code' => 'STATUS_TEMPORARILY_UNAVAILABLE',
            'error_message' => 'دریافت وضعیت سرویس موقتاً ممکن نیست؛ پیگیری خودکار ادامه دارد.',
            'retryable' => false,
            'next_poll_at' => now()->addMinutes(2),
        ]);
        PollStudioVideoGeneration::dispatch($generation->id)->delay(now()->addMinutes(2));
    }

    public function cancel(GeneratedVideo $generation): GeneratedVideo
    {
        if (in_array($generation->status, ['completed', 'failed', 'canceled', 'needs_review'], true)) return $generation;

        $generation->update(['cancel_requested_at' => now()]);
        if (!$generation->external_request_id) {
            $this->failAndRestore($generation, 'ساخت ویدیو با درخواست شما لغو شد.', 'canceled', null, 'USER_CANCELED', false);
            return $generation->fresh();
        }

        // OpenRouter در قرارداد رسمی فعلی endpoint لغو ندارد. درخواست تحویل
        // متوقف می‌شود، اما polling تا پاسخ نهایی ادامه دارد تا هزینهٔ واقعی
        // به‌درستی تسویه شود و رزرو به‌اشتباه کامل بازنگردد.
        $generation->update([
            'status' => 'processing',
            'error_code' => 'CANCEL_PENDING',
            'error_message' => 'درخواست لغو ثبت شد؛ تسویه پس از اعلام وضعیت نهایی سرویس انجام می‌شود.',
            'retryable' => false,
            'next_poll_at' => now(),
        ]);
        PollStudioVideoGeneration::dispatchAfterResponse($generation->id);

        return $generation->fresh();
    }

    public function retry(GeneratedVideo $generation): GeneratedVideo
    {
        if ($generation->status !== 'failed' || !$generation->retryable) {
            throw ValidationException::withMessages(['generation' => 'این درخواست قابل تلاش مجدد نیست.']);
        }

        $reservation = (array) ($generation->credit_reservation ?? []);
        if (($generation->credits_restored_at || $generation->credits_settled_at) && (int) ($generation->credits_reserved ?: 0) > 0) {
            $reservation = $this->wallet->reserve(
                $generation->user,
                (int) $generation->credits_reserved,
                $generation->order,
            );
        }
        $payload = (array) $generation->input_payload;
        unset($payload['attempted_models'], $payload['active_model'], $payload['active_provider']);
        $generation->update([
            'status' => 'submitting',
            'external_request_id' => null,
            'ai_provider_request_id' => null,
            'input_payload' => $payload,
            'credit_reservation' => $reservation,
            'credits_settled' => 0,
            'credits_refunded' => 0,
            'credits_settled_at' => null,
            'credits_restored_at' => null,
            'error_message' => null,
            'error_code' => null,
            'retryable' => false,
            'retry_count' => (int) $generation->retry_count + 1,
            'submitted_at' => null,
            'next_poll_at' => null,
            'cancel_requested_at' => null,
            'completed_at' => null,
        ]);
        $generation->order?->update([
            'status' => 'processing',
            'processing_status' => 'queued',
            'refunded_credits' => 0,
            'error_message' => null,
            'refunded_at' => null,
        ]);
        SubmitStudioVideoGeneration::dispatchAfterResponse($generation->id);

        return $generation->fresh();
    }

    public function quote(Product $product, User $user, array $options): array
    {
        $config = $product->videoConfiguration();
        $duration = (int) ($options['duration'] ?? $config['default_duration']);
        $resolution = (string) ($options['resolution'] ?? $config['default_resolution']);
        $quality = (string) ($options['quality'] ?? 'standard');
        $identity = !empty($options['source_image_data']) || !empty($options['face_profile_id']);
        $base = $this->videoConfig->creditCost($product, $duration, $resolution, (bool) ($options['generate_audio'] ?? false), $identity, $quality);
        $features = $this->buildSchema->additionalCredit($product, (array) ($options['fields'] ?? []));
        $total = max(0, $base + $features);
        return ['credits' => $total, 'balance' => (int) $user->tokens, 'can_afford' => (int) $user->tokens >= $total, 'breakdown' => ['مدت ویدیو' => $this->videoConfig->creditCost($product, $duration), 'کیفیت/امکانات' => $total - $features - $this->videoConfig->creditCost($product, $duration), 'ویژگی‌ها' => $features]];
    }

    private function sourceAspectRatio(?string $dataUri): ?string
    {
        if (!$dataUri || !str_contains($dataUri, 'base64,')) return null;
        $binary = base64_decode((string) Str::after($dataUri, 'base64,'), true);
        if ($binary === false) return null;
        $size = @getimagesizefromstring($binary);
        if (!$size || empty($size[0]) || empty($size[1])) return null;
        $gcd = function (int $a, int $b) use (&$gcd): int { return $b === 0 ? $a : $gcd($b, $a % $b); };
        $divider = $gcd((int) $size[0], (int) $size[1]);
        return ((int) $size[0] / $divider) . ':' . ((int) $size[1] / $divider);
    }

    public function refresh(GeneratedVideo $generation): GeneratedVideo
    {
        if (in_array($generation->status, ['completed', 'failed', 'canceled'], true)) return $generation;

        $request = $generation->providerRequest;
        if (!$request && $generation->external_request_id) {
            $request = AiProviderRequest::query()->where('external_request_id', $generation->external_request_id)->first();
        }
        if (!$request || !$request->aiModel) return $generation;

        if (in_array($request->status, ['completed', 'failed', 'canceled'], true)) {
            return $this->applyNormalized($generation, [
                'status' => $request->status,
                'external_request_id' => $request->external_request_id,
                'output_urls' => $request->output_urls ?: [],
                'actual_cost_usd' => $request->actual_cost_usd,
                'estimated_cost_usd' => $request->estimated_cost_usd,
                'error_message' => $request->error_message,
            ]);
        }

        $normalized = $this->asyncProvider($request->provider)
            ->getGenerationStatus($request->aiModel, $request->external_request_id);
        $request->update([
            'status' => $normalized['status'],
            'output_urls' => $normalized['output_urls'] ?? [],
            'raw_response' => $normalized['provider_metadata'] ?? $request->raw_response,
            'actual_cost_usd' => $normalized['actual_cost_usd'] ?? $request->actual_cost_usd,
            'estimated_cost_usd' => $normalized['estimated_cost_usd'] ?? $request->estimated_cost_usd,
            'error_code' => $normalized['error_code'] ?? null,
            'error_message' => $normalized['error_message'] ?? null,
            'completed_at' => in_array($normalized['status'], ['completed', 'failed', 'canceled'], true) ? now() : null,
        ]);

        return $this->applyNormalized($generation, $normalized);
    }

    public function syncFromProvider(array $normalized): void
    {
        $requestId = (string) ($normalized['external_request_id'] ?? '');
        if ($requestId === '') return;
        $request = AiProviderRequest::query()
            ->where('external_request_id', $requestId)
            ->first();
        if (!$request) return;

        $updates = [
            'status' => (string) ($normalized['status'] ?? 'processing'),
            'output_urls' => $normalized['output_urls'] ?? [],
            'raw_response' => $normalized['provider_metadata'] ?? [],
            'error_code' => $normalized['error_code'] ?? null,
            'error_message' => $normalized['error_message'] ?? null,
            'webhook_received_at' => now(),
            'completed_at' => in_array((string) ($normalized['status'] ?? ''), ['completed', 'failed', 'canceled'], true) ? now() : null,
        ];
        if (is_numeric($normalized['actual_cost_usd'] ?? null)) {
            $updates['actual_cost_usd'] = (float) $normalized['actual_cost_usd'];
        }
        if (is_numeric($normalized['estimated_cost_usd'] ?? null)) {
            $updates['estimated_cost_usd'] = (float) $normalized['estimated_cost_usd'];
        }
        $request->update($updates);
        $generation = GeneratedVideo::query()->where('external_request_id', $requestId)->first();
        if ($generation) $this->applyNormalized($generation, $normalized);
    }

    private function applyNormalized(GeneratedVideo $generation, array $normalized): GeneratedVideo
    {
        $status = (string) ($normalized['status'] ?? 'processing');
        if (in_array($status, ['queued', 'processing'], true)) {
            $generation->update(['status' => $status, 'next_poll_at' => now()->addSeconds(30)]);
            $generation->order?->update(['processing_status' => $status === 'queued' ? 'queued' : 'processing']);
            return $generation->fresh();
        }
        if (in_array($status, ['failed', 'canceled'], true)) {
            $providerMessage = (string) ($normalized['error_message'] ?? 'خطای سرویس‌دهنده');
            if ($status === 'failed') {
                Log::warning('Video provider returned a terminal failure', [
                    'generated_video_id' => $generation->id,
                    'correlation_id' => $generation->correlation_id,
                    'provider_request_id' => $generation->external_request_id,
                    'provider_error_code' => $normalized['error_code'] ?? null,
                    'provider_error_message' => Str::limit($providerMessage, 1000, ''),
                    'provider_metadata' => $normalized['provider_metadata'] ?? null,
                ]);
            }
            if (!$generation->cancel_requested_at && $status === 'failed' && $this->isRetryableProviderError($providerMessage) && $this->retryFallback($generation, $providerMessage)) {
                return $generation->fresh();
            }
            $actualCost = $this->actualProviderCostForOrder($generation->order_id);
            if ($generation->external_request_id && $actualCost === null) {
                return $this->holdForCostReview($generation, $generation->cancel_requested_at
                    ? 'درخواست لغو شد، اما هزینهٔ واقعی سرویس هنوز مشخص نشده است.'
                    : 'درخواست در سرویس پایان یافت، اما هزینهٔ واقعی آن هنوز مشخص نشده است.');
            }
            $retryable = !$generation->cancel_requested_at
                && $this->isRetryableProviderError($providerMessage)
                && ($actualCost === null || $actualCost <= 0);
            $this->failAndRestore(
                $generation,
                $generation->cancel_requested_at ? 'ساخت ویدیو با درخواست شما لغو شد.' : $this->publicErrorMessage($providerMessage),
                $generation->cancel_requested_at ? 'canceled' : $status,
                $actualCost,
                $generation->cancel_requested_at ? 'USER_CANCELED' : $this->errorCodeFor($providerMessage),
                $retryable,
            );
            return $generation->fresh();
        }
        if ($status !== 'completed') return $generation;
        if ($generation->status === 'completed' && $generation->credits_settled_at) {
            try {
                $this->creatorRewards->rewardForVideo($generation, (array) $generation->credit_reservation);
            } catch (\Throwable $exception) {
                report($exception);
            }
            return $generation;
        }

        $actualCost = $this->actualProviderCostForOrder($generation->order_id);
        if ($generation->cancel_requested_at) {
            if ($actualCost === null) {
                return $this->holdForCostReview($generation, 'درخواست لغو شد، اما هزینهٔ واقعی سرویس هنوز مشخص نشده است.');
            }
            $this->failAndRestore($generation, 'ساخت ویدیو با درخواست شما لغو شد.', 'canceled', $actualCost, 'USER_CANCELED', false);
            return $generation->fresh();
        }

        $claimed = GeneratedVideo::query()
            ->whereKey($generation->id)
            ->whereNull('credits_settled_at')
            ->whereNull('credits_restored_at')
            ->whereNull('cancel_requested_at')
            ->whereNotIn('status', ['failed', 'canceled', 'needs_review'])
            ->where(function ($query): void {
                $query->where('status', '<>', 'downloading')
                    ->orWhere('updated_at', '<=', now()->subMinutes(5));
            })
            ->update(['status' => 'downloading', 'updated_at' => now()]);
        if ($claimed !== 1) return $generation->fresh();
        $generation->refresh();
        $output = collect((array) ($normalized['output_urls'] ?? []))->first(fn ($item): bool => is_array($item) && filter_var($item['url'] ?? null, FILTER_VALIDATE_URL));
        if (!$output) throw new RuntimeException('سرویس‌دهنده ویدیو را تکمیل کرد اما آدرس فایل خروجی موجود نیست.');
        try {
            $stored = $this->downloadOutput((string) $output['url'], $generation->order?->ai_provider ?: 'openrouter');
        } catch (\Throwable $error) {
            $generation->update(['status' => 'processing', 'next_poll_at' => now()->addSeconds(30)]);
            throw $error;
        }
        if ($actualCost === null) {
            $generation->update([
                'status' => 'needs_review',
                'video_path' => $stored['path'],
                'video_url' => (string) $output['url'],
                'mime_type' => $stored['mime'],
                'size' => $stored['size'],
                'error_code' => 'ACTUAL_COST_MISSING',
                'error_message' => 'هزینهٔ واقعی سرویس هنوز مشخص نشده است؛ نتیجه پس از بررسی مالی آزاد می‌شود.',
            ]);
            $generation->order?->update(['status' => 'review', 'processing_status' => 'needs_review']);
            return $generation->fresh();
        }

        $finalCredits = $this->studioCosts->creditsForActualCost($actualCost, 'video');
        $reservation = (array) ($generation->credit_reservation ?? []);
        try {
            if (!$generation->credits_settled_at && $generation->user) {
                $reservation = $this->wallet->settle($generation->user, $reservation, $finalCredits);
            }
        } catch (ValidationException $exception) {
            $generation->update([
                'status' => 'needs_review',
                'video_path' => $stored['path'],
                'video_url' => (string) $output['url'],
                'mime_type' => $stored['mime'],
                'size' => $stored['size'],
                'actual_cost_usd' => $actualCost,
                'cost' => $actualCost,
                'error_code' => 'CREDIT_SETTLEMENT_REQUIRED',
                'error_message' => 'هزینهٔ واقعی بیشتر از مبلغ رزروشده است و برای تحویل نتیجه به بررسی اعتبار نیاز دارد.',
            ]);
            $generation->order?->update(['status' => 'review', 'processing_status' => 'needs_review']);
            return $generation->fresh();
        }
        $reservedCredits = (int) ($generation->credits_reserved ?: ($generation->credit_reservation['total'] ?? 0));
        $refundedCredits = max(0, $reservedCredits - $finalCredits);
        $generation->update([
            'status' => 'completed',
            'video_path' => $stored['path'],
            'video_url' => (string) $output['url'],
            'mime_type' => $stored['mime'],
            'size' => $stored['size'],
            'cost' => $actualCost,
            'actual_cost_usd' => $actualCost,
            'credit_reservation' => $reservation,
            'credits_settled' => $finalCredits,
            'credits_refunded' => $refundedCredits,
            'credits_settled_at' => $generation->credits_settled_at ?: now(),
            'completed_at' => now(),
            'error_message' => null,
            'error_code' => null,
            'retryable' => false,
        ]);
        $generation->order?->update([
            'status' => 'completed',
            'processing_status' => 'completed',
            'promotional_credits_used' => (int) ($reservation['promotional'] ?? 0),
            'paid_credits_used' => (int) ($reservation['paid'] ?? 0),
            'refunded_credits' => $refundedCredits,
            'output_payload' => ['media_type' => 'video', 'path' => $stored['path'], 'provider_url' => $output['url']],
            'completed_at' => now(),
            'processing_duration_ms' => $generation->order?->processing_started_at?->diffInMilliseconds(now()),
        ]);
        $generation->order?->recordEvent('completed', 'ویدیو با موفقیت ساخته شد');

        try {
            $this->creatorRewards->rewardForVideo($generation->fresh(['product', 'user', 'order']), $reservation);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $generation->fresh();
    }

    private function failAndRestore(
        GeneratedVideo $generation,
        string $message,
        string $status = 'failed',
        ?float $actualCostUsd = null,
        ?string $errorCode = null,
        bool $retryable = false,
    ): void
    {
        Cache::lock('studio-video-settlement:' . $generation->id, 30)->block(10, function () use ($generation, $message, $status, $actualCostUsd, $errorCode, $retryable): void {
            $generation = GeneratedVideo::query()->with(['user', 'order'])->findOrFail($generation->id);
            if ($generation->status === 'downloading' && $generation->updated_at?->isAfter(now()->subMinutes(5))) {
                return;
            }
            $reservation = (array) ($generation->credit_reservation ?? []);
            $creditsReturned = 0;
            $settledCredits = 0;
            if (!$generation->credits_restored_at && !$generation->credits_settled_at && $generation->user) {
                if ($actualCostUsd !== null) {
                    $settledCredits = $this->studioCosts->creditsForActualCost($actualCostUsd, 'video');
                    try {
                        $reservation = $this->wallet->settle($generation->user, $reservation, $settledCredits);
                        $creditsReturned = max(0, (int) ($generation->credits_reserved ?: 0) - $settledCredits);
                    } catch (ValidationException) {
                        $generation->update([
                            'status' => 'needs_review',
                            'actual_cost_usd' => $actualCostUsd,
                            'cost' => $actualCostUsd,
                            'error_code' => 'CREDIT_SETTLEMENT_REQUIRED',
                            'error_message' => 'هزینهٔ واقعی درخواست ناموفق برای تسویه به بررسی اعتبار نیاز دارد.',
                        ]);
                        $generation->order?->update(['status' => 'review', 'processing_status' => 'needs_review']);
                        return;
                    }
                } elseif ((int) ($reservation['total'] ?? 0) > 0) {
                    $creditsReturned = (int) ($reservation['promotional'] ?? 0) + (int) ($reservation['paid'] ?? 0);
                    $this->wallet->restore(
                        $generation->user,
                        (int) ($reservation['promotional'] ?? 0),
                        (int) ($reservation['paid'] ?? 0),
                        false,
                        $reservation['ledger_key'] ?? null,
                        (array) ($reservation['grant_allocations'] ?? []),
                    );
                }
            }
            $generation->update([
                'status' => $status,
                'error_message' => $message,
                'error_code' => $errorCode,
                'retryable' => $retryable,
                'actual_cost_usd' => $actualCostUsd,
                'cost' => $actualCostUsd ?? $generation->cost,
                'credit_reservation' => $reservation,
                'credits_settled' => $settledCredits,
                'credits_refunded' => $creditsReturned,
                'credits_settled_at' => $actualCostUsd !== null ? ($generation->credits_settled_at ?: now()) : $generation->credits_settled_at,
                'credits_restored_at' => $actualCostUsd === null ? ($generation->credits_restored_at ?: now()) : $generation->credits_restored_at,
                'completed_at' => now(),
            ]);
            $generation->order?->update([
                'status' => 'review',
                'processing_status' => $status === 'canceled' ? 'stopped' : 'failed',
                'error_message' => $message,
                'refunded_credits' => $creditsReturned,
                'promotional_credits_refunded' => $creditsReturned > 0 ? (int) ($reservation['promotional'] ?? 0) : 0,
                'paid_credits_refunded' => $creditsReturned > 0 ? (int) ($reservation['paid'] ?? 0) : 0,
                'refunded_at' => $creditsReturned > 0 ? now() : null,
                'processing_duration_ms' => $generation->order?->processing_started_at?->diffInMilliseconds(now()),
            ]);
            $generation->order?->recordEvent('failed', 'ساخت ویدیو کامل نشد', $message);
            Log::warning('Video generation failed', ['generated_video_id' => $generation->id, 'message' => $message]);
        });
    }

    private function candidateModels(Product $product): array
    {
        $ids = array_values(array_unique(array_filter(array_merge([(string) $product->primary_model], (array) $product->fallback_models))));
        $providers = array_values(array_merge([(string) $product->ai_provider], (array) $product->fallback_model_providers));
        $models = [];
        foreach ($ids as $index => $id) {
            $model = AiModel::query()->where('is_active', true)->where('output_modality', 'video')
                ->whereNotNull('capability_config')
                ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
                ->where('openrouter_model_id', $id)->where('provider', $providers[$index] ?? null)->first();
            if ($model && filled($this->credentials->for($model->provider)['api_key'] ?? null)) $models[] = $model;
        }
        return $models;
    }

    private function submitCandidate(GeneratedVideo $generation, Order $order, AiModel $model, Product $product, string $prompt, array $options): void
    {
        $input = $this->buildProviderInput($model, $product, $prompt, $options);
        if ($model->provider === 'openrouter') {
            $input['_idempotency_key'] = hash('sha256', implode('|', [
                'studio-video',
                $generation->id,
                $model->provider,
                $model->openrouter_model_id,
                $generation->retry_count,
            ]));
        }
        $submitted = $this->asyncProvider($model->provider)->submitGeneration($model, $prompt, [
            'input' => $input, 'order_id' => $order->id, 'n' => 1,
            'duration' => $options['duration'], 'resolution' => $options['resolution'],
            'workflow' => (string) ($options['workflow'] ?? 'text_to_video'),
        ]);
        $requestId = (string) $submitted['external_request_id'];
        $providerRequest = AiProviderRequest::query()->where('provider', $model->provider)->where('external_request_id', $requestId)->first();
        $attempted = (array) data_get($generation->input_payload, 'attempted_models', []);
        $attempted[] = ['provider' => $model->provider, 'model' => $model->openrouter_model_id];
        $generation->update(['status' => 'queued', 'external_request_id' => $requestId, 'ai_provider_request_id' => $providerRequest?->id, 'input_payload' => array_merge((array) $generation->input_payload, ['attempted_models' => $attempted, 'active_model' => $model->openrouter_model_id, 'active_provider' => $model->provider])]);
        $order->update(['processing_status' => 'queued', 'ai_model' => $model->openrouter_model_id, 'ai_provider' => $model->provider, 'attempts' => max(1, count($attempted))]);
        $order->recordEvent('queued', 'ویدیو وارد صف شد', 'مدل: ' . $model->name . ' · شناسه درخواست: ' . $requestId);
    }

    private function markAttempted(GeneratedVideo $generation, AiModel $model): void
    {
        $payload = (array) $generation->input_payload;
        $attempted = (array) ($payload['attempted_models'] ?? []);
        $attempted[] = ['provider' => $model->provider, 'model' => $model->openrouter_model_id];
        $generation->update(['input_payload' => array_merge($payload, ['attempted_models' => $attempted])]);
    }

    private function retryFallback(GeneratedVideo $generation, string $reason): bool
    {
        if (!$this->isRetryableProviderError($reason)) return false;
        $product = $generation->product;
        $payload = (array) $generation->input_payload;
        $attempted = collect((array) ($payload['attempted_models'] ?? []))->map(fn ($row) => ($row['provider'] ?? '') . '|' . ($row['model'] ?? ''))->all();
        foreach ($this->candidateModels($product) as $candidate) {
            if (in_array($candidate->provider . '|' . $candidate->openrouter_model_id, $attempted, true)) continue;
            try {
                $this->submitCandidate($generation, $generation->order, $candidate, $product, (string) $generation->user_prompt, $payload + ['duration' => $generation->duration_seconds, 'aspect_ratio' => data_get($payload, 'aspect_ratio', $product->videoConfiguration()['default_aspect_ratio']), 'resolution' => data_get($payload, 'resolution', $product->videoConfiguration()['default_resolution'])]);
                $generation->order?->recordEvent('fallback', 'مدل جایگزین فعال شد', $reason);
                return true;
            } catch (\Throwable $error) {
                $this->markAttempted($generation, $candidate);
                $reason = $error->getMessage();
                if (!$this->isRetryableProviderError($reason)) return false;
            }
        }
        return false;
    }

    private function isSafetyRejection(string $message): bool
    {
        $message = strtolower($message);
        return str_contains($message, 'inputsensitivecontentdetected')
            || str_contains($message, 'real person')
            || str_contains($message, 'privacyinformation');
    }

    public function isRetryableProviderError(string $message): bool
    {
        $message = strtolower($message);
        if ($this->isSafetyRejection($message)
            || str_contains($message, 'validation')
            || str_contains($message, 'unsupported')
            || str_contains($message, 'invalid input')
            || str_contains($message, 'http 400')
            || str_contains($message, 'http 401')
            || str_contains($message, 'http 403')
            || str_contains($message, 'http 404')
            || str_contains($message, 'http 422')) {
            return false;
        }

        return str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'connection')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'http 429')
            || preg_match('/http 5\d\d/', $message) === 1
            || str_contains($message, 'temporar')
            || str_contains($message, 'unavailable')
            || str_contains($message, 'exhausted balance')
            || str_contains($message, 'user is locked');
    }

    private function errorCodeFor(string $message): string
    {
        if ($this->isSafetyRejection($message)) return 'SAFETY_REJECTED';
        return $this->isRetryableProviderError($message) ? 'PROVIDER_UNAVAILABLE' : 'PROVIDER_REJECTED';
    }

    private function actualProviderCostForOrder(?int $orderId): ?float
    {
        if (!$orderId) return null;
        $requests = AiProviderRequest::query()->where('order_id', $orderId)->whereNotNull('actual_cost_usd');
        if (!$requests->exists()) return null;
        return (float) $requests->sum('actual_cost_usd');
    }

    private function publicErrorMessage(string $message): string
    {
        if ($this->isSafetyRejection($message)) {
            return 'سرویس‌دهنده این ورودی را به‌دلیل سیاست‌های ایمنی نپذیرفت.';
        }
        if ($this->isRetryableProviderError($message)) {
            return 'ارتباط با سرویس ساخت موقتاً برقرار نشد؛ می‌توانید دوباره تلاش کنید.';
        }
        return 'سرویس‌دهنده این درخواست را نپذیرفت؛ ورودی‌ها و مدل انتخاب‌شده را بررسی کنید.';
    }

    private function buildProviderInput(AiModel $model, Product $product, string $prompt, array $options): array
    {
        $properties = $this->modelSchemas->properties($model);
        $config = $product->videoConfiguration();
        $input = array_replace(
            collect($properties)->mapWithKeys(fn ($schema, $field): array => is_array($schema) && array_key_exists('default', $schema) ? [$field => $schema['default']] : [])->all(),
            (array) ($config['model_defaults'] ?? []),
        );
        $input['prompt'] = $prompt;
        $duration = (int) $options['duration'];
        $fps = max(4, min(60, (int) ($config['fps'] ?? 24)));
        if (array_key_exists('duration', $properties)) $input['duration'] = $this->enumAwareValue($properties['duration'], $duration);
        if (array_key_exists('num_frames', $properties)) {
            $minimum = (int) ($properties['num_frames']['minimum'] ?? 17);
            $maximum = (int) ($properties['num_frames']['maximum'] ?? ($duration * $fps + 1));
            $input['num_frames'] = max($minimum, min($maximum, $duration * $fps + 1));
        }
        foreach (['frames_per_second', 'fps'] as $field) if (array_key_exists($field, $properties)) $input[$field] = $fps;
        $aspectEnum = (array) data_get($properties, 'aspect_ratio.enum', []);
        $configuredAspectRatios = array_map('strval', (array) data_get($model->capability_config, 'supported_aspect_ratios', []));
        $aspectSupported = ($aspectEnum === [] || in_array((string) $options['aspect_ratio'], $aspectEnum, true))
            && ($configuredAspectRatios === [] || in_array((string) $options['aspect_ratio'], $configuredAspectRatios, true));
        if (array_key_exists('aspect_ratio', $properties) && $aspectSupported) $input['aspect_ratio'] = (string) $options['aspect_ratio'];
        if (array_key_exists('resolution', $properties)) $input['resolution'] = (string) $options['resolution'];
        $negativePrompt = trim(implode(', ', array_filter([$product->negative_prompt, $options['negative_prompt'] ?? ''])));
        if (array_key_exists('negative_prompt', $properties) && $negativePrompt !== '') $input['negative_prompt'] = $negativePrompt;
        if (array_key_exists('seed', $properties) && isset($options['seed'])) $input['seed'] = (int) $options['seed'];
        foreach (['enable_prompt_expansion', 'enhance_prompt', 'prompt_enhancement'] as $field) {
            if (array_key_exists($field, $properties)) $input[$field] = (bool) ($config['prompt_enhance'] ?? true);
        }
        if (array_key_exists('generate_audio', $properties)) $input['generate_audio'] = (bool) (($config['audio_allowed'] ?? false) && ($options['generate_audio'] ?? $config['audio_default'] ?? false));

        $sourceImages = array_values(array_filter(
            (array) ($options['source_image_data_list'] ?? []),
            fn ($value): bool => is_string($value) && trim($value) !== '',
        ));
        if ($sourceImages === [] && !empty($options['source_image_data'])) {
            $sourceImages = [(string) $options['source_image_data']];
        }
        if (!empty($options['source_upload_paths'])) {
            $sourceImages = [];
            foreach ((array) $options['source_upload_paths'] as $path) {
                if (!is_string($path) || !Storage::disk('public')->exists($path)) continue;
                $sourceImages[] = asset('storage/' . ltrim($path, '/'));
            }
        }

        if ($model->provider === 'openrouter') {
            $capabilities = (array) ($model->capability_config ?? []);
            $supportedDurations = array_values(array_map('strval', (array) ($capabilities['supported_durations'] ?? [])));
            $supportedResolutions = array_values(array_map('strtolower', array_map('strval', (array) ($capabilities['supported_resolutions'] ?? []))));
            $supportedAspectRatios = array_values(array_map('strtolower', array_map('strval', (array) ($capabilities['supported_aspect_ratios'] ?? []))));
            $passthrough = array_map('strtolower', array_values(array_filter((array) ($capabilities['allowed_passthrough_parameters'] ?? []), 'is_string')));
            $input = ['prompt' => $prompt];
            // پارامترهای اختیاری فقط وقتی ارسال می‌شوند که کاتالوگ زندهٔ مدل
            // آن‌ها را اعلام کرده باشد؛ این کار جلوی ردشدن درخواست به‌خاطر
            // فرستادن فیلدهای مدل دیگری را می‌گیرد.
            if ($supportedDurations === [] || in_array((string) $duration, $supportedDurations, true)) {
                if ($supportedDurations !== []) $input['duration'] = $duration;
            }
            if ($supportedResolutions !== [] && in_array(strtolower((string) $options['resolution']), $supportedResolutions, true)) {
                $input['resolution'] = (string) $options['resolution'];
            }
            if ($aspectSupported && ($supportedAspectRatios === [] || in_array(strtolower((string) $options['aspect_ratio']), $supportedAspectRatios, true))) {
                if ($supportedAspectRatios !== []) $input['aspect_ratio'] = (string) $options['aspect_ratio'];
            }
            $audioRequested = (bool) (($config['audio_allowed'] ?? false) && ($options['generate_audio'] ?? $config['audio_default'] ?? false));
            if ($audioRequested && data_get($capabilities, 'supports_audio') === true) $input['generate_audio'] = true;
            if ($negativePrompt !== '' && in_array('negative_prompt', $passthrough, true)) $input['negative_prompt'] = $negativePrompt;
            if (isset($options['seed']) && (data_get($capabilities, 'supports_seed') === true || in_array('seed', $passthrough, true))) {
                $input['seed'] = (int) $options['seed'];
            }
            if ($sourceImages !== []) {
                $references = array_map(fn (string $image): array => [
                    'type' => 'image_url',
                    'image_url' => ['url' => $image],
                ], $sourceImages);
                $useReferences = ($options['reference_mode'] ?? null) === 'input_references' || count($references) > 1;
                $supportedFrames = array_values(array_filter((array) data_get($model->capability_config, 'supported_frame_images', []), 'is_string'));
                // فقط مدل‌هایی که در کاتالوگ زنده frame_images را اعلام کرده‌اند
                // باید قالب first/last frame دریافت کنند؛ برای سایر مدل‌ها قالب
                // رسمی input_references استفاده می‌شود تا درخواست با schema مدل
                // ناسازگار نشود.
                if ($useReferences || $supportedFrames === []) {
                    $input['input_references'] = $references;
                } else {
                    $frameType = in_array('first_frame', $supportedFrames, true) ? 'first_frame' : $supportedFrames[0];
                    $input['frame_images'] = [[
                        ...$references[0],
                        'frame_type' => $frameType,
                    ]];
                }
            }
            if (!empty($options['source_video_url'])) {
                if (data_get($model->capability_config, 'supports_video_to_video') !== true
                    && $model->supports_video_input !== true) {
                    throw ValidationException::withMessages([
                        'source_video' => 'مدل انتخاب‌شده ورودی ویدیویی را پشتیبانی نمی‌کند.',
                    ]);
                }
                $input['input_references'][] = [
                    'type' => 'video_url',
                    'video_url' => ['url' => $options['source_video_url']],
                ];
            }
            if (!empty($options['audio_url'])) $input['audio_url'] = $options['audio_url'];
            return $input;
        }

        $imageField = $this->modelSchemas->fieldFor($model, ['image_url', 'start_image', 'first_frame', 'input_image', 'image', 'reference_image', 'subject_image']);
        if (!$imageField && $model->provider === 'fal' && $model->task_type === 'image_to_video') {
            $imageField = 'image_url';
        }
        if ($imageField && $sourceImages !== []) {
            $input[$imageField] = $sourceImages[0];
        }
        $multipleImageField = $this->modelSchemas->fieldFor($model, ['image_urls', 'images', 'reference_images']);
        if ($multipleImageField && count($sourceImages) > 1) {
            $input[$multipleImageField] = $sourceImages;
        }
        $videoField = $this->modelSchemas->fieldFor($model, ['video_url', 'input_video', 'source_video', 'video']);
        if (!$videoField && $model->provider === 'fal' && $model->task_type === 'video_to_video') {
            $videoField = 'video_url';
        }
        if ($videoField && !empty($options['source_video_url'])) $input[$videoField] = $options['source_video_url'];
        $audioField = $this->modelSchemas->fieldFor($model, ['audio_url', 'input_audio', 'audio']);
        if ($audioField && !empty($options['audio_url'])) $input[$audioField] = $options['audio_url'];

        foreach ((array) ($options['fields'] ?? []) as $field => $value) {
            if (array_key_exists($field, $properties) && !array_key_exists($field, $input)) $input[$field] = $value;
        }

        return $this->modelSchemas->sanitizeInput($model, $input);
    }

    private function resolveModel(Product $product): AiModel
    {
        // مدل اصلی فقط زمانی انتخاب می‌شود که کلید provider آن آماده باشد؛
        // در غیر این صورت، اولین مدل جایگزینِ فعال و دارای کلید اجرا می‌شود.
        // این کار جلوی ثبت سفارش و رزرو اعتبار برای Replicate بدون توکن را می‌گیرد.
        $ids = array_values(array_filter(array_merge([(string) $product->primary_model], (array) $product->fallback_models)));
        $providers = array_values(array_merge([(string) $product->ai_provider], (array) $product->fallback_model_providers));

        foreach ($ids as $index => $id) {
            $provider = $providers[$index] ?? null;
            if (! $provider) continue;

            $model = AiModel::query()
                ->where('is_active', true)
                ->where('output_modality', 'video')
                ->where('provider', 'openrouter')
                ->whereNotNull('capability_config')
                ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
                ->where('provider', $provider)
                ->where('openrouter_model_id', $id)
                ->first();

            if ($model && filled($this->credentials->for($model->provider)['api_key'] ?? null)) {
                return $model;
            }
        }

        throw new RuntimeException('هیچ مدل ویدیویی فعال و دارای کلید دسترسی برای این محصول پیدا نشد.');
    }

    private function asyncProvider(string $provider): AiAsyncImageProviderInterface
    {
        $service = $this->providers->videoServiceFor($provider);
        if (!$service instanceof AiAsyncImageProviderInterface) {
            throw new RuntimeException('سرویس انتخاب‌شده از صف تولید ویدیو پشتیبانی نمی‌کند.');
        }

        return $service;
    }

    private function enumAwareValue(array $schema, int|string $value): int|string
    {
        $enum = (array) ($schema['enum'] ?? []);
        if ($enum === []) return $value;
        foreach ($enum as $candidate) if ((string) $candidate === (string) $value) return $candidate;
        return $enum[0];
    }

    private function downloadOutput(string $url, string $provider): array
    {
        $this->assertSafeRemoteMediaUrl($url);
        $headers = $this->providerDownloadHeaders($url, $provider);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'vatan-video-');
        if ($temporaryPath === false) throw new RuntimeException('فضای موقت برای ذخیرهٔ ویدیو در دسترس نیست.');
        try {
            $response = Http::withHeaders($headers)
                ->withOptions(['sink' => $temporaryPath])
                ->connectTimeout(15)->timeout(180)->get($url);
            if ($response->failed()) throw new RuntimeException('دانلود خروجی ویدیو از سرویس‌دهنده ناموفق بود.');
            $size = (int) (filesize($temporaryPath) ?: 0);
            if ($size < 1 || $size > 120 * 1024 * 1024) throw new RuntimeException('حجم خروجی ویدیو معتبر نیست.');
            $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            if ($mime !== '' && !str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream') {
                throw new RuntimeException('فایل خروجی سرویس‌دهنده ویدیوی معتبر نیست.');
            }
            $extension = match ($mime) {
                'video/webm' => 'webm',
                'video/quicktime' => 'mov',
                default => 'mp4',
            };
            $path = 'generated/videos/' . uniqid('video_', true) . '.' . $extension;
            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false || !Storage::disk('public')->put($path, $stream)) {
                if (is_resource($stream)) fclose($stream);
                throw new RuntimeException('ذخیرهٔ خروجی ویدیو کامل نشد.');
            }
            fclose($stream);

            return ['path' => $path, 'mime' => $mime ?: 'video/mp4', 'size' => $size];
        } finally {
            @unlink($temporaryPath);
        }
    }

    private function assertSafeRemoteMediaUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!filter_var($url, FILTER_VALIDATE_URL) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('نشانی خروجی سرویس‌دهنده معتبر نیست.');
        }
        if ($scheme !== 'https' && !(app()->environment(['local', 'testing']) && $scheme === 'http')) {
            throw new RuntimeException('نشانی خروجی سرویس‌دهنده باید امن باشد.');
        }
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new RuntimeException('نشانی خروجی سرویس‌دهنده قابل دسترسی نیست.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('نشانی خروجی سرویس‌دهنده به شبکهٔ داخلی اشاره می‌کند.');
            }
        }
    }

    private function providerDownloadHeaders(string $url, string $provider): array
    {
        if (!in_array($provider, ['replicate', 'openrouter'], true)) return [];

        $credentials = $this->credentials->for($provider);
        $apiKey = trim((string) ($credentials['api_key'] ?? ''));
        $baseHost = strtolower((string) parse_url((string) ($credentials['base_url'] ?? ''), PHP_URL_HOST));
        $downloadHost = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($apiKey === '' || $baseHost === '' || !hash_equals($baseHost, $downloadHost)) return [];

        return ['Authorization' => 'Bearer ' . $apiKey];
    }

    private function holdForCostReview(GeneratedVideo $generation, string $message): GeneratedVideo
    {
        $generation->update([
            'status' => 'needs_review',
            'error_code' => 'ACTUAL_COST_MISSING',
            'error_message' => $message,
            'retryable' => false,
            'completed_at' => now(),
        ]);
        $generation->order?->update([
            'status' => 'review',
            'processing_status' => 'needs_review',
            'error_message' => $message,
        ]);

        return $generation->fresh();
    }
}
