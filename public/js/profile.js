/* ═══════════════════════════════════════
   PROFILE PAGE — رفتار استاندارد شده
   (استخراج‌شده از resources/views/app/profile.blade.php)
═══════════════════════════════════════ */
(function () {

  /* ───── Main Tabs ───── */
  var tabs   = document.querySelectorAll('.profile-tab');
  var panels = document.querySelectorAll('.profile-panel');

  function activateProfileTab(target, shouldScroll) {
    var selectedTab = document.querySelector('.profile-tab[data-tab="' + target + '"]');
    var selectedPanel = document.querySelector('.profile-panel[data-panel="' + target + '"]');
    if (!selectedTab || !selectedPanel) return;

    tabs.forEach(function (tab) { tab.classList.toggle('active', tab === selectedTab); });
    panels.forEach(function (panel) {
      var key = panel.getAttribute('data-panel');
      var show = key === target;
      panel.style.display = show ? ((key === 'grid' || key === 'saved' || key === 'referral') ? 'grid' : 'block') : 'none';
    });

    if (shouldScroll) {
      window.setTimeout(function () {
        selectedPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 40);
    }
  }

  var referralSubtabs = document.querySelectorAll('[data-referral-subtab]');
  var referralSubpanels = document.querySelectorAll('[data-referral-subpanel]');

  function activateReferralSubtab(target) {
    var selected = document.querySelector('[data-referral-subtab="' + target + '"]');
    if (!selected) return;

    referralSubtabs.forEach(function (tab) {
      var isActive = tab === selected;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    referralSubpanels.forEach(function (panel) {
      var isVisible = panel.getAttribute('data-referral-subpanel') === target;
      panel.classList.toggle('is-active', isVisible);
      panel.style.display = isVisible ? 'grid' : 'none';
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');
      activateProfileTab(target, false);
      if (target === 'referral') {
        activateReferralSubtab('affiliate');
        history.replaceState(null, '', '#referral-program');
      }
    });
  });

  referralSubtabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-referral-subtab');
      activateProfileTab('referral', false);
      activateReferralSubtab(target);
      var url = new URL(window.location.href);
      url.searchParams.set('tab', 'referral');
      if (target === 'custom-products') url.searchParams.set('subtab', 'custom-products');
      else url.searchParams.delete('subtab');
      url.hash = 'referral-program';
      history.replaceState(null, '', url.pathname + url.search + url.hash);
    });
  });

  document.querySelectorAll('[data-open-referral]').forEach(function (button) {
    button.addEventListener('click', function () {
      activateProfileTab('referral', true);
      activateReferralSubtab('affiliate');
      history.replaceState(null, '', '#referral-program');
    });
  });

  var requestedTab = new URLSearchParams(window.location.search).get('tab');
  var requestedSubtab = new URLSearchParams(window.location.search).get('subtab');
  if (window.location.hash === '#referral-program' || requestedTab === 'referral' || requestedTab === 'custom-products') {
    activateProfileTab('referral', true);
    activateReferralSubtab(requestedTab === 'custom-products' || requestedSubtab === 'custom-products' ? 'custom-products' : 'affiliate');
  }

  /* ───── لینک دعوت و اشتراک‌گذاری ───── */
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
  var previewDate     = document.getElementById('gridPreviewDate');
  var previewClose    = document.getElementById('gridPreviewClose');
  var previewDownloadTrackUrl = '';

  function updatePreviewPlayButton() {
    if (!previewPlay || !previewVideo) return;
    var isVideo = !previewVideo.hidden && Boolean(previewVideo.src);
    previewPlay.hidden = !isVideo || !previewVideo.paused;
    previewPlay.setAttribute('aria-label', previewVideo.paused ? 'پخش ویدیو' : 'توقف ویدیو');
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
    previewDownloadTrackUrl = cell.getAttribute('data-product-download-url') || '';

    var isVideo = mediaKind === 'video' && videoUrl;
    previewImg.hidden = Boolean(isVideo);
    previewVideo.hidden = !isVideo;
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

    previewModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
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
    if (previewPlay) previewPlay.hidden = true;
    if (previewImg) previewImg.hidden = false;
    if (previewRecreate) {
      previewRecreate.href = '#';
      previewRecreate.classList.add('is-disabled');
      previewRecreate.setAttribute('aria-disabled', 'true');
    }
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.grid-cell--clickable').forEach(function (cell) {
    cell.addEventListener('click', function () {
      openGridPreview(cell);
    });
  });

  if (previewClose) previewClose.addEventListener('click', closeGridPreview);

  if (previewPlay && previewVideo) {
    previewPlay.addEventListener('click', function (event) {
      event.stopPropagation();
      previewVideo.controls = true;
      previewVideo.play().catch(function () {});
    });
    previewVideo.addEventListener('play', updatePreviewPlayButton);
    previewVideo.addEventListener('pause', updatePreviewPlayButton);
    previewVideo.addEventListener('ended', updatePreviewPlayButton);
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
  document.querySelectorAll('.files-sub-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.files-sub-tab').forEach(function (b) {
        b.classList.remove('active');
      });
      btn.classList.add('active');
      var sub = btn.getAttribute('data-sub');
      document.getElementById('files-personal').style.display = sub === 'personal' ? 'grid' : 'none';
      document.getElementById('files-used-products').style.display = sub === 'used-products' ? 'grid' : 'none';
    });
  });

}());
