{{-- ══════════════════════════════════════════════════════
     PANEL: ذخیره شده‌ها
══════════════════════════════════════════════════════ --}}
<div class="profile-panel panel-saved" data-panel="saved" style="display:none;">

  @foreach ([
    asset('assets/img/Screenshot-2025-12-09-at-12.33.35-PM.avif'),
    asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg'),
    asset('assets/img/promptbank176.webp'),
    asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'),
  ] as $img)
    <div class="grid-cell">
      <img src="{{ $img }}" alt="" class="grid-img" loading="lazy">
      <div class="saved-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#ffffff">
          <path d="M17 3H7C5.9 3 5 3.9 5 5V21L12 18L19 21V5C19 3.9 18.1 3 17 3Z"/>
        </svg>
      </div>
    </div>
  @endforeach

</div>
