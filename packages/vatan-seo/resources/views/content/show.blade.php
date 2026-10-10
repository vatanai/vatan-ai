@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\ContentItem;
  $seoTitle = $item->title ?: 'محتوا';
  $seoIcon = 'fa-file-lines';
  $seoSubtitle = ContentItem::STATUSES[$item->status].' · کلمه: '.($item->keyword?->keyword ?? '—').' · '.Fa::ago($item->created_at);
  $ai = $item->quality['ai'] ?? null;
  $brief = (array) $item->brief;
@endphp

@section('seo-actions')
  <a href="{{ route('seo.content.index') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
  @if(in_array($item->status, ['review', 'approved', 'failed'], true) && $item->blocks)
    <form method="POST" action="{{ route('seo.content.reject', $item) }}">@csrf<button class="btn-pro btn-pro-danger" data-confirm="رد شود؟"><i class="fa-solid fa-ban"></i> رد</button></form>
    <form method="POST" action="{{ route('seo.content.publish', $item) }}">@csrf<button class="btn-pro btn-pro-primary" data-confirm="در سایت منتشر شود؟" data-loading="در حال انتشار…"><i class="fa-solid fa-upload"></i> تأیید و انتشار</button></form>
  @endif
  @if($item->published_url)<a href="{{ $item->published_url }}" target="_blank" rel="noopener" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-up-right-from-square"></i> مشاهده در سایت</a>@endif
@endsection

@section('seo-page')
<div class="seo-grid seo-grid-main" style="align-items:start">
  <section class="seo-card">
    @if($item->blocks)
      <div class="seo-serp" style="margin-bottom:18px">
        <div class="u">{{ $site->domain }} › {{ $item->slug }}</div>
        <div class="t">{{ $item->meta_title ?: $item->title }}</div>
        <div class="d">{{ $item->meta_description }}</div>
      </div>
      <article class="seo-article">{!! $html !!}</article>
    @elseif($brief)
      <div class="seo-card-title" style="margin-bottom:10px"><i class="fa-solid fa-clipboard-list"></i> بریف</div>
      <div class="seo-stack" style="gap:10px;font-size:12.5px;line-height:2">
        @if(!empty($brief['angle']))<div><b>زاویه‌ی یونیک:</b> {{ $brief['angle'] }}</div>@endif
        @if(!empty($brief['title_options']))<div><b>عنوان‌های پیشنهادی:</b><ul style="margin:4px 0;padding-right:18px">@foreach($brief['title_options'] as $t)<li>{{ $t }}</li>@endforeach</ul></div>@endif
        @if(!empty($brief['outline']))<div><b>سرفصل‌ها:</b><ul style="margin:4px 0;padding-right:18px">@foreach($brief['outline'] as $o)<li>{{ $o['h2'] ?? '' }} <span class="seo-muted">— {{ $o['notes'] ?? '' }}</span></li>@endforeach</ul></div>@endif
        @if(!empty($brief['faqs']))<div><b>سؤالات کاربران:</b> {{ implode(' · ', array_map(fn ($f) => is_array($f) ? ($f['q'] ?? '') : $f, $brief['faqs'])) }}</div>@endif
      </div>
      <form method="POST" action="{{ route('seo.content.redraft', $item) }}" style="margin-top:14px">@csrf<button class="btn-pro btn-pro-primary" data-loading="در حال نگارش… (۱ تا ۲ دقیقه)"><i class="fa-solid fa-feather"></i> نگارش مقاله از روی این بریف</button></form>
    @else
      <div class="seo-empty"><i class="fa-solid fa-hourglass-half"></i><b>{{ ContentItem::STATUSES[$item->status] }}</b>{{ $item->reviewer_note }}</div>
    @endif
  </section>

  <div class="seo-stack">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-clipboard-check"></i> ممیزی سئو @include('seo::partials.help', ['k' => 'content.score'])</div><span class="seo-tag {{ ($item->seo_score ?? 0) >= 80 ? 'is-success' : 'is-warning' }}">{{ Fa::n($item->seo_score ?? 0) }}/۱۰۰</span></div>
      @foreach($checks as $label => $ok)
        <div class="seo-check {{ $ok ? 'ok' : 'no' }}"><i class="fa-solid {{ $ok ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>{{ $label }}</div>
      @endforeach
      <hr class="seo-sep">
      <div class="seo-row-gap" style="justify-content:space-between;font-size:12px"><span>طول متن</span><b class="seo-num">{{ Fa::n($item->word_count) }} کلمه</b></div>
      <div class="seo-row-gap" style="justify-content:space-between;font-size:12px;margin-top:6px"><span>هزینه‌ی تولید</span><b class="seo-num">{{ Fa::usd($item->cost_usd) }}</b></div>
      <div class="seo-row-gap" style="justify-content:space-between;font-size:12px;margin-top:6px"><span>پژوهش زنده‌ی وب</span><b>{{ ! empty($brief['research_used']) ? 'انجام شد' : 'خیر' }}</b></div>
    </section>
    @if($ai)
      <section class="seo-card">
        <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-user-check"></i> ممیزی کیفیت @include('seo::partials.help', ['k' => 'content.quality'])</div><span class="seo-tag {{ ($ai['verdict'] ?? '') === 'publish' ? 'is-success' : 'is-warning' }}">{{ ($ai['verdict'] ?? '') === 'publish' ? 'قابل انتشار' : 'نیاز به اصلاح' }}</span></div>
        <div class="seo-grid seo-grid-3" style="gap:8px;text-align:center;margin-bottom:10px">
          <div><div class="seo-kpi-value seo-num" style="font-size:18px">{{ Fa::n($ai['quality'] ?? 0) }}</div><div class="seo-card-sub">کیفیت</div></div>
          <div><div class="seo-kpi-value seo-num" style="font-size:18px">{{ Fa::n($ai['eeat'] ?? 0) }}</div><div class="seo-card-sub">E-E-A-T</div></div>
          <div><div class="seo-kpi-value seo-num" style="font-size:18px">{{ Fa::n($ai['ai_likeness'] ?? 0) }}</div><div class="seo-card-sub">شباهت به متن ماشینی</div></div>
        </div>
        @if(!empty($ai['issues']))<ul style="margin:0;padding-right:18px;font-size:12px;line-height:1.9">@foreach($ai['issues'] as $i)<li>{{ $i }}</li>@endforeach</ul>@endif
      </section>
    @endif
    @if($item->blocks && $item->status !== 'published')
      <section class="seo-card">
        <div class="seo-card-title" style="margin-bottom:8px"><i class="fa-solid fa-rotate"></i> بازنویسی با بازخورد</div>
        <form method="POST" action="{{ route('seo.content.redraft', $item) }}" class="seo-stack" style="gap:8px">@csrf
          <textarea name="note" class="seo-textarea" rows="3" placeholder="مثلاً: مثال‌های فروشگاهی بیشتر، لحن رسمی‌تر، بخش قیمت اضافه شود"></textarea>
          <button class="btn-pro btn-pro-ghost" style="justify-content:center" data-loading="در حال بازنویسی…"><i class="fa-solid fa-feather"></i> بازنویسی</button>
        </form>
      </section>
    @endif
  </div>
</div>
@endsection
