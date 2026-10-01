# راهنمای نصب اتوماسیون Instagram — Vatan AI

## خلاصه معماری

```
Instagram Business Page
    ↓ (تدوین نظرات)
Composio API (API Gateway ایرانی‌دسترس)
    ↓
n8n (سرور محلی اتوماسیون)
    ├→ 1. فیلتر کلمات کلیدی
    ├→ 2. بررسی دنبال کننده
    ├→ 3. تولید پاسخ AI (OpenRouter)
    ├→ 4. ارسال DM Instagram
    └→ 5. ذخیره‌سازی Laravel
        ↓
   Laravel Database (پایگاه داده)
        ↓
   Dashboard (داشبورد مسیریابی و آمار)
```

---

## مرحله ۱: آماده‌سازی Laravel

### ۱.۱ فایل‌های مورد نیاز

سه فایل باید به پروژه کپی شوند:

```
database/migrations/[TIMESTAMP]_create_instagram_comments_table.php
app/Models/InstagramComment.php
app/Http/Controllers/InstagramWebhookController.php
```

**نحوه انجام:**

```bash
cd /path/to/vatan-ai-v500

# ۱. Migration فایل را کپی کنید (نام فایل با TIMESTAMP بروز رسانی شود)
# به عنوان مثال: 2026_09_30_000000_create_instagram_comments_table.php
cp instagram_comments_migration.php database/migrations/

# ۲. Model فایل را کپی کنید
cp InstagramComment.php app/Models/

# ۳. Controller فایل را کپی کنید
cp InstagramWebhookController.php app/Http/Controllers/
```

### ۱.۲ اضافه کردن Route

فایل `routes/api.php` را باز کنید و این خطوط را اضافه کنید:

```php
Route::prefix('instagram')->group(function () {
    Route::post('/webhook', [InstagramWebhookController::class, 'receiveComment']);
    Route::patch('/webhook/{comment_id}/status', [InstagramWebhookController::class, 'updateStatus']);
    Route::get('/comments', [InstagramWebhookController::class, 'getComments']);
    Route::get('/stats', [InstagramWebhookController::class, 'getStats']);
});
```

### ۱.۳ اجرای Migration

```bash
php artisan migrate
```

**خروجی مورد انتظار:**
```
Migrating: [TIMESTAMP]_create_instagram_comments_table
Migrated:  [TIMESTAMP]_create_instagram_comments_table (0.12s)
```

### ۱.۴ تست سلامتی Laravel

```bash
curl -X GET http://localhost:8000/api/instagram/stats
```

**خروجی مورد انتظار (200 OK):**
```json
{
  "success": true,
  "stats": {
    "total_comments": 0,
    "total_sent": 0,
    "total_failed": 0,
    "total_pending": 0,
    "followers_engaged": 0,
    "last_7_days": 0,
    "last_30_days": 0,
    "keyword_matches": 0
  }
}
```

---

## مرحله ۲: نصب و تنظیم n8n

### ۲.۱ بررسی n8n در حال اجرا

```bash
# اگر n8n روی localhost:5678 اجرا می‌شود:
curl http://localhost:5678/api/v1/health

# یا اگر درگیری پورت هست:
netstat -tulpn | grep 5678
```

### ۲.۲ اضافه کردن Credentials

1. **Composio API Key:**
   - به `http://localhost:5678` بروید
   - **Credentials** → **Create New** → **Composio**
   - API Key: `ck_...`
   - نام: `composio_api_key`
   - Save

2. **OpenRouter API Key:**
   - به `http://dashboard.openrouter.ai` بروید (یا `https://openrouter.ai`)
   - API Key را ایجاد کنید (یا از موجود استفاده کنید)
   - به n8n بروید → **Credentials** → **Create New** → **HTTP Request**
   - نام: `openrouter_api_key`
   - Header نام: `Authorization`
   - Header مقدار: `Bearer sk_or_...`
   - Save

3. **Laravel Webhook Auth (اختیاری):**
   - به n8n بروید → **Credentials** → **Create New** → **Basic Auth**
   - نام کاربری: `n8n`
   - رمز عبور: `webhook_secret_key_12345`
   - نام Credential: `laravel_webhook_auth`
   - Save

### ۲.۳ Environment Variables

فایل `.env` سرور n8n را ویرایش کنید (معمولاً در `/home/user/.n8n` یا همشا):

```bash
# اگر استفاده از Docker:
# docker exec n8n cat /home/node/.n8n/.env

# ورودی‌های موردنیاز:
WEBHOOK_URL=http://localhost:8000
COMPOSIO_API_KEY=ck_...
OPENROUTER_API_KEY=sk_or_xxxxx
```

### ۲.۴ وارد کردن Workflow

**گزینه الف: دستی**

