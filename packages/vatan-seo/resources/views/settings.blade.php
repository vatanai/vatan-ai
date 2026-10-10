@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  $seoTitle = 'تنظیمات و اتصال‌ها';
  $seoIcon = 'fa-sliders';
  $seoHelp = 'settings.page';
  $seoSubtitle = 'پروفایل سایت، سقف بودجه‌ی هوش مصنوعی و اتصال به سرچ کنسول، OpenRouter، بات تلگرام و سایت.';
  $status = fn (bool $ok, string $on = 'متصل', string $off = 'متصل نیست') => '<span class="seo-tag '.($ok ? 'is-success' : 'is-warning').'"><i class="fa-solid fa-circle"></i>'.e($ok ? $on : $off).'</span>';
@endphp

@section('seo-page')
<div class="seo-stack">
  <div class="seo-grid seo-grid-main" style="align-items:start">
    {{-- پروفایل و بودجه --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-id-card"></i> پروفایل سایت و بودجه</div><span class="seo-tag is-primary">{{ $profile['label'] ?? '' }}</span></div>
      <form method="POST" action="{{ route('seo.settings.site') }}" class="seo-form-grid">@csrf
        <div class="seo-field"><label>نام برند</label><input name="name" class="seo-input" value="{{ old('name', $site->name) }}" required></div>
        <div class="seo-field"><label>آدرس سایت</label><input name="base_url" class="seo-input seo-ltr" style="display:block" value="{{ old('base_url', $site->base_url) }}" required></div>
        <div class="seo-field"><label>سقف ماهانه‌ی هوش مصنوعی (دلار) @include('seo::partials.help', ['k' => 'settings.budget'])</label><input name="monthly_budget_usd" type="number" step="1" min="0" class="seo-input" value="{{ old('monthly_budget_usd', (float) $site->monthly_budget_usd) }}"></div>
        <div class="seo-field"><label>پروفایل (اختیاری) @include('seo::partials.help', ['k' => 'settings.tier'])</label><select name="tier_override" class="seo-select"><option value="">خودکار بر اساس مبلغ</option>@foreach($tiers as $k => $t)<option value="{{ $k }}" @selected($site->setting('tier_override') === $k)>{{ $t['label'] }}</option>@endforeach</select></div>
        <div class="seo-field"><label>Property سرچ کنسول @include('seo::partials.help', ['k' => 'settings.gsc_property'])</label><input name="gsc_property" class="seo-input seo-ltr" style="display:block" value="{{ old('gsc_property', $site->gsc_property) }}" placeholder="sc-domain:aivatan.com"></div>
        <div class="seo-field"><label>Property گوگل آنالیتیکس ۴</label><input name="ga4_property" class="seo-input seo-ltr" style="display:block" value="{{ old('ga4_property', $site->ga4_property) }}" placeholder="properties/123456789"></div>
        <div class="seo-field full"><label>حوزه‌ی کسب‌وکار</label><input name="niche" class="seo-input" value="{{ old('niche', $site->niche) }}"></div>
        <div class="seo-field full"><label>معرفی برند برای ایجنت‌ها @include('seo::partials.help', ['k' => 'settings.brand'])</label><textarea name="brand_brief" class="seo-textarea" rows="3" placeholder="مخاطب، مزیت رقابتی، لحن، محصولات اصلی، چیزهایی که نباید گفته شود…">{{ old('brand_brief', $site->brand_brief) }}</textarea></div>
        <div class="seo-field full"><label>رقبا (هر خط یک دامنه)</label><textarea name="competitors" class="seo-textarea" rows="2">{{ implode("\n", (array) $site->setting('competitors', [])) }}</textarea></div>
        <div class="full"><button class="btn-pro btn-pro-primary"><i class="fa-solid fa-check"></i> ذخیره</button></div>
      </form>
    </section>

    {{-- مقایسه‌ی پروفایل‌ها --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-layer-group"></i> پروفایل‌های بودجه @include('seo::partials.help', ['k' => 'settings.tiers'])</div></div>
      <div class="seo-stack" style="gap:10px">
        @foreach($tiers as $k => $t)
          <div style="padding:12px;border-radius:12px;border:1px solid {{ ($profile['key'] ?? '') === $k ? 'var(--primary)' : 'var(--border)' }};background:{{ ($profile['key'] ?? '') === $k ? 'var(--primary-l)' : 'transparent' }}">
            <div class="seo-row-gap" style="justify-content:space-between"><b style="color:var(--text-h)">{{ $t['label'] }}</b>@if(($profile['key'] ?? '') === $k)<span class="seo-tag is-primary">فعال</span>@endif</div>
            <div class="seo-card-sub" style="line-height:1.8">{{ $t['description'] }}</div>
            <div class="seo-row-gap" style="margin-top:6px;gap:4px">
              <span class="seo-tag">{{ Fa::n($t['limits']['tracked_keywords']) }} کلمه‌ی هدف</span>
              <span class="seo-tag">{{ Fa::n($t['limits']['articles_per_month']) }} مقاله/ماه</span>
              <span class="seo-tag">{{ Fa::n($t['limits']['research_calls_per_month']) }} پژوهش زنده</span>
              <span class="seo-tag">خزش {{ Fa::n($t['limits']['crawl_pages']) }} صفحه</span>
            </div>
          </div>
        @endforeach
      </div>
    </section>
  </div>

  {{-- فعال‌سازی هوش مصنوعی --}}
  <section class="seo-card" id="ai">
    <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-bolt"></i> فعال‌سازی هوش مصنوعی @include('seo::partials.help', ['k' => 'settings.activation'])</div>
      @if($keyInfo)<span class="seo-tag is-success"><i class="fa-solid fa-circle"></i>کلید فعال{{ !empty($keyInfo['label']) ? ' · '.$keyInfo['label'] : '' }}</span>@endif</div>
    <div class="seo-grid seo-grid-4" style="gap:12px">
      @php
        $aiSteps = [
        ['کلید OpenRouter', $aiReady, $aiReady ? ($dedicated ? 'کلید اختصاصی سئو' : 'کلید عمومی پروژه') : 'OPENROUTER_API_KEY در .env'],
        ['اعتبار حساب', $keyInfo !== null, $keyInfo ? ('مصرف کل: $'.number_format((float) ($keyInfo['usage'] ?? 0), 2).(isset($keyInfo['limit']) && $keyInfo['limit'] !== null ? ' · سقف کلید: $'.number_format((float) $keyInfo['limit'], 2) : ' · بدون سقف روی کلید')) : 'با «تست اتصال» بررسی کنید'],
        ['فهرست مدل‌های زنده', $liveModels > 0, $liveModels ? Fa::n($liveModels).' مدل · انتخاب خودکار' : 'دریافت نشد؛ کاندیدها به‌ترتیب امتحان می‌شوند'],
        ['سقف بودجه‌ی موتور', (float) $site->monthly_budget_usd > 0, Fa::usd((float) $site->monthly_budget_usd).' در ماه · '.($profile['label'] ?? '')],
      ];
      @endphp
      @foreach($aiSteps as $i => [$label, $ok, $hint])
        <div class="seo-item" style="border:1px solid var(--border);border-radius:12px;padding:12px;align-items:flex-start">
          <span class="seo-tag {{ $ok ? 'is-success' : 'is-warning' }}">{{ Fa::n($i + 1) }}</span>
          <div class="seo-item-main"><div class="seo-item-title">{{ $label }}</div><div class="seo-item-sub">{{ $hint }}</div></div>
          <i class="fa-solid {{ $ok ? 'fa-circle-check tone-success' : 'fa-circle-exclamation tone-warning' }}"></i>
        </div>
      @endforeach
    </div>
    <details style="margin-top:12px"><summary class="seo-link" style="cursor:pointer">راهنمای کامل فعال‌سازی (۵ دقیقه)</summary>
      <ol style="margin:10px 0 0;padding-right:18px;font-size:12px;line-height:2.1">
        <li>در <span class="seo-kbd">openrouter.ai</span> وارد حسابی شوید که کلید فعلی پروژه از آن است و از Credits حداقل ۱۰ تا ۲۰ دلار اعتبار داشته باشید.</li>
        <li>(پیشنهادی) Keys ← Create Key ← نام «vatan-seo» و Credit limit = ۲۰ دلار ← کلید را در <span class="seo-kbd">SEO_OPENROUTER_API_KEY</span> در .env سرور بگذارید. این‌طوری هزینه‌ی سئو جدا و محدود می‌ماند.</li>
        <li>اگر سرور از پل کلادفلر استفاده می‌کند ({{ $viaGateway ? 'بله، فعال است' : 'فعلاً مستقیم' }})، نسخه‌ی جدید Worker را یک بار دیپلوی کنید تا فهرست مدل‌ها و کلید اختصاصی عبور کند.</li>
        <li>همین صفحه ← «تست اتصال» در کارت مدل‌ها. Grok و Claude و Gemini همه از همین یک کلید استفاده می‌کنند؛ کلید جدا برای xAI لازم نیست.</li>
        <li>سقف ماهانه را در «پروفایل سایت و بودجه» تنظیم کنید (وطن: ۲۰ دلار).</li>
      </ol>
    </details>
  </section>

  <div class="seo-group-title" id="google">اتصال‌ها</div>
  <div class="seo-grid seo-grid-2" style="align-items:start">
    {{-- گوگل --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-brands fa-google"></i> سرچ کنسول و آنالیتیکس @include('seo::partials.help', ['k' => 'settings.google'])</div>{!! $status($sa['configured'], 'سرویس‌اکانت ثبت شده', 'تنظیم نشده') !!}</div>
      @if($sa['email'])<div class="seo-field" style="margin-bottom:10px"><label>ایمیل سرویس‌اکانت (در سرچ کنسول اضافه کنید)</label><div class="seo-row-gap"><span class="seo-kbd" id="seo-sa-email">{{ $sa['email'] }}</span><button type="button" class="seo-icon-btn" data-copy="seo-sa-email" title="کپی"><i class="fa-regular fa-copy"></i></button></div></div>@endif
      <ol style="margin:0 0 12px;padding-right:18px;font-size:12px;line-height:2.1">
        <li>در <span class="seo-ltr">console.cloud.google.com</span> یک پروژه بسازید و «Google Search Console API»، «Google Analytics Data API» و «PageSpeed Insights API» را فعال کنید.</li>
        <li>از IAM ← Service Accounts یک سرویس‌اکانت بسازید و کلید JSON آن را دانلود کنید.</li>
        <li>فایل JSON را همین‌جا آپلود کنید.</li>
        <li>در سرچ کنسول ← Settings ← Users and permissions ایمیل سرویس‌اکانت را با دسترسی <b>Full</b> اضافه کنید (در GA4 با نقش Viewer).</li>
      </ol>
      <form method="POST" action="{{ route('seo.settings.google') }}" enctype="multipart/form-data" class="seo-row-gap">@csrf
        <input type="file" name="service_account" accept=".json,application/json" class="seo-input" style="flex:1;padding-top:7px" required>
        <button class="btn-pro btn-pro-primary"><i class="fa-solid fa-upload"></i> آپلود</button>
      </form>
      <div class="seo-row-gap" style="margin-top:10px">
        <form method="POST" action="{{ route('seo.settings.test', 'gsc') }}">@csrf<button class="btn-pro btn-pro-ghost seo-btn-sm" data-loading="تست…"><i class="fa-solid fa-vial"></i> تست سرچ کنسول</button></form>
        <form method="POST" action="{{ route('seo.settings.test', 'pagespeed') }}">@csrf<button class="btn-pro btn-pro-ghost seo-btn-sm" data-loading="تست…"><i class="fa-solid fa-gauge"></i> تست PageSpeed</button></form>
      </div>
    </section>

    {{-- OpenRouter --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-microchip"></i> مدل‌های هوش مصنوعی (OpenRouter) @include('seo::partials.help', ['k' => 'settings.models'])</div>{!! $status($aiReady, 'کلید ثبت شده', 'کلید ندارد') !!}</div>
      <div class="seo-card-sub" style="margin-bottom:10px">از همان کلید و پل کلادفلر OpenRouter پروژه استفاده می‌شود. مدل‌های زنده: {{ $liveModels ? Fa::n($liveModels) : 'دریافت نشد (کاندیدها به‌ترتیب امتحان می‌شوند)' }}</div>
      <div class="seo-rows">
        @foreach($roles as $role => $r)
          <div class="seo-item"><div class="seo-item-main"><div class="seo-item-title">{{ $r['label'] }}</div><div class="seo-item-sub seo-ltr">{{ $r['model'] ?? '— در این پروفایل خاموش —' }}</div></div>@if(count($r['candidates']) > 1)<span class="seo-tag" title="{{ implode(' ← ', $r['candidates']) }}">{{ Fa::n(count($r['candidates']) - 1) }} جایگزین</span>@endif</div>
        @endforeach
      </div>
      <form method="POST" action="{{ route('seo.settings.test', 'openrouter') }}" style="margin-top:10px">@csrf<button class="btn-pro btn-pro-ghost seo-btn-sm" data-loading="تست…"><i class="fa-solid fa-vial"></i> تست اتصال</button></form>
    </section>

    {{-- تلگرام --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-brands fa-telegram"></i> بات تلگرام سئو @include('seo::partials.help', ['k' => 'settings.telegram'])</div>{!! $status($bot['configured'], '@'.$bot['username'], 'توکن ندارد') !!}</div>
      @if($bot['code'])
        <div class="seo-callout" style="margin-bottom:12px"><i class="fa-solid fa-key"></i><div>این پیام را برای <a class="seo-link" href="https://t.me/{{ $bot['username'] }}?start={{ $bot['code'] }}" target="_blank" rel="noopener">{{ '@'.$bot['username'] }}</a> بفرستید (یا روی لینک بزنید):<br><span class="seo-kbd">/start {{ $bot['code'] }}</span> — اعتبار ۳۰ دقیقه</div></div>
      @endif
      <div class="seo-row-gap">
        <form method="POST" action="{{ route('seo.settings.telegram.code') }}">@csrf<button class="btn-pro btn-pro-primary seo-btn-sm"><i class="fa-solid fa-link"></i> کد اتصال مدیر</button></form>
        <form method="POST" action="{{ route('seo.settings.telegram.setup') }}">@csrf<button class="btn-pro btn-pro-ghost seo-btn-sm" data-loading="ثبت…"><i class="fa-solid fa-plug"></i> ثبت وب‌هوک</button></form>
        <form method="POST" action="{{ route('seo.settings.telegram.test') }}">@csrf<button class="btn-pro btn-pro-ghost seo-btn-sm"><i class="fa-solid fa-paper-plane"></i> پیام آزمایشی</button></form>
      </div>
      <div class="seo-rows" style="margin-top:10px">
        @forelse($bot['admins'] as $a)
          <div class="seo-item"><span class="seo-dot {{ $a->is_active ? 'is-success' : '' }}"></span><div class="seo-item-main"><div class="seo-item-title">{{ $a->name ?: 'مدیر' }} {{ $a->username ? '· @'.$a->username : '' }}</div><div class="seo-item-sub">متصل {{ Fa::ago($a->created_at) }}</div></div></div>
        @empty
          <div class="seo-card-sub">هنوز مدیری متصل نشده.</div>
        @endforelse
      </div>
    </section>

    {{-- اتصال سایت --}}
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-plug"></i> اتصال به سایت (انتشار و محصولات) @include('seo::partials.help', ['k' => 'settings.connector'])</div>{!! $status($connector->health()[0], $connector->label(), 'نیاز به تنظیم') !!}</div>
      <form method="POST" action="{{ route('seo.settings.connector') }}" class="seo-form-grid">@csrf
        <div class="seo-field full"><label>پلتفرم</label><select name="platform" class="seo-select">@foreach($platforms as $k => $l)<option value="{{ $k }}" @selected($site->platform === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="seo-field full"><span class="hint">برای وردپرس: «نام کاربری» و «رمز برنامه» (کاربران ← پروفایل ← Application Passwords). برای همین پروژه‌ی لاراول نیازی به پر کردن نیست.</span></div>
        <div class="seo-field"><label>آدرس وردپرس</label><input name="url" class="seo-input seo-ltr" style="display:block" value="{{ $connectorConfig['url'] ?? '' }}"></div>
        <div class="seo-field"><label>نام کاربری</label><input name="username" class="seo-input seo-ltr" style="display:block" value="{{ $connectorConfig['username'] ?? '' }}"></div>
        <div class="seo-field"><label>رمز برنامه</label><input name="app_password" type="password" class="seo-input" placeholder="{{ ! empty($site->connectorConfig()['app_password']) ? '•••••• (ذخیره شده)' : '' }}"></div>
        <div class="seo-field"><label>وضعیت انتشار</label><select name="publish_status" class="seo-select"><option value="draft" @selected(($connectorConfig['publish_status'] ?? 'draft') === 'draft')>پیش‌نویس در وردپرس</option><option value="publish" @selected(($connectorConfig['publish_status'] ?? '') === 'publish')>انتشار مستقیم</option></select></div>
        <div class="full seo-row-gap"><button class="btn-pro btn-pro-primary seo-btn-sm"><i class="fa-solid fa-check"></i> ذخیره و تست</button></div>
      </form>
      <form method="POST" action="{{ route('seo.settings.test', 'connector') }}" style="margin-top:8px">@csrf<button class="btn-pro btn-pro-ghost seo-btn-sm" data-loading="تست…"><i class="fa-solid fa-vial"></i> تست خواندن محصولات</button></form>
    </section>
  </div>

  <section class="seo-card">
    <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-rocket"></i> ارتقاهای داده @include('seo::partials.help', ['k' => 'settings.upgrades'])</div></div>
    <div class="seo-grid seo-grid-3" style="gap:12px">
      <div class="seo-item" style="border:1px solid var(--border);border-radius:12px;padding:12px"><div class="seo-item-main"><div class="seo-item-title">DataForSEO</div><div class="seo-item-sub">رتبه‌ی دقیق روزانه از نتایج زنده‌ی گوگل ایران + ۱۰ رقیب هر کلمه (~۰٫۰۰۲ دلار برای هر بررسی). در پروفایل «رشد» خودکار فعال می‌شود.</div></div>{!! $status($dataforseo, 'فعال', 'تنظیم نشده') !!}</div>
      <div class="seo-item" style="border:1px solid var(--border);border-radius:12px;padding:12px"><div class="seo-item-main"><div class="seo-item-title">میزفا تولز</div><div class="seo-item-sub">ردیاب رتبه و حجم جستجوی فارسی (API و MCP). اتصال در نسخه‌ی بعدی.</div></div><span class="seo-tag is-warning">بزودی</span></div>
      <div class="seo-item" style="border:1px solid var(--border);border-radius:12px;padding:12px"><div class="seo-item-main"><div class="seo-item-title">IndexNow</div><div class="seo-item-sub">اعلام فوری صفحات جدید به بینگ، یاندکس و موتورهای مبتنی بر آن. کلید را در <span class="seo-kbd">SEO_INDEXNOW_KEY</span> بگذارید.</div></div>{!! $status($indexnow, 'فعال', 'تنظیم نشده') !!}</div>
    </div>
  </section>
</div>
@endsection
