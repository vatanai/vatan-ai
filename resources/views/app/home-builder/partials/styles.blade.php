{{--
  استایل مشترک تمام Sectionهای Home Builder (اسلایدر/گرید/هیرو/بنر/متن/فاصله‌گذار).
  از app/home.blade.php و هر partial رندر داخل sections/ استفاده می‌شود تا کلاس‌ها
  یک‌بار تعریف شوند، نه در هر partial جداگانه (جلوگیری از تکرار/ناهماهنگی رنگ).
  رنگ‌ها هم‌راستا با پالت فعلی صفحه Home (پس‌زمینه تیره #000000 / روشن #ffffff) هستند.
--}}
<link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/home-builder.css') }}">
