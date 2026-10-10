<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Vatan\Seo\Support\Budget;

class Site extends SeoModel
{
    protected $table = 'seo_sites';

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'monthly_budget_usd' => 'decimal:2',
            'started_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function keywords(): HasMany { return $this->hasMany(Keyword::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
    public function scenarios(): HasMany { return $this->hasMany(Scenario::class); }
    public function runs(): HasMany { return $this->hasMany(Run::class); }
    public function contentItems(): HasMany { return $this->hasMany(ContentItem::class); }
    public function alerts(): HasMany { return $this->hasMany(Alert::class); }
    public function dailyMetrics(): HasMany { return $this->hasMany(DailyMetric::class); }
    public function audits(): HasMany { return $this->hasMany(Audit::class); }

    /** پروفایل بودجه‌ی فعال این سایت (با اعمال overrides) */
    public function profile(): array
    {
        return Budget::profileFor($this);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    public function putSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /** ثبت «مرور خلاصه‌ی امروز» برای تیک خودکار تسک روزانه */
    public function markSeen(): void
    {
        $today = now(config('seo-engine.host.timezone', 'Asia/Tehran'))->toDateString();
        $days = (array) $this->setting('seen_days', []);
        $firstViewToday = ! in_array($today, $days, true);
        if ($firstViewToday) {
            $days[] = $today;
            $this->putSetting('seen_days', array_slice($days, -30));
            $this->save();
        }
        if ($firstViewToday) {
            \Vatan\Seo\Models\Task::where('site_id', $this->id)->where('playbook_key', 'day.brief')->where('due_on', $today)
                ->whereNotIn('status', ['done', 'skipped'])->update(['status' => 'done', 'completed_at' => now(), 'completed_by' => 'admin', 'last_message' => 'خلاصه‌ی امروز مرور شد.']);
        }
    }

    public function connectorConfig(): array
    {
        if (blank($this->connector_config)) {
            return [];
        }
        try {
            return (array) json_decode(Crypt::decryptString($this->connector_config), true);
        } catch (\Throwable) {
            return [];
        }
    }

    public function setConnectorConfig(array $config): void
    {
        $this->connector_config = Crypt::encryptString(json_encode($config, JSON_UNESCAPED_UNICODE));
    }

    public function url(string $path = ''): string
    {
        if ($path === '' || str_starts_with($path, 'http')) {
            return $path ?: rtrim($this->base_url, '/');
        }
        return rtrim($this->base_url, '/').'/'.ltrim($path, '/');
    }

    public function gscProperty(): ?string
    {
        return $this->gsc_property ?: config('seo-engine.google.gsc_property') ?: ('sc-domain:'.$this->domain);
    }
}
