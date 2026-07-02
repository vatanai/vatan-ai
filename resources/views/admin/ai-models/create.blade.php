@extends('layouts.admin')
@section('title', 'ثبت مدل هوش مصنوعی جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="{{ route('admin.dashboard') }}" class="text-[#a8c4a8] hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <a href="{{ route('admin.ai-models.index') }}" class="text-[#a8c4a8] hover:text-white transition-colors">مدل‌های هوش مصنوعی</a>
        <i class="fa-solid fa-chevron-left text-[10px] text-[#4d7a56]"></i>
        <span class="text-white font-semibold">ثبت مدل جدید</span>
      </div>
      <a href="{{ route('admin.ai-models.index') }}" class="inline-flex items-center gap-1.5 px-3.5 h-[34px] rounded-lg text-xs font-semibold bg-[#16161c] text-[#a8c4a8] border border-[#222230] transition-all hover:border-[#2e2e3e] hover:text-white no-underline">
        <i class="fa-solid fa-arrow-right text-[11px]"></i>
        بازگشت به لیست
      </a>
    </header>

    <main class="p-6 flex-1 pb-24">

      @if ($errors->any())
        <div class="bg-[#f05c5c]/10 border border-[#f05c5c] rounded-xl p-4 mb-6 text-right">
            <div class="text-[#f05c5c] font-bold text-sm mb-2.5">
                <i class="fa-solid fa-triangle-exclamation"></i> اصلاح خطاهای زیر برای ثبت مدل الزامی است:
            </div>
            <ul class="text-[#ff9191] text-xs pr-5 margin-0 list-disc space-y-1.5 leading-relaxed">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">ثبت مدل هوش مصنوعی جدید</div>
        <div class="text-xs text-[#4d7a56]">تنظیم و متصل‌سازی مدل‌های ارائه‌دهنده سرویس OpenRouter به پلتفرم</div>
      </div>

      <form id="real-model-form" action="{{ route('admin.ai-models.store') }}" method="POST">
        @csrf
        <input type="hidden" name="is_active" id="model-status" value="{{ old('is_active', '1') }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
          
          <div class="lg:col-span-2 space-y-4">
            
            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
              <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]">
                <i class="fa-solid fa-circle-nodes text-[#a07af5]"></i> هویت و شناسه کلاینت مدل
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[#a8c4a8]">نام مدل (فارسی) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                  <input type="text" name="name" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('name') }}" placeholder="مثال: جی‌پی‌تی 4 اومنی">
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-[#a8c4a8]">کمپانی ارائه‌دهنده (Provider) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                  <input type="text" name="provider" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5]" value="{{ old('provider') }}" placeholder="مثال: OpenAI یا Google">
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#a8c4a8]">شناسه مدل در اوپن‌روتر (Model ID) <span class="text-[#f05c5c] mr-0.5">*</span></label>
                <input type="text" name="model_id" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white outline-none transition-colors w-full focus:border-[#a07af5] ltr text-left font-mono tracking-wide" value="{{ old('model_id') }}" placeholder="openai/gpt-4o-mini">
                <p class="text-[10px] text-[#4d7a56] mt-1">شناسه را دقیقاً مشابه شناسه رسمی مستندات OpenRouter وارد کنید.</p>
              </div>
            </div>

            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
              <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230] justify-between">
                <div class="flex items-center gap-2">
                  <i class="fa-solid fa-sliders text-[#a07af5]"></i> مدل‌های جایگزین (Fallback Models)
                </div>
                <button type="button" onclick="addFallbackRow()" class="bg-[#a07af5]/10 border border-[#a07af5]/20 text-[#a07af5] text-[10px] font-bold px-2 py-1 rounded hover:bg-[#a07af5]/20 transition-all">
                  <i class="fa-solid fa-plus ml-0.5"></i> افزودن ردیف
                </button>
              </div>

              <div id="fallbacks-container" class="space-y-2.5">
                </div>
            </div>

          </div>

          <div class="space-y-4">
            
            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-4 flex items-center justify-between">
              <div>
                <div class="text-xs font-bold text-white mb-0.5">وضعیت مدل</div>
                <div class="text-[10px] text-[#4d7a56]">قابلیت استفاده در سیستم</div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer select-none">
                <input type="checkbox" id="status-toggle" checked class="sr-only peer" onchange="document.getElementById('model-status').value = this.checked ? '1' : '0'">
                <div class="w-9 h-5 bg-[#222230] rounded-full peer peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:right-[2px] after:bg-[#a8c4a8] after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#a07af5] peer-checked:after:bg-white"></div>
              </label>
            </div>

            <div class="bg-[#16161c] border border-[#222230] rounded-xl p-5 space-y-4">
              <div class="text-xs font-bold text-white pb-2 border-b border-[#222230]"><i class="fa-solid fa-wand-magic-sparkles text-[#a07af5] ml-1"></i> ویژگی‌های مدل</div>
              
              <div class="flex items-center justify-between">
                <span class="text-xs text-[#a8c4a8]">پشتیبانی از پردازش تصویر (Vision)</span>
                <label class="relative inline-flex items-center cursor-pointer select-none">
                  <input type="checkbox" name="supports_vision" value="1" class="sr-only peer" {{ old('supports_vision') ? 'checked' : '' }}>
                  <div class="w-9 h-5 bg-[#222230] rounded-full peer peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:right-[2px] after:bg-[#a8c4a8] after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#a07af5] peer-checked:after:bg-white"></div>
                </label>
              </div>
            </div>

          </div>

        </div>

        <div class="fixed bottom-0 right-0 left-0 bg-[#111116]/80 backdrop-blur border-t border-[#222230] px-6 h-16 flex items-center justify-end gap-3 z-40 md:mr-64">
          <button type="submit" class="px-5 h-[38px] rounded-lg text-xs font-bold bg-[#a07af5] text-[#0c0c10] shadow-lg shadow-[#a07af5]/10 hover:bg-[#8f68e0] transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-floppy-disk text-[13px]"></i>
            ذخیره و ثبت مدل
          </button>
        </div>

      </form>
    </main>
  </div>
</div>

<script>
  let fallbackCount = 0;

  function addFallbackRow(initialValue = '') {
    const container = document.getElementById('fallbacks-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 fallback-row bg-[#111116] p-2 rounded-lg border border-[#222230]';
    div.innerHTML = `
      <span class="text-[10px] font-mono text-gray-600 w-5 text-center">${fallbackCount + 1}.</span>
      <input type="text" name="fallback_models[${fallbackCount}]" class="bg-[#16161c] border border-[#222230] rounded-lg p-2 text-xs text-white outline-none focus:border-[#a07af5] flex-1 font-mono text-left ltr" placeholder="e.g. anthropic/claude-3-haiku" value="${initialValue}">
      <button type="button" class="w-8 h-8 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 text-xs transition-colors flex items-center justify-center" onclick="this.closest('.fallback-row').remove(); reindexFallbacks();">
        <i class="fa-regular fa-trash-can"></i>
      </button>
    `;
    container.appendChild(div);
    fallbackCount++;
  }

  function reindexFallbacks() {
    fallbackCount = 0;
    document.querySelectorAll('.fallback-row').forEach((row, idx) => {
      row.querySelector('span').textContent = `${idx + 1}.`;
      row.querySelector('input').name = `fallback_models[${idx}]`;
      fallbackCount++;
    });
  }

  // اضافه کردن یک ردیف به صورت پیش‌فرض در ابتدای لود صفحه
  document.addEventListener('DOMContentLoaded', () => {
    addFallbackRow();
  });
</script>
@endsection