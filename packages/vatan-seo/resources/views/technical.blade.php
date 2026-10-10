@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\Task;
  $seoTitle = 'سلامت فنی';
  $seoIcon = 'fa-screwdriver-wrench';
  $seoHelp = 'technical.page';
  $seoSubtitle = 'نتیجه‌ی آخرین خزش سایت، سنجش سرعت و پایش سلامت روزانه. هر مشکل به تسک مربوطش در برنامه‌ی کار وصل است.';
  $mobile = $speed ? collect($speed->pages)->firstWhere('strategy', 'mobile') : null;
  $desktop = $speed ? collect($speed->pages)->firstWhere('strategy', 'desktop') : null;
  $levelTone = ['danger' => 'danger', 'warning' => 'warning', 'info' => 'info'];
@endphp

@section('seo-actions')
  @php($crawlScenario = \Vatan\Seo\Models\Scenario::where('site_id', $site->id)->where('key', 'crawl_audit')->first())
  @php($speedScenario = \Vatan\Seo\Models\Scenario::where('site_id', $site->id)->where('key', 'pagespeed')->first())
  @if($speedScenario)<form method="POST" action="{{ route('seo.scenarios.run', $speedScenario) }}">@csrf<button class="btn-pro btn-pro-ghost" data-loading="در حال سنجش سرعت… (۱ تا ۲ دقیقه)"><i class="fa-solid fa-gauge-high"></i> سنجش سرعت</button></form>@endif
  @if($crawlScenario)<form method="POST" action="{{ route('seo.scenarios.run', $crawlScenario) }}">@csrf<button class="btn-pro btn-pro-primary" data-loading="در حال خزش سایت… (چند دقیقه)"><i class="fa-solid fa-spider"></i> خزش همین حالا</button></form>@endif
@endsection

