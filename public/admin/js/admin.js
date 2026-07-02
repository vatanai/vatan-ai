// ── SPA URL Routing ──────────────────────────────────────────────────────────
const PAGE_URLS = {
  'dashboard-page':           '/admin/dashboard',
  'crm-page':                 '/admin/dashboard/crm',
  'attendance-page':          '/admin/dashboard/attendance',
  'products-dashboard-page':  '/admin/dashboard/products',
  'products-list-page':       '/admin/dashboard/productslist',
  'products-create-page':     '/admin/dashboard/createproduct',
  'products-categories-page': '/admin/dashboard/categories',
  'products-pricing-page':    '/admin/dashboard/pricing',
  'ai-hub-page':              '/admin/dashboard/ai',
  'ai-models-page':           '/admin/dashboard/models',
  'ai-prompts-page':          '/admin/dashboard/prompts',
  'ai-logs-page':             '/admin/dashboard/logs',
};

const PAGE_META = {
  'dashboard-page':           { breadcrumb: 'مرکز فرماندهی' },
  'crm-page':                 { breadcrumb: 'CRM' },
  'attendance-page':          { breadcrumb: 'حضور و غیاب' },
  'products-dashboard-page':  { breadcrumb: 'محصولات — داشبورد محصولات' },
  'products-list-page':       { breadcrumb: 'محصولات — لیست محصولات' },
  'products-create-page':     { breadcrumb: 'محصولات — ثبت محصول جدید' },
  'products-categories-page': { breadcrumb: 'محصولات — دسته‌بندی‌ها' },
  'products-pricing-page':    { breadcrumb: 'محصولات — قیمت‌گذاری' },
  'ai-hub-page':              { breadcrumb: 'هوش مصنوعی — AI Hub' },
  'ai-models-page':           { breadcrumb: 'هوش مصنوعی — مدل‌ها' },
  'ai-prompts-page':          { breadcrumb: 'هوش مصنوعی — پرامپت‌ها' },
  'ai-logs-page':             { breadcrumb: 'هوش مصنوعی — لاگ‌ها' },
};

const URL_TO_PAGE = {};
Object.entries(PAGE_URLS).forEach(([page, url]) => { URL_TO_PAGE[url] = page; });

// ─────────────────────────────────────────────────────────────────────────────
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
    if (pageId === 'products-dashboard-page') {
      setTimeout(initProductsDashChart, 50);
    }
    if (pageId === 'ai-hub-page') {
      setTimeout(function(){ if (typeof initAiHubChart === 'function') initAiHubChart(); }, 50);
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
  const url = PAGE_URLS[pageId];
  if (url) history.pushState({ pageId, breadcrumb: name }, '', url);
}

function setActiveSub(el, parent, name, pageId) {
  document.querySelectorAll('.sub-item.active').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
  const bc = parent + ' — ' + name;
  document.getElementById('breadcrumb').textContent = bc;
  showPage(pageId || 'placeholder-page', name);
  closeSidebar();
  const url = PAGE_URLS[pageId];
  if (url) history.pushState({ pageId, breadcrumb: bc }, '', url);
}

// ── popstate: browser back/forward ───────────────────────────────────────────
window.addEventListener('popstate', function(e) {
  const pageId = e.state && e.state.pageId ? e.state.pageId : 'dashboard-page';
  const bc     = e.state && e.state.breadcrumb ? e.state.breadcrumb : (PAGE_META[pageId] || {}).breadcrumb || '';
  showPage(pageId, null);
  const el = document.getElementById('breadcrumb');
  if (el) el.textContent = bc;
});

// ── initFromURL: detect section from URL on page load ────────────────────────
function initFromURL() {
  const path   = window.location.pathname;
  const pageId = URL_TO_PAGE[path] || 'dashboard-page';
  const meta   = PAGE_META[pageId] || {};
  // Replace history state so popstate has data on first back
  history.replaceState({ pageId, breadcrumb: meta.breadcrumb || '' }, '', path);
  if (pageId !== 'dashboard-page') {
    showPage(pageId, null);
    const el = document.getElementById('breadcrumb');
    if (el && meta.breadcrumb) el.textContent = meta.breadcrumb;
  }
}

document.addEventListener('DOMContentLoaded', initFromURL);

