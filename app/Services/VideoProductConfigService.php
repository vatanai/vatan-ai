<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Product;

class VideoProductConfigService
{
    public const WORKFLOWS = ['text_to_video', 'image_to_video', 'video_to_video'];
    public const ASPECT_RATIOS = ['16:9', '9:16', '1:1', '4:3', '3:4', '4:5', '21:9'];
    public const STUDIO_ASPECT_RATIOS = ['16:9', '9:16', '1:1', '3:4', '4:5', '21:9'];
    public const RESOLUTIONS = ['480p', '720p', '1080p', '4K'];

    public function motionPresetCatalog(): array
    {
        return [
            'static' => ['label' => 'قاب ثابت', 'description' => 'حرکت سوژه در قاب ثابت', 'prompt' => 'Locked-off camera, stable composition, natural subject motion.'],
            'dolly_in' => ['label' => 'حرکت رو به جلو', 'description' => 'نزدیک‌شدن نرم دوربین', 'prompt' => 'Slow cinematic dolly-in toward the subject, smooth controlled camera motion.'],
            'dolly_out' => ['label' => 'حرکت رو به عقب', 'description' => 'بازشدن تدریجی قاب', 'prompt' => 'Slow dolly-out revealing the wider environment, smooth cinematic motion.'],
            'orbit' => ['label' => 'چرخش دور سوژه', 'description' => 'حرکت مداری نرم', 'prompt' => 'Smooth orbital camera move around the subject with consistent identity and geometry.'],
            'pan' => ['label' => 'پن افقی', 'description' => 'حرکت افقی کنترل‌شده', 'prompt' => 'Controlled cinematic horizontal pan, steady speed and natural parallax.'],
            'handheld' => ['label' => 'دوربین روی دست', 'description' => 'حرکت مستند و طبیعی', 'prompt' => 'Subtle handheld documentary camera movement, realistic micro-motion without jitter.'],
            'crane' => ['label' => 'حرکت کرین', 'description' => 'حرکت عمودی و باشکوه', 'prompt' => 'Elegant crane-up camera move revealing scale and depth, cinematic pacing.'],
            'zoom' => ['label' => 'زوم سینمایی', 'description' => 'تأکید تدریجی روی سوژه', 'prompt' => 'Gentle optical zoom with cinematic focus pull and stable subject details.'],
            'tilt' => ['label' => 'تیلت عمودی', 'description' => 'حرکت نرم از بالا به پایین', 'prompt' => 'Smooth vertical tilt following the subject from top to bottom, stable cinematic motion.'],
        ];
    }

    public function normalize(array $data): array
    {
        $workflow = in_array($data['workflow'] ?? null, self::WORKFLOWS, true)
            ? (string) $data['workflow']
            : 'text_to_video';
        $durations = collect((array) ($data['durations'] ?? []))
            ->map(fn ($value): int => max(1, min(15, (int) $value)))
            ->unique()->sort()->values()->all();
        $durations = $durations ?: [4];
        $ratios = array_values(array_intersect(self::ASPECT_RATIOS, array_map('strval', (array) ($data['aspect_ratios'] ?? []))));
        $ratios = $ratios ?: ['16:9'];
        $resolutions = array_values(array_intersect(self::RESOLUTIONS, array_map('strval', (array) ($data['resolutions'] ?? []))));
        $resolutions = $resolutions ?: ['720p'];
        $catalog = $this->motionPresetCatalog();
        $selectedMotion = array_values(array_intersect(array_keys($catalog), array_map('strval', (array) ($data['motion_presets'] ?? []))));
        $costs = [];
        foreach ($durations as $duration) {
            $costs[(string) $duration] = max(0, (int) data_get($data, "credit_costs_by_duration.{$duration}", $data['credit_cost'] ?? 0));
        }

        return [
            'workflow' => $workflow,
            'face_profile_mode' => in_array($data['face_profile_mode'] ?? null, ['disabled', 'optional', 'required'], true) ? $data['face_profile_mode'] : 'disabled',
            'durations' => $durations,
            'default_duration' => in_array((int) ($data['default_duration'] ?? 0), $durations, true) ? (int) $data['default_duration'] : $durations[0],
            'aspect_ratios' => $ratios,
            'default_aspect_ratio' => in_array((string) ($data['default_aspect_ratio'] ?? ''), $ratios, true) ? (string) $data['default_aspect_ratio'] : $ratios[0],
            'resolutions' => $resolutions,
            'default_resolution' => in_array((string) ($data['default_resolution'] ?? ''), $resolutions, true) ? (string) $data['default_resolution'] : $resolutions[0],
            'fps' => max(4, min(60, (int) ($data['fps'] ?? 24))),
            'motion_presets' => collect($selectedMotion)->map(fn (string $key): array => ['key' => $key] + $catalog[$key])->values()->all(),
            'audio_allowed' => filter_var($data['audio_allowed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'audio_default' => filter_var($data['audio_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'prompt_enhance' => filter_var($data['prompt_enhance'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'allow_promotional_credits' => filter_var($data['allow_promotional_credits'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'credit_costs_by_duration' => $costs,
            'quality_costs' => collect((array) ($data['quality_costs'] ?? []))->mapWithKeys(fn ($value, $key) => [(string) $key => max(0, (int) $value)])->all() + ['480p' => 0, '720p' => 2, '1080p' => 5, '4K' => 10],
            'quality_tiers' => (array) ($data['quality_tiers'] ?? [
                ['key' => 'standard', 'label' => 'استاندارد', 'resolution' => '720p', 'surcharge' => 0],
                ['key' => 'professional', 'label' => 'حرفه‌ای', 'resolution' => '1080p', 'surcharge' => 5],
                ['key' => 'best', 'label' => 'بهترین خروجی', 'resolution' => '4K', 'surcharge' => 10],
            ]),
            'model_defaults' => is_array($data['model_defaults'] ?? null) ? $data['model_defaults'] : [],
        ];
    }

    public function compatible(AiModel $model, string $workflow): bool
    {
        return match ($workflow) {
            'text_to_video' => $model->task_type === 'text_to_video',
            'image_to_video' => in_array($model->task_type, ['image_to_video', 'face_animation'], true),
            'video_to_video' => $model->task_type === 'video_to_video',
            default => false,
        };
    }

    public function creditCost(Product $product, int $duration, ?string $resolution = null, bool $audio = false, bool $identity = false): int
    {
        $configured = (array) data_get($product->videoConfiguration(), 'credit_costs_by_duration', []);
        $value = $configured[(string) $duration] ?? null;

        $base = is_numeric($value) ? max(0, (int) $value) : max(0, (int) $product->credit_cost);
        $quality = (array) data_get($product->videoConfiguration(), 'quality_costs', []);
        return max(0, $base + (int) ($quality[$resolution ?: ''] ?? 0) + ($audio ? 3 : 0) + ($identity ? 2 : 0));
    }
}
