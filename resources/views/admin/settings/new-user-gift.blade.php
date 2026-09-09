@extends('layouts.admin')
@section('title', 'هدیه کاربران جدید — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/new-user-gift.css') }}?v={{ filemtime(public_path('admin/css/new-user-gift.css')) }}">
@endpush

@php
  $isLeader = auth('admin')->user()?->isLeader();
  $siteGiftEnabled = (bool) old('registration_gift_enabled', $settings->registration_gift_enabled);
  $preloginCreditText = old('prelogin_credit_text', $settings->prelogin_credit_text ?: ('هدیه ' . number_format((int) $settings->registration_gift_tokens) . ' اعتبار'));
  $telegramGiftEnabled = (bool) old('telegram_registration_gift_enabled', $settings->telegram_registration_gift_enabled);
  $startButtons = is_array($startContent->buttons) ? $startContent->buttons : [];
  $startButton = is_array($startButtons[0] ?? null) ? $startButtons[0] : [];
  $startButtonEnabled = filter_var(old('telegram_start_button_enabled', $startButton !== []), FILTER_VALIDATE_BOOLEAN);
  $startMediaType = old('telegram_start_media_type', $startContent->media_type ?: 'none');
  $startMediaFileId = old('telegram_start_media_file_id', $startContent->media_file_id);
  $startBody = old('telegram_start_body', $startContent->body ?: "سلام {first_name} عزیز 🌿\nبه وطن خوش اومدی. برای شروع، از دکمه زیر استفاده کن.");
  $startButtonText = old('telegram_start_button_text', $startButton['text'] ?? 'ثبت‌نام برای شروع');
  $startButtonUrl = old('telegram_start_button_url', $startButton['url'] ?? '');
