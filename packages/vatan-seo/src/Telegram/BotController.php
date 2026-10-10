<?php

namespace Vatan\Seo\Telegram;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Vatan\Seo\Agents\Publisher;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\TelegramAdmin;
use Vatan\Seo\Services\Reporter;
use Vatan\Seo\Services\SiteManager;

/** وب‌هوک بات سئو — هیچ‌وقت خطا به تلگرام برنمی‌گرداند (تا تلگرام پیام را تکرار نکند) */
class BotController extends Controller
{
    public function __invoke(Request $request, SeoBot $bot, Reporter $reporter, SiteManager $sites, Publisher $publisher)
    {
        $secret = (string) config('seo-engine.telegram.webhook_secret');
        if ($secret !== '' && ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }
        try {
            $this->handle($request->all(), $bot, $reporter, $sites, $publisher);
        } catch (\Throwable $e) {
            report($e);
        }
        return response()->json(['ok' => true]);
    }

    protected function handle(array $u, SeoBot $bot, Reporter $reporter, SiteManager $sites, Publisher $publisher): void
    {
        if (isset($u['callback_query'])) {
            $cb = $u['callback_query'];
            $chat = (string) data_get($cb, 'message.chat.id');
            $admin = TelegramAdmin::where('chat_id', $chat)->where('is_active', true)->first();
            if (! $admin) {
                $bot->api('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'این چت به پنل متصل نیست.']);
                return;
            }
            [$ns, $action, $id] = array_pad(explode(':', (string) $cb['data']), 3, null);
            $item = $ns === 'seo' ? ContentItem::find((int) $id) : null;
            if (! $item) {
                $bot->api('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => 'مورد پیدا نشد.']);
                return;
            }
            $site = $item->site;
            $by = 'telegram:'.$chat;
            $text = match ($action) {
                'approve' => (function () use ($publisher, $site, $item, $by) {
                    $item->update(['status' => 'approved', 'approved_by' => $by, 'approved_at' => now()]);
                    $item = $publisher->publish($site, $item, $by);
                    return '✅ منتشر شد: '.urldecode((string) $item->published_url);
                })(),
                'reject' => (function () use ($item, $by) {
                    $item->update(['status' => 'rejected', 'approved_by' => $by, 'reviewer_note' => 'رد از تلگرام']);
                    return '⛔️ رد شد. از پنل می‌توانید بازنویسی بخواهید.';
                })(),
                default => 'دستور نامعتبر',
            };
            $bot->api('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => mb_substr($text, 0, 190)]);
            $bot->send($chat, e($text).' — «'.e((string) $item->title).'»');
            return;
        }

        $msg = $u['message'] ?? null;
        if (! $msg || ! isset($msg['chat']['id'])) {
            return;
        }
        $chat = (string) $msg['chat']['id'];
        $text = trim((string) ($msg['text'] ?? ''));
        $admin = TelegramAdmin::where('chat_id', $chat)->first();

        if (str_starts_with($text, '/start')) {
            $code = trim(substr($text, 6));
            if ($code !== '' && ($data = $bot->consumeLinkCode($code))) {
                TelegramAdmin::updateOrCreate(['chat_id' => $chat], [
                    'name' => trim(($msg['from']['first_name'] ?? '').' '.($msg['from']['last_name'] ?? '')),
                    'username' => $msg['from']['username'] ?? null,
                    'admin_id' => $data['admin_id'] ?? null,
                    'is_active' => true,
                ]);
                $bot->send($chat, "✅ <b>اتصال برقرار شد.</b>\nاز این به بعد هشدارها، گزارش‌ها و مقاله‌های منتظر تأیید اینجا می‌آید.\n\n/today برنامه‌ی امروز\n/keywords رتبه‌ی کلمات\n/help راهنما");
                return;
            }
            $bot->send($chat, $admin && $admin->is_active
                ? 'سلام دوباره 👋 دستورها: /today /keywords /report /review /budget'
                : "سلام 👋 این بات دستیار سئوی وطن است.\nبرای اتصال، از پنل مدیریت ← سئوی هوشمند ← تنظیمات ← تلگرام «کد اتصال» بگیرید و اینجا بفرستید:\n<code>/start کد</code>");
            return;
        }

        if (! $admin || ! $admin->is_active) {
            // اجازه‌ی ارسال کد بدون /start
            if (preg_match('/^[A-Z0-9]{6}$/i', $text) && ($data = $bot->consumeLinkCode($text))) {
                TelegramAdmin::updateOrCreate(['chat_id' => $chat], ['name' => $msg['from']['first_name'] ?? null, 'username' => $msg['from']['username'] ?? null, 'admin_id' => $data['admin_id'] ?? null, 'is_active' => true]);
                $bot->send($chat, '✅ اتصال برقرار شد. /help');
                return;
            }
            $bot->send($chat, 'این چت هنوز به پنل متصل نیست. کد اتصال را از تنظیمات سئوی هوشمند بگیرید.');
            return;
        }

        $site = $sites->current();
        $command = strtolower(strtok($text, ' @') ?: '');
        $arg = trim((string) preg_replace('/^\/\S+\s*/u', '', $text));
        // دستورهای سنگین بعد از پاسخ به تلگرام اجرا می‌شوند تا وب‌هوک منتظر نماند
        $later = function (string $ack, callable $job) use ($bot, $chat) {
            $bot->send($chat, $ack);
            app()->terminating(function () use ($job, $bot, $chat) {
                @set_time_limit(900);
                try {
                    $bot->send($chat, $job());
                } catch (\Throwable $e) {
                    report($e);
                    $bot->send($chat, '⚠️ انجام نشد: '.e(mb_substr($e->getMessage(), 0, 300)));
                }
            });
        };
        if (in_array($command, ['/ask', '/write', '/discover', '/audit', '/plan'], true)) {
            $this->command($command, $arg, $site, $later, $bot, $chat, $reporter);
            return;
        }
        match ($command) {
            '/today' => (function () use ($bot, $chat, $reporter, $site) { $site->markSeen(); $bot->send($chat, $reporter->daily($site)); })(),
            '/keywords' => $bot->send($chat, $reporter->keywords($site)),
            '/report' => $bot->send($chat, $reporter->weekly($site, false)),
            '/budget' => $bot->send($chat, $reporter->budget($site)),
            '/review' => $this->review($bot, $reporter, $site, $chat),
            default => $bot->send($chat, "دستورها:\n/today خلاصه و کارهای امروز\n/keywords رتبه‌ی کلمات هدف\n/report گزارش هفتگی\n/review مقاله‌های منتظر تأیید\n/budget هزینه‌ی هوش مصنوعی\n\n<b>دستور به ایجنت‌ها:</b>\n/ask سؤال — پرسش از مدیر سئو با داده‌ی واقعی سایت\n/write کلمه — نوشتن مقاله برای یک کلمه‌ی هدف\n/plan — برنامه‌ی هفته از استراتژیست\n/discover — کشف کلمات تازه از محصولات\n/audit — خزش و ممیزی فنی همین حالا"),
        };
    }

    protected function command(string $command, string $arg, $site, callable $later, SeoBot $bot, string $chat, Reporter $reporter): void
    {
        $recorder = app(\Vatan\Seo\Services\RunRecorder::class);
        $ctx = ['trigger' => 'telegram'];
        switch ($command) {
            case '/ask':
                if ($arg === '') {
                    $bot->send($chat, 'سؤالتان را بعد از دستور بنویسید. مثال: <code>/ask چرا کلیک این هفته کم شد؟</code>');
                    return;
                }
                $later('🤔 در حال بررسی داده‌ها…', function () use ($site, $arg, $recorder, $ctx) {
                    $answer = '';
                    $recorder->record($site, 'strategist', 'telegram_ask', function () use ($site, $arg, &$answer) {
                        $data = app(\Vatan\Seo\Agents\Strategist::class)->data($site);
                        $answer = app(\Vatan\Seo\Ai\Ai::class)->text($site, 'strategist', 'telegram-ask',
                            'تو مدیر سئوی این کسب‌وکار هستی: '.\Vatan\Seo\Support\Prompt::brand($site)."\nفقط بر اساس داده‌ی واقعی زیر، کوتاه (حداکثر ۱۲۰ کلمه)، فارسی و عملی جواب بده. اگر داده کافی نیست صادقانه بگو.\n\n".json_encode($data, JSON_UNESCAPED_UNICODE),
                            $arg, ['max_tokens' => 700, 'temperature' => 0.3]);
                        return ['success', 'پاسخ به سؤال تلگرام'];
                    }, $ctx);
                    return '🧠 '.e($answer ?: 'پاسخی دریافت نشد.');
                });
                return;
            case '/write':
                $kw = $arg !== '' ? $site->keywords()->where('normalized', \Vatan\Seo\Support\Fa::normalizeKeyword($arg))->first() : null;
                $kw ??= $arg === '' ? $site->keywords()->targets()->whereNotIn('id', ContentItem::where('site_id', $site->id)->whereNotNull('keyword_id')->pluck('keyword_id'))->orderByDesc('priority')->orderByDesc('ai_score')->first() : null;
                if (! $kw) {
                    $bot->send($chat, 'کلمه پیدا نشد. کلمه باید در فهرست کلمات باشد. مثال: <code>/write ساخت عکس محصول با هوش مصنوعی</code>');
                    return;
                }
                if (\Vatan\Seo\Support\Budget::articlesThisMonth($site) >= \Vatan\Seo\Support\Budget::limit($site, 'articles_per_month')) {
                    $bot->send($chat, 'سهمیه‌ی مقاله‌ی این ماه پر است.');
                    return;
                }
                $later('✍️ نوشتن مقاله برای «'.e($kw->keyword).'» شروع شد (۱ تا ۳ دقیقه)…', function () use ($site, $kw, $recorder, $ctx, $reporter, $bot, $chat) {
                    $item = null;
                    $recorder->record($site, 'writer', 'content_generate', function () use ($site, $kw, &$item) {
                        $w = app(\Vatan\Seo\Agents\ContentWriter::class);
                        $item = $w->draft($site, $w->brief($site, $kw));
                        return [$item->status === 'failed' ? 'failed' : 'success', 'مقاله از تلگرام: '.$kw->keyword];
                    }, $ctx);
                    if ($item && $item->status === 'review') {
                        [$text, $buttons] = $reporter->approvalCard($item);
                        $bot->send($chat, $text, $buttons);
                        return '✅ پیش‌نویس آماده شد.';
                    }
                    return '⚠️ پیش‌نویس ساخته نشد.';
                });
                return;
            case '/plan':
                $later('🧭 استراتژیست در حال چیدن برنامه‌ی هفته…', function () use ($site, $recorder, $ctx) {
                    $plan = [];
                    $recorder->record($site, 'strategist', 'strategist_review', function () use ($site, &$plan) {
                        $plan = app(\Vatan\Seo\Agents\Strategist::class)->weekly($site);
                        return ['success', 'برنامه‌ی هفته از تلگرام'];
                    }, $ctx);
                    return '<b>تمرکز:</b> '.e($plan['focus'] ?? '')."\n\n".collect($plan['actions'] ?? [])->take(5)->map(fn ($a, $i) => \Vatan\Seo\Support\Fa::n($i + 1).'. '.e($a['title']))->implode("\n");
                });
                return;
            case '/discover':
                $later('🔎 کشف کلمات از محصولات شروع شد…', function () use ($site, $recorder, $ctx) {
                    $r = [];
                    $recorder->record($site, 'researcher', 'discovery', function () use ($site, &$r) {
                        $r = app(\Vatan\Seo\Agents\KeywordDiscovery::class)->run($site);
                        return ['success', 'کشف کلمات از تلگرام'];
                    }, $ctx);
                    return '✅ '.\Vatan\Seo\Support\Fa::n($r['created'] ?? 0).' پیشنهاد تازه. انتخاب از پنل ← کلمات کلیدی ← پیشنهادها.';
                });
                return;
            case '/audit':
                $scenario = \Vatan\Seo\Models\Scenario::where('site_id', $site->id)->where('key', 'crawl_audit')->first();
                if (! $scenario) {
                    return;
                }
                $later('🕷 خزش سایت شروع شد (چند دقیقه)…', function () use ($scenario) {
                    $run = app(\Vatan\Seo\Services\ScenarioRunner::class)->run($scenario, 'telegram');
                    return '✅ '.e((string) ($run?->summary ?? 'در حال اجرا توسط فرایند دیگر'));
                });
                return;
        }
    }

    protected function review(SeoBot $bot, Reporter $reporter, $site, string $chat): void
    {
        $items = ContentItem::where('site_id', $site->id)->where('status', 'review')->latest()->limit(5)->get();
        if ($items->isEmpty()) {
            $bot->send($chat, 'مقاله‌ای منتظر تأیید نیست. ✨');
            return;
        }
        foreach ($items as $item) {
            [$text, $buttons] = $reporter->approvalCard($item);
            $bot->send($chat, $text, $buttons);
        }
    }
}
