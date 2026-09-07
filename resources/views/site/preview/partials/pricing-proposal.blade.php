@php
    $pricingSettings = is_array($homePricing ?? null) ? $homePricing : [];
    $pricingNotes = collect($pricingSettings['notes'] ?? [])->filter()->values();
    $proposalPlans = collect($homePricingPlans ?? []);
@endphp

<section class="vp-section vp-pricing vp-pricing--proposal" id="pricing-proposal" aria-labelledby="pricing-proposal-title">
    <div class="vp-container">
        <header class="vp-section-head vp-section-head--center vp-reveal">
            <h2 id="pricing-proposal-title">{{ $pricingSettings['title'] ?? 'پلن‌ها، بر پایه اعتبار دائمی' }}</h2>
        </header>

        @if($pricingNotes->isNotEmpty())
            <div class="vp-proposal-note vp-reveal">
                @foreach($pricingNotes as $note)
                    <span><i class="fa-solid fa-bolt vp-proposal-note__token" aria-hidden="true"></i>{{ $note }}</span>
                @endforeach
            </div>
        @endif

        <div class="vp-plans vp-proposal-plans">
            @foreach($proposalPlans as $index => $plan)
                <div class="vp-reveal" @if($index > 0) style="--delay:{{ $index * 65 }}ms" @endif>
                    @include('site.partials.pricing-proposal-card', ['plan' => $plan])
                </div>
            @endforeach
        </div>
    </div>
</section>
