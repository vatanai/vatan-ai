@extends('layouts.app')

@section('page_title', 'صفحه محصول - وطن AI')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════
   صفحه محصول (product-details)
   رنگ‌ها فقط از توکن‌های رسمی اپ (resources/css/app.css):
   --bg-page / --bg-surface / --bg-card / --text-primary /
   --text-secondary / --border-subtle / --green
═══════════════════════════════════════════════════════ */

/* ═══ قانون حاشیه استاندارد صفحه (سراسر سایت — دسکتاپ) ═══
   الگوبرداری از چیدمان pixifield:
   محتوا وسط‌چین با سقف عرض ۱۴۰۰px و حاشیه شناور از هر طرف:
   --page-margin: clamp(24px, 5vw, 96px)
   یعنی: ۵٪ عرض صفحه از هر طرف، حداقل ۲۴px و حداکثر ۹۶px */
:root{
  --page-max: 1400px;
  --page-margin: clamp(24px, 5vw, 96px);
}
.page-container{
  width:100%;
  max-width:calc(var(--page-max) + (2 * var(--page-margin)));
  margin-inline:auto;
  padding-inline:var(--page-margin);
}

.pd-shell{
  display:flex;
  flex-direction:row;      /* در RTL: فرزند اول = سمت راست */
  width:100%;
  background:var(--bg-page);
  color:var(--text-primary);
}

/* ── دسکتاپ و تبلت (از 768px به بالا): ارتفاع سکشن اول = یک صفحه کامل ── */
@media (min-width:768px){
  .pd-shell{
    height:calc(100vh - 64px); /* 64px هدر فیکس بالای صفحه */
    overflow:hidden;
  }
}

/* ═══════════ ستون راست: توضیحات محصول ═══════════ */
.pd-info{
  flex:1 1 0;
  min-width:0;
  background:var(--bg-surface);
  border-inline-start:1px solid var(--border-subtle); /* لبه‌ی مجاور تصاویر */
  display:flex;
  flex-direction:column;
}
@media (min-width:768px){
  .pd-info{ min-width:340px; }
  .pd-info-scroll{ overflow-y:auto; }
}
@media (min-width:1280px){
  .pd-info{ min-width:380px; }
}
/* اسکرول‌بار کانتینر توضیحات مخفی (اسکرول کار می‌کند) */
.pd-info-scroll{
  flex:1 1 auto;
  padding:28px 26px 40px;
  display:flex;
  flex-direction:column;
  gap:22px;
  scrollbar-width:none;
}
.pd-info-scroll::-webkit-scrollbar{ width:0; height:0; display:none; }

/* نام محصول (۱۰٪ کوچک‌تر) */
.pd-title{
  font-size:23px;
  font-weight:800;
  line-height:1.35;
  margin:0;
}

/* دسته‌بندی‌ها (باکس‌دار) + تگ‌ها (بدون باکس) */
.pd-meta{ display:flex; flex-direction:column; gap:12px; }
.pd-cats{ display:flex; flex-wrap:wrap; gap:8px; }
.pd-cat{
  height:26px;
  padding:0 11px;
  display:inline-flex;
  align-items:center;
  border-radius:8px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  font-size:11.5px;
  font-weight:700;
  color:var(--text-primary);
  cursor:pointer;
  transition:all .2s ease;
  user-select:none;
}
.pd-cat:hover{ border-color:var(--green); }
.pd-tags{ display:flex; flex-wrap:wrap; gap:4px 12px; }
.pd-tag{
  font-size:11px;
  font-weight:600;
  color:var(--text-secondary);
  cursor:pointer;
  transition:color .2s ease;
}
.pd-tag:hover{ color:var(--green); }

/* باکس توضیحات */
.pd-desc-box{
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  border-radius:12px;
  padding:18px 18px 20px;
  display:flex;
  flex-direction:column;
  gap:12px;
}
.pd-desc-box h2{
  font-size:15px;
  font-weight:800;
  margin:0;
}
.pd-desc-text{
  font-size:13.5px;
  line-height:2;
  color:var(--text-secondary);
  margin:0;
  max-height:170px;
  overflow-y:auto;
  scrollbar-width:none;
  padding-inline-end:6px;
}
.pd-desc-text::-webkit-scrollbar{ width:0; display:none; }

