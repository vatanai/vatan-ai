@php
    $config = is_array($plan->home_pricing_config) ? $plan->home_pricing_config : [];
    $offer = $offer ?? $plan->offerFor(auth()->user());
    $variant = $config['variant'] ?? 'gift';
    $price = (array) ($config['price'] ?? []);
    $primaryStat = (array) ($config['primary_stat'] ?? []);
    $secondaryStat = (array) ($config['secondary_stat'] ?? []);
    $button = (array) ($config['button'] ?? []);
    $pricingPage = $pricingPage ?? false;
    $planKey = $plan->slug ?: $plan->id;
    $variantClass = match ($variant) {
        'professional' => 'vp-proposal-plan--featured',
        'advanced' => 'vp-proposal-plan--advanced',
        'business' => 'vp-proposal-plan--business',
        default => '',
    };
    $hasHeadingIcon = filled($config['icon'] ?? null);
    $featureList = collect($plan->features ?? []);
    if (! $featureList->contains(fn ($feature) => trim((string) (is_array($feature) ? ($feature['title'] ?? '') : $feature)) === 'پروفایل چهره')) {
        $featureList->push([
            'title' => 'پروفایل چهره',
            'value' => (string) ((int) ($plan->face_profile_limit ?? 0)),
            'included' => (int) ($plan->face_profile_limit ?? 0) > 0 ? 'limited' : 'no',
        ]);
    }

    if ($pricingPage) {
        if ($plan->billing_type === 'custom') {
            $href = '/#contact';
            $actionLabel = 'درخواست مشاوره';
        } elseif (! auth()->check()) {
            $href = route('login', ['redirect' => route('pricing.checkout', $planKey)]);
            $actionLabel = (int) $offer['price'] > 0 ? 'ورود و ادامه خرید' : 'شروع رایگان';
        } elseif ((int) $offer['price'] <= 0) {
            $href = route('app.home');
            $actionLabel = 'هدیه شروع فعال است';
        } else {
            $href = route('pricing.checkout', $planKey);
            $actionLabel = 'انتخاب و ادامه';
        }
    } else {
        $href = match ($button['action'] ?? 'pricing') {
            'login' => route('login', ['redirect' => route('pricing.index')]),
            'contact' => '/#contact',
            default => route('pricing.index'),
        };
        $actionLabel = $button['label'] ?? 'انتخاب پلن';
    }
@endphp

<article class="vp-proposal-plan {{ $variantClass }}">
    @if(filled($config['ribbon'] ?? null) || filled($config['badge'] ?? null))
        <div class="vp-proposal-plan__badges">
            @if(filled($config['ribbon'] ?? null))<span class="vp-proposal-plan__ribbon">{{ $config['ribbon'] }}</span>@endif
            @if(filled($config['badge'] ?? null))
                @php $badge = (string) $config['badge']; @endphp
                <span class="vp-proposal-plan__discount">
                    @if(preg_match('/^([۰-۹]+)٪\s*(.*)$/u', $badge, $badgeParts))
                        <bdi dir="ltr"><span class="vp-proposal-plan__discount-sign">%</span><span dir="rtl">{{ $badgeParts[1] }}</span><span dir="rtl"> {{ $badgeParts[2] }}</span></bdi>
                    @else
                        <bdi dir="rtl">{{ $badge }}</bdi>
                    @endif
                </span>
            @endif
        </div>
    @endif

    <div class="vp-proposal-plan__top">
        @if($hasHeadingIcon)
            <div class="vp-proposal-plan__heading">
                <span class="vp-proposal-plan__icon"><i class="{{ $config['icon'] }}"></i></span>
                <div><span class="vp-proposal-plan__eyebrow">{{ $config['eyebrow'] ?? $plan->short_description }}</span><h3>{{ $plan->name }}</h3></div>
            </div>
        @else
            <span class="vp-proposal-plan__eyebrow">{{ $config['eyebrow'] ?? $plan->short_description }}</span>
            <h3>{{ $plan->name }}</h3>
        @endif
        <div class="vp-proposal-plan__price">
            @if(filled($price['prefix'] ?? null))<span>{{ $price['prefix'] }}</span>@endif
            <strong>{{ (int) $offer['price'] > 0 ? number_format((int) $offer['price']) : ($price['value'] ?? 'رایگان') }}</strong>
            @if((int) $offer['price'] > 0)<span>تومان</span>@elseif(filled($price['suffix'] ?? null))<span>{{ $price['suffix'] }}</span>@endif
        </div>
        <div class="vp-proposal-plan__credit"><i class="{{ $config['credit_icon'] ?? 'fa-solid fa-bolt' }}"></i> {{ number_format((int) $offer['tokens'] + (int) $offer['bonus_tokens']) }} اعتبار @if((int) $offer['bonus_tokens'] > 0)<small>{{ number_format((int) $offer['bonus_tokens']) }} هدیه</small>@elseif(filled($config['credit_detail'] ?? null))<small>{{ $config['credit_detail'] }}</small>@endif</div>
        @if(filled($config['estimate'] ?? null))<div class="vp-proposal-plan__estimate">{{ $config['estimate'] }}</div>@endif
    </div>
    <div class="vp-proposal-plan__stats">
        <div class="vp-proposal-plan__unit-price"><span>{{ $primaryStat['label'] ?? 'قیمت هر اعتبار' }}</span><b>{{ $primaryStat['value'] ?? '—' }}</b></div>
        <div class="{{ ($secondaryStat['type'] ?? 'saving') === 'capacity' ? 'vp-proposal-plan__capacity-status' : 'vp-proposal-plan__saving' }}"><span>{{ $secondaryStat['label'] ?? 'صرفه‌جویی' }}</span><b>{{ $secondaryStat['value'] ?? '—' }}</b></div>
    </div>
    <ul>
        @foreach($featureList as $feature)
            @php
                $featureTitle = is_array($feature) ? ($feature['title'] ?? '') : $feature;
                $featureValue = is_array($feature) ? ($feature['value'] ?? '') : '';
            @endphp
            @if(filled($featureTitle))<li>{{ $featureTitle }}@if($featureTitle === 'پروفایل چهره') · {{ number_format((int) $featureValue) }}@endif</li>@endif
        @endforeach
    </ul>
    <a class="vp-button {{ ($button['style'] ?? 'primary') === 'economic' ? 'vp-button--secondary vp-proposal-plan__economic-button' : 'vp-button--primary' }}" href="{{ $href }}">{{ $actionLabel }}</a>
</article>
