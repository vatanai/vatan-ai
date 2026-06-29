import Chart from 'chart.js/auto';
window.Chart = Chart;

// در صورت نیاز به اسکریپت‌های مدیریت پنل خودتان:
import '../../public/admin/js/admin.js';
// resources/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // پیدا کردن تمام دکمه‌های دراپ‌داون سایدبار
    const toggles = document.querySelectorAll('.nav-dropdown-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const wrapper = this.closest('.nav-dropdown-wrapper');
            const subNav = wrapper.querySelector('.sub-nav');
            const arrow = this.querySelector('.nav-arrow');

            // باز یا بسته کردن زیرمنو
            if (subNav.classList.contains('hidden')) {
                subNav.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(-90deg)'; // چرخاندن فلش به پایین
            } else {
                subNav.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        });
    });
});