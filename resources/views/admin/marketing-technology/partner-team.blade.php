@extends('layouts.admin')

@section('title', 'عملکرد تیم فروش — تکنولوژی مارکتینگ')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content sp-page sp-team-page flex-1 overflow-y-auto" id="content">
    @if(session('success'))
      <div class="mt-alert mt-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
      <div class="mt-alert mt-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>@foreach($errors->all() as $error){{ $error }}@if(!$loop->last)، @endif @endforeach</span></div>
    @endif

    <div class="mt-page-head sp-page-head">
      <div>
        <div class="mt-eyebrow">مدیریت ساده و نتیجه‌محور</div>
        <h1>عملکرد تیم فروش</h1>
        <p>هر نفر بداند امروز چه هدفی دارد، چه کارت‌هایی دست اوست و کدام پیگیری نباید عقب بیفتد.</p>
      </div>
      <div class="mt-head-actions">
        <a class="mt-btn" href="{{ route('admin.settings.admins') }}"><i class="fa-solid fa-user-plus"></i> افزودن عضو تیم</a>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.partners') }}"><i class="fa-solid fa-arrow-right"></i> بازگشت به مسیر</a>
        <a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.partners.queue') }}"><i class="fa-solid fa-list-check"></i> صف امروز</a>
      </div>
    </div>

    @if(!$ready)
      <section class="mt-card mt-card-pad sp-empty">
        <i class="fa-solid fa-database"></i>
        <h2>زیرساخت تیم فروش آماده نیست</h2>
        <p>بعد از اجرای مهاجرت دیتابیس، هدف‌ها و گزارش عملکرد این بخش فعال می‌شوند.</p>
      </section>
    @else
      <section class="sp-stat-grid sp-team-stat-grid" aria-label="خلاصه عملکرد تیم فروش">
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-primary"><i class="fa-solid fa-address-card"></i></span><div><small>کارت‌های فعال</small><strong>{{ number_format($metrics['active_leads']) }}</strong></div></article>
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-info"><i class="fa-solid fa-message"></i></span><div><small>ارتباط امروز</small><strong>{{ number_format($metrics['today_contacts']) }} <em>/ {{ number_format($metrics['today_target']) }}</em></strong></div></article>
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-warning"><i class="fa-solid fa-clock"></i></span><div><small>عقب‌افتاده</small><strong>{{ number_format($metrics['overdue']) }}</strong></div></article>
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-success"><i class="fa-solid fa-thumbs-up"></i></span><div><small>پاسخ مثبت امروز</small><strong>{{ number_format($metrics['positive_today']) }}</strong></div></article>
      </section>

      <section class="mt-card mt-card-pad sp-team-card">
        <div class="sp-section-head">
          <div><div class="mt-eyebrow">نمای روزانه</div><h2>اعضای تیم و هدف هر نفر</h2><p>هدف روزانه را برای هر نفر تنظیم کن؛ عدد ارتباط‌ها از فعالیت‌های واقعی مسیر محاسبه می‌شود.</p></div>
          <span class="sp-filter-result"><strong>{{ number_format($teamRows->count()) }}</strong> عضو فعال</span>
        </div>

        <div class="sp-team-list">
          @forelse($teamRows as $row)
            <article class="sp-team-member {{ !$row['active'] ? 'is-paused' : '' }}">
              <div class="sp-team-member-head">
                <div class="sp-team-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="sp-team-identity"><strong>{{ $row['admin']->name }}</strong><span>{{ $row['admin']->role ?: 'عضو تیم فروش' }}</span></div>
                <span class="sp-team-status {{ $row['active'] ? 'is-active' : 'is-paused' }}"><i class="fa-solid fa-circle"></i>{{ $row['active'] ? 'فعال' : 'متوقف' }}</span>
              </div>
              <div class="sp-team-progress-row"><div class="sp-team-progress-label"><span>پیشرفت ارتباط امروز</span><strong>{{ number_format($row['contacts']) }} از {{ number_format($row['target']) }} <em>({{ $row['progress'] }}٪)</em></strong></div><div class="sp-team-progress"><span style="width:{{ $row['progress'] }}%"></span></div></div>
              <div class="sp-team-metrics"><div><small>کارت‌های من</small><strong>{{ number_format($row['assigned']) }}</strong></div><div><small>تسک امروز</small><strong>{{ number_format($row['due']) }}</strong></div><div><small>عقب‌افتاده</small><strong class="{{ $row['overdue'] > 0 ? 'is-warning' : '' }}">{{ number_format($row['overdue']) }}</strong></div><div><small>پاسخ مثبت</small><strong>{{ number_format($row['positive']) }}</strong></div><div><small>نرخ پاسخ</small><strong>{{ $row['response_rate'] }}٪</strong></div></div>
              <form method="POST" action="{{ route('admin.marketing-technology.partners.team.settings.update', $row['admin']) }}" class="sp-team-setting-form" data-ajax-form>
                @csrf
                @method('PUT')
                <div class="mt-field"><label for="team-target-{{ $row['admin']->id }}">هدف ارتباط روزانه</label><input class="mt-input" id="team-target-{{ $row['admin']->id }}" type="number" name="daily_contact_target" min="0" max="200" value="{{ $row['target'] }}"></div>
                <label class="sp-team-toggle"><input type="checkbox" name="is_active" value="1" @checked($row['active'])><span>در صف امروز باشد</span></label>
                <button type="submit" class="mt-btn mt-btn-primary"><i class="fa-solid fa-check"></i> ذخیره</button>
              </form>
            </article>
          @empty
            <div class="sp-empty-inline"><i class="fa-solid fa-user-plus"></i><span>هنوز عضو فعال برای تیم فروش ثبت نشده است.</span></div>
          @endforelse
        </div>
      </section>

      <section class="mt-card mt-card-pad sp-team-card">
        <div class="sp-section-head"><div><div class="mt-eyebrow">کنترل خطاهای روزانه</div><h2>هشدارهایی که نباید بمانند</h2><p>هر هشدار مستقیم به همان نمایی می‌رود که برای حل آن لازم است.</p></div></div>
        <div class="sp-alert-grid">
          @foreach($alerts as $alert)
            <a href="{{ $alert['url'] }}" class="sp-alert-card {{ $alert['count'] > 0 ? 'has-alert' : '' }}"><span class="sp-alert-icon"><i class="fa-solid {{ $alert['count'] > 0 ? 'fa-bell' : 'fa-circle-check' }}"></i></span><div><strong>{{ $alert['title'] }}</strong><p>{{ $alert['description'] }}</p></div><b>{{ number_format($alert['count']) }}</b><i class="fa-solid fa-arrow-left sp-alert-arrow"></i></a>
          @endforeach
        </div>
      </section>
    @endif
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  document.querySelectorAll('[data-ajax-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) })
        .then(function (response) { return response.json().then(function (payload) { if (!response.ok) throw new Error(payload.message || 'ذخیره‌سازی انجام نشد.'); return payload; }); })
        .then(function (payload) {
          const notice = document.createElement('div');
          notice.className = 'mt-alert mt-alert-success';
          notice.innerHTML = '<i class="fa-solid fa-circle-check"></i><span></span>';
          notice.querySelector('span').textContent = payload.message || 'ذخیره شد.';
          document.querySelector('.marketing-tech-page')?.prepend(notice);
          window.setTimeout(function () { notice.remove(); }, 3500);
        })
        .catch(function (error) {
          const notice = document.createElement('div');
          notice.className = 'mt-alert mt-alert-danger';
          notice.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span></span>';
          notice.querySelector('span').textContent = error.message;
          document.querySelector('.marketing-tech-page')?.prepend(notice);
        })
        .finally(function () { if (button) button.disabled = false; });
    });
  });
})();
</script>
@endsection
