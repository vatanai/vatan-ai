@php $reportLabels = ['daily'=>'روزانه','monthly'=>'ماهانه','plans'=>'پلن','products'=>'محصول','models'=>'مدل هوش مصنوعی','providers'=>'ارائه‌دهنده','channels'=>'کانال جذب','users'=>'کاربر']; @endphp
<section class="finance-card finance-filter-card">
  <form class="finance-filter-grid report" method="get">
    <label class="finance-field"><span>تفکیک گزارش</span><select class="finance-input" name="report">@foreach($reportLabels as $key => $label)<option value="{{ $key }}" @selected($report === $key)>{{ $label }}</option>@endforeach</select></label>
    <label class="finance-field"><span>از تاریخ</span><input class="finance-input" type="date" name="from" value="{{ request('from', $from->toDateString()) }}"></label>
    <label class="finance-field"><span>تا تاریخ</span><input class="finance-input" type="date" name="to" value="{{ request('to', $to->toDateString()) }}"></label>
    <button class="finance-btn primary"><i class="fa-solid fa-chart-column"></i> ساخت گزارش</button>
  </form>
</section>
<section class="finance-card">
  <div class="finance-card-head"><div><div class="finance-card-title">گزارش {{ $reportLabels[$report] }}</div><div class="finance-card-subtitle">تمام مبالغ بر اساس نسخه مالی ثبت‌شده در زمان رویداد هستند.</div></div><a class="finance-btn secondary" href="{{ route('admin.finance.export', ['report'=>$report, 'from'=>$from->toDateString(), 'to'=>$to->toDateString()]) }}"><i class="fa-solid fa-file-csv"></i> دریافت فایل</a></div>
  <div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>عنوان</th><th>تعداد</th><th>اعتبار</th><th>درآمد</th><th>هزینه تخمینی</th><th>هزینه مستقیم</th><th>هزینه تخصیصی</th><th>سود</th><th>حاشیه</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td><strong>{{ $row['label'] }}</strong></td><td>{{ number_format($row['count']) }}</td><td>{{ number_format($row['credits']) }}</td><td>{{ number_format($row['revenue']) }}</td><td>{{ number_format($row['estimated_cost']) }}</td><td>{{ number_format($row['direct_cost']) }}</td><td>{{ number_format($row['allocated_cost']) }}</td><td class="{{ $row['profit'] < 0 ? 'finance-negative' : 'finance-positive' }}"><strong>{{ number_format($row['profit']) }}</strong></td><td>{{ number_format($row['margin'], 1) }}٪</td></tr>
    @empty<tr><td colspan="9" class="finance-empty">در این بازه داده‌ای برای گزارش وجود ندارد.</td></tr>@endforelse
  </tbody></table></div>
</section>
