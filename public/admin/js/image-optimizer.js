/* ══════════════════════════════════════════════════════════════════════
   image-optimizer.js — آپلودر مشترک «عکس محصول» در پنل مدیریت
   ----------------------------------------------------------------------
   این کد قبلاً فقط داخل products-create.js (ثبت محصول چهره) بود؛ حالا
   مشترک است تا ثبت محصول پروداکتی هم دقیقاً همان تجربه را داشته باشد:
   انتخاب چندتایی، بهینه‌سازی خودکار، مقایسه قبل/بعد، انتخاب حجم،
   شارپ‌کردن، حذف و جابه‌جایی برای تعیین کاور.
   هر صفحه‌ای که .image-optimizer-group دارد، خودکار راه‌اندازی می‌شود.
   ══════════════════════════════════════════════════════════════════════ */
function imageOptToFa(v) { return String(v).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }

/* ══════════════════ بهینه‌سازی ساده تصاویر محصول ══════════════════
   - فایل‌های جدید برای کم‌شدن حجم آپلود مرورگر پردازش می‌شوند.
   - فایل بزرگ بدون crop و با حفظ نسبت، حداکثر تا ضلع ۱۶۰۰ کوچک می‌شود.
   - Canvas مرورگر در فضای رنگی sRGB خروجی WebP با کیفیت بصری بالا می‌دهد.
   - بک‌اند همین قواعد را به‌صورت قطعی دوباره اعمال می‌کند. */
const IMAGE_OPT_MAX_EDGE = 1600;
const IMAGE_OPT_MAX_BYTES = 450 * 1024;
const originalImageFiles = new WeakMap();
const optimizedImageFiles = new WeakMap();
// فایل‌های صف‌شده باید بین چند انتخاب جداگانه‌ی کاربر حفظ شوند؛ انتخاب جدید
// نباید FileList قبلی را که مرورگر روی input گذاشته است جایگزین کند.
const selectedUploadFiles = new WeakMap();
const selectedImageIndexes = new WeakMap();
const imageOptimizationApproved = new WeakMap();
const selectedImageProfiles = new WeakMap();

function uploadFileKey(file) {
  return [file.name, file.size, file.lastModified, file.type].join(':');
}

function mergeUploadFiles(previous, added) {
  const files = [];
  const keys = new Set();
  [...previous, ...added].forEach(function (file) {
    const key = uploadFileKey(file);
    if (keys.has(key)) return;
    keys.add(key);
    files.push(file);
  });
  return files;
}

function setImageOptimizeState(group, state, message) {
  group.dataset.optimizeState = state;
  const button = group.querySelector('.image-optimize-btn');
  const icon = button && button.querySelector('i');
  const label = button && button.querySelector('span');
  const status = group.querySelector('.image-optimize-status');
  const loading = group.querySelector('.image-result-loading');
  const resultIcon = group.querySelector('.image-result-icon');
  const reoptimizeButton = group.querySelector('.image-reoptimize-btn');
  const reoptimizeIcon = reoptimizeButton?.querySelector('i');
  if (button) button.disabled = state === 'processing';
  if (icon) icon.className = state === 'processing' ? 'fa-solid fa-spinner fa-spin'
    : state === 'done' ? 'fa-solid fa-circle-check text-[var(--green)]'
    : state === 'failed' ? 'fa-solid fa-rotate-right text-[var(--red)]'
    : 'fa-solid fa-wand-magic-sparkles';
  if (label) label.textContent = state === 'processing' ? 'در حال بهینه‌سازی…'
    : state === 'done' ? 'بررسی مجدد'
    : state === 'failed' ? 'تلاش مجدد'
    : 'بهینه‌سازی اتوماتیک';
  if (status) {
    status.textContent = message || '';
    status.classList.toggle('text-[var(--green)]', state === 'done');
    status.classList.toggle('text-[var(--red)]', state === 'failed');
  }
  if (loading) {
    loading.classList.toggle('hidden', state !== 'processing');
    loading.classList.toggle('flex', state === 'processing');
  }
  if (resultIcon) resultIcon.className = state === 'done'
    ? 'image-result-icon fa-solid fa-circle-check text-[var(--green)]'
    : state === 'failed'
      ? 'image-result-icon fa-solid fa-triangle-exclamation text-[var(--red)]'
      : 'image-result-icon fa-solid fa-hourglass-half text-[var(--text3)]';
  if (reoptimizeButton) {
    reoptimizeButton.disabled = state === 'processing';
    reoptimizeButton.classList.toggle('border-[var(--green)]', state === 'done');
    reoptimizeButton.classList.toggle('text-[var(--green)]', state === 'done');
  }
  if (reoptimizeIcon) reoptimizeIcon.className = state === 'processing'
    ? 'fa-solid fa-spinner fa-spin'
    : state === 'done' ? 'fa-solid fa-circle-check text-[var(--green)]' : 'fa-solid fa-rotate';
}

