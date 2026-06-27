# AIPIX — مستند معماری سیستم محصول
**نسخه:** ۱.۰  
**تاریخ:** ۱۴۰۵  
**وضعیت:** پیش‌نویس اول

---

## ۱. دیدکلی سیستم

AIPIX یک پلتفرم تولید محتوای AI-محور است که کاربر بدون دانش فنی، عکس/متن/ویدیوی خود را آپلود می‌کند و خروجی نهایی به‌صورت خودکار تولید و تحویل داده می‌شود. کاربر نه پرامپت می‌بیند، نه مدل AI، نه پیچیدگی فنی.

### اصول پایه‌ای:
- **بلک‌باکس بودن برای کاربر:** تمام منطق AI پشت‌پرده است
- **انعطاف ورودی:** هر محصول ورودی‌های متفاوتی دارد
- **fallback هوشمند:** اگر مدل اول خطا داد، مدل دوم اجرا می‌شود
- **ذخیره‌سازی کامل:** هم ورودی کاربر، هم خروجی نهایی در پروفایل ذخیره می‌شود

---

## ۲. ساختار کلی سیستم محصول

```
PRODUCT
├── Identity (هویت محصول)
├── Media (رسانه نمایشی)
├── AI Config (تنظیمات هوش مصنوعی)
├── Input Schema (فیلدهای ورودی از کاربر)
├── Output Config (تنظیمات خروجی)
├── Pricing (قیمت‌گذاری)
└── Display Config (نحوه نمایش)
```

---

## ۳. مدل داده محصول (Product Data Model)

### ۳.۱ — Identity | هویت محصول

| فیلد | نوع | توضیح |
|------|-----|--------|
| `product_id` | UUID | شناسه یکتا |
| `title_fa` | string | نام فارسی محصول |
| `title_en` | string | نام انگلیسی محصول |
| `slug` | string | آدرس URL-friendly |
| `description_fa` | text | توضیح فارسی |
| `description_en` | text | توضیح انگلیسی |
| `category` | enum | دسته‌بندی اصلی |
| `subcategory` | enum | زیردسته |
| `tags` | array[string] | تگ‌های جستجو |
| `status` | enum | active / inactive / draft |
| `is_featured` | boolean | محصول ویژه |
| `is_new` | boolean | محصول جدید |
| `is_trending` | boolean | ترند بودن |
| `created_by` | user_id | سازنده |
| `created_at` | datetime | تاریخ ساخت |
| `updated_at` | datetime | آخرین ویرایش |

### ۳.۲ — Media | رسانه نمایشی

| فیلد | نوع | توضیح |
|------|-----|--------|
| `thumbnail` | image_url | تصویر کارت محصول در لیست |
| `cover_image` | image_url | تصویر کامل صفحه محصول |
| `sample_output` | array[media_url] | نمونه خروجی‌های نهایی (حداکثر ۱۰) |
| `sample_input` | array[media_url] | نمونه ورودی متناظر |
| `before_after_pairs` | array[{input, output}] | جفت before/after |
| `preview_video` | video_url | ویدیوی پیش‌نمایش (اختیاری) |
| `media_type` | enum | photo / video / both |

### ۳.۳ — AI Config | تنظیمات هوش مصنوعی

```json
{
  "ai_pipeline": [
    {
      "step": 1,
      "provider": "openrouter",
      "model": "black-forest-labs/flux-1.1-pro",
      "model_display_name": "FLUX 1.1 Pro",
      "type": "image_generation",
      "timeout_seconds": 60,
      "is_primary": true
    },
    {
      "step": 1,
      "provider": "openrouter",
      "model": "stability-ai/stable-diffusion-3.5",
      "model_display_name": "SD 3.5",
      "type": "image_generation",
      "timeout_seconds": 60,
      "is_fallback": true,
      "fallback_order": 1
    }
  ],
  "prompt_template": "A professional LinkedIn headshot of {gender} named {name}, wearing {clothing_style}, {background_description}, photorealistic, 8k, sharp focus",
  "negative_prompt": "blurry, low quality, distorted face, cartoon",
  "show_prompt_to_user": false,
  "prompt_language": "en",
  "face_swap_enabled": false,
  "face_reference_slot": null,
  "multi_step_pipeline": false,
  "steps": []
}
```

