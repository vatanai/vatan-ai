# وطن داشبورد — کانتکس کامل UI برای Claude

تو باید یه داشبورد مدیریت RTL فارسی بسازی که **دقیقاً** مطابق این مشخصات باشه. هیچ چیز رو تغییر نده — فقط پیاده‌سازی کن.

---

## ساختار Layout

فایل یک‌تکه HTML است. بدون فایل CSS یا JS جداگانه.

```
[مینی سایدبار 64px — right:0]  [سایدبار اصلی 265px — right:64px]  [تاپ‌بار — top:0, right:329px, left:0, height:68px]
                                                                    [محتوا — margin-right:329px, padding-top:68px]
```

- **جهت:** `dir="rtl"`, `lang="fa"`
- **فونت:** Vazirmatn از Google Fonts (وزن‌های 300–900)
- **آیکون:** Font Awesome 6.5.0
- **نمودار:** Chart.js 4.4.1

---

## رنگ‌ها — CSS Variables

```css
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
}

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
}
```

---

## قوانین کارت‌ها

```css
/* هر کارت اینطوری باشه — بدون نوار رنگی پایین، بدون decoration اضافه */
.card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;                         /* خمیدگی گوشه‌ها */
  box-shadow: var(--shadow-card);
  padding: 20px;
  transition: background .3s, border-color .3s;
}
/* کارت‌های کوچک‌تر: border-radius:14px */
/* کارت‌های آمار: border-radius:16px، هیچ رنگ اضافه‌ای نداره */
```

---

## سایدبار اصلی

```css
.sidebar {
  position: fixed;
  right: 64px; top: 0; bottom: 0;   /* 64px = عرض مینی سایدبار */
  width: 265px;
  background: var(--sb-bg);
  border-left: 1px solid var(--border);
  box-shadow: var(--shadow-sb);
  overflow-y: auto;
  scrollbar-width: none;
  transition: width .28s;
}

/* حالت Collapsed = icon-only (نه hidden) */
.sidebar.collapsed { width: 68px; overflow: hidden; }
.sidebar.collapsed .sb-logo-name,
.sidebar.collapsed .sb-logo-sub,
.sidebar.collapsed .sb-section,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-badge,
.sidebar.collapsed .nav-chev,
.sidebar.collapsed .submenu { display: none !important; }
.sidebar.collapsed .nav-link { justify-content: center; padding: 9px 6px; }
```

### لوگو
```css
.sb-logo-mark {
  width: 40px; height: 40px; border-radius: 12px;
  background: linear-gradient(135deg, #0bbf53 0%, #16594f 100%);
  box-shadow: 0 4px 14px rgba(11,191,83,.38);
}
/* نام برند: وطن + نقطه با رنگ --logo-green */
```

### آیتم منو
```css
.nav-link {
  display: flex; align-items: center; gap: 11px;
  padding: 9px 12px; border-radius: 12px;
}
.nav-icon {
  width: 37px; height: 37px; border-radius: 10px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  font-size: 15px; color: var(--text-soft);
}
/* فعال — بدون هاله، بدون box-shadow */
.nav-link.active .nav-icon {
  background: #16594f; color: #C2FD75; border-color: #16594f;
}
.nav-link.active .nav-label { color: var(--nav-active-t); font-weight: 700; }
```

---

## زیرمنو سطح ۲ — خط کروی پیشرفته

**قانون کلیدی:** وقتی یه آیتم active می‌شه، خط عمودی از بالا تا اون آیتم **رنگی** (سبز) می‌شه.
Bracket arm (╯) آیتم‌های بالاتر هم سبز می‌شه تا خط قطع نشه.
Background باکس از جلوی دایره شروع می‌شه (نه از پشتش).

