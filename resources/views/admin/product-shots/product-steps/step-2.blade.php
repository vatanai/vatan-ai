<div class="space-y-4">
  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-layer-group"></i> معماری سه‌سطحی کیفیت</div><div class="ps-card-desc">برای هر سطح یک مدل اصلی و حداکثر سه مدل جایگزین تعیین کنید. مدل اختصاصی هر شات در گام سوم می‌تواند این تنظیم را بازنویسی کند.</div>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-4">
      @foreach($qualityLevels as $qualityKey => $qualityLabel)
        @php
          $saved = (array)($savedQualityModels[$qualityKey] ?? []);
          $defaultPrimary = old("quality_models.$qualityKey.primary_id", $saved['primary_id'] ?? ($models->first()?->id));
          $defaultFallbacks = array_map('intval',(array)old("quality_models.$qualityKey.fallback_ids",$saved['fallback_ids'] ?? []));
        @endphp
        <div class="ps-quality-card" data-quality-card="{{ $qualityKey }}">
          <div class="ps-quality-card-title"><span>{{ $qualityLabel }}</span><small>{{ $qualityKey === 'standard' ? 'پیش‌فرض کاربران' : 'انتخاب اختیاری' }}</small></div>
          <div class="ps-field"><label>مدل اصلی <span class="text-[var(--danger)]">*</span></label><select name="quality_models[{{ $qualityKey }}][primary_id]" class="input-pro" required data-step-required>@foreach($models as $model)<option value="{{ $model->id }}" @selected((int)$defaultPrimary===$model->id)>{{ $model->name }} · {{ $model->provider }}{{ $model->cost_per_generation_usd ? ' · $'.rtrim(rtrim(number_format((float)$model->cost_per_generation_usd,4),'0'),'.') : '' }}</option>@endforeach</select></div>
          <div class="ps-field mt-3"><label>مدل‌های جایگزین</label><select name="quality_models[{{ $qualityKey }}][fallback_ids][]" class="input-pro" multiple size="4" style="height:112px">@foreach($models as $model)<option value="{{ $model->id }}" @selected(in_array($model->id,$defaultFallbacks,true))>{{ $model->name }} · {{ $model->provider }}</option>@endforeach</select></div>
        </div>
      @endforeach
    </div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-palette"></i> زمینه و سبک عمومی پک</div><div class="ps-card-desc">این اطلاعات به پرامپت همه‌ی شات‌ها اضافه می‌شوند؛ پرامپت اختصاصی هر شات در گام سوم قرار دارد.</div>
    <div class="ps-grid mt-4">
      <div class="ps-field"><label>توضیح فیزیکی محصول</label><input name="product_description" class="input-pro" dir="ltr" value="{{ old('product_description',$settings['product_description'] ?? '') }}" placeholder="amber glass serum bottle with gold cap"></div>
      <div class="ps-field"><label>پالت رنگ برند</label><input name="brand_palette" class="input-pro" dir="ltr" value="{{ old('brand_palette',$settings['brand_palette'] ?? '') }}" placeholder="peach, soft gold and cream"></div>
      <div class="ps-field"><label>حس کلی برند</label><input name="brand_style" class="input-pro" dir="ltr" value="{{ old('brand_style',$settings['brand_style'] ?? '') }}" placeholder="minimal luxury skincare"></div>
    </div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-pen-ruler"></i> پرامپت عکس</div>
    <div class="ps-card-desc">دستور اختصاصی بررسی عکس ورودی این محصول؛ این متن به قرارداد استاندارد کنترل کیفیت افزوده می‌شود و پرامپت ساخت اسلایدها را تغییر نمی‌دهد.</div>
    <div class="ps-field">
      <textarea name="preflight_prompt" class="input-pro" dir="ltr" rows="5" maxlength="4000" placeholder="For example: reject images where the shoe sole or main logo is not clearly visible...">{{ old('preflight_prompt',$savedPreflight['prompt'] ?? '') }}</textarea>
      <div class="ps-hint">برای تعریف حساسیت‌های ویژه‌ی عکس ورودی همین محصول استفاده کنید؛ پرامپت اختصاصی هر اسلاید در گام سوم قرار دارد.</div>
    </div>
  </section>

  <section class="content-card p-5 ps-brand-identity" data-brand-identity>
    <div class="flex items-start justify-between gap-4"><div><div class="ps-card-title"><i class="fa-solid fa-fingerprint"></i> حفظ هویت برند</div><div class="ps-card-desc">وقتی روشن باشد، این دستور در انتهای پرامپت تک‌تک شات‌ها قرار می‌گیرد تا نور، رنگ و حس بصری پک یکدست بماند.</div></div><label class="ps-toggle"><input type="hidden" name="brand_identity_enabled" value="0"><input type="checkbox" name="brand_identity_enabled" value="1" data-brand-identity-toggle @checked($brandIdentityEnabled)><span></span></label></div>
    <div class="ps-field mt-4" data-brand-identity-prompt-wrap @if(!$brandIdentityEnabled) hidden @endif><label>پرامپت ثابت حفظ هویت برند</label><textarea name="brand_identity_prompt" class="input-pro" dir="ltr" rows="5" maxlength="2000">{{ $brandIdentityPrompt }}</textarea></div>
  </section>

  <section class="content-card p-5"><div class="ps-card-title"><i class="fa-solid fa-shield-halved"></i> وفاداری به محصول</div><div class="ps-card-desc">این قانون همیشه فعال است و قابل خاموش‌کردن نیست: شکل، نسبت‌ها، رنگ، متریال، بسته‌بندی، لوگو و نوشته‌های واقعی محصول باید حفظ شوند؛ فقط صحنه و نور تغییر می‌کند.</div><div class="mt-3 p-3 rounded-xl border border-[var(--success)] text-xs text-[var(--text-main)] bg-[var(--input-bg)]">{{ \App\Services\ProductShots\ShotPromptBuilder::productFidelityBlockFa() }}</div></section>
</div>
