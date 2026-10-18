@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $disk = \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk'));
  $pend = (array) (((array) \Vatan\Content\Models\Setting::get('agent', []))['pending'] ?? []); @endphp

@section('cf-actions')
  <form method="post" action="{{ route('cf.jobs.store') }}">@csrf<input type="hidden" name="type" value="ingest_videos">
    <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-film"></i> تحلیل ویدیوهای پوشه‌ی مک @if(!empty($pend['inbox']))<span class="cf-tab-badge">{{ Fa::n($pend['inbox']) }}</span>@endif</button></form>
  <a href="{{ route('cf.styles.create') }}" class="cf-btn"><i class="fa-solid fa-plus"></i> سبک جدید</a>
@endsection

@section('cf-page')
<div class="cf-stack">
  <div class="cf-flash is-success" style="margin:0"><i class="fa-solid fa-folder-open"></i><span>ویدیوی مرجع را در <span class="cf-ltr">content-lab/00-inbox</span> بریزید و «تحلیل ویدیوهای پوشه‌ی مک» را بزنید. هر ویدیو بعد از تحلیل به <span class="cf-ltr">01-styles/&lt;سبک&gt;/refs</span> منتقل می‌شود؛ پس در 00-inbox فقط فایل‌های تحلیل‌نشده می‌مانند.</span></div>
  @include('cf::partials.jobs')
  <div class="cf-between">
    <div class="cf-tabs" style="margin:0">
      <a href="{{ route('cf.styles') }}" class="cf-tab {{ ! $status ? 'is-active' : '' }}">همه <span>{{ Fa::n($all->count()) }}</span></a>
      @foreach(config('content-factory.labels.style_status') as $k => $l)@if($all->where('status', $k)->count())
        <a href="{{ route('cf.styles', ['status' => $k]) }}" class="cf-tab {{ $status === $k ? 'is-active' : '' }}">{{ $l }} <span>{{ Fa::n($all->where('status', $k)->count()) }}</span></a>@endif
      @endforeach
    </div>
    <label class="cf-q-search" style="width:min(320px,100%)"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="جستجوی سبک…" data-style-search></label>
  </div>

  <div class="cf-style-grid">
    @foreach($styles as $s)
      @php $cover = $s->cover_path ?: optional($s->references->first())->sheet_path; @endphp
      <a href="{{ route('cf.styles.show', $s) }}" class="cf-style-card" data-style-name="{{ $s->name_fa }} {{ $s->name_en }} {{ $s->description }}">
        <span class="cf-style-cover">
          @if($cover)<img src="{{ $disk->url($cover) }}" alt="" loading="lazy">@else<i class="fa-solid fa-swatchbook" style="font-size:30px"></i>@endif
          <span class="cf-pill {{ ['active' => 'is-success', 'pending' => 'is-warning', 'testing' => 'is-info'][$s->status] ?? '' }}">{{ Fa::label('style_status', $s->status) }}</span>
        </span>
        <span class="cf-style-body">
          <b>{{ $s->name_fa }}</b>
          <span class="cf-row" style="gap:4px">@foreach(array_slice($s->palette ?? [], 0, 6) as $c)<span class="cf-swatch" style="background:{{ $c }}"></span>@endforeach<span class="cf-muted" style="font-size:11px">{{ $themes[$s->template] ?? '' }}</span></span>
          <p>{{ $s->description }}</p>
        </span>
        <span class="cf-style-foot"><span><i class="fa-solid fa-film"></i> {{ Fa::n($s->references_count) }} مرجع</span><span><i class="fa-solid fa-calendar"></i> {{ Fa::n($usage[$s->id] ?? 0) }} پست</span><span>امتیاز {{ $s->score !== null ? Fa::n((float) $s->score, 1) : '—' }}</span></span>
      </a>
    @endforeach
    <a href="{{ route('cf.styles.create') }}" class="cf-style-new"><span style="text-align:center"><i class="fa-solid fa-plus" style="font-size:26px;display:block;margin-bottom:8px"></i>سبک جدید</span></a>
  </div>

  @if($orphans->count())
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-circle-question"></i> مرجع‌های بدون سبک (01-styles/_unsorted) — سبک را انتخاب کنید</h3>
      <div class="cf-refs">@foreach($orphans as $r)<div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
        @if($r->sheet_path)<a href="{{ $disk->url($r->sheet_path) }}" target="_blank"><img src="{{ $disk->url($r->sheet_path) }}" alt="" title="{{ $r->analysis }}"></a>@endif
        <form method="post" action="{{ route('cf.styles.assign', $r) }}">@csrf @method('PATCH')<select name="style_id" class="cf-input" style="font-size:11px" onchange="this.form.submit()"><option value="">انتخاب سبک…</option>@foreach($all as $o)<option value="{{ $o->id }}">{{ $o->name_fa }}</option>@endforeach</select></form>
      </div>@endforeach</div></div>
  @endif
</div>
@endsection

@section('cf-scripts')
<script>
(function () { var q = document.querySelector('[data-style-search]'); if (!q) return;
  q.addEventListener('input', function () { var v = q.value.trim(); document.querySelectorAll('[data-style-name]').forEach(function (c) { c.hidden = v && c.getAttribute('data-style-name').indexOf(v) < 0; }); }); })();
</script>
@endsection
