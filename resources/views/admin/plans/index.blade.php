@extends('layouts.admin')
@section('title', 'پلن‌بیلدر — وطن استودیو')
@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/plan-builder.css') }}?v={{ filemtime(public_path('admin/css/plan-builder.css')) }}">
@endpush

@section('content')
<main class="flex-1 min-h-screen flex flex-col min-w-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto pb-page" id="content">
    @if(session('success'))<div class="pb-notice pb-notice-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="pb-notice pb-notice-danger">{{ session('error') }}</div>@endif

    <section class="pb-toolbar">
      <div>
        <h1 class="pb-title">پلن‌بیلدر فروش</h1>
        <p class="pb-subtitle">ساخت، انتشار و مدیریت قیمت و امکانات برای کاربران عادی و مشتریان ثابت</p>
      </div>
      <div class="pb-actions">
        <a href="{{ route('pricing.index') }}" target="_blank" class="pb-btn"><i class="fa-solid fa-eye"></i> پیش‌نمایش کاربران</a>
        <a href="{{ route('pricing.index', ['audience' => 'loyal']) }}" target="_blank" class="pb-btn"><i class="fa-solid fa-crown"></i> پیش‌نمایش مشتری ثابت</a>
        <a href="{{ route('admin.plans.create') }}" class="pb-btn pb-btn-primary"><i class="fa-solid fa-plus"></i> پلن جدید</a>
      </div>
    </section>

    <section class="pb-stats">
      <article class="pb-stat"><b>{{ number_format($stats['total']) }}</b><span>کل پلن‌ها</span></article>
      <article class="pb-stat"><b>{{ number_format($stats['active']) }}</b><span>پلن فعال و قابل نمایش</span></article>
      <article class="pb-stat"><b>{{ number_format($stats['draft']) }}</b><span>پیش‌نویس</span></article>
      <article class="pb-stat"><b>{{ number_format($stats['purchases']) }}</b><span>خرید ثبت‌شده</span></article>
    </section>

    <section class="pb-card pb-home-pricing">
      <div class="pb-card-head pb-home-pricing__head">
        <div>
          <h3>استایل فعال صفحه نخست</h3>
          <p>این همان چیدمان فعلی کارت‌های صفحه نخست است؛ متن نشان‌ها، قیمت، اعتبار، آمار و امکانات از اطلاعات پلن خوانده می‌شود.</p>
        </div>
        <span class="pb-badge pb-badge-success">فعال · {{ $homePricing['active_template'] ?? 'vatan-proposal' }}</span>
      </div>
      <div class="pb-home-pricing__intro">
        <b>{{ $homePricing['title'] ?? 'پلن‌ها، بر پایه اعتبار دائمی' }}</b>
        @foreach(($homePricing['notes'] ?? []) as $note)<span>{{ $note }}</span>@endforeach
      </div>
      <div class="pb-home-pricing__plans">
        @forelse($homePricingPlans as $homePlan)
          @php
            $config = is_array($homePlan->home_pricing_config) ? $homePlan->home_pricing_config : [];
            $price = (array) ($config['price'] ?? []);
            $primaryStat = (array) ($config['primary_stat'] ?? []);
            $secondaryStat = (array) ($config['secondary_stat'] ?? []);
          @endphp
          <article class="pb-home-pricing__plan {{ ($config['variant'] ?? '') === 'professional' ? 'is-featured' : '' }}">
            <div class="pb-home-pricing__plan-head">
              <span class="pb-icon"><i class="{{ $config['icon'] ?? $homePlan->icon }}"></i></span>
              <div><b>{{ $homePlan->name }}</b><small>{{ $config['eyebrow'] ?? $homePlan->short_description }}</small></div>
              <a class="pb-btn pb-btn-sm" href="{{ route('admin.plans.edit', $homePlan) }}" title="ویرایش پلن"><i class="fa-solid fa-pen"></i></a>
            </div>
            <dl class="pb-home-pricing__copy">
              <div><dt>استایل انتخاب‌شده</dt><dd>{{ $homePlan->card_style }}</dd></div>
              @if(filled($config['ribbon'] ?? null))<div><dt>نشان اصلی</dt><dd>{{ $config['ribbon'] }}</dd></div>@endif
              @if(filled($config['badge'] ?? null))<div><dt>باکس تخفیف / وضعیت</dt><dd><bdi dir="rtl">{{ $config['badge'] }}</bdi></dd></div>@endif
              <div><dt>قیمت باکس</dt><dd>{{ trim(implode(' ', array_filter([$price['prefix'] ?? null, $price['value'] ?? null, $price['suffix'] ?? null]))) }}</dd></div>
              <div><dt>اعتبار</dt><dd>{{ $config['credit'] ?? number_format($homePlan->tokens) . ' اعتبار' }}</dd></div>
              <div><dt>{{ $primaryStat['label'] ?? 'قیمت هر اعتبار' }}</dt><dd>{{ $primaryStat['value'] ?? '—' }}</dd></div>
              <div><dt>{{ $secondaryStat['label'] ?? 'صرفه‌جویی' }}</dt><dd>{{ $secondaryStat['value'] ?? '—' }}</dd></div>
            </dl>
            <ul>
              @foreach((array) $homePlan->features as $feature)
                @php $featureTitle = is_array($feature) ? ($feature['title'] ?? '') : $feature; @endphp
                @if(filled($featureTitle))<li>{{ $featureTitle }}</li>@endif
              @endforeach
            </ul>
          </article>
        @empty
          <p class="pb-empty">هنوز هیچ استایل فعالی برای صفحه نخست ثبت نشده است.</p>
        @endforelse
      </div>
      <p class="pb-home-pricing__legacy"><i class="fa-solid fa-box-archive"></i> پلن‌های بخش قبلی حذف نشده‌اند؛ وضعیت آن‌ها «غیرفعال» است و خریدهای قبلی‌شان حفظ می‌شود.</p>
    </section>

    <div class="pb-grid">
      <section class="pb-card">
        <div class="pb-card-head">
          <h3>پلن‌ها</h3>
          <form class="pb-filter" method="GET">
            <input class="pb-input" name="search" value="{{ request('search') }}" placeholder="جست‌وجوی نام یا کد">
            <select class="pb-select" name="status" onchange="this.form.submit()">
              <option value="">همه وضعیت‌ها</option>
              @foreach(['active'=>'فعال','draft'=>'پیش‌نویس','inactive'=>'غیرفعال','archived'=>'آرشیو'] as $key=>$label)
                <option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>
              @endforeach
            </select>
          </form>
        </div>
        <form action="{{ route('admin.plans.reorder') }}" method="POST">
          @csrf
          <div class="pb-table-wrap">
            <table class="pb-table">
              <thead><tr><th>پلن</th><th>قیمت عادی</th><th>مشتری ثابت</th><th>اعتبار</th><th>وضعیت</th><th>خرید</th><th>عملیات</th></tr></thead>
              <tbody id="plan-sortable">
              @forelse($plans as $plan)
                @php $loyal=$plan->audience_overrides['loyal']??[]; @endphp
                <tr draggable="true" data-id="{{ $plan->id }}">
                  <td>
                    <input type="hidden" name="order[]" value="{{ $plan->id }}">
                    <div class="pb-plan-name"><span class="pb-icon"><i class="{{ $plan->icon }}"></i></span><span><b>{{ $plan->name }}</b><small>{{ $plan->plan_code }}</small></span></div>
                  </td>
                  <td>{{ $plan->billing_type==='custom' ? (($plan->price_prefix ?: 'از').' '.number_format($plan->price)) : ($plan->price ? number_format($plan->price).' تومان' : 'رایگان') }}</td>
                  <td>{{ isset($loyal['price']) && is_numeric($loyal['price']) ? number_format($loyal['price']).' تومان' : 'همان قیمت عادی' }}</td>
                  <td>{{ $plan->is_unlimited ? 'نامحدود*' : number_format($plan->tokens) }}</td>
                  <td>
                    @if($plan->archived_at)<span class="pb-badge pb-badge-danger">آرشیو</span>
                    @elseif($plan->status==='active')<span class="pb-badge pb-badge-success">فعال</span>
                    @elseif($plan->status==='draft')<span class="pb-badge pb-badge-warning">پیش‌نویس</span>
                    @else<span class="pb-badge pb-badge-info">غیرفعال</span>@endif
                  </td>
                  <td>{{ number_format($plan->purchases_count ?? 0) }}</td>
                  <td><div class="pb-row-actions">
                    <a class="pb-btn pb-btn-sm" href="{{ route('admin.plans.edit',$plan) }}" title="ویرایش"><i class="fa-solid fa-pen"></i></a>
                    <button class="pb-btn pb-btn-sm" form="duplicate-{{ $plan->id }}" title="تکثیر"><i class="fa-solid fa-copy"></i></button>
                    <button class="pb-btn pb-btn-sm" form="archive-{{ $plan->id }}" title="آرشیو"><i class="fa-solid fa-box-archive"></i></button>
                  </div></td>
                </tr>
              @empty
                <tr><td colspan="7" class="pb-empty">پلنی پیدا نشد.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
          @if($plans->isNotEmpty())<div class="pb-card-body"><button class="pb-btn"><i class="fa-solid fa-arrow-down-1-9"></i> ذخیره ترتیب فعلی</button></div>@endif
        </form>
        @foreach($plans as $plan)
          <form id="duplicate-{{ $plan->id }}" action="{{ route('admin.plans.duplicate',$plan) }}" method="POST">@csrf</form>
          <form id="archive-{{ $plan->id }}" action="{{ route('admin.plans.archive',$plan) }}" method="POST">@csrf @method('PATCH')</form>
        @endforeach
      </section>

      <aside class="pb-card">
        <div class="pb-card-head"><h3>تنظیمات نمایش</h3></div>
        <div class="pb-card-body">
          <form class="pb-settings" action="{{ route('admin.plans.display-settings') }}" method="POST">
            @csrf @method('PUT')
            <label><span class="pb-label">حالت اصلی صفحه پلن‌ها</span><select class="pb-select" name="mode"><option value="cards" @selected(($display['mode']??'cards')==='cards')>کارت‌ها</option><option value="comparison" @selected(($display['mode']??'')==='comparison')>جدول مقایسه</option></select></label>
            <label><span class="pb-label">تعداد پلن در صفحه نخست</span><input class="pb-input" type="number" name="home_limit" min="1" max="6" value="{{ $display['home_limit']??3 }}"></label>
            <label><span class="pb-label">عنوان بخش</span><input class="pb-input" name="title" value="{{ $display['title']??'' }}" required></label>
            <label><span class="pb-label">زیرعنوان</span><textarea class="pb-textarea" name="subtitle" rows="3">{{ $display['subtitle']??'' }}</textarea></label>
            <label class="pb-check"><input type="checkbox" name="show_images" value="1" @checked($display['show_images']??false)> نمایش تصویر پلن</label>
            <label class="pb-check"><input type="checkbox" name="show_comparison" value="1" @checked($display['show_comparison']??true)> نمایش جدول مقایسه تکمیلی</label>
            <button class="pb-btn pb-btn-primary pb-btn-block">ذخیره تنظیمات نمایش</button>
          </form>
        </div>
      </aside>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  const list=document.getElementById('plan-sortable'); let dragging=null;
  list?.addEventListener('dragstart',e=>{dragging=e.target.closest('tr');});
  list?.addEventListener('dragover',e=>{e.preventDefault();const row=e.target.closest('tr');if(row&&dragging&&row!==dragging){const rect=row.getBoundingClientRect();row.parentNode.insertBefore(dragging,e.clientY<rect.top+rect.height/2?row:row.nextSibling);}});
</script>
@endsection