function imageFileList(files) {
  const transfer = new DataTransfer();
  files.forEach(file => transfer.items.add(file));
  return transfer.files;
}

function loadImageFile(file) {
  return new Promise(function (resolve, reject) {
    const url = URL.createObjectURL(file);
    const image = new Image();
    image.onload = function () { URL.revokeObjectURL(url); resolve(image); };
    image.onerror = function () { URL.revokeObjectURL(url); reject(new Error('تصویر خوانده نشد')); };
    image.src = url;
  });
}

async function optimizeOneImage(file, settings) {
  const image = await loadImageFile(file);
  const maxEdge = settings?.maxEdge || IMAGE_OPT_MAX_EDGE;
  const quality = settings?.quality || 0.9;
  if (!settings && Math.max(image.naturalWidth, image.naturalHeight) <= IMAGE_OPT_MAX_EDGE && file.size <= IMAGE_OPT_MAX_BYTES) return file;
  const scale = Math.min(1, maxEdge / Math.max(image.naturalWidth, image.naturalHeight));
  const width = Math.max(1, Math.round(image.naturalWidth * scale));
  const height = Math.max(1, Math.round(image.naturalHeight * scale));
  let canvas;
  try { canvas = new OffscreenCanvas(width, height); }
  catch (e) { canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height; }
  const context = canvas.getContext('2d', { alpha: true, colorSpace: 'srgb' });
  context.imageSmoothingEnabled = true;
  context.imageSmoothingQuality = 'high';
  context.drawImage(image, 0, 0, width, height);
  const blob = canvas.convertToBlob
    ? await canvas.convertToBlob({ type: 'image/webp', quality: quality })
    : await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', quality));
  if (!blob) throw new Error('مرورگر نتوانست تصویر را پردازش کند');
  if (scale === 1 && blob.size >= file.size) return file;
  return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp', lastModified: Date.now() });
}

function formatImageBytes(bytes) {
  if (!Number.isFinite(bytes)) return '—';
  if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)).toLocaleString('fa-IR') + ' کیلوبایت';
  return (bytes / (1024 * 1024)).toLocaleString('fa-IR', { maximumFractionDigits: 2 }) + ' مگابایت';
}

function imageFormatLabel(file) {
  return ({ 'image/jpeg': 'JPEG', 'image/png': 'PNG', 'image/webp': 'WebP' })[file?.type] || (file?.type?.split('/').pop() || '—').toUpperCase();
}

async function imageFileMeta(file) {
  if (!file) return null;
  const image = await loadImageFile(file);
  return { width: image.naturalWidth, height: image.naturalHeight, size: file.size, format: imageFormatLabel(file) };
}

function metaMarkup(meta) {
  if (!meta) return '<span>هنوز آماده نیست</span>';
  const gcd = function (a, b) { while (b) { const next = a % b; a = b; b = next; } return a || 1; };
  const divisor = gcd(meta.width, meta.height);
  const ratio = (meta.width / divisor) + ':' + (meta.height / divisor);
  return '<span><i class="fa-solid fa-weight-hanging ml-1"></i>' + formatImageBytes(meta.size) + '</span>' +
    '<span><i class="fa-solid fa-expand ml-1"></i>' + meta.width.toLocaleString('fa-IR') + ' × ' + meta.height.toLocaleString('fa-IR') + '</span>' +
    '<span><i class="fa-solid fa-file-code ml-1"></i>' + meta.format + '</span>' +
    '<span><i class="fa-solid fa-crop-simple ml-1"></i>' + ratio + '</span>';
}

function previewUrl(file) {
  return file ? URL.createObjectURL(file) : '';
}

function setImageApproval(group, count, approved) {
  imageOptimizationApproved.set(group, Array.from({ length: count }, function () { return !!approved; }));
}

function setSelectedImageApproval(group, index, approved) {
  const originals = originalImageFiles.get(group) || [];
  const approvals = (imageOptimizationApproved.get(group) || Array(originals.length).fill(false)).slice();
  approvals[index] = !!approved;
  imageOptimizationApproved.set(group, approvals);
}

