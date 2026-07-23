# Vatan OpenRouter Gateway

Cloudflare Worker امن برای عبور درخواست‌های سرور Vatan AI به OpenRouter.

## Secrets

- `OPENROUTER_API_KEY`: کلید OpenRouter؛ فقط در Cloudflare Secret ذخیره شود.
- `GATEWAY_SHARED_SECRET`: یک مقدار تصادفی و بلند که در Cloudflare و متغیر
  `OPENROUTER_GATEWAY_SECRET` اپ Liara یکسان است.

## تنظیم Liara بعد از انتشار Worker

```env
OPENROUTER_BASE_URL=https://vatan-openrouter-gateway.<subdomain>.workers.dev/api/v1
OPENROUTER_GATEWAY_SECRET=<same-shared-secret>
OPENROUTER_TIMEOUT=150
```

مسیر `/health` عمومی است، اما مسیرهای OpenRouter فقط با Secret مشترک پاسخ می‌دهند.
