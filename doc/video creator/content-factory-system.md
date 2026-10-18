# کارخانه‌ی محتوای وطن — سند جامع سیستم

> **نسخه‌ی سند:** ۱۷ اکتبر ۲۰۲۶ (۲۵ مهر ۱۴۰۵)
> **نسخه‌ی پکیج:** `0.3.0` (فایل `packages/vatan-content/VERSION`)
> **وضعیت:** کد و داشبورد نوشته و دیپلوی شده‌اند؛ تست گرم کامل روی مک و اتصال تلگرام هنوز انجام نشده است.
> **هدف این سند:** هر کسی (از جمله ChatGPT با دسترسی به مک) بتواند بدون پرسیدن از من، سیستم را بفهمد، راه‌اندازی کند، تست کند و کار را ادامه دهد.

---

## ۰. قوانین کار با این سیستم (اول بخوان)

1. **هیچ توکن یا کلید را در فایل، چت، لاگ یا سند ننویس.** فقط نام متغیر محیطی را بنویس. مقدار `CONTENT_TELEGRAM_BOT_TOKEN`، `OPENROUTER_API_KEY`، `FAL_API_KEY`، `CF_AGENT_TOKEN` فقط در فایل `.env` (روی مک) یا در تنظیمات داشبورد وارد می‌شود.
2. **دیپلوی روی سرور (aivatan.com) فقط با تأیید صریح صاحب پروژه.** هر تغییر روی مک یا سندباکس، روی سرور زنده اعمال نمی‌شود.
3. **کامیت و پوش فقط وقتی صاحب پروژه بخواهد.**
4. **تاریخ‌ها را همیشه با تاریخ واقعی امروز مقایسه کن.** تقویم از ۲۴ مهر ۱۴۰۵ (۱۶ اکتبر) شروع می‌شود و این تاریخ گذشته است؛ قبل از هر تست زنده باید جابه‌جا شود (بخش ۲۲).
5. **پرامپت‌ها و سبک‌ها را بی‌دلیل بازنویسی نکن.** نسخه‌ها ذخیره می‌شوند؛ برای برگشت از دکمه‌ی «بازگردانی» یا «نسخه‌ها» استفاده کن.
6. **هر پیام تلگرام و هر ساخت ویدیو هزینه دارد یا روی اعتبار تأثیر می‌گذارد.** قبل از ارسال انبوه یا ساخت کامل، یک نمونه‌ی تست بساز.
7. زبان داشبورد و تمام متن‌های کاربرپسند **فارسی** است؛ کد و نام فایل‌ها انگلیسی.

---

## ۱. هدف سیستم

کارخانه‌ی محتوای وطن یک خط تولید محتوای کوتاه (ریلز، اسلاید، پوستر، کاور و استوری) برای اینستاگرام وطن است. ایده‌ها و قالب‌ها از مرجع‌های ویدیویی و موسیقی‌ها یاد گرفته می‌شوند، هر پست بر اساس یک **تقویم** و یک **ژن محتوا** ساخته می‌شود، پیش از انتشار توسط انسان **تأیید** می‌شود، و بعد از انتشار **آمار** آن ثبت و امتیاز داده می‌شود تا سیستم یاد بگیرد.

اصول:
- **کنترل انسانی:** هیچ پستی بدون تأیید (داشبورد یا تلگرام) منتشر نمی‌شود.
- **تقویم اول، تولید بعد:** اول تقویم پر و قفل، بعد ساخت.
- **هزینه‌ی کنترل‌شده:** مدل‌های ارزان برای حجم، مدل قوی فقط برای داوری؛ سقف بودجه‌ی ماهانه.
- **یادگیری با داده:** هر پست یک ژن دارد (سبک، هوک، موسیقی، صنف، هدف، آزمایش) و آمار بازه‌ای می‌گیرد.
- **قابل جابه‌جایی:** فقط بخش `host` در کانفیگ مخصوص میزبان است؛ هسته‌ی پکیج بین داشبوردها مشترک است.

---

## ۲. معماری کلی

```
┌────────────── مک (محلی) ──────────────┐        ┌──────── سرور / داشبورد وب ────────┐
│ content-lab/                            │        │ Laravel + پکیج vatan-content       │
│   00-inbox  (ویدیوی خام)                 │        │  داشبورد (Blade)                    │
│   02-music  (موسیقی)                     │  HTTP  │  جدول‌های cf_*                       │
│   _system/agent/agent.py  ◄──────────────┼────────┤  cf_jobs (صف کار)                    │
│   _system/tools/ingest.py (تحلیل)        │ X-CF-  │  Services: Builder, MasterPrompt,   │
│   _system/render (Remotion, رندر)        │ Token  │   Ai, Library, Scorer, Telegram,     │
│   Claude Code CLI (نوشتن سناریو)          │        │   InstagramBridge                    │
└─────────────────────────────────────────┘        └────────────────────────────────────┘
          ▲                                                     │
          └─ اجرای دستی یا LaunchAgent (com.vatan.content-agent) ┘
```

جریان یک پست:
1. کاربر در **تقویم** یک ردیف را باز می‌کند (صفحه‌ی کامل پست، بخش ۸).
2. با **«ذخیره و سفارش ساخت»** یک `cf_production` و نسخه‌ی اول ساخته می‌شود؛ `Builder` مشخصات خروجی را می‌سازد (بخش ۹).
3. اگر `CF_RUN_INLINE=true` باشد، داشبورد ایجنت مک را در پس‌زمینه روشن می‌کند؛ ایجنت job `render` را می‌گیرد، Remotion ویدیو و تصاویر را می‌سازد، و با `/agent/upload` (یا قطعات `/agent/chunk`) به سرور می‌فرستد.
4. ایجنت با `/agent/productions/{id}/version` نسخه را ثبت می‌کند؛ وضعیت به **در انتظار تأیید** می‌رود.
5. تلگرام پیش‌نمایش را با دکمه‌ی **تأیید** یا **اصلاح** می‌فرستد؛ یا کاربر در صف داشبورد تأیید می‌کند.
6. بعد از انتشار دستی یا با اینستاگرام هوشمند، آمار در بازه‌های زمانی (بخش ۱۷) ثبت و امتیاز محاسبه می‌شود.

---

## ۳. ساختار فایل‌ها

### ۳.۱ پکیج Laravel: `packages/vatan-content/`

