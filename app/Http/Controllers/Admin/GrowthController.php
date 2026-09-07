<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrowthContent;
use App\Models\GrowthAttribution;
use App\Models\GrowthDataSource;
use App\Models\GrowthEvent;
use App\Models\GrowthLink;
use App\Models\GrowthLinkSnapshot;
use App\Services\GrowthAnalyticsService;
use App\Services\GrowthDataHealthService;
use App\Services\GrowthDataIngestionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GrowthController extends Controller
{
    private const CHANNELS = [
        'instagram' => 'اینستاگرام',
        'telegram' => 'تلگرام',
        'youtube' => 'یوتیوب',
        'other' => 'سایر کانال‌ها',
    ];

    public function __construct(
        private readonly GrowthAnalyticsService $analytics,
        private readonly GrowthDataHealthService $dataHealth,
        private readonly GrowthDataIngestionService $ingestion,
    ) {}

    public function monitor(): View
    {
        $metrics = $this->metrics();
        $funnel = $this->funnel();
        $channels = $this->channelSummaries();
        $trend = $this->trend(14);
        $topContents = $this->topContents(5);
        $activeLinks = $this->linksWithMetrics(5, true);
        $sourceHealth = $this->dataHealth->report();

        return $this->render('admin.growth.monitor', 'پایش کامل', compact(
            'metrics', 'funnel', 'channels', 'trend', 'topContents', 'activeLinks', 'sourceHealth'
        ));
    }

    public function overview(): View
    {
        return $this->render('admin.growth.overview', 'نمای کلی رشد', [
            'metrics' => $this->metrics(),
            'channels' => $this->channelSummaries(),
            'trend' => $this->trend(30),
            'funnel' => $this->funnel(),
        ]);
    }

    public function channels(string $channel = 'instagram'): View
    {
        abort_unless(array_key_exists($channel, self::CHANNELS), 404);

        $contents = $this->growthReady()
            ? GrowthContent::with('link')->where('channel', $channel)->latest('published_at')->latest()->paginate(12)
            : collect();
        $links = $this->growthReady()
            ? GrowthLink::where('channel', $channel)->withCount([
                'events as clicks_count' => fn ($query) => $query->where('event_type', GrowthEvent::TYPE_CLICK),
                'events as page_opens_count' => fn ($query) => $query->where('event_type', GrowthEvent::TYPE_PAGE_OPEN),
            ])->latest()->limit(8)->get()
            : collect();
        $summary = $this->channelSummaries()->firstWhere('key', $channel) ?? $this->blankChannel($channel);

        return $this->render('admin.growth.channels', 'کانال‌ها — '.self::CHANNELS[$channel], compact(
            'channel', 'contents', 'links', 'summary'
        ) + ['channelLabels' => self::CHANNELS]);
    }

    public function contents(Request $request): View
    {
        $query = $this->growthReady() ? GrowthContent::with('link')->latest('published_at')->latest() : null;

        if ($query && $request->filled('channel') && array_key_exists($request->string('channel')->toString(), self::CHANNELS)) {
            $query->where('channel', $request->string('channel')->toString());
        }
        if ($query && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($query && $request->filled('search')) {
            $query->where('title', 'like', '%'.$request->string('search')->toString().'%');
        }

        return $this->render('admin.growth.contents', 'مدیریت محتواها', [
            'contents' => $query ? $query->paginate(16)->withQueryString() : collect(),
            'links' => $this->growthReady() ? GrowthLink::active()->latest()->get(['id', 'title', 'slug']) : collect(),
            'dataSources' => $this->dataSourceReady()
                ? GrowthDataSource::where('is_active', true)
                    ->where('ingestion_method', 'manual')
                    ->orderBy('priority')->get()
                : collect(),
            'channelLabels' => self::CHANNELS,
        ]);
    }

    public function storeContent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::in(array_keys(self::CHANNELS))],
            'content_type' => ['nullable', 'string', 'max:40'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:2000'],
            'growth_link_id' => ['nullable', 'exists:growth_links,id'],
            'status' => ['required', Rule::in(['active', 'draft', 'archived'])],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'engagements' => ['nullable', 'integer', 'min:0'],
            'comments' => ['nullable', 'integer', 'min:0'],
            'shares' => ['nullable', 'integer', 'min:0'],
            'likes' => ['nullable', 'integer', 'min:0'],
            'saves' => ['nullable', 'integer', 'min:0'],
            'data_source_id' => ['nullable', 'exists:growth_data_sources,id'],
            'metric_date' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
        ]);

        $metricValues = collect(GrowthDataIngestionService::METRICS)
            ->mapWithKeys(fn (string $metric) => [$metric => (int) ($data[$metric] ?? ($metric === 'views' ? ($data['impressions'] ?? 0) : 0))])
            ->all();
        $source = null;
        if ($this->dataSourceReady()) {
            $source = $request->filled('data_source_id')
                ? GrowthDataSource::find($request->integer('data_source_id'))
                : $this->defaultManualSource($data['channel']);
            $this->ensureSourceMatchesChannel($source, $data['channel']);
        }

        unset($data['data_source_id'], $data['metric_date'], $data['views']);
        $content = GrowthContent::create($data + ['created_by' => $request->user('admin')?->id]);
        if ($source && $request->hasAny(['impressions', 'engagements', 'comments', 'shares', 'likes', 'saves'])) {
            $this->ingestion->recordContentMetrics(
                $content, $source, $metricValues, $request->input('metric_date'),
                $request->user('admin')?->id
            );
        }

        return back()->with('success', 'محتوا با موفقیت ثبت شد.');
    }

    public function updateContent(Request $request, GrowthContent $growthContent): RedirectResponse
    {
        $data = $request->validate([
            'impressions' => ['required', 'integer', 'min:0'],
            'engagements' => ['required', 'integer', 'min:0'],
            'comments' => ['required', 'integer', 'min:0'],
            'shares' => ['required', 'integer', 'min:0'],
            'likes' => ['nullable', 'integer', 'min:0'],
            'saves' => ['nullable', 'integer', 'min:0'],
            'data_source_id' => ['nullable', 'exists:growth_data_sources,id'],
            'metric_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'draft', 'archived'])],
        ]);

        if ($this->dataSourceReady()) {
            $source = $request->filled('data_source_id')
                ? GrowthDataSource::find($request->integer('data_source_id'))
                : $this->defaultManualSource($growthContent->channel);
            $this->ensureSourceMatchesChannel($source, $growthContent->channel);
            $this->ingestion->recordContentMetrics(
                $growthContent,
                $source,
                [
                    'views' => $data['impressions'],
                    'engagements' => $data['engagements'],
                    'comments' => $data['comments'],
                    'shares' => $data['shares'],
                    'likes' => $data['likes'] ?? 0,
                    'saves' => $data['saves'] ?? 0,
                ],
                $data['metric_date'] ?? null,
                $request->user('admin')?->id,
            );
            $growthContent->update(['status' => $data['status']]);
        } else {
            $growthContent->update(collect($data)->only(['impressions', 'engagements', 'comments', 'shares', 'likes', 'saves', 'status'])->all());
        }

        return back()->with('success', 'آمار محتوا به‌روزرسانی شد.');
    }

    public function links(Request $request): View
    {
        $query = $this->growthReady() ? GrowthLink::query()->latest() : null;
        if ($query && $request->filled('channel') && array_key_exists($request->string('channel')->toString(), self::CHANNELS)) {
            $query->where('channel', $request->string('channel')->toString());
        }
        if ($query && $request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($builder) => $builder->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        }
        if ($query) {
            $query->withCount([
                'events as clicks_count' => fn ($builder) => $builder->where('event_type', GrowthEvent::TYPE_CLICK),
                'events as page_opens_count' => fn ($builder) => $builder->where('event_type', GrowthEvent::TYPE_PAGE_OPEN),
            ]);
        }

        return $this->render('admin.growth.links.index', 'مدیریت لینک‌ها', [
            'links' => $query ? $query->paginate(20)->withQueryString() : collect(),
            'metrics' => $this->metrics(),
            'channelLabels' => self::CHANNELS,
        ]);
    }

    public function createLink(): View
    {
        return $this->render('admin.growth.links.create', 'ساخت و کوتاه‌سازی لینک', [
            'channelLabels' => self::CHANNELS,
        ]);
    }

    public function storeLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash:ascii', 'max:80', 'unique:growth_links,slug'],
            'destination_url' => ['required', 'url:http,https', 'max:4000'],
            'channel' => ['required', Rule::in(array_keys(self::CHANNELS))],
            'content_type' => ['nullable', 'string', 'max:40'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $baseSlug = $data['slug'] ?? Str::slug($data['title']);
        $data['slug'] = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));
        if (GrowthLink::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-'.Str::lower(Str::random(5));
        }
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = $request->user('admin')?->id;
        $link = GrowthLink::create($data);

        return redirect()->route('admin.growth.links.analytics', $link)->with('success', 'لینک ساخته شد و آماده ثبت رویداد است.');
    }

    public function linkAnalytics(Request $request, ?GrowthLink $growthLink = null): View
    {
        $growthLink ??= $this->growthReady() ? GrowthLink::latest()->first() : null;
        $windows = collect();
        $events = collect();
        $summary = null;
        $selectedWindow = null;

        if ($growthLink) {
            $windows = $this->analytics->windows($growthLink, 31);
            $selectedWindow = $this->resolveWindow($request, $growthLink, $windows);
            $summary = $this->analytics->summarize($growthLink, $selectedWindow['start'], $selectedWindow['end']);
            $events = $growthLink->events()
                ->where('occurred_at', '>=', $selectedWindow['start'])
                ->where('occurred_at', '<', $selectedWindow['end'])
                ->latest('occurred_at')
                ->paginate(30)
                ->withQueryString();
        }

        return $this->render('admin.growth.links.analytics', 'آنالیز ۲۴ ساعته لینک', [
            'growthLink' => $growthLink,
            'links' => $this->growthReady() ? GrowthLink::latest()->get() : collect(),
            'windows' => $windows,
            'events' => $events,
            'summary' => $summary,
            'selectedWindow' => $selectedWindow,
            'trend' => $this->linkTrend($growthLink, 14),
        ]);
    }

    public function section(string $section): View
    {
        $definitions = [
            'attribution' => ['کاربران و اتریبیوشن', 'مسیر ورود، کاربر جدید و تکراری و منبع موثر هر تبدیل'],
            'products' => ['محصولات', 'عملکرد محصولات از ورودی کانال تا ساخت و خرید'],
            'sales' => ['فروش و تبدیل', 'تبدیل بازدید به ساخت، پرداخت و درآمد'],
            'retention' => ['بازگشت و خرید مجدد', 'رفتار کاربران بازگشتی و خریدهای تکراری'],
            'reports' => ['گزارش‌ها', 'خروجی‌های مدیریتی و گزارش‌های دوره‌ای رشد'],
            'settings' => ['تنظیمات رشد', 'تنظیم ثبت رویداد، کانال‌ها و قواعد اتریبیوشن'],
        ];
        abort_unless(isset($definitions[$section]), 404);

        [$title, $description] = $definitions[$section];

        return $this->render('admin.growth.section', $title, [
            'section' => $section,
            'description' => $description,
            'metrics' => $this->sectionMetrics($section),
            'trackerSnippet' => '<script src="'.route('growth.tracker').'" defer></script>',
        ]);
    }

    private function render(string $partial, string $title, array $data = []): View
    {
        return view('admin.growth.page', $data + compact('partial', 'title'));
    }

    private function ensureSourceMatchesChannel(?GrowthDataSource $source, string $channel): void
    {
        if (! $source) {
            throw ValidationException::withMessages(['data_source_id' => 'برای این کانال منبع ورود اطلاعات فعال پیدا نشد.']);
        }
        if (! $source->is_active || ! in_array($source->channel, ['all', $channel], true)) {
            throw ValidationException::withMessages(['data_source_id' => 'منبع انتخاب‌شده فعال نیست یا با کانال محتوا هماهنگ نیست.']);
        }
    }

    private function metrics(): array
    {
        $blank = [
            'clicks' => 0, 'clicks_delta' => 0, 'unique_clicks' => 0,
            'page_opens' => 0, 'success_rate' => 0, 'active_links' => 0,
            'contents' => 0, 'impressions' => 0,
        ];
        if (! $this->growthReady()) {
            return $blank;
        }

        $periodStart = now()->subDays(30);
        $previousStart = now()->subDays(60);
        $clicks = GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)->where('occurred_at', '>=', $periodStart)->count();
        $previousClicks = GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)
            ->whereBetween('occurred_at', [$previousStart, $periodStart])->count();
        $opens = GrowthEvent::where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->where('occurred_at', '>=', $periodStart)->count();
        $uniqueClicks = GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)->where('occurred_at', '>=', $periodStart)
            ->whereNotNull('visitor_id')->distinct('visitor_id')->count('visitor_id');

        return [
            'clicks' => $clicks,
            'clicks_delta' => $this->delta($clicks, $previousClicks),
            'unique_clicks' => $uniqueClicks,
            'page_opens' => $opens,
            'success_rate' => $clicks > 0 ? round(min(100, ($opens / $clicks) * 100), 1) : 0,
            'active_links' => GrowthLink::where('is_active', true)->count(),
            'contents' => GrowthContent::count(),
            'impressions' => (int) GrowthContent::sum('impressions'),
        ];
    }

    private function channelSummaries(): Collection
    {
        return collect(self::CHANNELS)->map(function (string $label, string $key) {
            if (! $this->growthReady()) {
                return $this->blankChannel($key);
            }

            $linkIds = GrowthLink::where('channel', $key)->pluck('id');
            $clicks = GrowthEvent::whereIn('growth_link_id', $linkIds)->where('event_type', GrowthEvent::TYPE_CLICK)->count();
            $opens = GrowthEvent::whereIn('growth_link_id', $linkIds)->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count();

            return [
                'key' => $key,
                'label' => $label,
                'contents' => GrowthContent::where('channel', $key)->count(),
                'links' => $linkIds->count(),
                'clicks' => $clicks,
                'page_opens' => $opens,
                'success_rate' => $clicks > 0 ? round(min(100, ($opens / $clicks) * 100), 1) : 0,
            ];
        })->values();
    }

    private function blankChannel(string $channel): array
    {
        return [
            'key' => $channel,
            'label' => self::CHANNELS[$channel],
            'contents' => 0,
            'links' => 0,
            'clicks' => 0,
            'page_opens' => 0,
            'success_rate' => 0,
        ];
    }

    private function trend(int $days): array
    {
        $labels = collect(range($days - 1, 0))->map(fn ($offset) => now()->subDays($offset)->format('m/d'))->push(now()->format('m/d'));
        if (! $this->growthReady()) {
            return ['labels' => $labels, 'clicks' => array_fill(0, $days, 0), 'opens' => array_fill(0, $days, 0)];
        }

        $events = GrowthEvent::where('occurred_at', '>=', now()->subDays($days - 1)->startOfDay())->get();
        $grouped = $events->groupBy(fn (GrowthEvent $event) => $event->occurred_at->format('m/d'));

        return [
            'labels' => $labels,
            'clicks' => $labels->map(fn ($day) => $grouped->get($day, collect())->where('event_type', GrowthEvent::TYPE_CLICK)->count())->all(),
            'opens' => $labels->map(fn ($day) => $grouped->get($day, collect())->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count())->all(),
        ];
    }

    private function funnel(): array
    {
        $attributedGenerations = $this->attributionReady()
            ? GrowthAttribution::where('stage', 'generation_completed')->count()
            : 0;
        $attributedPurchases = $this->attributionReady()
            ? GrowthAttribution::whereIn('stage', ['purchase', 'plan_purchase'])->count()
            : 0;
        $stages = [
            ['label' => 'نمایش محتوا', 'value' => $this->growthReady() ? (int) GrowthContent::sum('impressions') : 0],
            ['label' => 'تعامل', 'value' => $this->growthReady() ? (int) GrowthContent::sum('engagements') : 0],
            ['label' => 'کلیک لینک', 'value' => $this->growthReady() ? GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)->count() : 0],
            ['label' => 'بازشدن مقصد', 'value' => $this->growthReady() ? GrowthEvent::where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count() : 0],
            ['label' => 'ساخت موفق', 'value' => $attributedGenerations],
            ['label' => 'خرید موفق', 'value' => $attributedPurchases],
        ];

        $max = max(1, ...array_column($stages, 'value'));

        return collect($stages)->map(fn ($stage) => $stage + ['width' => max(6, round(($stage['value'] / $max) * 100, 1))])->all();
    }

    private function topContents(int $limit): Collection
    {
        if (! $this->growthReady()) {
            return collect();
        }

        return GrowthContent::with('link')->orderByDesc('engagements')->limit($limit)->get()->map(function (GrowthContent $content) {
            $content->clicks_count = $content->link
                ? $content->link->events()->where('event_type', GrowthEvent::TYPE_CLICK)->count()
                : 0;
            return $content;
        });
    }

    private function linksWithMetrics(int $limit, bool $activeOnly = false): Collection
    {
        if (! $this->growthReady()) {
            return collect();
        }

        $query = GrowthLink::query();
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->withCount([
            'events as clicks_count' => fn ($builder) => $builder->where('event_type', GrowthEvent::TYPE_CLICK),
            'events as page_opens_count' => fn ($builder) => $builder->where('event_type', GrowthEvent::TYPE_PAGE_OPEN),
        ])->orderByDesc('clicks_count')->limit($limit)->get();
    }

    private function resolveWindow(Request $request, GrowthLink $link, Collection $windows): array
    {
        $windowId = $request->integer('window');
        if ($windowId > 0) {
            $snapshot = GrowthLinkSnapshot::where('growth_link_id', $link->id)->find($windowId);
            if ($snapshot) {
                return ['id' => $snapshot->id, 'start' => $snapshot->window_start, 'end' => $snapshot->window_end, 'is_live' => false];
            }
        }

        $current = $windows->first();

        return [
            'id' => null,
            'start' => Carbon::parse($current['window_start']),
            'end' => Carbon::parse($current['window_end']),
            'is_live' => true,
        ];
    }

    private function linkTrend(?GrowthLink $link, int $days): array
    {
        if (! $link) {
            return ['labels' => [], 'clicks' => [], 'opens' => []];
        }

        $labels = collect(range($days - 1, 0))->map(fn ($offset) => now()->subDays($offset)->format('m/d'))->push(now()->format('m/d'));
        $events = $link->events()->where('occurred_at', '>=', now()->subDays($days - 1)->startOfDay())->get()
            ->groupBy(fn (GrowthEvent $event) => $event->occurred_at->format('m/d'));

        return [
            'labels' => $labels,
            'clicks' => $labels->map(fn ($day) => $events->get($day, collect())->where('event_type', GrowthEvent::TYPE_CLICK)->count())->all(),
            'opens' => $labels->map(fn ($day) => $events->get($day, collect())->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count())->all(),
        ];
    }

    private function sectionMetrics(string $section): array
    {
        $metrics = $this->metrics();

        return match ($section) {
            'attribution' => [
                ['label' => 'کاربران یکتا', 'value' => $metrics['unique_clicks']],
                ['label' => 'کاربر شناسایی‌شده', 'value' => $this->attributionReady() ? GrowthAttribution::where('stage', 'landing_identified')->distinct('user_id')->count('user_id') : 0],
                ['label' => 'تبدیل منتسب‌شده', 'value' => $this->attributionReady() ? GrowthAttribution::whereIn('stage', ['generation_completed', 'purchase', 'plan_purchase'])->count() : 0],
            ],
            'products' => [
                ['label' => 'محصول فعال', 'value' => Schema::hasTable('products') ? DB::table('products')->where('status', 'active')->count() : 0],
                ['label' => 'شروع ساخت از رشد', 'value' => $this->attributionReady() ? GrowthAttribution::where('stage', 'generation_started')->count() : 0],
                ['label' => 'ساخت موفق از رشد', 'value' => $this->attributionReady() ? GrowthAttribution::where('stage', 'generation_completed')->count() : 0],
            ],
            'sales' => [
                ['label' => 'خرید منتسب‌شده', 'value' => $this->attributionReady() ? GrowthAttribution::whereIn('stage', ['purchase', 'plan_purchase'])->count() : 0],
                ['label' => 'اعتبار منتسب‌شده', 'value' => $this->attributedCredits()],
                ['label' => 'نرخ بازشدن مقصد', 'value' => $metrics['success_rate'].'٪'],
            ],
            'retention' => [
                ['label' => 'بازدید تکراری', 'value' => $this->growthReady() ? GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)->where('is_new_visitor', false)->count() : 0],
                ['label' => 'خرید مجدد منتسب', 'value' => $this->attributionReady() ? GrowthAttribution::whereIn('stage', ['purchase', 'plan_purchase'])->where('is_repeat', true)->count() : 0],
                ['label' => 'کاربران کل', 'value' => Schema::hasTable('users') ? DB::table('users')->count() : 0],
            ],
            'reports' => [
                ['label' => 'لینک قابل گزارش', 'value' => $metrics['active_links']],
                ['label' => 'محتوای ثبت‌شده', 'value' => $metrics['contents']],
                ['label' => 'بازه نگهداری', 'value' => 'دائمی'],
            ],
            default => [
                ['label' => 'کانال فعال', 'value' => $this->channelSummaries()->where('links', '>', 0)->count()],
                ['label' => 'لینک فعال', 'value' => $metrics['active_links']],
                ['label' => 'نسخه رهگیری', 'value' => '۱.۰'],
            ],
        };
    }

    private function attributedCredits(): int
    {
        if (! $this->attributionReady()) {
            return 0;
        }

        $orderCredits = Schema::hasTable('orders')
            ? DB::table('growth_attributions')->join('orders', 'orders.id', '=', 'growth_attributions.order_id')
                ->where('growth_attributions.stage', 'purchase')->sum('orders.final_credits')
            : 0;
        $planCredits = Schema::hasTable('plan_purchases')
            ? DB::table('growth_attributions')->join('plan_purchases', 'plan_purchases.id', '=', 'growth_attributions.plan_purchase_id')
                ->where('growth_attributions.stage', 'plan_purchase')->sum('plan_purchases.granted_tokens')
            : 0;

        return (int) ($orderCredits + $planCredits);
    }

    private function delta(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function growthReady(): bool
    {
        return Schema::hasTable('growth_links')
            && Schema::hasTable('growth_contents')
            && Schema::hasTable('growth_events')
            && Schema::hasTable('growth_link_24h_snapshots');
    }

    private function attributionReady(): bool
    {
        return Schema::hasTable('growth_attributions');
    }

    private function dataSourceReady(): bool
    {
        return Schema::hasTable('growth_data_sources')
            && Schema::hasTable('growth_raw_records')
            && Schema::hasTable('growth_content_daily_metrics');
    }

    private function defaultManualSource(string $channel): ?GrowthDataSource
    {
        return GrowthDataSource::where('is_active', true)
            ->whereIn('channel', [$channel, 'all'])
            ->where('ingestion_method', 'manual')
            ->orderByRaw('CASE WHEN channel = ? THEN 0 ELSE 1 END', [$channel])
            ->orderBy('priority')
            ->first();
    }
}
