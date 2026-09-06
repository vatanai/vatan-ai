<script>
/* ══════ Card ۱ — نمایش/مخفی‌سازی تنظیمات واترمارک + دقت گوشه ══════ */
function toggleWatermarkSettings() {
  const enabled = document.getElementById('watermark-enabled-input').checked;
  document.getElementById('watermark-settings-wrap').classList.toggle('hidden', !enabled);
  refreshWatermarkPreview();
}
function onWatermarkPosChange() {
  document.querySelectorAll('.wm-pos-card').forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
  const checked = document.querySelector('input[name="watermark_position"]:checked');
  if (checked) checked.closest('.wm-pos-card').classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
  document.getElementById('wm-precise-corner-wrap').classList.toggle('hidden', !checked || checked.value !== 'corner');
  refreshWatermarkPreview();
}
function setPreciseCorner(corner) {
  document.getElementById('new-watermark-corner-precise').value = corner;
  document.querySelectorAll('.corner-precise-btn').forEach(btn => {
    const active = btn.dataset.corner === corner;
    btn.classList.toggle('border-[var(--accent)]', active);
    btn.classList.toggle('bg-[var(--accent)]/8', active);
    btn.classList.toggle('text-[var(--text)]', active);
    btn.classList.toggle('border-[var(--b1)]', !active);
    btn.classList.toggle('bg-[var(--s1)]', !active);
    btn.classList.toggle('text-[var(--text3)]', !active);
  });
  refreshWatermarkPreview();
}

function currentProductImageUrls() {
  var input = document.getElementById('main-images-file');
  if (input?.files?.length) return Array.from(input.files).map(function(file){ return URL.createObjectURL(file); });
  var group = document.querySelector('[data-input="main-images-file"]');
  try { return JSON.parse(group?.dataset?.existing || '[]'); } catch (error) { return []; }
}

function refreshWatermarkPreview() {
  var image = document.getElementById('watermark-live-image');
  var mark = document.getElementById('watermark-live-mark');
  if (!image || !mark) return;
  var urls = currentProductImageUrls();
  if (urls[0]) image.src = urls[0];
  var opacity = Number(document.querySelector('[name="new_watermark_opacity"]')?.value || 70) / 100;
  var size = Number(document.querySelector('[name="new_watermark_size"]')?.value || 30);
  mark.style.opacity = String(opacity);
  mark.style.fontSize = Math.max(8, Math.round(size / 2.5)) + 'px';
  var type = document.querySelector('[name="new_watermark_type"]:checked')?.value || 'logo';
  mark.querySelector('.watermark-live-logo')?.classList.toggle('hidden', type !== 'logo');
  mark.querySelector('.watermark-live-text')?.classList.toggle('hidden', type !== 'text');
  var logo = mark.querySelector('.watermark-live-logo'); if (logo) logo.style.height = Math.max(14, Math.round(size * .7)) + 'px';
  mark.style.top = mark.style.bottom = mark.style.left = mark.style.right = 'auto';
  var position = document.querySelector('[name="watermark_position"]:checked')?.value || 'corner';
  var corner = document.getElementById('new-watermark-corner-precise')?.value || 'tr';
  if (position === 'center') { mark.style.top = '50%'; mark.style.left = '50%'; mark.style.transform = 'translate(-50%,-50%)'; }
  else {
    mark.style.transform = '';
    if (corner.charAt(0) === 't') mark.style.top = '12px'; else mark.style.bottom = '12px';
    if (corner.charAt(1) === 'r') mark.style.right = '12px'; else mark.style.left = '12px';
  }
}

