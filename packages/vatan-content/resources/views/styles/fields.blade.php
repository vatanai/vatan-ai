<div class="cf-grid cf-g3">
  <label class="cf-field">نام فارسی<input name="name_fa" value="{{ $s->name_fa ?? '' }}" required></label>
  <label class="cf-field">نام انگلیسی<input name="name_en" value="{{ $s->name_en ?? '' }}" dir="ltr"></label>
  <label class="cf-field"><span>قالب رندر @include('cf::partials.help', ['k' => 'styles.template'])</span><select name="template">
    @foreach(['punch' => 'پانچ تضادی', 'editorial' => 'ادیتوریال روشن', 'kinetic' => 'تایپوگرافی ضرب‌آهنگی', 'techgrid' => 'گرید فنی', 'stage' => 'کارت روی صحنه', 'tactile' => 'میز کار لمسی'] as $k => $l)
      <option value="{{ $k }}" @selected(($s->template ?? 'punch') === $k)>{{ $l }}</option>@endforeach</select></label>
</div>
<label class="cf-field">توضیح قابل‌فهم<textarea name="description">{{ $s->description ?? '' }}</textarea></label>
<div class="cf-grid cf-g2">
  <label class="cf-field">هدف‌های مناسب (با ، جدا)<input name="goals_text" value="{{ implode('، ', $s->goals ?? []) }}"></label>
  <label class="cf-field">صنف‌های مناسب (با ، جدا)<input name="sectors_text" value="{{ implode('، ', $s->sectors ?? []) }}"></label>
</div>
<div class="cf-grid cf-g3">
  <label class="cf-field">مدت پیشنهادی<input name="duration" value="{{ $s->duration ?? '' }}"></label>
  <label class="cf-field">نسبت تصویر<input name="aspect" value="{{ $s->aspect ?? '9:16' }}"></label>
  <label class="cf-field">تعداد شات<input name="shots" value="{{ $s->shots ?? '' }}"></label>
  <label class="cf-field">حرکت و افکت<input name="motion" value="{{ $s->motion ?? '' }}"></label>
  <label class="cf-field">متن روی تصویر<input name="text_style" value="{{ $s->text_style ?? '' }}"></label>
  <label class="cf-field">موسیقی مناسب<input name="music_hint" value="{{ $s->music_hint ?? '' }}"></label>
  <label class="cf-field">خانواده‌ی هوک<input name="hook_family" value="{{ $s->hook_family ?? '' }}"></label>
  <label class="cf-field">خانواده‌ی دعوت<input name="cta_family" value="{{ $s->cta_family ?? '' }}"></label>
  <label class="cf-field">موتور ساخت<input name="engine" value="{{ $s->engine ?? '' }}"></label>
</div>
<label class="cf-field">دستور صحنه‌ها (هر خط یک قدم)<textarea name="recipe_text">{{ implode("\n", $s->recipe ?? []) }}</textarea></label>
