# فعال‌سازی هوش مصنوعی و اتصال‌ها

> خلاصه: **همه‌ی مدل‌ها (Claude، Grok، Gemini، GPT) از یک حساب OpenRouter می‌آیند.** کلید جدا برای xAI/Grok یا Anthropic لازم نیست.

## ۱. OpenRouter (الزامی)

موتور به‌صورت پیش‌فرض از همان `OPENROUTER_API_KEY` پروژه (و پل کلادفلر، اگر تنظیم شده) استفاده می‌کند؛ یعنی **اگر ساخت عکس وطن کار می‌کند، هوش مصنوعی سئو هم کار می‌کند.**

1. در [openrouter.ai](https://openrouter.ai) وارد حسابی شوید که کلید پروژه از آن است ← **Credits**: حداقل ۱۰ تا ۲۰ دلار اعتبار.
2. **(پیشنهادی) کلید اختصاصی با سقف خرج:** Keys ← Create Key ← نام `vatan-seo` ← **Credit limit = 20** ← کلید را در `.env` سرور بگذارید:
   ```env
   SEO_OPENROUTER_API_KEY=sk-or-...
   ```
   این‌طوری هزینه‌ی سئو در خود OpenRouter هم جدا و محدود می‌شود (علاوه بر سقف سخت داخل موتور).
3. **اگر سرور از پل کلادفلر استفاده می‌کند** (`OPENROUTER_BASE_URL` = آدرس workers.dev): نسخه‌ی جدید `cloudflare/openrouter-gateway/src/index.js` را یک بار دیپلوی کنید:
   ```bash
   cd cloudflare/openrouter-gateway && npx wrangler deploy
   ```
   نسخه‌ی جدید سازگار با قبل است و این‌ها را اضافه می‌کند: عبور فهرست مدل‌ها (`/models`) و اعتبار کلید (`/key`)، و استفاده از کلید اختصاصی سئو (هدر `X-Vatan-Client-Key`).
4. تست:
   ```bash
   php artisan seo:ai-test
   ```
   برای هر نقش (سریع، استراتژیست، نویسنده، پژوهش با Grok) یک تماس کوچک فارسی می‌زند و مدل و هزینه را نشان می‌دهد (مجموعاً کمتر از ۱ سنت). همین تست در پنل: تنظیمات ← «تست اتصال».

### مدل‌های هر نقش (بررسی‌شده با فهرست زنده‌ی OpenRouter، ۱۸ مهر ۱۴۰۵)

| نقش | مدل اول | جایگزین‌ها | قیمت (ورودی/خروجی هر میلیون توکن) |
|---|---|---|---|
| سریع (امتیازدهی، دسته‌بندی، ممیزی) | `anthropic/claude-haiku-5.5` | gpt-6-luna، gemini-2.5-flash-lite، qwen3.7-flash | ۰٫۱ / ۰٫۵ $ |
| استراتژیست (برنامه‌ی هفته، بریف) | `anthropic/claude-sonnet-5.5` | ~claude-sonnet-latest، gpt-6-sol | ۲ / ۱۰ $ |
| نویسنده (مقاله‌ی فارسی) | `anthropic/claude-sonnet-5.5` | ~claude-sonnet-latest، gpt-6-sol | ۲ / ۱۰ $ |
| پژوهش زنده (Grok + جستجوی وب) | `x-ai/grok-4.7` (پروفایل ۳۰$+) / `x-ai/grok-4.3` (پروفایل ۲۰$) | ~grok-latest، gpt-4o-mini | ۲ / ۶ $ · ۱٫۲۵ / ۲٫۵ $ + حدود ۰٫۰۲$ جستجو |

اگر مدلی بازنشسته شود، موتور خودکار سراغ بعدی می‌رود. برآورد هزینه‌ی واقعی: هر مقاله‌ی کامل ~۰٫۰۸ تا ۰٫۱۵$، برنامه‌ی هفتگی ~۰٫۰۳$، هر پایش AI ~۰٫۰۶$. پروفایل ۲۰ دلاری با ۸ مقاله در ماه معمولاً ۳ تا ۶ دلار مصرف می‌کند.

## ۲. سرچ کنسول، GA4 و PageSpeed (رایگان)

1. [console.cloud.google.com](https://console.cloud.google.com) ← پروژه‌ی جدید ← APIs & Services ← Enable: **Google Search Console API**، **Google Analytics Data API**، **PageSpeed Insights API**.
2. IAM & Admin ← Service Accounts ← Create ← Keys ← Add key ← JSON ← دانلود.
3. پنل: سئوی هوشمند ← تنظیمات ← «سرچ کنسول و آنالیتیکس» ← آپلود JSON.
4. سرچ کنسول ← Settings ← Users and permissions ← Add user ← ایمیل سرویس‌اکانت (نمایش داده‌شده در پنل) ← **Full**. در GA4: Admin ← Property access ← Viewer.
5. (اختیاری) Credentials ← Create API key ← محدود به PageSpeed ← `SEO_PAGESPEED_KEY`.
6. اگر سرور به گوگل دسترسی ندارد: `packages/vatan-seo/cloudflare/seo-gateway` (README همان پوشه).

## ۳. بات تلگرام

```env
SEO_TELEGRAM_BOT_TOKEN=...      # در .env لوکال ثبت شده؛ روی سرور هم اضافه شود
SEO_TELEGRAM_WEBHOOK_SECRET=... # رشته‌ی تصادفی
```
بعد از دیپلوی: `php artisan seo:telegram setup` ← پنل ← تنظیمات ← «کد اتصال مدیر» ← در بات: `/start CODE`.

## ۴. زمان‌بند (الزامی روی سرور)

همه‌ی سناریوها و برنامه‌ی ۹۰ روزه با `php artisan schedule:run` (هر دقیقه) اجرا می‌شوند. در Cloudiva باید cron یا worker زمان‌بند فعال باشد.

## ۵. ارتقاهای اختیاری

| سرویس | چه چیزی اضافه می‌کند | متغیر |
|---|---|---|
| DataForSEO | رتبه‌ی دقیق روزانه از نتایج زنده‌ی گوگل ایران + ۱۰ رقیب هر کلمه | `SEO_DATAFORSEO_LOGIN/PASSWORD` |
| IndexNow | اعلام فوری صفحات جدید به بینگ/یاندکس (و ChatGPT Search) | `SEO_INDEXNOW_KEY` (در .env لوکال ساخته شده) |
| میزفا تولز | ردیاب رتبه و حجم جستجوی فارسی | بزودی |
