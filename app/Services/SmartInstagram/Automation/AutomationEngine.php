<?php

namespace App\Services\SmartInstagram\Automation;

use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Deal;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\Tag;
use App\Models\SmartInstagram\Task;
use App\Models\Product;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\OutboundService;
use App\Services\SmartInstagram\PersianText;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * موتور اتومیشن (پروپوزال ۵.۷): شروع‌کننده → شرط → اقدام، با حفاظ ایمنی
 * (سقف دفعات، فاصله‌ی تکرار، توقف روی موارد حساس، حالت آزمایشی و ثبت نسخه).
 */
class AutomationEngine
{
    public const TRIGGERS = [
        'comment_keyword' => 'کلمه‌ی کلیدی در کامنت',
        'dm_keyword' => 'کلمه‌ی کلیدی در دایرکت',
        'first_message' => 'اولین پیام مشتری جدید',
        'story_reply' => 'پاسخ به استوری',
        'mention' => 'منشن',
        'ad_referral' => 'ورود از تبلیغ',
    ];

    public const ACTIONS = [
        'public_reply' => 'پاسخ عمومی زیر کامنت',
        'private_reply' => 'پاسخ خصوصی به کامنت',
        'product_card' => 'ارسال کارت محصول به کامنت‌کننده',
        'send_dm' => 'ارسال دایرکت',
        'ai_suggest' => 'ساخت پیشنهاد هوش مصنوعی',
        'add_tag' => 'افزودن برچسب',
        'assign' => 'تعیین مسئول',
        'set_lead_status' => 'تغییر وضعیت لید',
        'create_deal' => 'ساخت فرصت فروش',
        'create_task' => 'ساخت وظیفه',
        'request_human' => 'ارجاع به انسان',
        'stop' => 'توقف سایر قوانین',
    ];

    /** اقدام‌های داخلی که فقط «ثبت پست» می‌سازد (در فرم عمومی اتومیشن نمایش داده نمی‌شوند). */
    public const INTERNAL_ACTIONS = [
        'post_flow' => 'جریان دایرکت ثبت پست (فالو اجباری + کارت)',
    ];

    private const MESSAGING = ['public_reply', 'private_reply', 'product_card', 'send_dm', 'post_flow'];

    public function __construct(
        private readonly OutboundService $outbound,
        private readonly OperationLogger $logger,
    ) {
    }

    public function handle(Message $message): int
    {
        if (!$message->isInbound() || $message->is_internal_note) {
            return 0;
        }

        $message->loadMissing('conversation.contact', 'conversation.channel');
        $conversation = $message->conversation;

        // پاسخ/کلیک مشتری در جریان دایرکت «ثبت پست» (افزایشی؛ اگر جریانی فعال نباشد هیچ اثری ندارد).
        if ($message->source_type !== 'comment') {
            try {
                if (app(\App\Services\SmartInstagram\Posts\PostFlowService::class)->handleReply($message)) {
                    return 1;
                }
            } catch (\Throwable $e) {
                // خطای جریان ثبت پست نباید دریافت دایرکت و قوانین فعلی را متوقف کند.
                $this->logger->error('post_flow.reply_failed', 'پردازش پاسخ جریان ثبت پست ناموفق بود.', $message, ['error' => $e->getMessage()]);
            }
        }

        $triggers = $this->triggersFor($message, $conversation);
        if (!$triggers) {
            return 0;
        }

        $rules = AutomationRule::query()
            ->where('workspace_id', $message->workspace_id)
            ->whereIn('status', ['active', 'test'])
            ->whereIn('trigger', $triggers)
            ->where(fn ($q) => $q->whereNull('channel_id')->orWhere('channel_id', $conversation->channel_id))
            ->orderBy('priority')->orderBy('id')
            ->get();

        $executed = 0;
        foreach ($rules as $rule) {
            if (!$this->matches($rule, $message, $conversation)) {
                continue;
            }
            $stop = $this->execute($rule, $message, $conversation);
            $executed++;
            if ($stop) {
                break;
            }
        }

        return $executed;
    }

