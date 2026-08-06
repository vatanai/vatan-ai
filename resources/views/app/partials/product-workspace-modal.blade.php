{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: مودال تخصصی میز کار هوش مصنوعی (فرآیند واقعی «بساز»)
     + مودال «برای ادامه باید وارد شوید» (سیو/لایک کاربر مهمان)
     منتقل‌شده از product.blade.php قدیمی — منطق تولید بدون تغییر.
     ═══════════════════════════════════════════════════════════════ --}}
@php
  // آیا محصول حداقل یک فیلد آپلود تصویر/فایل داخل تنظیمات داینامیک (input_schema) دارد؟
  $__hasSchemaUpload = collect($product->input_schema ?? [])->contains(function ($f) {
      return in_array($f['type'] ?? '', ['image_upload', 'file_upload'], true);
  });
@endphp

{{-- مودال تخصصی میز کار هوش مصنوعی --}}
<div id="workspaceModal" class="fixed inset-0 z-[400] hidden opacity-0 transition-opacity duration-300 items-center justify-center bg-black/85 backdrop-blur-md p-4">
  <div id="modalContent" class="bg-[#121214] [.light_&]:bg-white border border-white/[0.06] [.light_&]:border-black/10 w-full max-w-6xl h-[90vh] max-h-[750px] rounded-[28px] flex flex-col overflow-hidden scale-95 transition-transform duration-300 shadow-2xl">

    {{-- هدر مودال --}}
    <div class="p-4 border-b border-white/[0.04] [.light_&]:border-black/10 flex items-center justify-between shrink-0 bg-[#161619] [.light_&]:bg-[#f5f5f5]">
      <div class="flex items-center gap-2.5">
        <div class="w-6 h-6 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
          <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
        </div>
        <h3 class="text-[13px] font-bold text-gray-200 [.light_&]:text-gray-800">میز کار تخصصی تولید تصویر هوش مصنوعی</h3>
      </div>
      <button type="button" onclick="closeWorkspaceModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/[0.03] [.light_&]:bg-black/[0.04] text-gray-400 hover:text-white [.light_&]:hover:text-black hover:bg-white/10 [.light_&]:hover:bg-black/10 transition-colors">
        <i class="fa-solid fa-xmark text-sm"></i>
      </button>
    </div>

    {{-- بدنه سه ستونه مودال --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 overflow-hidden divide-y lg:divide-y-0 lg:divide-x lg:divide-x-reverse divide-white/[0.04] [.light_&]:divide-black/10">

      {{-- ستون اول (راست): تصویر الگو --}}
      <div class="p-5 bg-[#0e0e10] [.light_&]:bg-[#fafafa] flex flex-col h-full overflow-hidden">
        <div class="shrink-0">
          <span class="inline-block px-2.5 py-1 rounded-md bg-white/[0.03] [.light_&]:bg-black/[0.04] border border-white/[0.05] [.light_&]:border-black/10 text-[10px] font-bold text-gray-400 [.light_&]:text-gray-600 mb-2">
            ۱. تصویر الگو (محصول شما)
          </span>
          <p class="text-[11px] text-gray-500 [.light_&]:text-gray-600 mb-3 leading-relaxed">این تصویر مبنای طراحی هوش مصنوعی است.</p>
        </div>

        <div class="flex-1 min-h-0 border border-white/[0.03] [.light_&]:border-black/5 bg-[#070708] [.light_&]:bg-black/[0.03] rounded-2xl p-4 flex items-center justify-center overflow-hidden">
          <img src="{{ $product->displayImageUrl() }}"
               alt="Product Template" class="max-w-full max-h-full object-contain rounded-xl shadow-lg">
        </div>

        {{-- تنظیم نسبت تصویر --}}
        <div class="shrink-0 mt-4 pt-3 border-t border-white/[0.03] [.light_&]:border-black/10">
          <p class="text-[10px] font-bold text-gray-500 [.light_&]:text-gray-600 mb-2">تنظیم نسبت تصویر خروجی:</p>
          <div class="flex gap-1.5 flex-wrap">
            @php $__ratioLabels = ['1:1'=>'مربع','4:5'=>'عمودی ۴:۵','3:4'=>'عمودی ۳:۴','2:3'=>'عمودی ۲:۳','9:16'=>'استوری','16:9'=>'افقی ۱۶:۹','3:2'=>'افقی ۳:۲']; @endphp
            @foreach($product->allowedAspectRatioList() as $val)
            @php $lbl = $__ratioLabels[$val] ?? $val; @endphp
            <label class="cursor-pointer">
              <input type="radio" name="modal_ratio" value="{{ $val }}" {{ $loop->first ? 'checked' : '' }} class="sr-only peer">
              <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-[10px] font-bold border border-white/[0.05] [.light_&]:border-black/10 bg-white/[0.01] [.light_&]:bg-black/[0.02] text-gray-400 [.light_&]:text-gray-600 peer-checked:border-indigo-500/50 peer-checked:bg-indigo-500/10 peer-checked:text-indigo-400 transition-all">
                {{ $lbl }}
              </span>
            </label>
            @endforeach
          </div>
        </div>
      </div>

      {{-- ستون دوم (وسط): بخش آپلود تصویر کاربر (در صورت نیاز) و دکمه ساخت --}}
      <div class="p-5 bg-[#121214] [.light_&]:bg-white flex flex-col h-full overflow-hidden">
        <div class="flex-1 flex flex-col min-h-0 gap-3">
          <span class="inline-block px-2.5 py-1 rounded-md bg-white/[0.03] [.light_&]:bg-black/[0.04] border border-white/[0.05] [.light_&]:border-black/10 text-[10px] font-bold text-gray-400 [.light_&]:text-gray-600 self-start">
            ۲. بارگذاری تصویر ورودی
          </span>

          @if($__hasSchemaUpload)
            {{-- محصول از فیلد(های) آپلود تعریف‌شده در «تنظیمات محصول» صفحه استفاده می‌کند --}}
            <div class="flex-1 min-h-0 border border-dashed border-white/[0.06] [.light_&]:border-black/10 bg-[#070708] [.light_&]:bg-black/[0.03] rounded-2xl p-4 flex items-center justify-center text-center">
              <p class="text-[11px] text-gray-500 [.light_&]:text-gray-600 leading-relaxed">
                <i class="fa-solid fa-circle-check text-emerald-500 ml-1"></i>
                تصویر ورودی از بخش «تنظیمات محصول» صفحه دریافت می‌شود.
              </p>
            </div>
          @else
            <div onclick="document.getElementById('modalFileInp').click()"
                 class="w-full shrink-0 rounded-2xl border border-dashed border-white/[0.08] [.light_&]:border-black/15 bg-white/[0.01] [.light_&]:bg-black/[0.02] py-4 px-5
                        flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-indigo-500/40
                        hover:bg-indigo-500/[0.02] transition-all text-center group">
              <div class="w-9 h-9 rounded-xl bg-white/[0.03] [.light_&]:bg-black/[0.04] border border-white/[0.05] [.light_&]:border-black/10 group-hover:bg-indigo-500/10 group-hover:border-indigo-500/20 flex items-center justify-center text-gray-400 [.light_&]:text-gray-600 group-hover:text-indigo-400 transition-all">
                <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
              </div>
              <div>
                <p class="text-[11px] font-bold text-gray-200 [.light_&]:text-gray-800">انتخاب تصویر جدید</p>
              </div>
              <input type="file" id="modalFileInp" accept="image/*" class="hidden" onchange="handleModalUpload(this)">
            </div>

            <div class="flex-1 min-h-0 border border-white/[0.03] [.light_&]:border-black/5 bg-[#070708] [.light_&]:bg-black/[0.03] rounded-2xl p-4 flex items-center justify-center relative overflow-hidden">
              <img id="userImagePreview" src="" alt="User Source" class="hidden max-w-full max-h-full object-contain rounded-xl">
              <div id="userImagePlaceholder" class="text-center text-gray-600 flex flex-col items-center gap-2">
                <i class="fa-solid fa-user-astronaut text-lg opacity-40"></i>
                <p class="text-[10px]">تصویر شما هنوز آپلود نشده است</p>
              </div>
            </div>
          @endif
        </div>

        {{-- انتخاب مدل‌های خروجی چندگانه (فقط برای محصولات دارای واریانت) --}}
        @include('app.partials.product-output-variants', ['product' => $product])

        <div class="shrink-0 mt-4 pt-3 border-t border-white/[0.03] [.light_&]:border-black/10 space-y-2">
          <div id="modalFormError" class="hidden p-2.5 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl text-[10px] font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="modalFormErrorTxt"></span>
          </div>

          {{-- جمع نهایی توکن برای ساخت — بر اساس تعداد مدل‌های خروجی تیک‌خورده به‌روز می‌شود --}}
          @if(count($product->outputVariantList()))
            <div id="variantTokenTotal" class="flex items-center justify-between px-3 h-9 bg-white/[0.02] [.light_&]:bg-black/[0.03] border border-white/[0.05] [.light_&]:border-black/10 rounded-xl text-[10px] font-bold">
              <span class="text-gray-400 [.light_&]:text-gray-600"><i class="fa-solid fa-bolt text-orange-400 text-[9px] ml-1"></i> جمع توکن برای ساخت</span>
              <span class="text-orange-400" id="variantTokenTotalNum">—</span>
            </div>
          @endif

          <button type="button" onclick="triggerGeneration()" id="btnModalSubmit"
                  class="w-full h-12 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg active:scale-[0.98]">
            <i class="fa-solid fa-bolt text-[11px]"></i> بساز (شروع رندر)
          </button>
        </div>
      </div>

      {{-- ستون سوم (چپ): خروجی رندر شده نهایی هوش مصنوعی --}}
      <div class="p-5 bg-[#0a0a0c] [.light_&]:bg-[#fafafa] flex flex-col h-full overflow-hidden">
        <div class="shrink-0">
          <span class="inline-block px-2.5 py-1 rounded-md bg-white/[0.03] [.light_&]:bg-black/[0.04] border border-white/[0.05] [.light_&]:border-black/10 text-[10px] font-bold text-gray-400 [.light_&]:text-gray-600 mb-2">
            ۳. خروجی تصویر نهایی
          </span>
        </div>

        <div class="flex-1 min-h-0 border border-white/[0.04] [.light_&]:border-black/10 bg-[#040405] [.light_&]:bg-black/[0.03] rounded-2xl p-4 flex items-center justify-center relative overflow-hidden">
          <img id="modalOutputImage" src="" alt="AI Output" class="hidden max-w-full max-h-full object-contain rounded-xl shadow-2xl">

          {{-- خروجی چندتایی (مدل‌های خروجی چندگانه) — گرید تصاویر ساخته‌شده --}}
          <div id="modalOutputGrid" class="hidden absolute inset-0 p-3 overflow-y-auto grid grid-cols-2 gap-2 content-start"
               style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.08) transparent"></div>

          <div id="outputPlaceholder" class="text-center text-gray-600 flex flex-col items-center gap-2">
            <i class="fa-solid fa-sparkles text-xl text-gray-700"></i>
            <p class="text-[11px] text-gray-500">پس از کلیک روی دکمه ساخت، نتیجه اینجا نمایش داده می‌شود.</p>
          </div>

          {{-- انیمیشن و اورلی مراحل لودینگ هوش مصنوعی --}}
          <div id="modalProgressOverlay" class="hidden absolute inset-0 bg-[#0a0a0c]/95 [.light_&]:bg-white/95 backdrop-blur-md flex-col items-center justify-center text-center p-6 z-20 animate-fade-in">
            <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mb-4 shadow-inner">
              <i class="fa-solid fa-wand-magic-sparkles fa-spin text-xl text-indigo-400"></i>
            </div>
            <p id="modalPgTxt" class="text-[13px] font-bold text-white [.light_&]:text-gray-900 mb-1">در حال شروع فرآیند...</p>
            <p id="modalPgSub" class="text-[10px] text-gray-500 mb-4">سیستم در حال آماده‌سازی خط پردازش است</p>
            <div class="bg-white/5 [.light_&]:bg-black/5 rounded-full h-1 overflow-hidden w-40 mx-auto">
              <div id="modalPgBar" class="h-full bg-indigo-500 rounded-full transition-all duration-700 ease-out" style="width: 0%"></div>
            </div>
          </div>
        </div>

        <div class="shrink-0 mt-4 pt-3 border-t border-white/[0.03] [.light_&]:border-black/10">
          <button id="modalDlBtn" disabled onclick="downloadGeneratedImage()"
            class="w-full h-10 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold text-[11px]
                   rounded-xl flex items-center justify-center gap-2 transition-colors
                   border border-emerald-500/10 cursor-pointer disabled:opacity-20 disabled:cursor-not-allowed">
            <i class="fa-solid fa-download text-[11px]"></i>
            دانلود این خروجی
          </button>
        </div>
      </div>

    </div>

  </div>
</div>

{{-- مودال «برای ادامه میبایست وارد شوید» — برای کاربر مهمان هنگام کلیک روی سیو یا لایک --}}
<div id="saveLoginModal" class="fixed inset-0 z-[410] hidden opacity-0 transition-opacity duration-300 items-center justify-center bg-black/85 backdrop-blur-md p-4" dir="rtl">
  <div id="saveLoginModalContent" class="bg-[#121218] [.light_&]:bg-white border border-white/10 [.light_&]:border-black/10 w-full max-w-sm rounded-[24px] overflow-hidden scale-95 transition-transform duration-300 shadow-2xl relative p-6 text-center flex flex-col items-center gap-4">

    <button type="button" onclick="closeSaveLoginModal()" class="absolute top-4 left-4 w-7 h-7 flex items-center justify-center rounded-full bg-white/[0.03] [.light_&]:bg-black/[0.04] text-gray-400 hover:text-white [.light_&]:hover:text-black hover:bg-white/10 [.light_&]:hover:bg-black/10 transition-colors cursor-pointer">
      <i class="fa-solid fa-xmark text-xs"></i>
    </button>

    <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-2">
      <i class="fa-regular fa-bookmark text-2xl"></i>
    </div>

    <h3 class="text-[15px] font-black text-gray-100 [.light_&]:text-gray-900">برای این کار میبایست به پروفایل خود وارد شوید</h3>

    <div class="w-full grid grid-cols-1 gap-2 mt-2">
      <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="w-full h-11 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-[0.99] no-underline">
        <i class="fa-solid fa-right-to-bracket text-xs"></i>
        ورود
      </a>
      <button type="button" onclick="closeSaveLoginModal()" class="w-full h-10 bg-white/[0.03] [.light_&]:bg-black/[0.04] hover:bg-white/10 [.light_&]:hover:bg-black/10 text-gray-400 [.light_&]:text-gray-600 hover:text-white [.light_&]:hover:text-black font-bold text-[11px] rounded-xl transition-colors cursor-pointer">
        بعداً
      </button>
    </div>

  </div>
</div>

<style>
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.animate-fade-in { animation: fadeIn 0.25s ease-out forwards; }
</style>
