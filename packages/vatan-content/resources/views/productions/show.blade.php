@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $disk = \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk')); $url = fn ($p) => $p ? $disk->url($p) : null; @endphp

@section('cf-actions')
  <a href="{{ route('cf.productions') }}" class="cf-btn"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
  @if($p->item)
    <form method="post" action="{{ route('cf.productions.rebuild', $p) }}">@csrf<button class="cf-btn"><i class="fa-solid fa-rotate"></i> ساخت دوباره</button></form>
  @endif
  @if($v && ! in_array($p->status, ['approved', 'published'], true))
    <form method="post" action="{{ route('cf.productions.approve', $p) }}">@csrf<button class="cf-btn cf-btn-primary"><i class="fa-solid fa-check"></i> تأیید نسخه‌ی {{ Fa::n($p->current_version) }}</button></form>
  @endif
@endsection

@section('cf-page')
<div class="cf-grid" style="grid-template-columns:minmax(0,380px) minmax(0,1fr);align-items:start">
  <div class="cf-stack">
    <div class="cf-card">
      @if($v && $v->video_path)
        <video id="cf-player" class="cf-player" src="{{ $url($v->video_path) }}" poster="{{ $url($v->cover_path) }}" controls playsinline preload="metadata"></video>
      @elseif($v && $v->slides)
        <div class="cf-thumbs">@foreach($v->slides as $s)<img src="{{ $url($s) }}" alt="" style="width:100%;aspect-ratio:4/5">@endforeach</div>
      @else
        <div class="cf-empty"><i class="fa-solid fa-hourglass-start"></i>هنوز نسخه‌ای ساخته نشده است.</div>
      @endif
      <div class="cf-row" style="margin-top:12px">@include('cf::partials.status', ['s' => $p->status])@if($v)<span class="cf-pill">نسخه‌ی {{ Fa::n($v->version) }}</span><span class="cf-pill">{{ Fa::secs((float) $v->duration) }}</span>@endif</div>
    </div>
    @if($v && ($v->stories || $v->cover_path))
      <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-mobile-screen"></i> کاور و استوری‌ها</h3>
        <div class="cf-thumbs">
          @if($v->cover_path)<a href="{{ $url($v->cover_path) }}" target="_blank" title="کاور"><img src="{{ $url($v->cover_path) }}" alt="کاور"></a>@endif
          @foreach($v->stories ?? [] as $s)
            @if(str_ends_with($s, '.mp4'))<video src="{{ $url($s) }}" controls muted playsinline></video>@else<a href="{{ $url($s) }}" target="_blank"><img src="{{ $url($s) }}" alt="استوری"></a>@endif
          @endforeach
        </div></div>
    @endif
  </div>

  <div class="cf-stack">
    @if(in_array($p->status, ['queued', 'rendering'], true))
      <div class="cf-flash is-success" style="margin:0"><i class="fa-solid fa-gears"></i><span>در حال ساخت روی مک… این صفحه بعد از پایان خودکار تازه می‌شود.</span></div>
      <script>document.body.dataset.cfReloadOnDone = 1;</script>
      @include('cf::partials.jobs')
    @endif
    @if($p->caption)
      <div class="cf-card"><div class="cf-between"><h3 class="cf-card-title"><i class="fa-solid fa-align-right"></i> کپشن آماده‌ی انتشار</h3>
        <div class="cf-row"><form method="post" action="{{ route('cf.productions.caption.regen', $p) }}">@csrf<button class="cf-btn cf-btn-sm" title="با پرامپت فعال «کپشن»"><i class="fa-solid fa-wand-magic-sparkles"></i> نوشتن دوباره با کلاد</button></form>
        <button type="button" class="cf-btn cf-btn-sm" data-cf-confirm="cf-cap-edit"><i class="fa-solid fa-pen"></i> ویرایش</button>
        <button type="button" class="cf-btn cf-btn-sm" data-cf-copy="cf-caption"><i class="fa-regular fa-copy"></i> کپی</button></div></div>
        <form id="cf-cap-edit" method="post" action="{{ route('cf.productions.caption', $p) }}" class="cf-confirm cf-stack" style="gap:8px;margin-bottom:10px" hidden>@csrf @method('PATCH')
          <textarea name="caption" class="cf-input" style="min-height:180px">{{ $p->caption }}</textarea><input name="hashtags" class="cf-input" value="{{ $p->hashtags }}">
          <div><button class="cf-btn cf-btn-primary cf-btn-sm">ذخیره</button></div></form>
        <div class="cf-copy" id="cf-caption">{{ $p->caption }}@if($p->hashtags)

