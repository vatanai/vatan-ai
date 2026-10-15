/*
 * صفحه‌ی «ساخت» استودیو محصول — /app/crepro/{کد-کوتاه}
 * جریان: آپلود ۱ تا ۳ عکس در یک باکس (بررسی رایگان محلی) ← «بررسی هوشمند» اختیاری (یک درخواست ارزان)
 *        ← انتخاب صحنه‌ها ← کیفیت ← ساخت batch ← پیگیری ← نمایش نتیجه‌ها.
 * قرارداد API همان قبلی است (preflight با ai=0، preflight-set با smart=1، batches، show، run).
 * شکست یک عکس فقط همان عکس را برمی‌گرداند و دکمه‌ی «دوباره» دارد.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-product-create]');
  var cfgEl = document.getElementById('product-pack-config');
  if (!root || !cfgEl) return;

  var cfg = JSON.parse(cfgEl.textContent || '{}');
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (v) { return String(v).replace(/[0-9]/g, function (d) { return FA[d]; }); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  var reduceMotion = function () { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; };

  var MAX_PHOTOS = 1 + Math.max(0, Number(cfg.max_extra_angles || 0));
  // ۳ زاویه‌ی پیشنهادی + زاویه‌های اختیاری بیشتر (تا سقف ۶ عکس)
  var ANGLES = ['روبه‌رو', 'سه‌رخ چپ', 'سه‌رخ راست', 'پشت', 'بالا', 'جزئیات'];
  var RECOMMENDED = 3;
  var COVERAGE_FA = { front: 'روبه‌رو', three_quarter_left: 'سه‌رخ چپ', three_quarter_right: 'سه‌رخ راست', side: 'بغل', back: 'پشت', top: 'بالا', detail: 'جزئیات' };

  // توضیح کوتاه هر کیفیت (فقط راهنمای انتخاب؛ ادعای عددی ندارد)
  var QUALITY_HINT = {
    standard: 'کم‌هزینه و سریع؛ مناسب استوری و پست روزمره',
    professional: 'جزئیات و بافت بهتر؛ مناسب فروشگاه و کاتالوگ',
    best: 'بالاترین کیفیت؛ مناسب پوستر و تبلیغ اصلی'
  };
  var QUALITY_ICON = { standard: 'fa-bolt', professional: 'fa-gem', best: 'fa-crown' };

  var state = {
    uploads: [],            // [{preview, id, verdict, issues, suggestion, fixedUrl, originalUrl, useFixed, pending, error}] — اندیس ۰ = عکس اصلی
    notice: null,           // پیام کوتاه موقت (مثلاً «فقط ۳ عکس برداشته شد»)
    smart: null,            // null | {pending:true} | پاسخ بررسی هوشمند
    selected: {},           // shot_key => true
    showAll: false,
    quality: cfg.default_quality || 'standard',
    ratios: {},             // shot_key => نسبت انتخابی همان صحنه
    balance: Number(cfg.balance || 0),
    building: false,
    batch: null,
    pollTimer: null
  };

  var $ = function (sel, ctx) { return (ctx || root).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || root).querySelectorAll(sel)); };

  function request(url, options) {
    options = options || {};
    var headers = Object.assign({ 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});
    return fetch(url, Object.assign({ credentials: 'same-origin' }, options, { headers: headers }))
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) { return { status: res.status, ok: res.ok, body: body }; });
      });
  }

  /* ───── صحنه‌ها ───── */
  var shots = Array.isArray(cfg.shots) ? cfg.shots : [];
  // هیچ صحنه‌ای از پیش انتخاب نمی‌شود؛ کاربر خودش انتخاب می‌کند.
  shots.forEach(function (s) {
    state.ratios[s.key] = s.default_aspect_ratio || '4:5';
  });
  var hasHidden = shots.some(function (s) { return !s.is_default; });

  function selectedShots() { return shots.filter(function (s) { return state.selected[s.key]; }); }
  function selectedKeys() { return selectedShots().map(function (s) { return s.key; }); }
  function shotCredits(s) {
    var q = (s.quality_credits || {})[state.quality];
    return Number(q != null ? q : (s.credits || 0));
  }
  function totalCredits() { return shots.reduce(function (sum, s) { return sum + (state.selected[s.key] ? shotCredits(s) : 0); }, 0); }
  function affordableCount() {
    var remaining = state.balance;
    var count = 0;
    selectedShots()
      .sort(function (a, b) { return shotCredits(a) - shotCredits(b); })
      .some(function (s) {
        var cost = shotCredits(s);
        if (cost > remaining) return true;
        remaining -= cost;
        count += 1;
        return false;
      });
    return count;
  }

  function renderShots() {
    var wrap = $('[data-shots]');
    wrap.innerHTML = '';
    var count = selectedKeys().length;
    var max = Number(cfg.max_shots || 0);

    shots.forEach(function (s) {
      var on = !!state.selected[s.key];
      var limitReached = !on && max > 0 && count >= max;
      var allowed = Array.isArray(s.allowed_aspect_ratios) && s.allowed_aspect_ratios.length ? s.allowed_aspect_ratios : [s.default_aspect_ratio || '4:5'];

      var label = document.createElement('label');
      label.className = 'pc-shot' + (on ? ' is-selected' : '') + (limitReached ? ' is-disabled' : '');
      label.hidden = !state.showAll && !s.is_default && !on;

      var ratioControl = on && s.aspect_ratio_user_selectable && allowed.length > 1
        ? '<select class="pc-shot-ratio" aria-label="نسبت ' + esc(s.name) + '">' +
            allowed.map(function (ratio) {
              return '<option value="' + esc(ratio) + '"' + (state.ratios[s.key] === ratio ? ' selected' : '') + '>' + esc(ratio) + '</option>';
            }).join('') + '</select>'
        : '';

      label.innerHTML =
        '<input type="checkbox" ' + (on ? 'checked' : '') + (limitReached ? ' disabled' : '') + ' aria-label="' + esc(s.name) + '">' +
        '<span class="pc-shot-media">' + (s.sample_url ? '<img loading="lazy" alt="" src="' + esc(s.sample_url) + '">' : '<i class="fa-solid fa-image" aria-hidden="true"></i>') +
          '<span class="pc-shot-tick" aria-hidden="true"><i class="fa-solid fa-check pc-tick-on"></i><i class="fa-solid fa-plus pc-tick-off"></i></span>' +
        '</span>' +
        '<span class="pc-shot-body">' +
          '<span class="pc-shot-name">' + esc(s.name) + '</span>' +
          ratioControl +
        '</span>';

      label.querySelector('input').addEventListener('change', function (e) {
        if (e.target.checked) state.selected[s.key] = true; else delete state.selected[s.key];
        renderShots(); renderQualities(); renderBar();
      });
      var ratioSelect = label.querySelector('.pc-shot-ratio');
      if (ratioSelect) {
        ratioSelect.addEventListener('click', function (event) { event.preventDefault(); event.stopPropagation(); });
        ratioSelect.addEventListener('change', function (event) { state.ratios[s.key] = event.target.value; });
      }
      wrap.appendChild(label);
    });

    var counter = $('[data-shot-counter]');
    counter.textContent = max > 0
      ? fa(count) + ' از ' + fa(max) + ' انتخاب شده'
      : fa(count) + ' صحنه انتخاب شده';

    var more = $('[data-more]');
    more.hidden = !hasHidden;
    more.setAttribute('aria-expanded', state.showAll ? 'true' : 'false');
    more.innerHTML = state.showAll
      ? '<i class="fa-solid fa-compress" aria-hidden="true"></i> فقط صحنه‌های پیشنهادی'
      : '<i class="fa-solid fa-sliders" aria-hidden="true"></i> صحنه‌های بیشتر (' + fa(shots.filter(function (s) { return !s.is_default; }).length) + ')';
  }
  $('[data-more]').addEventListener('click', function () { state.showAll = !state.showAll; renderShots(); });

  /* ───── کیفیت ───── */
  function renderQualities() {
    var wrap = $('[data-qualities]');
    wrap.innerHTML = '';
    var levels = cfg.quality_levels || { standard: 'استاندارد' };
    Object.keys(levels).forEach(function (quality) {
      var cost = shots.reduce(function (sum, s) {
        if (!state.selected[s.key]) return sum;
        var q = (s.quality_credits || {})[quality];
        return sum + Number(q != null ? q : (s.credits || 0));
      }, 0);
      var on = state.quality === quality;
      var label = document.createElement('label');
      label.className = 'pc-quality' + (on ? ' is-selected' : '');
      label.innerHTML =
        '<input type="radio" name="pc-quality" value="' + esc(quality) + '"' + (on ? ' checked' : '') + '>' +
        '<span class="pc-quality-top">' +
          '<span class="pc-quality-icon" aria-hidden="true"><i class="fa-solid ' + (QUALITY_ICON[quality] || 'fa-bolt') + '"></i></span>' +
          '<span class="pc-quality-cost">' + fa(cost) + ' کردیت</span>' +
        '</span>' +
        '<span><strong>' + esc(levels[quality]) + '</strong>' +
        '<small>' + esc(QUALITY_HINT[quality] || '') + '</small></span>';
      label.querySelector('input').addEventListener('change', function () {
        state.quality = quality; renderQualities(); renderShots(); renderBar();
      });
      wrap.appendChild(label);
    });
  }

  /* ───── آپلود: یک باکس، ۱ تا ۳ عکس ───── */
  var input = $('[data-upload-input]');
  var box = $('[data-upload-box]');

  function uploadedCount() { return state.uploads.filter(function (u) { return u && u.id; }).length; }
  function anyPending() { return state.uploads.some(function (u) { return u.pending; }); }

  function addFiles(fileList) {
    if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
    var files = Array.prototype.filter.call(fileList || [], function (f) { return f && /^image\/(jpeg|png|webp)$/i.test(f.type); });
    var skippedType = (fileList ? fileList.length : 0) - files.length;
    var room = MAX_PHOTOS - state.uploads.length;
    state.notice = null;
    if (room <= 0) {
      state.notice = { tone: 'yellow', text: 'حداکثر ' + fa(MAX_PHOTOS) + ' عکس می‌توانی بگذاری؛ برای عوض‌کردن، اول یکی را حذف کن.' };
      renderUploads(); renderCheck();
      return;
    }
    if (files.length > room) {
      state.notice = { tone: 'yellow', text: 'فقط ' + fa(room) + ' عکس اول برداشته شد (حداکثر ' + fa(MAX_PHOTOS) + ' عکس).' };
      files = files.slice(0, room);
    } else if (skippedType > 0) {
      state.notice = { tone: 'yellow', text: 'فقط عکس JPG، PNG یا WEBP قابل قبول است.' };
    }
    files.forEach(uploadOne);
    renderUploads(); renderCheck(); renderBar();
  }

  function uploadOne(file) {
    if (file.size > cfg.max_upload_mb * 1024 * 1024) {
      state.notice = { tone: 'red', text: 'حجم یکی از عکس‌ها بیشتر از ' + fa(cfg.max_upload_mb) + ' مگابایت است.' };
      return;
    }
    var item = { preview: URL.createObjectURL(file), file: file, pending: true };
    state.uploads.push(item);
    invalidateSmart();
    sendUpload(item);
  }

  /* پیام خطای قابل‌فهم بر اساس وضعیت پاسخ سرور */
  function uploadError(res) {
    var msg = res && res.body && res.body.message;
    var errors = res && res.body && res.body.errors;
    if (errors) { var k = Object.keys(errors)[0]; if (k && errors[k][0]) return errors[k][0]; }
    switch (res && res.status) {
      case 401: return 'برای بارگذاری عکس اول وارد حسابت شو.';
      case 404: return 'بارگذاری الان در دسترس نیست؛ صفحه را تازه کن و دوباره امتحان کن.';
      case 413: return 'حجم عکس خیلی زیاد است؛ عکس سبک‌تری بفرست.';
      case 419: return 'صفحه مدت زیادی باز مانده؛ یک بار تازه‌اش کن.';
      case 422: return msg || 'این فایل قابل خواندن نیست؛ عکس دیگری انتخاب کن.';
      case 429: return 'چند لحظه صبر کن و دوباره امتحان کن.';
      default: return 'بارگذاری انجام نشد؛ دوباره امتحان کن.';
    }
  }

  function sendUpload(item) {
    item.pending = true;
    item.error = null;
    item.verdict = null;
    var data = new FormData();
    if (item.file) data.append('image', item.file);
    else data.append('image_url', item.imageUrl);
    data.append('role', state.uploads.indexOf(item) === 0 ? 'main' : 'angle');
    data.append('ai', '0');
    request(cfg.urls.preflight, { method: 'POST', body: data }).then(function (res) {
      if (state.uploads.indexOf(item) === -1) return; // در این فاصله حذف شده
      item.pending = false;
      if (!res.ok || !res.body.ok) {
        item.error = uploadError(res);
      } else {
        var b = res.body;
        item.id = b.upload_id;
        item.verdict = b.verdict;
        item.issues = b.issues || [];
        item.suggestion = b.suggestion || '';
        item.originalUrl = b.original_url;
        item.fixedUrl = b.fixed_url;
        item.useFixed = !!b.fixed_url;
      }
    }).catch(function () {
      if (state.uploads.indexOf(item) === -1) return;
      item.pending = false;
      item.error = 'اینترنت یا سرور در دسترس نبود؛ دوباره امتحان کن.';
    }).then(function () {
      renderUploads(); renderCheck(); renderBar();
    });
  }

  function retryUpload(i) {
    var item = state.uploads[i];
    if (!item || (!item.file && !item.imageUrl)) return;
    state.notice = null;
    invalidateSmart();
    sendUpload(item);
    renderUploads(); renderCheck(); renderBar();
  }

  function removeUpload(i) {
    var item = state.uploads[i];
    if (!item) return;
    if (item.preview) URL.revokeObjectURL(item.preview);
    state.uploads.splice(i, 1);
    state.notice = null;
    invalidateSmart();
    renderUploads(); renderCheck(); renderBar();
  }

  function makeMain(i) {
    if (i <= 0 || !state.uploads[i]) return;
    var item = state.uploads.splice(i, 1)[0];
    state.uploads.unshift(item);
    invalidateSmart();
    renderUploads(); renderCheck(); renderBar();
  }

  var BADGE = { green: 'آماده', yellow: 'قابل استفاده', red: 'نامناسب' };

  function smartPhoto(i) {
    if (!state.smart || state.smart.pending || !Array.isArray(state.smart.photos)) return null;
    return state.smart.photos.filter(function (p) { return Number(p.index) === i + 1; })[0] || null;
  }

  function renderUploads() {
    var list = $('[data-upload-list]');
    var empty = $('[data-upload-empty]');
    var filled = $('[data-upload-filled]');
    var n = state.uploads.length;
    empty.hidden = n > 0;
    filled.hidden = n === 0;
    box.classList.toggle('has-files', n > 0);

    list.innerHTML = '';
    state.uploads.forEach(function (u, i) {
      var el = document.createElement('figure');
      el.className = 'pc-up-item' + (i === 0 ? ' is-main' : '') + (u.pending ? ' is-pending' : '');
      el.style.margin = '0';
      var sp = smartPhoto(i);
      var tone = u.pending ? '' : (u.error ? 'red' : (sp ? (sp.ok ? 'green' : 'yellow') : (u.verdict || '')));
      var badgeText = u.pending ? 'در حال بارگذاری…'
        : (u.error ? 'بارگذاری نشد' : (sp ? (sp.ok ? 'تأیید هوشمند' : 'نیاز به اصلاح') : (BADGE[u.verdict] || '')));
      el.innerHTML =
        '<img alt="' + esc('عکس ' + (ANGLES[i] || '')) + '" referrerpolicy="no-referrer" src="' + esc(u.useFixed && u.fixedUrl ? u.fixedUrl : (u.originalUrl || u.preview)) + '">' +
        (badgeText ? '<span class="pc-badge' + (tone ? ' is-' + tone : '') + '" title="' + esc(badgeText) + '">' + (u.pending ? '<span class="pc-spin" aria-hidden="true" style="width:10px;height:10px;vertical-align:-1px"></span> ' : '') + esc(badgeText) + '</span>' : '') +
        '<button type="button" class="pc-up-remove" data-remove="' + i + '" aria-label="حذف عکس ' + esc(ANGLES[i] || '') + '"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
        '<figcaption class="pc-up-tag"><span>' + esc(ANGLES[i] || 'زاویه') + '</span>' +
          (i === 0
            ? '<em class="pc-up-main-pill"><i class="fa-solid fa-star" aria-hidden="true"></i><b>اصلی</b></em>'
            : '<button type="button" class="pc-up-main-btn" data-main="' + i + '" aria-label="این عکس را اصلی کن" title="اصلی کن"><i class="fa-regular fa-star" aria-hidden="true"></i><b>اصلی کن</b></button>') +
        '</figcaption>';
      list.appendChild(el);
    });
    if (n < MAX_PHOTOS) {
      var add = document.createElement('label');
      add.className = 'pc-up-add';
      add.setAttribute('for', 'pc-up-input');
      add.innerHTML = n < RECOMMENDED
        ? '<i class="fa-solid fa-plus" aria-hidden="true"></i><strong>افزودن ' + esc(ANGLES[n]) + '</strong><small>پیشنهادی؛ نتیجه دقیق‌تر می‌شود</small>'
        : '<i class="fa-solid fa-plus" aria-hidden="true"></i><strong>عکس بیشتر</strong><small>اختیاری · تا ' + fa(MAX_PHOTOS) + ' عکس</small>';
      list.appendChild(add);
    }
    $$('[data-remove]', list).forEach(function (btn) {
      btn.addEventListener('click', function () { removeUpload(Number(btn.getAttribute('data-remove'))); });
    });
    $$('[data-main]', list).forEach(function (btn) {
      btn.addEventListener('click', function () { makeMain(Number(btn.getAttribute('data-main'))); });
    });

    var angles = $('[data-upload-angles]');
    if (angles) {
      var covered = state.smart && state.smart.coverage && state.smart.coverage.length ? state.smart.coverage : null;
      var html = '<b>زاویه‌های پیشنهادی:</b>';
      ANGLES.slice(0, Math.min(RECOMMENDED, MAX_PHOTOS)).forEach(function (name, i) {
        var on = i < n;
        html += '<span class="pc-up-chip' + (on ? ' is-on' : '') + '"><i class="fa-solid ' + (on ? 'fa-check' : 'fa-circle') + '" aria-hidden="true"></i>' + esc(name) + '</span>';
      });
      if (n > RECOMMENDED) html += '<span class="pc-up-chip is-on"><i class="fa-solid fa-plus" aria-hidden="true"></i>' + fa(n - RECOMMENDED) + ' عکس بیشتر</span>';
      if (covered) {
        html += '<span class="pc-up-chip is-on"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>تشخیص هوشمند: ' +
          esc(covered.map(function (c) { return COVERAGE_FA[c] || c; }).join('، ')) + '</span>';
      }
      angles.innerHTML = html;
    }

    if (typeof renderLink === 'function' && link && link.images.length) renderLink();
    var counter = $('[data-upload-counter]');
    counter.textContent = fa(n) + ' از ' + fa(MAX_PHOTOS) + ' عکس';
    counter.classList.toggle('is-on', n > 0);
  }

  input.addEventListener('change', function () {
    if (input.files && input.files.length) addFiles(input.files);
    input.value = '';
  });
  ['dragenter', 'dragover'].forEach(function (ev) {
    box.addEventListener(ev, function (e) { e.preventDefault(); box.classList.add('is-dragover'); });
  });
  ['dragleave', 'dragend', 'drop'].forEach(function (ev) {
    box.addEventListener(ev, function (e) {
      if (ev === 'dragleave' && box.contains(e.relatedTarget)) return;
      box.classList.remove('is-dragover');
    });
  });
  box.addEventListener('drop', function (e) {
    e.preventDefault();
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
  });
  // چسباندن عکس با Ctrl+V / ⌘+V (دسکتاپ)
  document.addEventListener('paste', function (e) {
    var t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
    var items = (e.clipboardData && e.clipboardData.files) || [];
    if (items.length) { e.preventDefault(); addFiles(items); }
  });

  function uploadPayload() {
    return state.uploads.filter(function (u) { return u && u.id; }).map(function (u) {
      return { id: u.id, use_fixed: !!u.useFixed };
    });
  }

  /* ───── عکس از لینک محصول ───── */
  var linkBox = $('[data-link]');
  var link = { images: [], picked: [], busy: false };

  function linkRoom() { return Math.max(0, MAX_PHOTOS - state.uploads.length); }

  function linkMsg(text, info) {
    var el = $('[data-link-msg]');
    el.hidden = !text;
    el.textContent = text || '';
    el.classList.toggle('is-info', !!info);
  }

  function renderLink() {
    if (!linkBox) return;
    var result = $('[data-link-result]');
    result.hidden = !link.images.length;
    if (!link.images.length) return;
    var room = linkRoom();
    var grid = $('[data-link-grid]');
    grid.innerHTML = '';
    link.images.forEach(function (url) {
      var on = link.picked.indexOf(url) !== -1;
      var off = !on && link.picked.length >= room;
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'pc-link-pick' + (on ? ' is-on' : '') + (off ? ' is-off' : '');
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      b.setAttribute('aria-label', on ? 'برداشتن این عکس' : 'انتخاب این عکس');
      b.innerHTML = '<img alt="" loading="lazy" referrerpolicy="no-referrer" src="' + esc(url) + '"><i class="fa-solid ' + (on ? 'fa-check' : 'fa-plus') + '" aria-hidden="true"></i>';
      b.querySelector('img').addEventListener('error', function () {
        link.images = link.images.filter(function (x) { return x !== url; });
        link.picked = link.picked.filter(function (x) { return x !== url; });
        b.remove();
        updateLinkMeta();
      });
      b.addEventListener('click', function () {
        if (off) { linkMsg('حداکثر ' + fa(MAX_PHOTOS) + ' عکس می‌توانی داشته باشی؛ جای خالی: ' + fa(room) + '.', true); return; }
        if (on) link.picked = link.picked.filter(function (x) { return x !== url; });
        else link.picked.push(url);
        linkMsg('');
        renderLink();
      });
      grid.appendChild(b);
    });
    updateLinkMeta();
  }

  function updateLinkMeta() {
    var room = linkRoom();
    $('[data-link-count]').textContent = fa(link.picked.length) + ' انتخاب شده · جای خالی: ' + fa(room);
    var add = $('[data-link-add]');
    add.disabled = !link.picked.length || !room;
    if (!link.images.length) $('[data-link-result]').hidden = true;
  }

  if (linkBox && cfg.urls && cfg.urls.link_images) {
    var linkInput = $('[data-link-input]');
    var linkGo = $('[data-link-go]');
    $('[data-link-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
      var url = (linkInput.value || '').trim();
      if (!url) { linkMsg('لینک صفحه‌ی محصول را بگذار.'); linkInput.focus(); return; }
      if (link.busy) return;
      link.busy = true;
      linkGo.disabled = true;
      $('[data-link-icon]').innerHTML = '<span class="pc-spin" aria-hidden="true"></span>';
      linkMsg('در حال خواندن صفحه…', true);
      request(cfg.urls.link_images, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: url })
      }).then(function (res) {
        if (!res.ok || !res.body.ok) {
          link.images = []; link.picked = [];
          linkMsg(uploadError(res));
          return;
        }
        link.images = res.body.images || [];
        link.picked = [];
        $('[data-link-title]').textContent = res.body.title || '';
        linkMsg(link.images.length ? '' : 'در این صفحه عکسی پیدا نشد.');
      }).catch(function () {
        linkMsg('اینترنت یا سرور در دسترس نبود؛ دوباره امتحان کن.');
      }).then(function () {
        link.busy = false;
        linkGo.disabled = false;
        $('[data-link-icon]').innerHTML = '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>';
        renderLink();
      });
    });
    $('[data-link-add]').addEventListener('click', function () {
      if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
      var urls = link.picked.slice(0, linkRoom());
      urls.forEach(function (url) {
        var item = { preview: url, imageUrl: url, pending: true };
        state.uploads.push(item);
        sendUpload(item);
      });
      invalidateSmart();
      state.notice = null;
      link.images = linkRoom() > 0 ? link.images.filter(function (x) { return urls.indexOf(x) === -1; }) : [];
      link.picked = [];
      linkMsg(fa(urls.length) + ' عکس اضافه شد.', true);
      renderLink(); renderUploads(); renderCheck(); renderBar();
    });
    $('[data-link-close]').addEventListener('click', function () {
      link.images = []; link.picked = []; linkMsg(''); renderLink();
    });
  } else if (linkBox) {
    linkBox.hidden = true;
  }

  /* ───── بررسی هوشمند تصویر ورودی (اختیاری؛ یک درخواست) ───── */
  var smartBtn = $('[data-smart]');

  function invalidateSmart() { state.smart = null; }

  function renderSmartBtn() {
    if (!smartBtn) return;
    var ready = uploadedCount() > 0 && !anyPending();
    var busy = !!(state.smart && state.smart.pending);
    var done = !!(state.smart && !state.smart.pending);
    smartBtn.disabled = !cfg.is_authenticated || !ready || busy || done || state.building;
    var icon = smartBtn.querySelector('[data-smart-icon]');
    var label = smartBtn.querySelector('[data-smart-label]');
    icon.innerHTML = busy
      ? '<span class="pc-spin" aria-hidden="true"></span>'
      : '<i class="fa-solid ' + (done ? 'fa-check' : 'fa-wand-magic-sparkles') + '" aria-hidden="true"></i>';
    label.textContent = busy ? 'در حال بررسی…' : (done ? 'بررسی شد' : 'بررسی هوشمند');
  }

  function runSmart() {
    var uploads = uploadPayload();
    if (!uploads.length || anyPending()) return;
    state.smart = { pending: true };
    renderBar();
    request(cfg.urls.preflight_set, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ uploads: uploads, smart: true })
    }).then(function (res) {
      if (!state.smart || !state.smart.pending) return; // در این فاصله عکس‌ها عوض شده‌اند
      if (!res.ok || !res.body.ok) {
        state.smart = null;
        state.notice = { tone: 'red', text: (res.body && res.body.message) || 'بررسی هوشمند انجام نشد؛ دوباره امتحان کن.' };
        return;
      }
      state.notice = null;
      state.smart = res.body;
    }).catch(function () {
      state.smart = null;
      state.notice = { tone: 'red', text: 'ارتباط با سرور برقرار نشد؛ دوباره امتحان کن.' };
    }).then(function () {
      renderUploads(); renderCheck(); renderBar();
      var check = $('[data-check]');
      if (!check.hidden && check.getBoundingClientRect().bottom > window.innerHeight) {
        check.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'nearest' });
      }
    });
  }
  if (smartBtn) smartBtn.addEventListener('click', runSmart);

  /* ───── کادر پیام زیر آپلود ───── */
  var ICON = { green: 'fa-circle-check', yellow: 'fa-triangle-exclamation', red: 'fa-circle-xmark' };

  function photoLabel(i) { return 'عکس ' + fa(i + 1) + ' · ' + (ANGLES[i] || 'زاویه'); }

  function row(i, text, actions) {
    return '<li class="pc-check-row"><b>' + esc(photoLabel(i)) + '</b><span>' + esc(text) + '</span>' +
      (actions ? '<span class="pc-check-actions">' + actions + '</span>' : '') + '</li>';
  }
  function retryBtn(i) { return '<button type="button" class="pc-mini-btn" data-retry-up="' + i + '"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> دوباره</button>'; }
  function removeBtn(i) { return '<button type="button" class="pc-mini-btn is-ghost" data-remove-up="' + i + '"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> حذف</button>'; }

  function renderCheck() {
    var el = $('[data-check]');
    var html = '';
    var tone = '';
    var items = state.uploads.map(function (u, i) { return { u: u, i: i }; }).filter(function (x) { return !x.u.pending; });
    var failed = items.filter(function (x) { return x.u.error; });

    if (failed.length) {
      // ۱) خطای بارگذاری (مشکل اتصال یا سرور؛ نه کیفیت عکس)
      tone = 'red';
      html = '<div class="pc-check-head"><i class="fa-solid ' + ICON.red + '" aria-hidden="true"></i><strong>' +
        (failed.length === 1 ? 'یک عکس بارگذاری نشد.' : fa(failed.length) + ' عکس بارگذاری نشد.') + '</strong></div>' +
        '<ul class="pc-check-list">' + failed.map(function (x) { return row(x.i, x.u.error, retryBtn(x.i) + removeBtn(x.i)); }).join('') + '</ul>';
    } else if (state.smart && !state.smart.pending) {
      // ۲) نتیجه‌ی بررسی هوشمند
      var s = state.smart;
      tone = s.verdict || 'green';
      html = '<div class="pc-check-head"><i class="fa-solid ' + (ICON[tone] || ICON.green) + '" aria-hidden="true"></i><strong>' + esc(s.summary || '') + '</strong></div>';
      var notes = (Array.isArray(s.photos) ? s.photos : []).filter(function (p) { return p.note; });
      if (notes.length) {
        html += '<ul class="pc-check-list">' + notes.map(function (p) {
          var i = Number(p.index) - 1;
          return row(i, p.note, p.ok ? '' : removeBtn(i));
        }).join('') + '</ul>';
      } else if (s.issues && s.issues.length) {
        html += '<ul>' + s.issues.slice(0, 4).map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>';
      }
      if (s.suggestion) html += '<div class="pc-check-tip"><i class="fa-regular fa-lightbulb" aria-hidden="true"></i> ' + esc(s.suggestion) + '</div>';
      html += '<div class="pc-check-meta">' + (s.checked_by === 'vision'
        ? 'بررسی هوشمند · ' + fa(uploadedCount()) + ' عکس'
        : 'بررسی هوشمند الان در دسترس نبود؛ فقط بررسی ساده انجام شد.') + '</div>';
    } else if (state.notice) {
      tone = state.notice.tone;
      html = '<div class="pc-check-head"><i class="fa-solid ' + (ICON[tone] || ICON.yellow) + '" aria-hidden="true"></i><strong>' + esc(state.notice.text) + '</strong></div>';
    } else {
      // ۳) بررسی رایگان اولیه (فقط وقتی نکته‌ای هست)
      var red = items.filter(function (x) { return x.u.verdict === 'red'; });
      var yellow = items.filter(function (x) { return x.u.verdict === 'yellow'; });
      var detail = function (x) { return [(x.u.issues || []).join('، '), x.u.suggestion].filter(Boolean).join(' — '); };
      if (red.length) {
        tone = 'red';
        html = '<div class="pc-check-head"><i class="fa-solid ' + ICON.red + '" aria-hidden="true"></i><strong>' +
          (red.length === 1 ? 'این عکس برای ساخت مناسب نیست' : 'این عکس‌ها برای ساخت مناسب نیستند') + '؛ عوضش کن. هزینه‌ای کسر نشده.</strong></div>' +
          '<ul class="pc-check-list">' + red.map(function (x) { return row(x.i, detail(x), removeBtn(x.i)); }).join('') + '</ul>';
      } else if (yellow.length) {
        tone = 'yellow';
        html = '<div class="pc-check-head"><i class="fa-solid ' + ICON.yellow + '" aria-hidden="true"></i><strong>عکس‌ها قابل استفاده‌اند؛ چند نکته برای نتیجه‌ی بهتر:</strong></div>' +
          '<ul class="pc-check-list">' + yellow.map(function (x) { return row(x.i, detail(x), ''); }).join('') + '</ul>' +
          '<div class="pc-check-meta">برای نظر دقیق‌تر، «بررسی هوشمند» را بزن.</div>';
      }
    }

    // نسخه‌ی اصلاح خودکار عکس اصلی (قبل/بعد)
    var main = state.uploads[0];
    if (main && main.fixedUrl && !main.pending) {
      if (!html) { tone = 'yellow'; html = '<div class="pc-check-head"><i class="fa-solid ' + ICON.yellow + '" aria-hidden="true"></i><strong>عکس اصلی را خودکار بهتر کردیم:</strong></div>'; }
      html += '<div class="pc-fix">' +
        '<figure><img alt="عکس اصلی" src="' + esc(main.originalUrl || main.preview) + '"><figcaption>قبل</figcaption></figure>' +
        '<figure><img alt="عکس اصلاح‌شده" src="' + esc(main.fixedUrl) + '"><figcaption>اصلاح خودکار</figcaption></figure>' +
        '</div>' +
        '<label class="pc-switch"><input type="checkbox" data-use-fixed' + (main.useFixed ? ' checked' : '') + '> از نسخه‌ی اصلاح‌شده استفاده کن</label>';
    }

    el.hidden = !html;
    el.className = 'pc-check' + (tone ? ' is-' + tone : '');
    el.innerHTML = html;
    $$('[data-retry-up]', el).forEach(function (b) { b.addEventListener('click', function () { retryUpload(Number(b.getAttribute('data-retry-up'))); }); });
    $$('[data-remove-up]', el).forEach(function (b) { b.addEventListener('click', function () { removeUpload(Number(b.getAttribute('data-remove-up'))); }); });
    var toggle = el.querySelector('[data-use-fixed]');
    if (toggle) toggle.addEventListener('change', function () {
      if (state.uploads[0]) state.uploads[0].useFixed = toggle.checked;
      invalidateSmart();
      renderUploads(); renderCheck(); renderBar();
    });
  }

  /* ───── وضعیت گام‌ها و دکمه‌ی بساز ───── */
  function canBuild() {
    var main = state.uploads[0];
    var hasRejected = state.uploads.some(function (u) { return u.verdict === 'red' || u.error; });
    var smartRed = !!(state.smart && !state.smart.pending && state.smart.verdict === 'red');
    var smartBusy = !!(state.smart && state.smart.pending);
    return !state.building && !!(main && main.id) && !hasRejected && !anyPending() && !smartRed && !smartBusy && selectedKeys().length > 0;
  }

  function renderSteps() {
    var mainOk = !!(state.uploads[0] && state.uploads[0].id && state.uploads[0].verdict !== 'red' && !state.uploads[0].error) && !anyPending();
    var shotsOk = selectedKeys().length > 0;
    var built = !!(state.batch && state.batch.items && state.batch.items.length &&
      !state.batch.items.some(function (i) { return i.status === 'pending' || i.status === 'running'; }) &&
      state.batch.items.some(function (i) { return i.status === 'completed'; }));
    // کیفیت همیشه یک گزینه‌ی انتخاب‌شده دارد؛ وقتی عکس و صحنه آماده‌اند، این گام هم کامل است.
    var done = { 1: mainOk, 2: shotsOk, 3: mainOk && shotsOk, 4: built };
    var active = !mainOk ? 1 : (!shotsOk ? 2 : (built ? 0 : 4));
    $$('[data-step-item]').forEach(function (li) {
      var n = Number(li.getAttribute('data-step-item'));
      var isDone = !!done[n];
      var badge = li.querySelector('span');
      if (!badge.hasAttribute('data-num')) badge.setAttribute('data-num', badge.textContent);
      if (isDone !== li.classList.contains('is-done')) {
        badge.innerHTML = isDone ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : esc(badge.getAttribute('data-num'));
      }
      li.classList.toggle('is-done', isDone);
      li.classList.toggle('is-active', n === active && !isDone);
      if (n === active) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
      li.setAttribute('aria-label', li.textContent.trim() + (isDone ? ' (کامل شد)' : ''));
    });
  }

  function renderBar() {
    var n = selectedKeys().length;
    var credits = totalCredits();
    var summary = n ? fa(n) + ' عکس · ' + fa(credits) + ' کردیت' : 'یک صحنه انتخاب کن';
    $$('[data-bar-summary]').forEach(function (el) { el.textContent = summary; });

    var balHtml;
    if (!cfg.is_authenticated) {
      balHtml = 'برای ساخت <a href="' + esc(cfg.login_url) + '">وارد شو</a>';
    } else if (credits > state.balance) {
      balHtml = fa(credits - state.balance) + ' کردیت کم داری · <a href="' + esc(cfg.pricing_url) + '">افزایش اعتبار</a>';
    } else if (!state.uploads.length) {
      balHtml = 'اول عکس محصولت را بگذار';
    } else {
      balHtml = n ? 'بعد از ساخت ' + fa(state.balance - credits) + ' کردیت می‌ماند' : 'موجودی: ' + fa(state.balance) + ' کردیت';
    }
    $$('[data-bar-balance]').forEach(function (el) { el.innerHTML = balHtml; });

    var ok = canBuild();
    $$('[data-build]').forEach(function (btn) {
      btn.disabled = !ok;
      var label = btn.querySelector('span');
      if (state.building) label.textContent = 'در حال ساخت…';
      else label.textContent = state.batch ? 'ساخت دوباره' : 'بساز';
    });

    renderSmartBtn();
    renderSummary();
    renderSteps();
    syncDock();
  }

  /* ───── خلاصه‌ی سفارش (دسکتاپ): اسلایدر تک‌عکسی، هر ۵ ثانیه با دیزالو ───── */
  var slider = { el: $('[data-sum-slider]'), items: [], sig: '', idx: 0, timer: null, hover: false };
  var desktop = window.matchMedia('(min-width: 901px)');

  function showSlide(i) {
    var nodes = $$('[data-sum-slides] > *');
    if (!nodes.length) return;
    slider.idx = (i + nodes.length) % nodes.length;
    nodes.forEach(function (node, k) { node.classList.toggle('is-active', k === slider.idx); });
    var next = nodes[(slider.idx + 1) % nodes.length];
    if (next && next.tagName === 'IMG' && next.loading === 'lazy') next.loading = 'eager'; // پیش‌بارگذاری اسلاید بعدی
    var item = slider.items[slider.idx] || {};
    $('[data-sum-cap]').textContent = item.name || '';
    $('[data-sum-count]').textContent = nodes.length > 1 ? fa(slider.idx + 1) + ' / ' + fa(nodes.length) : '';
  }

  function renderSlider() {
    if (!slider.el) return;
    var items = selectedShots().map(function (s) { return { key: s.key, url: s.sample_url, name: s.name }; });
    if (!items.length) items = [{ key: '_cover', url: (cfg.product || {}).cover, name: '' }];
    var sig = items.map(function (x) { return x.key; }).join('|');
    if (sig === slider.sig) return;
    var currentKey = (slider.items[slider.idx] || {}).key;
    slider.items = items;
    slider.sig = sig;
    var wrap = $('[data-sum-slides]');
    wrap.innerHTML = items.map(function (x, k) {
      return x.url
        ? '<img src="' + esc(x.url) + '" alt="' + esc(x.name || 'نمونه') + '" decoding="async" loading="' + (k < 2 ? 'eager' : 'lazy') + '">'
        : '<span><i class="fa-solid fa-image" aria-hidden="true"></i></span>';
    }).join('');
    var keep = items.map(function (x) { return x.key; }).indexOf(currentKey);
    showSlide(keep >= 0 ? keep : 0);
  }

  function tickSlider() {
    if (slider.hover || document.hidden || !desktop.matches || slider.items.length < 2) return;
    showSlide(slider.idx + 1);
  }
  if (slider.el) {
    slider.timer = window.setInterval(tickSlider, 5000);
    slider.el.addEventListener('mouseenter', function () { slider.hover = true; });
    slider.el.addEventListener('mouseleave', function () { slider.hover = false; });
  }

  function renderSummary() {
    renderSlider();
    var n = selectedKeys().length;
    $('[data-sum-shots]').textContent = n ? fa(n) + ' صحنه' : '—';
    $('[data-sum-quality]').textContent = (cfg.quality_levels || {})[state.quality] || state.quality;
    var count = uploadedCount();
    $('[data-sum-uploads]').textContent = anyPending() ? 'در حال بارگذاری…' : (count ? fa(count) + (count === 1 ? ' عکس' : ' زاویه') : 'هنوز آپلود نشده');
    $('[data-sum-balance]').textContent = cfg.is_authenticated ? fa(state.balance) + ' کردیت' : '—';
  }

  /* ───── باکس «بساز» موبایل: فریز بالای نوار اپ تا جای طبیعی‌اش (زیر کیفیت) برسد ─────
   * همان رفتار .ps-dock صفحه‌ی محصول. */
  var dockWrap = $('[data-dock-wrap]');
  var dock = $('[data-dock]');
  var mobile = window.matchMedia('(max-width: 900px)');
  var DOCK_BOTTOM = 108; // همان فاصله‌ی صفحه‌ی محصول، بالای نوار ناوبری اپ
  function syncDock() {
    if (!dock || !dockWrap) return;
    if (!mobile.matches) {
      dock.classList.remove('is-floating');
      dockWrap.style.height = '';
      dock.style.width = '';
      dock.style.left = '';
      return;
    }
    var h = dock.offsetHeight;
    var naturalTop = dockWrap.getBoundingClientRect().top;
    var spotTop = window.innerHeight - DOCK_BOTTOM - h;
    var floating = naturalTop > spotTop;
    dock.classList.toggle('is-floating', floating);
    dockWrap.style.height = floating ? h + 'px' : '';
    var w = dockWrap.clientWidth;
    dock.style.width = w + 'px';
    dock.style.left = floating ? dockWrap.getBoundingClientRect().left + 'px' : '';
  }
  window.addEventListener('scroll', syncDock, { passive: true });
  window.addEventListener('resize', syncDock);
  window.addEventListener('load', syncDock);

  function showCreditAlert() {
    var modal = $('[data-credit-alert]');
    var count = affordableCount();
    $('[data-credit-alert-message]').textContent = count > 0
      ? 'اعتبار تو برای ساخت ' + fa(count) + ' عکس کافی است. چند صحنه را بردار یا اعتبارت را افزایش بده.'
      : 'اعتبار فعلی برای ساخت هیچ‌کدام از صحنه‌های انتخابی کافی نیست.';
    modal.hidden = false;
    var focusable = modal.querySelector('button, a');
    if (focusable) focusable.focus();
  }
  function hideCreditAlert() { $('[data-credit-alert]').hidden = true; }
  $$('[data-credit-alert-close]').forEach(function (el) { el.addEventListener('click', hideCreditAlert); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideCreditAlert(); });

  /* ───── نتیجه‌ها ───── */
  function tileHtml(item) {
    var ratio = (item.aspect_ratio || '4:5').split(':');
    var media = '<div class="pc-tile-media" style="aspect-ratio:' + ratio[0] + ' / ' + ratio[1] + '">';
    if (item.status === 'completed' && item.image_url) {
      media += '<img alt="' + esc(item.name) + '" src="' + esc(item.image_url) + '">';
    } else if (item.status === 'failed') {
      media += '<div class="pc-tile-error"><div>' + esc(item.error || 'ساخت این عکس انجام نشد.') + '</div>' +
        (item.can_retry ? '<button type="button" class="pc-btn pc-btn-ghost" data-retry="' + item.id + '"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> دوباره</button>' : '') +
        '</div>';
    } else {
      media += '<span class="pc-tile-state">' + (item.status === 'running' ? 'در حال ساخت…' : 'در صف') + '</span>';
    }
    media += '</div>';

    var actions = '';
    if (item.status === 'completed' && item.image_url) {
      actions = '<a class="pc-icon-btn" href="' + esc(item.image_url) + '" download aria-label="دانلود ' + esc(item.name) + '"><i class="fa-solid fa-download" aria-hidden="true"></i></a>';
    }
    return media + '<div class="pc-tile-body"><span class="pc-tile-name">' + esc(item.name) +
      (item.status === 'completed' && item.qc && item.qc.passed === false ? ' <span class="pc-soon">بررسی کن</span>' : '') +
      '</span><span class="pc-tile-actions">' + actions + '</span></div>';
  }

  function renderTile(item) {
    var tile = $('[data-tile="' + item.id + '"]');
    if (!tile) {
      tile = document.createElement('article');
      tile.setAttribute('data-tile', item.id);
      $('[data-tiles]').appendChild(tile);
    }
    tile.className = 'pc-tile is-' + (item.status === 'pending' ? 'waiting' : item.status);
    tile.setAttribute('aria-busy', item.status === 'running' || item.status === 'pending' ? 'true' : 'false');
    tile.innerHTML = tileHtml(item);
    var retry = tile.querySelector('[data-retry]');
    if (retry) retry.addEventListener('click', function () { enqueueRetry(item.id); });
  }

  function updateResultsStatus() {
    if (!state.batch) return;
    var items = state.batch.items;
    var done = items.filter(function (i) { return i.status === 'completed'; }).length;
    var failed = items.filter(function (i) { return i.status === 'failed'; }).length;
    var open = items.length - done - failed;
    var charged = items.reduce(function (s, i) { return s + (i.credits_charged || 0); }, 0);
    var refunded = items.reduce(function (s, i) { return s + (i.credits_refunded || 0); }, 0);

    var text = open > 0
      ? 'در حال ساخت: ' + fa(done) + ' از ' + fa(items.length) + ' عکس آماده شد'
      : (failed ? fa(done) + ' عکس آماده شد؛ ' + fa(failed) + ' عکس ساخته نشد و اعتبارش برگشت.' : 'همه‌ی ' + fa(done) + ' عکس آماده است.');
    if (charged) text += ' · کسرشده: ' + fa(charged) + ' کردیت';
    if (refunded) text += ' · برگشتی: ' + fa(refunded);
    $('[data-results-status]').textContent = text;

    var dl = $('[data-download]');
    dl.hidden = done === 0 || open > 0;
    dl.href = state.batch.download_url || '#';
  }

  function itemById(id) { return state.batch.items.filter(function (i) { return i.id === id; })[0]; }

  function enqueueRetry(id) {
    var item = itemById(id);
    item.status = 'running'; renderTile(item); updateResultsStatus();
    request(state.batch.run_urls[id], { method: 'POST' }).then(function (res) {
      if (res.body && res.body.item) Object.assign(item, res.body.item);
      else { item.status = 'failed'; item.error = (res.body && res.body.message) || 'ساخت این عکس انجام نشد.'; item.can_retry = true; }
      if (typeof res.body.balance === 'number') state.balance = res.body.balance;
      if (res.ok) pollBatch(1200);
    }).catch(function () {
      item.status = 'failed'; item.error = 'ارتباط قطع شد. دوباره امتحان کن.'; item.can_retry = true;
    }).then(function () { renderTile(item); updateResultsStatus(); renderBar(); });
  }

  function applyBatch(batch) {
    state.batch.status = batch.status;
    state.batch.download_url = batch.download_url;
    state.batch.show_url = batch.show_url;
    if (typeof batch.balance === 'number') state.balance = batch.balance;
    state.batch.items = batch.items;
    state.batch.items.forEach(renderTile);
    updateResultsStatus();
  }

  function pollBatch(delay) {
    if (!state.batch || !state.batch.show_url) return;
    if (state.pollTimer) window.clearTimeout(state.pollTimer);
    state.pollTimer = window.setTimeout(function () {
      request(state.batch.show_url).then(function (res) {
        if (!res.ok || !res.body.batch) throw new Error('poll');
        applyBatch(res.body.batch);
        var open = state.batch.items.some(function (i) { return i.status === 'pending' || i.status === 'running'; });
        state.building = open;
        renderBar();
        if (open) pollBatch(2500);
      }).catch(function () { pollBatch(5000); });
    }, delay == null ? 2500 : delay);
  }

  function failStart(message) {
    state.building = false;
    state.notice = { tone: 'red', text: message };
    renderCheck(); renderBar();
    var check = $('[data-check]');
    check.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'center' });
  }

  function build() {
    if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
    if (!canBuild()) return;
    if (totalCredits() > state.balance) { showCreditAlert(); return; }
    state.building = true; renderBar();

    var shotRatios = {};
    selectedKeys().forEach(function (key) { shotRatios[key] = state.ratios[key]; });

    request(cfg.urls.batches, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ uploads: uploadPayload(), shots: selectedKeys(), quality_level: state.quality, shot_ratios: shotRatios })
    }).then(function (res) {
      if (!res.ok || !res.body.ok) {
        var errors = res.body && res.body.errors ? Object.keys(res.body.errors).map(function (k) { return res.body.errors[k][0]; }) : [];
        failStart(errors[0] || (res.body && res.body.message) || 'ساخت شروع نشد؛ دوباره امتحان کن.');
        return;
      }
      state.batch = res.body.batch;
      var section = $('[data-results]');
      section.hidden = false;
      $('[data-tiles]').innerHTML = '';
      state.batch.items.forEach(renderTile);
      updateResultsStatus();
      if (res.body.message) $('[data-results-status]').textContent = res.body.message;
      renderBar();
      section.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'start' });
      pollBatch(1200);
    }).catch(function () {
      failStart('ارتباط با سرور برقرار نشد.');
    });
  }
  $$('[data-build]').forEach(function (btn) { btn.addEventListener('click', build); });

  renderShots();
  renderQualities();
  renderUploads();
  renderBar();
})();
