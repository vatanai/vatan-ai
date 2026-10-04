<?php

namespace App\Services\SmartInstagram;

use App\Models\SmartInstagram\Contact;

/**
 * نام قابل‌اعتماد مخاطب برای پیام‌های اینستاگرام.
 *
 * نام کاربری به‌تنهایی نام انسان محسوب نمی‌شود؛ بنابراین اگر از نام نمایشی
 * یا چند الگوی شناخته‌شده نامی با اطمینان کافی پیدا نشود، خروجی عمداً خالی است.
 */
final class ContactNameResolver
{
    /** @var array<string,string> */
    private const KNOWN_NAMES = [
        'mohsen' => 'محسن', 'mina' => 'مینا', 'fatemeh' => 'فاطمه', 'fateme' => 'فاطمه',
        'maryam' => 'مریم', 'zahra' => 'زهرا', 'sara' => 'سارا', 'sahar' => 'سحر',
        'elham' => 'الهام', 'samin' => 'ثمین', 'samira' => 'سمیرا', 'neda' => 'ندا',
        'nazanin' => 'نازنین', 'mahsa' => 'مهسا', 'parisa' => 'پریسا',
        'leila' => 'لیلا', 'leyla' => 'لیلا', 'roya' => 'رویا', 'shirin' => 'شیرین',
        'mary' => 'مریم', 'ali' => 'علی', 'reza' => 'رضا', 'hossein' => 'حسین',
        'hassan' => 'حسن', 'mehdi' => 'مهدی', 'amir' => 'امیر', 'armin' => 'آرمین',
        'sina' => 'سینا', 'milad' => 'میلاد', 'navid' => 'نوید', 'pooya' => 'پویا',
        'pouria' => 'پوریا', 'saeed' => 'سعید', 'omid' => 'امید', 'farhad' => 'فرهاد',
        'shahab' => 'شهاب', 'mohammad' => 'محمد', 'ahmad' => 'احمد', 'iman' => 'ایمان',
    ];

    public function resolve(?Contact $contact): ?string
    {
        if (!$contact) {
            return null;
        }

        $display = $this->clean((string) $contact->display_name);
        $username = $this->clean((string) $contact->username);

        if ($display !== '' && !$this->looksLikeUsername($display, $username)) {
            $first = preg_split('/\s+/u', $display)[0] ?? '';
            if ($this->looksLikeHumanName($first)) {
                return $first;
            }
        }

        if ($username !== '') {
            $first = preg_split('/[._\-\s]+/u', mb_strtolower($username))[0] ?? '';
            return self::KNOWN_NAMES[$first] ?? null;
        }

        return null;
    }

    /** جای‌گذاری ایمن نام؛ هرگز نام کاربری را به‌جای نام انسان وارد نمی‌کند. */
    public function render(string $text, ?Contact $contact): string
    {
        $name = $this->resolve($contact);
        $username = trim((string) $contact?->username);
        $rendered = str_replace('{name}', $name ?: '', $text);
        $rendered = str_replace('{username}', $username !== '' ? '@'.$username : '', $rendered);

        if (!$name) {
            $rendered = preg_replace('/\s*(?:جان|عزیز|خانم|آقا)\s*/u', ' ', $rendered) ?? $rendered;
        }
        if ($username !== '') {
            $rendered = str_ireplace(['@'.$username, $username], '', $rendered);
        }

        return $this->tidy($rendered);
    }

    /**
     * پاک‌سازی علائمی که بعد از حذف نام/نام کاربری تنها می‌مانند؛
     * مثلاً «{name} عزیز، خوشحالم…» بدون نام نباید با «، خوشحالم…» شروع شود.
     */
    private function tidy(string $text): string
    {
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        // فاصله‌ی قبل از علامت (« ،» ← «،»)
        $text = preg_replace('/[ \t]+([،,؛;:!?؟.])/u', '$1', $text) ?? $text;
        // علامت‌های جداکننده‌ی پشت‌سرهم («، ،» ← «،»)
        $text = preg_replace('/([،,؛;:])(?:[ \t]*[،,؛;:])+/u', '$1', $text) ?? $text;
        // جداکننده‌ی یتیم در ابتدای متن
        $text = preg_replace('/^\s*[،,؛;:!.]+\s*/u', '', $text) ?? $text;

        return trim($text);
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    }

    private function looksLikeUsername(string $value, string $username): bool
    {
        $normalizedValue = mb_strtolower(ltrim($value, '@'));
        $normalizedUsername = mb_strtolower(ltrim($username, '@'));

        return $normalizedUsername !== '' && $normalizedValue === $normalizedUsername
            || preg_match('/[@._\-\d]/u', $value) === 1;
    }

    private function looksLikeHumanName(string $value): bool
    {
        if (mb_strlen($value) < 2 || mb_strlen($value) > 30 || preg_match('/\d|[@._\-]/u', $value)) {
            return false;
        }

        return preg_match('/^[\p{L}]+$/u', $value) === 1;
    }
}
