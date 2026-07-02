<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanController extends Controller
{
    public function create()
    {
        // نمایش صفحه فرم افزودن پلن
        return view('admin.plans.create');
    }
public function index()
    {
        // واکشی تمام پلن‌ها بر اساس جدیدترین‌ها
        $plans = Plan::latest()->get();

        // ارسال داده‌ها به فایل ویو اختصاصی ادمین
        return view('admin.plans.index', compact('plans'));
    }
    public function store(Request $request)
    {
        // ۱. قبل از ولیدیشن، کاماهای قیمت را حذف می‌کنیم تا تبدیل به عدد خام شود
        if ($request->has('price')) {
            $request->merge([
                'price' => str_replace(',', '', $request->price)
            ]);
        }

        // ۲. ولیدیشن داده‌ها
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'tokens' => 'required|integer|min:1',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // حداکثر ۵ مگابایت
        ]);

        // ۳. آپلود تصویر در هاست / دیسک عمومی
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('plans', 'public');
        }

        // ۴. ذخیره در دیتابیس
        Plan::create([
            'name' => $request->name,
            'price' => $request->price,
            'tokens' => $request->tokens,
            'image_path' => $imagePath,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        // ۵. ریدایرکت با پیام موفقیت‌آمیز
        return redirect()->route('admin.plans.index')->with('success', 'پلن جدید با موفقیت ایجاد شد.');
    }
}