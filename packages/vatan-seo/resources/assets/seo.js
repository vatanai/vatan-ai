/* Vatan SEO Engine — اسکریپت پنل (بدون وابستگی؛ Chart.js فقط در صفحات نموداردار) */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const fa = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]).replace(/\./g, '٫');

  /* ── راهنمای کلیکی (پاپ‌آپ fixed؛ هیچ اسکرول افقی نمی‌سازد) ─────── */
  const placePop = (wrap) => {
    const pop = $('.seo-pop', wrap);
    const btn = $('.seo-help-btn', wrap);
    if (!pop || !btn) return;
    const r = btn.getBoundingClientRect();
    const w = Math.min(300, window.innerWidth - 24);
    pop.style.width = w + 'px';
    let left = r.right - w + 10;
    left = Math.max(12, Math.min(left, window.innerWidth - w - 12));
    let top = r.bottom + 8;
    const h = pop.offsetHeight || 160;
    if (top + h > window.innerHeight - 12 && r.top - h - 8 > 12) top = r.top - h - 8;
    pop.style.left = left + 'px';
    pop.style.top = top + 'px';
  };
  const closeAll = () => $$('.seo-help.is-open').forEach((w) => w.classList.remove('is-open'));
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.seo-help-btn');
    if (btn) {
      e.preventDefault();
      e.stopPropagation();
      const wrap = btn.closest('.seo-help');
      const open = wrap.classList.contains('is-open');
      closeAll();
      if (!open) { wrap.classList.add('is-open'); placePop(wrap); }
      return;
    }
    if (e.target.closest('.seo-pop-close')) { closeAll(); return; }
    if (!e.target.closest('.seo-pop')) closeAll();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
  window.addEventListener('scroll', closeAll, true);
  window.addEventListener('resize', closeAll);
  // راهنما داخل <summary> نباید جزئیات را باز/بسته کند
  $$('summary .seo-help-btn').forEach((b) => b.addEventListener('click', (e) => e.preventDefault()));

  /* ── اعلان‌ها ─────────────────────────────────────────────── */
  $$('[data-seo-dismiss]').forEach((b) => b.addEventListener('click', () => b.closest('.seo-flash').remove()));

  /* ── تأیید دومرحله‌ای (بدون پنجره‌ی مرورگر) ─────────────────── */
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-confirm]');
    if (!el) return;
    if (el.dataset.armed === '1') return;
    e.preventDefault();
    el.dataset.armed = '1';
    const original = el.innerHTML;
    el.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + el.dataset.confirm;
    setTimeout(() => { el.dataset.armed = '0'; el.innerHTML = original; }, 3500);
  }, true);

  /* ── حالت «در حال انجام» برای کارهای طولانی هوش مصنوعی ──────── */
  document.addEventListener('submit', (e) => {
    const form = e.target;
    const btn = form.querySelector('[data-loading]') || (e.submitter && e.submitter.matches('[data-loading]') ? e.submitter : null);
    if (!btn) return;
    btn.classList.add('is-loading');
    btn.innerHTML = '<i class="fa-solid fa-circle-notch"></i> ' + btn.dataset.loading;
  });

  /* ── انتخاب گروهی در جدول کلمات ─────────────────────────────── */
  const all = $('[data-select-all]');
  const bulk = $('.seo-bulk');
  const refreshBulk = () => {
    const checked = $$('[data-select-row]:checked');
    if (bulk) {
      bulk.classList.toggle('is-visible', checked.length > 0);
      const n = $('[data-selected-count]', bulk);
      if (n) n.textContent = fa(checked.length);
      const holder = $('[data-selected-ids]', bulk);
      if (holder) holder.innerHTML = checked.map((c) => '<input type="hidden" name="ids[]" value="' + c.value + '">').join('');
    }
  };
  if (all) all.addEventListener('change', () => { $$('[data-select-row]').forEach((c) => (c.checked = all.checked)); refreshBulk(); });
  $$('[data-select-row]').forEach((c) => c.addEventListener('change', refreshBulk));

  /* ── سوییچ‌های فرم (ارسال خودکار) ───────────────────────────── */
  $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form && el.form.submit()));

  /* ── باز/بسته کردن بخش‌ها ─────────────────────────────────── */
  $$('[data-toggle]').forEach((b) => b.addEventListener('click', () => {
    const t = document.getElementById(b.dataset.toggle);
    if (t) t.hidden = !t.hidden;
  }));

  /* ── کپی ─────────────────────────────────────────────────── */
  $$('[data-copy]').forEach((b) => b.addEventListener('click', () => {
    const t = document.getElementById(b.dataset.copy);
    if (!t) return;
    navigator.clipboard.writeText(t.innerText).then(() => { const o = b.innerHTML; b.innerHTML = '<i class="fa-solid fa-check"></i> کپی شد'; setTimeout(() => (b.innerHTML = o), 1500); });
  }));

  /* ── نمودارها ─────────────────────────────────────────────── */
  const css = (v) => getComputedStyle(document.body).getPropertyValue(v).trim();
  const isLight = () => document.body.classList.contains('light');
  const palette = () => [isLight() ? css('--primary') : css('--accent'), css('--info'), css('--warning'), css('--danger'), css('--success'), css('--text-soft')];
  const charts = [];

  function baseOptions(extra = {}) {
    const grid = css('--border');
    const text = css('--text-soft');
    return Object.assign({
      responsive: true, maintainAspectRatio: false, animation: { duration: 500 },
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', rtl: true, labels: { color: text, boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { family: 'inherit', size: 11 } } },
        tooltip: { rtl: true, textDirection: 'rtl', backgroundColor: css('--card-bg'), titleColor: css('--text-h'), bodyColor: css('--text-main'), borderColor: grid, borderWidth: 1, padding: 10, callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + (c.parsed.y === null ? '—' : fa(Math.round(c.parsed.y * 10) / 10)) } },
      },
      scales: {
        x: { reverse: true, grid: { display: false }, ticks: { color: text, maxRotation: 0, autoSkipPadding: 18, font: { size: 10.5 } } },
        y: { position: 'right', grid: { color: grid }, border: { display: false }, ticks: { color: text, font: { size: 10.5 }, callback: (v) => fa(v) } },
      },
    }, extra);
  }

  function build() {
    if (!window.Chart) return;
    charts.splice(0).forEach((c) => c.destroy());
    $$('[data-seo-chart]').forEach((canvas) => {
      const data = JSON.parse(document.getElementById(canvas.dataset.seoChart).textContent);
      const p = palette();
      let cfg;
      if (canvas.dataset.type === 'traffic') {
        cfg = { type: 'bar', data: { labels: data.labels, datasets: [
          { type: 'bar', label: 'کلیک', data: data.clicks, backgroundColor: p[0], borderRadius: 4, yAxisID: 'y', order: 2, maxBarThickness: 18 },
          { type: 'line', label: 'ایمپرشن', data: data.impressions, borderColor: p[1], backgroundColor: p[1], tension: .35, pointRadius: 0, borderWidth: 2, yAxisID: 'y1', order: 1 },
        ] }, options: baseOptions() };
        cfg.options.scales.y1 = { position: 'left', grid: { display: false }, border: { display: false }, ticks: { color: css('--text-soft'), font: { size: 10.5 }, callback: (v) => fa(v >= 1000 ? Math.round(v / 100) / 10 + 'k' : v) } };
      } else if (canvas.dataset.type === 'rank') {
        const series = data.series || [{ label: 'رتبه', points: data.labels.map((l, i) => ({ label: l, y: data.position[i] })) }];
        const hasX = series.every((s) => s.points.every((pt) => pt.x));
        const labels = Array.from(new Set(series.flatMap((s) => s.points.map((pt) => pt.x || pt.label))));
        if (hasX) labels.sort();
        const labelText = {};
        series.forEach((s) => s.points.forEach((pt) => (labelText[pt.x || pt.label] = pt.label)));
        cfg = { type: 'line', data: { labels: labels.map((l) => labelText[l]), datasets: series.map((s, i) => {
          const map = {}; s.points.forEach((pt) => (map[pt.x || pt.label] = pt.y));
          return { label: s.label, data: labels.map((l) => (map[l] === undefined ? null : map[l])), borderColor: p[i % p.length], backgroundColor: p[i % p.length], borderDash: i >= p.length ? [5, 4] : [], tension: .3, pointRadius: 2, pointHoverRadius: 5, borderWidth: 2, spanGaps: true };
        }) }, options: baseOptions() };
        cfg.options.scales.y.reverse = true;
        cfg.options.scales.y.suggestedMin = 1;
        cfg.options.scales.y.ticks.precision = 0;
      } else if (canvas.dataset.type === 'cost') {
        cfg = { type: 'bar', data: { labels: data.labels, datasets: [{ label: 'هزینه (دلار)', data: data.values, backgroundColor: p[0], borderRadius: 4, maxBarThickness: 14 }] }, options: baseOptions() };
        cfg.options.plugins.legend.display = false;
        cfg.options.plugins.tooltip.callbacks.label = (c) => ' $' + c.parsed.y.toFixed(4);
        cfg.options.scales.y.ticks.callback = (v) => '$' + v;
      }
      if (cfg) charts.push(new Chart(canvas, cfg));
    });
  }
  window.addEventListener('load', build);
  // بازسازی نمودارها با تغییر تم روز/شب (کلاس light روی body)
  new MutationObserver(() => setTimeout(build, 50)).observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
