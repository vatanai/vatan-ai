/*
 * صفحه‌ی «بساز» پک شات محصول.
 * جریان: آپلود ← فیلتر کیفیت (بدون هزینه) ← انتخاب شات‌ها ← ساخت batch
 *        ← صف پس‌زمینه ← پیگیری وضعیت ← اسلایدر نتیجه.
 * شکست یک شات فقط همان شات را برمی‌گرداند و دکمه‌ی «دوباره» دارد.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-product-pack]');
  var cfgEl = document.getElementById('product-pack-config');
  if (!root || !cfgEl) return;

  var cfg = JSON.parse(cfgEl.textContent || '{}');
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (v) { return String(v).replace(/[0-9]/g, function (d) { return FA[d]; }); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };

  var state = {
    uploads: [],            // [{id, verdict, fixedUrl, useFixed}] — اندیس ۰ = عکس اصلی
    selected: {},           // shot_key => true
    showAll: false,
    quality: cfg.default_quality || 'standard',
    ratios: {},             // shot_key => نسبت انتخابی همان شات
    setReport: null,
    balance: Number(cfg.balance || 0),
    building: false,
    batch: null,
    pollTimer: null,
    sliderIndex: 0,
    sliderTimer: null
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

  /* ───── شات‌ها ───── */
  var shots = Array.isArray(cfg.shots) ? cfg.shots : [];
  shots.forEach(function (s) { if (s.is_default) state.selected[s.key] = true; state.ratios[s.key] = s.default_aspect_ratio || '4:5'; });
  if (!Object.keys(state.selected).length) shots.slice(0, 4).forEach(function (s) { state.selected[s.key] = true; });
  var hasHidden = shots.some(function (s) { return !s.is_default; });

  function renderShots() {
    var wrap = $('[data-shots]');
    wrap.innerHTML = '';
    var count = selectedKeys().length;
    shots.forEach(function (s) {
      var on = !!state.selected[s.key];
      var label = document.createElement('label');
      label.className = 'pp-shot' + (on ? ' is-selected' : '');
      label.hidden = !state.showAll && !s.is_default && !on;
      var limitReached = !on && count >= cfg.max_shots;
      if (limitReached) label.classList.add('is-disabled');
      var allowedRatios = Array.isArray(s.allowed_aspect_ratios) && s.allowed_aspect_ratios.length ? s.allowed_aspect_ratios : [s.default_aspect_ratio || '4:5'];
      var ratioControl = on && s.aspect_ratio_user_selectable && allowedRatios.length > 1
        ? '<select class="pp-shot-ratio" aria-label="نسبت ' + esc(s.name) + '">' + allowedRatios.map(function (ratio) { return '<option value="' + esc(ratio) + '"' + (state.ratios[s.key] === ratio ? ' selected' : '') + '>' + esc(ratio) + '</option>'; }).join('') + '</select>'
        : '<small class="pp-shot-ratio-static" dir="ltr">' + esc(state.ratios[s.key] || allowedRatios[0]) + '</small>';
      label.innerHTML =
        '<input type="checkbox" ' + (on ? 'checked' : '') + (limitReached ? ' disabled' : '') + ' aria-label="' + esc(s.name) + '">' +
        '<span class="pp-shot-media">' + (s.sample_url ? '<img loading="lazy" alt="" src="' + esc(s.sample_url) + '">' : '<i class="fa-solid fa-image" aria-hidden="true"></i>') + '</span>' +
        '<span class="pp-shot-tick" aria-hidden="true"><i class="fa-solid fa-check"></i></span>' +
        '<span class="pp-shot-body"><span class="pp-shot-name">' + esc(s.name) + '</span><span class="pp-shot-meta" style="display:block">' + fa(shotCredits(s)) + ' کردیت' + (s.tags && s.tags.length ? ' · ' + esc(s.tags.slice(0, 2).join('، ')) : '') + '</span>' + ratioControl + '</span>';
      label.querySelector('input').addEventListener('change', function (e) {
        if (e.target.checked) state.selected[s.key] = true; else delete state.selected[s.key];
        renderShots(); renderBar();
      });
      var ratioSelect = label.querySelector('.pp-shot-ratio');
      if (ratioSelect) ratioSelect.addEventListener('click', function (event) { event.preventDefault(); event.stopPropagation(); });
      if (ratioSelect) ratioSelect.addEventListener('change', function (event) { state.ratios[s.key] = event.target.value; });
      wrap.appendChild(label);
    });
    var more = $('[data-more]');
    more.hidden = !hasHidden;
    more.setAttribute('aria-expanded', state.showAll ? 'true' : 'false');
    more.innerHTML = state.showAll
      ? '<i class="fa-solid fa-compress" aria-hidden="true"></i> فقط اسلایدهای انتخاب‌شده'
      : '<i class="fa-solid fa-sliders" aria-hidden="true"></i> اسلایدها را شخصی‌سازی کن (+' + fa(shots.filter(function (s) { return !s.is_default; }).length) + ')';
  }
  $('[data-more]').addEventListener('click', function () { state.showAll = !state.showAll; renderShots(); });

  function selectedKeys() { return shots.filter(function (s) { return state.selected[s.key]; }).map(function (s) { return s.key; }); }
  function shotCredits(s) { return Number((s.quality_credits || {})[state.quality] != null ? s.quality_credits[state.quality] : s.credits || 0); }
  function totalCredits() { return shots.reduce(function (sum, s) { return sum + (state.selected[s.key] ? shotCredits(s) : 0); }, 0); }
  function affordableCount() {
    var remaining = state.balance;
    var count = 0;
    shots.filter(function (s) { return state.selected[s.key]; }).sort(function (a, b) {
      return shotCredits(a) - shotCredits(b);
    }).some(function (s) {
      var cost = shotCredits(s);
      if (cost > remaining) return true;
      remaining -= cost;
      count += 1;
      return false;
    });
    return count;
  }

  /* ───── سطح کیفیت ───── */
  function renderQualities() {
    var wrap = $('[data-qualities]');
    wrap.innerHTML = '';
    Object.keys(cfg.quality_levels || {standard: 'استاندارد'}).forEach(function (quality) {
      var label = document.createElement('label');
      label.className = 'pp-ratio' + (state.quality === quality ? ' is-selected' : '');
      label.innerHTML = '<input type="radio" name="pp-quality" value="' + esc(quality) + '" ' + (state.quality === quality ? 'checked' : '') + '>' +
        '<span class="pp-quality-icon" aria-hidden="true"><i class="fa-solid ' + (quality === 'best' ? 'fa-crown' : (quality === 'professional' ? 'fa-gem' : 'fa-bolt')) + '"></i></span>' +
        '<span><strong>' + esc(cfg.quality_levels[quality]) + '</strong> <small>' + fa(shots.reduce(function (sum, s) { return sum + (state.selected[s.key] ? Number((s.quality_credits || {})[quality] || s.credits || 0) : 0); }, 0)) + ' کردیت</small></span>';
      label.querySelector('input').addEventListener('change', function () { state.quality = quality; renderQualities(); renderShots(); renderBar(); });
      wrap.appendChild(label);
    });
  }

  /* ───── آپلود + فیلتر کیفیت ───── */
  function slotEl(i) { return $('.pp-drop[data-slot="' + i + '"]'); }

  function setSlotPreview(i, file) {
    var el = slotEl(i);
    var img = el.querySelector('[data-slot-img]');
    img.src = URL.createObjectURL(file);
    img.hidden = false;
    el.classList.add('has-image');
    var rm = el.querySelector('[data-slot-remove]');
    if (rm) rm.hidden = false;
  }

  function clearSlot(i) {
    var el = slotEl(i);
    var img = el.querySelector('[data-slot-img]');
    img.hidden = true; img.removeAttribute('src');
    el.classList.remove('has-image');
    var input = el.querySelector('input[type=file]'); if (input) input.value = '';
    var rm = el.querySelector('[data-slot-remove]'); if (rm) rm.hidden = true;
    var badge = el.querySelector('[data-slot-badge]'); if (badge) badge.hidden = true;
    state.uploads[i] = null;
    resetSetReport();
    renderBar();
  }

  function resetSetReport() {
    state.setReport = null;
    var sheet = $('[data-product-sheet]');
    if (sheet) sheet.hidden = true;
  }

  function badge(i, text, tone) {
    var b = slotEl(i).querySelector('[data-slot-badge]');
    if (!b) return;
    b.hidden = false; b.className = 'pp-slot-badge' + (tone ? ' is-' + tone : ''); b.textContent = text;
  }

  function upload(i, file) {
    if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
    if (file.size > cfg.max_upload_mb * 1024 * 1024) { showCheck('red', 'حجم عکس بیشتر از ' + fa(cfg.max_upload_mb) + ' مگابایت است.', [], ''); return; }
    setSlotPreview(i, file);
    state.uploads[i] = { pending: true };
    resetSetReport();
    renderBar();
    badge(i, 'در حال بررسی…');
    showCheck('', 'در حال بررسی کیفیت عکس… (رایگان)', [], '');

    var data = new FormData();
    data.append('image', file);
    data.append('role', i === 0 ? 'main' : 'angle');
    request(cfg.urls.preflight, { method: 'POST', body: data }).then(function (res) {
      if (!res.ok || !res.body.ok) {
        state.uploads[i] = null;
        badge(i, 'خطا', 'red');
        showCheck('red', res.body.message || 'بررسی عکس انجام نشد؛ دوباره امتحان کن.', [], '');
        renderBar();
        return;
      }
      var b = res.body;
      state.uploads[i] = { id: b.upload_id, verdict: b.verdict, fixedUrl: b.fixed_url, useFixed: !!b.fixed_url };
      var labels = { green: 'عکس عالی است', yellow: 'قابل استفاده', red: 'نامناسب' };
      badge(i, labels[b.verdict] || '', b.verdict);
      var title = b.verdict === 'green' ? 'عکس مناسب است.' : (b.verdict === 'yellow' ? 'عکس قابل استفاده است، ولی بهتر می‌شود:' : 'این عکس برای ساخت مناسب نیست؛ عکس دیگری بفرست. هزینه‌ای کسر نشد.');
      showCheck(b.verdict, title, b.issues || [], b.suggestion || '', i === 0 ? b : null);
      renderBar();
    }).catch(function () {
      state.uploads[i] = null;
      badge(i, 'خطا', 'red');
      showCheck('red', 'ارتباط با سرور برقرار نشد.', [], '');
      renderBar();
    });
  }

  function showCheck(tone, title, issues, suggestion, data) {
    var box = $('[data-check]');
    box.hidden = false;
    box.className = 'pp-check' + (tone ? ' is-' + tone : '');
    var html = '<strong>' + esc(title) + '</strong>';
    if (issues.length) html += '<ul>' + issues.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>';
    if (suggestion && tone !== 'green') html += '<div>' + esc(suggestion) + '</div>';
    if (data && data.fixed_url) {
      html += '<div class="pp-fix"><figure><img alt="عکس اصلی" src="' + esc(data.original_url) + '"><figcaption>قبل</figcaption></figure>' +
        '<figure><img alt="عکس اصلاح‌شده" src="' + esc(data.fixed_url) + '"><figcaption>اصلاح خودکار</figcaption></figure></div>' +
        '<label class="pp-switch"><input type="checkbox" data-use-fixed checked> از نسخه‌ی اصلاح‌شده استفاده کن</label>';
    }
    box.innerHTML = html;
    var toggle = box.querySelector('[data-use-fixed]');
    if (toggle) toggle.addEventListener('change', function () { if (state.uploads[0]) state.uploads[0].useFixed = toggle.checked; });
  }

  $$('[data-slot-input]').forEach(function (input) {
    var i = Number(input.getAttribute('data-slot-input'));
    input.addEventListener('change', function () { if (input.files && input.files[0]) upload(i, input.files[0]); });
    var drop = slotEl(i);
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-dragover'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-dragover'); }); });
    drop.addEventListener('drop', function (e) {
      e.preventDefault();
      var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (f && /^image\//.test(f.type)) upload(i, f);
    });
  });
  $$('[data-slot-remove]').forEach(function (btn) {
    btn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); clearSlot(Number(btn.closest('.pp-drop').getAttribute('data-slot'))); });
  });

  function uploadPayload() {
    return state.uploads.filter(function (u) { return u && u.id; }).map(function (u) {
      return { id: u.id, use_fixed: !!u.useFixed };
    });
  }

  function checkSet(silent) {
    var uploads = uploadPayload();
    if (!uploads.length || state.uploads.some(function (u) { return u && u.pending; })) return Promise.resolve(false);
    var btn = $('[data-check-set]');
    btn.disabled = true;
    btn.innerHTML = '<span class="pp-spin" aria-hidden="true"></span> در حال بررسی';
    return request(cfg.urls.preflight_set, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ uploads: uploads })
    }).then(function (res) {
      if (!res.ok || !res.body.ok) {
        state.setReport = null;
        showCheck('red', res.body.message || 'بررسی مجموعه انجام نشد.', [], '');
        return false;
      }
      state.setReport = res.body;
      var sheet = $('[data-product-sheet]');
      var image = $('[data-product-sheet-img]');
      var copy = $('[data-product-sheet-copy]');
      if (res.body.product_sheet_url) {
        image.src = res.body.product_sheet_url;
        sheet.hidden = false;
      }
      copy.innerHTML = '<strong>' + (res.body.usable ? 'پروداکت‌شیت آماده است' : 'مجموعه نیاز به اصلاح دارد') + '</strong>' +
        '<span>' + esc(res.body.suggestion || 'زوایای انتخابی برای ساخت آماده‌اند.') + '</span>';
      if (!silent || !res.body.usable) {
        showCheck(res.body.verdict, res.body.usable ? 'مجموعه‌ی عکس‌ها آماده است.' : 'این مجموعه هنوز برای ساخت مناسب نیست.', res.body.issues || [], res.body.suggestion || '');
      }
      return !!res.body.usable;
    }).catch(function () {
      state.setReport = null;
      showCheck('red', 'ارتباط با سرور برقرار نشد.', [], '');
      return false;
    }).then(function (usable) {
      btn.innerHTML = '<i class="fa-solid fa-magnifying-glass-chart"></i> بررسی مجموعه';
      renderBar();
      return usable;
    });
  }
  $('[data-check-set]').addEventListener('click', function () { checkSet(false); });

  /* ───── نوار پایین ───── */
  function canBuild() {
    var main = state.uploads[0];
    var pending = state.uploads.some(function (u) { return u && u.pending; });
    var hasRejected = state.uploads.some(function (u) { return u && u.verdict === 'red'; });
    return !state.building && main && main.id && !hasRejected && !pending && selectedKeys().length > 0;
  }

  function renderBar() {
    var n = selectedKeys().length;
    var credits = totalCredits();
    $('[data-bar-summary]').textContent = fa(n) + ' عکس • ' + fa(credits) + ' کردیت';
    var bal = $('[data-bar-balance]');
    if (!cfg.is_authenticated) {
      bal.innerHTML = 'برای ساخت <a href="' + esc(cfg.login_url) + '">وارد شو</a>';
    } else if (credits > state.balance) {
      bal.innerHTML = 'موجودی: ' + fa(state.balance) + ' کردیت · <a href="' + esc(cfg.pricing_url) + '">افزایش اعتبار</a>';
    } else {
      bal.textContent = 'موجودی: ' + fa(state.balance) + ' کردیت';
    }
    var btn = $('[data-build]');
    btn.disabled = !canBuild();
    if (!state.building) btn.querySelector('span').textContent = state.batch ? 'ساخت دوباره' : 'بساز';
    var setBtn = $('[data-check-set]');
    if (setBtn && !state.building) setBtn.disabled = !state.uploads.some(function (u) { return u && u.id; }) || state.uploads.some(function (u) { return u && u.pending; });
  }

  function showCreditAlert() {
    var modal = $('[data-credit-alert]');
    var count = affordableCount();
    $('[data-credit-alert-message]').textContent = count > 0
      ? 'اعتبار شما برای ساخت ' + fa(count) + ' عکس کافی است. چند شات را بردار یا اعتبارت را افزایش بده.'
      : 'اعتبار فعلی برای ساخت هیچ‌کدام از شات‌های انتخابی کافی نیست.';
    modal.hidden = false;
    var focusable = modal.querySelector('button, a');
    if (focusable) focusable.focus();
  }

  function hideCreditAlert() { $('[data-credit-alert]').hidden = true; }
  $$('[data-credit-alert-close]').forEach(function (el) { el.addEventListener('click', hideCreditAlert); });

  /* ───── اسلایدر خروجی؛ در همین صفحه هر ۴ ثانیه حرکت می‌کند ───── */
  function sliderItems() { return $$('[data-tile]'); }
  function stopSliderTimer() {
    if (state.sliderTimer) window.clearInterval(state.sliderTimer);
    state.sliderTimer = null;
  }
  function goToSlide(index, smooth) {
    var items = sliderItems();
    if (!items.length) return;
    state.sliderIndex = (index + items.length) % items.length;
    var track = $('[data-tiles]');
    track.style.transition = smooth === false || window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'none' : '';
    track.style.transform = 'translateX(-' + (state.sliderIndex * 100) + '%)';
    $$('[data-slider-dot]').forEach(function (dot, i) {
      dot.classList.toggle('is-active', i === state.sliderIndex);
      dot.setAttribute('aria-current', i === state.sliderIndex ? 'true' : 'false');
    });
    $('[data-slider-counter]').textContent = fa(state.sliderIndex + 1) + ' از ' + fa(items.length);
  }
  function renderSliderControls() {
    var items = sliderItems();
    var multi = items.length > 1;
    $('[data-slider-prev]').hidden = !multi;
    $('[data-slider-next]').hidden = !multi;
    $('[data-slider-footer]').hidden = !multi;
    var dots = $('[data-slider-dots]');
    dots.innerHTML = '';
    items.forEach(function (item, i) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('data-slider-dot', '');
      dot.setAttribute('aria-label', 'نمایش اسلاید ' + fa(i + 1));
      dot.addEventListener('click', function () { goToSlide(i, true); restartSliderTimer(); });
      dots.appendChild(dot);
    });
    if (state.sliderIndex >= items.length) state.sliderIndex = 0;
    goToSlide(state.sliderIndex, false);
  }
  function restartSliderTimer() {
    stopSliderTimer();
    if (!state.batch || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var open = state.batch.items.some(function (i) { return i.status === 'pending' || i.status === 'running'; });
    if (!open && sliderItems().length > 1) {
      state.sliderTimer = window.setInterval(function () { goToSlide(state.sliderIndex + 1, true); }, 4000);
    }
  }
  $('[data-slider-prev]').addEventListener('click', function () { goToSlide(state.sliderIndex - 1, true); restartSliderTimer(); });
  $('[data-slider-next]').addEventListener('click', function () { goToSlide(state.sliderIndex + 1, true); restartSliderTimer(); });
  $('[data-slider]').addEventListener('mouseenter', stopSliderTimer);
  $('[data-slider]').addEventListener('mouseleave', restartSliderTimer);
  $('[data-slider]').addEventListener('focusin', stopSliderTimer);
  $('[data-slider]').addEventListener('focusout', restartSliderTimer);

  /* ───── ساخت ───── */
  function tileHtml(item) {
    var ratio = (item.aspect_ratio || '4:5').split(':');
    var media = '<div class="pp-tile-media" style="aspect-ratio:' + ratio[0] + ' / ' + ratio[1] + '">';
    if (item.status === 'completed' && item.image_url) {
      media += '<img alt="' + esc(item.name) + '" src="' + esc(item.image_url) + '">';
    } else if (item.status === 'failed') {
      media += '<div class="pp-tile-error"><div>' + esc(item.error || 'ساخت این شات انجام نشد.') + '</div>' +
        (item.can_retry ? '<button type="button" class="pp-btn pp-btn-ghost" data-retry="' + item.id + '"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> دوباره</button>' : '') + '</div>';
    } else {
      media += '<span class="pp-tile-state">' + (item.status === 'running' ? 'در حال ساخت…' : 'در صف') + '</span>';
    }
    media += '</div>';
    var actions = '';
    if (item.status === 'completed' && item.image_url) {
      actions = '<a class="pp-icon-btn" href="' + esc(item.image_url) + '" download aria-label="دانلود ' + esc(item.name) + '"><i class="fa-solid fa-download"></i></a>' +
        '<span class="pp-icon-btn" aria-disabled="true" title="ساخت ویدیو از این شات — بزودی"><i class="fa-solid fa-film"></i></span>';
    }
    return media + '<div class="pp-tile-body"><span class="pp-tile-name">' + esc(item.name) +
      (item.status === 'completed' && item.qc && item.qc.passed === false ? ' <span class="pp-soon">بررسی کن</span>' : '') +
      '</span><span class="pp-tile-actions">' + actions + '</span></div>';
  }

  function renderTile(item) {
    var tile = $('[data-tile="' + item.id + '"]');
    if (!tile) {
      tile = document.createElement('article');
      tile.setAttribute('data-tile', item.id);
      $('[data-tiles]').appendChild(tile);
    }
    tile.className = 'pp-tile is-' + (item.status === 'pending' ? 'waiting' : item.status);
    tile.setAttribute('aria-busy', item.status === 'running' || item.status === 'pending' ? 'true' : 'false');
    tile.innerHTML = tileHtml(item);
    var retry = tile.querySelector('[data-retry]');
    if (retry) retry.addEventListener('click', function () { enqueueRetry(item.id); });
    renderSliderControls();
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
      ? 'در حال ساخت: ' + fa(done) + ' از ' + fa(items.length) + ' آماده شد'
      : (failed ? fa(done) + ' شات آماده شد؛ ' + fa(failed) + ' شات ساخته نشد و اعتبارش برگشت.' : 'همه‌ی ' + fa(done) + ' شات آماده است.');
    if (charged) text += ' · کسرشده: ' + fa(charged) + ' کردیت';
    if (refunded) text += ' · برگشتی: ' + fa(refunded);
    $('[data-results-status]').textContent = text;
    var dl = $('[data-download]');
    dl.hidden = done === 0 || open > 0;
    dl.href = state.batch.download_url;
    if (open === 0) restartSliderTimer();
  }

  function itemById(id) { return state.batch.items.filter(function (i) { return i.id === id; })[0]; }

  function enqueueRetry(id) {
    var item = itemById(id);
    item.status = 'running'; renderTile(item); updateResultsStatus();
    request(state.batch.run_urls[id], { method: 'POST' }).then(function (res) {
      if (res.body && res.body.item) Object.assign(item, res.body.item);
      else { item.status = 'failed'; item.error = (res.body && res.body.message) || 'ساخت این شات انجام نشد.'; item.can_retry = true; }
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

  function startBuild() {
    if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
    if (!canBuild()) return;
    if (totalCredits() > state.balance) { showCreditAlert(); return; }
    stopSliderTimer();
    state.building = true; renderBar();
    var uploads = uploadPayload();
    var shotRatios = {};
    selectedKeys().forEach(function (key) { shotRatios[key] = state.ratios[key]; });
    request(cfg.urls.batches, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ uploads: uploads, shots: selectedKeys(), quality_level: state.quality, shot_ratios: shotRatios })
    }).then(function (res) {
      if (!res.ok || !res.body.ok) {
        state.building = false; renderBar();
        showCheck('red', res.body.message || 'ساخت شروع نشد؛ دوباره امتحان کن.', [], '');
        return;
      }
      state.batch = res.body.batch;
      var results = $('[data-results]');
      results.hidden = false;
      $('[data-tiles]').innerHTML = '';
      state.batch.items.forEach(renderTile);
      updateResultsStatus();
      if (res.body.message) $('[data-results-status]').textContent = res.body.message;
      results.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
      pollBatch(1200);
    }).catch(function () {
      state.building = false; renderBar();
      showCheck('red', 'ارتباط با سرور برقرار نشد.', [], '');
    });
  }
  function build() {
    if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
    if (!canBuild()) return;
    if (totalCredits() > state.balance) { showCreditAlert(); return; }
    if (state.setReport && state.setReport.usable) { startBuild(); return; }
    checkSet(true).then(function (usable) { if (usable) startBuild(); });
  }
  $('[data-build]').addEventListener('click', build);

  renderShots();
  renderQualities();
  renderBar();
})();
