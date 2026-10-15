@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $disk = \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk'));
  $pend = (array) (((array) \Vatan\Content\Models\Setting::get('agent', []))['pending'] ?? []); @endphp

@section('cf-actions')
  <form method="post" action="{{ route('cf.jobs.store') }}">@csrf<input type="hidden" name="type" value="ingest_music">
    <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-music"></i> تحلیل موزیک‌های پوشه‌ی مک @if(!empty($pend['music']))<span class="cf-tab-badge">{{ Fa::n($pend['music']) }}</span>@endif</button></form>
@endsection

@section('cf-page')
<div class="cf-stack">
  <div class="cf-flash is-warning" style="margin:0"><i class="fa-solid fa-scale-balanced"></i><span>موسیقی‌های ترند کتابخانه‌ی عادی اینستاگرام الزاماً برای استفاده‌ی تجاری مجاز نیستند. فقط موسیقی با وضعیت «مجوز تأییدشده» وارد تولید می‌شود. فایل جدید: <span class="cf-ltr">content-lab/02-music</span></span></div>
  <div class="cf-flash is-success" style="margin:0"><i class="fa-solid fa-folder-open"></i><span>فایل صوتی با مجوز ← <span class="cf-ltr">02-music/audio</span> · ویدیویی که موزیکش تأییده ← <span class="cf-ltr">02-music/from-video</span>. بعد از تحلیل هر فایل به <span class="cf-ltr">02-music/library/&lt;انرژی-سرعت&gt;</span> منتقل می‌شود.</span></div>
  @include('cf::partials.jobs')
  @if($tracks->isEmpty())
    <div class="cf-empty"><i class="fa-solid fa-music"></i>هنوز موسیقی ثبت نشده است.</div>
  @else
  <div class="cf-table-wrap"><table class="cf-table" style="min-width:980px">
    <thead><tr><th>نام</th><th>پیش‌نمایش</th><th>مدت</th><th>ضرب‌آهنگ</th><th>انرژی</th><th>منبع</th><th>پوشه</th><th>مجوز</th><th>استفاده</th><th>ویرایش</th></tr></thead>
    <tbody>
    @foreach($tracks as $t)
      <tr>
        <td style="font-weight:700;color:var(--text-h);max-width:220px;word-break:break-all">{{ $t->title }}@if($t->mood)<div class="cf-muted" style="font-weight:400">{{ $t->mood }}</div>@endif</td>
        <td>@if($t->audio_path)<audio class="cf-audio" controls preload="none" src="{{ $disk->url($t->audio_path) }}"></audio>@endif</td>
        <td class="cf-num">{{ Fa::secs((float) $t->duration) }}</td><td class="cf-num">{{ $t->tempo_bpm ? Fa::n($t->tempo_bpm).' BPM' : '—' }}</td>
        <td>{{ ['low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد'][$t->energy] ?? '—' }}</td>
        <td>{{ $t->source === 'video' ? 'استخراج از ویدیو' : 'فایل صوتی' }}</td><td class="cf-ltr" style="font-size:11px">{{ $t->library_path ?: ($t->folder ? 'library/'.$t->folder : '—') }}</td>
        <td><span class="cf-pill {{ ['approved' => 'is-success', 'forbidden' => 'is-danger'][$t->license_status] ?? 'is-warning' }}">{{ Fa::label('license_status', $t->license_status) }}</span></td>
        <td class="cf-num">{{ Fa::n($t->use_count) }}</td>
        <td><form method="post" action="{{ route('cf.music.update', $t) }}" class="cf-row">@csrf @method('PATCH')
          <input name="mood" class="cf-input" style="width:140px" placeholder="حال‌وهوا" value="{{ $t->mood }}">
          <select name="license_status" class="cf-input" style="width:auto">@foreach(config('content-factory.labels.license_status') as $k => $l)<option value="{{ $k }}" @selected($t->license_status === $k)>{{ $l }}</option>@endforeach</select>
          <button class="cf-btn cf-btn-sm"><i class="fa-solid fa-floppy-disk"></i></button></form></td>
      </tr>
    @endforeach
    </tbody></table></div>
  @endif
</div>
@endsection
