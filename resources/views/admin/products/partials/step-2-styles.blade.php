<style>
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
/* حالت فعال لیارا */
#lbl-api-liara.active-provider {
  background: rgba(16, 185, 129, 0.12);
  border-color: rgba(16, 185, 129, 0.45);
  color: #34d399;
}
</style>
