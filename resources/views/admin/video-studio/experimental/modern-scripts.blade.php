<script>
(() => {
  const form = document.getElementById('v2-form');
  if (!form) return;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('[name="_token"]')?.value || '';
  const toPersian = value => String(value).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  const legacyControls = [
    ...form.querySelectorAll('[data-v2-panel="1"] > .v2-hook-font-field,[data-v2-panel="1"] > .v2-hook-workspace,[data-v2-panel="1"] > .v2-cta-card,[data-v2-panel="1"] > .v2-grid'),
    ...form.querySelectorAll('[data-v2-panel="2"] > .v2-platform-grid > .v2-platform--instagram,[data-v2-panel="2"] > .v2-platform-grid > .v2-platform--telegram'),
  ];
  legacyControls.forEach(section => section.querySelectorAll('input,select,textarea,button').forEach(control => { control.disabled = true; }));
  ['v2-hook-background-value', 'v2-hook-text-color-value'].forEach(id => {
    const control = document.getElementById(id);
    if (control) control.disabled = true;
  });

  const hookManual = document.getElementById('v2-modern-hook-manual');
  const hookPreview = document.getElementById('v2-modern-phone-hook');
  const hookScreen = document.getElementById('v2-modern-phone-screen');
  const hookValue = document.getElementById('v2-hook-value');
  const syncHookText = value => {
    const text = String(value || '').trim() || 'یک انتخاب بهتر داشته باش';
    hookPreview.textContent = text;
    hookValue.value = text;
    document.querySelectorAll('#v2-modern-hook-grid .v2-hook-card').forEach(card => card.classList.toggle('is-selected', Boolean(card.querySelector('input')?.checked)));
  };
  const syncHookChoice = () => {
    const selected = document.querySelector('input[name="v2_modern_hook_choice"]:checked');
    if (selected && hookManual) hookManual.value = selected.value;
    syncHookText(selected?.value || hookManual?.value || hookValue?.value);
  };
  document.querySelectorAll('input[name="v2_modern_hook_choice"]').forEach(input => input.addEventListener('change', syncHookChoice));
  hookManual?.addEventListener('input', () => syncHookText(hookManual.value));

  const colorInput = target => document.querySelector(`input[name="${target === 'background' ? 'hook_background' : 'hook_text_color'}"]:checked`);
  const syncHookColors = () => {
    const background = colorInput('background');
    const text = colorInput('text');
    hookScreen?.style.setProperty('--v2-hook-bg', background?.dataset.v2ColorCss || 'var(--primary)');
    hookScreen?.style.setProperty('--v2-hook-fg', text?.dataset.v2ColorCss || 'var(--card-bg)');
  };
  document.querySelectorAll('input[name="hook_background"],input[name="hook_text_color"]').forEach(input => input.addEventListener('change', syncHookColors));

  const hookSize = document.getElementById('v2-modern-hook-font-size');
  const hookScale = document.getElementById('v2-modern-hook-scale');
  const hookOffset = document.getElementById('v2-modern-hook-offset');
  const hookWeight = () => document.querySelector('input[name="hook_font_weight"]:checked')?.value || '3';
  const fontWeight = { 1: 300, 2: 400, 3: 500, 4: 700, 5: 900 };
  const syncHookMetrics = () => {
    hookPreview.style.setProperty('font-family', `'${document.querySelector('input[name="font_family"]:checked')?.value || 'B_Yekan'}'`, 'important');
    hookPreview.style.fontSize = `${hookSize?.value || 36}px`;
    hookPreview.style.fontWeight = String(fontWeight[hookWeight()] || 500);
    hookPreview.style.setProperty('--v2-hook-scale', hookScale?.value || 1);
    hookPreview.style.setProperty('--v2-hook-offset', hookOffset?.value || 0);
    document.getElementById('v2-modern-hook-font-size-output').textContent = toPersian(hookSize?.value || 36);
    document.getElementById('v2-modern-hook-scale-output').textContent = `${toPersian(hookScale?.value || 1)}×`;
    document.getElementById('v2-modern-hook-offset-output').textContent = `${toPersian(hookOffset?.value || 0)}٪`;
  };
  document.querySelectorAll('input[name="font_family"],input[name="hook_font_weight"]').forEach(input => input.addEventListener('change', syncHookMetrics));
  [hookSize, hookScale, hookOffset].forEach(input => input?.addEventListener('input', syncHookMetrics));
  syncHookChoice(); syncHookColors(); syncHookMetrics();

  const hookDuration = document.getElementById('v2-modern-hook-duration');
  const hookDurationMode = document.getElementById('v2-modern-hook-duration-mode');
  const hookDurationOutput = document.getElementById('v2-modern-hook-duration-output');
  const hookDurationAuto = document.getElementById('v2-modern-hook-duration-auto');
  const syncHookDuration = () => {
    const automatic = hookDurationMode?.value === 'auto';
    if (hookDuration) hookDuration.disabled = automatic;
    if (hookDurationAuto) hookDurationAuto.setAttribute('aria-pressed', String(automatic));
    if (hookDurationOutput) hookDurationOutput.textContent = automatic
      ? 'خودکار'
      : `${toPersian(Number(hookDuration?.value || 2).toFixed(1).replace(/\.0$/, ''))} ثانیه`;
  };
  hookDuration?.addEventListener('input', () => { if (hookDurationMode) hookDurationMode.value = 'manual'; syncHookDuration(); });
  hookDurationAuto?.addEventListener('click', () => { if (hookDurationMode) hookDurationMode.value = hookDurationMode.value === 'auto' ? 'manual' : 'auto'; syncHookDuration(); });
  syncHookDuration();

  const ctaEnabled = document.getElementById('v2-modern-cta-enabled');
  const ctaText = document.getElementById('v2-modern-cta-text');
  const ctaScreen = document.getElementById('v2-modern-cta-screen');
  const ctaPreview = document.getElementById('v2-modern-cta-preview');
  const ctaColorInput = target => document.querySelector(`input[name="${target === 'background' ? 'cta_background' : 'cta_text_color'}"]:checked`);
  const syncCtaColors = () => {
    const background = ctaColorInput('background');
    const text = ctaColorInput('text');
    ctaScreen?.style.setProperty('--v2-hook-bg', background?.dataset.v2ColorCss || 'var(--primary)');
    ctaScreen?.style.setProperty('--v2-hook-fg', text?.dataset.v2ColorCss || 'var(--card-bg)');
  };
  const ctaSize = document.getElementById('v2-modern-cta-font-size');
  const ctaScale = document.getElementById('v2-modern-cta-scale');
  const ctaOffset = document.getElementById('v2-modern-cta-offset');
  const ctaWeight = () => document.querySelector('input[name="cta_font_weight"]:checked')?.value || '3';
  const syncCtaMetrics = () => {
    if (!ctaPreview) return;
    ctaPreview.style.setProperty('font-family', `'${document.querySelector('input[name="font_family"]:checked')?.value || 'B_Yekan'}'`, 'important');
    ctaPreview.style.fontWeight = String(fontWeight[ctaWeight()] || 500);
    ctaPreview.style.fontSize = `${ctaSize?.value || 36}px`;
    ctaPreview.style.setProperty('--v2-hook-scale', ctaScale?.value || 1);
    ctaPreview.style.setProperty('--v2-hook-offset', ctaOffset?.value || 0);
    const sizeOutput = document.getElementById('v2-modern-cta-font-size-output');
    const scaleOutput = document.getElementById('v2-modern-cta-scale-output');
    const offsetOutput = document.getElementById('v2-modern-cta-offset-output');
    if (sizeOutput) sizeOutput.textContent = toPersian(ctaSize?.value || 36);
    if (scaleOutput) scaleOutput.textContent = `${toPersian(ctaScale?.value || 1)}×`;
    if (offsetOutput) offsetOutput.textContent = `${toPersian(ctaOffset?.value || 0)}٪`;
  };
  const syncCtaPreview = () => {
    if (!ctaPreview || !ctaScreen) return;
    const text = String(ctaText?.value || '').trim() || 'برای دیدن جزئیات محصول، همین حالا اقدام کنید';
    ctaPreview.textContent = text;
    ctaScreen.style.opacity = ctaEnabled?.checked === false ? '.45' : '1';
    syncCtaColors();
    syncCtaMetrics();
  };
  const syncCtaChoice = () => {
    const selected = document.querySelector('input[name="cta_text_choice"]:checked');
    if (selected && ctaText) ctaText.value = selected.value;
    document.querySelectorAll('#v2-modern-cta-grid .v2-hook-card').forEach(card => card.classList.toggle('is-selected', Boolean(card.querySelector('input')?.checked)));
    syncCtaPreview();
  };
  document.querySelectorAll('input[name="cta_text_choice"]').forEach(input => input.addEventListener('change', syncCtaChoice));
  ctaText?.addEventListener('input', syncCtaPreview);
  ctaEnabled?.addEventListener('change', syncCtaPreview);
  document.querySelectorAll('input[name="cta_background"],input[name="cta_text_color"]').forEach(input => input.addEventListener('change', syncCtaPreview));
  document.querySelectorAll('input[name="font_family"],input[name="cta_font_weight"]').forEach(input => input.addEventListener('change', syncCtaPreview));
  [ctaSize, ctaScale, ctaOffset].forEach(input => input?.addEventListener('input', syncCtaPreview));
  syncCtaChoice(); syncCtaPreview();

  const ctaDuration = document.getElementById('v2-modern-cta-duration');
  const ctaDurationMode = document.getElementById('v2-modern-cta-duration-mode');
  const ctaDurationOutput = document.getElementById('v2-modern-cta-duration-output');
  const ctaDurationAuto = document.getElementById('v2-modern-cta-duration-auto');
  const syncCtaDuration = () => {
    if (ctaDurationMode && !['manual', 'auto'].includes(ctaDurationMode.value)) ctaDurationMode.value = 'manual';
    const automatic = ctaDurationMode?.value === 'auto';
    if (ctaDuration) ctaDuration.disabled = automatic;
    if (ctaDurationAuto) ctaDurationAuto.setAttribute('aria-pressed', String(automatic));
    if (ctaDurationOutput) ctaDurationOutput.textContent = automatic
      ? 'خودکار'
      : `${toPersian(Number(ctaDuration?.value || 2).toFixed(1).replace(/\.0$/, ''))} ثانیه`;
  };
  ctaDuration?.addEventListener('input', () => { if (ctaDurationMode) ctaDurationMode.value = 'manual'; syncCtaDuration(); });
  ctaDurationAuto?.addEventListener('click', () => { if (ctaDurationMode) ctaDurationMode.value = ctaDurationMode.value === 'auto' ? 'manual' : 'auto'; syncCtaDuration(); });
  form.addEventListener('v2:before-submit', () => { if (ctaDurationMode && !['manual', 'auto'].includes(ctaDurationMode.value)) ctaDurationMode.value = 'manual'; });
  syncCtaDuration();

  const hookPromptModal = document.getElementById('v2-hook-prompt-modal');
  const openHookPrompt = () => hookPromptModal?.classList.add('is-open');
  document.getElementById('v2-modern-open-hook-prompt')?.addEventListener('click', openHookPrompt);
  document.getElementById('v2-modern-regenerate-hook')?.addEventListener('click', async event => {
    const product = document.getElementById('v2-product');
    if (!product?.value) { window.alert('ابتدا یک محصول انتخاب کنید.'); return; }
    const button = event.currentTarget;
    const payload = new FormData();
    payload.append('_token', csrf); payload.append('product_id', product.value); payload.append('channel', 'instagram');
    payload.append('hook_guidelines', document.getElementById('v2-hook-guidelines')?.value || '');
    button.disabled = true;
    try {
      const response = await fetch('{{ route('admin.video-studio.preview') }}', { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: payload });
      const data = await response.json(); if (!response.ok) throw new Error(data.message || 'ساخت هوک ناموفق بود.');
      const values = (data.hook_options || []).map(value => String(value || '').trim()).filter(Boolean).slice(0, 3);
      const holder = document.getElementById('v2-modern-hook-grid');
      if (!values.length || !holder) return;
      holder.replaceChildren();
      values.forEach((text, index) => {
        const label = document.createElement('label'); label.className = 'v2-hook-card';
        const input = document.createElement('input'); input.type = 'radio'; input.name = 'v2_modern_hook_choice'; input.value = text;
        const title = document.createElement('strong'); title.textContent = `گزینه ${toPersian(index + 1)}`;
        const copy = document.createElement('p'); copy.textContent = text;
        input.addEventListener('change', syncHookChoice); label.append(input, title, copy); holder.appendChild(label);
      });
      holder.querySelector('input')?.click();
    } catch (error) { window.alert(error.message || 'ساخت هوک ناموفق بود.'); }
    finally { button.disabled = false; }
  });
  const ctaPromptModal = document.getElementById('v2-cta-prompt-modal');
  const ctaPromptText = document.getElementById('v2-cta-prompt-text');
  const ctaPromptValue = document.getElementById('v2-modern-cta-guidelines');
  document.getElementById('v2-modern-open-cta-prompt')?.addEventListener('click', () => { if (ctaPromptText) ctaPromptText.value = ctaPromptValue?.value || ''; ctaPromptModal?.classList.add('is-open'); });
  const closeCtaPrompt = () => ctaPromptModal?.classList.remove('is-open');
  document.getElementById('v2-cta-prompt-close')?.addEventListener('click', closeCtaPrompt);
  ctaPromptModal?.addEventListener('click', event => { if (event.target === ctaPromptModal) closeCtaPrompt(); });
  document.getElementById('v2-cta-prompt-save')?.addEventListener('click', () => { if (ctaPromptValue) ctaPromptValue.value = ctaPromptText?.value || ''; closeCtaPrompt(); });
  document.getElementById('v2-modern-regenerate-cta')?.addEventListener('click', async event => {
    if (!product?.value) { window.alert('ابتدا یک محصول انتخاب کنید.'); return; }
    const button = event.currentTarget; const payload = new FormData();
    payload.append('_token', csrf); payload.append('product_id', product.value); payload.append('channel', 'instagram');
    payload.append('hook_guidelines', ctaPromptValue?.value || '');
    button.disabled = true;
    try {
      const response = await fetch('{{ route('admin.video-studio.preview') }}', { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: payload });
      const data = await response.json(); if (!response.ok) throw new Error(data.message || 'ساخت CTA ناموفق بود.');
      const values = (data.hook_options || []).map(value => String(value || '').trim()).filter(Boolean).slice(0, 3); const holder = document.getElementById('v2-modern-cta-grid');
      if (!values.length || !holder) return; holder.replaceChildren();
      values.forEach((text, index) => { const label = document.createElement('label'); label.className = 'v2-hook-card'; const input = document.createElement('input'); input.type = 'radio'; input.name = 'cta_text_choice'; input.value = text; input.addEventListener('change', syncCtaChoice); const title = document.createElement('strong'); title.textContent = `گزینه ${toPersian(index + 1)}`; const copy = document.createElement('p'); copy.textContent = text; label.append(input, title, copy); holder.appendChild(label); });
      holder.querySelector('input')?.click();
    } catch (error) { window.alert(error.message || 'ساخت CTA ناموفق بود.'); }
    finally { button.disabled = false; }
  });

  const product = document.getElementById('v2-product');
  const productPicker = document.getElementById('v2-product-picker');
  const productTrigger = document.getElementById('v2-product-trigger');
  const productPopover = document.getElementById('v2-product-picker-popover');
  const productSearch = document.getElementById('v2-product-search');
  const productOptions = [...document.querySelectorAll('[data-v2-product-option]')];
  const productEmpty = document.getElementById('v2-product-empty');
  const closeProductPicker = () => { if (productPopover) productPopover.hidden = true; productTrigger?.setAttribute('aria-expanded', 'false'); };
  const openProductPicker = () => { if (!productPopover) return; productPopover.hidden = false; productTrigger?.setAttribute('aria-expanded', 'true'); productSearch?.focus(); };
  const selectProduct = option => {
    if (!product || !option) return;
    product.value = option.dataset.v2ProductId || '';
    document.getElementById('v2-product-picked-label').textContent = option.dataset.v2ProductName || 'انتخاب محصول';
    closeProductPicker();
    product.dispatchEvent(new Event('change', { bubbles: true }));
  };
  productTrigger?.addEventListener('click', () => productPopover?.hidden ? openProductPicker() : closeProductPicker());
  productSearch?.addEventListener('input', () => {
    const query = productSearch.value.trim().toLocaleLowerCase('fa'); let visible = 0;
    productOptions.forEach(option => { const matches = option.textContent.toLocaleLowerCase('fa').includes(query); option.hidden = !matches; if (matches) visible += 1; });
    if (productEmpty) productEmpty.hidden = visible !== 0;
  });
  productOptions.forEach(option => option.addEventListener('click', () => selectProduct(option)));
  document.getElementById('v2-product-random')?.addEventListener('click', () => {
    const eligible = productOptions.filter(option => option.dataset.v2ProductId);
    if (!eligible.length) { window.alert('محصول فعالی برای انتخاب تصادفی وجود ندارد.'); return; }
    const lowestCount = Math.min(...eligible.map(option => Number(option.dataset.v2ProductCount || 0)));
    const leastBuilt = eligible.filter(option => Number(option.dataset.v2ProductCount || 0) === lowestCount);
    selectProduct(leastBuilt[Math.floor(Math.random() * leastBuilt.length)]);
  });
  document.addEventListener('click', event => { if (productPicker && !productPicker.contains(event.target)) closeProductPicker(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') closeProductPicker(); });

  const selectedImageInputs = [...form.querySelectorAll('[data-v2-image-input]')];
  let selectedImageOrder = selectedImageInputs.filter(input => input.checked).map(input => input.value);
  const syncSelectedImageOrder = () => {
    selectedImageOrder = selectedImageOrder.filter(url => selectedImageInputs.some(input => input.checked && input.value === url));
    selectedImageInputs.forEach(input => {
      if (input.checked && !selectedImageOrder.includes(input.value)) selectedImageOrder.push(input.value);
    });
    selectedImageInputs.forEach(input => {
      const priority = input.closest('[data-v2-image-card]')?.querySelector('[data-v2-image-priority]');
      const index = selectedImageOrder.indexOf(input.value);
      if (priority) priority.textContent = index >= 0 ? toPersian(index + 1) : '';
    });
    const grid = selectedImageInputs[0]?.closest('.v2-image-grid');
    if (grid) {
      const selectedCards = selectedImageOrder
        .map(url => selectedImageInputs.find(input => input.checked && input.value === url)?.closest('[data-v2-image-card]'))
        .filter(Boolean);
      const unselectedCards = selectedImageInputs
        .filter(input => !input.checked)
        .map(input => input.closest('[data-v2-image-card]'))
        .filter(Boolean);
      [...selectedCards, ...unselectedCards].forEach(card => grid.appendChild(card));
    }
  };
  selectedImageInputs.forEach(input => input.addEventListener('change', syncSelectedImageOrder));
  form.addEventListener('v2:before-submit', syncSelectedImageOrder);
  syncSelectedImageOrder();

  const sourceSelect = document.getElementById('v2-source');
  const sourceFile = document.getElementById('v2-source-file');
  const sourceAudio = document.getElementById('v2-source-audio');
  const sourcePlayer = document.getElementById('v2-source-player');
  const sourcePlayerLabel = document.getElementById('v2-source-player-label');
  const sourcePlayerStatus = document.getElementById('v2-source-player-status');
  const sourcePlay = document.getElementById('v2-source-play');
  const sourceStop = document.getElementById('v2-source-stop');
  const sourceProgress = document.getElementById('v2-source-progress');
  const sourceTime = document.getElementById('v2-source-time');
  let sourcePreviewUrl = '';
  const formatSourceTime = seconds => {
    const value = Number.isFinite(seconds) ? Math.max(0, Math.floor(seconds)) : 0;
    return `${toPersian(Math.floor(value / 60))}:${toPersian(String(value % 60).padStart(2, '0'))}`;
  };
  const syncSourceTimeline = () => {
    const duration = Number.isFinite(sourceAudio?.duration) ? sourceAudio.duration : 0;
    const currentTime = Number.isFinite(sourceAudio?.currentTime) ? sourceAudio.currentTime : 0;
    if (sourceProgress) {
      sourceProgress.disabled = duration <= 0;
      sourceProgress.value = duration > 0 ? String((currentTime / duration) * 100) : '0';
    }
    if (sourceTime) sourceTime.textContent = `${formatSourceTime(currentTime)} / ${formatSourceTime(duration)}`;
  };
  const syncSourcePlayback = () => {
    const playing = Boolean(sourceAudio?.src) && !sourceAudio.paused && !sourceAudio.ended;
    if (sourcePlay) sourcePlay.innerHTML = playing
      ? '<i class="fa-solid fa-pause"></i><span>مکث</span>'
      : '<i class="fa-solid fa-play"></i><span>پخش</span>';
    if (sourcePlayerStatus) sourcePlayerStatus.textContent = playing ? 'در حال پخش' : (sourceAudio?.src ? 'آمادهٔ پخش' : 'منبعی انتخاب نشده است');
  };
  const setSourceAudio = (url, label = 'پیش‌نمایش منبع') => {
    if (!sourceAudio) return;
    if (sourcePreviewUrl.startsWith('blob:')) URL.revokeObjectURL(sourcePreviewUrl);
    sourcePreviewUrl = url || '';
    sourceAudio.pause();
    sourceAudio.removeAttribute('src');
    sourceAudio.load();
    if (sourcePlayer) sourcePlayer.hidden = !sourcePreviewUrl;
    if (sourcePlayerLabel) sourcePlayerLabel.textContent = label;
    if (!sourcePreviewUrl) { syncSourceTimeline(); syncSourcePlayback(); return; }
    sourceAudio.src = sourcePreviewUrl;
    sourceAudio.load();
    if (sourcePlayerStatus) sourcePlayerStatus.textContent = 'در حال آماده‌سازی…';
    syncSourceTimeline();
  };
  sourceSelect?.addEventListener('change', () => {
    const selected = sourceSelect.selectedOptions[0];
    setSourceAudio(selected?.dataset.sourceUrl || '', selected?.textContent?.trim() || 'منبع آرشیو');
  });
  sourceFile?.addEventListener('change', () => {
    const file = sourceFile.files?.[0];
    setSourceAudio(file ? URL.createObjectURL(file) : '', file?.name || 'فایل دستگاه');
  });
  sourcePlay?.addEventListener('click', async () => {
    if (!sourceAudio?.src) return;
    if (sourceAudio.paused || sourceAudio.ended) {
      try { await sourceAudio.play(); } catch (_) { if (sourcePlayerStatus) sourcePlayerStatus.textContent = 'پخش این منبع در مرورگر ممکن نیست.'; }
    } else sourceAudio.pause();
    syncSourcePlayback();
  });
  sourceStop?.addEventListener('click', () => { if (!sourceAudio) return; sourceAudio.pause(); sourceAudio.currentTime = 0; syncSourceTimeline(); syncSourcePlayback(); });
  sourceProgress?.addEventListener('input', () => {
    if (!sourceAudio || !Number.isFinite(sourceAudio.duration) || sourceAudio.duration <= 0) return;
    sourceAudio.currentTime = (Number(sourceProgress.value) / 100) * sourceAudio.duration;
    syncSourceTimeline();
  });
  sourceAudio?.addEventListener('loadedmetadata', () => { syncSourceTimeline(); syncSourcePlayback(); });
  sourceAudio?.addEventListener('timeupdate', syncSourceTimeline);
  sourceAudio?.addEventListener('play', syncSourcePlayback);
  sourceAudio?.addEventListener('pause', syncSourcePlayback);
  sourceAudio?.addEventListener('ended', () => { syncSourceTimeline(); syncSourcePlayback(); });
  sourceAudio?.addEventListener('error', () => { if (sourcePlayerStatus) sourcePlayerStatus.textContent = 'پیش‌نمایش این منبع قابل پخش نیست.'; });
  window.addEventListener('beforeunload', () => { if (sourcePreviewUrl.startsWith('blob:')) URL.revokeObjectURL(sourcePreviewUrl); });
  const initialSource = sourceSelect?.selectedOptions[0];
  setSourceAudio(initialSource?.dataset.sourceUrl || '', initialSource?.textContent?.trim() || 'منبع آرشیو');
  const modernCaptionOption = (index, text, onSelect) => {
    const option = document.createElement('button'); option.type = 'button'; option.className = 'v2-modern-caption-option';
    const title = document.createElement('small'); title.textContent = `گزینه ${toPersian(index + 1)}`;
    option.append(title, document.createTextNode(text));
    option.addEventListener('click', () => { option.parentElement.querySelectorAll('.v2-modern-caption-option').forEach(item => item.classList.remove('is-selected')); option.classList.add('is-selected'); onSelect(text); });
    return option;
  };
  const updateInstagramPreview = () => { document.getElementById('v2-modern-instagram-preview-caption').textContent = document.getElementById('v2-modern-instagram-caption')?.value || 'کپشن اینستاگرام اینجا پیش‌نمایش داده می‌شود.'; };
  const updateTelegramPreview = () => {
    document.getElementById('v2-modern-telegram-preview-caption').textContent = document.getElementById('v2-modern-telegram-caption')?.value || 'کپشن تلگرام اینجا پیش‌نمایش داده می‌شود.';
    const holder = document.getElementById('v2-modern-telegram-preview-buttons'); if (!holder) return; holder.replaceChildren();
    document.querySelectorAll('#v2-modern-telegram-button-list [data-v2-modern-telegram-button-row]').forEach(row => {
      const label = row.querySelector('[name="telegram_button_label[]"]')?.value.trim(); const url = row.querySelector('[name="telegram_button_url[]"]')?.value.trim();
      if (!label || !url) return;
      const button = document.createElement('span'); button.className = `v2-modern-telegram-button ${row.querySelector('[name="telegram_button_style[]"]')?.value || 'primary'} ${row.querySelector('[name="telegram_button_width[]"]')?.value || 'full'}`; button.textContent = label; holder.appendChild(button);
    });
  };
  const renderCaptions = (channel, options) => {
    const holder = document.getElementById(`v2-modern-caption-options-${channel}`);
    const target = document.getElementById(`v2-modern-${channel}-caption`);
    const values = (Array.isArray(options) ? options : []).map(value => String(value || '').trim()).filter(Boolean).slice(0, 3);
    if (!holder || !values.length) return;
    holder.replaceChildren(); values.forEach((text, index) => holder.appendChild(modernCaptionOption(index, text, value => { target.value = value; channel === 'instagram' ? updateInstagramPreview() : updateTelegramPreview(); })));
    holder.firstElementChild?.classList.add('is-selected'); target.value = values[0]; channel === 'instagram' ? updateInstagramPreview() : updateTelegramPreview();
  };
  const renderKeywords = (options, template) => {
    const holder = document.getElementById('v2-modern-keyword-options'); const keyword = document.getElementById('v2-modern-instagram-keyword'); const dm = document.getElementById('v2-modern-instagram-dm-template');
    const values = (Array.isArray(options) ? options : []).map(value => String(value || '').trim()).filter(Boolean).slice(0, 3);
    if (holder && values.length) { holder.replaceChildren(); values.forEach((text, index) => holder.appendChild(modernCaptionOption(index, text, value => { keyword.value = value; }))); holder.firstElementChild?.classList.add('is-selected'); keyword.value = values[0]; }
    if (template) dm.value = template;
  };
  const requestContent = async (channel, button, keywordOnly = false) => {
    if (!product?.value) { window.alert('ابتدا یک محصول انتخاب کنید.'); return; }
    const payload = new FormData(); payload.append('_token', csrf); payload.append('product_id', product.value); payload.append('channel', channel);
    if (channel === 'instagram') payload.append('hook_guidelines', document.getElementById('v2-hook-guidelines')?.value || '');
    button.disabled = true;
    try {
      const response = await fetch('{{ route('admin.video-studio.preview') }}', { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: payload });
      const data = await response.json(); if (!response.ok) throw new Error(data.message || 'ساخت محتوا ناموفق بود.');
      if (keywordOnly) renderKeywords(data.keyword_options || [], data.dm_template || ''); else { renderCaptions(channel, data.caption_options || [data.caption || '']); if (channel === 'instagram') renderKeywords(data.keyword_options || [], data.dm_template || ''); }
    } catch (error) { window.alert(error.message || 'ساخت محتوا ناموفق بود.'); }
    finally { button.disabled = false; }
  };
  document.querySelectorAll('[data-v2-modern-generate]').forEach(button => button.addEventListener('click', () => requestContent(button.dataset.v2ModernGenerate, button)));
  document.querySelectorAll('[data-v2-modern-keyword]').forEach(button => button.addEventListener('click', () => requestContent(button.dataset.v2ModernKeyword, button, true)));
  document.getElementById('v2-modern-instagram-caption')?.addEventListener('input', updateInstagramPreview);
  document.getElementById('v2-modern-telegram-caption')?.addEventListener('input', updateTelegramPreview);

  const telegramButtons = document.getElementById('v2-modern-telegram-button-list');
  const addTelegramButton = () => {
    if (!telegramButtons || telegramButtons.querySelectorAll('[data-v2-modern-telegram-button-row]').length >= 8) return;
    const row = document.createElement('div'); row.className = 'v2-telegram-button-row'; row.dataset.v2ModernTelegramButtonRow = '';
    row.innerHTML = '<input class="v2-input" name="telegram_button_label[]" placeholder="متن دکمه"><input class="v2-input" type="url" name="telegram_button_url[]" placeholder="https://..."><select class="v2-select" name="telegram_button_style[]"><option value="primary">سبز وطن</option><option value="success">سبز موفق</option><option value="danger">قرمز</option></select><input type="hidden" name="telegram_button_width[]" value="full"><button class="v2-telegram-button-remove" type="button" data-v2-modern-remove-button aria-label="حذف دکمه"><i class="fa-solid fa-trash"></i></button>';
    telegramButtons.appendChild(row);
  };
  document.getElementById('v2-modern-add-telegram-button')?.addEventListener('click', addTelegramButton);
  telegramButtons?.addEventListener('click', event => { const remove = event.target.closest('[data-v2-modern-remove-button]'); if (remove && telegramButtons.querySelectorAll('[data-v2-modern-telegram-button-row]').length > 1) remove.closest('[data-v2-modern-telegram-button-row]')?.remove(); updateTelegramPreview(); });
  telegramButtons?.addEventListener('input', updateTelegramPreview); telegramButtons?.addEventListener('change', updateTelegramPreview); updateInstagramPreview(); updateTelegramPreview();

  const colors = @json($hookColors); let colorTarget = 'background';
  const colorTargetMeta = target => ({
    apiTarget: target === 'cta_background' || target === 'cta_text' ? (target === 'cta_background' ? 'background' : 'text') : target,
    inputName: target === 'background' ? 'hook_background' : target === 'text' ? 'hook_text_color' : target === 'cta_background' ? 'cta_background' : 'cta_text_color',
    title: target === 'background' ? 'مدیریت رنگ پس‌زمینه هوک' : target === 'text' ? 'مدیریت رنگ متن هوک' : target === 'cta_background' ? 'مدیریت رنگ پس‌زمینه CTA' : 'مدیریت رنگ متن CTA',
  });
  const colorListTargets = target => (target === 'background' || target === 'cta_background') ? ['background', 'cta_background'] : ['text', 'cta_text'];
  const colorApiPalette = target => colors[colorTargetMeta(target).apiTarget] || (colors[colorTargetMeta(target).apiTarget] = []);
  const colorModal = document.getElementById('v2-modern-color-modal'); const colorList = document.getElementById('v2-modern-color-manager-list');
  const colorName = document.getElementById('v2-modern-color-name'); const colorValue = document.getElementById('v2-modern-color-value');
  const renderColorManager = () => {
    const meta = colorTargetMeta(colorTarget);
    document.getElementById('v2-modern-color-title').textContent = meta.title;
    colorList.replaceChildren();
    (colors[meta.apiTarget] || []).forEach(color => {
      const item = document.createElement('div'); item.className = 'v2-modern-managed-color';
      const swatch = document.createElement('span'); swatch.className = 'v2-modern-managed-swatch'; swatch.style.setProperty('--v2-color', color.css_value);
      const label = document.createElement('span'); label.textContent = color.name;
      item.append(swatch, label);
      const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'v2-modern-managed-remove'; remove.dataset.v2ModernRemoveColor = color.is_custom ? String(color.id) : color.key; remove.dataset.v2ModernColorCustom = color.is_custom ? '1' : '0'; remove.setAttribute('aria-label', `حذف ${color.name}`); remove.innerHTML = '<i class="fa-solid fa-trash"></i>'; item.appendChild(remove);
      colorList.appendChild(item);
    });
  };
  const colorOption = (target, color) => {
    const meta = colorTargetMeta(target);
    const wrapper = document.createElement('div'); wrapper.className = 'v2-modern-color';
    const input = document.createElement('input'); input.type = 'radio'; input.name = meta.inputName; input.value = color.key; input.id = `v2-modern-${target}-${color.key}`; input.dataset.v2ColorCss = color.css_value; input.dataset.v2ColorRender = color.render_value;
    const label = document.createElement('label'); label.htmlFor = input.id; label.title = color.name; label.style.setProperty('--v2-color', color.css_value); input.addEventListener('change', target.startsWith('cta_') ? syncCtaColors : syncHookColors); wrapper.append(input, label); return wrapper;
  };
  const upsertColorOption = (target, color) => {
    const list = document.querySelector(`[data-v2-modern-color-list="${target}"]`);
    if (!list) return;
    const id = `v2-modern-${target}-${color.key}`;
    const existing = document.getElementById(id);
    if (existing) {
      existing.value = color.key;
      existing.dataset.v2ColorCss = color.css_value;
      existing.dataset.v2ColorRender = color.render_value;
      const label = existing.closest('.v2-modern-color')?.querySelector('label');
      if (label) { label.title = color.name; label.style.setProperty('--v2-color', color.css_value); }
      return;
    }
    const add = list.querySelector('[data-v2-modern-open-colors]');
    list.insertBefore(colorOption(target, color), add || null);
  };
  const removeColorOptions = (target, key) => colorListTargets(target).forEach(listTarget => {
    document.getElementById(`v2-modern-${listTarget}-${key}`)?.closest('.v2-modern-color')?.remove();
  });
  const openColorManager = target => { colorTarget = target; renderColorManager(); colorModal?.classList.add('is-open'); };
  document.querySelectorAll('[data-v2-modern-open-colors]').forEach(button => button.addEventListener('click', () => openColorManager(button.dataset.v2ModernOpenColors)));
  const closeColorManager = () => colorModal?.classList.remove('is-open'); document.getElementById('v2-modern-close-colors')?.addEventListener('click', closeColorManager); colorModal?.addEventListener('click', event => { if (event.target === colorModal) closeColorManager(); });
  document.getElementById('v2-modern-save-color')?.addEventListener('click', async event => {
    const value = colorValue.value.trim(); if (!/^#[0-9a-fA-F]{6}$/.test(value)) { window.alert('کد رنگ را به صورت #RRGGBB وارد کنید.'); return; }
    const meta = colorTargetMeta(colorTarget);
    const button = event.currentTarget; button.disabled = true;
    try {
      const response = await fetch('{{ route('admin.video-studio.experimental.hook-colors.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify({ target: meta.apiTarget, name: colorName.value.trim(), color_value: value }) });
      const data = await response.json().catch(() => ({})); if (!response.ok || !data.color) throw new Error(data.message || `ذخیره رنگ ناموفق بود (${response.status}).`);
      const palette = colorApiPalette(colorTarget); const previous = palette.findIndex(color => color.key === data.color.key); if (previous >= 0) palette[previous] = data.color; else palette.push(data.color);
      colorListTargets(colorTarget).forEach(listTarget => upsertColorOption(listTarget, data.color));
      document.getElementById(`v2-modern-${colorTarget}-${data.color.key}`)?.click();
      syncHookColors(); syncCtaColors(); colorName.value = ''; renderColorManager();
    } catch (error) { window.alert(error.message || 'ذخیره رنگ ناموفق بود.'); }
    finally { button.disabled = false; }
  });
  colorList?.addEventListener('click', async event => {
    const button = event.target.closest('[data-v2-modern-remove-color]'); if (!button) return;
    const isCustom = button.dataset.v2ModernColorCustom === '1';
    const colorKey = button.dataset.v2ModernRemoveColor;
    const meta = colorTargetMeta(colorTarget);
    const selected = document.getElementById(`v2-modern-${colorTarget}-${isCustom ? `custom-${colorKey}` : colorKey}`)?.checked;
    try {
      const response = await fetch(isCustom
        ? '{{ route('admin.video-studio.experimental.hook-colors.destroy', ['color' => '__COLOR__']) }}'.replace('__COLOR__', colorKey)
        : '{{ route('admin.video-studio.experimental.hook-colors.defaults.destroy', ['target' => '__TARGET__', 'colorKey' => '__COLOR_KEY__']) }}'.replace('__TARGET__', meta.apiTarget).replace('__COLOR_KEY__', colorKey), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
      if (!response.ok) { const data = await response.json().catch(() => ({})); throw new Error(data.message || `حذف رنگ ناموفق بود (${response.status}).`); }
      const key = isCustom ? `custom-${colorKey}` : colorKey;
      colors[meta.apiTarget] = colorApiPalette(colorTarget).filter(color => color.key !== key);
      removeColorOptions(colorTarget, key);
      if (selected) document.querySelector(`[data-v2-modern-color-list="${colorTarget}"] input`)?.click();
      renderColorManager(); syncHookColors(); syncCtaColors();
    } catch (error) { window.alert(error.message || 'حذف رنگ ناموفق بود.'); }
  });
  form.addEventListener('v2:settings-applied', () => {
    syncHookText(hookManual?.value || hookValue?.value || '');
    syncHookColors();
    syncHookMetrics();
    syncHookDuration();
    syncCtaPreview();
    syncCtaDuration();
    syncSelectedImageOrder();
    updateInstagramPreview();
    updateTelegramPreview();
  });
})();
</script>
