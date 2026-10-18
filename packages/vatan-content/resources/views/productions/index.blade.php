@extends('cf::layout')
@php $J = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP); @endphp

@section('cf-actions')
  <a href="{{ route('cf.calendar') }}" class="cf-btn"><i class="fa-solid fa-calendar-days"></i> تقویم</a>
@endsection

@section('cf-page')
<div class="cf-stack" id="cf-queue">
  <div class="cf-card">
    <div class="cf-between" style="margin-bottom:12px">
      <h3 class="cf-card-title" style="margin:0"><i class="fa-solid fa-list-check"></i> صف و سفارش‌های ساخت @include('cf::partials.help', ['k' => 'queue'])</h3>
      <span class="cf-row"><span class="cf-agent" data-q-agent><i class="fa-solid fa-circle"></i> ایجنت</span><span class="cf-q-live"><i class="fa-solid fa-circle"></i> <span data-q-live>زنده</span></span></span>
    </div>
    <div class="cf-q-stats" role="tablist">
      <button type="button" class="cf-q-stat" data-f=""><strong data-s="all">۰</strong><span>کل پست‌ها</span></button>
      <button type="button" class="cf-q-stat success" data-f="built"><strong data-s="built">۰</strong><span>ساخته‌شده</span></button>
      <button type="button" class="cf-q-stat warning" data-f="active"><strong data-s="active">۰</strong><span>در صف و در حال ساخت</span></button>
      <button type="button" class="cf-q-stat info" data-f="review"><strong data-s="review">۰</strong><span>منتظر تأیید/اصلاح</span></button>
      <button type="button" class="cf-q-stat success" data-f="published"><strong data-s="published">۰</strong><span>منتشرشده</span></button>
      <button type="button" class="cf-q-stat danger" data-f="failed"><strong data-s="failed">۰</strong><span>ناموفق</span></button>
    </div>
  </div>

  <div class="cf-q-toolbar">
    <label class="cf-q-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="جستجو: کد، هوک، محصول، سبک…" data-q-search></label>
    <span class="cf-row">
      <select class="cf-input" style="width:auto" data-q-format><option value="">همه‌ی قالب‌ها</option><option>ریلز</option><option>اسلایدی</option><option>پست</option></select>
      <span class="cf-muted" style="font-size:12px" data-q-count></span>
    </span>
  </div>

  <div class="cf-table-wrap"><table class="cf-table cf-q-table" style="min-width:1250px">
    <thead><tr><th>شناسه</th><th>انتشار</th><th>پست</th><th>محصول</th><th>سبک · موزیک</th><th>نسخه</th><th>وضعیت ساخت</th><th>زمان و هزینه</th><th>انتشار و آمار</th><th>عملیات</th></tr></thead>
    <tbody data-q-body></tbody>
  </table></div>
  <div class="cf-q-pages" data-q-pages></div>
</div>
@endsection

