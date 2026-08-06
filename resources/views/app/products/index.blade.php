@extends('layouts.app')

@section('page_title', $pageCategory ? $pageCategory->metaTitle() : 'همه محصولات | وطن AI')

@push('meta')
<meta name="description" content="{{ $pageCategory ? $pageCategory->metaDescription() : 'همه محصولات هوش مصنوعی وطن را جستجو، مقایسه و بر اساس دسته‌بندی فیلتر کنید.' }}">
<link rel="canonical" href="{{ $pageCategory ? $pageCategory->canonicalUrl() : route('products.index') }}">
@endpush

@section('content')
@php
  $catalogUrl = $pageCategory
    ? route('categories.show', ['path' => $pageCategory->path ?: $pageCategory->slug])
    : (request()->routeIs('categories.show') ? route('categories.show') : route('products.index'));
  $persianProductTotal = \App\Support\Jalali::toPersianDigits((string) number_format($products->total()));
@endphp
<div class="catalog-page" dir="rtl">
  <div class="page-container catalog-shell">
    <header class="catalog-hero">
      <div>
        <h1>{{ $pageCategory ? $pageCategory->name_fa : 'همه محصولات' }}</h1>
        <p>{{ $pageCategory ? $pageCategory->metaDescription() : 'ابزار مناسب کارت را بین محصولات آماده پیدا کن و مستقیم شروع به ساختن کن.' }}</p>
      </div>
    </header>

    <form method="GET" action="{{ $catalogUrl }}" id="catalog-filter-form">
      <div class="catalog-layout">
        <aside class="catalog-sidebar">
          <div class="filter-heading"><div><i class="fa-solid fa-filter"></i> فیلتر محصولات</div>@if(request()->hasAny(['search','categories','media_type','pricing','sort']))<a href="{{ $catalogUrl }}">پاک‌کردن</a>@endif</div>

          <fieldset><legend>دسته‌بندی‌ها <span>{{ count($selectedCategories) ? number_format(count($selectedCategories)) . ' انتخاب' : 'چند انتخابی' }}</span></legend>
            <div class="category-options">
              @foreach($categories as $category)
                <label><input class="catalog-auto-filter" type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array($category->id, $selectedCategories)) {{ $pageCategory ? 'disabled' : '' }}><span class="checkmark"><i class="fa-solid fa-check"></i></span><span class="category-label">{{ $category->name_fa ?: $category->name }}</span><small>{{ \App\Support\Jalali::toPersianDigits((string) number_format($category->products_count)) }}</small></label>
                @if($pageCategory && $category->id === $pageCategory->id)<input type="hidden" name="categories[]" value="{{ $category->id }}">@endif
              @endforeach
            </div>
          </fieldset>

          <fieldset><legend>هزینه استفاده</legend><div class="radio-options"><label><input class="catalog-auto-filter" type="radio" name="pricing" value="" @checked(!request('pricing'))> همه</label><label><input class="catalog-auto-filter" type="radio" name="pricing" value="free" @checked(request('pricing')==='free')> رایگان</label><label><input class="catalog-auto-filter" type="radio" name="pricing" value="paid" @checked(request('pricing')==='paid')> کردیتی</label></div></fieldset>
          <button class="sidebar-submit" type="submit">نمایش نتایج</button>
        </aside>

        <section class="catalog-results" aria-label="نتایج محصولات">
          <div class="catalog-results-toolbar">
            <div class="catalog-search-stack">
              <div class="catalog-search">
                <span class="catalog-search-input"><i class="fa-solid fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="جستجو بین محصولات، کاربردها و تگ‌ها..." aria-label="جستجوی محصولات"></span>
              </div>
              @if(request()->filled('search') || count($selectedCategories))
                <small class="catalog-search-note"><i class="fa-solid fa-circle-info"></i> نتایج متناسب با فیلترهای انتخاب‌شده نمایش داده می‌شوند.</small>
              @endif
            </div>
            <div class="catalog-sort-box">
              <span class="catalog-sort-label"><i class="fa-solid fa-arrow-down-wide-short"></i> مرتب‌سازی</span>
              <select name="sort" aria-label="مرتب‌سازی" class="catalog-auto-filter">
                <option value="latest" @selected(request('sort','latest')==='latest')>جدیدترین‌ها</option>
                <option value="oldest" @selected(request('sort')==='oldest')>قدیمی‌ترین‌ها</option>
                <option value="name" @selected(request('sort')==='name')>نام محصول</option>
                <option value="credit_low" @selected(request('sort')==='credit_low')>کمترین کردیت</option>
              </select>
            </div>
            <button class="catalog-apply-button" type="submit"><i class="fa-solid fa-sliders"></i> اعمال فیلتر</button>
          </div>
          <div class="catalog-results-heading"><strong>{{ $persianProductTotal }}</strong><span>محصول پیدا شد</span></div>
          <div class="product-grid">
            @forelse($products as $product)
              <a class="product-card" href="{{ route('app.product', $product->route_slug) }}">
                <div class="product-image"><img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy">
                  <span class="product-type">{{ match($product->media_type) { 'video' => 'ویدیو', 'text' => 'متن', default => 'تصویر' } }}</span>
                </div>
                <div class="product-info"><h2>{{ $product->name_fa }}</h2><p>{{ \Illuminate\Support\Str::limit($product->description_fa, 78) }}</p>
                  <div class="product-meta"><span><i class="fa-solid fa-bolt"></i> {{ (int)$product->credit_cost === 0 ? 'رایگان' : number_format($product->credit_cost).' کردیت' }}</span><span>مشاهده <i class="fa-solid fa-arrow-left"></i></span></div>
                </div>
              </a>
            @empty
              <div class="empty-products"><i class="fa-regular fa-folder-open"></i><h2>محصولی پیدا نشد</h2><p>فیلترها یا عبارت جستجو را تغییر بده.</p><a href="{{ $catalogUrl }}">پاک‌کردن فیلترها</a></div>
            @endforelse
          </div>
          @if($products->hasPages())<div class="catalog-pagination">{{ $products->links() }}</div>@endif
        </section>
      </div>
    </form>
  </div>
