<script>
  (function () {
    var lazyMedia = document.querySelectorAll('.trends-card-media[data-src]:not(video)');

    function revealMedia(media) {
      var source = media.getAttribute('data-src');
      if (!source) return;
      media.src = source;
      media.removeAttribute('data-src');
    }

    if ('IntersectionObserver' in window) {
      var imageObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          revealMedia(entry.target);
          observer.unobserve(entry.target);
        });
      }, { rootMargin: '80px 0px' });

      lazyMedia.forEach(function (media) { imageObserver.observe(media); });
    } else {
      lazyMedia.forEach(revealMedia);
    }

    function loadAndPlayVideo(card) {
      var video = card.querySelector('[data-src].trends-card-video');
      if (!video) {
        video = card.querySelector('.trends-card-video');
      }
      if (!video) return;

      var source = video.getAttribute('data-src');
      if (source) {
        video.src = source;
        video.removeAttribute('data-src');
        video.addEventListener('playing', function () {
          video.classList.add('is-ready');
        }, { once: true });
      }

      var playback = video.play();
      if (playback && typeof playback.catch === 'function') {
        playback.catch(function () {});
      }
    }

    function pauseVideo(card) {
      var video = card.querySelector('.trends-card-video');
      if (video && !video.paused) video.pause();
    }

    document.querySelectorAll('[data-trends-video-card]').forEach(function (card) {
      card.addEventListener('pointerenter', function (event) {
        if (event.pointerType !== 'touch') loadAndPlayVideo(card);
      });
      card.addEventListener('pointerleave', function () { pauseVideo(card); });
      card.addEventListener('focusin', function () { loadAndPlayVideo(card); });
      card.addEventListener('focusout', function () { pauseVideo(card); });
    });

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) return;
      document.querySelectorAll('[data-trends-video-card]').forEach(pauseVideo);
    });
  }());

  (function () {
    document.querySelectorAll('[data-trends-tab-group]').forEach(function (group) {
      var tabs = group.querySelectorAll('[data-tab-target]');
      var panels = group.querySelectorAll('.trends-tab-panel');

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var target = tab.getAttribute('data-tab-target');
          tabs.forEach(function (item) {
            var active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          panels.forEach(function (panel) {
            var active = panel.id === target;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
          });
        });
      });

      function activeSlider() {
        return group.querySelector('.trends-tab-panel.is-active [data-trends-slider]');
      }

      group.querySelector('[data-slider-prev]')?.addEventListener('click', function () {
        activeSlider()?.scrollBy({ left: 320, behavior: 'smooth' });
      });
      group.querySelector('[data-slider-next]')?.addEventListener('click', function () {
        activeSlider()?.scrollBy({ left: -320, behavior: 'smooth' });
      });
    });
  }());

  (function () {
    var input = document.getElementById('trends-search-input');
    var form = document.getElementById('trends-search-form');
    var results = document.getElementById('trends-search-results');
    var timer = null;
    var request = null;

    if (!input || !form || !results) return;

    input.addEventListener('input', function () {
      clearTimeout(timer);
      if (input.value.trim().length < 2) {
        results.hidden = true;
        results.innerHTML = '';
        return;
      }
      timer = setTimeout(runSearch, 260);
    });

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        results.hidden = true;
      }
      if (event.key === 'Enter') {
        event.preventDefault();
        form.requestSubmit();
      }
    });

    form.addEventListener('submit', function (event) {
      if (input.value.trim().length < 2) {
        event.preventDefault();
        input.focus();
      }
    });

    function runSearch() {
      var query = input.value.trim();
      if (query.length < 2) return;
      if (request) request.abort();
      request = new AbortController();

      fetch(@json(route('app.home.search')) + '?q=' + encodeURIComponent(query), {
        headers: { 'Accept': 'application/json' },
        signal: request.signal,
        credentials: 'same-origin'
      }).then(function (response) {
        if (!response.ok) throw new Error('search_failed');
        return response.json();
      }).then(function (data) {
        results.innerHTML = '';
        (data.items || []).forEach(function (item) {
          var link = document.createElement('a');
          link.className = 'trends-search-result';
          link.setAttribute('role', 'option');
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
          results.appendChild(link);
        });

        if (!(data.items || []).length) {
          var empty = document.createElement('div');
          empty.className = 'trends-search-empty';
          empty.textContent = 'محصولی با این عبارت پیدا نشد';
          results.appendChild(empty);
        } else {
          var all = document.createElement('a');
          all.className = 'trends-search-all';
          all.href = data.all_results_url;
          all.textContent = 'نمایش همه نتایج';
          results.appendChild(all);
        }
        results.hidden = false;
      }).catch(function (error) {
        if (error.name !== 'AbortError') results.hidden = true;
      });
    }

    document.addEventListener('click', function (event) {
      if (!event.target.closest('.trends-search')) results.hidden = true;
    });
  }());
</script>
