@extends('cf::layout')
@php use Vatan\Content\Support\Fa;
  $kinds = ['learning' => ['یادگیری‌های تأییدشده از داده', 'fa-graduation-cap'], 'hypothesis' => ['فرضیه‌های در حال آزمایش', 'fa-vial'],
    'observation' => ['مشاهده‌ها (از ویدیوهای مرجع)', 'fa-binoculars'], 'opportunity' => ['فرصت‌ها و کمبودها', 'fa-lightbulb']];
  $st = ['pending' => ['در انتظار تصمیم', 'is-warning'], 'testing' => ['در حال آزمایش', 'is-info'], 'accepted' => ['پذیرفته', 'is-success'], 'rejected' => ['رد', 'is-danger']]; @endphp

@section('cf-page')
<div class="cf-stack">
  <div class="cf-grid cf-g4">
    <div class="cf-card cf-stat"><span class="cf-stat-label">پست‌های منتشرشده از کارخانه</span><span class="cf-stat-value">{{ Fa::n($published->count()) }}</span><span class="cf-stat-sub">امتیازدار: {{ Fa::n($published->whereNotNull('score_total')->count()) }}</span></div>
    <div class="cf-card cf-stat"><span class="cf-stat-label">ویدیوی مرجع تحلیل‌شده</span><span class="cf-stat-value">{{ Fa::n($library['refs']) }}</span><span class="cf-stat-sub">میانگین {{ Fa::n($library['avgDur'], 1) }} ثانیه · برش هر {{ Fa::n($library['avgShot'], 1) }} ثانیه</span></div>
    <div class="cf-card cf-stat"><span class="cf-stat-label">سبک‌ها و موسیقی‌ها</span><span class="cf-stat-value">{{ Fa::n($library['styles']) }} · {{ Fa::n($library['music']) }}</span><span class="cf-stat-sub">سبک · موسیقی مجاز</span></div>
    <div class="cf-card cf-stat"><span class="cf-stat-label">میانگین ۳۰ پست اخیر پیج</span>
      @if($account)<span class="cf-stat-value">{{ Fa::n($account['likes'], 1) }}</span><span class="cf-stat-sub">لایک · {{ Fa::n($account['comments'], 1) }} کامنت · {{ Fa::n($account['saves'], 1) }} ذخیره · {{ Fa::n($account['reach'], 1) }} دسترسی</span>
      @else<span class="cf-stat-value">—</span><span class="cf-stat-sub">پستی در اینستاگرام هوشمند همگام نشده</span>@endif</div>
  </div>

  <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-ranking-star"></i> رتبه‌بندی اجزا (میانگین امتیاز)</h3>
    @if($published->whereNotNull('score_total')->count() < 3)
      <div class="cf-empty" style="padding:18px"><i class="fa-solid fa-seedling"></i>رتبه‌بندی از سومین پست امتیازدار شروع می‌شود و تا ۵ نمونه برای هر جزء «نامشخص» است. فعلاً فرضیه‌های پایین در حال آزمایش‌اند.</div>
    @else
      <div class="cf-grid cf-g4">@foreach(['style' => 'سبک', 'hook_family' => 'خانواده‌ی هوک', 'pillar' => 'ستون محتوایی', 'music_mood' => 'موسیقی'] as $k => $l)
        <div><div class="cf-section-title">{{ $l }}</div>@foreach($ranks[$k] as $r)<div class="cf-between" style="font-size:12px;padding:4px 0;border-bottom:1px solid var(--divider)"><span>{{ $r['name'] }}</span><span class="cf-num">{{ Fa::n($r['avg'], 1) }} <span class="cf-muted">({{ Fa::n($r['n']) }}{{ $r['n'] < 5 ? ' · نامشخص' : '' }})</span></span></div>@endforeach</div>
      @endforeach</div>
    @endif
  </div>

  @foreach($kinds as $kind => [$title, $icon])
    @if(($insights[$kind] ?? collect())->count())
    <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid {{ $icon }}"></i> {{ $title }}</h3>
      @foreach($insights[$kind] as $in)
        <div style="padding:10px 0;border-bottom:1px solid var(--divider)">
          <div class="cf-between"><div class="cf-row"><b style="color:var(--text-h)">{{ $in->title }}</b><span class="cf-pill">{{ Fa::n($in->sample_size) }} نمونه</span><span class="cf-pill is-info">اطمینان: {{ $in->confidence }}</span>@if($in->scope)<span class="cf-muted" style="font-size:11.5px">{{ $in->scope }}</span>@endif</div>
            <form method="post" action="{{ route('cf.insights.decide', $in) }}" class="cf-row">@csrf<span class="cf-pill {{ $st[$in->status][1] ?? '' }}">{{ $st[$in->status][0] ?? $in->status }}</span>
              @if($in->status !== 'accepted')<button name="status" value="accepted" class="cf-btn cf-btn-sm">پذیرفتن</button>@endif
              @if($in->status !== 'rejected')<button name="status" value="rejected" class="cf-btn cf-btn-sm">رد</button>@endif</form></div>
          <div style="margin-top:6px;line-height:1.9">{{ $in->body }}</div>
          @if($in->suggestion)<div class="cf-muted" style="font-size:12.5px">پیشنهاد: {{ $in->suggestion }}</div>@endif
        </div>
      @endforeach
    </div>
    @endif
  @endforeach

  <div class="cf-card"><h3 class="cf-card-title"><i class="fa-solid fa-stopwatch"></i> آمار زمان‌مند انتشارها</h3>
    @if($published->isEmpty())
      <div class="cf-empty"><i class="fa-solid fa-chart-simple"></i>بعد از اولین «منتشر کردم»، آمار از ۱۰ دقیقه تا ۲۸ روز این‌جا ثبت می‌شود. داده‌ای که از متا نیاید «دریافت‌نشده» نوشته می‌شود، نه صفر.</div>
    @else
      <div class="cf-table-wrap"><table class="cf-table" style="min-width:1000px"><thead><tr><th>پست</th><th>امتیاز</th>@foreach($windows as $w => $m)<th>{{ Fa::digits($w) }}</th>@endforeach</tr></thead><tbody>
        @foreach($published as $p)
          <tr><td><a href="{{ route('cf.productions.show', $p) }}" style="font-weight:700;color:var(--text-h)">{{ $p->title }}</a></td><td class="cf-num">{{ $p->score_total !== null ? Fa::n((float) $p->score_total, 1) : '—' }}</td>
          @foreach($windows as $w => $m)@php $snap = $p->snapshots->firstWhere('window', $w); @endphp
            <td class="cf-num">{{ $snap ? (isset($snap->metrics['reach']) && $snap->metrics['reach'] !== null ? Fa::n($snap->metrics['reach']).' دسترسی' : Fa::n($snap->metrics['likes'] ?? null).' لایک') : '—' }}</td>@endforeach</tr>
        @endforeach</tbody></table></div>
    @endif
  </div>
</div>
@endsection
