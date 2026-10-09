/* مینی‌اپ تلگرام «ثبت پست» — اینستاگرام هوشمند وطن (بدون وابستگی؛ فقط Telegram WebApp SDK) */
(function () {
  'use strict';

  var tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
  var CFG = window.TGA || {};
  var app = document.getElementById('app');
  var toastEl = document.getElementById('toast');

  var S = {
    boot: null, posts: [], filter: 'all', view: 'list',
    post: null, c: null, initial: '', intent: 'active', fromDeepLink: false, busy: false, showMore: false,
  };

  /* ───────────── ابزارها ───────────── */
  var ICONS = {
    back: '<path d="M9 6l6 6-6 6"/>',
    sync: '<path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 4v5h-5"/>',
    spark: '<path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    x: '<path d="M18 6L6 18M6 6l12 12"/>',
    check: '<path d="M20 6L9 17l-5-5"/>',
    key: '<circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3L21 2M16 7l3 3M18 5l2 2"/>',
    comment: '<path d="M21 11.5a8.4 8.4 0 0 1-12.2 7.5L3 21l2-5.8A8.4 8.4 0 1 1 21 11.5z"/>',
    send: '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
    user: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
    rocket: '<path d="M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2.1-.1-2.9a2.2 2.2 0 0 0-2.9-.1z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.9A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22.4 22.4 0 0 1-4 2z"/><path d="M9 12H4s.6-3 2-4c1.6-1.1 5 0 5 0M12 15v5s3-.6 4-2c1.1-1.6 0-5 0-5"/>',
    image: '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/>',
    bag: '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
    chevron: '<path d="M6 9l6 6 6-6"/>',
    left: '<path d="M15 18l-6-6 6-6"/>',
    ext: '<path d="M7 17L17 7M8 7h9v9"/>',
    pause: '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>',
    play: '<path d="M6 4l14 8-14 8z"/>',
    alert: '<path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
    insta: '<rect x="3" y="3" width="18" height="18" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.1" class="fill"/>',
  };
  function ic(name) { return '<svg viewBox="0 0 24 24" aria-hidden="true">' + (ICONS[name] || '') + '</svg>'; }
  function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fa(n) { return Number(n || 0).toLocaleString('fa-IR'); }
  function $(sel, root) { return (root || app).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || app).querySelectorAll(sel)); }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function get(obj, path) { return path.split('.').reduce(function (o, k) { return o == null ? undefined : o[k]; }, obj); }
  function set(obj, path, val) {
    var ks = path.split('.'); var o = obj;
    for (var i = 0; i < ks.length - 1; i++) { if (o[ks[i]] == null || typeof o[ks[i]] !== 'object') o[ks[i]] = {}; o = o[ks[i]]; }
    o[ks[ks.length - 1]] = val;
  }
  function haptic(kind) {
    try {
      if (!tg || !tg.HapticFeedback) return;
      if (kind === 'ok' || kind === 'error' || kind === 'warning') tg.HapticFeedback.notificationOccurred(kind === 'ok' ? 'success' : kind);
      else tg.HapticFeedback.impactOccurred(kind || 'light');
    } catch (e) {}
  }
  var toastTimer;
  function toast(msg, kind) {
    toastEl.textContent = msg; toastEl.className = 'tga-toast' + (kind ? ' ' + kind : ''); toastEl.hidden = false;
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { toastEl.hidden = true; }, 3200);
  }
  function confirmBox(msg, cb) {
    if (tg && tg.showConfirm && tg.isVersionAtLeast && tg.isVersionAtLeast('6.2')) tg.showConfirm(msg, function (ok) { cb(!!ok); });
    else cb(window.confirm(msg));
  }
  function ago(iso) {
    if (!iso) return '';
    var s = (Date.now() - new Date(iso).getTime()) / 1000;
    var rtf = new Intl.RelativeTimeFormat('fa', { numeric: 'auto' });
    if (s < 3600) return rtf.format(-Math.max(1, Math.round(s / 60)), 'minute');
    if (s < 86400) return rtf.format(-Math.round(s / 3600), 'hour');
    if (s < 86400 * 30) return rtf.format(-Math.round(s / 86400), 'day');
    return new Date(iso).toLocaleDateString('fa-IR', { month: 'long', day: 'numeric' });
  }
  function norm(t) {
    var map = { 'ي': 'ی', 'ك': 'ک', 'ى': 'ی', 'ة': 'ه', 'ۀ': 'ه', 'أ': 'ا', 'إ': 'ا', 'ؤ': 'و', '‌': ' ', '‍': '', 'ـ': '' };
    t = String(t || '').replace(/[يكىةۀأإؤ‌‍ـ]/g, function (c) { return map[c]; });
    t = t.replace(/[۰-۹]/g, function (d) { return String(d.charCodeAt(0) - 1776); }).replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 1632); });
    t = t.replace(/[ً-ٰٟ]/g, '').toLowerCase().replace(/[^\p{L}\p{N}\s]+/gu, ' ');
    return t.replace(/\s+/g, ' ').trim();
  }
  function kwMatch(text, kw) {
    var h = norm(text), n = norm(kw.keyword);
    if (!n) return false;
    if (kw.match_mode === 'exact') return h === n;
    if (kw.match_mode === 'word') return (' ' + h + ' ').indexOf(' ' + n + ' ') !== -1;
    if (kw.match_mode === 'pattern') {
      var re = new RegExp('^' + n.split('*').map(function (p) { return p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('.*') + '$');
      return re.test(h) || h.indexOf(n.replace(/\*/g, '')) !== -1;
    }
    return h.indexOf(n) !== -1;
  }
  function personalize(t) { return String(t || '').replace(/\{name\}/g, 'سارا').replace(/\{username\}/g, 'sara.design'); }

  /* ───────────── API ───────────── */
  function api(path, opts) {
    opts = opts || {};
    return fetch(CFG.api + path, {
      method: opts.method || 'GET',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Telegram-Init-Data': tg ? tg.initData : '' },
      body: opts.body ? JSON.stringify(opts.body) : undefined,
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok || j.ok === false) { var e = new Error(j.message || 'خطای ارتباط با سرور (' + r.status + ')'); e.status = r.status; e.data = j; throw e; }
        return j;
      });
    });
  }

  /* ───────────── تم و تلگرام ───────────── */
  function applyTheme() {
    var light = tg ? tg.colorScheme !== 'dark' : !window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.body.classList.toggle('light', light);
    try {
      var bg = getComputedStyle(document.body).getPropertyValue('--page-bg').trim();
      if (tg && /^#[0-9a-f]{6}$/i.test(bg)) { tg.setHeaderColor(bg); tg.setBackgroundColor(bg); if (tg.setBottomBarColor) tg.setBottomBarColor(bg); }
    } catch (e) {}
  }
  function backButton(on) {
    if (!tg || !tg.BackButton) return;
    if (on) tg.BackButton.show(); else tg.BackButton.hide();
  }

  /* ───────────── گیت‌ها ───────────── */
  function gate(kind, data) {
    backButton(false);
    var bot = 'https://t.me/' + (CFG.bot || '');
    var html = '<div class="tga-gate">';
    if (kind === 'outside') {
      html += '<div class="tga-logo">' + ic('lock') + '</div><h1>این پنل داخل تلگرام باز می‌شود</h1><p>برای امنیت، تنظیم پست‌ها فقط از داخل بات تلگرام وطن ممکن است.</p>'
        + '<a class="tga-primary" style="max-width:280px;width:100%;text-decoration:none" href="' + esc(bot) + '">باز کردن بات ' + ic('ext') + '</a>';
    } else if (kind === 'unlinked') {
      html += '<div class="tga-logo">' + ic('lock') + '</div><h1>هنوز دسترسی نداری</h1><p>این بات مخصوص تیم وطن است. شناسه‌ی زیر را برای مدیر بفرست تا در پنل اضافه‌ات کند، بعد دوباره همین‌جا را باز کن.</p>'
        + '<span class="tga-code">' + esc(data && data.telegram_id) + '</span>';
    } else {
      html += '<div class="tga-logo">' + ic('alert') + '</div><h1>مشکلی پیش آمد</h1><p>' + esc(data || 'دوباره تلاش کنید.') + '</p>'
        + '<button class="tga-primary" style="max-width:280px;width:100%" data-act="reload">تلاش دوباره</button>';
    }
    app.innerHTML = html + '</div>';
  }

  /* ───────────── فهرست پست‌ها ───────────── */
  var FILTERS = [['all', 'همه'], ['new', 'تنظیم‌نشده'], ['active', 'فعال'], ['other', 'پیش‌نویس و متوقف']];
  function postState(p) { return p.campaign ? p.campaign.status : 'new'; }
  function pill(p, onCard) {
    var st = postState(p);
    var label = p.campaign ? p.campaign.status_label : 'تنظیم‌نشده';
    return '<span class="tga-pill s-' + st + (onCard ? ' on-card' : '') + '">' + esc(label) + '</span>';
  }
  function filtered() {
    return S.posts.filter(function (p) {
      var st = postState(p);
      if (S.filter === 'new') return st === 'new';
      if (S.filter === 'active') return st === 'active' || st === 'test';
      if (S.filter === 'other') return st === 'draft' || st === 'paused';
      return true;
    });
  }
  function renderList() {
    S.view = 'list'; backButton(false);
    var ch = S.boot.channel;
    var counts = { all: S.posts.length, new: 0, active: 0, other: 0 };
    S.posts.forEach(function (p) { var st = postState(p); if (st === 'new') counts.new++; else if (st === 'active' || st === 'test') counts.active++; else counts.other++; });
    var list = filtered();
    var html = '<header class="tga-top"><div class="tga-top-main"><h1>پست‌های اینستاگرام</h1><div class="sub">' + (ch && ch.username ? '@' + esc(ch.username) : 'وطن') + '</div></div>'
      + (S.boot.can_manage ? '<button class="tga-icon-btn" data-act="sync" aria-label="دریافت پست‌های تازه">' + ic('sync') + '</button>' : '') + '</header>'
      + '<nav class="tga-chips">' + FILTERS.map(function (f) { return '<button class="tga-chip' + (S.filter === f[0] ? ' is-on' : '') + '" data-filter="' + f[0] + '">' + f[1] + ' <b>' + fa(counts[f[0]]) + '</b></button>'; }).join('') + '</nav>';
    if (!list.length) {
      html += '<div class="tga-empty"><div class="big">' + ic('image') + '</div>' + (S.posts.length ? 'پستی در این دسته نیست.' : 'هنوز پستی همگام نشده؛ دکمه‌ی همگام‌سازی بالا را بزن.') + '</div>';
    } else {
      html += '<div class="tga-grid">' + list.map(function (p, i) {
        return '<button class="tga-post" style="animation-delay:' + Math.min(i, 10) * 30 + 'ms" data-open="' + p.id + '">'
          + '<div class="tga-post-cover">' + (p.cover ? '<img loading="lazy" src="' + esc(p.cover) + '" alt="">' : '<div class="ph">' + ic('image') + '</div>')
          + '<span class="tga-kind">' + esc(p.kind) + '</span><div class="tga-post-state">' + pill(p) + '<span>' + esc(ago(p.published_at)) + '</span></div></div>'
          + '<div class="tga-post-body">' + esc(p.short) + '</div></button>';
      }).join('') + '</div>';
    }
    app.innerHTML = html;
    window.scrollTo(0, S.listScroll || 0);
  }

  /* ───────────── ویرایشگر ───────────── */
  function openPost(id, fromDeepLink) {
    S.listScroll = window.scrollY;
    S.fromDeepLink = !!fromDeepLink;
    app.innerHTML = '<div style="padding:16px 14px"><div class="tga-skel" style="height:130px;margin-bottom:12px"></div><div class="tga-skel" style="height:64px;margin-bottom:12px"></div><div class="tga-skel" style="height:220px"></div></div>';
    backButton(true);
    api('/posts/' + id).then(function (j) {
      S.post = j.post; S.c = j.campaign; S.showMore = false;
      S.c.settings.card.buttons = (S.c.settings.card.buttons || []).length ? S.c.settings.card.buttons : [{ preset: 'product', type: 'web_url', label: 'مشاهده محصول', url: '', reply_text: '' }];
      S.c.settings.reply.styles = [0, 1, 2].map(function (i) { return (S.c.settings.reply.styles || [])[i] || ''; });
      S.intent = ['draft', 'test', 'active'].indexOf(S.c.status) !== -1 && S.c.id ? S.c.status : (S.c.id ? 'draft' : 'active');
      S.initial = snapshot();
      renderEditor();
      window.scrollTo(0, 0);
    }).catch(function (e) { handleErr(e); renderList(); });
  }
  function snapshot() { return JSON.stringify([S.c.title, S.c.keywords, S.c.public_reply_enabled, S.c.dm_enabled, S.c.follow_required, S.c.settings, S.intent]); }
  function dirty() { return S.view === 'editor' && S.c && snapshot() !== S.initial; }
  function product() {
    var id = Number(get(S.c, 'settings.card.product_id') || 0);
    return (S.boot.products || []).filter(function (p) { return p.id === id; })[0] || null;
  }

  function field(label, path, opts) {
    opts = opts || {};
    var v = get(S.c, path); v = v == null ? '' : v;
    var max = opts.max || 0;
    var cnt = max ? '<span class="cnt" data-cnt="' + path + '">' + fa(String(v).length) + '/' + fa(max) + '</span>' : '';
    var input = opts.area
      ? '<textarea class="tga-textarea" rows="' + (opts.rows || 2) + '" data-bind="' + path + '"' + (max ? ' maxlength="' + max + '"' : '') + ' placeholder="' + esc(opts.ph || '') + '">' + esc(v) + '</textarea>'
      : '<input class="tga-input' + (opts.ltr ? ' ltr' : '') + '" ' + (opts.type ? 'type="' + opts.type + '" inputmode="numeric"' : '') + ' data-bind="' + path + '"' + (max ? ' maxlength="' + max + '"' : '') + ' value="' + esc(v) + '" placeholder="' + esc(opts.ph || '') + '">';
    return '<label class="tga-field"><span class="tga-label">' + esc(label) + cnt + '</span>' + input + (opts.help ? '<span class="tga-help">' + esc(opts.help) + '</span>' : '') + '</label>';
  }
  function sw(path, checked, disabled) {
    return '<label class="tga-switch"><input type="checkbox" data-bind="' + path + '" data-bool="1"' + (checked ? ' checked' : '') + (disabled ? ' disabled' : '') + '><span></span></label>';
  }
  function seg(path, options, value, extraCls) {
    return '<div class="tga-seg ' + (extraCls || '') + '" data-seg="' + path + '">' + options.map(function (o) { return '<button type="button" data-v="' + o[0] + '" class="' + (String(value) === String(o[0]) ? 'is-on' : '') + '">' + o[1] + '</button>'; }).join('') + '</div>';
  }
  function section(key, icon, title, sub, toggle, body) {
    var on = toggle ? !!get(S.c, toggle) : true;
    return '<section class="tga-sec' + (on ? '' : ' is-off') + '" data-sec="' + key + '"><div class="tga-sec-head"><span class="tga-sec-ic">' + ic(icon) + '</span><div class="tga-sec-title"><b>' + title + '</b><small>' + sub + '</small></div>'
      + (toggle ? sw(toggle, on, !S.boot.can_manage) : '') + '</div><div class="tga-sec-body">' + body + '</div></section>';
  }

  function renderEditor() {
    S.view = 'editor';
    var p = S.post, c = S.c, s = c.settings, f = S.boot.flags;
    var html = '<div class="tga-editor"><header class="tga-top" style="padding-inline:2px"><div class="tga-top-main"><h1>' + (c.id ? 'تنظیمات پست' : 'تنظیم پست تازه') + '</h1><div class="sub">' + (c.id ? 'نسخه‌ی ' + fa(c.version) : 'کامنت و دایرکت هوشمند') + '</div></div>'
      + (c.id && S.boot.can_manage ? '<button class="tga-icon-btn" data-act="toggle-status" aria-label="توقف یا ادامه">' + ic(c.status === 'active' || c.status === 'test' ? 'pause' : 'play') + '</button>' : '') + '</header>';

    html += '<div class="tga-hero"><div class="tga-hero-cover">' + (p.cover ? '<img src="' + esc(p.cover) + '" alt="">' : '') + '</div><div class="tga-hero-main"><div class="tga-hero-meta">' + pill(p, true) + '<span>' + esc(p.kind) + ' · ' + esc(ago(p.published_at)) + '</span></div>'
      + '<div class="tga-hero-cap">' + esc(p.caption || p.short) + '</div>' + (p.permalink ? '<a class="tga-hero-link" href="' + esc(p.permalink) + '" data-ext>' + ic('insta') + ' دیدن در اینستاگرام</a>' : '') + '</div></div>';
    if (c.stats) html += '<div class="tga-stats"><div class="tga-stat"><b>' + fa(c.stats.matched) + '</b><span>کامنت شناسایی‌شده</span></div><div class="tga-stat"><b>' + fa(c.stats.succeeded) + '</b><span>اجرای موفق</span></div><div class="tga-stat"><b>' + fa(c.stats.failed) + '</b><span>خطا</span></div></div>';
    if (!p.verified) html += '<div class="tga-warn">' + ic('alert') + '<div>این پست هنوز از اتصال اینستاگرام تأیید نشده؛ تا تأیید فقط پیش‌نویس ذخیره می‌شود.</div></div>';
    if (!f.outbound) html += '<div class="tga-warn">' + ic('alert') + '<div>ارسال واقعی پیام روی سرور خاموش است؛ سناریو ذخیره می‌شود ولی تا روشن‌شدن ارسال، پیامی نمی‌رود.</div></div>';
    if (f.ai && S.boot.can_manage) html += '<button class="tga-ai" data-act="ai"><span class="ic">' + ic('spark') + '</span><span class="tx"><b>نوشتن متن‌ها با هوش مصنوعی</b><small>پاسخ کامنت، پیام فالو و کارت دایرکت بر اساس کپشن و محصول</small></span></button>';

    // ۱. کلمه‌ها
    html += section('kw', 'key', 'کلمه‌های کلیدی', 'کامنتی که یکی از این‌ها را داشته باشد، سناریو را اجرا می‌کند.', null,
      '<div class="tga-kws" data-kws></div><div class="tga-addrow"><input class="tga-input" data-kw-new maxlength="120" placeholder="مثلاً: لینک، قیمت، 1" enterkeyhint="done"><button class="tga-add" data-act="kw-add" aria-label="افزودن">' + ic('plus') + '</button></div>'
      + '<span class="tga-help">روی هر کلمه بزنید تا نحوه‌ی تطبیق عوض شود (شامل / کلمه‌ی کامل / دقیقاً).</span><div class="tga-divider"></div>'
      + '<input class="tga-input" data-kw-test placeholder="امتحان کن: یک کامنت نمونه بنویس…"><div class="tga-test" data-kw-result></div>');

    // ۲. پاسخ عمومی
    html += section('reply', 'comment', 'پاسخ زیر کامنت', 'یکی از این سه متن (به‌صورت چرخشی) زیر کامنت نوشته می‌شود.', 'public_reply_enabled',
      [0, 1, 2].map(function (i) { return field('سبک ' + ['اول', 'دوم', 'سوم'][i], 'settings.reply.styles.' + i, { area: true, max: 300, ph: '{name} جان، توی دایرکت برات فرستادیم 🌿' }); }).join('')
      + '<div class="tga-row"><div class="tga-row-main"><b>شخصی‌سازی با هوش مصنوعی</b><small>متن هر پاسخ کمی متناسب با کامنت تغییر می‌کند.</small></div>' + sw('settings.reply.ai_personalize', !!s.reply.ai_personalize, !S.boot.can_manage) + '</div>');

    // ۳. دایرکت
    var fs = f.follow_supported;
    html += section('dm', 'send', 'دایرکت و کارت محصول', 'برای کامنت‌گذار یک کارت با عکس، تیتر و دکمه‌ی لینک فرستاده می‌شود.', 'dm_enabled',
      '<div class="tga-field"><span class="tga-label">محصول هدف</span><div data-product></div></div>'
      + '<div class="tga-divider"></div><div class="tga-row"><div class="tga-row-main"><b>اول فالو، بعد لینک</b><small>' + (fs ? 'اگر فالو نکرده باشد، اول درخواست فالو با دکمه‌ی «فالو کردم» می‌گیرد.' : 'اتصال فعلی بررسی فالو را پشتیبانی نمی‌کند.') + '</small></div>' + sw('follow_required', fs && c.follow_required, !fs || !S.boot.can_manage) + '</div>'
      + '<div data-follow-fields' + (fs && c.follow_required ? '' : ' hidden') + ' style="display:flex;flex-direction:column;gap:12px">'
      + field('پیام درخواست فالو', 'settings.follow.text', { area: true, max: 900 })
      + field('متن دکمه', 'settings.follow.button', { max: 20 }) + '</div>'
      + '<div class="tga-divider"></div>'
      + field('پیام قبل از کارت (اختیاری)', 'settings.card.intro_text', { area: true, max: 900, ph: '{name} جان، اینم لینکی که خواستی 👇' })
      + field('تیتر کارت', 'settings.card.title', { max: 80, ph: 'خالی = نام محصول' })
      + field('توضیح کوتاه', 'settings.card.subtitle', { max: 80, ph: 'یک جمله درباره‌ی مزیت' })
      + '<div class="tga-field"><span class="tga-label">تصویر کارت</span>' + seg('settings.card.image_source', [['post', 'عکس پست'], ['product', 'عکس محصول'], ['none', 'بدون عکس']].concat(s.card.image_source === 'url' ? [['url', 'لینک']] : []), s.card.image_source) + '</div>'
      + '<div class="tga-field"><span class="tga-label">دکمه‌ها <span class="cnt">تا ۳ دکمه</span></span><div class="tga-btns" data-btns></div></div>'
      + '<div class="tga-preview" data-preview></div>');

    // ۴. انتشار
    var statusOpts = [['draft', 'پیش‌نویس'], ['test', 'آزمایشی'], ['active', 'فعال']];
    html += '<section class="tga-sec"><div class="tga-sec-head"><span class="tga-sec-ic">' + ic('rocket') + '</span><div class="tga-sec-title"><b>انتشار</b><small>«آزمایشی» کامنت‌ها را شناسایی و ثبت می‌کند ولی پیامی نمی‌فرستد.</small></div></div><div class="tga-sec-body">'
      + '<div class="tga-seg status" data-intent>' + statusOpts.map(function (o) { return '<button type="button" data-v="' + o[0] + '" class="' + (S.intent === o[0] ? 'is-on' : '') + '">' + o[1] + '</button>'; }).join('') + '</div></div>'
      + '<button class="tga-more' + (S.showMore ? ' is-open' : '') + '" data-act="more">تنظیمات بیشتر ' + ic('chevron') + '</button>'
      + '<div class="tga-sec-body" data-more' + (S.showMore ? '' : ' hidden') + '>'
      + field('نام سناریو (اختیاری)', 'title', { max: 190, ph: 'خالی = از کپشن' })
      + '<div class="tga-field"><span class="tga-label">ترتیب ارسال</span>' + seg('settings.flow.order', [['comment_first', 'اول پاسخ کامنت'], ['dm_first', 'اول دایرکت']], s.flow.order) + '</div>'
      + '<div class="tga-field"><span class="tga-label">تکرار برای هر نفر</span>' + seg('settings.limits.repeat', [['once', 'یک بار'], ['daily', 'روزی یک بار'], ['every', 'هر کامنت']], s.limits.repeat) + '</div>'
      + '<div class="tga-grid2">' + field('سقف روزانه', 'settings.limits.daily_cap', { type: 'number', ltr: true }) + field('توقف بعد از خطا', 'settings.limits.pause_after_failures', { type: 'number', ltr: true }) + '</div>'
      + '<div class="tga-grid2">' + field('تأخیر کامنت (ثانیه)', 'settings.limits.reply_delay_seconds', { type: 'number', ltr: true }) + field('تأخیر دایرکت (ثانیه)', 'settings.limits.dm_delay_seconds', { type: 'number', ltr: true }) + '</div>'
      + (fs ? field('یادآوری اگر هنوز فالو نکرده بود', 'settings.follow.retry_text', { area: true, max: 900 }) : '')
      + field('پیام بعد از کارت (اختیاری)', 'settings.card.after_text', { area: true, max: 900 })
      + (s.card.image_source === 'url' ? field('لینک تصویر کارت', 'settings.card.image_url', { ltr: true, ph: 'https://' }) : '')
      + '<div class="tga-row"><div class="tga-row-main"><b>توقف روی پیام حساس</b><small>کامنت‌های شکایت/حساس به اپراتور سپرده می‌شوند.</small></div>' + sw('settings.limits.stop_on_sensitive', !!s.limits.stop_on_sensitive) + '</div>'
      + '<div class="tga-row"><div class="tga-row-main"><b>برچسب «کامنت‌گذار پست»</b><small>برای پیگیری در بخش مشتریان.</small></div>' + sw('settings.limits.add_tag', !!s.limits.add_tag) + '</div>'
      + '</div></section></div>';

    html += S.boot.can_manage ? '<div class="tga-foot"><div class="tga-foot-in"><button class="tga-primary" data-act="save"></button></div></div>' : '';
    app.innerHTML = html;
    renderKw(); renderProduct(); renderBtns(); renderPreview(); renderFoot();
    $$('.tga-textarea').forEach(autosize);
    if (!S.boot.can_manage) $$('input,textarea,button[data-v]').forEach(function (el) { el.disabled = true; });
  }

  function renderKw() {
    var box = $('[data-kws]'); if (!box) return;
    var modes = { contains: 'شامل', word: 'کلمه', exact: 'دقیق', pattern: 'الگو' };
    box.innerHTML = S.c.keywords.length ? S.c.keywords.map(function (k, i) {
      return '<span class="tga-kw' + (k.is_active === false ? ' is-off' : '') + '"><button class="tga-kw-txt" data-kw-mode="' + i + '">' + esc(k.keyword) + '</button><span class="tga-kw-mode">' + (modes[k.match_mode] || 'شامل') + '</span><button class="tga-kw-x" data-kw-del="' + i + '" aria-label="حذف">' + ic('x') + '</button></span>';
    }).join('') : '<span class="tga-help">هنوز کلمه‌ای اضافه نشده.</span>';
    testKw();
  }
  function testKw() {
    var inp = $('[data-kw-test]'), out = $('[data-kw-result]'); if (!inp || !out) return;
    var t = inp.value.trim();
    if (!t) { out.className = 'tga-test'; out.innerHTML = ''; return; }
    var hit = S.c.keywords.filter(function (k) { return k.is_active !== false && kwMatch(t, k); })[0];
    out.className = 'tga-test ' + (hit ? 'ok' : 'no');
    out.innerHTML = hit ? ic('check') + ' اجرا می‌شود (کلمه‌ی «' + esc(hit.keyword) + '»)' : ic('x') + ' با این کامنت اجرا نمی‌شود';
  }
  function renderProduct() {
    var box = $('[data-product]'); if (!box) return;
    var pr = product();
    box.innerHTML = '<button class="tga-product' + (pr ? '' : ' is-empty') + '" data-act="pick-product">'
      + (pr && pr.image ? '<img src="' + esc(pr.image) + '" alt="">' : '<span class="ph">' + ic('bag') + '</span>')
      + '<span class="nm">' + (pr ? esc(pr.name) + '<small>دکمه‌ی بدون لینک به صفحه‌ی همین محصول می‌رود</small>' : 'انتخاب محصول<small>برای دایرکت الزامی است</small>') + '</span><span class="chev">' + ic('left') + '</span></button>';
  }
  function renderBtns() {
    var box = $('[data-btns]'); if (!box) return;
    var btns = S.c.settings.card.buttons;
    box.innerHTML = btns.map(function (b, i) {
      var post = b.type === 'postback';
      return '<div class="tga-btn-row"><input class="tga-input" maxlength="20" data-bind="settings.card.buttons.' + i + '.label" value="' + esc(b.label) + '" placeholder="متن دکمه">'
        + '<button class="tga-btn-del" data-btn-del="' + i + '" aria-label="حذف دکمه">' + ic('x') + '</button>'
        + (post ? '<input class="tga-input full" maxlength="900" data-bind="settings.card.buttons.' + i + '.reply_text" value="' + esc(b.reply_text) + '" placeholder="پاسخی که بعد از زدن دکمه می‌رود">'
          : '<input class="tga-input ltr full" inputmode="url" data-bind="settings.card.buttons.' + i + '.url" value="' + esc(b.url) + '" placeholder="لینک (خالی = صفحه‌ی محصول)">') + '</div>';
    }).join('') + (btns.length < 3 ? '<button class="tga-ghost-btn" data-act="btn-add">' + ic('plus') + ' افزودن دکمه</button>' : '');
  }
  function renderPreview() {
    var box = $('[data-preview]'); if (!box) return;
    var s = S.c.settings, pr = product();
    var img = s.card.image_source === 'product' ? (pr && pr.image) || S.post.cover : s.card.image_source === 'post' ? S.post.cover || (pr && pr.image) : s.card.image_source === 'url' ? s.card.image_url : null;
    var title = s.card.title || (pr ? pr.name : S.post.short);
    var html = '<div class="tga-preview-cap">' + ic('send') + ' پیش‌نمایش دایرکت</div>';
    if (S.c.follow_required && S.boot.flags.follow_supported && s.follow.text) {
      html += '<div class="tga-bubble">' + esc(personalize(s.follow.text)) + '</div><div class="tga-dmcard"><div class="bt">' + esc(s.follow.button || 'فالو کردم ✅') + '</div></div>';
    }
    if (s.card.intro_text) html += '<div class="tga-bubble">' + esc(personalize(s.card.intro_text)) + '</div>';
    html += '<div class="tga-dmcard">' + (s.card.image_source !== 'none' ? '<div class="img">' + (img ? '<img src="' + esc(img) + '" alt="">' : ic('image')) + '</div>' : '')
      + '<div class="tt"><b>' + esc(personalize(title)) + '</b>' + (s.card.subtitle ? '<small>' + esc(personalize(s.card.subtitle)) + '</small>' : '') + '</div>'
      + s.card.buttons.filter(function (b) { return (b.label || '').trim(); }).map(function (b) { return '<div class="bt">' + esc(b.label) + '</div>'; }).join('') + '</div>';
    if (s.card.after_text) html += '<div class="tga-bubble">' + esc(personalize(s.card.after_text)) + '</div>';
    box.innerHTML = html;
  }
  function renderFoot() {
    var b = $('[data-act="save"]'); if (!b) return;
    var wasActive = S.c.id && S.c.status === 'active';
    var label = S.intent === 'active' ? (wasActive ? 'ذخیره‌ی تغییرات' : 'ذخیره و فعال‌سازی') : S.intent === 'test' ? 'ذخیره در حالت آزمایشی' : 'ذخیره‌ی پیش‌نویس';
    b.innerHTML = (S.intent === 'active' ? ic('rocket') : ic('check')) + ' ' + label;
    b.classList.toggle('is-active', S.intent === 'active');
    b.disabled = S.busy;
  }
  function autosize(el) { el.style.height = 'auto'; el.style.height = Math.max(48, el.scrollHeight + 2) + 'px'; }

  /* ───────────── برگه‌ی انتخاب محصول ───────────── */
  function productSheet() {
    var wrap = document.createElement('div');
    wrap.className = 'tga-sheet';
    wrap.innerHTML = '<div class="tga-sheet-bg" data-close></div><div class="tga-sheet-box"><div class="tga-sheet-handle"></div><div class="tga-sheet-head"><b>انتخاب محصول هدف</b><input class="tga-input" data-q placeholder="جست‌وجوی محصول…"></div><div class="tga-sheet-list" data-list></div></div>';
    document.body.appendChild(wrap);
    document.body.style.overflow = 'hidden';
    var current = Number(get(S.c, 'settings.card.product_id') || 0);
    function draw(q) {
      q = norm(q);
      var rows = (S.boot.products || []).filter(function (p) { return !q || norm(p.name).indexOf(q) !== -1; }).slice(0, 80);
      $('[data-list]', wrap).innerHTML = rows.length ? rows.map(function (p) {
        return '<button class="tga-opt' + (p.id === current ? ' is-on' : '') + '" data-pid="' + p.id + '">' + (p.image ? '<img loading="lazy" src="' + esc(p.image) + '" alt="">' : '<span class="ph"></span>') + '<span>' + esc(p.name) + '</span>' + (p.id === current ? '<span class="ck">' + ic('check') + '</span>' : '') + '</button>';
      }).join('') : '<div class="tga-empty">محصولی پیدا نشد.</div>';
    }
    function close() { wrap.remove(); document.body.style.overflow = ''; }
    draw('');
    $('[data-q]', wrap).addEventListener('input', function (e) { draw(e.target.value); });
    wrap.addEventListener('click', function (e) {
      if (e.target.closest('[data-close]')) return close();
      var opt = e.target.closest('[data-pid]');
      if (opt) { set(S.c, 'settings.card.product_id', Number(opt.dataset.pid)); haptic('light'); close(); renderProduct(); renderPreview(); }
    });
  }

  /* ───────────── رویدادها ───────────── */
  app.addEventListener('click', function (e) {
    var t = e.target;
    var open = t.closest('[data-open]');
    if (open) { haptic('light'); return openPost(open.dataset.open); }
    var flt = t.closest('[data-filter]');
    if (flt) { S.filter = flt.dataset.filter; haptic('light'); S.listScroll = 0; return renderList(); }
    var ext = t.closest('a[data-ext]');
    if (ext && tg && tg.openLink) { e.preventDefault(); return tg.openLink(ext.href); }
    var segBtn = t.closest('[data-seg] button');
    if (segBtn && !segBtn.disabled) {
      var segEl = segBtn.parentNode;
      set(S.c, segEl.dataset.seg, segBtn.dataset.v);
      $$('button', segEl).forEach(function (b) { b.classList.toggle('is-on', b === segBtn); });
      haptic('light'); renderPreview(); return;
    }
    var intentBtn = t.closest('[data-intent] button');
    if (intentBtn && !intentBtn.disabled) {
      S.intent = intentBtn.dataset.v;
      $$('[data-intent] button').forEach(function (b) { b.classList.toggle('is-on', b === intentBtn); });
      haptic('light'); renderFoot(); return;
    }
    var mode = t.closest('[data-kw-mode]');
    if (mode) {
      var k = S.c.keywords[+mode.dataset.kwMode]; var order = ['contains', 'word', 'exact'];
      k.match_mode = order[(order.indexOf(k.match_mode) + 1) % order.length]; haptic('light'); return renderKw();
    }
    var del = t.closest('[data-kw-del]');
    if (del) { S.c.keywords.splice(+del.dataset.kwDel, 1); haptic('light'); return renderKw(); }
    var bdel = t.closest('[data-btn-del]');
    if (bdel) {
      if (S.c.settings.card.buttons.length <= 1) return toast('کارت حداقل یک دکمه لازم دارد.', 'err');
      S.c.settings.card.buttons.splice(+bdel.dataset.btnDel, 1); renderBtns(); renderPreview(); return;
    }
    var act = t.closest('[data-act]');
    if (!act) return;
    var a = act.dataset.act;
    if (a === 'reload') return location.reload();
    if (a === 'sync') return doSync(act);
    if (a === 'kw-add') return addKw();
    if (a === 'btn-add') { S.c.settings.card.buttons.push({ preset: 'link', type: 'web_url', label: 'مشاهده لینک', url: '', reply_text: '' }); renderBtns(); renderPreview(); return; }
    if (a === 'pick-product') return S.boot.can_manage && productSheet();
    if (a === 'more') { S.showMore = !S.showMore; act.classList.toggle('is-open', S.showMore); $('[data-more]').hidden = !S.showMore; $$('[data-more] .tga-textarea').forEach(autosize); return; }
    if (a === 'ai') return doAi(act);
    if (a === 'save') return doSave();
    if (a === 'toggle-status') return doToggleStatus();
    if (a === 'close') return tg ? tg.close() : null;
    if (a === 'list') { S.c = null; return loadList(); }
  });

  app.addEventListener('input', function (e) {
    var el = e.target;
    if (el.matches('[data-kw-test]')) return testKw();
    if (!el.dataset.bind || el.dataset.bool) return;
    var v = el.type === 'number' ? (el.value === '' ? 0 : Number(el.value)) : el.value;
    set(S.c, el.dataset.bind, v);
    if (el.tagName === 'TEXTAREA') autosize(el);
    var cnt = app.querySelector('[data-cnt="' + el.dataset.bind + '"]');
    if (cnt) { var max = Number(el.getAttribute('maxlength')); cnt.textContent = fa(el.value.length) + '/' + fa(max); cnt.classList.toggle('over', el.value.length >= max); }
    renderPreview();
  });
  app.addEventListener('change', function (e) {
    var el = e.target;
    if (!el.dataset.bind || !el.dataset.bool) return;
    set(S.c, el.dataset.bind, el.checked); haptic('light');
    var sec = el.closest('[data-sec]');
    if (sec && el.closest('.tga-sec-head')) sec.classList.toggle('is-off', !el.checked);
    if (el.dataset.bind === 'follow_required') { var ff = $('[data-follow-fields]'); if (ff) { ff.hidden = !el.checked; $$('.tga-textarea', ff).forEach(autosize); } }
    renderPreview();
  });
  app.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('[data-kw-new]')) { e.preventDefault(); addKw(); }
  });

  function addKw() {
    var inp = $('[data-kw-new]'); if (!inp) return;
    var parts = inp.value.split(/[،,]/).map(function (x) { return x.trim(); }).filter(Boolean);
    var added = 0;
    parts.forEach(function (w) {
      if (S.c.keywords.some(function (k) { return norm(k.keyword) === norm(w); })) return;
      if (S.c.keywords.length >= 30) return;
      S.c.keywords.push({ keyword: w.slice(0, 120), match_mode: 'contains', is_active: true }); added++;
    });
    inp.value = ''; inp.focus();
    if (added) { haptic('light'); renderKw(); }
  }

  function doSync(btn) {
    if (btn.classList.contains('is-busy')) return;
    btn.classList.add('is-busy');
    api('/sync', { method: 'POST' }).then(function (j) { S.posts = j.posts; toast(j.message, 'ok'); haptic('ok'); renderList(); })
      .catch(handleErr).then(function () { btn.classList.remove('is-busy'); });
  }

  function doAi(btn) {
    if (btn.classList.contains('is-busy')) return;
    btn.classList.add('is-busy'); $('b', btn).textContent = 'در حال نوشتن…';
    var sections = ['card'];
    if (S.c.public_reply_enabled) sections.push('public_reply');
    if (S.c.follow_required) sections.push('follow');
    api('/ai', { method: 'POST', body: { post_id: S.post.id, product_id: get(S.c, 'settings.card.product_id') || null, keywords: S.c.keywords.map(function (k) { return k.keyword; }), sections: sections } })
      .then(function (j) {
        var fl = j.fields || {}, s = S.c.settings;
        if (fl.public_replies) s.reply.styles = [0, 1, 2].map(function (i) { return fl.public_replies[i] || s.reply.styles[i] || ''; });
        if (fl.follow_text) s.follow.text = fl.follow_text;
        if (fl.follow_retry_text) s.follow.retry_text = fl.follow_retry_text;
        if (fl.follow_button) s.follow.button = fl.follow_button;
        if (fl.card_intro != null) s.card.intro_text = fl.card_intro;
        if (fl.card_title) s.card.title = fl.card_title;
        if (fl.card_subtitle) s.card.subtitle = fl.card_subtitle;
        if (fl.card_buttons) fl.card_buttons.forEach(function (lbl, i) { if (s.card.buttons[i]) s.card.buttons[i].label = lbl; });
        var y = window.scrollY; renderEditor(); window.scrollTo(0, y);
        haptic('ok'); toast('متن‌ها نوشته شد ✨ قبل از ذخیره نگاهی بینداز.', 'ok');
      })
      .catch(function (e) { handleErr(e); btn.classList.remove('is-busy'); $('b', btn).textContent = 'نوشتن متن‌ها با هوش مصنوعی'; });
  }

  function payload() {
    var c = clone(S.c);
    c.settings.reply.styles = c.settings.reply.styles.filter(function (x) { return (x || '').trim(); });
    c.settings.card.buttons = c.settings.card.buttons.filter(function (b) { return (b.label || '').trim(); });
    if (!c.settings.card.product_id) c.settings.card.product_id = null;
    if (!c.settings.card.image_url) delete c.settings.card.image_url;
    c.settings.card.buttons.forEach(function (b) { if (!b.url) delete b.url; });
    return { title: c.title, intent: S.intent, keywords: c.keywords, public_reply_enabled: c.public_reply_enabled, dm_enabled: c.dm_enabled, follow_required: S.boot.flags.follow_supported ? c.follow_required : false, settings: c.settings };
  }
  function doSave() {
    if (S.busy) return;
    if (!S.c.keywords.length) { haptic('error'); toast('حداقل یک کلمه‌ی کلیدی اضافه کن.', 'err'); $('[data-kw-new]').focus(); return; }
    if (S.c.dm_enabled && !get(S.c, 'settings.card.product_id')) { haptic('error'); toast('برای دایرکت، محصول هدف را انتخاب کن.', 'err'); $('[data-product]').scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
    if (!S.c.dm_enabled && !S.c.public_reply_enabled) { haptic('error'); toast('حداقل پاسخ کامنت یا دایرکت را روشن کن.', 'err'); return; }
    var go = function () {
      S.busy = true; renderFoot();
      api('/posts/' + S.post.id, { method: 'POST', body: payload() }).then(function (j) {
        haptic('ok'); S.c.id = j.campaign.id; S.c.status = j.campaign.status; S.c.version = j.campaign.version;
        S.initial = snapshot();
        success(j);
      }).catch(handleErr).then(function () { S.busy = false; renderFoot(); });
    };
    if (S.intent === 'active' && S.c.status !== 'active') confirmBox('سناریو فعال شود و از همین حالا روی کامنت‌های واقعی اجرا شود؟', function (ok) { if (ok) go(); });
    else go();
  }
  function success(j) {
    backButton(false);
    var c = S.c, pr = product(), st = j.campaign.status;
    var lines = [
      [ic('key'), 'کلمه‌ها: ' + c.keywords.map(function (k) { return '«' + k.keyword + '»'; }).join('، ')],
      [ic('comment'), c.public_reply_enabled ? 'پاسخ زیر کامنت: روشن' : 'پاسخ زیر کامنت: خاموش'],
      [ic('send'), c.dm_enabled ? 'دایرکت: ' + (pr ? pr.name : 'روشن') + (c.follow_required && S.boot.flags.follow_supported ? ' · با شرط فالو' : '') : 'دایرکت: خاموش'],
    ];
    app.innerHTML = '<div class="tga-gate"><div class="tga-logo" style="background:var(--' + (st === 'active' ? 'success' : 'primary') + ');color:#fff">' + ic(st === 'active' ? 'rocket' : 'check') + '</div>'
      + '<h1>' + esc(j.message) + '</h1><div class="tga-steps">' + lines.map(function (l) { return '<div style="display:flex;gap:8px;align-items:center;margin:4px 0">' + l[0] + '<span>' + esc(l[1]) + '</span></div>'; }).join('') + '</div>'
      + '<button class="tga-primary" style="max-width:320px;width:100%" data-act="list">پست‌های دیگر</button>'
      + (tg ? '<button class="tga-ghost-btn" style="max-width:320px;width:100%;border-style:solid" data-act="close">بستن</button>' : '') + '</div>';
    window.scrollTo(0, 0);
  }
  function doToggleStatus() {
    var on = S.c.status === 'active' || S.c.status === 'test';
    var next = on ? 'paused' : 'active';
    confirmBox(on ? 'سناریوی این پست متوقف شود؟' : 'سناریو دوباره فعال شود؟', function (ok) {
      if (!ok) return;
      api('/posts/' + S.post.id + '/status', { method: 'POST', body: { status: next } }).then(function (j) {
        haptic('ok'); toast(j.message, 'ok'); S.c.status = next; S.post.campaign = { id: S.c.id, status: next, status_label: next === 'active' ? 'فعال' : 'متوقف' };
        S.intent = next === 'active' ? 'active' : 'draft'; var y = window.scrollY; S.initial = snapshot(); renderEditor(); window.scrollTo(0, y);
      }).catch(handleErr);
    });
  }

  function handleErr(e) {
    haptic('error');
    if (e && e.data && e.data.code === 'TELEGRAM_NOT_LINKED') return gate('unlinked', e.data);
    if (e && e.status === 401) return gate('outside');
    toast((e && e.message) || 'خطای ناشناخته', 'err');
  }

  function goBack() {
    if (S.view !== 'editor') return;
    var leave = function () { S.c = null; loadList(); };
    if (dirty()) confirmBox('تغییرات ذخیره نشده‌اند؛ خارج شوی؟', function (ok) { if (ok) leave(); });
    else leave();
  }
  function loadList() {
    renderList();
    api('/posts').then(function (j) { S.posts = j.posts; if (S.view === 'list') renderList(); }).catch(function () {});
  }

  /* ───────────── شروع ───────────── */
  function start() {
    if (tg) {
      try { tg.ready(); tg.expand(); if (tg.disableVerticalSwipes) tg.disableVerticalSwipes(); } catch (e) {}
      tg.onEvent('themeChanged', applyTheme);
      if (tg.BackButton) tg.BackButton.onClick(goBack);
    }
    applyTheme();
    if (!tg || !tg.initData) return gate('outside');
    var sp = (tg.initDataUnsafe && tg.initDataUnsafe.start_param) || '';
    var deepId = CFG.postId || (/^post_(\d+)$/.test(sp) ? Number(sp.slice(5)) : null);
    api('/bootstrap').then(function (j) {
      S.boot = j; S.posts = j.posts || [];
      if (deepId) openPost(deepId, true); else renderList();
    }).catch(function (e) {
      if (e.data && e.data.code === 'TELEGRAM_NOT_LINKED') return gate('unlinked', e.data);
      if (e.status === 401) return gate('outside');
      gate('error', e.message);
    });
  }
  start();
})();
