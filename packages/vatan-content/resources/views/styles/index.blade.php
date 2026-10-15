@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $disk = \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk'));
  $pend = (array) (((array) \Vatan\Content\Models\Setting::get('agent', []))['pending'] ?? []); @endphp

@section('cf-actions')
  <form method="post" action="{{ route('cf.jobs.store') }}">@csrf<input type="hidden" name="type" value="ingest_videos">
    <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-film"></i> تحلیل ویدیوهای پوشه‌ی مک @if(!empty($pend['inbox']))<span class="cf-tab-badge">{{ Fa::n($pend['inbox']) }}</span>@endif</button></form>
  <button type="button" class="cf-btn" data-cf-open="cf-style-new"><i class="fa-solid fa-plus"></i> سبک جدید</button>
@endsection

@section('cf-page')
<div class="cf-stack">
  <div class="cf-flash is-success" style="margin:0"><i class="fa-solid fa-folder-open"></i><span>ویدیوی مرجع را در <span class="cf-ltr">content-lab/00-inbox</span> بریزید و «تحلیل ویدیوهای پوشه‌ی مک» را بزنید. هر ویدیو بعد از تحلیل به <span class="cf-ltr">01-styles/&lt;سبک&gt;/refs</span> منتقل می‌شود؛ پس در 00-inbox فقط فایل‌های تحلیل‌نشده می‌مانند.</span></div>
  @include('cf::partials.jobs')
  @foreach($styles as $s)
    <div class="cf-card cf-style" id="style-{{ $s->id }}">
      <div class="cf-between">
        <div class="cf-row"><b style="font-size:15px;color:var(--text-h)">{{ $s->name_fa }}</b><span class="cf-muted cf-ltr">{{ $s->name_en }}</span>
          <span class="cf-pill {{ ['active' => 'is-success', 'pending' => 'is-warning', 'testing' => 'is-info'][$s->status] ?? '' }}">{{ Fa::label('style_status', $s->status) }}</span>
          <span class="cf-pill">قالب رندر: {{ $themes[$s->template] ?? '—' }}</span>
          <span class="cf-pill">{{ Fa::n($s->references->count()) }} مرجع</span><span class="cf-pill">استفاده: {{ Fa::n($s->use_count) }}</span>
          <span class="cf-pill">امتیاز: {{ $s->score !== null ? Fa::n((float) $s->score, 1) : 'نامشخص' }}</span>
          <span class="cf-muted cf-ltr" style="font-size:11px">01-styles/{{ $s->folder ?: $s->slug }}</span></div>
        <div class="cf-row">
          <form method="post" action="{{ route('cf.styles.update', $s) }}" class="cf-row">@csrf @method('PATCH')<input type="hidden" name="only_status" value="1">
            @if($s->status !== 'active')<button name="status" value="active" class="cf-btn cf-btn-sm cf-btn-primary"><i class="fa-solid fa-check"></i> فعال</button>@endif
            @if($s->status !== 'testing')<button name="status" value="testing" class="cf-btn cf-btn-sm">آزمایشی</button>@endif
            @if($s->status !== 'archived')<button name="status" value="archived" class="cf-btn cf-btn-sm">بایگانی</button>@endif</form>
          <button type="button" class="cf-btn cf-btn-sm" data-cf-confirm="cf-edit-{{ $s->id }}"><i class="fa-solid fa-pen"></i> ویرایش جزئیات</button>
        </div>
      </div>
      <div style="line-height:1.9;color:var(--text-h)">{{ $s->description }}</div>
      <dl class="cf-dl">
        <dt>هدف مناسب</dt><dd>{{ implode('، ', $s->goals ?? []) }}</dd><dt>صنف‌ها</dt><dd>{{ implode('، ', $s->sectors ?? []) }}</dd>
        <dt>مدت · نسبت · شات</dt><dd>{{ Fa::digits($s->duration) }} · {{ Fa::digits($s->aspect) }} · {{ Fa::digits($s->shots) }}</dd>
        <dt>حرکت</dt><dd>{{ $s->motion }}</dd><dt>متن روی تصویر</dt><dd>{{ $s->text_style }}</dd>
        <dt>هوک / دعوت</dt><dd>{{ $s->hook_family }} / {{ $s->cta_family }}</dd><dt>موسیقی</dt><dd>{{ $s->music_hint }}</dd><dt>موتور ساخت</dt><dd>{{ $s->engine }}</dd>
        @if($s->recipe)<dt>دستور صحنه‌ها</dt><dd><ol style="margin:0;padding-inline-start:18px">@foreach($s->recipe as $r)<li>{{ $r }}</li>@endforeach</ol></dd>@endif
      </dl>
      <form id="cf-edit-{{ $s->id }}" method="post" action="{{ route('cf.styles.update', $s) }}" class="cf-confirm cf-stack" style="gap:10px;border-top:1px solid var(--divider);padding-top:12px" hidden>@csrf @method('PATCH')
        @include('cf::styles.fields', ['s' => $s])
        <div><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره</button></div>
      </form>
      @if($s->references->count())
        <div class="cf-refs">
          @foreach($s->references as $r)
            <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
              @if($r->sheet_path)<a href="{{ $disk->url($r->sheet_path) }}" target="_blank" title="{{ $r->analysis }}"><img src="{{ $disk->url($r->sheet_path) }}" alt="" loading="lazy"></a>@endif
              <span class="cf-muted" style="font-size:10.5px">{{ Fa::n((float) $r->duration, 1) }}ث · {{ Fa::n($r->shot_count) }} شات{{ $r->tempo_bpm ? ' · '.Fa::n($r->tempo_bpm).' BPM' : '' }} · {{ $r->style_confidence }}</span>
              <form method="post" action="{{ route('cf.styles.assign', $r) }}">@csrf @method('PATCH')
                <select name="style_id" class="cf-input" style="font-size:11px;padding:3px 6px" onchange="this.form.submit()">
                  @foreach($styles as $o)<option value="{{ $o->id }}" @selected($o->id === $r->style_id)>{{ $o->name_fa }}</option>@endforeach<option value="">بدون سبک</option>
                </select></form>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  @endforeach
  @if($orphans->count())
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-circle-question"></i> مرجع‌های بدون سبک (01-styles/_unsorted) — سبک را انتخاب کنید</h3>
      <div class="cf-refs">@foreach($orphans as $r)<div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
        @if($r->sheet_path)<img src="{{ $disk->url($r->sheet_path) }}" alt="" title="{{ $r->analysis }}">@endif
        <form method="post" action="{{ route('cf.styles.assign', $r) }}">@csrf @method('PATCH')<select name="style_id" class="cf-input" style="font-size:11px" onchange="this.form.submit()"><option value="">انتخاب سبک…</option>@foreach($styles as $o)<option value="{{ $o->id }}">{{ $o->name_fa }}</option>@endforeach</select></form>
      </div>@endforeach</div></div>
  @endif
</div>

<dialog id="cf-style-new" class="cf-dialog is-wide" dir="rtl">
  <form method="post" action="{{ route('cf.styles.store') }}" class="cf-dialog-body">@csrf
    <div class="cf-between"><b style="color:var(--text-h);font-size:15px">سبک جدید</b><button type="button" class="cf-btn cf-btn-sm" data-cf-close><i class="fa-solid fa-xmark"></i></button></div>
    @include('cf::styles.fields', ['s' => null])
    <div><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-plus"></i> افزودن</button></div>
  </form>
</dialog>
@endsection
