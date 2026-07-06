<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    /**
     * نمایش لیست کامل پلن‌ها
     */
    public function index()
    {
        $plans = Plan::latest()->get();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * نمایش فرم ایجاد پلن جدید
     */
    public function create()
    {
        return view('admin.plans.create');
    }

    /**
     * ذخیره‌سازی داده‌های فرم ایجاد
     */
    public function store(Request $request)
    {
        // حذف کاما از قیمت برای تبدیل به عدد خام پیش از ولیدیشن
        if ($request->has('price')) {
            $request->merge([
                'price' => str_replace(',', '', $request->price)
            ]);
        }

        $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:255|unique:plans,slug',
            'price'  => 'required|integer|min:0',
            'tokens' => 'required|integer|min:1',
            'image'  => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('plans', 'public');
        }

        // فیلد is_active به دلیل عدم وجود در دیتابیس حذف شد تا خطا برطرف شود
        Plan::create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->slug, '-'),
            'price'      => $request->price,
            'tokens'     => $request->tokens,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'پلن جدید با موفقیت ساخته شد.');
    }

    /**
     * نمایش فرم ویرایش پلن
     */
    public function edit(string $id)
    {
        $plan = Plan::findOrFail($id);
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * به‌روزرسانی اطلاعات پلن
     */
    public function update(Request $request, string $id)
    {
        $plan = Plan::findOrFail($id);

        // حذف کاما از قیمت پیش از ولیدیشن و آپدیت
        if ($request->has('price')) {
            $request->merge([
                'price' => str_replace(',', '', $request->price)
            ]);
        }

        $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'price'  => 'required|integer|min:0',
            'tokens' => 'required|integer|min:1',
            'image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($plan->image_path) {
                Storage::disk('public')->delete($plan->image_path);
            }
            $plan->image_path = $request->file('image')->store('plans', 'public');
        }

        $plan->update([
            'name'       => $request->name,
            'slug'       => Str::slug($request->slug, '-'),
            'price'      => $request->price,
            'tokens'     => $request->tokens,
            'image_path' => $plan->image_path,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'پلن با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف کامل پلن
     */
    public function destroy(string $id)
    {
        $plan = Plan::findOrFail($id);
        if ($plan->image_path) {
            Storage::disk('public')->delete($plan->image_path);
        }
        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'پلن مدنظر با موفقیت حذف شد.');
    }
}