{{--
  صفحه نخست — وطن AI
  aivatan.com
  لندینگ پیج کامل با ۱۰ سکشن + هدر + فوتر
  ریسپانسیو — دارک/لایت مود
--}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>وطن استودیو — عکس حرفه‌ای با هوش مصنوعی</title>
  <meta name="description" content="عکس خودت را به سبک‌های حرفه‌ای تبدیل کن. وطن استودیو در کمتر از ۲ دقیقه عکس سینمایی، ترند و حرفه‌ای می‌سازد.">

  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <script>
    /* ─── تم — اول از همه لود می‌شه ─── */
    (function () {
      var saved = localStorage.getItem('vatan-theme');
      if (saved === 'light') document.documentElement.classList.add('light');
      window.vatanToggleTheme = function () {
        var isLight = document.documentElement.classList.toggle('light');
        localStorage.setItem('vatan-theme', isLight ? 'light' : 'dark');
        updateThemeIcon();
      };
    }());
  </script>

  <style>
    /* ══════════════════════════════
       TOKENS — Dark (default)
    ══════════════════════════════ */
    :root {
      --bg:           #000000;
      --s1:           #111116;
      --s2:           #16161c;
      --b1:           #222230;
      --b2:           #2e2e3e;
      --text:         #ffffff;
      --text2:        #a8a8c0;
      --green:        #0BBF53;
      --green-dim:    rgba(11,191,83,0.12);
      --accent:       #a07af5;
      --accent-dim:   rgba(160,122,245,0.12);
      --card-bg:      #111116;
      --card-border:  #222230;

      --radius-sm:    10px;
      --radius-md:    16px;
      --radius-lg:    24px;
      --radius-xl:    32px;

      --header-h:     72px;
    }

    html.light {
      --bg:           #ffffff;
      --s1:           #f5f5f5;
      --s2:           #eeeeee;
      --b1:           #dddddd;
      --b2:           #cccccc;
      --text:         #000000;
      --text2:        #555566;
      --green:        #0BBF53;
      --green-dim:    rgba(11,191,83,0.10);
      --accent:       #a07af5;
      --accent-dim:   rgba(160,122,245,0.10);
      --card-bg:      #f5f5f5;
      --card-border:  #dddddd;
    }

    /* ══════════════════════════════
       RESET & BASE
    ══════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html {
      scroll-behavior: smooth;
      scrollbar-width: thin;
      scrollbar-color: #222230 transparent;
    }
    html::-webkit-scrollbar { width: 4px; }
    html::-webkit-scrollbar-thumb { background: #222230; border-radius: 99px; }

    body {
      font-family: 'IRANSansXFaNum', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      overflow-x: hidden;
      transition: background .3s ease, color .3s ease;
      -webkit-font-smoothing: antialiased;
    }

    a { text-decoration: none; color: inherit; }
    img { display: block; max-width: 100%; }
    button { font-family: inherit; cursor: pointer; border: none; background: none; }

    /* ══════════════════════════════
       UTILITY
    ══════════════════════════════ */
    .container {
      width: 100%;
      max-width: 1180px;
      margin: 0 auto;
      padding: 0 24px;
    }

    .section {
      padding: 96px 0;
    }

    .section-label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--green);
      background: var(--green-dim);
      border: 1px solid rgba(11,191,83,0.25);
      border-radius: 99px;
      padding: 5px 14px;
      margin-bottom: 20px;
      letter-spacing: 0.3px;
    }

    .section-title {
      font-size: clamp(26px, 4vw, 42px);
      font-weight: 800;
      line-height: 1.4;
      margin-bottom: 16px;
    }

    .section-sub {
      font-size: clamp(15px, 2vw, 18px);
      color: var(--text2);
      font-weight: 400;
      line-height: 1.7;
      max-width: 560px;
    }

    .section-header { margin-bottom: 56px; }
    .section-header.center { text-align: center; }
    .section-header.center .section-sub { margin: 0 auto; }

    /* ── دکمه‌های اصلی ── */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      height: 52px;
      padding: 0 28px;
      border-radius: var(--radius-md);
      font-size: 15px;
      font-weight: 700;
      transition: opacity .2s ease, transform .15s ease, box-shadow .2s ease;
      -webkit-tap-highlight-color: transparent;
      white-space: nowrap;
    }
    .btn:active { opacity: .85; transform: scale(0.97); }

    .btn-primary {
      background: var(--green);
      color: #ffffff;
      box-shadow: 0 4px 24px rgba(11,191,83,0.3);
    }
    .btn-primary:hover { box-shadow: 0 6px 32px rgba(11,191,83,0.45); }

    .btn-ghost {
      background: transparent;
      color: var(--text);
      border: 1.5px solid var(--b1);
    }
    .btn-ghost:hover { border-color: var(--b2); }

    /* ── fade-in on scroll ── */
    .reveal {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity .6s ease, transform .6s ease;
    }
    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }
    .reveal-delay-1 { transition-delay: .1s; }
    .reveal-delay-2 { transition-delay: .2s; }
    .reveal-delay-3 { transition-delay: .3s; }
    .reveal-delay-4 { transition-delay: .4s; }
    .reveal-delay-5 { transition-delay: .5s; }

    /* ════════════════════════════════════════
       HEADER
    ════════════════════════════════════════ */
    #site-header {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      height: var(--header-h);
      background: rgba(0,0,0,0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--b1);
      transition: background .3s ease, border-color .3s ease;
    }

    html.light #site-header {
      background: rgba(255,255,255,0.92);
    }

    #site-header .container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 100%;
      gap: 24px;
    }

    /* لوگو */
    .header-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .header-logo img.logo-icon { width: 34px; height: 34px; }
    .header-logo img.logo-text { height: 22px; }

    /* منو دسکتاپ */
    .header-nav {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .header-nav a {
      font-size: 14px;
      font-weight: 500;
      color: var(--text2);
      padding: 7px 14px;
      border-radius: var(--radius-sm);
      transition: color .2s ease, background .2s ease;
    }
    .header-nav a:hover {
      color: var(--text);
      background: var(--s1);
    }

    /* سمت چپ هدر */
    .header-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }

    /* دکمه تم */
    .theme-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text2);
      font-size: 18px;
      background: var(--s1);
      border: 1px solid var(--b1);
      transition: color .2s ease, background .2s ease;
    }
    .theme-btn:hover { color: var(--text); background: var(--s2); }

    /* دکمه ورود */
    .btn-header {
      height: 40px;
      padding: 0 20px;
      font-size: 14px;
      border-radius: var(--radius-md);
    }

    /* همبرگر */
    .hamburger {
      display: none;
      width: 40px; height: 40px;
      align-items: center;
      justify-content: center;
      border-radius: var(--radius-sm);
      background: var(--s1);
      border: 1px solid var(--b1);
      color: var(--text);
      font-size: 18px;
    }

    /* منوی موبایل */
    #mobile-menu {
      display: none;
      position: fixed;
      top: var(--header-h);
      left: 0; right: 0;
      background: var(--s1);
      border-bottom: 1px solid var(--b1);
      z-index: 999;
      padding: 16px 24px 24px;
      flex-direction: column;
      gap: 4px;
    }
    #mobile-menu.open { display: flex; }

    #mobile-menu a {
      font-size: 15px;
      font-weight: 500;
      color: var(--text);
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      transition: background .2s ease;
    }
    #mobile-menu a:hover { background: var(--s2); }

    #mobile-menu .mobile-menu-footer {
      margin-top: 12px;
      padding-top: 16px;
      border-top: 1px solid var(--b1);
      display: flex;
      gap: 10px;
    }
    #mobile-menu .mobile-menu-footer .btn { flex: 1; height: 48px; }

    @media (max-width: 768px) {
      .header-nav { display: none; }
      .hamburger { display: flex; }
      .btn-header { display: none; }
    }

    /* ════════════════════════════════════════
       SECTION 1 — HERO
    ════════════════════════════════════════ */
    #hero {
      padding-top: calc(var(--header-h) + 80px);
      padding-bottom: 100px;
      position: relative;
      overflow: hidden;
    }

    /* گردیان پس‌زمینه */
    #hero::before {
      content: '';
      position: absolute;
      top: -200px; right: -200px;
      width: 700px; height: 700px;
      background: radial-gradient(circle, rgba(11,191,83,0.07) 0%, transparent 70%);
      pointer-events: none;
    }
    #hero::after {
      content: '';
      position: absolute;
      bottom: -100px; left: -200px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(160,122,245,0.07) 0%, transparent 70%);
      pointer-events: none;
    }

    .hero-inner {
      display: grid;
      grid-template-columns: 1fr 480px;
      gap: 64px;
      align-items: center;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
      color: var(--green);
      background: var(--green-dim);
      border: 1px solid rgba(11,191,83,0.25);
      border-radius: 99px;
      padding: 6px 16px;
      margin-bottom: 28px;
    }
    .hero-badge .dot {
      width: 7px; height: 7px;
      background: var(--green);
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50%       { transform: scale(1.4); opacity: .6; }
    }

    .hero-title {
      font-size: clamp(32px, 5vw, 62px);
      font-weight: 900;
      line-height: 1.3;
      margin-bottom: 20px;
      letter-spacing: -0.5px;
    }
    .hero-title .highlight {
      color: var(--green);
      position: relative;
    }

    .hero-desc {
      font-size: clamp(15px, 2vw, 18px);
      color: var(--text2);
      line-height: 1.8;
      margin-bottom: 36px;
      max-width: 500px;
    }

    .hero-ctas {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 40px;
    }

    .hero-trust {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 24px;
    }
    .hero-trust-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: var(--text2);
      font-weight: 500;
    }
    .hero-trust-item .check {
      width: 20px; height: 20px;
      background: var(--green-dim);
      border: 1px solid rgba(11,191,83,0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--green);
      font-size: 10px;
      flex-shrink: 0;
    }

    /* Hero Visual */
    .hero-visual {
      position: relative;
    }

    .hero-visual-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .hero-card {
      border-radius: var(--radius-lg);
      overflow: hidden;
      aspect-ratio: 3/4;
      background: var(--s1);
      border: 1px solid var(--b1);
      position: relative;
    }

    .hero-card:first-child {
      grid-column: 1 / -1;
      aspect-ratio: 2/1;
    }

    .hero-card img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform .4s ease;
    }
    .hero-card:hover img { transform: scale(1.04); }

    .hero-card-label {
      position: absolute;
      bottom: 12px; right: 12px;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(8px);
      color: #fff;
      font-size: 12px;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 99px;
      border: 1px solid rgba(255,255,255,0.15);
    }

    /* floating badge روی hero visual */
    .hero-float-badge {
      position: absolute;
      top: -16px; left: -16px;
      background: var(--s1);
      border: 1px solid var(--b1);
      border-radius: var(--radius-md);
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
      z-index: 2;
    }
    .hero-float-badge .icon { font-size: 24px; }
    .hero-float-badge .text { font-size: 12px; font-weight: 700; }
    .hero-float-badge .sub  { font-size: 11px; color: var(--text2); }

    @media (max-width: 960px) {
      .hero-inner {
        grid-template-columns: 1fr;
        gap: 48px;
      }
      .hero-visual { order: -1; }
      .hero-visual-grid { max-width: 520px; margin: 0 auto; }
    }

    @media (max-width: 480px) {
      #hero { padding-top: calc(var(--header-h) + 40px); padding-bottom: 60px; }
      .hero-ctas { flex-direction: column; }
      .hero-ctas .btn { width: 100%; }
    }

    /* ════════════════════════════════════════
       SECTION 2 — سبک‌ها
    ════════════════════════════════════════ */
    #styles {
      padding: 80px 0;
      background: var(--s1);
    }

    .styles-scroll-wrap {
      overflow-x: auto;
      padding-bottom: 12px;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
    }
    .styles-scroll-wrap::-webkit-scrollbar { display: none; }

    .styles-track {
      display: flex;
      gap: 14px;
      width: max-content;
      padding: 4px 2px;
    }

    .style-card {
      width: 160px;
      flex-shrink: 0;
      border-radius: var(--radius-lg);
      overflow: hidden;
      background: var(--s2);
      border: 1px solid var(--b1);
      cursor: pointer;
      transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .style-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0,0,0,0.3);
      border-color: var(--green);
    }

    .style-card-img {
      width: 100%;
      aspect-ratio: 3/4;
      background: var(--b1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      position: relative;
      overflow: hidden;
    }

    .style-card-img img {
      width: 100%; height: 100%;
      object-fit: cover;
      position: absolute;
      inset: 0;
    }

    .style-card-body {
      padding: 12px;
      text-align: center;
    }
    .style-card-name {
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .style-card-sub {
      font-size: 11px;
      color: var(--text2);
    }

    @media (max-width: 480px) {
      .style-card { width: 140px; }
    }

    /* ════════════════════════════════════════
       SECTION 3 — چرا وطن استودیو
    ════════════════════════════════════════ */
    #why {
      padding: 96px 0;
    }

    .why-steps {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }

    .why-step {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-xl);
      padding: 36px 28px;
      text-align: center;
      transition: border-color .25s ease, transform .25s ease;
      position: relative;
      overflow: hidden;
    }
    .why-step::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, var(--green-dim) 0%, transparent 60%);
      opacity: 0;
      transition: opacity .3s ease;
    }
    .why-step:hover::before { opacity: 1; }
    .why-step:hover { border-color: rgba(11,191,83,0.3); transform: translateY(-3px); }

    .why-step-num {
      width: 56px; height: 56px;
      background: var(--green-dim);
      border: 1px solid rgba(11,191,83,0.25);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 22px;
      font-weight: 900;
      color: var(--green);
      position: relative;
      z-index: 1;
    }

    .why-step-title {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 12px;
      position: relative;
      z-index: 1;
    }

    .why-step-desc {
      font-size: 14px;
      color: var(--text2);
      line-height: 1.7;
      position: relative;
      z-index: 1;
    }

    @media (max-width: 768px) {
      .why-steps { grid-template-columns: 1fr; gap: 16px; }
    }

    /* ════════════════════════════════════════
       SECTION 4 — نمونه خروجی‌ها
    ════════════════════════════════════════ */
    #samples {
      padding: 96px 0;
      background: var(--s1);
    }

    .samples-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }

    .sample-card {
      border-radius: var(--radius-lg);
      overflow: hidden;
      background: var(--s2);
      border: 1px solid var(--b1);
      position: relative;
      cursor: pointer;
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .sample-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.35); }

    .sample-card-inner {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
    }

    .sample-before, .sample-after {
      aspect-ratio: 3/4;
      position: relative;
      overflow: hidden;
    }

    .sample-before img, .sample-after img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform .35s ease;
    }
    .sample-card:hover .sample-before img,
    .sample-card:hover .sample-after img { transform: scale(1.05); }

    .sample-tag {
      position: absolute;
      bottom: 8px;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 99px;
      backdrop-filter: blur(8px);
    }
    .sample-before .sample-tag {
      right: 8px;
      background: rgba(0,0,0,0.6);
      color: #fff;
    }
    .sample-after .sample-tag {
      left: 8px;
      background: var(--green);
      color: #fff;
    }

    .sample-divider {
      position: absolute;
      top: 0; bottom: 0;
      left: 50%;
      width: 2px;
      background: var(--bg);
      z-index: 2;
    }

    .sample-card-foot {
      padding: 14px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .sample-card-foot .style-name {
      font-size: 13px;
      font-weight: 700;
    }
    .sample-card-foot .time-badge {
      font-size: 11px;
      color: var(--green);
      background: var(--green-dim);
      padding: 3px 10px;
      border-radius: 99px;
    }

    /* placeholder برای تصاویر نمونه */
    .sample-placeholder {
      width: 100%; height: 100%;
      background: linear-gradient(135deg, var(--b1) 0%, var(--b2) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
    }

    @media (max-width: 960px) {
      .samples-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 580px) {
      .samples-grid { grid-template-columns: 1fr; }
    }

    /* ════════════════════════════════════════
       SECTION 5 — تمایزات
    ════════════════════════════════════════ */
    #features {
      padding: 96px 0;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }

    .feature-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-xl);
      padding: 28px 24px;
      transition: border-color .25s ease, transform .25s ease;
    }
    .feature-card:hover {
      border-color: rgba(11,191,83,0.3);
      transform: translateY(-3px);
    }

    .feature-icon-wrap {
      width: 52px; height: 52px;
      background: var(--green-dim);
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 20px;
    }

    .feature-title {
      font-size: 17px;
      font-weight: 800;
      margin-bottom: 10px;
    }

    .feature-desc {
      font-size: 14px;
      color: var(--text2);
      line-height: 1.7;
    }

    @media (max-width: 900px) {
      .features-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 580px) {
      .features-grid { grid-template-columns: 1fr; }
    }

    /* ════════════════════════════════════════
       SECTION 6 — چطور کار می‌کند؟
    ════════════════════════════════════════ */
    #how {
      padding: 96px 0;
      background: var(--s1);
    }

    .how-steps {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 32px;
      position: relative;
    }

    /* خط بین مراحل */
    .how-steps::before {
      content: '';
      position: absolute;
      top: 42px;
      right: 16.5%;
      left: 16.5%;
      height: 1px;
      background: linear-gradient(to left, transparent, var(--b1), transparent);
    }

    .how-step {
      text-align: center;
      position: relative;
    }

    .how-step-icon {
      width: 84px; height: 84px;
      background: var(--s2);
      border: 1px solid var(--b1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin: 0 auto 24px;
      position: relative;
      z-index: 1;
      transition: background .25s ease, border-color .25s ease;
    }
    .how-step:hover .how-step-icon {
      background: var(--green-dim);
      border-color: rgba(11,191,83,0.4);
    }

    .how-step-num {
      position: absolute;
      top: -6px; left: -6px;
      width: 26px; height: 26px;
      background: var(--green);
      color: #fff;
      border-radius: 50%;
      font-size: 12px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .how-step-title {
      font-size: 17px;
      font-weight: 800;
      margin-bottom: 10px;
    }
    .how-step-desc {
      font-size: 14px;
      color: var(--text2);
      line-height: 1.7;
    }

    @media (max-width: 768px) {
      .how-steps::before { display: none; }
      .how-steps { grid-template-columns: 1fr; gap: 24px; }
      .how-step { display: flex; align-items: flex-start; gap: 20px; text-align: right; }
      .how-step-icon { margin: 0; flex-shrink: 0; width: 64px; height: 64px; font-size: 28px; }
    }

    /* ════════════════════════════════════════
       SECTION 7 — برای چه کسانی؟
    ════════════════════════════════════════ */
    #audience {
      padding: 96px 0;
    }

    .audience-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      justify-content: center;
    }

    .audience-tag {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-xl);
      padding: 14px 22px;
      font-size: 15px;
      font-weight: 700;
      transition: background .2s ease, border-color .2s ease, transform .2s ease;
      cursor: default;
    }
    .audience-tag:hover {
      background: var(--green-dim);
      border-color: rgba(11,191,83,0.35);
      transform: translateY(-2px);
    }
    .audience-tag .tag-icon { font-size: 22px; }

    /* ════════════════════════════════════════
       SECTION 8 — تعرفه‌ها
    ════════════════════════════════════════ */
    #pricing {
      padding: 96px 0;
      background: var(--s1);
    }

    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      align-items: stretch;
    }

    .pricing-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-xl);
      padding: 36px 28px;
      display: flex;
      flex-direction: column;
      position: relative;
      transition: border-color .25s ease, transform .25s ease;
    }
    .pricing-card:hover { transform: translateY(-4px); }

    .pricing-card.featured {
      border-color: var(--green);
      background: linear-gradient(145deg, var(--s2) 0%, var(--s1) 100%);
      box-shadow: 0 0 0 1px var(--green), 0 20px 60px rgba(11,191,83,0.15);
    }

    .pricing-badge {
      position: absolute;
      top: -14px; left: 50%; transform: translateX(-50%);
      background: var(--green);
      color: #fff;
      font-size: 12px;
      font-weight: 800;
      padding: 5px 16px;
      border-radius: 99px;
      white-space: nowrap;
    }

    .pricing-plan-name {
      font-size: 15px;
      font-weight: 700;
      color: var(--text2);
      margin-bottom: 8px;
    }

    .pricing-price {
      margin-bottom: 6px;
    }
    .pricing-price .amount {
      font-size: 42px;
      font-weight: 900;
      color: var(--text);
      line-height: 1;
    }
    .pricing-price .unit {
      font-size: 14px;
      color: var(--text2);
      margin-right: 6px;
    }
    .pricing-price.free .amount { color: var(--green); }

    .pricing-outputs {
      font-size: 14px;
      color: var(--text2);
      margin-bottom: 28px;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--b1);
    }
    .pricing-outputs strong { color: var(--text); }

    .pricing-features {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 28px;
    }

    .pricing-feature {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 14px;
      color: var(--text2);
      line-height: 1.5;
    }
    .pricing-feature .check {
      width: 18px; height: 18px;
      background: var(--green-dim);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--green);
      font-size: 9px;
      flex-shrink: 0;
      margin-top: 2px;
    }

    @media (max-width: 900px) {
      .pricing-grid { grid-template-columns: 1fr; max-width: 440px; margin: 0 auto; }
      .pricing-card.featured { order: -1; }
    }

    /* ════════════════════════════════════════
       SECTION 9 — سوالات متداول
    ════════════════════════════════════════ */
    #faq {
      padding: 96px 0;
    }

    .faq-list {
      max-width: 720px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .faq-item {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: border-color .2s ease;
    }
    .faq-item.open { border-color: rgba(11,191,83,0.3); }

    .faq-q {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      cursor: pointer;
      gap: 16px;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }

    .faq-q-text {
      font-size: 16px;
      font-weight: 700;
    }

    .faq-arrow {
      width: 28px; height: 28px;
      background: var(--s1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text2);
      font-size: 12px;
      flex-shrink: 0;
      transition: transform .3s ease, background .2s ease, color .2s ease;
    }
    .faq-item.open .faq-arrow {
      transform: rotate(180deg);
      background: var(--green-dim);
      color: var(--green);
    }

    .faq-a {
      padding: 0 24px;
      max-height: 0;
      overflow: hidden;
      transition: max-height .35s ease, padding .35s ease;
    }
    .faq-item.open .faq-a {
      max-height: 300px;
      padding-bottom: 20px;
    }

    .faq-a p {
      font-size: 14px;
      color: var(--text2);
      line-height: 1.8;
    }

    /* ════════════════════════════════════════
       SECTION 10 — CTA نهایی
    ════════════════════════════════════════ */
    #cta-final {
      padding: 120px 0;
      background: var(--s1);
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    #cta-final::before {
      content: '';
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%,-50%);
      width: 800px; height: 500px;
      background: radial-gradient(ellipse, rgba(11,191,83,0.08) 0%, transparent 65%);
      pointer-events: none;
    }

    .cta-final-icon {
      font-size: 56px;
      margin-bottom: 24px;
      display: block;
    }

    .cta-final-title {
      font-size: clamp(28px, 5vw, 52px);
      font-weight: 900;
      margin-bottom: 16px;
      line-height: 1.3;
    }

    .cta-final-sub {
      font-size: 17px;
      color: var(--text2);
      margin-bottom: 40px;
    }

    .cta-final-btn {
      height: 60px;
      padding: 0 44px;
      font-size: 17px;
      border-radius: var(--radius-lg);
    }

    /* ════════════════════════════════════════
       FOOTER
    ════════════════════════════════════════ */
    #site-footer {
      background: var(--s2);
      border-top: 1px solid var(--b1);
      padding: 40px 0;
    }

    .footer-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }

    .footer-logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .footer-logo img.logo-icon { width: 28px; height: 28px; }
    .footer-logo img.logo-text { height: 18px; }

    .footer-links {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .footer-links a {
      font-size: 13px;
      color: var(--text2);
      transition: color .2s ease;
    }
    .footer-links a:hover { color: var(--text); }

    .footer-copy {
      font-size: 12px;
      color: var(--text2);
    }

    .footer-admin-box {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: var(--s1);
      border: 1px solid var(--b1);
      border-radius: var(--radius-md);
      font-size: 12px;
      color: var(--text2);
      transition: border-color .2s ease, color .2s ease;
      text-decoration: none;
    }
    .footer-admin-box:hover {
      border-color: rgba(11,191,83,0.4);
      color: var(--green);
    }
    .footer-admin-box i { font-size: 12px; }

    @media (max-width: 600px) {
      .footer-inner { flex-direction: column; text-align: center; }
      .footer-links { flex-wrap: wrap; justify-content: center; }
    }

    /* ════════════════════════════════════════
       TELEGRAM FLOATING BUTTON
    ════════════════════════════════════════ */
    .telegram-fab {
      position: fixed;
      bottom: 28px;
      left: 24px;
      width: 52px; height: 52px;
      background: #2AABEE;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 20px rgba(42,171,238,0.45);
      z-index: 500;
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .telegram-fab:hover { transform: scale(1.08); box-shadow: 0 6px 28px rgba(42,171,238,0.55); }
    .telegram-fab svg { width: 26px; height: 26px; color: #fff; }

  </style>
</head>
<body>

<!-- ══════════════ HEADER ══════════════ -->
<header id="site-header">
  <div class="container">
    <!-- لوگو -->
    <a href="/" class="header-logo">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" class="logo-icon">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" class="logo-text">
    </a>

    <!-- منو دسکتاپ -->
    <nav class="header-nav">
      <a href="#styles">سبک‌ها</a>
      <a href="#samples">نمونه‌ها</a>
      <a href="#features">ویژگی‌ها</a>
      <a href="#pricing">تعرفه‌ها</a>
      <a href="#faq">سوالات</a>
    </nav>

    <!-- اکشن‌ها -->
    <div class="header-actions">
      <!-- دکمه روز/شب -->
      <button class="theme-btn" onclick="vatanToggleTheme()" id="theme-btn" aria-label="تغییر تم">
        <i class="fa-solid fa-moon" id="theme-icon"></i>
      </button>

      <!-- CTA -->
      <a href="{{ route('app.home') }}" class="btn btn-primary btn-header">
        شروع رایگان
      </a>

      <!-- همبرگر موبایل -->
      <button class="hamburger" id="hamburger-btn" onclick="toggleMenu()" aria-label="منو">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>

<!-- منوی موبایل -->
<div id="mobile-menu">
  <a href="#styles" onclick="closeMenu()">سبک‌ها</a>
  <a href="#samples" onclick="closeMenu()">نمونه خروجی‌ها</a>
  <a href="#features" onclick="closeMenu()">ویژگی‌ها</a>
  <a href="#pricing" onclick="closeMenu()">تعرفه‌ها</a>
  <a href="#faq" onclick="closeMenu()">سوالات متداول</a>
  <div class="mobile-menu-footer">
    <a href="{{ route('app.home') }}" class="btn btn-primary" onclick="closeMenu()">شروع رایگان</a>
    <a href="{{ route('login') }}" class="btn btn-ghost" onclick="closeMenu()">ورود</a>
  </div>
</div>


<!-- ══════════════ SECTION 1: HERO ══════════════ -->
<section id="hero">
  <div class="container">
    <div class="hero-inner">

      <!-- متن -->
      <div>
        <div class="hero-badge reveal">
          <span class="dot"></span>
          وطن استودیو — هوش مصنوعی
        </div>

        <h1 class="hero-title reveal reveal-delay-1">
          عکس خودت را به<br>
          <span class="highlight">هر سبکی</span> تبدیل کن
        </h1>

        <p class="hero-desc reveal reveal-delay-2">
          فقط عکس بفرست. وطن استودیو در کمتر از ۲ دقیقه عکس حرفه‌ای، سینمایی و ترند برایت می‌سازد.
        </p>

        <div class="hero-ctas reveal reveal-delay-3">
          <a href="{{ route('app.home') }}" class="btn btn-primary" style="height:56px;padding:0 32px;font-size:16px;">
            <i class="fa-solid fa-bolt"></i>
            شروع رایگان
          </a>
          <a href="#samples" class="btn btn-ghost" style="height:56px;padding:0 32px;font-size:16px;">
            <i class="fa-solid fa-images"></i>
            نمونه خروجی‌ها
          </a>
        </div>

        <div class="hero-trust reveal reveal-delay-4">
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            تحویل زیر ۲ دقیقه
          </div>
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            بدون پرامپت‌نویسی
          </div>
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            مستقیم در تلگرام
          </div>
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            حفظ شباهت چهره
          </div>
        </div>
      </div>

      <!-- ویژوال -->
      <div class="hero-visual reveal reveal-delay-2">
        <div class="hero-float-badge">
          <span class="icon">⚡️</span>
          <div>
            <div class="text">زیر ۲ دقیقه</div>
            <div class="sub">تحویل سریع</div>
          </div>
        </div>

        <div class="hero-visual-grid">
          <div class="hero-card">
            <img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="نمونه سینمایی" loading="lazy">
            <span class="hero-card-label">سبک سینمایی</span>
          </div>
          <div class="hero-card">
            <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="پرتره" loading="lazy">
            <span class="hero-card-label">پرتره حرفه‌ای</span>
          </div>
          <div class="hero-card">
            <img src="{{ asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg') }}" alt="فشن" loading="lazy">
            <span class="hero-card-label">فشن و مدلینگ</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 2: سبک‌ها ══════════════ -->
<section id="styles">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-label">
        <i class="fa-solid fa-palette"></i>
        سبک‌های آماده
      </div>
      <h2 class="section-title">محبوب‌ترین سبک‌ها</h2>
      <p class="section-sub">از بین ده‌ها سبک آماده انتخاب کن — بدون نیاز به هیچ دانش فنی</p>
    </div>

    <div class="styles-scroll-wrap reveal reveal-delay-1">
      <div class="styles-track">

        <div class="style-card">
          <div class="style-card-img">📸
            <img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="پرتره" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">پرتره حرفه‌ای</div>
            <div class="style-card-sub">کلاسیک و شیک</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">👗
            <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="فشن" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">فشن و مدلینگ</div>
            <div class="style-card-sub">ترند روز</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🎬
            <img src="{{ asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg') }}" alt="سینمایی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">سینمایی</div>
            <div class="style-card-sub">نور دراماتیک</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">💼
            <img src="{{ asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp') }}" alt="بیزینسی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">بیزینسی</div>
            <div class="style-card-sub">حرفه‌ای رسمی</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🔗
            <img src="{{ asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp') }}" alt="لینکدین" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">پروفایل لینکدین</div>
            <div class="style-card-sub">اعتمادساز</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">📱
            <img src="{{ asset('assets/img/best-friends-ai-prompt-2.webp') }}" alt="اینستاگرام" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">ترند اینستاگرام</div>
            <div class="style-card-sub">وایرال استایل</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🎨</div>
          <div class="style-card-body">
            <div class="style-card-name">کارتونی</div>
            <div class="style-card-sub">انیمه و تون</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">✨
            <img src="{{ asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp') }}" alt="رویایی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">رویایی</div>
            <div class="style-card-sub">فانتزی و هنری</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">💍
            <img src="{{ asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg') }}" alt="عروسی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">عروسی</div>
            <div class="style-card-sub">رمانتیک و خاص</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🏋️
            <img src="{{ asset('assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg') }}" alt="ورزشی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">ورزشی</div>
            <div class="style-card-sub">پرانرژی</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>


<!-- ══════════════ SECTION 3: چرا وطن استودیو؟ ══════════════ -->
<section id="why" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-star"></i>
        چرا وطن؟
      </div>
      <h2 class="section-title">ساخت عکس حرفه‌ای هیچ‌وقت اینقدر ساده نبوده</h2>
    </div>

    <div class="why-steps">
      <div class="why-step reveal reveal-delay-1">
        <div class="why-step-num">۱</div>
        <h3 class="why-step-title">فقط عکس بفرست</h3>
        <p class="why-step-desc">نیازی به نوشتن پرامپت نیست. یک سلفی ساده کافی است.</p>
      </div>

      <div class="why-step reveal reveal-delay-2">
        <div class="why-step-num">۲</div>
        <h3 class="why-step-title">سبک را انتخاب کن</h3>
        <p class="why-step-desc">از بین ده‌ها سبک آماده، هر کدام که دوست داری انتخاب کن.</p>
      </div>

      <div class="why-step reveal reveal-delay-3">
        <div class="why-step-num">۳</div>
        <h3 class="why-step-title">نتیجه را تحویل بگیر</h3>
        <p class="why-step-desc">کمتر از ۲ دقیقه بعد عکس حرفه‌ای آماده است.</p>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════ SECTION 4: نمونه خروجی‌ها ══════════════ -->
<section id="samples">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-images"></i>
        نمونه کارها
      </div>
      <h2 class="section-title">ببین کاربران چه ساخته‌اند</h2>
      <p class="section-sub">تبدیل واقعی از عکس معمولی به تصویر حرفه‌ای — همه با یک کلیک</p>
    </div>

    <div class="samples-grid">

      <div class="sample-card reveal reveal-delay-1">
        <div class="sample-card-inner">
          <div class="sample-before">
            <img src="{{ asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp') }}" alt="قبل" loading="lazy">
            <span class="sample-tag">قبل</span>
          </div>
          <div class="sample-after">
            <img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="بعد" loading="lazy">
            <span class="sample-tag">بعد</span>
          </div>
          <div class="sample-divider"></div>
        </div>
        <div class="sample-card-foot">
          <span class="style-name">سبک سینمایی</span>
          <span class="time-badge">۴۵ ثانیه</span>
        </div>
      </div>

      <div class="sample-card reveal reveal-delay-2">
        <div class="sample-card-inner">
          <div class="sample-before">
            <img src="{{ asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp') }}" alt="قبل" loading="lazy">
            <span class="sample-tag">قبل</span>
          </div>
          <div class="sample-after">
            <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="بعد" loading="lazy">
            <span class="sample-tag">بعد</span>
          </div>
          <div class="sample-divider"></div>
        </div>
        <div class="sample-card-foot">
          <span class="style-name">پرتره حرفه‌ای</span>
          <span class="time-badge">۶۰ ثانیه</span>
        </div>
      </div>

      <div class="sample-card reveal reveal-delay-3">
        <div class="sample-card-inner">
          <div class="sample-before">
            <img src="{{ asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp') }}" alt="قبل" loading="lazy">
            <span class="sample-tag">قبل</span>
          </div>
          <div class="sample-after">
            <img src="{{ asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg') }}" alt="بعد" loading="lazy">
            <span class="sample-tag">بعد</span>
          </div>
          <div class="sample-divider"></div>
        </div>
        <div class="sample-card-foot">
          <span class="style-name">فشن و مدلینگ</span>
          <span class="time-badge">۹۰ ثانیه</span>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 5: تمایزات ══════════════ -->
<section id="features" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-gem"></i>
        چرا ما؟
      </div>
      <h2 class="section-title">چرا کاربران وطن استودیو را انتخاب می‌کنند؟</h2>
    </div>

    <div class="features-grid">

      <div class="feature-card reveal reveal-delay-1">
        <div class="feature-icon-wrap">🪞</div>
        <h3 class="feature-title">حفظ چهره واقعی</h3>
        <p class="feature-desc">نتیجه شبیه خودت می‌ماند. هویت چهره‌ات در تمام تبدیل‌ها حفظ می‌شود.</p>
      </div>

      <div class="feature-card reveal reveal-delay-2">
        <div class="feature-icon-wrap">⚡️</div>
        <h3 class="feature-title">سرعت بالا</h3>
        <p class="feature-desc">اکثر سفارش‌ها زیر ۲ دقیقه آماده می‌شوند. بدون انتظار طولانی.</p>
      </div>

      <div class="feature-card reveal reveal-delay-3">
        <div class="feature-icon-wrap">🧠</div>
        <h3 class="feature-title">بدون دانش فنی</h3>
        <p class="feature-desc">فقط عکس ارسال می‌کنی. هیچ پرامپت یا تنظیماتی نیاز نیست.</p>
      </div>

      <div class="feature-card reveal reveal-delay-1">
        <div class="feature-icon-wrap">🔄</div>
        <h3 class="feature-title">همیشه سبک‌های جدید</h3>
        <p class="feature-desc">سبک‌های ترند به‌صورت مداوم اضافه می‌شوند. همیشه به‌روز هستی.</p>
      </div>

      <div class="feature-card reveal reveal-delay-2">
        <div class="feature-icon-wrap">🔒</div>
        <h3 class="feature-title">حریم خصوصی</h3>
        <p class="feature-desc">عکس خام پس از پردازش حذف می‌شود. اطلاعاتت محفوظ است.</p>
      </div>

      <div class="feature-card reveal reveal-delay-3">
        <div class="feature-icon-wrap">📱</div>
        <h3 class="feature-title">استفاده آسان</h3>
        <p class="feature-desc">تلگرام و وب، هر دو در دسترس. از هر دستگاهی استفاده کن.</p>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 6: چطور کار می‌کند؟ ══════════════ -->
<section id="how">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-list-ol"></i>
        روند کار
      </div>
      <h2 class="section-title">فقط ۳ مرحله</h2>
      <p class="section-sub">از ارسال عکس تا دریافت نتیجه حرفه‌ای — بدون پیچیدگی</p>
    </div>

    <div class="how-steps">

      <div class="how-step reveal reveal-delay-1">
        <div class="how-step-icon">
          <span class="how-step-num">۱</span>
          📷
        </div>
        <div>
          <h3 class="how-step-title">عکس خودت را ارسال کن</h3>
          <p class="how-step-desc">یک سلفی یا عکس معمولی کافی است. کیفیت متوسط هم مشکلی ندارد.</p>
        </div>
      </div>

      <div class="how-step reveal reveal-delay-2">
        <div class="how-step-icon">
          <span class="how-step-num">۲</span>
          🎨
        </div>
        <div>
          <h3 class="how-step-title">سبک مورد علاقه‌ات را انتخاب کن</h3>
          <p class="how-step-desc">از لیست سبک‌های آماده یکی را انتخاب کن. همین و بس.</p>
        </div>
      </div>

      <div class="how-step reveal reveal-delay-3">
        <div class="how-step-icon">
          <span class="how-step-num">۳</span>
          ⚡️
        </div>
        <div>
          <h3 class="how-step-title">نتیجه را دریافت کن</h3>
          <p class="how-step-desc">کمتر از ۲ دقیقه بعد عکس حرفه‌ای تحویلت داده می‌شود.</p>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 7: برای چه کسانی؟ ══════════════ -->
<section id="audience" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-users"></i>
        مخاطبان
      </div>
      <h2 class="section-title">مناسب برای</h2>
      <p class="section-sub">هر کسی که می‌خواهد عکس بهتری داشته باشد</p>
    </div>

    <div class="audience-tags">
      <div class="audience-tag reveal reveal-delay-1">
        <span class="tag-icon">✍️</span>
        بلاگرها
      </div>
      <div class="audience-tag reveal reveal-delay-1">
        <span class="tag-icon">🎬</span>
        تولیدکنندگان محتوا
      </div>
      <div class="audience-tag reveal reveal-delay-2">
        <span class="tag-icon">🛍️</span>
        فروشگاه‌های آنلاین
      </div>
      <div class="audience-tag reveal reveal-delay-2">
        <span class="tag-icon">💼</span>
        صاحبان کسب‌وکار
      </div>
      <div class="audience-tag reveal reveal-delay-3">
        <span class="tag-icon">🎓</span>
        دانشجویان
      </div>
      <div class="audience-tag reveal reveal-delay-3">
        <span class="tag-icon">👤</span>
        کاربران عادی
      </div>
    </div>
  </div>
</section>


<!-- ══════════════ SECTION 8: تعرفه‌ها ══════════════ -->
<section id="pricing">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-tag"></i>
        قیمت‌گذاری
      </div>
      <h2 class="section-title">تعرفه‌ها</h2>
      <p class="section-sub">پرداخت بر اساس تعداد خروجی — ساده و شفاف</p>
    </div>

    <div class="pricing-grid">

      <!-- پلن رایگان -->
      <div class="pricing-card reveal reveal-delay-1">
        <div class="pricing-plan-name">شروع</div>
        <div class="pricing-price free">
          <span class="amount">رایگان</span>
        </div>
        <div class="pricing-outputs">
          <strong>۳ خروجی</strong> هدیه برای شروع
        </div>
        <div class="pricing-features">
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            ۳ تصویر حرفه‌ای رایگان
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            دسترسی به همه سبک‌ها
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            تحویل زیر ۲ دقیقه
          </div>
        </div>
        <a href="{{ route('app.home') }}" class="btn btn-ghost" style="width:100%;justify-content:center;">
          شروع رایگان
        </a>
      </div>

      <!-- پلن استاندارد -->
      <div class="pricing-card featured reveal reveal-delay-2">
        <div class="pricing-badge">محبوب‌ترین</div>
        <div class="pricing-plan-name">استاندارد</div>
        <div class="pricing-price">
          <span class="amount">۳۰</span>
          <span class="unit">خروجی</span>
        </div>
        <div class="pricing-outputs">
          مناسب برای <strong>استفاده منظم</strong> و محتوای روزانه
        </div>
        <div class="pricing-features">
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            ۳۰ تصویر حرفه‌ای
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            همه سبک‌های آماده
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            اولویت پردازش
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            کیفیت فول‌رزولوشن
          </div>
        </div>
        <a href="{{ route('app.home') }}" class="btn btn-primary" style="width:100%;justify-content:center;">
          خرید استاندارد
        </a>
      </div>

      <!-- پلن حرفه‌ای -->
      <div class="pricing-card reveal reveal-delay-3">
        <div class="pricing-plan-name">حرفه‌ای</div>
        <div class="pricing-price">
          <span class="amount">۱۰۰</span>
          <span class="unit">خروجی</span>
        </div>
        <div class="pricing-outputs">
          مناسب برای <strong>حرفه‌ای‌ها</strong> و کسب‌وکارها
        </div>
        <div class="pricing-features">
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            ۱۰۰ تصویر حرفه‌ای
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            همه سبک‌های آماده
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            اولویت ویژه پردازش
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            کیفیت فول‌رزولوشن
          </div>
          <div class="pricing-feature">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            پشتیبانی اختصاصی
          </div>
        </div>
        <a href="{{ route('app.home') }}" class="btn btn-ghost" style="width:100%;justify-content:center;">
          خرید حرفه‌ای
        </a>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 9: سوالات متداول ══════════════ -->
<section id="faq" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-circle-question"></i>
        سوالات متداول
      </div>
      <h2 class="section-title">جواب سوالاتت را اینجا پیدا کن</h2>
    </div>

    <div class="faq-list reveal reveal-delay-1">

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا نیاز به دانش هوش مصنوعی یا پرامپت‌نویسی دارم؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>خیر. کاملاً ساده است. فقط عکس خودت را ارسال کن، سبک را انتخاب کن و منتظر نتیجه باش. نیازی به هیچ دانش فنی ندارید.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">چقدر طول می‌کشد تا نتیجه آماده شود؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>معمولاً بین ۳۰ تا ۱۲۰ ثانیه. در اوقات شلوغی ممکن است کمی بیشتر طول بکشد اما هرگز بیش از ۵ دقیقه نخواهد بود.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا عکس من ذخیره یا به اشتراک گذاشته می‌شود؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>عکس خام شما بلافاصله پس از پردازش حذف می‌شود. ما به حریم خصوصی کاربران اهمیت می‌دهیم و تصاویر هرگز با شخص ثالثی به اشتراک گذاشته نمی‌شود.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا با موبایل هم کار می‌کند؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>بله. هم از طریق وب‌سایت و هم از طریق ربات تلگرام می‌توانید به راحتی از موبایل استفاده کنید.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا می‌توانم سبک‌های مختلف را با یک خروجی امتحان کنم؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>هر سفارش یک سبک مشخص دارد. اگر می‌خواهید یک عکس را با سبک‌های مختلف تبدیل کنید، هر بار یک خروجی از اعتبار شما کم می‌شود.</p>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 10: CTA نهایی ══════════════ -->
<section id="cta-final">
  <div class="container">
    <span class="cta-final-icon reveal">✨</span>
    <h2 class="cta-final-title reveal reveal-delay-1">
      اولین تصویرت را<br>همین حالا بساز
    </h2>
    <p class="cta-final-sub reveal reveal-delay-2">فقط یک عکس کافی است.</p>
    <a href="{{ route('app.home') }}" class="btn btn-primary cta-final-btn reveal reveal-delay-3">
      <i class="fa-solid fa-bolt"></i>
      شروع رایگان — همین حالا
    </a>
  </div>
</section>


<!-- ══════════════ FOOTER ══════════════ -->
<footer id="site-footer">
  <div class="container">
    <div class="footer-inner">
      <a href="/" class="footer-logo">
        <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" class="logo-icon">
        <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" class="logo-text">
      </a>

      <div class="footer-links">
        <a href="#styles">سبک‌ها</a>
        <a href="#pricing">تعرفه‌ها</a>
        <a href="#faq">سوالات</a>
        <a href="{{ route('app.home') }}">ورود به اپ</a>
      </div>

      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <p class="footer-copy">© ۱۴۰۴ وطن استودیو — تمام حقوق محفوظ است</p>
        <a href="{{ route('admin.dashboard') }}" class="footer-admin-box">
          <i class="fa-solid fa-gauge-high"></i>
          ورود به داشبورد
        </a>
      </div>
    </div>
  </div>
</footer>


<!-- ══════════════ دکمه شناور تلگرام ══════════════ -->
<a href="https://t.me/vatanstudio_bot" class="telegram-fab" target="_blank" aria-label="تلگرام">
  <svg viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.94z"/>
  </svg>
</a>


<!-- ══════════════ SCRIPTS ══════════════ -->
<script>
  /* ── تم آیکون ── */
  function updateThemeIcon() {
    var isLight = document.documentElement.classList.contains('light');
    var icon = document.getElementById('theme-icon');
    if (!icon) return;
    icon.className = isLight ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  }
  updateThemeIcon();
  document.addEventListener('DOMContentLoaded', updateThemeIcon);

  /* ── منو موبایل ── */
  function toggleMenu() {
    document.getElementById('mobile-menu').classList.toggle('open');
  }
  function closeMenu() {
    document.getElementById('mobile-menu').classList.remove('open');
  }

  /* ── FAQ accordion ── */
  function toggleFaq(el) {
    var isOpen = el.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(function(i) {
      i.classList.remove('open');
    });
    if (!isOpen) el.classList.add('open');
  }

  /* ── Intersection Observer — reveal on scroll ── */
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal').forEach(function(el) {
    observer.observe(el);
  });

  /* ── هدر shadow on scroll ── */
  window.addEventListener('scroll', function() {
    var header = document.getElementById('site-header');
    if (window.scrollY > 20) {
      header.style.boxShadow = '0 2px 24px rgba(0,0,0,0.3)';
    } else {
      header.style.boxShadow = 'none';
    }
  }, { passive: true });

  /* ── Smooth scroll برای لینک‌های anchor ── */
  document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      var id = this.getAttribute('href').slice(1);
      var target = document.getElementById(id);
      if (target) {
        e.preventDefault();
        var headerH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 72;
        var top = target.getBoundingClientRect().top + window.pageYOffset - headerH - 20;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });
</script>

</body>
</html>