**نکات مهم AI Config:**
- `ai_pipeline` آرایه‌ای از مدل‌هاست — اولی primary، بقیه fallback
- `prompt_template` از متغیرهایی که از ورودی کاربر می‌آیند استفاده می‌کند
- `show_prompt_to_user` قابل تنظیم توسط ادمین
- `multi_step_pipeline` برای محصولاتی که چند مرحله پردازش دارند

### ۳.۴ — Input Schema | ورودی‌های کاربر

این مهم‌ترین بخش است. هر محصول یک JSON Schema دارد که فیلدهای ورودی را تعریف می‌کند:

```json
{
  "input_fields": [
    {
      "field_id": "user_photo",
      "label_fa": "عکس شما",
      "label_en": "Your Photo",
      "type": "image_upload",
      "required": true,
      "min_count": 1,
      "max_count": 3,
      "accepted_formats": ["jpg", "jpeg", "png", "webp"],
      "max_size_mb": 10,
      "min_resolution": "512x512",
      "hint_fa": "عکس واضح از صورت آپلود کنید",
      "hint_en": "Upload a clear face photo",
      "example_image": "url_to_example",
      "order": 1
    },
    {
      "field_id": "name",
      "label_fa": "نام",
      "label_en": "Name",
      "type": "text",
      "required": false,
      "max_length": 50,
      "placeholder_fa": "نام شما",
      "hint_fa": "نام روی عکس نمایش داده می‌شود",
      "order": 2
    },
    {
      "field_id": "birth_date",
      "label_fa": "تاریخ تولد",
      "label_en": "Birth Date",
      "type": "date",
      "required": true,
      "format": "jalali",
      "order": 3
    },
    {
      "field_id": "clothing_style",
      "label_fa": "استایل لباس",
      "label_en": "Clothing Style",
      "type": "select",
      "required": true,
      "options": [
        {"value": "formal", "label_fa": "رسمی", "label_en": "Formal"},
        {"value": "casual", "label_fa": "کژوال", "label_en": "Casual"},
        {"value": "traditional", "label_fa": "سنتی", "label_en": "Traditional"}
      ],
      "order": 4
    },
    {
      "field_id": "business_link",
      "label_fa": "لینک کسب‌وکار",
      "label_en": "Business Link",
      "type": "url",
      "required": false,
      "hint_fa": "لینک سایت یا اینستاگرام شما",
      "order": 5
    }
  ]
}
```

**انواع فیلد ورودی (Input Field Types):**

| نوع | کاربرد |
|-----|--------|
| `image_upload` | آپلود عکس (با min/max count) |
| `video_upload` | آپلود ویدیو |
| `text` | متن کوتاه |
| `textarea` | متن بلند |
| `select` | انتخاب از لیست |
| `multi_select` | انتخاب چندتایی |
| `date` | تاریخ (جلالی/میلادی) |
| `number` | عدد |
| `url` | لینک |
| `color_picker` | انتخاب رنگ |
| `toggle` | بله/خیر |
| `file_upload` | فایل عمومی |

### ۳.۵ — Output Config | تنظیمات خروجی

| فیلد | نوع | توضیح |
|------|-----|--------|
| `output_type` | enum | image / video / image+video |
| `output_format` | string | jpg / png / mp4 / webm |
| `output_resolution` | string | مثلاً "1024x1024" یا "1080x1920" |
| `output_aspect_ratio` | string | "1:1" / "9:16" / "16:9" / "4:5" |
| `output_count` | number | تعداد خروجی که تولید می‌شود |
| `watermark_enabled` | boolean | واترمارک روی خروجی |
| `watermark_position` | enum | corner / center / none |
| `delivery_method` | enum | instant / queued |
| `estimated_time_seconds` | number | زمان تقریبی تولید |

### ۳.۶ — Pricing | قیمت‌گذاری

| فیلد | نوع | توضیح |
|------|-----|--------|
| `pricing_model` | enum | per_credit / free / subscription_only |
| `credit_cost` | number | تعداد کردیت لازم |
| `pricing_tier` | enum | basic / standard / premium |
| `is_free` | boolean | رایگان بودن |
| `discount_percent` | number | درصد تخفیف فعال |

