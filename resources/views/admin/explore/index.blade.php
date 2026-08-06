@extends('layouts.admin')
@section('title', 'مدیریت اکسپلور — وطن استودیو')

@push('styles')
<style>
  /* درایور راهنمای اکسپلور: عمداً از سمت چپ باز می‌شود (نه راست)، چون سمت راست
     زیر مینی‌سایدبار و سایدبار اصلی پنل قرار می‌گیرد و بخشی از متن پشت آن‌ها پنهان می‌شد.
     این override فقط مخصوص همین یک درایور است (با #id) و کلاس مشترک .drawer-panel
     که در صفحات دیگر (مثل پیش‌نمایش محصول) استفاده می‌شود دست‌نخورده می‌ماند. */
  #explore-guide-panel {
    right: auto;
    left: 0;
    border-left: none;
    border-right: 1px solid var(--border);
    transform: translateX(-100%);
  }
  #explore-guide-panel.open {
    transform: translateX(0);
  }
  .explore-pattern-option { position: relative; display: block; cursor: pointer; }
  .explore-pattern-option > input { position: absolute; opacity: 0; pointer-events: none; }
  .explore-pattern-card {
    height: 100%; padding: 12px; border-radius: 14px;
    border: 1px solid var(--border); background: var(--input-bg);
    transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
  }
  .explore-pattern-option:hover .explore-pattern-card { border-color: var(--primary); }
  .explore-pattern-option > input:checked + .explore-pattern-card {
    border-color: var(--primary); background: var(--primary-l);
    box-shadow: inset 0 0 0 1px var(--primary-m);
  }
  .explore-pattern-preview {
    display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: 6px;
    gap: 2px; direction: ltr; min-height: 70px; padding: 6px; margin-bottom: 10px;
    border-radius: 10px; border: 1px solid var(--border); background: var(--card-bg);
  }
  .explore-pattern-cell { border-radius: 2px; background: var(--border); }
  .explore-pattern-cell:not(.size-1x1) { background: var(--primary); }
  .explore-pattern-choice { width: 16px; height: 16px; border-radius: 50%; border: 1px solid var(--border); }
  .explore-pattern-option > input:checked + .explore-pattern-card .explore-pattern-choice {
    border: 4px solid var(--primary); background: var(--card-bg);
  }
  .explore-audience-box { padding:14px; border:1px solid var(--border); border-radius:14px; background:var(--input-bg); }
  .explore-audience-box.is-include { box-shadow:inset 3px 0 0 var(--primary); }
  .explore-audience-box.is-exclude { box-shadow:inset 3px 0 0 var(--danger); }
  .explore-audience-box.is-include > div:first-child i { color:var(--primary); }
  .explore-audience-box.is-exclude > div:first-child i { color:var(--danger); }
  .explore-filter-state { padding:3px 8px; border-radius:999px; white-space:nowrap; font-size:9.5px; font-weight:800; color:var(--text-soft); border:1px solid var(--border); background:var(--card-bg); }
  .explore-filter-chip { cursor:pointer; }
  .explore-filter-chip input { position:absolute; opacity:0; pointer-events:none; }
  .explore-filter-chip span { display:block; padding:6px 10px; border:1px solid var(--border); border-radius:9px; background:var(--card-bg); color:var(--text-soft); font-size:10.5px; font-weight:700; transition:.2s ease; }
  .explore-audience-box.is-include .explore-filter-chip input:checked + span { color:var(--primary); border-color:var(--primary); background:var(--primary-l); }
  .explore-audience-box.is-exclude .explore-filter-chip input:checked + span { color:var(--danger); border-color:var(--danger); background:var(--danger-l); }
  .explore-filter-details { margin-top:8px; border:1px solid var(--border); border-radius:10px; background:var(--card-bg); }
  .explore-filter-details summary { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:9px 10px; cursor:pointer; list-style:none; color:var(--text-main); font-size:10.5px; font-weight:700; }
  .explore-filter-details summary::-webkit-details-marker { display:none; }
  .explore-filter-count { min-width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; border-radius:7px; background:var(--input-bg); color:var(--text-soft); font-size:9.5px; }
  .explore-filter-dropdown { padding:0 9px 9px; border-top:1px solid var(--border); padding-top:9px; }
  .explore-filter-list { max-height:180px; overflow-y:auto; display:grid; gap:4px; }
  .explore-filter-list label { display:flex; align-items:center; gap:8px; min-height:31px; padding:5px 7px; border-radius:7px; color:var(--text-soft); font-size:10.5px; cursor:pointer; }
  .explore-filter-list label:hover { background:var(--primary-l); color:var(--text-main); }
  .explore-filter-list input { accent-color:var(--primary); }
  .explore-audience-box.is-exclude .explore-filter-list input { accent-color:var(--danger); }
  .explore-filter-list small { margin-right:auto; padding:2px 5px; border-radius:5px; background:var(--primary-l); color:var(--primary); font-size:8.5px; }
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">

    @php
      $styleLabels = collect($patterns)->mapWithKeys(fn ($pattern, $key) => [$key => $pattern['label']])->all();
    @endphp

    @if(session('success'))
      <div class="mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--success-l);color:var(--success);border:1px solid var(--success-m);">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--danger-l);color:var(--danger);border:1px solid var(--danger-m);">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
      </div>
    @endif

    {{-- ── سربرگ صفحه ── --}}
    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">مدیریت اکسپلور</div>
        <div class="text-[13px]" style="color:var(--text-soft);">کنترل کامل موتور فید هوشمند: سبک چیدمان کاشی‌ها، کمپین‌ها، آیتم‌های سنجاق‌شده و بوست دستی محصولات</div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" class="btn-pro btn-pro-ghost" onclick="openExploreGuide()">
          <i class="fa-solid fa-circle-question text-[11px]"></i> راهنمای استفاده از این بخش
        </button>
        <a href="{{ route('app.explore') }}" target="_blank" class="btn-pro btn-pro-ghost">
          <i class="fa-solid fa-arrow-up-left-from-circle text-[11px]"></i> مشاهده صفحه‌ی زنده
        </a>
      </div>
    </div>

    {{-- ══════════════ کارت‌های آماری کوتاه ══════════════ --}}
    <div class="grid grid-cols-4 max-[900px]:grid-cols-2 max-[480px]:grid-cols-1 gap-3 mb-5">
      <div class="stat-card">
        <div class="stat-card-icon" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-table-cells-large"></i></div>
        <div><div class="stat-card-value">{{ $styleLabels[$effectiveLayoutStyle] }}</div><div class="stat-card-label">سبک فعلی چیدمان</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="background:var(--info-l);color:var(--info);"><i class="fa-solid fa-shuffle"></i></div>
        <div><div class="stat-card-value">٪{{ $setting->randomness_level }}</div><div class="stat-card-label">سطح تصادفی‌بودن</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="background:var(--info-l);color:var(--info);"><i class="fa-solid fa-bullhorn"></i></div>
        <div><div class="stat-card-value">{{ $campaigns->where('is_active', true)->count() }}</div><div class="stat-card-label">کمپین فعال</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="background:var(--warning-l);color:var(--warning);"><i class="fa-solid fa-thumbtack"></i></div>
        <div><div class="stat-card-value">{{ $pins->count() }}</div><div class="stat-card-label">آیتم سنجاق‌شده</div></div>
      </div>
    </div>

    {{-- ══════════════ ۱. تنظیمات نمایش ══════════════ --}}
    <div class="content-card p-5 mb-5">
      <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-sliders" style="color:var(--primary);"></i> تنظیمات نمایش و چیدمان</div>
      <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">چهار معماری ذخیره‌شونده؛ هر انتخاب در موبایل، تبلت و دسکتاپ با چرخه‌ی متناسب همان نمایشگر تکرار می‌شود</div>

      <form method="POST" action="{{ route('admin.explore.settings.update') }}" id="explore-settings-form">
        @csrf

        <div class="grid grid-cols-4 max-[1100px]:grid-cols-2 max-[620px]:grid-cols-1 gap-3 mb-4">
          @foreach($patterns as $key => $pattern)
            @php
              $occupied = [];
              $previewSlots = [];
              $dimensions = ['size-1x1' => [1, 1], 'size-wide' => [2, 1], 'size-tall' => [1, 2], 'size-big' => [2, 2]];
              foreach ($pattern['anchors'] as $anchor) {
                  [$size, $row, $col] = $anchor;
                  $previewSlots[] = $anchor;
                  [$width, $height] = $dimensions[$size];
                  for ($r = $row; $r < $row + $height; $r++) for ($c = $col; $c < $col + $width; $c++) $occupied[$r.':'.$c] = true;
              }
              for ($r = 1; $r <= $pattern['rows']; $r++) for ($c = 1; $c <= 3; $c++) if (!isset($occupied[$r.':'.$c])) $previewSlots[] = ['size-1x1', $r, $c];
            @endphp
            <label class="explore-pattern-option">
              <input type="radio" name="layout_style" value="{{ $key }}" {{ $effectiveLayoutStyle === $key ? 'checked' : '' }}>
              <span class="explore-pattern-card block">
                <span class="explore-pattern-preview" style="grid-template-rows:repeat({{ $pattern['rows'] }}, 6px);">
                  @foreach($previewSlots as $slot)
                    @php [$size, $row, $col] = $slot; [$width, $height] = $dimensions[$size]; @endphp
                    <span class="explore-pattern-cell {{ $size }}" style="grid-column:{{ $col }} / span {{ $width }};grid-row:{{ $row }} / span {{ $height }};"></span>
                  @endforeach
                </span>
                <span class="flex items-start justify-between gap-2">
                  <span>
                    <span class="block text-[12.5px] font-extrabold" style="color:var(--text-h);">{{ $pattern['label'] }}</span>
                    <span class="block text-[10.5px] mt-1 leading-5" style="color:var(--text-soft);">{{ $pattern['description'] }}</span>
                  </span>
                  <span class="explore-pattern-choice shrink-0 mt-0.5"></span>
                </span>
              </span>
            </label>
          @endforeach
        </div>

        <div class="pt-4 mt-4 mb-4" style="border-top:1px solid var(--border);">
          <div class="text-[13px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-filter" style="color:var(--primary);"></i> فیلتر محصولات قابل نمایش</div>
          <div class="text-[10.5px] mb-3" style="color:var(--text-soft);">اگر هیچ گزینه‌ای در بخش ورودی انتخاب نشود، همه محصولات فعال مجاز هستند؛ قوانین سمت حذف همیشه اولویت دارند.</div>
          <div class="grid grid-cols-2 max-[900px]:grid-cols-1 gap-3">
            @include('admin.explore.partials.audience-filter', ['mode' => 'include'])
            @include('admin.explore.partials.audience-filter', ['mode' => 'exclude'])
          </div>
        </div>

        <div class="grid grid-cols-2 max-[768px]:grid-cols-1 gap-4 mb-4">
          <div>
            <label class="text-[11px] font-bold flex items-center justify-between mb-1.5" style="color:var(--text-soft);">
              <span>سطح تصادفی‌بودن ترتیب</span>
              <span id="randomness-value" style="color:var(--primary);">{{ $setting->randomness_level }}٪</span>
            </label>
            <input type="range" name="randomness_level" min="0" max="100" value="{{ $setting->randomness_level }}" style="width:100%; accent-color:var(--primary);" oninput="document.getElementById('randomness-value').textContent = this.value + '٪'">
          </div>
          <div>
            <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">سهم اسلات‌های کمپین از فید (٪)</label>
            <input type="number" name="campaign_ratio" min="0" max="100" class="input-pro w-full" value="{{ $setting->campaign_ratio }}">
          </div>
        </div>

        <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره تنظیمات</button>
      </form>
    </div>

    {{-- ══════════════ ۲. کمپین‌ها ══════════════ --}}
    <div class="content-card p-5 mb-5">
      <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-bullhorn" style="color:var(--primary);"></i> کمپین‌ها و بنرها</div>
      <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">کمپین‌ها با وزن دلخواه و بازه‌ی زمانی، بین کاشی‌های عادی فید تزریق می‌شوند</div>

      <form method="POST" action="{{ route('admin.explore.campaigns.store') }}" enctype="multipart/form-data" class="grid grid-cols-6 max-[900px]:grid-cols-2 gap-3 mb-5 items-end">
        @csrf
        <div class="max-[900px]:col-span-2">
          <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">عنوان کمپین</label>
          <input type="text" name="title_fa" required class="input-pro w-full" placeholder="مثلاً تخفیف ویژه نوروز">
        </div>
        <div>
          <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">تصویر</label>
          <input type="file" name="image" required accept="image/*" class="input-pro w-full" style="padding-top:7px;">
        </div>
        <div>
          <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">لینک مقصد</label>
          <input type="text" name="link" class="input-pro w-full" placeholder="/app/product/...">
        </div>
        <div>
          <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">وزن (۱-۱۰۰)</label>
          <input type="number" name="weight" min="1" max="100" value="50" class="input-pro w-full">
        </div>
        <div>
          <button type="submit" class="btn-pro btn-pro-primary w-full justify-center"><i class="fa-solid fa-plus text-[11px]"></i> افزودن</button>
        </div>
        <div class="max-[900px]:col-span-2">
          <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">شروع (اختیاری)</label>
          <input type="datetime-local" name="start_at" class="input-pro w-full">
        </div>
        <div class="max-[900px]:col-span-2">
          <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">پایان (اختیاری)</label>
          <input type="datetime-local" name="end_at" class="input-pro w-full">
        </div>
      </form>

      <div class="overflow-x-auto">
        <table class="table-pro">
          <thead>
            <tr>
              <th>تصویر</th><th>عنوان</th><th>وزن</th><th>بازه</th><th>وضعیت</th><th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($campaigns as $campaign)
              <tr>
                <td><div class="table-thumb">
                  <img src="{{ $campaign->image ? asset('storage/'.$campaign->image) : asset('assets/img/placeholder.webp') }}" alt="">
                </div></td>
                <td>{{ $campaign->title_fa }}</td>
                <td>{{ $campaign->weight }}</td>
                <td class="text-[11px]" style="color:var(--text-soft);">
                  {{ $campaign->start_at?->format('Y/m/d') ?? '—' }} تا {{ $campaign->end_at?->format('Y/m/d') ?? '—' }}
                </td>
                <td>
                  <form method="POST" action="{{ route('admin.explore.campaigns.toggle', $campaign) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="badge-pro is-clickable {{ $campaign->is_active ? 'badge-success' : 'badge-neutral' }}">
                      <i class="fa-solid fa-circle"></i> {{ $campaign->is_active ? 'فعال' : 'غیرفعال' }}
                    </button>
                  </form>
                </td>
                <td>
                  <form method="POST" action="{{ route('admin.explore.campaigns.destroy', $campaign) }}" onsubmit="return confirm('حذف این کمپین؟');">
                    @csrf @method('DELETE')
                    <button type="submit" class="icon-action-btn danger"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">هنوز کمپینی ثبت نشده</div></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-2 max-[900px]:grid-cols-1 gap-5">

      {{-- ══════════════ ۳. آیتم‌های سنجاق‌شده ══════════════ --}}
      <div class="content-card p-5">
        <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-thumbtack" style="color:var(--primary);"></i> آیتم‌های سنجاق‌شده</div>
        <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">محصول در موقعیت ثابتی از فید نمایش داده می‌شود، مستقل از رندوم</div>

        <form method="POST" action="{{ route('admin.explore.pins.store') }}" class="flex gap-2 mb-4 flex-wrap">
          @csrf
          <select name="product_id" required class="input-pro flex-1" style="min-width:160px;">
            <option value="">انتخاب محصول...</option>
            @foreach($products as $product)
              <option value="{{ $product->id }}">{{ $product->name_fa }}</option>
            @endforeach
          </select>
          <input type="number" name="position" min="1" max="100" placeholder="موقعیت" required class="input-pro" style="width:90px;">
          <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-thumbtack text-[11px]"></i></button>
        </form>

        <div class="space-y-2">
          @forelse($pins as $pin)
            <div class="flex items-center justify-between gap-2 p-2.5 rounded-xl" style="border:1px solid var(--border);">
              <div class="flex items-center gap-2 min-w-0">
                <span class="badge-pro badge-primary">#{{ $pin->position }}</span>
                <span class="text-[12px] font-semibold truncate" style="color:var(--text-h);">
                  {{ $pin->contentItem?->content?->name_fa ?? $pin->contentItem?->content?->title_fa ?? 'آیتم حذف‌شده' }}
                </span>
              </div>
              <form method="POST" action="{{ route('admin.explore.pins.destroy', $pin) }}">
                @csrf @method('DELETE')
                <button type="submit" class="icon-action-btn danger"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </div>
          @empty
            <div class="empty-state"><div class="empty-state-title text-[12px]">آیتم سنجاق‌شده‌ای وجود ندارد</div></div>
          @endforelse
        </div>
      </div>

      {{-- ══════════════ ۴. بوست دستی محصولات ══════════════ --}}
      <div class="content-card p-5">
        <div class="text-[14px] font-extrabold mb-1" style="color:var(--text-h);"><i class="fa-solid fa-rocket" style="color:var(--primary);"></i> بوست دستی محصولات</div>
        <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">امتیاز بالاتر یعنی شانس بیشتر برای دیده‌شدن زودتر در فید (نه موقعیت ثابت)</div>

        <form method="POST" action="{{ route('admin.explore.boost.update') }}" class="flex gap-2 mb-4 flex-wrap">
          @csrf
          <select name="product_id" required class="input-pro flex-1" style="min-width:160px;">
            <option value="">انتخاب محصول...</option>
            @foreach($products as $product)
              <option value="{{ $product->id }}">{{ $product->name_fa }}</option>
            @endforeach
          </select>
          <input type="number" name="manual_boost" min="0" max="100" placeholder="امتیاز" required class="input-pro" style="width:90px;">
          <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-rocket text-[11px]"></i></button>
        </form>

        <div class="space-y-2">
          @forelse($boostedItems as $score)
            <div class="flex items-center justify-between gap-2 p-2.5 rounded-xl" style="border:1px solid var(--border);">
              <span class="text-[12px] font-semibold truncate" style="color:var(--text-h);">
                {{ $score->contentItem?->content?->name_fa ?? 'آیتم حذف‌شده' }}
              </span>
              <span class="badge-pro badge-info">+{{ $score->manual_boost }}</span>
            </div>
          @empty
            <div class="empty-state"><div class="empty-state-title text-[12px]">هنوز بوستی ثبت نشده</div></div>
          @endforelse
        </div>
      </div>

    </div>

  </div>
