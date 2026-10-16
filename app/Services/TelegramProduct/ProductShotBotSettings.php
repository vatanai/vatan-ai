<?php

namespace App\Services\TelegramProduct;

use App\Models\AiModel;
use App\Models\ProductShotSetting;
use App\Models\TelegramProductSetting;
use App\Services\ProductShots\ShotVisionService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات مسیر «پروداکتی» بات ثبت محصول — همه از داشبورد (تنظیمات ← بات ثبت محصول ← پروداکتی).
 *
 * یک JSON در telegram_product_settings با کلید productshot_bot ذخیره می‌شود؛ هر مقداری که
 * ذخیره نشده باشد از defaults() می‌آید، پس افزودن تنظیم جدید هیچ‌وقت داده‌ی قبلی را خراب نمی‌کند.
 */
class ProductShotBotSettings
{
    public const KEY = 'productshot_bot';
    public const TILES = ['1x1' => '۱×۱ مربع', '2x2' => '۲×۲ بزرگ', '1x2' => '۱×۲ عمودی', '2x1' => '۲×۱ افقی'];

    /** متن پیام‌های بات؛ {n} شماره‌ی اسلاید، {max} سقف اسلاید، {count} تعداد. */
    public const TEXT_LABELS = [
        'type_choice' => ['انتخاب نوع محصول', 'اولین پیام بعد از «ثبت محصول»'],
        'input_image' => ['گام عکس ورودی', 'درخواست عکس ورودی (همان عکسی که اسلایدها با آن ساخته شده‌اند)'],
        'slide_image' => ['عکس اسلاید', 'درخواست عکس نمونه‌ی هر اسلاید — {n} شماره‌ی اسلاید'],
        'slide_prompt' => ['پرامپت اسلاید', 'درخواست پرامپت ساخت اسلاید — {n} شماره‌ی اسلاید'],
        'slides_menu' => ['اسلاید بعدی یا پایان', 'بعد از دریافت پرامپت — {n} شماره‌ی اسلاید، {count} تعداد اسلایدها'],
        'processing' => ['در حال آماده‌سازی', 'پیام انتظار هنگام کار هوش مصنوعی'],
        'review' => ['بازبینی', 'متن بالای پیش‌نمایش نهایی قبل از ثبت'],
        'final_published' => ['پیام نهایی انتشار', 'تنها پیامی که بعد از ثبت در چت می‌ماند'],
        'final_draft' => ['پیام نهایی پیش‌نویس', 'تنها پیامی که بعد از ذخیره‌ی پیش‌نویس می‌ماند'],
    ];

    private ?array $cache = null;