@section('seo-page')
<div class="seo-stack">
  <div class="seo-grid seo-grid-4">
    <div class="seo-card seo-kpi">
      <span class="seo-kpi-label">امتیاز فنی @include('seo::partials.help', ['k' => 'technical.score'])</span>
      <div class="seo-pillar"><div class="seo-ring" style="--p:{{ $crawl->score ?? 0 }};--c:var(--{{ ($crawl->score ?? 0) >= 80 ? 'success' : (($crawl->score ?? 0) >= 50 ? 'warning' : 'danger') }})"><span class="seo-num">{{ $crawl ? Fa::n($crawl->score) : '—' }}</span></div>
        <div class="seo-pillar-sub">{{ $crawl ? Fa::n($crawl->summary['pages'] ?? 0).' صفحه خزیده شد' : 'هنوز خزشی انجام نشده' }}<br>{{ $crawl ? Fa::ago($crawl->created_at) : '' }}</div></div>
    </div>
    <div class="seo-card seo-kpi">
      <span class="seo-kpi-label">سرعت موبایل @include('seo::partials.help', ['k' => 'technical.speed'])</span>
      <div class="seo-pillar"><div class="seo-ring" style="--p:{{ $mobile['scores']['performance'] ?? 0 }};--c:var(--{{ ($mobile['scores']['performance'] ?? 0) >= 90 ? 'success' : (($mobile['scores']['performance'] ?? 0) >= 50 ? 'warning' : 'danger') }})"><span class="seo-num">{{ $mobile ? Fa::n($mobile['scores']['performance']) : '—' }}</span></div>
        <div class="seo-pillar-sub">دسکتاپ: {{ $desktop ? Fa::n($desktop['scores']['performance']) : '—' }}<br>سئو (Lighthouse): {{ $mobile ? Fa::n($mobile['scores']['seo']) : '—' }}</div></div>
    </div>
    <div class="seo-card seo-kpi">
      <span class="seo-kpi-label">Core Web Vitals @include('seo::partials.help', ['k' => 'technical.cwv'])</span>
      @php($lcp = $mobile['field']['lcp_ms'] ?? $mobile['lab']['lcp_ms'] ?? null) @php($cls = $mobile['field']['cls'] ?? $mobile['lab']['cls'] ?? null) @php($inp = $mobile['field']['inp_ms'] ?? null)
      <div class="seo-stack" style="gap:6px;font-size:12px">
        <div class="seo-row-gap" style="justify-content:space-between"><span>LCP</span><span class="seo-tag {{ $lcp === null ? '' : ($lcp <= 2500 ? 'is-success' : ($lcp <= 4000 ? 'is-warning' : 'is-danger')) }}">{{ $lcp ? Fa::n(round($lcp / 1000, 1)).' ث' : '—' }}</span></div>
        <div class="seo-row-gap" style="justify-content:space-between"><span>INP</span><span class="seo-tag {{ $inp === null ? '' : ($inp <= 200 ? 'is-success' : ($inp <= 500 ? 'is-warning' : 'is-danger')) }}">{{ $inp ? Fa::n($inp).' ms' : 'نیاز به داده‌ی کاربر واقعی' }}</span></div>
        <div class="seo-row-gap" style="justify-content:space-between"><span>CLS</span><span class="seo-tag {{ $cls === null ? '' : ($cls <= .1 ? 'is-success' : ($cls <= .25 ? 'is-warning' : 'is-danger')) }}">{{ $cls !== null ? Fa::n(round($cls, 2), 2) : '—' }}</span></div>
      </div>
    </div>
    <div class="seo-card seo-kpi">
      <span class="seo-kpi-label">پایش سلامت روزانه @include('seo::partials.help', ['k' => 'technical.health'])</span>
      <div class="seo-stack" style="gap:6px;font-size:12px">
        @forelse((array) ($health->summary ?? []) as $key => $h)
          <div class="seo-row-gap" style="justify-content:space-between"><span>{{ ['home' => 'صفحه‌ی اصلی', 'robots' => 'robots.txt', 'sitemap' => 'نقشه‌ی سایت', 'https' => 'HTTPS'][$key] ?? $key }}</span><i class="fa-solid {{ $h['pass'] === false ? 'fa-circle-xmark tone-danger' : ($h['pass'] ? 'fa-circle-check tone-success' : 'fa-circle-question seo-muted') }}" title="{{ $h['message'] }}"></i></div>
        @empty
          <span class="seo-muted">هنوز اجرا نشده</span>
        @endforelse
      </div>
    </div>
  </div>

  <div class="seo-grid seo-grid-main" style="align-items:start">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-triangle-exclamation"></i> مشکلات پیدا شده در خزش @include('seo::partials.help', ['k' => 'technical.issues'])</div></div>
      @if($crawl && $crawl->issues)
        <div class="seo-rows">
          @foreach($crawl->issues as $key => $issue)
            <details class="seo-item" style="display:block">
              <summary style="display:flex;align-items:center;gap:10px;cursor:pointer;list-style:none">
                <span class="seo-dot is-{{ $levelTone[$issue['level']] ?? 'info' }}"></span>
                <span class="seo-item-main"><span class="seo-item-title">{{ $issue['title'] }}</span></span>
                <span class="seo-tag is-{{ $levelTone[$issue['level']] ?? 'info' }}">{{ Fa::n($issue['count']) }} صفحه</span>
              </summary>
              <div style="margin-top:8px;padding-right:18px">@foreach(array_slice($issue['urls'], 0, 15) as $u)<div class="seo-ltr seo-muted" style="display:block;text-align:right;font-size:11.5px">{{ urldecode($u) }}</div>@endforeach</div>
            </details>
          @endforeach
        </div>
      @elseif($crawl)
        <div class="seo-empty"><i class="fa-solid fa-circle-check"></i><b>مشکلی پیدا نشد</b></div>
      @else
        <div class="seo-empty"><i class="fa-solid fa-spider"></i><b>هنوز خزشی انجام نشده</b>دکمه‌ی «خزش همین حالا» را بزنید یا منتظر اجرای هفتگی بمانید.</div>
      @endif
      @if($crawl && !empty($crawl->summary['schema_types']))
        <hr class="seo-sep">
        <div class="seo-card-sub" style="margin-bottom:6px">اسکیماهای موجود در سایت:</div>
        <div class="seo-chips">@foreach($crawl->summary['schema_types'] as $t)<span class="seo-tag is-info">{{ $t }}</span>@endforeach</div>
      @endif
      @if($crawlHistory->count() > 1)
        <hr class="seo-sep">
        <div class="seo-card-sub" style="margin-bottom:6px">روند امتیاز فنی:</div>
        <div class="tone-success" style="max-width:320px">@include('seo::partials.spark', ['values' => $crawlHistory->pluck('score')->all()])</div>
      @endif
    </section>

    <div class="seo-stack">
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-list-check"></i> چک‌لیست زیرساخت</div><a class="seo-link" href="{{ route('seo.plan', ['pillar' => 'infrastructure', 'view' => 'all']) }}">برنامه‌ی کار <i class="fa-solid fa-angle-left"></i></a></div>
        @foreach($tasks as $cat => $items)
          <div class="seo-group-title">{{ Task::CATEGORIES[$cat] ?? $cat }} <span class="count">{{ Fa::n($items->where('status', 'done')->count()) }}/{{ Fa::n($items->count()) }}</span></div>
          @foreach($items as $t)
            <div class="seo-check {{ $t->status === 'done' ? 'ok' : ($t->status === 'needs_action' ? 'no' : '') }}" title="{{ $t->last_message }}">
              <i class="fa-solid {{ $t->status === 'done' ? 'fa-circle-check' : ($t->status === 'needs_action' ? 'fa-circle-xmark' : 'fa-circle seo-muted') }}" style="{{ $t->status === 'todo' ? 'opacity:.3' : '' }}"></i>
              <span>{{ $t->title }}</span>
            </div>
          @endforeach
        @endforeach
      </section>
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-robot"></i> llms.txt @include('seo::partials.help', ['k' => 'technical.llms'])</div></div>
        @if($llms)
          <pre class="seo-pre" id="seo-llms">{{ $llms }}</pre>
          <div class="seo-row-gap" style="margin-top:8px"><button type="button" class="btn-pro btn-pro-ghost seo-btn-sm" data-copy="seo-llms"><i class="fa-regular fa-copy"></i> کپی</button><a class="seo-link" href="{{ url('/llms.txt') }}" target="_blank" rel="noopener">مشاهده‌ی /llms.txt <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
        @endif
        <form method="POST" action="{{ route('seo.technical.llms') }}" style="margin-top:10px">@csrf<button class="btn-pro btn-pro-ghost" style="width:100%;justify-content:center" data-loading="در حال ساخت…"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ $llms ? 'ساخت دوباره' : 'ساخت با هوش مصنوعی' }}</button></form>
      </section>
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-robot"></i> دیده‌شدن در پاسخ‌های AI @include('seo::partials.help', ['k' => 'technical.geo'])</div>@if(!empty($geo['at']))<span class="seo-tag {{ ($geo['mentioned'] ?? 0) > 0 ? 'is-success' : 'is-warning' }}">{{ Fa::n($geo['mentioned'] ?? 0) }} از {{ Fa::n($geo['asked'] ?? 0) }}</span>@endif</div>
        @if(!empty($geo['results']))
          @foreach($geo['results'] as $r)
            <div class="seo-check {{ !empty($r['mentioned']) ? 'ok' : 'no' }}"><i class="fa-solid {{ !empty($r['mentioned']) ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i><span>{{ $r['keyword'] }}@if(!empty($r['recommended']))<span class="seo-muted" style="display:block;font-size:10.5px">پیشنهادهای AI: {{ implode('، ', array_slice($r['recommended'], 0, 3)) }}</span>@endif</span></div>
          @endforeach
          <div class="seo-card-sub" style="margin-top:6px">آخرین پایش: {{ Fa::ago($geo['at']) }}</div>
          @if(count($geoHistory) > 1)<div class="tone-success" style="margin-top:6px">@include('seo::partials.spark', ['values' => array_column($geoHistory, 'mentioned')])</div>@endif
        @else
          <div class="seo-card-sub">ایجنت پژوهش (Grok با جستجوی زنده) سؤال کاربران را برای ۳ کلمه‌ی هدف می‌پرسد و بررسی می‌کند آیا وطن پیشنهاد می‌شود. از هفته‌ی ۷ هر دو هفته خودکار.</div>
        @endif
        <form method="POST" action="{{ route('seo.technical.geo') }}" style="margin-top:10px">@csrf<button class="btn-pro btn-pro-ghost" style="width:100%;justify-content:center" data-loading="در حال پرسش از هوش مصنوعی…"><i class="fa-solid fa-satellite-dish"></i> پایش همین حالا</button></form>
      </section>
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-chess"></i> رقبای واقعی در گوگل @include('seo::partials.help', ['k' => 'technical.competitors'])</div></div>
        @forelse($competitors as $c)
          <div class="seo-item"><span class="seo-tag">{{ Fa::n($c['appearances']) }}×</span><div class="seo-item-main"><div class="seo-item-title seo-ltr" style="display:block;text-align:right">{{ $c['domain'] }}</div>@if($c['note'])<div class="seo-item-sub">{{ $c['note'] }}</div>@endif</div></div>
        @empty
          <div class="seo-card-sub">بر اساس نتایج زنده‌ی گوگل برای کلمات هدف شما پیدا می‌شوند (نه رقبای تجاری فرضی).</div>
        @endforelse
        @if($competitorsAt)<div class="seo-card-sub" style="margin-top:6px">{{ Fa::ago($competitorsAt) }}</div>@endif
        <form method="POST" action="{{ route('seo.technical.competitors') }}" style="margin-top:10px">@csrf<button class="btn-pro btn-pro-ghost" style="width:100%;justify-content:center" data-loading="در حال جستجوی نتایج گوگل…"><i class="fa-solid fa-magnifying-glass"></i> {{ $competitors ? 'به‌روزرسانی' : 'کشف رقبا' }}</button></form>
      </section>
      @if($cannibal)
        <section class="seo-card">
          <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-code-compare"></i> همنوع‌خواری @include('seo::partials.help', ['k' => 'technical.cannibal'])</div></div>
          @foreach(array_slice($cannibal, 0, 6) as $c)
            <div class="seo-item" style="display:block"><div class="seo-item-title">{{ $c['query'] }}</div>@foreach($c['pages'] as $pg)<div class="seo-item-sub seo-ltr" style="display:block;text-align:right">{{ Fa::percent($pg['share'], 0) }} · {{ urldecode(parse_url($pg['url'], PHP_URL_PATH) ?: '/') }}</div>@endforeach</div>
          @endforeach
        </section>
      @endif
    </div>
  </div>
</div>
@endsection
