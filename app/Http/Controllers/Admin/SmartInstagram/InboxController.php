<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Jobs\SmartInstagram\AnalyzeConversation;
use App\Models\Admin;
use App\Models\SmartInstagram\AiSuggestion;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\MessageAttachment;
use App\Models\SmartInstagram\OutboundMessage;
use App\Services\SmartInstagram\Ai\KnowledgeService;
use App\Services\SmartInstagram\MediaService;
use App\Services\SmartInstagram\MetricsService;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\OutboundService;
use App\Services\SmartInstagram\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** صندوق گفتگوی یکپارچه (پروپوزال ۵.۲ و ۹). */
class InboxController extends Controller
{
    public const FILTERS = [
        'open' => 'همه‌ی باز',
        'unanswered' => 'بی‌پاسخ',
        'attention' => 'نیازمند توجه',
        'ai' => 'پیشنهاد هوشمند',
        'waiting' => 'در انتظار مشتری',
        'assigned' => 'واگذارشده',
        'new' => 'جدید',
        'closed' => 'بسته',
    ];

    public function index(Request $request, ?Conversation $conversation = null): View
    {
        $this->authorizeAbility('view');
        $filter = array_key_exists((string) $request->query('filter'), self::FILTERS) ? (string) $request->query('filter') : 'open';
        $search = trim((string) $request->query('q', ''));
        $source = (string) $request->query('source', '');

        $base = $this->scoped();
        $counts = [];
        foreach (['unanswered', 'attention', 'ai'] as $key) {
            $counts[$key] = (clone $base)->inboxFilter($key)->count();
        }

        $list = (clone $base)->inboxFilter($filter)
            ->when($source !== '' && array_key_exists($source, config('smart_instagram.sources')), fn (Builder $q) => $q->where('last_source', $source))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $term = '%'.$search.'%';
                $q->where(function (Builder $w) use ($term): void {
                    $w->whereHas('contact', fn (Builder $c) => $c->where('username', 'like', $term)->orWhere('display_name', 'like', $term)->orWhere('phone', 'like', $term)
                        ->orWhereHas('tags', fn (Builder $t) => $t->where('name', 'like', $term)))
                        ->orWhereHas('messages', fn (Builder $m) => $m->where('body', 'like', $term));
                });
            })
            ->with(['contact:id,username,display_name,lead_status,lead_score', 'assignee:id,name'])
            ->orderByDesc('last_message_at')
            ->paginate(30)
            ->withQueryString();

        $selected = null;
        $timeline = collect();
        $pendingOutbound = collect();
        $suggestion = null;
        $learnable = null;
        if ($conversation) {
            $this->guardConversation($conversation);
            $selected = $conversation->load(['contact.tags', 'contact.deals' => fn ($q) => $q->latest()->limit(3), 'contact.tasks' => fn ($q) => $q->where('status', 'open')->orderBy('due_at')->limit(5), 'assignee:id,name', 'channel:id,name,username,outbound_enabled,gateway']);
            $timeline = $conversation->messages()->with(['attachments', 'admin:id,name'])
                ->orderByDesc('occurred_at')->orderByDesc('id')->limit(80)->get()->reverse()->values();
            $pendingOutbound = OutboundMessage::query()->where('conversation_id', $conversation->id)
                ->whereIn('status', ['pending', 'sending', 'retrying', 'blocked', 'failed'])
                ->where('created_at', '>=', now()->subDays(3))->latest('id')->limit(5)->get()->reverse()->values();
            $suggestion = $conversation->pendingSuggestion();
            $learnable = AiSuggestion::query()->where('conversation_id', $conversation->id)
                ->whereIn('status', ['accepted', 'edited'])->where('reviewed_at', '>=', now()->subDays(2))
                ->latest('reviewed_at')->first();
            if ($learnable && in_array('learned', (array) $learnable->flags, true)) {
                $learnable = null;
            }
            if ($conversation->unread_count > 0) {
                $conversation->forceFill(['unread_count' => 0])->saveQuietly();
            }
        }

        return view('admin.smart-instagram.inbox', [
            'filters' => self::FILTERS,
            'filter' => $filter,
            'search' => $search,
            'source' => $source,
            'counts' => $counts,
            'list' => $list,
            'selected' => $selected,
            'timeline' => $timeline,
            'pendingOutbound' => $pendingOutbound,
            'suggestion' => $suggestion,
            'learnable' => $learnable,
            'lastComment' => $selected ? $timeline->where('source_type', 'comment')->where('direction', 'in')->last() : null,
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canReply' => $this->context->can($this->admin(), 'reply'),
            'canManageKnowledge' => $this->context->can($this->admin(), 'manage_knowledge'),
            'outboundEnabled' => (bool) config('smart_instagram.outbound_enabled'),
            'quickReplies' => $selected ? \App\Models\SmartInstagram\KnowledgeSource::query()->where('workspace_id', $this->ws())
                ->where('category', 'approved_reply')->usableByAi()->orderByDesc('usage_count')->limit(6)->get(['id', 'title', 'content'])
                ->map(fn ($s) => ['title' => \Illuminate\Support\Str::after($s->title, ': '), 'body' => trim(\Illuminate\Support\Str::after($s->content, 'پاسخ تأییدشده:'))]) : collect(),
            'latestStamp' => (string) ((clone $base)->max('updated_at') ?? ''),
            'pipelineStages' => config('smart_instagram.pipeline_stages'),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        return $this->index($request, $conversation);
    }

    public function poll(Request $request): JsonResponse
    {
        $this->authorizeAbility('view');
        $latest = (clone $this->scoped())->max('updated_at');
        $conversationId = (int) $request->query('c', 0);
        $lastMessageId = $conversationId ? (int) Message::query()->where('workspace_id', $this->ws())->where('conversation_id', $conversationId)->max('id') : 0;

        return response()->json([
            'latest' => $latest ? (string) $latest : null,
            'last_message_id' => $lastMessageId,
            'unanswered' => (clone $this->scoped())->inboxFilter('unanswered')->count(),
        ]);
    }

    public function reply(Request $request, Conversation $conversation, OutboundService $outbound): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->guardConversation($conversation);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'kind' => ['nullable', 'in:dm,private_reply,public_reply'],
            'target_ref' => ['nullable', 'string', 'max:191'],
            'suggestion_id' => ['nullable', 'integer'],
        ]);

        $suggestionId = null;
        if (!empty($data['suggestion_id'])) {
            $suggestion = AiSuggestion::query()->where('conversation_id', $conversation->id)->whereKey($data['suggestion_id'])->first();
            if ($suggestion) {
                $same = PersianText::normalize($suggestion->body) === PersianText::normalize($data['body']);
                $suggestion->forceFill([
                    'status' => $same ? 'accepted' : 'edited',
                    'final_body' => $data['body'],
                    'reviewed_by' => $this->admin()?->id,
                    'reviewed_at' => now(),
                ])->save();
                $suggestionId = $suggestion->id;
            }
        }

        $message = $outbound->queue($conversation, $data['body'], 'human', $data['kind'] ?? 'dm', [
            'admin_id' => $this->admin()?->id,
            'target_ref' => $data['target_ref'] ?? null,
            'ai_suggestion_id' => $suggestionId,
        ]);
        app(MetricsService::class)->forget();

        return back()->with($message->status === 'blocked' ? 'warning' : 'success', $message->status === 'blocked'
            ? 'پیام ثبت شد اما ارسال نشد: '.$message->policy_reason
            : 'پیام در صف ارسال قرار گرفت.');
    }

    /** وقتی ارسال خودکار خاموش است، اپراتور پیام را دستی در اینستاگرام می‌فرستد و این‌جا ثبتش می‌کند. */
    public function markManualSent(OutboundMessage $outbound, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('reply');
        abort_unless((int) $outbound->workspace_id === $this->ws() && in_array($outbound->status, ['blocked', 'failed'], true), 404);
        $conversation = $outbound->conversation;
        $this->guardConversation($conversation);

        $message = Message::query()->create([
            'workspace_id' => $outbound->workspace_id,
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'source_type' => $outbound->kind === 'dm' ? 'dm' : 'comment',
            'message_type' => 'text',
            'body' => $outbound->body,
            'sent_by' => 'human',
            'admin_id' => $this->admin()?->id,
            'delivery_status' => 'manual',
            'meta' => ['outbound_id' => $outbound->id, 'manual' => true],
            'occurred_at' => now(),
        ]);
        $outbound->forceFill(['status' => 'manual', 'message_id' => $message->id, 'sent_at' => now()])->save();

        $updates = ['last_outbound_at' => now(), 'last_message_at' => now(), 'last_message_direction' => 'out', 'last_message_preview' => mb_substr($outbound->body, 0, 280), 'status' => 'waiting_customer', 'unread_count' => 0];
        if (!$conversation->first_response_at && $conversation->last_inbound_at) {
            $updates['first_response_at'] = now();
            $updates['first_response_seconds'] = max(0, $conversation->last_inbound_at->diffInSeconds(now()));
        }
        $conversation->forceFill($updates)->save();
        $logger->log('outbound.manual', 'پیام #'.$outbound->id.' به‌عنوان ارسال دستی ثبت شد.', $outbound);
        app(MetricsService::class)->forget();

        return back()->with('success', 'به‌عنوان ارسال دستی در تاریخچه ثبت شد.');
    }

    public function note(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->guardConversation($conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        Message::query()->create([
            'workspace_id' => $this->ws(),
            'conversation_id' => $conversation->id,
            'direction' => 'note',
            'source_type' => 'note',
            'message_type' => 'text',
            'body' => $data['body'],
            'sent_by' => 'human',
            'admin_id' => $this->admin()?->id,
            'is_internal_note' => true,
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'یادداشت داخلی ثبت شد.');
    }

    public function update(Request $request, Conversation $conversation, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->guardConversation($conversation);
        $data = $request->validate([
            'status' => ['nullable', 'in:new,unanswered,waiting_customer,assigned,closed'],
            'assigned_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'assign' => ['nullable', 'boolean'],
            'ai_paused' => ['nullable', 'boolean'],
            'needs_human' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'in:normal,high'],
        ]);

        $updates = [];
        if (array_key_exists('status', $data) && $data['status']) {
            $updates['status'] = $data['status'];
            $updates['closed_at'] = $data['status'] === 'closed' ? now() : null;
        }
        if ($request->boolean('assign')) {
            if (!$this->context->can($this->admin(), 'manage_sales') && (int) ($data['assigned_admin_id'] ?? 0) !== (int) $this->admin()?->id) {
                abort(403, 'فقط مدیر فروش می‌تواند گفتگو را به دیگران واگذار کند.');
            }
            $updates['assigned_admin_id'] = $data['assigned_admin_id'] ?? null;
            if (($data['assigned_admin_id'] ?? null) && $conversation->status !== 'closed') {
                $updates['status'] = 'assigned';
            }
            $conversation->contact?->forceFill(['assigned_admin_id' => $data['assigned_admin_id'] ?? null])->save();
        }
        foreach (['ai_paused', 'needs_human'] as $flag) {
            if ($request->has($flag)) {
                $updates[$flag] = $request->boolean($flag);
            }
        }
        if (!empty($data['priority'])) {
            $updates['priority'] = $data['priority'];
        }

        $conversation->forceFill($updates)->save();
        $logger->log('conversation.updated', 'گفتگوی #'.$conversation->id.' به‌روز شد.', $conversation, array_keys($updates));
        app(MetricsService::class)->forget();

        return back()->with('success', 'گفتگو به‌روز شد.');
    }

    public function analyze(Conversation $conversation): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->guardConversation($conversation);
        AnalyzeConversation::dispatch($conversation->id)->onQueue(config('smart_instagram.queues.ai', 'default'));

        return back()->with('success', 'تحلیل و پیشنهاد هوشمند در صف قرار گرفت؛ چند ثانیه‌ی دیگر صفحه را تازه کنید.');
    }

    public function reviewSuggestion(Request $request, AiSuggestion $suggestion, KnowledgeService $knowledge): RedirectResponse
    {
        $this->authorizeAbility('reply');
        abort_unless((int) $suggestion->workspace_id === $this->ws(), 404);
        $data = $request->validate([
            'decision' => ['required', 'in:rejected,learn'],
            'feedback' => ['nullable', 'string', 'max:300'],
        ]);

        if ($data['decision'] === 'rejected') {
            $suggestion->forceFill(['status' => 'rejected', 'feedback' => $data['feedback'] ?? null, 'reviewed_by' => $this->admin()?->id, 'reviewed_at' => now()])->save();

            return back()->with('success', 'پیشنهاد رد شد؛ بازخورد شما در «دانش هوش مصنوعی › یادگیری» دیده می‌شود.');
        }

        abort_unless(in_array($suggestion->status, ['accepted', 'edited'], true), 422, 'فقط پاسخ ارسال‌شده قابل افزودن به دانش است.');
        $approve = $this->context->can($this->admin(), 'manage_knowledge');
        $knowledge->learnFromSuggestion($suggestion, $approve, $this->admin()?->id);
        $suggestion->forceFill(['flags' => array_values(array_unique(array_merge((array) $suggestion->flags, ['learned'])))])->save();

        return back()->with('success', $approve ? 'این پاسخ به‌عنوان «پاسخ تأییدشده» به دانش اضافه شد.' : 'برای تأیید مدیر دانش ثبت شد.');
    }

    public function attachment(MessageAttachment $attachment, MediaService $media): StreamedResponse
    {
        $this->authorizeAbility('view');
        abort_unless((int) $attachment->workspace_id === $this->ws() && $attachment->storage_path, 404);
        $this->guardConversation($attachment->message->conversation);

        return Storage::disk($media->disk())->response($attachment->storage_path, null, [
            'Content-Type' => $attachment->mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function attachmentAction(Request $request, MessageAttachment $attachment, MediaService $media): RedirectResponse
    {
        $this->authorizeAbility('reply');
        abort_unless((int) $attachment->workspace_id === $this->ws(), 404);
        $this->guardConversation($attachment->message->conversation);
        $data = $request->validate([
            'action' => ['required', 'in:retry,delete,transcript'],
            'transcript' => ['nullable', 'string', 'max:5000'],
        ]);

        match ($data['action']) {
            'retry' => $media->retry($attachment),
            'delete' => $media->delete($attachment),
            'transcript' => $attachment->forceFill(['transcript' => $data['transcript'], 'transcript_status' => 'manual_done', 'transcript_language' => 'fa'])->save(),
        };

        // متن صوت در جست‌وجو و زمینه‌ی هوش مصنوعی هم دیده می‌شود.
        if ($data['action'] === 'transcript' && filled($data['transcript'])) {
            $msg = $attachment->message;
            if (blank($msg->body)) {
                $msg->forceFill(['body' => '[متن صوت] '.$data['transcript']])->save();
            }
        }

        return back()->with('success', ['retry' => 'دریافت دوباره در صف قرار گرفت.', 'delete' => 'فایل حذف شد.', 'transcript' => 'متن صوت ذخیره شد.'][$data['action']]);
    }

    private function scoped(): Builder
    {
        $query = Conversation::query()->where('workspace_id', $this->ws());
        if (!$this->context->can($this->admin(), 'view_all_conversations')) {
            $adminId = $this->admin()?->id;
            $query->where(fn (Builder $q) => $q->whereNull('assigned_admin_id')->orWhere('assigned_admin_id', $adminId));
        }

        return $query;
    }

    private function guardConversation(Conversation $conversation): void
    {
        abort_unless((int) $conversation->workspace_id === $this->ws(), 404);
        if (!$this->context->can($this->admin(), 'view_all_conversations')) {
            abort_unless($conversation->assigned_admin_id === null || (int) $conversation->assigned_admin_id === (int) $this->admin()?->id, 403);
        }
    }
}
