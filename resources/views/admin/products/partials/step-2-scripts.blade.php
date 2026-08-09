<script>
/* ══════ انتخاب دو مرحله‌ای provider و مدل ══════ */
function toggleApiProviderMenu() {
  const menu = document.getElementById('api-provider-picker-menu');
  if (!menu) return;
  const open = menu.classList.toggle('hidden') === false;
  document.getElementById('api-provider-picker-button')?.setAttribute('aria-expanded', open ? 'true' : 'false');
  if (open) document.getElementById('primary-model-menu')?.classList.add('hidden');
}

function togglePrimaryModelMenu() {
  const menu = document.getElementById('primary-model-menu');
  if (!menu) return;
  const open = menu.classList.toggle('hidden') === false;
  document.getElementById('primary-model-picker-button')?.setAttribute('aria-expanded', open ? 'true' : 'false');
  if (open) document.getElementById('api-provider-picker-menu')?.classList.add('hidden');
}

function updateApiProviderPicker(provider) {
  const label = document.getElementById('api-provider-picker-label');
  const choice = document.querySelector('[data-provider-choice="' + provider + '"]');
  if (label) label.textContent = provider === 'all' ? 'همه پرووایدرها' : (choice?.querySelector('span')?.textContent || provider);
  document.querySelectorAll('[data-provider-choice]').forEach(function (item) {
    item.classList.toggle('is-selected', item.dataset.providerChoice === provider);
  });
}

function primaryModelFilterValues() {
  return {
    provider: __currentApiProvider || 'all',
    task: document.getElementById('primary-model-task-filter')?.value || 'all',
    useCase: document.getElementById('primary-model-use-case-filter')?.value || 'all',
  };
}

function modelMatchesPrimaryFilters(provider, task, useCase, modelProvider, modelTask, modelUseCases) {
  const providerOk = provider === 'all' || provider === modelProvider;
  const taskOk = task === 'all' || task === modelTask;
  const useCases = String(modelUseCases || '').split(',').filter(Boolean);
  const useCaseOk = useCase === 'all' || useCases.includes(useCase);
  return providerOk && taskOk && useCaseOk;
}

function renderPrimaryModelPicker() {
  const filters = primaryModelFilterValues();
  document.querySelectorAll('#primary-model-options .model-picker-model-row').forEach(function (row) {
    const visible = modelMatchesPrimaryFilters(filters.provider, filters.task, filters.useCase, row.dataset.modelProvider, row.dataset.modelTask, row.dataset.modelUseCases);
    row.classList.toggle('hidden', !visible);
  });
  const primarySel = document.getElementById('primary-model-select');
  if (!primarySel) return;
  let needsReset = false;
  Array.from(primarySel.options).forEach(function (opt) {
    if (!opt.value) return;
    const visible = modelMatchesPrimaryFilters(filters.provider, filters.task, filters.useCase, opt.getAttribute('data-api-provider') || 'openrouter', opt.getAttribute('data-model-task') || '', opt.getAttribute('data-model-use-cases') || '');
    opt.hidden = !visible;
    opt.disabled = !visible;
    if (!visible && opt.selected) needsReset = true;
  });
  if (needsReset) {
    primarySel.value = '';
    onPrimaryModelChange();
  }
}

function onPrimaryModelFilterChange() {
  renderPrimaryModelPicker();
}

function selectApiProvider(provider) {
  onApiProviderChange(provider);
  document.getElementById('api-provider-picker-menu')?.classList.add('hidden');
  document.getElementById('api-provider-picker-button')?.setAttribute('aria-expanded', 'false');
}