```css
.sub-track {
  position: relative;
  padding: 4px 0 10px;
  margin-right: 30px;
}

/* خط عمودی — رنگ پیشرفته با --line-pct (از JS تنظیم می‌شه) */
.sub-track::before {
  content: ''; position: absolute;
  right: 0; top: 4px; bottom: 22px; width: 1.5px;
  background: linear-gradient(to bottom,
    var(--sub-dot-active) 0%,
    var(--sub-dot-active) var(--line-pct, 0%),
    var(--sub-line) var(--line-pct, 0%),
    transparent 100%);
  border-radius: 1px; transition: background .3s;
}

/* آیتم — background از جلوی دایره شروع می‌شه */
.sub-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px 8px 10px;
  margin: 2px 20px 2px 14px;    /* margin-right:20px = فضای bracket arm */
  border-radius: 9px;
  position: relative; overflow: visible;
}

/* bracket arm ╯ — خارج از ناحیه background */
.sub-item::before {
  content: ''; position: absolute;
  right: -20px; top: 0; bottom: 50%; width: 20px;
  border-right: 1.5px solid var(--sub-line);
  border-bottom: 1.5px solid var(--sub-line);
  border-bottom-right-radius: 10px;
  transition: border-color .2s;
}
.sub-item.active::before   { border-color: var(--sub-dot-active); }
.sub-item.above-active::before { border-color: var(--sub-dot-active); } /* آیتم‌های بالاتر */

.sub-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--sub-dot); flex-shrink: 0;
}
.sub-item.active .sub-dot {
  background: var(--sub-dot-active);
  width: 8px; height: 8px;
  box-shadow: 0 0 8px rgba(22,89,79,.45);
}
[data-theme="dark"] .sub-item.active .sub-dot {
  box-shadow: 0 0 8px rgba(194,253,117,.5);
}
```

---

## زیرمنو سطح ۳

آیتم‌هایی که زیرمنوی سطح ۳ دارن یه chevron کوچک نشون می‌دن.
همان قانون رنگ پیشرفته برای این سطح هم اعمال می‌شه.

```css
.sub-sub-wrap { max-height: 0; overflow: hidden; transition: max-height .3s cubic-bezier(.4,0,.2,1); }
.sub-sub-wrap.open { max-height: 400px; }

.sub-sub-track { position: relative; padding: 2px 0 6px; margin-right: 18px; }
.sub-sub-track::before {
  /* همان gradient پیشرفته با --line-pct */
  right: 0; top: 4px; bottom: 14px; width: 1px;
}

.sub-sub-item {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 8px;
  margin: 1px 14px 1px 16px;    /* از چپ باریک‌تر */
  border-radius: 7px; overflow: visible;
}
.sub-sub-item::before {
  right: -14px; top: 0; bottom: 50%; width: 14px;
  border-right: 1px solid; border-bottom: 1px solid;
  border-bottom-right-radius: 8px;
}
.sub-sub-item.above-active::before { border-color: var(--sub-dot-active); }
.sub-sub-dot { width: 5px; height: 5px; border-radius: 50%; }
```

---

## مینی سایدبار

ستون باریک راست‌ترین — **جدا و متفاوت** از سایدبار اصلی.

```css
.mini-rail {
  position: fixed; right: 0; top: 0; bottom: 0; width: 64px;
  background: var(--sb-bg);
  border-left: 1px solid var(--border);
  display: flex; flex-direction: column; align-items: center;
  z-index: 310; padding: 14px 0 20px; gap: 4px;
}

.mini-btn {
  width: 38px; height: 38px; border-radius: 10px;
  font-size: 13px;                              /* ۲۰٪ کوچک‌تر از nav-icon */
  color: var(--text-soft);
  border: 1px solid transparent; background: transparent;
}
.mini-btn.active {
  background: var(--primary-l); color: var(--primary);
  border-color: var(--primary-m);
}
[data-theme="dark"] .mini-btn.active {
  color: var(--accent);
  background: rgba(22,89,79,.25); border-color: rgba(22,89,79,.35);
}

/* Tooltip — سمت چپ دکمه */
.mini-btn-tooltip {
  position: absolute; left: calc(100% + 10px);
  background: var(--text-h); color: var(--sb-bg);
  font-size: 11px; font-weight: 600;
  padding: 4px 10px; border-radius: 7px;
  white-space: nowrap; opacity: 0; pointer-events: none;
  transform: translateX(-4px); transition: all .18s;
}
.mini-btn:hover .mini-btn-tooltip { opacity: 1; transform: translateX(0); }
```

---

## تاپ‌بار

```css
.topbar {
  position: fixed; top: 0; right: 329px; left: 0; height: 68px;
  background: var(--topbar-bg);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; padding: 0 22px; gap: 14px;
}
```

**ترتیب اجزا از راست به چپ (RTL):**
1. دکمه hamburger (toggle collapse سایدبار)
2. breadcrumb — آپدیت پویا با JS
3. باکس جستجو (`flex:1; max-width:280px; margin-right:auto`)
4. دکمه اعلان + ایمیل + تم‌سوئیچ
5. خط جداکننده عمودی
6. **Live chip + دکمه به‌روزرسانی** (سمت چپ تاپ‌بار)