const DARK_VARS = {
  '--bg':'#0c0c10','--s1':'#111116','--s2':'#16161c',
  '--b1':'#222230','--b2':'#2e2e3e',
  '--text':'#ffffff','--text2':'#a8c4a8','--text3':'#4d7a56',
  '--green':'#0BBF53','--accent':'#a07af5','--red':'#f05c5c','--orange':'#f5923a'
};
const LIGHT_VARS = {
  '--bg':'#f0f0f8','--s1':'#ffffff','--s2':'#f3f3f9',
  '--b1':'#e2e2ee','--b2':'#c8c8da',
  '--text':'#12121e','--text2':'#3d5c42','--text3':'#7a9e7f',
  '--green':'#0BBF53','--accent':'#7c5de0','--red':'#d94545','--orange':'#d97a2a'
};

function applyThemeVars(vars) {
  const root = document.documentElement;
  Object.entries(vars).forEach(([k,v]) => root.style.setProperty(k, v));
}

function toggleMode() {
  const isLight = document.body.classList.toggle('light');
  const btn = document.getElementById('theme-btn');
  const icon = btn ? btn.querySelector('i') : null;
  if (isLight) {
    applyThemeVars(LIGHT_VARS);
    if (icon) icon.className = 'fa-solid fa-sun';
    localStorage.setItem('admin-theme','light');
  } else {
    applyThemeVars(DARK_VARS);
    if (icon) icon.className = 'fa-solid fa-moon';
    localStorage.setItem('admin-theme','dark');
  }
  setTimeout(initChart, 50);
  setTimeout(function(){ if (typeof initAiHubChart === 'function') initAiHubChart(); }, 50);
}

// restore theme on load
(function() {
  if (localStorage.getItem('admin-theme') === 'light') {
    document.body.classList.add('light');
    applyThemeVars(LIGHT_VARS);
    const btn = document.getElementById('theme-btn');
    if (btn) { const i = btn.querySelector('i'); if(i) i.className='fa-solid fa-sun'; }
  }
})();

window.addEventListener('load', () => setTimeout(initChart, 100));

// ── Products Dashboard Chart ─────────────────────────────────────────────────
const PROD_RANGE_DATA = {
  7: {
    labels: ['۱','۲','۳','۴','۵','۶','۷'],
    orders: [62,71,58,85,90,78,110],
    revenue:[31,35,29,42,45,39,55],
  },
  30: {
    labels: ['هفته ۱','هفته ۲','هفته ۳','هفته ۴'],
    orders: [420,510,480,624],
    revenue:[210,255,240,312],
  },
  90: {
    labels: ['ماه ۱','ماه ۲','ماه ۳'],
    orders: [1400,1850,2481],
    revenue:[700,925,1241],
  },
};
let _prodChart = null;
let _prodRange = 30;

function initProductsDashChart() {
  const canvas = document.getElementById('prodTrendChart');
  if (!canvas || typeof Chart === 'undefined') return;
  if (_prodChart) { _prodChart.destroy(); _prodChart = null; }

  const isDark = !document.body.classList.contains('light');
  const textColor = isDark ? 'rgba(168,196,168,.65)' : 'rgba(61,92,66,.65)';
  const gridColor = isDark ? 'rgba(255,255,255,.05)' : 'rgba(0,0,0,.07)';
  const d = PROD_RANGE_DATA[_prodRange];

  _prodChart = new Chart(canvas, {
    type: 'line',
    data: {
      labels: d.labels,
      datasets: [
        {
          label: 'سفارشات',
          data: d.orders,
          borderColor: '#a07af5',
          backgroundColor: 'rgba(160,122,245,.08)',
          borderWidth: 2,
          pointRadius: 3,
          pointBackgroundColor: '#a07af5',
          tension: .38,
          fill: true,
          yAxisID: 'y',
        },
        {
          label: 'درآمد (M)',
          data: d.revenue,
          borderColor: '#0BBF53',
          backgroundColor: 'rgba(11,191,83,.06)',
          borderWidth: 2,
          pointRadius: 3,
          pointBackgroundColor: '#0BBF53',
          tension: .38,
          fill: true,
          yAxisID: 'y1',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: isDark ? '#16161c' : '#fff',
          borderColor: isDark ? '#2e2e3e' : '#e2e2ee',
          borderWidth: 1,
          titleColor: isDark ? '#fff' : '#12121e',
          bodyColor: isDark ? '#a8c4a8' : '#3d5c42',
          titleFont: { family: 'Vazirmatn', size: 11 },
          bodyFont: { family: 'Vazirmatn', size: 11 },
          rtl: true,
          padding: 10,
        },
      },
      scales: {
        x: {
          grid: { color: gridColor },
          ticks: { color: textColor, font: { family: 'Vazirmatn', size: 10 } },
        },
        y: {
          position: 'right',
          grid: { color: gridColor },
          ticks: { color: '#a07af5', font: { family: 'Vazirmatn', size: 10 } },
        },
        y1: {
          position: 'left',
          grid: { display: false },
          ticks: { color: '#0BBF53', font: { family: 'Vazirmatn', size: 10 } },
        },
      },
    },
  });
}

