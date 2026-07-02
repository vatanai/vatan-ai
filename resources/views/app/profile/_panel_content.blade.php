{{-- ══════════════════════════════════════════════════════
     PANEL: محتوا — گرید تصاویر (پیش‌فرض فعال)
══════════════════════════════════════════════════════ --}}
@php
  $gridImages = [
    ['url' => asset('assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif'),                                                           'video' => false],
    ['url' => asset('assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg'), 'video' => true],
    ['url' => asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp'),                                   'video' => false],
    ['url' => asset('assets/img/ai-photo-editor-prompt.webp'),                                                                         'video' => false],
    ['url' => asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg'),                                             'video' => true],
    ['url' => asset('assets/img/best-friends-ai-prompt-2.webp'),                                                                       'video' => false],
    ['url' => asset('assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg'), 'video' => false],
    ['url' => asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg'),                                                         'video' => false],
    ['url' => asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif'),                                                      'video' => true],
    ['url' => asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp'),                                           'video' => false],
    ['url' => asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif'),            'video' => false],
    ['url' => asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'),                        'video' => false],
  ];
@endphp

<div class="profile-panel panel-grid" data-panel="grid">
  @foreach ($gridImages as $item)
    <div class="grid-cell">
      <img src="{{ $item['url'] }}" alt="" class="grid-img" loading="lazy">
      @if($item['video'])
        <i class="fa-solid fa-video cell-badge"></i>
      @endif
    </div>
  @endforeach
</div>