function selectPrimaryModelFromPicker(row) {
  const select = document.getElementById('primary-model-select');
  if (!select || !row) return;
  const provider = row.dataset.modelProvider || '';
  const modelId = row.dataset.modelId || '';
  const option = Array.from(select.options).find(function (item) {
    return item.value === modelId && item.getAttribute('data-api-provider') === provider;
  });
  if (!option) return;
  onApiProviderChange(provider);
  select.value = option.value;
  select.dispatchEvent(new Event('change', {bubbles:true}));
  document.getElementById('primary-model-menu')?.classList.add('hidden');
  document.getElementById('primary-model-picker-button')?.setAttribute('aria-expanded', 'false');
}

/* ══════ Card ۱ — کارت اطلاعات مدل اصلی ══════ */
function onPrimaryModelChange() {
  const sel = document.getElementById('primary-model-select');
  const card = document.getElementById('model-info-card');
  if (!sel || !card) return;

  if (!window.__recommendedSelectionInProgress) clearRecommendedModelSelection();

  const opt = sel.options[sel.selectedIndex];
  if (!opt || !sel.value) { card.classList.add('hidden'); return; }

  // شناسه برخی مدل‌ها بین Liara و OpenRouter مشترک است. Provider باید همیشه
  // از خود option انتخاب‌شده خوانده شود تا جفت مدل/سرویس از هم جدا نشود.
  const selectedProvider = opt.getAttribute('data-api-provider') || '';
  const providerInput = document.getElementById('ai-provider-input');
  if (providerInput && selectedProvider) {
    providerInput.value = selectedProvider;
    __currentApiProvider = selectedProvider;
    updateApiProviderPicker(selectedProvider);
    renderPrimaryModelPicker();
  }

  const pickerLabel = document.getElementById('primary-model-picker-label');
  if (pickerLabel) pickerLabel.textContent = opt.getAttribute('data-name') || opt.value || '— انتخاب مدل اصلی —';
  document.querySelectorAll('#primary-model-options .model-picker-model-row').forEach(function (row) {
    row.classList.toggle('is-selected', row.dataset.modelId === opt.value && row.dataset.modelProvider === selectedProvider);
  });

  document.getElementById('model-info-name').textContent = opt.getAttribute('data-name') || '—';
  document.getElementById('model-info-provider').textContent = opt.getAttribute('data-provider') || '—';
  const media = opt.getAttribute('data-output-modality') || 'image';
  const mediaMeta = {
    image: {icon: 'fa-image', label: 'عکس'},
    video: {icon: 'fa-video', label: 'ویدیو'},
    audio: {icon: 'fa-volume-high', label: 'صوت'},
    text: {icon: 'fa-font', label: 'متن'},
  }[media] || {icon: 'fa-image', label: 'عکس'};
  const mediaEl = document.getElementById('model-info-media');
  if (mediaEl) mediaEl.innerHTML = '<i class="fa-solid ' + mediaMeta.icon + ' ml-1"></i>' + mediaMeta.label;
  const taskEl = document.getElementById('model-info-task');
  if (taskEl) taskEl.textContent = opt.getAttribute('data-task-label') || '—';
  const useCaseEl = document.getElementById('model-info-use-case');
  if (useCaseEl) useCaseEl.textContent = 'بهترین برای: ' + (opt.getAttribute('data-use-case-label') || 'کاربری عمومی');
  const capabilitiesEl = document.getElementById('model-info-capabilities');
  if (capabilitiesEl) capabilitiesEl.textContent = opt.getAttribute('data-capabilities') || 'قابلیت ثبت نشده';
  card.classList.remove('hidden');
}
function clearRecommendedModelSelection() {
  document.querySelectorAll('.recommended-model-card').forEach(function(card){
    card.classList.remove('border-[var(--green)]','bg-[var(--green)]/5');
    const check = card.querySelector('.recommended-model-check'); if (check) check.style.display = 'none';
  });
}
function selectRecommendedModel(modelId) {
  const select = document.getElementById('primary-model-select'); if (!select) return;
  const automaticFallbacks = {'openai/gpt-image-2':'openai/gpt-image-1','openai/gpt-image-1':'openai/gpt-image-1-mini','openai/gpt-image-1-mini':'openai/gpt-image-1'};
  window.__recommendedSelectionInProgress = true;
  setFallbackSelection(select, modelId, 'openrouter');
  select.dispatchEvent(new Event('change', {bubbles:true}));
  if (typeof refreshSearchable === 'function') refreshSearchable(select);
  window.__recommendedSelectionInProgress = false;
  clearRecommendedModelSelection();
  const activeCard = document.querySelector('.recommended-model-card[data-recommended-model="' + modelId + '"]');
  activeCard?.classList.add('border-[var(--green)]','bg-[var(--green)]/5'); const activeCheck = activeCard?.querySelector('.recommended-model-check'); if (activeCheck) activeCheck.style.display = 'inline-block';
  const fallback = document.querySelector('.fallback-select-item');
  const primaryProvider = select.options[select.selectedIndex]?.getAttribute('data-api-provider') || 'openrouter';
  if (fallback && automaticFallbacks[modelId]) {
    setFallbackSelection(fallback, automaticFallbacks[modelId], primaryProvider);
    if (typeof refreshSearchable === 'function') refreshSearchable(fallback);
  }
}