| مسیر | کار |
|---|---|
| `VERSION` | شماره‌ی نسخه (`0.3.0`) |
| `CHANGELOG.md` | تاریخچه‌ی تغییرات ۰.۱ تا ۰.۳ |
| `bootstrap.php` | بارگذاری اولیه |
| `config/content-factory.php` | همه‌ی تنظیمات (بخش ۴) |
| `config/help.php` | متن راهنمای «؟» تمام بخش‌ها |
| `database/migrations/` | ۵ مایگریشن (بخش ۵) |
| `database/seed/*.json` | داده‌ی پایه: سبک‌ها، لایه‌های پرامپت، تقویم، بینش‌ها |
| `database/seed/cal.py` | ساخت `calendar.json` از ردیف‌های دستی (ابزار کمکی) |
| `routes/web.php` | مسیرهای داشبورد و API ایجنت (بخش ۶) |
| `src/ContentFactoryServiceProvider.php` | ثبت پکیج، دستورها، زمان‌بندی، مهاجرت خودکار محلی |
| `src/Models/` | ۱۵ مدل Eloquent (بخش ۵) |
| `src/Services/` | منطق اصلی (بخش ۹ تا ۱۸) |
| `src/Http/Controllers/` | کنترلرهای داشبورد و ایجنت |
| `src/Console/` | دستورهای `content:sync-lab`، `content:metrics`، `content:seed` |
| `src/Support/` | `Fa` (تاریخ شمسی و اعداد فارسی)، `Nav` (منو)، `Catalog` (فهرست محصولات)، `Seed` |
| `resources/views/` | قالب‌های Blade: layout، overview، calendar، items، productions، styles، prompts، music، insights، settings و partials |
| `resources/assets/` | `cf.css`، `cf.js`، `cf-item.js` |
| `tests/ContentFactoryTest.php` | تست‌های PHPUnit |

### ۳.۲ پوشه‌ی محتوا: `content-lab/` (روی مک)

```
content-lab/
├── 00-inbox/            ویدیوهای خام ورودی (هر چیزی که کاربر می‌ریزد)
├── 01-styles/           سبک‌ها؛ هر سبک یک پوشه با نمونه‌ها (بعد از تحلیل جابه‌جا می‌شود)
├── 02-music/
│   ├── audio/           موسیقی ورودی
│   ├── from-video/      صدای استخراج‌شده از ویدیوها
│   ├── _extracted/      فایل‌های موقت تحلیل
│   └── library/         کتابخانه‌ی موسیقی؛ زیرپوشه‌ها به شکل «انرژی-تمپو» مثل medium-fast
├── 03-brand/            لوگو، فونت، رنگ‌های برند
├── 04-before-after/     جفت‌های قبل/بعد واقعی (فعلاً خالی؛ نسخه‌ی A بدون آن‌ها ساخته نمی‌شود)
├── 05-calendar/         خروجی تقویم
├── 06-productions/      نسخه‌های ساخته‌شده (هر پست یک پوشه با نسخه‌ها)
├── 07-reports/          گزارش‌های یادگیری و هفتگی
└── _system/
    ├── agent/agent.py   ایجنت مک (نسخه‌ی ۰.۳.۰)
    ├── tools/ingest.py  تحلیل ویدیو و موسیقی (فقط ffmpeg و پایتون استاندارد)
    ├── render/          پروژه‌ی Remotion (render.mjs، src/، public/)
    └── setup-mac.sh     راه‌اندازی اولیه‌ی مک
```

---

## ۴. پیکربندی و متغیرهای محیطی

### ۴.۱ متغیرهای `.env` (فقط نام؛ مقدار هرگز در سند نیست)

| متغیر | کاربرد | پیش‌فرض / یادداشت |
|---|---|---|
| `CONTENT_TELEGRAM_BOT_TOKEN` | توکن ربات تلگرام | فقط در `.env` یا تنظیمات؛ هرگز در چت |
| `CONTENT_TELEGRAM_BOT_USERNAME` | نام کاربری ربات | `creatvideovatanai_bot` |
| `CONTENT_TELEGRAM_CHAT_ID` | شناسه‌ی گروه (منفی) | بعد از «تشخیص گروه» در تنظیمات پر می‌شود |
| `CONTENT_TELEGRAM_WEBHOOK_SECRET` | بخش آخر آدرس وب‌هوک | رشته‌ی تصادفی |
| `CF_RUN_INLINE` | روشن کردن خودکار ایجنت هنگام دکمه‌ی ساخت | `true` |
| `CF_LAB_PATH` | مسیر `content-lab` | `base_path('content-lab')` |
| `CF_AGENT_TOKEN` | توکن ایجنت (اختیاری) | اگر خالی باشد، در تنظیمات ساخته و نگه‌داری می‌شود |
| `CF_WORKSPACE_ID` | شناسه‌ی فضای کاری | `1` (وطن) |
| `CF_LOCAL_URL` | آدرس داشبورد که ایجنت به آن وصل می‌شود | `APP_URL` |
| `CF_PRODUCT_URL` | الگوی لینک محصول | `https://aivatan.com/product/{slug}` |
| `CF_MODEL_FAST` / `CF_MODEL_WRITER` / `CF_MODEL_JUDGE` / `CF_MODEL_VISION` | مدل‌های OpenRouter | Haiku ۵.۵ / Sonnet ۵.۵ / Opus ۵.۵ / Sonnet ۵.۵ |
| `CF_AI_BUDGET_USD` | سقف هزینه‌ی ماهانه‌ی هوش مصنوعی | `30` |
| `CF_TELEGRAM_API_BASE` | آدرس API تلگرام | `https://api.telegram.org` |
| `OPENROUTER_API_KEY` | کلید OpenRouter | فقط در `.env` یا تنظیمات سرور |
| `FAL_API_KEY` | کلید fal.ai (در صورت استفاده) | فقط `.env` |
| `CF_PYTHON` | مسیر پایتون برای ایجنت | `python3` |

### ۴.۲ کانفیگ `config/content-factory.php` — بخش‌های مهم

- `host`: میان‌افزار (`web`، `auth:admin`)، پیشوند مسیر (`admin/content-factory`)، نام مسیرها (`cf.`)، منطقه‌ی زمانی `Asia/Tehran`، مدل محصول (`App\Models\Product`).
- `runner`: `inline` (روشن کردن خودکار ایجنت)، `extra_paths` (مسیرهای Homebrew برای PHP وب‌سرور).
- `ai.roles`: نقش‌ها به مدل‌های OpenRouter، و `monthly_budget_usd`.
- `telegram.topics`: نام تاپیک‌ها (بخش ۱۶).
- `metric_windows`: بازه‌های برداشت آمار (بخش ۱۷).
- `metric_fields`: ۱۵ شاخص آمار.
- `labels`: برچسب فارسی وضعیت‌ها.

---

## ۵. مدل داده

### ۵.۱ جدول‌ها (همه با پیشوند `cf_`)

