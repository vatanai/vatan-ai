{{-- resources/views/admin/partials/sidebar.blade.php --}}
<aside id="adminSidebar" class="fixed inset-y-0 right-0 z-50 w-72 bg-[#121217] border-l border-white/5 flex flex-col justify-between sidebar-transition transform translate-x-full lg:translate-x-0">

    {{-- دکمه شناور باز/بسته کردن سایدبار - روی لبه، وسط ارتفاع --}}
    <button onclick="toggleSidebarCollapse()" class="hidden lg:flex absolute top-1/2 -translate-y-1/2 -left-3.5 w-7 h-7 bg-[#1c1c24] hover:bg-[#0BBF53] border border-white/10 hover:border-[#0BBF53] rounded-full items-center justify-center text-[#8a91ad] hover:text-[#0d0d12] cursor-pointer shadow-lg shadow-black/40 z-50 group" id="collapseBtn" title="تغییر اندازه منو">
        <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300" id="collapseIcon"></i>
    </button>

    {{-- محتوای سایدبار بدون اسکرول‌بار ظاهری --}}
    <div class="flex flex-col flex-1 overflow-y-auto custom-hide-scrollbar">
        
        {{-- بخش هدر سایدبار (لوگو) --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-white/5 sidebar-header-wrapper">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0BBF53]/10 text-[#0BBF53] flex items-center justify-center font-black text-lg min-w-[36px]">
                    و
                </div>
                <div class="sidebar-text-element transition-opacity duration-200">
                    <div class="text-sm font-black text-white">وطن استودیو</div>
                    <div class="text-[10px] text-[#454c6c] font-bold tracking-wider uppercase mt-0.5">Admin Panel</div>
                </div>
            </div>
        </div>

        {{-- مشخصات کاربر لاگین شده --}}
        <div class="flex items-center justify-between mx-4 my-4 p-3 bg-white/[0.02] border border-white/5 rounded-2xl user-box">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold text-sm min-w-[40px]">
                    م
                </div>
                <div class="sidebar-text-element transition-opacity duration-200">
                    <div class="text-xs font-bold text-white">محسن رضایی</div>
                    <div class="text-[10px] text-[#8a91ad] mt-0.5">مدیر کل</div>
                </div>
            </div>
            <div class="w-2 h-2 rounded-full bg-[#0BBF53] shadow-[0_0_8px_rgba(11,191,83,0.5)] sidebar-text-element"></div>
        </div>

        {{-- منوهای ناوبری کامل با تشخیص وضعیت فعال --}}
        <nav class="flex-1 px-4 py-2 space-y-6" id="sidebarNav">

            {{-- بخش مرکز فرماندهی --}}
            <div class="space-y-1">
                @php $dashboardActive = request()->is('admin/dashboard') || request()->routeIs('admin.dashboard'); @endphp
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-4 h-11 border rounded-xl font-bold text-xs transition-all relative group {{ $dashboardActive ? 'bg-[#0BBF53]/10 text-[#0BBF53] border-[#0BBF53]/20' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02] border-transparent' }}">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-bolt-lightning text-sm w-4 text-center min-w-[16px] {{ $dashboardActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                        <span class="sidebar-text-element">مرکز فرماندهی</span>
                    </div>
                    <span class="px-1.5 py-0.5 text-[10px] font-black rounded-md bg-red-500 text-white sidebar-text-element">۳</span>
                    <span class="sidebar-tooltip">مرکز فرماندهی</span>
                </a>
            </div>

            {{-- بخش نظارت --}}
            <div class="space-y-1">
                <div class="text-[10px] font-bold text-[#454c6c] px-4 uppercase tracking-wider mb-2 sidebar-text-element">نظارت</div>
                
                {{-- داشبورد --}}
                @php $monitorActive = request()->is('admin/stats*') || request()->is('admin/dashboard/stats*'); @endphp
                <div class="has-submenu">
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $monitorActive ? 'text-white bg-white/[0.04]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-chart-line text-sm w-4 text-center min-w-[16px] {{ $monitorActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                            <span class="font-bold sidebar-text-element">داشبورد</span>
                        </div>
                        <i class="fa-solid fa-chevron-left text-[10px] transition-transform arrow-icon text-[#4a5170] sidebar-text-element {{ $monitorActive ? '-rotate-90 text-white' : '' }}"></i>
                        <span class="sidebar-tooltip">داشبورد</span>
                    </button>
                    <div class="submenu-items {{ $monitorActive ? '' : 'hidden' }} mr-6 my-1 pr-4 border-r-2 border-[#0BBF53]/40 space-y-0.5">
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/stats/realtime') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">آمار لحظه‌ای</a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/stats/monthly') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">آمار روزانه و ماهانه</a>
                        <a href="#" class="flex items-center justify-between h-9 text-xs transition-colors {{ request()->is('admin/stats/alerts') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
                            <span class="sidebar-text-element">هشدارها</span>
                            <span class="px-1.5 py-0.5 text-[8px] font-bold rounded bg-red-500 text-white">۳</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- بخش مدیریت --}}
            <div class="space-y-1">
                <div class="text-[10px] font-bold text-[#454c6c] px-4 uppercase tracking-wider mb-2 sidebar-text-element">مدیریت</div>
                
                {{-- کاربران --}}
                @php $usersActive = request()->is('admin/users*'); @endphp
                <div class="has-submenu">
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $usersActive ? 'text-white bg-white/[0.04]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-users text-sm w-4 text-center min-w-[16px] {{ $usersActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                            <span class="font-bold sidebar-text-element">کاربران</span>
                        </div>
                        <i class="fa-solid fa-chevron-left text-[10px] transition-transform arrow-icon text-[#4a5170] sidebar-text-element {{ $usersActive ? '-rotate-90 text-white' : '' }}"></i>
                        <span class="sidebar-tooltip">کاربران</span>
                    </button>
                 <div class="submenu-items {{ $usersActive ? '' : 'hidden' }} mr-6 my-1 pr-4 border-r-2 border-[#0BBF53]/40 space-y-0.5">
    
    {{-- لینک لیست کاربران --}}
    <a href="{{ route('admin.users.index') }}" class="flex items-center h-9 text-xs transition-colors {{ request()->routeIs('admin.users.index') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
        لیست کاربران
    </a>
    
    {{-- لینک لاگ‌های همگانی سیستم --}}
    <a href="{{ route('admin.users.all_logs') }}" class="flex items-center h-9 text-xs transition-colors {{ request()->routeIs('admin.users.all_logs') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
        لاگ‌ها
    </a>

    <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/users/smart') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
        لیست‌های هوشمند
    </a>
    
    <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/users/tokens') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
        مدیریت توکن
    </a>
