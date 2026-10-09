<?php

namespace App\Models\SmartInstagram;

use App\Models\Admin;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * فهرست مجاز بات تلگرام «ثبت پست»: هر شناسه‌ی تلگرام اینجا باشد، از بات و مینی‌اپ استفاده می‌کند.
 * یا کارمندی است که مدیر با شناسه اضافه کرده (role)، یا ادمینی که حسابش را از پنل وصل کرده (admin_id).
 */
class TelegramAdmin extends Model
{
    public const ROLES = [
        'manager' => 'مدیر پست‌ها (تنظیم و فعال‌سازی)',
        'viewer' => 'فقط مشاهده',
    ];

    protected $table = 'instagram_telegram_admins';

    protected $fillable = [
        'workspace_id', 'admin_id', 'telegram_id', 'name', 'role', 'added_by', 'username', 'first_name',
        'is_active', 'notify_new_posts', 'linked_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'notify_new_posts' => 'boolean', 'linked_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function displayName(): string
    {
        return trim((string) ($this->name ?: $this->admin?->name ?: $this->first_name)) ?: 'همکار';
    }

    /** دسترسی: اگر به ادمین وصل است نقش ادمین، وگرنه نقش همین ردیف. */
    public function allows(string $ability): bool
    {
        if ($this->admin) {
            return app(WorkspaceContext::class)->can($this->admin, $ability);
        }

        return $ability === 'view' || ($this->role === 'manager' && in_array($ability, ['manage_automation'], true));
    }
}
