<div class="profile-panel {{ $class ?? '' }}"
     data-panel="{{ $panel }}"
     data-lazy-panel="{{ $panel }}"
     data-endpoint="{{ route('profile.panels', $panel) }}"
     style="display:none;">
  <div class="grid-empty profile-lazy-state" role="status">
    <p>در حال آماده‌سازی این بخش...</p>
  </div>
</div>
