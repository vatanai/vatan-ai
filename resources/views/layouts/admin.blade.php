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
<body class="font-sans" style="background:var(--page-bg);color:var(--text-main);">
    <script>
      /* جلوگیری از چشمک‌زدن تم هنگام بارگذاری صفحه — باید اولین چیز داخل body باشد.
         پیش‌فرض کل داشبورد «روز/روشن» است؛ فقط اگر کاربر صراحتاً شب را انتخاب کرده باشد تیره می‌شود. */
      (function () {
        try {
          if (localStorage.getItem('admin-theme') !== 'dark') {
            document.body.classList.add('light');
          }
        } catch (e) {
          document.body.classList.add('light');
        }
      })();
    </script>

    <div class="admin-wrap" id="admin-wrap">
        <!-- مینی‌سایدبار (نوار باریک آیکونی سمت راست) -->
        @include('admin.partials.mini-sidebar')

        <!-- صدا زدن سایدبار از مسیر لوکال سیستم -->
        @include('admin.partials.sidebar')

        <!-- پرده تیره پشت اسلایدبار در حالت موبایل -->
        <div class="mobile-sidebar-overlay" onclick="adminToggleSidebar()"></div>

        <!-- محتوای اصلی بخش مدیریت -->
        <div class="admin-main" id="admin-main">
            @yield('content')
        </div>
    </div>

    <script>
      /* باز/بسته کردن اسلایدبار منوها و مینی‌سایدبار در حالت موبایل (زیر ۹۰۰px)
         با دکمه همبرگری در هدر — resources/views/admin/partials/header.blade.php */
      /* نام اختصاصی پنل مدیریت؛ با toggleSidebar عمومی اپ تداخل ندارد. */
      function adminToggleSidebar() {
        const wrap = document.getElementById('admin-wrap');
        if (!wrap) return;

        const isToggled = wrap.classList.toggle('sidebar-toggled');
        if (window.matchMedia('(min-width: 901px)').matches) {
          try { localStorage.setItem('admin-sidebar-collapsed', isToggled ? '1' : '0'); } catch (e) {}
        }
      }

      /* وضعیت جمع‌شدن سایدبار در دسکتاپ بین صفحات حفظ می‌شود. */
      (function () {
        if (!window.matchMedia('(min-width: 901px)').matches) return;
        try {
          if (localStorage.getItem('admin-sidebar-collapsed') === '1') {
            document.getElementById('admin-wrap')?.classList.add('sidebar-toggled');
          }
        } catch (e) {}
      })();
    </script>

    @yield('scripts')

</body>
</html>
