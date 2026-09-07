<div class="credit-table-wrap"><table class="credit-table credit-report-table"><thead><tr>
  <th>زمان / منبع</th><th>اجراکننده</th><th>محصول</th><th>پرووایدر و مدل</th><th>وضعیت</th><th>عکس ورودی</th><th>خروجی</th><th>هزینه</th><th>جزئیات</th><th>تنظیمات</th>
</tr></thead><tbody>
  @forelse($transactions as $transaction)
    @php($statusClass = in_array($transaction['status_key'], ['completed','success','charge','refund'], true) ? 'success' : (in_array($transaction['status_key'], ['failed','usage'], true) ? 'danger' : 'warning'))
    <tr class="credit-report-row" data-transaction-id="{{ $transaction['id'] }}">
      <td><div class="credit-source-cell"><span class="credit-source-icon {{ $transaction['source_key'] }}"><i class="fa-solid {{ $transaction['source_key'] === 'lab' ? 'fa-flask' : ($transaction['source_key'] === 'user' ? 'fa-user' : ($transaction['source_key'] === 'ledger' ? 'fa-wallet' : 'fa-receipt')) }}"></i></span><div><strong>{{ $transaction['source_label'] }}</strong><small>{{ $transaction['date_jalali'] }}</small><small>{{ $transaction['date_gregorian'] }}</small></div></div></td>
      <td><div class="credit-entity-cell"><strong>{{ $transaction['actor_label'] }}</strong><small>{{ $transaction['user_name'] }}</small><small>{{ $transaction['user_contact'] }}</small></div></td>
      <td><div class="credit-entity-cell"><strong>{{ $transaction['product_name'] }}</strong>@if($transaction['order_number'])<small>{{ $transaction['order_number'] }}</small>@elseif($transaction['reference'] !== '—')<small>{{ $transaction['reference'] }}</small>@endif</div></td>
      <td><div class="credit-entity-cell"><strong>{{ $transaction['provider'] }}</strong><small>{{ $transaction['model'] }}</small>@if($transaction['latency_seconds'] !== null)<small>{{ number_format($transaction['latency_seconds'], 1) }} ثانیه · {{ $transaction['retries'] ?? 0 }} تلاش</small>@endif</div></td>
      <td><span class="credit-status-badge {{ $statusClass }}"><span></span>{{ $transaction['status_label'] }}</span>@if($transaction['error'])<small class="credit-error-text" title="{{ $transaction['error'] }}"><i class="fa-solid fa-circle-exclamation"></i> خطا</small>@endif</td>
      <td>
        @if(!empty($transaction['input_media']))
          <div class="credit-input-cell">
            @foreach($transaction['input_media'] as $input)
              @if(($input['type'] ?? 'image') === 'text')
                <a class="credit-input-text" href="{{ $input['url'] }}" target="_blank" rel="noopener" title="{{ $input['text'] ?: $input['label'] }}"><i class="fa-solid fa-align-right"></i><span>{{ $input['text'] ?: $input['label'] }}</span></a>
              @elseif(($input['type'] ?? 'image') === 'video')
                <a class="credit-input-thumb" href="{{ $input['url'] }}" target="_blank" rel="noopener" title="{{ $input['label'] }}"><video src="{{ $input['url'] }}" preload="metadata" muted playsinline></video><span class="credit-input-type"><i class="fa-solid fa-play"></i></span></a>
              @else
                <a class="credit-input-thumb" href="{{ $input['url'] }}" target="_blank" rel="noopener" title="{{ $input['label'] }}"><img src="{{ $input['url'] }}" alt="{{ $input['label'] }}" loading="lazy"></a>
              @endif
            @endforeach
          </div>
        @else
          <span class="credit-muted">ثبت نشده</span>
        @endif
      </td>
      <td>@if(count($transaction['output_urls']))<div class="credit-output-cell"><a href="{{ $transaction['output_urls'][0] }}" target="_blank" rel="noopener" aria-label="بازکردن خروجی {{ $transaction['media_label'] ?? 'ساخت' }}">@if(($transaction['media_type'] ?? 'image') === 'video')<video src="{{ $transaction['output_urls'][0] }}" preload="metadata" muted playsinline></video>@else<img src="{{ $transaction['output_urls'][0] }}" alt="خروجی">@endif</a><span>{{ $transaction['media_label'] ?? 'یک خروجی' }}</span></div>@else<span class="credit-muted">بدون خروجی</span>@endif</td>
      <td><div class="credit-cost-cell">@if($transaction['amount_usd'] !== null)<strong>${{ number_format($transaction['amount_usd'], 6) }}</strong><small>{{ number_format($transaction['amount_toman']) }} تومان</small>@if($transaction['credits'] !== null)<small>{{ number_format($transaction['credits'], 2) }} اعتبار مصرف‌شده</small>@endif @elseif($transaction['credits'] !== null)<strong>{{ number_format($transaction['credits'], 2) }} اعتبار</strong><small>هزینه سرویس ثبت نشده</small>@else<span class="credit-muted">—</span>@endif</div></td>
      <td>
        <div class="credit-row-details">
          @if($transaction['detail_url'])<a class="credit-detail-link" href="{{ $transaction['detail_url'] }}" target="_blank" rel="noopener">مشاهده <i class="fa-solid fa-arrow-up-left-from-circle"></i></a>@endif
          @if($transaction['reference'] !== '—')<small><i class="fa-solid fa-hashtag"></i> {{ $transaction['reference'] }}</small>@endif
          @if($transaction['note'])<small class="credit-row-note"><i class="fa-solid fa-circle-info"></i> {{ $transaction['note'] }}</small>@endif
          @if($transaction['error'])<small class="credit-error-text"><i class="fa-solid fa-circle-exclamation"></i> {{ $transaction['error'] }}</small>@endif
          @if(!$transaction['detail_url'] && $transaction['reference'] === '—' && !$transaction['note'] && !$transaction['error'])<span class="credit-muted">—</span>@endif
        </div>
      </td>
      <td>
        <div class="credit-settings-cell">
          @if($transaction['user_url'] ?? null)<a class="credit-row-action" href="{{ $transaction['user_url'] }}" title="ورودی‌ها و ساخت‌های این کاربر"><i class="fa-solid fa-user"></i><span>کاربر</span></a>@endif
          @if($transaction['finance_url'] ?? null)<a class="credit-row-action finance" href="{{ $transaction['finance_url'] }}" title="پرونده‌های مالی کاربر"><i class="fa-solid fa-coins"></i><span>مالی</span></a>@endif
          @if($transaction['order_url'] ?? null)<a class="credit-row-action order" href="{{ $transaction['order_url'] }}" title="جزئیات سفارش"><i class="fa-solid fa-receipt"></i><span>سفارش</span></a>@endif
          @if(!($transaction['user_url'] ?? null) && !($transaction['finance_url'] ?? null) && !($transaction['order_url'] ?? null))<span class="credit-muted">—</span>@endif
        </div>
      </td>
    </tr>
  @empty
    <tr><td colspan="10" class="credit-empty-state"><i class="fa-solid fa-receipt"></i><strong>رکوردی با این فیلتر پیدا نشد.</strong><span>با پاک‌کردن فیلترها یا اجرای یک تولید جدید، گزارش اینجا نمایش داده می‌شود.</span></td></tr>
  @endforelse
</tbody></table></div>
<div class="credit-report-footer">
  <label class="credit-per-page">تعداد ردیف‌ها
    <select data-credit-per-page aria-label="تعداد ردیف‌های فهرست">
      @foreach([20,50,100] as $pageSize)<option value="{{ $pageSize }}" @selected($transactions->perPage() === $pageSize)>{{ $pageSize }}</option>@endforeach
    </select>
  </label>
  @if($transactions->hasPages())<div class="credit-report-pagination">{{ $transactions->onEachSide(1)->links() }}</div>@endif
</div>
