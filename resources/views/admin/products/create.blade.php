@extends('layouts.admin')
@section('title', 'ثبت محصول جدید — AIPIX Admin')

@push('styles')
<style>
  /* ══════════════════════════════════════════════════════════════
     توکن‌های رنگی صفحه ثبت محصول — پشتیبانی حالت شب/روز
     همان اسم متغیرهایی که در resources/views/admin/products/products-dashboard.blade.php
     استفاده شده (bg/s1/s2/b1/b2/text/text2/text3/accent/green/red/orange) تا با بقیه
     بخش «داشبورد محصولات» هماهنگ بماند؛ فقط اینجا override حالت روشن هم اضافه شده.
     رنگ‌های برند/معنایی (accent, green, red, orange) طبق قرارداد design-tokens.css
     در هر دو حالت ثابت می‌مانند — فقط بک‌گراند/بوردر/متن بین شب و روز تغییر می‌کند.
     ══════════════════════════════════════════════════════════════ */
  #product-create-page {
    --bg:     #0c0c10;
    --s1:     #111116;
    --s2:     #16161c;
    --b1:     #222230;
    --b2:     #2e2e3e;
    --text:   #ffffff;
    --text2:  #a8c4a8;
    --text3:  #4d7a56;
    --text4:  #3a3a48;
    --accent:       #a07af5;
    --accent-hover: #8f68e0;
    --accent-soft:  #c9b8f5;
    --green:        #0BBF53;
    --green-hover:  #08a443;
    --red:          #f05c5c;
    --red-soft:     #ff9191;
    --orange:       #f0a05c;
  }
  body.light #product-create-page {
    --bg:     #f5f5f5;
    --s1:     #f5f5f5;
    --s2:     #ffffff;
    --b1:     #E5E6E6;
    --b2:     #d5d6d6;
    --text:   #111111;
    --text2:  #3a3a3a;
    --text3:  #686E6B;
    --text4:  #9a9a9a;
    --accent-soft:  #6d4fc4;
    /* accent/green/red/orange عمداً override نمی‌شوند — طبق قرارداد design-tokens.css در هر دو حالت ثابت‌اند */
  }

  /* NEW: Skeleton System Unified — یک کلاس مشترک shimmer برای همه حالت‌های Loading صفحه */
  .skeleton { position: relative; overflow: hidden; background: var(--s1); }
  .skeleton::after {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(90deg, transparent, rgba(160,122,245,0.12), transparent);
    animation: skeleton-shimmer 1.4s infinite;
  }
  @keyframes skeleton-shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
  /* A11Y: مشخص بودن واضح Focus روی عناصر تعاملی */
  #real-product-form :focus-visible, .admin-content :focus-visible { outline: 2px solid var(--accent); outline-offset: 1px; }
</style>
@endpush

