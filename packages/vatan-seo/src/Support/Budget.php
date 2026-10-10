<?php

namespace Vatan\Seo\Support;

use Vatan\Seo\Models\AiCall;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;

/**
 * پروفایل بودجه و سقف هزینه.
 * سقف ماهانه «سخت» است: اگر هزینه‌ی ماه جاری + برآورد تماس بیشتر از سقف شود، تماس انجام نمی‌شود.
 * ۱۵٪ سقف برای کارهای حیاتی (گزارش و هشدار) رزرو است و کارهای غیرحیاتی نمی‌توانند آن را مصرف کنند.
 */
class Budget
{
    public static function tiers(): array
    {
        return config('seo-budget-tiers', []);
    }

    public static function tierKeyFor(float $usd): string
    {
        $chosen = 'free';
        $best = -1;
        foreach (self::tiers() as $key => $tier) {
            $min = (float) ($tier['min_usd'] ?? 0);
            if ($usd >= $min && $min > $best) {
                $chosen = $key;
                $best = $min;
            }
        }
        return $chosen;
    }

    public static function profileFor(Site $site): array
    {
        $key = $site->setting('tier_override') ?: self::tierKeyFor((float) $site->monthly_budget_usd);
        $tiers = self::tiers();
        $profile = $tiers[$key] ?? ($tiers['free'] ?? []);
        $profile['key'] = $key;
        $overrides = (array) $site->setting('overrides', []);

        return array_replace_recursive($profile, $overrides);
    }

    public static function limit(Site $site, string $key, int $default = 0): int
    {
        return (int) data_get($site->profile(), "limits.{$key}", $default);
    }

    public static function spentThisMonth(Site $site): float
    {
        return (float) AiCall::query()
            ->where('site_id', $site->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost_usd');
    }

    public static function cap(Site $site): float
    {
        return (float) $site->monthly_budget_usd;
    }

    public static function remaining(Site $site, bool $critical = false): float
    {
        $cap = self::cap($site);
        $reserve = $critical ? 0 : $cap * (float) config('seo-engine.ai.budget_reserve_ratio', 0.15);

        return max(0, $cap - $reserve - self::spentThisMonth($site));
    }

    public static function canSpend(Site $site, float $estimate, bool $critical = false): bool
    {
        // پروفایل رایگان: فقط مدل‌های بدون هزینه مجازند
        if (self::cap($site) <= 0) {
            return $estimate <= 0;
        }
        return $estimate <= self::remaining($site, $critical);
    }

    public static function usageRatio(Site $site): float
    {
        $cap = self::cap($site);
        return $cap > 0 ? min(1, self::spentThisMonth($site) / $cap) : 0;
    }

    /** پیش‌بینی هزینه‌ی کل ماه با نرخ مصرف فعلی */
    public static function forecast(Site $site): float
    {
        $day = max(1, now()->day);
        return round(self::spentThisMonth($site) / $day * now()->daysInMonth, 3);
    }

    public static function articlesThisMonth(Site $site): int
    {
        return ContentItem::query()
            ->where('site_id', $site->id)
            ->whereNotIn('status', ['idea', 'brief', 'failed'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public static function researchCallsThisMonth(Site $site): int
    {
        return AiCall::query()
            ->where('site_id', $site->id)
            ->where('web_search', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }
}
