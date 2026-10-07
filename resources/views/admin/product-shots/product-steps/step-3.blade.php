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
    <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3 mt-4"><div class="ps-field"><label>تصاویر تست محصول</label><input id="sp-test-images" type="file" accept="image/jpeg,image/png,image/webp" multiple class="input-pro"><div class="ps-hint" data-test-image-count>حداکثر ۳ تصویر از زوایای مکمل همان محصول.</div></div><div class="ps-field"><label>نسبت پیش‌نمایش</label><select id="sp-test-ratio" class="input-pro">@foreach($aspectRatios as $ratio)<option value="{{ $ratio }}">{{ $ratio }}</option>@endforeach</select></div></div>
    <div id="sp-preview-status" class="ps-hint mt-2" aria-live="polite"></div>
  </section>

  <section class="content-card p-5">
    <div class="flex items-start justify-between gap-3 flex-wrap"><div><div class="ps-card-title"><i class="fa-solid fa-images"></i> اسلایدهای خروجی</div><div class="ps-card-desc">برای هر اسلاید، تصویر نمونه و پرامپت مخصوص همان خروجی را وارد کنید. کاربر اسلایدهای دلخواه را انتخاب می‌کند و ساخت به همین ترتیب انجام می‌شود.</div></div><a href="{{ route('admin.product-shots.index',['tab'=>'library']) }}" target="_blank" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-layer-group"></i> کتابخانه اسلایدها</a></div>
    <div class="ps-shot-list mt-4" id="sp-shot-list">
      @foreach($shots as $index => $shot)
        @php
          $ps = $productShots->get($shot->id);
          $isGeneral = in_array((int)$shot->id,array_map('intval',$generalShotIds ?? []),true);
          $enabled = (bool)old("shots.{$shot->id}.enabled",$ps?->enabled ?? (!$product && $isGeneral));
          $isDefault = (bool)old("shots.{$shot->id}.is_default",$ps?->is_default ?? (!$product && $isGeneral && $index < 4));
          $credits = (array)data_get($ps?->model_configuration,'quality_credits',[]);
          $overrides = (array)data_get($ps?->model_configuration,'quality_models',[]);
          $allowed = (array)old("shots.{$shot->id}.allowed_aspect_ratios",$ps?->allowedRatios() ?? [$shot->aspect_ratio_default]);
          $defaultRatio = old("shots.{$shot->id}.aspect_ratio_default",$ps?->defaultRatio() ?? $shot->aspect_ratio_default);
          $options = (array)($ps?->options_enabled ?? []);
          $sample = $ps?->sampleImageUrl() ?? $shot->sampleImageUrl();
        @endphp
        <article class="ps-shot-editor {{ $enabled ? '' : 'is-off' }}" data-shot-row data-shot-id="{{ $shot->id }}" data-shot-name="{{ $shot->name_fa }}">
          <div class="ps-shot-summary">
            <label class="ps-slide-sample" title="انتخاب تصویر نمونه برای این اسلاید">
              <input type="file" name="shots[{{ $shot->id }}][sample]" accept="image/jpeg,image/png,image/webp" data-shot-sample-file>
              <span class="ps-thumb" data-shot-thumb>@if($sample)<img src="{{ $sample }}" alt="نمونه {{ $shot->name_fa }}">@else<i class="fa-solid fa-plus"></i>@endif</span>
              <small><i class="fa-solid fa-camera"></i> تصویر نمونه</small>
            </label>
            <div class="min-w-0 flex-1"><span class="ps-slide-order">اسلاید {{ $index + 1 }}</span><strong class="ps-shot-name">{{ $shot->name_fa }}</strong><p class="ps-shot-desc">{{ $shot->description_fa }}</p><div class="ps-tags">@foreach(array_slice($shot->tokenLabels(),0,4) as $tag)<span class="ps-tag">{{ $tag }}</span>@endforeach</div><div class="ps-hint" data-shot-result></div></div>
            <div class="ps-shot-head-actions"><label class="ps-check"><input type="hidden" name="shots[{{ $shot->id }}][enabled]" value="0"><input type="checkbox" name="shots[{{ $shot->id }}][enabled]" value="1" data-shot-enabled @checked($enabled)> فعال برای کاربر</label><label class="ps-check"><input type="hidden" name="shots[{{ $shot->id }}][is_default]" value="0"><input type="checkbox" name="shots[{{ $shot->id }}][is_default]" value="1" data-shot-default @checked($isDefault)> انتخاب پیش‌فرض</label></div>
          </div>
          <div class="ps-slide-prompt"><div class="ps-field"><label>پرامپت مخصوص این اسلاید</label><textarea name="shots[{{ $shot->id }}][prompt_override]" class="input-pro" dir="ltr" rows="3" placeholder="خالی = ساخت خودکار از تنظیمات کتابخانه">{{ old("shots.{$shot->id}.prompt_override",$ps?->prompt_override) }}</textarea></div><button type="button" class="btn-pro btn-pro-ghost" data-shot-toggle><i class="fa-solid fa-sliders"></i> تنظیمات پیشرفته</button></div>
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
        </article>
      @endforeach
    </div>
  </section>
</div>