/* ردیف سیو / انتشار / توکن — هر سه با خمیدگی ۱۲ و ارتفاع ۴۸ (دو باکس کناری مربع ۴۸×۴۸) */
.pd-actions{ display:flex; gap:10px; align-items:stretch; }
.pd-token{
  flex:1 1 auto;
  height:48px;
  border-radius:12px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  font-size:13.5px;
  font-weight:700;
  color:var(--text-primary);
}
.pd-token svg{ color:var(--green); }
.pd-token b{ color:var(--green); font-weight:800; }
.pd-iconbtn{
  width:48px;
  height:48px;
  flex:0 0 48px;
  border-radius:12px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  display:flex;
  align-items:center;
  justify-content:center;
  color:var(--text-primary);
  cursor:pointer;
  transition:all .2s ease;
}
.pd-iconbtn:hover{ border-color:var(--green); transform:translateY(-1px); }
.pd-iconbtn.is-on{ color:var(--green); border-color:var(--green); }
.pd-iconbtn.is-on svg{ fill:var(--green); }

/* گالری‌ها */
.pd-gal h2{
  font-size:15px;
  font-weight:800;
  margin:0 0 12px;
  display:flex;
  align-items:center;
  gap:8px;
}
.pd-gal h2 span{
  font-size:12px;
  font-weight:600;
  color:var(--text-secondary);
}
.pd-gal-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:8px;
}
.pd-gal-grid img{
  width:100%;
  aspect-ratio:1/1;
  object-fit:cover;
  border-radius:12px;
  border:1px solid var(--border-subtle);
  cursor:pointer;
  transition:all .2s ease;
}
.pd-gal-grid img:hover{ border-color:var(--green); transform:translateY(-2px); }
.pd-gal-grid img.is-viewing{ border:2px solid var(--green); }

/* ═══════════ ستون چپ: نمایش بزرگ تصویر ═══════════ */
.pd-stage{
  flex:0 1 1200px;          /* عرض بخش تصاویر: ۱۲۰۰ */
  min-width:0;
  position:relative;
  background:var(--bg-page);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
}

/* دکمه برگشت (ضربدر) — بالا سمت چپ بخش تصویر */
.pd-close{
  position:absolute;
  top:16px;
  inset-inline-end:20px;
  width:40px;
  height:40px;
  border-radius:12px;
  background:transparent;
  border:1px solid var(--border-subtle);
  color:var(--text-primary);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .2s ease;
  z-index:4;
}
.pd-close:hover{ border-color:var(--green); box-shadow:0 0 0 3px rgba(207,254,0,.18); }

/* شمارنده بالا */
.pd-counter{
  position:absolute;
  top:20px;
  inset-inline-start:24px;
  font-size:15px;
  font-weight:700;
  color:var(--text-secondary);
  letter-spacing:1px;
  z-index:3;
}

/* تصویر اصلی */
.pd-main{
  width:100%;
  height:100%;
  display:flex;
  align-items:center;
  justify-content:center;
}
.pd-main img{
  max-width:100%;
  max-height:100%;
  border-radius:12px;
  object-fit:contain;
  background:var(--bg-card);
}

