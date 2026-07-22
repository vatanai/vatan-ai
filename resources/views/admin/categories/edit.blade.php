@extends('layouts.admin')
@section('title', 'ویرایش دسته‌بندی — وطن استودیو')
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    @include('admin.categories.partials.form', ['category' => $category])
  </div>
</main>
@endsection
