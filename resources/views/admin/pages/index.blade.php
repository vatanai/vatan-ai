@extends('layouts.admin')
@section('title', 'مدیریت صفحات سایت — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="site-pages-heading">
      <div class="site-pages-heading-copy">
        <span class="site-pages-eyebrow"><i class="fa-solid fa-table-cells-large"></i> مرکز کنترل محتوا</span>
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
          <p><strong id="site-pages-visible-count">{{ $pages->count() }}</strong> صفحه در این نما نمایش داده می‌شود.</p>
        </div>
        <button type="button" class="site-pages-reset" id="site-pages-reset" hidden><i class="fa-solid fa-rotate-left"></i> پاک‌کردن فیلترها</button>
      </div>

      <div class="site-pages-filterbar" aria-label="فیلتر صفحات">
        <div class="site-pages-filter-main">
          <label class="site-pages-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="site-pages-search" placeholder="نام فارسی، انگلیسی یا آدرس صفحه..." autocomplete="off">
            <kbd>⌘ K</kbd>
          </label>
          <label class="site-pages-sort">
            <i class="fa-solid fa-arrow-down-wide-short"></i>
            <select id="site-pages-sort" aria-label="مرتب‌سازی صفحات">
              <option value="default">چیدمان پیش‌فرض</option>
              <option value="name">نام صفحه</option>
              <option value="status">وضعیت انتشار</option>
            </select>
          </label>
        </div>
        <div class="site-pages-filter-groups">
          <div class="site-pages-filter-group" role="group" aria-label="وضعیت انتشار">
            <span>وضعیت</span>
            <div>
              <button type="button" class="site-filter-pill active" data-page-status="all">همه <b>{{ $pages->count() }}</b></button>
              <button type="button" class="site-filter-pill" data-page-status="published">منتشرشده <b>{{ $publishedCount }}</b></button>
              <button type="button" class="site-filter-pill" data-page-status="draft">پیش‌نویس <b>{{ $draftCount }}</b></button>
            </div>
          </div>
          <div class="site-pages-filter-group" role="group" aria-label="امکانات صفحه">
            <span>دسترسی</span>
            <div>
              <button type="button" class="site-filter-pill" data-page-capability="connected"><i class="fa-solid fa-database"></i> متصل به دیتابیس</button>
              <button type="button" class="site-filter-pill" data-page-capability="preview"><i class="fa-regular fa-eye"></i> دارای پیش‌نمایش</button>
            </div>
          </div>
        </div>
      </div>

      <div class="site-pages-grid" id="site-pages-grid">
        @foreach($pages as $page)
          @php
            $__pageColors = ['#a78bfa', '#22d3ee', '#fb7185', '#cffe00', '#fbbf24', '#60a5fa'];
            $__pageAccent = $__pageColors[$loop->index % count($__pageColors)];
          @endphp
          <article class="site-page-card" style="--page-accent:{{ $__pageAccent }}" data-site-page data-order="{{ $loop->index }}" data-name="{{ $page['name_fa'] }}" data-status="{{ $page['status'] }}" data-connected="{{ $page['page'] ? '1' : '0' }}" data-preview="{{ $page['preview_url'] ? '1' : '0' }}" data-search="{{ $page['name_fa'] }} {{ $page['name_en'] }} {{ $page['path'] }}">
            <div class="site-page-card-glow" aria-hidden="true"></div>
            <div class="site-page-card-top">
              <div class="site-page-identity">
                <span class="site-page-icon"><i class="fa-solid {{ $page['icon'] }}"></i></span>
                <span class="site-page-number">صفحه {{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
              </div>
              <span class="site-page-status is-{{ $page['status'] }}"><i class="fa-solid {{ $page['status'] === 'published' ? 'fa-circle-check' : ($page['status'] === 'scheduled' ? 'fa-clock' : 'fa-pen') }}"></i> {{ $page['status_label'] }}</span>
            </div>
            <div class="site-page-title-row">
              <div><h3>{{ $page['name_fa'] }}</h3><span dir="ltr">{{ $page['name_en'] }}</span></div>
              @if($page['page'])
                <span class="site-page-version">نسخه {{ $page['page']->version ?? 1 }}</span>
              @endif
            </div>
            <p>{{ $page['description'] }}</p>
            <div class="site-page-meta">
              <span><small><i class="fa-solid fa-chart-simple"></i> داده فعال</small><b>{{ $page['metric'] }}</b></span>
              <span><small><i class="fa-solid fa-link"></i> مسیر صفحه</small><b dir="ltr">{{ $page['path'] }}</b></span>
            </div>
            <div class="site-page-actions">
              @if($page['manage_url'])
                <a href="{{ $page['manage_url'] }}" class="site-page-manage no-underline"><i class="fa-solid fa-sliders"></i><span>مدیریت و تنظیمات</span><i class="fa-solid fa-arrow-left"></i></a>
              @else
                <span class="site-page-soon-action"><i class="fa-solid fa-lock"></i> بزودی</span>
              @endif
              <div class="site-page-quick-actions">
                @if($page['preview_url'])
                  <button type="button" data-copy-url="{{ $page['preview_url'] }}" title="کپی لینک صفحه" aria-label="کپی لینک {{ $page['name_fa'] }}"><i class="fa-solid fa-link"></i></button>
                  <a href="{{ $page['preview_url'] }}" target="_blank" rel="noopener" class="no-underline" title="پیش‌نمایش صفحه" aria-label="پیش‌نمایش {{ $page['name_fa'] }}"><i class="fa-regular fa-eye"></i></a>
                @endif
              </div>
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
  .site-pages-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:20px}.site-pages-heading-copy{display:flex;flex-direction:column;align-items:flex-start}.site-pages-eyebrow{display:inline-flex;align-items:center;gap:6px;margin-bottom:7px;color:var(--primary);font-size:9.5px;font-weight:800}.site-pages-heading h1{margin:0;color:var(--text-h);font-size:23px;font-weight:950;letter-spacing:-.5px}.site-pages-heading p{margin:6px 0 0;color:var(--text-soft);font-size:11px}
  .site-pages-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px}.site-pages-summary article{position:relative;display:flex;align-items:center;gap:12px;min-width:0;padding:16px;border:1px solid var(--border);border-radius:14px;background:linear-gradient(145deg,var(--card-bg),color-mix(in srgb,var(--input-bg) 62%,var(--card-bg)));box-shadow:var(--shadow-card);overflow:hidden}.site-pages-summary article:after{content:"";position:absolute;right:0;bottom:0;width:72px;height:2px;background:linear-gradient(90deg,var(--primary),transparent)}.site-pages-summary article>span{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;flex:0 0 42px;border:1px solid color-mix(in srgb,var(--primary) 24%,transparent);border-radius:12px;color:var(--primary);background:var(--primary-l);font-size:14px}.site-pages-summary small,.site-pages-summary strong{display:block}.site-pages-summary small{color:var(--text-soft);font-size:9.5px}.site-pages-summary strong{margin-top:4px;color:var(--text-h);font-size:20px;line-height:1}
  .site-pages-panel{overflow:hidden;border-radius:16px}.site-pages-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 18px;border-bottom:1px solid var(--border)}.site-pages-toolbar h2{margin:0;color:var(--text-h);font-size:14px;font-weight:900}.site-pages-toolbar p{margin:4px 0 0;color:var(--text-soft);font-size:9.5px}.site-pages-toolbar p strong{color:var(--primary);font-size:10.5px}.site-pages-reset{height:31px;padding:0 10px;border:1px solid color-mix(in srgb,var(--warning) 24%,var(--border));border-radius:8px;color:var(--warning);background:color-mix(in srgb,var(--warning) 7%,transparent);font-family:inherit;font-size:9px;font-weight:700;cursor:pointer}.site-pages-reset i{margin-left:5px}
  .site-pages-filterbar{padding:14px 18px;border-bottom:1px solid var(--border);background:color-mix(in srgb,var(--input-bg) 68%,transparent)}.site-pages-filter-main{display:grid;grid-template-columns:minmax(260px,1fr) 190px;gap:9px}.site-pages-search,.site-pages-sort{position:relative;display:block}.site-pages-search>i,.site-pages-sort>i{position:absolute;z-index:1;top:50%;right:12px;color:var(--text-soft);font-size:11px;transform:translateY(-50%);pointer-events:none}.site-pages-search input,.site-pages-sort select{width:100%;height:40px;padding:0 35px 0 12px;border:1px solid var(--border);border-radius:10px;outline:none;color:var(--text-main);background:var(--card-bg);font-family:inherit;font-size:10.5px;transition:.18s}.site-pages-search input:focus,.site-pages-sort select:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 8%,transparent)}.site-pages-search kbd{position:absolute;top:50%;left:9px;padding:2px 6px;border:1px solid var(--border);border-radius:5px;color:var(--text-soft);background:var(--input-bg);font:8px sans-serif;transform:translateY(-50%)}.site-pages-sort select{appearance:none;cursor:pointer}.site-pages-filter-groups{display:flex;align-items:flex-end;gap:20px;margin-top:13px;flex-wrap:wrap}.site-pages-filter-group{display:flex;flex-direction:column;gap:7px}.site-pages-filter-group>span{color:var(--text-soft);font-size:8.5px;font-weight:700}.site-pages-filter-group>div{display:flex;gap:6px;flex-wrap:wrap}.site-filter-pill{display:inline-flex;align-items:center;gap:5px;height:30px;padding:0 11px;border:1px solid var(--border);border-radius:8px;color:var(--text-soft);background:var(--card-bg);font-family:inherit;font-size:9.5px;font-weight:700;cursor:pointer;transition:.16s}.site-filter-pill b{display:inline-flex;align-items:center;justify-content:center;min-width:17px;height:17px;padding:0 4px;border-radius:5px;background:var(--input-bg);font-size:8px}.site-filter-pill:hover{border-color:color-mix(in srgb,var(--primary) 45%,var(--border));color:var(--text-h)}.site-filter-pill.active{border-color:color-mix(in srgb,var(--primary) 55%,transparent);color:var(--primary);background:var(--primary-l)}.site-filter-pill.active b{background:color-mix(in srgb,var(--primary) 13%,var(--card-bg))}
  .site-pages-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:18px}.site-page-card{position:relative;display:flex;min-width:0;min-height:310px;padding:18px;border:1px solid var(--border);border-radius:15px;background:linear-gradient(145deg,var(--card-bg) 62%,color-mix(in srgb,var(--page-accent) 3%,var(--card-bg)));flex-direction:column;overflow:hidden;transition:border-color .2s,transform .2s,box-shadow .2s}.site-page-card:before{content:"";position:absolute;top:0;right:18px;left:18px;height:2px;border-radius:0 0 4px 4px;background:linear-gradient(90deg,transparent,var(--page-accent),transparent);opacity:.7}.site-page-card-glow{position:absolute;top:-90px;right:-80px;width:190px;height:190px;border-radius:50%;background:var(--page-accent);filter:blur(85px);opacity:.055;pointer-events:none}.site-page-card:hover{border-color:color-mix(in srgb,var(--page-accent) 45%,var(--border));transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,.08)}.site-page-card-top,.site-page-title-row,.site-page-actions{position:relative;display:flex;align-items:center;justify-content:space-between;gap:10px}.site-page-identity{display:flex;align-items:center;gap:9px}.site-page-icon{display:inline-flex;align-items:center;justify-content:center;width:46px;height:46px;border:1px solid color-mix(in srgb,var(--page-accent) 27%,transparent);border-radius:13px;color:var(--page-accent);background:color-mix(in srgb,var(--page-accent) 10%,var(--card-bg));font-size:16px;box-shadow:inset 0 1px 0 rgba(255,255,255,.05)}.site-page-number{color:var(--text-soft);font-size:8.5px;font-weight:700;letter-spacing:.2px}.site-page-status{display:inline-flex;align-items:center;gap:5px;min-height:26px;padding:3px 8px;border:1px solid;border-radius:8px;font-size:8.5px;font-weight:800}.site-page-status.is-published{color:var(--success);border-color:color-mix(in srgb,var(--success) 30%,transparent);background:color-mix(in srgb,var(--success) 8%,transparent)}.site-page-status.is-draft,.site-page-status.is-scheduled,.site-page-status.is-archived{color:var(--warning);border-color:color-mix(in srgb,var(--warning) 30%,transparent);background:color-mix(in srgb,var(--warning) 8%,transparent)}
  .site-page-title-row{margin-top:17px}.site-page-title-row>div{display:flex;align-items:baseline;gap:8px;min-width:0}.site-page-title-row h3{margin:0;color:var(--text-h);font-size:16px;font-weight:950}.site-page-title-row>div>span{overflow:hidden;color:var(--text-soft);font-size:10px;text-overflow:ellipsis;white-space:nowrap}.site-page-version{flex:none;padding:3px 7px;border:1px solid var(--border);border-radius:6px;color:var(--text-soft);background:var(--input-bg);font-size:8px}.site-page-card>p{position:relative;min-height:42px;margin:7px 0 14px;color:var(--text-soft);font-size:10.5px;line-height:2}.site-page-meta{position:relative;display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px}.site-page-meta span{display:flex;flex-direction:column;gap:6px;min-width:0;padding:9px 10px;border:1px solid var(--border);border-radius:10px;background:var(--input-bg)}.site-page-meta small{display:flex;align-items:center;gap:5px;color:var(--text-soft);font-size:8px}.site-page-meta i{color:var(--page-accent)}.site-page-meta b{overflow:hidden;color:var(--text-main);font-size:9.5px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}.site-page-actions{margin-top:auto;padding-top:14px;border-top:1px solid var(--border);justify-content:space-between}.site-page-manage{display:flex;align-items:center;gap:7px;min-height:38px;padding:0 12px;border:1px solid color-mix(in srgb,var(--page-accent) 38%,transparent);border-radius:10px;color:var(--text-h);background:color-mix(in srgb,var(--page-accent) 9%,var(--card-bg));font-size:9.5px;font-weight:850;transition:.16s}.site-page-manage i:last-child{margin-right:4px;color:var(--page-accent);transition:transform .16s}.site-page-manage:hover{border-color:var(--page-accent)}.site-page-manage:hover i:last-child{transform:translateX(-3px)}.site-page-quick-actions{display:flex;gap:6px}.site-page-quick-actions button,.site-page-quick-actions a{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;padding:0;border:1px solid var(--border);border-radius:10px;color:var(--text-soft);background:var(--input-bg);font-size:11px;cursor:pointer;transition:.16s}.site-page-quick-actions button:hover,.site-page-quick-actions a:hover{border-color:var(--page-accent);color:var(--page-accent)}.site-page-quick-actions button.is-copied{border-color:var(--success);color:var(--success);background:color-mix(in srgb,var(--success) 8%,transparent)}.site-page-soon-action{display:inline-flex;align-items:center;gap:6px;min-height:38px;padding:0 11px;border:1px solid var(--border);border-radius:10px;color:var(--text-soft);background:var(--input-bg);font-size:9px;font-weight:800}.site-pages-empty{display:flex;align-items:center;justify-content:center;gap:8px;min-height:150px;margin:18px;border:1px dashed var(--border);border-radius:12px;color:var(--text-soft);font-size:10.5px}.site-pages-empty.hidden{display:none}
  @media(max-width:1100px){.site-pages-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.site-pages-heading,.site-pages-toolbar{align-items:stretch;flex-direction:column}.site-pages-filter-main{grid-template-columns:1fr}.site-pages-filter-groups{align-items:stretch;flex-direction:column;gap:12px}.site-pages-grid{grid-template-columns:1fr}.site-pages-search kbd{display:none}}@media(max-width:520px){.site-pages-summary{grid-template-columns:1fr}.site-pages-grid{padding:12px}.site-page-card{min-height:300px;padding:15px}.site-page-meta{grid-template-columns:1fr}.site-page-title-row>div{align-items:flex-start;flex-direction:column;gap:2px}}
