@extends('cf::layout')
@php
  use Vatan\Content\Support\Fa;
  $J = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
  $v = $p?->versions->first();
  $productValue = $item->product_type ? $item->product_type.':'.($item->product_id ?: $item->product_key) : '';
  $fields = config('content-factory.metric_fields');
  $snaps = $p ? $p->snapshots->sortBy(fn ($s) => $s->window === 'live' ? PHP_INT_MAX : (array_search($s->window, array_keys($windows), true) ?: 0))->values() : collect();
  $usedFields = collect($fields)->filter(fn ($l, $k) => $snaps->contains(fn ($s) => isset($s->metrics[$k])));
  $latest = $snaps->last()?->metrics ?? [];
  $plan = $item->ai_plan ?? [];
  $storageBase = rtrim($disk->url(''), '/').'/';
  $media = fn ($path) => $path ? $disk->url($path) : null;
@endphp

@section('cf-actions')
  <a href="{{ route('cf.calendar') }}" class="cf-btn"><i class="fa-solid fa-calendar-days"></i> تقویم</a>
  @if($prev)<a href="{{ route('cf.item', $prev->id) }}" class="cf-btn" title="پست قبلی"><i class="fa-solid fa-chevron-right"></i> {{ Fa::digits($prev->code) }}</a>@endif
  @if($next)<a href="{{ route('cf.item', $next->id) }}" class="cf-btn" title="پست بعدی">{{ Fa::digits($next->code) }} <i class="fa-solid fa-chevron-left"></i></a>@endif
@endsection

