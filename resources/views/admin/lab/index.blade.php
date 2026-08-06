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
      <div class="overflow-x-auto"><table class="table-pro"><thead><tr><th>آزمایش</th><th>محصول</th><th>مدل‌ها</th><th>امتیاز نهایی</th><th>هزینه</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead><tbody>
      @forelse($experiments as $experiment)
        <tr><td><a class="font-bold" style="color:var(--text-h);" href="{{ route('admin.lab.show', $experiment) }}">{{ $experiment->title }}</a><div class="text-[10px]" style="color:var(--text-soft);">{{ $experiment->images_count }} تصویر مرجع</div></td><td>{{ $experiment->product?->name_fa }}</td><td>{{ $experiment->runs_count }} مدل در سه گرید</td><td>@if($experiment->overall_score)<span class="badge-pro badge-success">{{ number_format((float) $experiment->overall_score, 1) }} از ۵</span>@else<span style="color:var(--text-soft);">در انتظار امتیاز</span>@endif</td><td><div dir="ltr">${{ number_format((float)$experiment->estimated_cost_usd, 4) }}</div><div class="text-[10px]" style="color:var(--text-soft);">{{ number_format((float)$experiment->estimated_cost_usd * (float)$experiment->exchange_rate_irr / 10) }} تومان</div></td><td><span class="badge-pro {{ $experiment->status === 'completed' ? 'badge-success' : ($experiment->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">{{ $experiment->status_label }}</span>@if($experiment->applied_at)<div class="text-[9px] mt-1" style="color:var(--success);">اعمال‌شده روی محصول</div>@endif</td><td class="text-[11px]">{{ \App\Support\Jalali::formatNumeric($experiment->created_at) }}</td><td><div class="flex items-center gap-1"><a class="icon-action-btn" title="مشاهده آزمایش" href="{{ route('admin.lab.show', $experiment) }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></a><form method="POST" action="{{ route('admin.lab.duplicate', $experiment) }}">@csrf<button class="icon-action-btn" type="submit" title="تکثیر آزمایش"><i class="fa-solid fa-copy"></i></button></form></div></td></tr>
      @empty<tr><td colspan="8" class="text-center py-12" style="color:var(--text-soft);">هنوز آزمایشی ثبت نشده است.</td></tr>@endforelse
      </tbody></table></div>
      <div class="p-3">{{ $experiments->links() }}</div>
    </div>
  </div>
</main>
@endsection
