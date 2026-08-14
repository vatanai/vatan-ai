<?php

namespace App\Services;

use App\Models\AiProviderRequest;
use App\Models\GeneratedImage;
use App\Models\LabRun;
use App\Models\Order;
use App\Models\ServiceCreditTransaction;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * فید یکپارچه مصرف سرویس‌ها.
 *
 * تراکنش‌های موجودی، اجرای آزمایشگاه و اجرای واقعی کاربران در جدول‌های جدا
 * ذخیره می‌شوند؛ این سرویس آن‌ها را با یک شکل مشترک برای گزارش مدیریتی جمع می‌کند.
 */
class ServiceCreditTransactionReport
{
    public function __construct(private ExchangeRateService $exchangeRate) {}

    public function build(Request $request): array
    {
        $exchange = $this->exchangeRate->usdToIrr();
        $rateIrr = (float) ($exchange['rate'] ?? 0);
        $allRows = $this->collectRows($request, $rateIrr);
        $rows = $allRows->collect();

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $needle = Str::lower($search);
            $rows = $rows->filter(function (array $row) use ($needle): bool {
                $haystack = Str::lower(implode(' ', [
                    $row['user_name'], $row['user_contact'], $row['product_name'],
                    $row['provider'], $row['model'], $row['order_number'],
                    $row['reference'], $row['error'], $row['note'],
                ]));
                return Str::contains($haystack, $needle);
            })->values();
        }

        if ($source = trim((string) $request->input('source', ''))) {
            $rows = $rows->where('source_key', $source)->values();
        }
        if ($provider = trim((string) $request->input('provider', ''))) {
            $rows = $rows->where('provider_key', Str::lower($provider))->values();
        }
        if ($status = trim((string) $request->input('status', ''))) {
            $rows = $rows->where('status_key', $status)->values();
        }

