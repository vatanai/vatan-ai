<?php

namespace Vatan\Seo\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * ابزارهای زبان فارسی: نرمال‌سازی کلمه‌ی کلیدی، ارقام فارسی، تاریخ شمسی و زمان نسبی.
 * عمداً به هیچ کلاس میزبان وابسته نیست تا پکیج قابل انتقال بماند.
 */
class Fa
{
    private const AR = ['ي', 'ك', 'ى', 'ة', 'ؤ', 'إ', 'أ', 'ٱ', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const FA = ['ی', 'ک', 'ی', 'ه', 'و', 'ا', 'ا', 'ا', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    /** پاک‌سازی نمایشی: حروف عربی ← فارسی، فاصله‌های اضافه و نیم‌فاصله‌ی تکراری */
    public static function cleanKeyword(?string $text): string
    {
        $text = str_replace(self::AR, self::FA, (string) $text);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text); // اعراب
        $text = preg_replace('/[\x{200C}\x{200D}\x{200E}\x{200F}]+/u', "\u{200C}", $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return (string) preg_replace('/^[\s\x{200C}]+|[\s\x{200C}]+$/u', '', $text);
    }

    /** کلید یکتا برای مقایسه: نیم‌فاصله = فاصله، حروف کوچک، ارقام انگلیسی */
    public static function normalizeKeyword(?string $text): string
    {
        $text = self::cleanKeyword($text);
        $text = str_replace("\u{200C}", ' ', $text);
        $text = strtr($text, array_combine(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], range(0, 9)));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($text));

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public static function digits(mixed $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', '.' => '٫']);
    }

    public static function n(int|float|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return '—';
        }
        return self::digits(number_format((float) $value, $decimals, '.', '٬'));
    }

    /** اعداد بزرگ کوتاه: ۱٫۲ هزار */
    public static function short(int|float|null $value): string
    {
        if ($value === null) {
            return '—';
        }
        $abs = abs($value);
        return match (true) {
            $abs >= 1_000_000 => self::digits(round($value / 1_000_000, 1)).' میلیون',
            $abs >= 10_000 => self::digits(round($value / 1000)).' هزار',
            $abs >= 1_000 => self::digits(round($value / 1000, 1)).' هزار',
            default => self::n($value),
        };
    }

    public static function percent(?float $ratio, int $decimals = 1): string
    {
        return $ratio === null ? '—' : self::digits(number_format($ratio * 100, $decimals)).'٪';
    }

    public static function usd(?float $value): string
    {
        return $value === null ? '—' : '$'.number_format($value, $value < 1 ? 3 : 2);
    }

    /** [سال، ماه، روز] شمسی */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $gdm[$gm - 1];
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

    public static function date(CarbonInterface|string|null $date, bool $withTime = false, bool $monthName = true): string
    {
        if ($date === null || $date === '') {
            return '—';
        }
        $d = $date instanceof CarbonInterface ? $date->copy() : Carbon::parse($date);
        $d = $d->setTimezone(config('seo-engine.host.timezone', 'Asia/Tehran'));
        [$jy, $jm, $jd] = self::toJalali((int) $d->format('Y'), (int) $d->format('n'), (int) $d->format('j'));
        $out = $monthName ? "{$jd} ".self::MONTHS[$jm - 1]." {$jy}" : sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        if ($withTime) {
            $out .= ' — '.$d->format('H:i');
        }
        return self::digits($out);
    }

    /** برچسب کوتاه برای محور نمودار: «۱۲ مهر» */
    public static function dayLabel(CarbonInterface|string $date): string
    {
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        [, $jm, $jd] = self::toJalali((int) $d->format('Y'), (int) $d->format('n'), (int) $d->format('j'));
        return self::digits($jd.' '.self::MONTHS[$jm - 1]);
    }

    public static function ago(CarbonInterface|string|null $date): string
    {
        if (! $date) {
            return 'هرگز';
        }
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $sec = max(0, now()->getTimestamp() - $d->getTimestamp());
        return match (true) {
            $sec < 60 => 'همین حالا',
            $sec < 3600 => self::digits(intdiv($sec, 60)).' دقیقه پیش',
            $sec < 86400 => self::digits(intdiv($sec, 3600)).' ساعت پیش',
            $sec < 86400 * 30 => self::digits(intdiv($sec, 86400)).' روز پیش',
            default => self::date($d),
        };
    }

    /** زمان آینده: «۳ ساعت دیگر» */
    public static function until(CarbonInterface|string|null $date): string
    {
        if (! $date) {
            return '—';
        }
        $d = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $sec = $d->getTimestamp() - now()->getTimestamp();
        if ($sec <= 0) {
            return 'سررسیده';
        }
        return match (true) {
            $sec < 3600 => self::digits(max(1, intdiv($sec, 60))).' دقیقه دیگر',
            $sec < 86400 => self::digits(intdiv($sec, 3600)).' ساعت دیگر',
            default => self::digits(intdiv($sec, 86400)).' روز دیگر',
        };
    }
}
