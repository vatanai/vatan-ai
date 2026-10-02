<?php

namespace App\Services\ProductShots;

use App\Jobs\GenerateProfileMediaThumbnail;
use App\Models\AiProviderRequest;
use App\Models\GeneratedImage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductShotSetting;
use App\Models\ShotBatch;
use App\Models\ShotBatchItem;
use App\Models\ShotLibrary;
use App\Models\User;
use App\Services\AiProviderRouter;
use App\Services\CreditWalletService;
use App\Services\UserGalleryService;
use App\Services\UserStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * موتور ساخت «یک شات» از یک پک.
 *
 * هر شات: سفارش مستقل + رزرو اعتبار همان شات + ساخت + QC (و حداکثر یک بار
 * ساخت مجدد خودکار بدون هزینه‌ی اضافه برای کاربر) + تسویه؛ در خطا اعتبار همان
 * شات کامل برمی‌گردد. شات‌های دیگر پک تحت تأثیر نیستند.
 */
class ShotGenerationService
{
    public const ERROR_INSUFFICIENT = 'INSUFFICIENT_CREDITS';
    public const ERROR_DAILY_CAP = 'DAILY_COST_CAP';
    public const ERROR_BUSY = 'ITEM_BUSY';

    public function __construct(
        private AiProviderRouter $router,
        private CreditWalletService $wallet,
        private ShotPromptBuilder $prompts,
        private ShotVisionService $vision,
        private ShotImageStore $images,
        private ShotPackService $packs,
    ) {}

