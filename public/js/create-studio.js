(function () {
  'use strict';

  const root = document.querySelector('[data-create-studio]');
  if (!root) return;

  let config = {};
  try { config = JSON.parse(root.querySelector('[data-studio-config]')?.textContent || '{}'); } catch (_) {}
  const faDigits = (value) => String(value).replace(/[0-9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
  const modeTabs = [...root.querySelectorAll('[data-studio-mode]')];
  const form = root.querySelector('[data-studio-form]');
  const prompt = root.querySelector('[data-studio-prompt]');
  const promptCount = root.querySelector('[data-studio-count]');
  const submit = root.querySelector('[data-studio-submit]');
  const submitLabel = root.querySelector('[data-studio-submit-label]');
  const cost = root.querySelector('[data-studio-cost]');
  const videoContent = root.querySelector('[data-studio-video-content]');
  const imageContent = root.querySelector('[data-studio-image-content]');
  const stageVideo = root.querySelector('[data-studio-stage-video]');
  const progress = root.querySelector('[data-studio-progress]');
  const progressTitle = root.querySelector('[data-studio-progress-title]');
  const progressText = root.querySelector('[data-studio-progress-text]');
  const progressBar = root.querySelector('[data-studio-progress-bar]');
  const result = root.querySelector('[data-studio-result]');
  const outputImage = root.querySelector('[data-studio-output-image]');
  const outputVideo = root.querySelector('[data-studio-output-video]');
  const videoPlay = root.querySelector('[data-studio-video-play]');
  const errorBox = root.querySelector('[data-studio-error]');
  const errorText = errorBox?.querySelector('span');
  const uploadZone = root.querySelector('[data-studio-upload-zone]');
  const uploadInput = root.querySelector('[data-studio-upload-input]');
  const uploadFile = root.querySelector('[data-studio-upload-file]');
  const negativeInput = root.querySelector('[data-studio-negative-input]');
  const outputCountInput = root.querySelector('[data-studio-output-count]');
  const stateKey = 'vatan-create-studio-state';
  let currentMode = 'image';
  let activeConfig = null;
  let selectedValues = {};
  let pollTimer = null;
  let progressTimer = null;
  let quoteTimer = null;
  let quoteSequence = 0;

  const ratioNames = {'1:1': 'مربع', '16:9': 'افقی', '9:16': 'عمودی', '4:5': 'پرتره', '3:4': 'عمودی', '4:3': 'افقی', '2:3': 'عمودی', '3:2': 'افقی', '21:9': 'عریض'};
  const formatRatio = (value) => String(value).replace(/[0-9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
  const formatNumber = (value) => Number(value || 0).toLocaleString('fa-IR');
  const qualityLabel = (value) => {
    const normalized = String(value).trim().toLowerCase();
    if (normalized === '4k') return '۴٬۰۹۶ پیکسل';
    return `${formatNumber(normalizeDigits(value))} پیکسل`;
  };

  function hideError() { if (errorBox) errorBox.hidden = true; }
  function showError(message) { if (errorText) errorText.textContent = message; if (errorBox) errorBox.hidden = false; }

  function normalizeDigits(value) {
    return String(value ?? '')
      .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
      .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)))
      .replace(/[^0-9]/g, '');
  }

  function saveStudioState() {
    try {
      sessionStorage.setItem(stateKey, JSON.stringify({
        mode: currentMode,
        prompt: prompt.value,
        negative: negativeInput?.value || '',
        project: root.querySelector('[data-studio-project]')?.value || '',
        selectedValues,
        outputCount: selectedValues.count || outputCountInput?.value || '1',
      }));
    } catch (_) {}
  }

  function readStudioState() {
    try { return JSON.parse(sessionStorage.getItem(stateKey) || 'null'); } catch (_) { return null; }
  }

  function clearStudioState() { try { sessionStorage.removeItem(stateKey); } catch (_) {} }

  function valueForField(fieldId) {
    return activeConfig?.defaults?.[fieldId] ?? '';
  }

  function setHiddenField(name, value) {
    let input = form.querySelector(`[data-studio-hidden="${CSS.escape(name)}"]`);
    if (!input) {
      input = document.createElement('input'); input.type = 'hidden'; input.dataset.studioHidden = name; form.appendChild(input);
    }
    input.name = name; input.value = value ?? '';
  }

  function fieldOption(fieldId, fallback) {
    const field = (activeConfig?.fields || []).find((item) => item.id === fieldId);
    const options = field?.options || [];
    return options[0]?.value ?? fallback;
  }

  function fieldForKey(key) {
    if (!String(key).startsWith('field:')) return null;
    const fieldId = String(key).slice(6);
    return (activeConfig?.fields || []).find((field) => field.id === fieldId) || null;
  }

  function englishModelName(label, value) {
    const text = String(label || value || '').trim();
    const parenthesized = text.match(/\(([^()]*[A-Za-z][^()]*)\)\s*$/);
    if (parenthesized) return parenthesized[1].trim();
    return /[A-Za-z]/.test(text) ? text : String(value || text || 'مدل هوش مصنوعی');
  }

  function optionsFor(key) {
    if (!activeConfig) return [];
    if (key === 'model') return (activeConfig.model_options || [{value: activeConfig.model, label: activeConfig.model, meta: activeConfig.name}]).map((item) => ({
      ...item,
      value: item.value,
      label: item.label || item.value,
      meta: item.meta || '',
      provider: item.provider || '',
    }));
    if (key === 'count') return Array.from({length: 6}, (_, index) => {
      const value = String(index + 1);
      return {value, label: `${formatNumber(value)} عدد`, meta: ''};
    });
    const dynamicField = fieldForKey(key);
    if (dynamicField) return (dynamicField.options || []).map((item) => ({value: item.value, label: item.label, meta: item.meta || ''}));
    if (currentMode === 'video') {
      const video = activeConfig.video || {};
      if (key === 'duration') return Array.from({length: 15}, (_, index) => index + 1).map((value) => ({value: String(value), label: `${formatNumber(value)} ثانیه`}));
      if (key === 'ratio') return (video.aspect_ratios || [])
        .filter((value) => String(value) !== '4:3')
        .map((value) => ({value, label: formatRatio(value)}));
      if (key === 'quality') return (video.resolutions || []).map((value) => ({value, label: qualityLabel(value), meta: value === video.default_resolution ? 'پیشنهادی' : ''}));
      if (key === 'motion') return [{value: '', label: 'بر اساس پرامپت', meta: 'تنظیم خودکار'}].concat((video.motion_presets || []).map((item) => ({value: item.key, label: item.label, meta: item.description})));
    } else {
      const selectedModel = (activeConfig.model_options || []).find((item) => String(item.value) === String(selectedValues.model));
      if (key === 'ratio') {
        const ratios = (activeConfig.output_aspect_ratios || []).filter((value) => {
          const supported = (selectedModel?.supported_aspect_ratios || []).map((item) => String(item).toLowerCase());
          return supported.length === 0 || supported.includes(String(value).toLowerCase());
        });
        return ratios.map((value) => ({value, label: formatRatio(value)}));
      }
      if (key === 'quality') {
        const qualities = (activeConfig.output_resolutions || []).filter((value) => {
          const supported = (selectedModel?.supported_resolutions || []).map((item) => String(item).toLowerCase());
          return supported.length === 0 || supported.includes(String(value).toLowerCase());
        });
        return qualities.map((value) => ({value, label: qualityLabel(value), meta: value === activeConfig.default_output_resolution ? 'پیشنهادی' : ''}));
      }
      if (key === 'style') {
        const styleField = (activeConfig.fields || []).find((field) => field.type === 'select' && field.id === 'style') || (activeConfig.fields || []).find((field) => field.type === 'select');
        return (styleField?.options || []).map((item) => ({value: item.value, label: item.label, meta: 'سبک محصول'}));
      }
    }
    return [];
  }

  function defaultValueFor(key, options) {
    if (key === 'model' || key === 'ratio') return '';
    if (key === 'count') return '1';
    if (key === 'duration') return String(activeConfig.video?.default_duration ?? options[0]?.value ?? '');
    if (key === 'ratio') return currentMode === 'video' ? activeConfig.video?.default_aspect_ratio : activeConfig.default_output_aspect_ratio;
    if (key === 'quality') return currentMode === 'video' ? activeConfig.video?.default_resolution : activeConfig.default_output_resolution;
    if (key === 'motion') return '';
    if (key === 'style') return fieldOption('style', options[0]?.value ?? '');
    const dynamicField = fieldForKey(key);
    if (dynamicField) return dynamicField.value ?? options[0]?.value ?? '';
    return options[0]?.value ?? '';
  }

  function renderDynamicFields() {
    const container = root.querySelector('[data-studio-dynamic-fields]');
    if (!container) return;
    container.innerHTML = '';
    const supportedTypes = ['select', 'radio', 'button_group', 'style_preset'];
    (activeConfig?.fields || []).filter((field) => supportedTypes.includes(field.type) && !['style', 'background', 'duration', 'theme', 'action', 'actions'].includes(String(field.id).toLowerCase()) && !['اکشن', 'actions', 'action'].includes(String(field.label || '').trim().toLowerCase()) && !field.hidden).forEach((field) => {
      const row = document.createElement('div');
      row.className = 'create-studio-setting-row';
      row.dataset.studioDynamicField = field.id;
      row.innerHTML = `<span><i class="fa-solid fa-sliders"></i><b>${field.label || field.id}</b></span><div class="create-studio-select create-studio-select-menu-field" data-studio-select="field:${field.id}"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>انتخاب کنید</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--cards" data-select-menu role="listbox"></div><input type="hidden" data-select-input></div>`;
      container.appendChild(row);
    });
  }

  let activePortalSelect = null;
  let portalRepositionHandler = null;
  let portalScrollParents = [];

  function selectMenu(select) {
    return select._studioMenu || select.querySelector('[data-select-menu]');
  }

  function restoreSelectMenu(select) {
    const menu = selectMenu(select);
    if (!menu || !menu.classList.contains('create-studio-select-menu--portal')) return;
    const input = select.querySelector('[data-select-input]');
    if (input) input.before(menu); else select.appendChild(menu);
    menu.classList.remove('create-studio-select-menu--portal');
    menu.classList.remove('create-studio-select-menu--anchored');
    menu.removeAttribute('data-studio-menu-key');
    menu.removeAttribute('data-studio-menu-mode');
    ['width', 'min-width', 'left', 'top', 'right', 'bottom', 'position', 'z-index'].forEach((property) => menu.style.removeProperty(property));
    if (activePortalSelect === select) {
      if (portalRepositionHandler) {
        window.removeEventListener('resize', portalRepositionHandler);
        window.removeEventListener('scroll', portalRepositionHandler, true);
        portalScrollParents.forEach((parent) => parent.removeEventListener('scroll', portalRepositionHandler));
      }
      portalScrollParents = [];
      activePortalSelect = null;
      portalRepositionHandler = null;
    }
  }

  function scrollableAncestors(element) {
    const parents = [];
    let parent = element.parentElement;
    while (parent && parent !== document.body) {
      const styles = window.getComputedStyle(parent);
      if (/(auto|scroll|overlay)/.test(`${styles.overflow} ${styles.overflowX} ${styles.overflowY}`)) parents.push(parent);
      parent = parent.parentElement;
    }
    return parents;
  }

  function positionSelectMenu(select) {
    const menu = selectMenu(select);
    const toggle = select.querySelector('[data-select-toggle]');
    if (!menu || !toggle || !menu.classList.contains('create-studio-select-menu--portal')) return;
    const trigger = toggle.getBoundingClientRect();
    const key = select.dataset.studioSelect;
    const configuredWidth = key === 'model' ? (currentMode === 'video' ? Math.min(440, trigger.width) : 275) : 250;
    const width = Math.min(configuredWidth, Math.max(180, window.innerWidth - 24));
    const menuHeight = menu.getBoundingClientRect().height;
    const alignLeftWithTrigger = (currentMode === 'image' && (key === 'quality' || key === 'count')) || (currentMode === 'video' && (key === 'ratio' || key === 'motion'));
    const preferredLeft = alignLeftWithTrigger ? trigger.left : trigger.right - width;
    const left = Math.max(12, Math.min(preferredLeft, window.innerWidth - width - 12));
    const above = trigger.top - menuHeight - 8;
    const top = above >= 12 ? above : Math.min(window.innerHeight - menuHeight - 12, trigger.bottom + 8);
    const anchoredToTrigger = menu.classList.contains('create-studio-select-menu--anchored');
    const pageX = window.scrollX || document.documentElement.scrollLeft || 0;
    const pageY = window.scrollY || document.documentElement.scrollTop || 0;
    menu.style.width = `${width}px`;
    menu.style.minWidth = `${width}px`;
    menu.style.setProperty('left', `${Math.max(12, left) + (anchoredToTrigger ? pageX : 0)}px`, 'important');
    menu.style.setProperty('top', `${Math.max(12, top) + (anchoredToTrigger ? pageY : 0)}px`, 'important');
  }

  function closeSelect(select) {
    restoreSelectMenu(select);
    select.querySelector('[data-select-toggle]')?.setAttribute('aria-expanded', 'false');
    select.classList.remove('is-open');
  }

  function openSelect(select) {
    root.querySelectorAll('[data-studio-select].is-open').forEach((item) => { if (item !== select) closeSelect(item); });
    const menu = selectMenu(select);
    if (!menu) return;
    select._studioMenu = menu;
    select.classList.add('is-open');
    menu.dataset.studioMenuKey = select.dataset.studioSelect;
    menu.dataset.studioMenuMode = currentMode;
    menu.classList.add('create-studio-select-menu--portal');
    const anchoredToTrigger = window.matchMedia('(max-width: 700px)').matches;
    menu.classList.toggle('create-studio-select-menu--anchored', anchoredToTrigger);
    document.body.appendChild(menu);
    positionSelectMenu(select);
    activePortalSelect = select;
    portalRepositionHandler = () => positionSelectMenu(select);
    window.addEventListener('resize', portalRepositionHandler);
    if (anchoredToTrigger) {
      portalScrollParents = scrollableAncestors(select.querySelector('[data-select-toggle]'));
      portalScrollParents.forEach((parent) => parent.addEventListener('scroll', portalRepositionHandler, {passive: true}));
    } else {
      window.addEventListener('scroll', portalRepositionHandler, true);
    }
  }

  function renderSelectOptions(select, options, selected) {
    const menu = selectMenu(select);
    if (!menu) return;
    menu.innerHTML = '';
    if (select.dataset.studioSelect === 'duration') {
      const value = Number(selected || options[0]?.value || 1);
      const timeline = document.createElement('div');
      timeline.className = 'create-studio-duration-track';
      timeline.innerHTML = `<div class="create-studio-duration-track-head"><strong>زمان ویدیو</strong><b data-duration-value>${formatNumber(value)} ثانیه</b></div><input type="range" min="1" max="15" step="1" value="${value}" aria-label="زمان ویدیو از یک تا پانزده ثانیه"><div class="create-studio-duration-track-scale"><span>۱ ثانیه</span><span>۱۵ ثانیه</span></div>`;
      const slider = timeline.querySelector('input');
      const valueLabel = timeline.querySelector('[data-duration-value]');
      slider.addEventListener('input', () => {
        const nextValue = String(slider.value);
        selectedValues.duration = nextValue;
        valueLabel.textContent = `${formatNumber(nextValue)} ثانیه`;
        const label = select.querySelector('[data-select-label]');
        const input = select.querySelector('[data-select-input]');
        if (label) label.textContent = controlLabel('duration', {label: `${formatNumber(nextValue)} ثانیه`});
        if (input) input.value = nextValue;
        saveStudioState();
        updateCost();
      });
      menu.appendChild(timeline);
      return;
    }
    options.forEach((option) => {
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'create-studio-select-option'; button.dataset.value = option.value ?? ''; button.setAttribute('role', 'option');
      if (selected !== null && selected !== undefined && String(option.value ?? '') === String(selected)) button.classList.add('is-selected');
      if (select.dataset.studioSelect === 'ratio') button.innerHTML = `<i class="create-studio-ratio-frame" style="--ratio:${String(option.value || '1:1').replace(':', '/')}"></i><span><b>${option.label}</b><small>${option.meta || ''}</small></span><i class="fa-solid fa-check"></i>`;
      else if (select.dataset.studioSelect === 'model') button.innerHTML = `<span><b>${option.label}</b></span><i class="fa-solid fa-check"></i>`;
      else button.innerHTML = `<span><b>${option.label}</b><small>${option.meta || ''}</small></span><i class="fa-solid fa-check"></i>`;
      button.addEventListener('click', () => chooseSelect(select, option.value ?? ''));
      menu.appendChild(button);
    });
  }

  function controlLabel(key, option) {
    if (!option) return 'انتخاب کنید';
    if (key === 'model') return englishModelName(option.label, option.value);
    if (key === 'count') return `تعداد خروجی: ${option.label}`;
    if (key === 'quality') return `کیفیت خروجی: ${option.label}`;
    if (key === 'motion') return `حرکت دوربین: ${option.label}`;
    if (key === 'duration') return `زمان ویدیو: ${option.label}`;
    if (key === 'ratio') return `نسبت تصویر: ${formatRatio(option.value)}`;
    return option.label || 'انتخاب کنید';
  }

  function chooseSelect(select, value) {
    const key = select.dataset.studioSelect;
    const options = optionsFor(key);
    const isEmptyDefault = value === '' && (key === 'model' || key === 'ratio' || key === 'motion');
    const selected = isEmptyDefault ? null : (options.find((option) => String(option.value ?? '') === String(value ?? '')) || options[0]);
    selectedValues[key] = selected?.value ?? '';
    const label = select.querySelector('[data-select-label]');
    const input = select.querySelector('[data-select-input]');
    const selectedValue = selected?.value ?? '';
    if (label) label.textContent = !selected && key === 'model' ? 'مدل هوش مصنوعی' : !selected && key === 'ratio' ? 'نسبت تصویر' : !selected && key === 'motion' ? 'حرکت دوربین' : controlLabel(key, selected);
    if (input) { input.value = selectedValue; input.setAttribute('value', selectedValue); }
    closeSelect(select);
    renderSelectOptions(select, options, selected ? selected.value : null);
    const restoredInput = select.querySelector('[data-select-input]');
    if (restoredInput) { restoredInput.value = selectedValue; restoredInput.setAttribute('value', selectedValue); }
    if (key === 'count' && outputCountInput) outputCountInput.value = selectedValue || '1';
    if (key === 'model') {
      const selectedModel = options.find((option) => String(option.value) === String(selected?.value));
      const modelName = root.querySelector('[data-studio-model-name]');
      if (modelName) modelName.innerHTML = `${selectedModel?.label || activeConfig.model || 'مدل محصول'} <i class="fa-solid fa-signal"></i>`;
      if (currentMode === 'image') {
        ['ratio', 'quality'].forEach((dependentKey) => {
          const dependent = root.querySelector(`[data-studio-select="${dependentKey}"]`);
          if (!dependent) return;
          const dependentOptions = optionsFor(dependentKey);
          const current = selectedValues[dependentKey];
          const next = dependentOptions.some((option) => String(option.value) === String(current)) ? current : (dependentOptions[0]?.value || '');
          chooseSelect(dependent, next);
        });
      }
    }
    saveStudioState();
    updateCost();
  }

  function setupSelects() {
    root.querySelectorAll('[data-studio-select]').forEach((select) => {
      const key = select.dataset.studioSelect;
      const options = optionsFor(key);
      const value = selectedValues[key] ?? defaultValueFor(key, options);
      chooseSelect(select, value);
      const toggle = select.querySelector('[data-select-toggle]');
      toggle.onclick = (event) => {
        event.stopPropagation();
        const open = !select.classList.contains('is-open');
        if (open) { openSelect(select); toggle.setAttribute('aria-expanded', 'true'); }
        else closeSelect(select);
      };
      const row = select.closest('.create-studio-setting-row');
      row?.addEventListener('click', (event) => {
        if (event.target.closest('[data-select-toggle], [data-select-menu], input, button, a')) return;
        toggle.click();
      });
    });
  }

  function updateCost() {
    let value = Number(activeConfig?.cost || 0);
    if (currentMode === 'video') {
      const duration = Number(selectedValues.duration || activeConfig?.video?.default_duration || 0);
      const costs = activeConfig?.video?.credit_costs_by_duration || {};
      if (costs[String(duration)] !== undefined) value = Number(costs[String(duration)]);
      const qualityCosts = activeConfig?.video?.quality_costs || {};
      value += Number(qualityCosts[selectedValues.quality] || 0);
    } else {
      value *= Math.max(1, Math.min(6, Number(normalizeDigits(selectedValues.count || outputCountInput?.value || '1') || 1)));
    }
    if (cost) cost.textContent = faDigits(value);
    requestServerQuote();
  }

  function requestServerQuote() {
    if (!config.quote_url) return;
    if (quoteTimer) window.clearTimeout(quoteTimer);
    const sequence = ++quoteSequence;
    quoteTimer = window.setTimeout(async () => {
      const selectedModel = selectedValues.model ? optionsFor('model').find((option) => String(option.value) === String(selectedValues.model)) : null;
      const params = new URLSearchParams({
        mode: currentMode,
        model: selectedModel?.value || '',
        provider: selectedModel?.provider || '',
        resolution: selectedValues.quality || '',
        aspect_ratio: selectedValues.ratio || '',
        duration: selectedValues.duration || '',
        count: currentMode === 'image' ? normalizeDigits(selectedValues.count || outputCountInput?.value || '1') || '1' : '1',
      });
      try {
        const response = await fetch(`${config.quote_url}?${params.toString()}`, {headers: {'Accept': 'application/json'}});
        const payload = await response.json();
        if (sequence !== quoteSequence) return;
        if (!payload.cost_known || payload.credits === null || payload.credits === undefined) {
          if (cost) cost.textContent = '—';
          return;
        }
        if (cost) cost.textContent = faDigits(payload.credits);
      } catch (_) {}
    }, 220);
  }

  function updateModeUI() {
    activeConfig = config[currentMode] || {};
    root.dataset.mode = currentMode;
    modeTabs.forEach((button) => { const active = button.dataset.studioMode === currentMode; button.classList.toggle('is-active', active); button.setAttribute('aria-selected', active ? 'true' : 'false'); });
    root.querySelectorAll('[data-studio-video-only]').forEach((item) => { item.hidden = currentMode !== 'video'; });
    root.querySelectorAll('[data-studio-image-only]').forEach((item) => { item.hidden = currentMode !== 'image'; });
    videoContent.hidden = currentMode !== 'video'; imageContent.hidden = currentMode !== 'image';
    root.querySelector('[data-studio-model-title]').textContent = currentMode === 'video' ? 'استودیوی ویدیو' : 'استودیوی عکس';
    root.querySelector('[data-studio-model-subtitle]').textContent = currentMode === 'video' ? 'ساخت ویدیو با مدل واقعی محصول' : 'ساخت عکس با مدل واقعی محصول';
    root.querySelector('[data-studio-model-name]').innerHTML = `${activeConfig.model || 'مدل محصول'} <i class="fa-solid fa-signal"></i>`;
    root.querySelector('[data-studio-stage-kicker]').textContent = currentMode === 'video' ? 'استودیوی ساخت ویدیو' : 'استودیوی ساخت عکس';
    root.querySelector('[data-studio-stage-title]').textContent = currentMode === 'video' ? 'ویدیو را با چند کلمه بساز' : 'عکس را با چند کلمه بساز';
    root.querySelector('[data-studio-stage-subtitle]').textContent = currentMode === 'video' ? 'ایده‌ات را بنویس، تنظیمات را انتخاب کن و ساخت واقعی را به وطن بسپار.' : 'ایده‌ات را بنویس، تنظیمات را انتخاب کن و عکس واقعی‌ات را در گالری تحویل بگیر.';
    prompt.placeholder = currentMode === 'video' ? 'مثلاً: حرکت آرام دوربین در خیابان بارانی تهران با نورهای نئون...' : 'مثلاً: یک پرتره‌ی سینمایی با نور پنجره و پس‌زمینه‌ی مینیمال...';
    uploadInput.accept = currentMode === 'video' ? 'image/png,image/jpeg,image/webp,video/mp4' : 'image/png,image/jpeg,image/webp';
    root.querySelector('[data-upload-title]').textContent = currentMode === 'video' ? 'افزودن منبع' : 'افزودن تصویر مرجع';
    root.querySelector('[data-upload-help]').textContent = currentMode === 'video' ? 'تصویر یا ویدیو' : 'تصویر مرجع برای حفظ شباهت';
    if (outputCountInput && currentMode === 'image' && !outputCountInput.value) outputCountInput.value = '1';
    selectedValues = {};
    renderDynamicFields();
    setupSelects(); updateCost();
    if (stageVideo) { if (currentMode === 'video') stageVideo.play().catch(() => {}); else stageVideo.pause(); }
    if (outputVideo) outputVideo.hidden = currentMode !== 'video'; if (outputImage) outputImage.hidden = currentMode === 'video';
    if (videoPlay) videoPlay.hidden = currentMode !== 'video';
  }

  function setMode(mode) { currentMode = mode === 'image' ? 'image' : 'video'; hideError(); updateModeUI(); saveStudioState(); }

  function updatePromptCount() { promptCount.textContent = faDigits(prompt.value.length); }
  function stopProgress() { if (progressTimer) window.clearInterval(progressTimer); progressTimer = null; }
  function startProgress(title) { stopProgress(); progress.hidden = false; result.hidden = true; videoContent.hidden = true; imageContent.hidden = true; progressTitle.textContent = title; progressText.textContent = 'در حال آماده‌سازی و ارسال درخواست به سرویس هوش مصنوعی...'; progressBar.style.width = '8%'; let value = 8; progressTimer = window.setInterval(() => { value = Math.min(88, value + (value < 55 ? 3 : 1)); progressBar.style.width = `${value}%`; }, 900); }
  function finishProgress() { stopProgress(); progressBar.style.width = '100%'; window.setTimeout(() => { progress.hidden = true; }, 350); }

  function appendDefaults(data) {
    Object.entries(activeConfig.defaults || {}).forEach(([key, value]) => data.append(`fields[${key}]`, value));
    if (currentMode === 'video') {
      data.set('video[duration]', selectedValues.duration || activeConfig.video.default_duration);
      data.set('video[aspect_ratio]', selectedValues.ratio || activeConfig.video.default_aspect_ratio);
      data.set('video[resolution]', selectedValues.quality || activeConfig.video.default_resolution);
      data.set('video[motion_preset]', selectedValues.motion || '');
      data.set('studio_mode', '1');
      data.set('rights_confirmed', '1'); data.set('video[generate_audio]', '0');
      data.delete('studio_prompt'); data.append('prompt', prompt.value.trim());
      data.append('negative_prompt', negativeInput?.value?.trim() || '');
    } else {
      data.set('output[aspect_ratio]', selectedValues.ratio || activeConfig.default_output_aspect_ratio || '1:1');
      data.set('output[quality]', selectedValues.quality || activeConfig.default_output_resolution || '720');
      data.set('output[main_quality]', activeConfig.default_main_quality || 'standard');
      data.set('output[count]', normalizeDigits(selectedValues.count || outputCountInput?.value || '1') || '1');
      data.set('identity_preservation', currentMode === 'image' && activeConfig.reference_upload_key && uploadInput.files[0] ? '1' : '0');
    }
    data.set('studio_mode', '1');
    Object.entries(selectedValues).forEach(([key, value]) => {
      if (key.startsWith('field:')) data.set(`fields[${key.slice(6)}]`, value ?? '');
    });
    if (currentMode === 'video' && uploadInput.files[0]) data.append('source_image', uploadInput.files[0]);
    if (currentMode === 'image' && uploadInput.files[0] && activeConfig.reference_upload_key) data.append(`uploads[${activeConfig.reference_upload_key}][]`, uploadInput.files[0]);
    const selectedModel = selectedValues.model ? optionsFor('model').find((option) => String(option.value) === String(selectedValues.model)) : null;
    if (selectedModel?.value) {
      data.set('studio_model', selectedModel.value);
      if (selectedModel.provider) data.set('studio_provider', selectedModel.provider);
    }
    if (currentMode === 'image') data.set('studio_prompt', prompt.value.trim());
    data.set('studio_negative_prompt', negativeInput?.value?.trim() || '');
    return data;
  }

  async function readPayload(response) { const text = await response.text(); let payload = {}; try { payload = text ? JSON.parse(text) : {}; } catch (_) {} if (!response.ok || !payload.success) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'ساخت خروجی انجام نشد.'); return payload; }

  async function pollVideo(statusUrl) {
    for (let attempt = 0; attempt < 160; attempt += 1) {
      await new Promise((resolve) => { pollTimer = window.setTimeout(resolve, 2500); });
      const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}}); const payload = await response.json();
      if (payload.status === 'completed' && payload.video_url) return payload.video_url;
      if (['failed', 'canceled'].includes(payload.status)) throw new Error(payload.error_message || 'ساخت ویدیو ناموفق بود.');
      progressText.textContent = payload.status === 'queued' ? 'درخواست در صف ساخت قرار دارد...' : 'مدل هوش مصنوعی در حال ساخت خروجی است...';
    }
    throw new Error('ساخت ویدیو بیشتر از زمان معمول طول کشید؛ لطفاً بعداً وضعیت گالری را بررسی کنید.');
  }

  async function generate() {
    hideError();
    if (!prompt.value.trim()) { showError('ابتدا توضیحات ساخت را وارد کنید.'); prompt.focus(); return; }
    if (config.authenticated !== true) { saveStudioState(); window.location.href = config.login_url; return; }
    if (currentMode === 'image' && activeConfig.requires_reference && !uploadInput.files[0]) { showError('برای ساخت این محصول، یک تصویر مرجع واضح بارگذاری کنید.'); uploadZone.focus(); return; }
    submit.disabled = true; submitLabel.textContent = 'در حال ساخت'; startProgress(currentMode === 'video' ? 'در حال ساخت ویدیو' : 'در حال ساخت عکس');
    try {
      const url = activeConfig.generate_url || `${window.location.origin}/app/create/${activeConfig.route_slug}/generate`;
      const response = await fetch(url, {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json'}, body: appendDefaults(new FormData(form))});
      const payload = await readPayload(response);
      if (currentMode === 'video') {
        const videoUrl = await pollVideo(payload.status_url); outputVideo.src = videoUrl; outputVideo.hidden = false; outputImage.hidden = true; videoPlay.hidden = false; outputVideo.load(); await outputVideo.play().catch(() => {});
      } else {
        const first = payload.images?.[0]?.url || payload.image_url; if (!first) throw new Error('لینک خروجی تصویر از سرویس دریافت نشد.'); outputImage.src = first; outputImage.hidden = false; outputVideo.hidden = true; videoPlay.hidden = true;
      }
      finishProgress(); result.hidden = false; submitLabel.textContent = 'دوباره بساز';
      if (window.matchMedia('(max-width: 700px)').matches) {
        window.requestAnimationFrame(() => result.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'}));
      }
    } catch (error) { stopProgress(); progress.hidden = true; videoContent.hidden = currentMode !== 'video'; imageContent.hidden = currentMode !== 'image'; showError(error.message || 'ارتباط با سرویس ساخت برقرار نشد.'); }
    finally { submit.disabled = false; }
  }

  modeTabs.forEach((tab) => tab.addEventListener('click', () => setMode(tab.dataset.studioMode)));
  prompt.addEventListener('input', updatePromptCount);
  negativeInput?.addEventListener('input', () => root.querySelector('[data-studio-negative]').value = negativeInput.value);
  root.querySelector('[data-studio-improve]').addEventListener('click', () => { const suffix = currentMode === 'video' ? ' حرکت نرم دوربین، ریتم سینمایی و نورپردازی طبیعی' : ' ترکیب‌بندی حرفه‌ای، نورپردازی طبیعی و جزئیات دقیق'; prompt.value = prompt.value.trim() ? `${prompt.value.trim()}،${suffix}` : (currentMode === 'video' ? 'یک نمای سینمایی از تهران در شب با باران و نورهای نئون' : 'یک پرتره ادیتوریال با نور پنجره و پس‌زمینه مینیمال') + suffix; updatePromptCount(); prompt.focus(); });
  uploadZone.addEventListener('click', () => uploadInput.click()); uploadZone.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); uploadInput.click(); } });
  uploadInput.addEventListener('change', () => { const file = uploadInput.files[0]; if (!file) return; uploadFile.hidden = false; uploadFile.textContent = file.name; });
  submit.addEventListener('click', generate); root.querySelector('[data-studio-regenerate]').addEventListener('click', generate);
  root.querySelector('[data-studio-error-close]').addEventListener('click', hideError);
  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-studio-select], [data-select-menu]')) root.querySelectorAll('[data-studio-select].is-open').forEach(closeSelect);
    if (helpDialog && !helpDialog.hidden && !event.target.closest('.create-studio-help-dialog, [data-studio-help]')) closeHelp();
  });
  const helpDialog = root.querySelector('[data-studio-help-dialog]');
  const closeHelp = () => { if (helpDialog) helpDialog.hidden = true; };
  root.querySelector('[data-studio-help]').addEventListener('click', () => { if (helpDialog) helpDialog.hidden = false; });
  root.querySelector('[data-studio-help-close]').addEventListener('click', closeHelp);
  helpDialog?.addEventListener('click', (event) => { if (event.target === helpDialog) closeHelp(); });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeHelp(); });
  root.querySelector('[data-studio-download]').addEventListener('click', () => { const media = currentMode === 'video' ? outputVideo : outputImage; if (!media?.src) return; const link = document.createElement('a'); link.href = media.src; link.download = currentMode === 'video' ? 'vatan-ai-video.mp4' : 'vatan-ai-image.png'; link.target = '_blank'; link.click(); });
  root.querySelector('[data-studio-share]').addEventListener('click', async () => { const media = currentMode === 'video' ? outputVideo : outputImage; if (!media?.src) return; try { await navigator.clipboard.writeText(media.src); showError('لینک خروجی کپی شد.'); } catch (_) { showError('کپی لینک در این مرورگر ممکن نیست؛ از گزینه دانلود استفاده کنید.'); } });
  videoPlay.addEventListener('click', () => { if (outputVideo.paused) { outputVideo.play().catch(() => {}); videoPlay.innerHTML = '<i class="fa-solid fa-pause"></i>'; } else { outputVideo.pause(); videoPlay.innerHTML = '<i class="fa-solid fa-play"></i>'; } });
  window.addEventListener('beforeunload', () => { if (pollTimer) window.clearTimeout(pollTimer); stopProgress(); });

  const savedState = readStudioState();
  updatePromptCount();
  setMode(savedState?.mode === 'video' ? 'video' : 'image');
  if (savedState) {
    prompt.value = String(savedState.prompt || '');
    if (negativeInput) negativeInput.value = String(savedState.negative || '');
    const projectInput = root.querySelector('[data-studio-project]');
    if (projectInput) projectInput.value = String(savedState.project || '');
    if (outputCountInput) outputCountInput.value = normalizeDigits(savedState.outputCount || '1') || '1';
    selectedValues = {...(savedState.selectedValues || {})};
    if (!selectedValues.count) selectedValues.count = String(Math.max(1, Math.min(6, Number(normalizeDigits(savedState.outputCount || '1') || 1))));
    setupSelects();
    updatePromptCount();
    clearStudioState();
  }
}());
