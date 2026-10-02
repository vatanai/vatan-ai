/*
 * صفحه‌ی «بساز» پک شات محصول.
 * جریان: آپلود ← فیلتر کیفیت (بدون هزینه) ← انتخاب شات‌ها ← ساخت batch
 *        ← اجرای هر شات با یک درخواست جدا (هم‌زمانی محدود) ← کاشی‌های پیش‌رونده.
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
    ratio: cfg.default_aspect_ratio || '4:5',
    balance: Number(cfg.balance || 0),
    building: false,
    batch: null
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
  shots.forEach(function (s) { if (s.is_default) state.selected[s.key] = true; });
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
      label.innerHTML =
        '<input type="checkbox" ' + (on ? 'checked' : '') + (limitReached ? ' disabled' : '') + ' aria-label="' + esc(s.name) + '">' +
        '<span class="pp-shot-media">' + (s.sample_url ? '<img loading="lazy" alt="" src="' + esc(s.sample_url) + '">' : '<i class="fa-solid fa-image" aria-hidden="true"></i>') + '</span>' +
        '<span class="pp-shot-tick" aria-hidden="true"><i class="fa-solid fa-check"></i></span>' +
        '<span class="pp-shot-body"><span class="pp-shot-name">' + esc(s.name) + '</span><span class="pp-shot-meta" style="display:block">' + fa(s.credits) + ' کردیت' + (s.tags && s.tags.length ? ' · ' + esc(s.tags.slice(0, 2).join('، ')) : '') + '</span></span>';
      label.querySelector('input').addEventListener('change', function (e) {
        if (e.target.checked) state.selected[s.key] = true; else delete state.selected[s.key];
        renderShots(); renderBar();
      });
      wrap.appendChild(label);
    });
    var more = $('[data-more]');
    more.hidden = !hasHidden;
    more.setAttribute('aria-expanded', state.showAll ? 'true' : 'false');
    more.innerHTML = state.showAll
      ? '<i class="fa-solid fa-compress" aria-hidden="true"></i> فقط شات‌های انتخاب‌شده'
      : '<i class="fa-solid fa-sliders" aria-hidden="true"></i> شات‌ها را شخصی‌سازی کن (+' + fa(shots.filter(function (s) { return !s.is_default; }).length) + ')';
  }
  $('[data-more]').addEventListener('click', function () { state.showAll = !state.showAll; renderShots(); });

  function selectedKeys() { return shots.filter(function (s) { return state.selected[s.key]; }).map(function (s) { return s.key; }); }
  function totalCredits() { return shots.reduce(function (sum, s) { return sum + (state.selected[s.key] ? Number(s.credits || 0) : 0); }, 0); }

  /* ───── نسبت تصویر ───── */
  var RATIO_LABELS = { '4:5': ['پست اینستاگرام', 18, 22], '1:1': ['مربع', 20, 20], '9:16': ['استوری و ریلز', 14, 24] };
  function renderRatios() {
    var wrap = $('[data-ratios]');
    wrap.innerHTML = '';
    (cfg.aspect_ratios || ['4:5']).forEach(function (r) {
      var meta = RATIO_LABELS[r] || [r, 18, 18];
      var label = document.createElement('label');
      label.className = 'pp-ratio' + (state.ratio === r ? ' is-selected' : '');
      label.innerHTML = '<input type="radio" name="pp-ratio" value="' + esc(r) + '" ' + (state.ratio === r ? 'checked' : '') + '>' +
        '<span class="pp-ratio-shape" style="width:' + meta[1] + 'px;height:' + meta[2] + 'px" aria-hidden="true"></span>' +
        '<span><strong>' + esc(meta[0]) + '</strong> <small dir="ltr">' + esc(r) + '</small></span>';
      label.querySelector('input').addEventListener('change', function () { state.ratio = r; renderRatios(); });
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
    renderBar();
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
    renderBar();
    if (i === 0) { badge(0, 'در حال بررسی عکس…'); showCheck('', 'در حال بررسی کیفیت عکس… (رایگان)', [], ''); }

    var data = new FormData();
    data.append('image', file);
    data.append('role', i === 0 ? 'main' : 'angle');
    request(cfg.urls.preflight, { method: 'POST', body: data }).then(function (res) {
      if (!res.ok || !res.body.ok) {
        state.uploads[i] = null;
        if (i === 0) { badge(0, 'خطا', 'red'); showCheck('red', res.body.message || 'بررسی عکس انجام نشد؛ دوباره امتحان کن.', [], ''); }
        renderBar();
        return;
      }
      var b = res.body;
      state.uploads[i] = { id: b.upload_id, verdict: b.verdict, fixedUrl: b.fixed_url, useFixed: !!b.fixed_url };
      if (i === 0) {
        var labels = { green: 'عکس عالی است', yellow: 'قابل استفاده', red: 'نامناسب' };
        badge(0, labels[b.verdict] || '', b.verdict);
        var title = b.verdict === 'green' ? 'عکس مناسب است.' : (b.verdict === 'yellow' ? 'عکس قابل استفاده است، ولی بهتر می‌شود:' : 'این عکس برای ساخت مناسب نیست؛ عکس دیگری بفرست. هزینه‌ای کسر نشد.');
        showCheck(b.verdict, title, b.issues || [], b.suggestion || '', b);
      }
      renderBar();
    }).catch(function () {
      state.uploads[i] = null;
      if (i === 0) showCheck('red', 'ارتباط با سرور برقرار نشد.', [], '');
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

  /* ───── نوار پایین ───── */
  function canBuild() {
    var main = state.uploads[0];
    var pending = state.uploads.some(function (u) { return u && u.pending; });
    return !state.building && main && main.id && main.verdict !== 'red' && !pending && selectedKeys().length > 0;
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
  }

  /* ───── ساخت ───── */
  function tileHtml(item) {
    var ratio = (state.batch && state.batch.aspect_ratio || '4:5').split(':');
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
    if (retry) retry.addEventListener('click', function () { runQueue([item.id]); });
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
  }

  function itemById(id) { return state.batch.items.filter(function (i) { return i.id === id; })[0]; }

  function runOne(id) {
    var item = itemById(id);
    item.status = 'running'; renderTile(item); updateResultsStatus();
    return request(state.batch.run_urls[id], { method: 'POST' }).then(function (res) {
      if (res.body && res.body.item) Object.assign(item, res.body.item);
      else { item.status = 'failed'; item.error = (res.body && res.body.message) || 'ساخت این شات انجام نشد.'; item.can_retry = true; }
      if (typeof res.body.balance === 'number') state.balance = res.body.balance;
      if (res.body && res.body.error_code === 'INSUFFICIENT_CREDITS') item.error = 'اعتبار برای این شات کافی نیست. هزینه‌ای کسر نشد.';
    }).catch(function () {
      item.status = 'failed'; item.error = 'ارتباط قطع شد. اگر شات ساخته شده باشد با تازه‌سازی صفحه دیده می‌شود.'; item.can_retry = true;
    }).then(function () { renderTile(item); updateResultsStatus(); renderBar(); });
  }

  function runQueue(ids) {
    var queue = ids.slice();
    var workers = Math.max(1, Math.min(3, Number(cfg.concurrency || 1)));
    state.building = true; renderBar();
    var btn = $('[data-build]');
    btn.querySelector('span').innerHTML = '<span class="pp-spin" aria-hidden="true"></span> در حال ساخت';
    function next() {
      var id = queue.shift();
      if (id == null) return Promise.resolve();
      return runOne(id).then(next);
    }
    var pool = [];
    for (var w = 0; w < workers; w++) pool.push(next());
    return Promise.all(pool).then(function () { state.building = false; renderBar(); });
  }

  function build() {
    if (!cfg.is_authenticated) { window.location.href = cfg.login_url; return; }
    if (!canBuild()) return;
    state.building = true; renderBar();
    var uploads = state.uploads.filter(function (u) { return u && u.id; }).map(function (u, idx) {
      return { id: u.id, use_fixed: idx === 0 ? !!u.useFixed : false };
    });
    request(cfg.urls.batches, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ uploads: uploads, shots: selectedKeys(), aspect_ratio: state.ratio })
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
      if (res.body.warning) $('[data-results-status]').textContent = res.body.warning;
      results.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
      runQueue(state.batch.items.map(function (i) { return i.id; }));
    }).catch(function () {
      state.building = false; renderBar();
      showCheck('red', 'ارتباط با سرور برقرار نشد.', [], '');
    });
  }
  $('[data-build]').addEventListener('click', build);

  window.addEventListener('beforeunload', function (e) {
    if (state.building) { e.preventDefault(); e.returnValue = ''; }
  });

  renderShots();
  renderRatios();
  renderBar();
})();
