@extends('layouts.app')

@section('content')
{{-- تنظیم ارتفاع دقیق برای دسکتاپ منهای ۶۴ پیکسلِ هدر لایوت وطن AI --}}
<div class="h-screen sm:h-[calc(100vh-64px)] w-full bg-[#0a0a0c] text-white flex flex-col overflow-hidden relative" dir="rtl">

  {{-- بدنه اصلی صفحه --}}
  <div class="flex-1 flex overflow-hidden">

    {{-- بخش تصویر اصلی پیش‌فرض صفحه --}}
    <div class="flex-1 p-4 md:p-8 flex items-center justify-center relative bg-[#0a0a0c]">
      <button onclick="history.length>1?history.back():location.href='/app/home'"
        class="absolute top-5 left-5 z-20 w-8 h-8 flex items-center justify-center
               rounded-full bg-white/[0.04] hover:bg-white/10 border border-white/[0.06]
               text-gray-400 hover:text-white text-xs transition-colors">
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      <img id="mainImage"
        src="{{ $product->cover ? asset('storage/'.$product->cover) : ($product->thumbnail ? asset('storage/'.$product->thumbnail) : asset('assets/img/placeholder.webp')) }}"
        alt="{{ $product->name_fa }}"
        class="max-w-[85%] max-h-[85%] object-contain rounded-[20px]">

      <div id="successBadge"
           class="hidden absolute top-6 right-6 bg-emerald-500 text-black font-black text-[10px] px-3 py-1.5 rounded-lg shadow-lg">
        <i class="fa-solid fa-check"></i> آماده شد
      </div>
    </div>

    {{-- سایدبار اطلاعات محصول --}}
    <div class="w-[380px] shrink-0 bg-[#121214] border-r border-white/[0.04] flex flex-col h-full overflow-hidden">
      <div class="flex-1 overflow-y-auto p-6 pb-32 sm:pb-6 flex flex-col gap-6"
           style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.06) transparent">

        {{-- هدر سایدبار --}}
        <div class="flex items-start justify-between">
          <h1 class="text-[13px] font-bold text-gray-200 uppercase tracking-widest leading-relaxed max-w-[250px]">
            {{ $product->name_fa }}
          </h1>
          <button onclick="history.length>1?history.back():location.href='/app/home'"
            class="text-gray-500 hover:text-white transition-colors">
            <i class="fa-solid fa-xmark text-sm"></i>
          </button>
        </div>

        {{-- دسته‌بندی و قیمت توکنی ابزار --}}
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 px-3 py-1.5 bg-white/[0.03] border border-white/[0.05] rounded-full">
            <span class="text-[11px] font-bold text-gray-300">
              {{ $product->subcategory ?: $product->category }}
            </span>
            @if($product->pricing_model === 'per_credit')
              <span class="flex items-center gap-1 text-[10px] font-bold text-orange-400">
                <i class="fa-solid fa-bolt text-[9px]"></i>{{ $product->credit_cost }} توکن
              </span>
            @elseif($product->pricing_model === 'free')
              <span class="text-[10px] font-bold text-emerald-400">رایگان</span>
            @endif
          </div>
          
          <div class="flex items-center gap-1.5">
            <button id="btnShare" class="w-8 h-8 bg-white/[0.03] hover:bg-white/10 rounded-full flex items-center justify-center text-gray-400 hover:text-white transition-colors">
              <i class="fa-solid fa-share text-[11px]"></i>
            </button>
            <button id="btnBookmark" class="px-3 h-8 bg-white/[0.03] hover:bg-white/10 border border-white/[0.05] rounded-full flex items-center gap-1.5 text-gray-400 hover:text-white transition-colors">
              <i id="iconBkm" class="fa-regular fa-bookmark text-[11px]"></i>
              <span class="text-[11px] font-bold">ذخیره</span>
            </button>
          </div>
        </div>

        {{-- توضیحات محصول --}}
        @if($product->description_fa)
        <div class="flex items-start gap-2.5 bg-white/[0.01] p-3 rounded-xl border border-white/[0.03]">
          <p class="text-[11px] font-medium text-gray-400 leading-relaxed m-0">
            {{ $product->description_fa }}
          </p>
        </div>
        @endif

        {{-- دکمه ورود به کارگاه ساخت --}}
        <div class="pt-2">
          <button type="button" onclick="openWorkspaceModal()"
                  class="w-full h-12 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-[12px]
                         rounded-xl flex items-center justify-center gap-2 transition-all
                         shadow-lg shadow-indigo-600/20 active:scale-[0.99] cursor-pointer">
            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
            ورود به کارگاه ساخت تصویر
          </button>
        </div>

        {{-- نمونه خروجی‌ها --}}
        @if(is_array($product->sample_outputs) && count($product->sample_outputs))
        <div class="space-y-3 mt-2">
          <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">نمونه خروجی‌ها</h3>
          <div class="grid grid-cols-3 gap-2">
            @foreach(array_slice($product->sample_outputs,0,3) as $s)
            <div class="aspect-square rounded-lg bg-[#1a1a1d] border border-white/5 overflow-hidden">
              <img src="{{ asset('storage/'.$s) }}" class="w-full h-full object-cover opacity-90">
            </div>
            @endforeach
          </div>
        </div>
        @endif

      </div>
    </div>
  </div>

  {{-- مودال تخصصی میز کار هوش مصنوعی --}}
  <div id="workspaceModal" class="fixed inset-0 z-[400] hidden opacity-0 transition-opacity duration-300 items-center justify-center bg-black/85 backdrop-blur-md p-4">
    <div id="modalContent" class="bg-[#121214] border border-white/[0.06] w-full max-w-6xl h-[90vh] max-h-[750px] rounded-[28px] flex flex-col overflow-hidden scale-95 transition-transform duration-300 shadow-2xl">
      
      {{-- هدر مودال --}}
      <div class="p-4 border-b border-white/[0.04] flex items-center justify-between shrink-0 bg-[#161619]">
        <div class="flex items-center gap-2.5">
          <div class="w-6 h-6 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
          </div>
          <h3 class="text-[13px] font-bold text-gray-200">میز کار تخصصی تولید تصویر هوش مصنوعی</h3>
        </div>
        <button type="button" onclick="closeWorkspaceModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-white/[0.03] text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      {{-- بدنه سه ستونه مودال --}}
      <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 overflow-hidden divide-y lg:divide-y-0 lg:divide-x lg:divide-x-reverse divide-white/[0.04]">
        
        {{-- ستون اول (راست): تصویر الگو --}}
        <div class="p-5 bg-[#0e0e10] flex flex-col h-full overflow-hidden">
          <div class="shrink-0">
            <span class="inline-block px-2.5 py-1 rounded-md bg-white/[0.03] border border-white/[0.05] text-[10px] font-bold text-gray-400 mb-2">
              ۱. تصویر الگو (محصول شما)
            </span>
            <p class="text-[11px] text-gray-500 mb-3 leading-relaxed">این تصویر مبنای طراحی هوش مصنوعی است.</p>
          </div>
          
          <div class="flex-1 min-h-0 border border-white/[0.03] bg-[#070708] rounded-2xl p-4 flex items-center justify-center overflow-hidden">
            <img src="{{ $product->cover ? asset('storage/'.$product->cover) : ($product->thumbnail ? asset('storage/'.$product->thumbnail) : asset('assets/img/placeholder.webp')) }}"
                 alt="Product Template" class="max-w-full max-h-full object-contain rounded-xl shadow-lg">
          </div>
          
          {{-- تنظیم نسبت تصویر --}}
          <div class="shrink-0 mt-4 pt-3 border-t border-white/[0.03]">
            <p class="text-[10px] font-bold text-gray-500 mb-2">تنظیم نسبت تصویر خروجی:</p>
            <div class="flex gap-1.5 flex-wrap">
              @foreach(['1:1'=>'مربع','4:5'=>'پرتره','9:16'=>'عمودی','16:9'=>'افقی'] as $val=>$lbl)
              <label class="cursor-pointer">
                <input type="radio" name="modal_ratio" value="{{ $val }}" {{ $val==='1:1'?'checked':'' }} class="sr-only peer">
                <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-[10px] font-bold border border-white/[0.05] bg-white/[0.01] text-gray-400 peer-checked:border-indigo-500/50 peer-checked:bg-indigo-500/10 peer-checked:text-indigo-400 transition-all">
                  {{ $lbl }}
                </span>
              </label>
              @endforeach
            </div>
          </div>
        </div>

        {{-- ستون دوم (وسط): بخش آپلود تصویر کاربر و دکمه ساخت --}}
        <div class="p-5 bg-[#121214] flex flex-col h-full overflow-hidden">
          <div class="flex-1 flex flex-col min-h-0 gap-3">
            <span class="inline-block px-2.5 py-1 rounded-md bg-white/[0.03] border border-white/[0.05] text-[10px] font-bold text-gray-400 self-start">
              ۲. بارگذاری تصویر ورودی
            </span>
            
            <div onclick="document.getElementById('modalFileInp').click()"
                 class="w-full shrink-0 rounded-2xl border border-dashed border-white/[0.08] bg-white/[0.01] py-4 px-5 
                        flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-indigo-500/40 
                        hover:bg-indigo-500/[0.02] transition-all text-center group">
              <div class="w-9 h-9 rounded-xl bg-white/[0.03] border border-white/[0.05] group-hover:bg-indigo-500/10 group-hover:border-indigo-500/20 flex items-center justify-center text-gray-400 group-hover:text-indigo-400 transition-all">
                <i class="fa-solid fa-cloud-arrow-up text-xs"></i>
              </div>
              <div>
                <p class="text-[11px] font-bold text-gray-200">انتخاب تصویر جدید</p>
              </div>
              <input type="file" id="modalFileInp" accept="image/*" class="hidden" onchange="handleModalUpload(this)">
            </div>

            <div class="flex-1 min-h-0 border border-white/[0.03] bg-[#070708] rounded-2xl p-4 flex items-center justify-center relative overflow-hidden">
              <img id="userImagePreview" src="" alt="User Source" class="hidden max-w-full max-h-full object-contain rounded-xl">
              <div id="userImagePlaceholder" class="text-center text-gray-600 flex flex-col items-center gap-2">
                <i class="fa-solid fa-user-astronaut text-lg opacity-40"></i>
                <p class="text-[10px]">تصویر شما هنوز آپلود نشده است</p>
              </div>
            </div>
          </div>

          <div class="shrink-0 mt-4 pt-3 border-t border-white/[0.03] space-y-2">
            <div id="modalFormError" class="hidden p-2.5 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl text-[10px] font-bold flex items-center gap-2">
              <i class="fa-solid fa-circle-exclamation"></i>
              <span id="modalFormErrorTxt"></span>
            </div>

            <button type="button" onclick="triggerGeneration()" id="btnModalSubmit"
                    class="w-full h-12 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-[12px] rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg active:scale-[0.98]">
              <i class="fa-solid fa-bolt text-[11px]"></i> بساز (شروع رندر)
            </button>
          </div>
        </div>

        {{-- ستون سوم (چپ): خروجی رندر شده نهایی هوش مصنوعی --}}
        <div class="p-5 bg-[#0a0a0c] flex flex-col h-full overflow-hidden">
          <div class="shrink-0">
            <span class="inline-block px-2.5 py-1 rounded-md bg-white/[0.03] border border-white/[0.05] text-[10px] font-bold text-gray-400 mb-2">
              ۳. خروجی تصویر نهایی
            </span>
          </div>

          <div class="flex-1 min-h-0 border border-white/[0.04] bg-[#040405] rounded-2xl p-4 flex items-center justify-center relative overflow-hidden">
            <img id="modalOutputImage" src="" alt="AI Output" class="hidden max-w-full max-h-full object-contain rounded-xl shadow-2xl">
            
            <div id="outputPlaceholder" class="text-center text-gray-600 flex flex-col items-center gap-2">
              <i class="fa-solid fa-sparkles text-xl text-gray-700"></i>
              <p class="text-[11px] text-gray-500">پس از کلیک روی دکمه ساخت، نتیجه اینجا نمایش داده می‌شود.</p>
            </div>

            {{-- انیمیشن و اورلی مراحل لودینگ هوش مصنوعی --}}
            <div id="modalProgressOverlay" class="hidden absolute inset-0 bg-[#0a0a0c]/95 backdrop-blur-md flex-col items-center justify-center text-center p-6 z-20 animate-fade-in">
              <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mb-4 shadow-inner">
                <i class="fa-solid fa-wand-magic-sparkles fa-spin text-xl text-indigo-400"></i>
              </div>
              <p id="modalPgTxt" class="text-[13px] font-bold text-white mb-1">در حال شروع فرآیند...</p>
              <p id="modalPgSub" class="text-[10px] text-gray-500 mb-4">سیستم در حال آماده‌سازی خط پردازش است</p>
              <div class="bg-white/5 rounded-full h-1 overflow-hidden w-40 mx-auto">
                <div id="modalPgBar" class="h-full bg-indigo-500 rounded-full transition-all duration-700 ease-out" style="width: 0%"></div>
              </div>
            </div>
          </div>

          <div class="shrink-0 mt-4 pt-3 border-t border-white/[0.03]">
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

