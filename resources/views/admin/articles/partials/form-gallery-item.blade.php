<div class="article-manual-item" data-manual-item>
  @if(!empty($itemData['id']))<input type="hidden" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][id]" value="{{ $itemData['id'] }}">@endif
  <div class="article-field"><label>نوع</label><select class="article-select" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][media_type]"><option value="image" @selected(($itemData['media_type'] ?? 'image')==='image')>تصویر</option><option value="video" @selected(($itemData['media_type'] ?? '')==='video')>ویدیو</option></select></div>
  <div class="article-field"><label>عنوان</label><input class="article-input" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][title]" value="{{ $itemData['title'] ?? '' }}"></div>
  <div class="article-field"><label>مسیر مدیا</label><input class="article-input" dir="ltr" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][media_path]" value="{{ $itemData['media_path'] ?? '' }}"></div>
  <div class="article-field"><label>متن جایگزین</label><input class="article-input" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][alt_text]" value="{{ $itemData['alt_text'] ?? '' }}"></div>
  <div class="article-field"><label>&nbsp;</label><button class="article-btn article-btn--danger article-btn--small" type="button" data-remove-manual-item data-item-id="{{ $itemData['id'] ?? '' }}">حذف</button></div>
  <div class="article-field" style="grid-column:1/4;"><label>توضیح و جزئیات مدیا</label><input class="article-input" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][description]" value="{{ $itemData['description'] ?? '' }}" placeholder="توضیحی که زیر تصویر یا ویدیو نمایش داده می‌شود"></div>
  <div class="article-field" style="grid-column:4/6;"><label>لینک مقصد</label><input class="article-input" dir="ltr" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][link_url]" value="{{ $itemData['link_url'] ?? '' }}" placeholder="/app/product/... یا https://..."></div>
  <input type="hidden" name="galleries[{{ $galleryIndex }}][items][{{ $itemIndex }}][remove]" value="0" data-remove-item-input>
</div>
