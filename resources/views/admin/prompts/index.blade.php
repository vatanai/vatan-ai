{{-- resources/views/admin/prompts/index.blade.php --}}
@extends('admin.layouts.admin')

@section('title', 'مدیریت پرامپت‌ها | وطن استودیو')

@section('content')
<div id="index-prompt-page" class="space-y-6">

    {{-- هدر صفحه و دکمه افزودن جدید --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-[#121217] border border-white/5 rounded-2xl p-5">
        <div>
            <h1 class="text-xl font-black text-white">مدیریت پرامپت‌های هوش مصنوعی</h1>
            <p class="text-xs text-[#5a6184] mt-1">لیست سبک‌ها و پرامپت‌های مهندسی شده سیستم را مدیریت و ویرایش کنید.</p>
        </div>
        <div>
            <a href="{{ route('admin.prompts.create') }}" class="h-11 px-5 bg-[#0BBF53] hover:bg-[#09a346] text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-2 shadow-[0_4px_20px_rgba(11,191,83,0.15)]">
                <i class="fa-solid fa-plus text-sm"></i>
                <span>افزودن سبک (پرامپت) جدید</span>
            </a>
        </div>
    </div>

    {{-- جدول نمایش پرامپت‌ها --}}
    <div class="bg-[#121217] border border-white/5 rounded-2xl overflow-hidden">
        @if($prompts->isEmpty())
            {{-- حالت خالی بودن جدول --}}
            <div class="p-12 text-center space-y-4">
                <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center text-2xl text-[#454c6c] mx-auto">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white">هیچ پرامپتی یافت نشد</h3>
                    <p class="text-xs text-[#5a6184]">هنوز هیچ سبک یا پرامپتی در سیستم تعریف نکرده‌اید.</p>
                </div>
            </div>
        @else
            {{-- جدول اصلی --}}
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="border-b border-white/5 bg-white/[0.01] text-[#8a91ad] text-[11px] font-bold">
                            <th class="p-4 w-16 text-center">شناسه</th>
                            <th class="p-4">تصویر و نام سبک</th>
                            <th class="p-4 hidden md:table-cell">متن پرامپت (AI Prompt)</th>
                            <th class="p-4 text-center w-28">وضعیت انتشار</th>
                            <th class="p-4 text-center w-36">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-xs text-white">
                        @foreach($prompts as $prompt)
                            <tr class="hover:bg-white/[0.01] transition-colors">
                                {{-- شناسه --}}
                                <td class="p-4 text-center font-code text-[#454c6c]">#{{ $prompt->id }}</td>
                                
                                {{-- تصویر و نام سبک --}}
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ asset($prompt->image) }}" alt="{{ $prompt->name }}" class="w-12 h-12 object-cover rounded-xl border border-white/10 bg-[#0a0a0e]">
                                        <div class="space-y-1">
                                            <span class="block font-bold text-white">{{ $prompt->name }}</span>
                                            <span class="block text-[10px] text-[#5a6184] max-w-xs truncate">{{ $prompt->description ?? 'بدون توضیح' }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- متن پرامپت --}}
                                <td class="p-4 hidden md:table-cell max-w-xs">
                                    <div class="text-[11px] font-mono text-[#8a91ad] bg-white/[0.02] border border-white/5 p-2 rounded-lg truncate dir-ltr text-left">
                                        {{ $prompt->prompt }}
                                    </div>
                                </td>

                                {{-- وضعیت --}}
                                <td class="p-4 text-center">
                                    @if($prompt->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#0BBF53]/10 text-[#0BBF53] border border-[#0BBF53]/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#0BBF53]"></span> فعال
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> غیرفعال
                                        </span>
                                    @endif
                                </td>

                                {{-- عملیات (ویرایش / حذف) --}}
                                <td class="p-4">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- دکمه ویرایش --}}
                                        <a href="{{ route('admin.prompts.edit', $prompt->id) }}" class="w-8 h-8 bg-white/5 border border-white/5 hover:border-[#0BBF53]/30 hover:bg-[#0BBF53]/10 text-[#8a91ad] hover:text-[#0BBF53] rounded-lg flex items-center justify-center transition-all" title="ویرایش">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>

                                        {{-- فرم دکمه حذف با تاییدیه امنیتی --}}
                                        <form action="{{ route('admin.prompts.destroy', $prompt->id) }}" method="POST" onsubmit="return confirm('آیا از حذف این پرامپت مطمئن هستید؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 bg-white/5 border border-white/5 hover:border-red-500/30 hover:bg-red-500/10 text-[#8a91ad] hover:text-red-400 rounded-lg flex items-center justify-center transition-all" title="حذف">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection