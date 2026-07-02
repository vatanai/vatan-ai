@extends('admin.layouts.admin')

@section('content')
<div class="min-h-screen w-full bg-[#0a0a0c] text-white font-vazir p-4 md:p-10" dir="rtl">
    <div class="max-w-7xl mx-auto space-y-6">
        
        {{-- هدر صفحه همگانی --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-white/[0.04] pb-5 gap-4">
            <div>
                <h1 class="text-xl font-black text-gray-200 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    مرکز مانیتورینگ و لاگ‌های همگانی سیستم
                </h1>
                <p class="text-[11px] text-gray-500 mt-1">رهگیری زنده تمام تصاویر خلق شده، خطاهای سرور، توکن‌های سشن و هویت کاربران پلتفرم</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.all_activities') }}" class="px-3.5 py-2 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 rounded-xl text-[11px] border border-emerald-500/10 transition-colors">
                    <i class="fa-solid fa-timeline ml-1"></i> مانیتورینگ فعالیت‌ها
                </a>
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 bg-white/5 hover:bg-white/10 text-gray-300 rounded-xl text-[11px] border border-white/5 transition-colors">
                    مدیریت کاربران
                </a>
            </div>
        </div>

        {{-- باکس آمارهای سریع سیستم --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">کل درخواست‌های پردازش شده</span>
                <span class="text-xl font-bold font-mono text-gray-200 mt-1 block">{{ $logs->total() }}</span>
            </div>
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">صفحه فعلی</span>
                <span class="text-xl font-bold font-mono text-indigo-400 mt-1 block">{{ $logs->currentPage() }}</span>
            </div>
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">تعداد لاگ در این صفحه</span>
                <span class="text-xl font-bold font-mono text-emerald-400 mt-1 block">{{ $logs->count() }}</span>
            </div>
            <div class="bg-[#121214] border border-white/[0.04] p-4 rounded-2xl">
                <span class="text-gray-500 text-[10px] block">کل صفحات موجود</span>
                <span class="text-xl font-bold font-mono text-amber-400 mt-1 block">{{ $logs->lastPage() }}</span>
            </div>
        </div>

        {{-- گرید اصلی نمایش لاگ‌ها --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @forelse($logs as $log)
                <div class="bg-[#121214] border border-white/[0.04] rounded-2xl p-5 space-y-4 shadow-lg relative flex flex-col justify-between hover:border-white/10 transition-colors">
                    
                    <div class="space-y-3">
                        {{-- اطلاعات هویت کاربر و فرستنده درخواست --}}
                        <div class="flex items-center justify-between border-b border-white/[0.03] pb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-md">
                                    {{ mb_substr($log->user->name ?? 'م', 0, 1, 'utf-8') }}
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold text-gray-200 block">
                                        {{ $log->user ? $log->user->name . ' ' . $log->user->last_name : 'کاربر ناشناس / حذف شده' }}
                                    </span>
                                    <span class="text-[9px] text-gray-500 font-mono">{{ $log->user->phone ?? 'بدون شماره همراه' }}</span>
                                </div>
                            </div>

                            <div class="text-left">
                                @if($log->status === 'completed')
                                    <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/10 text-[9px] font-bold px-2.5 py-0.5 rounded-full">موفقیت‌آمیز</span>
                                @else
                                    <span class="bg-rose-500/10 text-rose-400 border border-rose-500/10 text-[9px] font-bold px-2.5 py-0.5 rounded-full">خطا / ناموفق</span>
                                @endif
                                <span class="text-[9px] text-gray-600 font-mono block mt-1">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
                            </div>
                        </div>

                        {{-- المان‌های تصویری (قبل و بعد پردازش هوش مصنوعی) --}}
                        <div class="grid grid-cols-2 gap-3 aspect-video w-full rounded-xl bg-[#17171a] border border-white/5 p-1.5">
                            {{-- عکس ورودی --}}
                            <div class="relative rounded-lg overflow-hidden bg-black/40 flex flex-col items-center justify-center border border-white/[0.02]">
                                @if($log->input_image)
                                    <img src="{{ asset('storage/' . $log->input_image) }}" class="w-full h-full object-cover" loading="lazy">
                                    <span class="absolute bottom-1 right-1 bg-black/70 text-[8px] px-1.5 py-0.5 rounded text-gray-400">ورودی کاربر</span>
                                @else
                                    <span class="text-[9px] text-gray-600">فاقد تصویر</span>
                                @endif
                            </div>

                            {{-- عکس خروجی هوش مصنوعی --}}
                            <div class="relative rounded-lg overflow-hidden bg-black/40 flex flex-col items-center justify-center border border-white/[0.02]">
                                @if($log->status === 'completed' && $log->output_image)
                                    <img src="{{ filter_var($log->output_image, FILTER_VALIDATE_URL) ? $log->output_image : asset('storage/' . $log->output_image) }}" class="w-full h-full object-cover" loading="lazy">
                                    <span class="absolute bottom-1 right-1 bg-indigo-950 text-indigo-400 border border-indigo-500/20 text-[8px] px-1.5 py-0.5 rounded">خروجی AI</span>
                                @else
                                    <div class="text-center space-y-1 text-gray-600 p-2">
                                        <i class="fa-solid fa-ban text-[11px] text-rose-500/50"></i>
                                        <p class="text-[8px]">خروجی صادر نشد</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- 🟢 بخش اصلی: رندر دیتای سشن، توکن و متادیتای پردازش --}}
                        <div class="space-y-1.5">
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1 font-mono">
                                <i class="fa-solid fa-terminal text-indigo-400 text-[8px]"></i> اطلاعات فنی سشن و ترابری (JSON Payload):
                            </span>
                            
                            <div class="bg-black/50 p-3 rounded-xl border border-white/[0.03] overflow-x-auto max-h-40 custom-scrollbar" dir="ltr">
                                <pre class="text-[10px] text-indigo-300 font-mono leading-relaxed select-all">@if(json_decode($log->prompt)){{ json_encode(json_decode($log->prompt), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}@else{
    "text_prompt": "{{ $log->prompt ?? 'N/A' }}",
    "product_id": {{ $log->product_id ?? 'null' }},
    "warning": "دیتا با ساختار متنی قدیمی ثبت شده است."
}@endif</pre>
                            </div>
                        </div>
                    </div>

                    {{-- شناسه یکتا لوکیشن لاگ --}}
                    <span class="absolute top-2 left-2 bg-black/60 backdrop-blur-sm text-[8px] font-mono px-1.5 py-0.5 rounded text-gray-500 border border-white/5">
                        LOG_ID: #{{ $log->id }}
                    </span>
                </div>
            @empty
                <div class="col-span-1 lg:col-span-2 text-center py-20 text-gray-500 text-[12px] bg-[#121214] rounded-2xl border border-white/[0.04] space-y-2">
                    <i class="fa-solid fa-wave-square text-3xl text-gray-700 block animate-pulse"></i>
                    <span>هیچ دیتای ساختاریافته‌ای یا لاگی در کل سیستم ثبت نشده است.</span>
                </div>
            @endforelse
        </div>

        {{-- پجینیشن آدرس‌دهی صفحات --}}
        <div class="pt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.06); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.12); }
</style>
@endsection