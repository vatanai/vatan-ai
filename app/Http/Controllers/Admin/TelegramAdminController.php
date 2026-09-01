<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramBotContent;
use App\Models\TelegramEvent;
use App\Models\TelegramProductClick;
use App\Models\TelegramSegment;
use App\Models\TelegramUser;
use App\Models\TokenLog;
use App\Services\TokenGrantService;
use App\Services\TelegramCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TelegramAdminController extends Controller
{
    public function __construct(private readonly TelegramCampaignService $campaignService)
    {
    }

    public function index(Request $request): View
    {
        $from = now()->subDays(29)->startOfDay();
        $events = TelegramEvent::query()->where('occurred_at', '>=', $from)->get(['event_type', 'occurred_at']);
        $daily = collect(range(0, 29))->map(function (int $offset) use ($from, $events): array {
            $date = $from->copy()->addDays($offset)->toDateString();
            return [
                'date' => $date,
                'starts' => $events->where('event_type', 'start')->filter(fn ($event) => $event->occurred_at?->toDateString() === $date)->count(),
                'events' => $events->filter(fn ($event) => $event->occurred_at?->toDateString() === $date)->count(),
            ];
        })->values();

        $stats = [
            'total' => TelegramUser::query()->count(),
            'linked' => TelegramUser::query()->whereNotNull('user_id')->count(),
            'new_today' => TelegramUser::query()->whereDate('created_at', today())->count(),
            'active_week' => TelegramUser::query()->where('last_active_at', '>=', now()->subDays(7))->count(),
            'blocked' => TelegramUser::query()->where('is_blocked', true)->count(),
            'clicks' => TelegramProductClick::query()->count(),
        ];
        $sources = TelegramProductClick::query()->select(['source'])->get()->groupBy(fn ($click) => $click->source ?: 'نامشخص')->map->count()->sortDesc()->take(8);
        $topProducts = TelegramProductClick::query()->with('product:id,name_fa,name_en')->get()->groupBy('product_id')->map(function ($clicks) {
            return ['name' => $clicks->first()->product?->name_fa ?: $clicks->first()->product?->name_en ?: 'محصول حذف‌شده', 'count' => $clicks->count()];
        })->sortByDesc('count')->take(8);

        $users = $this->userQuery($request)->paginate(15)->withQueryString();
        return view('admin.telegram.index', compact('stats', 'daily', 'sources', 'topProducts', 'users'));
    }

    public function users(Request $request): View
    {
        $users = $this->userQuery($request)->paginate(25)->withQueryString();
        $segments = TelegramSegment::query()->where('is_active', true)->latest()->get();
        $segmentCounts = $segments->mapWithKeys(fn (TelegramSegment $segment): array => [
            $segment->id => $this->campaignService->recipients((array) $segment->definition)->count(),
        ]);
        $segmentDefinition = $this->segmentDefinition($request);

        return view('admin.telegram.users', compact('users', 'segments', 'segmentCounts', 'segmentDefinition'));
    }

    public function storeSegment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'definition' => ['required', 'json', 'max:10000'],
        ]);
        $definition = json_decode($data['definition'], true);
        if (! is_array($definition)) {
            throw ValidationException::withMessages(['definition' => 'تعریف سگمنت معتبر نیست.']);
        }

        $definition = $this->validateSegmentDefinition($definition);
        TelegramSegment::query()->create([
            'created_by' => $request->user('admin')->id,
            'name' => $data['name'],
            'definition' => $definition,
            'user_count' => $this->campaignService->recipients($definition)->count(),
            'is_active' => true,
        ]);

        return back()->with('success', 'سگمنت ذخیره شد و تعداد مخاطبان آن محاسبه شد.');
    }

    public function destroySegment(TelegramSegment $telegramSegment): RedirectResponse
    {
        $telegramSegment->delete();

        return back()->with('success', 'سگمنت حذف شد؛ هیچ کاربری حذف نشده است.');
    }

    public function show(TelegramUser $telegramUser): View
    {
        $telegramUser->load('user');
        $clicks = $telegramUser->productClicks()->with('product:id,name_fa,name_en')->latest('clicked_at')->paginate(20);
        $events = $telegramUser->events()->latest('occurred_at')->paginate(20, ['*'], 'events_page');
        return view('admin.telegram.show', compact('telegramUser', 'clicks', 'events'));
    }

    public function update(Request $request, TelegramUser $telegramUser): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'is_blocked' => ['required', 'boolean'],
        ]);
        $data['blocked_at'] = $data['is_blocked'] ? ($telegramUser->blocked_at ?: now()) : null;
        $telegramUser->update($data);
        return back()->with('success', 'اطلاعات کاربر تلگرام ذخیره شد.');
    }

    public function archive(TelegramUser $telegramUser): RedirectResponse
    {
        $telegramUser->update(['is_blocked' => true, 'blocked_at' => now()]);
        return back()->with('success', 'کاربر از ارسال‌های بعدی خارج شد و سوابق او حفظ شد.');
    }

    public function adjustCredit(Request $request, TelegramUser $telegramUser): RedirectResponse
    {
        abort_unless($request->user('admin')?->isLeader(), 403);
        $data = $request->validate([
            'action' => ['required', Rule::in(['add', 'deduct', 'set'])],
            'amount' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        abort_unless($telegramUser->user, 422);

        DB::transaction(function () use ($request, $telegramUser, $data): void {
            $user = $telegramUser->user()->lockForUpdate()->firstOrFail();
            $before = (int) $user->tokens;
            $amount = (int) $data['amount'];
            $after = match ($data['action']) {
                'add' => $before + $amount,
                'deduct' => max(0, $before - $amount),
                'set' => $amount,
            };
            $promotionalBefore = (int) $user->promotionalTokenBalance();
            $delta = $after - $before;
            $promotionalDelta = $delta > 0
                ? $delta
                : -min($promotionalBefore, abs($delta));
            if ($promotionalDelta < 0 && Schema::hasTable('user_token_grants')) {
                app(TokenGrantService::class)->consumeLocked($user, abs($promotionalDelta));
            }
            $promotionalAfter = max(0, $promotionalBefore + $promotionalDelta);
            $user->forceFill(['tokens' => $after, 'promotional_tokens' => min($after, $promotionalAfter)])->save();

            if ($delta > 0 && Schema::hasTable('user_token_grants')) {
                app(TokenGrantService::class)->create($user, $delta, null, $request->user('admin')->id, null, 'telegram_admin_adjustment');
            }
            $eventKey = 'telegram-admin-credit:' . Str::uuid();
            if (Schema::hasTable('token_logs')) {
                TokenLog::query()->create([
                    'user_id' => $user->id,
                    'admin_id' => $request->user('admin')->id,
                    'action' => $delta >= 0 ? 'add' : 'deduct',
                    'source' => 'telegram_admin_adjustment',
                    'event_key' => $eventKey,
                    'amount' => abs($delta),
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'note' => $data['note'] ?: 'تغییر اعتبار از مدیریت کاربران تلگرام',
                    'metadata' => ['telegram_user_id' => $telegramUser->id, 'requested_action' => $data['action']],
                ]);
            }
        });

        return back()->with('success', 'اعتبار کاربر به‌روزرسانی شد.');
    }

    public function content(): View
    {
        $contents = TelegramBotContent::query()->orderBy('content_key')->get();
        return view('admin.telegram.content', compact('contents'));
    }

    public function campaigns(): View
    {
        $campaigns = \App\Models\TelegramCampaign::query()->with('admin:id,name')->latest()->paginate(20);
        return view('admin.telegram.campaigns', compact('campaigns'));
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:10000'],
            'segment_definition' => ['nullable', 'json'],
            'buttons' => ['nullable', 'json'],
            'media_type' => ['nullable', Rule::in(['photo', 'video', 'animation', 'document'])],
            'media_file_id' => ['nullable', 'string', 'max:2048'],
        ]);
        $data['created_by'] = $request->user('admin')->id;
        $data['segment_definition'] = $data['segment_definition'] ? json_decode($data['segment_definition'], true) : [];
        $data['buttons'] = $data['buttons'] ? json_decode($data['buttons'], true) : null;
        $data['status'] = 'draft';
        \App\Models\TelegramCampaign::query()->create($data);
        return back()->with('success', 'کمپین به‌صورت پیش‌نویس ذخیره شد؛ هنوز هیچ پیامی ارسال نشده است.');
    }

    public function prepareCampaign(\App\Models\TelegramCampaign $telegramCampaign, TelegramCampaignService $service): RedirectResponse
    {
        abort_unless($telegramCampaign->status === 'draft', 422);
        $count = $service->prepare($telegramCampaign);
        return back()->with('success', "فهرست گیرندگان آماده شد: {$count} کاربر؛ ارسال همچنان غیرفعال است.");
    }

    public function storeContent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:telegram_bot_contents,content_key'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'media_type' => ['nullable', Rule::in(['photo', 'video', 'animation', 'document'])],
            'media_file_id' => ['nullable', 'string', 'max:2048'],
            'buttons' => ['nullable', 'json'],
        ]);
        $data['buttons'] = $data['buttons'] ? json_decode($data['buttons'], true) : null;
        $data['is_active'] = true;
        TelegramBotContent::query()->create($data);
        return back()->with('success', 'محتوای بات اضافه شد.');
    }

    public function updateContent(Request $request, TelegramBotContent $telegramBotContent): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'media_type' => ['nullable', Rule::in(['photo', 'video', 'animation', 'document'])],
            'media_file_id' => ['nullable', 'string', 'max:2048'],
            'buttons' => ['nullable', 'json'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['buttons'] = $data['buttons'] ? json_decode($data['buttons'], true) : null;
        $telegramBotContent->update($data);
        return back()->with('success', 'محتوای بات به‌روزرسانی شد.');
    }

    public function destroyContent(TelegramBotContent $telegramBotContent): RedirectResponse
    {
        $telegramBotContent->delete();

        return back()->with('success', 'محتوای بات حذف شد و در صورت نیاز متن پیش‌فرض استفاده می‌شود.');
    }

    private function userQuery(Request $request)
    {
        return TelegramUser::query()->with('user:id,name,last_name,phone,tokens')
            ->withCount(['productClicks', 'productClicks as completed_builds_count' => fn ($clicks) => $clicks->whereNotNull('completed_at')])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%' . trim((string) $request->input('q')) . '%';
                $query->where(fn ($q) => $q->where('telegram_id', (int) $request->input('q'))->orWhere('username', 'like', $term)->orWhere('first_name', 'like', $term)->orWhere('last_name', 'like', $term));
            })
            ->when($request->input('status') === 'blocked', fn ($query) => $query->where('is_blocked', true))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_blocked', false))
            ->when($request->input('linked') === 'yes', fn ($query) => $query->whereNotNull('user_id'))
            ->when($request->input('linked') === 'no', fn ($query) => $query->whereNull('user_id'))
            ->when($request->filled('source'), fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->where('source', $request->input('source'))))
            ->when($request->filled('product_id'), fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->where('product_id', (int) $request->input('product_id'))))
            ->when($request->input('used_build') === 'yes', fn ($query) => $query->whereHas('productClicks', fn ($clicks) => $clicks->whereNotNull('completed_at')))
            ->when($request->input('used_build') === 'no', fn ($query) => $query->whereDoesntHave('productClicks', fn ($clicks) => $clicks->whereNotNull('completed_at')))
            ->when($request->filled('birth_month'), fn ($query) => $query->whereHas('user', fn ($user) => $user->whereMonth('birth_date', (int) $request->input('birth_month'))))
            ->when($request->filled('builds_min'), fn ($query) => $query->whereHas('user', fn ($user) => $user->whereRaw('(SELECT COUNT(*) FROM generated_images WHERE generated_images.user_id = users.id) >= ?', [(int) $request->input('builds_min')])))
            ->when($request->filled('builds_max'), fn ($query) => $query->whereHas('user', fn ($user) => $user->whereRaw('(SELECT COUNT(*) FROM generated_images WHERE generated_images.user_id = users.id) <= ?', [(int) $request->input('builds_max')])))
            ->when($request->filled('active_days'), fn ($query) => $query->where('last_active_at', '>=', now()->subDays(max(1, (int) $request->input('active_days')))))
            ->when($request->filled('created_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('created_from')))
            ->when($request->filled('created_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('created_to')))
            ->latest('last_active_at');
    }

    private function segmentDefinition(Request $request): array
    {
        $linked = match ($request->input('linked')) {
            'yes' => true,
            'no' => false,
            default => null,
        };

        return array_filter([
            'linked' => $linked,
            'source' => trim((string) $request->input('source', '')) ?: null,
            'product_id' => $request->filled('product_id') ? (int) $request->input('product_id') : null,
            'active_days' => $request->filled('active_days') ? (int) $request->input('active_days') : null,
            'used_build' => match ($request->input('used_build')) {
                'yes' => true,
                'no' => false,
                default => null,
            },
            'birth_month' => $request->filled('birth_month') ? (int) $request->input('birth_month') : null,
            'builds_min' => $request->filled('builds_min') ? (int) $request->input('builds_min') : null,
            'builds_max' => $request->filled('builds_max') ? (int) $request->input('builds_max') : null,
            'created_from' => $request->input('created_from') ?: null,
            'created_to' => $request->input('created_to') ?: null,
        ], fn ($value): bool => $value !== null && $value !== '');
    }

    private function validateSegmentDefinition(array $definition): array
    {
        $allowed = Arr::only($definition, ['linked', 'source', 'product_id', 'active_days', 'telegram_ids', 'used_build', 'birth_month', 'builds_min', 'builds_max', 'created_from', 'created_to']);
        validator($allowed, [
            'linked' => ['sometimes', 'boolean'],
            'source' => ['sometimes', 'string', 'max:120'],
            'product_id' => ['sometimes', 'integer', 'min:1'],
            'active_days' => ['sometimes', 'integer', 'min:1', 'max:3650'],
            'telegram_ids' => ['sometimes', 'array', 'max:1000'],
            'telegram_ids.*' => ['integer', 'min:1'],
            'used_build' => ['sometimes', 'boolean'],
            'birth_month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'builds_min' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'builds_max' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
        ])->validate();

        return $allowed;
    }
}
