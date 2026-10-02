@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = $rule->name;
  $siSubtitle = ($triggers[$rule->trigger] ?? $rule->trigger).' · نسخه‌ی '.Ui::n($rule->version).' · '.($statuses[$rule->status] ?? $rule->status);
@endphp

@section('si-actions')
  @if(app(\App\Services\SmartInstagram\WorkspaceContext::class)->can(auth('admin')->user(), 'manage_automation'))
    <a href="{{ route('admin.smart-instagram.automations.edit', $rule) }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-pen text-[11px]"></i> ویرایش</a>
    @foreach(['test' => ['fa-flask', 'آزمایشی'], 'active' => ['fa-play', 'فعال'], 'paused' => ['fa-pause', 'توقف']] as $status => [$icon, $label])
      @if($rule->status !== $status)
        <form method="POST" action="{{ route('admin.smart-instagram.automations.status', $rule) }}" @if($status === 'active') data-confirm="این قانون روی گفتگوهای واقعی اجرا شود؟" @endif>@csrf<input type="hidden" name="status" value="{{ $status }}"><button class="btn-pro {{ $status === 'active' ? 'btn-pro-primary' : 'btn-pro-ghost' }}"><i class="fa-solid {{ $icon }} text-[11px]"></i> {{ $label }}</button></form>
      @endif
    @endforeach
    <form method="POST" action="{{ route('admin.smart-instagram.automations.destroy', $rule) }}" data-confirm="این قانون و تاریخچه‌ی اجرایش حذف شود؟">@csrf @method('DELETE')<button class="btn-pro btn-pro-danger"><i class="fa-solid fa-trash text-[11px]"></i></button></form>
  @endif