/* ═══════════ سکشن دوم: محصولات مشابه (اسلایدر) ═══════════ */
.pd-similar{
  background:var(--bg-page);
  color:var(--text-primary);
  padding:48px 0 64px;   /* حاشیه افقی از قانون --page-margin (کلاس .page-container) می‌آید */
  border-top:1px solid var(--border-subtle);
}
.pd-similar-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom:22px;
}
.pd-similar-head h2{
  font-size:22px;
  font-weight:800;
  margin:0;
}
.pd-similar-nav{ display:flex; gap:8px; }
.pd-similar-nav button{
  width:40px;
  height:40px;
  border-radius:12px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  color:var(--text-primary);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .2s ease;
}
.pd-similar-nav button:hover{ border-color:var(--green); }
.pd-similar-track{
  display:flex;
  gap:16px;
  overflow-x:auto;
  scroll-behavior:smooth;
  scrollbar-width:none;
  padding-bottom:6px;
}
.pd-similar-track::-webkit-scrollbar{ display:none; }
.pd-scard{
  flex:0 0 236px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  border-radius:12px;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  cursor:pointer;
  transition:all .25s ease;
}
.pd-scard:hover{ transform:translateY(-4px); border-color:var(--green); }
.pd-scard img{
  width:100%;
  aspect-ratio:1/1;
  object-fit:cover;
}
.pd-scard-body{
  padding:12px 14px 14px;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.pd-scard-cat{
  align-self:flex-start;
  height:22px;
  padding:0 9px;
  display:inline-flex;
  align-items:center;
  border-radius:8px;
  background:var(--bg-surface);
  border:1px solid var(--border-subtle);
  font-size:10.5px;
  font-weight:700;
  color:var(--text-secondary);
}
.pd-scard-title{
  font-size:14px;
  font-weight:800;
  margin:0;
  line-height:1.5;
  overflow:hidden;
  display:-webkit-box;
  -webkit-line-clamp:1;
  -webkit-box-orient:vertical;
}
.pd-scard-desc{
  font-size:11.5px;
  line-height:1.7;
  color:var(--text-secondary);
  margin:0;
  overflow:hidden;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
}

/* ═══════════ فقط موبایل (زیر 768px) — تبلت دقیقاً مثل دسکتاپ است ═══════════ */
@media (max-width:767px){
  .pd-shell{ flex-direction:column; }
  .pd-stage{
    order:-1;               /* تصاویر بالا */
    flex:none;
    width:100%;
    padding:16px;
    min-height:52vh;
  }
  .pd-main img{ max-height:50vh; }
  .pd-info{ border-inline-start:none; border-top:1px solid var(--border-subtle); }
  .pd-info-scroll{ padding:22px 18px 40px; }
  .pd-title{ font-size:20px; }
  .pd-similar{ padding:36px 0 110px; } /* جا برای نویگیشن پایین موبایل */
}

/* توست کوچک */
.pd-toast{
  position:fixed;
  bottom:28px;
  left:50%;
  transform:translateX(-50%) translateY(20px);
  background:var(--bg-card);
  border:1px solid var(--green);
  color:var(--text-primary);
  padding:10px 22px;
  border-radius:999px;
  font-size:13.5px;
  font-weight:700;
  opacity:0;
  pointer-events:none;
  transition:all .3s ease;
  z-index:80;
}
.pd-toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }

