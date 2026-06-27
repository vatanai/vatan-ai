{{-- resources/views/admin/partials/header.blade.php --}}
<header class="sticky top-0 z-30 bg-white/80 dark:bg-[#121214]/80 backdrop-blur-xl border-b border-gray-200 dark:border-white/[0.06] px-4 md:px-6 py-3 flex items-center justify-between gap-4 flex-wrap transition-colors duration-300">
    <div class="flex items-center gap-3">
        <button class="lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white p-1.5 rounded-lg transition-colors" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-400 dark:text-gray-500">پنل مدیریت</span>
            <i class="fa-solid fa-chevron-left text-[10px] text-gray-300 dark:text-gray-600"></i>
            <span class="text-gray-900 dark:text-white font-bold" id="breadcrumb">مرکز فرماندهی</span>
        </div>
    </div>

    <div class="flex items-center gap-3 flex-1 max-w-xs">
        <div class="relative w-full">
            <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-xs"></i>
            <input type="text" placeholder="جستجو در پنل..." class="w-full bg-gray-100 dark:bg-white/[0.03] border border-gray-200 dark:border-white/[0.06] rounded-xl py-1.5 pr-8 pl-3 text-[12px] text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-indigo-500/50 focus:bg-white dark:focus:bg-white/[0.05] focus:outline-none transition-all" />
        </div>
    </div>

    <div class="flex items-center gap-1.5">
        <button class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/[0.03] hover:bg-gray-200 dark:hover:bg-white/10 border border-gray-200 dark:border-white/[0.05] text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition-colors" onclick="toggleTheme()" title="تغییر تم">
            <i class="fa-solid fa-moon text-[13px]" id="themeIcon"></i>
        </button>
        <button class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/[0.03] hover:bg-gray-200 dark:hover:bg-white/10 border border-gray-200 dark:border-white/[0.05] text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center justify-center relative transition-colors" title="اعلان‌ها">
            <i class="fa-solid fa-bell text-[13px]"></i>
            <span class="absolute top-1.5 left-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-[#121214]"></span>
        </button>
        <button class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/[0.03] hover:bg-gray-200 dark:hover:bg-white/10 border border-gray-200 dark:border-white/[0.05] text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition-colors">
            <i class="fa-solid fa-user text-[13px]"></i>
        </button>
    </div>
</header>