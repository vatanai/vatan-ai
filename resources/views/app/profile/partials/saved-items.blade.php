<div class="profile-panel panel-saved" data-panel="saved">
  @forelse ($savedProducts ?? [] as $product)
    <a href="{{ route('app.product', $product->route_slug) }}" class="grid-cell" style="display:block;">
      <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" class="grid-img" loading="lazy" decoding="async">
      <div class="saved-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#ffffff"><path d="M17 3H7C5.9 3 5 3.9 5 5V21L12 18L19 21V5C19 3.9 18.1 3 17 3Z"/></svg>
      </div>
    </a>
  @empty
    <div class="grid-empty">
      <img src="{{ \App\Support\AppAsset::url('assets/img/icons/fi-sr-bookmark.svg') }}" width="32" height="32" alt="" style="opacity:.4;">
      <p>هنوز هیچ محصولی سیو نکردی</p>
      <a href="{{ route('app.explore') }}" class="btn-empty-cta">مشاهده محصولات</a>
    </div>
  @endforelse
</div>
