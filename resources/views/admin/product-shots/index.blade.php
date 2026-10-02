@extends('layouts.admin')
@section('title', 'استودیو محصول — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/product-shots.css') }}">
@endpush

@section('content')
@php
  $tab = in_array(request('tab'), ['overview', 'library', 'products'], true) ? request('tab') : 'overview';
  $shotPayload = fn ($shot) => [
    'id' => $shot->id, 'key' => $shot->key, 'name_fa' => $shot->name_fa, 'name_en' => $shot->name_en,
    'description_fa' => $shot->description_fa, 'category' => $shot->category, 'niche_tags' => implode(', ', (array) $shot->niche_tags),
    'tokens' => $shot->normalizedTokens(), 'prompt_template' => $shot->prompt_template, 'default_credits' => $shot->default_credits,
    'aspect_ratio_default' => $shot->aspect_ratio_default, 'sort' => $shot->sort,
    'action' => route('admin.product-shots.library.update', $shot->id),
  ];
  $fa = fn ($n) => strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹','.'=>'٫']);
@endphp
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">

    @foreach (['success' => 'success', 'error' => 'danger'] as $key => $tone)
      @if(session($key))
        <div class="admin-toast mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--{{ $tone }}-l);color:var(--{{ $tone }});border:1px solid var(--{{ $tone }}-m);" role="{{ $key === 'error' ? 'alert' : 'status' }}">
          <span class="flex-1">{{ session($key) }}</span>
          <button type="button" onclick="this.closest('.admin-toast').remove()" aria-label="بستن پیام"><i class="fa-solid fa-xmark"></i></button>
        </div>
      @endif
    @endforeach
    @if($errors->any())
      <div class="mb-4 px-4 py-3 rounded-xl text-[12px]" style="background:var(--danger-l);color:var(--danger);border:1px solid var(--danger-m);" role="alert">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
      </div>
    @endif

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">استودیو محصول</div>
        <div class="text-[13px]" style="color:var(--text-soft);">پک شات تبلیغاتی از یک عکس محصول — کتابخانه‌ی شات، ثبت محصول پروداکتی و کنترل عرضه</div>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        @if($moduleEnabled)
          <span class="badge-pro badge-success"><i class="fa-solid fa-circle"></i> روشن · {{ ['admins' => 'فقط ادمین', 'whitelist' => 'لیست سفید', 'public' => 'همه‌ی کاربران'][$settings->audience] ?? $settings->audience }}</span>
          <a href="{{ route('admin.product-shots.products.create') }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> ثبت محصول پروداکتی</a>
        @else
          <span class="badge-pro badge-neutral"><i class="fa-solid fa-circle"></i> خاموش</span>
        @endif
      </div>
    </div>

    @unless($masterSwitch)
      <div class="ps-warn-box mb-4">کلید اصلی <code>PRODUCT_SHOTS_ENABLED</code> در تنظیمات سرور خاموش است؛ تا وقتی روشن نشود، تنظیمات این صفحه اثری ندارد.</div>
    @endunless

    <div class="ps-tabs" role="tablist">
      <a href="?tab=overview" class="chip-filter {{ $tab === 'overview' ? 'active' : '' }}" data-ps-tab="overview" role="tab">نمای کلی و فلگ</a>
      <a href="?tab=library" class="chip-filter {{ $tab === 'library' ? 'active' : '' }}" data-ps-tab="library" role="tab">کتابخانه‌ی شات <span class="chip-count">{{ $fa($shots->count()) }}</span></a>
      <a href="?tab=products" class="chip-filter {{ $tab === 'products' ? 'active' : '' }}" data-ps-tab="products" role="tab">محصولات پروداکتی <span class="chip-count">{{ $fa($products->count()) }}</span></a>
    </div>

    {{-- ═══ نمای کلی ═══ --}}
    <section class="ps-panel" data-ps-panel="overview" @if($tab !== 'overview') hidden @endif>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-5">
        <div class="stat-card pro-tooltip-wrap">
          <div class="stat-card-icon" style="background:var(--info-l);color:var(--info);"><i class="fa-solid fa-images"></i></div>
          <div class="min-w-0"><div class="stat-card-value">{{ $fa($stats['shots_7d']) }}</div><div class="stat-card-label">شات ساخته‌شده (۷ روز)</div></div>
          <div class="pro-tooltip">موفق و ناموفق، هفت روز اخیر</div>
        </div>
        <div class="stat-card pro-tooltip-wrap">
          <div class="stat-card-icon" style="background:var(--success-l);color:var(--success);"><i class="fa-solid fa-circle-check"></i></div>
          <div class="min-w-0"><div class="stat-card-value">{{ $stats['success_rate'] === null ? '—' : $fa($stats['success_rate']) . '٪' }}</div><div class="stat-card-label">نرخ موفقیت</div></div>
          <div class="pro-tooltip">کنترل کیفیت: {{ $stats['qc_pass_rate'] === null ? 'بدون داده' : $fa($stats['qc_pass_rate']) . '٪ قبول' }} · ساخت مجدد خودکار: {{ $fa($stats['qc_retries_7d']) }}</div>
        </div>
        <div class="stat-card pro-tooltip-wrap">
          <div class="stat-card-icon" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-coins"></i></div>
          <div class="min-w-0"><div class="stat-card-value">{{ $fa(number_format($stats['credits_7d'])) }}</div><div class="stat-card-label">کردیت کسرشده (۷ روز)</div></div>
          <div class="pro-tooltip">بازگشت‌داده‌شده: {{ $fa($stats['refunds_7d']) }} کردیت</div>
        </div>
        <div class="stat-card pro-tooltip-wrap">
          <div class="stat-card-icon" style="background:{{ ($stats['cost_ratio'] ?? 0) > 50 ? 'var(--danger-l)' : 'var(--warning-l)' }};color:{{ ($stats['cost_ratio'] ?? 0) > 50 ? 'var(--danger)' : 'var(--warning)' }};"><i class="fa-solid fa-scale-balanced"></i></div>
          <div class="min-w-0"><div class="stat-card-value">{{ $stats['cost_ratio'] === null ? '—' : $fa($stats['cost_ratio']) . '٪' }}</div><div class="stat-card-label">هزینه‌ی API به درآمد</div></div>
          <div class="pro-tooltip">هزینه: {{ $fa($stats['cost_usd_7d']) }} دلار{{ $stats['cost_toman_7d'] ? ' ≈ ' . $fa(number_format($stats['cost_toman_7d'])) . ' تومان' : '' }} · درآمد: {{ $fa(number_format($stats['revenue_toman_7d'])) }} تومان</div>
        </div>
      </div>

      @if(($stats['cost_ratio'] ?? 0) > 50)
        <div class="ps-warn-box mb-4"><i class="fa-solid fa-triangle-exclamation"></i> هزینه‌ی واقعی API بیش از ۵۰٪ درآمد این بخش است. قیمت شات‌ها یا مدل پیش‌فرض را بازبینی کنید.</div>
      @endif

      <form method="POST" action="{{ route('admin.product-shots.settings.update') }}" class="content-card p-5">
        @csrf @method('PUT')
        <div class="ps-card-title"><i class="fa-solid fa-flag"></i> فیچر فلگ و عرضه</div>
        <div class="ps-card-desc">پیش‌فرض خاموش است. با «فقط ادمین» فقط کسی که در همین مرورگر وارد پنل مدیریت است صفحه‌ی ساخت پک را می‌بیند. با فلگ خاموش هیچ اثری از این بخش در سایت و اپ دیده نمی‌شود.</div>

        <div class="ps-grid mb-4">
          <div class="ps-switch">
            <div><strong>روشن بودن استودیو محصول</strong><span>امروز: {{ $fa($stats['today_cost_usd']) }} دلار از سقف {{ $stats['cap_usd'] > 0 ? $fa($stats['cap_usd']) . ' دلار' : 'نامحدود' }}</span></div>
            <label class="ps-toggle"><input type="checkbox" name="enabled" value="1" @checked($settings->enabled)><i></i></label>
          </div>
          <div class="ps-field">
            <label for="ps-audience">مخاطب</label>
            <select id="ps-audience" name="audience" class="input-pro">
              <option value="admins" @selected($settings->audience === 'admins')>فقط ادمین (پایلوت)</option>
              <option value="whitelist" @selected($settings->audience === 'whitelist')>ادمین + کاربران لیست سفید</option>
              <option value="public" @selected($settings->audience === 'public')>همه‌ی کاربران</option>
            </select>
          </div>
          <div class="ps-field">
            <label for="ps-wl-ids">شناسه‌ی کاربران لیست سفید</label>
            <input id="ps-wl-ids" name="whitelist_user_ids" class="input-pro" value="{{ implode(', ', (array) $settings->whitelist_user_ids) }}" placeholder="مثلاً 12, 48, 301">
          </div>
          <div class="ps-field">
            <label for="ps-wl-phones">موبایل کاربران لیست سفید</label>
            <input id="ps-wl-phones" name="whitelist_phones" class="input-pro" value="{{ implode(', ', (array) $settings->whitelist_phones) }}" placeholder="09121234567, 09351234567" dir="ltr">
          </div>
        </div>

        <div class="ps-card-title mt-2"><i class="fa-solid fa-sliders"></i> سقف‌ها و هزینه</div>
        <div class="ps-grid ps-grid-3 mb-4">
          <div class="ps-field"><label for="ps-max">حداکثر شات در هر ساخت</label><input id="ps-max" type="number" min="1" max="10" name="max_shots_per_run" class="input-pro" value="{{ $settings->max_shots_per_run }}"></div>
          <div class="ps-field"><label for="ps-conc">ساخت هم‌زمان در مرورگر</label><input id="ps-conc" type="number" min="1" max="3" name="client_concurrency" class="input-pro" value="{{ $settings->client_concurrency }}"><div class="ps-hint">روی سرور ۱ گیگابایتی ۱ پیشنهاد می‌شود.</div></div>
          <div class="ps-field"><label for="ps-cap">سقف هزینه‌ی روزانه‌ی API (دلار)</label><input id="ps-cap" type="number" step="0.01" min="0" name="daily_cost_cap_usd" class="input-pro" value="{{ $settings->daily_cost_cap_usd }}"><div class="ps-hint">صفر = بدون سقف. بعد از سقف، ساخت بدون کسر کردیت متوقف می‌شود.</div></div>
          <div class="ps-field"><label for="ps-price">قیمت فروش هر کردیت (تومان)</label><input id="ps-price" type="number" min="1" name="credit_price_toman" class="input-pro" value="{{ $settings->credit_price_toman }}"><div class="ps-hint">فقط برای محاسبه‌ی هشدار «هزینه بیش از ۵۰٪ درآمد».</div></div>
        </div>

        <div class="ps-card-title mt-2"><i class="fa-solid fa-eye"></i> مدل بینای ارزان</div>
        <div class="ps-grid mb-4">
          <div class="ps-switch"><div><strong>فیلتر کیفیت عکس ورودی</strong><span>قبل از ساخت؛ هیچ کردیتی کسر نمی‌شود</span></div><label class="ps-toggle"><input type="checkbox" name="preflight_enabled" value="1" @checked($settings->preflight_enabled)><i></i></label></div>
          <div class="ps-field"><label for="ps-pf-model">مدل فیلتر ورودی</label><input id="ps-pf-model" name="preflight_model" class="input-pro" dir="ltr" value="{{ $settings->preflight_model }}" placeholder="{{ config('product_shots.vision_model') }}"></div>
          <div class="ps-switch"><div><strong>کنترل وفاداری خروجی (QC)</strong><span>آیا محصول خروجی همان محصول ورودی است؟</span></div><label class="ps-toggle"><input type="checkbox" name="qc_enabled" value="1" @checked($settings->qc_enabled)><i></i></label></div>
          <div class="ps-field"><label for="ps-qc-model">مدل QC</label><input id="ps-qc-model" name="qc_model" class="input-pro" dir="ltr" value="{{ $settings->qc_model }}" placeholder="{{ config('product_shots.vision_model') }}"></div>
          <div class="ps-switch"><div><strong>ساخت مجدد خودکار در صورت رد QC</strong><span>حداکثر ۱ بار، بدون کسر کردیت اضافه از کاربر</span></div><label class="ps-toggle"><input type="checkbox" name="qc_auto_retry" value="1" @checked($settings->qc_auto_retry)><i></i></label></div>
        </div>

        <div class="flex justify-end"><button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره‌ی تنظیمات</button></div>
      </form>
    </section>

    {{-- ═══ کتابخانه‌ی شات ═══ --}}
    <section class="ps-panel" data-ps-panel="library" @if($tab !== 'library') hidden @endif>
      <div class="content-card p-0 overflow-hidden">
        <div class="flex items-center justify-between flex-wrap gap-2 p-4" style="border-bottom:1px solid var(--divider);">
          <div>
            <div class="ps-card-title"><i class="fa-solid fa-layer-group"></i> کتابخانه‌ی مشترک شات</div>
            <div class="text-[11.5px]" style="color:var(--text-soft);">هر شات ترکیبی از توکن‌های «زبان شات» است؛ پرامپت نهایی خودکار ساخته می‌شود و بلوک وفاداری محصول همیشه به آن اضافه می‌شود.</div>
          </div>
          <button type="button" class="btn-pro btn-pro-primary" data-shot-new><i class="fa-solid fa-plus text-[11px]"></i> شات جدید</button>
        </div>
        @if($shots->isEmpty())
          <div class="empty-state"><div class="empty-state-icon"><i class="fa-solid fa-camera"></i></div><div class="empty-state-title">هنوز شاتی ثبت نشده</div></div>
        @else
          <div class="overflow-x-auto">
            <table class="table-pro">
              <thead><tr><th>شات</th><th>دسته</th><th>توکن‌ها</th><th>کردیت</th><th>محصولات</th><th>وضعیت</th><th>عملیات</th></tr></thead>
              <tbody>
                @foreach($shots as $shot)
                  <tr>
                    <td>
                      <div class="flex items-center gap-3">
                        <div class="table-thumb">@if($shot->sampleImageUrl())<img src="{{ $shot->sampleImageUrl() }}" alt="">@else<i class="fa-solid fa-image" style="color:var(--text-soft);"></i>@endif</div>
                        <div class="min-w-0"><div class="font-bold" style="color:var(--text-h);">{{ $shot->name_fa }}</div><div class="text-[10.5px]" style="color:var(--text-soft);" dir="ltr">{{ $shot->key }}</div></div>
                      </div>
                    </td>
                    <td><span class="badge-pro badge-neutral">{{ $categories[$shot->category] ?? $shot->category }}</span></td>
                    <td><div class="ps-tags">@foreach(array_slice($shot->tokenLabels(), 0, 4) as $label)<span class="ps-tag">{{ $label }}</span>@endforeach</div></td>
                    <td>{{ $fa($shot->default_credits) }}</td>
                    <td>{{ $fa($shot->product_shots_count) }}</td>
                    <td>@if($shot->is_active)<span class="badge-pro badge-success"><i class="fa-solid fa-circle"></i> فعال</span>@else<span class="badge-pro badge-neutral"><i class="fa-solid fa-circle"></i> غیرفعال</span>@endif</td>
                    <td>
                      <div class="flex items-center gap-1.5 justify-end">
                        <button type="button" class="icon-action-btn" title="ویرایش" aria-label="ویرایش {{ $shot->name_fa }}" data-shot-edit="{{ json_encode($shotPayload($shot), JSON_UNESCAPED_UNICODE) }}"><i class="fa-solid fa-pen"></i></button>
                        <form method="POST" action="{{ route('admin.product-shots.library.toggle', $shot->id) }}">@csrf @method('PATCH')
                          <button type="submit" class="icon-action-btn" title="{{ $shot->is_active ? 'غیرفعال کن' : 'فعال کن' }}" aria-label="{{ $shot->is_active ? 'غیرفعال کن' : 'فعال کن' }}"><i class="fa-solid {{ $shot->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </section>

    {{-- ═══ محصولات پروداکتی ═══ --}}
    <section class="ps-panel" data-ps-panel="products" @if($tab !== 'products') hidden @endif>
      <div class="content-card p-0 overflow-hidden">
        <div class="flex items-center justify-between flex-wrap gap-2 p-4" style="border-bottom:1px solid var(--divider);">
          <div class="ps-card-title"><i class="fa-solid fa-box-open"></i> محصولات پروداکتی</div>
          @if($moduleEnabled)
            <a href="{{ route('admin.product-shots.products.create') }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> ثبت محصول پروداکتی</a>
          @endif
        </div>
        @if($products->isEmpty())
          <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
            <div class="empty-state-title">هنوز محصول پروداکتی ثبت نشده</div>
            <div class="empty-state-desc">{{ $moduleEnabled ? 'اولین پک شات را برای یک محصول آرایشی بساز.' : 'ابتدا استودیو محصول را از تب «نمای کلی» روشن کنید.' }}</div>
          </div>
        @else
          <div class="overflow-x-auto">
            <table class="table-pro">
              <thead><tr><th>محصول</th><th>نیش</th><th>شات فعال</th><th>از</th><th>وضعیت</th><th>عملیات</th></tr></thead>
              <tbody>
                @foreach($products as $p)
                  <tr>
                    <td><div class="flex items-center gap-3"><div class="table-thumb"><img src="{{ $p->displayImageUrl() }}" alt=""></div><div><div class="font-bold" style="color:var(--text-h);">{{ $p->name_fa }}</div><div class="text-[10.5px]" style="color:var(--text-soft);">کد {{ $p->product_code }}</div></div></div></td>
                    <td>{{ $niches[$p->shot_settings['niche'] ?? ''] ?? '—' }}</td>
                    <td>{{ $fa($p->enabled_shots_count) }}</td>
                    <td>{{ $fa($p->credit_cost) }} کردیت</td>
                    <td>
                      @php $tone = ['active' => ['badge-success', 'منتشرشده'], 'draft' => ['badge-warning', 'پیش‌نویس'], 'inactive' => ['badge-neutral', 'غیرفعال']][$p->status] ?? ['badge-neutral', $p->status]; @endphp
                      <span class="badge-pro {{ $tone[0] }}"><i class="fa-solid fa-circle"></i> {{ $tone[1] }}</span>
                    </td>
                    <td>
                      <div class="flex items-center gap-1.5 justify-end">
                        @if($moduleEnabled)<a class="icon-action-btn" href="{{ route('admin.product-shots.products.create', $p->id) }}" title="ویرایش" aria-label="ویرایش {{ $p->name_fa }}"><i class="fa-solid fa-pen"></i></a>@endif
                        @if($moduleEnabled && $p->status === 'active')<a class="icon-action-btn" href="{{ route('app.create', ['product' => $p->route_slug]) }}" target="_blank" rel="noopener" title="مشاهده در اپ" aria-label="مشاهده در اپ"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>@endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </section>
  </div>
