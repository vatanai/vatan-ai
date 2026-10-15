/* کارخانه‌ی محتوای وطن — رفتارهای سبک صفحه */
(function () {
  document.addEventListener('click', function (e) {
    var d = e.target.closest('[data-cf-dismiss]');
    if (d) { d.closest('.cf-flash').remove(); return; }
    var open = e.target.closest('[data-cf-open]');
    if (open) {
      var dlg = document.getElementById(open.getAttribute('data-cf-open'));
      if (dlg && dlg.showModal) {
        var tpl = open.getAttribute('data-cf-fill');
        if (tpl && window.cfFill) { window.cfFill(dlg, JSON.parse(tpl)); }
        dlg.showModal();
      }
      return;
    }
    var close = e.target.closest('[data-cf-close]');
    if (close) { close.closest('dialog').close(); return; }
    var ask = e.target.closest('[data-cf-confirm]');
    if (ask) { var box = document.getElementById(ask.getAttribute('data-cf-confirm')); if (box) { box.hidden = !box.hidden; } return; }
    var cp = e.target.closest('[data-cf-copy]');
    if (cp) {
      var src = document.getElementById(cp.getAttribute('data-cf-copy'));
      var text = src ? src.innerText : '';
      var done = function () { var old = cp.innerHTML; cp.innerHTML = '<i class="fa-solid fa-check"></i> کپی شد'; setTimeout(function () { cp.innerHTML = old; }, 1500); };
      if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, function () { selectText(src); }); } else { selectText(src); }
    }
    var seek = e.target.closest('[data-cf-seek]');
    if (seek) { var v = document.getElementById('cf-player'); if (v) { v.currentTime = parseFloat(seek.getAttribute('data-cf-seek')); v.play(); } }
  });
  function selectText(el) { if (!el) return; var r = document.createRange(); r.selectNodeContents(el); var s = getSelection(); s.removeAllRanges(); s.addRange(r); }
  // گرفتن زمان فعلی پلیر برای فرم اصلاحیه
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-cf-mark]');
    if (!b) return;
    var v = document.getElementById('cf-player'); var f = document.getElementById(b.getAttribute('data-cf-mark'));
    if (v && f) { f.value = (Math.round(v.currentTime * 10) / 10).toFixed(1); }
  });
})();
