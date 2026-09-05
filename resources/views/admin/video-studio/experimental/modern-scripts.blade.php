<script>
(() => {
  const form = document.getElementById('v2-form');
  if (!form) return;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('[name="_token"]')?.value || '';
  const toPersian = value => String(value).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  const legacyControls = [
    ...form.querySelectorAll('[data-v2-panel="1"] > .v2-hook-font-field,[data-v2-panel="1"] > .v2-hook-workspace,[data-v2-panel="1"] > .v2-cta-card,[data-v2-panel="1"] > .v2-grid'),
    ...form.querySelectorAll('[data-v2-panel="3"] > .v2-platform-grid > .v2-platform--instagram,[data-v2-panel="3"] > .v2-platform-grid > .v2-platform--telegram'),
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
  let sourcePreviewUrl = '';
  const setSourceAudio = url => {
    if (!sourceAudio) return;
    if (sourcePreviewUrl.startsWith('blob:')) URL.revokeObjectURL(sourcePreviewUrl);
    sourcePreviewUrl = url || '';
    sourceAudio.pause();
    sourceAudio.removeAttribute('src');
    if (!sourcePreviewUrl) { sourceAudio.hidden = true; sourceAudio.load(); return; }
    sourceAudio.src = sourcePreviewUrl;
    sourceAudio.hidden = false;
    sourceAudio.load();
  };
  sourceSelect?.addEventListener('change', () => setSourceAudio(sourceSelect.selectedOptions[0]?.dataset.sourceUrl || ''));
  sourceFile?.addEventListener('change', () => setSourceAudio(sourceFile.files?.[0] ? URL.createObjectURL(sourceFile.files[0]) : ''));
  window.addEventListener('beforeunload', () => { if (sourcePreviewUrl.startsWith('blob:')) URL.revokeObjectURL(sourcePreviewUrl); });
  setSourceAudio(sourceSelect?.selectedOptions[0]?.dataset.sourceUrl || '');
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
  const colorModal = document.getElementById('v2-modern-color-modal'); const colorList = document.getElementById('v2-modern-color-manager-list');
  const colorName = document.getElementById('v2-modern-color-name'); const colorValue = document.getElementById('v2-modern-color-value');
  const renderColorManager = () => {
    document.getElementById('v2-modern-color-title').textContent = colorTarget === 'background' ? 'مدیریت رنگ پس‌زمینه هوک' : 'مدیریت رنگ متن هوک';
    colorList.replaceChildren();
    (colors[colorTarget] || []).forEach(color => {
      const item = document.createElement('div'); item.className = 'v2-modern-managed-color';
      const swatch = document.createElement('span'); swatch.className = 'v2-modern-managed-swatch'; swatch.style.setProperty('--v2-color', color.css_value);
      const label = document.createElement('span'); label.textContent = color.name;
      item.append(swatch, label);
      const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'v2-modern-managed-remove'; remove.dataset.v2ModernRemoveColor = color.is_custom ? String(color.id) : color.key; remove.dataset.v2ModernColorCustom = color.is_custom ? '1' : '0'; remove.setAttribute('aria-label', `حذف ${color.name}`); remove.innerHTML = '<i class="fa-solid fa-trash"></i>'; item.appendChild(remove);
      colorList.appendChild(item);
    });
  };
  const colorOption = (target, color) => {
    const wrapper = document.createElement('div'); wrapper.className = 'v2-modern-color';
    const input = document.createElement('input'); input.type = 'radio'; input.name = target === 'background' ? 'hook_background' : 'hook_text_color'; input.value = color.key; input.id = `v2-modern-${target}-${color.key}`; input.dataset.v2ColorCss = color.css_value; input.dataset.v2ColorRender = color.render_value;
    const label = document.createElement('label'); label.htmlFor = input.id; label.title = color.name; label.style.setProperty('--v2-color', color.css_value); input.addEventListener('change', syncHookColors); wrapper.append(input, label); return wrapper;
  };
  const openColorManager = target => { colorTarget = target; renderColorManager(); colorModal?.classList.add('is-open'); };
  document.querySelectorAll('[data-v2-modern-open-colors]').forEach(button => button.addEventListener('click', () => openColorManager(button.dataset.v2ModernOpenColors)));
  const closeColorManager = () => colorModal?.classList.remove('is-open'); document.getElementById('v2-modern-close-colors')?.addEventListener('click', closeColorManager); colorModal?.addEventListener('click', event => { if (event.target === colorModal) closeColorManager(); });
  document.getElementById('v2-modern-save-color')?.addEventListener('click', async event => {
    const value = colorValue.value.trim(); if (!/^#[0-9a-fA-F]{6}$/.test(value)) { window.alert('کد رنگ را به صورت #RRGGBB وارد کنید.'); return; }
    const button = event.currentTarget; button.disabled = true;
    try {
      const response = await fetch('{{ route('admin.video-studio.experimental.hook-colors.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify({ target: colorTarget, name: colorName.value.trim(), color_value: value }) });
      const data = await response.json(); if (!response.ok) throw new Error(data.message || 'ذخیره رنگ ناموفق بود.');
      const previous = colors[colorTarget].findIndex(color => color.key === data.color.key); if (previous >= 0) colors[colorTarget][previous] = data.color; else colors[colorTarget].push(data.color);
      const list = document.querySelector(`[data-v2-modern-color-list="${colorTarget}"]`); const add = list.querySelector('[data-v2-modern-open-colors]'); const option = colorOption(colorTarget, data.color); list.insertBefore(option, add); option.querySelector('input').checked = true; syncHookColors(); colorName.value = ''; renderColorManager();
    } catch (error) { window.alert(error.message || 'ذخیره رنگ ناموفق بود.'); }
    finally { button.disabled = false; }
  });
  colorList?.addEventListener('click', async event => {
    const button = event.target.closest('[data-v2-modern-remove-color]'); if (!button) return;
    const isCustom = button.dataset.v2ModernColorCustom === '1';
    const colorKey = button.dataset.v2ModernRemoveColor;
    const response = await fetch(isCustom
      ? '{{ route('admin.video-studio.experimental.hook-colors.destroy', ['color' => '__COLOR__']) }}'.replace('__COLOR__', colorKey)
      : '{{ route('admin.video-studio.experimental.hook-colors.defaults.destroy', ['target' => '__TARGET__', 'colorKey' => '__COLOR_KEY__']) }}'.replace('__TARGET__', colorTarget).replace('__COLOR_KEY__', colorKey), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
    if (!response.ok) { window.alert('حذف رنگ ناموفق بود.'); return; }
    const key = isCustom ? `custom-${colorKey}` : colorKey; const selected = document.getElementById(`v2-modern-${colorTarget}-${key}`)?.checked;
    colors[colorTarget] = colors[colorTarget].filter(color => color.key !== key); document.getElementById(`v2-modern-${colorTarget}-${key}`)?.closest('.v2-modern-color')?.remove();
    if (selected) document.querySelector(`[data-v2-modern-color-list="${colorTarget}"] input`)?.click();
    renderColorManager(); syncHookColors();
  });
  form.addEventListener('v2:settings-applied', () => {
    syncHookText(hookManual?.value || hookValue?.value || '');
    syncHookColors();
    syncHookMetrics();
    syncHookDuration();
    syncSelectedImageOrder();
    updateInstagramPreview();
    updateTelegramPreview();
  });
})();
</script>
