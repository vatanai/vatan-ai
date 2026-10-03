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
    public function __construct(
        private readonly PostAiSettings $settings,
        private readonly AiProfileService $profiles,
        private readonly WorkspaceContext $context,
    ) {
    }

    /** @param array<int,string> $sections */
    public function generate(array $sections, ?Post $post, array $context): array
    {
        $config = $this->settings->get();
        $sections = array_values(array_intersect($sections, ['public_reply', 'opening', 'follow', 'card'])) ?: ['public_reply', 'opening', 'follow', 'card'];
        $product = !empty($context['product_id']) ? Product::query()->whereKey((int) $context['product_id'])->first() : null;

        $instructions = collect($sections)->map(fn ($key) => '### '.PostAiSettings::SECTIONS[$key]."\n".$config['prompts'][$key])->implode("\n\n");
        $schema = [
            'public_reply' => '"public_replies":["...","...","..."]',
            'opening' => '"opening_text":"...","opening_button":"..."',
            'follow' => '"follow_text":"...","follow_retry_text":"...","follow_button":"..."',
            'card' => '"card_intro":"...","card_title":"...","card_subtitle":"...","card_buttons":["..."]',
        ];
        $system = "تو کپی‌رایتر فروش اینستاگرامی برند هستی و فقط فارسی روان و محاوره‌ی مؤدب می‌نویسی.\n"
            ."## سبک گفتمان برند\n".Str::limit((string) $this->profiles->active()->persona_prompt, 1500)."\n\n"
            ."## وظیفه‌ها\n{$instructions}\n\n"
            ."قواعد: متغیر {name} را برای نام مخاطب نگه دار. اعداد، قیمت و تخفیفی که در ورودی نیست ننویس. محدودیت نویسه‌ها را دقیق رعایت کن.\n"
            .'خروجی فقط یک JSON با این کلیدها: {'.collect($sections)->map(fn ($k) => $schema[$k])->implode(',').'}';

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
        $this->log('post_writer', 'success', $response, null, $started, $fields);

        return ['ok' => true, 'fields' => $fields, 'model' => $response['model'] ?? $config['model']];
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
