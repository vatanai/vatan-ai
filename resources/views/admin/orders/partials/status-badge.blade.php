@php
  $map = [
    'draft'=>['پیش‌نویس',''], 'pending'=>['در انتظار','warning'], 'confirmed'=>['تأییدشده','info'],
    'processing'=>['در حال انجام','warning'], 'completed'=>['تکمیل‌شده','success'],
    'cancelled'=>['لغوشده','danger'], 'review'=>['نیازمند بررسی','danger'],
    'unpaid'=>['پرداخت‌نشده',''], 'paid'=>['پرداخت‌شده','success'], 'failed'=>['ناموفق','danger'],
    'partially_refunded'=>['بازپرداخت جزئی','warning'], 'refunded'=>['بازپرداخت کامل','info'],
    'queued'=>['در صف','info'], 'retrying'=>['اجرای مجدد','warning'], 'expired'=>['منقضی','danger'],
    'stopped'=>['متوقف','danger'],
  ];
  [$label, $class] = $map[$status] ?? [$status, ''];
@endphp
<span class="order-badge {{ $class }}"><span class="order-dot"></span>{{ $label }}</span>
