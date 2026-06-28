# SESSION-A1 — ساخت دیتابیس
# پیش‌نیاز: CLAUDE.md خوانده شده باشه

## هدف این سشن
ساخت تمام جداول Supabase به ترتیب صحیح با تمام فیلدها

## ترتیب ساخت (به خاطر foreign keys)
platforms → styles → blogers → users → pricing_plans → collections → collection_styles → jobs_log → cost_per_job → payments → user_events → settings

## جداول و فیلدهای کلیدی

### platforms
- id: UUID PK (gen_random_uuid())
- name: TEXT UNIQUE (web, telegram, bale, ios, android)
- display_name: TEXT
- is_active: BOOLEAN default true
- added_at: TIMESTAMPTZ default now()
**Seed:** name='web', display_name='وب‌سایت', is_active=true

### users
- id: UUID PK
- phone: TEXT UNIQUE NOT NULL (format: 09XXXXXXXXX)
- phone_verified: BOOLEAN default false
- password_hash: TEXT NULLABLE
- full_name: TEXT NULLABLE
- telegram_id: BIGINT UNIQUE NULLABLE
- language_code: TEXT default 'fa'
- entry_platform_id: UUID FK → platforms.id
- bloger_ref: TEXT NULLABLE (permanent, never changes)
- quota_remaining: INT (default from settings)
- total_quota_purchased: INT default 0
- purchase_count: INT default 0
- total_spent_irr: NUMERIC default 0
- subscription_active: BOOLEAN default false
- subscription_expires_at: TIMESTAMPTZ NULLABLE
- tags: TEXT[]
- user_segment: TEXT (free/active/vip/churned)
- total_images_generated: INT default 0
- favorite_style_id: UUID FK → styles.id NULLABLE
- last_style_id: UUID FK → styles.id NULLABLE
- created_at: TIMESTAMPTZ default now()
- first_purchase_at: TIMESTAMPTZ NULLABLE
- last_active_at: TIMESTAMPTZ default now()
- last_purchase_at: TIMESTAMPTZ NULLABLE
- is_blocked: BOOLEAN default false
- blocked_bot: BOOLEAN default false
- notes: TEXT NULLABLE

### styles
- id: UUID PK
- name_fa: TEXT (فشن و مد، هنری و نقاشی، کارتونی و انیمه، طبیعت و روستایی، مینیمال و مدرن)
- name_en: TEXT (fashion, artistic, cartoon, nature, minimal)
- description: TEXT
- category: TEXT
- prompt: TEXT (NEVER expose to frontend or logs)
- prompt_visibility: ENUM (hidden, admin_only, public) default hidden
- prompt_encrypted: BOOLEAN default false
- cover_image: TEXT
- sample_outputs: TEXT[]
- primary_model: TEXT (fal.ai model name)
- fallback_models: TEXT[]
- token_cost: INT default 1
- is_active: BOOLEAN default true
- sort_order: INT default 0
- usage_count: INT default 0
- created_at: TIMESTAMPTZ default now()

### jobs_log
- id: UUID PK
- user_id: UUID FK → users.id
- style_id: UUID FK → styles.id
- input_url: TEXT
- output_url: TEXT NULLABLE
- status: TEXT (pending/processing/done/failed)
- api_used: TEXT NULLABLE
- api_cost_usd: NUMERIC NULLABLE
- processing_time_ms: INT NULLABLE
- error_message: TEXT NULLABLE
- attempts: INT default 0
- created_at: TIMESTAMPTZ default now()
- completed_at: TIMESTAMPTZ NULLABLE

### payments
- id: UUID PK
- user_id: UUID FK → users.id
- bloger_id: UUID FK → blogers.id NULLABLE
- amount_irr: NUMERIC NOT NULL
- quota_granted: INT NOT NULL
- status: TEXT (pending/success/failed/refunded)
- gateway: TEXT default 'zarinpal'
- gateway_ref: TEXT NULLABLE
- bloger_commission_irr: NUMERIC default 0
- created_at: TIMESTAMPTZ default now()
- paid_at: TIMESTAMPTZ NULLABLE

### settings
- key: TEXT PK (مثلاً 'default_quota', 'token_cost_per_image')
- value: TEXT
- description: TEXT
- updated_at: TIMESTAMPTZ default now()
**Seed:** ('default_quota', '3', 'توکن رایگان برای کاربر جدید')

## Laravel Migrations
برای هر جدول یه migration جداگانه بساز.
نام‌گذاری: create_platforms_table, create_users_table, ...

## وضعیت
- [ ] جداول ساخته شدن
- [ ] seed data وارد شد
- [ ] migrations تست شدن
