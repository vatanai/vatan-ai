<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#101713">
    <title>{{ $report['title'] }} — وطن</title>
    <style>
        @font-face { font-family: YekanBakh; src: url('{{ asset('fonts/YekanBakh-Regular.ttf') }}') format('truetype'); font-weight: 400; }
        @font-face { font-family: YekanBakh; src: url('{{ asset('fonts/YekanBakh-Bold.ttf') }}') format('truetype'); font-weight: 700 900; }
        :root { --page: #101713; --card: #18221f; --card-2: #1e2b27; --line: rgba(194,253,117,.12); --text: #f1f5f1; --muted: #9eaca5; --green: #c2fd75; --green-2: #7fe1a8; --danger: #ff7d7d; --warning: #f6bb69; }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; background: var(--page); }
        body { padding: max(18px, env(safe-area-inset-top)) 16px max(24px, env(safe-area-inset-bottom)); color: var(--text); font-family: YekanBakh, sans-serif; }
        button, a { font: inherit; }
        .shell { width: min(720px, 100%); margin: 0 auto; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-mark { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 14px; background: var(--green); color: #14372d; font-size: 21px; font-weight: 900; box-shadow: 0 12px 30px rgba(194,253,117,.16); }
        .brand-copy { display: grid; gap: 1px; }
        .brand-copy strong { font-size: 15px; }
        .brand-copy span { color: var(--muted); font-size: 10px; }
        .status { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border: 1px solid var(--line); border-radius: 999px; color: var(--green-2); background: rgba(127,225,168,.08); font-size: 10px; font-weight: 700; }
        .status.inactive { color: var(--danger); background: rgba(255,125,125,.08); }
        .status i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 4px color-mix(in srgb, currentColor 12%, transparent); }
        .hero { position: relative; overflow: hidden; display: grid; grid-template-columns: minmax(0, 1fr) 88px; gap: 14px; align-items: center; padding: 20px; border: 1px solid var(--line); border-radius: 24px; background: linear-gradient(135deg, #1d2d27, #15201d 70%); box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .hero:after { content: ''; position: absolute; width: 170px; height: 170px; right: -70px; bottom: -100px; border-radius: 50%; background: rgba(194,253,117,.13); filter: blur(5px); }
        .eyebrow { margin: 0 0 6px; color: var(--green); font-size: 10px; font-weight: 700; }
        h1 { margin: 0; font-size: clamp(20px, 5vw, 29px); line-height: 1.25; }
        .hero p { margin: 7px 0 0; color: var(--muted); font-size: 11px; line-height: 1.8; }
        .cover { position: relative; z-index: 1; width: 88px; height: 88px; overflow: hidden; border: 1px solid rgba(255,255,255,.16); border-radius: 23px; background: var(--card-2); box-shadow: 0 15px 28px rgba(0,0,0,.24); }
        .cover img { width: 100%; height: 100%; display: block; object-fit: cover; }
        .cover-fallback { width: 100%; height: 100%; display: grid; place-items: center; color: var(--green); font-size: 31px; font-weight: 900; }
        .section { margin-top: 14px; padding: 16px; border: 1px solid var(--line); border-radius: 20px; background: var(--card); }
        .section-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 13px; }
        .section-head h2 { margin: 0; font-size: 14px; }
        .section-head span { color: var(--muted); font-size: 9px; }
        .stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
        .stat { min-height: 83px; padding: 12px; border: 1px solid rgba(255,255,255,.055); border-radius: 15px; background: var(--card-2); }
        .stat.accent { border-color: rgba(194,253,117,.25); background: linear-gradient(145deg, rgba(194,253,117,.13), rgba(194,253,117,.035)); }
        .stat-label { display: flex; align-items: center; gap: 6px; color: var(--muted); font-size: 10px; }
        .stat-label i { width: 7px; height: 7px; border-radius: 50%; background: var(--green); }
        .stat strong { display: block; margin-top: 9px; color: var(--text); font-size: 24px; line-height: 1; }
        .stat small { display: block; margin-top: 6px; color: var(--muted); font-size: 9px; }
        .funnel { display: grid; gap: 10px; }
        .funnel-row { display: grid; grid-template-columns: 92px minmax(0, 1fr) 44px; gap: 9px; align-items: center; color: var(--muted); font-size: 10px; }
        .funnel-row strong { color: var(--text); text-align: left; font-size: 11px; }
        .track { height: 8px; overflow: hidden; border-radius: 99px; background: #26352f; }
        .track span { display: block; height: 100%; min-width: 3px; border-radius: inherit; background: linear-gradient(90deg, var(--green), var(--green-2)); }
        .chart { display: flex; align-items: end; gap: 6px; height: 142px; padding: 12px 5px 0; border-bottom: 1px solid rgba(255,255,255,.08); direction: ltr; }
        .bar { min-width: 0; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: end; gap: 5px; height: 100%; }
        .bar-value { width: 100%; height: var(--height); min-height: 8px; border-radius: 7px 7px 3px 3px; background: linear-gradient(180deg, var(--green), #59a878); opacity: .9; }
        .bar-label { color: var(--muted); font-size: 8px; direction: ltr; }
        .empty-chart { width: 100%; align-self: center; color: var(--muted); text-align: center; font-size: 10px; }
        .activity { display: grid; gap: 0; }
        .activity-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 0; border-bottom: 1px solid rgba(255,255,255,.07); font-size: 10px; }
        .activity-row:last-child { border-bottom: 0; padding-bottom: 0; }
        .activity-row:first-child { padding-top: 0; }
        .activity-row span { display: inline-flex; align-items: center; gap: 7px; color: var(--muted); }
        .activity-row i { width: 8px; height: 8px; border-radius: 50%; background: var(--green-2); }
        .activity-row i.warning { background: var(--warning); }
        .activity-row i.danger { background: var(--danger); }
        .activity-row time { color: var(--muted); font-size: 9px; direction: ltr; }
        .share { display: flex; align-items: center; gap: 8px; padding: 9px; border: 1px solid rgba(255,255,255,.08); border-radius: 12px; background: var(--card-2); }
        .share input { min-width: 0; flex: 1; border: 0; outline: 0; background: transparent; color: var(--muted); direction: ltr; text-align: left; font-size: 10px; }
        .share button, .primary-action { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 13px; border: 0; border-radius: 10px; background: var(--green); color: #132b22; cursor: pointer; font-size: 10px; font-weight: 900; white-space: nowrap; }
        .share button:active, .primary-action:active { transform: scale(.98); }
        .footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 14px; color: var(--muted); font-size: 9px; }
        .primary-action { min-height: 40px; text-decoration: none; }
        @media (min-width: 560px) { .stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } .stat { min-height: 96px; } }
        @media (max-width: 420px) { .hero { grid-template-columns: minmax(0, 1fr) 70px; padding: 16px; } .cover { width: 70px; height: 70px; border-radius: 19px; } .funnel-row { grid-template-columns: 76px minmax(0, 1fr) 38px; } }
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">و</div>
            <div class="brand-copy"><strong>داشبورد رفرال</strong><span>گزارش زنده‌ی عملکرد لینک</span></div>
        </div>
        <span class="status {{ $report['status_class'] === 'active' ? '' : 'inactive' }}"><i></i>{{ $report['status'] }}</span>
    </header>

    <section class="hero">
        <div>
            <p class="eyebrow">لینک اختصاصی تو</p>
            <h1>{{ $report['title'] }}</h1>
            <p>{{ $report['subtitle'] }}؛ هر تغییر مهم اینجا قابل مشاهده است.</p>
        </div>
        <div class="cover">
            @if($report['image'])
                <img src="{{ $report['image'] }}" alt="">
            @else
                <div class="cover-fallback">↗</div>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="section-head"><h2>خلاصه عملکرد</h2><span>آخرین ۱۴ روز و مجموع لینک</span></div>
        <div class="stats">
            <article class="stat"><span class="stat-label"><i></i>کلیک کل</span><strong>{{ number_format($report['stats']['clicks']) }}</strong><small>{{ number_format($report['stats']['unique']) }} بازدیدکننده یکتا</small></article>
            <article class="stat"><span class="stat-label"><i></i>ثبت‌نام</span><strong>{{ number_format($report['stats']['registrations']) }}</strong><small>{{ number_format($report['stats']['qualified']) }} دعوت معتبر</small></article>
            <article class="stat"><span class="stat-label"><i></i>خرید موفق</span><strong>{{ number_format($report['stats']['purchases']) }}</strong><small>{{ number_format($report['stats']['images']) }} ساخت موفق</small></article>
            <article class="stat accent"><span class="stat-label"><i></i>پاداش دریافتی</span><strong>{{ number_format($report['stats']['paid']) }}</strong><small>{{ number_format($report['stats']['pending']) }} در انتظار</small></article>
        </div>
    </section>

    <section class="section">
        <div class="section-head"><h2>قیف تبدیل</h2><span>از کلیک تا نتیجه</span></div>
        @php $maxFunnel = max(1, (int) $report['stats']['clicks']); @endphp
        <div class="funnel">
            <div class="funnel-row"><span>کلیک</span><div class="track"><span style="width:100%"></span></div><strong>{{ number_format($report['stats']['clicks']) }}</strong></div>
            <div class="funnel-row"><span>ثبت‌نام</span><div class="track"><span style="width:{{ min(100, ($report['stats']['registrations'] / $maxFunnel) * 100) }}%"></span></div><strong>{{ number_format($report['stats']['registrations']) }}</strong></div>
            <div class="funnel-row"><span>خرید موفق</span><div class="track"><span style="width:{{ min(100, ($report['stats']['purchases'] / $maxFunnel) * 100) }}%"></span></div><strong>{{ number_format($report['stats']['purchases']) }}</strong></div>
        </div>
    </section>

    <section class="section">
        <div class="section-head"><h2>روند کلیک‌ها</h2><span>۱۴ روز اخیر</span></div>
        <div class="chart">
            @forelse($report['series'] as $day)
                <div class="bar"><div class="bar-value" style="--height:{{ $day['height'] }}%" title="{{ $day['clicks'] }} کلیک"></div><span class="bar-label">{{ $day['label'] }}</span></div>
            @empty
                <div class="empty-chart">هنوز داده‌ای برای نمایش وجود ندارد.</div>
            @endforelse
        </div>
    </section>

    @if($report['recent'])
        <section class="section">
            <div class="section-head"><h2>آخرین فعالیت‌ها</h2><span>وضعیت دعوت‌ها</span></div>
            <div class="activity">
                @foreach($report['recent'] as $item)
                    <div class="activity-row"><span><i class="{{ $item['class'] }}"></i>{{ $item['label'] }}</span><time>{{ $item['date'] }}</time></div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="section-head"><h2>لینک قابل اشتراک‌گذاری</h2><span>برای انتشار کپی کن</span></div>
        <div class="share" dir="ltr"><input id="shareLink" value="{{ $report['share_url'] }}" readonly><button type="button" id="copyShareLink">کپی لینک</button></div>
    </section>

    <footer class="footer">
        <span>آخرین به‌روزرسانی: {{ $report['updated_at']?->diffForHumans() ?: 'هنوز ثبت نشده' }}</span>
        <a class="primary-action" href="{{ route('app.profile', ['tab' => 'referral']) }}">بازگشت به لینک‌ها</a>
    </footer>
</main>
<script>
(() => {
    const button = document.getElementById('copyShareLink');
    const input = document.getElementById('shareLink');
    if (!button || !input) return;
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(input.value);
            button.textContent = 'کپی شد ✓';
            setTimeout(() => { button.textContent = 'کپی لینک'; }, 1800);
        } catch (error) {
            input.select();
            button.textContent = 'انتخاب شد';
        }
    });
    const telegram = window.Telegram?.WebApp;
    if (telegram) {
        telegram.ready();
        telegram.expand();
    }
})();
</script>
</body>
</html>
