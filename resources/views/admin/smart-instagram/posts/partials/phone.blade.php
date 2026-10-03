{{-- موکاپ آیفون ۱۸ پرومکس — پیش‌نمایش زنده‌ی کامنت و دایرکت. محتوا با smart-instagram-posts.js از وضعیت فرم (یا data-sip-state) ساخته می‌شود. --}}
<div class="sip-aside" data-sip-preview @isset($state) data-sip-state='@json($state)' @endisset>
  <div class="sip-device-tabs" role="tablist" aria-label="پیش‌نمایش">
    <button type="button" class="is-active" data-ph-tab="comment" role="tab" aria-selected="true"><i class="fa-regular fa-comment"></i> کامنت</button>
    <button type="button" data-ph-tab="dm" role="tab" aria-selected="false"><i class="fa-regular fa-paper-plane"></i> دایرکت</button>
  </div>

  <div class="sip-phone" aria-label="پیش‌نمایش روی آیفون ۱۸ پرومکس">
    <div class="sip-screen">
      <div class="sip-island" aria-hidden="true"></div>
      <div class="sip-statusbar" aria-hidden="true">
        <span>9:41</span>
        <span><i class="fa-solid fa-signal"></i><i class="fa-solid fa-wifi"></i><i class="fa-solid fa-battery-three-quarters"></i></span>
      </div>
      <div class="sip-app" data-ph-app aria-live="polite"></div>
      <div class="sip-home" aria-hidden="true"></div>
    </div>
  </div>

  <label class="sip-follow-sim" data-ph-follow-sim>
    <span class="sip-switch"><input type="checkbox" data-ph-notfollow><span class="sip-switch-ui"></span></span>
    شبیه‌سازی کاربری که هنوز فالو نکرده
  </label>
  <p class="sip-phone-note">پیش‌نمایش با نام نمونه‌ی «محسن» ساخته می‌شود؛ ظاهر نهایی در اینستاگرام ممکن است کمی متفاوت باشد.</p>
</div>