    /** بررسی بدون اجرا — برای «آزمون» در فرم قانون. */
    public function simulate(AutomationRule $rule, string $text, string $source = 'dm', bool $newContact = true, ?string $scopeRef = null): array
    {
        $trigger = $rule->trigger;
        $sourceOk = match ($trigger) {
            'comment_keyword' => $source === 'comment',
            'dm_keyword' => in_array($source, ['dm', 'ad', 'story_reply'], true),
            'first_message' => $newContact && in_array($source, ['dm', 'ad'], true),
            'story_reply' => $source === 'story_reply',
            'mention' => $source === 'mention',
            'ad_referral' => $source === 'ad',
            default => false,
        };
        $scopeOk = !$rule->scope_ref || $rule->scope_ref === $scopeRef;
        $keywordOk = $this->keywordMatch($rule, $text);

        return [
            'matched' => $sourceOk && $scopeOk && $keywordOk,
            'checks' => [
                'منبع/شروع‌کننده' => $sourceOk,
                'محدوده‌ی محتوا' => $scopeOk,
                'کلمه‌ی کلیدی' => $keywordOk,
            ],
            'actions' => collect((array) $rule->actions)->map(fn ($a) => [
                'type' => self::ACTIONS[$a['type'] ?? ''] ?? ($a['type'] ?? '?'),
                'text' => isset($a['text']) ? $this->render((string) $a['text'], null) : null,
            ])->all(),
        ];
    }

    /** @return array<int,string> */
    private function triggersFor(Message $message, Conversation $conversation): array
    {
        $isFirst = Message::query()->where('conversation_id', $conversation->id)->where('direction', 'in')->where('id', '<', $message->id)->doesntExist();

        return match ($message->source_type) {
            'comment' => ['comment_keyword'],
            'story_reply' => ['story_reply', 'dm_keyword'],
            'mention' => ['mention'],
            'ad' => array_values(array_filter(['ad_referral', 'dm_keyword', $isFirst ? 'first_message' : null])),
            default => array_values(array_filter(['dm_keyword', $isFirst ? 'first_message' : null])),
        };
    }

    private function matches(AutomationRule $rule, Message $message, Conversation $conversation): bool
    {
        if ($rule->scope_ref && $rule->scope_ref !== $message->source_ref) {
            return false;
        }
        if (in_array($rule->trigger, ['comment_keyword', 'dm_keyword'], true) && !$this->keywordMatch($rule, (string) $message->body)) {
            return false;
        }

        $conditions = (array) $rule->conditions;
        $hours = $conditions['business_hours'] ?? 'any';
        if ($hours !== 'any') {
            $inside = $this->insideBusinessHours($conversation);
            if (($hours === 'inside' && !$inside) || ($hours === 'outside' && $inside)) {
                return false;
            }
        }
        $contact = $conversation->contact;
        if (!empty($conditions['require_tag']) && !$contact->tags()->where('name', $conditions['require_tag'])->exists()) {
            return false;
        }
        if (!empty($conditions['exclude_tag']) && $contact->tags()->where('name', $conditions['exclude_tag'])->exists()) {
            return false;
        }

        return true;
    }

    private function keywordMatch(AutomationRule $rule, string $text): bool
    {
        $keywords = array_filter((array) $rule->keywords);
        if (!$keywords) {
            return !in_array($rule->trigger, ['comment_keyword', 'dm_keyword'], true);
        }
        // «ثبت پست» برای هر کلمه نحوه‌ی تطبیق جدا دارد (conditions.keyword_modes)؛ قوانین قبلی همان match_mode عمومی را دارند.
        $modes = (array) data_get($rule->conditions, 'keyword_modes', []);
        foreach ($keywords as $keyword) {
            $mode = $modes[PersianText::normalize((string) $keyword)] ?? ($rule->match_mode ?: 'contains');
            if ($mode === 'pattern' ? $this->patternMatch($text, (string) $keyword) : PersianText::containsKeyword($text, (string) $keyword, $mode)) {
                return true;
            }
        }

        return false;
    }

