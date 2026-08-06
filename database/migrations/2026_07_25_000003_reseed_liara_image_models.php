<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * Re-seed مدل‌های تصویری لیارا با شناسه‌های صحیح API (provider/model)
 * ─────────────────────────────────────────────────────────────────────
 * منبع رسمی: https://console.liara.ir/ai/6a6494fdfe9fa573b97b9a9c/models
 *
 * علت وجود این migration:
 *   1) migration قبلی (2026_07_25_000002_seed_liara_ai_models) از تابع
 *      حذف‌شدهٔ `array_except()` استفاده کرده — اجرای دوم آن خطا می‌ده.
 *   2) شناسه‌های مدل باید با پیشوند provider ارسال بشن
 *      (مثل `openai/gpt-image-1-mini`, `google/gemini-2.5-flash-image`)
 *      وگرنه API لیارا مدل رو پیدا نمی‌کنه.
 *   3) دو مدل جدید Google اضافه شدن که در migration قبلی نبودن.
 *
 * این migration کاملاً idempotent است — اجرای دوباره داده تکراری نمی‌سازه
 * و شناسه‌های اشتباه قبلی (بدون prefix) رو هم پاک می‌کنه.
 *
 * ⚠️ نکته: در حال حاضر (تیر ۱۴۰۵) لیارا مدل خروجی video نداره؛
 * فقط مدل‌های image output. اگر بعداً اضافه شدن، در migration جدا اضافه شن.
 * ═══════════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        // مرحلهٔ ۱: پاک کردن شناسه‌های اشتباه قبلی (بدون prefix provider)
        // این‌ها از migration قبلی 2026_07_25_000002 مانده‌اند و به API نمی‌خورند.
        $legacyIds = [
            'gpt-image-1-mini',
            'gpt-image-1',
            'gpt-image-1.5',
            'gpt-image-2',
            'gemini-2.5-flash-image',
            'gemini-3-pro-image-preview',
            'dall-e-3',
        ];
        DB::table('ai_models')
            ->where('provider', 'liara')
            ->whereIn('openrouter_model_id', $legacyIds)
            ->delete();

        // مرحلهٔ ۲: درج/به‌روز‌رسانی شناسه‌های صحیح با پیشوند provider
        foreach ($this->models() as $data) {
            $existing = AiModel::where('openrouter_model_id', $data['openrouter_model_id'])
                ->where('provider', 'liara')
                ->first();

            if (!$existing) {
                AiModel::create($data);
            } else {
                // بدون اسم فارسی و توضیحات و وضعیت را جایگزین نمی‌کنیم اگر ادمین دستی عوض کرده
                $existing->update(Arr::except($data, [
                    'openrouter_model_id', 'provider', 'is_active', 'name', 'description',
                ]));
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        $ids = array_column($this->models(), 'openrouter_model_id');
        AiModel::whereIn('openrouter_model_id', $ids)
            ->where('provider', 'liara')
            ->delete();
    }

    /**
     * لیست کامل مدل‌های تصویری لیارا (منبع: کنسول لیارا — تیر ۱۴۰۵).
     * شناسهٔ مدل = دقیقاً همان چیزی که در فیلد `model` به API لیارا ارسال می‌شود.
     */
    private function models(): array
    {
        $openAiBase = [
            'provider'             => 'liara',
            'provider_name'        => 'OpenAI (Liara)',
            'output_modality'      => 'image',
            'supports_image_input' => true,
            'default_width'        => 1024,
            'default_height'       => 1024,
            'default_parameters'   => null,
        ];

        $googleBase = [
            'provider'             => 'liara',
            'provider_name'        => 'Google (Liara)',
            'output_modality'      => 'image',
            'supports_image_input' => true,
            'default_width'        => 1024,
            'default_height'       => 1024,
            'default_parameters'   => null,
        ];

        return [
            // ─── OpenAI via Liara ────────────────────────────────────────────
            array_merge($openAiBase, [
                'name'                => 'GPT Image 1 Mini — لیارا',
                'openrouter_model_id' => 'openai/gpt-image-1-mini',
                'cost_per_generation' => 1,
                'is_active'           => true,   // ✅ فعال در پلن میرزاخانی
                'description'         => 'نسخه اقتصادی و سریع GPT Image 1 از طریق لیارا — بدون VPN. مناسب تولید انبوه تصویر با هزینه پایین. فعال در پلن میرزاخانی.',
            ]),
            array_merge($openAiBase, [
                'name'                => 'GPT Image 1 — لیارا',
                'openrouter_model_id' => 'openai/gpt-image-1',
                'cost_per_generation' => 6,
                'is_active'           => false,  // نیازمند ارتقا پلن
                'description'         => 'مدل قدرتمند تولید و ویرایش تصویر OpenAI از طریق لیارا — رندر دقیق متن، پشتیبانی از پس‌زمینه شفاف و ویرایش با تصویر مرجع. نیازمند ارتقا پلن.',
            ]),
            array_merge($openAiBase, [
                'name'                => 'GPT Image 1.5 — لیارا',
                'openrouter_model_id' => 'openai/gpt-image-1.5',
                'cost_per_generation' => 7,
                'is_active'           => false,  // نیازمند ارتقا پلن
                'description'         => 'نسخه ارتقایافته GPT Image 1 با وفاداری بیشتر به پرامپت و کیفیت بالاتر — از طریق لیارا. نیازمند ارتقا پلن.',
            ]),
            array_merge($openAiBase, [
                'name'                => 'GPT Image 2 — لیارا',
                'openrouter_model_id' => 'openai/gpt-image-2',
                'cost_per_generation' => 5,
                'is_active'           => false,  // نیازمند ارتقا پلن
                'description'         => 'جدیدترین مدل تولید تصویر OpenAI از طریق لیارا با وفاداری بسیار بالا به پرامپت. نیازمند ارتقا پلن.',
            ]),

            // ─── Google via Liara ────────────────────────────────────────────
            array_merge($googleBase, [
                'name'                => 'Gemini 2.5 Flash Image (Nano Banana) — لیارا',
                'openrouter_model_id' => 'google/gemini-2.5-flash-image',
                'cost_per_generation' => 2,
                'is_active'           => false,  // نیازمند ارتقا پلن
                'description'         => 'مدل سریع تولید تصویر Google Gemini 2.5 Flash (Nano Banana) از طریق لیارا — بدون VPN. نیازمند ارتقا پلن.',
            ]),
            array_merge($googleBase, [
                'name'                => 'Nano Banana Pro (Gemini 3 Pro Image Preview) — لیارا',
                'openrouter_model_id' => 'google/gemini-3-pro-image-preview',
                'cost_per_generation' => 8,
                'is_active'           => false,  // نیازمند ارتقا پلن
                'description'         => 'مدل پیشرفته تولید تصویر Google Gemini 3 Pro Image Preview (Nano Banana Pro) از طریق لیارا — کیفیت بسیار بالا. نیازمند ارتقا پلن.',
            ]),
        ];
    }
};
