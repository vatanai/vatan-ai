{{-- پارشیال: گام سوم — متغیرها و فیلدهای ورودی کاربر --}}
{{-- جدا شده از step-2.blade.php هنگام تبدیل ویزارد ۳ مرحله‌ای به ۵ مرحله‌ای —
     فقط بازآرایی محل نمایش Cardهاست، هیچ فیلد/نام/منطقی تغییر نکرده و آرایه نام‌ها بر اساس ساختار استاندارد حفظ شده‌اند. --}}
{{-- بازطراحی: دو کارت قبلی «ویژگی‌های خاص محصول» (UI-only) و «فیلدهای ورودی کاربر»
     با پارشیال استاندارد جدید admin.products.partials.schema-builder جایگزین شدند.
     کارت «متغیرهای پرامپت» (بند ۱۴ — فقط UI) دست‌نخورده باقی مانده است. --}}

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

{{-- ═══════════════════ سازنده «ویژگی‌های خاص محصول» (Schema Builder) ═══════════════════
     پارشیال استاندارد جدید — جایگزین دو کارت قبلی این گام. تمام تنظیمات هر ویژگی
     (عنوان، توضیح، Placeholder، پیش‌فرض، اجباری/اختیاری، مخفی، ترتیب، شرط نمایش،
     اعتبارسنجی، کردیت و تاثیر در پرامپت) در همین سازنده مدیریت و در ستون
     input_schema ذخیره می‌شود. --}}
@include('admin.products.partials.schema-builder', ['product' => $product ?? null, 'duplicateFrom' => $duplicateFrom ?? null])
