<script>
(function () {

  /* ── Settings Dropdown (mobile only) ── */
  var settingsBtn  = document.getElementById('settingsBtn');
  var settingsMenu = document.getElementById('settingsMenu');
  var menuOpen     = false;

  if (settingsBtn && settingsMenu) {

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

  /* ── Main Tabs ── */
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
        if (key === 'grid' || key === 'saved') {
          panel.style.display = show ? 'grid' : 'none';
        } else {
          panel.style.display = show ? 'block' : 'none';
        }
      });
    });
  });

  /* ── Files Sub-Tabs ── */
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
</script>