وقتی collapse می‌شه: `right` از 329px به 132px (68+64) می‌ره.

---

## تم‌سوئیچ

```javascript
// Toggle با data-theme روی <html>
html.dataset.theme = dark ? 'light' : 'dark';
// آیکون: fa-moon (dark) / fa-sun (light)
```

---

## JavaScript — توابع کلیدی

```javascript
// ── سایدبار ──
function toggleSidebar() {
  sbOpen = !sbOpen;
  if (sbOpen) {
    sidebar.classList.remove('collapsed'); sidebar.style.width = '265px';
    topbar.style.right = '329px'; main.style.marginRight = '329px';
  } else {
    sidebar.classList.add('collapsed'); sidebar.style.width = '68px';
    topbar.style.right = '132px'; main.style.marginRight = '132px';
  }
}

// ── منو سطح ۱ + breadcrumb ──
function navGo(el) {
  /* remove active از همه، add active به el */
  document.querySelector('.active-crumb').textContent =
    el.querySelector('.nav-label').textContent;
}

// ── زیرمنو سطح ۲ — خط پیشرفته ──
function subGo(el) {
  const track = el.closest('.sub-track');
  const items = Array.from(track.querySelectorAll(':scope > .sub-item'));
  items.forEach(s => s.classList.remove('active','above-active','below-active'));
  el.classList.add('active');
  const idx = items.indexOf(el);
  items.forEach((s, i) => { if (i < idx) s.classList.add('above-active'); });
  // محاسبه درصد موقعیت برای رنگ خط
  const pct = Math.round(
    ((el.getBoundingClientRect().top - track.getBoundingClientRect().top
      + el.getBoundingClientRect().height * 0.5) / track.getBoundingClientRect().height) * 100
  );
  track.style.setProperty('--line-pct', Math.min(pct, 96) + '%');
  document.querySelector('.active-crumb').textContent =
    el.querySelector('.sub-label').textContent;
}

// ── زیرمنو سطح ۳ ──
function toggleSubSub(id, chevId, el) { /* باز/بسته کردن sub-sub-wrap */ }
function subSubGo(el) { /* مثل subGo ولی برای sub-sub-track و sub-sub-item */ }

// ── مینی سایدبار ──
function miniBtnGo(el) {
  document.querySelectorAll('.mini-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}
```

---

## ردیف کاربر بالای محتوا

به‌جای page title ثابت، یه ردیف کارت‌مانند داریم:

```html
<div class="content-urow">
  <div> <!-- آواتار + نام کاربر + نقش --> </div>
  <div id="contentDate"> <!-- تاریخ فارسی امروز با toLocaleDateString('fa-IR') --> </div>
</div>
```

```css
.content-urow {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 18px; padding: 12px 16px;
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 14px; box-shadow: var(--shadow-card);
}
.content-urow-av {
  width: 40px; height: 40px; border-radius: 50%;
  background: linear-gradient(135deg,#16594f,#0bbf53);
  border: 2px solid var(--accent);
  box-shadow: 0 0 10px rgba(11,191,83,.2);
}
```

---

## نکات مهم — حتماً رعایت کن

1. **Breadcrumb پویاست** — با هر کلیک روی منو آپدیت می‌شه، page title ثابت وجود نداره
2. **Scard-bar وجود نداره** — هیچ نوار رنگی پایین کارت‌ها نزن
3. **هاله آیکون وجود نداره** — `box-shadow` روی `.nav-icon.active` نزن
4. **مینی سایدبار جداست** — آیکون‌هاش کاملاً متفاوت از سایدبار اصلی، ۲۰٪ کوچک‌تر
5. **Collapse = icon-only** — سایدبار مخفی نمی‌شه، فقط به ۶۸px می‌رسه
6. **--line-pct** با هر کلیک زیرمنو از JS تنظیم می‌شه (0% = بدون رنگ)
7. **above-active** کلاسی است که به آیتم‌های بالاتر از active اضافه می‌شه تا bracket arm رنگی باشه
8. **Background sub-item** از جلوی دایره شروع می‌شه — `margin-right:20px` روی sub-item + `right:-20px` روی ::before

