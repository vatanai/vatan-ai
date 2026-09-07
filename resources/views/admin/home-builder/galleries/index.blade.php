@extends('layouts.admin')
@section('title', 'صفحه اصلی — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">
    @if(session('success'))
      <div class="mb-5 rounded-xl px-4 py-3 text-[12px] font-bold" style="color:var(--success);background:var(--success-l);border:1px solid var(--success-m);"><i class="fa-solid fa-circle-check ml-1"></i>{{ session('success') }}</div>
    @endif

    <div class="mb-6">
      <p class="mb-1 text-[11px] font-bold" style="color:var(--primary);">مدیریت سایت</p>
      <h1 class="m-0 text-xl font-extrabold" style="color:var(--text-h);">صفحه اصلی</h1>
      <p class="mt-2 mb-0 text-[12px]" style="color:var(--text-soft);">بخش‌های قابل مدیریت صفحه اصلی در اینجا قرار می‌گیرند و در ادامه می‌توان بخش‌های جدیدی به همین ساختار اضافه کرد.</p>
    </div>

    <section class="site-home-section">
      <div class="site-home-section__head"><div><h2>گالری‌های صفحه اصلی</h2><p>تصاویر و ویدیوهای گالری‌های صفحه اصلی را انتخاب کن؛ ذخیره‌سازی، بهینه‌سازی حجم و انتشار روی سایت هم‌زمان انجام می‌شود.</p></div><span><i class="fa-solid fa-images"></i> بخش فعال صفحه اصلی</span></div>
    @if(!$gallery)
      <p class="mb-4 text-[12px]" style="color:var(--text-soft);">برای تنظیم هر بخش، ابتدا کارت گالری موردنظر را انتخاب کن.</p>
      <div class="gallery-catalog">
        @foreach($galleries as $item)
          @php
            $catalogItems = collect($item->items ?? []);
            $firstItem = $catalogItems->first();
            $catalogImage = null;
            $catalogVideo = null;
            if (($firstItem['type'] ?? null) === 'product') {
                $catalogImage = optional($products->firstWhere('id', $firstItem['product_id'] ?? 0))->displayImageUrl();
            } elseif (($firstItem['type'] ?? null) === 'asset' && !empty($firstItem['path'])) {
                $catalogImage = asset($firstItem['path']);
            } elseif (($firstItem['type'] ?? null) === 'video' && !empty($firstItem['path'])) {
                $catalogVideo = asset($firstItem['path']);
                $catalogImage = !empty($firstItem['poster']) ? asset($firstItem['poster']) : null;
            } elseif (!empty($firstItem['path'])) {
                $catalogImage = asset('storage/'.$firstItem['path']);
            }
          @endphp
          <a class="gallery-catalog-card" href="{{ route('admin.home-builder.galleries.show', $item) }}">
            <div class="gallery-catalog-card__media">
              @if($catalogVideo)<video src="{{ $catalogVideo }}" poster="{{ $catalogImage }}" autoplay muted loop playsinline preload="metadata"></video>@elseif($catalogImage)<img src="{{ $catalogImage }}" alt="پیش‌نمایش {{ $item->title }}" loading="lazy">@else<i class="fa-regular fa-images"></i>@endif
              <span class="gallery-catalog-card__state {{ $item->is_active ? 'is-active' : '' }}">{{ $item->is_active ? 'در حال نمایش' : 'غیرفعال' }}</span>
            </div>
            <div class="gallery-catalog-card__body"><strong>{{ $item->title }}</strong><span>{{ $item->description }}</span><small>{{ $catalogItems->count() }} تصویر انتخاب‌شده</small></div>
            <b class="gallery-catalog-card__action">مدیریت گالری <i class="fa-solid fa-arrow-left"></i></b>
          </a>
        @endforeach
      </div>
    @else
      @php
        $items = collect($gallery->items ?? []);
        $selectedProducts = $items->where('type', 'product')->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $uploads = $items->where('type', 'upload')->pluck('path')->filter()->values();
        $staticMedia = $items->whereIn('type', ['asset', 'video'])->values();
        $settingsKey = fn (string $type, string $identity) => $type . '-' . md5($type . '|' . $identity);
      @endphp
      <div class="gallery-editor__back"><a href="{{ route('admin.home-builder.galleries.index') }}"><i class="fa-solid fa-arrow-right"></i> همه گالری‌ها</a><span>تنظیمات {{ $gallery->title }}</span></div>
      <form action="{{ route('admin.home-builder.galleries.update', $gallery) }}" method="POST" enctype="multipart/form-data" class="gallery-editor" data-gallery-editor>
        @csrf @method('PUT')
        <div class="gallery-editor__preview">
          <div class="gallery-preview__head"><div><b>پیش‌نمایش {{ $gallery->title }}</b><span>همین چیدمان، پس از ذخیره در صفحه اصلی نمایش داده می‌شود.</span></div><label class="gallery-switch"><input type="checkbox" name="is_active" value="1" @checked($gallery->is_active)><span>نمایش در سایت</span></label></div>
          <div class="gallery-preview-stage {{ $gallery->key === 'inspiration-gallery' ? 'is-inspiration' : '' }}" data-gallery-preview>
            @forelse($items as $item)
              @php($previewUrl = ($item['type'] ?? '') === 'product' ? optional($products->firstWhere('id', $item['product_id'] ?? 0))->displayImageUrl() : (($item['type'] ?? '') === 'video' ? asset($item['path']) : (($item['type'] ?? '') === 'asset' ? asset($item['path']) : (isset($item['path']) ? asset('storage/'.$item['path']) : null))))
              @if(($item['type'] ?? '') === 'video' && $previewUrl)<video src="{{ $previewUrl }}" poster="{{ !empty($item['poster']) ? asset($item['poster']) : '' }}" autoplay muted loop playsinline preload="metadata"></video>@elseif($previewUrl)<img src="{{ $previewUrl }}" alt="" loading="lazy">@endif
            @empty
              <span class="gallery-preview-empty">با انتخاب محصول یا بارگذاری عکس، پیش‌نمایش اینجا نمایش داده می‌شود.</span>
            @endforelse
          </div>
        </div>

        <div class="gallery-editor__content">
          <section class="content-card p-5">
            <div class="mb-4"><h2 class="m-0 text-[15px] font-extrabold" style="color:var(--text-h);">انتخاب عکس محصول</h2><p class="mt-1 mb-0 text-[11px]" style="color:var(--text-soft);">کاور محصول انتخابی نمایش داده می‌شود و خود تصویر در این بخش هیچ لینک یا کلیکی ندارد.</p></div>
            <div class="gallery-product-grid">
              @foreach($products as $product)
                @php($productItem = $items->first(fn ($item) => ($item['type'] ?? '') === 'product' && (int) ($item['product_id'] ?? 0) === $product->id))
                @php($productSettingsKey = $settingsKey('product', (string) $product->id))
                <label class="gallery-product-choice">
                  <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selectedProducts, true)) data-gallery-product data-gallery-item data-preview="{{ $product->displayImageUrl() }}" data-settings-key="{{ $productSettingsKey }}" data-settings-name="{{ $product->name_fa }}" data-settings-title="{{ $productItem['title'] ?? $product->name_fa }}" data-settings-tag="{{ $productItem['tag'] ?? '' }}" data-settings-text="{{ $productItem['display_text'] ?? '' }}" data-settings-link="{{ $productItem['link_url'] ?? '' }}" data-settings-link-label="{{ $productItem['link_label'] ?? '' }}" data-settings-show-text="{{ !empty($productItem['show_text']) ? '1' : '0' }}" data-settings-new-tab="{{ !empty($productItem['open_in_new_tab']) ? '1' : '0' }}">
                  <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy">
                  <span>{{ $product->name_fa }}</span><i class="fa-solid fa-check"></i>
                </label>
              @endforeach
            </div>
          </section>

          @if($staticMedia->isNotEmpty())
            <section class="content-card p-5">
              <div class="mb-3"><h2 class="m-0 text-[15px] font-extrabold" style="color:var(--text-h);">مدیای پیش‌فرض این گالری</h2><p class="mt-1 mb-0 text-[11px]" style="color:var(--text-soft);">این مدیاها همان مواردی هستند که اکنون در صفحه نخست نمایش داده می‌شوند؛ با برداشتن تیک، از گالری حذف می‌شوند.</p></div>
              <div class="gallery-uploaded-list">
                @foreach($staticMedia as $item)
                  @php($staticUrl = asset($item['path']))
                  @php($staticSettingsKey = $settingsKey($item['type'], $item['path']))
                  <label><input type="checkbox" name="keep_static_media[]" value="{{ base64_encode(json_encode($item, JSON_UNESCAPED_UNICODE)) }}" checked data-gallery-static data-gallery-item data-preview="{{ $staticUrl }}" data-media-type="{{ $item['type'] }}" data-settings-key="{{ $staticSettingsKey }}" data-settings-name="{{ $item['title'] ?? 'مدیای پیش‌فرض' }}" data-settings-title="{{ $item['title'] ?? '' }}" data-settings-tag="{{ $item['tag'] ?? '' }}" data-settings-text="{{ $item['display_text'] ?? '' }}" data-settings-link="{{ $item['link_url'] ?? '' }}" data-settings-link-label="{{ $item['link_label'] ?? '' }}" data-settings-show-text="{{ !empty($item['show_text']) ? '1' : '0' }}" data-settings-new-tab="{{ !empty($item['open_in_new_tab']) ? '1' : '0' }}">@if(($item['type'] ?? '') === 'video')<video src="{{ $staticUrl }}" poster="{{ !empty($item['poster']) ? asset($item['poster']) : '' }}" muted playsinline preload="metadata"></video>@else<img src="{{ $staticUrl }}" alt="مدیای پیش‌فرض">@endif<span>{{ $item['title'] ?? 'تصویر پیش‌فرض' }}</span></label>
                @endforeach
              </div>
            </section>
          @endif

          <section class="content-card p-5">
            <div class="mb-3"><h2 class="m-0 text-[15px] font-extrabold" style="color:var(--text-h);">یا عکس کاور جداگانه</h2><p class="mt-1 mb-0 text-[11px]" style="color:var(--text-soft);">تصاویر بزرگ به‌طور خودکار مانند تصاویر محصولات به `WebP` سبک‌تر و حداکثر ضلع `۱۶۰۰px` تبدیل می‌شوند.</p></div>
            @if($uploads->isNotEmpty())
              <div class="gallery-uploaded-list">
                @foreach($uploads as $path)
                  @php($uploadItem = $items->first(fn ($item) => ($item['type'] ?? '') === 'upload' && ($item['path'] ?? '') === $path))
                  <label><input type="checkbox" name="keep_uploads[]" value="{{ $path }}" checked data-gallery-item data-settings-key="{{ $settingsKey('upload', $path) }}" data-settings-name="{{ $uploadItem['title'] ?? 'تصویر کاور' }}" data-settings-title="{{ $uploadItem['title'] ?? '' }}" data-settings-tag="{{ $uploadItem['tag'] ?? '' }}" data-settings-text="{{ $uploadItem['display_text'] ?? '' }}" data-settings-link="{{ $uploadItem['link_url'] ?? '' }}" data-settings-link-label="{{ $uploadItem['link_label'] ?? '' }}" data-settings-show-text="{{ !empty($uploadItem['show_text']) ? '1' : '0' }}" data-settings-new-tab="{{ !empty($uploadItem['open_in_new_tab']) ? '1' : '0' }}"><img src="{{ asset('storage/'.$path) }}" alt="تصویر کاور"><span>نگه‌داشتن تصویر</span></label>
                @endforeach
              </div>
            @endif
            <label class="gallery-upload-zone"><i class="fa-solid fa-arrow-up-from-bracket"></i><b>بارگذاری عکس‌های جدید</b><span>فرمت‌های `JPG`، `PNG` و `WebP` تا `۱۲MB`</span><input type="file" name="uploads[]" accept="image/jpeg,image/png,image/webp" multiple data-gallery-upload></label>
          </section>
        </div>
        <section class="content-card p-5 gallery-item-settings-section">
          <div class="mb-4"><h2 class="m-0 text-[15px] font-extrabold" style="color:var(--text-h);">تنظیمات نمایش و لینک هر مدیا</h2><p class="mt-1 mb-0 text-[11px]" style="color:var(--text-soft);">برای هر عکس یا ویدیوی انتخاب‌شده، عنوان، متن روی تصویر و مقصد کلیک را مشخص کن. لینک می‌تواند داخلی مثل `/app/explore` یا یک آدرس کامل باشد.</p></div>
          <div class="gallery-item-settings" data-gallery-item-settings></div>
          <p class="gallery-item-settings-empty" data-gallery-item-settings-empty>ابتدا حداقل یک مدیا انتخاب کن تا تنظیمات آن نمایش داده شود.</p>
        </section>
        <div class="mt-5 flex items-center justify-end gap-3"><span class="text-[11px]" style="color:var(--text-soft);">تغییرات فقط با ذخیره روی صفحه اصلی اعمال می‌شوند.</span><button class="btn-pro" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره و انتشار گالری</button></div>
      </form>
    @endif
    </section>
  </div>
