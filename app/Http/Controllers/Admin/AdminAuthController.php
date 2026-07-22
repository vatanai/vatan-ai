<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SmsSetting;
use App\Services\SmsEventService;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'password.required' => 'وارد کردن کلمه عبور الزامی است.',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();
            $admin = Auth::guard('admin')->user();
            if ($phone = SmsSetting::valueOf('admin_test_phone')) app(SmsEventService::class)->send('admin_login', $phone, [
                'admin_name'=>$admin->name, 'admin_email'=>$admin->email,
                'login_time'=>now()->format('Y/m/d H:i'), 'ip'=>$request->ip(),
            ]);
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'اطلاعات وارد شده با رکوردهای مدیریت مطابقت ندارد.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }
}
