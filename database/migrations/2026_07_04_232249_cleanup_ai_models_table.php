<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $blueprint) {
            // حذف کامل ستون‌های اضافی و تکراری از دیتابیس
            // نکته: چون در برخی محیط‌ها این ستون‌ها اصلاً ساخته نشده بودند
            // (و همین باعث خطای «Column not found» و توقف کل زنجیره migrate می‌شد)،
            // هر ستون فقط در صورت وجود واقعی حذف می‌شود.
            $columnsToDrop = array_filter([
                'landscape_width',
                'landscape_height',
                'total_generations',
                'total_tokens_consumed',
                'output_type',
            ], fn ($column) => Schema::hasColumn('ai_models', $column));

            if (!empty($columnsToDrop)) {
                $blueprint->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $blueprint) {
            // متد بازگشت (در صورت نیاز به Rollback مایگریشن)
            $blueprint->integer('landscape_width')->unsigned()->nullable()->after('default_height');
            $blueprint->integer('landscape_height')->unsigned()->nullable()->after('landscape_width');
            $blueprint->integer('total_generations')->unsigned()->default(0)->after('is_active');
            $blueprint->bigInteger('total_tokens_consumed')->unsigned()->default(0)->after('total_generations');
            $blueprint->enum('output_type', ['text', 'image', 'audio', 'video'])->default('text')->after('updated_at');
        });
    }
};