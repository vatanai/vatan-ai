<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProviderRequest;
use App\Models\LabRun;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.jobs', $this->buildSnapshot($request));
    }

    public function snapshot(Request $request): JsonResponse
    {
        return response()->json($this->buildSnapshot($request));
    }

    public function retryFailed(int $failedJob): RedirectResponse
    {
        $row = $this->failedJob($failedJob);
        if (!$row) return back()->with('error', 'خطای موردنظر پیدا نشد.');

        Artisan::call('queue:retry', ['id' => [$row->uuid]]);
        return back()->with('success', 'جاب ناموفق دوباره وارد صف شد.');
    }

    public function forgetFailed(int $failedJob): RedirectResponse
    {
        $row = $this->failedJob($failedJob);
        if (!$row) return back()->with('error', 'خطای موردنظر پیدا نشد.');

        Artisan::call('queue:forget', ['id' => [$row->uuid]]);
        return back()->with('success', 'رکورد خطای انتخاب‌شده حذف شد.');
    }

    public function clear(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('jobs')) return back()->with('error', 'جدول صف هنوز ساخته نشده است.');

        $queue = (string) ($request->input('queue') ?: config('queue.connections.database.queue', 'default'));
        $deleted = DB::table(config('queue.connections.database.table', 'jobs'))
            ->where('queue', $queue)
            ->delete();

        return back()->with('success', "{$deleted} جاب در انتظار از صف {$queue} پاک شد.");
    }

    private function buildSnapshot(Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $provider = trim((string) $request->query('provider', ''));
        $rows = collect();

        $queueTable = config('queue.connections.database.table', 'jobs');
        if (Schema::hasTable($queueTable)) {
            $pending = DB::table($queueTable)
                ->where('queue', config('queue.connections.database.queue', 'default'))
                ->orderBy('available_at')
                ->limit(100)
                ->get();

            foreach ($pending as $job) {
                $meta = $this->payloadMeta($job->payload);
                $labRun = $this->labRunForPayload($meta);
                $rows->push([
                    'source' => 'queue',
                    'id' => (string) $job->id,
                    'status' => 'queued',
                    'status_label' => 'در صف',
                    'title' => $meta['title'],
                    'job_class' => $meta['job_class'],
                    'provider' => $meta['provider'] ?: (string) ($labRun?->provider ?: '—'),
                    'model' => $meta['model'] ?: (string) ($labRun?->model_id ?: '—'),
                    'user' => $labRun ? 'ادمین' : '—',
                    'product' => (string) ($labRun?->experiment?->title ?: '—'),
                    'attempts' => (int) $job->attempts,
                    'age_seconds' => max(0, now()->timestamp - (int) $job->created_at),
                    'time_label' => $this->durationLabel(max(0, now()->timestamp - (int) $job->created_at)),
                    'error' => null,
                    'failed_id' => null,
                ]);
            }
        }

        if (Schema::hasTable('failed_jobs')) {
            foreach (DB::table('failed_jobs')->latest('failed_at')->limit(100)->get() as $job) {
                $meta = $this->payloadMeta($job->payload);
                $labRun = $this->labRunForPayload($meta);
                $rows->push([
                    'source' => 'failed',
                    'id' => (string) $job->id,
                    'status' => 'failed',
                    'status_label' => 'ناموفق',
                    'title' => $meta['title'],
                    'job_class' => $meta['job_class'],
                    'provider' => $meta['provider'] ?: (string) ($labRun?->provider ?: '—'),
                    'model' => $meta['model'] ?: (string) ($labRun?->model_id ?: '—'),
                    'user' => $labRun ? 'ادمین' : '—',
                    'product' => (string) ($labRun?->experiment?->title ?: '—'),
                    'attempts' => null,
                    'age_seconds' => max(0, now()->diffInSeconds($job->failed_at)),
                    'time_label' => $job->failed_at ? now()->diffForHumans($job->failed_at, true) : '—',
                    'error' => Str::limit($this->exceptionMessage($job->exception), 240),
                    'failed_id' => (int) $job->id,
                ]);
            }
        }

        if (Schema::hasTable('orders')) {
            $orders = Order::with(['user:id,name,last_name', 'product:id,name_fa'])
                ->whereIn('processing_status', ['queued', 'processing', 'retrying', 'failed'])
                ->latest('updated_at')->limit(100)->get();
            foreach ($orders as $order) {
                $rows->push([
                    'source' => 'order',
                    'id' => (string) $order->order_number,
                    'status' => (string) $order->processing_status,
                    'status_label' => $this->statusLabel((string) $order->processing_status),
                    'title' => 'سفارش کاربر',
                    'job_class' => 'Order',
                    'provider' => (string) ($order->ai_provider ?: '—'),
                    'model' => (string) ($order->ai_model ?: '—'),
                    'user' => trim((string) ($order->user?->name . ' ' . $order->user?->last_name)) ?: '—',
                    'product' => (string) ($order->product?->name_fa ?: '—'),
                    'attempts' => (int) $order->attempts,
                    'age_seconds' => $order->updated_at ? max(0, now()->diffInSeconds($order->updated_at)) : null,
                    'time_label' => $order->processing_duration_ms ? $this->durationLabel((int) round($order->processing_duration_ms / 1000)) : '—',
                    'error' => $order->error_message,
                    'failed_id' => null,
                ]);
            }
        }

        if (Schema::hasTable('lab_runs')) {
            $runs = LabRun::with(['experiment:id,title,product_id', 'aiModel:id,name,provider,external_model_id,openrouter_model_id'])
                ->whereIn('status', ['queued', 'processing', 'failed'])
                ->latest('updated_at')->limit(100)->get();
            foreach ($runs as $run) {
                $rows->push([
                    'source' => 'lab',
                    'id' => 'LAB-' . $run->id,
                    'status' => (string) $run->status,
                    'status_label' => $run->status_label,
                    'title' => 'آزمایشگاه مدل',
                    'job_class' => 'RunLabModelJob',
                    'provider' => (string) ($run->provider ?: $run->aiModel?->provider ?: '—'),
                    'model' => (string) ($run->model_id ?: $run->aiModel?->externalModelId() ?: '—'),
                    'user' => 'ادمین',
                    'product' => (string) ($run->experiment?->title ?: '—'),
                    'attempts' => (int) $run->retry_count,
                    'age_seconds' => $run->updated_at ? max(0, now()->diffInSeconds($run->updated_at)) : null,
                    'time_label' => $run->duration_ms ? $this->durationLabel((int) round($run->duration_ms / 1000)) : '—',
                    'error' => $run->error_message,
                    'failed_id' => null,
                ]);
            }
        }

        $rows = $rows
            ->filter(fn (array $row) => !$status || $row['status'] === $status)
            ->filter(fn (array $row) => !$provider || $row['provider'] === $provider)
            ->filter(function (array $row) use ($search) {
                if ($search === '') return true;
                return str_contains(mb_strtolower(implode(' ', [
                    $row['id'], $row['title'], $row['job_class'], $row['provider'], $row['model'], $row['user'], $row['product'], $row['error'] ?? '',
                ])), mb_strtolower($search));
            })
            ->sortByDesc(fn (array $row) => $row['age_seconds'] ?? 0)
            ->take(100)
            ->values();

        $active = $this->countByStatuses(['queued', 'processing', 'retrying']);
        $completed24h = $this->countSince('completed', now()->subDay());
        $failed24h = $this->countSince('failed', now()->subDay());

        return [
            'rows' => $rows,
            'filters' => ['q' => $search, 'status' => $status, 'provider' => $provider],
            'stats' => [
                'processing' => $active['processing'],
                'queued' => $active['queued'],
                'success_24h' => $completed24h,
                'failed_24h' => $failed24h + (Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count() : 0),
                'avg_seconds' => $this->averageDurationSeconds(),
            ],
            'workers' => [$this->workerStatus()],
            'modelQueue' => $this->modelQueue(),
            'recentErrors' => $this->recentErrors(),
            'queueName' => config('queue.connections.database.queue', 'default'),
            'queueConnection' => config('queue.default'),
        ];
    }

    private function countByStatuses(array $statuses): array
    {
        $result = ['queued' => 0, 'processing' => 0];
        if (Schema::hasTable('orders')) {
            $orderCounts = DB::table('orders')->whereIn('processing_status', $statuses)->selectRaw('processing_status, COUNT(*) AS total')->groupBy('processing_status')->pluck('total', 'processing_status');
            foreach ($orderCounts as $key => $count) $result[$key === 'retrying' ? 'processing' : $key] += (int) $count;
        }
        if (Schema::hasTable('lab_runs')) {
            $runCounts = DB::table('lab_runs')->whereIn('status', $statuses)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
            foreach ($runCounts as $key => $count) $result[$key === 'retrying' ? 'processing' : $key] += (int) $count;
        }
        if (Schema::hasTable('ai_provider_requests')) {
            $requestCounts = DB::table('ai_provider_requests')->whereIn('status', $statuses)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
            foreach ($requestCounts as $key => $count) $result[$key === 'reserved' ? 'queued' : ($key === 'retrying' ? 'processing' : $key)] += (int) $count;
        }
        return $result;
    }

    private function countSince(string $status, \DateTimeInterface $since): int
    {
        $count = 0;
        foreach ([['orders', 'processing_status'], ['lab_runs', 'status'], ['ai_provider_requests', 'status']] as [$table, $column]) {
            if (Schema::hasTable($table)) $count += DB::table($table)->where($column, $status)->where('updated_at', '>=', $since)->count();
        }
        return $count;
    }

    private function averageDurationSeconds(): ?float
    {
        $values = collect();
        if (Schema::hasTable('orders')) $values = $values->merge(DB::table('orders')->where('completed_at', '>=', now()->subDay())->whereNotNull('processing_duration_ms')->pluck('processing_duration_ms')->map(fn ($value) => (float) $value / 1000));
        if (Schema::hasTable('lab_runs')) $values = $values->merge(DB::table('lab_runs')->where('completed_at', '>=', now()->subDay())->whereNotNull('duration_ms')->pluck('duration_ms')->map(fn ($value) => (float) $value / 1000));
        return $values->isEmpty() ? null : round((float) $values->avg(), 1);
    }

    private function modelQueue(): array
    {
        $rows = collect();
        if (Schema::hasTable('ai_provider_requests')) {
            $rows = $rows->merge(DB::table('ai_provider_requests')->whereIn('status', ['reserved', 'queued', 'processing'])->selectRaw('provider, ai_model_id, COUNT(*) AS total')->groupBy('provider', 'ai_model_id')->get()->map(fn ($row) => ['provider' => $row->provider, 'model' => (string) $row->ai_model_id, 'total' => (int) $row->total]));
        }
        if (Schema::hasTable('lab_runs')) {
            $rows = $rows->merge(DB::table('lab_runs')->whereIn('status', ['queued', 'processing'])->selectRaw('provider, model_id, COUNT(*) AS total')->groupBy('provider', 'model_id')->get()->map(fn ($row) => ['provider' => $row->provider, 'model' => $row->model_id, 'total' => (int) $row->total]));
        }
        return $rows->groupBy(fn ($row) => $row['provider'] . '|' . $row['model'])->map(fn ($group) => ['provider' => $group->first()['provider'], 'model' => $group->first()['model'], 'total' => $group->sum('total')])->sortByDesc('total')->take(8)->values()->all();
    }

    private function recentErrors(): array
    {
        $errors = collect();
        if (Schema::hasTable('failed_jobs')) {
            $errors = $errors->merge(DB::table('failed_jobs')->latest('failed_at')->limit(8)->get()->map(fn ($job) => ['source' => 'queue', 'title' => $this->payloadMeta($job->payload)['title'], 'message' => Str::limit($this->exceptionMessage($job->exception), 180), 'at' => $job->failed_at]));
        }
        if (Schema::hasTable('lab_runs')) {
            $errors = $errors->merge(DB::table('lab_runs')->where('status', 'failed')->whereNotNull('error_message')->latest('updated_at')->limit(8)->get()->map(fn ($run) => ['source' => 'lab', 'title' => 'LAB-' . $run->id, 'message' => Str::limit((string) $run->error_message, 180), 'at' => $run->updated_at]));
        }
        return $errors->sortByDesc('at')->take(8)->values()->all();
    }

    private function workerStatus(): array
    {
        $pidPath = storage_path('app/queue-worker.pid');
        $pid = is_file($pidPath) ? (int) trim((string) file_get_contents($pidPath)) : 0;
        $running = false;
        if ($pid > 0 && function_exists('posix_kill')) {
            $running = @posix_kill($pid, 0);
        } elseif ($pid > 0 && is_dir('/proc/' . $pid)) {
            $running = true;
        }

        return ['name' => 'Queue Worker', 'pid' => $pid ?: null, 'running' => $running, 'queue' => config('queue.connections.database.queue', 'default')];
    }

    private function payloadMeta(?string $payload): array
    {
        $data = json_decode((string) $payload, true) ?: [];
        $display = (string) ($data['displayName'] ?? data_get($data, 'data.commandName', 'UnknownJob'));
        $jobClass = class_basename(str_replace('\\', '/', $display));
        $command = (string) data_get($data, 'data.command', '');
        $model = '';
        foreach (['provider', 'model_id', 'modelId'] as $needle) {
            if (preg_match('/' . preg_quote($needle, '/') . '[:";s]*([^";]+)/i', $command, $match)) {
                $model = trim($match[1]);
                break;
            }
        }
        preg_match('/(?:runId|run_id)(?:[^0-9]{1,20})(\d+)/i', $command, $runMatch);

        return [
            'title' => $jobClass ?: 'Queue Job',
            'job_class' => $jobClass ?: 'UnknownJob',
            'provider' => '',
            'model' => $model,
            'run_id' => isset($runMatch[1]) ? (int) $runMatch[1] : null,
        ];
    }

    private function labRunForPayload(array $meta): ?LabRun
    {
        if (! $meta['run_id'] || ! Schema::hasTable('lab_runs')) return null;

        return LabRun::with('experiment:id,title')->find($meta['run_id']);
    }

    private function exceptionMessage(?string $exception): string
    {
        $lines = preg_split('/\R/', (string) $exception) ?: [];
        return trim((string) ($lines[0] ?? 'خطای نامشخص'));
    }

    private function statusLabel(string $status): string
    {
        return ['queued' => 'در صف', 'processing' => 'در حال اجرا', 'retrying' => 'اجرای مجدد', 'failed' => 'ناموفق'][$status] ?? $status;
    }

    private function durationLabel(int $seconds): string
    {
        if ($seconds < 60) return $seconds . ' ثانیه';
        return intdiv($seconds, 60) . ' دقیقه و ' . ($seconds % 60) . ' ثانیه';
    }

    private function failedJob(int $id): ?object
    {
        return Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->where('id', $id)->first() : null;
    }
}
