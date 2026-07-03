@extends('layouts.app')

@section('content')
<div class="create-page" dir="rtl" style="min-height:70vh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:24px; gap:10px;">
  <h1 style="font-size:20px; font-weight:800; margin:0;">صفحه بساز</h1>
  <p style="font-size:13px; color:rgba(255,255,255,0.5); margin:0; max-width:320px;">
    این صفحه هنوز در حال ساخته‌شدن است. به‌زودی امکان انتخاب سبک و آپلود عکس برای تولید تصویر با هوش مصنوعی اینجا اضافه می‌شود.
  </p>

  @if(!empty($product))
    <div style="margin-top:16px; font-size:12px; color:rgba(255,255,255,0.4);">
      محصول انتخاب‌شده: {{ $product->name_fa ?? $product->name ?? $product->slug }}
    </div>
  @endif
</div>
@endsection