{{ $p->hashtags }}@endif</div></div>
    @endif

    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-dna"></i> شناسنامه‌ی محتوا (ژن) @include('cf::partials.help', ['k' => 'production.gene'])</h3>
      <dl class="cf-dl">
        @if($p->item)<dt>تقویم</dt><dd>{{ Fa::digits($p->item->code) }} · {{ Fa::date($p->item->publish_date, true, true) }} · {{ Fa::digits($p->item->publish_time) }}</dd>@endif
        @foreach(['pillar' => 'ستون', 'sector' => 'صنف', 'goal' => 'هدف', 'hook' => 'هوک', 'hook_family' => 'خانواده‌ی هوک', 'narrative' => 'ساختار روایت', 'style' => 'سبک', 'motion' => 'حرکت و افکت', 'cta' => 'دعوت به اقدام', 'music_mood' => 'حال موسیقی', 'audience' => 'مخاطب', 'model' => 'مدل/موتور', 'prompt_version' => 'نسخه‌ی پرامپت', 'publish_at' => 'زمان انتشار'] as $k => $l)
          @if(! empty($p->gene[$k]))<dt>{{ $l }}</dt><dd>{{ is_array($p->gene[$k]) ? implode('، ', $p->gene[$k]) : $p->gene[$k] }}</dd>@endif
        @endforeach
        @if($p->music)<dt>موسیقی</dt><dd>{{ $p->music->title }} <span class="cf-pill is-success">{{ Fa::label('license_status', $p->music->license_status) }}</span></dd>@endif
        <dt>هزینه‌ی ساخت</dt><dd>{{ Fa::usd((float) $p->cost_usd) }}@if($p->build_seconds) · {{ Fa::n(round($p->build_seconds / 60, 1), 1) }} دقیقه@endif</dd>
      </dl>
      @if($p->script)<details style="margin-top:12px"><summary class="cf-muted" style="cursor:pointer">سناریو و شات‌لیست</summary><div class="cf-copy" style="margin-top:8px">{{ $p->script }}</div></details>@endif
    </div>

    @if($v)
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-scissors"></i> اصلاحیه‌ی بخشی @include('cf::partials.help', ['k' => 'production.edit'])</h3>
      <form method="post" action="{{ route('cf.productions.edit', $p) }}" class="cf-stack" style="gap:10px">@csrf
        <div class="cf-grid cf-g3">
          <label class="cf-field">از ثانیه<span class="cf-row"><input id="cf-s" name="start_sec" type="number" step="0.1" min="0" class="cf-input" required><button type="button" class="cf-btn cf-btn-sm" data-cf-mark="cf-s" title="زمان فعلی پلیر"><i class="fa-solid fa-location-crosshairs"></i></button></span></label>
          <label class="cf-field">تا ثانیه<span class="cf-row"><input id="cf-e" name="end_sec" type="number" step="0.1" min="0" class="cf-input" required><button type="button" class="cf-btn cf-btn-sm" data-cf-mark="cf-e" title="زمان فعلی پلیر"><i class="fa-solid fa-location-crosshairs"></i></button></span></label>
          <label class="cf-field">نوع<select name="kind"><option value="edit">تدوینی (متن، برش، رنگ، موسیقی)</option><option value="generative">مولد (بازسازی تصویر)</option></select></label>
        </div>
        <label class="cf-field">توضیح اصلاحیه<textarea name="note" required placeholder="مثلاً: متن این بخش دیر ظاهر می‌شود؛ کلمه‌ی «وطن» درشت‌تر شود."></textarea></label>
        <div><button class="cf-btn"><i class="fa-solid fa-paper-plane"></i> ثبت اصلاحیه</button></div>
      </form>
      @foreach($p->edits as $e)
        <div class="cf-between" style="padding:8px 0;border-top:1px solid var(--divider);margin-top:8px">
          <span><button type="button" class="cf-pill" data-cf-seek="{{ $e->start_sec }}">{{ Fa::secs((float) $e->start_sec) }} تا {{ Fa::secs((float) $e->end_sec) }}</button> {{ $e->note }}</span>
          <span class="cf-pill {{ $e->status === 'open' ? 'is-warning' : 'is-success' }}">v{{ Fa::n($e->version) }} · {{ $e->status === 'open' ? 'باز' : 'انجام‌شده در v'.Fa::n($e->resolved_version) }}</span>
        </div>
      @endforeach
    </div>
    @endif

    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-code-compare"></i> نسخه‌ها</h3>
      @forelse($p->versions as $ver)
        <div class="cf-between" style="padding:7px 0;border-bottom:1px solid var(--divider)"><span>نسخه‌ی {{ Fa::n($ver->version) }} · {{ Fa::date($ver->created_at, false, true) }}@if($ver->notes) · <span class="cf-muted">{{ $ver->notes }}</span>@endif</span>
          @if($ver->video_path)<a class="cf-btn cf-btn-sm" href="{{ $url($ver->video_path) }}" target="_blank"><i class="fa-solid fa-play"></i> پخش</a>@endif</div>
      @empty <div class="cf-muted">—</div> @endforelse
      @if($v && $v->qc)<div class="cf-row" style="margin-top:10px">@foreach($v->qc as $k => $ok)<span class="cf-pill {{ $ok ? 'is-success' : 'is-danger' }}"><i class="fa-solid {{ $ok ? 'fa-check' : 'fa-xmark' }}"></i> {{ $k }}</span>@endforeach</div>@endif
    </div>

    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-brands fa-instagram"></i> انتشار و اینستاگرام هوشمند @include('cf::partials.help', ['k' => 'production.publish'])</h3>
      @if($p->status === 'published')
        <div>منتشر شد: {{ Fa::date($p->published_at, true, true) }} @if($p->ig_permalink)<a class="cf-ltr" href="{{ $p->ig_permalink }}" target="_blank" style="color:var(--primary)">{{ $p->ig_permalink }}</a>@endif</div>
        <div class="cf-kv" style="margin-top:12px">
          <div><b>پست در اینستاگرام هوشمند</b>@if($siPost)<a href="{{ \Illuminate\Support\Facades\Route::has('admin.smart-instagram.posts.index') ? route('admin.smart-instagram.posts.index') : '#' }}" style="color:var(--primary)">وصل است ✓</a> · {{ Fa::n($siPost->like_count) }} لایک · {{ Fa::n($siPost->comments_count) }} کامنت@else<span class="cf-muted">هنوز پیدا نشد</span>@endif</div>
          <div><b>سناریوی کامنت → دایرکت</b>{{ $p->si_campaign_id ? 'ساخته شد (کلمه: «'.implode('»، «', (array) $p->keywords).'»)' : ($p->auto_reply ? 'بعد از اتصال ساخته می‌شود' : 'خاموش') }}</div>
        </div>
        <form method="post" action="{{ route('cf.productions.instagram', $p) }}" style="margin-top:10px">@csrf<button class="cf-btn cf-btn-sm"><i class="fa-solid fa-link"></i> اتصال و برداشت آمار الان</button></form>
      @else
        <form method="post" action="{{ route('cf.productions.published', $p) }}" class="cf-stack" style="gap:8px">@csrf
          <input name="ig_permalink" class="cf-input" placeholder="لینک پست منتشرشده در اینستاگرام (اختیاری؛ وگرنه از روی کپشن پیدا می‌شود)" dir="ltr">
          <label class="cf-row" style="font-size:12.5px"><input type="checkbox" name="auto_reply" value="1" {{ $p->auto_reply ? 'checked' : '' }}> ساخت خودکار سناریوی کامنت → دایرکت با کلمه‌ی «{{ implode('»، «', (array) $p->keywords) ?: '—' }}» و لینک محصول</label>
          <div><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-flag-checkered"></i> منتشر کردم</button></div></form>
        <div class="cf-muted" style="font-size:11.5px;margin-top:6px">ویدیو، کاور، کپشن و استوری‌ها را از همین صفحه یا تاپیک «نسخه‌های نهایی» تلگرام بردارید و منتشر کنید.</div>
      @endif
      @if(! in_array($p->status, ['rejected', 'published'], true))
        <button type="button" class="cf-btn cf-btn-danger cf-btn-sm" style="margin-top:10px" data-cf-confirm="cf-reject">رد کامل</button>
        <form id="cf-reject" method="post" action="{{ route('cf.productions.reject', $p) }}" class="cf-row cf-confirm" style="margin-top:8px" hidden>@csrf<input name="note" class="cf-input" style="flex:1" placeholder="دلیل رد" required><button class="cf-btn cf-btn-danger">رد شود</button></form>
      @endif
    </div>

    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-star-half-stroke"></i> امتیاز عملکرد @include('cf::partials.help', ['k' => 'production.score'])</h3>
      @if($p->score_total !== null)
        <div class="cf-row" style="gap:18px"><span class="cf-score">{{ Fa::n((float) $p->score_total, 1) }}</span><span class="cf-pill is-info">اطمینان: {{ $p->score_confidence }}</span><span class="cf-muted">بر اساس برداشت {{ Fa::digits($p->scores['window'] ?? '') }} · مقایسه با {{ Fa::n($p->scores['peers'] ?? 0) }} پست</span></div>
        <div class="cf-kv" style="margin-top:10px">@foreach(['attention' => 'توجه', 'engagement' => 'تعامل', 'business' => 'کسب‌وکار', 'economic' => 'اقتصادی'] as $k => $l)<div><b>{{ $l }}</b>{{ isset($p->scores[$k]) && $p->scores[$k] !== null ? Fa::n($p->scores[$k]) : 'نامشخص' }}</div>@endforeach</div>
      @else
        <div class="cf-muted">بعد از انتشار و اولین برداشت آمار (۱۰ دقیقه) محاسبه می‌شود.</div>
      @endif
      @if($p->snapshots->count())
        <div class="cf-table-wrap" style="margin-top:10px"><table class="cf-table"><thead><tr><th>بازه</th><th>دسترسی</th><th>لایک</th><th>کامنت</th><th>ذخیره</th><th>اشتراک</th><th>دایرکت</th></tr></thead><tbody>
          @foreach($p->snapshots->sortBy(fn ($x) => array_search($x->window, array_keys(config('content-factory.metric_windows')))) as $sn)
            <tr><td>{{ Fa::digits($sn->window) }}</td>@foreach(['reach', 'likes', 'comments', 'saves', 'shares', 'dm_flows'] as $mk)<td class="cf-num">{{ array_key_exists($mk, $sn->metrics ?? []) && $sn->metrics[$mk] !== null ? Fa::n($sn->metrics[$mk]) : 'دریافت‌نشده' }}</td>@endforeach</tr>
          @endforeach</tbody></table></div>
      @endif
    </div>
  </div>
</div>
<style>@media (max-width:1000px){.cf-grid[style*="380px"]{grid-template-columns:1fr!important}}</style>
@endsection
