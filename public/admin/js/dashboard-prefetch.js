/*
 * آماده‌سازی کم‌ریسک صفحات پنل:
 * فقط لینک‌های GET و همان صفحه‌ای که کاربر قصد بازکردنش را نشان داده است
 * در پس‌زمینه خوانده می‌شوند؛ هیچ محتوایی جایگزین نمی‌شود و منطق صفحات دست‌نخورده می‌ماند.
 */
(function () {
  const prefetched = new Set();
  const prefetching = new Set();

  function canPrefetch(anchor) {
    if (!anchor || anchor.dataset.noPrefetch !== undefined) return false;
    if (anchor.target && anchor.target !== '_self') return false;

    let url;
    try { url = new URL(anchor.href, window.location.href); } catch (e) { return false; }
    if (url.origin !== window.location.origin || !url.pathname.startsWith('/admin/')) return false;
    if (url.pathname === window.location.pathname && url.search === window.location.search) return false;
    if (url.pathname.endsWith('/logout') || url.pathname.endsWith('/refresh')) return false;
    return true;
  }

  function prefetch(anchor) {
    if (!canPrefetch(anchor)) return;

    const url = new URL(anchor.href, window.location.href).href;
    if (prefetched.has(url) || prefetching.has(url)) return;
    if (navigator.connection?.saveData) return;

    prefetching.add(url);
    fetch(url, {
      credentials: 'same-origin',
      cache: 'force-cache',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Purpose': 'prefetch' },
    }).then(response => {
      if (response.ok) prefetched.add(url);
      return response.body?.cancel?.();
    }).catch(() => {}).finally(() => prefetching.delete(url));
  }

  function schedule(anchor) {
    if (window.requestIdleCallback) {
      window.requestIdleCallback(() => prefetch(anchor), { timeout: 900 });
    } else {
      window.setTimeout(() => prefetch(anchor), 80);
    }
  }

  function init() {
    const sidebar = document.getElementById('admin-sidebar');
    if (!sidebar) return;

    sidebar.addEventListener('pointerover', event => {
      const anchor = event.target.closest('a');
      if (anchor && sidebar.contains(anchor)) schedule(anchor);
    }, { passive: true });
    sidebar.addEventListener('focusin', event => {
      const anchor = event.target.closest('a');
      if (anchor && sidebar.contains(anchor)) schedule(anchor);
    }, { passive: true });

    // چند صفحهٔ پرتکرار پس از آماده‌شدن داشبورد و در زمان بیکاری مرورگر گرم می‌شوند.
    const popularPaths = ['/admin/products', '/admin/users', '/admin/finance/overview'];
    const popular = [...sidebar.querySelectorAll('a')].filter(anchor => {
      try { return popularPaths.includes(new URL(anchor.href, window.location.href).pathname); }
      catch (e) { return false; }
    });
    popular.forEach(anchor => schedule(anchor));
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
