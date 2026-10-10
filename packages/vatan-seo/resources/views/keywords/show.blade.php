@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\Keyword;
  $seoTitle = $kw->keyword;
  $seoIcon = 'fa-key';
  $seoHelp = 'keyword.page';
  $seoSubtitle = ['target' => 'کلمه‌ی هدف', 'candidate' => 'پیشنهاد', 'archived' => 'آرشیو'][$kw->status].' · '.(Keyword::INTENTS[$kw->intent] ?? 'نیت نامشخص').' · منبع: '.(Keyword::SOURCES[$kw->source] ?? $kw->source);
  $advice = data_get($kw->meta, 'onpage_advice');
@endphp

@section('seo-actions')
  <a href="{{ route('seo.keywords.index') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
  @if($kw->status !== 'target')
    <form method="POST" action="{{ route('seo.keywords.update', $kw) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="target"><button class="btn-pro btn-pro-primary"><i class="fa-solid fa-bullseye"></i> هدف‌گذاری</button></form>
  @endif
@endsection

@section('seo-page')
<div class="seo-stack">
  <div class="seo-grid seo-grid-4">
    <div class="seo-card seo-kpi"><span class="seo-kpi-label">رتبه‌ی فعلی @include('seo::partials.help', ['k' => 'keywords.position'])</span><div>@include('seo::partials.pos', ['p' => $kw->current_position])</div><div class="seo-kpi-foot"><span>{{ $kw->rank_checked_at ? 'داده‌ی '.Fa::date($kw->rank_checked_at) : 'هنوز بررسی نشده' }}</span>@include('seo::partials.delta', ['now' => $kw->current_position, 'before' => $kw->previous_position, 'lower' => true, 'abs' => true])</div></div>
    <div class="seo-card seo-kpi"><span class="seo-kpi-label">بهترین رتبه</span><div class="seo-kpi-value seo-num">{{ $kw->best_position ? Fa::n($kw->best_position, 1) : '—' }}</div><div class="seo-kpi-foot"><span>شروع: {{ $kw->start_position ? Fa::n($kw->start_position, 1) : '—' }}</span>@include('seo::partials.delta', ['now' => $kw->current_position, 'before' => $kw->start_position, 'lower' => true, 'abs' => true])</div></div>
    <div class="seo-card seo-kpi"><span class="seo-kpi-label">کلیک ۲۸ روز</span><div class="seo-kpi-value seo-num">{{ Fa::n($kw->clicks_28d) }}</div><div class="seo-kpi-foot"><span>ایمپرشن: {{ Fa::short($kw->impressions_28d) }}</span></div></div>
    <div class="seo-card seo-kpi"><span class="seo-kpi-label">امتیاز فرصت @include('seo::partials.help', ['k' => 'keywords.score'])</span><div class="seo-kpi-value seo-num">{{ Fa::n($kw->ai_score ?? 0) }}</div><div class="seo-kpi-foot"><span>سختی برآوردی: {{ $kw->difficulty ? Fa::n($kw->difficulty) : '—' }}</span></div></div>
  </div>

  <div class="seo-grid seo-grid-main">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-chart-line"></i> روند رتبه @include('seo::partials.help', ['k' => 'keyword.chart'])</div></div>
      @if($ranks->count() > 1)
        <div class="seo-chart"><canvas data-seo-chart="seo-kw-data" data-type="rank"></canvas></div>
        <script type="application/json" id="seo-kw-data">@json($chart)</script>
      @else
        <div class="seo-empty"><i class="fa-solid fa-chart-line"></i><b>داده‌ی کافی نیست</b>رتبه هر روز ثبت می‌شود؛ بعد از دو روز نمودار ظاهر می‌شود.</div>
      @endif
      @if($pages->isNotEmpty())
        <hr class="seo-sep">
        <div class="seo-card-title" style="margin-bottom:8px;font-size:12.5px">صفحاتی که گوگل برای این کلمه نشان می‌دهد @include('seo::partials.help', ['k' => 'keyword.pages'])</div>
        <div class="seo-rows">
          @foreach($pages as $pg)
            <div class="seo-item">@include('seo::partials.pos', ['p' => $pg->position ? round($pg->position, 1) : null])<div class="seo-item-main"><div class="seo-item-title seo-ltr" style="display:block;text-align:right">{{ urldecode($pg->page) }}</div><div class="seo-item-sub">{{ Fa::n($pg->clicks) }} کلیک · {{ Fa::n($pg->impressions) }} ایمپرشن</div></div></div>
          @endforeach
        </div>
        @if($pages->count() > 1)<div class="seo-callout" style="margin-top:10px"><i class="fa-solid fa-triangle-exclamation"></i><div>چند صفحه برای این کلمه دیده می‌شوند؛ احتمال <b>همنوع‌خواری</b>. یک صفحه را هدف کنید و بقیه را به آن لینک دهید.</div></div>@endif
      @endif
    </section>

    <div class="seo-stack">
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-sliders"></i> تنظیمات کلمه</div></div>
        <form method="POST" action="{{ route('seo.keywords.update', $kw) }}" class="seo-stack" style="gap:12px">@csrf @method('PATCH')
          <div class="seo-field"><label>صفحه‌ی هدف @include('seo::partials.help', ['k' => 'keyword.target_url'])</label><input name="target_url" class="seo-input seo-ltr" style="display:block" value="{{ $kw->target_url ?: $kw->ranking_url }}" placeholder="https://..."></div>
          <div class="seo-form-grid">
            <div class="seo-field"><label>نیت جستجو</label><select name="intent" class="seo-select"><option value="">—</option>@foreach(Keyword::INTENTS as $k => $l)<option value="{{ $k }}" @selected($kw->intent === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="seo-field"><label>اولویت</label><select name="priority" class="seo-select">@foreach([5 => 'خیلی بالا', 4 => 'بالا', 3 => 'متوسط', 2 => 'کم', 1 => 'خیلی کم'] as $v => $l)<option value="{{ $v }}" @selected($kw->priority === $v)>{{ $l }}</option>@endforeach</select></div>
          </div>
          <div class="seo-field"><label>یادداشت</label><textarea name="notes" class="seo-textarea" rows="2">{{ $kw->notes }}</textarea></div>
          <button class="btn-pro btn-pro-primary" style="justify-content:center"><i class="fa-solid fa-check"></i> ذخیره</button>
        </form>
      </section>
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-wand-magic-sparkles"></i> پیشنهاد بهینه‌سازی صفحه @include('seo::partials.help', ['k' => 'keyword.advice'])</div></div>
        @if($advice)
          <div class="seo-serp" style="margin-bottom:10px"><div class="u">{{ urldecode((string) ($kw->target_url ?: $kw->ranking_url)) }}</div><div class="t">{{ $advice['title'] ?? '' }}</div><div class="d">{{ $advice['meta_description'] ?? '' }}</div></div>
          <div class="seo-item-sub"><b>H1:</b> {{ $advice['h1'] ?? '—' }}</div>
          @if(!empty($advice['intro']))<div class="seo-task-msg" style="margin-top:8px">{{ $advice['intro'] }}</div>@endif
          @if(!empty($advice['sections']))<div class="seo-item-sub" style="margin-top:8px"><b>سرفصل‌های پیشنهادی:</b> {{ implode('، ', (array) $advice['sections']) }}</div>@endif
          <div class="seo-card-sub" style="margin-top:8px">ساخته‌شده {{ Fa::ago(data_get($kw->meta, 'onpage_advice_at')) }}</div>
        @else
          <div class="seo-card-sub" style="margin-bottom:10px">ایجنت صفحه‌ی هدف را می‌خواند و عنوان، H1، توضیحات و پاراگراف آغازین آماده‌ی کپی پیشنهاد می‌دهد.</div>
        @endif
        <form method="POST" action="{{ route('seo.keywords.advice', $kw) }}" style="margin-top:10px">@csrf<button class="btn-pro btn-pro-ghost" style="width:100%;justify-content:center" data-loading="در حال تحلیل صفحه…"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ $advice ? 'پیشنهاد تازه' : 'دریافت پیشنهاد' }}</button></form>
      </section>
    </div>
  </div>

  <section class="seo-card">
    <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-link"></i> پیشنهاد لینک داخلی @include('seo::partials.help', ['k' => 'keyword.links'])</div>@if($wave)<span class="seo-tag">موج هفته‌ی {{ Fa::n($wave) }}</span>@endif</div>
    @if($links)
      <div class="seo-rows">
        @foreach($links as $l)
          <div class="seo-item"><span class="seo-tag is-info">{{ Fa::percent($l['score'], 0) }}</span><div class="seo-item-main"><div class="seo-item-title">{{ $l['title'] }}</div><div class="seo-item-sub"><span class="seo-ltr">{{ urldecode($l['source']) }}</span> ← انکر پیشنهادی: «{{ $l['anchor'] }}»</div></div></div>
        @endforeach
      </div>
    @else
      <div class="seo-card-sub">{{ ($kw->target_url || $kw->ranking_url) ? 'بعد از خزش سایت، صفحاتی که موضوعشان به این کلمه نزدیک است اینجا پیشنهاد می‌شوند.' : 'اول صفحه‌ی هدف را تعیین کنید.' }}</div>
    @endif
  </section>

  <div class="seo-grid seo-grid-2">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-list-check"></i> برنامه‌ی این کلمه</div></div>
      @forelse($tasks as $task) @include('seo::partials.task', ['task' => $task]) @empty <div class="seo-empty" style="padding:20px">بعد از هدف‌گذاری، ۶ تسک استاندارد برای این کلمه ساخته می‌شود.</div> @endforelse
    </section>
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-feather-pointed"></i> محتوای این کلمه</div>
        @if($kw->status === 'target')<form method="POST" action="{{ route('seo.content.generate') }}">@csrf<input type="hidden" name="keyword_id" value="{{ $kw->id }}"><input type="hidden" name="mode" value="full"><button class="btn-pro btn-pro-primary seo-btn-sm" data-loading="در حال نگارش… (۱ تا ۲ دقیقه)"><i class="fa-solid fa-feather"></i> نوشتن مقاله</button></form>@endif
      </div>
      <div class="seo-rows">
        @forelse($content as $c)
          <a class="seo-item" href="{{ route('seo.content.show', $c) }}"><span class="seo-dot is-{{ ['published' => 'success', 'review' => 'warning', 'failed' => 'danger', 'rejected' => 'danger'][$c->status] ?? 'info' }}"></span><div class="seo-item-main"><div class="seo-item-title">{{ $c->title }}</div><div class="seo-item-sub">{{ \Vatan\Seo\Models\ContentItem::STATUSES[$c->status] }} · {{ Fa::ago($c->created_at) }}</div></div></a>
        @empty
          <div class="seo-empty" style="padding:20px">محتوایی برای این کلمه ساخته نشده.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection

@push('seo-before-scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
@endpush
