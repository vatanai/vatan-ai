<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * تبدیل تاریخ میلادی به شمسی — پیاده‌سازی مستقل و بدون نیاز به پکیج جانبی
 * (الگوریتم استاندارد و رایج تبدیل تقویم، چون امکان نصب پکیج کامپوزر در این محیط نبود).
 */
class Jalali
{
    protected static array $monthNames = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    /**
     * تبدیل سال/ماه/روز میلادی به شمسی
     */
    public static function toJalaliYmd(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    public static function toPersianDigits(string $value): string
    {
        return strtr($value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    /**
     * فرمت خوانا و کامل: «۱۵ تیر ۱۴۰۵ — ساعت ۱۴:۳۰»
     */
    public static function format(?Carbon $date): string
    {
        if (!$date) {
            return '—';
        }

        [$jy, $jm, $jd] = self::toJalaliYmd((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));
        $monthName = self::$monthNames[$jm] ?? '';
        $time = self::toPersianDigits($date->format('H:i'));

        return self::toPersianDigits((string) $jd) . ' ' . $monthName . ' ' . self::toPersianDigits((string) $jy) . ' — ساعت ' . $time;
    }

    /**
     * فرمت عددی فشرده برای جدول‌های ادمین: «۱۴۰۵/۰۲/۲۲  ۲۱:۳۲»
     * (سال/ماه/روز شمسی با صفر پیش‌رو + ساعت ۲۴ ساعته، همه با ارقام فارسی)
     */
    public static function formatNumeric(?Carbon $date): string
    {
        if (!$date) {
            return '—';
        }

        [$jy, $jm, $jd] = self::toJalaliYmd((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        $dateStr = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        $timeStr = $date->format('H:i');

        return self::toPersianDigits($dateStr) . '  ' . self::toPersianDigits($timeStr);
    }
}
