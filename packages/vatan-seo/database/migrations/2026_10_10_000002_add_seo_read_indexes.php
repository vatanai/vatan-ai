<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ایندکس‌های خواندن پرتکرار پنل؛ مستقل و قابل اجرای دوباره. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_tasks')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('seo_tasks'))->pluck('name')->all();
        if (! in_array('seo_tasks_site_due_status_index', $indexes, true)) {
            Schema::table('seo_tasks', static function (Blueprint $table): void {
                $table->index(['site_id', 'due_on', 'status'], 'seo_tasks_site_due_status_index');
            });
        }
        if (! in_array('seo_tasks_site_kind_status_due_index', $indexes, true)) {
            Schema::table('seo_tasks', static function (Blueprint $table): void {
                $table->index(['site_id', 'kind', 'status', 'due_on'], 'seo_tasks_site_kind_status_due_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_tasks')) {
            return;
        }
        $indexes = collect(Schema::getIndexes('seo_tasks'))->pluck('name')->all();
        if (in_array('seo_tasks_site_due_status_index', $indexes, true)) {
            Schema::table('seo_tasks', static fn (Blueprint $table) => $table->dropIndex('seo_tasks_site_due_status_index'));
        }
        if (in_array('seo_tasks_site_kind_status_due_index', $indexes, true)) {
            Schema::table('seo_tasks', static fn (Blueprint $table) => $table->dropIndex('seo_tasks_site_kind_status_due_index'));
        }
    }
};
