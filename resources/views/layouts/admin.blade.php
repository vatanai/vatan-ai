<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">{{-- overridden by inline script below --}}
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'وطن استودیو — Admin Panel')</title>
{{-- Theme init — MUST run before CSS renders to avoid flash --}}
<script>
  (function(){
    var t = localStorage.getItem('vtn-admin-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>

<!-- Vazirmatn Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<!-- Font Awesome 6.5 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Chart.js 4.4.1 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<!-- Tailwind CSS CDN (JIT) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  darkMode: false,
  theme: {
    extend: {
      colors: {
        green:  '#0BBF53',
        accent: '#a07af5',
        orange: '#f5923a',
        red:    '#f05c5c',
        cyan:   '#06b6d4',
        's1':   'var(--s1)',
        's2':   'var(--s2)',
      },
      textColor: {
        'watan-text':  'var(--text)',
        'watan-text2': 'var(--text2)',
        'watan-text3': 'var(--text3)',
      },
      borderColor: {
        'b1': 'var(--b1)',
        'b2': 'var(--b2)',
      }
    }
  }
}
</script>

<style>
/* =====================================================
   THEME TOKENS — Light Mode (:root)
===================================================== */
:root {
  --primary:        #16594f;
  --primary-l:      rgba(22,89,79,.10);
  --primary-m:      rgba(22,89,79,.18);
  --accent:         #C2FD75;
  --logo-green:     #0bbf53;

  --page-bg:        #f5f5f5;
  --sb-bg:          #ffffff;
  --topbar-bg:      #ffffff;
  --card-bg:        #ffffff;

  --border:         #E5E6E6;
  --divider:        #EAECEC;

  --text-h:         #000000;
  --text-main:      #000000;
  --text-soft:      #686E6B;

  --nav-text:       #2a2a2a;
  --nav-hover:      rgba(22,89,79,.06);
  --nav-active:     rgba(22,89,79,.10);
  --nav-active-t:   #16594f;

  --sub-line:       #D6D9D8;
  --sub-dot:        #C8CBCA;
  --sub-dot-active: #16594f;
  --sub-text:       #686E6B;
  --sub-text-active:#16594f;

  --input-bg:       #f5f5f5;
  --shadow-card:    rgba(145,158,171,.20) 0 0 2px, rgba(145,158,171,.12) 0 12px 24px -4px;
  --shadow-sb:      -4px 0 20px rgba(0,0,0,.05);

  /* ── Old variable aliases (backward compat for sub-pages) ── */
  --bg:          var(--page-bg);
  --s1:          var(--card-bg);
  --s2:          #f0f2f1;
  --b1:          var(--border);
  --b2:          #d0d3d2;
  --text:        var(--text-h);
  --text2:       var(--text-main);
  --text3:       var(--text-soft);
  --watan-text:  var(--text-h);
  --watan-text2: var(--text-main);
  --watan-text3: var(--text-soft);
  --cyan:        #06b6d4;
  --green:       #0BBF53;
  --red:         #f05c5c;
  --orange:      #f5923a;
}

/* Dark Mode — default because html[data-theme="dark"] is set */
[data-theme="dark"] {
  --page-bg:        #141a18;
  --sb-bg:          #030f09;
  --topbar-bg:      #030f09;
  --card-bg:        #030f09;

  --border:         #0e1e14;
  --divider:        #0a1710;

  --text-h:         #e3e8f0;
  --text-main:      #a9b4c7;
  --text-soft:      #60748a;

  --nav-text:       #8a99ad;
  --nav-hover:      rgba(255,255,255,.05);
  --nav-active:     rgba(22,89,79,.25);
  --nav-active-t:   #C2FD75;

  --sub-line:       #1e2d3d;
  --sub-dot:        #263545;
  --sub-dot-active: #C2FD75;
  --sub-text:       #60748a;
  --sub-text-active:#C2FD75;

  --input-bg:       #0d1a10;
  --shadow-card:    0 4px 24px rgba(0,0,0,.35);
  --shadow-sb:      -4px 0 30px rgba(0,0,0,.3);

  /* ── Old variable aliases dark ── */
  --bg:          #141a18;
  --s1:          #030f09;
  --s2:          #061208;
  --b1:          #0e1e14;
  --b2:          #162a1e;
  --text:        #e3e8f0;
  --text2:       #a9b4c7;
  --text3:       #60748a;
  --watan-text:  #e3e8f0;
  --watan-text2: #a9b4c7;
  --watan-text3: #60748a;
}

/* =====================================================
   RESET
===================================================== */
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body {
  font-family:'Vazirmatn',sans-serif;
  background:var(--page-bg);
  color:var(--text-main);
  direction:rtl;
  min-height:100vh;
  overflow-x:hidden;
  transition:background .3s ease, color .3s ease;
}
a { text-decoration:none; color:inherit; }

/* =====================================================
   GLOBAL SCROLLBAR HIDING
===================================================== */
*::-webkit-scrollbar { display:none !important; }
* { scrollbar-width:none !important; }
html { overflow-y:auto; }
body { overflow-x:hidden; }

/* =====================================================
   MINI ICON RAIL  (64px, fixed right:0)
===================================================== */
.mini-rail {
  position:fixed;
  right:0; top:0; bottom:0; width:64px;
  background:var(--sb-bg);
  border-left:1px solid var(--border);
  display:flex; flex-direction:column; align-items:center;
  z-index:310;
  padding:14px 0 20px; gap:4px;
  transition:background .3s, border-color .3s;
}
.mini-rail-logo {
  width:38px; height:38px; border-radius:11px;
  background:linear-gradient(135deg,#0bbf53 0%,#16594f 100%);
  display:flex; align-items:center; justify-content:center;
  margin-bottom:10px; flex-shrink:0;
  box-shadow:0 4px 14px rgba(11,191,83,.35);
  overflow:hidden; cursor:pointer;
}
.mini-rail-logo img { width:22px; height:22px; object-fit:contain; filter:brightness(0) invert(1); }
.mini-rail-divider {
  width:28px; height:1px; background:var(--divider); margin:4px 0;
}
.mini-rail-spacer { flex:1; }
.mini-btn {
  width:38px; height:38px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:13px; color:var(--text-soft);
  cursor:pointer; transition:all .18s;
  position:relative; border:1px solid transparent;
}
.mini-btn:hover { background:var(--nav-hover); color:var(--primary); }
.mini-btn.active {
  background:var(--primary-l); color:var(--primary); border-color:var(--primary-m);
}
[data-theme="dark"] .mini-btn.active { color:var(--accent); }
.mini-btn-tooltip {
  position:absolute; left:calc(100% + 10px);
  background:var(--text-h); color:var(--sb-bg);
  font-size:11px; font-weight:600;
  padding:4px 10px; border-radius:7px;
  white-space:nowrap; pointer-events:none;
  opacity:0; transform:translateX(-4px);
  transition:all .18s; z-index:999;
}
.mini-btn:hover .mini-btn-tooltip { opacity:1; transform:translateX(0); }

/* =====================================================
   SIDEBAR  (265px, fixed right:64px)
===================================================== */
.sidebar {
  position:fixed;
  right:64px; top:0; bottom:0; width:265px;
  background:var(--sb-bg);
  border-left:1px solid var(--border);
  display:flex; flex-direction:column;
  z-index:300; overflow-y:auto; overflow-x:hidden;
  scrollbar-width:none;
  transition:background .3s, border-color .3s, width .28s cubic-bezier(.4,0,.2,1);
  box-shadow:var(--shadow-sb);
}
.sidebar::-webkit-scrollbar { display:none; }

/* Collapsed */
.sidebar.sb-collapsed {
  width:68px; overflow:hidden;
}
.sidebar.sb-collapsed .sb-logo-texts,
.sidebar.sb-collapsed .sb-section,
.sidebar.sb-collapsed .nav-label,
.sidebar.sb-collapsed .nav-badge,
.sidebar.sb-collapsed .nav-chev,
.sidebar.sb-collapsed .submenu,
.sidebar.sb-collapsed .sb-divider,
.sidebar.sb-collapsed .sb-user-info,
.sidebar.sb-collapsed .future-section { display:none !important; }
.sidebar.sb-collapsed .nav-item { padding:3px 4px; }
.sidebar.sb-collapsed .nav-link { justify-content:center; padding:9px 2px; gap:0; }
.sidebar.sb-collapsed .nav-icon { width:44px; height:44px; flex-shrink:0; }
.sidebar.sb-collapsed .sb-logo { justify-content:center; padding:18px 8px 16px; gap:0; }

/* Logo */
.sb-logo {
  display:flex; align-items:center; gap:12px;
  padding:20px 18px 16px;
  border-bottom:1px solid var(--border);
  flex-shrink:0;
}
.sb-logo-mark {
  width:40px; height:40px; border-radius:12px;
  background:linear-gradient(135deg,#0bbf53 0%,#16594f 100%);
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 4px 14px rgba(11,191,83,.38);
  flex-shrink:0; overflow:hidden;
}
.sb-logo-mark img { width:26px; height:26px; object-fit:contain; filter:brightness(0) invert(1); }
.sb-logo-texts { display:flex; flex-direction:column; }
.sb-logo-name {
  font-size:18px; font-weight:900; letter-spacing:-.5px;
  color:var(--text-h); line-height:1;
}
.sb-logo-name .dot-green { color:var(--logo-green); }
.sb-logo-sub {
  font-size:9px; font-weight:600; letter-spacing:1.8px;
  color:var(--text-soft); text-transform:uppercase; margin-top:3px;
}

/* Section label */
.sb-section {
  font-size:9.5px; font-weight:800; letter-spacing:2.5px;
  text-transform:uppercase; color:var(--text-soft);
  padding:18px 18px 7px;
}

/* Nav item */
.nav-item { padding:2px 10px; }
.nav-link {
  display:flex; align-items:center; gap:11px;
  padding:9px 12px; border-radius:12px;
  cursor:pointer; transition:all .2s; user-select:none;
}
.nav-link:hover { background:var(--nav-hover); }
.nav-link.active { background:var(--nav-active); }
.nav-icon {
  width:37px; height:37px; border-radius:10px;
  background:var(--input-bg); border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center;
  font-size:15px; color:var(--text-soft);
  flex-shrink:0; transition:all .2s;
}
.nav-link:hover .nav-icon { color:var(--primary); border-color:var(--primary-m); }
.nav-link.active .nav-icon { background:#16594f; color:#C2FD75; border-color:#16594f; }
.nav-label {
  flex:1; font-size:13.5px; font-weight:600;
  color:var(--nav-text); transition:color .2s;
}
.nav-link:hover .nav-label { color:var(--text-h); }
.nav-link.active .nav-label { color:var(--nav-active-t); font-weight:700; }
.nav-chev {
  font-size:10px; color:var(--text-soft);
  transition:transform .28s cubic-bezier(.4,0,.2,1);
  flex-shrink:0;
}
.nav-chev.open { transform:rotate(180deg); }
.nav-badge {
  font-size:9px; font-weight:800;
  padding:2px 7px; border-radius:20px;
  flex-shrink:0;
}
.badge-red { background:#ef4444; color:#fff; }
.badge-purple { background:rgba(160,122,245,.15); color:#a07af5; border:1px solid rgba(160,122,245,.3); }

/* =====================================================
   SUBMENU Level 2
===================================================== */
.submenu { max-height:0; overflow:hidden; transition:max-height .35s cubic-bezier(.4,0,.2,1); }
.submenu.open { max-height:1000px; }

.sub-track {
  position:relative; padding:4px 0 10px; margin-right:30px;
}
.sub-track::before {
  content:''; position:absolute;
  right:0; top:4px; bottom:22px; width:1.5px;
  background:linear-gradient(to bottom,
    var(--sub-dot-active) 0%,
    var(--sub-dot-active) var(--line-pct,0%),
    var(--sub-line) var(--line-pct,0%),
    transparent 100%);
  border-radius:1px; transition:background .3s;
}
.sub-item {
  display:flex; align-items:center; gap:10px;
  padding:8px 10px; margin:2px 20px 2px 14px;
  border-radius:9px; cursor:pointer;
  transition:all .18s; position:relative; overflow:visible;
}
.sub-item::before {
  content:''; position:absolute;
  right:-20px; top:0; bottom:50%; width:20px;
  border-right:1.5px solid var(--sub-line);
  border-bottom:1.5px solid var(--sub-line);
  border-bottom-right-radius:10px;
  pointer-events:none; transition:border-color .2s;
}
.sub-item:hover { background:var(--nav-hover); }
.sub-item:hover::before { border-color:var(--primary); }
.sub-item.active { background:var(--nav-active); }
.sub-item.active::before { border-color:var(--sub-dot-active); }
.sub-item.above-active::before { border-color:var(--sub-dot-active); }
.sub-dot {
  width:7px; height:7px; border-radius:50%;
  background:var(--sub-dot); flex-shrink:0; transition:all .2s;
}
.sub-item:hover .sub-dot { background:var(--primary); }
.sub-item.active .sub-dot {
  background:var(--sub-dot-active); width:8px; height:8px;
  box-shadow:0 0 8px rgba(22,89,79,.45);
}
[data-theme="dark"] .sub-item.active .sub-dot { box-shadow:0 0 8px rgba(194,253,117,.5); }
.sub-label {
  flex:1; font-size:12.5px; font-weight:500;
  color:var(--sub-text); transition:color .18s;
}
.sub-item:hover .sub-label { color:var(--text-h); }
.sub-item.active .sub-label { color:var(--sub-text-active); font-weight:700; }
.sub-chev {
  font-size:9px; color:var(--text-soft);
  transition:transform .25s cubic-bezier(.4,0,.2,1); flex-shrink:0;
}
.sub-chev.open { transform:rotate(180deg); }

/* =====================================================
   SUBMENU Level 3
===================================================== */
.sub-sub-wrap { max-height:0; overflow:hidden; transition:max-height .3s cubic-bezier(.4,0,.2,1); }
.sub-sub-wrap.open { max-height:500px; }
.sub-sub-track {
  position:relative; padding:2px 0 6px; margin-right:18px;
}
.sub-sub-track::before {
  content:''; position:absolute;
  right:0; top:4px; bottom:14px; width:1px;
  background:linear-gradient(to bottom,
    var(--sub-dot-active) 0%, var(--sub-dot-active) var(--line-pct,0%),
    var(--sub-line) var(--line-pct,0%), transparent 100%);
  border-radius:1px;
}
.sub-sub-item {
  display:flex; align-items:center; gap:8px;
  padding:6px 8px; margin:1px 14px 1px 16px;
  border-radius:7px; cursor:pointer;
  position:relative; overflow:visible; transition:all .15s;
}
.sub-sub-item::before {
  content:''; position:absolute;
  right:-14px; top:0; bottom:50%; width:14px;
  border-right:1px solid var(--sub-line);
  border-bottom:1px solid var(--sub-line);
  border-bottom-right-radius:8px;
  pointer-events:none; transition:border-color .18s;
}
.sub-sub-item:hover { background:var(--nav-hover); }
.sub-sub-item:hover::before { border-color:var(--primary); }
.sub-sub-item.active { background:var(--nav-active); }
.sub-sub-item.active::before { border-color:var(--sub-dot-active); }
.sub-sub-item.above-active::before { border-color:var(--sub-dot-active); }
.sub-sub-dot { width:5px; height:5px; border-radius:50%; background:var(--sub-dot); flex-shrink:0; transition:all .15s; }
.sub-sub-item:hover .sub-sub-dot { background:var(--primary); }
.sub-sub-item.active .sub-sub-dot { background:var(--sub-dot-active); width:6px; height:6px; }
.sub-sub-label {
  flex:1; font-size:11.5px; font-weight:500;
  color:var(--sub-text); transition:color .15s;
}
.sub-sub-item:hover .sub-sub-label { color:var(--text-h); }
.sub-sub-item.active .sub-sub-label { color:var(--sub-text-active); font-weight:600; }

/* =====================================================
   SIDEBAR EXTRAS
===================================================== */
.sb-divider { height:1px; background:var(--divider); margin:8px 14px; }

.sb-user {
  display:flex; align-items:center; gap:10px;
  padding:12px 16px; margin-top:auto;
  border-top:1px solid var(--border); flex-shrink:0;
  cursor:pointer; transition:background .2s;
}
.sb-user:hover { background:var(--nav-hover); }
.sb-av {
  width:36px; height:36px; border-radius:50%;
  background:linear-gradient(135deg,#16594f,#0bbf53);
  display:flex; align-items:center; justify-content:center;
  font-size:13px; font-weight:800; color:#fff; flex-shrink:0;
  border:2px solid var(--accent);
}
.sb-user-info { display:flex; flex-direction:column; }
.sb-uname { font-size:12px; font-weight:700; color:var(--text-h); }
.sb-urole { font-size:10px; color:var(--text-soft); margin-top:1px; }

/* Future section */
.future-section {}
.sb-future-toggle {
  display:flex; align-items:center; gap:8px;
  padding:10px 18px 6px;
  cursor:pointer; user-select:none;
  transition:opacity .2s;
  opacity:.65;
}
.sb-future-toggle:hover { opacity:1; }
.sb-future-label {
  flex:1; font-size:9px; font-weight:800;
  letter-spacing:2px; text-transform:uppercase; color:#a07af5;
}
.sb-future-chev {
  font-size:9px; color:#a07af5;
  transition:transform .28s cubic-bezier(.4,0,.2,1);
}
.sb-future-chev.open { transform:rotate(180deg); }
.future-wrap { max-height:0; overflow:hidden; transition:max-height .4s cubic-bezier(.4,0,.2,1); }
.future-wrap.open { max-height:3000px; }

/* Future nav items (dimmer) */
.future-nav-item { padding:1px 10px; }
.future-nav-link {
  display:flex; align-items:center; gap:10px;
  padding:7px 12px; border-radius:10px;
  cursor:pointer; transition:all .2s; user-select:none;
  opacity:.5;
}
.future-nav-link:hover { background:var(--nav-hover); opacity:.85; }
.future-nav-icon {
  width:32px; height:32px; border-radius:8px;
  background:transparent; border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center;
  font-size:12px; color:var(--text-soft); flex-shrink:0;
}
.future-nav-label { flex:1; font-size:12.5px; font-weight:500; color:var(--nav-text); }

.future-sub-section {
  font-size:8px; font-weight:800; letter-spacing:2px; text-transform:uppercase;
  color:var(--text-soft); padding:10px 18px 4px; opacity:.55;
}
.future-sub-wrap { max-height:0; overflow:hidden; transition:max-height .3s cubic-bezier(.4,0,.2,1); }
.future-sub-wrap.open { max-height:600px; }
.future-sub-track { position:relative; padding:2px 0 8px; margin-right:24px; }
.future-sub-track::before {
  content:''; position:absolute;
  right:0; top:4px; bottom:14px; width:1px;
  background:linear-gradient(to bottom,#a07af5,var(--sub-line) 70%,transparent);
  opacity:.25; border-radius:1px;
}
.future-sub-item {
  display:flex; align-items:center; gap:8px;
  padding:5px 8px; margin:1px 18px 1px 12px;
  border-radius:7px; cursor:pointer;
  position:relative; overflow:visible; transition:all .15s; opacity:.45;
}
.future-sub-item::before {
  content:''; position:absolute;
  right:-18px; top:0; bottom:50%; width:18px;
  border-right:1px solid var(--sub-line);
  border-bottom:1px solid var(--sub-line);
  border-bottom-right-radius:8px; opacity:.4; pointer-events:none;
}
.future-sub-item:hover { background:var(--nav-hover); opacity:.8; }
.future-sub-dot { width:4px; height:4px; border-radius:50%; background:var(--sub-dot); flex-shrink:0; }
.future-sub-label { flex:1; font-size:11px; font-weight:500; color:var(--sub-text); }

/* =====================================================
   TOPBAR  (fixed top:0, right:329px)
===================================================== */
.topbar {
  position:fixed;
  top:0; right:329px; left:0; height:68px;
  background:var(--topbar-bg);
  border-bottom:1px solid var(--border);
  display:flex; align-items:center;
  padding:0 22px; gap:14px; z-index:200;
  transition:background .3s, border-color .3s, right .28s cubic-bezier(.4,0,.2,1);
  box-shadow:0 1px 10px rgba(0,0,0,.05);
}
.topbar.sb-collapsed-tb { right:132px; }

.tb-menu-btn {
  width:36px; height:36px; border-radius:9px;
  border:1px solid var(--border); background:var(--card-bg);
  color:var(--text-soft); font-size:14px;
  cursor:pointer; display:flex; align-items:center; justify-content:center;
  transition:all .2s; flex-shrink:0;
}
.tb-menu-btn:hover { border-color:var(--primary); color:var(--primary); }

.tb-breadcrumb {
  display:flex; align-items:center; gap:6px;
  font-size:12.5px; color:var(--text-soft);
}
.tb-breadcrumb .crumb-active { color:var(--text-h); font-weight:700; }
.tb-breadcrumb i { font-size:9px; }

.tb-search {
  flex:1; max-width:280px; margin-right:auto; position:relative;
}
.tb-search input {
  width:100%; height:38px;
  background:var(--input-bg); border:1px solid var(--border);
  border-radius:10px; padding:0 38px 0 14px;
  font-family:'Vazirmatn',sans-serif;
  font-size:12.5px; color:var(--text-main);
  outline:none; transition:all .2s;
}
.tb-search input:focus { border-color:var(--primary); background:var(--card-bg); }
.tb-search input::placeholder { color:var(--text-soft); }
.tb-search .tb-si {
  position:absolute; right:12px; top:50%;
  transform:translateY(-50%); color:var(--text-soft);
  font-size:13px; pointer-events:none;
}

.tb-actions { display:flex; align-items:center; gap:6px; }
.tb-btn {
  width:38px; height:38px; border-radius:10px;
  border:1px solid var(--border); background:var(--card-bg);
  color:var(--text-h); font-size:15px;
  cursor:pointer; display:flex; align-items:center; justify-content:center;
  transition:all .2s; position:relative;
}
.tb-btn:hover { border-color:var(--primary); color:var(--primary); }
.tb-notif {
  position:absolute; top:7px; right:7px;
  width:7px; height:7px; border-radius:50%;
  background:#ef4444; border:2px solid var(--topbar-bg);
}
.tb-sep { width:1px; height:28px; background:var(--border); margin:0 4px; }

.tb-live {
  display:flex; align-items:center; gap:8px;
  padding:6px 14px; border-radius:10px;
  border:1px solid var(--border); background:var(--card-bg);
  cursor:pointer; transition:all .2s; user-select:none;
}
.tb-live:hover { border-color:var(--primary); }
.tb-live-dot {
  width:7px; height:7px; border-radius:50%; background:#22c55e;
  box-shadow:0 0 8px rgba(34,197,94,.6);
  animation:livePulse 2s infinite;
}
@keyframes livePulse {
  0%,100% { opacity:1; transform:scale(1); }
  50% { opacity:.6; transform:scale(1.25); }
}
.tb-live-text { font-size:11.5px; font-weight:700; color:var(--primary); }
[data-theme="dark"] .tb-live-text { color:var(--accent); }
.tb-refresh-btn {
  font-size:10.5px; font-weight:700; color:var(--text-soft);
  padding:2px 8px; border-radius:6px;
  background:var(--input-bg); border:1px solid var(--border);
  cursor:pointer; transition:all .2s;
}
.tb-refresh-btn:hover { color:var(--primary); border-color:var(--primary); }

/* =====================================================
   MAIN CONTENT
===================================================== */
.admin-content {
  margin-right:329px;
  padding-top:68px;
  min-height:100vh;
  transition:margin-right .28s cubic-bezier(.4,0,.2,1);
}
.admin-content.sb-collapsed-mc { margin-right:132px; }

/* =====================================================
   CARD STYLES (shared)
===================================================== */
.vtn-card {
  background:var(--card-bg);
  border:1px solid var(--border);
  border-radius:16px;
  box-shadow:var(--shadow-card);
  transition:background .3s, border-color .3s;
}

/* =====================================================
   SIDEBAR LOGO IMAGE
===================================================== */
.sb-logo-img {
  height:22px; max-width:120px;
  object-fit:contain;
}
[data-theme="light"] .sb-logo-img {
  filter: brightness(0) saturate(100%) invert(27%) sepia(60%) saturate(452%) hue-rotate(118deg) brightness(94%) contrast(96%);
}
[data-theme="dark"] .sb-logo-img { filter: brightness(0) invert(1); }

/* =====================================================
   NEUTRALIZE OLD INNER LAYOUT WRAPPERS
   (sub-pages that still have the old sidebar/header inside @section('content'))
===================================================== */
#adminContent .admin-sidebar,
#adminContent aside.admin-sidebar { display:none !important; }
#adminContent .admin-header,
#adminContent header.admin-header { display:none !important; }
#adminContent .admin-main { margin-right:0 !important; }
#adminContent .admin-wrap { background:transparent !important; }
/* For Tailwind-based old wrappers (e.g. class="flex min-h-screen bg-[#0c0c10]") */
#adminContent > [class*="flex"] > [class*="mr-64"],
#adminContent > [class*="flex"] > main[class*="mr-64"] { margin-right:0 !important; }
#adminContent > [class*="min-h-screen"] { background:transparent !important; color:inherit !important; }
</style>

@stack('styles')
{{-- FORCED THEME + FONT OVERRIDE — must come AFTER @stack('styles') to win cascade --}}
<style>
html[data-theme="dark"] {
  --bg:#141a18 !important;  --s1:#030f09 !important;  --s2:#061208 !important;
  --b1:#0e1e14 !important;  --b2:#162a1e !important;
  --text:#e3e8f0 !important; --text2:#a9b4c7 !important; --text3:#60748a !important;
  --watan-text:#e3e8f0 !important; --watan-text2:#a9b4c7 !important; --watan-text3:#60748a !important;
  --page-bg:#141a18 !important; --card-bg:#030f09 !important;
  --topbar-bg:#030f09 !important; --sb-bg:#030f09 !important;
  --border:#0e1e14 !important; --text-h:#e3e8f0 !important;
  --text-main:#a9b4c7 !important; --text-soft:#60748a !important;
}
html[data-theme="light"] {
  --bg:#f5f5f5 !important;  --s1:#ffffff !important;  --s2:#f0f2f1 !important;
  --b1:#E5E6E6 !important;  --b2:#d0d3d2 !important;
  --text:#000000 !important; --text2:#000000 !important; --text3:#686E6B !important;
  --watan-text:#000000 !important; --watan-text2:#000000 !important; --watan-text3:#686E6B !important;
  --page-bg:#f5f5f5 !important; --card-bg:#ffffff !important;
  --topbar-bg:#ffffff !important; --sb-bg:#ffffff !important;
  --border:#E5E6E6 !important; --text-h:#000000 !important;
  --text-main:#000000 !important; --text-soft:#686E6B !important;
}
/* GLOBAL FONT — remove IRANSansXFaNum and any other font from sub-pages */
*, *::before, *::after { font-family:'Vazirmatn',sans-serif !important; }
</style>
</head>
<body>

{{-- ▌ MINI ICON RAIL --}}
@include('admin.partials.mini-rail')

{{-- ▌ MAIN SIDEBAR --}}
@include('admin.partials.sidebar')

{{-- ▌ TOPBAR --}}
<header class="topbar" id="topbar">
  <button class="tb-menu-btn" onclick="toggleSidebar()" title="تغییر سایدبار">
    <i class="fa-solid fa-angles-right" id="menuToggleIcon"></i>
  </button>

  <div class="tb-breadcrumb">
    <span>خانه</span>
    <i class="fa-solid fa-chevron-left"></i>
    <span class="crumb-active" id="breadcrumb">@yield('page-title', 'داشبورد')</span>
  </div>

  <div class="tb-search">
    <input type="text" placeholder="جستجو در پنل...">
    <i class="fa-solid fa-magnifying-glass tb-si"></i>
  </div>

  <div class="tb-actions">
    <button class="tb-btn" title="CRM" onclick="crmBtnClick()">
      <i class="fa-solid fa-users-between-lines"></i>
    </button>
    <button class="tb-btn" title="اعلان‌ها">
      <i class="fa-solid fa-bell"></i>
      <div class="tb-notif"></div>
    </button>
    <button class="tb-btn" title="پیام‌ها">
      <i class="fa-regular fa-envelope"></i>
    </button>
    <button class="tb-btn" onclick="toggleTheme()" id="themeBtn" title="تغییر تم">
      <i class="fa-solid fa-moon" id="themeIcon"></i>
    </button>
  </div>

  <div class="tb-sep"></div>

  <div class="tb-live">
    <div class="tb-live-dot"></div>
    <span class="tb-live-text">زنده</span>
    <button class="tb-refresh-btn" onclick="location.reload()">بروزرسانی</button>
  </div>
</header>

{{-- ▌ MAIN CONTENT --}}
<main class="admin-content" id="adminContent">
  @yield('content')
</main>

@yield('scripts')

<script>
/* ================================================
   SIDEBAR TOGGLE
================================================ */
let _sbCollapsed = false;
function toggleSidebar() {
  _sbCollapsed = !_sbCollapsed;
  const sb   = document.getElementById('sidebar');
  const tb   = document.getElementById('topbar');
  const mc   = document.getElementById('adminContent');
  const icon = document.getElementById('menuToggleIcon');
  sb.classList.toggle('sb-collapsed', _sbCollapsed);
  tb.classList.toggle('sb-collapsed-tb', _sbCollapsed);
  mc.classList.toggle('sb-collapsed-mc', _sbCollapsed);
  if (icon) {
    icon.className = _sbCollapsed
      ? 'fa-solid fa-angles-left'
      : 'fa-solid fa-angles-right';
  }
}

function crmBtnClick() {
  // Open CRM section — works if SPA admin.js is loaded, falls back to full page nav
  if (typeof showPage === 'function') {
    showPage('page-crm', 'CRM');
    // Update breadcrumb via setActive if available
    const crmLink = document.querySelector('[data-page="page-crm"], .nav-link[onclick*="crm"]');
    if (crmLink && typeof setActive === 'function') setActive(crmLink, 'CRM', null, 'page-crm');
    const breadcrumb = document.getElementById('breadcrumb');
    if (breadcrumb) breadcrumb.textContent = 'CRM';
  } else {
    window.location.href = '/admin/dashboard';
  }
}

/* ================================================
   SUBMENU TOGGLE — Level 2
================================================ */
function toggleSub(wrapId, chevId) {
  const wrap = document.getElementById(wrapId);
  const chev = document.getElementById(chevId);
  if (!wrap) return;
  const isOpen = wrap.classList.contains('open');
  wrap.classList.toggle('open', !isOpen);
  if (chev) chev.classList.toggle('open', !isOpen);
  if (!isOpen) _updateSubLine(wrap);
}

/* ================================================
   SUBMENU TOGGLE — Level 3
================================================ */
function toggleSubSub(wrapId, chevId) {
  const wrap = document.getElementById(wrapId);
  const chev = document.getElementById(chevId);
  if (!wrap) return;
  const isOpen = wrap.classList.contains('open');
  wrap.classList.toggle('open', !isOpen);
  if (chev) chev.classList.toggle('open', !isOpen);
}

/* ================================================
   FUTURE SECTION TOGGLE
================================================ */
function toggleFuture() {
  const wrap = document.getElementById('future-wrap');
  const chev = document.getElementById('future-chev');
  if (!wrap) return;
  const isOpen = wrap.classList.contains('open');
  wrap.classList.toggle('open', !isOpen);
  if (chev) chev.classList.toggle('open', !isOpen);
}

function toggleFutureSub(wrapId, el) {
  const wrap = document.getElementById(wrapId);
  if (!wrap) return;
  const chev = el ? el.querySelector('.nav-chev') : null;
  const isOpen = wrap.classList.contains('open');
  wrap.classList.toggle('open', !isOpen);
  if (chev) chev.classList.toggle('open', !isOpen);
}

/* ================================================
   SUB LINE PROGRESS (fills top → active)
================================================ */
function _updateSubLine(submenuEl) {
  const track = submenuEl ? submenuEl.querySelector('.sub-track') : null;
  if (!track) return;
  const active = track.querySelector('.sub-item.active');
  if (!active) { track.style.setProperty('--line-pct','0%'); return; }
  const tRect = track.getBoundingClientRect();
  const aRect = active.getBoundingClientRect();
  const pct = Math.max(0, Math.min(100,
    Math.round(((aRect.top + aRect.height/2) - tRect.top) / tRect.height * 100)
  ));
  track.style.setProperty('--line-pct', pct + '%');
}

/* ================================================
   MINI RAIL ACTIVE
================================================ */
function miniBtnGo(el) {
  document.querySelectorAll('.mini-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

/* ================================================
   THEME TOGGLE
================================================ */
function toggleTheme() {
  const html = document.documentElement;
  const isDark = html.getAttribute('data-theme') === 'dark';
  const next = isDark ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  try { localStorage.setItem('vtn-admin-theme', next); } catch(e) {}
  _updateThemeIcon();
  if (typeof updateCharts === 'function') updateCharts();
  // compat with old dashboard toggleMode()
  if (typeof _syncOldTheme === 'function') _syncOldTheme(next);
}

// compat alias for old dashboard scripts that call toggleMode()
function toggleMode() { toggleTheme(); }
function _updateThemeIcon() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const icon = document.getElementById('themeIcon');
  if (icon) icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
}

/* ================================================
   INIT
================================================ */
/* ================================================
   BREADCRUMB AUTO-UPDATE FROM URL
================================================ */
(function() {
  var pathMap = {
    '/admin/dashboard':            'مرکز فرماندهی',
    '/admin/orders':               'مدیریت سفارشات',
    '/admin/orders/analytics':     'آنالیتیکس سفارشات',
    '/admin/analytics':            'آنالیتیکس محصولات',
    '/admin/products':             'لیست محصولات',
    '/admin/products/create':      'ثبت محصول جدید',
    '/admin/products/dashboard':   'داشبورد محصولات',
    '/admin/products/categories':  'دسته‌بندی‌ها',
    '/admin/products/pricing':     'قیمت‌گذاری',
    '/admin/crm':                  'CRM',
    '/admin/jobs':                 'لاگ جاب‌ها',
    '/admin/payments':             'پرداخت‌ها',
    '/admin/prompts':              'مدیریت مدل‌ها',
    '/admin/users':                'کاربران',
    '/admin/bloggers':             'بلاگرها',
  };
  var path = window.location.pathname.replace(/\/$/, '');
  var bc   = document.getElementById('breadcrumb');
  if (bc && pathMap[path]) bc.textContent = pathMap[path];
})();

document.addEventListener('DOMContentLoaded', function () {
  _updateThemeIcon();

  // Auto-open parent submenu of active sub-item
  document.querySelectorAll('.sub-item.active, .sub-sub-item.active').forEach(function (item) {
    const parentSub = item.closest('.submenu');
    if (parentSub && !parentSub.classList.contains('open')) {
      parentSub.classList.add('open');
      const chevId = parentSub.dataset.chevId;
      if (chevId) {
        const chev = document.getElementById(chevId);
        if (chev) chev.classList.add('open');
      }
      _updateSubLine(parentSub);
    }
    const ssWrap = item.closest('.sub-sub-wrap');
    if (ssWrap && !ssWrap.classList.contains('open')) {
      ssWrap.classList.add('open');
    }
  });

  // Mark above-active items
  document.querySelectorAll('.sub-track').forEach(function (track) {
    const items = [...track.querySelectorAll(':scope > .sub-item')];
    let hitActive = false;
    items.forEach(function (item) {
      if (item.classList.contains('active')) { hitActive = true; return; }
      if (!hitActive) item.classList.add('above-active');
    });
    _updateSubLine(track.closest('.submenu'));
  });
});
</script>

</body>
</html>