@section('content')
<div class="flex min-h-screen bg-[var(--bg)] text-[var(--text)]" dir="rtl">

  <main id="product-create-page" class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">

      <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-1.5 text-xs text-[var(--text2)]">
          <a href="/admin/dashboard" class="text-[var(--text2)] hover:text-[var(--text)] transition-colors"><i class="fa-solid fa-house text-[11px]"></i></a>
          <span class="text-[var(--text3)] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
          <a href="/admin/products" class="text-[var(--text2)] hover:text-[var(--text)] transition-colors">محصولات</a>
          <span class="text-[var(--text3)] text-[10px]"><i class="fa-solid fa-chevron-left"></i></span>
          <span class="text-[var(--text)] font-semibold">{{ $duplicateFrom ? 'تکثیر محصول' : 'ثبت محصول جدید' }}</span>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
          {{-- NEW: Role-Based UI Locking — نمایشی برای نقش‌بندی آینده؛ در حالت پیش‌فرض (Admin) هیچ محدودیتی اعمال نمی‌شود --}}
          <div class="flex items-center gap-1.5" title="NEW Role-Based UI Locking — برنامه‌نویسی شود">
            <label class="text-[10px] text-[var(--text3)]">نقش نمایشی:</label>
            <select id="role-preview-select" class="bg-[var(--s2)] border border-[var(--b1)] rounded-lg px-2 py-1.5 text-[10.5px] text-[var(--text2)]" onchange="applyRolePreview(this.value)" aria-label="NEW نقش نمایشی — فقط UI">
              <option value="admin">Admin</option>
              <option value="editor">Editor</option>
              <option value="viewer">Viewer</option>
            </select>
          </div>
          <a href="/admin/products" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-lg text-xs font-semibold bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] transition-all hover:border-[var(--b2)] hover:text-[var(--text)] no-underline">
            <i class="fa-solid fa-arrow-right text-[11px]"></i>
            بازگشت به لیست
          </a>
        </div>
      </div>

      {{-- NEW: Global Error Handler UI — Toast غیرمسدودکننده برای خطاهای عمومی سمت کلاینت --}}
      <div id="global-error-toast" class="hidden fixed top-4 left-1/2 -translate-x-1/2 z-[100] max-w-md w-[92%] bg-[var(--red)] text-[var(--text)] text-xs font-semibold rounded-xl px-4 py-3 shadow-2xl flex items-center gap-2.5" role="alert">
        <i class="fa-solid fa-circle-exclamation shrink-0"></i>
        <span id="global-error-text" class="flex-1"></span>
        <button type="button" class="shrink-0 opacity-80 hover:opacity-100" onclick="hideGlobalError()" aria-label="بستن پیام خطا"><i class="fa-solid fa-xmark"></i></button>
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

      @if($duplicateFrom)
        <div class="bg-[var(--accent)]/8 border border-[var(--accent)]/25 rounded-xl p-3.5 mb-5 flex items-center gap-2.5 text-xs text-[var(--accent-soft)]">
          <i class="fa-solid fa-copy text-[var(--accent)]"></i>
          در حال تکثیر «{{ $duplicateFrom->name_fa }}» — تمام فیلدها با اطلاعات این محصول پر شده‌اند؛ فقط موارد لازم را تغییر دهید و ثبت کنید.
        </div>
      @endif

      <div class="mb-6">
        <div class="text-xl font-extrabold tracking-tight mb-1">{{ $duplicateFrom ? 'تکثیر محصول' : 'ثبت محصول جدید' }}</div>
        <div class="text-xs text-[var(--text3)]">محصول را در ۵ مرحله تنظیم کنید — هویت، رسانه، هوش مصنوعی، ورودی و خروجی</div>
      </div>

      {{-- NEW: بنر بازیابی پیش‌نویس محلی (Form Persistence Layer) — فقط مرورگر همین کاربر، هیچ داده‌ای به Backend ارسال نمی‌شود --}}
      <div id="draft-restore-banner" class="hidden bg-[var(--accent)]/8 border border-[var(--accent)]/25 rounded-xl p-3.5 mb-5 flex items-center justify-between gap-3 flex-wrap text-xs text-[var(--accent-soft)]">
        <div class="flex items-center gap-2.5"><i class="fa-solid fa-clock-rotate-left text-[var(--accent)]"></i> یک پیش‌نویس محلی ذخیره‌شده از این فرم پیدا شد. بازیابی شود؟</div>
        <div class="flex gap-2">
          <button type="button" class="px-3 h-7 rounded-lg bg-[var(--accent)] text-[var(--text)] text-[11px] font-bold" onclick="restoreLocalDraft()">بازیابی</button>
          <button type="button" class="px-3 h-7 rounded-lg bg-[var(--text)]/5 text-[var(--text2)] text-[11px] font-bold" onclick="discardLocalDraft()">نادیده‌گرفتن</button>
        </div>
      </div>

      {{-- NEW: Validation Summary Panel — خلاصه خطاهای همان Step فعلی (سمت کلاینت، مکمل Validation واقعی سرور) --}}
      <div id="validation-summary" class="hidden bg-[var(--red)]/8 border border-[var(--red)]/30 rounded-xl p-3.5 mb-5 text-xs">
        <div class="text-[var(--red)] font-bold mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> برای رفتن به مرحله بعد، این موارد را تکمیل کنید:</div>
        <ul class="text-[var(--red-soft)] pr-5 list-disc space-y-1" id="validation-summary-list"></ul>
      </div>

      {{-- ═══ Stepper بازطراحی‌شده — یک ساختار واحد و ریسپانسیو (دسکتاپ افقی / موبایل عمودی) ═══ --}}
      <div class="mb-7 bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-2 md:p-1.5">
        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-0">

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-1" onclick="attemptGoStep(1)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 transition-all duration-200" id="step-num-1" data-num="۱">۱</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] mb-0.5 transition-colors">گام اول</div>
              <div class="step-title text-xs font-bold transition-colors">هویت محصول</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">اطلاعات پایه، برچسب و رسانه</div>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-1"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-1-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-2" onclick="attemptGoStep(2)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-2" data-num="۲">۲</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام دوم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">هوش مصنوعی</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">مدل و پرامپت</div>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-2"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-2-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-3" onclick="attemptGoStep(3)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-3" data-num="۳">۳</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام سوم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">ورودی و متغیرها</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">فیلدهای فرم کاربر</div>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-3"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-3-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-4" onclick="attemptGoStep(4)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-4" data-num="۴">۴</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام چهارم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">خروجی و قیمت</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">واترمارک، قیمت، انتشار</div>
            </div>
          </div>

          <div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)] transition-colors" id="conn-4"></div>
          <div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px] transition-colors" id="conn-4-m"></div>

          <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent" id="step-tab-5" onclick="attemptGoStep(5)">
            <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 border-[var(--b2)] text-[var(--text3)] transition-all duration-200" id="step-num-5" data-num="۵">۵</div>
            <div class="flex-1 min-w-0">
              <div class="step-label text-[11px] text-[var(--text3)] mb-0.5 transition-colors">گام پنجم</div>
              <div class="step-title text-xs font-bold text-[var(--text2)] transition-colors">بازبینی نهایی</div>
              <div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">مرور و ثبت محصول</div>
            </div>
          </div>

        </div>
      </div>

      <form id="real-product-form" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="status" id="product-status" value="active">
        @if($duplicateFrom)
          <input type="hidden" name="duplicate_from" value="{{ $duplicateFrom->id }}">
        @endif

        <div class="block space-y-4" id="panel-1">
          @include('admin.products.partials.step-identity', ['duplicateFrom' => $duplicateFrom])
        </div>

        {{-- ═══ گام دوم: هوش مصنوعی — پایپ‌لاین و پرامپت ═══ --}}
        <div class="hidden space-y-4" id="panel-2">
          @include('admin.products.partials.step-ai', ['aiModels' => $aiModels, 'duplicateFrom' => $duplicateFrom])
        </div>

        {{-- ═══ گام سوم: متغیرها و فیلدهای ورودی کاربر (قبلاً بخشی از گام هوش مصنوعی بود) ═══ --}}
        <div class="hidden space-y-4" id="panel-3">
          @include('admin.products.partials.step-ai-inputs', ['duplicateFrom' => $duplicateFrom])
        </div>

        {{-- ═══ گام چهارم: خروجی و قیمت ═══ --}}
        <div class="hidden space-y-4" id="panel-4">
          @include('admin.products.partials.step-output', ['duplicateFrom' => $duplicateFrom])
        </div>

        {{-- ═══ گام پنجم: بازبینی نهایی (قبلاً بخشی از گام خروجی و قیمت بود) ═══ --}}
        <div class="hidden space-y-4" id="panel-5">
          @include('admin.products.partials.step-summary', ['duplicateFrom' => $duplicateFrom])
        </div>
      </form>
    </div>

    <div class="sticky bottom-0 bg-[var(--s1)] border-t border-[var(--b1)] p-3 md:p-4 flex items-center justify-between gap-2 flex-wrap z-40">
      <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] hover:text-[var(--text)] transition-all order-1" id="btn-prev" onclick="prevStep()" style="display:none;">
        <i class="fa-solid fa-arrow-right"></i> <span class="hidden sm:inline">مرحله قبل</span>
      </button>
      <div class="flex flex-col items-center gap-0.5 order-3 sm:order-2 w-full sm:w-auto text-center">
        <div class="text-xs text-[var(--text3)]"> مرحله <strong class="text-[var(--text)]" id="step-label-num">۱</strong> از ۵ </div>
        <div class="text-[10px] text-[var(--text3)] flex items-center gap-1 justify-center" id="last-saved-label" title="NEW Auto Save Draft — فقط در همین مرورگر ذخیره می‌شود، برنامه‌نویسی شود"><i class="fa-solid fa-clock"></i> <span id="last-saved-text">هنوز ذخیره نشده</span></div>
      </div>
      <div class="flex gap-2 order-2 sm:order-3">
        <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--s2)] text-[var(--text2)] border border-[var(--b1)] hover:text-[var(--text)] transition-all" id="btn-draft" onclick="submitForm('draft')">
          <i class="fa-solid fa-floppy-disk"></i> <span class="hidden sm:inline">ذخیره پیش‌نویس</span>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--accent)] text-[var(--text)] hover:bg-[var(--accent-hover)] transition-all" id="btn-next" onclick="nextStep()">
          <span class="hidden sm:inline">مرحله بعد</span> <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button type="button" class="inline-flex items-center gap-2 px-3.5 md:px-5 h-10 rounded-xl text-xs font-bold cursor-pointer bg-[var(--green)] text-[var(--text)] hover:bg-[var(--green-hover)] transition-all" id="btn-submit" onclick="submitForm('active')" style="display:none;">
          <i class="fa-solid fa-check"></i> <span class="hidden sm:inline">ثبت نهایی محصول</span>
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
            'id' => $m->model_id,
            'name' => $m->name,
            'provider' => $m->provider,
        ];
    });