/* ═══ دکمه «بساز» — همان هویت بصری دکمه اصلی «شروع ساخت» پروژه ═══ */
.vatan-gen-btn {
  --black-700: hsla(0 0% 12% / 1);
  --border_radius: 15.6px;
  --transtion: 0.3s ease-in-out;
  --offset: 2px;
  cursor: pointer;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  box-sizing: border-box;
  transform-origin: center;
  padding: 1rem 2rem;
  background-color: transparent;
  border: none;
  border-radius: var(--border_radius);
  transform: scale(calc(1 + (var(--active, 0) * 0.02)));
  transition: transform var(--transtion);
  font-family: 'YekanBakh', sans-serif;
}
.vatan-gen-btn::before {
  content: "";
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 100%; height: 100%;
  background-color: var(--black-700);
  border-radius: var(--border_radius);
  box-shadow: inset 0 0.5px hsl(0, 0%, 100%), inset 0 -1px 2px 0 hsl(0, 0%, 0%),
    0px 4px 10px -4px hsla(0 0% 0% / calc(1 - var(--active, 0))),
    0 0 0 calc(var(--active, 0) * 0.3rem) hsl(71 100% 50% / 0.7);
  transition: all var(--transtion);
  z-index: 0;
}
.vatan-gen-btn::after {
  content: "";
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 100%; height: 100%;
  background-color: hsla(71 90% 50% / 0.7);
  background-image: radial-gradient(at 51% 89%, hsla(80, 85%, 62%, 1) 0px, transparent 50%),
    radial-gradient(at 100% 100%, hsla(71, 100%, 50%, 1) 0px, transparent 50%),
    radial-gradient(at 22% 91%, hsla(95, 75%, 45%, 1) 0px, transparent 50%);
  background-position: top;
  opacity: var(--active, 0);
  border-radius: var(--border_radius);
  transition: opacity var(--transtion);
  z-index: 2;
}
.vatan-gen-btn:is(:hover, :focus-visible) { --active: 1; }
.vatan-gen-btn:active { transform: scale(0.99); }
.vatan-gen-btn .dots_border {
  --size_border: calc(100% + 2px);
  overflow: hidden;
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: var(--size_border); height: var(--size_border);
  background-color: transparent;
  border-radius: var(--border_radius);
  z-index: -10;
}
.vatan-gen-btn .dots_border::before {
  content: "";
  position: absolute;
  top: 30%; left: 50%;
  transform-origin: left;
  transform: rotate(0deg);
  width: 100%; height: 2rem;
  background-color: white;
  mask: linear-gradient(transparent 0%, white 120%);
  animation: vatanGenBtnRotate 2s linear infinite;
}
@keyframes vatanGenBtnRotate { to { transform: rotate(360deg); } }
.vatan-gen-btn .sparkle { position: relative; z-index: 10; width: 1.5rem; flex-shrink: 0; }
.vatan-gen-btn .sparkle .path { fill: currentColor; stroke: currentColor; transform-origin: center; color: #cffe00; }
.vatan-gen-btn:is(:hover, :focus) .sparkle .path { animation: vatanGenBtnPath 1.5s linear 0.5s infinite; }
.vatan-gen-btn .sparkle .path:nth-child(1) { --scale_path_1: 1.2; }
.vatan-gen-btn .sparkle .path:nth-child(2) { --scale_path_2: 1.2; }
.vatan-gen-btn .sparkle .path:nth-child(3) { --scale_path_3: 1.2; }
@keyframes vatanGenBtnPath {
  0%, 34%, 71%, 100% { transform: scale(1); }
  17% { transform: scale(var(--scale_path_1, 1)); }
  49% { transform: scale(var(--scale_path_2, 1)); }
  83% { transform: scale(var(--scale_path_3, 1)); }
}
.vatan-gen-btn .text_button {
  position: relative;
  z-index: 10;
  background-image: linear-gradient(90deg, hsla(71 100% 50% / 1) 0%, hsla(71 100% 50% / var(--active, 0)) 120%);
  background-clip: text;
  -webkit-background-clip: text;
  font-size: 1rem;
  font-weight: 800;
  color: transparent;
}
</style>
@endpush

@section('content')

{{-- ═══════════ سکشن ۱: توضیحات (راست) + تصاویر (چپ) — فول‌صفحه ═══════════ --}}
<div class="pd-shell" dir="rtl">

  {{-- ═══════ سمت راست: توضیحات محصول ═══════ --}}
  <aside class="pd-info">
    <div class="pd-info-scroll">

      {{-- ۱) نام محصول --}}
      <h1 class="pd-title">پرتره سلفی فوق واقع‌گرایانه</h1>

      {{-- ۲) دسته‌بندی‌ها (باکس‌دار) + تگ‌ها (بدون باکس) --}}
      <div class="pd-meta">
        <div class="pd-cats">
          <span class="pd-cat">پرتره و چهره</span>
          <span class="pd-cat">عکاسی واقع‌گرایانه</span>
        </div>
        <div class="pd-tags">
          <span class="pd-tag"># سلفی</span>
          <span class="pd-tag"># واقع‌گرایانه</span>
          <span class="pd-tag"># نور طبیعی</span>
          <span class="pd-tag"># کلوزآپ</span>
          <span class="pd-tag"># پروفایل</span>
        </div>
      </div>

      {{-- ۳) توضیحات داخل باکس --}}
      <div class="pd-desc-box">
        <h2>توضیحات محصول</h2>
        <p class="pd-desc-text">این محصول یک پرتره‌ی سلفی فوق واقع‌گرایانه از روی تصویر مرجع شما می‌سازد؛ چهره، رنگ پوست، مدل مو، تناسبات صورت، عینک، لب‌ها و چشم‌ها دقیقاً حفظ می‌شوند و هیچ تغییری در ویژگی‌های چهره داده نمی‌شود. حفظ هویت چهره در این مدل بالاترین اولویت را دارد. نورپردازی به‌صورت نور روز طبیعی و نرم اعمال می‌شود تا خروجی کاملاً شبیه یک عکس واقعی از دوربین گوشی باشد. مناسب برای ساخت عکس پروفایل، محتوای شبکه‌های اجتماعی و تصاویر شخصی با کیفیت بالا.</p>
      </div>

      {{-- ۴) سیو / انتشار / میزان توکن مصرفی --}}
      <div class="pd-actions">
        <div class="pd-token" title="میزان توکن مصرفی">
          <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
          <span>مصرف هر ساخت:</span>
          <b>۲۰ توکن</b>
        </div>
        <button type="button" class="pd-iconbtn" id="pdSaveBtn" title="ذخیره" aria-label="ذخیره">
          <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
        </button>
        <button type="button" class="pd-iconbtn" id="pdShareBtn" title="انتشار" aria-label="انتشار">
          <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" x2="12" y1="2" y2="15"/></svg>
        </button>
      </div>

      {{-- ۵) دکمه بساز — همان دکمه اصلی پروژه --}}
      <button type="button" class="vatan-gen-btn" id="pdBuildBtn" aria-label="بساز">
        <div class="dots_border"></div>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="sparkle">
          <path class="path" stroke-linejoin="round" stroke-linecap="round" stroke="black" fill="black" d="M14.187 8.096L15 5.25L15.813 8.096C16.0231 8.83114 16.4171 9.50062 16.9577 10.0413C17.4984 10.5819 18.1679 10.9759 18.903 11.186L21.75 12L18.904 12.813C18.1689 13.0231 17.4994 13.4171 16.9587 13.9577C16.4181 14.4984 16.0241 15.1679 15.814 15.903L15 18.75L14.187 15.904C13.9769 15.1689 13.5829 14.4994 13.0423 13.9587C12.5016 13.4181 11.8321 13.0241 11.097 12.814L8.25 12L11.096 11.187C11.8311 10.9769 12.5006 10.5829 13.0413 10.0423C13.5819 9.50162 13.9759 8.83214 14.186 8.097L14.187 8.096Z"></path>
          <path class="path" stroke-linejoin="round" stroke-linecap="round" stroke="black" fill="black" d="M6 14.25L5.741 15.285C5.59267 15.8785 5.28579 16.4206 4.85319 16.8532C4.42059 17.2858 3.87853 17.5927 3.285 17.741L2.25 18L3.285 18.259C3.87853 18.4073 4.42059 18.7142 4.85319 19.1468C5.28579 19.5794 5.59267 20.1215 5.741 20.715L6 21.75L6.259 20.715C6.40725 20.1216 6.71398 19.5796 7.14639 19.147C7.5788 18.7144 8.12065 18.4075 8.714 18.259L9.75 18L8.714 17.741C8.12065 17.5925 7.5788 17.2856 7.14639 16.853C6.71398 16.4204 6.40725 15.8784 6.259 15.285L6 14.25Z"></path>
          <path class="path" stroke-linejoin="round" stroke-linecap="round" stroke="black" fill="black" d="M6.5 4L6.303 4.5915C6.24777 4.75718 6.15472 4.90774 6.03123 5.03123C5.90774 5.15472 5.75718 5.24777 5.5915 5.303L5 5.5L5.5915 5.697C5.75718 5.75223 5.90774 5.84528 6.03123 5.96877C6.15472 6.09226 6.24777 6.24282 6.303 6.4085L6.5 7L6.697 6.4085C6.75223 6.24282 6.84528 6.09226 6.96877 5.96877C7.09226 5.84528 7.24282 5.75223 7.4085 5.697L8 5.5L7.4085 5.303C7.24282 5.24777 7.09226 5.15472 6.96877 5.03123C6.84528 4.90774 6.75223 4.75718 6.697 4.5915L6.5 4Z"></path>
        </svg>
        <span class="text_button">بساز</span>
      </button>

      {{-- ۶) تصاویر محصول --}}
      <div class="pd-gal">
        <h2>تصاویر محصول</h2>
        <div class="pd-gal-grid" id="pdGalProduct">
          <img src="https://picsum.photos/id/1027/300/300" data-full="https://picsum.photos/id/1027/840/1260" alt="تصویر محصول ۱" loading="lazy">
          <img src="https://picsum.photos/id/338/300/300"  data-full="https://picsum.photos/id/338/840/1260"  alt="تصویر محصول ۲" loading="lazy">
          <img src="https://picsum.photos/id/823/300/300"  data-full="https://picsum.photos/id/823/840/1260"  alt="تصویر محصول ۳" loading="lazy">
          <img src="https://picsum.photos/id/996/300/300"  data-full="https://picsum.photos/id/996/840/1260"  alt="تصویر محصول ۴" loading="lazy">
          <img src="https://picsum.photos/id/64/300/300"   data-full="https://picsum.photos/id/64/840/1260"   alt="تصویر محصول ۵" loading="lazy">
          <img src="https://picsum.photos/id/65/300/300"   data-full="https://picsum.photos/id/65/840/1260"   alt="تصویر محصول ۶" loading="lazy">
        </div>
      </div>

      {{-- ۷) تصاویر خام --}}
      <div class="pd-gal">
        <h2>تصاویر خام</h2>
        <div class="pd-gal-grid" id="pdGalRaw">
          <img src="https://picsum.photos/id/1005/300/300" data-full="https://picsum.photos/id/1005/840/1260" alt="تصویر خام ۱" loading="lazy">
          <img src="https://picsum.photos/id/1011/300/300" data-full="https://picsum.photos/id/1011/840/1260" alt="تصویر خام ۲" loading="lazy">
          <img src="https://picsum.photos/id/1012/300/300" data-full="https://picsum.photos/id/1012/840/1260" alt="تصویر خام ۳" loading="lazy">
          <img src="https://picsum.photos/id/91/300/300"   data-full="https://picsum.photos/id/91/840/1260"   alt="تصویر خام ۴" loading="lazy">
          <img src="https://picsum.photos/id/903/300/300"  data-full="https://picsum.photos/id/903/840/1260"  alt="تصویر خام ۵" loading="lazy">
          <img src="https://picsum.photos/id/22/300/300"   data-full="https://picsum.photos/id/22/840/1260"   alt="تصویر خام ۶" loading="lazy">
        </div>
      </div>

    </div>
  </aside>

  {{-- ═══════ سمت چپ: نمایش بزرگ تصویر انتخاب‌شده ═══════ --}}
  <section class="pd-stage" aria-label="نمایش بزرگ تصویر محصول">

    {{-- شمارنده --}}
    <div class="pd-counter" id="pdCounter">۱ / ۱۲</div>

    {{-- دکمه برگشت به صفحه قبل --}}
    <button type="button" class="pd-close" id="pdCloseBtn" title="برگشت" aria-label="برگشت به صفحه قبل">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>

    {{-- تصویر اصلی --}}
    <div class="pd-main">
      <img id="pdMainImg" src="https://picsum.photos/id/1027/840/1260" alt="تصویر اصلی محصول">
    </div>

  </section>