function setSelectedImageProfile(group, index, profile) {
  const originals = originalImageFiles.get(group) || [];
  const profiles = (selectedImageProfiles.get(group) || Array(originals.length).fill('')).slice();
  profiles[index] = profile;
  selectedImageProfiles.set(group, profiles);
}

function currentImageProfile(group) {
  const index = selectedImageIndexes.get(group) || 0;
  return (selectedImageProfiles.get(group) || [])[index] || '';
}

function markImageVolumeChoice(group, profile, persist = true) {
  const index = selectedImageIndexes.get(group) || 0;
  if (persist) setSelectedImageProfile(group, index, profile);
  group.querySelectorAll('.image-volume-choice').forEach(function (button) {
    const selected = button.dataset.profile === profile;
    button.classList.toggle('border-[var(--green)]', selected);
    button.classList.toggle('bg-[var(--green)]/10', selected);
    button.classList.toggle('border-[var(--b1)]', !selected);
    const check = button.querySelector('.image-choice-check');
    if (check) check.style.display = selected ? 'inline-block' : 'none';
  });
}

async function renderImageComparison(group) {
  const originals = originalImageFiles.get(group) || [];
  const outputs = optimizedImageFiles.get(group) || [];
  const workspace = group.querySelector('.image-compare-workspace');
  const targetPanel = group.querySelector('.image-target-panel');
  if (!workspace) return;
  workspace.classList.toggle('hidden', originals.length === 0);
  if (!originals.length) { targetPanel?.classList.add('hidden'); return; }
  const index = Math.min(selectedImageIndexes.get(group) || 0, originals.length - 1);
  selectedImageIndexes.set(group, index);
  const original = originals[index];
  const optimized = outputs[index] || null;
  const originalImage = group.querySelector('.image-compare-original');
  const optimizedImage = group.querySelector('.image-compare-optimized');
  if (originalImage) originalImage.src = previewUrl(original);
  if (optimizedImage) {
    optimizedImage.src = previewUrl(optimized || original);
    optimizedImage.classList.toggle('opacity-30', !optimized);
  }
  const [originalMeta, optimizedMeta] = await Promise.all([imageFileMeta(original), imageFileMeta(optimized)]);
  const originalSpecs = group.querySelector('.image-original-specs');
  const optimizedSpecs = group.querySelector('.image-optimized-specs');
  if (originalSpecs) originalSpecs.innerHTML = metaMarkup(originalMeta);
  if (optimizedSpecs) optimizedSpecs.innerHTML = metaMarkup(optimizedMeta);
  const modalOriginal = group.querySelector('.image-modal-original');
  const modalOptimized = group.querySelector('.image-modal-optimized');
  if (modalOriginal) modalOriginal.src = originalImage?.src || '';
  if (modalOptimized) modalOptimized.src = optimizedImage?.src || '';
  const thumbs = group.querySelector('.image-compare-thumbs');
  if (thumbs) {
    thumbs.innerHTML = '';
    const approvals = imageOptimizationApproved.get(group) || [];
    const createThumb = function (file, thumbIndex) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'relative w-12 h-12 rounded-lg overflow-hidden shrink-0 border ' + (thumbIndex === index ? 'border-[var(--accent)]' : 'border-[var(--b1)]');
      button.innerHTML = '<img class="w-full h-full object-cover" alt=""><i class="absolute top-1 left-1 w-4 h-4 rounded-full flex items-center justify-center text-[8px] ' + (approvals[thumbIndex] ? 'fa-solid fa-check bg-[var(--green)] text-white' : 'fa-solid fa-xmark bg-[var(--red)] text-white') + '"></i>';
      button.querySelector('img').src = previewUrl(file);
      button.onclick = async function () { selectedImageIndexes.set(group, thumbIndex); await renderImageComparison(group); renderImageTargetOptions(group); };
      if (isMainImageGroup(group)) {
        button.draggable = true;
        button.title = thumbIndex === 0 ? 'کاور فعلی محصول' : 'برای تغییر ترتیب یا کاور، تصویر را بکشید';
        button.addEventListener('dragstart', function (event) {
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('text/plain', String(thumbIndex));
          button.classList.add('opacity-40');
        });
        button.addEventListener('dragover', function (event) {
          event.preventDefault(); event.dataTransfer.dropEffect = 'move';
          button.classList.add('border-[var(--green)]');
        });
        button.addEventListener('dragleave', function () { button.classList.remove('border-[var(--green)]'); });
        button.addEventListener('dragend', function () { button.classList.remove('opacity-40', 'border-[var(--green)]'); });
        button.addEventListener('drop', function (event) {
          event.preventDefault(); event.stopPropagation();
          button.classList.remove('border-[var(--green)]');
          commitImageOrder(group, Number(event.dataTransfer.getData('text/plain')), thumbIndex);
        });
      }
      return button;
    };

    if (isMainImageGroup(group)) {
      thumbs.className = 'image-compare-thumbs flex items-stretch gap-3 p-3 border-t border-[var(--b1)] bg-[var(--s2)] overflow-hidden';
      const coverSection = document.createElement('div');
      coverSection.className = 'shrink-0 flex flex-col gap-2 pl-3 border-l border-[var(--b1)]';
      coverSection.innerHTML = '<span class="text-[9px] font-semibold text-[var(--green)]"><i class="fa-solid fa-star ml-1"></i>عکس کاور</span>';
      coverSection.appendChild(createThumb(originals[0], 0));

      const gallerySection = document.createElement('div');
      gallerySection.className = 'min-w-0 flex-1 flex flex-col gap-2';
      gallerySection.innerHTML = '<span class="text-[9px] font-semibold text-[var(--text3)]"><i class="fa-solid fa-images ml-1"></i>عکس‌های دیگر محصول <span class="font-normal">— برای جابه‌جایی بکشید</span></span>';
      const galleryStrip = document.createElement('div');
      galleryStrip.className = 'flex gap-2 overflow-x-auto pb-1 min-h-12';
      originals.slice(1).forEach(function (file, galleryIndex) { galleryStrip.appendChild(createThumb(file, galleryIndex + 1)); });
      if (originals.length === 1) galleryStrip.innerHTML = '<span class="text-[9px] text-[var(--text3)] self-center">عکس دیگری اضافه نشده است.</span>';
      gallerySection.appendChild(galleryStrip);
      thumbs.appendChild(coverSection);
      thumbs.appendChild(gallerySection);
    } else {
      thumbs.className = 'image-compare-thumbs flex gap-2 overflow-x-auto p-3 border-t border-[var(--b1)]';
      originals.forEach(function (file, thumbIndex) { thumbs.appendChild(createThumb(file, thumbIndex)); });
    }
  }
  targetPanel?.classList.toggle('hidden', !optimized);
}

