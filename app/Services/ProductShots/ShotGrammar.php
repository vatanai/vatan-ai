<?php

namespace App\Services\ProductShots;

/**
 * «زبان شات» (Shot Grammar): واژگان ثابت توکن‌ها در هر محور و عبارت انگلیسی
 * متناظر برای پرامپت. شات‌ها به‌جای پرامپت آزاد، ترکیبی از همین توکن‌ها هستند
 * تا همه‌ی شات‌های یک پک حس و کیفیت یکدست داشته باشند و ادمین با تیک‌زدن
 * توکن‌ها شات جدید بسازد.
 */
class ShotGrammar
{
    /** ترتیب محورها همان ترتیب جمله‌های پرامپت است. */
    public const AXES = ['framing', 'camera', 'lens', 'lighting', 'surface', 'props', 'human', 'mood'];

    /** محورهایی که چند توکن هم‌زمان می‌پذیرند. */
    public const MULTI_AXES = ['lighting', 'props'];

    public const AXIS_LABELS = [
        'framing' => 'کادربندی',
        'camera' => 'زاویه دوربین',
        'lens' => 'لنز',
        'lighting' => 'نور',
        'surface' => 'سطح و دکور',
        'props' => 'پراپ و انرژی',
        'human' => 'حضور انسان',
        'mood' => 'حس و حال',
    ];

    public const CATEGORIES = [
        'hero' => 'هیرو',
        'lifestyle' => 'لایف‌استایل',
        'model' => 'با مدل',
        'detail' => 'جزئیات',
        'flatlay' => 'فلت‌لی',
    ];

    /**
     * @return array<string, array<string, array{fa:string, prompt:string}>>
     */
    public static function vocabulary(): array
    {
        return [
            'framing' => [
                'hero-centered' => ['fa' => 'هیرو وسط کادر', 'prompt' => 'a centered hero composition with the product as the clear focal point and generous negative space'],
                'closeup-macro' => ['fa' => 'کلوزآپ ماکرو', 'prompt' => 'a tight macro close-up that fills most of the frame with the product and its surface details'],
                'flatlay-topdown' => ['fa' => 'فلت‌لی از بالا', 'prompt' => 'a styled flat-lay composition seen directly from above'],
                'three-quarter' => ['fa' => 'سه‌رخ', 'prompt' => 'a three-quarter view that shows the product\'s front and one side'],
                'off-center-editorial' => ['fa' => 'ادیتوریال خارج از مرکز', 'prompt' => 'an editorial off-center composition following the rule of thirds'],
            ],
            'camera' => [
                'eye-level' => ['fa' => 'هم‌سطح چشم', 'prompt' => 'shot at eye level'],
                'low-angle' => ['fa' => 'زاویه پایین', 'prompt' => 'shot from a slightly low angle to make the product feel monumental'],
                '45deg' => ['fa' => '۴۵ درجه', 'prompt' => 'shot from a 45-degree elevated angle'],
                'top-down' => ['fa' => 'کاملاً از بالا', 'prompt' => 'shot from directly overhead'],
                'dutch-tilt' => ['fa' => 'کج (داچ)', 'prompt' => 'with a subtle dynamic dutch tilt'],
            ],
            'lens' => [
                'macro-100mm' => ['fa' => 'ماکرو ۱۰۰', 'prompt' => 'using a 100mm macro lens with shallow depth of field'],
                '85mm-portrait' => ['fa' => '۸۵ میلی‌متر', 'prompt' => 'using an 85mm lens with soft background compression'],
                '50mm-natural' => ['fa' => '۵۰ میلی‌متر', 'prompt' => 'using a 50mm lens with natural perspective'],
                '35mm-wide' => ['fa' => '۳۵ واید', 'prompt' => 'using a 35mm lens that shows more of the environment'],
            ],
            'lighting' => [
                'soft-window' => ['fa' => 'نور نرم پنجره', 'prompt' => 'soft diffused window light'],
                'hard-sun-leaf-shadow' => ['fa' => 'آفتاب تند + سایه برگ', 'prompt' => 'hard direct sunlight casting crisp palm-leaf shadows'],
                'softbox-studio' => ['fa' => 'سافت‌باکس استودیو', 'prompt' => 'clean studio softbox lighting with gentle gradients'],
                'rim-light' => ['fa' => 'نور لبه', 'prompt' => 'a glowing rim light that outlines the product silhouette'],
                'golden-hour' => ['fa' => 'ساعت طلایی', 'prompt' => 'warm golden-hour light'],
                'caustics' => ['fa' => 'انعکاس نور آب', 'prompt' => 'shimmering water caustics dancing across the scene'],
            ],
            'surface' => [
                'travertine-plinth' => ['fa' => 'پایه سنگ تراورتن', 'prompt' => 'standing on a travertine stone plinth'],
                'glass-ribbons' => ['fa' => 'روبان‌های شیشه‌ای', 'prompt' => 'surrounded by flowing translucent glass ribbons'],
                'satin-fabric' => ['fa' => 'پارچه ساتن', 'prompt' => 'on softly draped satin fabric'],
                'wet-marble' => ['fa' => 'مرمر خیس', 'prompt' => 'on a wet polished marble surface with reflections'],
                'pastel-seamless' => ['fa' => 'زمینه پاستلی یکدست', 'prompt' => 'against a seamless pastel backdrop that complements the product colors'],
                'water-surface' => ['fa' => 'سطح آب', 'prompt' => 'resting on a calm water surface with gentle ripples'],
                'sand-dune' => ['fa' => 'تپه شنی', 'prompt' => 'on sculpted fine sand'],
            ],
            'props' => [
                'none' => ['fa' => 'بدون پراپ', 'prompt' => 'with no additional props'],
                'liquid-splash' => ['fa' => 'پاشش مایع', 'prompt' => 'a dynamic frozen-motion splash of liquid matching the product\'s color'],
                'levitation' => ['fa' => 'معلق در هوا', 'prompt' => 'the product gently levitating at a slight angle'],
                'ingredients-scatter' => ['fa' => 'مواد اولیه پراکنده', 'prompt' => 'fresh natural ingredients related to the product scattered artfully around it'],
                'water-droplets' => ['fa' => 'قطره‌های آب', 'prompt' => 'fine fresh water droplets on and around the product'],
                'petals' => ['fa' => 'گلبرگ', 'prompt' => 'delicate flower petals'],
                'smoke-mist' => ['fa' => 'مه و بخار', 'prompt' => 'soft wisps of mist'],
                'bubbles' => ['fa' => 'حباب', 'prompt' => 'floating translucent bubbles'],
            ],
            'human' => [
                'none' => ['fa' => 'بدون انسان', 'prompt' => 'no people in the frame'],
                'hand-hold' => ['fa' => 'در دست', 'prompt' => 'an elegant hand with natural manicure holding the product, only the hand visible'],
                'model-closeup' => ['fa' => 'کلوزآپ مدل', 'prompt' => 'a close-up of a generic adult model applying or touching the product'],
                'model-profile' => ['fa' => 'پروفایل مدل', 'prompt' => 'a generic adult model in profile with the product resting on her open palm'],
                'model-portrait-with-prop' => ['fa' => 'پرتره مدل با محصول', 'prompt' => 'a beauty portrait of a generic adult model holding the product next to her face'],
            ],
            'mood' => [
                'clean-luxury' => ['fa' => 'لوکس و تمیز', 'prompt' => 'clean luxurious premium mood'],
                'playful-pop' => ['fa' => 'شاد و پاپ', 'prompt' => 'playful vibrant pop mood'],
                'warm-natural' => ['fa' => 'گرم و طبیعی', 'prompt' => 'warm natural organic mood'],
                'editorial' => ['fa' => 'ادیتوریال مجله', 'prompt' => 'high-fashion magazine editorial mood'],
                'fresh-cool' => ['fa' => 'خنک و تازه', 'prompt' => 'fresh cool hydrating mood'],
            ],
        ];
    }

