@extends('layouts.admin')
@section('title', 'ساخت و تراکنش‌ها — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/service-credits.css') }}?v={{ filemtime(public_path('admin/css/service-credits.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto credit-page" id="content">
    <h1 class="credit-list-title">ساخت و تراکنش‌ها</h1>
    <div id="credit-report-region" aria-live="polite">
      @include('admin.service-credits.partials.report-panel')
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
(() => {
  const region = document.getElementById('credit-report-region');
  let controller;
  async function load(url, record = true) {
    controller?.abort();
    controller = new AbortController();
    region.classList.add('is-loading');
    region.setAttribute('aria-busy', 'true');
    const target = new URL(url, location.href);
    target.searchParams.set('fragment', '1');
    try {
      const response = await fetch(target, {headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'}, signal: controller.signal});
      if (!response.ok) throw new Error('گزارش دریافت نشد');
      region.innerHTML = await response.text();
      if (record) {
        target.searchParams.delete('fragment');
        history.pushState(null, '', target);
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        region.classList.remove('is-loading');
        region.removeAttribute('aria-busy');
        location.assign(url);
      }
      return;
    }
    region.classList.remove('is-loading');
    region.removeAttribute('aria-busy');
  }
  region.addEventListener('click', event => {
    const link = event.target.closest('a');
    if (!link || link.target || !link.closest('.credit-report-pagination, .credit-filter-actions')) return;
    event.preventDefault();
    load(link.href);
  });
  region.addEventListener('submit', event => {
    if (!event.target.matches('[data-credit-filters]')) return;
    event.preventDefault();
    const url = new URL(event.target.action);
    const params = new URLSearchParams(new FormData(event.target));
    params.set('page', '1');
    url.search = params.toString();
    load(url);
  });
  region.addEventListener('change', event => {
    if (!event.target.matches('[data-credit-per-page]')) return;
    const url = new URL(location.href);
    url.searchParams.set('per_page', event.target.value);
    url.searchParams.delete('page');
    load(url);
  });
  addEventListener('popstate', () => load(location.href, false));
})();
</script>
@endsection
