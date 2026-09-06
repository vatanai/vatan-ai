/* ══════════════════════════════════════════════════════════════════
   جاوااسکریپت صفحه «ثبت محصول جدید» (admin/products/create.blade.php)
   مقادیر پویا (مدل‌های AI، ایندکس‌های شروع، زیردسته انتخابی) از طریق
   window.PRODUCT_CREATE_CONFIG که در خود Blade تزریق می‌شود خوانده می‌شوند.
   هیچ Route/API جدیدی صدا زده نمی‌شود؛ submitForm() و Validation واقعی سرور دست‌نخورده می‌مانند.
   ══════════════════════════════════════════════════════════════════ */
const CFG = window.PRODUCT_CREATE_CONFIG || {};
const AI_MODELS = CFG.aiModels || [];

let cur = 1;

/* تبدیل ارقام لاتین به فارسی برای نمایش کسری/درصد */
function toFa(v) { return String(v).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }

/* ── وضعیت تکمیل هر مرحله بر اساس فیلدهای اجباری واقعی ──
   خروجی: { total, filled, complete, dynamic }
   مرحله ۳ پویاست: بر اساس ردیف‌های واقعاً اضافه‌شده‌ی «فیلدهای ورودی کاربر» (بند ۴۵). */
function computeStepStatus(n) {
  if (n === 5) {
    const ready = [1, 2, 3, 4].every(function (step) {
      const status = computeStepStatus(step);
      return status.total === 0 || status.complete;
    });
    return { total: 1, filled: ready ? 1 : 0, complete: ready, dynamic: true };
  }
  if (n === 3) {
    const featuresEnabled = document.getElementById('special-features-enabled')?.value === '1';
    const identityFilled = progressReady && progressFieldValue('identity_preservation') ? 1 : 0;
    if (!featuresEnabled) {
      return {
        total: 1,
        filled: identityFilled,
        complete: identityFilled === 1,
        dynamic: true
      };
    }
    const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
    if (!rows.length) {
      return { total: 2, filled: identityFilled, complete: false, dynamic: true };
    }
    let filled = identityFilled;
    rows.forEach(function (r) {
      const requiredInputsComplete = Array.from(r.querySelectorAll('[required]')).every(function (input) {
        return String(input.value || '').trim() !== '' && input.checkValidity();
      });
      const builderValidationPassed = !r.classList.contains('sb-invalid');
      if (requiredInputsComplete && builderValidationPassed) filled++;
    });
    return { total: rows.length + 1, filled: filled, complete: filled === rows.length + 1, dynamic: true };
  }
  const req = STEP_REQUIRED_FIELDS[n] || [];
  let filled = 0;
  req.forEach(function (pair) { if (progressReady && progressFieldValue(pair[0])) filled++; });
  return { total: req.length, filled: filled, complete: req.length === 0 ? true : filled === req.length, dynamic: false };
}

/* ── رندر کامل Stepper (بندهای ۴۲، ۴۳، ۴۵، ۴۷) ──
   حالت دایره‌ها بر اساس «تکمیل واقعی» (نه صرفاً عبور از مرحله)، کسری فیلدها،
   تیک سبز تکمیل، خط اتصال و نوار پیشرفت کلی. با هر تغییر ورودی و هر ناوبری اجرا می‌شود. */
function renderStepper() {
  let overallTotal = 0, overallFilled = 0;

  for (let i = 1; i <= 5; i++) {
    const tab = document.getElementById('step-tab-' + i);
    const num = document.getElementById('step-num-' + i);
    if (!tab || !num) continue;
    const label = tab.querySelector('.step-label');
    const title = tab.querySelector('.step-title');
    const fracEl = document.getElementById('step-frac-' + i);
    const checkEl = document.getElementById('step-check-' + i);
    const st = computeStepStatus(i);

    // ریست کلاس‌های حالت
    tab.classList.remove('bg-[var(--accent)]/10','border-[var(--accent)]/25','step-tab-active','bg-[var(--green)]/5','border-[var(--green)]/20');
    num.classList.remove('border-[var(--accent)]','bg-[var(--accent)]/15','text-[var(--accent)]','border-[var(--green)]','bg-[var(--green)]/15','text-[var(--green)]','border-[var(--b2)]','text-[var(--text3)]','scale-105');
    if (label) label.classList.remove('text-[var(--accent)]','text-[var(--green)]','text-[var(--text3)]');
    if (title) title.classList.remove('text-[var(--text)]','text-[var(--text2)]');

    if (i === cur) {
      // Active — همیشه شماره‌ی هایلایت‌شده (حتی اگر کامل باشد)
      tab.classList.add('bg-[var(--accent)]/10','border-[var(--accent)]/25','step-tab-active');
      num.classList.add('border-[var(--accent)]','bg-[var(--accent)]/15','text-[var(--accent)]','scale-105');
      num.innerHTML = num.dataset.num;
      if (label) label.classList.add('text-[var(--accent)]');
      if (title) title.classList.add('text-[var(--text)]');
    } else if (st.complete && st.total > 0) {
      // Completed — تیک سبز روی دایره (بر اساس تکمیل واقعی، نه عبور)
      tab.classList.add('bg-[var(--green)]/5','border-[var(--green)]/20');
      num.classList.add('border-[var(--green)]','bg-[var(--green)]/15','text-[var(--green)]');
      num.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
      if (label) label.classList.add('text-[var(--green)]');
      if (title) title.classList.add('text-[var(--text2)]');
    } else {
      // Pending
      num.classList.add('border-[var(--b2)]','text-[var(--text3)]');
      num.innerHTML = num.dataset.num;
      if (label) label.classList.add('text-[var(--text3)]');
      if (title) title.classList.add('text-[var(--text2)]');
    }

    // کسری فیلدهای پرشده (بند ۴۵) — فقط وقتی مرحله فیلد اجباری دارد و هنوز کامل نشده
    if (fracEl) {
      if (st.total > 0 && !st.complete) {
        fracEl.textContent = toFa(st.filled) + '/' + toFa(st.total);
        fracEl.classList.remove('hidden');
      } else {
        fracEl.classList.add('hidden');
      }
    }
    // تیک سبز تکمیل گوشه‌ی کارت (بند ۴۲)
    if (checkEl) checkEl.classList.toggle('hidden', !(st.total > 0 && st.complete));

    if (st.total > 0) { overallTotal += st.total; overallFilled += st.filled; }
  }

  // رنگ خط اتصال بین Stepها بر اساس مرحله‌ی فعلی
  [1,2,3,4].forEach(function (idx) {
    ['conn-'+idx, 'conn-'+idx+'-m'].forEach(function (id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.remove('bg-[var(--b1)]','bg-[var(--green)]/40');
      el.classList.add(cur > idx ? 'bg-[var(--green)]/40' : 'bg-[var(--b1)]');
    });
  });

  // نوار پیشرفت کلی فرم (بند ۴۳/۴۷)
  const pct = overallTotal ? Math.round(overallFilled / overallTotal * 100) : 0;
  const fill = document.getElementById('wizard-progress-fill');
  const pctEl = document.getElementById('wizard-progress-pct');
  if (fill) fill.style.width = pct + '%';
  if (pctEl) pctEl.textContent = toFa(pct) + '٪';
  updateFinalSubmitButton(overallFilled, overallTotal);
}

function updateFinalSubmitButton(filled, total) {
  const button = document.getElementById('btn-submit');
  const progress = document.getElementById('final-submit-progress');
  const icon = document.getElementById('final-submit-icon');
  if (!button) return;
  const complete = total > 0 && filled === total;
  if (progress) progress.textContent = toFa(filled) + '/' + toFa(total);
  button.dataset.formComplete = complete ? '1' : '0';
  button.setAttribute('aria-disabled', complete ? 'false' : 'true');
  button.classList.toggle('bg-[var(--green)]', complete);
  button.classList.toggle('text-white', complete);
  button.classList.toggle('hover:bg-[var(--green-hover)]', complete);
  button.classList.toggle('bg-[var(--b1)]', !complete);
  button.classList.toggle('text-[var(--text3)]', !complete);
  button.classList.toggle('border', !complete);
  button.classList.toggle('border-[var(--b2)]', !complete);
  if (icon) icon.className = complete ? 'fa-solid fa-check' : 'fa-solid fa-lock';
}

/* ── Stepper: جابه‌جایی بین مراحل (پیمایش همیشه آزاد — بند ۴۴) ── */
function goStep(n) {
  if (n < 1 || n > 5) return;
  cur = n;

  for (let i = 1; i <= 5; i++) {
    const p = document.getElementById('panel-' + i);
    if (!p) continue;
    if (i === n) { p.classList.remove('hidden'); p.classList.add('block'); }
    else { p.classList.remove('block'); p.classList.add('hidden'); }
  }

  document.getElementById('btn-prev').style.display = n === 1 ? 'none' : 'inline-flex';
  const nextButton = document.getElementById('btn-next');
  nextButton.style.display = 'inline-flex';
  nextButton.disabled = n === 5;
  nextButton.classList.toggle('opacity-40', n === 5);
  nextButton.classList.toggle('cursor-not-allowed', n === 5);
  document.getElementById('btn-submit').style.display = 'inline-flex';
  document.getElementById('step-label-num').textContent = toFa(n);
  window.scrollTo({ top: 0, behavior: 'smooth' });

  renderStepper();
  lazyInitStep(n); // Step Lazy Loading — مقداردهی سنگین هر Step فقط در اولین بازدید آن
  ProductCreateState.ui.currentStep = n;
  if (n === 5 && typeof refreshProductPreview === 'function') refreshProductPreview();
}

/* ── Step Lazy Loading: کامپوننت‌های سنگین (Searchable Select و پیش‌نمایش فرم) Step ۲ و ۳
   فقط در اولین باری که کاربر وارد آن Step می‌شود مقداردهی می‌شوند، نه در بارگذاری اولیه صفحه ── */
const lazyInitedSteps = new Set([1]); // Step ۱ همراه بارگذاری صفحه مقداردهی می‌شود
function lazyInitStep(n) {
  if (lazyInitedSteps.has(n)) return;
  lazyInitedSteps.add(n);
  const panel = document.getElementById('panel-' + n);
  if (panel) initSearchables(panel);
  if (n === 2 && typeof onPrimaryModelChange === 'function') onPrimaryModelChange(); // پایپ‌لاین هوش مصنوعی
  if (n === 3 && typeof refreshFormPreview === 'function') refreshFormPreview();     // ورودی و متغیرها
  if (n === 5 && typeof refreshFinalSummary === 'function') refreshFinalSummary();   // بازبینی نهایی
}

/* ── State Management سبک: ProductCreateState فقط یک آینه از وضعیت UI است ── */
const ProductCreateState = { ui: { currentStep: 1 }, validation: { 1: true, 2: true, 3: true, 4: true, 5: true } };

