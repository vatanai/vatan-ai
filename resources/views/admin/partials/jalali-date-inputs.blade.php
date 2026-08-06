{{--
  ورودی تاریخ مشترک کل داشبورد:
  مدیر تاریخ را با تقویم شمسی و زمان رسمی ایران می‌بیند؛ input اصلی میلادی مخفی
  می‌ماند تا قرارداد فعلی کنترلرها، فیلترها و دیتابیس بدون تغییر کار کند.
--}}
<style>
  .admin-jalali-picker{position:fixed;z-index:9999;width:292px;padding:12px;border-radius:14px;background:var(--card-bg);border:1px solid var(--border);box-shadow:var(--shadow-card);direction:rtl}
  .admin-jalali-head,.admin-jalali-actions{display:flex;align-items:center;justify-content:space-between;gap:7px}
  .admin-jalali-head{margin-bottom:10px}.admin-jalali-actions{margin-top:10px;padding-top:9px;border-top:1px solid var(--divider)}
  .admin-jalali-nav{width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:var(--text-main);cursor:pointer}
  .admin-jalali-select,.admin-jalali-time{height:30px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:var(--text-main);font-family:inherit;font-size:11px;padding:0 7px}
  .admin-jalali-week,.admin-jalali-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:3px}
  .admin-jalali-week span{text-align:center;color:var(--text-soft);font-size:10px;padding:4px 0}
  .admin-jalali-day{height:32px;border:0;border-radius:9px;background:transparent;color:var(--text-main);font-family:inherit;font-size:11px;cursor:pointer}
  .admin-jalali-day:hover{background:var(--nav-hover)}.admin-jalali-day.is-today{border:1px solid var(--success);color:var(--success)}
  .admin-jalali-day.is-selected{background:var(--primary);color:var(--accent);font-weight:800}
  .admin-jalali-action{border:0;background:transparent;color:var(--text-soft);font-family:inherit;font-size:11px;cursor:pointer;padding:5px 7px}
  .admin-jalali-visible{direction:ltr;text-align:left;cursor:pointer}
