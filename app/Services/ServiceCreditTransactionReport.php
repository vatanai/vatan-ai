<?php

namespace App\Services;

use App\Models\AiProviderRequest;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\UserGalleryItem;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * گزارش یکپارچهٔ مصرف سرویس‌ها با صفحه‌بندی واقعی در پایگاه داده.
 *
 * هر سفارش دقیقاً یک ساخت است. تلاش‌های متعدد سرویس‌دهنده در زیرکوئری تجمیع
 * می‌شوند و UNION ALL فقط ردیف‌های هم‌شکل را برای فیلتر و paginate به دیتابیس می‌دهد.
 */
class ServiceCreditTransactionReport
{
    public function __construct(private ExchangeRateService $exchangeRate) {}

    public function build(Request $request, bool $withMedia = true): array
    {
        $exchange = $this->exchangeRate->usdToIrr();
        $rateIrr = (float) ($exchange['rate'] ?? 0);
        $base = $this->baseQuery($rateIrr);
        $filtered = $this->filteredQuery(clone $base, $request);
        $perPage = in_array((int) $request->input('per_page', 20), [20, 50, 100], true)
            ? (int) $request->input('per_page', 20)
            : 20;

        $paginator = (clone $filtered)
            ->orderByDesc('occurred_at')
            ->orderByDesc('source_record_id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1)))
            ->withPath($request->url())
            ->appends($request->query());

        $page = collect($paginator->items())->map(fn ($row): array => $this->normalizeRow($row));
        $paginator->setCollection($withMedia ? $this->hydrateMedia($page) : $page);

        $timeline = (clone $filtered)->orderByDesc('occurred_at')->limit(12)->get()
            ->map(fn ($row): array => $this->normalizeRow($row));

        return [
            'transactions' => $paginator,
            'summary' => $this->summaryFromQuery(clone $filtered),
            'exchange' => $exchange,
            'timeline' => $timeline,
            'providerStats' => $this->providerStatsFromQuery(clone $filtered),
            'providers' => $this->providerOptions(clone $base, $request),
            'models' => $this->modelOptions(clone $base, $request),
            'sourceOptions' => [
                'user' => 'ساخت کاربر',
                'lab' => 'آزمایشگاه',
                'ledger' => 'تراکنش مالی سرویس',
            ],
            'statusOptions' => [
                'completed' => 'موفق',
                'processing' => 'در حال پردازش',
                'queued' => 'در صف',
                'failed' => 'ناموفق',
                'cancelled' => 'لغوشده',
                'usage' => 'مصرف',
                'charge' => 'شارژ',
                'refund' => 'بازگشت وجه',
                'adjustment' => 'اصلاح',
            ],
        ];
    }

    public function latest(int $limit, float $rateIrr): Collection
    {
        return DB::query()->fromSub($this->baseQuery($rateIrr), 'report_rows')
            ->orderByDesc('occurred_at')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn ($row): array => $this->normalizeRow($row));
    }

    /**
     * داده‌های سبک مرکز اعتبار؛ بدون صفحه‌بندی، گزینه‌های فیلتر و بارگذاری رسانه.
     */
    public function overviewMetrics(float $rateIrr, int $timelineLimit = 8): array
    {
        $rows = DB::query()->fromSub($this->baseQuery($rateIrr), 'overview_rows');

        return [
            'timeline' => (clone $rows)
                ->orderByDesc('occurred_at')
                ->limit(max(1, $timelineLimit))
                ->get()
                ->map(fn ($row): array => $this->normalizeRow($row)),
            'providerStats' => $this->providerStatsFromQuery(clone $rows),
            'activitySummary' => $this->summaryFromQuery(clone $rows),
        ];
    }

