/* کارخانه‌ی محتوای وطن — ویرایشگر صفحه‌ی پست: صحنه‌ها، استوری‌ها، نقشه‌ی ساخت و پرامپت جامع */
(function () {
  var D = window.CF_ITEM; if (!D) return;
  var form = document.getElementById('cf-item-form'); if (!form) return;
  var fa = function (x) { return String(x).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }).replace('.', '٫'); };
  var el = function (tag, attrs, html) { var e = document.createElement(tag); for (var k in (attrs || {})) { if (k === 'class') e.className = attrs[k]; else e.setAttribute(k, attrs[k]); } if (html != null) e.innerHTML = html; return e; };
  var esc = function (x) { var d = document.createElement('div'); d.textContent = x == null ? '' : String(x); return d.innerHTML; };
  var imgUrl = function (v) { if (!v) return ''; if (v.indexOf('storage:') === 0) return D.storage + v.slice(8); if (/^https?:/.test(v)) return v; return ''; };
  var dirty = false; var markDirty = function () { dirty = true; var t = document.querySelector('[data-dirty]'); if (t) { t.textContent = '● تغییرات ذخیره نشده'; t.style.color = 'var(--warning)'; } };
  form.addEventListener('input', markDirty); form.addEventListener('change', markDirty);
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  /* ── تعریف انواع ── */
  var BG = [['dark', 'تیره'], ['light', 'روشن'], ['brand', 'برند']];
  var F = {
    kicker: ['بالانویس', 'text'], text: ['متن اصلی', 'area'], lines: ['خط‌ها (هر خط جدا)', 'lines'], hl: ['کلمه‌های برجسته (با ، جدا)', 'csv'],
    bg: ['پس‌زمینه', 'bg'], size: ['اندازه‌ی فونت', 'num'], image: ['تصویر', 'img'], tag: ['برچسب', 'text'], before: ['تصویر قبل', 'img'], after: ['تصویر بعد', 'img'],
    labelBefore: ['برچسب قبل', 'text'], labelAfter: ['برچسب بعد', 'text'], images: ['تصویرها (هر خط یکی)', 'imgs'], value: ['عدد', 'num'], prefix: ['پیشوند', 'text'],
    suffix: ['پسوند', 'text'], label: ['توضیح عدد', 'text'], title: ['عنوان', 'text'], items: ['آیتم‌ها (هر خط یکی)', 'lines'], mark: ['علامت', 'mark'],
    by: ['گوینده', 'text'], sub: ['زیرنویس', 'text'], keyword: ['کلمه‌ی کامنت', 'text'], titleHl: ['برجسته‌های عنوان (با ، جدا)', 'csv'], body: ['متن', 'area'],
    badge: ['نشان', 'text'], light: ['زمینه‌ی روشن', 'bool'], kind: ['نوع', 'kind']
  };
  var REEL = {
    text: ['متن', ['kicker', 'text', 'lines', 'hl', 'bg', 'size']], card: ['کارت محصول', ['image', 'tag', 'text', 'hl', 'bg']],
    split: ['قبل/بعد', ['before', 'after', 'labelBefore', 'labelAfter', 'text', 'hl']], grid: ['شبکه‌ی تصویر', ['images', 'text', 'hl', 'bg']],
    stat: ['عدد/آمار', ['value', 'prefix', 'suffix', 'label', 'text', 'bg']], list: ['فهرست', ['title', 'items', 'mark', 'bg']],
    quote: ['نقل‌قول', ['text', 'by', 'bg']], image: ['تصویر تمام‌قد', ['image', 'text', 'hl', 'tag']], cta: ['دعوت به اقدام', ['text', 'sub', 'keyword', 'bg']]
  };
  var SLIDE = {
    cover: ['جلد', ['title', 'titleHl', 'body', 'image', 'light']], tip: ['نکته', ['title', 'titleHl', 'body', 'image', 'light']],
    compare: ['مقایسه', ['title', 'titleHl', 'before', 'after', 'light']], list: ['فهرست', ['title', 'titleHl', 'items', 'light']],
    image: ['تصویر', ['title', 'titleHl', 'image', 'light']], cta: ['دعوت', ['title', 'titleHl', 'body', 'light']]
  };
  var POSTER = {poster: ['پوستر', ['title', 'titleHl', 'body', 'image', 'badge', 'light']]};
  var STORY = {teaser: 'تیزر پست', poll: 'نظرسنجی', question: 'سؤال', tip: 'نکته', cta: 'دعوت/ثبت‌نام'};
  var fmt = function () { return (form.elements.format || {}).value || D.format; };
  var TYPES = function () { return fmt() === 'اسلایدی' ? SLIDE : fmt() === 'پست' ? POSTER : REEL; };
  var typeKey = function () { return fmt() === 'ریلز' ? 'type' : 'kind'; };

  /* ── ورودی یک فیلد ── */
  function input(obj, key, onCh) {
    var def = F[key] || [key, 'text'], kind = def[1], v = obj[key], w = el('label', {class: 'cf-field'}), lab = el('span', null, esc(def[0])); w.appendChild(lab);
    var i;
    if (kind === 'area') { i = el('textarea'); i.value = v || ''; i.style.minHeight = '54px'; }
    else if (kind === 'lines' || kind === 'imgs') { i = el('textarea'); i.value = (v || []).join('\n'); i.style.minHeight = '54px'; if (kind === 'imgs') i.dir = 'ltr'; }
    else if (kind === 'csv') { i = el('input'); i.value = (v || []).join('، '); }
    else if (kind === 'bg' || kind === 'mark') {
      i = el('select'); var opts = kind === 'bg' ? BG : [['check', 'تیک'], ['cross', 'ضربدر'], ['num', 'شماره']];
      i.appendChild(el('option', {value: ''}, '—')); opts.forEach(function (o) { var op = el('option', {value: o[0]}, o[1]); if (v === o[0]) op.selected = true; i.appendChild(op); });
    } else if (kind === 'num') { i = el('input', {type: 'number', step: 'any'}); i.value = v == null ? '' : v; }
    else if (kind === 'bool') { i = el('input', {type: 'checkbox'}); i.checked = !!v; w.style.flexDirection = 'row'; w.style.alignItems = 'center'; }
    else if (kind === 'img') { i = el('input', {list: 'cf-images', dir: 'ltr'}); i.value = v || ''; }
    else { i = el('input'); i.value = v == null ? '' : v; }
    var thumb = null;
    if (kind === 'img') { thumb = el('img', {class: 'cf-scene-thumb', alt: ''}); var u = imgUrl(v); if (u) thumb.src = u; else thumb.hidden = true; w.appendChild(thumb); }
    var read = function () {
      var x;
      if (kind === 'lines' || kind === 'imgs') x = i.value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
      else if (kind === 'csv') x = i.value.split(/[،,]/).map(function (s) { return s.trim(); }).filter(Boolean);
      else if (kind === 'num') x = i.value === '' ? undefined : Number(i.value);
      else if (kind === 'bool') x = i.checked;
      else x = i.value === '' ? undefined : i.value;
      if (x === undefined || (Array.isArray(x) && !x.length) || x === false) delete obj[key]; else obj[key] = x;
      if (thumb) { var u2 = imgUrl(obj[key]); thumb.hidden = !u2; if (u2) thumb.src = u2; }
      onCh && onCh();
    };
    i.addEventListener('input', read); i.addEventListener('change', read);
    w.appendChild(i);
    return w;
  }

  /* ── صحنه‌ها ── */
  var scenes = JSON.parse(JSON.stringify(D.scenes || []));
  var box = document.getElementById('cf-scenes'), totalEl = document.querySelector('[data-total]');
  function total() {
    if (fmt() !== 'ریلز') { totalEl.textContent = fa(scenes.length) + (fmt() === 'اسلایدی' ? ' اسلاید' : ' طرح'); return; }
    var f = scenes.reduce(function (a, s) { return a + (Number(s.dur) || 0); }, 0);
    totalEl.textContent = fa(scenes.length) + ' صحنه · ' + fa(Math.round(f / 3) / 10) + ' ثانیه';
  }
  function renderScenes() {
    box.innerHTML = ''; var T = TYPES(), tk = typeKey();
    document.querySelector('[data-scene-title]').textContent = fmt() === 'ریلز' ? 'صحنه‌ها و متن‌های روی ویدیو' : fmt() === 'اسلایدی' ? 'اسلایدها و متن‌ها' : 'طرح پست';
    scenes.forEach(function (s, idx) {
      var card = el('div', {class: 'cf-scene'}), head = el('div', {class: 'cf-between'});
      var left = el('div', {class: 'cf-row'}); left.appendChild(el('b', {class: 'cf-scene-n'}, fa(idx + 1)));
      var sel = el('select', {class: 'cf-input', style: 'width:auto'}); Object.keys(T).forEach(function (k) { var o = el('option', {value: k}, T[k][0]); if ((s[tk] || Object.keys(T)[0]) === k) o.selected = true; sel.appendChild(o); });
      sel.addEventListener('change', function () { s[tk] = sel.value; markDirty(); renderScenes(); }); left.appendChild(sel);
      if (fmt() === 'ریلز') {
        var dl = el('label', {class: 'cf-row cf-muted', style: 'font-size:12px'}, 'مدت (ثانیه)'); var di = el('input', {type: 'number', step: '0.1', min: '0.3', class: 'cf-input', style: 'width:84px'});
        di.value = Math.round((s.dur || 45) / 3) / 10; di.addEventListener('input', function () { s.dur = Math.max(9, Math.round(Number(di.value || 0) * 30)); total(); markDirty(); }); dl.appendChild(di); left.appendChild(dl);
      }
      head.appendChild(left);
      var tools = el('div', {class: 'cf-row'});
      [['fa-arrow-up', 'بالا', function () { if (idx > 0) { scenes.splice(idx - 1, 0, scenes.splice(idx, 1)[0]); } }],
       ['fa-arrow-down', 'پایین', function () { if (idx < scenes.length - 1) { scenes.splice(idx + 1, 0, scenes.splice(idx, 1)[0]); } }],
       ['fa-clone', 'تکثیر', function () { scenes.splice(idx + 1, 0, JSON.parse(JSON.stringify(s))); }],
       ['fa-trash', 'حذف', function () { scenes.splice(idx, 1); }]].forEach(function (b) {
        var bt = el('button', {type: 'button', class: 'cf-btn cf-btn-sm' + (b[0] === 'fa-trash' ? ' cf-btn-danger' : ''), title: b[1]}, '<i class="fa-solid ' + b[0] + '"></i>');
        bt.addEventListener('click', function () { b[2](); markDirty(); renderScenes(); }); tools.appendChild(bt);
      });
      head.appendChild(tools); card.appendChild(head);
      var grid = el('div', {class: 'cf-grid cf-g3', style: 'margin-top:10px;gap:10px'});
      var def = T[s[tk]] || T[Object.keys(T)[0]];
      def[1].forEach(function (k) { var f = input(s, k, total); if ((F[k] || [])[1] === 'area' || (F[k] || [])[1] === 'lines' || (F[k] || [])[1] === 'imgs') f.style.gridColumn = 'span 2'; grid.appendChild(f); });
      card.appendChild(grid); box.appendChild(card);
    });
    if (!scenes.length) box.appendChild(el('div', {class: 'cf-empty'}, '<i class="fa-solid fa-film"></i>صحنه‌ای نیست؛ از پایین اضافه کنید یا با پرامپت جامع بنویسید.'));
    var ts = document.getElementById('cf-scene-type'); ts.innerHTML = ''; Object.keys(T).forEach(function (k) { ts.appendChild(el('option', {value: k}, T[k][0])); });
    total();
  }
  document.querySelector('[data-add-scene]').addEventListener('click', function () {
    var t = document.getElementById('cf-scene-type').value, s = {}; s[typeKey()] = t; if (fmt() === 'ریلز') { s.dur = t === 'cta' ? 80 : 45; s.bg = 'dark'; } s.text = ''; s.title = fmt() === 'ریلز' ? undefined : '';
    if (s.title === undefined) delete s.title; scenes.push(s); markDirty(); renderScenes();
  });
  if (form.elements.format) form.elements.format.addEventListener('change', renderScenes);
  renderScenes();

  /* ── استوری‌ها ── */
  var stories = JSON.parse(JSON.stringify(D.stories || [])), sbox = document.getElementById('cf-stories');
  function renderStories() {
    sbox.innerHTML = '';
    stories.forEach(function (s, idx) {
      var c = el('div', {class: 'cf-scene'}), h = el('div', {class: 'cf-between'});
      var sel = el('select', {class: 'cf-input', style: 'width:auto'}); Object.keys(STORY).forEach(function (k) { var o = el('option', {value: k}, STORY[k]); if (s.kind === k) o.selected = true; sel.appendChild(o); });
      sel.addEventListener('change', function () { s.kind = sel.value; markDirty(); });
      var l = el('div', {class: 'cf-row'}); l.appendChild(el('b', {class: 'cf-scene-n'}, fa(idx + 1))); l.appendChild(sel); h.appendChild(l);
      var del = el('button', {type: 'button', class: 'cf-btn cf-btn-sm cf-btn-danger', title: 'حذف'}, '<i class="fa-solid fa-trash"></i>'); del.addEventListener('click', function () { stories.splice(idx, 1); markDirty(); renderStories(); }); h.appendChild(del);
      c.appendChild(h); var g = el('div', {class: 'cf-stack', style: 'gap:8px;margin-top:8px'});
      ['title', 'titleHl', 'sub', 'badge', 'image'].forEach(function (k) { g.appendChild(input(s, k)); }); c.appendChild(g); sbox.appendChild(c);
    });
    document.querySelector('[data-add-story]').hidden = stories.length >= 3;
  }
  document.querySelector('[data-add-story]').addEventListener('click', function () { stories.push({kind: 'teaser', title: ''}); markDirty(); renderStories(); });
  renderStories();

  /* ── نقشه‌ی ساخت ── */
  var plan = JSON.parse(JSON.stringify(D.plan || [])), pbody = document.getElementById('cf-plan');
  function renderPlan() {
    pbody.innerHTML = '';
    plan.forEach(function (s, idx) {
      var tr = el('tr');
      ['step', 'who', 'where', 'prompt', 'cost'].forEach(function (k) {
        var td = el('td'), i = el('input', {class: 'cf-input'}); i.value = s[k] || ''; if (k === 'prompt') { i.dir = 'ltr'; i.style.width = '110px'; }
        i.addEventListener('input', function () { s[k] = i.value; markDirty(); }); td.appendChild(i);
        if (k === 'prompt' && s.prompt && s.prompt !== '—') td.appendChild(el('a', {href: D.promptsUrl + '?layer=' + encodeURIComponent(s.prompt), style: 'font-size:11px;color:var(--primary)'}, 'باز کردن'));
        tr.appendChild(td);
      });
      var td = el('td'), del = el('button', {type: 'button', class: 'cf-btn cf-btn-sm cf-btn-danger'}, '<i class="fa-solid fa-trash"></i>');
      del.addEventListener('click', function () { plan.splice(idx, 1); markDirty(); renderPlan(); }); td.appendChild(del); tr.appendChild(td); pbody.appendChild(tr);
    });
  }
  document.querySelector('[data-add-plan]').addEventListener('click', function () { plan.push({step: '', who: '', where: '', prompt: '', cost: ''}); markDirty(); renderPlan(); });
  renderPlan();

  /* ── پرامپت جامع ── */
  var P = JSON.parse(JSON.stringify(D.prompt || {sections: []})), psBox = document.getElementById('cf-psections'), activeTa = null, pChanged = false;
  function renderPrompt() {
    psBox.innerHTML = '';
    (P.sections || []).forEach(function (s, idx) {
      var c = el('div', {class: 'cf-scene' + (s.on === false ? ' is-off' : '')}), h = el('div', {class: 'cf-between'});
      var l = el('div', {class: 'cf-row', style: 'flex:1;min-width:0'}), tg = el('input', {type: 'checkbox', title: 'روشن/خاموش'}); tg.checked = s.on !== false;
      tg.addEventListener('change', function () { s.on = tg.checked; pChanged = true; markDirty(); c.classList.toggle('is-off', !tg.checked); });
      var ti = el('input', {class: 'cf-input', style: 'font-weight:800;flex:1;min-width:160px'}); ti.value = s.title || ''; ti.addEventListener('input', function () { s.title = ti.value; pChanged = true; });
      l.appendChild(tg); l.appendChild(ti); h.appendChild(l);
      var tools = el('div', {class: 'cf-row'});
      [['fa-arrow-up', function () { if (idx > 0) P.sections.splice(idx - 1, 0, P.sections.splice(idx, 1)[0]); }], ['fa-arrow-down', function () { if (idx < P.sections.length - 1) P.sections.splice(idx + 1, 0, P.sections.splice(idx, 1)[0]); }],
       ['fa-trash', function () { P.sections.splice(idx, 1); }]].forEach(function (b) {
        var bt = el('button', {type: 'button', class: 'cf-btn cf-btn-sm' + (b[0] === 'fa-trash' ? ' cf-btn-danger' : '')}, '<i class="fa-solid ' + b[0] + '"></i>');
        bt.addEventListener('click', function () { b[1](); pChanged = true; markDirty(); renderPrompt(); }); tools.appendChild(bt);
      });
      h.appendChild(tools); c.appendChild(h);
      var ta = el('textarea', {class: 'cf-input', style: 'margin-top:8px;min-height:110px;line-height:1.9;font-size:12.5px'}); ta.value = s.body || '';
      ta.addEventListener('input', function () { s.body = ta.value; pChanged = true; }); ta.addEventListener('focus', function () { activeTa = ta; });
      c.appendChild(ta); psBox.appendChild(c);
    });
  }
  document.querySelector('[data-add-psec]').addEventListener('click', function () { P.sections.push({title: 'بخش تازه', body: '', on: true}); pChanged = true; markDirty(); renderPrompt(); });
  var sys = document.getElementById('cf-p-system'); if (sys) { sys.addEventListener('input', function () { P.system = sys.value; pChanged = true; }); sys.addEventListener('focus', function () { activeTa = sys; }); }
  document.querySelectorAll('[data-ins]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (!activeTa) return; var t = '{{' + b.getAttribute('data-ins') + '}}', st = activeTa.selectionStart || 0;
      activeTa.value = activeTa.value.slice(0, st) + t + activeTa.value.slice(activeTa.selectionEnd || st); activeTa.dispatchEvent(new Event('input')); activeTa.focus();
    });
  });
  renderPrompt();

  /* ── ذخیره ── */
  form.addEventListener('submit', function () {
    form.elements.scenes_json.value = JSON.stringify(scenes);
    form.elements.story_specs_json.value = JSON.stringify(stories);
    form.elements.plan_json.value = JSON.stringify(plan);
    form.elements.prompt_json.value = pChanged ? JSON.stringify(P) : '';
    dirty = false;
  });

  /* ── پیش‌نمایش موزیک و شمارش کپشن ── */
  var ms = document.querySelector('[data-cf-music]'), mp = document.querySelector('[data-cf-music-player]');
  if (ms && mp) ms.addEventListener('change', function () { var o = ms.selectedOptions[0]; var src = o && o.getAttribute('data-src'); if (src) { mp.src = src; } else { mp.removeAttribute('src'); } });
  var cap = form.elements.caption, cc = document.querySelector('[data-cap-count]');
  var cnt = function () { if (cc && cap) cc.textContent = fa(cap.value.length) + ' نویسه از ۲۲۰۰'; }; if (cap) { cap.addEventListener('input', cnt); cnt(); }
  document.querySelectorAll('[data-cf-img]').forEach(function (i) { /* datalist کافی است */ });

  /* ── وضعیت زنده‌ی کارها ── */
  var jobsBox = document.getElementById('cf-item-jobs'), agent = document.querySelector('[data-agent]'), wasBusy = D.busy;
  function poll() {
    fetch(D.status, {headers: {Accept: 'application/json'}, credentials: 'same-origin'}).then(function (r) { return r.json(); }).then(function (s) {
      if (agent) { agent.classList.toggle('is-on', !!s.agent); agent.lastChild.textContent = s.agent ? ' ایجنت آنلاین' : ' ایجنت خاموش'; }
      jobsBox.innerHTML = s.jobs.map(function (j) {
        return '<div class="cf-job"><span><b>' + esc(j.type) + '</b> <span class="cf-muted">#' + fa(j.id) + ' · ' + esc(j.message || '') + ' · ' + esc(j.at || '') + '</span></span><span class="cf-pill' +
          (j.status === 'done' ? ' is-success' : j.status === 'failed' ? ' is-danger' : j.status === 'running' ? ' is-warning' : '') + '">' + esc(j.status_fa) + '</span>' +
          (j.status === 'queued' || j.status === 'running' ? '<span class="cf-job-bar"><span style="width:' + (j.progress || 0) + '%"></span></span>' : '') + '</div>';
      }).join('') || '<div class="cf-muted" style="font-size:12px">هنوز کاری ثبت نشده.</div>';
      if (wasBusy && !s.busy && !dirty) { location.reload(); return; }
      wasBusy = s.busy; setTimeout(poll, s.busy ? 3000 : 12000);
    }).catch(function () { setTimeout(poll, 15000); });
  }
  setTimeout(poll, 1500);
})();
