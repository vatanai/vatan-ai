@extends('cf::layout')
@php use Vatan\Content\Support\Fa; @endphp

@section('cf-actions')
  <a href="{{ route('cf.styles') }}" class="cf-btn"><i class="fa-solid fa-arrow-right"></i> بانک سبک‌ها</a>
  @if($s)
    <form method="post" action="{{ route('cf.styles.duplicate', $s) }}">@csrf<button class="cf-btn"><i class="fa-solid fa-clone"></i> کپی</button></form>
    <button type="button" class="cf-btn cf-btn-danger" data-cf-confirm="cf-style-del"><i class="fa-solid fa-trash"></i> حذف</button>
  @endif
@endsection

@section('cf-page')
<div class="cf-stack">
  @if($s)
    <div id="cf-style-del" class="cf-card cf-confirm" hidden>
      <div class="cf-between"><div><b style="color:var(--text-h)">سبک «{{ $s->name_fa }}» حذف شود؟</b>
        <div class="cf-muted" style="font-size:12px;margin-top:4px">{{ Fa::n($s->references->count()) }} مرجع به «بدون سبک» می‌روند و {{ Fa::n($items->count()) }} پست تقویم بدون سبک می‌شوند. اگر فقط نمی‌خواهید استفاده شود، «بایگانی» بهتر است.</div></div>
        <form method="post" action="{{ route('cf.styles.destroy', $s) }}" class="cf-row">@csrf @method('DELETE')
          <button type="button" class="cf-btn" data-cf-confirm="cf-style-del">انصراف</button><button class="cf-btn cf-btn-danger"><i class="fa-solid fa-trash"></i> بله، حذف کن</button></form></div>
    </div>
    <div class="cf-grid cf-g4">
      <div class="cf-card cf-stat"><span class="cf-stat-label">وضعیت</span><span class="cf-stat-value" style="font-size:18px">{{ Fa::label('style_status', $s->status) }}</span></div>
      <div class="cf-card cf-stat"><span class="cf-stat-label">مرجع‌ها</span><span class="cf-stat-value">{{ Fa::n($s->references->count()) }}</span></div>
      <div class="cf-card cf-stat"><span class="cf-stat-label">پست‌های تقویم با این سبک</span><span class="cf-stat-value">{{ Fa::n($items->count()) }}</span><span class="cf-stat-sub">استفاده در ساخت: {{ Fa::n($s->use_count) }}</span></div>
      <div class="cf-card cf-stat"><span class="cf-stat-label">امتیاز عملکرد</span><span class="cf-stat-value">{{ $s->score !== null ? Fa::n((float) $s->score, 1) : '—' }}</span><span class="cf-stat-sub">{{ $s->last_used_at ? 'آخرین استفاده '.Fa::date($s->last_used_at, false, true) : 'هنوز استفاده نشده' }}</span></div>
    </div>
  @endif

  <form method="post" action="{{ $s ? route('cf.styles.update', $s) : route('cf.styles.store') }}" enctype="multipart/form-data" class="cf-card cf-stack" style="gap:12px">@csrf @if($s) @method('PATCH') @endif
    <h3 class="cf-card-title"><i class="fa-solid fa-id-card"></i> شناسنامه‌ی سبک @include('cf::partials.help', ['k' => 'style.show'])</h3>
    @if($s?->cover_path)<img src="{{ $disk->url($s->cover_path) }}" alt="" style="width:180px;border-radius:12px;border:1px solid var(--border)">@endif
    @include('cf::styles.fields', ['s' => $s])
    <div class="cf-row"><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ $s ? 'ذخیره‌ی تغییرات' : 'افزودن سبک' }}</button></div>
  </form>

  @if($s)
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-film"></i> ویدیوهای مرجع این سبک</h3>
      @if($s->references->count())
        <div class="cf-refs">
          @foreach($s->references as $r)
            <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;width:min(280px,80vw)">
              @if($r->sheet_path)<a href="{{ $disk->url($r->sheet_path) }}" target="_blank"><img src="{{ $disk->url($r->sheet_path) }}" alt="" loading="lazy" style="height:auto;width:100%"></a>@endif
              <span class="cf-muted" style="font-size:11px">{{ Fa::n((float) $r->duration, 1) }}ث · {{ Fa::n($r->shot_count) }} شات{{ $r->tempo_bpm ? ' · '.Fa::n($r->tempo_bpm).' BPM' : '' }} · اطمینان: {{ $r->style_confidence }}</span>
              @if($r->analysis)<span style="font-size:11.5px;line-height:1.8">{{ \Illuminate\Support\Str::limit($r->analysis, 220) }}</span>@endif
              <span class="cf-muted cf-ltr" style="font-size:10.5px">{{ $r->moved_path }}</span>
              <div class="cf-row">
                <form method="post" action="{{ route('cf.styles.assign', $r) }}">@csrf @method('PATCH')
                  <select name="style_id" class="cf-input" style="font-size:11px;padding:3px 6px" onchange="this.form.submit()">
                    @foreach($styles as $o)<option value="{{ $o->id }}" @selected($o->id === $r->style_id)>{{ $o->name_fa }}</option>@endforeach<option value="">بدون سبک</option></select></form>
                <form method="post" action="{{ route('cf.styles.reference.delete', $r) }}">@csrf @method('DELETE')<button class="cf-btn cf-btn-sm cf-btn-danger" title="حذف از بانک"><i class="fa-solid fa-trash"></i></button></form>
              </div>
            </div>
          @endforeach
        </div>
      @else <div class="cf-muted">هنوز مرجعی ندارد. ویدیوی نمونه را در 00-inbox بریزید و «تحلیل» را بزنید یا از بخش «بدون سبک» به این سبک منتقل کنید.</div> @endif
    </div>

    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-calendar-days"></i> پست‌هایی که با این سبک ساخته می‌شوند</h3>
      @forelse($items as $i)
        <a href="{{ route('cf.item', $i) }}" class="cf-between" style="padding:8px 0;border-bottom:1px solid var(--divider)"><span><b style="color:var(--text-h)">{{ Fa::digits($i->code) }}</b> · {{ Fa::date($i->publish_date, false, true) }} · {{ $i->hook }}</span>
          @include('cf::partials.status', ['s' => $i->production->status ?? $i->production_status])</a>
      @empty <div class="cf-muted">هیچ پستی این سبک را ندارد.</div> @endforelse
    </div>
  @endif
</div>
@endsection
