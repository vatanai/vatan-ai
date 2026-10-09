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
function completionMirrorSourceStep(n) {
  const source = Number(document.getElementById('step-tab-' + n)?.dataset.completionMirrorsStep || 0);
  return source > 0 && source !== n ? source : null;
}

function computeStepStatus(n) {
  const mirroredFrom = completionMirrorSourceStep(n);
  if (mirroredFrom) {
    const sourceStatus = computeStepStatus(mirroredFrom);
    return Object.assign({}, sourceStatus, { mirroredFrom: mirroredFrom });
  }
  if (n === 5) {
    const ready = [1, 2, 3, 4].every(function (step) {
      const status = computeStepStatus(step);
      return status.total === 0 || status.complete;
    });
    return { total: 1, filled: ready ? 1 : 0, complete: ready, dynamic: true };
  }
  if (n === 3) {
    const featuresEnabled = document.getElementById('special-features-enabled')?.value === '1';
    if (!featuresEnabled) {
      return {
        total: 0,
        filled: 0,
        complete: true,
        dynamic: true
      };
    }
    const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
    if (!rows.length) {
      return { total: 1, filled: 0, complete: false, dynamic: true };
    }
    let filled = 0;
    rows.forEach(function (r) {
      const requiredInputsComplete = Array.from(r.querySelectorAll('[required]')).every(function (input) {
        return String(input.value || '').trim() !== '' && input.checkValidity();
      });
      const builderValidationPassed = !r.classList.contains('sb-invalid');
      if (requiredInputsComplete && builderValidationPassed) filled++;
    });
    return { total: rows.length, filled: filled, complete: filled === rows.length, dynamic: true };
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

    // وضعیت گام آینه‌ای برای نمایش تیک استفاده می‌شود، اما نباید همان فیلدهای
    // اجباری را دوباره در درصد پیشرفت و قفل ثبت نهایی حساب کند.
    if (st.total > 0 && !st.mirroredFrom) { overallTotal += st.total; overallFilled += st.filled; }
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
  if (n === 2 && typeof onPrimaryModelChange === 'function') onPrimaryModelChange(); // مدل اصلی هوش مصنوعی
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
  2: [ ['prompt_template', 'متن پرامپت'], ['identity_preservation', 'وضعیت حفظ هویت'] ],
  3: [],
  4: [], // وضعیت تکمیل این گام از طریق data-completion-mirrors-step از گام دوم می‌آید
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
  const sourceStep = completionMirrorSourceStep(n) || n;
  const missing = [];
  (STEP_REQUIRED_FIELDS[sourceStep] || []).forEach(([name, label]) => {
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

/* بهینه‌سازی و آپلودر عکس محصول به public/admin/js/image-optimizer.js منتقل شد (مشترک با ثبت محصول پروداکتی). */

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

  // پاداش مالک محصول، وقتی روشن است، برای پیش‌نویس و ثبت نهایی مالک و چهار مقدار معتبر می‌خواهد.
  // این مسیر پیش از FormData کنترل می‌شود چون ثبت محصول با fetch انجام می‌شود و submit معمولی فرم اجرا نمی‌شود.
  if (typeof window.validateCreatorRewardSettings === 'function' && !window.validateCreatorRewardSettings()) {
    return;
  }

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
  if (typeof window.saveStepTwoModelQualityPresetIfDirty === 'function'
      && !await window.saveStepTwoModelQualityPresetIfDirty()) {
    goStep(2);
    showGlobalError('ذخیره پیش‌فرض مدل انجام نشد؛ خطای نمایش‌داده‌شده در گام دوم را بررسی کنید.');
    return;
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
