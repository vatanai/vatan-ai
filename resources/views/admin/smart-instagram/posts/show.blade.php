@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@use('App\Services\SmartInstagram\Posts\PostCampaignService')
@php
  $siTitle = $campaign->title;
  $siSubtitle = 'سناریوی ثبت پست · نسخه '.Ui::n($campaign->version).' · '.(PostCampaignService::STATUSES[$campaign->status] ?? $campaign->status);
  $s = (array) $campaign->settings;
  $m = $metrics + ['matched' => 0, 'succeeded' => 0, 'failed' => 0, 'replies' => 0, 'dms' => 0, 'last_status' => null, 'last_at' => null];
  $element = data_get($card, 'message.attachment.payload.elements.0', []);
  $phoneState = [
    'cover' => $post->coverUrl(), 'caption' => $post->shortCaption(220), 'likes' => $post->like_count,
    'keyword' => optional($campaign->keywords->firstWhere('is_active', true))->keyword ?? 'لینک',
    'replyOn' => (bool) $campaign->public_reply_enabled, 'styles' => array_values(array_filter((array) data_get($s, 'reply.styles', []))),
    'aiPersonalize' => (bool) data_get($s, 'reply.ai_personalize'),
    'dmOn' => (bool) $campaign->dm_enabled, 'followOn' => (bool) $campaign->follow_required, 'mode' => data_get($s, 'dm.mode'),
    'openingText' => data_get($s, 'dm.opening_text'), 'openingButton' => data_get($s, 'dm.opening_button'),
    'followText' => data_get($s, 'follow.text'), 'followButton' => data_get($s, 'follow.button'),
    'intro' => data_get($s, 'card.intro_text'), 'after' => data_get($s, 'card.after_text'),
    'image' => $element['image_url'] ?? null, 'title' => $element['title'] ?? ($card['title'] ?? ''), 'subtitle' => $element['subtitle'] ?? '',
    'buttons' => collect($element['buttons'] ?? [])->map(fn ($b) => ['label' => $b['title'] ?? ''])->values(),
  ];
  $stageLabels = ['awaiting_click' => 'منتظر زدن دکمه', 'awaiting_follow' => 'منتظر فالو', 'delivery_failed' => 'نیازمند بررسی مجدد', 'completed' => 'کارت دریافت کرد'];
  $sessionsTotal = max(1, (int) $funnel->sum());
  $followLabels = ['following' => 'فالوور بود', 'not_following' => 'فالو نکرده بود', 'unknown' => 'نامشخص'];
  $igStats = [['fa-heart', 'لایک', $post->like_count], ['fa-comment', 'کامنت', $post->comments_count], ['fa-bookmark', 'ذخیره', $post->saved_count], ['fa-paper-plane', 'اشتراک', $post->shares_count]];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/smart-instagram-posts.css') }}?v={{ @filemtime(public_path('admin/css/smart-instagram-posts.css')) }}">
@endpush

@section('si-actions')
  <a href="{{ route('admin.smart-instagram.posts.index') }}" class="btn-pro btn-pro-secondary"><i class="fa-solid fa-arrow-right text-[11px]"></i> فهرست</a>
  @if($canManage)
    <form method="POST" action="{{ route('admin.smart-instagram.posts.sync-one', $campaign) }}">@csrf<button class="btn-pro btn-pro-secondary"><i class="fa-solid fa-rotate text-[11px]"></i> به‌روزرسانی آمار</button></form>
    <form method="POST" action="{{ route('admin.smart-instagram.posts.recheck', $campaign) }}">@csrf<button class="btn-pro btn-pro-secondary"><i class="fa-solid fa-arrows-rotate text-[11px]"></i> بررسی مجدد ارسال‌ها</button></form>
    @if($campaign->status === 'active')
      <form method="POST" action="{{ route('admin.smart-instagram.posts.status', $campaign) }}">@csrf<input type="hidden" name="status" value="paused"><button class="btn-pro btn-pro-secondary"><i class="fa-solid fa-pause text-[11px]"></i> توقف</button></form>
    @else
      <form method="POST" action="{{ route('admin.smart-instagram.posts.status', $campaign) }}" data-confirm="سناریو روی کامنت‌های واقعی اجرا و پیام واقعی ارسال شود؟">@csrf<input type="hidden" name="status" value="active"><button class="btn-pro btn-pro-secondary"><i class="fa-solid fa-play text-[11px]"></i> فعال‌سازی</button></form>
    @endif
    <a href="{{ route('admin.smart-instagram.posts.edit', $campaign) }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-pen text-[11px]"></i> ویرایش</a>
  @endif
