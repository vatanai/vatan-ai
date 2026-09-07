<div class="finance-reference-grid">
  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">مراکز هزینه</div><div class="finance-card-subtitle">محل مصرف هزینه در ساختار کسب‌وکار</div></div></div>
    <div class="finance-reference-list">@foreach($costCenters as $item)<div class="finance-reference-row"><div><strong>{{ $item->name }}</strong><small>{{ $item->code }}</small></div><span class="finance-status {{ $item->is_active ? 'status-paid' : 'status-cancelled' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</span></div>@endforeach</div>
    @if($canWrite)<form class="finance-mini-form" method="post" action="{{ route('admin.finance.cost-centers.store') }}">@csrf<input class="finance-input" name="name" placeholder="نام مرکز هزینه" required><input class="finance-input" name="code" placeholder="کد اختیاری"><textarea class="finance-input finance-textarea" name="description" placeholder="توضیح"></textarea><button class="finance-btn primary">افزودن مرکز</button></form>@endif
  </section>
  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">تأمین‌کنندگان</div><div class="finance-card-subtitle">طرف حساب هزینه‌ها و خدمات</div></div></div>
    <div class="finance-reference-list">@forelse($vendors as $item)<div class="finance-reference-row"><div><strong>{{ $item->name }}</strong><small>{{ $item->contact_name ?: $item->phone ?: 'بدون اطلاعات تماس' }}</small></div><span class="finance-status {{ $item->is_active ? 'status-paid' : 'status-cancelled' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</span></div>@empty<div class="finance-empty">هنوز تأمین‌کننده‌ای ثبت نشده است.</div>@endforelse</div>
    @if($canWrite)<form class="finance-mini-form" method="post" action="{{ route('admin.finance.vendors.store') }}">@csrf<input class="finance-input" name="name" placeholder="نام تأمین‌کننده" required><input class="finance-input" name="contact_name" placeholder="نام رابط"><input class="finance-input" name="phone" placeholder="تلفن"><input class="finance-input" type="email" name="email" placeholder="ایمیل"><button class="finance-btn primary">افزودن تأمین‌کننده</button></form>@endif
  </section>
  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">روش‌های پرداخت</div><div class="finance-card-subtitle">مسیر پرداخت یا دریافت وجه</div></div></div>
    <div class="finance-reference-list">@foreach($paymentMethods as $item)<div class="finance-reference-row"><div><strong>{{ $item->name }}</strong><small>{{ $item->code }}</small></div><span class="finance-status {{ $item->is_active ? 'status-paid' : 'status-cancelled' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</span></div>@endforeach</div>
    @if($canWrite)<form class="finance-mini-form" method="post" action="{{ route('admin.finance.payment-methods.store') }}">@csrf<input class="finance-input" name="name" placeholder="نام روش پرداخت" required><input class="finance-input" name="code" placeholder="کد اختیاری"><button class="finance-btn primary">افزودن روش</button></form>@endif
  </section>
</div>
