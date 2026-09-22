(function () {
  'use strict';

  const root = document.querySelector('[data-workflow-studio]');
  if (!root) return;

  const config = JSON.parse(root.querySelector('[data-studio-config]')?.textContent || '{}');
  const workflowTabs = root.querySelector('[data-workflow-tabs]');
  const workflowImageOptions = root.querySelector('[data-workflow-image-options]');
  const workflowNote = root.querySelector('[data-workflow-note]');
  const uploadZone = root.querySelector('[data-studio-upload-zone]');
  const uploadInput = root.querySelector('[data-studio-upload-input]');
  const uploadFile = root.querySelector('[data-studio-upload-file]');
  const workflowFiles = root.querySelector('[data-workflow-files]');
  const form = root.querySelector('[data-studio-form]');
  const prompt = root.querySelector('[data-studio-prompt]');
  const submit = root.querySelector('[data-studio-submit]');
  const submitLabel = root.querySelector('[data-studio-submit-label]');
  const cost = root.querySelector('[data-studio-cost]');
  const progress = root.querySelector('[data-studio-progress]');
  const progressTitle = root.querySelector('[data-studio-progress-title]');
  const progressText = root.querySelector('[data-studio-progress-text]');
  const progressBar = root.querySelector('[data-studio-progress-bar]');
  const progressValue = root.querySelector('[data-studio-progress-value]');
  const result = root.querySelector('[data-studio-result]');
  const outputVideo = root.querySelector('[data-studio-output-video]');
  const outputImage = root.querySelector('[data-studio-output-image]');
  const creditSummary = root.querySelector('[data-studio-credit-summary]');
  const videoPlay = root.querySelector('[data-studio-video-play]');
  const errorBox = root.querySelector('[data-studio-error]');
  const errorText = errorBox?.querySelector('span');
  const progressActions = root.querySelector('[data-studio-progress-actions]');
  const cancelButton = root.querySelector('[data-studio-cancel]');
  const retryButton = root.querySelector('[data-studio-retry]');

  let workflow = 'text_to_video';
  let progressTimer = null;
  let pollTimer = null;
  let quoteSequence = 0;
  let objectUrls = [];
  let activeGeneration = null;
  const activeGenerationKey = 'vatan-studio-active-video';

  const faDigits = (value) => String(value).replace(/[0-9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);

  function showError(message) {
    if (errorText) errorText.textContent = message;
    if (errorBox) errorBox.hidden = false;
  }

  function hideError() {
    if (errorBox) errorBox.hidden = true;
  }

  function selectedValue(key) {
    return root.querySelector('[data-studio-select="' + key + '"] [data-select-input]')?.value || '';
  }

  function selectedModel() {
    const id = selectedValue('model');
    const options = config.video?.model_options || [];
    return options.find((option) => String(option.value) === String(id)) || null;
  }

  function workflowModel(model) {
    return (config.workflow_models || []).find((item) => String(item.value) === String(model?.value));
  }

  function modelSupportsWorkflow(item) {
    if (!item) return true;
    if (workflow === 'text_to_video') return item.supports_text;
    if (workflow === 'video_to_video') return item.supports_video;
    if (workflow === 'image_sequence_to_video') return item.supports_image && Number(item.max_images || 1) >= 2;
    return item.supports_image;
  }

  function modelOptionError(item) {
    if (!item) return '';
    const duration = selectedValue('duration') || '4';
    const resolution = (selectedValue('quality') || '720p').toLowerCase().replace('2160p', '4k');
    const ratio = (selectedValue('ratio') || '16:9').toLowerCase();
    const supports = (key, value) => {
      const values = Array.isArray(item[key]) ? item[key].map((entry) => String(entry).toLowerCase()) : [];
      return values.length === 0 || values.includes(String(value).toLowerCase());
    };
    if (!supports('supported_durations', duration)) return 'مدل انتخاب‌شده این زمان ویدیو را پشتیبانی نمی‌کند؛ یک زمان سازگار انتخاب کنید.';
    if (!supports('supported_resolutions', resolution)) return 'مدل انتخاب‌شده این کیفیت خروجی را پشتیبانی نمی‌کند؛ کیفیت دیگری انتخاب کنید.';
    if (!supports('supported_aspect_ratios', ratio)) return 'مدل انتخاب‌شده این نسبت تصویر را پشتیبانی نمی‌کند؛ نسبت دیگری انتخاب کنید.';
    return '';
  }

  function normalizeModelOption(key, value) {
    const normalized = String(value ?? '').trim().toLowerCase();
    if (key === 'quality') {
      return ({'2160': '4k', '2160p': '4k', '4k': '4k', '1440': '2k', '1440p': '2k', '2k': '2k'})[normalized] || normalized;
    }
    return normalized;
  }

  function modelSelectMenu(key) {
    const select = root.querySelector(`[data-studio-select="${key}"]`);
    return select?._studioMenu || select?.querySelector('[data-select-menu]')
      || document.querySelector(`[data-studio-menu-key="${key}"]`);
  }

  function modelSupportedValues(item, key) {
    const values = Array.isArray(item?.[key]) ? item[key] : [];
    return values.map((value) => normalizeModelOption(key, value));
  }

  function syncModelConstraints() {
    if (root.dataset.mode !== 'video') return;
    const item = workflowModel(selectedModel());
    if (!item) return;

    const durations = modelSupportedValues(item, 'supported_durations')
      .map((value) => Number(value)).filter((value) => Number.isFinite(value));
    const durationSelect = root.querySelector('[data-studio-select="duration"]');
    const durationMenu = modelSelectMenu('duration');
    const slider = durationMenu?.querySelector('input[type="range"]');
    if (slider && durations.length > 0) {
      const minimum = Math.min(...durations);
      const maximum = Math.max(...durations);
      slider.min = String(minimum);
      slider.max = String(maximum);
      slider.step = '1';
      if (slider.dataset.modelDurationConstraintBound !== 'true') {
        slider.addEventListener('input', () => {
          const value = Number(slider.value);
          const nearest = durations.reduce((best, candidate) =>
            Math.abs(candidate - value) < Math.abs(best - value) ? candidate : best,
          durations[0]);
          if (Number(slider.value) !== nearest) {
            slider.value = String(nearest);
            slider.dispatchEvent(new Event('input', {bubbles: true}));
          }
        });
        slider.dataset.modelDurationConstraintBound = 'true';
      }
      const current = Number(selectedValue('duration') || slider.value || minimum);
      if (!durations.includes(current)) {
        const next = durations.find((value) => value >= current) ?? maximum;
        slider.value = String(next);
        slider.dispatchEvent(new Event('input', {bubbles: true}));
      }
      const scale = durationMenu.querySelector('.create-studio-duration-track-scale');
      if (scale) scale.innerHTML = `<span>${faDigits(minimum)} ثانیه</span><span>${faDigits(maximum)} ثانیه</span>`;
      durationSelect?.setAttribute('data-model-duration-range', `${minimum}-${maximum}`);
    }

    ['ratio', 'quality'].forEach((key) => {
      const supported = modelSupportedValues(item, key === 'ratio' ? 'supported_aspect_ratios' : 'supported_resolutions');
      const menu = modelSelectMenu(key);
      if (!menu || supported.length === 0) return;
      const buttons = [...menu.querySelectorAll('.create-studio-select-option')];
      buttons.forEach((button) => {
        button.hidden = !supported.includes(normalizeModelOption(key, button.dataset.value));
      });
      const current = normalizeModelOption(key, selectedValue(key));
      if (!buttons.some((button) => !button.hidden && normalizeModelOption(key, button.dataset.value) === current)) {
        buttons.find((button) => !button.hidden)?.click();
      }
    });
  }

  function filterModelOptions() {
    const menu = [...document.querySelectorAll('[data-select-menu]')]
      .find((item) => item.dataset.studioMenuKey === 'model' || item.closest('[data-studio-select="model"]'));
    if (!menu) return;
    const workflowOrder = new Map((config.workflow_models || []).map((item, index) => [String(item.value), index]));
    const buttons = [...menu.querySelectorAll('.create-studio-select-option')];
    buttons.forEach((button) => {
      const item = (config.workflow_models || []).find((model) => String(model.value) === String(button.dataset.value));
      button.hidden = !modelSupportsWorkflow(item);
    });
    buttons.sort((left, right) => {
      return (workflowOrder.get(String(left.dataset.value)) ?? Number.MAX_SAFE_INTEGER)
        - (workflowOrder.get(String(right.dataset.value)) ?? Number.MAX_SAFE_INTEGER);
    }).forEach((button) => menu.appendChild(button));
  }

  function updateModelAvailability() {
    const modelData = workflowModel(selectedModel());
    const unsupported = modelData && !modelSupportsWorkflow(modelData);
    if (unsupported) showError('مدل فعلی برای این نوع ورودی مناسب نیست؛ یک مدل سازگار انتخاب کنید.');
  }

  function selectCompatibleModel() {
    const current = workflowModel(selectedModel());
    const staleDefaults = new Set([
      'kwaivgi/kling-v2.5-turbo',
      'runwayml/gen-4-turbo',
      'luma/dream-machine-2',
    ]);
    if (current && modelSupportsWorkflow(current) && !staleDefaults.has(String(current.value))) return true;
    const compatible = (config.workflow_models || []).find((item) => modelSupportsWorkflow(item));
    const option = compatible && [...document.querySelectorAll('.create-studio-select-option[data-value]')]
      .find((button) => String(button.dataset.value) === String(compatible.value));
    if (option) {
      option.click();
      hideError();
      return true;
    }
    showError('برای این نوع ورودی، مدل سازگار در دسترس نیست.');
    return false;
  }

  function updateUploadUI() {
    const isVideo = root.dataset.mode === 'video';
    const isImageWorkflow = workflow === 'image_to_video' || workflow === 'image_sequence_to_video';
    const isVideoWorkflow = workflow === 'video_to_video';
    const disabled = !isVideo || workflow === 'text_to_video';

    if (workflowTabs) workflowTabs.hidden = !isVideo;
    if (workflowNote) workflowNote.hidden = !isVideo;
    if (workflowImageOptions) workflowImageOptions.hidden = !isVideo || !isImageWorkflow;
    if (uploadZone) {
      uploadZone.dataset.workflowDisabled = disabled ? 'true' : 'false';
      uploadZone.dataset.workflowMultiple = isImageWorkflow ? 'true' : 'false';
      uploadZone.hidden = disabled;
    }
    if (uploadInput) {
      uploadInput.multiple = isImageWorkflow;
      uploadInput.dataset.maxFiles = String(isImageWorkflow
        ? Math.max(1, Number(workflowModel(selectedModel())?.max_images || 1))
        : 1);
      uploadInput.accept = isImageWorkflow
        ? 'image/png,image/jpeg,image/webp,image/avif'
        : isVideoWorkflow
          ? 'video/mp4,video/webm,video/quicktime'
          : '';
    }

    if (workflowNote) {
      workflowNote.innerHTML = workflow === 'text_to_video'
        ? 'در این حالت فقط پرامپت استفاده می‌شود و فایل ورودی لازم نیست.'
        : workflow === 'image_to_video'
          ? 'یک عکس یا چند تصویر مرجع اضافه کنید. ترتیب تصاویر در پایین نمایش داده می‌شود.'
        : workflow === 'image_sequence_to_video'
            ? 'عکس‌ها به‌ترتیب شماره‌گذاری می‌شوند و هرکدام بخشی از مسیر ویدیو را مشخص می‌کنند.'
            : 'یک ویدیوی مرجع اضافه کنید تا حرکت و ساختار آن راهنمای تولید شود.';
    }
    if (uploadZone) {
      const title = uploadZone.querySelector('[data-upload-title]');
      const help = uploadZone.querySelector('[data-upload-help]');
      if (title) title.textContent = disabled
        ? 'فایل لازم نیست'
        : isImageWorkflow ? 'افزودن تصویر مرجع' : 'افزودن ویدیوی مرجع';
      const maxImages = Math.max(1, Number(workflowModel(selectedModel())?.max_images || 1));
      if (help) help.textContent = disabled
        ? 'پرامپت برای ساخت کافی است'
        : isImageWorkflow ? (workflow === 'image_sequence_to_video' ? `۲ تا ${faDigits(maxImages)} عکس پشت‌سرهم` : `۱ تا ${faDigits(maxImages)} تصویر برای این مدل`) : 'MP4، WebM یا MOV';
    }

    if (workflowFiles) workflowFiles.hidden = disabled || workflowFiles.childElementCount === 0;
    if (disabled && uploadInput) {
      uploadInput.value = '';
      renderFiles([]);
    }
    selectCompatibleModel();
    syncModelConstraints();
    updateModelAvailability();
  }

  function revokeObjectUrls() {
    objectUrls.forEach((url) => URL.revokeObjectURL(url));
    objectUrls = [];
  }

  function renderFiles(files) {
    if (!workflowFiles) return;
    revokeObjectUrls();
    workflowFiles.innerHTML = '';
    if (uploadFile) {
      uploadFile.hidden = files.length === 0;
      uploadFile.textContent = files.length > 1 ? faDigits(files.length) + ' فایل انتخاب شد' : (files[0]?.name || '');
    }
    workflowFiles.hidden = files.length === 0;
    files.forEach((file, index) => {
      const item = document.createElement('div');
      item.className = 'create-studio-workflow-file';
      const url = URL.createObjectURL(file);
      objectUrls.push(url);
      if (file.type.startsWith('image/')) {
        const image = document.createElement('img');
        image.src = url;
        image.alt = 'تصویر مرجع ' + (index + 1);
        item.appendChild(image);
      } else {
        item.innerHTML = '<i class="fa-solid fa-film"></i>';
      }
      const number = document.createElement('b');
      number.textContent = faDigits(index + 1);
      const name = document.createElement('span');
      name.textContent = file.name;
      const state = document.createElement('em');
      state.textContent = 'آماده ارسال';
      item.append(number, name, state);
      workflowFiles.appendChild(item);
    });
  }

  function renderCreditSummary(payload) {
    if (!creditSummary || !payload) return;
    const reserved = Math.max(0, Number(payload.credits_reserved || 0));
    const settled = Math.max(0, Number(payload.credits_settled || 0));
    const refunded = Math.max(0, Number(payload.credits_refunded || 0));
    creditSummary.querySelector('[data-studio-credits-reserved]').textContent = faDigits(reserved);
    creditSummary.querySelector('[data-studio-credits-settled]').textContent = faDigits(settled);
    creditSummary.querySelector('[data-studio-credits-refunded]').textContent = faDigits(refunded);
    const refundItem = creditSummary.querySelector('[data-studio-refund-item]');
    if (refundItem) refundItem.hidden = refunded < 1;
    creditSummary.hidden = false;
  }

  function setWorkflow(nextWorkflow) {
    const previousWorkflow = workflow;
    workflow = nextWorkflow;
    workflowTabs?.querySelectorAll('[data-workflow]').forEach((button) => {
      const active = button.dataset.workflow === workflow || (button.dataset.workflow === 'image_to_video' && workflow === 'image_sequence_to_video');
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    workflowImageOptions?.querySelectorAll('[data-workflow-submode]').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.workflowSubmode === workflow);
    });
    const previousImages = previousWorkflow === 'image_to_video' || previousWorkflow === 'image_sequence_to_video';
    const nextImages = workflow === 'image_to_video' || workflow === 'image_sequence_to_video';
    if (uploadInput && previousImages !== nextImages) {
      uploadInput.value = '';
      renderFiles([]);
    }
    updateUploadUI();
    filterModelOptions();
    syncModelConstraints();
    requestQuote();
  }

  function syncMode() {
    if (root.dataset.mode !== 'video') {
      if (workflowTabs) workflowTabs.hidden = true;
      if (workflowNote) workflowNote.hidden = true;
      if (workflowImageOptions) workflowImageOptions.hidden = true;
      if (workflowFiles) workflowFiles.hidden = true;
      return;
    }
    updateUploadUI();
    syncModelConstraints();
  }

  function setProgress(value) {
    const rounded = Math.max(0, Math.min(100, Math.round(value)));
    progressBar.style.width = rounded + '%';
    progressBar.parentElement?.setAttribute('aria-valuenow', String(rounded));
    if (progressValue) progressValue.textContent = String(rounded).replace(/[0-9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]) + '٪';
  }

  function startProgress() {
    if (!progress) return;
    progress.hidden = false;
    result.hidden = true;
    root.querySelector('[data-studio-video-content]')?.setAttribute('hidden', '');
    root.querySelector('[data-studio-image-content]')?.setAttribute('hidden', '');
    progressTitle.textContent = 'در حال ساخت ویدیو';
    progressText.textContent = 'در حال آماده‌سازی ورودی‌ها و ارسال درخواست به مدل...';
    setProgress(8);
    let value = 8;
    window.clearInterval(progressTimer);
    progressTimer = window.setInterval(() => {
      value = Math.min(88, value + (value < 55 ? 3 : 1));
      setProgress(value);
    }, 900);
  }

  function stopProgress() {
    window.clearInterval(progressTimer);
    progressTimer = null;
    window.clearTimeout(pollTimer);
  }

  async function readPayload(response) {
    const text = await response.text();
    let payload = {};
    try { payload = text ? JSON.parse(text) : {}; } catch (_) {}
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'ساخت ویدیو انجام نشد.');
    }
    return payload;
  }

  async function pollVideo(statusUrl) {
    for (let attempt = 0; attempt < 160; attempt += 1) {
      await new Promise((resolve) => { pollTimer = window.setTimeout(resolve, 2500); });
      const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}});
      const payload = await response.json();
      activeGeneration = {...activeGeneration, ...payload, statusUrl};
      window.localStorage.setItem(activeGenerationKey, JSON.stringify(activeGeneration));
      if (progressActions) progressActions.hidden = false;
      if (cancelButton) cancelButton.hidden = !payload.cancel_url || ['completed', 'failed', 'canceled', 'needs_review'].includes(payload.status);
      if (retryButton) retryButton.hidden = !(payload.retryable && payload.retry_url);
      if (payload.status === 'completed' && payload.video_url) return payload;
      if (['failed', 'canceled', 'needs_review'].includes(payload.status)) {
        const error = new Error(payload.error_message || payload.message || 'ساخت ویدیو ناموفق بود.');
        error.payload = payload;
        throw error;
      }
      if (progressText) progressText.textContent = payload.message || (payload.status === 'queued'
        ? 'درخواست در صف ساخت قرار دارد...'
        : 'مدل هوش مصنوعی در حال ساخت خروجی است...');
    }
    throw new Error('ساخت ویدیو بیشتر از زمان معمول طول کشید.');
  }

  async function generateWorkflow() {
    hideError();
    if (!prompt?.value.trim()) {
      showError('ابتدا توضیحات ساخت را وارد کنید.');
      prompt?.focus();
      return;
    }
    if (config.authenticated !== true) {
      window.location.href = config.login_url;
      return;
    }

    const files = [...(uploadInput?.files || [])];
    const maxImages = Math.max(1, Number(workflowModel(selectedModel())?.max_images || 1));
    if ((workflow === 'image_to_video' || workflow === 'image_sequence_to_video') && files.length > maxImages) {
      showError(`مدل انتخاب‌شده حداکثر ${faDigits(maxImages)} تصویر مرجع می‌پذیرد.`);
      return;
    }
    if (workflow === 'image_to_video' && files.length < 1) {
      showError('برای حالت عکس به ویدیو، حداقل یک تصویر انتخاب کنید.');
      return;
    }
    if (workflow === 'image_sequence_to_video' && (files.length < 2 || files.length > maxImages)) {
      showError(`برای توالی داستانی، دو تا ${faDigits(maxImages)} تصویر انتخاب کنید.`);
      return;
    }
    if (workflow === 'video_to_video' && files.length !== 1) {
      showError('برای حالت ویدیو به ویدیو، یک ویدیوی مرجع انتخاب کنید.');
      return;
    }

    const model = selectedModel();
    const modelData = workflowModel(model);
    if (modelData && workflow === 'video_to_video' && !modelData.supports_video) {
      showError('مدل انتخاب‌شده ورودی ویدیویی را پشتیبانی نمی‌کند.');
      return;
    }
    if (modelData && workflow !== 'text_to_video' && !modelData.supports_image && workflow !== 'video_to_video') {
      showError('مدل انتخاب‌شده ورودی تصویری را پشتیبانی نمی‌کند.');
      return;
    }
    const optionError = modelOptionError(modelData);
    if (optionError) {
      showError(optionError);
      return;
    }

    const data = new FormData(form);
    const idempotencyKey = window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    data.set('workflow', workflow);
    data.set('prompt', prompt.value.trim());
    data.set('studio_model', model?.value || '');
    data.set('studio_provider', model?.provider || '');
    data.set('video[duration]', selectedValue('duration') || '4');
    data.set('video[aspect_ratio]', selectedValue('ratio') || '16:9');
    data.set('video[resolution]', selectedValue('quality') || '720p');
    data.set('video[motion_preset]', selectedValue('motion') || '');
    data.set('rights_confirmed', '1');
    data.set('idempotency_key', idempotencyKey);
    if (workflow === 'video_to_video') {
      data.append('source_video', files[0]);
    } else {
      files.forEach((file) => data.append('source_images[]', file));
    }

    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    submitLabel.textContent = 'در حال ساخت';
    startProgress();
    try {
      const response = await fetch(config.workflow_generate_url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Accept': 'application/json',
          'Idempotency-Key': idempotencyKey,
        },
        body: data,
      });
      const payload = await readPayload(response);
      activeGeneration = {statusUrl: payload.poll_url || payload.status_url, generation_id: payload.generation_id};
      window.localStorage.setItem(activeGenerationKey, JSON.stringify(activeGeneration));
      if (progressActions) progressActions.hidden = false;
      const completed = await pollVideo(activeGeneration.statusUrl);
      renderCreditSummary(completed);
      outputVideo.src = completed.video_url;
      outputVideo.hidden = false;
      outputImage.hidden = true;
      outputVideo.controls = false;
      videoPlay.hidden = false;
      outputVideo.load();
      stopProgress();
      setProgress(100);
      window.setTimeout(() => { progress.hidden = true; }, 350);
      result.hidden = false;
      window.localStorage.removeItem(activeGenerationKey);
      submitLabel.textContent = 'دوباره بساز';
      if (window.matchMedia('(max-width: 700px)').matches) {
        window.requestAnimationFrame(() => result.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'}));
      }
    } catch (error) {
      stopProgress();
      setProgress(0);
      const canRetry = Boolean(error.payload?.retryable && error.payload?.retry_url);
      if (progress) progress.hidden = !canRetry;
      if (progressTitle && canRetry) progressTitle.textContent = 'ساخت ویدیو کامل نشد';
      if (progressText && canRetry) progressText.textContent = error.message || 'می‌توانید دوباره تلاش کنید.';
      if (progressActions) progressActions.hidden = !canRetry;
      if (retryButton) retryButton.hidden = !canRetry;
      if (cancelButton) cancelButton.hidden = true;
      if (!canRetry) root.querySelector('[data-studio-video-content]')?.removeAttribute('hidden');
      submitLabel.textContent = 'بساز';
      showError(error.message || 'ارتباط با سرویس ساخت برقرار نشد.');
      if (!canRetry) window.localStorage.removeItem(activeGenerationKey);
    } finally {
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
    }
  }

  async function requestQuote() {
    if (root.dataset.mode !== 'video' || !config.workflow_quote_url) return;
    const sequence = ++quoteSequence;
    const model = selectedModel();
    const params = new URLSearchParams({
      workflow,
      model: model?.value || '',
      provider: model?.provider || '',
      resolution: selectedValue('quality') || '720p',
      aspect_ratio: selectedValue('ratio') || '16:9',
      duration: selectedValue('duration') || '4',
      input_count: String(uploadInput?.files?.length || 0),
    });
    try {
      const response = await fetch(config.workflow_quote_url + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
      const payload = await response.json();
      if (sequence !== quoteSequence || !cost) return;
      cost.textContent = payload.cost_known && payload.credits !== null ? faDigits(payload.credits) : '—';
    } catch (_) {}
  }

  workflowTabs?.querySelectorAll('[data-workflow]').forEach((button) => {
    button.addEventListener('click', () => setWorkflow(button.dataset.workflow));
  });
  workflowImageOptions?.querySelectorAll('[data-workflow-submode]').forEach((button) => {
    button.addEventListener('click', () => setWorkflow(button.dataset.workflowSubmode));
  });
  uploadInput?.addEventListener('change', () => {
    const maxImages = Math.max(1, Number(workflowModel(selectedModel())?.max_images || 1));
    const limit = workflow === 'video_to_video' ? 1 : maxImages;
    const files = [...uploadInput.files].slice(0, limit);
    if (files.length !== uploadInput.files.length) showError(`مدل انتخاب‌شده حداکثر ${faDigits(limit)} فایل می‌پذیرد.`);
    const allowedImageTypes = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    const invalid = files.find((file) => workflow === 'video_to_video'
      ? !['video/mp4', 'video/webm', 'video/quicktime'].includes(file.type) || file.size > Number(config.upload_limits?.max_video_bytes || 104857600)
      : !allowedImageTypes.has(file.type) || file.size > Number(config.upload_limits?.max_image_bytes || 12582912));
    if (invalid) {
      uploadInput.value = '';
      renderFiles([]);
      showError(workflow === 'video_to_video' ? 'فرمت یا حجم ویدیوی انتخاب‌شده معتبر نیست.' : `فرمت یا حجم فایل «${invalid.name}» معتبر نیست.`);
      return;
    }
    renderFiles(files);
    requestQuote();
  });
  root.querySelectorAll('[data-studio-mode]').forEach((button) => {
    button.addEventListener('click', () => window.setTimeout(syncMode, 20));
  });
  root.querySelectorAll('[data-select-toggle]').forEach((button) => {
    button.addEventListener('click', () => window.setTimeout(requestQuote, 40));
  });
  root.querySelector('[data-studio-select="model"] [data-select-toggle]')?.addEventListener('click', () => window.setTimeout(() => {
    filterModelOptions();
  }, 40));
  document.addEventListener('click', (event) => {
    if (event.target.closest('.create-studio-select-option')) window.setTimeout(() => {
      syncModelConstraints();
      requestQuote();
    }, 40);
  }, true);
  submit?.addEventListener('click', (event) => {
    if (root.dataset.mode !== 'video') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    generateWorkflow();
  }, true);

  cancelButton?.addEventListener('click', async () => {
    if (!activeGeneration?.cancel_url) return;
    cancelButton.disabled = true;
    try {
      const response = await fetch(activeGeneration.cancel_url, {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json'}});
      const payload = await readPayload(response);
      activeGeneration = {...activeGeneration, ...payload, cancel_url: null};
      window.localStorage.setItem(activeGenerationKey, JSON.stringify(activeGeneration));
      cancelButton.hidden = true;
      if (progressText) progressText.textContent = payload.message;
    } catch (error) {
      showError(error.message || 'لغو ساخت انجام نشد.');
    } finally { cancelButton.disabled = false; }
  });

  retryButton?.addEventListener('click', async () => {
    if (!activeGeneration?.retry_url) return;
    retryButton.disabled = true;
    try {
      const response = await fetch(activeGeneration.retry_url, {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json'}});
      const payload = await readPayload(response);
      activeGeneration = {statusUrl: payload.poll_url, generation_id: payload.generation_id};
      window.localStorage.setItem(activeGenerationKey, JSON.stringify(activeGeneration));
      startProgress();
      const completed = await pollVideo(activeGeneration.statusUrl);
      renderCreditSummary(completed);
      outputVideo.src = completed.video_url;
      outputVideo.hidden = false;
      outputImage.hidden = true;
      outputVideo.controls = false;
      videoPlay.hidden = false;
      outputVideo.load();
      stopProgress();
      setProgress(100);
      progress.hidden = true;
      result.hidden = false;
      window.localStorage.removeItem(activeGenerationKey);
    } catch (error) { showError(error.message || 'تلاش دوباره انجام نشد.'); }
    finally { retryButton.disabled = false; }
  });

  const observer = new MutationObserver(syncMode);
  observer.observe(root, {attributes: true, attributeFilter: ['data-mode']});
  syncMode();
  syncModelConstraints();
  requestQuote();
  try {
    const savedGeneration = JSON.parse(window.localStorage.getItem(activeGenerationKey) || 'null');
    if (savedGeneration?.statusUrl) {
      activeGeneration = savedGeneration;
      startProgress();
      pollVideo(savedGeneration.statusUrl).then((completed) => {
        renderCreditSummary(completed);
        outputVideo.src = completed.video_url;
        outputVideo.hidden = false;
        outputVideo.load();
        stopProgress();
        progress.hidden = true;
        result.hidden = false;
        window.localStorage.removeItem(activeGenerationKey);
      }).catch((error) => {
        stopProgress();
        const canRetry = Boolean(error.payload?.retryable && error.payload?.retry_url);
        progress.hidden = !canRetry;
        if (progressTitle && canRetry) progressTitle.textContent = 'ساخت ویدیو کامل نشد';
        if (progressText && canRetry) progressText.textContent = error.message || 'می‌توانید دوباره تلاش کنید.';
        if (retryButton) retryButton.hidden = !canRetry;
        if (progressActions) progressActions.hidden = !canRetry;
        showError(error.message || 'پیگیری درخواست قبلی کامل نشد.');
        if (!canRetry) window.localStorage.removeItem(activeGenerationKey);
      });
    }
  } catch (_) { window.localStorage.removeItem(activeGenerationKey); }
}());
