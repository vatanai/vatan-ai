<?php

namespace Vatan\Seo\Ai;

use Vatan\Seo\Models\Site;

/**
 * نقش ← مدل. ترتیب تصمیم:
 *  ۱. بازنویسی دستی سایت (settings.models.{role})
 *  ۲. گروه مدل تعیین‌شده در پروفایل بودجه (models.{role} = fast|writer|research|free|null)
 *  ۳. فهرست کاندیدهای همان گروه در config/seo-engine.php → اولین مدلی که در OpenRouter زنده است
 */
class ModelRouter
{
    public function __construct(private OpenRouterClient $client) {}

    /** @return string[] فهرست مرتب کاندیدها (برای Failover در زمان اجرا) */
    public function candidates(Site $site, string $role): array
    {
        $manual = $site->setting("models.{$role}");
        $group = array_key_exists($role, (array) data_get($site->profile(), 'models', []))
            ? data_get($site->profile(), "models.{$role}")
            : $role;

        if ($group === null && ! $manual) {
            return []; // این نقش در این پروفایل خاموش است (مثلاً تحقیق زنده در پروفایل رایگان)
        }

        $list = (array) config("seo-engine.ai.roles.{$group}", []);
        if ($manual) {
            array_unshift($list, $manual);
        }
        $list = array_values(array_unique(array_filter($list)));

        $live = $this->client->models();
        if ($live) {
            $available = array_values(array_filter($list, fn ($id) => isset($live[$id]) || str_starts_with($id, '~')));
            if ($available) {
                return $available;
            }
        }
        return $list;
    }

    public function resolve(Site $site, string $role): ?string
    {
        return $this->candidates($site, $role)[0] ?? null;
    }

    /** قیمت هر میلیون توکن برای برآورد قبل از تماس */
    public function price(string $model): array
    {
        $live = $this->client->models();
        if (isset($live[$model])) {
            return ['in' => $live[$model]['in'], 'out' => $live[$model]['out']];
        }
        if (str_ends_with($model, ':free')) {
            return ['in' => 0.0, 'out' => 0.0];
        }
        return (array) config('seo-engine.ai.fallback_price_per_million', ['in' => 3.0, 'out' => 12.0]);
    }

    public function isFreeGroup(Site $site, string $role): bool
    {
        return data_get($site->profile(), "models.{$role}") === 'free';
    }
}