/* ── Validation سیستم: حداقل فیلدهای الزامی هر Step (مطابق Validation واقعی کنترلر) ──
   توجه: الزامی‌بودن فایل‌ها (مثل Thumbnail) اینجا چک نمی‌شود چون در حالت تکثیر محصول ممکن است
   از قبل موجود باشد؛ تصمیم نهایی همیشه با Validation واقعی سمت سرور است. */
const STEP_REQUIRED_FIELDS = {
  1: [ ['name_fa', 'نام فارسی'], ['name_en', 'نام انگلیسی'], ['slug', 'آدرس URL'], ['category_ids', 'دسته‌بندی'], ['main_images', 'تصویر اصلی محصول'] ],
  2: [ ['primary_model', 'مدل اصلی هوش مصنوعی'], ['fallback_models[]', 'مدل جایگزین هوش مصنوعی'], ['prompt_template', 'متن پرامپت'] ],
  3: [ ['identity_preservation', 'وضعیت حفظ هویت'] ],
  4: [ ['credit_cost', 'هزینه کردیت محصول'] ],
  5: [], // بازبینی نهایی: صرفاً مرور است
};

const progressInitialValues = new Map();
const progressTouchedFields = new Set();
let progressReady = !CFG.isFreshProduct;

function progressFieldValue(name) {
  const current = fieldValue(name);
  if (!CFG.isFreshProduct) return current;
  return progressTouchedFields.has(name) || current !== (progressInitialValues.get(name) || '') ? current : '';
}

function fieldValue(name) {
  // دسته‌بندی چندگانه (تگ‌های انتخاب‌شده) دیگر یک <select> واحد با name=category_id نیست؛
  // تکمیل‌بودنش یعنی حداقل یک چیپ دسته‌بندی در cat-tags-wrap انتخاب شده باشد.
  if (name === 'category_ids') {
    return document.querySelectorAll('#cat-tags-wrap [data-cat-id]').length ? '1' : '';
  }
  if (name === 'main_images') {
    const input = document.getElementById('main-images-file');
    const group = input?.closest('.image-optimizer-group');
    const hasExisting = (group?.dataset.existing || '[]') !== '[]';
    return (input?.files?.length || hasExisting) ? '1' : '';
  }
  const els = document.getElementsByName(name);
  if (!els.length) return '';
  if (els.length > 1 && els[0].type === 'radio') {
    const checked = Array.from(els).find(e => e.checked);
    return checked ? checked.value : '';
  }
  return (els[0].value || '').trim();
}

function validateStep(n) {
  const missing = [];
  (STEP_REQUIRED_FIELDS[n] || []).forEach(([name, label]) => {
    if (!fieldValue(name)) missing.push({ name, label });
  });
  ProductCreateState.validation[n] = missing.length === 0;
  return missing;
}

/* خلاصه موارد ناقص — هر آیتم شامل {name, label, step} است تا لینک پرش مستقیم به همان مرحله/فیلد بسازد */
function showValidationSummary(missing) {
  const box = document.getElementById('validation-summary');
  const list = document.getElementById('validation-summary-list');
  if (!box || !list) return;
  if (!missing.length) { box.classList.add('hidden'); list.innerHTML = ''; return; }
  list.innerHTML = missing.map(function (m) {
    return '<li><a href="#" class="underline" onclick="jumpToField(\'' + m.name + '\',' + (m.step || cur) + '); return false;">' + m.label + '</a></li>';
  }).join('');
  box.classList.remove('hidden');
  box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function alertIncompleteRequiredFields(missing) {
  const list = missing.map(function (item, index) {
    return toFa(index + 1) + '. ' + item.label;
  }).join('\n');
  alert('ثبت نهایی هنوز آماده نیست. موارد اجباری زیر باقی مانده‌اند:\n\n' + list);
}

function focusField(name) {
  if (name === 'special_features') {
    const toggle = document.getElementById('special-features-enabled');
    if (toggle) toggle.closest('label')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  if (String(name).startsWith('schema_row_')) {
    const index = Number(String(name).replace('schema_row_', ''));
    const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
    const row = rows[index];
    if (row) {
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
      row.classList.add('border-[var(--red)]');
      const target = row.querySelector('.schema-label, .schema-id');
      if (target) target.focus();
      setTimeout(function () { row.classList.remove('border-[var(--red)]'); }, 2000);
    }
    return;
  }
  if (name === 'category_ids') {
    const wrap = document.getElementById('cat-tags-wrap');
    if (wrap) {
      wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const input = document.getElementById('cat-search-input');
      if (input) input.focus();
      wrap.classList.add('border-[var(--red)]');
      setTimeout(function () { wrap.classList.remove('border-[var(--red)]'); }, 2000);
    }
    return;
  }
  const el = document.getElementsByName(name)[0];
  if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); el.classList.add('border-[var(--red)]'); setTimeout(function () { el.classList.remove('border-[var(--red)]'); }, 2000); }
}

/* پرش مستقیم به یک فیلد ناقص در مرحله‌ی مربوطه (از روی لیست خطاهای ثبت نهایی) */
function jumpToField(name, step) {
  if (step && step !== cur) { goStep(step); setTimeout(function () { focusField(name); }, 80); }
  else focusField(name);
}

/* بند ۴۴: پیمایش بین همه‌ی مراحل همیشه آزاد است (بدون گیت اعتبارسنجی رو‌به‌جلو) */
function attemptGoStep(n) { goStep(n); }
function nextStep() { if (cur < 5) goStep(cur + 1); }
function prevStep() { if (cur > 1) goStep(cur - 1); }

/* جمع‌آوری تمام فیلدهای اجباری خالی در کل فرم — فقط در لحظه‌ی «ثبت نهایی» استفاده می‌شود (بند ۴۴) */
function collectAllMissing() {
  const missing = [];
  [1, 2, 3, 4, 5].forEach(function (n) {
    (STEP_REQUIRED_FIELDS[n] || []).forEach(function (pair) {
      if (!fieldValue(pair[0])) missing.push({ name: pair[0], label: pair[1], step: n });
    });
  });
  document.querySelectorAll('#input-fields-list .input-schema-row').forEach(function (row, index) {
    const id = (row.querySelector('.schema-id')?.value || '').trim();
    const label = (row.querySelector('.schema-label')?.value || '').trim();
    if (!id || !label) {
      const missingParts = [!label ? 'عنوان' : '', !id ? 'شناسه' : ''].filter(Boolean).join(' و ');
      missing.push({ name: 'schema_row_' + index, label: 'ویژگی شماره ' + toFa(index + 1) + ': ' + missingParts, step: 3 });
    }
  });
  if (document.getElementById('special-features-enabled')?.value === '1' && !document.querySelectorAll('#input-fields-list .input-schema-row').length) {
    missing.push({ name: 'special_features', label: 'گام سوم: حداقل یک ویژگی اضافه کنید یا کلید ویژگی‌های خاص را خاموش کنید', step: 3 });
  }
  return missing;
}

/* سازگاری با کدهای قبلی: به‌جای نقطه‌های قرمز، رندر کامل Stepper (کسری/تیک/نوار پیشرفت) */
function refreshStepValidityDots() { renderStepper(); }

function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const wrap = document.getElementById('tags-wrap');
  const v = inp?.value.trim().replace(/^#+/, '').trim();
  if (!inp || !wrap || !v) return;

  const key = v.toLocaleLowerCase();
  const duplicate = Array.from(wrap.querySelectorAll('[data-tag-chip]'))
    .some(chip => (chip.dataset.tagKey || chip.textContent.replace('×', '').trim().toLocaleLowerCase()) === key);
  if (duplicate) {
    inp.value = '';
    return;
  }

  const chip = document.createElement('span');
  chip.className = 'inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]';
  chip.dataset.tagChip = '';
  chip.dataset.tagKey = key;
  chip.appendChild(document.createTextNode(v));
  const remove = document.createElement('button');
  remove.type = 'button';
  remove.className = 'text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1';
  remove.setAttribute('aria-label', 'حذف برچسب');
  remove.textContent = '×';
  remove.addEventListener('click', () => chip.remove());
  chip.appendChild(remove);
  wrap.insertBefore(chip, inp);
  inp.value = '';
}

function updateFileLabel(input, id, isMultiple = false) {
  if(input.files && input.files.length > 0) {
    document.getElementById(id).textContent = isMultiple
      ? `${input.files.length} فایل انتخاب شد`
      : `فایل انتخاب شد: ${input.files[0].name}`;
  }
}

/* ══════════════════ بهینه‌سازی ساده تصاویر محصول ══════════════════
   - فایل مناسب بدون هیچ بازنویسی حفظ می‌شود.
   - فایل بزرگ بدون crop و با حفظ نسبت، حداکثر تا ضلع ۱۶۰۰ کوچک می‌شود.
   - Canvas مرورگر در فضای رنگی sRGB خروجی WebP با کیفیت بصری بالا می‌دهد.
   - بک‌اند همین قواعد را دوباره کنترل می‌کند؛ این مرحله آپلود را سریع‌تر می‌کند. */
const IMAGE_OPT_MAX_EDGE = 1600;
const IMAGE_OPT_MAX_BYTES = 450 * 1024;
let allowUnoptimizedImageSubmit = false;
let clientPreparedImages = false;
const originalImageFiles = new WeakMap();
const optimizedImageFiles = new WeakMap();
const selectedImageIndexes = new WeakMap();
const imageOptimizationApproved = new WeakMap();
const selectedImageProfiles = new WeakMap();