@endphp
<script>
const AI_MODELS = @json($aiModelsForJs);


let cur = 1;

/* ── Stepper بازطراحی‌شده: هر Step یکی از سه حالت Active / Completed / Pending دارد ──
   (پیاده‌سازی UI طبق سند شماره ۱ — رفتار Validation-Gate واقعی در فاز معماری کلی اضافه می‌شود) */
function goStep(n) {
  if(n < 1 || n > 5) return;
  cur = n;

  for(let i=1; i<=5; i++) {
    const p = document.getElementById('panel-'+i);
    const tab = document.getElementById('step-tab-'+i);
    const num = document.getElementById('step-num-'+i);
    const label = tab.querySelector('.step-label');
    const title = tab.querySelector('.step-title');

    // ریست همه کلاس‌های حالت قبل از اعمال حالت جدید
    tab.classList.remove('bg-[var(--accent)]/10','border-[var(--accent)]/25','shadow-[0_0_12px_-2px_rgba(160,122,245,0.35)]','bg-[var(--green)]/5','border-[var(--green)]/20');
    num.classList.remove('border-[var(--accent)]','bg-[var(--accent)]/15','text-[var(--accent)]','border-[var(--green)]','bg-[var(--green)]/15','text-[var(--green)]','border-[var(--b2)]','text-[var(--text3)]','scale-105');
    label.classList.remove('text-[var(--accent)]','text-[var(--green)]','text-[var(--text3)]');
    title.classList.remove('text-[var(--text)]','text-[var(--text2)]');

    if (i === n) {
      // Active
      p.classList.remove('hidden'); p.classList.add('block');
      tab.classList.add('bg-[var(--accent)]/10','border-[var(--accent)]/25','shadow-[0_0_12px_-2px_rgba(160,122,245,0.35)]');
      num.classList.add('border-[var(--accent)]','bg-[var(--accent)]/15','text-[var(--accent)]','scale-105');
      num.innerHTML = num.dataset.num;
      label.classList.add('text-[var(--accent)]');
      title.classList.add('text-[var(--text)]');
    } else {
      p.classList.remove('block'); p.classList.add('hidden');
      if (i < n) {
        // Completed
        tab.classList.add('bg-[var(--green)]/5','border-[var(--green)]/20');
        num.classList.add('border-[var(--green)]','bg-[var(--green)]/15','text-[var(--green)]');
        num.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
        label.classList.add('text-[var(--green)]');
        title.classList.add('text-[var(--text2)]');
      } else {
        // Pending
        num.classList.add('border-[var(--b2)]','text-[var(--text3)]');
        num.innerHTML = num.dataset.num;
        label.classList.add('text-[var(--text3)]');
        title.classList.add('text-[var(--text2)]');
      }
    }
  }

  // رنگ خط اتصال بین Stepها (دسکتاپ و موبایل) بر اساس عبور از آن مرحله
  [1,2,3,4].forEach(idx => {
    ['conn-'+idx, 'conn-'+idx+'-m'].forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.remove('bg-[var(--b1)]','bg-[var(--green)]/40');
      el.classList.add(n > idx ? 'bg-[var(--green)]/40' : 'bg-[var(--b1)]');
    });
  });

  document.getElementById('btn-prev').style.display = n === 1 ? 'none' : 'inline-flex';
  document.getElementById('btn-next').style.display = n === 5 ? 'none' : 'inline-flex';
  document.getElementById('btn-submit').style.display = n === 5 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = n;
  window.scrollTo({top: 0, behavior: 'smooth'});

  lazyInitStep(n); // NEW: Step Lazy Loading — مقداردهی سنگین هر Step فقط در اولین بازدید آن
  ProductCreateState.ui.currentStep = n;
}

