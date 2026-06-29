{{--
    PARTIAL: create/header.blade.php
    بخش اول — هدر محصول
    متغیرها (از کنترلر یا demo):
      $product->name, $product->description, $product->image,
      $product->estimated_time, $product->token_cost, $product->upload_count
--}}

@php
  $productName      = $product->name        ?? 'عکس حرفه‌ای بیزنس';
  $productDesc      = $product->description ?? 'یک عکس حرفه‌ای از تو می‌سازیم — مناسب لینکدین، رزومه و پروفایل‌های کاری';
  $productImage     = $product->image        ?? null;
  $estimatedTime    = $product->estimated_time ?? '۳۰ تا ۶۰ ثانیه';
  $tokenCost        = $product->token_cost   ?? 4;
  $uploadCount      = $product->upload_count ?? 2;
@endphp

<section class="cp-header" dir="rtl">
  <div class="cp-header__inner">

    {{-- تصویر محصول --}}
    <div class="cp-header__thumb">
      @if($productImage)
        <img src="{{ Storage::url($productImage) }}" alt="{{ $productName }}" class="cp-header__img">
      @else
        <div class="cp-header__img-placeholder">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="3" width="18" height="18" rx="4" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
            <path d="M3 15L8 10L12 14L16 11L21 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      @endif
    </div>

    {{-- اطلاعات محصول --}}
    <div class="cp-header__info">
      <h1 class="cp-header__title">{{ $productName }}</h1>
      <p class="cp-header__desc">{{ $productDesc }}</p>

      {{-- بج‌های اطلاعاتی --}}
      <div class="cp-header__badges">

        <div class="cp-badge">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
            <path d="M12 7V12L15 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span>{{ $estimatedTime }}</span>
        </div>

        <div class="cp-badge cp-badge--green">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>{{ $tokenCost }} توکن</span>
        </div>

        <div class="cp-badge">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.8"/>
            <path d="M3 10H21" stroke="currentColor" stroke-width="1.8"/>
            <circle cx="7.5" cy="14.5" r="1" fill="currentColor"/>
          </svg>
          <span>{{ $uploadCount }} تصویر نیاز است</span>
        </div>

      </div>
    </div>

  </div>
</section>

<style>
/* ═══════════════════════════════
   CP-HEADER
═══════════════════════════════ */
.cp-header {
  padding: 20px 16px 0;
  max-width: 640px;
  margin: 0 auto;
}

.cp-header__inner {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  background: var(--bg-card, #3F3F3F);
  border-radius: 16px;
  padding: 16px;
  position: relative;
  overflow: hidden;
}

/* درخشش پس‌زمینه */
.cp-header__inner::before {
  content: '';
  position: absolute;
  top: -40px;
  right: -40px;
  width: 140px;
  height: 140px;
  background: radial-gradient(circle, rgba(160,122,245,0.12) 0%, transparent 70%);
  pointer-events: none;
}

/* تصویر محصول */
.cp-header__thumb {
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255,255,255,0.06);
}

.cp-header__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.cp-header__img-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.25);
}

html.light .cp-header__img-placeholder {
  color: rgba(0,0,0,0.2);
}

/* متن */
.cp-header__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cp-header__title {
  font-size: 17px;
  font-weight: 700;
  color: var(--text, #fff);
  line-height: 1.4;
  font-family: 'YekanBakh', sans-serif;
}

.cp-header__desc {
  font-size: 12px;
  font-weight: 400;
  color: rgba(255,255,255,0.5);
  line-height: 1.7;
  font-family: 'YekanBakh', sans-serif;
}

html.light .cp-header__desc {
  color: rgba(0,0,0,0.45);
}

/* بج‌ها */
.cp-header__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 4px;
}

.cp-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  padding: 4px 9px;
  font-size: 11px;
  font-weight: 500;
  color: rgba(255,255,255,0.65);
  font-family: 'YekanBakh', sans-serif;
  white-space: nowrap;
}

.cp-badge svg {
  flex-shrink: 0;
}

.cp-badge--green {
  background: rgba(11,191,83,0.1);
  border-color: rgba(11,191,83,0.25);
  color: #0BBF53;
}

html.light .cp-badge {
  background: rgba(0,0,0,0.05);
  border-color: rgba(0,0,0,0.08);
  color: rgba(0,0,0,0.55);
}

html.light .cp-badge--green {
  background: rgba(11,191,83,0.08);
  border-color: rgba(11,191,83,0.2);
  color: #09a446;
}

/* تبلت */
@media (min-width: 640px) {
  .cp-header {
    padding: 28px 24px 0;
  }
  .cp-header__thumb {
    width: 96px;
    height: 96px;
  }
  .cp-header__title { font-size: 19px; }
  .cp-header__desc  { font-size: 13px; }
}

/* دسکتاپ — overrides از create.blade.php کنترل می‌کنه padding/max-width رو */
@media (min-width: 1024px) {
  .cp-header__inner {
    padding: 20px;
    border-radius: 14px;
    gap: 14px;
  }
  /* تصویر بزرگتر در sidebar */
  .cp-header__thumb {
    width: 76px;
    height: 76px;
    border-radius: 12px;
  }
  .cp-header__title {
    font-size: 16px;
    font-weight: 800;
  }
  .cp-header__desc {
    font-size: 12px;
    line-height: 1.65;
  }
  .cp-header__badges { gap: 5px; }
  .cp-badge { font-size: 10.5px; padding: 3px 8px; }
}
</style>
