<?php

namespace App\Http\Controllers;

use App\Models\Plan; // اگر نام مدل پلن شما متفاوت است (مثلاً Product)، آن را اصلاح کنید
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanSubscriptionController extends Controller
{
    /**
     * نمایش صفحه پلن‌ها به کاربر
     */
    public function index()
    {
        // واکشی پلن‌های فعال بر اساس دیتابیس شما
        $plans = Plan::where('is_active', 1)->get(); 
        
        return view('site.pricing', compact('plans'));
    }

    /**
     * عملیات پرداخت الکی و شارژ دقیق توکن‌ها
     */
    public function fakePayment(Request $request, $id)
    {
        $user = Auth::user();
        
        // پیدا کردن پلن بر اساس ID
        $plan = Plan::findOrFail($id);

        // ۱. افزایش موجودی فعلی توکن کاربر
        $user->tokens = ($user->tokens ?? 0) + $plan->tokens;
        
        // ۲. 🔴 اضافه شدن به تاریخچه کل توکن‌های خریداری شده از اول تا الان
        $user->tokens_purchased = ($user->tokens_purchased ?? 0) + $plan->tokens;
        
        // ذخیره نهایی تغییرات در دیتابیس روی جدول users
        $user->save();

        // ارسال تعداد توکن واقعی به کمکت سشن موفقیت
        return redirect()->route('pricing.index')->with(
            'success', 
            "پرداخت آزمایشی برای پلن «" . $plan->name . "» موفقیت‌آمیز بود! تعداد " . number_format($plan->tokens) . " توکن به حساب شما اضافه شد."
        );
    }
}