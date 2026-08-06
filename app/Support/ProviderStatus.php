<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * ══════════════════════════════════════════════════════════════════════
 * ProviderStatus — کلید مرکزی روشن/خاموش سرویس‌های هوش مصنوعی
 * ─────────────────────────────────────────────────────────────────────
 * هدف: بدون حذف یا خراب‌کردن هیچ کدی از OpenRouterService یا LiaraAiService،
 * فقط با یک کلید ساده در پنل ادمین بتوانیم یک provider را «فریز» کنیم.
 *
 * ذخیره‌سازی: Cache دائمی (چون CACHE_STORE=database است، دیتا پایدار است
 * حتی پس از ری‌استارت سرور). کلید cache به‌شکل provider.<name>.enabled.
 *
 * پیش‌فرض‌ها (اولین بار که کاربر هنوز چیزی تنظیم نکرده):
 *   - liara      → true  (فعال — سرویس اصلی داخل ایران، بدون VPN)
 *   - openrouter → true  (فعال — قابل انتخاب در ثبت محصول)
 *
 * برای برگرداندن OpenRouter کافیست از پنل ادمین آن را روشن کنند؛
 * هیچ کدی نه پاک شده و نه فریز شده — فقط یک flag کوچک است.
 * ══════════════════════════════════════════════════════════════════════
 */
class ProviderStatus
{
    /** لیست providerهای شناخته‌شده در سیستم. */
    public const PROVIDERS = ['liara', 'openrouter', 'fal', 'replicate'];

    /** مقادیر پیش‌فرض روشن/خاموش برای اولین بار (تا وقتی ادمین دستی عوض نکرده). */
    protected const DEFAULTS = [
        'liara'      => true,
        'openrouter' => true,
        'fal'        => false,
        'replicate'  => false,
    ];

    /**
     * آیا provider مشخصی روشن است؟
     */
    public static function isEnabled(string $provider): bool
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, self::PROVIDERS, true)) {
            return false;
        }

        return (bool) Cache::rememberForever(
            self::cacheKey($provider),
            fn () => self::DEFAULTS[$provider] ?? false
        );
    }

    /**
     * تغییر وضعیت روشن/خاموش یک provider (استفاده در پنل ادمین).
     */
    public static function setEnabled(string $provider, bool $enabled): void
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, self::PROVIDERS, true)) {
            return;
        }

        Cache::forever(self::cacheKey($provider), $enabled);
    }

    /**
     * وضعیت همه providerها به‌صورت آرایه — برای نمایش در UI ادمین.
     *
     * @return array<string,bool>  ['liara' => true, 'openrouter' => false]
     */
    public static function all(): array
    {
        $result = [];
        foreach (self::PROVIDERS as $provider) {
            $result[$provider] = self::isEnabled($provider);
        }
        return $result;
    }

    /**
     * لیست providerهای فعال — برای فیلترکردن Eloquent queryها.
     *
     * @return array<int,string>
     */
    public static function enabled(): array
    {
        return array_values(array_filter(self::PROVIDERS, fn ($p) => self::isEnabled($p)));
    }

    protected static function cacheKey(string $provider): string
    {
        return 'provider.' . $provider . '.enabled';
    }
}
