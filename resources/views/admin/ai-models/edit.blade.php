@extends('layouts.admin')
@section('title', 'ویرایش مدل هوش مصنوعی — وطن استودیو')
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    <div class="flex items-start justify-between gap-3 flex-wrap mb-5"><div><h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">ویرایش مدل: {{ $model->name }}</h1><p class="text-xs text-[var(--text-soft)] m-0">شناسه، version، schema و قیمت مدل را به‌روزرسانی کن.</p></div><a class="btn-pro btn-pro-ghost no-underline" href="{{ route('admin.ai-models.index') }}">بازگشت به لیست</a></div>
    @if($errors->any())<div class="content-card mb-4 border-[var(--danger)]/30 text-[var(--danger)]"><ul class="m-0 pr-5 text-xs list-disc leading-7">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.ai-models.update', $model) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.ai-models._form')<div class="flex justify-end gap-2 mt-5"><button class="btn-pro btn-pro-primary" type="submit"><i class="fa-solid fa-floppy-disk ml-1"></i> ذخیره تغییرات</button></div></form>
  </div>
</main>
@endsection
