<?php

namespace App\Support;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Cache;

/**
 * ══════════════════════════════════════════════════════════════════════
 * ProviderStatus — کلید مرکزی روشن/خاموش سرویس‌های هوش مصنوعی
 * ─────────────────────────────────────────────────────────────────────
 * هدف: با یک کلید ساده در پنل ادمین بتوانیم providerهای فعال پروژه را کنترل کنیم.
 *
 * ذخیره‌سازی: علاوه بر Cache، وضعیت صریح ادمین داخل settings همان provider
 * ذخیره می‌شود تا با پاک‌شدن Cache یا تغییر workerها از بین نرود.
 *
 * پیش‌فرض‌ها (اولین بار که کاربر هنوز چیزی تنظیم نکرده):
 *   - replicate → true  (مسیر اصلی MVP)
 *   - openrouter → false (تا زمان شارژ/تأیید حساب)
 *   - fal → false (تا زمان رفع مسیر شبکه و health-check موفق)
 * در نسخه‌ی MVP، migration فعال‌سازی providerها این مقدار را به‌صورت صریح
 * داخل ai_provider_settings روی true می‌گذارد؛ بنابراین وضعیت استقرارشده
 * از مقدار محافظه‌کارانه‌ی نصب اولیه جداست.
 *
 * برای برگرداندن OpenRouter کافیست از پنل ادمین آن را روشن کنند؛
 * هیچ کدی نه پاک شده و نه فریز شده — فقط یک flag کوچک است.
 * ══════════════════════════════════════════════════════════════════════
 */
class ProviderStatus
{
    /** لیست providerهای شناخته‌شده در سیستم. */
    public const PROVIDERS = ['openrouter', 'fal', 'replicate'];

    /** مقادیر پیش‌فرض روشن/خاموش برای اولین بار (تا وقتی ادمین دستی عوض نکرده). */
    protected const DEFAULTS = [
        'openrouter' => false,
        'fal'        => false,
        'replicate'  => true,
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

        // اگر ادمین قبلاً در پنل وضعیت مشخص کرده، این مقدار مرجع اصلی است.
        try {
            $savedSettings = (array) (AiProviderSetting::forProvider($provider)?->settings ?? []);
            if (array_key_exists('admin_enabled', $savedSettings)) {
                return (bool) $savedSettings['admin_enabled'];
            }
        } catch (\Throwable) {
            // تا قبل/هنگام migration، Cache و env همچنان fallback هستند.
        }

        // Cacheهای قدیمی ممکن است وضعیت پیش‌فرض «خاموش» را نگه داشته باشند.
        // اگر کلید ثبت شده و هنوز وضعیت دستی ذخیره نشده، provider آماده‌به‌کار است.
        if (self::hasCredentials($provider)) {
            return true;
        }

        return (bool) Cache::rememberForever(
            self::cacheKey($provider),
            fn () => self::defaultFor($provider)
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

        try {
            $setting = AiProviderSetting::firstOrNew(['provider' => $provider]);
            $savedSettings = (array) $setting->settings;
            $savedSettings['admin_enabled'] = $enabled;
            $setting->settings = $savedSettings;
            $setting->save();
        } catch (\Throwable) {
            // Cache پایین‌تر همچنان امکان کنترل وضعیت را حفظ می‌کند.
        }

        Cache::forever(self::cacheKey($provider), $enabled);
    }

    /**
     * وضعیت همه providerها به‌صورت آرایه — برای نمایش در UI ادمین.
     *
     * @return array<string,bool>  ['openrouter' => true, 'fal' => false, 'replicate' => false]
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

    protected static function defaultFor(string $provider): bool
    {
        // پرووایدری که کلیدش قبلاً ثبت شده، در اولین استفاده آماده است؛
        // بعد از اولین تغییر دستی، admin_enabled مرجع قطعی خواهد بود.
        return self::hasCredentials($provider) || (self::DEFAULTS[$provider] ?? false);
    }

    protected static function hasCredentials(string $provider): bool
    {
        try {
            if (AiProviderSetting::forProvider($provider)?->hasApiKey()) {
                return true;
            }
        } catch (\Throwable) {
            // تنظیمات دیتابیس ممکن است هنوز ساخته نشده باشد.
        }

        return filled(config("services.{$provider}.api_key"))
            || filled(config("services.{$provider}.api_token"));
    }
}
