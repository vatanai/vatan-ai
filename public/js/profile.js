/* ═══════════════════════════════════════
   PROFILE PAGE — رفتار استاندارد شده
   (استخراج‌شده از resources/views/app/profile.blade.php)
═══════════════════════════════════════ */
(function () {

  /* ───── هدر موبایل: منوی همبرگری (کپی از هدر هوم) ───── */
  var profileMenuOpenBtn = document.getElementById('profileMenuOpenBtn');
  var profileMenuOverlay = document.getElementById('profileMenuOverlay');
  var profileMenuSheet   = document.getElementById('profileMenuSheet');
  var profileMenuIsOpen  = false;

  function openProfileMenu() {
    if (!profileMenuOverlay || !profileMenuSheet) return;
    profileMenuOverlay.style.display = 'block';
    setTimeout(function () {
      profileMenuSheet.style.transform = 'scale(1) translateY(0)';
      profileMenuSheet.style.opacity = '1';
    }, 10);
    profileMenuIsOpen = true;
  }

  window.closeProfileMenu = function () {
    if (!profileMenuOverlay || !profileMenuSheet) return;
    profileMenuSheet.style.transform = 'scale(0.9) translateY(-10px)';
    profileMenuSheet.style.opacity = '0';
    setTimeout(function () { profileMenuOverlay.style.display = 'none'; }, 200);
    profileMenuIsOpen = false;
  };

  if (profileMenuOpenBtn) {
    profileMenuOpenBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      profileMenuIsOpen ? window.closeProfileMenu() : openProfileMenu();
    });

    document.addEventListener('click', function (e) {
      if (profileMenuIsOpen && profileMenuSheet && !profileMenuSheet.contains(e.target)) {
        window.closeProfileMenu();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && profileMenuIsOpen) window.closeProfileMenu();
    });
  }

  /* ───── هدر موبایل: تم تاگل داخل منوی همبرگری ───── */
  var profileHeaderThemeToggle = document.getElementById('profileHeaderThemeToggle');
  if (profileHeaderThemeToggle) {
    profileHeaderThemeToggle.addEventListener('click', function () {
      window.vatanToggleTheme && window.vatanToggleTheme();
    });
  }

  /* ───── Main Tabs ───── */
  var tabs   = document.querySelectorAll('.profile-tab');
  var panels = document.querySelectorAll('.profile-panel');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');

      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');

      panels.forEach(function (panel) {
        var key  = panel.getAttribute('data-panel');
        var show = key === target;

        if (key === 'grid') {
          panel.style.display = show ? 'grid' : 'none';
        } else if (key === 'saved') {
          panel.style.display = show ? 'grid' : 'none';
        } else {
          panel.style.display = show ? 'block' : 'none';
        }
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
  var previewDownload = document.getElementById('gridPreviewDownload');
  var previewShare    = document.getElementById('gridPreviewShare');
  var previewDate     = document.getElementById('gridPreviewDate');
  var previewProductLink = document.getElementById('gridPreviewProductLink');
  var previewProductName = document.getElementById('gridPreviewProductName');
  var previewClose    = document.getElementById('gridPreviewClose');

  function openGridPreview(cell) {
    if (!previewModal) return;

    var imgUrl      = cell.getAttribute('data-image') || '';
    var date        = cell.getAttribute('data-date') || '';
    var productName = cell.getAttribute('data-product-name') || 'نامشخص';
    var productUrl  = cell.getAttribute('data-product-url') || '';

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
