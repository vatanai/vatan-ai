(() => {
  'use strict';

  const root = document.querySelector('[data-article-id]');
  if (!root) return;

  const endpoint = root.dataset.eventUrl;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  const sendEvent = (eventType, metadata = {}) => {
    if (!endpoint || !csrf) return;
    fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({ event_type: eventType, metadata }),
    }).catch(() => {});
  };

  const toc = root.querySelector('[data-article-toc]');
  const headings = [...root.querySelectorAll('.article-content h2, .article-content h3')];
  if (toc && headings.length) {
    const seen = new Set();
    headings.forEach((heading, index) => {
      let id = heading.id || `article-section-${index + 1}`;
      while (seen.has(id)) id = `${id}-${index + 1}`;
      heading.id = id;
      seen.add(id);

      const link = document.createElement('a');
      link.href = `#${id}`;
      link.textContent = heading.textContent.trim();
      link.dataset.level = heading.tagName.slice(1);
      link.addEventListener('click', () => sendEvent('toc_click', { heading: id }));
      toc.appendChild(link);
    });

    if ('IntersectionObserver' in window) {
      const links = [...toc.querySelectorAll('a')];
      const observer = new IntersectionObserver((entries) => {
        entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
          links.forEach((link) => link.classList.toggle('is-active', link.hash === `#${entry.target.id}`));
        });
      }, { rootMargin: '-20% 0px -70% 0px' });
      headings.forEach((heading) => observer.observe(heading));
    }
  } else if (toc) {
    toc.closest('.article-toc')?.remove();
  }

  root.querySelectorAll('[data-copy-prompt]').forEach((button) => {
    button.addEventListener('click', async () => {
      const text = button.closest('[data-prompt-box]')?.querySelector('pre')?.textContent.trim();
      if (!text) return;
      try {
        await navigator.clipboard.writeText(text);
        const original = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-check"></i> کپی شد';
        setTimeout(() => { button.innerHTML = original; }, 1800);
        sendEvent('prompt_copy', { length: text.length });
      } catch (_) {}
    });
  });

  root.querySelector('[data-share-article]')?.addEventListener('click', async () => {
    const payload = { title: document.title, url: window.location.href };
    try {
      if (navigator.share) await navigator.share(payload);
      else await navigator.clipboard.writeText(payload.url);
      sendEvent('share_click', { method: navigator.share ? 'native' : 'clipboard' });
    } catch (_) {}
  });

  root.querySelectorAll('[data-article-event]').forEach((element) => {
    element.addEventListener('click', () => {
      sendEvent(element.dataset.articleEvent, {
        product_id: Number(element.dataset.productId) || null,
        target: element.getAttribute('href') || null,
      });
    });
  });

  const reached = new Set();
  let ticking = false;
  const readProgress = () => {
    ticking = false;
    const documentHeight = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
    const progress = (window.scrollY / documentHeight) * 100;
    [[50, 'scroll_50'], [90, 'scroll_90']].forEach(([threshold, event]) => {
      const storageKey = `vatan-article-${root.dataset.articleId}-${event}`;
      if (progress >= threshold && !reached.has(event) && !sessionStorage.getItem(storageKey)) {
        reached.add(event);
        sessionStorage.setItem(storageKey, '1');
        sendEvent(event, { progress: threshold });
      }
    });
  };
  window.addEventListener('scroll', () => {
    if (!ticking) {
      ticking = true;
      requestAnimationFrame(readProgress);
    }
  }, { passive: true });
  readProgress();
})();
