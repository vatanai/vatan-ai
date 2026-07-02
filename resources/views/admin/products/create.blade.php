@extends('layouts.admin')
@section('title', 'ثبت محصول جدید — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">

  <div class="flex-1 flex flex-col min-h-screen mr-0 md:mr-64">

    <header class="sticky top-0 z-50 bg-[#111116] border-b border-[#222230] px-6 h-14 flex items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[#a8c4a8]">
        <a href="/admin/dashboard" class="text-[#a8c4a8] hover:text-white transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
        <span class="text-[#4d7a56] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
        <a href="/admin/products" class="text-[#a8c4a8] hover:text-white transition-colors">محصولات</a>
        <span class="text-[#4d7a56] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="text-white font-semibold">ثبت محصول جدید</span>
      </div>
      <a href="/admin/products" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[#16161c] text-[#a8c4a8] border border-[#222230] transition-all hover:border-[#2e2e3e] hover:text-white no-underline">
        <i class="fa-solid fa-arrow-right text-[11px]"></i>
        بازگشت به لیست
      </a>
    </header>

    <main class="p-6 flex-1 pb-24">

      @if ($errors->any())
        <div class="bg-[#f05c5c]/10 border border-[#f05c5c] rounded-xl p-4 mb-6 text-right">
            <div class="text-[#f05c5c] font-bold text-sm mb-2.5">
                <i class="fa-solid fa-triangle-exclamation"></i> اصلاح خطاهای زیر برای ثبت محصول الزامی است:
            </div>
            <ul class="text-[#ff9191] text-xs pr-5 margin-0 list-disc space-y-1.5 leading-relaxed">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      @if ($aiModels->isEmpty())
        <div class="bg-[#f0a05c]/10 border border-[#f0a05c] rounded-xl p-4 mb-6 text-right text-xs text-[#f0a05c]">
            <i class="fa-solid fa-circle-exclamation"></i>
            هیچ مدل هوش مصنوعی فعالی در سیستم ثبت نشده است. ابتدا از
            <a href="{{ route('admin.ai-models.create') }}" class="underline font-bold">این صفحه</a>
            حداقل یک مدل اضافه کنید.
        </div>
      @endif

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">ثبت محصول جدید</div>
        <div class="text-xs text-[#4d7a56]">محصول را در ۳ مرحله تنظیم کنید — هویت، هوش مصنوعی، و خروجی</div>
      </div>

      <div class="flex items-center gap-0 mb-7 bg-[#16161c] border border-[#222230] rounded-xl p-1.5">
        <div class="flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent bg-[#a07af5]/10 border-[#a07af5]/20" id="step-tab-1" onclick="goStep(1)">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[#a07af5] bg-[#a07af5]/15 text-[#a07af5] transition-all" id="step-num-1">۱</div>
          <div class="flex-1">
            <div class="step-label text-[11px] text-[#a07af5] mb-0.5">گام اول</div>
            <div class="step-title text-xs font-bold text-white">هویت و رسانه</div>
          </div>
        </div>
        <div class="w-6 shrink-0 h-px bg-[#222230]"></div>
        <div class="flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent" id="step-tab-2" onclick="goStep(2)">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[#2e2e3e] text-[#4d7a56] transition-all" id="step-num-2">۲</div>
          <div class="flex-1">
            <div class="step-label text-[11px] text-[#4d7a56] mb-0.5">گام دوم</div>
            <div class="step-title text-xs font-bold text-[#a8c4a8]">هوش مصنوعی</div>
          </div>
        </div>
        <div class="w-6 shrink-0 h-px bg-[#222230]"></div>
        <div class="flex-1 flex items-center gap-2.5 p-2.5 rounded-lg cursor-pointer transition-all border border-transparent" id="step-tab-3" onclick="goStep(3)">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[#2e2e3e] text-[#4d7a56] transition-all" id="step-num-3">۳</div>
          <div class="flex-1">
            <div class="step-label text-[11px] text-[#4d7a56] mb-0.5">گام سوم</div>
            <div class="step-title text-xs font-bold text-[#a8c4a8]">خروجی و قیمت</div>
          </div>
        </div>
      </div>

      <form id="real-product-form" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="status" id="product-status" value="active">

        <div class="block space-y-4" id="panel-1">
          @include('admin.products.partials.step-identity')
        </div>

        {{-- ═══ گام دوم: هوش مصنوعی — حالا یک پارشیال جداست ═══ --}}
        <div class="hidden space-y-4" id="panel-2">
          @include('admin.products.partials.step-ai', ['aiModels' => $aiModels])
        </div>

        <div class="hidden space-y-4" id="panel-3">
          @include('admin.products.partials.step-output')
        </div>
      </form>
    </main>

    <div class="sticky bottom-0 bg-[#111116] border-t border-[#222230] p-4 flex items-center justify-between z-40">
      <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#16161c] text-[#a8c4a8] border border-[#222230] hover:text-white transition-all" id="btn-prev" onclick="prevStep()" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i> مرحله قبل
      </button>
      <div class="text-xs text-[#4d7a56]"> مرحله <strong class="text-white" id="step-label-num">۱</strong> از ۳ </div>
      <div class="flex gap-2">
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#16161c] text-[#a8c4a8] border border-[#222230] hover:text-white transition-all" onclick="submitForm('draft')">
          <i class="fa-solid fa-floppy-disk"></i> ذخیره پیش‌نویس
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#a07af5] text-white hover:bg-[#8f68e0] transition-all" id="btn-next" onclick="nextStep()">
          مرحله بعد <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[#0BBF53] text-white hover:bg-[#08a443] transition-all" id="btn-submit" onclick="submitForm('active')" style="display:none;">
          <i class="fa-solid fa-check"></i> ثبت نهایی محصول
        </button>
      </div>
    </div>

  </div>
</div>
@endsection

@section('scripts')
@php
    // لیست مدل‌های فعال — از کنترلر گرفته شده، برای ساخت داینامیک ردیف‌های fallback استفاده می‌شود
    $aiModelsForJs = $aiModels->map(function ($m) {
        return [
            'id' => $m->model_id,
            'name' => $m->name,
            'provider' => $m->provider,
        ];
    });
@endphp
<script>
const AI_MODELS = @json($aiModelsForJs);


let cur = 1;

function goStep(n) {
  if(n < 1 || n > 3) return;
  cur = n;
  
  for(let i=1; i<=3; i++) {
    const p = document.getElementById('panel-'+i);
    const tab = document.getElementById('step-tab-'+i);
    const num = document.getElementById('step-num-'+i);
    
    if(i === n) {
      p.classList.remove('hidden'); p.classList.add('block');
      tab.classList.add('bg-[#a07af5]/10', 'border-[#a07af5]/20');
      num.classList.add('border-[#a07af5]', 'bg-[#a07af5]/15', 'text-[#a07af5]');
    } else {
      p.classList.remove('block'); p.classList.add('hidden');
      tab.classList.remove('bg-[#a07af5]/10', 'border-[#a07af5]/20');
      num.classList.remove('border-[#a07af5]', 'bg-[#a07af5]/15', 'text-[#a07af5]');
    }
  }

  document.getElementById('btn-prev').style.display = n === 1 ? 'none' : 'inline-flex';
  document.getElementById('btn-next').style.display = n === 3 ? 'none' : 'inline-flex';
  document.getElementById('btn-submit').style.display = n === 3 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = n;
  window.scrollTo({top: 0, behavior: 'smooth'});
}

function nextStep() { if(cur < 3) goStep(cur + 1); }
function prevStep() { if(cur > 1) goStep(cur - 1); }

function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const v = inp.value.trim();
  if (!v) return;

  const chip = document.createElement('span');
  chip.className = 'inline-flex items-center gap-1 bg-[#a07af5]/12 border border-[#a07af5]/25 rounded px-2 py-0.5 text-xs text-[#a07af5]';
  chip.innerHTML = `${v}<button type="button" class="text-[#4d7a56] hover:text-[#f05c5c] font-bold mr-1" onclick="this.parentElement.remove()">×</button>`;
  document.getElementById('tags-wrap').insertBefore(chip, inp);
  inp.value = '';
}

function updateFileLabel(input, id, isMultiple = false) {
  if(input.files && input.files.length > 0) {
    document.getElementById(id).textContent = isMultiple 
      ? `${input.files.length} فایل انتخاب شد` 
      : `فایل انتخاب شد: ${input.files[0].name}`;
  }
}

function autoSlug(el) {
  const s = el.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  document.getElementById('slug-input').value = s;
}

function toggleCreditCost(sel) {
  const w = document.getElementById('credit-cost-wrap');
  if(sel.value === 'per_credit') {
    w.classList.remove('opacity-30', 'pointer-events-none');
  } else {
    w.classList.add('opacity-30', 'pointer-events-none');
  }
}

/* ── افزودن فیلد FALLBACK به صورت داینامیک — حالا از مدل‌های واقعی پنل ادمین ── */
let fbIdx = 0;
function addFallback() {
  if (!AI_MODELS.length) {
    alert('ابتدا حداقل یک مدل هوش مصنوعی فعال در سیستم ثبت کنید.');
    return;
  }

  fbIdx++;
  const div = document.createElement('div');
  div.className = 'bg-[#111116] border border-[#222230] rounded-xl p-3 flex items-center gap-3';
  div.id = `fb-row-${fbIdx}`;

  const options = AI_MODELS.map(m =>
    `<option value="${m.id}">${m.name} (${m.provider})</option>`
  ).join('');

  div.innerHTML = `
    <span class="text-[10px] font-mono text-gray-500 w-14 shrink-0">اولویت ${fbIdx + 1}</span>
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white flex-1 fallback-select-item">
      ${options}
    </select>
    <button type="button" class="text-xs text-[#f05c5c] bg-[#f05c5c]/10 px-2.5 py-1.5 rounded-lg" onclick="document.getElementById('fb-row-${fbIdx}').remove()">حذف</button>
  `;
  document.getElementById('fallback-list').appendChild(div);
}

function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.substring(0, s) + v + ta.value.substring(e);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
}

let fieldIdx = 0;
function addInputField() {
  fieldIdx++;
  const div = document.createElement('div');
  div.className = 'bg-[#111116] border border-[#222230] rounded-xl p-3 grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center input-schema-row';
  div.id = `field-row-${fieldIdx}`;
  div.innerHTML = `
    <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white ltr text-left schema-id" placeholder="field_id">
    <input type="text" class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white schema-label" placeholder="برچسب فارسی">
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white schema-type">
      <option value="text">text</option>
      <option value="image_upload">image_upload</option>
      <option value="select">select</option>
      <option value="toggle">toggle</option>
    </select>
    <select class="bg-[#111116] border border-[#222230] rounded-lg p-2 text-xs text-white schema-required">
      <option value="1">اجباری</option>
      <option value="0">اختیاری</option>
    </select>
    <button type="button" class="text-xs text-[#f05c5c] bg-[#f05c5c]/10 py-2 rounded-lg" onclick="document.getElementById('field-row-${fieldIdx}').remove()">حذف فیلد</button>
  `;
  document.getElementById('input-fields-list').appendChild(div);
}

const subcats = {
  PEOPLE: ['Professional', 'Fashion', 'Lifestyle'],
  BUSINESS: ['Real Estate', 'Medical', 'Education'],
  EVENTS: ['Birthday', 'Wedding', 'Nowruz'],
  FAMILY: ['Parents', 'Kids'],
  AVATARS: ['Gaming', 'Anime', 'Fantasy']
};
function updateSubcat() {
  const main = document.getElementById('cat-main').value;
  const sub = document.getElementById('cat-sub');
  sub.innerHTML = '';
  if(!main || !subcats[main]) {
    sub.innerHTML = '<option value="">زیردسته ندارد</option>';
    return;
  }
  subcats[main].forEach(s => {
    sub.innerHTML += `<option value="${s}">${s}</option>`;
  });
}

function createHiddenInput(name, value) {
  const input = document.createElement('input');
  input.type = 'hidden'; input.name = name; input.value = value;
  return input;
}

function submitForm(statusValue) {
  document.getElementById('product-status').value = statusValue;
  const form = document.getElementById('real-product-form');

  document.querySelectorAll('#tags-wrap span').forEach((chip, idx) => {
    const text = chip.textContent.replace('×', '').trim();
    if(text) form.appendChild(createHiddenInput(`tags[${idx}]`, text));
  });

  // ترتیب select‌های fallback همان ترتیب چیده‌شده در صفحه = اولویت ذخیره‌شده در دیتابیس
  document.querySelectorAll('.fallback-select-item').forEach((select, idx) => {
    form.appendChild(createHiddenInput(`fallback_models[${idx}]`, select.value));
  });

  document.querySelectorAll('.input-schema-row').forEach((row, idx) => {
    const fieldId = row.querySelector('.schema-id').value.trim();
    const labelFa = row.querySelector('.schema-label').value.trim();
    const type = row.querySelector('.schema-type').value;
    const required = row.querySelector('.schema-required').value;

    if(fieldId) {
      form.appendChild(createHiddenInput(`input_schema[${idx}][field_id]`, fieldId));
      form.appendChild(createHiddenInput(`input_schema[${idx}][label_fa]`, labelFa));
      form.appendChild(createHiddenInput(`input_schema[${idx}][type]`, type));
      form.appendChild(createHiddenInput(`input_schema[${idx}][required]`, required));
    }
  });

  form.submit();
}
</script>
@endsection