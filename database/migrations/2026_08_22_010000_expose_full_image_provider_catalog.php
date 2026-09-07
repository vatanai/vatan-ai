<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ انتخاب مدل را از وضعیت اجرایی provider جدا می‌کند.
     *
     * خاموش بودن Fal.ai یا OpenRouter ممکن است به‌دلیل قطعی شبکه، شارژ حساب
     * یا تصمیم موقت MVP باشد؛ این وضعیت نباید باعث حذف مدل از گام دوم و
     * آزمایشگاه شود. اجرای واقعی همچنان از ProviderStatus و روتر provider
     * عبور می‌کند و در صورت خاموش/ناسالم بودن، خطای قابل‌فهم ثبت می‌کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')
            || ! Schema::hasColumn('ai_models', 'featured_in_lab')
            || ! Schema::hasColumn('ai_models', 'output_modality')
            || ! Schema::hasColumn('ai_models', 'task_type')) {
            return;
        }

        $models = DB::table('ai_models')
            ->whereIn('provider', ['openrouter', 'fal', 'replicate'])
            ->where('is_active', true)
            ->where('output_modality', 'image')
            ->where(function ($query): void {
                $query
                    // همه مدل‌های متن‌به‌عکس هر سه provider در کاتالوگ
                    // انتخابی دیده می‌شوند.
                    ->where('task_type', 'text_to_image')
                    // مدل‌های GPT تصویری Replicate ممکن است در catalog با
                    // task_type ویرایش تصویر ثبت شده باشند؛ این‌ها هم باید
                    // در گام دوم و آزمایشگاه قابل انتخاب باشند.
                    ->orWhere(function ($gpt): void {
                        $gpt->where('provider', 'replicate')
                            ->where(function ($id): void {
                                $id->where('openrouter_model_id', 'like', '%gpt%')
                                    ->orWhere('external_model_id', 'like', '%gpt%')
                                    ->orWhere('name', 'like', '%GPT%');
                            });
                    });
            });

        $models->update([
            'featured_in_lab' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // این migration فقط نمایش کاتالوگ را ترمیم می‌کند؛ خاموش‌کردن مدل‌ها
        // در rollback می‌تواند انتخاب‌های ذخیره‌شده‌ی محصولات را خراب کند.
    }
};
