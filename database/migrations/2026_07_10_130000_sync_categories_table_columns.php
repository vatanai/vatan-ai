<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول categories روی Production زودتر از تکمیل شدن migration اصلی ساخته شده
     * و ستون‌های درختی (parent_id) و مرتبط با آن هیچ‌وقت روی دیتابیس واقعی اضافه نشدند
     * چون migration migrations اصلی از قبل «اجرا شده» علامت خورده بود.
     * این migration idempotent است: فقط ستون‌های واقعاً غایب را اضافه می‌کند.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('categories', 'name_fa')) {
                $table->string('name_fa')->nullable();
            }
            if (!Schema::hasColumn('categories', 'name_en')) {
                $table->string('name_en')->nullable();
            }
            if (!Schema::hasColumn('categories', 'path')) {
                $table->string('path')->nullable();
            }
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon')->default('folder');
            }
            if (!Schema::hasColumn('categories', 'color')) {
                $table->string('color', 32)->default('#6B7280');
            }
            if (!Schema::hasColumn('categories', 'image')) {
                $table->string('image')->nullable();
            }
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
            if (!Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('categories', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
            if (!Schema::hasColumn('categories', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (!Schema::hasColumn('categories', 'meta_description')) {
                $table->string('meta_description', 500)->nullable();
            }
            if (!Schema::hasColumn('categories', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
            }
            if (!Schema::hasColumn('categories', 'og_title')) {
                $table->string('og_title')->nullable();
            }
            if (!Schema::hasColumn('categories', 'og_description')) {
                $table->string('og_description', 500)->nullable();
            }
            if (!Schema::hasColumn('categories', 'og_image')) {
                $table->string('og_image')->nullable();
            }
        });

        // فارغ از ترتیب فراخوانی بالا، parent_id باید unsigned big integer باشد تا با id سازگار باشد
        // اضافه کردن foreign key فقط در صورت نبودنش (رفع خطای Duplicate foreign key)
        if (!$this->foreignKeyExists('categories', 'categories_parent_id_foreign')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // اگر داده‌های ناسازگار مانع ساخت foreign key شدند، از build متوقف نشو
                // (صفحات با ستون موجود کار می‌کنند؛ فقط قید ارجاعی اضافه نمی‌شود)
            }
        }

        // اضافه کردن ایندکس یونیک path فقط در صورت نبودنش
        if (Schema::hasColumn('categories', 'path') && !$this->indexExists('categories', 'categories_path_unique')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique('path');
                });
            } catch (\Throwable $e) {
            }
        }

        // اضافه کردن ایندکس یونیک (parent_id, slug) فقط در صورت نبودنش
        if (Schema::hasColumn('categories', 'slug') && !$this->indexExists('categories', 'categories_parent_id_slug_unique')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique(['parent_id', 'slug']);
                });
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        // این migration فقط ستون‌های غایب را ترمیم می‌کند؛ rollback عمداً خالی است
        // تا داده‌های واقعی Production حذف نشوند.
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::connection()->getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, $table, $indexName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $dbName = DB::connection()->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(1) AS cnt FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'",
            [$dbName, $table, $constraintName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }
};
