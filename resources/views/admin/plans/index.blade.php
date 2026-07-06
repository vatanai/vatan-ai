@extends('layouts.admin')
@section('title', 'مدیریت پلن‌ها — AIPIX Admin')

@section('content')
<div class="flex min-h-screen bg-[#0c0c10] text-white" dir="rtl">
  <main class="flex-1 flex flex-col min-h-screen mr-0 md:mr-[294px]">
    @include('admin.partials.header')

    <div class="admin-content flex-1 p-6 max-w-5xl w-full mx-auto overflow-y-auto max-[768px]:p-[18px]" id="content">
      
      <div class="flex items-center justify-between border-b border-[#222230] pb-4 mb-6">
        <div>
          <h2 class="text-base font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-layer-group text-[#a07af5]"></i> لیست پلن‌های شارژ توکن
          </h2>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="px-4 h-9 bg-[#a07af5] text-[#0c0c10] text-xs font-bold rounded-lg hover:bg-[#8f68e0] transition-colors flex items-center gap-1.5">
          <i class="fa-solid fa-plus text-[11px]"></i> ایجاد پلن جدید
        </a>
      </div>

      {{-- نمایش پیام موفقیت آمیز سیستم --}}
      @if(session('success'))
        <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-xs">
          {{ session('success') }}
        </div>
      @endif

      <div class="bg-[#111116] border border-[#222230] rounded-xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-right border-collapse text-xs">
            <thead>
              <tr class="border-b border-[#222230] bg-[#14141a] text-gray-400 font-semibold">
                <th class="p-4">کاور</th>
                <th class="p-4">نام پلن</th>
                <th class="p-4">کد اختصاصی یکتا</th>
                <th class="p-4 text-center">توکن</th>
                <th class="p-4">مبلغ (تومان)</th>
                <th class="p-4 text-center">عملیات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#1b1b24]">
              @forelse($plans as $plan)
                <tr class="hover:bg-[#16161c]/50 transition-colors">
                  {{-- تصویر کاور --}}
                  <td class="p-4 w-16">
                    @if($plan->image_path)
                      <img src="{{ asset('storage/' . $plan->image_path) }}" class="w-11 h-11 object-cover rounded-lg border border-[#222230]">
                    @else
                      <div class="w-11 h-11 bg-[#16161c] rounded-lg border border-[#222230] flex items-center justify-center text-gray-600">
                        <i class="fa-regular fa-image"></i>
                      </div>
                    @endif
                  </td>

                  {{-- نام پلن --}}
                  <td class="p-4 font-bold text-white">
                    {{ $plan->name }}
                  </td>

                  {{-- کد یکتا --}}
                  <td class="p-4 font-mono text-gray-400 uppercase">
                    {{ $plan->plan_code ?? '—' }}
                  </td>

                  {{-- تعداد توکن جدا شده با کاما --}}
                  <td class="p-4 text-center font-mono font-bold text-[#a07af5]">
                    {{ number_format($plan->tokens) }}
                  </td>

                  {{-- مبلغ جدا شده با کاما ۳ رقم ۳ رقم --}}
                  <td class="p-4 font-mono font-bold text-white" dir="ltr">
                    {{ number_format($plan->price) }} <span class="text-[10px] text-gray-500 font-sans mr-1">تومان</span>
                  </td>

                  {{-- عملیات حذف و ویرایش ریسورسی --}}
                  <td class="p-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <a href="{{ route('admin.plans.edit', $plan->id) }}" class="w-8 h-8 rounded-lg bg-[#16161c] border border-[#222230] text-gray-400 hover:text-white flex items-center justify-center text-xs transition-colors" title="ویرایش">
                        <i class="fa-regular fa-pen-to-square"></i>
                      </a>
                      
                      <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این پلن مطمئن هستید؟')" class="inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-8 h-8 rounded-lg bg-[#16161c] border border-[#222230] text-gray-500 hover:text-rose-400 hover:border-rose-500/20 flex items-center justify-center text-xs transition-colors" title="حذف">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="p-8 text-center text-gray-500">هیچ پلنی یافت نشد.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>
@endsection