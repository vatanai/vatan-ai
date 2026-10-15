@extends('cf::layout')
@php use Vatan\Content\Support\Fa; @endphp

@section('cf-actions')
  <a href="{{ route('cf.calendar') }}" class="cf-btn"><i class="fa-solid fa-calendar-days"></i> تقویم</a>
  <a href="{{ route('cf.productions', ['status' => 'review']) }}" class="cf-btn cf-btn-primary"><i class="fa-solid fa-circle-check"></i> صف تأیید</a>
@endsection

@section('cf-page')
<div class="cf-stack">
  <div class="cf-grid cf-g4">
    <div class="cf-card cf-stat"><span class="cf-stat-label">تقویم فعال</span><span class="cf-stat-value">{{ $calendar ? Fa::n($stats['items']) : '—' }}</span>
      <span class="cf-stat-sub">@if($calendar){{ Fa::label('calendar_status', $calendar->status) }} · {{ Fa::date($calendar->starts_on) }} تا {{ Fa::date($calendar->ends_on) }}@else هنوز تقویمی ثبت نشده @endif</span></div>
    <div class="cf-card cf-stat"><span class="cf-stat-label">در انتظار تأیید شما</span><span class="cf-stat-value">{{ Fa::n(($statusCounts['review'] ?? 0) + ($statusCounts['revision'] ?? 0)) }}</span><span class="cf-stat-sub">منتشرشده: {{ Fa::n($stats['published']) }}</span></div>
    <div class="cf-card cf-stat"><span class="cf-stat-label">بانک سبک‌ها</span><span class="cf-stat-value">{{ Fa::n($stats['styles']) }}</span><span class="cf-stat-sub">{{ Fa::n($stats['refs']) }} ویدیوی مرجع · {{ Fa::n($stats['styles_pending']) }} در انتظار تأیید</span></div>
    <div class="cf-card cf-stat"><span class="cf-stat-label">هزینه‌ی این ماه</span><span class="cf-stat-value">{{ Fa::usd($stats['cost_month']) }}</span><span class="cf-stat-sub">{{ Fa::n($stats['music']) }} موسیقی مجاز در کتابخانه</span></div>
  </div>

  <div class="cf-grid cf-g2" style="align-items:start">
    @include('cf::partials.jobs')
    <div class="cf-card">
      <h2 class="cf-card-title"><i class="fa-solid fa-wand-magic-sparkles"></i> کارهای سریع @include('cf::partials.help', ['k' => 'agent'])</h2>
      <div class="cf-row">
        <form method="post" action="{{ route('cf.jobs.store') }}">@csrf<input type="hidden" name="type" value="ingest_videos"><button class="cf-btn"><i class="fa-solid fa-film"></i> تحلیل ویدیوهای پوشه‌ی مک</button></form>
        <form method="post" action="{{ route('cf.jobs.store') }}">@csrf<input type="hidden" name="type" value="ingest_music"><button class="cf-btn"><i class="fa-solid fa-music"></i> تحلیل موزیک‌های پوشه‌ی مک</button></form>
        @if($todayItem)<form method="post" action="{{ route('cf.calendar.build', $todayItem) }}">@csrf<button class="cf-btn cf-btn-primary"><i class="fa-solid fa-play"></i> ساخت پست امروز</button></form>@endif
      </div>
      @php $pend = (array) (((array) \Vatan\Content\Models\Setting::get('agent', []))['pending'] ?? []); @endphp
      <div class="cf-muted" style="font-size:12px;margin-top:10px">در پوشه‌ی مک منتظر تحلیل: {{ \Vatan\Content\Support\Fa::n($pend['inbox'] ?? 0) }} ویدیو · {{ \Vatan\Content\Support\Fa::n($pend['music'] ?? 0) }} موزیک (طبق آخرین گزارش ایجنت)</div>
    </div>
  </div>

  <div class="cf-grid cf-g2">
    <div class="cf-card">
      <h2 class="cf-card-title"><i class="fa-solid fa-bolt"></i> امروز و ۷ روز آینده</h2>
      @forelse($upcoming as $i)
        <a href="{{ $i->production ? route('cf.productions.show', $i->production) : route('cf.calendar') }}" class="cf-between" style="padding:9px 0;border-bottom:1px solid var(--divider)">
          <div style="min-width:0">
            <div style="font-weight:800;color:var(--text-h)">{{ $i->hook }}</div>
            <div class="cf-muted" style="font-size:11.5px">{{ Fa::date($i->publish_date, false, true) }} · {{ Fa::digits($i->publish_time) }} · {{ $i->format }} · {{ $i->pillar }}</div>
          </div>
          @include('cf::partials.status', ['s' => $i->production->status ?? $i->production_status])
        </a>
      @empty
        <div class="cf-empty"><i class="fa-solid fa-calendar-plus"></i>تقویمی برای روزهای آینده نیست. از «تنظیمات ← همگام‌سازی» تقویم پوشه‌ی content-lab را وارد کنید.</div>
      @endforelse
    </div>
    <div class="cf-card">
      <h2 class="cf-card-title"><i class="fa-solid fa-hourglass-half"></i> آخرین‌های صف تأیید</h2>
      @forelse($review as $p)
        <a href="{{ route('cf.productions.show', $p) }}" class="cf-between" style="padding:9px 0;border-bottom:1px solid var(--divider)">
          <span style="font-weight:700;color:var(--text-h)">{{ $p->title }}</span>
          <span class="cf-row">@include('cf::partials.status', ['s' => $p->status])<span class="cf-pill">نسخه‌ی {{ Fa::n($p->current_version) }}</span></span>
        </a>
      @empty
        <div class="cf-empty"><i class="fa-solid fa-mug-hot"></i>فعلاً چیزی منتظر تأیید نیست.</div>
      @endforelse
      <h2 class="cf-card-title" style="margin-top:18px"><i class="fa-solid fa-route"></i> مسیر هر محتوا</h2>
      <div class="cf-row" style="font-size:12px">
        @foreach(['پرسش‌نامه', 'تقویم', 'تأیید و قفل', 'سناریو', 'ساخت', 'کنترل کیفیت', 'تأیید شما', 'انتشار', 'آمار', 'یادگیری'] as $k => $step)
          <span class="cf-pill">{{ $step }}</span>@if(! $loop->last)<i class="fa-solid fa-angle-left cf-muted"></i>@endif
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection
