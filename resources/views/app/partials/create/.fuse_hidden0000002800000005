{{-- پارشیال: هدر محصول --}}
<div class="cp-header">
  <div class="cp-header__inner">
    <div style="display:flex;align-items:center;gap:14px;">
      @if($product->thumbnail)
        <img src="{{ asset('storage/'.$product->thumbnail) }}" alt="{{ $product->name_fa }}"
          class="cp-header__thumb" style="width:64px;height:64px;border-radius:12px;object-fit:cover;flex-shrink:0;">
      @endif
      <div>
        <div class="cp-header__title" style="font-size:16px;font-weight:800;color:#fff;margin-bottom:4px;">{{ $product->name_fa }}</div>
        <div class="cp-header__desc" style="font-size:12px;color:rgba(255,255,255,0.55);line-height:1.6;">{{ $product->description_fa }}</div>
      </div>
    </div>

    @if($product->pricing_model === 'per_credit')
    <div style="margin-top:12px;display:flex;align-items:center;gap:6px;padding:8px 12px;background:rgba(245,146,58,0.08);border:1px solid rgba(245,146,58,0.2);border-radius:10px;">
      <i class="fa-solid fa-bolt" style="color:#f5923a;font-size:12px;"></i>
      <span style="font-size:12px;color:#f5923a;font-weight:700;">{{ $product->credit_cost }} کردیت</span>
      <span style="font-size:11px;color:rgba(255,255,255,0.4);">به ازای هر تصویر</span>
    </div>
    @elseif($product->pricing_model === 'free')
    <div style="margin-top:12px;display:flex;align-items:center;gap:6px;padding:8px 12px;background:rgba(11,191,83,0.08);border:1px solid rgba(11,191,83,0.2);border-radius:10px;">
      <i class="fa-solid fa-check-circle" style="color:#0BBF53;font-size:12px;"></i>
      <span style="font-size:12px;color:#0BBF53;font-weight:700;">رایگان</span>
    </div>
    @endif
  </div>
</div>