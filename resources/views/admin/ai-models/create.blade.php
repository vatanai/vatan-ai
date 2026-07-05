@extends('layouts.admin')
@section('title', 'ثبت مدل هوش مصنوعی جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">
  <main class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px]" id="content">

      @if ($errors->any())
        <div class="bg-[#f05c5c]/10 border border-[#f05c5c] rounded-xl p-4 mb-6 text-right">
            <div class="text-[#f05c5c] font-bold text-sm mb-2">اصلاح خطاهای زیر الزامی است:</div>
            <ul class="text-[#ff9191] text-xs pr-5 list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
          <div class="text-xl font-extrabold tracking-tight mb-1">ثبت مدل هوش مصنوعی جدید</div>
          <div class="text-xs text-gray-500">اتصال و کانفیگ پارامترها و مشخصات ظاهری مدل‌های جدید سرویس</div>
        </div>
        <a href="{{ route('admin.ai-models.index') }}" class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[#16161c] text-[#a8c4a8] border border-[#222230] transition-all hover:text-white no-underline">
          <i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت به لیست
        </a>
      </div>

      <form action="{{ route('admin.ai-models.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="is_active" id="model-status" value="{{ old('is_active', '1') }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
          <div class="lg:col-span-2 space-y-4">
            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
              <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]">
                <i class="fa-solid fa-circle-nodes text-[#a07af5]"></i> اطلاعات هویتی و فنی مدل
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[#a8c4a8]">نام نمایشی مدل <span class="text-[#f05c5c] mr-0.5">*</span></label>
                  <input type="text" name="name" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] w-full" value="{{ old('name') }}" placeholder="مثلا: GPT-4o Omni" required>
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[#a8c4a8]">شناسه مدل در OpenRouter <span class="text-[#f05c5c] mr-0.5">*</span></label>
                  <input type="text" name="openrouter_model_id" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] w-full ltr text-left font-mono" value="{{ old('openrouter_model_id') }}" placeholder="openai/gpt-4o" required>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[#a8c4a8]">شرکت سازنده (Provider Name) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                  <input type="text" name="provider_name" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] w-full" value="{{ old('provider_name') }}" placeholder="مثلا: OpenAI یا Google" required>
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[#a8c4a8]">نوع خروجی (Output Modality) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                  <select name="output_modality" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] w-full cursor-pointer" required>
                    <option value="text" {{ old('output_modality') == 'text' ? 'selected' : '' }}>متن (image / text)</option>
                    <option value="image" {{ old('output_modality') == 'image' ? 'selected' : '' }}>عکس (image)</option>
                    <option value="video" {{ old('output_modality') == 'video' ? 'selected' : '' }}>ویدیو (video)</option>
                    <option value="audio" {{ old('output_modality') == 'audio' ? 'selected' : '' }}>صدا (audio)</option>
                  </select>
                </div>
              </div>

              <div class="flex flex-col gap-1.5 mb-4">
                <label class="text-xs font-semibold text-[#a8c4a8]">توضیحات مدل</label>
                <textarea name="description" rows="3" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] w-full resize-none leading-relaxed" placeholder="یادداشت یا توضیحات کوتاه مدل...">{{ old('description') }}</textarea>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">پارامترهای پیش‌فرض (JSON Configuration)</label>
                <textarea name="default_parameters" rows="4" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] w-full font-mono text-left ltr resize-none" placeholder='{ "temperature": 0.7 }'>{{ old('default_parameters', "{\n  \"temperature\": 0.7\n}") }}</textarea>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
              <div class="text-xs font-bold text-white mb-3 flex items-center gap-2 pb-2 border-b border-[#222230]">
                <i class="fa-regular fa-image text-[#a07af5]"></i> لوگو یا عکس مدل هوش مصنوعی
              </div>
              <div class="flex flex-col items-center justify-center border border-dashed border-[#333345] bg-[#111116] rounded-xl p-4 transition hover:border-[#a07af5] relative group min-h-[140px]">
                <img id="image-preview" class="hidden w-16 h-16 object-contain rounded-lg mb-2 shadow" src="" alt="Preview">
                <div id="upload-placeholder" class="flex flex-col items-center justify-center text-center cursor-pointer">
                  <i class="fa-solid fa-cloud-arrow-up text-lg text-gray-500 mb-1.5 group-hover:text-[#a07af5] transition-colors"></i>
                  <span class="text-[11px] text-[#a8c4a8] font-medium">انتخاب تصویر برای مدل</span>
                  <span class="text-[9px] text-gray-600 mt-0.5">برای انواع مدل‌ها (متنی، تصویری، ویدیویی، صوتی)</span>
                </div>
                <input type="file" name="model_image" id="model_image" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" onchange="previewImage(this)">
              </div>
            </div>

            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-4 flex items-center justify-between">
              <div>
                <div class="text-xs font-bold text-white mb-0.5">وضعیت فعال بودن</div>
                <div class="text-[10px] text-gray-500">امکان فراخوانی گیت‌وی در سایت</div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer select-none">
                <input type="checkbox" id="status-toggle" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer" onchange="document.getElementById('model-status').value = this.checked ? '1' : '0'">
                <div class="w-9 h-5 bg-[#222230] rounded-full peer peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:right-[2px] after:bg-gray-500 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#a07af5] peer-checked:after:bg-white"></div>
              </label>
            </div>

            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5 space-y-4">
              <div class="text-xs font-bold text-white pb-2 border-b border-[#222230]"><i class="fa-solid fa-wand-magic-sparkles text-[#a07af5] ml-1"></i> ویژگی‌ها و هزینه‌ها</div>
              
              <div class="flex flex-col gap-1.5">
                <label class="text-[11px] text-[#a8c4a8]">پشتیبانی از ورودی عکس (Vision)</label>
                <select name="supports_image_input" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] cursor-pointer">
                  <option value="0" {{ old('supports_image_input') == '0' ? 'selected' : '' }}>خیر (فقط ورودی متنی)</option>
                  <option value="1" {{ old('supports_image_input') == '1' ? 'selected' : '' }}>بله (دارای قابلیت خواندن تصویر)</option>
                </select>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-[11px] text-[#a8c4a8]">هزینه به توکن AIPix (هر جنریت) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <input type="number" name="cost_per_generation" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] font-mono text-left ltr" value="{{ old('cost_per_generation', 1) }}" required>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-1.5">
                  <label class="text-[11px] text-[#a8c4a8]">عرض پیش‌فرض (پیکسل)</label>
                  <input type="number" name="default_width" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] font-mono text-left ltr" value="{{ old('default_width', 1024) }}">
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[11px] text-[#a8c4a8]">ارتفاع پیش‌فرض (پیکسل)</label>
                  <input type="number" name="default_height" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none focus:border-[#a07af5] font-mono text-left ltr" value="{{ old('default_height', 1024) }}">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="fixed bottom-0 right-0 left-0 bg-[#111116]/80 backdrop-blur border-t border-[#222230] px-6 h-16 flex items-center justify-end gap-3 z-40 md:mr-[294px]">
          <button type="submit" class="px-5 h-[38px] rounded-lg text-xs font-bold bg-[#a07af5] text-[#0c0c10] shadow-lg shadow-[#a07af5]/10 hover:bg-[#8f68e0] transition-all flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-floppy-disk text-[13px]"></i>
            ذخیره و ثبت مدل هوش مصنوعی
          </button>
        </div>
      </form>

    </div>
  </main>
</div>

<script>
  function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('upload-placeholder');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        placeholder.classList.add('opacity-40');
      }
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
@endsection