    public function defaults(): array
    {
        $global = $this->globalShotSettings();

        return [
            'ai' => [
                'model' => (string) config('services.telegram_product.ai_model', 'openai/gpt-4o-mini'),
                'use_vision' => true,
                'metadata_prompt' => self::defaultMetadataPrompt(),
                'optimizer_enabled' => true,
                'optimizer_prompt' => self::defaultOptimizerPrompt(),
            ],
            'slides' => [
                'max_slides' => 12,
                'max_prompt_chars' => 20000,
                'credits' => ['standard' => 12, 'professional' => 18, 'best' => 24],
                'allowed_aspect_ratios' => array_values((array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16'])),
                'aspect_ratio_default' => (string) config('product_shots.default_aspect_ratio', '4:5'),
                'aspect_ratio_user_selectable' => true,
            ],
            'models' => [
                // خالی = همان مدل‌های آخرین محصول پروداکتی ثبت‌شده در پنل
                'quality' => collect(array_keys($this->qualityLevels()))->mapWithKeys(fn ($q) => [$q => ['primary_id' => null, 'fallback_ids' => []]])->all(),
            ],
            'quality' => [
                'preflight_enabled' => (bool) ($global['preflight_enabled'] ?? true),
                'blocking_issues' => array_values(array_unique(array_merge((array) ($global['preflight_blocking_issues'] ?? []), ShotVisionService::BLOCKING))),
                'min_side' => (int) ($global['preflight_min_side'] ?? config('product_shots.preflight_min_side', 900)),
                'product_sheet_enabled' => (bool) ($global['product_sheet_enabled'] ?? true),
                'product_sheet_size' => (int) ($global['product_sheet_size'] ?? config('product_shots.product_sheet_size', 2048)),
            ],
            'display' => [
                'watermark_enabled' => false,
                'display_mode' => 'card',
                'card_shape' => 'portrait',
                'gallery_layout' => 'slider',
                'card_label' => 'پک',
                'explore_tiles' => ['1x1'],
            ],
            'texts' => self::defaultTexts(),
        ];
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $saved = [];
        if (Schema::hasTable('telegram_product_settings')) {
            $raw = TelegramProductSetting::value(self::KEY);
            $decoded = $raw ? json_decode($raw, true) : null;
            $saved = is_array($decoded) ? $decoded : [];
        }

        return $this->cache = $this->merge($this->defaults(), $saved);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    public function text(string $key, array $replace = []): string
    {
        $text = trim((string) $this->get("texts.{$key}", ''));
        if ($text === '') {
            $text = (string) (self::defaultTexts()[$key] ?? '');
        }

        return strtr($text, collect($replace)->mapWithKeys(fn ($v, $k) => ['{' . $k . '}' => (string) $v])->all());
    }

    public function save(array $values): void
    {
        $merged = $this->merge($this->defaults(), $values);
        TelegramProductSetting::put(self::KEY, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->cache = null;
    }

    public function qualityLevels(): array
    {
        return (array) config('product_shots.quality_levels', ['standard' => 'استاندارد']);
    }

    /** اعتبار پیش‌فرض اسلاید اختصاصی برای هر سطح کیفیت. */
    public function slideCredits(): array
    {
        $credits = (array) $this->get('slides.credits', []);

        return collect(array_keys($this->qualityLevels()))
            ->mapWithKeys(fn ($q) => [$q => max(0, (int) ($credits[$q] ?? $credits['standard'] ?? 12))])
            ->all();
    }

    public function maxSlides(): int
    {
        return max(1, min(30, (int) $this->get('slides.max_slides', 12)));
    }

    public function maxPromptChars(): int
    {
        return max(500, min(30000, (int) $this->get('slides.max_prompt_chars', 20000)));
    }

    /**
     * مدل‌های سه سطح کیفیت از تنظیم داشبورد (ردیف کامل، مثل فرم استودیو محصول).
     * سطحی که مدل ندارد حذف می‌شود تا سازنده‌ی محصول از پیش‌فرض عمومی پُرش کند.
     */
    public function qualityModels(): array
    {
        $resolved = [];
        foreach (array_keys($this->qualityLevels()) as $quality) {
            $row = (array) $this->get("models.quality.{$quality}", []);
            $primaryId = (int) ($row['primary_id'] ?? 0);
            if ($primaryId <= 0) {
                continue;
            }
            $primary = AiModel::query()->where('is_active', true)->find($primaryId);
            if (! $primary) {
                continue;
            }
            $fallbacks = AiModel::query()
                ->where('is_active', true)
                ->whereIn('id', array_values(array_unique(array_map('intval', (array) ($row['fallback_ids'] ?? [])))))
                ->where('id', '!=', $primary->id)
                ->get();
            $resolved[$quality] = [
                'primary_id' => $primary->id,
                'primary_model' => (string) $primary->openrouter_model_id,
                'primary_provider' => (string) $primary->provider,
                'fallback_ids' => $fallbacks->pluck('id')->all(),
                'fallback_models' => $fallbacks->pluck('openrouter_model_id')->map(fn ($v) => (string) $v)->values()->all(),
                'fallback_providers' => $fallbacks->pluck('provider')->map(fn ($v) => (string) $v)->values()->all(),
            ];
        }

        return $resolved;
    }

    public static function defaultTexts(): array
    {
        return [
            'type_choice' => "چه نوع محصولی می‌خواهی ثبت کنی؟\n\n🧑 <b>پرتره (چهره‌محور)</b>\nکاربر عکس چهره‌ی خودش را می‌دهد؛ همان مسیر همیشگی.\n\n📦 <b>پروداکتی (عکس محصول)</b>\nکاربر عکس محصولش را می‌دهد و اسلایدهای تبلیغاتی همین پک را می‌گیرد.",
            'input_image' => "📦 <b>ثبت محصول پروداکتی</b>\n\n<b>گام ۱: عکس ورودی</b>\nهمان عکس محصولی را بفرست که اسلایدها با آن ساخته شده‌اند. این عکس در صفحه‌ی محصول با برچسب «عکس ورودی» به کاربر نشان داده می‌شود تا بداند چه عکسی بدهد (در صفحه‌ی ساخت دیده نمی‌شود).\n\n• فقط یک عکس\n• هم «عکس» (Photo) و هم «فایل» قبول است؛ برای بهترین کیفیت «فایل» بفرست.\n• عکس‌ها مثل ثبت از داشبورد خودکار بهینه می‌شوند (WebP، حداکثر ۱۶۰۰ پیکسل، حجم کم).",
            'slide_image' => "🖼 <b>اسلاید {n}</b>\n\nعکس نمونه‌ی این اسلاید را بفرست (خروجی‌ای که کاربر از این اسلاید می‌گیرد).\nاسلاید اول کاور کارت محصول می‌شود.\nبرای بهترین کیفیت، عکس را «به‌صورت فایل» بفرست؛ خودکار بهینه می‌شود.",
            'slide_prompt' => "✍️ <b>پرامپت ساخت اسلاید {n}</b>\n\nپرامپت کامل را بفرست. اگر طولانی است، بی‌خیال: تلگرام آن را چند تکه می‌کند و همه‌ی تکه‌ها پشت سر هم به همین اسلاید اضافه می‌شوند.\nبعد از آخرین تکه، دکمه‌ی پایین را بزن.",
            'slides_menu' => "✅ <b>اسلاید {n} کامل شد.</b>\n\nتا الان {count} اسلاید. اسلاید بعدی را اضافه کن یا ثبت را تمام کن.",
            'processing' => "⏳ <b>در حال آماده‌سازی هوشمند…</b>\n\nاصلاح پرامپت اسلایدها، نام، توضیح، هشتگ‌ها، صنف و دسته‌بندی در حال ساخت است. نتیجه خودش برایت می‌آید.",
            'review' => "🧾 <b>بازبینی نهایی</b>\nهمه‌چیز درست است؟ ثبت کن، یا هر بخش را اصلاح کن.",
            'final_published' => "✅ <b>محصول پروداکتی ثبت و منتشر شد.</b>",
            'final_draft' => "✅ <b>محصول پروداکتی در پیش‌نویس ذخیره شد.</b>",
        ];
    }

    public static function defaultMetadataPrompt(): string
    {
        return "تو کارشناس محتوای فروشگاهی و عکاسی تبلیغاتی محصول در پلتفرم «وطن» هستی.\n"
            . "مدیر یک «محصول پروداکتی» ثبت می‌کند: کاربر (یک کسب‌وکار) عکس محصول خودش را می‌دهد و هر اسلاید این محصول، یک عکس تبلیغاتی حرفه‌ای از همان محصول می‌سازد.\n\n"
            . "از روی پرامپت اسلایدها (و عکس‌ها اگر پیوست شده)، اطلاعات فروشگاهی محصول را بساز:\n"
            . "- نام فارسی: کوتاه، جذاب و دقیق (حداکثر ۶ کلمه)، بدون تکرار کلمه‌ی «پک» در ابتدا.\n"
            . "- نام انگلیسی: کوتاه و طبیعی.\n"
            . "- توضیح فارسی: ۲ تا ۳ جمله‌ی روان برای کاربر؛ چه عکس‌هایی می‌گیرد و برای کدام کسب‌وکار و کجا مناسب است.\n"
            . "- هشتگ‌ها: حداکثر ۸ هشتگ فارسی مرتبط.\n"
            . "- نام هر اسلاید: ۲ تا ۴ کلمه‌ی فارسی که صحنه‌ی همان اسلاید را توصیف کند.\n"
            . "- صنف‌ها و دسته‌بندی را فقط از فهرست‌های مجاز انتخاب کن.\n"
            . "لحن: حرفه‌ای، ساده و فروش‌محور. هیچ ادعای غیرواقعی ننویس.";
    }

    public static function defaultOptimizerPrompt(): string
    {
        return "You are a senior prompt editor for photorealistic product-photography image models.\n"
            . "Rewrite the user's prompt so an image model follows it more reliably, while keeping every creative decision exactly as written.\n\n"
            . "Rules:\n"
            . "- Keep the same scene, subject, composition, camera, lighting, props, colors, mood and any text instructions. Do not add new ideas, objects or styles.\n"
            . "- Fix grammar, remove contradictions and repetitions, and order the details clearly (subject, scene, composition, lighting, mood, constraints).\n"
            . "- Keep any placeholders in curly braces (for example {product}) unchanged.\n"
            . "- If part of the prompt is in Persian, translate it faithfully to English.\n"
            . "- The product in the reference image must stay exactly the same; never ask to change it.\n"
            . "- Output only the final prompt text, with no explanations, quotes or markdown.";
    }

    private function globalShotSettings(): array
    {
        try {
            if (! Schema::hasTable('product_shot_settings')) {
                return [];
            }

            return ProductShotSetting::current()->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /** ادغام بازگشتی؛ لیست‌ها (آرایه‌ی ترتیبی) جایگزین می‌شوند، نه ادغام. */
    private function merge(array $base, array $over): array
    {
        foreach ($over as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && ! array_is_list($base[$key]) && ! array_is_list($value)) {
                $base[$key] = $this->merge($base[$key], $value);
            } elseif (array_key_exists($key, $base) || ! is_int($key)) {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
