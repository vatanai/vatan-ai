<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrowthContent;
use App\Models\GrowthDataMapping;
use App\Models\GrowthDataSource;
use App\Models\GrowthRawRecord;
use App\Services\GrowthDataHealthService;
use App\Services\GrowthDataIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GrowthDataSourceController extends Controller
{
    private const SOURCE_TYPES = [
        'internal' => 'داخلی وطن',
        'internal_tracking' => 'رهگیری داخلی',
        'manual' => 'ورود دستی',
        'api' => 'رابط برنامه‌نویسی',
        'webhook' => 'وب‌هوک',
        'import' => 'ورود فایل',
    ];

    private const INGESTION_METHODS = [
        'database' => 'دیتابیس داخلی',
        'tracking' => 'رهگیری خودکار',
        'api' => 'رابط برنامه‌نویسی',
        'webhook' => 'وب‌هوک',
        'manual' => 'فرم دستی',
        'csv' => 'ورود فایل سی‌اس‌وی',
        'excel' => 'ورود فایل اکسل',
    ];

    private const CHANNELS = [
        'all' => 'همه کانال‌ها',
        'website' => 'وب‌سایت وطن',
        'instagram' => 'اینستاگرام',
        'telegram' => 'تلگرام',
        'youtube' => 'یوتیوب',
        'other' => 'سایر کانال‌ها',
    ];

    private const METRICS = [
        'views' => 'بازدید محتوا',
        'engagements' => 'تعامل',
        'comments' => 'کامنت',
        'shares' => 'اشتراک‌گذاری',
        'likes' => 'پسند',
        'saves' => 'ذخیره',
        'clicks' => 'کلیک',
        'unique_clicks' => 'کلیک یکتا',
        'page_opens' => 'بازشدن صفحه',
        'referrer' => 'ارجاع‌دهنده',
        'device' => 'نوع دستگاه',
        'operating_system' => 'سیستم‌عامل',
        'browser' => 'مرورگر',
        'country' => 'کشور',
        'city' => 'شهر',
        'occurred_at' => 'زمان رویداد',
        'visitor_status' => 'کاربر جدید یا تکراری',
        'channel' => 'کانال',
        'content_id' => 'محتوا',
        'link_id' => 'لینک',
        'users' => 'کاربر',
        'generations' => 'ساخت',
        'purchases' => 'خرید',
        'plans' => 'پلن',
        'credit_usage' => 'مصرف اعتبار',
    ];

    public function __construct(
        private readonly GrowthDataHealthService $health,
        private readonly GrowthDataIngestionService $ingestion,
    ) {}

    public function index(Request $request): View
    {
        $health = $this->health->report();
        $sources = $health['sources'];
        $selected = $request->filled('source')
            ? $sources->first(fn (array $item) => $item['source']->slug === $request->string('source')->toString())
            : $sources->first();
        $selectedSource = $selected['source'] ?? null;

        return view('admin.growth.page', [
            'partial' => 'admin.growth.data-sources.index',
            'title' => 'منابع داده رشد',
            'health' => $health,
            'sources' => $sources,
            'selectedHealth' => $selected,
            'selectedSource' => $selectedSource,
            'mappings' => $selectedSource?->mappings()->orderByDesc('is_primary')->orderBy('fallback_order')->get() ?? collect(),
            'rawRecords' => $selectedSource?->rawRecords()->latest('received_at')->limit(15)->get() ?? collect(),
            'importSources' => Schema::hasTable('growth_data_sources')
                ? GrowthDataSource::where('is_active', true)->where('ingestion_method', 'csv')->orderBy('priority')->get()
                : collect(),
            'sourceTypes' => self::SOURCE_TYPES,
            'ingestionMethods' => self::INGESTION_METHODS,
            'channelLabels' => self::CHANNELS,
            'metricLabels' => self::METRICS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_type' => ['required', Rule::in(array_keys(self::SOURCE_TYPES))],
            'channel' => ['nullable', Rule::in(array_keys(self::CHANNELS))],
            'ingestion_method' => ['required', Rule::in(array_keys(self::INGESTION_METHODS))],
            'data_types' => ['required', 'array', 'min:1'],
            'data_types.*' => [Rule::in(array_keys(self::METRICS))],
            'endpoint_url' => ['nullable', 'url:http,https', 'max:2000'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'webhook_event' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slugBase = Str::slug($data['name']) ?: Str::lower(Str::random(10));
        $slug = $slugBase;
        while (GrowthDataSource::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.Str::lower(Str::random(5));
        }

        $source = GrowthDataSource::create([
            'name' => $data['name'],
            'slug' => $slug,
            'source_type' => $data['source_type'],
            'channel' => $data['channel'] ?? 'all',
            'ingestion_method' => $data['ingestion_method'],
            'data_types' => array_values($data['data_types']),
            'config' => array_filter([
                'endpoint_url' => $data['endpoint_url'] ?? null,
                'api_key' => $data['api_key'] ?? null,
                'webhook_event' => $data['webhook_event'] ?? null,
            ]),
            'connection_status' => 'active',
            'health_status' => 'idle',
            'is_active' => $request->boolean('is_active', true),
            'priority' => $data['priority'] ?? 100,
            'created_by' => $request->user('admin')?->id,
        ]);

        return redirect()->route('admin.growth.data-sources.index', ['source' => $source->slug])
            ->with('success', 'منبع داده ساخته شد. حالا می‌توانید نگاشت فیلدهای آن را مشخص کنید.');
    }

    public function update(Request $request, GrowthDataSource $growthDataSource): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'endpoint_url' => ['nullable', 'url:http,https', 'max:2000'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'webhook_event' => ['nullable', 'string', 'max:255'],
        ]);
        $config = $growthDataSource->config ?? [];
        $config['endpoint_url'] = $data['endpoint_url'] ?? null;
        $config['webhook_event'] = $data['webhook_event'] ?? null;
        if (! empty($data['api_key'])) {
            $config['api_key'] = $data['api_key'];
        }

        $growthDataSource->update([
            'name' => $data['name'],
            'priority' => $data['priority'],
            'config' => array_filter($config),
        ]);

        return back()->with('success', 'تنظیمات منبع ذخیره شد.');
    }

    public function toggle(GrowthDataSource $growthDataSource): RedirectResponse
    {
        if ($growthDataSource->is_system) {
            return back()->withErrors(['source' => 'منابع داخلی و رهگیری اصلی وطن برای حفظ یکپارچگی داده قابل غیرفعال‌کردن نیستند.']);
        }

        $growthDataSource->update([
            'is_active' => ! $growthDataSource->is_active,
            'health_status' => $growthDataSource->is_active ? 'inactive' : 'idle',
        ]);

        return back()->with('success', $growthDataSource->is_active ? 'منبع فعال شد.' : 'منبع غیرفعال شد.');
    }

    public function storeMapping(Request $request, GrowthDataSource $growthDataSource): RedirectResponse
    {
        $data = $request->validate([
            'source_field' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'growth_metric' => ['required', Rule::in(array_keys(self::METRICS))],
            'transform' => ['required', Rule::in(['integer', 'text', 'boolean', 'lowercase'])],
            'is_primary' => ['nullable', 'boolean'],
            'fallback_order' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        $isPrimary = $request->boolean('is_primary');
        if ($isPrimary) {
            GrowthDataMapping::where('growth_metric', $data['growth_metric'])
                ->whereHas('source', fn ($query) => $query->where('channel', $growthDataSource->channel))
                ->update(['is_primary' => false]);
        }

        GrowthDataMapping::updateOrCreate(
            ['growth_data_source_id' => $growthDataSource->id, 'source_field' => $data['source_field']],
            [
                'growth_metric' => $data['growth_metric'],
                'transform' => $data['transform'],
                'is_primary' => $isPrimary,
                'fallback_order' => $data['fallback_order'],
                'is_active' => true,
            ]
        );

        return back()->with('success', 'نگاشت داده ذخیره شد.');
    }

    public function destroyMapping(GrowthDataSource $growthDataSource, GrowthDataMapping $growthDataMapping): RedirectResponse
    {
        abort_unless($growthDataMapping->growth_data_source_id === $growthDataSource->id, 404);
        $growthDataMapping->delete();

        return back()->with('success', 'نگاشت حذف شد.');
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'growth_data_source_id' => ['required', 'exists:growth_data_sources,id'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'metric_date' => ['nullable', 'date'],
        ]);
        $source = GrowthDataSource::where('is_active', true)->findOrFail($data['growth_data_source_id']);
        abort_unless($source->ingestion_method === 'csv', 422, 'این منبع برای ورود فایل سی‌اس‌وی تنظیم نشده است.');

        $handle = fopen($request->file('file')->getRealPath(), 'rb');
        abort_unless($handle !== false, 422, 'فایل قابل خواندن نیست.');
        $firstLine = fgets($handle) ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);
        $headers = array_map([$this, 'normalizeHeader'], fgetcsv($handle, 0, $delimiter) ?: []);
        $imported = 0;
        $failed = 0;
        $rowNumber = 1;

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false && $rowNumber < 2001) {
            $rowNumber++;
            $values = array_pad($values, count($headers), null);
            $row = array_combine($headers, array_slice($values, 0, count($headers))) ?: [];
            $content = ! empty($row['content_id'])
                ? GrowthContent::find((int) $row['content_id'])
                : GrowthContent::where('external_id', $row['external_id'] ?? '')->first();

            if (! $content) {
                GrowthRawRecord::create([
                    'growth_data_source_id' => $source->id,
                    'external_id' => $row['external_id'] ?? null,
                    'record_type' => 'content_metrics',
                    'payload' => $row,
                    'normalization_status' => 'failed',
                    'error_message' => "محتوای ردیف {$rowNumber} پیدا نشد",
                    'received_at' => now(),
                ]);
                $failed++;
                continue;
            }

            try {
                $this->ingestion->recordContentMetrics(
                    $content,
                    $source,
                    $row,
                    $row['metric_date'] ?? $row['date'] ?? ($data['metric_date'] ?? null),
                    $request->user('admin')?->id,
                    'csv',
                    $row['record_id'] ?? $row['external_id'] ?? null,
                );
                $imported++;
            } catch (\Throwable) {
                $failed++;
            }
        }
        fclose($handle);

        return back()->with('success', "{$imported} ردیف وارد شد و {$failed} ردیف نیازمند بررسی است.");
    }

    public function clearError(GrowthDataSource $growthDataSource): RedirectResponse
    {
        $growthDataSource->update(['last_error' => null, 'health_status' => 'idle', 'connection_status' => 'active']);

        return back()->with('success', 'خطای ثبت‌شده پاک شد؛ وضعیت منبع دوباره ارزیابی می‌شود.');
    }

    private function normalizeHeader(string $header): string
    {
        return Str::lower(trim(str_replace("\xEF\xBB\xBF", '', $header)));
    }
}