    private function baseQuery(float $rateIrr): Builder
    {
        $safeRate = $rateIrr > 0 ? $rateIrr : 1;
        $requestStats = DB::table('ai_provider_requests')
            ->select('order_id')
            ->selectRaw('MAX(id) AS latest_request_id')
            ->selectRaw('COUNT(*) AS request_count')
            ->selectRaw('SUM(CASE WHEN actual_cost_usd > 0 THEN actual_cost_usd ELSE 0 END) AS actual_cost_usd')
            ->whereNotNull('order_id')
            ->groupBy('order_id');

        $orders = DB::table('orders')
            ->leftJoinSub($requestStats, 'request_stats', fn ($join) => $join->on('request_stats.order_id', '=', 'orders.id'))
            ->leftJoin('ai_provider_requests as latest_request', 'latest_request.id', '=', 'request_stats.latest_request_id')
            ->leftJoin('ai_models', 'ai_models.id', '=', 'latest_request.ai_model_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('products', 'products.id', '=', 'orders.product_id')
            ->selectRaw("'order' AS source_entity")
            ->addSelect([
                'orders.id as source_record_id', 'orders.id as order_id', 'orders.build_uuid',
                'orders.user_id', 'orders.product_id', 'latest_request.id as request_id',
            ])
            ->selectRaw('NULL AS detail_record_id')
            ->selectRaw("'user' AS source_key, 'user' AS actor_type")
            ->addSelect([
                'users.name as user_name', 'users.last_name as user_last_name',
                'users.email as user_email', 'users.phone as user_phone',
                'products.name_fa as product_name_fa', 'products.name_en as product_name_en',
                'orders.order_number',
            ])
            ->selectRaw("COALESCE(latest_request.provider, orders.ai_provider, 'ثبت نشده') AS provider")
            ->selectRaw("COALESCE(ai_models.name, orders.ai_model, 'مدل نامشخص') AS model")
            ->selectRaw("CASE WHEN orders.processing_status = 'success' THEN 'completed' ELSE orders.processing_status END AS status_key")
            ->selectRaw('NULLIF(request_stats.actual_cost_usd, 0) AS amount_usd')
            ->selectRaw('CASE WHEN request_stats.actual_cost_usd > 0 THEN request_stats.actual_cost_usd * ? / 10 ELSE NULL END AS amount_toman', [$safeRate])
            ->addSelect(['orders.final_credits as credits'])
            ->selectRaw('COALESCE(orders.completed_at, latest_request.completed_at, latest_request.submitted_at, orders.created_at) AS occurred_at')
            ->selectRaw('COALESCE(latest_request.external_request_id, orders.order_number) AS reference')
            ->selectRaw('NULL AS note')
            ->addSelect(['orders.error_message as error'])
            ->selectRaw('CASE WHEN orders.processing_duration_ms IS NOT NULL THEN orders.processing_duration_ms / 1000.0 ELSE NULL END AS latency_seconds')
            ->selectRaw('COALESCE(request_stats.request_count, orders.attempts, 0) AS retries')
            ->selectRaw("CASE WHEN orders.processing_status IN ('completed', 'success') THEN 1 ELSE 0 END AS is_success")
            ->selectRaw('1 AS is_cost')
            ->selectRaw("CASE WHEN products.media_type = 'video' OR products.output_type = 'video' THEN 'video' ELSE 'image' END AS media_type");

        $orphanRequests = DB::table('ai_provider_requests')
            ->leftJoin('ai_models', 'ai_models.id', '=', 'ai_provider_requests.ai_model_id')
            ->whereNull('ai_provider_requests.order_id')
            ->selectRaw("'provider_request' AS source_entity")
            ->addSelect([
                'ai_provider_requests.id as source_record_id', 'ai_provider_requests.order_id', 'ai_provider_requests.build_uuid',
            ])
            ->selectRaw('NULL AS user_id, NULL AS product_id')
            ->addSelect(['ai_provider_requests.id as request_id'])
            ->selectRaw('NULL AS detail_record_id')
            ->selectRaw("'user' AS source_key, 'user' AS actor_type")
            ->selectRaw('NULL AS user_name, NULL AS user_last_name, NULL AS user_email, NULL AS user_phone')
            ->selectRaw('NULL AS product_name_fa, NULL AS product_name_en, NULL AS order_number')
            ->addSelect(['ai_provider_requests.provider'])
            ->selectRaw("COALESCE(ai_models.name, 'مدل نامشخص') AS model")
            ->selectRaw("CASE WHEN ai_provider_requests.status = 'success' THEN 'completed' ELSE ai_provider_requests.status END AS status_key")
            ->selectRaw('NULLIF(ai_provider_requests.actual_cost_usd, 0) AS amount_usd')
            ->selectRaw('CASE WHEN ai_provider_requests.actual_cost_usd > 0 THEN ai_provider_requests.actual_cost_usd * ? / 10 ELSE NULL END AS amount_toman', [$safeRate])
            ->selectRaw('NULL AS credits')
            ->selectRaw('COALESCE(ai_provider_requests.completed_at, ai_provider_requests.submitted_at, ai_provider_requests.created_at) AS occurred_at')
            ->addSelect(['ai_provider_requests.external_request_id as reference'])
            ->selectRaw("'درخواست بدون سفارش متصل' AS note")
            ->addSelect(['ai_provider_requests.error_message as error'])
            ->selectRaw('NULL AS latency_seconds, 1 AS retries')
            ->selectRaw("CASE WHEN ai_provider_requests.status IN ('completed', 'success') THEN 1 ELSE 0 END AS is_success")
            ->selectRaw('1 AS is_cost')
            ->selectRaw("'image' AS media_type");

        $legacyImages = DB::table('generated_images')
            ->leftJoin('users', 'users.id', '=', 'generated_images.user_id')
            ->leftJoin('products', 'products.id', '=', 'generated_images.product_id')
            ->whereNull('generated_images.order_id')
            ->whereNull('generated_images.ai_provider_request_id')
            ->selectRaw("'generated_image' AS source_entity")
            ->addSelect([
                'generated_images.id as source_record_id', 'generated_images.order_id', 'generated_images.build_uuid',
                'generated_images.user_id', 'generated_images.product_id',
            ])
            ->selectRaw('NULL AS request_id')
            ->selectRaw('NULL AS detail_record_id')
            ->selectRaw("'user' AS source_key, 'user' AS actor_type")
            ->addSelect([
                'users.name as user_name', 'users.last_name as user_last_name',
                'users.email as user_email', 'users.phone as user_phone',
                'products.name_fa as product_name_fa', 'products.name_en as product_name_en',
            ])
            ->selectRaw('NULL AS order_number')
            ->selectRaw("'ثبت نشده' AS provider, 'ثبت نشده' AS model, 'completed' AS status_key")
            ->selectRaw('NULLIF(generated_images.cost, 0) AS amount_usd')
            ->selectRaw('CASE WHEN generated_images.cost > 0 THEN generated_images.cost * ? / 10 ELSE NULL END AS amount_toman', [$safeRate])
            ->selectRaw('NULL AS credits')
            ->addSelect(['generated_images.created_at as occurred_at'])
            ->selectRaw('NULL AS reference')
            ->selectRaw("'خروجی قدیمی بدون سفارش متصل' AS note")
            ->selectRaw('NULL AS error, NULL AS latency_seconds, NULL AS retries, 1 AS is_success, 1 AS is_cost')
            ->selectRaw("'image' AS media_type");

        $legacyVideos = DB::table('generated_videos')
            ->leftJoin('users', 'users.id', '=', 'generated_videos.user_id')
            ->leftJoin('products', 'products.id', '=', 'generated_videos.product_id')
            ->whereNull('generated_videos.order_id')
            ->whereNull('generated_videos.ai_provider_request_id')
            ->selectRaw("'generated_video' AS source_entity")
            ->addSelect([
                'generated_videos.id as source_record_id', 'generated_videos.order_id', 'generated_videos.build_uuid',
                'generated_videos.user_id', 'generated_videos.product_id',
            ])
            ->selectRaw('NULL AS request_id')
            ->selectRaw('NULL AS detail_record_id')
            ->selectRaw("'user' AS source_key, 'user' AS actor_type")
            ->addSelect([
                'users.name as user_name', 'users.last_name as user_last_name',
                'users.email as user_email', 'users.phone as user_phone',
                'products.name_fa as product_name_fa', 'products.name_en as product_name_en',
            ])
            ->selectRaw('NULL AS order_number')
            ->selectRaw("'ثبت نشده' AS provider, 'ثبت نشده' AS model")
            ->addSelect(['generated_videos.status as status_key'])
            ->selectRaw('NULLIF(COALESCE(generated_videos.actual_cost_usd, generated_videos.cost), 0) AS amount_usd')
            ->selectRaw('CASE WHEN COALESCE(generated_videos.actual_cost_usd, generated_videos.cost) > 0 THEN COALESCE(generated_videos.actual_cost_usd, generated_videos.cost) * ? / 10 ELSE NULL END AS amount_toman', [$safeRate])
            ->addSelect(['generated_videos.credits_settled as credits'])
            ->selectRaw('COALESCE(generated_videos.completed_at, generated_videos.created_at) AS occurred_at')
            ->selectRaw('NULL AS reference')
            ->selectRaw("'ویدیوی بدون سفارش متصل' AS note")
            ->addSelect(['generated_videos.error_message as error'])
            ->selectRaw('NULL AS latency_seconds')
            ->addSelect(['generated_videos.retry_count as retries'])
            ->selectRaw("CASE WHEN generated_videos.status = 'completed' THEN 1 ELSE 0 END AS is_success")
            ->selectRaw('1 AS is_cost')
            ->selectRaw("'video' AS media_type");

        $labRuns = DB::table('lab_runs')
            ->join('lab_experiments', 'lab_experiments.id', '=', 'lab_runs.lab_experiment_id')
            ->leftJoin('admins', 'admins.id', '=', 'lab_experiments.admin_id')
            ->leftJoin('products', 'products.id', '=', 'lab_experiments.product_id')
            ->leftJoin('ai_models', 'ai_models.id', '=', 'lab_runs.ai_model_id')
            ->selectRaw("'lab_run' AS source_entity")
            ->addSelect(['lab_runs.id as source_record_id'])
            ->selectRaw('NULL AS order_id')
            ->addSelect(['lab_runs.build_uuid'])
            ->selectRaw('NULL AS user_id')
            ->addSelect(['lab_experiments.product_id'])
            ->selectRaw('NULL AS request_id')
            ->addSelect(['lab_experiments.id as detail_record_id'])
            ->selectRaw("'lab' AS source_key, 'admin' AS actor_type")
            ->addSelect(['admins.name as user_name'])
            ->selectRaw('NULL AS user_last_name')
            ->addSelect(['admins.email as user_email'])
            ->selectRaw('NULL AS user_phone')
            ->addSelect(['products.name_fa as product_name_fa', 'products.name_en as product_name_en'])
            ->selectRaw('NULL AS order_number')
            ->selectRaw("COALESCE(lab_runs.provider_name_snapshot, lab_runs.provider, 'نامشخص') AS provider")
            ->selectRaw("COALESCE(lab_runs.model_name_snapshot, ai_models.name, lab_runs.alias, lab_runs.model_id) AS model")
            ->addSelect(['lab_runs.status as status_key'])
            ->selectRaw('NULLIF(lab_runs.actual_cost_usd, 0) AS amount_usd')
            ->selectRaw('COALESCE(NULLIF(lab_runs.actual_cost_toman, 0), NULLIF(lab_runs.actual_cost_usd, 0) * ? / 10) AS amount_toman', [$safeRate])
            ->selectRaw('NULL AS credits')
            ->selectRaw('COALESCE(lab_runs.completed_at, lab_runs.started_at, lab_runs.created_at) AS occurred_at')
            ->addSelect(['lab_experiments.report_code as reference'])
            ->addSelect(['lab_runs.notes as note', 'lab_runs.error_message as error'])
            ->selectRaw('COALESCE(lab_runs.build_seconds, lab_runs.duration_ms / 1000.0) AS latency_seconds')
            ->addSelect(['lab_runs.retry_count as retries'])
            ->selectRaw("CASE WHEN lab_runs.status = 'completed' THEN 1 ELSE 0 END AS is_success")
            ->selectRaw('1 AS is_cost')
            ->selectRaw("'image' AS media_type");

        $ledger = DB::table('service_credit_transactions')
            ->join('service_credit_accounts', 'service_credit_accounts.id', '=', 'service_credit_transactions.service_credit_account_id')
            ->selectRaw("'ledger' AS source_entity")
            ->addSelect(['service_credit_transactions.id as source_record_id'])
            ->selectRaw('NULL AS order_id, NULL AS build_uuid, NULL AS user_id, NULL AS product_id, NULL AS request_id, NULL AS detail_record_id')
            ->selectRaw("'ledger' AS source_key, 'system' AS actor_type")
            ->selectRaw('NULL AS user_name, NULL AS user_last_name, NULL AS user_email, NULL AS user_phone')
            ->selectRaw('NULL AS product_name_fa, NULL AS product_name_en, NULL AS order_number')
            ->addSelect(['service_credit_accounts.name as provider'])
            ->selectRaw('NULL AS model')
            ->addSelect(['service_credit_transactions.type as status_key'])
            ->selectRaw("CASE WHEN service_credit_accounts.currency = 'USD' THEN service_credit_transactions.amount ELSE service_credit_transactions.amount / ? END AS amount_usd", [$safeRate])
            ->selectRaw("CASE WHEN service_credit_accounts.currency = 'USD' THEN service_credit_transactions.amount * ? / 10 ELSE service_credit_transactions.amount / 10 END AS amount_toman", [$safeRate])
            ->selectRaw('NULL AS credits')
            ->addSelect(['service_credit_transactions.occurred_at'])
            ->addSelect(['service_credit_transactions.reference', 'service_credit_transactions.note'])
            ->selectRaw('NULL AS error, NULL AS latency_seconds, NULL AS retries')
            ->selectRaw("CASE WHEN service_credit_transactions.type = 'usage' THEN 0 ELSE 1 END AS is_success")
            ->selectRaw("CASE WHEN service_credit_transactions.type = 'usage' THEN 1 ELSE 0 END AS is_cost")
            ->selectRaw("'none' AS media_type");

        return $orders
            ->unionAll($orphanRequests)
            ->unionAll($legacyImages)
            ->unionAll($legacyVideos)
            ->unionAll($labRuns)
            ->unionAll($ledger);
    }

    private function filteredQuery(Builder $base, Request $request): Builder
    {
        $query = DB::query()->fromSub($base, 'report_rows');
        $this->applyDateRange($query, $request);

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                foreach (['user_name', 'user_last_name', 'user_email', 'user_phone', 'product_name_fa', 'product_name_en', 'provider', 'model', 'order_number', 'reference', 'error', 'note', 'build_uuid'] as $column) {
                    $searchQuery->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }
        if ($source = trim((string) $request->input('source', ''))) {
            $query->where('source_key', $source);
        }
        if ($provider = trim((string) $request->input('provider', ''))) {
            $query->whereRaw('LOWER(provider) = ?', [Str::lower($provider)]);
        }
        if ($model = trim((string) $request->input('model', ''))) {
            $query->whereRaw('LOWER(model) = ?', [Str::lower($model)]);
        }
        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status_key', $status);
        }

        return $query;
    }

