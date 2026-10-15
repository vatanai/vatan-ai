@php $cfLinks = \Vatan\Content\Support\Nav::links(); $cfBadge = \Vatan\Content\Support\Nav::reviewCount(); @endphp
<nav class="cf-tabs" aria-label="بخش‌های کارخانه‌ی محتوا">
  @foreach($cfLinks as [$r, $icon, $label, $pattern])
    <a href="{{ route($r) }}" class="cf-tab {{ request()->routeIs($pattern) ? 'is-active' : '' }}"><i class="fa-solid {{ $icon }}"></i> {{ $label }}@if($r === 'cf.productions' && $cfBadge)<span class="cf-tab-badge">{{ \Vatan\Content\Support\Fa::n($cfBadge) }}</span>@endif</a>
  @endforeach
  @php $cfAgentOn = \Vatan\Content\Http\Controllers\JobController::agentOnline(); @endphp
  <span class="cf-agent {{ $cfAgentOn ? 'is-on' : '' }}" id="cf-agent-pill" style="margin-inline-start:auto" title="ایجنت مک"><i class="fa-solid fa-circle"></i> ایجنت مک: <b>{{ $cfAgentOn ? 'آنلاین' : 'خاموش' }}</b></span>
  <span class="cf-tab-version" style="margin-inline-start:0">کارخانه {{ \Vatan\Content\Support\Fa::digits('v'.config('content-factory.version')) }}</span>
</nav>
