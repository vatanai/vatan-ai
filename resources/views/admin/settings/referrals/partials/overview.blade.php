<section class="referral-stat-grid" aria-label="خلاصه عملکرد همکاری در فروش">
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-primary"><i class="fa-solid fa-arrow-pointer"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['visits']) }}</div><div class="stat-card-label">ورود از لینک دعوت</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-success"><i class="fa-solid fa-user-check"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['conversions']) }}</div><div class="stat-card-label">ثبت‌نام رفرالی</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-info"><i class="fa-solid fa-coins"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['paid_tokens']) }}</div><div class="stat-card-label">توکن پرداخت‌شده</div></div>
  </article>
  <article class="stat-card referral-stat-card">
    <span class="stat-card-icon is-warning"><i class="fa-solid fa-shield-halved"></i></span>
    <div><div class="stat-card-value">{{ number_format($stats['pending']) }}</div><div class="stat-card-label">در انتظار بررسی</div></div>
  </article>
</section>

<section class="content-card referral-hub-card">
  <div class="referral-card-head">
    <span class="referral-card-icon is-primary"><i class="fa-solid fa-table-cells-large"></i></span>
    <div><h2>مدیریت برنامه</h2><p>هر بخش صفحه و ابزارهای مخصوص خودش را دارد.</p></div>
  </div>
  <div class="referral-hub-grid">
    @foreach([
      ['route' => 'admin.referrals.settings', 'icon' => 'fa-sliders', 'title' => 'تنظیمات برنامه', 'text' => 'مقدار هدیه، شرط پرداخت و محتوای پروفایل'],
      ['route' => 'admin.referrals.conversions', 'icon' => 'fa-user-group', 'title' => 'فهرست دعوت‌ها', 'text' => 'وضعیت ثبت‌نام، خرید و دعوت‌کننده'],
      ['route' => 'admin.referrals.rewards', 'icon' => 'fa-coins', 'title' => 'گزارش پاداش‌ها', 'text' => 'ریز توکن‌های پرداخت‌شده و معلق'],
      ['route' => 'admin.referrals.visits', 'icon' => 'fa-arrow-pointer', 'title' => 'بازدید لینک‌ها', 'text' => 'ورودی لینک‌ها و نتیجه تبدیل'],
      ['route' => 'admin.referrals.reviews', 'icon' => 'fa-shield-halved', 'title' => 'صف بررسی', 'text' => 'تصمیم‌گیری روی موارد مشکوک و تکراری'],
    ] as $item)
      <a href="{{ route($item['route']) }}" class="referral-hub-link">
        <span><i class="fa-solid {{ $item['icon'] }}"></i></span>
        <div><strong>{{ $item['title'] }}</strong><small>{{ $item['text'] }}</small></div>
        <i class="fa-solid fa-chevron-left"></i>
      </a>
    @endforeach
  </div>
</section>
