@php
  $offer = $offer ?? $plan->offerFor(auth()->user());
  $planDisplay = $planDisplay ?? ['show_images' => false];
  $preview = $preview ?? false;
  // پلن‌های قدیمی ممکن است slug نداشته باشند؛ مسیر خرید، شناسه‌ی پلن را هم می‌پذیرد.
  $purchasePlanKey = filled($plan->slug) ? $plan->slug : $plan->getKey();
  $totalTokens = (int) $offer['tokens'] + (int) $offer['bonus_tokens'];
  $displayCreditText = fn ($text) => str_replace('توکن', 'اعتبار', (string) $text);
  $style = array_key_exists($plan->card_style ?: 'classic', config('plan_card_styles')) ? ($plan->card_style ?: 'classic') : 'classic';
  $isSplit = $style === 'split';
@endphp
<article id="plan-{{ $plan->slug ?: $plan->getKey() }}" class="vpc vpc--{{ $style }} {{ $plan->is_featured ? 'is-featured' : '' }}" data-plan-style="{{ $style }}">
  @if($plan->badge_text)<span class="vpc__badge">{{ $plan->badge_text }}</span>@endif
  <div class="vpc__top">
    @if(($planDisplay['show_images'] ?? false) && $plan->image_path)
      <img src="{{ asset('storage/'.$plan->image_path) }}" alt="{{ $plan->name }}" class="vpc__media">
    @else
      <span class="vpc__icon"><i class="{{ $plan->icon ?: 'fa-solid fa-gem' }}"></i></span>
    @endif
    <h2 class="vpc__name">{{ $plan->name }}</h2>
    <p class="vpc__fit">{{ $plan->short_description }}</p>
    @if($plan->show_model_tier)
      <p class="vpc__fit">ساخت با سطح {{ \App\Services\ModelTierService::DEFINITIONS[$plan->model_tier_key]['name'] ?? 'رایگان' }}</p>
    @endif
    <div class="vpc__price">
      @if($plan->compare_at_price && (int) $plan->compare_at_price > (int) $offer['price'])
        <del>{{ number_format((int) $plan->compare_at_price) }} تومان</del>
      @endif
      @if($plan->billing_type === 'custom')
        {{ $plan->price_prefix ?: 'از' }} {{ number_format($offer['price']) }} <small>تومان</small>
      @elseif((int) $offer['price'] === 0)
        رایگان
      @else
        {{ number_format($offer['price']) }} <small>تومان</small>
      @endif
    </div>
    <div class="vpc__tokens">{{ $plan->is_unlimited ? ($displayCreditText($plan->token_label) ?: 'اعتبار نامحدود*') : number_format($totalTokens).' اعتبار' }}</div>
    @if(!$plan->is_unlimited && $offer['price'] > 0 && $offer['tokens'] > 0)
      <div class="vpc__unit-price">هر اعتبار: {{ number_format((int) round($offer['price'] / $offer['tokens'])) }} تومان</div>
    @endif
    @if($offer['bonus_tokens'] > 0)<div class="vpc__bonus">{{ number_format($offer['bonus_tokens']) }} اعتبار هدیه مشتری ثابت</div>@endif
  </div>

  <div class="vpc__features">
    @foreach($plan->features ?? [] as $feature)
      @php $state = $feature['included'] ?? 'yes'; @endphp
      <div class="vpc__feature vpc__feature--{{ $state }} {{ ($feature['highlighted'] ?? false) ? 'is-highlighted' : '' }}">
        <i class="fa-solid {{ $state === 'no' ? 'fa-xmark' : ($state === 'limited' ? 'fa-minus' : 'fa-check') }}"></i>
        <span>{{ $displayCreditText($feature['title'] ?? '') }} @if(!empty($feature['value'])): {{ $displayCreditText($feature['value']) }}@endif</span>
      </div>
    @endforeach
  </div>

  <div class="vpc__actions">
    @if($preview)
      <span class="vpc__cta {{ $plan->is_featured ? '' : 'vpc__cta--ghost' }}">{{ $plan->billing_type === 'custom' ? 'درخواست مشاوره فروش' : 'انتخاب و فعال‌سازی پلن' }}</span>
    @elseif($plan->billing_type === 'custom')
      <a href="/#contact" class="vpc__cta">درخواست مشاوره فروش</a>
    @elseif(auth()->check())
      <a href="{{ (int) $offer['price'] > 0 ? route('pricing.checkout', $purchasePlanKey) : route('app.home') }}" class="vpc__cta {{ $plan->is_featured ? '' : 'vpc__cta--ghost' }}">{{ (int) $offer['price'] > 0 ? 'انتخاب و ادامه' : 'هدیه شروع فعال است' }}</a>
    @else
      <a href="{{ route('login', ['redirect' => route('pricing.index') . '#plan-' . ($plan->slug ?: $plan->getKey())]) }}" class="vpc__cta {{ $plan->is_featured ? '' : 'vpc__cta--ghost' }}">ورود و انتخاب پلن</a>
    @endif
  </div>
</article>
