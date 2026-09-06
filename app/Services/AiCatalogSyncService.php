<?php

namespace App\Services;

use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AiCatalogSyncService
{
    private const REPLICATE_VATAN_PRIORITY_MODELS = [
        'black-forest-labs/flux-2-max',
        'black-forest-labs/flux-2-pro',
        'google/nano-banana-2',
        'bytedance/seedream-4.5',
        'black-forest-labs/flux-kontext-pro',
        'google/nano-banana',
        'openai/gpt-image-1',
        'openai/gpt-image-1.5',
        'openai/gpt-image-2',
        'ideogram-ai/ideogram-v3-turbo',
    ];

    private ?array $availableWorkflowColumns = null;

    public function __construct(private AiProviderCredentials $credentials)
    {
    }

    public function sync(string $provider = 'all'): array
    {
        $provider = strtolower(trim($provider));
        $results = [];

        if ($provider === 'all' || $provider === 'fal') {
            $results['fal'] = $this->syncFal();
        }
        if ($provider === 'all' || $provider === 'replicate') {
            $results['replicate'] = $this->syncReplicate();
        }
        if ($provider === 'all' || $provider === 'openrouter') {
            $results['openrouter'] = $this->syncOpenRouterVideos();
        }

        if (!isset($results[$provider]) && $provider !== 'all') {
            throw new RuntimeException('provider کاتالوگ معتبر نیست. فقط fal، replicate، openrouter یا all مجاز است.');
        }

        return $results;
    }

    /**
     * OpenRouter برای ویدیو یک کاتالوگ جدا از /models دارد. این همگام‌سازی
     * متادیتای واقعی مدل، بازه‌ی زمانی، رزولوشن، تصویر فریم اول و قیمت هر
     * ثانیه را وارد جدول عمومی ai_models می‌کند تا انتخاب مدل و محاسبه‌ی
     * اعتبار از روی همان قرارداد زنده‌ی provider انجام شود.
     */
    public function syncOpenRouterVideos(): array
    {
        $credentials = $this->credentials->for('openrouter');
        if (blank($credentials['api_key'])) {
            return ['skipped' => true, 'reason' => 'کلید OpenRouter ثبت نشده است.', 'created' => 0, 'updated' => 0, 'total' => 0];
        }

        $response = Http::withToken($credentials['api_key'])
            ->acceptJson()
            ->connectTimeout(15)
            ->timeout(min(60, max(15, (int) $credentials['timeout'])))
            ->get(rtrim($credentials['base_url'] ?: 'https://openrouter.ai/api/v1', '/') . '/videos/models');

        if ($response->failed()) {
            throw new RuntimeException('OpenRouter video catalog HTTP ' . $response->status() . ': ' . Str::limit($response->body(), 500));
        }

        $created = 0;
        $updated = 0;
        $seen = [];
        foreach ((array) $response->json('data', []) as $remote) {
            $modelId = trim((string) ($remote['id'] ?? ''));
            if ($modelId === '' || isset($seen[$modelId])) continue;
            $seen[$modelId] = true;
            [$wasCreated, $wasUpdated] = $this->upsertModel($this->openRouterVideoData($remote));
            $created += $wasCreated;
            $updated += $wasUpdated;
        }

        return ['created' => $created, 'updated' => $updated, 'total' => count($seen)];
    }

    public function syncFal(): array
    {
        $credentials = $this->credentials->for('fal');

        $categories = ['text-to-image', 'image-to-image', 'text-to-video', 'image-to-video', 'video-to-video'];
        $seen = [];
        $created = 0;
        $updated = 0;

        foreach ($categories as $category) {
            $cursor = null;
            do {
                $query = [
                    'category' => $category,
                    'status' => 'active',
                    // Fal.ai همراه با OpenAPI کامل، حداکثر ۱۰ مدل در هر صفحه
                    // می‌پذیرد؛ pagination تمام مدل‌های هر دسته را پوشش می‌دهد.
                    'limit' => 10,
                    'expand' => 'openapi-3.0',
                ];
                if ($cursor) $query['cursor'] = $cursor;

                $client = Http::acceptJson()
                    ->connectTimeout(15)
                    ->timeout(min(60, max(15, (int) $credentials['timeout'])));
                if (filled($credentials['api_key'])) {
                    $client = $client->withToken($credentials['api_key'], 'Key');
                }

                $response = $client->get(rtrim(config('services.fal.platform_base_url', 'https://api.fal.ai'), '/') . '/v1/models', $query);

                if ($response->failed()) {
                    throw new RuntimeException('Fal.ai catalog HTTP ' . $response->status() . ': ' . Str::limit($response->body(), 500));
                }

                foreach ((array) $response->json('models', []) as $remote) {
                    $endpointId = trim((string) ($remote['endpoint_id'] ?? ''));
                    if ($endpointId === '' || isset($seen[$endpointId])) continue;
                    $seen[$endpointId] = true;

                    $metadata = (array) ($remote['metadata'] ?? []);
                    [$wasCreated, $wasUpdated] = $this->upsertModel($this->falData($endpointId, $category, $metadata, (array) ($remote['openapi'] ?? [])));
                    $created += $wasCreated;
                    $updated += $wasUpdated;
                }

                $cursor = $response->json('next_cursor');
            } while ($cursor);
        }

        return ['created' => $created, 'updated' => $updated, 'total' => count($seen)];
    }

    public function syncReplicate(): array
    {
        return $this->syncReplicateCollection('text-to-image');
    }

    /**
     * فهرست یک Collection رسمی Replicate را با schema زنده‌ی هر مدل در
     * کاتالوگ وطن ثبت می‌کند. مدل‌های ویدیویی یا غیرتصویری Collection عمداً
     * وارد این کاتالوگ نمی‌شوند؛ این مسیر فقط text-to-image و image-to-image است.
     */
    public function syncReplicateCollection(string $collectionSlug): array
    {
        $credentials = $this->credentials->for('replicate');
        $baseUrl = rtrim($credentials['base_url'] ?: 'https://api.replicate.com/v1', '/');
        $apiModels = [];

        // API رسمی Replicate برای دریافت schema به کلید نیاز دارد، اما خودِ
        // صفحهٔ عمومی Collection و صفحهٔ هر مدل، schema قابل‌نمایش را دارند.
        // این fallback اجازه می‌دهد کاتالوگ داشبورد حتی در محیط توسعه‌ای که
        // کلید خصوصی ذخیره نشده نیز کامل بماند؛ اجرای واقعی مدل همچنان فقط
        // با کلید provider مجاز است.
        if (filled($credentials['api_key'])) {
            $collection = Http::withToken($credentials['api_key'])
                ->connectTimeout(15)
                ->timeout(min(60, max(15, (int) $credentials['timeout'])))
                ->get($baseUrl . '/collections/' . rawurlencode($collectionSlug));

            if ($collection->failed()) {
                throw new RuntimeException('Replicate collection HTTP ' . $collection->status() . ': ' . Str::limit($collection->body(), 500));
            }

            $apiModels = (array) $collection->json('models', []);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        $summaries = $this->replicateCollectionSummaries(
            $apiModels,
            $collectionSlug,
            $credentials['timeout']
        );

        foreach ($summaries as $summary) {
            try {
                $remote = $this->hydrateReplicateModel($summary, $baseUrl, $credentials['api_key'], $credentials['timeout']);
                $classification = $this->classifyReplicate($remote);
                if ($classification === null) {
                    $classification = [
                        'modality' => 'image',
                        'task_type' => 'text_to_image',
                        'supports_face_identity' => false,
                        'supports_audio' => false,
                        'supports_video_input' => false,
                    ];
                }

                // تمام مواردی که خود Replicate در Collection «Generate images»
                // نمایش می‌دهد، مدل تصویری‌اند؛ نام provider بعضی مدل‌ها (مثل
                // wan-video/*-image) نباید آن‌ها را به اشتباه ویدیویی کند.
                $classification['modality'] = 'image';
                if (!in_array($classification['task_type'] ?? null, ['text_to_image', 'image_to_image'], true)) {
                    $classification['task_type'] = 'text_to_image';
                }

                $data = $this->replicateData($remote, $classification);
                $data['pricing_config'] = array_merge((array) $data['pricing_config'], [
                    'collection' => $collectionSlug,
                    'collection_source' => 'replicate-official',
                ]);
                [$wasCreated, $wasUpdated] = $this->upsertModel($data);
                $created += $wasCreated;
                $updated += $wasUpdated;
            } catch (\Throwable $error) {
                $failed++;
                Log::warning('Replicate collection model sync was skipped', [
                    'collection' => $collectionSlug,
                    'model' => trim((string) data_get($summary, 'owner') . '/' . (string) data_get($summary, 'name'), '/'),
                    'message' => $error->getMessage(),
                ]);
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => $created + $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'collection' => $collectionSlug,
        ];
    }

    /**
     * پاسخ API Collection در حال حاضر بخشی از مدل‌های صفحه را بازمی‌گرداند.
     * صفحهٔ رسمی همان Collection منبع کامل‌تری از لینک‌های مدل است؛ هر دو
     * منبع را ترکیب می‌کنیم و سپس جزئیات معتبر هر مدل را از API می‌گیریم.
     */
    private function replicateCollectionSummaries(array $apiModels, string $collectionSlug, int $timeout): array
    {
        $summaries = [];
        $add = function (array $model) use (&$summaries): void {
            $owner = trim((string) ($model['owner'] ?? ''));
            $name = trim((string) ($model['name'] ?? ''));
            if ($owner !== '' && $name !== '') {
                $summaries[strtolower($owner . '/' . $name)] = $model;
            }
        };

        foreach ($apiModels as $model) {
            $add((array) $model);
        }

        try {
            $page = Http::accept('text/html')
                ->connectTimeout(15)
                ->timeout(min(60, max(15, $timeout)))
                ->get('https://replicate.com/collections/' . rawurlencode($collectionSlug));

            if ($page->successful() && preg_match_all('/href="\/([A-Za-z0-9_.-]+)\/([A-Za-z0-9_.-]+)"/', $page->body(), $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $add(['owner' => $match[1], 'name' => $match[2]]);
                }
            }
        } catch (\Throwable $error) {
            Log::warning('Replicate collection page could not be read; API results will be used.', [
                'collection' => $collectionSlug,
                'message' => $error->getMessage(),
            ]);
        }

        foreach (self::REPLICATE_VATAN_PRIORITY_MODELS as $modelId) {
            [$owner, $name] = array_pad(explode('/', $modelId, 2), 2, '');
            $add(['owner' => $owner, 'name' => $name]);
        }

        return array_values($summaries);
    }

    private function hydrateReplicateModel(array $summary, string $baseUrl, string $apiKey, int $timeout): array
    {
        $owner = trim((string) ($summary['owner'] ?? ''));
        $name = trim((string) ($summary['name'] ?? ''));
        if ($owner === '' || $name === '') {
            throw new RuntimeException('شناسه‌ی مدل در پاسخ Collection معتبر نیست.');
        }

        // پاسخ Collection در بعضی نسخه‌های API schema کامل را ندارد؛ جزئیات
        // مدل را می‌گیریم تا ورودی واقعی هر مدل در UI و Provider قابل استفاده باشد.
        if (!empty(data_get($summary, 'latest_version.openapi_schema'))) {
            return $summary;
        }

        if (filled($apiKey)) {
            $response = Http::withToken($apiKey)
                ->connectTimeout(15)
                ->timeout(min(60, max(15, $timeout)))
                ->get($baseUrl . '/models/' . rawurlencode($owner) . '/' . rawurlencode($name));

            if ($response->successful()) {
                return (array) $response->json();
            }

            throw new RuntimeException('جزئیات مدل HTTP ' . $response->status() . ': ' . Str::limit($response->body(), 500));
        }

        return $this->hydratePublicReplicateModel($owner, $name, $timeout);
    }

    /**
     * صفحهٔ عمومی مدل‌های Replicate شامل یک script JSON با schema نمایش‌داده‌شده
     * است. این مسیر فقط برای کاتالوگ توسعه‌ایِ بدون کلید استفاده می‌شود.
     */
    private function hydratePublicReplicateModel(string $owner, string $name, int $timeout): array
    {
        $response = Http::accept('text/html')
            ->connectTimeout(15)
            ->timeout(min(60, max(15, $timeout)))
            ->get('https://replicate.com/' . rawurlencode($owner) . '/' . rawurlencode($name));

        if ($response->failed()) {
            throw new RuntimeException('صفحهٔ عمومی مدل HTTP ' . $response->status() . ': ' . Str::limit($response->body(), 500));
        }

        $model = [];
        $version = [];
        if (preg_match_all('/<script[^>]*type="application\/json"[^>]*>(.*?)<\/script>/is', $response->body(), $matches)) {
            foreach ($matches[1] as $json) {
                $payload = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
                if (!is_array($payload)) continue;

                $candidate = (array) ($payload['model'] ?? []);
                if (($candidate['owner'] ?? null) === $owner && ($candidate['name'] ?? null) === $name) {
                    $model = $candidate;
                }

                $candidateVersion = (array) ($payload['version'] ?? []);
                if (!empty(data_get($candidateVersion, '_extras.dereferenced_openapi_schema'))) {
                    $version = $candidateVersion;
                }
            }
        }

        $schema = (array) data_get($version, '_extras.dereferenced_openapi_schema', []);
        if (empty($model) || empty($schema)) {
            throw new RuntimeException("schema عمومی مدل {$owner}/{$name} در دسترس نیست.");
        }

        $model['latest_version'] = [
            'id' => (string) (data_get($model, 'latest_version.id') ?: ($version['id'] ?? '')),
            'openapi_schema' => $schema,
        ];

        return $model;
    }

    private function ensureKey(string $provider, string $key): void
    {
        if (blank($key)) throw new RuntimeException("کلید {$provider} برای همگام‌سازی کاتالوگ ثبت نشده است.");
    }

    private function upsertModel(array $data): array
    {
        $data = $this->removeUnavailableWorkflowColumns($data);
        $catalogProvider = (string) ($data['provider'] ?? '');
        $catalogModality = (string) ($data['output_modality'] ?? '');
        $catalogTask = (string) ($data['task_type'] ?? '');
        $catalogModelId = strtolower((string) ($data['external_model_id'] ?? $data['openrouter_model_id'] ?? ''));
        $isReplicateGptImage = $catalogProvider === 'replicate'
            && (str_contains($catalogModelId, 'gpt') || str_contains(strtolower((string) ($data['name'] ?? '')), 'gpt'));

        // همگام‌سازی بعدی نباید مدل‌های جدید متن‌به‌عکس را دوباره از گام دوم
        // و آزمایشگاه پنهان کند. مدل‌های اصلی با migration اولویت می‌گیرند؛
        // مدل‌های تازه با اولویت انتهای فهرست می‌آیند تا ترتیب curated حفظ شود.
        if (in_array($catalogProvider, ['openrouter', 'fal', 'replicate'], true)
            && $catalogModality === 'image'
            && ($catalogTask === 'text_to_image' || $isReplicateGptImage)) {
            $data['featured_in_lab'] = true;
            $data['lab_priority'] = (int) ($data['lab_priority'] ?? 999);
        }

        $model = AiModel::query()
            ->where('provider', $data['provider'])
            ->where(function ($query) use ($data) {
                $query->where('external_model_id', $data['external_model_id'])
                    ->orWhere('openrouter_model_id', $data['external_model_id']);
            })
            ->first();
        $created = !$model;

        if (!$model) {
            $model = new AiModel();
            $model->provider = $data['provider'];
            $model->external_model_id = $data['external_model_id'];
            $model->openrouter_model_id = $data['external_model_id'];
            $model->is_active = true;
        } elseif ($data['provider'] === 'replicate') {
            // همگام‌سازی عمومی Replicate باید schema زنده را تازه کند، اما
            // تنظیمات تخصصی ثبت‌شده برای مدل‌های منتخب (مثل نگاشت input_image
            // و تعداد تصاویر مرجع) نباید با پاسخ عمومی کاتالوگ حذف شود.
            $existingCapabilities = is_array($model->capability_config) ? $model->capability_config : [];
            $incomingCapabilities = is_array($data['capability_config'] ?? null) ? $data['capability_config'] : [];
            foreach (['field_map', 'required_reference_count', 'reference_fields'] as $key) {
                if (array_key_exists($key, $existingCapabilities) && !array_key_exists($key, $incomingCapabilities)) {
                    $incomingCapabilities[$key] = $existingCapabilities[$key];
                }
            }
            $data['capability_config'] = $incomingCapabilities;

            $existingPricing = is_array($model->pricing_config) ? $model->pricing_config : [];
            $incomingPricing = is_array($data['pricing_config'] ?? null) ? $data['pricing_config'] : [];
            $data['pricing_config'] = array_merge($existingPricing, $incomingPricing);

            if (blank($data['default_parameters'] ?? null) && !empty($model->default_parameters)) {
                $data['default_parameters'] = $model->default_parameters;
            }
            if ($model->supports_face_identity && empty($data['supports_face_identity'])) {
                $data['supports_face_identity'] = true;
            }
        }

        $model->fill($data);
        $model->save();
        return [$created ? 1 : 0, $created ? 0 : 1];
    }

    private function falData(string $endpointId, string $category, array $metadata, array $openapi): array
    {
        $isVideo = str_contains($category, 'video');
        $properties = $this->schemaProperties($openapi);
        $allowed = array_values(array_unique(array_merge(['prompt'], array_keys($properties))));
        $supportsImage = str_contains($category, 'image-to-') || $this->hasMediaInput($properties);
        $taskType = match ($category) {
            'image-to-image' => 'image_to_image',
            'text-to-video' => 'text_to_video',
            'image-to-video' => 'image_to_video',
            'video-to-video' => 'video_to_video',
            default => 'text_to_image',
        };
        $blob = strtolower($endpointId . ' ' . json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $faceIdentity = $this->looksLikeFaceModel($blob);
        $supportsVideoInput = $category === 'video-to-video' || $this->hasInputProperty($properties, '/video|frame|motion/');

        return [
            'provider' => 'fal',
            'name' => (string) ($metadata['display_name'] ?? $endpointId),
            'external_model_id' => $endpointId,
            'external_version' => null,
            'provider_name' => 'Fal.ai',
            'output_modality' => $isVideo ? 'video' : 'image',
            'task_type' => $taskType,
            'supports_image_input' => $supportsImage,
            'supports_face_identity' => $faceIdentity,
            'supports_multiple_faces' => (bool) preg_match('/multiple|multi[-_ ]?face|face[-_ ]?count|faces/', $blob),
            'supports_audio' => $this->hasInputProperty($properties, '/audio|voice|sound/'),
            'supports_video_input' => $supportsVideoInput,
            'cost_per_generation' => 0,
            'cost_per_generation_usd' => null,
            'default_width' => 1024,
            'default_height' => 1024,
            'max_resolution' => null,
            'max_duration' => null,
            'default_parameters' => null,
            'input_schema' => $openapi ?: null,
            'capability_config' => [
                'allowed_inputs' => $allowed,
                'supports_text_to_image' => !$isVideo,
                'supports_image_to_image' => $supportsImage,
                'supports_text_to_video' => $isVideo,
                'task_type' => $taskType,
                'supports_face_identity' => $faceIdentity,
                'supports_video_input' => $supportsVideoInput,
            ],
            'pricing_config' => ['source' => 'fal.ai', 'category' => $category],
            'pricing_type' => 'unknown',
            'commercial_use' => null,
            'supports_webhook' => true,
            'terms_url' => $metadata['model_url'] ?? null,
            'data_retention_notes' => 'اطلاعات و schema از کاتالوگ رسمی Fal.ai همگام شده است.',
            'last_verified_at' => now(),
            'description' => (string) ($metadata['description'] ?? "مدل {$endpointId} از Fal.ai؛ دسته‌بندی {$category}."),
        ];
    }

    private function openRouterVideoData(array $remote): array
    {
        $modelId = trim((string) ($remote['id'] ?? ''));
        $frameTypes = array_values(array_filter((array) ($remote['supported_frame_images'] ?? []), 'is_string'));
        $taskType = $frameTypes !== [] ? 'image_to_video' : 'text_to_video';
        $pricing = (array) ($remote['pricing_skus'] ?? []);
        $resolutionTiers = [];

        foreach ((array) ($remote['supported_resolutions'] ?? []) as $resolution) {
            $resolution = (string) $resolution;
            $candidates = [
                'cents_per_video_output_second_' . strtolower($resolution),
                'duration_seconds_' . strtolower($resolution),
                'duration_seconds_' . strtoupper($resolution),
                'text_to_video_duration_seconds_' . strtolower($resolution),
                'image_to_video_duration_seconds_' . strtolower($resolution),
            ];
            foreach ($candidates as $key) {
                if (is_numeric($pricing[$key] ?? null)) {
                    $value = (float) $pricing[$key];
                    if (str_starts_with($key, 'cents_')) $value /= 100;
                    $resolutionTiers[$resolution] = $value;
                    break;
                }
            }
        }

        if ($resolutionTiers === []) {
            foreach (['duration_seconds', 'cents_per_second_output', 'cents_per_video_output_second_480p'] as $key) {
                if (is_numeric($pricing[$key] ?? null)) {
                    $value = (float) $pricing[$key];
                    if (str_starts_with($key, 'cents_')) $value /= 100;
                    $resolutionTiers['default'] = $value;
                    break;
                }
            }
        }

        $qualityScore = match ($modelId) {
            'x-ai/grok-imagine-video', 'x-ai/grok-imagine-video-1.5', 'alibaba/wan-3.0', 'alibaba/wan-3.0-prime' => 8.0,
            default => null,
        };

        $capabilities = [
            'supports_text_to_video' => true,
            'supports_image_to_video' => $frameTypes !== [],
            'supports_text_to_image' => false,
            'allowed_inputs' => ['prompt', 'duration', 'resolution', 'aspect_ratio', 'generate_audio', 'seed', 'frame_images', 'input_references'],
            'supported_durations' => array_values(array_map('intval', (array) ($remote['supported_durations'] ?? []))),
            'supported_resolutions' => array_values(array_map('strval', (array) ($remote['supported_resolutions'] ?? []))),
            'supported_aspect_ratios' => array_values(array_map('strval', (array) ($remote['supported_aspect_ratios'] ?? []))),
            'supported_frame_images' => $frameTypes,
        ];
        if ($qualityScore !== null) $capabilities['quality_score'] = $qualityScore;

        return [
            'provider' => 'openrouter',
            'name' => (string) ($remote['name'] ?? $modelId),
            'external_model_id' => $modelId,
            'external_version' => null,
            'provider_name' => 'OpenRouter',
            'output_modality' => 'video',
            'task_type' => $taskType,
            'supports_image_input' => $frameTypes !== [],
            'supports_face_identity' => false,
            'supports_multiple_faces' => false,
            'supports_audio' => (bool) ($remote['generate_audio'] ?? false),
            'supports_video_input' => false,
            'cost_per_generation' => 0,
            'cost_per_generation_usd' => $resolutionTiers['720p'] ?? $resolutionTiers['default'] ?? null,
            'default_width' => 1280,
            'default_height' => 720,
            'max_resolution' => null,
            'max_duration' => !empty($remote['supported_durations']) ? max(array_map('intval', (array) $remote['supported_durations'])) : null,
            'default_parameters' => null,
            'input_schema' => null,
            'capability_config' => $capabilities,
            'pricing_config' => [
                'source' => 'openrouter.video.models',
                'unit' => 'per_second',
                'resolution_tiers' => $resolutionTiers,
                'pricing_skus' => $pricing,
                'supported_resolutions' => $remote['supported_resolutions'] ?? [],
                'supported_durations' => $remote['supported_durations'] ?? [],
                'supported_aspect_ratios' => $remote['supported_aspect_ratios'] ?? [],
                'supported_frame_images' => $frameTypes,
            ],
            'pricing_type' => 'per_second',
            'commercial_use' => null,
            'supports_webhook' => true,
            'terms_url' => 'https://openrouter.ai/' . $modelId,
            'data_retention_notes' => 'اطلاعات مدل و قیمت از کاتالوگ رسمی ویدیوی OpenRouter همگام شده است.',
            'last_verified_at' => now(),
            'description' => (string) ($remote['description'] ?? "مدل {$modelId} از OpenRouter."),
        ];
    }

    private function replicateData(array $remote, array $classification): array
    {
        $owner = (string) ($remote['owner'] ?? '');
        $name = (string) ($remote['name'] ?? '');
        $modelId = trim($owner . '/' . $name, '/');
        $latest = (array) ($remote['latest_version'] ?? []);
        $schema = (array) ($latest['openapi_schema'] ?? []);
        $properties = $this->schemaProperties($schema);
        $blob = strtolower(json_encode($remote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $referenceFields = $this->replicateReferenceFields($properties);
        // وجود واژهٔ «image» در توضیح یا خروجیِ مدل، به معنی پذیرش عکس
        // ورودی نیست. فقط فیلدهای واقعی schema برای عکس مرجع، مدل را
        // image-to-image می‌کنند تا در آزمایشگاه مدل نامناسب انتخاب نشود.
        $supportsImage = !empty($referenceFields);
        $faceIdentity = $classification['supports_face_identity'];
        $requiredInputs = (array) (data_get($schema, 'components.schemas.Input.required') ?: data_get($schema, 'required') ?: []);
        $requiredReferenceCount = count(array_intersect($requiredInputs, $referenceFields));

        return [
            'provider' => 'replicate',
            'name' => (string) ($remote['name'] ?? $modelId),
            'external_model_id' => $modelId,
            'external_version' => (string) ($latest['id'] ?? ''),
            'provider_name' => 'Replicate',
            'output_modality' => $classification['modality'],
            'task_type' => $classification['task_type'],
            'supports_image_input' => $supportsImage,
            'supports_face_identity' => $faceIdentity,
            'supports_multiple_faces' => (bool) preg_match('/multiple|multi[-_ ]?face|face[-_ ]?count|faces/', $blob),
            'supports_audio' => $classification['supports_audio'],
            'supports_video_input' => $classification['supports_video_input'],
            'cost_per_generation' => 0,
            'cost_per_generation_usd' => null,
            'default_width' => 1024,
            'default_height' => 1024,
            'max_resolution' => null,
            'max_duration' => null,
            'default_parameters' => null,
            'input_schema' => $schema ?: null,
            'capability_config' => [
                'allowed_inputs' => array_values(array_unique(array_merge(['prompt'], array_keys($properties)))),
                'reference_fields' => $referenceFields,
                'required_reference_count' => $requiredReferenceCount,
                'supports_text_to_image' => in_array('prompt', array_keys($properties), true),
                'supports_image_to_image' => $supportsImage,
                'supports_text_to_video' => $classification['task_type'] === 'text_to_video',
                'task_type' => $classification['task_type'],
                'supports_face_identity' => $faceIdentity,
                'supports_video_input' => $classification['supports_video_input'],
            ],
            'pricing_config' => ['source' => 'replicate', 'latest_version' => $latest['id'] ?? null],
            'pricing_type' => 'unknown',
            'commercial_use' => null,
            'supports_webhook' => true,
            'terms_url' => $remote['url'] ?? null,
            'data_retention_notes' => 'اطلاعات و نسخه‌ی مدل از کاتالوگ رسمی Replicate همگام شده است.',
            'last_verified_at' => now(),
            'description' => (string) ($remote['description'] ?? "مدل {$modelId} از Replicate."),
        ];
    }

    private function classifyReplicate(array $remote): ?array
    {
        $latest = (array) ($remote['latest_version'] ?? []);
        $schema = (array) ($latest['openapi_schema'] ?? []);
        $properties = $this->schemaProperties($schema);
        $blob = strtolower(json_encode([
            $remote['name'] ?? '',
            $remote['description'] ?? '',
            $remote['url'] ?? '',
            $schema,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        $hasImageInput = $this->hasMediaInput($properties) || $this->hasInputProperty($properties, '/image|photo|picture|reference|face|identity/');
        $hasVideoInput = $this->hasInputProperty($properties, '/video|frame|motion/');
        $faceIdentity = $this->looksLikeFaceModel($blob);
        $supportsAudio = (bool) preg_match('/audio|voice|sound|speech/', $blob) || $this->hasInputProperty($properties, '/audio|voice|sound/');

        if (preg_match('/video|animation|animate|motion|text-to-video|image-to-video|kling|runway|sora|veo|wan|seedance|luma|hailuo|ltx/', $blob)) {
            return [
                'modality' => 'video',
                'task_type' => $hasImageInput ? 'image_to_video' : 'text_to_video',
                'supports_face_identity' => $faceIdentity,
                'supports_video_input' => $hasVideoInput,
                'supports_audio' => $supportsAudio,
            ];
        }
        if (preg_match('/image|photo|picture|text-to-image|image-to-image|flux|sdxl|stable-diffusion|dall|recraft|ideogram|imagen|nano-banana|upscal|background/', $blob)) {
            return [
                'modality' => 'image',
                'task_type' => $hasImageInput ? 'image_to_image' : 'text_to_image',
                'supports_face_identity' => $faceIdentity,
                'supports_video_input' => false,
                'supports_audio' => $supportsAudio,
            ];
        }
        return null;
    }

    private function schemaProperties(array $schema): array
    {
        return (array) (data_get($schema, 'components.schemas.Input.properties') ?: data_get($schema, 'properties') ?: []);
    }

    private function hasMediaInput(array $properties): bool
    {
        return (bool) preg_match('/image|photo|picture|mask|reference|frame/', strtolower(implode(' ', array_keys($properties))));
    }

    private function replicateReferenceFields(array $properties): array
    {
        return collect(array_keys($properties))
            ->filter(fn (string $field) => (bool) preg_match('/(^|_)(image|images|photo|picture|reference|references|subject|source|frame|frames)(_|$)/i', $field))
            ->values()
            ->all();
    }

    private function hasInputProperty(array $properties, string $pattern): bool
    {
        return (bool) preg_match($pattern, strtolower(implode(' ', array_keys($properties))));
    }

    private function looksLikeFaceModel(string $value): bool
    {
        return (bool) preg_match('/pulid|instantid|photo.?maker|face.?id|ip.?adapter|live.?portrait|face.?consisten|identity|portrait/', strtolower($value));
    }

    private function removeUnavailableWorkflowColumns(array $data): array
    {
        $this->availableWorkflowColumns ??= array_values(array_filter(
            ['task_type', 'supports_face_identity', 'supports_multiple_faces', 'supports_audio', 'supports_video_input', 'max_resolution', 'max_duration', 'pricing_type', 'commercial_use'],
            fn (string $column) => Schema::hasColumn('ai_models', $column)
        ));

        foreach (array_keys(array_diff_key($data, array_flip($this->availableWorkflowColumns))) as $column) {
            if (in_array($column, ['task_type', 'supports_face_identity', 'supports_multiple_faces', 'supports_audio', 'supports_video_input', 'max_resolution', 'max_duration', 'pricing_type', 'commercial_use'], true)) unset($data[$column]);
        }

        return $data;
    }
}
