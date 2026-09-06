{{-- گام پنجم: بازبینی نهایی و پیش‌نمایش زنده صفحه محصول --}}
@php
  $__help = function (string $key, string $title) {
      $text = config('product_field_help.' . $key, '');
      if ($text === '') return '';
      return '<span class="field-help-btn inline-flex items-center justify-center shrink-0 cursor-pointer text-[var(--text3)] hover:text-[var(--accent)] transition-colors" role="button" tabindex="0" data-help-title="' . e($title) . '" data-help-text="' . e($text) . '" aria-label="راهنمایی آیتم"><i class="fa-solid fa-circle-question text-[10px]"></i></span>';
  };
  $previewProduct = $product ?? $duplicateFrom ?? null;
  $previewPaths = $previewProduct
      ? array_values(array_filter(array_merge([(string) $previewProduct->cover], (array) $previewProduct->sample_outputs)))
      : [];
  $previewUrls = array_map(fn ($path) => asset('storage/' . ltrim($path, '/')), $previewPaths);
  $initialCover = $previewUrls[0] ?? '';
  $previewUrl = ($previewProduct && $previewProduct->exists)
      ? route('app.product', $previewProduct->route_slug) . '?admin_preview=1'
      : route('app.product-details') . '?admin_preview=1';
  $curTiles = old('explore_tiles', optional($previewProduct)->explore_tiles ?? ['1x1','2x2','1x2','2x1']);
  if (!is_array($curTiles) || !$curTiles) $curTiles = ['1x1','2x2','1x2','2x1'];
  $tileDefs = [
    '1x1' => ['۱ × ۱ (مربع)', '1 / 1'], '2x2' => ['۲ × ۲ (بزرگ)', '1 / 1'],
    '1x2' => ['۱ × ۲ (عمودی)', '1 / 2'], '2x1' => ['۲ × ۱ (افقی)', '2 / 1'],
  ];
@endphp

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-table-cells text-[var(--accent)]"></i> نحوه نمایش در هوم و اکسپلور {!! $__help('explore_tiles', 'نحوه نمایش در هوم و اکسپلور') !!}</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">قاب‌هایی را انتخاب کنید که این محصول اجازه نمایش در آن‌ها را دارد.</div>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="explore-tiles-grid">
    @foreach($tileDefs as $mode => $def)
      <label class="explore-tile-card relative flex flex-col gap-2 p-2.5 bg-[var(--s1)] border rounded-xl cursor-pointer transition-all {{ in_array($mode, $curTiles) ? 'border-[var(--accent)]' : 'border-[var(--b1)]' }}">
        <div class="mx-auto rounded-lg overflow-hidden bg-[var(--bg)] border border-[var(--b1)]" style="height:7rem;aspect-ratio:{{ $def[1] }};max-width:100%">
          <img class="explore-tile-cover w-full h-full object-cover {{ $initialCover ? '' : 'invisible' }}" src="{{ $initialCover }}" alt="پیش‌نمایش محصول">
        </div>
        <div class="flex items-center justify-between gap-1">
          <span class="text-[11px] font-normal text-[var(--text2)]">{{ $def[0] }}</span>
          <span class="relative w-8 h-[18px] shrink-0 block">
            <input type="checkbox" name="explore_tiles[]" value="{{ $mode }}" class="sr-only peer explore-tile-checkbox" {{ in_array($mode, $curTiles) ? 'checked' : '' }} onchange="onExploreTileToggle(this)">
            <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
          </span>
        </div>
      </label>
    @endforeach
  </div>
  <div id="explore-tiles-warn" class="hidden text-[10.5px] text-[var(--red)] mt-2"><i class="fa-solid fa-triangle-exclamation"></i> حداقل یک حالت نمایش باید روشن بماند.</div>
