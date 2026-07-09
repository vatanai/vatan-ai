@extends('layouts.app')

@section('content')
<div class="pricing-page bg-[#0a0a0c] [.light_&]:bg-white text-white [.light_&]:text-black min-h-screen py-12 px-4 md:px-8 overflow-x-hidden" dir="rtl">

    {{-- هدر صفحه و نمایش موجودی توکن کاربر --}}
    <header class="text-center max-w-2xl mx-auto mb-12 relative">
        <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 w-72 h-72 bg-[#a07af5]/10 rounded-full blur-3xl" aria-hidden="true"></div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white [.light_&]:text-black mb-4 tracking-tight">
            ارتقای حساب و خرید توکن هوشمند
        </h1>
        <p class="text-gray-400 [.light_&]:text-gray-600 text-sm md:text-base leading-relaxed">
            موجودی فعلی حساب شما: 
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
                // گرفتن مبالغ و داده‌های عددی خام از دیتابیس
                $planPrice = (int) $plan->price;
                $planTokens = (int) $plan->tokens;
            @endphp

            <div class="pricing-card rounded-2xl p-6 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 relative bg-[#111116] [.light_&]:bg-white border border-[#222230] [.light_&]:border-[#E5E6E6] hover:border-[#333345] [.light_&]:hover:border-[#0BBF53]/40 [.light_&]:shadow-sm">

                <div>
                    {{-- تصویر کاور داینامیک پلن --}}
                    <div class="w-full h-40 mb-4 overflow-hidden rounded-xl bg-[#16161c] [.light_&]:bg-[#f5f5f5] border border-[#222230] [.light_&]:border-[#E5E6E6] flex items-center justify-center">
                        @if($plan->image_path)
                            <img src="{{ asset('storage/' . $plan->image_path) }}" alt="{{ $plan->name }}" class="w-full h-full object-cover">
                        @else
                            <i class="fa-regular fa-image text-3xl text-gray-600 [.light_&]:text-gray-400"></i>
                        @endif
                    </div>

                    <span class="text-[10px] font-mono text-gray-500 [.light_&]:text-gray-500 uppercase tracking-wider block mb-1">
                        شناسه پکیج: {{ $plan->plan_code ?? '—' }}
                    </span>

                    {{-- نام پلن --}}
                    <h2 class="text-lg font-bold mb-3 text-white [.light_&]:text-black">{{ $plan->name }}</h2>

                    {{-- نمایش قیمت واقعی تفکیک‌شده سه رقم سه رقم با کاما --}}
                    <div class="mb-5 flex items-baseline gap-1" dir="ltr">
                        <span class="text-xl font-extrabold text-white [.light_&]:text-black">
                            {{ $planPrice > 0 ? number_format($planPrice) : 'رایگان' }}
                        </span>
                        @if($planPrice > 0)
                            <span class="text-[10px] text-gray-400 [.light_&]:text-gray-500 font-sans ml-1">تومان</span>
                        @endif
                    </div>

                    <p class="text-xs text-gray-400 [.light_&]:text-gray-600 mb-5 leading-relaxed">
                        با فعال‌سازی این پکیج، اعتبار حساب کاربری شما بلافاصله شارژ شده و دسترسی‌های پیشرفته سیستم باز می‌گردند.
                    </p>

                    <hr class="border-[#222230] [.light_&]:border-[#E5E6E6] my-4">

                    {{-- ویژگی‌های ساختاری متصل به موجودی اصلی دیتابیس شما --}}
                    <ul class="space-y-3 text-xs text-gray-300 [.light_&]:text-gray-700">
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-wand-magic-sparkles text-[#a07af5]"></i>
                            <span class="font-semibold text-white [.light_&]:text-black">شارژ آنی پکیج: {{ number_format($planTokens) }} توکن</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>دسترسی به تمام مدل‌های هوش مصنوعی فعال</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-check text-emerald-500"></i>
                            <span>بدون تاریخ انقضا و محدودیت زمانی مصرف</span>
                        </li>
                    </ul>
                </div>

                {{-- اصلاح دکمه فرم برای ملوگیری از خطای آدرس دهی در صورت تهی بودن اسلاگ --}}
                <div class="mt-8">
                    <form action="{{ route('pricing.fakePayment', ['plan' => !empty($plan->slug) ? $plan->slug : $plan->id]) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3 px-4 rounded-xl text-xs font-bold transition-all bg-white/[0.04] [.light_&]:bg-black/[0.03] border border-white/[0.08] [.light_&]:border-black/[0.1] text-white [.light_&]:text-black hover:bg-[#a07af5] hover:text-[#0c0c10] [.light_&]:hover:text-white hover:border-[#a07af5] shadow-lg hover:shadow-[#a07af5]/10">
                            <i class="fa-solid fa-credit-card ml-1.5 text-[11px]"></i>
                            پرداخت آنلاین و افزایش آنی توکن
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16 text-gray-500 text-xs bg-[#111116] [.light_&]:bg-white border border-[#222230] [.light_&]:border-[#E5E6E6] rounded-2xl">
                <i class="fa-solid fa-layer-group text-2xl text-gray-600 [.light_&]:text-gray-400 mb-2 block"></i>
                در حال حاضر هیچ پلن و پکیج شارژی در سیستم منتشر نشده است.
            </div>
        @endforelse

    </main>
</div>
@endsection