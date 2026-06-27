<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت — وطن استودیو')</title>

<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

    <link rel="stylesheet" href="{{ asset('src/output.css') }}">

    <link rel="stylesheet" href="/admin/css/admin.css">

    @stack('styles')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
</head>
<body>

    @yield('content')

    <script src="{{ asset('admin/js/admin.js') }}" defer></script>

    @yield('scripts')

</body>
</html>
