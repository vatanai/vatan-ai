<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AiModelController extends Controller
{
    public function index()
    {
        $models = AiModel::latest()->get();
        return view('admin.ai-models.index', compact('models'));
    }

    public function create()
    {
        return view('admin.ai-models.create');
    }

    public function store(Request $request)
{
    // 💡 نکته تستی: اگر می‌خواهید ببینید دقیقاً چه داده‌هایی از فرم ارسال می‌شود، 
    // خط زیر را از کامنت خارج کنید تا ارسال فرم متوقف و داده‌ها چاپ شوند:
    // dd($request->all());

    $validatedData = $request->validate([
        'name'                 => 'required|string|max:255',
        'openrouter_model_id'  => 'required|string|max:255',
        'provider_name'        => 'required|string|max:255',
        'output_modality'      => 'required|string|in:text,image,video,audio',
        'supports_image_input' => 'nullable', 
        'cost_per_generation'  => 'required|numeric|min:0',
        'default_width'        => 'nullable|numeric|min:1',
        'default_height'       => 'nullable|numeric|min:1',
        'default_parameters'   => 'nullable|string',
        'is_active'            => 'nullable',
        'description'          => 'nullable|string',
        'model_image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096', // افزایش حجم تا ۴ مگابایت برای امنیت بیشتر
    ]);

    // ذخیره‌سازی هوشمند با مقادیر پیش‌فرض مپ شده
    $model = AiModel::create([
        'name'                 => $validatedData['name'],
        'openrouter_model_id'  => $validatedData['openrouter_model_id'],
        'provider_name'        => $validatedData['provider_name'],
        'output_modality'      => $validatedData['output_modality'],
        'supports_image_input' => $request->input('supports_image_input', '0') == '1',
        'cost_per_generation'  => $validatedData['cost_per_generation'],
        'default_width'        => $validatedData['default_width'] ?: 1024,
        'default_height'       => $validatedData['default_height'] ?: 1024,
        'default_parameters'   => $validatedData['default_parameters'],
        'description'          => $validatedData['description'],
        'is_active'            => $request->input('is_active', '1') == '1',
    ]);

    // آپلود تصویر مدل در صورت وجود
    if ($request->hasFile('model_image')) {
        $image = $request->file('model_image');
        $safeName = str_replace(['/', '\\', ':', '*'], '-', $model->openrouter_model_id);
        $filename = $safeName . '.' . $image->getClientOriginalExtension();
        
        $destinationPath = public_path('uploads/models');
        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }
        $image->move($destinationPath, $filename);
    }

    return redirect()->route('admin.ai-models.index')
        ->with('success', 'مدل هوش مصنوعی جدید با موفقیت به همراه تصویر اختصاصی ثبت و در پایگاه داده ذخیره شد.');
}

    public function edit($id)
    {
        $model = AiModel::findOrFail($id);
        return view('admin.ai-models.edit', compact('model'));
    }

    public function update(Request $request, $id)
    {
        $model = AiModel::findOrFail($id);

        $validatedData = $request->validate([
            'name'                 => 'required|string|max:200',
            'openrouter_model_id'  => 'required|string|max:300',
            'provider_name'        => 'required|string|max:100',
            'output_modality'      => 'required|string|in:image,text,video,audio',
            'supports_image_input' => 'required|in:0,1',
            'cost_per_generation'  => 'required|integer|min:0',
            'default_width'        => 'nullable|integer|min:1',
            'default_height'       => 'nullable|integer|min:1',
            'default_parameters'   => 'nullable|string',
            'is_active'            => 'required|in:0,1',
            'description'          => 'nullable|string',
            'model_image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // آپلود تصویر جدید و جایگزینی آن در صورت انتخاب توسط کاربر
        if ($request->hasFile('model_image')) {
            $image = $request->file('model_image');
            $safeName = str_replace(['/', '\\', ':', '*'], '-', $validatedData['openrouter_model_id']);
            
            $destinationPath = public_path('uploads/models');
            
            // حذف فایل‌های با فرمت قبلی برای جلوگیری از تداخل نام همسان
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $oldFile = $destinationPath . '/' . $safeName . '.' . $ext;
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $filename = $safeName . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $filename);
        }

        $model->update([
            'name'                 => $validatedData['name'],
            'openrouter_model_id'  => $validatedData['openrouter_model_id'],
            'provider_name'        => $validatedData['provider_name'],
            'output_modality'      => $validatedData['output_modality'],
            'supports_image_input' => $validatedData['supports_image_input'],
            'cost_per_generation'  => $validatedData['cost_per_generation'],
            'default_width'        => $validatedData['default_width'] ?? 1024,
            'default_height'       => $validatedData['default_height'] ?? 1024,
            'default_parameters'   => $validatedData['default_parameters'],
            'description'          => $validatedData['description'],
            'is_active'            => $validatedData['is_active'],
        ]);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'اطلاعات مدل هوش مصنوعی با موفقیت به‌روزرسانی شد.');
    }

    public function destroy($id)
    {
        $model = AiModel::findOrFail($id);
        
        // حذف فیزیکی عکس مدل از سرور هنگام حذف از دیتابیس
        $safeName = str_replace(['/', '\\', ':', '*'], '-', $model->openrouter_model_id);
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $file = public_path('uploads/models/' . $safeName . '.' . $ext);
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $model->delete();

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'مدل هوش مصنوعی با موفقیت از پایگاه داده حذف شد.');
    }
}