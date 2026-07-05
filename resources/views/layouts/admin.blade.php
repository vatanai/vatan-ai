<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت — وطن استودیو')</title>

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('admin/css/design-tokens.css') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans bg-bg text-white">
    <script>
      /* جلوگیری از چشمک‌زدن تم هنگام بارگذاری صفحه — باید اولین چیز داخل body باشد */
      (function () {
        try {
          if (localStorage.getItem('admin-theme') === 'light') {
            document.body.classList.add('light');
          }
        } catch (e) {}
      })();
    </script>

    <div class="admin-wrap" id="admin-wrap">
        <!-- مینی‌سایدبار (نوار باریک آیکونی سمت راست) -->
        @include('admin.partials.mini-sidebar')

        <!-- صدا زدن سایدبار از مسیر لوکال سیستم -->
        @include('admin.partials.sidebar')

        <!-- پرده تیره پشت اسلایدبار در حالت موبایل -->
        <div class="mobile-sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- محتوای اصلی بخش مدیریت -->
        <div class="admin-main">
            @yield('content')
        </div>
    </div>

    <script>
      /* باز/بسته کردن اسلایدبار منوها و مینی‌سایدبار در حالت موبایل (زیر ۹۰۰px)
         با دکمه همبرگری در هدر — resources/views/admin/partials/header.blade.php */
      function toggleSidebar() {
        const wrap = document.getElementById('admin-wrap');
        if (wrap) wrap.classList.toggle('sidebar-toggled');
      }
    </script>

    @yield('scripts')

</body>
</html>