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
     * زمان دیتابیس UTC است؛ برای نمایش، یک کپی را به ساعت محلی ایران تبدیل می‌کنیم.
     */
    protected static function forDisplay(Carbon $date): Carbon
    {
        return $date->copy()->setTimezone(config('app.display_timezone', 'Asia/Tehran'));
    }

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

    /**
     * تبدیل سال/ماه/روز شمسی به میلادی.
     */
    public static function toGregorianYmd(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8)
            + intdiv(($jy % 33) + 3, 4) + $jd
            + ($jm < 7 ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186));

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $gregorianMonthDays = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 1;

        while ($gm <= 12 && $gd > $gregorianMonthDays[$gm]) {
            $gd -= $gregorianMonthDays[$gm];
            $gm++;
        }

        return [$gy, $gm, $gd];
    }

    /** بررسی کامل تاریخ شمسی، از جمله روز ۳۰ اسفند در سال کبیسه. */
    public static function isValidDate(int $jy, int $jm, int $jd): bool
    {
        if ($jy < 1 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
            return false;
        }
        if ($jm > 6 && $jd > 30) {
            return false;
        }

        [$gy, $gm, $gd] = self::toGregorianYmd($jy, $jm, $jd);

        return self::toJalaliYmd($gy, $gm, $gd) === [$jy, $jm, $jd];
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

        $date = self::forDisplay($date);
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

        $date = self::forDisplay($date);
        [$jy, $jm, $jd] = self::toJalaliYmd((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

        $dateStr = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        $timeStr = $date->format('H:i');

        return self::toPersianDigits($dateStr) . '  ' . self::toPersianDigits($timeStr);
    }
}
