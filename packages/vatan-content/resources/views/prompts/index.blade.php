@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $list = $prompts[$selected] ?? collect(); $active = $list->firstWhere('status', 'active');
  $body = $active->body ?? ($list->first()->body ?? ''); preg_match_all('/\{\{\s*([a-z_\.]+)\s*\}\}/u', $body, $vm); $vars = array_values(array_unique($vm[1] ?? [])); @endphp

@section('cf-page')
<div class="cf-grid" style="grid-template-columns:250px minmax(0,1fr);align-items:start">
  <div class="cf-card" style="padding:8px;display:flex;flex-direction:column;gap:2px">
    @foreach($layers as $k => $l)
      @php $act = ($prompts[$k] ?? collect())->firstWhere('status', 'active'); @endphp
      <a href="{{ route('cf.prompts', ['layer' => $k]) }}" class="cf-tab {{ $selected === $k ? 'is-active' : '' }}" style="display:flex;justify-content:space-between">{{ $l }}<span style="opacity:.7">{{ $act ? 'v'.Fa::n($act->version) : '—' }}</span></a>
    @endforeach
  </div>
  <div class="cf-stack">
    <div class="cf-card">
      <h3 class="cf-card-title"><i class="fa-solid fa-flask"></i> {{ $layers[$selected] ?? $selected }} @if($active)<span class="cf-pill is-success">نسخه‌ی فعال: {{ Fa::n($active->version) }}</span>@endif
        <span class="cf-pill">استفاده: {{ Fa::n($active->use_count ?? 0) }}</span></h3>
      <div class="cf-muted" style="font-size:12.5px;line-height:1.9;margin-bottom:10px">{{ $help[$selected] ?? '' }} بخش بالای خط <span class="cf-ltr">--- USER ---</span> دستور سیستمی و بخش پایین پیام کاربر است.</div>
      @if($vars)<div class="cf-row" style="margin-bottom:10px;font-size:12px">متغیرها: @foreach($vars as $vv)<span class="cf-pill cf-ltr">{{ '{'.'{'.$vv.'}'.'}' }}</span>@endforeach</div>@endif
      <form method="post" action="{{ route('cf.prompts.store') }}" class="cf-stack" style="gap:10px">@csrf
        <input type="hidden" name="layer" value="{{ $selected }}">
        <label class="cf-field">متن پرامپت (ذخیره = پیش‌نویس جدید؛ تا «فعال کن» نزنید روی تولید اثری ندارد)<textarea name="body" style="min-height:340px;font-size:12.5px;line-height:2" required>{{ $body }}</textarea></label>
        <label class="cf-field">یادداشت تغییر<input name="notes" placeholder="چه چیزی و چرا عوض شد؟"></label>
        <div><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره به‌عنوان پیش‌نویس</button></div>
      </form>
    </div>
    @if($active)
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-vial"></i> آزمایش نسخه‌ی فعال</h3>
      <form method="post" action="{{ route('cf.prompts.test', $active) }}" class="cf-stack" style="gap:8px">@csrf
        <label class="cf-field">مقدار متغیرها (JSON)<textarea name="vars" dir="ltr" style="min-height:90px;font-family:ui-monospace,monospace;font-size:12px">{{ json_encode(collect($vars)->mapWithKeys(fn ($v) => [$v => ''])->all() ?: (object) [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</textarea></label>
        <div><button class="cf-btn"><i class="fa-solid fa-play"></i> اجرای آزمایشی</button></div></form>
      @if($testResult)<div class="cf-copy" style="margin-top:10px">{{ $testResult['text'] }}</div>@endif
    </div>
    @endif
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-clock-rotate-left"></i> تاریخچه‌ی نسخه‌ها</h3>
      @forelse($list as $pv)
        <div class="cf-between" style="padding:8px 0;border-bottom:1px solid var(--divider)">
          <span>نسخه‌ی {{ Fa::n($pv->version) }} · {{ Fa::date($pv->created_at, false, true) }} · {{ $pv->editor }}@if($pv->notes) · <span class="cf-muted">{{ $pv->notes }}</span>@endif</span>
          <span class="cf-row"><span class="cf-pill {{ $pv->status === 'active' ? 'is-success' : '' }}">{{ Fa::label('prompt_status', $pv->status) }}</span>
            @if($pv->status !== 'active')<form method="post" action="{{ route('cf.prompts.activate', $pv) }}">@csrf<button class="cf-btn cf-btn-sm">{{ $pv->version < ($active->version ?? 0) ? 'بازگردانی' : 'فعال کن' }}</button></form>@endif</span>
        </div>
      @empty <div class="cf-muted">هنوز نسخه‌ای برای این لایه نیست؛ متن را بنویسید و ذخیره کنید.</div> @endforelse
    </div>
  </div>
</div>
<style>@media (max-width:900px){.cf-grid[style*="250px"]{grid-template-columns:1fr!important}}</style>
@endsection
