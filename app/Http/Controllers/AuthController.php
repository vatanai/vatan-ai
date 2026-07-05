<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.index');
    }

    /**
     * مرحله اول: بررسی شماره موبایل برای ورود یا ثبت‌نام
     */
    public function checkPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'mode'  => ['required', 'in:login,register']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'شماره موبایل وارد شده معتبر نیست.'], 422);
        }

        // واکشی کاربر بدون محدودیت وضعیت برای بررسی دقیق شرایط
        $user = User::where('phone', $request->phone)->first();
        
        $userExists = $user && $user->status !== 'deleted';
        $isDeleted = $user && $user->status === 'deleted';

        // الف) منطق وضعیت‌ها در حالت ورود (Login)
        if ($request->mode === 'login') {
            if ($isDeleted || !$user) {
                return response()->json(['status' => 'error', 'message' => 'حسابی با این شماره یافت نشد. ابتدا ثبت‌نام کنید.'], 404);
            }
            // جلوگیری از ورود کاربر در صورت معلق بودن
            if ($user->status === 'suspended') {
                return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما معلق شده است و امکان ورود ندارید.'], 403);
            }
        }

        // ب) منطق وضعیت‌ها در حالت ثبت‌نام (Register)
        if ($request->mode === 'register') {
            // اگر کاربر قبلاً حذف شده باشد، اجازه ثبت‌نام مجدد داده نمی‌شود
            if ($isDeleted) {
                return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما حذف شده است و امکان ثبت‌نام مجدد وجود ندارد.'], 403);
            }
            
            // اگر کاربر فعال یا معلق با این شماره وجود داشته باشد
            if ($userExists) {
                return response()->json(['status' => 'error', 'message' => 'این شماره موبایل قبلاً ثبت‌نام شده است.'], 400);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'موبایل تایید شد. ورود به مرحله بعد.']);
    }

    /**
     * ثبت‌نام نهایی کاربر
     */
    public function registerSubmit(Request $request)
    {
        // حذف unique:users,phone برای مدیریت دستی وضعیت کاربران حذف شده
        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['required', 'regex:/^09\d{9}$/'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        // بررسی لایه دوم امنیتی (جلوگیری از هک یا دور زدن فرانت)
        $existingUser = User::where('phone', $request->phone)->first();
        if ($existingUser) {
            if ($existingUser->status === 'deleted') {
                return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما حذف شده است و امکان ثبت‌نام مجدد وجود ندارد.'], 403);
            }
            return response()->json(['status' => 'error', 'message' => 'این شماره موبایل قبلاً ثبت‌نام شده است.'], 400);
        }

        $user = User::create([
            'name'      => $request->name,
            'last_name' => $request->last_name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'status'    => 'active'
        ]);

        Auth::login($user, true);

        $giftTokens = 50; 
        session()->flash('welcome_tokens', $giftTokens);

        return response()->json([
            'status'    => 'success',
            'redirect'  => '/app/home',
            'user_name' => $user->name
        ]);
    }

    /**
     * ورود نهایی کاربر با رمز عبور
     */
    public function loginSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        // بررسی وضعیت کاربر پیش از تطابق پسورد
        if (!$user || $user->status === 'deleted') {
            return response()->json(['status' => 'error', 'message' => 'حساب کاربری یافت نشد.'], 404);
        }

        if ($user->status === 'suspended') {
            return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما معلق شده است و امکان ورود ندارید.'], 403);
        }

        if (Hash::check($request->password, $user->password)) {
            Auth::login($user, true);
            return response()->json([
                'status'   => 'success',
                'redirect' => '/app/home'
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'رمز عبور وارد شده اشتباه است.'], 401);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    /**
     * ارسال پیامک فراموشی رمز عبور
     */
    public function sendResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^09\d{9}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'شماره موبایل معتبر نیست.'], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        
        // به کاربر حذف شده اجازه بازیابی رمز داده نمی‌شود
        if (!$user || $user->status === 'deleted') {
            return response()->json(['status' => 'error', 'message' => 'کاربری با این شماره یافت نشد.'], 404);
        }

        if ($user->status === 'suspended') {
            return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما معلق است و امکان بازیابی رمز وجود ندارد.'], 403);
        }

        $otp = rand(10000, 99999);
        Cache::put('password_reset_otp_' . $request->phone, $otp, now()->addMinutes(3));

        return response()->json([
            'status'  => 'success',
            'message' => 'کد تایید شبیه‌سازی شد: ' . $otp
        ]);
    }

    /**
     * تایید کد OTP فراموشی رمز
     */
    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'code'  => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $cachedOtp = Cache::get('password_reset_otp_' . $request->phone);

        if (!$cachedOtp || $cachedOtp != $request->code) {
            return response()->json(['status' => 'error', 'message' => 'کد وارد شده اشتباه یا منقضی شده است.'], 422);
        }

        Cache::put('password_reset_verified_' . $request->phone, true, 300);

        return response()->json([
            'status'  => 'success',
            'message' => 'کد تایید احراز شد. اکنون رمز عبور جدید خود را وارد کنید.'
        ]);
    }

    /**
     * ثبت نهایی رمز عبور جدید
     */
    public function verifyAndResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        if (!Cache::get('password_reset_verified_' . $request->phone)) {
            return response()->json(['status' => 'error', 'message' => 'امکان تغییر رمز وجود ندارد. ابتدا کد تایید را احراز کنید.'], 422);
        }

        $user = User::where('phone', $request->phone)->where('status', '!=', 'deleted')->first();
        
        if ($user) {
            if ($user->status === 'suspended') {
                return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما معلق است.'], 403);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            Cache::forget('password_reset_otp_' . $request->phone);
            Cache::forget('password_reset_verified_' . $request->phone);

            return response()->json([
                'status'   => 'success',
                'message'  => 'رمز عبور شما با موفقیت تغییر کرد.',
                'redirect' => '/login'
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'کاربر یافت نشد یا حذف شده است.'], 404);
    }
}