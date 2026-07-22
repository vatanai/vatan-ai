<!DOCTYPE html>
<html lang="fa" dir="rtl" class="dark"> {{-- به صورت پیش‌فرض کلاس دارک اضافه شد --}}
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>@yield('page_title', $title ?? 'وطن AI')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- متاتگ‌های سئوی هر صفحه (description، Open Graph، Twitter، JSON-LD) از این استک تزریق می‌شوند --}}
  @stack('meta')

  @include('partials.site-icons')

  {{-- ۱. اولویت لود فونت‌ها --}}
  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')

  <style>
    
    /* تعریف متغیرهای رنگی برای جلوگیری از ارور بک‌گراند */
    :root {
      --bg-color: #0c0c10;
      --text-color: #ffffff;
    }
    html.light {
      --bg-color: #ffffff;
      --text-color: #0c0c10;
    }

    html {
      font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;
      scrollbar-width: thin;
      scrollbar-color: #222230 transparent;
      background-color: var(--bg-color);
    }
    
    html::-webkit-scrollbar { width: 4px; }
    html::-webkit-scrollbar-track { background: transparent; }
    html::-webkit-scrollbar-thumb { background: #222230; border-radius: 99px; }

    body {
      background-color: var(--bg-color);
      color: var(--text-color);
      min-height: 100vh;
      overflow-y: scroll;
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

      window.vatanGetThemeMode = function () {
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
<body id="top">

  {{-- محتوای اصلی صفحات --}}
  <main>
    @yield('content')
  </main>

  {{-- ناوبری هدر و فوتر موبایل --}}
  @include('layouts.nav')
  @include('partials.token-alert-modal')

  @stack('scripts')

</body>
</html>
