<div class="v2-modern-step-one" id="v2-step-one-modern">
  <section class="v2-modern-font-field" aria-labelledby="v2-modern-font-title">
    <label id="v2-modern-font-title">فونت نوشته‌های ویدیو</label>
    <div class="v2-modern-font-grid">
      @foreach($fonts->take(6) as $font)
        <div class="v2-modern-font">
          <input id="v2-modern-font-{{ $font->slug }}" type="radio" name="font_family" value="{{ $font->slug }}" @checked(($settings->font_family ?? 'B_Yekan') === $font->slug)>
          <label for="v2-modern-font-{{ $font->slug }}" style="font-family:'{{ $font->slug }}' !important">متن نمونه هوگ<br>برای ساخت ویدیو</label>
        </div>
      @endforeach
    </div>
  </section>

  <section class="v2-modern-hook-panel" aria-labelledby="v2-modern-hook-title">
    <div class="v2-modern-hook-head"><strong id="v2-modern-hook-title">تنظیمات هوک اول ویدیو</strong><span class="v2-note">پیش‌نمایش و خروجی از یک تنظیم استفاده می‌کنند.</span></div>
    <div class="v2-modern-hook-layout">
      <div class="v2-modern-hook-controls">
        <div class="v2-modern-hook-choice">
          <div class="v2-modern-hook-choice-head"><strong>متن هوک را انتخاب کنید:</strong><div class="v2-inline-actions"><button class="v2-mini-btn" type="button" id="v2-modern-open-hook-prompt"><i class="fa-solid fa-sliders"></i> تنظیم پرامپت</button><button class="v2-mini-btn" type="button" id="v2-modern-regenerate-hook"><i class="fa-solid fa-wand-magic-sparkles"></i> ساخت هوک</button></div></div>
          <div class="v2-hook-grid" id="v2-modern-hook-grid">
            @forelse($hookInspirations->take(3) as $hook)
              <label class="v2-hook-card {{ (filled($settings->hook_text ?? '') ? ($settings->hook_text ?? '') === $hook->hook_text : $loop->first) ? 'is-selected' : '' }}"><input type="radio" name="v2_modern_hook_choice" value="{{ $hook->hook_text }}" @checked(filled($settings->hook_text ?? '') ? ($settings->hook_text ?? '') === $hook->hook_text : $loop->first)><strong>{{ $hook->title }}</strong><p>{{ $hook->hook_text }}</p></label>
            @empty
              @foreach(['قبل از انتخاب، این نکته را ببین','یک انتخاب آگاهانه‌تر داشته باش','جزئیات محصول را همین حالا ببین'] as $index => $hookText)
                <label class="v2-hook-card {{ $index === 0 ? 'is-selected' : '' }}"><input type="radio" name="v2_modern_hook_choice" value="{{ $hookText }}" @checked($index === 0)><strong>گزینه {{ $index + 1 }}</strong><p>{{ $hookText }}</p></label>
              @endforeach
            @endforelse
          </div>
          <div class="v2-field" style="margin-top:10px"><label for="v2-modern-hook-manual">متن هوک</label><input class="v2-input" id="v2-modern-hook-manual" name="hook_text" value="{{ old('hook_text', $settings->hook_text ?? '') }}" placeholder="متن هوک را برای پیش‌نمایش و ساخت ویرایش کنید."></div>
        </div>

        <div class="v2-modern-hook-styles">
          <div class="v2-modern-hook-color-groups">
            @foreach(['background' => 'رنگ پس‌زمینه هوک', 'text' => 'رنگ متن'] as $target => $label)
              @php
                $defaultColorKey = $target === 'background' ? 'primary' : 'light';
                $hasDefaultColor = collect($hookColors[$target])->contains('key', $defaultColorKey);
              @endphp
              <div class="v2-modern-hook-color-group"><strong>{{ $label }}</strong><div class="v2-modern-color-list" data-v2-modern-color-list="{{ $target }}">
                @foreach($hookColors[$target] as $color)
                  <div class="v2-modern-color"><input type="radio" name="{{ $target === 'background' ? 'hook_background' : 'hook_text_color' }}" id="v2-modern-{{ $target }}-{{ $color['key'] }}" value="{{ $color['key'] }}" data-v2-color-css="{{ $color['css_value'] }}" data-v2-color-render="{{ $color['render_value'] }}" @checked($color['key'] === $defaultColorKey || ($loop->first && ! $hasDefaultColor))><label for="v2-modern-{{ $target }}-{{ $color['key'] }}" title="{{ $color['name'] }}" style="--v2-color:{{ $color['css_value'] }}"></label></div>
                @endforeach
                <button class="v2-modern-color-add" type="button" data-v2-modern-open-colors="{{ $target }}" aria-label="مدیریت رنگ‌ها"><i class="fa-solid fa-plus"></i></button>
              </div></div>
            @endforeach
            <div class="v2-modern-hook-weight"><strong>ضخامت فونت</strong><div class="v2-modern-weight-list">@for($weight = 1; $weight <= 5; $weight++)<input id="v2-modern-hook-weight-{{ $weight }}" type="radio" name="hook_font_weight" value="{{ $weight }}" @checked((int) old('hook_font_weight', 3) === $weight)><label for="v2-modern-hook-weight-{{ $weight }}">{{ $weight }}</label>@endfor</div></div>
          </div>
          <div class="v2-modern-hook-ranges">
            <div class="v2-modern-hook-range"><label for="v2-modern-hook-font-size">اندازه متن</label><input id="v2-modern-hook-font-size" type="range" name="hook_font_size" min="20" max="72" step="1" value="36"><output id="v2-modern-hook-font-size-output">۳۶</output></div>
            <div class="v2-modern-hook-range"><label for="v2-modern-hook-scale">زوم متن</label><input id="v2-modern-hook-scale" type="range" name="hook_scale" min="0.7" max="1.5" step="0.05" value="1"><output id="v2-modern-hook-scale-output">۱×</output></div>
            <div class="v2-modern-hook-range"><label for="v2-modern-hook-offset">موقعیت عمودی متن</label><input id="v2-modern-hook-offset" type="range" name="hook_vertical_offset" min="-45" max="45" step="1" value="0"><output id="v2-modern-hook-offset-output">۰٪</output></div>
            <div class="v2-modern-hook-range v2-modern-hook-duration"><label for="v2-modern-hook-duration">زمان هوک</label><input id="v2-modern-hook-duration" type="range" name="hook_duration" min="0.1" max="5" step="0.1" value="2"><output id="v2-modern-hook-duration-output">۲ ثانیه</output><input id="v2-modern-hook-duration-mode" type="hidden" name="hook_duration_mode" value="manual"><button class="v2-mini-btn" id="v2-modern-hook-duration-auto" type="button" aria-pressed="false"><i class="fa-solid fa-wand-magic-sparkles"></i> خودکار</button></div>
          </div>
        </div>

      </div>
      <div><div class="v2-modern-hook-phone" aria-label="پیش‌نمایش زنده هوک"><div class="v2-modern-hook-screen" id="v2-modern-phone-screen"><div class="v2-modern-hook-live-text" id="v2-modern-phone-hook">{{ $settings->hook_text ?: 'یک انتخاب بهتر داشته باش' }}</div></div><img class="v2-modern-hook-frame" src="{{ asset('admin/images/video-studio/iphone-hook-frame.png') }}" alt="قاب آیفون پیش‌نمایش هوک"></div></div>
    </div>
  </section>
  <section class="v2-modern-after-hook">
    <div class="v2-modern-hook-cta"><div class="v2-modern-hook-cta-head"><strong><i class="fa-solid fa-bullhorn"></i> دعوت به اقدام</strong><span class="v2-note">بخش پایانی ویدیو</span></div><div class="v2-grid"><label class="v2-checkbox-label"><input type="checkbox" name="cta_enabled" value="1" checked> نمایش دعوت به اقدام</label><input class="v2-input" name="cta_text" placeholder="متن دعوت به اقدام..."><select class="v2-select" name="cta_background"><option value="primary">پس‌زمینه سبز وطن</option><option value="light">پس‌زمینه سفید</option><option value="dark">پس‌زمینه مشکی</option></select></div></div>
    <div class="v2-modern-hook-command"><label for="v2-modern-text-command">دستور متنی ساخت یا اصلاح</label><textarea class="v2-textarea" id="v2-modern-text-command" name="text_command" rows="3" placeholder="اگر لازم است خروجی بعدی اصلاح شود، دستور را اینجا بنویسید..."></textarea></div>
  </section>
