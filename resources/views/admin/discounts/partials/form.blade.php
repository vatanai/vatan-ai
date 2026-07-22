<section class="order-panel" style="position:sticky;top:12px">
  <div class="order-panel-head"><div class="order-panel-title" id="discount-form-title">ساخت تخفیف جدید</div><button class="order-btn" type="button" onclick="discountNew()"><i class="fa-solid fa-eraser"></i> پاک‌کردن</button></div>
  <form class="order-form" id="discount-form" method="POST" action="{{ route('admin.discounts.store') }}">
    @csrf <input type="hidden" name="_method" id="discount-method" value="POST">
    <div class="order-form-row">
      <div class="order-field"><label>عنوان داخلی *</label><input class="order-input" name="name" value="{{ old('name') }}" placeholder="کمپین نوروز" required></div>
      <div class="order-field"><label>کد تخفیف *</label><input class="order-input" name="code" value="{{ old('code') }}" dir="ltr" placeholder="NOWRUZ" required></div>
    </div>
    <div class="order-form-row">
      <div class="order-field"><label>نوع تخفیف *</label><select class="order-select" name="type" required><option value="percent">درصدی</option><option value="fixed">اعتبار ثابت</option><option value="free">رایگان</option></select></div>
      <div class="order-field"><label>مقدار *</label><input class="order-input" type="number" name="value" value="{{ old('value',10) }}" min="0" required></div>
    </div>
    <div class="order-form-row">
      <div class="order-field"><label>سقف تخفیف (اعتبار)</label><input class="order-input" type="number" name="max_discount_credits" value="{{ old('max_discount_credits') }}" min="1" placeholder="بدون سقف"></div>
      <div class="order-field"><label>حداقل سفارش (اعتبار)</label><input class="order-input" type="number" name="min_order_credits" value="{{ old('min_order_credits',0) }}" min="0"></div>
    </div>
    <div class="order-form-row">
      <div class="order-field"><label>سقف استفاده کل</label><input class="order-input" type="number" name="usage_limit" value="{{ old('usage_limit') }}" min="1" placeholder="نامحدود"></div>
      <div class="order-field"><label>سقف هر کاربر *</label><input class="order-input" type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user',1) }}" min="1" required></div>
    </div>
    <div class="order-field"><label>دامنه اعمال *</label><select class="order-select" name="scope" id="discount-scope" onchange="discountScope()"><option value="all">همه محصولات</option><option value="products">محصولات منتخب</option><option value="categories">دسته‌بندی‌های منتخب</option></select></div>
    <div class="order-field" id="discount-products" style="margin-top:10px"><label>محصولات (چند انتخابی)</label><select class="order-select" name="product_ids[]" multiple style="height:110px">@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name_fa }}</option>@endforeach</select></div>
    <div class="order-field" id="discount-categories" style="margin-top:10px"><label>دسته‌بندی‌ها (چند انتخابی)</label><select class="order-select" name="category_ids[]" multiple style="height:110px">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name_fa ?: $category->name }}</option>@endforeach</select></div>
    <div class="order-form-row" style="margin-top:10px"><div class="order-field"><label>شروع</label><input class="order-input" type="datetime-local" name="starts_at"></div><div class="order-field"><label>پایان</label><input class="order-input" type="datetime-local" name="ends_at"></div></div>
    <div class="order-field"><label>توضیح داخلی</label><textarea class="order-textarea" name="description" placeholder="هدف کمپین یا نکات اجرایی...">{{ old('description') }}</textarea></div>
    <div style="display:flex;gap:18px;margin:12px 0"><label class="order-check"><input type="checkbox" name="is_active" value="1" checked> فعال باشد</label><label class="order-check"><input type="checkbox" name="first_order_only" value="1"> فقط سفارش اول</label></div>
    <button class="order-btn primary" id="discount-submit" style="width:100%"><i class="fa-solid fa-plus"></i> ساخت تخفیف</button>
  </form>
</section>
