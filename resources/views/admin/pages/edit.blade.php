@extends('layouts.admin')
@section('title', 'تنظیمات ' . $page->name_fa . ' — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="page-settings-heading">
      <div class="page-settings-heading__title">
        <a href="{{ route('admin.pages.index') }}" class="icon-action-btn" aria-label="بازگشت"><i class="fa-solid fa-arrow-right"></i></a>
        <div>
          <div class="page-settings-title-row"><h1>{{ $page->name_fa }}</h1><span dir="ltr">{{ $page->name_en }}</span><b>نسخه {{ $page->version }}</b></div>
          <p>{{ $definition['description'] }}</p>
        </div>
      </div>
      <div class="page-settings-actions">
        @if($advancedUrl)<a href="{{ $advancedUrl }}" class="btn-pro btn-pro-ghost no-underline"><i class="fa-solid fa-layer-group"></i> مدیریت تخصصی محتوا</a>@endif
        @if($previewUrl)<a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn-pro btn-pro-ghost no-underline"><i class="fa-regular fa-eye"></i> پیش‌نمایش</a>@endif
        @if($previewUrl)<button type="button" class="icon-action-btn page-copy-link" data-copy-page-link="{{ $previewUrl }}" title="کپی سریع لینک صفحه" aria-label="کپی سریع لینک صفحه"><i class="fa-solid fa-link"></i></button>@endif
        <span class="copy-link-feedback" id="copy-link-feedback" role="status" aria-live="polite"></span>
      </div>
    </div>

    @if(session('success'))<div class="page-alert is-success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    @if($errors->any())
      <div class="page-alert is-danger"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>اطلاعات ذخیره نشد.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif

    <div class="page-settings-layout">
      <form method="POST" action="{{ route('admin.pages.update', $page) }}" enctype="multipart/form-data" class="page-settings-main" id="page-settings-form">
        @csrf @method('PUT')

        <section class="content-card settings-section">
          <div class="settings-section__head"><span><i class="fa-solid fa-sliders"></i></span><div><h2>تنظیمات عمومی</h2><p>هویت صفحه و وضعیت نمایش آن در سایت</p></div></div>
          <div class="settings-grid cols-2">
            <label class="field-pro"><span>نام فارسی <em class="required-badge">ضروری</em></span><input name="name_fa" value="{{ old('name_fa', $page->name_fa) }}" required maxlength="150"></label>
            <label class="field-pro"><span>نام انگلیسی</span><input name="name_en" value="{{ old('name_en', $page->name_en) }}" maxlength="150" dir="ltr"></label>
            <label class="field-pro"><span>عنوان نمایشی <em class="required-badge">ضروری</em></span><input name="title" value="{{ old('title', $page->title) }}" required maxlength="180"></label>
            <label class="field-pro"><span>وضعیت انتشار <em class="required-badge">ضروری</em></span><select name="status" id="page-status" required><option value="draft" @selected(old('status',$page->status)==='draft')>پیش‌نویس</option><option value="published" @selected(old('status',$page->status)==='published')>منتشرشده</option><option value="scheduled" @selected(old('status',$page->status)==='scheduled')>زمان‌بندی‌شده</option><option value="archived" @selected(old('status',$page->status)==='archived')>بایگانی‌شده</option></select></label>
            <label class="field-pro span-2"><span>توضیح کوتاه صفحه</span><textarea name="subtitle" rows="3" maxlength="500">{{ old('subtitle', $page->subtitle) }}</textarea></label>
            <label class="field-pro span-2 schedule-field" @if(old('status',$page->status)!=='scheduled') hidden @endif><span>زمان انتشار <em class="required-badge">ضروری در حالت زمان‌بندی</em></span><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $page->scheduled_at?->format('Y-m-d\TH:i')) }}"></label>
          </div>
        </section>

        <section class="content-card settings-section">
          <div class="settings-section__head"><span><i class="fa-brands fa-google"></i></span><div><h2>سئو و اشتراک‌گذاری</h2><p>اطلاعات موتورهای جستجو و شبکه‌های اجتماعی</p></div></div>
          <div class="settings-grid cols-2">
            <label class="field-pro"><span>عنوان سئو <i class="fa-brands fa-google google-field-icon" title="مرتبط با گوگل"></i></span><input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" maxlength="180"><small>پیشنهاد: حداکثر ۶۰ نویسه</small></label>
            <label class="field-pro"><span>آدرس اصلی صفحه <i class="fa-brands fa-google google-field-icon" title="مرتبط با گوگل"></i></span><input name="canonical_url" value="{{ old('canonical_url', $page->canonical_url) }}" dir="ltr" placeholder="https://..."></label>
            <label class="field-pro span-2"><span>توضیحات سئو <i class="fa-brands fa-google google-field-icon" title="مرتبط با گوگل"></i></span><textarea name="meta_description" rows="3" maxlength="500">{{ old('meta_description', $page->meta_description) }}</textarea><small>پیشنهاد: بین ۱۲۰ تا ۱۶۰ نویسه</small></label>
            <label class="field-pro span-2"><span>کلمات کلیدی <i class="fa-brands fa-google google-field-icon" title="مرتبط با گوگل"></i></span><input name="meta_keywords" value="{{ old('meta_keywords', implode('، ', $page->meta_keywords ?? [])) }}" placeholder="هوش مصنوعی، ساخت عکس، وطن"><small>کلمات را با ویرگول جدا کنید.</small></label>
            <label class="field-pro"><span>تصویر اشتراک‌گذاری <i class="fa-brands fa-google google-field-icon" title="مرتبط با گوگل"></i></span><input type="file" name="og_image" accept="image/jpeg,image/png,image/webp"><small>حداکثر ۴ مگابایت؛ نسبت پیشنهادی ۱.۹۱ به ۱</small></label>
            @if($page->og_image)<div class="og-preview"><img src="{{ Storage::disk('public')->url($page->og_image) }}" alt="تصویر اشتراک‌گذاری"><span>تصویر فعلی</span></div>@endif
          </div>
        </section>

        <section class="content-card settings-section">
          <div class="settings-section__head"><span><i class="fa-solid fa-display"></i></span><div><h2>نمایش و محتوا</h2><p>رفتار عمومی و ظرفیت نمایش اطلاعات صفحه</p></div></div>
          <div class="settings-grid cols-2">
            <label class="field-pro"><span>تم صفحه <em class="required-badge">ضروری</em></span><select name="theme" required><option value="system" @selected(old('theme',$page->display('theme','system'))==='system')>هماهنگ با تنظیم کاربر</option><option value="light" @selected(old('theme',$page->display('theme'))==='light')>روشن</option><option value="dark" @selected(old('theme',$page->display('theme'))==='dark')>تیره</option></select></label>
            @if(in_array($page->key, ['explore','trends'], true))
              <label class="field-pro"><span>تعداد آیتم در هر صفحه <em class="required-badge">ضروری</em></span><input type="number" name="items_per_page" min="6" max="100" value="{{ old('items_per_page',$page->content('items_per_page',24)) }}" required></label>
            @else
              <input type="hidden" name="items_per_page" value="{{ $page->content('items_per_page',24) }}">
            @endif
          </div>
          <div class="toggle-grid">
            @if($page->key !== 'profile')
              <label class="setting-toggle"><input type="checkbox" name="show_page_title" value="1" @checked(old('show_page_title',$page->content('show_page_title',true)))><span><i class="fa-solid fa-heading"></i><b>نمایش عنوان صفحه</b><small>عنوان و توضیح بالای محتوا نمایش داده شود.</small></span></label>
            @else
              <input type="hidden" name="show_page_title" value="1">
            @endif
            @if(in_array($page->key, ['home','explore','trends'], true))
              <label class="setting-toggle"><input type="checkbox" name="show_search" value="1" @checked(old('show_search',$page->content('show_search',false)))><span><i class="fa-solid fa-magnifying-glass"></i><b>نمایش جستجو</b><small>بخش جستجوی اصلی این صفحه نمایش داده شود.</small></span></label>
            @else
              <input type="hidden" name="show_search" value="0">
            @endif
            <label class="setting-toggle"><input type="checkbox" name="show_footer" value="1" @checked(old('show_footer',$page->display('show_footer',true)))><span><i class="fa-solid fa-window-minimize"></i><b>نمایش فوتر</b><small>فوتر عمومی سایت در این صفحه نمایش داده شود.</small></span></label>
          </div>
        </section>

        <section class="content-card settings-section">
          <div class="settings-section__head"><span><i class="fa-solid fa-shield-halved"></i></span><div><h2>دسترسی و ایمنی</h2><p>کنترل مشاهده صفحه و حالت نگهداری</p></div></div>
          <div class="toggle-grid">
            <label class="setting-toggle"><input type="checkbox" name="is_indexable" value="1" @checked(old('is_indexable',$page->is_indexable))><span><i class="fa-brands fa-google"></i><b>ایندکس در گوگل</b><small>اجازه ثبت این صفحه در نتایج جستجوی گوگل.</small></span></label>
            <label class="setting-toggle"><input type="checkbox" name="requires_auth" value="1" @checked(old('requires_auth',$page->requires_auth))><span><i class="fa-solid fa-user-lock"></i><b>نیازمند ورود کاربر</b><small>کاربر مهمان ابتدا به صفحه ورود هدایت شود.</small></span></label>
            <label class="setting-toggle is-warning"><input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode',$page->maintenance_mode))><span><i class="fa-solid fa-screwdriver-wrench"></i><b>حالت نگهداری</b><small>صفحه برای کاربران موقتاً بسته شود.</small></span></label>
          </div>
          <label class="field-pro maintenance-message"><span>پیام حالت نگهداری</span><textarea name="maintenance_message" rows="2" maxlength="500">{{ old('maintenance_message',$page->maintenance_message) }}</textarea></label>
        </section>

        <section class="content-card settings-submit">
          <label class="field-pro"><span>یادداشت تغییرات</span><input name="change_note" maxlength="255" placeholder="مثلاً: اصلاح عنوان و تنظیمات سئو"></label>
          <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره همه تنظیمات</button>
        </section>
      </form>

      <aside class="page-settings-aside">
        <section class="content-card aside-card">
          <div class="aside-card__head"><h3>وضعیت صفحه</h3><span class="page-status-badge is-{{ $page->status }}">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده','archived'=>'بایگانی‌شده'][$page->status] }}</span></div>
          <div class="aside-facts"><span><small>مسیر</small><b dir="ltr">{{ $definition['path'] }}</b></span><span><small>نسخه</small><b>{{ $page->version }}</b></span><span><small>آخرین تغییر</small><b>{{ $page->updated_at?->diffForHumans() }}</b></span><span><small>ویرایشگر</small><b>{{ $page->updatedBy?->name ?? 'سیستم' }}</b></span></div>
          <div class="quick-status-actions">
            @if($page->status !== 'published')<form method="POST" action="{{ route('admin.pages.publish',$page) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="published"><button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> انتشار فوری</button></form>@endif
            @if($page->status === 'published')<form method="POST" action="{{ route('admin.pages.publish',$page) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="draft"><button class="btn-pro btn-pro-ghost" type="submit"><i class="fa-solid fa-file-pen"></i> تبدیل به پیش‌نویس</button></form>@endif
          </div>
        </section>

        <section class="content-card aside-card">
          <div class="aside-card__head"><h3>تاریخچه نسخه‌ها</h3><span>{{ $revisions->count() }} تغییر اخیر</span></div>
          <div class="revision-list">
            @forelse($revisions as $revision)
              <div class="revision-item"><div><b>نسخه {{ $revision->version }}</b><small>{{ $revision->change_note ?: 'بروزرسانی تنظیمات' }}</small><em>{{ $revision->admin?->name ?? 'سیستم' }} · {{ $revision->created_at->diffForHumans() }}</em></div>@if($revision->version !== $page->version)<form method="POST" action="{{ route('admin.pages.revisions.restore',[$page,$revision]) }}" onsubmit="return confirm('این نسخه بازیابی شود؟');">@csrf<button type="submit" class="icon-action-btn" title="بازیابی"><i class="fa-solid fa-rotate-left"></i></button></form>@endif</div>
            @empty<div class="revision-empty">پس از اولین ذخیره، سابقه نسخه‌ها اینجا نمایش داده می‌شود.</div>@endforelse
          </div>
        </section>
      </aside>
    </div>
  </div>