@endphp

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto" id="content" dir="rtl">
    <div class="gift-page">
      @if(session('success'))
        <div class="gift-alert gift-alert--success" role="status"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
      @endif
      @if($errors->any())
        <div class="gift-alert gift-alert--error" role="alert"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>ذخیره تنظیمات انجام نشد</strong><span>{{ $errors->first() }}</span></div></div>
      @endif

      <header class="gift-hero">
        <div class="gift-hero__copy">
          <div class="gift-eyebrow"><i class="fa-solid fa-sliders"></i> تنظیمات اعتبار</div>
          <h1>هدیه کاربران جدید</h1>
          <p>اعتبار خوش‌آمدگویی سایت و تلگرام را از یک محل مدیریت کنید و اثر آن را قبل از ذخیره ببینید.</p>
        </div>
        <div class="gift-hero__status {{ $siteGiftEnabled || $telegramGiftEnabled ? 'is-active' : 'is-off' }}"><span></span>{{ $siteGiftEnabled || $telegramGiftEnabled ? 'حداقل یک هدیه فعال است' : 'هدیه‌ها خاموش هستند' }}</div>
      </header>

      <div class="gift-summary-grid">
        <div class="gift-summary-card"><span class="gift-summary-card__icon is-primary"><i class="fa-solid fa-globe"></i></span><div><small>هدیه ثبت‌نام سایت</small><strong>{{ number_format((int) $settings->registration_gift_tokens) }} اعتبار</strong></div><span class="gift-summary-card__state {{ $settings->registration_gift_enabled ? 'is-on' : 'is-off' }}">{{ $settings->registration_gift_enabled ? 'فعال' : 'خاموش' }}</span></div>
        <div class="gift-summary-card"><span class="gift-summary-card__icon is-info"><i class="fa-brands fa-telegram"></i></span><div><small>هدیه ثبت‌نام تلگرام</small><strong>{{ number_format((int) $settings->telegram_registration_gift_tokens) }} اعتبار</strong></div><span class="gift-summary-card__state {{ $settings->telegram_registration_gift_enabled ? 'is-on' : 'is-off' }}">{{ $settings->telegram_registration_gift_enabled ? 'فعال' : 'خاموش' }}</span></div>
        <div class="gift-summary-card"><span class="gift-summary-card__icon is-success"><i class="fa-solid fa-chart-line"></i></span><div><small>هدیه‌های پرداخت‌شده امروز</small><strong>{{ number_format($todayGifts + $todayTelegramGifts) }} مورد</strong></div><span class="gift-summary-card__meta">سایت و تلگرام</span></div>
      </div>

      <form method="POST" action="{{ route('admin.settings.new-user-gift.update') }}" class="gift-form">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$isLeader)>
          <section class="bot-steps-section" aria-labelledby="bot-steps-title">
            <div class="bot-steps-section__head">
              <div>
                <div class="gift-eyebrow"><i class="fa-solid fa-route"></i> مسیر گفتگو</div>
                <h2 id="bot-steps-title">گام‌های بات</h2>
                <p>جزئیات هر مرحله را از نگاه کاربر ببینید. «گام شروع» از همین صفحه قابل تنظیم است و گام‌های دیگر فعلاً فقط نمایشی هستند.</p>
              </div>
              <span class="bot-steps-section__badge"><i class="fa-solid fa-pen-to-square"></i> یک گام متصل</span>
            </div>

            <div class="bot-step-list">
              <details class="bot-step bot-step--active" open>
                <summary class="bot-step__summary">
                  <span class="bot-step__number">۱</span>
                  <span class="bot-step__summary-copy"><strong>شروع</strong><small>پیام خوش‌آمد، رسانه و دکمه ورود به مسیر ثبت‌نام</small></span>
                  <span class="bot-step__status bot-step__status--connected"><i class="fa-solid fa-link"></i> متصل به بات</span>
                  <i class="fa-solid fa-chevron-down bot-step__chevron"></i>
                </summary>
                <div class="bot-step__body">
                  <div class="bot-step__notice bot-step__notice--success"><i class="fa-solid fa-circle-check"></i><span>تغییرات این بخش روی محتوای `welcome` بات اعمال می‌شود. اگر لینک خالی باشد، دکمه مسیر داخلی ثبت‌نام را اجرا می‌کند.</span></div>
                  <div class="bot-step__layout">
                    <div class="bot-step__fields">
                      <div class="bot-step__grid">
                        <label class="gift-field"><span class="gift-field__label">نوع رسانه</span><select class="gift-text" name="telegram_start_media_type"><option value="none" @selected($startMediaType === 'none')>بدون رسانه</option><option value="photo" @selected($startMediaType === 'photo')>عکس</option><option value="video" @selected($startMediaType === 'video')>ویدیو</option></select><small class="gift-field__hint">برای فایل تلگرام، شناسه `file_id` را وارد کنید.</small></label>
                        <label class="gift-field"><span class="gift-field__label">شناسه یا لینک رسانه</span><input class="gift-text" type="text" name="telegram_start_media_file_id" value="{{ $startMediaFileId }}" placeholder="شناسه فایل یا https://..."><small class="gift-field__hint">بات رسانه را با همین مقدار در پیام شروع می‌فرستد.</small></label>
                        <label class="gift-field gift-field--wide"><span class="gift-field__label">متن پیام شروع</span><textarea class="gift-text bot-step__textarea" name="telegram_start_body" rows="5" maxlength="10000">{{ $startBody }}</textarea><small class="gift-field__hint">برای نام کاربر می‌توانید از `{first_name}` استفاده کنید.</small></label>
                        <label class="gift-toggle gift-toggle--stacked gift-field--wide"><span class="gift-toggle__copy"><strong>دکمه شروع نمایش داده شود</strong><small>با خاموش‌کردن این گزینه، پیام بدون دکمه ارسال می‌شود.</small></span><input type="hidden" name="telegram_start_button_enabled" value="0"><input class="gift-switch-input" type="checkbox" name="telegram_start_button_enabled" value="1" @checked($startButtonEnabled)><span class="gift-switch" aria-hidden="true"><span></span></span></label>
                        <label class="gift-field"><span class="gift-field__label">متن دکمه</span><input class="gift-text" type="text" name="telegram_start_button_text" value="{{ $startButtonText }}" maxlength="255" placeholder="ثبت‌نام برای شروع"></label>
                        <label class="gift-field"><span class="gift-field__label">لینک دکمه</span><input class="gift-text" type="url" name="telegram_start_button_url" value="{{ $startButtonUrl }}" placeholder="https://..."><small class="gift-field__hint">اختیاری؛ در صورت خالی‌بودن، دکمه داخلی ثبت‌نام اجرا می‌شود.</small></label>
                      </div>
                    </div>
                    <aside class="bot-step__preview" aria-label="پیش‌نمایش گام شروع">
                      <div class="bot-step__preview-head"><span><i class="fa-brands fa-telegram"></i> پیش‌نمایش بات</span><small>گام شروع</small></div>
                      <div class="bot-step__telegram-card">
                        <div class="bot-step__telegram-media"><i class="fa-regular fa-image"></i><span>رسانه‌ی شروع</span></div>
                        <p>{{ $startBody }}</p>
                        @if($startButtonEnabled)<span class="bot-step__telegram-button">{{ $startButtonText ?: 'ثبت‌نام برای شروع' }} <i class="fa-solid fa-arrow-left"></i></span>@endif
                      </div>
                    </aside>
                  </div>
                </div>
              </details>

              <details class="bot-step">
                <summary class="bot-step__summary"><span class="bot-step__number">۲</span><span class="bot-step__summary-copy"><strong>بررسی عضویت در کانال</strong><small>نمایش پیام عضویت و دکمه بررسی وضعیت</small></span><span class="bot-step__status">فقط نمایشی</span><i class="fa-solid fa-chevron-down bot-step__chevron"></i></summary>
                <div class="bot-step__body"><div class="bot-step__notice"><i class="fa-solid fa-circle-info"></i><span>این بخش فعلاً برای طراحی مسیر آماده شده و به بک‌اند وصل نیست.</span></div><div class="bot-step__grid"><label class="gift-field"><span class="gift-field__label">متن راهنمای عضویت</span><input class="gift-text" type="text" value="برای ادامه، ابتدا عضو کانال وطن شوید." disabled></label><label class="gift-field"><span class="gift-field__label">متن دکمه بررسی</span><input class="gift-text" type="text" value="بررسی عضویت" disabled></label><label class="gift-field gift-field--wide"><span class="gift-field__label">لینک کانال</span><input class="gift-text" type="text" value="از تنظیمات اتصال تلگرام خوانده می‌شود" disabled></label></div></div>
              </details>

              <details class="bot-step">
                <summary class="bot-step__summary"><span class="bot-step__number">۳</span><span class="bot-step__summary-copy"><strong>ثبت‌نام کاربر</strong><small>دریافت نام و شماره موبایل</small></span><span class="bot-step__status">فقط نمایشی</span><i class="fa-solid fa-chevron-down bot-step__chevron"></i></summary>
                <div class="bot-step__body"><div class="bot-step__notice"><i class="fa-solid fa-circle-info"></i><span>ظاهر این مرحله آماده است؛ تنظیمات آن بعد از تأیید نهایی به بک‌اند متصل می‌شود.</span></div><div class="bot-step__grid"><label class="gift-field"><span class="gift-field__label">پیام دریافت نام</span><input class="gift-text" type="text" value="اسمت رو برام بفرست 😊" disabled></label><label class="gift-field"><span class="gift-field__label">نوع دریافت شماره</span><input class="gift-text" type="text" value="دکمه اشتراک‌گذاری شماره" disabled></label><label class="gift-field gift-field--wide"><span class="gift-field__label">پیام تأیید</span><textarea class="gift-text bot-step__textarea" rows="3" disabled>اطلاعاتت دریافت شد. حالا می‌ریم سراغ مرحله بعد.</textarea></label></div></div>
              </details>

              <details class="bot-step">
                <summary class="bot-step__summary"><span class="bot-step__number">۴</span><span class="bot-step__summary-copy"><strong>کد تأیید پیامکی</strong><small>ارسال کد، ورود کد و ارسال مجدد</small></span><span class="bot-step__status">فقط نمایشی</span><i class="fa-solid fa-chevron-down bot-step__chevron"></i></summary>
                <div class="bot-step__body"><div class="bot-step__notice"><i class="fa-solid fa-circle-info"></i><span>اتصال متن‌ها و محدودیت تلاش‌ها در مرحله‌ی بعدی انجام می‌شود.</span></div><div class="bot-step__grid"><label class="gift-field gift-field--wide"><span class="gift-field__label">متن ارسال کد</span><textarea class="gift-text bot-step__textarea" rows="3" disabled>کد ورود برای شماره موبایل شما ارسال شد.</textarea></label><label class="gift-field"><span class="gift-field__label">متن دکمه ارسال مجدد</span><input class="gift-text" type="text" value="ارسال مجدد کد" disabled></label></div></div>
              </details>

              <details class="bot-step">
                <summary class="bot-step__summary"><span class="bot-step__number">۵</span><span class="bot-step__summary-copy"><strong>نمایش محصول</strong><small>نمایش محصول انتخاب‌شده از لینک کانال</small></span><span class="bot-step__status">فقط نمایشی</span><i class="fa-solid fa-chevron-down bot-step__chevron"></i></summary>
                <div class="bot-step__body"><div class="bot-step__notice"><i class="fa-solid fa-circle-info"></i><span>محتوای محصول از لینک ورودی خوانده می‌شود؛ فرم تنظیمات این مرحله فعلاً نمایشی است.</span></div><div class="bot-step__grid"><label class="gift-field"><span class="gift-field__label">عنوان کارت محصول</span><input class="gift-text" type="text" value="محصول انتخاب‌شده" disabled></label><label class="gift-field"><span class="gift-field__label">متن دکمه ساخت</span><input class="gift-text" type="text" value="ساخت محصول" disabled></label></div></div>
              </details>

              <details class="bot-step">
                <summary class="bot-step__summary"><span class="bot-step__number">۶</span><span class="bot-step__summary-copy"><strong>بازشدن `Mini App`</strong><small>انتقال امن کاربر به صفحه ساخت داخل تلگرام</small></span><span class="bot-step__status">فقط نمایشی</span><i class="fa-solid fa-chevron-down bot-step__chevron"></i></summary>
                <div class="bot-step__body"><div class="bot-step__notice"><i class="fa-solid fa-circle-info"></i><span>نشانی پایه `Mini App` فعلاً در کارت اتصال تلگرام مدیریت می‌شود؛ جزئیات این گام هنوز به بک‌اند وصل نیست.</span></div><div class="bot-step__grid"><label class="gift-field gift-field--wide"><span class="gift-field__label">متن بارگذاری</span><input class="gift-text" type="text" value="در حال آماده‌سازی صفحه ساخت..." disabled></label><label class="gift-field"><span class="gift-field__label">مسیر جایگزین</span><input class="gift-text" type="text" value="نمایش در مرورگر" disabled></label></div></div>
              </details>

              <details class="bot-step">
                <summary class="bot-step__summary"><span class="bot-step__number">۷</span><span class="bot-step__summary-copy"><strong>ساخت محصول</strong><small>ساخت، دریافت خروجی و بازگشت به بات</small></span><span class="bot-step__status">فقط نمایشی</span><i class="fa-solid fa-chevron-down bot-step__chevron"></i></summary>
                <div class="bot-step__body"><div class="bot-step__notice"><i class="fa-solid fa-circle-info"></i><span>تنظیمات این مرحله بعد از قطعی‌شدن سناریوی محصول فعال خواهد شد.</span></div><div class="bot-step__grid"><label class="gift-field"><span class="gift-field__label">متن آماده‌سازی خروجی</span><input class="gift-text" type="text" value="محصولت در حال ساخته‌شدن است..." disabled></label><label class="gift-field"><span class="gift-field__label">متن دکمه بازگشت</span><input class="gift-text" type="text" value="بازگشت به بات" disabled></label></div></div>
              </details>
            </div>
          </section>

          <div class="gift-content-grid">
            <div class="gift-form-column">
              <section class="gift-card">
                <div class="gift-card__head"><div class="gift-card__title"><span class="gift-card__icon is-primary"><i class="fa-solid fa-globe"></i></span><div><h2>هدیه ثبت‌نام سایت</h2><p>اعتباری که بعد از ثبت‌نام معمولی به کاربر جدید داده می‌شود.</p></div></div><span class="gift-card__badge">مسیر سایت</span></div>
                <div class="gift-card__body">
                  <label class="gift-toggle"><span class="gift-toggle__copy"><strong>پرداخت هدیه سایت فعال باشد</strong><small>با خاموش‌کردن این گزینه، کاربر جدید از مسیر سایت هدیه دریافت نمی‌کند.</small></span><input type="hidden" name="registration_gift_enabled" value="0"><input class="gift-switch-input" type="checkbox" name="registration_gift_enabled" value="1" @checked($siteGiftEnabled)><span class="gift-switch" aria-hidden="true"><span></span></span></label>
                  <div class="gift-site-fields-grid" data-gift-amount-sync>
                    <label class="gift-field"><span class="gift-field__label">مقدار هدیه</span><span class="gift-number-wrap"><input class="gift-number" type="number" name="registration_gift_tokens" min="0" max="1000000" value="{{ old('registration_gift_tokens', $settings->registration_gift_tokens) }}" data-gift-amount-input required><span class="gift-number__suffix">اعتبار</span></span><small class="gift-field__hint">این مقدار اعتبار واقعی کاربر جدید بعد از ثبت‌نام است.</small>@error('registration_gift_tokens')<small class="gift-field__error">{{ $message }}</small>@enderror</label>
                    <label class="gift-field"><span class="gift-field__label">اعتبار قبل لاگین کاربر</span><input class="gift-text" type="text" name="prelogin_credit_text" value="{{ $preloginCreditText }}" maxlength="255" placeholder="هدیه ۴۰ اعتبار" data-prelogin-credit-text required><small class="gift-field__hint">هر متنی که بنویسید دقیقاً در هدر مهمان‌ها نمایش داده می‌شود؛ اگر متن عدد داشته باشد، همان عدد هدیهٔ ثبت‌نام است.</small>@error('prelogin_credit_text')<small class="gift-field__error">{{ $message }}</small>@enderror</label>
                  </div>
                </div>
              </section>

              <section class="gift-card">
                <div class="gift-card__head"><div class="gift-card__title"><span class="gift-card__icon is-info"><i class="fa-brands fa-telegram"></i></span><div><h2>هدیه ثبت‌نام تلگرام</h2><p>تنظیم مستقل هدیه برای کاربری که از مسیر بات ثبت‌نام می‌کند.</p></div></div><span class="gift-card__badge">مسیر تلگرام</span></div>
                <div class="gift-card__body">
                  <label class="gift-toggle"><span class="gift-toggle__copy"><strong>پرداخت هدیه تلگرام فعال باشد</strong><small>این هدیه جداگانه محاسبه می‌شود، اما به کیف اعتبار مشترک کاربر اضافه خواهد شد.</small></span><input type="hidden" name="telegram_registration_gift_enabled" value="0"><input class="gift-switch-input" type="checkbox" name="telegram_registration_gift_enabled" value="1" @checked($telegramGiftEnabled)><span class="gift-switch" aria-hidden="true"><span></span></span></label>
                  <label class="gift-field"><span class="gift-field__label">مقدار هدیه تلگرام</span><span class="gift-number-wrap"><input class="gift-number" type="number" name="telegram_registration_gift_tokens" min="0" max="1000000" value="{{ old('telegram_registration_gift_tokens', $settings->telegram_registration_gift_tokens) }}" required><span class="gift-number__suffix">اعتبار</span></span><small class="gift-field__hint">این مقدار فقط در مسیر ثبت‌نام تلگرام استفاده می‌شود.</small>@error('telegram_registration_gift_tokens')<small class="gift-field__error">{{ $message }}</small>@enderror</label>
                </div>
              </section>

              <section class="gift-card">
                <div class="gift-card__head"><div class="gift-card__title"><span class="gift-card__icon is-warning"><i class="fa-solid fa-link"></i></span><div><h2>اتصال تلگرام</h2><p>مقادیر مورد استفاده برای لینک عضویت، بات و `Mini App`.</p></div></div><span class="gift-card__badge">اتصال و لینک‌ها</span></div>
                <div class="gift-card__body gift-fields-grid">
                  <label class="gift-field"><span class="gift-field__label">نام کاربری کانال اصلی</span><input class="gift-text" type="text" name="telegram_channel_username" value="{{ old('telegram_channel_username', $settings->telegram_channel_username ?: config('services.telegram.channel_username')) }}" placeholder="ai_vatan"><small class="gift-field__hint">بدون `@` وارد شود.</small></label>
                  <label class="gift-field"><span class="gift-field__label">نام کاربری بات</span><input class="gift-text" type="text" name="telegram_bot_username" value="{{ old('telegram_bot_username', $settings->telegram_bot_username ?: config('services.telegram.bot_username')) }}" placeholder="vatanstudio_bot"><small class="gift-field__hint">برای لینک‌سازی و شناسایی بات استفاده می‌شود.</small></label>
                  <label class="gift-field"><span class="gift-field__label">شناسه کانال اصلی</span><input class="gift-text" type="text" name="telegram_channel_id" value="{{ old('telegram_channel_id', $settings->telegram_channel_id ?: config('services.telegram.channel_id')) }}" placeholder="-100..."><small class="gift-field__hint">در صورت فعال‌بودن بررسی دقیق عضویت وارد شود.</small></label>
                  <label class="gift-field"><span class="gift-field__label">لینک عضویت کانال</span><input class="gift-text" type="url" name="telegram_channel_invite_url" value="{{ old('telegram_channel_invite_url', $settings->telegram_channel_invite_url ?: config('services.telegram.channel_invite_url')) }}" placeholder="https://t.me/+..."><small class="gift-field__hint">لینکی که برای عضویت کاربر نمایش داده می‌شود.</small></label>
                  <label class="gift-field gift-field--wide"><span class="gift-field__label">نشانی پایه `Mini App`</span><input class="gift-text" type="text" name="telegram_mini_app_url" value="{{ old('telegram_mini_app_url', $settings->telegram_mini_app_url ?: route('telegram.mini-app')) }}" required><small class="gift-field__hint">اگر خالی باشد، مسیر داخلی `telegram/mini-app` استفاده می‌شود.</small></label>
                </div>
              </section>
            </div>

            <aside class="gift-side-column">
              <section class="gift-preview-card"><div class="gift-side-head"><div><h2>پیش‌نمایش اعتبار</h2><p>نمایش اعتبار قبل از ورود در هدر سایت</p></div><i class="fa-solid fa-eye"></i></div><div class="gift-preview-bar"><span class="gift-preview-bar__avatar"><i class="fa-regular fa-user"></i></span><span class="gift-preview-bar__balance"><i class="fa-solid fa-sparkles"></i><strong>{{ $preloginCreditText }}</strong></span><span class="gift-preview-bar__theme"><i class="fa-regular fa-moon"></i></span></div><div class="gift-preview-note"><i class="fa-solid fa-circle-info"></i><span>متن این باکس برای مهمان‌ها قبل از ورود دقیقاً در هدر سایت و اپ نمایش داده می‌شود.</span></div></section>
              <section class="gift-card gift-rules-card"><div class="gift-card__head"><div class="gift-card__title"><span class="gift-card__icon is-success"><i class="fa-solid fa-shield-halved"></i></span><div><h2>قوانین ساخت</h2><p>شرط امنیتی مسیر تلگرام</p></div></div></div><div class="gift-card__body"><label class="gift-toggle gift-toggle--stacked"><span class="gift-toggle__copy"><strong>عضویت در کانال الزامی باشد</strong><small>بات قبل از ادامه ساخت محصول، عضویت کاربر در کانال اصلی را بررسی می‌کند.</small></span><input type="hidden" name="telegram_membership_required" value="0"><input class="gift-switch-input" type="checkbox" name="telegram_membership_required" value="1" @checked((bool) old('telegram_membership_required', $settings->telegram_membership_required))><span class="gift-switch" aria-hidden="true"><span></span></span></label></div></section>
              <div class="gift-stats-card"><div><span class="gift-stats-card__icon"><i class="fa-solid fa-calendar-day"></i></span><span><small>هدیه سایت امروز</small><strong>{{ number_format($todayGifts) }}</strong></span></div><div><span class="gift-stats-card__icon is-info"><i class="fa-brands fa-telegram"></i></span><span><small>هدیه تلگرام امروز</small><strong>{{ number_format($todayTelegramGifts) }}</strong></span></div></div>
            </aside>
          </div>

          <div class="gift-save-bar">
            @if($isLeader)<div><strong>آماده ذخیره تنظیمات هستید؟</strong><span>تغییرات در تراکنش امن ذخیره و در تاریخچه تنظیمات ثبت می‌شوند.</span></div><button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات</button>@else<div class="gift-readonly"><i class="fa-solid fa-lock"></i><span>فقط رهبر داشبورد امکان تغییر این تنظیمات را دارد.</span></div>@endif
          </div>
        </fieldset>
      </form>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  const giftAmountInput = document.querySelector('[data-gift-amount-input]');
  const preloginCreditInput = document.querySelector('[data-prelogin-credit-text]');
  const toAsciiDigits = (value) => value.replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)).replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  const firstNumber = (value) => toAsciiDigits(value).match(/\d+/)?.[0] || '';

  giftAmountInput?.addEventListener('input', () => {
    const numberMatch = preloginCreditInput?.value.match(/[۰-۹٠-٩0-9]+/);
    if (preloginCreditInput && numberMatch) {
      preloginCreditInput.value = preloginCreditInput.value.replace(numberMatch[0], giftAmountInput.value);
    }
  });
  preloginCreditInput?.addEventListener('input', () => {
    const number = firstNumber(preloginCreditInput.value);
    if (giftAmountInput && number) giftAmountInput.value = number;
  });
</script>
@endsection