    /** الگوی ساده با * (هر چیزی)؛ بدون regex خام برای جلوگیری از الگوی پرهزینه. */
    private function patternMatch(string $text, string $pattern): bool
    {
        // اول روی * جدا می‌شود؛ normalize نشانه‌ها (از جمله _ و *) را حذف می‌کند.
        $parts = array_values(array_filter(array_map(fn ($p) => PersianText::normalize($p), explode('*', $pattern)), fn ($p) => $p !== ''));
        if ($parts === []) {
            return false;
        }
        $regex = '/'.implode('.*', array_map(fn ($p) => preg_quote($p, '/'), $parts)).'/u';

        return (bool) @preg_match($regex, PersianText::normalize($text));
    }

    /** @return bool آیا قوانین بعدی متوقف شوند */
    private function execute(AutomationRule $rule, Message $message, Conversation $conversation): bool
    {
        $mode = $rule->status === 'test' ? 'test' : 'live';
        $contact = $conversation->contact;
        $guards = (array) $rule->guards;

        try {
            $run = AutomationRun::query()->create([
                'workspace_id' => $rule->workspace_id,
                'rule_id' => $rule->id,
                'rule_version' => $rule->version,
                'message_id' => $message->id,
                'contact_id' => $contact->id,
                'mode' => $mode,
                'status' => 'running',
            ]);
        } catch (QueryException) {
            return false; // این قانون قبلاً برای همین پیام اجرا شده (idempotent)
        }

        $cooldown = (int) ($guards['cooldown_minutes'] ?? 0);
        if ($cooldown > 0 && AutomationRun::query()
            ->where('rule_id', $rule->id)->where('contact_id', $contact->id)->where('id', '!=', $run->id)
            ->whereIn('status', ['success', 'partial'])->where('created_at', '>=', now()->subMinutes($cooldown))->exists()) {
            return $this->finish($rule, $run, 'skipped', [['action' => 'guard', 'result' => 'فاصله‌ی تکرار برای این مشتری رعایت نشد']]);
        }
        $maxPerDay = (int) ($guards['max_per_contact_per_day'] ?? 0);
        if ($maxPerDay > 0 && AutomationRun::query()
            ->where('rule_id', $rule->id)->where('contact_id', $contact->id)->where('id', '!=', $run->id)
            ->whereIn('status', ['success', 'partial'])->where('created_at', '>=', now()->subDay())->count() >= $maxPerDay) {
            return $this->finish($rule, $run, 'skipped', [['action' => 'guard', 'result' => 'سقف اجرای روزانه برای این مشتری پر شده']]);
        }
        if ($contact->opted_out) {
            return $this->finish($rule, $run, 'skipped', [['action' => 'guard', 'result' => 'مخاطب در فهرست توقف است']]);
        }
        $dailyCap = (int) ($guards['max_runs_per_day'] ?? 0);
        if ($dailyCap > 0 && AutomationRun::query()->where('rule_id', $rule->id)->where('id', '!=', $run->id)
            ->whereIn('status', ['success', 'partial'])->where('created_at', '>=', now()->startOfDay())->count() >= $dailyCap) {
            return $this->finish($rule, $run, 'skipped', [['action' => 'guard', 'result' => 'سقف ارسال روزانه‌ی این سناریو پر شده']]);
        }

        $sensitive = $conversation->needs_human && ($guards['stop_on_sensitive'] ?? true);
        $decisions = [];
        $failures = 0;
        $stop = false;

        foreach ((array) $rule->actions as $index => $action) {
            $type = (string) ($action['type'] ?? '');
            if ($type === 'stop') {
                $stop = true;
                $decisions[] = ['action' => $type, 'result' => 'قوانین بعدی اجرا نمی‌شوند'];
                break;
            }
            if (in_array($type, self::MESSAGING, true) && $sensitive) {
                $decisions[] = ['action' => $type, 'result' => 'متوقف — گفتگو حساس است و به انسان سپرده شد'];
                continue;
            }
            if ($mode === 'test') {
                $decisions[] = ['action' => $type, 'result' => 'آزمایشی — اجرا نشد', 'text' => isset($action['text']) ? $this->render((string) $action['text'], $contact) : null];
                continue;
            }

            try {
                $result = $this->runAction($type, $action, $rule, $run, $message, $conversation, $index, $guards);
                $decisions[] = ['action' => $type] + $result;
                if (($result['ok'] ?? true) === false) {
                    $failures++;
                }
            } catch (\Throwable $e) {
                $failures++;
                $decisions[] = ['action' => $type, 'ok' => false, 'result' => 'خطا: '.$e->getMessage()];
            }
        }

        $status = $failures === 0 ? 'success' : ($failures < count($decisions) ? 'partial' : 'failed');
        $this->finish($rule, $run, $mode === 'test' ? 'simulated' : $status, $decisions);

        return $stop;
    }

