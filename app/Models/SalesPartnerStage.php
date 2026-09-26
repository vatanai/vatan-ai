<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPartnerStage extends Model
{
    protected $fillable = [
        'stage',
        'title',
        'description',
        'icon',
        'goal',
        'task',
        'script',
        'follow_up',
        'default_follow_up_hours',
        'max_follow_ups',
        'message_type',
        'advance_when',
        'stop_when',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stage' => 'integer',
            'default_follow_up_hours' => 'integer',
            'max_follow_ups' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public static function defaultDefinitions(): array
    {
        return [
            ['stage' => 0, 'title' => 'سرنخ جدید', 'description' => 'پیدا شده و هنوز بررسی نشده', 'icon' => 'fa-magnifying-glass', 'goal' => 'اطمینان از اینکه این پیج برای همکاری ارزش بررسی دارد.', 'task' => 'بررسی کیفیت محتوا، مخاطب و تناسب با وطن.', 'script' => 'این پیج را بررسی کن و دلیل مناسب‌بودنش برای همکاری را در یادداشت ثبت کن.', 'follow_up' => 'بدون زمان‌بندی؛ تا پایان بررسی.', 'default_follow_up_hours' => null, 'max_follow_ups' => 0, 'message_type' => 'none', 'advance_when' => 'اطلاعات اولیه و دلیل تناسب ثبت شده باشد.', 'stop_when' => 'پیج نامرتبط، بی‌کیفیت یا تکراری باشد.'],
            ['stage' => 1, 'title' => 'آماده تماس', 'description' => 'مناسب برای شروع ارتباط', 'icon' => 'fa-list-check', 'goal' => 'آماده‌سازی یک تماس انسانی و شخصی‌سازی‌شده.', 'task' => 'انتخاب متن یا وویس اول و تعیین دلیل شروع گفتگو.', 'script' => 'قبل از ارسال، حداقل یک اشاره واقعی به محتوا یا صفحه مخاطب اضافه کن.', 'follow_up' => 'همان روز.', 'default_follow_up_hours' => 0, 'max_follow_ups' => 0, 'message_type' => 'both', 'advance_when' => 'پیام اول آماده و مخاطب تأیید شده باشد.', 'stop_when' => 'اطلاعات تماس معتبر نیست یا مخاطب قبلاً پیگیری شده است.'],
            ['stage' => 2, 'title' => 'پیام اول', 'description' => 'پیام یا وویس اول ارسال شده', 'icon' => 'fa-microphone', 'goal' => 'بازکردن یک گفتگوی طبیعی، بدون فشار فروش.', 'task' => 'ارسال وویس یا پیام اول توسط نیروی انسانی.', 'script' => 'سلام، محتوای صفحه‌تون رو دیدم و به نظرم برای یک همکاری جذاب مناسب هستید. تا حالا از ابزارهای هوش مصنوعی برای درآمدزایی از محتوای خودتون استفاده کردید؟ اگر راهی برای افزایش درآمدتون وجود داشته باشه، براتون جذابه؟', 'follow_up' => '۲۴ ساعت بعد از پیام اول.', 'default_follow_up_hours' => 24, 'max_follow_ups' => 1, 'message_type' => 'both', 'advance_when' => 'پیام ارسال شده و زمان پیگیری ثبت شده باشد.', 'stop_when' => 'ارسال ناموفق، درخواست عدم تماس یا پاسخ منفی مستقیم.'],
            ['stage' => 3, 'title' => 'منتظر پاسخ', 'description' => 'نیازمند پیگیری پاسخ', 'icon' => 'fa-hourglass-half', 'goal' => 'گرفتن پاسخ بدون اسپم‌کردن مخاطب.', 'task' => 'یک پیگیری کوتاه و محترمانه، سپس توقف.', 'script' => 'سلام، فقط خواستم پیام قبلی‌ام بین پیام‌ها گم نشده باشه. اگر موضوع همکاری براتون جالبه، خیلی کوتاه توضیح می‌دم.', 'follow_up' => 'یک پیگیری بعد از ۲۴ تا ۴۸ ساعت؛ سپس توقف موقت.', 'default_follow_up_hours' => 36, 'max_follow_ups' => 1, 'message_type' => 'message', 'advance_when' => 'پاسخ واقعی از مخاطب دریافت شده باشد.', 'stop_when' => 'دو بار بی‌پاسخی یا درخواست عدم پیگیری.'],
            ['stage' => 4, 'title' => 'پاسخ مثبت', 'description' => 'علاقه اولیه ایجاد شده', 'icon' => 'fa-thumbs-up', 'goal' => 'تأیید علاقه و ورود به معرفی روشن برنامه.', 'task' => 'پاسخ‌دادن به سؤال مخاطب و آماده‌کردن زمینه معرفی.', 'script' => 'عالیه. ما در وطن یک برنامه محدود برای همکاری با ۱۵ متخصص و تولیدکننده محتوای منتخب داریم که می‌تونن از معرفی ابزارها و محصولات درآمد داشته باشن.', 'follow_up' => 'همان روز.', 'default_follow_up_hours' => 0, 'max_follow_ups' => 0, 'message_type' => 'message', 'advance_when' => 'مخاطب اجازه ادامه گفتگو یا دریافت توضیحات را داده باشد.', 'stop_when' => 'علاقه واقعی وجود ندارد یا پاسخ مبهم باقی می‌ماند.'],
            ['stage' => 5, 'title' => 'معرفی برنامه', 'description' => 'طرح همکاری معرفی شده', 'icon' => 'fa-presentation-screen', 'goal' => 'رساندن مخاطب به یک اقدام مشخص، نه توضیح طولانی.', 'task' => 'ارسال لینک تست و توضیح کوتاه ارزش همکاری.', 'script' => 'پیشنهاد می‌کنم اول وطن را از لینک معرفی داخل بیو تست کنید. اعتبار اولیه برای شروع دارید و بعد از اینکه تجربه‌تان را دیدید، مدل همکاری و درآمد را دقیق فعال می‌کنیم.', 'follow_up' => '۲۴ ساعت بعد از ارسال لینک.', 'default_follow_up_hours' => 24, 'max_follow_ups' => 1, 'message_type' => 'message', 'advance_when' => 'لینک دیده شده یا تست شروع شده باشد.', 'stop_when' => 'مخاطب فعلاً زمان ندارد یا علاقه‌اش را از دست داده است.'],
            ['stage' => 6, 'title' => 'شروع تست', 'description' => 'وطن را برای تست شروع کرده', 'icon' => 'fa-play', 'goal' => 'کمک به رسیدن سریع به اولین تجربه موفق.', 'task' => 'بررسی ورود و کمک برای ساخت اولین خروجی.', 'script' => 'برای اینکه سریع نتیجه بگیرید، یک موضوع یا پرامپت برای اولین محتوایتان بفرستید تا با هم شروعش کنیم.', 'follow_up' => 'حداکثر ۲۴ ساعت بعد از شروع تست.', 'default_follow_up_hours' => 24, 'max_follow_ups' => 2, 'message_type' => 'message', 'advance_when' => 'اولین استفاده یا خروجی ثبت شده باشد.', 'stop_when' => 'تست شروع نشده و بعد از دو یادآوری پاسخی نیست.'],
            ['stage' => 7, 'title' => 'محتوای اول', 'description' => 'در حال آماده‌سازی اولین محتوا', 'icon' => 'fa-wand-magic-sparkles', 'goal' => 'ساخت اولین محتوای قابل انتشار به نام همکار.', 'task' => 'دریافت ایده یا پرامپت و همراهی تا تأیید خروجی.', 'script' => 'این خروجی را به نام خودتان آماده می‌کنیم. اگر تأییدش کنید، می‌توانید با لینک اختصاصی‌تان منتشرش کنید و نتیجه را ببینید.', 'follow_up' => 'هر ۴۸ ساعت تا تکمیل محتوا.', 'default_follow_up_hours' => 48, 'max_follow_ups' => 2, 'message_type' => 'message', 'advance_when' => 'خروجی تأیید و آماده انتشار باشد.', 'stop_when' => 'محتوا رد شده و مخاطب ادامه همکاری را نمی‌خواهد.'],
            ['stage' => 8, 'title' => 'لینک اختصاصی', 'description' => 'لینک همکاری فعال شده', 'icon' => 'fa-link', 'goal' => 'وصل‌کردن خروجی به مسیر قابل‌اندازه‌گیری.', 'task' => 'ساخت لینک، توضیح نحوه استفاده و ثبت محل انتشار.', 'script' => 'لینک اختصاصی شما فعال شد. هر بازدید، ثبت‌نام و خرید از این لینک قابل مشاهده است و نتیجه‌ها در پنل‌تان نمایش داده می‌شود.', 'follow_up' => '۴۸ ساعت بعد از انتشار.', 'default_follow_up_hours' => 48, 'max_follow_ups' => 2, 'message_type' => 'message', 'advance_when' => 'لینک ساخته و محل انتشار مشخص شده باشد.', 'stop_when' => 'همکار لینک را نمی‌خواهد یا انتشار منتفی شده است.'],
            ['stage' => 9, 'title' => 'اولین تبدیل', 'description' => 'اولین معرفی یا فروش ثبت شده', 'icon' => 'fa-arrow-trend-up', 'goal' => 'تبدیل اولین نتیجه به انگیزه برای تکرار.', 'task' => 'نمایش نتیجه و پیشنهاد تکرار یک همکاری مشخص.', 'script' => 'اولین نتیجه شما ثبت شد. این فقط شروع مسیر است؛ اگر موافق باشید برای محتوای بعدی هم یک ایده سریع آماده کنیم.', 'follow_up' => 'همان روز اعلام نتیجه و ۷ روز بعد.', 'default_follow_up_hours' => 168, 'max_follow_ups' => 1, 'message_type' => 'message', 'advance_when' => 'اولین ثبت‌نام یا خرید معتبر ثبت شده باشد.', 'stop_when' => 'تبدیل مشکوک یا نیازمند بررسی است.'],
            ['stage' => 10, 'title' => 'همکار فعال', 'description' => 'آماده تکرار و رشد همکاری', 'icon' => 'fa-flag-checkered', 'goal' => 'ساخت عادت همکاری و برنامه تکرارشونده.', 'task' => 'برنامه‌ریزی ماهانه محتوا، لینک و پیگیری نتیجه.', 'script' => 'حالا که مسیر اول جواب داده، می‌توانیم همکاری را منظم کنیم: هر ماه چند محتوای مشخص، لینک اختصاصی و گزارش نتیجه در پنل شما.', 'follow_up' => 'هفتگی برای شروع؛ سپس ماهانه.', 'default_follow_up_hours' => 168, 'max_follow_ups' => 0, 'message_type' => 'message', 'advance_when' => 'همکار برنامه تکرار همکاری را پذیرفته باشد.', 'stop_when' => 'همکار همکاری را متوقف کرده یا چند دوره بدون فعالیت گذشته است.'],
        ];
    }
}
