(() => {
  const form = document.getElementById('video-product-v2-form');
  if (!form) return;

  const config = window.VIDEO_PRODUCT_ADMIN_V2 || {};
  const modelMap = new Map((config.models || []).map((model) => [String(model.id), model]));
  const modelSelect = document.getElementById('vpc2-model');
  const fallbackSelect = document.getElementById('vpc2-fallback-models');
  const timelineInput = document.getElementById('vpc2-timeline-json');
  const timelineList = document.getElementById('vpc2-timeline-list');
  const maxShotsInput = document.getElementById('vpc2-max-shots');
  const statusInput = document.getElementById('vpc2-status');
  const nextButton = document.getElementById('vpc2-next');
  const publishButton = document.getElementById('vpc2-publish');
  const previousButton = document.getElementById('vpc2-prev');
  const familyLabels = { shop: 'فروشگاهی', face: 'چهره‌محور', hybrid: 'ترکیبی', music_ready: 'آماده با موزیک' };
  const workflowLabel = { image_to_video: 'عکس به ویدیو', text_to_video: 'متن به ویدیو', video_to_video: 'ویدیو به ویدیو' };
  const transitionLabels = { cut: 'کات مستقیم', dissolve: 'حل شدن', fade: 'فید' };
  let currentStep = 1;
  let familyInitialized = false;
  let timeline = readJson(timelineInput?.value, []);

  function readJson(value, fallback) { try { const parsed = JSON.parse(value || ''); return Array.isArray(parsed) ? parsed : fallback; } catch (_) { return fallback; } }
  function escapeHtml(value) { return String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character])); }
  function checked(name) { return form.querySelector(`[name="${name}"][type="checkbox"]`); }
  function selectedFamily() { return form.querySelector('[name="product_family"]:checked')?.value || 'shop'; }
  function isChecked(name) { return !!checked(name)?.checked; }
  function setChecked(name, value) { const input = checked(name); if (input) input.checked = !!value; }
  function multiShotEnabled() { return document.getElementById('vpc2-multi-shot-value')?.value === '1' || selectedFamily() === 'music_ready'; }

  function mirroredOutputInputs(alias) {
    return [...form.querySelectorAll(`[name="${alias}[]"][type="checkbox"]`)];
  }

  function syncCanonicalOutputs() {
    const target = document.getElementById('vpc2-canonical-output-fields');
    if (!target) return;
    target.querySelectorAll('[data-vpc2-canonical-value]').forEach((input) => input.remove());
    [['allowed_aspect_ratios', 'aspect_ratios'], ['allowed_resolutions', 'resolutions']].forEach(([alias, canonical]) => {
      mirroredOutputInputs(alias).filter((input) => input.checked && !input.disabled).forEach((input) => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = `${canonical}[]`; hidden.value = canonical === 'resolutions' ? videoResolutionValue(input.value) : input.value; hidden.dataset.vpc2CanonicalValue = '1';
        target.appendChild(hidden);
      });
    });
    const syncDefault = (alias, canonical) => {
      const selected = mirroredOutputInputs(alias).find((input) => input.checked && !input.disabled)?.value || '';
      const hidden = target.querySelector(`[name="default_${canonical}"]`);
      if (hidden && selected) hidden.value = canonical === 'resolution' ? videoResolutionValue(selected) : selected;
    };
    syncDefault('allowed_aspect_ratios', 'aspect_ratio');
    syncDefault('allowed_resolutions', 'resolution');
  }

  function videoResolutionValue(value) {
    const normalized = String(value || '');
    if (['4k', '2160', '2160p', '1440', '1440p'].includes(normalized.toLowerCase())) return '4K';
    return normalized.endsWith('p') ? normalized : `${normalized}p`;
  }

  function syncPhotoStepFields() {
    const categoryTarget = document.getElementById('vpc2-photo-category-fields');
    if (categoryTarget) {
      categoryTarget.innerHTML = '';
      const categoryIds = [...document.querySelectorAll('#cat-tags-wrap [data-cat-id]')].map((chip) => chip.dataset.catId).filter(Boolean);
      // پیش از رندر اولیه‌ی چیپ‌ها، آرایه‌ی مشترک پارشیال منبع قابل استفاده است.
      if (!categoryIds.length && typeof selectedCategories !== 'undefined' && Array.isArray(selectedCategories)) {
        selectedCategories.forEach((category) => { if (category?.id) categoryIds.push(String(category.id)); });
      }
      [...new Set(categoryIds)].forEach((id) => {
        const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'category_ids[]'; hidden.value = id; categoryTarget.appendChild(hidden);
      });
    }
    const tagsTarget = document.getElementById('vpc2-photo-tags');
    if (tagsTarget) {
      tagsTarget.value = [...document.querySelectorAll('#tags-wrap [data-tag-chip]')].map((chip) => chip.dataset.tagKey || chip.firstChild?.textContent?.trim() || '').filter(Boolean).join('، ');
    }
  }

  function wirePhotoTagInput() {
    const input = document.getElementById('tags-raw');
    const wrap = document.getElementById('tags-wrap');
    if (!input || !wrap) return;
    input.addEventListener('keydown', (event) => {
      if (!['Enter', ','].includes(event.key)) return;
      event.preventDefault();
      const value = input.value.trim().replace(/^#+/, '').trim();
      if (!value) return;
      const key = value.toLocaleLowerCase();
      if ([...wrap.querySelectorAll('[data-tag-chip]')].some((chip) => (chip.dataset.tagKey || chip.firstChild?.textContent || '').trim().toLocaleLowerCase() === key)) { input.value = ''; return; }
      const chip = document.createElement('span'); chip.dataset.tagChip = ''; chip.dataset.tagKey = key; chip.className = 'inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]';
      chip.appendChild(document.createTextNode(value));
      const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1'; remove.setAttribute('aria-label', 'حذف برچسب'); remove.textContent = '×'; remove.addEventListener('click', () => { chip.remove(); syncPhotoStepFields(); });
      chip.appendChild(remove); wrap.insertBefore(chip, input); input.value = ''; syncPhotoStepFields();
    });
  }

  function applyFamilyDefaults() {
    const family = selectedFamily();
    // تنظیمات ذخیره‌شده‌ی محصول هنگام باز کردن فرم نباید بازنویسی شوند؛
    // فقط بعد از انتخاب خانواده توسط ادمین، قرارداد پیشنهادی اعمال می‌شود.
    if (familyInitialized) {
      setChecked('input_product_image', ['shop', 'hybrid', 'music_ready'].includes(family));
      setChecked('input_face_image', ['face', 'hybrid'].includes(family));
      setChecked('allow_face_profile', ['face', 'hybrid'].includes(family));
      const profileMode = form.querySelector(`[name="face_profile_mode"][value="${['face', 'hybrid'].includes(family) ? 'optional' : 'disabled'}"]`);
      if (profileMode) profileMode.checked = true;
    }
    if (family === 'music_ready') {
      setChecked('input_product_image', true);
      const structureInput = document.getElementById('vpc2-multi-shot-value');
      const multiShotRadio = form.querySelector('[name="video_structure"][value="multi_shot"]');
      if (structureInput) structureInput.value = '1';
      if (multiShotRadio) multiShotRadio.checked = true;
        const music = form.querySelector('[name="music_mode"][value="required"]');
        if (music) music.checked = true;
    }
    familyInitialized = true;
    syncTimelineVisibility();
    syncStepStates();
  }

  function syncTimelineVisibility() {
    const multiShot = multiShotEnabled();
    document.querySelectorAll('[data-vpc2-multi-shot-only]').forEach((element) => { element.hidden = !multiShot; });
    if (!multiShot && currentStep === 5) showStep(6);
  }

  function filterPrimaryModels() {
    [...modelSelect.options].forEach((option) => {
      if (!option.value) return;
      const isOpenRouter = option.dataset.provider === 'openrouter';
      option.hidden = !isOpenRouter;
      option.disabled = !isOpenRouter;
      if (option.disabled && option.selected) option.selected = false;
    });
    if (!modelSelect.value) {
      const first = [...modelSelect.options].find((option) => option.value && !option.disabled);
      if (first) modelSelect.value = first.value;
    }
  }

  function filterFallbackModels() {
    const primaryId = String(modelSelect.value || '');
    [...fallbackSelect.options].forEach((option) => {
      option.disabled = option.value === primaryId;
      if (option.disabled) option.selected = false;
    });
    if ([...fallbackSelect.selectedOptions].length > 3) {
      [...fallbackSelect.selectedOptions].slice(3).forEach((option) => { option.selected = false; });
    }
  }

  function updateModelSummary() {
    const summary = document.getElementById('vpc2-model-summary');
    const model = modelMap.get(String(modelSelect.value));
    if (!model) { summary.innerHTML = '<i class="fa-solid fa-circle-info"></i><span>یک مدل اصلی از `OpenRouter` انتخاب کنید.</span>'; return; }
    const caps = model.capabilities || {};
    const chips = [
      model.provider === 'openrouter' ? 'OpenRouter' : model.provider,
      workflowLabel[caps.task_type || model.task_type] || caps.task_type || model.task_type,
      caps.supports_image ? 'ورودی عکس' : null,
      caps.supports_audio ? 'صدا' : null,
      caps.durations?.length ? `${caps.durations.join('، ')} ثانیه` : null,
      caps.aspect_ratios?.length ? `${caps.aspect_ratios.length} نسبت تصویر` : null,
    ].filter(Boolean);
    summary.classList.toggle('is-warning', model.provider !== 'openrouter');
    summary.innerHTML = `<strong>${escapeHtml(model.name)}</strong>${chips.map((chip) => `<span>${escapeHtml(chip)}</span>`).join('')}`;
    syncCapabilityOptions(caps);
  }

  function syncCapabilityOptions(caps) {
    const restrict = (selector, values, normalize = (value) => String(value)) => {
      const allowed = (values || []).map(String);
      if (!allowed.length) return;
      form.querySelectorAll(selector).forEach((input) => {
        const supported = allowed.map(normalize).includes(normalize(input.value));
        input.disabled = !supported;
        input.closest('label,.vpc2-duration')?.classList.toggle('is-unsupported', !supported);
        if (!supported) input.checked = false;
      });
    };
    restrict('[name="durations[]"]', caps.durations);
    restrict('[name="allowed_aspect_ratios[]"]', caps.aspect_ratios);
    restrict('[name="allowed_resolutions[]"]', caps.resolutions, videoResolutionValue);
    syncCanonicalOutputs();
    syncDurationState();
    ['aspect_ratios', 'resolutions'].forEach((name) => {
      const alias = name === 'aspect_ratios' ? 'allowed_aspect_ratios' : 'allowed_resolutions';
      const inputs = mirroredOutputInputs(alias).filter((input) => input.checked && !input.disabled);
      const defaultSelect = form.querySelector(`[name="default_${name === 'aspect_ratios' ? 'aspect_ratio' : 'resolution'}"]`);
      if (defaultSelect && inputs.length && !inputs.some((input) => input.value === defaultSelect.value)) defaultSelect.value = inputs[0].value;
    });
  }

  function syncDurationState() {
    form.querySelectorAll('.vpc2-duration').forEach((item) => item.classList.toggle('is-enabled', !!item.querySelector('[name="durations[]"]')?.checked));
    const enabled = [...form.querySelectorAll('[name="durations[]"]:checked')].map((input) => input.value);
    const select = document.getElementById('vpc2-default-duration');
    [...select.options].forEach((option) => { option.hidden = !enabled.includes(option.value); option.disabled = option.hidden; });
    if (!enabled.includes(select.value) && enabled[0]) select.value = enabled[0];
  }

  function renderTimeline() {
    if (!timelineList) return;
    const max = Math.max(1, Math.min(10, Number(maxShotsInput?.value || 1)));
    timeline = timeline.slice(0, max);
    timelineList.innerHTML = timeline.map((shot, index) => {
      const roles = Array.isArray(shot.input_roles) ? shot.input_roles : [];
      return `<div class="vpc2-shot-row" data-shot-index="${index}">
        <label>عنوان پلان<input data-shot-key="title" value="${escapeHtml(shot.title || `پلان ${index + 1}`)}"></label>
        <label>ثانیه<input data-shot-key="duration" type="number" min="1" max="15" value="${Number(shot.duration || 4)}"></label>
        <label>پرامپت پلان<textarea data-shot-key="prompt" dir="ltr">${escapeHtml(shot.prompt || '')}</textarea></label>
        <div><span class="vpc2-shot-label">ورودی پلان</span><div class="vpc2-shot-roles"><label><input data-shot-role="face" type="checkbox" ${roles.includes('face') ? 'checked' : ''}><span>چهره</span></label><label><input data-shot-role="product" type="checkbox" ${roles.includes('product') ? 'checked' : ''}><span>محصول</span></label></div><select data-shot-key="transition" aria-label="انتقال"><option value="cut" ${shot.transition === 'cut' ? 'selected' : ''}>${transitionLabels.cut}</option><option value="dissolve" ${shot.transition === 'dissolve' ? 'selected' : ''}>${transitionLabels.dissolve}</option><option value="fade" ${shot.transition === 'fade' ? 'selected' : ''}>${transitionLabels.fade}</option></select></div>
        <button type="button" class="vpc2-shot-remove" data-shot-remove title="حذف پلان"><i class="fa-solid fa-trash"></i></button>
      </div>`;
    }).join('');
    syncTimeline();
  }

  function readTimeline() {
    timeline = [...(timelineList?.querySelectorAll('[data-shot-index]') || [])].map((row, index) => ({
      id: timeline[index]?.id || `shot_${index + 1}`,
      title: row.querySelector('[data-shot-key="title"]')?.value.trim() || `پلان ${index + 1}`,
      duration: Math.max(1, Math.min(15, Number(row.querySelector('[data-shot-key="duration"]')?.value || 4))),
      prompt: row.querySelector('[data-shot-key="prompt"]')?.value.trim() || '',
      input_roles: [...row.querySelectorAll('[data-shot-role]:checked')].map((input) => input.dataset.shotRole),
      transition: row.querySelector('[data-shot-key="transition"]')?.value || 'cut',
      music_start: timeline[index]?.music_start || 0,
    }));
    syncTimeline();
  }
  function syncTimeline() { if (timelineInput) timelineInput.value = JSON.stringify(timeline); renderUserPreview(); }

  function syncPlanRange() {
    if (!maxShotsInput) return;
    const value = Math.max(1, Math.min(10, Number(maxShotsInput.value || 1)));
    maxShotsInput.value = String(value);
    maxShotsInput.style.setProperty('--range-value', String(value));
    const output = document.getElementById('vpc2-max-shots-value');
    if (output) output.textContent = `${value} پلان`;
    const timelineLabel = document.getElementById('vpc2-timeline-max-label');
    if (timelineLabel) timelineLabel.textContent = `${value} پلان طبق تنظیم گام ۲`;
  }

  function showStep(step) {
    currentStep = Math.max(1, Math.min(6, Number(step)));
    if (currentStep === 5 && !multiShotEnabled()) currentStep = 6;
    document.querySelectorAll('[data-vpc2-step-panel]').forEach((panel) => panel.classList.toggle('is-active', Number(panel.dataset.vpc2StepPanel) === currentStep));
    document.querySelectorAll('[data-vpc2-step-tab]').forEach((tab) => { const n = Number(tab.dataset.vpc2StepTab); tab.classList.toggle('is-active', n === currentStep); tab.classList.toggle('is-done', n < currentStep && stepComplete(n)); });
    previousButton.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
    nextButton.hidden = currentStep === 6;
    publishButton.hidden = currentStep !== 6;
    const progressStep = multiShotEnabled() || currentStep < 5 ? currentStep : 5;
    const progressTotal = multiShotEnabled() ? 6 : 5;
    document.getElementById('vpc2-progress-label').textContent = `گام ${progressStep} از ${progressTotal}`;
    document.getElementById('vpc2-progress-bar').style.width = `${(progressStep / progressTotal) * 100}%`;
    if (currentStep === 6) updateReview();
    document.getElementById('content')?.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function stepComplete(step) {
    const value = (name) => String(form.elements[name]?.value || '').trim();
    if (step === 1) {
      const family = selectedFamily();
      const ratios = mirroredOutputInputs('allowed_aspect_ratios').some((input) => input.checked && !input.disabled);
      const resolutions = mirroredOutputInputs('allowed_resolutions').some((input) => input.checked && !input.disabled);
      syncPhotoStepFields();
      return !!(value('name_fa') && value('name_en') && value('slug') && document.querySelectorAll('#vpc2-photo-category-fields [name="category_ids[]"]').length && ratios && resolutions && family && (family === 'shop' ? isChecked('input_product_image') : family === 'face' ? isChecked('input_face_image') : family === 'hybrid' ? isChecked('input_product_image') && isChecked('input_face_image') : (isChecked('input_product_image') || isChecked('input_face_image'))));
    }
    if (step === 2) return !!value('prompt_template');
    if (step === 3) return !!modelSelect.value && modelMap.get(String(modelSelect.value))?.provider === 'openrouter';
    if (step === 4) return !!form.querySelector('[name="durations[]"]:checked');
    if (step === 5) return !multiShotEnabled() || timeline.length > 0;
    return [1, 2, 3, 4, 5].every(stepComplete);
  }

  function validatePanel() {
    if (currentStep === 1 && selectedFamily() === 'hybrid' && !(isChecked('input_product_image') && isChecked('input_face_image'))) { window.alert('محصول ترکیبی باید هر دو ورودی عکس محصول و چهره را داشته باشد.'); return false; }
    if (currentStep === 1 && !stepComplete(1)) { window.alert('اطلاعات اصلی، دسته‌بندی، حداقل یک نسبت تصویر و حداقل یک رزولوشن را کامل کنید.'); return false; }
    if (currentStep === 3 && modelMap.get(String(modelSelect.value))?.provider !== 'openrouter') { window.alert('مدل اصلی محصولات ویدیویی باید از `OpenRouter` انتخاب شود.'); return false; }
    if (currentStep === 4 && !stepComplete(4)) { window.alert('حداقل یک مدت سازگار انتخاب کنید.'); return false; }
    if (currentStep === 5 && !stepComplete(5)) { window.alert('برای ساخت چندپلان، حداقل یک پلان تعریف کنید.'); return false; }
    const panel = form.querySelector(`[data-vpc2-step-panel="${currentStep}"]`);
    const invalid = [...panel.querySelectorAll('input,select,textarea')].find((field) => !field.disabled && !field.checkValidity());
    if (invalid) { invalid.reportValidity(); invalid.focus(); return false; }
    return true;
  }

  function updateReview() {
    readTimeline();
    const model = modelMap.get(String(modelSelect.value));
    const inputs = [isChecked('input_product_image') ? 'عکس محصول' : null, isChecked('input_face_image') ? 'عکس چهره' : null, isChecked('allow_face_profile') ? 'پروفایل چهره' : null].filter(Boolean).join('، ') || 'بدون ورودی';
    const durations = [...form.querySelectorAll('[name="durations[]"]:checked')].map((input) => `${input.value} ثانیه`).join('، ') || '—';
    const ratios = mirroredOutputInputs('allowed_aspect_ratios').filter((input) => input.checked && !input.disabled).map((input) => input.value).join('، ') || '—';
    const structure = multiShotEnabled() ? `چندپلان (${timeline.length} پلان)` : 'تک‌پلان';
    const rows = [['fa-shapes', 'نوع محصول', familyLabels[selectedFamily()] || selectedFamily()], ['fa-images', 'ورودی‌ها', inputs], ['fa-microchip', 'مدل اصلی', model?.name || '—'], ['fa-clock', 'مدت‌ها', durations], ['fa-crop-simple', 'نسبت‌ها', ratios], ['fa-film', 'ساختار', structure]];
    document.getElementById('vpc2-review-list').innerHTML = rows.map(([icon, label, value]) => `<div><i class="fa-solid ${icon}"></i><span><b>${label}</b><strong>${escapeHtml(value)}</strong></span></div>`).join('');
  }

  function selectedValues(selector) { return [...form.querySelectorAll(selector)].filter((input) => input.checked && !input.disabled).map((input) => input.value); }
  function previewResolution(value) {
    const labels = { '480': '480p', '480p': '480p', '720': '720p', '720p': '720p', '1080': '1080p', '1080p': '1080p', '1440': '4K', '2160': '4K', '4K': '4K' };
    return labels[String(value)] || String(value || '—');
  }
  function renderUserPreview() {
    const root = document.getElementById('vpc2-user-preview');
    if (!root) return;
    const family = selectedFamily();
    const productName = String(form.elements.name_fa?.value || 'محصول ویدیویی جدید').trim();
    const cover = config.cover ? `<img src="${escapeHtml(config.cover)}" alt="کاور ${escapeHtml(productName)}">` : '<span class="vpc2-preview-cover-placeholder"><i class="fa-solid fa-video"></i></span>';
    const inputs = [
      isChecked('input_product_image') ? '<div class="vpc2-preview-option"><i class="fa-solid fa-box"></i><span><b>عکس محصول</b><small>ورودی اصلی محصول</small></span></div>' : '',
      isChecked('input_face_image') ? '<div class="vpc2-preview-option"><i class="fa-solid fa-user"></i><span><b>عکس چهره</b><small>برای سوژه‌ی انسانی</small></span></div>' : '',
    ].filter(Boolean).join('');
    const faceProfile = isChecked('allow_face_profile') && isChecked('input_face_image')
      ? '<div class="vpc2-preview-inline-note"><i class="fa-solid fa-id-card"></i> امکان انتخاب <b>پروفایل چهره</b> برای کاربر فعال است.</div>' : '';
    const motionCatalog = config.motion_catalog || {};
    const motions = selectedValues('[name="motion_presets[]"]').map((key) => motionCatalog[key]).filter(Boolean).map((motion) => `<div class="vpc2-preview-motion"><i class="fa-solid fa-camera-retro"></i><b>${escapeHtml(motion.label)}</b><small>${escapeHtml(motion.description)}</small></div>`).join('');
    const motionBlock = motions ? `<div class="vpc2-preview-motion-grid">${motions}</div>` : '<div class="vpc2-preview-empty"><i class="fa-solid fa-ban"></i> برای این محصول گزینه‌ی حرکت جداگانه فعال نشده است.</div>';
    const durations = selectedValues('[name="durations[]"]');
    const ratios = selectedValues('[name="allowed_aspect_ratios[]"]');
    const resolutions = selectedValues('[name="allowed_resolutions[]"]').map(previewResolution);
    const timelineRows = timeline.length ? timeline.map((shot, index) => `<span><b>${escapeHtml(shot.title || `پلان ${index + 1}`)}</b><small>${Number(shot.duration || 0)} ثانیه</small></span>`).join('') : '<div class="vpc2-preview-empty"><i class="fa-solid fa-circle-exclamation"></i> هنوز پلان تعریف نشده است.</div>';
    const totalSeconds = timeline.reduce((sum, shot) => sum + Number(shot.duration || 0), 0);
    const structureBlock = multiShotEnabled() ? `<div class="vpc2-preview-section"><div class="vpc2-preview-section-title"><i class="fa-solid fa-film"></i><b>سناریوی چندپلان</b><small>${timeline.length} پلان${totalSeconds ? `، ${totalSeconds} ثانیه مجموع` : ''}</small></div><div class="vpc2-preview-shot-list">${timelineRows}</div></div>` : '';
    const musicMode = form.querySelector('[name="music_mode"]:checked')?.value || 'disabled';
    const musicText = { disabled: 'بدون موسیقی', optional: 'موسیقی اختیاری', required: 'موسیقی ثابت محصول' }[musicMode] || 'بدون موسیقی';
    const promptMode = isChecked('show_prompt_to_user') && form.querySelector('[name="prompt_mode"]:checked')?.value === 'custom';
    const output = [durations.length ? `${durations.join('، ')} ثانیه` : '—', ratios.length ? ratios.join('، ') : '—', resolutions.length ? resolutions.join('، ') : '—'];
    root.innerHTML = `<div class="vpc2-preview-shell">
      <div class="vpc2-preview-product-head">${cover}<div><small>محصول ${escapeHtml(familyLabels[family] || family)}</small><h3>${escapeHtml(productName)}</h3><span><i class="fa-solid fa-clock"></i> زمان پردازش تقریبی: ${escapeHtml(form.elements.estimated_time?.value || '180')} ثانیه</span></div></div>
      <div class="vpc2-preview-section"><div class="vpc2-preview-section-title"><i class="fa-solid fa-cloud-arrow-up"></i><b>ورودی‌های شما</b><small>فقط موارد فعال‌شده نمایش داده می‌شوند.</small></div><div class="vpc2-preview-grid">${inputs || '<div class="vpc2-preview-empty"><i class="fa-solid fa-circle-exclamation"></i> ورودی‌ای برای این محصول فعال نشده است.</div>'}</div>${faceProfile}</div>
      <div class="vpc2-preview-section"><div class="vpc2-preview-section-title"><i class="fa-solid fa-wand-magic-sparkles"></i><b>منطق ساخت</b><small>${promptMode ? 'توضیح صحنه برای کاربر قابل تکمیل است.' : 'دستور ساخت محصول از پیش آماده است.'}</small></div><div class="vpc2-preview-prompt"><i class="fa-solid ${promptMode ? 'fa-pen-to-square' : 'fa-lock'}"></i><span>${promptMode ? 'شرح صحنه و حرکت' : 'دستور ساخت آماده و کنترل‌شده'}</span></div></div>
      <div class="vpc2-preview-section"><div class="vpc2-preview-section-title"><i class="fa-solid fa-camera-retro"></i><b>حرکت‌های مجاز</b><small>کاربر فقط این گزینه‌ها را می‌بیند.</small></div>${motionBlock}</div>
      <div class="vpc2-preview-section"><div class="vpc2-preview-section-title"><i class="fa-solid fa-sliders"></i><b>تنظیمات خروجی</b><small>انتخاب‌های مجاز این محصول</small></div><div class="vpc2-preview-setting-grid"><span><small>مدت</small><b>${escapeHtml(output[0])}</b></span><span><small>نسبت تصویر</small><b dir="ltr">${escapeHtml(output[1])}</b></span><span><small>کیفیت خروجی</small><b>${escapeHtml(output[2])}</b></span><span><small>موسیقی</small><b>${escapeHtml(musicText)}</b></span></div></div>
      ${structureBlock}
      <div class="vpc2-preview-action"><div><b>آماده‌ی ساخت هستید؟</b><small>هزینه و اعتبار قبل از شروع به کاربر نمایش داده می‌شود.</small></div><button type="button"><i class="fa-solid fa-wand-magic-sparkles"></i> ساخت ویدیو</button></div>
    </div>`;
  }

  form.querySelectorAll('[name="product_family"]').forEach((input) => input.addEventListener('change', applyFamilyDefaults));
  form.querySelectorAll('[name="video_structure"]').forEach((input) => input.addEventListener('change', () => { const hidden = document.getElementById('vpc2-multi-shot-value'); if (hidden) hidden.value = input.value === 'multi_shot' ? '1' : '0'; syncTimelineVisibility(); syncStepStates(); renderUserPreview(); }));
  form.querySelectorAll('[name="durations[]"]').forEach((input) => input.addEventListener('change', syncDurationState));
  modelSelect.addEventListener('change', () => { filterFallbackModels(); updateModelSummary(); syncStepStates(); });
  fallbackSelect.addEventListener('change', filterFallbackModels);
  timelineList?.addEventListener('input', readTimeline);
  timelineList?.addEventListener('change', readTimeline);
  timelineList?.addEventListener('click', (event) => { const button = event.target.closest('[data-shot-remove]'); if (!button) return; readTimeline(); timeline.splice(Number(button.closest('[data-shot-index]').dataset.shotIndex), 1); renderTimeline(); });
  document.getElementById('vpc2-add-shot')?.addEventListener('click', () => { readTimeline(); const max = Number(maxShotsInput?.value || 1); if (timeline.length >= max) { window.alert(`حداکثر ${max} پلان مجاز است.`); return; } timeline.push({ title: `پلان ${timeline.length + 1}`, duration: 4, prompt: '', input_roles: [], transition: 'cut', music_start: 0 }); renderTimeline(); });
  maxShotsInput?.addEventListener('input', () => { syncPlanRange(); renderTimeline(); });
  maxShotsInput?.addEventListener('change', () => { syncPlanRange(); renderTimeline(); });
  document.querySelectorAll('[data-vpc2-step-tab]').forEach((tab) => tab.addEventListener('click', () => showStep(tab.dataset.vpc2StepTab)));
  nextButton.addEventListener('click', () => { if (validatePanel()) { syncCanonicalOutputs(); showStep(currentStep === 4 && !multiShotEnabled() ? 6 : currentStep + 1); } });
  previousButton.addEventListener('click', () => showStep(currentStep === 6 && !multiShotEnabled() ? 4 : currentStep - 1));
  document.getElementById('vpc2-draft').addEventListener('click', () => { readTimeline(); syncCanonicalOutputs(); statusInput.value = 'draft'; HTMLFormElement.prototype.submit.call(form); });
  publishButton.addEventListener('click', () => { readTimeline(); syncCanonicalOutputs(); statusInput.value = 'active'; if ([1, 2, 3, 4, 5].every(stepComplete)) form.requestSubmit(); else window.alert('پیش از انتشار، همه‌ی گام‌ها را کامل و بررسی کنید.'); });
  form.addEventListener('input', () => { syncStepStates(); renderUserPreview(); });
  form.addEventListener('change', () => { syncCanonicalOutputs(); syncStepStates(); syncTimelineVisibility(); renderUserPreview(); });

  function syncStepStates() { document.querySelectorAll('[data-vpc2-step-tab]').forEach((tab) => { const n = Number(tab.dataset.vpc2StepTab); tab.classList.toggle('is-done', n < currentStep && stepComplete(n)); }); }
  const nameEn = form.elements.name_en;
  const slug = document.getElementById('vpc2-slug') || document.getElementById('slug-input');
  nameEn?.addEventListener('input', () => { if (slug.dataset.touched === '1') return; slug.value = nameEn.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); });
  slug?.addEventListener('input', () => { slug.dataset.touched = '1'; });
  form.querySelector('[data-vpc2-image]')?.addEventListener('change', (event) => { const file = event.target.files?.[0]; if (file) form.querySelector('[data-vpc2-image-preview]').src = URL.createObjectURL(file); });
  form.querySelector('[data-vpc2-video]')?.addEventListener('change', (event) => { const file = event.target.files?.[0]; if (!file) return; const preview = form.querySelector('[data-vpc2-video-preview]'); preview.src = URL.createObjectURL(file); preview.play().catch(() => {}); });

  filterPrimaryModels();
  filterFallbackModels();
  updateModelSummary();
  applyFamilyDefaults();
  renderTimeline();
  wirePhotoTagInput();
  syncPhotoStepFields();
  syncCanonicalOutputs();
  syncDurationState();
  syncPlanRange();
  renderUserPreview();
  showStep(1);
})();
