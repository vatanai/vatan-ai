# قرارداد اتصال بات تلگرام وطن

این سند قرارداد داخلی بین بات تلگرام، `n8n` و لاراول است. ارسال عمومی پیام عمداً در این مرحله فعال نیست.

## مسیر اصلی

۱. دکمه‌ی شیشه‌ای یا رنگی هر محصول در کانال به لینک `deep link` بات وصل می‌شود.

۲. لینک را می‌توان با دستور زیر ساخت:

```bash
php artisan telegram:product-link PRODUCT_ROUTE_SLUG SOURCE --channel=CHANNEL_KEY --campaign=CAMPAIGN_KEY --message=POST_ID
```

۳. رویداد تلگرام به مسیر زیر ارسال می‌شود:

```text
POST /webhooks/telegram
```

هدر الزامی:

```text
X-Telegram-Bot-Api-Secret-Token: TELEGRAM_WEBHOOK_SECRET
```

۴. پاسخ لاراول یک اقدام قابل اجرا برمی‌گرداند. مقدار `type` می‌تواند یکی از این موارد باشد:

- `send_message`: متن و دکمه‌ها را برای همان `chat_id` ارسال کن.
  اگر `media` وجود داشت، همان رسانه را همراه پیام ارسال کن؛ برای رسانه‌ی ذخیره‌شده‌ی داشبورد مقدار `file_id` و برای تصویر محصول مقدار `url` استفاده می‌شود.
- `send_message`: اگر یکی از دکمه‌ها کلید `web_app` داشته باشد، همان دکمه باید به‌صورت `web_app` برای کاربر ارسال شود تا `Mini App` داخل تلگرام باز شود.
- بررسی عضویت در هسته‌ی لاراول انجام می‌شود؛ اگر نتیجه از قبل در ورودی معتبر وجود نداشته باشد، با `getChatMember` از تلگرام استعلام می‌گیرد و در صورت خطا دسترسی را تأییدشده فرض نمی‌کند.

## قالب ورودی نرمال‌شده برای `n8n`

```json
{
  "update_id": 10001,
  "event": "start",
  "telegram": {
    "id": 123456789,
    "first_name": "Ali",
    "last_name": "Test",
    "username": "ali_test",
    "language_code": "fa"
  },
  "chat_id": "123456789",
  "start_payload": "v1_...",
  "is_channel_member": true,
  "payload": {}
}
```

برای پیام تماس تلفنی، `event` برابر `contact` و این بخش اضافه شود:

```json
{
  "contact": {
    "user_id": 123456789,
    "phone_number": "+989120000000"
  }
}
```

در متن‌ها و دکمه‌های قابل ویرایش داشبورد می‌توان از جای‌گذاری‌های `{first_name}`، `{gift_tokens}`، `{product_name}`، `{product_description}`، `{launch_token}` و `{channel_url}` استفاده کرد. برای دکمه‌ی عضویت، مقدار `action` برابر `join_channel` به لینک عضویت امن کانال تبدیل می‌شود.

## قواعد امنیتی

- `TELEGRAM_WEBHOOK_SECRET` فقط در تنظیمات امن `n8n` و لاراول قرار می‌گیرد.
- `initData` در `Mini App` فقط سمت سرور اعتبارسنجی می‌شود.
- توکن بات هرگز به مرورگر یا کد `Mini App` ارسال نمی‌شود.
- `update_id` برای جلوگیری از پردازش دوباره ثبت و کنترل می‌شود.
- هر کلیک محصول یک `launch_token` مستقل دارد و فقط برای همان کاربر معتبر است.
- ارسال عمومی از پنل فقط پس از فعال‌سازی صریح و تنظیم `TELEGRAM_BROADCAST_ENABLED` مجاز خواهد بود.

## تنظیمات محیط اجرا

```text
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_CHANNEL_ID=
TELEGRAM_CHANNEL_USERNAME=ai_vatan
TELEGRAM_CHANNEL_INVITE_URL=https://t.me/+R90JNkLlW7M4ZTk0
TELEGRAM_MINI_APP_URL=
TELEGRAM_INIT_DATA_MAX_AGE=86400
TELEGRAM_OTP_RESEND_SECONDS=60
TELEGRAM_BROADCAST_ENABLED=false
TELEGRAM_BROADCAST_RATE_PER_SECOND=25
```
