@extends('layouts.admin')
@section('title', 'صف پردازش — وطن استودیو')

@php
  $stats = $stats ?? [];
  $rows = $rows ?? collect();
  $workers = $workers ?? [];
  $modelQueue = $modelQueue ?? [];
  $recentErrors = $recentErrors ?? [];
  $filters = $filters ?? [];
  $statusClasses = ['queued' => 'queue-badge queue-badge-warning', 'processing' => 'queue-badge queue-badge-info', 'retrying' => 'queue-badge queue-badge-purple', 'failed' => 'queue-badge queue-badge-danger', 'completed' => 'queue-badge queue-badge-success'];
  $statusLabels = ['queued' => 'در صف', 'processing' => 'در حال اجرا', 'retrying' => 'اجرای مجدد', 'failed' => 'ناموفق', 'completed' => 'موفق'];
  $providerOptions = collect($rows)->pluck('provider')->filter()->unique()->values();
@endphp

@push('styles')
<style>
  .queue-page{direction:rtl;padding:24px;max-width:1600px;margin:0 auto;color:var(--text-h);}
  .queue-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;}
  .queue-head h1{font-size:18px;font-weight:800;margin:0;}
  .queue-head p{font-size:11px;color:var(--text-soft);margin:4px 0 0;}
  .queue-live{display:inline-flex;align-items:center;gap:7px;border:1px solid color-mix(in srgb,var(--success) 30%,var(--border));background:color-mix(in srgb,var(--success) 8%,transparent);color:var(--success);border-radius:999px;padding:6px 10px;font-size:11px;font-weight:700;}
  .queue-live-dot{width:7px;height:7px;border-radius:50%;background:var(--success);}
  .queue-actions{margin-right:auto;display:flex;gap:8px;flex-wrap:wrap;}
  .queue-btn{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text-main);padding:8px 12px;font-size:11px;font-weight:700;cursor:pointer;text-decoration:none;}
  .queue-btn-danger{color:var(--danger);border-color:color-mix(in srgb,var(--danger) 25%,var(--border));background:color-mix(in srgb,var(--danger) 6%,transparent);}
  .queue-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px;}
  .queue-stat{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:14px;min-height:86px;}
  .queue-stat-label{font-size:10px;color:var(--text-soft);margin-bottom:8px;}
  .queue-stat-value{font-size:24px;font-weight:900;line-height:1;color:var(--text-h);}
  .queue-stat-sub{font-size:10px;color:var(--text-soft);margin-top:7px;}
  .queue-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px;align-items:start;}
  .queue-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
  .queue-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);}
  .queue-card-title{font-size:12px;font-weight:800;color:var(--text-h);}
  .queue-card-meta{font-size:10px;color:var(--text-soft);}
  .queue-filter{display:flex;gap:8px;flex-wrap:wrap;padding:12px 16px;background:var(--input-bg);border-bottom:1px solid var(--border);}
  .queue-filter input,.queue-filter select{height:34px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text-main);font-size:11px;padding:0 10px;min-width:130px;}
  .queue-filter input{flex:1;min-width:210px;}
  .queue-table-wrap{overflow:auto;}
  .queue-table{width:100%;border-collapse:collapse;min-width:920px;}
  .queue-table th{font-size:9px;color:var(--text-soft);font-weight:800;text-align:right;padding:10px 12px;background:var(--input-bg);border-bottom:1px solid var(--border);white-space:nowrap;}
  .queue-table td{font-size:10.5px;color:var(--text-main);padding:11px 12px;border-bottom:1px solid var(--border);vertical-align:middle;}
  .queue-table tr:last-child td{border-bottom:0;}
  .queue-id{font-family:monospace;color:var(--text-soft);font-size:10px;direction:ltr;text-align:right;}
  .queue-mono{font-family:monospace;direction:ltr;text-align:right;color:var(--primary);font-size:10px;}
  .queue-badge{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:4px 8px;font-size:9.5px;font-weight:800;white-space:nowrap;}
  .queue-badge-warning{color:var(--warning);background:color-mix(in srgb,var(--warning) 10%,transparent);}
  .queue-badge-info{color:var(--info);background:color-mix(in srgb,var(--info) 10%,transparent);}
  .queue-badge-purple{color:var(--accent);background:color-mix(in srgb,var(--accent) 10%,transparent);}
  .queue-badge-danger{color:var(--danger);background:color-mix(in srgb,var(--danger) 10%,transparent);}
  .queue-badge-success{color:var(--success);background:color-mix(in srgb,var(--success) 10%,transparent);}
  .queue-muted{color:var(--text-soft);}
  .queue-error{max-width:260px;color:var(--danger);font-size:9px;line-height:1.7;}
  .queue-side{display:grid;gap:12px;}
  .queue-side-list{padding:12px 16px;}
  .queue-worker{display:flex;align-items:center;gap:9px;padding:9px 0;border-bottom:1px solid var(--border);}
  .queue-worker:last-child{border-bottom:0;}
  .queue-worker-dot{width:8px;height:8px;border-radius:50%;background:var(--text-soft);flex:none;}
  .queue-worker-dot.is-running{background:var(--success);}
  .queue-worker-name{font-size:11px;font-weight:800;color:var(--text-h);}
  .queue-worker-meta{font-size:9px;color:var(--text-soft);margin-top:2px;}
  .queue-model-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);}
  .queue-model-row:last-child{border-bottom:0;}
  .queue-model-name{font-family:monospace;font-size:9.5px;direction:ltr;text-align:right;color:var(--text-main);word-break:break-all;}
  .queue-model-provider{font-size:9px;color:var(--text-soft);margin-top:2px;}
  .queue-model-count{font-size:13px;font-weight:900;color:var(--text-h);}
  .queue-error-row{padding:9px 0;border-bottom:1px solid var(--border);}
  .queue-error-row:last-child{border-bottom:0;}
  .queue-error-title{font-family:monospace;direction:ltr;text-align:right;color:var(--danger);font-size:9.5px;}
  .queue-error-message{font-size:9.5px;line-height:1.7;color:var(--text-soft);margin-top:3px;}
  .queue-empty{padding:34px 16px;text-align:center;color:var(--text-soft);font-size:11px;}
  .queue-inline-form{display:inline;}
  .queue-icon-btn{border:0;background:transparent;color:var(--text-soft);cursor:pointer;padding:4px;font-size:11px;}
  .queue-icon-btn:hover{color:var(--danger);}
  @media(max-width:1100px){.queue-stats{grid-template-columns:repeat(3,minmax(0,1fr));}.queue-layout{grid-template-columns:1fr;}}
  @media(max-width:600px){.queue-page{padding:16px 12px;}.queue-stats{grid-template-columns:repeat(2,minmax(0,1fr));}.queue-stat-value{font-size:20px;}.queue-actions{margin-right:0;width:100%;}.queue-btn{flex:1;justify-content:center;}}
