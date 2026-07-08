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
        return redirect()->route('login');
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
            return redirect()->route('login');
        }

        // واکشی تصاویر با لود به ترتیب جدیدترین‌ها بر اساس رابطه‌های مدل User
        // with('product') برای جلوگیری از N+1 کوئری موقع تشخیص نوع محتوا (عکس/ویدیو)
        $createdImages = $user->generatedImages()->with('product')->latest()->get();
        $personalImages = $user->uploadedImages()->latest()->get();

        // محاسبه حجم مصرفی واقعی کاربر بر حسب بایت
        $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
        $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;
        
        $totalBytes = $createdImagesSize + $personalImagesSize;
        
        // تبدیل دقیق بایت به مگابایت با رند کردن تا ۲ رقم اعشار
        $storageUsed = round($totalBytes / (1024 * 1024), 2);
        $storageTotal = 100; // سقف مجاز ۱۰۰ مگابایت

        // ───── داده‌های واقعی باکس‌های آمار پروفایل ─────
        $tokenBalance  = (int) ($user->tokens ?? 0);
        $createdCount  = $createdImages->count();
        $planName      = optional($user->plan)->name ?? 'رایگان';
        $earnings      = (int) ($user->referral_earnings ?? 0);

        return view('app.profile', compact(
            'createdImages',
            'personalImages',
            'storageUsed',
            'storageTotal',
            'tokenBalance',
            'createdCount',
            'planName',
            'earnings'
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