@extends('layouts.app')

@section('content')
<div class="pricing-page bg-[#0a0a0c] text-white min-h-screen py-12 px-4 md:px-8 overflow-x-hidden" dir="rtl">
    
    {{-- هدر صفحه و نمایش موجودی توکن کاربر --}}
    <header class="text-center max-w-2xl mx-auto mb-12 relative">
        <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 w-72 h-72 bg-[#a07af5]/10 rounded-full blur-3xl" aria-hidden="true"></div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-4 tracking-tight">
            ارتقای حساب و خرید توکن
        </h1>
        <p class="text-gray-400 text-sm md:text-base leading-relaxed">
            موجودی فعلی شما: 
            <span class="text-emerald-400 font-bold font-mono bg-emerald-500/10 px-2 py-1 rounded border border-emerald-500/20">
                {{ number_format(auth()->user()->tokens ?? 0) }} توکن
            </span>
        </p>

        {{-- پیغام موفقیت پس از شارژ حساب --}}
        @if(session('success'))
            <div class="mt-6 bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 text-xs text-emerald-400 flex items-center gap-2 max-w-md mx-auto">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span class="text-right">{{ session('success') }}</span>
            </div>
        @endif
    </header>

    {{-- کارت‌های پلن‌ها کاملاً متصل به دیتابیس واقعی --}}
    <main class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch mb-20">
        
        @forelse($plans as $plan)
            @php
                // تبدیل ویژگی‌ها به آرایه در صورت وجود (اگر فیلد features ندارید آرایه خالی می‌ماند)
                $features = isset($plan->features) ? (is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? [])) : [];
                
                // بررسی محبوب بودن پلن (اگر فیلدش را ندارید پیش‌فرض false)
                $isPopular = $plan->is_popular ?? false;
                
                // گرفتن قیمت و توکن واقعی از ستون‌های دیتابیس شما
                $planPrice = (float) $plan->price;
                $planTokens = (int) $plan->tokens;
            @endphp

            <div class="pricing-card rounded-2xl p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 relative
                {{ $isPopular ? 'bg-[#151221] border-2 border-[#a07af5] shadow-[0_0_30px_rgba(160,122,245,0.1)]' : 'bg-[#111116] border border-[#222230] hover:border-[#333345]' }}">
                
                @if($isPopular)
                    <div class="absolute -top-3.5 left-1/2 transform -translate-x-1/2 bg-[#a07af5] text-[#0c0c10] text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wide shadow-md">
                        ★ محبوب‌ترین پلن
                    </div>
                @endif

                <div>
                    <span class="text-xs font-bold {{ $isPopular ? 'text-[#a07af5]' : 'text-gray-400' }} uppercase tracking-wider block mb-2">
                        سطح دسترسی
                    </span>
                    <h2 class="text-xl font-bold mb-4 text-white">{{ $plan->name }}</h2>
                    
                    {{-- نمایش قیمت واقعی از ستون price دیتابیس --}}
                    <div class="mb-6 flex items-baseline gap-1">
                        <span class="text-2xl font-extrabold text-white">
                            {{ $planPrice > 0 ? number_format($planPrice) . ' تومان' : 'رایگان' }}
                        </span>
                    </div>

                    <p class="text-xs text-gray-400 mb-6 leading-relaxed">
                        با فعال‌سازی این پلن، اعتبار حساب شما بلافاصله شارژ شده و دسترسی‌های مربوطه باز می‌گردند.
                    </p>
                    
                    <hr class="border-[#222230] my-4">
                    
                    {{-- لیست ویژگی‌ها و تعداد توکن واقعی از ستون tokens --}}
                    <ul class="space-y-3.5 text-xs text-gray-300">
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-wand-magic-sparkles text-emerald-400"></i>
                            <span class="font-semibold text-white">شارژ آنی {{ number_format($planTokens) }} توکن</span>
                        </li>
                        
                        @foreach($features as $feature)
                            @if($feature)
                                <li class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-check text-emerald-500"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                {{-- دکمه پرداخت و خرید پلن --}}
                <div class="mt-8">
                    <form action="{{ route('pricing.fakePayment', $plan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3 px-4 rounded-xl text-xs font-bold transition-all
                            {{ $isPopular 
                                ? 'bg-[#a07af5] text-[#0c0c10] hover:bg-[#8f68e0] shadow-lg shadow-[#a07af5]/20' 
                                : 'bg-white/[0.04] border border-white/[0.08] text-white hover:bg-white/10' }}">
                            <i class="fa-solid fa-credit-card ml-1.5 text-[11px]"></i>
                            پرداخت شبیه‌ساز (افزایش آنی توکن)
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500 text-xs">
                هیچ پلن فعالی در حال حاضر وجود ندارد.
            </div>
        @endforelse

    </main>
</div>
@endsection