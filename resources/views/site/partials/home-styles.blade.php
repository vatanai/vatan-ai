  <style>
    /* ══════════════════════════════
       TOKENS — Dark (default)
    ══════════════════════════════ */
    :root {
      --bg:           var(--vatan-bg-page);
      --s1:           #111116;
      --s2:           #16161c;
      --b1:           #222230;
      --b2:           #2e2e3e;
      --text:         #ffffff;
      --text2:        #a8a8c0;
      --green:        #cffe00;
      --green-dim:    rgba(207,254,0,0.12);
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
      --bg:           var(--vatan-bg-page);
      --s1:           #f5f5f5;
      --s2:           #eeeeee;
      --b1:           #dddddd;
      --b2:           #cccccc;
      --text:         #000000;
      --text2:        #555566;
      --green:        #cffe00;
      --green-dim:    rgba(207,254,0,0.10);
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
      border: 1px solid rgba(207,254,0,0.25);
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
      box-shadow: 0 4px 24px rgba(207,254,0,0.3);
    }
    .btn-primary:hover { box-shadow: 0 6px 32px rgba(207,254,0,0.45); }

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
      background: var(--vatan-header-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--vatan-header-border);
      transition: background .3s ease, border-color .3s ease;
    }

    html.light #site-header {
      background: var(--vatan-header-bg);
      border-bottom-color: var(--vatan-header-border);
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
    html.light #site-header .header-nav a { color: var(--vatan-header-text-muted); }
    .header-nav a:hover {
      color: var(--text);
      background: var(--s1);
    }
    html.light #site-header .header-nav a:hover {
      color: var(--vatan-header-text);
      background: rgba(255,255,255,.08);
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
    html.light #site-header .theme-btn {
      color: var(--vatan-header-text-muted);
      background: rgba(255,255,255,.05);
      border-color: var(--vatan-header-border);
    }
    html.light #site-header .theme-btn:hover {
      color: var(--vatan-header-text);
      background: rgba(255,255,255,.1);
    }

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
    html.light #site-header .hamburger {
      color: var(--vatan-header-text);
      background: rgba(255,255,255,.05);
      border-color: var(--vatan-header-border);
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
      background: radial-gradient(circle, rgba(207,254,0,0.07) 0%, transparent 70%);
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
      border: 1px solid rgba(207,254,0,0.25);
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

    .hero-trust {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 32px;
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
      border: 1px solid rgba(207,254,0,0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--green);
      font-size: 10px;
      flex-shrink: 0;
    }

    .hero-cta-box {
      display: inline-flex;
      padding: 20px;
      background: var(--s1);
      border: 1px solid var(--b1);
      border-radius: var(--radius-lg);
      box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    }
    .hero-cta-box .btn-primary {
      color: #000000;
      width: 450px;
      max-width: 100%;
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
      border-radius: var(--radius-md);
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
      #hero {
        padding-top: calc(var(--header-h) + 20px);
        padding-bottom: 28px;
      }
      .hero-inner {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .hero-badge { margin-bottom: 12px; }
      .hero-title { font-size: clamp(22px, 6vw, 30px); margin-bottom: 10px; line-height: 1.35; }
      .hero-desc { font-size: 14px; line-height: 1.6; margin-bottom: 18px; max-width: 100%; }
      .hero-trust { gap: 8px; margin-bottom: 10px; }
      .hero-trust-item { font-size: 12px; }
      .hero-cta-box { padding: 12px; }
      .hero-cta-box .btn-primary { height: 48px; font-size: 15px; }

      /* عکس: فقط یک عکس مربعی و بزرگ، نزدیک به لبه‌های صفحه */
      .hero-visual { order: -1; }
      .hero-visual-grid { grid-template-columns: 1fr; max-width: 100%; margin: 0 auto; }
      .hero-card:nth-child(2),
      .hero-card:nth-child(3) { display: none; }
      .hero-card:first-child { aspect-ratio: 1 / 1; }
    }

    @media (max-width: 480px) {
      .hero-cta-box { width: 90%; margin: 0 auto; }
      .hero-cta-box .btn-primary { width: 100%; }
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
      padding-bottom: 16px;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      margin: 0 -24px;
      padding-left: 24px;
      padding-right: 24px;
    }
    .styles-scroll-wrap::-webkit-scrollbar { display: none; }

    .styles-track {
      display: flex;
      gap: 16px;
      width: max-content;
      padding: 4px 2px;
    }

    .style-card {
      width: 220px;
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
      font-size: 52px;
      position: relative;
      overflow: hidden;
    }

    .style-card-img img {
      width: 100%; height: 100%;
      object-fit: cover;
      position: absolute;
      inset: 0;
      transition: transform .35s ease;
    }
    .style-card:hover .style-card-img img { transform: scale(1.05); }

    .style-card-body {
      padding: 14px;
      text-align: center;
    }
    .style-card-name {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .style-card-sub {
      font-size: 12px;
      color: var(--text2);
    }

    @media (max-width: 480px) {
      .style-card { width: 180px; }
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
    .why-step:hover { border-color: rgba(207,254,0,0.3); transform: translateY(-3px); }

    .why-step-num {
      width: 56px; height: 56px;
      background: var(--green-dim);
      border: 1px solid rgba(207,254,0,0.25);
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
      border-color: rgba(207,254,0,0.3);
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
      border-color: rgba(207,254,0,0.4);
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
      border-color: rgba(207,254,0,0.35);
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
      grid-template-columns: repeat(4, minmax(0, 1fr));
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
      box-shadow: 0 0 0 1px var(--green), 0 20px 60px rgba(207,254,0,0.15);
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

    @media (max-width: 1100px) {
      .pricing-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 700px) {
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
    .faq-item.open { border-color: rgba(207,254,0,0.3); }

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
      background: radial-gradient(ellipse, rgba(207,254,0,0.08) 0%, transparent 65%);
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
      border-color: rgba(207,254,0,0.4);
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
