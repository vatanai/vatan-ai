<?php

namespace App\Support;

final class Numeral
{
    /**
     * رقم‌های ده‌دهی یونیکد را، مستقل از زبان ورودی، به رقم انگلیسی تبدیل می‌کند.
     */
    public static function toAscii(?string $value): string
    {
        $value = trim((string) $value);

        $normalized = preg_replace_callback('/\p{Nd}/u', static function (array $match): string {
            if (class_exists(\IntlChar::class)) {
                $digit = \IntlChar::digit($match[0]);
                if (is_int($digit) && $digit >= 0 && $digit <= 9) {
                    return (string) $digit;
                }
            }

            return strtr($match[0], [
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            ]);
        }, $value);

        return $normalized ?? $value;
    }
}
