<div class="sms-modal" id="template-modal" hidden><div class="sms-modal-card content-card"><div class="sms-modal-head"><div><h2 id="template-modal-title">الگوی جدید</h2><p>متن و متغیرهای پیام را تنظیم کنید.</p></div><button type="button" class="sms-close-btn sms-modal-close" aria-label="بستن پنجره"><i class="fa-solid fa-xmark"></i></button></div>
  <form method="POST" id="template-form" action="{{ route('admin.sms.templates.store') }}" class="sms-form">@csrf <input type="hidden" name="_method" id="template-method" value="POST">
    <div class="sms-row"><div class="sms-field"><label>رویداد سیستم</label><select name="event_key" id="template-event" class="input-pro" required>@foreach($events as $key=>$item)<option value="{{ $key }}">{{ $item['group'] }} — {{ $item['label'] }}</option>@endforeach</select></div><div class="sms-field"><label>نام نمونه</label><input name="name" id="template-name" class="input-pro" maxlength="100" required placeholder="مثلاً رسمی و کوتاه"></div></div>
    <div class="sms-row"><div class="sms-field"><label>روش ارسال</label><select name="provider_method" id="template-provider-method" class="input-pro" required><option value="shared">خط خدماتی اشتراکی (پیشنهادی)</option><option value="simple">خط اختصاصی / ساده</option></select></div><div class="sms-field" id="template-body-id-field"><label>شناسه الگو در ملی‌پیامک</label><input name="provider_template_id" id="template-body-id" class="input-pro" maxlength="100" placeholder="Body ID الگوی تأییدشده"></div></div>
    <div class="sms-field" id="template-provider-variables-field"><label>ترتیب متغیرهای الگوی خدماتی</label><div id="template-provider-variables" class="sms-variable-order"></div><small>متغیرها را به همان ترتیبی انتخاب کنید که در پنل ملی‌پیامک تعریف شده‌اند.</small></div>
    <div class="sms-field"><label>متن پیامک</label><textarea name="body" id="template-body" class="input-pro" maxlength="1000" required></textarea><div class="sms-counter"><span id="sms-char-count">۰ کاراکتر</span><span id="sms-part-count">۰ بخش</span><span id="sms-encoding">یونیکد</span></div></div>
    <div class="sms-variable-box"><b>متغیرهای قابل استفاده</b><div id="sms-variable-list"></div><small>روی هر متغیر کلیک کنید تا به متن اضافه شود.</small></div>
    <div class="sms-preview"><b>پیش‌نمایش با داده نمونه</b><p id="sms-preview-text">—</p></div>
    <div class="sms-row"><label class="sms-toggle"><input type="checkbox" name="is_active" id="template-active" value="1" checked> الگو فعال باشد</label><label class="sms-toggle"><input type="checkbox" name="is_default" id="template-default" value="1"> الگوی پیش‌فرض این رویداد باشد</label></div>
    <div class="sms-actions"><button type="button" class="btn-pro btn-pro-ghost sms-modal-close"><i class="fa-solid fa-arrow-right"></i> بازگشت</button><button class="btn-pro btn-pro-primary">ذخیره الگو</button></div>
  </form>
</div></div>

<div class="sms-modal" id="test-modal" hidden><div class="sms-modal-card sms-modal-sm content-card"><div class="sms-modal-head"><div><h2>ارسال پیامک تست</h2><p id="test-template-name"></p></div><button type="button" class="sms-close-btn sms-test-close" aria-label="بستن پنجره"><i class="fa-solid fa-xmark"></i></button></div>
  <form method="POST" id="test-form" class="sms-form">@csrf
    <div class="sms-field"><label>ارسال برای مدیران سایت</label><div class="sms-admin-test-list">
      @foreach($admins as $admin)
        @php $hasPhone=preg_match('/^09\d{9}$/',(string)$admin->phone); @endphp
        <label class="sms-admin-test-item {{ !$hasPhone?'is-disabled':'' }}">
          <input type="checkbox" name="admin_ids[]" value="{{ $admin->id }}" {{ in_array($admin->id,$selectedTestAdminIds,true)&&$hasPhone?'checked':'' }} {{ !$hasPhone?'disabled':'' }}>
          <span class="sms-admin-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <span><b>{{ $admin->name }}</b><small dir="ltr">{{ $hasPhone?$admin->phone:'شماره ثبت نشده' }}</small></span>
          @if($hasPhone)<i class="fa-regular fa-square-check"></i>@else<i class="fa-solid fa-triangle-exclamation"></i>@endif
        </label>
      @endforeach
    </div></div>
    <div class="sms-field"><label>شماره دلخواه <small>(اختیاری)</small></label><input class="input-pro" name="phone" dir="ltr" value="{{ $settings['admin_test_phone'] ?? '' }}" placeholder="09123456789"></div>
    <div class="sms-guide-note"><i class="fa-solid fa-circle-info"></i><span>می‌توانید هم‌زمان چند مدیر را انتخاب کنید. متغیرها با داده نمونه پر می‌شوند و نتیجه واقعی برای شماره‌های انتخاب‌شده ارسال می‌شود.</span></div>
    <div class="sms-actions"><button type="button" class="btn-pro btn-pro-ghost sms-test-close">انصراف</button><button class="btn-pro btn-pro-primary"><i class="fa-solid fa-paper-plane"></i> ارسال تست</button></div>
  </form>
</div></div>
