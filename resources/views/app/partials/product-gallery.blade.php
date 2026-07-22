{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: گالری تصاویر محصول — ستون چپ صفحه محصول (دسکتاپ ~۶۰٪)
     - تصویر اصلی بزرگ + کیفیت بالا + نسبت تصویر ثابت + Sticky در دسکتاپ
     - تصویر اول همیشه «عکس اصلی» محصول است
     - سپس نمونه‌خروجی‌های محصول (sample_outputs)
     - تغییر تصویر با کلیک روی Thumbnail بدون Reload صفحه و با Transition نرم
     - اگر محصول فقط یک تصویر داشته باشد، ردیف Thumbnail اصلاً نمایش داده نمی‌شود
     ═══════════════════════════════════════════════════════════════ --}}
@php
  $__beforeSrc = $product->displayImageUrl();
  $__outputs   = is_array($product->sample_outputs) ? array_values($product->sample_outputs) : [];
  $__isVideo   = fn ($p) => $p && preg_match('/\.(mp4|webm|mov)$/i', $p);
  $__totalCount = 1 + count($__outputs);
@endphp

<div class="relative w-full" id="pdpGallery">

  {{-- دکمه ضربدر/برگشت — فقط موبایل، بالا سمت چپ روی عکس --}}
  <button type="button" onclick="history.length>1?history.back():location.href='/app/home'"
    class="lg:hidden absolute top-3 left-3 z-10 w-9 h-9 flex items-center justify-center rounded-full bg-black/40 backdrop-blur-sm text-white transition-colors">
    <i class="fa-solid fa-xmark text-sm"></i>
  </button>

  {{-- تصویر/ویدیو اصلی --}}
  <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-[var(--bg-surface)] flex items-center justify-center">
    <img id="pdpMainImage" src="{{ $__beforeSrc }}" alt="{{ $product->name_fa }}"
         class="w-full h-full object-contain transition-opacity duration-300 ease-out {{ $__isVideo($__beforeSrc) ? 'hidden' : '' }}">
    <video id="pdpMainVideo" src="{{ $__isVideo($__beforeSrc) ? $__beforeSrc : '' }}" muted loop playsinline autoplay
           class="w-full h-full object-contain transition-opacity duration-300 ease-out {{ $__isVideo($__beforeSrc) ? '' : 'hidden' }}"></video>
  </div>

  {{-- ردیف Thumbnailها — فقط وقتی مجموع تصاویر بیشتر از یکی باشد --}}
  @if($__totalCount > 1)
  <div class="mt-3 flex items-start gap-2.5 overflow-x-auto pb-1" style="scrollbar-width:thin;scrollbar-color:var(--border-subtle) transparent" id="pdpThumbRow">

    {{-- تصویر اصلی محصول --}}
    <button type="button" class="pdp-thumb is-active shrink-0 flex flex-col items-center gap-1.5" aria-label="عکس اصلی"
            data-src="{{ $__beforeSrc }}" data-type="{{ $__isVideo($__beforeSrc) ? 'video' : 'image' }}"
            onclick="pdpSelectThumb(this)">
      <span class="pdp-thumb-box block w-16 h-16 sm:w-[72px] sm:h-[72px] rounded-xl overflow-hidden border-2 border-transparent bg-[var(--bg-surface)]">
        <img src="{{ $__beforeSrc }}" alt="عکس اصلی" class="w-full h-full object-cover">
      </span>
      <span class="text-[10px] font-bold text-[var(--text-secondary)]">عکس اصلی</span>
    </button>

    {{-- نمونه تصاویر/ویدیوهای خروجی محصول --}}
    @foreach($__outputs as $__out)
      @php $__outUrl = asset('storage/'.$__out); @endphp
      <button type="button" class="pdp-thumb shrink-0" aria-label="{{ $product->name_fa }}"
              data-src="{{ $__outUrl }}" data-type="{{ $__isVideo($__out) ? 'video' : 'image' }}"
              onclick="pdpSelectThumb(this)">
        <span class="pdp-thumb-box block w-16 h-16 sm:w-[72px] sm:h-[72px] rounded-xl overflow-hidden border-2 border-transparent bg-[var(--bg-surface)]">
          @if($__isVideo($__out))
            <video src="{{ $__outUrl }}" muted loop playsinline autoplay class="w-full h-full object-cover"></video>
          @else
            <img src="{{ $__outUrl }}" alt="{{ $product->name_fa }}" class="w-full h-full object-cover">
          @endif
        </span>
      </button>
    @endforeach

  </div>
  @endif

</div>

<style>
  #pdpGallery .pdp-thumb-box { transition: border-color .2s ease, transform .2s ease; }
  #pdpGallery .pdp-thumb:hover .pdp-thumb-box { transform: translateY(-2px); }
  #pdpGallery .pdp-thumb.is-active .pdp-thumb-box { border-color: var(--green) !important; }
  #pdpGallery #pdpThumbRow::-webkit-scrollbar { height: 4px; }
  #pdpGallery #pdpThumbRow::-webkit-scrollbar-thumb { background: var(--border-subtle); border-radius: 99px; }
</style>

<script>
  function pdpSelectThumb(btn) {
    var row = document.getElementById('pdpThumbRow');
    if (row) row.querySelectorAll('.pdp-thumb').forEach(function (b) { b.classList.remove('is-active'); });
    btn.classList.add('is-active');

    var src  = btn.dataset.src;
    var type = btn.dataset.type;
    var img  = document.getElementById('pdpMainImage');
    var vid  = document.getElementById('pdpMainVideo');
    if (!img || !vid) return;

    img.style.opacity = 0;
    vid.style.opacity = 0;

    setTimeout(function () {
      if (type === 'video') {
        vid.src = src;
        vid.classList.remove('hidden');
        img.classList.add('hidden');
      } else {
        img.src = src;
        img.classList.remove('hidden');
        vid.classList.add('hidden');
        vid.pause();
      }
      requestAnimationFrame(function () {
        img.style.opacity = 1;
        vid.style.opacity = 1;
      });
    }, 150);
  }
</script>