</div>

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between gap-2">
    <div><div class="text-xs font-bold text-[var(--text)]"><i class="fa-solid fa-clipboard-check text-[var(--accent)] ml-2"></i>خلاصه نهایی</div><div class="text-[10.5px] text-[var(--text3)] mt-1">پیش از ثبت، اطلاعات محصول را مرور کنید</div></div>
    <span id="summary-status-badge" class="text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--orange)]/15 text-[var(--orange)] border border-[var(--orange)]/30">Incomplete</span>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5" id="final-summary-grid">
    @foreach(['sum-name'=>'نام محصول','sum-category'=>'دسته','sum-model'=>'مدل AI','sum-price'=>'قیمت','sum-media'=>'نوع خروجی','sum-status'=>'وضعیت'] as $id => $label)
      <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5"><span class="text-[11px] text-[var(--text3)]">{{ $label }}</span><span class="text-xs text-[var(--text)] font-semibold" id="{{ $id }}">—</span></div>
    @endforeach
  </div>
  <button type="button" class="inline-flex items-center gap-2 px-4 h-9 rounded-lg text-xs font-bold bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] mt-4" onclick="document.getElementById('product-live-preview')?.scrollIntoView({behavior:'smooth'})"><i class="fa-solid fa-eye"></i> پیش‌نمایش محصول</button>
</div>

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mt-5">
  <div class="mb-3 text-xs font-bold text-[var(--text)]"><i class="fa-solid fa-barcode text-[var(--accent)] ml-2"></i>کد محصول</div>
  <div class="flex items-center gap-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-3"><span class="text-lg font-bold font-mono tracking-widest text-[var(--accent)]" id="product-code-display" dir="ltr">--------</span><span class="text-[10.5px] text-[var(--text3)]">خودکار ساخته می‌شود</span></div>
</div>

<div id="product-live-preview" class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mt-5">
  <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
    <div><div class="text-xs font-bold text-[var(--text)]"><i class="fa-solid fa-window-maximize text-[var(--accent)] ml-2"></i>صفحه واقعی محصول</div><div class="text-[10.5px] text-[var(--text3)] mt-1">این قاب مستقیماً همان آدرس و همان کد صفحه محصول سایت را نمایش می‌دهد.</div></div>
    <button type="button" onclick="openProductPreviewInNewTab()" class="inline-flex items-center gap-2 h-8 px-3 rounded-lg border border-[var(--b1)] text-[11px] text-[var(--text2)] hover:text-[var(--text)]"><i class="fa-solid fa-arrow-up-right-from-square"></i>باز کردن در صفحه جدید</button>
  </div>
  <div class="rounded-2xl border border-[var(--b1)] overflow-hidden bg-[var(--bg)]">
    <iframe id="real-product-preview-frame" src="{{ $previewUrl }}" title="پیش‌نمایش صفحه واقعی محصول" class="block w-full h-[780px] border-0 bg-[var(--bg)]" loading="lazy" onload="lockRealProductPreview(this)"></iframe>
  </div>
</div>

