#!/usr/bin/env python3
"""حذف رنگ بنفش قدیمی از پنل مدیریت (فقط فایل‌های ادمین). اجرا از ریشه‌ی پروژه."""
import re, os, sys, colorsys
ROOT = sys.argv[1] if len(sys.argv) > 1 else '.'
SCOPES = ['resources/views/admin', 'resources/views/layouts/admin.blade.php', 'public/admin/css', 'packages']
TOKENS = 'public/admin/css/design-tokens.css'
BLOCK = """
/* ══════════════════════════════════════════════════════════════
   رنگ اکشن پنل (جایگزین بنفش قدیمی #a07af5 که از app.css می‌آمد)
   --accent: شب لیمویی برند، روز سبز اصلی · --on-accent / --on-primary: متن روی آن‌ها
   تعریف روی body تا بر :root و html.light فایل app.css غلبه کند.
════════════════════════════════════════════════════════════════ */
body { --accent: #C2FD75; --accent-hover: #b2ef62; --accent-soft: rgba(194,253,117,.14); --on-accent: #0b1f12; --on-primary: #C2FD75; }
body.light { --accent: #16594f; --accent-hover: #0f463e; --accent-soft: rgba(22,89,79,.10); --on-accent: #ffffff; --on-primary: #ffffff; }
"""
def purple(r, g, b):
    h, l, s = colorsys.rgb_to_hls(r/255, g/255, b/255)
    return 0.69 <= h <= 0.86 and s > 0.3 and 0.25 < l < 0.92
changed = {}
def fix(s, is_css):
    o = s
    # تعریف‌های inline بنفش در :root صفحه‌های قدیمی حذف شود (از توکن پنل ارث ببرد)
    s = re.sub(r'--accent\s*:\s*#a07af5\s*;?', '', s, flags=re.I)
    s = re.sub(r'--accent-(hover|soft)\s*:\s*#(8f68e0|c9b8f5|6d4fc4)\s*;?', '', s, flags=re.I)
    # rgba بنفش ← ترکیب با رنگ اکشن
    def rgba(m):
        r, g, b = int(m.group(1)), int(m.group(2)), int(m.group(3))
        if not purple(r, g, b): return m.group(0)
        a = float(m.group(4)) if m.group(4) else 1.0
        return 'color-mix(in srgb, var(--accent) %d%%, transparent)' % round(a * 100)
    s = re.sub(r'rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)', rgba, s)
    # هگزهای بنفش
    def hx(m):
        v = m.group(1); r, g, b = int(v[0:2], 16), int(v[2:4], 16), int(v[4:6], 16)
        if not purple(r, g, b): return m.group(0)
        return 'var(--accent-hover)' if v.lower() in ('8f68e0', '6a4dcc', '7a4fe0', '7c5de0', '6d4fc4') else 'var(--accent)'
    s = re.sub(r'#([0-9a-fA-F]{6})\b', hx, s)
    # دکمه‌های رنگ اکشن با متن سفید ثابت ← متن متناسب با تم
    s = re.sub(r'(background(?:-color)?\s*:\s*var\(--accent\)\s*;?[^;"\'{}]{0,0}\s*)color\s*:\s*#fff(?:fff)?\b', r'\1color:var(--on-accent)', s, flags=re.I)
    s = re.sub(r'(background(?:-color)?\s*:\s*var\(--accent\)\s*;)([^"\'{}]{0,120}?)color\s*:\s*#fff(?:fff)?\b', r'\1\2color:var(--on-accent)', s, flags=re.I)
    # پس‌زمینه‌ی سبز اصلی با متن رنگ اکشن ← متن روی سبز (روز سفید، شب لیمویی)
    s = re.sub(r'(background(?:-color)?\s*:\s*var\(--primary\)\s*;)([^"\'{}]{0,120}?)color\s*:\s*var\(--accent\)', r'\1\2color:var(--on-primary)', s)
    return s
for scope in SCOPES:
    p0 = os.path.join(ROOT, scope)
    paths = [p0] if os.path.isfile(p0) else [os.path.join(dp, f) for dp, _, fs in os.walk(p0) for f in fs]
    for p in paths:
        if not p.endswith(('.css', '.blade.php')) or 'node_modules' in p: continue
        s = open(p, encoding='utf-8').read()
        n = fix(s, p.endswith('.css'))
        if n != s:
            open(p, 'w', encoding='utf-8').write(n); changed[p] = True
tp = os.path.join(ROOT, TOKENS); t = open(tp, encoding='utf-8').read()
if 'رنگ اکشن پنل' not in t:
    t = t.rstrip() + '\n' + BLOCK; open(tp, 'w', encoding='utf-8').write(t); changed[tp] = True
print(len(changed), 'files changed')
for p in sorted(changed): print(' ', os.path.relpath(p, ROOT))

# ── رنگ نمودارهای canvas (Chart.js متغیر CSS نمی‌فهمد) ← سبز لوگوی برند
def chartfix(s):
    s = re.sub(r"((?:border|background|pointBackground|pointBorder|hoverBackground)Color\s*:\s*)'var\(--accent(?:-hover)?\)'", r"\1'#0BBF53'", s)
    s = re.sub(r"((?:border|background|pointBackground|pointBorder|hoverBackground)Color\s*:\s*)'color-mix\(in srgb, var\(--accent\) (\d+)%, transparent\)'",
               lambda m: "%s'rgba(11,191,83,%s)'" % (m.group(1), ('%.2f' % (int(m.group(2)) / 100)).rstrip('0').rstrip('.')), s)
    return s
for scope in SCOPES:
    p0 = os.path.join(ROOT, scope)
    paths = [p0] if os.path.isfile(p0) else [os.path.join(dp, f) for dp, _, fs in os.walk(p0) for f in fs]
    for p in paths:
        if not p.endswith('.blade.php'): continue
        s = open(p, encoding='utf-8').read(); n = chartfix(s)
        if n != s: open(p, 'w', encoding='utf-8').write(n); print('chart:', os.path.relpath(p, ROOT))