function setImageOptimizeState(group, state, message) {
  group.dataset.optimizeState = state;
  const button = group.querySelector('.image-optimize-btn');
  const icon = button && button.querySelector('i');
  const label = button && button.querySelector('span');
  const status = group.querySelector('.image-optimize-status');
  const loading = group.querySelector('.image-result-loading');
  const resultIcon = group.querySelector('.image-result-icon');
  const reoptimizeButton = group.querySelector('.image-reoptimize-btn');
  const reoptimizeIcon = reoptimizeButton?.querySelector('i');
  if (button) button.disabled = state === 'processing';
  if (icon) icon.className = state === 'processing' ? 'fa-solid fa-spinner fa-spin'
    : state === 'done' ? 'fa-solid fa-circle-check text-[var(--green)]'
    : state === 'failed' ? 'fa-solid fa-rotate-right text-[var(--red)]'
    : 'fa-solid fa-wand-magic-sparkles';
  if (label) label.textContent = state === 'processing' ? 'در حال بهینه‌سازی…'
    : state === 'done' ? 'بررسی مجدد'
    : state === 'failed' ? 'تلاش مجدد'
    : 'بهینه‌سازی اتوماتیک';
  if (status) {
    status.textContent = message || '';
    status.classList.toggle('text-[var(--green)]', state === 'done');
    status.classList.toggle('text-[var(--red)]', state === 'failed');
  }
  if (loading) {
    loading.classList.toggle('hidden', state !== 'processing');
    loading.classList.toggle('flex', state === 'processing');
  }
  if (resultIcon) resultIcon.className = state === 'done'
    ? 'image-result-icon fa-solid fa-circle-check text-[var(--green)]'
    : state === 'failed'
      ? 'image-result-icon fa-solid fa-triangle-exclamation text-[var(--red)]'
      : 'image-result-icon fa-solid fa-hourglass-half text-[var(--text3)]';
  if (reoptimizeButton) {
    reoptimizeButton.disabled = state === 'processing';
    reoptimizeButton.classList.toggle('border-[var(--green)]', state === 'done');
    reoptimizeButton.classList.toggle('text-[var(--green)]', state === 'done');
  }
  if (reoptimizeIcon) reoptimizeIcon.className = state === 'processing'
    ? 'fa-solid fa-spinner fa-spin'
    : state === 'done' ? 'fa-solid fa-circle-check text-[var(--green)]' : 'fa-solid fa-rotate';
}

function imageFileList(files) {
  const transfer = new DataTransfer();
  files.forEach(file => transfer.items.add(file));
  return transfer.files;
}

function loadImageFile(file) {
  return new Promise(function (resolve, reject) {
    const url = URL.createObjectURL(file);
    const image = new Image();
    image.onload = function () { URL.revokeObjectURL(url); resolve(image); };
    image.onerror = function () { URL.revokeObjectURL(url); reject(new Error('تصویر خوانده نشد')); };
    image.src = url;
  });
}

async function optimizeOneImage(file, settings) {
  const image = await loadImageFile(file);
  const maxEdge = settings?.maxEdge || IMAGE_OPT_MAX_EDGE;
  const quality = settings?.quality || 0.9;
  if (!settings && Math.max(image.naturalWidth, image.naturalHeight) <= IMAGE_OPT_MAX_EDGE && file.size <= IMAGE_OPT_MAX_BYTES) return file;
  const scale = Math.min(1, maxEdge / Math.max(image.naturalWidth, image.naturalHeight));
  const width = Math.max(1, Math.round(image.naturalWidth * scale));
  const height = Math.max(1, Math.round(image.naturalHeight * scale));
  let canvas;
  try { canvas = new OffscreenCanvas(width, height); }
  catch (e) { canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height; }
  const context = canvas.getContext('2d', { alpha: true, colorSpace: 'srgb' });
  context.imageSmoothingEnabled = true;
  context.imageSmoothingQuality = 'high';
  context.drawImage(image, 0, 0, width, height);
  const blob = canvas.convertToBlob
    ? await canvas.convertToBlob({ type: 'image/webp', quality: quality })
    : await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', quality));
  if (!blob) throw new Error('مرورگر نتوانست تصویر را پردازش کند');
  if (scale === 1 && blob.size >= file.size) return file;
  return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp', lastModified: Date.now() });
}

function formatImageBytes(bytes) {
  if (!Number.isFinite(bytes)) return '—';
  if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)).toLocaleString('fa-IR') + ' کیلوبایت';
  return (bytes / (1024 * 1024)).toLocaleString('fa-IR', { maximumFractionDigits: 2 }) + ' مگابایت';
}

function imageFormatLabel(file) {
  return ({ 'image/jpeg': 'JPEG', 'image/png': 'PNG', 'image/webp': 'WebP' })[file?.type] || (file?.type?.split('/').pop() || '—').toUpperCase();
}

async function imageFileMeta(file) {
  if (!file) return null;
  const image = await loadImageFile(file);
  return { width: image.naturalWidth, height: image.naturalHeight, size: file.size, format: imageFormatLabel(file) };
}

function metaMarkup(meta) {
  if (!meta) return '<span>هنوز آماده نیست</span>';
  const gcd = function (a, b) { while (b) { const next = a % b; a = b; b = next; } return a || 1; };
  const divisor = gcd(meta.width, meta.height);
  const ratio = (meta.width / divisor) + ':' + (meta.height / divisor);
  return '<span><i class="fa-solid fa-weight-hanging ml-1"></i>' + formatImageBytes(meta.size) + '</span>' +
    '<span><i class="fa-solid fa-expand ml-1"></i>' + meta.width.toLocaleString('fa-IR') + ' × ' + meta.height.toLocaleString('fa-IR') + '</span>' +
    '<span><i class="fa-solid fa-file-code ml-1"></i>' + meta.format + '</span>' +
    '<span><i class="fa-solid fa-crop-simple ml-1"></i>' + ratio + '</span>';
}

function previewUrl(file) {
  return file ? URL.createObjectURL(file) : '';
}

function setImageApproval(group, count, approved) {
  imageOptimizationApproved.set(group, Array.from({ length: count }, function () { return !!approved; }));
}

function setSelectedImageApproval(group, index, approved) {
  const originals = originalImageFiles.get(group) || [];
  const approvals = (imageOptimizationApproved.get(group) || Array(originals.length).fill(false)).slice();
  approvals[index] = !!approved;
  imageOptimizationApproved.set(group, approvals);
}

function setSelectedImageProfile(group, index, profile) {
  const originals = originalImageFiles.get(group) || [];
  const profiles = (selectedImageProfiles.get(group) || Array(originals.length).fill('')).slice();
  profiles[index] = profile;
  selectedImageProfiles.set(group, profiles);
}

function currentImageProfile(group) {
  const index = selectedImageIndexes.get(group) || 0;
  return (selectedImageProfiles.get(group) || [])[index] || '';
}

function markImageVolumeChoice(group, profile, persist = true) {
  const index = selectedImageIndexes.get(group) || 0;
  if (persist) setSelectedImageProfile(group, index, profile);
  group.querySelectorAll('.image-volume-choice').forEach(function (button) {
    const selected = button.dataset.profile === profile;
    button.classList.toggle('border-[var(--green)]', selected);
    button.classList.toggle('bg-[var(--green)]/10', selected);
    button.classList.toggle('border-[var(--b1)]', !selected);
    const check = button.querySelector('.image-choice-check');
    if (check) check.style.display = selected ? 'inline-block' : 'none';
  });
}

async function renderImageComparison(group) {
  const originals = originalImageFiles.get(group) || [];
  const outputs = optimizedImageFiles.get(group) || [];
  const workspace = group.querySelector('.image-compare-workspace');
  const targetPanel = group.querySelector('.image-target-panel');
  if (!workspace) return;
  workspace.classList.toggle('hidden', originals.length === 0);
  if (!originals.length) { targetPanel?.classList.add('hidden'); return; }
  const index = Math.min(selectedImageIndexes.get(group) || 0, originals.length - 1);
  selectedImageIndexes.set(group, index);
  const original = originals[index];
  const optimized = outputs[index] || null;
  const originalImage = group.querySelector('.image-compare-original');
  const optimizedImage = group.querySelector('.image-compare-optimized');
  if (originalImage) originalImage.src = previewUrl(original);
  if (optimizedImage) {
    optimizedImage.src = previewUrl(optimized || original);
    optimizedImage.classList.toggle('opacity-30', !optimized);
  }
  const [originalMeta, optimizedMeta] = await Promise.all([imageFileMeta(original), imageFileMeta(optimized)]);
  const originalSpecs = group.querySelector('.image-original-specs');
  const optimizedSpecs = group.querySelector('.image-optimized-specs');
  if (originalSpecs) originalSpecs.innerHTML = metaMarkup(originalMeta);
  if (optimizedSpecs) optimizedSpecs.innerHTML = metaMarkup(optimizedMeta);
  const modalOriginal = group.querySelector('.image-modal-original');
  const modalOptimized = group.querySelector('.image-modal-optimized');
  if (modalOriginal) modalOriginal.src = originalImage?.src || '';
  if (modalOptimized) modalOptimized.src = optimizedImage?.src || '';
  const thumbs = group.querySelector('.image-compare-thumbs');
  if (thumbs) {
    thumbs.innerHTML = '';
    const approvals = imageOptimizationApproved.get(group) || [];
    const createThumb = function (file, thumbIndex) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'relative w-12 h-12 rounded-lg overflow-hidden shrink-0 border ' + (thumbIndex === index ? 'border-[var(--accent)]' : 'border-[var(--b1)]');
      button.innerHTML = '<img class="w-full h-full object-cover" alt=""><i class="absolute top-1 left-1 w-4 h-4 rounded-full flex items-center justify-center text-[8px] ' + (approvals[thumbIndex] ? 'fa-solid fa-check bg-[var(--green)] text-white' : 'fa-solid fa-xmark bg-[var(--red)] text-white') + '"></i>';
      button.querySelector('img').src = previewUrl(file);
      button.onclick = async function () { selectedImageIndexes.set(group, thumbIndex); await renderImageComparison(group); renderImageTargetOptions(group); };
      if (isMainImageGroup(group)) {
        button.draggable = true;
        button.title = thumbIndex === 0 ? 'کاور فعلی محصول' : 'برای تغییر ترتیب یا کاور، تصویر را بکشید';
        button.addEventListener('dragstart', function (event) {
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('text/plain', String(thumbIndex));
          button.classList.add('opacity-40');
        });
        button.addEventListener('dragover', function (event) {
          event.preventDefault(); event.dataTransfer.dropEffect = 'move';
          button.classList.add('border-[var(--green)]');
        });
        button.addEventListener('dragleave', function () { button.classList.remove('border-[var(--green)]'); });
        button.addEventListener('dragend', function () { button.classList.remove('opacity-40', 'border-[var(--green)]'); });
        button.addEventListener('drop', function (event) {
          event.preventDefault(); event.stopPropagation();
          button.classList.remove('border-[var(--green)]');
          commitImageOrder(group, Number(event.dataTransfer.getData('text/plain')), thumbIndex);
        });
      }
      return button;
    };

    if (isMainImageGroup(group)) {
      thumbs.className = 'image-compare-thumbs flex items-stretch gap-3 p-3 border-t border-[var(--b1)] bg-[var(--s2)] overflow-hidden';
      const coverSection = document.createElement('div');
      coverSection.className = 'shrink-0 flex flex-col gap-2 pl-3 border-l border-[var(--b1)]';
      coverSection.innerHTML = '<span class="text-[9px] font-semibold text-[var(--green)]"><i class="fa-solid fa-star ml-1"></i>عکس کاور</span>';
      coverSection.appendChild(createThumb(originals[0], 0));

      const gallerySection = document.createElement('div');
      gallerySection.className = 'min-w-0 flex-1 flex flex-col gap-2';
      gallerySection.innerHTML = '<span class="text-[9px] font-semibold text-[var(--text3)]"><i class="fa-solid fa-images ml-1"></i>عکس‌های دیگر محصول <span class="font-normal">— برای جابه‌جایی بکشید</span></span>';
      const galleryStrip = document.createElement('div');
      galleryStrip.className = 'flex gap-2 overflow-x-auto pb-1 min-h-12';
      originals.slice(1).forEach(function (file, galleryIndex) { galleryStrip.appendChild(createThumb(file, galleryIndex + 1)); });
      if (originals.length === 1) galleryStrip.innerHTML = '<span class="text-[9px] text-[var(--text3)] self-center">عکس دیگری اضافه نشده است.</span>';
      gallerySection.appendChild(galleryStrip);
      thumbs.appendChild(coverSection);
      thumbs.appendChild(gallerySection);
    } else {
      thumbs.className = 'image-compare-thumbs flex gap-2 overflow-x-auto p-3 border-t border-[var(--b1)]';
      originals.forEach(function (file, thumbIndex) { thumbs.appendChild(createThumb(file, thumbIndex)); });
    }
  }
  targetPanel?.classList.toggle('hidden', !optimized);
}

