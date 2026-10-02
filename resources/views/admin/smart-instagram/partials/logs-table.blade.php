{{-- جدول لاگ عملیات: $logs, $compact --}}
<div class="si-table-wrap" style="margin-top:10px">
  <table class="table-pro">
    <thead><tr><th>سطح</th><th>عملیات</th><th>شرح</th>@unless($compact)<th>کاربر</th>@endunless<th>زمان</th></tr></thead>
    <tbody>
      @forelse($logs as $log)
        <tr>
          <td><span class="badge-pro badge-{{ ['error' => 'danger', 'warning' => 'warning', 'info' => 'info'][$log->level] ?? 'neutral' }}">{{ ['error' => 'خطا', 'warning' => 'هشدار', 'info' => 'اطلاعات'][$log->level] ?? $log->level }}</span></td>
          <td class="si-mono">{{ $log->action }}</td>
          <td style="white-space:normal;min-width:220px">{{ $log->message }}</td>
          @unless($compact)<td>{{ $log->admin?->name ?? 'سیستم' }}</td>@endunless
          <td class="si-muted">{{ \App\Services\SmartInstagram\Ui::ago($log->created_at) }}</td>
        </tr>
      @empty
        <tr><td colspan="{{ $compact ? 4 : 5 }}" class="si-table-empty">لاگی ثبت نشده.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
