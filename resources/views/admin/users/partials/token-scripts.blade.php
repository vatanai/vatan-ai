{{-- پارشیال مدیریت اعتبار: اسکریپت‌های صفحه (جستجو، انتخاب کاربر، میانبرها، اعمال اعتبار، تاریخچه) --}}
<script>
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var selectedUser = null;
  var lastResults = [];
  var searchDebounce = null;
  var toastTimer = null;

  function $(id) { return document.getElementById(id); }

  /* عنوان بردکرامب هدر */
  var bc = $('breadcrumb');
  if (bc) bc.textContent = 'مدیریت اعتبار';

  /* جلوگیری از XSS هنگام رندر داده‌های کاربر در HTML */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function faNum(n) {
    var v = parseInt(n, 10);
    if (isNaN(v)) v = 0;
    try { return v.toLocaleString('fa-IR'); } catch (e) { return String(v); }
  }

  /* ─── جستجوی کاربر (نام / نام‌خانوادگی / ایمیل / موبایل) ─── */
  window.tkSearch = function (q) {
    clearTimeout(searchDebounce);
    var box = $('tkSearchResults');
    if (!q || !q.trim()) { box.style.display = 'none'; box.innerHTML = ''; return; }
    searchDebounce = setTimeout(function () {
      fetch('/api/v1/admin/users/search?q=' + encodeURIComponent(q.trim()) + '&limit=8', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
        .then(function (d) { renderResults((d && d.data) || []); })
        .catch(function () {
          box.style.display = 'block';
          box.innerHTML = '<div class="tk-no-result">خطا در جستجو — دوباره تلاش کنید</div>';
        });
    }, 300);
  };

  function renderResults(list) {
    var el = $('tkSearchResults');
    lastResults = list;
    el.style.display = 'block';
    if (!list.length) {
      el.innerHTML = '<div class="tk-no-result">کاربری با این مشخصات پیدا نشد</div>';
      return;
    }
    el.innerHTML = list.map(function (u, i) {
      return '<div class="tk-result-item" onclick="tkPick(' + i + ')">' +
        '<div class="tk-avatar">' + esc((u.name || 'ک').trim().charAt(0)) + '</div>' +
        '<div style="flex:1;min-width:0;">' +
          '<div class="tk-user-name">' + esc(u.name || '—') + '</div>' +
          '<div class="tk-user-meta">' + esc(u.phone || u.email || '—') + '</div>' +
        '</div>' +
        '<div class="tk-token-badge">' + faNum(u.token) + ' اعتبار</div>' +
      '</div>';
    }).join('');
  }

  window.tkPick = function (i) { if (lastResults[i]) tkSelect(lastResults[i]); };

  /* ─── انتخاب / حذف انتخاب کاربر ─── */
  function tkSelect(u) {
    selectedUser = u;
    $('tkSearchResults').style.display = 'none';
    $('tkSearchResults').innerHTML = '';
    $('tkSearchInput').value = '';
    $('tkSelAvatar').textContent = (u.name || 'ک').trim().charAt(0);
    $('tkSelName').textContent = u.name || '—';
    $('tkSelMeta').textContent = [u.phone, u.email].filter(Boolean).join(' · ') || '—';
    $('tkSelToken').textContent = faNum(u.token);
    $('tkSelectedCard').style.display = 'block';
    $('tkSubmitBtn').disabled = false;
    $('tkHistoryLabel').textContent = 'تاریخچه ' + (u.name || 'کاربر');
    tkUpdatePreview();
    tkLoadHistory(u.id);
  }

  window.tkClear = function () {
    selectedUser = null;
    $('tkSelectedCard').style.display = 'none';
    $('tkSubmitBtn').disabled = true;
    $('tkHistoryLabel').textContent = 'آخرین ۲۰ تغییر (همه کاربران)';
    tkUpdatePreview();
    tkLoadHistory(null);
  };

  /* ─── میانبرهای سریع (۱ / ۵ / ۱۰ / ۲۰ / ۵۰) — هر کلیک مقدار فیلد را با همان عدد جایگزین می‌کند (جمع نمی‌شود) ─── */
  window.tkQuick = function (type, n) {
    $('tkAction').value = type;
    $('tkAmount').value = n;
    tkUpdatePreview();
  };

  window.tkUpdateExpiryMode = function () {
    var unit = $('tkExpiryUnit').value;
    var visible = $('tkAction').value === 'add' && $('tkCreditKind').value !== 'paid_adjustment';
    $('tkExpiryGroup').style.display = visible ? 'block' : 'none';
    $('tkExpiryValue').style.display = visible && ['days','weeks','months'].indexOf(unit) >= 0 ? 'block' : 'none';
    $('tkExpiryDate').style.display = visible && unit === 'date' ? 'block' : 'none';
  };

  function tkExpiryAt() {
    var unit = $('tkExpiryUnit')?.value || 'none';
    if (unit === 'date') return $('tkExpiryDate').value || null;
    if (['days','weeks','months'].indexOf(unit) < 0) return null;
    var n = parseInt($('tkExpiryValue').value, 10);
    if (!Number.isFinite(n) || n < 1) return null;
    var d = new Date();
    if (unit === 'days') d.setDate(d.getDate() + n);
    if (unit === 'weeks') d.setDate(d.getDate() + (n * 7));
    if (unit === 'months') d.setMonth(d.getMonth() + n);
    return d.toISOString();
  }

  /* ─── پیش‌نمایش موجودی پس از اعمال ─── */
  window.tkUpdatePreview = function () {
    var el = $('tkPreview');
    var action = $('tkAction').value;
    if ($('tkCreditKindGroup')) $('tkCreditKindGroup').style.display = action === 'deduct' ? 'none' : 'block';
    var amount = parseInt($('tkAmount').value, 10);
    if (!selectedUser || isNaN(amount) || amount < 0) { el.style.display = 'none'; return; }
    var cur = parseInt(selectedUser.token, 10) || 0;
    var after = action === 'add' ? cur + amount : (action === 'deduct' ? cur - amount : amount);
    el.style.display = 'block';
    if (after < 0) {
      el.className = 'tk-preview is-danger';
      el.innerHTML = 'موجودی کاربر کافی نیست (کمبود: <b>' + faNum(Math.abs(after)) + '</b> اعتبار)';
    } else {
      el.className = 'tk-preview';
      el.innerHTML = 'موجودی پس از اعمال: <b>' + faNum(after) + '</b> اعتبار';
    }
    el.style.display = 'block';
  };

  /* ─── اعمال تغییر اعتبار روی سرور ─── */
  window.tkSubmit = function () {
    if (!selectedUser) return tkToast('error', 'ابتدا یک کاربر را جستجو و انتخاب کنید');
    var action = $('tkAction').value;
    var amount = parseInt($('tkAmount').value, 10);
    if (isNaN(amount) || amount < 0 || (action !== 'set' && amount < 1)) {
      return tkToast('error', 'مقدار اعتبار را به‌درستی وارد کنید');
    }
    if (action === 'deduct' && amount > (parseInt(selectedUser.token, 10) || 0)) {
      return tkToast('error', 'موجودی کاربر کافی نیست');
    }

    var btn = $('tkSubmitBtn');
    var oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> در حال اعمال...';

    fetch('/api/v1/admin/users/' + selectedUser.id + '/token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf
      },
        body: JSON.stringify({ action: action, amount: amount, credit_kind: $('tkCreditKind').value, note: $('tkNote').value || null, expires_at: action === 'add' && $('tkCreditKind').value !== 'paid_adjustment' ? tkExpiryAt() : null, send_sms: !!$('tkSendSms')?.checked })
    })
      .then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; });
      })
      .then(function (res) {
        if (!res.ok || !res.d || res.d.status !== 'success') {
          throw new Error((res.d && res.d.message) || 'خطا در اعمال اعتبار');
        }
        selectedUser.token = res.d.new_balance;
        $('tkSelToken').textContent = faNum(res.d.new_balance);
        $('tkAmount').value = '';
        $('tkNote').value = '';
        tkUpdatePreview();
        tkToast('success', res.d.message || 'اعتبار با موفقیت اعمال شد');
        tkLoadHistory(selectedUser.id);
      })
      .catch(function (e) {
        tkToast('error', (e && e.message) || 'ارتباط با سرور برقرار نشد');
      })
      .finally(function () {
        btn.disabled = !selectedUser;
        btn.innerHTML = oldHtml;
      });
  };

  /* ─── تاریخچه‌ی تغییرات اعتبار ─── */
  function tkLoadHistory(userId) {
    var box = $('tkHistoryItems');
    box.innerHTML = '<div class="tk-h-loading">در حال بارگذاری...</div>';
    var url = userId ? '/api/v1/admin/users/' + encodeURIComponent(userId) + '/token-history' : '/api/v1/admin/token-history';
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
      .then(function (d) { renderHistory((d && d.data) || []); })
      .catch(function () {
        box.innerHTML = '<div class="tk-h-loading">خطا در دریافت تاریخچه</div>';
      });
  }

  var hConf = {
    add:    { icon: 'fa-plus',   cls: 'tk-h-add',    sign: '+', label: 'افزودن اعتبار' },
    deduct: { icon: 'fa-minus',  cls: 'tk-h-deduct', sign: '−', label: 'کسر اعتبار' },
    set:    { icon: 'fa-equals', cls: 'tk-h-set',    sign: '',  label: 'تنظیم مستقیم' }
  };

  function renderHistory(items) {
    var box = $('tkHistoryItems');
    if (!items.length) {
      box.innerHTML = '<div class="tk-h-loading">هنوز تغییری ثبت نشده است</div>';
      return;
    }
    box.innerHTML = items.map(function (h) {
      var c = hConf[h.type] || hConf.set;
      var desc = esc(h.note || c.label) + (h.admin ? ' · توسط ' + esc(h.admin) : '');
      return '<div class="tk-h-item">' +
        '<div class="tk-h-icon ' + c.cls + '"><i class="fa-solid ' + c.icon + '"></i></div>' +
        '<div style="flex:1;min-width:0;">' +
          '<div class="tk-h-user">' + esc(h.user || '—') + '</div>' +
          '<div class="tk-h-desc">' + desc + '</div>' +
        '</div>' +
        '<div style="text-align:left;">' +
          '<div class="tk-h-amount tk-amt-' + (hConf[h.type] ? h.type : 'set') + '">' + c.sign + faNum(h.amount) + '</div>' +
          '<div class="tk-h-meta">موجودی: ' + faNum(h.balance_after) + '</div>' +
          '<div class="tk-h-meta">' + esc(h.time || '') + '</div>' +
          (h.expires_at ? '<div class="tk-h-meta">انقضا: ' + esc(h.expires_at) + '</div>' : '') +
        '</div>' +
        '<button type="button" class="tk-clear-btn" style="margin:0;white-space:nowrap;" onclick="tkResendSms(' + Number(h.id) + ')"><i class="fa-solid fa-paper-plane"></i> پیامک</button>' +
      '</div>';
    }).join('');
  }

  window.tkResendSms = function (id) {
    fetch('/api/v1/admin/token-history/' + encodeURIComponent(id) + '/sms', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) { tkToast(res.ok && res.d.status === 'success' ? 'success' : 'error', res.d.message || 'ارسال پیامک انجام نشد.'); })
      .catch(function () { tkToast('error', 'ارتباط با سرور برقرار نشد.'); });
  };

  /* ─── توست نتیجه‌ی عملیات ─── */
  window.tkToast = function (type, msg) {
    var t = $('tkToast');
    clearTimeout(toastTimer);
    t.className = 'tk-toast ' + type;
    t.innerHTML = '<i class="fa-solid ' + (type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation') + '"></i> ' + esc(msg);
    /* رفرش انیمیشن */
    void t.offsetWidth;
    t.classList.add('show');
    toastTimer = setTimeout(function () { t.classList.remove('show'); }, 3200);
  };

  /* ─── پیش‌بارگذاری کاربر از بخش کاربران (?user_id=...) ─── */
  var preId = new URLSearchParams(window.location.search).get('user_id');
  if (preId) {
    fetch('/api/v1/admin/users/' + encodeURIComponent(preId), { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
      .then(function (d) {
        if (d && d.data) { tkSelect(d.data); } else { throw new Error(); }
      })
      .catch(function () {
        tkToast('error', 'کاربر موردنظر یافت نشد');
        tkLoadHistory(null);
      });
  } else {
    tkLoadHistory(null);
  }
})();
</script>
