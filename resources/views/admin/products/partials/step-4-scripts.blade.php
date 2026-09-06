<script>
/* ══════ مصرف اعتبار سه‌سطحی محصول ══════ */
function setQualityCreditCostInputs(costs) {
  Object.keys(costs || {}).forEach(function (key) {
    var input = document.querySelector('[data-quality-credit-cost="' + key + '"]');
    if (input && Number(costs[key]) > 0) input.value = Number(costs[key]);
  });
}

function markQualityCreditCostsCustom() {
  var presetKey = document.getElementById('quality-credit-preset-key');
  var preset = document.querySelector('[data-quality-credit-preset]');
  if (presetKey) presetKey.value = 'custom';
  if (preset && preset.value !== 'custom') preset.value = 'custom';
  var status = document.querySelector('[data-quality-credit-status]');
  if (status) {
    status.textContent = 'تغییر دستی فعال است؛ این محصول با تنظیم سفارشی ذخیره می‌شود.';
    status.style.color = 'var(--warning)';
  }
}

function qualityCreditCostsFromInputs() {
  var costs = {};
  document.querySelectorAll('[data-quality-credit-cost]').forEach(function (input) {
    costs[input.dataset.qualityCreditCost] = Number(input.value || 0);
  });
  return costs;
}

function qualityCreditPresetStatus(message, isError) {
  var box = document.querySelector('[data-quality-credit-preset-manager-status]');
  if (box) {
    box.textContent = message;
    box.style.color = isError ? 'var(--danger)' : 'var(--success)';
  }
  if (typeof showGlobalError === 'function' && isError) showGlobalError(message);
}

function qualityCreditRequest(url, method, payload) {
  return fetch(url, {
    method: method,
    headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},
    credentials: 'same-origin',
    body: payload ? JSON.stringify(payload) : undefined,
  }).then(function (response) {
    return response.json().catch(function () { return {}; }).then(function (data) {
      if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'ذخیره‌ی پیش‌فرض مصرف اعتبار انجام نشد.');
      return data;
    });
  });
}

