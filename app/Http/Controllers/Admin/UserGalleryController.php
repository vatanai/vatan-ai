<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceCase;
use App\Models\FinanceCreditAllocation;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\User;
use App\Models\UserGalleryConfig;
use App\Models\UserGalleryItem;
use App\Models\UserGallerySuggestion;
use App\Models\UserGalleryRecreation;
use App\Models\UserGalleryCampaign;
use App\Models\UserGalleryCostEvent;
use App\Models\Product;
use App\Services\UserGalleryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserGalleryController extends Controller
{
    private const INPUT_SOURCE_TYPES = ['upload', 'input_image', 'input_text', 'input_video'];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $items = UserGalleryItem::query()
            ->whereIn('source_type', self::INPUT_SOURCE_TYPES)
            ->with('user:id,name,last_name,phone')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('expires_at', '>', now()))
            ->when($status === 'expired', fn ($query) => $query->where('expires_at', '<=', now()))
            ->latest()
            ->paginate(24)
            ->withQueryString();
        $this->decorateItems($items);

        $config = UserGalleryConfig::current();
        $stats = [
            'items' => UserGalleryItem::whereIn('source_type', self::INPUT_SOURCE_TYPES)->count(),
            'users' => UserGalleryItem::query()->whereIn('source_type', self::INPUT_SOURCE_TYPES)->distinct('user_id')->count('user_id'),
            'active' => UserGalleryItem::whereIn('source_type', self::INPUT_SOURCE_TYPES)->where('expires_at', '>', now())->count(),
            'storage' => (int) UserGalleryItem::whereIn('source_type', self::INPUT_SOURCE_TYPES)->sum('size'),
            'suggestions' => UserGallerySuggestion::count(),
            'recreations' => UserGalleryRecreation::where('status', 'completed')->count(),
            'campaigns' => UserGalleryCampaign::count(),
            'costs' => (int) UserGalleryCostEvent::sum('cost_toman'),
        ];

        return view('admin.users.gallery.index', compact('items', 'config', 'stats', 'search', 'status'));
    }

    public function show(User $user)
    {
        $items = $user->galleryItems()
            ->whereIn('source_type', self::INPUT_SOURCE_TYPES)
            ->latest()
            ->paginate(24)
            ->withQueryString();
        $this->decorateItems($items);
        $setting = app(UserGalleryService::class)->setting($user);
        [$builds, $financeSummary] = $this->buildUserActivity($user);
        $shortcutLinks = [
            ['label' => 'پروفایل کاربر', 'description' => 'مشخصات، وضعیت و سوابق کاربر', 'icon' => 'fa-user', 'class' => 'primary', 'url' => route('admin.users.index', ['show_user' => $user->id])],
            ['label' => 'گزارش مالی', 'description' => 'پرونده‌های خرید و اعتبار', 'icon' => 'fa-chart-pie', 'class' => 'success', 'url' => route('admin.finance.cases.index', ['user_id' => $user->id])],
            ['label' => 'سفارش‌ها', 'description' => 'تمام سفارش‌ها و وضعیت ساخت', 'icon' => 'fa-receipt', 'class' => 'warning', 'url' => route('admin.orders.index', ['user_id' => $user->id])],
            ['label' => 'اعتبار سرویس‌ها', 'description' => 'مصرف مدل‌ها و هزینه اجرا', 'icon' => 'fa-bolt', 'class' => 'info', 'url' => route('admin.service-credits.index', ['q' => $user->phone ?: $user->email ?: $user->id])],
            ['label' => 'لاگ فعالیت', 'description' => 'ورودها و رخدادهای حساب', 'icon' => 'fa-clock-rotate-left', 'class' => 'neutral', 'url' => route('admin.users.logs', $user->id)],
        ];

        return view('admin.users.gallery.show', compact('user', 'items', 'setting', 'builds', 'financeSummary', 'shortcutLinks'));
    }

    /** جستجوی محصول برای تخصیص مالک از صفحه‌ی گالری همان کاربر. */
    public function searchCreatorRewardProducts(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $products = Product::query()
            ->with('creatorRewardOwner:id,name,last_name')
            ->where(function ($query) use ($search): void {
                $query->where('name_fa', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $query->orWhere('id', (int) $search)->orWhere('product_code', $search);
                }
            })
            ->latest('updated_at')
            ->limit(10)
            ->get(['id', 'name_fa', 'name_en', 'slug', 'status', 'media_type', 'creator_reward_enabled', 'creator_reward_owner_id']);

        return response()->json([
            'data' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name_fa ?: $product->name_en,
                'type' => $product->media_type === 'video' ? 'ویدیو' : ($product->media_type === 'both' ? 'عکس و ویدیو' : 'عکس'),
                'status' => $product->status === 'active' ? 'فعال' : ($product->status === 'draft' ? 'پیش‌نویس' : 'غیرفعال'),
                'owner' => $product->creatorRewardOwner
                    ? trim($product->creatorRewardOwner->name . ' ' . ($product->creatorRewardOwner->last_name ?? ''))
                    : null,
                'reward_enabled' => (bool) $product->creator_reward_enabled,
            ])->values(),
        ]);
    }

    /** مالک تجاری محصول را از صفحه‌ی گالری کاربر ثبت یا جایگزین می‌کند. */
    public function assignCreatorRewardProduct(Request $request, User $user)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        DB::transaction(function () use ($data, $user): void {
            $product = Product::query()
                ->whereKey((int) $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $currentOwnerId = (int) ($product->getRawOriginal('creator_reward_owner_id') ?? 0);
            if ($currentOwnerId > 0 && $currentOwnerId !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'product_id' => 'این محصول قبلاً به کاربر دیگری اختصاص داده شده و قابل اختصاص به کاربر دوم نیست.',
                ]);
            }
            $product->forceFill(['creator_reward_owner_id' => $user->id])->save();
        });

        return back()->with('success', 'مالک محصول با موفقیت به این کاربر اختصاص داده شد. برای فعال‌شدن پرداخت پاداش، گزینه‌ی پاداش مالک محصول را در تنظیمات محصول روشن کنید.');
    }

    private function buildUserActivity(User $user): array
    {
        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with('product')
            ->latest('created_at')
            ->limit(40)
            ->get();
        $orderIds = $orders->pluck('id')->values();

        $images = $orderIds->isNotEmpty()
            ? GeneratedImage::query()->whereIn('order_id', $orderIds)->latest('id')->get()->groupBy('order_id')
            : collect();
        $videos = Schema::hasTable('generated_videos') && $orderIds->isNotEmpty()
            ? GeneratedVideo::query()->whereIn('order_id', $orderIds)->latest('id')->get()->groupBy('order_id')
            : collect();
        $allocations = Schema::hasTable('finance_credit_allocations') && $orderIds->isNotEmpty()
            ? FinanceCreditAllocation::query()->with('financeCase.purchase')->whereIn('order_id', $orderIds)->latest('id')->get()->groupBy('order_id')
            : collect();
        $inputItems = $user->galleryItems()
            ->whereIn('source_type', self::INPUT_SOURCE_TYPES)
            ->latest('id')->get()
            ->filter(fn (UserGalleryItem $item): bool => is_numeric(data_get($item->metadata, 'order_id')))
            ->groupBy(fn (UserGalleryItem $item): string => (string) data_get($item->metadata, 'order_id'));

        $builds = $orders->map(function (Order $order) use ($images, $videos, $allocations, $inputItems): array {
            $orderImages = $images->get($order->id, collect());
            $orderVideos = $videos->get($order->id, collect());
            $orderAllocations = $allocations->get($order->id, collect());
            $case = $orderAllocations->first()?->financeCase;
            $inputMedia = $this->inputItemsForBuild($order, $inputItems->get((string) $order->id, collect()));
            $outputs = $orderImages->map(fn (GeneratedImage $image): array => ['type' => 'image', 'url' => $image->imageUrl(), 'label' => 'خروجی عکس'])
                ->concat($orderVideos->map(fn (GeneratedVideo $video): array => ['type' => 'video', 'url' => $video->playbackUrl(), 'label' => 'خروجی ویدیو']))
                ->filter(fn (array $output): bool => filled($output['url']))
                ->values()->all();
            $credits = (int) $orderAllocations->sum(fn (FinanceCreditAllocation $allocation): int => max(0, (int) $allocation->credits_used - (int) $allocation->credits_refunded));
            $prompt = $orderImages->first()?->user_prompt ?: $orderVideos->first()?->user_prompt;

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'date' => $order->completed_at ?: $order->created_at,
                'product_name' => $order->product?->name_fa ?: $order->product?->name_en ?: 'ساخت بدون محصول',
                'status' => $this->statusLabel($order->processing_status ?: $order->status),
                'status_key' => $order->processing_status ?: $order->status,
                'input_media' => $inputMedia,
                'outputs' => $outputs,
                'output_count' => count($outputs),
                'credits' => $credits > 0 ? $credits : (int) ($order->final_credits ?? 0),
                'revenue' => (float) $orderAllocations->sum('revenue_toman'),
                'prompt' => filled($prompt) ? Str::limit((string) $prompt, 150) : null,
                'case_number' => $case?->case_number,
                'case_url' => $case ? route('admin.finance.cases.show', $case) : null,
                'order_url' => route('admin.orders.show', $order),
            ];
        })->values()->all();

        $financeCases = Schema::hasTable('finance_cases')
            ? FinanceCase::query()->with(['purchase', 'lots'])->where('user_id', $user->id)->latest('started_at')->get()
            : collect();
        $userAllocations = $allocations->flatten(1);
        $financeSummary = [
            'cases' => $financeCases->count(),
            'purchases' => $financeCases->pluck('purchase')->filter()->unique('id')->count(),
            'paid' => (float) $financeCases->sum(fn (FinanceCase $case): float => (float) ($case->purchase?->paid_amount ?? 0)),
            'granted' => (int) $financeCases->sum(fn (FinanceCase $case): int => (int) $case->lots->sum('credits_granted')),
            'used' => (int) $userAllocations->sum(fn (FinanceCreditAllocation $allocation): int => max(0, (int) $allocation->credits_used - (int) $allocation->credits_refunded)),
            'revenue' => (float) $userAllocations->sum('revenue_toman'),
        ];

        return [$builds, $financeSummary];
    }

    private function inputItemsForBuild(Order $order, $items): array
    {
        $media = collect($items)->map(function (UserGalleryItem $item) use ($order): array {
            $mime = strtolower((string) $item->mime_type);
            $type = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'text/') ? 'text' : 'image');
            return [
                'type' => $type,
                'url' => $type === 'image'
                    ? route('admin.users.gallery.preview', [$order->user_id, $item->id])
                    : route('admin.users.gallery.original', [$order->user_id, $item->id]),
                'label' => $type === 'video' ? 'ویدیوی ورودی' : ($type === 'text' ? 'متن ورودی' : 'عکس ورودی'),
                'text' => $type === 'text' ? data_get($item->metadata, 'text') : null,
            ];
        })->values();

        if ($media->isNotEmpty()) {
            return $media->take(6)->all();
        }

        $payload = (array) $order->input_payload;
        $fallback = collect(array_values(array_unique(array_filter(
            array_merge((array) data_get($payload, 'source_upload_paths', []), [data_get($payload, 'source_upload_path')]),
            fn ($path): bool => is_scalar($path) && filled($path),
        ))))
            ->map(fn ($path): array => ['type' => 'image', 'url' => filter_var($path, FILTER_VALIDATE_URL) ? (string) $path : asset('storage/' . ltrim((string) $path, '/')), 'label' => 'عکس ورودی', 'text' => null]);
        $inputPrompt = data_get($payload, 'prompt') ?: data_get($payload, 'fields.prompt');
        if (filled($inputPrompt)) {
            $fallback->prepend(['type' => 'text', 'url' => route('admin.orders.show', $order), 'label' => 'متن ورودی', 'text' => (string) $inputPrompt]);
        }
        if (filled(data_get($payload, 'source_video_path'))) {
            $fallback->push(['type' => 'video', 'url' => asset('storage/' . ltrim((string) data_get($payload, 'source_video_path'), '/')), 'label' => 'ویدیوی ورودی', 'text' => null]);
        }
        if (filled(data_get($payload, 'source_video_url')) && ! filled(data_get($payload, 'source_video_path'))) {
            $fallback->push(['type' => 'video', 'url' => (string) data_get($payload, 'source_video_url'), 'label' => 'ویدیوی ورودی', 'text' => null]);
        }

        return $fallback->take(6)->values()->all();
    }

    private function statusLabel(?string $status): string
    {
        return [
            'completed' => 'موفق', 'success' => 'موفق', 'processing' => 'در حال پردازش',
            'queued' => 'در صف', 'failed' => 'ناموفق', 'stopped' => 'متوقف‌شده',
            'cancelled' => 'لغوشده', 'review' => 'نیازمند بررسی',
        ][$status ?: ''] ?? ($status ?: 'نامشخص');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'suggestions_enabled' => ['nullable', 'boolean'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'max_items_per_user' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_storage_mb' => ['required', 'integer', 'min:1', 'max:20480'],
            'free_recreations_per_month' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        UserGalleryConfig::current()->update([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'suggestions_enabled' => (bool) ($data['suggestions_enabled'] ?? false),
            'retention_days' => (int) $data['retention_days'],
            'max_items_per_user' => (int) $data['max_items_per_user'],
            'max_storage_mb' => (int) $data['max_storage_mb'],
            'free_recreations_per_month' => (int) $data['free_recreations_per_month'],
        ]);

        return back()->with('success', 'تنظیمات گالری شخصی ذخیره شد.');
    }

    public function preview(User $user, UserGalleryItem $item, UserGalleryService $gallery)
    {
        $this->ensureItemBelongsToUser($user, $item);

        return $gallery->response($item);
    }

    public function original(User $user, UserGalleryItem $item, UserGalleryService $gallery)
    {
        $this->ensureItemBelongsToUser($user, $item);

        return $gallery->response($item, true);
    }

    public function destroy(User $user, UserGalleryItem $item, UserGalleryService $gallery)
    {
        $this->ensureItemBelongsToUser($user, $item);
        $gallery->deleteItem($item);

        return back()->with('success', 'تصویر از گالری شخصی حذف شد.');
    }

    private function ensureItemBelongsToUser(User $user, UserGalleryItem $item): void
    {
        abort_unless((int) $item->user_id === (int) $user->id, 404);
        abort_unless(in_array($item->source_type, self::INPUT_SOURCE_TYPES, true), 404);
    }

    private function decorateItems($items): void
    {
        $items->getCollection()->transform(function (UserGalleryItem $item): UserGalleryItem {
            $mime = strtolower((string) $item->mime_type);
            $kind = str_starts_with($mime, 'image/')
                ? 'image'
                : (str_starts_with($mime, 'video/') ? 'video' : ($mime === 'text/plain' || str_starts_with($mime, 'text/') ? 'text' : 'file'));
            $item->setAttribute('input_kind', $kind);
            $item->setAttribute('input_label', match ($kind) {
                'image' => 'عکس ورودی',
                'video' => 'ویدیوی ورودی',
                'text' => 'متن ورودی',
                default => 'فایل ورودی',
            });
            if ($kind === 'text') {
                try {
                    $contents = Storage::disk($item->disk ?: 'user_gallery')->get($item->original_path);
                    $item->setAttribute('text_preview', Str::limit(trim($contents), 260));
                } catch (\Throwable) {
                    $item->setAttribute('text_preview', 'متن ورودی در دسترس نیست.');
                }
            }

            return $item;
        });
    }
}