| جدول | محتوا | کلیدهای مهم |
|---|---|---|
| `cf_calendars` | تقویم (یک تقویم برای هر دوره) | `title`, `status` (draft/approved/archived), `version` |
| `cf_calendar_items` | ردیف‌های تقویم (۳۷ ردیف اولیه) | `code` (D01…)، `date`, `time`, `format`, `pillar`, `sector`, `goal`, `audience`, `idea`, `hook`, `cta`, `style_slug`, `music_slug`, `product_type/product_id/product_key/product_label/product_url`, `engine`, `build_location`, `scenes` (JSON)، `story_specs` (JSON)، `stories`، `cover_text`، `assets`، `caption`، `hashtags`، `keywords`، `ai_plan`، `experiment`، `tracking_url`، `build_prompt` (JSON)، `status` |
| `cf_styles` | بانک سبک‌ها | `slug`, `name`, `template` (قالب رندر)، `recipe`، `palette`، `fonts`، `folder`، `sort`، `cover_path`، `status` (pending/active/testing/archived/locked)، `use_count`، `score` |
| `cf_references` | مرجع‌های ویدیویی (کانتکت‌شیت، رنگ، حرکت، تمپو) | `style_id` (قابل جابه‌جایی بین سبک‌ها) |
| `cf_music` | موسیقی‌ها | `folder`, `library_path`, `bpm`, `energy`, `license_status` (approved/unknown/forbidden)، `commercial_ok` |
| `cf_productions` | هر پست ساخته‌شده | `code`, `status`, `current_version`, `gene` (JSON)، `caption`، `hashtags`، `cover_path`، `si_post_id`، `si_campaign_id`، `auto_reply`، `keywords`، `scores` (JSON)، `score_total`، `score_confidence`، `build_prompt` (JSON)، `build_seconds`، `cost_usd`، `ig_permalink`، `published_at` |
| `cf_production_versions` | نسخه‌های هر پست | `version`, `video`, `cover`, `stories` (JSON)، `duration`، `qc` (JSON)، `notes`، `build_prompt`، `cost_usd` |
| `cf_segment_edits` | اصلاحیه‌ی بازه‌ای (ثانیه‌ی شروع و پایان) | `production_id`, `start`, `end`, `note` |
| `cf_prompt_versions` | لایه‌های پرامپت و نسخه‌ها | `layer`, `version`, `status` (draft/active/archived)، `depends_on`، `parent_id`، `body` |
| `cf_metric_snapshots` | آمار هر بازه | `production_id`, `window`, `metrics` (JSON)، `raw` (JSON)، `source` (manual/instagram)، `captured_at` |
| `cf_insights` | بینش‌ها | `kind` (learning/hypothesis/observation/opportunity)، `status` (pending/testing/accepted/rejected) |
| `cf_telegram_links` | نگاشت تاپیک‌ها و پیام‌ها | `topic`, `thread_id`, `message_id` |
| `cf_ai_usage` | مصرف هوش مصنوعی | `channel`, `model`, `purpose`, `tokens`, `cost_usd`, `workspace_id` |
| `cf_settings` | تنظیمات کلید/مقدار (JSON) برای هر فضای کاری | `agent`، `agent_token`، `telegram`، `last_sync`، `remote_server`، `custom_layers` |
| `cf_jobs` | صف کار برای ایجنت | `type`, `location` (local/server)، `status`, `progress`, `message`, `log`, `agent`, `attempts`, `claimed_at`, `finished_at`, `payload` (JSON)، `result` (JSON) |

### ۵.۲ مایگریشن‌ها (ترتیب اجرا)

1. `2026_10_15_000001_create_content_factory_tables` — جدول‌های پایه.
2. `2026_10_16_000001_content_factory_v02` — `cf_jobs` و ستون‌های تازه؛ ستون `engine` به ۲۵۵ کاراکتر رسید.
3. `2026_10_16_000002_seed_content_factory` — بارگذاری داده‌ی پایه (idempotent).
4. `2026_10_16_000003_content_factory_v03` — `build_prompt` روی آیتم و پست، `build_seconds`، `cost_usd` روی نسخه، `cover_path` روی سبک.
5. `2026_10_16_000004_seed_master_prompt` — لایه‌ی `master` (پرامپت جامع).

### ۵.۳ مدل‌ها

`Calendar`، `CalendarItem`، `Style`، `Reference`، `MusicTrack`، `Production`، `ProductionVersion`، `SegmentEdit`، `PromptVersion`، `MetricSnapshot`، `Insight`، `TelegramLink`، `AiUsage`، `Setting`، `Job`.

### ۵.۴ Seed (داده‌ی پایه) — `Seed::run()`

- `styles.json`: ۹ سبک. فعال: `contrast-punch`، `editorial-brand-story`، `kinetic-type-beat`، `before-after-split`، `stage-card-showcase`. آزمایشی: `ui-walkthrough`، `persian-tech-grid`، `tactile-desk`، `site-scroll-showcase`.
- `prompts.json`: ۱۶ لایه: `product`، `fidelity`، `style`، `hook`، `scenario`، `motion`، `music`، `caption`، `cta`، `qc`، `analysis`، `experiment`، `calendar`، `story`، `visual`، `master`.
- `calendar.json`: ۳۷ ردیف از ۲۰۲۶-۱۰-۱۶ تا ۲۰۲۶-۱۱-۲۱ (۲۴ مهر تا ۳۰ آبان ۱۴۰۵).
- `insights.json`: ۱۰ بینش.
- Seed **idempotent** است و ردیف‌هایی را که کاربر ویرایش کرده (`updated_at` بیش از ۵ ثانیه بعد از `created_at` و صحنه‌ها موجود) یا تقویم قفل‌شده را دست نمی‌زند.

---

## ۶. مسیرها (Routes)

### ۶.۱ داشبورد — پیشوند `admin/content-factory`، نام `cf.*`، میان‌افزار `web` + `auth:admin`