/* ── NEW Step Lazy Loading System — کامپوننت‌های سنگین (Searchable Select و پیش‌نمایش فرم) Step ۲ و ۳
   فقط در اولین باری که کاربر وارد آن Step می‌شود مقداردهی می‌شوند، نه در بارگذاری اولیه صفحه ── */
const lazyInitedSteps = new Set([1]); // Step ۱ همراه بارگذاری صفحه مقداردهی می‌شود
function lazyInitStep(n) {
  if (lazyInitedSteps.has(n)) return;
  lazyInitedSteps.add(n);
  const panel = document.getElementById('panel-' + n);
  if (panel) initSearchables(panel);
  if (n === 2 && typeof onPrimaryModelChange === 'function') onPrimaryModelChange(); // پایپ‌لاین هوش مصنوعی
  if (n === 3 && typeof refreshFormPreview === 'function') refreshFormPreview();     // ورودی و متغیرها
  if (n === 5 && typeof refreshFinalSummary === 'function') refreshFinalSummary();   // بازبینی نهایی
}

/* ══════════════════════════════════════════════════════════════════
   معماری کلی و رفتار سیستم (بخش چهارم سند) — همه‌چیز فقط UI/کلاینت است:
   هیچ Route/API جدیدی صدا زده نمی‌شود؛ submitForm() و Validation واقعی سرور دست‌نخورده می‌مانند.
   ══════════════════════════════════════════════════════════════════ */

/* ── State Management سبک: ProductCreateState فقط یک آینه از وضعیت UI است ── */
const ProductCreateState = { ui: { currentStep: 1 }, validation: { 1: true, 2: true, 3: true, 4: true, 5: true } };

/* ── Validation سیستم: حداقل فیلدهای الزامی هر Step (مطابق Validation واقعی کنترلر) ──
   توجه: الزامی‌بودن فایل‌ها (مثل Thumbnail) اینجا چک نمی‌شود چون در حالت تکثیر محصول ممکن است
   از قبل موجود باشد؛ تصمیم نهایی همیشه با Validation واقعی سمت سرور است. */
const STEP_REQUIRED_FIELDS = {
  1: [ ['name_fa', 'نام فارسی'], ['name_en', 'نام انگلیسی'], ['slug', 'آدرس URL'], ['category', 'دسته‌بندی'] ],
  2: [ ['primary_model', 'مدل اصلی هوش مصنوعی'], ['prompt_template', 'متن پرامپت'] ],
  3: [], // ورودی و متغیرها: فیلد الزامی خاصی ندارد
  4: [], // خروجی و قیمت: همه مقادیر پیش‌فرض دارند
  5: [], // بازبینی نهایی: صرفاً مرور است
};

function fieldValue(name) {
  const els = document.getElementsByName(name);
  if (!els.length) return '';
  if (els.length > 1 && els[0].type === 'radio') {
    const checked = Array.from(els).find(e => e.checked);
    return checked ? checked.value : '';
  }
  return (els[0].value || '').trim();
}

function validateStep(n) {
  const missing = [];
  (STEP_REQUIRED_FIELDS[n] || []).forEach(([name, label]) => {
    if (!fieldValue(name)) missing.push({ name, label });
  });
  ProductCreateState.validation[n] = missing.length === 0;
  return missing;
}

function showValidationSummary(missing) {
  const box = document.getElementById('validation-summary');
  const list = document.getElementById('validation-summary-list');
  if (!missing.length) { box.classList.add('hidden'); list.innerHTML = ''; return; }
  list.innerHTML = missing.map(m => `<li><a href="#" class="underline" onclick="focusField('${m.name}'); return false;">${m.label}</a></li>`).join('');
  box.classList.remove('hidden');
}

function focusField(name) {
  const el = document.getElementsByName(name)[0];
  if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); el.classList.add('border-[var(--red)]'); setTimeout(() => el.classList.remove('border-[var(--red)]'), 2000); }
}

/* پیمایش بین Stepها با گیت اعتبارسنجی — فقط برای حرکت روبه‌جلو؛ بازگشت همیشه آزاد است */
function attemptGoStep(n) {
  if (n <= cur) { goStep(n); return; }
  const missing = validateStep(cur);
  if (missing.length) { showValidationSummary(missing); refreshStepValidityDots(); return; }
  showValidationSummary([]);
  goStep(n);
}

function nextStep() {
  if (cur >= 5) return;
  attemptGoStep(cur + 1);
}
function prevStep() { if(cur > 1) { showValidationSummary([]); goStep(cur - 1); } }

