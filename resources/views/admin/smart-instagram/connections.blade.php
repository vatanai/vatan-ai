@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'اتصال‌ها و تیم';
  $siSubtitle = 'وضعیت اتصال رسمی Meta، ورودی واسط‌ها (n8n / Composio)، کلیدهای ایمنی، ساعت کاری و نقش اعضای تیم.';
  $hours = (array) data_get($workspace->settings, 'business_hours', ['start' => '09:00', 'end' => '21:00']);
@endphp

@section('si-actions')
  @if($canManage)
    <form method="POST" action="{{ route('admin.smart-instagram.connections.sync') }}">@csrf<button class="btn-pro btn-pro-ghost"><i class="fa-solid fa-rotate text-[11px]"></i> هم‌گام‌سازی با اتصال Meta</button></form>
    @if($composioConfigured)<form method="POST" action="{{ route('admin.smart-instagram.connections.sync-composio') }}">@csrf<button class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrows-rotate text-[11px]"></i> هم‌گام‌سازی با Composio</button></form>@endif
    <form method="POST" action="{{ route('admin.smart-instagram.connections.sandbox') }}">@csrf<button class="btn-pro btn-pro-ghost"><i class="fa-solid fa-flask text-[11px]"></i> کانال آزمایشی</button></form>
  @endif
@endsection

