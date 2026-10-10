# معماری موتور سئوی هوشمند

## لایه‌ها

```
پنل ادمین (Blade + seo.css/seo.js)        بات تلگرام (@seovatanai_bot)
            │                                         │
            ▼                                         ▼
  Http\Controllers  ───────────────►  Services (Installer, ScenarioRunner, Reporter, Notifier, RunRecorder)
                                           │
          ┌────────────────────────────────┼─────────────────────────────┐
          ▼                                ▼                             ▼
   Agents (هوش مصنوعی)             Checks (قاعده‌محور، رایگان)      Scenarios (زمان‌بندی)
   KeywordDiscovery, Clustering,    CheckRegistry: robots, sitemap,  GscSync, RankUpdate, HealthCheck,
   ContentWriter, Publisher,        https, crawl:*, schema:*,        AuditRunner, CrawlAudit, PageSpeed,
   Insights, Advisor                pagespeed:*, kw:*, agent:*       DailyPlan, Opportunities, Content…
          │                                │                             │
          ▼                                ▼                             ▼
   Ai (OpenRouter + ModelRouter + Budget)   Data (GSC, PSI, Autocomplete, Rank providers)   Connectors (Laravel/WP)
```

## جریان‌های اصلی

1. **زمان‌بند:** `schedule:run` میزبان ← هر ۵ دقیقه `seo:tick` ← `ScenarioRunner::runDue()` ← سناریوهای سررسیده (قفل بدون هم‌پوشانی) ← ثبت در `seo_runs` ← محاسبه‌ی `next_run_at` (به وقت تهران؛ تناوب برخی از پروفایل بودجه).
2. **تسک‌ها:** `Installer` پلی‌بوک را به `seo_tasks` تبدیل می‌کند (یک‌باره‌ها با `due_day` از تاریخ شروع، تسک‌های هر کلمه‌ی هدف با `offset_days`، تکراری‌های انسانی با `period_key`). `AuditRunner` هر روز بررسی خودکار را اجرا می‌کند: قبول ← `done`، رد ← `needs_action` با پیام و نمونه‌ی URL.
3. **کلمات:** محصولات (Connector) ← بذر با AI ← گسترش با Autocomplete ← ادغام با کوئری‌های GSC ← امتیازدهی AI ← `candidate`. کاربر انتخاب می‌کند ← `target` ← ۶ تسک استاندارد + پایش رتبه‌ی روزانه.
4. **رتبه:** `seo_query_metrics` (کوئری×صفحه×روز از GSC، کلید `query_hash = sha1(normalize(query))`) ← `GscRankProvider` ← `seo_keyword_ranks` + فیلدهای current/previous/best/start. با DataForSEO (پروفایل رشد) رتبه‌ی زنده‌ی گوگل ایران.
5. **محتوا:** بریف (پژوهش زنده‌ی Grok + محصولات + مقالات موجود) ← پیش‌نویس (نویسنده) ← امتیاز سئوی قاعده‌محور + ممیزی کیفیت ← `review` ← کارت تأیید تلگرام/پنل ← `Publisher` ← Connector ← IndexNow.
6. **بودجه:** `Ai::call` قبل از هر تماس: نقش ← کاندیدهای مدل (پروفایل) ← برآورد هزینه ← `Budget::canSpend` (۱۵٪ رزرو کارهای حیاتی) ← تماس ← ثبت هزینه‌ی واقعی (`usage.cost`) در `seo_ai_calls`. مدل ناموجود ← کاندید بعدی.

## داده (جداول)

`seo_sites` · `seo_keywords` · `seo_keyword_clusters` · `seo_keyword_ranks` · `seo_daily_metrics` · `seo_query_metrics` · `seo_tasks` · `seo_scenarios` · `seo_runs` · `seo_ai_calls` · `seo_content_items` · `seo_audits` · `seo_alerts` · `seo_telegram_admins`

همه‌چیز `site_id` دارد ← آماده‌ی چندسایتی (نسخه‌ی ۰٫۱ تک‌سایتی است).

## تصمیم‌های کلیدی

| تصمیم | چرا |
|---|---|
| پکیج داخل پروژه، نه اپ جدا | سریع‌ترین مسیر به نسخه‌ی استیبل؛ بعد با `composer require` تکثیر می‌شود |
| PHP آرایه به‌جای YAML برای پلی‌بوک | بدون وابستگی (symfony/yaml نصب نیست) و قابل کامنت فارسی |
| سرویس‌اکانت گوگل به‌جای OAuth | بدون نیاز به ورود و رفرش توکن؛ مناسب کار خودکار سرور |
| GSC به‌عنوان منبع رایگان رتبه | دقیق‌ترین داده‌ی واقعی و رایگان؛ تأخیر ۲ تا ۳ روزه را با برچسب «داده‌ی تاریخ» شفاف نشان می‌دهیم |
| OpenRouter برای همه‌ی مدل‌ها | یک کلید، پل کلادفلر موجود، دسترسی به Claude/Gemini/Grok/DeepSeek و هزینه‌ی دقیق هر تماس |
| بررسی‌های فنی بدون AI | رایگان، قطعی و قابل تکرار؛ AI فقط جایی که ارزش افزوده دارد |
| تأیید انسانی فقط برای انتشار | کیفیت و ریسک برند؛ بقیه‌ی کارها سناریوی خودکار |
| سرو CSS/JS از روت پکیج | بدون `vendor:publish`؛ نصب یک‌مرحله‌ای |
