<?php

namespace App\Http\Controllers;

use App\Models\ReferralConversion;
use App\Models\ReferralEvent;
use App\Models\ReferralLink;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\Product;
use App\Models\User;
use App\Models\FaceProfile;
use App\Models\GeneratedImage;
use App\Models\UserUpload;
use App\Models\GeneratedVideo;
use App\Models\SalesPartnerLead;
use App\Services\CustomerJourneyService;
use App\Services\UserStorageService;
use App\Services\ProfileMediaThumbnailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ProfileController extends Controller
{

public function gallery()
{
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('app.profile');
    }

    // گالری مستقل است؛ هر دو لیست صفحه‌بندی می‌شوند تا با رشد تاریخچه،
    // همهٔ فایل‌ها و متن‌ها در اولین پاسخ خوانده نشوند.
    $createdImages = $user->generatedImages()
        ->select(['id', 'user_id', 'image_path', 'user_prompt', 'created_at'])
        ->whereNotNull('image_path')
        ->latest()
        ->paginate(24, ['*'], 'images_page')
        ->withQueryString();
    $galleryItems = $user->galleryItems()
        ->select(['id', 'user_id', 'disk', 'mime_type', 'original_path', 'preview_path', 'metadata', 'created_at'])
        ->latest()
        ->paginate(24, ['*'], 'gallery_page')
        ->withQueryString();
    $galleryItems->each(function ($item): void {
        if (str_starts_with(strtolower((string) $item->mime_type), 'text/')) {
            try {
                $item->setAttribute('display_text', Storage::disk($item->disk ?: 'user_gallery')->get($item->original_path));
            } catch (\Throwable) {
                $item->setAttribute('display_text', data_get($item->metadata, 'text', 'متن ورودی در دسترس نیست.'));
            }
        }
    });
    $galleryService = app(\App\Services\UserGalleryService::class);
    $galleryConfig = $galleryService->config();
    $gallerySetting = $galleryService->setting($user);

    return view('app.gallery', compact('createdImages', 'galleryItems', 'galleryConfig', 'gallerySetting'));
}

    public function index()
    {
        // گرفتن اطلاعات دقیق کاربر لاگین شده فعلی
        $user = Auth::user();
        $referralSettings = ReferralSetting::current();
        $referralProfileEnabled = (bool) $referralSettings->profile_enabled;

        if (!$user) {
            // مهمان (وارد نشده): صفحه پروفایل با داده‌های پیش‌فرض/خالی نمایش داده می‌شود
            // به‌جای ریدایرکت خودکار به لاگین — کاربر فقط با کلیک صریح روی
            // «ورود و ثبت‌نام» داخل خود صفحه پروفایل به لاگین هدایت می‌شود.
            return view('app.profile', [
                'isGuest'        => true,
                'createdImages'  => collect(),
                'createdVideos'  => collect(),
                'createdMedia'   => collect(),
                'usedProducts'   => collect(),
                'personalImages' => collect(),
                'faceProfiles'   => collect(),
                'galleryItems'   => collect(),
                'savedProducts'  => collect(),
                'storageUsed'    => 0,
                'storageTotal'   => 100,
                'tokenBalance'   => 0,
                'createdCount'   => 0,
                'planName'       => 'رایگان',
                'earnings'       => 0,
                'referralSettings' => $referralSettings,
                'referralProfileEnabled' => $referralProfileEnabled,
                'referralData' => $this->emptyReferralData(),
                'referralProducts' => collect(),
                'creatorRewardProducts' => collect(),
                'creatorRewardCredits' => 0,
                'profileRewardTotal' => 0,
            ]);
        }

        $productPreviewColumns = 'id,name_fa,name_en,slug,product_code';
        $mediaPage = $this->mediaPage($user, null, 20, $productPreviewColumns);
        $createdImages = $mediaPage['createdImages'];
        $createdVideos = $mediaPage['createdVideos'];
        $createdMedia = $mediaPage['createdMedia'];
        $initialMediaCursor = $mediaPage['nextCursor'];

        // فقط شمارنده‌های سبک برای هدر خوانده می‌شوند؛ خود مجموعه‌ها در تب مربوطه می‌آیند.
        $createdCount = (int) $user->generatedImages()->whereNotNull('image_path')->count();
        if (Schema::hasTable('generated_videos')) {
            $createdCount += (int) $user->generatedVideos()
                ->where(function ($query): void {
                    $query->whereNotNull('video_path')->orWhereNotNull('video_url');
                })->count();
        }

        // محاسبهٔ فضای پروفایل برای نمایش cache می‌شود؛ مسیرهای کنترل سهمیه همچنان تازه می‌خوانند.
        $totalBytes = (int) app(UserStorageService::class)->profileSnapshot($user)['used'];

        // تبدیل دقیق بایت به مگابایت با رند کردن تا ۲ رقم اعشار
        $storageUsed = round($totalBytes / (1024 * 1024), 2);
        $storageTotal = 100; // سقف مجاز ۱۰۰ مگابایت

        // ───── داده‌های واقعی باکس‌های آمار پروفایل ─────
        $tokenBalance  = $user->token_balance;
        $planName      = optional($user->plan)->name ?? 'رایگان';
        $earnings = $this->profileEarnings($user, $referralProfileEnabled);
        $creatorRewardCredits = $this->creatorRewardCredits($user, $referralProfileEnabled);
        $profileRewardTotal = (int) $earnings + (int) $creatorRewardCredits;
        $isGuest       = false;
        return view('app.profile', compact(
            'createdImages',
            'createdVideos',
            'createdMedia',
            'storageUsed',
            'storageTotal',
            'tokenBalance',
            'createdCount',
            'planName',
            'earnings',
            'isGuest',
            'referralSettings',
            'referralProfileEnabled',
            'creatorRewardCredits',
            'profileRewardTotal',
            'initialMediaCursor'
        ));
    }

    /** درخواست همکاری فروش از مسیر کاربر عادی؛ با تأیید صریح کاربر وارد صف فروش می‌شود. */
    public function requestPartnerProgram(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        if (! Schema::hasTable('sales_partner_leads') || ! Schema::hasColumn('sales_partner_leads', 'user_id')) {
            return back()->with('error', 'مسیر همکاری فروش هنوز برای این محیط فعال نشده است.');
        }

        $existing = SalesPartnerLead::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('success', 'درخواست همکاری تو قبلاً ثبت شده و تیم فروش آن را پیگیری می‌کند.');
        }

        $name = trim(implode(' ', array_filter([(string) $user->name, (string) $user->last_name])));
        $name = $name !== '' ? $name : 'کاربر سایت';

        SalesPartnerLead::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'handle' => 'user:'.$user->id,
            'channel' => 'other',
            'source' => 'user_profile',
            'profile_url' => route('app.profile'),
            'stage' => 4,
            'stage_changed_at' => now(),
            'status' => 'active',
            'priority' => 'high',
            'next_follow_up_at' => now(),
            'notes' => 'درخواست همکاری فروش از مسیر کاربر عادی ثبت شده است.',
        ]);

        return back()->with('success', 'درخواست همکاری ثبت شد. تیم فروش به‌زودی با تو هماهنگ می‌کند.');
    }

    private function journeyData(User $user, int $createdCount): array
    {
        $imageCount = (int) $user->generatedImages()->whereNotNull('image_path')->count();
        $videoCount = Schema::hasTable('generated_videos')
            ? (int) $user->generatedVideos()->where(function ($query): void {
                $query->whereNotNull('video_path')->orWhereNotNull('video_url');
            })->count()
            : 0;
        $firstContentAt = $user->generatedImages()->whereNotNull('image_path')->min('created_at');
        if (Schema::hasTable('generated_videos')) {
            $firstVideoAt = $user->generatedVideos()
                ->where(function ($query): void {
                    $query->whereNotNull('video_path')->orWhereNotNull('video_url');
                })->min('created_at');
            if ($firstVideoAt && (! $firstContentAt || $firstVideoAt < $firstContentAt)) {
                $firstContentAt = $firstVideoAt;
            }
        }
        $firstContentAt = $firstContentAt ? Carbon::parse($firstContentAt) : null;

        $linkCount = Schema::hasTable('referral_links')
            ? (int) $user->referralLinks()->count()
            : 0;
        $conversionCount = Schema::hasTable('referral_conversions')
            ? (int) $user->referralConversions()->count()
            : 0;
        $purchaseCount = Schema::hasTable('plan_purchases')
            ? (int) $user->planPurchases()->where('status', 'completed')->count()
            : 0;
        $firstPurchaseAt = Schema::hasTable('plan_purchases')
            ? $user->planPurchases()->where('status', 'completed')->oldest('purchased_at')->value('purchased_at')
            : null;
        $existingPartnerLead = Schema::hasTable('sales_partner_leads')
            && Schema::hasColumn('sales_partner_leads', 'user_id')
            ? SalesPartnerLead::query()->where('user_id', $user->id)->where('status', 'active')->first()
            : null;
        $customerJourney = Schema::hasTable('customer_journeys')
            ? app(CustomerJourneyService::class)->sync($user, 'بازدید پروفایل کاربر')
            : null;
        $pointDefinitions = $customerJourney ? app(CustomerJourneyService::class)->pointDefinitions() : [];

        $steps = [
            [
                'title' => 'ساخت حساب',
                'description' => 'حساب وطن برایت ساخته شده است.',
                'done' => true,
                'date' => $user->registered_at ?? $user->created_at,
                'icon' => 'fa-user-check',
            ],
            [
                'title' => 'اولین ورود',
                'description' => 'وارد پنل شو تا مسیر شخصی‌سازی‌شده‌ات شروع شود.',
                'done' => (bool) ($user->last_login_at || (int) $user->login_count > 0),
                'date' => $user->last_login_at,
                'icon' => 'fa-door-open',
            ],
            [
                'title' => 'اولین خروجی',
                'description' => 'یک عکس یا ویدیو بساز و نتیجه را ببین.',
                'done' => $createdCount > 0,
                'date' => $firstContentAt,
                'icon' => 'fa-wand-magic-sparkles',
            ],
            [
                'title' => 'ساخت دوم',
                'description' => 'برای بار دوم خروجی بساز تا تجربه‌ات تکرارپذیر شود.',
                'done' => $createdCount >= 2,
                'date' => null,
                'icon' => 'fa-rotate',
            ],
            [
                'title' => 'استفاده مستمر',
                'description' => 'با چند بار استفاده، ارزش واقعی ابزار را پیدا کن.',
                'done' => $createdCount >= 3 || (int) $user->login_count >= 3 || $linkCount > 0,
                'date' => null,
                'icon' => 'fa-chart-line',
            ],
            [
                'title' => 'آماده خرید',
                'description' => 'با دیدن ارزش محصول، پلن مناسب خودت را انتخاب کن.',
                'done' => $createdCount >= 5 || (int) $user->tokens_used >= 40,
                'date' => null,
                'icon' => 'fa-coins',
            ],
            [
                'title' => 'خرید اول',
                'description' => 'اولین خرید موفق تو ثبت و آماده مصرف می‌شود.',
                'done' => $purchaseCount >= 1,
                'date' => $firstPurchaseAt ? Carbon::parse($firstPurchaseAt) : null,
                'icon' => 'fa-cart-shopping',
            ],
            [
                'title' => 'مصرف موفق',
                'description' => 'از خریدت نتیجه بگیر و رضایتت را ثبت کن.',
                'done' => $purchaseCount >= 1 && ($createdCount >= 3 || (int) $user->tokens_used >= 40),
                'date' => null,
                'icon' => 'fa-face-smile',
            ],
            [
                'title' => 'خرید مجدد',
                'description' => 'برای ادامه استفاده، پیشنهاد مناسب خرید بعدی را دریافت کن.',
                'done' => $purchaseCount >= 2,
                'date' => null,
                'icon' => 'fa-rotate',
            ],
        ];

        $nextIndex = collect($steps)->search(fn (array $step): bool => ! $step['done']);
        $nextIndex = $nextIndex === false ? count($steps) - 1 : $nextIndex;

        return [
            'steps' => $steps,
            'current_index' => $nextIndex,
            'completed_count' => collect($steps)->where('done', true)->count(),
            'login_count' => (int) $user->login_count,
            'link_count' => $linkCount,
            'conversion_count' => $conversionCount,
            'image_count' => $imageCount,
            'video_count' => $videoCount,
            'token_balance' => (int) $user->tokens,
            'customer_journey' => $customerJourney,
            'customer_point_title' => $customerJourney ? ($pointDefinitions[$customerJourney->point]['title'] ?? 'مسیر فعال') : null,
            'last_login_at' => $user->last_login_at,
            'existing_partner_lead' => $existingPartnerLead,
            'next_action_url' => $createdCount === 0 ? route('app.explore') : null,
        ];
    }

    private function emptyJourneyData(): array
    {
        return [
            'steps' => [],
            'current_index' => 0,
            'completed_count' => 0,
            'login_count' => 0,
            'link_count' => 0,
            'conversion_count' => 0,
            'image_count' => 0,
            'video_count' => 0,
            'token_balance' => 0,
            'customer_journey' => null,
            'customer_point_title' => null,
            'last_login_at' => null,
            'existing_partner_lead' => null,
            'next_action_url' => route('login'),
        ];
    }

    /** تب‌های سنگین پروفایل از پاسخ اصلی جدا هستند و می‌توانند در پس‌زمینه دریافت شوند. */
    public function panel(string $panel): JsonResponse
    {
        $user = Auth::user();

        // مهمان هم باید بتواند تب را باز کند و پیام ورود ببیند؛
        // پاسخ ۴۰۱ نباید به خطای عمومی «بارگذاری انجام نشد» تبدیل شود.
        if (!$user) {
            $html = match ($panel) {
                'saved' => view('app.profile.partials.saved-items', [
                    'isGuest' => true,
                    'savedProducts' => collect(),
                ])->render(),
                'files' => view('app.profile.files', [
                    'isGuest' => true,
                    'storageUsed' => 0,
                    'storageTotal' => 100,
                    'faceProfiles' => collect(),
                    'usedProducts' => collect(),
                ])->render(),
                'referral' => view('app.profile.referral', [
                    'isGuest' => true,
                    'referralSettings' => ReferralSetting::current(),
                    'referralData' => $this->emptyReferralData(),
                    'referralProducts' => collect(),
                    'creatorRewardProducts' => collect(),
                    'creatorRewardCredits' => 0,
                    'journeyData' => $this->emptyJourneyData(),
                ])->render(),
                default => abort(404),
            };

            return response()->json(['html' => $html]);
        }

        $html = match ($panel) {
            'saved' => view('app.profile.partials.saved-items', [
                'savedProducts' => $user->savedProducts()
                    ->select(['products.id', 'products.name_fa', 'products.slug', 'products.product_code', 'products.cover', 'products.sample_outputs', 'products.thumbnail'])
                    ->latest('saved_products.created_at')
                    ->limit(24)
                    ->get(),
            ])->render(),
            'files' => $this->filesPanelHtml($user),
            'referral' => $this->referralPanelHtml($user),
            default => abort(404),
        };

        return response()->json(['html' => $html]);
    }

    /** صفحهٔ بعدی گرید خروجی‌ها؛ cursor باعث می‌شود با رشد تاریخچه offset سنگین نشود. */
    public function media(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $productPreviewColumns = 'id,name_fa,name_en,slug,product_code';
        $page = $this->mediaPage($user, $request->string('cursor')->toString(), 20, $productPreviewColumns);

        return response()->json([
            'html' => view('app.profile.partials.media-items', [
                'createdMedia' => $page['createdMedia'],
                'eagerMedia' => false,
            ])->render(),
            'next_cursor' => $page['nextCursor'],
        ]);
    }

    /** thumbnail گرید؛ مالکیت رکورد قبل از سرویس فایل بررسی می‌شود. */
    public function generatedImageThumbnail(GeneratedImage $generatedImage, ProfileMediaThumbnailService $thumbnails)
    {
        $user = Auth::user();
        abort_unless($user && (int) $generatedImage->user_id === (int) $user->id, 404);

        return $thumbnails->serve($generatedImage);
    }

    private function filesPanelHtml(User $user): string
    {
        $faceProfiles = Schema::hasTable('face_profiles')
            ? $user->faceProfiles()->active()->latest()->limit(20)->get()
            : collect();

        return view('app.profile.files', [
            'isGuest' => false,
            'storageUsed' => round((int) app(UserStorageService::class)->profileSnapshot($user)['used'] / (1024 * 1024), 2),
            'storageTotal' => 100,
            'faceProfiles' => $faceProfiles,
            'usedProducts' => $this->usedProducts($user),
        ])->render();
    }

    private function referralPanelHtml(User $user): string
    {
        $referralSettings = ReferralSetting::current();
        $referralData = $this->referralData($user, $referralSettings);
        [$creatorRewardProducts, $creatorRewardCredits] = $this->creatorRewardData($user);
        $createdCount = (int) $user->generatedImages()->whereNotNull('image_path')->count();
        if (Schema::hasTable('generated_videos')) {
            $createdCount += (int) $user->generatedVideos()
                ->where(function ($query): void {
                    $query->whereNotNull('video_path')->orWhereNotNull('video_url');
                })->count();
        }

        return view('app.profile.referral', [
            'isGuest' => false,
            'referralSettings' => $referralSettings,
            'referralData' => $referralData,
            'referralProducts' => Product::query()
                ->where('status', 'active')
                ->orderBy('name_fa')
                ->limit(200)
                ->get(['id', 'name_fa', 'name_en']),
            'creatorRewardProducts' => $creatorRewardProducts,
            'creatorRewardCredits' => $creatorRewardCredits,
            'journeyData' => $this->journeyData($user, $createdCount),
        ])->render();
    }

    private function usedProducts(User $user): Collection
    {
        $ids = $user->generatedImages()
            ->whereNotNull('product_id')
            ->latest()
            ->limit(80)
            ->pluck('product_id')
            ->concat(Schema::hasTable('generated_videos')
                ? $user->generatedVideos()->whereNotNull('product_id')->latest()->limit(80)->pluck('product_id')
                : collect())
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'name_fa', 'name_en', 'slug', 'product_code', 'cover', 'sample_outputs', 'thumbnail'])
            ->keyBy('id');

        return $ids->map(fn ($id) => $products->get($id))->filter()->values();
    }

    private function mediaPage(User $user, ?string $cursor, int $limit, string $productPreviewColumns): array
    {
        $cursorData = $this->decodeMediaCursor($cursor);
        $applyCursor = function ($query, string $mediaKind) use ($cursorData): void {
            if (! $cursorData) {
                return;
            }

            $query->where(function ($nested) use ($cursorData): void {
                $nested->where('created_at', '<', $cursorData['created_at'])
                    ->orWhere(function ($sameTime) use ($cursorData): void {
                    $sameTime->where('created_at', $cursorData['created_at'])
                            ->where(function ($sameId) use ($cursorData, $mediaKind): void {
                                $sameId->where('id', '<', $cursorData['id']);

                                // در برخورد نادرِ زمان و شناسهٔ یکسان بین دو جدول،
                                // ویدیو قبل از تصویر مرتب می‌شود تا cursor چیزی را جا نیندازد.
                                if ($cursorData['kind'] === 'video' && $mediaKind === 'image') {
                                    $sameId->orWhere('id', $cursorData['id']);
                                }
                            });
                    });
            });
        };

        $images = $user->generatedImages()
            ->select(['id', 'user_id', 'product_id', 'image_path', 'size', 'created_at'])
            ->whereNotNull('image_path')
            ->with("product:{$productPreviewColumns}")
            ->tap(fn ($query) => $applyCursor($query, 'image'))
            ->latest()
            ->limit($limit + 1)
            ->get();

        $videos = Schema::hasTable('generated_videos')
            ? $user->generatedVideos()
                ->select(['id', 'user_id', 'product_id', 'video_path', 'video_url', 'poster_path', 'size', 'status', 'created_at'])
                ->where(function ($query): void {
                    $query->whereNotNull('video_path')->orWhereNotNull('video_url');
                })
                ->with("product:{$productPreviewColumns}")
                ->tap(fn ($query) => $applyCursor($query, 'video'))
                ->latest()
                ->limit($limit + 1)
                ->get()
                ->filter(fn (GeneratedVideo $video): bool => filled($video->playbackUrl()))
                ->values()
            : collect();

        $media = $this->decorateMedia($images, $videos)
            ->sort(function ($left, $right): int {
                $dateCompare = ($right->created_at?->getTimestamp() ?? 0) <=> ($left->created_at?->getTimestamp() ?? 0);
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                $idCompare = (int) $right->id <=> (int) $left->id;
                if ($idCompare !== 0) {
                    return $idCompare;
                }

                return ((int) ($left->media_kind !== 'video')) <=> ((int) ($right->media_kind !== 'video'));
            })
            ->values();
        $hasMore = $media->count() > $limit;
        $media = $media->take($limit)->values();
        $last = $media->last();

        return [
            'createdImages' => $images->take($limit)->values(),
            'createdVideos' => $videos->take($limit)->values(),
            'createdMedia' => $media,
            'nextCursor' => $hasMore && $last
                ? $this->encodeMediaCursor($last)
                : null,
        ];
    }

    private function decorateMedia(Collection $images, Collection $videos): Collection
    {
        return $images->map(function (GeneratedImage $image): GeneratedImage {
            $image->setAttribute('media_kind', 'image');
            $image->setAttribute('media_url', $image->imageUrl());
            return $image;
        })->concat($videos->map(function (GeneratedVideo $video): GeneratedVideo {
            $video->setAttribute('media_kind', 'video');
            $video->setAttribute('media_url', $video->playbackUrl());
            $video->setAttribute('poster_url', $video->poster_path
                ? (filter_var($video->poster_path, FILTER_VALIDATE_URL)
                    ? $video->poster_path
                    : asset('storage/' . ltrim($video->poster_path, '/')))
                : null);
            return $video;
        }))->values();
    }

    private function encodeMediaCursor(object $media): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'created_at' => $media->created_at?->format('Y-m-d H:i:s'),
            'id' => (int) $media->id,
            'kind' => (string) ($media->media_kind ?? 'image'),
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    private function decodeMediaCursor(?string $cursor): ?array
    {
        if (blank($cursor)) {
            return null;
        }

        try {
            $base64 = strtr($cursor, '-_', '+/');
            $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
            $decoded = json_decode(base64_decode($base64, true), true, 512, JSON_THROW_ON_ERROR);
            return isset($decoded['created_at'], $decoded['id']) ? [
                'created_at' => (string) $decoded['created_at'],
                'id' => (int) $decoded['id'],
                'kind' => in_array($decoded['kind'] ?? null, ['image', 'video'], true)
                    ? $decoded['kind']
                    : 'image',
            ] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function profileEarnings(User $user, bool $enabled): int
    {
        if (! $enabled || ! Schema::hasTable('referral_rewards')) {
            return 0;
        }

        return (int) ReferralReward::query()
            ->where('user_id', $user->id)
            ->whereIn('reward_type', ['inviter_reward', 'purchase_reward'])
            ->where('status', 'paid')
            ->sum('amount');
    }

    private function creatorRewardCredits(User $user, bool $enabled): int
    {
        if (! $enabled || ! Schema::hasTable('product_creator_reward_events')) {
            return 0;
        }

        if (! Schema::hasColumn('product_creator_reward_events', 'status')
            || ! Schema::hasColumn('product_creator_reward_events', 'reward_credits')
            || ! Schema::hasColumn('products', 'creator_reward_owner_id')
            || ! Schema::hasColumn('products', 'creator_reward_enabled')) {
            return 0;
        }

        return (int) DB::table('product_creator_reward_events')
            ->join('products', 'products.id', '=', 'product_creator_reward_events.product_id')
            ->where('products.creator_reward_owner_id', $user->id)
            ->where('products.creator_reward_enabled', true)
            ->where('product_creator_reward_events.status', 'credited')
            ->sum('product_creator_reward_events.reward_credits');
    }

    /** داده‌ی نمایشی مالک محصول؛ دفتر پاداش از رفرال کاملاً جدا خوانده می‌شود. */
    private function creatorRewardData(User $user): array
    {
        if (! Schema::hasTable('product_creator_reward_events')
            || ! Schema::hasColumn('products', 'creator_reward_owner_id')
            || ! Schema::hasColumn('products', 'creator_reward_enabled')) {
            return [collect(), 0];
        }

        $products = Product::query()
            ->where('creator_reward_owner_id', $user->id)
            ->where('creator_reward_enabled', true)
            ->withCount([
                'creatorRewardEvents as creator_reward_uses_count' => fn ($query) => $query->where('status', 'credited'),
                'creatorRewardEvents as creator_reward_photo_count' => fn ($query) => $query->where('status', 'credited')->where('media_type', 'photo'),
                'creatorRewardEvents as creator_reward_video_count' => fn ($query) => $query->where('status', 'credited')->where('media_type', 'video'),
            ])
            ->withSum(['creatorRewardEvents as creator_reward_credits_sum' => fn ($query) => $query->where('status', 'credited')], 'reward_credits')
            ->latest('updated_at')
            ->get();

        return [$products, (int) $products->sum('creator_reward_credits_sum')];
    }

    private function referralData(User $user, ReferralSetting $settings): array
    {
        if (! Schema::hasTable('referral_visits')
            || ! Schema::hasTable('referral_conversions')
            || ! Schema::hasTable('referral_rewards')) {
            return array_merge($this->emptyReferralData(), [
                'code' => $user->referral_code,
                'link' => $user->referral_url,
                'share_message' => $this->shareMessage($settings, $user->referral_url),
                'links' => collect(),
            ]);
        }

        $recentInvites = ReferralConversion::query()
            ->where('inviter_id', $user->id)
            ->with(['invitee:id,name,last_name,phone', 'rewards' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->latest()])
            ->withExists(['invitee as purchase_completed' => fn ($query) => $query
                ->whereHas('planPurchases', fn ($purchase) => $purchase->where('status', 'completed'))])
            ->latest()
            ->limit(8)
            ->get();

        $inviterRewards = ReferralReward::query()
            ->where('user_id', $user->id)
            ->whereIn('reward_type', ['inviter_reward', 'purchase_reward']);

        $links = Schema::hasTable('referral_links')
            ? ReferralLink::query()
                ->where('inviter_id', $user->id)
                ->with('product:id,name_fa,name_en,slug,thumbnail')
                ->withCount([
                    'visits as clicks_count',
                    'conversions as registrations_count',
                    'conversions as purchases_count' => fn ($query) => $query->whereHas(
                        'invitee.planPurchases',
                        fn ($purchase) => $purchase->where('status', 'completed'),
                    ),
                    'conversions as first_images_count' => fn ($query) => $query->whereNotNull('first_image_at'),
                ])
                ->withSum('conversions as commission_total', 'commission_amount')
                ->latest()
                ->limit(30)
                ->get()
            : collect();
        $commissionRewards = ReferralReward::query()
            ->where('user_id', $user->id)
            ->whereIn('reward_type', ['purchase_commission', 'purchase_commission_reversal']);
        $commissionRows = (clone $commissionRewards)->get(['amount', 'status', 'direction']);
        $commissionTotal = static fn (string $status): int => (int) $commissionRows
            ->where('status', $status)
            ->sum(fn ($reward) => ($reward->direction ?? 'credit') === 'debit' ? -((int) $reward->amount) : (int) $reward->amount);

        return [
            'code' => $user->referral_code,
            'link' => $user->referral_url,
            'share_message' => $this->shareMessage($settings, $user->referral_url),
            'visits' => ReferralVisit::query()->where('inviter_id', $user->id)->count(),
            'registrations' => ReferralConversion::query()->where('inviter_id', $user->id)->count(),
            'successful_purchases' => ReferralConversion::query()
                ->where('inviter_id', $user->id)
                ->whereHas('invitee.planPurchases', fn ($query) => $query->where('status', 'completed'))
                ->count(),
            'first_images' => ReferralConversion::query()->where('inviter_id', $user->id)->whereNotNull('first_image_at')->count(),
            'paid_tokens' => (int) (clone $inviterRewards)->where('status', 'paid')->sum('amount'),
            'pending_tokens' => (int) (clone $inviterRewards)->where('status', 'pending')->sum('amount'),
            'pending_commission' => $commissionTotal('pending'),
            'paid_commission' => $commissionTotal('paid'),
            'links' => $links,
            'recent_invites' => $recentInvites,
        ];
    }

    private function emptyReferralData(): array
    {
        return [
            'code' => null,
            'link' => null,
            'share_message' => null,
            'visits' => 0,
            'registrations' => 0,
            'successful_purchases' => 0,
            'paid_tokens' => 0,
            'pending_tokens' => 0,
            'first_images' => 0,
            'pending_commission' => 0,
            'paid_commission' => 0,
            'links' => collect(),
            'recent_invites' => collect(),
        ];
    }

    private function shareMessage(ReferralSetting $settings, string $link): string
    {
        $message = $settings->share_message
            ?: 'با لینک دعوت من به وطن بپیوند و هدیه شروع دریافت کن: {referral_link}';

        return str_replace('{referral_link}', $link, $message);
    }

    /**
     * آپلود/جایگزینی عکس پروفایل کاربر.
     * فایل در دیسک public داخل پوشه avatars ذخیره می‌شه و مسیر قبلی (در صورت وجود) پاک می‌شه.
     */
    public function updateAvatar(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // حذف عکس قبلی از استوریج (در صورت وجود) تا فضای اضافه اشغال نشه
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->avatar = $path;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'avatar_url' => asset('storage/' . $path),
            ]);
        }

        return back()->with('success', 'عکس پروفایل با موفقیت بروزرسانی شد.');
    }

    /** حذف خروجی تصویریِ متعلق به حساب کاربر و آزادکردن حجم آن. */
    public function destroyGeneratedImage(GeneratedImage $generatedImage)
    {
        $user = Auth::user();

        abort_unless($user && (int) $generatedImage->user_id === (int) $user->id, 404);

        app(\App\Services\UserGalleryService::class)->captureOutput(
            $user,
            'output_image',
            (int) $generatedImage->id,
            (string) $generatedImage->image_path,
            'public',
            (int) $generatedImage->size,
            'image/*',
            ['order_id' => $generatedImage->order_id, 'source' => 'profile_delete_snapshot'],
        );
        $storage = app(UserStorageService::class);
        app(ProfileMediaThumbnailService::class)->delete($generatedImage);
        $storage->deletePublicFile($generatedImage->image_path);
        $generatedImage->delete();
        $storage->forgetProfileSnapshot($user);

        return redirect()->route('app.profile', ['tab' => 'grid'])
            ->with('success', 'خروجی تصویر حذف شد و فضای آن آزاد شد.');
    }

    /** حذف فایل ورودیِ متعلق به حساب کاربر و آزادکردن حجم آن. */
    public function destroyUserUpload(UserUpload $userUpload)
    {
        $user = Auth::user();

        abort_unless($user && (int) $userUpload->user_id === (int) $user->id, 404);

        $storage = app(UserStorageService::class);
        $storage->deletePublicFile($userUpload->file_path);
        $userUpload->delete();
        $storage->forgetProfileSnapshot($user);

        return redirect()->route('app.profile', [
            'tab' => 'files',
            'file_tab' => 'face-profiles',
        ])->with('success', 'فایل ورودی حذف شد و فضای آن آزاد شد.');
    }

    /** حذف خروجی ویدیوییِ متعلق به حساب کاربر و آزادکردن حجم آن. */
    public function destroyGeneratedVideo(GeneratedVideo $generatedVideo)
    {
        $user = Auth::user();

        abort_unless($user && (int) $generatedVideo->user_id === (int) $user->id, 404);

        app(\App\Services\UserGalleryService::class)->captureOutput(
            $user,
            'output_video',
            (int) $generatedVideo->id,
            (string) ($generatedVideo->video_path ?: $generatedVideo->video_url),
            'public',
            (int) $generatedVideo->size,
            $generatedVideo->mime_type ?: 'video/mp4',
            ['order_id' => $generatedVideo->order_id, 'source' => 'profile_delete_snapshot'],
        );
        $storage = app(UserStorageService::class);
        $storage->deletePublicFile($generatedVideo->video_path);
        $storage->deletePublicFile($generatedVideo->poster_path);
        $generatedVideo->delete();
        $storage->forgetProfileSnapshot($user);

        return redirect()->route('app.profile', ['tab' => 'grid'])
            ->with('success', 'خروجی ویدیو حذف شد و فضای آن آزاد شد.');
    }

    /** ساخت پروفایل مرجع چهره برای استفاده‌ی مجدد در ساخت‌های بعدی. */
    public function storeFaceProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!Schema::hasTable('face_profiles')) {
            return back()->withErrors(['face_profile' => 'این قابلیت هنوز در دسترس نیست.']);
        }

        $limit = $user->faceProfileLimit();
        $activeCount = $user->faceProfiles()->active()->count();
        if ($limit < 1 || $activeCount >= $limit) {
            return back()->withErrors(['face_profile' => 'تعداد پروفایل چهره‌ی مجاز برای پلن شما تکمیل شده است.']);
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
        $storage->forgetProfileSnapshot($user);

        return redirect()->route('app.profile', [
            'tab' => 'files',
            'file_tab' => 'face-profiles',
        ])->with('success', 'پروفایل چهره با موفقیت ذخیره شد.');
    }

    /** تغییر نام پروفایل مرجع چهره توسط صاحب حساب. */
    public function updateFaceProfile(Request $request, FaceProfile $faceProfile)
    {
        $user = Auth::user();

        abort_unless($user && (int) $faceProfile->user_id === (int) $user->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $faceProfile->update(['name' => trim((string) $validated['name'])]);
        app(UserStorageService::class)->forgetProfileSnapshot($user);

        return redirect()->route('app.profile', [
            'tab' => 'files',
            'file_tab' => 'face-profiles',
        ])->with('success', 'نام پروفایل چهره با موفقیت تغییر کرد.');
    }

    /** غیرفعال‌سازی پروفایل و حذف فایل‌های مرجع آن. */
    public function destroyFaceProfile(FaceProfile $faceProfile)
    {
        $user = Auth::user();

        abort_unless($user && (int) $faceProfile->user_id === (int) $user->id, 404);

        $storage = app(UserStorageService::class);
        foreach ($faceProfile->referenceImageEntries() as $image) {
            $storage->deletePublicFile($image['path'] ?? null);
        }

        $faceProfile->delete();
        $storage->forgetProfileSnapshot($user);

        return redirect()->route('app.profile', [
            'tab' => 'files',
            'file_tab' => 'face-profiles',
        ])->with('success', 'پروفایل چهره حذف شد.');
    }

}
