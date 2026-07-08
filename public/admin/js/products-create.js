/* ══════════════════════════════════════════════════════════════════
   جاوااسکریپت صفحه «ثبت محصول جدید» (admin/products/create.blade.php)
   مقادیر پویا (مدل‌های AI، ایندکس‌های شروع، زیردسته انتخابی) از طریق
   window.PRODUCT_CREATE_CONFIG که در خود Blade تزریق می‌شود خوانده می‌شوند.
   هیچ Route/API جدیدی صدا زده نمی‌شود؛ submitForm() و Validation واقعی سرور دست‌نخورده می‌مانند.
   ══════════════════════════════════════════════════════════════════ */
const CFG = window.PRODUCT_CREATE_CONFIG || {};
const AI_MODELS = CFG.aiModels || [];

let cur = 1;

/* ── Stepper: هر Step یکی از سه حالت Active / Completed / Pending دارد ── */
function goStep(n) {
  if(n < 1 || n > 5) return;
  cur = n;

  for(let i=1; i<=5; i++) {
    const p = document.getElementById('panel-'+i);
    const tab = document.getElementById('step-tab-'+i);
    const num = document.getElementById('step-num-'+i);
    const label = tab.querySelector('.step-label');
    const title = tab.querySelector('.step-title');

    // ریست همه کلاس‌های حالت قبل از اعمال حالت جدید
    tab.classList.remove('bg-[var(--accent)]/10','border-[var(--accent)]/25','shadow-[0_0_12px_-2px_rgba(160,122,245,0.35)]','bg-[var(--green)]/5','border-[var(--green)]/20');
    num.classList.remove('border-[var(--accent)]','bg-[var(--accent)]/15','text-[var(--accent)]','border-[var(--green)]','bg-[var(--green)]/15','text-[var(--green)]','border-[var(--b2)]','text-[var(--text3)]','scale-105');
    label.classList.remove('text-[var(--accent)]','text-[var(--green)]','text-[var(--text3)]');
    title.classList.remove('text-[var(--text)]','text-[var(--text2)]');

    if (i === n) {
      // Active
      p.classList.remove('hidden'); p.classList.add('block');
      tab.classList.add('bg-[var(--accent)]/10','border-[var(--accent)]/25','shadow-[0_0_12px_-2px_rgba(160,122,245,0.35)]');
      num.classList.add('border-[var(--accent)]','bg-[var(--accent)]/15','text-[var(--accent)]','scale-105');
      num.innerHTML = num.dataset.num;
      label.classList.add('text-[var(--accent)]');
      title.classList.add('text-[var(--text)]');
    } else {
      p.classList.remove('block'); p.classList.add('hidden');
      if (i < n) {
        // Completed
        tab.classList.add('bg-[var(--green)]/5','border-[var(--green)]/20');
        num.classList.add('border-[var(--green)]','bg-[var(--green)]/15','text-[var(--green)]');
        num.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
        label.classList.add('text-[var(--green)]');
        title.classList.add('text-[var(--text2)]');
      } else {
        // Pending
        num.classList.add('border-[var(--b2)]','text-[var(--text3)]');
        num.innerHTML = num.dataset.num;
        label.classList.add('text-[var(--text3)]');
        title.classList.add('text-[var(--text2)]');
      }
    }
  }

  // رنگ خط اتصال بین Stepها (دسکتاپ و موبایل) بر اساس عبور از آن مرحله
  [1,2,3,4].forEach(idx => {
    ['conn-'+idx, 'conn-'+idx+'-m'].forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.remove('bg-[var(--b1)]','bg-[var(--green)]/40');
      el.classList.add(n > idx ? 'bg-[var(--green)]/40' : 'bg-[var(--b1)]');
    });
  });

  document.getElementById('btn-prev').style.display = n === 1 ? 'none' : 'inline-flex';
  document.getElementById('btn-next').style.display = n === 5 ? 'none' : 'inline-flex';
  document.getElementById('btn-submit').style.display = n === 5 ? 'inline-flex' : 'none';
  document.getElementById('step-label-num').textContent = n;
  window.scrollTo({top: 0, behavior: 'smooth'});

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
  1: [ ['name_fa', 'نام فارسی'], ['name_en', 'نام انگلیسی'], ['slug', 'آدرس URL'], ['category', 'دسته‌بندی'] ],
  2: [ ['primary_model', 'مدل اصلی هوش مصنوعی'], ['prompt_template', 'متن پرامپت'] ],
  3: [], // ورودی و متغیرها: فیلد الزامی خاصی ندارد
  4: [], // خروجی و قیمت: همه مقادیر پیش‌فرض دارند
  5: [], // بازبینی نهایی: صرفاً مرور است
};

