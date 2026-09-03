<?php

namespace App\Services;

use App\Contracts\AiAsyncImageProviderInterface;
use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
    ) {}

    public function start(Product $product, User $user, array $options): GeneratedVideo
    {
        if (!$product->isVideoProduct() || $product->status !== 'active') {
            throw ValidationException::withMessages(['product' => 'محصول ویدیویی انتخاب‌شده فعال نیست.']);
        }

        $config = $product->videoConfiguration();
        $studioMode = (bool) ($options['studio_mode'] ?? false);
        $duration = (int) ($options['duration'] ?? $config['default_duration']);
        $aspectRatio = (string) ($options['aspect_ratio'] ?? $config['default_aspect_ratio']);
        $resolution = (string) ($options['resolution'] ?? $config['default_resolution']);
        $allowedDurations = $studioMode ? range(1, 15) : array_map('intval', (array) $config['durations']);
        $allowedResolutions = $studioMode ? VideoProductConfigService::RESOLUTIONS : (array) $config['resolutions'];
        $motionCatalog = $studioMode
            ? collect($this->videoConfig->motionPresetCatalog())->map(fn (array $preset, string $key): array => ['key' => $key] + $preset)->all()
            : (array) $config['motion_presets'];
        if (!in_array($duration, $allowedDurations, true)) {
            throw ValidationException::withMessages(['video.duration' => 'مدت انتخاب‌شده برای این محصول فعال نیست.']);
        }
        $allowedAspectRatios = $studioMode ? VideoProductConfigService::STUDIO_ASPECT_RATIOS : (array) $config['aspect_ratios'];
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
        if ($config['workflow'] === 'video_to_video' && empty($options['source_video_url'])) {
            throw ValidationException::withMessages(['source_video' => 'سناریوی ویدیو به ویدیو به فایل ورودی نیاز دارد.']);
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
        $fieldValues = (array) ($options['fields'] ?? []);
        $fieldValues['prompt'] = trim((string) ($options['prompt'] ?? $fieldValues['prompt'] ?? ''));
        $identityRequested = $faceMode !== 'disabled' && $hasSourceImage;
        $prompt = $this->promptBuilder->build($product, $fieldValues, $identityRequested);
        $motion = collect($motionCatalog)->firstWhere('key', (string) ($options['motion_preset'] ?? ''));
        if ($motion && !empty($motion['prompt'])) $prompt .= "\n\nCamera direction: " . $motion['prompt'];
        if (!empty($options['prompt']) && !str_contains($prompt, trim((string) $options['prompt']))) {
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
            $creditCost = max(0, $this->videoConfig->creditCost($product, $duration, $resolution, (bool) ($options['generate_audio'] ?? false), $identityRequested) + $featureCost);
        }
        $studioQuote = $studioMode
            ? $this->studioCosts->quote($product, [
                'media_type' => 'video',
                'resolution' => $resolution,
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
        $allowPromotional = (bool) ($config['allow_promotional_credits'] ?? false)
            || $this->wallet->productAllowsPromotionalCredits($product);

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
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'resolution' => $resolution,
                'motion_preset' => $options['motion_preset'] ?? null,
                'project_name' => $options['project_name'] ?? null,
                'face_profile_id' => $options['face_profile_id'] ?? null,
                'source_upload_path' => $options['source_upload_path'] ?? null,
                'source_upload_paths' => array_values(array_filter((array) ($options['source_upload_paths'] ?? []))),
                'source_video_url' => $options['source_video_url'] ?? null,
                'workflow' => $options['workflow'] ?? $config['workflow'],
                'reference_mode' => $options['reference_mode'] ?? null,
                'source_image_data' => $hasSourceImage && strlen($sourceImageDataList[0]) <= 600000 ? $sourceImageDataList[0] : null,
            ],
            'source' => 'app',
            'paid_at' => now(),
            'processing_started_at' => now(),
        ]);
        $order->recordEvent('created', 'سفارش ویدیویی ثبت شد', 'درخواست برای صف تولید ویدیو آماده شد.');

        $reservation = ['total' => 0, 'promotional' => 0, 'paid' => 0, 'ledger_key' => null];
        try {
            if ($creditCost > 0) $reservation = $this->wallet->reserve($user, $creditCost, $allowPromotional, $order);
        } catch (ValidationException $exception) {
            $order->update(['status' => 'review', 'payment_status' => 'failed', 'processing_status' => 'stopped', 'error_message' => 'اعتبار کافی نیست.']);
            throw $exception;
        }

        $generation = GeneratedVideo::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'status' => 'submitting',
            'user_prompt' => $prompt,
            'input_payload' => $order->input_payload,
            'credit_reservation' => $reservation,
            'duration_seconds' => $duration,
        ]);

        $submitted = false;
        $lastError = null;
        foreach ($this->candidateModels($product) as $candidate) {
            try {
                $this->submitCandidate($generation, $order, $candidate, $product, $prompt, $options + ['duration' => $duration, 'aspect_ratio' => $aspectRatio, 'resolution' => $resolution]);
                $submitted = true;
                break;
            } catch (\Throwable $error) {
                $lastError = $error;
                $this->markAttempted($generation, $candidate);
                Log::warning('Video model failed; trying fallback', ['product_id' => $product->id, 'model' => $candidate->openrouter_model_id, 'provider' => $candidate->provider, 'message' => $error->getMessage()]);
            }
        }
        if (!$submitted) {
            $this->failAndRestore($generation, $lastError?->getMessage() ?: 'همه مدل‌های ویدیو ناموفق بودند.');
            throw $lastError ?: new RuntimeException('همه مدل‌های ویدیو ناموفق بودند.');
        }

        return $generation->fresh(['product', 'order']);
    }

    public function quote(Product $product, User $user, array $options): array
    {
        $config = $product->videoConfiguration();
        $duration = (int) ($options['duration'] ?? $config['default_duration']);
        $resolution = (string) ($options['resolution'] ?? $config['default_resolution']);
        $identity = !empty($options['source_image_data']) || !empty($options['face_profile_id']);
        $base = $this->videoConfig->creditCost($product, $duration, $resolution, (bool) ($options['generate_audio'] ?? false), $identity);
        $features = $this->buildSchema->additionalCredit($product, (array) ($options['fields'] ?? []));
        $total = max(0, $base + $features);
        return ['credits' => $total, 'balance' => (int) $user->tokens, 'can_afford' => (int) $user->tokens >= $total, 'breakdown' => ['مدت ویدیو' => $this->videoConfig->creditCost($product, $duration), 'کیفیت/امکانات' => $total - $features - $this->videoConfig->creditCost($product, $duration), 'ویژگی‌ها' => $features]];
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
        $generation = GeneratedVideo::query()->where('external_request_id', $requestId)->first();
        if ($generation) $this->applyNormalized($generation, $normalized);
    }

    private function applyNormalized(GeneratedVideo $generation, array $normalized): GeneratedVideo
    {
        $status = (string) ($normalized['status'] ?? 'processing');
        if (in_array($status, ['queued', 'processing'], true)) {
            $generation->update(['status' => $status]);
            $generation->order?->update(['processing_status' => $status === 'queued' ? 'queued' : 'processing']);
            return $generation->fresh();
        }
        if (in_array($status, ['failed', 'canceled'], true)) {
            if ($status === 'failed' && $this->retryFallback($generation, (string) ($normalized['error_message'] ?? 'خطای سرویس‌دهنده'))) {
                return $generation->fresh();
            }
            $this->failAndRestore($generation, (string) ($normalized['error_message'] ?? 'تولید ویدیو در سرویس‌دهنده کامل نشد.'), $status);
            return $generation->fresh();
        }
        if ($status !== 'completed') return $generation;
        if ($generation->status === 'completed' && $generation->credits_settled_at) return $generation;

        $output = collect((array) ($normalized['output_urls'] ?? []))->first(fn ($item): bool => is_array($item) && filter_var($item['url'] ?? null, FILTER_VALIDATE_URL));
        if (!$output) throw new RuntimeException('سرویس‌دهنده ویدیو را تکمیل کرد اما آدرس فایل خروجی موجود نیست.');
        try {
            $stored = $this->downloadOutput((string) $output['url'], $generation->order?->ai_provider ?: 'fal');
        } catch (\Throwable $error) {
            if ($this->retryFallback($generation, $error->getMessage())) return $generation->fresh();
            throw $error;
        }
        $reservation = (array) ($generation->credit_reservation ?? []);
        if (!$generation->credits_settled_at && (int) ($reservation['total'] ?? 0) > 0 && $generation->user) {
            $reservation = $this->wallet->settle($generation->user, $reservation, (int) $reservation['total']);
        }
        $actualCost = (float) ($normalized['actual_cost_usd'] ?? $normalized['estimated_cost_usd'] ?? 0);
        $generation->update([
            'status' => 'completed',
            'video_path' => $stored['path'],
            'video_url' => (string) $output['url'],
            'mime_type' => $stored['mime'],
            'size' => $stored['size'],
            'cost' => $actualCost,
            'credit_reservation' => $reservation,
            'credits_settled_at' => $generation->credits_settled_at ?: now(),
            'completed_at' => now(),
            'error_message' => null,
        ]);
        $generation->order?->update([
            'status' => 'completed',
            'processing_status' => 'completed',
            'promotional_credits_used' => (int) ($reservation['promotional'] ?? 0),
            'paid_credits_used' => (int) ($reservation['paid'] ?? 0),
            'output_payload' => ['media_type' => 'video', 'path' => $stored['path'], 'provider_url' => $output['url']],
            'completed_at' => now(),
            'processing_duration_ms' => $generation->order?->processing_started_at?->diffInMilliseconds(now()),
        ]);
        $generation->order?->recordEvent('completed', 'ویدیو با موفقیت ساخته شد');

        return $generation->fresh();
    }

    private function failAndRestore(GeneratedVideo $generation, string $message, string $status = 'failed'): void
    {
        $reservation = (array) ($generation->credit_reservation ?? []);
        if (!$generation->credits_restored_at && !$generation->credits_settled_at && (int) ($reservation['total'] ?? 0) > 0 && $generation->user) {
            $this->wallet->restore(
                $generation->user,
                (int) ($reservation['promotional'] ?? 0),
                (int) ($reservation['paid'] ?? 0),
                false,
                $reservation['ledger_key'] ?? null,
                (array) ($reservation['grant_allocations'] ?? []),
            );
        }
        $generation->update([
            'status' => $status,
            'error_message' => $message,
            'credits_restored_at' => $generation->credits_restored_at ?: now(),
            'completed_at' => now(),
        ]);
        $generation->order?->update([
            'status' => 'review',
            'processing_status' => $status === 'canceled' ? 'stopped' : 'failed',
            'error_message' => $message,
            'processing_duration_ms' => $generation->order?->processing_started_at?->diffInMilliseconds(now()),
        ]);
        $generation->order?->recordEvent('failed', 'ساخت ویدیو کامل نشد', $message);
        Log::warning('Video generation failed', ['generated_video_id' => $generation->id, 'message' => $message]);
    }

    private function candidateModels(Product $product): array
    {
        $ids = array_values(array_unique(array_filter(array_merge([(string) $product->primary_model], (array) $product->fallback_models))));
        $providers = array_values(array_merge([(string) $product->ai_provider], (array) $product->fallback_model_providers));
        $models = [];
        foreach ($ids as $index => $id) {
            $model = AiModel::query()->where('is_active', true)->where('output_modality', 'video')
                ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
                ->where('openrouter_model_id', $id)->where('provider', $providers[$index] ?? null)->first();
            if ($model && filled($this->credentials->for($model->provider)['api_key'] ?? null)) $models[] = $model;
        }
        return $models;
    }

    private function submitCandidate(GeneratedVideo $generation, Order $order, AiModel $model, Product $product, string $prompt, array $options): void
    {
        $input = $this->buildProviderInput($model, $product, $prompt, $options);
        $submitted = $this->asyncProvider($model->provider)->submitGeneration($model, $prompt, [
            'input' => $input, 'order_id' => $order->id, 'n' => 1,
            'duration' => $options['duration'], 'resolution' => $options['resolution'],
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
            }
        }
        return false;
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
        if (array_key_exists('aspect_ratio', $properties)) $input['aspect_ratio'] = (string) $options['aspect_ratio'];
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
        if ($sourceImages === [] && !empty($options['source_upload_paths'])) {
            foreach ((array) $options['source_upload_paths'] as $path) {
                if (!is_string($path) || !Storage::disk('public')->exists($path)) continue;
                $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
                $sourceImages[] = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
            }
        }

        if ($model->provider === 'openrouter') {
            $input = [
                'prompt' => $prompt,
                'duration' => $duration,
                'resolution' => (string) $options['resolution'],
                'aspect_ratio' => (string) $options['aspect_ratio'],
                'generate_audio' => (bool) (($config['audio_allowed'] ?? false) && ($options['generate_audio'] ?? $config['audio_default'] ?? false)),
            ];
            if ($negativePrompt !== '') $input['negative_prompt'] = $negativePrompt;
            if (isset($options['seed'])) $input['seed'] = (int) $options['seed'];
            if ($sourceImages !== []) {
                $references = array_map(fn (string $image): array => [
                    'type' => 'image_url',
                    'image_url' => ['url' => $image],
                ], $sourceImages);
                $useReferences = ($options['reference_mode'] ?? null) === 'input_references' || count($references) > 1;
                if ($useReferences) {
                    $input['input_references'] = $references;
                } else {
                    $input['frame_images'] = [[
                        ...$references[0],
                        'frame_type' => 'first_frame',
                    ]];
                }
            }
            if (!empty($options['source_video_url'])) {
                $input['input_references'][] = [
                    'type' => 'video_url',
                    'video_url' => ['url' => $options['source_video_url']],
                ];
            }
            return $input;
        }

        $imageField = $this->modelSchemas->fieldFor($model, ['image_url', 'start_image', 'first_frame', 'input_image', 'image', 'reference_image', 'subject_image']);
        if ($imageField && $sourceImages !== []) {
            $input[$imageField] = $sourceImages[0];
        }
        $multipleImageField = $this->modelSchemas->fieldFor($model, ['image_urls', 'images', 'reference_images']);
        if ($multipleImageField && count($sourceImages) > 1) {
            $input[$multipleImageField] = $sourceImages;
        }
        $videoField = $this->modelSchemas->fieldFor($model, ['video_url', 'input_video', 'source_video', 'video']);
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
        $headers = in_array($provider, ['replicate', 'openrouter'], true)
            ? ['Authorization' => 'Bearer ' . $this->credentials->for($provider)['api_key']]
            : [];
        $response = Http::withHeaders($headers)->connectTimeout(15)->timeout(180)->get($url);
        if ($response->failed()) throw new RuntimeException('دانلود خروجی ویدیو از سرویس‌دهنده ناموفق بود.');
        $body = $response->body();
        if (strlen($body) > 120 * 1024 * 1024) throw new RuntimeException('حجم خروجی ویدیو بیشتر از سقف ذخیره‌سازی مجاز است.');
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $extension = match ($mime) {
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => 'mp4',
        };
        $path = 'generated/videos/' . uniqid('video_', true) . '.' . $extension;
        Storage::disk('public')->put($path, $body);

        return ['path' => $path, 'mime' => $mime ?: 'video/mp4', 'size' => strlen($body)];
    }
}
