<section class="article-card-admin">
  <div class="article-card-admin__head"><div><h2>هویت و موضوع مقاله</h2><p>عنوان و خلاصه در کارت‌ها، نتایج جست‌وجو و اشتراک‌گذاری استفاده می‌شوند.</p></div></div>
  <div class="article-card-admin__body article-admin">
    <div class="article-field"><label for="article-title">عنوان مقاله</label><input class="article-input" id="article-title" name="title" value="{{ old('title',$article->title) }}" maxlength="220" required data-seo-title><small>شفاف، مشخص و منطبق با قصد جست‌وجوی کاربر باشد.</small></div>
    <div class="article-field"><label for="article-excerpt">خلاصه کاربردی</label><textarea class="article-textarea" id="article-excerpt" name="excerpt" maxlength="1000" required data-seo-excerpt>{{ old('excerpt',$article->excerpt) }}</textarea><small>در دو جمله بگویید کاربر چه چیزی یاد می‌گیرد یا به چه نتیجه‌ای می‌رسد.</small></div>
    <div class="article-grid-3">
      <div class="article-field"><label for="article-category-id">دسته‌بندی اصلی</label><select class="article-select" id="article-category-id" name="article_category_id" required data-seo-category>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int)old('article_category_id',$article->article_category_id)===$category->id)>{{ $category->name }}</option>@endforeach</select></div>
      <div class="article-field"><label for="article-author-id">نویسنده</label><select class="article-select" id="article-author-id" name="article_author_id" required>@foreach($authors as $author)<option value="{{ $author->id }}" @selected((int)old('article_author_id',$article->article_author_id)===$author->id)>{{ $author->name }}</option>@endforeach</select></div>
      <div class="article-field"><label for="article-type">قالب محتوا</label><select class="article-select" id="article-type" name="content_type" required>@foreach(['guide'=>'راهنمای گام‌به‌گام','prompt_pack'=>'بسته پرامپت','comparison'=>'مقایسه و بررسی','case_study'=>'مطالعه موردی','news'=>'خبر و بروزرسانی','ideas'=>'فهرست ایده‌ها','troubleshooting'=>'رفع مشکل'] as $value=>$label)<option value="{{ $value }}" @selected(old('content_type',$article->content_type ?: 'guide')===$value)>{{ $label }}</option>@endforeach</select></div>
    </div>
  </div>
</section>
