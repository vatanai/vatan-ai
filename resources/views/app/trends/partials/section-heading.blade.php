<div class="trends-section-heading">
  <div>
    <h2>{{ $title }}</h2>
    @if(!empty($description))
      <p>{{ $description }}</p>
    @endif
  </div>
  @if(!empty($actionUrl))
    <a href="{{ $actionUrl }}" class="trends-section-action">مشاهده همه <i class="fa-solid fa-arrow-left"></i></a>
  @endif
</div>
