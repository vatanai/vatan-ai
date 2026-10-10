@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  $seoTitle = 'نمای کلی سئو';
  $seoIcon = 'fa-chart-line';
  $seoHelp = 'overview.page';
  $seoSubtitle = 'وضعیت رشد سایت در گوگل، پیشرفت کلمات هدف، کارهای امروز و آنچه ایجنت‌ها انجام داده‌اند — در یک نگاه.';
  $c = $kpi['cur']; $p = $kpi['prev'];
  $kws = $targets->count();
@endphp

@section('seo-actions')
  <div class="seo-chart-toggle" role="tablist" aria-label="بازه">
    @foreach([7 => '۷ روز', 28 => '۲۸ روز', 90 => '۹۰ روز'] as $r => $l)
      <a href="{{ route('seo.overview', ['range' => $r]) }}" class="{{ $range === $r ? 'is-active' : '' }}">{{ $l }}</a>
    @endforeach
  </div>
@endsection

@section('seo-page')
<div class="seo-stack">

  @unless($gscReady)
    <div class="seo-callout">
      <i class="fa-solid fa-plug-circle-exclamation"></i>
      <div><b>سرچ کنسول هنوز متصل نیست.</b> کارت‌های کلیک و رتبه بعد از اتصال پر می‌شوند (۹۰ روز گذشته یک‌جا وارد می‌شود). بررسی‌های فنی و کشف کلمات از همین حالا کار می‌کنند.
        <a class="seo-link" href="{{ route('seo.settings') }}#google">اتصال در ۳ دقیقه <i class="fa-solid fa-angle-left"></i></a></div>
    </div>
  @endunless

  {{-- هفته‌ی جاری برنامه‌ی ۹۰ روزه --}}
  @php($wpct = $week['total'] ? round($week['done'] / $week['total'] * 100) : 0)
  <section class="seo-card">
    <div class="seo-weekcard">
      <span class="seo-week-num"><small>هفته</small>{{ Fa::n($week['n']) }}</span>
      <div style="min-width:0">
        <div class="seo-card-title">{{ $week['theme']['theme'] }} @include('seo::partials.help', ['k' => 'overview.week'])</div>
        <div class="seo-card-sub">{{ $week['theme']['goal'] }} · از {{ Fa::dayLabel($week['start']) }}{{ $week['n'] <= 13 ? ' · '.Fa::n(max(0, 13 - $week['n'])).' هفته تا پایان برنامه‌ی ۹۰ روزه' : '' }}</div>
        @if(!empty($week['plan']['actions']) && ($week['plan']['week'] ?? 0) === $week['n'])
          <ol class="seo-actions-list">@foreach(array_slice($week['plan']['actions'], 0, 5) as $a)<li>{{ $a['title'] }}</li>@endforeach</ol>
        @endif
      </div>
      <div style="text-align:left;min-width:120px">
        <div class="seo-kpi-value seo-num" style="font-size:20px">{{ Fa::n($week['done']) }}/{{ Fa::n($week['total']) }}</div>
        <div class="seo-meter" style="margin:6px 0"><span style="width:{{ max(2, $wpct) }}%"></span></div>
        <a class="seo-link" href="{{ route('seo.plan') }}">تقویم ۹۰ روزه <i class="fa-solid fa-angle-left"></i></a>
      </div>
    </div>
  </section>

  {{-- KPI --}}
  <div class="seo-grid seo-grid-4">
    @foreach([
      ['clicks', 'کلیک از گوگل', 'fa-arrow-pointer', 'primary', Fa::n($c['clicks']), $c['clicks'], $p['clicks'], false, $spark['clicks'], false],
      ['impressions', 'ایمپرشن (نمایش)', 'fa-eye', 'info', Fa::short($c['impressions']), $c['impressions'], $p['impressions'], false, $spark['impressions'], false],
      ['ctr', 'نرخ کلیک (CTR)', 'fa-percent', 'warning', Fa::percent($c['ctr']), $c['ctr'], $p['ctr'], false, [], false],
      ['position', 'میانگین رتبه', 'fa-ranking-star', 'success', $c['position'] ? Fa::n($c['position'], 1) : '—', $c['position'], $p['position'], true, $spark['position'], true],
    ] as [$key, $label, $icon, $tone, $value, $now, $before, $lower, $series, $invert])
      <div class="seo-card seo-kpi">
        <div class="seo-kpi-top">
          <span class="seo-kpi-label">{{ $label }} @include('seo::partials.help', ['k' => 'overview.'.$key])</span>
          <span class="seo-kpi-icon bg-{{ $tone }} tone-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></span>
        </div>
        <div class="seo-kpi-value seo-num">{{ $value }}</div>
        <div class="seo-kpi-foot">
          <span>نسبت به {{ Fa::n($range) }} روز قبل</span>
          @include('seo::partials.delta', ['now' => $now, 'before' => $before, 'lower' => $lower])
        </div>
        <div class="tone-{{ $tone }}">@include('seo::partials.spark', ['values' => $series, 'invert' => $invert])</div>
      </div>
    @endforeach
  </div>

  <div class="seo-grid seo-grid-main">
    {{-- نمودار ترافیک --}}
    <section class="seo-card">
      <div class="seo-card-head">
        <div>
          <div class="seo-card-title"><i class="fa-solid fa-chart-column"></i> روند ترافیک ارگانیک @include('seo::partials.help', ['k' => 'overview.traffic'])</div>
          <div class="seo-card-sub">{{ $lastDate ? 'آخرین داده‌ی گوگل: '.Fa::date($lastDate) : 'هنوز داده‌ای همگام نشده است' }}</div>
        </div>
      </div>
      @if(count($chart['labels']))
        <div class="seo-chart"><canvas data-seo-chart="seo-traffic-data" data-type="traffic"></canvas></div>
        <script type="application/json" id="seo-traffic-data">@json($chart)</script>
      @else
        <div class="seo-empty"><i class="fa-solid fa-chart-area"></i><b>هنوز داده‌ای نیست</b>بعد از اتصال سرچ کنسول، سناریوی «همگام‌سازی روزانه» ۹۰ روز گذشته را وارد می‌کند.</div>
      @endif
    </section>

    {{-- پیشرفت دو ستون + بودجه --}}
    <div class="seo-stack">
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-flag-checkered"></i> پیشرفت برنامه @include('seo::partials.help', ['k' => 'overview.pillars'])</div><a class="seo-link" href="{{ route('seo.plan') }}">برنامه‌ی کار <i class="fa-solid fa-angle-left"></i></a></div>
        @foreach([['زیرساخت فنی', $infra, 'infrastructure', 'پایه‌ی فنی: خزش، ایندکس، سرعت، اسکیما'], ['اهداف کلمات کلیدی', $goals, 'goals', 'تحقیق، صفحه‌ی هدف، محتوا و لینک برای هر کلمه']] as [$label, $prog, $key, $sub])
          @php($pct = $prog['total'] ? round($prog['done'] / $prog['total'] * 100) : 0)
          <a href="{{ route('seo.plan', ['pillar' => $key]) }}" class="seo-pillar" style="padding:6px 0">
            <div class="seo-ring" style="--p:{{ $pct }}"><span class="seo-num">{{ Fa::n($pct) }}٪</span></div>
            <div><div class="seo-pillar-title">{{ $label }}</div><div class="seo-pillar-sub">{{ Fa::n($prog['done']) }} از {{ Fa::n($prog['total']) }} تسک انجام شده<br>{{ $sub }}</div></div>
          </a>
        @endforeach
      </section>
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-coins"></i> بودجه‌ی هوش مصنوعی @include('seo::partials.help', ['k' => 'overview.budget'])</div><span class="seo-tag is-primary">{{ data_get($site->profile(), 'label') }}</span></div>
        <div class="seo-row-gap" style="justify-content:space-between;margin-bottom:8px"><span class="seo-kpi-value seo-num" style="font-size:20px">{{ Fa::usd($budget['spent']) }}</span><span class="seo-muted">از سقف {{ Fa::usd($budget['cap']) }}</span></div>
        <div class="seo-meter {{ $budget['ratio'] > .9 ? 'is-danger' : ($budget['ratio'] > .7 ? 'is-warn' : '') }}"><span style="width:{{ max(2, round($budget['ratio'] * 100)) }}%"></span></div>
        <div class="seo-card-sub" style="margin-top:8px">پیش‌بینی پایان ماه: {{ Fa::usd($budget['forecast']) }}</div>
      </section>
    </div>
  </div>

  <div class="seo-grid seo-grid-main">
    {{-- کلمات هدف --}}
    <section class="seo-card">
      <div class="seo-card-head">
        <div>
          <div class="seo-card-title"><i class="fa-solid fa-bullseye"></i> رتبه‌ی کلمات هدف @include('seo::partials.help', ['k' => 'overview.ranks'])</div>
          <div class="seo-card-sub">{{ Fa::n($kws) }} کلمه‌ی هدف · منبع: {{ data_get($site->profile(), 'rank_source') === 'gsc' ? 'سرچ کنسول' : 'نتایج زنده‌ی گوگل' }}</div>
        </div>
        <a class="seo-link" href="{{ route('seo.keywords.index') }}">همه‌ی کلمات <i class="fa-solid fa-angle-left"></i></a>
      </div>
      @if($kws)
        @php($total = max(1, array_sum($buckets)))
        <div class="seo-buckets" aria-label="توزیع رتبه">
          @foreach(['top3', 'top10', 'top20', 'rest', 'none'] as $b)@if($buckets[$b])<span class="b-{{ $b }}" style="width:{{ $buckets[$b] / $total * 100 }}%"></span>@endif @endforeach
        </div>
        <div class="seo-legend">
          <span><i class="b-top3" style="background:var(--success)"></i>۱ تا ۳ <b>{{ Fa::n($buckets['top3']) }}</b></span>
          <span><i style="background:var(--info)"></i>۴ تا ۱۰ <b>{{ Fa::n($buckets['top10']) }}</b></span>
          <span><i style="background:var(--warning)"></i>۱۱ تا ۲۰ <b>{{ Fa::n($buckets['top20']) }}</b></span>
          <span><i style="background:var(--text-soft);opacity:.45"></i>بالای ۲۰ <b>{{ Fa::n($buckets['rest']) }}</b></span>
          <span><i style="background:var(--border)"></i>بدون رتبه <b>{{ Fa::n($buckets['none']) }}</b></span>
        </div>
        <hr class="seo-sep">
        @if(collect($rankChart['series'])->sum(fn ($s) => count($s['points'])) > 1)
          <div class="seo-chart"><canvas data-seo-chart="seo-rank-data" data-type="rank"></canvas></div>
          <script type="application/json" id="seo-rank-data">@json($rankChart)</script>
        @else
          <div class="seo-rows">
            @foreach($topTargets as $k)
              <a class="seo-item" href="{{ route('seo.keywords.show', $k) }}">
                @include('seo::partials.pos', ['p' => $k->current_position])
                <div class="seo-item-main"><div class="seo-item-title">{{ $k->keyword }}</div><div class="seo-item-sub">{{ $k->target_url ? urldecode(parse_url($k->target_url, PHP_URL_PATH) ?: '/') : 'صفحه‌ی هدف تعیین نشده' }}</div></div>
                <div class="seo-item-meta">@include('seo::partials.delta', ['now' => $k->current_position, 'before' => $k->previous_position, 'lower' => true, 'abs' => true])</div>
              </a>
            @endforeach
          </div>
          <div class="seo-card-sub" style="margin-top:8px">نمودار روند بعد از چند روز ثبت رتبه نمایش داده می‌شود.</div>
        @endif
      @else
        <div class="seo-empty"><i class="fa-solid fa-key"></i><b>هنوز کلمه‌ی هدفی انتخاب نشده</b>ایجنت می‌تواند از روی محصولات بهترین کلمات را پیدا کند.
          <form method="POST" action="{{ route('seo.keywords.discover') }}" style="margin-top:12px">@csrf<button class="btn-pro btn-pro-primary" data-loading="در حال کشف کلمات… (۱ تا ۳ دقیقه)"><i class="fa-solid fa-wand-magic-sparkles"></i> کشف کلمات از محصولات</button></form>
        </div>
      @endif
    </section>

    {{-- امروز --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-calendar-day"></i> کارهای پیش‌رو @include('seo::partials.help', ['k' => 'overview.today'])</div><a class="seo-link" href="{{ route('seo.plan', ['view' => 'today']) }}">همه <i class="fa-solid fa-angle-left"></i></a></div>
      @if($review)
        <a href="{{ route('seo.content.index', ['status' => 'review']) }}" class="seo-callout" style="margin-bottom:10px;padding:10px 12px"><i class="fa-solid fa-feather-pointed"></i><div><b>{{ Fa::n($review) }} مقاله منتظر تأیید شماست</b></div></a>
      @endif
      <div class="seo-rows">
        @forelse($today as $t)
          <div class="seo-item">
            <span class="seo-dot {{ $t->status === 'needs_action' ? 'is-danger' : ($t->isOverdue() ? 'is-warning' : 'is-info') }}"></span>
            <div class="seo-item-main"><div class="seo-item-title">{{ $t->title }}</div><div class="seo-item-sub">{{ $t->automation === 'auto' ? 'ایجنت خودکار انجام می‌دهد' : ($t->automation === 'assisted' ? 'ایجنت پیشنهاد می‌دهد، شما تأیید می‌کنید' : 'نیاز به اقدام شما') }}{{ $t->due_on ? ' · '.Fa::date($t->due_on) : '' }}</div></div>
          </div>
        @empty
          <div class="seo-empty" style="padding:20px"><i class="fa-solid fa-mug-hot"></i>کار سررسیده‌ای نیست.</div>
        @endforelse
      </div>
    </section>
  </div>

  <div class="seo-grid seo-grid-3">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-heart-pulse"></i> سلامت فنی @include('seo::partials.help', ['k' => 'overview.health'])</div><a class="seo-link" href="{{ route('seo.technical') }}">جزئیات <i class="fa-solid fa-angle-left"></i></a></div>
      <div class="seo-grid seo-grid-2" style="gap:12px">
        <div class="seo-pillar"><div class="seo-ring" style="--p:{{ $crawl->score ?? 0 }};--c:var(--{{ ($crawl->score ?? 0) >= 80 ? 'success' : (($crawl->score ?? 0) >= 50 ? 'warning' : 'danger') }})"><span class="seo-num">{{ $crawl ? Fa::n($crawl->score) : '—' }}</span></div><div><div class="seo-pillar-title">امتیاز فنی</div><div class="seo-pillar-sub">{{ $crawl ? Fa::n($crawl->summary['pages'] ?? 0).' صفحه · '.Fa::ago($crawl->created_at) : 'منتظر اولین خزش' }}</div></div></div>
        @php($ps = $speed?->score)
        <div class="seo-pillar"><div class="seo-ring" style="--p:{{ $ps ?? 0 }};--c:var(--{{ ($ps ?? 0) >= 90 ? 'success' : (($ps ?? 0) >= 50 ? 'warning' : 'danger') }})"><span class="seo-num">{{ $ps !== null ? Fa::n($ps) : '—' }}</span></div><div><div class="seo-pillar-title">سرعت موبایل</div><div class="seo-pillar-sub">{{ $speed ? 'PageSpeed · '.Fa::ago($speed->created_at) : 'منتظر اولین سنجش' }}</div></div></div>
      </div>
    </section>
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-bell"></i> هشدارها @include('seo::partials.help', ['k' => 'overview.alerts'])</div><a class="seo-link" href="{{ route('seo.activity') }}">همه <i class="fa-solid fa-angle-left"></i></a></div>
      <div class="seo-rows">
        @forelse($alerts as $a)
          <div class="seo-item"><span class="seo-dot is-{{ $a->level === 'info' ? 'info' : $a->level }}"></span><div class="seo-item-main"><div class="seo-item-title">{{ $a->title }}</div><div class="seo-item-sub">{{ Fa::ago($a->created_at) }}{{ $a->telegram_sent_at ? ' · ارسال‌شده به تلگرام' : '' }}</div></div></div>
        @empty
          <div class="seo-empty" style="padding:20px"><i class="fa-regular fa-bell-slash"></i>هشداری ثبت نشده.</div>
        @endforelse
      </div>
    </section>
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-robot"></i> کار ایجنت‌ها @include('seo::partials.help', ['k' => 'overview.agents'])</div><a class="seo-link" href="{{ route('seo.activity') }}">لاگ کامل <i class="fa-solid fa-angle-left"></i></a></div>
      <div class="seo-rows">
        @forelse($runs as $r)
          <div class="seo-item">
            <span class="seo-agent" title="{{ \Vatan\Seo\Models\Run::AGENTS[$r->agent] ?? $r->agent }}"><i class="fa-solid {{ ['strategist' => 'fa-chess-knight', 'auditor' => 'fa-stethoscope', 'researcher' => 'fa-magnifying-glass', 'writer' => 'fa-feather', 'monitor' => 'fa-satellite-dish', 'reporter' => 'fa-paper-plane', 'publisher' => 'fa-upload'][$r->agent] ?? 'fa-gear' }}"></i></span>
            <div class="seo-item-main"><div class="seo-item-title">{{ \Illuminate\Support\Str::limit($r->summary ?? $r->action, 90) }}</div><div class="seo-item-sub">{{ \Vatan\Seo\Models\Run::AGENTS[$r->agent] ?? $r->agent }} · {{ Fa::ago($r->created_at) }}</div></div>
            <span class="seo-dot is-{{ ['success' => 'success', 'warning' => 'warning', 'failed' => 'danger'][$r->status] ?? 'info' }}"></span>
          </div>
        @empty
          <div class="seo-empty" style="padding:20px"><i class="fa-solid fa-robot"></i>هنوز کاری اجرا نشده.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection

@push('seo-before-scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
@endpush
