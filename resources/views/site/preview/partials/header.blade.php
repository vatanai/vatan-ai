@php
    $isPublicHome = request()->routeIs('site.home.root');
    $profilePlanLabel = auth()->check()
        ? 'پلن ' . auth()->user()->plan_display_name . ' | ' . number_format(auth()->user()->effective_token_balance) . ' اعتبار'
        : '';
    $headerTokenCount = $headerTokenCount ?? (int) (auth()->user()?->effective_token_balance ?? 0);
    $headerTokenLabel = $headerTokenLabel ?? 'اعتبار';
    $headerTokenTitle = $headerTokenTitle ?? ($profilePlanLabel ?: 'اعتبار شروع وطن');
    $referralProfileMenuEnabled = \App\Models\ReferralSetting::current()->profile_enabled;
    $publicSectionUrl = fn (string $section) => $isPublicHome
        ? '#' . $section
        : route('site.home.root') . '#' . $section;
@endphp

<header class="vp-header" data-header>
    <div class="vp-container vp-header__inner">
        <div class="vp-header__right">
            <button class="vp-menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="باز کردن منو">
                <span></span><span></span>
            </button>
            <a href="{{ $publicSectionUrl('top') }}" class="vp-brand vp-brand--app" aria-label="وطن، صفحه نخست">
                <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="31" height="31">
                <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن" width="65" height="29">
            </a>
        </div>

        <nav class="vp-nav" aria-label="ناوبری اصلی">
            <a class="{{ request()->routeIs('site.home.root') ? 'is-active' : '' }}" href="{{ $publicSectionUrl('top') }}">خانه</a>
            <a href="{{ $publicSectionUrl('features') }}">ویژگی‌ها</a>
            <a href="{{ $publicSectionUrl('ideas') }}">سبک‌ها</a>
            <a href="{{ $publicSectionUrl('gallery') }}">نمونه‌ها</a>
            <a href="{{ $publicSectionUrl('pricing-proposal') }}">تعرفه‌ها</a>
            <a href="{{ $publicSectionUrl('faq') }}">سوالات</a>
            <a href="{{ $publicSectionUrl('articles') }}" class="{{ request()->routeIs('articles.*') ? 'is-active' : '' }}">مقالات</a>
        </nav>

        <div class="vp-header__actions">
            <label class="topnav-popup vp-profile-popup" id="profile-popup">
                <input type="checkbox" aria-label="منوی کاربری">
                <div tabindex="0" class="topnav-burger" role="button" aria-label="منوی کاربری">
                @auth
                    @if(auth()->user()->avatar)<img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="پروفایل" class="topnav-burger-img">@else<span class="topnav-burger-icon" aria-hidden="true">@include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>19])</span>@endif
                @else
                    <span class="topnav-burger-icon" aria-hidden="true">@include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>19])</span>
                @endauth
                </div>
                @include('partials.topnav-profile-popup-window', ['showMobileThemeSelector' => true])
            </label>
            <span class="vp-token-box {{ auth()->guest() ? 'is-guest' : 'is-authenticated' }}" title="{{ $headerTokenTitle }}">
                <i class="vp-token-box__mark" aria-hidden="true"></i>
                <span class="vp-token-box__value"><b>{{ number_format($headerTokenCount) }}</b>@if($headerTokenLabel)<em>{{ $headerTokenLabel }}</em>@endif</span>
            </span>
            <button class="vp-theme-toggle" type="button" data-preview-theme aria-label="تغییر حالت روز و شب" title="تغییر حالت روز و شب">
                <svg class="vp-theme-toggle__moon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                <svg class="vp-theme-toggle__sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </div>
    </div>
</header>

<div class="vp-menu-overlay" data-menu-close></div>
<aside class="vp-mobile-menu" id="mobile-menu" aria-label="منوی موبایل" aria-hidden="true">
    <div class="vp-mobile-menu__head">
        <a href="{{ $publicSectionUrl('top') }}" class="vp-brand vp-brand--app" data-menu-link aria-label="وطن، صفحه نخست">
            <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="31" height="31">
            <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن" width="65" height="29">
        </a>
        <button class="vp-close-button" type="button" data-menu-close aria-label="بستن منو">×</button>
    </div>
    <nav class="vp-mobile-menu__nav">
        <a href="{{ $publicSectionUrl('top') }}" data-menu-link>خانه</a>
        <a href="{{ $publicSectionUrl('features') }}" data-menu-link>ویژگی‌ها</a>
        <a href="{{ $publicSectionUrl('ideas') }}" data-menu-link>سبک‌ها</a>
        <a href="{{ $publicSectionUrl('gallery') }}" data-menu-link>نمونه‌ها</a>
        <a href="{{ $publicSectionUrl('pricing-proposal') }}" data-menu-link>تعرفه‌ها</a>
        <a href="{{ $publicSectionUrl('faq') }}" data-menu-link>سوالات</a>
        <a href="{{ $publicSectionUrl('articles') }}" data-menu-link>مقالات</a>
    </nav>
    <div class="vp-mobile-menu__actions">
        <a class="vp-button vp-button--primary" href="{{ route('app.home') }}">رایگان شروع کن</a>
        <a class="vp-button vp-button--secondary" href="{{ route('login') }}">ورود به حساب</a>
    </div>
</aside>
