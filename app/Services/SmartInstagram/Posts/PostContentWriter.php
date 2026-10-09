<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\Product;
use App\Models\SmartInstagram\AiRun;
use App\Models\SmartInstagram\Post;
use App\Services\OpenRouterService;
use App\Services\SmartInstagram\Ai\AiProfileService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Str;

/**
 * «اجرا با هوش مصنوعی» در ویزارد ثبت پست: بر اساس کپشن پست، محصول/لینک و سبک گفتمان برند،
 * فیلدهای پیام را پر می‌کند. خروجی فقط پیشنهاد است و تا ذخیره‌ی مدیر اعمال نمی‌شود.
 */
class PostContentWriter
{
    private const TARGET_SCALARS = [
        'opening_text' => ['section' => 'opening', 'label' => 'متن پیام آغاز دایرکت'],
        'opening_button' => ['section' => 'opening', 'label' => 'متن دکمه‌ی آغاز دایرکت'],
        'follow_text' => ['section' => 'follow', 'label' => 'پیام اول فالو'],
        'follow_retry_text' => ['section' => 'follow', 'label' => 'پیام یادآوری فالو'],
        'follow_button' => ['section' => 'follow', 'label' => 'متن دکمه‌ی فالو'],
        'card_intro' => ['section' => 'card', 'label' => 'پیام قبل از کارت'],
        'card_title' => ['section' => 'card', 'label' => 'تیتر کارت'],
        'card_subtitle' => ['section' => 'card', 'label' => 'توضیح کارت'],
    ];

    public function __construct(
        private readonly PostAiSettings $settings,
        private readonly AiProfileService $profiles,
        private readonly WorkspaceContext $context,
    ) {
    }

    public static function targetSection(?string $targetField): ?string
    {
        if (!$targetField) return null;
        if (isset(self::TARGET_SCALARS[$targetField])) return self::TARGET_SCALARS[$targetField]['section'];
        if (preg_match('/^(public_replies|card_buttons)\.[0-2]$/', $targetField, $match)) {
            return $match[1] === 'public_replies' ? 'public_reply' : 'card';
        }

        return null;
    }

