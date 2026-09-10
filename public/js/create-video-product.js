(function () {
  'use strict';

  const workspace = document.querySelector('[data-video-workspace]');
  if (!workspace) return;
  const form = workspace.querySelector('[data-video-form]');
  const configNode = workspace.querySelector('[data-video-config]');
  const config = JSON.parse(configNode?.textContent || '{}');
  const submit = form.querySelector('.vv-generate');
  const submitLabel = submit.querySelector('span');
  const errorBox = form.querySelector('[data-video-error]');
  const statusLabel = workspace.querySelector('[data-video-status]');
  const processing = workspace.querySelector('[data-video-processing]');
  const processingMessage = workspace.querySelector('[data-processing-message]');
  const placeholder = workspace.querySelector('[data-result-placeholder]');
  const resultVideo = workspace.querySelector('[data-result-video]');
  const productPreview = workspace.querySelector('[data-product-preview]');
  let polling = false;

  workspace.querySelector('[data-video-close]')?.addEventListener('click', function () {
    if (window.history.length > 1) window.history.back();
    else window.location.href = '/app';
  });

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function clearError() {
    errorBox.hidden = true;
    errorBox.textContent = '';
  }

  function selected(name) {
    return form.querySelector(`[name="${name}"]:checked`)?.value || '';
  }

  function updateCost() {
    const duration = selected('video[duration]');
    const resolution = selected('video[resolution]');
    const audio = form.querySelector('[name="video[generate_audio]"]:checked')?.value === '1';
    const identity = !!form.querySelector('[name="face_profile_id"]')?.value;
    const quality = selected('video[quality]') || 'standard';
    const tierCosts = config.quality_credit_costs || {};
    const durationBase = Number(config.duration_costs?.[duration] ?? config.base_cost ?? 0);
    const defaultDurationBase = Number(config.duration_costs?.[config.default_duration] ?? config.base_cost ?? 0);
    const value = (Object.prototype.hasOwnProperty.call(tierCosts, quality)
      ? Number(tierCosts[quality]) + durationBase - defaultDurationBase
      : durationBase + Number(config.quality_costs?.[resolution] ?? 0)) + (audio ? 3 : 0) + (identity ? 2 : 0);
    form.querySelectorAll('[data-quality-cost-label]').forEach((label) => {
      const key = label.dataset.qualityCostLabel || 'standard';
      const qualityCost = Object.prototype.hasOwnProperty.call(tierCosts, key)
        ? Number(tierCosts[key]) + durationBase - defaultDurationBase
        : durationBase;
      const tierInput = form.querySelector(`[name="video[quality]"][value="${CSS.escape(key)}"]`);
      label.textContent = `${tierInput?.dataset.qualityResolution || ''} · ${new Intl.NumberFormat('fa-IR').format(Math.max(0, qualityCost))} اعتبار`;
    });
    workspace.querySelector('[data-video-cost]').textContent = new Intl.NumberFormat('fa-IR').format(value);
    const balance = Number(config.balance || 0);
    const note = workspace.querySelector('[data-video-balance]');
    const authenticated = form.dataset.authenticated === '1';
    if (note) { note.textContent = !authenticated || balance >= value ? `موجودی: ${new Intl.NumberFormat('fa-IR').format(balance)} اعتبار` : 'اعتبار کافی نیست'; note.classList.toggle('is-insufficient', authenticated && balance < value); }
    if (submit) {
      submit.disabled = false;
      submit.dataset.requiredCredits = String(Math.max(0, value));
      submit.classList.toggle('is-credit-locked', authenticated && balance < value);
      submit.setAttribute('aria-label', authenticated && balance < value ? 'افزایش اعتبار برای ساخت ویدیو' : 'ساخت ویدیو');
    }
  }
  form.querySelectorAll('[name="video[duration]"]').forEach((input) => input.addEventListener('change', updateCost));
  form.querySelectorAll('[name="video[resolution]"],[name="video[quality]"],[name="video[generate_audio]"],[name="face_profile_id"]').forEach((input) => input.addEventListener('change', updateCost));
  form.querySelectorAll('[name="video[quality]"]').forEach((input) => input.addEventListener('change', function () {
    const resolution = this.dataset.qualityResolution;
    const target = resolution ? form.querySelector(`[name="video[resolution]"][value="${CSS.escape(resolution)}"]`) : null;
    if (target) { target.checked = true; updateCost(); }
  }));
  updateCost();

  workspace.querySelector('[data-prompt-example]')?.addEventListener('click', function () {
    const prompt = form.querySelector('[name="prompt"]');
    if (prompt.value.trim()) return prompt.focus();
    prompt.value = 'حرکت طبیعی و نرم سوژه، دوربین سینمایی آرام، نورپردازی واقع‌گرایانه، جزئیات پایدار و بدون تغییر ناگهانی چهره یا پس‌زمینه';
    prompt.focus();
  });

  function bindPreview(input, preview) {
    if (!input || !preview) return;
    input.addEventListener('change', function () {
      if (!input.files?.[0]) return;
      preview.src = URL.createObjectURL(input.files[0]);
      preview.hidden = false;
      if (preview.tagName === 'VIDEO') preview.load();
    });
  }
  bindPreview(form.querySelector('[data-source-image]'), form.querySelector('[data-source-preview]'));
  bindPreview(form.querySelector('[data-source-video]'), form.querySelector('[data-source-video-preview]'));

  const faceRoot = form.querySelector('[data-face-source]');
  if (faceRoot) {
    const toggle = faceRoot.querySelector('[data-face-source-toggle]');
    const menu = faceRoot.querySelector('[data-face-source-menu]');
    const hidden = faceRoot.querySelector('[data-face-profile-input]');
    const label = faceRoot.querySelector('[data-face-source-label]');
    const sourceSection = form.querySelector('[data-source-section="image"]');
    const profileNotice = form.querySelector('[data-profile-selected]');
    const sourceInput = form.querySelector('[data-source-image]');

    function closeMenu() {
      menu.hidden = true;
      faceRoot.classList.remove('is-open');
      form.classList.remove('cw-face-menu-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
    toggle.addEventListener('click', function () {
      const willOpen = menu.hidden;
      menu.hidden = !willOpen;
      faceRoot.classList.toggle('is-open', willOpen);
      form.classList.toggle('cw-face-menu-open', willOpen);
      toggle.setAttribute('aria-expanded', String(willOpen));
    });
    faceRoot.querySelectorAll('[data-face-source-option]').forEach(function (option) {
      option.addEventListener('click', function () {
        faceRoot.querySelectorAll('[data-face-source-option]').forEach((item) => item.classList.remove('selected'));
        option.classList.add('selected');
        hidden.value = option.dataset.faceProfileId || '';
        label.textContent = option.querySelector('b')?.textContent || 'عکس جدید';
        const hasProfile = hidden.value !== '';
        if (sourceSection) sourceSection.hidden = hasProfile;
        if (profileNotice) profileNotice.hidden = !hasProfile;
        if (sourceInput) sourceInput.disabled = hasProfile;
        closeMenu();
      });
    });
    document.addEventListener('click', function (event) {
      if (!faceRoot.contains(event.target)) closeMenu();
    });
  }

  function validateSource() {
    if (config.workflow === 'image_to_video') {
      const profileId = form.querySelector('[name="face_profile_id"]')?.value;
      const image = form.querySelector('[name="source_image"]')?.files?.[0];
      const generatedImageId = form.querySelector('[name="source_generated_image_id"]')?.value;
      if (!profileId && !image && !generatedImageId) return 'یک پروفایل چهره یا تصویر شروع انتخاب کنید.';
    }
    if (config.workflow === 'video_to_video' && !form.querySelector('[name="source_video"]')?.files?.[0]) {
      return 'ویدیوی ورودی را انتخاب کنید.';
    }
    return '';
  }

  function enterProcessing(message) {
    clearError();
    submit.disabled = true;
    submitLabel.textContent = 'در حال ارسال...';
    statusLabel.textContent = 'در صف پردازش';
    processingMessage.textContent = message || 'درخواست با موفقیت در صف پردازش قرار گرفت.';
    processing.hidden = false;
    placeholder.hidden = true;
    if (productPreview) productPreview.hidden = true;
    resultVideo.hidden = true;
  }

  function showResult(url) {
    polling = false;
    processing.hidden = true;
    resultVideo.src = url;
    resultVideo.hidden = false;
    resultVideo.load();
    resultVideo.play().catch(function () {});
    statusLabel.textContent = 'ساخت کامل شد';
    submit.disabled = false;
    submitLabel.textContent = 'ساخت دوباره';
  }

  async function poll(url, attempt) {
    if (!polling) return;
    try {
      const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const data = await response.json();
      if (data.status === 'completed' && data.video_url) return showResult(data.video_url);
      if (['failed', 'canceled'].includes(data.status)) {
        polling = false;
        processing.hidden = true;
        submit.disabled = false;
        submitLabel.textContent = 'تلاش دوباره';
        statusLabel.textContent = 'ساخت کامل نشد';
        if (data.credits_returned > 0) window.showCreditsReturnedModal?.(data.credits_returned);
        return showError(data.error_message || 'ساخت ویدیو کامل نشد؛ اعتبار رزروشده بازگردانده شد.');
      }
      statusLabel.textContent = data.status === 'processing' ? 'در حال ساخت' : 'در صف پردازش';
      processingMessage.textContent = data.status === 'processing' ? 'مدل در حال ساخت فریم‌های ویدیو است.' : 'درخواست منتظر شروع پردازش است.';
    } catch (error) {
      processingMessage.textContent = 'ارتباط موقتاً قطع شد؛ وضعیت دوباره بررسی می‌شود.';
    }
    if (attempt < 180) window.setTimeout(() => poll(url, attempt + 1), 5000);
    else {
      polling = false;
      processingMessage.textContent = 'پردازش طولانی شده است؛ نتیجه بعداً در پروفایل شما هم قابل مشاهده خواهد بود.';
      submit.disabled = false;
      submitLabel.textContent = 'ساخت ویدیو';
    }
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearError();
    if (form.dataset.authenticated !== '1') {
      window.location.href = form.dataset.loginUrl;
      return;
    }
    if (!form.reportValidity()) return;
    const sourceError = validateSource();
    if (sourceError) return showError(sourceError);
    const requiredCredits = Number(submit.dataset.requiredCredits || 0);
    const balance = Number(config.balance || 0);
    if (requiredCredits > 0 && balance < requiredCredits) {
      window.showTokenShortageModal?.({ required: requiredCredits, balance });
      return;
    }
    enterProcessing('درخواست در حال ارسال به موتور ساخت ویدیو است.');

    try {
      const response = await fetch(form.action, {
        method: 'POST', body: new FormData(form), credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
      });
      const data = await response.json();
      if (!response.ok) {
        const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(validation || data.message || 'ارسال درخواست انجام نشد.');
      }
      statusLabel.textContent = 'در صف پردازش';
      processingMessage.textContent = data.message || 'درخواست در صف ساخت قرار گرفت.';
      polling = true;
      poll(data.status_url, 0);
    } catch (error) {
      polling = false;
      processing.hidden = true;
      placeholder.hidden = false;
      if (productPreview) productPreview.hidden = false;
      submit.disabled = false;
      submitLabel.textContent = 'ساخت ویدیو';
      statusLabel.textContent = 'آماده ساخت';
      showError(error.message || 'ارسال درخواست انجام نشد.');
    }
  });
}());
