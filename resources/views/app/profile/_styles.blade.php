<style>

/* ══════════════════════════════════════════════════════
   DESIGN TOKENS — Dark (default) / Light
══════════════════════════════════════════════════════ */
:root {
  --bg-page:          #000000;
  --text-primary:     #ffffff;
  --text-secondary:   #ffffff;
  --icon:             #ffffff;
  --bg-card:          #3F3F3F;
  --bg-affiliate:     #0d2818;
  --border-affiliate: #1a5c32;
  --green:            #0BBF53;
  --accent:           #a07af5;
  --red:              #f05c5c;
  --border-subtle:    #222230;
  --bg-surface:       #111116;
}
html.light {
  --bg-page:          #ffffff;
  --text-primary:     #000000;
  --text-secondary:   #000000;
  --icon:             #000000;
  --bg-card:          #E5E5E5;
  --bg-affiliate:     #e8f8ee;
  --border-affiliate: #a8e6be;
  --green:            #0BBF53;
  --border-subtle:    #e0e0e0;
  --bg-surface:       #f5f5f5;
}

/* ══════════════════════════════════════════════════════
   FONT — YekanBakh
══════════════════════════════════════════════════════ */
.profile-page,
.profile-page * {
  font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif !important;
}

/* ══════════════════════════════════════════════════════
   BASE
══════════════════════════════════════════════════════ */
html, body { overflow-x: hidden; background: var(--bg-page) !important; color: var(--text-primary); }

.profile-page {
  width: 100%;
  max-width: 480px;
  margin: 0 auto;
  background: var(--bg-page);
  min-height: 100vh;
  padding-bottom: 120px;
}

/* ══════════════════════════════════════════════════════
   UTILITY
══════════════════════════════════════════════════════ */
.show-desktop { display: none !important; }
.hide-desktop { display: flex; }
.hero-left-group { display: none; }
.icon-filter { filter: brightness(0) invert(1); transition: filter 0.2s; }
html.light .icon-filter { filter: brightness(0) invert(0); }
.profile-page i[class*="fa-"] {
  font-family: "Font Awesome 6 Free" !important;
  font-weight: 900 !important;
  display: inline-block;
}

/* ══════════════════════════════════════════════════════
   HERO — mobile: stack + centered
══════════════════════════════════════════════════════ */
.profile-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: calc(env(safe-area-inset-top) + 36px) 16px 16px;
  gap: 12px;
}

/* ─── Avatar ─── */
.avatar-wrap { flex-shrink: 0; }

.avatar-ring {
  width: 100px; height: 100px;
  border-radius: 50%;
  padding: 3px;
  background: var(--green);
}
.avatar-inner {
  width: 100%; height: 100%;
  border-radius: 50%;
  padding: 2px;
  background: var(--bg-page);
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

/* ─── Hero Right Group ─── */
.hero-right-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  gap: 12px;
}

/* ─── Profile Info ─── */
.profile-info {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  text-align: center;
}

.name-row {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
}

.profile-name {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}

.profile-phone {
  font-size: 14.3px;
  color: rgba(168,196,168,1);
  margin: 0;
  letter-spacing: 0.5px;
}
html.light .profile-phone { color: #5a7a5a; }

/* ─── Plan Badge ─── */
.plan-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(11,191,83,0.12);
  border: 1px solid rgba(11,191,83,0.3);
  border-radius: 8px;
  padding: 4px 10px;
}
.plan-badge span {
  font-size: 11px;
  font-weight: 700;
  color: var(--green);
}

/* ══════════════════════════════════════════════════════
   STATS — ۴ ستون
══════════════════════════════════════════════════════ */
.stats-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  width: 100%;
  max-width: 360px;
}

/* stats-desktop — داخل hero-left-group (tablet/desktop) */
.stats-desktop {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 0;
  direction: rtl;
  width: 100%;
}

.stat-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 0 4px;
}

.stat-sep {
  width: 1px;
  height: 28px;
  background: var(--border-subtle);
  flex-shrink: 0;
}

.stat-number {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.1;
}

.stat-number--plan {
  font-size: 13px;
  font-weight: 700;
  color: var(--green);
}

.stat-label {
  font-size: 11px;
  color: rgba(168,196,168,1);
  white-space: nowrap;
}
html.light .stat-label { color: #5a7a5a; }

/* ══════════════════════════════════════════════════════
   ACTION BUTTONS
══════════════════════════════════════════════════════ */
.action-row {
  display: flex;
  gap: 5px;
  direction: rtl;
  width: 100%;
  max-width: 400px;
  margin-top: 6px;
}

.btn-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 10px;
  border: none;
  background: var(--bg-card);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  padding: 11px 14px;
  transition: opacity 0.15s;
}
.btn-card:active { opacity: 0.8; }

