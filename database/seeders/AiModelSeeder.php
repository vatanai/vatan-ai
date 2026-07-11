<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Seeder;

/**
 * ══════════════════════════════════════════════════════════════════
 * Seeder مدل‌های هوش مصنوعی تصویری (OpenRouter) — idempotent
 * ──────────────────────────────────────────────────────────────────
 * • ۱۵ مدل تصویری جدید را با نام فارسی و اطلاعات کامل ثبت می‌کند.
 * • اجرای چندباره داده‌ی تکراری نمی‌سازد (کلید یکتا: openrouter_model_id).
 * • تنظیمات دستی مدیر (نام، هزینه، فعال/غیرفعال و…) هنگام اجرای دوباره
 *   بازنویسی نمی‌شوند؛ فقط فیلدهای خالی تکمیل می‌گردند.
 * • هزینه‌ها (cost_per_generation) بر اساس قیمت واقعی OpenRouter
 *   به‌صورت نسبی تخمین زده شده‌اند (هر توکن ≈ ۰٫۰۲۵ دلار) و از داشبورد
 *   قابل ویرایش‌اند.
 *
 * اجرا:
 *   php artisan db:seed --class=Database\\Seeders\\AiModelSeeder
 * یا خودکار از طریق DatabaseSeeder / migration مربوطه هنگام دیپلوی.
 * ══════════════════════════════════════════════════════════════════
 */
class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->models() as $data) {
            $existing = AiModel::where('openrouter_model_id', $data['openrouter_model_id'])->first();

            if (!$existing) {
                AiModel::create($data);
                continue;
            }

