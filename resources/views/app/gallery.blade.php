@extends('layouts.app')

@section('content')
<div class="gallery-page" dir="rtl" style="background: #000000; color: #ffffff; padding: 24px; min-height: 100vh; font-family: sans-serif;">
    
    {{-- هدر صفحه --}}
    <div class="gallery-header" style="margin-bottom: 32px; border-bottom: 1px solid #222; padding-bottom: 16px;">
        <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 8px 0; color: #fff;">گالری تصاویر من</h1>
        <p style="color: #888; font-size: 13px; margin: 0;">مدیریت یکپارچه تصاویر خلق‌شده توسط هوش مصنوعی و عکس‌های شخصی آپلود شده</p>
    </div>

    {{-- بخش اول: تصاویر خلق شده هوش مصنوعی --}}
    <div class="section-title" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <span style="color: #0BBF53; font-size: 18px;">✦</span>
        <h2 style="font-size: 16px; font-weight: 700; margin: 0;">تصاویر خلق شده (هوش مصنوعی)</h2>
    </div>
    
    <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 48px;">
        @forelse ($createdImages as $img)
            <div class="image-cell" style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: #151518; border: 1px solid #222;">
                <img src="{{ filter_var($img->image_path, FILTER_VALIDATE_URL) ? $img->image_path : asset('storage/' . ltrim($img->image_path, '/')) }}" 
                     alt="{{ $img->user_prompt ?? 'تصویر خلق شده' }}" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                     loading="lazy">
            </div>
        @empty
            <div style="grid-column: 1 / -1; color: #555; font-size: 12px; padding: 20px 0;">
                هنوز هیچ تصویری با هوش مصنوعی خلق نکرده‌اید.
            </div>
        @endforelse
    </div>

    {{-- بخش دوم: عکس‌های شخصی آپلود شده --}}
    <div class="section-title" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <span style="color: #3b82f6; font-size: 18px;">📁</span>
        <h2 style="font-size: 16px; font-weight: 700; margin: 0;">عکس‌های شخصی آپلود شده</h2>
    </div>

    <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
        @forelse ($personalImages as $img)
            <div class="image-cell" style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: #151518; border: 1px solid #222;">
                <img src="{{ filter_var($img->file_path, FILTER_VALIDATE_URL) ? $img->file_path : asset('storage/' . ltrim($img->file_path, '/')) }}" 
                     alt="تصویر آپلود شده" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                     loading="lazy">
            </div>
        @empty
            <div style="grid-column: 1 / -1; color: #555; font-size: 12px; padding: 20px 0;">
                هنوز هیچ عکسی آپلود نکرده‌اید.
            </div>
        @endforelse
    </div>

</div>
@endsection