| مسیر | نام | کار |
|---|---|---|
| `GET /` | `cf.overview` | فرماندهی |
| `GET /calendar` | `cf.calendar` | تقویم (نمای تقویم و جدول) |
| `PATCH /calendar/items/{item}` | `cf.calendar.item` | ویرایش سریع ردیف |
| `GET /calendar/items/{item}` | `cf.item` | صفحه‌ی کامل پست (۰ تا ۱۰۰) |
| `GET /calendar/items/{item}/status` | `cf.item.status` | وضعیت زنده (برای polling) |
| `POST /calendar/items/{item}/save` | `cf.item.save` | ذخیره‌ی همه‌ی بخش‌ها |
| `POST /calendar/items/{item}/order` | `cf.item.build` | ذخیره و سفارش ساخت |
| `POST /calendar/items/{item}/generate` | `cf.item.generate` | بازنویسی صحنه‌ها با پرامپت جامع |
| `POST /calendar/items/{item}/undo` | `cf.item.undo` | بازگشت به نسخه‌ی قبل پرامپت |
| `POST /calendar/items/{item}/prompt-reset` | `cf.item.prompt-reset` | برگشت پرامپت به پیش‌فرض |
| `POST /calendar/items/{item}/metrics` | `cf.item.metrics` | ثبت دستی آمار |
| `POST /calendar/items/{item}/snapshot` | `cf.item.snapshot` | برداشت لحظه‌ای آمار |
| `POST /calendar/items/{item}/build` | `cf.calendar.build` | ساخت از فهرست تقویم |
| `POST /calendar/{calendar}/approve` | `cf.calendar.approve` | تأیید و قفل تقویم |
| `POST /calendar/{calendar}/new-version` | `cf.calendar.version` | نسخه‌ی جدید تقویم |
| `GET /productions` | `cf.productions` | صف ساخت و انتشار |
| `GET /productions/feed` | `cf.productions.feed` | فید زنده‌ی صف |
| `GET /productions/{production}` | `cf.productions.show` | جزئیات پست |
| `POST /productions/{production}/approve` | `cf.productions.approve` | تأیید |
| `POST /productions/{production}/reject` | `cf.productions.reject` | رد |
| `POST /productions/{production}/edits` | `cf.productions.edit` | اصلاحیه‌ی بازه‌ای |
| `POST /productions/{production}/published` | `cf.productions.published` | ثبت انتشار |
| `PATCH /productions/{production}/caption` | `cf.productions.caption` | ویرایش کپشن |
| `POST /productions/{production}/caption/regenerate` | `cf.productions.caption.regen` | بازنویسی کپشن |
| `POST /productions/{production}/rebuild` | `cf.productions.rebuild` | ساخت مجدد |
| `POST /productions/{production}/instagram` | `cf.productions.instagram` | ساخت کمپین کامنت→دایرکت |
| `GET /styles` | `cf.styles` | بانک سبک (شبکه‌ی ۴تایی) |
| `GET /styles/create` | `cf.styles.create` | فرم سبک جدید |
| `POST /styles` | `cf.styles.store` | ذخیره‌ی سبک جدید |
| `GET /styles/{style}` | `cf.styles.show` | پرونده‌ی سبک |
| `PATCH /styles/{style}` | `cf.styles.update` | ویرایش سبک |
| `DELETE /styles/{style}` | `cf.styles.destroy` | حذف سبک |
| `POST /styles/{style}/duplicate` | `cf.styles.duplicate` | کپی سبک |
| `PATCH /styles/references/{reference}` | `cf.styles.assign` | جابه‌جایی مرجع بین سبک‌ها |
| `DELETE /styles/references/{reference}` | `cf.styles.reference.delete` | حذف مرجع |
| `GET /music` | `cf.music` | کتابخانه‌ی موسیقی |
| `PATCH /music/{track}` | `cf.music.update` | ویرایش موسیقی (مجوز، پوشه) |
| `GET /prompts` | `cf.prompts` | آزمایشگاه پرامپت |
| `POST /prompts` | `cf.prompts.store` | ذخیره‌ی نسخه (با گزینه‌ی «ذخیره و فعال کن») |
| `POST /prompts/layers` | `cf.prompts.layer` | افزودن لایه‌ی دلخواه |
| `DELETE /prompts/layers/{layer}` | `cf.prompts.layer.delete` | حذف لایه |
| `DELETE /prompts/{prompt}` | `cf.prompts.destroy` | حذف نسخه |
| `POST /prompts/{prompt}/activate` | `cf.prompts.activate` | فعال‌سازی نسخه |
| `POST /prompts/{prompt}/test` | `cf.prompts.test` | آزمایش پرامپت |
| `GET /insights` | `cf.insights` | بینش‌ها |
| `POST /insights/{insight}/decide` | `cf.insights.decide` | پذیرش/رد بینش |
| `POST /jobs` | `cf.jobs.store` | ثبت کار (ingest_videos، ingest_music، sync_files، doctor) |
| `GET /jobs/feed` | `cf.jobs.feed` | فید کارها |
| `POST /jobs/{job}/cancel` | `cf.jobs.cancel` | لغو کار |
| `GET /settings` | `cf.settings` | تنظیمات و بررسی سلامت |
| `POST /settings/sync` | `cf.settings.sync` | همگام‌سازی content-lab |
| `POST /settings/telegram` | `cf.settings.telegram` | تشخیص گروه، تاپیک‌ها، وب‌هوک، تست، ذخیره |
| `POST /settings/agent-install` | `cf.settings.agent-install` | نصب دائمی ایجنت (LaunchAgent) |
| `POST /settings/agent-token` | `cf.settings.agent-token` | ساخت/تغییر توکن ایجنت |
| `GET /asset/{file}` | `cf.asset` | CSS و JS پکیج |

### ۶.۲ API ایجنت و تلگرام — پیشوند `api/content-factory`، نام `cf.api.*`، میان‌افزار `api`

| مسیر | کار |
|---|---|
| `POST /agent/hello` | اعلام حضور ایجنت (نسخه، میزبان، کارهای در انتظار) |
| `POST /agent/next` | دریافت کار بعدی (long-poll) |
| `POST /agent/jobs/{job}/progress` | گزارش پیشرفت |
| `POST /agent/jobs/{job}/complete` | پایان کار (موفق/خطا) |
| `POST /agent/upload` | آپلود فایل کوچک |
| `POST /agent/chunk` | آپلود تکه‌ای (هر تکه ۱ مگابایت، سرور جمع می‌کند) |
| `POST /agent/reference` | ثبت مرجع تحلیل‌شده |
| `POST /agent/music` | ثبت موسیقی تحلیل‌شده |
| `POST /agent/moved` | ثبت جابه‌جایی فایل‌ها |
| `POST /agent/assignments` | همگام‌سازی تخصیص مرجع به سبک |
| `POST /agent/productions/{production}/version` | ثبت نسخه‌ی رندرشده |
| `POST /telegram/{secret}` | وب‌هوک تلگرام (`callback_query`) |

احراز هویت API ایجنت با هدر `X-CF-Token`. توکن اشتباه پاسخ JSON با کد `401` می‌دهد (نه HTML).

---

## ۷. صفحات داشبورد

منو داخل «استودیو تولید» در `partials/sidebar-menu` قرار دارد و فقط وقتی `admin/content-factory*` باز است فعال می‌شود.

| صفحه | محتوا |
|---|---|
| **فرماندهی** | وضعیت کلی، کارهای در جریان، هشدارها |
| **تقویم** | نمای تقویم ماهانه و جدول؛ کنترل توازن (هشدار اگر یک صنف یا سبک زیاد یا کم باشد)؛ هر روز به صفحه‌ی پست لینک دارد |
| **صفحه‌ی پست** (`items/show`) | بخش‌ها: `s-plan` (استراتژی)، `s-product`، `s-hook`، `s-scenes` (ویرایشگر صحنه‌به‌صحنه)، `s-stories`، `s-look` (سبک و موسیقی با پیش‌نمایش)، `s-caption`، `s-prompt` (پرامپت جامع)، `s-build` (نقشه‌ی ساخت)، `s-prod` (نسخه‌ها و کارهای زنده)، `s-stats` (آمار). نوار ذخیره‌ی چسبان پایین صفحه؛ ستون کناری با پلیر، دکمه‌ی ساخت، انتخاب پرامپت، جاب‌ها |
| **صف ساخت و انتشار** (`productions`) | ۶ کارت آمار، جستجو، فیلتر قالب، ۱۰ ستون، صفحه‌بندی، به‌روزرسانی زنده (هر ۴ ثانیه وقتی کار در جریان است، ۱۵ ثانیه در غیر این صورت) |
| **جزئیات پست** (`productions/show`) | پلیر، ژن محتوا، اصلاحیه‌ی بازه‌ای، نسخه‌ها، کپشن، ثبت انتشار |
| **بانک سبک** (`styles`) | شبکه‌ی ۴ کارت در هر ردیف؛ پرونده‌ی سبک با فیلدها، مراجع، پالت، فونت، تصویر کارت، کپی، حذف |
| **کتابخانه‌ی موسیقی** (`music`) | فهرست موسیقی‌ها، انرژی، تمپو، وضعیت مجوز |
| **آزمایشگاه پرامپت** (`prompts`) | فهرست لایه‌ها در کنار، ویرایشگر لایه‌ی `master`، آزمایش، تاریخچه‌ی نسخه، افزودن و حذف لایه‌ی دلخواه، «ذخیره و فعال کن» |
| **بینش‌ها** (`insights`) | پیشنهادها و فرضیه‌ها با دکمه‌ی پذیرش یا رد |
| **تنظیمات** (`settings`) | بررسی سلامت (ffmpeg، node، ایجنت، Remotion)، همگام‌سازی، فرم تلگرام، نصب ایجنت، توکن ایجنت |