</style>
<script>
(function () {
  'use strict';
  const faDigits = '۰۱۲۳۴۵۶۷۸۹';
  const months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const toFa = value => String(value).replace(/\d/g, digit => faDigits[digit]);
  const pad = value => String(value).padStart(2, '0');

  function toJalali(gy, gm, gd) {
    const gdm = [0,31,59,90,120,151,181,212,243,273,304,334];
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = 355666 + 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
    let jy = -1595 + 33 * Math.floor(days / 12053); days %= 12053;
    jy += 4 * Math.floor(days / 1461); days %= 1461;
    if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
    const jd = days < 186 ? 1 + days % 31 : 1 + (days - 186) % 30;
    return [jy, jm, jd];
  }

  function toGregorian(jy, jm, jd) {
    jy += 1595;
    let days = -355668 + 365 * jy + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd;
    days += jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186;
    let gy = 400 * Math.floor(days / 146097); days %= 146097;
    if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
    gy += 4 * Math.floor(days / 1461); days %= 1461;
    if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    let gd = days + 1;
    const leap = gy % 4 === 0 && gy % 100 !== 0 || gy % 400 === 0;
    const monthDays = [31, leap ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
    let gm = 0; while (gm < 12 && gd > monthDays[gm]) gd -= monthDays[gm++];
    return [gy, gm + 1, gd];
  }

  function iranNow() {
    if (window.AdminIranClock?.now) return window.AdminIranClock.now();
    return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Tehran' }));
  }
  function todayJalali() {
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone:'Asia/Tehran', year:'numeric', month:'2-digit', day:'2-digit' }).formatToParts(iranNow());
    const get = type => Number(parts.find(part => part.type === type)?.value || 0);
    return toJalali(get('year'), get('month'), get('day'));
  }
  function daysInMonth(year, month) {
    if (month <= 6) return 31;
    if (month <= 11) return 30;
    const start = toGregorian(year, 1, 1), next = toGregorian(year + 1, 1, 1);
    return (Date.UTC(...[next[0], next[1] - 1, next[2]]) - Date.UTC(...[start[0], start[1] - 1, start[2]])) / 86400000 === 366 ? 30 : 29;
  }

  function parsedOriginal(input) {
    const match = String(input.value || '').match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);
    if (!match) return null;
    const jalali = toJalali(Number(match[1]), Number(match[2]), Number(match[3]));
    return { year:jalali[0], month:jalali[1], day:jalali[2], hour:match[4] || '00', minute:match[5] || '00' };
  }
  function visibleValue(input) {
    const selected = parsedOriginal(input);
    if (!selected) return '';
    const date = `${selected.year}/${pad(selected.month)}/${pad(selected.day)}`;
    const time = input.dataset.jalaliType === 'datetime-local' ? `  ${selected.hour}:${selected.minute}` : '';
    return toFa(date + time);
  }
  function syncVisible(input) {
    if (input._jalaliVisible) input._jalaliVisible.value = visibleValue(input);
  }

  function enhance(input) {
    if (input.dataset.jalaliReady === '1' || input.dataset.jalaliIgnore === '1') return;
    input.dataset.jalaliReady = '1';
    input.dataset.jalaliType = input.type;
    const visible = document.createElement('input');
    visible.type = 'text'; visible.readOnly = true;
    visible.className = input.className + ' admin-jalali-visible';
    visible.placeholder = input.dataset.jalaliType === 'datetime-local' ? '۱۴۰۵/۰۵/۰۹  ۱۴:۳۰' : '۱۴۰۵/۰۵/۰۹';
    visible.setAttribute('aria-label', input.getAttribute('aria-label') || 'انتخاب تاریخ شمسی');
    if (input.id) {
      visible.id = input.id + '-jalali';
      document.querySelectorAll(`label[for="${input.id}"]`).forEach(label => label.setAttribute('for', visible.id));
    }
    input.type = 'hidden';
    input.before(visible);
    input._jalaliVisible = visible;
    syncVisible(input);
    visible.addEventListener('click', () => openPicker(input, visible));
    input.addEventListener('change', () => syncVisible(input));
  }

  function openPicker(input, visible) {
    document.querySelectorAll('.admin-jalali-picker').forEach(picker => picker.remove());
    const today = todayJalali();
    const selected = parsedOriginal(input) || { year:today[0], month:today[1], day:today[2], hour:pad(iranNow().getHours()), minute:pad(iranNow().getMinutes()) };
    let year = selected.year, month = selected.month;
    const picker = document.createElement('div'); picker.className = 'admin-jalali-picker';

    function render() {
      const firstGregorian = toGregorian(year, month, 1);
      const offset = (new Date(firstGregorian[0], firstGregorian[1] - 1, firstGregorian[2]).getDay() + 1) % 7;
      const blanks = Array.from({ length:offset }, () => '<span></span>').join('');
      const days = Array.from({ length:daysInMonth(year, month) }, (_, index) => {
        const day = index + 1;
        const isToday = year === today[0] && month === today[1] && day === today[2];
        const isSelected = year === selected.year && month === selected.month && day === selected.day;
        return `<button type="button" class="admin-jalali-day${isToday ? ' is-today' : ''}${isSelected ? ' is-selected' : ''}" data-day="${day}">${toFa(day)}</button>`;
      }).join('');
      const yearOptions = Array.from({ length:21 }, (_, index) => today[0] - 10 + index).map(item => `<option value="${item}"${item === year ? ' selected' : ''}>${toFa(item)}</option>`).join('');
      const monthOptions = months.map((name, index) => `<option value="${index + 1}"${index + 1 === month ? ' selected' : ''}>${name}</option>`).join('');
      picker.innerHTML = `<div class="admin-jalali-head"><button type="button" class="admin-jalali-nav" data-nav="prev">‹</button><div><select class="admin-jalali-select" data-month>${monthOptions}</select> <select class="admin-jalali-select" data-year>${yearOptions}</select></div><button type="button" class="admin-jalali-nav" data-nav="next">›</button></div><div class="admin-jalali-week">${['ش','ی','د','س','چ','پ','ج'].map(day => `<span>${day}</span>`).join('')}</div><div class="admin-jalali-days">${blanks}${days}</div><div class="admin-jalali-actions"><button type="button" class="admin-jalali-action" data-today>امروز</button>${input.dataset.jalaliType === 'datetime-local' ? `<input type="time" class="admin-jalali-time" value="${selected.hour}:${selected.minute}" data-time>` : ''}<button type="button" class="admin-jalali-action" data-clear>پاک کردن</button></div>`;
    }

    function pick(day) {
      const gregorian = toGregorian(year, month, day);
      const time = picker.querySelector('[data-time]')?.value || `${selected.hour}:${selected.minute}`;
      input.value = `${gregorian[0]}-${pad(gregorian[1])}-${pad(gregorian[2])}` + (input.dataset.jalaliType === 'datetime-local' ? `T${time}` : '');
      input.dispatchEvent(new Event('input', { bubbles:true }));
      input.dispatchEvent(new Event('change', { bubbles:true }));
      picker.remove();
    }
    picker.addEventListener('click', event => {
      const dayButton = event.target.closest('[data-day]'); if (dayButton) return pick(Number(dayButton.dataset.day));
      const nav = event.target.closest('[data-nav]')?.dataset.nav;
      if (nav === 'prev') { if (--month < 1) { month = 12; year--; } render(); }
      if (nav === 'next') { if (++month > 12) { month = 1; year++; } render(); }
      if (event.target.closest('[data-today]')) { year = today[0]; month = today[1]; pick(today[2]); }
      if (event.target.closest('[data-clear]')) { input.value = ''; input.dispatchEvent(new Event('change', { bubbles:true })); picker.remove(); }
    });
    picker.addEventListener('change', event => {
      if (event.target.matches('[data-month]')) { month = Number(event.target.value); render(); }
      if (event.target.matches('[data-year]')) { year = Number(event.target.value); render(); }
    });
    render(); document.body.appendChild(picker);
    const rect = visible.getBoundingClientRect();
    picker.style.top = Math.min(window.innerHeight - picker.offsetHeight - 8, rect.bottom + 6) + 'px';
    picker.style.left = Math.max(8, Math.min(window.innerWidth - picker.offsetWidth - 8, rect.right - picker.offsetWidth)) + 'px';
    setTimeout(() => document.addEventListener('click', function outside(event) {
      if (picker.contains(event.target) || event.target === visible) return;
      picker.remove(); document.removeEventListener('click', outside);
    }), 0);
  }

  function enhanceAll(root) {
    (root || document).querySelectorAll('input[type="date"],input[type="datetime-local"]').forEach(enhance);
  }
  document.addEventListener('DOMContentLoaded', () => {
    enhanceAll(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
      if (node.nodeType === 1) { if (node.matches?.('input[type="date"],input[type="datetime-local"]')) enhance(node); enhanceAll(node); }
    }))).observe(document.body, { childList:true, subtree:true });
  });
})();
</script>