</style>
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content">
    <div class="queue-page">
      <div class="queue-head">
        <div>
          <h1>صف پردازش واقعی</h1>
          <p>منبع داده: `{{ $queueConnection }}` / صف `{{ $queueName }}` · آخرین خواندن: {{ now()->format('H:i:s') }}</p>
        </div>
        <span class="queue-live"><span class="queue-live-dot"></span>داده زنده</span>
        <div class="queue-actions">
          <form method="POST" action="{{ route('admin.jobs.clear') }}" onsubmit="return confirm('فقط جاب‌های در انتظار صف پاک شوند؟')" class="queue-inline-form">
            @csrf
            <input type="hidden" name="queue" value="{{ $queueName }}">
            <button class="queue-btn queue-btn-danger" type="submit"><i class="fa-solid fa-trash"></i> پاک‌سازی صف</button>
          </form>
          <a class="queue-btn" href="{{ request()->fullUrl() }}"><i class="fa-solid fa-arrows-rotate"></i> تازه‌سازی</a>
        </div>
      </div>

      <div class="queue-stats">
        <div class="queue-stat"><div class="queue-stat-label">در حال پردازش</div><div class="queue-stat-value">{{ number_format((int) ($stats['processing'] ?? 0)) }}</div><div class="queue-stat-sub">سفارش، آزمایش و درخواست فعال</div></div>
        <div class="queue-stat"><div class="queue-stat-label">در انتظار</div><div class="queue-stat-value" style="color:var(--warning);">{{ number_format((int) ($stats['queued'] ?? 0)) }}</div><div class="queue-stat-sub">جاب‌های واقعی پایگاه داده</div></div>
        <div class="queue-stat"><div class="queue-stat-label">موفق در ۲۴ ساعت</div><div class="queue-stat-value" style="color:var(--success);">{{ number_format((int) ($stats['success_24h'] ?? 0)) }}</div><div class="queue-stat-sub">از عملیات ثبت‌شده</div></div>
        <div class="queue-stat"><div class="queue-stat-label">ناموفق در ۲۴ ساعت</div><div class="queue-stat-value" style="color:var(--danger);">{{ number_format((int) ($stats['failed_24h'] ?? 0)) }}</div><div class="queue-stat-sub">به‌همراه خطاهای صف</div></div>
        <div class="queue-stat"><div class="queue-stat-label">میانگین زمان اجرا</div><div class="queue-stat-value">{{ isset($stats['avg_seconds']) && $stats['avg_seconds'] !== null ? number_format((float) $stats['avg_seconds'], 1) . 's' : '—' }}</div><div class="queue-stat-sub">بر اساس ۲۴ ساعت اخیر</div></div>
      </div>

      <div class="queue-layout">
        <section class="queue-card">
          <div class="queue-card-head"><div class="queue-card-title">عملیات و جاب‌های واقعی</div><div class="queue-card-meta">{{ number_format($rows->count()) }} ردیف نمایش‌داده‌شده</div></div>
          <form class="queue-filter" method="GET" action="{{ route('admin.jobs') }}">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="جستجو در شناسه، مدل، کاربر یا خطا">
            <select name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select>
            <select name="provider"><option value="">همه providerها</option>@foreach($providerOptions as $option)<option value="{{ $option }}" @selected(($filters['provider'] ?? '') === $option)>{{ $option }}</option>@endforeach</select>
            <button class="queue-btn" type="submit">اعمال فیلتر</button>
          </form>
          <div class="queue-table-wrap">
            <table class="queue-table">
              <thead><tr><th>شناسه</th><th>منبع</th><th>کاربر / محصول</th><th>provider / مدل</th><th>وضعیت</th><th>زمان</th><th>تلاش</th><th>خطا / عملیات</th></tr></thead>
              <tbody>
              @forelse($rows as $row)
                <tr>
                  <td><div class="queue-id">{{ $row['id'] }}</div><div class="queue-muted" style="font-size:9px;margin-top:3px;">{{ $row['job_class'] }}</div></td>
                  <td><span class="queue-muted">{{ $row['source'] === 'queue' ? 'صف دیتابیس' : ($row['source'] === 'failed' ? 'خطای صف' : ($row['source'] === 'lab' ? 'آزمایشگاه' : 'سفارش')) }}</span></td>
                  <td><div>{{ $row['user'] }}</div><div class="queue-muted" style="margin-top:3px;">{{ $row['product'] }}</div></td>
                  <td><div class="queue-mono">{{ $row['model'] }}</div><div class="queue-muted" style="margin-top:3px;">{{ $row['provider'] }}</div></td>
                  <td><span class="{{ $statusClasses[$row['status']] ?? 'queue-badge' }}">{{ $row['status_label'] }}</span></td>
                  <td class="queue-muted">{{ $row['time_label'] }}</td>
                  <td class="queue-muted">{{ $row['attempts'] === null ? '—' : $row['attempts'] }}</td>
                  <td>
                    @if($row['error'])<div class="queue-error">{{ $row['error'] }}</div>@endif
                    @if($row['failed_id'])
                      <form method="POST" action="{{ route('admin.jobs.failed.retry', $row['failed_id']) }}" class="queue-inline-form">@csrf<button class="queue-icon-btn" title="اجرای مجدد" type="submit"><i class="fa-solid fa-rotate-right"></i></button></form>
                      <form method="POST" action="{{ route('admin.jobs.failed.forget', $row['failed_id']) }}" class="queue-inline-form" onsubmit="return confirm('رکورد خطا حذف شود؟')">@csrf @method('DELETE')<button class="queue-icon-btn" title="حذف خطا" type="submit"><i class="fa-solid fa-trash"></i></button></form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="8"><div class="queue-empty">در این فیلتر داده‌ای وجود ندارد.</div></td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <aside class="queue-side">
          <section class="queue-card"><div class="queue-card-head"><div class="queue-card-title">وضعیت worker</div></div><div class="queue-side-list">@foreach($workers as $worker)<div class="queue-worker"><span class="queue-worker-dot {{ $worker['running'] ? 'is-running' : '' }}"></span><div><div class="queue-worker-name">{{ $worker['name'] }}</div><div class="queue-worker-meta">{{ $worker['running'] ? 'فعال' : 'خاموش یا بدون heartbeat' }} · PID {{ $worker['pid'] ?: '—' }} · صف {{ $worker['queue'] }}</div></div></div>@endforeach</div></section>
          <section class="queue-card"><div class="queue-card-head"><div class="queue-card-title">صف به تفکیک مدل</div></div><div class="queue-side-list">@forelse($modelQueue as $model)<div class="queue-model-row"><div><div class="queue-model-name">{{ $model['model'] ?: 'شناسه ثبت نشده' }}</div><div class="queue-model-provider">{{ $model['provider'] ?: '—' }}</div></div><div class="queue-model-count">{{ number_format($model['total']) }}</div></div>@empty<div class="queue-empty" style="padding:18px 0;">صف مدل‌ها خالی است.</div>@endforelse</div></section>
          <section class="queue-card"><div class="queue-card-head"><div class="queue-card-title">خطاهای اخیر</div></div><div class="queue-side-list">@forelse($recentErrors as $error)<div class="queue-error-row"><div class="queue-error-title">{{ $error['title'] }}</div><div class="queue-error-message">{{ $error['message'] }}</div></div>@empty<div class="queue-empty" style="padding:18px 0;">خطای اخیر ثبت نشده است.</div>@endforelse</div></section>
        </aside>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  window.setTimeout(function () { window.location.reload(); }, 15000);
</script>
@endsection