</div>
                </div>

                {{-- بلاگرها --}}
                @php $bloggersActive = request()->is('admin/bloggers*'); @endphp
                <div class="has-submenu">
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $bloggersActive ? 'text-white bg-white/[0.04]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-bullhorn text-sm w-4 text-center min-w-[16px] {{ $bloggersActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                            <span class="font-bold sidebar-text-element">بلاگرها</span>
                        </div>
                        <i class="fa-solid fa-chevron-left text-[10px] transition-transform arrow-icon text-[#4a5170] sidebar-text-element {{ $bloggersActive ? '-rotate-90 text-white' : '' }}"></i>
                        <span class="sidebar-tooltip">بلاگرها</span>
                    </button>
                    <div class="submenu-items {{ $bloggersActive ? '' : 'hidden' }} mr-6 my-1 pr-4 border-r-2 border-[#0BBF53]/40 space-y-0.5">
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/bloggers/list') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">لیست بلاگرها</a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/bloggers/links') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">ساخت لینک اختصاصی</a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/bloggers/commission') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">مدیریت کمیسیون</a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/bloggers/traffic') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">گزارش ترافیک</a>
                    </div>
                </div>

                {{-- بخش محصولات (پرامپت‌های هوش مصنوعی) --}}
                @php $promptsActive = request()->routeIs('admin.prompts.*') || request()->is('admin/prompts*'); @endphp
                <div class="has-submenu">
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $promptsActive ? 'text-white bg-white/[0.04]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-palette text-sm w-4 text-center min-w-[16px] {{ $promptsActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                            <span class="font-bold sidebar-text-element">محصولات (AI)</span>
                        </div>
                        <i class="fa-solid fa-chevron-left text-[10px] transition-transform arrow-icon text-[#4a5170] sidebar-text-element {{ $promptsActive ? '-rotate-90 text-white' : '' }}"></i>
                        <span class="sidebar-tooltip">محصولات (AI)</span>
                    </button>
                    <div class="submenu-items {{ $promptsActive ? '' : 'hidden' }} mr-6 my-1 pr-4 border-r-2 border-[#0BBF53] space-y-0.5">
                        <a href="{{ route('admin.prompts.index') }}" class="flex items-center h-9 text-xs transition-colors {{ request()->routeIs('admin.prompts.index') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
                            <i class="fa-solid fa-minus text-[8px] ml-2 text-[#4a5170]"></i> <span class="sidebar-text-element">لیست پرامپت‌ها (سبک‌ها)</span>
                        </a>
                        <a href="{{ route('admin.prompts.create') }}" class="flex items-center justify-between h-9 text-xs transition-colors {{ request()->routeIs('admin.prompts.create') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
                            <div class="flex items-center">
                                <i class="fa-solid fa-minus text-[8px] ml-2 text-[#4a5170]"></i>
                                <span class="sidebar-text-element">افزودن پرامپت جدید</span>
                            </div>
                            <span class="px-1 py-0.5 text-[8px] font-black rounded bg-[#0BBF53]/10 text-[#0BBF53] border border-[#0BBF53]/20 sidebar-text-element">جدید</span>
                        </a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/prompts/dynamic-fields') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">
                            <i class="fa-solid fa-minus text-[8px] ml-2 text-[#4a5170]"></i> <span class="sidebar-text-element">فیلدهای داینامیک</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- بخش ارتباطات --}}
            <div class="space-y-1">
                <div class="text-[10px] font-bold text-[#454c6c] px-4 uppercase tracking-wider mb-2 sidebar-text-element">ارتباطات</div>
                
                {{-- تیکت‌ها --}}
                @php $ticketsActive = request()->is('admin/tickets*'); @endphp
                <div class="has-submenu">
                    <button onclick="toggleSubmenu(this)" class="w-full flex items-center justify-between px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $ticketsActive ? 'text-white bg-white/[0.04]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-ticket text-sm w-4 text-center min-w-[16px] {{ $ticketsActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                            <span class="font-bold sidebar-text-element">تیکت‌ها</span>
                        </div>
                        <div class="flex items-center gap-2 sidebar-text-element">
                            <span class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-red-500/10 text-red-400">۷</span>
                            <i class="fa-solid fa-chevron-left text-[10px] transition-transform arrow-icon text-[#4a5170] {{ $ticketsActive ? '-rotate-90 text-white' : '' }}"></i>
                        </div>
                        <span class="sidebar-tooltip">تیکت‌ها</span>
                    </button>
                    <div class="submenu-items {{ $ticketsActive ? '' : 'hidden' }} mr-6 my-1 pr-4 border-r-2 border-[#0BBF53]/40 space-y-0.5">
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/tickets/open') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">تیکت‌های باز</a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/tickets/pending') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">در حال بررسی</a>
                        <a href="#" class="flex items-center h-9 text-xs transition-colors {{ request()->is('admin/tickets/ai-reply') ? 'text-[#0BBF53] font-bold' : 'text-[#5a6184] hover:text-[#0BBF53]' }}">پاسخ هوش مصنوعی</a>
                    </div>
                </div>
            </div>

            {{-- بخش مالی و سیستم --}}
            <div class="space-y-1">
                <div class="text-[10px] font-bold text-[#454c6c] px-4 uppercase tracking-wider mb-2 sidebar-text-element">سیستم و مالی</div>
                
                @php $transactionsActive = request()->is('admin/transactions*'); @endphp
                <a href="#" class="flex items-center gap-3 px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $transactionsActive ? 'bg-[#0BBF53]/10 text-[#0BBF53]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                    <i class="fa-solid fa-credit-card text-sm w-4 text-center min-w-[16px] {{ $transactionsActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                    <span class="font-bold sidebar-text-element">تراکنش‌های مالی</span>
                    <span class="sidebar-tooltip">تراکنش‌های مالی</span>
                </a>

                @php $serverActive = request()->is('admin/server-status*'); @endphp
                <a href="#" class="flex items-center gap-3 px-4 h-11 rounded-xl text-xs transition-colors group relative {{ $serverActive ? 'bg-[#0BBF53]/10 text-[#0BBF53]' : 'text-[#8a91ad] hover:text-white hover:bg-white/[0.02]' }}">
                    <i class="fa-solid fa-server text-sm w-4 text-center min-w-[16px] {{ $serverActive ? 'text-[#0BBF53]' : 'text-[#4a5170] group-hover:text-white' }}"></i>
                    <span class="font-bold sidebar-text-element">وضعیت زیرساخت سرور</span>
                    <span class="sidebar-tooltip">وضعیت زیرساخت سرور</span>
                </a>
            </div>

        </nav>
    </div>

    {{-- دکمه خروج در فوتر سایدبار --}}
    <div class="p-4 border-t border-white/5 footer-wrapper">
        <button class="w-full flex items-center justify-center gap-2 h-11 bg-red-500/10 hover:bg-red-500 text-red-400 hover:text-white rounded-xl text-xs font-bold transition-all group relative">
            <i class="fa-solid fa-right-from-bracket transition-transform group-hover:-translate-x-1 min-w-[16px]"></i>
            <span class="sidebar-text-element">خروج از حساب</span>
            <span class="sidebar-tooltip">خروج از حساب</span>
        </button>
    </div>
</aside>

<script>
    function toggleSubmenu(button) {
        if(document.getElementById('adminSidebar').classList.contains('w-[80px]')) return;
        const submenu = button.nextElementSibling;
        const arrow = button.querySelector('.arrow-icon');
        if (submenu.classList.contains('hidden')) {
            submenu.classList.remove('hidden');
            if(arrow) arrow.classList.add('-rotate-90', 'text-white');
        } else {
            submenu.classList.add('hidden');
            if(arrow) arrow.classList.remove('-rotate-90', 'text-white');
        }
    }

    function toggleSidebarCollapse() {
        const sidebar = document.getElementById('adminSidebar');
        const mainContent = document.getElementById('adminMainContent'); 
        const icon = document.getElementById('collapseIcon');

        if (sidebar.classList.contains('w-72')) {
            sidebar.classList.remove('w-72');
            sidebar.classList.add('w-[80px]', 'sidebar-collapsed');
            if(icon) icon.classList.add('rotate-180');
            if(mainContent) { mainContent.classList.remove('lg:mr-72'); mainContent.classList.add('lg:mr-[80px]'); }
            localStorage.setItem('sidebar-collapsed', 'true');
        } else {
            sidebar.classList.remove('w-[80px]', 'sidebar-collapsed');
            sidebar.classList.add('w-72');
            if(icon) icon.classList.remove('rotate-180');
            if(mainContent) { mainContent.classList.remove('lg:mr-[80px]'); mainContent.classList.add('lg:mr-72'); }
            localStorage.setItem('sidebar-collapsed', 'false');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            const sidebar = document.getElementById('adminSidebar');
            const mainContent = document.getElementById('adminMainContent');
            const icon = document.getElementById('collapseIcon');
            
            if(sidebar) { sidebar.classList.remove('w-72'); sidebar.classList.add('w-[80px]', 'sidebar-collapsed'); }
            if(icon) icon.classList.add('rotate-180');
            if(mainContent) { mainContent.classList.remove('lg:mr-72'); mainContent.classList.add('lg:mr-[80px]'); }
        }
    });
</script>

<style>
    .custom-hide-scrollbar::-webkit-scrollbar { display: none; }
    .custom-hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .sidebar-collapsed .sidebar-text-element { display: none !important; }
    .sidebar-collapsed .user-box { padding: 8px !important; justify-content: center; }
    .sidebar-collapsed .submenu-items { display: none !important; }
    .sidebar-collapsed .sidebar-header-wrapper { justify-content: center; padding: 20px 10px; }
    .sidebar-tooltip {
        position: absolute; right: 90px; background: #1c1c24; color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; border: 1px solid rgba(255,255,255,0.05); white-space: nowrap; opacity: 0; visibility: hidden; transition: all 0.2s ease; pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 100;
    }
    .sidebar-collapsed .group:hover .sidebar-tooltip, .sidebar-collapsed a:hover .sidebar-tooltip { opacity: 1; visibility: visible; right: 85px; }

    /* دکمه شناور باز/بسته کردن سایدبار */

    /* انیمیشن جمع/باز شدن سایدبار - فقط روی property هایی که واقعا تغییر میکنن، نه transition-all */
    .sidebar-transition {
        transition: width 0.25s ease, transform 0.25s ease, background-color 0.25s ease, border-color 0.25s ease;
    }
</style>