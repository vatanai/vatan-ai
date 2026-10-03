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
                'aspect_ratios' => array_values((array) config('product_shots.aspect_ratios')),
                'default_aspect_ratio' => (string) config('product_shots.default_aspect_ratio', '4:5'),
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
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', "max:{$maxKb}"],
            'role' => ['nullable', Rule::in(['main', 'angle'])],
        ], [
            'image.required' => 'یک عکس از محصول انتخاب کنید.',
            'image.image' => 'فایل انتخاب‌شده تصویر نیست.',
            'image.mimes' => 'فقط عکس JPG، PNG یا WEBP قابل قبول است.',
            'image.max' => 'حجم عکس بیشتر از حد مجاز است.',
        ]);

        $user = $request->user();
        $storage = app(UserStorageService::class)->check($user, (int) $request->file('image')->getSize(), 0);
        if (! $storage['allowed']) {
            return response()->json(['ok' => false, 'message' => app(UserStorageService::class)->blockMessage($storage)], 422);
        }

        $dir = trim((string) config('product_shots.upload_dir', 'uploads/product-shots'), '/') . '/' . now()->format('Y/m/d');
        try {
            $stored = $this->images->storeUpload($request->file('image'), $dir);
        } catch (\Throwable) {
            return response()->json(['ok' => false, 'message' => 'این فایل قابل خواندن نیست؛ عکس دیگری انتخاب کنید.'], 422);
        }

        $isMain = $request->input('role', 'main') === 'main';
        $report = $isMain
            ? $this->vision->preflight($stored['path'])
            : ['verdict' => 'green', 'usable' => true, 'issues' => [], 'issue_labels' => [], 'crop_box' => null, 'suggestion_fa' => '', 'product_description' => null, 'checked_by' => 'skipped', 'model' => null];

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
            'aspect_ratio' => ['nullable', 'string', 'max:10'],
        ], [
            'uploads.required' => 'یک عکس از محصول بارگذاری کنید.',
            'shots.required' => 'حداقل یک شات انتخاب کنید.',
        ]);

        $user = $request->user();
        $batch = $this->packs->createBatch($user, $product, $data['uploads'], $data['shots'], (string) ($data['aspect_ratio'] ?? ''));
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
