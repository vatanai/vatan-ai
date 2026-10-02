<?php

namespace App\Services\SmartInstagram;

use App\Models\Admin;
use App\Models\SmartInstagram\Workspace;
use App\Models\SmartInstagram\WorkspaceMember;

/**
 * فضای کاری جاری و سطح دسترسی ادمین در آن.
 * فاز اول فقط یک فضای کاری (وطن) دارد، اما همه‌ی کوئری‌ها از همین‌جا workspace_id می‌گیرند
 * تا عرضه‌ی اشتراکی بدون بازنویسی ممکن باشد (پروپوزال بخش ۴).
 */
class WorkspaceContext
{
    private ?Workspace $workspace = null;

    /** @var array<int,string> */
    private array $roles = [];

    private const ABILITIES = [
        'view' => ['owner', 'sales_manager', 'operator', 'content_manager', 'viewer'],
        'view_all_conversations' => ['owner', 'sales_manager', 'content_manager', 'viewer'],
        'reply' => ['owner', 'sales_manager', 'operator'],
        'manage_sales' => ['owner', 'sales_manager'],
        'manage_automation' => ['owner', 'sales_manager', 'content_manager'],
        'manage_knowledge' => ['owner', 'sales_manager', 'content_manager'],
        'manage_settings' => ['owner'],
    ];

    public function workspace(): Workspace
    {
        if ($this->workspace) {
            return $this->workspace;
        }

        $slug = (string) config('smart_instagram.workspace_slug', 'vatan');

        return $this->workspace = Workspace::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => 'وطن', 'status' => 'active', 'settings' => ['business_hours' => ['start' => '09:00', 'end' => '21:00']]]
        );
    }

    public function id(): int
    {
        return (int) $this->workspace()->id;
    }

    public function role(?Admin $admin): string
    {
        if (!$admin) {
            return 'viewer';
        }
        if (isset($this->roles[$admin->id])) {
            return $this->roles[$admin->id];
        }

        $member = WorkspaceMember::query()
            ->where('workspace_id', $this->id())
            ->where('admin_id', $admin->id)
            ->where('is_active', true)
            ->value('role');

        $role = $member ?: ($admin->isLeader() ? 'owner' : (string) config('smart_instagram.default_role', 'operator'));

        return $this->roles[$admin->id] = $role;
    }

    public function can(?Admin $admin, string $ability): bool
    {
        if ($admin?->isLeader()) {
            return true;
        }

        return in_array($this->role($admin), self::ABILITIES[$ability] ?? [], true);
    }

    public function authorize(?Admin $admin, string $ability): void
    {
        abort_unless($this->can($admin, $ability), 403, 'دسترسی این بخش برای نقش شما فعال نیست.');
    }
}
