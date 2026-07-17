{{-- پارشیال: گام سوم — متغیرها و فیلدهای ورودی کاربر --}}
{{-- جدا شده از step-2.blade.php هنگام تبدیل ویزارد ۳ مرحله‌ای به ۵ مرحله‌ای —
     فقط بازآرایی محل نمایش Cardهاست، هیچ فیلد/نام/منطقی تغییر نکرده و آرایه نام‌ها بر اساس ساختار استاندارد حفظ شده‌اند. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';

  // آیکون «راهنمایی آیتم» — فقط برای فیلدهای واقعاً وصل‌شده به Backend (متن کامل از config/product_field_help.php خوانده می‌شود)
  // نکته مهم: عمداً <span role="button"> است نه <button> واقعی — چون این آیکون گاهی داخل عناصر <label>
  // (از جمله لیبل خودِ سوییچ روشن/خاموش) قرار می‌گیرد؛ <button> چون خودش هم «Labelable» است ممکن بود مرورگر
  // آن را به‌جای چک‌باکس واقعی «کنترل صاحب لیبل» در نظر بگیرد و با کلیک روی خودِ سوییچ (نه آیکون)، به‌جای
  // تغییر وضعیت چک‌باکس، پنجره راهنما باز شود. با <span> این تداخل کاملاً از بین می‌رود.
  $__help = function (string $key, string $title) {
      $text = config('product_field_help.' . $key, '');
      if ($text === '') return '';
      return '<span class="field-help-btn inline-flex items-center justify-center shrink-0 cursor-pointer text-[var(--text3)] hover:text-[var(--accent)] transition-colors" role="button" tabindex="0" data-help-title="' . e($title) . '" data-help-text="' . e($text) . '" aria-label="راهنمایی آیتم"><i class="fa-solid fa-circle-question text-[10px]"></i></span>';
  };
@endphp

{{-- ═══════════════════ Card — متغیرهای پرامپت (NEW / فقط UI — بند ۱۴) ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-brackets-curly text-[var(--accent)]"></i> متغیرهای پرامپت {!! $newBadge !!}</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">متغیرهایی که در متن پرامپت با مقدار واقعی جایگزین می‌شوند</div>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-xs" id="prompt-vars-table">
      <thead>
        <tr class="text-[var(--text3)] text-[10.5px]">
          <th class="p-2 font-semibold text-right">متغیر</th>
          <th class="p-2 font-semibold text-right">توضیح</th>
          <th class="p-2 font-semibold text-right">نوع</th>
          <th class="p-2 font-semibold text-right">پیش‌فرض</th>
          <th class="p-2 font-semibold text-center">اجباری</th>
          <th class="p-2"></th>
        </tr>
      </thead>
      <tbody id="prompt-vars-tbody">
        @foreach([
          ['{name}','نام کاربر','User'],
          ['{gender}','جنسیت','User'],
          ['{style}','سبک انتخابی','User'],
          ['{product_name}','نام محصول','Product'],
          ['{image}','عکس ورودی کاربر','Media'],
          ['{today}','تاریخ امروز','System'],
        ] as $v)
          <tr class="border-t border-[var(--b1)]">
            <td class="p-2"><code class="bg-[var(--b1)] px-1.5 py-0.5 rounded text-[var(--accent)] ltr">{{ $v[0] }}</code></td>
            <td class="p-2 text-[var(--text2)]">{{ $v[1] }}</td>
            <td class="p-2"><span class="text-[10px] bg-[var(--b1)] text-[var(--text2)] rounded px-1.5 py-0.5">{{ $v[2] }}</span></td>
            <td class="p-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1 text-[11px] text-[var(--text)] w-20" placeholder="—"></td>
            <td class="p-2 text-center"><input type="checkbox" class="accent-[var(--accent)]"></td>
            <td class="p-2"></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-3" onclick="addPromptVarRow()">
    <i class="fa-solid fa-plus"></i> افزودن متغیر جدید
  </button>
</div>

<script>
/* افزودن ردیف متغیر جدید به جدول متغیرهای پرامپت (بند ۱۴) — فقط UI */
function addPromptVarRow() {
  var tb = document.getElementById('prompt-vars-tbody');
  if (!tb) return;
  var tr = document.createElement('tr');
  tr.className = 'border-t border-[var(--b1)]';
  tr.innerHTML =
    '<td class="p-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1 text-[11px] text-[var(--text)] ltr text-left w-24" placeholder="{variable}"></td>' +
    '<td class="p-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1 text-[11px] text-[var(--text)] w-full" placeholder="توضیح"></td>' +
    '<td class="p-2"><select class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1 text-[11px] text-[var(--text)]"><option>User</option><option>Product</option><option>Media</option><option>System</option></select></td>' +
    '<td class="p-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1 text-[11px] text-[var(--text)] w-20" placeholder="—"></td>' +
    '<td class="p-2 text-center"><input type="checkbox" class="accent-[var(--accent)]"></td>' +
    '<td class="p-2"><button type="button" class="text-[var(--red)]" onclick="this.closest(\'tr\').remove()"><i class="fa-solid fa-xmark"></i></button></td>';
  tb.appendChild(tr);
}
</script>

{{-- ═══════════════════ Card — ویژگی‌های خاص محصول (NEW / فقط UI — بند ۴۸) ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-swatchbook text-[var(--accent)]"></i> ویژگی‌های خاص محصول {!! $newBadge !!}</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">مثلاً «سبک تصویر» — برای هر ویژگی نوع نمایش و گزینه‌هایش را مشخص کنید</div>
    </div>
  </div>

  <div id="product-attributes-list" class="space-y-3"></div>

  <div id="product-attributes-empty" class="text-[11px] text-[var(--text3)] text-center py-3 border-2 border-dashed border-[var(--b2)] rounded-xl">
    هنوز ویژگی‌ای اضافه نشده است.
  </div>

  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-3" onclick="addProductAttribute()">
    <i class="fa-solid fa-plus"></i> افزودن ویژگی جدید
  </button>
</div>

{{-- ═══════════════════ Card ۲ — فیلدهای ورودی کاربر ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-table-list text-[var(--accent)]"></i> فیلدهای ورودی کاربر {!! $__help('input_schema', 'فیلدهای ورودی کاربر') !!}</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">این فیلدها هنگام استفاده از محصول از کاربر گرفته می‌شوند</div>
  </div>
  
  <div id="input-fields-list" class="space-y-2">
    @foreach ((old('input_schema', optional($duplicateFrom)->input_schema ?? [])) as $index => $field)
      <div class="input-field-card bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 input-schema-row" draggable="true">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center">
          <div class="flex items-center gap-1.5 md:col-span-1">
            <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 hidden md:block" title="برای تغییر ترتیب بکشید"></i>
            <div class="flex md:hidden flex-col gap-0.5 shrink-0">
              <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
              <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
            </div>
            <input type="text" name="input_schema[{{ $index }}][field_id]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] ltr text-left schema-id w-full" placeholder="field_id" value="{{ $field['field_id'] ?? '' }}">
          </div>
          <input type="text" name="input_schema[{{ $index }}][label_fa]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-label" placeholder="برچسب فارسی" value="{{ $field['label_fa'] ?? '' }}">
          <select name="input_schema[{{ $index }}][type]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-type">
            @foreach (['text','textarea','number','image_upload','file_upload','select','radio','checkbox','switch','color'] as $t)
              <option value="{{ $t }}" {{ ($field['type'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
          </select>
          <select name="input_schema[{{ $index }}][required]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-required">
            <option value="1" {{ (string) ($field['required'] ?? '1') === '1' ? 'selected' : '' }}>اجباری</option>
            <option value="0" {{ (string) ($field['required'] ?? '1') === '0' ? 'selected' : '' }}>اختیاری</option>
          </select>
          <div class="flex items-center gap-1.5 justify-end">
            <button type="button" class="text-xs text-[var(--text2)] bg-[var(--text)]/5 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').querySelector('.field-advanced').classList.toggle('hidden')" title="ویرایش تنظیمات پیشرفته"><i class="fa-solid fa-pen"></i></button>
            <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').remove(); refreshFormPreview();">حذف</button>
          </div>
        </div>
        <div class="field-advanced hidden grid grid-cols-1 md:grid-cols-3 gap-2.5 mt-2.5 pt-2.5 border-t border-dashed border-[var(--b2)]">
          <input type="text" name="input_schema[{{ $index }}][placeholder]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-placeholder" placeholder="Placeholder (اختیاری)" value="{{ $field['placeholder'] ?? '' }}">
          <input type="text" name="input_schema[{{ $index }}][help_text]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-help" placeholder="متن راهنما (اختیاری)" value="{{ $field['help_text'] ?? '' }}">
          <div class="flex items-center gap-1.5">
            <input type="text" name="input_schema[{{ $index }}][min]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-min" placeholder="حداقل" value="{{ $field['min'] ?? '' }}">
            <input type="text" name="input_schema[{{ $index }}][max]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-max" placeholder="حداکثر" value="{{ $field['max'] ?? '' }}">
            <input type="text" name="input_schema[{{ $index }}][regex]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 ltr text-left schema-regex" placeholder="Regex" value="{{ $field['regex'] ?? '' }}">
          </div>
          <input type="text" name="input_schema[{{ $index }}][options]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] md:col-span-3 schema-options" placeholder="گزینه‌ها برای select/radio/checkbox — با کاما جدا کنید (مثلاً: مشکی,سفید,قرمز)" value="{{ $field['options'] ?? '' }}">
        </div>
      </div>
    @endforeach
  </div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-3" onclick="addInputField()">
    <i class="fa-solid fa-plus"></i> افزودن فیلد ورودی جدید
  </button>

  <div class="mt-4 pt-4 border-t border-dashed border-[var(--b2)]">
    <div class="text-[10.5px] font-bold text-[var(--orange)] mb-2 tracking-wide uppercase flex items-center gap-1.5"><i class="fa-solid fa-eye text-[10px]"></i> پیش‌نمایش فرم نهایی کاربر</div>
    <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3.5" id="user-form-preview"></div>
  </div>
</div>

<script>
/* ══════ مدیریت فیلدهای ورودی (Input Schema Rows) ══════ */
function updateSchemaInputNames() {
  document.querySelectorAll('#input-fields-list .input-schema-row').forEach((row, index) => {
    row.querySelector('.schema-id').setAttribute('name', `input_schema[${index}][field_id]`);
    row.querySelector('.schema-label').setAttribute('name', `input_schema[${index}][label_fa]`);
    row.querySelector('.schema-type').setAttribute('name', `input_schema[${index}][type]`);
    row.querySelector('.schema-required').setAttribute('name', `input_schema[${index}][required]`);
    
    // فیلدهای پیشرفته
    row.querySelector('.schema-placeholder')?.setAttribute('name', `input_schema[${index}][placeholder]`);
    row.querySelector('.schema-help')?.setAttribute('name', `input_schema[${index}][help_text]`);
    row.querySelector('.schema-min')?.setAttribute('name', `input_schema[${index}][min]`);
    row.querySelector('.schema-max')?.setAttribute('name', `input_schema[${index}][max]`);
    row.querySelector('.schema-regex')?.setAttribute('name', `input_schema[${index}][regex]`);
    row.querySelector('.schema-options')?.setAttribute('name', `input_schema[${index}][options]`);
  });
}

function refreshFormPreview() {
  const preview = document.getElementById('user-form-preview');
  if(!preview) return;
  preview.innerHTML = '';
  
  const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
  if(rows.length === 0) {
    preview.innerHTML = '<div class="text-[11px] text-[var(--text3)] text-center py-2">هیچ فیلدی تعریف نشده است. فرم کاربر خالی خواهد بود.</div>';
    return;
  }
  
  rows.forEach(row => {
    const id = row.querySelector('.schema-id').value.trim() || 'field';
    const label = row.querySelector('.schema-label').value.trim() || 'فیلد بدون نام';
    const type = row.querySelector('.schema-type').value;
    const req = row.querySelector('.schema-required').value === '1';
    const optionsRaw = row.querySelector('.schema-options')?.value.trim() || '';
    const opts = optionsRaw.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    
    const fDiv = document.createElement('div');
    fDiv.className = 'mb-3 flex flex-col gap-1.5 last:mb-0';
    
    let inputHtml = '';
    if(type === 'textarea') {
      inputHtml = `<textarea class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-xs w-full min-h-[60px]" disabled placeholder="ورودی کاربر..."></textarea>`;
    } else if(type === 'select') {
      const optHtml = opts.length ? opts.map(function (o) { return `<option>${o}</option>`; }).join('') : '<option>گزینه‌های انتخابی...</option>';
      inputHtml = `<select class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-xs w-full" disabled>${optHtml}</select>`;
    } else if(type === 'image_upload' || type === 'file_upload') {
      inputHtml = `<div class="border border-dashed border-[var(--b2)] rounded-lg p-3 text-center text-[11px] text-[var(--text3)] bg-[var(--bg)]"><i class="fa-solid fa-cloud-arrow-up ml-1"></i> آپلود فایل توسط کاربر</div>`;
    } else if(type === 'checkbox' || type === 'radio') {
      const list = opts.length ? opts : ['گزینه نمونه'];
      inputHtml = '<div class="flex flex-wrap gap-3 py-1">' + list.map(function (o) { return `<label class="flex items-center gap-1.5"><input type="${type}" disabled class="accent-[var(--accent)]"><span class="text-xs text-[var(--text2)]">${o}</span></label>`; }).join('') + '</div>';
    } else if(type === 'switch') {
      inputHtml = `<label class="flex items-center gap-2 py-1"><input type="checkbox" disabled class="accent-[var(--accent)] w-9 h-5"><span class="text-xs text-[var(--text2)]">روشن/خاموش</span></label>`;
    } else if(type === 'color') {
      inputHtml = `<input type="color" class="w-12 h-8 rounded-lg border border-[var(--b1)] bg-[var(--bg)]" disabled value="#16594f">`;
    } else {
      inputHtml = `<input type="${type}" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-xs w-full" disabled placeholder="ورودی کاربر...">`;
    }
    
    fDiv.innerHTML = `
      <label class="text-xs font-semibold text-[var(--text2)]">${label} ${req ? '<span class="text-[var(--red)]">*</span>' : ''}</label>
      ${inputHtml}
    `;
    preview.appendChild(fDiv);
  });
}

function wireInputFieldDrag(row) {
  row.addEventListener('dragstart', (e) => {
    row.classList.add('opacity-50');
    window.draggedFieldRow = row;
  });
  row.addEventListener('dragend', () => {
    row.classList.remove('opacity-50');
    updateSchemaInputNames();
  });
  row.addEventListener('dragover', (e) => { e.preventDefault(); });
  row.addEventListener('drop', (e) => {
    e.preventDefault();
    if (window.draggedFieldRow && window.draggedFieldRow !== row) {
      const list = document.getElementById('input-fields-list');
      const all = Array.from(list.children);
      if (all.indexOf(window.draggedFieldRow) < all.indexOf(row)) {
        row.after(window.draggedFieldRow);
      } else {
        row.before(window.draggedFieldRow);
      }
      refreshFormPreview();
    }
  });
}

function moveInputFieldRow(btn, dir) {
  const row = btn.closest('.input-field-card');
  if (!row) return;
  if (dir === 'up' && row.previousElementSibling) {
    row.previousElementSibling.before(row);
  } else if (dir === 'down' && row.nextElementSibling) {
    row.nextElementSibling.after(row);
  }
  updateSchemaInputNames();
  refreshFormPreview();
}

function addInputField() {
  const list = document.getElementById('input-fields-list');
  const index = list.children.length;
  
  const div = document.createElement('div');
  div.className = 'input-field-card bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 input-schema-row';
  div.draggable = true;
  div.innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center">
      <div class="flex items-center gap-1.5 md:col-span-1">
        <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 hidden md:block" title="برای تغییر ترتیب بکشید"></i>
        <div class="flex md:hidden flex-col gap-0.5 shrink-0">
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
        </div>
        <input type="text" name="input_schema[${index}][field_id]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] ltr text-left schema-id w-full" placeholder="field_id">
      </div>
      <input type="text" name="input_schema[${index}][label_fa]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-label" placeholder="برچسب فارسی">
      <select name="input_schema[${index}][type]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-type">
        <option value="text">text</option>
        <option value="textarea">textarea</option>
        <option value="number">number</option>
        <option value="image_upload">image_upload</option>
        <option value="file_upload">file_upload</option>
        <option value="select">select</option>
        <option value="radio">radio</option>
        <option value="checkbox">checkbox</option>
        <option value="switch">switch</option>
        <option value="color">color</option>
      </select>
      <select name="input_schema[${index}][required]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-required">
        <option value="1">اجباری</option>
        <option value="0">اختیاری</option>
      </select>
      <div class="flex items-center gap-1.5 justify-end">
        <button type="button" class="text-xs text-[var(--text2)] bg-[var(--text)]/5 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').querySelector('.field-advanced').classList.toggle('hidden')"><i class="fa-solid fa-pen"></i></button>
        <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').remove(); updateSchemaInputNames(); refreshFormPreview();">حذف</button>
      </div>
    </div>
    <div class="field-advanced hidden grid grid-cols-1 md:grid-cols-3 gap-2.5 mt-2.5 pt-2.5 border-t border-dashed border-[var(--b2)]">
      <input type="text" name="input_schema[${index}][placeholder]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-placeholder" placeholder="Placeholder (اختیاری)">
      <input type="text" name="input_schema[${index}][help_text]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-help" placeholder="متن راهنما (اختیاری)">
      <div class="flex items-center gap-1.5">
        <input type="text" name="input_schema[${index}][min]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-min" placeholder="حداقل">
        <input type="text" name="input_schema[${index}][max]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-max" placeholder="حداکثر">
        <input type="text" name="input_schema[${index}][regex]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 ltr text-left schema-regex" placeholder="Regex">
      </div>
      <input type="text" name="input_schema[${index}][options]" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] md:col-span-3 schema-options" placeholder="گزینه‌ها برای select/radio/checkbox — با کاما جدا کنید (مثلاً: مشکی,سفید,قرمز)">
    </div>
  `;
  
  list.appendChild(div);
  wireInputFieldDrag(div);
  
  div.querySelectorAll('.schema-id, .schema-label, .schema-type, .schema-required, .schema-options').forEach(el => {
    el.addEventListener('input', refreshFormPreview);
    el.addEventListener('change', refreshFormPreview);
  });
  
  refreshFormPreview();
}



document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#input-fields-list .input-schema-row').forEach(row => {
    wireInputFieldDrag(row);
    row.querySelectorAll('.schema-id, .schema-label, .schema-required, .schema-type, .schema-options').forEach(el => {
      el.addEventListener('input', refreshFormPreview);
      el.addEventListener('change', refreshFormPreview);
    });
  });
  refreshFormPreview();
});
</script>

<script>
/* ══════ ویژگی‌های خاص محصول (بند ۴۸) — کاملاً UI-only، بدون اتصال Backend ══════ */
var __attrIdx = 0;
var ATTR_TYPES = [
  ['single',  'گزینه‌ای / تک‌انتخابی (Radio یا Dropdown)'],
  ['multi',   'چندگزینه‌ای / چندانتخابی (Checkbox)'],
  ['toggle',  'تیک‌زدنی / روشن‌خاموش (Toggle)'],
  ['variant', 'چند مدل مختلف (Variant Models)'],
];
function syncAttrEmptyState() {
  var list = document.getElementById('product-attributes-list');
  var empty = document.getElementById('product-attributes-empty');
  if (list && empty) empty.classList.toggle('hidden', list.children.length > 0);
}
function addProductAttribute() {
  var list = document.getElementById('product-attributes-list');
  if (!list) return;
  var i = __attrIdx++;
  var typeOpts = ATTR_TYPES.map(function (t) { return '<option value="' + t[0] + '">' + t[1] + '</option>'; }).join('');
  var row = document.createElement('div');
  row.className = 'attr-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3';
  row.innerHTML =
    '<div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 mb-2.5">' +
      '<input type="text" name="new_product_attributes[' + i + '][name]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)]" placeholder="نام ویژگی (مثلاً سبک تصویر)">' +
      '<select name="new_product_attributes[' + i + '][type]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] attr-type" onchange="onAttrTypeChange(this)">' + typeOpts + '</select>' +
    '</div>' +
    '<div class="attr-options-block">' +
      '<label class="text-[10.5px] text-[var(--text3)] mb-1 block">گزینه‌ها <span class="text-[var(--text3)]">— بنویسید و Enter بزنید</span></label>' +
      '<div class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-1.5 flex flex-wrap gap-1.5 items-center min-h-[42px] attr-chips" onclick="this.querySelector(\'.attr-chip-input\').focus()">' +
        '<input type="text" class="bg-transparent border-none outline-none text-xs text-[var(--text)] flex-1 min-w-[80px] attr-chip-input" placeholder="گزینه..." onkeydown="attrAddChip(event)">' +
      '</div>' +
    '</div>' +
    '<div class="flex justify-end mt-2">' +
      '<button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="this.closest(\'.attr-row\').remove(); syncAttrEmptyState();">حذف ویژگی</button>' +
    '</div>';
  list.appendChild(row);
  onAttrTypeChange(row.querySelector('.attr-type'));
  syncAttrEmptyState();
}
function onAttrTypeChange(sel) {
  if (!sel) return;
  var row = sel.closest('.attr-row');
  if (!row) return;
  var optBlock = row.querySelector('.attr-options-block');
  if (optBlock) optBlock.classList.toggle('hidden', sel.value === 'toggle'); // نوع Toggle گزینه ندارد
}
function attrAddChip(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  var inp = e.target;
  var v = inp.value.trim();
  if (!v) return;
  var chip = document.createElement('span');
  chip.className = 'inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]';
  chip.innerHTML = v + '<button type="button" class="text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1" onclick="this.parentElement.remove()">×</button>';
  inp.parentElement.insertBefore(chip, inp);
  inp.value = '';
}
</script>