</style>
@endpush

@section('scripts')
<script>
  (function () {
    const cards = Array.from(document.querySelectorAll('[data-site-page]'));
    const grid = document.getElementById('site-pages-grid');
    const search = document.getElementById('site-pages-search');
    const sort = document.getElementById('site-pages-sort');
    const empty = document.getElementById('site-pages-empty');
    const reset = document.getElementById('site-pages-reset');
    const visibleCount = document.getElementById('site-pages-visible-count');
    const capabilities = new Set();
    let status = 'all';

    function applyPageFilters() {
      const query = (search?.value || '').trim().toLocaleLowerCase('fa');
      let visible = 0;
      cards.forEach(card => {
        const statusMatches = status === 'all'
          || card.dataset.status === status
          || (status === 'draft' && card.dataset.status === 'archived');
        const searchMatches = query === '' || (card.dataset.search || '').toLocaleLowerCase('fa').includes(query);
        const capabilityMatches = Array.from(capabilities).every(capability => card.dataset[capability] === '1');
        card.hidden = !(statusMatches && searchMatches && capabilityMatches);
        if (!card.hidden) visible += 1;
      });
      empty?.classList.toggle('hidden', visible !== 0);
      if (visibleCount) visibleCount.textContent = String(visible).toLocaleString('fa-IR');
      if (reset) reset.hidden = status === 'all' && query === '' && capabilities.size === 0 && (sort?.value || 'default') === 'default';
    }

    function sortCards() {
      const mode = sort?.value || 'default';
      const sorted = [...cards].sort((a, b) => {
        if (mode === 'name') return (a.dataset.name || '').localeCompare(b.dataset.name || '', 'fa');
        if (mode === 'status') return (a.dataset.status || '').localeCompare(b.dataset.status || '', 'fa');
        return Number(a.dataset.order || 0) - Number(b.dataset.order || 0);
      });
      sorted.forEach(card => grid?.appendChild(card));
      applyPageFilters();
    }

    document.querySelectorAll('[data-page-status]').forEach(button => {
      button.addEventListener('click', () => {
        document.querySelectorAll('[data-page-status]').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        status = button.dataset.pageStatus || 'all';
        applyPageFilters();
      });
    });

    document.querySelectorAll('[data-page-capability]').forEach(button => {
      button.addEventListener('click', () => {
        const capability = button.dataset.pageCapability;
        if (!capability) return;
        capabilities.has(capability) ? capabilities.delete(capability) : capabilities.add(capability);
        button.classList.toggle('active', capabilities.has(capability));
        applyPageFilters();
      });
    });

    search?.addEventListener('input', applyPageFilters);
    sort?.addEventListener('change', sortCards);

    reset?.addEventListener('click', () => {
      status = 'all';
      capabilities.clear();
      if (search) search.value = '';
      if (sort) sort.value = 'default';
      document.querySelectorAll('[data-page-status]').forEach(item => item.classList.toggle('active', item.dataset.pageStatus === 'all'));
      document.querySelectorAll('[data-page-capability]').forEach(item => item.classList.remove('active'));
      sortCards();
    });

    document.addEventListener('keydown', event => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        search?.focus();
      }
    });

    document.querySelectorAll('[data-copy-url]').forEach(button => {
      button.addEventListener('click', async () => {
        const url = button.dataset.copyUrl || '';
        if (!url) return;
        try {
          await navigator.clipboard.writeText(url);
          const icon = button.querySelector('i');
          icon?.classList.replace('fa-link', 'fa-check');
          button.classList.add('is-copied');
          button.title = 'لینک کپی شد';
          window.setTimeout(() => {
            icon?.classList.replace('fa-check', 'fa-link');
            button.classList.remove('is-copied');
            button.title = 'کپی لینک صفحه';
          }, 1600);
        } catch (error) {
          window.prompt('لینک صفحه را کپی کنید:', url);
        }
      });
    });
  })();
</script>
@endsection
