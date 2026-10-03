@extends('layouts.admin')
@section('title', ($product ? 'ویرایش محصول پروداکتی' : 'ثبت محصول پروداکتی') . ' — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/product-shots.css') }}">
@endpush

@section('content')
@php
  $selectedCategories = collect(old('category_ids', $product?->categories->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
  $currentModel = $models->firstWhere('openrouter_model_id', $product?->primary_model);
  $fallbackIds = $models->whereIn('openrouter_model_id', (array) ($product?->fallback_models ?? []))->pluck('id')->all();
  $brandIdentityEnabled = (bool) old('brand_identity_enabled', $product ? ($settings['brand_identity_enabled'] ?? false) : true);
  $brandIdentityPrompt = old('brand_identity_prompt', $settings['brand_identity_prompt'] ?? \App\Services\ProductShots\ShotPromptBuilder::defaultBrandIdentityPrompt());
@endphp
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">

    @if(session('success'))
      <div class="admin-toast mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--success-l);color:var(--success);border:1px solid var(--success-m);" role="status">
        <span class="flex-1">{{ session('success') }}</span>
        <button type="button" onclick="this.closest('.admin-toast').remove()" aria-label="بستن پیام"><i class="fa-solid fa-xmark"></i></button>
      </div>
    @endif
    @if($errors->any())
      <div class="mb-4 px-4 py-3 rounded-xl text-[12px]" style="background:var(--danger-l);color:var(--danger);border:1px solid var(--danger-m);" role="alert">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
      </div>
    @endif

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">{{ $product ? 'ویرایش «' . $product->name_fa . '»' : 'ثبت محصول پروداکتی' }}</div>
        <div class="text-[13px]" style="color:var(--text-soft);">یک محصول = یک «پک شات». کاربر ۱ عکس محصول می‌دهد و شات‌های تیک‌خورده‌ی همین صفحه را می‌گیرد.</div>
      </div>
      <a href="{{ route('admin.product-shots.index', ['tab' => 'products']) }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت به استودیو محصول</a>
    </div>

    <form method="POST" enctype="multipart/form-data" id="shot-product-form"
          action="{{ $product ? route('admin.product-shots.products.update', $product->id) : route('admin.product-shots.products.store') }}">
      @csrf
      @if($product) @method('PUT') @endif
      <input type="hidden" name="status" id="sp-status" value="{{ old('status', $product?->status ?? 'draft') }}">

      <div class="grid gap-4">
        {{-- ۱. هویت --}}
        <section class="content-card p-5">
          <div class="ps-card-title"><i class="fa-solid fa-id-card"></i> ۱. مشخصات محصول</div>
          <div class="ps-card-desc">نام و دسته در کاتالوگ و کارت «پک» نمایش داده می‌شود.</div>
          <div class="ps-grid">
            <div class="ps-field"><label for="sp-name-fa">نام فارسی</label><input id="sp-name-fa" name="name_fa" class="input-pro" required value="{{ old('name_fa', $product?->name_fa) }}" placeholder="مثلاً پک تبلیغاتی سرم"></div>
            <div class="ps-field"><label for="sp-name-en">نام انگلیسی</label><input id="sp-name-en" name="name_en" class="input-pro" required dir="ltr" value="{{ old('name_en', $product?->name_en) }}" placeholder="Serum ad pack"></div>
            <div class="ps-field">
              <label for="sp-niche">صنف (نیش)</label>
              <select id="sp-niche" name="niche" class="input-pro">
                @foreach($niches as $key => $label)<option value="{{ $key }}" @selected(old('niche', $settings['niche'] ?? 'beauty') === $key)>{{ $label }}</option>@endforeach
              </select>
              <div class="ps-hint">معماری برای تمام صنف‌ها مشترک است؛ شات‌های پیشنهادی هر صنف از کتابخانه انتخاب می‌شوند.</div>
            </div>
            <div class="ps-field">
              <label for="sp-categories">دسته‌بندی‌ها</label>
              <select id="sp-categories" name="category_ids[]" class="input-pro" multiple size="4" style="height:auto;padding:6px;">
                @foreach($categories as $category)
                  <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategories, true))>{{ $category->parent_id ? '— ' : '' }}{{ $category->name_fa ?: $category->name }}</option>
                @endforeach
              </select>
              <div class="ps-hint">برای انتشار حداقل یک دسته لازم است. اولین انتخاب = دسته‌ی اصلی.</div>
            </div>
          </div>
          <div class="ps-field mt-3"><label for="sp-desc">توضیح برای کاربر</label><textarea id="sp-desc" name="description_fa" class="input-pro" placeholder="یک عکس از محصولت بده و چند عکس تبلیغاتی سینمایی بگیر.">{{ old('description_fa', $product?->description_fa) }}</textarea></div>
        </section>

        {{-- ۲. سبک و مدل --}}
        <section class="content-card p-5">
          <div class="ps-card-title"><i class="fa-solid fa-palette"></i> ۲. سبک برند و مدل ساخت</div>
          <div class="ps-card-desc">این مقادیر در پرامپت همه‌ی شات‌های این محصول می‌نشینند. «توضیح فیزیکی» اگر خالی باشد از روی عکس کاربر (فیلتر کیفیت) پر می‌شود.</div>
          <div class="ps-grid">
            <div class="ps-field"><label for="sp-pdesc">توضیح فیزیکی محصول (انگلیسی، اختیاری)</label><input id="sp-pdesc" name="product_description" class="input-pro" dir="ltr" value="{{ old('product_description', $settings['product_description'] ?? '') }}" placeholder="amber glass dropper serum bottle with gold cap"></div>
            <div class="ps-field"><label for="sp-palette">پالت رنگ برند (اختیاری)</label><input id="sp-palette" name="brand_palette" class="input-pro" dir="ltr" value="{{ old('brand_palette', $settings['brand_palette'] ?? '') }}" placeholder="peach, soft gold and cream"></div>
            <div class="ps-field"><label for="sp-style">حس کلی برند (اختیاری)</label><input id="sp-style" name="brand_style" class="input-pro" dir="ltr" value="{{ old('brand_style', $settings['brand_style'] ?? '') }}" placeholder="minimal luxury skincare"></div>
            <div class="ps-field">
              <label for="sp-model">مدل ساخت (ویرایش تصویر با مرجع)</label>
              <select id="sp-model" name="ai_model_id" class="input-pro" required>
                @forelse($models as $model)
                  <option value="{{ $model->id }}" @selected((int) old('ai_model_id', $currentModel?->id) === $model->id)>{{ $model->name }} · {{ $model->provider }}{{ $model->cost_per_generation_usd ? ' · ~$' . rtrim(rtrim(number_format((float) $model->cost_per_generation_usd, 3), '0'), '.') : '' }}</option>
                @empty
                  <option value="">هیچ مدل فعالی با ورودی تصویر پیدا نشد</option>
                @endforelse
              </select>
              <div class="ps-hint">پیش‌فرض باید ارزان‌ترین مدلی باشد که در «حفظ محصول» رد نشود (نتیجه‌ی آزمون 3.B).</div>
            </div>
            <div class="ps-field">
              <label for="sp-fallback">مدل‌های جایگزین (حداکثر ۳)</label>
              <select id="sp-fallback" name="fallback_model_ids[]" class="input-pro" multiple size="3" style="height:auto;padding:6px;">
                @foreach($models as $model)
                  <option value="{{ $model->id }}" @selected(in_array($model->id, array_map('intval', (array) old('fallback_model_ids', $fallbackIds)), true))>{{ $model->name }} · {{ $model->provider }}</option>
                @endforeach
              </select>
            </div>
            <div class="ps-field">
              <label for="sp-cover">تصویر کارت (اختیاری)</label>
              <input id="sp-cover" type="file" name="cover" accept="image/*" class="input-pro" style="padding-top:7px;">
              <div class="ps-hint">اگر خالی بماند، اولین نمونه‌ی شات ذخیره‌شده کاور می‌شود.</div>
            </div>
          </div>

          <div class="ps-brand-identity mt-4" data-brand-identity>
            <div class="ps-brand-identity-head">
              <div>
                <div class="ps-card-title"><i class="fa-solid fa-fingerprint"></i> حفظ هویت برند</div>
                <div class="ps-card-desc">وقتی روشن باشد، این دستور دقیقاً به انتهای پرامپت تمام شات‌های همین پک اضافه می‌شود تا حس بصری خروجی‌ها یکدست بماند.</div>
              </div>
              <label class="ps-toggle" aria-label="فعال‌سازی حفظ هویت برند">
                <input type="hidden" name="brand_identity_enabled" value="0">
                <input type="checkbox" name="brand_identity_enabled" value="1" data-brand-identity-toggle @checked($brandIdentityEnabled)>
                <span aria-hidden="true"></span>
              </label>
            </div>
            <div class="ps-field mt-3" data-brand-identity-prompt-wrap @if(!$brandIdentityEnabled) hidden @endif>
              <label for="sp-brand-identity-prompt">پرامپت ثابت حفظ هویت برند</label>
              <textarea id="sp-brand-identity-prompt" name="brand_identity_prompt" class="input-pro" dir="ltr" rows="5" maxlength="2000" placeholder="Keep one coherent brand identity across the complete image set...">{{ $brandIdentityPrompt }}</textarea>
              <div class="ps-hint">این دستور جای «حفظ خود محصول» را نمی‌گیرد؛ شکل، رنگ، لوگو و بسته‌بندی محصول همیشه با قانون مستقل وفاداری محصول محافظت می‌شوند.</div>
            </div>
          </div>
        </section>

        {{-- ۳. شات‌ها + پیش‌نمایش --}}
        <section class="content-card p-5">
          <div class="flex items-start justify-between flex-wrap gap-3">
            <div>
              <div class="ps-card-title"><i class="fa-solid fa-camera-retro"></i> ۳. شات‌های پک</div>
              <div class="ps-card-desc">تیک «فعال» = در صفحه‌ی ساخت دیده می‌شود. تیک «در بسته‌ی آماده» = از پیش انتخاب‌شده برای کاربر عادی (پیشنهاد: ۴ شات). کردیت خالی = کردیت پیش‌فرض کتابخانه.</div>
            </div>
            <a href="{{ route('admin.product-shots.index', ['tab' => 'library']) }}" class="btn-pro btn-pro-ghost" target="_blank" rel="noopener"><i class="fa-solid fa-layer-group text-[11px]"></i> کتابخانه‌ی شات</a>
          </div>

          <div class="ps-preview-box mb-4">
            <div class="ps-grid">
              <div class="ps-field">
                <label for="sp-test-image">عکس تست محصول برای پیش‌نمایش</label>
                <input id="sp-test-image" type="file" accept="image/*" class="input-pro" style="padding-top:7px;">
                <div class="ps-hint">فقط برای پیش‌نمایش؛ به کاربر نمایش داده نمی‌شود. پیش‌نمایش سفارش نمی‌سازد و کردیت کم نمی‌کند، ولی هزینه‌ی واقعی API دارد.</div>
              </div>
              <div class="ps-field">
                <label for="sp-test-ratio">نسبت پیش‌نمایش</label>
                <select id="sp-test-ratio" class="input-pro">@foreach($aspectRatios as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
              </div>
            </div>
            <div id="sp-preview-status" class="ps-hint" aria-live="polite"></div>
          </div>

          <div class="ps-shot-list" id="sp-shot-list">
            @foreach($shots as $index => $shot)
              @php
                $ps = $productShots->get($shot->id);
                $isGeneralDefault = in_array((int) $shot->id, array_map('intval', $generalShotIds ?? []), true);
                $enabled = (bool) old("shots.{$shot->id}.enabled", $ps ? $ps->enabled : ($product ? false : $isGeneralDefault));
                $isDefault = (bool) old("shots.{$shot->id}.is_default", $ps ? $ps->is_default : ($product ? false : ($isGeneralDefault && $index < 4)));
                $sample = $ps?->sampleImageUrl() ?? $shot->sampleImageUrl();
              @endphp
              <div class="ps-shot-row {{ $enabled ? '' : 'is-off' }}" data-shot-row data-shot-id="{{ $shot->id }}" data-default-credits="{{ $shot->default_credits }}">
                <div class="ps-thumb" data-shot-thumb>@if($sample)<img src="{{ $sample }}" alt="">@else<i class="fa-solid fa-image"></i>@endif</div>
                <div class="min-w-0">
                  <div class="ps-shot-name">{{ $shot->name_fa }} @unless($shot->is_active)<span class="badge-pro badge-neutral">غیرفعال در کتابخانه</span>@endunless</div>
                  <div class="ps-shot-desc">{{ $shot->description_fa }}</div>
                  <div class="ps-tags">@foreach(array_slice($shot->tokenLabels(), 0, 5) as $label)<span class="ps-tag">{{ $label }}</span>@endforeach</div>
                  <div class="ps-hint" data-shot-result></div>
                </div>
                <div class="ps-shot-controls">
                  <input type="hidden" name="shots[{{ $shot->id }}][enabled]" value="0">
                  <label class="ps-check"><input type="checkbox" name="shots[{{ $shot->id }}][enabled]" value="1" data-shot-enabled @checked($enabled)> فعال</label>
                  <input type="hidden" name="shots[{{ $shot->id }}][is_default]" value="0">
                  <label class="ps-check"><input type="checkbox" name="shots[{{ $shot->id }}][is_default]" value="1" data-shot-default @checked($isDefault)> در بسته‌ی آماده</label>
                  <input type="number" min="0" max="1000" name="shots[{{ $shot->id }}][credits]" class="input-pro" placeholder="{{ $shot->default_credits }}" value="{{ old("shots.{$shot->id}.credits", $ps?->credits_override) }}" aria-label="کردیت {{ $shot->name_fa }}" data-shot-credits>
                  <input type="hidden" name="shots[{{ $shot->id }}][sort]" value="{{ $index }}">
                  <input type="hidden" name="shots[{{ $shot->id }}][sample_path]" value="" data-shot-sample>
                  <button type="button" class="btn-pro btn-pro-ghost" data-shot-preview="{{ $shot->id }}"><i class="fa-solid fa-wand-magic-sparkles text-[11px]"></i> پیش‌نمایش</button>
                </div>
              </div>
            @endforeach
          </div>
        </section>
      </div>

      <div class="ps-sticky-bar">
        <div class="text-[12px]" style="color:var(--text-main);" id="sp-summary" aria-live="polite"></div>
        <div class="flex gap-2 flex-wrap">
          <button type="submit" class="btn-pro btn-pro-ghost" data-status="draft"><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره‌ی پیش‌نویس</button>
          <button type="submit" class="btn-pro btn-pro-primary" data-status="active"><i class="fa-solid fa-rocket text-[11px]"></i> {{ $product && $product->status === 'active' ? 'ذخیره و انتشار' : 'انتشار' }}</button>
        </div>
      </div>
    </form>
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  var form = document.getElementById('shot-product-form');
  var csrf = form.querySelector('input[name=_token]').value;
  var previewUrl = @json(route('admin.product-shots.preview'));
  var status = document.getElementById('sp-preview-status');
  var fa = function (n) { return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  var sourcePath = null;

  function summary() {
    var rows = form.querySelectorAll('[data-shot-row]');
    var enabled = 0, packCount = 0, packCredits = 0;
    rows.forEach(function (row) {
      var on = row.querySelector('[data-shot-enabled]').checked;
      var def = row.querySelector('[data-shot-default]');
      row.classList.toggle('is-off', !on);
      def.disabled = !on;
      if (!on) return;
      enabled++;
      if (def.checked) {
        packCount++;
        var c = row.querySelector('[data-shot-credits]').value;
        packCredits += parseInt(c === '' ? row.getAttribute('data-default-credits') : c, 10) || 0;
      }
    });
    document.getElementById('sp-summary').textContent = fa(enabled) + ' شات فعال · بسته‌ی آماده: ' + fa(packCount) + ' شات، ' + fa(packCredits) + ' کردیت';
  }
  form.addEventListener('change', summary);
  form.addEventListener('input', function (e) { if (e.target.matches('[data-shot-credits]')) summary(); });
  summary();

  form.querySelectorAll('button[data-status]').forEach(function (b) {
    b.addEventListener('click', function () { document.getElementById('sp-status').value = b.getAttribute('data-status'); });
  });
  document.getElementById('sp-test-image').addEventListener('change', function () { sourcePath = null; });

  var brandToggle = form.querySelector('[data-brand-identity-toggle]');
  var brandPromptWrap = form.querySelector('[data-brand-identity-prompt-wrap]');
  function syncBrandIdentity() {
    if (brandPromptWrap) brandPromptWrap.hidden = !brandToggle.checked;
  }
  if (brandToggle) brandToggle.addEventListener('change', syncBrandIdentity);
  syncBrandIdentity();

  form.querySelectorAll('[data-shot-preview]').forEach(function (button) {
    button.addEventListener('click', function () {
      var row = button.closest('[data-shot-row]');
      var file = document.getElementById('sp-test-image').files[0];
      if (!file && !sourcePath) { status.textContent = 'اول یک عکس تست از محصول انتخاب کنید.'; document.getElementById('sp-test-image').focus(); return; }
      var data = new FormData();
      data.append('shot_id', button.getAttribute('data-shot-preview'));
      data.append('ai_model_id', document.getElementById('sp-model').value);
      data.append('product_description', document.getElementById('sp-pdesc').value);
      data.append('brand_palette', document.getElementById('sp-palette').value);
      data.append('brand_style', document.getElementById('sp-style').value);
      data.append('brand_identity_enabled', brandToggle && brandToggle.checked ? '1' : '0');
      data.append('brand_identity_prompt', document.getElementById('sp-brand-identity-prompt').value);
      data.append('aspect_ratio', document.getElementById('sp-test-ratio').value);
      if (sourcePath) data.append('image_path', sourcePath); else data.append('image', file);
      var result = row.querySelector('[data-shot-result]');
      button.disabled = true;
      result.innerHTML = '<span class="ps-spinner"></span> در حال ساخت پیش‌نمایش… (تا یک دقیقه)';
      fetch(previewUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: data, credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
          if (res.body.source_path) sourcePath = res.body.source_path;
          if (!res.ok || !res.body.ok) { result.textContent = res.body.message || 'پیش‌نمایش ساخته نشد.'; return; }
          var b = res.body;
          var qc = b.qc && b.qc.checked ? (' · وفاداری: ' + fa(b.qc.score) + ' از ۵' + (b.qc.passed ? '' : ' (رد)')) : '';
          result.innerHTML = '';
          var wrap = document.createElement('div'); wrap.className = 'ps-preview-result';
          wrap.innerHTML = '<figure><img alt="عکس تست"><figcaption>قبل</figcaption></figure><figure><img alt="پیش‌نمایش شات"><figcaption>بعد</figcaption></figure>';
          wrap.querySelectorAll('img')[0].src = b.source_url;
          wrap.querySelectorAll('img')[1].src = b.image_url;
          var meta = document.createElement('div'); meta.className = 'ps-hint';
          meta.textContent = 'هزینه‌ی API: $' + b.cost_usd + qc + ' · مدل: ' + (b.model || '—');
          var use = document.createElement('button'); use.type = 'button'; use.className = 'btn-pro btn-pro-ghost mt-2';
          use.innerHTML = '<i class="fa-solid fa-image text-[11px]"></i> استفاده به‌عنوان تصویر نمونه‌ی این شات';
          use.addEventListener('click', function () {
            row.querySelector('[data-shot-sample]').value = b.image_path;
            row.querySelector('[data-shot-thumb]').innerHTML = '<img alt="">';
            row.querySelector('[data-shot-thumb] img').src = b.image_url;
            use.disabled = true; use.textContent = 'با ذخیره‌ی فرم، نمونه ثبت می‌شود ✓';
          });
          result.appendChild(wrap); result.appendChild(meta); result.appendChild(use);
        })
        .catch(function () { result.textContent = 'ارتباط با سرور برقرار نشد.'; })
        .finally(function () { button.disabled = false; });
    });
  });
})();
</script>
@endsection
