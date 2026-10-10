{{-- راهنمای کلیکی: @include('seo::partials.help', ['k' => 'کلید در config/help.php']) یا ['text' => '...'] --}}
@php($seoH = isset($k) ? \Vatan\Seo\Support\Help::get($k) : null)
@php($seoH = $seoH ?? (isset($text) ? ['title' => $title ?? null, 'body' => $text, 'points' => $points ?? []] : null))
@if($seoH)
<span class="seo-help">
  <button type="button" class="seo-help-btn" aria-label="راهنما"><i class="fa-regular fa-circle-question"></i></button>
  <span class="seo-pop" role="dialog">
    <button type="button" class="seo-pop-close" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    @if(!empty($seoH['title']))<span class="seo-pop-title"><i class="fa-solid fa-circle-info"></i>{{ $seoH['title'] }}</span>@endif
    @if(!empty($seoH['body']))<span style="display:block">{{ $seoH['body'] }}</span>@endif
    @if(!empty($seoH['points']))<ul>@foreach($seoH['points'] as $seoP)<li>{{ $seoP }}</li>@endforeach</ul>@endif
  </span>
</span>
@endif
