@extends('admin.layouts.admin')

@section('content')
<div class="min-h-screen w-full bg-[#0a0a0c] text-white font-vazir p-4 md:p-10" dir="rtl">
    <div class="max-w-6xl mx-auto space-y-6">

        {{-- هدر صفحه --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-white/[0.04] pb-5 gap-4">
            <div>
                <h1 class="text-xl font-black text-gray-200 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    مانیتورینگ زنده تمام رویدادهای سیستم
                </h1>
                <p class="text-[11px] text-gray-500 mt-1">ثبت‌نام، ورود، خروج، آپلود، جنریت و خطاها - برای تمام کاربران به ترتیب زمانی</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.all_logs') }}" class="px-3.5 py-2 bg-white/5 hover:bg-white/10 text-gray-300 rounded-xl text-[11px] border border-white/5 transition-colors">
                    <i class="fa-solid fa-images ml-1"></i> لاگ تصاویر
                </a>
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 bg-white/5 hover:bg-white/10 text-gray-300 rounded-xl text-[11px] border border-white/5 transition-colors">
                    مدیریت کاربران
                </a>
            </div>
        </div>

        {{-- باکس آمار سریع --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">کل رویدادهای ثبت شده</span>
                <span class="text-xl font-bold font-mono text-gray-200 mt-1 block">{{ $activities->total() }}</span>
            </div>
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">صفحه فعلی</span>
                <span class="text-xl font-bold font-mono text-indigo-400 mt-1 block">{{ $activities->currentPage() }}</span>
            </div>
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">رویداد در این صفحه</span>
                <span class="text-xl font-bold font-mono text-emerald-400 mt-1 block">{{ $activities->count() }}</span>
            </div>
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">کل صفحات</span>
                <span class="text-xl font-bold font-mono text-amber-400 mt-1 block">{{ $activities->lastPage() }}</span>
            </div>
        </div>

        {{-- فیلتر سریع بر اساس سطح (فقط نمایشی - کلاینت ساید) --}}
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="filterLevel('all')" class="filter-btn active px-3 py-1.5 rounded-full text-[10px] font-bold border border-white/10 bg-white/5 text-gray-300" data-level="all">
                همه
            </button>
            <button onclick="filterLevel('success')" class="filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold border border-emerald-500/10 bg-emerald-500/5 text-emerald-400" data-level="success">
                موفق
            </button>
            <button onclick="filterLevel('warning')" class="filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold border border-amber-500/10 bg-amber-500/5 text-amber-400" data-level="warning">
                هشدار
            </button>
            <button onclick="filterLevel('error')" class="filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold border border-rose-500/10 bg-rose-500/5 text-rose-400" data-level="error">
                خطا
            </button>
            <button onclick="filterLevel('info')" class="filter-btn px-3 py-1.5 rounded-full text-[10px] font-bold border border-sky-500/10 bg-sky-500/5 text-sky-400" data-level="info">
                اطلاعات
            </button>
        </div>

        {{-- لیست رویدادها --}}
        <div class="space-y-3" id="activities-list">
            @forelse($activities as $activity)
                @php
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

                <div class="activity-item bg-[#121214] border border-white/[0.04] rounded-2xl p-4 flex items-start gap-4 hover:border-white/10 transition-colors" data-level="{{ $activity->level }}">

                    <div class="w-10 h-10 shrink-0 rounded-xl {{ $bgClass }} {{ $borderClass }} border {{ $textClass }} flex items-center justify-center">
                        <i class="fa-solid {{ $icon }} text-[13px]"></i>
                    </div>

                    <div class="flex-1 space-y-2 min-w-0">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- اطلاعات کاربر مربوطه --}}
                                @if($activity->user)
                                    <span class="text-[11px] font-bold text-gray-200">
                                        {{ $activity->user->name ?? 'کاربر' }} {{ $activity->user->last_name ?? '' }}
                                    </span>
                                @else
                                    <span class="text-[11px] font-bold text-gray-500">کاربر مهمان / ناشناس</span>
                                @endif
                                <span class="text-gray-600">—</span>
                                <span class="text-[12px] text-gray-300">{{ $activity->message }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="{{ $bgClass }} {{ $textClass }} text-[9px] font-bold px-2 py-0.5 rounded-full uppercase font-mono">{{ $activity->type }}</span>
                                <span class="text-[9px] text-gray-600 font-mono">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

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

                        <div class="flex items-center gap-3 text-[9px] text-gray-600 font-mono pt-1 flex-wrap">
                            @if($activity->ip_address)
                                <span><i class="fa-solid fa-location-dot ml-1"></i>{{ $activity->ip_address }}</span>
                            @endif
                            @if($activity->prompt_id)
                                <span><i class="fa-solid fa-image ml-1"></i>Prompt #{{ $activity->prompt_id }}</span>
                            @endif
                            @if($activity->generation_id)
                                <span class="text-indigo-400"><i class="fa-solid fa-link ml-1"></i>Generation #{{ $activity->generation_id }}</span>
                            @endif
                            @if($activity->user)
                                <a href="{{ route('admin.users.logs', $activity->user->id) }}" class="text-indigo-400 hover:text-indigo-300">
                                    <i class="fa-solid fa-arrow-up-left-from-circle ml-1"></i>مشاهده تایم‌لاین کامل این کاربر
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 text-gray-500 text-[12px] bg-[#121214] rounded-2xl border border-white/[0.04] space-y-2">
                    <i class="fa-solid fa-wave-square text-3xl text-gray-700 block animate-pulse"></i>
                    <span>هیچ رویدادی هنوز در سیستم ثبت نشده است.</span>
                </div>
            @endforelse
        </div>

        <div class="pt-4">
            {{ $activities->links() }}
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); border-radius: 10px; }
    .filter-btn.active { box-shadow: 0 0 0 1px rgba(255,255,255,0.15) inset; }
</style>

<script>
function filterLevel(level) {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.filter-btn[data-level="${level}"]`).classList.add('active');

    document.querySelectorAll('.activity-item').forEach(item => {
        if (level === 'all' || item.dataset.level === level) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}
</script>
@endsection