</div>

{{-- ═══════════ سکشن ۲: محصولات مشابه (اسلایدر افقی) ═══════════ --}}
<section class="pd-similar" dir="rtl">
  <div class="page-container">

  <div class="pd-similar-head">
    <h2>محصولات مشابه</h2>
    <div class="pd-similar-nav">
      <button type="button" id="pdSimPrev" aria-label="قبلی">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
      </button>
      <button type="button" id="pdSimNext" aria-label="بعدی">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      </button>
    </div>
  </div>

  <div class="pd-similar-track" id="pdSimTrack">
    @php
      $similar = [
        ['img' => 'https://picsum.photos/id/1062/480/480', 'cat' => 'پرتره',   'title' => 'پرتره مینیمال مدرن',    'desc' => 'سبک تمیز با پس‌زمینه ساده و نور نرم'],
        ['img' => 'https://picsum.photos/id/1074/480/480', 'cat' => 'حیوانات', 'title' => 'پرتره حیوانات وحشی',    'desc' => 'جزئیات بالا و فوکوس دقیق روی چهره'],
        ['img' => 'https://picsum.photos/id/1084/480/480', 'cat' => 'فانتزی',  'title' => 'دنیای فانتزی خیالی',     'desc' => 'ترکیب رنگ‌های سورئال و فضای رویایی'],
        ['img' => 'https://picsum.photos/id/1080/480/480', 'cat' => 'خوراکی',  'title' => 'عکاسی غذای حرفه‌ای',    'desc' => 'نورپردازی نرم و اشتهاآور استودیویی'],
        ['img' => 'https://picsum.photos/id/1043/480/480', 'cat' => 'معماری',  'title' => 'معماری مدرن شهری',      'desc' => 'خطوط تمیز و کنتراست بالا'],
        ['img' => 'https://picsum.photos/id/1035/480/480', 'cat' => 'طبیعت',   'title' => 'منظره کوهستانی مه‌آلود', 'desc' => 'فضای آرام با عمق میدان طبیعی'],
        ['img' => 'https://picsum.photos/id/103/480/480',  'cat' => 'پرتره',   'title' => 'پرتره سینمایی شب',      'desc' => 'نورپردازی دراماتیک و سایه‌های عمیق'],
        ['img' => 'https://picsum.photos/id/177/480/480',  'cat' => 'شهری',    'title' => 'خیابان‌های بارانی شب',   'desc' => 'انعکاس نور نئون روی آسفالت خیس'],
      ];
    @endphp

    @foreach($similar as $item)
    <div class="pd-scard">
      <img src="{{ $item['img'] }}" alt="{{ $item['title'] }}" loading="lazy">
      <div class="pd-scard-body">
        <span class="pd-scard-cat">{{ $item['cat'] }}</span>
        <h3 class="pd-scard-title">{{ $item['title'] }}</h3>
        <p class="pd-scard-desc">{{ $item['desc'] }}</p>
      </div>
    </div>
    @endforeach
  </div>

  </div>
