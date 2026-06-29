<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت — وطن استودیو')</title>

    {{-- تعریف متغیرهای CSS روت --}}
    <style>
        :root {
            --bg: #0c0c10;
            --s1: #111116;
            --s2: #16161c;
            --b1: #222230;
            --text: #ffffff;
            --text2: #a8c4a8;
            --text3: #4d7a56;
            --green: #0BBF53;
            --accent: #a07af5;
            --red: #f05c5c;
            --orange: #f5923a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'IRANSansXFaNum', sans-serif;
            background-color: var(--bg);
            color: var(--text);
        }
    </style>

    {{-- لود همزمان استایل‌ها و آیکون‌ها از طریق لاراول ویت --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>

    @yield('content')

    @stack('scripts')

</body> 
</html>