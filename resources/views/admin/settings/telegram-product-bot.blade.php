@extends('layouts.admin')
@section('title', 'بات ثبت محصول — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/telegram-product-settings.css') }}?v={{ filemtime(public_path('admin/css/telegram-product-settings.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content" dir="rtl">
    <div class="telegram-product-settings-page">
      @if(session('success'))
        <div class="tps-alert tps-alert--success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
      @endif
      @if($errors->any())
        <div class="tps-alert tps-alert--error"><i class="fa-solid fa-triangle-exclamation"></i><span>{{ $errors->first() }}</span></div>
      @endif

      <header class="tps-hero">
        <div>
          <div class="tps-eyebrow"><i class="fa-solid fa-box-open"></i> تنظیمات تلگرام</div>
          <h1>بات ثبت محصول</h1>
          <p>اتصال بات اختصاصی مدیران برای ثبت محصول، پردازش هوش مصنوعی و بررسی وضعیت ساخت را از یک محل مدیریت کنید.</p>
        </div>
        <span class="tps-hero__badge"><i class="fa-solid fa-shield-halved"></i> فقط رهبر پنل</span>
      </header>

      <div class="tps-stats">
        <div class="tps-stat"><span class="tps-stat__icon is-primary"><i class="fa-solid fa-user-shield"></i></span><span><small>مدیران ثبت‌شده</small><strong>{{ number_format($stats['managers']) }}</strong></span></div>
        <div class="tps-stat"><span class="tps-stat__icon is-success"><i class="fa-solid fa-circle-check"></i></span><span><small>مدیران فعال</small><strong>{{ number_format($stats['active_managers']) }}</strong></span></div>
        <div class="tps-stat"><span class="tps-stat__icon is-warning"><i class="fa-solid fa-spinner"></i></span><span><small>فرآیندهای باز</small><strong>{{ number_format($stats['active_drafts']) }}</strong></span></div>
        <div class="tps-stat"><span class="tps-stat__icon is-info"><i class="fa-solid fa-bolt"></i></span><span><small>رویدادهای ثبت‌شده</small><strong>{{ number_format($stats['events']) }}</strong></span></div>
      </div>

      <div class="tps-grid">
        <section class="tps-card">
          <div class="tps-card__head"><div><h2>اتصال و رفتار بات</h2><p>مقادیر امن سرویس فقط به‌صورت ماسک‌شده نمایش داده می‌شوند.</p></div><i class="fa-brands fa-telegram tps-card__head-icon"></i></div>
          <div class="tps-config-grid">
            <div class="tps-config-item"><small>توکن بات</small><strong dir="ltr">{{ $botToken }}</strong></div>
            <div class="tps-config-item"><small>کلید وب‌هوک</small><strong dir="ltr">{{ $webhookSecret }}</strong></div>
            <div class="tps-config-item"><small>مدل هوش مصنوعی</small><strong dir="ltr">{{ $aiModel }}</strong></div>
            <div class="tps-config-item"><small>حداکثر تصاویر هر محصول</small><strong>{{ number_format($maxImages) }} تصویر</strong></div>
            <div class="tps-config-item tps-config-item--wide"><small>نشانی وب‌هوک</small><strong class="tps-ltr" dir="ltr">{{ $webhookUrl }}</strong></div>
          </div>
        </section>

        <section class="tps-card tps-card--guide">
          <div class="tps-card__head"><div><h2>مسیر فعال بات</h2><p>نمایش خلاصه‌ی تجربه‌ی مدیر در بات</p></div><i class="fa-solid fa-route tps-card__head-icon"></i></div>
          <ol class="tps-flow"><li><b>۱</b><span>شروع فرآیند ثبت محصول</span></li><li><b>۲</b><span>دریافت یک یا چند تصویر</span></li><li><b>۳</b><span>دریافت توضیح و تحلیل هوش مصنوعی</span></li><li><b>۴</b><span>بررسی و ثبت نهایی محصول</span></li></ol>
        </section>
      </div>

      <section class="tps-card">
        <div class="tps-card__head"><div><h2>مدیران مجاز بات</h2><p>فقط شناسه‌های فعال این فهرست می‌توانند از بات ثبت محصول استفاده کنند.</p></div><span class="tps-badge">{{ number_format($stats['active_managers']) }} فعال</span></div>
        <div class="tps-table-wrap"><table class="tps-table"><thead><tr><th>نام مدیر</th><th>شناسه تلگرام</th><th>ثبت‌ها</th><th>وضعیت</th><th>ذخیره</th></tr></thead><tbody>
          @forelse($managers as $manager)
            <tr><form method="POST" action="{{ route('admin.settings.telegram.product-bot.managers.update', $manager) }}">@csrf @method('PUT')
              <td><input class="tps-input" name="name" value="{{ $manager->name }}" required></td>
              <td><input class="tps-input tps-ltr" name="telegram_id" value="{{ $manager->telegram_id }}" dir="ltr" required></td>
              <td><strong>{{ number_format($manager->drafts_count) }}</strong></td>
              <td><label class="tps-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($manager->is_active)> فعال</label></td>
              <td><button class="btn-pro btn-pro-primary tps-small-button" type="submit">ذخیره</button></td>
            </form></tr>
          @empty
            <tr><td colspan="5" class="tps-empty">هنوز مدیری برای بات ثبت محصول اضافه نشده است.</td></tr>
          @endforelse
        </tbody></table></div>
      </section>

      <section class="tps-card">
        <div class="tps-card__head"><div><h2>افزودن مدیر بات</h2><p>شناسه عددی کاربر را از تلگرام وارد کنید.</p></div><i class="fa-solid fa-user-plus tps-card__head-icon"></i></div>
        <form class="tps-manager-form" method="POST" action="{{ route('admin.settings.telegram.product-bot.managers.store') }}">
          @csrf
          <label><span>نام مدیر</span><input class="tps-input" name="name" value="{{ old('name') }}" required placeholder="نام نمایشی"></label>
          <label><span>شناسه تلگرام</span><input class="tps-input tps-ltr" name="telegram_id" value="{{ old('telegram_id') }}" dir="ltr" required placeholder="217979733"></label>
          <label class="tps-check tps-manager-active"><input type="checkbox" name="is_active" value="1" checked> مدیر فعال باشد</label>
          <button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-plus"></i> افزودن مدیر</button>
        </form>
      </section>

      <section class="tps-card">
        <div class="tps-card__head"><div><h2>آخرین فرآیندهای ثبت محصول</h2><p>وضعیت آخرین درخواست‌های ارسال‌شده از بات.</p></div><span class="tps-badge">نمایش آخرین ۱۲ مورد</span></div>
        <div class="tps-table-wrap"><table class="tps-table"><thead><tr><th>مدیر</th><th>وضعیت</th><th>توضیح</th><th>زمان</th></tr></thead><tbody>
          @forelse($drafts as $draft)
            <tr><td>{{ $draft->manager?->name ?: 'نامشخص' }}</td><td><span class="tps-state tps-state--{{ $draft->state }}">{{ match($draft->state) { 'awaiting_image' => 'در انتظار تصویر', 'awaiting_description' => 'در انتظار توضیح', 'processing' => 'در حال پردازش', 'review' => 'در انتظار بررسی', 'duplicate' => 'تکراری', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده', 'failed' => 'ناموفق', default => $draft->state ?: 'نامشخص' } }}</span></td><td>{{ IlluminateSupportStr::limit($draft->description ?: 'بدون توضیح', 90) }}</td><td>{{ optional($draft->created_at)->format('Y/m/d H:i') }}</td></tr>
          @empty
            <tr><td colspan="4" class="tps-empty">هنوز فرآیندی در دیتابیس ثبت نشده است.</td></tr>
          @endforelse
        </tbody></table></div>
      </section>
    </div>
  </div>
</main>
@endsection
