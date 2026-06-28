@extends('layouts.app')

@section('content')
<div class="prd-page" dir="rtl">

  {{-- ═══════════════════════════════════
       دکمه بازگشت — floating top-left
  ═══════════════════════════════════ --}}
  <button type="button" class="prd-back" id="btnBack" aria-label="برگشت">
    <i class="fa-solid fa-chevron-left"></i>
  </button>

  {{-- ═══════════════════════════════════
       لایه اصلی (hero + content) — 2-col on desktop
  ═══════════════════════════════════ --}}
  <div class="prd-main">

    {{-- عکس اصلی محصول --}}
    <div class="prd-hero">
      <img
        src="{{ asset('assets/img/prompt-for-gemini-ai-girl.webp') }}"
        alt="پرتره سینمایی"
        class="prd-hero-img"
        loading="eager"
      >
    </div>

    {{-- محتوا --}}
    <div class="prd-content">

      {{-- ── ردیف آیکون‌ها ── --}}
      <div class="prd-icons">

        {{-- سیو --}}
        <button type="button" class="prd-icon-btn" id="btnBookmark" aria-label="ذخیره">
          <i class="fa-regular fa-bookmark" id="iconBookmark"></i>
        </button>

        {{-- انتشار --}}
        <button type="button" class="prd-icon-btn" id="btnShare" aria-label="انتشار">
          <i class="fa-solid fa-share-nodes"></i>
        </button>

        {{-- جداکننده --}}
        <div class="prd-icons-sep"></div>

        {{-- نوع: عکس یا ویدیو --}}
        <div class="prd-icon-info" title="نوع خروجی">
          <i class="fa-solid fa-image"></i>
        </div>

        {{-- پریمیوم --}}
        <div class="prd-icon-info prd-icon-info--premium" title="پریمیوم">
          <i class="fa-solid fa-crown"></i>
        </div>

        {{-- توکن + عدد --}}
        <div class="prd-icon-info prd-icon-info--token" title="توکن مصرفی">
          <i class="fa-solid fa-bolt"></i>
          <span>۲</span>
        </div>

      </div>

      {{-- ── عنوان ── --}}
      <h1 class="prd-title">پرتره سینمایی</h1>

      {{-- ── توضیحات ── --}}
      <p class="prd-desc">
        یک سبک عکاسی حرفه‌ای با نورپردازی سینماتیک و اتمسفر خاص. مناسب برای پروفایل
        شبکه‌های اجتماعی، لینکدین و هر جایی که می‌خواهید تصویری تأثیرگذار داشته باشید.
      </p>

      {{-- ── دکمه بساز ── --}}
      <a href="/app/create?product={{ $productId ?? '' }}" class="prd-create-btn">
        <i class="fa-solid fa-wand-magic-sparkles" style="font-size:15px;"></i>
        بساز
      </a>

    </div>{{-- /prd-content --}}

  </div>{{-- /prd-main --}}

  {{-- ═══════════════════════════════════
       اسلایدر محصولات مشابه — full-width زیر grid
  ═══════════════════════════════════ --}}
  <div class="prd-similar">

    <div class="home-section-title home-section-title--sub">
      <div>
        <span class="home-section-title-right">محصولات مشابه</span>
        <p class="home-section-title-caption">سبک‌های نزدیک به این محصول</p>
      </div>
      <a href="/app/explore" class="home-section-viewall" style="text-decoration:none;">مشاهده همه</a>
    </div>

    <div class="home-cards-scroll">

      @php
        $simProducts = [
          ['img' => 'elegant-woman-cafe-portrait-by-promptplum.avif',    'name' => 'کافه گرل',       'tag' => 'پرتره',    'type' => 'image', 'tier' => ''],
          ['img' => 'best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'name' => 'فشن استودیو', 'tag' => 'فشن',  'type' => 'image', 'tier' => 'bolt'],
          ['img' => 'moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif', 'name' => 'طبیعت سبز', 'tag' => 'طبیعت', 'type' => 'video', 'tier' => ''],
          ['img' => 'dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg',       'name' => 'مرد سینماتیک',  'tag' => 'پرتره',    'type' => 'video', 'tier' => 'bolt'],
          ['img' => 'promptbank234.webp',                                  'name' => 'برندینگ حرفه‌ای','tag' => 'کسب‌وکار', 'type' => 'image', 'tier' => 'crown'],
        ];
      @endphp

      @foreach($simProducts as $i => $sim)
      <a href="{{ route('app.product', 'sim-' . ($i + 1)) }}"
         class="home-card"
         style="background-image: url('{{ asset('assets/img/' . $sim['img']) }}'); text-decoration:none; display:block;">
        <div class="home-card-overlay"></div>
        <i class="fa-solid fa-{{ $sim['type'] === 'image' ? 'image' : 'video' }} home-card-badge-type"></i>
        @if($sim['tier'])
          <i class="fa-solid fa-{{ $sim['tier'] }} home-card-badge-tier"></i>
        @endif
        <div class="home-card-info">
          <p class="home-card-name">{{ $sim['name'] }}</p>
          <p class="home-card-tag">{{ $sim['tag'] }}</p>
        </div>
      </a>
      @endforeach

    </div>

  </div>{{-- /prd-similar --}}

  {{-- فاصله برای nav --}}
  <div class="prd-nav-spacer"></div>

</div>{{-- /prd-page --}}
@endsection


@push('styles')
<style>
html, body { background: #000; overflow-x: hidden; }
html.light, html.light body { background: #fff; }

/* ══════════════════════════════════════════
   BASE — Mobile First
══════════════════════════════════════════ */

.prd-page {
  max-width: 480px;
  margin: 0 auto;
  min-height: 100vh;
  direction: rtl;
}

/* ─── دکمه بازگشت ─── */
.prd-back {
  position: fixed;
  top: calc(env(safe-area-inset-top, 0px) + 12px);
  left: 14px;
  z-index: 200;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.45);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 50%;
  color: #ffffff;
  font-size: 13px;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: transform 0.12s ease, background 0.18s ease;
}
html.light .prd-back {
  background: rgba(255,255,255,0.72);
  border-color: rgba(0,0,0,0.12);
  color: #000;
}
.prd-back:active { transform: scale(0.88); }

/* ─── wrapper اصلی (mobile: block | desktop: grid) ─── */
.prd-main {
  display: block;
}

/* ─── عکس اصلی ─── */
.prd-hero {
  width: 100%;
  aspect-ratio: 4 / 5;
  max-height: 460px;
  overflow: hidden;
  border-radius: 0 0 10px 10px;
  background: #0d0d0d;
}
html.light .prd-hero { background: #e8e8e8; }

.prd-hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ─── محتوا ─── */
.prd-content {
  padding: 16px 16px 0 16px;
  direction: rtl;
}

/* ─── ردیف آیکون‌ها ─── */
.prd-icons {
  display: flex;
  align-items: center;
  gap: 10px;
  direction: rtl;
  margin-bottom: 16px;
}
.prd-icons-sep { flex: 1; }

/* دکمه‌های اکشن */
.prd-icon-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 50%;
  color: #ffffff;
  font-size: 14px;
  cursor: pointer;
  flex-shrink: 0;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.18s, transform 0.12s, color 0.18s, border-color 0.18s;
}
html.light .prd-icon-btn {
  background: rgba(0,0,0,0.05);
  border-color: rgba(0,0,0,0.1);
  color: #000;
}
.prd-icon-btn:active { transform: scale(0.88); }
.prd-icon-btn.saved {
  background: rgba(11,191,83,0.14);
  border-color: rgba(11,191,83,0.35);
  color: #0BBF53;
}

/* badge های اطلاعاتی */
.prd-icon-info {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 30px;
  padding: 0 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.75);
  white-space: nowrap;
  flex-shrink: 0;
}
html.light .prd-icon-info {
  background: rgba(0,0,0,0.05);
  border-color: rgba(0,0,0,0.09);
  color: rgba(0,0,0,0.6);
}
.prd-icon-info--premium {
  color: #a07af5;
  background: rgba(160,122,245,0.1);
  border-color: rgba(160,122,245,0.25);
}
.prd-icon-info--token {
  color: #f5923a;
  background: rgba(245,146,58,0.08);
  border-color: rgba(245,146,58,0.25);
}

/* ─── عنوان ─── */
.prd-title {
  margin: 0 0 10px 0;
  font-size: 22px;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.35;
  direction: rtl;
}
html.light .prd-title { color: #000; }

/* ─── توضیحات — باکس خاکستری ─── */
.prd-desc {
  margin: 0 0 20px 0;
  font-size: 13px;
  font-weight: 400;
  color: rgba(255,255,255,0.7);
  line-height: 1.85;
  direction: rtl;
  background: #3F3F3F;
  border-radius: 12px;
  padding: 14px 16px;
}
html.light .prd-desc {
  background: #E5E5E5;
  color: rgba(0,0,0,0.6);
}

/* ─── دکمه بساز ─── */
.prd-create-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  width: 70%;
  margin: 0 auto;            /* وسط‌چین */
  height: 54px;
  background: #0BBF53;
  border-radius: 14px;
  color: #ffffff;
  font-size: 17px;
  font-weight: 800;
  font-family: inherit;
  text-decoration: none;
  border: none;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  box-shadow: none;
  transition: transform 0.14s ease, opacity 0.14s ease;
  letter-spacing: 0.5px;
}
.prd-create-btn:active {
  transform: scale(0.96);
  opacity: 0.88;
}

/* ─── اسلایدر مشابه ─── */
.prd-similar {
  padding: 0 16px;
  direction: rtl;
}

.home-section-title {
  margin-top: 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  direction: rtl;
}
.home-section-title--sub { margin-top: 28px; }

.home-section-title-right {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
}
html.light .home-section-title-right { color: #000000; }

.home-section-title-caption {
  margin: 2px 0 0 0;
  font-size: 10px;
  font-weight: 400;
  color: rgba(255,255,255,0.5);
}
html.light .home-section-title-caption { color: rgba(0,0,0,0.45); }

.home-section-viewall {
  flex-shrink: 0;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 10.5px;
  font-weight: 300;
  line-height: 1.2;
  color: #ffffff;
  font-family: inherit;
  cursor: pointer;
  white-space: nowrap;
  text-decoration: none;
  display: inline-block;
}
html.light .home-section-viewall {
  background: rgba(0,0,0,0.05);
  border-color: rgba(0,0,0,0.1);
  color: #000000;
}

.home-cards-scroll {
  display: flex;
  flex-direction: row;
  gap: 2px;
  overflow-x: auto;
  scrollbar-width: none;
  padding-bottom: 4px;
  direction: rtl;
  margin: 12px -16px 0 -16px;
  width: calc(100% + 32px);
}
.home-cards-scroll::-webkit-scrollbar { display: none; }

.home-card {
  aspect-ratio: 4 / 5;
  border-radius: 4px;
  overflow: hidden;
  position: relative;
  background-size: cover;
  background-position: center;
  display: block;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
}
.home-cards-scroll .home-card {
  width: calc(52.8vw);
  max-width: 211px;
  flex-shrink: 0;
}

.home-card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%);
}
.home-card-info {
  position: absolute;
  bottom: 8px;
  right: 8px;
  text-align: right;
}
.home-card-badge-type,
.home-card-badge-tier {
  position: absolute;
  top: 7px;
  color: #ffffff;
  font-size: 11px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.65);
  z-index: 2;
}
.home-card-badge-type { right: 7px; }
.home-card-badge-tier { left: 7px; }
.home-card-name {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
  color: #ffffff;
}
.home-card-tag {
  margin: 0;
  font-size: 10px;
  color: rgba(255,255,255,0.6);
}

