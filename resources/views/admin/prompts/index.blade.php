@extends('layouts.admin')

@section('title', 'مدیریت پرامپت‌ها — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content" dir="rtl">
    <div class="flex items-center justify-between gap-4 mb-6">
      <div><h1 class="text-xl font-extrabold" style="color:var(--text-h);">مدیریت پرامپت‌ها</h1><p class="text-xs mt-1" style="color:var(--text-secondary);">سبک‌ها و متن‌های مورد استفاده در تولید تصویر</p></div>
      <a href="{{ route('admin.prompts.create') }}" class="px-4 py-2 rounded-lg text-xs font-bold" style="background:var(--primary);color:var(--text-h);">پرامپت جدید</a>
    </div>
    <div class="overflow-x-auto rounded-xl border" style="border-color:var(--border);background:var(--card-bg);">
      <table class="w-full text-right text-xs">
        <thead><tr class="border-b" style="border-color:var(--border);color:var(--text-secondary);"><th class="p-4">تصویر و نام</th><th class="p-4">پرامپت</th><th class="p-4">وضعیت</th><th class="p-4">عملیات</th></tr></thead>
        <tbody>
        @forelse($prompts as $prompt)
          <tr class="border-b" style="border-color:var(--border);color:var(--text-main);">
            <td class="p-4"><div class="flex items-center gap-3">@if($prompt->image)<img src="{{ asset($prompt->image) }}" alt="" class="w-10 h-10 rounded-lg object-cover">@endif<div><div class="font-bold">{{ $prompt->name }}</div><div class="text-[10px] mt-1" style="color:var(--text-secondary);">{{ $prompt->description ?: 'بدون توضیح' }}</div></div></div></td>
            <td class="p-4 max-w-md"><div class="truncate" dir="ltr">{{ $prompt->prompt }}</div></td>
            <td class="p-4">{{ $prompt->is_active ? 'فعال' : 'غیرفعال' }}</td>
            <td class="p-4"><div class="flex items-center gap-2"><a href="{{ route('admin.prompts.edit', $prompt->id) }}" class="px-3 py-1.5 rounded-lg border" style="border-color:var(--border);">ویرایش</a><form method="POST" action="{{ route('admin.prompts.destroy', $prompt->id) }}" onsubmit="return confirm('این پرامپت حذف شود؟')">@csrf @method('DELETE')<button class="px-3 py-1.5 rounded-lg" style="color:var(--danger);">حذف</button></form></div></td>
          </tr>
        @empty
          <tr><td colspan="4" class="p-10 text-center" style="color:var(--text-secondary);">هنوز پرامپتی ثبت نشده است.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</main>
@endsection
