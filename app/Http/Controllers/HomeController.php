<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Generation;
use App\Models\User;
use App\Services\LogService; // 🟢 اضافه شد: استفاده از سرویس مرکزی لاگین برای پروژه شما
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * نمایش صفحه اصلی و ارسال دیتای آخرین تصویر تولید شده
     */
    public function index()
    {
        $products = Product::all();
        
        // گرفتن آخرین تصویر پردازش شده این جلسه (Session) برای نمایش به کاربر
        $latestGeneration = null;
        if (session()->has('latest_generation_id')) {
            $latestGeneration = Generation::find(session('latest_generation_id'));
        }

        // 🟢 باگ اصلاح شد: در تابع compact نباید علامت $ گذاشته شود؛ تصحیح شد به 'latestGeneration'
        return view('welcome', compact('products', 'latestGeneration'));
    }

    /**
     * پردازش آزمایشی/اولیه تصویر و ثبت لاگ از طریق سرویس مرکزی
     */
    public function generate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image'      => 'required|image|max:10240',
        ]);

        // ۱. ذخیره تصویر ورودی کاربر در هاست لوکال
        $path = $request->file('image')->store('user_uploads', 'public');

        // ۲. 🟢 اصلاح شد: استفاده از سرویس یکپارچه برای ذخیره لاگ (چه برای مهمان، چه کاربر لاگین شده)
        // توجه: این متد به طور خودکار Auth::id() را بررسی کرده و در دیتابیس می‌نشاند.
        $generation = LogService::log(
            prompt: "پردازش محصول با شناسه: " . $request->product_id,
            inputImage: $path,
            outputImage: 'sample_outputs/result.jpg', // آدرس تصویر فرضی خروجی لوکال
            status: 'completed'
        );

        // ۳. اگر به هر دلیلی مدل مستقیماً برگشت داده نشد، آخرین سطر را برای سشن پیدا می‌کنیم
        $generationId = $generation?->id ?? Generation::latest()->first()?->id;

        // ذخیره شناسه در سشن برای نمایش پس از ریلود صفحه و استفاده در bindGuestGenerations
        session(['latest_generation_id' => $generationId]);

        return redirect()->route('home')->with('success', 'تصویر شما با موفقیت پردازش شد.');
    }

    /**
     * متد قدیمی ورود (صرفاً جهت حفظ ساختار قبلی - پیشنهاد می‌شود روت‌ها به AuthController متصل باشند)
     */
    public function login(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            Auth::login($user, true);
            
            // متصل کردن عکس‌های زمان مهمان بودن به حساب کاربر پس از ورود
            if (session()->has('latest_generation_id')) {
                Generation::where('id', session('latest_generation_id'))->whereNull('user_id')->update(['user_id' => $user->id]);
            }
            
            return redirect()->route('home');
        }
        return redirect()->route('home')->withErrors(['login_error' => 'کاربری با این شماره یافت نشد.']);
    }

    /**
     * متد قدیمی ثبت‌نام همراه با شارژ کیف پول و هدیه اولیه ۵۰ توکن
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'phone'      => 'required|unique:users,phone',
            'email'      => 'nullable|email',
            'birth_date' => 'nullable|date',
        ]);

        // تغییر مپ دیتای ورودی با ساختار فیلدهای دیتابیس شما
        $user = User::create([
            'name'       => $data['first_name'],
            'last_name'  => $data['last_name'],
            'phone'      => $data['phone'],
            'email'      => $data['email'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
        ]);
        
        // ایجاد کیف پول و شارژ ۵۰ توکن هدیه اولیه در دیتابیس
        if (method_exists($user, 'wallet')) {
            $user->wallet()->create(['tokens_balance' => 50]);
        }
        
        Auth::login($user, true);

        // اگر قبل از ثبت‌نام عکسی تولید کرده بود، آن عکس را به حساب جدیدش متصل کن
        if (session()->has('latest_generation_id')) {
            Generation::where('id', session('latest_generation_id'))->whereNull('user_id')->update(['user_id' => $user->id]);
        }

        return redirect()->route('home');
    }
}