@php
  $videoSource = $product ?? $duplicateFrom ?? null;
  $videoConfig = $videoSource?->videoConfiguration() ?? [];
  $videoInput = old('video_config', []);
  $videoValue = fn (string $key, mixed $fallback = null) => data_get($videoInput, $key, data_get($videoConfig, $key, $fallback));
  $selectedRelations = collect(old('video_related_photo_product_ids', $videoSource?->sourcePhotoProducts?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5" data-video-admin-settings>
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-start justify-between gap-3 flex-wrap">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-film text-[var(--accent)]"></i> تنظیمات اختصاصی ویدیو</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">همان ساختار محصول عکس حفظ شده و این گزینه‌ها رفتار تبدیل عکس به ویدیو را کامل می‌کنند.</div>
    </div>
    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full bg-[var(--green)]/10 text-[var(--green)] border border-[var(--green)]/25"><i class="fa-solid fa-check"></i> ویدیو</span>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <label class="flex flex-col gap-1.5">
      <span class="text-xs font-semibold text-[var(--text2)]">سناریوی ساخت</span>
      <select name="video_config[workflow]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
        @foreach(['image_to_video' => 'عکس به ویدیو', 'text_to_video' => 'متن به ویدیو', 'video_to_video' => 'ویدیو به ویدیو'] as $key => $label)
          <option value="{{ $key }}" @selected($videoValue('workflow', 'image_to_video') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label class="flex flex-col gap-1.5">
      <span class="text-xs font-semibold text-[var(--text2)]">نیاز به تصویر ورودی</span>
      <select name="video_config[face_profile_mode]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
        @foreach(['disabled' => 'اختیاری', 'optional' => 'اختیاری با حفظ هویت', 'required' => 'الزامی'] as $key => $label)
          <option value="{{ $key }}" @selected($videoValue('face_profile_mode', 'optional') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
  </div>

  <div class="mt-4 rounded-xl border border-[var(--accent)]/30 bg-[var(--accent)]/5 p-3.5">
    <label class="flex items-start gap-3 cursor-pointer">
      <input type="hidden" name="video_preserve_source_aspect_ratio" value="0">
      <input type="checkbox" name="video_preserve_source_aspect_ratio" value="1" @checked(old('video_preserve_source_aspect_ratio', $videoValue('preserve_source_aspect_ratio', false))) class="mt-1 accent-[var(--accent)]">
      <span><b class="block text-[11.5px] text-[var(--text)]">حفظ دقیق نسبت تصویر عکس ورودی</b><small class="block text-[10px] leading-6 text-[var(--text3)]">قاب عکس نه برش می‌خورد، نه کشیده می‌شود و نه با نوار خالی تغییر می‌کند؛ مدل خروجی نسبت اصلی همان تصویر را دریافت می‌کند.</small></span>
    </label>
  </div>

  <div class="mt-4 rounded-xl border border-[var(--green)]/25 bg-[var(--green)]/5 p-3"><div class="text-[11px] font-bold text-[var(--text2)] mb-1">مصرف اعتبار سه سطحی</div><div class="text-[10px] leading-6 text-[var(--text3)]">عددهای «استاندارد»، «حرفه‌ای» و «بهترین خروجی» در کارت مشترک «مصرف اعتبار محصول» همین گام تنظیم می‌شوند و برای کیفیت‌های ویدیو هم منبع قطعی محاسبه هستند.</div></div>

  <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
    @foreach([4, 6, 8, 10, 12, 15] as $duration)
      <label class="flex items-center justify-between gap-2 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer">
        <span class="flex items-center gap-2 text-[11px] text-[var(--text2)]"><input type="checkbox" name="video_config[durations][]" value="{{ $duration }}" @checked(in_array($duration, array_map('intval', (array) $videoValue('durations', [4, 6, 8])), true)) class="accent-[var(--accent)]"><b>{{ $duration }} ثانیه</b></span>
        <input type="number" name="video_config[credit_costs_by_duration][{{ $duration }}]" value="{{ old('video_config.credit_costs_by_duration.'.$duration, data_get($videoConfig, 'credit_costs_by_duration.'.$duration)) }}" min="0" max="1000000" class="w-20 h-8 px-2 bg-[var(--s2)] border border-[var(--b1)] rounded-md text-[10px] text-[var(--text)] ltr text-left" aria-label="اعتبار {{ $duration }} ثانیه">
      </label>
    @endforeach
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mt-3.5">
    <label class="flex flex-col gap-1.5"><span class="text-xs font-semibold text-[var(--text2)]">مدت پیش‌فرض</span><select name="video_config[default_duration]" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">@foreach([4,6,8,10,12,15] as $duration)<option value="{{ $duration }}" @selected((int) $videoValue('default_duration', 6) === $duration)>{{ $duration }} ثانیه</option>@endforeach</select></label>
    <label class="flex flex-col gap-1.5"><span class="text-xs font-semibold text-[var(--text2)]">نرخ فریم</span><input type="number" name="video_config[fps]" min="12" max="60" value="{{ $videoValue('fps', 24) }}" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left"></label>
  </div>

  <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <div><div class="text-[11px] font-bold text-[var(--text2)] mb-2">نسبت‌های پشتیبانی‌شده</div><div class="grid grid-cols-3 gap-2">@foreach(\App\Services\VideoProductConfigService::ASPECT_RATIOS as $ratio)<label class="flex items-center gap-1.5 p-2 rounded-lg bg-[var(--s1)] border border-[var(--b1)] text-[10px] text-[var(--text2)]"><input type="checkbox" name="video_config[aspect_ratios][]" value="{{ $ratio }}" @checked(in_array($ratio, (array) $videoValue('aspect_ratios', ['16:9', '9:16', '1:1']), true)) class="accent-[var(--accent)]"><span dir="ltr">{{ $ratio }}</span></label>@endforeach</div></div>
    <div><div class="text-[11px] font-bold text-[var(--text2)] mb-2">کیفیت‌های پشتیبانی‌شده</div><div class="grid grid-cols-2 gap-2">@foreach(\App\Services\VideoProductConfigService::RESOLUTIONS as $resolution)<label class="flex items-center gap-1.5 p-2 rounded-lg bg-[var(--s1)] border border-[var(--b1)] text-[10px] text-[var(--text2)]"><input type="checkbox" name="video_config[resolutions][]" value="{{ $resolution }}" @checked(in_array($resolution, (array) $videoValue('resolutions', ['480p', '720p', '1080p']), true)) class="accent-[var(--accent)]"><span dir="ltr">{{ $resolution }}</span></label>@endforeach</div></div>
  </div>


  <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
    <label class="flex items-center gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg"><input type="hidden" name="video_prompt_enhance" value="0"><input type="checkbox" name="video_prompt_enhance" value="1" @checked(old('video_prompt_enhance', $videoValue('prompt_enhance', true))) class="accent-[var(--accent)]"><span class="text-[10.5px] text-[var(--text2)]">بهبود هوشمند پرامپت</span></label>
    <label class="flex items-center gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg"><input type="hidden" name="video_audio_allowed" value="0"><input type="checkbox" name="video_audio_allowed" value="1" @checked(old('video_audio_allowed', $videoValue('audio_allowed', false))) class="accent-[var(--accent)]"><span class="text-[10.5px] text-[var(--text2)]">امکان صدای همگام</span></label>
    <label class="flex items-center gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg"><input type="hidden" name="video_allow_promotional_credits" value="0"><input type="checkbox" name="video_allow_promotional_credits" value="1" @checked(old('video_allow_promotional_credits', $videoValue('allow_promotional_credits', false))) class="accent-[var(--accent)]"><span class="text-[10.5px] text-[var(--text2)]">اعتبار هدیه</span></label>
  </div>

  <div class="mt-4 border-t border-[var(--b1)] pt-4">
    <div class="text-[11px] font-bold text-[var(--text2)] mb-1">اتصال به محصولات عکس</div>
    <div class="text-[10px] text-[var(--text3)] mb-2">بعد از ساخت خروجی عکس، کاربر دکمه «تبدیل این عکس به ویدیو» را می‌بیند و تصویر همان خروجی بدون آپلود مجدد وارد می‌شود.</div>
    <select name="video_related_photo_product_ids[]" multiple size="5" class="w-full bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
      @forelse($relatedPhotoProducts ?? [] as $photoProduct)
        <option value="{{ $photoProduct->id }}" @selected(in_array((int) $photoProduct->id, $selectedRelations, true))>{{ $photoProduct->name_fa }} — {{ $photoProduct->product_code ?: $photoProduct->id }}</option>
      @empty
        <option disabled>هنوز محصول عکس فعالی برای اتصال وجود ندارد</option>
      @endforelse
    </select>
  </div>
</section>