function syncFallbackProvider(select) {
  const row = select?.closest('.fallback-row');
  const hidden = row?.querySelector('.fallback-provider-input');
  const option = select?.options[select.selectedIndex];
  if (hidden) hidden.value = option?.getAttribute('data-api-provider') || '';
}

function setFallbackSelection(select, modelId, provider) {
  if (!select) return;
  const options = Array.from(select.options);
  const index = options.findIndex(option => option.value === modelId && (!provider || option.getAttribute('data-api-provider') === provider) && !option.disabled);
  const fallbackIndex = index >= 0 ? index : options.findIndex(option => option.value === modelId && !option.disabled);
  if (fallbackIndex < 0) return;
  select.selectedIndex = fallbackIndex;
  syncFallbackProvider(select);
  select.dispatchEvent(new Event('change', {bubbles:true}));
}

/* ══════ فیلتر Provider — همه‌ی سرویس‌های فعال ══════ */
function onApiProviderChange(provider) {
  __currentApiProvider = provider;
  const providerInput = document.getElementById('ai-provider-input');
  // «همه» فقط فیلتر نمایشی است و نباید به‌عنوان provider واقعی فرم ارسال شود.
  if (providerInput && provider !== 'all') providerInput.value = provider;

  // آپدیت استایل دکمه‌های تاگل
  document.querySelectorAll('.api-provider-btn').forEach(btn => btn.classList.remove('active-provider'));
  const activeBtn = document.getElementById('lbl-api-' + provider);
  if (activeBtn) activeBtn.classList.add('active-provider');
  updateApiProviderPicker(provider);
  renderPrimaryModelPicker();
  document.getElementById('recommended-openrouter-models')?.classList.toggle('hidden', provider !== 'openrouter');

  // مدل‌های جایگزین مستقل از سرویس مدل اصلی هستند؛ مدیر می‌تواند برای
  // failover یک مدل لیارا و یک مدل OpenRouter را هم‌زمان انتخاب کند.
}

/* (اجرای اولیه در DOMContentLoaded موجود در انتهای فایل انجام می‌شود) */

/* ══════ مدیریت و چینش مدل‌های جایگزین (Fallback Models) ══════ */
function renumberFallbacks() {
  document.querySelectorAll('#fallback-list .fb-priority').forEach((el, index) => {
    el.textContent = 'اولویت ' + (index + 2);
  });
}

function wireFallbackDrag(row) {
  row.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', '');
    row.classList.add('opacity-50');
    window.draggedRow = row;
  });
  row.addEventListener('dragend', () => {
    row.classList.remove('opacity-50');
  });
  row.addEventListener('dragover', (e) => { e.preventDefault(); });
  row.addEventListener('drop', (e) => {
    e.preventDefault();
    if (window.draggedRow && window.draggedRow !== row) {
      const list = document.getElementById('fallback-list');
      const all = Array.from(list.children);
      if (all.indexOf(window.draggedRow) < all.indexOf(row)) {
        row.after(window.draggedRow);
      } else {
        row.before(window.draggedRow);
      }
      renumberFallbacks();
    }
  });
}

