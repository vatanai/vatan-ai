@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $isEdit = $rule->exists;
  $siTitle = $isEdit ? 'ویرایش قانون' : 'قانون اتومیشن تازه';
  $siSubtitle = 'شروع‌کننده → شرط → اقدام. تغییر رفتار قانون، نسخه‌ی تازه می‌سازد و اجراهای قبلی با نسخه‌ی خودشان ثبت می‌مانند.';
  $actions = old('actions', $rule->actions ?: [['type' => 'send_dm']]);
  $conditions = old('conditions', (array) $rule->conditions);
  $guards = old('guards', (array) $rule->guards);
  $leadStatuses = config('smart_instagram.lead_statuses');
@endphp

@section('si-actions')
  <a href="{{ $isEdit ? route('admin.smart-instagram.automations.show', $rule) : route('admin.smart-instagram.automations.index') }}" class="btn-pro btn-pro-ghost">انصراف</a>
@endsection

@section('si-page')
<form method="POST" action="{{ $isEdit ? route('admin.smart-instagram.automations.update', $rule) : route('admin.smart-instagram.automations.store') }}">
  @csrf @if($isEdit) @method('PUT') @endif
  <div class="si-grid-2">
    <div class="si-stack">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-play"></i> ۱. شروع‌کننده</div></div>
        <div class="si-form">
          <div class="si-field is-full"><label for="r-name">نام قانون</label><input id="r-name" name="name" class="input-pro" required maxlength="190" value="{{ old('name', $rule->name) }}" placeholder="مثلاً: قیمت زیر ریلز کیف"></div>
          <div class="si-field"><label for="r-trigger">وقتی…</label><select id="r-trigger" name="trigger" class="input-pro">@foreach($triggers as $k => $l)<option value="{{ $k }}" @selected(old('trigger', $rule->trigger) === $k)>{{ $l }}</option>@endforeach</select></div>
          <div class="si-field"><label for="r-scope">فقط برای محتوای مشخص (اختیاری)</label><input id="r-scope" name="scope_ref" class="input-pro si-ltr" maxlength="120" value="{{ old('scope_ref', $rule->scope_ref) }}" placeholder="شناسه‌ی پست/ریلز/استوری/تبلیغ"><span class="si-help">خالی = همه‌ی محتواها. شناسه را از «محتوا و فراخوان‌ها» بردارید.</span></div>
          <div class="si-field is-full" id="si-keywords-field"><label for="r-keywords">کلمات کلیدی</label><textarea id="r-keywords" name="keywords" class="input-pro" rows="2" style="min-height:60px" placeholder="هر کلمه در یک خط یا با ویرگول — مثلاً: قیمت، هزینه، چنده">{{ old('keywords', implode("\n", (array) $rule->keywords)) }}</textarea><span class="si-help">فارسی نرمال‌سازی می‌شود (ی/ک عربی، اعداد، نیم‌فاصله).</span></div>
          <div class="si-field"><label for="r-match">نحوه‌ی تطبیق</label><select id="r-match" name="match_mode" class="input-pro"><option value="contains" @selected(old('match_mode', $rule->match_mode) === 'contains')>شامل کلمه (پیشنهادی)</option><option value="word" @selected(old('match_mode', $rule->match_mode) === 'word')>کلمه‌ی کامل</option><option value="exact" @selected(old('match_mode', $rule->match_mode) === 'exact')>دقیقاً برابر</option></select></div>
          <div class="si-field"><label for="r-priority">اولویت (عدد کمتر = زودتر)</label><input id="r-priority" type="number" min="1" max="999" name="priority" class="input-pro" value="{{ old('priority', $rule->priority ?? 100) }}"></div>
        </div>
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head">
          <div class="si-panel-title"><i class="fa-solid fa-bolt"></i> ۳. اقدام‌ها (به ترتیب)</div>
          <button type="button" class="btn-pro btn-pro-ghost" id="si-add-action" style="height:32px"><i class="fa-solid fa-plus text-[10px]"></i> اقدام</button>
        </div>
        <div id="si-actions">
          @foreach($actions as $i => $action)
            @include('admin.smart-instagram.automations.partials.action', ['i' => $i, 'action' => $action])
          @endforeach
        </div>
        <template id="si-action-template">@include('admin.smart-instagram.automations.partials.action', ['i' => '__i__', 'action' => ['type' => 'send_dm']])</template>
        <p class="si-help" style="margin-top:10px">در متن‌ها از <code>{name}</code> و <code>{username}</code> استفاده کنید. پاسخ عمومی/خصوصی فقط برای شروع‌کننده‌ی کامنت معنا دارد. هر ارسال پیش از خروج، از درگاه قانون‌محور (پنجره‌ی ۲۴ساعته، تکرار، سقف روزانه، قفل انسانی) عبور می‌کند.</p>
      </section>
    </div>

    <div class="si-stack">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-filter"></i> ۲. شرط‌ها</div></div>
        <div class="si-form is-1">
          <div class="si-field"><label for="c-hours">ساعت کاری</label><select id="c-hours" name="conditions[business_hours]" class="input-pro">@foreach(['any' => 'همیشه', 'inside' => 'فقط داخل ساعت کاری', 'outside' => 'فقط خارج از ساعت کاری'] as $k => $l)<option value="{{ $k }}" @selected(($conditions['business_hours'] ?? 'any') === $k)>{{ $l }}</option>@endforeach</select></div>
          <div class="si-field"><label for="c-req">فقط مخاطبانی که این برچسب را دارند</label><input id="c-req" name="conditions[require_tag]" class="input-pro" maxlength="60" value="{{ $conditions['require_tag'] ?? '' }}"></div>
          <div class="si-field"><label for="c-exc">به‌جز مخاطبانی که این برچسب را دارند</label><input id="c-exc" name="conditions[exclude_tag]" class="input-pro" maxlength="60" value="{{ $conditions['exclude_tag'] ?? '' }}"></div>
        </div>
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-shield-halved"></i> حفاظ ایمنی</div></div>
        <div class="si-form is-1">
          <div class="si-field"><label for="g-max">حداکثر اجرا برای هر مشتری در ۲۴ ساعت</label><input id="g-max" type="number" min="0" max="20" name="guards[max_per_contact_per_day]" class="input-pro" value="{{ $guards['max_per_contact_per_day'] ?? 1 }}"><span class="si-help">۰ = بدون سقف (توصیه نمی‌شود)</span></div>
          <div class="si-field"><label for="g-cool">فاصله‌ی تکرار برای هر مشتری (دقیقه)</label><input id="g-cool" type="number" min="0" max="10080" name="guards[cooldown_minutes]" class="input-pro" value="{{ $guards['cooldown_minutes'] ?? 720 }}"></div>
          <label class="si-check"><input type="hidden" name="guards[stop_on_sensitive]" value="0"><input type="checkbox" name="guards[stop_on_sensitive]" value="1" @checked($guards['stop_on_sensitive'] ?? true)> توقف ارسال در گفتگوهای حساس / سپرده‌شده به انسان</label>
        </div>
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-toggle-on"></i> وضعیت</div></div>
        <div class="si-form is-1">
          <div class="si-field">
            <select name="status" class="input-pro" aria-label="وضعیت قانون">@foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(old('status', $rule->status) === $k)>{{ $l }}</option>@endforeach</select>
            <span class="si-help">«آزمایشی»: روی پیام‌های واقعی ارزیابی و ثبت می‌شود اما هیچ اقدامی اجرا نمی‌شود.</span>
          </div>
          <div class="si-form-actions"><button class="btn-pro btn-pro-primary"><i class="fa-solid fa-floppy-disk text-[11px]"></i> ذخیره‌ی قانون</button></div>
        </div>
      </section>
    </div>
  </div>
</form>
@endsection