@section('si-page')
  <div class="si-grid-2">
    <div class="si-stack">
      <section class="content-card si-panel is-flush">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-brands fa-instagram"></i> کانال‌های متصل</div><div class="si-panel-sub">فقط حساب حرفه‌ای (Business / Creator) از مسیر رسمی OAuth؛ رمز حساب هرگز گرفته نمی‌شود.</div></div></div>
        <div class="si-table-wrap" style="margin-top:10px">
          <table class="table-pro">
            <thead><tr><th>کانال</th><th>اتصال‌دهنده</th><th>وضعیت</th><th>آخرین رویداد</th><th>آخرین تست</th><th>ارسال</th><th></th></tr></thead>
            <tbody>
              @forelse($channels as $channel)
                <tr>
                  <td><span class="si-td-strong">{{ $channel->name }}</span><div class="si-muted si-ltr">{{ $channel->username ? '@'.$channel->username : ($channel->external_account_id ?: '—') }}</div></td>
                  <td>{{ $gateways[$channel->gateway] ?? $channel->gateway }}</td>
                  <td><span class="badge-pro badge-{{ Ui::statusTone($channel->status) }}"><i class="fa-solid fa-circle"></i> {{ Ui::label('channel', $channel->status) }}</span>@if($channel->last_error)<div class="si-error" style="max-width:220px;white-space:normal">{{ \Illuminate\Support\Str::limit($channel->last_error, 120) }}</div>@endif</td>
                  <td class="si-muted">{{ Ui::ago($channel->last_event_at) }}</td>
                  <td class="si-muted">{{ Ui::ago($channel->health_checked_at) }}</td>
                  <td><span class="badge-pro {{ $channel->outbound_enabled ? 'badge-success' : 'badge-neutral' }}">{{ $channel->outbound_enabled ? 'روشن' : 'خاموش' }}</span></td>
                  <td>
                    @if($canManage)
                      <div style="display:flex;gap:5px;justify-content:flex-end">
                        <form method="POST" action="{{ route('admin.smart-instagram.connections.test', $channel) }}">@csrf<button class="icon-action-btn" title="تست سلامت (فقط‌خواندنی)"><i class="fa-solid fa-stethoscope"></i></button></form>
                      </div>
                    @endif
                  </td>
                </tr>
                @if($canManage)
                  <tr><td colspan="7" style="background:var(--input-bg)">
                    <form method="POST" action="{{ route('admin.smart-instagram.connections.update', $channel) }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap" data-confirm="تنظیمات ارسال این کانال تغییر کند؟">@csrf @method('PATCH')
                      <input name="name" class="input-pro" style="width:200px;height:32px" value="{{ $channel->name }}" maxlength="120" aria-label="نام کانال">
                      <label class="si-check"><input type="hidden" name="outbound_enabled" value="0"><input type="checkbox" name="outbound_enabled" value="1" @checked($channel->outbound_enabled)> اجازه‌ی ارسال از این کانال</label>
                      <button class="btn-pro btn-pro-ghost" style="height:32px">ذخیره</button>
                      <span class="si-help">ارسال واقعی فقط وقتی انجام می‌شود که هم این گزینه و هم کلید اصلی سرور روشن باشد.</span>
                    </form>
                  </td></tr>
                @endif
              @empty
                <tr><td colspan="7" class="si-table-empty">هنوز کانالی ثبت نشده. ابتدا Meta را در «تکنولوژی مارکتینگ › اتصال‌ها» وصل و سپس «هم‌گام‌سازی» را بزنید.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-plug-circle-check"></i> چک‌لیست راه‌اندازی</div></div>
        @php
        $checkRows = [
          ['ok' => (bool) $metaIntegration, 'title' => 'اتصال رسمی Meta (OAuth)', 'desc' => $metaIntegration ? 'ثبت شده · '.Ui::label('channel', $metaIntegration->status) : 'از «تکنولوژی مارکتینگ › اتصال‌ها و سلامت سرویس» حساب حرفه‌ای را وصل کنید.'],
          ['ok' => $checks['meta_app'], 'title' => 'کلیدهای اپ Meta روی سرور', 'desc' => 'META_APP_ID و META_APP_SECRET (برای امضای وب‌هوک)'],
          ['ok' => $checks['meta_verify'], 'title' => 'توکن تأیید وب‌هوک', 'desc' => 'META_WEBHOOK_VERIFY_TOKEN'],
          ['ok' => $checks['composio'], 'title' => 'اتصال Composio به اینستاگرام', 'desc' => $checks['composio'] ? 'حساب متصل و آماده‌ی دریافت داده‌های اینستاگرام است.' : 'کلید و شناسه‌ی حساب متصل Composio روی محیط تنظیم نشده است.'],
          ['ok' => $checks['ingest_secret'], 'title' => 'کلید امضای ورودی واسط (اختیاری)', 'desc' => 'SMART_INSTAGRAM_INGEST_SECRET — فقط اگر n8n/Composio رویداد می‌فرستد'],
          ['ok' => $checks['ai'], 'title' => 'دستیار هوشمند', 'desc' => 'SMART_INSTAGRAM_AI_ENABLED و کلید OpenRouter'],
          ['ok' => $checks['outbound'], 'title' => 'کلید اصلی ارسال', 'desc' => $checks['outbound'] ? 'روشن — ارسال‌ها پس از عبور از قواعد ایمنی انجام می‌شوند.' : 'خاموش (پیش‌فرض امن) — پیام‌ها ثبت و پیشنهاد می‌شوند ولی ارسال نمی‌شوند. SMART_INSTAGRAM_OUTBOUND_ENABLED', 'neutral' => true],
          ['ok' => $checks['transcription'], 'title' => 'متن‌نگاری خودکار صوت', 'desc' => 'تا تأیید سرویس بیرونی خاموش است؛ متن صوت را می‌توان دستی ثبت کرد. (بزودی)', 'neutral' => true],
        ];
        @endphp
        @foreach($checkRows as $row)
          <div class="si-row">
            <i class="fa-solid {{ $row['ok'] ? 'fa-circle-check' : (!empty($row['neutral']) ? 'fa-circle-minus' : 'fa-circle-xmark') }}" style="color:var(--{{ $row['ok'] ? 'success' : (!empty($row['neutral']) ? 'text-soft' : 'warning') }})"></i>
            <div class="si-row-main"><div class="si-row-title">{{ $row['title'] }}</div><div class="si-row-sub" style="white-space:normal">{{ $row['desc'] }}</div></div>
          </div>
        @endforeach
        @if($metaIntegrationsUrl)<a href="{{ $metaIntegrationsUrl }}" class="si-link" style="margin-top:10px">رفتن به اتصال Meta <i class="fa-solid fa-angle-left"></i></a>@endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-link"></i> آدرس‌های دریافت رویداد</div></div>
        <div class="si-form is-1">
          <div class="si-field"><label for="u-meta">وب‌هوک رسمی Meta (کامنت، دایرکت، منشن)</label><div class="si-copy"><input id="u-meta" class="input-pro" readonly value="{{ $urls['meta_webhook'] }}"><button type="button" class="icon-action-btn" data-si-copy="u-meta" aria-label="کپی"><i class="fa-regular fa-copy"></i></button></div><span class="si-help">در پنل توسعه‌دهنده‌ی Meta، فیلدهای comments، messages و mentions را برای حساب اینستاگرام مشترک شوید. امضای X-Hub-Signature-256 بررسی می‌شود.</span></div>
          <div class="si-field"><label for="u-ingest">ورودی امضاشده‌ی واسط (n8n / Composio)</label><div class="si-copy"><input id="u-ingest" class="input-pro" readonly value="{{ $urls['ingest'] }}"><button type="button" class="icon-action-btn" data-si-copy="u-ingest" aria-label="کپی"><i class="fa-regular fa-copy"></i></button></div>
            <span class="si-help">هدرها: <code class="si-ltr">X-Vatan-Timestamp</code> و <code class="si-ltr">X-Vatan-Signature: sha256=HMAC(secret, timestamp.body)</code> — بدنه: <code class="si-ltr">{"type":"dm|comment|story_reply|mention|ad","id":"…","sender":{"id":"…","username":"…"},"text":"…","media_id":"…"}</code></span></div>
        </div>
      </section>
    </div>

    <div class="si-stack">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-users-gear"></i> تیم و نقش‌ها</div><div class="si-panel-sub">نقش شما: {{ $roles[$myRole] ?? $myRole }} · ادمین‌های بدون نقش، «اپراتور فروش» هستند.</div></div></div>
        @foreach($members as $member)
          <div class="si-row">
            <span class="si-avatar is-sm">{{ mb_substr($member->admin?->name ?? '؟', 0, 1) }}</span>
            <div class="si-row-main"><div class="si-row-title">{{ $member->admin?->name ?? 'ادمین حذف‌شده' }}</div><div class="si-row-sub">{{ $roles[$member->role] ?? $member->role }}</div></div>
            @if($canManage)<form method="POST" action="{{ route('admin.smart-instagram.connections.team.destroy', $member) }}" data-confirm="نقش این عضو حذف شود؟">@csrf @method('DELETE')<button class="icon-action-btn danger" aria-label="حذف"><i class="fa-solid fa-xmark"></i></button></form>@endif
          </div>
        @endforeach
        @if($members->isEmpty())<div class="si-muted" style="margin-bottom:8px">هنوز نقشی تعریف نشده؛ مدیر ارشد پنل همیشه «مالک برند» است.</div>@endif
        @if($canManage)
          <form method="POST" action="{{ route('admin.smart-instagram.connections.team.store') }}" class="si-form" style="margin-top:12px">@csrf
            <div class="si-field"><label for="m-admin">ادمین</label><select id="m-admin" name="admin_id" class="input-pro" required>@foreach($admins as $admin)<option value="{{ $admin->id }}">{{ $admin->name }}</option>@endforeach</select></div>
            <div class="si-field"><label for="m-role">نقش</label><select id="m-role" name="role" class="input-pro">@foreach($roles as $k => $l)<option value="{{ $k }}" @selected($k === 'operator')>{{ $l }}</option>@endforeach</select></div>
            <div class="si-form-actions"><button class="btn-pro btn-pro-primary" style="height:34px">ثبت نقش</button></div>
          </form>
          <ul class="si-ul" style="margin-top:10px;font-size:11px">
            <li><b>مالک برند:</b> همه‌چیز، از جمله اتصال، ارسال و حذف داده</li>
            <li><b>مدیر فروش:</b> گفتگوها، مشتریان، قیف، اتومیشن و دانش</li>
            <li><b>اپراتور:</b> فقط گفتگوهای خودش یا بدون مسئول</li>
            <li><b>مدیر محتوا:</b> اتومیشن، دانش و گزارش؛ بدون پاسخ‌دهی</li>
            <li><b>ناظر:</b> فقط خواندنی</li>
          </ul>
        @endif
      </section>

      <section class="content-card si-panel" id="telegram-bot">
        <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-brands fa-telegram"></i> کارمندان بات تلگرام</div><div class="si-panel-sub">فقط شناسه‌های این فهرست از بات <span class="si-ltr">{{ '@'.$telegramBot['username'] }}</span> استفاده می‌کنند؛ پست تازه برایشان اعلام می‌شود و تنظیمش را داخل تلگرام انجام می‌دهند.</div></div></div>
        @unless($telegramBot['configured'])
          <div class="si-error" style="margin-bottom:10px">توکن بات هنوز روی سرور تنظیم نشده (<span class="si-ltr">TELEGRAM_INSTAGRAM_BOT_TOKEN</span>).</div>
        @endunless
        @forelse($telegramAccounts as $account)
          <div class="si-row" style="{{ $account->is_active ? '' : 'opacity:.55' }}">
            <span class="si-avatar is-sm">{{ mb_substr($account->displayName(), 0, 1) }}</span>
            <div class="si-row-main">
              <div class="si-row-title">{{ $account->displayName() }}@if($account->admin_id) <span class="badge-pro badge-info">ادمین پنل</span>@endif</div>
              <div class="si-row-sub"><span class="si-ltr">{{ $account->telegram_id }}</span>{{ $account->username ? ' · @'.$account->username : '' }} · {{ $account->last_seen_at ? 'آخرین استفاده '.$account->last_seen_at->diffForHumans() : 'هنوز بات را باز نکرده' }}{{ $account->notify_new_posts ? '' : ' · 🔕' }}</div>
            </div>
            @if($canManage)
              @unless($account->admin_id)
                <form method="POST" action="{{ route('admin.smart-instagram.connections.telegram.update', $account) }}">@csrf @method('PATCH')
                  <select name="role" class="input-pro" style="height:32px;font-size:12px" onchange="this.form.submit()" aria-label="سطح دسترسی">@foreach($telegramRoles as $k => $l)<option value="{{ $k }}" @selected($account->role === $k)>{{ $l }}</option>@endforeach</select>
                </form>
              @endunless
              <form method="POST" action="{{ route('admin.smart-instagram.connections.telegram.update', $account) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $account->is_active ? 0 : 1 }}"><button class="icon-action-btn" aria-label="{{ $account->is_active ? 'غیرفعال' : 'فعال' }}" title="{{ $account->is_active ? 'غیرفعال کردن موقت' : 'فعال کردن' }}"><i class="fa-solid {{ $account->is_active ? 'fa-pause' : 'fa-play' }}"></i></button></form>
            @endif
            @if($canManage || (int) $account->admin_id === (int) auth('admin')->id())<form method="POST" action="{{ route('admin.smart-instagram.connections.telegram.destroy', $account) }}" data-confirm="دسترسی «{{ $account->displayName() }}» به بات حذف شود؟">@csrf @method('DELETE')<button class="icon-action-btn danger" aria-label="حذف"><i class="fa-solid fa-xmark"></i></button></form>@endif
          </div>
        @empty
          <div class="si-muted" style="margin-bottom:8px">هنوز کسی اضافه نشده است.</div>
        @endforelse
        @if($canManage)
          <form method="POST" action="{{ route('admin.smart-instagram.connections.telegram.store') }}" class="si-form" style="margin-top:12px">@csrf
            <div class="si-field"><label for="tg-name">نام کارمند</label><input id="tg-name" name="name" class="input-pro" maxlength="120" required value="{{ old('name') }}" placeholder="مثلاً: ساغر محمدی"></div>
            <div class="si-field"><label for="tg-id">شناسه‌ی عددی تلگرام</label><input id="tg-id" name="telegram_id" class="input-pro si-ltr" inputmode="numeric" maxlength="20" required value="{{ old('telegram_id') }}" placeholder="101754869"></div>
            <div class="si-field"><label for="tg-role">دسترسی</label><select id="tg-role" name="role" class="input-pro">@foreach($telegramRoles as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <div class="si-form-actions"><button class="btn-pro btn-pro-primary" style="height:34px"><i class="fa-solid fa-plus text-[11px]"></i> افزودن</button></div>
          </form>
          <div class="si-help" style="margin-top:6px">شناسه را خود کارمند با زدن Start در بات می‌بیند (یا از ‎@userinfobot‎).</div>
        @endif
        @if($telegramBot['configured'])
          <div class="si-form-actions" style="margin-top:10px">
            <form method="POST" action="{{ route('admin.smart-instagram.connections.telegram') }}" target="_blank">@csrf<button class="btn-pro btn-pro-ghost" style="height:34px"><i class="fa-brands fa-telegram text-[12px]"></i> اتصال تلگرام خودم</button></form>
            <a class="btn-pro btn-pro-ghost si-ltr" style="height:34px" href="https://t.me/{{ $telegramBot['username'] }}" target="_blank" rel="noopener">{{ '@'.$telegramBot['username'] }}</a>
          </div>
        @endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-business-time"></i> ساعت کاری</div></div>
        @if($canManage)
          <form method="POST" action="{{ route('admin.smart-instagram.connections.settings') }}" class="si-form">@csrf
            <div class="si-field"><label for="h-start">شروع</label><input id="h-start" type="time" name="business_hours_start" class="input-pro" value="{{ $hours['start'] ?? '09:00' }}" required></div>
            <div class="si-field"><label for="h-end">پایان</label><input id="h-end" type="time" name="business_hours_end" class="input-pro" value="{{ $hours['end'] ?? '21:00' }}" required></div>
            <div class="si-form-actions"><button class="btn-pro btn-pro-ghost" style="height:34px">ذخیره</button><span class="si-help">به وقت تهران؛ در شرط اتومیشن‌ها استفاده می‌شود.</span></div>
          </form>
        @else
          <div class="si-muted">{{ Ui::n($hours['start'] ?? '09:00') }} تا {{ Ui::n($hours['end'] ?? '21:00') }}</div>
        @endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-ban"></i> مرز قابلیت‌ها</div></div>
        <ul class="si-ul" style="font-size:11.5px">
          <li>فقط مسیر رسمی Meta؛ بدون خزیدن، ورود با رمز، فالو/آنفالو یا دایرکت سرد.</li>
          <li>شروع گفتگو فقط پس از پیام مشتری و در پنجره‌ی ۲۴ساعته؛ هر کامنت فقط یک پاسخ خصوصی.</li>
          <li>تاریخچه از لحظه‌ی اتصال ساخته می‌شود؛ مهاجرت دایرکت‌های قدیمی تضمین نمی‌شود.</li>
          <li>Composio و n8n فقط واسط قابل‌جایگزین‌اند؛ داده و منطق فروش در وطن می‌ماند.</li>
        </ul>
      </section>
    </div>
  </div>
@endsection
