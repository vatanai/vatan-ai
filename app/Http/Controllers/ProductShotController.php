<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductShotSetting;
use App\Models\ShotBatch;
use App\Models\ShotBatchItem;
use App\Services\ProductShots\ShotBatchDispatcher;
use App\Services\ProductShots\ShotImageStore;
use App\Services\ProductShots\ShotPackService;
use App\Services\ProductShots\ShotVisionService;
use App\Services\UserStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * صفحه و API «استودیو محصول» برای کاربر: آپلود + فیلتر کیفیت، ساخت batch،
 * صف پس‌زمینه، پیگیری وضعیت، تلاش دوباره‌ی تک‌شات و دانلود.
 */
class ProductShotController extends Controller
{
    public function __construct(
        private ShotPackService $packs,
        private ShotImageStore $images,
        private ShotVisionService $vision,
        private ShotBatchDispatcher $dispatcher,
    ) {}

    /** داده‌ی صفحه‌ی «بساز» پک؛ از ProductGenerateController::create صدا زده می‌شود. */
    public function page(Product $product)
    {
        $settings = ProductShotSetting::current();
        $user = auth()->user();

        return view('app.product-pack', [
            'product' => $product,
            'packConfig' => [
                'product' => [
                    'slug' => $product->slug,
                    'name' => $product->name_fa ?: $product->name_en,
                    'description' => $product->description_fa,
                    'cover' => $product->displayImageUrl(),
                ],
                'shots' => $this->packs->shotCards($product),
                'quality_levels' => (array) config('product_shots.quality_levels'),
                'default_quality' => (string) config('product_shots.default_quality', 'standard'),
                'max_shots' => (int) $settings->max_shots_per_run,
                'max_extra_angles' => (int) config('product_shots.max_extra_angles', 2),
                'max_upload_mb' => (int) config('product_shots.max_upload_mb', 12),
                'concurrency' => max(1, min(3, (int) $settings->client_concurrency)),
                'balance' => $user ? (int) $user->tokens : 0,
                'is_authenticated' => (bool) $user,
                'login_url' => route('login', ['redirect' => request()->fullUrl()]),
                'pricing_url' => route('pricing.index'),
                'profile_url' => route('app.profile'),
                'urls' => [
                    'preflight' => route('app.product-shots.preflight', $product->slug),
                    'preflight_set' => route('app.product-shots.preflight-set', $product->slug),
                    'link_images' => route('app.product-shots.link-images', $product->slug),
                    'batches' => route('app.product-shots.batches.store', $product->slug),
                ],
            ],
        ]);
    }

    public function preflight(Request $request, Product $product): JsonResponse
    {
        $this->ensureShotProduct($product);
        $maxKb = (int) config('product_shots.max_upload_mb', 12) * 1024;
        $request->validate([
            'image' => ['required_without:image_url', 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', "max:{$maxKb}"],
            'image_url' => ['required_without:image', 'nullable', 'string', 'max:2048'],
            'role' => ['nullable', Rule::in(['main', 'angle'])],
        ], [
            'image.required_without' => 'یک عکس از محصول انتخاب کنید.',
            'image_url.required_without' => 'یک عکس از محصول انتخاب کنید.',
            'image.image' => 'فایل انتخاب‌شده تصویر نیست.',
            'image.mimes' => 'فقط عکس JPG، PNG یا WEBP قابل قبول است.',
            'image.max' => 'حجم عکس بیشتر از حد مجاز است.',
        ]);

        // «عکس از لینک محصول»: عکس انتخاب‌شده از روی لینک دانلود و مثل یک فایل آپلودی بررسی می‌شود.
        $file = $request->file('image');
        if (! $file && $request->filled('image_url')) {
            try {
                $downloaded = app(\App\Services\ProductShots\ProductLinkImages::class)->download((string) $request->input('image_url'), $maxKb * 1024);
            } catch (\RuntimeException $e) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            $file = new \Illuminate\Http\UploadedFile($downloaded['path'], 'link-image', $downloaded['mime'], null, true);
        }

        $user = $request->user();
        $storage = app(UserStorageService::class)->check($user, (int) $file->getSize(), 0);
        if (! $storage['allowed']) {
            return response()->json(['ok' => false, 'message' => app(UserStorageService::class)->blockMessage($storage)], 422);
        }

        $dir = trim((string) config('product_shots.upload_dir', 'uploads/product-shots'), '/') . '/' . now()->format('Y/m/d');
        try {
            $stored = $this->images->storeUpload($file, $dir);
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'message' => 'این فایل قابل خواندن نیست؛ عکس دیگری انتخاب کنید.'], 422);
        } finally {
            if (isset($downloaded['path'])) {
                @unlink($downloaded['path']);
            }
        }