.btn-icon { width: 44px; height: 44px; padding: 0; flex-shrink: 0; }
.btn-support { flex: 1.2; }

.btn-subscribe {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 14px;
  border-radius: 10px;
  border: none;
  background: var(--green);
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s;
}
.btn-subscribe:active { opacity: 0.85; }

.subscribe-badge {
  background: #e91e8c;
  color: #ffffff;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 6px;
  white-space: nowrap;
}

/* ══════════════════════════════════════════════════════
   SETTINGS DROPDOWN
══════════════════════════════════════════════════════ */
.settings-wrap { position: relative; flex-shrink: 0; }

.settings-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 270px;
  background: #111116;
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.55);
  z-index: 300;
  overflow: hidden;
  transform-origin: top right;
  animation: menuIn 0.18s ease forwards;
}
html.light .settings-menu {
  background: #ffffff;
  border-color: #e0e0e0;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

@keyframes menuIn {
  from { transform: scale(0.9) translateY(-6px); opacity: 0; }
  to   { transform: scale(1)   translateY(0);    opacity: 1; }
}

.sm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-subtle);
}

.sm-user { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }

.sm-avatar-wrap { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
.sm-avatar-img  { width: 100%; height: 100%; object-fit: cover; }

.sm-name {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sm-phone { margin: 2px 0 0; font-size: 11px; color: rgba(168,196,168,1); }
html.light .sm-phone { color: #5a7a5a; }

.sm-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: transparent;
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 400;
  cursor: pointer;
  direction: rtl;
  text-align: right;
  border-bottom: 1px solid var(--border-subtle);
  transition: background 0.15s;
}
.sm-item:last-child { border-bottom: none; }
.sm-item:hover { background: rgba(255,255,255,0.05); }
html.light .sm-item:hover { background: rgba(0,0,0,0.04); }
.sm-item--danger { color: var(--red); }

/* ══════════════════════════════════════════════════════
   PROMO BANNER (mobile + desktop hero-left)
══════════════════════════════════════════════════════ */
.promo-section { padding: 0 16px; margin-top: 14px; }

.promo-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  direction: rtl;
  background: var(--bg-affiliate);
  border: 1px solid var(--border-affiliate);
  border-radius: 12px;
  padding: 12px 14px;
}
.promo-text {
  font-size: 13px;
  font-weight: 700;
  color: var(--green);
  margin: 0;
}
.promo-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--green);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  padding: 9px 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ══════════════════════════════════════════════════════
   TABS
══════════════════════════════════════════════════════ */
.tabs-section { margin-top: 20px; }

.profile-tabs {
  display: flex;
  align-items: center;
  border-bottom: 1px solid var(--border-subtle);
}

.profile-tab {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  position: relative;
  color: var(--text-primary);
  transition: color 0.2s;
}

.profile-tab::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 0; right: 0;
  height: 2px;
  background: var(--green);
  opacity: 0;
  transition: opacity 0.2s;
}
.profile-tab.active::after { opacity: 1; }

.tab-icon {
  filter: brightness(0) invert(1);
  transition: filter 0.2s, transform 0.15s;
}
.profile-tab.active .tab-icon { transform: scale(1.05); }
html.light .tab-icon { filter: brightness(0) invert(0); }

.tab-icon--svg {
  filter: none !important;
  color: var(--text-primary);
  transition: color 0.2s, transform 0.15s;
}
.profile-tab.active .tab-icon--svg { transform: scale(1.05); }

/* متن تب — موبایل پنهان */
.tab-label {
  display: none;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

/* ══════════════════════════════════════════════════════
   PANELS
══════════════════════════════════════════════════════ */
.profile-panel { width: 100%; }

.panel-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
}

.panel-saved {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2px;
}

.grid-cell {
  aspect-ratio: 4/5;
  overflow: hidden;
  position: relative;
  background: var(--bg-surface);
  border-radius: 3px;
}
.grid-img { width: 100%; height: 100%; object-fit: cover; display: block; }

.cell-badge {
  position: absolute; top: 6px; right: 6px;
  color: #ffffff; font-size: 11px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.65);
}
.saved-badge {
  position: absolute; top: 7px; left: 7px;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));
}

