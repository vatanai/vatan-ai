<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    /**
     * نام کوتاه صرفاً نمایشی برای جدول‌ها، همراه با کد سرویس.
     * شناسه فنی مدل و اتصال محصولات به provider هیچ تغییری نمی‌کند.
     */
    public function shortDisplayName(): string
    {
        $modelId = strtolower((string) $this->openrouter_model_id);

        $knownNames = [
            'openai/gpt-image-1-mini' => 'GPT IM 1 MINI',
            'openai/gpt-image-1' => 'GPT IM 1',
            'openai/gpt-image-1.5' => 'GPT IM 1.5',
            'openai/gpt-image-2' => 'GPT IM 2',
            'openai/gpt-5.4-image-2' => 'GPT 5.4 IM 2',
            'google/gemini-2.5-flash-image' => 'GEM 2.5 FL IM',
            'google/gemini-3-pro-image-preview' => 'GEM 3 PRO IM PREV',
            'google/gemini-3.1-flash-image' => 'GEM 3.1 FL IM',
            'google/gemini-3.1-flash-image-preview' => 'GEM 3.1 FL IM PREV',
            'google/gemini-3.1-flash-lite-image' => 'GEM 3.1 FL LITE IM',
            'google/gemini-3-pro-image' => 'GEM 3 PRO IM',
            'sourceful/riverflow-v2.5-pro' => 'RF 2.5 PRO',
            'sourceful/riverflow-v2.5-fast' => 'RF 2.5 FAST',
            'sourceful/riverflow-v2-pro' => 'RF 2 PRO',
            'sourceful/riverflow-v2-fast' => 'RF 2 FAST',
            'x-ai/grok-imagine-image-quality' => 'GROK IM Q',
            'recraft/recraft-v4.1-vector' => 'RCR 4.1 VEC',
            'recraft/recraft-v4.1-pro-vector' => 'RCR 4.1 PRO VEC',
            'kwaivgi/kling-v2.5-turbo' => 'KLING 2.5 TURBO',
            'runwayml/gen-4-turbo' => 'RW GEN 4 TURBO',
            'luma/dream-machine-2' => 'LUMA DM 2',
        ];

        $shortName = $knownNames[$modelId]
            ?? \Illuminate\Support\Str::limit(
                strtoupper(str_replace(['-', '_'], ' ', basename($modelId))) ?: 'AI MODEL',
                20,
                ''
            );
        $providerPrefix = match ($this->provider) {
            'liara' => 'LR',
            'fal' => 'FAL',
            'replicate' => 'REP',
            default => 'OR',
        };

        return $providerPrefix . ' ' . $shortName;
    }

    protected $table = 'ai_models';

    protected $fillable = [
        'name',
        'openrouter_model_id',
        'external_model_id',
        'external_version',
        'provider_name',
        'output_modality',
        'task_type',
        'supports_image_input',
        'supports_face_identity',
        'supports_multiple_faces',
        'supports_audio',
        'supports_video_input',
        'cost_per_generation',
        'cost_per_generation_usd',
        'default_width',
        'default_height',
        'max_resolution',
        'max_duration',
        'default_parameters',
        'input_schema',
        'capability_config',
        'recommended_category_ids',
        'lab_categories',
        'lab_priority',
        'featured_in_lab',
        'lab_status',
        'lab_description',
        'pricing_config',
        'pricing_type',
        'commercial_use',
        'supports_webhook',
        'terms_url',
        'data_retention_notes',
        'last_verified_at',
        'description',
        'is_active',
        'provider',
        'liara_plan',
    ];

    protected $casts = [
        'task_type' => 'string',
        'default_parameters' => 'array',
        'supports_image_input' => 'boolean',
        'supports_face_identity' => 'boolean',
        'supports_multiple_faces' => 'boolean',
        'supports_audio' => 'boolean',
        'supports_video_input' => 'boolean',
        'is_active' => 'boolean',
        'cost_per_generation' => 'integer',
        'cost_per_generation_usd' => 'decimal:6',
        'default_width' => 'integer',
        'default_height' => 'integer',
        'max_duration' => 'integer',
        'input_schema' => 'array',
        'capability_config' => 'array',
        'recommended_category_ids' => 'array',
        'lab_categories' => 'array',
        'lab_priority' => 'integer',
        'featured_in_lab' => 'boolean',
        'lab_status' => 'string',
        'lab_description' => 'string',
        'pricing_config' => 'array',
        'supports_webhook' => 'boolean',
        'commercial_use' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function externalModelId(): string
    {
        return (string) ($this->external_model_id ?: $this->openrouter_model_id);
    }

    public function labCategoryLabels(): array
    {
        $labels = [
            'popular' => 'پرکاربردترین',
            'identity' => 'بیشترین شباهت',
            'economic' => 'اقتصادی',
            'vip' => 'VIP',
            'experimental' => 'آزمایشی',
        ];

        return collect((array) $this->lab_categories)
            ->map(fn ($category) => $labels[(string) $category] ?? (string) $category)
            ->filter()
            ->values()
            ->all();
    }

    public function capability(string $key, mixed $default = null): mixed
    {
        return data_get($this->capability_config ?: [], $key, $default);
    }

    public function mediaIcon(): string
    {
        return match ($this->output_modality) {
            'video' => 'fa-video',
            'audio' => 'fa-volume-high',
            'text' => 'fa-font',
            default => 'fa-image',
        };
    }

    public function mediaLabel(): string
    {
        return match ($this->output_modality) {
            'video' => 'ویدیو',
            'audio' => 'صوت',
            'text' => 'متن',
            default => 'عکس',
        };
    }

    public function taskLabel(): string
    {
        return match ($this->task_type) {
            'text_to_image' => 'متن به عکس',
            'image_to_image' => 'عکس به عکس',
            'text_to_video' => 'متن به ویدیو',
            'image_to_video' => 'عکس به ویدیو',
            'video_to_video' => 'ویدیو به ویدیو',
            'face_consistency' => 'حفظ هویت چهره',
            'face_animation' => 'متحرک‌سازی چهره',
            'upscaling' => 'افزایش کیفیت',
            default => $this->mediaLabel(),
        };
    }

    /**
     * کاربردهای پیشنهادی از مشخصات واقعی و شناسه‌ی عمومی مدل استخراج می‌شوند.
     * این مقادیر ذخیره نمی‌شوند تا مدل‌های همگام‌سازی‌شده‌ی قدیمی هم بلافاصله
     * در فیلترهای پنل و آزمایشگاه قابل استفاده باشند.
     */
    public function recommendedUseCaseKeys(): array
    {
        $task = (string) $this->task_type;
        $source = strtolower(implode(' ', [
            $this->externalModelId(),
            (string) $this->name,
            (string) $this->description,
        ]));
        $has = static fn (string $pattern): bool => (bool) preg_match($pattern, $source);
        $keys = [];

        if (in_array($task, ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'], true)) {
            $keys[] = 'video';
        }
        if ($this->supports_face_identity || $task === 'face_consistency' || $has('/instantid|instant-id|photomaker|facefusion|face.?id|identity|consistent.?face/')) {
            $keys[] = 'identity';
        }
        if ($has('/portrait|headshot|selfie|face|character.?reference|person.?reference/')) {
            $keys[] = 'portrait';
        }
        if ($has('/product|commercial|advertis|ecommerce|e-commerce|brand|mockup|packshot|catalog|seedream|flux|imagen|gpt.?image|nano.?banana|photon|lucid|bria|recraft|ideogram/')) {
            $keys[] = 'business';
        }
        if ($has('/recraft|ideogram|logo|vector|svg|sticker|poster|typography|text.?render|graphic.?design/')) {
            $keys[] = 'design';
        }

        if (empty($keys) && in_array($task, ['text_to_image', 'image_to_image', 'upscaling'], true)) {
            $keys[] = 'creative';
        }

        return array_values(array_unique($keys));
    }

    public function recommendedUseCases(): array
    {
        $labels = [
            'portrait' => 'چهره و پرتره',
            'identity' => 'حفظ هویت چهره',
            'business' => 'محصول و کسب‌وکار',
            'design' => 'طراحی و متن',
            'creative' => 'تصویرسازی خلاق',
            'video' => 'ویدیو و موشن',
        ];

        return array_values(array_map(
            static fn (string $key): string => $labels[$key] ?? $key,
            $this->recommendedUseCaseKeys()
        ));
    }

    public function primaryUseCaseLabel(): string
    {
        return $this->recommendedUseCases()[0] ?? 'کاربری عمومی';
    }

    /**
     * مدل مناسب جریان اصلی وطن: پرامپت + تصویر مرجع را دریافت می‌کند و تصویر می‌سازد.
     * این معیار عمداً از task_type خام مستقل است، چون بعضی providerها مدل‌های edit
     * را به‌عنوان image_to_image ثبت می‌کنند اما قابلیت واقعی متن به تصویر را هم دارند.
     */
    public function supportsProductImageWorkflow(): bool
    {
        $capabilities = is_array($this->capability_config) ? $this->capability_config : [];
        return $this->output_modality === 'image'
            && $this->is_active
            && (bool) $this->supports_image_input
            && ($this->task_type === 'text_to_image'
                || $this->task_type === 'image_to_image'
                || $this->task_type === 'face_consistency')
            && (bool) data_get($capabilities, 'supports_text_to_image', true);
    }

    public function productWorkflowLabel(): string
    {
        if ($this->supportsProductImageWorkflow()) return 'متن + عکس → عکس';
        return $this->taskLabel();
    }

    /** قابلیت‌های قابل نمایش کنار مدل؛ فقط مواردی که واقعاً در کاتالوگ ثبت شده‌اند. */
    public function capabilityLabels(): array
    {
        $labels = [$this->taskLabel()];

        if ($this->supports_image_input) $labels[] = 'پذیرش عکس ورودی';
        if ($this->supports_face_identity) $labels[] = 'حفظ هویت';
        if ($this->supports_multiple_faces) $labels[] = 'چند چهره';
        if ($this->supports_video_input) $labels[] = 'ورودی ویدیو';
        if ($this->supports_audio) $labels[] = 'صوت';
        if ($this->supports_webhook) $labels[] = 'وب‌هوک';

        return array_values(array_unique($labels));
    }

    /** نام فنی انگلیسی برای جدول‌های انتخاب مدل. */
    public function englishDisplayName(): string
    {
        return $this->externalModelId();
    }

    /** امتیاز کیفی مدیریتی؛ تا زمان ثبت امتیاز واقعی، مقدار نمایشی موقت ۱۰ از ۱۰ است. */
    public function qualityScore(): ?float
    {
        $configured = $this->capability('quality_score')
            ?? data_get($this->pricing_config ?: [], 'quality_score');

        if (is_numeric($configured)) {
            return max(0, min(10, (float) $configured));
        }

        $knownScores = [
            'zsxkib/instant-id' => 9.0,
            'lucataco/modelscope-facefusion' => 9.0,
            'tencentarc/photomaker' => 8.5,
            'black-forest-labs/flux-dev' => 9.0,
            'black-forest-labs/flux-schnell' => 8.0,
            'lucataco/realvisxl-v2.0' => 8.5,
            'lucataco/real-esrgan' => 8.5,
            'sunfjun/stable-video-diffusion' => 7.5,
        ];

        return $knownScores[$this->externalModelId()] ?? 10.0;
    }

    public function qualityGradeLabel(): string
    {
        $score = $this->qualityScore();
        return $score === null ? 'ثبت نشده' : rtrim(rtrim(number_format($score, 1), '0'), '.') . ' از ۱۰';
    }

    /**
     * دریافت آدرس هوشمند لوگو/عکس مدل از پوشه سرور بر اساس شناسه OpenRouter
     */
    public function getImageUrlAttribute()
    {
        $safeName = str_replace(['/', '\\', ':', '*'], '-', $this->openrouter_model_id);

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = 'uploads/models/' . $safeName . '.' . $ext;
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        // تصویر موقت در صورت عدم آپلود عکس اختصاصی
        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100%" height="100%" fill="%23222230"/><text x="50%" y="55%" font-family="sans-serif" font-size="20" fill="%23555570" text-anchor="middle">AI Model</text></svg>';
    }
}
