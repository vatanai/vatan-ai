@extends('layouts.admin')
@section('title', 'آزمایش جدید — آزمایشگاه')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/products-create.css') }}">
@endpush

@section('content')
<main id="lab-create-page" class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content" dir="rtl" style="background:var(--page-bg);">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
      <div>
        <div class="flex items-center gap-2 mb-1.5">
          <span class="lab-page-icon"><i class="fa-solid fa-flask"></i></span>
          <h1 class="text-xl font-extrabold" style="color:var(--text-h);">آزمایش جدید</h1>
        </div>
        <p class="text-[12px]" style="color:var(--text-soft);">کیفیت خروجی مدل‌ها را روی تصاویر واقعی محصول مقایسه کنید.</p>
      </div>
      <a href="{{ route('admin.lab.index') }}" class="btn-pro btn-pro-ghost">
        <i class="fa-solid fa-arrow-right"></i>
        بازگشت به آزمایشگاه
      </a>
    </div>

    @if($errors->any())
      <div class="lab-alert mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.lab.store') }}" id="lab-form">
      @csrf

      {{-- همان ساختار استپر ثبت محصول: دسکتاپ افقی و موبایل عمودی --}}
      <div class="mb-7 bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-2 md:p-1.5" id="lab-steps">
        <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-0">
          @foreach([1 => ['محصول و تصاویر','انتخاب نمونه‌های آزمایش','۱','اول'], 2 => ['مدل‌ها','انتخاب مدل‌های تصویری','۲','دوم'], 3 => ['تنظیمات و اجرا','مرور و شروع آزمایش','۳','سوم'], 4 => ['اجرای آزمایش','خروجی و ارزیابی','۴','چهارم']] as $step => $meta)
            @if($step > 1)<div class="hidden md:block w-6 shrink-0 h-px bg-[var(--b1)]" id="conn-{{ $step - 1 }}"></div><div class="md:hidden w-px h-3 bg-[var(--b1)] mr-[35px]" id="conn-{{ $step - 1 }}-m"></div>@endif
            <div class="step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all duration-200 border border-transparent {{ $step === 1 ? 'bg-[var(--accent)]/10 border-[var(--accent)]/25 step-tab-active' : '' }}" id="step-tab-{{ $step }}" data-step="{{ $step }}">
              <div class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2 {{ $step === 1 ? 'border-[var(--accent)] bg-[var(--accent)]/15 text-[var(--accent)]' : 'border-[var(--b2)] text-[var(--text3)]' }} transition-all duration-200" id="step-num-{{ $step }}" data-num="{{ $meta[2] }}">{{ $meta[2] }}</div>
              <div class="flex-1 min-w-0"><div class="step-label text-[11px] {{ $step === 1 ? 'text-[var(--accent)]' : 'text-[var(--text3)]' }} mb-0.5 transition-colors">گام {{ $meta[3] }}</div><div class="step-title text-xs font-bold {{ $step === 1 ? 'text-[var(--text)]' : 'text-[var(--text2)]' }} transition-colors">{{ $meta[0] }}</div><div class="step-desc text-[10.5px] text-[var(--text3)] mt-0.5">{{ $meta[1] }}</div></div>
              <div class="shrink-0 flex items-center gap-1.5 pr-1"><span class="step-frac hidden text-[10px] font-bold font-mono text-[var(--text3)] bg-[var(--text)]/5 rounded px-1.5 py-0.5" id="step-frac-{{ $step }}"></span><span class="step-check hidden text-[var(--green)]" id="step-check-{{ $step }}"><i class="fa-solid fa-circle-check text-sm"></i></span></div>
            </div>
          @endforeach
        </div>
      </div>

      <div id="lab-form-error" class="lab-alert hidden mb-4" role="alert"></div>

      <section class="lab-panel content-card" data-panel="1">
        <div class="lab-panel-heading">
          <span class="lab-panel-icon"><i class="fa-solid fa-images"></i></span>
          <div>
            <h2>محصول و تصاویر مورد آزمایش</h2>
            <p>محصول را انتخاب کنید و حداقل یک تصویر را برای مقایسه علامت بزنید.</p>
          </div>
        </div>

        <label class="lab-field-label" for="lab-product">محصول</label>
        <select name="product_id" id="lab-product" class="input-pro w-full" required>
          <option value="">یک محصول را انتخاب کنید</option>
          @foreach($products as $product)
            <option value="{{ $product->id }}" @selected((string) old('product_id', $selectedProductId) === (string) $product->id)>
              {{ $product->name_fa }} — {{ $product->name_en }}
            </option>
          @endforeach
        </select>

        <div class="lab-selection-head mt-5">
          <div>
            <strong>تصاویر محصول</strong>
            <span id="lab-image-count">هنوز تصویری انتخاب نشده</span>
          </div>
          <span class="lab-hint"><i class="fa-solid fa-circle-info"></i> برای انتخاب روی تصویر کلیک کنید</span>
        </div>

        <div id="lab-image-empty" class="lab-empty-state">
          <i class="fa-regular fa-image"></i>
          <span>ابتدا یک محصول را انتخاب کنید.</span>
        </div>
        <div id="lab-images" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>

        <div class="lab-actions justify-end">
          <button type="button" class="btn-pro btn-pro-primary" data-next="2">
            مرحله بعد
            <i class="fa-solid fa-arrow-left"></i>
          </button>
        </div>
      </section>

      <section class="lab-panel content-card hidden" data-panel="2">
        <div class="lab-panel-heading">
          <span class="lab-panel-icon"><i class="fa-solid fa-microchip"></i></span>
          <div>
            <h2>مدل‌های هوش مصنوعی</h2>
            <p>مدل‌های نامزد را انتخاب کنید؛ مدل اصلی و جایگزین هر گرید در آپدیت پایین گام چهارم مشخص می‌شود.</p>
          </div>
        </div>

        <div id="lab-model-context" class="lab-model-context mb-4"><i class="fa-solid fa-lightbulb"></i><span>با انتخاب محصول، مدل‌های پیشنهادی همان دسته در اینجا مشخص می‌شوند.</span></div>

        @php
          $providerLabels = ['liara' => 'Liara', 'openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate'];
          $providerIcons = ['liara' => 'fa-cloud', 'openrouter' => 'fa-bolt', 'fal' => 'fa-wand-magic-sparkles', 'replicate' => 'fa-cubes'];
        @endphp
        <div class="lab-provider-grid">
          @forelse($models->groupBy('provider') as $provider => $providerModels)
            <section class="lab-provider-column">
              <header class="lab-provider-heading"><span class="lab-provider-icon"><i class="fa-solid {{ $providerIcons[$provider] ?? 'fa-microchip' }}"></i></span><div><strong>{{ $providerLabels[$provider] ?? $provider }}</strong><small>{{ $providerModels->count() }} مدل تصویری</small></div></header>
              <div class="lab-provider-models">
                @foreach($providerModels as $model)
                    <label class="lab-model-card" data-category-ids="{{ implode(',', array_map('intval', (array) $model->recommended_category_ids)) }}">
                    <input type="checkbox" name="models[]" value="{{ $model->id }}" data-cost="{{ $model->cost_per_generation_usd }}" @checked(in_array($model->id, old('models', $duplicateModelIds ?? [])))>
                    <span class="lab-model-mark"><i class="fa-solid fa-check"></i></span>
                    <span class="lab-model-icon"><i class="fa-solid {{ $model->mediaIcon() }}"></i></span>
                    <span class="lab-model-copy"><strong>{{ $model->name }}</strong><small dir="ltr">{{ $model->externalModelId() }}</small><em class="lab-model-recommendation">پیشنهاد این دسته</em></span>
                    <span class="lab-model-cost" dir="ltr">@if($model->cost_per_generation_usd !== null)${{ number_format($model->cost_per_generation_usd, 4) }}@else—@endif</span>
                  </label>
                @endforeach
              </div>
            </section>
          @empty
            <div class="lab-empty-state mt-3"><i class="fa-solid fa-microchip"></i><span>مدل تصویری فعالی برای آزمایش ثبت نشده است.</span></div>
          @endforelse
        </div>

        <div class="lab-actions justify-between">
          <button type="button" class="btn-pro btn-pro-ghost" data-prev="1"><i class="fa-solid fa-arrow-right"></i> مرحله قبل</button>
          <button type="button" class="btn-pro btn-pro-primary" data-next="3">مرحله بعد <i class="fa-solid fa-arrow-left"></i></button>
        </div>
      </section>

      <section class="lab-panel content-card hidden" data-panel="3">
        <div class="lab-panel-heading">
          <span class="lab-panel-icon"><i class="fa-solid fa-sliders"></i></span>
          <div>
            <h2>تنظیمات و اجرای آزمایش</h2>
            <p>پارامترها را مرور کنید؛ سپس آزمایش برای مدل‌های انتخاب‌شده در صف اجرا قرار می‌گیرد.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <label class="lab-field-label">عنوان آزمایش <span class="text-[var(--text3)]">(خودکار، قابل ویرایش)</span>
            <input name="title" id="lab-title" value="{{ old('title') }}" class="input-pro w-full mt-1.5" placeholder="آزمایش نام محصول (کد محصول)">
          </label>
          <label class="lab-field-label">تعداد خروجی برای هر مدل
            <select name="count" id="lab-count" class="input-pro w-full mt-1.5">
              @foreach([1, 2, 3, 4] as $count)<option value="{{ $count }}" @selected((int) old('count', 1) === $count)>{{ $count }} خروجی</option>@endforeach
            </select>
          </label>
          <label class="lab-field-label" dir="ltr">Resolution
            <select name="resolution" id="lab-resolution" class="input-pro w-full mt-1.5" dir="ltr">
              @foreach(['480', '720', '1080', '1440', '2160'] as $value)
                <option value="{{ $value }}" @selected(old('resolution', '720') === $value)>{{ $value }}</option>
              @endforeach
            </select>
          </label>
          <label class="lab-field-label">نسبت تصویر <span class="text-[var(--text3)]">(پیش‌فرض: ۴:۵ عمودی)</span>
            <select name="aspect_ratio" id="lab-aspect-ratio" class="input-pro w-full mt-1.5" dir="ltr">
              @foreach(['4:5' => '۴:۵ · عمودی', '9:16' => '۹:۱۶ · استوری', '3:4' => '۳:۴ · عمودی', '1:1' => '۱:۱ · مربعی', '2:3' => '۲:۳ · عمودی', '16:9' => '۱۶:۹ · افقی', '3:2' => '۳:۲ · افقی'] as $value => $label)
                <option value="{{ $value }}" @selected(old('aspect_ratio', '4:5') === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </label>
          <label class="lab-field-label">Seed اختیاری
            <input type="number" name="seed" value="{{ old('seed') }}" class="input-pro w-full mt-1.5" dir="ltr" placeholder="خالی = تصادفی">
            <span class="lab-field-help">Seed یک عدد ثابت برای تکرارپذیری است؛ اگر یک پرامپت و یک seed یکسان بدهید، احتمال نزدیک‌بودن خروجی‌ها بیشتر می‌شود. برای مقایسه منصفانه می‌توانید ثابتش کنید.</span>
          </label>
          <label class="lab-field-label">مدل ارزیابی خودکار
            <select name="scoring_model" id="lab-scoring-model" class="input-pro w-full mt-1.5" dir="ltr">
              <option value="openai/gpt-4o-mini" @selected(old('scoring_model', 'openai/gpt-4o-mini') === 'openai/gpt-4o-mini')>GPT-4o-mini · OpenRouter</option>
              @foreach($scoringModels->where('openrouter_model_id', '!=', 'openai/gpt-4o-mini') as $scoringModel)
                <option value="{{ $scoringModel->externalModelId() }}" @selected(old('scoring_model') === $scoringModel->externalModelId())>{{ $scoringModel->name }} · OpenRouter</option>
              @endforeach
            </select>
            <span class="lab-field-help">این مدل خروجی‌ها را از نظر هماهنگی با پرامپت، کیفیت، ترکیب‌بندی و تناسب با محصول از ۱ تا ۵ امتیاز می‌دهد.</span>
          </label>
          <label class="lab-field-label md:col-span-2">پرامپت محصول
            <textarea name="prompt_override" id="lab-prompt" rows="18" class="input-pro w-full mt-1.5 lab-prompt-input" placeholder="با انتخاب محصول، پرامپت همان محصول اینجا قرار می‌گیرد؛ در صورت نیاز قابل ویرایش است.">{{ old('prompt_override') }}</textarea>
            <span class="lab-field-help">با انتخاب محصول، متن پرامپت ذخیره‌شده‌ی همان محصول خودکار وارد می‌شود.</span>
          </label>
        </div>

        <div class="lab-actions justify-between">
          <button type="button" class="btn-pro btn-pro-ghost" data-prev="2"><i class="fa-solid fa-arrow-right"></i> مرحله قبل</button>
          <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-flask"></i> ساخت و اجرای آزمایش</button>
        </div>
      </section>

    </form>

      <section class="lab-panel content-card hidden" data-panel="4">
        <div class="lab-panel-heading">
          <span class="lab-panel-icon"><i class="fa-solid fa-flask"></i></span>
          <div>
            <h2>گام چهارم: اجرای آزمایش</h2>
            <p>در این مرحله تا پایان تولید و ارزیابی خروجی منتظر می‌مانیم.</p>
          </div>
        </div>

        <div id="lab-wizard-status" class="lab-wizard-status"><i class="fa-solid fa-arrows-rotate fa-spin"></i><span>آزمایش در حال آماده‌سازی است…</span></div>

        <div class="lab-review mt-5">
          <div class="lab-review-title"><i class="fa-solid fa-receipt"></i> خلاصه‌ی آزمایش</div>
          <div class="lab-review-grid">
            <div><span>محصول</span><strong id="lab-summary-product">—</strong></div><div><span>تصاویر انتخاب‌شده</span><strong id="lab-summary-images">۰</strong></div><div><span>مدل‌ها</span><strong id="lab-summary-models">۰</strong></div><div><span>ارزیاب</span><strong id="lab-summary-scoring-model" dir="ltr">GPT-4o-mini</strong></div>
          </div>
          <div class="lab-cost-table mt-3">
            <div><span>تولید تصویر</span><strong id="lab-cost-images-usd" dir="ltr">$0.0000</strong><small id="lab-cost-images-irr">۰ تومان</small></div>
            <div><span>امتیازدهی هوش مصنوعی</span><strong id="lab-cost-score-usd" dir="ltr">بعد از اجرا</strong><small id="lab-cost-score-irr">بر اساس مصرف واقعی</small></div>
            <div><span>جمع تخمینی</span><strong id="lab-estimate" dir="ltr">$0.0000</strong><small id="lab-estimate-irr">۰ تومان</small></div>
          </div>
          <p>هزینه‌ی واقعی پس از دریافت پاسخ Provider نیز در سوابق آزمایش ذخیره می‌شود.</p>
        </div>

        <div id="lab-wizard-results" class="mt-5">
          <div class="lab-model-results-grid">
            @foreach([
              ['مدل هوش مصنوعی شماره یک', 'نمونه محلی مدل اول', '۱۲.۴ ثانیه', '$0.0400', '۱۲۰٬۰۰۰ تومان'],
              ['مدل هوش مصنوعی شماره دو', 'نمونه محلی مدل دوم', '۱۴.۸ ثانیه', '$0.0550', '۱۶۵٬۰۰۰ تومان'],
            ] as $sample)
              <article class="lab-model-result-card">
                <header class="lab-model-result-head"><strong>{{ $sample[0] }}</strong><span class="badge-pro badge-success">نمونه</span></header>
                <div class="lab-model-result-images"><img src="{{ asset('assets/img/images.jpg') }}" alt="خروجی نمونه آزمایش" loading="lazy"></div>
                <div class="lab-model-result-meta">
                  <div class="lab-model-result-meta-wide"><span>مدل هوش مصنوعی</span><strong>{{ $sample[1] }}</strong></div>
                  <div><span>زمان پاسخگویی (Execution Time)</span><strong dir="ltr">{{ $sample[2] }}</strong></div>
                  <div class="lab-model-result-meta-wide"><span>قیمت تمام‌شده (Exact Cost)</span><strong dir="ltr">{{ $sample[3] }} <small>{{ $sample[4] }}</small></strong></div>
                  <div><span>وضعیت موفقیت (Status)</span><strong class="lab-status-success">موفق</strong></div>
                  <div><span>تعداد تلاش مجدد (Retries)</span><strong>۰ بار</strong></div>
                </div>
              </article>
            @endforeach
          </div>
        </div>
        <div id="lab-wizard-evaluation" class="hidden mt-5"></div>
        <div class="lab-actions justify-between">
          <a href="{{ route('admin.lab.index') }}" class="btn-pro btn-pro-ghost">بازگشت به لیست آزمایش‌ها</a>
          <a href="#" id="lab-wizard-details" class="btn-pro btn-pro-primary hidden">مشاهده جزئیات کامل <i class="fa-solid fa-arrow-left"></i></a>
        </div>
      </section>

    <div id="lab-confirm-modal" class="lab-modal hidden" role="dialog" aria-modal="true" aria-labelledby="lab-confirm-title">
      <div class="lab-modal-card">
        <div class="flex items-start justify-between gap-3 mb-4"><div><h2 id="lab-confirm-title" class="text-sm font-extrabold text-[var(--text-h)]">مرور نهایی آزمایش</h2><p class="text-[10px] text-[var(--text-soft)] mt-1">قبل از شروع، خلاصه تنظیمات را بررسی کنید.</p></div><button type="button" class="icon-action-btn" data-close-lab-modal><i class="fa-solid fa-xmark"></i></button></div>
        <div id="lab-confirm-summary" class="lab-confirm-summary"></div>
        <div class="flex items-center justify-end gap-2 mt-5"><button type="button" class="btn-pro btn-pro-ghost" data-close-lab-modal>انصراف</button><button type="button" class="btn-pro btn-pro-primary" id="lab-confirm-submit"><i class="fa-solid fa-flask ml-1"></i> شروع آزمایش</button></div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('styles')
<style>
  #lab-create-page { --bg:var(--input-bg); --s1:var(--input-bg); --s2:var(--card-bg); --b1:var(--border); --b2:color-mix(in srgb, var(--text-soft) 42%, transparent); --text:var(--text-h); --text2:var(--text-main); --text3:var(--text-soft); --accent:var(--primary); --green:var(--success); --red:var(--danger); }
  #lab-create-page .step-item.step-tab-active { box-shadow:0 0 12px -2px color-mix(in srgb, var(--primary) 42%, transparent); }
  #lab-form :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
  .lab-page-icon, .lab-panel-icon { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--primary); background:var(--primary-l); }
  .lab-page-icon { width:34px; height:34px; border-radius:10px; font-size:14px; }
  .lab-stepper { padding:6px; border:1px solid var(--border); border-radius:12px; background:var(--card-bg); }
  .lab-step-item { flex:1; min-width:0; display:flex; align-items:center; gap:12px; padding:10px 12px; border:1px solid transparent; border-radius:9px; cursor:pointer; text-align:right; background:transparent; color:var(--text-soft); transition:all .2s ease; }
  .lab-step-item:hover { background:var(--input-bg); }
  .lab-step-item.active { border-color:var(--primary); background:var(--primary-l); color:var(--text-h); }
  .lab-step-item.done .lab-step-circle { color:var(--success); border-color:var(--success); background:color-mix(in srgb, var(--success) 10%, transparent); }
  .lab-step-item.done .lab-step-check { display:inline-flex; }
  .lab-step-circle { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border:2px solid color-mix(in srgb, var(--text-soft) 42%, transparent); border-radius:999px; color:var(--text-soft); font-size:11px; font-weight:800; transition:all .2s ease; }
  .lab-step-item.active .lab-step-circle { color:var(--primary); border-color:var(--primary); background:var(--card-bg); }
  .lab-step-copy { min-width:0; flex:1; }
  .lab-step-label, .lab-step-title, .lab-step-desc { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .lab-step-label { margin-bottom:2px; color:var(--text-soft); font-size:10px; }
  .lab-step-title { color:var(--text-h); font-size:11px; font-weight:800; }
  .lab-step-desc { margin-top:2px; color:var(--text-soft); font-size:10px; }
  .lab-step-status { width:18px; flex-shrink:0; text-align:center; }
  .lab-step-check { display:none; color:var(--success); font-size:13px; }
  .lab-connector { width:24px; height:1px; flex-shrink:0; background:var(--border); transition:background .2s ease; }
  .lab-connector.active { background:var(--primary); }
  .lab-alert { display:flex; align-items:flex-start; gap:9px; padding:11px 13px; border:1px solid color-mix(in srgb, var(--danger) 30%, transparent); border-radius:10px; background:color-mix(in srgb, var(--danger) 8%, transparent); color:var(--danger); font-size:11px; line-height:1.8; }
  .lab-alert.hidden { display:none; }
  .lab-panel { padding:20px; }
  .lab-panel-heading { display:flex; align-items:flex-start; gap:11px; padding-bottom:17px; margin-bottom:18px; border-bottom:1px solid var(--border); }
  .lab-panel-icon { width:36px; height:36px; border-radius:10px; font-size:14px; }
  .lab-panel-heading h2 { margin:0; color:var(--text-h); font-size:14px; font-weight:800; }
  .lab-panel-heading p { margin:4px 0 0; color:var(--text-soft); font-size:11px; line-height:1.8; }
  .lab-field-label { display:block; color:var(--text-soft); font-size:11px; line-height:1.7; }
  .lab-selection-head { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; padding-bottom:10px; border-bottom:1px solid var(--border); }
  .lab-selection-head strong { display:block; color:var(--text-h); font-size:12px; }
  .lab-selection-head span { color:var(--text-soft); font-size:10px; }
  .lab-hint { display:flex; align-items:center; gap:5px; }
  .lab-empty-state { min-height:130px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:9px; color:var(--text-soft); font-size:11px; }
  .lab-empty-state i { font-size:25px; opacity:.6; }
  .lab-image-card { position:relative; overflow:hidden; border:1px solid var(--border); border-radius:11px; background:var(--input-bg); cursor:pointer; transition:all .2s ease; }
  .lab-image-card:hover { border-color:var(--primary); transform:translateY(-1px); }
  .lab-image-card.selected { border-color:var(--primary); box-shadow:0 0 0 2px var(--primary-l); }
  .lab-image-card img { display:block; width:100%; aspect-ratio:1; object-fit:cover; }
  .lab-image-card input { position:absolute; opacity:0; pointer-events:none; }
  .lab-image-mark { position:absolute; top:8px; right:8px; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:7px; background:var(--card-bg); color:transparent; font-size:10px; }
  .lab-image-card.selected .lab-image-mark { border-color:var(--primary); background:var(--primary); color:var(--accent); }
  .lab-image-meta { display:block; padding:8px; overflow:hidden; color:var(--text-soft); font-size:9px; text-overflow:ellipsis; white-space:nowrap; }
  .lab-model-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
  .lab-provider-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; align-items:start; }
  .lab-provider-column { min-width:0; border:1px solid var(--border); border-radius:12px; background:var(--input-bg); overflow:hidden; }
  .lab-provider-heading { display:flex; align-items:center; gap:9px; padding:11px; border-bottom:1px solid var(--border); background:var(--card-bg); }
  .lab-provider-icon { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border-radius:9px; background:var(--primary-l); color:var(--primary); font-size:12px; }
  .lab-provider-heading strong, .lab-provider-heading small { display:block; }
  .lab-provider-heading strong { color:var(--text-h); font-size:11px; }
  .lab-provider-heading small { margin-top:2px; color:var(--text-soft); font-size:9px; }
  .lab-provider-models { display:grid; gap:6px; padding:7px; }
  .lab-provider-models .lab-model-card { padding:9px; gap:7px; border-radius:9px; }
  .lab-provider-models .lab-model-icon { width:27px; height:27px; border-radius:8px; font-size:11px; }
  .lab-provider-models .lab-model-copy strong { font-size:10px; }
  .lab-provider-models .lab-model-copy small { font-size:8px; }
  .lab-model-context { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid var(--border); border-radius:9px; color:var(--text-soft); background:var(--input-bg); font-size:10px; }
  .lab-model-context i { color:var(--primary); }
  .lab-model-card { position:relative; display:flex; align-items:center; gap:10px; min-width:0; padding:12px; border:1px solid var(--border); border-radius:11px; background:var(--input-bg); cursor:pointer; transition:all .2s ease; }
  .lab-model-card:hover, .lab-model-card.selected, .lab-model-card.recommended { border-color:var(--primary); background:var(--primary-l); }
  .lab-model-card.recommended:not(.selected) { box-shadow:inset 3px 0 0 var(--primary); }
  .lab-model-card input { position:absolute; opacity:0; pointer-events:none; }
  .lab-model-mark { width:19px; height:19px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid color-mix(in srgb, var(--text-soft) 50%, transparent); border-radius:6px; color:transparent; font-size:9px; }
  .lab-model-card.selected .lab-model-mark { border-color:var(--primary); background:var(--primary); color:var(--accent); }
  .lab-model-card.selected .lab-model-mark i { display:block; opacity:1; transform:scale(1); }
  .lab-model-mark i { display:block; opacity:0; transform:scale(.5); transition:all .18s ease; }
  .lab-model-icon { width:32px; height:32px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:9px; background:var(--primary-l); color:var(--primary); font-size:13px; }
  .lab-model-copy { min-width:0; flex:1; }
  .lab-model-copy strong, .lab-model-copy small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .lab-model-copy strong { color:var(--text-h); font-size:11px; }
  .lab-model-copy small { margin-top:3px; color:var(--text-soft); font-size:9px; }
  .lab-model-recommendation { display:none; margin-top:3px; color:var(--primary); font-size:8px; font-style:normal; }
  .lab-model-card.recommended .lab-model-recommendation { display:block; }
  .lab-model-cost { flex-shrink:0; color:var(--text-soft); font-size:10px; }
  .lab-review { padding:14px; border:1px solid var(--border); border-radius:11px; background:var(--input-bg); }
  .lab-review-title { display:flex; align-items:center; gap:7px; color:var(--text-h); font-size:11px; font-weight:800; }
  .lab-review-title i { color:var(--primary); }
  .lab-review-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; margin-top:13px; }
  .lab-review-grid div { min-width:0; padding:10px; border:1px solid var(--border); border-radius:9px; background:var(--card-bg); }
  .lab-review-grid span, .lab-review-grid strong { display:block; }
  .lab-review-grid span { color:var(--text-soft); font-size:9px; }
  .lab-review-grid strong { margin-top:5px; overflow:hidden; color:var(--text-h); font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
  .lab-review > p { margin:10px 0 0; color:var(--text-soft); font-size:10px; }
  .lab-cost-table { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:7px; }
  .lab-cost-table > div { padding:9px; border:1px solid var(--border); border-radius:9px; background:var(--card-bg); }
  .lab-cost-table span, .lab-cost-table strong, .lab-cost-table small { display:block; }
  .lab-cost-table span { color:var(--text-soft); font-size:9px; }
  .lab-cost-table strong { margin-top:4px; color:var(--text-h); font-size:11px; }
  .lab-cost-table small { margin-top:3px; color:var(--text-soft); font-size:9px; }
  .lab-field-help { display:block; margin-top:4px; color:var(--text-soft); font-size:9px; line-height:1.8; }
  .lab-prompt-input { min-height:360px; line-height:1.9; resize:vertical; }
  .lab-modal { position:fixed; inset:0; z-index:120; display:flex; align-items:center; justify-content:center; padding:18px; background:color-mix(in srgb, var(--text-h) 55%, transparent); }
  .lab-modal.hidden { display:none; }
  .lab-modal-card { width:min(560px, 100%); max-height:85vh; overflow:auto; padding:18px; border:1px solid var(--border); border-radius:16px; background:var(--card-bg); box-shadow:var(--shadow-card); }
  .lab-confirm-summary { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:7px; }
  .lab-confirm-summary > div { padding:9px; border:1px solid var(--border); border-radius:9px; background:var(--input-bg); }
  .lab-confirm-summary span, .lab-confirm-summary strong { display:block; }
  .lab-confirm-summary span { color:var(--text-soft); font-size:9px; }
  .lab-confirm-summary strong { margin-top:4px; color:var(--text-h); font-size:10px; overflow-wrap:anywhere; }
  .lab-wizard-status { display:flex; align-items:center; gap:8px; margin-top:20px; padding:11px 13px; border-radius:9px; color:var(--primary); background:var(--input-bg); font-size:10px; }
  .lab-wizard-status.complete { color:var(--success); }
  .lab-wizard-status.failed { color:var(--danger); }
  .lab-model-results-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
  .lab-model-result-card { overflow:hidden; border:1px solid var(--border); border-radius:12px; background:var(--card-bg); }
  .lab-model-result-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px; border-bottom:1px solid var(--border); }
  .lab-model-result-head strong { color:var(--text-h); font-size:12px; }
  .lab-model-result-images { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:2px; background:var(--border); }
  .lab-model-result-images img { display:block; width:100%; aspect-ratio:4/5; object-fit:cover; background:var(--input-bg); }
  .lab-model-image-empty { min-height:190px; display:flex; align-items:center; justify-content:center; color:var(--text-soft); background:var(--input-bg); font-size:10px; }
  .lab-model-result-meta { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:6px; padding:10px; }
  .lab-model-result-meta > div { min-width:0; padding:8px; border:1px solid var(--border); border-radius:7px; background:var(--input-bg); }
  .lab-model-result-meta-wide { grid-column:1 / -1; }
  .lab-model-result-meta span, .lab-model-result-meta strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .lab-model-result-meta span { color:var(--text-soft); font-size:8px; }
  .lab-model-result-meta strong { margin-top:4px; color:var(--text-h); font-size:9px; }
  .lab-model-result-meta strong small { margin-right:5px; color:var(--text-soft); font-size:8px; }
  .lab-status-success { color:var(--success) !important; }
  .lab-status-failed { color:var(--danger) !important; }
  .lab-status-pending { color:var(--warning) !important; }
  .lab-wizard-evaluation-title { margin-bottom:9px; color:var(--text-h); font-size:11px; font-weight:800; }
  .lab-wizard-evaluation-table { width:100%; min-width:760px; border-collapse:collapse; }
  .lab-wizard-evaluation-table th, .lab-wizard-evaluation-table td { padding:9px; border-bottom:1px solid var(--border); text-align:right; vertical-align:top; font-size:9px; }
  .lab-wizard-evaluation-table th { color:var(--text-soft); background:var(--input-bg); font-size:8px; }
  .lab-wizard-evaluation-table td { color:var(--text-main); }
  .lab-wizard-evaluation-table .input-pro { height:28px; min-width:90px; font-size:9px; }
  .lab-actions { display:flex; align-items:center; gap:10px; margin-top:22px; padding-top:16px; border-top:1px solid var(--border); }
  @media (max-width: 767px) {
    .lab-step-item { padding:10px; }
    .lab-connector { width:1px; height:12px; margin-right:24px; }
    .lab-panel { padding:15px; }
    .lab-model-grid, .lab-review-grid, .lab-provider-grid, .lab-cost-table { grid-template-columns:1fr; }
    .lab-review-grid { gap:7px; }
    .lab-model-results-grid { grid-template-columns:1fr; }
    .lab-confirm-summary { grid-template-columns:1fr; }
    .lab-prompt-input { min-height:280px; }
  }
