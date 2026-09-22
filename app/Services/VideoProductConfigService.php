<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Product;

class VideoProductConfigService
{
    public const WORKFLOWS = ['text_to_video', 'image_to_video', 'video_to_video'];
    public const PRODUCT_FAMILIES = ['shop', 'face', 'hybrid', 'music_ready'];
    public const ASPECT_RATIOS = ['16:9', '9:16', '1:1', '4:3', '3:4', '4:5', '21:9'];
    public const STUDIO_ASPECT_RATIOS = ['16:9', '9:16', '1:1', '3:4', '4:5', '21:9'];
    public const RESOLUTIONS = ['480p', '720p', '1080p', '4K'];
    public const QUALITY_KEYS = ['standard', 'professional', 'best'];

    public function productFamilyCatalog(): array
    {
        return [
            'shop' => ['label' => 'محصول فروشگاهی', 'description' => 'یک عکس محصول به ویدیوی تبلیغاتی تبدیل می‌شود.'],
            'face' => ['label' => 'محصول چهره‌محور', 'description' => 'عکس چهره یا پروفایل چهره به ویدیو تبدیل می‌شود.'],
            'hybrid' => ['label' => 'محصول ترکیبی', 'description' => 'عکس چهره و عکس محصول هم‌زمان استفاده می‌شوند.'],
            'music_ready' => ['label' => 'ویدیوی آماده با موزیک', 'description' => 'چند پلان با موسیقی ساخته و در یک خروجی مونتاژ می‌شوند.'],
        ];
    }

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
        $productFamily = $this->normalizeProductFamily($data);
        $inputContract = $this->normalizeInputContract($data, $productFamily);
        $promptMode = ($data['prompt_mode'] ?? 'locked') === 'custom' ? 'custom' : 'locked';
        $showPromptToUser = array_key_exists('show_prompt_to_user', $data)
            ? filter_var($data['show_prompt_to_user'], FILTER_VALIDATE_BOOLEAN)
            : $promptMode === 'custom';
        $musicMode = ($data['music_mode'] ?? null) === 'required' || $productFamily === 'music_ready'
            ? 'required'
            : (($data['music_mode'] ?? null) === 'optional' ? 'optional' : 'disabled');
        $videoStructure = in_array($data['video_structure'] ?? null, ['single_shot', 'multi_shot'], true)
            ? (string) $data['video_structure']
            : null;
        $multiShotEnabled = $productFamily === 'music_ready'
            ? true
            : ($videoStructure === 'multi_shot'
                ? true
                : ($videoStructure === 'single_shot'
                    ? false
                    : filter_var($data['multi_shot_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)));
        $maxShots = max(1, min(10, (int) ($data['max_shots'] ?? 6)));
        $qualityTier = in_array($data['quality_tier'] ?? null, self::QUALITY_KEYS, true)
            ? (string) $data['quality_tier']
            : 'standard';
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
            'product_family' => $productFamily,
            'input_contract' => $inputContract,
            'prompt_mode' => $promptMode,
            'show_prompt_to_user' => $showPromptToUser,
            'customer_prompt_allowed' => $promptMode === 'custom' && $showPromptToUser,
            'music_mode' => $musicMode,
            'video_structure' => $multiShotEnabled ? 'multi_shot' : 'single_shot',
            'multi_shot_enabled' => $multiShotEnabled,
            'max_shots' => $maxShots,
            'timeline' => $this->normalizeTimeline($data['timeline'] ?? $data['shots'] ?? [], $maxShots),
            'face_profile_mode' => in_array($data['face_profile_mode'] ?? null, ['disabled', 'optional', 'required'], true) ? $data['face_profile_mode'] : 'disabled',
            'durations' => $durations,
            'default_duration' => in_array((int) ($data['default_duration'] ?? 0), $durations, true) ? (int) $data['default_duration'] : $durations[0],
            'aspect_ratios' => $ratios,
            'preserve_source_aspect_ratio' => filter_var($data['preserve_source_aspect_ratio'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'default_aspect_ratio' => in_array((string) ($data['default_aspect_ratio'] ?? ''), $ratios, true) ? (string) $data['default_aspect_ratio'] : $ratios[0],
            'resolutions' => $resolutions,
            'default_resolution' => in_array((string) ($data['default_resolution'] ?? ''), $resolutions, true) ? (string) $data['default_resolution'] : $resolutions[0],
            'quality_tier' => $qualityTier,
            'fps' => max(4, min(60, (int) ($data['fps'] ?? 24))),
            'motion_presets' => collect($selectedMotion)->map(fn (string $key): array => ['key' => $key] + $catalog[$key])->values()->all(),
            'audio_allowed' => filter_var($data['audio_allowed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'audio_default' => filter_var($data['audio_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'prompt_enhance' => filter_var($data['prompt_enhance'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'credit_costs_by_duration' => $costs,
            'quality_costs' => collect((array) ($data['quality_costs'] ?? []))->mapWithKeys(fn ($value, $key) => [(string) $key => max(0, (int) $value)])->all() + ['480p' => 0, '720p' => 2, '1080p' => 5, '4K' => 10],
            'quality_tiers' => (array) ($data['quality_tiers'] ?? [
                ['key' => 'standard', 'label' => 'استاندارد', 'resolution' => '720p', 'surcharge' => 0],
                ['key' => 'professional', 'label' => 'حرفه‌ای', 'resolution' => '1080p', 'surcharge' => 5],
                ['key' => 'best', 'label' => 'بهترین خروجی', 'resolution' => '4K', 'surcharge' => 10],
            ]),
            'quality_credit_costs' => collect(self::QUALITY_KEYS)->mapWithKeys(function (string $key, int $index) use ($data): array {
                $defaults = [12, 20, 50];
                $value = data_get($data, "quality_credit_costs.{$key}", $defaults[$index]);
                return [$key => max(1, min(1000000, (int) $value))];
            })->all(),
            'model_defaults' => is_array($data['model_defaults'] ?? null) ? $data['model_defaults'] : [],
        ];
    }

    public function normalizeTimeline(mixed $timeline, int $maxShots = 10): array
    {
        if (is_string($timeline)) {
            $timeline = json_decode($timeline, true);
        }
        if (!is_array($timeline)) return [];

        return collect($timeline)->filter(fn ($shot): bool => is_array($shot))->take($maxShots)->values()->map(
            fn (array $shot, int $index): array => [
                'id' => (string) ($shot['id'] ?? 'shot_' . ($index + 1)),
                'title' => trim((string) ($shot['title'] ?? 'پلان ' . ($index + 1))),
                'duration' => max(1, min(15, (int) ($shot['duration'] ?? 4))),
                'prompt' => trim((string) ($shot['prompt'] ?? '')),
                'input_roles' => array_values(array_intersect(['face', 'product', 'reference'], array_map('strval', (array) ($shot['input_roles'] ?? [])))),
                'transition' => trim((string) ($shot['transition'] ?? 'cut')) ?: 'cut',
                'music_start' => max(0, (float) ($shot['music_start'] ?? 0)),
            ],
        )->all();
    }

    private function normalizeProductFamily(array $data): string
    {
        $family = (string) ($data['product_family'] ?? '');
        if (in_array($family, self::PRODUCT_FAMILIES, true)) return $family;
        if (($data['music_mode'] ?? null) === 'required' || filter_var($data['multi_shot_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) return 'music_ready';
        if (($data['face_profile_mode'] ?? 'disabled') !== 'disabled') return 'face';
        return ($data['workflow'] ?? '') === 'image_to_video' ? 'shop' : 'shop';
    }

    private function normalizeInputContract(array $data, string $family): array
    {
        $raw = (array) ($data['input_contract'] ?? []);
        $productImage = array_key_exists('product_image', $raw)
            ? filter_var($raw['product_image'], FILTER_VALIDATE_BOOLEAN)
            : filter_var($data['input_product_image'] ?? null, FILTER_VALIDATE_BOOLEAN);
        $faceImage = array_key_exists('face_image', $raw)
            ? filter_var($raw['face_image'], FILTER_VALIDATE_BOOLEAN)
            : filter_var($data['input_face_image'] ?? null, FILTER_VALIDATE_BOOLEAN);
        $faceProfile = array_key_exists('face_profile', $raw)
            ? filter_var($raw['face_profile'], FILTER_VALIDATE_BOOLEAN)
            : filter_var($data['allow_face_profile'] ?? null, FILTER_VALIDATE_BOOLEAN);

        if ($family === 'shop') [$productImage, $faceImage] = [true, false];
        if ($family === 'face') [$productImage, $faceImage] = [false, true];
        if ($family === 'hybrid') [$productImage, $faceImage] = [true, true];
        if ($family === 'music_ready' && !$productImage && !$faceImage) $productImage = true;

        return [
            'product_image' => $productImage,
            'face_image' => $faceImage,
            'face_profile' => $faceProfile && $faceImage,
            'product_required' => $productImage,
            'face_required' => $faceImage,
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

    public function creditCost(Product $product, int $duration, ?string $resolution = null, bool $audio = false, bool $identity = false, ?string $quality = null): int
    {
        $configured = (array) data_get($product->videoConfiguration(), 'credit_costs_by_duration', []);
        $value = $configured[(string) $duration] ?? null;

        $base = is_numeric($value) ? max(0, (int) $value) : max(0, (int) $product->credit_cost);
        $config = $product->videoConfiguration();
        $qualitySurcharge = (array) data_get($config, 'quality_costs', []);
        $tierCosts = (array) data_get($config, 'quality_credit_costs', []);
        if ($quality && isset($tierCosts[$quality])) {
            $defaultDuration = (int) ($config['default_duration'] ?? $duration);
            $defaultDurationBase = is_numeric($configured[(string) $defaultDuration] ?? null)
                ? max(0, (int) $configured[(string) $defaultDuration])
                : max(0, (int) $product->credit_cost);
            $qualityCost = (int) $tierCosts[$quality] + ($base - $defaultDurationBase);
        } else {
            $qualityCost = $base + (int) ($qualitySurcharge[$resolution ?: ''] ?? 0);
        }
        return max(0, $qualityCost + ($audio ? 3 : 0) + ($identity ? 2 : 0));
    }
}
