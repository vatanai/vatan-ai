// ============================================================================
//  main.js — مسیریابی SPA پنل ادمین (فقط ناوبری، بدون سیستم تم)
//  توجه: توابع تم (toggleMode) در header.blade.php و toggleSub در sidebar.blade.php
//  تعریف شده‌اند و اینجا بازتعریف نمی‌شوند تا تداخلی پیش نیاید.
// ============================================================================

// ── نگاشت صفحه ↔ URL ─────────────────────────────────────────────────────────
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
  'crm-page':                 { breadcrumb: 'سیستم مدیریت پروژه' },
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

const PAGE_SECTIONS = {
  'crm-page': 'crm', 'attendance-page': 'attendance',
  'products-dashboard-page': 'products', 'products-list-page': 'productslist',
  'products-create-page': 'createproduct', 'products-categories-page': 'categories',
  'products-pricing-page': 'pricing', 'ai-hub-page': 'ai', 'ai-models-page': 'models',
  'ai-prompts-page': 'prompts', 'ai-logs-page': 'logs',
};

const pageLoads = new Map();

function executeFragmentScripts(container) {
  container.querySelectorAll('script').forEach(oldScript => {
    const script = document.createElement('script');
    [...oldScript.attributes].forEach(attribute => script.setAttribute(attribute.name, attribute.value));
    script.textContent = oldScript.textContent;
    oldScript.replaceWith(script);
  });
}

function loadExternalScript(src) {
  const existing = document.querySelector('script[src="' + src + '"]');
  if (existing && existing.dataset.loaded === '1') return Promise.resolve();
  return new Promise((resolve, reject) => {
    const script = existing || document.createElement('script');
    script.src = src;
    script.onload = () => { script.dataset.loaded = '1'; resolve(); };
    script.onerror = reject;
    if (!existing) document.body.appendChild(script);
  });
}

function ensurePageLoaded(pageId) {
  if (document.getElementById(pageId)) return Promise.resolve(document.getElementById(pageId));
  const section = PAGE_SECTIONS[pageId];
  if (!section) return Promise.resolve(null);
  if (pageLoads.has(pageId)) return pageLoads.get(pageId);

  const content = document.getElementById('content');
  if (!content) return Promise.resolve(null);
  const loading = document.createElement('div');
  loading.className = 'admin-page-loading';
  loading.setAttribute('aria-live', 'polite');
  loading.textContent = 'در حال آماده‌سازی بخش...';
  content.appendChild(loading);

  const request = fetch('/admin/dashboard/fragment/' + section, {
    credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
  }).then(response => {
    if (!response.ok) throw new Error('بارگذاری بخش با خطا روبه‌رو شد');
    return response.text();
  }).then(html => {
    loading.remove();
    const fragmentRoot = document.createElement('div');
    fragmentRoot.className = 'admin-lazy-fragment';
    fragmentRoot.innerHTML = html;
    content.appendChild(fragmentRoot);
    executeFragmentScripts(fragmentRoot);
    const loadedPage = document.getElementById(pageId);
    if (section === 'ai' && typeof window.Chart === 'undefined') {
      return loadExternalScript('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js')
        .then(() => loadedPage);
    }
    return loadedPage;
  }).catch(error => {
    loading.textContent = error.message || 'بارگذاری بخش ناموفق بود';
    throw error;
  });
  pageLoads.set(pageId, request);
  return request;
}