</div>

<div class="v2-modal v2-modern-color-modal" id="v2-modern-color-modal" role="dialog" aria-modal="true" aria-labelledby="v2-modern-color-title">
  <div class="v2-modal-card"><div class="v2-modal-head"><strong id="v2-modern-color-title">مدیریت رنگ هوک</strong><button class="v2-modal-close" type="button" id="v2-modern-close-colors"><i class="fa-solid fa-xmark"></i></button></div><p class="v2-note" id="v2-modern-color-copy">هر رنگِ فهرست، از جمله رنگ‌های اولیه، قابل حذف است و تغییرات فقط برای همین مدیر ذخیره می‌شود.</p><div class="v2-modern-color-manager"><div class="v2-modern-color-manager-list" id="v2-modern-color-manager-list"></div><div class="v2-modern-color-form"><div class="v2-field"><label for="v2-modern-color-name">نام رنگ</label><input class="v2-input" id="v2-modern-color-name" maxlength="80" placeholder="مثلاً آبی تیره"></div><div class="v2-field"><label for="v2-modern-color-value">کد رنگ</label><input class="v2-input" id="v2-modern-color-value" value="#16594F" pattern="^#[0-9A-Fa-f]{6}$" placeholder="#16594F"></div><button class="v2-btn primary" type="button" id="v2-modern-save-color"><i class="fa-solid fa-plus"></i> افزودن رنگ</button></div></div></div>
</div>