@section('cf-page')
<div class="cf-item-layout">
  {{-- ستون اصلی --}}
  <div class="cf-stack" style="min-width:0">
    <nav class="cf-tabs cf-item-nav" aria-label="بخش‌های پست">
      @foreach(['s-plan' => 'استراتژی', 's-product' => 'محصول', 's-hook' => 'هوک و کاور', 's-scenes' => 'صحنه‌ها و متن‌ها', 's-stories' => 'استوری‌ها', 's-look' => 'سبک و موزیک', 's-caption' => 'کپشن', 's-prompt' => 'پرامپت جامع', 's-build' => 'نقشه‌ی ساخت', 's-prod' => 'تولید', 's-stats' => 'انتشار و آمار'] as $a => $l)
        <a href="#{{ $a }}" class="cf-tab">{{ $l }}</a>
      @endforeach
    </nav>

    <form method="post" action="{{ route('cf.item.save', $item) }}" id="cf-item-form" class="cf-stack">@csrf
      <input type="hidden" name="scenes_json"><input type="hidden" name="story_specs_json"><input type="hidden" name="plan_json"><input type="hidden" name="prompt_json">

      <section class="cf-card" id="s-plan"><h3 class="cf-card-title"><i class="fa-solid fa-chess"></i> استراتژی و زمان‌بندی @include('cf::partials.help', ['k' => 'item.plan'])</h3>
        <div class="cf-grid cf-g4">
          <label class="cf-field"><span>تاریخ (میلادی) · <b style="color:var(--text-h)">{{ Fa::date($item->publish_date, true, true) }}</b></span><input name="publish_date" dir="ltr" value="{{ $item->publish_date?->toDateString() }}" pattern="\d{4}-\d{2}-\d{2}"></label>
          <label class="cf-field">ساعت انتشار<input name="publish_time" value="{{ $item->publish_time }}"></label>
          <label class="cf-field">قالب<select name="format">@foreach(['ریلز', 'اسلایدی', 'پست'] as $f)<option @selected($item->format === $f)>{{ $f }}</option>@endforeach</select></label>
          <label class="cf-field">وضعیت در تقویم<select name="approval_status">@foreach(['pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'حذف از تقویم'] as $k => $l)<option value="{{ $k }}" @selected($item->approval_status === $k)>{{ $l }}</option>@endforeach</select></label>
        </div>
        <div class="cf-grid cf-g3" style="margin-top:12px">
          <label class="cf-field">ستون محتوایی<input name="pillar" value="{{ $item->pillar }}"></label>
          <label class="cf-field">صنف<input name="sector" value="{{ $item->sector }}"></label>
          <label class="cf-field">هدف<input name="goal" value="{{ $item->goal }}"></label>
        </div>
        <label class="cf-field" style="margin-top:12px">مخاطب دقیق<input name="audience" value="{{ $item->audience }}"></label>
        <label class="cf-field" style="margin-top:12px">ایده و سناریوی خلاصه<textarea name="idea" style="min-height:90px">{{ $item->idea }}</textarea></label>
        <div class="cf-grid cf-g2" style="margin-top:12px">
          <label class="cf-field">روایت (برای پرامپت)<textarea name="narrative">{{ $plan['narrative'] ?? '' }}</textarea></label>
          <label class="cf-field">آزمایش این پست<textarea name="experiment">{{ $item->experiment }}</textarea></label>
        </div>
        <label class="cf-field" style="margin-top:12px">یادداشت ویژه (در پرامپت هم می‌رود)<textarea name="note">{{ $item->note }}</textarea></label>
      </section>

      <section class="cf-card" id="s-product"><h3 class="cf-card-title"><i class="fa-solid fa-box-open"></i> محصول وطن در این پست @include('cf::partials.help', ['k' => 'calendar.product'])</h3>
        <label class="cf-field">محصول
          <select name="product"><option value="">— انتخاب —</option>
            @foreach(collect($catalog)->groupBy('group') as $g => $opts)<optgroup label="{{ $g }}">@foreach($opts as $o)<option value="{{ $o['value'] }}" @selected($o['value'] === $productValue)>{{ $o['label'] }}</option>@endforeach</optgroup>@endforeach
          </select></label>
        <div class="cf-grid cf-g2" style="margin-top:12px">
          <label class="cf-field">لینک محصول (دایرکت و رهگیری)<input name="product_url" dir="ltr" value="{{ $item->product_url }}"></label>
          <label class="cf-field">لینک رهگیری (UTM)<input name="tracking_url" dir="ltr" value="{{ $item->tracking_url }}"></label>
        </div>
        @if($item->product_label)<div class="cf-muted" style="font-size:12px;margin-top:8px">فعلی: <b style="color:var(--text-h)">{{ $item->product_label }}</b></div>@endif
      </section>

      <section class="cf-card" id="s-hook"><h3 class="cf-card-title"><i class="fa-solid fa-bolt"></i> هوک، دعوت به اقدام و کاور @include('cf::partials.help', ['k' => 'item.hook'])</h3>
        <div class="cf-grid cf-g2">
          <label class="cf-field">هوک (۱.۵ ثانیه‌ی اول)<input name="hook" value="{{ $item->hook }}" style="font-weight:800"></label>
          <label class="cf-field">خانواده‌ی هوک<input name="hook_family" value="{{ $plan['hook_family'] ?? '' }}" placeholder="سؤال درد، عدد، قبل/بعد…"></label>
          <label class="cf-field">دعوت به اقدام<input name="cta" value="{{ $item->cta }}"></label>
          <label class="cf-field"><span>کلمه‌ی کلیدی کامنت (با ، جدا) @include('cf::partials.help', ['k' => 'calendar.keywords'])</span><input name="keywords_text" value="{{ implode('، ', (array) $item->keywords) }}"></label>
        </div>
        <div class="cf-section-title" style="margin-top:14px">کاور</div>
        <div class="cf-grid cf-g3">
          <label class="cf-field">متن کاور<input name="cover_text" value="{{ $item->cover_text }}"></label>
          <label class="cf-field">کلمه‌های برجسته‌ی کاور (با ، جدا)<input name="cover_hl" value="{{ implode('، ', (array) ($item->assets['cover_hl'] ?? [])) }}"></label>
          <label class="cf-field">بالانویس کاور<input name="cover_kicker" value="{{ $item->assets['cover_kicker'] ?? '' }}"></label>
        </div>
        <label class="cf-field" style="margin-top:12px">تصویر کاور<input name="cover_image" dir="ltr" list="cf-images" value="{{ $item->assets['cover_image'] ?? '' }}" data-cf-img></label>
      </section>

      <section class="cf-card" id="s-scenes">
        <div class="cf-between"><h3 class="cf-card-title" style="margin:0"><i class="fa-solid fa-film"></i> <span data-scene-title>صحنه‌ها و متن‌های روی ویدیو</span> @include('cf::partials.help', ['k' => 'calendar.scenes'])</h3>
          <span class="cf-row"><span class="cf-pill" data-total></span>
            @if(!empty(($item->result ?? [])['history']))<button type="submit" form="cf-undo" class="cf-btn cf-btn-sm"><i class="fa-solid fa-rotate-left"></i> بازگشت به قبل از بازنویسی</button>@endif</span></div>
        <div class="cf-muted" style="font-size:12px;margin:6px 0 12px">هر صحنه را مستقیم ویرایش کنید؛ زمان به ثانیه است (۳۰ فریم = ۱ ثانیه). ترتیب با فلش‌ها عوض می‌شود. «کلمه‌های برجسته» با رنگ برند نمایش داده می‌شوند.</div>
        <div id="cf-scenes" class="cf-stack" style="gap:10px"></div>
        <div class="cf-row" style="margin-top:12px"><select id="cf-scene-type" class="cf-input" style="width:auto"></select><button type="button" class="cf-btn" data-add-scene><i class="fa-solid fa-plus"></i> افزودن صحنه</button></div>
      </section>

      <section class="cf-card" id="s-stories">
        <h3 class="cf-card-title"><i class="fa-solid fa-mobile-screen"></i> استوری‌های مخصوص این پست (۱ تا ۳) @include('cf::partials.help', ['k' => 'calendar.stories'])</h3>
        <div id="cf-stories" class="cf-grid cf-g3"></div>
        <div style="margin-top:12px"><button type="button" class="cf-btn" data-add-story><i class="fa-solid fa-plus"></i> افزودن استوری</button></div>
      </section>

      <section class="cf-card" id="s-look"><h3 class="cf-card-title"><i class="fa-solid fa-palette"></i> سبک، موزیک و موتور ساخت</h3>
        <div class="cf-grid cf-g2">
          <label class="cf-field"><span>سبک ویدیو @if($item->styleModel)<a href="{{ route('cf.styles.show', $item->styleModel) }}" style="color:var(--primary)">جزئیات سبک</a>@endif</span>
            <select name="style_id"><option value="">—</option>@foreach($styles as $st)<option value="{{ $st->id }}" @selected($st->id === $item->style_id)>{{ $st->name_fa }} · {{ $st->template }}</option>@endforeach</select></label>
          <label class="cf-field">موسیقی
            <select name="music_id" data-cf-music><option value="">انتخاب خودکار از کتابخانه‌ی مجاز</option>@foreach($tracks as $t)<option value="{{ $t->id }}" data-src="{{ $t->audio_path ? $disk->url($t->audio_path) : '' }}" @selected($t->id === $item->music_id) @disabled($t->license_status === 'forbidden')>{{ $t->title }} · {{ $t->mood }}{{ $t->tempo_bpm ? ' · '.Fa::n($t->tempo_bpm).' BPM' : '' }}{{ $t->license_status !== 'approved' ? ' (مجوز نامشخص)' : '' }}</option>@endforeach</select></label>
        </div>
        <div class="cf-row" style="margin-top:8px"><audio class="cf-audio" controls preload="none" data-cf-music-player @if($item->musicTrack?->audio_path) src="{{ $disk->url($item->musicTrack->audio_path) }}" @endif></audio></div>
        <div class="cf-grid cf-g4" style="margin-top:12px">
          <label class="cf-field">شروع موزیک (ثانیه)<input name="music_start" type="number" step="0.1" min="0" value="{{ $item->assets['music_start'] ?? 0 }}"></label>
          <label class="cf-field">مدت هدف (ثانیه)<input name="duration_sec" type="number" min="3" max="180" value="{{ $item->duration_sec }}"></label>
          <label class="cf-field">محل ساخت<select name="build_location"><option value="mac" @selected($item->build_location !== 'server')>مک (ایجنت)</option><option value="server" @selected($item->build_location === 'server')>سرور</option></select></label>
          <label class="cf-field">موتور<input name="engine" value="{{ $item->engine }}"></label>
        </div>
        <label class="cf-field" style="margin-top:12px">پرامپت تصویر/کلیپ هوش مصنوعی (fal) — اختیاری<textarea name="visual_prompt">{{ $item->visual_prompt }}</textarea></label>
      </section>

      <section class="cf-card" id="s-caption"><h3 class="cf-card-title"><i class="fa-solid fa-align-right"></i> کپشن، هشتگ</h3>
        <label class="cf-field">کپشن (هنگام «ساخت» اگر تیک «کپشن تازه» بخورد، کلاد با پرامپت «کپشن» از روی این بازنویسی می‌کند)<textarea name="caption" style="min-height:200px">{{ $p?->caption ?: $item->caption }}</textarea></label>
        <label class="cf-field" style="margin-top:12px">هشتگ‌ها<input name="hashtags" value="{{ $item->hashtags }}"></label>
        <div class="cf-muted" style="font-size:11.5px;margin-top:6px"><span data-cap-count></span> · <a href="{{ route('cf.prompts', ['layer' => 'caption']) }}" style="color:var(--primary)"><i class="fa-solid fa-flask"></i> پرامپت کپشن</a></div>
      </section>

      <section class="cf-card" id="s-prompt">
        <div class="cf-between"><h3 class="cf-card-title" style="margin:0"><i class="fa-solid fa-scroll"></i> پرامپت جامع ساخت این پست @include('cf::partials.help', ['k' => 'item.prompt'])</h3>
          <span class="cf-row">
            <span class="cf-pill {{ ($promptSections['custom'] ?? false) ? 'is-warning' : 'is-success' }}">{{ ($promptSections['custom'] ?? false) ? 'شخصی‌سازی‌شده برای این پست' : 'از قالب اصلی' }}</span>
            <a href="{{ route('cf.prompts', ['layer' => 'master']) }}" class="cf-btn cf-btn-sm"><i class="fa-solid fa-flask"></i> قالب اصلی</a>
            @if($promptSections['custom'] ?? false)<button type="submit" form="cf-prompt-reset" class="cf-btn cf-btn-sm">بازگشت به قالب</button>@endif
          </span></div>
        <div class="cf-muted" style="font-size:12px;margin:8px 0 12px;line-height:2">این پرامپت کامل‌ترین دستور ساخت این پست است: هر بخش را ویرایش، خاموش، جابه‌جا یا حذف کنید و بخش تازه بیفزایید. متغیرهای <span class="cf-ltr">{{ '{'.'{…}'.'}' }}</span> لحظه‌ی ساخت با داده‌ی همین صفحه پر می‌شوند. با «نوشتن صحنه‌ها با این پرامپت» کلاد صحنه‌ها، استوری‌ها و کپشن را از نو می‌نویسد (سرور با OpenRouter یا Claude Code روی مک). همین پرامپت در هر نسخه‌ی ساخته‌شده ذخیره می‌شود.</div>
        <details><summary class="cf-muted" style="cursor:pointer;font-size:12.5px">دستور سیستمی (نقش مدل)</summary>
          <textarea id="cf-p-system" class="cf-input" style="min-height:90px;margin-top:8px;line-height:1.9">{{ $promptSections['system'] ?? '' }}</textarea></details>
        <div class="cf-row" style="margin:10px 0;font-size:11.5px">متغیرها (کلیک = درج در بخش فعال):
          @foreach($vars as $vv)<button type="button" class="cf-pill cf-ltr" data-ins="{{ $vv }}" style="cursor:pointer">{{ $vv }}</button>@endforeach</div>
        <div id="cf-psections" class="cf-stack" style="gap:10px"></div>
        <div class="cf-row" style="margin-top:12px"><button type="button" class="cf-btn" data-add-psec><i class="fa-solid fa-plus"></i> افزودن بخش</button></div>
        <details style="margin-top:14px"><summary style="cursor:pointer;font-weight:700;color:var(--text-h)">پیش‌نمایش پرامپت نهایی (آخرین ذخیره) · {{ Fa::n(mb_strlen($compiled['text'])) }} نویسه</summary>
          <div class="cf-row" style="margin:8px 0"><button type="button" class="cf-btn cf-btn-sm" data-cf-copy="cf-compiled"><i class="fa-regular fa-copy"></i> کپی</button></div>
          <div class="cf-copy" id="cf-compiled" style="max-height:520px;overflow:auto;font-size:12.5px">{{ $compiled['text'] }}</div></details>
      </section>

      <section class="cf-card" id="s-build"><h3 class="cf-card-title"><i class="fa-solid fa-diagram-project"></i> نقشه‌ی ساخت: چه مرحله‌ای، با چه هوشی، کجا @include('cf::partials.help', ['k' => 'calendar.engine'])</h3>
        <div class="cf-table-wrap"><table class="cf-table"><thead><tr><th>مرحله</th><th>ابزار / هوش</th><th>کجا</th><th>پرامپت</th><th>هزینه</th><th></th></tr></thead><tbody id="cf-plan"></tbody></table></div>
        <div style="margin-top:10px"><button type="button" class="cf-btn cf-btn-sm" data-add-plan><i class="fa-solid fa-plus"></i> افزودن مرحله</button></div>
      </section>

      <div class="cf-savebar">
        <span class="cf-muted" data-dirty>تغییری ذخیره نشده</span>
        <span class="cf-row">
          <button class="cf-btn cf-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره</button>
          <button class="cf-btn" name="then" value="build"><i class="fa-solid fa-play"></i> ذخیره و سفارش ساخت</button>
        </span>
      </div>
    </form>

    <section class="cf-card" id="s-prod"><h3 class="cf-card-title"><i class="fa-solid fa-clapperboard"></i> تولید و نسخه‌ها</h3>
      @if($p)
        <div class="cf-row" style="margin-bottom:10px">@include('cf::partials.status', ['s' => $p->status])<span class="cf-pill">نسخه‌ی فعلی: {{ Fa::n($p->current_version) }}</span>
          @if($p->build_seconds)<span class="cf-pill">زمان ساخت: {{ Fa::n($p->build_seconds) }} ثانیه</span>@endif<span class="cf-pill">هزینه: {{ Fa::digits(number_format((float) $p->cost_usd, 3)) }} دلار</span>
          <a href="{{ route('cf.productions.show', $p) }}" class="cf-btn cf-btn-sm">بازبینی، اصلاحیه و تأیید <i class="fa-solid fa-arrow-left"></i></a></div>
        <div class="cf-table-wrap"><table class="cf-table"><thead><tr><th>نسخه</th><th>زمان</th><th>مدت</th><th>خروجی‌ها</th><th>کنترل کیفیت</th><th>وضعیت</th></tr></thead><tbody>
          @forelse($p->versions as $pv)
            <tr><td class="cf-num">v{{ Fa::n($pv->version) }}</td><td>{{ Fa::date($pv->created_at, false, true) }}</td><td>{{ $pv->duration ? Fa::n(round($pv->duration, 1)).' ث' : '—' }}</td>
              <td><span class="cf-row">@if($pv->video_path)<a class="cf-pill is-info" href="{{ $media($pv->video_path) }}" target="_blank">ویدیو</a>@endif @if($pv->cover_path)<a class="cf-pill" href="{{ $media($pv->cover_path) }}" target="_blank">کاور</a>@endif
                @foreach((array) $pv->stories as $k => $s)<a class="cf-pill" href="{{ $media($s) }}" target="_blank">استوری {{ Fa::n($k + 1) }}</a>@endforeach @foreach((array) $pv->slides as $k => $s)<a class="cf-pill" href="{{ $media($s) }}" target="_blank">اسلاید {{ Fa::n($k + 1) }}</a>@endforeach</span></td>
              <td>@foreach((array) $pv->qc as $q => $ok)<span class="cf-pill {{ $ok ? 'is-success' : 'is-danger' }}" style="margin:1px">{{ $q }}</span>@endforeach</td>
              <td>{{ Fa::label('production_status', $pv->status) }}</td></tr>
          @empty <tr><td colspan="6" class="cf-muted">هنوز نسخه‌ای ساخته نشده است.</td></tr> @endforelse
        </tbody></table></div>
      @else
        <div class="cf-empty"><i class="fa-solid fa-clapperboard"></i>هنوز سفارش ساختی ثبت نشده. «سفارش ساخت» را بزنید؛ اگر ایجنت مک روشن باشد چند دقیقه بعد ویدیو، کاور و استوری‌ها اینجا هستند.</div>
      @endif
    </section>

    <section class="cf-card" id="s-stats"><h3 class="cf-card-title"><i class="fa-solid fa-chart-line"></i> انتشار و آمار کامل @include('cf::partials.help', ['k' => 'item.stats'])</h3>
      @if(! $p)
        <div class="cf-muted">بعد از ساخت و انتشار، آمار این پست در همه‌ی بازه‌ها (۱۰ دقیقه تا ۲۸ روز) اینجا ذخیره و پایش می‌شود.</div>
      @else
        <div class="cf-grid cf-g2" style="align-items:start">
          <form method="post" action="{{ route('cf.productions.published', $p) }}" class="cf-stack" style="gap:8px">@csrf
            <div class="cf-section-title">ثبت انتشار</div>
            <label class="cf-field">لینک پست اینستاگرام<input name="ig_permalink" dir="ltr" value="{{ $p->ig_permalink }}" placeholder="https://www.instagram.com/p/…"></label>
            <label class="cf-muted" style="font-size:12px"><input type="hidden" name="auto_reply" value="0"><input type="checkbox" name="auto_reply" value="1" @checked($p->auto_reply)> پاسخ خودکار کامنت/دایرکت با کلمه‌ی کلیدی (اینستاگرام هوشمند)</label>
            <div class="cf-row"><button class="cf-btn cf-btn-primary"><i class="fa-brands fa-instagram"></i> {{ $p->published_at ? 'به‌روزرسانی انتشار' : 'منتشر شد' }}</button>
              @if($p->published_at)<span class="cf-muted" style="font-size:12px">منتشرشده: {{ Fa::date($p->published_at, false, true) }}</span>@endif</div>
          </form>
          <div class="cf-stack" style="gap:8px">
            <div class="cf-section-title">اتصال و امتیاز</div>
            <div class="cf-row">
              <span class="cf-pill {{ $siPost ? 'is-success' : 'is-warning' }}">{{ $siPost ? 'وصل به پست اینستاگرام هوشمند' : 'هنوز وصل نیست' }}</span>
              @if($p->si_campaign_id)<span class="cf-pill is-success">سناریوی دایرکت فعال</span>@endif
              @if($siPost?->permalink)<a href="{{ $siPost->permalink }}" target="_blank" class="cf-pill is-info">باز کردن پست</a>@endif
            </div>
            <div class="cf-row">
              <form method="post" action="{{ route('cf.productions.instagram', $p) }}">@csrf<button class="cf-btn cf-btn-sm"><i class="fa-solid fa-link"></i> اتصال/همگام‌سازی</button></form>
              <form method="post" action="{{ route('cf.item.snapshot', $item) }}">@csrf<button class="cf-btn cf-btn-sm"><i class="fa-solid fa-camera"></i> برداشت آمار همین الان</button></form>
            </div>
            <div class="cf-row" style="align-items:flex-end"><span class="cf-score">{{ $p->score_total !== null ? Fa::n($p->score_total) : '—' }}</span><span class="cf-muted">از ۱۰۰ · اطمینان: {{ $p->score_confidence ?? 'نامشخص' }}</span></div>
            @if($p->scores)<div class="cf-row" style="font-size:12px">@foreach(['attention' => 'توجه', 'engagement' => 'تعامل', 'business' => 'کسب‌وکار', 'economic' => 'اقتصادی'] as $sk => $sl)<span class="cf-pill">{{ $sl }}: {{ isset($p->scores[$sk]) ? Fa::n($p->scores[$sk]) : '—' }}</span>@endforeach</div>@endif
          </div>
        </div>

        <div class="cf-section-title" style="margin-top:16px">آمار در طول زمان</div>
        @if($snaps->isNotEmpty())
          <div class="cf-grid cf-g4" style="margin-bottom:12px">
            @foreach(collect(['views', 'reach', 'likes', 'saves', 'shares', 'comments', 'follows', 'link_clicks'])->filter(fn ($k) => isset($latest[$k]))->take(8) as $k)
              @php $series = $snaps->map(fn ($s) => (float) ($s->metrics[$k] ?? 0)); $mx = max(1, $series->max()); @endphp
              <div class="cf-card" style="padding:12px"><div class="cf-stat-label">{{ $fields[$k] }}</div><div class="cf-stat-value">{{ Fa::n($latest[$k]) }}</div>
                <svg viewBox="0 0 100 28" preserveAspectRatio="none" style="width:100%;height:28px;margin-top:6px" aria-hidden="true">
                  <polyline fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" style="color:var(--primary)" points="{{ $series->values()->map(fn ($y, $i) => round($series->count() > 1 ? $i * 100 / ($series->count() - 1) : 50, 1).','.round(26 - $y / $mx * 24, 1))->implode(' ') }}"/></svg></div>
            @endforeach
          </div>
          <div class="cf-table-wrap"><table class="cf-table"><thead><tr><th>بازه</th><th>زمان برداشت</th><th>منبع</th>@foreach($usedFields as $l)<th>{{ $l }}</th>@endforeach</tr></thead><tbody>
            @foreach($snaps as $s)<tr><td class="cf-num">{{ $s->window === 'live' ? 'لحظه‌ای' : Fa::digits($s->window) }}</td><td>{{ Fa::date($s->captured_at, false, true) }}</td><td style="font-size:11px">{{ $s->source }}</td>
              @foreach($usedFields as $k => $l)<td class="cf-num">{{ isset($s->metrics[$k]) ? Fa::n($s->metrics[$k]) : '—' }}</td>@endforeach</tr>@endforeach
          </tbody></table></div>
        @else
          <div class="cf-muted" style="font-size:12.5px">هنوز برداشتی نیست. بعد از ثبت انتشار، هر ۱۰ دقیقه بازه‌های رسیده خودکار برداشت می‌شوند؛ یا دستی ثبت کنید.</div>
        @endif

        <details style="margin-top:14px"><summary style="cursor:pointer;font-weight:700;color:var(--text-h)"><i class="fa-solid fa-pen-to-square"></i> ثبت دستی آمار (بازدید، فالو، کلیک لینک، ثبت‌نام، فروش…)</summary>
          <form method="post" action="{{ route('cf.item.metrics', $item) }}" class="cf-stack" style="gap:10px;margin-top:10px">@csrf
            <label class="cf-field" style="max-width:220px">بازه<select name="window">@foreach(array_keys($windows) as $w)<option value="{{ $w }}">{{ Fa::digits($w) }}</option>@endforeach<option value="live">لحظه‌ای</option></select></label>
            <div class="cf-grid cf-g4">@foreach($fields as $k => $l)<label class="cf-field">{{ $l }}<input name="{{ $k }}" type="number" step="any" min="0" value="{{ $latest[$k] ?? '' }}"></label>@endforeach</div>
            <div><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره‌ی آمار و محاسبه‌ی امتیاز</button></div>
          </form></details>
      @endif
    </section>
  </div>

  {{-- ستون کناری چسبان --}}
  <aside class="cf-item-side">
    <div class="cf-card">
      <div class="cf-between" style="margin-bottom:10px"><b style="color:var(--text-h)">{{ Fa::digits($item->code) }} · {{ $item->format }}</b>
        @include('cf::partials.status', ['s' => $p->status ?? $item->production_status])</div>
      @if($v?->video_path)
        <video id="cf-player" class="cf-player" style="max-width:100%" src="{{ $media($v->video_path) }}" poster="{{ $media($v->cover_path) }}" controls playsinline preload="metadata"></video>
      @elseif($v?->cover_path)
        <img class="cf-player" style="max-width:100%;object-fit:cover" src="{{ $media($v->cover_path) }}" alt="">
      @else
        <div class="cf-player cf-empty" style="max-width:100%;display:grid;place-items:center;aspect-ratio:9/16"><span><i class="fa-solid fa-film"></i>هنوز ساخته نشده</span></div>
      @endif
      @if($v && ($v->stories || $v->slides))<div class="cf-thumbs" style="margin-top:8px">@foreach(array_merge((array) $v->slides, (array) $v->stories) as $s)<a href="{{ $media($s) }}" target="_blank"><img src="{{ $media($s) }}" alt="" style="width:64px" loading="lazy"></a>@endforeach</div>@endif
      <form method="post" action="{{ route('cf.item.build', $item) }}" class="cf-stack" style="gap:8px;margin-top:12px">@csrf
        <label class="cf-muted" style="font-size:12px"><input type="checkbox" name="regen_caption" value="1"> کپشن تازه با کلاد</label>
        <button class="cf-btn cf-btn-primary" style="justify-content:center"><i class="fa-solid fa-play"></i> {{ $p && $p->current_version ? 'سفارش نسخه‌ی جدید' : 'سفارش ساخت' }}</button>
      </form>
      <div class="cf-muted" style="font-size:11.5px;margin-top:6px">تغییرات فرم را اول ذخیره کنید یا «ذخیره و سفارش ساخت» را بزنید.</div>
      <form method="post" action="{{ route('cf.item.generate', $item) }}" class="cf-stack" style="gap:6px;margin-top:12px;border-top:1px solid var(--divider);padding-top:12px">@csrf
        <b style="color:var(--text-h);font-size:12.5px"><i class="fa-solid fa-wand-magic-sparkles"></i> نوشتن صحنه‌ها با پرامپت جامع</b>
        <select name="where" class="cf-input"><option value="auto">خودکار (سرور، وگرنه Claude Code مک)</option><option value="server" @disabled(! $aiOn)>کلاد روی سرور (OpenRouter){{ $aiOn ? '' : ' — در دسترس نیست' }}</option><option value="mac">Claude Code روی مک (اشتراک Pro)</option></select>
        <button class="cf-btn" style="justify-content:center">بازنویسی صحنه، استوری و کپشن</button>
        <span class="cf-muted" style="font-size:11px">نسخه‌ی فعلی نگه داشته می‌شود و با «بازگشت» برمی‌گردد.</span>
      </form>
    </div>
    <div class="cf-card"><div class="cf-between" style="margin-bottom:8px"><b style="color:var(--text-h);font-size:13px"><i class="fa-solid fa-gears"></i> کارهای این پست</b><span class="cf-agent" data-agent><i class="fa-solid fa-circle"></i> ایجنت</span></div>
      <div class="cf-jobs" id="cf-item-jobs">
        @forelse($jobs as $j)
          <div class="cf-job"><span><b>{{ \Vatan\Content\Models\Job::TYPES[$j->type] ?? $j->type }}</b> <span class="cf-muted">#{{ Fa::n($j->id) }} · {{ $j->message }}</span></span><span class="cf-pill">{{ \Vatan\Content\Models\Job::STATUSES[$j->status] ?? $j->status }}</span>
            @if(in_array($j->status, ['queued', 'running'], true))<span class="cf-job-bar"><span style="width:{{ $j->progress }}%"></span></span>@endif</div>
        @empty <div class="cf-muted" style="font-size:12px">هنوز کاری ثبت نشده.</div> @endforelse
      </div></div>
    @if(!empty(($item->result ?? [])['edits']))
      <div class="cf-card"><b style="color:var(--text-h);font-size:13px">آخرین ویرایش‌ها</b>
        @foreach(array_slice($item->result['edits'], 0, 5) as $e)<div class="cf-muted" style="font-size:11.5px;margin-top:4px">{{ $e['by'] }} · {{ Fa::date($e['at'], false, true) }}</div>@endforeach</div>
    @endif
  </aside>
</div>

<form id="cf-undo" method="post" action="{{ route('cf.item.undo', $item) }}">@csrf</form>
<form id="cf-prompt-reset" method="post" action="{{ route('cf.item.prompt-reset', $item) }}">@csrf</form>
<datalist id="cf-images">@foreach($images as $im)<option value="{{ $im }}">@endforeach</datalist>
@endsection

@section('cf-scripts')
<script>
window.CF_ITEM = {
  format: {!! $J($item->format) !!},
  scenes: {!! $J($item->scenes ?? []) !!},
  stories: {!! $J($item->story_specs ?? []) !!},
  plan: {!! $J($plan['steps'] ?? []) !!},
  prompt: {!! $J(['system' => $promptSections['system'] ?? '', 'sections' => $promptSections['sections'] ?? []]) !!},
  storage: {!! $J($storageBase) !!},
  status: {!! $J(route('cf.item.status', $item)) !!},
  promptsUrl: {!! $J(route('cf.prompts')) !!},
  busy: {{ $jobs->whereIn('status', ['queued', 'running'])->isNotEmpty() ? 'true' : 'false' }}
};
</script>
<script src="{{ route('cf.asset', 'cf-item.js') }}?v={{ config('content-factory.version') }}-{{ @filemtime(dirname(__DIR__, 2).'/assets/cf-item.js') }}" defer></script>
@endsection