<script>
function onExploreTileToggle(cb) {
  var checks = Array.from(document.querySelectorAll('.explore-tile-checkbox'));
  if (!checks.some(function(c){ return c.checked; })) { cb.checked = true; var warn = document.getElementById('explore-tiles-warn'); warn?.classList.remove('hidden'); setTimeout(function(){ warn?.classList.add('hidden'); }, 2500); }
  var card = cb.closest('.explore-tile-card'); card?.classList.toggle('border-[var(--accent)]', cb.checked); card?.classList.toggle('border-[var(--b1)]', !cb.checked);
}
function applyExploreCover(src) { document.querySelectorAll('.explore-tile-cover').forEach(function(img){ img.src = src; img.classList.remove('invisible'); }); }
function updateExploreTileCovers() {
  var input = document.getElementById('main-images-file');
  if (input?.files?.[0]) applyExploreCover(URL.createObjectURL(input.files[0]));
}
function refreshFinalSummary() {
  var name = document.querySelector('[name="name_fa"]')?.value.trim();
  var model = document.getElementById('primary-model-select');
  var pricing = document.querySelector('[name="pricing_model"]:checked');
  var media = document.querySelector('[name="media_type"]:checked');
  var values = {
    'sum-name': name, 'sum-category': typeof getSelectedCategoryNames === 'function' ? getSelectedCategoryNames() : '',
    'sum-model': model?.value ? model.options[model.selectedIndex].textContent : '',
    'sum-price': pricing ? ({free:'رایگان',per_credit:'کردیتی',subscription:'اشتراکی'}[pricing.value]) : '',
    'sum-media': media ? ({photo:'عکس',video:'ویدیو',both:'هر دو'}[media.value]) : '',
    'sum-status': document.getElementById('product-status')?.value === 'active' ? 'ثبت نهایی' : 'پیش‌نویس'
  };
  var complete = true;
  Object.keys(values).forEach(function(id){ var el = document.getElementById(id); if (!el) return; if (values[id]) el.textContent = values[id]; else { el.textContent = 'تکمیل‌نشده'; complete = false; } });
  var badge = document.getElementById('summary-status-badge'); if (badge) { badge.textContent = complete ? 'Ready' : 'Incomplete'; badge.className = 'text-[10.5px] font-bold rounded-full px-2.5 py-1 ' + (complete ? 'bg-[var(--green)]/15 text-[var(--green)] border border-[var(--green)]/30' : 'bg-[var(--orange)]/15 text-[var(--orange)] border border-[var(--orange)]/30'); }
}
function productPreviewPlaceholder(label) {
  return '<span class="admin-preview-missing"><i class="fa-solid fa-circle-exclamation"></i>' + label + ' هنوز تکمیل نشده</span>';
}
function productPreviewFiles(inputId, existingSelector) {
  var input = document.getElementById(inputId);
  if (input?.files?.length) return Array.from(input.files).map(function(file){ return URL.createObjectURL(file); });
  var group = document.querySelector('[data-input="' + inputId + '"]');
  try { return JSON.parse(group?.dataset?.existing || '[]'); } catch (error) { return []; }
}
function refreshProductPreview() {
  var frame = document.getElementById('real-product-preview-frame');
  var doc;
  try { doc = frame?.contentDocument; } catch (error) { return; }
  if (!doc?.querySelector('.pd-shell')) return;

  if (!doc.getElementById('admin-live-preview-style')) {
    var style = doc.createElement('style');
    style.id = 'admin-live-preview-style';
    style.textContent = '.admin-preview-missing{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px dashed var(--border-subtle);border-radius:9px;color:var(--text-secondary);background:var(--bg-card);font-size:11px;font-weight:700}.admin-preview-missing i{color:var(--green)}.admin-preview-image-missing{width:min(78%,520px);min-height:220px;display:flex;align-items:center;justify-content:center;padding:24px;text-align:center}.pd-gal-grid img{cursor:pointer}.pd-gal-grid img:hover{outline:2px solid var(--green)}';
    doc.head.appendChild(style);
  }

  var value = function(name) { return document.querySelector('[name="' + name + '"]')?.value?.trim() || ''; };
  var name = value('name_fa');
  var description = value('description_fa') || value('description_en');
  var categories = typeof selectedCategories !== 'undefined' ? selectedCategories.map(function(cat){ return cat.name; }) : [];
  var tags = Array.from(document.querySelectorAll('#tags-wrap > [data-tag-chip]')).map(function(chip){ return chip.textContent.replace('×', '').trim(); }).filter(Boolean);
  var pricing = document.querySelector('[name="pricing_model"]:checked')?.value || '';
  var cost = value('credit_cost');
  var mainImages = productPreviewFiles('main-images-file');
  var beforeImages = productPreviewFiles('before-images-file');
  var info = doc.querySelector('.pd-info-scroll');

  var title = doc.querySelector('.pd-title');
  if (title) title.innerHTML = name ? '' : productPreviewPlaceholder('نام محصول');
  if (title && name) title.textContent = name;

  var meta = doc.querySelector('.pd-meta');
  if (!meta && info) { meta = doc.createElement('div'); meta.className = 'pd-meta admin-preview-only'; title?.after(meta); }
  if (meta) meta.innerHTML = '<div class="pd-cats">' + (categories.length ? categories.map(function(cat){ return '<span class="pd-cat"></span>'; }).join('') : productPreviewPlaceholder('دسته‌بندی')) + '</div><div class="pd-tags">' + (tags.length ? tags.map(function(tag){ return '<span class="pd-tag"></span>'; }).join('') : productPreviewPlaceholder('تگ‌های محصول')) + '</div>';
  meta?.querySelectorAll('.pd-cat').forEach(function(el, index){ el.textContent = categories[index]; });
  meta?.querySelectorAll('.pd-tag').forEach(function(el, index){ el.textContent = '# ' + tags[index]; });

  var descBox = doc.querySelector('.pd-desc-box');
  if (!descBox && info) { descBox = doc.createElement('div'); descBox.className = 'pd-desc-box admin-preview-only'; var actions = info.querySelector('.pd-actions'); info.insertBefore(descBox, actions); }
  if (descBox) descBox.innerHTML = '<h2>توضیحات محصول</h2><p class="pd-desc-text"></p>';
  var descText = descBox?.querySelector('.pd-desc-text');
  if (descText) { if (description) descText.textContent = description; else descText.innerHTML = productPreviewPlaceholder('توضیحات محصول'); }

  var token = doc.querySelector('#pdTokenBtn b');
  if (token) {
    if (!pricing) token.innerHTML = productPreviewPlaceholder('قیمت محصول');
    else token.textContent = pricing === 'per_credit' ? (cost ? cost + ' توکن' : 'هزینه توکن هنوز تکمیل نشده') : (pricing === 'free' ? 'رایگان' : 'اشتراکی');
  }

  var galleries = info ? Array.from(info.querySelectorAll('.pd-gal')) : [];
  galleries.forEach(function(gallery){ gallery.remove(); });
  function appendGallery(titleText, images, missingLabel) {
    if (!info) return;
    var gallery = doc.createElement('div'); gallery.className = 'pd-gal admin-preview-only';
    gallery.innerHTML = '<h2></h2><div class="pd-gal-grid"></div>'; gallery.querySelector('h2').textContent = titleText;
    var grid = gallery.querySelector('.pd-gal-grid');
    if (images.length) images.forEach(function(src){ var img = doc.createElement('img'); img.src = src; img.dataset.full = src; img.alt = name || 'پیش‌نمایش محصول'; img.addEventListener('click', function(){ var large = doc.getElementById('pdpMainImage'); if (large) { large.hidden = false; large.src = src; large.scrollIntoView({behavior:'smooth',block:'center'}); } }); grid.appendChild(img); });
    else grid.innerHTML = productPreviewPlaceholder(missingLabel);
    info.appendChild(gallery);
  }
  appendGallery('تصاویر محصول', mainImages, 'تصاویر محصول');
  appendGallery('عکس‌های قبل', beforeImages, 'عکس‌های قبل');

  var main = doc.getElementById('pdpMainImage');
  if (mainImages.length && main) { main.hidden = false; main.src = mainImages[0]; main.alt = name || 'پیش‌نمایش محصول'; doc.querySelector('.admin-preview-image-missing')?.remove(); }
  else if (main) {
    main.hidden = true;
    var mainWrap = main.parentElement;
    var missing = mainWrap?.querySelector('.admin-preview-image-missing');
    if (!missing && mainWrap) { missing = doc.createElement('div'); missing.className = 'admin-preview-missing admin-preview-image-missing'; mainWrap.appendChild(missing); }
    if (missing) missing.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i>تصویر اصلی محصول هنوز تکمیل نشده';
  }

  var mainWrap = main?.parentElement;
  var watermark = mainWrap?.querySelector('.product-watermark');
  var watermarkEnabled = document.getElementById('watermark-enabled-input')?.checked
    && document.querySelector('[name="watermark_position"]:checked')?.value !== 'none';
  if (watermarkEnabled && mainWrap) {
    if (!watermark) {
      watermark = doc.createElement('span');
      watermark.className = 'product-watermark';
      watermark.style.cssText = 'position:absolute;z-index:5;display:inline-flex;align-items:center;justify-content:center;pointer-events:none;';
      mainWrap.appendChild(watermark);
    }
    var watermarkType = document.querySelector('[name="new_watermark_type"]:checked')?.value || 'logo';
    watermark.innerHTML = watermarkType === 'text'
      ? '<strong>VATAN AI</strong>'
      : '<img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="Vatan AI" style="width:100%;height:auto;object-fit:contain;">';
    var opacity = Math.max(0, Math.min(100, Number(document.querySelector('[name="new_watermark_opacity"]')?.value || 70)));
    var size = Math.max(10, Math.min(100, Number(document.querySelector('[name="new_watermark_size"]')?.value || 30)));
    watermark.style.opacity = String(opacity / 100);
    watermark.style.setProperty('width', size + '%', 'important');
    watermark.style.top = watermark.style.bottom = watermark.style.left = watermark.style.right = 'auto';
    watermark.style.transform = '';
    var wmPosition = document.querySelector('[name="watermark_position"]:checked')?.value || 'corner';
    var wmCorner = document.getElementById('new-watermark-corner-precise')?.value || 'tr';
    if (wmPosition === 'center') {
      watermark.style.top = '50%'; watermark.style.left = '50%'; watermark.style.transform = 'translate(-50%,-50%)';
    } else {
      watermark.style[wmCorner.charAt(0) === 't' ? 'top' : 'bottom'] = '3%';
      watermark.style[wmCorner.charAt(1) === 'r' ? 'right' : 'left'] = '3%';
    }
    var text = watermark.querySelector('strong');
    if (text) {
      text.style.color = document.querySelector('[name="new_watermark_text_color"]')?.value || '#FFFFFF';
      text.style.fontSize = 'clamp(11px,2vw,28px)';
      text.style.textShadow = '0 1px 5px rgba(0,0,0,.45)';
    }
  } else {
    watermark?.remove();
  }

  doc.querySelector('.pd-similar')?.remove();
}
function lockRealProductPreview(frame) {
  try {
    var doc = frame.contentDocument;
    if (!doc) return;
    doc.querySelectorAll('#btnBookmark,#btnShare,#btnLike,#pdCloseBtn,.pd-build-btn,.vatan-gen-btn,button[type="submit"]').forEach(function(button){
      button.disabled = true; button.style.pointerEvents = 'none'; button.setAttribute('aria-disabled', 'true');
    });
    doc.querySelectorAll('a').forEach(function(link){ link.addEventListener('click', function(event){ event.preventDefault(); }); });
  } catch (error) {}
  refreshProductPreview();
}
function openProductPreviewInNewTab() {
  refreshProductPreview();
  var frame = document.getElementById('real-product-preview-frame');
  try {
    var previewWindow = window.open('', '_blank');
    if (!previewWindow || !frame?.contentDocument) return;
    previewWindow.document.open();
    previewWindow.document.write('<!doctype html>' + frame.contentDocument.documentElement.outerHTML);
    previewWindow.document.close();
  } catch (error) { if (typeof showGlobalError === 'function') showGlobalError('باز کردن پیش‌نمایش در صفحه جدید ممکن نشد.'); }
}
function generateProductCode() { var el = document.getElementById('product-code-display'); if (!el || el.textContent.trim() !== '--------') return; el.textContent = Array.from({length:8}, function(){ return Math.floor(Math.random()*10); }).join(''); }
document.addEventListener('DOMContentLoaded', function(){
  generateProductCode(); refreshFinalSummary(); updateExploreTileCovers();
  ['name_fa','description_fa','credit_cost'].forEach(function(name){ document.querySelector('[name="'+name+'"]')?.addEventListener('input', refreshFinalSummary); });
  document.getElementById('primary-model-select')?.addEventListener('change', refreshFinalSummary);
  document.querySelectorAll('[name="media_type"],[name="pricing_model"]').forEach(function(el){ el.addEventListener('change', refreshFinalSummary); });
  document.getElementById('main-images-file')?.addEventListener('change', updateExploreTileCovers);
  document.getElementById('main-images-file')?.addEventListener('change', refreshProductPreview);
  document.getElementById('before-images-file')?.addEventListener('change', refreshProductPreview);
  document.getElementById('real-product-form')?.addEventListener('input', refreshProductPreview);
  document.getElementById('real-product-form')?.addEventListener('change', refreshProductPreview);
  ['tags-wrap','cat-tags-wrap'].forEach(function(id){
    var target = document.getElementById(id);
    if (target) new MutationObserver(refreshProductPreview).observe(target, {childList:true});
  });
});
</script>
