{{-- یک ردیف دکمه‌ی کارت دایرکت: $i (اندیس یا __i__ برای قالب)، $b، $presets، $unavailableButtons --}}
@php($bType = ($b['type'] ?? 'web_url') === 'postback' ? 'postback' : 'web_url')
<div class="sip-btn-row" data-btn-row>
  <div class="si-field">
    <label>نوع دکمه</label>
    <select class="input-pro" name="settings[card][buttons][{{ $i }}][preset]" data-btn-preset aria-label="نوع دکمه">
      @foreach($presets as $key => $preset)
        <option value="{{ $key }}" data-type="{{ $preset['type'] }}" data-label="{{ $preset['label'] }}" @selected(($b['preset'] ?? '') === $key)>{{ $preset['label'] }} — {{ $preset['type'] === 'postback' ? 'پاسخ سریع' : 'لینک' }}</option>
      @endforeach
      <optgroup label="پشتیبانی‌نشده در اینستاگرام">
        @foreach($unavailableButtons as $key => $label)<option value="" disabled>{{ $label }}</option>@endforeach
      </optgroup>
    </select>
    <input type="hidden" name="settings[card][buttons][{{ $i }}][type]" value="{{ $bType }}" data-btn-type>
  </div>
  <div class="sip-btn-fields">
    <div class="si-field"><label>متن دکمه</label><input class="input-pro" name="settings[card][buttons][{{ $i }}][label]" maxlength="20" data-btn-label data-counter="20" value="{{ $b['label'] ?? '' }}"><div class="sip-counter" data-counter-out></div></div>
    <div class="si-field" data-btn-url-wrap @if($bType === 'postback') hidden @endif><label>آدرس لینک</label><input class="input-pro si-ltr" name="settings[card][buttons][{{ $i }}][url]" maxlength="1000" data-btn-url value="{{ $b['url'] ?? '' }}" placeholder="خالی = صفحه‌ی محصول انتخاب‌شده"></div>
    <div class="si-field" data-btn-reply-wrap @if($bType !== 'postback') hidden @endif><label>پاسخ پس از زدن دکمه</label><input class="input-pro" name="settings[card][buttons][{{ $i }}][reply_text]" maxlength="900" data-btn-reply value="{{ $b['reply_text'] ?? '' }}" placeholder="متنی که در دایرکت ارسال می‌شود"></div>
  </div>
  <div class="sip-btn-tools">
    <button type="button" class="icon-action-btn" data-btn-up aria-label="بالا"><i class="fa-solid fa-angle-up"></i></button>
    <button type="button" class="icon-action-btn" data-btn-down aria-label="پایین"><i class="fa-solid fa-angle-down"></i></button>
    <button type="button" class="icon-action-btn" data-btn-remove aria-label="حذف"><i class="fa-solid fa-trash"></i></button>
  </div>
</div>
