<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GeneratedImage;

class Product extends Model
{
    // ۱. اضافه کردن تمام فیلدهای اصلی و جدید به fillable جهت پشتیبانی از متدهای دیتابیس
    protected $fillable = [
        'name_fa',
        'name_en',
        'slug',
        'product_code',
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
        'fallback_models',
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
        'output_type',
        'output_format',
        'output_count',
        'output_variants',
        'resolution',
        'aspect_ratio',
        'allowed_aspect_ratios',
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
        'input_schema'      => 'array',
        'output_variants'   => 'array',
        'explore_tiles'     => 'array',
        'provider_options'  => 'array',
        'tags'              => 'array',
        'allowed_aspect_ratios' => 'array',
        'is_featured'       => 'boolean',
        'is_new'            => 'boolean',
        'is_trending'       => 'boolean',
        'watermark_enabled' => 'boolean',
        'identity_preservation' => 'boolean',
        'preserve_body'     => 'boolean',
        'identity_strength' => 'integer',
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
    ];

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

    /**
     * اجراهای واقعی این محصول (هر رکورد جدول generations = یک بار اجرای محصول توسط کاربر).
     * مبنای «تعداد اجرا»، کارت «کل اجراها» و مرتب‌سازی «بیشترین اجرا» در پنل ادمین.
     */
    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    /** آزمایش‌های مدیریتی محصول؛ جدا از اجراهای واقعی کاربران. */
    public function testRuns(): HasMany
    {
        return $this->hasMany(ProductTestRun::class)->latest();
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
        }

        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="100%25" height="100%25" fill="%2315181c"/></svg>';
    }

    public function allowedAspectRatioList(): array
    {
        $allowed = ['1:1', '4:5', '3:4', '9:16', '16:9', '3:2', '2:3'];
        $schemaField = collect((array) $this->input_schema)->first(
            fn ($field) => is_array($field) && ($field['type'] ?? null) === 'aspect_ratio'
        );
        $schemaRatios = collect((array) ($schemaField['options'] ?? []))
            ->pluck('value')->map(fn ($value) => (string) $value)->all();
        $fromSchema = array_values(array_intersect($allowed, $schemaRatios));
        if ($fromSchema !== []) return $fromSchema;

        $configured = array_values(array_intersect($allowed, array_map('strval', (array) $this->allowed_aspect_ratios)));
        $legacy = in_array((string) $this->aspect_ratio, $allowed, true) ? (string) $this->aspect_ratio : '1:1';
        return $configured ?: [$legacy];
    }
}
