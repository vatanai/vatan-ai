{{-- ═══════════════════════════════════════════════════════════════════════════
     گام «تست ثبت محصول» (سندباکس UI-only)
     ─────────────────────────────────────────────────────────────────────────
     هدف: تجربه‌ی جدید «ساخت هوشمند ویژگی‌های محصول» را قبل از اتصال بک‌اند اینجا
     به‌صورت کاملاً UI بسازیم تا مدیر بازخورد بدهد. بعد از تأیید، همین تجربه به
     بک‌اند وصل و به‌عنوان روش اصلی ثبت ویژگی وارد گام ورودی (گام ۳) می‌شود.

     قوانین رعایت‌شده (طبق CLAUDE.md + سند تسک‌ها):
       • فقط توکن‌های رنگی var(--...) — بدون هیچ hex ثابت.
       • بخش‌هایی که هنوز بک‌اند ندارند بج «برنامه‌نویسی شود» دارند.
       • هیچ input این گام name ندارد → با فرم اصلی submit نمی‌شود و ثبت محصول را
         خراب نمی‌کند. ابزارهای JSON/پیش‌نمایش/کپی کاملاً سمت‌کلاینت و واقعی‌اند.
       • انواع مجاز و ساختار دقیقاً از config/product_schema_types.php خوانده می‌شود
         تا JSON خروجی ۱:۱ با ستون input_schema محصول بخورد (اتصال بعدی بی‌دردسر).
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
  $trBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
  $trTypes  = config('product_schema_types.types', []);
  $trGroups = config('product_schema_types.groups', []);
@endphp

<div class="space-y-4" id="tr-root">

  {{-- ── سرکارت: معرفی گام ── --}}
  <div class="bg-gradient-to-l from-[var(--accent)]/10 to-transparent border border-[var(--accent)]/25 rounded-xl p-5">
    <div class="flex items-start gap-3">
      <span class="w-10 h-10 rounded-xl bg-[var(--accent)]/15 text-[var(--accent)] flex items-center justify-center shrink-0"><i class="fa-solid fa-flask-vial"></i></span>
      <div class="min-w-0">
        <div class="text-sm font-extrabold text-[var(--text)] flex items-center gap-2 flex-wrap">
          تست ثبت محصول — ساخت هوشمند ویژگی‌ها
          <span class="inline-flex items-center gap-1 bg-[var(--accent)]/12 text-[var(--accent)] border border-[var(--accent)]/25 rounded px-1.5 py-[1px] text-[9px] font-bold">نسخه آزمایشی</span>
        </div>
        <div class="text-[11px] text-[var(--text2)] mt-1 leading-relaxed">
          اینجا محصول را با زبان ساده توصیف می‌کنی، سیستم (یا هر هوش مصنوعی) ویژگی‌های آن را به‌صورت
          یک «کد JSON» می‌سازد، پیش‌نمایش می‌بینی و بعد از تأیید، همان ویژگی‌ها روی محصول ثبت می‌شوند.
          این گام فعلاً آزمایشی است؛ بعد از تأییدِ تو به گام اصلی ثبت ویژگی تبدیل می‌شود.
        </div>
      </div>
    </div>
  </div>

  {{-- ── کارت ۱: توصیف محصول + ساخت خودکار با AI (mock) ── --}}
  <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
    <div class="mb-3 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2">
        <span class="w-6 h-6 rounded-lg bg-[var(--accent)]/12 text-[var(--accent)] text-[11px] flex items-center justify-center">۱</span>
        توصیف محصول و ویژگی‌های دلخواه
      </div>
    </div>

    <label class="block text-[11px] font-semibold text-[var(--text2)] mb-1.5">این محصول چیست و چه ویژگی‌هایی باید داشته باشد؟</label>
    <textarea id="tr-desc" rows="4"
      class="w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-3 text-xs text-[var(--text)] leading-relaxed resize-y focus:border-[var(--accent)] outline-none"
      placeholder="مثال: یک محصول پرتره سینمایی. ویژگی‌هاش: «سبک تصویر» (کلاسیک، سینمایی، ادیتوریال)، «جنسیت چهره» (زن/مرد)، «حس‌وحال» (آرام، جدی، لبخند)، «میزان حفظ شباهت» به‌صورت اسلایدر، و «نسبت خروجی»."></textarea>

    <div class="flex items-center gap-2 flex-wrap mt-3">
      <button type="button" id="tr-ai-generate"
        class="inline-flex items-center gap-2 px-3.5 h-9 rounded-lg text-xs font-bold bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)] transition-all">
        <i class="fa-solid fa-wand-magic-sparkles"></i> ساخت خودکار ویژگی‌ها با هوش مصنوعی
      </button>
      {!! $trBadge !!}
      <span class="text-[10.5px] text-[var(--text3)]">— با کلیک، سیستم خودش JSON ویژگی‌ها را می‌سازد و در کادر پایین می‌گذارد.</span>
    </div>
    <div id="tr-ai-note" class="hidden mt-3 text-[11px] rounded-lg p-3 bg-[var(--orange)]/8 border border-[var(--orange)]/25 text-[var(--orange)]"></div>
  </div>

  {{-- ── کارت ۲: پرامپت آماده برای هر هوش مصنوعی ── --}}
  <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
    <div class="mb-3 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2">
        <span class="w-6 h-6 rounded-lg bg-[var(--accent)]/12 text-[var(--accent)] text-[11px] flex items-center justify-center">۲</span>
        پرامپت آماده — برای گرفتن JSON از هر هوش مصنوعی
      </div>
      <button type="button" id="tr-copy-prompt"
        class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg text-[11px] font-bold bg-[var(--s1)] text-[var(--text2)] border border-[var(--b1)] hover:text-[var(--text)] hover:border-[var(--b2)] transition-all">
        <i class="fa-solid fa-copy"></i> کپی پرامپت
      </button>
    </div>
    <div class="text-[10.5px] text-[var(--text3)] mb-2 leading-relaxed">
      این پرامپت را کپی کن، در ChatGPT / Gemini / Claude یا هر مدلی بچسبان؛ خروجی، دقیقاً یک کد JSON آماده‌ی این سایت است.
      توصیفِ کادر بالا هم به‌صورت خودکار داخل همین پرامپت قرار می‌گیرد.
    </div>
    <pre id="tr-prompt-box" dir="rtl" class="w-full max-h-64 overflow-auto bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-3 text-[11px] text-[var(--text2)] leading-relaxed whitespace-pre-wrap"></pre>
  </div>

  {{-- ── کارت ۳: ورود/ویرایش کد JSON ── --}}
  <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
    <div class="mb-3 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2">
        <span class="w-6 h-6 rounded-lg bg-[var(--accent)]/12 text-[var(--accent)] text-[11px] flex items-center justify-center">۳</span>
        کد JSON ویژگی‌ها
      </div>
      <div class="flex items-center gap-1.5 flex-wrap">
        <button type="button" id="tr-json-sample" class="tr-mini-btn"><i class="fa-solid fa-file-import"></i> نمونه</button>
        <button type="button" id="tr-json-format" class="tr-mini-btn"><i class="fa-solid fa-indent"></i> قالب‌بندی</button>
        <button type="button" id="tr-json-validate" class="tr-mini-btn"><i class="fa-solid fa-circle-check"></i> بررسی</button>
        <button type="button" id="tr-json-clear" class="tr-mini-btn tr-mini-btn-danger"><i class="fa-solid fa-eraser"></i> پاک‌کردن</button>
      </div>
    </div>
    <textarea id="tr-json" dir="ltr" spellcheck="false" rows="10"
      class="w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-3 text-[11.5px] text-[var(--text)] leading-relaxed resize-y focus:border-[var(--accent)] outline-none"
      style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;"
      placeholder='[ { "field_id": "image_style", "label_fa": "سبک تصویر", "type": "radio", "required": "1", "options": [ {"label":"سینمایی","value":"cinematic","prompt":"cinematic lighting, film look"} ] } ]'></textarea>
    <div id="tr-json-status" class="mt-2 text-[11px] font-semibold flex items-center gap-1.5"></div>
  </div>

  {{-- ── کارت ۴: پیش‌نمایش زنده ویژگی‌ها ── --}}
  <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
    <div class="mb-3 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2">
        <span class="w-6 h-6 rounded-lg bg-[var(--accent)]/12 text-[var(--accent)] text-[11px] flex items-center justify-center">۴</span>
        پیش‌نمایش زنده — همان چیزی که کاربر در صفحه «بساز» می‌بیند
      </div>
      <span id="tr-preview-count" class="text-[10px] font-bold text-[var(--text3)] bg-[var(--text)]/5 rounded px-2 py-0.5">۰ ویژگی</span>
    </div>
    <div id="tr-preview" class="space-y-3"></div>
    <div id="tr-preview-empty" class="text-center py-8 text-[var(--text3)] text-[11px]">
      <i class="fa-solid fa-eye-slash text-2xl mb-2 block opacity-50"></i>
      برای دیدن پیش‌نمایش، کد JSON را وارد کن و «بررسی» را بزن.
    </div>
  </div>

  {{-- ── کارت ۵: تأیید و اعمال (mock) ── --}}
  <div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="min-w-0">
        <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap">
          <span class="w-6 h-6 rounded-lg bg-[var(--accent)]/12 text-[var(--accent)] text-[11px] flex items-center justify-center">۵</span>
          تأیید و اعمال روی محصول
          {!! $trBadge !!}
        </div>
        <div class="text-[10.5px] text-[var(--text3)] mt-1">بعد از تأیید، این ویژگی‌ها به گام «ورودی و متغیرها» منتقل و همراه محصول ثبت می‌شوند.</div>
      </div>
      <button type="button" id="tr-apply"
        class="inline-flex items-center gap-2 px-4 h-10 rounded-xl text-xs font-bold bg-[var(--green)] text-white hover:bg-[var(--green-hover)] transition-all disabled:opacity-50">
        <i class="fa-solid fa-circle-check"></i> تأیید و اعمال ویژگی‌ها
      </button>
    </div>
    <div id="tr-apply-note" class="hidden mt-3 text-[11px] rounded-lg p-3 bg-[var(--orange)]/8 border border-[var(--orange)]/25 text-[var(--orange)]"></div>
  </div>

</div>

<style>
  #tr-root .tr-mini-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:8px;font-size:10.5px;font-weight:700;
    background:var(--s1);color:var(--text2);border:1px solid var(--b1);transition:all .15s;cursor:pointer;}
  #tr-root .tr-mini-btn:hover{color:var(--text);border-color:var(--b2);}
  #tr-root .tr-mini-btn-danger:hover{color:var(--red);border-color:var(--red);}
  #tr-root .tr-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:11px;
    background:var(--s1);border:1px solid var(--b1);color:var(--text2);}
  #tr-root .tr-pv-card{background:var(--s1);border:1px solid var(--b1);border-radius:12px;padding:12px 14px;}
