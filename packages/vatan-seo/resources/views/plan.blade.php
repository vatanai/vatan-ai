@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\Task;
  $seoTitle = 'برنامه‌ی کار';
  $seoIcon = 'fa-list-check';
  $seoHelp = 'plan.page';
  $seoSubtitle = 'همه‌ی کارهای سئو در دو ستون «زیرساخت» و «اهداف کلمات کلیدی»، با اولویت و سررسید. تسک‌های خودکار را ایجنت بررسی و تیک می‌زند؛ کارهای تکراری در «سناریوها» بدون نیاز به تأیید اجرا می‌شوند.';
  $q = fn ($extra) => route('seo.plan', array_filter(array_merge(['view' => $view, 'pillar' => $pillar], $extra)));
@endphp

@section('seo-actions')
  <form method="POST" action="{{ route('seo.plan.rebuild') }}">@csrf<button class="btn-pro btn-pro-ghost" data-confirm="تاریخ تسک‌های باز از برنامه تنظیم شود؟" data-loading="در حال به‌روزرسانی…"><i class="fa-solid fa-calendar-check"></i> بازچینی برنامه</button></form>
  <form method="POST" action="{{ route('seo.tasks.check-all') }}">@csrf<button class="btn-pro btn-pro-primary" data-loading="در حال بررسی همه‌ی تسک‌ها…"><i class="fa-solid fa-robot"></i> بررسی خودکار همه</button></form>
@endsection

