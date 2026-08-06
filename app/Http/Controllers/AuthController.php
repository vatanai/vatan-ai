<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\Otp;
use App\Services\SmsEventService;
use App\Services\ReferralProgramService;
use App\Models\ReferralSetting;
use App\Support\Jalali;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $this->normalizePhoneInput($request);

        $data = $request->validate([
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'purpose' => ['required', 'in:login,register'],
        ]);

        $user = User::where('phone', $data['phone'])->first();
        if ($data['purpose'] === 'login' && (!$user || $user->status === 'deleted')) {
            return response()->json(['status' => 'error', 'message' => 'حسابی با این شماره یافت نشد.'], 404);
        }
        if ($data['purpose'] === 'login' && $user->status === 'suspended') {
            return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما معلق شده است.'], 403);
        }

        $rateLimitKey = 'user-otp:' . $data['phone'] . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد ورود قبلاً ارسال شده است. لطفاً یک دقیقه بعد دوباره تلاش کنید.',
            ], 429);
        }

        $code = (string) random_int(10000, 99999);
        $sent = app(SmsEventService::class)->send('otp_code', $data['phone'], [
            'code' => $code,
            'expiry_minutes' => '3',
            'brand_name' => 'پلتفرم وطن',
        ], type: 'authentication');
        if (!$sent) {
            return response()->json([
                'status' => 'error',
                'message' => 'ارسال کد ورود انجام نشد. لطفاً چند لحظه بعد دوباره تلاش کنید.',
            ], 503);
        }

        Otp::where('phone', $data['phone'])
            ->where('purpose', $data['purpose'])
            ->where('used', false)
            ->update(['used' => true]);

        Otp::create([
            'phone' => $data['phone'],
            'purpose' => $data['purpose'],
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(3),
            'used' => false,
            'attempts' => 0,
        ]);

        RateLimiter::hit($rateLimitKey, 60);

        return response()->json([
            'status' => 'success',
            'message' => 'کد ورود برای شما پیامک شد.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $this->normalizePhoneInput($request);
        $this->normalizeOtpInput($request);

        $data = $request->validate([
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'purpose' => ['required', 'in:login,register'],
            'code' => ['required', 'digits:5'],
        ]);

        $otp = Otp::where('phone', $data['phone'])->where('purpose', $data['purpose'])
            ->where('used', false)->latest()->first();
        if (!$otp || !$otp->isValid() || $otp->attempts >= 5) {
            return response()->json(['status' => 'error', 'message' => 'کد منقضی یا نامعتبر است.'], 422);
        }

        $otp->increment('attempts');
        if (!Hash::check($data['code'], $otp->code)) {
            return response()->json(['status' => 'error', 'message' => 'کد وارد شده اشتباه است.'], 422);
        }

        $otp->update(['used' => true]);
        if ($data['purpose'] === 'login') {
            $user = User::where('phone', $data['phone'])->where('status', 'active')->firstOrFail();
            Auth::login($user, true);
            $request->session()->regenerate();
            return response()->json(['status' => 'success', 'redirect' => $this->pullIntendedUrl($request), 'user_name' => $user->name]);
        }

        Cache::put('registration_otp_verified_' . $data['phone'], true, now()->addMinutes(10));
        return response()->json(['status' => 'success']);
    }

    public function showLogin(Request $request)
    {
        $candidate = $request->query('redirect') ?: url()->previous();
        $intended = $this->safeLocalUrl($request, is_string($candidate) ? $candidate : null);
        if ($intended !== '/login') {
            $request->session()->put('url.intended', $intended);
        }

        return view('auth.index');
    }

    /**
     * مرحله اول: بررسی شماره موبایل برای ورود یا ثبت‌نام
     */
    public function checkPhone(Request $request)
    {
        $this->normalizePhoneInput($request);

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
        $this->normalizePhoneInput($request);

        // حذف unique:users,phone برای مدیریت دستی وضعیت کاربران حذف شده
        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email'     => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['required', 'regex:/^09\d{9}$/'],
            'birth_day'   => ['required', 'integer', 'between:1,31'],
            'birth_month' => ['required', 'integer', 'between:1,12'],
            'birth_year'  => ['required', 'integer', 'between:1250,1500'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->hasAny(['birth_day', 'birth_month', 'birth_year'])) {
                return;
            }

            $year = (int) $request->birth_year;
            $month = (int) $request->birth_month;
            $day = (int) $request->birth_day;

            if (!Jalali::isValidDate($year, $month, $day)) {
                $validator->errors()->add('birth_date', 'تاریخ تولد شمسی واردشده معتبر نیست.');
                return;
            }

            [$gy, $gm, $gd] = Jalali::toGregorianYmd($year, $month, $day);
            if (Carbon::create($gy, $gm, $gd)->startOfDay()->isAfter(today())) {
                $validator->errors()->add('birth_date', 'تاریخ تولد نمی‌تواند مربوط به آینده باشد.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        if (!Cache::pull('registration_otp_verified_' . $request->phone)) {
            return response()->json(['status' => 'error', 'message' => 'ابتدا شماره موبایل را با کد پیامکی تأیید کنید.'], 422);
        }

        // بررسی لایه دوم امنیتی (جلوگیری از هک یا دور زدن فرانت)
        $existingUser = User::where('phone', $request->phone)->first();
        if ($existingUser) {
            if ($existingUser->status === 'deleted') {
                return response()->json(['status' => 'error', 'message' => 'حساب کاربری شما حذف شده است و امکان ثبت‌نام مجدد وجود ندارد.'], 403);
            }
            return response()->json(['status' => 'error', 'message' => 'این شماره موبایل قبلاً ثبت‌نام شده است.'], 400);
        }

        [$birthGy, $birthGm, $birthGd] = Jalali::toGregorianYmd(
            (int) $request->birth_year,
            (int) $request->birth_month,
            (int) $request->birth_day,
        );

        [$user, $rewardResult] = DB::transaction(function () use ($request, $birthGy, $birthGm, $birthGd) {
            $user = User::create([
                'name'      => $request->name,
                'last_name' => $request->last_name,
                'email'     => $request->filled('email') ? $request->email : null,
                'phone'     => $request->phone,
                'birth_date'=> sprintf('%04d-%02d-%02d', $birthGy, $birthGm, $birthGd),
                // ورود کاربران فقط با رمز یک‌بارمصرف انجام می‌شود؛ این مقدار تصادفی
                // صرفاً برای سازگاری با ستون قدیمی و غیرقابل‌تهی رمز نگه‌داری می‌شود.
                'password'  => $registrationPassword = Str::random(64),
                'password_reveal' => $registrationPassword,
                'status'    => 'active',
                'tokens'    => 0,
            ]);

            return [$user, app(ReferralProgramService::class)->completeRegistration($user, $request)];
        });

        Auth::login($user, true);

        $giftTokens = (int) $rewardResult['registration_gift'] + (int) $rewardResult['invitee_reward'];

        if (ReferralSetting::current()->registration_sms_enabled) {
            app(SmsEventService::class)->send('registration_success', $user->phone, [
                'name'=>$user->name, 'phone'=>$user->phone, 'gift_credits'=>(string)$giftTokens,
            ]);
        }

        if ($giftTokens > 0) {
            session()->flash('welcome_tokens', $giftTokens);
        }

        return response()->json([
            'status'    => 'success',
            'redirect'  => $this->pullIntendedUrl($request),
            'user_name' => $user->name
        ]);
    }

    /**
     * ورود نهایی کاربر با رمز عبور
     */
    public function loginSubmit(Request $request)
    {
        $this->normalizePhoneInput($request);

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
            if (!app()->environment('local')) {
                app(SmsEventService::class)->send('login_success', $user->phone, [
                    'name'=>$user->name, 'phone'=>$user->phone, 'login_time'=>now()->format('Y/m/d H:i'),
                ]);
            }
            return response()->json([
                'status'   => 'success',
                'redirect' => $this->pullIntendedUrl($request)
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'رمز عبور وارد شده اشتباه است.'], 401);
    }

    public function logout(Request $request)
    {
        $returnTo = $this->safeLocalUrl($request, $request->input('return_to'), '/');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'redirect' => $returnTo]);
        }

        return redirect($returnTo);
    }

    /**
     * ارسال پیامک فراموشی رمز عبور
     */
    public function sendResetOtp(Request $request)
    {
        $this->normalizePhoneInput($request);

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
        $this->normalizePhoneInput($request);

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
        $this->normalizePhoneInput($request);

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

            $user->password = $request->password;
            $user->password_reveal = $request->password;
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

    /** تبدیل تمام شکل‌های رایج شماره ایران به کلید یکتای 09xxxxxxxxx. */
    private function normalizePhoneInput(Request $request): void
    {
        if (! $request->has('phone')) {
            return;
        }

        $phone = strtr(trim((string) $request->input('phone')), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $phone = preg_replace('/[\s\-()]/', '', $phone) ?? $phone;

        if (str_starts_with($phone, '+98')) {
            $phone = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '0098')) {
            $phone = '0' . substr($phone, 4);
        } elseif (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '0' . $phone;
        }

        $request->merge(['phone' => $phone]);
    }

    private function normalizeOtpInput(Request $request): void
    {
        $code = strtr((string) $request->input('code', ''), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $request->merge(['code' => preg_replace('/\D+/u', '', $code)]);
    }

    private function pullIntendedUrl(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        return $this->safeLocalUrl($request, is_string($intended) ? $intended : null, '/app/home');
    }

    private function safeLocalUrl(Request $request, ?string $candidate, string $fallback = '/app/home'): string
    {
        if (!$candidate) {
            return $fallback;
        }

        $parts = parse_url($candidate);
        if ($parts === false || (isset($parts['host']) && strcasecmp($parts['host'], $request->getHost()) !== 0)) {
            return $fallback;
        }

        $path = $parts['path'] ?? '/';
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $fallback;
        }

        if ($path === '/login' || str_starts_with($path, '/auth/') || str_starts_with($path, '/admin')) {
            return $fallback;
        }

        return $path
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }
}