</style>

{{-- انواع مجاز و گروه‌ها از رجیستری واقعی به JS تزریق می‌شوند --}}
<script>
window.TR_CFG = {
  types: @json($trTypes),
  groups: @json($trGroups),
};
</script>
<script>
/* ابزارهای گام «تست ثبت محصول» — کاملاً سمت‌کلاینت (بدون هیچ درخواست سرور). */
(function () {
  'use strict';
  var CFG = window.TR_CFG || { types: {}, groups: {} };
  var TYPES = CFG.types || {};
  var typeKeys = Object.keys(TYPES);

  var faDigits = function (v) { return String(v).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  var $ = function (id) { return document.getElementById(id); };
  var choiceTypes = ['select', 'radio', 'multi_select', 'button_group', 'style_preset', 'gender'];
  var layoutTypes = ['section', 'divider', 'info'];

  /* ── ساخت متن پرامپت آماده (با تزریق توصیف زنده) ── */
  function typeReference() {
    return typeKeys.map(function (k) {
      var t = TYPES[k] || {};
      return '  • ' + k + ' — ' + (t.label || k) + (t.desc ? ' (' + t.desc + ')' : '');
    }).join('\n');
  }
  function buildPrompt() {
    var desc = ($('tr-desc') && $('tr-desc').value.trim()) || '«اینجا توصیف محصولت را بنویس»';
    return [
'تو دستیار ساخت «ویژگی‌های محصول» برای پلتفرم Vatan AI هستی.',
'خروجی تو باید فقط و فقط یک آرایه‌ی JSON معتبر باشد — بدون هیچ توضیح، بدون ```markdown، فقط خود JSON.',
'',
'هر عضو آرایه یک ویژگی است با این کلیدها:',
'- field_id: شناسه‌ی انگلیسیِ یکتا؛ فقط حروف کوچک a-z، عدد و _ ، حتماً با حرف شروع شود. مثل: image_style',
'- label_fa: عنوان فارسی که کاربر می‌بیند. مثل: سبک تصویر',
'- type: دقیقاً یکی از این انواع مجاز:',
typeReference(),
'- required: "1" اگر پرکردنش اجباری است، "0" اگر اختیاری.',
'- description: توضیح کوتاه فارسی زیر عنوان (اختیاری).',
'- options: فقط برای انواع انتخابی (select, radio, multi_select, button_group, style_preset, gender).',
'    آرایه‌ای از آبجکت‌های {label, value, prompt}:',
'      • label = متن فارسی گزینه   • value = شناسه انگلیسی کوچک   • prompt = متن انگلیسیِ دقیق که هنگام انتخاب این گزینه وارد پرامپت مدل تصویرساز می‌شود',
'- برای انواع عددی/اسلایدری (number, slider, strength): کلیدهای min, max, step را هم بده.',
'',
'قواعد مهم:',
'- «prompt» هر گزینه مهم‌ترین بخش کیفیت است: انگلیسی، دقیق و تصویری بنویس.',
'- فقط ویژگی‌هایی بساز که واقعاً به این محصول مربوط‌اند.',
'- خروجی فقط JSON خام باشد.',
'',
'محصول و ویژگی‌های موردنظر:',
'«' + desc + '»'
    ].join('\n');
  }
  function refreshPrompt() { if ($('tr-prompt-box')) $('tr-prompt-box').textContent = buildPrompt(); }

  /* ── نمونه JSON آماده ── */
  var SAMPLE = [
    { field_id: 'image_style', label_fa: 'سبک تصویر', type: 'radio', required: '1',
      description: 'حال‌وهوای کلی خروجی را انتخاب کنید',
      options: [
        { label: 'سینمایی', value: 'cinematic', prompt: 'cinematic lighting, shallow depth of field, film grain' },
        { label: 'کلاسیک',  value: 'classic',   prompt: 'timeless classic portrait, soft natural light' },
        { label: 'ادیتوریال', value: 'editorial', prompt: 'high-fashion editorial style, studio lighting' }
      ] },
    { field_id: 'face_gender', label_fa: 'جنسیت چهره', type: 'gender', required: '1',
      options: [
        { label: 'مرد', value: 'male', prompt: 'male subject, masculine facial structure' },
        { label: 'زن',  value: 'female', prompt: 'female subject, feminine facial structure' }
      ] },
    { field_id: 'identity_strength', label_fa: 'میزان حفظ شباهت', type: 'strength', required: '0',
      min: 50, max: 100, step: 5 },
    { field_id: 'user_note', label_fa: 'توضیح دلخواه شما', type: 'prompt', required: '0',
      description: 'هر جزئیاتی که دوست دارید در خروجی باشد' }
  ];

  /* ── اعتبارسنجی JSON ── */
  function setStatus(kind, msg) {
    var el = $('tr-json-status'); if (!el) return;
    var map = { ok: ['var(--green)', 'fa-circle-check'], err: ['var(--red)', 'fa-circle-xmark'], info: ['var(--text3)', 'fa-circle-info'] };
    var m = map[kind] || map.info;
    el.style.color = m[0];
    el.innerHTML = '<i class="fa-solid ' + m[1] + '"></i><span>' + msg + '</span>';
  }
  function parseJson(raw) {
    raw = (raw || '').trim();
    if (raw === '') return { ok: false, empty: true };
    // پاک‌سازی حصار markdown احتمالی
    raw = raw.replace(/^```(json)?/i, '').replace(/```$/,'').trim();
    var data;
    try { data = JSON.parse(raw); } catch (e) { return { ok: false, error: 'JSON نامعتبر است: ' + e.message }; }
    if (!Array.isArray(data)) return { ok: false, error: 'خروجی باید یک «آرایه» از ویژگی‌ها باشد (با [ شروع شود).' };
    var errs = [], ids = {};
    data.forEach(function (f, i) {
      var n = faDigits(i + 1);
      if (typeof f !== 'object' || f === null) { errs.push('ویژگی ' + n + ': ساختار نامعتبر.'); return; }
      if (!f.field_id || !/^[a-z][a-z0-9_]*$/.test(String(f.field_id))) errs.push('ویژگی ' + n + ': field_id نامعتبر (فقط a-z، عدد، _ و شروع با حرف).');
      else if (ids[f.field_id]) errs.push('ویژگی ' + n + ': field_id تکراری «' + f.field_id + '».'); else ids[f.field_id] = 1;
      if (!f.label_fa || !String(f.label_fa).trim()) errs.push('ویژگی ' + n + ': label_fa (عنوان فارسی) خالی است.');
      if (!f.type || typeKeys.indexOf(String(f.type)) === -1) errs.push('ویژگی ' + n + ': نوع «' + (f.type || '—') + '» مجاز نیست.');
      if (choiceTypes.indexOf(String(f.type)) > -1 && (!Array.isArray(f.options) || f.options.length === 0))
        errs.push('ویژگی ' + n + ' («' + (f.label_fa || f.field_id) + '»): این نوع باید حداقل یک گزینه داشته باشد.');
    });
    return { ok: errs.length === 0, data: data, errors: errs };
  }

  /* ── رندر پیش‌نمایش کاربرپسند ── */
  function ctrlPreview(f) {
    var t = String(f.type), opts = Array.isArray(f.options) ? f.options : [];
    var chips = function () { return opts.map(function (o) { return '<span class="tr-chip">' + esc(o.label || o.value || '') + '</span>'; }).join(' '); };
    if (layoutTypes.indexOf(t) > -1) {
      if (t === 'divider') return '<div style="height:1px;background:var(--b1);margin:4px 0"></div>';
      if (t === 'info') return '<div class="text-[11px] rounded-lg p-2.5 bg-[var(--accent)]/8 border border-[var(--accent)]/20 text-[var(--text2)]"><i class="fa-solid fa-circle-info text-[var(--accent)] ml-1"></i>' + esc(f.label_fa || f.description || 'پیام راهنما') + '</div>';
      return '<div class="text-xs font-bold text-[var(--text)]">' + esc(f.label_fa || '') + '</div>';
    }
    if (choiceTypes.indexOf(t) > -1) return '<div class="flex flex-wrap gap-1.5">' + (chips() || '<span class="text-[10px] text-[var(--text3)]">— بدون گزینه —</span>') + '</div>';
    if (t === 'switch' || t === 'checkbox') return '<span class="inline-flex items-center gap-2 text-[11px] text-[var(--text2)]"><span style="width:34px;height:19px;border-radius:999px;background:var(--b2);position:relative;display:inline-block"><span style="position:absolute;top:2px;right:2px;width:15px;height:15px;border-radius:50%;background:var(--s2)"></span></span> روشن / خاموش</span>';
    if (t === 'slider' || t === 'strength') return '<input type="range" disabled class="w-full accent-[var(--accent)]" min="' + (f.min != null ? f.min : 0) + '" max="' + (f.max != null ? f.max : 100) + '" value="' + (f.min != null ? f.min : 0) + '">';
    if (t === 'number' || t === 'seed') return '<div class="h-9 w-28 rounded-lg bg-[var(--s2)] border border-[var(--b1)] flex items-center px-3 text-[11px] text-[var(--text3)]" dir="ltr">123</div>';
    if (t === 'color') return '<span style="width:26px;height:26px;border-radius:8px;background:var(--accent);display:inline-block;border:1px solid var(--b1)"></span>';
    if (t === 'image_upload' || t === 'multi_image' || t === 'file_upload') return '<div class="h-16 rounded-lg border-2 border-dashed border-[var(--b2)] flex items-center justify-center text-[11px] text-[var(--text3)] gap-2"><i class="fa-solid fa-cloud-arrow-up"></i> آپلود ' + (t === 'multi_image' ? 'چند تصویر' : (t === 'file_upload' ? 'فایل' : 'تصویر')) + '</div>';
    if (t === 'textarea' || t === 'prompt' || t === 'negative_prompt') return '<div class="h-16 rounded-lg bg-[var(--s2)] border border-[var(--b1)] px-3 py-2 text-[11px] text-[var(--text3)]">' + esc(f.placeholder || 'متن کاربر...') + '</div>';
    return '<div class="h-9 rounded-lg bg-[var(--s2)] border border-[var(--b1)] flex items-center px-3 text-[11px] text-[var(--text3)]">' + esc(f.placeholder || 'ورودی کاربر...') + '</div>';
  }
  function esc(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function renderPreview(data) {
    var wrap = $('tr-preview'), empty = $('tr-preview-empty'), count = $('tr-preview-count');
    if (!wrap) return;
    if (!data || !data.length) { wrap.innerHTML = ''; if (empty) empty.style.display = 'block'; if (count) count.textContent = '۰ ویژگی'; return; }
    if (empty) empty.style.display = 'none';
    if (count) count.textContent = faDigits(data.length) + ' ویژگی';
    wrap.innerHTML = data.map(function (f) {
      var t = TYPES[String(f.type)] || {};
      var isLayout = layoutTypes.indexOf(String(f.type)) > -1;
      var head = isLayout ? '' :
        '<div class="flex items-center justify-between gap-2 mb-2 flex-wrap">' +
          '<div class="text-[11.5px] font-bold text-[var(--text)]">' + esc(f.label_fa || f.field_id) +
            (String(f.required) === '1' ? ' <span style="color:var(--red)">*</span>' : '') + '</div>' +
          '<span class="text-[9.5px] font-bold text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5"><i class="fa-solid ' + (t.icon || 'fa-font') + ' ml-1"></i>' + esc(t.label || f.type) + '</span>' +
        '</div>' +
        (f.description ? '<div class="text-[10.5px] text-[var(--text3)] mb-2">' + esc(f.description) + '</div>' : '');
      return '<div class="tr-pv-card">' + head + ctrlPreview(f) + '</div>';
    }).join('');
  }

  /* ── سیم‌کشی رویدادها ── */
  function doValidate(showEmpty) {
    var res = parseJson($('tr-json') ? $('tr-json').value : '');
    if (res.empty) { if (showEmpty) setStatus('info', 'کادر JSON خالی است.'); renderPreview([]); return res; }
    if (res.error) { setStatus('err', res.error); renderPreview([]); return res; }
    if (!res.ok) { setStatus('err', res.errors.length + ' ایراد: ' + res.errors[0]); renderPreview(res.data); return res; }
    setStatus('ok', faDigits(res.data.length) + ' ویژگی معتبر است. آماده‌ی تأیید.');
    renderPreview(res.data);
    return res;
  }

  window.initTestRegistration = function () {
    if (window.__trInited) return; window.__trInited = true;
    refreshPrompt();
    if ($('tr-desc')) $('tr-desc').addEventListener('input', refreshPrompt);

    if ($('tr-copy-prompt')) $('tr-copy-prompt').addEventListener('click', function () {
      var txt = buildPrompt();
      var done = function () { flash($('tr-copy-prompt'), '<i class="fa-solid fa-check"></i> کپی شد'); };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(txt).then(done, function () { legacyCopy(txt); done(); });
      else { legacyCopy(txt); done(); }
    });

    if ($('tr-json-sample')) $('tr-json-sample').addEventListener('click', function () {
      $('tr-json').value = JSON.stringify(SAMPLE, null, 2); doValidate(true);
    });
    if ($('tr-json-format')) $('tr-json-format').addEventListener('click', function () {
      var res = parseJson($('tr-json').value);
      if (res.data) { $('tr-json').value = JSON.stringify(res.data, null, 2); doValidate(true); }
      else setStatus('err', res.error || 'ابتدا JSON را درست کن.');
    });
    if ($('tr-json-validate')) $('tr-json-validate').addEventListener('click', function () { doValidate(true); });
    if ($('tr-json-clear')) $('tr-json-clear').addEventListener('click', function () {
      $('tr-json').value = ''; setStatus('info', 'پاک شد.'); renderPreview([]);
    });
    if ($('tr-json')) $('tr-json').addEventListener('input', function () { doValidate(false); });

    /* دکمه‌های نیازمند بک‌اند (mock) */
    if ($('tr-ai-generate')) $('tr-ai-generate').addEventListener('click', function () {
      var n = $('tr-ai-note'); if (!n) return;
      n.classList.remove('hidden');
      n.innerHTML = '<i class="fa-solid fa-triangle-exclamation ml-1"></i> ساخت خودکار با هوش مصنوعی هنوز به بک‌اند وصل نشده (برنامه‌نویسی شود). فعلاً برای تست، دکمه‌ی «نمونه» را در کادر JSON بزن تا یک خروجی واقعی ببینی.';
    });
    if ($('tr-apply')) $('tr-apply').addEventListener('click', function () {
      var res = doValidate(true), n = $('tr-apply-note'); if (!n) return;
      n.classList.remove('hidden');
      if (res.ok) n.innerHTML = '<i class="fa-solid fa-circle-check ml-1" style="color:var(--green)"></i> ' + faDigits(res.data.length) + ' ویژگی آماده‌ی اعمال است. اتصال واقعی به گام ورودی و ثبت روی محصول: برنامه‌نویسی شود.';
      else n.innerHTML = '<i class="fa-solid fa-triangle-exclamation ml-1"></i> اول ایرادهای کد JSON را برطرف کن، بعد تأیید کن.';
    });

    setStatus('info', 'کد JSON را وارد کن یا «نمونه» را بزن.');
  };
  function flash(btn, html) { if (!btn) return; var o = btn.innerHTML; btn.innerHTML = html; setTimeout(function () { btn.innerHTML = o; }, 1400); }
  function legacyCopy(t) { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); } catch (e) {} document.body.removeChild(a); }

  /* اگر گام هنگام لود قابل‌مشاهده بود (یا لود تنبل صدا نزد)، یک‌بار خودمان مقداردهی می‌کنیم */
  if (document.readyState !== 'loading') { try { window.initTestRegistration(); } catch (e) {} }
  else document.addEventListener('DOMContentLoaded', function () { try { window.initTestRegistration(); } catch (e) {} });
})();
</script>
