@extends('layouts.app')

@section('content')
<div class="cp-page" dir="rtl">

  {{-- padding بالا برای notch موبایل --}}
  <div style="padding-top: calc(env(safe-area-inset-top, 0px) + 16px);"></div>

  {{-- فرم اصلی --}}
  <form id="create-form" novalidate>
    @csrf

    {{-- بخش ۱: هدر محصول --}}
    @include('app.partials.create.header')

    {{-- بخش ۲: دستورالعمل --}}
    @include('app.partials.create.instructions')

    {{-- بخش ۳: آپلود --}}
    @include('app.partials.create.upload')

    {{-- بخش ۴: فیلدهای داینامیک --}}
    @include('app.partials.create.options')

    {{-- بخش ۵: تنظیمات خروجی --}}
    @include('app.partials.create.output-settings')

    {{-- بخش ۶: خلاصه + CTA --}}
    @include('app.partials.create.summary')

  </form>

  {{-- فاصله پایین برای bottom nav --}}
  <div style="height: calc(env(safe-area-inset-bottom, 0px) + 120px);"></div>

  {{-- بخش ۷: مودال نتیجه --}}
  @include('app.partials.create.result-modal')

</div>

<style>
/* متغیرهای رنگ صفحه */
:root {
  --bg: #000000;
  --text: #ffffff;
  --bg-card: #1e1e2a;
}
html.light {
  --bg: #f0f0f5;
  --text: #000000;
  --bg-card: #f5f5f7;
}

.cp-page {
  font-family: 'YekanBakh', sans-serif;
  min-height: 100dvh;
  background: var(--bg);
  direction: rtl;
}
</style>

@push('scripts')
<script>
(function () {
  'use strict';

  /* ── تشخیص کلید فعال nav ── */
  var activeKey = 'create';
  var topLinks  = document.querySelectorAll('.topnav-link, .topnav-create');
  topLinks.forEach(function(l) {
    if (l.dataset.key === activeKey) l.classList.add('is-active');
  });

  /* ── به‌روزرسانی خلاصه ── */
  function cpUpdateSummary() {
    /* نسبت تصویر */
    var ratioEl = document.querySelector('input[name="output[aspect_ratio]"]:checked');
    var sumRatio = document.getElementById('sum-ratio');
    if (sumRatio) sumRatio.textContent = ratioEl ? ratioEl.value : '—';

    /* کیفیت */
    var qualityEl = document.querySelector('input[name="output[quality]"]:checked');
    var sumQuality = document.getElementById('sum-quality');
    if (sumQuality) sumQuality.textContent = qualityEl ? qualityEl.value : '—';

    /* تصاویر */
    updateUploadSummary();
  }

  function updateUploadSummary() {
    var cards   = document.querySelectorAll('.cp-upload-card');
    var total   = cards.length;
    var filled  = document.querySelectorAll('.cp-upload-card.is-filled').length;
    var el      = document.getElementById('sum-uploads');
    if (!el) return;

    if (filled === 0) {
      el.innerHTML = '<span class="cp-summary__badge cp-summary__badge--warn">ناقص</span>';
    } else if (filled < total) {
      el.innerHTML = '<span class="cp-summary__badge cp-summary__badge--warn">' + filled + ' از ' + total + '</span>';
    } else {
      el.innerHTML = '<span class="cp-summary__badge cp-summary__badge--ok">تکمیل ✓</span>';
    }
  }

  /* گوش دادن به تغییر رادیوها و آپلود */
  document.addEventListener('change', cpUpdateSummary);
  document.addEventListener('cp:upload-changed', updateUploadSummary);
  cpUpdateSummary();

  /* ── Validation & Submit ── */
  var form      = document.getElementById('create-form');
  var submitBtn = document.getElementById('cp-submit-btn');
  var ctaText   = document.getElementById('cp-cta-text');
  var ctaLoad   = document.getElementById('cp-cta-loading');
  var formErr   = document.getElementById('form-error');
  var formErrTx = document.getElementById('form-error-text');

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!cpValidate()) return;
      cpSubmit();
    });
  }

  function cpValidate() {
    /* همه slot‌های ضروری پُر باشند */
    var cards  = document.querySelectorAll('.cp-upload-card');
    var filled = document.querySelectorAll('.cp-upload-card.is-filled').length;
    if (filled < cards.length) {
      cpShowFormError('لطفاً همه تصاویر را بارگذاری کنید.');
      return false;
    }

    /* فیلدهای required */
    var invalid = false;
    form.querySelectorAll('[required]').forEach(function(f) {
      var err = document.getElementById('error-' + (f.closest('.cp-field') ? f.closest('.cp-field').dataset.key : ''));
      if (!f.value.trim()) {
        if (err) err.style.display = 'block';
        invalid = true;
      } else {
        if (err) err.style.display = 'none';
      }
    });

    if (invalid) {
      cpShowFormError('لطفاً همه فیلدهای ضروری را پر کنید.');
      return false;
    }

    cpHideFormError();
    return true;
  }

  function cpSubmit() {
    /* نمایش loading روی دکمه */
    submitBtn.disabled    = true;
    ctaText.style.display = 'none';
    ctaLoad.style.display = 'flex';

    /* باز کردن مودال و شروع progress */
    cpModalOpen();
    cpSetStep(0);

    /* ساخت FormData */
    var fd = new FormData(form);

    /* آپلود فایل‌ها */
    document.querySelectorAll('.cp-upload-card.is-filled').forEach(function(card) {
      var key   = card.dataset.slot;
      var input = document.getElementById('file-' + key);
      if (input && input.files[0]) fd.append('uploads[' + key + ']', input.files[0]);
    });

    /* ─ polling مرحله‌ای (شبیه‌سازی) ─
       وقتی بک‌اند آماده شد، endpoint واقعی جایگزین می‌شود */
    var steps = [
      { index: 0, delay: 200  },
      { index: 1, delay: 1800 },
      { index: 2, delay: 3500 },
      { index: 3, delay: 6000 },
    ];

    steps.forEach(function(s) {
      setTimeout(function() { cpSetStep(s.index); }, s.delay);
    });

    /* ─ ارسال به بک‌اند (آماده برای اتصال) ─ */
    var endpoint = form.dataset.endpoint || null;

    /* خواندن CSRF از hidden input فرم */
    var csrfToken = (form.querySelector('input[name="_token"]') || {}).value || '';

    if (endpoint) {
      fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          cpResetBtn();
          if (data.success && data.image_url) {
            setTimeout(function() {
              cpShowResult(data.image_url, data.tokens_left);
            }, 500);
          } else {
            cpShowError(data.message || 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.');
          }
        })
        .catch(function() {
          cpResetBtn();
          cpShowError('ارتباط با سرور قطع شد. لطفاً اینترنت خود را بررسی کنید.');
        });
    } else {
      /* حالت demo — بدون بک‌اند */
      setTimeout(function() {
        cpResetBtn();
        cpShowResult('https://picsum.photos/seed/vatan/600/600', 12);
      }, 7500);
    }
  }

  function cpResetBtn() {
    submitBtn.disabled    = false;
    ctaText.style.display = 'flex';
    ctaLoad.style.display = 'none';
  }

  function cpShowFormError(msg) {
    if (formErr && formErrTx) {
      formErrTx.textContent = msg;
      formErr.style.display = 'flex';
      formErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function cpHideFormError() {
    if (formErr) formErr.style.display = 'none';
  }

}());
</script>
@endpush
@endsection
