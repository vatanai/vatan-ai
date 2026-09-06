  <form method="POST" action="{{ route('admin.referrals.settings.update') }}" class="referral-settings-form">
    @csrf
    @method('PUT')

    <div class="referral-config-grid">
      <section class="content-card referral-config-card">
        <div class="referral-card-head">
          <span class="referral-card-icon is-primary"><i class="fa-solid fa-sparkles"></i></span>
          <div><h2>هدیه شروع کار</h2><p>هدیه‌ای که پس از ثبت‌نام و تأیید موبایل برای کاربر جدید ثبت می‌شود.</p></div>
        </div>
        <div class="referral-card-body">
          <label class="referral-toggle-row">
            <span><strong>هدیه ثبت‌نام فعال باشد</strong><small>با خاموش‌کردن، کاربر جدید بدون هدیه وارد می‌شود.</small></span>
            <input type="hidden" name="registration_gift_enabled" value="0">
            <input class="referral-switch-input" type="checkbox" name="registration_gift_enabled" value="1" @checked(old('registration_gift_enabled', $settings->registration_gift_enabled))>
            <span class="referral-switch" aria-hidden="true"></span>
          </label>
          <div class="referral-fields two-columns">
            <label class="referral-field"><span>تعداد توکن هدیه</span><input class="input-pro" type="number" min="0" name="registration_gift_tokens" value="{{ old('registration_gift_tokens', $settings->registration_gift_tokens) }}"><small>صفر یعنی بدون واریز توکن.</small></label>
            <label class="referral-field"><span>فاصله مجاز دریافت مجدد</span><div class="referral-input-suffix"><input class="input-pro" type="number" min="1" max="365" name="registration_gift_cooldown_days" value="{{ old('registration_gift_cooldown_days', $settings->registration_gift_cooldown_days) }}"><b>روز</b></div><small>برای دستگاه یا اینترنت تکراری.</small></label>
          </div>
          <label class="referral-check-row"><input type="hidden" name="registration_sms_enabled" value="0"><input type="checkbox" name="registration_sms_enabled" value="1" @checked(old('registration_sms_enabled', $settings->registration_sms_enabled))><span>پیامک خوش‌آمدگویی و مقدار هدیه ارسال شود</span></label>
          <label class="referral-check-row"><input type="hidden" name="registration_gift_review_repeated_device" value="0"><input type="checkbox" name="registration_gift_review_repeated_device" value="1" @checked(old('registration_gift_review_repeated_device', $settings->registration_gift_review_repeated_device))><span>هدیه دستگاه تکراری برای بررسی نگه داشته شود</span></label>
          <label class="referral-check-row"><input type="hidden" name="registration_gift_review_repeated_ip" value="0"><input type="checkbox" name="registration_gift_review_repeated_ip" value="1" @checked(old('registration_gift_review_repeated_ip', $settings->registration_gift_review_repeated_ip))><span>هدیه اینترنت تکراری برای بررسی نگه داشته شود</span></label>
        </div>
      </section>

      <section class="content-card referral-config-card">
        <div class="referral-card-head">
          <span class="referral-card-icon is-success"><i class="fa-solid fa-people-arrows-left-right"></i></span>
          <div><h2>پاداش همکاری در فروش</h2><p>کاربر لینک خود را منتشر می‌کند و پس از دعوت موفق پاداش می‌گیرد.</p></div>
        </div>
        <div class="referral-card-body">
          <label class="referral-toggle-row">
            <span><strong>سیستم همکاری در فروش فعال باشد</strong><small>در حالت خاموش، لینک‌های قبلی پاداش جدید ایجاد نمی‌کنند.</small></span>
            <input type="hidden" name="referral_enabled" value="0">
            <input class="referral-switch-input" type="checkbox" name="referral_enabled" value="1" @checked(old('referral_enabled', $settings->referral_enabled))>
            <span class="referral-switch" aria-hidden="true"></span>
          </label>
          <label class="referral-field"><span>شرط آزادشدن پاداش</span>
            <select class="input-pro" name="reward_trigger">
              <option value="first_purchase" @selected(old('reward_trigger', $settings->reward_trigger) === 'first_purchase')>بعد از اولین خرید موفق کاربر دعوت‌شده — پیشنهادشده</option>
              <option value="registration" @selected(old('reward_trigger', $settings->reward_trigger) === 'registration')>بلافاصله بعد از ثبت‌نام و تأیید موبایل</option>
            </select>
            <small class="is-important"><i class="fa-solid fa-shield"></i> شرط اولین خرید، ساخت حساب‌های متعدد فقط برای گرفتن توکن را بی‌اثر می‌کند.</small>
          </label>
          <div class="referral-fields two-columns">
            <label class="referral-field"><span>هدیه کاربر دعوت‌شده</span><div class="referral-input-suffix"><input class="input-pro" type="number" min="0" name="invitee_reward_tokens" value="{{ old('invitee_reward_tokens', $settings->invitee_reward_tokens) }}"><b>توکن</b></div></label>
            <label class="referral-field"><span>پاداش دعوت‌کننده</span><div class="referral-input-suffix"><input class="input-pro" type="number" min="0" name="inviter_reward_tokens" value="{{ old('inviter_reward_tokens', $settings->inviter_reward_tokens) }}"><b>توکن</b></div></label>
          </div>
        </div>
      </section>

      <section class="content-card referral-config-card">
        <div class="referral-card-head">
          <span class="referral-card-icon is-info"><i class="fa-solid fa-calendar-days"></i></span>
          <div><h2>کمپین و محدودیت پرداخت</h2><p>بازه اعتبار لینک و سقف پرداخت پاداش‌ها را کنترل کنید.</p></div>
        </div>
        <div class="referral-card-body">
          <div class="referral-fields two-columns">
            <label class="referral-field"><span>اعتبار انتساب لینک</span><div class="referral-input-suffix"><input class="input-pro" type="number" min="1" max="365" name="attribution_window_days" value="{{ old('attribution_window_days', $settings->attribution_window_days) }}"><b>روز</b></div></label>
            <label class="referral-field"><span>بودجه کل کمپین</span><div class="referral-input-suffix"><input class="input-pro" type="number" min="1" name="campaign_token_budget" value="{{ old('campaign_token_budget', $settings->campaign_token_budget) }}" placeholder="بدون سقف"><b>توکن</b></div></label>
            <label class="referral-field"><span>سقف دعوت موفق روزانه</span><input class="input-pro" type="number" min="1" name="daily_inviter_reward_limit" value="{{ old('daily_inviter_reward_limit', $settings->daily_inviter_reward_limit) }}" placeholder="بدون سقف"></label>
            <label class="referral-field"><span>سقف دعوت موفق ماهانه</span><input class="input-pro" type="number" min="1" name="monthly_inviter_reward_limit" value="{{ old('monthly_inviter_reward_limit', $settings->monthly_inviter_reward_limit) }}" placeholder="بدون سقف"></label>
            <label class="referral-field"><span>شروع کمپین</span><input class="input-pro" type="datetime-local" name="campaign_starts_at" value="{{ old('campaign_starts_at', $settings->campaign_starts_at?->format('Y-m-d\\TH:i')) }}"></label>
            <label class="referral-field"><span>پایان کمپین</span><input class="input-pro" type="datetime-local" name="campaign_ends_at" value="{{ old('campaign_ends_at', $settings->campaign_ends_at?->format('Y-m-d\\TH:i')) }}"></label>
          </div>
        </div>
      </section>

      <section class="content-card referral-config-card">
        <div class="referral-card-head">
          <span class="referral-card-icon is-warning"><i class="fa-solid fa-user-shield"></i></span>
          <div><h2>کنترل تقلب و نمایش پروفایل</h2><p>دعوت‌های تکراری حذف نمی‌شوند؛ برای تصمیم مدیر در صف بررسی می‌مانند.</p></div>
        </div>
        <div class="referral-card-body">
          <label class="referral-check-row"><input type="hidden" name="review_repeated_device" value="0"><input type="checkbox" name="review_repeated_device" value="1" @checked(old('review_repeated_device', $settings->review_repeated_device))><span>دعوت‌های ثبت‌شده با دستگاه تکراری بررسی شوند</span></label>
          <label class="referral-check-row"><input type="hidden" name="review_repeated_ip" value="0"><input type="checkbox" name="review_repeated_ip" value="1" @checked(old('review_repeated_ip', $settings->review_repeated_ip))><span>دعوت‌های ثبت‌شده با اینترنت تکراری بررسی شوند</span></label>
          <div class="referral-divider"></div>
          <label class="referral-toggle-row">
            <span><strong>نمایش همکاری در فروش در پروفایل</strong><small>محتوا و پنل کامل این بخش در مرحله چهارم متصل می‌شود.</small></span>
            <input type="hidden" name="profile_enabled" value="0">
            <input class="referral-switch-input" type="checkbox" name="profile_enabled" value="1" @checked(old('profile_enabled', $settings->profile_enabled))>
            <span class="referral-switch" aria-hidden="true"></span>
          </label>
          <label class="referral-field"><span>عنوان پنل کاربر</span><input class="input-pro" name="profile_title" maxlength="120" value="{{ old('profile_title', $settings->profile_title) }}"></label>
          <label class="referral-field"><span>زیرعنوان ترغیب‌کننده</span><input class="input-pro" name="profile_subtitle" maxlength="180" value="{{ old('profile_subtitle', $settings->profile_subtitle) }}"></label>
          <label class="referral-field"><span>توضیحات برنامه</span><textarea class="input-pro referral-textarea" name="profile_description" maxlength="1000" rows="4">{{ old('profile_description', $settings->profile_description) }}</textarea></label>
          <label class="referral-field"><span>متن آماده اشتراک‌گذاری</span><textarea class="input-pro referral-textarea" name="share_message" maxlength="500" rows="3">{{ old('share_message', $settings->share_message) }}</textarea><small>متغیر <b dir="ltr">{referral_link}</b> با لینک اختصاصی هر کاربر جایگزین می‌شود.</small></label>
          <div class="referral-profile-note"><i class="fa-solid fa-circle-check"></i><span>پنل پروفایل آماده است؛ پس از فعال‌کردن این گزینه برای کاربران نمایش داده می‌شود.</span></div>
        </div>
      </section>
    </div>

    <footer class="referral-save-bar">
      <div><strong>ذخیره تنظیمات</strong><span>تمام تغییرات همراه نام مدیر و زمان تغییر ثبت می‌شوند.</span></div>
      @if(auth('admin')->user()?->isLeader())
        <button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره همه تغییرات</button>
      @else
        <span class="badge-pro badge-neutral">فقط رهبر امکان ویرایش دارد</span>
      @endif
    </footer>
  </form>
