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
  const result = root.querySelector('[data-studio-result]');
  const outputVideo = root.querySelector('[data-studio-output-video]');
  const outputImage = root.querySelector('[data-studio-output-image]');
  const videoPlay = root.querySelector('[data-studio-video-play]');
  const errorBox = root.querySelector('[data-studio-error]');
  const errorText = errorBox?.querySelector('span');

  let workflow = 'text_to_video';
  let progressTimer = null;
  let pollTimer = null;
  let quoteSequence = 0;
  let objectUrls = [];

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

  function filterModelOptions() {
    const menu = [...document.querySelectorAll('[data-select-menu]')]
      .find((item) => item.dataset.studioMenuKey === 'model' || item.closest('[data-studio-select="model"]'));
    if (!menu) return;
    menu.querySelectorAll('.create-studio-select-option').forEach((button) => {
      const item = (config.workflow_models || []).find((model) => String(model.value) === String(button.dataset.value));
      button.hidden = !modelSupportsWorkflow(item);
    });
  }

  function updateModelAvailability() {
    const modelData = workflowModel(selectedModel());
    const unsupported = modelData && !modelSupportsWorkflow(modelData);
    if (unsupported) showError('مدل فعلی برای این نوع ورودی مناسب نیست؛ یک مدل سازگار انتخاب کنید.');
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
      if (help) help.textContent = disabled
        ? 'پرامپت برای ساخت کافی است'
        : isImageWorkflow ? (workflow === 'image_sequence_to_video' ? '۲ تا ۴ عکس پشت‌سرهم' : '۱ تا ۴ تصویر') : 'MP4، WebM یا MOV';
    }

    if (workflowFiles) workflowFiles.hidden = disabled || workflowFiles.childElementCount === 0;
    if (disabled && uploadInput) {
      uploadInput.value = '';
      renderFiles([]);
    }
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
    files.slice(0, 4).forEach((file, index) => {
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
      item.insertAdjacentHTML('beforeend', '<b>' + faDigits(index + 1) + '</b><span>' + file.name + '</span>');
      workflowFiles.appendChild(item);
    });
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
    const modelInput = root.querySelector('[data-studio-select="model"] [data-select-input]');
    const modelLabel = root.querySelector('[data-studio-select="model"] [data-select-label]');
    const currentModel = workflowModel(selectedModel());
    if (modelInput && currentModel && !modelSupportsWorkflow(currentModel)) {
      modelInput.value = '';
      if (modelLabel) modelLabel.textContent = 'مدل هوش مصنوعی';
    }
    updateUploadUI();
    filterModelOptions();
    requestQuote();
  }

  function syncMode() {
    if (root.dataset.mode !== 'video') {
      if (workflowTabs) workflowTabs.hidden = true;
      if (workflowNote) workflowNote.hidden = true;
      return;
    }
    updateUploadUI();
  }

  function startProgress() {
    if (!progress) return;
    progress.hidden = false;
    result.hidden = true;
    root.querySelector('[data-studio-video-content]')?.setAttribute('hidden', '');
    root.querySelector('[data-studio-image-content]')?.setAttribute('hidden', '');
    progressTitle.textContent = 'در حال ساخت ویدیو';
    progressText.textContent = 'در حال آماده‌سازی ورودی‌ها و ارسال درخواست به مدل...';
    progressBar.style.width = '8%';
    let value = 8;
    window.clearInterval(progressTimer);
    progressTimer = window.setInterval(() => {
      value = Math.min(88, value + (value < 55 ? 3 : 1));
      progressBar.style.width = value + '%';
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
      if (payload.status === 'completed' && payload.video_url) return payload.video_url;
      if (['failed', 'canceled'].includes(payload.status)) {
        throw new Error(payload.error_message || 'ساخت ویدیو ناموفق بود.');
      }
      if (progressText) progressText.textContent = payload.status === 'queued'
        ? 'درخواست در صف ساخت قرار دارد...'
        : 'مدل هوش مصنوعی در حال ساخت خروجی است...';
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
    if (files.length > 4) {
      showError('حداکثر چهار فایل قابل انتخاب است.');
      return;
    }
    if (workflow === 'image_to_video' && files.length < 1) {
      showError('برای حالت عکس به ویدیو، حداقل یک تصویر انتخاب کنید.');
      return;
    }
    if (workflow === 'image_sequence_to_video' && (files.length < 2 || files.length > 4)) {
      showError('برای توالی داستانی، دو تا چهار تصویر انتخاب کنید.');
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
    data.set('workflow', workflow);
    data.set('prompt', prompt.value.trim());
    data.set('studio_model', model?.value || '');
    data.set('studio_provider', model?.provider || '');
    data.set('video[duration]', selectedValue('duration') || '4');
    data.set('video[aspect_ratio]', selectedValue('ratio') || '16:9');
    data.set('video[resolution]', selectedValue('quality') || '720p');
    data.set('video[motion_preset]', selectedValue('motion') || '');
    data.set('rights_confirmed', '1');
    if (workflow === 'video_to_video') {
      data.append('source_video', files[0]);
    } else {
      files.forEach((file) => data.append('source_images[]', file));
    }

    submit.disabled = true;
    submitLabel.textContent = 'در حال ساخت';
    startProgress();
    try {
      const response = await fetch(config.workflow_generate_url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Accept': 'application/json',
        },
        body: data,
      });
      const payload = await readPayload(response);
      const videoUrl = await pollVideo(payload.status_url);
      outputVideo.src = videoUrl;
      outputVideo.hidden = false;
      outputImage.hidden = true;
      videoPlay.hidden = false;
      outputVideo.load();
      await outputVideo.play().catch(() => {});
      stopProgress();
      progressBar.style.width = '100%';
      window.setTimeout(() => { progress.hidden = true; }, 350);
      result.hidden = false;
      submitLabel.textContent = 'دوباره بساز';
      if (window.matchMedia('(max-width: 700px)').matches) {
        window.requestAnimationFrame(() => result.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'}));
      }
    } catch (error) {
      stopProgress();
      if (progress) progress.hidden = true;
      root.querySelector('[data-studio-video-content]')?.removeAttribute('hidden');
      showError(error.message || 'ارتباط با سرویس ساخت برقرار نشد.');
    } finally {
      submit.disabled = false;
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
    const files = [...uploadInput.files].slice(0, 4);
    if (files.length !== uploadInput.files.length) showError('حداکثر چهار فایل قابل انتخاب است.');
    renderFiles(files);
    requestQuote();
  });
  root.querySelectorAll('[data-studio-mode]').forEach((button) => {
    button.addEventListener('click', () => window.setTimeout(syncMode, 20));
  });
  root.querySelectorAll('[data-select-toggle]').forEach((button) => {
    button.addEventListener('click', () => window.setTimeout(requestQuote, 40));
  });
  root.querySelector('[data-studio-select="model"] [data-select-toggle]')?.addEventListener('click', () => window.setTimeout(filterModelOptions, 40));
  document.addEventListener('click', (event) => {
    if (event.target.closest('.create-studio-select-option')) window.setTimeout(requestQuote, 40);
  }, true);
  submit?.addEventListener('click', (event) => {
    if (root.dataset.mode !== 'video') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    generateWorkflow();
  }, true);

  const observer = new MutationObserver(syncMode);
  observer.observe(root, {attributes: true, attributeFilter: ['data-mode']});
  syncMode();
  requestQuote();
}());
