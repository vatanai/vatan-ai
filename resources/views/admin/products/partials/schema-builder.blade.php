{{-- ═══════════════════════════════════════════════════════════════════════════
     پارشیال استاندارد: سازنده «ویژگی‌های خاص محصول» (Schema Builder)
     ─────────────────────────────────────────────────────────────────────────
     جایگزین حرفه‌ای دو کارت قبلی گام ۳ («ویژگی‌های خاص محصول» UI-only و
     «فیلدهای ورودی کاربر»). همه‌چیز در ستون input_schema محصول ذخیره می‌شود
     بک‌اند ساختار کامل را پیش از ذخیره اعتبارسنجی و پاک‌سازی می‌کند.

     معماری:
       • رجیستری انواع فیلد: config/product_schema_types.php  (تنها منبع حقیقت)
       • منطق سازنده:        public/admin/js/schema-builder.js (State-driven)
       • استایل اختصاصی:     public/admin/css/schema-builder.css (توکن‌محور، تم روز/شب)
       • سازگاری کامل عقب‌رو: کانتینر #input-fields-list و کلاس‌های schema-id /
         schema-label / schema-type / schema-required حفظ شده‌اند تا
         products-create.js (Stepper گام ۳) با ساختار جدید کار کند.
         فرمت قدیمی options (رشته با کاما) هنگام لود به فرمت جدید تبدیل می‌شود.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
  // آیکون «راهنمایی آیتم» — از همان سیستم مشترک راهنمای فرم ثبت محصول
  $__sbHelp = function (string $key, string $title) {
      $text = config('product_field_help.' . $key, '');
      if ($text === '') return '';
      return '<span class="field-help-btn inline-flex items-center justify-center shrink-0 cursor-pointer text-[var(--text3)] hover:text-[var(--accent)] transition-colors" role="button" tabindex="0" data-help-title="' . e($title) . '" data-help-text="' . e($text) . '" aria-label="راهنمایی آیتم"><i class="fa-solid fa-circle-question text-[10px]"></i></span>';
  };

  // داده اولیه: خطای ولیدیشن (old) → محصول در حال ویرایش → محصول مبدا تکثیر
  // (رفع نقص قبلی: در حالت «ویرایش»، فیلدهای موجود محصول اصلاً لود نمی‌شدند)
  $__sbInitial = old('input_schema');
  if (!is_array($__sbInitial)) {
      $__sbInitial = ($product->input_schema ?? null) ?: (optional($duplicateFrom)->input_schema ?? []);
  }
  $__sbInitial = is_array($__sbInitial) ? array_values($__sbInitial) : [];
@endphp

<link rel="stylesheet" href="{{ asset('admin/css/schema-builder.css') }}?v={{ filemtime(public_path('admin/css/schema-builder.css')) }}">

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5" id="schema-builder-card">

  {{-- ── هدر کارت ── --}}
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap">
        <i class="fa-solid fa-swatchbook text-[var(--accent)]"></i>
        ویژگی‌های خاص محصول
        {!! $__sbHelp('input_schema', 'ویژگی‌های خاص محصول') !!}
        <span id="sb-count-badge" class="sb-count-badge" style="display:none"></span>
      </div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">
        این ویژگی‌ها دقیقاً همان فرمی است که کاربر در صفحه «بساز» پر می‌کند و مقادیرش وارد پرامپت هوش مصنوعی می‌شود
      </div>
    </div>
    <button type="button" class="sb-btn-primary" onclick="sbOpenLibrary()">
      <i class="fa-solid fa-sliders"></i> حالت‌های بیشتر
    </button>
  </div>

  <div class="sb-template-intro">
    <div>
      <strong>یک نمونه آماده انتخاب کنید</strong>
      <span>بعد از افزودن، عنوان و متن پرامپت هر گزینه را به دلخواه تغییر دهید.</span>
    </div>
    <span class="sb-template-count">۷ سبک پرتکرار</span>
  </div>
  <div class="sb-template-grid">
    @foreach(config('product_schema_types.templates', []) as $templateKey => $template)
      <button type="button" class="sb-template-card" onclick="sbAddTemplate(@js($templateKey))">
        <span class="sb-template-icon"><i class="fa-solid {{ $template['icon'] }}"></i></span>
        <span>
          <strong>{{ $template['label'] }}</strong>
          <small>{{ $template['desc'] }}</small>
        </span>
        <i class="fa-solid fa-plus sb-template-plus"></i>
      </button>
    @endforeach
  </div>

  <div class="sb-user-preview">
    <div class="sb-user-preview-head">
      <span><i class="fa-solid fa-eye"></i> نمایی که کاربر در سایت می‌بیند</span>
      <small>هم‌زمان با تغییر ویژگی‌ها به‌روز می‌شود</small>
    </div>
    <div id="sb-live-preview" class="sb-live-preview"></div>
  </div>

  {{-- ── لیست فیلدها (State-driven — توسط schema-builder.js رندر می‌شود) ──
       نکته سازگاری: id همان #input-fields-list قبلی است تا Stepper گام ۳
       (computeStepStatus در products-create.js) بدون تغییر کار کند. --}}
  <div id="input-fields-list" class="sb-list"></div>

  {{-- ── حالت خالی ── --}}
  <div id="sb-empty" class="sb-empty" style="display:none">
    <div class="sb-empty-icon"><i class="fa-solid fa-swatchbook"></i></div>
    <div class="sb-empty-title">هنوز ویژگی‌ای تعریف نشده است</div>
    <div class="sb-empty-desc">مثلاً «سبک تصویر»، «نسبت خروجی» یا «آپلود عکس چهره» را اضافه کنید</div>
    <button type="button" class="sb-btn-dashed" onclick="sbOpenLibrary()">
      <i class="fa-solid fa-plus"></i> افزودن اولین ویژگی
    </button>
  </div>

  {{-- ── دکمه افزودن پایین لیست ── --}}
  <button type="button" id="sb-add-bottom" class="sb-btn-dashed sb-add-bottom" style="display:none" onclick="sbOpenLibrary()">
    <i class="fa-solid fa-plus"></i> افزودن ویژگی جدید
  </button>
</div>

{{-- ═══ مودال کتابخانه انواع ویژگی ═══ --}}
<div id="sb-library-overlay" class="sb-overlay" style="display:none" onclick="if(event.target === this) sbCloseLibrary()">
  <div class="sb-modal" role="dialog" aria-modal="true" aria-labelledby="sb-library-title">
    <div class="sb-modal-head">
      <div class="sb-modal-head-right">
        <span class="sb-modal-icon"><i class="fa-solid fa-swatchbook"></i></span>
        <div>
          <div id="sb-library-title" class="sb-modal-title">انتخاب نوع ویژگی</div>
          <div class="sb-modal-sub">نوع فیلدی که کاربر در صفحه بساز می‌بیند را انتخاب کنید</div>
        </div>
      </div>
      <button type="button" class="sb-modal-close" onclick="sbCloseLibrary()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="sb-modal-search">
      <input type="text" id="sb-library-search" placeholder="جستجوی نوع ویژگی..." class="sb-input" oninput="sbRenderLibrary(this.value)">
    </div>
    <div id="sb-library-body" class="sb-modal-body"></div>
  </div>
</div>

{{-- تزریق رجیستری انواع + داده اولیه به JS سازنده --}}
<script>
window.SCHEMA_BUILDER_CFG = {
  groups:  @json(config('product_schema_types.groups', [])),
  types:   @json(config('product_schema_types.types', [])),
  templates: @json(config('product_schema_types.templates', [])),
  help:    @json(config('product_field_help.input_schema_fields', [])),
  initial: @json($__sbInitial),
};
</script>
<script src="{{ asset('admin/js/schema-builder.js') }}?v={{ filemtime(public_path('admin/js/schema-builder.js')) }}"></script>
