{{--
  Layout «اسلایدر با کارت معرفی» — نمونه‌برداری‌شده از شات Commercial Studio (pixifield.com).
  اولین آیتم یک کارت معرفی/اطلاعاتی قابل‌تنظیم (برچسب، عنوان دو‌رنگ، توضیح، مراحل شماره‌دار، یادداشت، دکمه CTA)
  و پس از آن کارت‌های واید محصول. تمام محتوای کارت معرفی از settings این Section خوانده می‌شود.
--}}
@php
  $hbIntroSteps = collect(preg_split('/\r\n|\r|\n/', (string) $section->setting('intro_steps', '')))
      ->map(fn ($line) => trim($line))
      ->filter()
      ->values();
@endphp
<div class="hb-intro-row">
  <div class="hb-intro-card">
    @if($section->setting('intro_badge'))
      <span class="hb-intro-badge">{{ $section->setting('intro_badge') }}</span>
    @endif

    <h3 class="hb-intro-heading">
      {{ $section->setting('intro_heading') }}
      @if($section->setting('intro_heading_accent'))
        <span class="hb-intro-heading-accent">{{ $section->setting('intro_heading_accent') }}</span>
      @endif
    </h3>

    @if($section->setting('intro_desc'))
      <p class="hb-intro-desc">{{ $section->setting('intro_desc') }}</p>
    @endif

    @if($hbIntroSteps->isNotEmpty())
      <div class="hb-intro-steps">
        @foreach($hbIntroSteps as $hbStepIndex => $hbStepText)
          <div class="hb-intro-step">
            <span class="hb-intro-step-num">{{ $hbStepIndex + 1 }}</span> {{ $hbStepText }}
          </div>
        @endforeach
      </div>
    @endif

    @if($section->setting('intro_note'))
      <div class="hb-intro-note"><i class="fa-solid fa-circle-info"></i> {{ $section->setting('intro_note') }}</div>
    @endif

    @if($section->setting('intro_cta_label'))
      <a class="hb-intro-cta" href="{{ $section->setting('intro_cta_link') ?: '#' }}">{{ $section->setting('intro_cta_label') }}</a>
    @endif
  </div>

  @foreach($products as $product)
    <a class="hb-wide-item" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-wide-card" style="background-image: url('{{ $product->displayImageUrl() }}');">
        @if(in_array($product->media_type, ['video', 'both']))
          <i class="fa-solid fa-circle-play" style="position:absolute;inset:0;margin:auto;width:32px;height:32px;font-size:28px;color:#fff;text-shadow:0 2px 6px rgba(0,0,0,.6);"></i>
        @endif
      </div>
      <div class="hb-wide-meta">
        @if($section->setting('show_credit', true))
          <div class="hb-card-cost"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</div>
        @endif
        <p class="hb-card-title">{{ $product->name_fa }}</p>
        <p class="hb-card-desc">{{ $product->subcategory ?: $product->category }}</p>
      </div>
    </a>
  @endforeach
</div>
