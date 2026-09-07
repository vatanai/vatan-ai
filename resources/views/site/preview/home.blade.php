<!doctype html>
<html lang="fa" dir="rtl">
<head>
    @php
        $sitePage = $sitePage ?? null;
        $managedMetaTitle = $sitePage?->meta_title ?: 'وطن — ساخت بدون محدودیت';
        $managedMetaDescription = $sitePage?->meta_description ?: 'وطن؛ پلتفرم فارسی هوش مصنوعی برای ساخت عکس، ویدیو و محتوای خلاقانه.';
        $managedCanonical = $sitePage?->canonical_url ?: url()->current();
        $managedOgImage = $sitePage?->og_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($sitePage->og_image) : null;
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.google-site-verification')
    <meta name="theme-color" content="#0B0F0D" data-vatan-theme-color>
    <title>{{ $managedMetaTitle }}</title>
    <meta name="description" content="{{ $managedMetaDescription }}">
    <meta name="robots" content="{{ $sitePage && ! $sitePage->is_indexable ? 'noindex,nofollow' : 'index,follow' }}">
    <link rel="canonical" href="{{ $managedCanonical }}">
    <meta property="og:title" content="{{ $managedMetaTitle }}">
    <meta property="og:description" content="{{ $managedMetaDescription }}">
    <meta property="og:url" content="{{ $managedCanonical }}">
    @if($managedOgImage)<meta property="og:image" content="{{ $managedOgImage }}">@endif
    <script>
        (function () {
            var html = document.documentElement;
            var systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
            var configuredPageTheme = @json($sitePage?->display('theme', 'system') ?? 'system');
            var themeColorMeta = document.querySelector('[data-vatan-theme-color]');

            function resolveTheme(mode) {
                if (mode === 'system') return systemTheme.matches ? 'dark' : 'light';
                return mode === 'light' ? 'light' : 'dark';
            }

            function applyTheme(resolved) {
                html.classList.toggle('light', resolved === 'light');
                html.classList.toggle('dark', resolved === 'dark');
                if (themeColorMeta) themeColorMeta.setAttribute('content', resolved === 'light' ? '#F5F7F6' : '#0B0F0D');
            }

            window.vatanGetThemeMode = function () {
                if (configuredPageTheme === 'light' || configuredPageTheme === 'dark') return configuredPageTheme;
                return localStorage.getItem('vatan-theme') || 'dark';
            };

            window.vatanSetTheme = function (mode) {
                if (['light', 'dark', 'system'].indexOf(mode) === -1) return;
                localStorage.setItem('vatan-theme', mode);
                applyTheme(resolveTheme(mode));
                document.dispatchEvent(new CustomEvent('vatan-theme-changed', {
                    detail: { mode: mode, resolved: resolveTheme(mode) }
                }));
            };

            applyTheme(resolveTheme(window.vatanGetThemeMode()));
            systemTheme.addEventListener('change', function () {
                if (window.vatanGetThemeMode() === 'system') window.vatanSetTheme('system');
            });
        }());
    </script>
    @include('partials.site-icons')
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/plan-cards.css') }}?v={{ filemtime(public_path('css/plan-cards.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/app-footer.css') }}?v={{ filemtime(public_path('css/app-footer.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/support-widget.css') }}?v={{ filemtime(public_path('css/support-widget.css')) }}">
    @include('layouts.partials.nav-styles')
    <link rel="stylesheet" href="{{ asset('assets/site/css/home-preview.css') }}?v={{ filemtime(public_path('assets/site/css/home-preview.css')) }}">
</head>
<body class="vatan-preview">
    @include('site.preview.partials.header')

    <main>
        @include('site.preview.partials.hero')
        @include('site.preview.partials.marquee')
        @include('site.preview.partials.capabilities')
        @include('site.preview.partials.workflow')
        @include('site.preview.partials.ideas')
        @include('site.preview.partials.gallery')
        @include('site.preview.partials.pricing-proposal')
        @include('site.preview.partials.faq')
        @include('site.preview.partials.final-cta')
        @include('site.preview.partials.articles')
    </main>

    @if(!$sitePage || $sitePage->display('show_footer', true))
        @include('site.preview.partials.footer')
        <section class="vp-app-footer-wrap vp-app-footer-wrap--public" aria-label="فوتر اپ وطن">
            @include('app.partials.footer')
        </section>
    @endif
    @include('layouts.partials.nav-scripts')
    @include('support.partials.widget')
    <script src="{{ asset('assets/site/js/home-preview.js') }}?v={{ filemtime(public_path('assets/site/js/home-preview.js')) }}" defer></script>
</body>
</html>
