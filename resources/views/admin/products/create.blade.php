@extends('layouts.admin')
@section('title', 'ثبت محصول جدید — AIPIX Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/products-create.css') }}">
@endpush

@section('content')
<div class="flex min-h-screen" dir="rtl" style="background:var(--page-bg);color:var(--text-h);">

  <main id="product-create-page" class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">

      <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-1.5 text-xs text-[var(--text2)]">
          <a href="/admin/dashboard" class="text-[var(--text2)] hover:text-[var(--text)] transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
          <span class="text-[var(--text3)] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
          <a href="/admin/products" class="text-[var(--text2)] hover:text-[var(--text)] transition-colors">محصولات</a>
          <span class="text-[var(--text3)] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
          <span class="text-[var(--text)] font-semibold">{{ $product ? 'ویرایش محصول' : ($duplicateFrom ? 'تکثیر محصول' : 'ثبت محصول جدید') }}</span>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
          {{-- بند ۳۳: انتخاب نقش نمایشی (Role Preview) — فقط UI، پیش‌نمایش قفل فیلدها --}}
          <div class="inline-flex items-center gap-1.5">
            <span class="text-[10.5px] text-[var(--text3)]">نمای نقش:</span>
            <select id="role-preview-select" class="bg-[var(--s2)] border border-[var(--b1)] rounded-lg px-2 h-8 text-[11px] text-[var(--text2)]" title="پیش‌نمایش UI بر اساس نقش — فقط نمایشی">
              <option value="admin">Admin</option>
              <option value="editor">Editor</option>
              <option value="viewer">Viewer</option>
            </select>
          </div>
          <a href="/admin/products" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] transition-all hover:border-[var(--b2)] hover:text-[var(--text)] no-underline">
            <i class="fa-solid fa-arrow-right text-[11px]"></i>
            بازگشت به لیست
          </a>
          @if($product)
            <a href="{{ route('admin.lab.create', ['product_id' => $product->id]) }}" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[var(--primary-l)] text-[var(--primary)] border border-[var(--primary-m)] transition-all no-underline">
              <i class="fa-solid fa-flask text-[11px]"></i> آزمایش این محصول
            </a>
          @else
            <button type="button" onclick="submitProductAndOpenLab()" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[var(--primary-l)] text-[var(--primary)] border border-[var(--primary-m)] transition-all">
              <i class="fa-solid fa-flask text-[11px]"></i> ثبت و آزمایش
            </button>
          @endif
        </div>
      </div>

      {{-- NEW: Global Error Handler UI — Toast غیرمسدودکننده برای خطاهای عمومی سمت کلاینت --}}
      <div id="global-error-toast" class="hidden fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-md w-[92%] bg-[var(--red)] text-white text-xs font-semibold rounded-xl px-4 py-3 shadow-2xl flex items-center gap-2.5" role="alert">
        <i class="fa-solid fa-circle-exclamation shrink-0"></i>
        <span id="global-error-text" class="flex-1"></span>
        <button type="button" class="shrink-0 opacity-80 hover:opacity-100" onclick="hideGlobalError()" aria-label="بستن پیام خطا"><i class="fa-solid fa-xmark"></i></button>
      </div>

      {{-- «راهنمایی آیتم» — پنجره مشترک نمایش توضیح کامل هر فیلد اجباری/اختیاری (کلیک روی آیکون کنار توضیح فیلد) --}}
      <div id="field-help-overlay" class="hidden fixed inset-0 z-[110] field-help-overlay-bg flex items-center justify-center p-4" onclick="if(event.target === this) closeFieldHelp()">
        <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-2xl shadow-2xl w-full max-w-md p-5 max-h-[80vh] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="field-help-title">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-[var(--accent)]/12 text-[var(--accent)] flex items-center justify-center shrink-0"><i class="fa-solid fa-circle-question text-sm"></i></span>
              <div id="field-help-title" class="text-sm font-bold text-[var(--text)]">راهنمای فیلد</div>
            </div>
            <button type="button" class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-[var(--text3)] hover:text-[var(--text)] hover:bg-[var(--b1)] transition-colors" onclick="closeFieldHelp()" aria-label="بستن راهنما"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div id="field-help-text" class="text-xs text-[var(--text2)] leading-relaxed"></div>
        </div>
      </div>

      @if ($errors->any())
        <div class="bg-[var(--red)]/10 border border-[var(--red)] rounded-xl p-4 mb-6 text-right" role="alert">
            <div class="text-[var(--red)] font-bold text-sm mb-2.5">
                <i class="fa-solid fa-triangle-exclamation"></i> اصلاح خطاهای زیر برای ثبت محصول الزامی است:
            </div>
            <ul class="text-[var(--red-soft)] text-xs pr-5 margin-0 list-disc space-y-1.5 leading-relaxed">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
      @endif

      @if ($aiModels->isEmpty())
        <div class="bg-[var(--orange)]/10 border border-[var(--orange)] rounded-xl p-4 mb-6 text-right text-xs text-[var(--orange)]">
            <i class="fa-solid fa-circle-exclamation"></i>
            هیچ مدل هوش مصنوعی فعالی در سیستم ثبت نشده است. ابتدا از
            <a href="{{ route('admin.ai-models.create') }}" class="underline font-bold">این صفحه</a>
            حداقل یک مدل اضافه کنید.
        </div>
      @endif

      @if($product)
        <div class="bg-[var(--accent)]/8 border border-[var(--accent)]/25 rounded-xl p-3.5 mb-5 flex items-center gap-2.5 text-xs text-[var(--accent-soft)]">
          <i class="fa-solid fa-pen"></i>
          در حال ویرایش «{{ $product->name_fa }}» — تمام فیلدها با اطلاعات فعلی همین محصول پر شده‌اند؛ فقط موارد لازم را تغییر دهید و ثبت کنید.
        </div>
      @elseif($duplicateFrom)
        <div class="bg-[var(--accent)]/8 border border-[var(--accent)]/25 rounded-xl p-3.5 mb-5 flex items-center gap-2.5 text-xs text-[var(--accent-soft)]">
          <i class="fa-solid fa-copy text-[var(--accent)]"></i>
          در حال تکثیر «{{ $duplicateFrom->name_fa }}» — تمام فیلدها با اطلاعات این محصول پر شده‌اند؛ فقط موارد لازم را تغییر دهید و ثبت کنید.
        </div>
      @endif

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">{{ $product ? 'ویرایش محصول' : ($duplicateFrom ? 'تکثیر محصول' : 'ثبت محصول جدید') }}</div>
        <div class="text-xs text-[var(--text3)]">محصول را در ۵ مرحله تنظیم کنید — هویت، رسانه، هوش مصنوعی، ورودی و خروجی</div>
      </div>

      {{-- NEW: Validation Summary Panel — خلاصه خطاهای همان Step فعلی (سمت کلاینت، مکمل Validation واقعی سرور) --}}
      <div id="validation-summary" class="hidden bg-[var(--red)]/8 border border-[var(--red)]/30 rounded-xl p-3.5 mb-5 text-xs">
        <div class="text-[var(--red)] font-bold mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> برای ثبت نهایی محصول، این موارد را باید تکمیل کنید:</div>
        <ul class="text-[var(--red-soft)] pr-5 list-disc space-y-1" id="validation-summary-list"></ul>
      </div>

      {{-- ═══ Stepper بازطراحی‌شده — یک ساختار واحد و ریسپانسیو (دسکتاپ افقی / موبایل عمودی) ═══ --}}
      <div class="mb-7 bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-2 md:p-1.5">
        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-0">

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-1" onclick="goStep(1)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 transition-all duration-200" id="step-num-1" data-num="۱">۱</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] mb-0.5 transition-colors">گام اول</div>
              <div class="step-title text-xs font-bold transition-colors">هویت محصول</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">اطلاعات پایه محصول</div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5 pr-1">
              <span class="step-frac hidden text-[10px] font-bold font-mono text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5" id="step-frac-1"></span>
              <span class="step-check hidden text-[var(--green)]" id="step-check-1" title="این مرحله کامل شده"><i class="fa-solid fa-circle-check text-sm"></i></span>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-1"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-1-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-2" onclick="goStep(2)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-2" data-num="۲">۲</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام دوم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">هوش مصنوعی</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">مدل و پرامپت</div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5 pr-1">
              <span class="step-frac hidden text-[10px] font-bold font-mono text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5" id="step-frac-2"></span>
              <span class="step-check hidden text-[var(--green)]" id="step-check-2" title="این مرحله کامل شده"><i class="fa-solid fa-circle-check text-sm"></i></span>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-2"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-2-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-3" onclick="goStep(3)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-3" data-num="۳">۳</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام سوم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">ویژگی‌های محصول</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">نمونه و فرم کاربر</div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5 pr-1">
              <span class="step-frac hidden text-[10px] font-bold font-mono text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5" id="step-frac-3"></span>
              <span class="step-check hidden text-[var(--green)]" id="step-check-3" title="این مرحله کامل شده"><i class="fa-solid fa-circle-check text-sm"></i></span>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-3"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-3-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-4" onclick="goStep(4)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-4" data-num="۴">۴</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام چهارم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">خروجی و قیمت</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">واترمارک، قیمت، انتشار</div>
            </div>
            <div class="shrink-0 flex items-center gap-1.5 pr-1">
              <span class="step-frac hidden text-[10px] font-bold font-mono text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5" id="step-frac-4"></span>
              <span class="step-check hidden text-[var(--green)]" id="step-check-4" title="این مرحله کامل شده"><i class="fa-solid fa-circle-check text-sm"></i></span>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-4"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-4-m"></div>
          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-5" onclick="goStep(5)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-5" data-num="۵">۵</div>
            <div class="flex-1 min-w-0"><div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام پنجم</div><div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">بازبینی نهایی</div><div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">مرور و ثبت محصول</div></div>
            <div class="shrink-0 flex items-center gap-1.5 pr-1"><span class="step-frac hidden text-[10px] font-bold font-mono text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5" id="step-frac-5"></span><span class="step-check hidden text-[var(--green)]" id="step-check-5"><i class="fa-solid fa-circle-check text-sm"></i></span></div>
          </div>
        </div>
      </div>

      {{-- در حالت ویرایش، فرم باید PUT به‌سمت update() برود نه POST به store() — وگرنه (باگ قبلی)
           هر «ویرایش» عملاً یک محصول تکراری جدید می‌ساخت و محصول اصلی هرگز به‌روزرسانی نمی‌شد. --}}
      <form id="real-product-form"
            action="{{ $product ? route('admin.products.update', $product->id) : route('admin.products.store') }}"
            method="POST" enctype="multipart/form-data">
        @csrf
        @if($product)
          @method('PUT')
        @endif
        <input type="hidden" name="status" id="product-status" value="active">
        <input type="hidden" name="open_lab_after_save" id="open-lab-after-save" value="0">
        @if($duplicateFrom)
          <input type="hidden" name="duplicate_from" value="{{ $duplicateFrom->id }}">
        @endif

        <div class="block space-y-4" id="panel-1">
          @include('admin.products.partials.step-1', ['duplicateFrom' => $duplicateFrom, 'product' => $product])
          @include('admin.products.partials.future-updates')
        </div>

        {{-- ═══ گام دوم: هوش مصنوعی — پایپ‌لاین و پرامپت ═══ --}}
        <div class="hidden space-y-4" id="panel-2">
          @include('admin.products.partials.step-2', ['aiModels' => $aiModels, 'duplicateFrom' => $duplicateFrom, 'product' => $product])
        </div>

        {{-- ═══ گام سوم: متغیرها و فیلدهای ورودی کاربر ═══ --}}
        <div class="hidden space-y-4" id="panel-3">
          @include('admin.products.partials.step-3', ['duplicateFrom' => $duplicateFrom, 'product' => $product])
        </div>

        {{-- ═══ گام چهارم: خروجی و قیمت ═══ --}}
        <div class="hidden space-y-4" id="panel-4">
          @include('admin.products.partials.step-4', ['duplicateFrom' => $duplicateFrom, 'product' => $product])
        </div>

        {{-- ═══ گام پنجم: بازبینی نهایی ═══ --}}
        <div class="hidden space-y-4" id="panel-5">
          @include('admin.products.partials.step-5', ['duplicateFrom' => $duplicateFrom, 'product' => $product])
        </div>
      </form>
    </div>

    <div class="sticky bottom-0 bg-[var(--s1)] border-t border-[var(--b1)] p-3 md:p-4 flex items-center justify-between gap-2 flex-wrap z-40">
      <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] hover:text-[var(--text)] transition-all order-1" id="btn-prev" onclick="prevStep()" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i> <span class="hidden sm:inline">مرحله قبل</span>
      </button>
      <div class="flex-1 min-w-[220px] max-w-xl order-3 sm:order-2 w-full">
        <div class="flex items-center justify-between mb-1.5">
          <span class="text-[10.5px] font-bold text-[var(--text2)]"><i class="fa-solid fa-gauge-high text-[var(--accent)] ml-1"></i> پیشرفت موارد اجباری</span>
          <span class="text-[10.5px] font-bold text-[var(--text)]" id="wizard-progress-pct">۰٪</span>
        </div>
        <div id="wizard-progress-track"><div id="wizard-progress-fill"></div></div>
        <div class="text-[9.5px] text-[var(--text3)] mt-1 text-center">مرحله <strong id="step-label-num">۱</strong> از ۵ · پیش‌نویس فقط با دکمه ذخیره می‌شود</div>
      </div>
      <div class="flex gap-2 order-2 sm:order-3">
        <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)] transition-all" id="btn-next" onclick="nextStep()">
          <span class="hidden sm:inline">مرحله بعد</span> <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] hover:text-[var(--text)] transition-all" id="btn-draft" onclick="submitForm('draft')">
          <i class="fa-solid fa-floppy-disk"></i> <span class="hidden sm:inline">ذخیره در پیش‌نویس</span>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--b1)] text-[var(--text3)] border border-[var(--b2)] transition-all" id="btn-submit" onclick="submitForm('active')" aria-disabled="true">
          <i class="fa-solid fa-lock" id="final-submit-icon"></i>
          <span class="hidden sm:inline">ثبت نهایی محصول</span>
          <span id="final-submit-progress" class="inline-flex items-center justify-center min-w-10 h-6 px-1.5 rounded-md bg-[var(--s2)]/70 text-[10px]" dir="ltr">۰/۰</span>
        </button>
      </div>
    </div>

  </main>