/* NEW: Step Validation Indicator — نقطه قرمز کوچک روی Stepهایی که سر زده شده‌اند ولی ناقص‌اند */
function refreshStepValidityDots() {
  for (let i = 1; i <= 5; i++) {
    const tab = document.getElementById('step-tab-' + i);
    if (!tab) continue;
    let dot = tab.querySelector('.step-invalid-dot');
    const invalid = ProductCreateState.validation[i] === false;
    if (invalid && !dot) {
      dot = document.createElement('span');
      dot.className = 'step-invalid-dot w-1.5 h-1.5 rounded-full bg-[var(--red)] absolute top-2 left-2';
      tab.style.position = 'relative';
      tab.appendChild(dot);
    } else if (!invalid && dot) {
      dot.remove();
    }
  }
}

/* ── Form Persistence Layer (NEW) — پیش‌نویس محلی در LocalStorage همین مرورگر ──
   شامل فایل‌ها نمی‌شود (محدودیت فنی LocalStorage)؛ فقط فیلدهای متنی/انتخابی. */
const DRAFT_STORAGE_KEY = 'vatan-product-draft-{{ $duplicateFrom ? $duplicateFrom->id : "new" }}';

function snapshotFormFields() {
  const form = document.getElementById('real-product-form');
  const data = {};
  Array.from(form.elements).forEach(el => {
    if (!el.name || el.type === 'file' || el.type === 'hidden') return;
    if (el.type === 'checkbox') { data[el.name] = data[el.name] || []; if (el.checked) data[el.name].push(el.value || '1'); return; }
    if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; return; }
    data[el.name] = el.value;
  });
  return data;
}

function saveDraftToLocalStorage() {
  try {
    const snap = snapshotFormFields();
    localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify({ savedAt: Date.now(), fields: snap }));
    updateLastSavedLabel(Date.now());
  } catch (e) { /* فضای LocalStorage پر یا در دسترس نیست — بی‌صدا رد می‌شویم */ }
}

function updateLastSavedLabel(ts) {
  const el = document.getElementById('last-saved-text');
  if (!el) return;
  if (!ts) { el.textContent = 'هنوز ذخیره نشده'; return; }
  const d = new Date(ts);
  el.textContent = 'آخرین ذخیره خودکار: ' + d.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
}

function restoreLocalDraft() {
  try {
    const raw = localStorage.getItem(DRAFT_STORAGE_KEY);
    if (!raw) return;
    const { fields } = JSON.parse(raw);
    const form = document.getElementById('real-product-form');
    Object.keys(fields).forEach(name => {
      const els = form.querySelectorAll(`[name="${CSS.escape(name)}"], [name="${CSS.escape(name)}[]"]`);
      if (!els.length) return;
      if (els[0].type === 'checkbox') { Array.from(els).forEach(el => el.checked = (fields[name] || []).includes(el.value)); return; }
      if (els[0].type === 'radio') { Array.from(els).forEach(el => el.checked = (el.value === fields[name])); Array.from(els).forEach(el => el.dispatchEvent(new Event('change', {bubbles:true}))); return; }
      els[0].value = fields[name];
      els[0].dispatchEvent(new Event('input', { bubbles: true }));
      els[0].dispatchEvent(new Event('change', { bubbles: true }));
    });
    document.getElementById('draft-restore-banner').classList.add('hidden');
    if (typeof refreshFinalSummary === 'function') refreshFinalSummary();
  } catch (e) { /* داده خراب یا ناسازگار — نادیده گرفته می‌شود */ }
}

function discardLocalDraft() {
  localStorage.removeItem(DRAFT_STORAGE_KEY);
  document.getElementById('draft-restore-banner').classList.add('hidden');
}

function isFormEssentiallyEmpty() {
  return !fieldValue('name_fa') && !fieldValue('name_en');
}

function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const v = inp.value.trim();
  if (!v) return;

  const chip = document.createElement('span');
  chip.className = 'inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]';
  chip.innerHTML = `${v}<button type="button" class="text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1" onclick="this.parentElement.remove()">×</button>`;
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

/* اگر مدیر Slug را دستی ویرایش کرد، دیگر با تغییر نام انگلیسی به‌صورت خودکار جایگزین نشود */
let slugManuallyEdited = false;
function lockSlugManual() { slugManuallyEdited = true; }
function autoSlug(el) {
  if (slugManuallyEdited) return;
  const s = el.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  document.getElementById('slug-input').value = s;
}

/* ── کامپوننت مستقل و قابل استفاده مجدد: Searchable Select ──
   یک <select> واقعی (با همان name فعلی) پنهان و در پس‌زمینه نگه‌داشته می‌شود تا هیچ رفتار
   Backend/Submit تغییر نکند؛ یک UI جستجوپذیر روی آن سوار می‌شود و مقدار select اصلی را همگام نگه می‌دارد. */
