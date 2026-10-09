<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** دو کارمند اول مجاز بات تلگرام «ثبت پست» (idempotent؛ ردیف موجود دست نمی‌خورد). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('instagram_telegram_admins')) {
            return;
        }
        $slug = (string) config('smart_instagram.workspace_slug', 'vatan');
        $workspaceId = DB::table('instagram_workspaces')->where('slug', $slug)->value('id');
        if (!$workspaceId) {
            $workspaceId = DB::table('instagram_workspaces')->insertGetId([
                'slug' => $slug, 'name' => 'وطن', 'status' => 'active',
                'settings' => json_encode(['business_hours' => ['start' => '09:00', 'end' => '21:00']]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ([['101754869', 'ساغر محمدی'], ['6234518857', 'عاطفه جورسرایی']] as [$telegramId, $name]) {
            DB::table('instagram_telegram_admins')->insertOrIgnore([
                'workspace_id' => $workspaceId, 'telegram_id' => $telegramId, 'name' => $name, 'role' => 'manager',
                'is_active' => true, 'notify_new_posts' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
