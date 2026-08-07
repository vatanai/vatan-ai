{{-- آزمایشگاه مدل‌های هوش مصنوعی — رابط آزمایش مدل‌ها در فرم محصول --}}
@php
  $labSampleImage = asset('assets/img/images.jpg');
  $labExchangeRateToman = (float) data_get($exchange ?? [], 'rate', 0) / 10;
  $labTested = (bool) ($labTested ?? false);
  $labProviderLabels = [
    'liara' => 'لیارا',
    'openrouter' => 'OpenRouter',
    'fal' => 'Fal.ai',
    'replicate' => 'Replicate',
  ];
  $labProviderEnglishLabels = [
    'liara' => 'Liara AI',
    'openrouter' => 'OpenRouter',
    'fal' => 'Fal.ai',
    'replicate' => 'Replicate',
  ];
  $labModels = collect($aiModels)->map(function ($model) use ($labProviderLabels, $labProviderEnglishLabels) {
    $provider = $model->provider ?? 'openrouter';
    return [
      'id' => (string) $model->id,
      'modelId' => (string) $model->openrouter_model_id,
      'name' => $model->name,
      'externalId' => $model->externalModelId(),
      'persianName' => $model->name,
      'englishName' => $model->externalModelId(),
      'usage' => $model->taskLabel(),
      'grade' => $model->qualityGradeLabel(),
      'provider' => $provider,
      'providerLabel' => $labProviderLabels[$provider] ?? ($model->provider_name ?? $provider),
      'providerEnglishLabel' => $labProviderEnglishLabels[$provider] ?? ($model->provider_name ?? $provider),
      'costUsd' => $model->cost_per_generation_usd !== null ? (float) $model->cost_per_generation_usd : null,
    ];
  })->values();
  $labProviders = $labModels->groupBy('provider')->map(function ($models, $provider) use ($labProviderLabels, $labProviderEnglishLabels) {
    return [
      'value' => $provider,
      'label' => $labProviderLabels[$provider] ?? $provider,
      'englishLabel' => $labProviderEnglishLabels[$provider] ?? $provider,
      'count' => $models->count(),
    ];
  })->values();
@endphp

@php($labVersion = $labVersion ?? 'V12')
<section class="ai-model-lab" id="ai-model-lab" aria-labelledby="ai-model-lab-title">
  <div class="ai-model-lab-header">
    <div class="ai-model-lab-heading">
      <span class="ai-model-lab-icon"><i class="fa-solid fa-flask-vial"></i></span>
      <div>
        <div class="ai-model-lab-title-row">
          <h3 id="ai-model-lab-title">آزمایشگاه مدل‌های هوش مصنوعی</h3>
          <small class="ai-model-lab-version" dir="ltr">{{ $labVersion }}</small>
        </div>
        <p>چند مدل را انتخاب کنید و کیفیت خروجی و سایز هرکدام را جداگانه ببینید.</p>
      </div>
    </div>
    <div class="ai-model-lab-header-actions">
      <button type="button" class="ai-model-lab-toggle" id="ai-model-lab-toggle" aria-expanded="false" aria-controls="ai-model-lab-drawer" onclick="toggleAiModelLab()">
        <span class="ai-model-lab-toggle-copy" id="ai-model-lab-toggle-copy">روشن کردن آزمایشگاه</span>
        <span class="ai-model-lab-switch" aria-hidden="true"><span></span></span>
        <i class="fa-solid fa-chevron-down ai-model-lab-chevron" aria-hidden="true"></i>
      </button>
      <span class="ai-model-lab-toolbar-item ai-model-lab-tested is-tested" title="وضعیت آزمایش محصول"><i class="fa-solid fa-circle-check"></i> آزمایش شده</span>
    </div>
  </div>

  <div class="ai-model-lab-drawer hidden" id="ai-model-lab-drawer">
    <div class="ai-model-lab-drawer-head">
      <div>
        <div class="ai-model-lab-drawer-title"><i class="fa-solid fa-sliders"></i> مدل‌های مورد آزمایش <span class="ai-model-lab-count" id="ai-model-lab-count">۰ مدل</span></div>
        <div class="ai-model-lab-drawer-help">ابتدا پرووایدر را انتخاب کنید؛ سپس مدل‌های همان پرووایدر نمایش داده می‌شوند.</div>
      </div>
      <div class="ai-model-lab-toolbar">
        <div class="ai-model-lab-evaluation-box">
          <span class="ai-model-lab-evaluation-label">مدل ارزیاب:</span>
          <select id="ai-model-lab-evaluator" class="ai-model-lab-evaluator" aria-label="مدل هوش مصنوعی ارزیاب">
            <option value="gpt-4o-mini">GPT-4o mini</option>
            @foreach($labModels as $labModel)
              <option value="catalog-{{ $labModel['id'] }}">{{ $labModel['persianName'] }} · {{ $labModel['englishName'] }} · {{ $labModel['providerLabel'] }}</option>
            @endforeach
          </select>
          <button type="button" class="ai-model-lab-evaluate" onclick="evaluateAiModelLab()"><i class="fa-solid fa-ranking-star"></i> ارزیابی خروجی‌ها</button>
        </div>
        <div class="ai-model-lab-action-group">
          <button type="button" class="ai-model-lab-toolbar-item ai-model-lab-reset" onclick="resetAiModelLab()"><i class="fa-solid fa-rotate-left"></i> ریست تنظیمات</button>
          <div class="ai-model-lab-defaults-shell">
            <button type="button" class="ai-model-lab-toolbar-item" onclick="toggleAiModelLabDefaults(event)" aria-expanded="false"><i class="fa-solid fa-wand-magic-sparkles"></i> پیش‌فرض <i class="fa-solid fa-chevron-down"></i></button>
            <div class="ai-model-lab-defaults-menu hidden" id="ai-model-lab-defaults-menu" role="menu">
              <button type="button" data-default-profile="professional-face" onclick="applyAiModelLabDefaults('professional-face')">مدل حرفه‌ای چهره</button>
              <button type="button" data-default-profile="normal-face" onclick="applyAiModelLabDefaults('normal-face')">مدل معمولی چهره</button>
              <button type="button" data-default-profile="business" onclick="applyAiModelLabDefaults('business')">مدل کسب و کار</button>
            </div>
          </div>
          <button type="button" class="ai-model-lab-run" onclick="runAiModelLab()"><i class="fa-solid fa-play"></i> آزمایش کن</button>
        </div>
      </div>
    </div>

    <div class="ai-model-lab-rows" id="ai-model-lab-rows"></div>

    <div class="ai-model-lab-footer">
      <button type="button" class="ai-model-lab-add" onclick="addAiModelLabRow()">
        <i class="fa-solid fa-plus"></i> افزودن مدل دیگر
      </button>
      <span><i class="fa-solid fa-circle-info"></i> این تنظیمات فعلاً ذخیره یا اجرا نمی‌شوند.</span>
    </div>

    <section class="ai-model-lab-output" aria-labelledby="ai-model-lab-output-title">
      <div class="ai-model-lab-output-heading">
        <div>
          <div class="ai-model-lab-drawer-title" id="ai-model-lab-output-title"><i class="fa-solid fa-image"></i> خروجی</div>
          <div class="ai-model-lab-drawer-help">پس از اجرای آزمایش، تصویر و جزئیات واقعی مدل در این قاب نمایش داده می‌شود.</div>
        </div>
        <span class="ai-model-lab-output-status" id="ai-model-lab-output-status">در انتظار آزمایش</span>
      </div>
      {{-- تصاویر نمونه موقتاً برای بررسی چیدمان نمایش داده می‌شوند و بعد از تأیید حذف خواهند شد. --}}
      <div class="ai-model-lab-output-grid" id="ai-model-lab-output-grid">
        <article class="ai-model-lab-output-card ai-model-lab-input-card">
          <div class="ai-model-lab-card-head">
            <span class="ai-model-lab-card-head-title">تصویر ورودی آزمایش</span>
            <span class="ai-model-lab-card-head-english" dir="ltr">—</span><span class="ai-model-lab-card-head-persian">تصویر ورودی</span>
          </div>
          <div class="ai-model-lab-output-frame ai-model-lab-input-frame" role="button" tabindex="0" aria-label="آپلود یا تغییر تصویر ورودی آزمایش" onclick="document.getElementById('ai-model-lab-upload-input')?.click()" onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); document.getElementById('ai-model-lab-upload-input')?.click(); }">
            <img src="{{ $labSampleImage }}" alt="تصویر ورودی آزمایش" loading="lazy" data-lab-input-output>
            <span class="ai-model-lab-input-frame-hint"><i class="fa-solid fa-upload"></i> تغییر تصویر</span>
          </div>
          <input type="file" id="ai-model-lab-upload-input" accept="image/jpeg,image/png,image/webp" multiple>
          <div class="ai-model-lab-output-meta ai-model-lab-input-meta">
            <div><strong><span>ابعاد</span><b data-lab-input-meta="dimensions">۱۲۰۰ × ۱۶۰۰</b></strong></div>
            <div><strong><span>نسبت</span><b data-lab-input-meta="ratio">۴:۵ عمودی</b></strong></div>
            <div><strong><span>حجم</span><b data-lab-input-meta="size">۳۲۰ کیلوبایت</b></strong></div>
            <div><strong><span>فرمت</span><b data-lab-input-meta="type">JPEG</b></strong></div>
            <div><strong><span>رنگ</span><b data-lab-input-meta="color">RGB</b></strong></div>
          </div>
        </article>
        <div class="ai-model-lab-output-model-cards" id="ai-model-lab-output-model-cards"></div>
      </div>
    </section>

    <section class="ai-model-lab-calculation" aria-labelledby="ai-model-lab-calculation-title" data-lab-record data-product-id="{{ $product?->id ?? '' }}">
      <div class="ai-model-lab-calculation-head">
        <div>
          <div class="ai-model-lab-drawer-title" id="ai-model-lab-calculation-title"><i class="fa-solid fa-table-list"></i> جدول محاسبه دقیق</div>
          <div class="ai-model-lab-drawer-help">تمام اطلاعات محصول، مدل‌های آزمایش‌شده، هزینه، زمان ساخت و رتبه‌بندی در این جدول قابل انتقال به لیست آزمایش‌هاست.</div>
        </div>
        <span class="ai-model-lab-calculation-status" id="ai-model-lab-calculation-status">آماده بررسی</span>
      </div>
      <section class="ai-model-lab-manager-score" aria-labelledby="ai-model-lab-manager-score-title">
        <div class="ai-model-lab-manager-score-title" id="ai-model-lab-manager-score-title"><i class="fa-solid fa-user-check"></i> نمره مدیر سایت</div>
        <div class="ai-model-lab-manager-score-help">برای هر خروجی، نمره و اولویت استفاده را انتخاب کنید؛ همه مقادیر بلافاصله در جدول محاسبه دقیق ثبت می‌شوند.</div>
        <div class="ai-model-lab-table-wrap">
          <table class="ai-model-lab-manager-table">
            <thead><tr><th>خروجی مدل</th><th>نمره کلی (۱ تا ۱۰)</th><th>شباهت به مرجع</th><th>کیفیت جزئیات</th><th>اولویت استفاده</th></tr></thead>
            <tbody id="ai-model-lab-manager-score-rows"><tr><td colspan="5" class="ai-model-lab-table-empty">پس از انتخاب مدل، گزینه‌های نمره‌دهی اینجا نمایش داده می‌شوند.</td></tr></tbody>
          </table>
        </div>
      </section>
      <div class="ai-model-lab-table-wrap">
        <table class="ai-model-lab-calculation-table" data-report-table="ai-lab-results">
          <thead>
            <tr><th>مدل</th><th>پرووایدر</th><th>کیفیت / سایز</th><th>حفظ چهره</th><th>دلار</th><th>تومان</th><th>زمان ساخت (ثانیه)</th><th>توکن</th><th>تلاش مجدد</th><th>امتیاز ارزیاب</th><th>نمره مدیر</th><th>شباهت مدیر</th><th>کیفیت جزئیات</th><th>اولویت استفاده</th><th>اولویت ارزیابی</th></tr>
          </thead>
          <tbody id="ai-model-lab-calculation-rows"></tbody>
          <tfoot>
            <tr><th colspan="2">نام محصول</th><td colspan="3" data-lab-summary="product-name">{{ $product?->name_fa ?? 'محصول در حال ساخت' }}</td><th>کد محصول</th><td colspan="9" dir="ltr" data-lab-summary="product-code">{{ $product?->product_code ?? '—' }}</td></tr>
            <tr><th colspan="2">تعداد مدل‌های آزمایش‌شده</th><td colspan="3" data-lab-summary="model-count">۰ مدل</td><th>تصویر ورودی</th><td colspan="9" data-lab-summary="input-image">تصویر نمونه</td></tr>
            <tr><th colspan="2">تاریخ و زمان آزمایش</th><td colspan="3" data-lab-summary="tested-at">۱۴۰۴/۰۴/۲۲ · ۱۲:۴۸</td><th>هزینه کل دلار</th><td colspan="2" dir="ltr" data-lab-summary="total-usd">۰٫۰۰۰۰</td><th>هزینه کل تومان</th><td colspan="6" dir="ltr" data-lab-summary="total-toman">۰</td></tr>
            <tr><th colspan="2">شناسه گزارش</th><td colspan="3" dir="ltr" data-lab-summary="report-id">LAB-DRAFT-{{ $product?->id ?? 'NEW' }}</td><th>وضعیت گزارش</th><td colspan="9" data-lab-summary="report-status">آماده بررسی</td></tr>
            <tr><th colspan="2">مدل ارزیاب مقایسه</th><td colspan="13" data-lab-summary="evaluator">GPT-4o mini</td></tr>
          </tfoot>
        </table>
      </div>
    </section>
  </div>
