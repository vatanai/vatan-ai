{{-- قالب «اسلاید اختصاصی» تازه؛ __KEY__ در مرورگر با شناسه‌ی یکتا جایگزین می‌شود. --}}
@php
  $slideCredits = (array) ($slideDefaults['credits'] ?? []);
  $slideRatios = (array) ($slideDefaults['allowed_aspect_ratios'] ?? $aspectRatios);
  $slideDefaultRatio = (string) ($slideDefaults['aspect_ratio_default'] ?? ($aspectRatios[0] ?? '4:5'));
  $slideSelectable = (bool) ($slideDefaults['aspect_ratio_user_selectable'] ?? true);
@endphp
<article class="ps-shot-editor is-custom is-new" data-shot-row data-new-slide data-shot-id="new-__KEY__" data-shot-name="اسلاید اختصاصی" data-shot-original-name="اسلاید اختصاصی">
  <input type="hidden" name="new_slides[__KEY__][removed]" value="0" data-shot-removed>
  <div class="ps-shot-card-main">
    <div class="ps-shot-summary">
      <div class="ps-slide-sample" title="تصویر نمونه‌ی این اسلاید">
        <span class="ps-thumb" data-shot-thumb><i class="fa-solid fa-plus"></i></span>
        <small><i class="fa-solid fa-camera"></i> تصویر نمونه</small>
      </div>
      <div class="min-w-0 flex-1">
        <div class="ps-slide-title-row">
          <button type="button" class="ps-slide-drag" data-shot-drag-handle aria-label="جابه‌جایی اسلاید" title="برای جابه‌جایی بکشید"><i class="fa-solid fa-grip-vertical"></i></button>
          <span class="ps-slide-order">اسلاید جدید</span><span class="ps-slide-kind"><i class="fa-solid fa-pen-nib"></i> اختصاصی · ذخیره‌نشده</span>
          <label class="ps-slide-name-field"><span>نام نمایشی اسلاید</span><input type="text" name="new_slides[__KEY__][name]" value="" maxlength="120" class="input-pro" data-shot-name-input placeholder="مثلاً روی سنگ مرمر با نور صبح" required></label>
        </div>
        <div class="ps-hint" data-shot-result>بعد از ذخیره‌ی محصول، پیش‌نمایش واقعی این اسلاید هم فعال می‌شود.</div>
      </div>
      <div class="ps-shot-head-actions">
        <div class="ps-slide-order-actions" aria-label="کنترل ترتیب اسلاید"><button type="button" data-shot-move="up" title="انتقال به بالا" aria-label="انتقال اسلاید به بالا"><i class="fa-solid fa-arrow-up"></i></button><button type="button" data-shot-move="down" title="انتقال به پایین" aria-label="انتقال اسلاید به پایین"><i class="fa-solid fa-arrow-down"></i></button><button type="button" class="is-danger" data-shot-remove title="حذف این اسلاید" aria-label="حذف این اسلاید"><i class="fa-solid fa-trash-can"></i></button></div>
        <label class="ps-check" data-shot-enabled-label><input type="hidden" name="new_slides[__KEY__][enabled]" value="0"><input type="checkbox" name="new_slides[__KEY__][enabled]" value="1" data-shot-enabled checked> فعال برای کاربر</label>
      </div>
    </div>
    <div class="ps-slide-image-optimizer">
      @include('admin.partials.image-optimizer-group', [
        'inputId' => 'new-slide-sample-__KEY__',
        'inputName' => 'new_slides[__KEY__][sample]',
        'title' => 'تصویر نمونه‌ی اسلاید',
        'hint' => 'خروجی‌ای که کاربر از این اسلاید می‌گیرد؛ نسخه‌ی بهینه‌شده‌ی WebP ذخیره می‌شود.',
        'paths' => [],
        'required' => false,
        'multiple' => false,
        'variant' => 'product-shot',
        'uploadLabel' => 'انتخاب تصویر اسلاید',
      ])
    </div>
    <div class="ps-slide-prompt"><div class="ps-field"><label>پرامپت ساخت این اسلاید<span class="ps-char-count" data-char-count></span></label><textarea name="new_slides[__KEY__][prompt]" class="input-pro" dir="ltr" rows="6" maxlength="30000" data-shot-prompt placeholder="پرامپت کامل ساخت این اسلاید" required></textarea></div><button type="button" class="btn-pro btn-pro-ghost" data-shot-toggle><i class="fa-solid fa-sliders"></i> تنظیمات پیشرفته</button></div>
    <div class="ps-shot-detail" data-shot-detail hidden>
      <input type="hidden" name="new_slides[__KEY__][sort]" value="999">
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
        @foreach($qualityLevels as $qualityKey => $qualityLabel)
          <div class="ps-quality-card"><div class="ps-quality-card-title"><span>{{ $qualityLabel }}</span></div><div class="ps-field"><label>اعتبار هر خروجی</label><input type="number" min="0" max="1000" name="new_slides[__KEY__][quality_credits][{{ $qualityKey }}]" class="input-pro" value="{{ (int) ($slideCredits[$qualityKey] ?? $slideCredits['standard'] ?? 12) }}"></div></div>
        @endforeach
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3"><div><div class="text-xs font-bold text-[var(--text-main)] mb-2">نسبت‌های مجاز</div><div class="flex flex-wrap gap-2">@foreach($aspectRatios as $ratio)<label class="ps-check"><input type="checkbox" name="new_slides[__KEY__][allowed_aspect_ratios][]" value="{{ $ratio }}" @checked(in_array($ratio, $slideRatios, true))> {{ $ratio }}</label>@endforeach</div><label class="ps-check mt-3"><input type="hidden" name="new_slides[__KEY__][aspect_ratio_user_selectable]" value="0"><input type="checkbox" name="new_slides[__KEY__][aspect_ratio_user_selectable]" value="1" @checked($slideSelectable)> کاربر اجازه تغییر نسبت را دارد</label></div><div class="ps-field"><label>نسبت پیش‌فرض</label><select name="new_slides[__KEY__][aspect_ratio_default]" class="input-pro">@foreach($aspectRatios as $ratio)<option value="{{ $ratio }}" @selected($slideDefaultRatio === $ratio)>{{ $ratio }}</option>@endforeach</select></div></div>
      <div class="ps-hint mt-2">مدل اختصاصی و پیش‌نمایش واقعی بعد از اولین ذخیره در همین اسلاید در دسترس است.</div>
    </div>
  </div>
</article>
