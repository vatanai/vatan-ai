{{-- صف کارهای ایجنت مک — زنده (هر ۴ ثانیه) --}}
<div class="cf-card" id="cf-jobs-card">
  <div class="cf-between"><h3 class="cf-card-title" style="margin:0"><i class="fa-solid fa-microchip"></i> کارهای ایجنت مک @include('cf::partials.help', ['k' => 'jobs'])</h3>
    <span class="cf-agent" id="cf-agent-state"><i class="fa-solid fa-circle"></i> <span>…</span></span></div>
  <div class="cf-jobs" id="cf-jobs" style="margin-top:12px"><div class="cf-muted">در حال بارگذاری…</div></div>
</div>
<script>
(function () {
  var box = document.getElementById('cf-jobs'), st = document.getElementById('cf-agent-state'), pill = document.getElementById('cf-agent-pill');
  if (!box || box.dataset.bound) return; box.dataset.bound = 1;
  var esc = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };
  var tone = {queued: '', running: 'is-info', done: 'is-success', failed: 'is-danger', cancelled: ''};
  var hadRunning = false;
  function load() {
    fetch(@json(route('cf.jobs.feed')), {headers: {'Accept': 'application/json'}, credentials: 'same-origin'}).then(function (r) { return r.json(); }).then(function (d) {
      var a = d.agent || {};
      st.className = 'cf-agent' + (a.online ? ' is-on' : '');
      st.querySelector('span').textContent = a.online ? ('آنلاین · ' + (a.host || '')) : ('خاموش' + (a.last_seen ? ' · آخرین تماس ' + a.last_seen : ''));
      if (pill) { pill.className = 'cf-agent' + (a.online ? ' is-on' : ''); pill.querySelector('b').textContent = a.online ? 'آنلاین' : 'خاموش'; }
      if (!d.jobs.length) { box.innerHTML = '<div class="cf-muted">هنوز کاری ثبت نشده است.</div>'; return; }
      var running = false;
      box.innerHTML = d.jobs.map(function (j) {
        if (j.status === 'running' || j.status === 'queued') running = true;
        return '<div class="cf-job"><div><b style="color:var(--text-h)">' + esc(j.type) + '</b> <span class="cf-muted">· ' + esc(j.message || '') + ' · ' + esc(j.at) + '</span>'
          + (j.production ? ' <a href="' + j.production + '" style="color:var(--primary)">مشاهده</a>' : '') + '</div>'
          + '<span class="cf-pill ' + (tone[j.status] || '') + '">' + esc(j.status_fa) + (j.status === 'running' ? ' ' + j.progress + '٪' : '') + '</span>'
          + (j.status === 'running' ? '<div class="cf-job-bar"><span style="width:' + j.progress + '%"></span></div>' : '') + '</div>';
      }).join('');
      if (hadRunning && !running && document.body.dataset.cfReloadOnDone) location.reload();
      hadRunning = running;
    }).catch(function () {});
  }
  load(); setInterval(load, 4000);
})();
</script>
