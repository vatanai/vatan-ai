@php
  $tx = $editingTransaction;
  $formSection = $section === 'expenses' ? 'expense' : ($section === 'income' ? 'income' : old('direction', $tx?->direction ?? 'expense'));
@endphp
<section class="finance-card">
  <div class="finance-card-head">
    <div><div class="finance-card-title">{{ $tx ? 'ویرایش تراکنش' : 'ثبت تراکنش دستی' }}</div><div class="finance-card-subtitle">مبلغ نهایی به تومان با نرخ همین فرم ذخیره و ثابت می‌شود.</div></div>
    @if($tx)<a class="finance-link" href="{{ route('admin.finance.show', ['section' => $section]) }}">انصراف</a>@endif
  </div>
  <form class="finance-form" method="post" enctype="multipart/form-data" action="{{ $tx ? route('admin.finance.transactions.update', $tx) : route('admin.finance.transactions.store') }}">
    @csrf @if($tx) @method('PUT') @endif
    <div class="finance-form-grid">
      <label class="finance-field"><span>نوع</span><select class="finance-input" name="direction" required><option value="expense" @selected(old('direction', $formSection) === 'expense')>هزینه</option><option value="income" @selected(old('direction', $formSection) === 'income')>درآمد</option></select></label>
      <label class="finance-field"><span>دسته</span><select class="finance-input" name="category" required>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category', $tx?->category) === $key)>{{ $label }}</option>@endforeach</select></label>
      <label class="finance-field finance-span-2"><span>عنوان</span><input class="finance-input" name="title" value="{{ old('title', $tx?->title) }}" required maxlength="190"></label>
      <label class="finance-field"><span>مبلغ</span><input class="finance-input" type="number" min="0" step="0.0001" name="amount_original" value="{{ old('amount_original', $tx?->amount_original) }}" required></label>
      <label class="finance-field"><span>ارز</span><select class="finance-input" name="currency"><option value="IRT" @selected(old('currency', $tx?->currency === 'USD' ? 'USD' : 'IRT') === 'IRT')>تومان</option><option value="USD" @selected(old('currency', $tx?->currency) === 'USD')>دلار</option></select></label>
      <label class="finance-field"><span>نرخ دلار به تومان</span><input class="finance-input" type="number" min="0" step="0.0001" name="exchange_rate_toman" value="{{ old('exchange_rate_toman', $tx?->currency === 'USD' ? $tx?->exchange_rate_toman : '') }}" placeholder="برای تومان خالی بماند"></label>
      <label class="finance-field"><span>وضعیت</span><select class="finance-input" name="status">@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected(old('status', $tx?->status ?? 'paid') === $key)>{{ $label }}</option>@endforeach</select></label>
      <label class="finance-field"><span>تاریخ ثبت</span><input class="finance-input" type="datetime-local" name="occurred_at" value="{{ old('occurred_at', $tx?->occurred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required></label>
      <label class="finance-field"><span>سررسید</span><input class="finance-input" type="date" name="due_at" value="{{ old('due_at', $tx?->due_at?->format('Y-m-d')) }}"></label>
      <label class="finance-field"><span>تاریخ پرداخت</span><input class="finance-input" type="datetime-local" name="paid_at" value="{{ old('paid_at', $tx?->paid_at?->format('Y-m-d\TH:i')) }}"></label>
      <label class="finance-field"><span>مرکز هزینه</span><select class="finance-input" name="cost_center_id"><option value="">انتخاب نشده</option>@foreach($costCenters as $item)<option value="{{ $item->id }}" @selected((string) old('cost_center_id', $tx?->cost_center_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></label>
      <label class="finance-field"><span>تأمین‌کننده</span><select class="finance-input" name="vendor_id"><option value="">انتخاب نشده</option>@foreach($vendors as $item)<option value="{{ $item->id }}" @selected((string) old('vendor_id', $tx?->vendor_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></label>
      <label class="finance-field"><span>روش پرداخت</span><select class="finance-input" name="payment_method_id"><option value="">انتخاب نشده</option>@foreach($paymentMethods as $item)<option value="{{ $item->id }}" @selected((string) old('payment_method_id', $tx?->payment_method_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach</select></label>
      <label class="finance-field"><span>فایل فاکتور</span><input class="finance-input finance-file" type="file" name="invoice" accept=".pdf,.jpg,.jpeg,.png"></label>
      <label class="finance-field"><span>برچسب‌ها</span><input class="finance-input" name="tags_text" value="{{ old('tags_text', implode(', ', $tx?->tags ?? [])) }}" placeholder="ماهانه، سرور، ضروری"></label>
      <label class="finance-field finance-span-2"><span>یادداشت</span><textarea class="finance-input finance-textarea" name="notes" maxlength="5000">{{ old('notes', $tx?->notes) }}</textarea></label>
    </div>
    <div class="finance-form-actions"><button class="finance-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> {{ $tx ? 'ذخیره تغییرات' : 'ثبت تراکنش' }}</button></div>
  </form>
</section>
