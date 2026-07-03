{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: Pagination (تعداد در صفحه + شماره صفحات)
  ورودی مورد انتظار: $products (Paginator)
  ══════════════════════════════════════════════════════════════════
--}}
<div class="pagination-bar" style="border-top:1px solid var(--border);">

  <div class="flex items-center gap-2.5">
    <span class="text-[11px]" style="color:var(--text-soft);">تعداد در صفحه:</span>
    @foreach([10, 25, 50, 100] as $size)
      <a href="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}"
         class="page-btn {{ (int) request('per_page', 15) === $size ? 'active' : '' }}">{{ $size }}</a>
    @endforeach
  </div>

  @if($products->hasPages())
    <div class="flex items-center gap-1.5">
      <a href="{{ $products->previousPageUrl() ?? '#' }}" class="page-btn {{ $products->onFirstPage() ? 'is-disabled' : '' }}">
        <i class="fa-solid fa-angle-right"></i>
      </a>

      @php
        $current = $products->currentPage();
        $last = $products->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
      @endphp

      @if($start > 1)
        <a href="{{ $products->url(1) }}" class="page-btn">1</a>
        @if($start > 2)<span class="text-[11px]" style="color:var(--text-soft);">…</span>@endif
      @endif

      @for($p = $start; $p <= $end; $p++)
        <a href="{{ $products->url($p) }}" class="page-btn {{ $p === $current ? 'active' : '' }}">{{ $p }}</a>
      @endfor

      @if($end < $last)
        @if($end < $last - 1)<span class="text-[11px]" style="color:var(--text-soft);">…</span>@endif
        <a href="{{ $products->url($last) }}" class="page-btn">{{ $last }}</a>
      @endif

      <a href="{{ $products->nextPageUrl() ?? '#' }}" class="page-btn {{ !$products->hasMorePages() ? 'is-disabled' : '' }}">
        <i class="fa-solid fa-angle-left"></i>
      </a>
    </div>
  @endif

</div>
