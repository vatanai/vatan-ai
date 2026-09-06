@extends('layouts.app')

@section('content')
<div class="gallery-page" dir="rtl" style="background: var(--bg-page); color: var(--text-primary); padding: 24px; min-height: 100vh; font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;">

    {{-- هدر صفحه --}}
    <div class="gallery-header" style="margin-bottom: 32px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 16px;">
        <h1 style="font-size: 22px; font-weight: 700; margin: 0 0 8px 0; color: var(--text-primary);">گالری تصاویر من</h1>
        <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">مدیریت یکپارچه تصاویر خلق‌شده توسط هوش مصنوعی و عکس‌های ورودی برای ساخت</p>
    </div>

    <section style="margin-bottom: 38px; padding: 18px; border: 1px solid var(--border-subtle); border-radius: 14px; background: var(--bg-card);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h2 style="font-size: 16px; font-weight: 700; margin: 0 0 7px 0; color: var(--text-primary);">گالری شخصی وطن</h2>
                <p style="color: var(--text-secondary); font-size: 12px; line-height: 1.8; margin: 0;">با رضایت شما، تصاویر انتخابی تا {{ number_format($galleryConfig->retention_days) }} روز در فضای خصوصی نگهداری می‌شوند. فایل اصلی عمومی نیست و پیش‌نمایش با واترمارک نمایش داده می‌شود.</p>
            </div>
            @if($galleryConfig->enabled)
                <form method="POST" action="{{ route('profile.gallery.consent') }}" style="display:flex;align-items:center;gap:8px;white-space:nowrap;">
                    @csrf
                    <input type="hidden" name="enabled" value="0">
                    <label style="display:flex;align-items:center;gap:8px;color:var(--text-primary);font-size:12px;cursor:pointer;">
                        <input type="checkbox" name="enabled" value="1" @checked($gallerySetting->enabled) onchange="this.form.submit()">
                        ذخیره‌سازی گالری فعال باشد
                    </label>
                </form>
            @else
                <span style="color:var(--text-secondary);font-size:11px;">این قابلیت فعلاً غیرفعال است.</span>
            @endif
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-top:18px;">
            @forelse($galleryItems as $item)
                <article style="overflow:hidden;aspect-ratio:1/1;border-radius:10px;background:var(--bg-page);border:1px solid var(--border-subtle);position:relative;">
                    <a href="{{ route('profile.gallery.preview', $item) }}" target="_blank" rel="noopener"><img src="{{ route('profile.gallery.preview', $item) }}" alt="تصویر گالری شخصی" style="width:100%;height:100%;object-fit:cover;display:block;" loading="lazy"></a>
                    <div style="position:absolute;left:7px;right:7px;bottom:7px;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 8px;border-radius:8px;background:color-mix(in srgb,var(--bg-card) 88%,transparent);font-size:10px;">
                        <span style="color:var(--text-secondary);">انقضا {{ \App\Support\Jalali::formatNumeric($item->expires_at) }}</span>
                        <form method="POST" action="{{ route('profile.gallery.destroy', $item) }}" onsubmit="return confirm('این تصویر حذف شود؟')">@csrf @method('DELETE')<button type="submit" style="color:var(--danger);background:none;border:0;cursor:pointer;" title="حذف"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                </article>
            @empty
                <p style="grid-column:1/-1;color:var(--text-secondary);font-size:12px;margin:0;">هنوز تصویری با رضایت شما در گالری شخصی ذخیره نشده است.</p>
            @endforelse
        </div>
    </section>

    @if(isset($galleryPreference))
        <section style="margin-bottom: 38px; padding: 18px; border: 1px solid var(--border-subtle); border-radius: 14px; background: var(--bg-card);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                <div>
                    <h2 style="font-size: 16px; font-weight: 700; margin: 0 0 7px 0; color: var(--text-primary);">پیشنهادهای شخصی و مناسبتی</h2>
                    <p style="color: var(--text-secondary); font-size: 12px; line-height: 1.8; margin: 0;">پیشنهادها قبل از ساخت تصویر فقط به‌صورت ایده نمایش داده می‌شوند و بدون انتخاب شما اعلان یا هزینه‌ای ایجاد نمی‌کنند.</p>
                </div>
                @if($galleryConfig->enabled && ($galleryConfig->suggestions_enabled ?? true))
                    <form method="POST" action="{{ route('profile.gallery.preferences') }}" style="display:grid;gap:8px;min-width:230px;">
                        @csrf
                        <input type="hidden" name="suggestions_enabled" value="0">
                        <input type="hidden" name="marketing_enabled" value="0">
                        <input type="hidden" name="occasion_enabled" value="0">
                        <input type="hidden" name="reminders_enabled" value="0">
                        <label style="display:flex;align-items:center;gap:8px;color:var(--text-primary);font-size:12px;cursor:pointer;"><input type="checkbox" name="suggestions_enabled" value="1" @checked($galleryPreference->suggestions_enabled)> پیشنهادهای شخصی فعال باشد</label>
                        <label style="display:flex;align-items:center;gap:8px;color:var(--text-primary);font-size:12px;cursor:pointer;"><input type="checkbox" name="occasion_enabled" value="1" @checked($galleryPreference->occasion_enabled)> پیشنهادهای مناسبتی</label>
                        <label style="display:flex;align-items:center;gap:8px;color:var(--text-primary);font-size:12px;cursor:pointer;"><input type="checkbox" name="marketing_enabled" value="1" @checked($galleryPreference->marketing_enabled)> پیشنهاد محصولات و سبک‌های جدید</label>
                        <label style="display:flex;align-items:center;gap:8px;color:var(--text-primary);font-size:12px;cursor:pointer;"><input type="checkbox" name="reminders_enabled" value="1" @checked($galleryPreference->reminders_enabled)> یادآوری پیش از حذف تصویر</label>
                        <button type="submit" class="text-white" style="justify-self:start;padding:7px 11px;border:1px solid var(--border-subtle);border-radius:8px;background:var(--primary);cursor:pointer;font-size:11px;">ذخیره تنظیمات پیشنهادها</button>
                    </form>
                @else
                    <span style="color:var(--text-secondary);font-size:11px;">پیشنهادهای شخصی فعلاً از داشبورد غیرفعال است.</span>
                @endif
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;margin-top:18px;">
                @forelse($suggestions as $suggestion)
                    <article style="display:flex;gap:10px;align-items:center;padding:10px;border:1px solid var(--border-subtle);border-radius:10px;background:var(--bg-page);">
                        @if($suggestion->galleryItem)
                            <img src="{{ route('profile.gallery.preview', $suggestion->galleryItem) }}" alt="پیش‌نمایش پیشنهاد" style="width:58px;height:58px;object-fit:cover;border-radius:8px;">
                        @endif
                        <div style="min-width:0;flex:1;">
                            <strong style="display:block;color:var(--text-primary);font-size:11px;">{{ $suggestion->title }}</strong>
                            <span style="display:block;color:var(--text-secondary);font-size:10px;line-height:1.7;margin-top:3px;">{{ $suggestion->product?->name_fa ?: $suggestion->product?->name_en ?: 'محصول پیشنهادی' }}</span>
                            <div style="display:flex;align-items:center;gap:8px;margin-top:7px;">
                                @if($suggestion->product && $suggestion->galleryItem)
                                    <a href="{{ route('profile.gallery.suggestion.recreate', $suggestion) }}" style="color:var(--primary);font-size:10px;text-decoration:none;">انتخاب و بازآفرینی</a>
                                @endif
                                <form method="POST" action="{{ route('profile.gallery.suggestion.dismiss', $suggestion) }}">@csrf<button type="submit" style="border:0;background:none;color:var(--text-secondary);font-size:10px;cursor:pointer;">حذف پیشنهاد</button></form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p style="grid-column:1/-1;color:var(--text-secondary);font-size:12px;margin:0;">پیشنهاد فعالی ندارید؛ با فعال‌کردن پیشنهادها، ایده‌های مناسب تصویرهای شما اینجا نمایش داده می‌شود.</p>
                @endforelse
            </div>
            @if(($galleryConfig->free_recreations_per_month ?? 0) > 0)
                <p style="color:var(--text-secondary);font-size:11px;margin:14px 0 0;">سهم بازآفرینی رایگان این ماه: {{ number_format($freeRecreationsRemaining ?? 0) }} از {{ number_format($galleryConfig->free_recreations_per_month) }} ساخت</p>
            @endif
        </section>
    @endif

    <form method="GET" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
        <span style="color:var(--text-secondary);font-size:11px;">فیلتر گالری:</span>
        <select name="type" style="padding:7px 10px;border:1px solid var(--border-subtle);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:11px;">
            <option value="">همه منابع</option>
            <option value="upload" @selected(request('type') === 'upload')>آپلودشده</option>
            <option value="generated" @selected(request('type') === 'generated')>ساخته‌شده</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" style="padding:7px 10px;border:1px solid var(--border-subtle);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:11px;">
        <input type="date" name="to" value="{{ request('to') }}" style="padding:7px 10px;border:1px solid var(--border-subtle);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-size:11px;">
        <button type="submit" style="padding:7px 11px;border:1px solid var(--border-subtle);border-radius:8px;background:var(--bg-card);color:var(--text-primary);cursor:pointer;font-size:11px;">اعمال فیلتر</button>
    </form>

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

</div>
@endsection
