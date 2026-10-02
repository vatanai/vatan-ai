<?php
// Instagram Test Data Setup - Simple Admin Panel
// Access: https://aivatan.com/setup-instagram-test.php

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['action'])) {
    // Show HTML form
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیم تست اینستاگرام</title>
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background: #f5f5f5;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #833AB4, #E1306C);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
        }
        button:hover {
            opacity: 0.9;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            display: none;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            display: block;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 تنظیم تست اینستاگرام</h1>
        
        <form id="testForm">
            <div class="form-group">
                <label>پست ID اینستاگرام</label>
                <input type="text" name="instagram_post_id" value="Dd4VEftOMUV" required>
            </div>
            
            <div class="form-group">
                <label>عنوان پست</label>
                <input type="text" name="title" value="تست نیلوفر - Reel" required>
            </div>
            
            <div class="form-group">
                <label>کلمه کلیدی (Keyword)</label>
                <input type="text" name="keyword" value="نیلوفر" required>
            </div>
            
            <div class="form-group">
                <label>نام محصول</label>
                <input type="text" name="product_name" value="پرامپت نیلوفر آبی" required>
            </div>
            
            <div class="form-group">
                <label>قیمت محصول (توکن)</label>
                <input type="number" name="product_price" value="12" required>
            </div>
            
            <div class="form-group">
                <label>لینک محصول</label>
                <input type="text" name="product_link" value="https://aivatan.com/app/product/468466-lily-in-the-mirror" required>
            </div>
            
            <button type="submit">ایجاد تست داده</button>
        </form>
        
        <div id="status" class="status"></div>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('?action=setup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                const statusDiv = document.getElementById('status');
                
                if (result.success) {
                    statusDiv.className = 'status success';
                    statusDiv.textContent = '✅ تست داده با موفقیت ایجاد شد!';
                } else {
                    statusDiv.className = 'status error';
                    statusDiv.textContent = '❌ خطا: ' + result.message;
                }
            } catch (error) {
                const statusDiv = document.getElementById('status');
                statusDiv.className = 'status error';
                statusDiv.textContent = '❌ خطا در ارسال درخواست';
            }
        });
    </script>
