<header class="referral-page-head">
  <div>
    <div class="referral-eyebrow"><i class="fa-solid {{ $pageMeta['icon'] }}"></i> همکاری در فروش</div>
    <h1>{{ $pageMeta['title'] }}</h1>
    <p>{{ $pageMeta['description'] }}</p>
  </div>
  <div class="referral-head-status {{ $settings->referral_enabled ? 'is-active' : '' }}">
    <span></span>
    {{ $settings->referral_enabled ? 'برنامه فعال است' : 'برنامه غیرفعال است' }}
  </div>
</header>

@if(session('success'))
  <div class="referral-alert is-success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="referral-alert is-error"><i class="fa-solid fa-triangle-exclamation"></i>{{ $errors->first() }}</div>
@endif