@endsection

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-bolt', 'tone' => 'info', 'value' => Ui::n($rule->runs_count), 'label' => 'اجرا'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-circle-check', 'tone' => 'success', 'value' => $rule->successRate() === null ? '—' : Ui::pct($rule->successRate()), 'label' => 'نرخ موفقیت'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-bug', 'tone' => $rule->failure_count ? 'danger' : 'primary', 'value' => Ui::n($rule->failure_count), 'label' => 'ناموفق'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-clock', 'tone' => 'warning', 'value' => Ui::ago($rule->last_run_at), 'label' => 'آخرین اجرا'])
  </div>

  <div class="si-grid-even">
    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-sitemap"></i> منطق قانون</div></div>
      <div class="si-step"><span class="si-step-num">۱</span><div><b>وقتی</b> {{ $triggers[$rule->trigger] ?? $rule->trigger }}@if($rule->keywords) شامل «{{ implode('»، «', $rule->keywords) }}»@endif @if($rule->scope_ref) روی رسانه‌ی <span class="si-mono">{{ $rule->scope_ref }}</span>@endif @if(!empty($rule->conditions['post_url'])) · <a class="si-link" href="{{ $rule->conditions['post_url'] }}" target="_blank" rel="noreferrer">باز کردن پست هدف</a>@endif</div></div>
      <div class="si-step"><span class="si-step-num">۲</span><div><b>اگر</b> {{ ['any' => 'در هر ساعتی', 'inside' => 'داخل ساعت کاری', 'outside' => 'خارج از ساعت کاری'][$rule->conditions['business_hours'] ?? 'any'] }}@if(!empty($rule->conditions['require_tag'])) و برچسب «{{ $rule->conditions['require_tag'] }}» داشت@endif @if(!empty($rule->conditions['exclude_tag'])) و برچسب «{{ $rule->conditions['exclude_tag'] }}» نداشت@endif @if(!empty($rule->conditions['require_follow'])) · قانون فالو فعال است@endif</div></div>
      <div class="si-step"><span class="si-step-num">۳</span><div><b>آن‌گاه</b>
        <ol class="si-ul">@foreach((array) $rule->actions as $action)<li>{{ $actionsMap[$action['type']] ?? $action['type'] }}@if(!empty($action['text'])): <span class="si-muted">«{{ \Illuminate\Support\Str::limit($action['text'], 120) }}»</span>@endif @if(!empty($action['tag'])): {{ $action['tag'] }}@endif</li>@endforeach</ol>
      </div></div>
      <div class="si-step"><span class="si-step-num"><i class="fa-solid fa-shield-halved" style="font-size:10px"></i></span><div>حداکثر {{ Ui::n($rule->guards['max_per_contact_per_day'] ?? 0) }} بار در روز برای هر مشتری · فاصله‌ی تکرار {{ Ui::n($rule->guards['cooldown_minutes'] ?? 0) }} دقیقه · {{ ($rule->guards['stop_on_sensitive'] ?? true) ? 'توقف در گفتگوی حساس' : 'بدون توقف حساس' }}</div></div>
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-vial"></i> آزمون سریع</div><div class="si-panel-sub">یک پیام نمونه بنویسید و ببینید قانون اجرا می‌شود یا نه (بدون ثبت و ارسال).</div></div></div>
      <form id="si-simulate" action="{{ route('admin.smart-instagram.automations.simulate', $rule) }}" class="si-form" data-no-lock>
        <div class="si-field is-full"><label for="s-text">متن پیام/کامنت</label><input id="s-text" name="text" class="input-pro" required maxlength="500" placeholder="مثلاً: قیمتش چنده؟"></div>
        <div class="si-field"><label for="s-source">منبع</label><select id="s-source" name="source" class="input-pro">@foreach(config('smart_instagram.sources') as $k => $l)<option value="{{ $k }}" @selected(($rule->trigger === 'comment_keyword' ? 'comment' : 'dm') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="si-field"><label for="s-scope">شناسه‌ی محتوا</label><input id="s-scope" name="scope_ref" class="input-pro si-ltr" value="{{ $rule->scope_ref }}"></div>
        <label class="si-check si-field is-full" style="flex-direction:row"><input type="checkbox" name="new_contact" value="1" checked> مشتری جدید (اولین پیام)</label>
        <div class="si-form-actions"><button type="submit" class="btn-pro btn-pro-primary">بررسی</button></div>
      </form>
      <div id="si-simulate-result" class="si-simulate-result"></div>
    </section>
  </div>

  <section class="content-card si-panel is-flush">
    <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-clock-rotate-left"></i> تاریخچه‌ی اجرا</div></div>
    <div class="si-table-wrap" style="margin-top:10px">
      <table class="table-pro">
        <thead><tr><th>زمان</th><th>مخاطب</th><th>نسخه</th><th>حالت</th><th>نتیجه</th><th>تصمیم‌ها</th></tr></thead>
        <tbody>
          @forelse($runs as $run)
            <tr>
              <td class="si-muted">{{ Ui::date($run->created_at, true) }}</td>
              <td>@if($run->contact)<a class="si-link" href="{{ route('admin.smart-instagram.contacts.show', $run->contact) }}">{{ $run->contact->label() }}</a>@else — @endif</td>
              <td class="si-num">{{ Ui::n($run->rule_version) }}</td>
              <td>{{ $run->mode === 'test' ? 'آزمایشی' : 'واقعی' }}</td>
              <td><span class="badge-pro badge-{{ Ui::statusTone($run->status) }}">{{ Ui::label('run', $run->status) }}</span></td>
              <td style="text-align:right;white-space:normal;min-width:240px"><ul class="si-ul" style="font-size:11px;line-height:1.8">@foreach((array) $run->decisions as $decision)<li><b>{{ $actionsMap[$decision['action'] ?? ''] ?? ($decision['action'] === 'guard' ? 'حفاظ' : ($decision['action'] ?? '')) }}:</b> {{ $decision['result'] ?? '' }}</li>@endforeach</ul></td>
            </tr>
          @empty
            <tr><td colspan="6" class="si-table-empty">این قانون هنوز اجرا نشده.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('admin.smart-instagram.partials.pagination', ['paginator' => $runs])
  </section>
@endsection
