@php
  $type = $block['type'] ?? 'paragraph';
  $contentValue = $block['content'] ?? implode("\n", $block['items'] ?? []);
@endphp
<article class="article-block" data-content-block>
  <div class="article-block__head"><div><i class="fa-solid fa-grip-vertical article-block__handle"></i><span class="article-block__number" data-block-number>{{ is_numeric($index) ? $index+1 : '#' }}</span><strong data-block-label>بلوک محتوا</strong></div><div class="article-block__tools"><button type="button" data-move-block="up" title="انتقال به بالا"><i class="fa-solid fa-arrow-up"></i></button><button type="button" data-move-block="down" title="انتقال به پایین"><i class="fa-solid fa-arrow-down"></i></button><button type="button" data-remove-block title="حذف بلوک"><i class="fa-solid fa-trash"></i></button></div></div>
  <div class="article-block__body">
    <div class="article-field"><label>نوع بلوک</label><select class="article-select" name="content_blocks[{{ $index }}][type]" data-block-type>@foreach(['paragraph'=>'پاراگراف','heading'=>'تیتر','lead'=>'مقدمه برجسته','note'=>'نکته','quote'=>'نقل‌قول','prompt'=>'پرامپت','image'=>'تصویر','video'=>'ویدیو','list'=>'فهرست','cta'=>'دعوت به اقدام'] as $value=>$label)<option value="{{ $value }}" @selected($type===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="article-block__fields">
      <div class="article-field" data-block-field="title"><label>عنوان فرعی</label><input class="article-input" name="content_blocks[{{ $index }}][title]" value="{{ $block['title'] ?? '' }}"></div>
      <div class="article-field" data-block-field="level"><label>سطح تیتر</label><select class="article-select" name="content_blocks[{{ $index }}][level]"><option value="2" @selected(($block['level'] ?? 2)==2)>تیتر دوم</option><option value="3" @selected(($block['level'] ?? 2)==3)>تیتر سوم</option><option value="4" @selected(($block['level'] ?? 2)==4)>تیتر چهارم</option></select></div>
      <div class="article-field is-wide" data-block-field="content"><label>متن محتوا</label><textarea class="article-textarea" name="content_blocks[{{ $index }}][content]">{{ $contentValue }}</textarea></div>
      <div class="article-field is-wide" data-block-field="url"><label>نشانی فایل تصویر یا ویدیو</label><input class="article-input" name="content_blocks[{{ $index }}][url]" value="{{ $block['url'] ?? '' }}" placeholder="/assets/... یا https://..."></div>
      <div class="article-field" data-block-field="alt"><label>متن جایگزین تصویر</label><input class="article-input" name="content_blocks[{{ $index }}][alt]" value="{{ $block['alt'] ?? '' }}"></div>
      <div class="article-field" data-block-field="caption"><label>زیرنویس مدیا</label><input class="article-input" name="content_blocks[{{ $index }}][caption]" value="{{ $block['caption'] ?? '' }}"></div>
      <div class="article-field" data-block-field="button_label"><label>متن دکمه</label><input class="article-input" name="content_blocks[{{ $index }}][button_label]" value="{{ $block['button_label'] ?? '' }}"></div>
      <div class="article-field" data-block-field="button_url"><label>لینک دکمه</label><input class="article-input" name="content_blocks[{{ $index }}][button_url]" value="{{ $block['button_url'] ?? '' }}"></div>
    </div>
  </div>
</article>