    /** @param array<int,string> $sections */
    public function generate(array $sections, ?Post $post, array $context, ?string $targetField = null): array
    {
        $config = $this->settings->get();
        $sections = array_values(array_intersect($sections, ['public_reply', 'opening', 'follow', 'card'])) ?: ['public_reply', 'opening', 'follow', 'card'];
        $targetSection = self::targetSection($targetField);
        if ($targetField && !$targetSection) {
            return ['ok' => false, 'message' => 'فیلد هدف برای تولید نمونه معتبر نیست.'];
        }
        if ($targetSection) $sections = [$targetSection];
        $product = !empty($context['product_id']) ? Product::query()->whereKey((int) $context['product_id'])->first() : null;

        $targetMeta = $targetField ? $this->targetMeta($targetField) : null;
        $instructions = $targetMeta
            ? '### '.$targetMeta['label']."\nفقط همین فیلد را تولید کن و هیچ فیلد دیگری نساز.\n".$config['prompts'][$targetMeta['section']]
            : collect($sections)->map(fn ($key) => '### '.PostAiSettings::SECTIONS[$key]."\n".$config['prompts'][$key])->implode("\n\n");
        $schema = [
            'public_reply' => '"public_replies":["...","...","..."]',
            'opening' => '"opening_text":"...","opening_button":"..."',
            'follow' => '"follow_text":"...","follow_retry_text":"...","follow_button":"..."',
            'card' => '"card_intro":"...","card_title":"...","card_subtitle":"...","card_buttons":["..."]',
        ];
        $targetSchema = [
            'public_replies' => '"public_replies":["..."]',
            'card_buttons' => '"card_buttons":["..."]',
        ] + collect(self::TARGET_SCALARS)->mapWithKeys(fn ($meta, $key) => [$key => '"'.$key.'":"..."'])->all();
        $schemaText = $targetMeta
            ? $targetSchema[$targetMeta['key']]
            : collect($sections)->map(fn ($k) => $schema[$k])->implode(',');
        $profile = $this->profiles->active();
        $system = $this->settings->sharedRules()."\n"
            ."## سبک گفتمان برند\n".Str::limit((string) $profile->persona_prompt, 1500)."\n"
            ."## عبارت‌های ممنوع\n".implode('، ', (array) $profile->forbidden_phrases ?: ['—'])."\n\n"
            ."## وظیفه‌ها\n{$instructions}\n\n"
            ."قواعد تکمیلی: متغیرهای {name} و {username} را برای جای‌گذاری بعدی سالم نگه دار. اگر دایرکت یا فالو در تنظیمات این سناریو فعال نیست، درباره‌ی آن وعده نده.\n"
            .(in_array('public_reply', $sections, true)
                ? "قاعده‌ی ثابت پاسخ عمومی: کامنت‌گذار معمولاً فقط کلمه‌ی کلیدی را نوشته و چیزی خواسته، نه نظری داده؛ پس پاسخ‌ها باید درخواستش را تأیید کنند (مثلاً «برات توی دایرکت فرستادیم»). "
                    ."از واکنش به نظر یا تعریف از کامنت مثل «کاملاً موافقم»، «ایده‌ات جالبه»، «حق با توئه» یا «حتماً امتحانش کن» استفاده نکن. "
                    ."هر پاسخ یک جمله‌ی کوتاه محاوره‌ای (حداکثر ۱۲ کلمه) مثل نوشته‌ی ادمین واقعی، حداکثر یک ایموجی و بدون علامت تعجب پشت‌سرهم؛ سه پاسخ با ساختار متفاوت.\n"
                : '')
            .(in_array('follow', $sections, true)
                ? "قاعده‌ی ثابت پیام فالو: پیام اول همان پاسخ خصوصی به کامنت است و هم به کسی می‌رسد که فالو ندارد و هم به کاربر تازه‌ای که شاید فالوور باشد؛ "
                    ."پس با «لینک آماده‌ست» شروع کن و شرط را مشروط بگو (مثلاً «اگه هنوز فالو نکردی اول فالو کن، بعد روی «فالو کردم» بزن»)؛ سرزنش یا اجبار نکن.\n"
                : '')
            .(in_array('card', $sections, true)
                ? "قاعده‌ی ثابت کارت: تیتر (حداکثر ۴۰ نویسه) نتیجه‌ای است که مخاطب می‌گیرد، نه اسم ابزار (مثلاً «کلاژ سه‌تایی با عکس خودت» به‌جای «ساخت کلاژ سه‌تایی»). "
                    ."توضیح (حداکثر ۸۰ نویسه) یک مزیت مشخص + سادگی مسیر را بگوید (مثلاً «یه عکس بده، سه قاب ادیتوریال تحویل بگیر؛ بدون آتلیه»). "
                    ."متن دکمه فعل اقدام و شخصی باشد (مثلاً «با عکس خودم بساز»). پیام قبل از کارت یا خالی باشد یا فقط یک جمله‌ی کوتاه شخصی مثل «{name} جان، اینم لینکی که خواستی 👇»؛ شعار تبلیغاتی ننویس.\n"
                : '')
            .'خروجی فقط یک JSON با این کلیدها: {'.$schemaText.'}';

        $user = collect([
            'کپشن پست' => Str::limit((string) ($post?->caption ?? ''), 1200) ?: '—',
            'نوع محتوا' => $post?->kindLabel(),
            'کلمات کلیدی' => implode('، ', (array) ($context['keywords'] ?? [])) ?: '—',
            'محصول' => $product ? $product->name_fa.' — '.Str::limit(strip_tags((string) $product->description_fa), 400) : null,
            'لینک مقصد' => $context['link'] ?? null,
            'توضیح اضافه‌ی مدیر' => $context['hint'] ?? null,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        $started = microtime(true);
        try {
            $response = app(OpenRouterService::class)->generateStructuredText($system, $user, $config['model'] ?: null, (int) config('smart_instagram.ai.timeout', 40));
        } catch (\Throwable $e) {
            $this->log('post_writer', 'failed', null, $e->getMessage(), $started);

            return ['ok' => false, 'message' => 'هوش مصنوعی پاسخ نداد: '.Str::limit($e->getMessage(), 160)];
        }

        $c = (array) $response['content'];
        $clip = fn ($v, int $n) => Str::limit(trim((string) $v), $n, '');
        $fields = array_filter([
            'public_replies' => in_array('public_reply', $sections, true) ? array_values(array_slice(array_map(fn ($v) => $clip($v, 300), array_filter((array) ($c['public_replies'] ?? []))), 0, 3)) : null,
            'opening_text' => isset($c['opening_text']) ? $clip($c['opening_text'], 900) : null,
            'opening_button' => isset($c['opening_button']) ? $clip($c['opening_button'], 20) : null,
            'follow_text' => isset($c['follow_text']) ? $clip($c['follow_text'], 900) : null,
            'follow_retry_text' => isset($c['follow_retry_text']) ? $clip($c['follow_retry_text'], 900) : null,
            'follow_button' => isset($c['follow_button']) ? $clip($c['follow_button'], 20) : null,
            'card_intro' => isset($c['card_intro']) ? $clip($c['card_intro'], 900) : null,
            'card_title' => isset($c['card_title']) ? $clip($c['card_title'], 80) : null,
            'card_subtitle' => isset($c['card_subtitle']) ? $clip($c['card_subtitle'], 80) : null,
            'card_buttons' => isset($c['card_buttons']) ? array_values(array_slice(array_map(fn ($v) => $clip($v, 20), array_filter((array) $c['card_buttons'])), 0, 3)) : null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
        $targetValue = null;
        if ($targetMeta) {
            $targetValue = in_array($targetMeta['key'], ['public_replies', 'card_buttons'], true)
                ? (($fields[$targetMeta['key']] ?? [])[0] ?? null)
                : ($fields[$targetMeta['key']] ?? null);
            $fields = $targetValue !== null && $targetValue !== ''
                ? [$targetMeta['key'] => in_array($targetMeta['key'], ['public_replies', 'card_buttons'], true) ? [$targetValue] : $targetValue]
                : [];
        }
        $this->log('post_writer', 'success', $response, null, $started, $fields);

        return [
            'ok' => true,
            'fields' => $fields,
            'target_field' => $targetField,
            'target_value' => $targetValue,
            'model' => $response['model'] ?? $config['model'],
        ];
    }

    private function targetMeta(string $targetField): ?array
    {
        if (isset(self::TARGET_SCALARS[$targetField])) {
            return self::TARGET_SCALARS[$targetField] + ['key' => $targetField];
        }
        if (preg_match('/^(public_replies|card_buttons)\.[0-2]$/', $targetField, $match)) {
            return [
                'section' => $match[1] === 'public_replies' ? 'public_reply' : 'card',
                'key' => $match[1],
                'label' => $match[1] === 'public_replies' ? 'یک پاسخ عمومی کامنت' : 'متن یک دکمه‌ی کارت',
            ];
        }

        return null;
    }

    private function log(string $purpose, string $status, ?array $response, ?string $error, float $started, ?array $output = null): void
    {
        $usage = (array) ($response['usage'] ?? []);
        AiRun::query()->create([
            'workspace_id' => $this->context->id(),
            'purpose' => $purpose,
            'model' => $response['model'] ?? null,
            'status' => $status,
            'output' => $output,
            'prompt_tokens' => $usage['prompt_tokens'] ?? null,
            'completion_tokens' => $usage['completion_tokens'] ?? null,
            'cost_usd' => isset($usage['cost']) && is_numeric($usage['cost']) ? (float) $usage['cost'] : null,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'error' => $error ? Str::limit($error, 1000) : null,
        ]);
    }
}
