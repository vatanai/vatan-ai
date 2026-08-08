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

        if (!isset($results[$provider]) && $provider !== 'all') {
            throw new RuntimeException('provider کاتالوگ معتبر نیست. فقط fal، replicate یا all مجاز است.');
        }

        return $results;
    }

    public function syncFal(): array
    {
        $credentials = $this->credentials->for('fal');
        $this->ensureKey('fal', $credentials['api_key']);

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
                    'limit' => 100,
                    'expand' => 'openapi-3.0',
                ];
                if ($cursor) $query['cursor'] = $cursor;

                $response = Http::withHeaders(['Authorization' => 'Key ' . $credentials['api_key']])
                    ->connectTimeout(15)
                    ->timeout(min(60, max(15, (int) $credentials['timeout'])))
                    ->get(rtrim(config('services.fal.platform_base_url', 'https://api.fal.ai'), '/') . '/v1/models', $query);

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
        $this->ensureKey('replicate', $credentials['api_key']);

        $baseUrl = rtrim($credentials['base_url'] ?: 'https://api.replicate.com/v1', '/');
        $collection = Http::withToken($credentials['api_key'])
            ->connectTimeout(15)
            ->timeout(min(60, max(15, (int) $credentials['timeout'])))
            ->get($baseUrl . '/collections/' . rawurlencode($collectionSlug));

        if ($collection->failed()) {
            throw new RuntimeException('Replicate collection HTTP ' . $collection->status() . ': ' . Str::limit($collection->body(), 500));
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ((array) $collection->json('models', []) as $summary) {
            try {
                $remote = $this->hydrateReplicateModel($summary, $baseUrl, $credentials['api_key'], $credentials['timeout']);
                $classification = $this->classifyReplicate($remote);
                if ($classification === null || $classification['modality'] !== 'image') {
                    $skipped++;
                    continue;
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

        $response = Http::withToken($apiKey)
            ->connectTimeout(15)
            ->timeout(min(60, max(15, $timeout)))
            ->get($baseUrl . '/models/' . rawurlencode($owner) . '/' . rawurlencode($name));

        if ($response->failed()) {
            throw new RuntimeException('جزئیات مدل HTTP ' . $response->status() . ': ' . Str::limit($response->body(), 500));
        }

        return (array) $response->json();
    }

    private function ensureKey(string $provider, string $key): void
    {
        if (blank($key)) throw new RuntimeException("کلید {$provider} برای همگام‌سازی کاتالوگ ثبت نشده است.");
    }

    private function upsertModel(array $data): array
    {
        $data = $this->removeUnavailableWorkflowColumns($data);
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
        $supportsImage = !empty($referenceFields) || (bool) preg_match('/image|photo|picture|frame|reference/', $blob);
        $faceIdentity = $classification['supports_face_identity'];
        $requiredInputs = (array) (data_get($schema, 'components.schemas.Input.required') ?: data_get($schema, 'required') ?: []);
        $requiredReferenceCount = count(array_intersect($requiredInputs, $referenceFields));

        return [
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
            ->filter(fn (string $field) => (bool) preg_match('/(^|_)(image|images|photo|picture|reference|references|subject|source)(_|$)/i', $field))
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
