<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\SmartInstagram\AiRun;
use App\Models\Product;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Message;
use App\Services\OpenRouterService;
use App\Services\SmartInstagram\Ai\AiProfileService;
use App\Services\SmartInstagram\ContactNameResolver;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Str;

/**
 * پاسخ عمومی شخصی‌سازی‌شده به هر کامنت (نام مخاطب + لحن سبک‌های تعریف‌شده).
 * در صورت خطا یا خاموش‌بودن AI، null برمی‌گرداند و موتور یکی از سه سبک را به‌صورت چرخشی می‌فرستد.
 */
class CommentReplyWriter
{
    public function __construct(
        private readonly PostAiSettings $settings,
        private readonly AiProfileService $profiles,
        private readonly WorkspaceContext $context,
        private readonly ContactNameResolver $names,
    ) {
    }

    /** @param array<int,string> $styles */
    public function write(Message $comment, ?Contact $contact, array $styles, ?AutomationRule $rule = null): ?string
    {
        if (!config('smart_instagram.ai.enabled')) {
            return null;
        }
        $config = $this->settings->get();
        $profile = $this->profiles->active();
        $campaign = $this->campaignFor($rule);
        $post = $campaign?->post;
        $product = $campaign && !empty(data_get($campaign->settings, 'card.product_id'))
            ? Product::query()->whereKey((int) data_get($campaign->settings, 'card.product_id'))->first()
            : null;
        $hasDm = (bool) ($campaign?->dm_enabled ?? false);
        $system = $this->settings->sharedRules()."\n"
            ."## نقش\nتو پاسخ‌گوی اجتماعی برند هستی؛ قرار است یک انسان واقعی و آگاه به همین پست پاسخ بدهد.\n"
            ."## سبک گفتمان برند\n".Str::limit((string) $profile->persona_prompt, 1800)."\n"
            ."## عبارت‌های ممنوع\n".implode('، ', (array) $profile->forbidden_phrases ?: ['—'])."\n"
            ."## دستور همین بخش\n".$config['prompts']['personalize']."\n\n"
            ."نمونه‌های سبک (فقط برای درک لحن؛ کپی نکن):\n- ".implode("\n- ", array_slice($styles, 0, 3))
            ."\n\n## قاعده‌ی ثابت پاسخ به کامنت (غیرقابل‌تغییر)\n"
            ."- اگر کامنت فقط کلمه‌ی کلیدی یا درخواست کوتاه است (مثل «لینک»، «کلاژ»، «قیمت»)، مخاطب نظری نداده و فقط چیزی خواسته است؛ "
            ."پس پاسخ باید درخواست او را تأیید کند و (اگر دایرکت فعال است) بگوید پیام را در دایرکت فرستادیم. "
            ."در این حالت از واکنش به «نظر» یا «ایده» مثل «کاملاً موافقم»، «ایده‌ات جالبه»، «حق با توئه» یا تعریف از کامنت استفاده نکن.\n"
            ."- اگر کامنت سؤال یا احساس دارد، اول همان را کوتاه جواب بده یا به رسمیت بشناس، بعد در صورت فعال بودن دایرکت به آن اشاره کن.\n"
            ."- اگر نمونه‌های سبک با این قاعده‌ها تناقض دارند، فقط لحن آن‌ها را بگیر و محتوا را طبق این قاعده‌ها بنویس.\n"
            ."- موضوع پاسخ باید همان موضوع پست باشد و از اول‌شخص جمع برند استفاده شود؛ پاسخ را خطاب به خود برند یا درباره‌ی محصول دیگری ننویس.\n"
            ."\nقرارداد خروجی: فقط JSON معتبر با یک کلید به نام reply برگردان؛ اگر پاسخ طبیعی ممکن نیست، reply را خالی برگردان.";
        $user = collect([
            'نام کاربری' => $contact?->username ?: 'نامشخص',
            'نام نمایشی' => $contact?->display_name ?: 'نامشخص',
            'متن کامنت' => Str::limit((string) $comment->body, 500),
            'نوع کامنت' => $this->isKeywordRequest((string) $comment->body, (array) ($rule?->keywords ?? []))
                ? 'فقط کلمه‌ی کلیدی/درخواست کوتاه (نظر یا سؤالی ندارد)'
                : 'کامنت دارای متن یا سؤال',
            'کپشن و موضوع پست' => Str::limit((string) ($post?->caption ?? ''), 1200) ?: 'در دسترس نیست',
            'کلمه‌های فعال سناریو' => implode('، ', (array) ($rule?->keywords ?? [])) ?: 'در دسترس نیست',
            'محصول مرتبط' => $product ? $product->name_fa.' — '.Str::limit(strip_tags((string) $product->description_fa), 500) : 'ندارد',
            'جریان دایرکت فعال است' => $hasDm ? 'بله؛ می‌توانی دعوت کوتاه به دیدن دایرکت بدهی.' : 'خیر؛ درباره‌ی ارسال دایرکت وعده نده.',
        ])->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        $started = microtime(true);
        try {
            $response = app(OpenRouterService::class)->generateStructuredText($system, $user, $config['model'] ?: null, 15);
        } catch (\Throwable $e) {
            $this->log($comment, 'failed', null, $e->getMessage(), $started);

            return null;
        }
        $reply = Str::limit($this->names->render(trim((string) data_get($response, 'content.reply', '')), $contact), 300, '');
        $this->log($comment, $reply !== '' ? 'success' : 'failed', $response, $reply === '' ? 'پاسخ خالی' : null, $started, $reply);

        return $reply !== '' && !str_contains($reply, '{') ? $reply : null;
    }

    /** کامنتی که جز کلمه‌ی کلیدی (و ایموجی/علامت) چیزی ندارد یا بسیار کوتاه است. */
    private function isKeywordRequest(string $body, array $keywords): bool
    {
        $text = \App\Services\SmartInstagram\PersianText::normalize($body);
        foreach ($keywords as $keyword) {
            $keyword = \App\Services\SmartInstagram\PersianText::normalize((string) $keyword);
            if ($keyword !== '' && $keyword !== '*') {
                $text = trim(str_replace($keyword, ' ', $text));
            }
        }

        return mb_strlen(preg_replace('/\s+/u', '', $text) ?? $text) <= 3;
    }

    private function campaignFor(?AutomationRule $rule): ?PostCampaign
    {
        $id = (int) data_get($rule?->conditions, 'post_campaign_id', 0);
        if ($id <= 0) {
            return null;
        }

        return PostCampaign::query()->where('workspace_id', $this->context->id())->with('post')->find($id);
    }

    private function log(Message $comment, string $status, ?array $response, ?string $error, float $started, ?string $reply = null): void
    {
        $usage = (array) ($response['usage'] ?? []);
        AiRun::query()->create([
            'workspace_id' => $this->context->id(),
            'conversation_id' => $comment->conversation_id,
            'message_id' => $comment->id,
            'purpose' => 'comment_reply',
            'model' => $response['model'] ?? null,
            'status' => $status,
            'output' => $reply ? ['reply' => $reply] : null,
            'prompt_tokens' => $usage['prompt_tokens'] ?? null,
            'completion_tokens' => $usage['completion_tokens'] ?? null,
            'cost_usd' => isset($usage['cost']) && is_numeric($usage['cost']) ? (float) $usage['cost'] : null,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'error' => $error,
        ]);
    }
}