    /**
     * @return array{ok:bool, error_code:?string, message:?string, item:ShotBatchItem}
     */
    public function runItem(ShotBatchItem $item, User $user): array
    {
        $batch = $item->batch()->with('product')->firstOrFail();
        if ((int) $batch->user_id !== (int) $user->id) {
            throw new RuntimeException('این ساخت متعلق به کاربر دیگری است.');
        }

        // قفل خوش‌بینانه: فقط یک درخواست می‌تواند یک شات را اجرا کند (جلوگیری از کسر دوباره).
        $claimed = ShotBatchItem::query()
            ->whereKey($item->id)
            ->whereIn('status', ['pending', 'failed'])
            ->where('attempts', '<', ShotBatchItem::MAX_ATTEMPTS)
            ->update([
                'status' => 'running',
                'attempts' => DB::raw('attempts + 1'),
                'started_at' => now(),
                'error_message' => null,
                'updated_at' => now(),
            ]);
        $item->refresh();
        if ($claimed === 0) {
            return [
                'ok' => $item->status === 'completed',
                'error_code' => $item->status === 'completed' ? null : self::ERROR_BUSY,
                'message' => $item->status === 'completed' ? null : 'این شات در حال ساخت است یا سقف تلاش آن تمام شده.',
                'item' => $item,
            ];
        }
        if ($batch->status === 'pending') {
            $batch->update(['status' => 'running']);
        }

        $settings = ProductShotSetting::current();
        $cap = (float) $settings->daily_cost_cap_usd;
        if ($cap > 0 && $this->packs->todayCostUsd() >= $cap) {
            return $this->failWithoutCharge($item, $batch, self::ERROR_DAILY_CAP, 'ظرفیت ساخت امروز «استودیو محصول» تکمیل شده؛ لطفاً فردا دوباره امتحان کنید. اعتباری کسر نشد.');
        }

        $product = $batch->product;
        $shot = $item->shot ?: ShotLibrary::query()->where('key', $item->shot_key)->first();
        if (! $product || ! $shot) {
            return $this->failWithoutCharge($item, $batch, 'SHOT_UNAVAILABLE', 'این شات دیگر در دسترس نیست. اعتباری کسر نشد.');
        }

        $prompt = $this->prompts->build($product, $shot, $batch->aspect_ratio, [
            'product_description' => $batch->preflight['product_description'] ?? null,
        ]);
        $references = array_values(array_filter(array_map(
            fn ($path) => $this->images->reference((string) $path),
            (array) $batch->source_paths
        )));
        if ($references === []) {
            return $this->failWithoutCharge($item, $batch, 'SOURCE_MISSING', 'عکس محصول دیگر در دسترس نیست؛ لطفاً دوباره بارگذاری کنید. اعتباری کسر نشد.');
        }

        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'plan_id' => $user->plan_id,
            'plan_name' => $user->plan?->name ?: 'رایگان',
            'status' => 'processing',
            'payment_status' => 'paid',
            'processing_status' => 'processing',
            'original_credits' => $item->credits,
            'discount_credits' => 0,
            'final_credits' => $item->credits,
            'ai_model' => $product->primary_model,
            'ai_provider' => $product->ai_provider,
            'attempts' => $item->attempts,
            'input_payload' => [
                'product_mode' => 'product',
                'shot_batch' => $batch->uuid,
                'shot_key' => $item->shot_key,
                'aspect_ratio' => $batch->aspect_ratio,
                'resolved_prompt' => $prompt,
                'source_upload_paths' => array_values((array) $batch->source_paths),
                'input_media_count' => count((array) $batch->source_paths),
            ],
            'source' => 'product_shot',
            'paid_at' => now(),
            'processing_started_at' => now(),
        ]);
        $order->recordEvent('created', 'سفارش شات ثبت شد', 'شات «' . $item->shot_name_fa . '» از پک محصول.');
        $item->update(['order_id' => $order->id, 'prompt' => $prompt]);

        $reservation = ['total' => 0, 'promotional' => 0, 'paid' => 0];
        $settled = false;
        $outputs = [];
        try {
            if ($item->credits > 0) {
                try {
                    $reservation = $this->wallet->reserve($user, $item->credits, $order);
                } catch (ValidationException) {
                    $order->update([
                        'status' => 'review', 'payment_status' => 'failed', 'processing_status' => 'stopped',
                        'error_message' => 'موجودی اعتبار کافی نبود.',
                    ]);
                    $order->recordEvent('payment_failed', 'رزرو اعتبار ناموفق بود', 'موجودی برای این شات کافی نبود.');

                    return $this->failWithoutCharge($item, $batch, self::ERROR_INSUFFICIENT, 'اعتبار شما برای این شات کافی نیست.');
                }
            }

            $resolution = (string) config('product_shots.output_resolution', '1080');
            $extra = [
                'order_id' => $order->id,
                'input_references' => array_map(fn ($ref) => ['type' => 'image_url', 'image_url' => ['url' => $ref]], $references),
                'input_fidelity' => 'high',
                'requested_output_resolution' => $resolution,
                'requested_aspect_ratio' => $batch->aspect_ratio,
            ];
            if (! empty($product->negative_prompt)) {
                $extra['negative_prompt'] = $product->negative_prompt;
            }

            $costUsd = 0.0;
            $usedModel = null;
            $attempt = $this->generateOnce($product, $prompt, $resolution, $batch->aspect_ratio, $extra);
            $outputs[] = $attempt['path'];
            $costUsd += $attempt['cost'];
            $usedModel = $attempt['model'];
            $finalPath = $attempt['path'];
            $providerRequestId = $attempt['provider_request_id'];

            $qc = $this->vision->qualityCheck((string) $batch->source_paths[0], $finalPath, $settings);
            $qcRetries = 0;
            if ($qc['checked'] && ! $qc['passed'] && $settings->qc_auto_retry) {
                $qcRetries = 1;
                $retry = $this->generateOnce(
                    $product,
                    $prompt . "\n\nIMPORTANT CORRECTION: the previous attempt changed the product. Reproduce the product from the reference image exactly; preserve its exact shape, colors and logo.",
                    $resolution,
                    $batch->aspect_ratio,
                    $extra,
                );
                $outputs[] = $retry['path'];
                $costUsd += $retry['cost'];
                $retryQc = $this->vision->qualityCheck((string) $batch->source_paths[0], $retry['path'], $settings);
                if (! $retryQc['checked'] || $retryQc['passed'] || (int) $retryQc['score'] >= (int) $qc['score']) {
                    $finalPath = $retry['path'];
                    $usedModel = $retry['model'] ?: $usedModel;
                    $providerRequestId = $retry['provider_request_id'] ?: $providerRequestId;
                    $qc = $retryQc + ['first_attempt_score' => $qc['score']];
                }
            }

            if ($reservation['total'] > 0) {
                $reservation = $this->wallet->settle($user, $reservation, $item->credits);
                $settled = true;
            }

            $size = Storage::disk('public')->exists($finalPath) ? Storage::disk('public')->size($finalPath) : 0;
            $generatedImage = GeneratedImage::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'ai_provider_request_id' => $providerRequestId,
                'image_path' => $finalPath,
                'user_prompt' => $prompt,
                'cost' => $costUsd,
                'size' => $size,
                'expires_at' => now()->addDays(UserStorageService::OUTPUT_RETENTION_DAYS),
            ]);
            try {
                GenerateProfileMediaThumbnail::dispatch($generatedImage->id)->afterResponse();
            } catch (\Throwable $e) {
                report($e);
            }
            try {
                app(UserGalleryService::class)->captureOutput($user, 'output_image', (int) $generatedImage->id, $finalPath, 'public', $size, 'image/*', [
                    'order_id' => $order->id, 'source' => 'product_shot',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            // خروجی‌های کنارگذاشته‌شده‌ی QC پاک می‌شوند
            foreach ($outputs as $path) {
                if ($path !== $finalPath) {
                    Storage::disk('public')->delete($path);
                }
            }

            $order->update([
                'status' => 'completed',
                'processing_status' => 'completed',
                'ai_model' => $usedModel ?: $product->primary_model,
                'final_credits' => $item->credits,
                'promotional_credits_used' => (int) ($reservation['promotional'] ?? 0),
                'paid_credits_used' => (int) ($reservation['paid'] ?? 0),
                'output_payload' => [['key' => $item->shot_key, 'title' => $item->shot_name_fa, 'path' => $finalPath]],
                'completed_at' => now(),
                'processing_duration_ms' => $order->processing_started_at?->diffInMilliseconds(now()),
            ]);
            $order->recordEvent('completed', 'شات ساخته شد', $qc['checked'] ? ('امتیاز وفاداری: ' . $qc['score'] . ' از ۵') : null);

            $item->update([
                'status' => 'completed',
                'credits_charged' => $item->credits,
                'generated_image_id' => $generatedImage->id,
                'image_path' => $finalPath,
                'qc' => $qc,
                'qc_retries' => $qcRetries,
                'cost_usd' => $costUsd,
                'ai_model' => $usedModel,
                'finished_at' => now(),
            ]);
            $batch->refreshStatus();
            $this->cleanupSourcesIfDone($batch);
            app(UserStorageService::class)->forgetProfileSnapshot($user);

            return ['ok' => true, 'error_code' => null, 'message' => null, 'item' => $item->fresh()];
        } catch (\Throwable $e) {
            $refunded = 0;
            if (($reservation['total'] ?? 0) > 0) {
                $refunded = (int) $reservation['promotional'] + (int) $reservation['paid'];
                $this->wallet->restore(
                    $user,
                    (int) $reservation['promotional'],
                    (int) $reservation['paid'],
                    $settled,
                    $reservation['ledger_key'] ?? null,
                    $reservation['grant_allocations'] ?? [],
                );
            }
            foreach ($outputs as $path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('ProductShots item failed', [
                'item_id' => $item->id, 'order_id' => $order->id, 'exception' => $e::class,
                'message' => Str::limit($e->getMessage(), 800, ''),
            ]);
            $order->update([
                'status' => 'review', 'processing_status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 1000, ''),
                'refunded_credits' => $refunded,
                'promotional_credits_refunded' => $refunded > 0 ? (int) $reservation['promotional'] : 0,
                'paid_credits_refunded' => $refunded > 0 ? (int) $reservation['paid'] : 0,
                'refunded_at' => $refunded > 0 ? now() : null,
                'processing_duration_ms' => $order->processing_started_at?->diffInMilliseconds(now()),
            ]);
            $order->recordEvent('failed', 'ساخت شات ناموفق بود', Str::limit($e->getMessage(), 300));

            $item->update([
                'status' => 'failed',
                'credits_refunded' => (int) $item->credits_refunded + $refunded,
                'error_message' => $this->friendlyError($e),
                'finished_at' => now(),
            ]);
            $batch->refreshStatus();

            return ['ok' => false, 'error_code' => 'GENERATION_FAILED', 'message' => $item->error_message, 'item' => $item->fresh()];
        }
    }

    /**
     * پیش‌نمایش ادمین با عکس تست: بدون سفارش و بدون کسر اعتبار.
     *
     * @return array{path:string, cost:float, model:?string, prompt:string, qc:array}
     */
    public function preview(Product $product, ShotLibrary $shot, string $sourcePath, string $aspectRatio = '4:5', ?string $description = null): array
    {
        $prompt = $this->prompts->build($product, $shot, $aspectRatio, ['product_description' => $description]);
        $ref = $this->images->reference($sourcePath);
        if (! $ref) {
            throw new RuntimeException('عکس تست پیدا نشد.');
        }
        $result = $this->generateOnce($product, $prompt, (string) config('product_shots.output_resolution', '1080'), $aspectRatio, [
            'input_references' => [['type' => 'image_url', 'image_url' => ['url' => $ref]]],
            'input_fidelity' => 'high',
            'requested_aspect_ratio' => $aspectRatio,
        ]);
        $qc = $this->vision->qualityCheck($sourcePath, $result['path']);

        return ['path' => $result['path'], 'cost' => $result['cost'], 'model' => $result['model'], 'prompt' => $prompt, 'qc' => $qc];
    }

    /** @return array{path:string, cost:float, model:?string, provider_request_id:?int} */
    private function generateOnce(Product $product, string $prompt, string $resolution, string $aspectRatio, array $extra): array
    {
        $attempt = $this->router->generateForProduct($product, $prompt, $resolution, $aspectRatio, 1, $extra);
        $data = (array) ($attempt['data'] ?? []);
        $path = $this->images->saveProviderOutput($data);
        $cost = $data['usage']['actual_cost_usd'] ?? $data['usage']['cost'] ?? $data['usage']['estimated_cost_usd'] ?? 0;
        $providerRequestId = isset($extra['order_id'])
            ? AiProviderRequest::query()->where('order_id', $extra['order_id'])->latest('id')->value('id')
            : null;

        return [
            'path' => $path,
            'cost' => is_numeric($cost) ? (float) $cost : 0.0,
            'model' => isset($attempt['model']) ? (string) $attempt['model'] : null,
            'provider_request_id' => $providerRequestId ? (int) $providerRequestId : null,
        ];
    }

    private function failWithoutCharge(ShotBatchItem $item, ShotBatch $batch, string $code, string $message): array
    {
        // تلاشی که هیچ اعتباری مصرف نکرده نباید از سقف تلاش کاربر کم کند.
        $item->update([
            'status' => 'failed',
            'attempts' => max(0, (int) $item->attempts - 1),
            'error_message' => $message,
            'finished_at' => now(),
        ]);
        $batch->refreshStatus();

        return ['ok' => false, 'error_code' => $code, 'message' => $message, 'item' => $item->fresh()];
    }

    private function cleanupSourcesIfDone(ShotBatch $batch): void
    {
        $batch->refresh();
        if ($batch->status !== 'completed' || $batch->sources_deleted_at) {
            return;
        }
        foreach ((array) $batch->source_paths as $path) {
            Storage::disk('public')->delete((string) $path);
        }
        $batch->update(['sources_deleted_at' => now()]);
    }

    private function friendlyError(\Throwable $e): string
    {
        $text = strtolower($e->getMessage());

        return match (true) {
            str_contains($text, 'timeout') || str_contains($text, 'curl') => 'مدل در زمان مقرر پاسخ نداد. اعتبار این شات برگشت؛ دوباره امتحان کنید.',
            str_contains($text, '429') || str_contains($text, 'concurrent') || str_contains($text, 'rate') => 'صف سرویس شلوغ است. اعتبار این شات برگشت؛ چند لحظه بعد دوباره امتحان کنید.',
            str_contains($text, 'insufficient') || str_contains($text, 'quota') => 'سرویس ساخت موقتاً در دسترس نیست. اعتبار این شات برگشت.',
            default => 'ساخت این شات انجام نشد. اعتبار آن کامل برگشت؛ دوباره امتحان کنید.',
        };
    }
}
