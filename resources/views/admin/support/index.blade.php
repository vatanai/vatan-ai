@extends('layouts.admin')
@section('title', 'پشتیبانی کاربران — وطن استودیو')
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    <div class="support-admin-head"><div><h1>مرکز پشتیبانی</h1><p>تیکت‌های سایت و گفت‌وگوی کاربران را یکجا مدیریت کنید.</p></div><a href="{{ route('support.index') }}" target="_blank" class="support-admin-link">مشاهده صفحه کاربر <i class="fa-solid fa-arrow-up-left-from-square"></i></a></div>
    <div class="support-admin-stats"><div><span>باز و در انتظار</span><strong>{{ number_format($stats['open']) }}</strong></div><div><span>پاسخ داده‌شده</span><strong>{{ number_format($stats['answered']) }}</strong></div><div><span>بسته‌شده</span><strong>{{ number_format($stats['closed']) }}</strong></div></div>
    <form class="support-admin-filter" method="GET"><input name="search" value="{{ request('search') }}" placeholder="جستجو با شماره، موضوع یا مشخصات کاربر"><select name="status"><option value="">همه وضعیت‌ها</option><option value="open" @selected(request('status')==='open')>باز</option><option value="pending" @selected(request('status')==='pending')>در انتظار پاسخ</option><option value="answered" @selected(request('status')==='answered')>پاسخ داده‌شده</option><option value="closed" @selected(request('status')==='closed')>بسته‌شده</option></select><button type="submit">جستجو</button></form>
    <section class="support-admin-table"><div class="support-admin-table__head"><span>درخواست</span><span>کاربر</span><span>وضعیت</span><span>آخرین پیام</span></div>@forelse($tickets as $ticket)<a class="support-admin-row" href="{{ route('admin.support.show', $ticket) }}"><span><b>{{ $ticket->subject }}</b><small>{{ $ticket->ticket_number }} · {{ $ticket->categoryLabel() }}</small></span><span><b>{{ $ticket->user?->name ?: 'کاربر حذف‌شده' }}</b><small>{{ $ticket->user?->phone ?: $ticket->user?->email ?: '—' }}</small></span><strong class="support-admin-status support-admin-status--{{ $ticket->status }}">{{ $ticket->statusLabel() }}</strong><small>{{ $ticket->last_message_at?->format('Y/m/d H:i') ?: $ticket->created_at?->format('Y/m/d H:i') }}</small></a>@empty<div class="support-admin-empty">تیکتی با این مشخصات پیدا نشد.</div>@endforelse</section>
    <div class="support-admin-pagination">{{ $tickets->links() }}</div>
  </div>
</main>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/support.css') }}?v={{ filemtime(public_path('admin/css/support.css')) }}">@endpush
