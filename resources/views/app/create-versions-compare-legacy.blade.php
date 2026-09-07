<!doctype html>
<html lang="fa" dir="rtl" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $page === 'product' ? 'بساز محصول' : 'بساز' }} — {{ $source }}</title>
  <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
  <link rel="stylesheet" href="{{ asset('css/theme-tokens.css') }}?v={{ filemtime(public_path('css/theme-tokens.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/create-samples-workspace.css') }}?v={{ filemtime(public_path('css/create-samples-workspace.css')) }}">
  <style>
    html,body{margin:0;padding:0;background:var(--bg-page);color:var(--text-primary);font-family:'YekanBakh',sans-serif}
    body{min-width:1080px}
    .cw-page{min-height:100vh!important}
  </style>
</head>
<body>
  @include('app.partials.create-samples-workspace', [
      'product' => $product,
      'previewMode' => false,
      'instance' => $page === 'create' ? 'redesign' : 'compare-legacy-product',
  ])
  <script src="{{ asset('js/create-samples-workspace.js') }}?v={{ filemtime(public_path('js/create-samples-workspace.js')) }}"></script>
</body>
</html>
