<style>
/* ── پنل‌های باز و بسته‌شونده گام دوم — هم‌فرم با آزمایشگاه مدل ── */
.step2-disclosure { position:relative; overflow:hidden; margin-bottom:20px; border:1px solid var(--b1); border-radius:14px; background:var(--s2); }
.step2-disclosure__header { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:16px 18px; }
.step2-disclosure__heading { display:flex; align-items:flex-start; gap:10px; min-width:0; }
.step2-disclosure__icon { display:grid; place-items:center; width:32px; height:32px; flex:0 0 32px; border-radius:9px; background:var(--primary-l); color:var(--primary); }
.step2-disclosure__heading h3 { margin:0; color:var(--text); font-size:12px; font-weight:800; }
.step2-disclosure__heading p { margin:3px 0 0; color:var(--text3); font-size:10px; line-height:1.7; }
.step2-disclosure__actions { display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
.step2-disclosure__toggle { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-width:180px; min-height:34px; padding:7px 10px; border:1px solid var(--b1); border-radius:9px; background:var(--s1); color:var(--text2); font-family:inherit; font-size:10px; font-weight:800; cursor:pointer; transition:border-color .2s ease,color .2s ease,background .2s ease; }
.step2-disclosure__toggle:hover,.step2-disclosure__toggle[aria-expanded="true"] { border-color:var(--accent); color:var(--text); }
.step2-disclosure__switch { position:relative; display:block; width:31px; height:17px; flex:0 0 31px; border-radius:999px; background:var(--b2); transition:background .2s ease; }
.step2-disclosure__switch span { position:absolute; top:3px; right:3px; width:11px; height:11px; border-radius:50%; background:var(--text3); transition:transform .2s ease,background .2s ease; }
.step2-disclosure__toggle[aria-expanded="true"] .step2-disclosure__switch { background:var(--green); }
.step2-disclosure__toggle[aria-expanded="true"] .step2-disclosure__switch span { transform:translateX(-14px); background:var(--card-bg); }
.step2-disclosure__toggle .fa-chevron-down { color:var(--text3); font-size:9px; transition:transform .2s ease; }
.step2-disclosure__toggle[aria-expanded="true"] .fa-chevron-down { transform:rotate(180deg); color:var(--accent); }
.step2-disclosure__drawer { padding:0 18px 16px; border-top:1px solid var(--b1); direction:rtl; }
.step2-disclosure__drawer.hidden { display:none; }
@media (max-width:640px) { .step2-disclosure__header { align-items:stretch; flex-direction:column; } .step2-disclosure__actions { justify-content:stretch; } .step2-disclosure__toggle { width:100%; } }

/* ── انتخاب دو مرحله‌ای provider و مدل ── */
.model-picker-field { position:relative; }
.model-picker-label { display:block; margin-bottom:6px; color:var(--text3); font-size:10px; font-weight:700; }
.model-picker-shell { position:relative; }
.model-picker-trigger { display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%; min-height:40px; padding:9px 11px; border:1px solid var(--b1); border-radius:9px; background:var(--s1); color:var(--text); font-size:11px; font-weight:700; text-align:right; cursor:pointer; }
.model-picker-trigger:hover,.model-picker-trigger:focus { border-color:var(--accent); outline:none; }
.model-picker-trigger i { color:var(--text3); font-size:9px; }
.model-picker-menu { position:absolute; z-index:40; top:calc(100% + 6px); right:0; left:0; max-height:330px; overflow:auto; padding:7px; border:1px solid var(--b1); border-radius:11px; background:var(--s2); box-shadow:var(--shadow-card); }
.model-picker-provider-head,.model-picker-provider-row { display:grid; grid-template-columns:1fr 1fr; align-items:center; gap:8px; }
.model-picker-provider-head { padding:5px 8px; color:var(--text3); font-size:9px; font-weight:800; border-bottom:1px solid var(--b1); }
.model-picker-provider-row { width:100%; padding:9px 8px; border:0; border-bottom:1px solid var(--b1); background:transparent; color:var(--text2); font-size:10px; text-align:right; cursor:pointer; }
.model-picker-provider-row:last-child { border-bottom:0; }
.model-picker-provider-row:hover,.model-picker-provider-row.is-selected { background:var(--primary-l); color:var(--text); }
.model-picker-provider-row span:last-child { color:var(--text3); text-align:left; }
.model-picker-empty { display:block; margin-top:5px; color:var(--red); font-size:10px; }
.model-picker-filter { width:100%; min-height:40px; padding:9px 11px; border:1px solid var(--b1); border-radius:9px; outline:none; color:var(--text); background:var(--s1); font-family:inherit; font-size:10px; font-weight:700; cursor:pointer; }
.model-picker-filter:hover,.model-picker-filter:focus { border-color:var(--accent); }
.model-picker-model-menu { min-width:650px; right:auto; left:0; }
.model-picker-model-head,.model-picker-model-row { display:grid; grid-template-columns:1.6fr 1fr .7fr; align-items:center; gap:8px; }
.model-picker-model-head { padding:6px 8px; color:var(--text3); font-size:8.5px; font-weight:800; border-bottom:1px solid var(--b1); }
.model-picker-model-row { width:100%; padding:9px 8px; border:0; border-bottom:1px solid var(--b1); background:transparent; color:var(--text2); font-size:9px; line-height:1.5; text-align:right; cursor:pointer; }
.model-picker-model-row:last-child { border-bottom:0; }
.model-picker-model-row:hover,.model-picker-model-row.is-selected { background:var(--primary-l); color:var(--text); }
.model-picker-model-row > span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.model-picker-model-name { display:flex; flex-direction:column; gap:1px; }
.model-picker-model-name b,.model-picker-model-name small { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.model-picker-model-name small { color:var(--text3); font-size:8px; }
.model-quality-grade { color:var(--warning); font-weight:800; white-space:nowrap; }
@media (max-width:760px) { .model-picker-model-menu { min-width:0; width:calc(100vw - 54px); right:0; left:auto; } .model-picker-model-head,.model-picker-model-row { min-width:420px; } .model-picker-model-menu { overflow-x:auto; } }
.model-grade-card { min-width:0; min-height:76px; display:flex; flex-direction:column; justify-content:space-between; gap:5px; overflow:hidden; }
.model-grade-card.grade-1 { border-color:color-mix(in srgb,var(--accent) 78%,var(--b1)); background:color-mix(in srgb,var(--accent) 12%,var(--s1)); }
.model-grade-card.grade-2 { border-color:color-mix(in srgb,var(--primary) 72%,var(--b1)); background:color-mix(in srgb,var(--primary) 12%,var(--s1)); }
.model-grade-card.grade-3 { border-color:color-mix(in srgb,var(--success) 66%,var(--b1)); background:color-mix(in srgb,var(--success) 11%,var(--s1)); }
.model-grade-card.grade-4 { border-color:color-mix(in srgb,var(--warning) 72%,var(--b1)); background:color-mix(in srgb,var(--warning) 12%,var(--s1)); }
.model-grade-card:hover,.model-grade-card:focus-visible { transform:translateY(-1px); box-shadow:var(--shadow-card); outline:none; }
.model-grade-card-top,.model-grade-card-meta { display:flex; align-items:center; gap:5px; min-width:0; line-height:1; }
.model-grade-card-top { justify-content:flex-start; }
.model-grade-card-meta { justify-content:space-between; color:var(--text3); font-size:8.8px; }
.model-grade-card-meta b { color:var(--text2); font-size:9.4px; }
.model-grade-badge { flex:0 0 auto; padding:3px 5px; border-radius:5px; color:var(--text); background:var(--b1); font-size:8.8px; font-weight:900; }
.grade-1 .model-grade-badge { color:var(--text-h); background:color-mix(in srgb,var(--accent) 26%,var(--s1)); }
.grade-2 .model-grade-badge { color:var(--text); background:color-mix(in srgb,var(--primary) 22%,var(--s1)); }
.grade-3 .model-grade-badge { color:var(--text); background:color-mix(in srgb,var(--success) 20%,var(--s1)); }
.grade-4 .model-grade-badge { color:var(--text); background:color-mix(in srgb,var(--warning) 24%,var(--s1)); }
.model-grade-hint,.model-grade-provider { color:var(--text3); font-size:8.25px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.model-grade-provider { margin-right:auto; }
.model-grade-card-name { display:block; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text); font-size:10.5px; font-weight:800; line-height:1.35; }
#recommended-product-models,#fallback-recommended-product-models { grid-template-columns:repeat(4,minmax(0,1fr)); }
@media (max-width:1100px) { #recommended-product-models,#fallback-recommended-product-models { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media (max-width:760px) { #recommended-product-models,#fallback-recommended-product-models { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:480px) { #recommended-product-models,#fallback-recommended-product-models { grid-template-columns:1fr; } }
.fallback-toggle-card { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 13px; border:1px solid var(--b1); border-radius:11px; background:var(--s1); cursor:pointer; }
.fallback-toggle-card > span { display:flex; flex-direction:column; gap:2px; min-width:0; }
.fallback-toggle-card b { color:var(--text); font-size:11px; }
.fallback-toggle-card small { color:var(--text3); font-size:9.5px; }
.fallback-toggle-card input { position:absolute; opacity:0; pointer-events:none; }
.fallback-toggle-card i { position:relative; display:block; width:38px; height:21px; flex:0 0 38px; border-radius:999px; background:var(--b2); transition:background .2s ease; }
.fallback-toggle-card i::after { content:''; position:absolute; top:3px; right:3px; width:15px; height:15px; border-radius:50%; background:var(--text3); transition:transform .2s ease,background .2s ease; }
.fallback-toggle-card:has(input:checked) { border-color:var(--primary); background:var(--primary-l); }
.fallback-toggle-card:has(input:checked) i { background:var(--success); }
.fallback-toggle-card:has(input:checked) i::after { transform:translateX(-17px); background:var(--card-bg); }
.fallback-configuration.hidden { display:none; }

/* ── دکمه‌های toggle provider در step-2 ── */
.api-provider-btn {
  background: transparent;
  border-color: var(--b2, #2d2d3d);
  color: var(--text3, #6b7280);
}
.api-provider-btn:hover {
  border-color: var(--b1, #4b5563);
  color: var(--text2, #9ca3af);
}
/* حالت فعال OpenRouter */
#lbl-api-openrouter.active-provider {
  background: rgba(160, 122, 245, 0.12);
  border-color: rgba(160, 122, 245, 0.45);
  color: #a07af5;
}
</style>
