<div class="sms-template-grid">
@forelse($templates as $template)
  @php $chars=mb_strlen($template->body); $parts=$chars<=70?1:(int)ceil($chars/67); @endphp
  <article class="content-card sms-template-card {{ !$template->is_active ? 'is-off' : '' }}">
    <div class="sms-template-top"><div><div class="sms-template-event">{{ $template->eventLabel() }}</div><h3>{{ $template->name }}</h3></div><div class="sms-template-badges">@if($template->is_default)<span class="badge-pro badge-pro-primary">پیش‌فرض</span>@endif<span class="sms-state-icon {{ $template->is_active?'is-active':'is-inactive' }}" title="{{ $template->is_active?'الگوی فعال':'الگوی غیرفعال' }}"><i class="fa-solid {{ $template->is_active?'fa-circle-check':'fa-circle-xmark' }}"></i></span><span class="sms-vars-icon" title="متغیرها: {{ implode('، ',config('sms_events.events.'.$template->event_key.'.variables',[])) }}"><i class="fa-solid fa-brackets-curly"></i></span></div></div>
    <p class="sms-template-body">{{ $template->body }}</p>
    <div class="sms-template-meta"><span>{{ $chars }} کاراکتر</span><span>{{ $parts }} بخش پیامک</span><span>{{ number_format($template->sent_count) }} ارسال</span></div>
    <div class="sms-template-actions">
      <button type="button" class="btn-pro btn-pro-ghost sms-edit-template" data-template-id="{{ $template->id }}"><i class="fa-solid fa-pen"></i> ویرایش</button>
      <form method="POST" action="{{ route('admin.sms.templates.toggle',$template) }}">@csrf @method('PATCH')<button class="btn-pro btn-pro-ghost">{{ $template->is_active?'غیرفعال':'فعال' }}</button></form>
      @unless($template->is_default)<form method="POST" action="{{ route('admin.sms.templates.default',$template) }}">@csrf @method('PATCH')<button class="btn-pro btn-pro-ghost">انتخاب پیش‌فرض</button></form>@endunless
      <button type="button" class="btn-pro btn-pro-primary sms-test-open" data-id="{{ $template->id }}" data-name="{{ $template->name }}"><i class="fa-solid fa-mobile-screen"></i> تست</button>
      <form method="POST" action="{{ route('admin.sms.templates.destroy',$template) }}" onsubmit="return confirm('این الگو حذف شود؟')">@csrf @method('DELETE')<button class="btn-pro btn-pro-danger" aria-label="حذف"><i class="fa-solid fa-trash"></i></button></form>
    </div>
  </article>
@empty
  <div class="content-card empty-state"><i class="fa-regular fa-message"></i><strong>برای این رویداد الگویی وجود ندارد</strong><span>با دکمه «الگوی جدید» اولین نمونه را اضافه کنید.</span></div>
@endforelse
</div>
