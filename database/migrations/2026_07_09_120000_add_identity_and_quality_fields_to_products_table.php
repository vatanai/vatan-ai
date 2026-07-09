<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * افزودن فیلدهای «نوع سوژه / حفظ هویت» و پارامترهای واقعی کیفیت خروجی.
 * همه فیلدها افزودنی و دارای مقدار پیش‌فرض امن هستند تا رفتار محصولات فعلی تغییر نکند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // ─── نوع سوژه و حفظ هویت (قلب دقت خروجی) ───
            // generic | face | body | product | scene
            $table->string('subject_type')->default('generic')->after('pipeline_type');

            // کلید اصلی «این محصول چهره/هویت‌محور است»
            $table->boolean('identity_preservation')->default(false)->after('subject_type');

            // شدت شباهت (۰ تا ۱۰۰) — در ساخت پرامپت و provider options استفاده می‌شود
            $table->unsignedTinyInteger('identity_strength')->default(80)->after('identity_preservation');

            // علاوه بر چهره، هیکل/تناسب بدن هم حفظ شود
            $table->boolean('preserve_body')->default(false)->after('identity_strength');

            // دستور انگلیسی اضافه که هنگام روشن بودن حفظ هویت به پرامپت افزوده می‌شود
            $table->text('identity_instructions')->nullable()->after('preserve_body');

            // حداقل/حداکثر تعداد تصویر مرجعی که کاربر باید آپلود کند
            $table->unsignedTinyInteger('min_reference_images')->default(0)->after('identity_instructions');
            $table->unsignedTinyInteger('max_reference_images')->default(1)->after('min_reference_images');

            // ─── پارامترهای واقعی مؤثر بر کیفیت (Unified Image API) ───
            // دستور سیستمی/پایه که ابتدای پرامپت نهایی قرار می‌گیرد
            $table->text('system_prompt')->nullable()->after('prompt_template');

            // seed برای بازتولیدپذیری خروجی (NULL = تصادفی)
            $table->integer('seed')->nullable()->after('system_prompt');

            // تنظیمات اختصاصی provider به‌صورت JSON (provider.options اوپن‌روتر)
            $table->json('provider_options')->nullable()->after('seed');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'subject_type',
                'identity_preservation',
                'identity_strength',
                'preserve_body',
                'identity_instructions',
                'min_reference_images',
                'max_reference_images',
                'system_prompt',
                'seed',
                'provider_options',
            ]);
        });
    }
};
