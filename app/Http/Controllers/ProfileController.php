<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $earnings      = (int) ($user->referral_earnings ?? 0);
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
            'isGuest'
        ));
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