async function optimizeOneImageToBytes(file, targetBytes) {
  const image = await loadImageFile(file);
  let scale = Math.min(1, 2000 / Math.max(image.naturalWidth, image.naturalHeight));
  let bestBlob = null;
  for (let resizePass = 0; resizePass < 3; resizePass++) {
    const width = Math.max(1, Math.round(image.naturalWidth * scale));
    const height = Math.max(1, Math.round(image.naturalHeight * scale));
    const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height;
    const context = canvas.getContext('2d', { alpha: true, colorSpace: 'srgb' });
    context.imageSmoothingEnabled = true; context.imageSmoothingQuality = 'high'; context.drawImage(image, 0, 0, width, height);
    let low = 0.42, high = 0.98;
    for (let iteration = 0; iteration < 7; iteration++) {
      const quality = (low + high) / 2;
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', quality));
      if (!blob) throw new Error('پردازش تصویر ناموفق بود');
      if (!bestBlob || Math.abs(blob.size - targetBytes) < Math.abs(bestBlob.size - targetBytes)) bestBlob = blob;
      if (blob.size > targetBytes) high = quality; else low = quality;
    }
    if (bestBlob && bestBlob.size <= targetBytes * 1.12) break;
    scale *= 0.82;
  }
  return new File([bestBlob], file.name.replace(/\.[^.]+$/, '') + '.webp', { type: 'image/webp', lastModified: Date.now() });
}

function renderImageTargetOptions(group) {
  const outputs = optimizedImageFiles.get(group) || [];
  const index = selectedImageIndexes.get(group) || 0;
  const reference = outputs[index];
  const panel = group.querySelector('.image-target-panel');
  const options = group.querySelector('.image-target-options');
  if (!reference || !panel || !options) { panel?.classList.add('hidden'); return; }
  panel.classList.remove('hidden');
  const autoSize = group.querySelector('.image-auto-size');
  if (autoSize) autoSize.textContent = formatImageBytes(reference.size);
  const original = (originalImageFiles.get(group) || [])[index];
  group.querySelectorAll('.image-original-size').forEach(function (el) { el.textContent = formatImageBytes(original?.size); });
  const range = group.querySelector('.image-size-range');
  if (range) {
    const selectedKb = Math.max(20, Math.round(reference.size / 1024));
    const span = Math.max(10, Math.min(selectedKb - 10, Math.max(60, Math.round(selectedKb * 0.75))));
    range.min = String(Math.max(10, selectedKb - span));
    range.max = String(selectedKb + span);
    range.value = String(selectedKb);
    previewImageRange(range);
  }
  const levels = [
    [1.75, 'جزئیات بیشتر', 'fa-gem'], [1.50, 'خیلی باکیفیت', 'fa-star'], [1.25, 'کمی سنگین‌تر', 'fa-arrow-trend-up'],
    [0.80, 'کمی سبک‌تر', 'fa-feather'], [0.65, 'سبک', 'fa-compress'], [0.50, 'خیلی سبک', 'fa-bolt'],
  ];
  options.innerHTML = levels.map(function(level) {
    return '<button type="button" data-profile="relative-' + level[0] + '" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] hover:border-[var(--accent)] rounded-xl p-2.5 text-right transition-colors" onclick="applyImageTargetLevel(this,' + level[0] + ')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i>' +
      '<span class="flex items-center gap-1.5 text-[10px] text-[var(--text2)]"><i class="fa-solid ' + level[2] + ' text-[var(--accent)]"></i>' + level[1] + '</span>' +
      '<strong class="block text-[11px] text-[var(--text)] mt-1">حدود ' + formatImageBytes(reference.size * level[0]) + '</strong></button>';
  }).join('');
  markImageVolumeChoice(group, currentImageProfile(group), false);
}

async function applyImageTargetLevel(button, factor) {
  const group = button.closest('.image-optimizer-group');
  const originals = originalImageFiles.get(group) || [];
  const current = optimizedImageFiles.get(group) || [];
  if (!originals.length || !current.length) return;
  setImageOptimizeState(group, 'processing', 'در حال ساخت حجم انتخابی…');
  group.querySelectorAll('.image-target-options button').forEach(function(item){ item.disabled = true; });
  try {
    const index = selectedImageIndexes.get(group) || 0;
    const output = current.slice();
    output[index] = await optimizeOneImageToBytes(originals[index], Math.max(24 * 1024, current[index].size * factor));
    optimizedImageFiles.set(group, output);
    setSelectedImageApproval(group, index, true);
    document.getElementById(group.dataset.input).files = imageFileList(output);
    renderImageGroupPreviews(group, output);
    await renderImageComparison(group); renderImageTargetOptions(group);
    markImageVolumeChoice(group, 'relative-' + factor);
    clientPreparedImages = true;
    setImageOptimizeState(group, 'done', 'حجم انتخابی آماده ثبت است.');
  } catch (error) { setImageOptimizeState(group, 'failed', 'ساخت حجم انتخابی انجام نشد؛ دوباره تلاش کنید.'); }
  finally { group.querySelectorAll('.image-target-options button').forEach(function(item){ item.disabled = false; }); }
}

async function applySelectedImageToAbsoluteTarget(group, targetBytes, profile) {
  const originals = originalImageFiles.get(group) || [];
  if (!originals.length) return;
  const index = selectedImageIndexes.get(group) || 0;
  const current = optimizedImageFiles.get(group) || originals.slice();
  setImageOptimizeState(group, 'processing', 'در حال ساخت حجم انتخابی برای عکس انتخاب‌شده…');
  try {
    const output = current.slice();
    output[index] = await optimizeOneImageToBytes(originals[index], targetBytes);
    optimizedImageFiles.set(group, output);
    setSelectedImageApproval(group, index, true);
    document.getElementById(group.dataset.input).files = imageFileList(output);
    renderImageGroupPreviews(group, output);
    await renderImageComparison(group); renderImageTargetOptions(group); markImageVolumeChoice(group, profile);
    clientPreparedImages = true;
    setImageOptimizeState(group, 'done', 'حجم انتخابی برای همین عکس آماده ثبت است.');
  } catch (error) { setImageOptimizeState(group, 'failed', 'پردازش حجم انتخابی انجام نشد؛ دوباره تلاش کنید.'); }
}

async function applyImageQuickPreset(button, profile) {
  const group = button.closest('.image-optimizer-group');
  const originals = originalImageFiles.get(group) || [];
  if (!originals.length) return;
  if (profile === 'original') {
    const index = selectedImageIndexes.get(group) || 0;
    const output = (optimizedImageFiles.get(group) || originals.slice()).slice();
    output[index] = originals[index];
    optimizedImageFiles.set(group, output);
    setSelectedImageApproval(group, index, true);
    document.getElementById(group.dataset.input).files = imageFileList(output);
    renderImageGroupPreviews(group, output);
    await renderImageComparison(group); renderImageTargetOptions(group); markImageVolumeChoice(group, profile);
    clientPreparedImages = true;
    setImageOptimizeState(group, 'done', 'نسخه اورجینال برای همین عکس انتخاب شد.');
    return;
  }
  const targets = { 'site-standard': 300 * 1024, 'site-light': 180 * 1024 };
  await applySelectedImageToAbsoluteTarget(group, targets[profile], profile);
}

function previewImageRange(range) {
  const group = range.closest('.image-optimizer-group');
  const label = group?.querySelector('.image-range-value');
  if (label) label.textContent = 'حدود ' + Number(range.value).toLocaleString('fa-IR') + ' کیلوبایت';
}

async function applyImageRange(range) {
  const group = range.closest('.image-optimizer-group');
  await applySelectedImageToAbsoluteTarget(group, Number(range.value) * 1024, 'range');
  markImageVolumeChoice(group, 'range');
}