</section>

<style>
  #ai-model-lab { overflow:hidden; border:1px solid var(--b1); border-radius:14px; background:var(--s2); }
  #ai-model-lab .ai-model-lab-header { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:16px 18px; }
  #ai-model-lab .ai-model-lab-header-actions { display:flex; align-items:center; gap:7px; flex-shrink:0; direction:ltr; }
  #ai-model-lab .ai-model-lab-header-actions .ai-model-lab-tested { direction:rtl; }
  #ai-model-lab .ai-model-lab-heading { display:flex; align-items:center; gap:11px; min-width:0; }
  #ai-model-lab .ai-model-lab-icon { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; flex-shrink:0; border-radius:11px; color:var(--accent); background:var(--primary-l); font-size:15px; }
  #ai-model-lab .ai-model-lab-title-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  #ai-model-lab h3 { margin:0; color:var(--text); font-size:13px; font-weight:800; }
  #ai-model-lab p { margin:4px 0 0; color:var(--text3); font-size:10.5px; line-height:1.7; }
  #ai-model-lab .ai-model-lab-ui-badge { padding:3px 7px; border:1px solid var(--b1); border-radius:999px; color:var(--text3); background:var(--s1); font-size:9px; font-weight:700; }
  #ai-model-lab .ai-model-lab-toggle { display:inline-flex; align-items:center; justify-content:center; gap:8px; flex-shrink:0; min-width:180px; min-height:34px; padding:6px 10px 6px 12px; border:1px solid var(--b1); border-radius:9px; color:var(--text2); background:var(--s1); font-size:10px; font-weight:700; cursor:pointer; transition:all .18s ease; }
  #ai-model-lab .ai-model-lab-toggle:hover, #ai-model-lab .ai-model-lab-toggle[aria-expanded="true"] { border-color:var(--accent); color:var(--text); }
  #ai-model-lab .ai-model-lab-toggle-copy { white-space:nowrap; }
  #ai-model-lab .ai-model-lab-switch { position:relative; width:29px; height:17px; border-radius:999px; background:var(--b2); transition:background .18s ease; }
  #ai-model-lab .ai-model-lab-switch span { position:absolute; top:3px; right:3px; width:11px; height:11px; border-radius:50%; background:var(--text3); transition:transform .18s ease, background .18s ease; }
  #ai-model-lab .ai-model-lab-toggle[aria-expanded="true"] .ai-model-lab-switch { background:var(--green); }
  #ai-model-lab .ai-model-lab-toggle[aria-expanded="true"] .ai-model-lab-switch span { background:var(--s1); transform:translateX(-12px); }
  #ai-model-lab .ai-model-lab-chevron { color:var(--text3); font-size:9px; transition:transform .18s ease; }
  #ai-model-lab .ai-model-lab-toggle[aria-expanded="true"] .ai-model-lab-chevron { transform:rotate(180deg); }
  #ai-model-lab .ai-model-lab-version { padding:2px 5px; border-radius:5px; color:var(--text3); background:var(--input-bg); font-size:8.8px; font-weight:800; }
  #ai-model-lab .ai-model-lab-drawer { padding:0 18px 16px; border-top:1px solid var(--b1); }
  #ai-model-lab .ai-model-lab-drawer-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 0 12px; }
  #ai-model-lab .ai-model-lab-drawer-title { display:flex; align-items:center; gap:7px; color:var(--text2); font-size:11px; font-weight:800; }
  #ai-model-lab .ai-model-lab-drawer-title i { color:var(--accent); }
  #ai-model-lab .ai-model-lab-drawer-help { margin-top:4px; color:var(--text3); font-size:10px; }
  #ai-model-lab .ai-model-lab-count { display:inline-flex; align-items:center; margin-right:4px; padding:4px 8px; border-radius:999px; color:var(--accent); background:var(--primary-l); font-size:10px; font-weight:800; vertical-align:middle; }
  #ai-model-lab .ai-model-lab-toolbar { display:flex; align-items:center; justify-content:flex-end; gap:5px; flex-wrap:wrap; }
  #ai-model-lab .ai-model-lab-evaluator-control { display:flex; align-items:center; gap:5px; min-height:28px; }
  #ai-model-lab .ai-model-lab-evaluator-control > span { color:var(--text3); font-size:8px; font-weight:800; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-toolbar-item { display:inline-flex; align-items:center; gap:5px; min-height:28px; padding:4px 7px; border:1px solid var(--b1); border-radius:7px; color:var(--text3); background:var(--s1); font-family:inherit; font-size:8.5px; font-weight:700; white-space:nowrap; }
  #ai-model-lab button.ai-model-lab-toolbar-item { cursor:pointer; }
  #ai-model-lab button.ai-model-lab-toolbar-item:hover { border-color:var(--accent); color:var(--text); }
  #ai-model-lab .ai-model-lab-toolbar-item i { font-size:9px; }
  #ai-model-lab .ai-model-lab-tested.is-tested { min-height:34px; box-sizing:border-box; border-color:var(--green); color:var(--green); }
  #ai-model-lab .ai-model-lab-reset { border-color:var(--warning); color:var(--warning); background:var(--warning-l); }
  #ai-model-lab .ai-model-lab-reset:hover { border-color:var(--warning); color:var(--warning); background:var(--warning-l); }
  #ai-model-lab .ai-model-lab-evaluation-box { display:flex; align-items:center; gap:6px; width:max-content; min-height:37px; height:37px; box-sizing:border-box; padding:3px 5px; border:1px solid var(--b1); border-radius:8px; background:var(--s1); margin-inline:4px; direction:rtl; }
  #ai-model-lab .ai-model-lab-evaluation-label { color:var(--text2); font-size:8.5px; font-weight:800; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-action-group { display:flex; align-items:center; gap:5px; direction:rtl; }
  #ai-model-lab .ai-model-lab-action-group > .ai-model-lab-toolbar-item,
  #ai-model-lab .ai-model-lab-action-group > .ai-model-lab-defaults-shell > button,
  #ai-model-lab .ai-model-lab-action-group > .ai-model-lab-run { min-height:37px; height:37px; box-sizing:border-box; }
  #ai-model-lab .ai-model-lab-defaults-shell { position:relative; }
  #ai-model-lab .ai-model-lab-defaults-shell > button { direction:rtl; }
  #ai-model-lab .ai-model-lab-defaults-menu { position:absolute; z-index:50; top:calc(100% + 6px); right:0; min-width:185px; padding:5px; border:1px solid var(--b1); border-radius:9px; background:var(--s2); box-shadow:var(--shadow-card); }
  #ai-model-lab .ai-model-lab-defaults-menu button { display:block; width:100%; padding:8px 9px; border:0; border-bottom:1px solid var(--b1); color:var(--text2); background:transparent; font-family:inherit; font-size:9px; text-align:right; cursor:pointer; }
  #ai-model-lab .ai-model-lab-defaults-menu button:last-child { border-bottom:0; }
  #ai-model-lab .ai-model-lab-defaults-menu button:hover { color:var(--text); background:var(--primary-l); }
  #ai-model-lab .ai-model-lab-evaluate { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-width:112px; height:32px; padding:0 12px; border:1px solid var(--primary); border-radius:8px; color:var(--primary); background:var(--primary-l); font-family:inherit; font-size:9.5px; font-weight:900; cursor:pointer; transition:all .18s ease; }
  #ai-model-lab .ai-model-lab-evaluate:hover { color:var(--s2); background:var(--primary); }
  #ai-model-lab .ai-model-lab-run { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-width:101px; height:37px; padding:0 15px; border:1px solid var(--green); border-radius:8px; color:var(--green); background:var(--green-l); font-family:inherit; font-size:11.55px; font-weight:600; cursor:pointer; transition:all .18s ease; }
  #ai-model-lab .ai-model-lab-run:hover { color:var(--s2); background:var(--green); }
  #ai-model-lab .ai-model-lab-rows { display:grid; gap:9px; }
  #ai-model-lab .ai-model-lab-row { display:grid; grid-template-columns:minmax(150px, .9fr) minmax(220px, 1.45fr) minmax(130px, .7fr) minmax(130px, .75fr) minmax(105px, .55fr) 32px; align-items:end; gap:9px; padding:12px; border:1px solid var(--b1); border-radius:11px; background:var(--s1); }
  #ai-model-lab .ai-model-lab-field { display:flex; flex-direction:column; gap:6px; min-width:0; }
  #ai-model-lab .ai-model-lab-field > span { color:var(--text3); font-size:9.5px; font-weight:700; }
  #ai-model-lab .ai-model-lab-preserve-field { align-self:stretch; }
  #ai-model-lab .ai-model-lab-checkbox-control { display:flex; align-items:center; justify-content:center; gap:5px; height:37px; padding:0 6px; border:1px solid var(--b1); border-radius:8px; background:var(--s2); color:var(--text2); font-size:8.5px; }
  #ai-model-lab .ai-model-lab-checkbox-control input { width:14px; height:14px; margin:0; accent-color:var(--green); cursor:pointer; }
  #ai-model-lab .ai-model-lab-checkbox-control b { font-weight:800; }
  #ai-model-lab select { width:100%; height:37px; min-width:0; padding:0 9px; border:1px solid var(--b1); border-radius:8px; outline:none; color:var(--text); background:var(--s2); font-family:inherit; font-size:10.5px; cursor:pointer; }
  #ai-model-lab select:hover, #ai-model-lab select:focus { border-color:var(--accent); }
  #ai-model-lab select:disabled { opacity:.45; cursor:not-allowed; }
  #ai-model-lab .ai-model-lab-model-shell { position:relative; }
  #ai-model-lab .ai-model-lab-model-trigger { display:flex; align-items:center; justify-content:space-between; gap:8px; width:100%; height:37px; padding:0 9px; border:1px solid var(--b1); border-radius:8px; color:var(--text); background:var(--s2); font-family:inherit; font-size:10.5px; text-align:right; cursor:pointer; }
  #ai-model-lab .ai-model-lab-model-trigger:hover, #ai-model-lab .ai-model-lab-model-trigger:focus { border-color:var(--accent); outline:none; }
  #ai-model-lab .ai-model-lab-model-trigger:disabled { opacity:.45; cursor:not-allowed; }
  #ai-model-lab .ai-model-lab-model-trigger i { color:var(--text3); font-size:9px; }
  #ai-model-lab .ai-model-lab-model-menu { position:absolute; z-index:45; top:calc(100% + 6px); right:0; left:0; min-width:430px; max-height:280px; overflow:auto; padding:6px; border:1px solid var(--b1); border-radius:10px; background:var(--s2); box-shadow:var(--shadow-card); }
  #ai-model-lab .ai-model-lab-model-head, #ai-model-lab .ai-model-lab-model-option { display:grid; grid-template-columns:1.6fr 1.2fr .7fr; align-items:center; gap:8px; }
  #ai-model-lab .ai-model-lab-model-head { padding:5px 7px; border-bottom:1px solid var(--b1); color:var(--text3); font-size:8px; font-weight:800; }
  #ai-model-lab .ai-model-lab-model-option { width:100%; padding:8px 7px; border:0; border-bottom:1px solid var(--b1); color:var(--text2); background:transparent; font-family:inherit; font-size:9px; line-height:1.5; text-align:right; cursor:pointer; }
  #ai-model-lab .ai-model-lab-model-option:last-child { border-bottom:0; }
  #ai-model-lab .ai-model-lab-model-option:hover, #ai-model-lab .ai-model-lab-model-option.is-selected { color:var(--text); background:var(--primary-l); }
  #ai-model-lab .ai-model-lab-model-option > span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-model-option > span:first-child { display:flex; flex-direction:column; gap:1px; }
  #ai-model-lab .ai-model-lab-model-option > span:first-child small { color:var(--text3); font-size:8px; text-align:left; }
  #ai-model-lab .ai-model-lab-model-option .model-quality-grade { color:var(--warning); font-weight:800; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-remove { width:32px; height:32px; border:1px solid var(--b1); border-radius:8px; color:var(--text3); background:transparent; cursor:pointer; transition:all .18s ease; }
  #ai-model-lab .ai-model-lab-remove:hover { border-color:var(--danger); color:var(--danger); background:var(--danger-l); }
  #ai-model-lab .ai-model-lab-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:11px; }
  #ai-model-lab .ai-model-lab-add { display:inline-flex; align-items:center; gap:6px; height:34px; padding:0 11px; border:1px solid var(--primary-m); border-radius:8px; color:var(--primary); background:var(--primary-l); font-size:10px; font-weight:800; cursor:pointer; }
  #ai-model-lab .ai-model-lab-add:hover { border-color:var(--primary); }
  #ai-model-lab .ai-model-lab-footer > span { color:var(--text3); font-size:9.5px; }
  #ai-model-lab .ai-model-lab-footer > span i { margin-left:4px; color:var(--accent); }
  #ai-model-lab .ai-model-lab-input { margin-top:14px; padding-top:14px; border-top:1px solid var(--b1); }
  #ai-model-lab .ai-model-lab-input-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; }
  #ai-model-lab .ai-model-lab-upload { display:inline-flex; align-items:center; gap:6px; height:31px; padding:0 9px; border:1px solid var(--b1); border-radius:8px; color:var(--text2); background:var(--s1); font-size:9.5px; font-weight:800; cursor:pointer; }
  #ai-model-lab .ai-model-lab-upload:hover { border-color:var(--accent); color:var(--text); }
  #ai-model-lab .ai-model-lab-upload input { display:none; }
  #ai-model-lab .ai-model-lab-input-gallery { display:flex; gap:8px; overflow-x:auto; padding:2px 1px; }
  #ai-model-lab .ai-model-lab-input-item { position:relative; width:78px; height:78px; flex:0 0 78px; overflow:hidden; border:2px solid var(--b1); border-radius:9px; background:var(--input-bg); cursor:pointer; }
  #ai-model-lab .ai-model-lab-input-item.is-selected { border-color:var(--accent); box-shadow:0 0 0 2px var(--primary-l); }
  #ai-model-lab .ai-model-lab-input-item img { display:block; width:100%; height:100%; object-fit:cover; }
  #ai-model-lab .ai-model-lab-input-item .ai-model-lab-input-check { position:absolute; right:5px; bottom:5px; display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border:1px solid var(--b1); border-radius:5px; color:transparent; background:var(--s2); font-size:9px; }
  #ai-model-lab .ai-model-lab-input-item.is-selected .ai-model-lab-input-check { border-color:var(--primary); color:var(--accent); background:var(--primary); }
  #ai-model-lab .ai-model-lab-input-remove { position:absolute; top:4px; left:4px; display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border:1px solid var(--b1); border-radius:5px; color:var(--text2); background:var(--s2); font-size:8px; cursor:pointer; }
  #ai-model-lab .ai-model-lab-input-remove:hover { color:var(--danger); border-color:var(--danger); }
  #ai-model-lab .ai-model-lab-input-name { position:absolute; right:3px; bottom:3px; left:3px; overflow:hidden; padding:2px 3px; border-radius:4px; color:var(--text); background:var(--s2); font-size:7px; text-overflow:ellipsis; white-space:nowrap; text-align:center; }
  #ai-model-lab .ai-model-lab-input-empty { display:flex; align-items:center; justify-content:center; gap:6px; min-height:68px; border:1px dashed var(--b1); border-radius:9px; color:var(--text3); font-size:9.5px; }
  #ai-model-lab .ai-model-lab-output { margin-top:14px; padding-top:14px; border-top:1px solid var(--b1); }
  #ai-model-lab .ai-model-lab-output-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; }
  #ai-model-lab .ai-model-lab-output-status { flex-shrink:0; padding:4px 8px; border:1px solid var(--b1); border-radius:999px; color:var(--text3); background:var(--s1); font-size:9px; font-weight:700; }
  #ai-model-lab .ai-model-lab-output-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; direction:rtl; }
  #ai-model-lab .ai-model-lab-output-model-cards { display:contents; }
  #ai-model-lab .ai-model-lab-output-card { min-width:0; overflow:hidden; border:1px solid var(--b1); border-radius:10px; background:var(--s1); }
  #ai-model-lab .ai-model-lab-card-head { position:relative; display:flex; align-items:center; justify-content:space-between; gap:8px; min-height:34px; padding:5px 8px; border-bottom:1px solid var(--b1); color:var(--text2); background:var(--s2); font-size:8.5px; direction:ltr; }
  #ai-model-lab .ai-model-lab-card-head-title { position:absolute; top:50%; left:50%; color:var(--text2); font-size:9px; font-weight:800; transform:translate(-50%, -50%); white-space:nowrap; }
  #ai-model-lab .ai-model-lab-card-head-english { color:var(--text3); font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:8px; }
  #ai-model-lab .ai-model-lab-card-head-persian { margin-left:auto; overflow:hidden; color:var(--text); font-weight:800; text-overflow:ellipsis; white-space:nowrap; direction:rtl; }
  #ai-model-lab .ai-model-lab-output-model-head { display:flex; align-items:center; justify-content:space-between; gap:8px; min-height:26px; padding:4px 8px; border-bottom:1px solid var(--b1); color:var(--text2); background:var(--s2); font-size:8.5px; direction:ltr; }
  #ai-model-lab .ai-model-lab-output-model-head span:first-child { color:var(--text3); font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:8px; }
  #ai-model-lab .ai-model-lab-output-model-head span:last-child { overflow:hidden; font-weight:800; text-overflow:ellipsis; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-output-model-head span:last-child { direction:rtl; }
  #ai-model-lab .ai-model-lab-output-frame { position:relative; display:flex; align-items:center; justify-content:center; aspect-ratio:4/5; overflow:hidden; color:var(--text3); background:var(--input-bg); font-size:9px; }
  #ai-model-lab .ai-model-lab-output-image-label { position:absolute; z-index:2; top:8px; left:50%; padding:2px 7px; color:var(--text3); background:var(--s1); border-radius:5px; font-size:9px; font-weight:800; transform:translateX(-50%); white-space:nowrap; pointer-events:none; }
  #ai-model-lab .ai-model-lab-input-frame { cursor:pointer; }
  #ai-model-lab .ai-model-lab-input-frame:hover .ai-model-lab-input-frame-hint, #ai-model-lab .ai-model-lab-input-frame:focus .ai-model-lab-input-frame-hint { opacity:1; }
  #ai-model-lab .ai-model-lab-output-frame img { display:block; width:100%; height:100%; object-fit:cover; }
  #ai-model-lab .ai-model-lab-input-frame-hint { position:absolute; top:50%; left:50%; display:inline-flex; align-items:center; gap:5px; padding:6px 8px; border:1px solid var(--b1); border-radius:7px; color:var(--text); background:var(--s2); font-size:8px; font-weight:800; opacity:0; transform:translate(-50%, -50%); transition:opacity .18s ease; }
  #ai-model-lab .ai-model-lab-card-upload { justify-content:center; width:100%; border-width:0 0 1px; border-radius:0; background:var(--s2); }
  #ai-model-lab .ai-model-lab-output-meta { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:5px; padding:7px; }
  #ai-model-lab .ai-model-lab-output-meta > div { display:flex; min-width:0; min-height:28px; align-items:center; justify-content:center; flex-direction:column; padding:3px 3px; border:1px solid var(--b1); border-radius:7px; background:var(--s2); text-align:center; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong { display:block; max-width:100%; margin:0; color:var(--text); font-size:8px; line-height:1.35; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong > span { display:block; max-width:100%; overflow:hidden; color:var(--text); font-size:8.5px; text-overflow:ellipsis; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong > span b { color:var(--text); font-weight:800; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong > b { display:block; margin-top:3px; color:var(--text); font-size:8.5px; font-weight:800; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong.ai-model-lab-money-stack { display:flex; align-items:stretch; justify-content:center; flex-direction:column; gap:2px; margin:0; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong.ai-model-lab-money-stack > span { display:flex; align-items:center; justify-content:center; gap:5px; overflow:visible; color:var(--text3); font-size:8px; line-height:1.3; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong.ai-model-lab-money-stack em { color:var(--text3); font-size:8px; font-style:normal; }
  #ai-model-lab .ai-model-lab-output-meta > div > strong.ai-model-lab-money-stack b { display:inline; margin:0; color:var(--text); font-size:8.5px; }
  #ai-model-lab .ai-model-lab-input-meta { grid-template-columns:repeat(3, minmax(0, 1fr)); padding-top:6px; }
  #ai-model-lab #ai-model-lab-upload-input { display:none; }
  #ai-model-lab .ai-model-lab-output-caption { padding:0 8px 8px; color:var(--text3); font-size:8px; text-align:center; }
  #ai-model-lab .ai-model-lab-calculation { margin-top:14px; padding-top:14px; border-top:1px solid var(--b1); }
  #ai-model-lab .ai-model-lab-calculation-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; }
  #ai-model-lab .ai-model-lab-calculation-status { flex-shrink:0; padding:4px 8px; border:1px solid var(--b1); border-radius:999px; color:var(--text3); background:var(--s1); font-size:9px; font-weight:700; }
  #ai-model-lab .ai-model-lab-calculation-status.is-ready { border-color:var(--green); color:var(--green); background:var(--green-l); }
  #ai-model-lab .ai-model-lab-table-wrap { overflow-x:auto; border:1px solid var(--b1); border-radius:10px; background:var(--s1); }
  #ai-model-lab .ai-model-lab-calculation-table { width:100%; min-width:1370px; border-collapse:collapse; color:var(--text2); background:var(--s1); font-size:8.5px; }
  #ai-model-lab .ai-model-lab-calculation-table th, #ai-model-lab .ai-model-lab-calculation-table td { padding:8px 7px; border:1px solid var(--b1); text-align:right; white-space:nowrap; background:var(--s1); }
  #ai-model-lab .ai-model-lab-calculation-table thead th { color:var(--text3); background:var(--s1); font-size:8px; font-weight:800; }
  #ai-model-lab .ai-model-lab-calculation-table tbody tr:last-child td { border-bottom:1px solid var(--b1); }
  #ai-model-lab .ai-model-lab-calculation-table tfoot th { color:var(--text3); background:var(--s1); font-weight:700; }
  #ai-model-lab .ai-model-lab-calculation-table tfoot td { color:var(--text); background:var(--s1); font-weight:800; }
  #ai-model-lab .ai-model-lab-calculation-table td strong, #ai-model-lab .ai-model-lab-calculation-table td small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  #ai-model-lab .ai-model-lab-calculation-table td strong { color:var(--text); font-size:8.5px; }
  #ai-model-lab .ai-model-lab-calculation-table td small { margin-top:2px; color:var(--text3); font-size:7.5px; direction:ltr; text-align:right; }
  #ai-model-lab .ai-model-lab-calculation-table .lab-rank { color:var(--accent); font-weight:900; }
  #ai-model-lab .ai-model-lab-calculation-table .lab-score { color:var(--warning); font-weight:900; }
  #ai-model-lab .ai-model-lab-table-empty { padding:18px !important; color:var(--text3) !important; text-align:center !important; }
  #ai-model-lab .ai-model-lab-evaluator { width:250px; min-width:250px; height:29px; padding:0 8px; border:1px solid var(--b1); border-radius:7px; color:var(--text); background:var(--s2); font-family:inherit; font-size:9px; }
  #ai-model-lab .ai-model-lab-manager-score { margin:0 0 12px; padding:11px; border:1px solid var(--b1); border-radius:9px; background:var(--s1); }
  #ai-model-lab .ai-model-lab-manager-score-title { display:flex; align-items:center; gap:6px; color:var(--text2); font-size:10px; font-weight:900; }
  #ai-model-lab .ai-model-lab-manager-score-title i { color:var(--accent); }
  #ai-model-lab .ai-model-lab-manager-score-help { margin:4px 0 8px; color:var(--text3); font-size:8.5px; }
  #ai-model-lab .ai-model-lab-manager-table { width:100%; min-width:700px; border-collapse:collapse; color:var(--text2); background:var(--s1); font-size:8.5px; }
  #ai-model-lab .ai-model-lab-manager-table th, #ai-model-lab .ai-model-lab-manager-table td { padding:7px; border:1px solid var(--b1); text-align:right; white-space:nowrap; background:var(--s1); }
  #ai-model-lab .ai-model-lab-manager-table th { color:var(--text3); background:var(--s1); font-size:8px; font-weight:800; }
  #ai-model-lab .ai-model-lab-manager-table td:first-child { color:var(--text); font-weight:800; }
  #ai-model-lab .ai-model-lab-manager-table select { width:100%; min-width:118px; height:28px; padding:0 6px; font-size:8.5px; }
  #ai-model-lab .ai-model-lab-manager-table tr:last-child td { border-bottom:1px solid var(--b1); }
  @media (min-width:981px) { #ai-model-lab .ai-model-lab-toolbar { flex-wrap:nowrap; } }
  @media (max-width: 980px) { #ai-model-lab .ai-model-lab-row { grid-template-columns:repeat(2, minmax(0, 1fr)); } #ai-model-lab .ai-model-lab-remove { align-self:center; } }
  @media (max-width: 640px) { #ai-model-lab .ai-model-lab-header, #ai-model-lab .ai-model-lab-drawer-head, #ai-model-lab .ai-model-lab-footer, #ai-model-lab .ai-model-lab-input-heading, #ai-model-lab .ai-model-lab-output-heading, #ai-model-lab .ai-model-lab-calculation-head { align-items:flex-start; flex-direction:column; } #ai-model-lab .ai-model-lab-header-actions { align-self:stretch; justify-content:center; } #ai-model-lab .ai-model-lab-toolbar { justify-content:flex-start; width:100%; } #ai-model-lab .ai-model-lab-evaluation-box { width:100%; } #ai-model-lab .ai-model-lab-evaluator { min-width:0; flex:1; } #ai-model-lab .ai-model-lab-toggle { align-self:stretch; width:100%; min-width:0; justify-content:center; } #ai-model-lab .ai-model-lab-row { grid-template-columns:1fr; } #ai-model-lab .ai-model-lab-remove { width:100%; } #ai-model-lab .ai-model-lab-output-grid { grid-template-columns:1fr; } #ai-model-lab .ai-model-lab-input-meta { grid-template-columns:repeat(2, minmax(0, 1fr)); } #ai-model-lab .ai-model-lab-model-menu { min-width:0; width:calc(100vw - 80px); } }
</style>

<script>
(function () {
  const root = document.getElementById('ai-model-lab');
  if (!root || root.dataset.ready === '1') return;
  root.dataset.ready = '1';

  const models = @json($labModels);
  const providers = @json($labProviders);
  const sampleImage = @json($labSampleImage);
  const exchangeRateToman = Number(@json($labExchangeRateToman)) || 0;
  const ordinalNames = ['اول', 'دوم', 'سوم', 'چهارم', 'پنجم', 'ششم', 'هفتم', 'هشتم'];
  const qualityOptions = [
    ['480', '۴۸۰'], ['720', '۷۲۰'], ['1080', '۱۰۸۰'], ['1440', '۱۴۴۰'], ['2160', '۲۱۶۰']
  ];
  const sizeOptions = [
    ['4:5', '۴:۵ · عمودی'], ['9:16', '۹:۱۶ · استوری'], ['3:4', '۳:۴ · عمودی'],
    ['1:1', '۱:۱ · مربعی'], ['2:3', '۲:۳ · عمودی'], ['16:9', '۱۶:۹ · افقی'], ['3:2', '۳:۲ · افقی']
  ];
  let rowIndex = 0;
  let labRunReady = false;
  let managerScores = {};

  const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
  const faNumber = value => Number(value).toLocaleString('fa-IR');

  function providerMarkup() {
    return '<option value="">انتخاب پرووایدر</option>' + providers.map(provider =>
      `<option value="${esc(provider.value)}">${esc(provider.label)} · ${esc(provider.englishLabel)} (${faNumber(provider.count)} مدل)</option>`
    ).join('');
  }

  function optionMarkup(options, placeholder, selectedValue) {
    return `<option value="">${placeholder}</option>` + options.map(option => `<option value="${esc(option[0])}" ${option[0] === selectedValue ? 'selected' : ''}>${esc(option[1])}</option>`).join('');
  }

  function modelMenuMarkup(provider) {
    const filtered = models.filter(model => model.provider === provider);
    if (!filtered.length) return '<div class="text-[10px] text-[var(--text3)] text-center py-3">مدلی برای این پرووایدر وجود ندارد.</div>';
    return '<div class="ai-model-lab-model-head"><span>اسم فارسی / اسم انگلیسی</span><span>کاربری</span><span>گرید</span></div>' +
      '<div class="ai-model-lab-model-options">' + filtered.map(model =>
        `<button type="button" class="ai-model-lab-model-option" data-model-key="${esc(model.id)}"><span><b>${esc(model.persianName)}</b><small dir="ltr">${esc(model.englishName)}</small></span><span>${esc(model.usage)}</span><span class="model-quality-grade">${esc(model.grade)}</span></button>`
      ).join('') + '</div>';
  }

  function closeModelMenus(except) {
    root.querySelectorAll('.ai-model-lab-model-menu').forEach(menu => {
      if (menu !== except) {
        menu.classList.add('hidden');
        menu.closest('.ai-model-lab-model-shell')?.querySelector('.ai-model-lab-model-trigger')?.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function renderLabModelOutputs() {
    const holder = document.getElementById('ai-model-lab-output-model-cards');
    if (!holder) return;
    const rows = Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId);
    holder.innerHTML = rows.map((row, index) => {
      const selectedModel = models.find(item => item.id === row.dataset.modelId);
      const englishName = selectedModel?.englishName || 'model-' + (index + 1);
      const persianName = selectedModel?.persianName || 'مدل آزمایشی';
      const quality = row.querySelector('.ai-model-lab-quality')?.selectedOptions?.[0]?.textContent || '۴۸۰';
      const size = row.querySelector('.ai-model-lab-size')?.selectedOptions?.[0]?.textContent || '۴:۵ · عمودی';
      const version = index === 0 ? '۴.۳' : '۲.۰';
      const costUsd = Number(selectedModel?.costUsd ?? (index === 0 ? .042 : .055));
      const dollar = '$' + costUsd.toFixed(4);
      const toman = exchangeRateToman > 0 ? Math.round(costUsd * exchangeRateToman).toLocaleString('fa-IR') : '—';
      const tokens = index === 0 ? '۱٬۲۸۰' : '۱٬۸۴۰';
      const buildSeconds = 48 + (index * 13);
      const preserveFace = row.querySelector('.ai-model-lab-preserve-face')?.checked ? 'فعال' : 'خاموش';
      return `
        <article class="ai-model-lab-output-card ai-model-lab-model-output-card">
          <div class="ai-model-lab-card-head"><span class="ai-model-lab-card-head-english" dir="ltr">${esc(englishName)}</span><span class="ai-model-lab-card-head-persian">${esc(persianName)}</span></div>
          <div class="ai-model-lab-output-frame">
            <img src="${esc(sampleImage)}" alt="خروجی نمونه ${esc(persianName)}" loading="lazy">
            <span class="ai-model-lab-output-image-label">آزمایش مدل ${ordinalNames[index] || faNumber(index + 1)}</span>
          </div>
          <div class="ai-model-lab-output-meta">
            <div><strong><span>مدل: <b dir="ltr">${esc(englishName)}</b></span><span>نسخه: ${version}</span></strong></div>
            <div><strong class="ai-model-lab-money-stack"><span dir="ltr"><em>دلار</em><b>${dollar}</b></span><span dir="rtl"><em>تومان</em><b>${toman}</b></span></strong></div>
            <div><strong><span>تاریخ: ۱۴۰۴/۰۴/۲۲</span><span>زمان ساخت: ${buildSeconds} ثانیه</span></strong></div>
            <div><strong><span>کیفیت: ${esc(quality)}</span><span>سایز: ${esc(size)}</span></strong></div>
            <div><strong><span>توکن: ${tokens}</span><span>تلاش مجدد: ۰ بار</span></strong></div>
            <div><strong><span>Seed: تصادفی</span><span>حفظ چهره: ${preserveFace}</span></strong></div>
          </div>
        </article>`;
    }).join('');
  }

  function renderCalculationTable() {
    const table = document.getElementById('ai-model-lab-calculation-rows');
    if (!table) return;
    const rows = Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId);
    const countTarget = root.querySelector('[data-lab-summary="model-count"]');
    const inputTarget = root.querySelector('[data-lab-summary="input-image"]');
    const evaluator = document.getElementById('ai-model-lab-evaluator');
    const evaluatorTarget = root.querySelector('[data-lab-summary="evaluator"]');
    const selectedInput = labInputItems.find(item => item.selected) || labInputItems[0];
    if (countTarget) countTarget.textContent = faNumber(rows.length) + ' مدل';
    if (inputTarget) inputTarget.textContent = selectedInput?.name || 'تصویر نمونه';
    if (evaluatorTarget) evaluatorTarget.textContent = evaluator?.selectedOptions?.[0]?.textContent || 'GPT-4o mini · مقایسه‌گر تصویر';
    const totalUsd = rows.reduce((total, row, index) => {
      const model = models.find(item => item.id === row.dataset.modelId);
      return total + Number(model?.costUsd ?? (index === 0 ? .042 : .055));
    }, 0);
    const totalToman = exchangeRateToman > 0 ? Math.round(totalUsd * exchangeRateToman).toLocaleString('fa-IR') : '—';
    const totalUsdTarget = root.querySelector('[data-lab-summary="total-usd"]');
    const totalTomanTarget = root.querySelector('[data-lab-summary="total-toman"]');
    if (totalUsdTarget) totalUsdTarget.textContent = totalUsd.toFixed(4);
    if (totalTomanTarget) totalTomanTarget.textContent = totalToman;
    const scores = labRunReady ? [9.4, 8.2, 7.1, 6.4, 5.8, 5.2] : [];
    table.innerHTML = rows.map((row, index) => {
      const model = models.find(item => item.id === row.dataset.modelId);
      const quality = row.querySelector('.ai-model-lab-quality')?.selectedOptions?.[0]?.textContent || '۴۸۰';
      const size = row.querySelector('.ai-model-lab-size')?.selectedOptions?.[0]?.textContent || '۴:۵ · عمودی';
      const costUsd = Number(model?.costUsd ?? (index === 0 ? .042 : .055));
      const costToman = exchangeRateToman > 0 ? Math.round(costUsd * exchangeRateToman).toLocaleString('fa-IR') : '—';
      const seconds = 48 + (index * 13);
      const score = scores[index] ? scores[index].toLocaleString('fa-IR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) : '—';
      const rank = scores[index] ? ordinalNames[index] || faNumber(index + 1) : '—';
      const manager = managerScores[model?.id] || {};
      const managerPriority = manager.priority && Number(manager.priority) <= rows.length ? manager.priority : '—';
      const preserveFace = row.querySelector('.ai-model-lab-preserve-face')?.checked ? 'فعال' : 'خاموش';
      return `<tr data-lab-model-row="${esc(model?.id || '')}">
        <td><strong>${esc(model?.persianName || 'مدل آزمایشی')}</strong><small dir="ltr">${esc(model?.englishName || '—')}</small></td>
        <td>${esc(model?.providerLabel || '—')}</td>
        <td dir="ltr">${esc(quality)} · ${esc(size)}</td>
        <td>${preserveFace}</td>
        <td dir="ltr">${costUsd.toFixed(4)}</td>
        <td dir="rtl">${costToman}</td>
        <td dir="ltr">${seconds}</td>
        <td dir="ltr">${index === 0 ? '۱٬۲۸۰' : '۱٬۸۴۰'}</td>
        <td>۰ بار</td>
        <td class="lab-score">${score}</td>
        <td class="lab-manager-score" data-lab-manager-score="${esc(model?.id || '')}">${esc(manager.overall || '—')}</td>
        <td data-lab-manager-field="similarity">${esc(manager.similarity || '—')}</td>
        <td data-lab-manager-field="detail">${esc(manager.detail || '—')}</td>
        <td data-lab-manager-field="priority">${esc(managerPriority)}</td>
        <td class="lab-rank">${rank}</td>
      </tr>`;
    }).join('') || '<tr><td colspan="15" class="ai-model-lab-table-empty">برای تکمیل جدول، حداقل یک مدل انتخاب کنید.</td></tr>';
  }

  function renderManagerScoreTable() {
    const table = document.getElementById('ai-model-lab-manager-score-rows');
    if (!table) return;
    const rows = Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId);
    if (!rows.length) {
      table.innerHTML = '<tr><td colspan="5" class="ai-model-lab-table-empty">پس از انتخاب مدل، گزینه‌های نمره‌دهی اینجا نمایش داده می‌شوند.</td></tr>';
      return;
    }
    const selectOptions = (values, selected, label = value => value) => '<option value="">انتخاب</option>' + values.map(value => {
      const selectedAttr = String(value) === String(selected ?? '') ? ' selected' : '';
      return `<option value="${esc(value)}"${selectedAttr}>${esc(label(value))}</option>`;
    }).join('');
    const overallValues = Array.from({ length: 10 }, (_, index) => index + 1);
    const qualitativeValues = ['عالی', 'خوب', 'متوسط', 'ضعیف'];
    table.innerHTML = rows.map(row => {
      const model = models.find(item => item.id === row.dataset.modelId);
      const score = managerScores[model?.id] || {};
      const priorityValues = Array.from({ length: rows.length }, (_, index) => index + 1);
      const selectedPriority = Number(score.priority) <= rows.length ? score.priority : '';
      return `<tr data-manager-row="${esc(model?.id || '')}">
        <td>${esc(model?.persianName || 'مدل آزمایشی')} <small dir="ltr">${esc(model?.englishName || '—')}</small></td>
        <td><select onchange="updateAiManagerScore('${esc(model?.id || '')}', 'overall', this.value)">${selectOptions(overallValues, score.overall, value => faNumber(value) + ' از ۱۰')}</select></td>
        <td><select onchange="updateAiManagerScore('${esc(model?.id || '')}', 'similarity', this.value)">${selectOptions(qualitativeValues, score.similarity)}</select></td>
        <td><select onchange="updateAiManagerScore('${esc(model?.id || '')}', 'detail', this.value)">${selectOptions(qualitativeValues, score.detail)}</select></td>
        <td><select onchange="updateAiManagerScore('${esc(model?.id || '')}', 'priority', this.value)">${selectOptions(priorityValues, selectedPriority, value => 'اولویت ' + faNumber(value))}</select></td>
      </tr>`;
    }).join('');
  }

  function refreshOutputPreview() {
    labRunReady = false;
    const calculationStatus = document.getElementById('ai-model-lab-calculation-status');
    const outputStatus = document.getElementById('ai-model-lab-output-status');
    if (calculationStatus) {
      calculationStatus.textContent = 'آماده بررسی';
      calculationStatus.classList.remove('is-ready');
    }
    if (outputStatus) outputStatus.textContent = 'در انتظار آزمایش';
    const reportStatus = root.querySelector('[data-lab-summary="report-status"]');
    if (reportStatus) reportStatus.textContent = 'آماده بررسی';
    updateCount();
    renderLabModelOutputs();
    renderCalculationTable();
    renderManagerScoreTable();
  }

  function updateCount() {
    const count = Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId).length;
    const label = document.getElementById('ai-model-lab-count');
    if (label) label.textContent = faNumber(count) + ' مدل';
  }

  window.updateAiManagerScore = function (modelId, key, value) {
    if (!managerScores[modelId]) managerScores[modelId] = {};
    managerScores[modelId][key] = value;
    renderCalculationTable();
  };

  window.toggleAiModelLabDefaults = function (event) {
    event?.stopPropagation();
    const menu = document.getElementById('ai-model-lab-defaults-menu');
    const button = event?.currentTarget || root.querySelector('.ai-model-lab-defaults-shell > button');
    if (!menu) return;
    const open = menu.classList.contains('hidden');
    menu.classList.toggle('hidden', !open);
    button?.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  window.runAiModelLab = function () {
    const count = Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId).length;
    const status = document.getElementById('ai-model-lab-output-status');
    const calculationStatus = document.getElementById('ai-model-lab-calculation-status');
    if (count < 1) {
      if (status) status.textContent = 'حداقل یک مدل انتخاب کنید';
      if (calculationStatus) calculationStatus.textContent = 'حداقل یک مدل انتخاب کنید';
      return;
    }
    labRunReady = false;
    if (status) status.textContent = 'در حال ساخت خروجی‌ها...';
    if (calculationStatus) calculationStatus.textContent = 'در حال آماده‌سازی گزارش';
    renderCalculationTable();
    window.setTimeout(() => {
      if (status) status.textContent = 'خروجی‌های آزمایش آماده شد';
      if (calculationStatus) calculationStatus.textContent = 'آماده ارزیابی';
      renderCalculationTable();
    }, 650);
  };

  window.evaluateAiModelLab = function () {
    const count = Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId).length;
    const status = document.getElementById('ai-model-lab-output-status');
    const calculationStatus = document.getElementById('ai-model-lab-calculation-status');
    if (count < 3) {
      if (status) status.textContent = 'برای اولویت‌بندی حداقل ۳ مدل انتخاب کنید';
      if (calculationStatus) calculationStatus.textContent = 'برای رتبه‌بندی کافی نیست';
      return;
    }
    labRunReady = false;
    if (status) status.textContent = 'در حال ارزیابی خروجی‌ها...';
    if (calculationStatus) calculationStatus.textContent = 'مدل ارزیاب در حال مقایسه است';
    renderCalculationTable();
    window.setTimeout(() => {
      labRunReady = true;
      if (status) status.textContent = 'ارزیابی خروجی‌ها تکمیل شد';
      if (calculationStatus) {
        calculationStatus.textContent = 'رتبه‌بندی آماده است';
        calculationStatus.classList.add('is-ready');
      }
      const reportStatus = root.querySelector('[data-lab-summary="report-status"]');
      if (reportStatus) reportStatus.textContent = 'ارزیابی‌شده';
      renderCalculationTable();
    }, 650);
  };

  function chooseModelInRow(row, model) {
    if (!row || !model) return;
    const provider = row.querySelector('.ai-model-lab-provider');
    const trigger = row.querySelector('.ai-model-lab-model-trigger');
    const menu = row.querySelector('.ai-model-lab-model-menu');
    if (!provider || !trigger || !menu) return;
    provider.value = model.provider;
    provider.dispatchEvent(new Event('change'));
    const option = Array.from(menu.querySelectorAll('.ai-model-lab-model-option')).find(item => item.dataset.modelKey === model.id);
    option?.click();
  }

  window.resetAiModelLab = function () {
    const rows = Array.from(root.querySelectorAll('.ai-model-lab-row'));
    rows.forEach(row => row.querySelector('.ai-model-lab-remove')?.click());
    managerScores = {};
    labInputItems.forEach(item => { item.selected = false; });
    renderLabInputImages();
    updateCount();
    renderLabModelOutputs();
    renderCalculationTable();
    renderManagerScoreTable();
  };

  window.applyAiModelLabDefaults = function (profile = 'professional-face') {
    const profileIndexes = {
      'professional-face': [0, 1, 2],
      'normal-face': [0, 1],
      business: [2, 3, 4],
    };
    const defaults = (profileIndexes[profile] || profileIndexes['professional-face']).map(index => models[index]).filter(Boolean);
    if (!defaults.length) return;
    while (root.querySelectorAll('.ai-model-lab-row').length < defaults.length) addRow();
    Array.from(root.querySelectorAll('.ai-model-lab-row')).slice(defaults.length).forEach(row => row.remove());
    defaults.forEach((model, index) => chooseModelInRow(root.querySelectorAll('.ai-model-lab-row')[index], model));
    document.getElementById('ai-model-lab-defaults-menu')?.classList.add('hidden');
    root.querySelector('.ai-model-lab-defaults-shell > button')?.setAttribute('aria-expanded', 'false');
    updateCount();
    renderLabModelOutputs();
    renderCalculationTable();
    renderManagerScoreTable();
  };

  function addRow() {
    rowIndex += 1;
    const row = document.createElement('div');
    row.className = 'ai-model-lab-row';
    row.dataset.row = String(rowIndex);
    row.innerHTML = `
      <label class="ai-model-lab-field">
        <span>پرووایدر</span>
        <select class="ai-model-lab-provider" aria-label="پرووایدر مدل">${providerMarkup()}</select>
      </label>
      <div class="ai-model-lab-field">
        <span>مدل هوش مصنوعی</span>
        <div class="ai-model-lab-model-shell">
          <button type="button" class="ai-model-lab-model-trigger" aria-label="مدل هوش مصنوعی" aria-expanded="false" disabled><span>ابتدا پرووایدر را انتخاب کنید</span><i class="fa-solid fa-chevron-down"></i></button>
          <div class="ai-model-lab-model-menu hidden" role="listbox"></div>
        </div>
      </div>
      <label class="ai-model-lab-field">
        <span>کیفیت خروجی</span>
        <select class="ai-model-lab-quality" aria-label="کیفیت خروجی">${optionMarkup(qualityOptions, 'انتخاب کیفیت', '480')}</select>
      </label>
      <label class="ai-model-lab-field">
        <span>سایز خروجی</span>
        <select class="ai-model-lab-size" aria-label="سایز خروجی" dir="ltr">${optionMarkup(sizeOptions, 'انتخاب سایز', '4:5')}</select>
      </label>
      <label class="ai-model-lab-field ai-model-lab-preserve-field">
        <span>حفظ چهره</span>
        <span class="ai-model-lab-checkbox-control"><input class="ai-model-lab-preserve-face" type="checkbox" checked aria-label="حفظ چهره"><b>فعال</b></span>
      </label>
      <button type="button" class="ai-model-lab-remove" aria-label="حذف مدل" title="حذف مدل"><i class="fa-solid fa-trash-can"></i></button>
    `;
    root.querySelector('#ai-model-lab-rows').appendChild(row);
    const providerSelect = row.querySelector('.ai-model-lab-provider');
    const modelTrigger = row.querySelector('.ai-model-lab-model-trigger');
    const modelMenu = row.querySelector('.ai-model-lab-model-menu');
    providerSelect.addEventListener('change', function () {
      row.dataset.modelId = '';
      modelTrigger.querySelector('span').textContent = this.value ? 'انتخاب مدل هوش مصنوعی' : 'ابتدا پرووایدر را انتخاب کنید';
      modelTrigger.disabled = !this.value;
      modelMenu.innerHTML = this.value ? modelMenuMarkup(this.value) : '';
      modelMenu.classList.add('hidden');
      refreshOutputPreview(row);
    });
    modelTrigger.addEventListener('click', function () {
      if (this.disabled) return;
      const willOpen = modelMenu.classList.contains('hidden');
      closeModelMenus(modelMenu);
      modelMenu.classList.toggle('hidden', !willOpen);
      this.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
    modelMenu.addEventListener('click', function (event) {
      const option = event.target.closest('.ai-model-lab-model-option');
      if (!option) return;
      const model = models.find(item => item.id === option.dataset.modelKey);
      if (!model) return;
      row.dataset.modelId = model.id;
      modelTrigger.querySelector('span').textContent = model.persianName + ' · ' + model.providerLabel;
      modelMenu.querySelectorAll('.ai-model-lab-model-option').forEach(item => item.classList.toggle('is-selected', item === option));
      modelMenu.classList.add('hidden');
      modelTrigger.setAttribute('aria-expanded', 'false');
      refreshOutputPreview(row);
    });
    row.querySelectorAll('.ai-model-lab-quality, .ai-model-lab-size').forEach(control => control.addEventListener('change', () => refreshOutputPreview(row)));
    row.querySelector('.ai-model-lab-preserve-face')?.addEventListener('change', function () {
      const status = this.closest('.ai-model-lab-checkbox-control')?.querySelector('b');
      if (status) status.textContent = this.checked ? 'فعال' : 'خاموش';
      refreshOutputPreview(row);
    });
    row.querySelector('.ai-model-lab-remove').addEventListener('click', function () {
      const rows = root.querySelectorAll('.ai-model-lab-row');
      if (rows.length === 1) {
        row.querySelectorAll('select').forEach(select => { select.selectedIndex = 0; });
        row.querySelector('.ai-model-lab-quality').value = '480';
        row.querySelector('.ai-model-lab-size').value = '4:5';
        row.querySelector('.ai-model-lab-preserve-face').checked = true;
        row.querySelector('.ai-model-lab-preserve-field b').textContent = 'فعال';
        row.dataset.modelId = '';
        modelTrigger.querySelector('span').textContent = 'ابتدا پرووایدر را انتخاب کنید';
        modelTrigger.disabled = true;
        modelMenu.innerHTML = '';
      } else {
        row.remove();
      }
      updateCount();
      refreshOutputPreview(root.querySelector('.ai-model-lab-row'));
    });
    refreshOutputPreview(row);
    updateCount();
  }

  let labInputItems = [];
  let labInputSequence = 0;
  let labInputSourceSignature = '';

  function syncStepOneInputImages() {
    const input = document.getElementById('main-images-file');
    const group = document.querySelector('[data-input="main-images-file"]');
    const files = Array.from(input?.files || []);
    let existing = [];
    try { existing = JSON.parse(group?.dataset?.existing || '[]'); } catch (error) { existing = []; }
    const signature = files.length
      ? files.map(file => [file.name, file.size, file.lastModified].join(':')).join('|')
      : existing.join('|');
    if (signature === labInputSourceSignature) return;
    labInputSourceSignature = signature;
    labInputItems = labInputItems.filter(item => item.source === 'upload');
    const sourceItems = files.length
      ? files.map((file, index) => ({ id: 'step-one-' + index, url: URL.createObjectURL(file), name: file.name, file, source: 'step-one', selected: false }))
      : existing.map((url, index) => ({ id: 'step-one-' + index, url, name: 'تصویر گام اول ' + (index + 1), source: 'step-one', selected: false }));
    labInputItems = sourceItems.concat(labInputItems);
  }

  function updateLabInputOutput() {
    const selected = labInputItems.find(item => item.selected) || labInputItems[0];
    if (!selected) return;
    root.querySelectorAll('[data-lab-input-output]').forEach(image => { image.src = selected.url; });
    const file = selected.file;
    const image = new Image();
    image.onload = function () {
      const width = image.naturalWidth || 1200;
      const height = image.naturalHeight || 1600;
      const ratio = width / height;
      const ratioLabel = Math.abs(ratio - .8) < .05 ? '۴:۵ عمودی' : width >= height ? 'افقی' : 'عمودی';
      const values = {
        dimensions: faNumber(width) + ' × ' + faNumber(height),
        ratio: ratioLabel,
        size: file ? (file.size / 1024).toFixed(1) + ' کیلوبایت' : '۳۲۰ کیلوبایت',
        type: file ? (file.type.split('/')[1] || 'image').toUpperCase() : 'JPEG',
        color: 'RGB',
        time: file ? new Date(file.lastModified || Date.now()).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' }) : '۱۲:۴۸',
        selection: 'آماده'
      };
      Object.entries(values).forEach(([key, value]) => {
        const target = root.querySelector('[data-lab-input-meta="' + key + '"]');
        if (target) target.textContent = value;
      });
    };
    image.src = selected.url;
  }

  function renderLabInputImages() {
    syncStepOneInputImages();
    updateLabInputOutput();
  }

  document.getElementById('ai-model-lab-upload-input')?.addEventListener('change', function () {
    const files = Array.from(this.files || []);
    labInputItems.forEach(item => { item.selected = false; });
    files.forEach((file, index) => {
      labInputSequence += 1;
      labInputItems.push({ id: 'lab-upload-' + labInputSequence, url: URL.createObjectURL(file), name: file.name, file, source: 'upload', selected: index === 0 });
    });
    this.value = '';
    renderLabInputImages();
  });
  document.addEventListener('product-images-changed', renderLabInputImages);
  renderLabInputImages();
  document.getElementById('ai-model-lab-evaluator')?.addEventListener('change', renderCalculationTable);

  document.addEventListener('click', event => {
    if (!event.target.closest('#ai-model-lab .ai-model-lab-model-shell')) closeModelMenus();
    if (!event.target.closest('#ai-model-lab .ai-model-lab-defaults-shell')) {
      document.getElementById('ai-model-lab-defaults-menu')?.classList.add('hidden');
      document.querySelector('#ai-model-lab .ai-model-lab-defaults-shell > button')?.setAttribute('aria-expanded', 'false');
    }
  });

  window.toggleAiModelLab = function () {
    const toggle = document.getElementById('ai-model-lab-toggle');
    const drawer = document.getElementById('ai-model-lab-drawer');
    const copy = document.getElementById('ai-model-lab-toggle-copy');
    if (!toggle || !drawer) return;
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    drawer.classList.toggle('hidden', !open);
    if (copy) copy.textContent = open ? 'خاموش کردن آزمایشگاه' : 'روشن کردن آزمایشگاه';
  };
  window.addAiModelLabRow = addRow;
  addRow();
  const hostForm = root.closest('form');
  hostForm?.addEventListener('submit', () => {
    const payload = {
      evaluator: document.getElementById('ai-model-lab-evaluator')?.value || 'gpt-4o-mini',
      input: (labInputItems.find(item => item.selected) || labInputItems[0] || {}),
      models: Array.from(root.querySelectorAll('.ai-model-lab-row')).filter(row => row.dataset.modelId).map(row => ({
        id: Number(row.dataset.modelId), provider: row.querySelector('.ai-model-lab-provider')?.value || '', quality: row.querySelector('.ai-model-lab-quality')?.value || '720', size: row.querySelector('.ai-model-lab-size')?.value || '4:5', preserve_face: row.querySelector('.ai-model-lab-preserve-face')?.checked !== false,
      })),
      manager_scores: managerScores,
    };
    const field = hostForm.querySelector('input[name="ai_lab_payload"]');
    if (field) field.value = JSON.stringify(payload);
  }, { once: false });
})();
</script>