function renderQualityCreditPresetSelect(presets, selectedKey) {
  var select = document.querySelector('[data-quality-credit-preset]');
  if (!select) return;
  var current = selectedKey || select.value;
  select.innerHTML = Object.entries(presets).map(function (entry) {
    return '<option value="' + String(entry[0]).replace(/"/g, '&quot;') + '">' + String(entry[1].name || entry[0]).replace(/[&<>]/g, '') + '</option>';
  }).join('') + '<option value="custom">تنظیم سفارشی</option>';
  select.value = presets[current] ? current : 'custom';
}

function renderQualityCreditPresetManager(presets) {
  var list = document.querySelector('[data-quality-credit-preset-list]');
  if (!list) return;
  list.innerHTML = Object.entries(presets).map(function (entry) {
    var key = entry[0], preset = entry[1];
    var costs = preset.costs || {};
    return '<div class="p-3 rounded-xl bg-[var(--s1)] border border-[var(--b1)]" data-credit-preset-row="' + key + '">' +
      '<div class="flex items-center gap-2 flex-wrap">' +
      '<input class="flex-1 min-w-[150px] h-9 px-2.5 rounded-lg bg-[var(--s2)] border border-[var(--b1)] text-xs text-[var(--text)]" data-credit-preset-name value="' + String(preset.name || '').replace(/[&<>"']/g, '') + '">' +
      '<button type="button" class="h-9 px-2.5 rounded-lg text-[10px] font-bold bg-[var(--primary-l)] text-[var(--primary)]" data-credit-preset-save>ذخیره نام</button>' +
      '<button type="button" class="h-9 px-2.5 rounded-lg text-[10px] font-bold ' + (preset.is_default_for_product_creation ? 'bg-[var(--success-l)] text-[var(--success)]' : 'bg-[var(--s2)] text-[var(--text2)]') + '" data-credit-preset-default>' + (preset.is_default_for_product_creation ? 'پیش‌فرض ثبت محصول' : 'انتخاب برای ثبت محصول') + '</button>' +
      '<button type="button" class="h-9 px-2.5 rounded-lg text-[10px] font-bold text-[var(--danger)] bg-[var(--danger-l)]" data-credit-preset-delete>حذف</button>' +
      '</div>' +
      '<div class="flex items-center gap-3 flex-wrap mt-2 text-[10px] text-[var(--text3)]">استاندارد: <b>' + Number(costs.standard || 0).toLocaleString('fa-IR') + '</b> · حرفه‌ای: <b>' + Number(costs.professional || 0).toLocaleString('fa-IR') + '</b> · بهترین: <b>' + Number(costs.best || 0).toLocaleString('fa-IR') + '</b> اعتبار</div>' +
      '</div>';
  }).join('');
}

function openQualityCreditPresetManager() {
  var dialog = document.getElementById('quality-credit-preset-dialog');
  if (!dialog) return;
  renderQualityCreditPresetManager(window.__qualityCreditPresets || {});
  dialog.showModal();
}

function closeQualityCreditPresetManager() { document.getElementById('quality-credit-preset-dialog')?.close(); }

document.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('[data-quality-credit-pricing]');
  if (!root) return;
  var presets = {};
  try { presets = JSON.parse(root.dataset.creditPresets || '{}'); } catch (error) { presets = {}; }
  window.__qualityCreditPresets = presets;
  var select = root.querySelector('[data-quality-credit-preset]');
  var keyInput = document.getElementById('quality-credit-preset-key');
  var status = root.querySelector('[data-quality-credit-status]');
  select?.addEventListener('change', function () {
    var selected = presets[select.value];
    if (selected?.costs) {
      window.__qualityCreditSourcePresetKey = select.value;
      setQualityCreditCostInputs(selected.costs);
      if (keyInput) keyInput.value = select.value;
      if (status) { status.textContent = 'مقادیر پیش‌فرض انتخاب شد؛ در صورت نیاز قابل ویرایش است.'; status.style.color = 'var(--success)'; }
    } else {
      markQualityCreditCostsCustom();
    }
  });
  root.querySelector('[data-manage-quality-credit-presets]')?.addEventListener('click', openQualityCreditPresetManager);
  root.querySelector('[data-fix-quality-credit-preset]')?.addEventListener('click', function () {
    var key = select?.value === 'custom' ? (window.__qualityCreditSourcePresetKey || '') : (select?.value || '');
    var preset = presets[key];
    if (!preset?.update_url) {
      qualityCreditPresetStatus('ابتدا یک پیش‌فرض ذخیره‌شده را انتخاب کنید؛ تنظیم سفارشی قابل ذخیره روی پیش‌فرض نیست.', true);
      return;
    }
    qualityCreditRequest(preset.update_url, 'PATCH', {costs: qualityCreditCostsFromInputs()})
      .then(function (data) {
        presets[key] = Object.assign(presets[key], {costs: data.costs || qualityCreditCostsFromInputs()});
        if (select) select.value = key;
        if (keyInput) keyInput.value = key;
        qualityCreditPresetStatus(data.message || 'اعداد این محصول در پیش‌فرض ذخیره شد.', false);
      })
      .catch(function (error) { qualityCreditPresetStatus(error.message, true); });
  });
  root.querySelectorAll('[data-quality-credit-cost]').forEach(function (input) {
    input.addEventListener('input', markQualityCreditCostsCustom);
  });

  document.getElementById('quality-credit-preset-dialog')?.addEventListener('click', function (event) {
    if (event.target === this) this.close();
  });
  document.querySelector('[data-close-quality-credit-presets]')?.addEventListener('click', closeQualityCreditPresetManager);
  document.querySelector('[data-add-quality-credit-preset]')?.addEventListener('click', function () {
    var nameInput = document.querySelector('[data-new-quality-credit-preset-name]');
    var name = String(nameInput?.value || '').trim();
    if (!name) { qualityCreditPresetStatus('نام پیش‌فرض را وارد کنید.', true); return; }
    qualityCreditRequest(root.dataset.creditPresetCreateUrl, 'POST', {name: name, costs: qualityCreditCostsFromInputs()})
      .then(function (data) {
        var key = data.preset.preset_key;
        presets[key] = {name: data.preset.name, costs: data.costs, is_default_for_product_creation: !!data.preset.is_default_for_product_creation, update_url: data.update_url, delete_url: data.delete_url};
        window.__qualityCreditPresets = presets;
        renderQualityCreditPresetManager(presets);
        renderQualityCreditPresetSelect(presets, key);
        if (keyInput) keyInput.value = key;
        if (nameInput) nameInput.value = '';
        qualityCreditPresetStatus(data.message || 'پیش‌فرض اضافه شد.', false);
      })
      .catch(function (error) { qualityCreditPresetStatus(error.message, true); });
  });
  document.querySelector('[data-quality-credit-preset-list]')?.addEventListener('click', function (event) {
    var row = event.target.closest('[data-credit-preset-row]');
    if (!row) return;
    var key = row.dataset.creditPresetRow, preset = presets[key];
    if (!preset) return;
    if (event.target.closest('[data-credit-preset-save]')) {
      var name = row.querySelector('[data-credit-preset-name]')?.value.trim();
      if (!name) { qualityCreditPresetStatus('نام پیش‌فرض نمی‌تواند خالی باشد.', true); return; }
      qualityCreditRequest(preset.update_url, 'PATCH', {name: name})
        .then(function (data) { preset.name = data.preset.name; renderQualityCreditPresetManager(presets); renderQualityCreditPresetSelect(presets); qualityCreditPresetStatus(data.message, false); })
        .catch(function (error) { qualityCreditPresetStatus(error.message, true); });
    } else if (event.target.closest('[data-credit-preset-default]')) {
      qualityCreditRequest(preset.update_url, 'PATCH', {is_default_for_product_creation: true})
        .then(function (data) {
          Object.keys(presets).forEach(function (item) { presets[item].is_default_for_product_creation = item === key; });
          renderQualityCreditPresetManager(presets); renderQualityCreditPresetSelect(presets, key); select.value = key; keyInput.value = key; setQualityCreditCostInputs(presets[key].costs); qualityCreditPresetStatus(data.message, false);
        }).catch(function (error) { qualityCreditPresetStatus(error.message, true); });
    } else if (event.target.closest('[data-credit-preset-delete]')) {
      if (!window.confirm('این پیش‌فرض حذف شود؟ محصولات متصل به پیش‌فرض بعدی منتقل می‌شوند.')) return;
      qualityCreditRequest(preset.delete_url, 'DELETE')
        .then(function (data) {
          delete presets[key]; window.__qualityCreditPresets = presets;
          var nextKey = Object.keys(presets).find(function (item) { return presets[item].is_default_for_product_creation; }) || Object.keys(presets)[0] || 'custom';
          renderQualityCreditPresetManager(presets); renderQualityCreditPresetSelect(presets, nextKey);
          if (presets[nextKey]) { keyInput.value = nextKey; setQualityCreditCostInputs(presets[nextKey].costs); }
          qualityCreditPresetStatus(data.message, false);
        }).catch(function (error) { qualityCreditPresetStatus(error.message, true); });
    }
  });
});

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

/* ══════ رادیوکارت‌های نمایش/شکل/گالری: هایلایت کارت انتخاب‌شده ══════ */
function wireCardRadioGroup(selector) {
  document.querySelectorAll(selector + ' input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll(selector).forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
      if (radio.checked) radio.closest(selector.replace(' input','')).classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
      if (typeof refreshFinalSummary === 'function') refreshFinalSummary();
    });
  });
}
document.querySelectorAll('.preview-card-option, .shape-card-option').forEach(card => {
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
