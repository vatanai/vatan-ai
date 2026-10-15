@extends('cf::layout')
@php
  use Vatan\Content\Support\Fa;
  $fmtClass = fn ($f) => ['ریلز' => 'cf-fmt-reel', 'اسلایدی' => 'cf-fmt-car', 'پست' => 'cf-fmt-post'][$f] ?? 'cf-fmt-reel';
  $order = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
  $today = now(config('content-factory.host.timezone'))->toDateString();
  $locked = $calendar?->isLocked();
  $productValue = fn ($i) => $i->product_type ? $i->product_type.':'.($i->product_id ?: $i->product_key) : '';
  $fill = fn ($i) => json_encode($i->only(['id', 'code', 'publish_time', 'format', 'pillar', 'sector', 'goal', 'audience', 'idea', 'hook', 'cta', 'style_id', 'music_id',
      'engine', 'build_location', 'note', 'tracking_url', 'caption', 'hashtags', 'cover_text', 'experiment', 'product_label', 'product_url', 'duration_sec']) + [
    'date' => Fa::date($i->publish_date, true, true), 'publish_date' => $i->publish_date->toDateString(), 'stories' => $i->stories ?? [],
    'keywords' => implode('، ', (array) $i->keywords), 'product' => $productValue($i), 'scenes' => $i->scenes ?? [], 'story_specs' => $i->story_specs ?? [],
    'ai_plan' => $i->ai_plan ?? [], 'build_url' => route('cf.calendar.build', $i), 'update_url' => route('cf.calendar.item', $i),
    'production' => $i->production ? route('cf.productions.show', $i->production) : null,
    'status' => Fa::label('production_status', $i->production->status ?? $i->production_status)], JSON_UNESCAPED_UNICODE);
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
        <button type="button" class="cf-day {{ $fmtClass($i->format) }} {{ $i->publish_date->toDateString() === $today ? 'is-today' : '' }}" style="{{ $dim ? 'opacity:.3' : '' }}{{ $i->approval_status === 'rejected' ? ';text-decoration:line-through' : '' }}" data-cf-open="cf-item" data-cf-fill="{{ $fill($i) }}">
          <span class="cf-day-top"><b>{{ Fa::date($i->publish_date) }}</b><span>{{ Fa::digits($i->code) }} · {{ $i->format }}</span></span>
          <span class="cf-day-hook">{{ $i->hook }}</span>
          <span class="cf-day-meta" style="margin-top:2px"><i class="fa-solid fa-box-open"></i> {{ \Illuminate\Support\Str::limit(str_replace('استودیو محصول · ', '', (string) $i->product_label), 28) }}</span>
          <span class="cf-day-meta"><span>{{ $i->styleModel->name_fa ?? $i->style }}</span>@if($i->note)<i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)" title="{{ $i->note }}"></i>@endif
            @if($i->production)@include('cf::partials.status', ['s' => $i->production->status])@endif</span>
        </button>
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
          <td style="white-space:nowrap"><button type="button" class="cf-btn cf-btn-sm" data-cf-open="cf-item" data-cf-fill="{{ $fill($i) }}"><i class="fa-solid fa-pen"></i></button>
            <form method="post" action="{{ route('cf.calendar.build', $i) }}" style="display:inline">@csrf<button class="cf-btn cf-btn-sm cf-btn-primary" title="ساخت"><i class="fa-solid fa-play"></i></button></form></td>
        </tr>
      @endforeach
      </tbody></table></div>
  @endif
</div>

