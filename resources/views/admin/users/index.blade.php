{{-- resources/views/admin/users/index.blade.php --}}
@extends('admin.layouts.admin')

@section('title', 'مدیریت کاربران | پنل ادمین')

@section('content')
<div class="w-full text-white font-vazir" dir="rtl">
    
    <div class="space-y-6">
        {{-- هدر صفحه --}}
        <div class="flex items-center justify-between border-b border-white/[0.04] pb-5">
            <h1 class="text-lg font-black tracking-wide flex items-center gap-2">
                <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                مدیریت کاربران سایت
            </h1>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.all_activities') }}" class="px-3 py-1.5 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 border border-emerald-500/10 rounded-xl text-[11px] transition-colors">
                    <i class="fa-solid fa-timeline ml-1"></i> مانیتورینگ فعالیت‌ها
                </a>
                <a href="{{ route('admin.users.all_logs') }}" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-gray-300 border border-white/5 rounded-xl text-[11px] transition-colors">
                    <i class="fa-solid fa-images ml-1"></i> لاگ تصاویر
                </a>
                <a href="{{ route('admin.dashboard') }}" class="text-[11px] text-gray-400 hover:text-white transition-colors">
                    بازگشت به داشبورد
                </a>
            </div>
        </div>

        {{-- جدول نمایش کاربران --}}
        <div class="bg-[#121214] border border-white/[0.04] rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="border-b border-white/[0.04] bg-white/[0.01] text-gray-400 text-[11px] font-bold">
                            <th class="p-4">نام و نام خانوادگی</th>
                            <th class="p-4">ایمیل</th>
                            <th class="p-4">شماره موبایل</th>
                            <th class="p-4 text-center">تعداد تصاویر خلق شده</th>
                            <th class="p-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="text-[12px] divide-y divide-white/[0.02]">
                        @forelse($users as $user)
                            <tr class="hover:bg-white/[0.01] transition-colors">
                                <td class="p-4 font-bold text-gray-200">
                                    {{ $user->name ?? 'کاربر' }} {{ $user->last_name ?? '' }}
                                </td>
                                <td class="p-4 text-gray-400 font-mono">{{ $user->email ?? '-' }}</td>
                                <td class="p-4 text-gray-400 font-mono">{{ $user->phone ?? '-' }}</td>
                                <td class="p-4 text-center font-bold text-indigo-400 font-mono">
                                    {{ $user->generations_count ?? 0 }}
                                </td>
                                <td class="p-4 text-center">
                                    {{-- 🟢 نام روت با توجه به پیشوند گروه به admin.users.logs تغییر یافت --}}
                                    <a href="{{ route('admin.users.logs', $user->id) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600/10 hover:bg-indigo-600 text-indigo-400 hover:text-white border border-indigo-500/10 rounded-lg transition-all font-black text-[10px]">
                                        <i class="fa-solid fa-history ml-1"></i> مشاهده لاگ‌ها
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500 text-[11px]">هیچ کاربری در دیتابیس یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- پجینیشن --}}
        <div class="pt-2">
            {{ $users->links() }}
        </div>
    </div>

</div>
@endsection