@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\Run;
  $seoTitle = 'فعالیت ایجنت‌ها و هزینه';
  $seoIcon = 'fa-wave-square';
  $seoHelp = 'activity.page';
  $seoSubtitle = 'هر کاری که ایجنت‌ها انجام داده‌اند با نتیجه، مدت و هزینه‌ی دقیق؛ و مصرف هوش مصنوعی به تفکیک مدل و کاربرد.';
  $statusTone = ['success' => 'success', 'warning' => 'warning', 'skipped' => 'warning', 'failed' => 'danger', 'running' => 'info'];
  $agentIcons = ['strategist' => 'fa-chess-knight', 'auditor' => 'fa-stethoscope', 'researcher' => 'fa-magnifying-glass', 'writer' => 'fa-feather', 'monitor' => 'fa-satellite-dish', 'reporter' => 'fa-paper-plane', 'publisher' => 'fa-upload', 'system' => 'fa-gear'];
@endphp

@section('seo-page')
<div class="seo-stack">
  <div class="seo-grid seo-grid-main">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-chart-column"></i> هزینه‌ی روزانه‌ی هوش مصنوعی (۳۰ روز)</div></div>
      <div class="seo-chart is-sm"><canvas data-seo-chart="seo-cost-data" data-type="cost"></canvas></div>
      <script type="application/json" id="seo-cost-data">@json($costChart)</script>
    </section>
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-coins"></i> این ماه @include('seo::partials.help', ['k' => 'overview.budget'])</div></div>
      <div class="seo-kpi-value seo-num">{{ Fa::usd($budget['spent']) }}</div>
      <div class="seo-card-sub" style="margin:4px 0 10px">از سقف {{ Fa::usd($budget['cap']) }} · پیش‌بینی پایان ماه {{ Fa::usd($budget['forecast']) }}</div>
      <div class="seo-meter {{ $budget['ratio'] > .9 ? 'is-danger' : ($budget['ratio'] > .7 ? 'is-warn' : '') }}"><span style="width:{{ max(2, round($budget['ratio'] * 100)) }}%"></span></div>
      <hr class="seo-sep">
      @forelse($byPurpose->take(6) as $p)
        <div class="seo-row-gap" style="justify-content:space-between;font-size:12px;padding:3px 0"><span>{{ $p->purpose }} <span class="seo-muted">×{{ Fa::n($p->n) }}</span></span><b class="seo-num">{{ Fa::usd((float) $p->cost) }}</b></div>
      @empty
        <div class="seo-muted" style="font-size:12px">هنوز تماسی با هوش مصنوعی ثبت نشده.</div>
      @endforelse
    </section>
  </div>

  @if($byModel->isNotEmpty())
    <section class="seo-card is-flush">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-microchip"></i> مصرف به تفکیک مدل (این ماه)</div></div>
      <div class="seo-table-wrap"><table class="seo-table">
        <thead><tr><th>مدل</th><th class="hide-sm">نقش</th><th>تعداد</th><th class="hide-sm">توکن ورودی</th><th class="hide-sm">توکن خروجی</th><th class="hide-sm">خطا</th><th>هزینه</th></tr></thead>
        <tbody>@foreach($byModel as $m)<tr><td class="seo-ltr">{{ $m->model }}</td><td class="hide-sm">{{ ['fast' => 'سریع', 'strategist' => 'استراتژیست', 'writer' => 'نویسنده', 'research' => 'پژوهش'][$m->role] ?? $m->role }}</td><td class="seo-num">{{ Fa::n($m->n) }}</td><td class="seo-num hide-sm">{{ Fa::short((int) $m->tin) }}</td><td class="seo-num hide-sm">{{ Fa::short((int) $m->tout) }}</td><td class="seo-num hide-sm">{{ Fa::n($m->errors) }}</td><td class="seo-num"><b>{{ Fa::usd((float) $m->cost) }}</b></td></tr>@endforeach</tbody>
      </table></div>
    </section>
  @endif

  <div class="seo-grid seo-grid-main" style="align-items:start">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-list-ul"></i> لاگ اجراها</div>
        <div class="seo-chips"><a href="{{ route('seo.activity') }}" class="seo-chip {{ ! $agent ? 'is-active' : '' }}">همه</a>@foreach(Run::AGENTS as $k => $l)<a href="{{ route('seo.activity', ['agent' => $k]) }}" class="seo-chip {{ $agent === $k ? 'is-active' : '' }}">{{ $l }}</a>@endforeach</div>
      </div>
      <div class="seo-rows">
        @forelse($runs as $r)
          <div class="seo-item">
            <span class="seo-agent"><i class="fa-solid {{ $agentIcons[$r->agent] ?? 'fa-gear' }}"></i></span>
            <div class="seo-item-main">
              <div class="seo-item-title">{{ $r->summary ?: $r->action }}</div>
              <div class="seo-item-sub">{{ Run::AGENTS[$r->agent] ?? $r->agent }} · {{ $r->scenario?->title ?? $r->action }} · {{ ['schedule' => 'زمان‌بندی', 'manual' => 'دستی', 'telegram' => 'تلگرام', 'system' => 'سیستم'][$r->trigger] ?? $r->trigger }} · {{ Fa::date($r->created_at, true) }}{{ $r->duration_ms ? ' · '.Fa::n(round($r->duration_ms / 1000, 1)).' ثانیه' : '' }}</div>
            </div>
            <div class="seo-item-meta">@if($r->cost_usd > 0)<span class="seo-tag">{{ Fa::usd($r->cost_usd) }}</span>@endif<span class="seo-tag is-{{ $statusTone[$r->status] ?? '' }}">{{ ['success' => 'موفق', 'warning' => 'هشدار', 'skipped' => 'رد شد', 'failed' => 'خطا', 'running' => 'در حال اجرا'][$r->status] ?? $r->status }}</span></div>
          </div>
        @empty
          <div class="seo-empty"><i class="fa-solid fa-robot"></i>هنوز اجرایی ثبت نشده.</div>
        @endforelse
      </div>
      @if($runs->hasPages())<div class="seo-pagination">@if(! $runs->onFirstPage())<a href="{{ $runs->previousPageUrl() }}">قبلی</a>@endif<span class="is-active">{{ Fa::n($runs->currentPage()) }}</span>@if($runs->hasMorePages())<a href="{{ $runs->nextPageUrl() }}">بعدی</a>@endif</div>@endif
    </section>
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-bell"></i> هشدارها</div></div>
      <div class="seo-rows">
        @forelse($alerts as $a)
          <div class="seo-item" style="align-items:flex-start"><span class="seo-dot is-{{ $a->level === 'info' ? 'info' : $a->level }}" style="margin-top:7px"></span><div class="seo-item-main"><div class="seo-item-title">{{ $a->title }}</div>@if($a->body)<div class="seo-item-sub" style="white-space:pre-line">{{ $a->body }}</div>@endif<div class="seo-item-sub">{{ Fa::ago($a->created_at) }}</div></div></div>
        @empty
          <div class="seo-empty" style="padding:20px">هشداری نیست.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection

@push('seo-before-scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
@endpush
