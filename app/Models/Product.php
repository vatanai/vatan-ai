<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    public const OUTPUT_RESOLUTIONS = ['480', '720', '1080', '1440', '2160'];

    public const OUTPUT_RESOLUTION_LABELS = [
        '480' => 'پایین',
        '720' => 'استاندارد',
        '1080' => 'بالاتر',
        '1440' => 'خیلی بالا',
        '2160' => 'حرفه‌ای',
    ];

    public const DEFAULT_OUTPUT_RESOLUTIONS = ['480', '720', '1080'];

    /** رزولوشن پیش‌فرض هر گروه کاربری؛ مدیر می‌تواند برای هر محصول تغییرش دهد. */
    public const DEFAULT_PLAN_OUTPUT_RESOLUTIONS = [
        'free' => '720',
        'paid' => '1080',
    ];

    /** هزینه‌ی پایه‌ی سه سطح کیفیت؛ مقدار محصول می‌تواند آن را override کند. */
    public const DEFAULT_QUALITY_CREDIT_COSTS = [
        'standard' => 12,
        'professional' => 20,
        'best' => 50,
    ];

    // ۱. اضافه کردن تمام فیلدهای اصلی و جدید به fillable جهت پشتیبانی از متدهای دیتابیس
    protected $fillable = [
        'name_fa',
        'name_en',
        'slug',
        'product_code',
        'created_by',
        'updated_by',
        'description_fa',
        'description_en',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'category_id',
        'category',
        'subcategory',
        'status',
        'thumbnail',
        'cover',
        'before_images',
        'sample_outputs',
        'media_type',
        'preview_video_url',
        'primary_model',
        'ai_provider',
        'fallback_models',
        'fallback_model_providers',
        'lab_grade_config',
        'model_configuration',
        'prompt_template',
        'system_prompt',
        'negative_prompt',
        'seed',
        'provider_options',
        'input_schema',
        'timeout',
        'pipeline_type',

        // نوع سوژه و حفظ هویت
        'subject_type',
        'identity_preservation',
        'identity_strength',
        'preserve_body',
        'identity_instructions',
        'identity_instructions_fa',
        'identity_model',
        'identity_model_provider',
        'identity_credit_cost',
        'min_reference_images',
        'max_reference_images',

        'watermark_enabled',
        'watermark_position',
        'pricing_model',
        'credit_cost',
        'display_mode',
        'card_shape',
        'gallery_layout',
        'card_label',
        'card_label_enabled',
        'card_label_position',
        'output_type',
        'output_format',
        'output_count',
        'output_variants',
        'resolution',
        'aspect_ratio',
        'allowed_aspect_ratios',
        'allowed_resolutions',
        'output_quality_selector_enabled',
        'pipeline_enabled',
        'images_optimized_at',
        'delivery_method',
        'estimated_time',
        'last_test_duration_ms',
        'total_test_tokens',
        'price_tier',
        'discount_percentage',
        'platform',
        'accent_color',
        'tags',
        'explore_tiles',
        'base_likes_count',

        // فیلدهای فاز جدید توسعه
        'new_display_order',
        'new_internal_code',
        'new_admin_note',
        'new_is_premium',
        'new_is_recommended',
        'new_is_beta',
        'new_product_icon',
        'new_watermark_corner_precise',
        'new_watermark_opacity',
        'new_watermark_size',
        'new_watermark_type',
        'new_watermark_text_color',
        'new_min_credit_required',
        'new_max_run_per_user',
        'new_show_free_badge',
        'new_price_custom_label'
    ];

    // ۲. تعریف کست‌ها برای تبدیل خودکار آرایه‌ها به JSON موقع ذخیره در دیتابیس
    protected $casts = [
        'sample_outputs'    => 'array',
        'before_images'     => 'array',
        'fallback_models'   => 'array',
        'fallback_model_providers' => 'array',
        'lab_grade_config' => 'array',
        'model_configuration' => 'array',
        'input_schema'      => 'array',
        'output_variants'   => 'array',
        'explore_tiles'     => 'array',
        'provider_options'  => 'array',
        'tags'              => 'array',
        'allowed_aspect_ratios' => 'array',
        'allowed_resolutions' => 'array',
        'output_quality_selector_enabled' => 'boolean',
        'pipeline_enabled' => 'boolean',
        'images_optimized_at' => 'datetime',
        'is_featured'       => 'boolean',
        'is_new'            => 'boolean',
        'is_trending'       => 'boolean',
        'watermark_enabled' => 'boolean',
        'card_label_enabled' => 'boolean',
        'identity_preservation' => 'boolean',
        'preserve_body'     => 'boolean',
        'identity_strength' => 'integer',
        'identity_credit_cost' => 'integer',
        'min_reference_images' => 'integer',
        'max_reference_images' => 'integer',
        'seed'              => 'integer',
        'new_is_premium'    => 'boolean',
        'new_is_recommended'=> 'boolean',
        'new_is_beta'       => 'boolean',
        'new_show_free_badge'=> 'boolean',
        'new_min_credit_required' => 'integer',
        'new_max_run_per_user' => 'integer',
        'last_test_duration_ms' => 'integer',
        'total_test_tokens' => 'integer',
        'base_likes_count'  => 'integer',
        'created_by'        => 'integer',
        'updated_by'        => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $adminId = auth('admin')->id();
            if ($adminId) {
                $product->created_by = $adminId;
                $product->updated_by = $adminId;
            }
        });

        static::updating(function (Product $product): void {
            $adminId = auth('admin')->id();
            if ($adminId) {
                $product->updated_by = $adminId;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** تنظیمات جدید کیفیت خروجی، جدا از معماری قدیمی چهار سطحی مدل. */
    public function qualityModelConfiguration(string $qualityKey, bool $isFreeUser = false): array
    {
        $configuration = (array) ($this->model_configuration ?? []);
        if (data_get($configuration, 'quality_architecture_enabled', true) === false) {
            return [];
        }
        // معماری اصلی سه‌گرید منبع قطعی است؛ مسیر قدیمی free_quality_models
        // فقط برای محصولات قبلی که هنوز مدل استاندارد اصلی ندارند fallback است.
        $selection = data_get($configuration, "quality_models.{$qualityKey}", []);
        if ((!is_array($selection) || empty($selection)) && $isFreeUser && $qualityKey === 'standard') {
            $selection = data_get($configuration, 'free_quality_models.standard', []);
        }

        return is_array($selection) ? $selection : [];
    }

    /**
     * هزینه‌ی سه سطح کیفیت همین محصول. برای محصولات قدیمی که این تنظیم را
     * ندارند، مقدارهای پیش‌فرض فعلی سیستم برگردانده می‌شود تا مسیر ساخت نشکند.
     */
    public function qualityCreditCosts(): array
    {
        $configuration = (array) ($this->model_configuration ?? []);
        $configured = (array) data_get($configuration, 'quality_credit_costs', []);

        return collect(self::DEFAULT_QUALITY_CREDIT_COSTS)
            ->mapWithKeys(function (int $default, string $key) use ($configured): array {
                $value = $configured[$key] ?? null;
                return [$key => (is_numeric($value) && (int) $value > 0) ? (int) $value : $default];
            })
            ->all();
    }

    public function qualityCreditCost(string $qualityKey): int
    {
        return (int) ($this->qualityCreditCosts()[$qualityKey] ?? self::DEFAULT_QUALITY_CREDIT_COSTS['standard']);
    }

    /**
     * آدرس نهایی محصول در URL عمومی: کد ۶ رقمی محصول + اسلاگ.
     * مثال: 546834-concept-sketchbook-portrait
     * اگر به هر دلیلی کد هنوز ساخته نشده باشد (رکورد خیلی قدیمی)، فقط اسلاگ برگردانده می‌شود.
     */
    public function getRouteSlugAttribute(): string
    {
        return $this->product_code ? $this->product_code . '-' . $this->slug : $this->slug;
    }

    /**
     * تنظیمات مدل اختصاصی هر سطح اشتراک برای همین محصول.
     * ساختار قدیمی مدل‌ها برای سازگاری محفوظ می‌ماند اما اجرای جدید از این بخش می‌خواند.
     */
    public function modelTierConfiguration(string $tierKey): array
    {
        $tiers = data_get($this->model_configuration, 'tiers', []);
        $tier = is_array($tiers) ? ($tiers[$tierKey] ?? []) : [];

        return is_array($tier) ? $tier : [];
    }

    /**
     * بایندینگ سفارشی مسیر برای پارامتر route_slug — کد ۶ رقمی ابتدای مقدار را جدا می‌کند
     * و محصول را بر اساس اسلاگ واقعی پیدا می‌کند (برای سازگاری با لینک‌های قدیمی بدون کد هم کار می‌کند).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'route_slug') {
            if (preg_match('/^\d{6}-(.+)$/', (string) $value, $matches)) {
                $value = $matches[1];
            }
            return $this->where('slug', $value)->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * ارتباط با تاریخچه تغییرات پرامپت محصول
     */
    public function promptHistories(): HasMany
    {
        return $this->hasMany(ProductPromptHistory::class)->orderBy('version_number', 'desc');
    }

    /** دسته‌بندی‌های چندگانه (سرشاخه + زیرشاخه‌ها) */
    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    /**
     * سوابق تصاویر ساخته‌شده توسط کاربران با استفاده از این محصول — برای «تعداد ساخت» در صفحه محصول.
     */
    public function generatedImages(): HasMany
    {
        return $this->hasMany(GeneratedImage::class);
    }

    /** خروجی‌های ویدیویی این محصول؛ کاملاً مستقل از گالری generated_images. */
    public function generatedVideos(): HasMany
    {
        return $this->hasMany(GeneratedVideo::class);
    }

    /**
     * اجراهای واقعی این محصول (هر رکورد جدول generations = یک بار اجرای محصول توسط کاربر).
     * مبنای «تعداد اجرا»، کارت «کل اجراها» و مرتب‌سازی «بیشترین اجرا» در پنل ادمین.
     */
    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    /** دانلودهای ثبت‌شده‌ی این محصول برای رتبه‌بندی و نمایش ترندز. */
    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class);
    }

    /** رویدادهای بازدید و بازکردن محصول برای گزارش‌های ترندز. */
    public function metricEvents(): HasMany
    {
        return $this->hasMany(ProductMetricEvent::class);
    }

    public function creditLogs(): HasMany
    {
        return $this->hasMany(ProductCreditLog::class);
    }

    /** آزمایش‌های مدیریتی محصول؛ جدا از اجراهای واقعی کاربران. */
    public function testRuns(): HasMany
    {
        return $this->hasMany(ProductTestRun::class)->latest();
    }

    /** آزمایش‌های ثبت‌شده در آزمایشگاه مستقل محصولات. */
    public function labExperiments(): HasMany
    {
        return $this->hasMany(LabExperiment::class)->latest();
    }

    public function latestLabExperiment(): HasOne
    {
        return $this->hasOne(LabExperiment::class)->ofMany([
            'completed_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->whereIn('status', ['completed', 'evaluated', 'finalized']);
        });
    }

    /**
     * تولید یک کد ۶ رقمی یکتا برای محصول — همان منطق Migration بک‌فیل
     * (2026_07_13_000001_add_product_code_to_products_table) تا محصولات جدید/کپی‌شده هم
     * همیشه کد واقعی داشته باشند.
     */
    public static function generateUniqueProductCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('product_code', $code)->exists());

        return $code;
    }

    /** کاربرانی که این محصول را سیو (ذخیره) کرده‌اند */
    public function savedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_products')->withTimestamps();
    }

    /** کاربرانی که این محصول را لایک کرده‌اند */
    public function likedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'liked_products')->withTimestamps();
    }

    /** عدد نمایشی لایک: عدد پایه‌ی مدیر + لایک‌های واقعی ثبت‌شده کاربران. */
    public function getDisplayedLikesCountAttribute(): int
    {
        $actualLikes = $this->getAttributeFromArray('liked_by_users_count');

        if ($actualLikes === null) {
            $actualLikes = $this->relationLoaded('likedByUsers')
                ? $this->likedByUsers->count()
                : $this->likedByUsers()->count();
        }

        return max(0, (int) $this->base_likes_count) + (int) $actualLikes;
    }

    /**
     * لیست نرمال‌شده «مدل‌های خروجی چندگانه» محصول (Output Variants).
     * فقط ردیف‌های معتبر (دارای عنوان) برگردانده می‌شوند؛ اگر محصول واریانت نداشته باشد آرایه خالی است.
     */
    public function outputVariantList(): array
    {
        $raw = is_array($this->output_variants) ? $this->output_variants : [];
        $out = [];
        foreach ($raw as $v) {
            if (!is_array($v)) continue;
            $title = trim((string) ($v['title'] ?? ''));
            if ($title === '') continue;
            $out[] = [
                'key'    => (string) ($v['key'] ?? ''),
                'title'  => $title,
                'image'  => $v['image'] ?? null,
                'prompt' => trim((string) ($v['prompt'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * آدرس قابل اعتماد تصویر کارت محصول برای هوم/اکسپلور/صفحه محصول.
     * اول Thumbnail واقعی روی دیسک، بعد Cover، در نهایت یک SVG خاکستری inline —
     * هرگز 404 نمی شود، حتی برای رکوردهای قدیمی که مسیر فایلشان از قبل خراب/غایب بوده.
     */
    public function displayImageUrl(): string
    {
        foreach (array_merge([$this->cover], (array) $this->sample_outputs, [$this->thumbnail]) as $path) {
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return asset('storage/' . $path);
            }
            if ($path && !Str::startsWith((string) $path, ['http://', 'https://', 'data:']) && is_file(public_path(ltrim((string) $path, '/')))) {
                return asset(ltrim((string) $path, '/'));
            }
            if ($path && Str::startsWith((string) $path, ['http://', 'https://', 'data:'])) {
                return (string) $path;
            }
        }

        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="100%25" height="100%25" fill="%2315181c"/></svg>';
    }

    public function isVideoProduct(): bool
    {
        return in_array((string) $this->media_type, ['video', 'both'], true)
            && (string) $this->output_type === 'video';
    }

    /**
     * تنظیمات رفتاری محصول ویدیو با پیش‌فرض‌های امن. تنظیمات عکس در کلیدهای
     * دیگر provider_options محفوظ می‌ماند و هرگز بازنویسی نمی‌شود.
     */
    public function videoConfiguration(): array
    {
        $configured = data_get((array) $this->provider_options, 'video', []);
        $configured = is_array($configured) ? $configured : [];

        return array_replace_recursive([
            'workflow' => 'text_to_video',
            'face_profile_mode' => 'disabled',
            'durations' => [4],
            'default_duration' => 4,
            'aspect_ratios' => ['16:9', '9:16', '1:1'],
            'default_aspect_ratio' => '16:9',
            'resolutions' => ['480p', '720p'],
            'default_resolution' => '720p',
            'fps' => 24,
            'motion_presets' => [],
            'audio_allowed' => false,
            'audio_default' => false,
            'prompt_enhance' => true,
            'allow_promotional_credits' => false,
            'credit_costs_by_duration' => [],
            'model_defaults' => [],
        ], $configured);
    }

    public function previewVideoUrl(): ?string
    {
        $path = trim((string) $this->preview_video_url);
        if ($path === '') return null;
        if (Str::startsWith($path, ['http://', 'https://', '/'])) return $path;
        if (is_file(public_path($path))) return asset($path);
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) return asset('storage/' . $path);

        return null;
    }

    public function allowedAspectRatioList(): array
    {
        $allowed = self::supportedAspectRatios();
        $rawConfigured = array_values(array_map('strval', (array) $this->allowed_aspect_ratios));
        $legacyAll = ['auto', '1:1', '9:16', '16:9', '2:3', '3:2', '3:4', '4:3'];
        if (count($rawConfigured) === count($legacyAll) && !array_diff($legacyAll, $rawConfigured) && !array_diff($rawConfigured, $legacyAll)) {
            $rawConfigured = $allowed;
        }
        $configured = array_values(array_intersect($allowed, $rawConfigured));
        if ($configured !== []) return $configured;

        $legacy = in_array((string) $this->aspect_ratio, $allowed, true) ? (string) $this->aspect_ratio : '3:4';
        return [$legacy];
    }

    public static function supportedAspectRatios(): array
    {
        return ['3:4', '4:3', '1:1', '4:5', '9:16', '16:9', '2:3', '3:2'];
    }

    public function allowedResolutionList(): array
    {
        $allowed = self::supportedOutputResolutions();
        $configured = array_map('strval', (array) $this->allowed_resolutions);

        // اگر مدیر برای محصول تنظیمی ذخیره نکرده باشد، رفتار سازگار قدیمی حفظ
        // می‌شود؛ در غیر این صورت دقیقاً همان گزینه‌های انتخاب‌شده معتبر هستند.
        $configured = $configured !== [] ? $configured : self::DEFAULT_OUTPUT_RESOLUTIONS;
        $defaults = array_values($this->outputResolutionDefaults());

        return array_values(array_intersect($allowed, array_unique(array_merge($configured, $defaults))));
    }

    public static function supportedOutputResolutions(): array
    {
        return self::OUTPUT_RESOLUTIONS;
    }

    public function defaultOutputAspectRatio(): string
    {
        $configured = $this->allowedAspectRatioList();
        return in_array('3:4', $configured, true) ? '3:4' : $configured[0];
    }

    public function defaultOutputResolution(): string
    {
        return $this->defaultOutputResolutionForUser(null);
    }

    /**
     * تنظیمات رزولوشن پیش‌فرض محصول برای کاربر رایگان/پلن‌دار.
     * در model_configuration ذخیره می‌شود تا نیاز به migration جدید نباشد.
     */
    public function outputResolutionDefaults(): array
    {
        $raw = data_get($this->model_configuration, 'output_resolution_defaults', []);
        $raw = is_array($raw) ? $raw : [];

        $normalize = static function (mixed $value, string $fallback): string {
            $value = (string) $value;
            return in_array($value, self::supportedOutputResolutions(), true) ? $value : $fallback;
        };

        return [
            'free' => $normalize($raw['free'] ?? null, self::DEFAULT_PLAN_OUTPUT_RESOLUTIONS['free']),
            'paid' => $normalize($raw['paid'] ?? null, self::DEFAULT_PLAN_OUTPUT_RESOLUTIONS['paid']),
        ];
    }

    public function defaultOutputResolutionForUser(?User $user = null): string
    {
        $hasPaidPlan = $user?->plan !== null
            && $user->plan->billing_type !== 'free'
            && (int) $user->plan->price > 0;
        $preferred = $this->outputResolutionDefaults()[$hasPaidPlan ? 'paid' : 'free'];
        $configured = $this->allowedResolutionList();

        return in_array($preferred, $configured, true) ? $preferred : ($configured[0] ?? self::DEFAULT_PLAN_OUTPUT_RESOLUTIONS['free']);
    }
}
