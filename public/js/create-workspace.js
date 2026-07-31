(function () {
  'use strict';
  const root = document.querySelector('.cw-page');
  if (!root) return;
  const form = root.querySelector('#createPreviewForm');
  const alertBox = root.querySelector('[data-form-alert]');
  const alertText = alertBox.querySelector('span');

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
  let hasPrimaryImage = false;
  function updateReadiness() {
    const value = hasPrimaryImage ? 92 : 35;
    readiness.textContent = value.toLocaleString('fa-IR') + '٪';
    scoreBar.style.width = value + '%';
    readinessText.textContent = hasPrimaryImage ? 'همه‌چیز برای یک خروجی دقیق آماده است.' : 'ابتدا تصویر اصلی را اضافه کنید.';
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

  const baseCost = Number(root.querySelector('[data-cost]').textContent);
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
    root.querySelector('[data-cost]').textContent = baseCost + extra;
  }
  form.addEventListener('change', recalculateCost);
  form.addEventListener('input', recalculateCost);
  recalculateCost();
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
  root.querySelector('[data-action=generate]')?.addEventListener('click', async () => {
    const requiredUploadField = [...form.querySelectorAll('.cw-field')].find((field) => field.querySelector('input[type=file]') && field.querySelector('.cw-label b'));
    const requiredUpload = requiredUploadField?.querySelector('.cw-upload');
    if (requiredUpload && !requiredUpload.querySelector('input[type=file]').files.length) {
      alertText.textContent = 'برای ادامه، تصویر الزامی را اضافه کنید.';
      alertBox.hidden = false; requiredUpload.style.borderColor = 'var(--red)';
      tabs.find((tab) => tab.dataset.tab === 'basic').click(); requiredUpload.scrollIntoView({ behavior: 'smooth', block: 'center' }); return;
    }
    if (root.dataset.authenticated !== '1' && root.dataset.preview !== '1') {
      window.location.href = root.dataset.loginUrl; return;
    }
    const empty = root.querySelector('[data-empty]'); const progress = root.querySelector('[data-progress]'); const result = root.querySelector('[data-result]');
    empty.hidden = true; result.hidden = true; progress.hidden = false;
    const bar = progress.querySelector('.cw-progress-track i'); const text = progress.querySelector('[data-progress-text]');
    bar.style.width = '18%'; text.textContent = 'در حال بررسی ورودی‌ها';
    if (root.dataset.preview === '1' || !root.dataset.generateUrl) {
      setTimeout(() => { bar.style.width = '58%'; text.textContent = 'در حال حفظ جزئیات چهره'; }, 650);
      setTimeout(() => { bar.style.width = '86%'; text.textContent = 'پرداخت نهایی تصویر'; }, 1400);
      setTimeout(() => { progress.hidden = true; result.hidden = false; }, 2300);
      return;
    }
    const submit = root.querySelector('[data-action=generate]'); submit.disabled = true;
    const data = new FormData(form);
    root.querySelectorAll('[data-field-type=aspect_ratio] input:checked').forEach((input) => data.append('output[aspect_ratio]', input.value));
    root.querySelectorAll('[data-field-type=resolution] input:checked').forEach((input) => data.append('output[quality]', input.value));
    try {
      const response = await fetch(root.dataset.generateUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }, body: data });
      const responseText = await response.text();
      let payload = {};
      try { payload = responseText ? JSON.parse(responseText) : {}; } catch (_) {}
      if (response.status === 401 || response.status === 419) {
        throw new Error('نشست شما منقضی شده است؛ صفحه را تازه‌سازی کنید و دوباره وارد شوید.');
      }
      if (!response.ok && !payload.message && !payload.errors) {
        throw new Error(response.status >= 500 ? 'ارتباط با سرویس ساخت تصویر برقرار نشد. لطفاً دوباره تلاش کنید.' : 'درخواست ساخت تصویر پذیرفته نشد.');
      }
      if (!response.ok || !payload.success) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'ساخت تصویر انجام نشد.');
      const images = payload.images?.length ? payload.images : [{ url: payload.image_url, title: '' }];
      const main = result.querySelector(':scope > img'); main.src = images[0].url;
      const strip = result.querySelector('.cw-result-strip'); strip.innerHTML = '';
      images.forEach((image, index) => {
        const button = document.createElement('button'); button.type = 'button'; button.className = index === 0 ? 'active' : '';
        button.innerHTML = `<img src="${image.url}" alt=""><span>${index + 1}</span>`;
        button.addEventListener('click', () => { main.src = image.url; [...strip.children].forEach((item) => item.classList.toggle('active', item === button)); });
        strip.appendChild(button);
      });
      result.querySelector('.cw-result-count').innerHTML = `<i class="fa-solid fa-circle-check"></i> ${Number(images.length).toLocaleString('fa-IR')} خروجی آماده شد`;
      bar.style.width = '100%'; progress.hidden = true; result.hidden = false;
    } catch (error) {
      progress.hidden = true; empty.hidden = false; alertText.textContent = error.message; alertBox.hidden = false;
    } finally { submit.disabled = false; }
  });
  root.querySelectorAll('.cw-result-strip button').forEach((button) => button.addEventListener('click', () => {
    root.querySelectorAll('.cw-result-strip button').forEach((item) => item.classList.toggle('active', item === button));
  }));
  root.querySelector('[data-action=download]')?.addEventListener('click', () => {
    const url = root.querySelector('[data-result] > img').src;
    if (!url) return;
    const link = document.createElement('a'); link.href = url; link.download = 'vatan-ai-output.png'; link.target = '_blank'; link.click();
  });
  root.querySelector('[data-action=regenerate]')?.addEventListener('click', () => root.querySelector('[data-action=generate]')?.click());
  updateReadiness();
}());
