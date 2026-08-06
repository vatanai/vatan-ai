@extends('layouts.admin')
@section('title', 'گزارش‌های آزمایشگاه')
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content" dir="rtl">
    <div class="flex items-center justify-between mb-5"><div><h1 class="text-xl font-extrabold" style="color:var(--text-h);">گزارش‌های آزمایشگاه</h1><p class="text-[12px] mt-1" style="color:var(--text-soft);">خلاصه‌ی هزینه و وضعیت آزمایش‌های ثبت‌شده</p></div><a href="{{ route('admin.lab.index') }}" class="btn-pro btn-pro-ghost">لیست آزمایش‌ها</a></div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">@foreach([['آزمایش‌ها',$totalExperiments,'fa-flask'],['تکمیل‌شده',$completedExperiments,'fa-circle-check'],['اجراهای ناموفق',$failedRuns,'fa-triangle-exclamation'],['هزینه واقعی','$'.number_format($totalCostUsd,4),'fa-coins']] as [$label,$value,$icon])<div class="content-card p-4"><i class="fa-solid {{ $icon }}" style="color:var(--primary);"></i><div class="text-[10px] mt-3" style="color:var(--text-soft);">{{ $label }}</div><div class="text-xl font-bold mt-1" style="color:var(--text-h);" dir="ltr">{{ $value }}</div>@if($label==='هزینه واقعی')<div class="text-[10px] mt-1" style="color:var(--text-soft);">{{ number_format($totalCostUsd * (float)($exchange['rate'] ?? 0) / 10) }} تومان</div>@endif</div>@endforeach</div>
  </div>
</main>
@endsection
