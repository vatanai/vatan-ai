@php
  $cards = [
    ['کل سفارشات', $stats['total'] ?? 0, 'fa-receipt'],
    ['تکمیل‌شده', $stats['completed'] ?? $stats['full'] ?? 0, 'fa-circle-check'],
    ['در حال پردازش', $stats['active'] ?? $stats['partial'] ?? 0, 'fa-spinner'],
    ['ناموفق / لغوشده', $stats['failed'] ?? $stats['cancelled'] ?? 0, 'fa-triangle-exclamation'],
    ['اعتبار', number_format($stats['credits'] ?? 0), 'fa-coins'],
  ];
@endphp
<div class="orders-stats">
  @foreach($cards as [$label,$value,$icon])
    <div class="order-stat">
      <div class="order-stat-icon"><i class="fa-solid {{ $icon }}"></i></div>
      <div class="order-stat-label">{{ $label }}</div>
      <div class="order-stat-value">{{ is_numeric($value) ? number_format($value) : $value }}</div>
    </div>
  @endforeach
</div>
