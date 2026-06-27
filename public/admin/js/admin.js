let revenueChart = null;

function initChart() {
  if (typeof Chart === 'undefined') return;
  const ctx = document.getElementById('revenueChart');
  if (!ctx) return;
  const isDark = !document.body.classList.contains('light');
  const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.05)';
  const textColor = isDark ? '#3d4260' : '#9ba3bf';

  if (revenueChart) revenueChart.destroy();

  revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['شنبه','یک‌شنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه'],
      datasets: [{
        label: 'درآمد (میلیون تومان)',
        data: [32, 28, 41, 35, 52, 44, 48],
        borderColor: '#0BBF53',
        backgroundColor: 'rgba(11,191,83,0.06)',
        borderWidth: 2,
        pointBackgroundColor: '#0BBF53',
        pointBorderColor: isDark ? '#111116' : '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.4,
        fill: true,
      },{
        label: 'هزینه AI ($)',
        data: [14, 12, 18, 15, 22, 19, 18],
        borderColor: '#a07af5',
        backgroundColor: 'rgba(160,122,245,0.04)',
        borderWidth: 2,
        pointBackgroundColor: '#a07af5',
        pointBorderColor: isDark ? '#111116' : '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.4,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          position: 'top',
          align: 'end',
          labels: {
            color: textColor,
            font: { family: 'Vazirmatn', size: 11 },
            boxWidth: 8,
            boxHeight: 8,
            borderRadius: 4,
            padding: 12,
            usePointStyle: true,
          }
        },
        tooltip: {
          backgroundColor: isDark ? '#16161c' : '#fff',
          borderColor: isDark ? '#222230' : '#e2e5ef',
          borderWidth: 1,
          titleColor: isDark ? '#e8eaf2' : '#1a1c2a',
          bodyColor: isDark ? '#7c839e' : '#555c7a',
          titleFont: { family: 'Vazirmatn', size: 12, weight: '700' },
          bodyFont: { family: 'Vazirmatn', size: 11 },
          padding: 12,
          rtl: true,
        }
      },
      scales: {
        x: {
          grid: { color: gridColor },
          ticks: { color: textColor, font: { family: 'Vazirmatn', size: 11 } },
          border: { display: false }
        },
        y: {
          grid: { color: gridColor },
          ticks: { color: textColor, font: { family: 'Vazirmatn', size: 11 } },
          border: { display: false }
        }
      }
    }
  });
}

function showPage(pageId, sectionName) {
  document.querySelectorAll('[id$="-page"]').forEach(p => p.style.display = 'none');
  const page = document.getElementById(pageId);
  if (page) {
    page.style.display = 'block';
    if (pageId === 'placeholder-page') {
      document.getElementById('placeholder-section-name').textContent = sectionName;
    }
    if (pageId === 'dashboard-page') {
      setTimeout(initChart, 50);
    }
  }
}

function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('show');
}

function closeSidebar() {
  document.querySelector('.sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('show');
}

function toggleSub(el, name) {
  const sub = el.nextElementSibling;
  const isOpen = sub.classList.contains('open');
  document.querySelectorAll('.sub-nav.open').forEach(s => {
    s.classList.remove('open');
    s.classList.add('hidden');
  });
  document.querySelectorAll('.nav-item.open').forEach(i => i.classList.remove('open'));
  if (!isOpen) {
    sub.classList.remove('hidden');
    sub.classList.add('open');
    el.classList.add('open');
  }
  document.querySelectorAll('.nav-item.active').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('breadcrumb').textContent = name;
}

function setActive(el, name, sub, pageId) {
  document.querySelectorAll('.nav-item.active').forEach(i => i.classList.remove('active'));
  document.querySelectorAll('.sub-item.active').forEach(i => i.classList.remove('active'));
  document.querySelectorAll('.sub-nav.open').forEach(s => s.classList.remove('open'));
  document.querySelectorAll('.nav-item.open').forEach(i => i.classList.remove('open'));
  el.classList.add('active');
  document.getElementById('breadcrumb').textContent = name;
  showPage(pageId || 'placeholder-page', name);
  closeSidebar();
}

function setActiveSub(el, parent, name, pageId) {
  document.querySelectorAll('.sub-item.active').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
  document.getElementById('breadcrumb').textContent = parent + ' — ' + name;
  showPage(pageId || 'placeholder-page', name);
  closeSidebar();
}

function toggleMode() {
  document.body.classList.toggle('light');
  const btn = document.getElementById('theme-btn');
  const icon = btn.querySelector('i');
  if (document.body.classList.contains('light')) {
    icon.className = 'fa-solid fa-sun';
  } else {
    icon.className = 'fa-solid fa-moon';
  }
  setTimeout(initChart, 50);
}

window.addEventListener('load', () => setTimeout(initChart, 100));
