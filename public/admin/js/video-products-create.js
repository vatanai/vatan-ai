(() => {
  const form = document.getElementById('video-product-form');
  if (!form) return;

  const config = window.VIDEO_PRODUCT_ADMIN || {};
  const modelMap = new Map((config.models || []).map((model) => [String(model.id), model]));
  const featureList = document.getElementById('vpc-feature-list');
  const featureEmpty = document.getElementById('vpc-feature-empty');
  const featuresInput = document.getElementById('vpc-features-json');
  const statusInput = document.getElementById('vpc-status');
  const nextButton = document.getElementById('vpc-next');
  const publishButton = document.getElementById('vpc-publish');
  const previousButton = document.getElementById('vpc-prev');
  const modelSelect = document.getElementById('vpc-model');
  let currentStep = 1;
  let features = Array.isArray(config.features) ? config.features.map(normalizeFeature) : [];

  const featurePresets = {
    direction: { field_id: 'creative_direction', type: 'textarea', label_fa: 'جزئیات صحنه و حرکت', description: 'حرکت سوژه، فضا و اتفاق موردنظر را بنویسید.', placeholder: 'مثلاً نور غروب و حرکت آرام دوربین...', required: true, prompt_mode: 'append', prompt_wrap: 'Creative direction: {value}', options: [] },
    style: { field_id: 'visual_style', type: 'select', label_fa: 'استایل بصری', description: 'حال‌وهوای کلی خروجی', required: true, default: 'cinematic', prompt_mode: 'append', prompt_wrap: 'Visual style: {value}', options: [
      { label: 'سینمایی', value: 'cinematic', prompt: 'Cinematic lighting and filmic color grade.' },
      { label: 'تبلیغاتی', value: 'commercial', prompt: 'Premium commercial lighting and clean composition.' },
      { label: 'طبیعی', value: 'natural', prompt: 'Natural light and documentary realism.' },
    ] },
    mood: { field_id: 'mood', type: 'select', label_fa: 'حس‌وحال', description: 'احساس غالب ویدیو', required: false, default: 'calm', prompt_mode: 'append', prompt_wrap: 'Mood: {value}', options: [
      { label: 'آرام', value: 'calm', prompt: 'Calm pacing and soft natural motion.' },
      { label: 'پر انرژی', value: 'energetic', prompt: 'Energetic pacing with confident motion.' },
      { label: 'دراماتیک', value: 'dramatic', prompt: 'Dramatic atmosphere and expressive lighting.' },
    ] },
    intensity: { field_id: 'motion_intensity', type: 'slider', label_fa: 'شدت حرکت', description: 'مقدار حرکت سوژه و دوربین', required: false, default: 45, min: 10, max: 90, step: 5, unit: '٪', prompt_mode: 'append', prompt_wrap: 'Motion intensity: {value} percent.', options: [] },
    toggle: { field_id: uniqueId('extra_motion'), type: 'switch', label_fa: 'حرکت تکمیلی', description: 'گزینه روشن یا خاموش برای کاربر', required: false, default: '0', prompt_mode: 'append', prompt_wrap: '{value}', options: [] },
    custom: { field_id: uniqueId('custom_feature'), type: 'text', label_fa: 'ویژگی دلخواه', description: '', required: false, prompt_mode: 'append', prompt_wrap: '{value}', options: [] },
  };

  function normalizeFeature(feature, index = 0) {
    const raw = feature || {};
    return {
      field_id: raw.field_id || raw.name || `feature_${index + 1}`,
      type: raw.type || 'text',
      label_fa: raw.label_fa || raw.label || 'ویژگی',
      description: raw.description || raw.help_text || '',
      placeholder: raw.placeholder || '',
      required: raw.required === true || raw.required === 1 || raw.required === '1',
      default: raw.default ?? '',
      min: raw.min ?? '',
      max: raw.max ?? '',
      step: raw.step ?? '',
      unit: raw.unit || '',
      credit_cost: Number(raw.credit_cost || 0),
      prompt_mode: raw.prompt_mode || 'append',
      prompt_wrap: raw.prompt_wrap || '',
      accept: raw.accept || '',
      max_size_mb: Number(raw.max_size_mb || 10),
      options: Array.isArray(raw.options) ? raw.options.map((option) => ({
        label: option.label || option.value || String(option),
        value: option.value || option.label || String(option),
        prompt: option.prompt || '',
        credit: Number(option.credit || 0),
      })) : [],
    };
  }

  function uniqueId(prefix) {
    const used = new Set(features.map((feature) => feature.field_id));
    let suffix = 1;
    let candidate = prefix;
    while (used.has(candidate)) candidate = `${prefix}_${++suffix}`;
    return candidate;
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
  }

  function optionsToText(options) {
    return (options || []).map((option) => [option.label, option.value, option.prompt, option.credit || 0].join('|')).join('\n');
  }

  function textToOptions(value) {
    return String(value || '').split('\n').map((line) => line.trim()).filter(Boolean).map((line) => {
      const [label, optionValue, prompt, credit] = line.split('|').map((item) => item.trim());
      return { label, value: optionValue || label, prompt: prompt || '', credit: Number(credit || 0) };
    });
  }

  function renderFeatures() {
    featureList.innerHTML = features.map((feature, index) => `
      <article class="vpc-feature-item" data-feature-index="${index}">
        <div class="vpc-feature-item-head">
          <i class="fa-solid fa-grip-vertical"></i>
          <b>${escapeHtml(feature.label_fa)}</b>
          <button type="button" data-feature-up title="انتقال به بالا"><i class="fa-solid fa-arrow-up"></i></button>
          <button type="button" data-feature-down title="انتقال به پایین"><i class="fa-solid fa-arrow-down"></i></button>
          <button type="button" data-feature-remove title="حذف ویژگی"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="vpc-feature-item-body">
          <label>عنوان فارسی<input data-key="label_fa" value="${escapeHtml(feature.label_fa)}"></label>
          <label>شناسه انگلیسی<input data-key="field_id" dir="ltr" value="${escapeHtml(feature.field_id)}" pattern="[a-z][a-z0-9_]{1,79}"></label>
          <label>نوع کنترل<select data-key="type">${['text','textarea','select','radio','multi_select','button_group','slider','switch','checkbox','image_upload','file_upload'].map((type) => `<option value="${type}" ${feature.type === type ? 'selected' : ''}>${typeLabel(type)}</option>`).join('')}</select></label>
          <label class="feature-required"><input type="checkbox" data-key="required" ${feature.required ? 'checked' : ''}> برای کاربر الزامی باشد</label>
          <label class="wide">راهنمای کاربر<input data-key="description" value="${escapeHtml(feature.description)}"></label>
          <label class="wide">متن نمونه<input data-key="placeholder" value="${escapeHtml(feature.placeholder)}"></label>
          <label>مقدار پیش‌فرض<input data-key="default" value="${escapeHtml(feature.default)}"></label>
          <label>اعتبار اضافه<input data-key="credit_cost" type="number" min="0" value="${feature.credit_cost}"></label>
          <label>کمینه<input data-key="min" type="number" value="${escapeHtml(feature.min)}"></label>
          <label>بیشینه<input data-key="max" type="number" value="${escapeHtml(feature.max)}"></label>
          <label class="wide">قالب افزوده به پرامپت<input data-key="prompt_wrap" dir="ltr" value="${escapeHtml(feature.prompt_wrap)}" placeholder="Visual style: {value}"></label>
          <label class="wide">گزینه‌ها؛ هر خط: عنوان | مقدار | پرامپت | اعتبار<textarea data-key="options" rows="3" dir="ltr">${escapeHtml(optionsToText(feature.options))}</textarea></label>
        </div>
      </article>
    `).join('');
    featureEmpty.hidden = features.length > 0;
    syncFeaturesInput();
  }

  function typeLabel(type) {
    return ({ text: 'متن کوتاه', textarea: 'متن بلند', select: 'فهرست انتخاب', radio: 'تک‌انتخاب', multi_select: 'چندانتخاب', button_group: 'دکمه گروهی', slider: 'اسلایدر', switch: 'روشن/خاموش', checkbox: 'تأییدیه', image_upload: 'بارگذاری عکس', file_upload: 'بارگذاری فایل' })[type] || type;
  }

  function readFeatures() {
    features = [...featureList.querySelectorAll('[data-feature-index]')].map((item) => {
      const get = (key) => item.querySelector(`[data-key="${key}"]`);
      return normalizeFeature({
        field_id: get('field_id').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_'),
        type: get('type').value,
        label_fa: get('label_fa').value.trim(),
        description: get('description').value.trim(),
        placeholder: get('placeholder').value.trim(),
        required: get('required').checked,
        default: get('default').value,
        credit_cost: Number(get('credit_cost').value || 0),
        min: get('min').value,
        max: get('max').value,
        prompt_mode: 'append',
        prompt_wrap: get('prompt_wrap').value.trim(),
        options: textToOptions(get('options').value),
      });
    });
    syncFeaturesInput();
  }

  function syncFeaturesInput() {
    featuresInput.value = JSON.stringify(features);
  }

  featureList.addEventListener('input', () => {
    readFeatures();
    featureList.querySelectorAll('[data-feature-index]').forEach((item, index) => {
      item.querySelector('.vpc-feature-item-head b').textContent = features[index]?.label_fa || 'ویژگی';
    });
  });
  featureList.addEventListener('click', (event) => {
    const item = event.target.closest('[data-feature-index]');
    if (!item) return;
    const index = Number(item.dataset.featureIndex);
    readFeatures();
    if (event.target.closest('[data-feature-remove]')) features.splice(index, 1);
    if (event.target.closest('[data-feature-up]') && index > 0) [features[index - 1], features[index]] = [features[index], features[index - 1]];
    if (event.target.closest('[data-feature-down]') && index < features.length - 1) [features[index + 1], features[index]] = [features[index], features[index + 1]];
    renderFeatures();
  });
  document.querySelectorAll('[data-add-feature]').forEach((button) => button.addEventListener('click', () => {
    readFeatures();
    const preset = normalizeFeature(structuredClone(featurePresets[button.dataset.addFeature] || featurePresets.custom), features.length);
    if (features.some((feature) => feature.field_id === preset.field_id)) preset.field_id = uniqueId(preset.field_id);
    features.push(preset);
    renderFeatures();
  }));

  function selectedWorkflow() {
    return form.querySelector('[name="workflow"]:checked')?.value || 'text_to_video';
  }

  function filterModels() {
    const workflow = selectedWorkflow();
    [modelSelect, document.getElementById('vpc-fallback-models')].forEach((select) => {
      [...select.options].forEach((option) => {
        if (!option.value) return;
        option.hidden = option.dataset.workflow !== workflow;
        option.disabled = option.hidden;
        if (option.hidden && option.selected) option.selected = false;
      });
    });
    if (!modelSelect.value) {
      const firstCompatible = [...modelSelect.options].find((option) => option.value && !option.disabled);
      if (firstCompatible) modelSelect.value = firstCompatible.value;
    }
    const faceCard = document.querySelector('[data-face-profile-card]');
    faceCard.style.display = workflow === 'image_to_video' ? '' : 'none';
    if (workflow !== 'image_to_video') {
      const disabled = form.querySelector('[name="face_profile_mode"][value="disabled"]');
      if (disabled) disabled.checked = true;
    }
    updateModelSummary();
  }

  function updateModelSummary() {
    const summary = document.getElementById('vpc-model-summary');
    const model = modelMap.get(String(modelSelect.value));
    if (!model) {
      summary.innerHTML = '<i class="fa-solid fa-circle-info"></i><span>یک مدل سازگار انتخاب کنید</span>';
      return;
    }
    const caps = model.capabilities || {};
    const chips = [model.provider, workflowLabel(caps.task_type), caps.supports_image ? 'ورودی عکس' : null, caps.supports_video ? 'ورودی ویدیو' : null, caps.supports_audio ? 'صدا' : null, caps.supports_first_last_frame ? 'فریم اول و آخر' : null].filter(Boolean);
    summary.innerHTML = `<strong>${escapeHtml(model.name)}</strong>${chips.map((chip) => `<span>${escapeHtml(chip)}</span>`).join('')}`;
  }

  function workflowLabel(value) {
    return ({ text_to_video: 'متن به ویدیو', image_to_video: 'عکس به ویدیو', video_to_video: 'ویدیو به ویدیو', face_animation: 'متحرک‌سازی چهره' })[value] || value;
  }

  form.querySelectorAll('[name="workflow"]').forEach((input) => input.addEventListener('change', filterModels));
  modelSelect.addEventListener('change', updateModelSummary);

  document.querySelectorAll('.vpc-duration').forEach((item) => {
    const checkbox = item.querySelector('[name="durations[]"]');
    checkbox.addEventListener('change', () => {
      item.classList.toggle('is-enabled', checkbox.checked);
      syncDurationDefaults();
    });
  });

  function syncDurationDefaults() {
    const enabled = [...form.querySelectorAll('[name="durations[]"]:checked')].map((input) => input.value);
    const select = document.getElementById('vpc-default-duration');
    [...select.options].forEach((option) => { option.hidden = !enabled.includes(option.value); option.disabled = option.hidden; });
    if (!enabled.includes(select.value) && enabled[0]) select.value = enabled[0];
  }

  function showStep(step) {
    currentStep = Math.max(1, Math.min(6, Number(step)));
    document.querySelectorAll('[data-step-panel]').forEach((panel) => panel.classList.toggle('is-active', Number(panel.dataset.stepPanel) === currentStep));
    document.querySelectorAll('[data-step-tab]').forEach((tab) => {
      const tabStep = Number(tab.dataset.stepTab);
      tab.classList.toggle('is-active', tabStep === currentStep);
      tab.classList.toggle('is-done', stepComplete(tabStep));
    });
    previousButton.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
    nextButton.hidden = currentStep === 6;
    publishButton.hidden = currentStep !== 6;
    document.getElementById('vpc-progress-label').textContent = `گام ${currentStep} از ۶`;
    document.getElementById('vpc-progress-bar').style.width = `${(currentStep / 6) * 100}%`;
    if (currentStep === 6) updateReview();
    document.getElementById('content')?.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function stepComplete(step) {
    const value = (name) => String(form.elements[name]?.value || '').trim();
    if (step === 1) return !!(value('name_fa') && value('name_en') && value('slug') && form.querySelector('[name="category_ids[]"]:checked'));
    if (step === 2) return !!(form.querySelector('[name="workflow"]:checked') && form.querySelector('[name="face_profile_mode"]:checked'));
    if (step === 3) return !!(value('model_id') && value('prompt_template'));
    if (step === 4) { readFeatures(); return features.some((item) => item.required === '1' || item.required === 1 || item.required === true); }
    if (step === 5) return !!(form.querySelector('[name="durations[]"]:checked') && form.querySelector('[name="aspect_ratios[]"]:checked') && form.querySelector('[name="resolutions[]"]:checked') && [...form.querySelectorAll('[name^="credit_costs_by_duration"]')].some((input) => Number(input.value) >= 0 && input.value !== ''));
    return [1, 2, 3, 4, 5].every(stepComplete);
  }

  function syncStepStates() {
    document.querySelectorAll('[data-step-tab]').forEach((tab) => tab.classList.toggle('is-done', stepComplete(Number(tab.dataset.stepTab))));
  }

  function panelIsValid() {
    const panel = form.querySelector(`[data-step-panel="${currentStep}"]`);
    const invalid = [...panel.querySelectorAll('input,select,textarea')].find((field) => !field.checkValidity());
    if (invalid) { invalid.reportValidity(); invalid.focus(); return false; }
    if (currentStep === 1 && !form.querySelector('[name="category_ids[]"]:checked')) {
      window.alert('حداقل یک دسته‌بندی انتخاب کنید.'); return false;
    }
    if (currentStep === 5 && !form.querySelector('[name="durations[]"]:checked')) {
      window.alert('حداقل یک مدت برای ساخت فعال کنید.'); return false;
    }
    if (currentStep === 4) {
      readFeatures();
      if (!features.length) { window.alert('حداقل یک ویژگی برای فرم کاربر اضافه کنید.'); return false; }
    }
    if (currentStep === 5 && (!form.querySelector('[name="aspect_ratios[]"]:checked') || !form.querySelector('[name="resolutions[]"]:checked'))) {
      window.alert('حداقل یک نسبت تصویر و یک کیفیت خروجی انتخاب کنید.'); return false;
    }
    return true;
  }

  function updateReview() {
    readFeatures();
    const model = modelMap.get(String(modelSelect.value));
    const durations = [...form.querySelectorAll('[name="durations[]"]:checked')].map((input) => `${input.value} ثانیه`).join('، ');
    const ratios = [...form.querySelectorAll('[name="aspect_ratios[]"]:checked')].map((input) => input.value).join('، ');
    const review = [
      ['fa-cube', 'نام محصول', form.elements.name_fa.value || '—'],
      ['fa-route', 'سناریو', workflowLabel(selectedWorkflow())],
      ['fa-microchip', 'مدل اصلی', model?.name || 'انتخاب نشده'],
      ['fa-sliders', 'ویژگی‌ها', `${features.length} ویژگی`],
      ['fa-clock', 'مدت‌های فعال', durations || '—'],
      ['fa-crop-simple', 'نسبت‌ها', ratios || '—'],
    ];
    document.getElementById('vpc-review-list').innerHTML = review.map(([icon, label, value]) => `<div><i class="fa-solid ${icon}"></i><span><b>${label}</b><strong>${escapeHtml(value)}</strong></span></div>`).join('');
  }

  document.querySelectorAll('[data-step-tab]').forEach((tab) => tab.addEventListener('click', () => showStep(tab.dataset.stepTab)));
  nextButton.addEventListener('click', () => { if (panelIsValid()) showStep(currentStep + 1); });
  previousButton.addEventListener('click', () => showStep(currentStep - 1));
  document.getElementById('vpc-draft').addEventListener('click', () => {
    readFeatures(); statusInput.value = 'draft'; HTMLFormElement.prototype.submit.call(form);
  });
  publishButton.addEventListener('click', () => {
    readFeatures(); statusInput.value = 'active'; form.requestSubmit();
  });
  form.addEventListener('submit', readFeatures);
  form.addEventListener('input', syncStepStates);
  form.addEventListener('change', syncStepStates);

  const nameEn = form.elements.name_en;
  const slug = document.getElementById('vpc-slug');
  nameEn?.addEventListener('input', () => {
    if (slug.dataset.touched === '1') return;
    slug.value = nameEn.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  });
  slug?.addEventListener('input', () => { slug.dataset.touched = '1'; });

  form.querySelector('[data-preview-image]')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0]; if (!file) return;
    const preview = form.querySelector('[data-image-preview]'); preview.src = URL.createObjectURL(file);
  });
  form.querySelector('[data-preview-video]')?.addEventListener('change', (event) => {
    const file = event.target.files?.[0]; if (!file) return;
    const preview = form.querySelector('[data-video-preview]'); preview.src = URL.createObjectURL(file); preview.play().catch(() => {});
  });

  renderFeatures();
  filterModels();
  syncDurationDefaults();
  showStep(1);
  syncStepStates();
})();
