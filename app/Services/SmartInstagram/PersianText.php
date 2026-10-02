<?php

namespace App\Services\SmartInstagram;

/** نرمال‌سازی متن فارسی برای جست‌وجو، تطبیق کلمه‌ی کلیدی و بازیابی دانش. */
class PersianText
{
    private const STOP_WORDS = [
        'و', 'در', 'به', 'از', 'که', 'این', 'آن', 'با', 'را', 'برای', 'تا', 'هم', 'یا', 'است', 'هست', 'بود', 'شد',
        'می', 'های', 'ها', 'یک', 'من', 'تو', 'ما', 'شما', 'اون', 'این', 'چی', 'چه', 'کنید', 'کنم', 'دارم', 'دارید',
        'سلام', 'لطفا', 'ممنون', 'مرسی', 'the', 'a', 'an', 'is', 'to', 'of', 'and', 'in', 'for',
    ];

    public static function normalize(?string $text): string
    {
        $text = (string) $text;
        $text = strtr($text, [
            'ي' => 'ی', 'ك' => 'ک', 'ى' => 'ی', 'ة' => 'ه', 'ۀ' => 'ه', 'أ' => 'ا', 'إ' => 'ا', 'ؤ' => 'و',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            "\u{200C}" => ' ', "\u{200D}" => '', 'ـ' => '',
        ]);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text; // اعراب
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return array<int,string> */
    public static function tokens(?string $text): array
    {
        $tokens = [];
        foreach (explode(' ', self::normalize($text)) as $token) {
            if ($token === '' || mb_strlen($token) < 2 || in_array($token, self::STOP_WORDS, true)) {
                continue;
            }
            $tokens[] = $token;
        }

        return $tokens;
    }

    public static function containsKeyword(string $text, string $keyword, string $mode = 'contains'): bool
    {
        $haystack = self::normalize($text);
        $needle = self::normalize($keyword);
        if ($needle === '') {
            return false;
        }

        return match ($mode) {
            'exact' => $haystack === $needle,
            'word' => (bool) preg_match('/(^|\s)'.preg_quote($needle, '/').'($|\s)/u', $haystack),
            default => str_contains($haystack, $needle),
        };
    }

    public static function faDigits(int|float|string|null $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', '.' => '٫']);
    }
}