/* nav spacer */
.prd-nav-spacer { height: 110px; }

/* ══════════════════════════════════════════
   TABLET — 640px+
   یه‌ستونه ولی فضای بیشتر + hero rounded
══════════════════════════════════════════ */
@media (min-width: 640px) {
  .prd-page {
    max-width: 600px;
  }

  .prd-back {
    left: max(14px, calc((100vw - 600px) / 2 + 14px));
    top: calc(env(safe-area-inset-top, 0px) + 16px);
  }

  .prd-hero {
    border-radius: 14px;
    max-height: 520px;
    margin: 20px 20px 0;
    width: calc(100% - 40px);
  }

  .prd-content {
    padding: 20px 24px 0;
  }

  .prd-similar {
    padding: 0 24px;
  }

  .home-cards-scroll {
    margin-left: -24px;
    margin-right: -24px;
    width: calc(100% + 48px);
  }

  .home-cards-scroll .home-card {
    width: 180px;
    max-width: 180px;
  }
}

/* ══════════════════════════════════════════
   DESKTOP — 1024px+
   دو‌ستونه: hero (sticky) | content
══════════════════════════════════════════ */
@media (min-width: 1024px) {
  .prd-page {
    max-width: 1080px;
    padding: 44px 40px 0;
  }

  .prd-back {
    top: 44px;
    left: max(14px, calc((100vw - 1080px) / 2 + 4px));
  }

  /* ── grid دو‌ستونه ── */
  .prd-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 52px;
    align-items: start;
  }

  /* hero: sticky در ستون چپ */
  .prd-hero {
    margin: 0;
    width: 100%;
    aspect-ratio: 4 / 5;
    max-height: none;
    border-radius: 18px;
    position: sticky;
    top: 24px;
  }

  /* content: ستون راست */
  .prd-content {
    padding: 4px 0 0;
  }

  .prd-title {
    font-size: clamp(22px, 2.2vw, 30px);
  }

  .prd-desc {
    font-size: clamp(13px, 1.1vw, 15px);
  }

  .prd-create-btn {
    height: 58px;
    font-size: clamp(16px, 1.4vw, 19px);
    border-radius: 16px;
  }

  /* similar: زیر grid — full width */
  .prd-similar {
    padding: 0;
    margin-top: 12px;
  }

  .home-section-title--sub {
    margin-top: 40px;
  }

  /* اسلایدر روی desktop بدون negative margin */
  .home-cards-scroll {
    margin-left: 0;
    margin-right: 0;
    width: 100%;
    gap: 8px;
    padding-bottom: 8px;
  }

  .home-cards-scroll .home-card {
    width: 200px;
    max-width: 220px;
    border-radius: 10px;
  }

  .prd-nav-spacer { height: 60px; }
}