        $isMain = $request->input('role', 'main') === 'main';
        $preflightConfig = (array) data_get($product->shot_settings, 'preflight', []);
        // صفحه‌ی ساخت جدید فقط بررسی رایگان محلی می‌خواهد (ai=0)؛ بررسی هوشمند با دکمه‌ی جدا انجام می‌شود.
        if ($request->has('ai') && ! $request->boolean('ai')) {
            $preflightConfig['enabled'] = false;
            // تشخیص «تاری» محلی روی عکس‌های ساده (پس‌زمینه‌ی صاف) خطای کاذب زیاد دارد؛
            // بدون تأیید هوش مصنوعی فقط هشدار است، نه مانع. موقع ساخت، بررسی کامل سرور دوباره انجام می‌شود.
            $blocking = array_values(array_filter(array_map('strval', (array) ($preflightConfig['blocking_issues']
                ?? \App\Models\ProductShotSetting::current()->preflight_blocking_issues
                ?? ShotVisionService::BLOCKING))));
            $blocking = array_values(array_diff($blocking ?: ShotVisionService::BLOCKING, ['blur']));
            $preflightConfig['blocking_issues'] = $blocking ?: ['__none__'];
        }
        $report = $this->vision->preflight($stored['path'], null, $preflightConfig);

        $fixed = null;
        if ($isMain && $report['verdict'] === 'yellow') {
            $fixable = array_intersect($report['issues'], ['dark', 'cropped', 'busy_bg', 'multiple_products']);
            if ($fixable) {
                $fixed = $this->images->autoFix(
                    $stored['path'],
                    in_array('busy_bg', $report['issues'], true) || in_array('multiple_products', $report['issues'], true) ? $report['crop_box'] : null,
                    in_array('dark', $report['issues'], true),
                    $dir,
                );
            }
        }

        $uploadId = (string) Str::uuid();
        $this->packs->rememberUpload($uploadId, [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'path' => $stored['path'],
            'fixed_path' => $fixed['path'] ?? null,
            'preflight' => $report,
        ]);

