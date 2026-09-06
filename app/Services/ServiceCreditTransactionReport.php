<?php

namespace App\Services;

use App\Models\AiProviderRequest;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\LabRun;
use App\Models\Order;
use App\Models\ServiceCreditTransaction;
use App\Models\UserGalleryItem;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
        $requestedPerPage = (int) $request->input('per_page', 20);
        $perPage = in_array($requestedPerPage, [20, 50, 100], true) ? $requestedPerPage : 20;
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
            $isSuccessful = $run->status === 'completed';
            $usd = (float) $run->actual_cost_usd > 0
                ? (float) $run->actual_cost_usd
                : ($isSuccessful ? (float) $run->estimated_cost_usd : 0.0);
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

        // منبع اصلی خروجی‌های موفق خود جدول تصاویر ساخته‌شده است؛ هر تصویر
        // دقیقاً یک ردیف دارد و جزئیات سفارش/درخواست سرویس به همان ردیف متصل می‌شود.
        $generatedImages = GeneratedImage::with(['user', 'product', 'order.user', 'order.product', 'providerRequest.aiModel'])
            ->when($from, fn ($query) => $query->where('generated_images.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('generated_images.created_at', '<=', $to))
            ->latest('generated_images.created_at')->limit(700)->get();
        $generatedVideos = Schema::hasTable('generated_videos')
            ? GeneratedVideo::with(['user', 'product', 'order.user', 'order.product', 'providerRequest.aiModel'])
                ->when($from, fn ($query) => $query->where('generated_videos.created_at', '>=', $from))
                ->when($to, fn ($query) => $query->where('generated_videos.created_at', '<=', $to))
                ->latest('generated_videos.created_at')->limit(700)->get()
            : collect();
        $generatedMedia = $generatedImages->map(fn (GeneratedImage $item): array => ['item' => $item, 'media_type' => 'image'])
            ->concat($generatedVideos->map(fn (GeneratedVideo $item): array => ['item' => $item, 'media_type' => 'video']))
            ->sortByDesc(fn (array $media) => $media['item']->created_at?->timestamp ?? 0)
            ->values();
        $generatedProviderRequestIds = $generatedMedia->pluck('item.ai_provider_request_id')->filter()->unique()->values();
        $generatedOrderIds = $generatedMedia->pluck('item.order_id')->filter()->unique()->values();
        $providerOutputCounts = $generatedMedia->whereNotNull('item.ai_provider_request_id')->countBy('item.ai_provider_request_id');
        $orderOutputCounts = $generatedMedia->whereNotNull('item.order_id')->countBy('item.order_id');
        $inputGalleryByOrder = $this->inputGalleryByOrder();

        foreach ($generatedMedia as $media) {
            $item = $media['item'];
            $mediaType = $media['media_type'];
            $isVideo = $mediaType === 'video';
            $image = $isVideo ? null : $item;
            $video = $isVideo ? $item : null;
            $providerRequest = $item->providerRequest;
            $order = $item->order;
            $requestOutputCount = max(1, (int) $providerOutputCounts->get($item->ai_provider_request_id, 1));
            $orderOutputCount = max(1, (int) $orderOutputCounts->get($item->order_id, 1));
            $requestUsd = (float) ($providerRequest?->actual_cost_usd ?: $providerRequest?->estimated_cost_usd ?: 0);
            $usd = (float) $item->cost > 0
                ? (float) $item->cost
                : ($requestUsd > 0 ? $requestUsd / $requestOutputCount : null);
            $status = $providerRequest?->status ?: ($order?->processing_status ?: 'completed');
            if ($isVideo) {
                $status = $video->status ?: $status;
            }
            $status = $status === 'success' ? 'completed' : $status;

            $rows->push($this->row([
                'id' => ($isVideo ? 'video-' : 'image-') . $item->id, 'source_key' => 'user', 'source_label' => 'ساخت کاربر',
                'actor_type' => 'user', 'actor_label' => 'کاربر',
                'provider' => $providerRequest?->provider ?: $order?->ai_provider ?: 'ثبت نشده',
                'provider_key' => Str::lower((string) ($providerRequest?->provider ?: $order?->ai_provider ?: 'unknown')),
                'model' => $providerRequest?->aiModel?->name ?: $order?->ai_model ?: 'ثبت نشده',
                'status_key' => $status, 'status_label' => $this->statusLabel($status), 'media_type' => $mediaType,
                'user_name' => $this->userName($item->user ?: $order?->user),
                'user_contact' => $item->user?->email ?: $item->user?->phone ?: $order?->user?->email ?: $order?->user?->phone,
                'product_name' => $item->product?->name_fa ?: $item->product?->name_en ?: $order?->product?->name_fa ?: $order?->product?->name_en,
                'product_id' => $item->product_id ?: $order?->product_id,
                'user_id' => $item->user_id ?: $order?->user_id,
                'order_id' => $order?->id,
                'order_number' => $order?->order_number,
                'amount_usd' => $usd,
                'amount_toman' => $usd !== null && $rateIrr > 0 ? $usd * $rateIrr / 10 : null,
                'credits' => $order?->final_credits !== null ? round((float) $order->final_credits / $orderOutputCount, 2) : null,
                'occurred_at' => $providerRequest?->completed_at ?: $order?->completed_at ?: $item->created_at,
                'reference' => ($isVideo ? 'VID-' : 'IMG-') . $item->id,
                'note' => null,
                'error' => $providerRequest?->error_message ?: $order?->error_message,
                'output_urls' => $isVideo ? array_values(array_filter([$video->playbackUrl()])) : [$this->generatedImageUrl((string) $image->image_path)],
                'latency_seconds' => $providerRequest?->submitted_at && $providerRequest?->completed_at
                    ? round($providerRequest->submitted_at->diffInMilliseconds($providerRequest->completed_at) / 1000, 1)
                    : ($order?->processing_duration_ms ? round($order->processing_duration_ms / 1000, 1) : null),
                'retries' => $order?->attempts, 'media_label' => $isVideo ? 'ویدیو' : 'عکس',
                'is_success' => in_array($status, ['completed', 'success'], true),
                'detail_url' => $order ? route('admin.orders.show', $order) : null,
                'input_media' => $this->inputMediaFor($item->user_id ?: $order?->user_id, $order, $inputGalleryByOrder),
            ]));
        }

        // درخواست‌های در صف/ناموفق یا درخواست‌هایی که هنوز خروجی ذخیره‌شده
        // ندارند، یک ردیف مستقل نگه می‌دارند.
        $providerRequests = AiProviderRequest::with(['order.user', 'order.product', 'aiModel'])
            ->when($generatedProviderRequestIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $generatedProviderRequestIds))
            ->when($from, fn ($query) => $query->where('ai_provider_requests.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('ai_provider_requests.created_at', '<=', $to))
            ->latest('ai_provider_requests.created_at')->limit(700)->get();
        $requestOrderIds = $providerRequests->pluck('order_id')->filter()->unique();
        foreach ($providerRequests as $providerRequest) {
            $order = $providerRequest->order;
            $isSuccessful = in_array($providerRequest->status, ['completed', 'success'], true);
            // هزینهٔ تخمینی برای درخواست ناموفق، کسر واقعی از provider نیست و
            // نباید در «اعتبار سرویس‌ها» به‌صورت بدهی نمایش داده شود.
            $usd = $providerRequest->actual_cost_usd !== null
                ? (float) $providerRequest->actual_cost_usd
                : ($isSuccessful && (float) $providerRequest->estimated_cost_usd > 0
                    ? (float) $providerRequest->estimated_cost_usd
                    : null);
            $rows->push($this->row([
                'id' => 'request-' . $providerRequest->id, 'source_key' => 'user', 'source_label' => 'ساخت کاربر',
                'provider' => $providerRequest->provider ?: 'نامشخص', 'provider_key' => Str::lower((string) ($providerRequest->provider ?: 'unknown')),
                'model' => $providerRequest->aiModel?->name ?: $order?->ai_model ?: 'مدل نامشخص',
                'status_key' => $providerRequest->status, 'status_label' => $this->statusLabel($providerRequest->status),
                'actor_type' => 'user', 'actor_label' => 'کاربر',
                'user_name' => $this->userName($order?->user), 'user_contact' => $order?->user?->email ?: $order?->user?->phone,
                'product_name' => $order?->product?->name_fa ?: $order?->product?->name_en,
                'product_id' => $order?->product_id, 'user_id' => $order?->user_id, 'order_id' => $order?->id,
                'order_number' => $order?->order_number,
                'amount_usd' => $usd, 'amount_toman' => $usd !== null && $rateIrr > 0 ? $usd * $rateIrr / 10 : null,
                'credits' => $order?->final_credits, 'occurred_at' => $providerRequest->completed_at ?: $providerRequest->submitted_at ?: $providerRequest->created_at,
                'reference' => $providerRequest->external_request_id,
                'note' => $order ? null : 'درخواست قدیمی provider بدون سفارش متصل؛ برای تکمیل جزئیات، از این پس order_id ثبت می‌شود.',
                'error' => $providerRequest->error_message, 'output_urls' => (array) $providerRequest->output_urls,
                'latency_seconds' => $providerRequest->submitted_at && $providerRequest->completed_at ? round($providerRequest->submitted_at->diffInMilliseconds($providerRequest->completed_at) / 1000, 1) : null,
                'retries' => $order?->attempts, 'is_success' => $providerRequest->status === 'completed',
                'detail_url' => $order ? route('admin.orders.show', $order) : null,
                'input_media' => $this->inputMediaFor($order?->user_id, $order, $inputGalleryByOrder),
            ]));
        }

        $orders = Order::with(['user', 'product'])
            ->when($requestOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $requestOrderIds))
            ->when($generatedOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $generatedOrderIds))
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
                'user_id' => $order->user_id, 'order_id' => $order->id, 'order_number' => $order->order_number,
                'amount_usd' => null, 'amount_toman' => null,
                'credits' => $order->final_credits, 'occurred_at' => $order->completed_at ?: $order->created_at,
                'reference' => $order->order_number, 'note' => 'جزئیات هزینه provider برای این سفارش ثبت نشده است.',
                'error' => $order->error_message, 'output_urls' => $this->orderOutputUrls($order),
                'latency_seconds' => $order->processing_duration_ms ? round($order->processing_duration_ms / 1000, 1) : null,
                'retries' => $order->attempts, 'is_success' => $order->processing_status === 'completed',
                'detail_url' => route('admin.orders.show', $order),
                'input_media' => $this->inputMediaFor($order->user_id, $order, $inputGalleryByOrder),
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
        foreach (['source_key', 'source_label', 'actor_type', 'actor_label', 'provider_key', 'user_name', 'user_contact', 'product_name', 'provider', 'model', 'status_key', 'status_label', 'reference', 'error', 'note'] as $field) {
            $row[$field] = $this->displayText($row[$field] ?? null);
        }
        $row['error'] = $row['error'] ?: null;
        $row['output_urls'] = collect((array) ($row['output_urls'] ?? []))
            ->map(function (mixed $output): ?string {
                if (is_scalar($output)) return (string) $output;
                if (is_array($output)) {
                    $url = $output['url'] ?? $output['path'] ?? null;
                    return is_scalar($url) ? (string) $url : null;
                }
                return null;
            })
            ->filter(fn (?string $output): bool => filled($output))
            ->values()->all();
        $row['detail_url'] = is_scalar($row['detail_url'] ?? null) ? (string) $row['detail_url'] : null;
        $row['user_id'] = is_numeric($row['user_id'] ?? null) ? (int) $row['user_id'] : null;
        $row['order_id'] = is_numeric($row['order_id'] ?? null) ? (int) $row['order_id'] : null;
        $row['user_url'] = $row['user_id'] ? route('admin.users.gallery.show', $row['user_id']) : null;
        $row['finance_url'] = $row['user_id'] ? route('admin.finance.cases.index', ['user_id' => $row['user_id']]) : null;
        $row['order_url'] = $row['order_id'] ? route('admin.orders.show', $row['order_id']) : null;
        $row['input_media'] = collect((array) ($row['input_media'] ?? []))
            ->filter(fn ($input): bool => is_array($input) && filled($input['url'] ?? null))
            ->map(fn (array $input): array => [
                'type' => in_array(($input['type'] ?? 'image'), ['image', 'video', 'text'], true) ? $input['type'] : 'image',
                'url' => (string) $input['url'],
                'label' => (string) ($input['label'] ?? 'ورودی'),
                'text' => filled($input['text'] ?? null) ? Str::limit((string) $input['text'], 90) : null,
            ])->values()->all();
        return $row;
    }

    private function inputGalleryByOrder(): Collection
    {
        if (! Schema::hasTable('user_gallery_items')) {
            return collect();
        }

        return UserGalleryItem::query()
            ->whereIn('source_type', ['upload', 'input_image', 'input_text', 'input_video'])
            ->latest('id')->get()
            ->filter(fn (UserGalleryItem $item): bool => is_numeric(data_get($item->metadata, 'order_id')))
            ->groupBy(fn (UserGalleryItem $item): string => (string) data_get($item->metadata, 'order_id'));
    }

    private function inputMediaFor(?int $userId, ?Order $order, Collection $inputGalleryByOrder): array
    {
        if (! $userId) {
            return [];
        }

        $items = $order?->id ? $inputGalleryByOrder->get((string) $order->id, collect()) : collect();
        $media = $items->map(function (UserGalleryItem $item) use ($userId): array {
            $mime = strtolower((string) $item->mime_type);
            $type = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'text/') ? 'text' : 'image');
            $url = $type === 'image'
                ? route('admin.users.gallery.preview', [$userId, $item->id])
                : route('admin.users.gallery.original', [$userId, $item->id]);

            return [
                'type' => $type,
                'url' => $url,
                'label' => $type === 'video' ? 'ویدیوی ورودی' : ($type === 'text' ? 'متن ورودی' : 'عکس ورودی'),
                'text' => $type === 'text' ? data_get($item->metadata, 'text') : null,
            ];
        })->values()->all();

        if ($media !== []) {
            return $media;
        }

        $payload = (array) ($order?->input_payload ?? []);
        $paths = array_values(array_filter(array_merge(
            (array) data_get($payload, 'source_upload_paths', []),
            [data_get($payload, 'source_upload_path')],
        )));
        foreach ($paths as $path) {
            if (! is_scalar($path) || ! filled($path)) {
                continue;
            }
            $path = (string) $path;
            $media[] = [
                'type' => 'image',
                'url' => filter_var($path, FILTER_VALIDATE_URL) ? $path : asset('storage/' . ltrim($path, '/')),
                'label' => 'عکس ورودی',
                'text' => null,
            ];
        }
        $videoUrl = data_get($payload, 'source_video_url');
        if (filled($videoUrl)) {
            $media[] = ['type' => 'video', 'url' => (string) $videoUrl, 'label' => 'ویدیوی ورودی', 'text' => null];
        }

        return array_values(array_slice($media, 0, 6));
    }

    private function displayText(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_scalar($value)) return (string) $value;

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
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

    private function generatedImageUrl(string $path): string
    {
        return filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : asset('storage/' . ltrim($path, '/'));
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
