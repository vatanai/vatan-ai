<?php

namespace App\Services\SmartInstagram;

use Illuminate\Support\Carbon;

/** قالب‌بندی‌های نمایشی مشترک صفحات اینستاگرام هوشمند (اعداد فارسی، زمان نسبی، مبلغ). */
class Ui
{
    public static function n(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return PersianText::faDigits(is_numeric($value) ? number_format((float) $value, is_float($value) && fmod((float) $value, 1) !== 0.0 ? 1 : 0) : $value);
    }

    public static function pct(int|float|null $value): string
    {
        return $value === null ? '—' : PersianText::faDigits((string) round($value)).'٪';
    }

    public static function money(int|float|null $toman): string
    {
        $toman = (int) $toman;
        if ($toman >= 1_000_000_000) {
            return PersianText::faDigits(rtrim(rtrim(number_format($toman / 1_000_000_000, 1), '0'), '.')).' میلیارد';
        }
        if ($toman >= 1_000_000) {
            return PersianText::faDigits(rtrim(rtrim(number_format($toman / 1_000_000, 1), '0'), '.')).' میلیون';
        }

        return PersianText::faDigits(number_format($toman));
    }

    public static function duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        if ($seconds < 60) {
            return PersianText::faDigits((string) $seconds).' ثانیه';
        }
        if ($seconds < 3600) {
            return PersianText::faDigits((string) round($seconds / 60)).' دقیقه';
        }
        if ($seconds < 86400) {
            return PersianText::faDigits(rtrim(rtrim(number_format($seconds / 3600, 1), '0'), '.')).' ساعت';
        }

        return PersianText::faDigits((string) round($seconds / 86400)).' روز';
    }

    public static function ago(Carbon|string|null $time): string
    {
        if (!$time) {
            return '—';
        }
        $time = $time instanceof Carbon ? $time : Carbon::parse($time);
        $diff = max(0, (int) $time->diffInSeconds(now()));

        return match (true) {
            $diff < 60 => 'همین حالا',
            $diff < 3600 => PersianText::faDigits((string) intdiv($diff, 60)).' دقیقه پیش',
            $diff < 86400 => PersianText::faDigits((string) intdiv($diff, 3600)).' ساعت پیش',
            $diff < 86400 * 7 => PersianText::faDigits((string) intdiv($diff, 86400)).' روز پیش',
            default => self::date($time),
        };
    }

    public static function date(Carbon|string|null $time, bool $withTime = false): string
    {
        if (!$time) {
            return '—';
        }
        $time = ($time instanceof Carbon ? $time : Carbon::parse($time))->copy()->setTimezone('Asia/Tehran');
        [$jy, $jm, $jd] = self::toJalali((int) $time->format('Y'), (int) $time->format('n'), (int) $time->format('j'));
        $months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        $out = PersianText::faDigits((string) $jd).' '.$months[$jm - 1];
        if ($jy !== self::toJalali((int) now()->format('Y'), (int) now()->format('n'), (int) now()->format('j'))[0]) {
            $out .= ' '.PersianText::faDigits((string) $jy);
        }

        return $withTime ? $out.'، '.PersianText::faDigits($time->format('H:i')) : $out;
    }

    public static function time(Carbon|string|null $time): string
    {
        if (!$time) {
            return '';
        }

        return PersianText::faDigits(($time instanceof Carbon ? $time : Carbon::parse($time))->copy()->setTimezone('Asia/Tehran')->format('H:i'));
    }

    /** @return array{0:int,1:int,2:int} */
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
        $jm = $days < 186 ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
        $jd = 1 + ($days < 186 ? $days % 31 : ($days - 186) % 30);

        return [$jy, $jm, $jd];
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'new', 'unanswered', 'pending', 'queued', 'retrying', 'test', 'draft', 'warning' => 'warning',
            'connected', 'active', 'sent', 'success', 'approved', 'processed', 'won', 'done', 'accepted', 'manual', 'stored' => 'success',
            'failed', 'error', 'blocked', 'lost', 'rejected', 'danger' => 'danger',
            'waiting_customer', 'assigned', 'sending', 'simulated', 'info', 'edited', 'running' => 'info',
            default => 'neutral',
        };
    }

    public static function label(string $group, ?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }
        $map = [
            'status' => config('smart_instagram.conversation_statuses') + ['note' => 'یادداشت'],
            'source' => config('smart_instagram.sources') + ['note' => 'یادداشت'],
            'intent' => config('smart_instagram.intents'),
            'stage' => config('smart_instagram.customer_stages'),
            'lead' => config('smart_instagram.lead_statuses'),
            'pipeline' => config('smart_instagram.pipeline_stages'),
            'outbound' => ['pending' => 'در صف', 'sending' => 'در حال ارسال', 'retrying' => 'تلاش دوباره', 'sent' => 'ارسال شد', 'failed' => 'ناموفق', 'blocked' => 'مسدود (قانون)', 'manual' => 'ارسال دستی'],
            'kind' => ['dm' => 'دایرکت', 'private_reply' => 'پاسخ خصوصی', 'public_reply' => 'پاسخ عمومی'],
            'origin' => ['human' => 'انسان', 'ai' => 'هوش مصنوعی', 'automation' => 'اتومیشن', 'instagram_app' => 'اپ اینستاگرام', 'customer' => 'مشتری'],
            'event' => ['received' => 'دریافت‌شده', 'processed' => 'پردازش‌شده', 'failed' => 'ناموفق', 'ignored' => 'نادیده', 'duplicate' => 'تکراری'],
            'knowledge' => ['draft' => 'پیش‌نویس', 'approved' => 'تأییدشده', 'archived' => 'بایگانی'],
            'suggestion' => ['pending' => 'در انتظار', 'accepted' => 'پذیرفته', 'edited' => 'ویرایش‌شده', 'rejected' => 'ردشده', 'superseded' => 'جایگزین‌شده'],
            'run' => ['success' => 'موفق', 'partial' => 'نیمه‌موفق', 'failed' => 'ناموفق', 'skipped' => 'ردشده (حفاظ)', 'simulated' => 'آزمایشی', 'running' => 'در حال اجرا'],
            'channel' => ['connected' => 'متصل', 'pending' => 'در انتظار اتصال', 'error' => 'خطا'],
        ][$group] ?? [];

        return $map[$key] ?? $key;
    }
}
