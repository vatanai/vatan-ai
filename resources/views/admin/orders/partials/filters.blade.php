<form class="order-panel order-filters" method="GET">
  <div class="order-field"><label>جستجو</label><input class="order-input" name="q" value="{{ request('q') }}" placeholder="شماره سفارش، کاربر یا تراکنش"></div>
  <div class="order-field"><label>وضعیت سفارش</label><select class="order-select" name="status"><option value="">همه</option>@foreach(['pending'=>'در انتظار','confirmed'=>'تأییدشده','processing'=>'در حال انجام','completed'=>'تکمیل‌شده','cancelled'=>'لغوشده','review'=>'نیازمند بررسی'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select></div>
  <div class="order-field"><label>پرداخت</label><select class="order-select" name="payment_status"><option value="">همه</option>@foreach(['unpaid'=>'پرداخت‌نشده','pending'=>'در انتظار','paid'=>'پرداخت‌شده','failed'=>'ناموفق','partially_refunded'=>'بازپرداخت جزئی','refunded'=>'بازپرداخت کامل'] as $v=>$l)<option value="{{ $v }}" @selected(request('payment_status')===$v)>{{ $l }}</option>@endforeach</select></div>
  <div class="order-field"><label>محصول</label><select class="order-select" name="product_id"><option value="">همه محصولات</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)request('product_id')===(string)$product->id)>{{ $product->name_fa }}</option>@endforeach</select></div>
  <div class="order-field"><label>از تاریخ</label><input class="order-input" type="date" name="from" value="{{ request('from') }}"></div>
  <button class="order-btn primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال</button>
</form>
