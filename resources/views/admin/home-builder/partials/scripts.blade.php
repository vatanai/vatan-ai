<script>
/* ══════════════════════════════════════════════════════════════════
   Home Builder — منطق سمت کاربر صفحه‌ی مدیریت صفحه هوم
   همه‌ی درخواست‌ها AJAX هستند (fetch + CSRF) — همان الگوی
   resources/views/admin/products/partials/drawer.blade.php
   ══════════════════════════════════════════════════════════════════ */
window.HB_TYPES = @json($typeRegistry);
window.HB_CATEGORIES = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name_fa])->values());
window.HB_CSRF = '{{ csrf_token() }}';
window.HB_LAYOUT_THUMB_BASE = '{{ asset('admin/img/home-builder/layouts') }}';
window.HB_ROUTES = {
  preview: '{{ route('admin.home-builder.preview') }}',
  store: '{{ route('admin.home-builder.store') }}',
  update: '{{ url('admin/home-builder') }}/__ID__',
  destroy: '{{ url('admin/home-builder') }}/__ID__',
  duplicate: '{{ url('admin/home-builder') }}/__ID__/duplicate',
  status: '{{ url('admin/home-builder') }}/__ID__/status',
  reorder: '{{ route('admin.home-builder.reorder') }}',
  productSearch: '{{ route('admin.home-builder.products.search') }}',
};

