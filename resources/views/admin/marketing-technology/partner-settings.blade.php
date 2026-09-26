@extends('layouts.admin')

@section('title', 'تنظیمات مراحل همکاری — تکنولوژی مارکتینگ')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content sp-settings-page flex-1 overflow-y-auto" id="content">
    @if(session('success'))
      <div class="mt-alert mt-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
      <div class="mt-alert mt-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>@foreach($errors->all() as $error){{ $error }}@if(!$loop->last)، @endif @endforeach</span></div>
    @endif

    <div class="mt-page-head">
      <div>
        <div class="mt-eyebrow">مسیر همکاران فروش</div>
        <h1>تنظیمات مراحل همکاری</h1>
        <p>متن‌ها و قواعد مسیر را از اینجا مدیریت کن. تغییر هر مرحله مستقیماً در راهنمای برد و تسک‌های بعدی نمایش داده می‌شود.</p>
      </div>
      <div class="mt-head-actions">
        <span class="sp-manual-badge"><i class="fa-solid fa-pen-to-square"></i> تنظیمات قابل ویرایش</span>
        <a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.partners') }}"><i class="fa-solid fa-arrow-right"></i> بازگشت به برد</a>
      </div>
    </div>

    @if(!$ready)
      <section class="mt-card mt-empty-section"><span class="mt-empty-icon"><i class="fa-solid fa-sliders"></i></span><h2>تنظیمات هنوز فعال نشده است</h2><p>ابتدا مهاجرت پایگاه داده اجرا شود تا متن و قواعد مرحله‌ها قابل ویرایش شوند.</p><span class="mt-badge mt-badge-warn">بزودی</span></section>
    @else
      <section class="mt-card mt-card-pad sp-settings-intro">
        <div class="sp-settings-intro-icon"><i class="fa-solid fa-route"></i></div>
        <div><strong>قانون ساده مسیر</strong><p>در هر مرحله فقط یک اقدام اصلی داشته باشیم. نیروی فروش باید بداند الان چه پیامی بدهد، چه چیزی ثبت کند و با چه شرطی کارت را جلو ببرد.</p></div>
        <div class="sp-settings-legend"><span><i class="fa-solid fa-microphone"></i> وویس</span><span><i class="fa-solid fa-message"></i> پیام</span><span><i class="fa-solid fa-clock"></i> پیگیری</span></div>
      </section>

      @php($messageTypeLabels = ['none' => 'بدون پیام', 'voice' => 'وویس', 'message' => 'پیام', 'both' => 'وویس یا پیام'])
      <section class="mt-card mt-card-pad sp-settings-flow" aria-label="نقشه کامل مسیر همکاری">
        <div class="sp-settings-flow-head"><div><div class="mt-eyebrow">سناریوی صفر تا صد همکاری</div><h2>درخت اجرای مسیر همکار فروش</h2><p>از لحظه پیدا شدن سرنخ تا همکاری فعال و تکرار فروش؛ هر گام به تنظیمات کامل همان مرحله پایین صفحه وصل است.</p></div><span class="mt-badge">{{ $stages->count() }} گام</span></div>
        <div class="sp-settings-flow-tree">
          @foreach($stages as $stage)
            <a class="sp-settings-flow-node" href="#stage-setting-{{ $stage->stage }}"><span>{{ $stage->stage }}</span><span class="sp-settings-flow-copy"><strong>{{ $stage->title }}</strong><small>{{ $stage->task ?: $stage->description }}</small></span><b>{{ $messageTypeLabels[$stage->message_type] ?? $stage->message_type }}</b></a>
          @endforeach
        </div>
      </section>

      <section class="sp-settings-grid">
        @foreach($stages as $stage)
          <article class="mt-card sp-stage-setting-card" id="stage-setting-{{ $stage->stage }}">
            <div class="sp-stage-setting-head">
              <div class="sp-stage-setting-number">{{ $stage->stage }}</div>
              <div><h2>{{ $stage->title }}</h2><p>{{ $stage->description }}</p></div>
              <span class="mt-badge {{ $stage->is_active ? 'mt-badge-success' : 'mt-badge-warn' }}">{{ $stage->is_active ? 'فعال' : 'متوقف' }}</span>
            </div>
            <form method="POST" action="{{ route('admin.marketing-technology.partners.settings.update', $stage) }}" class="sp-stage-setting-form" data-ajax-form>
              @csrf
              @method('PUT')
              <div class="sp-settings-basic-grid">
                <div class="mt-field"><label>عنوان مرحله</label><input class="mt-input" name="title" value="{{ old('title', $stage->title) }}" required></div>
                <div class="mt-field"><label>توضیح کوتاه</label><input class="mt-input" name="description" value="{{ old('description', $stage->description) }}"></div>
                <div class="mt-field"><label>نوع ارتباط اصلی</label><select class="mt-input" name="message_type">@foreach($messageTypes as $type => $label)<option value="{{ $type }}" @selected(old('message_type', $stage->message_type) === $type)>{{ $label }}</option>@endforeach</select></div>
                <div class="mt-field"><label>زمان پیش‌فرض پیگیری، ساعت</label><input class="mt-input" name="default_follow_up_hours" type="number" min="0" max="8760" value="{{ old('default_follow_up_hours', $stage->default_follow_up_hours) }}" placeholder="خالی = بدون زمان‌بندی"></div>
                <div class="mt-field"><label>حداکثر پیگیری خودکار</label><input class="mt-input" name="max_follow_ups" type="number" min="0" max="20" value="{{ old('max_follow_ups', $stage->max_follow_ups) }}"></div>
                <div class="sp-settings-active"><input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $stage->is_active))> این مرحله در مسیر فعال باشد</label></div>
              </div>
              <div class="sp-settings-text-grid">
                <div class="mt-field"><label>هدف مرحله</label><textarea class="mt-input mt-textarea" name="goal">{{ old('goal', $stage->goal) }}</textarea></div>
                <div class="mt-field"><label>تسک اصلی نیروی فروش</label><textarea class="mt-input mt-textarea" name="task">{{ old('task', $stage->task) }}</textarea></div>
                <div class="mt-field sp-settings-wide"><label>اسکریپت پیشنهادی پیام یا وویس</label><textarea class="mt-input sp-script-textarea" name="script">{{ old('script', $stage->script) }}</textarea><small>این متن پیشنهاد است؛ ارسال اول همچنان دستی انجام می‌شود.</small></div>
                <div class="mt-field"><label>توضیح زمان‌بندی</label><textarea class="mt-input mt-textarea" name="follow_up">{{ old('follow_up', $stage->follow_up) }}</textarea></div>
                <div class="mt-field"><label>شرط عبور به مرحله بعد</label><textarea class="mt-input mt-textarea" name="advance_when">{{ old('advance_when', $stage->advance_when) }}</textarea></div>
                <div class="mt-field"><label>شرط توقف یا خروج</label><textarea class="mt-input mt-textarea" name="stop_when">{{ old('stop_when', $stage->stop_when) }}</textarea></div>
              </div>
              <div class="sp-stage-setting-actions"><span><i class="fa-solid fa-circle-info"></i> آخرین تغییر: {{ $stage->updated_at?->format('Y/m/d H:i') ?? 'ثبت نشده' }}</span><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره مرحله {{ $stage->stage }}</button></div>
            </form>
          </article>
        @endforeach
      </section>
    @endif
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  document.querySelectorAll('[data-ajax-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) })
        .then(function (response) { return response.json().then(function (payload) { if (!response.ok) throw new Error(payload.message || 'ذخیره‌سازی انجام نشد.'); return payload; }); })
        .then(function (payload) {
          const notice = document.createElement('div');
          notice.className = 'mt-alert mt-alert-success';
          notice.innerHTML = '<i class="fa-solid fa-circle-check"></i><span></span>';
          notice.querySelector('span').textContent = payload.message || 'ذخیره شد.';
          document.querySelector('.sp-settings-page')?.prepend(notice);
          window.setTimeout(function () { notice.remove(); }, 3500);
        })
        .catch(function (error) {
          const notice = document.createElement('div');
          notice.className = 'mt-alert mt-alert-danger';
          notice.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span></span>';
          notice.querySelector('span').textContent = error.message;
          document.querySelector('.sp-settings-page')?.prepend(notice);
        })
        .finally(function () { if (button) button.disabled = false; });
    });
  });
})();
</script>
@endsection