function moveFallbackRow(btn, dir) {
  const row = btn.closest('.fallback-row');
  if (!row) return;
  if (dir === 'up' && row.previousElementSibling) {
    row.previousElementSibling.before(row);
  } else if (dir === 'down' && row.nextElementSibling) {
    row.nextElementSibling.after(row);
  }
  renumberFallbacks();
}

function addFallback() {
  const list = document.getElementById('fallback-list');
  const selectHtml = document.getElementById('primary-model-select')?.innerHTML || '';
  const count = list.children.length;
  
  const div = document.createElement('div');
  div.className = 'fallback-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-center gap-3';
  div.draggable = true;
  div.innerHTML = `
    <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 fb-drag-handle hidden md:block" title="برای تغییر اولویت بکشید"></i>
    <div class="flex md:hidden flex-col gap-0.5 shrink-0">
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
    </div>
    <span class="fb-priority text-[10px] font-mono text-[var(--text3)] w-14 shrink-0">اولویت ${count + 2}</span>
    <select name="fallback_models[]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] flex-1 fallback-select-item">
      ${selectHtml}
    </select>
    <input type="hidden" name="fallback_providers[]" class="fallback-provider-input" value="">
    <label class="relative w-8 h-[18px] shrink-0 block cursor-pointer">
      <input type="checkbox" class="sr-only peer" checked>
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
    </label>
    <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg shrink-0" onclick="this.closest('.fallback-row').remove(); renumberFallbacks();">حذف</button>
  `;
  
  // پاک کردن آپشن پیشفرض شبیه‌سازی شده از سلکتور کپی شده
  const firstOpt = div.querySelector('select option[value=""]');
  if (firstOpt) firstOpt.remove();

  list.appendChild(div);
  wireFallbackDrag(div);

  const newSel = div.querySelector('.fallback-select-item');
  if (newSel) {
    Array.from(newSel.options).forEach(opt => {
      if (!opt.value) return;
      opt.hidden = false;
      opt.disabled = false;
    });
    if (newSel.options.length) newSel.selectedIndex = 0;
    syncFallbackProvider(newSel);
    newSel.addEventListener('change', () => syncFallbackProvider(newSel));
  }
}

/* ══════ تست پرامپت — فراخوانی واقعی Backend ══════ */
function testPromptNow() {
  var prompt  = document.getElementById('prompt-template').value.trim();
  var modelId = document.getElementById('primary-model-select').value;

  if (!prompt)   { alert('ابتدا پرامپت را بنویسید.'); return; }
  if (!modelId)  { alert('ابتدا مدل اصلی را انتخاب کنید.'); return; }

  document.getElementById('test-result-box').classList.add('hidden');
  document.getElementById('test-error-box').classList.add('hidden');

  var btn  = document.getElementById('btn-test-prompt');
  var text = document.getElementById('btn-test-text');
  btn.disabled = true;
  text.textContent = 'در حال تولید...';

  var csrfToken = document.querySelector('input[name="_token"]')?.value || '';
  var startedAt = Date.now();

  fetch('{{ route('admin.ai-models.test-prompt') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      prompt:   prompt,
      model_id: modelId,
    }),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false;
    text.textContent = 'اجرای تست';

    if (data.success) {
      document.getElementById('test-result-img').src = data.image_url;
      document.getElementById('test-result-model').textContent = data.model;
      document.getElementById('test-result-download').href = data.image_url;
      var lastRunEl = document.getElementById('stat-last-run');
      if (lastRunEl) lastRunEl.textContent = 'همین الان';
      var durEl = document.getElementById('stat-duration');
      if (durEl) durEl.textContent = Math.max(1, Math.round((Date.now() - startedAt) / 1000)) + ' ثانیه';
      document.getElementById('test-result-box').classList.remove('hidden');
    } else {
      document.getElementById('test-error-text').textContent = data.message || 'خطای ناشناخته';
      document.getElementById('test-error-box').classList.remove('hidden');
    }
  })
  .catch(function(err) {
    btn.disabled = false;
    text.textContent = 'اجرای تست';
    document.getElementById('test-error-text').textContent = 'خطا در ارتباط با سرور: ' + err.message;
    document.getElementById('test-error-box').classList.remove('hidden');
  });
}