### ۳.۷ — Display Config | تنظیمات نمایش

| فیلد | نوع | توضیح |
|------|-----|--------|
| `display_mode` | enum | card / featured / minimal |
| `card_style` | enum | portrait / landscape / square |
| `show_before_after` | boolean | نمایش before/after slider |
| `gallery_layout` | enum | grid / masonry / carousel |
| `color_theme` | string | رنگ accent محصول |
| `badge` | string | برچسب روی کارت (مثلاً "جدید"، "پرفروش") |
| `platforms` | array | web / mobile / both |

---

## ۴. دسته‌بندی محصولات (Taxonomy)

```
ROOT
├── PEOPLE (شخصی)
│   ├── Professional
│   ├── Fashion
│   ├── Lifestyle
│   ├── Fitness
│   └── Beauty
├── BUSINESS (کسب‌وکار)
│   ├── Real Estate
│   ├── Medical
│   ├── Lawyer
│   ├── Coach
│   ├── Education
│   └── Entrepreneur
├── EVENTS (مناسبت‌ها)
│   ├── Birthday
│   ├── Wedding
│   ├── Graduation
│   ├── Valentine
│   └── Iranian Events (Nowruz, Yalda, Eid)
├── FAMILY (خانواده)
├── KIDS (کودکان)
├── PETS (حیوانات)
├── ENTERTAINMENT (سرگرمی)
│   ├── Anime / Manga
│   ├── Disney / Pixar
│   └── Superhero / Fantasy
├── PRODUCTS (محصولات)
├── AVATARS (آواتار)
└── VIDEOS (ویدیو — فاز بعد)
    ├── Personal
    ├── Business
    ├── Social Media
    ├── Avatars
    ├── Events
    ├── Kids
    ├── Entertainment
    ├── Products
    └── AI Tools
```

---

## ۵. منطق اجرای AI (AI Execution Logic)

```
کاربر فرم را submit می‌کند
        ↓
Validation ورودی‌ها
        ↓
ساخت prompt نهایی از template + ورودی‌های کاربر
        ↓
ارسال به OpenRouter — مدل Primary
        ↓
    [موفق؟]
   /        \
بله          خیر
 ↓            ↓
خروجی     مدل Fallback 1
           ↓
       [موفق؟]
      /        \
    بله          خیر
     ↓            ↓
   خروجی      مدل Fallback 2
               ↓
           [موفق؟]
          /        \
        بله          خیر
         ↓            ↓
       خروجی      Error Handler
                  (اطلاع‌رسانی به کاربر)
        ↓
Post-processing (watermark، resize، فرمت)
        ↓
ذخیره در پروفایل کاربر
        ↓
نمایش خروجی به کاربر
```

---

## ۶. ساختار ذخیره‌سازی (Storage Model)

### Order (سفارش):
```json
{
  "order_id": "uuid",
  "user_id": "uuid",
  "product_id": "uuid",
  "status": "pending | processing | completed | failed",
  "inputs": {
    "user_photo": ["url1", "url2"],
    "name": "علی",
    "clothing_style": "formal"
  },
  "prompt_used": "generated prompt text",
  "model_used": "black-forest-labs/flux-1.1-pro",
  "fallback_used": false,
  "outputs": ["output_url_1", "output_url_2"],
  "credits_used": 5,
  "processing_time_ms": 12500,
  "created_at": "datetime",
  "completed_at": "datetime"
}
```

---

## ۷. قوانین کلی محصول

1. هر محصول حداقل یک sample_output دارد
2. هر محصول حداقل یک مدل AI دارد (primary)
3. هر محصول یک input_schema دارد (حتی اگر فقط یک فیلد داشته باشد)
4. prompt_template هرگز به کاربر نمایش داده نمی‌شود مگر show_prompt_to_user = true
5. اگر fallback تعریف نشده باشد و primary خطا داد، سفارش failed می‌شود
6. تمام ورودی‌های کاربر در سرور ذخیره می‌شوند (برای پروفایل و reuse)
7. خروجی‌ها بعد از تحویل هم در پروفایل کاربر باقی می‌مانند

---

*نسخه بعدی: طراحی API endpoints و ساختار database schema*
