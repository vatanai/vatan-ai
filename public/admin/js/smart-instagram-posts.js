/* اینستاگرام هوشمند › ثبت پست — ویزارد مرحله‌ای، پیش‌نمایش زنده‌ی آیفون، سازنده‌ی کلمه و دکمه، دستیار هوش مصنوعی و آزمون بدون ارسال.
   هیچ پیامی از مرورگر ارسال نمی‌شود؛ همه‌ی ارسال‌ها فقط از سرور و با SendPolicy انجام می‌شوند. */
(function () {
  'use strict';

  var CFG = window.SIP_CONFIG || {};
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var faDigits = function (v) { return String(v).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };

  /* ── نرمال‌سازی فارسی (هم‌ارز PersianText::normalize سرور) ── */
  var MAP = { 'ي': 'ی', 'ك': 'ک', 'ى': 'ی', 'ة': 'ه', 'ۀ': 'ه', 'أ': 'ا', 'إ': 'ا', 'ؤ': 'و', '‌': ' ', '‍': '', 'ـ': '' };
  function normalize(text) {
    var t = String(text || '').replace(/[يكىةۀأإؤ‌‍ـ]/g, function (c) { return MAP[c]; })
      .replace(/[۰-۹]/g, function (d) { return String(d.charCodeAt(0) - 1776); })
      .replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 1632); })
      .replace(/[ً-ٰٟ]/g, '').toLowerCase();
    try { t = t.replace(/[^\p{L}\p{N}\s]+/gu, ' '); } catch (e) { t = t.replace(/[!-\/:-@\[-`{-~،؛؟«»]+/g, ' '); }
    return t.replace(/\s+/g, ' ').trim();
  }
  function captionKeywords(caption) {
    var values = [], seen = {};
    var add = function (value) {
      value = String(value || '').replace(/\s+/g, ' ').trim().replace(/^[\s.,،؛;:：!?؟!()[\]{}<>|/\\]+|[\s.,،؛;:：!?؟!()[\]{}<>|/\\]+$/g, '');
      if (!value || value.length < 2 || value.length > 120) return;
      var key = normalize(value);
      if (!key || seen[key]) return;
      seen[key] = true;
      values.push(value);
    };
    var quoted = String(caption || '').match(/[«“”"]([^«»“”"]{2,120})[»“”"]/gu) || [];
    quoted.forEach(function (v) { add(v.slice(1, -1)); });
    var hashtags = String(caption || '').match(/#[\p{L}\p{N}_‌-]{2,120}/gu) || [];
    hashtags.forEach(function (v) { add(v.slice(1)); });
    return values.slice(0, 5);
  }
  function matches(text, keyword, mode) {
    var hay = normalize(text);
    if (mode === 'pattern') {
      var parts = String(keyword).split('*').map(normalize).filter(Boolean);
      if (!parts.length) return false;
      return new RegExp(parts.map(function (p) { return p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('.*'), 'u').test(hay);
    }
    var needle = normalize(keyword);
    if (!needle) return false;
    if (mode === 'exact') return hay === needle;
    if (mode === 'word') return (' ' + hay + ' ').indexOf(' ' + needle + ' ') !== -1;
    return hay.indexOf(needle) !== -1;
  }
  function personalize(text) {
    return String(text || '').replace(/\{name\}/g, CFG.sampleName || 'محسن').replace(/\{username\}/g, CFG.sampleUser || 'mohsen_shop');
  }

  /* ═════════════ پیش‌نمایش آیفون ═════════════ */
  function Phone(root, getState) {
    this.root = root;
    this.app = $('[data-ph-app]', root);
    this.getState = getState;
    this.tab = 'comment';
    this.styleIdx = 0;
    this.notFollow = false;
    var self = this;
    $$('[data-ph-tab]', root).forEach(function (b) {
      b.addEventListener('click', function () { self.setTab(b.getAttribute('data-ph-tab')); });
    });
    var nf = $('[data-ph-notfollow]', root);
    if (nf) nf.addEventListener('change', function () { self.notFollow = nf.checked; self.setTab('dm'); });
    this.app.addEventListener('click', function (e) {
      var dot = e.target.closest('[data-style-dot]');
      if (dot) { self.styleIdx = +dot.getAttribute('data-style-dot'); self.render(); }
    });
  }
  Phone.prototype.setTab = function (tab) {
    this.tab = tab;
    $$('[data-ph-tab]', this.root).forEach(function (b) {
      var on = b.getAttribute('data-ph-tab') === tab;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    this.render();
  };
  Phone.prototype.render = function () {
    var st = this.getState();
    var sim = $('[data-ph-follow-sim]', this.root);
    if (sim) sim.hidden = !(st.dmOn && st.followOn);
    this.app.innerHTML = this.tab === 'dm' ? this.dm(st) : this.comment(st);
  };
  Phone.prototype.avatar = function (letter, small) {
    return '<span class="ig-avatar' + (small ? ' is-sm' : '') + '"><span>' + esc(letter) + '</span></span>';
  };
  Phone.prototype.comment = function (st) {
    var user = CFG.username || 'vatan.ai';
    var styles = (st.styles || []).filter(function (s) { return String(s).trim() !== ''; });
    if (this.styleIdx >= styles.length) this.styleIdx = 0;
    var reply = styles[this.styleIdx] || '';
    var h = '<div class="ig-top">' + this.avatar(user.charAt(0).toUpperCase()) + '<b>' + esc(user) + '</b><i class="fa-solid fa-ellipsis" style="margin-right:auto"></i></div>';
    h += '<div class="ig-scroll"><div class="ig-media">' + (st.cover ? '<img src="' + esc(st.cover) + '" alt="">' : '<span class="ig-placeholder"><i class="fa-regular fa-image"></i></span>') + '</div>';
    h += '<div class="ig-actions"><i class="fa-regular fa-heart"></i><i class="fa-regular fa-comment"></i><i class="fa-regular fa-paper-plane"></i><i class="fa-regular fa-bookmark"></i></div>';
    if (st.likes != null) h += '<div class="ig-likes">' + faDigits(Number(st.likes).toLocaleString('en-US')) + ' لایک</div>';
    h += '<div class="ig-cap"><b>' + esc(user) + '</b> ' + esc(st.caption || 'کپشن پست اینجا نمایش داده می‌شود') + '</div>';
    h += '<div class="ig-comments">';
    h += '<div class="ig-comment">' + this.avatar('M', true) + '<div><b>' + esc(CFG.sampleUser || 'mohsen_shop') + '</b><span class="ig-keyword">' + esc(st.keyword || 'کلمه‌ی کلیدی') + '</span><small>همین حالا · پاسخ</small></div></div>';
    if (st.replyOn && reply) {
      h += '<div class="ig-comment is-reply">' + this.avatar(user.charAt(0).toUpperCase(), true) + '<div><b>' + esc(user) + '</b>' + esc(personalize(reply)) + '<small>همین حالا' + (st.aiPersonalize ? ' · ✨ نسخه‌ی نهایی با هوش مصنوعی شخصی‌سازی می‌شود' : '') + '</small></div></div>';
      if (styles.length > 1) {
        h += '<div class="ig-style-dots">' + styles.map(function (s, i) { return '<button type="button" data-style-dot="' + i + '" class="' + (i === this.styleIdx ? 'is-active' : '') + '" aria-label="سبک ' + faDigits(i + 1) + '"></button>'; }, this).join('') + '</div>';
      }
    } else if (!st.replyOn) {
      h += '<div class="ig-note" style="display:block;margin:10px auto">پاسخ عمومی خاموش است</div>';
    }
    h += '</div></div><div class="ig-composer"><i class="fa-regular fa-face-smile"></i> برای ' + esc(user) + ' کامنت بگذارید…</div>';
    return h;
  };
  Phone.prototype.card = function (st) {
    var btns = (st.buttons || []).filter(function (b) { return b.label; });
    var h = '<div class="ig-card"><div class="ig-card-img">' + (st.image ? '<img src="' + esc(st.image) + '" alt="">' : '<span class="ig-placeholder"><i class="fa-regular fa-image"></i></span>') + '</div>';
    h += '<div class="ig-card-body"><b>' + esc(personalize(st.title || 'تیتر کارت')) + '</b>' + (st.subtitle ? '<small>' + esc(personalize(st.subtitle)) + '</small>' : '') + '</div>';
    h += btns.map(function (b) { return '<div class="ig-card-btn">' + esc(b.label) + '</div>'; }).join('');
    if (!btns.length) h += '<div class="ig-card-btn" style="color:rgb(200 60 60)">بدون دکمه</div>';
    return h + '</div>';
  };
  Phone.prototype.dm = function (st) {
    var user = CFG.username || 'vatan.ai';
    var h = '<div class="ig-dm-head"><i class="fa-solid fa-angle-right"></i>' + this.avatar(user.charAt(0).toUpperCase()) + '<div><b>' + esc(user) + ' <i class="fa-solid fa-circle-check"></i></b><small>حساب کسب‌وکار</small></div></div>';
    h += '<div class="ig-scroll"><div class="ig-chat">';
    var msgIn = function (t) { return t && String(t).trim() ? '<div class="ig-msg is-in">' + esc(personalize(t)) + '</div>' : ''; };
    var msgOut = function (t) { return '<div class="ig-msg is-out">' + esc(t) + '</div>'; };
    var quick = function (t) { return '<div class="ig-quick"><span>' + esc(t) + '</span></div>'; };
    if (!st.dmOn) {
      h += '<div class="ig-note">دایرکت برای این پست خاموش است</div>';
    } else {
      h += '<div class="ig-note">در پاسخ به کامنت شما: «' + esc(st.keyword || '…') + '»</div>';
      var direct = st.mode === 'direct_card' && !st.followOn;
      if (!direct) {
        h += msgIn(st.openingText || 'پیام آغاز') + quick(st.openingButton || 'ارسال لینک') + msgOut(st.openingButton || 'ارسال لینک');
        if (st.followOn && this.notFollow) {
          h += msgIn(st.followText || 'لطفاً پیج را فالو کنید') + '<div class="ig-note">' + esc('instagram.com/' + user) + '</div>' + quick(st.followButton || 'فالو کردم') + msgOut(st.followButton || 'فالو کردم');
          h += '<div class="ig-note">فالو تأیید شد ✓</div>';
        } else if (st.followOn) {
          h += '<div class="ig-note">فالو بررسی شد ✓</div>';
        }
      }
      h += msgIn(st.intro) + this.card(st) + msgIn(st.after);
    }
    h += '</div></div><div class="ig-composer"><i class="fa-regular fa-face-smile"></i> پیام…</div>';
    return h;
  };

  /* ═════════════ ویزارد ═════════════ */
  function Wizard(form) {
    this.form = form;
    this.steps = $$('[data-step]', form);
    this.current = 0;
    this.visited = { 0: true };
    this.autoKeywordSeed = !CFG.isEdit;
    this.replacingKeywords = false;
    var self = this;
    var preview = $('[data-sip-preview]');
    this.phone = preview ? new Phone(preview, function () { return self.state(); }) : null;

    $$('[data-step-go]').forEach(function (b) { b.addEventListener('click', function () { self.go(+b.getAttribute('data-step-go')); }); });
    $('[data-step-next]', form).addEventListener('click', function () { if (self.validate(self.current, true)) self.go(self.current + 1); });
    $('[data-step-prev]', form).addEventListener('click', function () { self.go(self.current - 1); });

    this.initPosts();
    this.initProducts();
    this.initKeywords();
    this.initCounters(form);
    this.initVars();
    this.initToggles();
    this.initButtons();
    this.initImage();
    this.initAi();

    form.addEventListener('input', function () { self.refresh(); });
    form.addEventListener('change', function () { self.refresh(); });
    form.addEventListener('submit', function (e) { self.onSubmit(e); });
    this.go(0, true);
    this.refresh();
  }

  Wizard.prototype.go = function (i, silent) {
    i = Math.max(0, Math.min(this.steps.length - 1, i));
    this.current = i;
    this.visited[i] = true;
    var self = this;
    this.steps.forEach(function (s, k) { s.classList.toggle('is-current', k === i); });
    $$('[data-step-go]').forEach(function (b, k) {
      b.classList.toggle('is-current', k === i);
      b.classList.toggle('is-done', k !== i && self.visited[k] && self.validate(k, false));
      var check = $('[data-step-check]', b);
      if (check) check.hidden = !(k !== i && self.visited[k] && self.validate(k, false));
      b.setAttribute('aria-current', k === i ? 'step' : 'false');
    });
    var bar = $('[data-step-progress]');
    if (bar) bar.style.width = ((i + 1) / this.steps.length * 100).toFixed(1) + '%';
    var last = i === this.steps.length - 1;
    $('[data-step-prev]', this.form).style.visibility = i === 0 ? 'hidden' : 'visible';
    $('[data-step-next]', this.form).hidden = last;
    $$('[data-final]', this.form).forEach(function (b) { b.hidden = !last; });
    if (this.phone) this.phone.setTab(i >= 3 && i <= 4 ? 'dm' : 'comment');
    if (last) this.summary();
    if (!silent) {
      var top = $('.sip-stepper');
      if (top && top.getBoundingClientRect().top < 0) top.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  /* اعتبارسنجی هر قدم — پیام خطا کنار همان قدم */
  Wizard.prototype.errorFor = function (i) {
    var f = this.form;
    if (i === 0 && !$('[data-post-input]:checked', f) && !$('input[type=hidden][data-post-input]', f)) return 'یک پست انتخاب کنید.';
    if (i === 1 && !$$('[data-kw-row]', f).length) return 'حداقل یک کلمه‌ی کلیدی اضافه کنید.';
    if (i === 2 && this.isOn('[data-toggle-reply]') && !this.styles().some(function (s) { return s.trim(); })) return 'حداقل یک سبک پاسخ بنویسید یا پاسخ عمومی را خاموش کنید.';
    if (i === 4 && this.isOn('[data-toggle-dm]')) {
      var product = ($('[data-card-product]', f) || {}).value;
      if (!product) return 'محصول هدف برای ارسال دایرکت الزامی است.';
      var ok = $$('[data-btn-row]', f).some(function (r) {
        return $('[data-btn-label]', r).value.trim() && $('[data-btn-type]', r).value === 'web_url' && ($('[data-btn-url]', r).value.trim() || product);
      });
      if (!ok) return 'کارت حداقل یک دکمه‌ی لینک با آدرس لازم دارد (یا یک محصول انتخاب کنید).';
      var bad = $$('[data-btn-url]', f).filter(function (u) { return u.value.trim() && !/^https?:\/\/\S+$/i.test(u.value.trim()); });
      if (bad.length) return 'آدرس دکمه باید با http یا https شروع شود.';
    }
    return null;
  };
  Wizard.prototype.validate = function (i, show) {
    var err = this.errorFor(i);
    var btn = $$('[data-step-go]')[i];
    if (btn) btn.classList.toggle('has-error', !!err && show);
    if (show) {
      var step = this.steps[i];
      var box = $('[data-step-error]', step);
      if (err) {
        if (!box) { box = document.createElement('div'); box.className = 'si-flash is-danger'; box.setAttribute('data-step-error', ''); box.setAttribute('role', 'alert'); step.insertBefore(box, step.children[1]); }
        box.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span>' + esc(err) + '</span>';
      } else if (box) { box.remove(); }
    }
    return !err;
  };
  Wizard.prototype.onSubmit = function (e) {
    var sub = e.submitter;
    var intent = sub ? sub.value : 'draft';
    var checks = intent === 'draft' ? [0, 1] : [0, 1, 2, 4];
    for (var k = 0; k < checks.length; k++) {
      if (!this.validate(checks[k], true)) { e.preventDefault(); this.go(checks[k]); return; }
    }
    if (sub && sub.hasAttribute('data-confirm-active') && !window.confirm(sub.getAttribute('data-confirm-active'))) { e.preventDefault(); return; }
    this.reindexKeywords();
    this.reindexButtons();
    $$('button[type=submit]', this.form).forEach(function (b) { setTimeout(function () { b.disabled = true; }, 0); });
  };

  Wizard.prototype.isOn = function (sel) { var el = $(sel, this.form); return !!(el && el.checked); };
  Wizard.prototype.val = function (name) { var el = this.form.elements[name]; if (!el) return ''; if (el.length && !el.tagName) { var c = Array.prototype.find.call(el, function (x) { return x.checked; }) || el[el.length - 1]; return c.type === 'checkbox' ? (c.checked ? c.value : '') : c.value; } return el.value; };
  Wizard.prototype.radio = function (name) { var c = $('input[name="' + name + '"]:checked', this.form); return c ? c.value : ''; };
  Wizard.prototype.styles = function () { return $$('[data-style-index]', this.form).map(function (t) { return t.value; }); };
  Wizard.prototype.post = function () {
    var el = $('[data-post-input]:checked', this.form) || $('input[type=hidden][data-post-input]', this.form);
    return el ? ((CFG.posts || {})[el.value] || null) : null;
  };
  Wizard.prototype.product = function () {
    var id = ($('[data-card-product]', this.form) || {}).value;
    return id ? ((CFG.products || {})[id] || null) : null;
  };
  Wizard.prototype.cardImage = function () {
    var src = this.radio('settings[card][image_source]') || 'post';
    var post = this.post(); var product = this.product();
    if (src === 'post') return post && post.cover;
    if (src === 'product') return product && product.image;
    if (src === 'url') { var u = this.val('settings[card][image_url]').trim(); return /^https?:\/\//i.test(u) ? u : null; }
    return null;
  };
  Wizard.prototype.state = function () {
    var post = this.post() || {};
    var product = this.product();
    var firstKw = $$('[data-kw-row]', this.form).filter(function (r) { return $('[data-name=is_active]', r).checked; })[0];
    var title = this.val('settings[card][title]').trim() || (product && product.name) || (post.caption ? post.caption.slice(0, 60) : '');
    return {
      cover: post.cover, caption: post.caption, likes: post.likes,
      keyword: firstKw ? $('[data-kw-label]', firstKw).textContent : '',
      replyOn: this.isOn('[data-toggle-reply]'), styles: this.styles(),
      aiPersonalize: !!$('input[type=checkbox][name="settings[reply][ai_personalize]"]:checked', this.form),
      dmOn: this.isOn('[data-toggle-dm]'), followOn: this.isOn('[data-toggle-follow]'), mode: this.radio('settings[dm][mode]'),
      openingText: this.val('settings[dm][opening_text]'), openingButton: this.val('settings[dm][opening_button]'),
      followText: this.val('settings[follow][text]'), followButton: this.val('settings[follow][button]'),
      intro: this.val('settings[card][intro_text]'), after: this.val('settings[card][after_text]'),
      image: this.cardImage(), title: title, subtitle: this.val('settings[card][subtitle]'),
      buttons: $$('[data-btn-row]', this.form).map(function (r) { return { label: $('[data-btn-label]', r).value.trim() }; })
    };
  };
  Wizard.prototype.refresh = function () {
    this.applyToggles();
    this.updateImagePreview();
    this.updateProductCard();
    this.testKeyword();
    var self = this;
    $$('[data-step-go]').forEach(function (b, k) {
      var done = k !== self.current && self.visited[k] && self.validate(k, false);
      b.classList.toggle('is-done', !!done);
      var check = $('[data-step-check]', b); if (check) check.hidden = !done;
    });
    if (this.phone) this.phone.render();
    if (this.current === this.steps.length - 1) this.summary();
  };

  /* ── قدم ۱: انتخاب پست ── */
  Wizard.prototype.initPosts = function () {
    var search = $('[data-post-search]');
    if (search) {
      search.addEventListener('input', function () {
        var q = normalize(search.value);
        $$('[data-post-item]').forEach(function (item) { item.hidden = q !== '' && normalize(item.getAttribute('data-search')).indexOf(q) === -1; });
      });
    }
    var self = this;
    $$('[data-post-input]', this.form).forEach(function (input) {
      input.addEventListener('change', function () {
        if (!input.checked || !self.autoKeywordSeed) return;
        var post = CFG.posts && CFG.posts[input.value];
        var words = captionKeywords(post && post.caption);
        if (words.length) self.applyCaptionKeywords(words);
      });
    });
  };

  /* ── محصول هدف: جست‌وجو، پیشنهاد و کارت خلاصه ── */
  Wizard.prototype.initProducts = function () {
    var self = this;
    var search = $('[data-product-search]', this.form);
    var select = $('[data-card-product]', this.form);
    if (!select) return;
    var filter = function () {
      var q = normalize(search ? search.value : '');
      Array.prototype.forEach.call(select.options, function (option) {
        option.hidden = !!q && option.value !== '' && normalize(option.getAttribute('data-search') || option.textContent).indexOf(q) === -1;
      });
    };
    if (search) search.addEventListener('input', filter);
    select.addEventListener('change', function () { self.updateProductCard(); self.refresh(); });
    $$('[data-product-suggestion]', this.form).forEach(function (button) {
      button.addEventListener('click', function () {
        select.value = button.getAttribute('data-product-suggestion') || '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
    filter();
  };
  Wizard.prototype.updateProductCard = function () {
    var box = $('[data-product-card]', this.form); var product = this.product();
    if (!box) return;
    if (!product) { box.hidden = true; box.innerHTML = ''; return; }
    box.hidden = false;
    box.innerHTML = (product.image ? '<img src="' + esc(product.image) + '" alt="" loading="lazy"><div>' : '<div class="sip-product-card-noimage"><i class="fa-regular fa-image"></i></div><div>')
      + '<b>' + esc(product.name || '') + '</b><small>' + esc((product.description || '').slice(0, 150)) + '</small><a href="' + esc(product.url || '#') + '" target="_blank" rel="noopener">مشاهده محصول <i class="fa-solid fa-arrow-up-left-from-circle"></i></a></div>';
  };

  /* ── قدم ۲: کلمات کلیدی ── */
  Wizard.prototype.initKeywords = function () {
    var self = this;
    var input = $('[data-kw-new]', this.form);
    var add = function () {
      self.autoKeywordSeed = false;
      String(input.value).split(/[,،\n]+/).forEach(function (w) { self.addKeyword(w); });
      input.value = '';
      input.focus();
      self.refresh();
    };
    $('[data-kw-add]', this.form).addEventListener('click', add);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); add(); } });
    $('[data-kw-list]', this.form).addEventListener('click', function (e) {
      var rm = e.target.closest('[data-kw-remove]');
      if (rm) { self.autoKeywordSeed = false; rm.closest('[data-kw-row]').remove(); self.reindexKeywords(); self.refresh(); }
    });
    var test = $('[data-kw-test]', this.form);
    if (test) test.addEventListener('input', function () { self.testKeyword(); });
  };
  Wizard.prototype.addKeyword = function (word) {
    word = String(word || '').replace(/\s+/g, ' ').trim().slice(0, 120);
    var norm = normalize(word);
    if (!norm) return;
    var exists = $$('[data-kw-row]', this.form).filter(function (r) { return normalize($('[data-name=keyword]', r).value) === norm; })[0];
    if (exists) { exists.classList.remove('sip-flash-field'); void exists.offsetWidth; exists.classList.add('sip-flash-field'); return; }
    var tpl = $('[data-kw-template]', this.form).content.firstElementChild.cloneNode(true);
    $('[data-kw-label]', tpl).textContent = word;
    $('[data-name=keyword]', tpl).value = word;
    if (word.indexOf('*') !== -1) $('[data-name=match_mode]', tpl).value = 'pattern';
    $('[data-kw-list]', this.form).appendChild(tpl);
    if (!this.replacingKeywords) this.autoKeywordSeed = false;
    this.reindexKeywords();
  };
  Wizard.prototype.applyCaptionKeywords = function (words) {
    var list = $('[data-kw-list]', this.form);
    if (!list || !words.length) return;
    this.replacingKeywords = true;
    list.innerHTML = '';
    words.forEach(function (word) { this.addKeyword(word); }, this);
    this.replacingKeywords = false;
    this.autoKeywordSeed = true;
    this.reindexKeywords();
    this.refresh();
  };
  Wizard.prototype.reindexKeywords = function () {
    var rows = $$('[data-kw-row]', this.form);
    rows.forEach(function (r, i) {
      $('[data-name=keyword]', r).name = 'keywords[' + i + '][keyword]';
      $('[data-name=match_mode]', r).name = 'keywords[' + i + '][match_mode]';
      $('[data-name=is_active_off]', r).name = 'keywords[' + i + '][is_active]';
      $('[data-name=is_active]', r).name = 'keywords[' + i + '][is_active]';
    });
    var empty = $('[data-kw-empty]', this.form);
    if (empty) empty.hidden = rows.length > 0;
  };
  Wizard.prototype.testKeyword = function () {
    var input = $('[data-kw-test]', this.form); var out = $('[data-kw-test-result]', this.form);
    if (!input || !out) return;
    if (!input.value.trim()) { out.textContent = ''; return; }
    var hit = $$('[data-kw-row]', this.form).filter(function (r) {
      return $('[data-name=is_active]', r).checked && matches(input.value, $('[data-name=keyword]', r).value, $('[data-name=match_mode]', r).value);
    })[0];
    out.innerHTML = hit
      ? '<span style="color:var(--success)"><i class="fa-solid fa-circle-check"></i> منطبق با «' + esc($('[data-kw-label]', hit).textContent) + '»</span>'
      : '<span style="color:var(--danger)"><i class="fa-solid fa-circle-xmark"></i> منطبق نیست</span>';
  };

  /* ── شمارنده‌ها و متغیرها ── */
  Wizard.prototype.initCounters = function (root) {
    var update = function (el) {
      var out = $('[data-counter-out]', el.closest('.si-field, .sip-style'));
      if (!out) return;
      var max = +el.getAttribute('data-counter'); var n = el.value.length;
      out.textContent = faDigits(n) + ' / ' + faDigits(max);
      out.classList.toggle('is-over', n > max);
    };
    $$('[data-counter]', root).forEach(update);
    root.addEventListener('input', function (e) { if (e.target.matches('[data-counter]')) update(e.target); });
    this.updateCounter = update;
  };
  Wizard.prototype.initVars = function () {
    var self = this; var last = null;
    $$('[data-style-index]', this.form).forEach(function (t) { t.addEventListener('focus', function () { last = t; }); });
    $$('[data-insert]', this.form).forEach(function (b) {
      b.addEventListener('click', function () {
        var t = last || $('[data-style-index]', self.form);
        var v = b.getAttribute('data-insert'); var s = t.selectionStart || t.value.length;
        t.value = (t.value.slice(0, s) + v + t.value.slice(t.selectionEnd || s)).slice(0, +t.getAttribute('maxlength') || 300);
        t.focus(); t.setSelectionRange(s + v.length, s + v.length);
        self.updateCounter(t); self.refresh();
      });
    });
  };

  /* ── کلیدهای روشن/خاموش ── */
  Wizard.prototype.initToggles = function () { this.applyToggles(); };
  Wizard.prototype.applyToggles = function () {
    var f = this.form;
    var reply = this.isOn('[data-toggle-reply]'), dm = this.isOn('[data-toggle-dm]'), follow = this.isOn('[data-toggle-follow]');
    var dim = function (el, on) { if (el) { el.style.opacity = on ? '' : '.45'; } };
    dim($('[data-reply-fields]', f), reply);
    $('[data-dm-fields]', f).hidden = !dm;
    $('[data-dm-off-note]', f).hidden = dm;
    $('[data-card-fields]', f).hidden = !dm;
    $('[data-card-off-note]', f).hidden = dm;
    $('[data-follow-fields]', f).hidden = !follow;
    $$('[data-flow-follow]', f).forEach(function (el) { el.hidden = !follow; });
    var direct = $('[data-direct-card] input', f);
    if (direct) {
      direct.disabled = follow;
      $('[data-direct-card]', f).style.opacity = follow ? '.5' : '';
      if (follow && direct.checked) { $('input[name="settings[dm][mode]"][value=opening_then_card]', f).checked = true; }
    }
    var isDirect = this.radio('settings[dm][mode]') === 'direct_card' && !follow;
    $('[data-opening-fields]', f).hidden = isDirect;
    $$('[data-flow-opening]', f).forEach(function (el) { el.hidden = isDirect; });
  };

  /* ── قدم ۵: دکمه‌ها ── */
  Wizard.prototype.initButtons = function () {
    var self = this; var list = $('[data-btn-list]', this.form);
    $('[data-btn-add]', this.form).addEventListener('click', function () { self.addButton(); self.refresh(); });
    list.addEventListener('click', function (e) {
      var row = e.target.closest('[data-btn-row]'); if (!row) return;
      if (e.target.closest('[data-btn-remove]')) row.remove();
      else if (e.target.closest('[data-btn-up]') && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
      else if (e.target.closest('[data-btn-down]') && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
      else return;
      self.reindexButtons(); self.refresh();
    });
    list.addEventListener('change', function (e) {
      if (!e.target.matches('[data-btn-preset]')) return;
      var row = e.target.closest('[data-btn-row]'); var opt = e.target.selectedOptions[0];
      var label = $('[data-btn-label]', row);
      var prev = row.getAttribute('data-preset-label');
      if (!label.value.trim() || label.value === prev || Object.keys(CFG.presets || {}).some(function (k) { return CFG.presets[k].label === label.value; })) label.value = opt.getAttribute('data-label');
      row.setAttribute('data-preset-label', opt.getAttribute('data-label'));
      self.setButtonType(row, opt.getAttribute('data-type'));
      self.updateCounter(label);
    });
    this.reindexButtons();
  };
  Wizard.prototype.setButtonType = function (row, type) {
    $('[data-btn-type]', row).value = type;
    $('[data-btn-url-wrap]', row).hidden = type === 'postback';
    $('[data-btn-reply-wrap]', row).hidden = type !== 'postback';
  };
  Wizard.prototype.addButton = function (label) {
    var list = $('[data-btn-list]', this.form);
    if ($$('[data-btn-row]', list).length >= (CFG.maxButtons || 3)) return null;
    var tpl = $('[data-btn-template]', this.form).content.firstElementChild.cloneNode(true);
    if (label) $('[data-btn-label]', tpl).value = label;
    list.appendChild(tpl);
    this.reindexButtons();
    this.updateCounter($('[data-btn-label]', tpl));
    return tpl;
  };
  Wizard.prototype.reindexButtons = function () {
    var rows = $$('[data-btn-row]', this.form);
    rows.forEach(function (r, i) {
      $$('[name]', r).forEach(function (el) { el.name = el.name.replace(/\[buttons\]\[[^\]]*\]/, '[buttons][' + i + ']'); });
    });
    var max = CFG.maxButtons || 3;
    var add = $('[data-btn-add]', this.form); if (add) add.disabled = rows.length >= max;
    var cnt = $('[data-btn-count]', this.form); if (cnt) cnt.textContent = '(' + faDigits(rows.length) + ' از ' + faDigits(max) + ')';
  };

  /* ── تصویر کارت ── */
  Wizard.prototype.initImage = function () { this.updateImagePreview(); };
  Wizard.prototype.updateImagePreview = function () {
    var box = $('[data-card-img-preview]', this.form); if (!box) return;
    var src = this.cardImage();
    var key = src || '';
    if (box.getAttribute('data-src') !== key) {
      box.setAttribute('data-src', key);
      box.innerHTML = src ? '<img src="' + esc(src) + '" alt="">' : '<i class="fa-solid fa-image"></i>';
    }
    var urlField = $('[data-img-url-field]', this.form);
    if (urlField) urlField.hidden = this.radio('settings[card][image_source]') !== 'url';
  };

  /* ── جمع‌بندی قدم آخر ── */
  Wizard.prototype.summary = function () {
    var box = $('[data-summary]', this.form); if (!box) return;
    var st = this.state();
    var kws = $$('[data-kw-row]', this.form).length;
    var items = [
      [!!this.post(), this.post() ? 'پست انتخاب شده' : 'پست انتخاب نشده'],
      [kws > 0, faDigits(kws) + ' کلمه‌ی کلیدی'],
      [true, st.replyOn ? 'پاسخ عمومی با ' + faDigits(st.styles.filter(function (s) { return s.trim(); }).length) + ' سبک' + (st.aiPersonalize ? ' + هوش مصنوعی' : '') : 'پاسخ عمومی خاموش'],
      [true, st.dmOn ? (st.followOn ? 'دایرکت با فالو اجباری' : 'دایرکت بدون شرط فالو') : 'دایرکت خاموش'],
      [!st.dmOn || this.validate(4, false), st.dmOn ? 'کارت با ' + faDigits(st.buttons.filter(function (b) { return b.label; }).length) + ' دکمه' : 'بدون کارت'],
      [!!st.image || !st.dmOn, st.image ? 'تصویر کارت آماده' : 'کارت بدون تصویر']
    ];
    box.innerHTML = items.map(function (it) { return '<div><i class="fa-solid ' + (it[0] ? 'fa-circle-check is-ok' : 'fa-triangle-exclamation is-warn') + '"></i>' + esc(it[1]) + '</div>'; }).join('');
  };

  /* ── دستیار هوش مصنوعی ── */
  Wizard.prototype.initAi = function () {
    var box = $('[data-sip-ai]'); if (!box) return;
    var self = this;
    $$('[data-ai-tab]', box).forEach(function (b) {
      b.addEventListener('click', function () {
        var tab = b.getAttribute('data-ai-tab');
        $$('[data-ai-tab]', box).forEach(function (x) { x.classList.toggle('is-active', x === b); });
        $$('[data-ai-pane]', box).forEach(function (p) { p.hidden = p.getAttribute('data-ai-pane') !== tab; });
      });
    });
    var run = $('[data-ai-run]', box); var status = $('[data-ai-status]', box);
    var execute = function (sections, trigger) {
      if (!sections.length) { status.textContent = 'حداقل یک بخش را انتخاب کنید.'; return; }
      var postEl = $('[data-post-input]:checked', self.form) || $('input[type=hidden][data-post-input]', self.form);
      var body = {
        sections: sections,
        post_id: postEl ? +postEl.value : null,
        product_id: +(($('[data-card-product]', self.form) || {}).value || 0) || null,
        link: ($('[data-ai-link]', box) || {}).value || null,
        hint: ($('[data-ai-hint]', box) || {}).value || null,
        keywords: $$('[data-kw-row] [data-name=keyword]', self.form).map(function (i) { return i.value; })
      };
      if (trigger) trigger.disabled = true;
      status.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> در حال نوشتن…';
      request(CFG.routes.generate, body).then(function (res) {
        if (!res.ok) { status.textContent = res.message || 'پاسخی دریافت نشد.'; return; }
        var n = self.applyAi(res.fields || {});
        var complete = $('[data-ai-applied-check]', box);
        if (complete && n > 0 && trigger === run) complete.hidden = false;
        status.innerHTML = '<span style="color:var(--success)"><i class="fa-solid fa-circle-check"></i> ' + faDigits(n) + ' فیلد پر شد' + (res.model ? ' · ' + esc(res.model) : '') + ' — بازبینی و در صورت نیاز ویرایش کنید.</span>';
      }).catch(function (err) { status.textContent = err.message; }).then(function () { if (trigger) trigger.disabled = false; });
    };
    run.addEventListener('click', function () {
      execute($$('[data-ai-section]:checked', box).map(function (c) { return c.value; }), run);
    });
    var fieldSection = function (key) {
      if (key.indexOf('public_replies.') === 0) return 'public_reply';
      if (key.indexOf('opening_') === 0) return 'opening';
      if (key.indexOf('follow_') === 0) return 'follow';
      return 'card';
    };
    $$('[data-ai-field], [data-btn-label]', self.form).forEach(function (field) {
      var label = field.parentElement && ($('label', field.parentElement) || $('.sip-style-tag', field.parentElement));
      if (!label || $('[data-ai-tools]', label)) return;
      var key = field.getAttribute('data-ai-field') || 'card_buttons'; var tools = document.createElement('span');
      tools.className = 'sip-ai-field-tools'; tools.setAttribute('data-ai-tools', '');
      tools.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles" title="تولیدشده با هوش مصنوعی"></i><button type="button" data-ai-refresh-field title="تولید نمونه‌ی تازه"><i class="fa-solid fa-rotate"></i></button>';
      label.appendChild(tools);
      $('[data-ai-refresh-field]', tools).addEventListener('click', function () {
        execute([fieldSection(key)], this);
      });
    });
    var save = $('[data-ai-save]', box);
    if (save) save.addEventListener('click', function () {
      var out = $('[data-ai-save-status]', box);
      var prompts = {};
      $$('[data-ai-prompt]', box).forEach(function (t) { prompts[t.getAttribute('data-ai-prompt')] = t.value; });
      save.disabled = true;
      request(CFG.routes.aiSettings, { model: $('[data-ai-model]', box).value.trim(), prompts: prompts })
        .then(function (res) { out.innerHTML = '<span style="color:var(--success)"><i class="fa-solid fa-circle-check"></i> ' + esc(res.message || 'ذخیره شد') + '</span>'; })
        .catch(function (err) { out.textContent = err.message; })
        .then(function () { save.disabled = false; });
    });
  };
  Wizard.prototype.applyAi = function (fields) {
    var self = this; var count = 0;
    var put = function (el, value) {
      if (!el || value == null || value === '') return;
      var max = +el.getAttribute('maxlength') || 0;
      el.value = max ? String(value).slice(0, max) : value;
      el.classList.remove('sip-flash-field'); void el.offsetWidth; el.classList.add('sip-flash-field');
      el.setAttribute('data-ai-applied', '1');
      if (el.matches('[data-counter]')) self.updateCounter(el);
      count++;
    };
    (fields.public_replies || []).forEach(function (v, i) { put($('[data-ai-field="public_replies.' + i + '"]', self.form), v); });
    ['opening_text', 'opening_button', 'follow_text', 'follow_retry_text', 'follow_button', 'card_intro', 'card_title', 'card_subtitle'].forEach(function (k) {
      put($('[data-ai-field="' + k + '"]', self.form), fields[k]);
    });
    (fields.card_buttons || []).forEach(function (label, i) {
      var row = $$('[data-btn-row]', self.form)[i] || self.addButton();
      if (row) put($('[data-btn-label]', row), label);
    });
    this.refresh();
    return count;
  };

  function request(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CFG.csrf, 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (data) {
        if (r.status === 419) throw new Error('نشست منقضی شده؛ صفحه را دوباره بارگذاری کنید.');
        if (r.status === 429) throw new Error('درخواست‌ها زیاد شد؛ کمی بعد دوباره امتحان کنید.');
        if (r.status === 403) throw new Error('دسترسی این کار را ندارید.');
        if (!r.ok) {
          var first = data.errors ? data.errors[Object.keys(data.errors)[0]][0] : null;
          throw new Error(first || data.message || 'خطا در ارتباط با سرور.');
        }
        return data;
      });
    });
  }

  /* ═════════════ صفحه‌ی جزئیات: پیش‌نمایش ثابت + آزمون بدون ارسال ═════════════ */
  function initShow() {
    var preview = $('[data-sip-preview][data-sip-state]');
    if (preview && !$('#sip-form')) {
      var state = {};
      try { state = JSON.parse(preview.getAttribute('data-sip-state')) || {}; } catch (e) { state = {}; }
      var phone = new Phone(preview, function () { return state; });
      phone.render();
    }
    var sim = $('[data-sip-simulate]');
    if (!sim) return;
    var btn = $('[data-sim-run]', sim); var out = $('[data-sim-out]', sim); var text = $('[data-sim-text]', sim);
    var go = function () {
      if (!text.value.trim()) { text.focus(); return; }
      btn.disabled = true;
      request(sim.getAttribute('data-url'), { text: text.value, follows: $('[data-sim-follows]', sim).value }).then(function (res) {
        var h = '<div class="si-check-list">' + Object.keys(res.checks || {}).map(function (k) {
          return '<div><i class="fa-solid ' + (res.checks[k] ? 'fa-circle-check' : 'fa-circle-xmark') + '" style="color:var(--' + (res.checks[k] ? 'success' : 'danger') + ')"></i>' + esc(k) + '</div>';
        }).join('') + '</div>';
        if (!res.matched) { h += '<p class="si-muted" style="margin:6px 0 0">' + esc(res.message || 'این کامنت سناریو را شروع نمی‌کند.') + '</p>'; }
        (res.steps || []).forEach(function (s, i) {
          h += '<div class="sip-flow-step" style="text-align:right;margin-top:8px' + (s.ok === false ? ';border-color:var(--danger)' : '') + '"><b>' + faDigits(i + 1) + '. ' + esc(s.where) + ' · ' + esc(s.title) + '</b><div>' + esc(personalize(s.text || '')) + '</div></div>';
        });
        out.innerHTML = h;
      }).catch(function (err) { out.textContent = err.message; }).then(function () { btn.disabled = false; });
    };
    btn.addEventListener('click', go);
    text.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); go(); } });
  }

  function boot() {
    var form = $('#sip-form');
    if (form) new Wizard(form);
    initShow();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
