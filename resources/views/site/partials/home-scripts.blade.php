<script>
  /* ── تم آیکون ── */
  function updateThemeIcon() {
    var isLight = document.documentElement.classList.contains('light');
    var icon = document.getElementById('theme-icon');
    if (!icon) return;
    icon.className = isLight ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  }
  updateThemeIcon();
  document.addEventListener('DOMContentLoaded', updateThemeIcon);

  /* ── منو موبایل ── */
  function toggleMenu() {
    document.getElementById('mobile-menu').classList.toggle('open');
  }
  function closeMenu() {
    document.getElementById('mobile-menu').classList.remove('open');
  }

  /* ── FAQ accordion ── */
  function toggleFaq(el) {
    var isOpen = el.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(function(i) {
      i.classList.remove('open');
    });
    if (!isOpen) el.classList.add('open');
  }

  /* ── Intersection Observer — reveal on scroll ── */
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal').forEach(function(el) {
    observer.observe(el);
  });

  /* ── هدر shadow on scroll ── */
  window.addEventListener('scroll', function() {
    var header = document.getElementById('site-header');
    if (window.scrollY > 20) {
      header.style.boxShadow = '0 2px 24px rgba(0,0,0,0.3)';
    } else {
      header.style.boxShadow = 'none';
    }
  }, { passive: true });

  /* ── Smooth scroll برای لینک‌های anchor ── */
  document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      var id = this.getAttribute('href').slice(1);
      var target = document.getElementById(id);
      if (target) {
        e.preventDefault();
        var headerH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 72;
        var top = target.getBoundingClientRect().top + window.pageYOffset - headerH - 20;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });
</script>
