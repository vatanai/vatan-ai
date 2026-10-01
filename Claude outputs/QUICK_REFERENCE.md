# Instagram Automation — سریع‌ترین مرجع

## API Endpoints

### Receive Comment (n8n → Laravel)
```
POST /api/instagram/webhook
Content-Type: application/json

{
  "instagram_comment_id": "string",
  "instagram_user_id": "string",
  "instagram_username": "string",
  "instagram_media_id": "string",
  "comment_text": "string",
  "contains_keyword": boolean,
  "user_is_following": boolean,
  "ai_response": "string",
  "status": "sent|failed|pending|skipped"
}
```

### Update Status
```
PATCH /api/instagram/webhook/{comment_id}/status

{
  "status": "sent|failed|skipped",
  "sent_at": "2026-09-30T10:30:00Z",
  "failure_reason": "error description"
}
```

### Get Comments
```
GET /api/instagram/comments?limit=50&status=all
GET /api/instagram/comments?status=sent
GET /api/instagram/comments?status=pending
```

### Get Statistics
```
GET /api/instagram/stats
```

---

## Environment Variables

```bash
# Laravel .env
COMPOSIO_API_KEY=ck_...

# n8n .env
WEBHOOK_URL=http://localhost:8000
COMPOSIO_API_KEY=ck_...
OPENROUTER_API_KEY=sk_or_xxxxx
```

---

## n8n Credentials Required

| نام | نوع | مقدار |
|-----|-----|-------|
| composio_api_key | API Key | `ck_...` |
| openrouter_api_key | Bearer Token | `sk_or_xxxxx` |
| laravel_webhook_auth | Basic Auth | username/password |

---

## Database Fields

```sql
CREATE TABLE instagram_comments (
  id BIGINT PRIMARY KEY,
  user_id BIGINT NULLABLE,
  instagram_comment_id VARCHAR UNIQUE,
  instagram_user_id VARCHAR,
  instagram_username VARCHAR,
  instagram_media_id VARCHAR,
  comment_text LONGTEXT,
  contains_keyword BOOLEAN DEFAULT false,
  user_is_following BOOLEAN DEFAULT false,
  ai_response LONGTEXT NULLABLE,
  status VARCHAR DEFAULT 'pending', -- pending|sent|failed|skipped
  failure_reason VARCHAR NULLABLE,
  retry_count INT DEFAULT 0,
  metadata JSON NULLABLE,
  checked_at TIMESTAMP NULLABLE,
  sent_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Files to Copy

```bash
database/migrations/[TIMESTAMP]_create_instagram_comments_table.php
app/Models/InstagramComment.php
app/Http/Controllers/InstagramWebhookController.php
```

---

## Routes to Add

```php
Route::prefix('instagram')->group(function () {
    Route::post('/webhook', [InstagramWebhookController::class, 'receiveComment']);
    Route::patch('/webhook/{comment_id}/status', [InstagramWebhookController::class, 'updateStatus']);
    Route::get('/comments', [InstagramWebhookController::class, 'getComments']);
    Route::get('/stats', [InstagramWebhookController::class, 'getStats']);
});
```

---

## Commands

```bash
# Run migrations
php artisan migrate

# Test endpoint
curl -X GET http://localhost:8000/api/instagram/stats

# Send test comment
curl -X POST http://localhost:8000/api/instagram/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "instagram_comment_id": "test_123",
    "instagram_user_id": "user_456",
    "instagram_username": "testuser",
    "instagram_media_id": "media_789",
    "comment_text": "محصول خوب بود!",
    "contains_keyword": true,
    "user_is_following": true,
    "ai_response": "سلام! متشکریم!",
    "status": "sent"
  }'
```

---

## n8n Workflow Flow

```
1. Trigger (Every 1 minute)
   ↓
2. Get Instagram Comments (Composio API)
   ↓
3. Filter by Keyword
   ├→ YES: Check Follower Status
   │       ↓
   │       Generate AI Response (OpenRouter)
   │       ↓
   │       Send Instagram DM
   │       ↓
   │       Save to Laravel
   │       ↓
   │       Notify Slack (optional)
   │
   └→ NO: Save as Skipped
```

---

## Keywords to Customize

Edit in n8n workflow node "Filter by Keyword":

```javascript
// Current (English)
$json.text.toLowerCase().includes('product') ||
$json.text.toLowerCase().includes('price') ||
$json.text.toLowerCase().includes('buy')

// Customized (Persian)
$json.text.includes('محصول') ||
$json.text.includes('قیمت') ||
$json.text.includes('خرید')
```

---

## OpenRouter Models

| Model | Cost/1K | Speed | Quality |
|-------|---------|-------|---------|
| gpt-3.5-turbo | $0.0005 | Fast | Good |
| gpt-4-turbo | $0.01 | Medium | Excellent |
| claude-3-haiku | $0.00025 | Fast | Good |
| claude-3-sonnet | $0.003 | Medium | Excellent |

**Recommended:** `gpt-3.5-turbo` (بهترین نسبت هزینه/عملکرد)

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| 401 Composio | بررسی API Key در n8n Credentials |
| 401 OpenRouter | بررسی Bearer Token |
| 404 Endpoint | اجرای `php artisan route:list` |
| CORS Error | اضافه کردن URL به `config/cors.php` |
| No comments | بررسی n8n Execution Log |
| DM not sent | بررسی Composio API Logs |

---

## Performance

- **Interval:** 1 minute (production) / 30 seconds (testing)
- **Retry:** 3 attempts (built-in n8n)
- **Timeout:** 30 seconds per request
- **Cost/month:** ~$5-10 (OpenRouter only)

---

## Success Indicators

✅ Migration اجرا شده  
✅ Routes رجیستر شده  
✅ Composio Credential ایجاد شده  
✅ OpenRouter Credential ایجاد شده  
✅ n8n Workflow Import شده  
✅ Curl test موفق  
✅ Database entry visible  
