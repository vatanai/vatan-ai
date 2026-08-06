<!DOCTYPE html>
<html lang="fa" dir="rtl" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    html,body{margin:0;min-height:100%;background:#000;color:#fff;font-family:'YekanBakh',sans-serif;overflow-x:hidden}
    body{padding:18px!important}.hb-preview-page{width:100%;max-width:1180px;margin:0 auto}.hb-preview-page .hb-section:first-child .home-section-title,.hb-preview-page .hb-section:first-child>*:first-child{margin-top:0}
    a{pointer-events:none}
  </style>
  @include('app.home-builder.partials.styles')
</head>
<body>
  <main class="hb-preview-page">
    @include('app.home-builder.dispatcher', ['item' => $item])
  </main>
</body>
</html>
