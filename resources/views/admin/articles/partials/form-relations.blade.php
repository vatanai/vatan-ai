<section class="article-card-admin">
  <div class="article-card-admin__head"><div><h2>تصویر اصلی، برچسب و محصولات مرتبط</h2><p>محصولات انتخابی با دعوت به اقدام مستقیم زیر مقاله نمایش داده می‌شوند.</p></div></div>
  <div class="article-card-admin__body article-admin">
    <div class="article-grid-2">
      <div class="article-field"><label for="article-featured-image">تصویر اصلی مقاله</label><input class="article-input" id="article-featured-image" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/avif"><small>پیشنهاد: نسبت ۱۶:۹ و حداقل عرض ۱۲۰۰ پیکسل.</small></div>
      <div class="article-field"><label for="article-featured-path">یا مسیر فایل موجود</label><input class="article-input" id="article-featured-path" dir="ltr" name="featured_image_path" value="{{ old('featured_image_path',$article->featured_image) }}" placeholder="assets/img/example.webp"></div>
    </div>
    @if($article->imageUrl())<div class="article-media-preview"><img src="{{ $article->imageUrl() }}" alt=""><span>تصویر فعلی مقاله</span></div>@endif
    <div class="article-field"><label for="article-featured-alt">متن جایگزین تصویر</label><input class="article-input" id="article-featured-alt" name="featured_image_alt" value="{{ old('featured_image_alt',$article->featured_image_alt) }}" maxlength="500"></div>
    <div class="article-grid-2"><div class="article-field"><label for="article-tags">برچسب‌های داخلی</label><input class="article-input" id="article-tags" name="tags" value="{{ old('tags',$article->exists ? $article->tags->pluck('name')->implode('، ') : '') }}" placeholder="پرتره، پرامپت، ساخت تصویر"><small>با ویرگول جدا کنید.</small></div><div class="article-field"><label for="article-product-search">جست‌وجوی محصول</label><input class="article-input" id="article-product-search" placeholder="نام یا کد محصول..." data-product-search></div></div>
    <div class="article-product-picker" data-product-picker>
      @php
        $selectedProducts = collect(old('product_ids', $article->exists ? $article->products->pluck('id')->all() : []))->map(fn($id) => (int) $id)->all();
      @endphp
      @foreach($products as $product)<label class="article-product-choice" data-product-choice data-search="{{ $product->name_fa }} {{ $product->product_code }}"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id,$selectedProducts,true))>@if($product->thumbnail)<img src="{{ str_starts_with($product->thumbnail,'storage/') ? asset($product->thumbnail) : asset('storage/'.ltrim($product->thumbnail,'/')) }}" alt="">@else<i class="fa-solid fa-wand-magic-sparkles"></i>@endif<span>{{ $product->name_fa }}</span></label>@endforeach
    </div>
  </div>
</section>
