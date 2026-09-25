<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceCase;
use App\Models\FinanceCreditAllocation;
use App\Models\FaceProfile;
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
use App\Models\PlanPurchase;
use App\Models\ReferralConversion;
use App\Models\ReferralReward;
use App\Models\ReferralVisit;
use App\Services\UserGalleryService;
use App\Services\UserStorageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserGalleryController extends Controller
{
    private const INPUT_SOURCE_TYPES = ['upload', 'input_image', 'input_text', 'input_video'];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $mediaType = (string) $request->query('media_type', 'all');
        if (! in_array($mediaType, ['all', 'image', 'video'], true)) {
            $mediaType = 'all';
        }
        $sort = (string) $request->query('sort', 'newest');
        if (! in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }
        $from = $this->galleryDate($request->query('date_from'), false);
        $to = $this->galleryDate($request->query('date_to'), true);

        $hasGeneratedVideos = Schema::hasTable('generated_videos');

        // صفحهٔ گالری باید صاحبان خروجی‌های واقعی را هم نشان بدهد؛ قبلاً فقط
        // ردیف‌های ورودی از user_gallery_items در این فهرست خوانده می‌شدند.
        $galleryUsersQuery = User::query()
            ->where(function ($query) use ($hasGeneratedVideos, $mediaType, $from, $to): void {
                if ($mediaType !== 'video') {
                    $query->where(function ($imageQuery) use ($from, $to): void {
                        $imageQuery->whereHas('generatedImages', function ($outputQuery) use ($from, $to): void {
                            $outputQuery->whereNotNull('image_path');
                            $this->applyGalleryDateRange($outputQuery, $from, $to);
                        })->orWhereHas('galleryItems', function ($itemQuery) use ($from, $to): void {
                            $itemQuery->where('source_type', 'output_image');
                            $this->applyGalleryDateRange($itemQuery, $from, $to);
                        });
                    });
                }
                if ($mediaType !== 'image') {
                    $query->orWhere(function ($videoQuery) use ($hasGeneratedVideos, $from, $to): void {
                        if ($hasGeneratedVideos) {
                            $videoQuery->whereHas('generatedVideos', function ($outputQuery) use ($from, $to): void {
                                $outputQuery->where(function ($query): void {
                                    $query->whereNotNull('video_path')->orWhereNotNull('video_url');
                                });
                                $this->applyGalleryDateRange($outputQuery, $from, $to);
                            });
                            $videoQuery->orWhereHas('galleryItems', function ($itemQuery) use ($from, $to): void {
                                $itemQuery->where('source_type', 'output_video');
                                $this->applyGalleryDateRange($itemQuery, $from, $to);
                            });
                        } else {
                            $videoQuery->whereHas('galleryItems', function ($itemQuery) use ($from, $to): void {
                                $itemQuery->where('source_type', 'output_video');
                                $this->applyGalleryDateRange($itemQuery, $from, $to);
                            });
                        }
                    });
                }
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount(['generatedImages' => fn ($query) => $query->whereNotNull('image_path')]);
        if ($hasGeneratedVideos) {
            $galleryUsersQuery->withCount(['generatedVideos' => function ($query): void {
                $query->where(function ($outputQuery): void {
                    $outputQuery->whereNotNull('video_path')->orWhereNotNull('video_url');
                });
            }]);
        }
        $sort === 'oldest' ? $galleryUsersQuery->oldest('id') : $galleryUsersQuery->latest('id');
        $galleryUsers = $galleryUsersQuery->paginate(24, ['*'], 'gallery_page')->withQueryString();
        $galleryCards = $this->buildGalleryCards($galleryUsers->getCollection(), $hasGeneratedVideos, $mediaType, $from, $to);

        $config = UserGalleryConfig::current();
        $stats = [
            'items' => UserGalleryItem::whereIn('source_type', self::INPUT_SOURCE_TYPES)->count(),
            'input_images' => UserGalleryItem::whereIn('source_type', ['upload', 'input_image'])->count(),
            'users' => UserGalleryItem::query()->whereIn('source_type', self::INPUT_SOURCE_TYPES)->distinct('user_id')->count('user_id'),
            'active' => UserGalleryItem::whereIn('source_type', self::INPUT_SOURCE_TYPES)->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'storage' => (int) UserGalleryItem::whereIn('source_type', self::INPUT_SOURCE_TYPES)->sum('size'),
            'input_image_storage' => (int) UserGalleryItem::whereIn('source_type', ['upload', 'input_image'])->sum('size'),
            'generated_images' => Schema::hasTable('generated_images') ? GeneratedImage::whereNotNull('image_path')->count() : 0,
            'generated_image_storage' => Schema::hasTable('generated_images') ? (int) GeneratedImage::sum('size') : 0,
            'generated_videos' => Schema::hasTable('generated_videos') ? GeneratedVideo::where(function ($query): void {
                $query->whereNotNull('video_path')->orWhereNotNull('video_url');
            })->count() : 0,
            'generated_video_storage' => Schema::hasTable('generated_videos') ? (int) GeneratedVideo::sum('size') : 0,
            'face_profiles' => Schema::hasTable('face_profiles') ? FaceProfile::active()->count() : 0,
            'face_storage' => Schema::hasTable('face_profiles')
                ? (int) FaceProfile::active()->get()->sum(fn (FaceProfile $profile): int => collect($profile->referenceImageEntries())->sum(fn (array $image): int => (int) ($image['size'] ?? 0)))
                : 0,
            'suggestions' => UserGallerySuggestion::count(),
            'recreations' => UserGalleryRecreation::where('status', 'completed')->count(),
            'campaigns' => UserGalleryCampaign::count(),
            'costs' => (int) UserGalleryCostEvent::sum('cost_toman'),
        ];

        return view('admin.users.gallery.index', compact('config', 'stats', 'search', 'mediaType', 'sort', 'from', 'to', 'galleryUsers', 'galleryCards'));
    }

    /** فهرست مستقل کارکتر شیت‌های کاربران برای مدیریت سریع ادمین. */
    public function faceProfiles(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'active');
        if (! in_array($status, ['all', 'active', 'deleted'], true)) {
            $status = 'active';
        }

        $profiles = FaceProfile::query()
            ->with('user:id,name,last_name,phone,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($profileQuery) use ($search): void {
                    $profileQuery->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(24, ['*'], 'profiles_page')
            ->withQueryString();

        $stats = [
            'all' => FaceProfile::count(),
            'active' => FaceProfile::active()->count(),
            'deleted' => FaceProfile::where('status', 'deleted')->count(),
            'users' => FaceProfile::active()->distinct('user_id')->count('user_id'),
        ];

        return view('admin.users.face-profiles.index', compact('profiles', 'search', 'status', 'stats'));
    }

    /** افزودن کارکتر شیت برای کاربر از صفحهٔ گالری همان کاربر. */
    public function storeFaceProfile(Request $request, User $user)
    {
        $limit = $user->faceProfileLimit();
        if ($user->faceProfiles()->active()->count() >= $limit) {
            return back()->withErrors(['face_profile' => "این کاربر به سقف {$limit} کارکتر شیت فعال رسیده است."]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'images' => ['required', 'array', 'min:1', 'max:3'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $incomingBytes = collect($request->file('images', []))
            ->sum(fn ($image): int => (int) $image->getSize());
        $storage = app(UserStorageService::class);
        $storageCheck = $storage->check($user, $incomingBytes);
        if (! $storageCheck['allowed']) {
            return back()->withErrors(['face_profile' => $storage->blockMessage($storageCheck)]);
        }

        $referenceImages = [];
        foreach ($request->file('images', []) as $image) {
            $path = $image->store('face-profiles', 'public');
            $referenceImages[] = [
                'path' => $path,
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
            ];
        }

        $user->faceProfiles()->create([
            'name' => trim((string) $validated['name']),
            'reference_images' => $referenceImages,
            'status' => 'active',
        ]);

        return back()->with('success', 'کارکتر شیت برای کاربر با موفقیت اضافه شد.');
    }

    /** تغییر نام کارکتر شیت از پنل ادمین. */
    public function updateFaceProfile(Request $request, User $user, FaceProfile $faceProfile)
    {
        $this->ensureFaceProfileBelongsToUser($user, $faceProfile);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);
        $faceProfile->update(['name' => trim((string) $validated['name'])]);

        return back()->with('success', 'نام کارکتر شیت با موفقیت تغییر کرد.');
    }

    /** حذف کارکتر شیت و فایل‌های مرجع آن از پنل ادمین. */
    public function destroyFaceProfile(User $user, FaceProfile $faceProfile)
    {
        $this->ensureFaceProfileBelongsToUser($user, $faceProfile);

        foreach ($faceProfile->referenceImageEntries() as $image) {
            if (! empty($image['path'])) {
                Storage::disk('public')->delete($image['path']);
            }
        }

        $faceProfile->update(['status' => 'deleted']);

        return back()->with('success', 'کارکتر شیت از حساب کاربر حذف شد.');
    }

    private function ensureFaceProfileBelongsToUser(User $user, FaceProfile $faceProfile): void
    {
        abort_unless((int) $faceProfile->user_id === (int) $user->id, 404);
    }

    private function galleryDate(mixed $value, bool $endOfDay): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
            if (! $date || $date->format('Y-m-d') !== $value) {
                return null;
            }

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyGalleryDateRange($query, ?Carbon $from, ?Carbon $to): void
    {
        $query
            ->when($from, fn ($builder) => $builder->where('created_at', '>=', $from))
            ->when($to, fn ($builder) => $builder->where('created_at', '<=', $to));
    }

    /** کارت‌های قبل/بعد را برای همهٔ کاربران دارای خروجی ساخت آماده می‌کند. */
    private function buildGalleryCards($users, bool $hasGeneratedVideos, string $mediaType = 'all', ?Carbon $from = null, ?Carbon $to = null): array
    {
        $userIds = collect($users)->pluck('id')->values();
        if ($userIds->isEmpty()) {
            return [];
        }

        $images = $mediaType === 'video'
            ? collect()
            : GeneratedImage::query()
                ->whereIn('user_id', $userIds)
                ->whereNotNull('image_path')
                ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
                ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
                ->latest('id')
                ->get();
        $videos = $hasGeneratedVideos && $mediaType !== 'image'
            ? GeneratedVideo::query()->whereIn('user_id', $userIds)
                ->where(function ($query): void {
                    $query->whereNotNull('video_path')->orWhereNotNull('video_url');
                })
                ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
                ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
                ->latest('id')->get()
            : collect();
        $outputOrderIds = $images->pluck('order_id')->merge($videos->pluck('order_id'))->filter()->unique()->values();
        $outputItems = UserGalleryItem::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('source_type', $mediaType === 'image' ? ['output_image'] : ($mediaType === 'video' ? ['output_video'] : ['output_image', 'output_video']))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->latest('id')
            ->get();
        $outputOrderIds = $outputOrderIds
            ->merge($outputItems->map(fn (UserGalleryItem $item) => data_get($item->metadata, 'order_id'))->filter())
            ->filter()->unique()->values();
        // فقط سفارش‌هایی را بخوان که واقعاً خروجیِ همین صفحه به آن‌ها متصل است؛
        // مرتب‌سازی کل جدول orders روی دیتاست بزرگ باعث خطای sort memory می‌شد.
        $ordersByUser = $outputOrderIds->isNotEmpty()
            ? Order::query()->whereIn('id', $outputOrderIds)->with('product')->get()->sortByDesc('created_at')->groupBy('user_id')
            : collect();
        $inputItems = UserGalleryItem::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('source_type', self::INPUT_SOURCE_TYPES)
            ->latest('id')
            ->get()
            ->groupBy('user_id');
        $imagesByOrder = $images->filter(fn (GeneratedImage $image): bool => filled($image->order_id))->groupBy('order_id');
        $videosByOrder = $videos->filter(fn (GeneratedVideo $video): bool => filled($video->order_id))->groupBy('order_id');
        $outputItemsByOrder = $outputItems
            ->filter(fn (UserGalleryItem $item): bool => is_numeric(data_get($item->metadata, 'order_id')))
            ->groupBy(fn (UserGalleryItem $item): string => (string) data_get($item->metadata, 'order_id'));

        return collect($users)->map(function (User $user) use ($ordersByUser, $images, $videos, $outputItems, $inputItems, $imagesByOrder, $videosByOrder, $outputItemsByOrder): array {
            $userImages = $images->where('user_id', $user->id);
            $userVideos = $videos->where('user_id', $user->id);
            $userOutputItems = $outputItems->where('user_id', $user->id);
            $consumedImageIds = collect();
            $consumedVideoIds = collect();
            $pairs = collect($ordersByUser->get($user->id, collect()))->map(function (Order $order) use ($inputItems, $imagesByOrder, $videosByOrder, $outputItemsByOrder, &$consumedImageIds, &$consumedVideoIds): ?array {
                $orderImages = collect($imagesByOrder->get($order->id, collect()));
                $orderVideos = collect($videosByOrder->get($order->id, collect()));
                $snapshots = collect($outputItemsByOrder->get((string) $order->id, collect()));
                $snapshotImageIds = $snapshots->where('source_type', 'output_image')->pluck('source_id')->map(fn ($id) => (int) $id);
                $snapshotVideoIds = $snapshots->where('source_type', 'output_video')->pluck('source_id')->map(fn ($id) => (int) $id);
                $outputs = $snapshots->map(function (UserGalleryItem $item) use ($order): array {
                    $isVideo = $item->source_type === 'output_video';
                    return [
                        'type' => $isVideo ? 'video' : 'image',
                        'url' => $isVideo
                            ? route('admin.users.gallery.original', [$order->user_id, $item->id])
                            : route('admin.users.gallery.preview', [$order->user_id, $item->id]),
                        'label' => $isVideo ? 'خروجی ویدیو' : 'خروجی عکس',
                        'id' => $item->id,
                    ];
                })->concat($orderImages->reject(fn (GeneratedImage $image): bool => $snapshotImageIds->contains((int) $image->id))->map(fn (GeneratedImage $image): array => ['type' => 'image', 'url' => $image->imageUrl(), 'label' => 'خروجی عکس', 'id' => $image->id]))
                    ->concat($orderVideos->reject(fn (GeneratedVideo $video): bool => $snapshotVideoIds->contains((int) $video->id))->map(fn (GeneratedVideo $video): array => ['type' => 'video', 'url' => $video->playbackUrl(), 'label' => 'خروجی ویدیو', 'id' => $video->id]))
                    ->filter(fn (array $output): bool => filled($output['url']))->values();
                if ($outputs->isEmpty()) {
                    return null;
                }
                $consumedImageIds = $consumedImageIds->merge($orderImages->pluck('id'));
                $consumedVideoIds = $consumedVideoIds->merge($orderVideos->pluck('id'));
                $before = collect($this->inputItemsForBuild($order, collect($inputItems->get($order->user_id, collect()))
                    ->filter(fn (UserGalleryItem $item): bool => (int) data_get($item->metadata, 'order_id') === (int) $order->id)))
                    ->filter(fn (array $media): bool => in_array($media['type'], ['image', 'video'], true))->values();

                return [
                    'product_name' => $order->product?->name_fa ?: $order->product?->name_en ?: 'ساخت بدون محصول',
                    'date' => $order->completed_at ?: $order->created_at,
                    'order_url' => route('admin.orders.show', $order),
                    'before' => $before->take(4)->all(),
                    'after' => $outputs->take(4)->all(),
                ];
            })->filter()->values();

            // خروجی‌هایی که سفارششان حذف شده یا به سفارش متصل نشده‌اند نیز گم نشوند.
            $orphanOutputs = $userOutputItems->filter(fn (UserGalleryItem $item): bool => ! is_numeric(data_get($item->metadata, 'order_id')))
                ->map(function (UserGalleryItem $item) use ($user): array {
                    $isVideo = $item->source_type === 'output_video';
                    return [
                        'type' => $isVideo ? 'video' : 'image',
                        'url' => $isVideo ? route('admin.users.gallery.original', [$user->id, $item->id]) : route('admin.users.gallery.preview', [$user->id, $item->id]),
                        'label' => $isVideo ? 'خروجی ویدیو' : 'خروجی عکس',
                    ];
                })
                ->concat($userImages->reject(fn (GeneratedImage $image): bool => $consumedImageIds->contains($image->id))
                ->map(fn (GeneratedImage $image): array => ['type' => 'image', 'url' => $image->imageUrl(), 'label' => 'خروجی عکس'])
                ->concat($userVideos->reject(fn (GeneratedVideo $video): bool => $consumedVideoIds->contains($video->id))
                    ->map(fn (GeneratedVideo $video): array => ['type' => 'video', 'url' => $video->playbackUrl(), 'label' => 'خروجی ویدیو'])))
                ->filter(fn (array $output): bool => filled($output['url']))->values();
            if ($orphanOutputs->isNotEmpty()) {
                $pairs->push([
                    'product_name' => 'خروجی‌های بدون سفارش متصل',
                    'date' => $orphanOutputs->first()['type'] === 'image' ? $userImages->first()?->created_at : $userVideos->first()?->created_at,
                    'order_url' => route('admin.users.gallery.show', $user),
                    'before' => [],
                    'after' => $orphanOutputs->take(4)->all(),
                ]);
            }

            return [
                'user_id' => $user->id,
                'user_name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'کاربر بدون نام',
                'user_phone' => $user->phone,
                'output_count' => max((int) $user->generated_images_count + (int) ($user->generated_videos_count ?? 0), $userOutputItems->count()),
                'pairs' => $pairs->take(6)->all(),
            ];
        })->values()->all();
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
        $faceProfiles = $user->faceProfiles()->active()->latest()->get();
        [$builds, $financeSummary] = $this->buildUserActivity($user);
        $referralReport = $this->buildReferralReport($user);
        $shortcutLinks = [
            ['label' => 'پروفایل کاربر', 'description' => 'مشخصات، وضعیت و سوابق کاربر', 'icon' => 'fa-user', 'class' => 'primary', 'url' => route('admin.users.index', ['show_user' => $user->id])],
            ['label' => 'گزارش مالی', 'description' => 'پرونده‌های خرید و اعتبار', 'icon' => 'fa-chart-pie', 'class' => 'success', 'url' => route('admin.finance.cases.index', ['user_id' => $user->id])],
            ['label' => 'سفارش‌ها', 'description' => 'تمام سفارش‌ها و وضعیت ساخت', 'icon' => 'fa-receipt', 'class' => 'warning', 'url' => route('admin.orders.index', ['user_id' => $user->id])],
            ['label' => 'اعتبار سرویس‌ها', 'description' => 'مصرف مدل‌ها و هزینه اجرا', 'icon' => 'fa-bolt', 'class' => 'info', 'url' => route('admin.service-credits.index', ['q' => $user->phone ?: $user->email ?: $user->id])],
            ['label' => 'کارکتر شیت‌ها', 'description' => 'مشاهده و مدیریت مرجع‌های چهره', 'icon' => 'fa-user', 'class' => 'success', 'url' => '#face-profiles'],
            ['label' => 'گزارش همکاری در فروش', 'description' => 'کلیک، ثبت‌نام، خرید و پاداش قابل ارسال', 'icon' => 'fa-chart-line', 'class' => 'primary', 'url' => route('admin.users.gallery.referral-report', $user)],
            ['label' => 'لاگ فعالیت', 'description' => 'ورودها و رخدادهای حساب', 'icon' => 'fa-clock-rotate-left', 'class' => 'neutral', 'url' => route('admin.users.logs', $user->id)],
        ];

        return view('admin.users.gallery.show', compact('user', 'items', 'setting', 'faceProfiles', 'builds', 'financeSummary', 'referralReport', 'shortcutLinks'));
    }

    public function referralReport(User $user)
    {
        return view('admin.users.gallery.referral-report', [
            'user' => $user,
            'report' => $this->buildReferralReport($user),
        ]);
    }

    public function referralReportCsv(User $user): StreamedResponse
    {
        $report = $this->buildReferralReport($user);
        $filename = 'referral-report-'.$user->id.'.csv';

        return response()->streamDownload(function () use ($report, $user): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['گزارش همکاری در فروش', trim(($user->name ?? '').' '.($user->last_name ?? ''))]);
            fputcsv($handle, ['شاخص', 'مقدار']);
            fputcsv($handle, ['کلیک کل', $report['summary']['clicks']]);
            fputcsv($handle, ['بازدیدکننده یکتا', $report['summary']['unique_clicks']]);
            fputcsv($handle, ['ثبت‌نام', $report['summary']['registrations']]);
            fputcsv($handle, ['خرید موفق', $report['summary']['purchases']]);
            fputcsv($handle, ['ساخت مخاطبان', $report['summary']['outputs']]);
            fputcsv($handle, ['نرخ ثبت‌نام', $report['summary']['registration_rate'].'٪']);
            fputcsv($handle, ['پاداش پرداخت‌شده همکار', $report['rewards']['own_paid'].' اعتبار']);
            fputcsv($handle, ['پاداش پرداخت‌شده مخاطبان', $report['rewards']['invitee_paid'].' اعتبار']);
            fputcsv($handle, ['جمع پاداش پرداخت‌شده', $report['rewards']['combined_paid'].' اعتبار']);
            fputcsv($handle, ['کمیسیون پرداخت‌شده', $report['rewards']['commission_paid'].' تومان']);
            fputcsv($handle, []);
            fputcsv($handle, ['لینک', 'نوع', 'کلیک', 'ثبت‌نام', 'خرید موفق', 'ساخت مخاطبان']);
            foreach ($report['links'] as $link) {
                fputcsv($handle, [$link['url'] ?: 'کد دعوت ندارد', $link['label'], $link['clicks'], $link['registrations'], $link['purchases'], $link['outputs']]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['مخاطب', 'لینک/محصول', 'وضعیت دعوت', 'خرید موفق', 'پاداش مخاطب', 'تاریخ']);
            foreach ($report['conversions'] as $conversion) {
                fputcsv($handle, [$conversion['invitee'], $conversion['link'], $conversion['status'], $conversion['purchased'] ? 'بله' : 'خیر', $conversion['invitee_reward'].' اعتبار', $conversion['date']?->format('Y/m/d H:i') ?? '']);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildReferralReport(User $user): array
    {
        $links = $user->referralLinks()->with('product:id,name_fa,name_en,slug')->latest('id')->get();
        $visits = ReferralVisit::query()->where('inviter_id', $user->id)->get();
        $conversions = ReferralConversion::query()
            ->where('inviter_id', $user->id)
            ->with(['invitee:id,name,last_name,phone', 'link.product:id,name_fa,name_en', 'rewards'])
            ->latest('id')
            ->get();
        $inviteeIds = $conversions->pluck('invitee_id')->filter()->unique()->values();
        $purchases = $inviteeIds->isNotEmpty()
            ? PlanPurchase::query()->whereIn('user_id', $inviteeIds)->where('status', 'completed')->get(['user_id', 'paid_amount', 'purchased_at'])
            : collect();
        $outputs = $inviteeIds->isNotEmpty()
            ? GeneratedImage::query()->whereIn('user_id', $inviteeIds)->whereNotNull('image_path')->get(['user_id'])
            : collect();
        $outputVideos = Schema::hasTable('generated_videos') && $inviteeIds->isNotEmpty()
            ? GeneratedVideo::query()->whereIn('user_id', $inviteeIds)->where(function ($query): void { $query->whereNotNull('video_path')->orWhereNotNull('video_url'); })->get(['user_id'])
            : collect();

        $purchaseUserIds = $purchases->pluck('user_id')->unique();
        $linkMetrics = function (?int $linkId) use ($visits, $conversions, $purchaseUserIds, $outputs, $outputVideos): array {
            $linkConversions = $conversions->filter(fn (ReferralConversion $conversion): bool => $linkId === null
                ? $conversion->link_id === null
                : (int) $conversion->link_id === $linkId);
            $linkInviteeIds = $linkConversions->pluck('invitee_id')->filter();

            return [
                'clicks' => $visits->filter(fn (ReferralVisit $visit): bool => $linkId === null ? $visit->link_id === null : (int) $visit->link_id === $linkId)->count(),
                'registrations' => $linkConversions->count(),
                'purchases' => $linkInviteeIds->intersect($purchaseUserIds)->count(),
                'outputs' => $outputs->whereIn('user_id', $linkInviteeIds)->count() + $outputVideos->whereIn('user_id', $linkInviteeIds)->count(),
            ];
        };

        $reportLinks = collect();
        if ($user->referral_code) {
            $metrics = $linkMetrics(null);
            $reportLinks->push([
                'label' => 'لینک عادی',
                'url' => route('referral.visit', ['code' => $user->referral_code]),
                'metrics' => $metrics,
                'clicks' => $metrics['clicks'],
                'registrations' => $metrics['registrations'],
                'purchases' => $metrics['purchases'],
                'outputs' => $metrics['outputs'],
            ]);
        }
        foreach ($links as $link) {
            $metrics = $linkMetrics((int) $link->id);
            $reportLinks->push([
                'label' => $link->product?->name_fa ?: $link->product?->name_en ?: 'محصول حذف‌شده',
                'url' => route('referral.link', ['referralLink' => $link->slug]),
                'metrics' => $metrics,
                'clicks' => $metrics['clicks'],
                'registrations' => $metrics['registrations'],
                'purchases' => $metrics['purchases'],
                'outputs' => $metrics['outputs'],
                'active' => $link->isActive(),
            ]);
        }

        $ownRewards = ReferralReward::query()->where('user_id', $user->id)->whereIn('reward_type', ['inviter_reward', 'purchase_reward']);
        $inviteeRewards = $inviteeIds->isNotEmpty()
            ? ReferralReward::query()->whereIn('user_id', $inviteeIds)->where('reward_type', 'invitee_reward')
            : ReferralReward::query()->whereRaw('1 = 0');
        $commissionRewards = ReferralReward::query()->where('user_id', $user->id)->whereIn('reward_type', ['purchase_commission', 'purchase_commission_reversal'])->where('currency', 'IRT');
        $rewardSum = static function ($query, string $status, bool $signed = false): int {
            return (int) $query->clone()->where('status', $status)->get()->sum(function (ReferralReward $reward) use ($signed): int {
                return $signed && $reward->direction === 'debit' ? -(int) $reward->amount : (int) $reward->amount;
            });
        };
        $ownPaid = $rewardSum($ownRewards, 'paid');
        $ownPending = $rewardSum($ownRewards, 'pending');
        $inviteePaid = $rewardSum($inviteeRewards, 'paid');
        $inviteePending = $rewardSum($inviteeRewards, 'pending');
        $commissionPaid = $rewardSum($commissionRewards, 'paid', true);
        $commissionPending = $rewardSum($commissionRewards, 'pending', true);

        $summary = [
            'clicks' => $visits->count(),
            'unique_clicks' => $visits->pluck('visitor_token')->filter()->unique()->count(),
            'registrations' => $conversions->count(),
            'purchases' => $purchaseUserIds->count(),
            'outputs' => $outputs->count() + $outputVideos->count(),
        ];
        $summary['registration_rate'] = $summary['clicks'] > 0 ? round(($summary['registrations'] / $summary['clicks']) * 100, 1) : 0;

        return [
            'summary' => $summary,
            'rewards' => [
                'own_paid' => $ownPaid,
                'own_pending' => $ownPending,
                'invitee_paid' => $inviteePaid,
                'invitee_pending' => $inviteePending,
                'combined_paid' => $ownPaid + $inviteePaid,
                'commission_paid' => $commissionPaid,
                'commission_pending' => $commissionPending,
            ],
            'links' => $reportLinks->map(fn (array $link): array => $link + $link['metrics'])->all(),
            'conversions' => $conversions->map(function (ReferralConversion $conversion): array {
                $invitee = $conversion->invitee;
                $inviteeReward = $conversion->rewards
                    ->where('reward_type', 'invitee_reward')
                    ->filter(fn (ReferralReward $reward): bool => in_array($reward->currency, [null, 'token'], true))
                    ->sum('amount');
                return [
                    'invitee' => trim(($invitee?->name ?? '').' '.($invitee?->last_name ?? '')) ?: 'کاربر بدون نام',
                    'link' => $conversion->link?->product?->name_fa ?: $conversion->link?->product?->name_en ?: 'لینک عادی',
                    'status' => match ($conversion->status) { 'qualified' => 'معتبر', 'under_review' => 'نیازمند بررسی', 'rejected' => 'ردشده', default => $conversion->status },
                    'purchased' => $conversion->first_purchase_at !== null,
                    'invitee_reward' => (int) $inviteeReward,
                    'date' => $conversion->created_at,
                ];
            })->all(),
        ];
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
            'retention_days' => ['required', 'integer', Rule::in([0, 7, 30, 90, 180, 270, 365])],
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

    public function bulkDestroy(Request $request, UserGalleryService $gallery)
    {
        $validated = $request->validate([
            'item_ids' => ['nullable', 'array', 'max:100'],
            'item_ids.*' => ['integer'],
            'user_ids' => ['nullable', 'array', 'max:100'],
            'user_ids.*' => ['integer'],
            'select_all' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', 'active', 'expired'])],
        ]);

        $query = UserGalleryItem::query()->whereIn('source_type', self::INPUT_SOURCE_TYPES);
        if (! empty($validated['select_all'])) {
            $status = (string) ($validated['status'] ?? 'all');
            if ($status === 'active') {
                $query->where(function ($expiryQuery): void {
                    $expiryQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            } elseif ($status === 'expired') {
                $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
            }
            $search = trim((string) ($validated['q'] ?? ''));
            if ($search !== '') {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
        } elseif (! empty($validated['user_ids'])) {
            $query->whereIn('user_id', array_values(array_filter((array) $validated['user_ids'])));
        } else {
            $ids = array_values(array_filter((array) ($validated['item_ids'] ?? [])));
            if ($ids === []) {
                return back()->withErrors(['item_ids' => 'حداقل یک ورودی برای حذف انتخاب کنید.']);
            }
            $query->whereIn('id', $ids);
        }

        $deleted = 0;
        $query->orderBy('id')->chunkById(100, function ($items) use ($gallery, &$deleted): void {
            foreach ($items as $item) {
                $gallery->deleteItem($item);
                $deleted++;
            }
        });

        return back()->with('success', "{$deleted} ورودی از گالری کاربران حذف شد.");
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
