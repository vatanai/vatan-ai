@extends('cf::layout')
@php
  use Vatan\Content\Support\Fa;
  $fmtClass = fn ($f) => ['ریلز' => 'cf-fmt-reel', 'اسلایدی' => 'cf-fmt-car', 'پست' => 'cf-fmt-post'][$f] ?? 'cf-fmt-reel';
  $order = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
  $today = now(config('content-factory.host.timezone'))->toDateString();
  $locked = $calendar?->isLocked();
  $productValue = fn ($i) => $i->product_type ? $i->product_type.':'.($i->product_id ?: $i->product_key) : '';
  $q = fn (array $extra) => route('cf.calendar', array_filter(array_merge(['c' => $calendar?->id, 'view' => $view], $filters, $extra)));
@endphp

@section('cf-actions')
  @if($calendar)
    @if(! $locked)
      <button type="button" class="cf-btn cf-btn-primary" data-cf-confirm="cf-approve-box"><i class="fa-solid fa-lock"></i> تأیید و قفل تقویم</button>
    @else
      <form method="post" action="{{ route('cf.calendar.version', $calendar) }}">@csrf<button class="cf-btn"><i class="fa-solid fa-code-branch"></i> نسخه‌ی جدید برای تغییر</button></form>
    @endif
  @endif
@endsection

@section('cf-page')
@if(! $calendar)
  <div class="cf-empty"><i class="fa-solid fa-calendar-plus"></i>هنوز تقویمی ثبت نشده است. دستور <span class="cf-ltr">php artisan content:seed</span> تقویم پایه را وارد می‌کند.</div>
