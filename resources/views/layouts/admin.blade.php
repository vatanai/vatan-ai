<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت — وطن استودیو')</title>

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans bg-bg text-white">

    <div class="admin-wrap">
        <!-- صدا زدن سایدبار از مسیر لوکال سیستم -->
        @include('admin.partials.sidebar')

        <!-- محتوای اصلی بخش مدیریت -->
        <div class="admin-main">
            @yield('content')
        </div>
    </div>

    @yield('scripts')

</body>
</html>