function makeSearchable(select) {
  if (!select) return;
  if (select.dataset.searchableReady === '1') { refreshSearchable(select); return; }
  select.dataset.searchableReady = '1';

  const wrap = document.createElement('div');
  wrap.className = 'relative searchable-select-wrap';
  select.parentNode.insertBefore(wrap, select);
  select.classList.add('hidden');
  wrap.appendChild(select);

  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'ss-btn bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full flex items-center justify-between gap-2 focus:border-[var(--accent)] outline-none transition-colors';
  wrap.appendChild(btn);

  const panel = document.createElement('div');
  panel.className = 'ss-panel hidden absolute z-30 mt-1 w-full bg-[var(--s2)] border border-[var(--b1)] rounded-lg shadow-xl overflow-hidden';
  panel.innerHTML = '<div class="p-1.5 border-b border-[var(--b1)]"><input type="text" placeholder="جستجو..." class="ss-search w-full bg-[var(--s1)] border border-[var(--b1)] rounded-md p-1.5 text-[11px] text-[var(--text)] outline-none focus:border-[var(--accent)]"></div><div class="ss-list overflow-y-auto max-h-44"></div>';
  wrap.appendChild(panel);

  function renderList(filter) {
    const list = panel.querySelector('.ss-list');
    list.innerHTML = '';
    Array.from(select.options).forEach(opt => {
      if (!opt.value && !opt.textContent.trim()) return;
      if (filter && opt.textContent.toLowerCase().indexOf(filter.toLowerCase()) === -1) return;
      const item = document.createElement('div');
      item.className = 'px-3 py-2 text-xs cursor-pointer transition-colors ' + (opt.value === select.value ? 'bg-[var(--accent)]/15 text-[var(--accent)]' : 'text-[var(--text2)] hover:bg-[var(--accent)]/10 hover:text-[var(--text)]');
      item.textContent = opt.textContent;
      item.onclick = () => {
        select.value = opt.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateButtonLabel();
        panel.classList.add('hidden');
      };
      list.appendChild(item);
    });
    if (!list.children.length) list.innerHTML = '<div class="px-3 py-3 text-[11px] text-[var(--text3)] text-center">موردی یافت نشد</div>';
  }

  function updateButtonLabel() {
    const opt = select.options[select.selectedIndex];
    btn.innerHTML = '<span class="truncate">' + (opt && opt.value ? opt.textContent : (select.disabled ? 'ابتدا دسته را انتخاب کنید' : 'انتخاب کنید')) + '</span><i class="fa-solid fa-chevron-down text-[10px] text-[var(--text3)]"></i>';
    btn.disabled = select.disabled;
    btn.classList.toggle('opacity-40', select.disabled);
    btn.classList.toggle('cursor-not-allowed', select.disabled);
  }

  btn.addEventListener('click', () => {
    if (select.disabled) return;
    const willOpen = panel.classList.contains('hidden');
    document.querySelectorAll('.ss-panel').forEach(p => p.classList.add('hidden'));
    if (willOpen) { renderList(''); panel.classList.remove('hidden'); panel.querySelector('.ss-search').focus(); }
  });
  panel.querySelector('.ss-search').addEventListener('input', e => renderList(e.target.value));
  document.addEventListener('click', e => { if (!wrap.contains(e.target)) panel.classList.add('hidden'); });

  updateButtonLabel();
}

function refreshSearchable(select) {
  if (!select) return;
  const wrap = select.closest('.searchable-select-wrap');
  if (!wrap) return;
  const btn = wrap.querySelector('.ss-btn');
  const opt = select.options[select.selectedIndex];
  btn.innerHTML = '<span class="truncate">' + (opt && opt.value ? opt.textContent : (select.disabled ? 'ابتدا دسته را انتخاب کنید' : 'انتخاب کنید')) + '</span><i class="fa-solid fa-chevron-down text-[10px] text-[var(--text3)]"></i>';
  btn.disabled = select.disabled;
  btn.classList.toggle('opacity-40', select.disabled);
  btn.classList.toggle('cursor-not-allowed', select.disabled);
}

function initSearchables(root) {
  (root || document).querySelectorAll('select[data-searchable]').forEach(makeSearchable);
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
/* fbIdx با تعداد ردیف‌های از قبل پرشده (در حالت تکثیر محصول) شروع می‌شود تا برچسب اولویت درست بماند */
let fbIdx = {{ count(old('fallback_models', optional($duplicateFrom)->fallback_models ?? [])) }};
function addFallback() {
  if (!AI_MODELS.length) {
    alert('ابتدا حداقل یک مدل هوش مصنوعی فعال در سیستم ثبت کنید.');
    return;
  }
  // قانون UX سند: بدون انتخاب مدل اصلی، افزودن Fallback مجاز نیست
  const primary = document.getElementById('primary-model-select');
  if (primary && !primary.value) {
    alert('ابتدا مدل اصلی را انتخاب کنید، سپس مدل جایگزین اضافه کنید.');
    return;
  }

  fbIdx++;
  const div = document.createElement('div');
  div.className = 'fallback-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-center gap-3';
  div.id = `fb-row-${fbIdx}`;
  div.draggable = true;

  const options = AI_MODELS.map(m =>
    `<option value="${m.id}">${m.name} (${m.provider})</option>`
  ).join('');

  div.innerHTML = `
    <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 fb-drag-handle hidden md:block" title="برای تغییر اولویت بکشید"></i>
    <div class="flex md:hidden flex-col gap-0.5 shrink-0">
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
    </div>
    <span class="fb-priority text-[10px] font-mono text-[var(--text3)] w-14 shrink-0">اولویت ${fbIdx + 1}</span>
    <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] flex-1 fallback-select-item" data-searchable>
      ${options}
    </select>
    <label class="relative w-8 h-[18px] shrink-0 block cursor-pointer" title="NEW Enable/Disable — فقط UI، برنامه‌نویسی شود">
      <input type="checkbox" class="sr-only peer" checked>
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
    </label>
    <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg shrink-0" onclick="document.getElementById('fb-row-${fbIdx}').remove(); renumberFallbacks();">حذف</button>
  `;
  document.getElementById('fallback-list').appendChild(div);
  makeSearchable(div.querySelector('select'));
  wireFallbackDrag(div);
}

/* شماره اولویت‌ها را بعد از حذف/جابجایی یک ردیف، دوباره محاسبه می‌کند (فقط نمایشی) */
function renumberFallbacks() {
  document.querySelectorAll('#fallback-list .fallback-row, #fallback-list > div').forEach((row, idx) => {
    const p = row.querySelector('.fb-priority');
    if (p) p.textContent = 'اولویت ' + (idx + 2);
  });
}

/* Drag & Drop برای تغییر ترتیب مدل‌های جایگزین — ترتیب DOM دقیقاً همان ترتیبی است که submitForm() به‌عنوان اولویت ارسال می‌کند */
/* NEW: Sort Mode موبایل — چون Drag & Drop نیتیو HTML5 روی لمسی کار نمی‌کند، دکمه بالا/پایین جایگزین آن می‌شود */
function moveFallbackRow(btn, dir) {
  const row = btn.closest('.fallback-row');
  const sib = dir === 'up' ? row.previousElementSibling : row.nextElementSibling;
  if (!sib) return;
  if (dir === 'up') row.parentNode.insertBefore(row, sib); else row.parentNode.insertBefore(sib, row);
  renumberFallbacks();
}
function moveInputFieldRow(btn, dir) {
  const row = btn.closest('.input-field-card');
  const sib = dir === 'up' ? row.previousElementSibling : row.nextElementSibling;
  if (!sib) return;
  if (dir === 'up') row.parentNode.insertBefore(row, sib); else row.parentNode.insertBefore(sib, row);
  refreshFormPreview();
}

let dragFbEl = null;
function wireFallbackDrag(row) {
  row.addEventListener('dragstart', () => { dragFbEl = row; row.classList.add('opacity-40'); });
  row.addEventListener('dragend', () => { row.classList.remove('opacity-40'); renumberFallbacks(); });
  row.addEventListener('dragover', e => e.preventDefault());
  row.addEventListener('drop', e => {
    e.preventDefault();
    if (!dragFbEl || dragFbEl === row) return;
    const list = document.getElementById('fallback-list');
    const rows = Array.from(list.children);
    if (rows.indexOf(dragFbEl) < rows.indexOf(row)) list.insertBefore(dragFbEl, row.nextSibling);
    else list.insertBefore(dragFbEl, row);
    renumberFallbacks();
  });
}

function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.substring(0, s) + v + ta.value.substring(e);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
}

