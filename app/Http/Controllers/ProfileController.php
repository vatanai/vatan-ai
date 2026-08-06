<?php

namespace App\Http\Controllers;

use App\Models\ReferralConversion;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{

public function gallery()
{
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('app.profile');
    }

    // واکشی تصاویر بر اساس رابطه‌های مدل User
    $createdImages = $user->generatedImages()->latest()->get();
    $personalImages = $user->uploadedImages()->latest()->get();

    return view('app.gallery', compact('createdImages', 'personalImages'));
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
                'personalImages' => collect(),
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
            ]);
        }

        // واکشی تصاویر با لود به ترتیب جدیدترین‌ها بر اساس رابطه‌های مدل User
        // with('product') برای جلوگیری از N+1 کوئری موقع تشخیص نوع محتوا (عکس/ویدیو)
        $createdImages = $user->generatedImages()->with('product')->latest()->get();
        $personalImages = $user->uploadedImages()->latest()->get();

        // محصولات ذخیره‌شده (سیو) کاربر — بخش «ذخیره شده‌ها» در صفحه پروفایل
        $savedProducts = $user->savedProducts()->latest('saved_products.created_at')->get();

        // محاسبه حجم مصرفی واقعی کاربر بر حسب بایت
        $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
        $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;

        $totalBytes = $createdImagesSize + $personalImagesSize;

        // تبدیل دقیق بایت به مگابایت با رند کردن تا ۲ رقم اعشار
        $storageUsed = round($totalBytes / (1024 * 1024), 2);
        $storageTotal = 100; // سقف مجاز ۱۰۰ مگابایت

        // ───── داده‌های واقعی باکس‌های آمار پروفایل ─────
        $tokenBalance  = $user->token_balance;
        $createdCount  = $createdImages->count();
        $planName      = optional($user->plan)->name ?? 'رایگان';
        $referralData  = $this->referralData($user, $referralSettings);
        $earnings      = $referralData['paid_tokens'];
        $isGuest       = false;

        return view('app.profile', compact(
            'createdImages',
            'personalImages',
            'savedProducts',
            'storageUsed',
            'storageTotal',
            'tokenBalance',
            'createdCount',
            'planName',
            'earnings',
            'isGuest',
            'referralSettings',
            'referralProfileEnabled',
            'referralData'
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
}
