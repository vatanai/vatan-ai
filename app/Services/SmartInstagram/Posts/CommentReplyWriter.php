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
            ."- اگر نام مطمئن مخاطب در ورودی آمده، می‌توانی فقط یک بار و طبیعی با «{name} جان» خطابش کنی؛ نام کاربری به‌تنهایی نام واقعی نیست.\n"
            ."- موضوع پاسخ باید همان موضوع پست باشد و از اول‌شخص جمع برند استفاده شود؛ پاسخ را خطاب به خود برند یا درباره‌ی محصول دیگری ننویس.\n"
            ."- مثل ادمین واقعی پیج بنویس، نه مثل هوش مصنوعی: یک جمله‌ی کوتاه محاوره‌ای (حداکثر ۱۵ کلمه)، حداکثر یک علامت تعجب و یک ایموجی؛ "
            ."بدون «حتماً»، «قطعاً»، «فوق‌العاده»، «اثر هنری»، بدون تکرار نام محصول در هر جمله و بدون دو جمله‌ی پشت‌سرهم با علامت تعجب.\n"
            ."\nقرارداد خروجی: فقط JSON معتبر با یک کلید به نام reply برگردان؛ اگر پاسخ طبیعی ممکن نیست، reply را خالی برگردان.";
        $user = collect([
            'نام کاربری' => $contact?->username ?: 'نامشخص',
            'نام نمایشی' => $contact?->display_name ?: 'نامشخص',
            'متن کامنت' => Str::limit((string) $comment->body, 500),
            'نوع کامنت' => self::isKeywordRequest((string) $comment->body, (array) ($rule?->keywords ?? []))
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
        $reply = self::humanize($this->names->render(trim((string) data_get($response, 'content.reply', '')), $contact));
        $this->log($comment, $reply !== '' ? 'success' : 'failed', $response, $reply === '' ? 'پاسخ خالی' : null, $started, $reply);

        return $reply !== '' && !str_contains($reply, '{') ? $reply : null;
    }

    /** پاسخ قطعی و کوتاه برای درخواست‌های کلیدی وقتی کارت دایرکت واقعاً فعال است. */
    public function keywordReply(Message $comment, ?Contact $contact, ?AutomationRule $rule = null): ?string
    {
        if (!self::isKeywordRequest((string) $comment->body, (array) ($rule?->keywords ?? []))) {
            return null;
        }

        $campaign = $this->campaignFor($rule);
        if (!$campaign?->dm_enabled) {
            return null;
        }

        $name = $this->names->resolve($contact);
        $reply = $name
            ? "{$name} جان، لینک ساخت رو توی دایرکت برات فرستادیم؛ چک کن."
            : 'لینک ساخت رو توی دایرکت برات فرستادیم؛ چک کن.';

        return self::humanize($reply);
    }

    /**
     * کامنتی که جز کلمه‌ی کلیدی (و ایموجی، علامت یا تعارف‌های کوتاهی مثل «لطفاً»، «سلام») چیزی ندارد.
     * برای این کامنت‌ها پاسخ از سبک‌های کوتاهِ نوشته‌ی مدیر انتخاب می‌شود، نه هوش مصنوعی.
     */
    public static function isKeywordRequest(string $body, array $keywords): bool
    {
        $text = ' '.\App\Services\SmartInstagram\PersianText::normalize($body).' ';
        foreach (['سلام', 'لطفا', 'لطفاً', 'مرسی', 'ممنون', 'ممنونم', 'پلیز', 'please', 'pls', 'میخوام', 'می خوام', 'میخواستم', 'بفرست', 'بفرستید', 'بفرستین', 'بساز', 'بسازم', 'بسازش', 'ساخت', 'ساختش', 'ساختن', 'محصول', 'رو', 'را', 'هم', 'منم', 'من', 'برام', 'برای من', 'میشه', 'می شه'] as $filler) {
            $text = str_replace(' '.$filler.' ', ' ', $text);
        }
        $text = trim($text);
        foreach ($keywords as $keyword) {
            $keyword = \App\Services\SmartInstagram\PersianText::normalize((string) $keyword);
            if ($keyword !== '' && $keyword !== '*') {
                $text = trim(str_replace($keyword, ' ', $text));
            }
        }

        return mb_strlen(preg_replace('/\s+/u', '', $text) ?? $text) <= 3;
    }

    /**
     * پاک‌سازی ظاهر «ماشینی» خروجی: حداکثر یک علامت تعجب، حداکثر یک ایموجی، حذف قیدهای اغراق‌آمیز
     * و کوتاه‌سازی در مرز جمله (حداکثر ۱۴۰ نویسه).
     */
    public static function humanize(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $text = preg_replace('/\s*(حتماً|حتما|قطعاً|قطعا)\s+/u', ' ', $text) ?? $text;
        $text = preg_replace('/!{2,}/u', '!', $text) ?? $text;
        // فقط اولین علامت تعجب می‌ماند؛ بقیه به نقطه تبدیل می‌شوند (آخر متن حذف).
        $seen = false;
        $text = preg_replace_callback('/!/u', function () use (&$seen) {
            if (!$seen) {
                $seen = true;

                return '!';
            }

            return '.';
        }, $text) ?? $text;
        // فقط اولین ایموجی نگه داشته می‌شود.
        $emoji = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B50}\x{2764}](?:\x{FE0F})?(?:\x{200D}[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2764}](?:\x{FE0F})?)*/u';
        $count = 0;
        $text = preg_replace_callback($emoji, function ($m) use (&$count) {
            return ++$count === 1 ? $m[0] : '';
        }, $text) ?? $text;
        $text = trim(preg_replace('/\s{2,}/u', ' ', $text) ?? $text);
        $text = preg_replace('/\.(\s*[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2764}\x{FE0F}]*)$/u', '$1', $text) ?? $text;
        if (mb_strlen($text) > 140) {
            $cut = mb_substr($text, 0, 140);
            $pos = max((int) mb_strrpos($cut, '.'), (int) mb_strrpos($cut, '!'), (int) mb_strrpos($cut, '؟'), (int) mb_strrpos($cut, '،'));
            $text = trim($pos > 40 ? mb_substr($cut, 0, $pos + 1) : $cut);
            $text = rtrim($text, '،, ');
        }

        return trim($text);
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
