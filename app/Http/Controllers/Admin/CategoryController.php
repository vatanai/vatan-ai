<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * نمایش لیست دسته‌بندی‌ها به همراه تعداد محصولات هر کدام
     */
    public function index()
    {
        // دریافت دسته‌بندی‌ها به همراه شمارش خودکار محصولات وابسته (products_count)
        $categories = Category::withCount('products')->latest()->get();
        
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * نمایش فرم ساخت دسته‌بندی جدید
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * ذخیره دسته‌بندی جدید در دیتابیس
     */
    public function store(Request $request)
    {
        // ولیدیشن دقیق فیلدها و حجم فایل تصویر
        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name',
            'slug'  => 'nullable|string|max:255|unique:categories,slug',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // حداکثر ۲ مگابایت
        ], [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'name.unique'   => 'این نام دسته‌بندی قبلاً ثبت شده است.',
            'slug.unique'   => 'این اسلاگ تکراری است. لطفاً اسلاگ دیگری وارد کنید.',
            'image.image'   => 'فایل انتخابی باید از نوع تصویر باشد.',
            'image.mimes'   => 'فرمت‌های مجاز برای تصویر: jpeg, png, jpg, webp',
            'image.max'     => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ]);

        $data = $request->only(['name']);
        
        // هندل کردن اسلاگ فارسی و انگلیسی (جلوگیری از حذف حروف فارسی توسط لاراول)
        $slugSource = $request->slug ? $request->slug : $request->name;
        $data['slug'] = preg_replace('/\s+/u', '-', trim($slugSource));

        // آپلود فیزیکی تصویر روی استوریج محلی پروژه (بدون نیاز به CDN)
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        // ایجاد رکورد در دیتابیس
        Category::create($data);

        // ریدایرکت به صفجه ایندکس همراه با فلش سشن برای نمایش توست موفقیت
        return redirect()->route('admin.categories.index')->with('success', 'دسته‌بندی جدید با موفقیت ثبت و اضافه شد.');
    }

    /**
     * نمایش فرم ویرایش دسته‌بندی
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * بروزرسانی اطلاعات دسته‌بندی
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug'  => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'name.unique'   => 'این نام دسته‌بندی قبلاً ثبت شده است.',
            'slug.unique'   => 'این اسلاگ تکراری است.',
            'image.image'   => 'فایل انتخابی باید تصویر باشد.',
        ]);

        $data = $request->only(['name']);
        
        $slugSource = $request->slug ? $request->slug : $request->name;
        $data['slug'] = preg_replace('/\s+/u', '-', trim($slugSource));

        // مدیریت جایگزینی تصویر جدید و حذف تصویر قبلی از هاست
        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'تغییرات دسته‌بندی با موفقیت ذخیره شد.');
    }

    /**
     * حذف دسته‌بندی
     */
    public function destroy(Category $category)
    {
        // ۱. حذف فایل تصویر از پوشه storage برای جلوگیری از پر شدن هاست
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        // ۲. حذف خود رکورد دسته‌بندی
        // نکته: به دلیل وجود onDelete('set null') در مایگریشن، محصولات وابسته آسیب نمیبینند و فیلد category_id آنها null میشود.
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'دسته‌بندی با موفقیت حذف شد و محصولات وابسته به حالت بدون دسته‌بندی درآمدند.');
    }
}