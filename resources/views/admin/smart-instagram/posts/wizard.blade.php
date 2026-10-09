@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $isEdit = $campaign->exists;
  $siTitle = $isEdit ? 'ویرایش سناریوی پست' : 'ثبت پست جدید';
  $siSubtitle = 'شش قدم کوتاه: پست، کلمه‌ی کلیدی، پاسخ کامنت، دایرکت و فالو، کارت، انتشار — پیش‌نمایش زنده روی گوشی کنار فرم است.';
  $s = array_replace_recursive((array) $campaign->settings, (array) old('settings', []));
  if (old('settings.card.buttons') !== null) { $s['card']['buttons'] = array_values((array) old('settings.card.buttons')); }
  if (old('settings.reply.styles') !== null) { $s['reply']['styles'] = array_values((array) old('settings.reply.styles')); }
  $styles = array_pad(array_slice((array) data_get($s, 'reply.styles', []), 0, 3), 3, '');
  $kwRows = collect(old('keywords', $keywords->all()))->values();
  $selectedId = (int) old('post_id', $selected?->id);
  $followOn = (bool) old('follow_required', $isEdit ? $campaign->follow_required : true);
  $replyOn = (bool) old('public_reply_enabled', $isEdit ? $campaign->public_reply_enabled : true);
  $dmOn = (bool) old('dm_enabled', $isEdit ? $campaign->dm_enabled : true);
  $styleTags = ['صمیمی', 'رسمی و مؤدب', 'پرانرژی'];
  $steps = [
    ['icon' => 'fa-photo-film', 'title' => 'پست', 'sub' => 'انتخاب از پست‌های همگام‌شده'],
    ['icon' => 'fa-key', 'title' => 'کلمات کلیدی', 'sub' => 'چه کامنتی فعال کند'],
    ['icon' => 'fa-reply', 'title' => 'پاسخ کامنت', 'sub' => 'سه سبک + شخصی‌سازی'],
    ['icon' => 'fa-user-plus', 'title' => 'دایرکت و فالو', 'sub' => 'پیام آغاز و شرط فالو'],
    ['icon' => 'fa-id-card', 'title' => 'کارت دایرکت', 'sub' => 'تصویر، تیتر و دکمه‌ها'],
    ['icon' => 'fa-rocket', 'title' => 'تنظیمات و انتشار', 'sub' => 'محدودیت‌ها و وضعیت'],
  ];
  $postsJson = $posts->mapWithKeys(fn ($p) => [$p->id => [
    'cover' => $p->coverUrl(), 'caption' => $p->shortCaption(220), 'kind' => $p->kindLabel(), 'likes' => $p->like_count,
    'permalink' => $p->permalink, 'verified' => $p->isVerified(),
  ]]);
  $sipConfig = [
    'isEdit' => $isEdit,
    'posts' => $postsJson,
    'products' => $products->keyBy('id'),
    'productSuggestions' => array_values($productSuggestions ?? []),
    'presets' => $presets,
    'username' => $channel?->username ?: 'vatan.ai',
    'sampleName' => 'محسن',
    'sampleUser' => 'mohsen_shop',
    'routes' => ['generate' => route('admin.smart-instagram.posts.ai.generate'), 'aiSettings' => route('admin.smart-instagram.posts.ai.settings')],
    'csrf' => csrf_token(),
    'maxButtons' => 3,
  ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/smart-instagram-posts.css') }}?v={{ @filemtime(public_path('admin/css/smart-instagram-posts.css')) }}">
@endpush

@section('si-actions')
  @if($isEdit)<a href="{{ route('admin.smart-instagram.posts.show', $campaign) }}" class="btn-pro btn-pro-secondary"><i class="fa-solid fa-chart-simple text-[11px]"></i> جزئیات و آمار</a>@endif
  <a href="{{ route('admin.smart-instagram.posts.index') }}" class="btn-pro btn-pro-secondary"><i class="fa-solid fa-arrow-right text-[11px]"></i> فهرست پست‌ها</a>
@endsection

@section('si-page')
  {{-- فرم‌های جدا (فرم تو در تو مجاز نیست؛ دکمه‌ها با ویژگی form به این‌ها وصل‌اند) --}}
  <form id="sip-sync-form" method="POST" action="{{ route('admin.smart-instagram.posts.sync') }}" hidden>@csrf</form>
  <form id="sip-manual-form" method="POST" action="{{ route('admin.smart-instagram.posts.manual') }}" hidden>@csrf</form>

  <div class="sip-wizard">
    <div style="min-width:0">
      {{-- ── باکس هوش مصنوعی ── --}}
      <section class="sip-ai" data-sip-ai>
        <div class="sip-ai-inner">
          <div class="sip-ai-head">
            <div class="sip-ai-title">
              <span class="sip-step-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
              <div><b>دستیار نوشتن</b><small>متن‌ها را از روی پست، محصول یا لینک پیشنهاد می‌دهد؛ قبل از ذخیره بازبینی کنید.</small></div>
            </div>
            <div class="sip-seg" role="tablist">
              <button type="button" class="is-active" data-ai-tab="run" role="tab">اجرا با هوش مصنوعی</button>
              <button type="button" data-ai-tab="settings" role="tab">تنظیمات</button>
            </div>
          </div>

          <div class="sip-ai-pane" data-ai-pane="run">
            <div class="sip-ai-checks">
              @foreach(['public_reply' => 'سه سبک پاسخ کامنت', 'follow' => 'پیام فالو', 'card' => 'تیتر، توضیح و دکمه‌های کارت'] as $key => $label)
                <label><input type="checkbox" value="{{ $key }}" data-ai-section checked> {{ $label }}</label>
              @endforeach
            </div>
            <div class="si-form">
              <div class="si-field"><label for="ai-link">لینک محصول یا صفحه (اختیاری)</label><input id="ai-link" class="input-pro si-ltr" maxlength="1000" data-ai-link placeholder="https://aivatan.com/..."></div>
              <div class="si-field"><label for="ai-hint">نکته برای هوش مصنوعی (اختیاری)</label><input id="ai-hint" class="input-pro" maxlength="500" data-ai-hint placeholder="مثلاً: تخفیف ۲۰٪ تا جمعه، لحن خودمونی"></div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap">
              <button type="button" class="btn-pro btn-pro-primary" data-ai-run><i class="fa-solid fa-sparkles text-[11px]"></i> اعمال هوش مصنوعی</button>
              <span class="badge-pro badge-success" data-ai-applied-check hidden><i class="fa-solid fa-circle-check"></i> بخش‌های انتخاب‌شده کامل شد</span>
              <span class="si-help">از کپشن پست انتخاب‌شده، محصول کارت و کلمات کلیدی استفاده می‌شود. هیچ پیامی ارسال نمی‌شود.</span>
            </div>
            <div class="sip-ai-status" data-ai-status aria-live="polite"></div>
          </div>

          <div class="sip-ai-pane" data-ai-pane="settings" hidden>
            @if($canEditAi)
              <div class="si-form is-1">
                <div class="si-field"><label for="ai-model">مدل هوش مصنوعی</label><input id="ai-model" class="input-pro si-ltr" maxlength="120" data-ai-model value="{{ $aiSettings['model'] }}" placeholder="openai/gpt-4o-mini"><span class="si-help">شناسه‌ی مدل در OpenRouter؛ برای همه‌ی بخش‌های ثبت پست و پاسخ شخصی زنده استفاده می‌شود.</span></div>
                @foreach($aiSections as $key => $label)
                  <div class="si-field"><label for="ai-p-{{ $key }}">پرامپت: {{ $label }}</label><textarea id="ai-p-{{ $key }}" class="input-pro" rows="3" maxlength="3000" data-ai-prompt="{{ $key }}">{{ $aiSettings['prompts'][$key] ?? '' }}</textarea></div>
                @endforeach
              </div>
              <div style="display:flex;gap:8px;align-items:center;margin-top:10px">
                <button type="button" class="btn-pro btn-pro-primary" data-ai-save><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره‌ی تنظیمات</button>
                <span class="sip-ai-status" data-ai-save-status aria-live="polite" style="margin:0"></span>
              </div>
            @else
              <p class="si-muted" style="margin:0">مدل: <span class="si-mono si-ltr">{{ $aiSettings['model'] }}</span> — تغییر پرامپت‌ها فقط با دسترسی «مدیریت دانش» ممکن است.</p>
            @endif
          </div>
        </div>
      </section>

      {{-- ── قدم‌ها ── --}}
      <nav class="sip-stepper" aria-label="مراحل ثبت">
        @foreach($steps as $i => $step)
          <button type="button" class="sip-step-btn {{ $i === 0 ? 'is-current' : '' }}" data-step-go="{{ $i }}">
            <span class="sip-step-num">{{ Ui::n($i + 1) }}</span>
            <span class="sip-step-check" data-step-check hidden><i class="fa-solid fa-check"></i></span>
            <span class="sip-step-txt"><b>{{ $step['title'] }}</b><small>{{ $step['sub'] }}</small></span>
          </button>
        @endforeach
      </nav>
      <div class="sip-progress"><span data-step-progress style="width:16.6%"></span></div>

      <form id="sip-form" method="POST" action="{{ $isEdit ? route('admin.smart-instagram.posts.update', $campaign) : route('admin.smart-instagram.posts.store') }}" novalidate>
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- ۱. پست --}}
        <section class="sip-step is-current" data-step="0">
          <div class="sip-step-head"><span class="sip-step-icon"><i class="fa-solid fa-photo-film"></i></span><div><h2>کدام پست؟</h2><p>پست یا ریلز موردنظر را انتخاب کنید؛ تصویر کارت و پیش‌نمایش خودکار از همین پست برداشته می‌شود.</p></div></div>
          <div class="sip-block">
            @if($isEdit)
              <input type="hidden" name="post_id" value="{{ $selected?->id }}" data-post-input>
              <div class="sip-img-pick">
                <div class="sip-img-preview" style="width:96px;height:120px">@if($selected?->coverUrl())<img src="{{ $selected->coverUrl() }}" alt="">@else<i class="fa-solid fa-image"></i>@endif</div>
                <div>
                  <div class="sip-title" style="font-size:13px;font-weight:800;color:var(--text-h)"><i class="fa-solid {{ $selected?->kindIcon() }}"></i> {{ $selected?->kindLabel() }} · {{ Ui::date($selected?->published_at, true) }}</div>
                  <p class="si-muted" style="margin:6px 0;line-height:1.9">{{ $selected?->shortCaption(220) }}</p>
                  <span class="si-help">پست هر سناریو ثابت است؛ برای پست دیگر، سناریوی تازه بسازید.</span>
                </div>
              </div>
            @else
              <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                <div class="si-search" style="flex:1;min-width:200px;position:relative"><input type="search" class="input-pro" data-post-search placeholder="جست‌وجو در کپشن پست‌ها…" aria-label="جست‌وجوی پست" style="width:100%"></div>
                <button type="submit" form="sip-sync-form" class="btn-pro btn-pro-secondary" @disabled(!$channel)><i class="fa-solid fa-rotate text-[11px]"></i> همگام‌سازی</button>
              </div>
              @if($posts->isEmpty())
                <div class="si-table-empty" style="padding:26px 10px">هنوز پستی همگام نشده — «همگام‌سازی» را بزنید یا لینک پست را پایین وارد کنید.</div>
              @else
                <div class="sip-pick-grid" role="radiogroup" aria-label="انتخاب پست">
                  @foreach($posts as $p)
                    @php $taken = $p->campaign && $p->id !== $selectedId; @endphp
                    <label class="sip-pick {{ $taken ? 'is-taken' : '' }}" data-post-item data-search="{{ mb_strtolower($p->caption ?? '') }}" title="{{ $taken ? 'برای این پست قبلاً سناریو ثبت شده' : $p->shortCaption(120) }}">
                      <input type="radio" name="post_id" value="{{ $p->id }}" data-post-input @checked($selectedId === $p->id) @disabled($taken)>
                      @if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" loading="lazy" decoding="async">@else<span class="sip-pick-empty"><i class="fa-solid {{ $p->kindIcon() }}"></i></span>@endif
                      <span class="sip-pick-kind sip-glass"><i class="fa-solid {{ $p->kindIcon() }}"></i> {{ $p->kindLabel() }}</span>
                      <span class="sip-pick-check"><i class="fa-solid fa-check"></i></span>
                      <span class="sip-pick-meta"><b>{{ $p->shortCaption(70) }}</b>{{ Ui::date($p->published_at) }}@if($taken) · دارای سناریو @elseif(!$p->isVerified()) · نیازمند بررسی اتصال @endif</span>
                    </label>
                  @endforeach
                </div>
              @endif
              <div class="si-field" style="margin-top:14px">
                <label for="sip-manual">پست در فهرست نیست؟</label>
                <div style="display:flex;gap:8px">
                  <input id="sip-manual" form="sip-manual-form" name="media_ref" class="input-pro si-ltr" maxlength="300" placeholder="لینک پست اینستاگرام یا Media ID" style="flex:1">
                  <button type="submit" form="sip-manual-form" class="btn-pro btn-pro-secondary"><i class="fa-solid fa-link text-[11px]"></i> افزودن</button>
                </div>
                <span class="si-help">اگر اتصال آن را تأیید نکند، با وضعیت «نیازمند بررسی اتصال» ثبت می‌شود و تا تأیید فقط پیش‌نویس می‌ماند.</span>
              </div>
            @endif
            @if($selected && !$selected->isVerified())
              <div class="si-flash is-warning" style="margin:12px 0 0"><i class="fa-solid fa-triangle-exclamation"></i><span>این پست هنوز از اتصال واقعی تأیید نشده است؛ ذخیره فقط به‌صورت پیش‌نویس انجام می‌شود.</span></div>
            @endif
          </div>
          <div class="sip-block">
            <div class="si-field"><label for="sip-title">نام سناریو (اختیاری)</label><input id="sip-title" name="title" class="input-pro" maxlength="190" value="{{ old('title', $campaign->title) }}" placeholder="اگر خالی بماند از کپشن پست ساخته می‌شود"></div>
          </div>
        </section>

        {{-- ۲. کلمات کلیدی --}}
        <section class="sip-step" data-step="1">
          <div class="sip-step-head"><span class="sip-step-icon"><i class="fa-solid fa-key"></i></span><div><h2>کلمات کلیدی</h2><p>کامنتی که یکی از این کلمه‌ها را داشته باشد سناریو را شروع می‌کند. ی/ک عربی، اعداد و نیم‌فاصله خودکار یکسان می‌شوند.</p></div></div>
          <div class="sip-block">
            <div class="sip-kw-add">
              <input class="input-pro" data-kw-new maxlength="120" placeholder="کلمه را بنویسید و Enter بزنید — مثلاً: لینک، قیمت، 1" style="flex:1" aria-label="کلمه‌ی کلیدی تازه">
              <button type="button" class="btn-pro btn-pro-primary" data-kw-add><i class="fa-solid fa-plus text-[11px]"></i> افزودن</button>
            </div>
            <div class="sip-kw-list" data-kw-list>
              @foreach($kwRows as $i => $kw)
                <div class="sip-kw-row" data-kw-row>
                  <span class="sip-kw-text" data-kw-label>{{ $kw['keyword'] ?? '' }}</span>
                  <input type="hidden" data-name="keyword" name="keywords[{{ $i }}][keyword]" value="{{ $kw['keyword'] ?? '' }}">
                  <select class="input-pro" data-name="match_mode" name="keywords[{{ $i }}][match_mode]" aria-label="نحوه‌ی تطبیق">
                    @foreach($matchModes as $mk => $ml)<option value="{{ $mk }}" @selected(($kw['match_mode'] ?? 'contains') === $mk)>{{ $ml }}</option>@endforeach
                  </select>
                  <label class="sip-switch" title="فعال/غیرفعال"><input type="hidden" data-name="is_active_off" name="keywords[{{ $i }}][is_active]" value="0"><input type="checkbox" data-name="is_active" name="keywords[{{ $i }}][is_active]" value="1" @checked(filter_var($kw['is_active'] ?? true, FILTER_VALIDATE_BOOL))><span class="sip-switch-ui"></span></label>
                  <button type="button" class="icon-action-btn" data-kw-remove aria-label="حذف"><i class="fa-solid fa-xmark"></i></button>
                </div>
              @endforeach
            </div>
            <template data-kw-template>
              <div class="sip-kw-row" data-kw-row>
                <span class="sip-kw-text" data-kw-label></span>
                <input type="hidden" data-name="keyword" value="">
                <select class="input-pro" data-name="match_mode" aria-label="نحوه‌ی تطبیق">@foreach($matchModes as $mk => $ml)<option value="{{ $mk }}">{{ $ml }}</option>@endforeach</select>
                <label class="sip-switch" title="فعال/غیرفعال"><input type="hidden" data-name="is_active_off" value="0"><input type="checkbox" data-name="is_active" value="1" checked><span class="sip-switch-ui"></span></label>
                <button type="button" class="icon-action-btn" data-kw-remove aria-label="حذف"><i class="fa-solid fa-xmark"></i></button>
              </div>
            </template>
            <p class="si-help" data-kw-empty style="margin-top:10px" @if($kwRows->isNotEmpty()) hidden @endif>هنوز کلمه‌ای اضافه نشده.</p>
            <p class="si-help" style="margin-top:10px"><i class="fa-solid fa-wand-magic-sparkles"></i> اگر در کپشن کلمه‌ای را داخل «گیومه» یا با هشتگ بنویسید، هنگام انتخاب پست خودکار اینجا پیشنهاد می‌شود؛ قبل از ذخیره قابل ویرایش است.</p>
          </div>
          <div class="sip-block">
            <div class="sip-block-title"><i class="fa-solid fa-vial"></i> آزمون سریع</div>
            <div class="sip-tester">
              <input class="input-pro" data-kw-test placeholder="یک کامنت نمونه بنویسید…" style="flex:1" aria-label="کامنت نمونه">
              <span class="sip-tester-result" data-kw-test-result aria-live="polite"></span>
            </div>
            <p class="si-help" style="margin-top:6px">«شامل عبارت»: هرجای کامنت · «کلمه‌ی کامل»: فقط به‌صورت کلمه‌ی جدا · «دقیقاً برابر»: کل کامنت · «الگو»: با * مثل <span class="si-mono">لینک*</span></p>
          </div>
        </section>

        {{-- ۳. پاسخ کامنت --}}
        <section class="sip-step" data-step="2">
          <div class="sip-step-head"><span class="sip-step-icon"><i class="fa-solid fa-reply"></i></span><div><h2>پاسخ عمومی زیر کامنت</h2><p>سه سبک متفاوت بنویسید تا پاسخ‌ها تکراری به نظر نرسند؛ هر بار یکی به‌صورت تصادفی انتخاب می‌شود.</p></div></div>
          <div class="sip-block">
            <div class="sip-toggle-card">
              <div><b>پاسخ عمومی فعال باشد</b><small>زیر کامنت مشتری پاسخ کوتاهی منتشر می‌شود که بگوید دایرکتش را ببیند.</small></div>
              <label class="sip-switch is-lg"><input type="hidden" name="public_reply_enabled" value="0"><input type="checkbox" name="public_reply_enabled" value="1" data-toggle-reply @checked($replyOn)><span class="sip-switch-ui"></span></label>
            </div>
          </div>
          <div class="sip-block" data-reply-fields>
            <div class="sip-block-title"><i class="fa-solid fa-masks-theater"></i> سه سبک پاسخ</div>
            @foreach($styles as $i => $style)
              <div class="sip-style" style="margin-top:{{ $i ? 14 : 6 }}px">
                <span class="sip-style-tag">سبک {{ Ui::n($i + 1) }} · {{ $styleTags[$i] }}</span>
                <textarea class="input-pro" rows="2" name="settings[reply][styles][{{ $i }}]" maxlength="300" data-counter="300" data-style-index="{{ $i }}" data-ai-field="public_replies.{{ $i }}" style="padding-top:14px;min-height:64px">{{ $style }}</textarea>
                <div class="sip-counter" data-counter-out></div>
              </div>
            @endforeach
            <div class="sip-vars"><span class="si-help">متغیرها:</span><button type="button" class="sip-var" data-insert="{name}">{name} نام کوچک</button><button type="button" class="sip-var" data-insert="{username}">{username} آیدی</button></div>
            <div class="sip-toggle-card" style="margin-top:14px">
              <div><b><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--info)"></i> شخصی‌سازی با هوش مصنوعی</b><small>برای هر کامنت پاسخی تازه با نام مشتری نوشته می‌شود — مثلاً «محسن جان توی دایرکت برات ارسال شد 🌿». اگر هوش مصنوعی در ۱۵ ثانیه پاسخ ندهد، یکی از سه سبک بالا ارسال می‌شود.</small></div>
              <label class="sip-switch is-lg"><input type="hidden" name="settings[reply][ai_personalize]" value="0"><input type="checkbox" name="settings[reply][ai_personalize]" value="1" @checked(filter_var(data_get($s, 'reply.ai_personalize'), FILTER_VALIDATE_BOOL))><span class="sip-switch-ui"></span></label>
            </div>
          </div>
        </section>

        {{-- ۴. دایرکت و فالو --}}
        <section class="sip-step" data-step="3">
          <div class="sip-step-head"><span class="sip-step-icon"><i class="fa-solid fa-user-plus"></i></span><div><h2>دایرکت و شرط فالو</h2><p>کامنت‌گذار پیام خصوصی می‌گیرد؛ اگر فالو اجباری روشن باشد، لینک فقط بعد از فالو ارسال می‌شود.</p></div></div>
          <div class="sip-block">
            <div class="sip-toggle-card">
              <div><b>ارسال دایرکت فعال باشد</b><small>پیام خصوصی و کارت لینک برای کامنت‌گذار.</small></div>
              <label class="sip-switch is-lg"><input type="hidden" name="dm_enabled" value="0"><input type="checkbox" name="dm_enabled" value="1" data-toggle-dm @checked($dmOn)><span class="sip-switch-ui"></span></label>
            </div>
          </div>
          <div data-dm-fields>
            <div class="sip-block">
              <div class="sip-toggle-card" style="background:var(--warning-l);border-color:var(--warning-m)">
                <div><b><i class="fa-solid fa-user-check" style="color:var(--warning)"></i> فالو اجباری</b><small>قبل از ارسال کارت بررسی می‌شود کاربر پیج را فالو کرده یا نه.</small></div>
                <label class="sip-switch is-lg"><input type="hidden" name="follow_required" value="0"><input type="checkbox" name="follow_required" value="1" data-toggle-follow @checked($followOn)><span class="sip-switch-ui"></span></label>
              </div>
              <div class="sip-flow" style="margin-top:14px" aria-label="مسیر مشتری">
                <div class="sip-flow-step"><i class="fa-regular fa-comment"></i>کامنت با کلمه‌ی کلیدی</div>
                <div class="sip-flow-arrow"><i class="fa-solid fa-angle-left"></i></div>
                <div class="sip-flow-step" data-flow-follow><i class="fa-solid fa-user-plus"></i>فالو ندارد؟ درخواست فالو + «فالو کردم»</div>
                <div class="sip-flow-arrow" data-flow-follow><i class="fa-solid fa-angle-left"></i></div>
                <div class="sip-flow-step"><i class="fa-solid fa-id-card"></i>کارت مخصوص همین پست</div>
              </div>
              <p class="si-help" data-flow-follow><i class="fa-solid fa-circle-info"></i> فالوورها همان اول کارت را می‌گیرند. کاربری که تا حالا هیچ پیامی به پیج نداده، از نظر اینستاگرام وضعیت فالوی نامشخص دارد؛ برای او درخواست فالو می‌رود و اگر فالوور باشد با زدن «فالو کردم» بلافاصله کارت را می‌گیرد.</p>
              @if(!$followSupported)
                <p class="si-help"><i class="fa-solid fa-circle-info"></i> بررسی واقعی فالو به اتصال Meta یا Composio نیاز دارد؛ روی کانال آزمایشی وضعیت از تنظیمات همان کانال خوانده می‌شود.</p>
              @endif
            </div>

            <div class="sip-block">
              <div class="sip-block-title"><i class="fa-solid fa-arrow-down-wide-short"></i> ترتیب اجرای سناریو</div>
              <div class="sip-radio-cards">
                <label class="sip-radio-card"><input type="radio" name="settings[flow][order]" value="dm_first" @checked(data_get($s, 'flow.order', 'comment_first') === 'dm_first')><b>اول دایرکت، بعد پاسخ کامنت</b>ابتدا پیام خصوصی برای بازکردن مسیر گفتگو ارسال می‌شود، سپس پاسخ عمومی زیر همان کامنت.</label>
                <label class="sip-radio-card"><input type="radio" name="settings[flow][order]" value="comment_first" @checked(data_get($s, 'flow.order', 'comment_first') !== 'dm_first')><b>اول پاسخ کامنت، بعد دایرکت</b>ترتیب فعلی و مناسب وقتی است که می‌خواهید ابتدا پاسخ عمومی دیده شود.</label>
              </div>
              <span class="si-help">این گزینه فقط ترتیب صف ارسال را تعیین می‌کند؛ متن‌ها، شرط فالو و قوانین ایمنی تغییری نمی‌کنند.</span>
            </div>

            <div class="sip-block" data-follow-fields>
              <div class="sip-block-title"><i class="fa-solid fa-user-plus"></i> پیام درخواست فالو</div>
              <div class="si-form">
                <div class="si-field is-full"><label for="f-text">پیام درخواست فالو (پاسخ خصوصی به کامنت)</label><textarea id="f-text" class="input-pro" rows="2" name="settings[follow][text]" maxlength="900" data-counter="900" data-ai-field="follow_text">{{ data_get($s, 'follow.text') }}</textarea><div class="sip-counter" data-counter-out></div></div>
                <div class="si-field is-full"><label for="f-retry">اگر دوباره زد و هنوز فالو نکرده بود</label><textarea id="f-retry" class="input-pro" rows="2" name="settings[follow][retry_text]" maxlength="900" data-counter="900" data-ai-field="follow_retry_text">{{ data_get($s, 'follow.retry_text') }}</textarea><div class="sip-counter" data-counter-out></div></div>
                <div class="si-field"><label for="f-btn">متن دکمه</label><input id="f-btn" class="input-pro" name="settings[follow][button]" maxlength="20" data-counter="20" data-ai-field="follow_button" value="{{ data_get($s, 'follow.button') }}"><div class="sip-counter" data-counter-out></div></div>
                <div class="si-field"><label for="f-max">حداکثر دفعات بررسی</label><select id="f-max" class="input-pro" name="settings[follow][max_checks]">@foreach([1, 2, 3, 4, 5] as $n)<option value="{{ $n }}" @selected((int) data_get($s, 'follow.max_checks', 3) === $n)>{{ Ui::n($n) }} بار</option>@endforeach</select></div>
              </div>
              <div class="si-label" style="margin:14px 0 8px">اگر بعد از زدن «فالو کردم» هم وضعیت فالو از اینستاگرام دریافت نشد</div>
              <div class="sip-radio-cards">
                <label class="sip-radio-card"><input type="radio" name="settings[follow][unknown_policy]" value="send" @checked(data_get($s, 'follow.unknown_policy') !== 'ask')><b>کارت ارسال شود</b>مشتری منتظر نمی‌ماند. (پیشنهادی)</label>
                <label class="sip-radio-card"><input type="radio" name="settings[follow][unknown_policy]" value="ask" @checked(data_get($s, 'follow.unknown_policy') === 'ask')><b>درخواست فالو</b>تا تأیید فالو کارت ارسال نشود.</label>
              </div>
            </div>
          </div>
          <p class="si-help" data-dm-off-note hidden>دایرکت خاموش است؛ فقط پاسخ عمومی زیر کامنت ارسال می‌شود.</p>
        </section>

        {{-- ۵. کارت دایرکت --}}
        <section class="sip-step" data-step="4">
          <div class="sip-step-head"><span class="sip-step-icon"><i class="fa-solid fa-id-card"></i></span><div><h2>کارت دایرکت</h2><p>کارت تصویری با تیتر، توضیح و تا سه دکمه — همان چیزی که مشتری برای آن کامنت گذاشته.</p></div></div>
          <div data-card-fields>
            <div class="sip-block">
              <div class="si-field"><label for="c-intro">پیام قبل از کارت (اختیاری)</label><textarea id="c-intro" class="input-pro" rows="2" name="settings[card][intro_text]" maxlength="900" data-counter="900" data-ai-field="card_intro">{{ data_get($s, 'card.intro_text') }}</textarea><div class="sip-counter" data-counter-out></div></div>
            </div>
            <div class="sip-block">
              <div class="sip-block-title"><i class="fa-regular fa-image"></i> تصویر کارت</div>
              <div class="sip-img-pick">
                <div class="sip-img-preview sip-card-img-preview" data-card-img-preview><i class="fa-solid fa-image"></i></div>
                <div>
                  <div class="si-chips" style="flex-wrap:wrap">
                    @foreach(['post' => ['fa-photo-film', 'کاور همین پست'], 'product' => ['fa-bag-shopping', 'تصویر محصول'], 'url' => ['fa-link', 'لینک تصویر'], 'none' => ['fa-ban', 'بدون تصویر']] as $k => [$ic, $l])
                      <label class="chip-filter" style="cursor:pointer"><input type="radio" name="settings[card][image_source]" value="{{ $k }}" data-img-source @checked(data_get($s, 'card.image_source', 'post') === $k) style="accent-color:var(--primary)"> <i class="fa-solid {{ $ic }}"></i> {{ $l }}</label>
                    @endforeach
                  </div>
                  <div class="si-field" style="margin-top:10px" data-img-url-field><label for="c-imgurl">لینک تصویر</label><input id="c-imgurl" class="input-pro si-ltr" name="settings[card][image_url]" maxlength="1000" value="{{ data_get($s, 'card.image_url') }}" placeholder="https://..."></div>
                  <span class="si-help">تصویر پیش‌فرض، کاور خود پست است و هنگام همگام‌سازی خودکار ذخیره می‌شود.</span>
                </div>
              </div>
            </div>
            <div class="sip-block">
              <div class="sip-block-title" style="justify-content:space-between"><span><i class="fa-solid fa-hand-pointer"></i> دکمه‌ها <span class="si-muted" data-btn-count></span></span>
                <button type="button" class="btn-pro btn-pro-secondary" data-btn-add style="height:32px"><i class="fa-solid fa-plus text-[11px]"></i> دکمه‌ی تازه</button></div>
              <div class="sip-buttons" data-btn-list>
                @foreach(array_values((array) data_get($s, 'card.buttons', [])) as $i => $b)
                  @include('admin.smart-instagram.posts.partials.button-row', ['i' => $i, 'b' => $b])
                @endforeach
              </div>
              <template data-btn-template>@include('admin.smart-instagram.posts.partials.button-row', ['i' => '__i__', 'b' => ['preset' => 'link', 'type' => 'web_url', 'label' => 'مشاهده لینک', 'url' => '', 'reply_text' => '']])</template>
              <p class="si-help" style="margin-top:8px">اینستاگرام حداکثر ۳ دکمه در کارت می‌پذیرد. «لینک»: صفحه‌ی وب باز می‌شود · «پاسخ سریع»: متن تعیین‌شده در دایرکت ارسال می‌شود. دکمه‌های {{ implode('، ', $unavailableButtons) }} را اینستاگرام پشتیبانی نمی‌کند.</p>
            </div>
            <div class="sip-block">
              <div class="si-form">
                <div class="si-field is-full"><label for="c-product">محصول هدف</label>
                  <div class="sip-product-search"><i class="fa-solid fa-magnifying-glass"></i><input id="c-product-search" class="input-pro" type="search" data-product-search placeholder="نام، توضیح یا کد محصول را جست‌وجو کنید…" autocomplete="off"></div>
                  <div class="sip-product-suggestions" data-product-suggestions>
                    @foreach(array_filter($productSuggestions ?? []) as $suggestedId)
                      @php($suggested = $products->firstWhere('id', $suggestedId))
                      @if($suggested)<button type="button" class="chip-filter" data-product-suggestion="{{ $suggested['id'] }}">{{ $suggested['name'] }}</button>@endif
                    @endforeach
                  </div>
                  <select id="c-product" class="input-pro" name="settings[card][product_id]" data-card-product required>
                    <option value="">محصول هدف را انتخاب کنید</option>
                    @foreach($products as $product)<option value="{{ $product['id'] }}" data-search="{{ $product['name'].' '.$product['description'].' '.$product['id'] }}" @selected((int) data_get($s, 'card.product_id') === $product['id'])>{{ $product['name'] }}</option>@endforeach
                  </select>
                  <div class="sip-product-card" data-product-card hidden></div>
                  <span class="si-help">انتخاب محصول هدف الزامی است؛ لینک و تصویر کارت از همین محصول استفاده می‌شود.</span>
                </div>
                <div class="si-field"><label for="c-title">تیتر کارت</label><input id="c-title" class="input-pro" name="settings[card][title]" maxlength="80" data-counter="80" data-ai-field="card_title" value="{{ data_get($s, 'card.title') }}" placeholder="خالی = نام محصول یا کپشن پست"><div class="sip-counter" data-counter-out></div></div>
                <div class="si-field"><label for="c-sub">توضیح کوتاه</label><input id="c-sub" class="input-pro" name="settings[card][subtitle]" maxlength="80" data-counter="80" data-ai-field="card_subtitle" value="{{ data_get($s, 'card.subtitle') }}"><div class="sip-counter" data-counter-out></div></div>
              </div>
            </div>
            <div class="sip-block">
              <div class="si-field"><label for="c-after">پیام بعد از کارت (اختیاری)</label><textarea id="c-after" class="input-pro" rows="2" name="settings[card][after_text]" maxlength="900" data-counter="900">{{ data_get($s, 'card.after_text') }}</textarea><div class="sip-counter" data-counter-out></div></div>
            </div>
          </div>
          <p class="si-help" data-card-off-note hidden>دایرکت خاموش است؛ کارت ارسال نمی‌شود.</p>
        </section>

        {{-- ۶. تنظیمات و انتشار --}}
        <section class="sip-step" data-step="5">
          <div class="sip-step-head"><span class="sip-step-icon"><i class="fa-solid fa-rocket"></i></span><div><h2>تنظیمات و انتشار</h2><p>محدودیت‌های ایمنی را مشخص کنید و تصمیم بگیرید سناریو پیش‌نویس، آزمایشی یا فعال باشد.</p></div></div>
          <div class="sip-block">
            <div class="sip-block-title"><i class="fa-solid fa-repeat"></i> تکرار برای هر مخاطب</div>
            <div class="sip-radio-cards">
              @foreach(['once' => ['یک بار', 'هر مخاطب فقط یک بار پاسخ می‌گیرد.'], 'daily' => ['روزی یک بار', 'اگر فردا دوباره کامنت گذاشت، دوباره پاسخ می‌گیرد.'], 'every' => ['هر کامنت', 'با رعایت قواعد ایمنی ارسال (یک پاسخ خصوصی برای هر کامنت).']] as $k => [$t, $d])
                <label class="sip-radio-card"><input type="radio" name="settings[limits][repeat]" value="{{ $k }}" @checked(data_get($s, 'limits.repeat', 'once') === $k)><b>{{ $t }}</b>{{ $d }}</label>
              @endforeach
            </div>
          </div>
          <div class="sip-block">
            <div class="sip-block-title"><i class="fa-solid fa-shield-halved"></i> ایمنی و زمان‌بندی</div>
            <div class="si-form">
              <div class="si-field"><label for="l-cap">سقف اجرای روزانه</label><input id="l-cap" type="number" min="0" max="10000" class="input-pro" name="settings[limits][daily_cap]" value="{{ data_get($s, 'limits.daily_cap', 500) }}"><span class="si-help">۰ یعنی بدون سقف.</span></div>
              <div class="si-field"><label for="l-fail">توقف خودکار بعد از چند خطای پیاپی</label><input id="l-fail" type="number" min="0" max="50" class="input-pro" name="settings[limits][pause_after_failures]" value="{{ data_get($s, 'limits.pause_after_failures', 5) }}"><span class="si-help">۰ یعنی خاموش.</span></div>
              <div class="si-field"><label for="l-rd">تأخیر پاسخ عمومی (ثانیه)</label><input id="l-rd" type="number" min="0" max="3600" class="input-pro" name="settings[limits][reply_delay_seconds]" value="{{ data_get($s, 'limits.reply_delay_seconds', 0) }}"><span class="si-help">کمی تأخیر طبیعی‌تر به نظر می‌رسد.</span></div>
              <div class="si-field"><label for="l-dd">تأخیر دایرکت (ثانیه)</label><input id="l-dd" type="number" min="0" max="3600" class="input-pro" name="settings[limits][dm_delay_seconds]" value="{{ data_get($s, 'limits.dm_delay_seconds', 0) }}"></div>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:14px">
              <label class="sip-switch"><input type="hidden" name="settings[limits][stop_on_sensitive]" value="0"><input type="checkbox" name="settings[limits][stop_on_sensitive]" value="1" @checked(filter_var(data_get($s, 'limits.stop_on_sensitive', true), FILTER_VALIDATE_BOOL))><span class="sip-switch-ui"></span> کامنت حساس (شکایت، تهدید، فحش) به انسان سپرده شود و پاسخ خودکار نگیرد</label>
              <label class="sip-switch"><input type="hidden" name="settings[limits][add_tag]" value="0"><input type="checkbox" name="settings[limits][add_tag]" value="1" @checked(filter_var(data_get($s, 'limits.add_tag', true), FILTER_VALIDATE_BOOL))><span class="sip-switch-ui"></span> برچسب «کامنت‌گذار پست» به مخاطب اضافه شود</label>
            </div>
          </div>
          <div class="sip-block">
            <div class="sip-block-title"><i class="fa-solid fa-list-check"></i> جمع‌بندی</div>
            <div class="sip-summary" data-summary></div>
            @if(!$outboundEnabled)
              <p class="si-help" style="margin-top:10px"><i class="fa-solid fa-lock"></i> کلید ارسال واقعی خاموش است؛ حتی در حالت «فعال» پیامی به مخاطب ارسال نمی‌شود تا پس از تست اتصال روشن شود.</p>
            @endif
          </div>
        </section>

        <div class="sip-nav">
          <button type="button" class="btn-pro btn-pro-secondary" data-step-prev><i class="fa-solid fa-arrow-right text-[11px]"></i> قبلی</button>
          <div class="is-end">
            <button type="submit" name="intent" value="draft" class="btn-pro btn-pro-secondary"><i class="fa-regular fa-floppy-disk text-[11px]"></i> ذخیره‌ی پیش‌نویس</button>
            <button type="button" class="btn-pro btn-pro-primary" data-step-next>بعدی <i class="fa-solid fa-arrow-left text-[11px]"></i></button>
            <button type="submit" name="intent" value="test" class="btn-pro btn-pro-secondary" data-final hidden><i class="fa-solid fa-flask text-[11px]"></i> ذخیره در حالت آزمایشی</button>
            <button type="submit" name="intent" value="active" class="btn-pro btn-pro-primary" data-final hidden data-confirm-active="سناریو فعال شود و روی کامنت‌های واقعی اجرا شود؟"><i class="fa-solid fa-rocket text-[11px]"></i> ذخیره و فعال‌سازی</button>
          </div>
        </div>
      </form>
    </div>

    @include('admin.smart-instagram.posts.partials.phone')
  </div>
@endsection

@section('si-scripts')
<script>window.SIP_CONFIG = @json($sipConfig);</script>
<script src="{{ asset('admin/js/smart-instagram-posts.js') }}?v={{ @filemtime(public_path('admin/js/smart-instagram-posts.js')) }}" defer></script>
@endsection