</style>
@endpush

@section('scripts')
<script>
(() => {
  const labProducts = @json($productOptions);
  const imageWrap = document.getElementById('lab-images');
  const imageEmpty = document.getElementById('lab-image-empty');
  const imageCount = document.getElementById('lab-image-count');
  const productSelect = document.getElementById('lab-product');
  const formError = document.getElementById('lab-form-error');
  const form = document.getElementById('lab-form');
  const titleInput = document.getElementById('lab-title');
  const promptInput = document.getElementById('lab-prompt');
  const aspectInput = document.getElementById('lab-aspect-ratio');
  const exchangeRateToman = Number(@json((float) (($exchange['rate'] ?? 0) / 10))) || 0;
  const statusUrlTemplate = @json(route('admin.lab.status', ['experiment' => '__EXPERIMENT__']));
  const detailsUrlTemplate = @json(route('admin.lab.show', ['experiment' => '__EXPERIMENT__']));
  const scoreUrlTemplate = @json(route('admin.lab.outputs.score', ['output' => '__OUTPUT__']));
  let titleTouched = Boolean(titleInput?.value.trim());
  let promptTouched = Boolean(promptInput?.value.trim());
  let aspectTouched = Boolean(@json(old('aspect_ratio')));
  let activeLabStep = 1;
  let labStatusUrl = null;
  let labDetailsUrl = null;
  let labPollTimer = null;
  let labSubmitting = false;

  function showLabError(message) {
    formError.textContent = message;
    formError.classList.remove('hidden');
    formError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function clearLabError() {
    formError.textContent = '';
    formError.classList.add('hidden');
  }

  function updateImageState(card) {
    const input = card.querySelector('input');
    card.classList.toggle('selected', input.checked);
    const selected = imageWrap.querySelectorAll('input:checked').length;
    imageCount.textContent = selected ? `${selected} تصویر انتخاب شده` : 'هنوز تصویری انتخاب نشده';
    document.getElementById('lab-summary-images').textContent = selected.toLocaleString('fa-IR');
  }

  function renderLabImages(id) {
    imageWrap.innerHTML = '';
    const item = labProducts[id];
    if (!item || !item.images || !item.images.length) {
      imageEmpty.querySelector('span').textContent = id ? 'این محصول تصویر قابل انتخاب ندارد.' : 'ابتدا یک محصول را انتخاب کنید.';
      imageEmpty.classList.remove('hidden');
      imageCount.textContent = 'هنوز تصویری انتخاب نشده';
      document.getElementById('lab-summary-images').textContent = '۰';
      return;
    }

    imageEmpty.classList.add('hidden');
    item.images.forEach((img, index) => {
      const card = document.createElement('label');
      card.className = `lab-image-card${index === 0 ? ' selected' : ''}`;

      const input = document.createElement('input');
      input.type = 'checkbox';
      input.name = 'images[]';
      input.value = img.path;
      input.checked = index === 0;
      input.addEventListener('change', () => updateImageState(card));

      const mark = document.createElement('span');
      mark.className = 'lab-image-mark';
      mark.innerHTML = '<i class="fa-solid fa-check"></i>';

      const image = document.createElement('img');
      image.src = img.url;
      image.alt = item.name || 'تصویر محصول';
      image.loading = 'lazy';

      const meta = document.createElement('span');
      meta.className = 'lab-image-meta';
      meta.textContent = img.width ? `${img.width}×${img.height}` : 'تصویر محصول';

      card.append(input, mark, image, meta);
      imageWrap.appendChild(card);
    });
    imageCount.textContent = '۱ تصویر انتخاب شده';
    document.getElementById('lab-summary-images').textContent = '۱';
  }

  function validateLabStep(step) {
    if (step === 1) {
      if (!productSelect.value) return 'ابتدا یک محصول را انتخاب کنید.';
      if (!imageWrap.querySelector('input:checked')) return 'حداقل یک تصویر را برای آزمایش انتخاب کنید.';
    }
    if (step === 2 && !document.querySelector('[name="models[]"]:checked')) {
      return 'حداقل یک مدل هوش مصنوعی را انتخاب کنید.';
    }
    return null;
  }

  function updateLabSummary() {
    const product = labProducts[productSelect.value];
    document.getElementById('lab-summary-product').textContent = product ? product.name : '—';
    const models = document.querySelectorAll('[name="models[]"]:checked');
    document.getElementById('lab-summary-models').textContent = models.length.toLocaleString('fa-IR');
    const scoringModel = document.getElementById('lab-scoring-model');
    const scoringSummary = document.getElementById('lab-summary-scoring-model');
    if (scoringSummary) scoringSummary.textContent = scoringModel?.value || 'openai/gpt-4o-mini';
    const count = Number(document.querySelector('[name="count"]').value || 1);
    const total = Array.from(models).reduce((sum, model) => sum + (Number(model.dataset.cost) || 0) * count, 0);
    document.getElementById('lab-cost-images-usd').textContent = `$${total.toFixed(4)}`;
    document.getElementById('lab-cost-images-irr').textContent = `${Math.round(total * exchangeRateToman).toLocaleString('fa-IR')} تومان`;
    document.getElementById('lab-estimate').textContent = `$${total.toFixed(4)}`;
    document.getElementById('lab-estimate-irr').textContent = `${Math.round(total * exchangeRateToman).toLocaleString('fa-IR')} تومان`;
  }

  function syncProductDefaults(force = false) {
    const product = labProducts[productSelect.value];
    if (!product) return;
    if (force || !titleTouched) titleInput.value = `آزمایش ${product.name}${product.code ? ` (${product.code})` : ''}`;
    if (force || !promptTouched) promptInput.value = product.prompt || '';
    if (force || !aspectTouched) aspectInput.value = '4:5';

  }

  function syncLabModelRecommendations(productId) {
    const product = labProducts[productId];
    const productCategoryIds = new Set((product?.category_ids || []).map(String));
    const cards = [...document.querySelectorAll('.lab-model-card[data-category-ids]')];
    let matched = 0;
    cards.forEach(card => {
      const modelCategoryIds = card.dataset.categoryIds ? card.dataset.categoryIds.split(',').filter(Boolean) : [];
      const recommended = productCategoryIds.size > 0 && modelCategoryIds.some(categoryId => productCategoryIds.has(categoryId));
      card.classList.toggle('recommended', recommended);
      if (recommended) matched++;
    });
    const context = document.getElementById('lab-model-context');
    if (!context) return;
    context.innerHTML = matched
      ? `<i class="fa-solid fa-lightbulb"></i><span>${matched.toLocaleString('fa-IR')} مدل مرتبط با دسته‌ی این محصول پیشنهاد شده است؛ مدل‌های دیگر هم قابل انتخاب‌اند.</span>`
      : '<i class="fa-solid fa-circle-info"></i><span>برای این محصول مدل دسته‌بندی‌شده‌ای ثبت نشده است؛ همه‌ی مدل‌های فعال نمایش داده شده‌اند.</span>';
  }

  function setLabStep(step, validateCurrent = true) {
    if (step === activeLabStep) return;
    if (step > activeLabStep && validateCurrent) {
      const error = validateLabStep(activeLabStep);
      if (error) { showLabError(error); return; }
    }
    clearLabError();
    activeLabStep = Math.max(1, Math.min(4, step));
    document.querySelectorAll('.lab-panel').forEach(panel => panel.classList.toggle('hidden', Number(panel.dataset.panel) !== activeLabStep));
    document.querySelectorAll('.step-item[data-step]').forEach(button => {
      const buttonStep = Number(button.dataset.step);
      const active = buttonStep === activeLabStep;
      const done = buttonStep < activeLabStep;
      const circle = button.querySelector('.step-circle');
      const label = button.querySelector('.step-label');
      const title = button.querySelector('.step-title');
      const number = button.querySelector('.step-circle');
      const check = button.querySelector('.step-check');
      button.classList.toggle('bg-[var(--accent)]/10', active);
      button.classList.toggle('border-[var(--accent)]/25', active);
      button.classList.toggle('step-tab-active', active);
      button.classList.toggle('border-transparent', !active);
      circle?.classList.toggle('border-[var(--accent)]', active);
      circle?.classList.toggle('bg-[var(--accent)]/15', active);
      circle?.classList.toggle('text-[var(--accent)]', active);
      circle?.classList.toggle('border-[var(--b2)]', !active && !done);
      circle?.classList.toggle('text-[var(--text3)]', !active && !done);
      circle?.classList.toggle('border-[var(--green)]', done);
      circle?.classList.toggle('bg-[var(--green)]/10', done);
      circle?.classList.toggle('text-[var(--green)]', done);
      if (number) number.innerHTML = done ? '<i class="fa-solid fa-check text-[10px]"></i>' : number.dataset.num;
      label?.classList.toggle('text-[var(--accent)]', active);
      label?.classList.toggle('text-[var(--text3)]', !active);
      title?.classList.toggle('text-[var(--text)]', active);
      title?.classList.toggle('text-[var(--text2)]', !active);
      check?.classList.toggle('hidden', !done);
    });
    document.getElementById('conn-1')?.classList.toggle('bg-[var(--accent)]', activeLabStep > 1);
    document.getElementById('conn-2')?.classList.toggle('bg-[var(--accent)]', activeLabStep > 2);
    document.getElementById('conn-3')?.classList.toggle('bg-[var(--accent)]', activeLabStep > 3);
    document.getElementById('conn-1-m')?.classList.toggle('bg-[var(--accent)]', activeLabStep > 1);
    document.getElementById('conn-2-m')?.classList.toggle('bg-[var(--accent)]', activeLabStep > 2);
    document.getElementById('conn-3-m')?.classList.toggle('bg-[var(--accent)]', activeLabStep > 3);
    updateLabSummary();
  }

  productSelect.addEventListener('change', event => {
    renderLabImages(event.target.value);
    titleTouched = false;
    promptTouched = false;
    aspectTouched = false;
    syncProductDefaults();
    syncLabModelRecommendations(event.target.value);
    updateLabSummary();
    clearLabError();
  });

  document.querySelectorAll('[data-next]').forEach(button => button.addEventListener('click', () => setLabStep(Number(button.dataset.next))));
  document.querySelectorAll('[data-prev]').forEach(button => button.addEventListener('click', () => setLabStep(Number(button.dataset.prev), false)));
  document.querySelectorAll('.step-item[data-step]').forEach(button => button.addEventListener('click', () => setLabStep(Number(button.dataset.step), false)));
  document.querySelectorAll('[name="models[]"]').forEach(input => input.addEventListener('change', () => {
    input.closest('.lab-model-card').classList.toggle('selected', input.checked);
    updateLabSummary();
    clearLabError();
  }));
  document.querySelector('[name="count"]').addEventListener('change', updateLabSummary);
  document.getElementById('lab-scoring-model')?.addEventListener('change', updateLabSummary);
  titleInput?.addEventListener('input', () => { titleTouched = true; });
  promptInput?.addEventListener('input', () => { promptTouched = true; });
  aspectInput?.addEventListener('input', () => { aspectTouched = true; });

  function formatToman(usd) {
    return `${Math.round(Number(usd || 0) * exchangeRateToman).toLocaleString('fa-IR')} تومان`;
  }

  function renderWizardEvaluation(runs) {
    const holder = document.getElementById('lab-wizard-evaluation');
    const normalizedRuns = Array.isArray(runs) ? runs : [runs];
    const outputs = normalizedRuns.flatMap(run => (run?.outputs || []).map(output => ({ run, output })));
    if (!outputs.length) { holder.classList.add('hidden'); return; }
    const csrf = document.querySelector('input[name="_token"]')?.value || '';
    const rows = outputs.map(({ run, output }) => {
      const scores = output.scores || [];
      const average = scores.length ? (scores.reduce((sum, score) => sum + Number(score.score || 0), 0) / scores.length).toFixed(1) : '—';
      const breakdown = scores.length ? scores.map(score => `<span>${escapeHtml(score.criterion)}: <b>${escapeHtml(score.score)}/۵</b></span>`).join('') : '<span>در انتظار ارزیابی</span>';
      const action = scoreUrlTemplate.replace('__OUTPUT__', encodeURIComponent(output.id));
      return `<tr><td><strong>${escapeHtml(run.grade_label || 'استاندارد')} · ${escapeHtml(run.alias || run.model_id)}</strong><small>#${escapeHtml(output.id)} · ${escapeHtml(run.role || 'primary')}</small></td><td><b class="lab-ai-score">${escapeHtml(average)} از ۵</b><small>${escapeHtml(document.getElementById('lab-scoring-model')?.value || 'openai/gpt-4o-mini')}</small></td><td><div class="lab-score-breakdown">${breakdown}</div></td><td><form method="POST" action="${action}" class="lab-wizard-score-form"><input type="hidden" name="_token" value="${escapeHtml(csrf)}"><div class="flex items-center gap-2"><input type="number" name="manual_score" min="1" max="5" step=".1" value="${escapeHtml(output.manual_score || '')}" class="input-pro" placeholder="۱ تا ۵"><label><input type="checkbox" name="is_winner" value="1" ${output.is_winner ? 'checked' : ''}> برنده</label></div><div class="flex items-center gap-2 mt-2"><input type="text" name="note" value="${escapeHtml(output.note || '')}" class="input-pro" placeholder="یادداشت"><button class="icon-action-btn" title="ذخیره"><i class="fa-solid fa-floppy-disk"></i></button></div></form></td></tr>`;
    }).join('');
    holder.innerHTML = `<div class="lab-wizard-evaluation-title">نمره‌دهی و همه‌ی توضیحات</div><div class="overflow-x-auto"><table class="lab-wizard-evaluation-table"><thead><tr><th>مدل / خروجی</th><th>امتیاز ارزیاب</th><th>جزئیات</th><th>امتیاز دستی، برنده و یادداشت</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    holder.classList.remove('hidden');
  }

  function renderWizardResults(data) {
    const holder = document.getElementById('lab-wizard-results');
    const runs = data.runs || [];
    if (!runs.length) return;
    const cards = runs.map((run, index) => {
      const outputs = run.outputs || [];
      const cost = Number(run.actual_cost_usd || run.estimated_cost_usd || 0);
      const statusClass = run.status === 'completed' ? 'lab-status-success' : (run.status === 'failed' ? 'lab-status-failed' : 'lab-status-pending');
      const statusLabel = run.status_label || run.status || 'در انتظار';
      const retries = Number(run.retry_count || 0);
      const modelNumber = (index + 1).toLocaleString('fa-IR');
      const images = outputs.length ? outputs.map(output => output.url ? `<a href="${escapeHtml(output.url)}" target="_blank" rel="noopener"><img src="${escapeHtml(output.url)}" alt="خروجی ${escapeHtml(run.alias || run.model_id)}" loading="lazy"></a>` : '<div class="lab-model-image-empty"><i class="fa-regular fa-image"></i></div>').join('') : `<div class="lab-model-image-empty"><i class="fa-solid fa-spinner fa-spin"></i><span>${escapeHtml(run.status === 'failed' ? (run.error_message || 'اجرای مدل ناموفق بود.') : 'در انتظار خروجی…')}</span></div>`;
      return `<article class="lab-model-result-card"><header class="lab-model-result-head"><strong>مدل هوش مصنوعی شماره ${modelNumber}</strong><span class="badge-pro ${run.status === 'completed' ? 'badge-success' : (run.status === 'failed' ? 'badge-danger' : 'badge-warning')}">${escapeHtml(statusLabel)}</span></header><div class="lab-model-result-images">${images}</div><div class="lab-model-result-meta"><div class="lab-model-result-meta-wide"><span>مدل هوش مصنوعی</span><strong>${escapeHtml(run.alias || run.model_id || '—')} <small dir="ltr">${escapeHtml(run.provider || '')} · ${escapeHtml(run.model_id || '')}</small></strong></div><div><span>زمان پاسخگویی (Execution Time)</span><strong dir="ltr">${run.duration_ms ? `${(Number(run.duration_ms) / 1000).toFixed(1)} ثانیه` : '—'}</strong></div><div class="lab-model-result-meta-wide"><span>قیمت تمام‌شده (Exact Cost)</span><strong dir="ltr">$${cost.toFixed(4)} <small>${formatToman(cost)}</small></strong></div><div><span>وضعیت موفقیت (Status)</span><strong class="${statusClass}">${escapeHtml(statusLabel)}</strong></div><div><span>تعداد تلاش مجدد (Retries)</span><strong>${retries.toLocaleString('fa-IR')} بار</strong></div></div></article>`;
    }).join('');
    holder.innerHTML = `<div class="lab-model-results-grid">${cards}</div>`;
    holder.classList.remove('hidden');
    renderWizardEvaluation(runs);
  }

  function renderWizardStatus(data) {
    const status = document.getElementById('lab-wizard-status');
    const runs = data.runs || [];
    const run = runs[0];
    const completed = data.status === 'completed';
    const failed = data.status === 'failed';
    const hasOutput = runs.some(item => item.outputs?.length);
    status.classList.toggle('complete', completed);
    status.classList.toggle('failed', failed);
    status.innerHTML = completed ? '<i class="fa-solid fa-circle-check"></i><span>آزمایش کامل شد؛ همه‌ی مدل‌ها کنار هم و جدول ارزیابی آماده است.</span>' : (failed ? `<i class="fa-solid fa-circle-exclamation"></i><span>${escapeHtml(run?.error_message || 'اجرای آزمایش با خطا متوقف شد.')}</span>` : `<i class="fa-solid fa-arrows-rotate fa-spin"></i><span>${hasOutput ? 'بخشی از خروجی‌ها تولید شد؛ ارزیابی هوش مصنوعی در حال انجام است…' : 'آزمایش در حال اجراست و منتظر دریافت خروجی همه‌ی مدل‌ها هستیم…'}</span>`);
    renderWizardResults(data);
    if (completed || failed) {
      if (labPollTimer) window.clearTimeout(labPollTimer);
      document.getElementById('lab-wizard-details').href = labDetailsUrl || '#';
      document.getElementById('lab-wizard-details').classList.remove('hidden');
    }
  }

  async function pollLabStatus() {
    if (!labStatusUrl) return;
    try {
      const response = await fetch(labStatusUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'وضعیت آزمایش قابل دریافت نیست.');
      renderWizardStatus(data);
      if (!['completed', 'failed', 'cancelled'].includes(data.status)) labPollTimer = window.setTimeout(pollLabStatus, 3000);
    } catch (error) {
      document.getElementById('lab-wizard-status').className = 'lab-wizard-status failed';
      document.getElementById('lab-wizard-status').innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i><span>${escapeHtml(error.message)}</span>`;
      labPollTimer = window.setTimeout(pollLabStatus, 5000);
    }
  }

  async function startLabExecution() {
    if (labSubmitting) return;
    labSubmitting = true;
    closeLabModal();
    clearLabError();
    setLabStep(4, false);
    document.getElementById('lab-wizard-status').className = 'lab-wizard-status';
    document.getElementById('lab-wizard-status').innerHTML = '<i class="fa-solid fa-arrows-rotate fa-spin"></i><span>در حال ثبت آزمایش و انتقال به گام چهارم…</span>';
    const body = new FormData(form);
    body.set('ajax', '1');
    try {
      const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await response.json();
      if (!response.ok) {
        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'ثبت آزمایش ناموفق بود.');
        throw new Error(errors);
      }
      labStatusUrl = data.status_url;
      labDetailsUrl = data.redirect_url || detailsUrlTemplate.replace('__EXPERIMENT__', data.id);
      labSubmitting = false;
      pollLabStatus();
    } catch (error) {
      labSubmitting = false;
      setLabStep(3, false);
      showLabError(error.message);
    }
  }

  const modal = document.getElementById('lab-confirm-modal');
  const modalSummary = document.getElementById('lab-confirm-summary');
  const scoringModel = document.getElementById('lab-scoring-model');
  const resolutionInput = document.getElementById('lab-resolution');
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character]));
  function closeLabModal() { modal?.classList.add('hidden'); }
  function openLabModal() {
    const product = labProducts[productSelect.value];
    const models = [...document.querySelectorAll('[name="models[]"]:checked')].map(input => input.closest('.lab-model-card')?.querySelector('.lab-model-copy strong')?.textContent || input.value);
    const count = Number(document.querySelector('[name="count"]').value || 1);
    const estimate = document.getElementById('lab-estimate')?.textContent || '$0.0000';
    const estimateIrr = document.getElementById('lab-estimate-irr')?.textContent || '۰ تومان';
    const rows = [
      ['محصول', product?.name || '—'],
      ['عنوان آزمایش', titleInput.value || '—'],
      ['تصاویر', `${imageWrap.querySelectorAll('input:checked').length.toLocaleString('fa-IR')} تصویر`],
      ['مدل‌ها', models.length ? models.join('، ') : '—'],
      ['خروجی برای هر مدل', `${count.toLocaleString('fa-IR')} خروجی`],
      ['رزولوشن / نسبت تصویر', `${resolutionInput.value || '—'} / ${aspectInput.value || '—'}`],
      ['ارزیاب', scoringModel?.selectedOptions[0]?.textContent.trim() || 'GPT-4o-mini · OpenRouter'],
      ['هزینه تخمینی', `${estimate} · ${estimateIrr}`],
    ];
    modalSummary.innerHTML = rows.map(([label, value]) => `<div><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`).join('');
    modal?.classList.remove('hidden');
  }
  document.querySelectorAll('[data-close-lab-modal]').forEach(button => button.addEventListener('click', closeLabModal));
  document.getElementById('lab-confirm-submit')?.addEventListener('click', startLabExecution);
  form.addEventListener('submit', event => {
    event.preventDefault();
    const error = validateLabStep(1) || validateLabStep(2);
    if (error) { showLabError(error); return; }
    startLabExecution();
  });

  const initialProductId = @json(old('product_id', $selectedProductId));
  if (initialProductId) {
    renderLabImages(initialProductId);
    syncProductDefaults();
    syncLabModelRecommendations(initialProductId);
  }
  document.querySelectorAll('[name="models[]"]:checked').forEach(input => input.closest('.lab-model-card').classList.add('selected'));
  setLabStep(1, false);
  updateLabSummary();
})();
</script>
@endsection