/* ══════════════════════════════════════════
   LARGE DESKTOP — 1280px+
   فضای بیشتر
══════════════════════════════════════════ */
@media (min-width: 1280px) {
  .prd-page {
    max-width: 1200px;
    padding: 48px 56px 0;
  }

  .prd-main {
    grid-template-columns: 5fr 4fr;
    gap: 64px;
  }

  .prd-back {
    left: max(14px, calc((100vw - 1200px) / 2 + 4px));
  }

  .home-cards-scroll .home-card {
    width: 220px;
    max-width: 240px;
  }
}
</style>
@endpush


@push('scripts')
<script>
(function () {
  'use strict';

  /* بازگشت */
  document.getElementById('btnBack').addEventListener('click', function () {
    window.history.length > 1 ? window.history.back() : (window.location.href = '/app/home');
  });

  /* اشتراک‌گذاری */
  document.getElementById('btnShare').addEventListener('click', function () {
    if (navigator.share) {
      navigator.share({
        title: document.querySelector('.prd-title').textContent.trim(),
        url: window.location.href
      }).catch(function(){});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href).then(function () {
        showToast('لینک کپی شد');
      });
    }
  });

  /* سیو */
  var saved  = false;
  var btnBm  = document.getElementById('btnBookmark');
  var iconBm = document.getElementById('iconBookmark');
  btnBm.addEventListener('click', function () {
    saved = !saved;
    iconBm.className = saved ? 'fa-solid fa-bookmark' : 'fa-regular fa-bookmark';
    btnBm.classList.toggle('saved', saved);
    showToast(saved ? 'ذخیره شد' : 'از ذخیره‌ها حذف شد');
  });

  /* toast */
  function showToast(msg) {
    var t = document.getElementById('_toast');
    if (!t) {
      t = document.createElement('div');
      t.id = '_toast';
      Object.assign(t.style, {
        position:'fixed', bottom:'150px', left:'50%',
        transform:'translateX(-50%) translateY(8px)',
        background:'rgba(25,25,30,0.92)', color:'#fff',
        fontSize:'13px', fontWeight:'600', fontFamily:'inherit',
        padding:'8px 18px', borderRadius:'10px',
        backdropFilter:'blur(12px)', WebkitBackdropFilter:'blur(12px)',
        border:'1px solid rgba(255,255,255,0.1)',
        zIndex:'999', opacity:'0',
        transition:'opacity 0.2s, transform 0.2s',
        whiteSpace:'nowrap', pointerEvents:'none',
      });
      document.body.appendChild(t);
    }
    t.textContent = msg;
    clearTimeout(t._t);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        t.style.opacity = '1';
        t.style.transform = 'translateX(-50%) translateY(0)';
        t._t = setTimeout(function () {
          t.style.opacity = '0';
          t.style.transform = 'translateX(-50%) translateY(6px)';
        }, 2000);
      });
    });
  }

}());
</script>
@endpush
