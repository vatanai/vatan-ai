<?php

use Database\Seeders\FalAiCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        (new FalAiCatalogSeeder())->run();
    }

    public function down(): void
    {
        // مدل‌های خارجی ممکن است به محصول یا آزمایش متصل شده باشند؛ rollback آن‌ها را حذف نمی‌کند.
    }
};