const HomeBuilder = (function () {
  const state = { isNew: false, currentId: null, selectedType: null, selectedLayout: null, settingsValues: {}, previewDevice: 'desktop', previewTimer: null, previewRequestId: 0, layoutPreviewCache: {} };

  function fetchJson(url, method, body) {
    return fetch(url, {
      method,
      headers: {
        'X-CSRF-TOKEN': window.HB_CSRF,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: body ? JSON.stringify(body) : undefined,
    }).then(async (r) => {
      if (!r.ok) {
        const errBody = await r.json().catch(() => ({}));
        throw new Error(errBody.message || 'خطا در ارتباط با سرور');
      }
      return r.json();
    });
  }

  // ── Drawer افزودن (گام نوع + گام Layout) ──────────────────────
  function openAddDrawer() {
    document.getElementById('hb-add-overlay').classList.add('open');
    document.getElementById('hb-add-panel').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadAllAddLayoutPreviews();
  }

  function closeAddDrawer() {
    document.getElementById('hb-add-overlay').classList.remove('open');
    document.getElementById('hb-add-panel').classList.remove('open');
    document.body.style.overflow = '';
  }

  function loadAllAddLayoutPreviews() {
    document.querySelectorAll('#hb-layout-gallery [data-preview-type][data-preview-layout]').forEach((preview) => {
      loadLayoutPreview(preview.id, preview.dataset.previewType, preview.dataset.previewLayout);
    });
  }

  function selectLayout(type, layoutKey) {
    state.isNew = true;
    state.currentId = null;
    state.selectedType = type;
    state.selectedLayout = layoutKey;
    closeAddDrawer();
    openEditDrawerInternal({ id: null, type, layout: layoutKey, title_fa: '', subtitle_fa: '', settings: {}, responsive: {} });
  }

  // ── Drawer تنظیمات (هم افزودن، هم ویرایش) ──────────────────────
  function openEditDrawer(section) {
    state.isNew = false;
    state.currentId = section.id;
    state.selectedType = section.type;
    state.selectedLayout = section.layout;
    openEditDrawerInternal(section);
  }

  function openEditDrawerInternal(section) {
    const typeInfo = window.HB_TYPES[section.type];
    document.getElementById('hb-edit-title').textContent = (state.isNew ? 'افزودن ' : 'ویرایش ') + typeInfo.label;
    document.getElementById('hb-edit-subtitle').textContent = typeInfo.description || '';
    document.getElementById('hb-f-title_fa').value = section.title_fa || '';
    document.getElementById('hb-f-subtitle_fa').value = section.subtitle_fa || '';

    state.settingsValues = {};
    renderEditLayoutGallery(section.type, section.layout);
    renderSettingsFields(section.type, section.settings || {});

    const resp = Object.assign({ desktop: true, tablet: true, mobile: true, mobile_layout: null }, section.responsive || {});
    document.getElementById('hb-f-resp-desktop').checked = !!resp.desktop;
    document.getElementById('hb-f-resp-tablet').checked = !!resp.tablet;
    document.getElementById('hb-f-resp-mobile').checked = !!resp.mobile;

    const mobileLayoutSelect = document.getElementById('hb-f-resp-mobile-layout');
    mobileLayoutSelect.innerHTML = '<option value="">— همان Layout اصلی —</option>' +
      Object.entries(typeInfo.layouts).map(([key, l]) => `<option value="${key}">${l.label}</option>`).join('');
    mobileLayoutSelect.value = resp.mobile_layout || '';

    document.getElementById('hb-edit-overlay').classList.add('open');
    document.getElementById('hb-edit-panel').classList.add('open');
    document.body.style.overflow = 'hidden';
    scheduleLivePreview(true);
  }

  function closeEditDrawer() {
    document.getElementById('hb-edit-overlay').classList.remove('open');
    document.getElementById('hb-edit-panel').classList.remove('open');
    document.body.style.overflow = '';
  }

  function renderEditLayoutGallery(type, currentLayout) {
    const typeInfo = window.HB_TYPES[type];
    const gallery = document.getElementById('hb-edit-layout-gallery');
    gallery.innerHTML = Object.entries(typeInfo.layouts).map(([key, layout]) => {
      const active = key === currentLayout;
      return `
        <button type="button" class="hb-layout-card" data-layout-key="${key}" onclick="HomeBuilder.pickEditLayout('${key}')"
                style="text-align:center;padding:8px;border:1px solid ${active ? 'var(--primary)' : 'var(--border)'};border-radius:12px;background:var(--input-bg);cursor:pointer;">
          <div class="hb-layout-preview" id="hb-edit-preview-${type}-${key}">
            <div class="hb-preview-loading"><i class="fa-solid fa-spinner fa-spin"></i> پیش‌نمایش واقعی</div>
            <iframe title="پیش‌نمایش ${escapeHtml(layout.label)}" tabindex="-1"></iframe>
          </div>
          <div class="text-[11px] font-bold" style="color:${active ? 'var(--primary)' : 'var(--text-h)'};">${layout.label}</div>
        </button>
      `;
    }).join('');
    Object.keys(typeInfo.layouts).forEach((layoutKey) => loadLayoutPreview(`hb-edit-preview-${type}-${layoutKey}`, type, layoutKey));
  }

  function pickEditLayout(layoutKey) {
    const currentValues = collectSettingsValues(state.selectedType);
    state.selectedLayout = layoutKey;
    renderEditLayoutGallery(state.selectedType, layoutKey);
    renderSettingsFields(state.selectedType, currentValues);
    scheduleLivePreview(true);
  }

  // ── فرم داینامیک تنظیمات اختصاصی هر نوع Section ──────────────────
  function renderSettingsFields(type, values) {
    const typeInfo = window.HB_TYPES[type];
    const wrap = document.getElementById('hb-edit-fields');
    state.settingsValues = Object.assign({}, state.settingsValues || {}, values || {});

    const sourceField = typeInfo.settings_fields.find((f) => f.key === 'source');
    const currentSource = state.settingsValues.source ?? (sourceField ? (sourceField.default ?? 'latest') : null);

    const visibleFields = typeInfo.settings_fields
      .filter((field) => !field.show_if_layout || field.show_if_layout.includes(state.selectedLayout))
      .filter((field) => !field.show_if_source || field.show_if_source.includes(String(currentSource)))
      .filter((field) => {
        if (!field.show_if_setting) return true;
        const dependency = typeInfo.settings_fields.find((item) => item.key === field.show_if_setting.key);
        const current = state.settingsValues[field.show_if_setting.key] ?? dependency?.default ?? null;
        return field.show_if_setting.values.includes(String(current));
      });

    wrap.innerHTML = visibleFields.map((field) => fieldHtml(field, state.settingsValues[field.key])).join('');

    visibleFields.filter((f) => f.type === 'product_multiselect').forEach((f) => initProductPicker(f.key));

    const sourceEl = document.getElementById('hb-sf-source');
    if (sourceEl) {
      sourceEl.addEventListener('change', function () {
        state.settingsValues = Object.assign({}, state.settingsValues, collectSettingsValues(type));
        renderSettingsFields(type, {});
      });
    }

    const dependencyKeys = [...new Set(typeInfo.settings_fields.filter((field) => field.show_if_setting).map((field) => field.show_if_setting.key))];
    dependencyKeys.forEach((key) => {
      const dependencyEl = document.getElementById(`hb-sf-${key}`);
      if (!dependencyEl) return;
      dependencyEl.addEventListener('change', function () {
        state.settingsValues = Object.assign({}, state.settingsValues, collectSettingsValues(type));
        renderSettingsFields(type, {});
        scheduleLivePreview(true);
      });
    });

    wrap.querySelectorAll('input,select,textarea').forEach((el) => {
      el.addEventListener('input', () => scheduleLivePreview());
      el.addEventListener('change', () => scheduleLivePreview());
    });
  }

  function fieldHtml(field, value) {
    const val = value === undefined || value === null ? (field.default ?? '') : value;
    const id = `hb-sf-${field.key}`;
    const label = `<div class="drawer-label mb-1">${field.label}</div>`;
    const placeholder = escapeHtml(field.placeholder || '');

    if (field.type === 'textarea') {
      return `<div>${label}<textarea id="${id}" class="input-pro" rows="3" style="height:auto;padding-top:8px;">${escapeHtml(val)}</textarea></div>`;
    }
    if (field.type === 'select') {
      const opts = Object.entries(field.options || {}).map(([k, l]) => `<option value="${k}" ${String(val) === k ? 'selected' : ''}>${l}</option>`).join('');
      return `<div>${label}<select id="${id}" class="input-pro">${opts}</select></div>`;
    }
    if (field.type === 'category_select') {
      const opts = window.HB_CATEGORIES.map((c) => `<option value="${c.id}" ${String(val) === String(c.id) ? 'selected' : ''}>${c.name}</option>`).join('');
      return `<div>${label}<select id="${id}" class="input-pro"><option value="">— بدون فیلتر —</option>${opts}</select></div>`;
    }
    if (field.type === 'checkbox') {
      return `<label class="flex items-center gap-2 text-[12.5px] cursor-pointer" style="color:var(--text-main);">
                <input type="checkbox" id="${id}" ${val ? 'checked' : ''}> ${field.label}
              </label>`;
    }
    if (field.type === 'number') {
      return `<div>${label}<input type="number" id="${id}" class="input-pro" value="${escapeHtml(val)}" min="${field.min ?? ''}" max="${field.max ?? ''}"></div>`;
    }
    if (field.type === 'image') {
      return `<div>${label}<input type="text" id="${id}" class="input-pro" value="${escapeHtml(val)}" placeholder="آدرس تصویر (URL)" dir="ltr"></div>`;
    }
    if (field.type === 'product_multiselect') {
      window.__hbProductPick = window.__hbProductPick || {};
      window.__hbProductPick[field.key] = Array.isArray(value) ? value.filter((p) => p && p.id) : [];
      return `<div>${label}
        <div id="hb-pick-${field.key}-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px;"></div>
        <input type="text" id="hb-pick-${field.key}-input" class="input-pro" placeholder="جستجوی نام محصول و انتخاب از نتایج..." autocomplete="off">
        <div id="hb-pick-${field.key}-results" style="display:none;margin-top:6px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);overflow:hidden;"></div>
      </div>`;
    }
    // text (پیش‌فرض)
    return `<div>${label}<input type="text" id="${id}" class="input-pro" value="${escapeHtml(val)}" placeholder="${placeholder}"></div>`;
  }

  function initProductPicker(key) {
    const input = document.getElementById(`hb-pick-${key}-input`);
    const results = document.getElementById(`hb-pick-${key}-results`);
    if (!input || !results) return;

    renderPickChips(key);

    let timer = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      const q = input.value.trim();
      if (!q) { results.style.display = 'none'; results.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch(`${window.HB_ROUTES.productSearch}?q=${encodeURIComponent(q)}`, {
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.HB_CSRF },
        })
          .then((r) => r.json())
          .then((data) => {
            const picked = (window.__hbProductPick[key] || []).map((p) => String(p.id));
            const items = (data.products || []).filter((p) => !picked.includes(String(p.id)));
            if (!items.length) {
              results.innerHTML = '<div style="padding:10px 12px;font-size:11.5px;color:var(--text-soft);">نتیجه‌ای یافت نشد</div>';
            } else {
              results.innerHTML = items.map((p) => `
                <button type="button" onclick="HomeBuilder.pickProduct('${key}', ${p.id}, '${escapeHtml(p.name).replace(/'/g, '&#39;')}')"
                        style="display:block;width:100%;text-align:right;padding:9px 12px;font-size:12px;background:transparent;border:0;border-bottom:1px solid var(--border);color:var(--text-main);cursor:pointer;">
                  ${escapeHtml(p.name)}
                </button>`).join('');
            }
            results.style.display = '';
          })
          .catch(() => { results.style.display = 'none'; });
      }, 300);
    });
  }

  function renderPickChips(key) {
    const chips = document.getElementById(`hb-pick-${key}-chips`);
    if (!chips) return;
    const picked = window.__hbProductPick[key] || [];
    chips.innerHTML = picked.length
      ? picked.map((p) => `
          <span style="display:inline-flex;align-items:center;gap:6px;background:var(--primary-l);color:var(--primary);border:1px solid var(--primary-m);border-radius:99px;padding:4px 10px;font-size:11px;font-weight:700;">
            ${escapeHtml(p.name)}
            <button type="button" onclick="HomeBuilder.unpickProduct('${key}', ${p.id})" style="background:transparent;border:0;color:inherit;cursor:pointer;font-size:12px;line-height:1;">×</button>
          </span>`).join('')
      : '<span style="font-size:11px;color:var(--text-soft);">هنوز محصولی انتخاب نشده</span>';
  }

  function pickProduct(key, id, name) {
    window.__hbProductPick[key] = (window.__hbProductPick[key] || []).concat([{ id, name }]);
    renderPickChips(key);
    const input = document.getElementById(`hb-pick-${key}-input`);
    const results = document.getElementById(`hb-pick-${key}-results`);
    if (input) input.value = '';
    if (results) { results.style.display = 'none'; results.innerHTML = ''; }
    scheduleLivePreview();
  }

  function unpickProduct(key, id) {
    window.__hbProductPick[key] = (window.__hbProductPick[key] || []).filter((p) => String(p.id) !== String(id));
    renderPickChips(key);
    scheduleLivePreview();
  }

  function previewPayload(type, layout, includeFormValues) {
    const typeInfo = window.HB_TYPES[type];
    let settings = {};
    if (includeFormValues && state.selectedType === type) {
      settings = Object.assign({}, state.settingsValues || {}, collectSettingsValues(type));
    } else {
      (typeInfo.settings_fields || []).forEach((field) => {
        if (field.default !== undefined) settings[field.key] = field.default;
      });
    }

    return {
      type,
      layout,
      title_fa: includeFormValues ? (document.getElementById('hb-f-title_fa')?.value || null) : null,
      subtitle_fa: includeFormValues ? (document.getElementById('hb-f-subtitle_fa')?.value || null) : null,
      settings,
      responsive: includeFormValues ? collectResponsive() : {},
      device: state.previewDevice,
    };
  }

  function fetchPreview(payload) {
    return fetch(window.HB_ROUTES.preview, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': window.HB_CSRF,
        'Accept': 'text/html',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    }).then(async (response) => {
      if (!response.ok) throw new Error('ساخت پیش‌نمایش انجام نشد');
      return response.text();
    });
  }

  function loadLayoutPreview(containerId, type, layout) {
    const container = document.getElementById(containerId);
    const iframe = container?.querySelector('iframe');
    if (!container || !iframe) return;
    const cacheKey = `${type}:${layout}`;
    const previewPromise = state.layoutPreviewCache[cacheKey]
      ? Promise.resolve(state.layoutPreviewCache[cacheKey])
      : fetchPreview(previewPayload(type, layout, false)).then((html) => {
          state.layoutPreviewCache[cacheKey] = html;
          return html;
        });
    previewPromise
      .then((html) => {
        iframe.srcdoc = html;
        iframe.addEventListener('load', () => container.classList.add('is-ready'), { once: true });
      })
      .catch(() => {
        const loading = container.querySelector('.hb-preview-loading');
        if (loading) loading.textContent = 'پیش‌نمایش در دسترس نیست';
      });
  }

  function scheduleLivePreview(immediate = false) {
    clearTimeout(state.previewTimer);
    state.previewTimer = setTimeout(renderLivePreview, immediate ? 0 : 350);
  }

  function renderLivePreview() {
    if (!state.selectedType || !state.selectedLayout) return;
    const frame = document.getElementById('hb-live-preview-frame');
    const iframe = document.getElementById('hb-live-preview-iframe');
    if (!frame || !iframe || !document.getElementById('hb-edit-panel')?.classList.contains('open')) return;
    const requestId = ++state.previewRequestId;
    frame.classList.remove('is-ready');
    fetchPreview(previewPayload(state.selectedType, state.selectedLayout, true))
      .then((html) => {
        if (requestId !== state.previewRequestId) return;
        iframe.srcdoc = html;
        iframe.addEventListener('load', () => frame.classList.add('is-ready'), { once: true });
      })
      .catch(() => {
        if (requestId !== state.previewRequestId) return;
        const loading = frame.querySelector('.hb-preview-loading');
        if (loading) loading.textContent = 'خطا در ساخت پیش‌نمایش';
      });
  }

  function setPreviewDevice(device) {
    state.previewDevice = device;
    const frame = document.getElementById('hb-live-preview-frame');
    if (frame) frame.className = `hb-live-preview-frame is-${device}`;
    document.querySelectorAll('.hb-preview-devices button').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.device === device);
    });
    scheduleLivePreview(true);
  }

  function escapeHtml(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function collectSettingsValues(type) {
    const typeInfo = window.HB_TYPES[type];
    const out = {};
    typeInfo.settings_fields.forEach((field) => {
      if (field.type === 'product_multiselect') {
        out[field.key] = (window.__hbProductPick && window.__hbProductPick[field.key]) || [];
        return;
      }
      const el = document.getElementById(`hb-sf-${field.key}`);
      if (!el) return;
      if (field.type === 'checkbox') {
        out[field.key] = el.checked;
      } else if (field.type === 'number') {
        out[field.key] = el.value === '' ? null : Number(el.value);
      } else {
        out[field.key] = el.value;
      }
    });
    return out;
  }

  function collectResponsive() {
    return {
      desktop: document.getElementById('hb-f-resp-desktop').checked,
      tablet: document.getElementById('hb-f-resp-tablet').checked,
      mobile: document.getElementById('hb-f-resp-mobile').checked,
      mobile_layout: document.getElementById('hb-f-resp-mobile-layout').value || null,
    };
  }

  function saveSection(statusToSet) {
    const payload = {
      title_fa: document.getElementById('hb-f-title_fa').value || null,
      subtitle_fa: document.getElementById('hb-f-subtitle_fa').value || null,
      layout: state.selectedLayout,
      settings: Object.assign({}, state.settingsValues || {}, collectSettingsValues(state.selectedType)),
      responsive: collectResponsive(),
    };

    const afterSave = () => { closeEditDrawer(); window.location.reload(); };

    if (state.isNew) {
      payload.type = state.selectedType;
      fetchJson(window.HB_ROUTES.store, 'POST', payload)
        .then((res) => {
          const newId = res.section.id;
          if (statusToSet === 'published') {
            return fetchJson(window.HB_ROUTES.status.replace('__ID__', newId), 'PATCH', { status: 'published' });
          }
        })
        .then(afterSave)
        .catch((e) => alert(e.message));
    } else {
      payload.status = statusToSet;
      fetchJson(window.HB_ROUTES.update.replace('__ID__', state.currentId), 'PUT', payload)
        .then(afterSave)
        .catch((e) => alert(e.message));
    }
  }

  // ── اکشن‌های سریع هر ردیف ──────────────────────────────────────
  function duplicate(id) {
    fetchJson(window.HB_ROUTES.duplicate.replace('__ID__', id), 'POST')
      .then(() => window.location.reload())
      .catch((e) => alert(e.message));
  }

  function setStatus(id, status) {
    fetchJson(window.HB_ROUTES.status.replace('__ID__', id), 'PATCH', { status })
      .then(() => window.location.reload())
      .catch((e) => alert(e.message));
  }

  function destroy(id) {
    if (!confirm('این Section حذف شود؟ این عمل قابل بازگشت نیست.')) return;
    fetchJson(window.HB_ROUTES.destroy.replace('__ID__', id), 'DELETE')
      .then(() => window.location.reload())
      .catch((e) => alert(e.message));
  }

  // ── Drag & Drop عمودی (بدون هیچ وابستگی خارجی) ──────────────────
  function initDragDrop() {
    const list = document.getElementById('hb-section-list');
    if (!list) return;
    let draggedEl = null;

    list.addEventListener('dragstart', (e) => {
      const row = e.target.closest('.hb-row');
      if (!row) return;
      draggedEl = row;
      row.style.opacity = '.4';
    });

    list.addEventListener('dragend', (e) => {
      const row = e.target.closest('.hb-row');
      if (row) row.style.opacity = '';
      draggedEl = null;
      persistOrder();
    });

    list.addEventListener('dragover', (e) => {
      e.preventDefault();
      const overRow = e.target.closest('.hb-row');
      if (!overRow || !draggedEl || overRow === draggedEl) return;
      const rect = overRow.getBoundingClientRect();
      const isAfter = (e.clientY - rect.top) > rect.height / 2;
      list.insertBefore(draggedEl, isAfter ? overRow.nextSibling : overRow);
    });
  }

  function persistOrder() {
    const ids = [...document.querySelectorAll('#hb-section-list .hb-row')].map((r) => Number(r.dataset.id));
    if (!ids.length) return;
    fetchJson(window.HB_ROUTES.reorder, 'POST', { order: ids }).catch(() => {
      alert('خطا در ذخیره ترتیب جدید. صفحه دوباره بارگذاری می‌شود.');
      window.location.reload();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initDragDrop();
    ['hb-f-title_fa', 'hb-f-subtitle_fa', 'hb-f-resp-mobile-layout'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', () => scheduleLivePreview());
      el.addEventListener('change', () => scheduleLivePreview());
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeAddDrawer(); closeEditDrawer(); }
  });

  return {
    openAddDrawer, closeAddDrawer, selectLayout,
    openEditDrawer, closeEditDrawer, pickEditLayout, saveSection,
    duplicate, setStatus, destroy,
    pickProduct, unpickProduct, setPreviewDevice,
  };
})();
</script>