/* ══════ Prompt Editor قابلیت‌های پیشرفته ══════ */
function updatePromptLineNumbers() {
  const ta = document.getElementById('prompt-template');
  const gutter = document.getElementById('prompt-line-numbers');
  if(!ta || !gutter) return;
  const lines = ta.value.split('\n').length;
  let out = '';
  for (let i = 1; i <= lines; i++) out += i + '\n';
  gutter.textContent = out;
}
function syncPromptScroll() {
  const ta = document.getElementById('prompt-template');
  const gutter = document.getElementById('prompt-line-numbers');
  if(ta && gutter) gutter.scrollTop = ta.scrollTop;
}
function autoResizePrompt() {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  ta.style.height = 'auto';
  ta.style.height = Math.max(150, ta.scrollHeight) + 'px';
}
function onPromptInput() {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  updatePromptLineNumbers();
  autoResizePrompt();
  syncPromptScroll();
  document.getElementById('prompt-char-count').textContent = ta.value.length + ' کاراکتر';
  document.getElementById('prompt-token-estimate').textContent = '~' + Math.ceil(ta.value.length / 4) + ' توکن (تخمینی)';
  const matches = ta.value.match(/\{[a-zA-Z0-9_]+\}/g) || [];
  document.getElementById('prompt-vars-detected').textContent = matches.length + ' متغیر شناسایی شد';
}
function copyPromptText() {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  ta.select();
  navigator.clipboard?.writeText(ta.value).catch(() => document.execCommand('copy'));
}
function clearPromptText() {
  if (!confirm('متن پرامپت پاک شود؟')) return;
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  ta.value = '';
  onPromptInput();
  ta.focus();
}
function toggleExpandEditor() {
  const wrap = document.getElementById('prompt-editor-card');
  const btn = document.getElementById('expand-editor-btn');
  if(!wrap || !btn) return;
  const expanded = wrap.classList.toggle('prompt-fullscreen');
  if (expanded) {
    wrap.classList.add('fixed','inset-4','z-50','bg-[var(--s2)]','p-4','rounded-xl','border','border-[var(--accent)]','shadow-2xl','overflow-y-auto');
    btn.innerHTML = '<i class="fa-solid fa-compress ml-1"></i>بستن تمام‌صفحه';
  } else {
    wrap.classList.remove('fixed','inset-4','z-50','bg-[var(--s2)]','p-4','rounded-xl','border','border-[var(--accent)]','shadow-2xl','overflow-y-auto');
    btn.innerHTML = '<i class="fa-solid fa-expand ml-1"></i>Expand Editor';
  }
}
function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  if(!ta) return;
  const start = ta.selectionStart;
  const end = ta.selectionEnd;
  ta.value = ta.value.substring(0, start) + v + ta.value.substring(end);
  ta.selectionStart = ta.selectionEnd = start + v.length;
  onPromptInput();
  ta.focus();
}

