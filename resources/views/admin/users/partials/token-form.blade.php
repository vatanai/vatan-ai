{{-- پارشیال مدیریت اعتبار: فرم عملیات (افزودن / کسر / تنظیم مستقیم) + میانبرهای ۱ / ۵ / ۱۰ / ۲۰ / ۵۰ --}}

<div class="content-card">
  <div class="tk-card-header"><i class="fa-solid fa-coins" style="color:var(--warning);"></i> عملیات اعتبار</div>
  <div class="tk-card-body">

    <div class="tk-form-group">
      <label class="tk-label" for="tkAction">نوع عملیات</label>
      <select class="input-pro" id="tkAction" onchange="tkUpdatePreview();tkUpdateExpiryMode()">
        <option value="add">➕ افزودن اعتبار</option>
        <option value="deduct">➖ کسر اعتبار</option>
        <option value="set">🎯 تنظیم مستقیم موجودی</option>
      </select>
    </div>

    <div class="tk-form-group" id="tkCreditKindGroup">
      <label class="tk-label" for="tkCreditKind">منبع اعتبار</label>
      <select class="input-pro" id="tkCreditKind" onchange="tkUpdateExpiryMode()">
        <option value="gift">اعتبار هدیه</option>
        <option value="plan_upgrade">هدیه ارتقای پلن</option>
        <option value="paid_adjustment">اصلاح اعتبار خریداری‌شده</option>
      </select>
    </div>

    <div class="tk-form-group" style="margin-bottom:10px;">
      <label class="tk-label" for="tkAmount">مقدار اعتبار</label>
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

    <div class="tk-form-group" id="tkExpiryGroup">
      <label class="tk-label" for="tkExpiryAt">مهلت استفاده از اعتبار هدیه (اختیاری)</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <select class="input-pro" id="tkExpiryUnit" onchange="tkUpdateExpiryMode()">
          <option value="none">بدون انقضا</option>
          <option value="days">تعداد روز</option>
          <option value="weeks">تعداد هفته</option>
          <option value="months">تعداد ماه</option>
          <option value="date">تاریخ مشخص</option>
        </select>
        <input type="number" class="input-pro" id="tkExpiryValue" min="1" step="1" placeholder="مثال: ۷" style="display:none;">
        <input type="date" class="input-pro" id="tkExpiryDate" style="display:none;">
      </div>
      <div style="font-size:10px;color:var(--text-soft);margin-top:5px;">اعتبار پس از پایان این مهلت خودکار از موجودی قابل‌استفاده حذف می‌شود.</div>
    </div>

    <label style="display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--text-main);margin:4px 0 14px;cursor:pointer;">
      <input type="checkbox" id="tkSendSms" style="accent-color:var(--primary);"> ارسال پیامک تغییر اعتبار به کاربر
    </label>

    <div class="tk-form-group">
      <label class="tk-label" for="tkNote">توضیحات (اختیاری)</label>
      <input type="text" class="input-pro" id="tkNote" placeholder="دلیل تغییر اعتبار..." maxlength="255">
    </div>

    <button type="button" class="btn-pro btn-pro-primary tk-submit" id="tkSubmitBtn" onclick="tkSubmit()" disabled>
      <i class="fa-solid fa-bolt-lightning"></i> اعمال تغییر اعتبار
    </button>

  </div>
</div>
