<script>
/* تابع مشترک باز/بستن بخش‌های فاز بعد */
function toggleFutureSection(cb){ var b = cb.closest('.future-block'); if(b){ var s = b.querySelector('.future-section'); if(s) s.classList.toggle('hidden', !cb.checked); } }

/* ── ابزارهای کمکی عمومی مربوط به فایلهای آپلود ── */
function updateFileLabel(input, labelId, isMultiple = false) {
  const label = document.getElementById(labelId);
  if (!label) return;
  if (input.files && input.files.length > 0) {
    label.textContent = isMultiple ? input.files.length + ' فایل انتخاب شد' : input.files[0].name;
  }
}

/* ── کامپوننت مستقل: Preview / Replace / Remove برای Uploadهای تک‌فایلی ── */
function previewUpload(input, imgId, emptyStateId, removeBtnId) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function (e) {
    const img = document.getElementById(imgId);
    img.src = e.target.result;
    img.classList.remove('hidden');
    document.getElementById(emptyStateId).classList.add('hidden');
    const btn = document.getElementById(removeBtnId);
    btn.classList.remove('hidden');
    btn.classList.add('flex');
  };
  reader.readAsDataURL(input.files[0]);
}

function removeUpload(inputId, imgId, emptyStateId, removeBtnId, titleId, defaultTitle) {
  const input = document.getElementById(inputId);
  if(input) input.value = '';
  const img = document.getElementById(imgId);
  if(img) img.classList.add('hidden');
  const empty = document.getElementById(emptyStateId);
  if(empty) empty.classList.remove('hidden');
  const btn = document.getElementById(removeBtnId);
  if(btn) {
    btn.classList.add('hidden');
    btn.classList.remove('flex');
  }
  const title = document.getElementById(titleId);
  if(title) title.textContent = defaultTitle;
}

