@extends('layouts.app')

@section('content')
<div class="cp-page" dir="rtl">

  <div class="cp-page__top-pad"></div>

  <form id="create-form" novalidate>
    @csrf

    <div class="cp-layout">

      {{-- ۱: هدر محصول — موبایل: اول / دسکتاپ: ستون راست ردیف ۱ --}}
      <div class="cpl-header">
        @include('app.partials.create.header')
      </div>

      {{-- ۲: دستورالعمل — موبایل: دوم / دسکتاپ: ستون راست ردیف ۲ --}}
      <div class="cpl-instr">
        @include('app.partials.create.instructions')
      </div>

      {{-- ۳: آپلود — موبایل: سوم / دسکتاپ: ستون چپ ردیف ۱ --}}
      <div class="cpl-upload">
        @include('app.partials.create.upload')
      </div>

      {{-- ۴: فیلدهای داینامیک — موبایل: چهارم / دسکتاپ: ستون چپ ردیف ۲ --}}
      <div class="cpl-options">
        @include('app.partials.create.options')
      </div>

      {{-- ۵: تنظیمات خروجی — موبایل: پنجم / دسکتاپ: ستون چپ ردیف ۳ --}}
      <div class="cpl-output">
        @include('app.partials.create.output-settings')
      </div>

      {{-- ۶: خلاصه + CTA — موبایل: آخر / دسکتاپ: ستون راست sticky --}}
      <div class="cpl-summary">
        @include('app.partials.create.summary')
      </div>

    </div>

  </form>

  <div class="cp-page__bottom-pad"></div>

  @include('app.partials.create.result-modal')

</div>

<style>
/* ════════════════════════════════════════════
   CSS VARIABLES
════════════════════════════════════════════ */
:root {
  --bg:      #000000;
  --text:    #ffffff;
  --bg-card: #1e1e2a;
  --cp-sidebar-w: 360px;
  --cp-gap:       28px;
  --cp-outer-pad: 40px;
}
html.light {
  --bg:      #f0f0f5;
  --text:    #000000;
  --bg-card: #f5f5f7;
}

/* ════════════════════════════════════════════
   PAGE SHELL
════════════════════════════════════════════ */
.cp-page {
  font-family: 'YekanBakh', sans-serif;
  min-height: 100dvh;
  background: var(--bg);
  direction: rtl;
}

.cp-page__top-pad {
  padding-top: calc(env(safe-area-inset-top, 0px) + 16px);
}

.cp-page__bottom-pad {
  height: calc(env(safe-area-inset-bottom, 0px) + 120px);
}

/* ════════════════════════════════════════════
   MOBILE LAYOUT — تک ستون، ترتیب طبیعی HTML
════════════════════════════════════════════ */
.cp-layout {
  display: flex;
  flex-direction: column;
}

/* ════════════════════════════════════════════
   TABLET (640–1023px) — تک ستون، عریض‌تر
════════════════════════════════════════════ */
@media (min-width: 640px) {
  .cp-page__top-pad { padding-top: 24px; }
  .cp-page__bottom-pad { height: 80px; }

  .cp-header,
  .cp-instructions,
  .cp-upload,
  .cp-options,
  .cp-output,
  .cp-summary {
    padding-left: 24px;
    padding-right: 24px;
    max-width: 680px;
  }
}

/* ════════════════════════════════════════════
   DESKTOP (1024px+) — دو ستون CSS Grid
   ستون چپ:  upload / options / output
   ستون راست (sticky): header / instructions / summary
════════════════════════════════════════════ */
@media (min-width: 1024px) {

  /* ── Page top/bottom ── */
  .cp-page__top-pad    { padding-top: 32px; }
  .cp-page__bottom-pad { height: 60px; }

  /* ── Grid container ── */
  .cp-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) var(--cp-sidebar-w);
    grid-template-rows: auto auto auto;
    column-gap: var(--cp-gap);
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 var(--cp-outer-pad) 48px;
  }

  /* ── ستون چپ: محتوای اصلی ── */
  .cpl-upload  { grid-column: 1; grid-row: 1; }
  .cpl-options { grid-column: 1; grid-row: 2; }
  .cpl-output  { grid-column: 1; grid-row: 3; }

  /* ── ستون راست: sidebar ── */
  .cpl-header  { grid-column: 2; grid-row: 1; align-self: start; }
  .cpl-instr   { grid-column: 2; grid-row: 2; align-self: start; }
  .cpl-summary {
    grid-column: 2;
    grid-row: 3;
    position: sticky;
    top: 88px;        /* زیر top nav (64px) + فاصله */
    align-self: start;
  }

  /* ── حذف max-width/margin درونی partial‌ها ── */
  .cp-header,
  .cp-instructions,
  .cp-upload,
  .cp-options,
  .cp-output,
  .cp-summary {
    max-width: none;
    margin-left:  0;
    margin-right: 0;
    padding-left:  0;
    padding-right: 0;
    padding-top:   0;
    padding-bottom: 20px;
  }

  /* ── ارتقای کارت‌ها روی دسکتاپ ── */
  .cp-header__inner {
    padding: 20px;
  }

  .cp-header__thumb {
    width: 88px;
    height: 88px;
  }

  .cp-header__title { font-size: 18px; }
  .cp-header__desc  { font-size: 13px; }

  .cp-instructions__inner { padding: 18px; }

  .cp-upload__inner   { padding: 18px; }
  .cp-options__inner  { padding: 18px; }
  .cp-output__inner   { padding: 18px; }
  .cp-summary__inner  { padding: 18px; }

  /* ── آپلود: grid سه‌ستونه روی دسکتاپ ── */
  .cp-upload__grid--2 { grid-template-columns: 1fr 1fr; }
  .cp-upload__grid--3 { grid-template-columns: 1fr 1fr 1fr; }
  .cp-upload__grid--4 { grid-template-columns: 1fr 1fr; }

  .cp-upload-card {
    aspect-ratio: 3 / 4;
    max-width: 220px;
  }

  /* ── فیلدها: دوستونه روی دسکتاپ ── */
  .cp-options__fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 24px;
  }

  /* فیلدهای عریض */
  .cp-field:has(.cp-input--textarea),
  .cp-field:has(.cp-radio-group),
  .cp-field:has(.cp-slider-wrap) {
    grid-column: 1 / -1;
  }

  /* ── CTA بزرگ‌تر ── */
  .cp-cta { height: 56px; font-size: 15px; border-radius: 14px; }

  /* ── خط جداکننده بصری بین دو ستون ── */
  .cp-layout::before {
    content: '';
    grid-column: 1 / 2;
    grid-row: 1 / 4;
    display: none; /* اگر خواستی separator اضافه کنی فعال کن */
  }
}

/* ════════════════════════════════════════════
   LARGE DESKTOP (1440px+)
════════════════════════════════════════════ */
@media (min-width: 1440px) {
  :root {
    --cp-sidebar-w: 380px;
    --cp-outer-pad: 60px;
    --cp-gap:       32px;
  }

  .cp-upload-card { aspect-ratio: 3 / 4; max-width: 240px; }
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
