/* ═══════════════════════════════════════
   PROFILE PAGE — رفتار استاندارد شده
   (استخراج‌شده از resources/views/app/profile.blade.php)
═══════════════════════════════════════ */
(function () {

  /* ───── Main Tabs ───── */
  var tabs   = document.querySelectorAll('.profile-tab');
  var panels = document.querySelectorAll('.profile-panel');

  function activateProfileTab(target) {
    var selectedTab = document.querySelector('.profile-tab[data-tab="' + target + '"]');
    var selectedPanel = document.querySelector('.profile-panel[data-panel="' + target + '"]');
    if (!selectedTab || !selectedPanel) return;

    tabs.forEach(function (tab) { tab.classList.toggle('active', tab === selectedTab); });
    panels.forEach(function (panel) {
      var key = panel.getAttribute('data-panel');
      var show = key === target;
      panel.style.display = show ? ((key === 'grid' || key === 'saved' || key === 'referral') ? 'grid' : 'block') : 'none';
    });

  }

  function activateReferralSubtab(target) {
    var selected = document.querySelector('[data-referral-subtab="' + target + '"]');
    if (!selected) return;

    document.querySelectorAll('[data-referral-subtab]').forEach(function (tab) {
      var isActive = tab === selected;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    document.querySelectorAll('[data-referral-subpanel]').forEach(function (panel) {
      var isVisible = panel.getAttribute('data-referral-subpanel') === target;
      panel.classList.toggle('is-active', isVisible);
      panel.style.display = isVisible ? 'grid' : 'none';
    });
  }

  function requestedReferralSubtab() {
    var value = new URLSearchParams(window.location.search).get('subtab');
    return value === 'custom-products' || value === 'journey' ? value : 'affiliate';
  }

  var profileRoot = document.querySelector('.profile-page');
  var panelRequests = {};

  function scrollToProfilePanel(panel) {
    if (!panel) return;

    var fixedHeader = document.querySelector('#vatan-topnav, .app-mobile-header');
    var headerHeight = fixedHeader ? fixedHeader.getBoundingClientRect().height : 0;
    var top = panel.getBoundingClientRect().top + window.pageYOffset - headerHeight - 12;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  }

  function showProfilePanelError(panel) {
    if (!panel) return;
    panel.classList.remove('is-loading');
    panel.innerHTML = '<div class="grid-empty"><p>بارگذاری این بخش انجام نشد. دوباره تلاش کن.</p></div>';
  }

  function loadProfilePanel(target) {
    var panel = document.querySelector('.profile-panel[data-panel="' + target + '"]');
    if (!panel || !panel.dataset.lazyPanel) return Promise.resolve(panel);
    if (panel.dataset.loaded === '1') return Promise.resolve(panel);
    if (panelRequests[target]) return panelRequests[target];

    var endpoint = panel.getAttribute('data-endpoint');
    if (!endpoint) return Promise.resolve(panel);

    panel.classList.add('is-loading');
    panelRequests[target] = fetch(endpoint, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) throw new Error('profile-panel-failed');
        return response.json();
      })
      .then(function (payload) {
        var template = document.createElement('template');
        template.innerHTML = payload.html || '';
        var source = template.content.querySelector('.profile-panel[data-panel="' + target + '"]');
        if (source) {
          // پنل‌های تنبل با یک ریشهٔ کامل برمی‌گردند. فقط innerHTML را
          // کپی‌کردن، کلاس‌های حیاتی مثل referral-program را از بین می‌برد
          // و باعث می‌شود تمام استایل اختصاصی پنل اعمال نشود.
          panel.className = source.className;
          panel.innerHTML = source.innerHTML;
        } else {
          panel.innerHTML = payload.html || '';
        }
        panel.dataset.loaded = '1';
        panel.classList.remove('is-loading');
        bindFilesSubtabs();
        bindGridCells(panel);
        initReferralControls();
        if (target === 'referral') {
          activateReferralSubtab(requestedReferralSubtab());
        }
        return panel;
      })
      .finally(function () {
        delete panelRequests[target];
      });

    return panelRequests[target];
  }

  function requestProfilePanel(target, shouldScroll) {
    var panel = document.querySelector('.profile-panel[data-panel="' + target + '"]');
    return loadProfilePanel(target)
      .then(function (loadedPanel) {
        if (shouldScroll) {
          window.setTimeout(function () { scrollToProfilePanel(loadedPanel || panel); }, 40);
        }
        return loadedPanel;
      })
      .catch(function () {
        showProfilePanelError(panel);
        return null;
      });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');
      activateProfileTab(target, false);
      requestProfilePanel(target, false);
      if (target === 'referral') {
        activateReferralSubtab('affiliate');
        var referralUrl = new URL(window.location.href);
        referralUrl.searchParams.set('tab', 'referral');
        referralUrl.searchParams.delete('subtab');
        referralUrl.hash = 'referral-program';
        history.replaceState(null, '', referralUrl.pathname + referralUrl.search + referralUrl.hash);
      }
    });
  });

  document.addEventListener('click', function (event) {
    var tab = event.target.closest('[data-referral-subtab]');
    if (!tab) return;
      var target = tab.getAttribute('data-referral-subtab');
      activateProfileTab('referral', false);
      requestProfilePanel('referral', false);
      activateReferralSubtab(target);
      var url = new URL(window.location.href);
      url.searchParams.set('tab', 'referral');
      if (target === 'custom-products' || target === 'journey') url.searchParams.set('subtab', target);
      else url.searchParams.delete('subtab');
      url.hash = 'referral-program';
      history.replaceState(null, '', url.pathname + url.search + url.hash);
    });

  document.querySelectorAll('[data-open-referral]').forEach(function (button) {
    button.addEventListener('click', function () {
      // ورود به همکاری در فروش نباید کاربر را به پایین صفحه پرتاب کند؛
      // هدر پروفایل باید در لینک مستقیم و بعد از بارگذاری پنل قابل‌مشاهده بماند.
      activateProfileTab('referral', false);
      requestProfilePanel('referral', false);
      activateReferralSubtab('affiliate');
      history.replaceState(null, '', '#referral-program');
    });
  });

  var requestedParams = new URLSearchParams(window.location.search);
  var requestedTab = requestedParams.get('tab');
  var requestedFileTab = requestedParams.get('file_tab');
  if (['grid', 'saved', 'files', 'referral'].indexOf(requestedTab) !== -1) {
    activateProfileTab(requestedTab, true);
    requestProfilePanel(requestedTab, requestedTab !== 'grid' && requestedTab !== 'referral');
  }
  if (window.location.hash === '#referral-program' || requestedTab === 'referral' || requestedTab === 'custom-products') {
    // لینک عمیق مرورگر ممکن است قبل از آماده‌شدن پنل، صفحه را به پایین
    // ببرد و هدر پروفایل را از دید خارج کند. پنل در پس‌زمینه آماده می‌شود
    // اما موقعیت صفحه عمداً روی ابتدای پروفایل باقی می‌ماند.
    if (window.pageYOffset > 0) window.scrollTo(0, 0);
    activateProfileTab('referral', true);
    requestProfilePanel('referral', false);
    activateReferralSubtab(requestedTab === 'custom-products' ? 'custom-products' : requestedReferralSubtab());
  }

  function preloadProfilePanels() {
    if (!profileRoot || profileRoot.dataset.authenticated !== '1') return;

    var targets = ['saved', 'files', 'referral'].filter(function (target) {
      return document.querySelector('.profile-panel[data-panel="' + target + '"]');
    });
    var index = 0;
    var loadNext = function () {
      if (index >= targets.length) return;
      var target = targets[index++];
      loadProfilePanel(target)
        .catch(function () {})
        .finally(function () { window.setTimeout(loadNext, 80); });
    };
    var schedule = window.requestIdleCallback
      ? function (callback) { window.requestIdleCallback(callback, { timeout: 1800 }); }
      : function (callback) { window.setTimeout(callback, 900); };
    schedule(loadNext);
  }

  preloadProfilePanels();

  /* ───── تصویر بندانگشتی خروجی‌های ویدیویی گرید ───── */
  function initVideoGridPosters() {
    var cards = document.querySelectorAll('.grid-cell--video');
    if (!cards.length) return;

    function renderPoster(card) {
      var poster = card.querySelector('.grid-video-poster');
      var sourceVideo = card.querySelector('.grid-video-source');
      if (!poster || !sourceVideo || poster.dataset.posterReady === 'loading') return;

      var existingPoster = poster.getAttribute('src');
      if (existingPoster) {
        poster.dataset.posterReady = 'ready';
        poster.addEventListener('error', function () {
          poster.removeAttribute('src');
          poster.dataset.posterReady = '';
          renderPoster(card);
        }, { once: true });
        return;
      }

      var source = poster.getAttribute('data-video-source') || sourceVideo.getAttribute('data-src') || '';
      if (!source) return;

      poster.dataset.posterReady = 'loading';
      var cleanUp = function () {
        sourceVideo.pause();
        sourceVideo.removeAttribute('src');
        sourceVideo.load();
      };
      var fail = function () {
        poster.dataset.posterReady = 'failed';
        cleanUp();
      };
      var capture = function () {
        if (!sourceVideo.videoWidth || !sourceVideo.videoHeight) {
          fail();
          return;
        }

        var width = Math.min(sourceVideo.videoWidth, 640);
        var height = Math.max(1, Math.round(width * sourceVideo.videoHeight / sourceVideo.videoWidth));
        var canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        try {
          var context = canvas.getContext('2d');
          context.drawImage(sourceVideo, 0, 0, width, height);
          poster.src = canvas.toDataURL('image/jpeg', .82);
          poster.dataset.posterReady = 'ready';
        } catch (error) {
          fail();
          return;
        }

        cleanUp();
      };
      var seekToFifthFrame = function () {
        var fifthFrameTime = 5 / 30;
        var duration = Number.isFinite(sourceVideo.duration) ? sourceVideo.duration : fifthFrameTime;
        sourceVideo.addEventListener('seeked', capture, { once: true });
        try {
          sourceVideo.currentTime = Math.min(fifthFrameTime, Math.max(0, duration - .01));
        } catch (error) {
          fail();
        }
      };

      sourceVideo.addEventListener('loadedmetadata', seekToFifthFrame, { once: true });
      sourceVideo.addEventListener('error', fail, { once: true });
      sourceVideo.src = source;
      sourceVideo.preload = 'metadata';
      sourceVideo.load();
    }

    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          renderPoster(entry.target);
          observer.unobserve(entry.target);
        });
      }, { rootMargin: '220px 0px' });
      cards.forEach(function (card) {
        if (card.dataset.posterObserverBound === '1') return;
        card.dataset.posterObserverBound = '1';
        observer.observe(card);
      });
    } else {
      Array.prototype.slice.call(cards, 0, 4).forEach(function (card) {
        if (card.dataset.posterObserverBound === '1') return;
        card.dataset.posterObserverBound = '1';
        renderPoster(card);
      });
    }
  }

  initVideoGridPosters();

  /* ───── لینک دعوت و اشتراک‌گذاری ───── */
  function initReferralControls() {
  var referralLinkInput = document.getElementById('referralLinkInput');
  var copyReferralLink = document.getElementById('copyReferralLink');
  var referralCopyFeedback = document.getElementById('referralCopyFeedback');

  function showReferralFeedback(message) {
    if (!referralCopyFeedback) return;
    referralCopyFeedback.textContent = message;
    window.clearTimeout(showReferralFeedback.timer);
    showReferralFeedback.timer = window.setTimeout(function () {
      referralCopyFeedback.textContent = '';
    }, 2800);
  }

  function copyText(value) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(value);
    }

    return new Promise(function (resolve, reject) {
      var helper = document.createElement('textarea');
      helper.value = value;
      helper.style.position = 'fixed';
      helper.style.opacity = '0';
      document.body.appendChild(helper);
      helper.select();
      try {
        document.execCommand('copy') ? resolve() : reject(new Error('copy-failed'));
      } catch (error) {
        reject(error);
      }
      helper.remove();
    });
  }

  if (copyReferralLink && referralLinkInput) {
    copyReferralLink.addEventListener('click', function () {
      copyText(referralLinkInput.value).then(function () {
        showReferralFeedback('لینک دعوت کپی شد؛ حالا برای مخاطبانت بفرست.');
      }).catch(function () {
        referralLinkInput.select();
        showReferralFeedback('لینک انتخاب شد؛ آن را کپی کن.');
      });
    });
  }

  /* ───── جست‌وجو و انتخاب محصول برای لینک اختصاصی ───── */
  var productForm = document.getElementById('referralProductForm');
  var productSearch = document.getElementById('referralProductSearch');
  var productId = document.getElementById('referralProductId');
  var productSubmit = document.getElementById('referralProductSubmit');
  var productOptions = document.getElementById('referralProductOptions');
  var productSelection = document.getElementById('referralProductSelection');
  var productButtons = productOptions ? Array.prototype.slice.call(productOptions.querySelectorAll('.referral-product-option')) : [];

  function setProductOptionsVisibility(show) {
    if (!productOptions || !productSearch) return;
    productOptions.classList.toggle('is-open', show);
    productSearch.setAttribute('aria-expanded', show ? 'true' : 'false');
  }

  function filterProducts() {
    if (!productSearch || !productOptions) return;
    var query = productSearch.value.trim().toLocaleLowerCase('fa-IR');
    var visibleCount = 0;

    productButtons.forEach(function (button) {
      var haystack = (button.textContent || '').trim().toLocaleLowerCase('fa-IR');
      var visible = !query || haystack.indexOf(query) !== -1;
      button.hidden = !visible;
      if (visible) visibleCount += 1;
    });

    var empty = productOptions.querySelector('.referral-product-filter-empty');
    if (!visibleCount && productButtons.length) {
      if (!empty) {
        empty = document.createElement('div');
        empty.className = 'referral-product-options-empty referral-product-filter-empty';
        empty.textContent = 'محصولی با این نام پیدا نشد.';
        productOptions.appendChild(empty);
      }
      empty.hidden = false;
    } else if (empty) {
      empty.hidden = true;
    }

    setProductOptionsVisibility(true);
  }

  if (productSearch && productId && productSubmit) {
    productSearch.addEventListener('focus', function () {
      filterProducts();
    });
    productSearch.addEventListener('input', function () {
      productId.value = '';
      productSubmit.disabled = true;
      if (productSelection) productSelection.textContent = 'یک محصول را از نتایج انتخاب کن.';
      filterProducts();
    });

    productButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        productId.value = button.getAttribute('data-product-id') || '';
        productSearch.value = button.getAttribute('data-product-name') || button.textContent.trim();
        productSubmit.disabled = !productId.value;
        if (productSelection) productSelection.textContent = 'محصول انتخاب‌شده: ' + productSearch.value;
        setProductOptionsVisibility(false);
      });
    });

    if (productForm) {
      productForm.addEventListener('submit', function (event) {
        if (!productId.value) {
          event.preventDefault();
          productSearch.focus();
          setProductOptionsVisibility(true);
        }
      });
    }

    document.addEventListener('click', function (event) {
      if (!productForm || !productForm.contains(event.target)) setProductOptionsVisibility(false);
    });
  }

  /* کپی لینک محصول از همان مسیر امن کپی لینک عمومی استفاده می‌کند. */
  document.querySelectorAll('[data-copy-referral-link]').forEach(function (button) {
    button.addEventListener('click', function () {
      copyText(button.getAttribute('data-copy-referral-link') || '').then(function () {
        showReferralFeedback('لینک محصول کپی شد؛ حالا برای مخاطبانت بفرست.');
      }).catch(function () {
        showReferralFeedback('کپی لینک انجام نشد؛ دوباره تلاش کن.');
      });
    });
  });
  }

  initReferralControls();

  /* ───── آپلود عکس پروفایل ───── */
  var changeAvatarBtn    = document.getElementById('changeAvatarBtn');
  var avatarClickTrigger = document.getElementById('avatarClickTrigger');
  var avatarInput        = document.getElementById('avatarInput');
  var avatarForm         = document.getElementById('avatarUploadForm');

  // کلیک روی خود عکس آواتار هم مثل قبل، دیالوگ انتخاب فایل رو باز می‌کنه
  [changeAvatarBtn, avatarClickTrigger].forEach(function (trigger) {
    if (trigger && avatarInput) {
      trigger.addEventListener('click', function () {
        avatarInput.click();
      });
    }
  });

  if (avatarInput && avatarForm) {
    avatarInput.addEventListener('change', function () {
      if (!avatarInput.files || !avatarInput.files[0]) return;

      var formData = new FormData(avatarForm);
      if (changeAvatarBtn) changeAvatarBtn.disabled = true;

      fetch(avatarForm.action, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.success) throw new Error('upload-failed');

          var url = data.avatar_url + '?t=' + Date.now();

          [
            document.getElementById('profileAvatarImg'),
            document.getElementById('profileAvatarImgSm')
          ].forEach(function (img) {
            if (!img) return;
            img.src = url;
            img.classList.remove('avatar-img--placeholder', 'icon-filter');
          });

          // آواتار هدر بالای سایت و نوار پایین موبایل هم به‌صورت زنده آپدیت بشه
          document.querySelectorAll('.topnav-avatar img, .vatan-nav-avatar').forEach(function (img) {
            img.src = url;
          });
        })
        .catch(function () {
          alert('آپلود عکس پروفایل با خطا مواجه شد. لطفا دوباره تلاش کن.');
        })
        .finally(function () {
          if (changeAvatarBtn) changeAvatarBtn.disabled = false;
        });
    });
  }

  /* ───── مودال پیش‌نمایش عکس گرید ───── */
  var previewModal   = document.getElementById('gridPreviewModal');
  var previewImg     = document.getElementById('gridPreviewImg');
  var previewVideo   = document.getElementById('gridPreviewVideo');
  var previewPlay    = document.getElementById('gridPreviewPlay');
  var previewDownload = document.getElementById('gridPreviewDownload');
  var previewShare    = document.getElementById('gridPreviewShare');
  var previewRecreate = document.getElementById('gridPreviewRecreate');
  var previewDelete   = document.getElementById('gridPreviewDelete');
  var previewDeleteConfirm = document.getElementById('gridPreviewDeleteConfirm');
  var previewDeleteForm = document.getElementById('gridPreviewDeleteForm');
  var previewDeleteCancel = document.getElementById('gridPreviewDeleteCancel');
  var previewDate     = document.getElementById('gridPreviewDate');
  var previewClose    = document.getElementById('gridPreviewClose');
  var previewImgWrap  = previewVideo ? previewVideo.closest('.grid-preview-img-wrap') : null;
  var previewDownloadTrackUrl = '';

  function updatePreviewPlayButton() {
    if (!previewPlay || !previewVideo) return;
    var isVideo = !previewVideo.hidden && Boolean(previewVideo.src);
    previewPlay.hidden = !isVideo || !previewVideo.paused;
    previewPlay.setAttribute('aria-label', previewVideo.paused ? 'پخش ویدیو' : 'توقف ویدیو');
  }

  function syncPreviewVideoFrame() {
    if (!previewImgWrap || !previewVideo || previewVideo.hidden) {
      if (previewImgWrap) {
        previewImgWrap.classList.remove('is-video');
        previewImgWrap.style.removeProperty('--preview-video-ratio');
      }
      return;
    }

    previewImgWrap.classList.add('is-video');
    if (previewVideo.videoWidth && previewVideo.videoHeight) {
      previewImgWrap.style.setProperty('--preview-video-ratio', previewVideo.videoWidth + ' / ' + previewVideo.videoHeight);
    }
  }

  function openGridPreview(cell) {
    if (!previewModal) return;

    var mediaKind   = cell.getAttribute('data-media-kind') || 'image';
    var mediaUrl    = cell.getAttribute('data-media-url') || cell.getAttribute('data-image') || cell.getAttribute('data-video') || '';
    var imgUrl      = cell.getAttribute('data-image') || '';
    var videoUrl    = cell.getAttribute('data-video') || '';
    var posterUrl   = cell.getAttribute('data-poster') || '';
    var date        = cell.getAttribute('data-date') || '';
    var productCreateUrl = cell.getAttribute('data-product-create-url') || '';
    var deleteUrl = cell.getAttribute('data-delete-url') || '';
    previewDownloadTrackUrl = cell.getAttribute('data-product-download-url') || '';

    var isVideo = mediaKind === 'video' && videoUrl;
    previewImg.hidden = Boolean(isVideo);
    previewVideo.hidden = !isVideo;
    syncPreviewVideoFrame();
    previewImg.src = isVideo ? '' : imgUrl;
    if (isVideo) {
      previewVideo.poster = posterUrl;
      previewVideo.src = videoUrl;
      previewVideo.pause();
      previewVideo.load();
    } else {
      previewVideo.pause();
      previewVideo.removeAttribute('src');
      previewVideo.load();
    }
    updatePreviewPlayButton();
    previewDownload.href = mediaUrl;
    previewDate.textContent = date;

    if (previewRecreate) {
      if (productCreateUrl) {
        previewRecreate.href = productCreateUrl;
        previewRecreate.classList.remove('is-disabled');
        previewRecreate.setAttribute('aria-disabled', 'false');
      } else {
        previewRecreate.href = '#';
        previewRecreate.classList.add('is-disabled');
        previewRecreate.setAttribute('aria-disabled', 'true');
      }
    }

    if (previewDeleteForm) previewDeleteForm.action = deleteUrl;
    if (previewDelete) previewDelete.hidden = !deleteUrl;
    if (previewDeleteConfirm) previewDeleteConfirm.hidden = true;

    previewModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    syncPreviewVideoFrame();
  }

  function closeGridPreview() {
    if (!previewModal) return;
    previewModal.style.display = 'none';
    previewImg.src = '';
    if (previewVideo) {
      previewVideo.pause();
      previewVideo.removeAttribute('src');
      previewVideo.load();
      previewVideo.hidden = true;
    }
    syncPreviewVideoFrame();
    if (previewPlay) previewPlay.hidden = true;
    if (previewImg) previewImg.hidden = false;
    if (previewRecreate) {
      previewRecreate.href = '#';
      previewRecreate.classList.add('is-disabled');
      previewRecreate.setAttribute('aria-disabled', 'true');
    }
    if (previewDeleteConfirm) previewDeleteConfirm.hidden = true;
    if (previewDeleteForm) previewDeleteForm.removeAttribute('action');
    document.body.style.overflow = '';
  }

  function bindGridCells(root) {
    (root || document).querySelectorAll('.grid-cell--clickable').forEach(function (cell) {
      if (cell.dataset.profilePreviewBound === '1') return;
      cell.dataset.profilePreviewBound = '1';
      cell.addEventListener('click', function () {
        openGridPreview(cell);
      });
    });
  }

  bindGridCells(document);

  if (previewClose) previewClose.addEventListener('click', closeGridPreview);

  if (previewDelete) {
    previewDelete.addEventListener('click', function (event) {
      event.stopPropagation();
      if (previewDeleteForm && previewDeleteForm.action) {
        previewDeleteConfirm.hidden = false;
      }
    });
  }
  if (previewDeleteCancel) {
    previewDeleteCancel.addEventListener('click', function () {
      previewDeleteConfirm.hidden = true;
    });
  }

  if (previewPlay && previewVideo) {
    previewPlay.addEventListener('click', function (event) {
      event.stopPropagation();
      previewVideo.controls = true;
      previewVideo.play().catch(function () {});
    });
    previewVideo.addEventListener('play', updatePreviewPlayButton);
    previewVideo.addEventListener('pause', updatePreviewPlayButton);
    previewVideo.addEventListener('ended', updatePreviewPlayButton);
    previewVideo.addEventListener('loadedmetadata', syncPreviewVideoFrame);
    window.addEventListener('resize', syncPreviewVideoFrame);
  }

  if (previewDownload) {
    previewDownload.addEventListener('click', function () {
      if (!previewDownloadTrackUrl) return;
      fetch(previewDownloadTrackUrl, {
        method: 'POST',
        keepalive: true,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Accept': 'application/json'
        }
      }).catch(function () {});
    });
  }

  var previewBackdrop = previewModal ? previewModal.querySelector('.grid-preview-backdrop') : null;
  if (previewBackdrop) previewBackdrop.addEventListener('click', closeGridPreview);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && previewModal && previewModal.style.display === 'flex') {
      closeGridPreview();
    }
  });

  if (previewShare) {
    previewShare.addEventListener('click', function () {
      var url = (previewVideo && !previewVideo.hidden ? previewVideo.src : previewImg.src) || '';
      if (!url) return;
      if (navigator.share) {
        navigator.share({ title: 'خروجی ساخته‌شده در وطن AI', url: url }).catch(function () {});
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () {
          alert('لینک عکس کپی شد.');
        });
      }
    });
  }

  /* ───── Files Sub-Tabs ───── */
  function activateFilesSubtab(sub) {
    var allowed = ['face-profiles', 'personal', 'used-products'];
    var target = allowed.indexOf(sub) !== -1 ? sub : 'face-profiles';
    document.querySelectorAll('.files-sub-tab').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-sub') === target);
    });
    var facePanel = document.getElementById('files-face-profiles');
    var personalPanel = document.getElementById('files-personal');
    var productsPanel = document.getElementById('files-used-products');
    if (facePanel) facePanel.style.display = target === 'face-profiles' ? 'block' : 'none';
    if (personalPanel) personalPanel.style.display = target === 'personal' ? 'grid' : 'none';
    if (productsPanel) productsPanel.style.display = target === 'used-products' ? 'grid' : 'none';
  }

  function bindFilesSubtabs(root) {
    (root || document).querySelectorAll('.files-sub-tab').forEach(function (btn) {
      if (btn.dataset.profileSubtabBound === '1') return;
      btn.dataset.profileSubtabBound = '1';
      btn.addEventListener('click', function () {
        activateFilesSubtab(btn.getAttribute('data-sub'));
      });
    });
  }

  bindFilesSubtabs(document);
  activateFilesSubtab(requestedTab === 'files' && requestedFileTab ? requestedFileTab : 'face-profiles');

  /* ───── بارگذاری مرحله‌ای خروجی‌های قدیمی‌تر ───── */
  var mediaSentinel = document.querySelector('[data-media-sentinel]');
  var mediaRequestActive = false;

  function loadMoreProfileMedia() {
    if (!mediaSentinel || mediaRequestActive) return;
    var cursor = mediaSentinel.getAttribute('data-next-cursor') || '';
    var endpoint = mediaSentinel.getAttribute('data-media-endpoint') || '';
    if (!cursor || !endpoint) return;

    mediaRequestActive = true;
    var url = endpoint + '?cursor=' + encodeURIComponent(cursor);
    fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) throw new Error('profile-media-failed');
        return response.json();
      })
      .then(function (payload) {
        var grid = document.querySelector('.profile-panel[data-panel="grid"]');
        if (!grid) return;
        grid.insertAdjacentHTML('beforeend', payload.html || '');
        bindGridCells(grid);
        initVideoGridPosters();
        if (payload.next_cursor) {
          mediaSentinel.setAttribute('data-next-cursor', payload.next_cursor);
        } else {
          mediaSentinel.removeAttribute('data-next-cursor');
        }
      })
      .catch(function () {})
      .finally(function () {
        mediaRequestActive = false;
      });
  }

  if (mediaSentinel && mediaSentinel.getAttribute('data-next-cursor')) {
    if ('IntersectionObserver' in window) {
      var mediaObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) loadMoreProfileMedia();
        });
      }, { rootMargin: '640px 0px' });
      mediaObserver.observe(mediaSentinel);
    } else {
      window.addEventListener('scroll', loadMoreProfileMedia, { passive: true });
    }
  }

}());