function fieldValue(name) {
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

function showValidationSummary(missing) {
  const box = document.getElementById('validation-summary');
  const list = document.getElementById('validation-summary-list');
  if (!missing.length) { box.classList.add('hidden'); list.innerHTML = ''; return; }
  list.innerHTML = missing.map(m => `<li><a href="#" class="underline" onclick="focusField('${m.name}'); return false;">${m.label}</a></li>`).join('');
  box.classList.remove('hidden');
}

function focusField(name) {
  const el = document.getElementsByName(name)[0];
  if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); el.classList.add('border-[var(--red)]'); setTimeout(() => el.classList.remove('border-[var(--red)]'), 2000); }
}

/* پیمایش بین Stepها با گیت اعتبارسنجی — فقط برای حرکت روبه‌جلو؛ بازگشت همیشه آزاد است */
function attemptGoStep(n) {
  if (n <= cur) { goStep(n); return; }
  const missing = validateStep(cur);
  if (missing.length) { showValidationSummary(missing); refreshStepValidityDots(); return; }
  showValidationSummary([]);
  goStep(n);
}

function nextStep() {
  if (cur >= 5) return;
  attemptGoStep(cur + 1);
}
function prevStep() { if(cur > 1) { showValidationSummary([]); goStep(cur - 1); } }

/* Step Validation Indicator — نقطه قرمز کوچک روی Stepهایی که سر زده شده‌اند ولی ناقص‌اند */
function refreshStepValidityDots() {
  for (let i = 1; i <= 5; i++) {
    const tab = document.getElementById('step-tab-' + i);
    if (!tab) continue;
    let dot = tab.querySelector('.step-invalid-dot');
    const invalid = ProductCreateState.validation[i] === false;
    if (invalid && !dot) {
      dot = document.createElement('span');
      dot.className = 'step-invalid-dot w-1.5 h-1.5 rounded-full bg-[var(--red)] absolute top-2 left-2';
      tab.style.position = 'relative';
      tab.appendChild(dot);
    } else if (!invalid && dot) {
      dot.remove();
    }
  }
}

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

const subcats = {
  PEOPLE: ['Professional', 'Fashion', 'Lifestyle'],
  BUSINESS: ['Real Estate', 'Medical', 'Education'],
  EVENTS: ['Birthday', 'Wedding', 'Nowruz'],
  FAMILY: ['Parents', 'Kids'],
  AVATARS: ['Gaming', 'Anime', 'Fantasy']
};
function updateSubcat() {
  const main = document.getElementById('cat-main').value;
  const sub = document.getElementById('cat-sub');
  sub.innerHTML = '';
  if(!main || !subcats[main]) {
    sub.innerHTML = '<option value="">ابتدا دسته را انتخاب کنید</option>';
    sub.disabled = true;
    refreshSearchable(sub);
    return;
  }
  sub.disabled = false;
  sub.innerHTML = '<option value="">زیردسته ندارد</option>';
  subcats[main].forEach(s => {
    sub.innerHTML += `<option value="${s}">${s}</option>`;
  });
  refreshSearchable(sub);
}

function createHiddenInput(name, value) {
  const input = document.createElement('input');
  input.type = 'hidden'; input.name = name; input.value = value;
  return input;
}

function submitForm(statusValue) {
  document.getElementById('product-status').value = statusValue;
  const form = document.getElementById('real-product-form');

  document.querySelectorAll('#tags-wrap span').forEach((chip, idx) => {
    const text = chip.textContent.replace('×', '').trim();
    if(text) form.appendChild(createHiddenInput(`tags[${idx}]`, text));
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
   زیردسته باید بعد از پرشدن گزینه‌های آن (updateSubcat) انتخاب شود، و
   وضعیت فعال/غیرفعال بودن باکس هزینه‌ی کردیت باید با pricing_model هماهنگ شود. */
document.addEventListener('DOMContentLoaded', function () {
  goStep(1); // مقداردهی اولیه حالت Stepper (Active/Completed/Pending) طبق طراحی جدید

  const catMain = document.getElementById('cat-main');
  const wantedSub = CFG.wantedSubcategory;
  if (catMain && catMain.value) {
    updateSubcat();
    if (wantedSub) {
      const subSel = document.getElementById('cat-sub');
      const match = Array.from(subSel.options).find(o => o.value === wantedSub);
      if (match) subSel.value = wantedSub;
    }
  } else {
    const sub = document.getElementById('cat-sub');
    if (sub) sub.disabled = true;
  }

  const pricingSelect = document.querySelector('select[name="pricing_model"]');
  if (pricingSelect) toggleCreditCost(pricingSelect);

  // فعال‌سازی UI جستجوپذیر روی Selectهای Step ۱ (بعد از پرشدن مقادیر اولیه) — Step ۲/۳ با Lazy Loading مقداردهی می‌شوند
  initSearchables(document.getElementById('panel-1'));

  refreshStepValidityDots();
});