@else
<div class="cf-stack">
  <div id="cf-approve-box" class="cf-card cf-confirm" hidden>
    <div class="cf-between">
      <div><b style="color:var(--text-h)">تقویم {{ Fa::digits($calendar->version) }} تأیید و قفل شود؟</b>
        <div class="cf-muted" style="font-size:12px;margin-top:4px">برای هر ردیف یک آیتم تولید ساخته می‌شود. تغییر بعدی فقط با نسخه‌ی جدید ممکن است.</div></div>
      <form method="post" action="{{ route('cf.calendar.approve', $calendar) }}" class="cf-row">@csrf
        <button type="button" class="cf-btn" data-cf-confirm="cf-approve-box">انصراف</button>
        <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-check"></i> بله، تأیید و قفل کن</button></form>
    </div>
  </div>

  <div class="cf-card">
    <div class="cf-between">
      <div class="cf-row">
        <b style="color:var(--text-h);font-size:15px">{{ $calendar->title }}</b>
        <span class="cf-pill {{ $locked ? 'is-success' : 'is-warning' }}"><i class="fa-solid {{ $locked ? 'fa-lock' : 'fa-pen' }}"></i> {{ Fa::label('calendar_status', $calendar->status) }}</span>
        <span class="cf-pill">نسخه {{ Fa::digits($calendar->version) }}</span>
        <span class="cf-muted">{{ Fa::date($calendar->starts_on, true) }} تا {{ Fa::date($calendar->ends_on, true) }} · {{ Fa::n($items->count()) }} پست · {{ Fa::n($balance['stories']) }} استوری · هدف: {{ $calendar->goal }}</span>
      </div>
      @if($calendars->count() > 1)
        <form method="get" class="cf-row"><select name="c" class="cf-input" style="width:auto" onchange="this.form.submit()">
          @foreach($calendars as $c)<option value="{{ $c->id }}" @selected($c->id === $calendar->id)>{{ Fa::digits($c->version) }} · {{ Fa::label('calendar_status', $c->status) }}</option>@endforeach
        </select></form>
      @endif
    </div>
    @if($calendar->brief)
      <details style="margin-top:10px"><summary class="cf-muted" style="cursor:pointer;font-size:12.5px">بریف تقویم: مخاطب، لحن، آزمایش‌های هفتگی و مناسبت‌ها</summary>
        <div class="cf-kv" style="margin-top:10px">
          @foreach(['audience' => 'مخاطب', 'tone' => 'لحن', 'cadence' => 'ریتم انتشار', 'publish' => 'انتشار', 'faces' => 'چهره'] as $k => $l)
            @if(!empty($calendar->brief[$k]))<div><b>{{ $l }}</b>{{ $calendar->brief[$k] }}</div>@endif
          @endforeach
          @if(!empty($calendar->brief['experiments']))<div style="grid-column:1/-1"><b>آزمایش‌های هفتگی</b>{{ implode(' · ', $calendar->brief['experiments']) }}</div>@endif
          @if(!empty($calendar->brief['occasions']))<div style="grid-column:1/-1"><b>مناسبت‌ها</b>{{ implode(' · ', $calendar->brief['occasions']) }}</div>@endif
        </div></details>
    @endif
  </div>

  <div class="cf-grid cf-g4">
    @foreach(['format' => 'قالب', 'pillar' => 'ستون محتوایی', 'sector' => 'صنف', 'goal' => 'هدف'] as $k => $label)
      @php $max = max(1, $balance[$k]->max() ?? 1); @endphp
      <div class="cf-card"><h3 class="cf-card-title">{{ $label }} @if($loop->first)@include('cf::partials.help', ['k' => 'calendar.balance'])@endif</h3><div class="cf-bars">
        @foreach($balance[$k] as $name => $c)
          <a class="cf-bar" href="{{ $q([$k => ($filters[$k] ?? null) ? null : $name]) }}" title="فیلتر"><span>{{ $name }}</span><span class="cf-bar-track"><span class="cf-bar-fill" style="display:block;width:{{ round($c / $max * 100) }}%"></span></span><span class="cf-num">{{ Fa::n($c) }}</span></a>
        @endforeach
      </div></div>
    @endforeach
  </div>

  @if($balance['warnings'])
    <div class="cf-flash is-warning" style="margin:0"><i class="fa-solid fa-scale-unbalanced"></i><div><b>کنترل توازن:</b> @foreach($balance['warnings'] as $w)<div>{{ $w }}</div>@endforeach</div></div>
  @endif

  <div class="cf-between">
    <div class="cf-tabs" style="margin:0">
      <a href="{{ $q(['view' => 'calendar']) }}" class="cf-tab {{ $view !== 'table' ? 'is-active' : '' }}"><i class="fa-solid fa-calendar"></i> نمای تقویم</a>
      <a href="{{ $q(['view' => 'table']) }}" class="cf-tab {{ $view === 'table' ? 'is-active' : '' }}"><i class="fa-solid fa-table"></i> نمای جدول</a>
    </div>
    <div class="cf-row cf-muted" style="font-size:12px">
      @foreach(array_filter($filters) as $fk => $fv)<a class="cf-pill is-info" href="{{ $q([$fk => null]) }}">{{ $fv }} <i class="fa-solid fa-xmark"></i></a>@endforeach
      <span class="cf-pill" style="border-top:3px solid var(--primary)">ریلز</span><span class="cf-pill" style="border-top:3px solid var(--info)">اسلایدی</span><span class="cf-pill" style="border-top:3px solid var(--warning)">پست</span>
    </div>
  </div>

  @if($view !== 'table')
    <div class="cf-cal">
      @foreach($order as $wd)<div class="cf-cal-wd">{{ $wd }}</div>@endforeach
      @php $first = $items->first(); $off = $first ? array_search(Fa::weekday($first->publish_date), $order, true) : 0; @endphp
      @for($k = 0; $k < $off; $k++)<div class="cf-day is-ghost" aria-hidden="true"></div>@endfor
      @foreach($items as $i)
        @php $dim = $filtered->doesntContain($i); @endphp
        <a href="{{ route('cf.item', $i) }}" class="cf-day {{ $fmtClass($i->format) }} {{ $i->publish_date->toDateString() === $today ? 'is-today' : '' }}" style="{{ $dim ? 'opacity:.3' : '' }}{{ $i->approval_status === 'rejected' ? ';text-decoration:line-through' : '' }}">
          <span class="cf-day-top"><b>{{ Fa::date($i->publish_date) }}</b><span>{{ Fa::digits($i->code) }} · {{ $i->format }}</span></span>
          <span class="cf-day-hook">{{ $i->hook }}</span>
          <span class="cf-day-meta" style="margin-top:2px"><i class="fa-solid fa-box-open"></i> {{ \Illuminate\Support\Str::limit(str_replace('استودیو محصول · ', '', (string) $i->product_label), 28) }}</span>
          <span class="cf-day-meta"><span>{{ $i->styleModel->name_fa ?? $i->style }}</span>@if($i->note)<i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)" title="{{ $i->note }}"></i>@endif
            @if($i->production)@include('cf::partials.status', ['s' => $i->production->status])@endif</span>
        </a>
      @endforeach
    </div>
  @else
    <div class="cf-table-wrap"><table class="cf-table" style="min-width:1500px">
      <thead><tr><th>#</th><th>تاریخ</th><th>ساعت</th><th>قالب</th><th>محصول وطن</th><th>سبک</th><th>ستون</th><th>صنف</th><th>هدف</th><th>هوک</th><th>ایده</th><th>دعوت · کلمه</th><th>موسیقی</th><th>موتور و محل ساخت</th><th>استوری‌ها</th><th>آزمایش</th><th>وضعیت</th><th></th></tr></thead>
      <tbody>
      @foreach($filtered as $i)
        <tr style="{{ $i->approval_status === 'rejected' ? 'opacity:.45' : '' }}">
          <td class="cf-num">{{ Fa::digits($i->code) }}</td><td style="white-space:nowrap">{{ Fa::date($i->publish_date, false, true) }}</td><td class="cf-num">{{ Fa::digits($i->publish_time) }}</td>
          <td><span class="cf-pill">{{ $i->format }}</span></td><td style="min-width:170px">{{ $i->product_label }}</td><td>{{ $i->styleModel->name_fa ?? $i->style }}</td>
          <td>{{ $i->pillar }}</td><td>{{ $i->sector }}</td><td>{{ $i->goal }}</td>
          <td style="font-weight:700;color:var(--text-h);min-width:180px">{{ $i->hook }}</td><td style="min-width:260px">{{ $i->idea }}</td>
          <td>{{ $i->cta }}@if($i->keywords)<div class="cf-pill" style="margin-top:4px">«{{ implode('»، «', $i->keywords) }}»</div>@endif</td>
          <td>{{ $i->musicTrack->title ?? $i->music_mood }}</td><td style="min-width:200px;font-size:11.5px">{{ $i->engine }}</td>
          <td style="min-width:200px">@foreach($i->stories ?? [] as $k => $st)<div>{{ Fa::n($k + 1) }}. {{ $st }}</div>@endforeach</td>
          <td style="font-size:11.5px">{{ $i->experiment }}</td>
          <td>@include('cf::partials.status', ['s' => $i->production->status ?? $i->production_status])</td>
          <td style="white-space:nowrap"><a class="cf-btn cf-btn-sm" href="{{ route('cf.item', $i) }}" title="صفحه‌ی کامل پست"><i class="fa-solid fa-pen"></i></a>
            <form method="post" action="{{ route('cf.item.build', $i) }}" style="display:inline">@csrf<button class="cf-btn cf-btn-sm cf-btn-primary" title="ساخت"><i class="fa-solid fa-play"></i></button></form></td>
        </tr>
      @endforeach
      </tbody></table></div>
  @endif
</div>


@endif
@endsection
