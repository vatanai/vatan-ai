@php
  $programActive = $referralSettings->referralIsActive();
  $triggerIsPurchase = $referralSettings->reward_trigger === 'first_purchase';
  $loginReturnUrl = route('app.profile', ['tab' => 'referral']).'#referral-program';
@endphp

<div class="profile-panel referral-program" data-panel="referral" id="referral-program" style="display:none;">
  <section class="referral-program-hero">
    <div class="referral-program-copy">
      <span class="referral-program-kicker"><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="7" cy="8" r="3"/><circle cx="17" cy="16" r="3"/><path d="M9.5 9.5 14.5 14.5M14 8h4M18 8l-2-2M18 8l-2 2M10 16H6M6 16l2-2M6 16l2 2"/></svg> همکاری در فروش وطن</span>
      <h2>{{ $referralSettings->profile_title }}</h2>
      <p class="referral-program-lead">{{ $referralSettings->profile_subtitle }}</p>
      <p class="referral-program-description">{{ $referralSettings->profile_description }}</p>
      <div class="referral-reward-summary">
        @if($referralSettings->registration_gift_enabled && $referralSettings->registration_gift_tokens > 0)
          <span><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7H8.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7Zm0 0h3.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7Z"/></svg> هدیه شروع دوستت: <b>{{ number_format($referralSettings->registration_gift_tokens) }} توکن</b></span>
        @endif
        @if($referralSettings->invitee_reward_tokens > 0)
          <span><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 1.5 6.5L20 10l-6.5 1.5L12 18l-1.5-6.5L4 10l6.5-1.5L12 2ZM19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7L19 16Z"/></svg> هدیه دعوت‌شده: <b>{{ number_format($referralSettings->invitee_reward_tokens) }} توکن</b></span>
        @endif
        <span><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v10M15 9.2c-.7-.7-1.7-1.1-3-1.1-1.5 0-2.5.8-2.5 1.8 0 2.8 5.5 1.1 5.5 4 0 1.1-1 1.9-2.7 1.9-1.2 0-2.2-.4-2.9-1.1"/></svg> پاداش هر دعوت موفق: <b>{{ number_format($referralSettings->inviter_reward_tokens) }} توکن</b></span>
        @if((float) ($referralSettings->referral_discount_percent ?? 0) > 0)
          <span><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/><path d="m6 18 12-12"/></svg> تخفیف خرید دعوت‌شده: <b>{{ rtrim(rtrim(number_format((float) $referralSettings->referral_discount_percent, 2), '0'), '.') }}٪</b></span>
        @endif
      </div>
    </div>
    <div class="referral-program-visual" aria-hidden="true">
      <div class="referral-visual-orbit"><svg class="referral-icon referral-icon--visual" viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 13.5 13.5 10.5M8 16l-1.5 1.5a3.5 3.5 0 0 1-5-5L5 9a3.5 3.5 0 0 1 5 0M16 8l1.5-1.5a3.5 3.5 0 0 1 5 5L19 15a3.5 3.5 0 0 1-5 0"/></svg></div>
      <span class="referral-visual-person is-first"><svg class="referral-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5.5 20c.7-4 2.8-6 6.5-6s5.8 2 6.5 6"/></svg></span>
      <span class="referral-visual-person is-second"><svg class="referral-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20c.7-4 2.7-6 6-6 2.4 0 4.2 1 5.2 3M18 12v7M14.5 15.5h7"/></svg></span>
      <span class="referral-visual-gift"><svg class="referral-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16v10H4zM3 7h18v3H3zM12 7v13M12 7H8.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7Z"/></svg></span>
    </div>
  </section>

  @unless($programActive)
    <div class="referral-program-notice"><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg><span>این برنامه فعلاً متوقف است. آمار قبلی تو محفوظ می‌ماند و با شروع کمپین بعدی دوباره می‌توانی دعوت کنی.</span></div>
  @endunless

  @if(session('success'))
    <div class="referral-program-feedback is-success" role="status"><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m8 12 2.5 2.5L16 9"/></svg><span>{{ session('success') }}</span></div>
  @elseif(session('error'))
    <div class="referral-program-feedback is-error" role="alert"><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v6M12 16.5v.1"/></svg><span>{{ session('error') }}</span></div>
  @endif

  <nav class="referral-subtabs" aria-label="بخش‌های همکاری در فروش">
    <button type="button" class="referral-subtab is-active" data-referral-subtab="affiliate" aria-selected="true">
      <svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="7" cy="8" r="3"/><circle cx="17" cy="16" r="3"/><path d="M9.5 9.5 14.5 14.5M14 8h4M18 8l-2-2M18 8l-2 2M10 16H6M6 16l2-2M6 16l2 2"/></svg>
      <span>همکاری در فروش</span>
    </button>
    <button type="button" class="referral-subtab" data-referral-subtab="custom-products" aria-selected="false">
      <svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 8 8-4 8 4-8 4-8-4Z"/><path d="M4 8v9l8 4 8-4V8M8 10v8M16 10v8"/></svg>
      <span>محصولات سفارشی</span>
    </button>
  </nav>

  <div class="referral-subpanel is-active" data-referral-subpanel="affiliate">
  @if($isGuest ?? false)
    <section class="referral-guest-card">
      <div><svg class="referral-icon referral-icon--visual" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div>
      <h3>لینک اختصاصی تو آماده ساخت است</h3>
      <p>وارد حسابت شو تا لینک دعوت اختصاصی، آمار دعوت‌ها و پاداش‌هایت را یکجا ببینی.</p>
      <a href="{{ route('login', ['redirect' => $loginReturnUrl]) }}">ورود و شروع همکاری</a>
    </section>
  @else
    <section class="referral-dashboard-grid">
      <article class="referral-user-stat"><span class="referral-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 3v8m0 0 3-3m-3 3-3-3M5 11c0 5 2 8 6 9 3 .7 6-1.1 6-4.1 0-2.2-1.7-3.9-3.9-3.9H11"/><path d="M16 4v5m0 0 2-2m-2 2-2-2"/></svg></span><strong>{{ number_format($referralData['visits']) }}</strong><small>ورود از لینک تو</small></article>
      <article class="referral-user-stat"><span class="referral-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><path d="M3.5 19c.6-3.2 2.5-5 5.5-5 1.6 0 2.9.5 3.9 1.4M15 15l2 2 4-4"/></svg></span><strong>{{ number_format($referralData['registrations']) }}</strong><small>ثبت‌نام با دعوت</small></article>
      <article class="referral-user-stat"><span class="referral-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M8 8a4 4 0 0 1 8 0M9 12h.01M15 12h.01"/></svg></span><strong>{{ number_format($referralData['successful_purchases']) }}</strong><small>خرید موفق</small></article>
      <article class="referral-user-stat"><span class="referral-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.5"/><path d="m4 17 4-4 3 3 2-2 5 4"/></svg></span><strong>{{ number_format($referralData['first_images']) }}</strong><small>اولین تصویر ساخته‌شده</small></article>
      <article class="referral-user-stat is-highlight"><span class="referral-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v10M15 9.2c-.7-.7-1.7-1.1-3-1.1-1.5 0-2.5.8-2.5 1.8 0 2.8 5.5 1.1 5.5 4 0 1.1-1 1.9-2.7 1.9-1.2 0-2.2-.4-2.9-1.1"/></svg></span><strong>{{ number_format($referralData['paid_tokens']) }}</strong><small>توکن دریافت‌شده</small></article>
      <article class="referral-user-stat"><span class="referral-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h13.5A2.5 2.5 0 0 1 20 10v8.5A1.5 1.5 0 0 1 18.5 20h-14A2.5 2.5 0 0 1 2 17.5v-9A1 1 0 0 1 3 7.5h1Z"/><path d="M4 7.5V5a1 1 0 0 1 1-1h11M16 13h4"/><circle cx="16" cy="13" r=".5"/></svg></span><strong>{{ number_format($referralData['pending_commission']) }}</strong><small>کمیسیون در انتظار تسویه</small></article>
    </section>

    <section class="referral-link-card {{ $programActive ? '' : 'is-disabled' }}">
      <div class="referral-section-heading">
        <div><span>۱. لینک عادی دعوت تو</span><small>مخاطب با این لینک وارد صفحهٔ اصلی سایت می‌شود و عملکردش برای تو رصد خواهد شد.</small></div>
        @if($referralData['pending_tokens'] > 0)<b><svg class="referral-icon referral-icon--xs" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12M6 21h12M8 3v4c0 2 4 3 4 5s-4 3-4 5v4M16 3v4c0 2-4 3-4 5s4 3 4 5v4"/></svg> {{ number_format($referralData['pending_tokens']) }} توکن در انتظار بررسی</b>@endif
      </div>
      <div class="referral-code-row"><span>کد دعوت تو</span><code dir="ltr">{{ $referralData['code'] }}</code><small>همین کد داخل همه لینک‌های کسب پاداش تو استفاده می‌شود.</small></div>
      <div class="referral-link-box" dir="ltr">
        <input id="referralLinkInput" value="{{ $referralData['link'] }}" readonly aria-label="لینک اختصاصی دعوت">
        <button type="button" id="copyReferralLink" @disabled(!$programActive)><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg><span>کپی لینک</span></button>
      </div>
      <p class="referral-copy-feedback" id="referralCopyFeedback" aria-live="polite"></p>
    </section>

    <section class="referral-link-card">
      <div class="referral-section-heading"><div><span>۲. لینک محصول</span><small>یک محصول را جست‌وجو و انتخاب کن؛ مخاطب مستقیم به همان محصول می‌رود و آمارش جداگانه گزارش می‌شود.</small></div></div>
      @if($programActive)
        <form method="POST" action="{{ route('profile.referral-links.store') }}" class="referral-product-form" id="referralProductForm" dir="rtl">
          @csrf
          <div class="referral-product-picker">
            <label for="referralProductSearch">محصول مقصد</label>
            <div class="referral-product-search-wrap">
              <svg class="referral-search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
              <input id="referralProductSearch" type="search" autocomplete="off" placeholder="نام محصول را جست‌وجو کن" role="combobox" aria-expanded="false" aria-controls="referralProductOptions" aria-autocomplete="list">
            </div>
            <input type="hidden" name="product_id" id="referralProductId" required>
            <div class="referral-product-options" id="referralProductOptions" role="listbox">
              @forelse(($referralProducts ?? collect()) as $product)
                @php $productTitle = $product->name_fa ?: $product->name_en; @endphp
                <button type="button" class="referral-product-option" role="option" data-product-id="{{ $product->id }}" data-product-name="{{ $productTitle }}">
                  <span>{{ $productTitle }}</span>
                  @if($product->name_en)<small dir="ltr">{{ $product->name_en }}</small>@endif
                </button>
              @empty
                <div class="referral-product-options-empty">محصول فعالی برای ساخت لینک وجود ندارد.</div>
              @endforelse
            </div>
            <small class="referral-product-selection" id="referralProductSelection" aria-live="polite">هنوز محصولی انتخاب نشده است.</small>
          </div>
          <button type="submit" id="referralProductSubmit" disabled><svg class="referral-icon referral-icon--xs" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>ساخت لینک محصول</span></button>
        </form>
      @else
        <div class="referral-product-disabled"><svg class="referral-icon referral-icon--xs" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg><span>ساخت لینک محصول تا فعال‌شدن دوباره‌ی برنامه متوقف است.</span></div>
      @endif
      <div class="referral-invite-list">
        @forelse($referralData['links'] as $link)
          <article class="referral-invite-row referral-product-link-row">
            <div class="referral-invite-user referral-link-main"><span class="referral-link-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10.5 13.5 13.5 10.5M8 16l-1.5 1.5a3.5 3.5 0 0 1-5-5L5 9a3.5 3.5 0 0 1 5 0M16 8l1.5-1.5a3.5 3.5 0 0 1 5 5L19 15a3.5 3.5 0 0 1-5 0"/></svg></span><div><strong>{{ $link->product?->name_fa ?: ($link->product?->name_en ?: 'محصول وطن') }}</strong><div class="referral-link-url-row"><small dir="ltr">{{ route('referral.link', $link->slug) }}</small><button type="button" class="referral-link-copy" data-copy-referral-link="{{ route('referral.link', $link->slug) }}" aria-label="کپی لینک محصول"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg></button></div></div></div>
            <div class="referral-link-stats" aria-label="آمار لینک محصول">
              <span><b>{{ number_format((int) $link->clicks_count) }}</b><small>کلیک</small></span>
              <span><b>{{ number_format((int) $link->registrations_count) }}</b><small>ثبت‌نام</small></span>
              <span><b>{{ number_format((int) $link->purchases_count) }}</b><small>خرید موفق</small></span>
              <span><b>{{ number_format((int) $link->first_images_count) }}</b><small>تصویر</small></span>
              <span class="is-commission"><b>{{ number_format((int) $link->commission_total) }}</b><small>کمیسیون تومان</small></span>
            </div>
            <div class="referral-link-status-box referral-invite-state {{ $link->isActive() ? 'is-paid' : 'is-rejected' }}"><span class="referral-status-label"><svg viewBox="0 0 24 24" aria-hidden="true">@if($link->isActive())<circle cx="12" cy="12" r="8.5"/><path d="m8 12 2.5 2.5L16 9"/>@else<circle cx="12" cy="12" r="8.5"/><path d="M9 9v6M15 9v6"/>@endif</svg>{{ $link->isActive() ? 'فعال' : 'غیرفعال' }}</span>@if($link->isActive())<form method="POST" action="{{ route('profile.referral-links.deactivate', $link) }}">@csrf @method('PATCH')<button type="submit">غیرفعال‌سازی</button></form>@endif</div>
          </article>
        @empty
          <div class="referral-invites-empty"><svg class="referral-icon referral-icon--visual" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4ZM4 12l8 4 8-4M4 17l8 4 8-4"/></svg><p>هنوز لینک محصولی نساخته‌ای.</p></div>
        @endforelse
      </div>
    </section>

    <section class="referral-how-card">
      <div class="referral-section-heading"><div><span>چطور پاداش می‌گیری؟</span><small>سه قدم ساده و شفاف تا دریافت توکن</small></div></div>
      <div class="referral-steps">
        <article><b>۱</b><div><strong>لینکت را منتشر کن</strong><p>در استوری، کانال، گروه یا پیام مستقیم با مخاطبانت به اشتراک بگذار.</p></div></article>
        <svg class="referral-icon referral-icon--xs" viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5-7 7 7 7"/></svg>
        <article><b>۲</b><div><strong>دوستت ثبت‌نام می‌کند</strong><p>باید از لینک تو وارد شود و ثبت‌نامش را با همان ورود تکمیل کند.</p></div></article>
        <svg class="referral-icon referral-icon--xs" viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5-7 7 7 7"/></svg>
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
            <div class="referral-invite-state {{ $inviteState['class'] }}"><svg class="referral-icon referral-icon--xs" viewBox="0 0 24 24" aria-hidden="true">@if($inviteState['class'] === 'is-rejected')<circle cx="12" cy="12" r="8.5"/><path d="m9 9 6 6M15 9l-6 6"/>@elseif($inviteState['class'] === 'is-review')<path d="M12 3 20 6v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3Z"/><path d="M12 8v4l2 1"/>@elseif($inviteState['class'] === 'is-paid' || $inviteState['class'] === 'is-qualified')<circle cx="12" cy="12" r="8.5"/><path d="m8 12 2.5 2.5L16 9"/>@else<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/>@endif</svg><span>{{ $inviteState['label'] }}</span>@if($reward?->status === 'paid')<b>+{{ number_format($reward->amount) }} توکن</b>@endif</div>
          </article>
        @empty
          <div class="referral-invites-empty"><svg class="referral-icon referral-icon--visual" viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 13.5 13.5 10.5M8 16l-1.5 1.5a3.5 3.5 0 0 1-5-5L5 9a3.5 3.5 0 0 1 5 0M16 8l1.5-1.5a3.5 3.5 0 0 1 5 5L19 15a3.5 3.5 0 0 1-5 0"/></svg><strong>هنوز دعوتی ثبت نشده</strong><p>لینکت را به اشتراک بگذار؛ اولین دعوت موفق از همین‌جا دیده می‌شود.</p></div>
        @endforelse
      </div>
    </section>
  @endif
  </div>

  <div class="referral-subpanel custom-products-panel" data-referral-subpanel="custom-products" style="display:none;">
    @if($isGuest ?? false)
      <div class="custom-products-empty">
        <span class="custom-products-empty__icon"><svg class="referral-icon referral-icon--visual" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
        <strong>محصولات سفارشی صاحب حساب</strong>
        <p>برای مشاهده محصولاتی که به نامت ثبت شده و پاداش آن‌ها وارد حسابت می‌شود، وارد حساب شو.</p>
        <a href="{{ route('login', ['redirect' => route('app.profile', ['tab' => 'referral', 'subtab' => 'custom-products'])]) }}#referral-program">ورود به حساب</a>
      </div>
    @else
      <section class="custom-products-hero">
        <div>
          <span class="custom-products-kicker"><svg class="referral-icon referral-icon--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 1.5 6.5L20 10l-6.5 1.5L12 18l-1.5-6.5L4 10l6.5-1.5L12 2ZM19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7L19 16Z"/></svg> درآمد مستقل از محصول</span>
          <h2>محصولات سفارشی تو</h2>
          <p>هر ساخت موفق با محصولی که مالک آن هستی، طبق تنظیمات همان محصول اعتبار پاداش جداگانه برایت ثبت می‌کند.</p>
        </div>
        <div class="custom-products-total"><strong>{{ number_format($creatorRewardCredits ?? 0) }}</strong><span>اعتبار پاداش دریافت‌شده</span></div>
      </section>

      <div class="custom-products-grid">
        @forelse(($creatorRewardProducts ?? collect()) as $product)
          <article class="custom-product-card">
            <a href="{{ $product->status === 'active' ? route('app.product', $product->route_slug) : '#' }}" class="custom-product-card__media {{ $product->status !== 'active' ? 'is-disabled' : '' }}">
              <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy">
              <span class="custom-product-card__status {{ $product->status === 'active' ? 'is-active' : 'is-muted' }}">{{ $product->status === 'active' ? 'فعال' : 'غیرفعال' }}</span>
            </a>
            <div class="custom-product-card__body">
              <div class="custom-product-card__heading"><h3>{{ $product->name_fa }}</h3><span>{{ $product->media_type === 'video' ? 'ویدیو' : ($product->media_type === 'both' ? 'عکس و ویدیو' : 'عکس') }}</span></div>
              <div class="custom-product-card__stats">
                <div><strong>{{ number_format((int) ($product->creator_reward_uses_count ?? 0)) }}</strong><small>استفاده موفق</small></div>
                <div><strong>{{ number_format((int) ($product->creator_reward_photo_count ?? 0)) }}</strong><small>ساخت عکس</small></div>
                <div><strong>{{ number_format((int) ($product->creator_reward_video_count ?? 0)) }}</strong><small>ساخت ویدیو</small></div>
                <div class="is-highlight"><strong>{{ number_format((int) ($product->creator_reward_credits_sum ?? 0)) }}</strong><small>اعتبار پاداش</small></div>
              </div>
              <div class="custom-product-card__rewards"><span>عکس رایگان: {{ number_format((int) data_get($product->creator_reward_settings, 'image_free', 1)) }}</span><span>ویدیو رایگان: {{ number_format((int) data_get($product->creator_reward_settings, 'video_free', 2)) }}</span><span>عکس خریداری‌شده: {{ number_format((int) data_get($product->creator_reward_settings, 'image_paid', 2)) }}</span><span>ویدیو خریداری‌شده: {{ number_format((int) data_get($product->creator_reward_settings, 'video_paid', 4)) }}</span></div>
            </div>
          </article>
        @empty
          <div class="custom-products-empty custom-products-empty--compact"><span class="custom-products-empty__icon"><svg class="referral-icon referral-icon--visual" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 8 8-4 8 4-8 4-8-4Z"/><path d="M4 8v9l8 4 8-4V8M8 10v8M16 10v8"/></svg></span><strong>هنوز محصول سفارشی برایت ثبت نشده است</strong><p>وقتی مدیر محصولی را به‌عنوان مالک برایت ثبت کند، پاداش‌ها و آمار استفاده‌اش اینجا نمایش داده می‌شود.</p></div>
        @endforelse
      </div>
    @endif
  </div>
</div>
