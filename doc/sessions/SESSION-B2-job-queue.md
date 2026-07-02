# SESSION-B2 — Redis Queue + Worker Connection
# پیش‌نیاز: A1 + A3 تموم شده باشن

## هدف
ارسال job از Laravel به Redis + تست Worker

## Laravel Side
```php
// config/queue.php → driver: redis
// App\Jobs\ProcessImageJob → dispatch to 'images' queue
// Job data: { job_log_id, style_id, input_url, user_id }
// NEVER include prompt in job data
```

## Python Worker (موجود روی سرور)
Worker انتظار داره این ساختار رو از queue بگیره:
```json
{
  "job_log_id": "uuid",
  "style_id": "uuid",
  "input_url": "supabase-url",
  "user_id": "uuid"
}
```
Worker خودش prompt رو از DB می‌خونه با style_id.

## Callback به Laravel
بعد از پردازش Worker صدا می‌زنه:
```
POST /api/worker/callback
body: { job_log_id, output_url, status, api_used, cost_usd }
Authorization: Bearer [WORKER_SECRET از .env]
```

## Test
یه job دستی بده به queue و ببین Worker جواب می‌ده.

## وضعیت
- [ ] Redis اتصال دارد
- [ ] Job dispatch می‌شه
- [ ] Worker callback دریافت می‌شه
- [ ] job_log status آپدیت می‌شه
