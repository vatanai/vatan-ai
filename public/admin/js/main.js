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

// ── نمایش یک صفحه و مخفی‌کردن بقیه ───────────────────────────────────────────
function showPage(pageId, sectionName) {
  document.querySelectorAll('[id$="-page"]').forEach(p => { p.style.display = 'none'; });
  const page = document.getElementById(pageId);
  if (!page) return;
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
