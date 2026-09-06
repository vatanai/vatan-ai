@extends('layouts.admin')
@section('title', 'افزودن پروایدر — مدل‌های هوش مصنوعی')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
      <div>
        <h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">افزودن پروایدر</h1>
        <p class="text-xs text-[var(--text-soft)] m-0">کلید اتصال در پایگاه‌داده به‌صورت رمزنگاری‌شده ذخیره می‌شود.</p>
      </div>
      <a href="{{ route('admin.ai-models.providers') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت به پروایدرها</a>
    </div>

    <section class="content-card provider-form-card">
      <div class="provider-form-icon"><i class="fa-solid fa-plug-circle-plus"></i></div>
      <div class="mb-5"><h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">اتصال سرویس جدید</h2><p class="text-[11px] text-[var(--text-soft)] mt-1 mb-0">یکی از سرویس‌های پشتیبانی‌شده را انتخاب کنید و اطلاعات اتصال را وارد کنید.</p></div>
      <form method="POST" action="{{ route('admin.ai-models.provider-settings') }}" class="space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <label class="text-[11px] text-[var(--text-soft)]">پروایدر
            <select name="provider" class="input-pro mt-1.5 w-full" required>
              @foreach($providers as $provider)
                <option value="{{ $provider }}">{{ match($provider) { 'liara' => 'Liara', 'openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate', default => $provider } }}</option>
              @endforeach
            </select>
          </label>
          <label class="text-[11px] text-[var(--text-soft)]">کلید API
            <input class="input-pro mt-1.5 w-full" type="password" name="api_key" autocomplete="new-password" required placeholder="کلید را فقط در همین صفحه وارد کنید">
          </label>
          <label class="text-[11px] text-[var(--text-soft)]">Base URL اختیاری
            <input class="input-pro mt-1.5 w-full ltr text-left" dir="ltr" type="url" name="base_url" placeholder="https://..."><span class="field-help">اگر سرویس از مسیر جایگزین یا Gateway استفاده می‌کند، اینجا وارد کنید.</span>
          </label>
          <label class="text-[11px] text-[var(--text-soft)]">Webhook Secret اختیاری
            <input class="input-pro mt-1.5 w-full" type="password" name="webhook_secret" autocomplete="new-password" placeholder="فقط برای سرویس‌های دارای Webhook"><span class="field-help">این مقدار نیز رمزنگاری‌شده ذخیره می‌شود.</span>
          </label>
          <label class="text-[11px] text-[var(--text-soft)]">زمان انتظار (ثانیه)
            <input class="input-pro mt-1.5 w-full ltr text-left" dir="ltr" type="number" name="timeout" min="5" max="3600" value="120" required>
          </label>
          <input type="hidden" name="max_retries" value="2">
        </div>
        <label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-soft)] cursor-pointer"><input type="checkbox" name="webhook_enabled" value="1" checked> وب‌هوک فعال باشد</label>
        <div class="provider-security-note"><i class="fa-solid fa-shield-halved"></i><span>کلید در صفحه نمایش داده نمی‌شود، در فایل کد ذخیره نمی‌شود و مقدار فعلی فقط به‌صورت ناقص نمایش داده خواهد شد.</span></div>
        <div class="flex items-center justify-end gap-2 pt-3 border-t border-[var(--border)]"><a href="{{ route('admin.ai-models.providers') }}" class="btn-pro btn-pro-ghost">انصراف</a><button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-lock ml-1"></i> ذخیره امن اتصال</button></div>
      </form>
    </section>
  </div>
</main>
@endsection

@push('styles')
<style>
  .provider-form-card { max-width:760px; margin:0 auto; padding:22px; }
  .provider-form-icon { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px; border-radius:11px; background:var(--primary-l); color:var(--primary); }
  .field-help { display:block; margin-top:4px; color:var(--text-soft); font-size:9px; }
  .provider-security-note { display:flex; align-items:flex-start; gap:8px; padding:10px; border:1px solid color-mix(in srgb,var(--success) 28%,var(--border)); border-radius:9px; background:color-mix(in srgb,var(--success) 7%,transparent); color:var(--text-soft); font-size:10px; line-height:1.8; }
  .provider-security-note i { color:var(--success); margin-top:3px; }
</style>
@endpush
