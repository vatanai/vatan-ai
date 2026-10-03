@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@use('App\Services\SmartInstagram\Posts\PostCampaignService')
@php
  $siTitle = 'ثبت پست';
  $siSubtitle = 'برای هر پست مشخص کنید با کدام کلمه، چه پاسخی در کامنت و چه کارتی در دایرکت ارسال شود — با شرط فالو، حالت آزمایشی و آمار زنده.';
  $statusLed = ['active' => 'is-active', 'test' => 'is-test', 'paused' => 'is-paused', 'draft' => ''];
  $statFmt = fn ($v) => $v === null ? null : Ui::n($v);
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/smart-instagram-posts.css') }}?v={{ @filemtime(public_path('admin/css/smart-instagram-posts.css')) }}">
@endpush

@section('si-actions')
  @if($canManage)
    <form method="POST" action="{{ route('admin.smart-instagram.posts.sync') }}">@csrf
      <button class="btn-pro btn-pro-secondary" @disabled(!$channel) title="{{ $channel ? 'دریافت آخرین پست‌ها از اتصال' : 'اتصالی برقرار نیست' }}"><i class="fa-solid fa-rotate text-[11px]"></i> همگام‌سازی پست‌ها</button>
    </form>
    <a href="{{ route('admin.smart-instagram.posts.create') }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> ثبت پست جدید</a>
  @endif
@endsection

