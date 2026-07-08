{{-- پارشیال: گام سوم — متغیرها و فیلدهای ورودی کاربر --}}
{{-- جدا شده از step-2.blade.php هنگام تبدیل ویزارد ۳ مرحله‌ای به ۵ مرحله‌ای (طبق درخواست کاربر) —
     فقط بازآرایی محل نمایش Cardهاست، هیچ فیلد/نام/منطقی تغییر نکرده. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

{{-- ═══════════════════ Card ۱ — متغیرهای پرامپت ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-code-branch text-[var(--accent)]"></i> متغیرهای پرامپت</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">فهرست متغیرهایی که در پرامپت قابل استفاده‌اند (فقط راهنما — بدون اتصال Backend)</div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-xs" id="variables-table">
      <thead>
        <tr class="text-[10.5px] text-[var(--text3)] border-b border-[var(--b1)]">
          <th class="text-right py-2 font-semibold">Variable</th>
          <th class="text-right py-2 font-semibold">Description</th>
          <th class="text-right py-2 font-semibold">Type</th>
          <th class="text-right py-2 font-semibold">NEW Default Value</th>
          <th class="text-right py-2 font-semibold">NEW Required</th>
        </tr>
      </thead>
      <tbody id="variables-table-body">
        <tr class="border-b border-[var(--b1)]/60">
          <td class="py-2 font-mono text-[var(--accent)] ltr text-left">{name}</td>
          <td class="py-2 text-[var(--text2)]">نام کاربر</td>
          <td class="py-2 text-[var(--text3)]">Text</td>
          <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] w-full" placeholder="—"></td>
          <td class="py-2"><label class="relative w-8 h-[18px] block cursor-pointer"><input type="checkbox" checked class="sr-only peer"><span class="absolute inset-0 bg-[var(--b2)] rounded-full peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span></label></td>
        </tr>
        <tr class="border-b border-[var(--b1)]/60">
          <td class="py-2 font-mono text-[var(--accent)] ltr text-left">{gender}</td>
          <td class="py-2 text-[var(--text2)]">جنسیت کاربر</td>
          <td class="py-2 text-[var(--text3)]">Text</td>
          <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] w-full" placeholder="—"></td>
          <td class="py-2"><label class="relative w-8 h-[18px] block cursor-pointer"><input type="checkbox" class="sr-only peer"><span class="absolute inset-0 bg-[var(--b2)] rounded-full peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span></label></td>
        </tr>
        <tr class="border-b border-[var(--b1)]/60">
          <td class="py-2 font-mono text-[var(--accent)] ltr text-left">{style}</td>
          <td class="py-2 text-[var(--text2)]">سبک انتخابی محصول</td>
          <td class="py-2 text-[var(--text3)]">Text</td>
          <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] w-full" placeholder="—"></td>
          <td class="py-2"><label class="relative w-8 h-[18px] block cursor-pointer"><input type="checkbox" class="sr-only peer"><span class="absolute inset-0 bg-[var(--b2)] rounded-full peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span></label></td>
        </tr>
      </tbody>
    </table>
  </div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-3" onclick="addVariableRow()">
    <i class="fa-solid fa-plus"></i> NEW افزودن متغیر جدید {!! $newBadge !!}
  </button>
</div>

{{-- ═══════════════════ Card ۲ — فیلدهای ورودی کاربر ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-table-list text-[var(--accent)]"></i> فیلدهای ورودی کاربر</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">این فیلدها هنگام استفاده از محصول از کاربر گرفته می‌شوند</div>
  </div>
  <div id="input-fields-list" class="space-y-2">
    @foreach ((old('input_schema', optional($duplicateFrom)->input_schema ?? [])) as $field)
      <div class="input-field-card bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 input-schema-row" draggable="true">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center">
          <div class="flex items-center gap-1.5 md:col-span-1">
            <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 hidden md:block" title="برای تغییر ترتیب بکشید"></i>
            <div class="flex md:hidden flex-col gap-0.5 shrink-0">
              <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
              <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
            </div>
            <input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] ltr text-left schema-id w-full" placeholder="field_id" value="{{ $field['field_id'] ?? '' }}">
          </div>
          <input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-label" placeholder="برچسب فارسی" value="{{ $field['label_fa'] ?? '' }}">
          <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-type" data-searchable>
            @foreach (['text','textarea','number','image_upload','file_upload','select','radio','checkbox'] as $t)
              <option value="{{ $t }}" {{ ($field['type'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
          </select>
          <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-required">
            <option value="1" {{ (string) ($field['required'] ?? '1') === '1' ? 'selected' : '' }}>اجباری</option>
            <option value="0" {{ (string) ($field['required'] ?? '1') === '0' ? 'selected' : '' }}>اختیاری</option>
          </select>
          <div class="flex items-center gap-1.5 justify-end">
            <button type="button" class="text-xs text-[var(--text2)] bg-[var(--text)]/5 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').querySelector('.field-advanced').classList.toggle('hidden')" title="ویرایش تنظیمات پیشرفته"><i class="fa-solid fa-pen"></i></button>
            <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').remove(); refreshFormPreview();">حذف</button>
          </div>
        </div>
        <div class="field-advanced hidden grid grid-cols-1 md:grid-cols-3 gap-2.5 mt-2.5 pt-2.5 border-t border-dashed border-[var(--b2)]">
          <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-placeholder" placeholder="NEW Placeholder — برنامه‌نویسی شود">
          <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-help" placeholder="NEW Help Text — برنامه‌نویسی شود">
          <div class="flex items-center gap-1.5">
            <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-min" placeholder="حداقل">
            <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-max" placeholder="حداکثر">
            <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 ltr text-left schema-regex" placeholder="Regex">
          </div>
        </div>
      </div>
    @endforeach
  </div>
  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-3" onclick="addInputField()">
    <i class="fa-solid fa-plus"></i> افزودن فیلد ورودی جدید
  </button>

  <div class="mt-4 pt-4 border-t border-dashed border-[var(--b2)]">
    <div class="text-[10.5px] font-bold text-[var(--orange)] mb-2 tracking-wide uppercase flex items-center gap-1.5"><i class="fa-solid fa-flask text-[10px]"></i> NEW پیش‌نمایش فرم نهایی کاربر {!! $newBadge !!}</div>
    <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3.5" id="user-form-preview"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#input-fields-list .input-schema-row').forEach(row => {
    wireInputFieldDrag(row);
    row.querySelectorAll('.schema-id, .schema-label, .schema-required').forEach(el => el.addEventListener('input', refreshFormPreview));
    const reqSel = row.querySelector('.schema-required');
    if (reqSel) reqSel.addEventListener('change', refreshFormPreview);
  });
  refreshFormPreview();
});

/* ══════ افزودن ردیف محلی به جدول متغیرها (فقط UI) ══════ */
function addVariableRow() {
  const tbody = document.getElementById('variables-table-body');
  const tr = document.createElement('tr');
  tr.className = 'border-b border-[var(--b1)]/60';
  tr.innerHTML = `
    <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] ltr text-left w-full" placeholder="{variable}"></td>
    <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] w-full" placeholder="توضیح"></td>
    <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] w-full" placeholder="Text"></td>
    <td class="py-2"><input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded p-1.5 text-[11px] text-[var(--text)] w-full" placeholder="—"></td>
    <td class="py-2"><label class="relative w-8 h-[18px] block cursor-pointer"><input type="checkbox" class="sr-only peer"><span class="absolute inset-0 bg-[var(--b2)] rounded-full peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span></label></td>
  `;
  tbody.appendChild(tr);
}
</script>
