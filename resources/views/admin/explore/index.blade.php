@extends('layouts.admin')
@section('title', 'مدیریت اکسپلور — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">

    @php
      $styleLabels = ['classic' => 'کلاسیک متعادل', 'dense' => 'فشرده (بیشتر تک‌کاشی)', 'magazine' => 'مجله‌ای (بلوک‌های بزرگ)', 'custom' => 'سفارشی'];
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
      <a href="{{ route('app.explore') }}" target="_blank" class="btn-pro btn-pro-ghost">
        <i class="fa-solid fa-arrow-up-left-from-circle text-[11px]"></i> مشاهده صفحه‌ی زنده
      </a>
    </div>

    {{-- ══════════════ کارت‌های آماری کوتاه ══════════════ --}}
    <div class="grid grid-cols-4 max-[900px]:grid-cols-2 max-[480px]:grid-cols-1 gap-3 mb-5">
      <div class="stat-card">
        <div class="stat-card-icon" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-table-cells-large"></i></div>
        <div><div class="stat-card-value">{{ $styleLabels[$setting->layout_style] ?? $setting->layout_style }}</div><div class="stat-card-label">سبک فعلی چیدمان</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="background:var(--info-l);color:var(--info);"><i class="fa-solid fa-shuffle"></i></div>
        <div><div class="stat-card-value">٪{{ $setting->randomness_level }}</div><div class="stat-card-label">سطح تصادفی‌بودن</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon" style="background:#a07af51a;color:#a07af5;"><i class="fa-solid fa-bullhorn"></i></div>
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
      <div class="text-[11.5px] mb-4" style="color:var(--text-soft);">چند مدل سبک نمایش آماده — یا سبک سفارشی با وزن دلخواه هر اندازه کاشی</div>

      <form method="POST" action="{{ route('admin.explore.settings.update') }}" id="explore-settings-form">
        @csrf

        <div class="grid grid-cols-3 max-[768px]:grid-cols-1 gap-3 mb-4">
          @php
            $styleLabels = ['classic' => 'کلاسیک متعادل', 'dense' => 'فشرده (بیشتر تک‌کاشی)', 'magazine' => 'مجله‌ای (بلوک‌های بزرگ)', 'custom' => 'سفارشی'];
          @endphp
          @foreach(array_merge($presets, ['custom' => []]) as $key => $weights)
            <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer" style="border:1px solid var(--border); background:var(--input-bg);">
              <input type="radio" name="layout_style" value="{{ $key }}" class="explore-style-radio" style="accent-color:var(--primary);" {{ $setting->layout_style === $key ? 'checked' : '' }}>
              <span class="text-[12.5px] font-bold" style="color:var(--text-h);">{{ $styleLabels[$key] }}</span>
            </label>
          @endforeach
        </div>

        <div id="explore-custom-weights" class="grid grid-cols-4 max-[768px]:grid-cols-2 gap-3 mb-4" style="{{ $setting->layout_style === 'custom' ? '' : 'display:none;' }}">
          <div>
            <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">۱×۱ (٪)</label>
            <input type="number" name="tile_1x1" min="0" max="100" class="input-pro w-full" value="{{ $setting->tile_weights['size-1x1'] ?? 64 }}">
          </div>
          <div>
            <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">عریض ۲×۱ (٪)</label>
            <input type="number" name="tile_wide" min="0" max="100" class="input-pro w-full" value="{{ $setting->tile_weights['size-wide'] ?? 14 }}">
          </div>
          <div>
            <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">بلند ۱×۲ (٪)</label>
            <input type="number" name="tile_tall" min="0" max="100" class="input-pro w-full" value="{{ $setting->tile_weights['size-tall'] ?? 14 }}">
          </div>
          <div>
            <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">بزرگ ۲×۲ (٪)</label>
            <input type="number" name="tile_big" min="0" max="100" class="input-pro w-full" value="{{ $setting->tile_weights['size-big'] ?? 8 }}">
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

<script>
  document.querySelectorAll('.explore-style-radio').forEach(function (radio) {
    radio.addEventListener('change', function () {
      document.getElementById('explore-custom-weights').style.display = (this.value === 'custom') ? '' : 'none';
    });
  });
</script>
@endsection