</main>

{{-- ══════════════ درایور راهنمای استفاده ══════════════ --}}
<div class="drawer-overlay" id="explore-guide-overlay" onclick="closeExploreGuide()"></div>
<div class="drawer-panel" id="explore-guide-panel">

  <div class="drawer-section flex items-center justify-between" style="position:sticky; top:0; background:var(--card-bg); z-index:2;">
    <div class="text-[14px] font-extrabold" style="color:var(--text-h);"><i class="fa-solid fa-circle-question" style="color:var(--primary);"></i> راهنمای بخش مدیریت اکسپلور</div>
    <button type="button" class="icon-action-btn" onclick="closeExploreGuide()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="drawer-section">
    <div class="drawer-label">این بخش چیست و چرا وجود دارد</div>
    <div class="drawer-value" style="font-weight:400; line-height:2;">
      صفحه‌ی «اکسپلور» در اپلیکیشن (همان صفحه‌ای که کاربر با زدن دکمه‌ی «مشاهده صفحه‌ی زنده» می‌بیند) یک گرید از کاشی‌های محصولات است که اندازه‌شان (کوچک، عریض، بلند، بزرگ) و ترتیبشان به‌صورت هوشمند و تا حدی تصادفی تعیین می‌شود؛ هدف این است که هر بار کاربر سر بزند، چیدمان کمی متفاوت و جذاب‌تر از یک لیست ثابت و یکنواخت ببیند.
      <br><br>
      قبل از این بخش، این چیدمان و محتوا فقط با تغییر کد توسط برنامه‌نویس قابل تغییر بود. با «مدیریت اکسپلور» شما به‌عنوان مدیر سایت، بدون نیاز به هیچ دانش فنی، می‌توانید نحوه‌ی نمایش، محصولات ویژه، کمپین‌های تبلیغاتی و ترتیب نمایش را مستقیماً از همین صفحه کنترل کنید. هر تغییری که اینجا ذخیره کنید، بلافاصله روی صفحه‌ی زنده اعمال می‌شود.
      <br><br>
      این بخش کاملاً مستقل ساخته شده و هیچ تأثیری روی محصولات، سفارشات، کاربران یا هر بخش دیگر پنل ندارد؛ فقط نحوه‌ی «نمایش» محصولات در صفحه‌ی اکسپلور را کنترل می‌کند.
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label">۱. تنظیمات نمایش و چیدمان</div>
    <div class="drawer-value" style="font-weight:400; line-height:2;">
      یکی از چهار معماری تصویری را انتخاب کنید. «الگوی اکسل ۱۱ ردیفی» همان نمونه‌ی تأییدشده است و سه مدل «متعادل»، «عمودی» و «بنری» نمونه‌های جایگزین هستند. هر الگو در موبایل، تبلت و دسکتاپ چرخه‌ی مخصوص عرض همان دستگاه را دارد و بعد از پایان چرخه دوباره از ابتدا تکرار می‌شود.
      <br><br>
      «سطح تصادفی‌بودن» تعیین می‌کند ترتیب محصولات چقدر در هر بازدید عوض شود: عدد بالاتر یعنی تنوع و جابه‌جایی بیشتر بین بازدیدها؛ عدد پایین‌تر یعنی محصولات پرطرفدار/تازه‌تر ثابت‌تر در بالای صفحه می‌مانند.
      <br><br>
      «سهم اسلات‌های کمپین» یعنی چند درصد از کل کاشی‌های صفحه به کمپین‌ها و بنرهای تبلیغاتی اختصاص پیدا کند (مثلاً عدد ۵ یعنی از هر ۲۰ کاشی، یکی کمپین باشد).
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label">۲. کمپین‌ها و بنرها</div>
    <div class="drawer-value" style="font-weight:400; line-height:2;">
      برای تبلیغ یک تخفیف، یک مناسبت (مثل نوروز یا یلدا) یا معرفی یک محصول خاص، از این بخش کمپین بسازید: عنوان، تصویر، لینک مقصد (جایی که با کلیک روی کمپین کاربر به آنجا می‌رود)، و «وزن» (هرچه بیشتر باشد، شانس بیشتری برای دیده‌شدن دارد) را وارد کنید. بازه‌ی زمانی شروع و پایان اختیاری است؛ اگر خالی بگذارید، کمپین همیشه فعال می‌ماند.
      <br><br>
      هر زمان بخواهید می‌توانید با کلیک روی بج «فعال/غیرفعال» کنار هر کمپین، آن را موقتاً خاموش یا روشن کنید، یا با دکمه‌ی سطل زباله کاملاً حذفش کنید.
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label">۳. آیتم‌های سنجاق‌شده (Pin)</div>
    <div class="drawer-value" style="font-weight:400; line-height:2;">
      اگر می‌خواهید یک محصول خاص همیشه در یک موقعیت مشخص از صفحه (مثلاً همیشه اولین کاشی) دیده شود، محصول را از لیست انتخاب کنید و شماره‌ی موقعیت را وارد کنید (عدد ۱ یعنی اولین کاشی). این محصول دیگر تحت‌تأثیر تصادفی‌بودن یا امتیاز قرار نمی‌گیرد و دقیقاً همان‌جا می‌ماند، تا وقتی خودتان از لیست حذفش کنید.
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label">۴. بوست دستی محصولات</div>
    <div class="drawer-value" style="font-weight:400; line-height:2;">
      اگر نمی‌خواهید موقعیت محصول را کاملاً ثابت کنید، اما دوست دارید شانس بیشتری برای بالاتر دیده‌شدن داشته باشد، از این بخش به آن محصول امتیاز «بوست» بین ۰ تا ۱۰۰ بدهید. هرچه امتیاز بالاتر باشد، احتمال بیشتری دارد زودتر در صفحه ظاهر شود، اما همچنان در ترکیب تصادفی و متنوع صفحه باقی می‌ماند (برخلاف Pin که موقعیتش کاملاً ثابت است).
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label">نکته‌ی پایانی</div>
    <div class="drawer-value" style="font-weight:400; line-height:2;">
      بعد از هر تغییر، برای دیدن نتیجه‌ی واقعی کافی است دکمه‌ی «مشاهده صفحه‌ی زنده» را بزنید. چون سطح تصادفی‌بودن فعال است، ممکن است هر بار رفرش صفحه، چیدمان کمی فرق کند — این طبیعی و دقیقاً همان چیزی است که برای جذابیت صفحه تنظیم شده است.
    </div>
  </div>

</div>

<script>
  document.querySelectorAll('[data-filter-group]').forEach(function (group) {
    var search = group.querySelector('[data-filter-search]');
    var items = Array.from(group.querySelectorAll('[data-filter-item]'));
    var counter = group.querySelector('[data-filter-count]');
    var syncCount = function () {
      if (counter) counter.textContent = items.filter(function (item) {
        return item.querySelector('input[type="checkbox"]')?.checked;
      }).length;
    };
    search?.addEventListener('input', function () {
      var query = this.value.trim().toLocaleLowerCase('fa');
      items.forEach(function (item) {
        item.style.display = !query || (item.dataset.filterText || '').includes(query) ? '' : 'none';
      });
    });
    items.forEach(function (item) {
      item.querySelector('input[type="checkbox"]')?.addEventListener('change', syncCount);
    });
    syncCount();
  });

  function openExploreGuide() {
    document.getElementById('explore-guide-overlay').classList.add('open');
    document.getElementById('explore-guide-panel').classList.add('open');
  }
  function closeExploreGuide() {
    document.getElementById('explore-guide-overlay').classList.remove('open');
    document.getElementById('explore-guide-panel').classList.remove('open');
  }
</script>
@endsection
