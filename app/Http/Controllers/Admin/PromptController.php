<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PromptController extends Controller
{
    // ۱. نمایش لیست پرامپت‌ها در پنل ادمین (جدول مدیریت، حذف و ادیت)
    public function index()
    {
        $prompts = Prompt::latest()->get();
        return view('admin.prompts.index', compact('prompts'));
    }

    // ۲. متد اختصاصی برای نمایش صفحه اصلی سایت (پوسته تاریک Uniset)
    public function home()
    {
        $prompts = Prompt::where('is_active', true)->latest()->get();
        return view('index', compact('prompts'));
    }

    // ۳. نمایش فرم افزودن پرامپت جدید
    public function create()
    {
        return view('admin.prompts.create');
    }

    // ۴. دریافت داده‌های فرم و ذخیره در دیتابیس
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'prompt'      => 'required|string',
            'description' => 'nullable|string',
            'image'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imageName = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/prompts'), $imageName);
        }

        Prompt::create([
            'name'        => $request->name,
            'prompt'      => $request->prompt,
            'description' => $request->description,
            'image'       => 'uploads/prompts/' . $imageName,
            'is_active'   => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.prompts.create')->with('success', 'سبک جدید با موفقیت ایجاد شد.');
    }

    // ۵. نمایش فرم ویرایش پرامپت
    public function edit($id)
    {
        $prompt = Prompt::findOrFail($id);
        return view('admin.prompts.edit', compact('prompt'));
    }

    // ۶. اعمال تغییرات ویرایش شده در دیتابیس
    public function update(Request $request, $id)
    {
        $prompt = Prompt::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255',
            'prompt'      => 'required|string',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'name'        => $request->name,
            'prompt'      => $request->prompt,
            'description' => $request->description,
            'is_active'   => $request->has('is_active') ? true : false,
        ];

        if ($request->hasFile('image')) {
            if ($prompt->image && File::exists(public_path($prompt->image))) {
                File::delete(public_path($prompt->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/prompts'), $imageName);
            
            $data['image'] = 'uploads/prompts/' . $imageName;
        }

        $prompt->update($data);

        return redirect()->route('admin.prompts.index')->with('success', 'پرامپت با موفقیت بروزرسانی شد.');
    }

    // ۷. حذف کامل پرامپت و فایل تصویر آن
    public function destroy($id)
    {
        $prompt = Prompt::findOrFail($id);

        if ($prompt->image && File::exists(public_path($prompt->image))) {
            File::delete(public_path($prompt->image));
        }

        $prompt->delete();

        return redirect()->route('admin.prompts.index')->with('success', 'پرامپت و تصویر مربوطه با موفقیت حذف شدند.');
    }
}