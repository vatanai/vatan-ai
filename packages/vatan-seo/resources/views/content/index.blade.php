@extends('seo::layout')
@php
  use Vatan\Seo\Support\Fa;
  use Vatan\Seo\Models\ContentItem;
  $seoTitle = 'تولید محتوا';
  $seoIcon = 'fa-feather-pointed';
  $seoHelp = 'content.page';
  $seoSubtitle = 'خط تولید مقاله‌ی یونیک: پژوهش نتایج گوگل ← بریف ← نگارش ← ممیزی کیفیت ← تأیید شما (پنل یا تلگرام) ← انتشار خودکار در سایت.';
  $tones = ['published' => 'success', 'review' => 'warning', 'approved' => 'info', 'failed' => 'danger', 'rejected' => 'danger', 'drafting' => 'info', 'brief' => 'info', 'idea' => ''];
@endphp

@section('seo-page')
<div class="seo-grid seo-grid-main" style="align-items:start">
  <div class="seo-stack">
    <div class="seo-chips">
      <a href="{{ route('seo.content.index') }}" class="seo-chip {{ ! $status ? 'is-active' : '' }}">همه</a>
      @foreach(['review', 'brief', 'published', 'idea', 'rejected', 'failed'] as $st)
        <a href="{{ route('seo.content.index', ['status' => $st]) }}" class="seo-chip {{ $status === $st ? 'is-active' : '' }}">{{ ContentItem::STATUSES[$st] }} <span class="n">{{ Fa::n($counts[$st] ?? 0) }}</span></a>
      @endforeach
    </div>
    <section class="seo-card is-flush">
      @if($items->isEmpty())
        <div class="seo-empty"><i class="fa-solid fa-feather-pointed"></i><b>هنوز محتوایی ساخته نشده</b>از کادر کناری یک کلمه‌ی هدف انتخاب کنید یا منتظر اجرای خودکار «خط تولید محتوا» بمانید.</div>
      @else
        <div class="seo-table-wrap"><table class="seo-table">
          <thead><tr><th>عنوان</th><th class="hide-sm">کلمه</th><th>وضعیت</th><th class="hide-sm">امتیاز سئو @include('seo::partials.help', ['k' => 'content.score'])</th><th class="hide-lg">کیفیت</th><th class="hide-md">طول</th><th class="hide-lg">هزینه</th><th class="hide-sm">تاریخ</th></tr></thead>
          <tbody>
            @foreach($items as $item)
              <tr>
                <td class="kw"><a href="{{ route('seo.content.show', $item) }}">{{ \Illuminate\Support\Str::limit($item->title ?: 'بدون عنوان', 70) }}</a><small>{{ $item->type === 'refresh' ? 'به‌روزرسانی محتوای موجود' : ($item->published_url ? urldecode(parse_url($item->published_url, PHP_URL_PATH)) : '') }}</small></td>
                <td class="hide-sm">{{ $item->keyword?->keyword ?? '—' }}</td>
                <td><span class="seo-tag is-{{ $tones[$item->status] ?? '' }}">{{ ContentItem::STATUSES[$item->status] ?? $item->status }}</span></td>
                <td class="seo-num hide-sm">{{ $item->seo_score !== null ? Fa::n($item->seo_score) : '—' }}</td>
                <td class="seo-num hide-lg">{{ isset($item->quality['ai']['quality']) ? Fa::n($item->quality['ai']['quality']) : '—' }}</td>
                <td class="seo-num hide-md">{{ $item->word_count ? Fa::n($item->word_count).' کلمه' : '—' }}</td>
                <td class="seo-num hide-lg">{{ Fa::usd($item->cost_usd) }}</td>
                <td class="seo-muted hide-sm">{{ Fa::ago($item->created_at) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table></div>
      @endif
    </section>
  </div>

  <div class="seo-stack">
    <section class="seo-card">
      <div class="seo-card-head"><div class="seo-card-title"><i class="fa-solid fa-feather"></i> نوشتن مقاله‌ی جدید</div></div>
      <div class="seo-row-gap" style="justify-content:space-between;margin-bottom:8px"><span class="seo-muted" style="font-size:12px">سهمیه‌ی این ماه @include('seo::partials.help', ['k' => 'content.quota'])</span><b class="seo-num">{{ Fa::n($quota['used']) }} / {{ Fa::n($quota['articles']) }}</b></div>
      <div class="seo-meter" style="margin-bottom:14px"><span style="width:{{ $quota['articles'] ? min(100, max(2, $quota['used'] / $quota['articles'] * 100)) : 100 }}%"></span></div>
      @if($available->isEmpty())
        <div class="seo-card-sub">همه‌ی کلمات هدف محتوا دارند یا هنوز کلمه‌ی هدفی انتخاب نشده. <a class="seo-link" href="{{ route('seo.keywords.index') }}">کلمات کلیدی</a></div>
      @else
        <form method="POST" action="{{ route('seo.content.generate') }}" class="seo-stack" style="gap:10px">@csrf
          <div class="seo-field"><label>کلمه‌ی هدف</label><select name="keyword_id" class="seo-select">@foreach($available as $kw)<option value="{{ $kw->id }}">{{ $kw->keyword }}{{ $kw->current_position ? ' — رتبه '.Fa::n($kw->current_position, 1) : '' }}</option>@endforeach</select></div>
          <div class="seo-field"><label>مرحله</label><select name="mode" class="seo-select"><option value="full">بریف + نگارش کامل</option><option value="brief">فقط بریف (ارزان‌تر)</option></select></div>
          <button class="btn-pro btn-pro-primary" style="justify-content:center" data-loading="در حال پژوهش و نگارش… (۱ تا ۳ دقیقه)"><i class="fa-solid fa-wand-magic-sparkles"></i> شروع</button>
        </form>
      @endif
    </section>
    <section class="seo-card">
      <div class="seo-card-title" style="margin-bottom:10px"><i class="fa-solid fa-route"></i> مسیر هر مقاله</div>
      <div class="seo-stack" style="gap:8px;font-size:12px;line-height:1.9">
        <span><span class="seo-tag is-info">۱</span> پژوهش زنده‌ی ۱۰ نتیجه‌ی اول گوگل (Grok)</span>
        <span><span class="seo-tag is-info">۲</span> بریف: زاویه‌ی یونیک، سرفصل‌ها، سؤالات کاربران، لینک داخلی</span>
        <span><span class="seo-tag is-info">۳</span> نگارش فارسی انسانی با مدل نویسنده</span>
        <span><span class="seo-tag is-info">۴</span> ممیزی: ۱۰ معیار سئو + کیفیت، E-E-A-T و متن ماشینی</span>
        <span><span class="seo-tag is-warning">۵</span> تأیید شما (پنل یا یک دکمه در تلگرام)</span>
        <span><span class="seo-tag is-success">۶</span> انتشار در سایت + اعلام فوری به موتورها (IndexNow)</span>
      </div>
    </section>
  </div>
</div>
@endsection
