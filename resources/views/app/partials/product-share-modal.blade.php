@php
  $normalProductUrl = route('app.product', $product->route_slug);
  $shareLoginUrl = route('login', ['redirect' => $normalProductUrl]);
  $programActive = $referralSettings->referralIsActive();
@endphp

<div class="product-share-modal" id="productShareModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="productShareTitle">
  <button type="button" class="product-share-backdrop" data-close-product-share aria-label="بستن"></button>
  <section class="product-share-card">
    <header class="product-share-head">
      <div><h2 id="productShareTitle">انتشار محصول</h2><p>مشخص کنید این محصول را معمولی منتشر می‌کنید یا با لینک اختصاصی خودتان.</p></div>
      <button type="button" class="product-share-close" data-close-product-share aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </header>

    <div class="product-share-options">
      <button type="button" class="product-share-option" data-product-share data-mode="normal" data-url="{{ $normalProductUrl }}" data-title="{{ $product->name_fa }}">
        <span><i class="fa-solid fa-share-nodes"></i></span>
        <div><strong>انتشار معمولی</strong><small>لینک مستقیم محصول را بدون ثبت دعوت منتشر کن.</small></div>
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      @auth
        <button type="button" class="product-share-option is-earning" data-product-share data-mode="earning" data-url="{{ $productReferralUrl }}" data-title="{{ $product->name_fa }} در وطن" @disabled(!$programActive)>
          <span><i class="fa-solid fa-coins"></i></span>
          <div>
            <strong>انتشار برای کسب پاداش</strong>
            <small>{{ $programActive ? 'هر ثبت‌نام و خرید معتبر از این لینک، برای شما پاداش ثبت می‌کند.' : 'برنامه همکاری در فروش فعلاً غیرفعال است.' }}</small>
            <code class="product-share-code">{{ auth()->user()->referral_code }}</code>
          </div>
          <i class="fa-solid fa-chevron-left"></i>
        </button>
      @else
        <button type="button" class="product-share-option is-earning" data-product-share data-login-url="{{ $shareLoginUrl }}">
          <span><i class="fa-solid fa-coins"></i></span>
          <div><strong>انتشار برای کسب پاداش</strong><small>وارد حساب شو تا لینک اختصاصی و قابل پیگیری خودت ساخته شود.</small></div>
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
        </button>
      @endauth
    </div>
    <p class="product-share-feedback" id="productShareFeedback" aria-live="polite"></p>
  </section>
</div>
