{{-- کارخانه‌ی محتوای وطن — قالب مشترک (ساختار اجباری پنل وطن + هدر مشترک) --}}
@extends(config('content-factory.host.layout', 'layouts.admin'))
@section('title', ($cfTitle ?? 'کارخانه‌ی محتوا').' — '.config('content-factory.name'))

@push('styles')
  <link rel="stylesheet" href="{{ route('cf.asset', 'cf.css') }}?v={{ config('content-factory.version') }}-{{ @filemtime(dirname(__DIR__).'/assets/cf.css') }}">
@endpush

@section('content')
<main class="{{ config('content-factory.host.main_class') }}">
  @includeIf(config('content-factory.host.header_partial'))

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px] cf" id="content" dir="rtl" style="background:var(--page-bg);">
    @include('cf::partials.nav')

    @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $cfKey => $cfTone)
      @if(session($cfKey) && is_string(session($cfKey)))
        <div class="cf-flash is-{{ $cfTone }}" role="{{ $cfKey === 'error' ? 'alert' : 'status' }}">
          <i class="fa-solid {{ $cfTone === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
          <span>{{ session($cfKey) }}</span>
          <button type="button" data-cf-dismiss aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
        </div>
      @endif
    @endforeach
    @if($errors->any())
      <div class="cf-flash is-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i><div>@foreach($errors->all() as $cfError)<div>{{ $cfError }}</div>@endforeach</div></div>
    @endif

    <div class="cf-head">
      <div>
        <div class="cf-head-title"><span class="cf-head-icon"><i class="fa-solid {{ $cfIcon ?? 'fa-industry' }}"></i></span><span>{{ $cfTitle ?? 'کارخانه‌ی محتوا' }}</span>@isset($cfHelp) @include('cf::partials.help', ['k' => $cfHelp]) @endisset</div>
        @isset($cfSubtitle)<div class="cf-head-sub">{{ $cfSubtitle }}</div>@endisset
      </div>
      <div class="cf-head-actions">@yield('cf-actions')</div>
    </div>

    @yield('cf-page')
  </div>
</main>
@endsection

@section('scripts')
  <script src="{{ route('cf.asset', 'cf.js') }}?v={{ config('content-factory.version') }}-{{ @filemtime(dirname(__DIR__).'/assets/cf.js') }}" defer></script>
  @yield('cf-scripts')
@endsection
