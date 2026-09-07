@extends('layouts.admin')

@section('title', 'لاگ عملیات — تکنولوژی مارکتینگ')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content flex-1 overflow-y-auto" id="content">
    @if(session('success'))<div class="mt-alert mt-alert-success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    <div class="mt-page-head"><div><div class="mt-eyebrow">تکنولوژی مارکتینگ</div><h1>لاگ عملیات</h1><p>اجرای سناریوها و آماده‌سازی محتوا را با خطا، تلاش و شناسه‌ی مشترک دنبال کن.</p></div><div class="mt-head-actions"><span class="mt-status"><span></span> ثبت عملیات</span><a class="mt-btn" href="{{ route('admin.marketing-technology.index') }}"><i class="fa-solid fa-arrow-right"></i> مرکز فرماندهی</a></div></div>
    <form method="GET" class="mt-card mt-card-pad mt-log-filter"><div class="mt-field"><label>وضعیت</label><select class="mt-input" name="status"><option value="">همه وضعیت‌ها</option><option value="queued" @selected(request('status') === 'queued')>در صف</option><option value="running" @selected(request('status') === 'running')>در حال اجرا</option><option value="completed" @selected(request('status') === 'completed')>تکمیل‌شده</option><option value="failed" @selected(request('status') === 'failed')>ناموفق</option><option value="cancelled" @selected(request('status') === 'cancelled')>لغوشده</option></select></div><div class="mt-form-actions"><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button></div></form>
    <section class="mt-card mt-card-pad mt-table-card"><div class="mt-section-head"><div><h2>اجرای عملیات</h2><p>هر تلاش با شناسه‌ی مستقل و قابل پیگیری ثبت می‌شود.</p></div><span class="mt-badge">{{ number_format($operations->total()) }} اجرا</span></div>@if(!$ready)<div class="mt-note">مدل اجرای عملیات هنوز روی این محیط اجرا نشده است.</div>@elseif($operations->isEmpty())<div class="mt-table-empty"><i class="fa-solid fa-list-check"></i><strong>هنوز عملیاتی ثبت نشده است</strong><small>با ورود محتوا به صف، اجرای آن اینجا ثبت می‌شود.</small></div>@else<div class="mt-table-wrap"><table class="mt-table"><thead><tr><th>عملیات</th><th>محتوا</th><th>وضعیت</th><th>تلاش</th><th>خطا</th><th>زمان</th><th>عملیات بعدی</th></tr></thead><tbody>@foreach($operations as $operation)<tr><td><strong>{{ $operation->operation_type }}</strong><small class="mt-event-meta">{{ $operation->run_uuid }}</small></td><td>{{ $operation->content?->title ?? '—' }}</td><td><span class="mt-log-status mt-log-status-{{ $operation->status }}">{{ ['queued' => 'در صف', 'running' => 'در حال اجرا', 'completed' => 'تکمیل‌شده', 'failed' => 'ناموفق', 'cancelled' => 'لغوشده'][$operation->status] ?? $operation->status }}</span></td><td>{{ number_format($operation->attempt) }}</td><td><span class="mt-log-error">{{ $operation->error_message ?: '—' }}</span></td><td>{{ $operation->created_at?->format('Y/m/d H:i') }}</td><td>@if(in_array($operation->status, ['failed', 'cancelled'], true))<form method="POST" action="{{ route('admin.marketing-technology.logs.retry', $operation) }}">@csrf<button class="mt-btn mt-btn-small" type="submit"><i class="fa-solid fa-rotate-right"></i> تلاش مجدد</button></form>@else<span class="mt-event-meta">—</span>@endif</td></tr>@endforeach</tbody></table></div><div class="mt-pagination">{{ $operations->links() }}</div>@endif</section>
  </div>
</main>
@endsection