@section('si-page')
  @php $totals = collect($metrics); @endphp
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-layer-group', 'tone' => 'primary', 'value' => Ui::n($counts['all'] ?? 0), 'label' => 'پست ثبت‌شده'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-play', 'tone' => 'success', 'value' => Ui::n($counts['active'] ?? 0), 'label' => 'فعال', 'hint' => ($counts['test'] ?? 0) ? Ui::n($counts['test']).' آزمایشی' : null, 'hintTone' => 'warning'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-comments', 'tone' => 'info', 'value' => Ui::n($totals->sum('matched')), 'label' => 'کامنت منطبق'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-paper-plane', 'tone' => 'warning', 'value' => Ui::n($totals->sum('dms')), 'label' => 'دایرکت ارسال‌شده'])
  </div>

  @if(!$channel)
    <div class="si-flash is-warning" role="status"><i class="fa-solid fa-plug-circle-xmark"></i><span>هنوز اتصال اینستاگرامی برقرار نیست؛ پست‌ها همگام نمی‌شوند و سناریوها فقط به‌صورت پیش‌نویس ذخیره می‌شوند. <a class="si-link" href="{{ route('admin.smart-instagram.connections') }}">اتصال‌ها</a></span></div>
  @endif

  <div class="si-toolbar" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
    <div class="si-chips" role="tablist" aria-label="فیلتر وضعیت">
      <a href="{{ route('admin.smart-instagram.posts.index') }}" class="chip-filter {{ $status === '' ? 'active' : '' }}">همه <span class="si-num">{{ Ui::n($counts['all'] ?? 0) }}</span></a>
      @foreach(PostCampaignService::STATUSES as $key => $label)
        <a href="{{ route('admin.smart-instagram.posts.index', ['status' => $key]) }}" class="chip-filter {{ $status === $key ? 'active' : '' }}">{{ $label }} <span class="si-num">{{ Ui::n($counts[$key] ?? 0) }}</span></a>
      @endforeach
    </div>
    @if($canManage)
      <form method="POST" action="{{ route('admin.smart-instagram.posts.manual') }}" style="display:flex;gap:6px;flex:1;max-width:440px;min-width:240px">@csrf
        <input name="media_ref" class="input-pro si-ltr" required maxlength="300" placeholder="لینک پست اینستاگرام یا Media ID" aria-label="لینک پست یا شناسه‌ی رسانه" style="flex:1">
        <button class="btn-pro btn-pro-secondary"><i class="fa-solid fa-link text-[11px]"></i> افزودن</button>
      </form>
    @endif
  </div>

  @if($campaigns->isEmpty())
    <section class="content-card" style="text-align:center;padding:42px 18px">
      <div class="sip-step-icon" style="margin:0 auto 12px;width:64px;height:64px;font-size:24px;border-radius:20px"><i class="fa-solid fa-photo-film"></i></div>
      <h2 style="font-size:16px;font-weight:800;color:var(--text-h);margin:0 0 6px">{{ $status === '' ? 'هنوز پستی ثبت نشده است' : 'پستی با این وضعیت نیست' }}</h2>
      <p class="si-muted" style="max-width:460px;margin:0 auto 16px;line-height:1.9">پست را انتخاب کنید، کلمه‌ی کلیدی بگذارید و تعیین کنید کامنت‌گذار چه پاسخی بگیرد و چه کارتی در دایرکت دریافت کند.</p>
      @if($canManage)<a href="{{ route('admin.smart-instagram.posts.create') }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-plus text-[11px]"></i> ثبت پست جدید</a>@endif
    </section>
  @else
    <div class="sip-grid">
      @foreach($campaigns as $campaign)
        @php
          $post = $campaign->post;
          $m = $metrics[$campaign->id] ?? ['matched' => 0, 'replies' => 0, 'dms' => 0, 'failed' => 0, 'last_status' => null, 'last_at' => null];
          $igStats = [
            ['icon' => 'fa-heart', 'label' => 'لایک', 'v' => $post->like_count],
            ['icon' => 'fa-comment', 'label' => 'کامنت', 'v' => $post->comments_count],
            ['icon' => 'fa-bookmark', 'label' => 'ذخیره', 'v' => $post->saved_count],
            ['icon' => 'fa-paper-plane', 'label' => 'اشتراک', 'v' => $post->shares_count],
          ];
        @endphp
        <article class="sip-card">
          <a href="{{ route('admin.smart-instagram.posts.show', $campaign) }}" class="sip-cover" aria-label="جزئیات {{ $campaign->title }}">
            @if($post->coverUrl())<img src="{{ $post->coverUrl() }}" alt="" loading="lazy" decoding="async">@else<span class="sip-cover-empty"><i class="fa-solid {{ $post->kindIcon() }}"></i></span>@endif
            <span class="sip-cover-top">
              <span class="sip-glass"><span class="sip-led {{ $statusLed[$campaign->status] ?? '' }}"></span>{{ PostCampaignService::STATUSES[$campaign->status] ?? $campaign->status }}</span>
              <span class="sip-glass"><i class="fa-solid {{ $post->kindIcon() }}"></i> {{ $post->kindLabel() }}</span>
            </span>
            <span class="sip-cover-bottom">
              <span><i class="fa-regular fa-calendar"></i> {{ Ui::date($post->published_at, true) }}</span>
              @if(!$post->isVerified())<span class="sip-glass"><i class="fa-solid fa-triangle-exclamation"></i> نیازمند بررسی اتصال</span>@endif
            </span>
          </a>
          <div class="sip-body">
            <div>
              <div class="sip-title">{{ $campaign->title }}</div>
              <div class="sip-caption">{{ $post->shortCaption(160) }}</div>
            </div>
            <div class="sip-ig-stats" title="آمار اینستاگرام — {{ $post->stats_synced_at ? 'به‌روزرسانی '.Ui::ago($post->stats_synced_at) : 'هنوز دریافت نشده' }}">
              @foreach($igStats as $st)
                <div class="sip-ig-stat" title="{{ $st['label'] }}"><i class="fa-solid {{ $st['icon'] }}" aria-hidden="true"></i>@if($st['v'] === null)<b class="is-missing">دریافت نشده</b>@else<b class="si-num">{{ Ui::n($st['v']) }}</b>@endif</div>
              @endforeach
            </div>
            <div class="sip-keywords">
              @forelse($campaign->keywords as $kw)<span class="sip-kw {{ $kw->is_active ? '' : 'is-off' }}" title="{{ PostCampaignService::MATCH_MODES[$kw->match_mode] ?? '' }}">{{ $kw->keyword }}</span>@empty<span class="si-muted">بدون کلمه‌ی کلیدی</span>@endforelse
              @if($campaign->follow_required)<span class="sip-kw" style="background:var(--warning-l);color:var(--warning);border-color:var(--warning-m)"><i class="fa-solid fa-user-plus"></i> فالو اجباری</span>@endif
            </div>
            <div class="sip-auto">
              <div><b class="si-num">{{ Ui::n($m['matched']) }}</b><span>کامنت منطبق</span></div>
              <div><b class="si-num">{{ Ui::n($m['replies']) }}</b><span>پاسخ عمومی</span></div>
              <div><b class="si-num">{{ Ui::n($m['dms']) }}</b><span>دایرکت</span></div>
            </div>
            <div class="sip-last">
              <i class="fa-solid fa-clock-rotate-left"></i>
              @if($m['last_at'])آخرین اجرا {{ Ui::ago($m['last_at']) }} · <span class="badge-pro badge-{{ Ui::statusTone((string) $m['last_status']) }}">{{ Ui::label('run', $m['last_status']) }}</span>@else هنوز اجرا نشده @endif
              @if($m['failed'])<span class="si-error">· {{ Ui::n($m['failed']) }} ناموفق</span>@endif
            </div>
          </div>
          <div class="sip-foot">
            <a href="{{ route('admin.smart-instagram.posts.show', $campaign) }}" class="btn-pro btn-pro-secondary"><i class="fa-solid fa-chart-simple text-[11px]"></i> جزئیات</a>
            @if($canManage)
              <a href="{{ route('admin.smart-instagram.posts.edit', $campaign) }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-pen text-[11px]"></i> ویرایش</a>
              <details class="sip-menu">
                <summary class="icon-action-btn" aria-label="عملیات بیشتر"><i class="fa-solid fa-ellipsis"></i></summary>
                <div class="sip-menu-list">
                  @if($campaign->status !== 'active')
                    <form method="POST" action="{{ route('admin.smart-instagram.posts.status', $campaign) }}" data-confirm="سناریو روی کامنت‌های واقعی اجرا و پیام واقعی ارسال شود؟">@csrf<input type="hidden" name="status" value="active"><button><i class="fa-solid fa-play"></i> فعال‌سازی</button></form>
                  @else
                    <form method="POST" action="{{ route('admin.smart-instagram.posts.status', $campaign) }}">@csrf<input type="hidden" name="status" value="paused"><button><i class="fa-solid fa-pause"></i> توقف</button></form>
                  @endif
                  @if($campaign->status !== 'test')
                    <form method="POST" action="{{ route('admin.smart-instagram.posts.status', $campaign) }}">@csrf<input type="hidden" name="status" value="test"><button><i class="fa-solid fa-flask"></i> اجرای آزمایشی (بدون ارسال)</button></form>
                  @endif
                  <form method="POST" action="{{ route('admin.smart-instagram.posts.sync-one', $campaign) }}">@csrf<button><i class="fa-solid fa-rotate"></i> به‌روزرسانی آمار</button></form>
                  @if($post->permalink)<a href="{{ $post->permalink }}" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> مشاهده در اینستاگرام</a>@endif
                  <form method="POST" action="{{ route('admin.smart-instagram.posts.destroy', $campaign) }}" data-confirm="سناریوی این پست و قانون اجرایی آن حذف شود؟ این کار برگشت ندارد.">@csrf @method('DELETE')<button class="is-danger"><i class="fa-solid fa-trash"></i> حذف</button></form>
                </div>
              </details>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif

  @if($canManage && $unlinked->isNotEmpty())
    <section class="content-card si-panel" style="margin-top:18px">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-images"></i> پست‌های همگام‌شده‌ی بدون سناریو</div><span class="si-muted">روی پست بزنید تا ثبت شروع شود</span></div>
      <div class="sip-mini-grid" style="margin-top:12px">
        @foreach($unlinked as $p)
          <a href="{{ route('admin.smart-instagram.posts.create', ['post' => $p->id]) }}" class="sip-mini" title="{{ $p->shortCaption(120) }}">
            @if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" loading="lazy" decoding="async">@else<span class="sip-cover-empty"><i class="fa-solid {{ $p->kindIcon() }}"></i></span>@endif
            <span><span><i class="fa-solid {{ $p->kindIcon() }}"></i> {{ $p->kindLabel() }}</span><i class="fa-solid fa-plus"></i></span>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  @if($legacy->isNotEmpty())
    <section class="content-card si-panel is-flush" style="margin-top:18px">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-diagram-project"></i> قوانین پست‌محور قبلی</div><span class="si-muted">از بخش اتومیشن‌ها ساخته شده‌اند و همچنان اجرا می‌شوند</span></div>
      <div class="si-table-wrap" style="margin-top:10px">
        <table class="table-pro">
          <thead><tr><th>نام</th><th>پست</th><th>کلمات</th><th>وضعیت</th><th>اجرا</th><th></th></tr></thead>
          <tbody>
            @foreach($legacy as $rule)
              <tr>
                <td class="si-td-strong">{{ $rule->name }}</td>
                <td class="si-mono si-ltr">{{ \Illuminate\Support\Str::limit($rule->scope_ref, 22) }}</td>
                <td class="si-muted">{{ \Illuminate\Support\Str::limit(implode('، ', (array) $rule->keywords), 40) ?: '—' }}</td>
                <td><span class="badge-pro badge-{{ Ui::statusTone($rule->status) }}">{{ PostCampaignService::STATUSES[$rule->status] ?? $rule->status }}</span></td>
                <td class="si-num">{{ Ui::n($rule->runs_count) }}</td>
                <td>
                  <div style="display:flex;gap:5px;justify-content:flex-end">
                    <a href="{{ route('admin.smart-instagram.automations.show', $rule) }}" class="icon-action-btn" title="جزئیات"><i class="fa-solid fa-eye"></i></a>
                    @if($canManage)
                      <form method="POST" action="{{ route('admin.smart-instagram.posts.import', $rule) }}">@csrf<button class="btn-pro btn-pro-secondary" style="height:30px"><i class="fa-solid fa-arrow-right-to-bracket text-[11px]"></i> انتقال به ثبت پست</button></form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @endif
@endsection
