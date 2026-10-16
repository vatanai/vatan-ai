@extends('layouts.admin')
@section('title', 'بات ثبت محصول — پروداکتی')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/telegram-product-settings.css') }}?v={{ filemtime(public_path('admin/css/telegram-product-settings.css')) }}">
@endpush

@php
  $fa = fn ($v) => strtr((string) $v, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
  $selectedFallbacks = fn (string $level) => array_map('intval', (array) data_get(old() ?: $values, "models.quality.{$level}.fallback_ids", []));
@endphp

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content" dir="rtl">
    <div class="telegram-product-settings-page">
      @if(session('success'))
        <div class="tps-alert tps-alert--success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
      @endif
      @if(isset($errors) && $errors->any())
        <div class="tps-alert tps-alert--error"><i class="fa-solid fa-triangle-exclamation"></i><span>{{ $errors->first() }}</span></div>
      @endif

      <header class="tps-hero">
        <div>
          <div class="tps-eyebrow"><i class="fa-solid fa-box-open"></i> تنظیمات تلگرام · بات ثبت محصول</div>
          <h1>مسیر پروداکتی</h1>
          <p>مدیر در بات «ثبت محصول ← پروداکتی» را می‌زند، عکس ورودی و اسلایدها (عکس نمونه + پرامپت ساخت) را می‌فرستد و هوش مصنوعی بقیه را آماده می‌کند. همه‌ی رفتار این مسیر از همین صفحه کنترل می‌شود.</p>
        </div>
        <span class="tps-hero__badge"><i class="fa-solid fa-shield-halved"></i> فقط رهبر پنل</span>
      </header>

      @include('admin.settings.partials.telegram-product-tabs', ['active' => 'productshot'])

      <div class="tps-stats tps-stats--three">
        <div class="tps-stat"><span class="tps-stat__icon is-warning"><i class="fa-solid fa-spinner"></i></span><span><small>ثبت‌های باز پروداکتی</small><strong>{{ $fa(number_format($stats['open'])) }}</strong></span></div>
        <div class="tps-stat"><span class="tps-stat__icon is-primary"><i class="fa-solid fa-box"></i></span><span><small>محصولات ثبت‌شده از بات</small><strong>{{ $fa(number_format($stats['saved'])) }}</strong></span></div>
        <div class="tps-stat"><span class="tps-stat__icon is-success"><i class="fa-solid fa-circle-check"></i></span><span><small>منتشرشده</small><strong>{{ $fa(number_format($stats['published'])) }}</strong></span></div>
      </div>

      <section class="tps-card">
        <div class="tps-card__head"><div><h2>مسیر مدیر در بات</h2><p>همین ترتیب در تلگرام اجرا می‌شود؛ پیام‌های مدیر بلافاصله پاک می‌شوند و در پایان فقط پیام ثبت نهایی می‌ماند.</p></div><i class="fa-solid fa-route tps-card__head-icon"></i></div>
        <ol class="tps-steps">
          <li><b>۱</b><span><strong>عکس ورودی</strong><small>یک عکس؛ در صفحه‌ی محصول با برچسب «عکس ورودی»</small></span></li>
          <li><b>۲</b><span><strong>اسلایدها</strong><small>هر اسلاید: عکس نمونه + پرامپت (چند پیام هم مجاز است)</small></span></li>
          <li><b>۳</b><span><strong>آماده‌سازی هوشمند</strong><small>اصلاح پرامپت‌ها، نام، توضیح، صنف و دسته</small></span></li>
          <li><b>۴</b><span><strong>بازبینی</strong><small>اصلاح نام‌ها، صنف، دسته و اسلایدها</small></span></li>
          <li><b>۵</b><span><strong>ثبت</strong><small>پیش‌نویس یا انتشار + قاب‌های اکسپلور</small></span></li>
        </ol>
      </section>

      <form method="POST" action="{{ route('admin.settings.telegram.product-bot.productshot.update') }}" class="tps-settings-form" data-tps-form>
        @csrf @method('PUT')

        {{-- ── هوش مصنوعی ── --}}
        <section class="tps-card">
          <div class="tps-card__head"><div><h2>پرامپت‌های هوش مصنوعی</h2><p>قالب خروجی (JSON) در کد ثابت است؛ تغییر این متن‌ها هیچ‌وقت ساختار بات را خراب نمی‌کند.</p></div><i class="fa-solid fa-wand-magic-sparkles tps-card__head-icon"></i></div>
          <div class="tps-section">
            <div class="tps-fields tps-fields--2">
              <label class="tps-field"><span>مدل متنی هوش مصنوعی</span><input class="tps-input tps-ltr" dir="ltr" name="ai[model]" value="{{ old('ai.model', data_get($values, 'ai.model')) }}" required placeholder="openai/gpt-4o-mini"><small>شناسه‌ی OpenRouter؛ برای پرامپت مادر و اصلاح‌گر.</small></label>
              <div class="tps-switch-row">
                <div><strong>دیدن عکس‌ها</strong><small>عکس ورودی و نمونه‌ی ۳ اسلاید اول هم به مدل داده شود (دقیق‌تر، کمی گران‌تر).</small></div>
                <label class="tps-switch"><input type="hidden" name="ai[use_vision]" value="0"><input type="checkbox" name="ai[use_vision]" value="1" @checked(old('ai.use_vision', data_get($values, 'ai.use_vision')))><span></span></label>
              </div>
            </div>

            <div class="tps-prompt-block">
              <div class="tps-prompt-block__head">
                <div><strong><i class="fa-solid fa-crown"></i> پرامپت مادر</strong><small>نام فارسی/انگلیسی، توضیح، هشتگ، نام هر اسلاید، صنف و دسته را می‌سازد.</small></div>
                <button type="button" class="btn-pro btn-pro-ghost tps-small-button" data-reset-target="tps-metadata-prompt" data-reset-value="{{ $defaults['ai']['metadata_prompt'] }}"><i class="fa-solid fa-rotate-left"></i> پیش‌فرض</button>
              </div>
              <textarea id="tps-metadata-prompt" class="tps-input tps-prompt-input" name="ai[metadata_prompt]" required minlength="30" maxlength="12000" data-counter>{{ old('ai.metadata_prompt', data_get($values, 'ai.metadata_prompt')) }}</textarea>
              <div class="tps-counter" data-counter-for="tps-metadata-prompt"></div>
            </div>

            <div class="tps-prompt-block">
              <div class="tps-prompt-block__head">
                <div><strong><i class="fa-solid fa-pen-ruler"></i> پرامپت اصلاح‌گر اسلایدها</strong><small>پرامپت هر اسلاید فقط بر اساس همین متن اصلاح می‌شود. مدیر فقط پیام «اصلاح شد» را می‌بیند، نه متن اصلاح‌شده را.</small></div>
                <div class="tps-inline">
                  <label class="tps-switch" title="روشن/خاموش اصلاح پرامپت"><input type="hidden" name="ai[optimizer_enabled]" value="0"><input type="checkbox" name="ai[optimizer_enabled]" value="1" @checked(old('ai.optimizer_enabled', data_get($values, 'ai.optimizer_enabled')))><span></span></label>
                  <button type="button" class="btn-pro btn-pro-ghost tps-small-button" data-reset-target="tps-optimizer-prompt" data-reset-value="{{ $defaults['ai']['optimizer_prompt'] }}"><i class="fa-solid fa-rotate-left"></i> پیش‌فرض</button>
                </div>
              </div>
              <textarea id="tps-optimizer-prompt" class="tps-input tps-prompt-input tps-ltr" dir="ltr" name="ai[optimizer_prompt]" required minlength="30" maxlength="12000" data-counter>{{ old('ai.optimizer_prompt', data_get($values, 'ai.optimizer_prompt')) }}</textarea>
              <div class="tps-counter" data-counter-for="tps-optimizer-prompt"></div>
              <p class="tps-muted">اگر پاسخ اصلاح‌گر خالی یا ناقص باشد، پرامپت اصلی مدیر بدون تغییر ذخیره می‌شود. پرامپت اصلی هم همیشه برای مرجع نگه داشته می‌شود.</p>
            </div>
          </div>
        </section>

        <div class="tps-grid tps-grid--even">
          {{-- ── پیش‌فرض اسلایدها ── --}}
          <section class="tps-card">
            <div class="tps-card__head"><div><h2>پیش‌فرض اسلایدها</h2><p>برای هر اسلایدی که از بات ثبت می‌شود؛ بعداً در فرم استودیو محصول قابل تغییر است.</p></div><i class="fa-solid fa-images tps-card__head-icon"></i></div>
            <div class="tps-section">
              <div class="tps-fields tps-fields--2">
                <label class="tps-field"><span>حداکثر اسلاید هر محصول</span><input class="tps-input" type="number" min="1" max="30" name="slides[max_slides]" value="{{ old('slides.max_slides', data_get($values, 'slides.max_slides')) }}" required></label>
                <label class="tps-field"><span>حداکثر طول پرامپت هر اسلاید</span><input class="tps-input" type="number" min="500" max="30000" step="500" name="slides[max_prompt_chars]" value="{{ old('slides.max_prompt_chars', data_get($values, 'slides.max_prompt_chars')) }}" required><small>کاراکتر؛ پیام‌های بلند تلگرام خودکار به هم چسبانده می‌شوند.</small></label>
              </div>
              <div class="tps-subtitle">اعتبار هر خروجی</div>
              <div class="tps-fields tps-fields--3">
                @foreach($qualityLevels as $level => $label)
                  <label class="tps-field"><span>{{ $label }}</span><input class="tps-input" type="number" min="0" max="1000" name="slides[credits][{{ $level }}]" value="{{ old("slides.credits.{$level}", data_get($values, "slides.credits.{$level}")) }}" required></label>
                @endforeach
              </div>
              <div class="tps-subtitle">نسبت تصویر</div>
              <div class="tps-chip-group">
                @foreach($aspectRatios as $ratio)
                  <label class="tps-chip"><input type="checkbox" name="slides[allowed_aspect_ratios][]" value="{{ $ratio }}" @checked(in_array($ratio, (array) old('slides.allowed_aspect_ratios', data_get($values, 'slides.allowed_aspect_ratios', [])), true))><span dir="ltr">{{ $ratio }}</span></label>
                @endforeach
              </div>
              <div class="tps-fields tps-fields--2">
                <label class="tps-field"><span>نسبت پیش‌فرض</span><select class="tps-input" name="slides[aspect_ratio_default]">@foreach($aspectRatios as $ratio)<option value="{{ $ratio }}" @selected(old('slides.aspect_ratio_default', data_get($values, 'slides.aspect_ratio_default')) === $ratio)>{{ $ratio }}</option>@endforeach</select></label>
                <div class="tps-switch-row">
                  <div><strong>انتخاب نسبت توسط کاربر</strong><small>کاربر در صفحه‌ی ساخت نسبت را عوض کند.</small></div>
                  <label class="tps-switch"><input type="hidden" name="slides[aspect_ratio_user_selectable]" value="0"><input type="checkbox" name="slides[aspect_ratio_user_selectable]" value="1" @checked(old('slides.aspect_ratio_user_selectable', data_get($values, 'slides.aspect_ratio_user_selectable')))><span></span></label>
                </div>
              </div>
              <p class="tps-muted"><i class="fa-solid fa-circle-info"></i> در صفحه‌ی ساخت هیچ اسلایدی از پیش تیک نمی‌خورد؛ کاربر خودش انتخاب می‌کند.</p>
            </div>
          </section>

          {{-- ── مدل‌های تصویر ── --}}
          <section class="tps-card">
            <div class="tps-card__head"><div><h2>مدل‌های ساخت تصویر</h2><p>خالی = همان مدل‌های آخرین محصول پروداکتی ثبت‌شده در پنل.</p></div><i class="fa-solid fa-microchip tps-card__head-icon"></i></div>
            <div class="tps-section">
              @foreach($qualityLevels as $level => $label)
                <div class="tps-model-row">
                  <div class="tps-model-row__label">{{ $label }}</div>
                  <label class="tps-field"><span>مدل اصلی</span>
                    <select class="tps-input" name="models[quality][{{ $level }}][primary_id]">
                      <option value="">خودکار (آخرین محصول پروداکتی)</option>
                      @foreach($models as $model)<option value="{{ $model->id }}" @selected((int) old("models.quality.{$level}.primary_id", data_get($values, "models.quality.{$level}.primary_id")) === (int) $model->id)>{{ $model->name }} · {{ $model->provider }}</option>@endforeach
                    </select>
                  </label>
                  <label class="tps-field"><span>جایگزین‌ها (تا ۳)</span>
                    <select class="tps-input tps-multi" name="models[quality][{{ $level }}][fallback_ids][]" multiple size="3">
                      @foreach($models as $model)<option value="{{ $model->id }}" @selected(in_array((int) $model->id, $selectedFallbacks($level), true))>{{ $model->name }}</option>@endforeach
                    </select>
                  </label>
                </div>
              @endforeach
              @if($models->isEmpty())
                <p class="tps-muted"><i class="fa-solid fa-triangle-exclamation"></i> هیچ مدل تصویری فعالی پیدا نشد؛ از «مدل‌ها» مدل تصویر‌به‌تصویر فعال کنید.</p>
              @endif
            </div>
          </section>
        </div>

        <div class="tps-grid tps-grid--even">
          {{-- ── کنترل کیفیت ── --}}
          <section class="tps-card">
            <div class="tps-card__head"><div><h2>کنترل کیفیت عکس کاربر</h2><p>قبل از ساخت، عکس محصولی که کاربر می‌دهد بررسی می‌شود.</p></div><i class="fa-solid fa-magnifying-glass-chart tps-card__head-icon"></i></div>
            <div class="tps-section">
              <div class="tps-switch-row">
                <div><strong>بررسی هوشمند عکس ورودی کاربر</strong><small>خطای مسدودکننده اجازه‌ی ساخت نمی‌دهد و اعتبار کم نمی‌شود.</small></div>
                <label class="tps-switch"><input type="hidden" name="quality[preflight_enabled]" value="0"><input type="checkbox" name="quality[preflight_enabled]" value="1" @checked(old('quality.preflight_enabled', data_get($values, 'quality.preflight_enabled')))><span></span></label>
              </div>
              <div class="tps-subtitle">خطاهای مسدودکننده</div>
              <div class="tps-chip-group">
                @foreach($issues as $key => $label)
                  <label class="tps-chip"><input type="checkbox" name="quality[blocking_issues][]" value="{{ $key }}" @checked(in_array($key, (array) old('quality.blocking_issues', data_get($values, 'quality.blocking_issues', [])), true))><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <div class="tps-fields tps-fields--2">
                <label class="tps-field"><span>حداقل ضلع تصویر (پیکسل)</span><input class="tps-input" type="number" min="500" max="3000" name="quality[min_side]" value="{{ old('quality.min_side', data_get($values, 'quality.min_side')) }}" required></label>
                <label class="tps-field"><span>اندازه پروداکت‌شیت</span><select class="tps-input" name="quality[product_sheet_size]">@foreach([1536 => '۱۵۳۶ پیکسل', 2048 => '۲۰۴۸ پیکسل — پیشنهادی', 3072 => '۳۰۷۲ پیکسل'] as $size => $label)<option value="{{ $size }}" @selected((int) old('quality.product_sheet_size', data_get($values, 'quality.product_sheet_size')) === $size)>{{ $label }}</option>@endforeach</select></label>
              </div>
              <div class="tps-switch-row">
                <div><strong>پروداکت‌شیت چندزاویه‌ای</strong><small>چند عکس کاربر در یک شیت مرجع چیده می‌شود.</small></div>
                <label class="tps-switch"><input type="hidden" name="quality[product_sheet_enabled]" value="0"><input type="checkbox" name="quality[product_sheet_enabled]" value="1" @checked(old('quality.product_sheet_enabled', data_get($values, 'quality.product_sheet_enabled')))><span></span></label>
              </div>
            </div>
          </section>

          {{-- ── نمایش ── --}}
          <section class="tps-card">
            <div class="tps-card__head"><div><h2>نمایش محصول در سایت</h2><p>پیش‌فرض کارت و صفحه‌ی محصول‌هایی که از بات ثبت می‌شوند.</p></div><i class="fa-solid fa-display tps-card__head-icon"></i></div>
            <div class="tps-section">
              <div class="tps-fields tps-fields--2">
                <label class="tps-field"><span>حالت نمایش</span><select class="tps-input" name="display[display_mode]">@foreach(['card' => 'کارت پک', 'slider' => 'اسلایدر'] as $key => $label)<option value="{{ $key }}" @selected(old('display.display_mode', data_get($values, 'display.display_mode')) === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="tps-field"><span>شکل کارت</span><select class="tps-input" name="display[card_shape]">@foreach(['portrait' => 'عمودی', 'square' => 'مربع', 'landscape' => 'افقی'] as $key => $label)<option value="{{ $key }}" @selected(old('display.card_shape', data_get($values, 'display.card_shape')) === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="tps-field"><span>چیدمان نمونه‌ها</span><select class="tps-input" name="display[gallery_layout]">@foreach(['slider' => 'اسلایدی', 'grid' => 'شبکه‌ای', 'masonry' => 'آبشاری'] as $key => $label)<option value="{{ $key }}" @selected(old('display.gallery_layout', data_get($values, 'display.gallery_layout')) === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="tps-field"><span>متن نشان کارت</span><input class="tps-input" name="display[card_label]" maxlength="100" value="{{ old('display.card_label', data_get($values, 'display.card_label')) }}"></label>
              </div>
              <div class="tps-subtitle">قاب‌های پیش‌فرض اکسپلور</div>
              <div class="tps-chip-group">
                @foreach($tiles as $key => $label)
                  <label class="tps-chip"><input type="checkbox" name="display[explore_tiles][]" value="{{ $key }}" @checked(in_array($key, (array) old('display.explore_tiles', data_get($values, 'display.explore_tiles', [])), true))><span>{{ $label }}</span></label>
                @endforeach
              </div>
              <div class="tps-switch-row">
                <div><strong>واترمارک خروجی‌ها</strong><small>روی عکس‌هایی که کاربر می‌سازد.</small></div>
                <label class="tps-switch"><input type="hidden" name="display[watermark_enabled]" value="0"><input type="checkbox" name="display[watermark_enabled]" value="1" @checked(old('display.watermark_enabled', data_get($values, 'display.watermark_enabled')))><span></span></label>
              </div>
            </div>
          </section>
        </div>

        {{-- ── متن پیام‌ها ── --}}
        <section class="tps-card">
          <div class="tps-card__head"><div><h2>متن پیام‌های بات</h2><p>HTML ساده‌ی تلگرام مجاز است (&lt;b&gt;، &lt;i&gt;، &lt;code&gt;). <span dir="ltr">{n}</span> شماره‌ی اسلاید و <span dir="ltr">{count}</span> تعداد اسلایدهاست.</p></div><i class="fa-solid fa-comment-dots tps-card__head-icon"></i></div>
          <div class="tps-texts">
            @foreach($textLabels as $key => [$label, $hint])
              <details class="tps-text" @if($loop->first || $errors->has("texts.{$key}")) open @endif>
                <summary><span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span><i class="fa-solid fa-chevron-down"></i></summary>
                <div class="tps-text__body">
                  <textarea id="tps-text-{{ $key }}" class="tps-input tps-text-input" name="texts[{{ $key }}]" maxlength="3000" required data-counter>{{ old("texts.{$key}", data_get($values, "texts.{$key}")) }}</textarea>
                  <div class="tps-text__foot"><span class="tps-counter" data-counter-for="tps-text-{{ $key }}"></span><button type="button" class="btn-pro btn-pro-ghost tps-small-button" data-reset-target="tps-text-{{ $key }}" data-reset-value="{{ $defaults['texts'][$key] ?? '' }}"><i class="fa-solid fa-rotate-left"></i> پیش‌فرض</button></div>
                </div>
              </details>
            @endforeach
          </div>
        </section>

        <div class="tps-savebar">
          <span class="tps-savebar__status" data-dirty-status><i class="fa-solid fa-circle-check"></i> همه‌ی تغییرات ذخیره شده است</span>
          <button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره و اعمال</button>
        </div>
      </form>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  var form = document.querySelector('[data-tps-form]');
  if (!form) return;
  var fa = function (v) { return String(v).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
  function count(textarea) {
    var target = form.querySelector('[data-counter-for="' + textarea.id + '"]');
    if (target) target.textContent = fa(textarea.value.length.toLocaleString('en-US')) + ' / ' + fa(Number(textarea.maxLength).toLocaleString('en-US')) + ' کاراکتر';
  }
  form.querySelectorAll('[data-counter]').forEach(function (textarea) { count(textarea); textarea.addEventListener('input', function () { count(textarea); }); });
  form.querySelectorAll('[data-reset-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      var target = document.getElementById(button.dataset.resetTarget);
      if (!target) return;
      target.value = button.dataset.resetValue || '';
      target.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
  var status = form.querySelector('[data-dirty-status]');
  var dirty = false;
  function markDirty() {
    if (dirty || !status) return;
    dirty = true;
    status.classList.add('is-dirty');
    status.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> تغییرات ذخیره‌نشده دارید';
  }
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (event) { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
  form.querySelectorAll('select[multiple]').forEach(function (select) {
    select.addEventListener('change', function () {
      var chosen = Array.from(select.selectedOptions);
      if (chosen.length > 3) chosen[chosen.length - 1].selected = false;
    });
  });
})();
</script>
@endsection
