@extends('layouts.admin')
@section('title', 'تنظیمات ثبت محصول — وطن استودیو')

@push('styles')
<style>
.tp-page{padding:24px;display:grid;gap:18px}.tp-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}.tp-title{font-size:21px;font-weight:900;color:var(--text-h)}.tp-subtitle{font-size:12px;color:var(--text-soft);margin-top:6px}.tp-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,.75fr);gap:18px;align-items:start}.tp-card{background:var(--card-bg);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow-card);overflow:hidden}.tp-card-head{padding:16px 18px;border-bottom:1px solid var(--divider);display:flex;justify-content:space-between;align-items:center;gap:10px}.tp-card-title{font-size:14px;font-weight:800;color:var(--text-h)}.tp-card-copy{font-size:10px;color:var(--text-soft)}.tp-form{padding:18px;display:grid;gap:12px}.tp-field{display:grid;gap:6px}.tp-field label{font-size:11px;font-weight:700;color:var(--text-main)}.tp-input{width:100%;min-height:42px;border-radius:10px;border:1px solid var(--border);background:var(--input-bg);color:var(--text-h);padding:10px 12px;font-size:12px;outline:none}.tp-input:focus{border-color:var(--primary);background:var(--card-bg)}textarea.tp-input{min-height:170px;line-height:1.9;resize:vertical;direction:rtl}.tp-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}.tp-submit{height:40px;padding:0 15px;border:0;border-radius:10px;background:var(--primary);color:var(--accent);font-size:11px;font-weight:900;cursor:pointer}.tp-info{padding:18px;display:grid;gap:11px}.tp-info-row{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--divider);padding-bottom:10px;font-size:11px}.tp-info-row:last-child{border-bottom:0;padding-bottom:0}.tp-info-label{color:var(--text-soft)}.tp-info-value{color:var(--text-h);font-weight:800;direction:ltr}.tp-alert{padding:11px 14px;border-radius:11px;border:1px solid;font-size:12px}.tp-alert.success{color:var(--success);border-color:var(--success-m);background:var(--success-l)}.tp-alert.error{color:var(--danger);border-color:var(--danger-m);background:var(--danger-l)}.tp-table-wrap{overflow:auto}.tp-table{width:100%;border-collapse:collapse;min-width:760px}.tp-table th,.tp-table td{text-align:right;padding:12px 15px;border-bottom:1px solid var(--divider);font-size:11px;color:var(--text-main);vertical-align:top}.tp-table th{font-weight:800;color:var(--text-soft);background:var(--input-bg)}.tp-table td strong{color:var(--text-h)}.tp-badge{display:inline-flex;align-items:center;border:1px solid var(--border);border-radius:99px;padding:4px 8px;font-size:10px;color:var(--text-soft);background:var(--input-bg)}.tp-badge.active{color:var(--success);border-color:var(--success-m);background:var(--success-l)}.tp-perms{display:flex;gap:6px;flex-wrap:wrap}.tp-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.tp-check{display:flex;align-items:center;gap:7px;font-size:10px;color:var(--text-main)}.tp-check input{accent-color:var(--primary)}.tp-muted{font-size:10px;line-height:1.8;color:var(--text-soft)}@media(max-width:1050px){.tp-grid{grid-template-columns:1fr}}@media(max-width:600px){.tp-page{padding:14px}.tp-checks{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content" dir="rtl">
    <div class="tp-page">
      <div class="tp-head"><div><h1 class="tp-title">تنظیمات ثبت محصول با بات</h1><p class="tp-subtitle">مدیریت اتصال بات، پرامپت‌های هوش مصنوعی، مدیران مجاز و گزارش ثبت محصولات</p></div><a class="btn-pro btn-pro-ghost" href="{{ route('admin.products') }}"><i class="fa-solid fa-arrow-right"></i> بازگشت به محصولات</a></div>
      @if(session('success'))<div class="tp-alert success">{{ session('success') }}</div>@endif
      @if($errors->any())<div class="tp-alert error">{{ $errors->first() }}</div>@endif

      <div class="tp-grid">
        <section class="tp-card">
          <div class="tp-card-head"><h2 class="tp-card-title">پرامپت‌های مادر</h2><span class="tp-card-copy">فقط رهبر پنل می‌تواند تغییر دهد</span></div>
          <form class="tp-form" method="POST" action="{{ route('admin.products.settings.prompts') }}">
            @csrf @method('PUT')
            <div class="tp-field"><label for="metadata_prompt">پرامپت اسم، توضیحات، دسته‌بندی و برچسب‌ها</label><textarea class="tp-input" id="metadata_prompt" name="metadata_prompt" required>{{ old('metadata_prompt', $metadataPrompt) }}</textarea></div>
            <div class="tp-field"><label for="prompt_optimizer">پرامپت اصلاح و بهینه‌سازی پرامپت محصول</label><textarea class="tp-input" id="prompt_optimizer" name="prompt_optimizer" required>{{ old('prompt_optimizer', $optimizerPrompt) }}</textarea></div>
            <p class="tp-muted">بات عکس را برای هوش مصنوعی ارسال نمی‌کند؛ عکس فقط به محصول متصل می‌شود و این پرامپت‌ها متن مدیر را به اطلاعات قابل ذخیره تبدیل می‌کنند.</p>
            <div class="tp-actions"><button class="tp-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره پرامپت‌ها</button></div>
          </form>
        </section>

        <section class="tp-card">
          <div class="tp-card-head"><h2 class="tp-card-title">اتصال بات</h2><span class="tp-badge active">محافظت‌شده</span></div>
          <div class="tp-info">
            <div class="tp-info-row"><span class="tp-info-label">نام کاربری بات</span><span class="tp-info-value">{{ $botUsername ?: 'تنظیم نشده' }}</span></div>
            <div class="tp-info-row"><span class="tp-info-label">توکن بات</span><span class="tp-info-value">{{ $botToken }}</span></div>
            <div class="tp-info-row"><span class="tp-info-label">وضعیت مسیر ثبت محصول</span><span class="tp-info-value">فعال</span></div>
            <p class="tp-muted">توکن کامل هرگز در پنل نمایش داده نمی‌شود و از تنظیمات امن سرویس خوانده می‌شود.</p>
          </div>
        </section>
      </div>

      <section class="tp-card">
        <div class="tp-card-head"><h2 class="tp-card-title">مدیران بات و سطح دسترسی</h2><span class="tp-card-copy">حساب فعلی با شناسه تلگرام `217979733` دسترسی کامل دارد</span></div>
        <div class="tp-table-wrap"><table class="tp-table"><thead><tr><th>مدیر</th><th>شناسه تلگرام</th><th>وضعیت</th><th>تعداد ثبت</th><th>دسترسی‌ها</th><th>ذخیره</th></tr></thead><tbody>
          @forelse($managers as $manager)
            @php $managerPermissions = array_merge(\App\Models\TelegramProductManager::defaultPermissions(), (array) $manager->permissions); @endphp
            <tr><form method="POST" action="{{ route('admin.products.settings.managers.update', $manager) }}">@csrf @method('PUT')
              <td><input class="tp-input" name="name" value="{{ $manager->name }}" required></td>
              <td><input class="tp-input" name="telegram_id" value="{{ $manager->telegram_id }}" dir="ltr" required></td>
              <td><label class="tp-check"><input type="checkbox" name="is_active" value="1" @checked($manager->is_active)> فعال</label></td>
              <td><strong>{{ number_format($counts[$manager->id] ?? 0) }}</strong></td>
              <td><div class="tp-checks">@foreach(['create_product'=>'ثبت محصول','edit_product'=>'ویرایش محصول','publish_product'=>'انتشار','manage_prompts'=>'مدیریت پرامپت','manage_bot_settings'=>'تنظیمات بات','view_reports'=>'گزارش‌ها'] as $key=>$label)<label class="tp-check"><input type="checkbox" name="permissions[{{ $key }}]" value="1" @checked($managerPermissions[$key] ?? false)> {{ $label }}</label>@endforeach</div></td>
              <td><button class="tp-submit" type="submit">ذخیره</button></td>
            </form></tr>
          @empty<tr><td colspan="6">مدیر باتی ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div>
      </section>

      <section class="tp-card"><div class="tp-card-head"><h2 class="tp-card-title">افزودن مدیر بات</h2><span class="tp-card-copy">شناسه عددی کاربر را از تلگرام وارد کنید</span></div><form class="tp-form" method="POST" action="{{ route('admin.products.settings.managers.store') }}"><div class="tp-grid"><div class="tp-field"><label>نام مدیر</label><input class="tp-input" name="name" required></div><div class="tp-field"><label>شناسه تلگرام</label><input class="tp-input" name="telegram_id" dir="ltr" required></div></div><div class="tp-checks">@foreach(['create_product'=>'ثبت محصول','edit_product'=>'ویرایش محصول','publish_product'=>'انتشار','manage_prompts'=>'مدیریت پرامپت','manage_bot_settings'=>'تنظیمات بات','view_reports'=>'گزارش‌ها'] as $key=>$label)<label class="tp-check"><input type="checkbox" name="permissions[{{ $key }}]" value="1" @checked(in_array($key, ['create_product','edit_product','view_reports'], true))> {{ $label }}</label>@endforeach</div><div class="tp-actions">@csrf<button class="tp-submit" type="submit">افزودن مدیر</button></div></form></section>

      <section class="tp-card"><div class="tp-card-head"><h2 class="tp-card-title">آخرین ثبت‌های انجام‌شده با بات</h2><span class="tp-card-copy">مالکیت هر محصول در این جدول ثبت می‌شود</span></div><div class="tp-table-wrap"><table class="tp-table"><thead><tr><th>محصول</th><th>کد محصول</th><th>ثبت‌کننده</th><th>وضعیت</th><th>زمان</th></tr></thead><tbody>@forelse($registrations as $registration)<tr><td><strong>{{ $registration->product?->name_fa ?: 'محصول حذف شده' }}</strong></td><td dir="ltr">{{ $registration->product?->product_code ?: '—' }}</td><td>{{ $registration->manager?->name ?: 'نامشخص' }}<br><span class="tp-card-copy" dir="ltr">{{ $registration->telegram_id }}</span></td><td><span class="tp-badge active">{{ $registration->status === 'active' ? 'منتشرشده' : 'پیش‌نویس' }}</span></td><td>{{ optional($registration->created_at)->format('Y/m/d H:i') }}</td></tr>@empty<tr><td colspan="5">هنوز محصولی از بات ثبت نشده است.</td></tr>@endforelse</tbody></table></div></section>
    </div>
  </div>
</main>
@endsection