/* ══════════════════════════════════════════════════════
   PANEL: فایل‌ها
══════════════════════════════════════════════════════ */
.storage-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 16px;
}
.storage-header { display: flex; justify-content: space-between; margin-bottom: 8px; direction: rtl; }
.storage-title { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.storage-used  { font-size: 11px; color: rgba(168,196,168,1); }
html.light .storage-used { color: #5a7a5a; }

.storage-bar { width: 100%; height: 6px; background: var(--border-subtle); border-radius: 99px; overflow: hidden; }
.storage-fill { height: 100%; background: linear-gradient(to left, var(--green), #08a045); border-radius: 99px; }

.storage-footer {
  display: flex; justify-content: space-between;
  margin-top: 6px; direction: rtl;
  font-size: 10px; color: rgba(168,196,168,1);
}
html.light .storage-footer { color: #5a7a5a; }
.storage-free { color: rgba(255,255,255,0.3); }
html.light .storage-free { color: rgba(0,0,0,0.25); }

.files-sub-tabs { display: flex; gap: 6px; margin-bottom: 14px; }
.files-sub-tab {
  flex: 1; padding: 9px 8px; border-radius: 7px;
  border: 1px solid var(--border-subtle);
  background: var(--bg-surface);
  color: rgba(255,255,255,0.4);
  font-size: 13px; font-weight: 400; cursor: pointer;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}
html.light .files-sub-tab { color: rgba(0,0,0,0.35); }
.files-sub-tab.active {
  border-color: var(--green);
  background: rgba(11,191,83,0.1);
  color: var(--green);
  font-weight: 700;
}

.files-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  margin: 0 -16px;
  width: calc(100% + 32px);
}
.files-cell {
  aspect-ratio: 4/5;
  border-radius: 3px;
  overflow: hidden;
  background: var(--bg-surface);
  position: relative;
}

/* ══════════════════════════════════════════════════════
   PANEL: همکاری در فروش
══════════════════════════════════════════════════════ */
.referral-hero { text-align: center; margin-bottom: 20px; }
.referral-icon-wrap {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(11,191,83,0.12);
  border: 1px solid rgba(11,191,83,0.25);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 12px;
}
.referral-title { font-size: 17px; font-weight: 700; color: var(--text-primary); margin: 0 0 6px; }
.referral-sub   { font-size: 12px; color: rgba(255,255,255,0.5); margin: 0; line-height: 1.7; }
html.light .referral-sub { color: rgba(0,0,0,0.45); }

.referral-desc {
  background: var(--bg-affiliate);
  border: 1px solid var(--border-affiliate);
  border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; direction: rtl;
}
.referral-desc p { font-size: 13px; color: #a8e6be; line-height: 1.9; margin: 0; }
html.light .referral-desc p { color: #1a6e3a; }

.referral-stats { display: flex; gap: 8px; margin-bottom: 20px; direction: rtl; }
.referral-stat {
  flex: 1; background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 9px; padding: 12px; text-align: center;
}
.rs-number { font-size: 20px; font-weight: 700; color: var(--green); margin: 0; }
.rs-label  { font-size: 11px; color: rgba(168,196,168,1); margin: 4px 0 0; }
html.light .rs-label { color: #5a7a5a; }

.referral-actions { display: flex; flex-direction: column; gap: 10px; }
.btn-referral-outline {
  width: 100%; padding: 13px;
  border-radius: 12px; border: 1px solid rgba(11,191,83,0.35);
  background: transparent; color: var(--green);
  font-size: 14px; font-weight: 500; cursor: pointer;
}

/* ══════════════════════════════════════════════════════
   SMALL TABLET — 640px+  (دو ستون فعال)
══════════════════════════════════════════════════════ */
@media (min-width: 640px) {

  .show-desktop { display: inline-flex !important; }
  .hide-desktop { display: none !important; }

  .profile-page { max-width: 1100px; padding-bottom: 40px; }

  /* hero: دو ستون RTL — راست: آواتار+اطلاعات | چپ: آمار+بنر */
  .profile-hero {
    flex-direction: row;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 28px 36px 22px 0;
    margin-top: 0; position: static; z-index: auto;
    gap: 20px;
  }

  .hero-right-group {
    flex-direction: row;
    align-items: flex-start;
    gap: 14px;
    flex: 1;
    min-width: 0;
  }

  .avatar-ring { width: 106px; height: 106px; }

  .profile-info {
    flex: 1;
    min-width: 0;
    align-items: flex-start;
    text-align: right;
    padding-top: 0;
  }

  .name-row    { justify-content: flex-start; }
  .profile-name  { font-size: 17px; }
  .profile-phone { padding-right: 10px; font-size: 13px; }

  .plan-badge   { display: none !important; }
  .action-row   { max-width: 340px; width: 100%; margin-top: 4px; }
  .settings-wrap { display: none !important; }
  .promo-section { display: none; }

  /* ستون چپ آمار + بنر */
  .hero-left-group {
    display: flex;
    flex-direction: column;
    flex: 0 0 auto;
    width: 248px;
    gap: 12px;
    align-items: stretch;
    padding-top: 0;
  }

  .stats-desktop                    { width: 100% !important; box-sizing: border-box; }
  .hero-left-group .promo-banner    { width: 100%; box-sizing: border-box; }
  .stats-desktop .stat-col          { flex: 1; padding: 0 1px; }
  .stats-desktop .stat-sep          { height: 20px; }
  .stats-row                        { display: none !important; }

  /* تب‌ها */
  .tab-label   { display: inline; }
  .tabs-section { margin-top: 20px; }
  .profile-tabs { padding: 0 36px; justify-content: flex-start; }
  .profile-tab  { flex: none; height: 44px; gap: 6px; padding: 0 14px; }

  /* گرید */
  .panel-grid  { grid-template-columns: repeat(3, 1fr); gap: 5px; }
  .panel-saved { grid-template-columns: repeat(3, 1fr); gap: 5px; }
  .files-grid  { grid-template-columns: repeat(3, 1fr); gap: 5px; margin: 0; width: 100%; }
}

/* ══════════════════════════════════════════════════════
   LARGE TABLET — 768px+
══════════════════════════════════════════════════════ */
@media (min-width: 768px) {
  .profile-page { max-width: 1100px; }
  .profile-hero { padding: 36px 44px 26px 0; gap: 26px; }

  .hero-right-group { gap: 18px; }
  .avatar-ring  { width: 118px; height: 118px; }
  .profile-info { flex: 0 0 auto; min-width: 200px; }
  .profile-name  { font-size: 19px; }
  .profile-phone { font-size: 14.5px; padding-right: 12px; }

  .hero-left-group { width: 276px; gap: 13px; }
  .stats-desktop .stat-col { padding: 0 2px; }
  .stats-desktop .stat-sep { height: 22px; }

  .action-row   { max-width: 360px; }
  .tabs-section { margin-top: 22px; }
  .profile-tabs { padding: 0 44px; }
  .profile-tab  { height: 46px; padding: 0 16px; }

  .panel-grid  { grid-template-columns: repeat(4, 1fr); gap: 6px; }
  .panel-saved { grid-template-columns: repeat(3, 1fr); gap: 6px; }
  .files-grid  { grid-template-columns: repeat(4, 1fr); gap: 6px; }
}

/* ══════════════════════════════════════════════════════
   DESKTOP — 1024px+
══════════════════════════════════════════════════════ */
@media (min-width: 1024px) {
  .profile-page { max-width: 1200px; padding: 0 0 60px; }
  .profile-hero { padding: 44px 56px 32px 0; gap: 40px; }

  .avatar-ring      { width: 136px; height: 136px; }
  .hero-right-group { gap: 24px; }
  .profile-info     { min-width: 260px; }
  .profile-name     { font-size: 24px; }
  .profile-phone    { font-size: 15.7px; }

  /* آمار + بنر: ۱سانت عریض‌تر (هر طرف +۰.۵سانت ≈ ۱۹px) */
  .hero-left-group { width: 358px; padding-top: 0; }

  /* بنر ۱mm پایین‌تر — هم‌تراز لبه پایین دکمه خرید اشتراک */
  .hero-left-group .promo-banner { margin-top: 4px; }

  .action-row   { max-width: 380px; }
  .tabs-section { margin-top: 28px; }
  .promo-section { padding: 0 56px; }
  .profile-tabs { padding: 0 56px; justify-content: flex-start; }
  .profile-tab  { flex: none; height: 48px; gap: 7px; padding: 0 18px; }

  .panel-grid  { grid-template-columns: repeat(5, 1fr); gap: 8px; }
  .panel-saved { grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .files-grid  { grid-template-columns: repeat(5, 1fr); gap: 8px; }
}

/* ══════════════════════════════════════════════════════
   LARGE DESKTOP — 1280px+
══════════════════════════════════════════════════════ */
@media (min-width: 1280px) {
  .profile-page  { max-width: 1320px; padding: 0 0 60px; }
  .profile-hero  { padding: 48px 72px 36px 0; }
  .hero-left-group { width: 378px; }
  .promo-section { padding: 0 64px; }
  .profile-tabs  { padding: 0 72px; }
  .panel-grid    { grid-template-columns: repeat(6, 1fr); }
  .files-grid    { grid-template-columns: repeat(6, 1fr); }
}

</style>