function refreshCardGalleryPreview() {
  var main = document.getElementById('card-gallery-main');
  var thumbs = document.getElementById('card-gallery-thumbs');
  if (!main || !thumbs) return;
  var urls = currentProductImageUrls();
  if (urls[0]) main.src = urls[0];
  var shape = document.querySelector('[name="card_shape"]:checked')?.value || 'portrait';
  var box = document.getElementById('card-gallery-live');
  box.style.aspectRatio = shape === 'landscape' ? '16 / 9' : shape === 'square' ? '1 / 1' : '4 / 5';
  var mode = document.querySelector('[name="display_mode"]:checked')?.value || 'card';
  main.style.objectFit = mode === 'simple' ? 'contain' : 'cover';
  var layout = document.querySelector('[name="gallery_layout"]:checked')?.value || 'grid';
  thumbs.className = layout === 'slider' ? 'flex gap-1.5 mt-2 overflow-x-auto pb-1' : layout === 'masonry' ? 'grid grid-cols-3 gap-1.5 mt-2 items-start' : 'grid grid-cols-5 gap-1.5 mt-2';
  thumbs.innerHTML = '';
  var gallery = urls.length ? urls : [''];
  while (gallery.length < 5) gallery.push(gallery[gallery.length % Math.max(1, urls.length)] || '');
  gallery.slice(0, 5).forEach(function(url, index) {
    var button = document.createElement('button'); button.type = 'button';
    button.className = 'shrink-0 overflow-hidden rounded-md border transition-colors ' + (index === 0 ? 'border-[var(--accent)]' : 'border-[var(--b1)]') + (layout === 'slider' ? ' w-11 h-11' : ' aspect-square');
    button.innerHTML = url ? '<img class="w-full h-full object-cover" alt="">' : '<span class="block w-full h-full bg-[var(--b1)]"></span>';
    if (url) button.querySelector('img').src = url;
    button.onclick = function(){ if (url) main.src = url; thumbs.querySelectorAll('button').forEach(function(b){b.classList.remove('border-[var(--accent)]');b.classList.add('border-[var(--b1)]');});button.classList.add('border-[var(--accent)]'); };
    thumbs.appendChild(button);
  });
  var enabled = document.querySelector('[name="card_label_enabled"]')?.checked;
  var label = document.getElementById('card-label-live');
  label.textContent = document.querySelector('[name="card_label"]')?.value || 'برچسب';
  label.classList.toggle('hidden', !enabled);
  label.style.top = label.style.bottom = label.style.left = label.style.right = 'auto';
  var pos = document.querySelector('[name="card_label_position"]:checked')?.value || 'top-right';
  if (pos.indexOf('top') === 0) label.style.top = '12px'; else label.style.bottom = '12px';
  if (pos.indexOf('right') > -1) label.style.right = '12px'; else label.style.left = '12px';
}

/* ══════ Card ۲/۳ — رادیوکارت‌های Pricing/Display/Shape/Gallery: هایلایت کارت انتخاب‌شده ══════ */
function wireCardRadioGroup(selector) {
  document.querySelectorAll(selector + ' input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll(selector).forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
      if (radio.checked) radio.closest(selector.replace(' input','')).classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
      if (typeof refreshFinalSummary === 'function') refreshFinalSummary();
    });
  });
}
document.querySelectorAll('.pricing-card, .preview-card-option, .shape-card-option').forEach(card => {
  const radio = card.querySelector('input[type="radio"]');
  if (!radio) return;
  radio.addEventListener('change', () => {
    document.querySelectorAll('.' + card.className.split(' ')[0]).forEach(c => c.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
    if (radio.checked) card.classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
    refreshFinalSummary();
    refreshCardGalleryPreview();
  });
});

/* توجه: تابع refreshFinalSummary و کارت «خلاصه نهایی» به step-5.blade.php منتقل شدند (گام پنجم).
   توابع بالا (toggleWatermarkSettings/onWatermarkPosChange) و رادیوکارت‌های این صفحه در صورت وجود
   refreshFinalSummary در Scope سراسری آن را صدا می‌زنند تا خلاصه گام پنجم زنده به‌روزرسانی شود. */
document.addEventListener('DOMContentLoaded', () => {
  toggleWatermarkSettings();
  onWatermarkPosChange();
  refreshWatermarkPreview();
  refreshCardGalleryPreview();
  document.querySelectorAll('[name="new_watermark_opacity"],[name="new_watermark_size"],[name="new_watermark_type"]').forEach(function(input){ input.addEventListener('input', refreshWatermarkPreview); input.addEventListener('change', refreshWatermarkPreview); });
  document.addEventListener('product-images-changed', function(){ refreshWatermarkPreview(); refreshCardGalleryPreview(); });
  document.getElementById('main-images-file')?.addEventListener('change', function(){ setTimeout(function(){ refreshWatermarkPreview(); refreshCardGalleryPreview(); }, 0); });
});
</script>
