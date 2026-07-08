/* ═══════════════════════════════════════
   PROFILE PAGE — رفتار استاندارد شده
   (استخراج‌شده از resources/views/app/profile.blade.php)
═══════════════════════════════════════ */
(function () {

  /* ───── Theme Toggle ───── */
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      window.vatanToggleTheme && window.vatanToggleTheme();
    });
  }

  /* ───── Settings Dropdown ───── */
  var settingsBtn  = document.getElementById('settingsBtn');
  var settingsMenu = document.getElementById('settingsMenu');
  var menuOpen     = false;

  function openSettings() {
    settingsMenu.style.display = 'block';
    settingsBtn.setAttribute('aria-expanded', 'true');
    menuOpen = true;
  }

  function closeSettings() {
    settingsMenu.style.display = 'none';
    settingsBtn.setAttribute('aria-expanded', 'false');
    menuOpen = false;
  }

  if (settingsBtn && settingsMenu) {
    settingsBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      menuOpen ? closeSettings() : openSettings();
    });

    document.addEventListener('click', function (e) {
      if (menuOpen && !settingsMenu.contains(e.target) && e.target !== settingsBtn) {
        closeSettings();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menuOpen) closeSettings();
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
  var changeAvatarBtn = document.getElementById('changeAvatarBtn');
  var avatarInput     = document.getElementById('avatarInput');
  var avatarForm      = document.getElementById('avatarUploadForm');

  if (changeAvatarBtn && avatarInput && avatarForm) {
    changeAvatarBtn.addEventListener('click', function () {
      avatarInput.click();
    });

    avatarInput.addEventListener('change', function () {
      if (!avatarInput.files || !avatarInput.files[0]) return;

      var formData = new FormData(avatarForm);
      changeAvatarBtn.disabled = true;

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
          changeAvatarBtn.disabled = false;
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
