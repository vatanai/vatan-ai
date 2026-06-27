<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Generation;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * نمایش صفحه لاگین و ثبت‌نام
     */
    public function showLogin()
    {
        return view('login.index');
    }

    /**
     * ارسال کد OTP (شبیه‌سازی شده)
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^09\d{9}$/',
            'mode'  => 'required|in:register,login'
        ]);

        $user = User::where('phone', $request->phone)->first();

        // اگر کاربر قصد ورود دارد اما ثبت نام نکرده است
        if ($request->mode === 'login' && !$user) {
            ActivityLog::record(
                type: 'otp_failed',
                message: 'تلاش برای ورود با شماره‌ای که ثبت‌نام نشده',
                level: 'warning',
                meta: ['phone' => $request->phone, 'mode' => $request->mode],
            );

            return response()->json(['message' => 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.'], 422);
        }

        // اگر کاربر قصد ثبت نام دارد اما قبلاً ثبت نام کرده است
        if ($request->mode === 'register' && $user) {
            ActivityLog::record(
                type: 'otp_failed',
                message: 'تلاش برای ثبت‌نام مجدد با شماره‌ای که قبلاً ثبت‌نام شده',
                level: 'warning',
                meta: ['phone' => $request->phone, 'mode' => $request->mode],
            );

            return response()->json(['message' => 'این شماره موبایل قبلاً ثبت‌نام شده است. لطفاً وارد شوید.'], 422);
        }

        // ذخیره موقت شماره موبایل در سشن
        session(['otp_phone' => $request->phone]);

        ActivityLog::record(
            type: 'otp_sent',
            message: 'کد تایید برای شماره موبایل ارسال شد',
            userId: $user?->id,
            meta: ['phone' => $request->phone, 'mode' => $request->mode],
        );

        return response()->json(['message' => 'کد تایید پیامک شد. (کد تست: 11111)']);
    }

    /**
     * تایید کد OTP و ورود/هدایت به تکمیل مشخصات
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^09\d{9}$/',
            'code'  => 'required|string'
        ]);

        // هاردکد کردن کد تایید مطابق با منطق فرانت‌اَند شما
        if ($request->code !== '11111') {
            ActivityLog::record(
                type: 'otp_failed',
                message: 'کد تایید وارد شده اشتباه بود',
                level: 'warning',
                meta: ['phone' => $request->phone, 'entered_code' => $request->code],
            );

            return response()->json(['message' => 'کد وارد شده اشتباه است.'], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if ($user) {
            // کاربر وجود دارد -> لاگین مستقیم و فعالسازی سشن
            Auth::login($user, true);

            // متصل کردن عکس‌های زمان مهمان بودن به حساب کاربر
            $this->bindGuestGenerations($user->id);

            ActivityLog::record(
                type: 'login',
                message: 'کاربر با موفقیت از طریق کد تایید وارد شد',
                userId: $user->id,
                level: 'success',
                meta: ['phone' => $request->phone, 'method' => 'otp'],
            );

            return response()->json([
                'is_registered' => true,
                'message' => 'خوش آمدید'
            ]);
        }

        // کاربر وجود ندارد -> ذخیره شماره در سشن برای مرحله تکمیل مشخصات
        session(['register_phone' => $request->phone]);

        ActivityLog::record(
            type: 'otp_verified',
            message: 'کد تایید شد، در انتظار تکمیل مشخصات کاربر جدید',
            meta: ['phone' => $request->phone],
        );

        return response()->json([
            'is_registered' => false,
            'message' => 'کد تایید شد، لطفاً اطلاعات خود را تکمیل کنید.'
        ]);
    }

    /**
     * ایجاد کاربر جدید در دیتابیس (تکمیل مشخصات نهایی برای روش پیامک)
     */
    public function completeProfile(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
        ]);

        $phone = session('register_phone');

        if (!$phone) {
            ActivityLog::record(
                type: 'validation_error',
                message: 'تلاش برای تکمیل مشخصات با سشن منقضی شده',
                level: 'error',
                meta: ['step' => 'completeProfile'],
            );

            return response()->json(['message' => 'جلسه کاری شما منقضی شده است. مجدداً تلاش کنید.'], 422);
        }

        // ثبت قطعی کاربر در دیتابیس
        $user = User::create([
            'name'      => $request->first_name, 
            'last_name' => $request->last_name,
            'phone'     => $phone,
            'password'  => null, 
        ]);

        // ورود کاربر به سیستم و ثبت سشن پایدار
        Auth::login($user, true);

        // پاکسازی سشن موقت
        session()->forget('register_phone');

        // متصل کردن عکس‌های زمان مهمان بودن به این حساب جدید
        $this->bindGuestGenerations($user->id);

        ActivityLog::record(
            type: 'register',
            message: 'ثبت‌نام کرد',
            userId: $user->id,
            level: 'success',
            meta: [
                'method'     => 'otp',
                'phone'      => $phone,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
            ],
        );

        return response()->json(['message' => 'ثبت نام با موفقیت انجام شد.']);
    }

    /**
     * ثبت‌نام مستقیم با ایمیل و رمز عبور (کاملاً مستقل از سیستم پیامکی)
     */
    public function registerWithEmail(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
        ]);

        // ایجاد ساختار کاربر ایمیلی (ستون phone مقدار null می‌گیرد)
        $user = User::create([
            'name'      => $request->first_name,
            'last_name' => $request->last_name,
            'email'     => $request->email,
            'phone'     => null,
            'password'  => Hash::make($request->password), // هش کردن امن پسورد
        ]);

        // لاگین قطعی و پایدار کاربر در سشن لاراول
        Auth::login($user, true);

        // انتقال رکوردهای تصاویر تولید شده زمان مهمان بودن
        $this->bindGuestGenerations($user->id);

        ActivityLog::record(
            type: 'register',
            message: 'ثبت‌نام کرد',
            userId: $user->id,
            level: 'success',
            meta: [
                'method'     => 'email',
                'email'      => $request->email,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
            ],
        );

        return response()->json(['message' => 'ثبت‌نام شما با ایمیل با موفقیت انجام شد.']);
    }

    /**
     * ورود سنتی با ایمیل و رمز عبور
     */
    public function loginWithEmail(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $input    = $request->input('email');
        $password = $request->input('password');

        // تلاش با ایمیل
        $user = \App\Models\User::where('email', $input)->first();

        // اگر پیدا نشد، با نام کاربری (name) جستجو کن
        if (!$user) {
            $user = \App\Models\User::where('name', $input)->first();
        }

        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user, true);
            $request->session()->regenerate();

            $this->bindGuestGenerations(Auth::id());

            ActivityLog::record(
                type: 'login',
                message: 'وارد حساب کاربری شد',
                userId: Auth::id(),
                level: 'success',
                meta: ['method' => 'email', 'input' => $input],
            );

            return response()->json([
                'message'     => 'ورود موفقیت‌آمیز بود.',
                'redirect_to' => '/admin/dashboard',
            ]);
        }

        ActivityLog::record(
            type: 'login_failed',
            message: 'تلاش ناموفق برای ورود',
            level: 'warning',
            meta: ['input' => $input],
        );

        return response()->json(['message' => 'نام کاربری یا رمز عبور اشتباه است.'], 422);
    }

    /**
     * خروج از حساب کاربری
     */
    public function logout(Request $request)
    {
        ActivityLog::record(
            type: 'logout',
            message: 'از حساب کاربری خارج شد',
            userId: Auth::id(),
            level: 'info',
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * متد کمکی برای اتصال سشن عکس‌های مهمان به حساب کاربر واقعی
     */
    private function bindGuestGenerations($userId)
    {
        if (session()->has('latest_generation_id')) {
            Generation::where('id', session('latest_generation_id'))
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);

            // به‌روزرسانی لاگ‌های فعالیت مهمان قبلی که به این رکورد جنریت مرتبط بودند
            ActivityLog::where('generation_id', session('latest_generation_id'))
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);
        }
    }
}