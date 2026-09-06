@php
  $programActive = $referralSettings->referralIsActive();
  $triggerIsPurchase = $referralSettings->reward_trigger === 'first_purchase';
  $loginReturnUrl = route('app.profile', ['tab' => 'referral']).'#referral-program';
@endphp

<div class="profile-panel referral-program" data-panel="referral" id="referral-program" style="display:none;">
  <section class="referral-program-hero">
    <div class="referral-program-copy">
      <span class="referral-program-kicker"><i class="fa-solid fa-people-arrows-left-right"></i> همکاری در فروش وطن</span>
      <h2>{{ $referralSettings->profile_title }}</h2>
      <p class="referral-program-lead">{{ $referralSettings->profile_subtitle }}</p>
      <p class="referral-program-description">{{ $referralSettings->profile_description }}</p>
      <div class="referral-reward-summary">
        @if($referralSettings->registration_gift_enabled && $referralSettings->registration_gift_tokens > 0)
          <span><i class="fa-solid fa-gift"></i> هدیه شروع دوستت: <b>{{ number_format($referralSettings->registration_gift_tokens) }} توکن</b></span>
        @endif
        @if($referralSettings->invitee_reward_tokens > 0)
          <span><i class="fa-solid fa-sparkles"></i> هدیه دعوت‌شده: <b>{{ number_format($referralSettings->invitee_reward_tokens) }} توکن</b></span>
        @endif
        <span><i class="fa-solid fa-coins"></i> پاداش هر دعوت موفق: <b>{{ number_format($referralSettings->inviter_reward_tokens) }} توکن</b></span>
      </div>
    </div>
    <div class="referral-program-visual" aria-hidden="true">
      <div class="referral-visual-orbit"><i class="fa-solid fa-link"></i></div>
      <span class="referral-visual-person is-first"><i class="fa-solid fa-user"></i></span>
      <span class="referral-visual-person is-second"><i class="fa-solid fa-user-plus"></i></span>
      <span class="referral-visual-gift"><i class="fa-solid fa-gift"></i></span>
    </div>
  </section>

  @unless($programActive)
    <div class="referral-program-notice"><i class="fa-solid fa-clock"></i><span>این برنامه فعلاً متوقف است. آمار قبلی تو محفوظ می‌ماند و با شروع کمپین بعدی دوباره می‌توانی دعوت کنی.</span></div>
  @endunless

  @if($isGuest ?? false)
    <section class="referral-guest-card">
      <div><i class="fa-solid fa-lock"></i></div>
      <h3>لینک اختصاصی تو آماده ساخت است</h3>
      <p>وارد حسابت شو تا لینک دعوت اختصاصی، آمار دعوت‌ها و پاداش‌هایت را یکجا ببینی.</p>
      <a href="{{ route('login', ['redirect' => $loginReturnUrl]) }}">ورود و شروع همکاری</a>
    </section>
  @else
    <section class="referral-dashboard-grid">
      <article class="referral-user-stat"><span><i class="fa-solid fa-arrow-pointer"></i></span><strong>{{ number_format($referralData['visits']) }}</strong><small>ورود از لینک تو</small></article>
      <article class="referral-user-stat"><span><i class="fa-solid fa-user-check"></i></span><strong>{{ number_format($referralData['registrations']) }}</strong><small>ثبت‌نام با دعوت</small></article>
      <article class="referral-user-stat"><span><i class="fa-solid fa-bag-shopping"></i></span><strong>{{ number_format($referralData['successful_purchases']) }}</strong><small>خرید موفق</small></article>
      <article class="referral-user-stat is-highlight"><span><i class="fa-solid fa-coins"></i></span><strong>{{ number_format($referralData['paid_tokens']) }}</strong><small>توکن دریافت‌شده</small></article>
    </section>

    <section class="referral-link-card {{ $programActive ? '' : 'is-disabled' }}">
      <div class="referral-section-heading">
        <div><span>لینک اختصاصی تو</span><small>این لینک را در شبکه‌های اجتماعی یا برای دوستانت بفرست.</small></div>
        @if($referralData['pending_tokens'] > 0)<b><i class="fa-solid fa-hourglass-half"></i> {{ number_format($referralData['pending_tokens']) }} توکن در انتظار بررسی</b>@endif
      </div>
      <div class="referral-code-row"><span>کد دعوت تو</span><code dir="ltr">{{ $referralData['code'] }}</code><small>همین کد داخل همه لینک‌های کسب پاداش تو استفاده می‌شود.</small></div>
      <div class="referral-link-box" dir="ltr">
        <input id="referralLinkInput" value="{{ $referralData['link'] }}" readonly aria-label="لینک اختصاصی دعوت">
        <button type="button" id="copyReferralLink" @disabled(!$programActive)><i class="fa-regular fa-copy"></i><span>کپی لینک</span></button>
      </div>
      <p class="referral-copy-feedback" id="referralCopyFeedback" aria-live="polite"></p>
    </section>

    <section class="referral-how-card">
      <div class="referral-section-heading"><div><span>چطور پاداش می‌گیری؟</span><small>سه قدم ساده و شفاف تا دریافت توکن</small></div></div>
      <div class="referral-steps">
        <article><b>۱</b><div><strong>لینکت را منتشر کن</strong><p>در استوری، کانال، گروه یا پیام مستقیم با مخاطبانت به اشتراک بگذار.</p></div></article>
        <i class="fa-solid fa-chevron-left"></i>
        <article><b>۲</b><div><strong>دوستت ثبت‌نام می‌کند</strong><p>باید از لینک تو وارد شود و ثبت‌نامش را با همان ورود تکمیل کند.</p></div></article>
        <i class="fa-solid fa-chevron-left"></i>
        <article><b>۳</b><div><strong>{{ $triggerIsPurchase ? 'اولین خرید موفق انجام می‌شود' : 'ثبت‌نام تأیید می‌شود' }}</strong><p>پس از تکمیل این مرحله، پاداش هر دو طرف خودکار ثبت می‌شود.</p></div></article>
      </div>
    </section>

    <section class="referral-invites-card">
      <div class="referral-section-heading"><div><span>دعوت‌های اخیر</span><small>آخرین وضعیت افرادی که با لینک تو ثبت‌نام کرده‌اند.</small></div></div>
      <div class="referral-invite-list">
        @forelse($referralData['recent_invites'] as $invite)
          @php
            $reward = $invite->rewards->first();
            if ($invite->status === 'rejected') {
              $inviteState = ['label' => 'تأیید نشد', 'class' => 'is-rejected', 'icon' => 'fa-xmark'];
            } elseif ($invite->status === 'under_review' || $reward?->status === 'pending') {
              $inviteState = ['label' => 'در حال بررسی', 'class' => 'is-review', 'icon' => 'fa-shield-halved'];
            } elseif ($reward?->status === 'paid') {
              $inviteState = ['label' => 'پاداش پرداخت شد', 'class' => 'is-paid', 'icon' => 'fa-check'];
            } elseif($triggerIsPurchase && !$invite->purchase_completed) {
              $inviteState = ['label' => 'در انتظار اولین خرید', 'class' => 'is-waiting', 'icon' => 'fa-clock'];
            } else {
              $inviteState = ['label' => 'دعوت معتبر', 'class' => 'is-qualified', 'icon' => 'fa-user-check'];
            }
            $inviteName = trim(($invite->invitee?->name ?? '').' '.($invite->invitee?->last_name ?? '')) ?: 'کاربر جدید وطن';
          @endphp
          <article class="referral-invite-row">
            <div class="referral-invite-user"><span>{{ mb_substr($inviteName, 0, 1) }}</span><div><strong>{{ $inviteName }}</strong><small>{{ $invite->created_at?->format('Y/m/d') }}</small></div></div>
            <div class="referral-invite-state {{ $inviteState['class'] }}"><i class="fa-solid {{ $inviteState['icon'] }}"></i><span>{{ $inviteState['label'] }}</span>@if($reward?->status === 'paid')<b>+{{ number_format($reward->amount) }} توکن</b>@endif</div>
          </article>
        @empty
          <div class="referral-invites-empty"><i class="fa-solid fa-link"></i><strong>هنوز دعوتی ثبت نشده</strong><p>لینکت را به اشتراک بگذار؛ اولین دعوت موفق از همین‌جا دیده می‌شود.</p></div>
        @endforelse
      </div>
    </section>
  @endif
</div>