    private function applyDateRange(Builder $query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }
    }

    private function summaryFromQuery(Builder $filtered): array
    {
        $summary = DB::query()->fromSub($filtered, 'summary_rows')->selectRaw(
            'COUNT(*) AS aggregate_count,
             SUM(CASE WHEN is_success = 1 THEN 1 ELSE 0 END) AS success_count,
             SUM(CASE WHEN status_key = ? THEN 1 ELSE 0 END) AS failed_count,
             SUM(CASE WHEN status_key IN (?, ?) THEN 1 ELSE 0 END) AS processing_count,
             SUM(CASE WHEN is_cost = 1 THEN COALESCE(amount_usd, 0) ELSE 0 END) AS usd_total,
             SUM(CASE WHEN is_cost = 1 THEN COALESCE(amount_toman, 0) ELSE 0 END) AS toman_total',
            ['failed', 'queued', 'processing'],
        )->first();

        return [
            'count' => (int) ($summary->aggregate_count ?? 0),
            'success' => (int) ($summary->success_count ?? 0),
            'failed' => (int) ($summary->failed_count ?? 0),
            'processing' => (int) ($summary->processing_count ?? 0),
            'usd' => (float) ($summary->usd_total ?? 0),
            'toman' => (float) ($summary->toman_total ?? 0),
        ];
    }

    private function providerStatsFromQuery(Builder $filtered): Collection
    {
        return DB::query()->fromSub($filtered, 'provider_rows')
            ->selectRaw('LOWER(provider) AS provider_key, MAX(provider) AS provider_label, COUNT(*) AS aggregate_count')
            ->selectRaw('SUM(CASE WHEN is_success = 1 THEN 1 ELSE 0 END) AS success_count')
            ->selectRaw("SUM(CASE WHEN status_key = 'failed' THEN 1 ELSE 0 END) AS failed_count")
            ->selectRaw('SUM(CASE WHEN is_cost = 1 THEN COALESCE(amount_usd, 0) ELSE 0 END) AS usd_total')
            ->selectRaw('SUM(CASE WHEN is_cost = 1 THEN COALESCE(amount_toman, 0) ELSE 0 END) AS toman_total')
            ->selectRaw('MAX(occurred_at) AS latest_at')
            ->groupByRaw('LOWER(provider)')
            ->orderByDesc('aggregate_count')
            ->get()
            ->map(function ($row): array {
                $latest = $row->latest_at ? Carbon::parse($row->latest_at) : null;

                return [
                    'key' => $row->provider_key ?: 'unknown',
                    'label' => $row->provider_label ?: 'نامشخص',
                    'count' => (int) $row->aggregate_count,
                    'success' => (int) $row->success_count,
                    'failed' => (int) $row->failed_count,
                    'usd' => (float) $row->usd_total,
                    'toman' => (float) $row->toman_total,
                    'latest_at' => $latest ? Jalali::formatNumeric($latest) : '—',
                ];
            });
    }

    private function providerOptions(Builder $base, Request $request): Collection
    {
        $query = DB::query()->fromSub($base, 'provider_options');
        $this->applyDateRange($query, $request);

        return $query->whereNotNull('provider')
            ->selectRaw('LOWER(provider) AS provider_key, MAX(provider) AS provider_label')
            ->groupByRaw('LOWER(provider)')
            ->orderBy('provider_label')
            ->get()
            ->map(fn ($provider): array => ['key' => $provider->provider_key, 'label' => $provider->provider_label]);
    }

    private function modelOptions(Builder $base, Request $request): Collection
    {
        $query = DB::query()->fromSub($base, 'model_options');
        $this->applyDateRange($query, $request);

        return $query->whereNotNull('model')
            ->whereNotIn('model', ['', 'ثبت نشده', 'مدل نامشخص'])
            ->selectRaw('LOWER(model) AS model_key, MAX(model) AS model_label')
            ->groupByRaw('LOWER(model)')
            ->orderBy('model_label')
            ->get()
            ->map(fn ($model): array => ['key' => $model->model_key, 'label' => $model->model_label]);
    }

    private function normalizeRow(object|array $record): array
    {
        $row = (array) $record;
        $date = ! empty($row['occurred_at'])
            ? Carbon::parse($row['occurred_at'])->timezone(config('app.display_timezone', 'Asia/Tehran'))
            : null;
        $entity = (string) $row['source_entity'];
        $recordId = (int) $row['source_record_id'];
        $id = match ($entity) {
            'order' => 'order-'.$recordId,
            'provider_request' => 'request-'.$recordId,
            'generated_image' => 'image-'.$recordId,
            'generated_video' => 'video-'.$recordId,
            'lab_run' => 'lab-'.$recordId,
            default => 'ledger-'.$recordId,
        };
        $userName = trim(implode(' ', array_filter([$row['user_name'] ?? null, $row['user_last_name'] ?? null])));
        $sourceKey = (string) $row['source_key'];
        $actorType = (string) $row['actor_type'];

        return [
            'id' => $id,
            'source_entity' => $entity,
            'source_record_id' => $recordId,
            'order_id' => $row['order_id'] ? (int) $row['order_id'] : null,
            'build_uuid' => $row['build_uuid'] ?: null,
            'build_reference' => $row['build_uuid'] ? Str::upper(substr((string) $row['build_uuid'], 0, 8)) : null,
            'user_id' => $row['user_id'] ? (int) $row['user_id'] : null,
            'product_id' => $row['product_id'] ? (int) $row['product_id'] : null,
            'request_id' => $row['request_id'] ? (int) $row['request_id'] : null,
            'source_key' => $sourceKey,
            'source_label' => match ($sourceKey) {
                'user' => 'ساخت کاربر', 'lab' => 'آزمایشگاه', default => 'تراکنش مالی سرویس',
            },
            'actor_type' => $actorType,
            'actor_label' => match ($actorType) {
                'user' => 'کاربر', 'admin' => 'مدیر آزمایش', default => 'سیستم',
            },
            'user_name' => $this->displayText($userName) ?: '—',
            'user_contact' => $this->displayText(($row['user_email'] ?? null) ?: ($row['user_phone'] ?? null)) ?: '—',
            'product_name' => $this->displayText(($row['product_name_fa'] ?? null) ?: ($row['product_name_en'] ?? null)) ?: '—',
            'provider' => $this->displayText($row['provider'] ?? null) ?: '—',
            'provider_key' => Str::lower((string) (($row['provider'] ?? null) ?: 'unknown')),
            'model' => $this->displayText($row['model'] ?? null) ?: '—',
            'status_key' => (string) (($row['status_key'] ?? null) ?: 'unknown'),
            'status_label' => $this->statusLabel($row['status_key'] ?? null),
            'order_number' => $this->displayText($row['order_number'] ?? null),
            'amount_usd' => $row['amount_usd'] !== null ? (float) $row['amount_usd'] : null,
            'amount_toman' => $row['amount_toman'] !== null ? (float) $row['amount_toman'] : null,
            'credits' => $row['credits'] !== null ? (float) $row['credits'] : null,
            'occurred_at' => $date,
            'date_jalali' => $date ? Jalali::formatNumeric($date) : '—',
            'date_gregorian' => $date?->format('Y/m/d H:i') ?: '—',
            'reference' => $this->displayText($row['reference'] ?? null) ?: match ($entity) {
                'generated_image' => 'IMG-'.$recordId,
                'generated_video' => 'VID-'.$recordId,
                'lab_run' => 'LAB-'.($row['detail_record_id'] ?? $recordId),
                default => '—',
            },
            'note' => $this->displayText($row['note'] ?? null),
            'error' => $this->errorText($row['error'] ?? null),
            'latency_seconds' => $row['latency_seconds'] !== null ? (float) $row['latency_seconds'] : null,
            'retries' => $row['retries'] !== null ? (int) $row['retries'] : null,
            'is_success' => (bool) $row['is_success'],
            'media_type' => ($row['media_type'] ?? null) ?: 'image',
            'media_label' => ($row['media_type'] ?? null) === 'video' ? 'ویدیو' : 'خروجی',
            'input_media' => [],
            'output_urls' => [],
            'output_preview_url' => null,
            'media_missing' => false,
            'detail_url' => $entity === 'order'
                ? route('admin.orders.show', $recordId)
                : ($entity === 'lab_run' && $row['detail_record_id'] ? route('admin.lab.show', $row['detail_record_id']) : null),
            'user_url' => $row['user_id'] ? route('admin.users.gallery.show', $row['user_id']) : null,
            'order_url' => $row['order_id'] ? route('admin.orders.show', $row['order_id']) : null,
            'finance_url' => null,
        ];
    }

    /** فقط رسانه‌های ردیف‌های صفحهٔ فعلی خوانده می‌شوند. */
    private function hydrateMedia(Collection $page): Collection
    {
        $orderIds = $page->pluck('order_id')->filter()->unique()->values();
        $orphanRequestIds = $page->where('source_entity', 'provider_request')->pluck('source_record_id')->filter()->values();
        $legacyImageIds = $page->where('source_entity', 'generated_image')->pluck('source_record_id')->filter()->values();
        $legacyVideoIds = $page->where('source_entity', 'generated_video')->pluck('source_record_id')->filter()->values();
        $orders = Order::query()->whereIn('id', $orderIds)->get()->keyBy('id');
        $providerRequests = collect();
        if ($orderIds->isNotEmpty()) {
            $providerRequests = $providerRequests->merge(AiProviderRequest::query()->whereIn('order_id', $orderIds)->get());
        }
        if ($orphanRequestIds->isNotEmpty()) {
            $providerRequests = $providerRequests->merge(AiProviderRequest::query()->whereIn('id', $orphanRequestIds)->get());
        }
        $providerRequests = $providerRequests->unique('id')->values();
        $requestIds = $providerRequests->pluck('id');

        $images = collect();
        $videos = collect();
        if ($orderIds->isNotEmpty()) {
            $images = $images->merge(GeneratedImage::query()->whereIn('order_id', $orderIds)->get());
            $videos = $videos->merge(GeneratedVideo::query()->whereIn('order_id', $orderIds)->get());
        }
        if ($requestIds->isNotEmpty()) {
            $images = $images->merge(GeneratedImage::query()->whereIn('ai_provider_request_id', $requestIds)->get());
            $videos = $videos->merge(GeneratedVideo::query()->whereIn('ai_provider_request_id', $requestIds)->get());
        }
        if ($legacyImageIds->isNotEmpty()) {
            $images = $images->merge(GeneratedImage::query()->whereIn('id', $legacyImageIds)->get());
        }
        if ($legacyVideoIds->isNotEmpty()) {
            $videos = $videos->merge(GeneratedVideo::query()->whereIn('id', $legacyVideoIds)->get());
        }
        $images = $images->unique('id')->values();
        $videos = $videos->unique('id')->values();
        $gallery = $orderIds->isNotEmpty()
            ? UserGalleryItem::query()->whereIn('order_id', $orderIds)
                ->whereIn('source_type', ['upload', 'input_image', 'input_text', 'input_video'])
                ->orderByDesc('id')->get()->groupBy('order_id')
            : collect();

        return $page->map(function (array $row) use ($orders, $providerRequests, $images, $videos, $gallery): array {
            $orderId = $row['order_id'];
            $order = $orderId ? $orders->get($orderId) : null;
            $rowRequests = $orderId
                ? $providerRequests->where('order_id', $orderId)
                : $providerRequests->where('id', $row['request_id']);
            $rowRequestIds = $rowRequests->pluck('id')->map(fn ($id) => (int) $id)->all();
            $rowImages = $images->filter(function (GeneratedImage $image) use ($row, $orderId, $rowRequestIds): bool {
                if ($row['source_entity'] === 'generated_image') return (int) $image->id === $row['source_record_id'];
                return ($orderId && (int) $image->order_id === $orderId)
                    || in_array((int) $image->ai_provider_request_id, $rowRequestIds, true);
            })->values();
            $rowVideos = $videos->filter(function (GeneratedVideo $video) use ($row, $orderId, $rowRequestIds): bool {
                if ($row['source_entity'] === 'generated_video') return (int) $video->id === $row['source_record_id'];
                return ($orderId && (int) $video->order_id === $orderId)
                    || in_array((int) $video->ai_provider_request_id, $rowRequestIds, true);
            })->values();

            if ($order) {
                $items = $gallery->get($orderId, collect())->take(6);
                $row['input_media'] = $items->isNotEmpty()
                    ? $this->galleryInputMedia($items, $order)
                    : $this->inputMediaForOrderPayload($order);
            }

            $validImages = $rowImages->map(fn (GeneratedImage $image) => [
                'model' => $image,
                'url' => $this->publicMediaUrl($image->image_path),
            ])->filter(fn (array $item) => filled($item['url']))->values();
            $validVideos = $rowVideos->map(fn (GeneratedVideo $video) => $this->videoUrl($video))->filter()->values();
            $row['media_missing'] = $validImages->count() < $rowImages->count()
                || ($rowVideos->isNotEmpty() && $validVideos->count() < $rowVideos->count());
            $row['output_urls'] = $validImages->pluck('url')->all();

            if ($row['output_urls'] === [] && $validVideos->isNotEmpty()) {
                $row['output_urls'] = $validVideos->all();
                $row['media_type'] = 'video';
                $row['media_label'] = 'ویدیو';
            }
            if ($row['output_urls'] === [] && $order) {
                $row['output_urls'] = $this->orderOutputUrls($order);
            }
            if ($row['output_urls'] === []) {
                $row['output_urls'] = $rowRequests->flatMap(fn (AiProviderRequest $providerRequest) => $this->providerOutputUrls($providerRequest->output_urls))
                    ->unique()->values()->all();
            }

            $previewImage = $validImages->first()['model'] ?? null;
            if ($previewImage instanceof GeneratedImage) {
                $row['output_preview_url'] = route('admin.service-credits.image-thumbnail', $previewImage);
            }
            if ($row['media_missing'] && $row['output_urls'] === []) {
                $row['note'] = trim(implode(' ', array_filter([$row['note'], 'فایل خروجی در فضای ذخیره‌سازی موجود نیست.'])));
            }

            return $row;
        });
    }

    private function galleryInputMedia(Collection $items, Order $order): array
    {
        return $items->map(function (UserGalleryItem $item) use ($order): ?array {
            $mime = strtolower((string) $item->mime_type);
            $type = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'text/') ? 'text' : 'image');
            $disk = Storage::disk($item->disk ?: 'user_gallery');
            if (! $item->original_path || ! $disk->exists($item->original_path)) return null;

            return [
                'type' => $type,
                'url' => $type === 'image'
                    ? route('admin.users.gallery.thumbnail', [$order->user_id, $item->id])
                    : route('admin.users.gallery.original', [$order->user_id, $item->id]),
                'original_url' => route('admin.users.gallery.original', [$order->user_id, $item->id]),
                'preview_url' => $type === 'image' ? route('admin.users.gallery.thumbnail', [$order->user_id, $item->id]) : null,
                'label' => 'ورودی',
                'text' => $type === 'text' ? data_get($item->metadata, 'text') : null,
            ];
        })->filter()->values()->all();
    }

    private function orderOutputUrls(Order $order): array
    {
        return collect((array) $order->output_payload)
            ->map(function ($item): ?string {
                $path = is_string($item) ? $item : (is_array($item) ? ($item['path'] ?? $item['url'] ?? null) : null);
                return $this->publicMediaUrl($path);
            })->filter()->unique()->values()->all();
    }

    private function inputMediaForOrderPayload(Order $order): array
    {
        $payload = (array) $order->input_payload;
        $paths = array_values(array_unique(array_filter(array_merge(
            (array) data_get($payload, 'source_upload_paths', []),
            [data_get($payload, 'source_upload_path')],
        ), fn ($path): bool => is_scalar($path) && filled($path))));
        $media = collect($paths)->values()->map(function ($path, int $index) use ($order): ?array {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return ['type' => 'image', 'url' => (string) $path, 'original_url' => (string) $path, 'preview_url' => (string) $path, 'label' => 'عکس ورودی', 'text' => null];
            }

            $path = ltrim((string) $path, '/');
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            $url = route('admin.service-credits.order-input', [$order, $index]);
            return ['type' => 'image', 'url' => $url, 'original_url' => $url, 'preview_url' => $url, 'label' => 'عکس ورودی', 'text' => null];
        })->filter();

        $inputPrompt = data_get($payload, 'prompt') ?: data_get($payload, 'fields.prompt');
        if (filled($inputPrompt)) {
            $media->prepend([
                'type' => 'text', 'url' => route('admin.orders.show', $order), 'original_url' => route('admin.orders.show', $order),
                'preview_url' => null, 'label' => 'متن ورودی', 'text' => (string) $inputPrompt,
            ]);
        }
        if ($media->isEmpty() && filled(data_get($payload, 'resolved_prompt'))) {
            $media->push([
                'type' => 'text', 'url' => route('admin.orders.show', $order), 'original_url' => route('admin.orders.show', $order),
                'preview_url' => null, 'label' => data_get($payload, 'face_profile_id') ? 'ورودی پروفایل چهره' : 'ورودی تنظیمات ساخت',
                'text' => (string) data_get($payload, 'resolved_prompt'),
            ]);
        }

        $videoPath = data_get($payload, 'source_video_path');
        $videoUrl = $videoPath ? $this->publicMediaUrl((string) $videoPath) : data_get($payload, 'source_video_url');
        if (filled($videoUrl)) {
            $media->push(['type' => 'video', 'url' => $videoUrl, 'original_url' => $videoUrl, 'preview_url' => null, 'label' => 'ویدیوی ورودی', 'text' => null]);
        }

        return $media->take(6)->values()->all();
    }

    private function providerOutputUrls(mixed $payload): array
    {
        return collect((array) $payload)->map(function ($item): ?string {
            $path = is_string($item) ? $item : (is_array($item) ? ($item['url'] ?? $item['path'] ?? null) : null);
            return $this->publicMediaUrl($path);
        })->filter()->values()->all();
    }

    private function publicMediaUrl(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') return null;
        if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path) ? asset('storage/'.$path) : null;
    }

    private function videoUrl(GeneratedVideo $video): ?string
    {
        if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
            return asset('storage/'.ltrim($video->video_path, '/'));
        }

        return filter_var($video->video_url, FILTER_VALIDATE_URL) ? $video->video_url : null;
    }

    private function displayText(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_scalar($value)) return (string) $value;

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    private function errorText(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach (['message', 'error', 'detail'] as $key) {
                if (isset($value[$key]) && is_scalar($value[$key])) return (string) $value[$key];
            }
            return 'خطای سرویس در دریافت جزئیات';
        }

        return $this->displayText($value);
    }

    private function statusLabel(?string $status): string
    {
        return [
            'completed' => 'موفق', 'success' => 'موفق', 'processing' => 'در حال پردازش', 'queued' => 'در صف',
            'failed' => 'ناموفق', 'cancelled' => 'لغوشده', 'usage' => 'مصرف', 'charge' => 'شارژ',
            'refund' => 'بازگشت وجه', 'adjustment' => 'اصلاح', 'review' => 'نیازمند بررسی',
            'expired' => 'منقضی', 'stopped' => 'متوقف‌شده', 'retrying' => 'تلاش مجدد',
        ][$status ?: ''] ?? ($status ?: 'نامشخص');
    }
}
