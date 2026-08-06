@php
  $hbIntroSteps = collect(preg_split('/\r\n|\r|\n/', (string) $section->setting('intro_steps', '')))
      ->map(fn ($line) => trim($line))->filter()->values();
@endphp
<div class="hb-intro-card">
  @if($section->setting('intro_badge'))<span class="hb-intro-badge">{{ $section->setting('intro_badge') }}</span>@endif
  <h3 class="hb-intro-heading">
    {{ $section->setting('intro_heading') }}
    @if($section->setting('intro_heading_accent'))<span class="hb-intro-heading-accent">{{ $section->setting('intro_heading_accent') }}</span>@endif
  </h3>
  @if($section->setting('intro_desc'))<p class="hb-intro-desc">{{ $section->setting('intro_desc') }}</p>@endif
  @if($hbIntroSteps->isNotEmpty())
    <div class="hb-intro-steps">
      @foreach($hbIntroSteps as $hbStepIndex => $hbStepText)
        <div class="hb-intro-step"><span class="hb-intro-step-num">{{ $hbStepIndex + 1 }}</span>{{ $hbStepText }}</div>
      @endforeach
    </div>
  @endif
  @if($section->setting('intro_note'))<div class="hb-intro-note"><i class="fa-solid fa-circle-info"></i>{{ $section->setting('intro_note') }}</div>@endif
  @if($section->setting('intro_cta_label'))<a class="hb-intro-cta" href="{{ $section->setting('intro_cta_link') ?: '#' }}">{{ $section->setting('intro_cta_label') }}</a>@endif
</div>
