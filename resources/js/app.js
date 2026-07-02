import Chart from 'chart.js/auto';
window.Chart = Chart;

// ۱. خط ایمپورت از public کاملاً حذف شد تا تداخل و خطای کامپایل Vite برطرف شود.

document.addEventListener('DOMContentLoaded', () => {
    
    // ══ مدیریت دراپ‌داون‌های سایدبار ══
    const toggles = document.querySelectorAll('.nav-dropdown-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault(); // جلوگیری از رفتار پیش‌فرض لینک‌ها
            
            const wrapper = this.closest('.nav-dropdown-wrapper');
            if (!wrapper) return;

            const subNav = wrapper.querySelector('.sub-nav');
            const arrow = this.querySelector('.nav-arrow');

            // باز یا بسته کردن زیرمنو با جابجایی کلاس hidden
            if (subNav) {
                subNav.classList.toggle('hidden');
            }

            // چرخاندن فلش با کلاس‌های خود تیلوند بجای استایل خطی (فرض بر این است که فلش به صورت پیش‌فرض کلاسی مثل transition-transform دارد)
            if (arrow) {
                arrow.classList.toggle('-rotate-90'); 
                // نکته: اگر جهت فلش شما فرق دارد، می‌توانید از کلاس 'rotate-180' یا 'rotate-90' استفاده کنید
            }
        });
    });

    // ══ مدیریت منو و اوورلی سایدبار در موبایل ══
    // تعریف به صورت گلوبال (window) تا دکمه‌های همبرگری و اوورلی که onclick دارند بدون خطا کار کنند
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar'); // مطمئن شوید تگ سایدبار اصلی شما id="sidebar" دارد
        const overlay = document.getElementById('sidebar-overlay');

        if (!sidebar || !overlay) return;

        // تلاقی باز و بسته شدن سایدبار (بر اساس کلاس‌های ریسپانسیو که استفاده می‌کنید)
        // مثلاً اگر در حالت موبایل سایدبار با translate-x-full مخفی می‌شود:
        sidebar.classList.toggle('translate-x-full');

        // مدیریت انیمیشن و نمایش اوورلی شفاف پشت سایدبار
        if (overlay.classList.contains('hidden')) {
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            }, 20);
        } else {
            overlay.classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 250); // همگام با duration-[250ms] که در بلید دادید
        }
    };
});