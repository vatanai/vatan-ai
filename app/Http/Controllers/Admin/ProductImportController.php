<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductImportItem;
use App\Models\ProductImportRun;
use App\Models\ProductImportSetting;
use App\Services\ProductImports\ProductImportProcessor;
use App\Services\ProductImports\ProductImportSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProductImportController extends Controller
{
    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'prompt_amendment' => ['required', 'string', 'min:80', 'max:10000'],
            'text_model' => ['required', 'string', 'max:160'],
        ]);
        $setting = ProductImportSetting::current();
        $setting->fill($data)->save();

        return response()->json(['ok' => true, 'message' => 'اصلاحیه‌ی پرامپت ذخیره شد.']);
    }

    public function createRun(Request $request, ProductImportSourceService $source): JsonResponse
    {
        $data = $request->validate(['source_url' => ['required', 'url', 'max:2048']]);
        @set_time_limit(300);
        try {
            $discovery = $source->discover($data['source_url']);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['source_url' => Str::limit($e->getMessage(), 250)]);
        }
        $items = (array) ($discovery['items'] ?? []);
        if ($items === []) throw ValidationException::withMessages(['source_url' => 'در این نشانی محتوای قابل‌استخراج پیدا نشد.']);
        $estimatedProducts = collect($items)->sum(function (array $entry): int {
            $count = count((array) data_get($entry, 'payload.shots', []));
            return $count > 7 ? (int) ceil($count / 6) : 1;
        });

        $settings = ProductImportSetting::current();
        $host = (string) parse_url($data['source_url'], PHP_URL_HOST);
        $run = DB::transaction(function () use ($items, $discovery, $data, $settings, $host) {
            $run = ProductImportRun::query()->create([
                'admin_id' => auth('admin')->id(),
                'source_url' => $data['source_url'],
                'source_host' => $host,
                'adapter' => (string) ($discovery['adapter'] ?? 'generic'),
                'prompt_amendment' => $settings->prompt_amendment,
                'text_model' => $settings->text_model,
                'status' => 'processing',
                'discovered_count' => count($items),
            ]);

            foreach ($items as $entry) {
                $url = (string) ($entry['url'] ?? '');
                if ($url === '') continue;
                $key = hash('sha256', $url);
                $previous = ProductImportItem::query()->where('source_key', $key)->where('status', 'imported')->latest('id')->first();
                $run->items()->create([
                    'source_url' => $url,
                    'source_key' => $key,
                    'source_title' => Str::limit((string) ($entry['title'] ?? ''), 255, ''),
                    'payload' => (array) ($entry['payload'] ?? []),
                    'status' => $previous ? 'imported' : 'pending',
                    'products_created' => $previous?->products_created ?? 0,
                    'product_ids' => $previous?->product_ids,
                    'processed_at' => $previous ? now() : null,
                ]);
            }
            $run->discovered_count = $run->items()->count();
            $run->processed_count = $run->items()->where('status', 'imported')->count();
            $run->product_count = (int) $run->items()->sum('products_created');
            $run->status = $run->items()->where('status', 'pending')->exists() ? 'processing' : 'completed';
            $run->save();

            return $run;
        });

        return response()->json([
            'ok' => true,
            'message' => 'فهرست منبع خوانده شد و انتقال مرحله‌ای شروع شد.',
            'run' => $this->runPayload($run),
            'estimated_products' => $estimatedProducts,
        ]);
    }

    public function processNext(ProductImportRun $run, ProductImportProcessor $processor): JsonResponse
    {
        @set_time_limit(180);
        $claim = DB::transaction(function () use ($run) {
            // Skip rows claimed by other workers; this lets independent workers
            // safely process one run without waiting on the same queue head.
            $item = $run->items()->where('status', 'pending')->orderBy('id')->lock('for update skip locked')->first();
            if ($item) {
                $item->update(['status' => 'processing', 'error_message' => null]);
                return $item;
            }
            return null;
        }, 5);

        if (! $claim) {
            // Recover abandoned work only when the queue has no claimable item.
            // The timeout avoids stealing a healthy long-running AI request.
            $run->items()->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(45))
                ->update(['status' => 'pending', 'error_message' => null]);
            $claim = DB::transaction(function () use ($run) {
                $item = $run->items()->where('status', 'pending')->orderBy('id')->lock('for update skip locked')->first();
                if ($item) $item->update(['status' => 'processing', 'error_message' => null]);
                return $item;
            }, 5);
        }
        $item = $claim;

        if (! $item) {
            $this->refreshRun($run);
            $busy = $run->fresh()->items()->where('status', 'processing')->exists();
            return response()->json(['ok' => true, 'done' => ! $busy, 'busy' => $busy, 'run' => $this->runPayload($run->fresh())]);
        }

        try {
            $result = $processor->process($item);
        } catch (Throwable $e) {
            report($e);
            $item->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 800),
                'processed_at' => now(),
            ])->save();
            $result = ['created' => 0, 'product_ids' => [], 'error' => Str::limit($e->getMessage(), 250)];
        }

        $this->refreshRun($run);
        return response()->json([
            'ok' => true,
            'done' => ! $run->fresh()->items()->where('status', 'pending')->exists(),
            'item' => ['title' => $item->source_title, 'status' => $item->fresh()->status, 'created' => $result['created'] ?? 0, 'error' => $result['error'] ?? null],
            'run' => $this->runPayload($run->fresh()),
        ]);
    }

    public function retryFailed(ProductImportRun $run): JsonResponse
    {
        $count = $run->items()->where('status', 'failed')->update(['status' => 'pending', 'error_message' => null, 'processed_at' => null]);
        $this->refreshRun($run);
        return response()->json(['ok' => true, 'retried' => $count, 'run' => $this->runPayload($run->fresh())]);
    }

    private function refreshRun(ProductImportRun $run): void
    {
        $run->refresh();
        $items = $run->items();
        $run->processed_count = (clone $items)->whereIn('status', ['imported', 'failed'])->count();
        $run->product_count = (int) (clone $items)->sum('products_created');
        $run->failed_count = (clone $items)->where('status', 'failed')->count();
        $pending = (clone $items)->whereIn('status', ['pending', 'processing'])->exists();
        $run->status = $pending ? 'processing' : ($run->failed_count > 0 ? 'partial' : 'completed');
        $run->save();
    }

    private function runPayload(?ProductImportRun $run): array
    {
        if (! $run) return [];
        return [
            'id' => $run->id,
            'status' => $run->status,
            'discovered' => $run->discovered_count,
            'processed' => $run->processed_count,
            'products' => $run->product_count,
            'failed' => $run->failed_count,
        ];
    }
}
