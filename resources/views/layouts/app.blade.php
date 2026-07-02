<!DOCTYPE html>
<html lang="fa" dir="rtl" class="dark"> {{-- به صورت پیش‌فرض کلاس دارک اضافه شد --}}
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>{{ $title ?? 'وطن AI' }}</title>
  <link class="apple-touch-icon" href="{{ asset('assets/img/icon_vatan.svg') }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="AIPIX">

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
    
    /* ایجاد فضای خالی حیاتی برای هدر فیکس شده */
    @media (min-width: 640px) {
      body { padding-top: 64px !important; }
    }
  </style>

  <script>
    /* ── مدیریت تم بدون Flash تصویر ── */
    (function () {
      var html = document.documentElement;
      function applyTheme(theme) {
        if (theme === 'light') {
          html.classList.add('light');
          html.classList.remove('dark');
        } else {
          html.classList.add('dark');
          html.classList.remove('light');
        }
        localStorage.setItem('vatan-theme', theme);
      }

      window.vatanToggleTheme = function () {
        var current = html.classList.contains('light') ? 'light' : 'dark';
        applyTheme(current === 'light' ? 'dark' : 'light');
      };

      var saved = localStorage.getItem('vatan-theme');
      if (saved) {
        applyTheme(saved);
      } else {
        applyTheme('dark'); /* تم پیش‌فرض وطن استودیو */
      }
    }());
  </script>
</head>
<body>

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