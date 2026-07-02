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

    public function checkPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'mode'  => ['required', 'in:login,register']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'شماره موبایل وارد شده معتبر نیست.'], 422);
        }

        $userExists = User::where('phone', $request->phone)->exists();

        if ($request->mode === 'login' && !$userExists) {
            return response()->json(['status' => 'error', 'message' => 'حسابی با این شماره یافت نشد. ابتدا ثبت‌نام کنید.'], 404);
        }

        if ($request->mode === 'register' && $userExists) {
            return response()->json(['status' => 'error', 'message' => 'این شماره موبایل قبلاً ثبت‌نام شده است.'], 400);
        }

        return response()->json(['status' => 'success', 'message' => 'موبایل تایید شد. ورود به مرحله بعد.']);
    }

    public function loginSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'اطلاعات ارسالی ناقص است.'], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'رمز عبور وارد شده اشتباه است.'], 422);
        }

        Auth::login($user, true);

        return response()->json([
            'status'    => 'success',
            'redirect'  => '/app/home',
            'user_name' => $user->name ?? 'کاربر قدیمی'
        ]);
    }

    public function registerSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['required', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'password'  => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $user = User::create([
            'name'      => $request->name,
            'last_name' => $request->last_name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'redirect' => '/login']);
        }

        return redirect('/login');
    }

    // ═══ فراموشی رمز عبور ═══
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // مرحله اول: ارسال کد OTP
    public function sendResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^09\d{9}$/']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'شماره موبایل وارد شده معتبر نیست.'], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'حسابی با این شماره موبایل یافت نشد.'], 404);
        }

        $otpCode = rand(1000, 9999);
        Cache::put('password_reset_otp_' . $request->phone, $otpCode, 120); // معتبر به مدت ۲ دقیقه

        return response()->json([
            'status' => 'success',
            'message' => 'کد تایید با موفقیت پیامک شد.',
            'debug_code' => $otpCode
        ]);
    }

    // مرحله دوم: صرفاً تایید صحت کد OTP (بدون تغییر پسورد)
    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'otp'   => ['required', 'numeric', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'اطلاعات ارسالی معتبر نیست.'], 422);
        }

        $cachedOtp = Cache::get('password_reset_otp_' . $request->phone);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json(['status' => 'error', 'message' => 'کد تایید اشتباه است یا زمان آن به پایان رسیده.'], 422);
        }

        // ایجاد یک کلید موقت در کش جهت اثبات تایید موفقیت‌آمیز کد در مرحله قبل (به مدت ۵ دقیقه)
        Cache::put('password_reset_verified_' . $request->phone, true, 300);

        return response()->json([
            'status' => 'success',
            'message' => 'کد تایید احراز شد. اکنون رمز عبور جدید خود را وارد کنید.'
        ]);
    }

    // مرحله سوم: دریافت و اعمال رمز عبور جدید
    public function verifyAndResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        // بررسی اینکه آیا مرحله دوم (تایید کد) واقعاً پاس شده است یا خیر
        if (!Cache::get('password_reset_verified_' . $request->phone)) {
            return response()->json(['status' => 'error', 'message' => 'امکان تغییر رمز وجود ندارد. ابتدا کد تایید را احراز کنید.'], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();

            // پاکسازی کش‌ها
            Cache::forget('password_reset_otp_' . $request->phone);
            Cache::forget('password_reset_verified_' . $request->phone);

            return response()->json([
                'status' => 'success',
                'message' => 'رمز عبور شما با موفقیت تغییر کرد.',
                'redirect' => '/login'
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'خطایی رخ داده است. مجدداً تلاش کنید.'], 500);
    }
}