1. `http://localhost:5678` → **New Workflow**
2. محتوای `n8n_instagram_workflow.json` را کپی کنید
3. **⋮** → **Import from JSON**
4. JSON خود را پیست کنید
5. **Import**

**گزینه ب: از طریق API (اگر فایل لوکال دسترسی دارید):**

```bash
curl -X POST http://localhost:5678/api/v1/workflows \
  -H "Content-Type: application/json" \
  -H "X-N8N-API-KEY: your_api_key" \
  -d @n8n_instagram_workflow.json
```

### ۲.۵ تنظیم متغیرهای Workflow

بعد از وارد کردن، این متغیرها را تنظیم کنید:

- **Trigger Interval**: ۱ دقیقه (یا کمتر برای تست)
- **Composio Credentials**: `composio_api_key` انتخاب کنید
- **OpenRouter Credentials**: `openrouter_api_key` انتخاب کنید
- **WEBHOOK_URL**: `http://localhost:8000` (یا IP سرور Laravel)
- **Keywords**: کلماتی که می‌خواهید مطابقت پیدا کنند تغییر دهید (مثل "محصول"، "قیمت"، "خرید")

### ۲.۶ اجرای Workflow

1. **Test**: روی دکمه **Test Workflow** کلیک کنید
2. **Expected Output**: یکی یا بیشتر comment‌های Instagram اگر نظرات جدید وجود دارد
3. **Activate**: بعد از تأیید موفقیت، **Activate Workflow** را کلیک کنید

---

## مرحله ۳: تنظیم و تست

### ۳.۱ تنظیم Keywords

فایل `n8n_instagram_workflow.json` را ویرایش کنید، node **"Filter by Keyword"** را پیدا کنید:

**قبل:**
```javascript
$json.text.toLowerCase().includes('product') || 
$json.text.toLowerCase().includes('price') ||
...
```

**بعد (مثال برای فارسی):**
```javascript
$json.text.includes('محصول') || 
$json.text.includes('قیمت') ||
$json.text.includes('خرید') ||
$json.text.includes('موجود') ||
$json.text.toLowerCase().includes('buy')
```

### ۳.۲ تنظیم OpenRouter Prompt

Node **"Generate AI Response"** را باز کنید و Prompt را سفارشی کنید:

**مثال برای تجارت:**
```
You are a professional shop customer service representative in Persian/Farsi. 
Respond to product inquiries in a friendly, professional manner.
Keep responses under 100 words.
Always include the shop's product catalog link if appropriate.
Ask for the customer's specific needs if unclear.
Respond in the same language as the customer's comment.
```

### ۳.۳ تنظیم Trigger Interval

برای **تست**:
```
Interval: 30 seconds (برای بررسی سریع)
```

برای **تولید**:
```
Interval: 1 minute (توازن بین بروز‌رسانی و هزینه API)
```

### ۳.۴ Test Commands

**تست ۱: ارسال نظر آزمایشی درست‌مانند n8n**

```bash
curl -X POST http://localhost:8000/api/instagram/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "instagram_comment_id": "test_12345",
    "instagram_user_id": "user_9876",
    "instagram_username": "test_user",
    "instagram_media_id": "media_5555",
    "comment_text": "محصول خیلی خوب بود! چقدر هزینه داره؟",
    "contains_keyword": true,
    "user_is_following": true,
    "ai_response": "سلام! متشکریم که از محصول ما خوشتان آمد. لطفاً قیمت روز را از [لینک کاتالوگ] ببینید یا از DM ما اطلاعات بیشتری بپرسید.",
    "status": "sent",
    "metadata": {"test": true}
  }'
```

**خروجی مورد انتظار:**
```json
{
  "success": true,
  "message": "Comment processed successfully",
  "id": 1,
  "status": "sent"
}
```

**تست ۲: بررسی نظرات ذخیره شده**

```bash
curl http://localhost:8000/api/instagram/comments?limit=10
```

**خروجی مورد انتظار:**
```json
{
  "success": true,
  "total": 1,
  "comments": [
    {
      "id": 1,
      "instagram_username": "test_user",
      "comment_text": "محصول خیلی خوب بود! چقدر هزینه داره؟",
      "ai_response": "سلام! متشکریم...",
      "status": "sent",
      "user_is_following": true,
      "sent_at": "2026-09-30T10:30:00Z",
      "created_at": "2026-09-30T10:30:00Z"
    }
  ]
}
```

**تست ۳: آمار (Stats)**

```bash
curl http://localhost:8000/api/instagram/stats
```

**خروجی مورد انتظار:**
```json
{
  "success": true,
  "stats": {
    "total_comments": 1,
    "total_sent": 1,
    "total_failed": 0,
    "total_pending": 0,
    "followers_engaged": 1,
    "last_7_days": 1,
    "last_30_days": 1,
    "keyword_matches": 1
  }
}
```

---

## مرحله ۴: مانیتورینگ و تعمیر