</main>
@endsection

@push('styles')
<style>
  .page-settings-heading,.page-settings-heading__title,.page-settings-actions,.page-settings-title-row,.settings-section__head,.settings-submit,.aside-card__head,.revision-item { display:flex; align-items:center; }
  .page-settings-heading { justify-content:space-between; gap:14px; margin-bottom:16px; }
  .page-settings-heading__title { gap:10px; min-width:0; }
  .page-settings-heading__title > div { min-width:0; }
  .page-settings-title-row { gap:7px; flex-wrap:wrap; }
  .page-settings-title-row h1 { margin:0; color:var(--text-h); font-size:19px; font-weight:900; }
  .page-settings-title-row span,.page-settings-title-row b { padding:3px 6px; border:1px solid var(--border); border-radius:6px; color:var(--text-soft); background:var(--input-bg); font-size:8px; }
  .page-settings-heading p { margin:4px 0 0; color:var(--text-soft); font-size:10px; }
  .page-settings-actions { gap:7px; flex-wrap:wrap; }
  .page-copy-link { width:34px; height:34px; flex:0 0 34px; color:var(--primary); }
  .copy-link-feedback { color:var(--success); font-size:8px; font-weight:800; }
  .page-alert { display:flex; align-items:flex-start; gap:8px; margin-bottom:12px; padding:10px 12px; border:1px solid; border-radius:9px; font-size:10px; }
  .page-alert.is-success { color:var(--success); border-color:color-mix(in srgb,var(--success) 28%,transparent); background:color-mix(in srgb,var(--success) 7%,var(--card-bg)); }
  .page-alert.is-danger { color:var(--danger); border-color:color-mix(in srgb,var(--danger) 28%,transparent); background:color-mix(in srgb,var(--danger) 7%,var(--card-bg)); }
  .page-alert strong,.page-alert ul { margin:0; }.page-alert ul { padding-right:16px; }
  .page-settings-layout { display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:12px; align-items:start; }
  .page-settings-main,.page-settings-aside { display:grid; gap:12px; min-width:0; }
  .settings-section { padding:14px; }
  .settings-section__head { gap:9px; padding-bottom:11px; margin-bottom:12px; border-bottom:1px solid var(--border); }
  .settings-section__head > span { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; color:var(--primary); background:var(--primary-l); }
  .settings-section__head h2 { margin:0; color:var(--text-h); font-size:12px; font-weight:900; }
  .settings-section__head p { margin:3px 0 0; color:var(--text-soft); font-size:8.5px; }
  .settings-grid { display:grid; gap:10px; }.settings-grid.cols-2 { grid-template-columns:repeat(2,minmax(0,1fr)); }.settings-grid .span-2 { grid-column:1/-1; }
  .field-pro { display:grid; gap:5px; min-width:0; color:var(--text-main); font-size:9.5px; font-weight:800; }
  .required-badge { display:inline-flex; margin-right:4px; padding:2px 5px; border-radius:5px; color:var(--danger); background:color-mix(in srgb,var(--danger) 8%,transparent); font-size:6.5px; font-style:normal; font-weight:900; vertical-align:middle; }
  .google-field-icon { margin-right:4px; color:var(--primary); font-size:9px; }
  .field-pro small { color:var(--text-soft); font-size:7.5px; font-weight:500; }
  .field-pro input,.field-pro select,.field-pro textarea { width:100%; min-height:36px; padding:8px 9px; border:1px solid var(--border); border-radius:8px; outline:none; color:var(--text-main); background:var(--input-bg); font-family:inherit; font-size:10px; font-weight:500; }
  .field-pro textarea { resize:vertical; line-height:1.8; }.field-pro input:focus,.field-pro select:focus,.field-pro textarea:focus { border-color:var(--primary); background:var(--card-bg); }
  .toggle-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin-top:11px; }
  .setting-toggle { position:relative; display:block; cursor:pointer; }.setting-toggle input { position:absolute; opacity:0; pointer-events:none; }
  .setting-toggle > span { display:grid; min-height:86px; padding:10px; border:1px solid var(--border); border-radius:9px; background:var(--input-bg); transition:border-color .15s,background .15s; }
  .setting-toggle i { color:var(--primary); font-size:12px; }.setting-toggle b { margin-top:7px; color:var(--text-h); font-size:9.5px; }.setting-toggle small { margin-top:3px; color:var(--text-soft); font-size:7.5px; line-height:1.6; }
  .setting-toggle input:checked + span { border-color:var(--primary); background:var(--primary-l); }.setting-toggle input:checked + span::after { content:'فعال'; align-self:end; justify-self:end; color:var(--primary); font-size:7px; font-weight:900; }
  .maintenance-message { margin-top:10px; }.og-preview { display:flex; align-items:center; gap:8px; }.og-preview img { width:90px; height:48px; border:1px solid var(--border); border-radius:7px; object-fit:cover; }.og-preview span { color:var(--text-soft); font-size:8px; }
  .settings-submit { justify-content:space-between; gap:12px; padding:12px; }.settings-submit .field-pro { flex:1; }.settings-submit button { min-width:165px; justify-content:center; }
  .aside-card { padding:12px; }.aside-card__head { justify-content:space-between; gap:8px; padding-bottom:9px; margin-bottom:9px; border-bottom:1px solid var(--border); }.aside-card__head h3 { margin:0; color:var(--text-h); font-size:10.5px; }.aside-card__head > span { color:var(--text-soft); font-size:7.5px; }
  .page-status-badge { padding:3px 6px; border-radius:6px; font-weight:900; }.page-status-badge.is-published { color:var(--success); background:color-mix(in srgb,var(--success) 9%,transparent); }.page-status-badge:not(.is-published) { color:var(--warning); background:color-mix(in srgb,var(--warning) 9%,transparent); }
  .aside-facts { display:grid; gap:2px; }.aside-facts span { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:7px 0; border-bottom:1px solid var(--border); }.aside-facts small { color:var(--text-soft); font-size:8px; }.aside-facts b { overflow:hidden; color:var(--text-main); font-size:8.5px; text-overflow:ellipsis; white-space:nowrap; }
  .quick-status-actions { display:grid; gap:6px; margin-top:10px; }.quick-status-actions form,.quick-status-actions button { width:100%; }.quick-status-actions button { justify-content:center; }
  .revision-list { display:grid; gap:7px; }.revision-item { align-items:flex-start; justify-content:space-between; gap:6px; padding:8px; border:1px solid var(--border); border-radius:8px; background:var(--input-bg); }.revision-item > div { display:grid; min-width:0; }.revision-item b { color:var(--text-h); font-size:8.5px; }.revision-item small { overflow:hidden; margin-top:2px; color:var(--text-main); font-size:7.5px; text-overflow:ellipsis; white-space:nowrap; }.revision-item em { margin-top:3px; color:var(--text-soft); font-size:7px; font-style:normal; }.revision-empty { padding:14px 6px; color:var(--text-soft); font-size:8px; line-height:1.8; text-align:center; }
  [hidden] { display:none !important; }
  @media (max-width:1100px) { .page-settings-layout { grid-template-columns:1fr; }.page-settings-aside { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:720px) { .page-settings-heading { align-items:stretch; flex-direction:column; }.page-settings-actions { justify-content:flex-start; }.settings-grid.cols-2,.toggle-grid,.page-settings-aside { grid-template-columns:1fr; }.settings-grid .span-2 { grid-column:auto; }.settings-submit { align-items:stretch; flex-direction:column; }.settings-submit button { width:100%; }.page-settings-actions .btn-pro { flex:1; justify-content:center; } }
</style>
@endpush

@section('scripts')
<script>
  (function () {
    const status = document.getElementById('page-status');
    const schedule = document.querySelector('.schedule-field');
    const scheduledInput = schedule?.querySelector('input');
    function syncScheduleField() {
      const scheduled = status?.value === 'scheduled';
      if (schedule) schedule.hidden = !scheduled;
      if (scheduledInput) scheduledInput.required = scheduled;
    }
    status?.addEventListener('change', syncScheduleField);
    syncScheduleField();

    document.querySelector('[data-copy-page-link]')?.addEventListener('click', async function () {
      const feedback = document.getElementById('copy-link-feedback');
      const link = this.dataset.copyPageLink || '';
      try {
        await navigator.clipboard.writeText(link);
      } catch (error) {
        const textarea = document.createElement('textarea');
        textarea.value = link;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
      }
      this.classList.add('is-active');
      if (feedback) feedback.textContent = 'لینک کپی شد';
      window.setTimeout(() => {
        this.classList.remove('is-active');
        if (feedback) feedback.textContent = '';
      }, 1800);
    });
  })();
</script>
@endsection