<dialog id="cf-item" class="cf-dialog is-wide" dir="rtl">
  <div class="cf-dialog-body">
    <div class="cf-between"><b style="color:var(--text-h);font-size:15px" data-k="title"></b>
      <div class="cf-row">
        <form method="post" data-k="build-form" class="cf-row">@csrf
          <label class="cf-muted" style="font-size:12px"><input type="checkbox" name="regen_caption" value="1"> کپشن تازه با کلاد</label>
          <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-play"></i> ساخت</button></form>
        @include('cf::partials.help', ['k' => 'calendar.build'])
        <button type="button" class="cf-btn cf-btn-sm" data-cf-close><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>
    <div class="cf-row"><span class="cf-pill" data-k="date"></span><span class="cf-pill is-warning" data-k="status"></span><a class="cf-pill is-info" data-k="production" hidden>مشاهده‌ی تولید <i class="fa-solid fa-arrow-left"></i></a><span class="cf-pill" data-k="experiment" hidden></span></div>
    <div class="cf-flash is-warning" data-k="note" hidden style="margin:0"></div>

    <form method="post" id="cf-item-form" class="cf-stack" style="gap:12px">@csrf @method('PATCH')
      <div class="cf-tabs-inner" role="tablist">
        @foreach(['main' => 'خلاصه و محصول', 'content' => 'هوک و ایده', 'scenes' => 'صحنه‌ها', 'stories' => 'استوری‌ها', 'caption' => 'کپشن', 'build' => 'نقشه‌ی ساخت'] as $tk => $tl)
          <button type="button" role="tab" data-tab="{{ $tk }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $tl }}</button>
        @endforeach
      </div>

      <div class="cf-pane" data-pane="main">
        <div class="cf-grid cf-g3">
          <label class="cf-field"><span>تاریخ میلادی (YYYY-MM-DD) · <b data-k="date-fa" style="color:var(--text-h)"></b></span><input name="publish_date" dir="ltr" pattern="\d{4}-\d{2}-\d{2}"></label>
          <label class="cf-field">ساعت انتشار<input name="publish_time"></label>
          <label class="cf-field">قالب<select name="format"><option>ریلز</option><option>اسلایدی</option><option>پست</option></select></label>
        </div>
        <label class="cf-field" style="margin-top:10px"><span>محصول وطن که این پست تبلیغش می‌کند @include('cf::partials.help', ['k' => 'calendar.product'])</span>
          <select name="product"><option value="">— انتخاب —</option>
            @foreach(collect($catalog)->groupBy('group') as $g => $opts)<optgroup label="{{ $g }}">@foreach($opts as $o)<option value="{{ $o['value'] }}">{{ $o['label'] }}</option>@endforeach</optgroup>@endforeach
          </select></label>
        <label class="cf-field" style="margin-top:10px">لینک محصول (برای دایرکت و رهگیری)<input name="product_url" dir="ltr" placeholder="https://aivatan.com/…"></label>
        <div class="cf-grid cf-g2" style="margin-top:10px">
          <label class="cf-field">سبک ویدیو<select name="style_id"><option value="">—</option>@foreach($styles as $st)<option value="{{ $st->id }}">{{ $st->name_fa }}</option>@endforeach</select></label>
          <label class="cf-field">موسیقی (فقط مجاز)<select name="music_id"><option value="">انتخاب خودکار از کتابخانه</option>@foreach($tracks->where('license_status', 'approved') as $t)<option value="{{ $t->id }}">{{ $t->title }} · {{ $t->mood }}{{ $t->tempo_bpm ? ' · '.$t->tempo_bpm.' BPM' : '' }}</option>@endforeach</select></label>
        </div>
        <div class="cf-grid cf-g3" style="margin-top:10px">
          <label class="cf-field">ستون محتوایی<input name="pillar"></label>
          <label class="cf-field">صنف<input name="sector"></label>
          <label class="cf-field">هدف<input name="goal"></label>
        </div>
        <label class="cf-field" style="margin-top:10px">مخاطب<input name="audience"></label>
        <div class="cf-grid cf-g2" style="margin-top:10px">
          <label class="cf-field"><span>موتور ساخت @include('cf::partials.help', ['k' => 'calendar.engine'])</span><input name="engine"></label>
          <label class="cf-field">محل ساخت<select name="build_location"><option value="mac">مک (ایجنت)</option><option value="server">سرور</option></select></label>
        </div>
        <label class="cf-field" style="margin-top:10px">آزمایش این پست<input name="experiment"></label>
        <label class="cf-field" style="margin-top:10px">یادداشت<textarea name="note"></textarea></label>
      </div>

      <div class="cf-pane" data-pane="content" hidden>
        <label class="cf-field">هوک (۱.۵ ثانیه‌ی اول)<input name="hook"></label>
        <label class="cf-field" style="margin-top:10px">ایده و سناریوی خلاصه<textarea name="idea" style="min-height:110px"></textarea></label>
        <div class="cf-grid cf-g2" style="margin-top:10px">
          <label class="cf-field">دعوت به اقدام<input name="cta"></label>
          <label class="cf-field"><span>کلمه‌ی کلیدی کامنت (با ، جدا کنید) @include('cf::partials.help', ['k' => 'calendar.keywords'])</span><input name="keywords_text"></label>
        </div>
        <label class="cf-field" style="margin-top:10px">متن کاور<input name="cover_text"></label>
        <div class="cf-muted" style="font-size:11.5px;margin-top:8px">لینک رهگیری: <span class="cf-ltr" data-k="tracking_url"></span></div>
      </div>

      <div class="cf-pane" data-pane="scenes" hidden>
        <div class="cf-section-title">صحنه‌ها به زبان ساده @include('cf::partials.help', ['k' => 'calendar.scenes'])</div>
        <ol data-k="scene-list" style="margin:0;padding-inline-start:20px;line-height:2;font-size:12.5px"></ol>
        <details style="margin-top:10px"><summary class="cf-muted" style="cursor:pointer">ویرایش دقیق (JSON)</summary>
          <textarea name="scenes_json" class="cf-input" dir="ltr" style="min-height:280px;font-family:ui-monospace,monospace;font-size:12px;margin-top:8px"></textarea></details>
      </div>

      <div class="cf-pane" data-pane="stories" hidden>
        <div class="cf-section-title">استوری‌های مخصوص این پست @include('cf::partials.help', ['k' => 'calendar.stories'])</div>
        <label class="cf-field">خلاصه (هر خط یک استوری، حداکثر ۳)<textarea name="stories_text"></textarea></label>
        <details style="margin-top:10px"><summary class="cf-muted" style="cursor:pointer">طرح دقیق استوری‌ها (JSON)</summary>
          <textarea name="story_specs_json" class="cf-input" dir="ltr" style="min-height:200px;font-family:ui-monospace,monospace;font-size:12px;margin-top:8px"></textarea></details>
      </div>

      <div class="cf-pane" data-pane="caption" hidden>
        <label class="cf-field">پیش‌نویس کپشن (هنگام «ساخت»، کلاد با پرامپت «کپشن» از روی این می‌نویسد)<textarea name="caption" style="min-height:220px"></textarea></label>
        <label class="cf-field" style="margin-top:10px">هشتگ‌ها<input name="hashtags"></label>
        <a href="{{ route('cf.prompts', ['layer' => 'caption']) }}" class="cf-muted" style="font-size:12px"><i class="fa-solid fa-flask"></i> ویرایش پرامپت کپشن در آزمایشگاه پرامپت</a>
      </div>

      <div class="cf-pane" data-pane="build" hidden>
        <div class="cf-section-title">دقیقاً چه چیزی، با چه هوش و کجا ساخته می‌شود @include('cf::partials.help', ['k' => 'calendar.engine'])</div>
        <div class="cf-table-wrap"><table class="cf-table"><thead><tr><th>مرحله</th><th>ابزار / هوش</th><th>کجا</th><th>پرامپت</th><th>هزینه</th></tr></thead><tbody data-k="plan"></tbody></table></div>
        <div class="cf-muted" style="font-size:12px;margin-top:8px">مدت تقریبی ویدیو: <b data-k="duration"></b> ثانیه</div>
      </div>

      <div class="cf-row" style="border-top:1px solid var(--divider);padding-top:12px">
        @if(! $locked)
          <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره‌ی تغییرات</button>
          <button class="cf-btn cf-btn-danger" name="approval_status" value="rejected"><i class="fa-solid fa-ban"></i> حذف از تقویم</button>
        @else
          <span class="cf-muted"><i class="fa-solid fa-lock"></i> تقویم قفل است؛ برای تغییر نسخه‌ی جدید بسازید. «ساخت» همچنان کار می‌کند.</span>
        @endif
      </div>
    </form>
  </div>
