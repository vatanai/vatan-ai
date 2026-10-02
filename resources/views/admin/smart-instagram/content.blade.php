@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'محتوا و فراخوان‌ها';
  $siSubtitle = 'کدام پست، ریلز، استوری یا تبلیغ گفتگو و فروش ساخت — نه فقط بازدید — و کدام کلمه‌ی کلیدی به کدام سناریو وصل است.';
@endphp

@section('si-actions')
  <div class="si-chips">@foreach([7 => '۷ روز', 30 => '۳۰ روز', 90 => '۹۰ روز'] as $d => $l)<a href="{{ route('admin.smart-instagram.content', ['days' => $d]) }}" class="chip-filter {{ $days === $d ? 'active' : '' }}">{{ $l }}</a>@endforeach</div>
@endsection

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-photo-film', 'tone' => 'primary', 'value' => Ui::n($totals['items']), 'label' => 'محتوای دارای تعامل'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-comment', 'tone' => 'info', 'value' => Ui::n($totals['interactions']), 'label' => 'کامنت/پاسخ/ورودی'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-handshake', 'tone' => 'warning', 'value' => Ui::n($totals['deals']), 'label' => 'فرصت فروش منتسب'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-sack-dollar', 'tone' => 'success', 'value' => Ui::money($totals['value']), 'label' => 'فروش منتسب (تومان)'])
  </div>

  <section class="content-card si-panel is-flush" style="margin-bottom:14px">
    <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-brands fa-instagram"></i> پست‌های متصل از اینستاگرام</div><div class="si-panel-sub">یک پست یا ریلز را انتخاب کنید تا سناریوی کامنت و دایرکت فقط روی همان محتوای مشخص ساخته شود.</div></div></div>
    <div class="si-table-wrap" style="margin-top:10px">
      <table class="table-pro">
        <thead><tr><th>محتوا</th><th>نوع</th><th>تاریخ</th><th>شناسه‌ی پست</th><th></th></tr></thead>
        <tbody>
          @forelse($composioMedia as $media)
            <tr>
              <td><span class="si-td-strong">{{ \Illuminate\Support\Str::limit($media['caption'] ?? 'بدون کپشن', 90) }}</span></td>
              <td>{{ $media['media_type'] ?? '—' }}</td>
              <td class="si-muted">{{ $media['timestamp'] ?? '—' }}</td>
              <td class="si-mono">{{ $media['id'] ?? '—' }}</td>
              <td><a href="{{ route('admin.smart-instagram.automations.create', ['template' => 'price_comment', 'scope' => $media['id'] ?? '']) }}" class="btn-pro btn-pro-ghost" style="height:30px">ساخت سناریو برای این پست</a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="si-table-empty">پستی از `Composio` دریافت نشده است؛ ابتدا اتصال‌ها را هم‌گام کنید.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="content-card si-panel is-flush" style="margin-bottom:14px">
    <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-ranking-star"></i> عملکرد محتوا تا فروش</div><div class="si-panel-sub">بر اساس شناسه‌ی محتوایی که مشتری از آن وارد شد</div></div></div>
    <div class="si-table-wrap" style="margin-top:10px">
      <table class="table-pro">
        <thead><tr><th>شناسه‌ی محتوا</th><th>نوع</th><th>تعامل</th><th>گفتگو</th><th>فرصت</th><th>برنده</th><th>فروش (تومان)</th><th>سناریوی متصل</th><th>آخرین تعامل</th><th></th></tr></thead>
        <tbody>
          @forelse($performance as $row)
            @php($linked = $rulesByScope->get($row['ref'], collect()))
            <tr>
              <td class="si-mono" title="{{ $row['ref'] }}">{{ \Illuminate\Support\Str::limit($row['ref'], 22) }}</td>
              <td><span class="si-source">{{ Ui::label('source', $row['type']) }}</span></td>
              <td class="si-num">{{ Ui::n($row['interactions']) }}</td>
              <td class="si-num">{{ Ui::n($row['conversations']) }}</td>
              <td class="si-num">{{ Ui::n($row['deals']) }}</td>
              <td class="si-num">{{ Ui::n($row['won']) }}</td>
              <td class="si-num si-td-strong">{{ Ui::money($row['value']) }}</td>
              <td>@forelse($linked as $rule)<a href="{{ route('admin.smart-instagram.automations.show', $rule) }}" class="badge-pro badge-{{ Ui::statusTone($rule->status) }}" style="margin:1px">{{ \Illuminate\Support\Str::limit($rule->name, 18) }}</a>@empty<span class="si-muted">عمومی</span>@endforelse</td>
              <td class="si-muted">{{ Ui::ago($row['last_at']) }}</td>
              <td><a href="{{ route('admin.smart-instagram.automations.create', array_filter(['template' => $row['type'] === 'comment' ? 'price_comment' : null, 'scope' => $row['ref']])) }}" class="icon-action-btn" title="سناریوی اختصاصی برای این محتوا"><i class="fa-solid fa-plus"></i></a></td>
            </tr>
          @empty
            <tr><td colspan="10" class="si-table-empty">در این بازه تعاملی ثبت نشده است.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <div class="si-grid-even">
    <section class="content-card si-panel">
      <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-key"></i> فراخوان‌های عمومی فعال</div><div class="si-panel-sub">کلمات کلیدی که روی همه‌ی محتواها کار می‌کنند</div></div><a href="{{ route('admin.smart-instagram.automations.index') }}" class="si-link">اتومیشن‌ها <i class="fa-solid fa-angle-left"></i></a></div>
      @forelse($globalRules as $rule)
        <a href="{{ route('admin.smart-instagram.automations.show', $rule) }}" class="si-row">
          <span class="si-dot is-{{ Ui::statusTone($rule->status) }}"></span>
          <div class="si-row-main"><div class="si-row-title">{{ $rule->name }}</div><div class="si-row-sub">{{ implode('، ', (array) $rule->keywords) }}</div></div>
          <span class="badge-pro badge-neutral">{{ $rule->trigger === 'comment_keyword' ? 'کامنت' : 'دایرکت' }}</span>
        </a>
      @empty
        <div class="si-table-empty">فراخوان فعالی نیست. <a class="si-link" href="{{ route('admin.smart-instagram.automations.create', ['template' => 'price_comment']) }}">سناریوی «قیمت» بسازید</a></div>
      @endforelse
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head"><div><div class="si-panel-title"><i class="fa-solid fa-calendar-days"></i> محتوای برنامه‌ریزی‌شده</div><div class="si-panel-sub">از «تکنولوژی مارکتینگ › تقویم و صف محتوا» (فقط نمایش)</div></div>
        @if(\Illuminate\Support\Facades\Route::has('admin.marketing-technology.content-calendar'))<a href="{{ route('admin.marketing-technology.content-calendar') }}" class="si-link">تقویم <i class="fa-solid fa-angle-left"></i></a>@endif
      </div>
      @forelse($planned as $item)
        <div class="si-row">
          <span class="si-dot is-{{ Ui::statusTone($item->status === 'published' ? 'success' : $item->status) }}"></span>
          <div class="si-row-main"><div class="si-row-title">{{ $item->title }}</div><div class="si-row-sub">{{ $item->content_type }} · کلمه‌ی کلیدی: {{ $item->keyword ?: '—' }} @if($item->external_id)· <span class="si-mono">{{ \Illuminate\Support\Str::limit($item->external_id, 16) }}</span>@endif</div></div>
          <span class="si-muted">{{ Ui::date($item->published_at ?? $item->publish_at) }}</span>
        </div>
      @empty
        <div class="si-table-empty">محتوای برنامه‌ریزی‌شده‌ای ثبت نشده.</div>
      @endforelse
      <p class="si-help" style="margin-top:10px">انتشار خودکار محتوا طبق پروپوزال در فاز اول غیرفعال است و فقط با تأیید انسان انجام می‌شود (بزودی).</p>
    </section>
  </div>
@endsection
