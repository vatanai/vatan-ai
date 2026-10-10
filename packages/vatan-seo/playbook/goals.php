<?php

/*
|--------------------------------------------------------------------------
| پلی‌بوک — ستون ۲: اهداف (کلمات کلیدی)
|--------------------------------------------------------------------------
| setup     تسک‌های یک‌باره‌ی شروع استراتژی کلمات کلیدی.
| per_keyword  قالب تسک‌هایی که برای «هر کلمه‌ی هدف» به‌صورت خودکار ساخته می‌شود.
|            {keyword} با خود کلمه جایگزین می‌شود.
| recurring تسک‌های تکراری انسانی/نیمه‌خودکار که در برنامه‌ی دوره‌ای ظاهر می‌شوند
|            (کارهای کاملاً خودکار تکراری در scenarios.php هستند و تأیید نمی‌خواهند).
*/

return [

    'setup' => [
        ['key' => 'goals.discovery', 'title' => 'کشف کلمات کلیدی از روی محصولات و داده‌ی سرچ کنسول', 'category' => 'research', 'impact' => 5, 'effort' => 1, 'automation' => 'auto', 'check' => 'agent:discovery', 'due_day' => 2,
            'why' => 'ایجنت محصولات را می‌خواند، کلمات بذر می‌سازد، با پیشنهادهای گوگل گسترش می‌دهد و با داده‌ی واقعی سرچ کنسول امتیاز می‌دهد.'],
        ['key' => 'goals.pick_targets', 'title' => 'انتخاب کلمات هدف (تا سقف پروفایل بودجه)', 'category' => 'research', 'impact' => 5, 'effort' => 1, 'automation' => 'assisted', 'check' => 'targets_selected', 'due_day' => 3,
            'why' => 'تمرکز روی تعداد محدودی کلمه‌ی درست بهتر از پراکندگی روی صدها کلمه است. ایجنت پیشنهاد می‌دهد، شما تأیید می‌کنید.'],
        ['key' => 'goals.clustering', 'title' => 'خوشه‌بندی کلمات و نقشه‌ی «کلمه ← صفحه‌ی هدف»', 'category' => 'strategy', 'impact' => 4, 'effort' => 1, 'automation' => 'auto', 'check' => 'agent:clustering', 'due_day' => 4,
            'why' => 'هر خوشه یک صفحه‌ی ستون (Pillar) و چند مقاله‌ی پشتیبان دارد؛ جلوی همنوع‌خواری را هم می‌گیرد.'],
        ['key' => 'goals.competitors', 'title' => 'شناسایی ۳ تا ۵ رقیب اصلی در نتایج گوگل', 'category' => 'research', 'impact' => 3, 'effort' => 2, 'automation' => 'assisted', 'check' => 'agent:competitors', 'due_day' => 6,
            'why' => 'رقیب واقعی سئو همان سایتی است که برای کلمات شما رتبه دارد، نه لزوماً رقیب تجاری.'],
        ['key' => 'goals.content_calendar', 'title' => 'تقویم محتوای ماه اول بر اساس خوشه‌ها', 'category' => 'content', 'impact' => 4, 'effort' => 1, 'automation' => 'auto', 'check' => 'agent:calendar', 'due_day' => 7,
            'why' => 'تولید محتوا با برنامه و هدف مشخص، نه سلیقه‌ای.'],
        ['key' => 'goals.cannibalization', 'title' => 'بررسی همنوع‌خواری (چند صفحه برای یک کلمه)', 'category' => 'strategy', 'impact' => 3, 'effort' => 1, 'automation' => 'auto', 'check' => 'agent:cannibalization', 'due_day' => 10,
            'why' => 'وقتی دو صفحه برای یک کلمه رقابت کنند، گوگل هیچ‌کدام را بالا نمی‌برد.'],
    ],

    'per_keyword' => [
        ['key' => 'kw.map_page', 'title' => 'تعیین صفحه‌ی هدف برای «{keyword}»', 'category' => 'strategy', 'impact' => 5, 'effort' => 1, 'automation' => 'auto', 'check' => 'kw:map_page', 'offset_days' => 0,
            'why' => 'هر کلمه‌ی هدف باید دقیقاً یک صفحه‌ی مسئول داشته باشد.'],
        ['key' => 'kw.onpage', 'title' => 'بهینه‌سازی عنوان، H1، توضیحات و متن صفحه برای «{keyword}»', 'category' => 'onpage', 'impact' => 5, 'effort' => 2, 'automation' => 'assisted', 'check' => 'kw:onpage', 'offset_days' => 2,
            'why' => 'ایجنت صفحه را با نتایج برتر مقایسه می‌کند و پیشنهاد دقیق تغییر می‌دهد.'],
        ['key' => 'kw.brief', 'title' => 'بریف محتوای پشتیبان برای «{keyword}»', 'category' => 'content', 'impact' => 4, 'effort' => 1, 'automation' => 'auto', 'check' => 'kw:brief', 'offset_days' => 4,
            'why' => 'بریف = نیت جستجو، سؤال‌های کاربر، سرفصل‌ها و لینک‌های داخلی؛ پایه‌ی یک مقاله‌ی یونیک.'],
        ['key' => 'kw.article', 'title' => 'نگارش مقاله‌ی پشتیبان برای «{keyword}» (با تأیید شما)', 'category' => 'content', 'impact' => 4, 'effort' => 1, 'automation' => 'assisted', 'check' => 'kw:article', 'offset_days' => 7,
            'why' => 'مقاله‌ی پشتیبان با لینک به صفحه‌ی هدف، اعتبار موضوعی (Topical Authority) می‌سازد.'],
        ['key' => 'kw.internal_links', 'title' => 'حداقل ۳ لینک داخلی با انکر مرتبط به صفحه‌ی «{keyword}»', 'category' => 'onpage', 'impact' => 4, 'effort' => 2, 'automation' => 'assisted', 'check' => 'kw:internal_links', 'offset_days' => 9,
            'why' => 'لینک داخلی ارزان‌ترین و سریع‌ترین اهرم بالا بردن رتبه‌ی یک صفحه است.'],
        ['key' => 'kw.schema', 'title' => 'اسکیمای مناسب روی صفحه‌ی هدف «{keyword}»', 'category' => 'schema', 'impact' => 2, 'effort' => 1, 'automation' => 'auto', 'check' => 'kw:schema', 'offset_days' => 10,
            'why' => 'نتیجه‌ی غنی‌تر و فهم بهتر محتوا برای موتورهای جستجو و AI.'],
    ],

    // تسک‌های دوره‌ای از نسخه‌ی 0.2.0 به برنامه‌ی ۹۰ روزه (playbook/roadmap.php) منتقل شده‌اند.
    'recurring' => [],
];
