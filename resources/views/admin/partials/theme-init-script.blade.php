{{-- resources/views/admin/partials/theme-init-script.blade.php --}}
{{-- این اسکریپت باید همیشه در <head> و قبل از بارگذاری CSS اجرا شود تا از فلیکر (چشمک تغییر تم هنگام لود) جلوگیری شود --}}
<script>
    (function () {
        const savedTheme = localStorage.getItem('theme');
        const isDark = savedTheme === 'dark' || savedTheme === null; // پیش‌فرض: تیره (هماهنگ با طراحی فعلی پنل)

        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>