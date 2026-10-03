/* ویترین اپ هوم — تب‌ها، قبل/بعد، اسلایدر هیرو و پخش ویدیوهای عمودی (بدون وابستگی). */
(function () {
  if (window.__vtVitrineInit) return;
  window.__vtVitrineInit = true;

  function init() {
    // ── تب‌ها
    document.querySelectorAll('[data-vt-tabs]').forEach(function (root) {
      root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-vt-tab]');
        if (!btn || !root.contains(btn)) return;
        var key = btn.getAttribute('data-vt-tab');
        root.querySelectorAll('[data-vt-tab]').forEach(function (b) { b.classList.toggle('is-on', b === btn); });
        root.querySelectorAll('[data-vt-panel]').forEach(function (p) {
          var on = p.getAttribute('data-vt-panel') === key;
          p.hidden = !on;
          if (on) p.scrollLeft = 0;
        });
      });
    });

    // ── قبل / بعد
    document.querySelectorAll('[data-vt-compare]').forEach(function (cmp) {
      var dragging = false;
      function set(x) {
        var r = cmp.getBoundingClientRect();
        var p = Math.max(3, Math.min(97, ((x - r.left) / r.width) * 100));
        cmp.style.setProperty('--p', p + '%');
      }
      cmp.addEventListener('pointerdown', function (e) { dragging = true; set(e.clientX); });
      cmp.addEventListener('pointermove', function (e) { if (dragging || e.pointerType === 'mouse') set(e.clientX); });
      window.addEventListener('pointerup', function () { dragging = false; });
      cmp.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') cmp.style.setProperty('--p', '50%'); });
    });

    // ── هیرو: نقطه‌ها + چرخش خودکار در موبایل
    document.querySelectorAll('[data-vt-hero]').forEach(function (wrap) {
      var track = wrap.querySelector('.vt-hero');
      var dots = wrap.querySelectorAll('.vt-dots span');
      if (!track || !dots.length) return;
      var cards = track.querySelectorAll('.vt-hcard');
      function current() {
        var best = 0, bestDist = Infinity, center = track.getBoundingClientRect().left + track.clientWidth / 2;
        cards.forEach(function (c, i) {
          var r = c.getBoundingClientRect();
          var d = Math.abs(r.left + r.width / 2 - center);
          if (d < bestDist) { bestDist = d; best = i; }
        });
        return best;
      }
      var raf = 0;
      track.addEventListener('scroll', function () {
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(function () {
          var i = current();
          dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
        });
      }, { passive: true });
      if (wrap.getAttribute('data-vt-autoplay') === '1' && window.matchMedia('(max-width:640px)').matches
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var touched = false;
        track.addEventListener('touchstart', function () { touched = true; }, { passive: true });
        setInterval(function () {
          if (touched || document.hidden) return;
          var next = (current() + 1) % cards.length;
          cards[next].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }, 5000);
      }
    });

    // ── ویدیوهای عمودی: هاور در دسکتاپ، دیده‌شدن در لمسی
    var cards = document.querySelectorAll('.vt-vcard');
    if (!cards.length) return;
    function play(card) {
      var v = card.querySelector('video[data-vt-video-src]');
      if (!v) return;
      if (!v.src) { v.src = v.getAttribute('data-vt-video-src'); v.load(); }
      var p = v.play();
      if (p && p.then) p.then(function () { card.classList.add('is-playing'); }).catch(function () {});
      else card.classList.add('is-playing');
    }
    function stop(card) {
      var v = card.querySelector('video');
      if (v && v.src) v.pause();
      card.classList.remove('is-playing');
    }
    var canHover = window.matchMedia('(hover: hover)').matches;
    if (canHover) {
      cards.forEach(function (c) {
        c.addEventListener('mouseenter', function () { play(c); });
        c.addEventListener('mouseleave', function () { stop(c); });
      });
    } else if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { en.intersectionRatio >= 0.6 ? play(en.target) : stop(en.target); });
      }, { threshold: [0, 0.6] });
      cards.forEach(function (c) { io.observe(c); });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
