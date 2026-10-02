@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'دانش هوش مصنوعی';
  $siSubtitle = 'سبک گفتمان دستیار را با پرامپت (متن یا فایل) تعیین کنید، دانش تأییدشده‌ی برند را بارگذاری کنید و پیش از استفاده، پاسخ‌ها را بیازمایید.';
  $tones = ['friendly' => 'صمیمی و گرم', 'formal' => 'رسمی و محترمانه', 'energetic' => 'پرانرژی', 'luxury' => 'لوکس و فاخر', 'calm' => 'آرام و مشاوره‌ای'];
  $lengths = ['short' => 'کوتاه (۱-۲ جمله)', 'medium' => 'متوسط (تا ۴ جمله)', 'long' => 'کامل (تا ۶ جمله)'];
  $disclosures = ['when_asked' => 'اگر پرسید، بگوید دستیار هوشمند است', 'always' => 'همیشه در شروع معرفی کند', 'never_claim_human' => 'فقط هرگز ادعای انسان‌بودن نکند'];
@endphp

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-book', 'tone' => 'primary', 'value' => Ui::n($stats['sources']), 'label' => 'منبع دانش'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-circle-check', 'tone' => 'success', 'value' => Ui::n($stats['approved']), 'label' => 'قابل استفاده برای دستیار', 'tip' => 'تأییدشده، مجاز برای AI و منقضی‌نشده'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-hourglass-half', 'tone' => 'warning', 'value' => Ui::n($stats['drafts']), 'label' => 'در انتظار تأیید', 'href' => route('admin.smart-instagram.knowledge.index', ['tab' => 'sources', 'status' => 'draft'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-cubes', 'tone' => 'info', 'value' => Ui::n($stats['chunks']), 'label' => 'بخش قابل جست‌وجو'])
  </div>

  <nav class="si-tabs" aria-label="بخش‌های دانش">
    @foreach($tabs as $key => $label)
      <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => $key]) }}" class="chip-filter {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
  </nav>

  @if(!$aiEnabled)
    <div class="si-flash is-warning"><i class="fa-solid fa-triangle-exclamation"></i><span>هوش مصنوعی در تنظیمات سرور خاموش است (SMART_INSTAGRAM_AI_ENABLED)؛ دانش ذخیره می‌شود اما تحلیل و پیشنهاد ساخته نمی‌شود.</span></div>
  @endif

  {{-- ═══ سبک گفتمان و پرامپت ═══ --}}
  @if($tab === 'profile')
    <div class="si-grid-2">
      <section class="content-card si-panel">
        <div class="si-panel-head">
          <div><div class="si-panel-title"><i class="fa-solid fa-masks-theater"></i> سبک گفتمان با مشتری</div><div class="si-panel-sub">نسخه‌ی فعال: {{ Ui::n($profile->version) }} · هر ذخیره یک نسخه‌ی تازه می‌سازد و قابل برگشت است.</div></div>
        </div>
        @if($canManage)
          <form method="POST" action="{{ route('admin.smart-instagram.knowledge.profile.save') }}" enctype="multipart/form-data" class="si-form" id="si-profile-form">
            @csrf
            <div class="si-field"><label for="p-name">نام دستیار</label><input id="p-name" name="assistant_name" class="input-pro" maxlength="80" value="{{ old('assistant_name', $profile->assistant_name) }}"></div>
            <div class="si-field"><label for="p-model">مدل (اختیاری)</label><input id="p-model" name="model" class="input-pro si-ltr" maxlength="120" value="{{ old('model', $profile->model) }}" placeholder="{{ config('smart_instagram.ai.model') }}"><span class="si-help">خالی = مدل پیش‌فرض سرور</span></div>
            <div class="si-field is-full">
              <label for="p-prompt">پرامپت شخصیت و سبک گفتگو</label>
              <textarea id="p-prompt" name="persona_prompt" class="input-pro is-tall" maxlength="12000">{{ old('persona_prompt', $profile->persona_prompt) }}</textarea>
              <span class="si-help">بنویسید دستیار کیست، با چه لحنی حرف می‌زند، هدفش چیست و چه کارهایی نباید بکند. قواعد ایمنی (منع حدس قیمت، ارجاع موارد حساس به انسان، عدم ادعای انسان‌بودن) همیشه به‌صورت خودکار اضافه می‌شوند.</span>
            </div>
            <div class="si-field">
              <label for="p-file">یا بارگذاری فایل پرامپت</label>
              <input id="p-file" type="file" name="prompt_file" class="input-pro" accept=".txt,.md,.docx,.json,.html,.htm">
              <span class="si-help">txt، md، docx، json یا html — حداکثر ۱ مگابایت</span>
            </div>
            <div class="si-field"><label for="p-mode">با فایل چه شود؟</label><select id="p-mode" name="prompt_file_mode" class="input-pro"><option value="replace">جایگزین متن بالا شود</option><option value="append">به انتهای متن بالا اضافه شود</option></select></div>
            <div class="si-field"><label for="p-tone">لحن</label><select id="p-tone" name="tone" class="input-pro">@foreach($tones as $k => $l)<option value="{{ $k }}" @selected(old('tone', $profile->tone) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="si-field"><label for="p-len">طول پاسخ</label><select id="p-len" name="reply_length" class="input-pro">@foreach($lengths as $k => $l)<option value="{{ $k }}" @selected(old('reply_length', $profile->reply_length) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="si-field"><label for="p-disc">معرفی دستیار</label><select id="p-disc" name="bot_disclosure" class="input-pro">@foreach($disclosures as $k => $l)<option value="{{ $k }}" @selected(old('bot_disclosure', $profile->bot_disclosure) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="si-field"><label for="p-conf">حداقل اطمینان قابل‌قبول</label><input id="p-conf" type="number" step="0.05" min="0.3" max="0.95" name="min_confidence" class="input-pro" value="{{ old('min_confidence', $profile->min_confidence) }}"><span class="si-help">پیشنهادهای زیر این عدد با پرچم «اطمینان پایین» نمایش داده می‌شوند.</span></div>
            <div class="si-field"><label for="p-esc">کلمات ارجاع به انسان</label><textarea id="p-esc" name="escalation_keywords" class="input-pro" rows="3">{{ old('escalation_keywords', implode("\n", (array) $profile->escalation_keywords)) }}</textarea><span class="si-help">هر کدام در یک خط؛ پاسخ خودکار متوقف و گفتگو اولویت‌دار می‌شود.</span></div>
            <div class="si-field"><label for="p-forbid">عبارات ممنوع در پاسخ</label><textarea id="p-forbid" name="forbidden_phrases" class="input-pro" rows="3">{{ old('forbidden_phrases', implode("\n", (array) $profile->forbidden_phrases)) }}</textarea></div>
            <div class="si-field is-full"><label for="p-note">یادداشت تغییر</label><input id="p-note" name="change_note" class="input-pro" maxlength="300" placeholder="مثلاً: لحن صمیمی‌تر برای صنف زیبایی"></div>
            <div class="si-form-actions">
              <button class="btn-pro btn-pro-primary"><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره و فعال‌سازی نسخه‌ی تازه</button>
              <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'playground']) }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-flask text-[11px]"></i> آزمایش</a>
            </div>
          </form>
        @else
          <div class="si-pre">{{ $profile->persona_prompt }}</div>
        @endif
      </section>

      <section class="content-card si-panel is-flush">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-code-branch"></i> نسخه‌ها</div></div>
        <div class="si-table-wrap" style="margin-top:10px">
          <table class="table-pro">
            <thead><tr><th>نسخه</th><th>یادداشت</th><th>سازنده</th><th></th></tr></thead>
            <tbody>
              @foreach($versions as $version)
                <tr>
                  <td><b class="si-num">{{ Ui::n($version->version) }}</b>@if($version->is_active) <span class="badge-pro badge-success">فعال</span>@endif<div class="si-muted">{{ Ui::date($version->created_at, true) }}</div></td>
                  <td class="si-muted" style="white-space:normal">{{ $version->change_note ?: '—' }}</td>
                  <td>{{ $version->creator?->name ?? 'سیستم' }}</td>
                  <td>@if(!$version->is_active && $canManage)<form method="POST" action="{{ route('admin.smart-instagram.knowledge.profile.activate', $version) }}" data-confirm="نسخه‌ی {{ $version->version }} فعال شود؟">@csrf<button class="btn-pro btn-pro-ghost" style="height:30px">فعال‌سازی</button></form>@endif</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    </div>
  @endif

  {{-- ═══ منابع دانش ═══ --}}
  @if($tab === 'sources')
    <div class="si-grid-2">
      <section class="content-card si-panel is-flush">
        <div class="si-panel-head">
          <div class="si-chips">
            <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'sources']) }}" class="chip-filter {{ $category === '' && $status === '' ? 'active' : '' }}">همه</a>
            <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'sources', 'status' => 'usable']) }}" class="chip-filter {{ $status === 'usable' ? 'active' : '' }}">قابل استفاده</a>
            <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'sources', 'status' => 'draft']) }}" class="chip-filter {{ $status === 'draft' ? 'active' : '' }}">در انتظار تأیید</a>
            @foreach($categories as $k => $l)
              <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'sources', 'category' => $k]) }}" class="chip-filter {{ $category === $k ? 'active' : '' }}">{{ $l }}</a>
            @endforeach
          </div>
        </div>
        <div class="si-table-wrap" style="margin-top:10px">
          <table class="table-pro">
            <thead><tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th>اجازه‌ی AI</th><th>حجم</th><th>استفاده</th><th>به‌روزرسانی</th></tr></thead>
            <tbody>
              @forelse($sources as $source)
                <tr>
                  <td><a class="si-td-strong" style="color:var(--text-h)" href="{{ route('admin.smart-instagram.knowledge.sources.show', $source) }}">{{ $source->title }}</a>
                    <div class="si-muted">@if($source->source_type === 'file')<i class="fa-solid fa-paperclip"></i> {{ \Illuminate\Support\Str::limit($source->original_filename, 30) }} · @endif نسخه {{ Ui::n($source->version) }}@if($source->digest_status === 'done') · <i class="fa-solid fa-brain"></i> تحلیل‌شده@endif</div></td>
                  <td>{{ $categories[$source->category] ?? $source->category }}</td>
                  <td><span class="badge-pro badge-{{ Ui::statusTone($source->status) }}">{{ Ui::label('knowledge', $source->status) }}</span>@if($source->valid_until)<div class="si-muted">تا {{ Ui::date($source->valid_until) }}</div>@endif</td>
                  <td>{!! $source->ai_allowed ? '<i class="fa-solid fa-circle-check" style="color:var(--success)" title="مجاز"></i>' : '<i class="fa-solid fa-ban" style="color:var(--danger)" title="غیرمجاز"></i>' !!}</td>
                  <td class="si-num si-muted">{{ Ui::n($source->chunk_count) }} بخش</td>
                  <td class="si-num">{{ Ui::n($source->usage_count) }}</td>
                  <td class="si-muted">{{ Ui::ago($source->updated_at) }}</td>
                </tr>
              @empty
                <tr><td colspan="7" class="si-table-empty">هنوز دانشی ثبت نشده؛ از فرم کنار شروع کنید (معرفی برند، محصولات، قیمت‌ها، سؤالات رایج).</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('admin.smart-instagram.partials.pagination', ['paginator' => $sources])
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-upload"></i> افزودن دانش</div><div class="si-panel-sub">متن بنویسید یا فایل بارگذاری کنید؛ دستیار آن را می‌خواند، تکه‌بندی و ایندکس می‌کند.</div></div></div>
        @if($canManage)
          <form method="POST" action="{{ route('admin.smart-instagram.knowledge.sources.store') }}" enctype="multipart/form-data" class="si-form is-1">
            @csrf
            <div class="si-field"><label for="k-title">عنوان</label><input id="si-knowledge-title" name="title" class="input-pro" required maxlength="190" value="{{ old('title') }}" placeholder="مثلاً: تعرفه‌ی عکاسی محصول پاییز"></div>
            <div class="si-field"><label for="k-cat">دسته</label><select id="k-cat" name="category" class="input-pro">@foreach($categories as $k => $l)<option value="{{ $k }}" @selected(old('category', 'faq') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="si-field"><label for="k-content">متن دانش</label><textarea id="k-content" name="content" class="input-pro is-tall" maxlength="200000" placeholder="پرسش: … پاسخ: …&#10;یا هر متن ساختاریافته‌ی دیگر">{{ old('content') }}</textarea></div>
            <div class="si-field"><label for="si-knowledge-file">یا فایل</label><input id="si-knowledge-file" type="file" name="file" class="input-pro" accept=".txt,.md,.csv,.json,.html,.htm,.docx"><span class="si-help">{{ implode('، ', config('smart_instagram.knowledge.extensions')) }} — حداکثر {{ Ui::n((int) (config('smart_instagram.knowledge.max_upload_kb') / 1024)) }} مگابایت</span></div>
            <div class="si-field"><label for="k-valid">معتبر تا (اختیاری)</label><input id="k-valid" type="date" name="valid_until" class="input-pro" value="{{ old('valid_until') }}"><span class="si-help">برای قیمت و تخفیف‌های زمان‌دار؛ پس از این تاریخ دستیار از آن استفاده نمی‌کند.</span></div>
            <label class="si-check"><input type="hidden" name="ai_allowed" value="0"><input type="checkbox" name="ai_allowed" value="1" checked> دستیار اجازه‌ی استفاده دارد</label>
            <label class="si-check"><input type="checkbox" name="approve_now" value="1"> همین حالا تأیید شود</label>
            <label class="si-check"><input type="checkbox" name="digest_now" value="1" checked> تحلیل هوشمند (خلاصه، حقایق، پرسش‌وپاسخ و کمبودها)</label>
            <div class="si-form-actions"><button class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> ذخیره و ایندکس</button></div>
          </form>
        @else
          <p class="si-muted">افزودن دانش برای نقش شما فعال نیست.</p>
        @endif
      </section>
    </div>
  @endif

  {{-- ═══ آزمایشگاه دستیار ═══ --}}
  @if($tab === 'playground')
    <div class="si-grid-even">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-flask"></i> آزمایش دستیار</div><div class="si-panel-sub">یک پیام مشتری بنویسید؛ همان زنجیره‌ی واقعی (بازیابی دانش تأییدشده + سبک گفتمان + قواعد ایمنی) اجرا می‌شود، بدون ثبت در گفتگوها.</div></div></div>
        <form id="si-playground" action="{{ route('admin.smart-instagram.knowledge.playground') }}" class="si-form is-1" data-no-lock>
          <div class="si-field"><label for="pg-msg">پیام مشتری</label><textarea id="pg-msg" name="message" class="input-pro" required maxlength="1000" placeholder="مثلاً: سلام، برای فروشگاه کیف چرمی عکس محصول می‌خوام، قیمتش چنده؟"></textarea></div>
          <div class="si-chips">
            @foreach(['سلام، قیمت عکس محصول چنده؟', 'نمونه‌کار پوشاک دارید؟', 'سفارشم دیر شده، می‌خوام شکایت کنم', 'برای لوازم آرایشی هم کار می‌کنید؟'] as $sample)
              <button type="button" class="chip-filter" onclick="document.getElementById('pg-msg').value=this.textContent.trim()">{{ $sample }}</button>
            @endforeach
          </div>
          <div class="si-form-actions"><button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-paper-plane text-[11px]"></i> اجرای آزمایش</button></div>
        </form>
      </section>
      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-message"></i> خروجی دستیار</div></div>
        <div id="si-play-out" class="si-play-out"><span class="si-muted">نتیجه‌ی آزمایش این‌جا نمایش داده می‌شود: پاسخ، نیت، اطمینان، منابع دانش و پرچم‌های ایمنی.</span></div>
      </section>
    </div>
  @endif

  {{-- ═══ یادگیری و بازخورد ═══ --}}
  @if($tab === 'learning')
    @php($totalReviewed = (int) (($quality['accepted'] ?? 0) + ($quality['edited'] ?? 0) + ($quality['rejected'] ?? 0)))
    <div class="si-stats">
      @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-thumbs-up', 'tone' => 'success', 'value' => $totalReviewed ? Ui::pct(100 * (($quality['accepted'] ?? 0) + ($quality['edited'] ?? 0)) / $totalReviewed) : '—', 'label' => 'نرخ پذیرش پیشنهاد (۳۰ روز)'])
      @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-pen-to-square', 'tone' => 'info', 'value' => Ui::n($quality['edited'] ?? 0), 'label' => 'پذیرفته با ویرایش'])
      @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-thumbs-down', 'tone' => 'danger', 'value' => Ui::n($quality['rejected'] ?? 0), 'label' => 'ردشده'])
      @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-microchip', 'tone' => 'primary', 'value' => Ui::n((int) ($runs->total ?? 0)), 'label' => 'اجرای هوش مصنوعی', 'hint' => ($runs->failed ?? 0) ? Ui::n($runs->failed).' ناموفق' : null, 'hintTone' => 'danger', 'tip' => 'توکن: '.Ui::n((int) ($runs->tokens ?? 0)).' · هزینه: $'.number_format((float) ($runs->cost ?? 0), 4).' · میانگین زمان: '.Ui::n((int) ($runs->duration ?? 0)).'ms'])
    </div>

    <div class="si-grid-even">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-circle-question"></i> سؤال‌های بدون دانش</div><div class="si-panel-sub">مواردی که دستیار برای پاسخشان دانش نداشت — با افزودن دانش، این فهرست کوتاه می‌شود.</div></div></div>
        @forelse($gaps as $gap)
          <div class="si-hbar"><span class="si-hbar-label" style="width:auto;flex:1;white-space:normal">{{ $gap['label'] }}</span><span class="badge-pro badge-warning si-num">{{ Ui::n($gap['total']) }} بار</span></div>
        @empty
          <div class="si-table-empty">موردی ثبت نشده.</div>
        @endforelse
        @if($canManage)<a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'sources']) }}" class="si-link" style="margin-top:8px">افزودن دانش <i class="fa-solid fa-angle-left"></i></a>@endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-graduation-cap"></i> پاسخ‌های آموخته‌شده در انتظار تأیید</div><div class="si-panel-sub">پاسخ‌هایی که اپراتورها از گفتگوها به دانش افزوده‌اند.</div></div></div>
        @forelse($pendingLearned as $learned)
          <a href="{{ route('admin.smart-instagram.knowledge.sources.show', $learned) }}" class="si-row"><div class="si-row-main"><div class="si-row-title">{{ $learned->title }}</div><div class="si-row-sub">{{ Ui::ago($learned->created_at) }}</div></div><i class="fa-solid fa-angle-left si-muted"></i></a>
        @empty
          <div class="si-table-empty">موردی در انتظار نیست.</div>
        @endforelse
      </section>
    </div>

    <section class="content-card si-panel is-flush">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-comments"></i> بازخورد اپراتورها روی پیشنهادها</div></div>
      <div class="si-table-wrap" style="margin-top:10px">
        <table class="table-pro">
          <thead><tr><th>مخاطب</th><th>تصمیم</th><th>پیشنهاد دستیار</th><th>نسخه‌ی نهایی / دلیل</th><th>بازبین</th></tr></thead>
          <tbody>
            @forelse($feedback as $item)
              <tr>
                <td>@if($item->conversation)<a class="si-link" href="{{ route('admin.smart-instagram.inbox.show', $item->conversation_id) }}">{{ $item->conversation->contact?->label() }}</a>@endif</td>
                <td><span class="badge-pro badge-{{ Ui::statusTone($item->status) }}">{{ Ui::label('suggestion', $item->status) }}</span></td>
                <td style="white-space:normal;min-width:200px" class="si-muted">{{ \Illuminate\Support\Str::limit($item->body, 160) }}</td>
                <td style="white-space:normal;min-width:200px">{{ \Illuminate\Support\Str::limit($item->status === 'edited' ? $item->final_body : ($item->feedback ?: '—'), 160) }}</td>
                <td class="si-muted">{{ $item->reviewer?->name ?? '—' }}<br>{{ Ui::ago($item->reviewed_at) }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="si-table-empty">هنوز بازخوردی ثبت نشده.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  @endif
@endsection
