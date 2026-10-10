@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\Keyword;
  $seoTitle = 'کلمات کلیدی و رتبه';
  $seoIcon = 'fa-key';
  $seoHelp = 'keywords.page';
  $seoSubtitle = 'کلمات هدف را انتخاب کنید تا رتبه‌ی روزانه‌شان پایش شود و برای هر کدام برنامه‌ی کار ساخته شود. ایجنت از روی محصولات و داده‌ی سرچ کنسول پیشنهاد می‌دهد.';
  $targetsCount = (int) ($counts['target'] ?? 0);
@endphp

@section('seo-actions')
  <button type="button" class="btn-pro btn-pro-ghost" data-toggle="seo-add-kw"><i class="fa-solid fa-plus"></i> افزودن دستی</button>
  <form method="POST" action="{{ route('seo.keywords.discover') }}">@csrf
    <button class="btn-pro btn-pro-primary" data-loading="در حال کشف… (۱ تا ۳ دقیقه)"><i class="fa-solid fa-wand-magic-sparkles"></i> کشف هوشمند از محصولات</button>
  </form>
@endsection

@section('seo-page')
<div class="seo-stack">

  <section class="seo-card" id="seo-add-kw" hidden>
    <form method="POST" action="{{ route('seo.keywords.store') }}" class="seo-form-grid">@csrf
      <div class="seo-field full">
        <label>کلمات (هر خط یک کلمه) @include('seo::partials.help', ['k' => 'keywords.add'])</label>
        <textarea name="keywords" class="seo-textarea" rows="4" placeholder="ساخت عکس محصول با هوش مصنوعی&#10;عکاسی صنعتی آنلاین" required></textarea>
      </div>
      <div class="seo-field">
        <label>اضافه شود به</label>
        <select name="as" class="seo-select"><option value="target">کلمات هدف (پایش رتبه + ساخت تسک)</option><option value="candidate">فهرست پیشنهادها</option></select>
      </div>
      <div class="seo-field" style="justify-content:flex-end"><button class="btn-pro btn-pro-primary" style="justify-content:center"><i class="fa-solid fa-check"></i> ذخیره</button></div>
    </form>
  </section>

  <div class="seo-row-gap" style="justify-content:space-between">
    <div class="seo-chips">
      <a href="{{ route('seo.keywords.index', ['tab' => 'targets']) }}" class="seo-chip {{ $tab === 'targets' ? 'is-active' : '' }}"><i class="fa-solid fa-bullseye"></i> کلمات هدف <span class="n">{{ Fa::n($targetsCount) }} / {{ Fa::n($limit) }}</span></a>
      <a href="{{ route('seo.keywords.index', ['tab' => 'candidates']) }}" class="seo-chip {{ $tab === 'candidates' ? 'is-active' : '' }}"><i class="fa-solid fa-lightbulb"></i> پیشنهادها <span class="n">{{ Fa::n($counts['candidate'] ?? 0) }}</span></a>
      <a href="{{ route('seo.keywords.index', ['tab' => 'archived']) }}" class="seo-chip {{ $tab === 'archived' ? 'is-active' : '' }}"><i class="fa-solid fa-box-archive"></i> آرشیو <span class="n">{{ Fa::n($counts['archived'] ?? 0) }}</span></a>
      @include('seo::partials.help', ['k' => 'keywords.tabs'])
    </div>
    <form method="GET" class="seo-row-gap">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <input name="q" value="{{ request('q') }}" class="seo-input" style="width:200px" placeholder="جستجوی کلمه…">
      <select name="intent" class="seo-select" style="width:140px" data-autosubmit><option value="">همه‌ی نیت‌ها</option>@foreach(Keyword::INTENTS as $k => $l)<option value="{{ $k }}" @selected(request('intent') === $k)>{{ $l }}</option>@endforeach</select>
      @if($tab !== 'targets')<select name="source" class="seo-select" style="width:140px" data-autosubmit><option value="">همه‌ی منابع</option>@foreach(Keyword::SOURCES as $k => $l)<option value="{{ $k }}" @selected(request('source') === $k)>{{ $l }}</option>@endforeach</select>@endif
    </form>
  </div>

  @if($tab === 'targets' && $clusters->isNotEmpty())
    <div class="seo-chips" aria-label="خوشه‌ها">
      <span class="seo-muted" style="font-size:11.5px;align-self:center">خوشه‌ها @include('seo::partials.help', ['k' => 'keywords.clusters'])</span>
      @foreach($clusters as $cl)<span class="seo-tag">{{ $cl->name }} · {{ Fa::n($cl->keywords_count) }}</span>@endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('seo.keywords.bulk') }}" class="seo-stack" id="seo-bulk-form">@csrf
  <section class="seo-card is-flush">
    @if($keywords->isEmpty())
      <div class="seo-empty">
        <i class="fa-solid {{ $tab === 'targets' ? 'fa-bullseye' : 'fa-lightbulb' }}"></i>
        @if($tab === 'targets')
          <b>هنوز کلمه‌ی هدفی ندارید</b>از تب «پیشنهادها» بهترین‌ها را انتخاب کنید یا دستی اضافه کنید. سقف پروفایل فعلی: {{ Fa::n($limit) }} کلمه.
        @elseif($tab === 'candidates')
          <b>پیشنهادی وجود ندارد</b>دکمه‌ی «کشف هوشمند از محصولات» را بزنید.
        @else
          <b>آرشیو خالی است</b>
        @endif
      </div>
    @else
      <div class="seo-table-wrap">
        <table class="seo-table">
          <thead>
            <tr>
              <th style="width:34px"><input type="checkbox" data-select-all aria-label="انتخاب همه"></th>
              <th>کلمه</th>
              @if($tab === 'targets')
                <th>رتبه @include('seo::partials.help', ['k' => 'keywords.position'])</th>
                <th class="hide-sm">تغییر</th>
                <th class="hide-sm">روند ۳۰ روز</th>
                <th class="hide-lg">بهترین</th>
                <th class="hide-sm">رشد از شروع @include('seo::partials.help', ['k' => 'keywords.growth'])</th>
              @else
                <th>امتیاز فرصت @include('seo::partials.help', ['k' => 'keywords.score'])</th>
                <th class="hide-sm">نیت @include('seo::partials.help', ['k' => 'keywords.intent'])</th>
                <th class="hide-sm">رتبه‌ی فعلی</th>
                <th class="hide-lg">منبع</th>
              @endif
              <th class="hide-sm">کلیک ۲۸ روز</th>
              <th class="hide-md">ایمپرشن</th>
              <th class="hide-sm"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($keywords as $k)
              <tr>
                <td><input type="checkbox" name="ids[]" value="{{ $k->id }}" data-select-row aria-label="انتخاب"></td>
                <td class="kw">
                  <a href="{{ route('seo.keywords.show', $k) }}">{{ $k->keyword }}</a>
                  <small>
                    @if($tab === 'targets'){{ $k->target_url ? urldecode(parse_url($k->target_url, PHP_URL_PATH) ?: '/') : 'صفحه‌ی هدف تعیین نشده' }}{{ $k->cluster ? ' · '.$k->cluster->name : '' }}
                    @else{{ data_get($k->meta, 'reason') ?: (data_get($k->meta, 'product') ? 'محصول: '.data_get($k->meta, 'product') : '') }}@endif
                  </small>
                </td>
                @if($tab === 'targets')
                  <td>@include('seo::partials.pos', ['p' => $k->current_position])</td>
                  <td class="hide-sm">@include('seo::partials.delta', ['now' => $k->current_position, 'before' => $k->previous_position, 'lower' => true, 'abs' => true])</td>
                  <td class="seo-cell-spark hide-sm"><span class="tone-primary">@include('seo::partials.spark', ['values' => $spark[$k->id] ?? [], 'invert' => true])</span></td>
                  <td class="seo-num hide-lg">{{ $k->best_position ? Fa::n($k->best_position, 1) : '—' }}</td>
                  <td class="hide-sm">@include('seo::partials.delta', ['now' => $k->current_position, 'before' => $k->start_position, 'lower' => true, 'abs' => true])</td>
                @else
                  <td>
                    <div class="seo-row-gap" style="gap:6px"><div class="seo-meter" style="width:60px"><span style="width:{{ (int) $k->ai_score }}%"></span></div><span class="seo-num" style="font-weight:800">{{ Fa::n($k->ai_score ?? 0) }}</span></div>
                  </td>
                  <td class="hide-sm">@if($k->intent)<span class="seo-tag {{ in_array($k->intent, ['transactional', 'commercial'], true) ? 'is-success' : '' }}">{{ Keyword::INTENTS[$k->intent] ?? $k->intent }}</span>@else<span class="seo-muted">—</span>@endif</td>
                  <td class="hide-sm">@include('seo::partials.pos', ['p' => $k->current_position])</td>
                  <td class="hide-lg"><span class="seo-tag">{{ Keyword::SOURCES[$k->source] ?? $k->source }}</span></td>
                @endif
                <td class="seo-num hide-sm">{{ Fa::n($k->clicks_28d) }}</td>
                <td class="seo-num hide-md">{{ Fa::short($k->impressions_28d) }}</td>
                <td class="hide-sm"><a class="seo-icon-btn" href="{{ route('seo.keywords.show', $k) }}" title="جزئیات"><i class="fa-solid fa-angle-left"></i></a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if($keywords->hasPages())
        <div class="seo-pagination">
          @foreach($keywords->getUrlRange(1, $keywords->lastPage()) as $pg => $url)
            <a href="{{ $url }}" class="{{ $pg === $keywords->currentPage() ? 'is-active' : '' }}">{{ Fa::n($pg) }}</a>
          @endforeach
        </div>
      @endif
    @endif
  </section>

  <div class="seo-bulk" role="toolbar">
    <span><b data-selected-count>۰</b> کلمه انتخاب شد</span>
    @if($tab !== 'targets')<button name="action" value="target" class="btn-pro btn-pro-primary seo-btn-sm"><i class="fa-solid fa-bullseye"></i> هدف‌گذاری</button>@endif
    @if($tab !== 'candidates')<button name="action" value="candidate" class="btn-pro btn-pro-ghost seo-btn-sm"><i class="fa-solid fa-lightbulb"></i> انتقال به پیشنهادها</button>@endif
    @if($tab !== 'archived')<button name="action" value="archive" class="btn-pro btn-pro-ghost seo-btn-sm"><i class="fa-solid fa-box-archive"></i> آرشیو</button>@endif
    <button name="action" value="delete" class="btn-pro btn-pro-danger seo-btn-sm" data-confirm="حذف شود؟"><i class="fa-solid fa-trash"></i> حذف</button>
  </div>
  </form>
</div>
@endsection
