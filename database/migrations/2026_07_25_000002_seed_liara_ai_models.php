<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed مدل‌های هوش مصنوعی لیارا (پلن میرزاخانی)
 *
 * این migration مدل‌های تصویری لیارا را در جدول ai_models ثبت می‌کند.
 * کاملاً idempotent است — اجرای دوباره داده تکراری نمی‌سازد.
 *
 * مدل‌های تصویری معتبر لیارا (تیر ۱۴۰۵):
 *   gpt-image-1-mini          ← فعال در پلن میرزاخانی
 *   gpt-image-1               ← نیاز به پلن بالاتر
 *   gpt-image-1.5             ← نیاز به پلن بالاتر
 *   gpt-image-2               ← نیاز به پلن بالاتر
 *   gemini-2.5-flash-image    ← نیاز به پلن بالاتر (Google)
 *   gemini-3-pro-image-preview ← نیاز به پلن بالاتر (Google)
 *
 * نکته: dall-e-3 در لیارا وجود ندارد و در این migration حذف می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        // حذف dall-e-3 لیارا اگه وجود داشت (مدل نادرست از نسخه قبلی)
        AiModel::where('openrouter_model_id', 'dall-e-3')
            ->where('provider', 'liara')
            ->delete();

        foreach ($this->models() as $data) {
            $existing = AiModel::where('openrouter_model_id', $data['openrouter_model_id'])
                ->where('provider', 'liara')
                ->first();

            if (!$existing) {
                AiModel::create($data);
            } else {
                // آپدیت فیلدهای مهم در صورت اجرای مجدد
                $existing->update(Arr::except($data, ['openrouter_model_id', 'provider']));
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        $ids = array_column($this->models(), 'openrouter_model_id');
        AiModel::whereIn('openrouter_model_id', $ids)->where('provider', 'liara')->delete();
    }

    /**
     * لیست کامل مدل‌های تصویری لیارا.
     * منبع: API endpoint /v1/models — تیر ۱۴۰۵
     * ID مدل = مقداری که مستقیماً به API لیارا ارسال می‌شود.
     */
    private function models(): array
    {
        $baseOpenAI = [
            'provider'             => 'liara',
            'provider_name'        => 'OpenAI (Liara)',
            'output_modality'      => 'image',
            'supports_image_input' => true,
            'default_width'        => 1024,
            'default_height'       => 1024,
            'default_parameters'   => null,
        ];

        $baseGoogle = [
            'provider'             => 'liara',
            'provider_name'        => 'Google (Liara)',
            'output_modality'      => 'image',
            'supports_image_input' => true,
            'default_width'        => 1024,
            'default_height'       => 1024,
            'default_parameters'   => null,
        ];

        return [
            // ─── OpenAI via Liara ─────────────────────────────────────────────────────

            array_merge($baseOpenAI, [
                'name'                => 'GPT Image 1 Mini — لیارا',
                'openrouter_model_id' => 'gpt-image-1-mini',
                'cost_per_generation' => 1,
                'is_active'           => true,   // ✅ فعال در پلن میرزاخانی
                'description'         => 'نسخه اقتصادی و سریع GPT Image 1 از طریق لیارا — بدون VPN. مناسب تولید انبوه تصویر با هزینه پایین. فعال در پلن میرزاخانی.',
            ]),

            array_merge($baseOpenAI, [
                'name'                => 'GPT Image 1 — لیارا',
                'openrouter_model_id' => 'gpt-image-1',
                'cost_per_generation' => 6,
                'is_active'           => false,  // نیاز به پلن بالاتر
                'description'         => 'مدل قدرتمند تولید و ویرایش تصویر OpenAI از طریق لیارا — رندر دقیق متن، پشتیبانی از پس‌زمینه شفاف و ویرایش با تصویر مرجع. نیاز به ارتقای پلن دارد.',
            ]),

            array_merge($baseOpenAI, [
                'name'                => 'GPT Image 1.5 — لیارا',
                'openrouter_model_id' => 'gpt-image-1.5',
                'cost_per_generation' => 7,
                'is_active'           => false,  // نیاز به پلن بالاتر
                'description'         => 'نسخه ارتقایافته GPT Image 1 با وفاداری بیشتر به پرامپت و کیفیت بالاتر — از طریق لیارا. نیاز به ارتقای پلن دارد.',
            ]),

            array_merge($baseOpenAI, [
                'name'                => 'GPT Image 2 — لیارا',
                'openrouter_model_id' => 'gpt-image-2',
                'cost_per_generation' => 5,
                'is_active'           => false,  // نیاز به پلن بالاتر
                'description'         => 'جدیدترین مدل تولید تصویر OpenAI از طریق لیارا با وفاداری بسیار بالا به پرامپت. نیاز به ارتقای پلن دارد.',
            ]),

            // ─── Google via Liara ─────────────────────────────────────────────────────

            array_merge($baseGoogle, [
                'name'                => 'Gemini Flash Image 2.5 — لیارا',
                'openrouter_model_id' => 'gemini-2.5-flash-image',
                'cost_per_generation' => 2,
                'is_active'           => false,  // نیاز به پلن بالاتر
                'description'         => 'مدل سریع تولید تصویر Google Gemini 2.5 Flash از طریق لیارا — بدون VPN. نیاز به ارتقای پلن دارد.',
            ]),

            array_merge($baseGoogle, [
                'name'                => 'Gemini Pro Image 3 — لیارا (Nano Banana Pro)',
                'openrouter_model_id' => 'gemini-3-pro-image-preview',
                'cost_per_generation' => 8,
                'is_active'           => false,  // نیاز به پلن بالاتر
                'description'         => 'مدل پیشرفته تولید تصویر Google Gemini 3 Pro از طریق لیارا — کیفیت بسیار بالا. نیاز به ارتقای پلن دارد.',
            ]),
        ];
    }
};
