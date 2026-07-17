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
  if (n === 3) {
    const rows = document.querySelectorAll('#input-fields-list .input-schema-row');
    let filled = 0;
    rows.forEach(function (r) {
      const idEl  = r.querySelector('.schema-id');
      const lblEl = r.querySelector('.schema-label');
      const id  = idEl  ? (idEl.value  || '').trim() : '';
      const lbl = lblEl ? (lblEl.value || '').trim() : '';
      if (id && lbl) filled++;
    });
    return { total: rows.length, filled: filled, complete: rows.length > 0 && filled === rows.length, dynamic: true };
  }
  const req = STEP_REQUIRED_FIELDS[n] || [];
  let filled = 0;
  req.forEach(function (pair) { if (fieldValue(pair[0])) filled++; });
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
  document.getElementById('btn-next').style.display = n === 5 ? 'none' : 'inline-flex';
  document.getElementById('btn-submit').style.display = n === 5 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = toFa(n);
  window.scrollTo({ top: 0, behavior: 'smooth' });

  renderStepper();
  lazyInitStep(n); // Step Lazy Loading — مقداردهی سنگین هر Step فقط در اولین بازدید آن
  ProductCreateState.ui.currentStep = n;
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
  1: [ ['name_fa', 'نام فارسی'], ['name_en', 'نام انگلیسی'], ['slug', 'آدرس URL'], ['category_ids', 'دسته‌بندی'] ],
  2: [ ['primary_model', 'مدل اصلی هوش مصنوعی'], ['prompt_template', 'متن پرامپت'] ],
  3: [], // ورودی و متغیرها: فیلد الزامی خاصی ندارد
  4: [], // خروجی و قیمت: همه مقادیر پیش‌فرض دارند
  5: [], // بازبینی نهایی: صرفاً مرور است
};