### ۷.۱ راهنمای «؟»

هر بخش یک نشانگر «؟» دارد. با hover یا focus، متن کامل راهنما از `config/help.php` نمایش داده می‌شود (کلیدهای مثل `overview`، `agent`، `agent_install`، `jobs`، `calendar.*`، `production.*`، `styles`، `style.show`، `prompts`، `insights`، `settings`، `telegram`، `queue`، `item`، `item.plan`، `item.hook`، `item.prompt`، `item.stats`). متن‌ها را می‌توان در همان فایل ویرایش کرد.

### ۷.۲ ظاهر

- رنگ‌ها فقط از متغیرهای CSS (`var(--primary)`، `var(--on-primary)`، …) می‌آیند؛ هیچ کد هگز ثابتی در `cf.css` نیست.
- بنفش حذف شده است: در `public/admin/css/design-tokens.css` یک override با `:root:root` و `body.light` برای `--accent` گذاشته شده؛ `--on-primary` در روز سفید و در شب لایم است.
- ثابت JS `#a07af5` در `admin.js`، `crm.js` و `crm_js_final.js` تعویض شده.
- کلاس‌های بنفش داخل `all-logs.blade.php` و `instagram-dashboard.blade.php` اصلاح شده‌اند.
- **هشدار:** همه‌ی صفحه‌های داشبورد اصلی تک‌تک بررسی نشده‌اند (بخش ۲۲).

---

## ۸. تقویم و صفحه‌ی پست

- تقویم **۳۷ ردیف** دارد، از ۲۴ مهر تا ۳۰ آبان ۱۴۰۵ (ابتدا باید جابه‌جا شود؛ بخش ۲۲).
- هر ردیف: فرمت (ریلز، اسلاید، پوستر، استوری)، ژن (ستون، صنف، هدف، مخاطب، هوک، CTA)، سبک، موسیقی، محصول، صحنه‌ها، استوری‌ها، کلمه‌ی کامنت، کپشن، هشتگ، نقشه‌ی هوش مصنوعی، آزمایش، ردیابی.
- مناسبت‌ها: فاطمیه (احتمالی)، جمعه‌ی سیاه (۶ آذر)، یلدا (۳۰ آذر، بیرون از بازه‌ی فعلی).
- آزمایش‌ها هفتگی‌اند.
- **ساخت:** «ذخیره و سفارش ساخت» → `cf_productions` + نسخه‌ی اول + `cf_jobs` (`render`).
- **Undo:** بازگشت به نسخه‌ی قبل پرامپت (تاریخچه‌ی ۵ مورد آخر در `build_prompt`).

---

## ۹. ساخت پست (Builder)

`Vatan\Content\Services\Builder`:

- `production()`: ساخت یا بازیابی پست برای یک ردیف.
- `caption()`: کپشن ترکیبی از متن ردیف و هشتگ‌ها.
- `build()`: پرامپت جامع را کامپایل کرده در `build_prompt` ذخیره می‌کند و `prompt` را در payload کار می‌گذارد.
- `spec()`: مشخصات خروجی بر اساس فرمت:

| فرمت | خروجی‌ها |
|---|---|
| ریلز (`reel`) | `reel.mp4` (ترکیب `VatanReel`) + `cover.png` (ترکیب `Cover`) |
| اسلایدشو (`slideshow`) | `slide-N.png` برای هر صحنه (ترکیب `Slide`) |
| پست (`post`) | `post.png` (ترکیب `Poster`) |
| استوری (`stories`) | `story-N.png` (ترکیب `Story`، فریم ۶۰) |

- `resolve()`: مسیرها را تبدیل می‌کند: `lab:` (پوشه‌ی content-lab)، `storage:`، `media/`، `brand/`.

---

## ۱۰. پرامپت جامع (لایه‌ی `master`)

### ۱۰.۱ ساختار

متن `master` در `prompts.json` به **۱۳ بخش** با سرتیتر `## ` تقسیم می‌شود:

1. ماموریت
2. برند
3. محصول
4. هوک
5. روایت و صحنه‌ها
6. سبک
7. موسیقی
8. وفاداری و تصاویر
9. کاور و استوری‌ها
10. کپشن
11. قرارداد خروجی (JSON)
12. چک‌لیست QC
13. یادداشت

### ۱۰.۲ متغیرها

حدود ۴۰ متغیر با فرم `{{name}}`، از جمله: `code`، `date`، `time`، `format`، `duration`، `pillar`، `sector`، `goal`، `audience`، `idea`، `experiment`، `product`، `product_url`، `keywords`، `hook`، `hook_family`، `narrative`، `scenes`، `style`، `theme`، `style_desc`، `style_recipe`، `motion`، `palette`، `music`، `bpm`، `beat_frames`، `music_start`، `fidelity`، `images`، `cover_text`، `stories`، `caption`، `note`.

### ۱۰.۳ سرویس `MasterPrompt`

- `template()`: متن پیش‌فرض لایه.
- `sections()`: تقسیم به بخش‌ها.
- `compile()`: خروجی `system`، `user` و `text` (پیش‌نمایش نهایی).
- `generate()`: بازنویسی صحنه‌ها با نقش `writer` از OpenRouter، یا اگر کلید نبود، ساخت کار `ai_write` برای Claude Code روی مک.
- `apply()`: خروجی JSON را روی آیتم می‌نشاند و تاریخچه را نگه می‌دارد.
- `undo()`: بازگشت.

### ۱۰.۴ ویرایش برای هر پست

برای هر پست می‌توان بخش‌ها را **ویرایش، خاموش، جابه‌جا، حذف و اضافه** کرد. نتیجه در `build_prompt` ذخیره می‌شود: `{system, sections[{title, body, on}], custom}`. ریست به پیش‌فرض با `prompt-reset`.

---

## ۱۱. لایه‌های پرامپت (۱۶ لایه)

هر لایه نسخه‌بندی دارد (`draft` / `active` / `archived`)، می‌تواند وابسته به لایه‌ی دیگر باشد (`depends_on`)، و قابل آزمایش است. لایه‌ی `master` همان پرامپت جامع بخش ۱۰ است. لایه‌های دیگر شامل: محصول، وفاداری، سبک، هوک، سناریو، حرکت، موسیقی، کپشن (انسانی)، CTA، QC، تحلیل، آزمایش، تقویم، استوری، بصری. افزودن لایه‌ی دلخواه در `cf.prompts.layer` و ذخیره در `custom_layers` تنظیمات.

---

## ۱۲. بانک سبک‌ها

