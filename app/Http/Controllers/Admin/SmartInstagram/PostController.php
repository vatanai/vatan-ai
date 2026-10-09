<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\Product;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\OutboundMessage;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\PostCampaignVersion;
use App\Models\SmartInstagram\PostFlowSession;
use App\Services\SmartInstagram\Automation\AutomationEngine;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use App\Services\SmartInstagram\Gateways\RichInstagramGateway;
use App\Services\SmartInstagram\PersianText;
use App\Services\SmartInstagram\Posts\PostAiSettings;
use App\Services\SmartInstagram\Posts\PostCampaignService;
use App\Services\SmartInstagram\Posts\PostContentWriter;
use App\Services\SmartInstagram\Posts\PostFlowService;
use App\Services\SmartInstagram\Posts\PostSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** «ثبت پست»: کارت پست‌ها، ویزارد مرحله‌ای، جزئیات و ابزار هوش مصنوعی. */
class PostController extends Controller
{
    public function index(Request $request, PostCampaignService $campaigns, PostSyncService $sync): View
    {
        $this->authorizeAbility('view');
        $status = array_key_exists((string) $request->query('status'), PostCampaignService::STATUSES) ? (string) $request->query('status') : '';

        $all = PostCampaign::query()->where('workspace_id', $this->ws())->with(['post', 'keywords', 'rule'])->latest('updated_at')->get();
        $list = $status !== '' ? $all->where('status', $status)->values() : $all;

        $linkedPostIds = $all->pluck('post_id');
        $unlinked = Post::query()->where('workspace_id', $this->ws())->whereNotIn('id', $linkedPostIds)
            ->orderByDesc('published_at')->limit(12)->get();

        return view('admin.smart-instagram.posts.index', [
            'campaigns' => $list,
            'counts' => $all->countBy('status')->all() + ['all' => $all->count()],
            'status' => $status,
            'metrics' => $this->metrics($all),
            'unlinked' => $unlinked,
            'legacy' => $campaigns->legacyRules(),
            'channel' => $sync->channel(),
            'canManage' => $this->context->can($this->admin(), 'manage_automation'),
        ]);
    }

    public function create(Request $request, PostCampaignService $campaigns, PostSyncService $sync): View|RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $selected = $request->filled('post') ? Post::query()->where('workspace_id', $this->ws())->find((int) $request->query('post')) : null;
        if ($selected && $selected->campaign) {
            return redirect()->route('admin.smart-instagram.posts.edit', $selected->campaign);
        }

        $campaign = new PostCampaign([
            'title' => '', 'status' => 'draft', 'follow_required' => true, 'public_reply_enabled' => true, 'dm_enabled' => true,
            'settings' => $campaigns->defaults(),
        ]);

        $captionKeywords = $selected ? $campaigns->keywordsFromCaption($selected->caption) : [];
        $captionKeywords = $captionKeywords ?: [['keyword' => 'لینک', 'match_mode' => 'contains', 'is_active' => true]];

