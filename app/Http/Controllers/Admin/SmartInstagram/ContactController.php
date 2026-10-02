<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\Admin;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\ContactNote;
use App\Models\SmartInstagram\MessageAttachment;
use App\Models\SmartInstagram\Tag;
use App\Services\SmartInstagram\MediaService;
use App\Services\SmartInstagram\MetricsService;
use App\Services\SmartInstagram\OperationLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** مشتریان و لیدها + پرونده‌ی کامل مشتری (پروپوزال ۵.۳ و ۹). */
class ContactController extends Controller
{
    private const SORTS = ['recent' => 'آخرین تعامل', 'score' => 'امتیاز لید', 'newest' => 'جدیدترین'];

    public function index(Request $request): View
    {
        $this->authorizeAbility('view');
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'source' => (string) $request->query('source', ''),
            'tag' => (string) $request->query('tag', ''),
            'assignee' => (string) $request->query('assignee', ''),
            'sort' => array_key_exists((string) $request->query('sort'), self::SORTS) ? (string) $request->query('sort') : 'recent',
        ];

        $base = Contact::query()->where('workspace_id', $this->ws());
        $statusCounts = (clone $base)->select('lead_status', DB::raw('COUNT(*) as total'))->groupBy('lead_status')->pluck('total', 'lead_status');

        $contacts = (clone $base)
            ->when($filters['q'] !== '', function (Builder $q) use ($filters): void {
                $term = '%'.$filters['q'].'%';
                $q->where(fn (Builder $w) => $w->where('username', 'like', $term)->orWhere('display_name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('industry', 'like', $term));
            })
            ->when($filters['status'] !== '', fn (Builder $q) => $q->where('lead_status', $filters['status']))
            ->when($filters['source'] !== '', fn (Builder $q) => $q->where('first_source', $filters['source']))
            ->when($filters['tag'] !== '', fn (Builder $q) => $q->whereHas('tags', fn (Builder $t) => $t->where('name', $filters['tag'])))
            ->when($filters['assignee'] === 'none', fn (Builder $q) => $q->whereNull('assigned_admin_id'))
            ->when(ctype_digit($filters['assignee']), fn (Builder $q) => $q->where('assigned_admin_id', (int) $filters['assignee']))
            ->with(['tags:id,name', 'assignee:id,name'])
            ->withCount(['deals as open_deals_count' => fn (Builder $q) => $q->where('outcome', 'open')])
            ->when($filters['sort'] === 'score', fn (Builder $q) => $q->orderByDesc('lead_score'))
            ->when($filters['sort'] === 'newest', fn (Builder $q) => $q->latest('id'))
            ->when($filters['sort'] === 'recent', fn (Builder $q) => $q->orderByDesc('last_interaction_at'))
            ->paginate(25)->withQueryString();

        return view('admin.smart-instagram.contacts.index', [
            'contacts' => $contacts,
            'filters' => $filters,
            'sorts' => self::SORTS,
            'statusCounts' => $statusCounts,
            'total' => (clone $base)->count(),
            'tags' => Tag::query()->where('workspace_id', $this->ws())->orderBy('name')->pluck('name'),
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'view' => $request->query('view') === 'cards' ? 'cards' : 'table',
        ]);
    }

    public function show(Contact $contact): View
    {
        $this->authorizeAbility('view');
        $this->own($contact);
        $contact->load(['tags', 'assignee:id,name', 'notes.admin:id,name', 'deals' => fn ($q) => $q->latest(), 'tasks' => fn ($q) => $q->latest()->limit(20), 'tasks.assignee:id,name']);
        $conversations = $contact->conversations()->with('channel:id,name')->get();
        $timeline = \App\Models\SmartInstagram\Message::query()
            ->whereIn('conversation_id', $conversations->pluck('id'))
            ->with('attachments')
            ->latest('occurred_at')->limit(60)->get();

        return view('admin.smart-instagram.contacts.show', [
            'contact' => $contact,
            'conversations' => $conversations,
            'timeline' => $timeline,
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'allTags' => Tag::query()->where('workspace_id', $this->ws())->orderBy('name')->pluck('name'),
            'canEdit' => $this->context->can($this->admin(), 'reply'),
            'canErase' => $this->context->can($this->admin(), 'manage_settings'),
        ]);
    }

    public function update(Request $request, Contact $contact, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->own($contact);
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'industry' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:80'],
            'lead_status' => ['nullable', 'in:'.implode(',', array_keys(config('smart_instagram.lead_statuses')))],
            'lead_score' => ['nullable', 'integer', 'between:0,100'],
            'assigned_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'consent_contact' => ['nullable', 'boolean'],
            'opted_out' => ['nullable', 'boolean'],
        ]);
        $data['consent_contact'] = $request->boolean('consent_contact');
        $data['opted_out'] = $request->boolean('opted_out');
        $contact->fill(array_filter($data, fn ($v) => $v !== null) + ['assigned_admin_id' => $data['assigned_admin_id'] ?? null])->save();
        $logger->log('contact.updated', 'پرونده‌ی '.$contact->label().' به‌روز شد.', $contact);
        app(MetricsService::class)->forget();

        return back()->with('success', 'پرونده‌ی مشتری ذخیره شد.');
    }

    public function note(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->own($contact);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        ContactNote::query()->create(['workspace_id' => $this->ws(), 'contact_id' => $contact->id, 'admin_id' => $this->admin()?->id, 'body' => $data['body']]);

        return back()->with('success', 'یادداشت ثبت شد.');
    }

    public function tags(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $this->own($contact);
        $data = $request->validate(['tag' => ['required', 'string', 'max:60'], 'remove' => ['nullable', 'boolean']]);
        $tag = Tag::query()->firstOrCreate(['workspace_id' => $this->ws(), 'name' => trim($data['tag'])]);
        $request->boolean('remove') ? $contact->tags()->detach($tag->id) : $contact->tags()->syncWithoutDetaching([$tag->id]);

        return back()->with('success', $request->boolean('remove') ? 'برچسب برداشته شد.' : 'برچسب افزوده شد.');
    }

    /** حذف کامل داده‌ی مخاطب (پروپوزال ۱۱ — حریم خصوصی): پیام، فایل، متن صوت، یادداشت و فرصت‌ها. */
    public function destroy(Contact $contact, MediaService $media, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_settings');
        $this->own($contact);
        $label = $contact->label();

        MessageAttachment::query()
            ->whereIn('message_id', \App\Models\SmartInstagram\Message::query()->whereIn('conversation_id', $contact->conversations()->pluck('id'))->pluck('id'))
            ->each(fn (MessageAttachment $a) => $media->delete($a));
        $contact->delete(); // گفتگو، پیام، یادداشت، برچسب، فرصت و وظیفه با cascade حذف می‌شوند

        $logger->log('contact.erased', 'تمام داده‌ی مخاطب «'.$label.'» حذف شد.', null, [], 'warning');
        app(MetricsService::class)->forget();

        return redirect()->route('admin.smart-instagram.contacts.index')->with('success', 'داده‌ی مخاطب به‌طور کامل حذف شد.');
    }

    private function own(Contact $contact): void
    {
        abort_unless((int) $contact->workspace_id === $this->ws(), 404);
    }
}