/* fieldIdx با تعداد ردیف‌های از قبل پرشده (در حالت تکثیر محصول) شروع می‌شود */
const INPUT_FIELD_TYPES = ['text','textarea','number','image_upload','file_upload','select','radio','checkbox'];
let fieldIdx = {{ count(old('input_schema', optional($duplicateFrom)->input_schema ?? [])) }};
function addInputField() {
  fieldIdx++;
  const div = document.createElement('div');
  div.className = 'input-field-card bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 input-schema-row';
  div.id = `field-row-${fieldIdx}`;
  div.draggable = true;

  const typeOptions = INPUT_FIELD_TYPES.map(t => `<option value="${t}">${t}</option>`).join('');

  div.innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center">
      <div class="flex items-center gap-1.5 md:col-span-1">
        <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 hidden md:block" title="برای تغییر ترتیب بکشید"></i>
        <div class="flex md:hidden flex-col gap-0.5 shrink-0">
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
        </div>
        <input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] ltr text-left schema-id w-full" placeholder="field_id">
      </div>
      <input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-label" placeholder="برچسب فارسی">
      <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-type" data-searchable>
        ${typeOptions}
      </select>
      <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-required">
        <option value="1">اجباری</option>
        <option value="0">اختیاری</option>
      </select>
      <div class="flex items-center gap-1.5 justify-end">
        <button type="button" class="text-xs text-[var(--text2)] bg-[var(--text)]/5 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').querySelector('.field-advanced').classList.toggle('hidden')" title="ویرایش تنظیمات پیشرفته"><i class="fa-solid fa-pen"></i></button>
        <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="document.getElementById('field-row-${fieldIdx}').remove(); refreshFormPreview();">حذف</button>
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
  `;
  document.getElementById('input-fields-list').appendChild(div);
  makeSearchable(div.querySelector('.schema-type'));
  wireInputFieldDrag(div);
  div.querySelectorAll('.schema-id, .schema-label, .schema-required').forEach(el => el.addEventListener('input', refreshFormPreview));
  div.querySelector('.schema-required').addEventListener('change', refreshFormPreview);
  refreshFormPreview();
}

let dragFieldEl = null;
function wireInputFieldDrag(row) {
  row.addEventListener('dragstart', () => { dragFieldEl = row; row.classList.add('opacity-40'); });
  row.addEventListener('dragend', () => { row.classList.remove('opacity-40'); refreshFormPreview(); });
  row.addEventListener('dragover', e => e.preventDefault());
  row.addEventListener('drop', e => {
    e.preventDefault();
    if (!dragFieldEl || dragFieldEl === row) return;
    const list = document.getElementById('input-fields-list');
    const rows = Array.from(list.children);
    if (rows.indexOf(dragFieldEl) < rows.indexOf(row)) list.insertBefore(dragFieldEl, row.nextSibling);
    else list.insertBefore(dragFieldEl, row);
    refreshFormPreview();
  });
}

/* NEW Preview Form — رندر زنده فرم نهایی کاربر بر اساس فیلدهای تعریف‌شده (فقط UI) */
function refreshFormPreview() {
  const box = document.getElementById('user-form-preview');
  if (!box) return;
  const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
  if (!rows.length) { box.innerHTML = '<div class="text-[11px] text-[var(--text3)] text-center py-4">هنوز فیلد ورودی‌ای تعریف نشده است</div>'; return; }
  let html = '';
  rows.forEach(row => {
    const label = row.querySelector('.schema-label')?.value || row.querySelector('.schema-id')?.value || 'بدون عنوان';
    const required = row.querySelector('.schema-required')?.value === '1';
    html += `<div class="flex flex-col gap-1 mb-2.5"><label class="text-[11px] text-[var(--text2)]">${label}${required ? ' <span class=\"text-[var(--red)]\">*</span>' : ''}</label><div class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text3)]">— ورودی کاربر —</div></div>`;
  });
  box.innerHTML = html;
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
    sub.innerHTML = '<option value="">ابتدا دسته را انتخاب کنید</option>';
    sub.disabled = true;
    refreshSearchable(sub);
    return;
  }
  sub.disabled = false;
  sub.innerHTML = '<option value="">زیردسته ندارد</option>';
  subcats[main].forEach(s => {
    sub.innerHTML += `<option value="${s}">${s}</option>`;
  });
  refreshSearchable(sub);
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

  // Button Loading State — هنگام ارسال واقعی فرم به سرور (رفتار بصری، جلوگیری از دوبار کلیک)
  setButtonsLoading(true, statusValue);
  form.submit();
}