function fieldValue(name) {
  // دسته‌بندی چندگانه (تگ‌های انتخاب‌شده) دیگر یک <select> واحد با name=category_id نیست؛
  // تکمیل‌بودنش یعنی حداقل یک چیپ دسته‌بندی در cat-tags-wrap انتخاب شده باشد.
  if (name === 'category_ids') {
    return document.querySelectorAll('#cat-tags-wrap [data-cat-id]').length ? '1' : '';
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

function focusField(name) {
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
  return missing;
}

/* سازگاری با کدهای قبلی: به‌جای نقطه‌های قرمز، رندر کامل Stepper (کسری/تیک/نوار پیشرفت) */
function refreshStepValidityDots() { renderStepper(); }

function addTag(e) {
  if (e.key !== 'Enter' && e.key !== ',') return;
  e.preventDefault();
  const inp = document.getElementById('tags-raw');
  const v = inp.value.trim();
  if (!v) return;

  const chip = document.createElement('span');
  chip.className = 'inline-flex items-center gap-1 bg-[var(--accent)]/12 border border-[var(--accent)]/25 rounded px-2 py-0.5 text-xs text-[var(--accent)]';
  chip.innerHTML = `${v}<button type="button" class="text-[var(--text3)] hover:text-[var(--red)] font-bold mr-1" onclick="this.parentElement.remove()">×</button>`;
  document.getElementById('tags-wrap').insertBefore(chip, inp);
  inp.value = '';
}

function updateFileLabel(input, id, isMultiple = false) {
  if(input.files && input.files.length > 0) {
    document.getElementById(id).textContent = isMultiple
      ? `${input.files.length} فایل انتخاب شد`
      : `فایل انتخاب شد: ${input.files[0].name}`;
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
    Array.from(select.options).forEach(opt => {
      if (!opt.value && !opt.textContent.trim()) return;
      if (filter && opt.textContent.toLowerCase().indexOf(filter.toLowerCase()) === -1) return;
      const item = document.createElement('div');
      item.className = 'px-3 py-2 text-xs cursor-pointer transition-colors ' + (opt.value === select.value ? 'bg-[var(--accent)]/15 text-[var(--accent)]' : 'text-[var(--text2)] hover:bg-[var(--accent)]/10 hover:text-[var(--text)]');
      item.textContent = opt.textContent;
      item.onclick = () => {
        select.value = opt.value;
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
    `<option value="${m.id}">${m.name} (${m.provider})</option>`
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
  return input;
}

function submitForm(statusValue) {
  const form = document.getElementById('real-product-form');

  // بند ۴۴: فقط در «ثبت نهایی» (active) فیلدهای اجباری چک می‌شوند؛ «ذخیره پیش‌نویس» هیچ محدودیتی ندارد.
  if (statusValue === 'active') {
    const missing = collectAllMissing();
    if (missing.length) {
      showValidationSummary(missing);
      renderStepper();
      return; // جلوگیری از ثبت تا تکمیل موارد ناقص
    }
    showValidationSummary([]);
  }

  document.getElementById('product-status').value = statusValue;

  document.querySelectorAll('#tags-wrap span').forEach((chip, idx) => {
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
    form.appendChild(createHiddenInput(`fallback_models[${idx}]`, select.value));
  });

  document.querySelectorAll('.input-schema-row').forEach((row, idx) => {
    const fieldId = row.querySelector('.schema-id').value.trim();
    const labelFa = row.querySelector('.schema-label').value.trim();
    const type = row.querySelector('.schema-type').value;
    const required = row.querySelector('.schema-required').value;

    if(fieldId) {
      form.appendChild(createHiddenInput(`input_schema[${idx}][field_id]`, fieldId));
      form.appendChild(createHiddenInput(`input_schema[${idx}][label_fa]`, labelFa));
      form.appendChild(createHiddenInput(`input_schema[${idx}][type]`, type));
      form.appendChild(createHiddenInput(`input_schema[${idx}][required]`, required));
    }
  });

  // Button Loading State — هنگام ارسال واقعی فرم به سرور (رفتار بصری، جلوگیری از دوبار کلیک)
  setButtonsLoading(true, statusValue);
  form.submit();
}

/* Loading State یکپارچه برای دکمه‌های پایین صفحه در لحظه ارسال فرم */
function setButtonsLoading(isLoading, which) {
  const draftBtn = document.getElementById('btn-draft');
  const submitBtn = document.getElementById('btn-submit');
  [draftBtn, submitBtn].forEach(btn => { if (btn) btn.disabled = isLoading; });
  if (!isLoading) return;
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
window.addEventListener('offline', () => showGlobalError('اتصال اینترنت قطع شد — تغییرات فقط به‌صورت محلی ذخیره می‌شوند.'));

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
  goStep(1); // مقداردهی اولیه حالت Stepper (Active/Completed/Pending) طبق طراحی جدید

  const pricingSelect = document.querySelector('select[name="pricing_model"]');
  if (pricingSelect) toggleCreditCost(pricingSelect);

  // فعال‌سازی UI جستجوپذیر روی Selectهای Step ۱ (بعد از پرشدن مقادیر اولیه) — Step ۲/۳ با Lazy Loading مقداردهی می‌شوند
  initSearchables(document.getElementById('panel-1'));

  // به‌روزرسانی زنده‌ی کسری/تیک/نوار پیشرفت با هر تغییر ورودی در کل فرم (بندهای ۴۲،۴۳،۴۵،۴۷)
  const rpForm = document.getElementById('real-product-form');
  if (rpForm) {
    rpForm.addEventListener('input', renderStepper);
    rpForm.addEventListener('change', renderStepper);
  }

  renderStepper();

  // بند ۱۶/۲۳: کارت‌های Collapsible با ذخیره وضعیت باز/بسته
  makeCardsCollapsible();
  // بند ۲۶: ذخیره خودکار پیش‌نویس + بنر بازیابی
  initAutosaveDraft();
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
      bodyEls.forEach(function (el) { el.classList.toggle('hidden', collapsed); });
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

/* ══════════════════ بند ۲۶ — ذخیره خودکار پیش‌نویس (فقط UI / localStorage) ══════════════════ */
var AUTOSAVE_KEY = 'pc-autosave-draft';
function autosaveSerialize() {
  var form = document.getElementById('real-product-form');
  if (!form) return null;
  var data = {};
  Array.prototype.forEach.call(form.elements, function (el) {
    if (!el.name || el.type === 'file' || el.name === '_token' || el.name === 'status') return;
    if (el.type === 'checkbox') data[el.name] = el.checked ? (el.value || '1') : '';
    else if (el.type === 'radio') { if (el.checked) data[el.name] = el.value; }
    else data[el.name] = el.value;
  });
  return data;
}
function autosaveDraft() {
  try {
    var data = autosaveSerialize();
    if (!data) return;
    localStorage.setItem(AUTOSAVE_KEY, JSON.stringify({ t: (new Date()).getTime(), d: data }));
    var el = document.getElementById('autosave-status');
    if (el) {
      var now = new Date();
      el.textContent = 'آخرین ذخیره خودکار: ' + toFa(('0' + now.getHours()).slice(-2)) + ':' + toFa(('0' + now.getMinutes()).slice(-2));
    }
  } catch (e) {}
}
function initAutosaveDraft() {
  try {
    var raw = localStorage.getItem(AUTOSAVE_KEY);
    var banner = document.getElementById('draft-recovery-banner');
    var isDuplicate = !!document.querySelector('input[name="duplicate_from"]');
    if (raw && banner && !isDuplicate) banner.classList.remove('hidden');
  } catch (e) {}
  setInterval(autosaveDraft, 10000); // هر ۱۰ ثانیه
}
function restoreAutosaveDraft() {
  try {
    var raw = localStorage.getItem(AUTOSAVE_KEY);
    if (!raw) return;
    var data = (JSON.parse(raw) || {}).d || {};
    var form = document.getElementById('real-product-form');
    Object.keys(data).forEach(function (name) {
      var els = form.querySelectorAll('[name="' + name.replace(/"/g, '') + '"]');
      els.forEach(function (el) {
        if (el.type === 'checkbox') el.checked = !!data[name];
        else if (el.type === 'radio') el.checked = (el.value === data[name]);
        else el.value = data[name];
      });
    });
    var banner = document.getElementById('draft-recovery-banner');
    if (banner) banner.classList.add('hidden');
    renderStepper();
    if (typeof refreshFinalSummary === 'function') refreshFinalSummary();
    if (typeof refreshProductPreview === 'function') refreshProductPreview();
  } catch (e) {}
}
function dismissDraftRecovery() {
  var banner = document.getElementById('draft-recovery-banner');
  if (banner) banner.classList.add('hidden');
  try { localStorage.removeItem(AUTOSAVE_KEY); } catch (e) {}
}