function setProdRange(range, btn) {
  _prodRange = range;
  document.querySelectorAll('.pd-range').forEach(b => {
    b.style.background = 'none';
    b.style.color = 'var(--text2)';
  });
  btn.style.background = 'var(--b2)';
  btn.style.color = 'var(--text)';
  if (_prodChart) {
    const d = PROD_RANGE_DATA[range];
    _prodChart.data.labels = d.labels;
    _prodChart.data.datasets[0].data = d.orders;
    _prodChart.data.datasets[1].data = d.revenue;
    _prodChart.update();
  }
}

// ── Products List Page ───────────────────────────────────────────────────────
const PL_PRODUCTS = [
  {id:1,emoji:'💼',name:'عکس لینکدین',cat:'افراد',model:'flux-1.1-pro',status:'فعال',statusColor:'var(--green)',orders:624,rev:'۳۱۲M',rate:97.8},
  {id:2,emoji:'✨',name:'آواتار انیمه',cat:'سرگرمی',model:'flux-kontext',status:'فعال',statusColor:'var(--green)',orders:475,rev:'۲۳۸M',rate:95.0},
  {id:3,emoji:'📸',name:'عکس پرتره',cat:'افراد',model:'flux-1.1-pro',status:'فعال',statusColor:'var(--green)',orders:362,rev:'۱۸۱M',rate:98.0},
  {id:4,emoji:'🎪',name:'بنر رویداد',cat:'رویدادها',model:'ideogram/v3',status:'فعال',statusColor:'var(--green)',orders:261,rev:'۱۳۱M',rate:93.0},
  {id:5,emoji:'🐾',name:'عکس حیوانات',cat:'حیوانات',model:'flux-1.1-pro',status:'فعال',statusColor:'var(--green)',orders:183,rev:'۹۲M',rate:96.0},
  {id:6,emoji:'🏢',name:'لوگو کسب‌وکار',cat:'کسب‌وکار',model:'sd-3.5-large',status:'غیرفعال',statusColor:'var(--text3)',orders:0,rev:'—',rate:0},
  {id:7,emoji:'🎨',name:'تصویر هنری',cat:'سرگرمی',model:'flux-kontext',status:'پیش‌نویس',statusColor:'var(--orange)',orders:0,rev:'—',rate:0},
  {id:8,emoji:'🌿',name:'پس‌زمینه طبیعی',cat:'طبیعت',model:'flux-1.1-pro',status:'فعال',statusColor:'var(--green)',orders:94,rev:'۴۷M',rate:94.5},
];

let _plView = 'grid';

function setPLView(v) {
  _plView = v;
  document.getElementById('pl-grid-view').style.display = v==='grid' ? '' : 'none';
  document.getElementById('pl-table-view').style.display = v==='table' ? '' : 'none';
  const gb = document.getElementById('pl-grid-btn');
  const tb = document.getElementById('pl-table-btn');
  if(gb && tb){
    gb.style.background = v==='grid' ? 'var(--accent)' : 'var(--s1)';
    gb.style.color = v==='grid' ? '#fff' : 'var(--text2)';
    gb.style.borderColor = v==='grid' ? 'var(--accent)' : 'var(--b1)';
    tb.style.background = v==='table' ? 'var(--accent)' : 'var(--s1)';
    tb.style.color = v==='table' ? '#fff' : 'var(--text2)';
    tb.style.borderColor = v==='table' ? 'var(--accent)' : 'var(--b1)';
  }
  filterProducts();
}

function filterProducts() {
  const q = (document.getElementById('pl-search')||{value:''}).value.toLowerCase();
  const cat = (document.getElementById('pl-cat')||{value:''}).value;
  const status = (document.getElementById('pl-status')||{value:''}).value;
  const model = (document.getElementById('pl-model')||{value:''}).value;
  const filtered = PL_PRODUCTS.filter(p =>
    (!q || p.name.includes(q) || p.cat.includes(q)) &&
    (!cat || p.cat === cat) &&
    (!status || p.status === status) &&
    (!model || p.model === model)
  );
  const lbl = document.getElementById('pl-count-label');
  if(lbl) lbl.textContent = `نمایش ۱–${filtered.length} از ${filtered.length} محصول`;
  renderPLGrid(filtered);
  renderPLTable(filtered);
}