</section>

{{-- توست --}}
<div class="pd-toast" id="pdToast">بزودی</div>
@endsection

@push('scripts')
<script>
(function () {
  function toFa(n){ return String(n).replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }

  /* ── کلیک روی عکس‌های سمت راست → نمایش بزرگ در سمت چپ ── */
  var mainImg = document.getElementById('pdMainImg');
  var counter = document.getElementById('pdCounter');
  var galleryImgs = Array.prototype.slice.call(document.querySelectorAll('.pd-gal-grid img'));

  function show(i){
    var img = galleryImgs[i];
    if (!img) return;
    mainImg.src = img.dataset.full || img.src;
    counter.textContent = toFa(i + 1) + ' / ' + toFa(galleryImgs.length);
    galleryImgs.forEach(function (g, gi) {
      g.classList.toggle('is-viewing', gi === i);
    });
  }

  galleryImgs.forEach(function (img, i) {
    img.addEventListener('click', function(){ show(i); });
  });

  show(0);

  /* ── توست «بزودی» برای بخش‌های بدون بک‌اند ── */
  var toast = document.getElementById('pdToast');
  var toastTimer = null;
  function showToast(msg){
    toast.textContent = msg || 'بزودی';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ toast.classList.remove('show'); }, 1800);
  }

  /* ذخیره (تاگل ظاهری) */
  var saveBtn = document.getElementById('pdSaveBtn');
  saveBtn.addEventListener('click', function () {
    saveBtn.classList.toggle('is-on');
    showToast(saveBtn.classList.contains('is-on') ? 'ذخیره شد' : 'از ذخیره‌ها حذف شد');
  });

  /* انتشار */
  document.getElementById('pdShareBtn').addEventListener('click', function () {
    showToast('بزودی');
  });

  /* دکمه برگشت به صفحه قبل */
  document.getElementById('pdCloseBtn').addEventListener('click', function () {
    if (window.history.length > 1) {
      window.history.back();
    } else {
      window.location.href = '/app/home';
    }
  });

  /* دکمه بساز */
  document.getElementById('pdBuildBtn').addEventListener('click', function () {
    showToast('بزودی');
  });

  /* ── اسلایدر محصولات مشابه ── */
  var track = document.getElementById('pdSimTrack');
  var step = 268; /* عرض کارت + فاصله */
  document.getElementById('pdSimNext').addEventListener('click', function () {
    track.scrollBy({ left: -step * 2, behavior: 'smooth' });
  });
  document.getElementById('pdSimPrev').addEventListener('click', function () {
    track.scrollBy({ left: step * 2, behavior: 'smooth' });
  });
}());
</script>
@endpush