    /**
     * توکن‌ها را تمیز می‌کند: محور ناشناخته و توکن ناشناخته حذف؛ محورهای تکی فقط یک مقدار.
     *
     * @return array<string, string|array<int,string>>
     */
    public static function normalize(array $tokens): array
    {
        $vocab = self::vocabulary();
        $clean = [];
        foreach (self::AXES as $axis) {
            $raw = $tokens[$axis] ?? null;
            if ($raw === null || $raw === '' || $raw === []) {
                continue;
            }
            $values = array_values(array_unique(array_filter(array_map(
                fn ($v) => trim((string) $v),
                is_array($raw) ? $raw : [$raw]
            ), fn ($v) => isset($vocab[$axis][$v]))));
            if ($values === []) {
                continue;
            }
            $clean[$axis] = in_array($axis, self::MULTI_AXES, true) ? $values : $values[0];
        }

        return $clean;
    }

    /** عبارت انگلیسی یک محور (چند توکن با «and» وصل می‌شوند). */
    public static function phrase(string $axis, string|array|null $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }
        $vocab = self::vocabulary()[$axis] ?? [];
        $parts = [];
        foreach ((array) $value as $token) {
            if (isset($vocab[$token])) {
                $parts[] = $vocab[$token]['prompt'];
            }
        }

        return implode(' and ', $parts);
    }

    /** برچسب فارسی کوتاه توکن‌ها برای نمایش در پنل. */
    public static function labels(array $tokens): array
    {
        $vocab = self::vocabulary();
        $labels = [];
        foreach (self::normalize($tokens) as $axis => $value) {
            foreach ((array) $value as $token) {
                $labels[] = $vocab[$axis][$token]['fa'];
            }
        }

        return $labels;
    }

    public static function involvesHuman(array $tokens): bool
    {
        $human = self::normalize($tokens)['human'] ?? 'none';

        return $human !== 'none';
    }
}
