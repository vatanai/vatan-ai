@extends('layouts.admin')

@section('title', 'گزارش همکاری در فروش — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/user-gallery.css') }}?v={{ filemtime(public_path('admin/css/user-gallery.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    <div class="user-gallery-report-head">
      <div>
        <span class="user-gallery-report-eyebrow"><i class="fa-solid fa-chart-line"></i> گزارش رسمی عملکرد رفرال</span>
        <h1>گزارش همکاری در فروش {{ trim(($user->name ?? '').' '.($user->last_name ?? '')) ?: 'کاربر' }}</h1>
        <p>این گزارش شامل ورودی‌های ثبت‌شده، مسیر تبدیل، خریدها و پاداش‌های پرداخت‌شده تا این لحظه است.</p>
      </div>
      <div class="user-gallery-report-actions">
        <button type="button" class="user-gallery-report-button primary" data-copy-referral-report><i class="fa-regular fa-copy"></i> کپی خلاصه برای ارسال</button>
        <button type="button" class="user-gallery-report-button" onclick="window.print()"><i class="fa-solid fa-print"></i> چاپ / ذخیره PDF</button>
        <a href="{{ route('admin.users.gallery.referral-report.export', $user) }}" class="user-gallery-report-button"><i class="fa-solid fa-file-csv"></i> خروجی CSV</a>
        <a href="{{ route('admin.users.gallery.show', $user) }}" class="user-gallery-report-button"><i class="fa-solid fa-arrow-right"></i> بازگشت به گالری</a>
      </div>
    </div>

    <section class="user-gallery-report-card">
      <div class="user-gallery-section-head"><div><h2>خلاصه عملکرد</h2><p>مقایسه‌ی کلیک با نتیجه‌ی واقعی کاربران</p></div><span class="user-gallery-section-count">به‌روزرسانی هنگام بازکردن گزارش</span></div>
      <div class="user-gallery-report-kpis">
        <div><span>کلیک کل</span><strong>{{ number_format($report['summary']['clicks']) }}</strong><small>{{ number_format($report['summary']['unique_clicks']) }} بازدیدکننده یکتا</small></div>
        <div class="success"><span>ثبت‌نام رفرالی</span><strong>{{ number_format($report['summary']['registrations']) }}</strong><small>{{ $report['summary']['registration_rate'] }}٪ نرخ تبدیل کلیک به ثبت‌نام</small></div>
        <div class="info"><span>خرید موفق</span><strong>{{ number_format($report['summary']['purchases']) }}</strong><small>از کاربران معرفی‌شده</small></div>
        <div class="warning"><span>ساخت مخاطبان</span><strong>{{ number_format($report['summary']['outputs']) }}</strong><small>تصویر یا ویدیوی ثبت‌شده</small></div>
      </div>
    </section>

    <section class="user-gallery-report-card">
      <div class="user-gallery-section-head"><div><h2>پاداش‌ها و منافع</h2><p>مبالغ پرداخت‌شده و مواردی که هنوز در انتظار تسویه یا بررسی هستند</p></div></div>
      <div class="user-gallery-report-rewards">
        <div><span>پاداش پرداخت‌شده همکار</span><strong>{{ number_format($report['rewards']['own_paid']) }} اعتبار</strong><small>{{ number_format($report['rewards']['own_pending']) }} اعتبار در انتظار</small></div>
        <div class="success"><span>پاداش پرداخت‌شده مخاطبان</span><strong>{{ number_format($report['rewards']['invitee_paid']) }} اعتبار</strong><small>{{ number_format($report['rewards']['invitee_pending']) }} اعتبار در انتظار</small></div>
        <div class="info"><span>جمع پاداش پرداخت‌شده</span><strong>{{ number_format($report['rewards']['combined_paid']) }} اعتبار</strong><small>همکار و کاربران معرفی‌شده</small></div>
        <div class="warning"><span>کمیسیون نقدی</span><strong>{{ number_format($report['rewards']['commission_paid']) }} تومان</strong><small>{{ number_format($report['rewards']['commission_pending']) }} تومان در انتظار تسویه</small></div>
      </div>
    </section>

    <section class="user-gallery-report-card">
      <div class="user-gallery-section-head"><div><h2>عملکرد لینک‌ها</h2><p>هر لینک به‌صورت مستقل قابل پیگیری است؛ لینک عادی و لینک محصول جدا شده‌اند.</p></div></div>
      <div class="user-gallery-report-table-wrap">
        <table class="user-gallery-report-table">
          <thead><tr><th>لینک / مقصد</th><th>وضعیت</th><th>کلیک</th><th>ثبت‌نام</th><th>خرید</th><th>ساخت</th><th>نشانی</th></tr></thead>
          <tbody>
            @forelse($report['links'] as $link)
              <tr>
                <td><strong>{{ $link['label'] }}</strong><small>{{ $link['label'] === 'لینک عادی' ? 'ورود به صفحه‌ی اصلی سایت' : 'ورود مستقیم به محصول' }}</small></td>
                <td><span class="user-gallery-report-status {{ ($link['active'] ?? true) ? 'active' : 'inactive' }}">{{ ($link['active'] ?? true) ? 'فعال' : 'غیرفعال' }}</span></td>
                <td>{{ number_format($link['clicks']) }}</td>
                <td>{{ number_format($link['registrations']) }}</td>
                <td>{{ number_format($link['purchases']) }}</td>
                <td>{{ number_format($link['outputs']) }}</td>
                <td><a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="user-gallery-report-url" dir="ltr">بازکردن لینک <i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>
              </tr>
            @empty
              <tr><td colspan="7" class="user-gallery-report-empty">برای این کاربر هنوز لینک رفرال یا بازدیدی ثبت نشده است.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="user-gallery-report-card">
      <div class="user-gallery-section-head"><div><h2>جزئیات کاربران معرفی‌شده</h2><p>وضعیت دعوت، خرید و پاداش هر مخاطب در یک ردیف قابل استناد</p></div><span class="user-gallery-section-count">{{ number_format(count($report['conversions'])) }} ثبت‌نام</span></div>
      <div class="user-gallery-report-table-wrap">
        <table class="user-gallery-report-table">
          <thead><tr><th>مخاطب</th><th>لینک / محصول</th><th>وضعیت دعوت</th><th>خرید</th><th>پاداش مخاطب</th><th>تاریخ</th></tr></thead>
          <tbody>
            @forelse($report['conversions'] as $conversion)
              <tr>
                <td><strong>{{ $conversion['invitee'] }}</strong></td>
                <td>{{ $conversion['link'] }}</td>
                <td><span class="user-gallery-report-status {{ $conversion['status'] === 'معتبر' ? 'active' : ($conversion['status'] === 'ردشده' ? 'inactive' : 'pending') }}">{{ $conversion['status'] }}</span></td>
                <td>{{ $conversion['purchased'] ? 'خرید موفق' : 'بدون خرید' }}</td>
                <td>{{ number_format($conversion['invitee_reward']) }} اعتبار</td>
                <td>{{ $conversion['date']?->format('Y/m/d H:i') ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="user-gallery-report-empty">هنوز ثبت‌نامی از لینک‌های این کاربر ثبت نشده است.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <p class="user-gallery-report-note">این گزارش برای اطلاع‌رسانی ساخته شده است؛ پاداش‌های در انتظار، پس از رعایت شرط آزادشدن پاداش یا تأیید مدیر پرداخت می‌شوند.</p>
  </div>
</main>
@endsection

@section('scripts')
@php($copyReport = "گزارش همکاری در فروش «".trim(($user->name ?? '').' '.($user->last_name ?? ''))."»\nکلیک: ".$report['summary']['clicks']."\nبازدیدکننده یکتا: ".$report['summary']['unique_clicks']."\nثبت‌نام: ".$report['summary']['registrations']."\nخرید موفق: ".$report['summary']['purchases']."\nساخت مخاطبان: ".$report['summary']['outputs']."\nجمع پاداش پرداخت‌شده: ".$report['rewards']['combined_paid']." اعتبار\nکمیسیون پرداخت‌شده: ".$report['rewards']['commission_paid']." تومان")
<script>
(() => {
  const button = document.querySelector('[data-copy-referral-report]');
  if (!button) return;
  const text = @json($copyReport);
  button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(text);
      button.innerHTML = '<i class="fa-solid fa-check"></i> خلاصه کپی شد';
      setTimeout(() => { button.innerHTML = '<i class="fa-regular fa-copy"></i> کپی خلاصه برای ارسال'; }, 1800);
    } catch (_) {
      if (typeof window.showAdminToast === 'function') window.showAdminToast('کپی گزارش انجام نشد.', 'error');
    }
  });
})();
</script>
@endsection
