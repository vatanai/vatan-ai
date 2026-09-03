<?php

namespace App\Services;

use App\Models\AiModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class VideoModelSchemaService
{
    public function inputSchema(AiModel $model): array
    {
        $document = (array) ($model->input_schema ?? []);
        $schema = data_get($document, 'components.schemas.Input');
        if (is_array($schema)) return $this->resolveSchema($document, $schema);

        $paths = (array) ($document['paths'] ?? []);
        foreach ($paths as $path) {
            $requestSchema = data_get($path, 'post.requestBody.content.application/json.schema');
            if (is_array($requestSchema)) {
                $resolved = $this->resolveSchema($document, $requestSchema);
                if (!empty($resolved['properties'])) return $resolved;
            }
        }

        $schemas = (array) data_get($document, 'components.schemas', []);
        foreach ($schemas as $name => $candidate) {
            if (Str::endsWith(Str::lower((string) $name), 'input') && is_array($candidate)) {
                return $this->resolveSchema($document, $candidate);
            }
        }

        return is_array($document['properties'] ?? null) ? $document : [];
    }

    public function properties(AiModel $model): array
    {
        return (array) ($this->inputSchema($model)['properties'] ?? []);
    }

    public function summarize(AiModel $model): array
    {
        $properties = $this->properties($model);
        $fields = array_keys($properties);
        $imageFields = $this->matchingFields($fields, [
            'image_url', 'image_urls', 'image', 'images', 'input_image', 'start_image',
            'first_frame', 'reference_image', 'reference_images', 'subject_image',
        ]);
        $videoFields = $this->matchingFields($fields, ['video_url', 'video', 'input_video', 'source_video']);
        $audioFields = $this->matchingFields($fields, ['audio_url', 'audio', 'input_audio']);

        return [
            'task_type' => (string) $model->task_type,
            'fields' => $fields,
            'image_fields' => $imageFields,
            'video_fields' => $videoFields,
            'audio_fields' => $audioFields,
            'supports_image' => $imageFields !== [] || $model->supports_image_input || $model->task_type === 'image_to_video',
            'supports_video' => $videoFields !== [] || $model->supports_video_input || $model->task_type === 'video_to_video',
            'supports_audio' => $audioFields !== [] || array_key_exists('generate_audio', $properties) || $model->supports_audio,
            'supports_first_last_frame' => collect($fields)->contains(fn (string $field): bool => in_array($field, ['end_image', 'last_frame', 'end_frame'], true)),
            'durations' => $this->enumValues($properties['duration'] ?? null),
            'resolutions' => $this->enumValues($properties['resolution'] ?? null),
            'aspect_ratios' => $this->enumValues($properties['aspect_ratio'] ?? null),
            'defaults' => collect($properties)
                ->mapWithKeys(fn ($schema, $field): array => is_array($schema) && array_key_exists('default', $schema) ? [$field => $schema['default']] : [])
                ->all(),
        ];
    }

    public function fieldFor(AiModel $model, array $candidates): ?string
    {
        $fields = array_keys($this->properties($model));
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) return $candidate;
        }

        return null;
    }

    /** فقط گزینه‌های واقعاً اعلام‌شده در schema به provider فرستاده می‌شوند. */
    public function sanitizeInput(AiModel $model, array $input): array
    {
        $properties = $this->properties($model);
        if ($properties === []) return array_filter($input, fn ($value) => $value !== null && $value !== '');

        return collect($input)
            ->filter(function ($value, string $key) use ($properties): bool {
                if (!array_key_exists($key, $properties) || $value === null || $value === '') return false;
                $allowed = $this->enumValues($properties[$key]);
                return $allowed === [] || in_array((string) $value, array_map('strval', $allowed), true);
            })
            ->all();
    }

    private function resolveSchema(array $document, array $schema): array
    {
        $reference = $schema['$ref'] ?? null;
        if (!is_string($reference) || !str_starts_with($reference, '#/')) return $schema;

        $path = str_replace('/', '.', substr($reference, 2));
        $resolved = data_get($document, $path);

        return is_array($resolved) ? $resolved : $schema;
    }

    private function enumValues(mixed $schema): array
    {
        if (!is_array($schema)) return [];
        $direct = $schema['enum'] ?? null;
        if (is_array($direct)) return array_values($direct);

        foreach ((array) ($schema['anyOf'] ?? []) as $candidate) {
            if (is_array($candidate['enum'] ?? null)) return array_values($candidate['enum']);
        }

        return [];
    }

    private function matchingFields(array $fields, array $candidates): array
    {
        return array_values(array_intersect($candidates, $fields));
    }
}