</div>
@endsection

@push('styles')
<style>
.catalog-page{min-height:100vh;background:var(--bg-page);color:var(--text-primary);padding:42px 0 110px}.catalog-shell{display:block}.catalog-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:26px}.catalog-eyebrow{color:var(--accent);font-size:12px;font-weight:800;margin-bottom:9px}.catalog-hero h1{font-size:clamp(26px,4vw,42px);font-weight:900;letter-spacing:-1px;margin:0 0 8px}.catalog-hero p{color:var(--text-secondary);font-size:13px;line-height:1.9;max-width:680px;margin:0}.catalog-total{background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:16px;padding:14px 20px;display:flex;align-items:center;gap:10px;white-space:nowrap}.catalog-total strong{font-size:22px;color:var(--green)}.catalog-total span{font-size:11px;color:var(--text-secondary)}
.catalog-searchbar{display:grid;grid-template-columns:minmax(0,1fr) 170px 130px;gap:10px;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:18px;padding:10px;margin-bottom:18px;position:sticky;top:74px;z-index:20}.catalog-search{display:flex;align-items:center;gap:10px;background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:12px;padding:0 14px}.catalog-search i{color:var(--text-secondary)}.catalog-search input,.catalog-searchbar select,.catalog-sidebar select{width:100%;height:44px;background:var(--bg-surface);color:var(--text-primary);border:1px solid var(--border-subtle);border-radius:12px;padding:0 12px;outline:none;font:inherit;font-size:12px}.catalog-search input{border:0;padding:0;background:transparent}.catalog-searchbar button,.sidebar-submit{border:0;border-radius:12px;background:var(--green);color:var(--bg-page);font:inherit;font-weight:900;font-size:12px;cursor:pointer}.catalog-layout{display:grid;grid-template-columns:260px minmax(0,1fr);gap:20px;align-items:start}.catalog-sidebar{position:sticky;top:146px;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:18px;padding:18px}.filter-heading{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:900;padding-bottom:14px;border-bottom:1px solid var(--border-subtle)}.filter-heading i{color:var(--green);margin-left:6px}.filter-heading a{font-size:10px;color:var(--red);text-decoration:none}.catalog-sidebar fieldset{border:0;border-bottom:1px solid var(--border-subtle);padding:16px 0;margin:0}.catalog-sidebar legend{width:100%;display:flex;justify-content:space-between;font-size:12px;font-weight:800}.catalog-sidebar legend span{font-size:9px;color:var(--text-secondary);font-weight:500}.category-options{max-height:260px;overflow:auto;margin-top:11px;padding-left:3px}.category-options label{display:flex;align-items:center;gap:8px;padding:7px 0;cursor:pointer;font-size:11px}.category-options input{position:absolute;opacity:0}.checkmark{width:17px;height:17px;border:1px solid var(--border-subtle);border-radius:5px;display:flex;align-items:center;justify-content:center;background:var(--bg-surface);flex:none}.checkmark i{font-size:8px;opacity:0;color:var(--bg-page)}.category-options input:checked+.checkmark{background:var(--green);border-color:var(--green)}.category-options input:checked+.checkmark i{opacity:1}.category-label{flex:1}.category-options small{color:var(--text-secondary)}.radio-options{display:flex;gap:14px;margin-top:12px;font-size:11px;color:var(--text-secondary)}.radio-options label{cursor:pointer}.sidebar-submit{width:100%;height:40px;margin-top:16px}.active-filter-note{border:1px solid var(--border-subtle);background:var(--bg-card);color:var(--text-secondary);padding:11px 14px;border-radius:12px;font-size:11px;margin-bottom:14px}.active-filter-note i{color:var(--accent);margin-left:5px}.product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.product-card{display:block;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:18px;overflow:hidden;text-decoration:none;color:var(--text-primary);transition:transform .2s ease,border-color .2s ease}.product-card:hover{transform:translateY(-3px);border-color:var(--accent)}.product-image{aspect-ratio:1/1;position:relative;overflow:hidden;background:var(--bg-surface)}.product-image img{width:100%;height:100%;object-fit:cover;transition:transform .35s}.product-card:hover .product-image img{transform:scale(1.035)}.product-type{position:absolute;top:10px;right:10px;padding:5px 9px;border-radius:99px;background:var(--bg-card);border:1px solid var(--border-subtle);font-size:9px;font-weight:800}.product-info{padding:14px}.product-info h2{font-size:14px;margin:0 0 6px;font-weight:900}.product-info p{font-size:10px;color:var(--text-secondary);line-height:1.8;height:36px;margin:0}.product-meta{display:flex;justify-content:space-between;align-items:center;margin-top:13px;padding-top:11px;border-top:1px solid var(--border-subtle);font-size:10px;color:var(--text-secondary)}.product-meta span:first-child i{color:var(--green)}.product-meta span:last-child{color:var(--accent)}.empty-products{grid-column:1/-1;text-align:center;background:var(--bg-card);border:1px dashed var(--border-subtle);border-radius:18px;padding:70px 20px}.empty-products>i{font-size:34px;color:var(--text-secondary)}.empty-products h2{font-size:17px}.empty-products p{font-size:11px;color:var(--text-secondary)}.empty-products a{display:inline-block;margin-top:8px;color:var(--accent);font-size:11px}.catalog-pagination{margin-top:24px}
@media(max-width:1050px){.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.catalog-page{padding-top:24px}.catalog-hero{align-items:flex-start}.catalog-total{display:none}.catalog-searchbar{grid-template-columns:1fr 110px;top:10px}.catalog-search{grid-column:1/-1}.catalog-searchbar button{height:44px}.catalog-layout{grid-template-columns:1fr}.catalog-sidebar{position:static}.category-options{max-height:180px}.product-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.product-info{padding:11px}.product-info p{display:none}}@media(max-width:430px){.product-grid{grid-template-columns:1fr 1fr}.catalog-hero h1{font-size:27px}.product-info h2{font-size:12px}.product-meta{font-size:9px}.product-meta span:last-child{display:none}}
.catalog-page{--accent:var(--green);}
.category-options{position:relative;}
.catalog-layout{grid-template-areas:'sidebar results';}
.catalog-sidebar{grid-area:sidebar;}
.catalog-results{grid-area:results;min-width:0;}
.catalog-results-toolbar{display:grid;grid-template-columns:minmax(0,1fr) 170px 130px;gap:8px;align-items:stretch;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:18px;padding:8px;margin-bottom:12px;position:sticky;top:74px;z-index:20;}
.catalog-search-stack{display:flex;min-width:0;flex-direction:column;justify-content:center;gap:3px;}
.catalog-search-stack .catalog-search{display:flex;min-height:42px;height:42px;align-items:center;gap:10px;padding:0 12px;}
.catalog-search-input{display:flex;width:100%;min-height:40px;align-items:center;gap:10px;}
.catalog-search-note{display:block;margin:0 2px;color:var(--text-secondary);font-size:9px;line-height:1.25;white-space:normal;overflow:hidden;text-overflow:ellipsis;}
.catalog-search-note i{color:var(--green);margin-left:4px;}
.catalog-results-toolbar>button{min-height:42px;height:42px;align-self:start;}
.catalog-apply-button{border:0;border-radius:12px;background:var(--green);color:var(--bg-page);font:inherit;font-size:12px;font-weight:900;cursor:pointer;transition:transform .2s ease,filter .2s ease;}
.catalog-apply-button:hover{filter:brightness(.95);transform:translateY(-1px);}
.catalog-sort-box{display:flex;min-width:0;height:42px;min-height:42px;align-items:center;gap:6px;margin:0;padding:0 9px;background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:12px;}
.catalog-sort-label{display:flex;align-items:center;gap:5px;color:var(--text-secondary);font-size:10px;font-weight:800;white-space:nowrap;}
.catalog-sort-label i{color:var(--green);}
.catalog-sort-box select{min-width:0;width:100%;height:34px;flex:1;padding:0 2px;border:0;background:transparent;color:var(--text-primary);font:inherit;font-size:10px;outline:none;}
.catalog-sort-box select:focus{border-color:var(--green);box-shadow:0 0 0 3px color-mix(in srgb,var(--green) 15%,transparent);}
.catalog-results-heading{display:flex;align-items:baseline;gap:8px;margin:4px 0 14px;padding-inline:2px;color:var(--text-primary);}
.catalog-results-heading strong,.catalog-results-heading span{font-size:21px;font-weight:850;line-height:1.2;}
.catalog-results-heading span{font-weight:700;}
.catalog-sidebar fieldset:first-of-type{padding-top:30px;}
.catalog-sidebar fieldset+fieldset{padding-top:26px;}
.sidebar-submit{background:var(--green);color:var(--bg-page);}
.product-card{transition:transform .3s ease,border-color .3s ease,box-shadow .3s ease;}
.product-card:hover{transform:translateY(-3px);border-color:var(--green);box-shadow:0 18px 36px rgba(0,0,0,.5);}
.product-card:hover .product-image img{transform:none;}
.product-meta span:last-child,.empty-products a{color:var(--green);}
.catalog-searchbar,.catalog-total,.active-filter-note{display:none;}
@media(max-width:760px){
  .catalog-layout{grid-template-areas:'sidebar' 'results';}
  .catalog-results-toolbar{grid-template-columns:minmax(0,1fr) 110px;top:10px;}
  .catalog-search-stack{grid-column:1/-1;}
  .catalog-search-stack .catalog-search{height:44px;min-height:44px;}
  .catalog-results-toolbar>button{min-height:44px;height:44px;}
  .catalog-sort-box{height:44px;min-height:44px;}
  .catalog-results-heading strong,.catalog-results-heading span{font-size:17px;}
  .catalog-sidebar{position:static;}
}
.catalog-pagination nav{color:var(--text-secondary);}
.catalog-pagination nav p{margin:0;color:var(--text-secondary);font-size:11px;}
.catalog-pagination nav a,
.catalog-pagination nav span[aria-disabled] > span,
.catalog-pagination nav span[aria-current] > span{border-color:var(--border-subtle)!important;border-radius:9px!important;box-shadow:none!important;font:inherit!important;}
.catalog-pagination nav a{background:var(--bg-card)!important;color:var(--text-primary)!important;}
.catalog-pagination nav a:hover{background:var(--bg-surface)!important;color:var(--text-primary)!important;}
.catalog-pagination nav span[aria-current] > span{background:var(--green)!important;border-color:var(--green)!important;color:var(--bg-page)!important;}
.catalog-pagination nav span[aria-disabled] > span{background:var(--bg-surface)!important;color:var(--text-secondary)!important;}
.catalog-pagination nav svg{color:currentColor;}
</style>
@endpush

@push('scripts')
<script>
  document.getElementById('catalog-filter-form')?.addEventListener('change', function (event) {
    if (event.target.classList.contains('catalog-auto-filter')) this.submit();
  });

  document.querySelector('.catalog-sort-box')?.addEventListener('click', function (event) {
    var select = this.querySelector('select');
    if (!select || event.target.closest('select')) return;
    select.focus();
    if (typeof select.showPicker === 'function') select.showPicker();
  });
</script>
@endpush
