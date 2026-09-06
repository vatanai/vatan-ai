@php
  $features = old('features', $plan->features ?: [['title'=>'','value'=>'','included'=>'yes','highlighted'=>false]]);
  $loyal = $plan->audience_overrides['loyal'] ?? [];
  $cardStyles = config('plan_card_styles');
  $selectedStyle = old('card_style', $plan->card_style ?: 'classic');
  $previewPlan = clone $plan;
  $previewPlan->name = old('name', $plan->name ?: 'پلن نمونه');
  $previewPlan->short_description = old('short_description', $plan->short_description ?: 'مناسب برای کاربران وطن استودیو');
  $previewPlan->price = (int) old('price', $plan->price ?? 599000);
  $previewPlan->tokens = (int) old('tokens', $plan->tokens ?? 25);
  $previewPlan->billing_type = old('billing_type', $plan->billing_type ?: 'monthly');
  $previewPlan->icon = old('icon', $plan->icon ?: 'fa-solid fa-gem');
  $previewPlan->badge_text = old('badge_text', $plan->badge_text ?: 'پیشنهاد ما');
  $previewPlan->features = collect($features)->filter(fn($f) => !empty($f['title']))->values()->all() ?: [
    ['title'=>'کیفیت اصلی تصاویر','value'=>'','included'=>'yes','highlighted'=>true],
    ['title'=>'بدون واترمارک','value'=>'','included'=>'yes','highlighted'=>false],
    ['title'=>'اولویت پردازش','value'=>'','included'=>'yes','highlighted'=>false],
    ['title'=>'پشتیبانی اولویت‌دار','value'=>'','included'=>'no','highlighted'=>false],
  ];
  $previewOffer = ['segment'=>'regular','price'=>(int)$previewPlan->price,'tokens'=>(int)$previewPlan->tokens,'bonus_tokens'=>0,'visible'=>true,'purchasable'=>true];
@endphp
@if(session('success'))<div class="pb-notice pb-notice-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="pb-notice pb-notice-danger"><b>لطفاً موارد زیر را اصلاح کنید:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<section class="pb-toolbar">
  <div><h1 class="pb-title">{{ $editing ? 'ویرایش '.$plan->name : 'ساخت پلن جدید' }}</h1><p class="pb-subtitle">{{ $editing ? 'نسخه '.$plan->version.' — '.$plan->plan_code : 'اطلاعات، امکانات و قیمت هر گروه را تعریف کنید' }}</p></div>
  <div class="pb-actions">
    <a href="{{ route('admin.plans.index') }}" class="pb-btn"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
    @if($editing)<a href="{{ route('pricing.index') }}" target="_blank" class="pb-btn"><i class="fa-solid fa-eye"></i> پیش‌نمایش</a>@endif
  </div>
</section>

