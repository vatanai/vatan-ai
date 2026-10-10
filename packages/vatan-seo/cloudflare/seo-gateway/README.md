# Vatan SEO Gateway (اختیاری)

فقط وقتی لازم است که سرور به APIهای گوگل دسترسی مستقیم نداشته باشد (مثل OpenRouter پروژه).

```bash
cd packages/vatan-seo/cloudflare/seo-gateway
npx wrangler deploy
npx wrangler secret put GATEWAY_SHARED_SECRET   # یک رشته‌ی تصادفی بلند
```

سپس در `.env` سرور:

```env
SEO_GOOGLE_GATEWAY_URL=https://vatan-seo-gateway.<subdomain>.workers.dev
SEO_GOOGLE_GATEWAY_SECRET=<همان رشته>
```

میزبان‌های مجاز: googleapis (سرچ کنسول، OAuth، PageSpeed، GA4) و پیشنهادهای جستجوی گوگل.
