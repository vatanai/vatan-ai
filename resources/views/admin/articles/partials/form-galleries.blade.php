@php
  $galleryEditors = old('galleries');
  if ($galleryEditors === null) {
      $galleryEditors = $article->exists ? $article->galleries->map(fn($gallery) => [
          'id'=>$gallery->id,'title'=>$gallery->title,'description'=>$gallery->description,
          'source_type'=>$gallery->source_type,'display_style'=>$gallery->display_style,
          'product_category_id'=>$gallery->product_category_id,'limit'=>data_get($gallery->settings,'limit',12),
          'product_ids'=>data_get($gallery->settings,'product_ids',[]),
          'generated_image_ids'=>data_get($gallery->settings,'generated_image_ids',[]),
          'items'=>$gallery->items->map(fn($item)=>$item->only(['id','media_type','media_path','title','description','alt_text','link_url']))->all(),
      ])->all() : [];
  }
@endphp
<section class="article-card-admin">
  <div class="article-card-admin__head"><div><h2>گالری‌های هوشمند مقاله</h2><p>برای هر مقاله چند گالری دستی، محصولی، دسته‌محصول یا خروجی ساخته‌شده تعریف کنید.</p></div><button class="article-btn article-btn--small" type="button" data-add-gallery><i class="fa-solid fa-plus"></i> گالری جدید</button></div>
  <div class="article-card-admin__body"><div class="article-gallery-list" data-gallery-list>@foreach($galleryEditors as $galleryIndex=>$galleryData)@include('admin.articles.partials.form-gallery-editor',['galleryIndex'=>$galleryIndex,'galleryData'=>$galleryData])@endforeach</div><div class="article-empty-admin" data-gallery-empty @if(count($galleryEditors)) hidden @endif><i class="fa-regular fa-images"></i><strong>برای این مقاله گالری تعریف نشده است.</strong><p>در صورت نیاز، گالری اختصاصی اضافه کنید.</p></div></div>
</section>
<template id="article-gallery-template">@include('admin.articles.partials.form-gallery-editor',['galleryIndex'=>'__GALLERY__','galleryData'=>['source_type'=>'manual','display_style'=>'grid','limit'=>12,'items'=>[]]])</template>
<template id="article-manual-item-template">@include('admin.articles.partials.form-gallery-item',['galleryIndex'=>'__GALLERY__','itemIndex'=>'__ITEM__','itemData'=>[]])</template>