- هر سبک: `slug`، نام، `template` (کدام ترکیب رندر)، `recipe` (دستور صحنه)، `palette`، `fonts`، `folder`، `sort`، `cover_path`، `status`.
- وضعیت‌ها: `pending` (در انتظار تأیید)، `active`، `testing`، `archived`، `locked`.
- مراجع (ویدیوهای تحلیل‌شده) به سبک تخصیص داده می‌شوند و بین سبک‌ها قابل جابه‌جایی‌اند.
- کپی سبک و حذف (با تأیید) در دسترس است؛ حذف مرجع جدا.
- قالب‌های رندر موجود: `punch`، `editorial`، `kinetic`، `techgrid`، `stage`، `tactile` (بخش ۱۵).

---

## ۱۳. موسیقی و کتابخانه

- موسیقی‌های `02-music/library/<انرژی>-<تمپو>/` ثبت می‌شوند (مثلاً `medium-fast`).
- `ingest_music` تمپو (با autocorrelation)، انرژی و نقاط شروع دراپ را محاسبه می‌کند.
- `license_status`: `approved` (مجوز تأییدشده)، `unknown` (نامشخص؛ قبل از انتشار تأیید شود)، `forbidden` (ممنوع؛ هرگز انتخاب نمی‌شود).
- ویدیوهای تحلیل‌شده، صدایشان به `02-music/from-video/` می‌رود.
- **قاعده:** موسیقی با مجوز `unknown` در نسخه‌ی تجاری استفاده نشود.

---

## ۱۴. ایجنت مک

### ۱۴.۱ فایل

`content-lab/_system/agent/agent.py` — نسخه **۰.۳.۰**.

### ۱۴.۲ پرچم‌ها

| پرچم | کار |
|---|---|
| `--server URL` | آدرس داشبورد (چندبار مجاز) |
| `--token TOKEN` | توکن ایجنت (چندبار مجاز) |
| `--once` | یک بار کار بگیر و خارج شو |
| `--interval N` | فاصله‌ی پرس‌وجو به ثانیه (پیش‌فرض ۵) |
| `--install` | نصب LaunchAgent `com.vatan.content-agent` در `~/Library/LaunchAgents` و نوشتن `config.json` و `launchctl bootstrap` |
| `--doctor` | بررسی ابزارها: ffmpeg، node، brew، وجود node_modules در Remotion |

### ۱۴.۳ کارها (job types)

| نوع | کار |
|---|---|
| `ingest_videos` | تحلیل ویدیوهای `00-inbox`: برش‌ها، کانتکت‌شیت، رنگ‌ها، حرکت، تمپو، تشخیص سبک (Claude با کانتکت‌شیت یا نزدیک‌ترین سبک بر اساس طول شات و حرکت)؛ انتقال به `01-styles` |
| `ingest_music` | تحلیل موسیقی‌ها و انتقال به `library` |
| `sync_files` | همگام‌سازی فایل‌ها با سرور |
| `render` | اجرای Remotion (`node render.mjs <composition> <props> <out> [--still] [--frame=N]`) و آپلود |
| `ai_write` | اجرای Claude Code CLI (`claude -p`) برای بازنویسی صحنه‌ها |
| `doctor` | گزارش سلامت |

وضعیت کار: `queued` → `running` → `done` / `failed` / `cancelled`. کار `running` بیش از ۳۰ دقیقه بی‌پاسخ بماند دوباره در صف می‌آید. کار `render` ناموفق به `queued` برمی‌گردد.

### ۱۴.۴ تحلیل بدون numpy

`ingest.py` فقط از ffmpeg (فریم‌های خام برای رنگ و حرکت، تفاضل خاکستری) و پایتون استاندارد استفاده می‌کند. نصب numpy یا opencv دیگر لازم نیست. اگر ffmpeg یا node نباشد، `ensure_tool()` با Homebrew نصبشان می‌کند.

### ۱۴.۵ آپلود

فایل‌های بزرگ (مثل ویدیوی ۶۶ ثانیه‌ای) با `/agent/chunk` و تکه‌های ۱ مگابایتی فرستاده می‌شوند؛ سرور جمع می‌کند. خطای قدیمی `422 failed to upload` با این حل شد.

### ۱۴.۶ اجرای دکمه‌ها

اگر `CF_RUN_INLINE=true` باشد، `JobController::spawnLocal()` ایجنت را با `nohup` در پس‌زمینه روشن می‌کند. `agentOnline()` بررسی می‌کند که `last_seen` کمتر از ۴۵ ثانیه باشد.

### ۱۴.۷ نصب دائمی

تنظیمات → «نصب ایجنت» یا `python3 agent.py --install --server <آدرس> --token <توکن>`. بعد از نصب، ایجنت با ورود مک بالا می‌آید.

---

## ۱۵. رندر (Remotion)

مسیر: `content-lab/_system/render/`. فایل `render.mjs` و `src/`: `Root.tsx`، `VatanReel.tsx`، `Stills.tsx`، `parts.tsx`، `brand.ts`، `index.ts`. دارایی‌ها در `public/`: `fonts/`، `brand/`، `media/`، `lab-cache/`.

| ترکیب | نوع | ابعاد / نرخ |
|---|---|---|
| `VatanReel` | ویدیو | ۱۰۸۰×۱۹۲۰، ۳۰ فریم بر ثانیه |
| `Story` | تصویر | ۹:۱۶ |
| `Cover` | تصویر | کاور ریلز |
| `Slide` | تصویر | ۱۰۸۰×۱۳۵۰ |
| `Poster` | تصویر | ۱۰۸۰×۱۳۵۰ |

- **نوع صحنه‌ها:** `text`، `card`، `split`، `grid`، `stat`، `list`، `quote`، `image`، `cta`.
- **تم‌ها:** `punch`، `editorial`، `kinetic`، `techgrid`، `stage`، `tactile`.
- **برند (`brand.ts`):** اصلی `#16594f`، تأکید `#C2FD75`، لایم `#CFFC00`، تیره `#030f09`.
- **فونت‌ها:** مدام (ModamFaNum، وزن ۳۰۰ تا ۹۰۰)، یکان‌بخ (Medium، Bold، Heavy، Fat)، Peyda-Black.
- **لوگو:** `public/brand/badge.png`، `wordmark-light.png`، `wordmark-dark.png`؛ در `parts.tsx` به `media/logo.svg` اشاره شده (بررسی شود که فایل موجود باشد).
- **مرورگر رندر:** متغیر `CHROME` اگر لازم باشد.
- **نمونه‌ی موجود:** پست D01 (هوک «عکس محصولت رو با موبایل گرفتی؟»، سبک `contrast-punch`، ۱۳ ثانیه، ۱۰۸ BPM، دراپ در ۴٫۵ ثانیه) با نسخه‌ی v1 ساخته شده (بدون قبل/بعد واقعی، نسخه‌ی B).

### ۱۵.۱ قاعده‌ی QC

هر نسخه باید این موارد را داشته باشد: ۹:۱۶، ناحیه‌ی امن متن، فونت فارسی، بدون لوگوی برند دیگر، موسیقی مجاز، بدون ادعای اغراق‌آمیز.

---

## ۱۶. تلگرام

