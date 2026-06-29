<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>{{ $title ?? 'وطن AI' }}</title>
  <link rel="apple-touch-icon" href="{{ asset('assets/img/icon_vatan.svg') }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="AIPIX">
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  @stack('styles')
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; font-family: inherit; }
    html {
      font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;
      scrollbar-width: thin;
      scrollbar-color: #222230 transparent;
    }
    html::-webkit-scrollbar { width: 4px; }
    html::-webkit-scrollbar-track { background: transparent; }
    html::-webkit-scrollbar-thumb { background: #222230; border-radius: 99px; }
    body {
      background-color: var(--bg);
      color: var(--text);
      min-height: 100vh;
      overflow-y: scroll;
      font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    /* فضای top nav روی تبلت/دسکتاپ */
    @media (min-width: 640px) {
      body { padding-top: 64px; }
    }
  </style>

  <script>
    /* ── تم سیستم — باید اول از همه لود بشه ── */
    (function () {
      var html = document.documentElement;

      /* اعمال تم */
      function applyTheme(theme) {
        if (theme === 'light') {
          html.classList.add('light');
        } else {
          html.classList.remove('light');
        }
        localStorage.setItem('vatan-theme', theme);
      }

      /* toggle — از هر صفحه‌ای قابل صدا زدنه */
      window.vatanToggleTheme = function () {
        var current = html.classList.contains('light') ? 'light' : 'dark';
        applyTheme(current === 'light' ? 'dark' : 'light');
      };

      /* اعمال تم ذخیره‌شده قبل از render — بدون flash */
      var saved = localStorage.getItem('vatan-theme');
      if (saved === 'light') {
        applyTheme('light');
      }
    }());
  </script>
</head>
<body>

  @yield('content')

  @include('layouts.nav')

  @stack('scripts')

</body>
</html>
