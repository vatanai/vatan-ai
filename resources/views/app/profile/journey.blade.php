@php
  $journey = $journeyData ?? [];
  $steps = $journey['steps'] ?? [];
  $completed = (int) ($journey['completed_count'] ?? 0);
  $stepCount = max(1, count($steps));
  $progress = min(100, (int) round(($completed / $stepCount) * 100));
  $partnerLead = $journey['existing_partner_lead'] ?? null;
@endphp

<section class="profile-journey {{ ($journeyCompact ?? false) ? 'profile-journey--compact' : '' }}" aria-labelledby="profile-journey-title">
  <div class="journey-head">
    <div>
      <span class="journey-kicker">مسیر تو در وطن</span>
      <h2 id="profile-journey-title">هر قدمت ثبت می‌شود؛ قدم بعدی هم روشن است</h2>
      <p>از اولین ورود تا ساخت محتوا و همکاری فروش، اطلاعات مهم حسابت را یک‌جا ببین.</p>
    </div>
    <div class="journey-progress" aria-label="درصد پیشرفت مسیر">
      <strong>{{ $progress }}٪</strong>
      <span>پیشرفت مسیر</span>
    </div>
  </div>

  @if($isGuest ?? false)
    <div class="journey-guest">
      <div class="journey-guest-icon"><i class="fa-solid fa-route"></i></div>
      <div>
        <strong>مسیر اختصاصی‌ات بعد از ورود فعال می‌شود</strong>
        <p>زمان ورود، خروجی‌ها و قدم بعدی‌ات را منظم و شفاف دنبال کن.</p>
      </div>
      <a href="{{ route('login') }}" class="journey-button journey-button-primary">ورود و شروع مسیر <i class="fa-solid fa-arrow-left"></i></a>
    </div>
  @else
    <div class="journey-stats" aria-label="آمار مسیر کاربر">
      <div class="journey-stat"><span class="journey-stat-icon"><i class="fa-solid fa-clock"></i></span><div><small>آخرین ورود</small><strong>{{ $journey['last_login_at']?->format('Y/m/d H:i') ?? 'هنوز ثبت نشده' }}</strong></div></div>
      <div class="journey-stat"><span class="journey-stat-icon"><i class="fa-solid fa-right-to-bracket"></i></span><div><small>تعداد ورود</small><strong>{{ number_format((int) ($journey['login_count'] ?? 0)) }}</strong></div></div>
      <div class="journey-stat"><span class="journey-stat-icon"><i class="fa-solid fa-image"></i></span><div><small>عکس ساخته‌شده</small><strong>{{ number_format((int) ($journey['image_count'] ?? 0)) }}</strong></div></div>
      <div class="journey-stat"><span class="journey-stat-icon"><i class="fa-solid fa-video"></i></span><div><small>ویدیوی ساخته‌شده</small><strong>{{ number_format((int) ($journey['video_count'] ?? 0)) }}</strong></div></div>
      <div class="journey-stat"><span class="journey-stat-icon"><i class="fa-solid fa-link"></i></span><div><small>لینک ساخته‌شده</small><strong>{{ number_format((int) ($journey['link_count'] ?? 0)) }}</strong></div></div>
      <div class="journey-stat"><span class="journey-stat-icon"><i class="fa-solid fa-coins"></i></span><div><small>اعتبار باقی‌مانده</small><strong>{{ number_format((int) ($journey['token_balance'] ?? 0)) }}</strong></div></div>
    </div>

    <div class="journey-steps" role="list" aria-label="مراحل مسیر کاربر">
      @foreach($steps as $index => $step)
        <div class="journey-step {{ $step['done'] ? 'is-done' : ($index === ($journey['current_index'] ?? 0) ? 'is-current' : '') }}" role="listitem">
          <div class="journey-step-marker"><i class="fa-solid {{ $step['done'] ? 'fa-check' : $step['icon'] }}"></i></div>
          <div class="journey-step-copy"><strong>{{ $step['title'] }}</strong><span>{{ $step['description'] }}</span>@if($step['date'])<small>{{ $step['date']->format('Y/m/d') }}</small>@endif</div>
        </div>
      @endforeach
    </div>

    @unless($journeyCompact ?? false)
      <span class="journey-status"><i class="fa-solid fa-handshake"></i> آماده همکاری فروش؛ مسیر جداگانه برای کاربران باتجربه</span>
    @endunless

    <div class="journey-next">
      <div class="journey-next-copy"><span>قدم بعدی پیشنهادی</span><strong>{{ $steps[$journey['current_index'] ?? 0]['title'] ?? 'ادامه مسیر' }}</strong></div>
      @if($partnerLead)
        <span class="journey-status"><i class="fa-solid fa-circle-check"></i> درخواست همکاری در صف پیگیری است</span>
      @elseif($journey['next_action_url'] ?? null)
        <a href="{{ $journey['next_action_url'] }}" class="journey-button journey-button-primary">ساخت اولین محتوا <i class="fa-solid fa-arrow-left"></i></a>
      @else
        <form method="POST" action="{{ route('profile.partner-interest') }}">
          @csrf
          <button type="submit" class="journey-button journey-button-primary">آماده همکاری فروش · درخواست همکاری <i class="fa-solid fa-handshake"></i></button>
        </form>
      @endif
      @if(!empty($journey['customer_point_title']))
        <span class="journey-status"><i class="fa-solid fa-route"></i> وضعیت فعلی: {{ $journey['customer_point_title'] }}</span>
      @endif
    </div>
  @endif
</section>
