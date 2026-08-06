/* ═══════════════════════════════════════════════════════════════════════════
   سازنده «ویژگی‌های خاص محصول» (Schema Builder) — State-driven
   ─────────────────────────────────────────────────────────────────────────
   منبع انواع فیلد: config/product_schema_types.php → window.SCHEMA_BUILDER_CFG
   خروجی: input های نام‌گذاری‌شده input_schema[i][...] داخل همان فرم اصلی —
   بک‌اند این ساختار را پیش از ذخیره اعتبارسنجی و پاک‌سازی می‌کند.

   سازگاری عقب‌رو با products-create.js:
     • کانتینر #input-fields-list و کلاس ردیف input-schema-row حفظ شده
     • کلاس‌های schema-id / schema-label / schema-type / schema-required روی
       ورودی‌های واقعی هر کارت هستند تا Stepper گام ۳ آن‌ها را بررسی کند
     • فرمت قدیمی (options رشته‌ای با کاما، help_text) هنگام لود تبدیل می‌شود
   همه نام‌های سراسری با پیشوند sb شروع می‌شوند تا با هیچ تابع قبلی تداخل نکنند.
   ═══════════════════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  var CFG = window.SCHEMA_BUILDER_CFG || { groups: {}, types: {}, initial: [] };
  var TYPES = CFG.types || {};
  var GROUPS = CFG.groups || {};
  var TEMPLATES = CFG.templates || {};

  /* وضعیت مرکزی — تنها منبع حقیقت UI */
  var SB = { fields: [], libTarget: null };
  window.SB_STATE = SB; // برای پیش‌نمایش زنده (تسک بعدی) و دیباگ

  /*
   * قرارداد ارسال فرم:
   * هر ویژگی تعداد زیادی input تو‌در‌تو می‌سازد. ارسال مستقیم همه آن‌ها در فرم
   * multipart ممکن است از max_input_vars سرور عبور کند و درخواست را ناقص کند.
   * پیش از submit، وضعیت مرکزی را در یک JSON واحد می‌فرستیم و inputهای تکراری
   * را غیرفعال می‌کنیم. بک‌اند JSON را دوباره به input_schema تبدیل و سپس با
   * همان قواعد سخت‌گیرانه قبلی اعتبارسنجی می‌کند.
   */
  window.sbPrepareSubmit = function (form) {
    if (!form) return;

    var payload = SB.fields.map(function (field) {
      var clean = {};
      Object.keys(field).forEach(function (key) {
        if (key.charAt(0) !== '_') clean[key] = field[key];
      });
      return clean;
    });

    var jsonInput = form.querySelector('input[name="input_schema_json"]');
    if (!jsonInput) {
      jsonInput = document.createElement('input');
      jsonInput.type = 'hidden';
      jsonInput.name = 'input_schema_json';
      form.appendChild(jsonInput);
    }
    jsonInput.value = JSON.stringify(payload);

    form.querySelectorAll('[name^="input_schema["]').forEach(function (input) {
      input.disabled = true;
    });
  };

  /* ── ابزارها ── */
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }
  function toFaNum(v) { return String(v).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
  function typeOf(key) { return TYPES[key] || TYPES.text || { label: key, icon: 'fa-font', caps: {} }; }
  function caps(f) { return typeOf(f.type).caps || {}; }
  function isLayout(f) { return !!caps(f).layout; }

  function sanitizeId(v) {
    v = String(v || '').toLowerCase().trim()
      .replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '').replace(/^_+/, '');
    if (v && !/^[a-z]/.test(v)) v = 'f_' + v;
    return v;
  }
  function uniqueId(base) {
    base = sanitizeId(base) || 'field';
    var id = base, n = 1;
    while (SB.fields.some(function (f) { return f.field_id === id; })) id = base + '_' + (++n);
    return id;
  }

  /* ── نرمال‌سازی داده (فرمت قدیمی → جدید) ── */
  function normalizeOptions(raw) {
    if (Array.isArray(raw)) {
      return raw.map(function (o) {
        if (o && typeof o === 'object') {
          return {
            value: String(o.value != null ? o.value : (o.label || '')),
            label: String(o.label != null ? o.label : (o.value || '')),
            prompt: String(o.prompt || ''),
            credit: o.credit === '' || o.credit == null ? '' : String(o.credit),
            image: String(o.image || ''),
          };
        }
        return { value: String(o), label: String(o), prompt: '', credit: '', image: '' };
      }).filter(function (o) { return o.value !== '' || o.label !== ''; });
    }
    if (typeof raw === 'string' && raw.trim() !== '') {
      return raw.split(',').map(function (s) { return s.trim(); }).filter(Boolean)
        .map(function (s) { return { value: s, label: s, prompt: '', credit: '', image: '' }; });
    }
    return [];
  }

  function normalizeField(raw) {
    raw = raw || {};
    var type = String(raw.type || 'text');
    var opts = normalizeOptions(raw.options);
    // فرمت قدیمی: checkbox با گزینه‌ها = چندانتخابی
    if (type === 'checkbox' && opts.length) type = 'multi_select';
    if (!TYPES[type]) type = 'text';
    var t = typeOf(type);
    var presets = t.presets || {};
    return {
      field_id: sanitizeId(raw.field_id || ''),
      label_fa: String(raw.label_fa || ''),
      type: type,
      required: (String(raw.required) === '1' || raw.required === true) ? '1' : '0',
      hidden: (String(raw.hidden) === '1' || raw.hidden === true) ? '1' : '0',
      description: String(raw.description || raw.help_text || ''),
      placeholder: String(raw.placeholder || ''),
      default: raw.default == null ? '' : String(raw.default),
      min: raw.min == null ? '' : String(raw.min),
      max: raw.max == null ? '' : String(raw.max),
      step: raw.step == null ? '' : String(raw.step),
      unit: String(raw.unit || presets.unit || ''),
      regex: String(raw.regex || ''),
      max_files: raw.max_files == null ? String(presets.max_files || '') : String(raw.max_files),
      max_size_mb: raw.max_size_mb == null ? String(presets.max_size_mb || '') : String(raw.max_size_mb),
      accept: raw.accept == null ? String(presets.accept || '') : String(raw.accept),
      credit_cost: raw.credit_cost == null ? '' : String(raw.credit_cost),
      variant: String(raw.variant || 'info'),
      prompt_mode: ['token', 'append', 'off'].indexOf(raw.prompt_mode) > -1 ? raw.prompt_mode : 'token',
      prompt_wrap: String(raw.prompt_wrap || ''),
      show_if: {
        field: String((raw.show_if && raw.show_if.field) || ''),
        op: String((raw.show_if && raw.show_if.op) || 'eq'),
        value: String((raw.show_if && raw.show_if.value) || ''),
      },
      options: opts,
      _open: false,
      _autoId: !raw.field_id,
    };
  }

  function newField(type) {
    var t = typeOf(type);
    var presets = t.presets || {};
    var f = normalizeField({ type: type });
    f.type = type;
    f.label_fa = type === 'divider' ? 'جداکننده' : String(presets.label_fa || '');
    f.field_id = uniqueId(type);
    f._autoId = true;
    f._open = true;
    if (presets.options) f.options = normalizeOptions(presets.options);
    if (presets.min != null) f.min = String(presets.min);
    if (presets.max != null) f.max = String(presets.max);
    if (presets.step != null) f.step = String(presets.step);
    if (presets.default != null) f.default = String(presets.default);
    if (presets.required != null) f.required = String(presets.required) === '1' ? '1' : '0';
    if (presets.prompt_mode) f.prompt_mode = String(presets.prompt_mode);
    if (presets.unit) f.unit = presets.unit;
    return f;
  }

  /* ═══════════════ رندر ═══════════════ */

  var listEl, emptyEl, addBottomEl, countEl;

  function renderAll() {
    if (!listEl) return;
    listEl.innerHTML = '';
    SB.fields.forEach(function (f, i) { listEl.appendChild(buildCard(f, i)); });
    var has = SB.fields.length > 0;
    if (emptyEl) emptyEl.style.display = has ? 'none' : '';
    if (addBottomEl) addBottomEl.style.display = has ? 'inline-flex' : 'none';
    if (countEl) {
      countEl.style.display = has ? 'inline-flex' : 'none';
      countEl.textContent = toFaNum(SB.fields.length) + ' ویژگی';
    }
    validateAll();
    renderLivePreview();
    if (typeof window.renderStepper === 'function') { try { window.renderStepper(); } catch (e) {} }
  }

  window.sbAddTemplate = function (key) {
    var template = TEMPLATES[key];
    if (!template || !template.field) return;
    var base = newField(template.field.type || 'text');
    var field = normalizeField(Object.assign({}, base, template.field));
    if (!field.field_id) field.field_id = uniqueId(field.type);
    else field.field_id = uniqueId(field.field_id);
    field._autoId = false;
    field._open = true;
    SB.fields.push(field);
    renderAll();
    var cards = document.querySelectorAll('#input-fields-list .sb-card');
    if (cards.length) cards[cards.length - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  function renderLivePreview() {
    var root = document.getElementById('sb-live-preview');
    if (!root) return;
    if (!SB.fields.length) {
      root.innerHTML = '<div class="sb-preview-empty"><i class="fa-solid fa-arrow-up"></i><span>یکی از نمونه‌های بالا را انتخاب کنید تا فرم واقعی کاربر اینجا نمایش داده شود.</span></div>';
      return;
    }
    root.innerHTML = SB.fields.map(function (field, index) {
      if (field.hidden === '1') return '';
      if (field.type === 'divider') return '<hr class="sb-preview-divider">';
      if (field.type === 'section') return '<div class="sb-preview-section">' + esc(field.label_fa || 'عنوان بخش') + '</div>';
      if (field.type === 'info') return '<div class="sb-preview-info"><i class="fa-solid fa-circle-info"></i>' + esc(field.description || field.label_fa || 'راهنمای این بخش') + '</div>';
      var id = 'sb-preview-' + index;
      var label = '<label for="' + id + '" class="sb-preview-label">' + esc(field.label_fa || 'عنوان ویژگی') + (field.required === '1' ? '<b>*</b>' : '') + '</label>';
      var control = previewControl(field, id, index);
      return '<div class="sb-preview-field">' + label + control +
        (field.description ? '<small>' + esc(field.description) + '</small>' : '') + '</div>';
    }).join('');
  }

  function previewControl(field, id, index) {
    var opts = field.options || [];
    if (field.type === 'textarea' || field.type === 'prompt' || field.type === 'negative_prompt') {
      return '<textarea id="' + id + '" placeholder="' + esc(field.placeholder || 'بنویسید…') + '"></textarea>';
    }
    if (field.type === 'select') {
      return '<select id="' + id + '"><option>انتخاب کنید</option>' + opts.map(function (o) { return '<option>' + esc(o.label || o.value) + '</option>'; }).join('') + '</select>';
    }
    if (['radio', 'gender', 'button_group', 'style_preset', 'aspect_ratio', 'resolution'].indexOf(field.type) > -1) {
      return '<div class="sb-preview-options">' + opts.map(function (o, i) {
        return '<label><input type="radio" name="sb-preview-radio-' + index + '"' + (i === 0 ? ' checked' : '') + '><span>' + esc(o.label || o.value || 'گزینه') + '</span></label>';
      }).join('') + '</div>';
    }
    if (field.type === 'multi_select') {
      return '<div class="sb-preview-options">' + opts.map(function (o) {
        return '<label><input type="checkbox"><span>' + esc(o.label || o.value || 'گزینه') + '</span></label>';
      }).join('') + '</div>';
    }
    if (field.type === 'switch' || field.type === 'checkbox') {
      return '<label class="sb-preview-switch"><input type="checkbox"><span></span><em>فعال</em></label>';
    }
    if (['image_upload', 'multi_image', 'file_upload'].indexOf(field.type) > -1) {
      return '<label class="sb-preview-upload" for="' + id + '"><i class="fa-solid fa-cloud-arrow-up"></i><span>برای انتخاب فایل کلیک کنید</span><input id="' + id + '" type="file"' + (field.type === 'multi_image' ? ' multiple' : '') + '></label>';
    }
    if (field.type === 'slider' || field.type === 'strength') {
      return '<input id="' + id + '" type="range" min="' + esc(field.min || 0) + '" max="' + esc(field.max || 100) + '" value="' + esc(field.default || 50) + '">';
    }
    if (field.type === 'color') return '<input id="' + id + '" type="color" value="' + esc(field.default || '#16594f') + '">';
    return '<input id="' + id + '" type="' + (field.type === 'number' || field.type === 'seed' ? 'number' : 'text') + '" placeholder="' + esc(field.placeholder || 'بنویسید…') + '" value="' + esc(field.default || '') + '">';
  }

  /* نام input بر اساس ایندکس فیلد */
  function nm(i, key) { return 'input_schema[' + i + '][' + key + ']'; }

  function helpBtn(key, title) {
    var text = (CFG.help || {})[key];
    if (!text) return '';
    return '<span class="field-help-btn sb-field-help" role="button" tabindex="0" data-help-title="' + esc(title) + '" data-help-text="' + esc(text) + '" aria-label="راهنمایی ' + esc(title) + '"><i class="fa-solid fa-circle-question"></i></span>';
  }

  function buildCard(f, i) {
    var t = typeOf(f.type);
    var c = caps(f);
    var card = document.createElement('div');
    card.className = 'sb-card input-schema-row' + (f._open ? ' sb-open' : '') + (f.hidden === '1' ? ' sb-is-hidden-field' : '');
    card.dataset.sbIndex = i;

    var showLabel = f.type !== 'divider';

    card.innerHTML =
      /* ── ورودی‌های مخفی (نوع/اجباری/مخفی/ترتیب + سازگاری قدیمی) ── */
      '<input type="hidden" class="schema-type" name="' + nm(i, 'type') + '" value="' + esc(f.type) + '">' +
      '<input type="hidden" class="schema-required" name="' + nm(i, 'required') + '" value="' + esc(f.required) + '">' +
      '<input type="hidden" name="' + nm(i, 'hidden') + '" value="' + esc(f.hidden) + '">' +
      '<input type="hidden" name="' + nm(i, 'order') + '" value="' + i + '">' +
      /* ── هدر کارت ── */
      '<div class="sb-card-head">' +
        '<i class="fa-solid fa-grip-vertical sb-grip" title="برای تغییر ترتیب بکشید"></i>' +
        '<button type="button" class="sb-type-chip" data-sb="change-type" title="تغییر نوع ویژگی — ' + esc(t.control || '') + '">' +
          '<i class="fa-solid ' + esc(t.icon) + '"></i> ' + esc(t.label) + ' <i class="fa-solid fa-chevron-down sb-type-caret"></i>' +
        '</button>' +
        '<div class="sb-head-inputs">' +
          (showLabel
            ? '<span style="color:var(--danger);font-weight:800;" title="اجباری">*</span>' + helpBtn('label_fa', 'عنوان ویژگی') + '<input type="text" required class="sb-input sb-input-label schema-label" name="' + nm(i, 'label_fa') + '" value="' + esc(f.label_fa) + '" placeholder="عنوان ویژگی (مثلاً سبک تصویر)" data-k="label_fa">'
            : '<input type="hidden" class="schema-label" name="' + nm(i, 'label_fa') + '" value="' + esc(f.label_fa) + '">') +
          (showLabel
            ? '<span style="color:var(--danger);font-weight:800;" title="اجباری">*</span>' + helpBtn('field_id', 'شناسه فیلد') + '<input type="text" required class="sb-input sb-input-id schema-id" name="' + nm(i, 'field_id') + '" value="' + esc(f.field_id) + '" placeholder="field_id" data-k="field_id" title="شناسه فیلد — همین شناسه به‌صورت {' + esc(f.field_id) + '} در پرامپت استفاده می‌شود">'
            : '<input type="hidden" class="schema-id" name="' + nm(i, 'field_id') + '" value="' + esc(f.field_id) + '">') +
        '</div>' +
        (!isLayout(f)
          ? '<button type="button" class="sb-flag' + (f.required === '1' ? ' sb-on-req' : '') + '" data-sb="toggle-required" title="آیا پر کردن این ویژگی برای کاربر اجباری باشد؟">' +
              '<i class="fa-solid ' + (f.required === '1' ? 'fa-asterisk' : 'fa-circle-minus') + '"></i>' + (f.required === '1' ? 'اجباری' : 'اختیاری') +
            '</button>'
          : '') +
        '<button type="button" class="sb-flag' + (f.hidden === '1' ? ' sb-on-hid' : '') + '" data-sb="toggle-hidden" title="فیلد مخفی به کاربر نمایش داده نمی‌شود ولی مقدار پیش‌فرضش به هوش مصنوعی ارسال می‌شود">' +
          '<i class="fa-solid ' + (f.hidden === '1' ? 'fa-eye-slash' : 'fa-eye') + '"></i>' + (f.hidden === '1' ? 'مخفی' : 'نمایان') +
        '</button>' +
        '<div class="sb-actions">' +
          '<button type="button" class="sb-act" data-sb="move-up" title="جابه‌جایی به بالا"><i class="fa-solid fa-caret-up"></i></button>' +
          '<button type="button" class="sb-act" data-sb="move-down" title="جابه‌جایی به پایین"><i class="fa-solid fa-caret-down"></i></button>' +
          '<button type="button" class="sb-act" data-sb="duplicate" title="تکثیر ویژگی"><i class="fa-solid fa-copy"></i></button>' +
          '<button type="button" class="sb-act" data-sb="toggle-open" title="تنظیمات"><i class="fa-solid fa-chevron-down sb-chev"></i></button>' +
          '<button type="button" class="sb-act sb-act-del" data-sb="remove" title="حذف ویژگی"><i class="fa-solid fa-trash-can"></i></button>' +
        '</div>' +
      '</div>' +

      '<div class="sb-warn" data-sb-warn style="display:none"></div>' +
      '<div class="sb-summary" data-sb-summary></div>' +

      /* ── بدنه تنظیمات ── */
      '<div class="sb-body">' + buildBody(f, i) + '</div>';

    wireCard(card, i);
    refreshSummary(card, i);
    return card;
  }

  /* ── بدنه: بخش‌های تنظیمات بر اساس قابلیت‌های نوع ── */
  function buildBody(f, i) {
    var c = caps(f);
    var html = '';

    /* بخش عمومی */
    var general = '';
    general += fld('توضیح برای کاربر', '<input type="text" class="sb-input" name="' + nm(i, 'description') + '" value="' + esc(f.description) + '" placeholder="زیر عنوان فیلد به کاربر نمایش داده می‌شود (اختیاری)" data-k="description">', 'sb-col-span', '', '', 'description');
    if (c.placeholder) {
      general += fld('Placeholder', '<input type="text" class="sb-input" name="' + nm(i, 'placeholder') + '" value="' + esc(f.placeholder) + '" placeholder="متن داخل فیلد خالی" data-k="placeholder">', '', '', '', 'placeholder');
    }
    if (c.default) general += fld('مقدار پیش‌فرض', defaultInput(f, i), '', 'data-sb-default-wrap', '', 'default');
    if (!isLayout(f)) {
      general += fld('کردیت اضافه', '<input type="number" class="sb-input sb-ltr" name="' + nm(i, 'credit_cost') + '" value="' + esc(f.credit_cost) + '" min="0" placeholder="0" data-k="credit_cost">', '', '', 'اگر کاربر این ویژگی را پر/فعال کند، این مقدار به هزینه ساخت اضافه می‌شود (خالی = بدون هزینه)', 'credit_cost');
    }
    if (f.type === 'info') {
      general += fld('حالت نمایش پیام', selectHtml(i, 'variant', f.variant, [['info', 'اطلاع‌رسانی'], ['warning', 'هشدار'], ['success', 'موفقیت']]));
    }
    html += sec('fa-sliders', 'تنظیمات عمومی', '<div class="sb-grid">' + general + '</div>');

    /* گزینه‌ها */
    if (c.options) html += sec('fa-list-ul', 'گزینه‌ها', buildOptions(f, i), 'data-sb-opts-sec');

    /* اعتبارسنجی */
    if (c.validation) {
      var v = '';
      if (c.validation === 'text') {
        v += fld('حداقل کاراکتر', numIn(i, 'min', f.min));
        v += fld('حداکثر کاراکتر', numIn(i, 'max', f.max));
        v += fld('الگوی Regex', '<input type="text" class="sb-input sb-ltr" name="' + nm(i, 'regex') + '" value="' + esc(f.regex) + '" placeholder="مثلاً ^[a-z]+$" data-k="regex">');
      } else if (c.validation === 'number') {
        v += fld(f.type === 'multi_select' ? 'حداقل تعداد انتخاب' : 'حداقل مقدار', numIn(i, 'min', f.min));
        v += fld(f.type === 'multi_select' ? 'حداکثر تعداد انتخاب' : 'حداکثر مقدار', numIn(i, 'max', f.max));
        if (f.type === 'number') v += fld('گام (Step)', numIn(i, 'step', f.step));
      } else if (c.validation === 'range') {
        v += fld('حداقل بازه', numIn(i, 'min', f.min));
        v += fld('حداکثر بازه', numIn(i, 'max', f.max));
        v += fld('گام (Step)', numIn(i, 'step', f.step));
        v += fld('واحد نمایش', '<input type="text" class="sb-input" name="' + nm(i, 'unit') + '" value="' + esc(f.unit) + '" placeholder="مثلاً ٪ یا px" data-k="unit">');
      } else if (c.validation === 'files') {
        v += fld('حداکثر تعداد فایل', numIn(i, 'max_files', f.max_files));
        v += fld('حداکثر حجم هر فایل (MB)', numIn(i, 'max_size_mb', f.max_size_mb));
        v += fld('فرمت‌های مجاز (accept)', '<input type="text" class="sb-input sb-ltr" name="' + nm(i, 'accept') + '" value="' + esc(f.accept) + '" placeholder="image/*" data-k="accept">', '', '', 'مثل image/* یا .pdf,.zip — خالی یعنی همه فرمت‌ها');
      }
      html += sec('fa-shield-halved', 'اعتبارسنجی', '<div class="sb-grid-3">' + v + '</div>');
    }

    /* شرط نمایش */
    html += sec('fa-code-branch', 'شرط نمایش (اختیاری)',
      '<div class="sb-grid-3">' +
        fld('وابسته به ویژگی', condFieldSelect(f, i)) +
        fld('شرط', selectHtml(i, 'show_if.op', f.show_if.op, [['eq', 'برابر باشد با'], ['neq', 'برابر نباشد با'], ['has', 'شامل باشد'], ['not_empty', 'پر شده باشد']], 'data-k="show_if.op"')) +
        fld('مقدار', '<input type="text" class="sb-input" name="' + nm(i, 'show_if') + '[value]" value="' + esc(f.show_if.value) + '" placeholder="مقدار مقایسه" data-k="show_if.value"' + (f.show_if.op === 'not_empty' ? ' disabled' : '') + '>') +
      '</div>' +
      '<div class="sb-fld-hint" style="margin-top:6px">این ویژگی فقط زمانی به کاربر نمایش داده می‌شود که شرط بالا برقرار باشد — «بدون شرط» یعنی همیشه نمایان.</div>');

    /* تاثیر در پرامپت */
    if (caps(f).promptable) {
      html += sec('fa-wand-magic-sparkles', 'تاثیر در پرامپت هوش مصنوعی',
        '<div class="sb-grid">' +
          fld('نحوه تاثیر', selectHtml(i, 'prompt_mode', f.prompt_mode, [
            ['token', 'جایگذاری توکن در قالب پرامپت'],
            ['append', 'الحاق خودکار به انتهای پرامپت'],
            ['off', 'بدون تاثیر مستقیم در پرامپت'],
          ], 'data-k="prompt_mode"')) +
          fld('قالب مقدار (اختیاری)', '<input type="text" class="sb-input sb-ltr" name="' + nm(i, 'prompt_wrap') + '" value="' + esc(f.prompt_wrap) + '" placeholder="wearing {value} clothes" data-k="prompt_wrap">', '', '', 'مقدار کاربر به‌جای {value} می‌نشیند تا جمله‌بندی پرامپت طبیعی و دقیق شود') +
        '</div>' +
        '<div style="display:flex;align-items:center;gap:8px;margin-top:9px;flex-wrap:wrap">' +
          '<span class="sb-token" data-sb-token>{' + esc(f.field_id) + '}' +
            '<button type="button" class="sb-token-btn" data-sb="copy-token" title="کپی توکن"><i class="fa-regular fa-copy"></i></button>' +
            '<button type="button" class="sb-token-btn" data-sb="insert-token" title="درج توکن در قالب پرامپت (گام ۲)"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>' +
          '</span>' +
          '<span class="sb-fld-hint">در حالت «توکن»، این عبارت را در قالب پرامپت گام ۲ قرار دهید تا مقدار انتخابی کاربر دقیقاً همان‌جا بنشیند.</span>' +
        '</div>');
    }

    return html;
  }

  function sec(icon, title, body, extraAttr) {
    var helpKeys = {'گزینه‌ها': 'options', 'اعتبارسنجی': 'validation', 'شرط نمایش (اختیاری)': 'show_if', 'تاثیر در پرامپت هوش مصنوعی': 'prompt_mode'};
    return '<div class="sb-sec" ' + (extraAttr || '') + '><div class="sb-sec-title"><i class="fa-solid ' + icon + '"></i>' + title + helpBtn(helpKeys[title] || '', title) + '</div>' + body + '</div>';
  }
  function fld(label, control, cls, attr, hint, helpKey) {
    return '<div class="sb-fld ' + (cls || '') + '" ' + (attr || '') + '><label class="sb-fld-label">' + label + helpBtn(helpKey || '', label) + '</label>' + control + (hint ? '<div class="sb-fld-hint">' + hint + '</div>' : '') + '</div>';
  }
  function numIn(i, key, val) {
    return '<input type="number" class="sb-input sb-ltr" name="' + nm(i, key) + '" value="' + esc(val) + '" data-k="' + key + '">';
  }
  function selectHtml(i, key, current, pairs, dataAttr) {
    var name = key.indexOf('.') > -1 ? nm(i, key.split('.')[0]) + '[' + key.split('.')[1] + ']' : nm(i, key);
    var h = '<select class="sb-select" name="' + name + '" ' + (dataAttr || 'data-k="' + key + '"') + '>';
    pairs.forEach(function (p) { h += '<option value="' + esc(p[0]) + '"' + (String(current) === p[0] ? ' selected' : '') + '>' + esc(p[1]) + '</option>'; });
    return h + '</select>';
  }

  /* ورودی «مقدار پیش‌فرض» بر اساس نوع */
  function defaultInput(f, i) {
    var kind = caps(f).default;
    if (kind === 'bool') return selectHtml(i, 'default', f.default === '1' ? '1' : '0', [['0', 'خاموش'], ['1', 'روشن']], 'data-k="default"');
    if (kind === 'color') return '<input type="color" class="sb-input" style="height:34px;padding:3px" name="' + nm(i, 'default') + '" value="' + esc(/^#[0-9a-fA-F]{6}$/.test(f.default) ? f.default : '#16594f') + '" data-k="default">';
    if (kind === 'number') return numIn(i, 'default', f.default);
    if (kind === 'option') {
      var h = '<select class="sb-select" name="' + nm(i, 'default') + '" data-k="default" data-sb-default-select><option value="">— بدون پیش‌فرض —</option>';
      (f.options || []).forEach(function (o) {
        h += '<option value="' + esc(o.value) + '"' + (f.default === o.value ? ' selected' : '') + '>' + esc(o.label || o.value) + '</option>';
      });
      return h + '</select>';
    }
    return '<input type="text" class="sb-input" name="' + nm(i, 'default') + '" value="' + esc(f.default) + '" data-k="default">';
  }

  /* انتخاب فیلد مرجع برای شرط نمایش */
  function condFieldSelect(f, i) {
    var h = '<select class="sb-select" name="' + nm(i, 'show_if') + '[field]" data-k="show_if.field" data-sb-cond-select><option value="">— بدون شرط —</option>';
    SB.fields.forEach(function (other) {
      if (other === f || isLayout(other) || !other.field_id) return;
      h += '<option value="' + esc(other.field_id) + '"' + (f.show_if.field === other.field_id ? ' selected' : '') + '>' + esc(other.label_fa || other.field_id) + '</option>';
    });
    return h + '</select>';
  }

  /* ── ویرایشگر گزینه‌ها ── */
  function optCols(f) {
    var c = caps(f);
    var cols = ['18px', '1.2fr', '1fr']; // grip، عنوان، مقدار
    var heads = ['', 'عنوان نمایش (فارسی) *', 'مقدار / کلید (EN) *'];
    if (c.opt_prompt) { cols.push('1.4fr'); heads.push('متن پرامپت این گزینه (EN)'); }
    if (c.opt_credit) { cols.push('64px'); heads.push('کردیت+'); }
    if (c.opt_image) { cols.push('1fr'); heads.push('آدرس تصویر'); }
    cols.push('26px'); heads.push('');
    return { style: 'grid-template-columns:' + cols.join(' '), heads: heads };
  }

  function buildOptions(f, i) {
    var c = caps(f);
    var g = optCols(f);
    var h = '<div class="sb-opts" data-sb-opts>';
    h += '<div class="sb-opt-head" style="' + g.style + '">' + g.heads.map(function (t) { return '<span>' + t + '</span>'; }).join('') + '</div>';
    (f.options || []).forEach(function (o, j) {
      h += '<div class="sb-opt-row" style="' + g.style + '" data-opt="' + j + '">' +
        '<i class="fa-solid fa-grip-vertical sb-opt-grip" title="ترتیب گزینه‌ها"></i>' +
        '<input type="text" class="sb-input" name="' + nm(i, 'options') + '[' + j + '][label]" value="' + esc(o.label) + '" placeholder="مثلاً کلاسیک" data-ok="label">' +
        '<input type="text" class="sb-input sb-ltr" name="' + nm(i, 'options') + '[' + j + '][value]" value="' + esc(o.value) + '" placeholder="classic" data-ok="value">' +
        (c.opt_prompt ? '<input type="text" class="sb-input sb-ltr" name="' + nm(i, 'options') + '[' + j + '][prompt]" value="' + esc(o.prompt) + '" placeholder="classic vintage style, film grain" data-ok="prompt" title="این متن انگلیسی هنگام انتخاب این گزینه وارد پرامپت می‌شود — کلید کیفیت خروجی">' : '') +
        (c.opt_credit ? '<input type="number" class="sb-input sb-ltr" name="' + nm(i, 'options') + '[' + j + '][credit]" value="' + esc(o.credit) + '" min="0" placeholder="0" data-ok="credit" title="کردیت اضافه این گزینه">' : '') +
        (c.opt_image ? '<input type="text" class="sb-input sb-ltr" name="' + nm(i, 'options') + '[' + j + '][image]" value="' + esc(o.image) + '" placeholder="/storage/... یا https://" data-ok="image">' : '') +
        '<button type="button" class="sb-opt-del" data-sb="opt-remove" title="حذف گزینه"><i class="fa-solid fa-xmark"></i></button>' +
      '</div>';
    });
    h += '</div>';
    h += '<button type="button" class="sb-btn-dashed" style="margin-top:8px;padding:6px 12px;font-size:11px" data-sb="opt-add"><i class="fa-solid fa-plus"></i> افزودن گزینه</button>';
    if (c.opt_prompt) {
      h += '<div class="sb-fld-hint" style="margin-top:7px">ستون «متن پرامپت» مهم‌ترین بخش کیفیت خروجی است: به‌جای برچسب فارسی، همین توضیح دقیق انگلیسی وارد پرامپت نهایی می‌شود.</div>';
    }
    return h;
  }

  /* ═══════════════ رویدادها ═══════════════ */

  function setDeep(f, key, val) {
    if (key === 'show_if.field') f.show_if.field = val;
    else if (key === 'show_if.op') f.show_if.op = val;
    else if (key === 'show_if.value') f.show_if.value = val;
    else f[key] = val;
  }

  function wireCard(card, i) {
    var f = SB.fields[i];

    /* دکمه‌ها (Delegation داخل کارت) */
    card.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-sb]');
      if (!btn || !card.contains(btn)) return;
      var act = btn.dataset.sb;
      if (act === 'toggle-open') { f._open = !f._open; card.classList.toggle('sb-open', f._open); refreshSummary(card, i); }
      else if (act === 'remove') { SB.fields.splice(i, 1); renderAll(); }
      else if (act === 'duplicate') {
        var copy = JSON.parse(JSON.stringify(f));
        copy.field_id = uniqueId(copy.field_id); copy._open = false;
        SB.fields.splice(i + 1, 0, copy); renderAll();
      }
      else if (act === 'move-up' && i > 0) { SB.fields.splice(i - 1, 0, SB.fields.splice(i, 1)[0]); renderAll(); }
      else if (act === 'move-down' && i < SB.fields.length - 1) { SB.fields.splice(i + 1, 0, SB.fields.splice(i, 1)[0]); renderAll(); }
      else if (act === 'change-type') { sbOpenLibrary(i); }
      else if (act === 'toggle-required') { f.required = f.required === '1' ? '0' : '1'; renderAll(); }
      else if (act === 'toggle-hidden') { f.hidden = f.hidden === '1' ? '0' : '1'; renderAll(); }
      else if (act === 'opt-add') { f.options.push({ value: '', label: '', prompt: '', credit: '', image: '' }); rerenderBody(card, i); }
      else if (act === 'opt-remove') {
        var row = btn.closest('[data-opt]');
        if (row) { f.options.splice(parseInt(row.dataset.opt, 10), 1); rerenderBody(card, i); }
      }
      else if (act === 'copy-token') { copyText('{' + f.field_id + '}', btn); }
      else if (act === 'insert-token') {
        if (typeof window.insertVar === 'function' && document.getElementById('prompt-template')) {
          window.insertVar('{' + f.field_id + '}');
          if (typeof window.goStep === 'function') window.goStep(2);
        } else copyText('{' + f.field_id + '}', btn);
      }
    });

    /* ورودی‌های تنظیمات فیلد */
    card.addEventListener('input', function (e) { onCardInput(card, i, e); });
    card.addEventListener('change', function (e) { onCardInput(card, i, e); });

    /* درگ‌ودراپ ترتیب کارت‌ها — فقط از دستگیره */
    var grip = card.querySelector('.sb-grip');
    if (grip) {
      grip.addEventListener('mousedown', function () { card.draggable = true; });
      card.addEventListener('mouseup', function () { card.draggable = false; });
      card.addEventListener('dragstart', function () { SB._drag = i; card.classList.add('sb-dragging'); });
      card.addEventListener('dragend', function () { card.classList.remove('sb-dragging'); card.draggable = false; });
      card.addEventListener('dragover', function (e) { e.preventDefault(); });
      card.addEventListener('drop', function (e) {
        e.preventDefault();
        if (SB._drag == null || SB._drag === i) return;
        var moved = SB.fields.splice(SB._drag, 1)[0];
        SB.fields.splice(i, 0, moved);
        SB._drag = null;
        renderAll();
      });
    }
  }

  function onCardInput(card, i, e) {
    var f = SB.fields[i];
    var el = e.target;

    /* گزینه‌ها */
    if (el.dataset.ok) {
      var row = el.closest('[data-opt]');
      if (!row) return;
      var j = parseInt(row.dataset.opt, 10);
      if (!f.options[j]) return;
      f.options[j][el.dataset.ok] = el.value;
      /* مقدار EN خودکار از روی عنوان (تا وقتی دستی ویرایش نشده) */
      if (el.dataset.ok === 'label') {
        var valIn = row.querySelector('[data-ok="value"]');
        if (valIn && (valIn.dataset.touched !== '1')) {
          var auto = el.value.trim();
          if (/^[\x00-\x7F]*$/.test(auto)) { valIn.value = auto.toLowerCase().replace(/\s+/g, '_'); f.options[j].value = valIn.value; }
        }
      }
      if (el.dataset.ok === 'value') el.dataset.touched = '1';
      syncDefaultSelect(card, f);
      refreshSummary(card, i);
      return;
    }

    var k = el.dataset.k;
    if (!k) return;
    var val = el.value;

    if (k === 'field_id') {
      val = sanitizeId(val);
      el.value = val;
      f._autoId = false;
      f.field_id = val;
      syncTokens(card, f);
      refreshCondSelects();
      validateAll();
      return;
    }
    if (k === 'label_fa') {
      f.label_fa = val;
      /* پیشنهاد خودکار شناسه از روی عنوان انگلیسی (تا وقتی مدیر دستی ننوشته) */
      if (f._autoId && /^[\x00-\x7F]+$/.test(val.trim()) && val.trim() !== '') {
        var idIn = card.querySelector('.schema-id');
        f.field_id = uniqueId(val);
        if (idIn) idIn.value = f.field_id;
        syncTokens(card, f);
      }
      refreshCondSelects();
      refreshSummary(card, i);
      validateAll();
      return;
    }
    if (k === 'show_if.op') {
      f.show_if.op = val;
      var vIn = card.querySelector('[data-k="show_if.value"]');
      if (vIn) vIn.disabled = (val === 'not_empty');
      refreshSummary(card, i);
      return;
    }

    setDeep(f, k, val);
    if (k === 'description') {
      var mirror = card.querySelector('input[name="' + nm(i, 'help_text') + '"]');
      if (mirror) mirror.value = val;
    }
    refreshSummary(card, i);
  }

  /* رندر مجدد فقط بدنه یک کارت (بعد از تغییر ساختار گزینه‌ها) */
  function rerenderBody(card, i) {
    var f = SB.fields[i];
    var body = card.querySelector('.sb-body');
    if (body) body.innerHTML = buildBody(f, i);
    refreshSummary(card, i);
  }

  function syncDefaultSelect(card, f) {
    var sel = card.querySelector('[data-sb-default-select]');
    if (!sel) return;
    var cur = f.default;
    var h = '<option value="">— بدون پیش‌فرض —</option>';
    (f.options || []).forEach(function (o) {
      h += '<option value="' + esc(o.value) + '"' + (cur === o.value ? ' selected' : '') + '>' + esc(o.label || o.value) + '</option>';
    });
    sel.innerHTML = h;
  }

  function syncTokens(card, f) {
    var tok = card.querySelector('[data-sb-token]');
    if (tok) tok.firstChild.nodeValue = '{' + f.field_id + '}';
    var idIn = card.querySelector('.sb-input-id');
    if (idIn) idIn.title = 'شناسه فیلد — همین شناسه به‌صورت {' + f.field_id + '} در پرامپت استفاده می‌شود';
  }

  /* Selectهای شرط نمایش همه کارت‌ها را با لیست جدید فیلدها تازه می‌کند */
  function refreshCondSelects() {
    document.querySelectorAll('#input-fields-list .sb-card').forEach(function (card) {
      var i = parseInt(card.dataset.sbIndex, 10);
      var f = SB.fields[i];
      if (!f) return;
      var sel = card.querySelector('[data-sb-cond-select]');
      if (!sel) return;
      var cur = f.show_if.field;
      var h = '<option value="">— بدون شرط —</option>';
      SB.fields.forEach(function (other) {
        if (other === f || isLayout(other) || !other.field_id) return;
        h += '<option value="' + esc(other.field_id) + '"' + (cur === other.field_id ? ' selected' : '') + '>' + esc(other.label_fa || other.field_id) + '</option>';
      });
      sel.innerHTML = h;
    });
  }

  /* ── چیپ‌های خلاصه هر کارت ── */
  function refreshSummary(card, i) {
    var f = SB.fields[i];
    var box = card.querySelector('[data-sb-summary]');
    if (!box) return;
    var t = typeOf(f.type);
    var chips = [];
    chips.push('<span class="sb-mini"><i class="fa-solid ' + esc(t.icon) + '"></i>' + esc(t.control || t.label) + '</span>');
    if (!isLayout(f)) chips.push(f.required === '1'
      ? '<span class="sb-mini sb-mini-req"><i class="fa-solid fa-asterisk"></i>اجباری</span>'
      : '<span class="sb-mini"><i class="fa-solid fa-circle-minus"></i>اختیاری</span>');
    if (f.hidden === '1') chips.push('<span class="sb-mini"><i class="fa-solid fa-eye-slash"></i>مخفی</span>');
    if (caps(f).options) chips.push('<span class="sb-mini"><i class="fa-solid fa-list-ul"></i>' + toFaNum((f.options || []).length) + ' گزینه</span>');
    if (f.show_if.field) chips.push('<span class="sb-mini sb-mini-cond"><i class="fa-solid fa-code-branch"></i>شرطی: ' + esc(f.show_if.field) + '</span>');
    var credit = parseInt(f.credit_cost, 10);
    if (credit > 0) chips.push('<span class="sb-mini sb-mini-credit"><i class="fa-solid fa-coins"></i>+' + toFaNum(credit) + ' کردیت</span>');
    if (f.default !== '' && caps(f).default) chips.push('<span class="sb-mini"><i class="fa-solid fa-wand-sparkles"></i>پیش‌فرض: ' + esc(f.default) + '</span>');
    box.innerHTML = chips.join('');
  }

  /* ── اعتبارسنجی سازنده (شناسه تکراری/خالی) ── */
  function validateAll() {
    var seen = {};
    SB.fields.forEach(function (f) { seen[f.field_id] = (seen[f.field_id] || 0) + 1; });
    document.querySelectorAll('#input-fields-list .sb-card').forEach(function (card) {
      var i = parseInt(card.dataset.sbIndex, 10);
      var f = SB.fields[i];
      if (!f) return;
      var warn = card.querySelector('[data-sb-warn]');
      var msg = '';
      if (!f.field_id) msg = 'شناسه فیلد (field_id) خالی است — این ویژگی ذخیره نمی‌شود.';
      else if (seen[f.field_id] > 1) msg = 'شناسه «' + esc(f.field_id) + '» تکراری است — شناسه هر ویژگی باید یکتا باشد.';
      else if (!isLayout(f) && !f.label_fa) msg = 'عنوان ویژگی خالی است — کاربر برچسبی برای این فیلد نمی‌بیند.';
      card.classList.toggle('sb-invalid', !!msg);
      if (warn) {
        warn.style.display = msg ? 'flex' : 'none';
        warn.innerHTML = msg ? '<i class="fa-solid fa-triangle-exclamation"></i>' + msg : '';
      }
    });
  }

  function copyText(text, btn) {
    try {
      navigator.clipboard.writeText(text);
      if (btn) {
        var icon = btn.querySelector('i');
        if (icon) { var old = icon.className; icon.className = 'fa-solid fa-check'; setTimeout(function () { icon.className = old; }, 1200); }
      }
    } catch (e) {}
  }

  /* ═══════════════ کتابخانه انواع ═══════════════ */

  window.sbOpenLibrary = function (changeIndex) {
    SB.libTarget = (typeof changeIndex === 'number') ? changeIndex : null;
    var overlay = document.getElementById('sb-library-overlay');
    var search = document.getElementById('sb-library-search');
    var title = document.getElementById('sb-library-title');
    if (title) title.textContent = SB.libTarget == null ? 'انتخاب نوع ویژگی' : 'تغییر نوع ویژگی';
    if (search) search.value = '';
    window.sbRenderLibrary('');
    if (overlay) overlay.style.display = 'flex';
    if (search) setTimeout(function () { search.focus(); }, 60);
  };

  window.sbCloseLibrary = function () {
    var overlay = document.getElementById('sb-library-overlay');
    if (overlay) overlay.style.display = 'none';
    SB.libTarget = null;
  };

  window.sbRenderLibrary = function (filter) {
    var body = document.getElementById('sb-library-body');
    if (!body) return;
    filter = (filter || '').trim().toLowerCase();
    var html = '';
    Object.keys(GROUPS).forEach(function (gk) {
      var items = Object.keys(TYPES).filter(function (tk) {
        var t = TYPES[tk];
        if (t.group !== gk) return false;
        if (!filter) return true;
        return (t.label + ' ' + (t.desc || '') + ' ' + tk).toLowerCase().indexOf(filter) > -1;
      });
      if (!items.length) return;
      html += '<div class="sb-lib-group-title"><i class="fa-solid ' + esc(GROUPS[gk].icon || 'fa-folder') + '"></i>' + esc(GROUPS[gk].label) + '</div>';
      html += '<div class="sb-lib-grid">';
      items.forEach(function (tk) {
        var t = TYPES[tk];
        html += '<button type="button" class="sb-lib-item" onclick="sbPickType(\'' + tk + '\')">' +
          '<span class="sb-lib-item-head"><span class="sb-lib-item-icon"><i class="fa-solid ' + esc(t.icon) + '"></i></span><span class="sb-lib-item-name">' + esc(t.label) + '</span></span>' +
          '<span class="sb-lib-item-desc">' + esc(t.desc || '') + '</span>' +
        '</button>';
      });
      html += '</div>';
    });
    body.innerHTML = html || '<div class="sb-lib-empty">نوعی با این جستجو پیدا نشد</div>';
  };

  window.sbPickType = function (typeKey) {
    if (SB.libTarget == null) {
      SB.fields.push(newField(typeKey));
      renderAll();
      var last = listEl && listEl.lastElementChild;
      if (last) last.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      var f = SB.fields[SB.libTarget];
      if (f) {
        f.type = typeKey;
        var presets = typeOf(typeKey).presets || {};
        if ((typeOf(typeKey).caps || {}).options && (!f.options || !f.options.length) && presets.options) {
          f.options = normalizeOptions(presets.options);
        }
        if (presets.min != null && f.min === '') f.min = String(presets.min);
        if (presets.max != null && f.max === '') f.max = String(presets.max);
        if (presets.step != null && f.step === '') f.step = String(presets.step);
        f._open = true;
      }
      renderAll();
    }
    window.sbCloseLibrary();
  };

  /* بستن مودال با Escape */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.sbCloseLibrary();
  });

  /* ═══════════════ راه‌اندازی ═══════════════ */
  document.addEventListener('DOMContentLoaded', function () {
    listEl = document.getElementById('input-fields-list');
    emptyEl = document.getElementById('sb-empty');
    addBottomEl = document.getElementById('sb-add-bottom');
    countEl = document.getElementById('sb-count-badge');
    if (!listEl) return;
    SB.fields = (Array.isArray(CFG.initial) ? CFG.initial : []).map(normalizeField);
    renderAll();
    listEl.addEventListener('input', function () { setTimeout(renderLivePreview, 0); });
    listEl.addEventListener('change', function () { setTimeout(renderLivePreview, 0); });
  });

})();
