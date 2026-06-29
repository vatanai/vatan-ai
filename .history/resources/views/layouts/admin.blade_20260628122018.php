<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت — وطن استودیو')</title>

    <link href="{{ asset('css/fonts.css') }}"  ="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans bg-bg text-white">

    @yield('content')

    @yield('scripts')

</body>
</html>