</body>
</html>
HTML;
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'setup') {
    try {
        // Get JSON data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Get database config
        $env_file = __DIR__ . '/../.env';
        $env_content = file_get_contents($env_file);
        
        // Parse .env file
        $config = [];
        foreach (explode("\n", $env_content) as $line) {
            if (strpos($line, '=') && !str_starts_with($line, '#')) {
                [$key, $value] = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
        
        // Connect to database
        $pdo = new PDO(
            'mysql:host=' . $config['DB_HOST'] . ';dbname=' . $config['DB_DATABASE'],
            $config['DB_USERNAME'],
            $config['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Get user
        $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception('کاربری یافت نشد');
        }
        
        // Prepare data
        $instagram_post_id = $data['instagram_post_id'] ?? 'Dd4VEftOMUV';
        $title = $data['title'] ?? 'تست نیلوفر - Reel';
        $keyword = $data['keyword'] ?? 'نیلوفر';
        $product_name = $data['product_name'] ?? 'پرامپت نیلوفر آبی';
        $product_price = $data['product_price'] ?? 12;
        $product_link = $data['product_link'] ?? 'https://aivatan.com/app/product/468466-lily-in-the-mirror';
        
        // Create post setting
        $stmt = $pdo->prepare("
            INSERT INTO instagram_post_settings 
            (user_id, title, instagram_post_id, instagram_caption, status, require_follow, min_followers, 
             non_follower_response, follower_response, comment_reply_delay, dm_product_delay, dm_form_delay, 
             repeat_policy, log_all_interactions, notify_slack, daily_excel_export, metadata, created_at, updated_at)
            VALUES 
            (:user_id, :title, :instagram_post_id, :instagram_caption, :status, :require_follow, :min_followers,
             :non_follower_response, :follower_response, :comment_reply_delay, :dm_product_delay, :dm_form_delay,
             :repeat_policy, :log_all_interactions, :notify_slack, :daily_excel_export, :metadata, NOW(), NOW())
        ");
        
        $stmt->execute([
            ':user_id' => $user['id'],
            ':title' => $title,
            ':instagram_post_id' => $instagram_post_id,
            ':instagram_caption' => 'تست اتوماسیون کامنت و دایرکت 🎯',
            ':status' => 'testing',
            ':require_follow' => 1,
            ':min_followers' => 0,
            ':non_follower_response' => json_encode(['text' => '👋 سلام! برای دسترسی به ' . $product_name . ' اول صفحه رو فالو کن 🎁']),
            ':follower_response' => json_encode(['text' => '✅ خوش‌آمدی! ' . $product_name . ' رو براتت فرستادم 👈']),
            ':comment_reply_delay' => 3,
            ':dm_product_delay' => 5,
            ':dm_form_delay' => 2,
            ':repeat_policy' => 'once_per_user',
            ':log_all_interactions' => 1,
            ':notify_slack' => 0,
            ':daily_excel_export' => 0,
            ':metadata' => json_encode(['product_name' => $product_name, 'test_marker' => 'real_test_niloofar']),
        ]);
        
        $post_id = $pdo->lastInsertId();
        
        // Add keyword
        $stmt = $pdo->prepare("
            INSERT INTO instagram_post_keywords (post_setting_id, keyword, match_type, is_active, created_at, updated_at)
            VALUES (:post_setting_id, :keyword, :match_type, :is_active, NOW(), NOW())
        ");
        $stmt->execute([
            ':post_setting_id' => $post_id,
            ':keyword' => $keyword,
            ':match_type' => 'exact',
            ':is_active' => 1,
        ]);
        
        // Add responses
        $responses = [
            ['scenario' => 'non_follower', 'target' => 'dm_text', 'message' => '👋 سلام! برای دسترسی به ' . $product_name . ' اول صفحه رو فالو کن بعد منتظر بمون ۳ ثانیه دیگه پیام رو براتت میفرسته 🎁'],
            ['scenario' => 'follower', 'target' => 'dm_text', 'message' => '✅ خوش‌آمدی! ' . $product_name . ' رو براتت فرستادم لطفا دایرکت بررسی کن 👈'],
            ['scenario' => 'follower', 'target' => 'comment', 'message' => '✅ اطلاعات کامل در DM فرستادم برو چک کن 👈'],
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO instagram_post_responses (post_setting_id, scenario, target, message, created_at, updated_at)
            VALUES (:post_setting_id, :scenario, :target, :message, NOW(), NOW())
        ");
        
        foreach ($responses as $response) {
            $stmt->execute([
                ':post_setting_id' => $post_id,
                ':scenario' => $response['scenario'],
                ':target' => $response['target'],
                ':message' => $response['message'],
            ]);
        }
        
        // Add product
        $stmt = $pdo->prepare("
            INSERT INTO instagram_post_products (post_setting_id, product_name, description, price, product_link, is_active, inventory, created_at, updated_at)
            VALUES (:post_setting_id, :product_name, :description, :price, :product_link, :is_active, :inventory, NOW(), NOW())
        ");
        $stmt->execute([
            ':post_setting_id' => $post_id,
            ':product_name' => $product_name,
            ':description' => 'پرامپت خاص برای تولید تصاویر با موضوع ' . $keyword,
            ':price' => $product_price,
            ':product_link' => $product_link,
            ':is_active' => 1,
            ':inventory' => 999,
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تست داده با موفقیت ایجاد شد',
            'data' => [
                'post_id' => $post_id,
                'instagram_post_id' => $instagram_post_id,
                'keyword' => $keyword,
                'product' => $product_name,
            ],
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
