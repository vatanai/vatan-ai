<div class="sms-template-grid">
@forelse($templates as $template)
  @php
    $chars=mb_strlen($template->body);
    $parts=$chars<=70?1:(int)ceil($chars/67);
    $providerStatus=$template->provider_approval_status ?? ($template->provider_method === 'shared' ? 'pending' : 'not_applicable');
    $providerState=match($providerStatus){'approved'=>'is-approved','rejected'=>'is-rejected',default=>'is-pending'};
    $providerLabel=match($providerStatus){'approved'=>'تأیید ملی‌پیامک','rejected'=>'رد ملی‌پیامک','not_applicable'=>'ارسال تبلیغاتی/ساده',default=>'منتظر تأیید ملی‌پیامک'};
  @endphp
  <article class="content-card sms-template-card {{ !$template->is_active ? 'is-off' : '' }} {{ $providerState }}" data-template-group="{{ config('sms_events.events.'.$template->event_key.'.group','سفارشی') }}">
    <div class="sms-template-top"><div><div class="sms-template-event">{{ $template->eventLabel() }}</div><h3>{{ $template->name }}</h3></div><div class="sms-template-badges">@if($template->is_default)<span class="badge-pro badge-pro-primary">پیش‌فرض</span>@endif<span class="sms-state-icon {{ $template->is_active?'is-active':'is-inactive' }}" title="{{ $template->is_active?'الگوی فعال':'الگوی غیرفعال' }}"><i class="fa-solid {{ $template->is_active?'fa-circle-check':'fa-circle-xmark' }}"></i></span><span class="sms-vars-icon" title="متغیرها: {{ implode('، ',config('sms_events.events.'.$template->event_key.'.variables',[])) }}"><i class="fa-solid fa-brackets-curly"></i></span></div></div>
    <p class="sms-template-body">{{ $template->body }}</p>
    <div class="sms-template-meta"><span>{{ $chars }} کاراکتر</span><span>{{ $parts }} بخش پیامک</span><span>{{ number_format($template->sent_count) }} ارسال</span><span>{{ $template->provider_method === 'shared' ? 'BodyId: '.($template->provider_template_id ?: 'تنظیم‌نشده') : 'ارسال ساده' }}</span></div>
    <div class="sms-provider-state {{ $providerState }}"><i class="fa-solid {{ $providerStatus==='approved'?'fa-circle-check':($providerStatus==='rejected'?'fa-circle-xmark':'fa-clock') }}"></i><span>{{ $providerLabel }}</span>@if($template->provider_checked_at)<small>آخرین بررسی: {{ $template->provider_checked_at->format('Y/m/d H:i') }}</small>@elseif($template->provider_note)<small>{{ $template->provider_note }}</small>@endif</div>
    <div class="sms-template-actions">
      <button type="button" class="btn-pro btn-pro-ghost sms-edit-template" data-template-id="{{ $template->id }}"><i class="fa-solid fa-pen"></i> ویرایش</button>
      <form method="POST" action="{{ route('admin.sms.templates.toggle',$template) }}">@csrf @method('PATCH')<button class="btn-pro btn-pro-ghost">{{ $template->is_active?'غیرفعال':'فعال' }}</button></form>
      @unless($template->is_default)<form method="POST" action="{{ route('admin.sms.templates.default',$template) }}">@csrf @method('PATCH')<button class="btn-pro btn-pro-ghost">انتخاب پیش‌فرض</button></form>@endunless
      <div class="sms-test-control">
        <button type="button" class="btn-pro {{ $providerStatus==='approved'?'btn-pro-primary':'btn-pro-ghost' }} sms-test-open" data-id="{{ $template->id }}" data-name="{{ $template->name }}"><i class="fa-solid fa-flask-vial"></i> ارسال تست</button>
        @if($template->last_test_status === 'success')
          <span class="sms-test-state is-success" title="آخرین تست با موفقیت ارسال شده است{{ $template->last_tested_at ? ' — '.$template->last_tested_at->format('Y/m/d H:i') : '' }}"><i class="fa-solid fa-circle-check"></i></span>
        @elseif(in_array($template->last_test_status, ['failed', 'partial'], true))
          <span class="sms-test-state is-failed" title="آخرین تست کامل ارسال نشده است"><i class="fa-solid fa-circle-exclamation"></i></span>
        @endif
      </div>
      <form method="POST" action="{{ route('admin.sms.templates.destroy',$template) }}" onsubmit="return confirm('این الگو حذف شود؟')">@csrf @method('DELETE')<button class="btn-pro btn-pro-danger" aria-label="حذف"><i class="fa-solid fa-trash"></i></button></form>
    </div>
  </article>
@empty
  <div class="content-card empty-state"><i class="fa-regular fa-message"></i><strong>برای این رویداد الگویی وجود ندارد</strong><span>با دکمه «الگوی جدید» اولین نمونه را اضافه کنید.</span></div>
@endforelse
</div>
<div class="content-card empty-state" id="sms-filter-empty" hidden><i class="fa-regular fa-folder-open"></i><strong>در این دسته‌بندی الگویی وجود ندارد</strong><span>از دکمه «الگوی جدید» برای اضافه‌کردن نمونه استفاده کنید.</span></div>