- **ربات:** `@creatvideovatanai_bot` (توکن فقط در `.env`).
- **گروه:** از طریق دعوت‌نامه‌ی گروه؛ ربات باید ادمین با دسترسی به **تاپیک‌ها (Topics)** باشد.
- **تاپیک‌ها** (`telegram.topics`): سبک‌ها و نمونه‌ها، موسیقی، هوک‌ها، پرامپت‌ها، در انتظار تأیید، اصلاحیه‌ها، نسخه‌های نهایی، گزارش یادگیری. ساخت با `createForumTopic` و `setupTopics()`.
- **پیش‌نمایش:** `notifyReview()` ویدیو یا تصویر را با دکمه‌ها می‌فرستد:
  - `cf:approve:{production_id}` ← ✅ تأیید
  - `cf:revise:{production_id}` ← ✏️ نیاز به اصلاح
- **وب‌هوک:** `POST /api/content-factory/telegram/{secret}`؛ `callback_query` را پردازش می‌کند.
- **روال اتصال (فقط روی سرور، بعد از تأیید):**
  1. کاربر یک پیام در گروه می‌فرستد.
  2. «تشخیص گروه» شناسه‌ی گروه را از `getUpdates` می‌گیرد و `CONTENT_TELEGRAM_CHAT_ID` را پر می‌کند.
  3. «ساخت تاپیک‌ها» را اجرا کن.
  4. وب‌هوک را تنظیم کن (`deleteWebhook` قبلی لازم است؛ وب‌هوک n8n قبلاً برداشته شده).
  5. «تست» بفرست.
- **توکن را هرگز در چت یا فایل قرار نده**؛ از تنظیمات داشبورد وارد شود.

---

## ۱۷. اینستاگرام هوشمند، آمار و امتیاز

### ۱۷.۱ اتصال

`InstagramBridge` به `App\Models\SmartInstagram\Post` و سرویس‌های `PostSyncService` (`syncOne`، `syncRecent`، `shortcode`) و `PostCampaignService` (`defaults`، `save`) وصل است. کمپین کامنت→دایرکت با کلمه‌ی کلیدی و لینک محصول ساخته می‌شود.

### ۱۷.۲ بازه‌های آمار

`10m`، `30m`، `1h`، `3h`، `6h`، `12h`، `24h`، `72h`، `7d`، `28d` و `live`. دستور `content:metrics` هر ۱۰ دقیقه اجرا می‌شود (`withoutOverlapping(20)`، `runInBackground`).

### ۱۷.۳ شاخص‌ها (۱۵ مورد)

بازدید/پخش، دسترسی، لایک، کامنت، ذخیره، اشتراک، فالوور جدید، بازدید پروفایل، کلیک لینک، دایرکت/سناریو، ثبت‌نام، خرید پلن، بازدید استوری، کلیک استوری، میانگین تماشا.

ثبت دستی از صفحه‌ی پست (`cf.item.metrics`) یا برداشت لحظه‌ای (`cf.item.snapshot`).

### ۱۷.۴ امتیاز (`Scorer`)

- وزن‌ها: **توجه ۰٫۳۰**، **تعامل ۰٫۳۵**، **کسب‌وکار ۰٫۲۵**، **اقتصادی ۰٫۱۰** (اقتصادی فقط وقتی هزینه ثبت شده باشد).
- تعامل = ذخیره×۳ + اشتراک×۴ + کامنت×۲ + لایک.
- امتیاز **نسبی به همتاها** است؛ ۵۰ یعنی میانگین.
- اطمینان: زیر ۲۴ ساعت = «نامشخص»، زیر ۵ همتا = «کم»، زیر ۱۵ = «متوسط»، بقیه = «زیاد».

---

## ۱۸. هوش مصنوعی و هزینه

- `Services\Ai` با OpenRouter کار می‌کند. اگر کلاس `Vatan\Seo\Ai\OpenRouterClient` موجود باشد از آن استفاده می‌کند؛ وگرنه HTTP مستقیم.
- نقش‌ها: `fast` = Haiku ۵.۵ (حجم)، `writer` = Sonnet ۵.۵ (نوشتن)، `judge` = Opus ۵.۵ (داوری و گزارش هفتگی)، `vision` = Sonnet ۵.۵ (تحلیل تصویر).
- پرامپت سیستم با `cache_control: ephemeral` ارسال می‌شود.
- هر تماس در `cf_ai_usage` ثبت می‌شود (کانال، مدل، هدف، توکن، هزینه).
- **محدودیت جغرافیایی:** OpenRouter از ایران مستقیم کار نمی‌کند؛ روی سرور از طریق پل Cloudflare کار می‌کند. روی مک باید از مسیر سرور یا Claude Code مک استفاده شود.
- کلید سندباکس نامعتبر بود («User not found»)؛ کلید واقعی فقط در محیط سرور و `.env`.

---

## ۱۹. نقش دستیار Claude Code روی مک

کار `ai_write` یک پرامپت (ساخته‌شده از لایه‌ی `master`) را به `claude -p` می‌دهد و خروجی JSON صحنه‌ها را برمی‌گرداند. مسیر باینری با `claude_bin()` پیدا می‌شود. این مسیر هزینه‌ی API ندارد (از اشتراک Claude Pro استفاده می‌کند).

---

## ۲۰. دستورات راه‌اندازی

### ۲۰.۱ روی مک (توسعه‌ی محلی)

```bash
# ۱. کپی پکیج و بارگذاری خودکار (در محیط local، مهاجرت یک‌بار برای هر نسخه اجرا می‌شود)
cd <ریشه‌ی پروژه>
composer dump-autoload
php artisan migrate --path=packages/vatan-content/database/migrations

# ۲. داده‌ی پایه
php artisan content:seed

# ۳. آماده‌سازی content-lab
bash content-lab/_system/setup-mac.sh

# ۴. بررسی ایجنت
python3 content-lab/_system/agent/agent.py --doctor

# ۵. اجرای ایجنت (یک بار برای آزمایش)
python3 content-lab/_system/agent/agent.py --server http://127.0.0.1:8000 --token <CF_AGENT_TOKEN> --once

# ۶. نصب دائمی
python3 content-lab/_system/agent/agent.py --install --server <آدرس داشبورد> --token <CF_AGENT_TOKEN>

# ۷. همگام‌سازی و آمار
php artisan content:sync-lab
php artisan content:metrics
```

### ۲۰.۲ روی سرور (aivatan.com)

1. کد پکیج را دیپلوی کن (فقط با تأیید صاحب پروژه).
2. `php artisan migrate` (مایگریشن‌ها با `--path` اجرا شوند).
3. `php artisan content:seed` (idempotent است).
4. تنظیمات → تلگرام → «تشخیص گروه» → «ساخت تاپیک‌ها» → «وب‌هوک» → «تست».
5. تنظیمات → توکن ایجنت را بساز و روی مک در `--token` بگذار.
6. بررسی زمان‌بندی: `content:metrics` در `schedule:run` (cron هر دقیقه‌ی سرور).

### ۲۰.۳ دستورهای Artisan

| دستور | کار |
|---|---|
| `content:seed` | بارگذاری داده‌ی پایه |
| `content:sync-lab` | همگام‌سازی content-lab با پایگاه داده |
| `content:metrics` | برداشت آمار بازه‌های سررسیده |

---

