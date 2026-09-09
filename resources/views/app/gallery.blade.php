@extends('layouts.app')

@section('content')
<div class="gallery-page" dir="rtl" style="background: var(--bg-page); color: var(--text-primary); padding: 24px; min-height: 100vh; font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;">

    {{-- هدر صفحه --}}
    <div class="gallery-header" style="margin-bottom: 32px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 16px;">
        <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 8px 0; color: var(--text-primary);">گالری من</h1>
        <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">مدیریت یکپارچه خروجی‌ها و ورودی‌های متنی، تصویری و ویدیویی ساخت</p>
        @if ($galleryConfig->enabled)
            <form method="POST" action="{{ route('profile.gallery.consent') }}" style="margin-top: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 12px 14px; border: 1px solid var(--border-subtle); border-radius: 12px; background: var(--bg-card);">
                @csrf
                <span style="color: var(--text-secondary); font-size: 12px;">{{ $gallerySetting->enabled ? 'ورودی‌های هر آزمایش در گالری خصوصی شما ذخیره می‌شوند.' : 'برای نگهداری ورودی‌های هر آزمایش، گالری خصوصی را فعال کنید.' }}</span>
                <input type="hidden" name="enabled" value="{{ $gallerySetting->enabled ? 0 : 1 }}">
                <button type="submit" style="border: 0; border-radius: 9px; padding: 8px 14px; background: var(--primary); color: #fff; font-family: inherit; cursor: pointer; font-size: 12px;">{{ $gallerySetting->enabled ? 'غیرفعال‌کردن ذخیره‌سازی' : 'فعال‌کردن گالری خصوصی' }}</button>
            </form>
        @endif
    </div>

    {{-- بخش اول: تصاویر خلق شده هوش مصنوعی --}}
    <div class="section-title" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <span style="color: #cffe00; font-size: 18px;">✦</span>
        <h2 style="font-size: 16px; font-weight: 700; margin: 0;">تصاویر خلق شده (هوش مصنوعی)</h2>
    </div>
    
    <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 48px;">
        @forelse ($createdImages as $img)
            <div class="image-cell" style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: var(--bg-card); border: 1px solid var(--border-subtle);">
                <img src="{{ filter_var($img->image_path, FILTER_VALIDATE_URL) ? $img->image_path : asset('storage/' . ltrim($img->image_path, '/')) }}" 
                     alt="{{ $img->user_prompt ?? 'تصویر خلق شده' }}" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                     loading="lazy">
            </div>
        @empty
            <div style="grid-column: 1 / -1; color: var(--text-secondary); font-size: 12px; padding: 20px 0;">
                هنوز هیچ تصویری با هوش مصنوعی خلق نکرده‌اید.
            </div>
        @endforelse
    </div>

    {{-- بخش دوم: عکس‌های ورودی برای ساخت --}}
    <div class="section-title" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <span style="color: #3b82f6; font-size: 18px;">📁</span>
        <h2 style="font-size: 16px; font-weight: 700; margin: 0;">عکس‌های ورودی برای ساخت</h2>
    </div>

    <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
        @forelse ($personalImages as $img)
            <div class="image-cell" style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: var(--bg-card); border: 1px solid var(--border-subtle);">
                <img src="{{ filter_var($img->file_path, FILTER_VALIDATE_URL) ? $img->file_path : asset('storage/' . ltrim($img->file_path, '/')) }}" 
                     alt="تصویر آپلود شده" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                     loading="lazy">
            </div>
        @empty
            <div style="grid-column: 1 / -1; color: var(--text-secondary); font-size: 12px; padding: 20px 0;">
                هنوز هیچ عکس ورودی برای ساخت وارد نکرده‌اید.
            </div>
        @endforelse
    </div>

    {{-- بخش سوم: ورودی‌های ثبت‌شدهٔ هر آزمایش --}}
    <div class="section-title" style="margin: 48px 0 16px; display: flex; align-items: center; gap: 8px;">
        <span style="color: #cffe00; font-size: 18px;">✦</span>
        <h2 style="font-size: 16px; font-weight: 700; margin: 0;">ورودی‌های ثبت‌شدهٔ ساخت</h2>
    </div>
    <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
        @forelse ($galleryItems as $item)
            @php($mime = strtolower((string) $item->mime_type))
            @if (str_starts_with($mime, 'image/'))
                <a href="{{ route('profile.gallery.original', $item) }}" target="_blank" rel="noopener" class="image-cell" style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: var(--bg-card); border: 1px solid var(--border-subtle); display: block;">
                    <img src="{{ route('profile.gallery.preview', $item) }}" alt="عکس ورودی ساخت" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
                </a>
            @elseif (str_starts_with($mime, 'video/'))
                <div class="image-cell" style="aspect-ratio: 1/1; border-radius: 10px; overflow: hidden; background: var(--bg-card); border: 1px solid var(--border-subtle);">
                    <video src="{{ route('profile.gallery.original', $item) }}" controls preload="metadata" style="width: 100%; height: 100%; object-fit: contain; display: block;"></video>
                </div>
            @else
                <div class="image-cell" style="min-height: 140px; border-radius: 10px; overflow: hidden; background: var(--bg-card); border: 1px solid var(--border-subtle); padding: 14px; color: var(--text-primary); font-size: 12px; line-height: 2; white-space: pre-wrap;">{{ $item->display_text ?? data_get($item->metadata, 'text', 'متن ورودی') }}</div>
            @endif
        @empty
            <div style="grid-column: 1 / -1; color: var(--text-secondary); font-size: 12px; padding: 20px 0;">
                هنوز ورودی‌ای از آزمایش‌های شما در گالری ثبت نشده است.
            </div>
        @endforelse
    </div>

</div>
@endsection
