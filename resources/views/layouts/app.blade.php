<!DOCTYPE html>
<html lang="fa" dir="rtl" class="dark"> {{-- به صورت پیش‌فرض کلاس دارک اضافه شد --}}
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>@yield('page_title', ($sitePage->meta_title ?? null) ?: ($sitePage->title ?? null) ?: ($title ?? 'وطن AI'))</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @if(isset($sitePage))
    <meta name="description" content="{{ $sitePage->meta_description ?: $sitePage->subtitle }}">
    @if(!empty($sitePage->meta_keywords))<meta name="keywords" content="{{ implode(', ', $sitePage->meta_keywords) }}">@endif
    <meta name="robots" content="{{ $sitePage->is_indexable ? 'index,follow' : 'noindex,nofollow' }}">
    <link rel="canonical" href="{{ $sitePage->canonical_url ?: url()->current() }}">
    <meta property="og:title" content="{{ $sitePage->meta_title ?: $sitePage->title }}">
    <meta property="og:description" content="{{ $sitePage->meta_description ?: $sitePage->subtitle }}">
    <meta property="og:url" content="{{ $sitePage->canonical_url ?: url()->current() }}">
    <meta property="og:type" content="website">
    @if($sitePage->og_image)<meta property="og:image" content="{{ url(Storage::disk('public')->url($sitePage->og_image)) }}">@endif
    <meta name="twitter:card" content="{{ $sitePage->og_image ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $sitePage->meta_title ?: $sitePage->title }}">
    <meta name="twitter:description" content="{{ $sitePage->meta_description ?: $sitePage->subtitle }}">
  @endif

  {{-- متاتگ‌های سئوی هر صفحه (description، Open Graph، Twitter، JSON-LD) از این استک تزریق می‌شوند --}}
  @stack('meta')

  @include('partials.site-icons')

  {{-- ۱. اولویت لود فونت‌ها --}}
  <link href="{{ \App\Support\AppAsset::url('css/fonts.css') }}" rel="stylesheet">
  <link href="{{ \App\Support\AppAsset::url('css/theme-tokens.css') }}" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')
  {{-- استایل مستقل فوتر عمداً بعد از استایل صفحات لود می‌شود؛ بدون تایید کاربر جابه‌جا یا ادغام نشود. --}}
  <link href="{{ \App\Support\AppAsset::url('css/app-footer.css') }}" rel="stylesheet">

  <style>
    
    /* تعریف متغیرهای رنگی برای جلوگیری از ارور بک‌گراند */
    :root {
      --bg-color: var(--vatan-bg-page);
      --text-color: var(--vatan-text-page);
    }

    html {
      font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;
      scrollbar-width: none;
      background-color: var(--bg-color);
    }
    
    html::-webkit-scrollbar {
      width: 0;
      height: 0;
      display: none;
    }

    body {
      background-color: var(--bg-color);
      color: var(--text-color);
      min-height: 100vh;
      overflow-y: auto;
      font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* موبایل: اسکرول‌بار کاملاً مخفی بشه (فقط ظاهرش، اسکرول‌شدن صفحه دست نمی‌خوره)
       تا نه ترک اسکرول‌بار دیده بشه و نه با تغییر عرضش، نوار پایین (باتوم‌نویگیشن) جابجا بشه */
    @media (max-width: 639px) {
      html {
        scrollbar-width: none;
      }
      html::-webkit-scrollbar {
        width: 0;
        height: 0;
        display: none;
      }
      body {
        overflow-y: auto;
      }
    }
    
    /* ایجاد فضای خالی حیاتی برای هدر فیکس شده */
    @media (min-width: 640px) {
      body { padding-top: 64px !important; }
    }

    /* دکمهٔ برگشت صفحات عمومی در موبایل؛ صفحات اپ کنترل برگشت اختصاصی خودشان را دارند. */
    .mobile-page-back {
      display: none;
    }
    @media (max-width: 639px) {
      .mobile-page-back {
        position: fixed;
        top: max(14px, env(safe-area-inset-top));
        left: 14px;
        z-index: 410;
        width: 44px;
        height: 44px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-subtle);
        border-radius: 13px;
        background: color-mix(in srgb, var(--bg-card) 92%, transparent);
        color: var(--text-primary);
        box-shadow: 0 10px 24px color-mix(in srgb, var(--bg-page) 32%, transparent);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        cursor: pointer;
        font: inherit;
      }
      .mobile-page-back:hover,
      .mobile-page-back:focus-visible {
        border-color: var(--green);
        color: var(--green);
        outline: none;
      }
      .mobile-page-back i { font-size: 17px; }
    }
  </style>

  <script>
    /* ── مدیریت تم بدون Flash تصویر ──
       سه حالت پشتیبانی می‌شه: light / dark / system
       در حالت system، تم بر اساس prefers-color-scheme سیستم‌عامل کاربر تعیین و به‌صورت زنده هم‌گام می‌شه. */
    (function () {
      var html = document.documentElement;
      var mql = window.matchMedia('(prefers-color-scheme: dark)');

      function resolve(mode) {
        if (mode === 'system') return mql.matches ? 'dark' : 'light';
        return mode === 'light' ? 'light' : 'dark';
      }

      function applyResolved(resolved) {
        if (resolved === 'light') {
          html.classList.add('light');
          html.classList.remove('dark');
        } else {
          html.classList.add('dark');
          html.classList.remove('light');
        }
      }

      function broadcast(mode) {
        document.dispatchEvent(new CustomEvent('vatan-theme-changed', {
          detail: { mode: mode, resolved: resolve(mode) }
        }));
      }

      var configuredPageTheme = @json(isset($sitePage) ? $sitePage->display('theme', 'system') : 'system');

      window.vatanGetThemeMode = function () {
        if (configuredPageTheme === 'light' || configuredPageTheme === 'dark') return configuredPageTheme;
        return localStorage.getItem('vatan-theme') || 'dark';
      };

      window.vatanSetTheme = function (mode) {
        if (['light', 'dark', 'system'].indexOf(mode) === -1) return;
        localStorage.setItem('vatan-theme', mode);
        applyResolved(resolve(mode));
        broadcast(mode);
      };

      /* برای سازگاری با کدهای قدیمی که فقط بین روز/شب سوییچ می‌کردند (مثل تاگل تنظیمات پروفایل) */
      window.vatanToggleTheme = function () {
        var current = resolve(window.vatanGetThemeMode());
        window.vatanSetTheme(current === 'light' ? 'dark' : 'light');
      };

      /* اعمال اولیه بدون Flash */
      applyResolved(resolve(window.vatanGetThemeMode()));

      /* اگر حالت روی «سیستم» باشه، با تغییر لحظه‌ای ترجیح سیستم‌عامل هم‌گام بمون */
      mql.addEventListener('change', function () {
        if (window.vatanGetThemeMode() === 'system') {
          applyResolved(resolve('system'));
          broadcast('system');
        }
      });
    }());
  </script>
