<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
  const breadcrumb = document.getElementById('breadcrumb');
  if (breadcrumb) breadcrumb.textContent = @json($title);

  const readVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  document.querySelectorAll('[data-growth-chart]').forEach((canvas) => {
    if (typeof Chart === 'undefined') return;
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const clicks = JSON.parse(canvas.dataset.clicks || '[]');
    const opens = JSON.parse(canvas.dataset.opens || '[]');
    new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [
          { label: 'کلیک', data: clicks, borderColor: readVar('--primary'), backgroundColor: readVar('--primary-l'), fill: true, tension: .42, pointRadius: 2, pointHoverRadius: 5, borderWidth: 2 },
          { label: 'بازشدن مقصد', data: opens, borderColor: readVar('--success'), backgroundColor: readVar('--success-l'), fill: false, tension: .42, pointRadius: 2, pointHoverRadius: 5, borderWidth: 2 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
          legend: { position: 'bottom', rtl: true, labels: { color: readVar('--text-soft'), usePointStyle: true, boxWidth: 7, padding: 18, font: { family: 'YekanBakh', size: 10 } } },
          tooltip: { rtl: true, titleFont: { family: 'YekanBakh' }, bodyFont: { family: 'YekanBakh' } }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: readVar('--text-soft'), font: { family: 'YekanBakh', size: 9 } }, border: { color: readVar('--border') } },
          y: { beginAtZero: true, grid: { color: readVar('--divider') }, ticks: { color: readVar('--text-soft'), precision: 0, font: { family: 'YekanBakh', size: 9 } }, border: { display: false } }
        }
      }
    });
  });

  document.querySelectorAll('[data-user-growth-chart]').forEach((canvas) => {
    if (typeof Chart === 'undefined') return;
    new Chart(canvas, {
      type: 'line',
      data: {
        labels: JSON.parse(canvas.dataset.labels || '[]'),
        datasets: [
          { label: 'ثبت‌نام', data: JSON.parse(canvas.dataset.signups || '[]'), borderColor: readVar('--primary'), backgroundColor: readVar('--primary-l'), fill: true, tension: .42, pointRadius: 2, borderWidth: 2 },
          { label: 'ورود موفق', data: JSON.parse(canvas.dataset.logins || '[]'), borderColor: readVar('--info'), backgroundColor: readVar('--info-l'), fill: false, tension: .42, pointRadius: 2, borderWidth: 2 },
          { label: 'خرید موفق', data: JSON.parse(canvas.dataset.purchases || '[]'), borderColor: readVar('--success'), backgroundColor: readVar('--success-l'), fill: false, tension: .42, pointRadius: 2, borderWidth: 2 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
          legend: { position: 'bottom', rtl: true, labels: { color: readVar('--text-soft'), usePointStyle: true, boxWidth: 7, padding: 18, font: { family: 'YekanBakh', size: 10 } } },
          tooltip: { rtl: true, titleFont: { family: 'YekanBakh' }, bodyFont: { family: 'YekanBakh' } }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: readVar('--text-soft'), font: { family: 'YekanBakh', size: 9 } }, border: { color: readVar('--border') } },
          y: { beginAtZero: true, grid: { color: readVar('--divider') }, ticks: { color: readVar('--text-soft'), precision: 0, font: { family: 'YekanBakh', size: 9 } }, border: { display: false } }
        }
      }
    });
  });

  document.querySelectorAll('[data-timeline-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filter = button.dataset.timelineFilter;
      document.querySelectorAll('[data-timeline-filter]').forEach((item) => item.classList.toggle('active', item === button));
      document.querySelectorAll('[data-timeline-type]').forEach((item) => {
        item.hidden = filter !== 'all' && item.dataset.timelineType !== filter;
      });
    });
  });

  document.querySelectorAll('[data-copy-value]').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(button.dataset.copyValue || '');
        const original = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-check"></i> کپی شد';
        window.setTimeout(() => { button.innerHTML = original; }, 1400);
      } catch (error) {}
    });
  });

  document.querySelectorAll('[data-link-select]').forEach((select) => {
    select.addEventListener('change', () => { if (select.value) window.location.href = select.value; });
  });
})();
</script>
