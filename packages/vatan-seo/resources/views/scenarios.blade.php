@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\Scenario;
  $seoTitle = 'سناریوهای خودکار';
  $seoIcon = 'fa-robot';
  $seoHelp = 'scenarios.page';
  $seoSubtitle = 'کارهای تکراری که بدون نیاز به تأیید اجرا می‌شوند. فقط کنترلشان کنید: روشن/خاموش، تغییر زمان و دیدن نتیجه‌ی هر اجرا.';
  $statusTone = ['success' => 'success', 'warning' => 'warning', 'skipped' => 'warning', 'failed' => 'danger'];
  $statusLabel = ['success' => 'موفق', 'warning' => 'با هشدار', 'skipped' => 'رد شد', 'failed' => 'خطا', 'running' => 'در حال اجرا'];
@endphp

@section('seo-page')
<div class="seo-stack">
  <div class="seo-callout"><i class="fa-solid fa-clock"></i><div>زمان‌ها به وقت تهران است. زمان‌بند لاراول هر ۵ دقیقه سناریوهای سررسیده را اجرا می‌کند (<span class="seo-kbd">php artisan schedule:run</span> باید روی سرور فعال باشد). سناریوهای دارای <span class="seo-tag is-primary">پیرو بودجه</span> تناوبشان را از پروفایل بودجه می‌گیرند.</div></div>

  @foreach($scenarios->groupBy('pillar') as $pillar => $group)
    <div class="seo-group-title">{{ \Vatan\Seo\Models\Task::PILLARS[$pillar] ?? $pillar }} <span class="count">{{ Fa::n($group->count()) }}</span></div>
    <div class="seo-grid seo-grid-2">
      @foreach($group as $s)
        @php($freq = $effective[$s->id])
        <section class="seo-card" style="{{ $s->is_enabled ? '' : 'opacity:.6' }}">
          <div class="seo-card-head" style="align-items:flex-start">
            <div style="min-width:0">
              <div class="seo-card-title">{{ $s->title }} @include('seo::partials.help', ['text' => $s->why, 'title' => $s->title])</div>
              <div class="seo-card-sub">{{ Scenario::FREQUENCIES[$freq] ?? $freq }}{{ in_array($freq, ['weekly', 'twice_weekly'], true) && $s->weekday !== null ? ' · '.(Scenario::WEEKDAYS[$s->weekday] ?? '') : '' }}{{ $freq === 'monthly' ? ' · روز '.Fa::n($s->day ?? 1).' ماه' : '' }} · ساعت {{ Fa::digits($s->at) }}
                @if(!empty($s->config['profile_key']) && $s->follow_profile)<span class="seo-tag is-primary" style="margin-right:4px">پیرو بودجه</span>@endif</div>
            </div>
            <form method="POST" action="{{ route('seo.scenarios.update', $s) }}">@csrf @method('PATCH')<input type="hidden" name="toggle" value="1"><button class="seo-switch {{ $s->is_enabled ? 'is-on' : '' }}" title="{{ $s->is_enabled ? 'خاموش کردن' : 'روشن کردن' }}" aria-label="روشن/خاموش"></button></form>
          </div>
          <div class="seo-row-gap" style="font-size:11.5px;margin-bottom:10px">
            <span class="seo-tag is-{{ $statusTone[$s->last_status] ?? '' }}">{{ $s->last_status ? $statusLabel[$s->last_status] ?? $s->last_status : 'هنوز اجرا نشده' }}</span>
            <span class="seo-tag"><i class="fa-regular fa-clock"></i>آخرین: {{ Fa::ago($s->last_run_at) }}</span>
            <span class="seo-tag"><i class="fa-solid fa-forward"></i>بعدی: {{ $s->is_enabled ? Fa::until($s->next_run_at) : 'خاموش' }}</span>
            <span class="seo-tag">{{ Fa::n($s->run_count) }} اجرا</span>
          </div>
          @if($s->last_summary)<div class="seo-task-msg {{ $s->last_status === 'failed' ? 'is-danger' : '' }}" style="margin-top:0">{{ $s->last_summary }}</div>@endif

          <div class="seo-row-gap" style="margin-top:12px;justify-content:space-between">
            <button type="button" class="btn-pro btn-pro-ghost seo-btn-sm" data-toggle="seo-sc-{{ $s->id }}"><i class="fa-solid fa-sliders"></i> تنظیم زمان</button>
            <form method="POST" action="{{ route('seo.scenarios.run', $s) }}">@csrf<button class="btn-pro btn-pro-primary seo-btn-sm" data-loading="در حال اجرا…"><i class="fa-solid fa-play"></i> اجرا همین حالا</button></form>
          </div>
          <form method="POST" action="{{ route('seo.scenarios.update', $s) }}" id="seo-sc-{{ $s->id }}" hidden class="seo-form-grid" style="margin-top:12px">@csrf @method('PATCH')
            <div class="seo-field"><label>تناوب</label><select name="frequency" class="seo-select">@foreach(Scenario::FREQUENCIES as $k => $l)<option value="{{ $k }}" @selected($s->frequency === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="seo-field"><label>ساعت</label><input name="at" class="seo-input seo-ltr" style="display:block" value="{{ $s->at }}" placeholder="06:00"></div>
            <div class="seo-field"><label>روز هفته</label><select name="weekday" class="seo-select">@foreach(Scenario::WEEKDAYS as $k => $l)<option value="{{ $k }}" @selected((int) $s->weekday === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="seo-field"><label>روز ماه (ماهانه)</label><input type="number" min="1" max="28" name="day" class="seo-input" value="{{ $s->day ?? 1 }}"></div>
            @if(!empty($s->config['profile_key']))<label class="full seo-row-gap" style="font-size:12px"><input type="checkbox" name="follow_profile" value="1" @checked($s->follow_profile)> تناوب از پروفایل بودجه خوانده شود</label>@endif
            <div class="full"><button class="btn-pro btn-pro-primary seo-btn-sm"><i class="fa-solid fa-check"></i> ذخیره</button></div>
          </form>

          @if(($runs[$s->id] ?? collect())->isNotEmpty())
            <details style="margin-top:10px"><summary class="seo-link" style="cursor:pointer">تاریخچه‌ی اجرا</summary>
              <div class="seo-rows" style="margin-top:6px">@foreach($runs[$s->id]->take(6) as $r)<div class="seo-item" style="padding:7px 0"><span class="seo-dot is-{{ $statusTone[$r->status] ?? 'info' }}"></span><div class="seo-item-main"><div class="seo-item-sub" style="margin:0">{{ \Illuminate\Support\Str::limit($r->summary, 120) }}</div></div><span class="seo-item-meta">{{ Fa::ago($r->created_at) }}</span></div>@endforeach</div>
            </details>
          @endif
        </section>
      @endforeach
    </div>
  @endforeach
</div>
@endsection
