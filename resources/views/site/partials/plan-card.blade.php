@php
  $offer = $offer ?? $plan->offerFor(auth()->user());
  $planDisplay = $planDisplay ?? ['show_images' => false];
  $preview = $preview ?? false;
  // پلن‌های قدیمی ممکن است slug نداشته باشند؛ مسیر خرید، شناسه‌ی پلن را هم می‌پذیرد.
  $purchasePlanKey = filled($plan->slug) ? $plan->slug : $plan->getKey();
  $totalTokens = (int) $offer['tokens'] + (int) $offer['bonus_tokens'];
  $style = array_key_exists($plan->card_style ?: 'classic', config('plan_card_styles')) ? ($plan->card_style ?: 'classic') : 'classic';
  $isSplit = $style === 'split';
@endphp
<article class="vpc vpc--{{ $style }} {{ $plan->is_featured ? 'is-featured' : '' }}" data-plan-style="{{ $style }}">
  @if($plan->badge_text)<span class="vpc__badge">{{ $plan->badge_text }}</span>@endif
  <div class="vpc__top">
    @if(($planDisplay['show_images'] ?? false) && $plan->image_path)
      <img src="{{ asset('storage/'.$plan->image_path) }}" alt="{{ $plan->name }}" class="vpc__media">
    @else
      <span class="vpc__icon"><i class="{{ $plan->icon ?: 'fa-solid fa-gem' }}"></i></span>
    @endif
    <h2 class="vpc__name">{{ $plan->name }}</h2>
    <p class="vpc__fit">{{ $plan->short_description }}</p>
    <div class="vpc__price">
      @if($plan->billing_type === 'custom')
        {{ $plan->price_prefix ?: 'از' }} {{ number_format($offer['price']) }} <small>تومان</small>
      @elseif((int) $offer['price'] === 0)
        رایگان
      @else
        {{ number_format($offer['price']) }} <small>تومان / {{ $plan->billing_type === 'yearly' ? 'سال' : ($plan->billing_type === 'one_time' ? 'یک‌باره' : 'ماه') }}</small>
      @endif
    </div>
    <div class="vpc__tokens">{{ $plan->is_unlimited ? ($plan->token_label ?: 'توکن نامحدود*') : number_format($totalTokens).' توکن' }}</div>
    @if(!$plan->is_unlimited && $offer['price'] > 0 && $offer['tokens'] > 0)
      <div class="vpc__unit-price">هر توکن: {{ number_format((int) round($offer['price'] / $offer['tokens'])) }} تومان</div>
    @endif
    @if($offer['bonus_tokens'] > 0)<div class="vpc__bonus">{{ number_format($offer['bonus_tokens']) }} توکن هدیه مشتری ثابت</div>@endif
  </div>

  <div class="vpc__features">
    @foreach($plan->features ?? [] as $feature)
      @php $state = $feature['included'] ?? 'yes'; @endphp
      <div class="vpc__feature vpc__feature--{{ $state }} {{ ($feature['highlighted'] ?? false) ? 'is-highlighted' : '' }}">
        <i class="fa-solid {{ $state === 'no' ? 'fa-xmark' : ($state === 'limited' ? 'fa-minus' : 'fa-check') }}"></i>
        <span>{{ $feature['title'] }} @if(!empty($feature['value'])): {{ $feature['value'] }}@endif</span>
      </div>
    @endforeach
  </div>

  <div class="vpc__actions">
    @if($preview)
      <span class="vpc__cta {{ $plan->is_featured ? '' : 'vpc__cta--ghost' }}">{{ $plan->billing_type === 'custom' ? 'درخواست مشاوره فروش' : 'انتخاب و فعال‌سازی پلن' }}</span>
    @elseif($plan->billing_type === 'custom')
      <a href="/#contact" class="vpc__cta">درخواست مشاوره فروش</a>
    @elseif(auth()->check())
      <form action="{{ route('pricing.fakePayment', $purchasePlanKey) }}" method="POST">@csrf<button class="vpc__cta {{ $plan->is_featured ? '' : 'vpc__cta--ghost' }}">انتخاب و فعال‌سازی پلن</button></form>
    @else
      <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="vpc__cta {{ $plan->is_featured ? '' : 'vpc__cta--ghost' }}">ورود و انتخاب پلن</a>
    @endif
  </div>
</article>
