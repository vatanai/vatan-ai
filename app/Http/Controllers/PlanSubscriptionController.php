<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanSubscriptionController extends Controller
{
    /**
     * نمایش صفحه پلن‌ها و قیمت‌ها به کاربر
     */
    public function index()
    {
        // واکشی تمامی پلن‌ها بدون ستون ناموجود is_active برای جلوگیری از خطا
        $plans = Plan::latest()->get(); 
        
        return view('site.pricing', compact('plans'));
    }

    /**
     * عملیات پرداخت شبیه‌ساز و شارژ دقیق توکن‌ها
     */
    public function fakePayment(Request $request, $plan)
    {
        // جستجو بر اساس اسلاگ؛ اگر پیدا نشد بر اساس ID (جهت بالا بردن پایداری سیستم)
        $planModel = Plan::where('slug', $plan)->first();
        
        if (!$planModel && is_numeric($plan)) {
            $planModel = Plan::find($plan);
        }

        if (!$planModel) {
            abort(404, 'پلن مورد نظر یافت نشد.');
        }
        
        // دریافت کاربر فعلی لاگین شده
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'لطفاً ابتدا وارد حساب کاربری خود شوید.');
        }

        // اضافه کردن توکن‌های واقعی پلن به موجودی کاربر
        $user->tokens = ($user->tokens ?? 0) + (int) $planModel->tokens;
        $user->save();

        // بازگشت به صفحه قیمت‌ها همراه با پیام موفقیت داینامیک
        return redirect()->route('pricing.index')->with(
            'success', 
            "حساب شما با موفقیت ارتقا یافت! پکیج «{$planModel->name}» فعال شد و تعداد " . number_format($planModel->tokens) . " توکن به حساب شما اضافه گردید."
        );
    }
}