(function () {
  'use strict';

  var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  function parseList(raw) {
    try {
      var value = JSON.parse(raw || '[]');
      return Array.isArray(value) ? value : [];
    } catch (e) {
      return [];
    }
  }

  // placeholder تایپ‌شونده؛ وقتی باکس فوکوس دارد پنهان می‌شود
  function typeLoop(input) {
    var list = parseList(input.getAttribute('data-examples')), idx = 0, pos = 0, deleting = false;
    function step() {
      if (document.activeElement === input) { input.setAttribute('placeholder', ''); setTimeout(step, 500); return; }
      if (input.value) { setTimeout(step, 500); return; }
      if (!list.length) return;
      var word = list[idx % list.length];
      pos += deleting ? -1 : 1;
      input.setAttribute('placeholder', word.slice(0, Math.max(pos, 0)));
      var delay = deleting ? 28 : 62;
      if (!deleting && pos >= word.length) { deleting = true; delay = 1600; }
      else if (deleting && pos <= 0) { deleting = false; idx = (idx + 1) % list.length; delay = 380; }
      setTimeout(step, delay);
    }
    step();
  }

  // عنوان تایپی «دنبال … هستی؟»
  function typedHeadline(el) {
    var words = parseList(el.getAttribute('data-words'));
    if (!words.length) return;
    var i = 0, pos = words[0].length, deleting = true;
    function step() {
      var word = words[i];
      pos += deleting ? -1 : 1;
      el.textContent = word.slice(0, Math.max(pos, 0));
      var delay = deleting ? 45 : 75;
      if (!deleting && pos >= word.length) { deleting = true; delay = 1800; }
      else if (deleting && pos <= 0) { deleting = false; i = (i + 1) % words.length; delay = 300; }
      setTimeout(step, delay);
    }
    setTimeout(step, 1200);
  }

  // جست‌وجوی زنده (همان API صفحه‌ی خانه)
  function setupForm(form) {
    var input = form.querySelector('.hsv-input');
    var hidden = form.querySelector('input[name="search"]');
    var results = form.querySelector('.hsv-results');
    var url = form.getAttribute('data-search-url');
    var timer = null, controller = null;

    function hideResults() {
      if (results) { results.hidden = true; results.innerHTML = ''; }
    }

    function render(data) {
      if (!results) return;
      results.innerHTML = '';
      var items = (data && data.items) || [];
      items.forEach(function (item) {
        var link = document.createElement('a');
        link.className = 'hsv-result';
        link.href = item.url;
        var img = document.createElement('img');
        img.src = item.image;
        img.alt = '';
        img.loading = 'lazy';
        var copy = document.createElement('span');
        var title = document.createElement('b');
        title.textContent = item.name;
        var meta = document.createElement('small');
        meta.textContent = item.meta || 'محصول هوش مصنوعی';
        copy.append(title, meta);
        link.append(img, copy);
        results.appendChild(link);
      });
      if (!items.length) {
        var empty = document.createElement('div');
        empty.className = 'hsv-empty';
        empty.textContent = 'محصولی با این عبارت پیدا نشد';
        results.appendChild(empty);
      } else if (data.all_results_url) {
        var all = document.createElement('a');
        all.className = 'hsv-all';
        all.href = data.all_results_url;
        all.textContent = 'نمایش همه نتایج';
        results.appendChild(all);
      }
      results.hidden = false;
    }

    function runSearch(q) {
      if (!url || !results) return;
      if (controller) controller.abort();
      controller = new AbortController();
      fetch(url + '?q=' + encodeURIComponent(q), {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal,
        credentials: 'same-origin'
      }).then(function (response) {
        if (!response.ok) throw new Error('search_failed');
        return response.json();
      }).then(render).catch(function (error) {
        if (error.name !== 'AbortError') hideResults();
      });
    }

    function refresh() {
      var q = input ? input.value.trim() : '';
      if (hidden) hidden.value = q;
      clearTimeout(timer);
      if (q.length < 2) {
        if (controller) controller.abort();
        hideResults();
        return;
      }
      timer = setTimeout(function () { runSearch(q); }, 240);
    }

    if (input) {
      input.addEventListener('input', refresh);
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { input.blur(); hideResults(); }
      });
    }

    form.addEventListener('keydown', function (event) {
      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
      var list = Array.prototype.slice.call(form.querySelectorAll('.hsv-results:not([hidden]) a'));
      if (!list.length) return;
      var i = list.indexOf(document.activeElement), next;
      if (event.key === 'ArrowDown') next = i < 0 ? 0 : (i + 1) % list.length;
      else next = i <= 0 ? list.length - 1 : i - 1;
      event.preventDefault();
      list[next].focus();
    });

    form.addEventListener('submit', function (event) {
      var q = input ? input.value.trim() : '';
      if (hidden) hidden.value = q;
      if (q.length < 2) {
        event.preventDefault();
        if (input) input.focus();
      }
    });

    form._hsvClose = hideResults;
  }

  var forms = document.querySelectorAll('[data-hsv-form]');
  forms.forEach(setupForm);

  document.querySelectorAll('.hsv-input[data-examples]').forEach(function (input) {
    if (reduceMotion) {
      var first = parseList(input.getAttribute('data-examples'))[0];
      if (first) input.setAttribute('placeholder', first);
    } else {
      typeLoop(input);
    }
  });

  document.querySelectorAll('[data-hsv-typed]').forEach(function (el) {
    if (!reduceMotion) typedHeadline(el);
  });

  // کلیک بیرون از فرم: بستن نتایج
  document.addEventListener('mousedown', function (event) {
    forms.forEach(function (form) {
      if (!form.contains(event.target) && form._hsvClose) form._hsvClose();
    });
  });
})();
