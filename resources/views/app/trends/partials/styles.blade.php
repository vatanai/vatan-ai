<style>
  html, body { background: var(--vatan-bg-page); overflow-x: hidden; }
  html.light, html.light body { background: var(--vatan-bg-page); }

  .trends-page {
    width: 100%; max-width: 480px; margin: 0 auto;
    min-height: 100vh; padding-bottom: 120px;
    background: var(--vatan-bg-page); color: var(--vatan-text-page);
  }

  .trends-header { padding: calc(env(safe-area-inset-top) + 18px) 16px 24px; }
  .trends-heading-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
  .trends-eyebrow { display: inline-flex; align-items: center; gap: 6px; color: #cffe00; font-size: 11px; font-weight: 800; }
  .trends-title { margin: 6px 0 0; color: var(--vatan-text-page); font-size: 24px; font-weight: 900; line-height: 1.3; }
  .trends-subtitle { max-width: 620px; margin: 7px 0 0; color: rgba(255,255,255,.58); font-size: 12px; line-height: 1.8; }
  html.light .trends-subtitle { color: rgba(12,12,16,.58); }

  .trends-search { position: relative; max-width: 680px; margin-top: 22px; }
  .trends-search-input-wrap { position: relative; display: flex; align-items: center; min-height: 48px; border: 1px solid rgba(255,255,255,.14); border-radius: 15px; background: rgba(255,255,255,.07); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
  .trends-search-input-wrap:focus-within { border-color: #cffe00; box-shadow: 0 0 0 3px rgba(207,254,0,.1), 0 12px 32px rgba(0,0,0,.16); }
  .trends-search-icon { flex: 0 0 auto; margin-right: 15px; color: rgba(255,255,255,.5); font-size: 14px; }
  .trends-search-input { min-width: 0; flex: 1; height: 46px; padding: 0 11px; border: 0; outline: 0; background: transparent; color: var(--vatan-text-page); font: inherit; font-size: 13px; }
  .trends-search-input::placeholder { color: rgba(255,255,255,.45); }
  .trends-search-submit { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; width: 36px; height: 36px; margin-left: 7px; margin-right: 7px; border: 0; border-radius: 10px; background: #cffe00; color: #12160a; cursor: pointer; }
  .trends-search-submit:hover { filter: brightness(.95); }
  html.light .trends-search-input-wrap { border-color: rgba(0,0,0,.12); background: rgba(255,255,255,.8); }
  html.light .trends-search-icon { color: rgba(12,12,16,.48); }
  html.light .trends-search-input::placeholder { color: rgba(12,12,16,.45); }
  .trends-search-results { position: absolute; z-index: 20; top: calc(100% + 8px); right: 0; left: 0; overflow: hidden; border: 1px solid rgba(255,255,255,.12); border-radius: 14px; background: rgba(20,24,22,.98); box-shadow: 0 18px 42px rgba(0,0,0,.3); }
  .trends-search-result { display: flex; align-items: center; gap: 10px; min-height: 61px; padding: 8px 12px; color: inherit; text-decoration: none; }
  .trends-search-result + .trends-search-result { border-top: 1px solid rgba(255,255,255,.08); }
  .trends-search-result:hover { background: rgba(255,255,255,.07); }
  .trends-search-result img { width: 42px; height: 47px; flex: 0 0 auto; border-radius: 8px; background: rgba(255,255,255,.08); object-fit: cover; }
  .trends-search-result span { display: flex; min-width: 0; flex-direction: column; gap: 3px; }
  .trends-search-result strong { overflow: hidden; color: var(--vatan-text-page); font-size: 12px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
  .trends-search-result small { overflow: hidden; color: rgba(255,255,255,.5); font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
  .trends-search-all { display: block; padding: 12px; border-top: 1px solid rgba(255,255,255,.08); color: #cffe00; font-size: 11px; font-weight: 800; text-align: center; text-decoration: none; }
  .trends-search-empty { padding: 18px 12px; color: rgba(255,255,255,.58); font-size: 11px; text-align: center; }
  html.light .trends-search-results { border-color: rgba(0,0,0,.1); background: rgba(255,255,255,.98); box-shadow: 0 18px 42px rgba(0,0,0,.15); }
  html.light .trends-search-result + .trends-search-result,
  html.light .trends-search-all { border-color: rgba(0,0,0,.08); }
  html.light .trends-search-result:hover { background: rgba(0,0,0,.04); }
  html.light .trends-search-result small,
  html.light .trends-search-empty { color: rgba(12,12,16,.52); }

  .trends-section { margin-top: 28px; padding: 0 16px; }
  .trends-section-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
  .trends-section-heading h2 { margin: 0; color: var(--vatan-text-page); font-size: 17px; font-weight: 900; }
  .trends-section-heading p { margin: 4px 0 0; color: rgba(255,255,255,.5); font-size: 11px; line-height: 1.7; }
  html.light .trends-section-heading p { color: rgba(12,12,16,.52); }
  .trends-section-action { flex-shrink: 0; color: #cffe00; font-size: 11px; font-weight: 800; text-decoration: none; }

  .trends-time-grid, .trends-three-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
  .trends-time-column h3 { margin: 0 0 8px; color: var(--vatan-text-page); font-size: 12px; font-weight: 800; }

  .trends-card { position: relative; display: block; overflow: hidden; aspect-ratio: 3 / 4; border-radius: 13px; background: rgba(255,255,255,.06); color: inherit; text-decoration: none; isolation: isolate; }
  .trends-card-media { position: absolute; inset: 0; width: 100%; height: 100%; display: block; object-fit: cover; z-index: 0; }
  .trends-card-overlay { position: absolute; inset: 0; z-index: 1; background: linear-gradient(to bottom, rgba(0,0,0,.2), transparent 38%, rgba(0,0,0,.86)); }
  .trends-download-badge { position: absolute; top: 8px; left: 8px; z-index: 2; display: inline-flex; align-items: center; gap: 4px; padding: 4px 7px; border: 1px solid rgba(255,255,255,.18); border-radius: 7px; background: rgba(0,0,0,.55); color: #fff; font-size: 9px; font-weight: 800; backdrop-filter: blur(8px); }
  .trends-card-type { position: absolute; top: 9px; right: 9px; z-index: 2; color: rgba(255,255,255,.85); font-size: 11px; text-shadow: 0 1px 4px rgba(0,0,0,.6); }
  .trends-card-info { position: absolute; right: 10px; bottom: 10px; left: 10px; z-index: 2; display: flex; flex-direction: column; gap: 2px; text-align: right; }
  .trends-card-info strong { overflow: hidden; color: #fff; font-size: 12px; font-weight: 900; line-height: 1.45; text-overflow: ellipsis; white-space: nowrap; }
  .trends-card-info small { overflow: hidden; color: rgba(255,255,255,.68); font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
  .trends-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,0,0,.26); }
  .trends-card, .trends-card:hover { transition: transform .2s ease, box-shadow .2s ease; }

  .trends-tab-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
  .trends-tabs { display: flex; gap: 7px; min-width: 0; overflow-x: auto; scrollbar-width: none; }
  .trends-tabs::-webkit-scrollbar { display: none; }
  .trends-tab { flex: 0 0 auto; padding: 7px 13px; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; background: rgba(255,255,255,.05); color: rgba(255,255,255,.62); cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; white-space: nowrap; }
  .trends-tab.is-active { border-color: #cffe00; background: #cffe00; color: #12160a; }
  html.light .trends-tab { border-color: rgba(0,0,0,.1); background: rgba(0,0,0,.04); color: rgba(12,12,16,.62); }
  html.light .trends-tab.is-active { color: #12160a; }

  .trends-slider-actions { display: flex; flex-shrink: 0; gap: 6px; }
  .trends-slider-actions button { width: 29px; height: 29px; border: 1px solid rgba(255,255,255,.12); border-radius: 9px; background: rgba(255,255,255,.05); color: var(--vatan-text-page); cursor: pointer; }
  html.light .trends-slider-actions button { border-color: rgba(0,0,0,.1); background: rgba(0,0,0,.04); }
  .trends-tab-panel[hidden] { display: none; }
  .trends-four-slider { overflow-x: auto; scrollbar-width: none; scroll-behavior: smooth; }
  .trends-four-slider::-webkit-scrollbar { display: none; }
  .trends-four-track { display: flex; gap: 10px; direction: rtl; }
  .trends-four-item { flex: 0 0 calc(50% - 5px); min-width: 0; }

  .trends-other-list { display: grid; gap: 12px; }
  .trends-other-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
  .trends-feed-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-inline: -16px; }
  .trends-feed-grid .trends-card { width: 100%; aspect-ratio: 3 / 4; }
  .trends-banner { display: block; grid-column: 1 / -1; overflow: hidden; aspect-ratio: 3.6 / 1; border-radius: 13px; background: rgba(255,255,255,.06); }
  .trends-banner img { display: block; width: 100%; height: 100%; object-fit: cover; }
  .trends-banner-desktop { display: none; }
  .trends-banner-mobile { display: block; }
  .trends-empty { grid-column: 1 / -1; margin: 0; padding: 28px 12px; border: 1px dashed rgba(255,255,255,.14); border-radius: 12px; color: rgba(255,255,255,.52); font-size: 12px; text-align: center; }
  html.light .trends-empty { border-color: rgba(0,0,0,.14); color: rgba(12,12,16,.52); }

  @media (max-width: 639px) {
    .trends-time-grid, .trends-three-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .trends-time-column h3 { margin-bottom: 8px; font-size: 10.5px; }
    .trends-search { margin-top: 18px; }
  }

  @media (min-width: 640px) {
    .trends-page { max-width: 720px; }
    .trends-header { padding: 28px 28px 26px; }
    .trends-section { padding: 0 28px; }
    .trends-four-item { flex-basis: calc(33.333% - 7px); }
    .trends-feed-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .trends-feed-grid .trends-card { aspect-ratio: 3 / 4; }
    .trends-feed-grid { margin-inline: -28px; }
    .trends-banner-mobile { display: none; }
    .trends-banner-desktop { display: block; }
  }

  @media (min-width: 768px) {
    .trends-page { max-width: 900px; }
    .trends-header { padding: 32px 36px 28px; }
    .trends-section { padding: 0 36px; }
    .trends-four-item { flex-basis: calc(25% - 7.5px); }
    .trends-feed-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .trends-feed-grid { margin-inline: -36px; }
  }

  @media (min-width: 1024px) {
    .trends-page { max-width: none; padding: 0 114px 60px; }
    .trends-header { padding: 40px 0 30px; }
    .trends-section { padding: 0; margin-top: 34px; }
    .trends-section-heading h2 { font-size: 19px; }
    .trends-card-info strong { font-size: 13px; }
    .trends-card-info small { font-size: 11px; }
    .trends-feed-grid { gap: 14px; }
    .trends-feed-grid { margin-inline: 0; }
    .trends-banner { border-radius: 16px; }
  }

  @media (min-width: 1280px) {
    .trends-page { padding-right: var(--home-desktop-grid-gutter); padding-left: var(--home-desktop-grid-gutter); }
    .trends-header { padding-top: 44px; }
  }
</style>
