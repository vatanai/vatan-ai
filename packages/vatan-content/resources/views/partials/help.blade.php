@php $cfHelpText = (config('content-factory-help') ?? [])[$k] ?? null; @endphp
@if($cfHelpText)
<span class="cf-help" tabindex="0" role="button" aria-label="راهنما"><i class="fa-solid fa-circle-question"></i><span class="cf-help-pop" role="tooltip">{{ $cfHelpText }}</span></span>
@endif
