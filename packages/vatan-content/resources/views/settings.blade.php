@extends('cf::layout')
@php use Vatan\Content\Support\Fa; $agentCmd = 'python3 content-lab/_system/agent/agent.py --server '.$serverUrl.' --token '.$agentToken; @endphp

@section('cf-actions')
  <form method="post" action="{{ route('cf.settings.sync') }}">@csrf<button class="cf-btn"><i class="fa-solid fa-rotate"></i> همگام‌سازی دستی</button></form>
@endsection

@section('cf-page')
<div class="cf-grid cf-g2" style="align-items:start">
  <div class="cf-stack">
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-plug"></i> اتصال‌ها</h3>
      @foreach($checks as [$label, $ok, $hint])
        <div class="cf-between" style="padding:9px 0;border-bottom:1px solid var(--divider)"><span><b style="color:var(--text-h)">{{ $label }}</b><div class="cf-muted" style="font-size:11.5px">{{ $hint }}</div></span>
          <span class="cf-pill {{ $ok ? 'is-success' : 'is-warning' }}"><i class="fa-solid {{ $ok ? 'fa-check' : 'fa-minus' }}"></i> {{ $ok ? 'آماده' : 'تنظیم نشده' }}</span></div>
      @endforeach
    </div>
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-brands fa-telegram"></i> گروه تلگرام کارخانه @include('cf::partials.help', ['k' => 'telegram'])</h3>
      <form method="post" action="{{ route('cf.settings.telegram') }}" class="cf-stack" style="gap:10px">@csrf
        <label class="cf-field">توکن بات<input name="token" dir="ltr" placeholder="{{ $tg['token'] ? '•••• ذخیره شده — برای تغییر وارد کنید' : '123456:ABC…' }}"></label>
        <label class="cf-field">شناسه‌ی گروه (اختیاری؛ با «پیدا کردن گروه» خودکار پر می‌شود)<input name="chat_id" dir="ltr" value="{{ $tg['chat_id'] }}"></label>
        <div class="cf-row">
          <button name="action" value="save" class="cf-btn">ذخیره</button>
          <button name="action" value="detect" class="cf-btn">۱. پیدا کردن گروه</button>
          <button name="action" value="topics" class="cf-btn">۲. ساخت تاپیک‌ها</button>
          <button name="action" value="webhook" class="cf-btn">۳. تنظیم وب‌هوک (سرور اصلی)</button>
          <button name="action" value="test" class="cf-btn cf-btn-primary">پیام آزمایشی</button>
        </div>
        @if(array_filter($tg['topics']))<div class="cf-row" style="font-size:12px">@foreach(config('content-factory.telegram.topics') as $k => $n)<span class="cf-pill {{ !empty($tg['topics'][$k]) ? 'is-success' : '' }}">{{ $n }}</span>@endforeach</div>@endif
      </form></div>
  </div>
  <div class="cf-stack">
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-laptop-code"></i> ایجنت مک @include('cf::partials.help', ['k' => 'agent'])</h3>
      @php $caps = $agent['caps'] ?? []; @endphp
      @if($caps)
        <div class="cf-row" style="font-size:12px;margin-bottom:8px">
          @foreach(['ffmpeg' => 'ffmpeg', 'node' => 'Node', 'render_deps' => 'موتور رندر', 'brew' => 'Homebrew', 'launch_agent' => 'ایجنت دائمی'] as $ck => $cl)
            <span class="cf-pill {{ !empty($caps[$ck]) ? 'is-success' : 'is-warning' }}"><i class="fa-solid {{ !empty($caps[$ck]) ? 'fa-check' : 'fa-xmark' }}"></i> {{ $cl }}</span>
          @endforeach
          @if(!empty($caps['python']))<span class="cf-pill">Python {{ $caps['python'] }}</span>@endif
        </div>
      @endif
      <form method="post" action="{{ route('cf.jobs.store') }}" style="margin-bottom:10px">@csrf<input type="hidden" name="type" value="doctor"><button class="cf-btn cf-btn-sm"><i class="fa-solid fa-stethoscope"></i> بررسی ابزارهای مک</button></form>
      @if($isLocal)
        <div class="cf-section-title">ایجنت دائمی (یک کلیک) @include('cf::partials.help', ['k' => 'agent_install'])</div>
        <form method="post" action="{{ route('cf.settings.agent-install') }}" class="cf-stack" style="gap:8px">@csrf
          <label class="cf-field">آدرس داشبورد آنلاین<input name="online_url" dir="ltr" value="{{ $remote['url'] ?? 'https://aivatan.com' }}"></label>
          <label class="cf-field">توکن ایجنتِ داشبورد آنلاین (از صفحه‌ی تنظیمات آنلاین کپی کنید)<input name="online_token" dir="ltr" value="{{ $remote['token'] ?? '' }}"></label>
          <div><button class="cf-btn cf-btn-primary"><i class="fa-solid fa-power-off"></i> نصب و روشن کردن ایجنت دائمی</button></div>
        </form>
        <div class="cf-muted" style="font-size:11.5px;margin-top:6px">ایجنت با روشن شدن مک خودکار اجرا می‌شود و هم کارهای داشبورد لوکال و هم آنلاین را انجام می‌دهد. ffmpeg و node اگر نباشند با Homebrew خودکار نصب می‌شوند.</div>
      @else
        <div class="cf-section-title" style="margin-top:4px">توکن این داشبورد برای ایجنت مک <button type="button" class="cf-btn cf-btn-sm" data-cf-copy="cf-agent-token"><i class="fa-regular fa-copy"></i> کپی</button></div>
        <div class="cf-code" id="cf-agent-token">{{ $agentToken }}</div>
        <div class="cf-muted" style="font-size:12px;line-height:2;margin-top:6px">در داشبورد لوکال مک ← تنظیمات ← «ایجنت دائمی»، این توکن را بچسبانید و دکمه را بزنید. یا در ترمینال:</div>
        <div class="cf-code" id="cf-agent-cmd">{{ $agentCmd }}</div>
      @endif
      <form method="post" action="{{ route('cf.settings.agent-token') }}" style="margin-top:10px">@csrf<button class="cf-btn cf-btn-sm">ساخت توکن جدید</button></form>
      @if(!empty($agent['last_seen']))<div class="cf-muted" style="font-size:12px;margin-top:8px">آخرین تماس: {{ Fa::date($agent['last_seen'], false, true) }} · {{ $agent['host'] ?? '' }} · نسخه {{ Fa::digits($agent['version'] ?? '') }}</div>@endif
    </div>
    @include('cf::partials.jobs')
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-brain"></i> مدل‌ها</h3>
      <dl class="cf-dl"><dt>سریع و پرتعداد</dt><dd class="cf-ltr">{{ $models['fast'] }}</dd><dt>نویسنده (کپشن، تشخیص سبک)</dt><dd class="cf-ltr">{{ $models['writer'] }}</dd><dt>داور و تحلیل هفتگی</dt><dd class="cf-ltr">{{ $models['judge'] }}</dd></dl>
      <div class="cf-muted" style="font-size:11.5px;margin-top:8px">کار سنگین خلاقه (تقویم، سناریوها، تحلیل سبک‌ها) با Claude در گفتگو انجام شده و هزینه‌ی API ندارد. از ایران، OpenRouter فقط از سرور اصلی با پل اتصال کار می‌کند.</div></div>
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-clock-rotate-left"></i> آخرین همگام‌سازی دستی</h3>
      @if($lastSync)<div>{{ Fa::date($lastSync['at'] ?? null, true, true) }}</div><div class="cf-muted" style="font-size:12px">{{ implode(' · ', $lastSync['log'] ?? []) }}</div>@else<div class="cf-muted">—</div>@endif</div>
  </div>
</div>
@endsection
