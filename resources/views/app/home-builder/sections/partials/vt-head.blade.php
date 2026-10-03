@if($section->title_fa || $section->subtitle_fa || !empty($viewAllUrl))
  <div class="vt-head">
    <div>
      @if($section->title_fa)<h2>{{ $section->title_fa }}</h2>@endif
      @if($section->subtitle_fa)<p>{{ $section->subtitle_fa }}</p>@endif
    </div>
    @if(!empty($viewAllUrl))<a class="vt-more" href="{{ $viewAllUrl }}">{{ $viewAllLabel ?? 'مشاهده همه' }}</a>@endif
  </div>
@endif
