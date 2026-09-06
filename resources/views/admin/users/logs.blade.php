@extends('admin.layouts.admin')

@section('content')
<div class="min-h-screen w-full bg-[#0a0a0c] text-white font-vazir p-6 md:p-12" dir="rtl">
    <div class="max-w-6xl mx-auto space-y-8">

        <div class="flex items-center justify-between border-b border-white/[0.04] pb-5">
            <div>
                <h1 class="text-lg font-bold text-gray-200">
                    تاریخچه فعالیت: {{ $user->name ?? 'کاربر' }} {{ $user->last_name ?? '' }}
                </h1>
                <p class="text-[11px] text-gray-500 mt-1">از لحظه ثبت‌نام تا آخرین خطا - مشاهده دقیق تایم‌لاین، تصاویر و دیتای JSON</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-gray-300 rounded-xl text-[11px] transition-colors">
                بازگشت به کاربران
            </a>
        </div>

        {{-- 🟢 تب‌های سوییچ بین تایم‌لاین فعالیت و گالری تصاویر --}}
        <div class="flex items-center gap-2 bg-[#121214] border border-white/[0.04] rounded-2xl p-1.5 w-fit">
            <button onclick="switchTab('activity')" id="tab-btn-activity" class="tab-btn px-4 py-2 rounded-xl text-[11px] font-bold transition-colors bg-indigo-600 text-white">
                <i class="fa-solid fa-timeline ml-1.5"></i> تایم‌لاین فعالیت
            </button>
            <button onclick="switchTab('images')" id="tab-btn-images" class="tab-btn px-4 py-2 rounded-xl text-[11px] font-bold transition-colors text-gray-400 hover:text-white">
                <i class="fa-solid fa-images ml-1.5"></i> گالری تصاویر
            </button>
        </div>

        {{-- =========================================== --}}
        {{-- بخش اول: تایم‌لاین کامل فعالیت کاربر --}}
        {{-- =========================================== --}}
        <div id="tab-content-activity" class="tab-content space-y-4">

            @forelse($activities as $activity)
                @php
                    // تعیین رنگ و آیکون بر اساس نوع رویداد
                    $levelStyles = [
                        'success' => ['bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/10'],
                        'error'   => ['bg-rose-500/10', 'text-rose-400', 'border-rose-500/10'],
                        'warning' => ['bg-amber-500/10', 'text-amber-400', 'border-amber-500/10'],
                        'info'    => ['bg-sky-500/10', 'text-sky-400', 'border-sky-500/10'],
                    ];
                    [$bgClass, $textClass, $borderClass] = $levelStyles[$activity->level] ?? $levelStyles['info'];

                    $typeIcons = [
                        'register'                 => 'fa-user-plus',
                        'login'                    => 'fa-right-to-bracket',
                        'login_failed'             => 'fa-triangle-exclamation',
                        'logout'                   => 'fa-right-from-bracket',
                        'otp_sent'                 => 'fa-paper-plane',
                        'otp_verified'             => 'fa-circle-check',
                        'otp_failed'               => 'fa-circle-xmark',
                        'upload'                   => 'fa-cloud-arrow-up',
                        'generate_attempt'         => 'fa-bolt',
                        'generate_success'         => 'fa-wand-magic-sparkles',
                        'generate_failed'          => 'fa-circle-exclamation',
                        'validation_error'         => 'fa-list-check',
                        'unauthenticated_attempt'  => 'fa-lock',
                    ];
                    $icon = $typeIcons[$activity->type] ?? 'fa-circle-info';
                @endphp

                <div class="bg-[#121214] border border-white/[0.04] rounded-2xl p-4 flex items-start gap-4 hover:border-white/10 transition-colors">

                    {{-- آیکون نوع رویداد --}}
                    <div class="w-10 h-10 shrink-0 rounded-xl {{ $bgClass }} {{ $borderClass }} border {{ $textClass }} flex items-center justify-center">
                        <i class="fa-solid {{ $icon }} text-[13px]"></i>
                    </div>

                    <div class="flex-1 space-y-2 min-w-0">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <span class="text-[12px] font-bold text-gray-200">{{ $activity->message }}</span>
                            <div class="flex items-center gap-2">
                                <span class="{{ $bgClass }} {{ $textClass }} text-[9px] font-bold px-2 py-0.5 rounded-full uppercase font-mono">{{ $activity->type }}</span>
                                <span class="text-[9px] text-gray-600 font-mono">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        {{-- متادیتای JSON در صورت وجود --}}
                        @if($activity->meta && count($activity->meta) > 0)
                            <details class="group">
                                <summary class="text-[9px] text-gray-500 cursor-pointer hover:text-gray-300 transition-colors list-none flex items-center gap-1">
                                    <i class="fa-solid fa-chevron-left text-[7px] group-open:rotate-90 transition-transform"></i>
                                    جزئیات بیشتر (JSON)
                                </summary>
                                <div class="bg-black/40 mt-2 p-3 rounded-xl border border-white/[0.03] overflow-x-auto max-h-48 custom-scrollbar" dir="ltr">
                                    <pre class="text-[10px] text-indigo-300 font-mono leading-relaxed select-all">{{ json_encode($activity->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </details>
                        @endif

                        {{-- اطلاعات تکمیلی: آیپی و سشن --}}
                        <div class="flex items-center gap-3 text-[9px] text-gray-600 font-mono pt-1">
                            @if($activity->ip_address)
                                <span><i class="fa-solid fa-location-dot ml-1"></i>{{ $activity->ip_address }}</span>
                            @endif
                            @if($activity->generation_id)
                                <span class="text-indigo-400"><i class="fa-solid fa-link ml-1"></i>Generation #{{ $activity->generation_id }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 text-gray-500 text-[12px] bg-[#121214] rounded-2xl border border-white/[0.04] space-y-2">
                    <i class="fa-solid fa-timeline text-3xl text-gray-700 block"></i>
                    <span>هیچ فعالیتی برای این کاربر ثبت نشده است.</span>
                </div>
            @endforelse

            <div class="pt-2">
                {{ $activities->links() }}
            </div>
        </div>

        {{-- =========================================== --}}
        {{-- بخش دوم: گالری تصاویر تولید شده (قبلاً موجود بود) --}}
        {{-- =========================================== --}}
        <div id="tab-content-images" class="tab-content hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($logs as $log)
                    <div class="bg-[#121214] border border-white/[0.04] rounded-2xl overflow-hidden flex flex-col p-5 space-y-4 shadow-lg relative">

                        <div class="flex items-center justify-between text-[11px] border-b border-white/[0.03] pb-3">
                            <div>
                                <span class="text-gray-500 ml-1">وضعیت:</span>
                                @if($log->status === 'completed')
                                    <span class="bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full font-bold">موفقیت‌آمیز</span>
                                @else
                                    <span class="bg-rose-500/10 text-rose-400 px-2 py-0.5 rounded-full font-bold">خطا / شکست</span>
                                @endif
                            </div>
                            <div class="text-gray-500 font-mono text-[10px]">
                                {{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 aspect-video w-full rounded-xl bg-[#1a1a1d] overflow-hidden border border-white/5 p-1.5">
                            <div class="relative rounded-lg overflow-hidden bg-black/40 flex flex-col items-center justify-center border border-white/[0.02]">
                                @if($log->input_image)
                                    <img src="{{ asset('storage/' . $log->input_image) }}" class="w-full h-full object-cover">
                                    <span class="absolute bottom-1 right-1 bg-black/70 text-[8px] px-1.5 py-0.5 rounded text-gray-400">تصویر ورودی</span>
                                @else
                                    <span class="text-[9px] text-gray-600">فاقد عکس</span>
                                @endif
                            </div>

                            <div class="relative rounded-lg overflow-hidden bg-black/40 flex flex-col items-center justify-center border border-white/[0.02]">
                                @if($log->status === 'completed' && $log->output_image)
                                    <img src="{{ filter_var($log->output_image, FILTER_VALIDATE_URL) ? $log->output_image : asset('storage/' . $log->output_image) }}" class="w-full h-full object-cover">
                                    <span class="absolute bottom-1 right-1 bg-indigo-900/80 text-[8px] px-1.5 py-0.5 rounded text-indigo-300">خروجی AI</span>
                                @else
                                    <span class="text-[9px] text-rose-400/70">بدون خروجی</span>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <span class="text-[10px] font-bold text-gray-500 flex items-center gap-1 font-mono">
                                <i class="fa-solid fa-code text-indigo-400"></i> ساختار دیتای سشن و پردازش (JSON Payload):
                            </span>

                            <div class="bg-black/40 p-3 rounded-xl border border-white/[0.03] overflow-x-auto max-h-40 custom-scrollbar" dir="ltr">
                                <pre class="text-[10px] text-indigo-300 font-mono leading-relaxed select-all">@if(json_decode($log->prompt)){{ json_encode(json_decode($log->prompt), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}@else{
    "text_prompt": "{{ $log->prompt ?? 'N/A' }}",
    "warning": "دیتا در قالب فرمت قدیمی ذخیره شده است."
}@endif</pre>
                            </div>
                        </div>

                        <span class="absolute top-2 left-2 bg-black/60 backdrop-blur-sm text-[9px] font-mono px-1.5 py-0.5 rounded text-gray-500 border border-white/5">
                            ID: #{{ $log->id }}
                        </span>
                    </div>
                @empty
                    <div class="col-span-2 text-center py-16 text-gray-500 text-[12px] bg-[#121214] rounded-2xl border border-white/[0.04]">
                        هیچ تصویر یا لاگ ساختاریافته‌ای برای این کاربر در دیتابیس یافت نشد.
                    </div>
                @endforelse
            </div>

            <div class="pt-4">
                {{ $logs->links() }}
            </div>
        </div>

    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
</style>

<script>
function switchTab(tab) {
    // مدیریت نمایش محتوا
    document.getElementById('tab-content-activity').classList.toggle('hidden', tab !== 'activity');
    document.getElementById('tab-content-images').classList.toggle('hidden', tab !== 'images');

    // مدیریت استایل دکمه‌های تب
    const activeClasses = ['bg-indigo-600', 'text-white'];
    const inactiveClasses = ['text-gray-400'];

    const activityBtn = document.getElementById('tab-btn-activity');
    const imagesBtn = document.getElementById('tab-btn-images');

    if (tab === 'activity') {
        activityBtn.classList.add(...activeClasses);
        activityBtn.classList.remove(...inactiveClasses);
        imagesBtn.classList.remove(...activeClasses);
        imagesBtn.classList.add(...inactiveClasses);
    } else {
        imagesBtn.classList.add(...activeClasses);
        imagesBtn.classList.remove(...inactiveClasses);
        activityBtn.classList.remove(...activeClasses);
        activityBtn.classList.add(...inactiveClasses);
    }
}
</script>
@endsection