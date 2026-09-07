<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrowthContent;
use App\Models\GrowthEvent;
use App\Models\GrowthLink;
use App\Models\MarketingCampaign;
use App\Models\MarketingContent;
use App\Models\MarketingOperationRun;
use App\Models\MarketingCostEvent;
use App\Models\MarketingEvent;
use App\Models\MarketingIntegration;
use App\Models\MarketingScenario;
use App\Models\Product;
use App\Services\MarketingCostAnalysisService;
use App\Services\MarketingAnalyticsService;
use App\Services\MetaInstagramApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingTechnologyController extends Controller
{
    public function index(): View
    {
        return view('admin.marketing-technology.index', [
            'title' => 'تکنولوژی مارکتینگ',
            'metrics' => $this->metrics(),
            'modules' => $this->modules(),
            'pipeline' => $this->pipeline(),
        ]);
    }

    public function contentCalendar(Request $request): View
    {
        $ready = $this->foundationReady();
        $query = $ready ? MarketingContent::with(['campaign', 'scenario', 'product'])->latest('publish_at')->latest() : null;
        if ($query && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($query && $request->filled('from')) {
            $query->whereDate('publish_at', '>=', $request->date('from'));
        }
        if ($query && $request->filled('to')) {
            $query->whereDate('publish_at', '<=', $request->date('to'));
        }

        return view('admin.marketing-technology.content-calendar', [
            'title' => 'تقویم و صف محتوا',
            'ready' => $ready,
            'contents' => $query ? $query->paginate(12)->withQueryString() : $this->emptyPaginator(),
            'campaigns' => $ready ? MarketingCampaign::query()->whereIn('status', ['draft', 'active'])->orderBy('name')->get() : collect(),
            'scenarios' => $ready ? MarketingScenario::query()->whereIn('status', ['draft', 'active'])->orderBy('name')->get() : collect(),
            'products' => Schema::hasTable('products') ? Product::query()->where('status', 'active')->orderBy('name_fa')->limit(200)->get(['id', 'name_fa', 'product_code']) : collect(),
            'operations' => $ready ? MarketingOperationRun::with('content')->latest()->limit(12)->get() : collect(),
        ]);
    }

    public function storeContent(Request $request): RedirectResponse
    {
        abort_unless($this->foundationReady(), 503, 'مدل داده‌ی تکنولوژی مارکتینگ هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_scenario_id' => ['nullable', 'exists:marketing_scenarios,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'content_type' => ['required', Rule::in(['reel', 'post', 'story', 'video'])],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'ready', 'published', 'paused'])],
            'publish_at' => ['nullable', 'date'],
            'hook' => ['nullable', 'string', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'keyword' => ['nullable', 'string', 'max:120'],
        ]);
        $data['created_by'] = $request->user('admin')?->id;
        MarketingContent::query()->create($data);

        return back()->with('success', 'محتوا در تقویم تکنولوژی مارکتینگ ثبت شد.');
    }

    public function updateContent(Request $request, MarketingContent $marketingContent): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'ready', 'published', 'paused'])],
            'publish_at' => ['nullable', 'date'],
            'hook' => ['nullable', 'string', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'keyword' => ['nullable', 'string', 'max:120'],
        ]);
        $marketingContent->update($data);

        return back()->with('success', 'محتوا به‌روزرسانی شد.');
    }

    public function queueContent(MarketingContent $marketingContent): RedirectResponse
    {
        abort_unless($this->foundationReady(), 503, 'مدل داده‌ی تکنولوژی مارکتینگ هنوز روی این محیط اجرا نشده است.');
        $run = MarketingOperationRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'operation_type' => 'content.prepare',
            'status' => 'queued',
            'idempotency_key' => 'content-'.$marketingContent->id.'-'.Str::uuid(),
            'marketing_campaign_id' => $marketingContent->marketing_campaign_id,
            'marketing_content_id' => $marketingContent->id,
            'marketing_scenario_id' => $marketingContent->marketing_scenario_id,
        ]);
        $marketingContent->update(['status' => 'ready']);

        return back()->with('success', "محتوا وارد صف شد (اجرای شماره {$run->id}).");
    }

    public function scenarios(): View
    {
        $ready = $this->foundationReady();

        return view('admin.marketing-technology.scenarios', [
            'title' => 'سناریوهای کامنت و دایرکت',
            'ready' => $ready,
            'scenarios' => $ready ? MarketingScenario::with('versions')->latest()->paginate(10) : $this->emptyPaginator(),
        ]);
    }

    public function inbox(Request $request): View
    {
        $ready = Schema::hasTable('marketing_events');
        $query = $ready ? MarketingEvent::with(['campaign', 'content', 'scenario'])->latest('occurred_at') : null;
        if ($query && $request->filled('channel')) $query->where('channel', $request->string('channel')->toString());
        if ($query && $request->filled('processing_status')) $query->where('processing_status', $request->string('processing_status')->toString());
        if ($query && $request->filled('search')) $query->where(function ($builder) use ($request) { $term = '%'.$request->string('search')->toString().'%'; $builder->where('event_type', 'like', $term)->orWhere('external_id', 'like', $term)->orWhere('actor_ref', 'like', $term); });

        return view('admin.marketing-technology.inbox', [
            'title' => 'صندوق گفتگوها',
            'ready' => $ready,
            'events' => $query ? $query->paginate(15)->withQueryString() : $this->emptyPaginator(15),
        ]);
    }

    public function storeScenario(Request $request): RedirectResponse
    {
        abort_unless($this->foundationReady(), 503, 'مدل داده‌ی تکنولوژی مارکتینگ هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'alpha_dash:ascii', 'max:100', 'unique:marketing_scenarios,code'],
            'channel' => ['required', Rule::in(['instagram', 'telegram', 'youtube', 'other'])],
            'trigger_type' => ['required', Rule::in(['comment_keyword', 'direct_message', 'manual'])],
            'status' => ['required', Rule::in(['draft', 'active', 'paused'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_keyword' => ['nullable', 'string', 'max:120'],
            'public_reply' => ['nullable', 'string', 'max:2000'],
            'opening_message' => ['required', 'string', 'max:4000'],
            'opening_button_label' => ['nullable', 'string', 'max:120'],
            'followup_message' => ['nullable', 'string', 'max:4000'],
            'followup_button_label' => ['nullable', 'string', 'max:120'],
            'followup_url' => ['nullable', 'url:http,https', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $scenario = MarketingScenario::query()->create([
                'name' => $data['name'], 'code' => $data['code'], 'channel' => $data['channel'],
                'trigger_type' => $data['trigger_type'], 'status' => $data['status'],
                'description' => $data['description'] ?? null, 'created_by' => $request->user('admin')?->id,
            ]);
            $scenario->versions()->create($this->versionPayload($data, 1, $request->user('admin')?->id));
        });

        return back()->with('success', 'سناریو و نسخه‌ی اول آن ساخته شد.');
    }

    public function updateScenario(Request $request, MarketingScenario $marketingScenario): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_keyword' => ['nullable', 'string', 'max:120'],
            'public_reply' => ['nullable', 'string', 'max:2000'],
            'opening_message' => ['required', 'string', 'max:4000'],
            'opening_button_label' => ['nullable', 'string', 'max:120'],
            'followup_message' => ['nullable', 'string', 'max:4000'],
            'followup_button_label' => ['nullable', 'string', 'max:120'],
            'followup_url' => ['nullable', 'url:http,https', 'max:2000'],
        ]);
        DB::transaction(function () use ($data, $request, $marketingScenario): void {
            $marketingScenario->update(['name' => $data['name'], 'status' => $data['status'], 'description' => $data['description'] ?? null]);
            $version = ((int) $marketingScenario->versions()->max('version')) + 1;
            $marketingScenario->versions()->create($this->versionPayload($data, $version, $request->user('admin')?->id));
            $marketingScenario->update(['active_version' => $version]);
        });

        return back()->with('success', 'نسخه‌ی جدید سناریو ذخیره شد.');
    }

    public function reports(Request $request, MarketingAnalyticsService $analytics): View
    {
        return view('admin.marketing-technology.reports', $analytics->report($request->only(['from', 'to', 'channel'])) + [
            'title' => 'گزارش و تحلیل',
        ]);
    }

    public function costs(Request $request, MarketingCostAnalysisService $costs): View
    {
        $report = $costs->report($request->only(['from', 'to', 'refresh_rate']));

        return view('admin.marketing-technology.costs', $report + [
            'title' => 'مرکز هزینه',
            'campaigns' => $this->foundationReady() ? MarketingCampaign::orderBy('name')->get() : collect(),
            'contents' => $this->foundationReady() ? MarketingContent::orderBy('title')->limit(200)->get(['id', 'title']) : collect(),
        ]);
    }

    public function storeCost(Request $request): RedirectResponse
    {
        abort_unless($this->foundationReady() && Schema::hasTable('marketing_cost_events'), 503, 'مدل هزینه هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:80'],
            'service' => ['required', 'string', 'max:100'],
            'units' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:40'],
            'unit_cost_usd' => ['nullable', 'numeric', 'min:0'],
            'fx_rate_toman' => ['nullable', 'numeric', 'min:0'],
            'cost_toman' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['estimated', 'actual', 'needs_review'])],
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_content_id' => ['nullable', 'exists:marketing_contents,id'],
            'incurred_at' => ['required', 'date'],
        ]);
        $calculated = (float) ($data['units'] ?? 0) * (float) ($data['unit_cost_usd'] ?? 0) * (float) ($data['fx_rate_toman'] ?? 0);
        $data['cost_toman'] = array_key_exists('cost_toman', $data) && $data['cost_toman'] !== null
            ? (int) $data['cost_toman']
            : (int) round($calculated);
        if ((int) $data['cost_toman'] <= 0 && $calculated <= 0) {
            $data['status'] = 'needs_review';
        }
        MarketingCostEvent::query()->create($data);

        return back()->with('success', 'رویداد هزینه ثبت شد و در گزارش‌ها محاسبه می‌شود.');
    }

    public function integrations(): View
    {
        return view('admin.marketing-technology.integrations', [
            'title' => 'اتصال‌ها و سلامت سرویس',
            'ready' => Schema::hasTable('marketing_integrations'),
            'integration' => Schema::hasTable('marketing_integrations') ? MarketingIntegration::query()->where('provider', 'meta')->first() : null,
            'webhookUrl' => route('webhooks.meta.verify'),
        ]);
    }

    public function storeMetaIntegration(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('marketing_integrations'), 503, 'مدل اتصال هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'instagram_user_id' => ['required', 'string', 'max:120'],
            'page_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['required', 'string', 'max:5000'],
        ]);
        $integration = MarketingIntegration::query()->firstOrNew(['provider' => 'meta']);
        $integration->fill([
            'provider' => 'meta',
            'name' => $data['name'],
            'status' => 'configured',
            'credentials' => [
                'instagram_user_id' => trim($data['instagram_user_id']),
                'page_id' => isset($data['page_id']) ? trim($data['page_id']) : null,
                'access_token' => trim($data['access_token']),
            ],
            'last_error' => null,
            'created_by' => $request->user('admin')?->id,
        ]);
        $integration->save();

        return back()->with('success', 'اتصال `Meta` به‌صورت امن ذخیره شد؛ اکنون تست اتصال را اجرا کن.');
    }

    public function testMetaIntegration(MarketingIntegration $marketingIntegration, MetaInstagramApiService $meta): RedirectResponse
    {
        abort_unless($marketingIntegration->provider === 'meta', 404);
        $result = $meta->testConnection($marketingIntegration);
        $marketingIntegration->forceFill([
            'status' => $result['ok'] ? 'healthy' : 'error',
            'last_checked_at' => now(),
            'last_success_at' => $result['ok'] ? now() : $marketingIntegration->last_success_at,
            'last_error' => $result['ok'] ? null : $result['message'],
        ])->save();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function logs(Request $request): View
    {
        $ready = Schema::hasTable('marketing_operation_runs');
        $query = $ready ? MarketingOperationRun::with(['content', 'scenario', 'campaign'])->latest() : null;
        if ($query && $request->filled('status')) $query->where('status', $request->string('status')->toString());

        return view('admin.marketing-technology.logs', [
            'title' => 'لاگ عملیات',
            'ready' => $ready,
            'operations' => $query ? $query->paginate(15)->withQueryString() : $this->emptyPaginator(15),
        ]);
    }

    public function retryOperation(MarketingOperationRun $marketingOperationRun): RedirectResponse
    {
        abort_unless(in_array($marketingOperationRun->status, ['failed', 'cancelled'], true), 422, 'فقط عملیات ناموفق یا لغوشده قابل تلاش مجدد است.');
        $marketingOperationRun->update([
            'status' => 'queued',
            'attempt' => ((int) $marketingOperationRun->attempt) + 1,
            'started_at' => null,
            'finished_at' => null,
            'error_code' => null,
            'error_message' => null,
            'response_payload' => null,
        ]);

        return back()->with('success', 'عملیات دوباره وارد صف شد.');
    }

    private function section(string $title, string $description): View
    {
        return view('admin.marketing-technology.section', [
            'title' => $title,
            'description' => $description,
            'modules' => $this->modules(),
        ]);
    }

    private function metrics(): array
    {
        $ready = Schema::hasTable('growth_contents') && Schema::hasTable('growth_links') && Schema::hasTable('growth_events');

        return [
            'contents' => $ready ? GrowthContent::count() : 0,
            'links' => $ready ? GrowthLink::where('is_active', true)->count() : 0,
            'clicks' => $ready ? GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)->count() : 0,
            'opens' => $ready ? GrowthEvent::where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count() : 0,
        ];
    }

    private function modules(): array
    {
        return [
            ['key' => 'content-calendar', 'title' => 'تقویم و صف محتوا', 'description' => 'برنامه‌ریزی، تولید، تأیید و انتشار محتوا', 'icon' => 'fa-calendar-days', 'status' => 'فعال'],
            ['key' => 'scenarios', 'title' => 'سناریوهای کامنت و دایرکت', 'description' => 'مدیریت متن، دکمه، لینک و قواعد پاسخ', 'icon' => 'fa-comments', 'status' => 'فعال'],
            ['key' => 'inbox', 'title' => 'صندوق گفتگوها', 'description' => 'یک نمای واحد از تعاملات ورودی و خروجی', 'icon' => 'fa-inbox', 'status' => 'فعال'],
            ['key' => 'reports', 'title' => 'گزارش و تحلیل', 'description' => 'قیف تبدیل، عملکرد محتوا و بینش مدیریتی', 'icon' => 'fa-chart-line', 'status' => 'فعال'],
            ['key' => 'costs', 'title' => 'مرکز هزینه', 'description' => 'هزینه‌ی هر عملیات، محتوا و کمپین', 'icon' => 'fa-coins', 'status' => 'فعال'],
            ['key' => 'integrations', 'title' => 'اتصال‌ها و سلامت سرویس', 'description' => 'کنترل اتصال‌ها و آخرین وضعیت دریافت داده', 'icon' => 'fa-plug', 'status' => 'کلید لازم'],
            ['key' => 'logs', 'title' => 'لاگ عملیات', 'description' => 'خطاها، تلاش مجدد و وضعیت اجرای عملیات', 'icon' => 'fa-list-check', 'status' => 'فعال'],
        ];
    }

    private function pipeline(): array
    {
        return [
            ['title' => 'محصول', 'description' => 'انتخاب محصول و دارایی', 'icon' => 'fa-box-open'],
            ['title' => 'محتوا', 'description' => 'هوک، کپشن و رسانه', 'icon' => 'fa-photo-film'],
            ['title' => 'تأیید', 'description' => 'صف تلگرام و کنترل کیفیت', 'icon' => 'fa-circle-check'],
            ['title' => 'انتشار', 'description' => 'اتصال بعد از آماده‌شدن کلید', 'icon' => 'fa-paper-plane'],
            ['title' => 'اندازه‌گیری', 'description' => 'کلیک، گفتگو، ساخت و خرید', 'icon' => 'fa-chart-pie'],
        ];
    }

    private function versionPayload(array $data, int $version, ?int $adminId): array
    {
        return [
            'version' => $version,
            'status' => ($data['status'] ?? 'draft') === 'active' ? 'published' : 'draft',
            'trigger_keyword' => $data['trigger_keyword'] ?? null,
            'public_reply' => $data['public_reply'] ?? null,
            'opening_message' => $data['opening_message'],
            'opening_button_label' => $data['opening_button_label'] ?? null,
            'followup_message' => $data['followup_message'] ?? null,
            'followup_button_label' => $data['followup_button_label'] ?? null,
            'followup_url' => $data['followup_url'] ?? null,
            'created_by' => $adminId,
            'published_at' => ($data['status'] ?? 'draft') === 'active' ? now() : null,
        ];
    }

    private function foundationReady(): bool
    {
        return Schema::hasTable('marketing_campaigns')
            && Schema::hasTable('marketing_contents')
            && Schema::hasTable('marketing_scenarios')
            && Schema::hasTable('marketing_operation_runs');
    }

    private function emptyPaginator(int $perPage = 12): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => request()->url()]);
    }
}