        $rows = $rows->sortByDesc(fn (array $row) => $row['occurred_at']?->timestamp ?? 0)->values();
        $summary = $this->summary($rows);
        $perPage = min(50, max(10, (int) $request->input('per_page', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $paginator = new Paginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [
            'transactions' => $paginator,
            'summary' => $summary,
            'exchange' => $exchange,
            'providers' => $allRows->map(fn (array $row) => ['key' => $row['provider_key'], 'label' => $row['provider']])
                ->unique('key')->sortBy('label')->values(),
            'sourceOptions' => [
                'user' => 'ساخت کاربر',
                'lab' => 'آزمایشگاه',
                'order' => 'سفارش بدون جزئیات مدل',
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
        $request = Request::create('/', 'GET', ['per_page' => $limit]);
        return $this->collectRows($request, $rateIrr)
            ->sortByDesc(fn (array $row) => $row['occurred_at']?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }

    private function collectRows(Request $request, float $rateIrr): Collection
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : null;
        $rows = collect();

        $credits = ServiceCreditTransaction::with('account')
            ->when($from, fn ($query) => $query->where('occurred_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('occurred_at', '<=', $to))
            ->latest('occurred_at')->limit(500)->get();
        foreach ($credits as $transaction) {
            $amount = (float) $transaction->amount;
            $isUsd = $transaction->account?->currency === 'USD';
            $rows->push($this->row([
                'id' => 'ledger-' . $transaction->id,
                'source_key' => 'ledger', 'source_label' => 'تراکنش مالی سرویس',
                'provider' => $transaction->account?->name ?: 'سرویس نامشخص',
                'provider_key' => Str::lower((string) ($transaction->account?->slug ?: 'unknown')),
                'model' => null, 'status_key' => $transaction->type,
                'status_label' => $this->statusLabel($transaction->type),
                'user_name' => null, 'user_contact' => null, 'product_name' => null,
                'product_id' => null, 'order_number' => null, 'amount_usd' => $isUsd ? $amount : ($rateIrr > 0 ? $amount / $rateIrr : null),
                'amount_toman' => $isUsd ? $amount * $rateIrr / 10 : $amount / 10,
                'credits' => null, 'occurred_at' => $transaction->occurred_at,
                'reference' => $transaction->reference, 'note' => $transaction->note,
                'error' => null, 'output_urls' => [], 'latency_seconds' => null,
                'retries' => null, 'is_success' => $transaction->type !== 'usage', 'detail_url' => null,
            ]));
        }

        $labRuns = LabRun::with(['experiment.product', 'experiment.admin', 'outputs', 'aiModel'])
            ->when($from, fn ($query) => $query->where('lab_runs.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('lab_runs.created_at', '<=', $to))
            ->latest('lab_runs.created_at')->limit(500)->get();
        foreach ($labRuns as $run) {
            $usd = (float) $run->actual_cost_usd > 0 ? (float) $run->actual_cost_usd : (float) $run->estimated_cost_usd;
            $runRate = (float) ($run->exchange_rate_irr ?: $run->experiment?->exchange_rate_irr ?: $rateIrr);
            $toman = (float) $run->actual_cost_toman > 0 ? (float) $run->actual_cost_toman : ($usd * $runRate / 10);
            $model = $run->model_name_snapshot ?: $run->aiModel?->name ?: $run->alias ?: $run->model_id;
            $admin = $run->experiment?->admin;
            $adminName = $admin?->name ?: $admin?->email ?: 'مدیر سایت ثبت‌نشده';
            $rows->push($this->row([
                'id' => 'lab-' . $run->id, 'source_key' => 'lab', 'source_label' => 'آزمایشگاه',
                'provider' => $run->provider_name_snapshot ?: $run->provider ?: 'نامشخص',
                'provider_key' => Str::lower((string) ($run->provider ?: 'unknown')), 'model' => $model,
                'status_key' => $run->status, 'status_label' => $run->status_label,
                'actor_type' => 'admin', 'actor_label' => 'مدیر آزمایش',
                'user_name' => $adminName, 'user_contact' => $admin?->email ?: $admin?->phone ?: 'اجرای آزمایشگاهی',
                'product_name' => $run->experiment?->product_name_snapshot ?: $run->experiment?->product?->name_fa ?: $run->experiment?->product?->name_en,
                'product_id' => $run->experiment?->product_id, 'order_number' => null,
                'amount_usd' => $usd > 0 ? $usd : null, 'amount_toman' => $toman > 0 ? $toman : null,
                'credits' => null, 'occurred_at' => $run->completed_at ?: $run->started_at ?: $run->created_at,
                'reference' => $run->experiment?->report_code ?: 'LAB-' . $run->experiment?->id,
                'note' => $run->notes, 'error' => $run->error_message,
                'output_urls' => $run->outputs->map(fn ($output) => $output->url)->filter()->values()->all(),
                'latency_seconds' => $run->build_seconds ?: ($run->duration_ms ? round($run->duration_ms / 1000, 1) : null),
                'retries' => (int) $run->retry_count, 'is_success' => $run->status === 'completed',
                'detail_url' => $run->experiment ? route('admin.lab.show', $run->experiment) : null,
            ]));
        }

        $providerRequests = AiProviderRequest::with(['order.user', 'order.product', 'aiModel'])
            ->whereNotNull('order_id')
            ->when($from, fn ($query) => $query->where('ai_provider_requests.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('ai_provider_requests.created_at', '<=', $to))
            ->latest('ai_provider_requests.created_at')->limit(700)->get();
        $requestOrderIds = $providerRequests->pluck('order_id')->filter()->unique();
        foreach ($providerRequests as $providerRequest) {
            $order = $providerRequest->order;
            $usd = (float) $providerRequest->actual_cost_usd > 0
                ? (float) $providerRequest->actual_cost_usd
                : ((float) $providerRequest->estimated_cost_usd > 0 ? (float) $providerRequest->estimated_cost_usd : null);
            $rows->push($this->row([
                'id' => 'request-' . $providerRequest->id, 'source_key' => 'user', 'source_label' => 'ساخت کاربر',
                'provider' => $providerRequest->provider ?: 'نامشخص', 'provider_key' => Str::lower((string) ($providerRequest->provider ?: 'unknown')),
                'model' => $providerRequest->aiModel?->name ?: $order?->ai_model ?: 'مدل نامشخص',
                'status_key' => $providerRequest->status, 'status_label' => $this->statusLabel($providerRequest->status),
                'actor_type' => 'user', 'actor_label' => 'کاربر',
                'user_name' => $this->userName($order?->user), 'user_contact' => $order?->user?->email ?: $order?->user?->phone,
                'product_name' => $order?->product?->name_fa ?: $order?->product?->name_en,
                'product_id' => $order?->product_id, 'order_number' => $order?->order_number,
                'amount_usd' => $usd, 'amount_toman' => $usd !== null && $rateIrr > 0 ? $usd * $rateIrr / 10 : null,
                'credits' => $order?->final_credits, 'occurred_at' => $providerRequest->completed_at ?: $providerRequest->submitted_at ?: $providerRequest->created_at,
                'reference' => $providerRequest->external_request_id, 'note' => null,
                'error' => $providerRequest->error_message, 'output_urls' => (array) $providerRequest->output_urls,
                'latency_seconds' => $providerRequest->submitted_at && $providerRequest->completed_at ? round($providerRequest->submitted_at->diffInMilliseconds($providerRequest->completed_at) / 1000, 1) : null,
                'retries' => $order?->attempts, 'is_success' => $providerRequest->status === 'completed',
                'detail_url' => $order ? route('admin.orders.show', $order) : null,
            ]));
        }

        $orders = Order::with(['user', 'product'])
            ->when($requestOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $requestOrderIds))
            ->when($from, fn ($query) => $query->where('orders.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('orders.created_at', '<=', $to))
            ->latest('orders.created_at')->limit(300)->get();
        foreach ($orders as $order) {
            $rows->push($this->row([
                'id' => 'order-' . $order->id, 'source_key' => 'order', 'source_label' => 'سفارش بدون جزئیات مدل',
                'provider' => $order->ai_provider ?: 'ثبت نشده', 'provider_key' => Str::lower((string) ($order->ai_provider ?: 'unknown')),
                'model' => $order->ai_model, 'status_key' => $order->processing_status ?: $order->status,
                'status_label' => $this->statusLabel($order->processing_status ?: $order->status),
                'actor_type' => 'user', 'actor_label' => 'کاربر',
                'user_name' => $this->userName($order->user), 'user_contact' => $order->user?->email ?: $order->user?->phone,
                'product_name' => $order->product?->name_fa ?: $order->product?->name_en, 'product_id' => $order->product_id,
                'order_number' => $order->order_number, 'amount_usd' => null, 'amount_toman' => null,
                'credits' => $order->final_credits, 'occurred_at' => $order->completed_at ?: $order->created_at,
                'reference' => $order->order_number, 'note' => 'جزئیات هزینه provider برای این سفارش ثبت نشده است.',
                'error' => $order->error_message, 'output_urls' => $this->orderOutputUrls($order),
                'latency_seconds' => $order->processing_duration_ms ? round($order->processing_duration_ms / 1000, 1) : null,
                'retries' => $order->attempts, 'is_success' => $order->processing_status === 'completed',
                'detail_url' => route('admin.orders.show', $order),
            ]));
        }

        // خروجی‌های قدیمی که پیش از ثبت سفارش/درخواست provider ساخته شده‌اند هم
        // باید در گزارش دیده شوند، اما خروجی تکراری سفارش‌های جدید حذف می‌شود.
        $knownPaths = $rows->flatMap(fn (array $row) => $row['output_urls'])->filter()->values();
        $legacyImages = GeneratedImage::with(['user', 'product'])
            ->when($from, fn ($query) => $query->where('generated_images.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('generated_images.created_at', '<=', $to))
            ->latest('generated_images.created_at')->limit(300)->get();
        foreach ($legacyImages as $image) {
            $imageUrl = asset('storage/' . ltrim((string) $image->image_path, '/'));
            if ($knownPaths->contains($imageUrl) || $knownPaths->contains($image->image_path)) continue;
            $usd = (float) $image->cost > 0 ? (float) $image->cost : null;
            $rows->push($this->row([
                'id' => 'image-' . $image->id, 'source_key' => 'user', 'source_label' => 'ساخت کاربر',
                'actor_type' => 'user', 'actor_label' => 'کاربر',
                'provider' => 'ثبت نشده', 'provider_key' => 'unknown', 'model' => 'ثبت نشده',
                'status_key' => 'completed', 'status_label' => 'موفق', 'user_name' => $this->userName($image->user),
                'user_contact' => $image->user?->email ?: $image->user?->phone, 'product_name' => $image->product?->name_fa ?: $image->product?->name_en,
                'product_id' => $image->product_id, 'order_number' => null, 'amount_usd' => $usd,
                'amount_toman' => $usd !== null && $rateIrr > 0 ? $usd * $rateIrr / 10 : null, 'credits' => null,
                'occurred_at' => $image->created_at, 'reference' => 'IMG-' . $image->id, 'note' => 'خروجی قدیمی بدون سفارش متصل',
                'error' => null, 'output_urls' => [$imageUrl], 'latency_seconds' => null, 'retries' => null, 'is_success' => true,
                'detail_url' => $image->product_id ? route('admin.products.create', $image->product_id) : null,
            ]));
        }

        return $rows;
    }

    private function row(array $row): array
    {
        $date = $row['occurred_at'] ? Carbon::parse($row['occurred_at'])->timezone(config('app.display_timezone', 'Asia/Tehran')) : null;
        $row['occurred_at'] = $date;
        $row['date_jalali'] = $date ? Jalali::formatNumeric($date) : '—';
        $row['date_gregorian'] = $date?->format('Y/m/d H:i') ?: '—';
        $row['user_name'] = $row['user_name'] ?: '—';
        $row['user_contact'] = $row['user_contact'] ?: '—';
        $row['actor_type'] = $row['actor_type'] ?? 'system';
        $row['actor_label'] = $row['actor_label'] ?? 'سیستم';
        $row['product_name'] = $row['product_name'] ?: '—';
        $row['provider'] = $row['provider'] ?: '—';
        $row['model'] = $row['model'] ?: '—';
        $row['reference'] = $row['reference'] ?: '—';
        $row['error'] = $row['error'] ?: null;
        $row['output_urls'] = array_values(array_filter((array) $row['output_urls']));
        $row['detail_url'] = $row['detail_url'] ?? null;
        return $row;
    }

    private function summary(Collection $rows): array
    {
        return [
            'count' => $rows->count(),
            'success' => $rows->where('is_success', true)->count(),
            'failed' => $rows->where('status_key', 'failed')->count(),
            'processing' => $rows->whereIn('status_key', ['queued', 'processing'])->count(),
            'usd' => (float) $rows->sum(fn (array $row) => (float) ($row['amount_usd'] ?? 0)),
            'toman' => (float) $rows->sum(fn (array $row) => (float) ($row['amount_toman'] ?? 0)),
        ];
    }

    private function userName($user): ?string
    {
        if (!$user) return null;
        return trim(implode(' ', array_filter([$user->name, $user->last_name]))) ?: ($user->email ?: $user->phone);
    }

    private function orderOutputUrls(Order $order): array
    {
        return collect((array) $order->output_payload)->map(function ($item) {
            $path = is_array($item) ? ($item['path'] ?? $item['url'] ?? null) : null;
            if (!$path) return null;
            return filter_var($path, FILTER_VALIDATE_URL) ? $path : asset('storage/' . ltrim($path, '/'));
        })->filter()->values()->all();
    }

    private function statusLabel(?string $status): string
    {
        return [
            'completed' => 'موفق', 'processing' => 'در حال پردازش', 'queued' => 'در صف',
            'failed' => 'ناموفق', 'cancelled' => 'لغوشده', 'usage' => 'مصرف',
            'charge' => 'شارژ', 'refund' => 'بازگشت وجه', 'adjustment' => 'اصلاح',
            'review' => 'نیازمند بررسی',
        ][$status ?: ''] ?? ($status ?: 'نامشخص');
    }
}
