<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * گزارش خلاصهٔ داشبورد چند منبع را بر اساس زمان آخرین رکورد می‌خواند.
     * ایندکس مستقل زمان، مرتب‌سازی را از اسکن و sort کل جدول خارج می‌کند.
     */
    public function up(): void
    {
        foreach ([
            ['table' => 'orders', 'column' => 'created_at', 'name' => 'orders_created_at_index'],
            ['table' => 'ai_provider_requests', 'column' => 'created_at', 'name' => 'ai_provider_requests_created_at_index'],
            ['table' => 'lab_runs', 'column' => 'created_at', 'name' => 'lab_runs_created_at_index'],
            ['table' => 'generated_images', 'column' => 'created_at', 'name' => 'generated_images_created_at_index'],
        ] as $index) {
            $this->addIndexIfMissing($index['table'], $index['column'], $index['name']);
        }
    }

    public function down(): void
    {
        foreach ([
            ['table' => 'orders', 'name' => 'orders_created_at_index'],
            ['table' => 'ai_provider_requests', 'name' => 'ai_provider_requests_created_at_index'],
            ['table' => 'lab_runs', 'name' => 'lab_runs_created_at_index'],
            ['table' => 'generated_images', 'name' => 'generated_images_created_at_index'],
        ] as $index) {
            if (! Schema::hasTable($index['table']) || ! $this->indexExists($index['table'], $index['name'])) {
                continue;
            }

            Schema::table($index['table'], function (Blueprint $table) use ($index): void {
                $table->dropIndex($index['name']);
            });
        }
    }

    private function addIndexIfMissing(string $table, string $column, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $name): void {
            $blueprint->index($column, $name);
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn (object $index): bool => (string) $index->Key_name === $name);
    }
};
