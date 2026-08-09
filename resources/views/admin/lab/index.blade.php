@extends('layouts.admin')
@section('title', 'آزمایشگاه محصولات — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content" dir="rtl" style="background:var(--page-bg);">
    @if(session('success'))<div class="content-card p-3 mb-4 text-[12px]" style="color:var(--success);">{{ session('success') }}</div>@endif
    <div class="flex items-center justify-between gap-3 flex-wrap mb-5">
      <div><h1 class="text-xl font-extrabold" style="color:var(--text-h);">آزمایشگاه محصولات</h1><p class="text-[12px] mt-1" style="color:var(--text-soft);">آزمایش ذخیره‌شده‌ی مدل‌ها و مقایسه‌ی خروجی‌ها برای هر محصول</p></div>
      <div class="flex gap-2"><a href="{{ route('admin.lab.reports') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-chart-column"></i> گزارش‌ها</a><a href="{{ route('admin.lab.create') }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-flask"></i> آزمایش جدید</a></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
      @foreach([['محصول آزمایش‌شده',$totalProductsTested,'fa-boxes-stacked','var(--primary)'],['کل آزمایش‌ها',$totalExperiments,'fa-flask','var(--accent)'],['تکمیل‌شده',$completedExperiments,'fa-circle-check','var(--success)'],['خروجی‌ها',$totalOutputs,'fa-images','var(--primary)'],['ناموفق',$failedExperiments,'fa-triangle-exclamation','var(--danger)'],['هزینه کل', '$'.number_format($totalCostUsd, 4),'fa-wallet','var(--warning)']] as $card)
        <div class="content-card p-3"><div class="flex items-center justify-between"><span class="text-[10px]" style="color:var(--text-soft);">{{ $card[0] }}</span><i class="fa-solid {{ $card[2] }}" style="color:{{ $card[3] }};"></i></div><strong class="block mt-2 text-lg" style="color:var(--text-h);">{{ is_numeric($card[1]) ? number_format($card[1]) : $card[1] }}</strong></div>
      @endforeach
    </div>
    <div class="content-card p-3 mb-4 flex items-center justify-between gap-3 flex-wrap">
      <div class="text-[11px]" style="color:var(--text-soft);">نرخ آنلاین دلار: <strong style="color:var(--text-h);">{{ ($exchange['rate'] ?? 0) > 0 ? number_format($exchange['rate'] / 10) . ' تومان' : 'ناموجود' }}</strong><span class="mr-2">({{ $exchange['source'] ?? '—' }})</span></div>
      <div class="text-[10px]" style="color:var(--text-soft);">هزینه‌ی هر آزمایش بر اساس Snapshot نرخ همان زمان ذخیره می‌شود.</div>
    </div>
    <form method="GET" class="content-card p-3 mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">
      <input class="input-pro" name="search" value="{{ request('search') }}" placeholder="جستجوی نام محصول">
      <select class="input-pro" name="product_id"><option value="">همه محصولات</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name_fa }}</option>@endforeach</select>
      <select class="input-pro" name="status"><option value="">همه وضعیت‌ها</option>@foreach(['queued'=>'در صف','processing'=>'در حال اجرا','completed'=>'تکمیل‌شده','failed'=>'ناموفق','cancelled'=>'لغوشده'] as $key=>$label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select>
      <button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-filter"></i> فیلتر</button>
    </form>
    <div class="content-card overflow-hidden">
      <div class="overflow-x-auto"><table class="table-pro"><thead><tr><th>کد گزارش</th><th>محصول</th><th>مدل‌ها</th><th>ارزیاب</th><th>نمره مدیر</th><th>هزینه دلار</th><th>هزینه تومان</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead><tbody>
      @forelse($experiments as $experiment)
        <tr><td><a class="font-bold" style="color:var(--text-h);" href="{{ route('admin.lab.show', $experiment) }}">{{ $experiment->report_code ?: $experiment->title }}</a></td><td>{{ $experiment->product?->name_fa }}<div class="text-[10px]" style="color:var(--text-soft);">{{ $experiment->product?->product_code }}</div></td><td>{{ $experiment->runs_count }}</td><td dir="ltr">{{ $experiment->evaluator_model_id ?: 'gpt-4o-mini' }}</td><td>{{ $experiment->overall_score ? number_format((float)$experiment->overall_score, 1).' از ۱۰' : '—' }}</td><td dir="ltr">{{ $experiment->effectiveCostUsd() > 0 ? '$'.number_format($experiment->effectiveCostUsd(), 4) : '—' }}</td><td>{{ $experiment->effectiveCostToman() > 0 ? number_format($experiment->effectiveCostToman()) : '—' }}</td><td><span class="badge-pro {{ in_array($experiment->status,['completed','evaluated','finalized']) ? 'badge-success' : ($experiment->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">{{ $experiment->status_label }}</span></td><td class="text-[11px]">{{ \App\Support\Jalali::formatNumeric($experiment->created_at) }}</td><td><div class="flex items-center gap-1"><a class="icon-action-btn" title="مشاهده آزمایش" href="{{ route('admin.lab.show', $experiment) }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></a><form method="POST" action="{{ route('admin.lab.duplicate', $experiment) }}">@csrf<button class="icon-action-btn" type="submit" title="تکثیر آزمایش"><i class="fa-solid fa-copy"></i></button></form></div></td></tr>
      @empty<tr><td colspan="10" class="text-center py-12" style="color:var(--text-soft);">هنوز آزمایشی ثبت نشده است.</td></tr>@endforelse
      </tbody></table></div>
      <div class="p-3">{{ $experiments->links() }}</div>
    </div>
  </div>
</main>
@endsection
