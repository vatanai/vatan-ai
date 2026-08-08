<?php

use App\Services\AiCatalogSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_models')) {
            app(AiCatalogSyncService::class)->syncReplicateCollection('text-to-image');
        }
    }

    public function down(): void
    {
        // داده‌های کاتالوگ عملیاتی هستند و rollback نباید آن‌ها را حذف کند.
    }
};
