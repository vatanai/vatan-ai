@php
  $defaultBlocks = [['type'=>'lead','content'=>''],['type'=>'heading','level'=>2,'content'=>''],['type'=>'paragraph','content'=>'']];
  $editorBlocks = old('content_blocks', $article->content_blocks ?: $defaultBlocks);
@endphp
<section class="article-card-admin">
  <div class="article-card-admin__head"><div><h2>محتوای بلوکی مقاله</h2><p>بلوک‌ها را جابه‌جا کنید و متن، تصویر، ویدیو، پرامپت یا دعوت به اقدام بسازید.</p></div><span class="article-status"><i class="fa-solid fa-layer-group"></i> <b data-block-count>{{ count($editorBlocks) }}</b> بلوک</span></div>
  <div class="article-card-admin__body">
    <div class="article-block-list" data-block-list>
      @foreach($editorBlocks as $index=>$block)
        @include('admin.articles.partials.form-block', ['index'=>$index,'block'=>$block])
      @endforeach
    </div>
    <div class="article-block-add"><select class="article-select" data-new-block-type>@foreach(['paragraph'=>'پاراگراف','heading'=>'تیتر','lead'=>'مقدمه برجسته','note'=>'نکته','quote'=>'نقل‌قول','prompt'=>'پرامپت قابل کپی','image'=>'تصویر','video'=>'ویدیو','list'=>'فهرست','cta'=>'دعوت به اقدام'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><button class="article-btn" type="button" data-add-block><i class="fa-solid fa-plus"></i> افزودن بلوک</button></div>
  </div>
</section>
<template id="article-block-template">@include('admin.articles.partials.form-block',['index'=>'__INDEX__','block'=>['type'=>'paragraph']])</template>