        return $this->wizard($campaign, $selected, $sync, collect($captionKeywords));
    }

    public function edit(PostCampaign $campaign, PostSyncService $sync): View
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        $campaign->load('post', 'keywords');

        return $this->wizard($campaign, $campaign->post, $sync, $campaign->keywords->map->only(['keyword', 'match_mode', 'is_active']));
    }

    public function store(Request $request, PostCampaignService $campaigns): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $post = Post::query()->where('workspace_id', $this->ws())->find((int) $request->input('post_id'));
        if (!$post) {
            return back()->withInput()->withErrors(['post_id' => 'ابتدا یک پست انتخاب کنید.']);
        }
        if ($post->campaign) {
            return redirect()->route('admin.smart-instagram.posts.edit', $post->campaign)->with('warning', 'برای این پست قبلاً سناریو ثبت شده است.');
        }
        $data = $this->validated($request, $post, $campaigns);
        $campaign = $campaigns->save($post, $data, null, $this->admin()?->id);

        return redirect()->route('admin.smart-instagram.posts.show', $campaign)->with('success', $this->savedMessage($campaign, $post));
    }

    public function update(Request $request, PostCampaign $campaign, PostCampaignService $campaigns): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        $data = $this->validated($request, $campaign->post, $campaigns);
        $campaign = $campaigns->save($campaign->post, $data, $campaign, $this->admin()?->id);

        return redirect()->route('admin.smart-instagram.posts.show', $campaign)->with('success', $this->savedMessage($campaign, $campaign->post));
    }

    public function show(PostCampaign $campaign, PostFlowService $flow): View
    {
        $this->authorizeAbility('view');
        $this->own($campaign);
        $campaign->load(['post', 'keywords', 'rule', 'versions.admin:id,name']);

        $runs = $campaign->rule
            ? AutomationRun::query()->where('rule_id', $campaign->rule->id)->with('contact:id,username,display_name')->latest('id')->paginate(20)
            : null;
        $funnel = PostFlowSession::query()->where('campaign_id', $campaign->id)
            ->select('stage', DB::raw('COUNT(*) as total'))->groupBy('stage')->pluck('total', 'stage');
        $followStats = PostFlowSession::query()->where('campaign_id', $campaign->id)->whereNotNull('follow_status')
            ->select('follow_status', DB::raw('COUNT(*) as total'))->groupBy('follow_status')->pluck('total', 'follow_status');

        return view('admin.smart-instagram.posts.show', [
            'campaign' => $campaign,
            'post' => $campaign->post,
            'metrics' => $this->metrics(collect([$campaign]))[$campaign->id] ?? [],
            'runs' => $runs,
            'funnel' => $funnel,
            'followStats' => $followStats,
            'daily' => $campaign->post->dailyStats()->orderByDesc('day')->limit(14)->get(),
            'card' => $flow->cardMessage($campaign),
            'canManage' => $this->context->can($this->admin(), 'manage_automation'),
        ]);
    }

    public function status(Request $request, PostCampaign $campaign, PostCampaignService $campaigns): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', array_keys(PostCampaignService::STATUSES))]]);
        $campaigns->setStatus($campaign->load('post', 'rule'), $data['status'], $this->admin()?->id);

        return back()->with('success', 'وضعیت سناریو: '.PostCampaignService::STATUSES[$data['status']]);
    }

    public function recheck(PostCampaign $campaign, PostFlowService $flow, AutomationEngine $automation): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        $replayed = 0;
        if ($campaign->status === 'active' && $campaign->automation_rule_id && ($campaign->public_reply_enabled || $campaign->dm_enabled)) {
            $missingRuns = AutomationRun::query()
                ->where('rule_id', $campaign->automation_rule_id)
                ->where('mode', 'live')
                ->whereIn('status', ['success', 'partial', 'skipped'])
                ->whereNotExists(fn ($query) => $query->selectRaw('1')
                    ->from('instagram_outbound_messages as outbound')
                    ->whereColumn('outbound.automation_run_id', 'instagram_automation_runs.id'))
                ->oldest('id')->limit(100)->get();

            foreach ($missingRuns as $run) {
                $replayed += $automation->replay($run) ? 1 : 0;
            }
        }

        $retried = $flow->retryCampaign($campaign);
        $count = $replayed + $retried;

        return back()->with($count > 0 ? 'success' : 'warning', $count > 0
            ? $count.' اجرای ناقص یا ارسال ناموفق دوباره بررسی شد.'
            : 'ارسال ناموفق آماده‌ی بررسی مجددی برای این سناریو پیدا نشد.');
    }

    public function destroy(PostCampaign $campaign, PostCampaignService $campaigns): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        $campaigns->delete($campaign->load('rule'), $this->admin()?->id);

        return redirect()->route('admin.smart-instagram.posts.index')->with('success', 'سناریوی پست حذف شد؛ خود پست در فهرست همگام‌شده باقی ماند.');
    }

    public function restore(PostCampaign $campaign, PostCampaignVersion $version, PostCampaignService $campaigns): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        abort_unless((int) $version->campaign_id === (int) $campaign->id, 404);
        $campaigns->restore($campaign->load('post', 'rule'), $version, $this->admin()?->id);

        return back()->with('success', 'تنظیمات نسخه‌ی '.$version->version.' بازگردانده شد (وضعیت: پیش‌نویس).');
    }

    /** اجرای آزمایشی بدون ارسال: تطبیق کلمه + پیام‌هایی که مشتری خواهد دید. */
    public function simulate(Request $request, PostCampaign $campaign, AutomationEngine $engine, PostFlowService $flow): JsonResponse
    {
        $this->authorizeAbility('view');
        $this->own($campaign);
        $data = $request->validate(['text' => ['required', 'string', 'max:500'], 'follows' => ['nullable', 'in:yes,no,unknown']]);
        $campaign->load('post', 'rule', 'keywords');
        if (!$campaign->rule) {
            return response()->json(['matched' => false, 'steps' => [], 'message' => 'قانون اجرایی هنوز ساخته نشده است.']);
        }
        $result = $engine->simulate($campaign->rule, $data['text'], 'comment', true, $campaign->post->media_id);
        $s = (array) $campaign->settings;
        $steps = [];
        if ($result['matched']) {
            $publicStep = $campaign->public_reply_enabled ? [[
                'where' => 'کامنت',
                'title' => 'پاسخ عمومی',
                'text' => data_get($s, 'reply.ai_personalize')
                    ? 'پاسخ شخصی‌سازی‌شده با هوش مصنوعی (نمونه‌ی سبک): '.data_get($s, 'reply.styles.0')
                    : implode(' | ', (array) data_get($s, 'reply.styles', [])),
            ]] : [];
            $dmSteps = [];
            if ($campaign->dm_enabled) {
                // جریان دومرحله‌ای: فالوور ← کارت مستقیم؛ بدون فالو یا کاربر تازه (وضعیت نامشخص) ← درخواست فالو، بعد کارت.
                $follows = $data['follows'] ?? 'yes';
                if ($campaign->follow_required && $follows !== 'yes') {
                    $dmSteps[] = ['where' => 'دایرکت', 'title' => 'درخواست فالو + دکمه‌های «مشاهده پیج» و «'.data_get($s, 'follow.button').'»'.($follows === 'unknown' ? ' (وضعیت فالو نامشخص)' : ''), 'text' => data_get($s, 'follow.text')];
                    $dmSteps[] = ['where' => 'دایرکت', 'title' => 'بعد از زدن «'.data_get($s, 'follow.button').'»', 'text' => 'وضعیت فالو دوباره بررسی می‌شود؛ اگر فالو کرده بود کارت، وگرنه پیام یادآوری.'];
                }
                $card = $flow->cardMessage($campaign);
                $dmSteps[] = ['where' => 'دایرکت', 'title' => 'کارت', 'text' => $card['ok'] ? $card['title'].' — '.collect(data_get($card, 'message.attachment.payload.elements.0.buttons', []))->pluck('title')->implode(' / ') : $card['error'], 'ok' => $card['ok']];
            }
            $steps = data_get($s, 'flow.order') === 'dm_first'
                ? array_merge($dmSteps, $publicStep)
                : array_merge($publicStep, $dmSteps);
        }

        return response()->json($result + ['steps' => $steps]);
    }

    public function syncPosts(PostSyncService $sync): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $result = $sync->syncRecent(30);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function syncOne(PostCampaign $campaign, PostSyncService $sync): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($campaign);
        $result = $sync->syncOne($campaign->post);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function manual(Request $request, PostSyncService $sync): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $data = $request->validate([
            'media_ref' => ['required', 'string', 'max:300'],
        ]);
        $ref = trim($data['media_ref']);
        $permalink = str_contains($ref, 'instagram.com') ? $ref : null;
        $mediaId = $permalink ? null : preg_replace('/\D/', '', $ref);
        if ($permalink) {
            $shortcode = $sync->shortcode($permalink);
            $find = fn () => $shortcode ? Post::query()->where('workspace_id', $this->ws())->where('shortcode', $shortcode)->where('media_id', 'not like', 'link:%')->first() : null;
            $existing = $find();
            if (!$existing && $shortcode) {
                $sync->syncRecent(50); // شاید پست جدید است و هنوز همگام نشده
                $existing = $find();
            }
            if ($existing) {
                return redirect()->route('admin.smart-instagram.posts.create', ['post' => $existing->id]);
            }
            $mediaId = 'link:'.($shortcode ?: md5($permalink));
        }
        if (!$mediaId) {
            return back()->withErrors(['media_ref' => 'شناسه‌ی عددی رسانه یا لینک پست را وارد کنید.']);
        }
        $post = $sync->registerManual($mediaId, $permalink);
        if (!str_starts_with($mediaId, 'link:')) {
            $sync->syncOne($post);
        }

        return redirect()->route('admin.smart-instagram.posts.create', ['post' => $post->id])->with(
            $post->fresh()->isVerified() ? 'success' : 'warning',
            $post->fresh()->isVerified() ? 'پست از اتصال واقعی تأیید شد.' : 'پست با وضعیت «نیازمند بررسی اتصال» ثبت شد؛ تا تأیید اتصال فقط به‌صورت پیش‌نویس ذخیره می‌شود.'
        );
    }

    public function import(AutomationRule $rule, PostCampaignService $campaigns, PostSyncService $sync): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $campaign = $campaigns->importRule($rule, $sync, $this->admin()?->id);

        return redirect()->route('admin.smart-instagram.posts.edit', $campaign)->with('success', 'قانون قبلی به «ثبت پست» منتقل شد؛ اجرای فعلی تا ذخیره‌ی شما بدون تغییر ادامه دارد.');
    }

    public function aiGenerate(Request $request, PostContentWriter $writer): JsonResponse
    {
        $this->authorizeAbility('manage_automation');
        $data = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:public_reply,opening,follow,card'],
            'target_field' => ['nullable', 'string', 'max:40'],
            'post_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'link' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'array', 'max:30'],
            'keywords.*' => ['nullable', 'string', 'max:120'],
            'hint' => ['nullable', 'string', 'max:500'],
        ]);
        if (!config('smart_instagram.ai.enabled')) {
            return response()->json(['ok' => false, 'message' => 'هوش مصنوعی در تنظیمات سرور خاموش است.'], 422);
        }
        if (($data['target_field'] ?? null) !== null && PostContentWriter::targetSection($data['target_field']) === null) {
            return response()->json(['ok' => false, 'message' => 'فیلد هدف برای تولید نمونه معتبر نیست.'], 422);
        }
        $post = !empty($data['post_id']) ? Post::query()->where('workspace_id', $this->ws())->find((int) $data['post_id']) : null;

        return response()->json($writer->generate((array) ($data['sections'] ?? []), $post, $data, $data['target_field'] ?? null));
    }

    public function aiSettings(Request $request, PostAiSettings $settings): JsonResponse
    {
        $this->authorizeAbility('manage_knowledge');
        $data = $request->validate([
            'model' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9._\-]+\/[a-z0-9._:\-]+$/i'],
            'prompts' => ['nullable', 'array'],
            'prompts.*' => ['nullable', 'string', 'max:3000'],
        ]);

        return response()->json(['ok' => true, 'message' => 'تنظیمات هوش مصنوعی ذخیره شد.', 'settings' => $settings->save($data)]);
    }

    private function wizard(PostCampaign $campaign, ?Post $selected, PostSyncService $sync, Collection $keywords): View
    {
        $posts = Post::query()->where('workspace_id', $this->ws())
            ->with('campaign:id,post_id,status')
            ->orderByDesc('published_at')->orderByDesc('id')->limit(60)->get();
        $channel = $sync->channel();
        $gateway = null;
        try {
            $gateway = $channel ? app(GatewayManager::class)->for($channel) : null;
        } catch (\Throwable) {
        }

        $productRows = Product::query()->where('status', 'active')->orderByDesc('created_at')->orderByDesc('id')->limit(500)
            ->get(['id', 'name_fa', 'description_fa', 'slug', 'product_code', 'cover', 'thumbnail', 'sample_outputs'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name_fa,
                'description' => trim(strip_tags((string) $p->description_fa)),
                'image' => $p->displayImageUrl(),
                'url' => route('app.product', $p->route_slug),
            ])->values();
        $caption = PersianText::normalize((string) ($selected?->caption ?? ''));
        $captionTerms = collect(preg_split('/\s+/u', $caption, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($term) => mb_strlen($term) >= 3)->values();
        $productSuggestions = $productRows->take(10)->map(function (array $product) use ($captionTerms): array {
            $haystack = PersianText::normalize($product['name'].' '.$product['description']);
            $score = $captionTerms->sum(fn ($term) => str_contains($haystack, $term) ? 1 : 0);
            $product['_score'] = $score;

            return $product;
        })->sortByDesc('_score')->take(10)->values()->map(fn (array $product) => $product['id'])->all();

        return view('admin.smart-instagram.posts.wizard', [
            'campaign' => $campaign,
            'selected' => $selected,
            'posts' => $posts,
            'keywords' => $keywords->values(),
            'products' => $productRows,
            'productSuggestions' => $productSuggestions,
            'aiSettings' => app(PostAiSettings::class)->get(),
            'aiSections' => PostAiSettings::SECTIONS,
            'canEditAi' => $this->context->can($this->admin(), 'manage_knowledge'),
            'channel' => $channel,
            'followSupported' => $gateway instanceof RichInstagramGateway && $channel?->gateway !== 'sandbox',
            'outboundEnabled' => (bool) config('smart_instagram.outbound_enabled') && (bool) $channel?->outbound_enabled,
            'presets' => PostCampaignService::BUTTON_PRESETS,
            'unavailableButtons' => PostCampaignService::UNAVAILABLE_BUTTONS,
            'matchModes' => PostCampaignService::MATCH_MODES,
        ]);
    }

    private function validated(Request $request, Post $post, PostCampaignService $campaigns): array
    {
        $request->validate(PostCampaignService::rules(), [], PostCampaignService::ATTRIBUTE_NAMES);

        $keywords = collect((array) $request->input('keywords'))->filter(fn ($k) => PersianText::normalize((string) ($k['keyword'] ?? '')) !== '');
        if ($keywords->isEmpty()) {
            $keywords = collect($campaigns->keywordsFromCaption($post->caption));
        }
        if ($keywords->isEmpty()) {
            abort(back()->withInput()->withErrors(['keywords' => 'کلمه‌ی کلیدی در فرم یا کپشن پیدا نشد؛ حداقل یک کلمه وارد کنید.']));
        }
        $settings = (array) $request->input('settings');
        $dmEnabled = $request->boolean('dm_enabled');
        if ($dmEnabled) {
            if (!filled(data_get($settings, 'card.product_id'))) {
                abort(back()->withInput()->withErrors(['settings.card.product_id' => 'برای ارسال دایرکت، انتخاب محصول هدف الزامی است.']));
            }
            $buttons = collect((array) data_get($settings, 'card.buttons', []))->filter(fn ($b) => trim((string) ($b['label'] ?? '')) !== '');
            $hasLink = $buttons->contains(fn ($b) => ($b['type'] ?? 'web_url') === 'web_url' && (filled($b['url'] ?? null) || filled(data_get($settings, 'card.product_id'))));
            if (!$hasLink) {
                abort(back()->withInput()->withErrors(['settings.card.buttons' => 'کارت دایرکت حداقل یک دکمه‌ی لینک‌دار لازم دارد (یا یک محصول انتخاب کنید).']));
            }
        }
        $intent = (string) $request->input('intent', 'draft');

        return [
            'title' => $request->input('title'),
            'status' => $intent,
            'follow_required' => $request->boolean('follow_required'),
            'public_reply_enabled' => $request->boolean('public_reply_enabled'),
            'dm_enabled' => $dmEnabled,
            'settings' => $settings,
            'keywords' => $keywords->values()->all(),
        ];
    }

    private function savedMessage(PostCampaign $campaign, Post $post): string
    {
        $msg = 'سناریوی پست ذخیره شد (نسخه '.$campaign->version.' · '.PostCampaignService::STATUSES[$campaign->status].').';
        if (!$post->isVerified()) {
            $msg .= ' پست هنوز از اتصال واقعی تأیید نشده؛ تا تأیید فقط پیش‌نویس می‌ماند.';
        }

        return $msg;
    }

    /** @return array<int,array<string,mixed>> آمار هر سناریو با کوئری‌های تجمیعی */
    private function metrics(Collection $campaigns): array
    {
        $ruleIds = $campaigns->pluck('automation_rule_id')->filter()->values();
        if ($ruleIds->isEmpty()) {
            return [];
        }
        $runs = AutomationRun::query()->whereIn('rule_id', $ruleIds)
            ->select('rule_id', DB::raw('COUNT(*) as matched'), DB::raw("SUM(CASE WHEN status IN ('success','partial') THEN 1 ELSE 0 END) as succeeded"), DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"))
            ->groupBy('rule_id')->get()->keyBy('rule_id');
        $dms = OutboundMessage::query()
            ->join('instagram_automation_runs as r', 'r.id', '=', 'instagram_outbound_messages.automation_run_id')
            ->whereIn('r.rule_id', $ruleIds)
            ->whereIn('instagram_outbound_messages.status', ['sent', 'manual'])
            ->select('r.rule_id',
                DB::raw("SUM(CASE WHEN instagram_outbound_messages.kind = 'public_reply' THEN 1 ELSE 0 END) as replies"),
                DB::raw("SUM(CASE WHEN instagram_outbound_messages.kind <> 'public_reply' THEN 1 ELSE 0 END) as dms"))
            ->groupBy('r.rule_id')->get()->keyBy('rule_id');
        $lastStatus = AutomationRun::query()->whereIn('id', AutomationRun::query()->whereIn('rule_id', $ruleIds)->whereNotIn('status', ['running'])->select(DB::raw('MAX(id)'))->groupBy('rule_id'))
            ->get(['rule_id', 'status', 'created_at'])->keyBy('rule_id');

        $out = [];
        foreach ($campaigns as $campaign) {
            $id = $campaign->automation_rule_id;
            $out[$campaign->id] = [
                'matched' => (int) ($runs[$id]->matched ?? 0),
                'succeeded' => (int) ($runs[$id]->succeeded ?? 0),
                'failed' => (int) ($runs[$id]->failed ?? 0),
                'replies' => (int) ($dms[$id]->replies ?? 0),
                'dms' => (int) ($dms[$id]->dms ?? 0),
                'last_status' => $lastStatus[$id]->status ?? null,
                'last_at' => $lastStatus[$id]->created_at ?? null,
            ];
        }

        return $out;
    }

    private function own(PostCampaign $campaign): void
    {
        abort_unless((int) $campaign->workspace_id === $this->ws(), 404);
    }
}
