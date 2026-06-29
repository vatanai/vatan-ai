<!DOCTYPE html>
<html dir="rtl" lang="fa" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت — وطن استودیو')</title>

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans bg-[#0c0c10] text-white antialiased antialiased-none">

    <div class="flex min-height-screen">
        @include('admin.partials.sidebar')

        <div class="flex-1 flex flex-col min-h-screen">
            @yield('content')
        </div>
    </div>

    @yield('scripts')

</body>
</html>