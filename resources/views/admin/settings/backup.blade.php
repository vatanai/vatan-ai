@extends('layouts.admin')

@section('title', 'پشتیبان‌گیری — تنظیمات وطن استودیو')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/backup.css') }}?v={{ filemtime(public_path('admin/css/backup.css')) }}">
@endpush

@section('content')
    <main class="backup-page mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
        @include('admin.partials.header')

        <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
            @if(session('success'))
                <div class="backup-alert backup-alert--success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="backup-alert backup-alert--danger"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ session('error') }}</div>
            @endif

            <header class="backup-page-head">
                <div>
                    <span class="backup-eyebrow">تنظیمات / امنیت داده‌ها</span>
                    <h1>پشتیبان‌گیری</h1>
                    <p>نسخه‌ی استاندارد از داده‌های انتخابی، جدول‌های پایگاه‌داده و فایل‌های رسانه‌ای تهیه و مدیریت کن.</p>
                </div>
                <span class="backup-status"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> پشتیبان قابل‌بازیابی</span>
            </header>

            <section class="backup-stats-grid" aria-label="آمار پشتیبان‌گیری">
                <div class="backup-stat-card"><span class="backup-stat-card__icon"><i class="fa-solid fa-box-archive"></i></span><div><small>تعداد پشتیبان‌ها</small><strong>{{ number_format($stats['backups_count']) }}</strong></div></div>
                <div class="backup-stat-card"><span class="backup-stat-card__icon backup-stat-card__icon--success"><i class="fa-solid fa-clock-rotate-left"></i></span><div><small>تاریخ آخرین پشتیبان</small><strong class="backup-stat-card__date">{{ $stats['last_backup'] }}</strong></div></div>
                <div class="backup-stat-card"><span class="backup-stat-card__icon backup-stat-card__icon--warning"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>محصولات پشتیبان‌گرفته‌نشده</small><strong>{{ number_format($stats['products_not_backed_up']) }}</strong></div></div>
                <div class="backup-stat-card"><span class="backup-stat-card__icon backup-stat-card__icon--info"><i class="fa-solid fa-hard-drive"></i></span><div><small>حجم آرشیوها روی سرور</small><strong>{{ $stats['storage_size'] }}</strong></div></div>
            </section>

            <div class="backup-grid">
                <section class="backup-card">
                    <div class="backup-card__head">
                        <div class="backup-card__icon"><i class="fa-solid fa-database" aria-hidden="true"></i></div>
                        <div><h2>ساخت پشتیبان جدید</h2><p>بخش‌هایی را که باید در آرشیو قرار بگیرند انتخاب کن.</p></div>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.backup.create') }}" id="site-backup-form" onsubmit="return false;">
                        @csrf
                        <input type="hidden" name="backup_scope" value="site">
                        <div class="backup-section-list">
                            @foreach($sections as $key => $label)
                                <label class="backup-section-option {{ $key === 'all' ? 'backup-section-option--all' : '' }}">
                                    <input type="checkbox" name="sections[]" value="{{ $key }}" @checked($key === 'all') data-backup-section="{{ $key }}">
                                    <span class="backup-section-option__check"><i class="fa-solid fa-check"></i></span>
                                    <span><b>{{ $label }}</b><small>
                                        @switch($key)
                                            @case('all') همه‌ی داده‌های کاربردی به‌جز کش و صف‌ها @break
                                            @case('users') کاربران، ورودها، گالری و فعالیت‌های مرتبط @break
                                            @case('accounting') سفارش‌ها، خریدها، اعتبارها و تراکنش‌های مالی @break
                                            @case('products') محصولات، دسته‌بندی‌ها، مدل‌ها و تنظیمات تولید @break
                                            @case('settings') تنظیمات، صفحات، محتوا و کمپین‌های سایت @break
                                            @case('activity') گزارش‌ها، رشد، ارجاع‌ها و فعالیت‌های مدیریتی @break
                                            @case('gallery') تنظیمات، پیشنهادها و هزینه‌های گالری خصوصی @break
                                            @case('media') فایل‌های فضای عمومی برای بازیابی تصاویر و خروجی‌ها @break
                                        @endswitch
                                    </small></span>
                                </label>
                            @endforeach
                        </div>
                        <button class="backup-button backup-button--primary" type="button" onclick="openBackupDeliveryPrompt(document.getElementById('site-backup-form'))"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i>ساخت پشتیبان انتخاب‌شده</button>
                        <small class="backup-card__hint">نام فایل بر اساس تاریخ و ساعت همان لحظه ساخته می‌شود؛ دانلود مستقیم پس از ارسال، فایل موقت را از سرور پاک می‌کند.</small>
                    </form>
                </section>

                <section class="backup-card">
                    <div class="backup-card__head">
                        <div class="backup-card__icon backup-card__icon--muted"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></div>
                        <div><h2>بازیابی امن</h2><p>داده‌ها و فایل‌های یک پشتیبان معتبر را برگردان.</p></div>
                    </div>
                    <div class="backup-warning"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>بازیابی داده‌ها به‌صورت همگام‌سازی‌شده انجام می‌شود و داده‌های فعلی را خودکار حذف نمی‌کند. برای بازگردانی کامل روی سرور تازه، ابتدا مهاجرت‌های برنامه را اجرا کن.</span></div>
                    <form class="backup-upload" method="POST" action="{{ route('admin.settings.backup.restore') }}" enctype="multipart/form-data">
                        @csrf
                        <label for="backup-file"><i class="fa-solid fa-file-import" aria-hidden="true"></i><span>فایل زیپ پشتیبان را انتخاب کن</span><small>حداکثر حجم: ۵۱۲ مگابایت</small></label>
                        <input id="backup-file" name="backup" type="file" accept=".zip,application/zip" required>
                        <button class="backup-button backup-button--secondary" type="submit"><i class="fa-solid fa-rotate" aria-hidden="true"></i>شروع بازیابی</button>
                    </form>
                    <small class="backup-card__hint">آرشیوهای استاندارد سایت شامل `manifest`، snapshot جدول‌ها و در صورت دسترسی، فایل `database.sql` هستند.</small>
                </section>
            </div>

            <section class="backup-list backup-card">
                <div class="backup-list__head">
                    <div><span class="backup-eyebrow">مدیریت آرشیوهای ساخته‌شده</span><h2>پشتیبان‌های موجود</h2></div>
                    <span>{{ number_format(count($files)) }} فایل</span>
                </div>
                @forelse($files as $file)
                    <div class="backup-file">
                        <div class="backup-file__icon"><i class="fa-solid {{ $file['format'] === 'پشتیبان سایت' ? 'fa-database' : 'fa-box-archive' }}" aria-hidden="true"></i></div>
                        <div class="backup-file__name"><strong>{{ $file['name'] }}</strong><small>{{ $file['format'] }} · {{ $file['size'] }} · {{ $file['date'] }}</small>@if($file['sections'] !== [])<small>{{ implode('، ', $file['sections']) }}</small>@endif</div>
                        <a class="backup-file__download" href="{{ route('admin.settings.backup.download', ['backup' => $file['name']]) }}"><i class="fa-solid fa-download" aria-hidden="true"></i><span>دانلود</span></a>
                        <form method="POST" action="{{ route('admin.settings.backup.destroy', ['backup' => $file['name']]) }}" onsubmit="return confirm('این فایل از روی سرور حذف شود؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="backup-file__delete" title="حذف از سرور" aria-label="حذف {{ $file['name'] }}"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="backup-empty"><i class="fa-solid fa-box-open" aria-hidden="true"></i><p>هنوز پشتیبانی ساخته نشده است.</p></div>
                @endforelse
            </section>

            <p class="backup-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>برای بازیابی کامل، فایل را خارج از سرور اصلی هم نگه دار. نسخه‌های روی همان سرور در صورت خرابی دیسک یا سرویس، به‌تنهایی کافی نیستند.</p>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    (function () {
        var all = document.querySelector('[data-backup-section="all"]');
        var others = document.querySelectorAll('[data-backup-section]:not([data-backup-section="all"])');
        if (!all) return;
        all.addEventListener('change', function () {
            if (all.checked) others.forEach(function (item) { item.checked = false; });
        });
        others.forEach(function (item) {
            item.addEventListener('change', function () {
                if (item.checked) all.checked = false;
                if (![...others].some(function (other) { return other.checked; }) && !all.checked) all.checked = true;
            });
        });
    })();
</script>
@endpush
