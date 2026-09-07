<div class="finance-two-column finance-settings-layout">
  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">تنظیمات محاسبات آینده</div><div class="finance-card-subtitle">تغییر این اعداد هیچ رکورد گذشته‌ای را بازنویسی نمی‌کند.</div></div></div>
    <form class="finance-form" method="post" action="{{ route('admin.finance.settings.update') }}">
      @csrf @method('PUT')
      <div class="finance-form-grid">
        <label class="finance-field"><span>کارمزد درگاه (درصد)</span><input class="finance-input" type="number" min="0" step="0.01" name="gateway_fee_percent" value="{{ $settings['gateway_fee_percent'] }}" @disabled(!$canApprove)></label>
        <label class="finance-field"><span>تخصیص زیرساخت (درصد)</span><input class="finance-input" type="number" min="0" step="0.01" name="infrastructure_allocation_percent" value="{{ $settings['infrastructure_allocation_percent'] }}" @disabled(!$canApprove)></label>
        <label class="finance-field"><span>تخصیص نیروی انسانی (درصد)</span><input class="finance-input" type="number" min="0" step="0.01" name="workforce_allocation_percent" value="{{ $settings['workforce_allocation_percent'] }}" @disabled(!$canApprove)></label>
        <label class="finance-field"><span>هزینه تخمینی هر اعتبار (تومان)</span><input class="finance-input" type="number" min="0" step="1" name="estimated_model_cost_per_credit_toman" value="{{ $settings['estimated_model_cost_per_credit_toman'] }}" @disabled(!$canApprove)></label>
        <label class="finance-field"><span>حد هشدار حاشیه منفی</span><input class="finance-input" type="number" min="0" step="0.01" name="negative_margin_alert_percent" value="{{ $settings['negative_margin_alert_percent'] }}" @disabled(!$canApprove)></label>
        <label class="finance-field"><span>حد هشدار جهش دلار (درصد)</span><input class="finance-input" type="number" min="0" step="0.01" name="exchange_jump_alert_percent" value="{{ $settings['exchange_jump_alert_percent'] }}" @disabled(!$canApprove)></label>
      </div>
      @if($canApprove)<div class="finance-form-actions"><button class="finance-btn primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات</button></div>@else<div class="finance-readonly-note">تغییر فرمول‌های مالی فقط برای مالک مجاز است.</div>@endif
    </form>
  </section>
  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">همگام‌سازی داده فعلی</div><div class="finance-card-subtitle">رکوردهای قبلی خرید، سفارش و اجرای مدل را بدون تغییر منبع وارد نماهای مالی می‌کند.</div></div></div>
    <div class="finance-sync-box"><i class="fa-solid fa-rotate"></i><p>همگام‌سازی تکرارپذیر است و رکورد تکراری نمی‌سازد.</p>@if($canWrite)<form method="post" action="{{ route('admin.finance.sync') }}">@csrf<button class="finance-btn secondary">اجرای همگام‌سازی</button></form>@endif</div>
  </section>
</div>
<section class="finance-card">
  <div class="finance-card-head"><div><div class="finance-card-title">ثبت تغییرات حساس</div><div class="finance-card-subtitle">ایجاد، ویرایش، تأیید، حذف، نرخ ارز و تغییر فرمول‌ها</div></div><span class="finance-count">{{ number_format($auditLogs->total()) }} رویداد</span></div>
  <div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>زمان</th><th>عملیات</th><th>رکورد</th><th>مدیر</th><th>نشانی شبکه</th></tr></thead><tbody>
    @forelse($auditLogs as $log)<tr><td>{{ $log->created_at?->format('Y/m/d H:i') }}</td><td>{{ $log->action }}</td><td>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id ?: '—' }}</td><td>{{ $log->admin?->name ?: 'سیستم' }}</td><td>{{ $log->ip_address ?: '—' }}</td></tr>
    @empty<tr><td colspan="5" class="finance-empty">هنوز تغییری ثبت نشده است.</td></tr>@endforelse
  </tbody></table></div><div class="finance-pagination">{{ $auditLogs->links() }}</div>
</section>
