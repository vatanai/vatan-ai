(function () {
  'use strict';
  var root = document.getElementById('product-shot-wizard');
  var form = document.getElementById('shot-product-form');
  if (!root || !form) return;
  var config = window.PRODUCT_SHOT_FORM_CONFIG || {};
  var step = 1;
  var fa = function (value) { return String(value).replace(/[0-9]/g, function (n) { return '۰۱۲۳۴۵۶۷۸۹'[n]; }); };
  var one = function (selector, context) { return (context || document).querySelector(selector); };
  var all = function (selector, context) { return Array.from((context || document).querySelectorAll(selector)); };

  function showStep(next) {
    step = Math.max(1, Math.min(5, Number(next) || 1));
    all('[data-step-panel]', root).forEach(function (panel) { panel.hidden = Number(panel.dataset.stepPanel) !== step; });
    all('[data-step-tab]', root).forEach(function (tab) {
      var number = Number(tab.dataset.stepTab);
      var active = number === step;
      var passed = number < step;
      tab.classList.toggle('is-active', active);
      tab.classList.toggle('is-complete', passed);
      tab.setAttribute('aria-current', active ? 'step' : 'false');
    });
    one('[data-wizard-prev]', root).hidden = step === 1;
    one('[data-wizard-next]', root).hidden = step === 5;
    one('[data-save-active]', root).hidden = step !== 5;
    one('[data-wizard-progress]', root).style.width = (step * 20) + '%';
    one('[data-wizard-progress-label]', root).textContent = 'گام ' + fa(step) + ' از ۵';
    one('#psw-validation', root).classList.add('hidden');
    if (step === 5) refreshSummary();
    root.querySelector('.admin-content').scrollTo({top: 0, behavior: 'smooth'});
  }

  function error(message) {
    var box = one('#psw-validation', root);
    box.textContent = message;
    box.classList.remove('hidden');
    box.scrollIntoView({behavior: 'smooth', block: 'center'});
    return false;
  }

  function validateStep(number, finalSubmit) {
    if (number === 1) {
      if (!one('[name="name_fa"]', form).value.trim() || !one('[name="name_en"]', form).value.trim()) return error('نام فارسی و انگلیسی محصول را کامل کنید.');
      if (!all('[name="occupation_ids[]"]:checked', form).length) return error('حداقل یک صنف مناسب برای محصول انتخاب کنید.');
      if (finalSubmit && !all('[name="category_ids[]"]:checked', form).length && !Array.from(one('[name="category_ids[]"]', form).selectedOptions).length) return error('برای انتشار، حداقل یک دسته‌بندی انتخاب کنید.');
    }
    if (number === 2) {
      var missingModel = all('[name^="quality_models"][name$="[primary_id]"]', form).some(function (input) { return !input.value; });
      if (missingModel) return error('مدل اصلی هر سه سطح کیفیت را انتخاب کنید.');
    }
    if (number === 3) {
      var enabledRows = all('[data-shot-enabled]:checked', form);
      if (!enabledRows.length) return error('حداقل یک شات را فعال کنید.');
      if (!all('[data-shot-default]:checked', form).length) return error('حداقل یک شات را در بسته‌ی آماده قرار دهید.');
      var invalidRatio = enabledRows.some(function (checkbox) {
        return !all('[name$="[allowed_aspect_ratios][]"]:checked', checkbox.closest('[data-shot-row]')).length;
      });
      if (invalidRatio) return error('برای هر شات فعال، حداقل یک نسبت تصویر مجاز انتخاب کنید.');
    }
    if (number === 4 && finalSubmit && !all('[name="explore_tiles[]"]:checked', form).length) return error('حداقل یک قاب نمایش در اکسپلور انتخاب کنید.');
    return true;
  }

  function validateAll() {
    for (var i = 1; i <= 4; i++) {
      showStep(i);
      if (!validateStep(i, true)) return false;
    }
    showStep(5);
    return true;
  }

  all('[data-step-tab]', root).forEach(function (tab) { tab.addEventListener('click', function () { showStep(tab.dataset.stepTab); }); });
  one('[data-wizard-prev]', root).addEventListener('click', function () { showStep(step - 1); });
  one('[data-wizard-next]', root).addEventListener('click', function () { if (validateStep(step, false)) showStep(step + 1); });
  one('[data-save-draft]', root).addEventListener('click', function () {
    if (!validateStep(1, false) || !validateStep(2, false) || !validateStep(3, false)) return;
    one('#psw-status', form).value = 'draft';
    form.submit();
  });
  one('[data-save-active]', root).addEventListener('click', function () { if (!validateAll()) return; one('#psw-status', form).value = 'active'; form.submit(); });

  var occupationSearch = one('[data-occupation-search]', form);
  if (occupationSearch) occupationSearch.addEventListener('input', function () {
    var query = occupationSearch.value.trim().toLowerCase();
    all('[data-occupation-item]', form).forEach(function (item) { item.hidden = query && !(item.dataset.search || '').toLowerCase().includes(query); });
    all('.ps-occupation-group', form).forEach(function (group) { group.hidden = !all('[data-occupation-item]:not([hidden])', group).length; });
  });

  var brandToggle = one('[data-brand-identity-toggle]', form);
  if (brandToggle) brandToggle.addEventListener('change', function () { one('[data-brand-identity-prompt-wrap]', form).hidden = !brandToggle.checked; });

  all('[data-shot-toggle]', form).forEach(function (button) { button.addEventListener('click', function () { var detail = one('[data-shot-detail]', button.closest('[data-shot-row]')); detail.hidden = !detail.hidden; }); });
  all('[data-shot-enabled]', form).forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
      var row = checkbox.closest('[data-shot-row]');
      row.classList.toggle('is-off', !checkbox.checked);
      var defaultBox = one('[data-shot-default]', row);
      defaultBox.disabled = !checkbox.checked;
      if (!checkbox.checked) defaultBox.checked = false;
      refreshSummary();
    });
  });
  all('[data-shot-default], [name*="[quality_credits]"]', form).forEach(function (input) { input.addEventListener('change', refreshSummary); input.addEventListener('input', refreshSummary); });
  all('[name="explore_tiles[]"]', form).forEach(function (input) { input.addEventListener('change', refreshFrames); });

  var testImages = one('#sp-test-images', form);
  if (testImages) testImages.addEventListener('change', function () {
    if (testImages.files.length > 4) { testImages.value = ''; return error('حداکثر چهار تصویر تست انتخاب کنید.'); }
    one('[data-test-image-count]', form).textContent = testImages.files.length ? fa(testImages.files.length) + ' تصویر آماده‌ی پیش‌نمایش است.' : 'حداکثر ۴ تصویر از زوایای مکمل.';
  });

  function previewShot(button) {
    if (!testImages || !testImages.files.length) return error('برای پیش‌نمایش، حداقل یک تصویر تست انتخاب کنید.');
    var row = button.closest('[data-shot-row]');
    var body = new FormData();
    body.append('shot_id', button.dataset.shotPreview);
    Array.from(testImages.files).slice(0, 4).forEach(function (file) { body.append('images[]', file); });
    var override = one('[name*="[model_overrides][standard][primary_id]"]', row);
    var global = one('[name="quality_models[standard][primary_id]"]', form);
    body.append('ai_model_id', override && override.value ? override.value : global.value);
    body.append('product_description', one('[name="product_description"]', form).value || '');
    body.append('brand_palette', one('[name="brand_palette"]', form).value || '');
    body.append('brand_style', one('[name="brand_style"]', form).value || '');
    body.append('brand_identity_enabled', brandToggle && brandToggle.checked ? '1' : '0');
    body.append('brand_identity_prompt', one('[name="brand_identity_prompt"]', form).value || '');
    body.append('aspect_ratio', one('#sp-test-ratio', form).value);
    var result = one('[data-shot-result]', row);
    button.disabled = true; result.textContent = 'در حال ساخت پیش‌نمایش واقعی…';
    fetch(config.previewUrl, {method: 'POST', headers: {'X-CSRF-TOKEN': one('[name="_token"]', form).value, 'Accept': 'application/json'}, body: body, credentials: 'same-origin'})
      .then(function (response) { return response.json().then(function (json) { return {ok: response.ok, body: json}; }); })
      .then(function (response) {
        if (!response.ok || !response.body.ok) throw new Error(response.body.message || 'پیش‌نمایش ساخته نشد.');
        var thumb = one('[data-shot-thumb]', row); thumb.innerHTML = '<img src="' + response.body.image_url + '" alt="">';
        one('[data-shot-sample]', row).value = response.body.image_path;
        result.textContent = 'پیش‌نمایش آماده شد · مدل: ' + (response.body.model || '—') + ' · هزینه: $' + Number(response.body.cost_usd || 0).toFixed(4);
        refreshSummary();
      })
      .catch(function (caught) { result.textContent = caught.message || 'پیش‌نمایش ساخته نشد.'; })
      .finally(function () { button.disabled = false; });
  }
  all('[data-shot-preview]', form).forEach(function (button) { button.addEventListener('click', function () { previewShot(button); }); });

  function refreshTotals() {
    Object.keys(config.qualityLevels || {}).forEach(function (quality) {
      var total = 0;
      all('[data-shot-row]', form).forEach(function (row) {
        if (!one('[data-shot-enabled]', row).checked || !one('[data-shot-default]', row).checked) return;
        var credit = one('[name$="[quality_credits][' + quality + ']"]', row);
        total += Number(credit && credit.value || 0);
      });
      var card = one('[data-quality-total="' + quality + '"]', form);
      if (card) one('strong', card).textContent = fa(total.toLocaleString('en-US')) + ' اعتبار';
    });
  }

  function refreshFrames() {
    all('[data-preview-frame]', form).forEach(function (frame) {
      var checkbox = one('[name="explore_tiles[]"][value="' + frame.dataset.previewFrame + '"]', form);
      frame.classList.toggle('is-disabled', !checkbox || !checkbox.checked);
    });
  }

  function refreshSummary() {
    refreshTotals(); refreshFrames();
    var name = one('[name="name_fa"]', form).value.trim() || '—';
    var occupations = all('[name="occupation_ids[]"]:checked', form).map(function (input) { return one('span', input.closest('label')).textContent.trim(); });
    var enabled = all('[data-shot-enabled]:checked', form).length;
    var defaults = all('[data-shot-default]:checked', form).length;
    var standard = one('[name="quality_models[standard][primary_id]"]', form);
    var model = standard && standard.selectedOptions[0] ? standard.selectedOptions[0].textContent.trim() : '—';
    var standardCredits = 0;
    all('[data-shot-row]', form).forEach(function (row) { if (one('[data-shot-enabled]', row).checked && one('[data-shot-default]', row).checked) standardCredits += Number(one('[name$="[quality_credits][standard]"]', row).value || 0); });
    var values = {name: name, occupations: occupations.join('، ') || '—', shots: fa(enabled), defaults: fa(defaults), model: model, credits: fa(standardCredits) + ' اعتبار'};
    Object.keys(values).forEach(function (key) { var target = one('[data-summary="' + key + '"]', form); if (target) target.textContent = values[key]; });
    var ready = name !== '—' && occupations.length && enabled && defaults;
    var status = one('[data-final-status]', form); if (status) { status.textContent = ready ? 'آماده ثبت' : 'نیازمند تکمیل'; status.classList.toggle('badge-success', !!ready); }
  }

  form.addEventListener('input', function () { if (step === 5) refreshSummary(); });
  form.addEventListener('change', function () { if (step === 5) refreshSummary(); });
  showStep(1); refreshTotals();
})();