function renderPLGrid(items) {
  const g = document.getElementById('pl-grid');
  if(!g) return;
  g.innerHTML = items.map(p => `
    <div style="background:var(--s2);border:1px solid var(--b1);border-radius:14px;overflow:hidden;transition:transform .15s,box-shadow .15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
      <div style="padding:16px;text-align:center;background:linear-gradient(135deg,var(--b1),var(--s1));">
        <div style="font-size:40px;line-height:1;">${p.emoji}</div>
      </div>
      <div style="padding:14px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:8px;">
          <div style="font-size:13px;font-weight:800;color:var(--text);line-height:1.3;">${p.name}</div>
          <span style="flex-shrink:0;font-size:10px;padding:2px 7px;border-radius:99px;font-weight:700;background:${p.statusColor}18;color:${p.statusColor};border:1px solid ${p.statusColor}30;">${p.status}</span>
        </div>
        <div style="font-size:10.5px;color:var(--text3);margin-bottom:10px;">${p.cat} · <span style="font-family:monospace;">${p.model}</span></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:10px 0;border-top:1px solid var(--b1);border-bottom:1px solid var(--b1);margin-bottom:10px;">
          <div style="text-align:center;"><div style="font-size:10px;color:var(--text3);">سفارشات</div><div style="font-size:14px;font-weight:800;color:var(--text);">${p.orders||'—'}</div></div>
          <div style="text-align:center;"><div style="font-size:10px;color:var(--text3);">درآمد</div><div style="font-size:14px;font-weight:800;color:var(--green);">${p.rev}</div></div>
        </div>
        <div style="display:flex;gap:6px;">
          <a href="/admin/products/${p.id}" style="flex:1;height:30px;border-radius:7px;border:1px solid var(--b1);background:none;color:var(--text2);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;text-decoration:none;">مشاهده</a>
          <a href="/admin/products/${p.id}/edit" style="flex:1;height:30px;border-radius:7px;border:none;background:var(--accent);color:#fff;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;text-decoration:none;">ویرایش</a>
          <button onclick="duplicateProduct(${p.id})" title="تکثیر محصول" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--b1);background:none;color:var(--text2);font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-copy"></i></button>
        </div>
      </div>
    </div>
  `).join('');
}

function renderPLTable(items) {
  const tb = document.getElementById('pl-table-body');
  if(!tb) return;
  tb.innerHTML = items.map(p => `
    <tr style="border-top:1px solid var(--b1);" onmouseover="this.style.background='rgba(255,255,255,.012)'" onmouseout="this.style.background=''">
      <td style="padding:10px 16px;"><div style="display:flex;align-items:center;gap:8px;"><span style="font-size:18px;">${p.emoji}</span><span style="font-size:12.5px;font-weight:700;color:var(--text);">${p.name}</span></div></td>
      <td style="padding:10px 16px;font-size:11.5px;color:var(--text2);">${p.cat}</td>
      <td style="padding:10px 16px;font-size:10.5px;font-family:monospace;color:var(--accent);">${p.model}</td>
      <td style="padding:10px 16px;"><span style="font-size:10.5px;padding:3px 8px;border-radius:99px;font-weight:700;background:${p.statusColor}18;color:${p.statusColor};border:1px solid ${p.statusColor}30;">${p.status}</span></td>
      <td style="padding:10px 16px;font-size:12.5px;font-weight:700;color:var(--text);">${p.orders||'—'}</td>
      <td style="padding:10px 16px;font-size:12px;font-weight:700;color:var(--green);">${p.rev}</td>
      <td style="padding:10px 16px;"><span style="font-size:11px;font-weight:700;color:${p.rate>=95?'var(--green)':p.rate>=88?'var(--orange)':'var(--red)'};">${p.rate?p.rate+'٪':'—'}</span></td>
      <td style="padding:10px 16px;white-space:nowrap;"><a href="/admin/products/${p.id}" style="font-size:10.5px;color:var(--accent);text-decoration:none;margin-left:8px;">مشاهده</a><a href="/admin/products/${p.id}/edit" style="font-size:10.5px;color:var(--text3);text-decoration:none;margin-left:8px;">ویرایش</a><button onclick="duplicateProduct(${p.id})" title="تکثیر" style="font-size:10.5px;color:var(--text3);background:none;border:none;cursor:pointer;padding:0;"><i class="fa-solid fa-copy"></i></button></td>
    </tr>
  `).join('');
}

