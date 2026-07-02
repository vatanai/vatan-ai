# SESSION-B4 — Token System
# پیش‌نیاز: A2 + B2 تموم شده باشن

## هدف
سیستم توکن — کم کردن + نمایش + صفحه خرید

## Logic (فقط backend)
```php
// بعد از callback موفق Worker:
if ($job->status === 'done') {
    $user->decrement('quota_remaining');
    $user->increment('total_images_generated');
    // اگر quota = 0 → flag کن
}
```

## API Endpoints
```
GET /api/user/quota → { remaining: int, total_purchased: int }
```

## UI نمایش توکن (Hamburger Menu)
- نمایش: "۳ از ۱۰ توکن باقیمانده"
- progress bar
- دکمه «خرید توکن»

## صفحه quota=0
وقتی quota تموم شد:
- overlay روی دکمه «بساز»
- modal یا redirect به صفحه خرید
- پیام: «توکن‌هات تموم شد — بخر و ادامه بده»

## وضعیت
- [ ] توکن بعد از هر تولید کم می‌شه
- [ ] نمایش در UI درست کار می‌کنه
- [ ] quota=0 → block تولید
