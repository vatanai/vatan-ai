{{-- صفحه‌بندی سبک با کلاس‌های توکن‌دار: $paginator --}}
@if($paginator->hasPages())
  <div class="pagination-bar">
    <span class="si-muted">صفحه‌ی {{ \App\Services\SmartInstagram\Ui::n($paginator->currentPage()) }} از {{ \App\Services\SmartInstagram\Ui::n($paginator->lastPage()) }} · {{ \App\Services\SmartInstagram\Ui::n($paginator->total()) }} مورد</span>
    <div style="display:flex;gap:6px">
      <a class="page-btn {{ $paginator->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $paginator->previousPageUrl() }}" aria-label="قبلی"><i class="fa-solid fa-angle-right"></i></a>
      @foreach(range(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page)
        <a class="page-btn {{ $page === $paginator->currentPage() ? 'active' : '' }}" href="{{ $paginator->url($page) }}">{{ \App\Services\SmartInstagram\Ui::n($page) }}</a>
      @endforeach
      <a class="page-btn {{ $paginator->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $paginator->nextPageUrl() }}" aria-label="بعدی"><i class="fa-solid fa-angle-left"></i></a>
    </div>
  </div>
@endif
