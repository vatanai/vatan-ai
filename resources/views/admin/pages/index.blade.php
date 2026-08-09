@extends('layouts.admin')
@section('title', 'مدیریت صفحات سایت — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="site-pages-heading">
      <div>
        <h1>مدیریت صفحات سایت</h1>
        <p>نمای کلی همه صفحات کاربری، وضعیت اتصال و مسیر مستقیم مدیریت هر صفحه</p>
      </div>
      <a href="{{ route('app.home') }}" target="_blank" rel="noopener" class="btn-pro btn-pro-ghost no-underline">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> مشاهده سایت
      </a>
    </div>

    <section class="site-pages-summary" aria-label="خلاصه وضعیت صفحات">
      <article><span><i class="fa-solid fa-layer-group"></i></span><div><small>کل صفحات</small><strong>{{ $pages->count() }}</strong></div></article>
      <article><span><i class="fa-solid fa-database"></i></span><div><small>متصل به دیتابیس</small><strong>{{ $connectedCount }}</strong></div></article>
      <article><span><i class="fa-solid fa-eye"></i></span><div><small>پیش‌نمایش فعال</small><strong>{{ $pages->whereNotNull('preview_url')->count() }}</strong></div></article>
      <article><span><i class="fa-solid fa-circle-check"></i></span><div><small>منتشرشده</small><strong>{{ $publishedCount }}</strong></div></article>
    </section>

    <section class="content-card site-pages-panel">
      <div class="site-pages-toolbar">
        <div>
          <h2>فهرست صفحات</h2>
          <p>برای ورود به تنظیمات هر صفحه روی «مدیریت صفحه» بزنید.</p>
        </div>
        <div class="site-pages-filters" role="group" aria-label="فیلتر صفحات">
          <label class="site-pages-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="site-pages-search" placeholder="جستجوی صفحه..." autocomplete="off">
          </label>
          <button type="button" class="chip-filter active" data-page-status="all">همه</button>
          <button type="button" class="chip-filter" data-page-status="published">منتشرشده</button>
          <button type="button" class="chip-filter" data-page-status="draft">پیش‌نویس</button>
        </div>
      </div>

      <div class="site-pages-grid" id="site-pages-grid">
        @foreach($pages as $page)
          <article class="site-page-card" data-site-page data-status="{{ $page['status'] }}" data-search="{{ $page['name_fa'] }} {{ $page['name_en'] }} {{ $page['path'] }}">
            <div class="site-page-card-top">
              <span class="site-page-icon"><i class="fa-solid {{ $page['icon'] }}"></i></span>
              <span class="site-page-status is-{{ $page['status'] }}"><i class="fa-solid {{ $page['status'] === 'published' ? 'fa-circle-check' : ($page['status'] === 'scheduled' ? 'fa-clock' : 'fa-pen') }}"></i> {{ $page['status_label'] }}</span>
            </div>
            <div class="site-page-title-row">
              <h3>{{ $page['name_fa'] }}</h3>
              <span dir="ltr">{{ $page['name_en'] }}</span>
            </div>
            <p>{{ $page['description'] }}</p>
            <div class="site-page-meta">
              <span><i class="fa-solid fa-link"></i><b dir="ltr">{{ $page['path'] }}</b></span>
              <span><i class="fa-solid fa-chart-simple"></i><b>{{ $page['metric'] }}</b></span>
            </div>
            <div class="site-page-actions">
              @if($page['manage_url'])
                <a href="{{ $page['manage_url'] }}" class="btn-pro btn-pro-primary no-underline"><i class="fa-solid fa-sliders"></i> تنظیمات صفحه</a>
              @else
                <span class="site-page-soon-action"><i class="fa-solid fa-lock"></i> بزودی</span>
              @endif
              @if($page['preview_url'])
                <a href="{{ $page['preview_url'] }}" target="_blank" rel="noopener" class="btn-pro btn-pro-ghost no-underline" title="پیش‌نمایش صفحه"><i class="fa-regular fa-eye"></i></a>
              @endif
            </div>
          </article>
        @endforeach
      </div>

      <div class="site-pages-empty hidden" id="site-pages-empty"><i class="fa-solid fa-magnifying-glass"></i><span>صفحه‌ای با این فیلتر پیدا نشد.</span></div>
    </section>
  </div>
</main>
@endsection

@push('styles')
<style>
  .site-pages-heading { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px; }
  .site-pages-heading h1 { margin:0; color:var(--text-h); font-size:20px; font-weight:900; }
  .site-pages-heading p { margin:5px 0 0; color:var(--text-soft); font-size:11px; }
  .site-pages-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:18px; }
  .site-pages-summary article { display:flex; align-items:center; gap:10px; min-width:0; padding:13px; border:1px solid var(--border); border-radius:12px; background:var(--card-bg); box-shadow:var(--shadow-card); }
  .site-pages-summary article > span { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; flex:0 0 34px; border-radius:9px; color:var(--primary); background:var(--primary-l); }
  .site-pages-summary small,.site-pages-summary strong { display:block; }
  .site-pages-summary small { color:var(--text-soft); font-size:9px; }
  .site-pages-summary strong { margin-top:3px; color:var(--text-h); font-size:17px; line-height:1; }
  .site-pages-panel { overflow:hidden; }
  .site-pages-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border-bottom:1px solid var(--border); }
  .site-pages-toolbar h2 { margin:0; color:var(--text-h); font-size:13px; font-weight:900; }
  .site-pages-toolbar p { margin:4px 0 0; color:var(--text-soft); font-size:9.5px; }
  .site-pages-filters { display:flex; align-items:center; justify-content:flex-end; gap:6px; flex-wrap:wrap; }
  .site-pages-search { position:relative; display:block; width:210px; }
  .site-pages-search i { position:absolute; top:50%; right:10px; color:var(--text-soft); font-size:10px; transform:translateY(-50%); }
  .site-pages-search input { width:100%; height:32px; padding:0 30px 0 9px; border:1px solid var(--border); border-radius:8px; outline:none; color:var(--text-main); background:var(--input-bg); font-family:inherit; font-size:10px; }
  .site-pages-search input:focus { border-color:var(--primary); background:var(--card-bg); }
  .site-pages-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; padding:14px; }
  .site-page-card { display:flex; min-width:0; min-height:236px; padding:13px; border:1px solid var(--border); border-radius:12px; background:var(--card-bg); flex-direction:column; transition:border-color .16s,transform .16s; }
  .site-page-card:hover { border-color:var(--primary); transform:translateY(-1px); }
  .site-page-card-top,.site-page-title-row,.site-page-actions { display:flex; align-items:center; justify-content:space-between; gap:8px; }
  .site-page-icon { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; color:var(--primary); background:var(--primary-l); font-size:13px; }
  .site-page-status { display:inline-flex; align-items:center; gap:4px; min-height:23px; padding:3px 6px; border:1px solid; border-radius:7px; font-size:8px; font-weight:800; }
  .site-page-status.is-published { color:var(--success); border-color:color-mix(in srgb,var(--success) 30%,transparent); background:color-mix(in srgb,var(--success) 8%,transparent); }
  .site-page-status.is-draft,.site-page-status.is-scheduled,.site-page-status.is-archived { color:var(--warning); border-color:color-mix(in srgb,var(--warning) 30%,transparent); background:color-mix(in srgb,var(--warning) 8%,transparent); }
  .site-page-title-row { margin-top:13px; }
  .site-page-title-row h3 { margin:0; color:var(--text-h); font-size:13px; font-weight:900; }
  .site-page-title-row > span { overflow:hidden; color:var(--text-soft); font-size:9px; text-overflow:ellipsis; white-space:nowrap; }
  .site-page-card > p { min-height:39px; margin:6px 0 10px; color:var(--text-soft); font-size:9.5px; line-height:1.9; }
  .site-page-meta { display:grid; gap:5px; margin-bottom:12px; }
  .site-page-meta span { display:flex; align-items:center; gap:6px; min-width:0; padding:6px 7px; border:1px solid var(--border); border-radius:7px; color:var(--text-soft); background:var(--input-bg); font-size:8.5px; }
  .site-page-meta i { color:var(--primary); }
  .site-page-meta b { overflow:hidden; color:var(--text-main); font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
  .site-page-actions { margin-top:auto; justify-content:flex-start; }
  .site-page-actions .btn-pro { min-height:31px; padding:0 9px; font-size:9px; }
  .site-page-actions .btn-pro:last-child { width:31px; padding:0; justify-content:center; }
  .site-page-soon-action { display:inline-flex; align-items:center; gap:5px; min-height:31px; padding:0 9px; border:1px solid var(--border); border-radius:8px; color:var(--text-soft); background:var(--input-bg); font-size:9px; font-weight:800; }
  .site-pages-empty { display:flex; align-items:center; justify-content:center; gap:7px; min-height:110px; margin:14px; border:1px dashed var(--border); border-radius:10px; color:var(--text-soft); font-size:10px; }
  .site-pages-empty.hidden { display:none; }
  @media (max-width:1100px) { .site-pages-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width:760px) { .site-pages-heading,.site-pages-toolbar { align-items:stretch; flex-direction:column; } .site-pages-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .site-pages-filters { justify-content:flex-start; } .site-pages-search { width:100%; } .site-pages-grid { grid-template-columns:1fr; } }
  @media (max-width:420px) { .site-pages-summary { grid-template-columns:1fr; } }
</style>
@endpush

@section('scripts')
<script>
  (function () {
    const cards = Array.from(document.querySelectorAll('[data-site-page]'));
    const search = document.getElementById('site-pages-search');
    const empty = document.getElementById('site-pages-empty');
    let status = 'all';

    function applyPageFilters() {
      const query = (search?.value || '').trim().toLocaleLowerCase('fa');
      let visible = 0;
      cards.forEach(card => {
        const statusMatches = status === 'all' || card.dataset.status === status;
        const searchMatches = query === '' || (card.dataset.search || '').toLocaleLowerCase('fa').includes(query);
        card.hidden = !(statusMatches && searchMatches);
        if (!card.hidden) visible += 1;
      });
      empty?.classList.toggle('hidden', visible !== 0);
    }

    document.querySelectorAll('[data-page-status]').forEach(button => {
      button.addEventListener('click', () => {
        document.querySelectorAll('[data-page-status]').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        status = button.dataset.pageStatus || 'all';
        applyPageFilters();
      });
    });
    search?.addEventListener('input', applyPageFilters);
  })();
</script>
@endsection