async function optimizeOneImageToBytes(file, targetBytes) {
  const image = await loadImageFile(file);
  let scale = Math.min(1, 2000 / Math.max(image.naturalWidth, image.naturalHeight));
  let bestBlob = null;
  for (let resizePass = 0; resizePass < 3; resizePass++) {
    const width = Math.max(1, Math.round(image.naturalWidth * scale));
    const height = Math.max(1, Math.round(image.naturalHeight * scale));
    const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height;
    const context = canvas.getContext('2d', { alpha: true, colorSpace: 'srgb' });
    context.imageSmoothingEnabled = true; context.imageSmoothingQuality = 'high'; context.drawImage(image, 0, 0, width, height);
    let low = 0.42, high = 0.98;
    for (let iteration = 0; iteration < 7; iteration++) {
      const quality = (low + high) / 2;
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', quality));
      if (!blob) throw new Error('پردازش تصویر ناموفق بود');
      if (!bestBlob || Math.abs(blob.size - targetBytes) < Math.abs(bestBlob.size - targetBytes)) bestBlob = blob;
      if (blob.size > targetBytes) high = quality; else low = quality;
    }
    if (bestBlob && bestBlob.size <= targetBytes * 1.12) break;
    scale *= 0.82;
  }
  return new File([bestBlob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp', lastModified: Date.now() });
}

function renderImageTargetOptions(group) {
  const outputs = optimizedImageFiles.get(group) || [];
  const index = selectedImageIndexes.get(group) || 0;
  const reference = outputs[index];
  const panel = group.querySelector('.image-target-panel');
  const options = group.querySelector('.image-target-options');
  if (!reference || !panel || !options) { panel?.classList.add('hidden'); return; }
  panel.classList.remove('hidden');
  const autoSize = group.querySelector('.image-auto-size');
  if (autoSize) autoSize.textContent = formatImageBytes(reference.size);
  const original = (originalImageFiles.get(group) || [])[index];
  group.querySelectorAll('.image-original-size').forEach(function (el) { el.textContent = formatImageBytes(original?.size); });
  const range = group.querySelector('.image-size-range');
  if (range) {
    const selectedKb = Math.max(20, Math.round(reference.size / 1024));
    const span = Math.max(10, Math.min(selectedKb - 10, Math.max(60, Math.round(selectedKb * 0.75))));
    range.min = String(Math.max(10, selectedKb - span));
    range.max = String(selectedKb + span);
    range.value = String(selectedKb);
    previewImageRange(range);
  }
  const levels = [
    [1.75, 'جزئیات بیشتر', 'fa-gem'], [1.50, 'خیلی باکیفیت', 'fa-star'], [1.25, 'کمی سنگین‌تر', 'fa-arrow-trend-up'],
    [0.80, 'کمی سبک‌تر', 'fa-feather'], [0.65, 'سبک', 'fa-compress'], [0.50, 'خیلی سبک', 'fa-bolt'],
  ];
  options.innerHTML = levels.map(function(level) {
    return '<button type="button" data-profile="relative-' + level[0] + '" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] hover:border-[var(--accent)] rounded-xl p-2.5 text-right transition-colors" onclick="applyImageTargetLevel(this,' + level[0] + ')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i>' +
      '<span class="flex items-center gap-1.5 text-[10px] text-[var(--text2)]"><i class="fa-solid ' + level[2] + ' text-[var(--accent)]"></i>' + level[1] + '</span>' +
      '<strong class="block text-[11px] text-[var(--text)] mt-1">حدود ' + formatImageBytes(reference.size * level[0]) + '</strong></button>';
  }).join('');
  markImageVolumeChoice(group, currentImageProfile(group), false);
}

async function applyImageTargetLevel(button, factor) {
  const group = button.closest('.image-optimizer-group');
  const originals = originalImageFiles.get(group) || [];
  const current = optimizedImageFiles.get(group) || [];
  if (!originals.length || !current.length) return;
  setImageOptimizeState(group, 'processing', 'در حال ساخت حجم انتخابی…');
  group.querySelectorAll('.image-target-options button').forEach(function(item){ item.disabled = true; });
  try {
    const index = selectedImageIndexes.get(group) || 0;
    const output = current.slice();
    output[index] = await optimizeOneImageToBytes(originals[index], Math.max(24 * 1024, current[index].size * factor));
    optimizedImageFiles.set(group, output);
    setSelectedImageApproval(group, index, true);
    document.getElementById(group.dataset.input).files = imageFileList(output);
    renderImageGroupPreviews(group, output);
    await renderImageComparison(group); renderImageTargetOptions(group);
    markImageVolumeChoice(group, 'relative-' + factor);
    setImageOptimizeState(group, 'done', 'حجم انتخابی آماده ثبت است.');
  } catch (error) { setImageOptimizeState(group, 'failed', 'ساخت حجم انتخابی انجام نشد؛ دوباره تلاش کنید.'); }
  finally { group.querySelectorAll('.image-target-options button').forEach(function(item){ item.disabled = false; }); }
}

async function applySelectedImageToAbsoluteTarget(group, targetBytes, profile) {
  const originals = originalImageFiles.get(group) || [];
  if (!originals.length) return;
  const index = selectedImageIndexes.get(group) || 0;
  const current = optimizedImageFiles.get(group) || originals.slice();
  setImageOptimizeState(group, 'processing', 'در حال ساخت حجم انتخابی برای عکس انتخاب‌شده…');
  try {
    const output = current.slice();
    output[index] = await optimizeOneImageToBytes(originals[index], targetBytes);
    optimizedImageFiles.set(group, output);
    setSelectedImageApproval(group, index, true);
    document.getElementById(group.dataset.input).files = imageFileList(output);
    renderImageGroupPreviews(group, output);
    await renderImageComparison(group); renderImageTargetOptions(group); markImageVolumeChoice(group, profile);
    setImageOptimizeState(group, 'done', 'حجم انتخابی برای همین عکس آماده ثبت است.');
  } catch (error) { setImageOptimizeState(group, 'failed', 'پردازش حجم انتخابی انجام نشد؛ دوباره تلاش کنید.'); }
}

async function applyImageQuickPreset(button, profile) {
  const group = button.closest('.image-optimizer-group');
  const originals = originalImageFiles.get(group) || [];
  if (!originals.length) return;
  if (profile === 'original') {
    const index = selectedImageIndexes.get(group) || 0;
    const output = (optimizedImageFiles.get(group) || originals.slice()).slice();
    output[index] = originals[index];
    optimizedImageFiles.set(group, output);
    setSelectedImageApproval(group, index, true);
    document.getElementById(group.dataset.input).files = imageFileList(output);
    renderImageGroupPreviews(group, output);
    await renderImageComparison(group); renderImageTargetOptions(group); markImageVolumeChoice(group, profile);
    setImageOptimizeState(group, 'done', 'نسخه اورجینال برای همین عکس انتخاب شد.');
    return;
  }
  const targets = { 'site-standard': 300 * 1024, 'site-light': 180 * 1024 };
  await applySelectedImageToAbsoluteTarget(group, targets[profile], profile);
}

function previewImageRange(range) {
  const group = range.closest('.image-optimizer-group');
  const label = group?.querySelector('.image-range-value');
  if (label) label.textContent = 'حدود ' + Number(range.value).toLocaleString('fa-IR') + ' کیلوبایت';
}

async function applyImageRange(range) {
  const group = range.closest('.image-optimizer-group');
  await applySelectedImageToAbsoluteTarget(group, Number(range.value) * 1024, 'range');
  markImageVolumeChoice(group, 'range');
}

function openImageCompareModal(button) {
  const group = button.closest('.image-optimizer-group');
  group?.querySelector('.image-compare-modal')?.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImageCompareModal(modal) {
  modal?.classList.add('hidden'); document.body.style.overflow = '';
}

async function existingImageFiles(group) {
  const urls = JSON.parse(group.dataset.existing || '[]');
  const files = [];
  for (let i = 0; i < urls.length; i++) {
    const response = await fetch(urls[i], { credentials: 'same-origin' });
    if (!response.ok) throw new Error('دریافت یکی از تصاویر فعلی ممکن نبود');
    const blob = await response.blob();
    const pathname = new URL(urls[i], location.href).pathname;
    files.push(new File([blob], decodeURIComponent(pathname.split('/').pop() || ('image-' + i)), { type: blob.type || 'image/jpeg' }));
  }
  return files;
}

function isMainImageGroup(group) {
  return group?.dataset.input === 'main-images-file';
}

function moveImageItem(items, fromIndex, toIndex) {
  const output = items.slice();
  if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= output.length || toIndex >= output.length) return output;
  const item = output.splice(fromIndex, 1)[0];
  output.splice(toIndex, 0, item);
  return output;
}

function movedSelectedImageIndex(selected, fromIndex, toIndex) {
  if (selected === fromIndex) return toIndex;
  if (fromIndex < toIndex && selected > fromIndex && selected <= toIndex) return selected - 1;
  if (fromIndex > toIndex && selected >= toIndex && selected < fromIndex) return selected + 1;
  return selected;
}

async function commitImageOrder(group, fromIndex, toIndex) {
  const input = document.getElementById(group.dataset.input);
  let submitted = Array.from(input?.files || []);
  const originals = (originalImageFiles.get(group) || []).slice();
  const optimized = (optimizedImageFiles.get(group) || []).slice();
  if (!submitted.length) submitted = (optimized.length ? optimized : originals).slice();
  if (!submitted.length) submitted = await existingImageFiles(group);
  if (!submitted.length || fromIndex === toIndex) return;

  const approvals = (imageOptimizationApproved.get(group) || Array(submitted.length).fill(true)).slice();
  const profiles = (selectedImageProfiles.get(group) || Array(submitted.length).fill('')).slice();
  const selected = selectedImageIndexes.get(group) || 0;
  const reorderedSubmitted = moveImageItem(submitted, fromIndex, toIndex);
  input.files = imageFileList(reorderedSubmitted);
  group.dataset.existing = '[]';
  originalImageFiles.set(group, moveImageItem(originals.length ? originals : submitted, fromIndex, toIndex));
  optimizedImageFiles.set(group, optimized.length ? moveImageItem(optimized, fromIndex, toIndex) : []);
  imageOptimizationApproved.set(group, moveImageItem(approvals, fromIndex, toIndex));
  selectedImageProfiles.set(group, moveImageItem(profiles, fromIndex, toIndex));
  selectedImageIndexes.set(group, movedSelectedImageIndex(selected, fromIndex, toIndex));
  renderImageGroupPreviews(group, reorderedSubmitted);
  await renderImageComparison(group);
  renderImageTargetOptions(group);
  document.dispatchEvent(new CustomEvent('product-images-changed'));
}

function renderImageGroupPreviews(group, files) {
  const strip = group.querySelector('.image-preview-strip');
  const label = group.querySelector('.image-file-label');
  if (label) label.textContent = files.length ? imageOptToFa(files.length) + ' تصویر انتخاب شد' : 'انتخاب تصاویر';
  if (!strip) return;
  strip.innerHTML = '';
  files.slice(0, 12).forEach(function (file, index) {
    const holder = document.createElement('span');
    holder.className = 'relative inline-flex';
    const image = document.createElement('img');
    image.className = 'w-14 h-14 rounded-lg object-cover border border-[var(--b2)]';
    image.src = URL.createObjectURL(file);
    image.onload = function () { URL.revokeObjectURL(image.src); };
    image.onclick = function (event) {
      event.stopPropagation();
      selectedImageIndexes.set(group, index);
      renderImageComparison(group); renderImageTargetOptions(group);
    };
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'absolute -top-1.5 -left-1.5 w-4 h-4 rounded-full bg-[var(--red)] text-white text-[8px] flex items-center justify-center border border-[var(--s2)]';
    remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    remove.title = 'حذف این عکس';
    remove.onclick = function (event) { event.stopPropagation(); removeSelectedImage(group, index); };
    holder.appendChild(image);
    holder.appendChild(remove);
    strip.appendChild(holder);
  });
}

async function removeSelectedImage(group, index) {
  const input = document.getElementById(group.dataset.input);
  let current = Array.from(input?.files || []);
  if (!current.length) current = (selectedUploadFiles.get(group) || optimizedImageFiles.get(group) || originalImageFiles.get(group) || []).slice();
  if (!current.length) current = await existingImageFiles(group);
  current.splice(index, 1);
  if (input) input.files = imageFileList(current);
  selectedUploadFiles.set(group, current.slice());
  group.dataset.existing = '[]';
  const originals = (originalImageFiles.get(group) || []).slice();
  const optimized = (optimizedImageFiles.get(group) || []).slice();
  originals.splice(index, 1); optimized.splice(index, 1);
  originalImageFiles.set(group, originals.length ? originals : current.slice());
  optimizedImageFiles.set(group, optimized);
  setImageApproval(group, current.length, current.length > 0);
  selectedImageIndexes.set(group, Math.max(0, Math.min(index, current.length - 1)));
  renderImageGroupPreviews(group, current);
  renderImageComparison(group);
  if (!current.length) setImageOptimizeState(group, 'idle', 'همه تصاویر حذف شدند.');
  document.dispatchEvent(new CustomEvent('product-images-changed'));
}

async function sharpenSelectedImage(button) {
  const group = button.closest('.image-optimizer-group');
  const input = document.getElementById(group.dataset.input);
  let files = Array.from(input?.files || []);
  if (!files.length) files = await existingImageFiles(group);
  if (!files.length) return setImageOptimizeState(group, 'idle', 'تصویری برای شارپ‌کردن وجود ندارد.');
  const index = Math.min(selectedImageIndexes.get(group) || 0, files.length - 1);
  setImageOptimizeState(group, 'processing', 'در حال شارپ‌کردن عکس انتخاب‌شده…');
  try {
    const source = files[index];
    const image = await loadImageFile(source);
    const scale = Math.min(1, 1800 / Math.max(image.naturalWidth, image.naturalHeight));
    const width = Math.max(1, Math.round(image.naturalWidth * scale));
    const height = Math.max(1, Math.round(image.naturalHeight * scale));
    const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height;
    const context = canvas.getContext('2d', { alpha: true, colorSpace: 'srgb' });
    context.imageSmoothingEnabled = true; context.imageSmoothingQuality = 'high';
    context.filter = 'contrast(1.12) saturate(1.04)';
    context.drawImage(image, 0, 0, width, height);
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', .93));
    if (!blob) throw new Error('sharpen failed');
    files[index] = new File([blob], source.name.replace(/\.[^.]+$/, '') + '-sharp.webp', { type: 'image/webp', lastModified: Date.now() });
    input.files = imageFileList(files);
    originalImageFiles.set(group, files.slice());
    optimizedImageFiles.set(group, files.slice());
    setImageApproval(group, files.length, true);
    renderImageGroupPreviews(group, files);
    await renderImageComparison(group);
    setImageOptimizeState(group, 'done', 'عکس انتخاب‌شده شارپ و آماده ثبت شد.');
    document.dispatchEvent(new CustomEvent('product-images-changed'));
  } catch (error) {
    setImageOptimizeState(group, 'failed', 'شارپ‌کردن عکس انجام نشد؛ دوباره تلاش کنید.');
  }
}

async function optimizeImageGroup(buttonOrGroup) {
  const group = buttonOrGroup.closest ? buttonOrGroup.closest('.image-optimizer-group') : buttonOrGroup;
  const input = document.getElementById(group.dataset.input);
  setImageOptimizeState(group, 'processing', 'لطفاً تا پایان پردازش صبر کنید.');
  try {
    let files = Array.from(input.files || []);
    if (!files.length) files = await existingImageFiles(group);
    if (!files.length) {
      setImageOptimizeState(group, 'idle', 'تصویری برای بهینه‌سازی وجود ندارد.');
      return;
    }
    if (!originalImageFiles.has(group)) originalImageFiles.set(group, files.slice());
    const optimized = [];
    const sources = originalImageFiles.get(group) || files;
    for (const file of sources) optimized.push(await optimizeOneImage(file));
    optimizedImageFiles.set(group, optimized);
    setImageApproval(group, sources.length, true);
    selectedImageProfiles.set(group, Array(sources.length).fill(''));
    input.files = imageFileList(optimized);
    renderImageGroupPreviews(group, optimized);
    await renderImageComparison(group);
    renderImageTargetOptions(group);
    markImageVolumeChoice(group, '');
    setImageOptimizeState(group, 'done', 'تصاویر آماده ثبت هستند.');
  } catch (error) {
    setImageOptimizeState(group, 'failed', 'بهینه‌سازی انجام نشد؛ دوباره تلاش کنید.');
  }
}


/* راه‌اندازی همه‌ی گروه‌های آپلود عکس داخل یک صفحه/ریشه */
function initImageOptimizerGroups(root) {
  (root || document).querySelectorAll('.image-optimizer-group').forEach(function (group) {
    setImageOptimizeState(group, 'idle', group.querySelectorAll('[data-existing]').length ? '' : group.querySelector('.image-optimize-status')?.textContent);
    const input = document.getElementById(group.dataset.input);
    if (!input) return;
    if (!input.files.length && JSON.parse(group.dataset.existing || '[]').length) {
      existingImageFiles(group).then(function (files) {
        if (selectedUploadFiles.has(group)) return;
        selectedUploadFiles.set(group, files.slice());
        originalImageFiles.set(group, files.slice());
        optimizedImageFiles.set(group, []);
        setImageApproval(group, files.length, true);
        selectedImageProfiles.set(group, Array(files.length).fill(''));
        selectedImageIndexes.set(group, 0);
        renderImageGroupPreviews(group, files);
        renderImageComparison(group);
      }).catch(function () {
        setImageOptimizeState(group, 'failed', 'نمایش تصاویر فعلی ممکن نبود؛ صفحه را دوباره بارگذاری کنید.');
      });
    } else if (input.files.length) {
      selectedUploadFiles.set(group, Array.from(input.files));
    }
    input.addEventListener('change', function () {
      const newlySelected = Array.from(input.files || []);
      const previous = selectedUploadFiles.get(group) || [];
      const selected = mergeUploadFiles(previous, newlySelected);
      if (selected.length !== newlySelected.length) input.files = imageFileList(selected);
      selectedUploadFiles.set(group, selected.slice());
      originalImageFiles.set(group, selected.slice());
      optimizedImageFiles.set(group, []);
      setImageApproval(group, selected.length, false);
      selectedImageProfiles.set(group, Array(selected.length).fill(''));
      selectedImageIndexes.set(group, 0);
      renderImageGroupPreviews(group, selected);
      renderImageComparison(group);
      setImageOptimizeState(group, 'idle', '');
      optimizeImageGroup(group);
    });
  });
}

/* آیا هنوز تصویری در حال پردازش یا تأییدنشده هست؟ برای جلوگیری از ثبت ناقص */
function imageOptimizerPendingState(root) {
  const scope = root || document;
  const processing = scope.querySelector('.image-optimizer-group[data-optimize-state="processing"]');
  if (processing) return { state: 'processing', group: processing };
  const failed = scope.querySelector('.image-optimizer-group[data-optimize-state="failed"]');
  if (failed) return { state: 'failed', group: failed };
  const incomplete = Array.from(scope.querySelectorAll('.image-optimizer-group')).find(function (group) {
    const originals = originalImageFiles.get(group) || [];
    const approvals = imageOptimizationApproved.get(group) || [];
    return originals.length > 0 && (approvals.length !== originals.length || approvals.some(function (approved) { return !approved; }));
  });
  if (incomplete) return { state: 'incomplete', group: incomplete };
  return null;
}

document.addEventListener('DOMContentLoaded', function () { initImageOptimizerGroups(document); });
