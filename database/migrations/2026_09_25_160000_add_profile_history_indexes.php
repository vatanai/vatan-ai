<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ایندکس‌های مسیرهای تاریخچهٔ پروفایل و گالری.
     *
     * همهٔ ایندکس‌ها با بررسی وجود جدول، ستون‌ها و نام ایندکس ساخته می‌شوند
     * تا اجرای migration روی نسخه‌های قدیمی یا محیط‌های ناقص متوقف نشود.
     */
    public function up(): void
    {
        foreach ([
            [
                'table' => 'generated_images',
                'columns' => ['user_id', 'created_at', 'id'],
                'name' => 'generated_images_user_created_id_index',
            ],
            [
                'table' => 'generated_videos',
                'columns' => ['user_id', 'created_at', 'id'],
                'name' => 'generated_videos_user_created_id_index',
            ],
            [
                'table' => 'saved_products',
                'columns' => ['user_id', 'created_at', 'id'],
                'name' => 'saved_products_user_created_id_index',
            ],
            [
                'table' => 'face_profiles',
                'columns' => ['user_id', 'status', 'created_at', 'id'],
                'name' => 'face_profiles_user_status_created_id_index',
            ],
            [
                'table' => 'user_gallery_items',
                'columns' => ['user_id', 'created_at', 'id'],
                'name' => 'user_gallery_items_user_created_id_index',
            ],
        ] as $index) {
            $this->addIndexIfMissing($index['table'], $index['columns'], $index['name']);
        }
    }

    public function down(): void
    {
        foreach ([
            ['table' => 'generated_images', 'name' => 'generated_images_user_created_id_index'],
            ['table' => 'generated_videos', 'name' => 'generated_videos_user_created_id_index'],
            ['table' => 'saved_products', 'name' => 'saved_products_user_created_id_index'],
            ['table' => 'face_profiles', 'name' => 'face_profiles_user_status_created_id_index'],
            ['table' => 'user_gallery_items', 'name' => 'user_gallery_items_user_created_id_index'],
        ] as $index) {
            if (! Schema::hasTable($index['table']) || ! $this->indexExists($index['table'], $index['name'])) {
                continue;
            }

            Schema::table($index['table'], function (Blueprint $table) use ($index): void {
                $table->dropIndex($index['name']);
            });
        }
    }

    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)
            || collect($columns)->contains(fn (string $column): bool => ! Schema::hasColumn($table, $column))
            || $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return collect(Schema::getIndexes($table))
                ->contains(fn (array $index): bool => ($index['name'] ?? null) === $name);
        }

        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn (object $index): bool => (string) $index->Key_name === $name);
    }
};