</div>
@endsection

@section('scripts')
@php
    // لیست مدل‌های فعال — از کنترلر گرفته شده، برای ساخت داینامیک ردیف‌های fallback استفاده می‌شود
    $aiModelsForJs = $aiModels->map(function ($m) {
        return [
            'id' => $m->openrouter_model_id,
            'name' => $m->name,
            'provider' => $m->provider_name,
            'apiProvider' => $m->provider ?? 'openrouter',
        ];
    });
    $fbIdxStart = count(old('fallback_models', optional($duplicateFrom)->fallback_models ?? []));
    $fieldIdxStart = count(old('input_schema', optional($duplicateFrom)->input_schema ?? []));
@endphp
{{-- مقادیر پویا (مدل‌های AI، ایندکس‌های شروع، زیردسته انتخابی) اینجا به JS تزریق می‌شوند؛
     تمام منطق واقعی در public/admin/js/products-create.js نگه‌داری می‌شود --}}
<script>
function submitProductAndOpenLab() {
  const target = document.getElementById('open-lab-after-save');
  if (target) target.value = '1';
  if (typeof submitForm === 'function') submitForm('draft');
  else document.getElementById('real-product-form')?.submit();
}
window.PRODUCT_CREATE_CONFIG = {
  isFreshProduct: {{ (!$product && !$duplicateFrom && !session()->hasOldInput()) ? 'true' : 'false' }},
  aiModels: @json($aiModelsForJs),
  fbIdxStart: {{ $fbIdxStart }},
  fieldIdxStart: {{ $fieldIdxStart }},
  wantedSubcategory: @json(old('subcategory', optional($duplicateFrom)->subcategory)),
};
</script>
<script src="{{ asset('admin/js/products-create.js') }}?v={{ filemtime(public_path('admin/js/products-create.js')) }}"></script>
@endsection
