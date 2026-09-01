<?php

namespace App\Services;

use App\Models\TelegramBotContent;
use Illuminate\Support\Facades\Schema;

class TelegramContentService
{
    private const DEFAULTS = [
        'welcome' => 'سلام {first_name} عزیز 🌿\nبه وطن خوش اومدی. برای ساخت محصول، اول عضویتت در کانال وطن رو بررسی می‌کنیم.',
        'membership_required' => 'برای ساخت محصول، ابتدا عضو کانال وطن شو و بعد روی «بررسی عضویت» بزن.',
        'registration_name' => 'عالیه 🌿\nبرای ساخت محصول، نام و نام خانوادگی‌ات رو در یک پیام بفرست.',
        'registration_phone' => 'حالا شماره موبایلت رو با دکمه‌ی زیر بفرست. اگر دکمه برایت کار نکرد، شماره را دستی وارد کن.',
        'registration_otp' => 'کد تأیید برای شماره‌ات پیامک شد؛ همان کد را همین‌جا بفرست تا حساب وطن برایت آماده شود.',
        'registration_done' => 'حسابت آماده شد 🎉\n{gift_tokens} اعتبار هدیه برای شروع به حسابت اضافه شد.',
        'returning_user' => 'خوش برگشتی {first_name} عزیز 🌿\nحسابت آماده است؛ از اینجا ادامه بده.',
        'product_selected' => "این همون محصولیه که انتخاب کردی 🌿\n\n{product_name}\n{product_description}",
        'product_missing' => 'محصول انتخاب‌شده پیدا نشد. می‌تونی از بین همه‌ی قالب‌ها انتخاب کنی.',
        'build_ready' => 'صفحه‌ی ساخت محصول آماده است 🌿',
        'all_products_ready' => 'همه‌ی قالب‌های وطن آماده‌اند 🌿',
        'generic_error' => 'یک لحظه مشکلی پیش آمد. دوباره تلاش کن یا با پشتیبانی در تماس باش.',
    ];

    public function text(string $key, array $variables = []): string
    {
        $content = $this->find($key);
        $text = is_string($content?->body) && trim($content->body) !== ''
            ? $content->body
            : (self::DEFAULTS[$key] ?? '');

        return $this->replaceVariables($text, $variables);
    }

    public function buttons(string $key, array $variables = [], array $fallback = []): array
    {
        $content = $this->find($key);
        $buttons = $content?->buttons;
        if (! is_array($buttons) || $buttons === []) {
            return $fallback;
        }

        return array_map(function ($button) use ($variables) {
            if (! is_array($button)) {
                return $button;
            }

            return array_map(fn ($value) => is_string($value) ? $this->replaceVariables($value, $variables) : $value, $button);
        }, $buttons);
    }

    public function media(string $key, array $variables = []): ?array
    {
        $content = $this->find($key);
        if (! $content?->media_type || ! $content?->media_file_id) {
            return null;
        }

        return [
            'type' => $content->media_type,
            'file_id' => $this->replaceVariables($content->media_file_id, $variables),
        ];
    }

    private function find(string $key): ?TelegramBotContent
    {
        if (! Schema::hasTable('telegram_bot_contents')) {
            return null;
        }

        return TelegramBotContent::query()
            ->where('content_key', $key)
            ->where('is_active', true)
            ->first();
    }

    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }

        return $text;
    }
}