/* پیش‌نمایش چندفایلی برای گالری نمونه خروجی‌ها */
function previewMultiUpload(input, stripId) {
  const strip = document.getElementById(stripId);
  if(!strip) return;
  strip.innerHTML = '';
  if (!input.files) return;
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = function (e) {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'w-11 h-11 rounded-lg object-cover border border-[var(--b2)]';
      strip.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

/* NEW: Upload Queue System — نمایش صف فایل‌های در حال آپلود برای Uploadهای چندگانه */
function renderUploadQueue(input, queueId) {
  const queue = document.getElementById(queueId);
  if (!queue) return;
  queue.innerHTML = '';
  if (!input.files || !input.files.length) return;
  Array.from(input.files).forEach(file => {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 bg-[var(--bg)] border border-[var(--b1)] rounded-lg px-2 py-1.5 mt-1';
    row.innerHTML = `
      <i class="fa-solid fa-file-image text-[10px] text-[var(--text3)] shrink-0"></i>
      <span class="text-[10px] text-[var(--text2)] flex-1 truncate">${file.name}</span>
      <div class="w-16 h-1 bg-[var(--b1)] rounded-full overflow-hidden shrink-0"><div class="h-full bg-[var(--green)]" style="width:100%"></div></div>
      <span class="text-[9px] text-[var(--green)] shrink-0">آماده</span>
    `;
    queue.appendChild(row);
  });
}

/* ── Drag & Drop عمومی برای Uploadها ── */
function wireUploadZone(zoneId, inputId) {
  const zone = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;
  ['dragover', 'dragenter'].forEach(evt => zone.addEventListener(evt, e => {
    e.preventDefault(); e.stopPropagation();
    zone.classList.add('border-[var(--accent)]');
  }));
  ['dragleave', 'drop'].forEach(evt => zone.addEventListener(evt, e => {
    e.preventDefault(); e.stopPropagation();
    zone.classList.remove('border-[var(--accent)]');
  }));
  zone.addEventListener('drop', e => {
    if (e.dataTransfer.files && e.dataTransfer.files.length) {
      input.files = e.dataTransfer.files;
      input.dispatchEvent(new Event('change'));
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  ['thumb-zone', 'cover-zone', 'samples-zone'].forEach(id => wireUploadZone(id, id.replace('-zone', '-file')));
});

/* ── Radio Card نوع رسانه: هایلایت کردن کارت انتخاب‌شده ── */
document.querySelectorAll('.media-type-card input[type="radio"]').forEach(radio => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('.media-type-card').forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
    if (radio.checked) radio.closest('.media-type-card').classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
  });
});

/* ── NEW: انتخاب حالت پیش‌نمایش گالری (فقط UI) ── */
function setGalleryPreviewMode(mode) {
  document.getElementById('new-gallery-preview-mode').value = mode;
  document.querySelectorAll('.gallery-mode-btn').forEach(btn => {
    const active = btn.dataset.mode === mode;
    btn.classList.toggle('border-[var(--accent)]', active);
    btn.classList.toggle('bg-[var(--accent)]/8', active);
    btn.classList.toggle('text-[var(--text)]', active);
    btn.classList.toggle('border-[var(--b1)]', !active);
    btn.classList.toggle('bg-[var(--s1)]', !active);
    btn.classList.toggle('text-[var(--text2)]', !active);
  });
  renderGalleryModePreview(mode);
}

function renderGalleryModePreview(mode) {
  const root = document.getElementById('gallery-mode-live-preview');
  if (!root) return;
  const input = document.getElementById('main-images-file');
  let urls = input?.files?.length ? Array.from(input.files).map(file => URL.createObjectURL(file)) : [];
  if (!urls.length) {
    const group = document.querySelector('[data-input="main-images-file"]');
    try { urls = JSON.parse(group?.dataset?.existing || '[]'); } catch (error) { urls = []; }
  }
  while (urls.length < 5) urls.push(urls[urls.length % Math.max(1, urls.length)] || '');
  root.className = mode === 'slider'
    ? 'flex gap-1.5 p-2 bg-[var(--bg)] border border-[var(--b1)] rounded-xl max-w-md overflow-x-auto'
    : mode === 'carousel'
      ? 'flex items-center justify-center gap-1.5 p-2 bg-[var(--bg)] border border-[var(--b1)] rounded-xl max-w-md'
      : 'grid grid-cols-5 gap-1.5 p-2 bg-[var(--bg)] border border-[var(--b1)] rounded-xl max-w-md';
  root.innerHTML = '';
  urls.slice(0, 5).forEach(function(url, index) {
    const item = document.createElement('div');
    item.className = 'rounded-md overflow-hidden bg-[var(--b1)] ' + (mode === 'slider' ? 'w-16 h-14 shrink-0' : mode === 'carousel' ? (index === 2 ? 'w-20 h-16' : 'w-12 h-12 opacity-60') : 'aspect-square');
    if (url) { const img = document.createElement('img'); img.src = url; img.className = 'w-full h-full object-cover'; item.appendChild(img); }
    root.appendChild(item);
  });
}

/* ── دسته‌بندی محصول: انتخاب چندگانه به‌صورت تگ (جایگزین سلکت تک‌انتخابی قبلی) ──
   لیست کامل دسته‌بندی‌ها (مسطح‌شده با عمق برای تورفتگی) و دسته‌بندی‌های از‌قبل‌انتخاب‌شده
   (old('category_ids')/تکثیر محصول/مقدار قدیمی تک‌فیلدی) از طریق window.CATEGORIES_FLAT و
   window.CATEGORIES_SELECTED_INIT در همین پارشیال تزریق شده‌اند. در لحظه‌ی ثبت فرم،
   submitForm() در products-create.js این چیپ‌ها را می‌خواند و به category_ids[] تبدیل می‌کند. */
let selectedCategories = Array.isArray(window.CATEGORIES_SELECTED_INIT) ? window.CATEGORIES_SELECTED_INIT.slice() : [];
const ALL_CATEGORIES = Array.isArray(window.CATEGORIES_FLAT) ? window.CATEGORIES_FLAT : [];

function renderCatChips() {
  const wrap = document.getElementById('cat-tags-wrap');
  const input = document.getElementById('cat-search-input');
  if (!wrap || !input) return;
  wrap.querySelectorAll('[data-cat-id]').forEach(el => el.remove());
  selectedCategories.forEach(cat => {
    const chip = document.createElement('span');
    chip.className = 'inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]';
    chip.dataset.catId = cat.id;
    chip.innerHTML = `${cat.name}<button type="button" class="text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1" aria-label="حذف دسته‌بندی" onclick="removeCategory(${cat.id})">×</button>`;
    wrap.insertBefore(chip, input);
  });
  if (typeof renderStepper === 'function') renderStepper();
  if (typeof refreshFinalSummary === 'function') refreshFinalSummary();
  if (typeof refreshProductPreview === 'function') refreshProductPreview();
}

function addCategory(id) {
  id = parseInt(id, 10);
  if (selectedCategories.some(c => c.id === id)) return;
  const cat = ALL_CATEGORIES.find(c => c.id === id);
  if (!cat) return;
  selectedCategories.push(cat);
  renderCatChips();
  const input = document.getElementById('cat-search-input');
  if (input) { input.value = ''; input.focus(); }
  renderCatDropdown('');
}

function removeCategory(id) {
  id = parseInt(id, 10);
  selectedCategories = selectedCategories.filter(c => c.id !== id);
  renderCatChips();
}

/* لیست دسته‌بندی‌های نمایش‌داده‌شده در نام‌های انتخاب‌شده (برای خلاصه/پیش‌نمایش گام پنجم) */
function getSelectedCategoryNames() {
  return selectedCategories.map(c => c.name).join('، ');
}

function renderCatDropdown(filter) {
  const dd = document.getElementById('cat-dropdown');
  if (!dd) return;
  const f = (filter || '').trim().toLowerCase();
  const items = ALL_CATEGORIES.filter(c => {
    if (selectedCategories.some(s => s.id === c.id)) return false;
    if (!f) return true;
    return c.name.toLowerCase().indexOf(f) !== -1;
  });
  dd.innerHTML = '';
  if (!items.length) {
    dd.innerHTML = '<div class="px-3 py-3 text-[11px] text-[var(--text3)] text-center">دسته‌بندی‌ای یافت نشد</div>';
  } else {
    items.forEach(c => {
      const row = document.createElement('div');
      row.className = 'px-3 py-2 text-xs cursor-pointer transition-colors text-[var(--text2)] hover:bg-[var(--accent)]/10 hover:text-[var(--text)]';
      row.textContent = (c.depth > 0 ? '— '.repeat(c.depth) : '') + c.name;
      row.onclick = () => addCategory(c.id);
      dd.appendChild(row);
    });
  }
  dd.classList.remove('hidden');
}

document.addEventListener('click', function (e) {
  const box = document.getElementById('cat-multiselect');
  const dd = document.getElementById('cat-dropdown');
  if (box && dd && !box.contains(e.target)) dd.classList.add('hidden');
});

document.addEventListener('DOMContentLoaded', renderCatChips);
document.addEventListener('DOMContentLoaded', function(){ renderGalleryModePreview('grid'); document.getElementById('main-images-file')?.addEventListener('change', function(){ renderGalleryModePreview(document.getElementById('new-gallery-preview-mode')?.value || 'grid'); }); });
</script>
