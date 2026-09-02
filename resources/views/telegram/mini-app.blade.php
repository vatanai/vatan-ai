<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>وطن</title>
    <style>
        @font-face { font-family: YekanBakh; src: url('{{ asset('fonts/YekanBakh-Regular.ttf') }}') format('truetype'); }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #101713; color: #f4f7f5; font-family: YekanBakh, sans-serif; }
        main { width: min(420px, 100%); text-align: center; }
        .mark { width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 20px; background: #c2fd75; color: #103c32; display: grid; place-items: center; font-size: 28px; font-weight: 800; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0; color: #a9b4c7; line-height: 1.9; }
        .loader { width: 34px; height: 34px; margin: 26px auto 0; border: 3px solid #284238; border-top-color: #c2fd75; border-radius: 50%; animation: spin .8s linear infinite; }
        a { display: inline-flex; margin-top: 24px; padding: 11px 18px; border-radius: 12px; background: #c2fd75; color: #103c32; text-decoration: none; font-weight: 700; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<main>
    <div class="mark">و</div>
    <h1>در حال آماده‌سازی وطن</h1>
    <p id="message">چند لحظه صبر کن تا صفحه‌ی ساخت برایت باز شود.</p>
    <div class="loader" id="loader"></div>
    <a id="fallback" href="{{ $fallbackUrl }}" hidden>ادامه در سایت</a>
</main>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<script>
(() => {
    const message = document.getElementById('message');
    const loader = document.getElementById('loader');
    const fallback = document.getElementById('fallback');
    const registrationUrl = @json($registrationUrl);
    const telegram = window.Telegram?.WebApp;
    const showFallback = (text) => {
        message.textContent = text;
        loader.hidden = true;
        fallback.hidden = false;
    };
    if (!telegram || !telegram.initData) {
        showFallback('این صفحه باید از داخل بات وطن باز شود.');
        return;
    }
    telegram.ready();
    telegram.expand();
    fetch('{{ route('auth.csrf-token') }}', { credentials: 'same-origin' })
        .then(response => response.json())
        .then(csrf => fetch('{{ route('telegram.mini-app.session') }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf.token,
                'X-Telegram-Init-Data': telegram.initData
            },
            body: JSON.stringify({
                init_data: telegram.initData,
                launch_token: @json($launchToken),
                all: @json($allProducts),
                target: @json($target)
            })
        }))
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(result => {
            if (!result.ok || !result.data.redirect) throw new Error(result.data.message || 'ورود انجام نشد.');
            window.location.replace(result.data.redirect);
        })
        .catch(error => {
            if (error?.message === 'ابتدا ثبت‌نام را در بات کامل کنید.') {
                fallback.href = registrationUrl;
                fallback.textContent = 'بازگشت به بات و ثبت‌نام';
            }
            showFallback(error.message || 'ورود به صفحه‌ی ساخت انجام نشد.');
        });
})();
</script>
</body>
</html>
