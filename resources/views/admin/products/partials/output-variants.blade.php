{{-- پارشیال: «مدل‌های خروجی چندگانه» (Output Variants) — بخشی از ویژگی‌های پیشرفته محصول (گام سوم) --}}
{{-- بعضی محصولات چند مدل/سبک خروجی مختلف دارند (مثلاً چند فضاسازی متفاوت برای شیشه عطر).
     ادمین اینجا برای هر مدل: عنوان، عکس پیش‌نمایش و پرامپت اختصاصی تعریف می‌کند.
     کاربر در صفحه ساخت، مدل‌های دلخواه را تیک می‌زند و دقیقاً همان‌ها ساخته می‌شوند. --}}

@php
  $__ovExisting = old('output_variants', optional($duplicateFrom)->output_variants ?? []);
  if (!is_array($__ovExisting)) $__ovExisting = [];
@endphp

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap">
        <i class="fa-solid fa-layer-group text-[var(--accent)]"></i> مدل‌های خروجی چندگانه (انتخاب کاربر)
      </div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">
        اگر این محصول چند مدل/سبک خروجی متفاوت دارد (مثلاً چند فضاسازی مختلف)، اینجا تعریف کنید —
        کاربر در صفحه ساخت انتخاب می‌کند کدام مدل‌ها ساخته شوند و هزینه توکن هر مدل جداگانه حساب می‌شود.
        اگر خالی بماند، محصول مثل قبل فقط یک خروجی می‌دهد.
      </div>
    </div>
  </div>

  <div id="output-variants-list" class="space-y-3">
    @foreach ($__ovExisting as $i => $v)
      @php if (!is_array($v) || trim((string)($v['title'] ?? '')) === '') continue; @endphp
      <div class="ov-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3">
        <input type="hidden" name="output_variants[{{ $i }}][key]" value="{{ $v['key'] ?? '' }}">
        <input type="hidden" name="output_variants[{{ $i }}][image]" class="ov-image-path" value="{{ $v['image'] ?? '' }}">
        <div class="grid grid-cols-1 md:grid-cols-[96px_1fr] gap-3">
          <div class="flex flex-col items-center gap-1.5">
            <div class="ov-thumb w-[96px] h-[96px] rounded-lg border border-[var(--b1)] bg-[var(--bg)] overflow-hidden flex items-center justify-center text-[var(--text3)] cursor-pointer" onclick="this.closest('.ov-row').querySelector('.ov-file').click()" title="تغییر عکس پیش‌نمایش">
              @if(!empty($v['image']))
                <img src="{{ asset('storage/'.$v['image']) }}" class="w-full h-full object-cover ov-thumb-img" alt="">
              @else
                <i class="fa-regular fa-image ov-thumb-icon"></i>
              @endif
            </div>
            <input type="file" name="output_variants[{{ $i }}][image_file]" accept=".jpg,.jpeg,.png,.webp" class="hidden ov-file" onchange="ovPreviewImage(this)">
            <span class="text-[9.5px] text-[var(--text3)]">عکس پیش‌نمایش</span>
          </div>
          <div class="flex flex-col gap-2">
            <input type="text" name="output_variants[{{ $i }}][title]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] w-full" placeholder="عنوان مدل (مثلاً فضاسازی ساحلی)" value="{{ $v['title'] ?? '' }}">
            <textarea name="output_variants[{{ $i }}][prompt]" rows="2" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] w-full resize-y ltr text-left" placeholder="Prompt اختصاصی این مدل (اختیاری) — به انتهای پرامپت اصلی اضافه می‌شود">{{ $v['prompt'] ?? '' }}</textarea>
            <div class="flex justify-end">
              <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.ov-row').remove(); ovSyncEmptyState();">حذف مدل</button>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div id="output-variants-empty" class="text-[11px] text-[var(--text3)] text-center py-3 border-2 border-dashed border-[var(--b2)] rounded-xl {{ count($__ovExisting) ? 'hidden' : '' }}">
    هنوز مدل خروجی‌ای تعریف نشده — این محصول یک خروجی معمولی خواهد داشت.
  </div>

  <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border-2 border-dashed border-[var(--b2)] bg-transparent text-[var(--text3)] text-xs font-semibold mt-3" onclick="ovAddVariant()">
    <i class="fa-solid fa-plus"></i> افزودن مدل خروجی جدید
  </button>
</div>

<script>
/* ══════ مدل‌های خروجی چندگانه (Output Variants) — متصل به Backend ══════ */
var __ovIdx = {{ count($__ovExisting) }};

function ovSyncEmptyState() {
  var list = document.getElementById('output-variants-list');
  var empty = document.getElementById('output-variants-empty');
  if (list && empty) empty.classList.toggle('hidden', list.children.length > 0);
}

function ovPreviewImage(inp) {
  if (!inp.files || !inp.files[0]) return;
  var thumb = inp.closest('.ov-row').querySelector('.ov-thumb');
  var reader = new FileReader();
  reader.onload = function (e) {
    thumb.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover ov-thumb-img" alt="">';
  };
  reader.readAsDataURL(inp.files[0]);
}

function ovAddVariant() {
  var list = document.getElementById('output-variants-list');
  if (!list) return;
  var i = __ovIdx++;
  var row = document.createElement('div');
  row.className = 'ov-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3';
  row.innerHTML =
    '<input type="hidden" name="output_variants[' + i + '][key]" value="">' +
    '<input type="hidden" name="output_variants[' + i + '][image]" class="ov-image-path" value="">' +
    '<div class="grid grid-cols-1 md:grid-cols-[96px_1fr] gap-3">' +
      '<div class="flex flex-col items-center gap-1.5">' +
        '<div class="ov-thumb w-[96px] h-[96px] rounded-lg border border-[var(--b1)] bg-[var(--bg)] overflow-hidden flex items-center justify-center text-[var(--text3)] cursor-pointer" onclick="this.closest(\'.ov-row\').querySelector(\'.ov-file\').click()" title="انتخاب عکس پیش‌نمایش">' +
          '<i class="fa-regular fa-image ov-thumb-icon"></i>' +
        '</div>' +
        '<input type="file" name="output_variants[' + i + '][image_file]" accept=".jpg,.jpeg,.png,.webp" class="hidden ov-file" onchange="ovPreviewImage(this)">' +
        '<span class="text-[9.5px] text-[var(--text3)]">عکس پیش‌نمایش</span>' +
      '</div>' +
      '<div class="flex flex-col gap-2">' +
        '<input type="text" name="output_variants[' + i + '][title]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] w-full" placeholder="عنوان مدل (مثلاً فضاسازی ساحلی)">' +
        '<textarea name="output_variants[' + i + '][prompt]" rows="2" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] w-full resize-y ltr text-left" placeholder="Prompt اختصاصی این مدل (اختیاری) — به انتهای پرامپت اصلی اضافه می‌شود"></textarea>' +
        '<div class="flex justify-end">' +
          '<button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="this.closest(\'.ov-row\').remove(); ovSyncEmptyState();">حذف مدل</button>' +
        '</div>' +
      '</div>' +
    '</div>';
  list.appendChild(row);
  ovSyncEmptyState();
}
</script>
