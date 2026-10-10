{{-- Vatan SEO Engine — قالب مشترک صفحات (ساختار اجباری پنل میزبان + هدر مشترک) --}}
@extends(config('seo-engine.host.layout', 'layouts.admin'))
@section('title', ($seoTitle ?? 'سئوی هوشمند').' — '.config('seo-engine.host.name'))

@push('styles')
  @if(config('seo-engine.host.fallback_tokens'))
    <link rel="stylesheet" href="{{ route('seo.asset', 'tokens-fallback.css') }}">
  @endif
  <link rel="stylesheet" href="{{ route('seo.asset', 'seo.css') }}?v={{ config('seo-engine.version') }}-{{ @filemtime(dirname(__DIR__, 2).'/resources/assets/seo.css') }}">
@endpush

@section('content')
<main class="{{ config('seo-engine.host.main_class') }}">
  @includeIf(config('seo-engine.host.header_partial'))

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px] seo" id="content" dir="rtl" style="background:var(--page-bg);">
    @include('seo::partials.nav')

    @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $seoKey => $seoTone)
      @if(session($seoKey) && is_string(session($seoKey)))
        <div class="seo-flash is-{{ $seoTone }}" role="{{ $seoKey === 'error' ? 'alert' : 'status' }}">
          <i class="fa-solid {{ $seoTone === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
          <span>{{ session($seoKey) }}</span>
          <button type="button" data-seo-dismiss aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
        </div>
      @endif
    @endforeach
    @if($errors->any())
      <div class="seo-flash is-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i><div>@foreach($errors->all() as $seoError)<div>{{ $seoError }}</div>@endforeach</div></div>
    @endif

    <div class="seo-head">
      <div>
        <div class="seo-head-title">
          <span class="seo-head-icon"><i class="fa-solid {{ $seoIcon ?? 'fa-magnifying-glass-chart' }}"></i></span>
          <span>{{ $seoTitle ?? 'سئوی هوشمند' }}</span>
          @isset($seoHelp) @include('seo::partials.help', ['k' => $seoHelp]) @endisset
        </div>
        @isset($seoSubtitle)<div class="seo-head-sub">{{ $seoSubtitle }}</div>@endisset
      </div>
      <div class="seo-head-actions">@yield('seo-actions')</div>
    </div>

    @yield('seo-page')
  </div>
</main>
@endsection

@section('scripts')
  @stack('seo-before-scripts')
  <script src="{{ route('seo.asset', 'seo.js') }}?v={{ config('seo-engine.version') }}-{{ @filemtime(dirname(__DIR__, 2).'/resources/assets/seo.js') }}" defer></script>
  @yield('seo-scripts')
@endsection