@section('seo-page')
<div class="seo-grid seo-grid-main" style="align-items:start">
  <div class="seo-stack">
    <div class="seo-row-gap" style="justify-content:space-between">
      <div class="seo-chips">
        @foreach(['roadmap' => 'تقویم ۹۰ روزه', 'today' => 'امروز', 'week' => '۷ روز آینده', 'month' => 'این ماه', 'all' => 'همه‌ی باز', 'done' => 'انجام‌شده'] as $v => $l)
          <a href="{{ $q(['view' => $v]) }}" class="seo-chip {{ $view === $v ? 'is-active' : '' }}">{{ $l }} <span class="n">{{ Fa::n($counts[$v]) }}</span></a>
        @endforeach
      </div>
      <div class="seo-chips">
        <a href="{{ route('seo.plan', ['view' => $view]) }}" class="seo-chip {{ ! $pillar ? 'is-active' : '' }}">هر دو ستون</a>
        <a href="{{ $q(['pillar' => 'infrastructure']) }}" class="seo-chip {{ $pillar === 'infrastructure' ? 'is-active' : '' }}"><i class="fa-solid fa-screwdriver-wrench"></i> زیرساخت</a>
        <a href="{{ $q(['pillar' => 'goals']) }}" class="seo-chip {{ $pillar === 'goals' ? 'is-active' : '' }}"><i class="fa-solid fa-bullseye"></i> اهداف</a>
      </div>
    </div>

    @if($roadmap)
      @if(!empty($weeklyPlan['focus']))
        <div class="seo-callout"><i class="fa-solid fa-compass"></i><div><b>تمرکز هفته‌ی {{ Fa::n($weeklyPlan['week'] ?? $currentWeek) }} از نگاه استراتژیست:</b> {{ $weeklyPlan['focus'] }} @if(!empty($weeklyPlan['summary']))<div class="seo-muted" style="font-size:12px">{{ $weeklyPlan['summary'] }}</div>@endif</div></div>
      @endif
      <div class="seo-roadmap">
        @foreach($roadmap as $wk)
          @php($pct = $wk['total'] ? round($wk['done'] / $wk['total'] * 100) : 0)
          @php($state = $wk['week'] < $currentWeek ? 'is-past' : ($wk['week'] === $currentWeek ? 'is-current' : 'is-future'))
          <details class="seo-week {{ $state }}" @if($wk['week'] === $currentWeek || $wk['week'] === $currentWeek + 1) open @endif>
            <summary>
              <span class="seo-week-num"><small>هفته</small>{{ Fa::n($wk['week']) }}</span>
              <span class="seo-week-head">
                <span class="seo-week-theme">{{ $wk['theme']['theme'] }} @if($wk['week'] === $currentWeek)<span class="seo-tag is-primary">هفته‌ی جاری</span>@endif @if($wk['action'])<span class="seo-tag is-danger">{{ Fa::n($wk['action']) }} نیاز به اقدام</span>@endif</span>
                <span class="seo-week-sub">{{ Fa::dayLabel($wk['start']) }} تا {{ Fa::dayLabel($wk['end']) }} · {{ $wk['theme']['goal'] }}</span>
              </span>
              <span class="seo-week-prog">
                <span class="seo-num">{{ Fa::n($wk['done']) }}/{{ Fa::n($wk['total']) }}</span>
                <span class="seo-meter" style="width:90px"><span style="width:{{ max(2, $pct) }}%"></span></span>
                @if($wk['daily_total'])<span class="seo-muted" style="font-size:10.5px">روزانه {{ Fa::n($wk['daily_done']) }}/{{ Fa::n($wk['daily_total']) }}</span>@endif
              </span>
            </summary>
            @if($wk['days'])
              <div class="seo-week-days">
                @foreach($wk['days'] as $code => $day)
                  <div class="seo-day {{ $day['date']->isToday() ? 'is-today' : '' }}">
                    <div class="seo-day-head">{{ \Vatan\Seo\Services\RoadmapPlanner::DAY_LABELS[$code] }} <span>{{ Fa::dayLabel($day['date']) }}</span></div>
                    @foreach($day['tasks'] as $task) @include('seo::partials.task-mini', ['task' => $task]) @endforeach
                  </div>
                @endforeach
              </div>
            @else
              <div class="seo-empty" style="padding:16px">تسکی در این هفته نیست.</div>
            @endif
          </details>
        @endforeach
      </div>
    @endif

    @forelse($groups as $title => $items)
      <div>
        <div class="seo-group-title">{{ $title }} <span class="count">{{ Fa::n($items->count()) }}</span>
          @if($title === 'نیاز به اقدام شما') @include('seo::partials.help', ['k' => 'plan.needs_action']) @endif
        </div>
        @foreach($items as $task) @include('seo::partials.task', ['task' => $task]) @endforeach
      </div>
    @empty
      @unless($roadmap)<div class="seo-card"><div class="seo-empty"><i class="fa-solid fa-circle-check"></i><b>کاری در این بازه نیست</b>بازه‌ی دیگری را انتخاب کنید.</div></div>@endunless
    @endforelse
  </div>

  <div class="seo-stack">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-flag-checkered"></i> پیشرفت @include('seo::partials.help', ['k' => 'overview.pillars'])</div></div>
      @foreach(['infrastructure' => 'زیرساخت فنی', 'goals' => 'اهداف کلمات کلیدی'] as $key => $label)
        @php($pr = $progress[$key]) @php($pct = $pr['total'] ? round($pr['done'] / $pr['total'] * 100) : 0)
        <div style="margin-bottom:14px">
          <div class="seo-row-gap" style="justify-content:space-between;margin-bottom:6px"><b style="font-size:12.5px;color:var(--text-h)">{{ $label }}</b><span class="seo-muted seo-num" style="font-size:11.5px">{{ Fa::n($pr['done']) }}/{{ Fa::n($pr['total']) }}{{ $pr['action'] ? ' · '.Fa::n($pr['action']).' نیاز به اقدام' : '' }}</span></div>
          <div class="seo-meter"><span style="width:{{ max(2, $pct) }}%"></span></div>
        </div>
      @endforeach
      <hr class="seo-sep">
      <div class="seo-card-title" style="font-size:12px;margin-bottom:8px">زیرساخت به تفکیک حوزه</div>
      @foreach($categories as $cat => $c)
        <div class="seo-row-gap" style="justify-content:space-between;padding:4px 0;font-size:12px"><span>{{ Task::CATEGORIES[$cat] ?? $cat }}</span><span class="seo-num {{ $c['done'] === $c['total'] ? 'tone-success' : 'seo-muted' }}">{{ Fa::n($c['done']) }}/{{ Fa::n($c['total']) }}</span></div>
      @endforeach
    </section>
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-robot"></i> اجرای خودکار بعدی @include('seo::partials.help', ['k' => 'plan.scenarios'])</div><a href="{{ route('seo.scenarios.index') }}" class="seo-link">مدیریت <i class="fa-solid fa-angle-left"></i></a></div>
      <div class="seo-rows">
        @foreach($scenarios->take(7) as $s)
          <div class="seo-item"><span class="seo-dot is-{{ ['success' => 'success', 'warning' => 'warning', 'failed' => 'danger', 'skipped' => 'warning'][$s->last_status] ?? 'info' }}"></span><div class="seo-item-main"><div class="seo-item-title" style="font-size:12px">{{ $s->title }}</div><div class="seo-item-sub">{{ Fa::until($s->next_run_at) }}</div></div></div>
        @endforeach
      </div>
    </section>
    <section class="seo-card">
      <div class="seo-card-title" style="margin-bottom:8px"><i class="fa-solid fa-circle-info"></i> راهنمای نمادها</div>
      <div class="seo-stack" style="gap:6px;font-size:11.5px">
        <span><span class="seo-tag"><i class="fa-solid fa-robot"></i>خودکار</span> ایجنت خودش بررسی و تیک می‌زند</span>
        <span><span class="seo-tag"><i class="fa-solid fa-handshake-angle"></i>نیمه‌خودکار</span> ایجنت پیشنهاد آماده می‌دهد، شما اعمال می‌کنید</span>
        <span><span class="seo-tag"><i class="fa-solid fa-user"></i>دستی</span> کار انسانی؛ بعد از انجام تیک بزنید</span>
        <span><span class="seo-tag">روزانه</span> شنبه تا چهارشنبه؛ روزش بگذرد خودکار بسته می‌شود</span>
        <span><span class="seo-tag">استراتژیست</span> اقدام‌هایی که مدیر سئوی هوش مصنوعی هر شنبه اضافه می‌کند</span>
      </div>
    </section>
  </div>
</div>
@endsection
