<section class="finance-card finance-filter-card">
  <form class="finance-filter-grid" method="get">
    <input class="finance-input" type="search" name="q" value="{{ request('q') }}" placeholder="جستجو در عنوان یا کد پیگیری">
    <select class="finance-input" name="status"><option value="">همه وضعیت‌ها</option>@foreach($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
    <select class="finance-input" name="category"><option value="">همه دسته‌ها</option>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>@endforeach</select>
    <button class="finance-btn primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> جستجو</button>
  </form>
</section>

@if($canWrite)
  @include('admin.finance.partials.transaction-form')
@endif

<section class="finance-card">
  <div class="finance-card-head">
    <div><div class="finance-card-title">فهرست {{ $section === 'expenses' ? 'هزینه‌ها' : ($section === 'income' ? 'درآمدها' : 'تراکنش‌ها') }}</div><div class="finance-card-subtitle">رکوردهای خودکار قفل‌اند و فقط از منبع اصلی بروزرسانی می‌شوند.</div></div>
    <span class="finance-count">{{ number_format($transactions->total()) }} رکورد</span>
  </div>
  <div class="finance-table-wrap">
    <table class="finance-table finance-transactions-table">
      <thead><tr><th>تراکنش</th><th>دسته / مرکز</th><th>مبلغ</th><th>تاریخ / سررسید</th><th>وضعیت</th><th>ردگیری</th><th>عملیات</th></tr></thead>
      <tbody>
        @forelse($transactions as $transaction)
          <tr>
            <td><div class="finance-row-title">{{ $transaction->title }}</div><div class="finance-row-sub">{{ $transaction->reference_code }}</div></td>
            <td><div>{{ $categories[$transaction->category] ?? $transaction->category }}</div><div class="finance-row-sub">{{ $transaction->costCenter?->name ?: 'بدون مرکز هزینه' }}</div></td>
            <td class="{{ $transaction->direction === 'income' ? 'finance-positive' : 'finance-negative' }}"><strong>{{ $transaction->direction === 'income' ? '+' : '−' }}{{ number_format((float) $transaction->amount_toman) }}</strong><div class="finance-row-sub">{{ $transaction->currency === 'USD' ? '$' . number_format((float) $transaction->amount_original, 4) : 'تومان' }}</div></td>
            <td><div>{{ $transaction->occurred_at?->format('Y/m/d') }}</div><div class="finance-row-sub">{{ $transaction->due_at ? 'سررسید ' . $transaction->due_at->format('Y/m/d') : 'بدون سررسید' }}</div></td>
            <td><span class="finance-status status-{{ $transaction->status }}">{{ $statuses[$transaction->status] ?? $transaction->status }}</span></td>
            <td><span class="finance-source {{ $transaction->source_type === 'manual' ? 'manual' : 'automatic' }}">{{ $transaction->source_type === 'manual' ? 'دستی' : 'خودکار' }}</span>@if($transaction->approved_at)<div class="finance-row-sub">تأیید مالک</div>@endif</td>
            <td>
              <div class="finance-actions">
                @if($transaction->invoice_path)<a class="finance-icon-btn" href="{{ asset('storage/' . $transaction->invoice_path) }}" target="_blank" title="فاکتور"><i class="fa-solid fa-paperclip"></i></a>@endif
                @if($canWrite && $transaction->source_type === 'manual')<a class="finance-icon-btn" href="{{ request()->fullUrlWithQuery(['edit' => $transaction->id]) }}" title="ویرایش"><i class="fa-solid fa-pen"></i></a>@endif
                @if($canApprove && !$transaction->approved_at)
                  <form method="post" action="{{ route('admin.finance.transactions.approve', $transaction) }}">@csrf @method('PATCH')<button class="finance-icon-btn success" title="تأیید"><i class="fa-solid fa-check"></i></button></form>
                @endif
                @if($canApprove && $transaction->source_type === 'manual')
                  <form method="post" action="{{ route('admin.finance.transactions.destroy', $transaction) }}" onsubmit="return confirm('این تراکنش به‌صورت نرم حذف شود؟')">@csrf @method('DELETE')<button class="finance-icon-btn danger" title="حذف"><i class="fa-solid fa-trash"></i></button></form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="finance-empty">تراکنشی برای نمایش وجود ندارد.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="finance-pagination">{{ $transactions->links() }}</div>
</section>
