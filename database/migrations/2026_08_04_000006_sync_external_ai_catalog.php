<?php

use App\Services\AiCatalogSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!config('services.ai.catalog_sync_on_migrate', true) || !Schema::hasTable('ai_models')) return;

        try {
            app(AiCatalogSyncService::class)->sync('all');
        } catch (\Throwable $e) {
            Log::warning('AI catalog sync during migration was skipped', ['message' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        // کاتالوگ خارجی داده‌ی کاربر نیست و در rollback حذف نمی‌شود.
    }
};
