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

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');
      activateProfileTab(target, false);
      if (target === 'referral') history.replaceState(null, '', '#referral-program');
    });
  });

  document.querySelectorAll('[data-open-referral]').forEach(function (button) {
    button.addEventListener('click', function () {
      activateProfileTab('referral', true);
      history.replaceState(null, '', '#referral-program');
    });
  });

  var requestedTab = new URLSearchParams(window.location.search).get('tab');
  if (window.location.hash === '#referral-program' || requestedTab === 'referral') {
    activateProfileTab('referral', true);
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
  var previewDownload = document.getElementById('gridPreviewDownload');
  var previewShare    = document.getElementById('gridPreviewShare');
  var previewDate     = document.getElementById('gridPreviewDate');
  var previewProductLink = document.getElementById('gridPreviewProductLink');
  var previewProductName = document.getElementById('gridPreviewProductName');
  var previewClose    = document.getElementById('gridPreviewClose');
  var previewDownloadTrackUrl = '';

  function openGridPreview(cell) {
    if (!previewModal) return;

    var imgUrl      = cell.getAttribute('data-image') || '';
    var date        = cell.getAttribute('data-date') || '';
    var productName = cell.getAttribute('data-product-name') || 'نامشخص';
    var productUrl  = cell.getAttribute('data-product-url') || '';
    previewDownloadTrackUrl = cell.getAttribute('data-product-download-url') || '';

    previewImg.src = imgUrl;
    previewDownload.href = imgUrl;
    previewDate.textContent = date;
    previewProductName.textContent = productName;

    if (productUrl) {
      previewProductLink.href = productUrl;
      previewProductLink.classList.remove('is-disabled');
    } else {
      previewProductLink.href = '#';
      previewProductLink.classList.add('is-disabled');
    }

    previewModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeGridPreview() {
    if (!previewModal) return;
    previewModal.style.display = 'none';
    previewImg.src = '';
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.grid-cell--clickable').forEach(function (cell) {
    cell.addEventListener('click', function () {
      openGridPreview(cell);
    });
  });

  if (previewClose) previewClose.addEventListener('click', closeGridPreview);

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
      var url = previewImg.src;
      if (navigator.share) {
        navigator.share({ title: 'عکس ساخته‌شده در وطن AI', url: url }).catch(function () {});
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
      document.getElementById('files-created').style.display  = sub === 'created'  ? 'grid' : 'none';
      document.getElementById('files-personal').style.display = sub === 'personal' ? 'grid' : 'none';
    });
  });

}());
