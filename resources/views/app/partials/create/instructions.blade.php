{{-- پارشیال: دستورالعمل --}}
<div class="cp-instructions">
  <div class="cp-instructions__inner" style="background:rgba(160,122,245,0.06);border:1px solid rgba(160,122,245,0.18);border-radius:14px;padding:14px 16px;">
    <div style="font-size:12px;font-weight:700;color:#a07af5;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
      <i class="fa-solid fa-circle-info"></i> راهنمای استفاده
    </div>
    <ol style="margin:0;padding-right:18px;font-size:12px;color:rgba(255,255,255,0.65);line-height:2;direction:rtl;">
      @php
        $schema = is_array($product->input_schema) ? $product->input_schema : [];
        $hasUpload = collect($schema)->where('type', 'image_upload')->isNotEmpty();
      @endphp
      @if($hasUpload)
        <li>عکس خود را آپلود کنید — رو به رو باشد (نه نیم‌رخ)</li>
        <li>فیلدهای مربوطه را پر کنید</li>
        <li>دکمه «ساخت تصویر» را بزنید</li>
        <li>چند ثانیه صبر کنید تا هوش مصنوعی عکس را ویرایش کند</li>
      @else
        <li>فیلدهای خواسته‌شده را پر کنید</li>
        <li>دکمه «ساخت تصویر» را بزنید</li>
        <li>چند ثانیه صبر کنید تا تصویر تولید شود</li>
      @endif
    </ol>
  </div>
</div>