@endsection

@section('si-page')
  <div class="sip-wizard">
    <div style="min-width:0">
      <section class="content-card si-panel" style="margin-bottom:14px">
        <div class="sip-hero">
          <div class="sip-hero-cover">@if($post->coverUrl())<img src="{{ $post->coverUrl() }}" alt="">@else<span class="sip-cover-empty"><i class="fa-solid {{ $post->kindIcon() }}"></i></span>@endif</div>
          <div style="min-width:0;display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <span class="badge-pro badge-{{ Ui::statusTone($campaign->status) }}"><i class="fa-solid fa-circle"></i> {{ PostCampaignService::STATUSES[$campaign->status] ?? $campaign->status }}</span>
              <span class="badge-pro badge-neutral"><i class="fa-solid {{ $post->kindIcon() }}"></i> {{ $post->kindLabel() }}</span>
              @if($campaign->follow_required)<span class="badge-pro badge-warning"><i class="fa-solid fa-user-check"></i> فالو اجباری</span>@endif
              @unless($post->isVerified())<span class="badge-pro badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> نیازمند بررسی اتصال</span>@endunless
            </div>
            <p class="si-muted" style="margin:0;line-height:1.9">{{ $post->shortCaption(320) }}</p>
            <dl class="si-kv">
              <dt>انتشار</dt><dd>{{ Ui::date($post->published_at, true) }}</dd>
              <dt>شناسه‌ی رسانه</dt><dd class="si-mono si-ltr">{{ $post->media_id }}</dd>
              <dt>آمار اینستاگرام</dt><dd>{{ $post->stats_synced_at ? Ui::ago($post->stats_synced_at) : 'دریافت نشده' }}</dd>
              @if($post->permalink)<dt>لینک</dt><dd><a class="si-link" href="{{ $post->permalink }}" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> مشاهده در اینستاگرام</a></dd>@endif
              @if($post->sync_error)<dt>خطای همگام‌سازی</dt><dd class="si-error">{{ \Illuminate\Support\Str::limit($post->sync_error, 140) }}</dd>@endif
            </dl>
            <div class="sip-ig-stats">
              @foreach($igStats as [$icon, $label, $value])
                <div class="sip-ig-stat" title="{{ $label }}"><i class="fa-solid {{ $icon }}"></i>@if($value === null)<b class="is-missing">دریافت نشده</b>@else<b class="si-num">{{ Ui::n($value) }}</b>@endif</div>
              @endforeach
            </div>
            <div class="sip-keywords">
              @foreach($campaign->keywords as $kw)<span class="sip-kw {{ $kw->is_active ? '' : 'is-off' }}" title="{{ PostCampaignService::MATCH_MODES[$kw->match_mode] ?? '' }}">{{ $kw->keyword }}</span>@endforeach
            </div>
          </div>
        </div>
      </section>

      <div class="si-stats">
        @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-comments', 'tone' => 'info', 'value' => Ui::n($m['matched']), 'label' => 'کامنت منطبق'])
        @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-reply', 'tone' => 'primary', 'value' => Ui::n($m['replies']), 'label' => 'پاسخ عمومی'])
        @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-paper-plane', 'tone' => 'success', 'value' => Ui::n($m['dms']), 'label' => 'دایرکت ارسال‌شده'])
        @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-bug', 'tone' => $m['failed'] ? 'danger' : 'warning', 'value' => Ui::n($m['failed']), 'label' => 'اجرای ناموفق', 'hint' => $m['last_at'] ? 'آخرین اجرا '.Ui::ago($m['last_at']) : 'هنوز اجرا نشده'])
      </div>

      <div class="si-grid-even" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-bottom:14px">
        <section class="content-card si-panel">
          <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-filter"></i> قیف دایرکت</div></div>
          <div class="sip-funnel" style="margin-top:12px">
            @foreach($stageLabels as $key => $label)
              @php $v = (int) ($funnel[$key] ?? 0); @endphp
              <div class="sip-funnel-row"><span>{{ $label }}</span><span class="sip-funnel-bar"><span style="width:{{ round($v / $sessionsTotal * 100) }}%"></span></span><b class="si-num">{{ Ui::n($v) }}</b></div>
            @endforeach
          </div>
          @if($funnel->sum() === 0)<p class="si-help" style="margin-top:10px">هنوز گفتگویی از این پست شروع نشده.</p>@endif
        </section>
        <section class="content-card si-panel">
          <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-user-check"></i> وضعیت فالو کامنت‌گذاران</div></div>
          <div class="sip-funnel" style="margin-top:12px">
            @php $followTotal = max(1, (int) $followStats->sum()); @endphp
            @foreach($followLabels as $key => $label)
              @php $v = (int) ($followStats[$key] ?? 0); @endphp
              <div class="sip-funnel-row"><span>{{ $label }}</span><span class="sip-funnel-bar"><span style="width:{{ round($v / $followTotal * 100) }}%"></span></span><b class="si-num">{{ Ui::n($v) }}</b></div>
            @endforeach
          </div>
          @unless($campaign->follow_required)<p class="si-help" style="margin-top:10px">فالو اجباری برای این پست خاموش است.</p>@endunless
        </section>
      </div>

      <section class="content-card si-panel" style="margin-bottom:14px" data-sip-simulate data-url="{{ route('admin.smart-instagram.posts.simulate', $campaign) }}">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-vial"></i> آزمون بدون ارسال</div><span class="si-muted">هیچ پیامی برای کسی ارسال نمی‌شود</span></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
          <input class="input-pro" data-sim-text maxlength="500" placeholder="یک کامنت نمونه — مثلاً: لینک لطفاً" style="flex:1;min-width:200px" aria-label="کامنت نمونه">
          <select class="input-pro" data-sim-follows style="width:auto" aria-label="وضعیت فالو">
            <option value="yes">کاربر فالوور است</option><option value="no">فالو نکرده</option><option value="unknown">وضعیت نامشخص</option>
          </select>
          <button type="button" class="btn-pro btn-pro-primary" data-sim-run><i class="fa-solid fa-play text-[11px]"></i> اجرا</button>
        </div>
        <div class="si-simulate-result" data-sim-out style="margin-top:12px" aria-live="polite"></div>
      </section>

      <section class="content-card si-panel is-flush" style="margin-bottom:14px">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-clock-rotate-left"></i> اجراها</div></div>
        <div class="si-table-wrap" style="margin-top:10px">
          <table class="table-pro">
            <thead><tr><th>مخاطب</th><th>حالت</th><th>نتیجه</th><th>نسخه</th><th>زمان</th></tr></thead>
            <tbody>
              @forelse($runs ?? [] as $run)
                <tr>
                  <td>{{ $run->contact?->label() ?? '—' }}</td>
                  <td>{{ $run->mode === 'test' ? 'آزمایشی' : 'واقعی' }}</td>
                  <td><span class="badge-pro badge-{{ Ui::statusTone($run->status) }}">{{ Ui::label('run', $run->status) }}</span>@if($run->error)<div class="si-error" title="{{ $run->error }}">{{ \Illuminate\Support\Str::limit($run->error, 60) }}</div>@endif</td>
                  <td class="si-num">{{ Ui::n($run->rule_version) }}</td>
                  <td class="si-muted">{{ Ui::date($run->created_at, true) }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="si-table-empty">هنوز اجرایی ثبت نشده.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($runs && $runs->hasPages())<div style="padding:10px 16px">@include('admin.smart-instagram.partials.pagination', ['paginator' => $runs])</div>@endif
      </section>

      <div class="si-grid-even" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px">
        <section class="content-card si-panel is-flush">
          <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-chart-line"></i> آمار روزانه‌ی پست</div></div>
          <div class="si-table-wrap" style="margin-top:10px">
            <table class="table-pro">
              <thead><tr><th>روز</th><th>لایک</th><th>کامنت</th><th>ذخیره</th><th>اشتراک</th></tr></thead>
              <tbody>
                @forelse($daily as $d)
                  <tr>
                    <td>{{ Ui::date($d->day) }}</td>
                    @foreach(['like_count', 'comments_count', 'saved_count', 'shares_count'] as $col)<td class="si-num">{{ $d->{$col} === null ? '—' : Ui::n($d->{$col}) }}</td>@endforeach
                  </tr>
                @empty
                  <tr><td colspan="5" class="si-table-empty">آماری دریافت نشده.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
        <section class="content-card si-panel is-flush">
          <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-code-branch"></i> نسخه‌ها</div></div>
          <div class="si-table-wrap" style="margin-top:10px">
            <table class="table-pro">
              <thead><tr><th>نسخه</th><th>توسط</th><th>زمان</th><th></th></tr></thead>
              <tbody>
                @forelse($campaign->versions->sortByDesc('version')->take(15) as $version)
                  <tr>
                    <td class="si-num">{{ Ui::n($version->version) }}@if($version->note)<div class="si-muted">{{ $version->note }}</div>@endif</td>
                    <td>{{ $version->admin?->name ?? '—' }}</td>
                    <td class="si-muted">{{ Ui::date($version->created_at, true) }}</td>
                    <td>
                      @if($canManage && $version->version !== $campaign->version)
                        <form method="POST" action="{{ route('admin.smart-instagram.posts.restore', [$campaign, $version]) }}" data-confirm="تنظیمات نسخه‌ی {{ $version->version }} بازگردانده شود؟ سناریو پیش‌نویس می‌شود.">@csrf<button class="icon-action-btn" title="بازگردانی"><i class="fa-solid fa-rotate-left"></i></button></form>
                      @elseif($version->version === $campaign->version)
                        <span class="badge-pro badge-success">فعلی</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="si-table-empty">نسخه‌ای ثبت نشده.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      </div>

      @if($canManage)
        <form method="POST" action="{{ route('admin.smart-instagram.posts.destroy', $campaign) }}" data-confirm="سناریوی این پست و قانون اجرایی آن حذف شود؟ این کار برگشت ندارد." style="margin-top:16px">@csrf @method('DELETE')
          <button class="btn-pro btn-pro-secondary" style="color:var(--danger)"><i class="fa-solid fa-trash text-[11px]"></i> حذف سناریو</button>
        </form>
      @endif
    </div>

    @include('admin.smart-instagram.posts.partials.phone', ['state' => $phoneState])
  </div>
@endsection

@section('si-scripts')
@php $sipConfig = ['username' => $post->channel?->username ?: 'vatan.ai', 'sampleName' => 'محسن', 'sampleUser' => 'mohsen_shop', 'csrf' => csrf_token()]; @endphp
<script>window.SIP_CONFIG = @json($sipConfig);</script>
<script src="{{ asset('admin/js/smart-instagram-posts.js') }}?v={{ @filemtime(public_path('admin/js/smart-instagram-posts.js')) }}" defer></script>
@endsection
