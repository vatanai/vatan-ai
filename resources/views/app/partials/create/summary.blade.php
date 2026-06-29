{{--
    PARTIAL: create/summary.blade.php
    بخش ششم — خلاصه سفارش + دکمه اصلی
    متغیرها: $productName, $tokenCost, $estimatedTime
--}}

@php
  $productName   = $product->name          ?? 'عکس حرفه‌ای بیزنس';
  $tokenCost     = $product->token_cost    ?? 4;
  $estimatedTime = $product->estimated_time ?? '۳۰ تا ۶۰ ثانیه';
@endphp

<section class="cp-summary" dir="rtl">
  <div class="cp-summary__inner">

    <div class="cp-section-label">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      خلاصه سفارش
    </div>

    {{-- آیتم‌های خلاصه --}}
    <div class="cp-summary__items">

      <div class="cp-summary__item">
        <span class="cp-summary__item-key">محصول</span>
        <span class="cp-summary__item-val" id="sum-product">{{ $productName }}</span>
      </div>

      <div class="cp-summary__item">
        <span class="cp-summary__item-key">تصاویر آپلود‌شده</span>
        <span class="cp-summary__item-val" id="sum-uploads">
          <span class="cp-summary__badge cp-summary__badge--warn">ناقص</span>
        </span>
      </div>

      <div class="cp-summary__item">
        <span class="cp-summary__item-key">نسبت تصویر</span>
        <span class="cp-summary__item-val" id="sum-ratio">—</span>
      </div>

      <div class="cp-summary__item">
        <span class="cp-summary__item-key">کیفیت</span>
        <span class="cp-summary__item-val" id="sum-quality">—</span>
      </div>

      <div class="cp-summary__divider"></div>

      <div class="cp-summary__item">
        <span class="cp-summary__item-key">زمان تخمینی</span>
        <span class="cp-summary__item-val">{{ $estimatedTime }}</span>
      </div>

      <div class="cp-summary__item">
        <span class="cp-summary__item-key">توکن مورد نیاز</span>
        <span class="cp-summary__item-val cp-summary__item-val--green">{{ $tokenCost }} توکن</span>
      </div>

    </div>

    {{-- CTA اصلی --}}
    <button
      type="submit"
      form="create-form"
      id="cp-submit-btn"
      class="cp-cta"
      aria-label="ساخت تصویر"
    >
      <span id="cp-cta-text">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        ساخت تصویر ({{ $tokenCost }} توکن)
      </span>
      <span id="cp-cta-loading" style="display:none;">
        <span class="cp-cta-spinner"></span>
        در حال پردازش...
      </span>
    </button>

    {{-- خطای فرم --}}
    <div class="cp-summary__form-error" id="form-error" style="display:none;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#f05c5c" stroke-width="1.8"/><path d="M12 8v5M12 15.5v.5" stroke="#f05c5c" stroke-width="1.8" stroke-linecap="round"/></svg>
      <span id="form-error-text">لطفاً همه تصاویر را بارگذاری کنید.</span>
    </div>

  </div>
</section>

<style>
/* ═══════════════════════════════
   CP-SUMMARY
═══════════════════════════════ */
.cp-summary {
  padding: 14px 16px 0;
  max-width: 640px;
  margin: 0 auto;
}

.cp-summary__inner {
  background: var(--bg-card, #3F3F3F);
  border-radius: 16px;
  padding: 16px;
  border: 1px solid rgba(11,191,83,0.12);
}

/* آیتم‌ها */
.cp-summary__items {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 18px;
}

.cp-summary__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.cp-summary__item-key {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255,255,255,0.45);
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-summary__item-key { color: rgba(0,0,0,0.4); }

.cp-summary__item-val {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  font-family: 'YekanBakh', sans-serif;
  text-align: left;
}

html.light .cp-summary__item-val { color: rgba(0,0,0,0.7); }

.cp-summary__item-val--green { color: #0BBF53; }

.cp-summary__badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  font-family: 'YekanBakh', sans-serif;
}

.cp-summary__badge--warn {
  background: rgba(245,146,58,0.12);
  border: 1px solid rgba(245,146,58,0.25);
  color: #f5923a;
}

.cp-summary__badge--ok {
  background: rgba(11,191,83,0.1);
  border: 1px solid rgba(11,191,83,0.22);
  color: #0BBF53;
}

.cp-summary__divider {
  height: 1px;
  background: rgba(255,255,255,0.07);
  margin: 4px 0;
}

html.light .cp-summary__divider { background: rgba(0,0,0,0.06); }

/* CTA */
.cp-cta {
  width: 100%;
  height: 54px;
  border-radius: 14px;
  background: #0BBF53;
  border: none;
  color: #fff;
  font-size: 15px;
  font-weight: 800;
  font-family: 'YekanBakh', sans-serif;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: opacity 0.18s, transform 0.14s;
  -webkit-tap-highlight-color: transparent;
  outline: none;
}

.cp-cta:hover   { opacity: 0.88; }
.cp-cta:active  { transform: scale(0.98); }

.cp-cta:focus-visible {
  box-shadow: 0 0 0 3px rgba(11,191,83,0.4);
}

.cp-cta:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

#cp-cta-text {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cp-cta-spinner {
  width: 18px;
  height: 18px;
  border: 2.5px solid rgba(255,255,255,0.25);
  border-top-color: #fff;
  border-radius: 50%;
  animation: cpSpin 0.7s linear infinite;
  display: inline-block;
}

/* خطای فرم */
.cp-summary__form-error {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 10px;
  font-size: 12px;
  color: #f05c5c;
  font-family: 'YekanBakh', sans-serif;
  justify-content: center;
}

@media (min-width: 640px) {
  .cp-summary { padding: 14px 24px 0; }
  .cp-cta { height: 58px; font-size: 16px; }
}

@media (min-width: 1024px) {
  .cp-summary__inner {
    padding: 18px;
    border-radius: 14px;
    border-color: rgba(11,191,83,0.18);
  }

  .cp-summary__items  { gap: 9px; }
  .cp-summary__item-key { font-size: 11.5px; }
  .cp-summary__item-val { font-size: 11.5px; }

  .cp-cta {
    height: 52px;
    font-size: 14px;
    border-radius: 12px;
    margin-top: 4px;
  }

  .cp-summary__form-error { font-size: 11.5px; }
}
</style>
