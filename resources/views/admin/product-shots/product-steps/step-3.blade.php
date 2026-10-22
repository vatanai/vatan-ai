@php
  $pfEnabled = (bool)old('preflight_enabled',$savedPreflight['enabled'] ?? $globalSettings->preflight_enabled);
  $pfBlockingDefault = array_key_exists('blocking_issues', $savedPreflight)
    ? (array)$savedPreflight['blocking_issues']
    : array_values(array_unique(array_merge(
        (array)($globalSettings->preflight_blocking_issues ?? []),
        \App\Services\ProductShots\ShotVisionService::BLOCKING
      )));
  $pfBlocking = (array)old('preflight_blocking_issues',$pfBlockingDefault);
  $sheetEnabled = (bool)old('product_sheet_enabled',$savedPreflight['product_sheet_enabled'] ?? $globalSettings->product_sheet_enabled);
@endphp
<div class="space-y-4">
  <section class="content-card p-5">
    <div class="flex items-start justify-between gap-4"><div><div class="ps-card-title"><i class="fa-solid fa-magnifying-glass-chart"></i> کنترل هوشمند تصاویر ورودی</div><div class="ps-card-desc">هر یک از حداکثر سه زاویه از همان محصول و سپس مجموعه‌ی کامل تصاویر قبل از ساخت بررسی می‌شود؛ خطای مسدودکننده اجازه‌ی ساخت نمی‌دهد و اعتبار کم نمی‌شود.</div></div><label class="ps-toggle"><input type="hidden" name="preflight_enabled" value="0"><input type="checkbox" name="preflight_enabled" value="1" @checked($pfEnabled)><span></span></label></div>
    <div class="ps-grid mt-4">
      <div class="ps-field"><label>مدل بینایی اختصاصی این محصول</label><input name="preflight_model" class="input-pro" dir="ltr" value="{{ old('preflight_model',$savedPreflight['model'] ?? '') }}" placeholder="{{ $globalSettings->preflight_model ?: config('product_shots.vision_model') }}"><div class="ps-hint">خالی = استفاده از تنظیم عمومی استودیو محصول.</div></div>
      <div class="ps-field"><label>حداقل ضلع تصویر</label><input type="number" name="preflight_min_side" min="500" max="3000" class="input-pro" value="{{ old('preflight_min_side',$savedPreflight['min_side'] ?? $globalSettings->preflight_min_side ?? 900) }}"><div class="ps-hint">تصاویر کوچک‌تر اخطار کیفیت می‌گیرند.</div></div>
    </div>
    <div class="mt-4"><div class="text-xs font-bold text-[var(--text-main)] mb-2">خطاهای مسدودکننده</div><div class="ps-check-grid">@foreach(\App\Services\ProductShots\ShotVisionService::ISSUES as $issueKey => $issueLabel)<label><input type="checkbox" name="preflight_blocking_issues[]" value="{{ $issueKey }}" @checked(in_array($issueKey,$pfBlocking,true))><span>{{ $issueLabel }}</span></label>@endforeach</div></div>
    <div class="mt-4 p-4 rounded-xl border border-[var(--border)] bg-[var(--input-bg)] flex items-start justify-between gap-4"><div><strong class="block text-xs text-[var(--text-main)]">ساخت خودکار پروداکت‌شیت چندزاویه‌ای</strong><small class="block text-[10px] text-[var(--text-soft)] mt-1 leading-6">تا سه نمای اصلی در یک شیت مرجع باکیفیت چیده می‌شوند و همراه تصاویر اصلی به مدل نهایی می‌روند؛ مدل اجازه ندارد قاب شیت یا چند نسخه از محصول را بازسازی کند.</small></div><label class="ps-toggle"><input type="hidden" name="product_sheet_enabled" value="0"><input type="checkbox" name="product_sheet_enabled" value="1" @checked($sheetEnabled)><span></span></label></div>
    <div class="ps-field mt-3 max-w-xs"><label>اندازه پروداکت‌شیت</label><select name="product_sheet_size" class="input-pro"><option value="1536" @selected((int)old('product_sheet_size',$savedPreflight['product_sheet_size'] ?? $globalSettings->product_sheet_size)===1536)>۱۵۳۶ پیکسل</option><option value="2048" @selected((int)old('product_sheet_size',$savedPreflight['product_sheet_size'] ?? $globalSettings->product_sheet_size)===2048)>۲۰۴۸ پیکسل — پیشنهادی</option><option value="3072" @selected((int)old('product_sheet_size',$savedPreflight['product_sheet_size'] ?? $globalSettings->product_sheet_size)===3072)>۳۰۷۲ پیکسل</option></select></div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-vial-circle-check"></i> تصاویر تست و پیش‌نمایش اسلایدها</div><div class="ps-card-desc">یک تا سه زاویه از یک محصول واحد انتخاب کنید. پیش‌نمایش واقعی هزینه‌ی سرویس هوش مصنوعی دارد، اما سفارش و کسر اعتبار کاربر ایجاد نمی‌کند.</div>
    <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3 mt-4"><div class="ps-field"><label for="sp-test-images">تصاویر تست محصول</label><label class="ps-test-upload" data-test-upload><input id="sp-test-images" type="file" accept="image/jpeg,image/png,image/webp" multiple><span class="ps-test-upload-icon"><i class="fa-solid fa-images"></i></span><span class="ps-test-upload-copy"><strong>انتخاب تصاویر تست</strong><small data-test-image-count>حداکثر ۳ تصویر از زوایای مکمل همان محصول.</small></span><span class="ps-test-upload-thumbs" data-test-thumbs></span></label></div><div class="ps-field"><label>نسبت پیش‌نمایش</label><select id="sp-test-ratio" class="input-pro">@foreach($aspectRatios as $ratio)<option value="{{ $ratio }}">{{ $ratio }}</option>@endforeach</select></div></div>
    <div id="sp-preview-status" class="ps-hint mt-2" aria-live="polite"></div>
  </section>

  <section class="content-card p-5" id="sp-input-image-card">
    <div class="ps-card-title"><i class="fa-solid fa-camera-retro"></i> عکس ورودی</div>
    <div class="ps-card-desc">همان عکس محصولی که اسلایدها با آن ساخته شده‌اند. در گالری صفحه‌ی محصول با برچسب «عکس ورودی» و مقایسه‌ی قبل/بعد به کاربر نشان داده می‌شود تا بداند چه عکسی بدهد؛ در صفحه‌ی ساخت (انتخاب اسلایدها) نمایش داده نمی‌شود.</div>
    <input type="hidden" name="remove_input_image" value="0" data-remove-input-image>
    <div class="ps-input-image mt-4">
      @include('admin.partials.image-optimizer-group', [
        'inputId' => 'input-image-file',
        'inputName' => 'input_image',
        'title' => 'عکس ورودی (فقط یک عکس)',
        'hint' => 'یک عکس ساده و واضح از خود محصول؛ نسخه‌ی بهینه‌شده‌ی WebP ذخیره می‌شود.',
        'paths' => !empty($inputImage) ? [$inputImage] : [],
        'required' => false,
        'multiple' => false,
        'uploadLabel' => 'انتخاب عکس ورودی',
      ])
    </div>
  </section>

  <section class="content-card p-5">
    <div class="flex items-start justify-between gap-3 flex-wrap"><div><div class="ps-card-title"><i class="fa-solid fa-images"></i> اسلایدهای خروجی</div><div class="ps-card-desc">اسلاید اختصاصی بسازید (عکس نمونه + پرامپت ساخت) یا از کتابخانه استفاده کنید. ترتیب را با دستگیره مشخص کنید. در صفحه‌ی ساخت هیچ اسلایدی از پیش انتخاب نیست و کاربر خودش انتخاب می‌کند.</div></div><div class="flex items-center gap-2 flex-wrap"><button type="button" class="btn-pro btn-pro-primary" data-add-custom-slide><i class="fa-solid fa-plus"></i> افزودن اسلاید</button><a href="{{ route('admin.product-shots.index',['tab'=>'library']) }}" target="_blank" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-layer-group"></i> کتابخانه اسلایدها</a></div></div>
    <div class="ps-slide-quantity mt-3" data-shot-quantity-control>
      <span class="ps-slide-quantity-label"><i class="fa-solid fa-layer-group"></i> تعداد اسلایدهای محصول</span>
      <div class="ps-slide-quantity-stepper" role="group" aria-label="تعیین تعداد اسلایدهای محصول">
        <button type="button" data-shot-quantity-step="-1" aria-label="یک اسلاید کمتر"><i class="fa-solid fa-minus"></i></button>
        <input type="number" min="1" max="30" value="1" inputmode="numeric" data-shot-quantity aria-label="تعداد اسلایدها">
        <button type="button" data-shot-quantity-step="1" aria-label="یک اسلاید بیشتر"><i class="fa-solid fa-plus"></i></button>
      </div>
      <small>عدد را تغییر دهید تا تعداد اسلایدهای همین محصول تنظیم شود.</small>
    </div>
    <div class="ps-slide-manager-bar mt-4">
      <div><i class="fa-solid fa-grip-vertical"></i><span>برای جابه‌جایی، اسلاید را از دستگیره بگیرید و بالا یا پایین ببرید.</span></div>
      <span class="ps-slide-count" data-shot-count aria-live="polite"></span>
    </div>
    <div class="ps-shot-list mt-4" id="sp-shot-list">
      @php $defaultShotId = (int) ($generalShotIds[0] ?? 0); @endphp
      @foreach($shots as $index => $shot)
        @php
          $ps = $productShots->get($shot->id);
          $isGeneral = in_array((int)$shot->id,array_map('intval',$generalShotIds ?? []),true);
          $enabled = (bool)old("shots.{$shot->id}.enabled",$ps?->enabled ?? (!$product && $isGeneral && (int) $shot->id === $defaultShotId));
          $isCustom = $shot->isCustom();
          $credits = (array)data_get($ps?->model_configuration,'quality_credits',[]);
          $overrides = (array)data_get($ps?->model_configuration,'quality_models',[]);
          $allowed = (array)old("shots.{$shot->id}.allowed_aspect_ratios",$ps?->allowedRatios() ?? [$shot->aspect_ratio_default]);
          $defaultRatio = old("shots.{$shot->id}.aspect_ratio_default",$ps?->defaultRatio() ?? $shot->aspect_ratio_default);
          $options = (array)($ps?->options_enabled ?? []);
          $sample = $ps?->sampleImageUrl() ?? $shot->sampleImageUrl();
          $samplePath = $ps?->sample_image ?: $shot->sample_image;
          $removed = (bool)old("shots.{$shot->id}.removed",$product && !$ps);
          $slideName = old("shots.{$shot->id}.name_override",$ps?->displayName() ?? $shot->name_fa);
          $inputImageSlide = (bool) data_get($ps?->options_enabled, 'input_image_slide', false);
          $showShotRow = $enabled || $removed || $loop->first;
        @endphp
        <article class="ps-shot-editor {{ $isCustom ? 'is-custom' : '' }} {{ $removed ? 'is-removed' : ($enabled ? '' : 'is-off') }} {{ $inputImageSlide ? 'is-input-image-slide' : '' }}" data-shot-row data-shot-id="{{ $shot->id }}" data-shot-name="{{ $slideName }}" data-shot-original-name="{{ $shot->name_fa }}" @if(!$showShotRow) hidden @endif>
          <input type="hidden" name="shots[{{ $shot->id }}][removed]" value="{{ $removed ? 1 : 0 }}" data-shot-removed>
          <div class="ps-shot-removed-state">
            <span class="ps-shot-removed-icon"><i class="fa-solid fa-box-archive"></i></span>
            <span><strong>{{ $slideName }}</strong><small>این اسلاید در این محصول استفاده نمی‌شود.</small></span>
            <button type="button" class="btn-pro btn-pro-ghost" data-shot-restore><i class="fa-solid fa-rotate-left"></i> افزودن دوباره</button>
          </div>
          <div class="ps-shot-card-main">
          <div class="ps-shot-summary">
            <div class="ps-slide-sample" title="تصویر مرجع فعلی این اسلاید">
              <span class="ps-thumb" data-shot-thumb>@if($sample)<img src="{{ $sample }}" alt="نمونه {{ $shot->name_fa }}">@else<i class="fa-solid fa-plus"></i>@endif</span>
              <small><i class="fa-solid fa-camera"></i> تصویر نمونه</small>
            </div>
            <div class="min-w-0 flex-1">
              <div class="ps-slide-title-row">
                <button type="button" class="ps-slide-drag" data-shot-drag-handle aria-label="جابه‌جایی اسلاید" title="برای جابه‌جایی بکشید"><i class="fa-solid fa-grip-vertical"></i></button>
                <span class="ps-slide-order">اسلاید {{ $index + 1 }}</span>@if($isCustom)<span class="ps-slide-kind"><i class="fa-solid fa-pen-nib"></i> اختصاصی</span>@endif
                <label class="ps-slide-name-field"><input type="text" name="shots[{{ $shot->id }}][name_override]" value="{{ $slideName }}" maxlength="120" class="input-pro" data-shot-name-input aria-label="نام نمایشی اسلاید" placeholder="نام نمایشی اسلاید"></label>
              </div>
              @if(!$isCustom)<p class="ps-shot-desc">{{ $shot->description_fa }}</p>@endif<div class="ps-tags">@foreach(array_slice($shot->tokenLabels(),0,4) as $tag)<span class="ps-tag">{{ $tag }}</span>@endforeach</div><div class="ps-hint" data-shot-result></div>
            </div>
            <div class="ps-shot-head-actions">
              <div class="ps-slide-order-actions" aria-label="کنترل ترتیب اسلاید"><button type="button" data-shot-move="up" title="انتقال به بالا" aria-label="انتقال اسلاید به بالا"><i class="fa-solid fa-arrow-up"></i></button><button type="button" data-shot-move="down" title="انتقال به پایین" aria-label="انتقال اسلاید به پایین"><i class="fa-solid fa-arrow-down"></i></button><button type="button" class="is-danger" data-shot-remove title="حذف از این محصول" aria-label="حذف اسلاید از این محصول"><i class="fa-solid fa-trash-can"></i></button></div>
              <label class="ps-check" data-shot-enabled-label><input type="hidden" name="shots[{{ $shot->id }}][enabled]" value="0"><input type="checkbox" name="shots[{{ $shot->id }}][enabled]" value="1" data-shot-enabled @checked($enabled)> فعال برای کاربر</label>
            </div>
          </div>
          <div class="ps-slide-image-optimizer">
            @include('admin.partials.image-optimizer-group', [
              'inputId' => 'shot-sample-' . $shot->id,
              'inputName' => "shots[{$shot->id}][sample]",
              'title' => 'مدیریت تصویر مرجع اسلاید',
              'hint' => 'یک تصویر از همین محصول انتخاب کنید؛ نسخه‌ی بهینه‌شده‌ی WebP با حفظ نسبت تصویر جایگزین فایل ورودی می‌شود.',
              'paths' => $samplePath ? [$samplePath] : [],
              'required' => false,
              'multiple' => false,
              'variant' => 'product-shot',
              'uploadLabel' => 'انتخاب تصویر اسلاید',
            ])
          </div>
          <div class="ps-slide-prompt"><div class="ps-slide-prompt-panel"><div class="ps-field"><label>{{ $isCustom ? 'پرامپت ساخت این اسلاید' : 'پرامپت مخصوص این اسلاید' }}<span class="ps-char-count" data-char-count></span></label><textarea name="shots[{{ $shot->id }}][prompt_override]" class="input-pro" dir="ltr" rows="{{ $isCustom ? 6 : 3 }}" maxlength="30000" data-shot-prompt placeholder="{{ $isCustom ? 'پرامپت کامل ساخت این اسلاید' : 'خالی = ساخت خودکار از تنظیمات کتابخانه' }}">{{ old("shots.{$shot->id}.prompt_override",$ps?->prompt_override ?? ($isCustom ? $shot->prompt_template : null)) }}</textarea></div><button type="button" class="btn-pro btn-pro-ghost" data-shot-toggle><i class="fa-solid fa-sliders"></i> تنظیمات پیشرفته</button></div></div>
          <div class="ps-shot-detail" data-shot-detail hidden>
            <input type="hidden" name="shots[{{ $shot->id }}][sort]" value="{{ $index }}"><input type="hidden" name="shots[{{ $shot->id }}][sample_path]" value="" data-shot-sample>
            <div class="ps-option-switches"><span>قابلیت‌های فعال:</span>@foreach(['prompt'=>'پرامپت','model'=>'مدل اختصاصی','ratio'=>'نسبت تصویر','credits'=>'اعتبار','preview'=>'پیش‌نمایش'] as $optionKey=>$optionLabel)<label><input type="hidden" name="shots[{{ $shot->id }}][option_{{ $optionKey }}]" value="0"><input type="checkbox" name="shots[{{ $shot->id }}][option_{{ $optionKey }}]" value="1" @checked((bool)old("shots.{$shot->id}.option_{$optionKey}",$options[$optionKey] ?? true))> {{ $optionLabel }}</label>@endforeach</div>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
              @foreach($qualityLevels as $qualityKey=>$qualityLabel)
                @php $override=(array)($overrides[$qualityKey] ?? []); $fallbackIds=array_map('intval',(array)($override['fallback_ids'] ?? [])); @endphp
                <div class="ps-quality-card"><div class="ps-quality-card-title"><span>{{ $qualityLabel }}</span></div><div class="ps-field"><label>اعتبار هر خروجی</label><input type="number" min="0" max="1000" name="shots[{{ $shot->id }}][quality_credits][{{ $qualityKey }}]" class="input-pro" value="{{ old("shots.{$shot->id}.quality_credits.{$qualityKey}",$credits[$qualityKey] ?? $ps?->credits() ?? $shot->default_credits) }}"></div><div class="ps-field mt-2"><label>مدل اختصاصی اختیاری</label><select name="shots[{{ $shot->id }}][model_overrides][{{ $qualityKey }}][primary_id]" class="input-pro"><option value="">مدل عمومی سطح</option>@foreach($models as $model)<option value="{{ $model->id }}" @selected((int)old("shots.{$shot->id}.model_overrides.{$qualityKey}.primary_id",$override['primary_id'] ?? 0)===$model->id)>{{ $model->name }} · {{ $model->provider }}</option>@endforeach</select></div><div class="ps-field mt-2"><label>جایگزین‌های اختصاصی</label><select name="shots[{{ $shot->id }}][model_overrides][{{ $qualityKey }}][fallback_ids][]" class="input-pro" multiple size="3" style="height:90px">@foreach($models as $model)<option value="{{ $model->id }}" @selected(in_array($model->id,$fallbackIds,true))>{{ $model->name }}</option>@endforeach</select></div></div>
              @endforeach
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3"><div><div class="text-xs font-bold text-[var(--text-main)] mb-2">نسبت‌های مجاز</div><div class="flex flex-wrap gap-2">@foreach($aspectRatios as $ratio)<label class="ps-check"><input type="checkbox" name="shots[{{ $shot->id }}][allowed_aspect_ratios][]" value="{{ $ratio }}" @checked(in_array($ratio,$allowed,true))> {{ $ratio }}</label>@endforeach</div><label class="ps-check mt-3"><input type="hidden" name="shots[{{ $shot->id }}][aspect_ratio_user_selectable]" value="0"><input type="checkbox" name="shots[{{ $shot->id }}][aspect_ratio_user_selectable]" value="1" @checked((bool)old("shots.{$shot->id}.aspect_ratio_user_selectable",$ps?->aspect_ratio_user_selectable ?? true))> کاربر اجازه تغییر نسبت را دارد</label></div><div class="ps-field"><label>نسبت پیش‌فرض</label><select name="shots[{{ $shot->id }}][aspect_ratio_default]" class="input-pro">@foreach($aspectRatios as $ratio)<option value="{{ $ratio }}" @selected($defaultRatio===$ratio)>{{ $ratio }}</option>@endforeach</select></div></div>
            <div class="mt-4 flex justify-end"><button type="button" class="btn-pro btn-pro-primary" data-shot-preview="{{ $shot->id }}"><i class="fa-solid fa-wand-magic-sparkles"></i> ساخت پیش‌نمایش واقعی</button></div>
          </div>
          </div>
        </article>
      @endforeach
      <div class="ps-shot-add-footer" data-shot-add-footer>
        <button type="button" class="btn-pro btn-pro-ghost" data-add-custom-slide><i class="fa-solid fa-plus"></i> افزودن اسلاید</button>
      </div>
    </div>
    <div class="ps-hint mt-3" data-shot-order-status aria-live="polite"></div>
    <template id="sp-custom-slide-template">
      @include('admin.product-shots.product-steps.custom-slide-row')
    </template>
  </section>
</div>
