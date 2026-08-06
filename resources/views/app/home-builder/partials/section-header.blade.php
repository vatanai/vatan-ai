@if($section->title_fa || $section->subtitle_fa || !empty($viewAllUrl))
  <div class="home-section-title home-section-title--sub">
    <div>
      @if($section->title_fa)<span class="home-section-title-right">{{ $section->title_fa }}</span>@endif
      @if($section->subtitle_fa)<p class="home-section-title-caption">{{ $section->subtitle_fa }}</p>@endif
    </div>
    @if(!empty($viewAllUrl))
      <a class="home-section-viewall" href="{{ $viewAllUrl }}">مشاهده همه</a>
    @endif
  </div>
@endif
