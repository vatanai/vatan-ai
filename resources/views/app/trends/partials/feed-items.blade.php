@foreach($trendProducts as $index => $card)
  @include('app.trends.partials.product-card', [
    'card' => $card,
    'cardIndex' => ((int) ($cardOffset ?? 0)) + $index,
  ])
@endforeach