function plInit() {
  filterProducts();
}

// ── Products Create Page ─────────────────────────────────────────────────────
let _pcStep = 1;
function pcGoStep(n) {
  _pcStep = n;
  for(let i=1;i<=5;i++){
    const c = document.getElementById('pc-step-circle-'+i);
    const l = document.getElementById('pc-step-label-'+i);
    if(c) { c.style.background=i===n?'var(--accent)':'var(--s1)'; c.style.borderColor=i<=n?'var(--accent)':'var(--b2)'; c.style.color=i===n?'#fff':'var(--text3)'; }
    if(l) { l.style.color=i===n?'var(--accent)':'var(--text3)'; }
  }
}
function pcUpdatePreview() {
  const n = document.getElementById('pc-name');
  const e = document.getElementById('pc-emoji');
  const pn = document.getElementById('pc-prev-name');
  const pe = document.getElementById('pc-prev-emoji');
  if(n&&pn) pn.textContent = n.value || 'نام محصول';
  if(e&&pe) pe.textContent = e.value || '💼';
}
function pcSaveDraft() { alert('پیش‌نویس ذخیره شد'); }
function pcPublish() { alert('محصول منتشر شد'); }
function pcAddField() {
  const list = document.getElementById('pc-fields-list');
  if(!list) return;
  const n = list.querySelectorAll('.pc-field-row').length + 1;
  const div = document.createElement('div');
  div.className = 'pc-field-row';
  div.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--s1);border:1px solid var(--b1);border-radius:8px;margin-bottom:6px;';
  div.innerHTML = `
    <div style="width:22px;height:22px;border-radius:5px;background:var(--accent);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${n}</div>
    <input placeholder="key" style="width:110px;height:32px;background:var(--s2);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:11px;font-family:monospace;color:var(--text);outline:none;" dir="ltr"/>
    <input placeholder="برچسب فارسی" style="flex:1;height:32px;background:var(--s2);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:12px;color:var(--text);outline:none;font-family:inherit;"/>
    <select style="height:32px;background:var(--s2);border:1px solid var(--b1);border-radius:6px;padding:0 8px;font-size:11px;color:var(--text2);font-family:inherit;outline:none;"><option>text</option><option>select</option><option>number</option><option>image</option></select>
    <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:var(--text2);cursor:pointer;white-space:nowrap;"><input type="checkbox" style="accent-color:var(--accent);"/>اجباری</label>
    <button onclick="this.closest('.pc-field-row').remove()" style="width:26px;height:26px;border-radius:6px;border:1px solid var(--b1);background:none;color:var(--red);cursor:pointer;font-size:11px;"><i class="fa-solid fa-trash"></i></button>
  `;
  list.appendChild(div);
}

// ── Duplicate Product ────────────────────────────────────────────────────────
function duplicateProduct(id) {
  const orig = PL_PRODUCTS.find(p => p.id === id);
  if(!orig) return;
  const newId = Math.max(...PL_PRODUCTS.map(p=>p.id)) + 1;
  const copy = Object.assign({}, orig, {
    id: newId,
    name: orig.name + ' (کپی)',
    status: 'پیش‌نویس',
    statusColor: 'var(--orange)',
    orders: 0,
    rev: '—',
    rate: 0
  });
  PL_PRODUCTS.push(copy);
  filterProducts();
  // Show toast
  const t = document.getElementById('pl-toast');
  const m = document.getElementById('pl-toast-msg');
  if(t && m) {
    m.textContent = `"${copy.name}" با موفقیت تکثیر شد`;
    t.style.display = 'flex';
    setTimeout(()=>{ t.style.display='none'; }, 3000);
  }
}

// ── Products Categories Modal ────────────────────────────────────────────────
function pcatOpenModal(){ const m=document.getElementById('pcat-modal'); if(m) m.style.display='flex'; }
function pcatCloseModal(){ const m=document.getElementById('pcat-modal'); if(m) m.style.display='none'; }
function pcatEdit(name){ document.getElementById('pcat-modal-title').textContent='ویرایش: '+name; document.getElementById('pcat-name').value=name; pcatOpenModal(); }
function pcatSave(){ alert('دسته‌بندی ذخیره شد'); pcatCloseModal(); }

// init products-list-page on show
const _origShowPage = showPage;
showPage = function(pageId, sectionName) {
  _origShowPage(pageId, sectionName);
  if(pageId === 'products-list-page') setTimeout(plInit, 50);
};
