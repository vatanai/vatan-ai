{{-- پارشیال: خلاصه + دکمه ساخت --}}
<div class="cp-summary">
  <div class="cp-summary__inner" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:16px;">

    <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:14px;border-bottom:1px solid rgba(255,255,255,0.08);padding-bottom:12px;">
      خلاصه سفارش
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:12px;color:rgba(255,255,255,0.45);">تصاویر</span>
        <span id="sum-uploads" style="font-size:12px;color:rgba(255,255,255,0.7);">
          <span class="cp-summary__badge cp-summary__badge--warn">ناقص</span>
        </span>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:12px;color:rgba(255,255,255,0.45);">نسبت تصویر</span>
        <span id="sum-ratio" style="font-size:12px;color:rgba(255,255,255,0.7);">۱:۱</span>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:12px;color:rgba(255,255,255,0.45);">کیفیت</span>
        <span id="sum-quality" style="font-size:12px;color:rgba(255,255,255,0.7);">1K</span>
      </div>
      @if($product->pricing_model === 'per_credit')
      <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid rgba(255,255,255,0.08);">
        <span style="font-size:12px;color:rgba(255,255,255,0.45);">هزینه</span>
        <span style="font-size:13px;font-weight:700;color:#f5923a;">{{ $product->credit_cost }} کردیت</span>
      </div>
      @endif
    </div>

    <button type="submit" id="cp-submit-btn" class="cp-cta"
      style="width:100%;height:52px;background:#0BBF53;border:none;border-radius:14px;font-size:15px;font-weight:800;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;font-family:inherit;transition:opacity 0.18s,transform 0.14s;">
      <span id="cp-cta-text" style="display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        ساخت تصویر
      </span>
      <span id="cp-cta-loading" style="display:none;align-items:center;gap:8px;">
        <i class="fa-solid fa-spinner fa-spin"></i>
        در حال پردازش...
      </span>
    </button>

    <div style="font-size:11px;color:rgba(255,255,255,0.25);text-align:center;margin-top:10px;">
      معمولاً ۱۰ تا ۳۰ ثانیه طول می‌کشد
    </div>

  </div>
</div>

<style>
.cp-summary__badge--warn { color:#f5923a; font-size:12px; }
.cp-summary__badge--ok   { color:#0BBF53; font-size:12px; }
#cp-submit-btn:active { transform:scale(0.97); opacity:0.9; }
#cp-submit-btn:disabled { opacity:0.6; cursor:not-allowed; }
</style>