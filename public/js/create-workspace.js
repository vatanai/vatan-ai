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
    upload.addEventListener('drop', (event) => renderFiles(event.dataTransfer.files));
    input.addEventListener('change', () => renderFiles(input.files));
    function renderFiles(files) {
      preview.innerHTML = '';
      [...files].slice(0, input.multiple ? 4 : 1).forEach((file) => {
        const item = document.createElement('div'); item.className = 'cw-preview-item';
        if (file.type.startsWith('image/')) {
          const image = document.createElement('img'); image.src = URL.createObjectURL(file); item.appendChild(image);
        } else { item.innerHTML = '<i class="fa-solid fa-file"></i>'; }
        const name = document.createElement('span'); name.textContent = file.name; item.appendChild(name); preview.appendChild(item);
      });
      if (input.accept.includes('image') && files.length) { hasPrimaryImage = true; updateReadiness(); }
      if (input.accept.includes('image') && files.length) {
        alertBox.hidden = true;
        upload.style.borderColor = '';
      }
    }
  });

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
    root.querySelector('[data-cost]').textContent = baseCost + extra;
  }
  form.addEventListener('change', recalculateCost);
  form.addEventListener('input', recalculateCost);
  recalculateCost();
  root.querySelectorAll('[data-ratio]').forEach((input) => input.addEventListener('change', () => {
    root.querySelector('[data-ratio-label]').textContent = input.nextElementSibling.querySelector('b').textContent;
  }));

  root.querySelector('[data-action=reset]').addEventListener('click', () => window.location.reload());
  root.querySelector('[data-action=generate]').addEventListener('click', async () => {
    const requiredUpload = form.querySelector('.cw-field:has(input[type=file]) .cw-label b')?.closest('.cw-field')?.querySelector('.cw-upload');
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
      const payload = await response.json();
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
  root.querySelector('[data-action=download]').addEventListener('click', () => {
    const url = root.querySelector('[data-result] > img').src;
    if (!url) return;
    const link = document.createElement('a'); link.href = url; link.download = 'vatan-ai-output.png'; link.target = '_blank'; link.click();
  });
  root.querySelector('[data-action=regenerate]').addEventListener('click', () => root.querySelector('[data-action=generate]').click());
  updateReadiness();
}());