        return response()->json([
            'ok' => true,
            'upload_id' => $uploadId,
            'verdict' => $report['verdict'],
            'usable' => $report['usable'],
            'issues' => $report['issue_labels'],
            'suggestion' => $report['suggestion_fa'],
            'checked_by' => $report['checked_by'],
            'original_url' => asset('storage/' . $stored['path']),
            'fixed_url' => $fixed ? asset('storage/' . $fixed['path']) : null,
            'diagnostics' => $report['diagnostics'] ?? null,
        ]);
    }

    /** «عکس از لینک محصول»: پیدا کردن عکس‌های یک صفحه‌ی محصول (سایت خود کاربر یا هر سایت دیگر). */
    public function linkImages(Request $request, Product $product): JsonResponse
    {
        $this->ensureShotProduct($product);
        $data = $request->validate(['url' => ['required', 'string', 'max:2048']], ['url.required' => 'لینک صفحه‌ی محصول را بگذار.']);
        try {
            $result = app(\App\Services\ProductShots\ProductLinkImages::class)->images($data['url']);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
        if ($result['images'] === []) {
            return response()->json(['ok' => false, 'message' => 'در این صفحه عکس محصولی پیدا نشد؛ عکس‌ها را دستی بارگذاری کن.'], 422);
        }

        return response()->json(['ok' => true, 'title' => $result['title'], 'images' => $result['images']]);
    }

    public function preflightSet(Request $request, Product $product): JsonResponse
    {
        $this->ensureShotProduct($product);
        $data = $request->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:' . (1 + (int) config('product_shots.max_extra_angles', 2))],
            'uploads.*.id' => ['required', 'string', 'max:64'],
            'uploads.*.use_fixed' => ['nullable', 'boolean'],
            'smart' => ['nullable', 'boolean'],
        ]);
        $paths = [];
        foreach ($data['uploads'] as $upload) {
            $meta = $this->packs->upload((string) $upload['id']);
            if (! $meta || (int) ($meta['user_id'] ?? 0) !== (int) $request->user()->id || (int) ($meta['product_id'] ?? 0) !== (int) $product->id) {
                return response()->json(['ok' => false, 'message' => 'یکی از عکس‌ها پیدا نشد یا منقضی شده است؛ دوباره بارگذاری کنید.'], 422);
            }
            $paths[] = ! empty($upload['use_fixed']) && ! empty($meta['fixed_path']) ? $meta['fixed_path'] : $meta['path'];
        }
        $configuration = (array) data_get($product->shot_settings, 'preflight', []);

        // «بررسی هوشمند تصویر ورودی»: فقط یک درخواست ارزان برای همه‌ی عکس‌ها، بدون ساخت پروداکت‌شیت.
        if (! empty($data['smart'])) {
            $report = $this->vision->smartCheck($paths, null, $configuration);
            if ($report['checked_by'] === 'vision') {
                \Illuminate\Support\Facades\Cache::put(ShotPackService::smartCacheKey((int) $product->id, $paths), $report, now()->addHours(3));
            }

            return response()->json([
                'ok' => true,
                'verdict' => $report['verdict'],
                'usable' => $report['usable'],
                'issues' => $report['issue_labels'],
                'summary' => $report['summary_fa'],
                'suggestion' => $report['suggestion_fa'],
                'coverage' => $report['coverage'],
                'photos' => $report['photos'],
                'checked_by' => $report['checked_by'],
            ]);
        }

        $report = $this->vision->preflightSet($paths, null, $configuration);
        $sheet = $this->images->createProductSheet(
            $paths,
            trim((string) config('product_shots.upload_dir'), '/') . '/sheet-previews/' . now()->format('Y/m/d'),
            (int) ($configuration['product_sheet_size'] ?? config('product_shots.product_sheet_size', 2048)),
        );

        return response()->json([
            'ok' => true,
            'verdict' => $report['verdict'],
            'usable' => $report['usable'],
            'issues' => $report['issue_labels'],
            'suggestion' => $report['suggestion_fa'],
            'coverage' => $report['coverage'],
            'checked_by' => $report['checked_by'],
            'product_sheet_url' => $sheet ? asset('storage/' . $sheet['path']) : null,
        ]);
    }

    public function storeBatch(Request $request, Product $product): JsonResponse
    {
        $this->ensureShotProduct($product);
        $data = $request->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:' . (1 + (int) config('product_shots.max_extra_angles', 2))],
            'uploads.*.id' => ['required', 'string', 'max:64'],
            'uploads.*.use_fixed' => ['nullable', 'boolean'],
            'shots' => ['required', 'array', 'min:1', 'max:20'],
            'shots.*' => ['string', 'max:80'],
            'quality_level' => ['required', Rule::in(array_keys((array) config('product_shots.quality_levels')))],
            'shot_ratios' => ['nullable', 'array'],
            'shot_ratios.*' => ['string', Rule::in((array) config('product_shots.aspect_ratios'))],
        ], [
            'uploads.required' => 'یک عکس از محصول بارگذاری کنید.',
            'shots.required' => 'حداقل یک شات انتخاب کنید.',
        ]);

        $user = $request->user();
        $batch = $this->packs->createBatch(
            $user,
            $product,
            $data['uploads'],
            $data['shots'],
            (string) $data['quality_level'],
            (array) ($data['shot_ratios'] ?? []),
        );
        $this->dispatcher->dispatchBatch($batch);

        return response()->json([
            'ok' => true,
            'message' => 'ساخت در پس‌زمینه شروع شد؛ می‌توانید این صفحه را ببندید.',
            'batch' => $this->packs->batchPayload($batch),
        ], 201);
    }

    public function showBatch(Request $request, ShotBatch $shotBatch): JsonResponse
    {
        $this->authorizeBatch($request, $shotBatch);

        return response()->json(['ok' => true, 'batch' => $this->packs->batchPayload($shotBatch)]);
    }

    public function runItem(Request $request, ShotBatch $shotBatch, ShotBatchItem $item): JsonResponse
    {
        $this->authorizeBatch($request, $shotBatch);
        if ((int) $item->shot_batch_id !== (int) $shotBatch->id) {
            $this->notFound();
        }

        if (! $item->canRun()) {
            return response()->json([
                'ok' => $item->status === 'completed',
                'message' => $item->status === 'completed' ? null : 'این شات در حال ساخت است یا سقف تلاش آن تمام شده.',
                'item' => $item->toClientArray(),
                'batch_status' => $shotBatch->status,
            ], $item->status === 'completed' ? 200 : 409);
        }

        $this->dispatcher->dispatchItem($item);

        return response()->json([
            'ok' => true,
            'message' => 'ساخت دوباره در صف قرار گرفت.',
            'item' => $item->fresh()->toClientArray(),
            'batch_status' => $shotBatch->fresh()->status,
            'balance' => (int) $request->user()->fresh()->tokens,
        ], 202);
    }

    public function download(Request $request, ShotBatch $shotBatch): BinaryFileResponse
    {
        $this->authorizeBatch($request, $shotBatch);
        $paths = $shotBatch->items()->where('status', 'completed')->whereNotNull('image_path')->get(['shot_key', 'image_path']);
        abort_if($paths->isEmpty(), 404);  // درخواست دانلود JSON نیست؛ صفحه‌ی ۴۰۴ عادی

        $zipPath = tempnam(sys_get_temp_dir(), 'shots_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $disk = Storage::disk('public');
        foreach ($paths->values() as $i => $row) {
            if ($disk->exists($row->image_path)) {
                $ext = pathinfo($row->image_path, PATHINFO_EXTENSION) ?: 'png';
                $zip->addFromString(sprintf('%02d-%s.%s', $i + 1, $row->shot_key, $ext), (string) $disk->get($row->image_path));
            }
        }
        $zip->close();

        return response()->download($zipPath, 'vatan-pack-' . Str::limit($shotBatch->uuid, 8, '') . '.zip')->deleteFileAfterSend(true);
    }

    private function ensureShotProduct(Product $product): void
    {
        if (! $product->isShotProduct() || $product->status !== 'active') {
            $this->notFound();
        }
    }

    private function authorizeBatch(Request $request, ShotBatch $batch): void
    {
        if ((int) $batch->user_id !== (int) $request->user()->id) {
            $this->notFound();
        }
    }

    /**
     * هندلر سراسری خطاهای JSON هر HttpException را ۵۰۰ می‌کند؛ اینجا پاسخ ۴۰۴
     * صریح می‌دهیم تا کلاینت پک وضعیت درست ببیند.
     */
    private function notFound(): never
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json(['ok' => false, 'message' => 'پیدا نشد.', 'error_code' => 'NOT_FOUND'], 404)
        );
    }
}