</main>

{{-- ═══ Drawer افزودن/ویرایش شات ═══ --}}
<div class="drawer-overlay" id="shot-drawer-overlay" data-shot-close></div>
<aside class="drawer-panel" id="shot-drawer" dir="rtl" aria-label="ویرایش شات" aria-hidden="true">
  <form method="POST" enctype="multipart/form-data" id="shot-form" action="{{ route('admin.product-shots.library.store') }}" class="p-5">
    @csrf
    <input type="hidden" name="_method" value="POST" id="shot-form-method">
    <div class="flex items-center justify-between mb-4">
      <div class="ps-card-title" id="shot-drawer-title"><i class="fa-solid fa-camera"></i> شات جدید</div>
      <button type="button" class="icon-action-btn" data-shot-close aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="grid gap-3">
      <div class="ps-grid">
        <div class="ps-field"><label for="sf-name-fa">نام فارسی</label><input id="sf-name-fa" name="name_fa" class="input-pro" required></div>
        <div class="ps-field"><label for="sf-name-en">نام انگلیسی</label><input id="sf-name-en" name="name_en" class="input-pro" dir="ltr"></div>
      </div>
      <div class="ps-grid">
        <div class="ps-field"><label for="sf-key">کلید (اختیاری)</label><input id="sf-key" name="key" class="input-pro" dir="ltr" placeholder="beauty-new-shot"></div>
        <div class="ps-field"><label for="sf-category">دسته</label><select id="sf-category" name="category" class="input-pro">@foreach($categories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
      </div>
      <div class="ps-field"><label for="sf-desc">توضیح کوتاه (برای کاربر)</label><textarea id="sf-desc" name="description_fa" class="input-pro"></textarea></div>

      <div class="ps-label">توکن‌های زبان شات</div>
      <div class="ps-token-grid">
        @foreach($axisLabels as $axis => $label)
          <div class="ps-field">
            <label>{{ $label }}</label>
            @if(in_array($axis, $multiAxes, true))
              <div class="ps-token-multi" data-axis="{{ $axis }}">
                @foreach($grammar[$axis] as $tokenKey => $token)
                  <label><input type="checkbox" name="tokens[{{ $axis }}][]" value="{{ $tokenKey }}"> {{ $token['fa'] }}</label>
                @endforeach
              </div>
            @else
              <select name="tokens[{{ $axis }}]" class="input-pro" data-axis="{{ $axis }}">
                <option value="">— پیش‌فرض —</option>
                @foreach($grammar[$axis] as $tokenKey => $token)<option value="{{ $tokenKey }}">{{ $token['fa'] }}</option>@endforeach
              </select>
            @endif
          </div>
        @endforeach
      </div>

      <div class="ps-grid ps-grid-3">
        <div class="ps-field"><label for="sf-credits">کردیت پیش‌فرض</label><input id="sf-credits" type="number" min="0" name="default_credits" class="input-pro" value="12"></div>
        <div class="ps-field"><label for="sf-ratio">نسبت پیش‌فرض</label><select id="sf-ratio" name="aspect_ratio_default" class="input-pro">@foreach((array) config('product_shots.aspect_ratios') as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select></div>
        <div class="ps-field"><label for="sf-sort">ترتیب</label><input id="sf-sort" type="number" min="0" name="sort" class="input-pro" value="100"></div>
      </div>
      <div class="ps-field"><label for="sf-niche">برچسب نیش</label><input id="sf-niche" name="niche_tags" class="input-pro" dir="ltr" placeholder="beauty, perfume"></div>
      <div class="ps-field">
        <label for="sf-template">قالب پرامپت اختصاصی (اختیاری)</label>
        <textarea id="sf-template" name="prompt_template" class="input-pro" dir="ltr" placeholder="Advertising photo of {product}. {framing}, {lighting}. {fidelity}"></textarea>
        <div class="ps-hint">جای‌خالی‌ها: <span dir="ltr">{product} {framing} {camera} {lens} {lighting} {surface} {props} {human} {mood} {fidelity} {aspect_ratio}</span>. خالی = قالب استاندارد. بلوک وفاداری محصول همیشه اضافه می‌شود.</div>
      </div>
      <div class="ps-field"><label for="sf-sample">تصویر نمونه‌ی عمومی (اختیاری)</label><input id="sf-sample" type="file" name="sample" accept="image/*" class="input-pro" style="padding-top:7px;"></div>
      <div class="flex justify-end gap-2 mt-2">
        <button type="button" class="btn-pro btn-pro-ghost" data-shot-close>انصراف</button>
        <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره‌ی شات</button>
      </div>
    </div>
  </form>
</aside>
@endsection

@section('scripts')
<script>
(function () {
  document.querySelectorAll('[data-ps-tab]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var tab = link.getAttribute('data-ps-tab');
      document.querySelectorAll('[data-ps-tab]').forEach(function (l) { l.classList.toggle('active', l === link); });
      document.querySelectorAll('[data-ps-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-ps-panel') !== tab; });
      try { history.replaceState(null, '', '?tab=' + tab); } catch (err) {}
    });
  });

  var drawer = document.getElementById('shot-drawer');
  var overlay = document.getElementById('shot-drawer-overlay');
  var form = document.getElementById('shot-form');
  var method = document.getElementById('shot-form-method');
  var title = document.getElementById('shot-drawer-title');
  var storeUrl = form.getAttribute('action');

  function open(data) {
    form.reset();
    form.querySelectorAll('.ps-token-multi input').forEach(function (i) { i.checked = false; });
    if (data) {
      form.action = data.action; method.value = 'PUT';
      title.innerHTML = '<i class="fa-solid fa-pen"></i> ویرایش شات';
      ['key', 'name_fa', 'name_en', 'description_fa', 'category', 'niche_tags', 'prompt_template', 'default_credits', 'aspect_ratio_default', 'sort'].forEach(function (k) {
        var el = form.elements[k]; if (el) el.value = data[k] == null ? '' : data[k];
      });
      Object.keys(data.tokens || {}).forEach(function (axis) {
        var value = data.tokens[axis];
        if (Array.isArray(value)) {
          value.forEach(function (v) { var box = form.querySelector('input[name="tokens[' + axis + '][]"][value="' + v + '"]'); if (box) box.checked = true; });
        } else {
          var sel = form.querySelector('select[name="tokens[' + axis + ']"]'); if (sel) sel.value = value;
        }
      });
    } else {
      form.action = storeUrl; method.value = 'POST';
      title.innerHTML = '<i class="fa-solid fa-camera"></i> شات جدید';
    }
    drawer.classList.add('open'); overlay.classList.add('open'); drawer.setAttribute('aria-hidden', 'false');
    setTimeout(function () { form.elements.name_fa.focus(); }, 50);
  }
  function close() { drawer.classList.remove('open'); overlay.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true'); }

  document.querySelectorAll('[data-shot-new]').forEach(function (b) { b.addEventListener('click', function () { open(null); }); });
  document.querySelectorAll('[data-shot-edit]').forEach(function (b) {
    b.addEventListener('click', function () { open(JSON.parse(b.getAttribute('data-shot-edit'))); });
  });
  document.querySelectorAll('[data-shot-close]').forEach(function (b) { b.addEventListener('click', close); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
@endsection
