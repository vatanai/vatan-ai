@foreach($article->content_blocks ?? [] as $block)
  @switch($block['type'] ?? '')
    @case('lead')
      <p class="article-lead">{{ $block['content'] ?? '' }}</p>
      @break
    @case('heading')
      @php
        $level = in_array((int) ($block['level'] ?? 2), [2,3,4], true) ? (int) $block['level'] : 2;
        $headingText = $block['content'] ?? $block['title'] ?? '';
        $headingId = \Illuminate\Support\Str::slug($headingText) ?: 'section-' . $loop->iteration;
      @endphp
      <h{{ $level }} id="{{ $headingId }}">{{ $headingText }}</h{{ $level }}>
      @break
    @case('paragraph')
      <p>{!! nl2br(e($block['content'] ?? '')) !!}</p>
      @break
    @case('note')
      <aside class="article-note"><i class="fa-regular fa-lightbulb"></i><div>@if(!empty($block['title']))<strong>{{ $block['title'] }}</strong>@endif<p>{!! nl2br(e($block['content'] ?? '')) !!}</p></div></aside>
      @break
    @case('quote')
      <blockquote>{!! nl2br(e($block['content'] ?? '')) !!}</blockquote>
      @break
    @case('prompt')
      <section class="article-prompt" data-prompt-box>
        <div><strong>{{ $block['title'] ?? 'پرامپت آماده' }}</strong><button type="button" data-copy-prompt><i class="fa-regular fa-copy"></i> کپی پرامپت</button></div>
        <pre>{{ $block['content'] ?? '' }}</pre>
      </section>
      @break
    @case('image')
      <figure><img src="{{ str_starts_with($block['url'] ?? '', 'http') ? $block['url'] : asset(ltrim($block['url'] ?? '', '/')) }}" alt="{{ $block['alt'] ?? $block['caption'] ?? '' }}" loading="lazy">@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
      @break
    @case('video')
      <figure><video controls preload="metadata" playsinline><source src="{{ str_starts_with($block['url'] ?? '', 'http') ? $block['url'] : asset(ltrim($block['url'] ?? '', '/')) }}"></video>@if(!empty($block['caption']))<figcaption>{{ $block['caption'] }}</figcaption>@endif</figure>
      @break
    @case('list')
      <ul>@foreach($block['items'] ?? [] as $item)<li>{{ $item }}</li>@endforeach</ul>
      @break
    @case('cta')
      <section class="article-inline-cta"><div><strong>{{ $block['title'] ?? 'در وطن امتحانش کن' }}</strong><p>{{ $block['content'] ?? '' }}</p></div>@if(!empty($block['button_url']))<a href="{{ $block['button_url'] }}" data-article-event="product_cta_click">{{ $block['button_label'] ?? 'شروع ساخت' }}</a>@endif</section>
      @break
  @endswitch
@endforeach
