<?php

use App\Services\AiCatalogSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        // این Collection منبع رسمی مدل‌های text-to-image و image-to-image
        // انتخاب‌شده‌ی Replicate است. سرویس، schema زنده‌ی هر مدل را نیز ثبت می‌کند.
        app(AiCatalogSyncService::class)->syncReplicateCollection('text-to-image');
    }

    public function down(): void
    {
        // کاتالوگ خارجی داده‌ی عملیاتی است؛ rollback نباید مدل‌های قابل‌استفاده را حذف کند.
    }
};
