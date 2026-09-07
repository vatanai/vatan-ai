<nav class="finance-tabs" aria-label="بخش‌های مالی">
  @foreach($sections as $key => $label)
    <a href="{{ route('admin.finance.show', ['section' => $key]) }}" class="finance-tab {{ $section === $key ? 'active' : '' }}">{{ $label }}</a>
  @endforeach
</nav>
