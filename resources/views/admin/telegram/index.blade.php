@extends('layouts.admin')
@section('title', 'داشبورد بات تلگرام — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    <header class="tg-head">
      <div><p>جذب و ارتباط</p><h1>داشبورد بات تلگرام</h1><span>مسیر ورود کاربران، محصول‌های انتخاب‌شده و وضعیت اتصال به حساب سایت را یکجا ببین.</span></div>
      <a class="btn-pro btn-pro-primary" href="{{ route('admin.telegram.users') }}"><i class="fa-solid fa-users"></i> مدیریت کاربران</a>
    </header>

    <section class="tg-stats">
      @foreach([
        ['label' => 'کل کاربران بات', 'value' => $stats['total'], 'icon' => 'fa-users', 'tone' => 'primary'],
        ['label' => 'متصل به حساب سایت', 'value' => $stats['linked'], 'icon' => 'fa-link', 'tone' => 'success'],
        ['label' => 'ورودی‌های امروز', 'value' => $stats['new_today'], 'icon' => 'fa-user-plus', 'tone' => 'info'],
        ['label' => 'فعال در هفت روز اخیر', 'value' => $stats['active_week'], 'icon' => 'fa-bolt', 'tone' => 'warning'],
        ['label' => 'خارج‌شده از ارسال', 'value' => $stats['blocked'], 'icon' => 'fa-ban', 'tone' => 'danger'],
        ['label' => 'کلیک محصول', 'value' => $stats['clicks'], 'icon' => 'fa-arrow-pointer', 'tone' => 'purple'],
      ] as $stat)
        <div class="stat-card tg-stat-card">
          <div class="tg-stat-icon tone-{{ $stat['tone'] }}"><i class="fa-solid {{ $stat['icon'] }}"></i></div>
          <div><span>{{ $stat['label'] }}</span><strong>{{ number_format($stat['value']) }}</strong></div>
        </div>
      @endforeach
    </section>

    <section class="tg-grid">
      <div class="content-card tg-chart-card">
        <div class="tg-card-head"><div><h2>روند ورود در ۳۰ روز اخیر</h2><p>تعداد شروع‌های ثبت‌شده در هر روز</p></div><i class="fa-solid fa-chart-column"></i></div>
        @php $maxStarts = max(1, (int) $daily->max('starts')); @endphp
        <div class="tg-bars">
          @foreach($daily as $day)
            <div class="tg-bar-col" title="{{ $day['date'] }} — {{ number_format($day['starts']) }} ورود">
              <div class="tg-bar" style="height:{{ max(4, (int) round(($day['starts'] / $maxStarts) * 100)) }}%"></div>
              <small>{{ \Carbon\Carbon::parse($day['date'])->format('m/d') }}</small>
            </div>
          @endforeach
        </div>
      </div>
      <div class="content-card tg-list-card">
        <div class="tg-card-head"><div><h2>منابع ورود</h2><p>بر اساس کلیک‌های محصول</p></div><i class="fa-solid fa-filter"></i></div>
        @forelse($sources as $source => $count)
          @php $width = max(8, (int) round(($count / max(1, (int) $sources->first())) * 100)); @endphp
          <div class="tg-source-row"><div><span>{{ $source }}</span><strong>{{ number_format($count) }}</strong></div><div class="tg-progress"><i style="width:{{ $width }}%"></i></div></div>
        @empty
          <div class="empty-state">هنوز کلیک محصولی ثبت نشده است.</div>
        @endforelse
      </div>
    </section>

    <section class="tg-grid tg-grid-bottom">
      <div class="content-card tg-list-card"><div class="tg-card-head"><div><h2>محصول‌های پرتکرار</h2><p>محصول‌هایی که بیشتر از کانال انتخاب شده‌اند</p></div><i class="fa-solid fa-box-open"></i></div>
        @forelse($topProducts as $item)<div class="tg-product-row"><span>{{ $item['name'] }}</span><b>{{ number_format($item['count']) }} کلیک</b></div>@empty<div class="empty-state">هنوز داده‌ای برای نمایش وجود ندارد.</div>@endforelse
      </div>
      <div class="content-card tg-list-card"><div class="tg-card-head"><div><h2>آخرین کاربران</h2><p>برای مشاهده‌ی جزئیات، کاربر را انتخاب کن.</p></div><a href="{{ route('admin.telegram.users') }}">همه</a></div>
        @forelse($users->take(6) as $telegramUser)<a class="tg-user-row" href="{{ route('admin.telegram.users.show', $telegramUser) }}"><span class="tg-avatar">{{ mb_substr($telegramUser->first_name ?: 'ت', 0, 1) }}</span><span><strong>{{ trim($telegramUser->first_name . ' ' . $telegramUser->last_name) ?: 'کاربر تلگرام' }}</strong><small>{{ '@' . ($telegramUser->username ?: $telegramUser->telegram_id) }}</small></span><b>{{ number_format($telegramUser->product_clicks_count) }} کلیک</b></a>@empty<div class="empty-state">کاربری ثبت نشده است.</div>@endforelse
      </div>
    </section>
  </div>
</main>
<style>
.tg-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.tg-head p{margin:0 0 5px;color:var(--primary);font-size:11px;font-weight:800}.tg-head h1{margin:0;color:var(--text-h);font-size:23px;font-weight:900}.tg-head span{display:block;margin-top:7px;color:var(--text-soft);font-size:12px}.tg-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:16px}.tg-stat-card{display:flex;align-items:center;gap:11px;min-height:92px;padding:15px}.tg-stat-card span{display:block;color:var(--text-soft);font-size:10px;line-height:1.7}.tg-stat-card strong{display:block;margin-top:2px;color:var(--text-h);font-size:21px}.tg-stat-icon{display:grid;width:38px;height:38px;flex:0 0 auto;place-items:center;border-radius:11px;font-size:14px}.tone-primary{background:var(--primary-l);color:var(--primary)}.tone-success{background:var(--success-l);color:var(--success)}.tone-info{background:var(--info-l);color:var(--info)}.tone-warning{background:var(--warning-l);color:var(--warning)}.tone-danger{background:var(--danger-l);color:var(--danger)}.tone-purple{background:var(--primary-l);color:var(--primary)}.tg-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(300px,.8fr);gap:16px;margin-bottom:16px}.tg-grid-bottom{grid-template-columns:1fr 1fr}.tg-chart-card,.tg-list-card{padding:18px}.tg-card-head{display:flex;align-items:start;justify-content:space-between;gap:12px;margin-bottom:17px}.tg-card-head h2{margin:0;color:var(--text-h);font-size:14px}.tg-card-head p{margin:5px 0 0;color:var(--text-soft);font-size:10px}.tg-card-head>i{color:var(--primary)}.tg-card-head a{color:var(--primary);font-size:11px;font-weight:800;text-decoration:none}.tg-bars{display:flex;align-items:end;gap:5px;height:190px;padding-top:10px;overflow:hidden}.tg-bar-col{display:flex;min-width:14px;height:100%;flex:1;flex-direction:column;align-items:center;justify-content:end;gap:7px}.tg-bar{width:100%;min-height:5px;border-radius:6px 6px 2px 2px;background:var(--primary);opacity:.85}.tg-bar-col small{color:var(--text-soft);font-size:8px;direction:ltr;white-space:nowrap;transform:rotate(-45deg);transform-origin:top center}.tg-source-row{margin-bottom:15px}.tg-source-row>div:first-child{display:flex;justify-content:space-between;gap:8px;color:var(--text-main);font-size:11px}.tg-source-row strong{color:var(--text-h)}.tg-progress{height:7px;margin-top:7px;overflow:hidden;border-radius:99px;background:var(--input-bg)}.tg-progress i{display:block;height:100%;border-radius:inherit;background:var(--primary)}.tg-product-row,.tg-user-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--border);color:var(--text-main);font-size:11px;text-decoration:none}.tg-product-row:last-child,.tg-user-row:last-child{border-bottom:0}.tg-product-row b,.tg-user-row>b{color:var(--text-soft);font-size:10px}.tg-user-row{justify-content:flex-start}.tg-user-row>span:nth-child(2){min-width:0;flex:1}.tg-user-row strong,.tg-user-row small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.tg-user-row small{margin-top:2px;color:var(--text-soft);font-size:9px;direction:ltr;text-align:right}.tg-avatar{display:grid;width:32px;height:32px;flex:0 0 auto;place-items:center;border-radius:50%;background:var(--primary-l);color:var(--primary);font-weight:900}.empty-state{padding:24px 8px;text-align:center;color:var(--text-soft);font-size:11px}@media(max-width:1200px){.tg-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:800px){.tg-grid,.tg-grid-bottom{grid-template-columns:1fr}.tg-head{align-items:start;flex-direction:column}.tg-bars{height:165px}}@media(max-width:520px){.tg-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.tg-stat-card strong{font-size:18px}.tg-head h1{font-size:20px}}
</style>
@endsection