</head>
@php
  $hideAppFooter = request()->routeIs(
      'app.profile',
      'profile',
      'profile.gallery',
      'app.create',
      'app.create.preview',
      'app.create.architecture',
      'app.product',
      'app.product-details'
  );

  $showAppFooter = ! $hideAppFooter && request()->routeIs(
      'app.*',
      'products.index',
      'categories.show',
      'profile',
      'profile.gallery',
      'prompts.show',
      'privacy'
  );

  if (isset($sitePage)) {
      $showAppFooter = (bool) $sitePage->display('show_footer', $showAppFooter);
  }

  $managedPageLayout = isset($sitePage) ? $sitePage->display('layout_width', 'default') : 'default';

  $showMobilePageBack = request()->routeIs(
      'pricing.index',
      'pricing.checkout',
      'payments.*',
      'site.about',
      'site.sitemap',
      'privacy',
      'articles.*'
  );
  $mobileBackFallback = request()->routeIs('payments.receipt')
      ? route('app.profile', ['tab' => 'account'])
      : (request()->routeIs('pricing.checkout', 'payments.*')
          ? route('pricing.index')
          : route('site.home.root'));
@endphp
<body id="top" @class(['vatan-app-shell' => $showAppFooter, 'site-page-managed' => isset($sitePage), 'site-page-layout-' . $managedPageLayout => isset($sitePage)]) @if(isset($sitePage)) data-site-page="{{ $sitePage->key }}" data-site-page-version="{{ $sitePage->version }}" @endif>

  {{-- محتوای اصلی صفحات --}}
  <main>
    @yield('content')
  </main>

  @if($showAppFooter)
    @if(request()->routeIs('privacy'))
      @include('site.preview.partials.footer')
      <section class="vp-app-footer-wrap vp-app-footer-wrap--public" aria-label="فوتر اپ وطن">
        @include('app.partials.footer')
      </section>
    @else
      @include('app.partials.footer')
    @endif
  @endif

  @if($showMobilePageBack)
    @include('site.partials.mobile-page-back', ['mobileBackFallback' => $mobileBackFallback])
  @endif

  {{-- ناوبری هدر و فوتر موبایل --}}
  @include('layouts.nav')
  @include('partials.token-alert-modal')

  @if($showMobilePageBack)
    <script>
      document.querySelectorAll('[data-mobile-page-back]').forEach(function (button) {
        button.addEventListener('click', function () {
          var sameOriginReferrer = false;
          try {
            sameOriginReferrer = document.referrer !== '' && new URL(document.referrer).origin === window.location.origin;
          } catch (error) {}
          if (sameOriginReferrer && window.history.length > 1) {
            window.history.back();
            return;
          }
          window.location.href = button.dataset.fallbackUrl;
        });
      });
    </script>
  @endif

  @stack('scripts')

</body>
</html>