    private function runAction(string $type, array $action, AutomationRule $rule, AutomationRun $run, Message $message, Conversation $conversation, int $index, array $guards): array
    {
        $contact = $conversation->contact;
        $key = "auto:{$rule->id}:{$rule->version}:{$message->id}:{$index}";

        switch ($type) {
            case 'public_reply':
            case 'private_reply':
                $commentId = (string) data_get($message->meta, 'comment_id', '');
                if ($commentId === '') {
                    return ['ok' => false, 'result' => 'این پیام کامنت نیست'];
                }
                $out = $this->outbound->queue($conversation, $this->replyText($type, $action, $message, $contact), 'automation', $type, [
                    'target_ref' => $commentId, 'automation_run_id' => $run->id, 'idempotency_key' => $key,
                    'allow_human_lock' => !($guards['stop_on_sensitive'] ?? true),
                    'delay_seconds' => (int) ($action['delay_seconds'] ?? 0),
                ]);

                return ['ok' => $out->status !== 'blocked', 'result' => $out->status === 'blocked' ? 'مسدود: '.$out->policy_reason : 'در صف ارسال', 'outbound_id' => $out->id];

            case 'product_card':
                $commentId = (string) data_get($message->meta, 'comment_id', '');
                if ($commentId === '') {
                    return ['ok' => false, 'result' => 'این پیام کامنت نیست'];
                }
                $card = $this->productCard($action, $contact);
                if (!$card['ok']) {
                    return ['ok' => false, 'result' => $card['error']];
                }
                $out = $this->outbound->queue($conversation, $card['title'], 'automation', 'private_reply', [
                    'target_ref' => $commentId,
                    'message_payload' => $card['payload'],
                    'automation_run_id' => $run->id,
                    'idempotency_key' => $key,
                    'allow_human_lock' => !($guards['stop_on_sensitive'] ?? true),
                ]);

                return ['ok' => $out->status !== 'blocked', 'result' => $out->status === 'blocked' ? 'مسدود: '.$out->policy_reason : 'کارت محصول در صف ارسال قرار گرفت', 'outbound_id' => $out->id];

            case 'post_flow':
                $campaign = \App\Models\SmartInstagram\PostCampaign::query()->with('post')->where('workspace_id', $rule->workspace_id)->find((int) ($action['campaign_id'] ?? 0));
                if (!$campaign || !$campaign->post) {
                    return ['ok' => false, 'result' => 'سناریوی ثبت پست پیدا نشد'];
                }

                return app(\App\Services\SmartInstagram\Posts\PostFlowService::class)
                    ->start($campaign, $message, $conversation, $run, $key, !($guards['stop_on_sensitive'] ?? true));

            case 'send_dm':
                $out = $this->outbound->queue($conversation, $this->render((string) ($action['text'] ?? ''), $contact), 'automation', 'dm', [
                    'automation_run_id' => $run->id, 'idempotency_key' => $key,
                    'allow_human_lock' => !($guards['stop_on_sensitive'] ?? true),
                ]);

                return ['ok' => $out->status !== 'blocked', 'result' => $out->status === 'blocked' ? 'مسدود: '.$out->policy_reason : 'در صف ارسال', 'outbound_id' => $out->id];

            case 'ai_suggest':
                \App\Jobs\SmartInstagram\AnalyzeConversation::dispatch($conversation->id, $message->id)->onQueue(config('smart_instagram.queues.ai', 'default'));

                return ['result' => 'تحلیل هوش مصنوعی در صف قرار گرفت'];

            case 'add_tag':
                $name = trim((string) ($action['tag'] ?? ''));
                if ($name === '') {
                    return ['ok' => false, 'result' => 'برچسب خالی است'];
                }
                $tag = Tag::query()->firstOrCreate(['workspace_id' => $contact->workspace_id, 'name' => $name]);
                $contact->tags()->syncWithoutDetaching([$tag->id]);

                return ['result' => 'برچسب «'.$name.'» افزوده شد'];

            case 'assign':
                $adminId = (int) ($action['admin_id'] ?? 0) ?: null;
                $conversation->forceFill(['assigned_admin_id' => $adminId, 'status' => $adminId ? 'assigned' : $conversation->status])->save();
                $contact->forceFill(['assigned_admin_id' => $adminId])->save();

                return ['result' => $adminId ? 'مسئول تعیین شد' : 'مسئول برداشته شد'];

            case 'set_lead_status':
                $status = (string) ($action['stage'] ?? 'qualified');
                if (!array_key_exists($status, config('smart_instagram.lead_statuses'))) {
                    return ['ok' => false, 'result' => 'وضعیت نامعتبر'];
                }
                $contact->forceFill(['lead_status' => $status])->save();

                return ['result' => 'وضعیت لید: '.config('smart_instagram.lead_statuses')[$status]];

            case 'create_deal':
                if (Deal::query()->where('contact_id', $contact->id)->where('outcome', 'open')->exists()) {
                    return ['result' => 'فرصت باز از قبل وجود دارد'];
                }
                Deal::query()->create([
                    'workspace_id' => $contact->workspace_id, 'contact_id' => $contact->id, 'conversation_id' => $conversation->id,
                    'title' => $this->render((string) ($action['text'] ?? 'فرصت از اتومیشن «'.$rule->name.'»'), $contact),
                    'stage' => 'new', 'source_type' => $message->source_type, 'source_ref' => $message->source_ref, 'stage_changed_at' => now(),
                ]);

                return ['result' => 'فرصت فروش ساخته شد'];

            case 'create_task':
                Task::query()->create([
                    'workspace_id' => $contact->workspace_id, 'contact_id' => $contact->id, 'conversation_id' => $conversation->id,
                    'type' => 'follow_up', 'title' => $this->render((string) ($action['text'] ?? 'پیگیری مشتری'), $contact),
                    'due_at' => now()->addHours(max(1, (int) ($action['due_hours'] ?? 24))),
                    'assigned_admin_id' => $conversation->assigned_admin_id, 'created_via' => 'automation',
                ]);

                return ['result' => 'وظیفه‌ی پیگیری ساخته شد'];

            case 'request_human':
                $conversation->forceFill(['needs_human' => true, 'priority' => 'high'])->save();

                return ['result' => 'گفتگو به انسان ارجاع شد'];
        }

        return ['ok' => false, 'result' => 'اقدام ناشناخته'];
    }

