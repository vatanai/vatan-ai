(function () {
  'use strict';
  const roots = [...document.querySelectorAll('.cw-page')];
  if (!roots.length) return;

  roots.forEach((root) => {
  const form = root.querySelector('.cw-form');
  const alertBox = root.querySelector('[data-form-alert]');
  const alertText = alertBox.querySelector('span');
  const isRedesign = root.dataset.instance === 'redesign';
  const stageTabButtons = [...root.querySelectorAll('[data-stage-tab]')];
  const outputStageTab = root.querySelector('[data-output-tab]');
  const emptyStage = root.querySelector('[data-empty]');
  const outputPlaceholder = root.querySelector('[data-output-placeholder]');
  const progressStage = root.querySelector('[data-progress]');
  const resultStage = root.querySelector('[data-result]');
  let hasGeneratedOutput = false;
  let progressAnimationFrame = null;
  let progressStartedAt = 0;
  let progressLastValue = 0;

  const progressBar = progressStage?.querySelector('[data-progress-bar]');
  const progressTrack = progressStage?.querySelector('[role="progressbar"]');
  const progressValue = progressStage?.querySelector('[data-progress-value]');
  const progressText = progressStage?.querySelector('[data-progress-text]');
  const progressTime = progressStage?.querySelector('[data-progress-time]');
  const progressNote = progressStage?.querySelector('[data-progress-note]');

  function formatElapsedTime(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60).toLocaleString('fa-IR', { minimumIntegerDigits: 2, useGrouping: false });
    const seconds = Math.floor(totalSeconds % 60).toLocaleString('fa-IR', { minimumIntegerDigits: 2, useGrouping: false });
    return `${minutes}:${seconds}`;
  }

  function progressForElapsed(elapsedSeconds) {
    if (elapsedSeconds < 4) {
      const part = elapsedSeconds / 4;
      return 10 * (1 - Math.pow(1 - part, 2));
    }
    if (elapsedSeconds < 16) {
      const part = (elapsedSeconds - 4) / 12;
      return 10 + (22 * Math.pow(part, .9));
    }
    if (elapsedSeconds < 30) {
      const part = (elapsedSeconds - 16) / 14;
      return 32 + (32 * (1 - Math.pow(1 - part, 1.45)));
    }
    if (elapsedSeconds < 46) {
      const part = (elapsedSeconds - 30) / 16;
      return 64 + (18 * (1 - Math.pow(1 - part, 1.35)));
    }

    // پس از ثانیه ۴۶ پیشرفت به‌صورت مجانبی کند می‌شود و هرگز قبل از
    // دریافت پاسخ واقعی به ۱۰۰ درصد نمی‌رسد.
    return Math.min(96, 82 + (14 * (1 - Math.exp(-(elapsedSeconds - 46) / 26))));
  }

  function messageForElapsed(elapsedSeconds) {
    if (elapsedSeconds < 2) return 'در حال بررسی ورودی‌ها';
    if (elapsedSeconds < 8) return 'در حال تحلیل تصویر و جزئیات آن';
    if (elapsedSeconds < 18) return 'در حال ساخت ترکیب اصلی تصویر';
    if (elapsedSeconds < 25) return 'در حال پرداخت جزئیات نهایی';
    if (elapsedSeconds < 30) return 'در حال تکمیل خروجی شما';
    return 'ساخت کمی بیشتر از معمول طول کشیده؛ همچنان ادامه دارد';
  }

  function renderVisualProgress(value, elapsedSeconds, message = null) {
    if (!progressStage || !progressBar || !progressTrack) return;
    const roundedValue = Math.max(0, Math.min(100, Math.round(value)));
    const currentMessage = message || messageForElapsed(elapsedSeconds);
    progressLastValue = Math.max(progressLastValue, roundedValue);
    progressBar.style.width = `${progressLastValue}%`;
    if (progressValue) progressValue.textContent = `${progressLastValue.toLocaleString('fa-IR')}٪`;
    if (progressText && progressText.textContent !== currentMessage) progressText.textContent = currentMessage;
    if (progressTime) progressTime.textContent = `زمان سپری‌شده ${formatElapsedTime(elapsedSeconds)}`;
    if (progressNote) progressNote.textContent = elapsedSeconds < 30 ? 'زمان هدف حدود ۰۰:۳۰' : 'در انتظار پاسخ نهایی سرویس';
    progressTrack.setAttribute('aria-valuenow', String(progressLastValue));
    progressTrack.setAttribute('aria-valuetext', `${progressLastValue.toLocaleString('fa-IR')} درصد؛ ${currentMessage}`);
  }

  function stopVisualProgress() {
    if (progressAnimationFrame !== null) cancelAnimationFrame(progressAnimationFrame);
    progressAnimationFrame = null;
  }

  function resetVisualProgress() {
    stopVisualProgress();
    progressLastValue = 0;
    progressStage?.classList.remove('is-complete');
    renderVisualProgress(0, 0, 'در حال بررسی ورودی‌ها');
  }

  function startVisualProgress() {
    resetVisualProgress();
    progressStartedAt = performance.now();
    const tick = (now) => {
      const elapsedSeconds = Math.max(0, (now - progressStartedAt) / 1000);
      renderVisualProgress(progressForElapsed(elapsedSeconds), elapsedSeconds);
      progressAnimationFrame = requestAnimationFrame(tick);
    };
    progressAnimationFrame = requestAnimationFrame(tick);
  }

  function completeVisualProgress() {
    stopVisualProgress();
    const elapsedSeconds = Math.max(0, (performance.now() - progressStartedAt) / 1000);
    progressStage?.classList.add('is-complete');
    renderVisualProgress(100, elapsedSeconds, 'خروجی آماده شد');
    if (progressNote) progressNote.textContent = 'آماده برای نمایش';
    return new Promise((resolve) => window.setTimeout(resolve, 520));
  }

  function waitForStageImage(image) {
    if (!image || (image.complete && image.naturalWidth > 0)) return Promise.resolve();
    return new Promise((resolve) => {
      let timeout = null;
      const done = () => {
        image.removeEventListener('load', done);
        image.removeEventListener('error', done);
        if (timeout) window.clearTimeout(timeout);
        resolve();
      };
      image.addEventListener('load', done, { once: true });
      image.addEventListener('error', done, { once: true });
      timeout = window.setTimeout(done, 8000);
    });
  }

  function imageUrlFromPayload(image) {
    if (typeof image === 'string') return image.trim();
    if (!image || typeof image !== 'object') return '';
    const nestedUrl = image.image_url && typeof image.image_url === 'object' ? image.image_url.url : image.image_url;
    return String(image.url || nestedUrl || image.src || '').trim();
  }

  function normalizeImageOutputs(payload) {
    const listedImages = Array.isArray(payload?.images) ? payload.images : [];
    const images = listedImages
      .map((image) => ({...(image && typeof image === 'object' ? image : {}), url: imageUrlFromPayload(image)}))
      .filter((image) => image.url);
    if (images.length) return images;
    const fallbackUrl = imageUrlFromPayload(payload?.image_url);
    return fallbackUrl ? [{url: fallbackUrl, title: ''}] : [];
  }

  root.querySelectorAll('[data-ratio-dropdown]').forEach((dropdown) => {
    const summary = dropdown.querySelector('[data-ratio-summary] b');
    dropdown.addEventListener('toggle', () => {
      if (!dropdown.open) return;
      root.querySelectorAll('[data-face-source]').forEach((selector) => {
        const menu = selector.querySelector('[data-face-source-menu]');
        const toggle = selector.querySelector('[data-face-source-toggle]');
        if (menu) menu.hidden = true;
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        selector.classList.remove('is-open');
      });
      root.classList.remove('cw-face-menu-open');
    });
    dropdown.querySelectorAll('input[name="output[aspect_ratio]"]').forEach((input) => {
      input.addEventListener('change', () => {
        if (summary) summary.textContent = input.dataset.ratioLabel || input.value;
        dropdown.removeAttribute('open');
      });
    });
  });

  root.querySelectorAll('[data-face-source]').forEach((selector) => {
    const toggle = selector.querySelector('[data-face-source-toggle]');
    const menu = selector.querySelector('[data-face-source-menu]');
    const label = selector.querySelector('[data-face-source-label]');
    const faceProfileInput = selector.querySelector('[data-face-profile-input]');
    if (!toggle || !menu || !label) return;

    toggle.addEventListener('click', () => {
      const isOpen = !menu.hidden;
      if (!isOpen) {
        root.querySelectorAll('[data-ratio-dropdown]').forEach((dropdown) => dropdown.removeAttribute('open'));
      }
      menu.hidden = isOpen;
      toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      selector.classList.toggle('is-open', !isOpen);
      root.classList.toggle('cw-face-menu-open', !isOpen);
    });

    selector.querySelectorAll('[data-face-source-option]').forEach((option) => {
      option.addEventListener('click', () => {
        const optionLabel = option.querySelector('b');
        if (optionLabel) label.textContent = optionLabel.textContent;
        selector.querySelectorAll('[data-face-source-option]').forEach((item) => item.classList.remove('selected'));
        option.classList.add('selected');
        if (faceProfileInput) faceProfileInput.value = option.dataset.faceProfileId || '';
        hasPrimaryImage = Boolean(faceProfileInput?.value)
          || [...root.querySelectorAll('[data-upload-input][accept*="image"]')].some((imageInput) => imageInput.files.length);
        updateReadiness();
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        selector.classList.remove('is-open');
        root.classList.remove('cw-face-menu-open');
      });
    });
  });

  // کلیک بیرون از هر منوی باز، همان منو را می‌بندد.
  document.addEventListener('click', (event) => {
    const clickedControl = event.target.closest('[data-face-source], [data-ratio-dropdown]');
    if (clickedControl && clickedControl.closest('.cw-page') === root) return;
    root.querySelectorAll('[data-face-source]').forEach((selector) => {
      const menu = selector.querySelector('[data-face-source-menu]');
      const toggle = selector.querySelector('[data-face-source-toggle]');
      if (menu) menu.hidden = true;
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
      selector.classList.remove('is-open');
    });
    root.querySelectorAll('[data-ratio-dropdown][open]').forEach((dropdown) => dropdown.removeAttribute('open'));
    root.classList.remove('cw-face-menu-open');
  });

  function setStageTab(tab) {
    if (!isRedesign) return;
    stageTabButtons.forEach((button) => button.classList.toggle('active', button.dataset.stageTab === tab));
    if (tab === 'output' && !outputStageTab?.hidden) {
      if (emptyStage) emptyStage.hidden = true;
      if (progressStage) progressStage.hidden = true;
      if (outputPlaceholder) outputPlaceholder.hidden = hasGeneratedOutput;
      if (resultStage) resultStage.hidden = !hasGeneratedOutput;
    } else if (tab === 'upload') {
      if (resultStage) resultStage.hidden = true;
      if (outputPlaceholder) outputPlaceholder.hidden = true;
      if (progressStage?.hidden !== false && emptyStage) emptyStage.hidden = false;
    }
  }

  function revealOutputTab() {
    if (!isRedesign) return;
    hasGeneratedOutput = true;
    if (outputStageTab) {
      outputStageTab.hidden = false;
    }
    setStageTab('output');
  }

  stageTabButtons.forEach((button) => button.addEventListener('click', () => setStageTab(button.dataset.stageTab)));

  const tabs = [...root.querySelectorAll('[data-tab]')];
  const panels = [...root.querySelectorAll('[data-panel]')];
  tabs.forEach((tab) => tab.addEventListener('click', () => {
    tabs.forEach((item) => item.classList.toggle('active', item === tab));
    panels.forEach((panel) => panel.classList.toggle('active', panel.dataset.panel === tab.dataset.tab));
  }));

  root.querySelectorAll('[data-range]').forEach((input) => {
    const output = input.closest('.cw-range-wrap').querySelector('output');
    input.addEventListener('input', () => { output.textContent = input.value + '٪'; });
  });

  function fieldValue(id) {
    const controls = [...form.querySelectorAll(`[name="fields[${id}]"], [name="fields[${id}][]"]`)].filter((input) => input.type !== 'hidden');
    if (!controls.length) return '';
    if (controls[0].type === 'checkbox') return controls.filter((input) => input.checked).map((input) => input.value);
    if (controls[0].type === 'radio') return controls.find((input) => input.checked)?.value || '';
    return controls[0].value;
  }

  function updateConditions() {
    root.querySelectorAll('[data-show-field]').forEach((field) => {
      const current = fieldValue(field.dataset.showField);
      const expected = field.dataset.showValue || '';
      const values = Array.isArray(current) ? current : [current];
      const op = field.dataset.showOp || 'eq';
      const visible = op === 'neq' ? !values.includes(expected)
        : op === 'has' ? values.some((value) => String(value).includes(expected))
        : op === 'not_empty' ? values.some((value) => String(value) !== '')
        : values.includes(expected);
      field.hidden = !visible;
      field.querySelectorAll('input,select,textarea').forEach((control) => { control.disabled = !visible; });
    });
  }
  form.addEventListener('change', updateConditions);
  updateConditions();

  root.querySelectorAll('input[type=color]').forEach((input) => input.addEventListener('input', () => {
    input.closest('.cw-color-control').querySelector('[data-color-value]').textContent = input.value;
  }));

  const readiness = root.querySelector('[data-readiness]');
  const readinessText = root.querySelector('[data-readiness-text]');
  const scoreBar = root.querySelector('[data-score-bar]');
  let hasPrimaryImage = Boolean(form.querySelector('[data-face-profile-input]')?.value);
  function updateReadiness() {
    const value = hasPrimaryImage ? 92 : 35;
    const generateButton = root.querySelector('[data-action=generate]');
    if (generateButton) {
      generateButton.disabled = !hasPrimaryImage;
      generateButton.setAttribute('aria-disabled', hasPrimaryImage ? 'false' : 'true');
      updateCreditState();
    }
    if (!readiness || !scoreBar || !readinessText) return;
    readiness.textContent = value.toLocaleString('fa-IR') + '٪';
    scoreBar.style.width = value + '%';
    const selectedFaceProfile = form.querySelector('[data-face-profile-input]')?.value;
    readinessText.textContent = hasPrimaryImage
      ? (selectedFaceProfile ? 'پروفایل چهره انتخاب شد و برای ساخت آماده است.' : 'همه‌چیز برای یک خروجی دقیق آماده است.')
      : 'ابتدا تصویر اصلی را اضافه کنید.';
  }

  root.querySelectorAll('[data-upload-input]').forEach((input) => {
    const upload = input.closest('.cw-upload');
    const preview = upload.nextElementSibling;
    ['dragenter', 'dragover'].forEach((name) => upload.addEventListener(name, (event) => { event.preventDefault(); upload.classList.add('dragging'); }));
    ['dragleave', 'drop'].forEach((name) => upload.addEventListener(name, (event) => { event.preventDefault(); upload.classList.remove('dragging'); }));
    upload.addEventListener('drop', (event) => {
      const transfer = new DataTransfer();
      [...event.dataTransfer.files].forEach((file) => transfer.items.add(file));
      input.files = transfer.files;
      renderFiles(input.files);
    });
    input.addEventListener('change', () => renderFiles(input.files));
    async function renderFiles(files) {
      const maxFiles = Number(input.dataset.maxFiles || (input.multiple ? 3 : 1));
      const selected = [...files].slice(0, maxFiles);
      if (files.length > maxFiles) {
        alertText.textContent = `حداکثر ${maxFiles.toLocaleString('fa-IR')} عکس قابل استفاده است؛ عکس‌های اضافه انتخاب نشدند.`;
        alertBox.hidden = false;
        const transfer = new DataTransfer(); selected.forEach((file) => transfer.items.add(file)); input.files = transfer.files;
      }
      preview.innerHTML = '';
      selected.forEach((file, index) => {
        const item = document.createElement('div'); item.className = 'cw-preview-item';
        if (file.type.startsWith('image/')) {
          const image = document.createElement('img'); image.src = URL.createObjectURL(file); item.appendChild(image);
        } else { item.innerHTML = '<i class="fa-solid fa-file"></i>'; }
        const name = document.createElement('span'); name.textContent = file.name; item.appendChild(name);
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'cw-preview-remove'; remove.setAttribute('aria-label', 'حذف عکس'); remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        remove.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); const transfer = new DataTransfer(); selected.filter((_, fileIndex) => fileIndex !== index).forEach((kept) => transfer.items.add(kept)); input.files = transfer.files; renderFiles(input.files); });
        item.appendChild(remove); preview.appendChild(item);
      });
      if (input.accept.includes('image')) { hasPrimaryImage = [...root.querySelectorAll('[data-upload-input][accept*="image"]')].some((imageInput) => imageInput.files.length); updateReadiness(); }
      if (input.accept.includes('image') && selected.length) {
        upload.style.borderColor = '';
        const warnings = (await Promise.all(selected.map(checkImageQuality))).filter(Boolean);
        if (warnings.length) {
          alertText.textContent = warnings[0] + ' می‌توانید عکس را عوض کنید یا با همین عکس ادامه دهید.';
          alertBox.hidden = false;
        } else if (files.length <= maxFiles) alertBox.hidden = true;
      }
    }
  });

  async function checkImageQuality(file) {
    if (!file.type.startsWith('image/')) return '';
    let bitmap;
    try {
      bitmap = await createImageBitmap(file);
    } catch (_) {
      return '';
    }
    // این هشدار صرفاً برای تصاویر واقعاً خیلی کوچک است. عکس‌های موبایل، تصاویر
    // برش‌خورده و ورودی‌های رایج ۵۱۲ پیکسلی نباید بی‌دلیل کم‌کیفیت اعلام شوند.
    if (Math.min(bitmap.width, bitmap.height) < 360 && Math.max(bitmap.width, bitmap.height) < 720) {
      return `ابعاد «${file.name}» خیلی کوچک است؛ برای نتیجه بهتر تصویر بزرگ‌تری انتخاب کنید.`;
    }
    if ('FaceDetector' in window) {
      try {
        const faces = await new FaceDetector({ fastMode: true, maxDetectedFaces: 3 }).detect(bitmap);
        // نبودن تشخیص یا چندچهره بودن به‌تنهایی نشانه کیفیت پایین نیست و بین
        // مرورگرها خطای مثبت کاذب زیادی دارد. فقط چهره بسیار دور را یادآوری کن.
        if (faces.length === 1) {
          const box = faces[0].boundingBox;
          if (Math.min(box.width / bitmap.width, box.height / bitmap.height) < .10) {
            return `چهره در «${file.name}» خیلی دور است؛ عکس نزدیک‌تر نتیجه بهتری می‌دهد.`;
          }
        }
      } catch (_) {}
    }
    bitmap.close?.();
    return '';
  }

  const costElement = root.querySelector('[data-cost]');
  const toNumber = (value) => Number(String(value ?? '').replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)).replace(/[^0-9.-]/g, '')) || 0;
  const baseCostText = costElement?.dataset.value || root.querySelector('[name="redesign_cost"]')?.value || '0';
  const baseCost = toNumber(baseCostText);
  const generateButton = root.querySelector('[data-action=generate]');
  const updateCreditState = () => {
    if (!generateButton || root.dataset.preview === '1' || root.dataset.authenticated !== '1') return;
    const balance = toNumber(root.dataset.balance);
    const cost = toNumber(costElement?.dataset.value || costElement?.textContent);
    const locked = cost > 0 && balance < cost;
    generateButton.classList.toggle('is-credit-locked', locked);
    generateButton.setAttribute('aria-label', locked ? 'افزایش اعتبار برای ساخت' : 'بساز');
    generateButton.dataset.creditLocked = locked ? '1' : '0';
  };
  function selectedMainQuality() {
    return form.querySelector('[data-main-quality]:checked') || form.querySelector('input[type="hidden"][data-main-quality]');
  }
  function mainQualityCreditCost() {
    const selected = selectedMainQuality();
    return selected ? Number(selected.dataset.creditCost || baseCost) : baseCost;
  }
  function updateMainQualitySummary() {
    const selected = selectedMainQuality();
    if (!selected) return;
    const qualityName = root.querySelector('[data-build-quality-name]');
    const qualityGrade = root.querySelector('[data-build-quality-grade]');
    if (qualityName) qualityName.textContent = selected.dataset.qualityName || '';
    if (qualityGrade) qualityGrade.textContent = selected.dataset.qualityGrade || '';
  }
  function recalculateCost() {
    let extra = 0;
    root.querySelectorAll('.cw-field:not([hidden])').forEach((field) => {
      const controls = [...field.querySelectorAll('input:not([type=hidden]),select,textarea')];
      const hasValue = controls.some((control) => control.type === 'file' ? control.files.length > 0 : ['checkbox','radio'].includes(control.type) ? control.checked : String(control.value).trim() !== '');
      if (hasValue) extra += Number(field.dataset.fieldCredit || 0);
      controls.forEach((control) => {
        if (control.tagName === 'SELECT') extra += Number(control.selectedOptions[0]?.dataset.optionCredit || 0);
        else if ((!['checkbox','radio'].includes(control.type) || control.checked) && control.dataset.optionCredit) extra += Number(control.dataset.optionCredit || 0);
      });
    });
    const identity = root.querySelector('[data-identity-toggle]');
    if (identity?.checked) extra += Number(identity.closest('[data-identity-extra]')?.dataset.identityExtra || 0);
    const total = mainQualityCreditCost() + extra;
    if (costElement) {
      costElement.textContent = total;
      costElement.dataset.value = String(total);
    }
    const redesignCost = root.querySelector('[name="redesign_cost"]');
    if (redesignCost) redesignCost.value = total;
    updateCreditState();
  }
  form.addEventListener('change', () => { recalculateCost(); updateMainQualitySummary(); });
  form.addEventListener('input', recalculateCost);
  recalculateCost();
  updateMainQualitySummary();
  root.querySelector('[data-identity-toggle]')?.addEventListener('change', (event) => {
    const grade = root.querySelector('[data-grade-label]');
    if (grade) grade.textContent = event.target.checked ? 'Grade A · High' : 'Grade B · Medium';
    if (event.target.checked) {
      alertText.textContent = 'برای شباهت بهتر، ۲ یا ۳ عکس واضح از زاویه‌های مختلف اضافه کنید.';
      alertBox.hidden = false;
    }
    recalculateCost();
  });
  root.querySelectorAll('[data-ratio]').forEach((input) => input.addEventListener('change', () => {
    const ratioLabel = root.querySelector('[data-ratio-label]');
    const selectedLabel = input.nextElementSibling?.querySelector('b');
    if (ratioLabel && selectedLabel) ratioLabel.textContent = selectedLabel.textContent;
  }));

  root.querySelector('[data-action=reset]')?.addEventListener('click', () => window.location.reload());
  generateButton?.addEventListener('click', async () => {
    const requiredUploadField = [...form.querySelectorAll('.cw-field')].find((field) => field.querySelector('input[type=file]') && field.querySelector('.cw-label b'));
    const requiredUpload = requiredUploadField?.querySelector('.cw-upload')
      || root.querySelector('[data-required-upload="1"]');
    const requiredUploadInput = requiredUpload?.querySelector('input[type=file]');
    const selectedFaceProfile = form.querySelector('[data-face-profile-input]')?.value;
    if (requiredUploadInput && !requiredUploadInput.files.length && !selectedFaceProfile) {
      alertText.textContent = 'برای ادامه، تصویر الزامی را اضافه کنید.';
      alertBox.hidden = false; requiredUpload.style.borderColor = 'var(--red)';
      tabs.find((tab) => tab.dataset.tab === 'basic')?.click(); requiredUpload.scrollIntoView({ behavior: 'smooth', block: 'center' }); return;
    }
    if (root.dataset.authenticated !== '1' && root.dataset.preview !== '1' && root.dataset.sampleOnly !== '1') {
      window.location.href = root.dataset.loginUrl; return;
    }
    const currentBalance = toNumber(root.dataset.balance);
    const requiredCredits = toNumber(costElement?.dataset.value || costElement?.textContent || root.querySelector('[name="redesign_cost"]')?.value);
    if (root.dataset.preview !== '1' && root.dataset.sampleOnly !== '1' && requiredCredits > 0 && currentBalance < requiredCredits) {
      window.showTokenShortageModal?.({ required: requiredCredits, balance: currentBalance });
      return;
    }
    const empty = root.querySelector('[data-empty]'); const progress = root.querySelector('[data-progress]'); const result = root.querySelector('[data-result]');
    setStageTab('upload');
    empty.hidden = true; result.hidden = true; progress.hidden = false;
    startVisualProgress();
    const submit = root.querySelector('[data-action=generate]'); submit.disabled = true;
    if (root.dataset.preview === '1' || !root.dataset.generateUrl) {
      setTimeout(async () => {
        await completeVisualProgress();
        progress.hidden = true; result.hidden = false; submit.disabled = false; revealOutputTab();
      }, 3200);
      return;
    }
    const data = new FormData(form);
    // FormData فرم را مبنا می‌گیرد، اما set باعث می‌شود در صورت وجود فیلدهای
    // قدیمیِ هم‌نام یا اسکریپت‌های سازگاری، فقط یک مقدار واقعی به بک‌اند برسد.
    const selectedAspectRatio = form.querySelector('[name="output[aspect_ratio]"]:checked')
      || form.querySelector('select[name="output[aspect_ratio]"]');
    const selectedQuality = form.querySelector('[name="output[quality]"]:checked')
      || form.querySelector('select[name="output[quality]"]');
    const selectedMainQuality = form.querySelector('[name="output[main_quality]"]:checked')
      || form.querySelector('input[type="hidden"][name="output[main_quality]"]');
    if (selectedAspectRatio?.value) data.set('output[aspect_ratio]', selectedAspectRatio.value);
    if (selectedQuality?.value) data.set('output[quality]', selectedQuality.value);
    if (selectedMainQuality?.value) data.set('output[main_quality]', selectedMainQuality.value);
    let payload = {};
    try {
      const response = await fetch(root.dataset.generateUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: data });
      const responseText = await response.text();
      try { payload = responseText ? JSON.parse(responseText) : {}; } catch (_) {}
      if (response.status === 401 || response.status === 419) {
        throw new Error('نشست شما منقضی شده است؛ صفحه را تازه‌سازی کنید و دوباره وارد شوید.');
      }
      if (!response.ok && !payload.message && !payload.errors) {
        throw new Error(response.status >= 500 ? 'ارتباط با سرویس ساخت تصویر برقرار نشد. لطفاً دوباره تلاش کنید.' : 'درخواست ساخت تصویر پذیرفته نشد.');
      }
      if (!response.ok || !payload.success) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'ساخت تصویر انجام نشد.');
      const images = normalizeImageOutputs(payload);
      if (!images.length) throw new Error('لینک خروجی تصویر از سرویس دریافت نشد.');
      const main = result.querySelector('[data-result-image]') || result.querySelector(':scope > img');
      if (!main) throw new Error('فضای نمایش خروجی تصویر در صفحه پیدا نشد.');
      main.src = images[0].url;
      result.dataset.generatedImageId = images[0].generated_image_id || '';
      const strip = result.querySelector('.cw-result-strip'); strip.innerHTML = '';
      images.forEach((image, index) => {
        const button = document.createElement('button'); button.type = 'button'; button.className = index === 0 ? 'active' : '';
        button.dataset.generatedImageId = image.generated_image_id || '';
        button.innerHTML = `<img src="${image.url}" alt=""><span>${index + 1}</span>`;
        button.addEventListener('click', () => { main.src = image.url; result.dataset.generatedImageId = image.generated_image_id || ''; [...strip.children].forEach((item) => item.classList.toggle('active', item === button)); updateRelatedVideoLinks(); });
        strip.appendChild(button);
      });
      updateRelatedVideoLinks();
      const resultMessage = images.length === 1
        ? 'یک خروجی آماده و در بخش پروفایل ذخیره شد'
        : `${Number(images.length).toLocaleString('fa-IR')} خروجی آماده و در بخش پروفایل ذخیره شد`;
      result.querySelector('.cw-result-count').innerHTML = `<i class="fa-solid fa-circle-check"></i> ${resultMessage}`;
      if (payload?.credits_returned > 0) window.showCreditsReturnedModal?.(payload.credits_returned);
      // نمایش خروجی نباید به زمان بارگذاری تصویر وابسته باشد؛ تصویر در همان
      // پنل خروجی شروع به بارگذاری می‌کند و اگر شبکه کند باشد، صفحه قفل نمی‌شود.
      const imageReady = waitForStageImage(main);
      progress.hidden = true; result.hidden = false; revealOutputTab();
      await imageReady;
      await completeVisualProgress();
    } catch (error) {
      resetVisualProgress();
      progress.hidden = true; empty.hidden = false; setStageTab('upload'); alertText.textContent = error.message; alertBox.hidden = false;
      if (payload?.credits_returned > 0) window.showCreditsReturnedModal?.(payload.credits_returned);
    } finally { submit.disabled = false; }
  });
  root.querySelectorAll('.cw-result-strip button').forEach((button) => button.addEventListener('click', () => {
    root.querySelectorAll('.cw-result-strip button').forEach((item) => item.classList.toggle('active', item === button));
  }));
  root.querySelector('[data-action=download]')?.addEventListener('click', () => {
    const url = (root.querySelector('[data-result-image]') || root.querySelector('[data-result] > img'))?.src;
    if (!url) return;
    const trackUrl = root.dataset.downloadTrackUrl;
    if (trackUrl) {
      fetch(trackUrl, {
        method: 'POST',
        keepalive: true,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
          'Accept': 'application/json',
        },
      }).catch(() => {});
    }
    const link = document.createElement('a'); link.href = url; link.download = 'vatan-ai-output.png'; link.target = '_blank'; link.click();
  });
  root.querySelector('[data-action=regenerate]')?.addEventListener('click', () => root.querySelector('[data-action=generate]')?.click());
  function updateRelatedVideoLinks() {
    const id = result?.dataset.generatedImageId || '';
    const actions = root.querySelector('[data-related-video-actions]');
    if (!actions) return;
    actions.hidden = !id;
    actions.querySelectorAll('[data-convert-video]').forEach((link) => {
      const url = link.dataset.videoUrl || link.href.split('?')[0];
      link.href = id ? `${url}${url.includes('?') ? '&' : '?'}source_generated_image=${encodeURIComponent(id)}` : url;
    });
  }
  updateReadiness();
  if (root.dataset.loaderDemo === '1') {
    setStageTab('upload');
    if (emptyStage) emptyStage.hidden = true;
    if (outputPlaceholder) outputPlaceholder.hidden = true;
    if (resultStage) resultStage.hidden = true;
    if (progressStage) {
      progressStage.hidden = false;
      startVisualProgress();
    }
  } else if (root.dataset.resultDemo === '1') {
    hasGeneratedOutput = true;
    if (emptyStage) emptyStage.hidden = true;
    if (progressStage) progressStage.hidden = true;
    if (outputPlaceholder) outputPlaceholder.hidden = true;
    if (outputStageTab) outputStageTab.hidden = false;
    if (resultStage) resultStage.hidden = false;
    setStageTab('output');
  }
  });
}());