@section('cf-scripts')
<script>
(function () {
  var rows = {!! $J($rows) !!}, feed = {!! $J(route('cf.productions.feed')) !!}, csrf = {!! $J(csrf_token()) !!};
  var filter = {!! $J(in_array($status, ['built', 'active', 'review', 'published', 'failed'], true) ? $status : '') !!}, q = '', fmt = '', page = 1, per = 10;
  var $ = function (s) { return document.querySelector(s); };
  var fa = function (x) { return String(x).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  var esc = function (x) { var d = document.createElement('div'); d.textContent = x == null ? '' : String(x); return d.innerHTML; };
  var tone = {planned: '', queued: 'is-warning', scripting: 'is-warning', rendering: 'is-warning', review: 'is-info', revision: 'is-warning', approved: 'is-success', published: 'is-success', rejected: 'is-danger', failed: 'is-danger'};
  var groups = {built: function (r) { return r.version > 0; }, active: function (r) { return r.status === 'queued' && r.job || r.status === 'rendering' || r.status === 'scripting'; },
    review: function (r) { return r.status === 'review' || r.status === 'revision'; }, published: function (r) { return r.status === 'published'; }, failed: function (r) { return r.status === 'failed' || r.status === 'rejected'; }};
  function list() {
    return rows.filter(function (r) {
      if (filter && !groups[filter](r)) return false; if (fmt && r.format !== fmt) return false;
      if (q) { var h = [r.code, r.hook, r.product, r.style, r.music, r.status_fa, r.date].join(' '); if (h.indexOf(q) < 0) return false; }
      return true;
    });
  }
  function render() {
    document.querySelectorAll('[data-s]').forEach(function (s) { var k = s.getAttribute('data-s'); s.textContent = fa(k === 'all' ? rows.length : rows.filter(groups[k]).length); });
    document.querySelectorAll('[data-f]').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-f') === filter); });
    var L = list(), pages = Math.max(1, Math.ceil(L.length / per)); if (page > pages) page = pages;
    $('[data-q-count]').textContent = fa(L.length) + ' مورد';
    $('[data-q-body]').innerHTML = L.slice((page - 1) * per, page * per).map(function (r) {
      var job = r.job && (r.job.status === 'queued' || r.job.status === 'running') ? '<span class="cf-job-bar" style="display:block;margin-top:6px;width:120px"><span style="width:' + (r.job.progress || 0) + '%"></span></span>' : '';
      var jmsg = r.job ? '<span class="cf-q-sub">' + esc(r.job.message || '') + (r.job.at ? ' · ' + esc(r.job.at) : '') + (r.job.agent ? ' · ' + esc(r.job.agent) : '') + '</span>' : '<span class="cf-q-sub">' + esc(r.scenes ? fa(r.scenes) + ' صحنه · ' + fa(r.stories) + ' استوری' : 'بدون صحنه') + '</span>';
      var stats = r.published ? '<span class="cf-pill is-success">منتشر: ' + esc(r.published) + '</span>' +
        '<span class="cf-q-sub">' + (r.reach != null ? 'دسترسی ' + fa(r.reach) + ' · ' : '') + (r.likes != null ? 'لایک ' + fa(r.likes) + ' · ' : '') + (r.saves != null ? 'ذخیره ' + fa(r.saves) : '') + '</span>' +
        (r.score != null ? '<span class="cf-q-sub">امتیاز <b style="color:var(--text-h)">' + fa(r.score) + '</b>' + (r.window ? ' · ' + fa(r.window) : '') + '</span>' : '') : '<span class="cf-muted">—</span>';
      var thumb = r.cover ? '<img class="cf-q-thumb" src="' + esc(r.cover) + '" alt="" loading="lazy">' : '<span class="cf-q-thumb"><i class="fa-solid fa-film"></i></span>';
      var busy = r.job && (r.job.status === 'queued' || r.job.status === 'running');
      return '<tr style="' + (r.rejected ? 'opacity:.45' : '') + '">' +
        '<td class="cf-num"><a href="' + r.url + '" style="font-weight:800;color:var(--text-h)">' + esc(r.code) + '</a><span class="cf-q-sub">' + esc(r.format) + '</span></td>' +
        '<td style="white-space:nowrap">' + esc(r.date) + '<span class="cf-q-sub">' + esc(r.weekday) + ' · ' + esc(r.time) + '</span></td>' +
        '<td><a href="' + r.url + '" class="cf-row" style="flex-wrap:nowrap;align-items:flex-start">' + thumb + '<span style="min-width:0;max-width:260px"><b style="color:var(--text-h)">' + esc(r.hook) + '</b></span></a></td>' +
        '<td style="max-width:180px;font-size:12px">' + esc(r.product || '—') + '</td>' +
        '<td style="font-size:12px">' + esc(r.style || '—') + '<span class="cf-q-sub"><i class="fa-solid fa-music"></i> ' + esc(r.music) + '</span></td>' +
        '<td class="cf-num">' + (r.version ? '<span class="cf-pill">v' + fa(r.version) + '</span>' : '<span class="cf-muted">—</span>') + '</td>' +
        '<td><span class="cf-pill ' + (tone[r.status] || '') + '">' + esc(r.status_fa) + '</span>' + job + jmsg + '</td>' +
        '<td style="font-size:12px;white-space:nowrap">' + esc(r.location) + (r.seconds ? ' · ' + fa(r.seconds) + ' ث' : '') + '<span class="cf-q-sub">' + fa(r.cost) + ' دلار</span></td>' +
        '<td style="font-size:12px">' + stats + '</td>' +
        '<td style="white-space:nowrap"><span class="cf-row" style="flex-wrap:nowrap">' +
          '<a class="cf-btn cf-btn-sm" href="' + r.url + '" title="صفحه‌ی کامل پست"><i class="fa-solid fa-pen-to-square"></i></a>' +
          (r.video ? '<a class="cf-btn cf-btn-sm" href="' + esc(r.video) + '" target="_blank" title="دیدن ویدیو"><i class="fa-solid fa-play"></i></a>' : '') +
          (r.prod_url ? '<a class="cf-btn cf-btn-sm" href="' + r.prod_url + '" title="بازبینی و تأیید"><i class="fa-solid fa-clipboard-check"></i></a>' : '') +
          (r.permalink ? '<a class="cf-btn cf-btn-sm" href="' + esc(r.permalink) + '" target="_blank" title="پست اینستاگرام"><i class="fa-brands fa-instagram"></i></a>' : '') +
          (busy || r.rejected ? '' : '<form method="post" action="' + r.build_url + '"><input type="hidden" name="_token" value="' + csrf + '"><button class="cf-btn cf-btn-sm cf-btn-primary" title="' + (r.version ? 'ساخت نسخه‌ی جدید' : 'سفارش ساخت') + '"><i class="fa-solid fa-hammer"></i></button></form>') +
        '</span></td></tr>';
    }).join('') || '<tr><td colspan="10" class="cf-muted" style="text-align:center;padding:30px">موردی پیدا نشد.</td></tr>';
    var pg = ''; for (var i = 1; i <= pages; i++) pg += '<button type="button" data-p="' + i + '" class="' + (i === page ? 'is-active' : '') + '">' + fa(i) + '</button>';
    $('[data-q-pages]').innerHTML = pages > 1 ? pg : '';
  }
  document.addEventListener('click', function (e) {
    var f = e.target.closest('[data-f]'); if (f) { var v = f.getAttribute('data-f'); filter = filter === v ? '' : v; page = 1; render(); return; }
    var p = e.target.closest('[data-p]'); if (p) { page = +p.getAttribute('data-p'); render(); window.scrollTo({top: 0, behavior: 'smooth'}); }
  });
  $('[data-q-search]').addEventListener('input', function (e) { q = e.target.value.trim(); page = 1; render(); });
  $('[data-q-format]').addEventListener('change', function (e) { fmt = e.target.value; page = 1; render(); });
  function poll() {
    fetch(feed, {headers: {Accept: 'application/json'}, credentials: 'same-origin'}).then(function (r) { return r.json(); }).then(function (d) {
      rows = d.rows; render(); $('[data-q-live]').textContent = 'به‌روزرسانی شد ' + d.at;
      var a = $('[data-q-agent]'); a.classList.toggle('is-on', !!d.agent); a.lastChild.textContent = d.agent ? ' ایجنت آنلاین' : ' ایجنت خاموش';
      var busy = rows.some(function (r) { return r.job && (r.job.status === 'queued' || r.job.status === 'running'); });
      setTimeout(poll, busy ? 4000 : 15000);
    }).catch(function () { $('[data-q-live]').textContent = 'قطع اتصال'; setTimeout(poll, 20000); });
  }
  render(); setTimeout(poll, 3000);
})();
</script>
@endsection
