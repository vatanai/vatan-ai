{{-- عکس ورودی محصول (از گام ۳ به گام ۱ منتقل شد؛ ساختار و نام فیلدها بدون تغییر) --}}
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
