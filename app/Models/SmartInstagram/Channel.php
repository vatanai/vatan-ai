<?php

namespace App\Models\SmartInstagram;

use App\Models\MarketingIntegration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    protected $table = 'instagram_channels';

    protected $fillable = [
        'workspace_id', 'marketing_integration_id', 'gateway', 'name', 'external_account_id', 'username', 'account_type',
        'status', 'outbound_enabled', 'last_event_at', 'health_checked_at', 'token_expires_at', 'last_error', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'outbound_enabled' => 'boolean',
            'last_event_at' => 'datetime',
            'health_checked_at' => 'datetime',
            'token_expires_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(MarketingIntegration::class, 'marketing_integration_id');
    }

    public function isHealthy(): bool
    {
        return $this->status === 'connected';
    }

    /** شناسه‌های واقعی خود حساب (بدون مقدار نمادین «me»). */
    public function ownAccountIds(): array
    {
        return array_values(array_unique(array_map('strval', array_filter([
            $this->external_account_id,
            data_get($this->settings, 'composio_instagram_user_id'),
            data_get($this->settings, 'instagram_account_id'),
        ], fn ($id) => filled($id) && $id !== 'me'))));
    }

    /** آیا فرستنده‌ی رویداد خود پیج است (پاسخ‌هایی که خود سامانه زیر کامنت‌ها می‌گذارد). */
    public function isOwnActor(?string $id, ?string $username): bool
    {
        $id = trim((string) $id);
        if ($id !== '' && in_array($id, $this->ownAccountIds(), true)) {
            return true;
        }
        $username = mb_strtolower(ltrim(trim((string) $username), '@'));
        $own = mb_strtolower(ltrim(trim((string) $this->username), '@'));

        return $username !== '' && $own !== '' && $username === $own;
    }

    /**
     * وب‌هوک رسمی Meta برای این حساب فعال است؟ (رویداد امضاشده در ۷ روز اخیر رسیده)
     * در این حالت دکمه‌ها قالب واقعی دارند و همگام‌سازی سریع لازم نیست.
     */
    public function hasLiveWebhook(): bool
    {
        $at = data_get($this->settings, 'meta_webhook_at');
        if (!$at) {
            return false;
        }
        try {
            return \Illuminate\Support\Carbon::parse($at)->greaterThan(now()->subDays(7));
        } catch (\Throwable) {
            return false;
        }
    }
}
