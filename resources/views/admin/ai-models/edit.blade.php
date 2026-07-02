@extends('layouts.admin')
@section('title', 'ویرایش مدل هوش مصنوعی — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <a href="{{ route('admin.ai-models.index') }}" class="hover:text-white transition-colors">مدل‌های هوش مصنوعی</a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <span class="text-white font-semibold">ویرایش مدل</span>
      </div>
    </header>

    <main class="p-6 flex-1 flex justify-center items-start">
      
      <div class="w-full max-w-2xl bg-[#111116] border border-[#222230] rounded-2xl shadow-2xl overflow-hidden p-6 sm:p-8 relative">
        
        <div class="absolute top-[-20%] left-[-20%] w-72 h-72 bg-[#a07af5]/5 rounded-full blur-[80px] pointer-events-none"></div>

        <div class="mb-6 select-none relative z-10">
          <h2 class="text-lg font-extrabold tracking-tight text-white flex items-center gap-2">
            <i class="fa-regular fa-pen-to-square text-[#a07af5] text-base"></i>
            ویرایش تنظیمات مدل: <span class="text-[#a07af5]">{{ $model->name }}</span>
          </h2>
          <p class="mt-1 text-[11px] text-[#4d7a56]">تغییر کانفیگ، شناسه موتور و دسترسی‌های فریم‌ورک OpenRouter</p>
        </div>

        @if($errors->any())
          <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-3.5 mb-5 flex items-start gap-2.5 relative z-10">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5 shrink-0 text-xs"></i>
            <div class="text-[11px] text-rose-400 font-medium leading-relaxed">
              {{ $errors->first() }}
            </div>
          </div>
        @endif

        <form class="space-y-5 relative z-10" action="{{ route('admin.ai-models.update', $model->id) }}" method="POST">
          @csrf
          @method('PUT') <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="name" class="block text-xs font-semibold text-[#a8c4a8] mb-1.5 pr-0.5">نام مدل (فارسی)</label>
              <input id="name" name="name" type="text" required value="{{ old('name', $model->name) }}"
                     class="block w-full px-4 py-2.5 bg-[#0c0c10] border border-[#222230] rounded-xl text-xs text-white placeholder-gray-600 outline-none transition-all focus:border-[#a07af5] focus:ring-4 focus:ring-[#a07af5]/5"
                     placeholder="مثال: جی‌پی‌تی 4 او">
            </div>

            <div>
              <label for="provider" class="block text-xs font-semibold text-[#a8c4a8] mb-1.5 pr-0.5">کمپانی ارائه‌دهنده (Provider)</label>
              <input id="provider" name="provider" type="text" required value="{{ old('provider', $model->provider) }}"
                     class="block w-full px-4 py-2.5 bg-[#0c0c10] border border-[#222230] rounded-xl text-xs text-white placeholder-gray-600 outline-none transition-all focus:border-[#a07af5] focus:ring-4 focus:ring-[#a07af5]/5"
                     placeholder="مثال: OpenAI یا Google">
            </div>
          </div>

          <div>
            <label for="model_id" class="block text-xs font-semibold text-[#a8c4a8] mb-1.5 pr-0.5">شناسه اِی‌پی‌آی (Model ID)</label>
            <input id="model_id" name="model_id" type="text" required value="{{ old('model_id', $model->model_id) }}"
                   class="block w-full px-4 py-2.5 bg-[#0c0c10] border border-[#222230] rounded-xl text-xs text-white placeholder-gray-600 outline-none transition-all focus:border-[#a07af5] focus:ring-4 focus:ring-[#a07af5]/5 ltr text-left font-mono tracking-wide"
                   placeholder="openai/gpt-4o-mini">
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            
            <div class="bg-[#0c0c10] border border-[#222230] rounded-xl p-3.5 flex items-center justify-between select-none">
              <div class="flex flex-col gap-0.5">
                <span class="text-xs font-bold text-white">وضعیت فعالیت مدل</span>
                <span class="text-[10px] text-[#4d7a56]">در دسترس بودن مدل برای کاربران سیستم</span>
              </div>
              <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $model->is_active) ? 'checked' : '' }}
                     class="h-4 w-4 bg-[#0c0c10] border-[#222230] rounded text-[#a07af5] focus:ring-offset-0 focus:ring-[#a07af5]/30 cursor-pointer">
            </div>

            <div class="bg-[#0c0c10] border border-[#222230] rounded-xl p-3.5 flex items-center justify-between select-none">
              <div class="flex flex-col gap-0.5">
                <span class="text-xs font-bold text-white">پشتیبانی از Vision</span>
                <span class="text-[10px] text-[#4d7a56]">توانایی آنالیز تصاویر و ورودی‌های مولتی‌مدیا</span>
              </div>
              <input id="supports_vision" name="supports_vision" type="checkbox" value="1" {{ old('supports_vision', $model->supports_vision) ? 'checked' : '' }}
                     class="h-4 w-4 bg-[#0c0c10] border-[#222230] rounded text-[#a07af5] focus:ring-offset-0 focus:ring-[#a07af5]/30 cursor-pointer">
            </div>

          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#222230]/70">
            <a href="{{ route('admin.ai-models.index') }}" 
               class="px-4 h-10 rounded-xl text-xs font-semibold bg-[#222230] text-gray-400 hover:text-white flex items-center justify-center transition-colors no-underline">
              انصراف
            </a>
            <button type="submit" 
                    class="px-5 h-10 flex justify-center items-center gap-1.5 rounded-xl text-xs font-bold bg-[#a07af5] text-black shadow-lg shadow-[#a07af5]/10 hover:bg-[#8f68e0] active:scale-[0.98] transition-all cursor-pointer">
              <i class="fa-regular fa-circle-check text-[13px]"></i> ذخیره تغییرات نهایی
            </button>
          </div>
        </form>

      </div>

    </main>
  </div>
</div>
@endsection