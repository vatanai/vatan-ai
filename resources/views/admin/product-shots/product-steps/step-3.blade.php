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

</div>