// ── نمایش یک صفحه و مخفی‌کردن بقیه ───────────────────────────────────────────
function showPage(pageId, sectionName) {
  document.querySelectorAll('[id$="-page"]').forEach(p => { p.style.display = 'none'; });
  const page = document.getElementById(pageId);
  if (!page) {
    ensurePageLoaded(pageId).then(loadedPage => {
      if (loadedPage) showPage(pageId, sectionName);
    }).catch(() => {});
    return;
  }
  page.style.display = 'block';

  if (pageId === 'placeholder-page') {
    const el = document.getElementById('placeholder-section-name');
    if (el && sectionName) el.textContent = sectionName;
  }
  // اجرای امن نمودارهای هر صفحه در صورت وجود
  if (pageId === 'dashboard-page')          setTimeout(function(){ if (typeof initChart === 'function') initChart(); }, 50);
  if (pageId === 'products-dashboard-page') setTimeout(function(){ if (typeof initProductsDashChart === 'function') initProductsDashChart(); }, 50);
  if (pageId === 'ai-hub-page')             setTimeout(function(){ if (typeof initAiHubChart === 'function') initAiHubChart(); }, 50);
  if (pageId === 'crm-page')                setTimeout(function(){ if (typeof crmInit === 'function') crmInit(); else if (typeof crmRender === 'function') crmRender(); }, 50);
  if (pageId === 'attendance-page')         setTimeout(function(){ if (typeof attendanceInit === 'function') attendanceInit(); }, 50);
}

// ── سایدبار موبایل ───────────────────────────────────────────────────────────
function toggleSidebar() {
  const sb = document.querySelector('.sidebar');
  if (sb) sb.classList.toggle('open');
  const ov = document.getElementById('sidebar-overlay');
  if (ov) ov.classList.toggle('show');
}
function closeSidebar() {
  const sb = document.querySelector('.sidebar');
  if (sb) sb.classList.remove('open');
  const ov = document.getElementById('sidebar-overlay');
  if (ov) ov.classList.remove('show');
}

// ── فعال‌سازی آیتم اصلی منو ───────────────────────────────────────────────────
function setActive(el, name, sub, pageId) {
  document.querySelectorAll('.nav-item.active, .nav-link.active').forEach(i => i.classList.remove('active'));
  document.querySelectorAll('.sub-item.active').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
  const bc = document.getElementById('breadcrumb');
  if (bc && name) bc.textContent = name;
  showPage(pageId || 'placeholder-page', name);
  closeSidebar();
  const url = PAGE_URLS[pageId];
  if (url) history.pushState({ pageId, breadcrumb: name }, '', url);
}

// ── فعال‌سازی زیرمنو ──────────────────────────────────────────────────────────
function setActiveSub(el, parent, name, pageId) {
  document.querySelectorAll('.sub-item.active').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
  const bc = (parent ? parent + ' — ' : '') + name;
  const bcEl = document.getElementById('breadcrumb');
  if (bcEl) bcEl.textContent = bc;
  showPage(pageId || 'placeholder-page', name);
  closeSidebar();
  const url = PAGE_URLS[pageId];
  if (url) history.pushState({ pageId, breadcrumb: bc }, '', url);
}

// ── دکمه‌ی back/forward مرورگر ────────────────────────────────────────────────
window.addEventListener('popstate', function (e) {
  const pageId = (e.state && e.state.pageId) ? e.state.pageId : (URL_TO_PAGE[window.location.pathname] || 'dashboard-page');
  const bc     = (e.state && e.state.breadcrumb) ? e.state.breadcrumb : ((PAGE_META[pageId] || {}).breadcrumb || '');
  showPage(pageId, null);
  const el = document.getElementById('breadcrumb');
  if (el) el.textContent = bc;
});

// ── تعیین صفحه از روی URL هنگام بارگذاری ──────────────────────────────────────
function initFromURL() {
  const path   = window.location.pathname;
  const pageId = URL_TO_PAGE[path] || 'dashboard-page';
  const meta   = PAGE_META[pageId] || {};
  history.replaceState({ pageId, breadcrumb: meta.breadcrumb || '' }, '', path);
  if (pageId !== 'dashboard-page') {
    showPage(pageId, null);
    const el = document.getElementById('breadcrumb');
    if (el && meta.breadcrumb) el.textContent = meta.breadcrumb;
  }
}

document.addEventListener('DOMContentLoaded', initFromURL);
