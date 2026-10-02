-- Instagram Test Data Setup
-- Date: 2026-10-01
-- Keyword: نیلوفر

SET @user_id = (SELECT id FROM users LIMIT 1);
SET @now = NOW();

-- 1. Insert Instagram Post Setting
INSERT INTO instagram_post_settings 
(user_id, instagram_post_id, instagram_post_url, post_type, status, caption, metadata, created_at, updated_at)
VALUES (
    @user_id,
    'Dd4VEftOMUV',
    'https://www.instagram.com/reel/Dd4VEftOMUV/',
    'reel',
    'testing',
    'تست اتوماسیون کامنت و دایرکت',
    JSON_OBJECT('product_name', 'پرامپت نیلوفر آبی', 'test_marker', 'real_test_niloofar'),
    @now,
    @now
);

SET @post_id = LAST_INSERT_ID();

-- 2. Insert Keyword
INSERT INTO instagram_post_keywords
(instagram_post_setting_id, keyword, match_type, priority, is_active, created_at, updated_at)
VALUES (@post_id, 'نیلوفر', 'exact', 1, 1, @now, @now);

-- 3. Non-follower message
INSERT INTO instagram_post_responses
(instagram_post_setting_id, scenario, target, message_body, `order`, is_active, created_at, updated_at)
VALUES (
    @post_id,
    'non_follower',
    'dm_text',
    '👋 سلام! 

برای دسترسی به پرامپت نیلوفر آبی
اول صفحه رو فالو کن بعد منتظر بمون
۳ ثانیه دیگه پیام رو براتت میفرسته 🎁',
    1,
    1,
    @now,
    @now
);

-- 4. Follower main message
INSERT INTO instagram_post_responses
(instagram_post_setting_id, scenario, target, message_body, `order`, is_active, created_at, updated_at)
VALUES (
    @post_id,
    'follower',
    'dm_text',
    '✅ خوش‌آمدی! 

پرامپت نیلوفر آبی رو براتت فرستادم
لطفا دایرکت بررسی کن 👈

قیمت: ۱۲ توکن ⚡',
    1,
    1,
    @now,
    @now
);

-- 5. Comment reply
INSERT INTO instagram_post_responses
(instagram_post_setting_id, scenario, target, message_body, `order`, is_active, created_at, updated_at)
VALUES (
    @post_id,
    'follower',
    'comment',
    '✅ اطلاعات کامل در DM فرستادم
برو چک کن 👈',
    1,
    1,
    @now,
    @now
);

-- 6. Product
INSERT INTO instagram_post_products
(instagram_post_setting_id, product_name, product_url, product_price, product_currency, product_description, metadata, stock_quantity, `order`, is_active, created_at, updated_at)
VALUES (
    @post_id,
    'پرامپت نیلوفر آبی',
    'https://aivatan.com/app/product/468466-lily-in-the-mirror',
    12,
    'tokens',
    'پرامپت خاص برای تولید تصاویر با موضوع نیلوفر آبی',
    JSON_OBJECT('prompt_type', 'image_generation'),
    999,
    1,
    1,
    @now,
    @now
);

-- Verify
SELECT 'Test Data Created!' as status;
SELECT @post_id as instagram_post_id;
