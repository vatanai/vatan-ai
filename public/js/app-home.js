(function () {
  var home = document.querySelector('.home-page');
  if (home && 'IntersectionObserver' in window) {
    var imageObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.setAttribute('data-hb-loaded', '');
        imageObserver.unobserve(entry.target);
      });
    }, { rootMargin: '400px' });
    home.querySelectorAll('[data-hb-background]').forEach(function (element) {
      imageObserver.observe(element);
    });
    clearTimeout(window.homeImageFallback);
  }

  // ویدیوهای هوم فقط وقتی نزدیک viewport هستند منبع می‌گیرند و پخش می‌شوند.
  // به این ترتیب تعداد زیاد محصولات باعث دانلود هم‌زمان فایل‌های MP4 نمی‌شود.
  var lazyVideos = document.querySelectorAll('[data-hb-video-src]');
  function activateVideo(video) {
    if (!video.dataset.hbVideoLoaded) {
      video.src = video.dataset.hbVideoSrc;
      video.dataset.hbVideoLoaded = '1';
      video.load();
    }
    video.play().catch(function () {});
  }
  if (lazyVideos.length) {
    if ('IntersectionObserver' in window) {
      var videoObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && entry.intersectionRatio >= 0.25) {
            activateVideo(entry.target);
          } else if (entry.target.dataset.hbVideoLoaded) {
            entry.target.pause();
          }
        });
      }, { rootMargin: '160px 0px', threshold: [0, 0.25] });
      Array.prototype.forEach.call(lazyVideos, function (video) { videoObserver.observe(video); });
    } else {
      Array.prototype.slice.call(lazyVideos, 0, 2).forEach(activateVideo);
    }
  }

  // ===== باکس تولید تصویر =====
  var igCountEl = document.getElementById('igCount');
  var igMax = 4, igMin = 1;
  function igSet(n) {
    n = Math.max(igMin, Math.min(igMax, n));
    if (igCountEl) igCountEl.textContent = n;
  }
  document.querySelectorAll('[data-ig="inc"]').forEach(function (b) {
    b.addEventListener('click', function () { igSet(parseInt(igCountEl.textContent, 10) + 1); });
  });
  document.querySelectorAll('[data-ig="dec"]').forEach(function (b) {
    b.addEventListener('click', function () { igSet(parseInt(igCountEl.textContent, 10) - 1); });
  });

  // بزرگ‌شدن خودکار پرامپت
  var igPrompt = document.getElementById('igPrompt');
  var igForm = document.getElementById('home-search-form');
  var igResults = document.getElementById('ig-search-results');
  var igSearchTimer = null;
  var igSearchRequest = null;
  if (igPrompt) {
    igPrompt.addEventListener('input', function () {
      igPrompt.parentElement.classList.toggle('has-value', igPrompt.value.trim().length > 0);
      igPrompt.style.height = 'auto';
      igPrompt.style.height = igPrompt.scrollHeight + 'px';
      clearTimeout(igSearchTimer);
      if (igPrompt.value.trim().length < 2) {
        if (igResults) { igResults.hidden = true; igResults.innerHTML = ''; }
        return;
      }
      igSearchTimer = setTimeout(runHomeSearch, 260);
    });
    igPrompt.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        igForm?.requestSubmit();
      }
    });
  }

  document.getElementById('ig-focus-search')?.addEventListener('click', function () {
    igPrompt?.focus({ preventScroll: true });
    var cursorPosition = igPrompt?.value.length || 0;
    igPrompt?.setSelectionRange(cursorPosition, cursorPosition);
  });

  if (igForm) {
    igForm.addEventListener('submit', function (event) {
      if ((igPrompt?.value || '').trim().length < 2) {
        event.preventDefault();
        igPrompt?.focus();
      }
    });
  }

  function runHomeSearch() {
    var query = (igPrompt?.value || '').trim();
    if (query.length < 2 || !igResults) return;
    if (igSearchRequest) igSearchRequest.abort();
    igSearchRequest = new AbortController();
    fetch(igForm.dataset.searchUrl + '?q=' + encodeURIComponent(query), {
      headers: { 'Accept': 'application/json' },
      signal: igSearchRequest.signal,
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) throw new Error('search_failed');
      return response.json();
    }).then(function (data) {
      igResults.innerHTML = '';
      (data.items || []).forEach(function (item) {
        var link = document.createElement('a');
        link.className = 'ig-search-result';
        link.href = item.url;
        var image = document.createElement('img');
        image.src = item.image;
        image.alt = '';
        var copy = document.createElement('span');
        var title = document.createElement('strong');
        title.textContent = item.name;
        var meta = document.createElement('small');
        meta.textContent = item.meta || 'محصول هوش مصنوعی';
        copy.append(title, meta);
        link.append(image, copy);
        igResults.appendChild(link);
      });
      if (!(data.items || []).length) {
        var empty = document.createElement('div');
        empty.className = 'ig-search-empty';
        empty.textContent = 'محصولی با این عبارت پیدا نشد';
        igResults.appendChild(empty);
      } else {
        var all = document.createElement('a');
        all.className = 'ig-search-all';
        all.href = data.all_results_url;
        all.textContent = 'نمایش همه نتایج';
        igResults.appendChild(all);
      }
      igResults.hidden = false;
    }).catch(function (error) {
      if (error.name !== 'AbortError') igResults.hidden = true;
    });
  }

  document.addEventListener('click', function (event) {
    if (igResults && !event.target.closest('.ig-prompt-wrap')) igResults.hidden = true;
  });

})();