/* NEW: Loading State یکپارچه برای دکمه‌های پایین صفحه در لحظه ارسال فرم */
function setButtonsLoading(isLoading, which) {
  const draftBtn = document.getElementById('btn-draft');
  const submitBtn = document.getElementById('btn-submit');
  [draftBtn, submitBtn].forEach(btn => { if (btn) btn.disabled = isLoading; });
  if (!isLoading) return;
  const target = which === 'draft' ? draftBtn : submitBtn;
  if (target) {
    target.classList.add('opacity-70', 'pointer-events-none');
    const icon = target.querySelector('i');
    if (icon) icon.className = 'fa-solid fa-spinner fa-spin';
  }
}

/* ── Global Error Handler UI (NEW) — نمایش غیرمسدودکننده خطاهای عمومی سمت کلاینت ── */
let globalErrorTimer;
function showGlobalError(message) {
  const box = document.getElementById('global-error-toast');
  const text = document.getElementById('global-error-text');
  if (!box || !text) return;
  text.textContent = message;
  box.classList.remove('hidden');
  clearTimeout(globalErrorTimer);
  globalErrorTimer = setTimeout(hideGlobalError, 6000);
}
function hideGlobalError() {
  const box = document.getElementById('global-error-toast');
  if (box) box.classList.add('hidden');
}
window.addEventListener('offline', () => showGlobalError('اتصال اینترنت قطع شد — تغییرات فقط به‌صورت محلی ذخیره می‌شوند.'));

/* ── NEW Role-Based UI Locking (فقط نمایشی) — پیش‌فرض Admin یعنی هیچ قفلی اعمال نمی‌شود ── */
function applyRolePreview(role) {
  const lockable = document.querySelectorAll('[data-role-lockable], .field-advanced, input[name^="new_"], select[name^="new_"], textarea[name^="new_"]');
  const locked = role === 'viewer';
  const readonly = role === 'editor';
  lockable.forEach(el => {
    el.closest('div')?.classList.toggle('opacity-40', locked);
    el.closest('div')?.classList.toggle('pointer-events-none', locked);
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
      el.disabled = locked || (readonly && el.type !== 'radio' && el.type !== 'checkbox');
    }
  });
  if (locked) showGlobalError('نقش «Viewer» فقط برای پیش‌نمایش است — فیلدهای پیشرفته غیرفعال شدند (نمایشی).');
}

/* ── مقداردهی اولیه فرم هنگام بارگذاری صفحه (فقط برای حالت تکثیر محصول) ──
   زیردسته باید بعد از پرشدن گزینه‌های آن (updateSubcat) انتخاب شود، و
   وضعیت فعال/غیرفعال بودن باکس هزینه‌ی کردیت باید با pricing_model هماهنگ شود. */
document.addEventListener('DOMContentLoaded', function () {
  goStep(1); // مقداردهی اولیه حالت Stepper (Active/Completed/Pending) طبق طراحی جدید

  const catMain = document.getElementById('cat-main');
  const wantedSub = @json(old('subcategory', optional($duplicateFrom)->subcategory));
  if (catMain && catMain.value) {
    updateSubcat();
    if (wantedSub) {
      const subSel = document.getElementById('cat-sub');
      const match = Array.from(subSel.options).find(o => o.value === wantedSub);
      if (match) subSel.value = wantedSub;
    }
  } else {
    const sub = document.getElementById('cat-sub');
    if (sub) sub.disabled = true;
  }

  const pricingSelect = document.querySelector('select[name="pricing_model"]');
  if (pricingSelect) toggleCreditCost(pricingSelect);

  // فعال‌سازی UI جستجوپذیر روی Selectهای Step ۱ (بعد از پرشدن مقادیر اولیه) — Step ۲/۳ با Lazy Loading مقداردهی می‌شوند
  initSearchables(document.getElementById('panel-1'));

  // ── فعال‌سازی Form Persistence Layer + Auto Save Draft (NEW — فقط UI/LocalStorage) ──
  if (isFormEssentiallyEmpty() && localStorage.getItem(DRAFT_STORAGE_KEY)) {
    document.getElementById('draft-restore-banner').classList.remove('hidden');
  }
  let autosaveDebounce;
  document.getElementById('real-product-form').addEventListener('input', () => {
    clearTimeout(autosaveDebounce);
    autosaveDebounce = setTimeout(saveDraftToLocalStorage, 1500);
  });
  setInterval(saveDraftToLocalStorage, 10000); // Auto Save Draft — هر ۱۰ ثانیه طبق سند

  refreshStepValidityDots();
});
</script>
@endsection