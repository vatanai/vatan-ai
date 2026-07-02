<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    /**
     * نمایش لیست تمام مدل‌های هوش مصنوعی
     */
    public function index()
    {
        $models = AiModel::latest()->get();
        return view('admin.ai-models.index', compact('models'));
    }

    /**
     * نمایش فرم افزودن مدل جدید
     */
    public function create()
    {
        return view('admin.ai-models.create');
    }

    /**
     * ذخیره‌سازی مدل جدید در دیتابیس
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'model_id' => 'required|string|max:255|unique:ai_models,model_id',
            'provider' => 'required|string|max:255',
            'fallback_models' => 'nullable|array',
        ], [
            'name.required' => 'وارد کردن نام مدل الزامی است.',
            'model_id.required' => 'شناسه مدل از OpenRouter الزامی است.',
            'model_id.unique' => 'این شناسه مدل قبلاً ثبت شده است.',
            'provider.required' => 'نام ارائه‌دهنده الزامی است.',
        ]);

        AiModel::create([
            'name' => $request->name,
            'model_id' => $request->model_id,
            'provider' => $request->provider,
            'supports_vision' => $request->has('supports_vision'), // حل مشکل دکمه ویژن
            'is_active' => $request->has('is_active'),
            'fallback_models' => $request->fallback_models ?? [], // ذخیره مدل‌های جایگزین
        ]);

        return redirect()
            ->route('admin.ai-models.index')
            ->with('success', 'مدل هوش مصنوعی جدید با موفقیت به سیستم اضافه شد.');
    }

    /**
     * نمایش فرم ویرایش مدل هوش مصنوعی
     */
    public function edit(AiModel $aiModel)
    {
        // اگر روت شما به صورت {ai_model} است، لاراول به طور خودکار Model Binding را انجام می‌دهد.
        // در غیر این صورت اگر به مشکل خوردید، ورودی را تبدیل به ($id) کنید و با AiModel::findOrFail($id) بگیرید.
        return view('admin.ai-models.edit', ['model' => $aiModel]);
    }

    /**
     * به‌روزرسانی اطلاعات مدل در دیتابیس
     */
    public function update(Request $request, AiModel $aiModel)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'model_id' => 'required|string|max:255|unique:ai_models,model_id,' . $aiModel->id,
            'provider' => 'required|string|max:255',
            'fallback_models' => 'nullable|array',
        ], [
            'name.required' => 'وارد کردن نام مدل الزامی است.',
            'model_id.required' => 'شناسه مدل از OpenRouter الزامی است.',
            'model_id.unique' => 'این شناسه مدل قبلاً ثبت شده است.',
            'provider.required' => 'نام ارائه‌دهنده الزامی است.',
        ]);

        $aiModel->update([
            'name' => $request->name,
            'model_id' => $request->model_id,
            'provider' => $request->provider,
            'supports_vision' => $request->has('supports_vision'), // آپدیت وضعیت ویژن
            'is_active' => $request->has('is_active'),
            'fallback_models' => $request->fallback_models ?? [],
        ]);

        return redirect()
            ->route('admin.ai-models.index')
            ->with('success', 'تغییرات مدل هوش مصنوعی با موفقیت اعمال شد.');
    }

    /**
     * حذف فیزیکی مدل هوش مصنوعی از سیستم
     */
    public function destroy(AiModel $aiModel)
    {
        $aiModel->delete();

        return redirect()
            ->route('admin.ai-models.index')
            ->with('success', 'مدل هوش مصنوعی مورد نظر با موفقیت از سیستم حذف شد.');
    }
}