## ۲۱. تست‌ها

### ۲۱.۱ PHPUnit

```bash
DB_DATABASE=<مسیر>/database/test.sqlite vendor/bin/phpunit packages/vatan-content/tests
```

آخرین اجرای گزارش‌شده: **۷ تست و ۱۰۶ assert سبز** (در سندباکس قبلی؛ این اجرا باید دوباره انجام شود).

### ۲۱.۲ تست مرورگر

Playwright در `/opt/npm-tools/node_modules/playwright` (فقط سندباکس). در مک می‌توان با هر ابزار مرورگر همان مسیرها را باز کرد.

---

## ۲۲. مشکلات شناخته‌شده و قوانین کار

| # | مشکل | وضعیت | اقدام |
|---|---|---|---|
| ۱ | تاریخ تقویم از ۱۶ اکتبر شروع می‌شود و گذشته است | باز | همه‌ی ردیف‌ها را طوری جابه‌جا کن که اولین پست حداقل ۱۸ اکتبر باشد؛ انتهای تقویم ۳۰ آبان بماند |
| ۲ | ایجنت مک هنوز روی نسخه‌ی ۰.۳ تأیید نشده؛ لاگ قدیمی خطای numpy داشت | باز | `--doctor` و `ingest_videos` با یک فایل واقعی |
| ۳ | OpenRouter از ایران مستقیم کار نمی‌کند | محدودیت | مسیر سرور یا Claude Code مک |
| ۴ | کلید OpenRouter سندباکس نامعتبر بود | باز | کلید واقعی فقط در سرور |
| ۵ | توکن تلگرام در `.env` مک است؛ هرگز در چت یا سند نیاید | قاعده | — |
| ۶ | صفحه‌ی `items/show` از `$disk->url('')` برای `storageBase` استفاده می‌کند | بررسی | روی سرور مسیر فایل‌های عمومی را تست کن |
| ۷ | همه‌ی صفحه‌های داشبورد بعد از حذف بنفش بررسی نشده‌اند | باز | تک‌تک صفحه‌ها را بعد از دیپلوی ببین |
| ۸ | گروه تلگرام و تاپیک‌ها هنوز ساخته نشده‌اند | باز | بعد از پیام کاربر در گروه |
| ۹ | کیفیت خروجی پرامپت جامع فقط بعد از چند پست واقعی ارزیابی شده است | باز | بعد از تست گرم، نتیجه را در لایه‌ی `master` نسخه‌بندی کن |
| ۱۰ | D01 اولین نمونه است؛ قبل/بعد واقعی وجود ندارد (نسخه‌ی B) | باز | با رسیدن نمونه‌ی واقعی نسخه‌ی A |
| ۱۱ | موسیقی‌های با مجوز نامشخص در نسخه‌ی تجاری | قاعده | فقط `approved` |
| ۱۲ | رندر ویدیو فعلاً روی مک (ایجنت + Remotion) انجام می‌شود؛ اگر مک خاموش باشد صف منتظر می‌ماند | محدودیت طراحی | بررسی وضعیت ایجنت در صفحه‌ی تنظیمات |

---

## ۲۳. کارهای باقی‌مانده (به ترتیب)

1. **جابه‌جایی تاریخ تقویم** به بازه‌ی آینده (حداقل ۱۸ اکتبر تا ۳۰ آبان).
2. **تست ایجنت روی مک:** `--doctor`، سپس `ingest_videos` و `ingest_music` با یک فایل واقعی از `00-inbox`.
3. **تأیید دیپلوی نسخه‌ی ۰.۳** روی aivatan.com (بعد از تأیید صاحب پروژه).
4. **تلگرام:** کاربر یک پیام در گروه بفرستد؛ سپس تشخیص گروه، ساخت تاپیک‌ها، وب‌هوک و تست.
5. **تست گرم کامل:**
   - تحلیل یک ویدیو از `00-inbox` و انتقال به سبک.
   - باز کردن D01 از صفحه‌ی پست، ساخت، دیدن پیش‌نمایش در تلگرام.
   - تأیید (از داشبورد یا تلگرام).
   - ثبت انتشار.
   - ثبت آمار دستی و برداشت لحظه‌ای و دیدن امتیاز.
6. **بررسی بصری همه‌ی صفحه‌ها** برای نبود بنفش و خطای PHP.
7. **تکمیل نمونه‌ی قبل/بعد** از کاربر و ساخت نسخه‌ی A.
8. **بازبینی پرامپت‌ها** بعد از چند پست واقعی و تصمیم درباره‌ی بینش‌ها.

---

## ۲۴. چک‌لیست تست گرم (معیار پذیرش)

- [ ] ایجنت با `--doctor` همه‌ی ابزارها را «آماده» نشان می‌دهد.
- [ ] `ingest_videos` یک ویدیوی واقعی را بدون خطای numpy تحلیل می‌کند و به `01-styles` منتقل می‌کند.
- [ ] `ingest_music` تمپو و انرژی را ثبت می‌کند.
- [ ] تقویم از تاریخ امروز به بعد است و ۳۷ ردیف دارد.
- [ ] صفحه‌ی پست (۰ تا ۱۰۰) باز می‌شود؛ همه‌ی بخش‌ها ذخیره می‌شوند.
- [ ] «ذخیره و سفارش ساخت» یک نسخه‌ی v1 می‌سازد و ایجنت خودکار روشن می‌شود.
- [ ] رندر `VatanReel` با ۹ صحنه و کاور و استوری تولید می‌شود.
- [ ] نسخه روی سرور آپلود و در صف با وضعیت «در انتظار تأیید» دیده می‌شود.
- [ ] پیش‌نمایش در تلگرام با دکمه‌ها می‌آید؛ «تأیید» وضعیت را به «تأییدشده» می‌برد.
- [ ] «ثبت انتشار» وضعیت را به «منتشرشده» می‌برد.
- [ ] آمار دستی ذخیره و در نمودار دیده می‌شود؛ امتیاز محاسبه می‌شود.
- [ ] دکمه‌ی «بازنویسی با پرامپت جامع» صحنه‌ها را عوض می‌کند و «بازگشت» آن‌ها را برمی‌گرداند.
- [ ] هیچ کد هگز بنفش یا خطای PHP در صفحه‌ها نیست.
- [ ] هیچ توکنی در لاگ یا صفحه‌ی عمومی دیده نمی‌شود.

---

## ۲۵. نقشه‌ی سریع برای ChatGPT (یا هر ابزار دیگر)

1. این سند را بخوان؛ بعد `content-lab/_system/agent/agent.py` و `packages/vatan-content/src/Services/Builder.php` و `MasterPrompt.php` را.
2. مسیرها را با بخش ۶ مقایسه کن؛ اگر چیزی نبود، فایل `routes/web.php` را بخوان.
3. قبل از هر تغییر، وضعیت را با `--doctor` و `php artisan migrate:status` بررسی کن.
4. هیچ مقداری از `.env` را نقل نکن.
5. تست‌ها را بعد از هر تغییر اجرا کن؛ گزارش را به فارسی و کوتاه بده.
6. تغییر روی سرور و کامیت فقط با تأیید صاحب پروژه.
