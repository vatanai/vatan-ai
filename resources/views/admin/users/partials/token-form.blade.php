{{-- پارشیال مدیریت توکن: فرم عملیات (افزودن / کسر / تنظیم مستقیم) + میانبرهای ۱ / ۵ / ۱۰ / ۲۰ / ۵۰ --}}

<div class="content-card">
  <div class="tk-card-header"><i class="fa-solid fa-coins" style="color:var(--warning);"></i> عملیات توکن</div>
  <div class="tk-card-body">

    <div class="tk-form-group">
      <label class="tk-label" for="tkAction">نوع عملیات</label>
      <select class="input-pro" id="tkAction" onchange="tkUpdatePreview()">
        <option value="add">➕ افزودن توکن</option>
        <option value="deduct">➖ کسر توکن</option>
        <option value="set">🎯 تنظیم مستقیم موجودی</option>
      </select>
    </div>

    <div class="tk-form-group" style="margin-bottom:10px;">
      <label class="tk-label" for="tkAmount">مقدار توکن</label>
      <input type="number" class="input-pro" id="tkAmount" placeholder="مثال: ۱۰" min="0" step="1" inputmode="numeric" oninput="tkUpdatePreview()">
    </div>

    {{-- میانبرهای سریع: با هر کلیک به مقدار بالا اضافه می‌شود و نوع عملیات هم خودکار ست می‌شود --}}
    <div class="tk-quick-box">
      <div class="tk-quick-title"><i class="fa-solid fa-plus" style="color:var(--success);"></i> افزودن سریع</div>
      <div class="tk-quick-row">
        <button type="button" class="tk-chip tk-chip-add" onclick="tkQuick('add',1)">+۱</button>
        <button type="button" class="tk-chip tk-chip-add" onclick="tkQuick('add',5)">+۵</button>
        <button type="button" class="tk-chip tk-chip-add" onclick="tkQuick('add',10)">+۱۰</button>
        <button type="button" class="tk-chip tk-chip-add" onclick="tkQuick('add',20)">+۲۰</button>
        <button type="button" class="tk-chip tk-chip-add" onclick="tkQuick('add',50)">+۵۰</button>
      </div>
      <div class="tk-quick-title" style="margin-top:10px;"><i class="fa-solid fa-minus" style="color:var(--danger);"></i> کسر سریع</div>
      <div class="tk-quick-row">
        <button type="button" class="tk-chip tk-chip-deduct" onclick="tkQuick('deduct',1)">−۱</button>
        <button type="button" class="tk-chip tk-chip-deduct" onclick="tkQuick('deduct',5)">−۵</button>
        <button type="button" class="tk-chip tk-chip-deduct" onclick="tkQuick('deduct',10)">−۱۰</button>
        <button type="button" class="tk-chip tk-chip-deduct" onclick="tkQuick('deduct',20)">−۲۰</button>
        <button type="button" class="tk-chip tk-chip-deduct" onclick="tkQuick('deduct',50)">−۵۰</button>
      </div>
    </div>

    <div class="tk-preview" id="tkPreview"></div>

    <div class="tk-form-group">
      <label class="tk-label" for="tkNote">توضیحات (اختیاری)</label>
      <input type="text" class="input-pro" id="tkNote" placeholder="دلیل تغییر توکن..." maxlength="255">
    </div>

    <button type="button" class="btn-pro btn-pro-primary tk-submit" id="tkSubmitBtn" onclick="tkSubmit()" disabled>
      <i class="fa-solid fa-bolt-lightning"></i> اعمال تغییر توکن
    </button>

  </div>
</div>
