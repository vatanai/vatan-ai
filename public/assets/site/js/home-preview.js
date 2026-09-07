(() => {
  'use strict';

  const header = document.querySelector('[data-header]');
  const menu = document.querySelector('.vp-mobile-menu');
  const overlay = document.querySelector('.vp-menu-overlay');
  const menuToggle = document.querySelector('[data-menu-toggle]');
  const menuClose = document.querySelectorAll('[data-menu-close], [data-menu-link]');

  const setMenu = (isOpen) => {
    menu.classList.toggle('is-open', isOpen);
    overlay.classList.toggle('is-open', isOpen);
    menu.setAttribute('aria-hidden', String(!isOpen));
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    document.body.style.overflow = isOpen ? 'hidden' : '';
  };

  menuToggle?.addEventListener('click', () => setMenu(!menu.classList.contains('is-open')));
  menuClose.forEach((element) => element.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setMenu(false); });

  const onScroll = () => header?.classList.toggle('is-scrolled', window.scrollY > 12);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const themeToggle = document.querySelector('[data-preview-theme]');
  themeToggle?.addEventListener('click', () => {
    const nextMode = document.documentElement.classList.contains('light') ? 'dark' : 'light';
    if (typeof window.vatanSetTheme === 'function') {
      window.vatanSetTheme(nextMode);
    } else {
      document.documentElement.classList.toggle('light', nextMode === 'light');
    }
  });

  const revealItems = document.querySelectorAll('.vp-reveal');
  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -30px' });
  revealItems.forEach((item) => revealObserver.observe(item));

  const navigation = [...document.querySelectorAll('.vp-nav a')]
    .filter((link) => link.getAttribute('href')?.startsWith('#'));
  const sections = navigation.map((link) => document.querySelector(link.getAttribute('href'))).filter(Boolean);
  const navObserver = new IntersectionObserver((entries) => {
    const activeEntry = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
    if (!activeEntry) return;
    navigation.forEach((link) => {
      const shouldBeActive = link.getAttribute('href') === `#${activeEntry.target.id}`;
      const wasActive = link.classList.contains('is-active');

      if (!shouldBeActive && wasActive) {
        link.classList.add('is-leaving');
        window.setTimeout(() => link.classList.remove('is-leaving'), 260);
      }

      if (shouldBeActive) link.classList.remove('is-leaving');
      link.classList.toggle('is-active', shouldBeActive);
    });
  }, { rootMargin: '-30% 0px -62% 0px', threshold: 0 });
  sections.forEach((section) => navObserver.observe(section));

  const equalizePlanCards = () => {
    const catalog = document.querySelector('.vp-plans--catalog');
    if (!catalog) return;

    const cards = [...catalog.querySelectorAll('.vpc--landing')];
    cards.forEach((card) => card.style.removeProperty('height'));

    if (window.matchMedia('(max-width: 700px)').matches || cards.length < 2) return;

    const regularCards = cards.filter((card) => !card.classList.contains('is-featured'));
    const featuredCard = cards.find((card) => card.classList.contains('is-featured'));
    const regularHeight = Math.ceil(Math.max(...regularCards.map((card) => card.scrollHeight)));

    regularCards.forEach((card) => { card.style.height = `${regularHeight}px`; });
    if (featuredCard) {
      featuredCard.style.height = `${Math.max(featuredCard.scrollHeight, regularHeight + 20)}px`;
    }
  };

  const schedulePlanCardSizing = () => window.requestAnimationFrame(equalizePlanCards);
  schedulePlanCardSizing();
  window.addEventListener('resize', schedulePlanCardSizing, { passive: true });
  document.fonts?.ready?.then(schedulePlanCardSizing);

  const showcase = document.querySelector('[data-showcase]');
  if (showcase) {
    const cards = [...showcase.querySelectorAll('.vp-showcase-card')];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // محور اولیه‌ی حرکت روی مرکز افقی سکشن قرار می‌گیرد؛ مقدار منفی باعث می‌شد
    // ردیف کارت‌ها از ابتدا به یک سمت متمایل دیده شود.
    let position = 0;
    let velocity = 0;
    let dragging = false;
    let pointerX = 0;
    let lastMoveAt = 0;
    let lastFrameAt = performance.now();
    let layout = { cardWidth: 191, cardHeight: 268, pitch: 250, cycle: 2500 };

    const modulo = (value, length) => ((value % length) + length) % length;
    const relativePosition = (value, cycle) => modulo(value + (cycle / 2), cycle) - (cycle / 2);

    const measure = () => {
      const width = showcase.clientWidth;
      const cardWidth = Math.min(191, Math.max(106, width * 0.149));
      layout = {
        cardWidth,
        cardHeight: cardWidth * 1.405,
        pitch: cardWidth * 1.35,
        cycle: cardWidth * 1.35 * cards.length,
      };
    };

    const renderShowcase = () => {
      const { cardWidth, cardHeight, pitch, cycle } = layout;
      const cardRadius = Math.max(14, Math.min(20, Math.round(cardWidth * 0.105)));
      cards.forEach((card, index) => {
        const x = relativePosition((index * pitch) + position, cycle);
        const edge = Math.min(Math.abs(x) / (showcase.clientWidth * 0.44), 1.18);
        const scale = 1 + (edge * 0.82);
        const depth = Math.round((1.2 - Math.min(edge, 1)) * 100);
        const tilt = Math.sign(x) * Math.min(edge * 8, 8);
        // کارت پیش از بازگشت حلقه‌ای کاملاً محو می‌شود تا انتقال آن به ابتدای مسیر دیده نشود.
        const edgeVisibility = edge <= 1.02 ? 1 : Math.max(0, (1.18 - edge) / 0.16);

        card.style.width = `${cardWidth}px`;
        card.style.height = `${cardHeight}px`;
        card.style.zIndex = String(depth);
        card.style.opacity = String(edgeVisibility);
        card.style.pointerEvents = edgeVisibility > 0 ? 'auto' : 'none';
        card.style.borderRadius = `${cardRadius}px`;
        card.style.transform = `translate(-50%, -50%) translate3d(${x}px, 0, 0) scale(${scale}) rotateY(${tilt}deg)`;
      });
    };

    const animateShowcase = (now) => {
      const delta = Math.min(48, now - lastFrameAt);
      lastFrameAt = now;
      if (!dragging && !reduceMotion) {
        position -= delta * 0.045;
        position += velocity * delta;
        velocity *= 0.92;
        if (Math.abs(velocity) < 0.0001) velocity = 0;
      }
      renderShowcase();
      requestAnimationFrame(animateShowcase);
    };

    showcase.addEventListener('pointerdown', (event) => {
      if (event.pointerType === 'mouse' && event.button !== 0) return;
      dragging = true;
      velocity = 0;
      pointerX = event.clientX;
      lastMoveAt = performance.now();
      showcase.classList.add('is-dragging');
      showcase.setPointerCapture(event.pointerId);
    });

    showcase.addEventListener('pointermove', (event) => {
      if (!dragging) return;
      const now = performance.now();
      const distance = event.clientX - pointerX;
      const elapsed = Math.max(1, now - lastMoveAt);
      position += distance;
      velocity = distance / elapsed;
      pointerX = event.clientX;
      lastMoveAt = now;
      renderShowcase();
    });

    const releaseShowcase = (event) => {
      if (!dragging) return;
      dragging = false;
      showcase.classList.remove('is-dragging');
      if (showcase.hasPointerCapture(event.pointerId)) showcase.releasePointerCapture(event.pointerId);
    };
    showcase.addEventListener('pointerup', releaseShowcase);
    showcase.addEventListener('pointercancel', releaseShowcase);
    window.addEventListener('resize', () => { measure(); renderShowcase(); }, { passive: true });

    measure();
    renderShowcase();
    requestAnimationFrame(animateShowcase);
  }

  document.querySelectorAll('.vp-faq-item button').forEach((button) => {
    button.addEventListener('click', () => {
      const item = button.closest('.vp-faq-item');
      const shouldOpen = !item.classList.contains('is-open');
      document.querySelectorAll('.vp-faq-item').forEach((faq) => {
        faq.classList.remove('is-open');
        faq.querySelector('button').setAttribute('aria-expanded', 'false');
      });
      if (shouldOpen) {
        item.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
      }
    });
  });
})();
