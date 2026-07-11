<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ستون‌های قدیمی model_id/provider جدول ai_models از migration اولیه
     * (2026_06_29_210117) هرگز به‌درستی حذف نشدند — migration پاکسازی
     * (2026_07_04_232249) لیست حذفش این دو ستون را شامل نمی‌شد، و چون
     * model_id از نوع NOT NULL بدون مقدار پیش‌فرض بود، هر INSERT جدید
     * (مثلاً از AiModelSeeder) که این فیلد را ست نمی‌کرد با خطای
     * «Field 'model_id' doesn't have a default value» متوقف می‌شد.
     * داده‌های این دو ستون قبلاً در migration 2026_07_07_043100 به
     * openrouter_model_id / provider_name منتقل شده‌اند، پس حذف امن است.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        Schema::table('ai_models', function (Blueprint $table) {
            $columnsToDrop = array_filter(
                ['model_id', 'provider'],
                fn ($column) => Schema::hasColumn('ai_models', $column)
            );

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'model_id')) {
                $table->string('model_id')->nullable()->after('name');
            }
            if (!Schema::hasColumn('ai_models', 'provider')) {
                $table->string('provider')->nullable()->after('model_id');
            }
        });
    }
};
