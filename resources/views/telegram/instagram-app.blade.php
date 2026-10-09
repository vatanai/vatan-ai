<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow">
  <title>ثبت پست · وطن</title>
  <script src="https://telegram.org/js/telegram-web-app.js?57"></script>
  <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
  <link rel="stylesheet" href="{{ asset('admin/css/design-tokens.css') }}?v={{ @filemtime(public_path('admin/css/design-tokens.css')) }}">
  <link rel="stylesheet" href="{{ asset('telegram/instagram-app.css') }}?v={{ @filemtime(public_path('telegram/instagram-app.css')) }}">
</head>
<body class="tga">
  {{-- مینی‌اپ تلگرام «ثبت پست» — همه‌ی رابط با telegram/instagram-app.js ساخته می‌شود. --}}
  <div id="app" aria-live="polite">
    <div class="tga-splash">
      <div class="tga-logo"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.1" class="fill"/></svg></div>
      <div class="tga-spinner"></div>
    </div>
  </div>
  <div id="toast" class="tga-toast" role="status" hidden></div>
  <script>
    window.TGA = {!! json_encode(['postId' => $postId, 'bot' => $botUsername, 'api' => url('/api/tg/instagram')], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
  </script>
  <script src="{{ asset('telegram/instagram-app.js') }}?v={{ @filemtime(public_path('telegram/instagram-app.js')) }}"></script>
</body>
</html>