    /** @return array{ok:bool,title?:string,payload?:array,error?:string} */
    private function productCard(array $action, Contact $contact): array
    {
        $product = null;
        if (!empty($action['product_id'])) {
            $product = Product::query()->whereKey((int) $action['product_id'])->where('status', 'active')->first();
        }

        $title = trim($this->render((string) ($action['card_title'] ?? $product?->name_fa ?? ''), $contact));
        $message = trim($this->render((string) ($action['card_message'] ?? ''), $contact));
        $subtitle = trim($this->render((string) ($action['card_subtitle'] ?? $product?->description_fa ?? ''), $contact));
        if ($message !== '') {
            $subtitle = $message;
        }
        $imageUrl = trim((string) ($action['card_image_url'] ?? ($product?->displayImageUrl() ?? '')));
        $buttonUrl = trim((string) ($action['card_button_url'] ?? ($product ? route('app.product', $product->route_slug) : '')));
        $buttonText = trim($this->render((string) ($action['card_button_text'] ?? 'مشاهده صفحه'), $contact));

        if ($title === '' || $imageUrl === '' || $buttonUrl === '' || !filter_var($imageUrl, FILTER_VALIDATE_URL) || !filter_var($buttonUrl, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'کارت محصول باید عنوان، تصویر عمومی و لینک معتبر داشته باشد.'];
        }

        $element = array_filter([
            'title' => mb_substr($title, 0, 80),
            'image_url' => $imageUrl,
            'subtitle' => $subtitle !== '' ? mb_substr($subtitle, 0, 80) : null,
            'default_action' => ['type' => 'web_url', 'url' => $buttonUrl],
            'buttons' => [['type' => 'web_url', 'url' => $buttonUrl, 'title' => mb_substr($buttonText ?: 'مشاهده', 0, 20)]],
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'ok' => true,
            'title' => $title,
            'payload' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => ['template_type' => 'generic', 'elements' => [$element]],
                ],
                'fallback_text' => trim(implode("\n", array_filter([$message, $title, $buttonUrl]))),
            ],
        ];
    }

    /** متن پاسخ کامنت: چند سبک چرخشی + پاسخ شخصی‌سازی‌شده با هوش مصنوعی (در صورت فعال‌بودن). */
    private function replyText(string $type, array $action, Message $message, Contact $contact): string
    {
        $variants = array_values(array_filter((array) ($action['variants'] ?? [])));
        if ($type === 'public_reply' && !empty($action['ai_personalize'])) {
            $written = app(\App\Services\SmartInstagram\Posts\CommentReplyWriter::class)->write($message, $contact, $variants ?: [(string) ($action['text'] ?? '')]);
            if ($written) {
                return $written;
            }
        }
        $text = $variants ? $variants[array_rand($variants)] : (string) ($action['text'] ?? '');

        return $this->render($text, $contact);
    }

    private function finish(AutomationRule $rule, AutomationRun $run, string $status, array $decisions): bool
    {
        $run->forceFill(['status' => $status, 'decisions' => $decisions])->save();
        $rule->forceFill([
            'runs_count' => $rule->runs_count + 1,
            'success_count' => $rule->success_count + (in_array($status, ['success', 'simulated'], true) ? 1 : 0),
            'failure_count' => $rule->failure_count + ($status === 'failed' ? 1 : 0),
            'last_run_at' => now(),
            'last_error' => $status === 'failed' ? collect($decisions)->pluck('result')->implode(' · ') : $rule->last_error,
        ])->save();
        if ($status === 'failed') {
            $this->logger->error('automation.failed', 'اجرای قانون «'.$rule->name.'» ناموفق بود.', $rule, ['run_id' => $run->id]);
            $this->pauseAfterFailures($rule);
        }

        return false;
    }

    /** توقف خودکار پس از چند اجرای ناموفق پیاپی (حفاظ «ثبت پست»). */
    private function pauseAfterFailures(AutomationRule $rule): void
    {
        $limit = (int) data_get($rule->guards, 'pause_after_failures', 0);
        if ($limit <= 0 || $rule->status !== 'active') {
            return;
        }
        $recent = AutomationRun::query()->where('rule_id', $rule->id)->whereNotIn('status', ['skipped', 'running'])
            ->latest('id')->limit($limit)->pluck('status');
        if ($recent->count() >= $limit && $recent->every(fn ($s) => $s === 'failed')) {
            $rule->forceFill(['status' => 'paused'])->save();
            \App\Models\SmartInstagram\PostCampaign::query()->where('automation_rule_id', $rule->id)->update(['status' => 'paused']);
            $this->logger->error('automation.auto_paused', 'قانون «'.$rule->name.'» پس از '.$limit.' خطای پیاپی خودکار متوقف شد.', $rule);
        }
    }

    private function insideBusinessHours(Conversation $conversation): bool
    {
        $settings = (array) ($conversation->channel?->workspace?->settings ?? []);
        $start = (string) data_get($settings, 'business_hours.start', '09:00');
        $end = (string) data_get($settings, 'business_hours.end', '21:00');
        $now = Carbon::now('Asia/Tehran')->format('H:i');

        return $now >= $start && $now <= $end;
    }

    public function render(string $text, ?Contact $contact): string
    {
        return trim(strtr($text, [
            '{name}' => $contact?->display_name ?: ($contact?->username ?: 'دوست عزیز'),
            '{username}' => $contact?->username ? '@'.$contact->username : '',
        ]));
    }
}
