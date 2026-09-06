@extends('layouts.admin')
@section('title','تاریخچه پیامک — وطن استودیو')
@push('styles')
<style>
.sms-history{padding:24px;display:grid;gap:18px}.sms-history-head h1{font-size:22px;font-weight:800;color:var(--text-h)}.sms-history-head p{font-size:12px;color:var(--text-soft);margin-top:5px}.sms-overview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.sms-stat{padding:16px;display:flex;align-items:center;gap:12px}.sms-stat-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--primary-l);color:var(--primary)}.sms-stat.warn .sms-stat-icon{background:var(--warning-l);color:var(--warning)}.sms-stat.danger .sms-stat-icon{background:var(--danger-l);color:var(--danger)}.sms-stat.success .sms-stat-icon{background:var(--success-l);color:var(--success)}.sms-stat b{display:block;color:var(--text-h);font-size:19px}.sms-stat span{display:block;color:var(--text-soft);font-size:10px;margin-top:3px}.sms-template-stats{padding:16px}.sms-section-title{font-size:13px;font-weight:800;color:var(--text-h);margin-bottom:12px}.sms-template-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.sms-template-stat{padding:11px;border:1px solid var(--border);background:var(--input-bg);border-radius:10px;display:flex;align-items:center;justify-content:space-between;gap:8px}.sms-template-stat div{min-width:0}.sms-template-stat strong{display:block;color:var(--text-main);font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sms-template-stat small{display:block;color:var(--text-soft);font-size:9px;margin-top:3px}.sms-template-stat b{color:var(--primary);font-size:15px}.sms-table-card{padding:18px;overflow:hidden}.sms-table-scroll{overflow:auto}.sms-table{width:100%;min-width:850px;border-collapse:collapse}.sms-table th,.sms-table td{padding:11px;border-bottom:1px solid var(--border);text-align:right;font-size:11px}.sms-table th{position:sticky;top:0;background:var(--card-bg);color:var(--text-soft);z-index:2}.sms-type{display:flex;align-items:center;gap:6px}.sms-type i{color:var(--primary)}@media(max-width:1050px){.sms-overview,.sms-template-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.sms-history{padding:14px}.sms-overview,.sms-template-stat-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content">
    <div class="sms-history">
      <div class="sms-history-head"><h1>تاریخچه ارسال پیامک</h1><p>آمار ارسال، اعتبار ارائه‌دهنده و عملکرد هر الگو را یکجا ببینید.</p></div>
      <section class="sms-overview">
        <article class="content-card sms-stat success"><span class="sms-stat-icon"><i class="fa-solid fa-paper-plane"></i></span><div><b>{{ number_format($historyStats['sent']) }}</b><span>کل پیامک‌های ارسال‌شده</span></div></article>
        <article class="content-card sms-stat"><span class="sms-stat-icon"><i class="fa-solid fa-calendar-day"></i></span><div><b>{{ number_format($historyStats['today']) }}</b><span>ارسال موفق امروز</span></div></article>
        <article class="content-card sms-stat danger"><span class="sms-stat-icon"><i class="fa-solid fa-circle-exclamation"></i></span><div><b>{{ number_format($historyStats['failed']) }}</b><span>ارسال‌های ناموفق</span></div></article>
        <article class="content-card sms-stat warn"><span class="sms-stat-icon"><i class="fa-solid fa-wallet"></i></span><div><b>{{ data_get($providers->first()?->settings,'last_balance') !== null ? number_format((float)data_get($providers->first()?->settings,'last_balance')) : '—' }}</b><span>اعتبار {{ $providers->first()?->name ?? 'پنل پیامک' }}</span></div></article>
      </section>
      <section class="content-card sms-template-stats">
        <h2 class="sms-section-title">تعداد ارسال هر الگو</h2>
        <div class="sms-template-stat-grid">
          @forelse($templates as $template)
            <article class="sms-template-stat"><div><strong title="{{ $template->name }}">{{ $template->name }}</strong><small>{{ $template->eventLabel() }} · {{ $template->provider_template_id ? 'BodyId '.$template->provider_template_id : 'ارسال ساده' }}</small></div><b>{{ number_format($template->sent_count) }}</b></article>
          @empty
            <span>الگویی ثبت نشده است.</span>
          @endforelse
        </div>
      </section>
      <div class="content-card sms-table-card">
        <h2 class="sms-section-title">ریز ارسال‌ها</h2>
        <div class="sms-table-scroll"><table class="sms-table"><thead><tr><th>زمان</th><th>نوع</th><th>گیرنده</th><th>متن</th><th>شناسه</th><th>وضعیت</th></tr></thead><tbody>
        @forelse($messages as $m)
          @php $eventKey=Str::afterLast($m->type,':'); $eventLabel=config("sms_events.events.{$eventKey}.label",$m->type); @endphp
          <tr><td>{{ $m->created_at?->format('Y/m/d H:i') }}</td><td><span class="sms-type"><i class="fa-solid {{ Str::startsWith($m->type,'test:')||Str::startsWith($m->type,'approval-check:')?'fa-flask':'fa-bolt' }}"></i>{{ $eventLabel }}</span></td><td dir="ltr">{{ $m->recipient }}</td><td>{{ Str::limit($m->body,80)?:'رمز یک‌بارمصرف' }}</td><td>{{ $m->provider_id?:'—' }}</td><td><span class="badge-pro badge-pro-{{ $m->status==='failed'?'danger':'success' }}">{{ $m->status==='failed'?'ناموفق':'ارسال‌شده' }}</span></td></tr>
        @empty
          <tr><td colspan="6">رکوردی وجود ندارد.</td></tr>
        @endforelse
        </tbody></table></div>
        {{ $messages->links() }}
      </div>
    </div>
  </div>
</main>
@endsection