</div>

<style>
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.animate-fade-in { animation: fadeIn 0.25s ease-out forwards; }

html, body { 
  overflow: hidden !important; 
  height: 100% !important; 
}
@media (min-width: 640px) {
  body { 
    padding-top: 64px !important;
  }
}
</style>
@endsection

@push('scripts')
<script>
var GEN_URL = '{{ route('app.product.generate', $product->slug) }}';
var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
var _modalTimers = [];
var _modalResultUrl = null;

function openWorkspaceModal() {
  var modal = document.getElementById('workspaceModal');
  var content = document.getElementById('modalContent');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(function() {
    modal.classList.remove('opacity-0');
    content.classList.remove('scale-95');
  }, 20);
}

function closeWorkspaceModal() {
  var modal = document.getElementById('workspaceModal');
  var content = document.getElementById('modalContent');
  modal.classList.add('opacity-0');
  content.classList.add('scale-95');
  setTimeout(function() {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
  }, 300);
}

function handleModalUpload(inp) {
  if (!inp.files || !inp.files[0]) return;
  var r = new FileReader();
  r.onload = function(e) {
    var pv = document.getElementById('userImagePreview');
    var ph = document.getElementById('userImagePlaceholder');
    if(pv) {
      pv.src = e.target.result;
      pv.classList.remove('hidden');
    }
    if(ph) ph.classList.add('hidden');
  };
  r.readAsDataURL(inp.files[0]);
}

