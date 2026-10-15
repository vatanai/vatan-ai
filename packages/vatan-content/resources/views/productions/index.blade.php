@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $disk = \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk')); @endphp

@section('cf-page')
<div class="cf-stack">
  <div class="cf-tabs" style="margin:0">
    <a href="{{ route('cf.productions') }}" class="cf-tab {{ ! $status ? 'is-active' : '' }}">همه <span class="cf-muted">{{ Fa::n($counts->sum()) }}</span></a>
    @foreach(config('content-factory.labels.production_status') as $k => $label)
      @if(($counts[$k] ?? 0) > 0 || in_array($k, ['review', 'approved', 'published'], true))
        <a href="{{ route('cf.productions', ['status' => $k]) }}" class="cf-tab {{ $status === $k ? 'is-active' : '' }}">{{ $label }} <span class="cf-muted">{{ Fa::n($counts[$k] ?? 0) }}</span></a>
      @endif
    @endforeach
  </div>
  @forelse($productions as $p)
    <a href="{{ route('cf.productions.show', $p) }}" class="cf-prod">
      <span class="cf-prod-thumb">@if($p->cover_path)<img src="{{ $disk->url($p->cover_path) }}" alt="" loading="lazy">@else<i class="fa-solid fa-film"></i>@endif</span>
      <span style="min-width:0">
        <span style="display:block;font-weight:800;color:var(--text-h)">{{ $p->title }}</span>
        <span class="cf-muted" style="font-size:12px">@if($p->item){{ Fa::digits($p->item->code) }} · {{ Fa::date($p->item->publish_date, false, true) }} · {{ Fa::digits($p->item->publish_time) }} · @endif{{ $p->format }} · {{ $p->gene['style'] ?? $p->style_slug }}</span>
      </span>
      <span class="cf-row">@include('cf::partials.status', ['s' => $p->status])@if($p->current_version)<span class="cf-pill">v{{ Fa::n($p->current_version) }}</span>@endif</span>
    </a>
  @empty
    <div class="cf-empty"><i class="fa-solid fa-clapperboard"></i>هنوز تولیدی نیست. بعد از تأیید و قفل تقویم، برای هر ردیف یک آیتم تولید ساخته می‌شود.</div>
  @endforelse
</div>
@endsection
