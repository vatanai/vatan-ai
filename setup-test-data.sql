-- Instagram Test Data Setup for Niloofar
-- استفاده: این فایل را به دیتابیس اجرا کنید

-- Step 1: Get your user ID (change 1 to your actual user ID if needed)
SET @user_id = 1;

-- Step 2: Insert Instagram Post Setting
INSERT INTO instagram_post_settings 
(user_id, title, instagram_post_id, instagram_caption, status, require_follow, min_followers, 
 non_follower_response, follower_response, comment_reply_delay, dm_product_delay, dm_form_delay, 
 repeat_policy, log_all_interactions, notify_slack, daily_excel_export, metadata, created_at, updated_at)
VALUES 
(@user_id, 
 'تست نیلوفر - Reel',
 'Dd4VEftOMUV',
 'تست اتوماسیون کامنت و دایرکت 🎯',
 'testing',
 1,  -- require_follow
 0,  -- min_followers
 JSON_OBJECT('text', '👋 سلام! برای دسترسی به پرامپت نیلوفر آبی اول صفحه رو فالو کن 🎁'),
 JSON_OBJECT('text', '✅ خوش‌آمدی! پرامپت نیلوفر آبی رو براتت فرستادم 👈 قیمت: ۱۲ توکن ⚡'),
 3,  -- comment_reply_delay
 5,  -- dm_product_delay
 2,  -- dm_form_delay
 'once_per_user',
 1,  -- log_all_interactions
 0,  -- notify_slack
 0,  -- daily_excel_export
 JSON_OBJECT('product_name', 'پرامپت نیلوفر آبی', 'test_marker', 'real_test_niloofar'),
 NOW(),
 NOW()
);

SET @post_id = LAST_INSERT_ID();

-- Step 3: Add Keyword
INSERT INTO instagram_post_keywords (post_setting_id, keyword, match_type, is_active, created_at, updated_at)
VALUES (@post_id, 'نیلوفر', 'exact', 1, NOW(), NOW());

-- Step 4: Add Response Templates
-- Non-follower DM
INSERT INTO instagram_post_responses (post_setting_id, scenario, target, message, created_at, updated_at)
VALUES (@post_id, 'non_follower', 'dm_text', 
        '👋 سلام! برای دسترسی به پرامپت نیلوفر آبی اول صفحه رو فالو کن بعد منتظر بمون ۳ ثانیه دیگه پیام رو براتت میفرسته 🎁',
        NOW(), NOW());

-- Follower DM
INSERT INTO instagram_post_responses (post_setting_id, scenario, target, message, created_at, updated_at)
VALUES (@post_id, 'follower', 'dm_text',
        '✅ خوش‌آمدی! پرامپت نیلوفر آبی رو براتت فرستادم لطفا دایرکت بررسی کن 👈 قیمت: ۱۲ توکن ⚡',
        NOW(), NOW());

-- Follower Comment Reply
INSERT INTO instagram_post_responses (post_setting_id, scenario, target, message, created_at, updated_at)
VALUES (@post_id, 'follower', 'comment',
        '✅ اطلاعات کامل در DM فرستادم برو چک کن 👈',
        NOW(), NOW());

-- Step 5: Add Product
INSERT INTO instagram_post_products (post_setting_id, product_name, description, price, product_link, is_active, inventory, created_at, updated_at)
VALUES (@post_id, 
        'پرامپت نیلوفر آبی',
        'پرامپت خاص برای تولید تصاویر با موضوع نیلوفر آبی',
        12,
        'https://aivatan.com/app/product/468466-lily-in-the-mirror',
        1,
        999,
        NOW(),
        NOW());

-- Done! Now you can test
SELECT @post_id as 'Post ID Created', 'نیلوفر' as 'Keyword', 'Dd4VEftOMUV' as 'Instagram Post ID';