</dialog>
@endif
@endsection

@section('cf-scripts')
<script>
(function () {
  var dlg = document.getElementById('cf-item'); if (!dlg) return;
  var prompts = {scenario: 'سناریو', caption: 'کپشن', qc: 'کنترل کیفیت', story: 'استوری', visual: 'تصویر/کلیپ'};
  dlg.addEventListener('click', function (e) {
    var t = e.target.closest('[data-tab]'); if (!t) return;
    dlg.querySelectorAll('[data-tab]').forEach(function (b) { b.setAttribute('aria-selected', b === t ? 'true' : 'false'); });
    dlg.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.dataset.pane !== t.dataset.tab; });
  });
  var esc = function (x) { var d = document.createElement('div'); d.textContent = x == null ? '' : String(x); return d.innerHTML; };
  var sceneText = function (s) {
    var fa = function (x) { return String(x).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }).replace('.', '٫'); };
    var sec = fa(Math.round((s.dur || 0) / 3) / 10);
    var map = {text: 'متن', card: 'کارت محصول', split: 'قبل/بعد', grid: 'شبکه‌ی ۴ تصویر', stat: 'آمار', list: 'فهرست', quote: 'نقل‌قول', image: 'تصویر تمام‌قد', cta: 'دعوت', cover: 'جلد', tip: 'نکته', compare: 'مقایسه'};
    var body = s.kicker ? s.kicker + ' / ' : '';
    body += s.text || s.title || (s.lines || s.items || []).join(' · ') || s.label || '';
    if (s.keyword) body += ' · کامنت «' + s.keyword + '»';
    var img = s.image || s.after || (s.images ? s.images.length + ' تصویر' : '');
    return '<li><b>' + (map[s.type || s.kind] || s.type || s.kind) + '</b>' + (s.dur ? ' · ' + sec + ' ثانیه' : '') + ' — ' + esc(body) + (img ? ' <span class="cf-muted cf-ltr">' + esc(String(img).replace('storage:', '')) + '</span>' : '') + '</li>';
  };
  window.cfFill = function (dlg, d) {
    var f = document.getElementById('cf-item-form');
    f.action = d.update_url;
    ['publish_date', 'publish_time', 'format', 'pillar', 'sector', 'goal', 'audience', 'idea', 'hook', 'cta', 'style_id', 'music_id', 'engine', 'build_location', 'note', 'caption', 'hashtags', 'cover_text', 'experiment', 'product_url', 'product']
      .forEach(function (k) { if (f.elements[k]) f.elements[k].value = d[k] == null ? '' : d[k]; });
    f.elements['keywords_text'].value = d.keywords || '';
    f.elements['stories_text'].value = (d.stories || []).join('\n');
    f.elements['scenes_json'].value = JSON.stringify(d.scenes || [], null, 1);
    f.elements['story_specs_json'].value = JSON.stringify(d.story_specs || [], null, 1);
    dlg.querySelector('[data-k=title]').textContent = d.code + ' · ' + (d.hook || '');
    dlg.querySelector('[data-k=date]').textContent = d.date + ' · ' + (d.publish_time || '');
    dlg.querySelector('[data-k=date-fa]').textContent = d.date;
    dlg.querySelector('[data-k=status]').textContent = d.status;
    dlg.querySelector('[data-k=tracking_url]').textContent = d.tracking_url || '—';
    dlg.querySelector('[data-k=duration]').textContent = d.duration_sec ? String(d.duration_sec).replace(/\d/g, function (x) { return '۰۱۲۳۴۵۶۷۸۹'[x]; }) : '—';
    var ex = dlg.querySelector('[data-k=experiment]'); ex.hidden = !d.experiment; ex.textContent = '🧪 ' + (d.experiment || '');
    var nt = dlg.querySelector('[data-k=note]'); nt.hidden = !d.note; nt.textContent = d.note || '';
    var pl = dlg.querySelector('[data-k=production]'); pl.hidden = !d.production; if (d.production) pl.href = d.production;
    dlg.querySelector('[data-k=build-form]').action = d.build_url;
    dlg.querySelector('[data-k=scene-list]').innerHTML = (d.scenes || []).map(sceneText).join('') || '<li class="cf-muted">صحنه‌ای تعریف نشده</li>';
    dlg.querySelector('[data-k=plan]').innerHTML = ((d.ai_plan || {}).steps || []).map(function (s) {
      return '<tr><td>' + esc(s.step) + '</td><td>' + esc(s.who) + '</td><td>' + esc(s.where) + '</td><td>' + (s.prompt && s.prompt !== '—' ? '<a href="{{ route('cf.prompts') }}?layer=' + s.prompt + '" style="color:var(--primary)">' + esc(prompts[s.prompt] || s.prompt) + '</a>' : '—') + '</td><td>' + esc(s.cost) + '</td></tr>';
    }).join('') || '<tr><td colspan="5" class="cf-muted">—</td></tr>';
    dlg.querySelectorAll('[data-tab]').forEach(function (b, i) { b.setAttribute('aria-selected', i === 0 ? 'true' : 'false'); });
    dlg.querySelectorAll('[data-pane]').forEach(function (p, i) { p.hidden = i !== 0; });
    @if($locked ?? false) f.querySelectorAll('input,textarea,select').forEach(function (el) { el.disabled = true; }); @endif
  };
})();
</script>
@endsection
