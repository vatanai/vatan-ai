{{-- پارشیال: گام سوم — متغیرها و فیلدهای ورودی کاربر --}}
{{-- جدا شده از step-2.blade.php هنگام تبدیل ویزارد ۳ مرحله‌ای به ۵ مرحله‌ای —
     فقط بازآرایی محل نمایش Cardهاست، هیچ فیلد/نام/منطقی تغییر نکرده و آرایه نام‌ها بر اساس ساختار استاندارد حفظ شده‌اند. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

{{-- ═══════════════════ Card ۲ — فیلدهای ورودی کاربر ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-table-list text-[var(--accent)]"></i> فیلدهای ورودی کاربر</div>
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
            @foreach (['text','textarea','number','image_upload','file_upload','select','radio','checkbox'] as $t)
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
    
    const fDiv = document.createElement('div');
    fDiv.className = 'mb-3 flex flex-col gap-1.5 last:mb-0';
    
    let inputHtml = '';
    if(type === 'textarea') {
      inputHtml = `<textarea class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-xs w-full min-h-[60px]" disabled placeholder="ورودی کاربر..."></textarea>`;
    } else if(type === 'select') {
      inputHtml = `<select class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-xs w-full" disabled><option>گزینه‌های انتخابی...</option></select>`;
    } else if(type === 'image_upload' || type === 'file_upload') {
      inputHtml = `<div class="border border-dashed border-[var(--b2)] rounded-lg p-3 text-center text-[11px] text-[var(--text3)] bg-[var(--bg)]"><i class="fa-solid fa-cloud-arrow-up ml-1"></i> آپلود فایل توسط کاربر</div>`;
    } else if(type === 'checkbox' || type === 'radio') {
      inputHtml = `<div class="flex items-center gap-2 py-1"><input type="${type}" disabled class="accent-[var(--accent)]"><span class="text-xs text-[var(--text2)]">گزینه نمونه</span></div>`;
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
    </div>
  `;
  
  list.appendChild(div);
  wireInputFieldDrag(div);
  
  div.querySelectorAll('.schema-id, .schema-label, .schema-type, .schema-required').forEach(el => {
    el.addEventListener('input', refreshFormPreview);
    el.addEventListener('change', refreshFormPreview);
  });
  
  refreshFormPreview();
}



document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#input-fields-list .input-schema-row').forEach(row => {
    wireInputFieldDrag(row);
    row.querySelectorAll('.schema-id, .schema-label, .schema-required, .schema-type').forEach(el => {
      el.addEventListener('input', refreshFormPreview);
      el.addEventListener('change', refreshFormPreview);
    });
  });
  refreshFormPreview();
});
</script>