### ۴.۱ بررسی Logs

**Laravel Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i instagram
```

**Expected Pattern:**
```
[2026-09-30 10:30:45] local.INFO: Instagram comment recorded {"id":1,"comment_id":"test_12345","username":"test_user","keyword_match":true} []
```

**n8n Logs:**
- `http://localhost:5678` → **Executions** → Workflow انتخاب کنید
- بررسی **Logs** برای هر execution

### ۴.۲ خطاهای رایج

| خطا | علت | راه حل |
|-----|-----|--------|
| 401 Unauthorized (Composio) | API Key اشتباه | در `n8n` → **Credentials** بررسی کنید |
| 401 Unauthorized (OpenRouter) | Token اشتباه | API Key را در **Credentials** بروز رسانی کنید |
| 404 Not Found (Laravel) | URL یا Route اشتباه | `php artisan route:list` اجرا کنید |
| Connection refused (Laravel) | سرور خاموش است | `php artisan serve` را شروع کنید |
| CORS error | سرویس متقابل از دامنه‌های متفاوت | `config/cors.php` میں `LARAVEL_URL` اضافه کنید |

### ۴.۳ بروز رسانی Database Schema

اگر نیاز به اضافه کردن فیلدهای جدید دارید:

```bash
# Migration جدید ایجاد کنید:
php artisan make:migration add_new_field_to_instagram_comments

# Migration را ویرایش کنید:
# ثم اجرا:
php artisan migrate
```

---

## مرحله ۵: داشبورد و مسیریابی

### ۵.۱ اضافه کردن به Dashboard

بخش **Growth/Marketing** در `resources/views/admin/growth/` این component را اضافه کنید:

```php
<!-- resources/views/admin/growth/instagram-comments.blade.php -->
<div class="card">
    <div class="card-header">
        <h5>Instagram Comments</h5>
    </div>
    <div class="card-body">
        <div id="stats-container">
            @livewire('InstagramCommentStats')
        </div>
    </div>
</div>
```

### ۵.۲ Livewire Component

```php
// app/Livewire/InstagramCommentStats.php
namespace App\Livewire;

use App\Models\InstagramComment;
use Livewire\Component;

class InstagramCommentStats extends Component
{
    public $stats;

    public function mount()
    {
        $this->refreshStats();
    }

    public function refreshStats()
    {
        $this->stats = [
            'total' => InstagramComment::count(),
            'sent' => InstagramComment::where('status', 'sent')->count(),
            'followers' => InstagramComment::where('user_is_following', true)->count(),
            'last_7_days' => InstagramComment::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    public function render()
    {
        return view('livewire.instagram-comment-stats');
    }
}
```

---

## مرحله ۶: بهینه‌سازی (اختیاری)

### ۶.۱ Caching

برای کاهش بار Database:

```php
// app/Http/Controllers/InstagramWebhookController.php
public function getStats(): JsonResponse
{
    return response()->json([
        'success' => true,
        'stats' => Cache::remember('instagram_stats', 300, function () {
            return [
                'total_comments' => InstagramComment::count(),
                'total_sent' => InstagramComment::where('status', 'sent')->count(),
                // ...
            ];
        }),
    ]);
}
```

### ۶.۲ Queue Jobs

برای پردازش بدون تاخیر:

```bash
# تعریف Job:
php artisan make:job ProcessInstagramComment

# ثم استفاده در Controller:
ProcessInstagramComment::dispatch($validated);
```

### ۶.۳ Rate Limiting

```php
// routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/instagram/webhook', [InstagramWebhookController::class, 'receiveComment']);
});
```

---

## خطوط پایانی

**تست شامل:**
1. ✅ Migration و Database
2. ✅ Laravel Routes و Controller
3. ✅ n8n Workflow Import
4. ✅ Composio + OpenRouter Credentials
5. ✅ Curl Test Commands
6. ✅ Dashboard Component

**بعد از تأیید:**
- Workflow را در n8n **Activate** کنید
- نظر واقعی روی Instagram پست کنید
- بررسی کنید که پاسخ خودکار ارسال شد
- Database entry را تأیید کنید

---

## Support & Debugging

اگر مسائل دارید:

1. **Log Files** را بررسی کنید
2. **Curl Test** را برای هر endpoint اجرا کنید
3. **n8n Execution Log** را در داشبورد ببینید
4. **Composio API Status** را بررسی کنید: https://status.composio.dev

**متغیرهایی که نیاز به شخصی‌سازی دارند:**
- `WEBHOOK_URL` → IP/Domain سرور Laravel
- `COMPOSIO_API_KEY` → از dashboard.composio.dev
- `OPENROUTER_API_KEY` → از openrouter.ai
- Keywords → برحسب تجارت خود
- Prompt → برای پاسخ‌های بهتر شخصی‌سازی کنید
