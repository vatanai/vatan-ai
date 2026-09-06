<?php

namespace App\Http\Controllers;

use App\Models\ReferralConversion;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\FaceProfile;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use App\Models\UserGalleryConfig;
use App\Models\UserGallerySetting;
use App\Models\UserGalleryPreference;
use App\Models\UserGallerySuggestion;
use App\Services\UserGalleryRecreationService;
use App\Services\UserGalleryGrowthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{

public function gallery(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('app.profile');
    }

    // واکشی تصاویر بر اساس رابطه‌های مدل User
    $createdImages = $user->generatedImages()->latest()->get();
    $personalImages = $user->uploadedImages()->latest()->get();
    $galleryTablesAvailable = Schema::hasTable('user_gallery_configs')
        && Schema::hasTable('user_gallery_settings')
        && Schema::hasTable('user_gallery_items');
    $galleryItems = $galleryTablesAvailable
        ? $user->galleryItems()
            ->when(in_array($request->query('type'), ['upload', 'generated'], true), fn ($query) => $query->where('source_type', $request->query('type')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->get()
        : collect();
    $gallerySetting = $galleryTablesAvailable
        ? UserGallerySetting::forUser($user)
        : new UserGallerySetting(['enabled' => false]);
    $galleryConfig = $galleryTablesAvailable
        ? UserGalleryConfig::current()
        : new UserGalleryConfig(['enabled' => false, 'retention_days' => 60]);
    $growthTablesAvailable = $galleryTablesAvailable
        && Schema::hasTable('user_gallery_preferences')
        && Schema::hasTable('user_gallery_suggestions')
        && Schema::hasTable('user_gallery_recreations');
    $galleryPreference = $growthTablesAvailable
        ? UserGalleryPreference::forUser($user)
        : new UserGalleryPreference(['suggestions_enabled' => false]);
    if ($growthTablesAvailable && $galleryPreference->suggestions_enabled) {
        app(UserGalleryGrowthService::class)->generateForUser($user);
    }
    $suggestions = $growthTablesAvailable
        ? UserGallerySuggestion::query()
            ->with(['galleryItem', 'product'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['suggested', 'viewed', 'clicked'])
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get()
        : collect();
    $freeRecreationsRemaining = $growthTablesAvailable
        ? app(UserGalleryRecreationService::class)->freeRemaining($user)
        : 0;

    return view('app.gallery', compact(
        'createdImages',
        'personalImages',
        'galleryItems',
        'gallerySetting',
        'galleryConfig',
        'galleryPreference',
        'suggestions',
        'freeRecreationsRemaining',
    ));
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
                'personalImages' => collect(),
                'savedProducts'  => collect(),
                'storageUsed'    => 0,
                'storageTotal'   => 100,
                'tokenBalance'   => 0,
                'createdCount'   => 0,
                'planName'       => 'رایگان',
                'planTierName'   => 'رایگان',
                'earnings'       => 0,
                'referralSettings' => $referralSettings,
                'referralProfileEnabled' => $referralProfileEnabled,
                'referralData' => $this->emptyReferralData(),
                'planPurchases' => collect(),
                'tokenLogs' => collect(),
                'accountSummary' => $this->emptyAccountSummary(),
                'faceProfiles' => collect(),
                'faceProfileLimit' => 0,
            ]);
        }

        // واکشی تصاویر با لود به ترتیب جدیدترین‌ها بر اساس رابطه‌های مدل User
        // with('product') برای جلوگیری از N+1 کوئری موقع تشخیص نوع محتوا (عکس/ویدیو)
        $createdImages = $user->generatedImages()->with('product')->latest()->get();
        $createdVideos = Schema::hasTable('generated_videos')
            ? $user->generatedVideos()->with('product')->latest()->get()
            : collect();
        $createdMedia = $createdImages->concat($createdVideos)
            ->sortByDesc(fn ($item) => $item->created_at?->timestamp ?? 0)
            ->values();
        $personalImages = $user->uploadedImages()->latest()->get();
        $faceProfiles = Schema::hasTable('face_profiles')
            ? $user->faceProfiles()->active()->latest()->get()
            : collect();

        // محصولات ذخیره‌شده (سیو) کاربر — بخش «ذخیره شده‌ها» در صفحه پروفایل
        $savedProducts = $user->savedProducts()->latest('saved_products.created_at')->get();

        // محاسبه حجم مصرفی واقعی کاربر بر حسب بایت
        $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
        $createdVideosSize = Schema::hasTable('generated_videos')
            ? ($user->generatedVideos()->sum('size') ?? 0)
            : 0;
        $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;
        $faceProfilesSize = $faceProfiles->sum(function (FaceProfile $profile): int {
            return collect($profile->referenceImageEntries())->sum(fn (array $image) => (int) ($image['size'] ?? 0));
        });

        $totalBytes = $createdImagesSize + $createdVideosSize + $personalImagesSize + $faceProfilesSize;

        // تبدیل دقیق بایت به مگابایت با رند کردن تا ۲ رقم اعشار
        $storageUsed = round($totalBytes / (1024 * 1024), 2);
        $storageTotal = 100; // سقف مجاز ۱۰۰ مگابایت

        // ───── داده‌های واقعی باکس‌های آمار پروفایل ─────
        $tokenBalance  = $user->effective_token_balance;
        $createdCount  = $createdImages->count() + $createdVideos->count();
        $planName      = $this->profilePlanName($user);
        $planTierKey   = optional($user->plan)->model_tier_key ?? 'free';
        $planTierName  = \App\Services\ModelTierService::DEFINITIONS[$planTierKey]['name'] ?? 'رایگان';
        $referralData  = $this->referralData($user, $referralSettings);
        $earnings      = $referralData['paid_tokens'];
        $isGuest       = false;
        $planPurchases = $user->planPurchases()
            ->with('plan:id,name,slug')
            ->latest()
            ->limit(20)
            ->get();
        $tokenLogs = TokenLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();
        $accountSummary = [
            'successful_purchases' => $user->planPurchases()
                ->where('status', PlanPurchase::COMPLETED)
                ->count(),
            'total_paid' => (int) $user->planPurchases()
                ->where('status', PlanPurchase::COMPLETED)
                ->sum('paid_amount'),
            'purchased_tokens' => (int) $user->tokens_purchased,
            'promotional_tokens' => (int) $user->promotionalTokenBalance(),
        ];
        $faceProfileLimit = $user->faceProfileLimit();

        return view('app.profile', compact(
            'createdImages',
            'createdVideos',
            'createdMedia',
            'personalImages',
            'savedProducts',
            'storageUsed',
            'storageTotal',
            'tokenBalance',
            'createdCount',
            'planName',
            'planTierName',
            'earnings',
            'isGuest',
            'referralSettings',
            'referralProfileEnabled',
            'referralData',
            'planPurchases',
            'tokenLogs',
            'accountSummary',
            'faceProfiles',
            'faceProfileLimit'
        ));
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
            ->where('reward_type', 'inviter_reward');

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
            'paid_tokens' => (int) (clone $inviterRewards)->where('status', 'paid')->sum('amount'),
            'pending_tokens' => (int) (clone $inviterRewards)->where('status', 'pending')->sum('amount'),
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
            'recent_invites' => collect(),
        ];
    }

    private function emptyAccountSummary(): array
    {
        return [
            'successful_purchases' => 0,
            'total_paid' => 0,
            'purchased_tokens' => 0,
            'promotional_tokens' => 0,
        ];
    }

    /** نام پلن در پروفایل فقط یکی از چهار عنوان رسمی رابط کاربری است. */
    private function profilePlanName(User $user): string
    {
        $plan = $user->plan;
        if (! $plan || $plan->billing_type === 'free' || (int) $plan->price <= 0) {
            return 'رایگان';
        }

        $identity = Str::lower(trim(implode(' ', array_filter([
            $plan->name,
            $plan->slug,
            $plan->model_tier_key,
        ]))));

        if (Str::contains($identity, ['business', 'biz', 'enterprise', 'بیزینس', 'کسب'])) {
            return 'کسب و کار';
        }
        if (Str::contains($identity, ['advanced', 'premium', 'پیشرفته'])) {
            return 'پیشرفته';
        }

        return 'حرفه‌ای';
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

    public function storeFaceProfile(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'images' => ['required', 'array', 'min:1', 'max:3'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $storedPaths = [];

        try {
            $profile = DB::transaction(function () use ($request, &$storedPaths): FaceProfile {
                $user = User::query()->with('plan')->lockForUpdate()->findOrFail($request->user()->id);
                $limit = $user->faceProfileLimit();

                if ($limit < 1) {
                    throw ValidationException::withMessages([
                        'face_profile' => 'پلن فعلی شما امکان ساخت پروفایل چهره ندارد. برای فعال‌سازی، پلن خود را ارتقا دهید.',
                    ]);
                }

                if ($user->faceProfiles()->active()->count() >= $limit) {
                    throw ValidationException::withMessages([
                        'face_profile' => "سقف {$limit} پروفایل چهره در پلن فعلی شما تکمیل شده است.",
                    ]);
                }

                $images = collect($request->file('images'))->map(function ($image) use (&$storedPaths): array {
                    $path = $image->store('face-profiles', 'public');
                    $storedPaths[] = $path;

                    return [
                        'path' => $path,
                        'mime' => $image->getMimeType(),
                        'size' => $image->getSize(),
                    ];
                })->all();

                return $user->faceProfiles()->create([
                    'name' => trim((string) $request->input('name')),
                    'reference_images' => $images,
                    'status' => 'active',
                ]);
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'cover_url' => $profile->coverUrl(),
                    'image_count' => count($profile->referenceImageEntries()),
                ],
            ]);
        }

        return redirect()->route('app.profile', ['tab' => 'files', 'file_tab' => 'face-profiles'])
            ->with('success', 'پروفایل چهره با موفقیت ذخیره شد.');
    }

    public function destroyFaceProfile(Request $request, FaceProfile $faceProfile)
    {
        abort_unless($faceProfile->user_id === $request->user()->id, 404);

        foreach ($faceProfile->referenceImageEntries() as $image) {
            Storage::disk('public')->delete($image['path']);
        }

        $faceProfile->delete();

        return redirect()->route('app.profile', ['tab' => 'files', 'file_tab' => 'face-profiles'])
            ->with('success', 'پروفایل چهره حذف شد.');
    }
}