            // فقط فیلدهای خالی ردیف موجود تکمیل می‌شوند (بدون دست‌زدن به تنظیمات مدیر)
            $updates = [];
            foreach (['name', 'provider_name', 'output_modality', 'description', 'default_width', 'default_height'] as $field) {
                if (empty($existing->{$field}) && !empty($data[$field])) {
                    $updates[$field] = $data[$field];
                }
            }
            if (!empty($updates)) {
                $existing->update($updates);
            }
        }

        $this->command?->info('✔ مدل‌های هوش مصنوعی تصویری OpenRouter ثبت/تکمیل شدند.');
    }

    /**
     * لیست کامل ۱۵ مدل تصویری + ۳ مدل ویدیویی با اطلاعات OpenRouter (تیر ۱۴۰۵ / July 2026).
     */
    private function models(): array
    {
        $base = [
            'output_modality'      => 'image',
            'supports_image_input' => true,
            'default_width'        => 1024,
            'default_height'       => 1024,
            'default_parameters'   => null,
            'is_active'            => true,
        ];

        $videoBase = [
            'output_modality'      => 'video',
            'supports_image_input' => true, // پشتیبانی از تصویر مرجع برای image-to-video
            'default_width'        => 1080,
            'default_height'       => 1920,
            'default_parameters'   => null,
            'is_active'            => true,
        ];

        return [
            // ─── OpenAI ───────────────────────────────────────────────
            array_merge($base, [
                'name'                => 'جی‌پی‌تی ایمیج ۲ (GPT Image 2)',
                'openrouter_model_id' => 'openai/gpt-image-2',
                'provider_name'       => 'OpenAI',
                'cost_per_generation' => 5,
                'description'         => 'جدیدترین مدل تولید تصویر OpenAI با وفاداری بسیار بالا به پرامپت، رندر دقیق متن داخل تصویر و پشتیبانی کامل از ویرایش تصویر (Images API). مناسب طراحی‌های حرفه‌ای و تبلیغاتی.',
            ]),
            array_merge($base, [
                'name'                => 'جی‌پی‌تی ایمیج ۱ (GPT Image 1)',
                'openrouter_model_id' => 'openai/gpt-image-1',
                'provider_name'       => 'OpenAI',
                'cost_per_generation' => 6,
                'description'         => 'مدل قدرتمند تولید و ویرایش تصویر OpenAI؛ رندر دقیق متن، پشتیبانی از پس‌زمینه شفاف و امکان استفاده از حداکثر ۱۶ تصویر مرجع برای ویرایش.',
            ]),
            array_merge($base, [
                'name'                => 'جی‌پی‌تی ایمیج ۱ مینی (GPT Image 1 Mini)',
                'openrouter_model_id' => 'openai/gpt-image-1-mini',
                'provider_name'       => 'OpenAI',
                'cost_per_generation' => 1,
                'description'         => 'نسخه اقتصادی و سریع GPT Image 1 با کیفیت مطلوب و هزینه پایین؛ بهترین انتخاب برای تولید انبوه تصویر و پیش‌نمایش سریع ایده‌ها.',
            ]),
            array_merge($base, [
                'name'                => 'جی‌پی‌تی ۵.۴ ایمیج ۲ (GPT-5.4 Image 2)',
                'openrouter_model_id' => 'openai/gpt-5.4-image-2',
                'provider_name'       => 'OpenAI',
                'cost_per_generation' => 5,
                'description'         => 'ترکیب هوش زبانی GPT-5.4 با تولید تصویر پیشرفته؛ درک عمیق پرامپت‌های پیچیده و جریان‌های کاری چندوجهی (متن + تصویر + فایل). مناسب سناریوهای خلاقانه و استدلالی.',
            ]),

            // ─── Google ───────────────────────────────────────────────
            array_merge($base, [
                'name'                => 'جمینای ۳.۱ فلش ایمیج — نانو بنانا ۲ (Gemini 3.1 Flash Image)',
                'openrouter_model_id' => 'google/gemini-3.1-flash-image',
                'provider_name'       => 'Google',
                'cost_per_generation' => 3,
                'description'         => 'نسخه پایدار «نانو بنانا ۲»؛ تولید و ویرایش تصویر باکیفیت با سرعت بالا. تعادل عالی بین کیفیت، سرعت و هزینه برای کاربردهای روزمره.',
            ]),
            array_merge($base, [
                'name'                => 'جمینای ۳.۱ فلش ایمیج پیش‌نمایش — نانو بنانا ۲ (Preview)',
                'openrouter_model_id' => 'google/gemini-3.1-flash-image-preview',
                'provider_name'       => 'Google',
                'cost_per_generation' => 3,
                'description'         => 'نسخه پیش‌نمایش (Preview) نانو بنانا ۲؛ جدیدترین قابلیت‌های تولید و ویرایش تصویر گوگل پیش از انتشار پایدار. برای تست ویژگی‌های تازه.',
            ]),
            array_merge($base, [
                'name'                => 'جمینای ۳.۱ فلش لایت ایمیج — نانو بنانا ۲ لایت (Flash Lite)',
                'openrouter_model_id' => 'google/gemini-3.1-flash-lite-image',
                'provider_name'       => 'Google',
                'cost_per_generation' => 1,
                'description'         => 'سریع‌ترین و مقرون‌به‌صرفه‌ترین مدل تصویری گوگل؛ مناسب تولید حجم بالا، اکتشاف سریع ایده‌های بصری و پایپ‌لاین‌های پرسرعت.',
            ]),
            array_merge($base, [
                'name'                => 'جمینای ۳ پرو ایمیج — نانو بنانا پرو (Gemini 3 Pro Image)',
                'openrouter_model_id' => 'google/gemini-3-pro-image',
                'provider_name'       => 'Google',
                'cost_per_generation' => 6,
                'description'         => 'پیشرفته‌ترین مدل تصویری گوگل بر پایه Gemini 3 Pro؛ استدلال چندوجهی قوی، دقت بالا در جزئیات واقعی دنیای اطراف و کیفیت خروجی ممتاز تا رزولوشن‌های بالا.',
            ]),

            // ─── Sourceful (Riverflow) ────────────────────────────────
            array_merge($base, [
                'name'                => 'ریورفلو ۲.۵ پرو (Riverflow V2.5 Pro)',
                'openrouter_model_id' => 'sourceful/riverflow-v2.5-pro',
                'provider_name'       => 'Sourceful',
                'cost_per_generation' => 5,
                'description'         => 'قدرتمندترین مدل سری ریورفلو ۲.۵؛ بالاترین سطح کنترل روی خروجی و کیفیت ممتاز. مناسب طراحی‌های حساس به کیفیت و پروژه‌های برندینگ.',
            ]),
            array_merge($base, [
                'name'                => 'ریورفلو ۲.۵ فست (Riverflow V2.5 Fast)',
                'openrouter_model_id' => 'sourceful/riverflow-v2.5-fast',
                'provider_name'       => 'Sourceful',
                'cost_per_generation' => 1,
                'description'         => 'نسخه سرعت‌محور ریورفلو ۲.۵؛ بهینه برای محیط production و جریان‌های کاری حساس به تأخیر با هزینه بسیار پایین.',
            ]),
            array_merge($base, [
                'name'                => 'ریورفلو ۲ پرو (Riverflow V2 Pro)',
                'openrouter_model_id' => 'sourceful/riverflow-v2-pro',
                'provider_name'       => 'Sourceful',
                'cost_per_generation' => 6,
                'description'         => 'قوی‌ترین مدل سری ریورفلو ۲؛ کنترل سطح‌بالا و کیفیت عالی در رندر متن داخل تصویر. مناسب پوستر، بنر و طراحی‌های متن‌محور.',
            ]),
            array_merge($base, [
                'name'                => 'ریورفلو ۲ فست (Riverflow V2 Fast)',
                'openrouter_model_id' => 'sourceful/riverflow-v2-fast',
                'provider_name'       => 'Sourceful',
                'cost_per_generation' => 1,
                'description'         => 'سریع‌ترین مدل سری ریورفلو ۲ با کارایی SOTA؛ بهینه برای دیپلوی‌های production و کارهای حساس به سرعت با هزینه اندک.',
            ]),

            // ─── xAI ──────────────────────────────────────────────────
            array_merge($base, [
                'name'                => 'گراک ایمَجین — کیفیت بالا (Grok Imagine Image Quality)',
                'openrouter_model_id' => 'x-ai/grok-imagine-image-quality',
                'provider_name'       => 'xAI',
                'cost_per_generation' => 2,
                'description'         => 'مدل سریع و باکیفیت xAI برای تولید و ویرایش تصویر؛ خروجی‌های فتورئالیستی در رزولوشن 1K و 2K با پشتیبانی از تصاویر مرجع.',
            ]),

            // ─── Recraft ──────────────────────────────────────────────
            array_merge($base, [
                'name'                => 'ریکرفت ۴.۱ وکتور (Recraft V4.1 Vector)',
                'openrouter_model_id' => 'recraft/recraft-v4.1-vector',
                'provider_name'       => 'Recraft',
                'cost_per_generation' => 3,
                'description'         => 'مدل تخصصی تولید تصویر وکتوری (SVG) با زیبایی‌شناسی بالا؛ پشتیبانی از ورودی متن و تصویر و نسبت‌های ابعادی متنوع. ایده‌آل برای لوگو، آیکون و طراحی گرافیک.',
            ]),
            array_merge($base, [
                'name'                => 'ریکرفت ۴.۱ پرو وکتور (Recraft V4.1 Pro Vector)',
                'openrouter_model_id' => 'recraft/recraft-v4.1-pro-vector',
                'provider_name'       => 'Recraft',
                'cost_per_generation' => 10,
                'description'         => 'نسخه حرفه‌ای ریکرفت ۴.۱ وکتور با خروجی SVG در رزولوشن بالاتر و جزئیات بیشتر؛ بهترین انتخاب برای برندینگ حرفه‌ای و فایل‌های چاپی وکتوری.',
            ]),

            // ─── مدل‌های ویدیویی (Video Generation) ────────────────────
            // نکته: تا قبل از این Seeder، هیچ مدل ویدیویی در جدول ai_models ثبت نشده بود
            // در حالی‌که محصولاتی با media_type=video/both در پنل قابل تعریف بودند — این باعث
            // می‌شد فیلد primary_model محصولات ویدیویی به هیچ مدل واقعی متصل نباشد.
            array_merge($videoBase, [
                'name'                => 'کلینگ ۲.۵ توربو (Kling V2.5 Turbo)',
                'openrouter_model_id' => 'kwaivgi/kling-v2.5-turbo',
                'provider_name'       => 'Kuaishou',
                'cost_per_generation' => 25,
                'default_width'       => 1080,
                'default_height'      => 1920,
                'description'         => 'مدل تولید ویدیوی کوتاه از تصویر/متن با کیفیت سینمایی و حرکت طبیعی؛ مناسب ریلز، تیزر تبلیغاتی و ویدیوهای مناسبتی عمودی.',
            ]),
            array_merge($videoBase, [
                'name'                => 'ران‌وی جن-۴ توربو (Runway Gen-4 Turbo)',
                'openrouter_model_id' => 'runwayml/gen-4-turbo',
                'provider_name'       => 'Runway',
                'cost_per_generation' => 30,
                'default_width'       => 1920,
                'default_height'      => 1080,
                'description'         => 'مدل تولید ویدیوی سینمایی Runway با کنترل دوربین و حرکت پیشرفته؛ مناسب ویدیوهای کوتاه روایی و تبلیغاتی با کیفیت بالا.',
            ]),
            array_merge($videoBase, [
                'name'                => 'لوما دریم‌مشین ۲ (Luma Dream Machine 2)',
                'openrouter_model_id' => 'luma/dream-machine-2',
                'provider_name'       => 'Luma AI',
                'cost_per_generation' => 18,
                'default_width'       => 1080,
                'default_height'      => 1080,
                'description'         => 'مدل اقتصادی‌تر تولید ویدیو با کیفیت مطلوب و سرعت بالا؛ مناسب تولید انبوه ویدیوهای کوتاه شبکه‌های اجتماعی.',
            ]),
        ];
    }
}
