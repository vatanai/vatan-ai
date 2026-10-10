<?php

namespace Vatan\Seo\Data\Google;

use Illuminate\Support\Facades\Cache;

/**
 * پیشنهادهای جستجوی گوگل (Autocomplete) — منبع رایگان کشف کلمات فارسی.
 * حروف الفبا به انتهای بذر اضافه می‌شود تا دنباله‌های طولانی (Long-tail) کشف شوند.
 */
class Autocomplete
{
    public function suggest(string $seed): array
    {
        $key = 'seo-engine:ac:'.md5($seed);
        return Cache::remember($key, now()->addDays(7), function () use ($seed) {
            try {
                $res = GoogleHttp::request(10)->get(GoogleHttp::url('https://suggestqueries.google.com/complete/search'), [
                    'client' => 'firefox', 'hl' => 'fa', 'gl' => 'ir', 'q' => $seed,
                ]);
                if (! $res->successful()) {
                    return [];
                }
                $body = $res->body();
                if (! mb_check_encoding($body, 'UTF-8')) {
                    $body = mb_convert_encoding($body, 'UTF-8', 'Windows-1256');
                }
                $data = json_decode($body, true);
                return array_values(array_filter((array) ($data[1] ?? []), 'is_string'));
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** گسترش یک بذر: خود بذر + بذر با پسوندهای پرکاربرد و حروف الفبا */
    public function expand(string $seed, int $max = 40, bool $deep = false): array
    {
        $out = $this->suggest($seed);
        $modifiers = ['چیست', 'رایگان', 'آنلاین', 'با هوش مصنوعی', 'قیمت', 'بهترین', 'آموزش'];
        if ($deep) {
            $modifiers = array_merge($modifiers, ['ا', 'ب', 'پ', 'ت', 'س', 'ع', 'م', 'ن', 'ه', 'ی']);
        }
        foreach ($modifiers as $mod) {
            if (count($out) >= $max) {
                break;
            }
            $out = array_merge($out, $this->suggest($seed.' '.$mod));
            usleep(150_000);
        }
        return array_slice(array_values(array_unique($out)), 0, $max);
    }
}
