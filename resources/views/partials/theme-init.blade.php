{{-- اعمال تم روز/شب قبل از رندر (بدون چشمک) برای صفحات مستقلی که از layouts.app استفاده نمی‌کنند.
     همان قرارداد layouts.app: کلید localStorage «vatan-theme» با مقادیر light / dark / system و کلاس light روی <html>. --}}
<script>
  (function () {
    var html = document.documentElement;
    var mode = 'dark';
    try { mode = localStorage.getItem('vatan-theme') || 'dark'; } catch (e) {}
    var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var resolved = mode === 'system' ? (systemDark ? 'dark' : 'light') : (mode === 'light' ? 'light' : 'dark');
    html.classList.toggle('light', resolved === 'light');
    html.classList.toggle('dark', resolved !== 'light');
  }());
</script>