function openImageCompareModal(button) {
  const group = button.closest('.image-optimizer-group');
  group?.querySelector('.image-compare-modal')?.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImageCompareModal(modal) {
  modal?.classList.add('hidden'); document.body.style.overflow = '';
}

async function existingImageFiles(group) {
  const urls = JSON.parse(group.dataset.existing || '[]');
  const files = [];
  for (let i = 0; i < urls.length; i++) {
    const response = await fetch(urls[i], { credentials: 'same-origin' });
    if (!response.ok) throw new Error('دریافت یکی از تصاویر فعلی ممکن نبود');
    const blob = await response.blob();
    const pathname = new URL(urls[i], location.href).pathname;
    files.push(new File([blob], decodeURIComponent(pathname.split('/').pop() || ('image-' + i)), { type: blob.type || 'image/jpeg' }));
  }
  return files;
}

function isMainImageGroup(group) {
  return group?.dataset.input === 'main-images-file';
}

function moveImageItem(items, fromIndex, toIndex) {
  const output = items.slice();
  if (fromIndex === toIndex || fromIndex < 0 || toIndex < 0 || fromIndex >= output.length || toIndex >= output.length) return output;
  const item = output.splice(fromIndex, 1)[0];
  output.splice(toIndex, 0, item);
  return output;
}

function movedSelectedImageIndex(selected, fromIndex, toIndex) {
  if (selected === fromIndex) return toIndex;
  if (fromIndex < toIndex && selected > fromIndex && selected <= toIndex) return selected - 1;
  if (fromIndex > toIndex && selected >= toIndex && selected < fromIndex) return selected + 1;
  return selected;
}

async function commitImageOrder(group, fromIndex, toIndex) {
  const input = document.getElementById(group.dataset.input);
  let submitted = Array.from(input?.files || []);
  const originals = (originalImageFiles.get(group) || []).slice();
  const optimized = (optimizedImageFiles.get(group) || []).slice();
  if (!submitted.length) submitted = (optimized.length ? optimized : originals).slice();
  if (!submitted.length) submitted = await existingImageFiles(group);
  if (!submitted.length || fromIndex === toIndex) return;

  const approvals = (imageOptimizationApproved.get(group) || Array(submitted.length).fill(true)).slice();
  const profiles = (selectedImageProfiles.get(group) || Array(submitted.length).fill('')).slice();
  const selected = selectedImageIndexes.get(group) || 0;
  const reorderedSubmitted = moveImageItem(submitted, fromIndex, toIndex);
  input.files = imageFileList(reorderedSubmitted);
  group.dataset.existing = '[]';
  originalImageFiles.set(group, moveImageItem(originals.length ? originals : submitted, fromIndex, toIndex));
  optimizedImageFiles.set(group, optimized.length ? moveImageItem(optimized, fromIndex, toIndex) : []);
  imageOptimizationApproved.set(group, moveImageItem(approvals, fromIndex, toIndex));
  selectedImageProfiles.set(group, moveImageItem(profiles, fromIndex, toIndex));
  selectedImageIndexes.set(group, movedSelectedImageIndex(selected, fromIndex, toIndex));
  renderImageGroupPreviews(group, reorderedSubmitted);
  await renderImageComparison(group);
  renderImageTargetOptions(group);
  document.dispatchEvent(new CustomEvent('product-images-changed'));
}

function renderImageGroupPreviews(group, files) {
  const strip = group.querySelector('.image-preview-strip');
  const label = group.querySelector('.image-file-label');
  if (label) label.textContent = files.length ? toFa(files.length) + ' تصویر انتخاب شد' : 'انتخاب تصاویر';
  if (!strip) return;
  strip.innerHTML = '';
  files.slice(0, 12).forEach(function (file, index) {
    const holder = document.createElement('span');
    holder.className = 'relative inline-flex';
    const image = document.createElement('img');
    image.className = 'w-14 h-14 rounded-lg object-cover border border-[var(--b2)]';
    image.src = URL.createObjectURL(file);
    image.onload = function () { URL.revokeObjectURL(image.src); };
    image.onclick = function (event) {
      event.stopPropagation();
      selectedImageIndexes.set(group, index);
      renderImageComparison(group); renderImageTargetOptions(group);
    };
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'absolute -top-1.5 -left-1.5 w-4 h-4 rounded-full bg-[var(--red)] text-white text-[8px] flex items-center justify-center border border-[var(--s2)]';
    remove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    remove.title = 'حذف این عکس';
    remove.onclick = function (event) { event.stopPropagation(); removeSelectedImage(group, index); };
    holder.appendChild(image);
    holder.appendChild(remove);
    strip.appendChild(holder);
  });
}

async function removeSelectedImage(group, index) {
  const input = document.getElementById(group.dataset.input);
  let current = Array.from(input?.files || []);
  if (!current.length) current = (optimizedImageFiles.get(group) || originalImageFiles.get(group) || []).slice();
  if (!current.length) current = await existingImageFiles(group);
  current.splice(index, 1);
  if (input) input.files = imageFileList(current);
  group.dataset.existing = '[]';
  const originals = (originalImageFiles.get(group) || []).slice();
  const optimized = (optimizedImageFiles.get(group) || []).slice();
  originals.splice(index, 1); optimized.splice(index, 1);
  originalImageFiles.set(group, originals.length ? originals : current.slice());
  optimizedImageFiles.set(group, optimized);
  setImageApproval(group, current.length, current.length > 0);
  selectedImageIndexes.set(group, Math.max(0, Math.min(index, current.length - 1)));
  renderImageGroupPreviews(group, current);
  renderImageComparison(group);
  if (!current.length) setImageOptimizeState(group, 'idle', 'همه تصاویر حذف شدند.');
  document.dispatchEvent(new CustomEvent('product-images-changed'));
}

async function sharpenSelectedImage(button) {
  const group = button.closest('.image-optimizer-group');
  const input = document.getElementById(group.dataset.input);
  let files = Array.from(input?.files || []);
  if (!files.length) files = await existingImageFiles(group);
  if (!files.length) return setImageOptimizeState(group, 'idle', 'تصویری برای شارپ‌کردن وجود ندارد.');
  const index = Math.min(selectedImageIndexes.get(group) || 0, files.length - 1);
  setImageOptimizeState(group, 'processing', 'در حال شارپ‌کردن عکس انتخاب‌شده…');
  try {
    const source = files[index];
    const image = await loadImageFile(source);
    const scale = Math.min(1, 1800 / Math.max(image.naturalWidth, image.naturalHeight));
    const width = Math.max(1, Math.round(image.naturalWidth * scale));
    const height = Math.max(1, Math.round(image.naturalHeight * scale));
    const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height;
    const context = canvas.getContext('2d', { alpha: true, colorSpace: 'srgb' });
    context.imageSmoothingEnabled = true; context.imageSmoothingQuality = 'high';
    context.filter = 'contrast(1.12) saturate(1.04)';
    context.drawImage(image, 0, 0, width, height);
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', .93));
    if (!blob) throw new Error('sharpen failed');
    files[index] = new File([blob], source.name.replace(/\.[^.]+$/, '') + '-sharp.webp', { type: 'image/webp', lastModified: Date.now() });
    input.files = imageFileList(files);
    originalImageFiles.set(group, files.slice());
    optimizedImageFiles.set(group, files.slice());
    setImageApproval(group, files.length, true);
    renderImageGroupPreviews(group, files);
    await renderImageComparison(group);
    clientPreparedImages = true;
    setImageOptimizeState(group, 'done', 'عکس انتخاب‌شده شارپ و آماده ثبت شد.');
    document.dispatchEvent(new CustomEvent('product-images-changed'));
  } catch (error) {
    setImageOptimizeState(group, 'failed', 'شارپ‌کردن عکس انجام نشد؛ دوباره تلاش کنید.');
  }
}

async function optimizeImageGroup(buttonOrGroup) {
  const group = buttonOrGroup.closest ? buttonOrGroup.closest('.image-optimizer-group') : buttonOrGroup;
  const input = document.getElementById(group.dataset.input);
  setImageOptimizeState(group, 'processing', 'لطفاً تا پایان پردازش صبر کنید.');
  allowUnoptimizedImageSubmit = false;
  try {
    let files = Array.from(input.files || []);
    if (!files.length) files = await existingImageFiles(group);
    if (!files.length) {
      setImageOptimizeState(group, 'idle', 'تصویری برای بهینه‌سازی وجود ندارد.');
      return;
    }
    if (!originalImageFiles.has(group)) originalImageFiles.set(group, files.slice());
    const optimized = [];
    const sources = originalImageFiles.get(group) || files;
    for (const file of sources) optimized.push(await optimizeOneImage(file));
    optimizedImageFiles.set(group, optimized);
    setImageApproval(group, sources.length, true);
    selectedImageProfiles.set(group, Array(sources.length).fill(''));
    input.files = imageFileList(optimized);
    renderImageGroupPreviews(group, optimized);
    await renderImageComparison(group);
    renderImageTargetOptions(group);
    markImageVolumeChoice(group, '');
    clientPreparedImages = true;
    setImageOptimizeState(group, 'done', 'تصاویر آماده ثبت هستند.');
  } catch (error) {
    setImageOptimizeState(group, 'failed', 'بهینه‌سازی انجام نشد؛ دوباره تلاش کنید.');
  }
}

/* اگر مدیر Slug را دستی ویرایش کرد، دیگر با تغییر نام انگلیسی به‌صورت خودکار جایگزین نشود */
let slugManuallyEdited = false;
function lockSlugManual() { slugManuallyEdited = true; }
function autoSlug(el) {
  if (slugManuallyEdited) return;
  const s = el.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  document.getElementById('slug-input').value = s;
}

/* ── کامپوننت مستقل و قابل استفاده مجدد: Searchable Select ──
   یک <select> واقعی (با همان name فعلی) پنهان و در پس‌زمینه نگه‌داشته می‌شود تا هیچ رفتار
   Backend/Submit تغییر نکند؛ یک UI جستجوپذیر روی آن سوار می‌شود و مقدار select اصلی را همگام نگه می‌دارد. */
function makeSearchable(select) {
  if (!select) return;
  if (select.dataset.searchableReady === '1') { refreshSearchable(select); return; }
  select.dataset.searchableReady = '1';

  const wrap = document.createElement('div');
  wrap.className = 'relative searchable-select-wrap';
  select.parentNode.insertBefore(wrap, select);
  select.classList.add('hidden');
  wrap.appendChild(select);

  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'ss-btn bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full flex items-center justify-between gap-2 focus:border-[var(--accent)] outline-none transition-colors';
  wrap.appendChild(btn);

  const panel = document.createElement('div');
  panel.className = 'ss-panel hidden absolute z-30 mt-1 w-full bg-[var(--s2)] border border-[var(--b1)] rounded-lg shadow-xl overflow-hidden';
  panel.innerHTML = '<div class="p-1.5 border-b border-[var(--b1)]"><input type="text" placeholder="جستجو..." class="ss-search w-full bg-[var(--s1)] border border-[var(--b1)] rounded-md p-1.5 text-[11px] text-[var(--text)] outline-none focus:border-[var(--accent)]"></div><div class="ss-list overflow-y-auto max-h-44"></div>';
  wrap.appendChild(panel);

  function renderList(filter) {
    const list = panel.querySelector('.ss-list');
    list.innerHTML = '';
    Array.from(select.options).forEach((opt, optionIndex) => {
      if (!opt.value && !opt.textContent.trim()) return;
      if (opt.hidden || opt.disabled) return;
      if (filter && opt.textContent.toLowerCase().indexOf(filter.toLowerCase()) === -1) return;
      const item = document.createElement('div');
      item.className = 'px-3 py-2 text-xs cursor-pointer transition-colors ' + (opt.value === select.value ? 'bg-[var(--accent)]/15 text-[var(--accent)]' : 'text-[var(--text2)] hover:bg-[var(--accent)]/10 hover:text-[var(--text)]');
      item.textContent = opt.textContent;
      item.onclick = () => {
        // استفاده از value برای مدل‌های دارای شناسه یکسان بین دو provider
        // همیشه اولین option را انتخاب می‌کرد. اندیس دقیق، همان گزینه کلیک‌شده را حفظ می‌کند.
        select.selectedIndex = optionIndex;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateButtonLabel();
        panel.classList.add('hidden');
      };
      list.appendChild(item);
    });
    if (!list.children.length) list.innerHTML = '<div class="px-3 py-3 text-[11px] text-[var(--text3)] text-center">موردی یافت نشد</div>';
  }

  function updateButtonLabel() {
    const opt = select.options[select.selectedIndex];
    btn.innerHTML = '<span class="truncate">' + (opt && opt.value ? opt.textContent : (select.disabled ? 'ابتدا دسته را انتخاب کنید' : 'انتخاب کنید')) + '</span><i class="fa-solid fa-chevron-down text-[10px] text-[var(--text3)]"></i>';
    btn.disabled = select.disabled;
    btn.classList.toggle('opacity-40', select.disabled);
    btn.classList.toggle('cursor-not-allowed', select.disabled);
  }

  btn.addEventListener('click', () => {
    if (select.disabled) return;
    const willOpen = panel.classList.contains('hidden');
    document.querySelectorAll('.ss-panel').forEach(p => p.classList.add('hidden'));
    if (willOpen) { renderList(''); panel.classList.remove('hidden'); panel.querySelector('.ss-search').focus(); }
  });
  panel.querySelector('.ss-search').addEventListener('input', e => renderList(e.target.value));
  document.addEventListener('click', e => { if (!wrap.contains(e.target)) panel.classList.add('hidden'); });

  updateButtonLabel();
}

function refreshSearchable(select) {
  if (!select) return;
  const wrap = select.closest('.searchable-select-wrap');
  if (!wrap) return;
  const btn = wrap.querySelector('.ss-btn');
  const opt = select.options[select.selectedIndex];
  btn.innerHTML = '<span class="truncate">' + (opt && opt.value ? opt.textContent : (select.disabled ? 'ابتدا دسته را انتخاب کنید' : 'انتخاب کنید')) + '</span><i class="fa-solid fa-chevron-down text-[10px] text-[var(--text3)]"></i>';
  btn.disabled = select.disabled;
  btn.classList.toggle('opacity-40', select.disabled);
  btn.classList.toggle('cursor-not-allowed', select.disabled);
}

function initSearchables(root) {
  (root || document).querySelectorAll('select[data-searchable]').forEach(makeSearchable);
}

function toggleCreditCost(sel) {
  const w = document.getElementById('credit-cost-wrap');
  if(sel.value === 'per_credit') {
    w.classList.remove('opacity-30', 'pointer-events-none');
  } else {
    w.classList.add('opacity-30', 'pointer-events-none');
  }
}

/* ── افزودن فیلد FALLBACK به صورت داینامیک — از مدل‌های واقعی پنل ادمین ── */
/* fbIdx با تعداد ردیف‌های از قبل پرشده (در حالت تکثیر محصول) شروع می‌شود تا برچسب اولویت درست بماند */
let fbIdx = CFG.fbIdxStart || 0;
function addFallback() {
  if (!AI_MODELS.length) {
    alert('ابتدا حداقل یک مدل هوش مصنوعی فعال در سیستم ثبت کنید.');
    return;
  }
  // قانون UX سند: بدون انتخاب مدل اصلی، افزودن Fallback مجاز نیست
  const primary = document.getElementById('primary-model-select');
  if (primary && !primary.value) {
    alert('ابتدا مدل اصلی را انتخاب کنید، سپس مدل جایگزین اضافه کنید.');
    return;
  }

  fbIdx++;
  const div = document.createElement('div');
  div.className = 'fallback-row bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 flex items-center gap-3';
  div.id = `fb-row-${fbIdx}`;
  div.draggable = true;

  const options = AI_MODELS.map(m =>
    `<option value="${m.id}" data-api-provider="${m.apiProvider || 'openrouter'}">${m.name} (${m.provider})</option>`
  ).join('');

  div.innerHTML = `
    <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 fb-drag-handle hidden md:block" title="برای تغییر اولویت بکشید"></i>
    <div class="flex md:hidden flex-col gap-0.5 shrink-0">
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
      <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveFallbackRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
    </div>
    <span class="fb-priority text-[10px] font-mono text-[var(--text3)] w-14 shrink-0">اولویت ${fbIdx + 1}</span>
    <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] flex-1 fallback-select-item" data-searchable>
      ${options}
    </select>
    <label class="relative w-8 h-[18px] shrink-0 block cursor-pointer" title="Enable/Disable — فقط UI، برنامه‌نویسی شود">
      <input type="checkbox" class="sr-only peer" checked>
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
    </label>
    <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg shrink-0" onclick="document.getElementById('fb-row-${fbIdx}').remove(); renumberFallbacks();">حذف</button>
  `;
  document.getElementById('fallback-list').appendChild(div);
  makeSearchable(div.querySelector('select'));
  wireFallbackDrag(div);
}

/* شماره اولویت‌ها را بعد از حذف/جابجایی یک ردیف، دوباره محاسبه می‌کند (فقط نمایشی) */
function renumberFallbacks() {
  document.querySelectorAll('#fallback-list .fallback-row, #fallback-list > div').forEach((row, idx) => {
    const p = row.querySelector('.fb-priority');
    if (p) p.textContent = 'اولویت ' + (idx + 2);
  });
}

/* Drag & Drop برای تغییر ترتیب مدل‌های جایگزین — ترتیب DOM دقیقاً همان ترتیبی است که submitForm() به‌عنوان اولویت ارسال می‌کند */
/* Sort Mode موبایل — چون Drag & Drop نیتیو HTML5 روی لمسی کار نمی‌کند، دکمه بالا/پایین جایگزین آن می‌شود */
function moveFallbackRow(btn, dir) {
  const row = btn.closest('.fallback-row');
  const sib = dir === 'up' ? row.previousElementSibling : row.nextElementSibling;
  if (!sib) return;
  if (dir === 'up') row.parentNode.insertBefore(row, sib); else row.parentNode.insertBefore(sib, row);
  renumberFallbacks();
}
function moveInputFieldRow(btn, dir) {
  const row = btn.closest('.input-field-card');
  const sib = dir === 'up' ? row.previousElementSibling : row.nextElementSibling;
  if (!sib) return;
  if (dir === 'up') row.parentNode.insertBefore(row, sib); else row.parentNode.insertBefore(sib, row);
  refreshFormPreview();
}

let dragFbEl = null;
function wireFallbackDrag(row) {
  row.addEventListener('dragstart', () => { dragFbEl = row; row.classList.add('opacity-40'); });
  row.addEventListener('dragend', () => { row.classList.remove('opacity-40'); renumberFallbacks(); });
  row.addEventListener('dragover', e => e.preventDefault());
  row.addEventListener('drop', e => {
    e.preventDefault();
    if (!dragFbEl || dragFbEl === row) return;
    const list = document.getElementById('fallback-list');
    const rows = Array.from(list.children);
    if (rows.indexOf(dragFbEl) < rows.indexOf(row)) list.insertBefore(dragFbEl, row.nextSibling);
    else list.insertBefore(dragFbEl, row);
    renumberFallbacks();
  });
}

function insertVar(v) {
  const ta = document.getElementById('prompt-template');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.substring(0, s) + v + ta.value.substring(e);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
}

/* fieldIdx با تعداد ردیف‌های از قبل پرشده (در حالت تکثیر محصول) شروع می‌شود */
const INPUT_FIELD_TYPES = ['text','textarea','number','image_upload','file_upload','select','radio','checkbox'];
let fieldIdx = CFG.fieldIdxStart || 0;
function addInputField() {
  fieldIdx++;
  const div = document.createElement('div');
  div.className = 'input-field-card bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3 input-schema-row';
  div.id = `field-row-${fieldIdx}`;
  div.draggable = true;

  const typeOptions = INPUT_FIELD_TYPES.map(t => `<option value="${t}">${t}</option>`).join('');

  div.innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-5 gap-2.5 items-center">
      <div class="flex items-center gap-1.5 md:col-span-1">
        <i class="fa-solid fa-grip-vertical text-[var(--text3)] cursor-grab shrink-0 hidden md:block" title="برای تغییر ترتیب بکشید"></i>
        <div class="flex md:hidden flex-col gap-0.5 shrink-0">
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'up')" aria-label="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>
          <button type="button" class="w-5 h-4 flex items-center justify-center text-[var(--text3)] bg-[var(--text)]/5 rounded" onclick="moveInputFieldRow(this,'down')" aria-label="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>
        </div>
        <input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] ltr text-left schema-id w-full" placeholder="field_id">
      </div>
      <input type="text" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-label" placeholder="برچسب فارسی">
      <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-type" data-searchable>
        ${typeOptions}
      </select>
      <select class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2 text-xs text-[var(--text)] schema-required">
        <option value="1">اجباری</option>
        <option value="0">اختیاری</option>
      </select>
      <div class="flex items-center gap-1.5 justify-end">
        <button type="button" class="text-xs text-[var(--text2)] bg-[var(--text)]/5 px-2.5 py-1.5 rounded-lg" onclick="this.closest('.input-field-card').querySelector('.field-advanced').classList.toggle('hidden')" title="ویرایش تنظیمات پیشرفته"><i class="fa-solid fa-pen"></i></button>
        <button type="button" class="text-xs text-[var(--red)] bg-[var(--red)]/10 px-2.5 py-1.5 rounded-lg" onclick="document.getElementById('field-row-${fieldIdx}').remove(); refreshFormPreview();">حذف</button>
      </div>
    </div>
    <div class="field-advanced hidden grid grid-cols-1 md:grid-cols-3 gap-2.5 mt-2.5 pt-2.5 border-t border-dashed border-[var(--b2)]">
      <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-placeholder" placeholder="Placeholder — برنامه‌نویسی شود">
      <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] schema-help" placeholder="Help Text — برنامه‌نویسی شود">
      <div class="flex items-center gap-1.5">
        <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-min" placeholder="حداقل">
        <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 schema-max" placeholder="حداکثر">
        <input type="text" class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text)] w-1/3 ltr text-left schema-regex" placeholder="Regex">
      </div>
    </div>
  `;
  document.getElementById('input-fields-list').appendChild(div);
  makeSearchable(div.querySelector('.schema-type'));
  wireInputFieldDrag(div);
  div.querySelectorAll('.schema-id, .schema-label, .schema-required').forEach(el => el.addEventListener('input', refreshFormPreview));
  div.querySelector('.schema-required').addEventListener('change', refreshFormPreview);
  refreshFormPreview();
}

let dragFieldEl = null;
function wireInputFieldDrag(row) {
  row.addEventListener('dragstart', () => { dragFieldEl = row; row.classList.add('opacity-40'); });
  row.addEventListener('dragend', () => { row.classList.remove('opacity-40'); refreshFormPreview(); });
  row.addEventListener('dragover', e => e.preventDefault());
  row.addEventListener('drop', e => {
    e.preventDefault();
    if (!dragFieldEl || dragFieldEl === row) return;
    const list = document.getElementById('input-fields-list');
    const rows = Array.from(list.children);
    if (rows.indexOf(dragFieldEl) < rows.indexOf(row)) list.insertBefore(dragFieldEl, row.nextSibling);
    else list.insertBefore(dragFieldEl, row);
    refreshFormPreview();
  });
}

/* Preview Form — رندر زنده فرم نهایی کاربر بر اساس فیلدهای تعریف‌شده (فقط UI) */
function refreshFormPreview() {
  const box = document.getElementById('user-form-preview');
  if (!box) return;
  const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
  if (!rows.length) { box.innerHTML = '<div class="text-[11px] text-[var(--text3)] text-center py-4">هنوز فیلد ورودی‌ای تعریف نشده است</div>'; return; }
  let html = '';
  rows.forEach(row => {
    const label = row.querySelector('.schema-label')?.value || row.querySelector('.schema-id')?.value || 'بدون عنوان';
    const required = row.querySelector('.schema-required')?.value === '1';
    html += `<div class="flex flex-col gap-1 mb-2.5"><label class="text-[11px] text-[var(--text2)]">${label}${required ? ' <span class=\"text-[var(--red)]\">*</span>' : ''}</label><div class="bg-[var(--bg)] border border-[var(--b1)] rounded-lg p-2 text-[11px] text-[var(--text3)]">— ورودی کاربر —</div></div>`;
  });
  box.innerHTML = html;
}

function createHiddenInput(name, value) {
  const input = document.createElement('input');
  input.type = 'hidden'; input.name = name; input.value = value;
  input.dataset.submitGenerated = '1';
  return input;
}

const PRODUCT_OFFLINE_DB = 'vatan-admin-offline';
const PRODUCT_OFFLINE_STORE = 'product-submissions';

function openProductOfflineDb() {
  return new Promise(function (resolve, reject) {
    const request = indexedDB.open(PRODUCT_OFFLINE_DB, 1);
    request.onupgradeneeded = function () {
      const db = request.result;
      if (!db.objectStoreNames.contains(PRODUCT_OFFLINE_STORE)) {
        db.createObjectStore(PRODUCT_OFFLINE_STORE, { keyPath: 'id', autoIncrement: true });
      }
    };
    request.onsuccess = function () { resolve(request.result); };
    request.onerror = function () { reject(request.error); };
  });
}

function offlineStoreTransaction(mode, action) {
  return openProductOfflineDb().then(function (db) {
    return new Promise(function (resolve, reject) {
      const tx = db.transaction(PRODUCT_OFFLINE_STORE, mode);
      const store = tx.objectStore(PRODUCT_OFFLINE_STORE);
      const request = action(store);
      request.onsuccess = function () { resolve(request.result); };
      request.onerror = function () { reject(request.error); };
      tx.oncomplete = function () { db.close(); };
    });
  });
}

function showOfflineStatus(message, state) {
  let toast = document.getElementById('product-offline-status');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'product-offline-status';
    toast.className = 'fixed left-5 bottom-20 z-[160] max-w-sm rounded-xl border p-3 text-xs font-bold shadow-xl flex items-center gap-2';
    document.body.appendChild(toast);
  }
  const icon = state === 'synced' ? 'fa-circle-check' : state === 'syncing' ? 'fa-rotate fa-spin' : 'fa-cloud-arrow-up';
  toast.className = 'fixed left-5 bottom-20 z-[160] max-w-sm rounded-xl border p-3 text-xs font-bold shadow-xl flex items-center gap-2 ' +
    (state === 'synced' ? 'bg-[var(--green)]/15 border-[var(--green)]/30 text-[var(--green)]' : 'bg-[var(--s2)] border-[var(--b2)] text-[var(--text2)]');
  toast.innerHTML = '<i class="fa-solid ' + icon + '"></i><span></span>';
  toast.querySelector('span').textContent = message;
}

async function queueProductSubmission(form, formData) {
  const entries = [];
  formData.forEach(function (value, key) { entries.push([key, value]); });
  await offlineStoreTransaction('readwrite', function (store) {
    return store.add({ url: form.action, method: 'POST', entries: entries, createdAt: Date.now() });
  });
  showOfflineStatus('این محصول روی همین دستگاه ذخیره شد و بعد از اتصال اینترنت خودکار ثبت می‌شود.', 'queued');
}

async function syncOfflineProductSubmissions() {
  if (!navigator.onLine) return;
  const rows = await offlineStoreTransaction('readonly', function (store) { return store.getAll(); }).catch(function () { return []; });
  if (!rows.length) return;
  showOfflineStatus('در حال همگام‌سازی ' + toFa(rows.length) + ' محصول ذخیره‌شده…', 'syncing');
  let synced = 0;
  for (const row of rows) {
    const body = new FormData();
    row.entries.forEach(function (entry) { body.append(entry[0], entry[1]); });
    try {
      const response = await fetch(row.url, {
        method: row.method || 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: body,
        credentials: 'same-origin',
      });
      if (!response.ok) {
        const payload = await response.json().catch(function () { return null; });
        showOfflineStatus((payload && payload.message) || 'همگام‌سازی نیاز به بررسی اطلاعات محصول دارد.', 'queued');
        continue;
      }
      await offlineStoreTransaction('readwrite', function (store) { return store.delete(row.id); });
      synced++;
    } catch (error) { break; }
  }
  if (synced) showOfflineStatus(toFa(synced) + ' محصول آفلاین با موفقیت در سایت ثبت شد.', 'synced');
}

async function submitForm(statusValue) {
  const form = document.getElementById('real-product-form');
  form.querySelectorAll('[data-submit-generated="1"]').forEach(input => input.remove());

  // ثبت نهایی تا زمان تکمیل تمام موارد اجباری متوقف می‌ماند. این بررسی پیش از
  // پردازش تصاویر انجام می‌شود تا مدیر ابتدا فهرست دقیق کمبودهای فرم را ببیند.
  if (statusValue === 'active') {
    const missing = collectAllMissing();
    if (missing.length) {
      showValidationSummary(missing);
      renderStepper();
      alertIncompleteRequiredFields(missing);
      return;
    }
    showValidationSummary([]);
  }

  const processing = document.querySelector('.image-optimizer-group[data-optimize-state="processing"]');
  if (processing) {
    alert('بهینه‌سازی تصاویر هنوز در حال انجام است. لطفاً چند لحظه صبر کنید.');
    processing.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  const incompleteOptimization = Array.from(document.querySelectorAll('.image-optimizer-group')).find(function (group) {
    const originals = originalImageFiles.get(group) || [];
    const approvals = imageOptimizationApproved.get(group) || [];
    return originals.length > 0 && (approvals.length !== originals.length || approvals.some(function (approved) { return !approved; }));
  });
  if (statusValue === 'active' && incompleteOptimization) {
    showGlobalError('برای ثبت نهایی، همه تصاویر باید ابتدا بهینه‌سازی یا تأیید شوند.');
    incompleteOptimization.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  const failed = document.querySelector('.image-optimizer-group[data-optimize-state="failed"]');
  if (statusValue === 'active' && failed) {
    showGlobalError('بهینه‌سازی بعضی تصاویر ناموفق بوده است؛ پیش از ثبت نهایی دوباره تلاش کنید.');
    failed.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  if ((allowUnoptimizedImageSubmit || clientPreparedImages) && !form.querySelector('[name="skip_image_optimization"]')) {
    form.appendChild(createHiddenInput('skip_image_optimization', '1'));
  }

  document.getElementById('product-status').value = statusValue;

  document.querySelectorAll('#tags-wrap [data-tag-chip]').forEach((chip, idx) => {
    const text = chip.textContent.replace('×', '').trim();
    if(text) form.appendChild(createHiddenInput(`tags[${idx}]`, text));
  });

  // دسته‌بندی‌های چندگانه انتخاب‌شده (تگ‌های چیپ در گام اول) → category_ids[] برای کنترلر
  document.querySelectorAll('#cat-tags-wrap [data-cat-id]').forEach((chip, idx) => {
    const id = chip.dataset.catId;
    if (id) form.appendChild(createHiddenInput(`category_ids[${idx}]`, id));
  });

  // ترتیب select‌های fallback همان ترتیب چیده‌شده در صفحه = اولویت ذخیره‌شده در دیتابیس
  document.querySelectorAll('.fallback-select-item').forEach((select, idx) => {
    const selected = select.options[select.selectedIndex];
    form.appendChild(createHiddenInput(`fallback_providers[${idx}]`, selected?.dataset?.apiProvider || 'openrouter'));
  });

  // Schema Builder ورودی‌های کامل و نام‌گذاری‌شده را خودش داخل فرم می‌سازد.
  // برای جلوگیری از عبور تعداد inputهای تو‌در‌تو از سقف max_input_vars سرور،
  // وضعیت کامل سازنده در یک ورودی JSON ارسال می‌شود. در نبود سازنده جدید، فرم
  // قدیمی همچنان با همان input_schema[...] قبلی کار می‌کند.
  if (typeof window.sbPrepareSubmit === 'function') {
    window.sbPrepareSubmit(form);
  }

  const preparedFormData = new FormData(form);
  if (!navigator.onLine) {
    await queueProductSubmission(form, preparedFormData);
    clearLocalProductDrafts();
    setButtonsLoading(false, statusValue);
    return;
  }

  // Button Loading State — هنگام ارسال واقعی فرم به سرور (رفتار بصری، جلوگیری از دوبار کلیک)
  setButtonsLoading(true, statusValue);

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: preparedFormData,
      credentials: 'same-origin',
    });

    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
      const errors = payload && payload.errors ? payload.errors : {};
      const messages = Object.values(errors).flat().filter(Boolean);
      const schemaError = Object.keys(errors).some(key => key === 'input_schema_json' || key.startsWith('input_schema.'));

      if (schemaError) goStep(3);
      showGlobalError(messages[0] || (payload && payload.message) || 'ثبت محصول انجام نشد. لطفاً مقادیر واردشده را بررسی کنید.');
      showServerValidationSummary(messages);
      setButtonsLoading(false, statusValue);
      return;
    }

    clearLocalProductDrafts();
    window.location.assign((payload && payload.redirect) || '/admin/products');
  } catch (error) {
    await queueProductSubmission(form, preparedFormData);
    showOfflineStatus('ارتباط هنگام ثبت قطع شد؛ محصول در صف امن محلی قرار گرفت.', 'queued');
    setButtonsLoading(false, statusValue);
  }
}

function showServerValidationSummary(messages) {
  const box = document.getElementById('validation-summary');
  const list = document.getElementById('validation-summary-list');
  if (!box || !list || !messages.length) return;
  list.innerHTML = '';
  messages.forEach(function (message) {
    const item = document.createElement('li');
    item.textContent = message;
    list.appendChild(item);
  });
  box.classList.remove('hidden');
  box.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* Loading State یکپارچه برای دکمه‌های پایین صفحه در لحظه ارسال فرم */
function setButtonsLoading(isLoading, which) {
  const draftBtn = document.getElementById('btn-draft');
  const submitBtn = document.getElementById('btn-submit');
  [draftBtn, submitBtn].forEach(btn => {
    if (!btn) return;
    btn.disabled = isLoading;
    if (!isLoading) btn.classList.remove('opacity-70', 'pointer-events-none');
  });
  if (!isLoading) {
    const draftIcon = draftBtn && draftBtn.querySelector('i');
    if (draftIcon) draftIcon.className = 'fa-solid fa-floppy-disk';
    renderStepper();
    return;
  }
  const target = which === 'draft' ? draftBtn : submitBtn;
  if (target) {
    target.classList.add('opacity-70', 'pointer-events-none');
    const icon = target.querySelector('i');
    if (icon) icon.className = 'fa-solid fa-spinner fa-spin';
  }
}

/* ── Global Error Handler UI — نمایش غیرمسدودکننده خطاهای عمومی سمت کلاینت ── */
let globalErrorTimer;
function showGlobalError(message) {
  const box = document.getElementById('global-error-toast');
  const text = document.getElementById('global-error-text');
  if (!box || !text) return;
  text.textContent = message;
  box.classList.remove('hidden');
  clearTimeout(globalErrorTimer);
  globalErrorTimer = setTimeout(hideGlobalError, 6000);
}
function hideGlobalError() {
  const box = document.getElementById('global-error-toast');
  if (box) box.classList.add('hidden');
}
window.addEventListener('offline', () => showOfflineStatus('اینترنت قطع است؛ می‌توانید ادامه دهید و محصول را در صف محلی ثبت کنید.', 'queued'));
window.addEventListener('online', syncOfflineProductSubmissions);

/* ── «راهنمایی آیتم» — پنجره مشترک نمایش توضیح کامل هر فیلد اجباری/اختیاری ──
   با کلیک روی هر آیکونی با کلاس field-help-btn، عنوان و متن آن (از data-help-title/data-help-text،
   که در Blade از config('product_field_help.*') پر می‌شوند) در یک پنجره مرکزی نمایش داده می‌شود.
   رویداد به‌صورت Delegation روی document گرفته می‌شود تا برای آیکون‌های داخل ردیف‌های داینامیک هم کار کند. */
function openFieldHelp(title, text) {
  const overlay = document.getElementById('field-help-overlay');
  const titleEl = document.getElementById('field-help-title');
  const textEl = document.getElementById('field-help-text');
  if (!overlay || !titleEl || !textEl) return;
  titleEl.textContent = title || 'راهنمای فیلد';
  textEl.textContent = text || '';
  overlay.classList.remove('hidden');
}
function closeFieldHelp() {
  const overlay = document.getElementById('field-help-overlay');
  if (overlay) overlay.classList.add('hidden');
}
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.field-help-btn');
  if (!btn) return;
  e.preventDefault();
  e.stopPropagation();
  openFieldHelp(btn.dataset.helpTitle, btn.dataset.helpText);
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') { closeFieldHelp(); return; }
  // آیکون راهنما به‌صورت <span role="button"> ساخته می‌شود (نه <button> واقعی — به دلیل تداخل با
  // «کنترل صاحب لیبل» در سوییچ‌های روشن/خاموش)، پس برخلاف دکمه واقعی با Enter/Space خودش فعال نمی‌شود
  // و باید این رفتار برای دسترس‌پذیری (Keyboard Accessibility) به‌صورت دستی شبیه‌سازی شود.
  if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
    const btn = e.target.closest('.field-help-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    openFieldHelp(btn.dataset.helpTitle, btn.dataset.helpText);
  }
});

/* ── Role-Based UI Locking (فقط نمایشی) — پیش‌فرض Admin یعنی هیچ قفلی اعمال نمی‌شود ── */
function applyRolePreview(role) {
  const lockable = document.querySelectorAll('[data-role-lockable], .field-advanced, input[name^="new_"], select[name^="new_"], textarea[name^="new_"]');
  const locked = role === 'viewer';
  const readonly = role === 'editor';
  lockable.forEach(el => {
    el.closest('div')?.classList.toggle('opacity-40', locked);
    el.closest('div')?.classList.toggle('pointer-events-none', locked);
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
      el.disabled = locked || (readonly && el.type !== 'radio' && el.type !== 'checkbox');
    }
  });
  if (locked) showGlobalError('نقش «Viewer» فقط برای پیش‌نمایش است — فیلدهای پیشرفته غیرفعال شدند (نمایشی).');
}

/* ── مقداردهی اولیه فرم هنگام بارگذاری صفحه (فقط برای حالت تکثیر محصول) ──
   وضعیت فعال/غیرفعال بودن باکس هزینه‌ی کردیت باید با pricing_model هماهنگ شود.
   دسته‌بندی چندگانه (چیپ‌های cat-tags-wrap) مستقل از اینجا در همان step-1.blade.php مقداردهی اولیه می‌شود. */
document.addEventListener('DOMContentLoaded', function () {
  if (CFG.isFreshProduct) {
    Object.values(STEP_REQUIRED_FIELDS).flat().forEach(function (pair) {
      progressInitialValues.set(pair[0], fieldValue(pair[0]));
    });
    progressReady = true;
  }
  goStep(1); // مقداردهی اولیه حالت Stepper (Active/Completed/Pending) طبق طراحی جدید

  const pricingSelect = document.querySelector('select[name="pricing_model"]');
  if (pricingSelect) toggleCreditCost(pricingSelect);

  // فعال‌سازی UI جستجوپذیر روی Selectهای Step ۱ (بعد از پرشدن مقادیر اولیه) — Step ۲/۳ با Lazy Loading مقداردهی می‌شوند
  initSearchables(document.getElementById('panel-1'));

  // به‌روزرسانی زنده‌ی کسری/تیک/نوار پیشرفت با هر تغییر ورودی در کل فرم (بندهای ۴۲،۴۳،۴۵،۴۷)
  const rpForm = document.getElementById('real-product-form');
  if (rpForm) {
    const updateProgress = function (event) {
      const rawName = event.target?.name || '';
      const normalizedName = rawName === 'main_images[]' ? 'main_images' : rawName;
      if (normalizedName) progressTouchedFields.add(normalizedName);
      renderStepper();
    };
    rpForm.addEventListener('input', updateProgress);
    rpForm.addEventListener('change', updateProgress);
  }

  renderStepper();

  // بند ۱۶/۲۳: کارت‌های Collapsible با ذخیره وضعیت باز/بسته
  makeCardsCollapsible();
  // پیش‌نویس فقط با دکمه «ذخیره پیش‌نویس» در دیتابیس ثبت می‌شود.
  // داده‌های محلی نسخه‌های قدیمی پاک می‌شوند تا فرم محصول جدید را آلوده نکنند.
  clearLocalProductDrafts();
  syncOfflineProductSubmissions();
  // بند ۳۳: انتخاب نقش نمایشی (Role Preview)
  var roleSel = document.getElementById('role-preview-select');
  if (roleSel) roleSel.addEventListener('change', function () { applyRolePreview(this.value); });

  document.querySelectorAll('.image-optimizer-group').forEach(function (group) {
    setImageOptimizeState(group, 'idle', group.querySelectorAll('[data-existing]').length ? '' : group.querySelector('.image-optimize-status')?.textContent);
    const input = document.getElementById(group.dataset.input);
    if (!input) return;
    if (!input.files.length && JSON.parse(group.dataset.existing || '[]').length) {
      existingImageFiles(group).then(function (files) {
        originalImageFiles.set(group, files.slice());
        optimizedImageFiles.set(group, []);
        setImageApproval(group, files.length, true);
        selectedImageProfiles.set(group, Array(files.length).fill(''));
        selectedImageIndexes.set(group, 0);
        renderImageGroupPreviews(group, files);
        renderImageComparison(group);
      }).catch(function () {
        setImageOptimizeState(group, 'failed', 'نمایش تصاویر فعلی ممکن نبود؛ صفحه را دوباره بارگذاری کنید.');
      });
    }
    input.addEventListener('change', function () {
      const selected = Array.from(input.files || []);
      originalImageFiles.set(group, selected.slice());
      optimizedImageFiles.set(group, []);
      setImageApproval(group, selected.length, false);
      selectedImageProfiles.set(group, Array(selected.length).fill(''));
      selectedImageIndexes.set(group, 0);
      renderImageGroupPreviews(group, selected);
      renderImageComparison(group);
      setImageOptimizeState(group, 'idle', '');
      optimizeImageGroup(group);
    });
  });
});

/* ══════════════════ بند ۱۶/۲۳ — کارت‌های Collapsible (ذخیره وضعیت در localStorage) ══════════════════ */
function makeCardsCollapsible() {
  var cards = document.querySelectorAll('#real-product-form [class*="bg-[var(--s2)]"]');
  var idx = 0;
  cards.forEach(function (card) {
    var header = card.querySelector(':scope > div');
    if (!header || !/border-b/.test(header.className)) return;
    if (header.dataset.collapsibleReady === '1') return;
    header.dataset.collapsibleReady = '1';
    idx++;
    var key = 'pc-card-collapsed-' + idx;
    header.style.position = 'relative';
    header.style.cursor = 'pointer';
    var chev = document.createElement('i');
    chev.className = 'fa-solid fa-chevron-up text-[10px] text-[var(--text3)] card-collapse-chevron';
    chev.style.position = 'absolute';
    chev.style.left = '0';
    chev.style.top = '2px';
    chev.style.transition = 'transform .2s';
    header.appendChild(chev);
    var bodyEls = Array.prototype.slice.call(card.children).filter(function (c) { return c !== header; });
    function apply(collapsed) {
      bodyEls.forEach(function (el) { el.style.display = collapsed ? 'none' : ''; });
      chev.style.transform = collapsed ? 'rotate(180deg)' : '';
    }
    var collapsed = false;
    try { collapsed = localStorage.getItem(key) === '1'; } catch (e) {}
    apply(collapsed);
    header.addEventListener('click', function (e) {
      if (e.target.closest('button, a, input, select, textarea, label')) return;
      collapsed = !collapsed;
      apply(collapsed);
      try { localStorage.setItem(key, collapsed ? '1' : '0'); } catch (e) {}
    });
  });
}

/* پاک‌سازی سازگار با نسخه‌های قبل؛ از این نسخه هیچ محتوای فرم در مرورگر ذخیره نمی‌شود. */
function clearLocalProductDrafts() {
  try {
    for (var i = localStorage.length - 1; i >= 0; i--) {
      var key = localStorage.key(i);
      if (key === 'pc-autosave-draft' || (key && key.indexOf('pc-autosave-') === 0)) {
        localStorage.removeItem(key);
      }
    }
  } catch (e) {}
}
