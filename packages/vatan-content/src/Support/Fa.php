<?php

namespace Vatan\Content\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/** ابزار نمایش فارسی: ارقام، تاریخ شمسی، برچسب‌ها */
class Fa
{
    public const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    public const WEEKDAYS = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه']; // Carbon dayOfWeek: 0=Sunday

    public static function digits(mixed $v): string
    {
        return strtr((string) $v, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    public static function n(int|float|null $v, int $dec = 0): string
    {
        return $v === null ? '—' : self::digits(number_format((float) $v, $dec, '٫', '٬'));
    }

    public static function usd(?float $v): string
    {
        return $v === null ? '—' : self::digits('$'.number_format($v, $v < 1 ? 3 : 2));
    }

    /** @return array{int,int,int} */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $jm = $days < 186 ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
        $jd = 1 + ($days < 186 ? $days % 31 : ($days - 186) % 30);

        return [$jy, $jm, $jd];
    }

    public static function date(CarbonInterface|string|null $d, bool $year = false, bool $weekday = false): string
    {
        if (! $d) {
            return '—';
        }
        $c = $d instanceof CarbonInterface ? $d : Carbon::parse($d);
        $c = $c->copy()->setTimezone(config('content-factory.host.timezone', 'Asia/Tehran'));
        [$jy, $jm, $jd] = self::toJalali($c->year, $c->month, $c->day);
        $s = $jd.' '.self::MONTHS[$jm - 1].($year ? ' '.$jy : '');

        return self::digits(($weekday ? self::WEEKDAYS[$c->dayOfWeek].' ' : '').$s);
    }

    public static function weekday(CarbonInterface $d): string
    {
        return self::WEEKDAYS[$d->dayOfWeek];
    }

    public static function label(string $group, ?string $key): string
    {
        return (string) (config("content-factory.labels.$group.$key") ?? $key ?? '—');
    }

    public static function secs(?float $s): string
    {
        if ($s === null) {
            return '—';
        }
        $m = intdiv((int) $s, 60);

        return self::digits(sprintf('%02d:%05.2f', $m, $s - $m * 60));
    }
}
