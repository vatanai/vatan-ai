# CLAUDE.md — وطن استودیو (AI Vatan)
# این فایل را در root پروژه قرار بده — Claude Code هر سشن خودکار می‌خونه

---

## PROJECT IDENTITY
- Name: وطن استودیو / AI Vatan
- Domain: aivatan.com
- Type: AI Product Marketplace (Instagram-like)
- Phase: فاز ۱ — MVP

## TECH STACK
- Backend: Laravel 11
- Database: Supabase (PostgreSQL) — UUID as PK, TIMESTAMPTZ for all dates
- Frontend: Tailwind CSS v3 (RTL, Dark mode default)
- Queue: Redis + Python Workers
- AI Provider: fal.ai (primary), Replicate (fallback)
- Storage: Supabase Storage
- Automation: n8n + زرین‌پال

## FOLDER STRUCTURE
```
/login       → aivatan.com/login
/app         → aivatan.com/app
/admin       → aivatan.com/admin
/site        → aivatan.com (marketing - not started)
```

## DESIGN SYSTEM (NON-NEGOTIABLE)

### Colors — Dark Mode (default)
```css
--bg-page:        #000000
--text-primary:   #ffffff
--text-secondary: #ffffff
--icon:           #ffffff
--bg-card:        #3F3F3F
--bg-affiliate:   #0d2818
--border-affiliate: #1a5c32
--green:          #0BBF53
```

### Colors — Light Mode
```css
--bg-page:        #ffffff
--text-primary:   #000000
--text-secondary: #000000
--icon:           #000000
--bg-card:        #E5E5E5
--bg-affiliate:   #e8f8ee
--border-affiliate: #a8e6be
--green:          #0BBF53
```

### Other Tokens
```css
--accent: #a07af5
--red:    #f05c5c
--orange: #f5923a
```

- Font: yekan bakh آدرس این فونت توی فایل پروژه به این آدرس هست/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/ai-vatan-mac/public/fonts
‫-‬ به هیچ عنوان از فونت دیگه ای استفاده نشه
- Direction: RTL mandatory
- Border-radius: 14px standard | circles: 50% | badges: 6-8px
- UI style: Instagram-like
- Theme: Dark default, Light supported
- Build: `npx tailwindcss -i ./src/input.css -o ./src/output.css`

## MVP COMPLETION CRITERIA (فاز ۱)
کاربر میاد ← سبک انتخاب میکنه ← عکس آپلود میکنه ← خروجی blur میبینه ← ثبت‌نام/ورود میکنه ← خروجی کامل میگیره ← داشبورد می‌بینه ← توکنش کم شده

## CURRENT STATUS
- login/index.html ✅ done
- admin/index.html ✅ done
- app/index.html 🔄 ~60% UI done
- app/pages/create.html 🔄 in progress
- app/pages/files.html 🔄 in progress
- app/pages/ideas.html 🔄 in progress
- app/pages/profile.html 🔄 in progress
- site/index.html ❌ not started
- Backend: ~40% done

## USER ROLES
مدیر کل | مدیر میانی | بلاگر | کاربر عادی

## TOKEN SYSTEM
- New user: free quota (from settings table)
- Each image generation: -1 quota
- quota = 0 → show purchase page
- Logic: backend only, never frontend

## APP NAVIGATION (Bottom Nav — RTL)
خانه (fa-house) | ایده‌ها (fa-lightbulb) | بساز (+ green circle) | فایل‌ها (fa-folder) | پروفایل (fa-user)

## APP HEADER
- Right: icon_vatan.svg + vatan-logo.svg
- Left: pricing box (خرید ویژه + badge ۲۵٪ تخفیف) + hamburger
- Padding-top: 48px (iPhone notch)

## AI ENGINE (READ-ONLY — DO NOT MODIFY ARCHITECTURE)
User → Product → Job → Queue → Worker → AI Model → Output
- Prompts stored ONLY in DB (styles.prompt) — never in code
- Worker receives style_id only, fetches prompt at runtime
- Users never see model names
- Max processing time: 120s per job
- Retry: max 3, exponential backoff

## DATABASE — KEY TABLES (Supabase/PostgreSQL)
Build order: platforms → styles → blogers → users → pricing_plans → collections → collection_styles → jobs_log → cost_per_job → payments → user_events → settings

Key tables:
- users: UUID PK, phone (primary auth), quota_remaining, user_segment
- styles: UUID PK, name_fa, name_en, prompt (never expose), primary_model
- jobs_log: user_id, style_id, status, input_url, output_url, api_used
- payments: user_id, amount_irr, status, bloger_commission
- settings: global config (default quota, etc.)

## ROUTES (PAGE-MAP — NEVER CREATE PAGES OUTSIDE THIS LIST)
- GET /app/home — feed
- GET /app/explore — discovery
- GET /app/create — AI generation entry
- GET /app/files — saved outputs
- GET /app/profile — user dashboard
- GET /app/product/{id} — single product
- /admin/dashboard, /admin/users, /admin/products, /admin/jobs, /admin/payments

## HARD RULES (FROM CLAUDE-RULES.txt)
- Do NOT redesign system
- Do NOT suggest alternatives unless asked
- Do NOT explain architecture unless asked
- Output: production Laravel 11 code only
- Tailwind CSS v3 only
- RTL Persian UI mandatory
- Instagram-like UI mandatory
- Never create pages outside PAGE-MAP
- Always assume Redis + Queue + Worker architecture exists
- Never bypass AI ENGINE ARCHITECTURE
- If unclear → ask one question only
- If clear → output code only

## IMAGE ASSETS
Location: app/assets/img/
Logos: icon_vatan.svg, vatan-logo.svg, icon-telegram.svg, Bale-icon.svg

## SESSION FILES
All session files are in /docs/ folder.
When user says "start session X", read docs/SESSION-X.md then execute.

## HOW TO START EACH SESSION
1. This file (CLAUDE.md) is auto-loaded ✅
2. User will say which session to run
3. Read that session file from docs/
4. Execute without asking unnecessary questions
5. If unclear → ask ONE question only, then proceed
