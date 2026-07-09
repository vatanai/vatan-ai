<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>AIPIX — هوش مصنوعی</title>
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'IRANSansXFaNum', sans-serif;
            background: #000000;
            color: #ffffff;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: env(safe-area-inset-top, 0) 24px env(safe-area-inset-bottom, 0) 24px;
        }
        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin-bottom: 48px;
        }
        .logo-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-tagline {
            font-size: 14px;
            color: #4d7a56;
            font-weight: 400;
            letter-spacing: 0.3px;
        }
        .btn-enter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 320px;
            height: 54px;
            background: #0BBF53;
            color: #ffffff;
            font-family: 'IRANSansXFaNum', sans-serif;
            font-size: 16px;
            font-weight: 700;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s ease, transform 0.15s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-enter:active {
            opacity: 0.85;
            transform: scale(0.97);
        }
        .btn-enter svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .admin-link {
            margin-top: 24px;
            font-size: 12px;
            color: #4d7a56;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }
        .admin-link:hover {
            color: #a8c4a8;
        }
        .admin-link svg {
            width: 13px;
            height: 13px;
        }
    </style>
</head>
<body>

    <div class="logo-wrap">
        <div class="logo-icons">
            <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="AIPIX" style="width:42px;height:42px;display:block;">
            <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="AIPIX" style="height:28px;display:block;">
        </div>
        <p class="logo-tagline">تصاویر حرفه‌ای با هوش مصنوعی</p>
    </div>

    <a href="{{ route('app.home') }}" class="btn-enter">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
        ورود به اپلیکیشن
    </a>

    <a href="{{ route('admin.dashboard') }}" class="admin-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
        پنل مدیریت
    </a>

</body>
</html>
