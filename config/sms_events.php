<?php

return [
    'events' => [
        'otp_code' => ['label' => 'رمز یک‌بارمصرف ورود', 'group' => 'کاربران', 'variables' => ['code', 'expiry_minutes', 'brand_name']],
        'login_success' => ['label' => 'ورود موفق کاربر', 'group' => 'کاربران', 'variables' => ['name', 'phone', 'login_time']],
        'registration_success' => ['label' => 'ثبت‌نام موفق', 'group' => 'کاربران', 'variables' => ['name', 'phone', 'gift_credits']],
        'password_reset' => ['label' => 'بازیابی رمز عبور', 'group' => 'کاربران', 'variables' => ['name', 'phone']],
        'purchase_success' => ['label' => 'خرید موفق', 'group' => 'سفارشات', 'variables' => ['name', 'phone', 'order_number', 'product_name', 'amount', 'balance']],
        'plan_purchase_success' => ['label' => 'خرید موفق پلن', 'group' => 'پلن‌ها', 'variables' => ['name', 'plan_name']],
        'plan_purchase_failed' => ['label' => 'خرید ناموفق پلن', 'group' => 'پلن‌ها', 'variables' => ['name', 'plan_name']],
        'order_completed' => ['label' => 'تکمیل سفارش', 'group' => 'سفارشات', 'variables' => ['name', 'phone', 'order_number', 'product_name', 'balance']],
        'order_failed' => ['label' => 'ناموفق بودن سفارش', 'group' => 'سفارشات', 'variables' => ['name', 'phone', 'order_number', 'product_name']],
        'refund_success' => ['label' => 'بازگشت اعتبار', 'group' => 'سفارشات', 'variables' => ['name', 'phone', 'order_number', 'amount', 'balance']],
        'credit_changed' => ['label' => 'تغییر موجودی اعتبار', 'group' => 'اعتبار', 'variables' => ['name', 'phone', 'amount', 'balance', 'action']],
        'credit_low' => ['label' => 'هشدار کاهش اعتبار', 'group' => 'اعتبار', 'variables' => ['name', 'phone', 'balance', 'threshold']],
        'birthday' => ['label' => 'تبریک تولد مشتری', 'group' => 'مناسبت‌ها', 'variables' => ['name', 'phone', 'discount_code', 'brand_name']],
        'occasion' => ['label' => 'مناسبت سفارشی', 'group' => 'مناسبت‌ها', 'variables' => ['name', 'phone', 'occasion_name', 'discount_code', 'brand_name']],
        'admin_login' => ['label' => 'ورود مدیر به داشبورد', 'group' => 'مدیران', 'variables' => ['admin_name', 'admin_email', 'login_time', 'ip']],
        'sms_balance_low' => ['label' => 'هشدار شارژ پنل پیامک', 'group' => 'هشدار مدیران', 'variables' => ['provider_name', 'balance', 'threshold']],
        'ai_balance_low' => ['label' => 'هشدار شارژ هوش مصنوعی', 'group' => 'هشدار مدیران', 'variables' => ['provider_name', 'balance', 'threshold']],
        'custom' => ['label' => 'رویداد سفارشی', 'group' => 'سفارشی', 'variables' => ['name', 'phone']],
    ],
    // تنها الگوهای خدماتی که برای هر رویداد تأیید شده‌اند. این فهرست
    // «قرارداد ارسال» است: پنل مدیریت فقط می‌تواند یکی از این نگاشت‌های
    // تأییدشده را ذخیره کند و سرویس ارسال نیز همان قرارداد را اجرا می‌کند.
    'approved_shared_templates' => [
        'otp_code' => ['provider_template_id' => '506694', 'variables' => ['code']],
        'login_success' => ['provider_template_id' => '504170', 'variables' => ['name', 'login_time']],
        'registration_success' => ['provider_template_id' => '506692', 'variables' => ['name']],
        'plan_purchase_success' => ['provider_template_id' => '506695', 'variables' => ['name', 'plan_name']],
        'plan_purchase_failed' => ['provider_template_id' => '506696', 'variables' => ['name', 'plan_name']],
        'credit_low' => ['provider_template_id' => '504178', 'variables' => ['name', 'balance']],
        'order_completed' => ['provider_template_id' => '504179', 'variables' => ['name', 'order_number']],
        'purchase_success' => ['provider_template_id' => '504181', 'variables' => ['name', 'order_number', 'balance']],
        'refund_success' => ['provider_template_id' => '504185', 'variables' => ['name', 'amount', 'order_number', 'balance']],
        'admin_login' => ['provider_template_id' => '504173', 'variables' => ['admin_name', 'login_time', 'ip']],
        'sms_balance_low' => ['provider_template_id' => '504174', 'variables' => ['provider_name', 'balance', 'threshold']],
        'ai_balance_low' => ['provider_template_id' => '504174', 'variables' => ['provider_name', 'balance', 'threshold']],
    ],
    'samples' => [
        'name' => 'محمد رضایی', 'phone' => '09123456789', 'login_time' => '۱۴۰۵/۰۴/۳۰ - ۱۰:۳۰',
        'gift_credits' => '۵۰', 'order_number' => 'ORD-14050430-1024', 'product_name' => 'ساخت تصویر حرفه‌ای', 'plan_name' => 'حرفه‌ای',
        'amount' => '۱۲۰', 'balance' => '۸۵۰', 'action' => 'افزایش', 'threshold' => '۱۰۰',
        'discount_code'=>'BIRTHDAY20', 'brand_name'=>'وطن استودیو', 'occasion_name'=>'نوروز',
        'admin_name'=>'مدیر سایت', 'admin_email'=>'admin@example.com', 'ip'=>'127.0.0.1', 'provider_name'=>'ملی‌پیامک',
        'code'=>'۱۲۳۴۵', 'expiry_minutes'=>'۳',
    ],
];
