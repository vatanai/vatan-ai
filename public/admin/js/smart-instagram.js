/* اینستاگرام هوشمند — اسکریپت سبک ماژول (بدون وابستگی، defer) */
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  var fa = function (s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };

  function postJson(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(data),
      credentials: 'same-origin'
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, message: 'پاسخ نامعتبر از سرور (' + r.status + ')' }; })
        .then(function (j) { if (!r.ok && j && !j.message && j.errors) { j.message = Object.values(j.errors).flat().join(' '); } return j; });
    });
  }

  /* تأیید پیش از عملیات حساس */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.matches('[data-confirm]') && !window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); return; }
    var btn = form.querySelector('button[type="submit"], button:not([type])');
    if (btn && !form.matches('[data-no-lock]')) { setTimeout(function () { btn.disabled = true; btn.classList.add('is-disabled'); }, 0); }
  });

  /* بستن پیام‌ها */
  $$('[data-si-dismiss]').forEach(function (b) { b.addEventListener('click', function () { b.closest('.si-flash').remove(); }); });

  /* کپی */
  $$('[data-si-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = document.getElementById(b.getAttribute('data-si-copy'));
      if (!input) return;
      (navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.reject()).then(function () {
        var old = b.innerHTML; b.innerHTML = '<i class="fa-solid fa-check"></i>'; setTimeout(function () { b.innerHTML = old; }, 1400);
      }).catch(function () { input.select(); });
    });
  });

  /* ═══ صندوق گفتگو ═══ */
  var chat = $('#si-chat-body');
  if (chat) { chat.scrollTop = chat.scrollHeight; }

  var composer = $('#si-composer');
  if (composer) {
    var textarea = $('textarea[name="body"]', composer);
    var kindInput = $('input[name="kind"]', composer);
    var targetInput = $('input[name="target_ref"]', composer);
    var suggestionInput = $('input[name="suggestion_id"]', composer);

    $$('[data-si-kind]', composer).forEach(function (chip) {
      chip.addEventListener('click', function () {
        $$('[data-si-kind]', composer).forEach(function (c) { c.classList.remove('active'); });
        chip.classList.add('active');
        kindInput.value = chip.getAttribute('data-si-kind');
        targetInput.value = chip.getAttribute('data-target') || '';
      });
    });

    $$('[data-si-use-suggestion]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        textarea.value = btn.getAttribute('data-body') || '';
        suggestionInput.value = btn.getAttribute('data-si-use-suggestion');
        textarea.focus();
        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
      });
    });

    $$('[data-si-quick-reply]').forEach(function (btn) {
      btn.addEventListener('click', function () { textarea.value = btn.getAttribute('data-si-quick-reply'); textarea.focus(); });
    });

    textarea && textarea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); composer.requestSubmit ? composer.requestSubmit() : composer.submit(); }
    });
  }

  $$('[data-si-toggle-side]').forEach(function (b) {
    b.addEventListener('click', function () { var inbox = $('.si-inbox'); inbox && inbox.classList.toggle('show-side'); });
  });

  var inbox = $('.si-inbox[data-poll]');
  if (inbox) {
    var pollUrl = inbox.getAttribute('data-poll');
    var lastLatest = inbox.getAttribute('data-latest') || '';
    var lastMessage = parseInt(inbox.getAttribute('data-last-message') || '0', 10);
    var banner = $('#si-new-banner');
    var poll = function () {
      if (document.hidden) return;
      fetch(pollUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (!data) return;
          var changed = (data.latest && data.latest !== lastLatest) || (data.last_message_id && data.last_message_id > lastMessage);
          if (!changed) return;
          var typing = composer && $('textarea[name="body"]', composer) && $('textarea[name="body"]', composer).value.trim() !== '';
          if (typing) { banner && banner.removeAttribute('hidden'); }
          else { window.location.reload(); }
        }).catch(function () {});
    };
    setInterval(poll, 20000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
  }

  /* ═══ فرم اتومیشن ═══ */
  var actionsWrap = $('#si-actions');
  if (actionsWrap) {
    var tpl = $('#si-action-template');
    var counter = actionsWrap.children.length;
    var messaging = ['public_reply', 'private_reply', 'send_dm', 'create_task', 'create_deal'];

    var sync = function (row) {
      var type = $('select[data-field="type"]', row).value;
      $$('[data-show-for]', row).forEach(function (el) {
        var list = el.getAttribute('data-show-for').split(',');
        el.hidden = list.indexOf(type) === -1;
        $$('input,select,textarea', el).forEach(function (i) { i.disabled = el.hidden; });
      });
      var text = $('[data-field="text"]', row);
      if (text) { text.placeholder = messaging.indexOf(type) > -1 && type.indexOf('create') === 0 ? 'عنوان (از {name} می‌توانید استفاده کنید)' : 'متن پیام — {name} و {username} جایگزین می‌شوند'; }
    };

    var bind = function (row) {
      sync(row);
      $('select[data-field="type"]', row).addEventListener('change', function () { sync(row); });
      $('[data-remove-action]', row).addEventListener('click', function () {
        if (actionsWrap.children.length > 1) { row.remove(); } else { window.alert('حداقل یک اقدام لازم است.'); }
      });
    };
    $$('.si-action', actionsWrap).forEach(bind);

    $('#si-add-action') && $('#si-add-action').addEventListener('click', function () {
      if (actionsWrap.children.length >= 10) return;
      var html = tpl.innerHTML.replace(/__i__/g, String(counter++));
      var holder = document.createElement('div');
      holder.innerHTML = html.trim();
      var row = holder.firstElementChild;
      actionsWrap.appendChild(row);
      bind(row);
    });

    var trigger = $('select[name="trigger"]');
    var kw = $('#si-keywords-field');
    var syncTrigger = function () { if (trigger && kw) { kw.hidden = ['comment_keyword', 'dm_keyword'].indexOf(trigger.value) === -1 && !$('textarea', kw).value.trim(); } };
    trigger && trigger.addEventListener('change', syncTrigger);
    syncTrigger();
  }

  var simForm = $('#si-simulate');
  if (simForm) {
    simForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var out = $('#si-simulate-result');
      out.innerHTML = '<span class="si-muted"><i class="fa-solid fa-spinner fa-spin"></i> در حال بررسی…</span>';
      postJson(simForm.action, {
        text: simForm.text.value, source: simForm.source.value,
        new_contact: simForm.new_contact.checked, scope_ref: simForm.scope_ref ? simForm.scope_ref.value : null
      }).then(function (r) {
        if (!r || r.matched === undefined) { out.innerHTML = '<div class="si-error">' + esc((r && r.message) || 'خطا در بررسی') + '</div>'; return; }
        var html = '<div class="badge-pro ' + (r.matched ? 'badge-success' : 'badge-neutral') + '"><i class="fa-solid fa-circle"></i> ' + (r.matched ? 'این پیام قانون را اجرا می‌کند' : 'این پیام قانون را اجرا نمی‌کند') + '</div><div class="si-check-list">';
        Object.keys(r.checks || {}).forEach(function (k) {
          html += '<div><i class="fa-solid ' + (r.checks[k] ? 'fa-circle-check' : 'fa-circle-xmark') + '" style="color:var(--' + (r.checks[k] ? 'success' : 'danger') + ')"></i>' + esc(k) + '</div>';
        });
        html += '</div>';
        if (r.matched && r.actions) {
          html += '<ol class="si-ul">';
          r.actions.forEach(function (a) { html += '<li><b>' + esc(a.type) + '</b>' + (a.text ? ' — ' + esc(a.text) : '') + '</li>'; });
          html += '</ol>';
        }
        out.innerHTML = html;
      }).catch(function () { out.innerHTML = '<div class="si-error">ارتباط برقرار نشد.</div>'; });
    });
  }

  /* ═══ آزمایشگاه دستیار ═══ */
  var play = $('#si-playground');
  if (play) {
    play.addEventListener('submit', function (e) {
      e.preventDefault();
      var out = $('#si-play-out');
      var btn = $('button[type="submit"], button:not([type])', play) || {};
      btn.disabled = true;
      out.innerHTML = '<span class="si-muted"><i class="fa-solid fa-spinner fa-spin"></i> دستیار در حال خواندن دانش و نوشتن پاسخ است…</span>';
      var payload = { message: play.message.value, use_draft: play.use_draft && play.use_draft.checked };
      if (payload.use_draft) {
        var draft = document.getElementById('si-profile-form');
        if (draft) { payload.persona_prompt = draft.persona_prompt.value; payload.tone = draft.tone.value; payload.reply_length = draft.reply_length.value; }
      }
      postJson(play.action, payload).then(function (r) {
        btn.disabled = false;
        if (!r || !r.ok) { out.innerHTML = '<div class="si-flash is-danger">' + esc((r && r.message) || 'خطا') + '</div>'; return; }
        var o = r.output || {};
        var flags = (r.flags || []).map(function (f) {
          var map = { sensitive: 'موضوع حساس', forbidden_phrase: 'عبارت ممنوع', unverified_numbers: 'عدد تأییدنشده', no_knowledge: 'بدون دانش مرتبط', low_confidence: 'اطمینان پایین' };
          return '<span class="badge-pro badge-warning">' + esc(map[f] || f) + '</span>';
        }).join(' ');
        var html = '<div class="si-play-reply">' + esc(o.reply || '(پاسخی پیشنهاد نشد)') + '</div>';
        html += '<div class="si-status-strip" style="margin-top:10px">'
          + '<span class="badge-pro badge-primary">نیت: ' + esc(o.intent) + '</span>'
          + '<span class="badge-pro badge-info">اطمینان: ' + fa(Math.round((o.confidence || 0) * 100)) + '٪</span>'
          + (o.needs_human ? '<span class="badge-pro badge-danger">ارجاع به انسان</span>' : '<span class="badge-pro badge-success">قابل پاسخ</span>')
          + flags + '</div>';
        if (o.reason) html += '<div class="si-help"><b>دلیل:</b> ' + esc(o.reason) + '</div>';
        if (o.next_action) html += '<div class="si-help"><b>اقدام بعدی:</b> ' + esc(o.next_action) + '</div>';
        if ((o.missing_info || []).length) html += '<div class="si-help"><b>اطلاعات کم:</b> ' + esc(o.missing_info.join('، ')) + '</div>';
        if ((r.sources || []).length) {
          html += '<div class="si-label" style="margin-top:12px">منابع بازیابی‌شده</div>';
          r.sources.forEach(function (s) { html += '<span class="si-source-chip"><b>' + esc(s.title) + '</b><br>' + esc(s.excerpt) + '</span>'; });
        } else {
          html += '<div class="si-help" style="margin-top:10px">هیچ منبع دانش تأییدشده‌ای مرتبط نبود.</div>';
        }
        html += '<div class="si-help" style="margin-top:8px">' + esc(r.model || '') + ' · ' + fa(r.duration_ms || 0) + ' میلی‌ثانیه</div>';
        out.innerHTML = html;
      }).catch(function () { btn.disabled = false; out.innerHTML = '<div class="si-flash is-danger">ارتباط برقرار نشد.</div>'; });
    });
  }

  /* پرکردن عنوان منبع از نام فایل */
  var fileInput = $('#si-knowledge-file');
  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var title = $('#si-knowledge-title');
      if (title && !title.value && fileInput.files[0]) { title.value = fileInput.files[0].name.replace(/\.[^.]+$/, ''); }
    });
  }
})();