function triggerGeneration() {
  var fileInp = document.getElementById('modalFileInp');
  var errorBox = document.getElementById('modalFormError');
  var errorTxt = document.getElementById('modalFormErrorTxt');
  
  if(!fileInp.files || !fileInp.files[0]) {
    errorBox.classList.remove('hidden');
    errorTxt.textContent = 'لطفاً ابتدا تصویر ورودی خود را بارگذاری کنید.';
    return;
  }
  errorBox.classList.add('hidden');

  var fd = new FormData();
  fd.append('_token', CSRF);
  fd.append('uploads[photo]', fileInp.files[0]);

  var ratio = document.querySelector('input[name="modal_ratio"]:checked');
  fd.append('output[aspect_ratio]', ratio ? ratio.value : '1:1');
  fd.append('output[quality]', '1K');

  var overlay = document.getElementById('modalProgressOverlay');
  overlay.classList.remove('hidden');
  overlay.classList.add('flex');
  
  document.getElementById('btnModalSubmit').disabled = true;

  var steps = [
    {t: 'در حال آپلود تصویر...', s: 'ارسال امن اطلاعات به سرور پردازش', p: 20},
    {t: 'تحلیل ساختار الگو...', s: 'هوش مصنوعی در حال همگام‌سازی اجزا است', p: 45},
    {t: 'رندر و اعمال سبک...', s: 'طراحی لایه‌های نهایی تصویر', p: 75},
    {t: 'بهینه‌سازی خروجی...', s: 'شفاف‌سازی و آماده‌سازی جهت نمایش', p: 92},
  ];
  
  var stepIdx = 0;
  function processSteps() {
    if (stepIdx >= steps.length) return;
    var step = steps[stepIdx++];
    document.getElementById('modalPgTxt').textContent = step.t;
    document.getElementById('modalPgSub').textContent = step.s;
    document.getElementById('modalPgBar').style.width = step.p + '%';
    _modalTimers.push(setTimeout(processSteps, 3000));
  }
  processSteps();

  fetch(GEN_URL, {
    method: 'POST', body: fd,
    headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF}
  })
  .then(function(r){
    // ═══ مدیریت هوشمند خطای اتمام توکن (HTTP 402 یا عدم مجاز بودن) ═══
    if (r.status === 402) {
       _modalTimers.forEach(clearTimeout); _modalTimers = [];
       overlay.classList.add('hidden');
       document.getElementById('btnModalSubmit').disabled = false;
       
       // باز کردن پاپ‌آپ سراسری خرید بدون نیاز به رفرش
       if(typeof window.showTokenShortageModal === 'function') {
           window.showTokenShortageModal();
       }
       throw new Error('موجودی اعتبار توکن شما کافی نیست.');
    }
    return r.json();
  })
  .then(function(d){
    _modalTimers.forEach(clearTimeout); _modalTimers = [];
    overlay.classList.remove('flex');
    overlay.classList.add('hidden');
    document.getElementById('btnModalSubmit').disabled = false;

    if (d.success && d.image_url) {
      var outImg = document.getElementById('modalOutputImage');
      var outPh = document.getElementById('outputPlaceholder');
      
      outImg.src = d.image_url;
      outImg.classList.remove('hidden');
      if(outPh) outPh.classList.add('hidden');

      _modalResultUrl = d.image_url;
      document.getElementById('modalDlBtn').disabled = false;
      
      document.getElementById('mainImage').src = d.image_url;
      document.getElementById('successBadge').classList.remove('hidden');

      // ═══ به‌روزرسانی زنده و آنی مقدار توکن در دراپ‌داون پروفایل بدون رفرش ═══
      var tokenEl = document.getElementById('top-nav-tokens');
      if (tokenEl && d.remaining_tokens !== undefined) {
          tokenEl.textContent = Number(d.remaining_tokens).toLocaleString('fa-IR') + ' توکن';
      }
    } else {
      errorBox.classList.remove('hidden');
      errorTxt.textContent = d.message || 'پردازش هوش مصنوعی با خطا مواجه شد.';
    }
  })
  .catch(function(err){
    _modalTimers.forEach(clearTimeout); _modalTimers = [];
    overlay.classList.remove('flex');
    overlay.classList.add('hidden');
    document.getElementById('btnModalSubmit').disabled = false;
    
    if(err.message !== 'موجودی اعتبار توکن شما کافی نیست.') {
        errorBox.classList.remove('hidden');
        errorTxt.textContent = err.message || 'ارتباط با سرور برقرار نشد.';
    }
  });
}

function downloadGeneratedImage(){
  if (!_modalResultUrl) return;
  var a = document.createElement('a');
  a.href = _modalResultUrl; a.download = 'ai-product-result.png'; a.click();
}

function doShare() {
  var t = document.querySelector('h1').textContent.trim();
  if (navigator.share) navigator.share({title:t, url:location.href}).catch(function(){});
  else if (navigator.clipboard) navigator.clipboard.writeText(location.href);
}
document.getElementById('btnShare').addEventListener('click', doShare);

var _saved = false;
document.getElementById('btnBookmark').addEventListener('click', function(){
  _saved = !_saved;
  document.getElementById('iconBkm').className = _saved ? 'fa-solid fa-bookmark text-[11px]' : 'fa-regular fa-bookmark text-[11px]';
  this.classList.toggle('text-emerald-400', _saved);
});
</script>
@endpush