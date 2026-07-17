{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: محصولات مشابه — بخش ۳ صفحه محصول (Slider)
     از کالکشن $similar (از ProductGenerateController::show) استفاده می‌کند.
     استایل کارت/اسلایدر دقیقاً همان home-card / home-cards-scroll در app/home.blade.php
     برای یکپارچگی کامل بصری بین صفحه خانه و صفحه محصول تکرار شده است.
     ═══════════════════════════════════════════════════════════════ --}}
@if(isset($similar) && $similar->isNotEmpty())
<section class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 pb-10 sm:pb-14">
  <h2 class="text-sm font-black text-[var(--text-primary)] mb-1">محصولات مشابه</h2>
  <p class="text-[11px] text-[var(--text-secondary)] mb-1">این محصولات هم ممکن است برای شما جالب باشند</p>

  <div class="home-cards-scroll">
    @foreach($similar as $__sp)
      <a class="home-card" href="{{ route('app.product', $__sp->route_slug) }}" style="background-image: url('{{ $__sp->displayImageUrl() }}');">
        <div class="home-card-overlay"></div>
        <i class="fa-solid {{ $__sp->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
        @if ($__sp->is_featured)
          <i class="fa-solid fa-crown home-card-badge-tier"></i>
        @elseif ($__sp->is_new)
          <i class="fa-solid fa-bolt home-card-badge-tier"></i>
        @endif
        <div class="home-card-info">
          <p class="home-card-name">{{ $__sp->name_fa }}</p>
          <p class="home-card-tag">{{ $__sp->subcategory ?: $__sp->category }}</p>
        </div>
      </a>
    @endforeach
  </div>
</section>

<style>
  .home-cards-scroll {
    display: flex;
    flex-direction: row;
    gap: 10px;
    overflow-x: auto;
    overflow-y: visible;
    scrollbar-width: none;
    padding: 10px 0 14px 0;
    direction: rtl;
    isolation: isolate;
  }
  .home-cards-scroll::-webkit-scrollbar { display: none; }

  .home-card {
    aspect-ratio: 4 / 5;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    background-size: cover;
    background-position: center;
    cursor: pointer;
    flex: 0 0 auto;
    width: 150px;
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s ease;
    will-change: transform;
    transform-origin: center center;
    z-index: 0;
  }
  .home-card:hover { transform: scale(1.035) translateY(-2px); box-shadow: 0 14px 30px rgba(0, 0, 0, 0.45); z-index: 20; }
  .home-card:hover .home-card-overlay { background: linear-gradient(to top, rgba(0, 0, 0, 0.78) 0%, transparent 65%); }
  .home-card-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 60%); transition: background 0.35s ease; }
  .home-card-info { position: absolute; bottom: 8px; right: 8px; text-align: right; }
  .home-card-badge-type, .home-card-badge-tier { position: absolute; top: 7px; color: #ffffff; font-size: 11px; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.65); z-index: 2; }
  .home-card-badge-type { right: 7px; }
  .home-card-badge-tier { left: 7px; }
  .home-card-name { margin: 0; font-size: 12px; font-weight: 700; color: #ffffff; }
  .home-card-tag { margin: 0; font-size: 10px; color: rgba(255, 255, 255, 0.6); }

  @media (min-width: 640px) { .home-card { width: 180px; } }
  @media (min-width: 1024px) { .home-card { width: 200px; border-radius: 10px; } .home-cards-scroll { gap: 14px; } }
  @media (min-width: 1280px) { .home-card { width: 220px; } }
</style>
@endif