</main>

<style>
.site-home-section{padding:20px;border:1px solid var(--border);border-radius:20px;background:var(--card-bg);box-shadow:var(--shadow-card)}.site-home-section__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.site-home-section__head h2{margin:0;color:var(--text-h);font-size:15px;font-weight:800}.site-home-section__head p{margin:5px 0 0;color:var(--text-soft);font-size:11px;line-height:1.8}.site-home-section__head>span{display:inline-flex;align-items:center;gap:6px;flex:none;padding:6px 10px;border:1px solid var(--border);border-radius:999px;color:var(--primary);font-size:10px;white-space:nowrap}.site-home-section .gallery-editor__back{margin-top:-2px}.site-home-section .gallery-catalog{padding-bottom:2px}@media(max-width:650px){.site-home-section{padding:14px;border-radius:16px}.site-home-section__head{flex-direction:column;gap:9px}.site-home-section__head>span{align-self:flex-start}}
.gallery-tabs{display:flex;gap:10px;flex-wrap:wrap}.gallery-tab{min-width:190px;padding:13px 15px;border:1px solid var(--border);border-radius:14px;background:var(--card-bg);text-decoration:none;color:var(--text-soft);transition:.2s}.gallery-tab strong,.gallery-tab span{display:block}.gallery-tab strong{font-size:13px;color:var(--text-h)}.gallery-tab span{margin-top:4px;font-size:10px}.gallery-tab.is-active{border-color:var(--primary);box-shadow:0 6px 20px var(--primary-l)}.gallery-editor{display:grid;gap:16px}.gallery-editor__preview,.gallery-editor__content{display:grid;gap:16px}.gallery-preview__head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:12px}.gallery-preview__head b,.gallery-preview__head span{display:block}.gallery-preview__head b{font-size:14px;color:var(--text-h)}.gallery-preview__head span{margin-top:3px;font-size:10px;color:var(--text-soft)}.gallery-switch{display:flex;align-items:center;gap:7px;flex:none;font-size:11px;color:var(--text-soft)}.gallery-switch input{accent-color:var(--primary)}.gallery-preview-stage{display:flex;align-items:center;gap:clamp(7px,1vw,16px);min-height:250px;padding:20px;overflow:hidden;border:1px solid var(--border);border-radius:18px;background:var(--card-bg)}.gallery-preview-stage img,.gallery-preview-stage video{width:clamp(80px,11vw,160px);height:190px;flex:0 0 auto;object-fit:cover;border-radius:15px;transform:perspective(480px) rotateY(-8deg)}.gallery-preview-stage img:nth-child(3n),.gallery-preview-stage video:nth-child(3n){height:145px;transform:none}.gallery-preview-stage.is-inspiration{display:grid;grid-template-columns:1fr 1.3fr .9fr;grid-template-rows:100px 100px}.gallery-preview-stage.is-inspiration img,.gallery-preview-stage.is-inspiration video{width:100%;height:100%;transform:none;border-radius:11px}.gallery-preview-stage.is-inspiration img:first-child,.gallery-preview-stage.is-inspiration video:first-child{grid-row:span 2}.gallery-preview-empty{margin:auto;text-align:center;font-size:12px;color:var(--text-soft)}.gallery-product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(112px,1fr));gap:10px;max-height:430px;overflow:auto;padding-left:3px}.gallery-product-choice{position:relative;display:block;overflow:hidden;border:1px solid var(--border);border-radius:12px;background:var(--page-bg);cursor:pointer}.gallery-product-choice input{position:absolute;opacity:0}.gallery-product-choice img{display:block;width:100%;height:105px;object-fit:cover}.gallery-product-choice span{display:block;overflow:hidden;padding:7px;white-space:nowrap;text-overflow:ellipsis;font-size:10px;color:var(--text-soft)}.gallery-product-choice i{position:absolute;top:7px;left:7px;display:none;width:20px;height:20px;padding-top:4px;border-radius:50%;background:var(--primary);color:var(--accent);text-align:center;font-size:10px}.gallery-product-choice:has(input:checked){border-color:var(--primary)}.gallery-product-choice:has(input:checked) i{display:block}.gallery-uploaded-list{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:13px}.gallery-uploaded-list label{display:flex;align-items:center;gap:7px;padding:6px;border:1px solid var(--border);border-radius:10px;font-size:10px;color:var(--text-soft)}.gallery-uploaded-list img,.gallery-uploaded-list video{width:35px;height:35px;border-radius:6px;object-fit:cover}.gallery-upload-zone{display:flex;min-height:120px;flex-direction:column;align-items:center;justify-content:center;gap:6px;border:1px dashed var(--border);border-radius:14px;color:var(--text-soft);cursor:pointer}.gallery-upload-zone i{font-size:18px;color:var(--primary)}.gallery-upload-zone b{font-size:12px;color:var(--text-h)}.gallery-upload-zone span{font-size:10px}.gallery-upload-zone input{position:absolute;width:1px;height:1px;opacity:0}@media(min-width:1100px){.gallery-editor__content{grid-template-columns:1.25fr .75fr}}@media(max-width:650px){.gallery-preview__head{align-items:flex-start;flex-direction:column}.gallery-preview-stage{min-height:190px;padding:12px}.gallery-preview-stage img,.gallery-preview-stage video{width:82px;height:145px}.gallery-product-grid{grid-template-columns:repeat(3,1fr)}}
</style>
<style>
.gallery-item-settings{display:grid;grid-template-columns:repeat(auto-fit,minmax(255px,1fr));gap:12px}.gallery-item-setting{display:grid;gap:10px;padding:13px;border:1px solid var(--border);border-radius:13px;background:var(--input-bg)}.gallery-item-setting__head{display:flex;align-items:center;justify-content:space-between;gap:8px}.gallery-item-setting__head b{overflow:hidden;color:var(--text-h);font-size:12px;text-overflow:ellipsis;white-space:nowrap}.gallery-item-setting__head span{color:var(--text-soft);font-size:10px}.gallery-item-setting__fields{display:grid;grid-template-columns:1fr 1fr;gap:8px}.gallery-item-setting label{display:grid;gap:4px;color:var(--text-soft);font-size:10px}.gallery-item-setting input[type="text"],.gallery-item-setting input[type="url"]{width:100%;min-width:0;border:1px solid var(--border);border-radius:8px;padding:8px;background:var(--card-bg);color:var(--text-h);font:inherit;font-size:11px}.gallery-item-setting__link{grid-column:1 / -1}.gallery-item-setting__checks{display:flex;flex-wrap:wrap;gap:11px}.gallery-item-setting__checks label{display:flex;align-items:center;gap:5px}.gallery-item-settings-empty{margin:0;color:var(--text-soft);font-size:11px}.gallery-item-settings:not(:empty)+.gallery-item-settings-empty{display:none}@media(max-width:650px){.gallery-item-settings{grid-template-columns:1fr}.gallery-item-setting__fields{grid-template-columns:1fr}}
</style>
<style>
.gallery-catalog{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px}.gallery-catalog-card{display:flex;min-height:310px;flex-direction:column;overflow:hidden;border:1px solid var(--border);border-radius:18px;background:var(--card-bg);color:var(--text-soft);text-decoration:none;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.gallery-catalog-card:hover{border-color:var(--primary);box-shadow:0 10px 26px var(--primary-l);transform:translateY(-3px)}.gallery-catalog-card__media{position:relative;display:grid;height:156px;place-items:center;overflow:hidden;background:var(--page-bg);color:var(--primary);font-size:36px}.gallery-catalog-card__media img{width:100%;height:100%;object-fit:cover}.gallery-catalog-card__state{position:absolute;top:11px;right:11px;padding:4px 8px;border:1px solid var(--border);border-radius:99px;background:var(--card-bg);color:var(--text-soft);font-size:10px;font-style:normal}.gallery-catalog-card__state.is-active{border-color:var(--primary);color:var(--primary)}.gallery-catalog-card__body{display:grid;gap:6px;padding:15px 16px 10px}.gallery-catalog-card__body strong{color:var(--text-h);font-size:14px}.gallery-catalog-card__body span{min-height:34px;font-size:11px;line-height:1.65}.gallery-catalog-card__body small{font-size:10px;color:var(--text-soft)}.gallery-catalog-card__action{display:flex;align-items:center;gap:7px;margin-top:auto;padding:12px 16px;border-top:1px solid var(--border);color:var(--primary);font-size:11px}.gallery-editor__back{display:flex;align-items:center;gap:10px;margin-bottom:14px;font-size:12px}.gallery-editor__back a{display:inline-flex;align-items:center;gap:6px;color:var(--primary);text-decoration:none;font-weight:700}.gallery-editor__back span{color:var(--text-soft)}@media(max-width:650px){.gallery-catalog{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.querySelector('[data-gallery-editor]');if(!root)return;const stage=root.querySelector('[data-gallery-preview]'),settingsBox=root.querySelector('[data-gallery-item-settings]');const empty=()=>{if(!stage.querySelector('img,video'))stage.innerHTML='<span class="gallery-preview-empty">با انتخاب محصول یا بارگذاری عکس، پیش‌نمایش اینجا نمایش داده می‌شود.</span>'};const draw=()=>{stage.innerHTML='';root.querySelectorAll('[data-gallery-product]:checked').forEach(input=>{const image=document.createElement('img');image.src=input.dataset.preview;image.alt='';stage.append(image)});root.querySelectorAll('.gallery-uploaded-list input:checked').forEach(input=>{if(input.hasAttribute('data-gallery-static')&&input.dataset.mediaType==='video'){const video=document.createElement('video');video.src=input.dataset.preview;video.muted=true;video.autoplay=true;video.loop=true;video.playsInline=true;stage.append(video);return}const image=input.parentElement.querySelector('img').cloneNode();stage.append(image)});empty()};const readCurrent=()=>Object.fromEntries([...settingsBox.querySelectorAll('[data-settings-card]')].map(card=>[card.dataset.settingsCard,Object.fromEntries([...card.querySelectorAll('[data-setting]')].map(field=>[field.dataset.setting,field.type==='checkbox'?field.checked?1:0:field.value]))]));const addSettingsCard=(item,saved)=>{const key=item.dataset.settingsKey,values=saved[key]||{};const card=document.createElement('article');card.className='gallery-item-setting';card.dataset.settingsCard=key;card.innerHTML='<div class="gallery-item-setting__head"><b></b><span>تنظیمات این مدیا</span></div><div class="gallery-item-setting__fields"><label>عنوان / متن اصلی<input type="text" data-setting="title"></label><label>برچسب کوتاه<input type="text" data-setting="tag"></label><label>متن جایگزین روی تصویر<input type="text" data-setting="display_text"></label><label>متن دکمه<input type="text" data-setting="link_label" placeholder="مثلاً مشاهده بیشتر"></label><label class="gallery-item-setting__link">لینک مقصد<input type="url" data-setting="link_url" dir="ltr" placeholder="/app/explore یا https://..."></label></div><div class="gallery-item-setting__checks"><label><input type="checkbox" data-setting="show_text"> نمایش متن روی مدیا</label><label><input type="checkbox" data-setting="open_in_new_tab"> بازشدن در تب جدید</label></div>';card.querySelector('b').textContent=item.dataset.settingsName||'مدیای انتخاب‌شده';card.querySelectorAll('[data-setting]').forEach(field=>{const name=field.dataset.setting;field.name='item_settings['+key+']['+name+']';const value=Object.prototype.hasOwnProperty.call(values,name)?values[name]:item.dataset['settings'+name.split('_').map(part=>part.charAt(0).toUpperCase()+part.slice(1)).join('')];if(field.type==='checkbox')field.checked=String(value)==='1'||value===true;else field.value=value||''});settingsBox.append(card)};const syncSettings=()=>{const saved=readCurrent();settingsBox.innerHTML='';root.querySelectorAll('[data-gallery-item]:checked').forEach(item=>addSettingsCard(item,saved))};root.querySelectorAll('[data-gallery-product],.gallery-uploaded-list input').forEach(input=>input.addEventListener('change',()=>{draw();syncSettings()}));root.querySelector('[data-gallery-upload]')?.addEventListener('change',event=>{Array.from(event.target.files||[]).forEach(file=>{const image=document.createElement('img');image.src=URL.createObjectURL(file);image.alt='پیش‌نمایش عکس جدید';stage.querySelector('.gallery-preview-empty')?.remove();stage.append(image)})});syncSettings()});
</script>
<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.querySelector('[data-gallery-editor]');const fileInput=root?.querySelector('[data-gallery-upload]');const settingsBox=root?.querySelector('[data-gallery-item-settings]');if(!fileInput||!settingsBox)return;const makeUploadSettings=(file,index)=>{const key='new-upload-'+index,card=document.createElement('article');card.className='gallery-item-setting';card.dataset.newUploadSetting='true';card.innerHTML='<div class="gallery-item-setting__head"><b></b><span>تنظیمات عکس جدید</span></div><div class="gallery-item-setting__fields"><label>عنوان / متن اصلی<input type="text" name="item_settings['+key+'][title]"></label><label>برچسب کوتاه<input type="text" name="item_settings['+key+'][tag]"></label><label>متن جایگزین روی تصویر<input type="text" name="item_settings['+key+'][display_text]"></label><label>متن دکمه<input type="text" name="item_settings['+key+'][link_label]" placeholder="مثلاً مشاهده بیشتر"></label><label class="gallery-item-setting__link">لینک مقصد<input type="url" name="item_settings['+key+'][link_url]" dir="ltr" placeholder="/app/explore یا https://..."></label></div><div class="gallery-item-setting__checks"><label><input type="checkbox" name="item_settings['+key+'][show_text]" value="1"> نمایش متن روی مدیا</label><label><input type="checkbox" name="item_settings['+key+'][open_in_new_tab]" value="1"> بازشدن در تب جدید</label></div>';card.querySelector('b').textContent=file.name;settingsBox.append(card)};fileInput.addEventListener('change',event=>{settingsBox.querySelectorAll('[data-new-upload-setting]').forEach(card=>card.remove());Array.from(event.target.files||[]).forEach(makeUploadSettings)})});
</script>
@endsection
