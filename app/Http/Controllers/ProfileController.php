<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $createdImages = $user->generatedImages()->latest()->get();
        $personalImages = $user->uploadedImages()->latest()->get();

        // محاسبه حجم مصرفی واقعی کاربر بر حسب بایت
        $createdImagesSize = $user->generatedImages()->sum('size') ?? 0;
        $personalImagesSize = $user->uploadedImages()->sum('size') ?? 0;
        
        $totalBytes = $createdImagesSize + $personalImagesSize;
        
        // تبدیل دقیق بایت به مگابایت با رند کردن تا ۲ رقم اعشار
        $storageUsed = round($totalBytes / (1024 * 1024), 2); 
        $storageTotal = 100; // سقف مجاز ۱۰۰ مگابایت

        return view('app.profile', compact(
            'createdImages',
            'personalImages',
            'storageUsed',
            'storageTotal'
        ));
    }
}