function filterVarChips(term) {
  term = term.toLowerCase();
  document.querySelectorAll('#var-chips .var-chip').forEach(chip => {
    chip.classList.toggle('hidden', term && chip.textContent.toLowerCase().indexOf(term) === -1);
  });
}
function filterVarCategory(cat) {
  document.querySelectorAll('.var-cat-btn').forEach(btn => {
    const active = btn.dataset.cat === cat;
    btn.classList.toggle('border-[var(--accent)]', active);
    btn.classList.toggle('bg-[var(--accent)]/10', active);
    btn.classList.toggle('text-[var(--accent)]', active);
    btn.classList.toggle('border-[var(--b1)]', !active);
    btn.classList.toggle('text-[var(--text3)]', !active);
  });
  document.querySelectorAll('#var-chips .var-chip').forEach(chip => {
    chip.classList.toggle('hidden', cat !== 'all' && chip.dataset.cat !== cat);
  });
}

/* ══════ نسخه‌بندی و تاریخچه پرامپت (بند ۳۰) — فقط UI، درون‌حافظه‌ای ══════ */
var __promptVersions = [];
function __escHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function savePromptVersion() {
  var ta = document.getElementById('prompt-template');
  if (!ta || !ta.value.trim()) { alert('ابتدا پرامپت را بنویسید.'); return; }
  var now = new Date();
  var stamp = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
  __promptVersions.unshift({ time: stamp, text: ta.value });
  renderPromptVersions();
}
function renderPromptVersions() {
  var list = document.getElementById('prompt-versions-list');
  var empty = document.getElementById('prompt-versions-empty');
  if (!list) return;
  list.innerHTML = '';
  if (empty) empty.classList.toggle('hidden', __promptVersions.length > 0);
  __promptVersions.forEach(function (v, i) {
    var row = document.createElement('div');
    row.className = 'flex items-center gap-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg px-2.5 py-1.5';
    var preview = __escHtml(v.text.replace(/\s+/g, ' ').slice(0, 40));
    row.innerHTML =
      '<i class="fa-solid fa-code-branch text-[10px] text-[var(--text3)] shrink-0"></i>' +
      '<span class="text-[10px] text-[var(--text3)] font-mono shrink-0">' + v.time + '</span>' +
      '<span class="text-[11px] text-[var(--text2)] flex-1 truncate ltr text-left">' + preview + '</span>' +
      '<button type="button" class="text-[10.5px] text-[var(--accent)] shrink-0" onclick="restorePromptVersion(' + i + ')">بازگردانی</button>' +
      '<button type="button" class="text-[10.5px] text-[var(--red)] shrink-0" onclick="deletePromptVersion(' + i + ')"><i class="fa-solid fa-xmark"></i></button>';
    list.appendChild(row);
  });
}
function restorePromptVersion(i) {
  var v = __promptVersions[i]; if (!v) return;
  var ta = document.getElementById('prompt-template');
  if (ta) { ta.value = v.text; if (typeof onPromptInput === 'function') onPromptInput(); }
}
function deletePromptVersion(i) { __promptVersions.splice(i, 1); renderPromptVersions(); }

document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('click', (event) => {
    if (!event.target.closest('#api-provider-picker-shell')) {
      document.getElementById('api-provider-picker-menu')?.classList.add('hidden');
      document.getElementById('api-provider-picker-button')?.setAttribute('aria-expanded', 'false');
    }
    if (!event.target.closest('#primary-model-picker-shell')) {
      document.getElementById('primary-model-menu')?.classList.add('hidden');
      document.getElementById('primary-model-picker-button')?.setAttribute('aria-expanded', 'false');
    }
  });
  clearRecommendedModelSelection();
  onPrimaryModelChange();
  clearRecommendedModelSelection();
  document.querySelectorAll('#fallback-list .fallback-row').forEach(row => wireFallbackDrag(row));
  document.querySelectorAll('#fallback-list .fallback-select-item').forEach(select => {
    select.addEventListener('change', () => syncFallbackProvider(select));
    syncFallbackProvider(select);
  });
  onPromptInput();
  // اجرای اولیه فیلتر provider
  onApiProviderChange(typeof __currentApiProvider !== 'undefined' ? __currentApiProvider : 'openrouter');
});
</script>