<form action="{{ $editing ? route('admin.plans.update',$plan) : route('admin.plans.store') }}" method="POST" enctype="multipart/form-data">
  @csrf @if($editing) @method('PUT') @endif
  <div class="pb-form-layout">
    <div class="pb-form-main">
      <section class="pb-form-card">
        <h3>اطلاعات پایه</h3>
        <div class="pb-fields">
          <label><span class="pb-label">نام پلن *</span><input class="pb-input" name="name" id="plan-name" value="{{ old('name',$plan->name) }}" required placeholder="مثلاً پلن حرفه‌ای"></label>
          <label><span class="pb-label">اسلاگ انگلیسی *</span><input class="pb-input" dir="ltr" name="slug" value="{{ old('slug',$plan->slug) }}" required placeholder="pro"></label>
          <label class="pb-field-full"><span class="pb-label">مناسب برای</span><input class="pb-input" name="short_description" id="plan-short" value="{{ old('short_description',$plan->short_description) }}" placeholder="فروشندگان اینستاگرام و آنلاین‌شاپ‌ها"></label>
          <label class="pb-field-full"><span class="pb-label">توضیحات کامل</span><textarea class="pb-textarea" name="description" rows="3">{{ old('description',$plan->description) }}</textarea></label>
          <label><span class="pb-label">آیکون Font Awesome</span><input class="pb-input" name="icon" value="{{ old('icon',$plan->icon ?: 'fa-solid fa-gem') }}"></label>
          <label><span class="pb-label">نشان روی کارت</span><input class="pb-input" name="badge_text" value="{{ old('badge_text',$plan->badge_text) }}" placeholder="پرفروش"></label>
          <label class="pb-field-full"><span class="pb-label">تصویر اختیاری</span><input class="pb-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"><span class="pb-help">اگر تصویری انتخاب نشود، آیکون پلن نمایش داده می‌شود.</span></label>
        </div>
      </section>

      <section class="pb-form-card">
        <h3>قیمت و اعتبار پایه</h3>
        <div class="pb-fields">
          <label><span class="pb-label">نوع فروش</span><select class="pb-select" name="billing_type"><option value="free" @selected(old('billing_type',$plan->billing_type)==='free')>رایگان</option><option value="monthly" @selected(old('billing_type',$plan->billing_type ?: 'monthly')==='monthly')>ماهانه</option><option value="yearly" @selected(old('billing_type',$plan->billing_type)==='yearly')>سالانه</option><option value="one_time" @selected(old('billing_type',$plan->billing_type)==='one_time')>خرید یک‌باره</option><option value="custom" @selected(old('billing_type',$plan->billing_type)==='custom')>قراردادی / تماس با فروش</option></select></label>
          <label><span class="pb-label">قیمت *</span><input class="pb-input money-input" name="price" id="plan-price" value="{{ old('price',$plan->price) }}" inputmode="numeric" required></label>
          <label><span class="pb-label">پیشوند قیمت</span><input class="pb-input" name="price_prefix" value="{{ old('price_prefix',$plan->price_prefix) }}" placeholder="مثلاً از"></label>
          <label><span class="pb-label">قیمت قبل از تخفیف</span><input class="pb-input money-input" name="compare_at_price" value="{{ old('compare_at_price',$plan->compare_at_price) }}" inputmode="numeric"></label>
          <label><span class="pb-label">تعداد توکن *</span><input class="pb-input" type="number" min="0" name="tokens" id="plan-tokens" value="{{ old('tokens',$plan->tokens ?? 0) }}" required></label>
          <label><span class="pb-label">متن سفارشی توکن</span><input class="pb-input" name="token_label" value="{{ old('token_label',$plan->token_label) }}" placeholder="نامحدود بر اساس استفاده منصفانه"></label>
          <label class="pb-check"><input type="checkbox" name="is_unlimited" value="1" @checked(old('is_unlimited',$plan->is_unlimited))> اعتبار نامحدود است</label>
          <label><span class="pb-label">سقف خرید هر کاربر</span><input class="pb-input" type="number" min="1" name="purchase_limit" value="{{ old('purchase_limit',$plan->purchase_limit) }}" placeholder="بدون محدودیت"></label>
        </div>
      </section>

      <section class="pb-form-card">
        <div class="pb-card-head" style="padding:0 0 12px;margin-bottom:14px"><h3 style="border:0;padding:0;margin:0">امکانات پلن</h3><button type="button" class="pb-btn pb-btn-sm" onclick="addPlanFeature()"><i class="fa-solid fa-plus"></i> قابلیت جدید</button></div>
        <div id="feature-list">
          @foreach($features as $index=>$feature)
            <div class="pb-feature">
              <input class="pb-input" name="features[{{ $index }}][title]" value="{{ $feature['title']??'' }}" required placeholder="عنوان قابلیت">
              <input class="pb-input" name="features[{{ $index }}][value]" value="{{ $feature['value']??'' }}" placeholder="مقدار اختیاری">
              <select class="pb-select" name="features[{{ $index }}][included]"><option value="yes" @selected(($feature['included']??'yes')==='yes')>دارد</option><option value="limited" @selected(($feature['included']??'')==='limited')>محدود</option><option value="no" @selected(($feature['included']??'')==='no')>ندارد</option></select>
              <label class="pb-check"><input type="checkbox" name="features[{{ $index }}][highlighted]" value="1" @checked($feature['highlighted']??false)> مهم</label>
              <button type="button" class="pb-btn pb-btn-sm pb-btn-danger" onclick="removePlanFeature(this)"><i class="fa-solid fa-trash"></i></button>
            </div>
          @endforeach
        </div>
      </section>

      <section class="pb-form-card">
        <h3>قیمت و اعتبار مشتری ثابت</h3>
        <div class="pb-fields">
          <label><span class="pb-label">قیمت اختصاصی</span><input class="pb-input money-input" name="loyal_price" value="{{ old('loyal_price',$loyal['price']??'') }}" placeholder="خالی = قیمت پایه"></label>
          <label><span class="pb-label">تعداد توکن اختصاصی</span><input class="pb-input" type="number" min="0" name="loyal_tokens" value="{{ old('loyal_tokens',$loyal['tokens']??'') }}" placeholder="خالی = مقدار پایه"></label>
          <label><span class="pb-label">توکن هدیه اضافه</span><input class="pb-input" type="number" min="0" name="loyal_bonus_tokens" value="{{ old('loyal_bonus_tokens',$loyal['bonus_tokens']??0) }}"></label>
          <div><span class="pb-label">دسترسی این گروه</span><label class="pb-check"><input type="checkbox" name="loyal_visible" value="1" @checked(old('loyal_visible',$loyal['visible']??true))> قابل مشاهده</label><label class="pb-check"><input type="checkbox" name="loyal_purchasable" value="1" @checked(old('loyal_purchasable',$loyal['purchasable']??true))> قابل خرید</label></div>
        </div>
      </section>

      <section class="pb-form-card">
        <h3>سبک نمایش کارت در سایت</h3>
        <p class="pb-help" style="margin-bottom:14px">یکی از ۱۵ مدل زیر را انتخاب کنید. مدل انتخاب‌شده برای همین پلن ثابت می‌ماند و در سایت با همین ظاهر نمایش داده می‌شود.</p>
        <div class="pb-style-gallery">
          @foreach($cardStyles as $styleKey=>$styleMeta)
            @php $stylePlan=clone $previewPlan; $stylePlan->card_style=$styleKey; @endphp
            <label class="pb-style-option">
              <input type="radio" name="card_style" value="{{ $styleKey }}" @checked($selectedStyle===$styleKey) onchange="selectPlanCardStyle('{{ $styleKey }}')">
              <div class="pb-style-meta"><b>{{ $styleMeta['name'] }}</b><span>{{ $styleMeta['description'] }}</span></div>
              <div class="pb-style-canvas">
                <div class="vpc-gallery-card">@include('site.partials.plan-card',['plan'=>$stylePlan,'offer'=>$previewOffer,'preview'=>true,'planDisplay'=>['show_images'=>false]])</div>
              </div>
            </label>
          @endforeach
        </div>
      </section>

      <section class="pb-form-card">
        <h3>پیش‌نمایش در سایت</h3>
        <p class="pb-help" style="margin-bottom:14px">این دو قاب از همان کامپوننتی استفاده می‌کنند که در صفحه نهایی سایت رندر می‌شود.</p>
        <div class="pb-site-preview">
          <div class="pb-device">
            <div class="pb-device-head"><b>نسخه دسکتاپ</b><span>چهار کارت در یک ردیف</span></div>
            <div class="pb-device-stage pb-preview-desktop" id="desktop-plan-preview">
              @php $previewPlan->card_style=$selectedStyle; @endphp
              @include('site.partials.plan-card',['plan'=>$previewPlan,'offer'=>$previewOffer,'preview'=>true,'planDisplay'=>['show_images'=>false]])
            </div>
          </div>
          <div class="pb-device pb-device-mobile">
            <div class="pb-device-head"><b>نسخه موبایل</b><span>عرض ۳۹۰ پیکسل</span></div>
            <div class="pb-device-stage pb-preview-mobile" id="mobile-plan-preview">
              @include('site.partials.plan-card',['plan'=>$previewPlan,'offer'=>$previewOffer,'preview'=>true,'planDisplay'=>['show_images'=>false]])
            </div>
          </div>
        </div>
        <p class="pb-preview-note">پس از تغییر اطلاعات یا امکانات، ذخیره کنید تا پیش‌نمایش نهایی با همه داده‌های تازه بازسازی شود.</p>
      </section>
    </div>

    <aside class="pb-form-side">
      <section class="pb-form-card">
        <h3>انتشار</h3>
        <div class="pb-settings">
          <label><span class="pb-label">وضعیت</span><select class="pb-select" name="status"><option value="draft" @selected(old('status',$plan->status ?: 'draft')==='draft')>پیش‌نویس</option><option value="active" @selected(old('status',$plan->status)==='active')>فعال</option><option value="inactive" @selected(old('status',$plan->status)==='inactive')>غیرفعال</option></select></label>
          <label><span class="pb-label">ترتیب نمایش</span><input class="pb-input" type="number" min="0" name="sort_order" value="{{ old('sort_order',$plan->sort_order ?? 0) }}" required></label>
          <label class="pb-check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$plan->is_featured))> پلن پیشنهادی و برجسته</label>
          <label><span class="pb-label">شروع فروش</span><input class="pb-input" type="datetime-local" name="starts_at" value="{{ old('starts_at',$plan->starts_at?->format('Y-m-d\\TH:i')) }}"></label>
          <label><span class="pb-label">پایان فروش</span><input class="pb-input" type="datetime-local" name="ends_at" value="{{ old('ends_at',$plan->ends_at?->format('Y-m-d\\TH:i')) }}"></label>
          <button class="pb-btn pb-btn-primary pb-btn-block"><i class="fa-solid fa-check"></i> {{ $editing ? 'ذخیره تغییرات' : 'ساخت پلن' }}</button>
        </div>
      </section>
      <section class="pb-form-card">
        <h3>پیش‌نمایش زنده</h3>
        <article class="pb-preview-card" id="plan-preview">
          <div class="pb-preview-icon"><i class="{{ old('icon',$plan->icon ?: 'fa-solid fa-gem') }}"></i></div>
          <b id="preview-name">{{ old('name',$plan->name ?: 'نام پلن') }}</b>
          <div class="pb-preview-price" id="preview-price">—</div>
          <div class="pb-preview-meta"><span id="preview-tokens">۰</span> توکن</div>
          <p class="pb-preview-meta" id="preview-short">{{ old('short_description',$plan->short_description ?: 'مناسب برای گروه هدف شما') }}</p>
        </article>
      </section>
      @if($editing)
      <section class="pb-form-card">
        <h3>عملیات حساس</h3>
        <p class="pb-help">پلن دارای سابقه خرید حذف نمی‌شود و باید آرشیو شود.</p>
        <button type="submit" class="pb-btn pb-btn-danger pb-btn-block" form="delete-plan" onclick="return confirm('از حذف کامل این پلن مطمئن هستید؟')"><i class="fa-solid fa-trash"></i> حذف کامل</button>
      </section>
      @endif
    </aside>
  </div>
</form>
@if($editing)<form id="delete-plan" action="{{ route('admin.plans.destroy',$plan) }}" method="POST">@csrf @method('DELETE')</form>@endif
