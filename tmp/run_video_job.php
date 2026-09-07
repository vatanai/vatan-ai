<?php

declare(strict_types=1);

$root = '/var/www/html';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = App\Models\VideoStudioJob::create([
    'product_id' => 194,
    'admin_id' => null,
    'source_mode' => 'video',
    'source_url' => 'https://aivatan.com/storage/video-studio/sources/3973030067271894011.mp4',
    'selected_images' => [
        'https://aivatan.com/storage/products/main/2zPwMnfdKmSxPM69LNbnzUaPWax6xXVjThniy7Tt.webp',
        'https://aivatan.com/storage/products/main/zpPDHLLBrsD3ftDzGmNJtPxkpckuqQesjED9QGsa.webp',
    ],
    'aspect_ratio' => '9:16',
    'hook_text' => 'قبل از خرید، این محصول را بهتر بشناس',
    'caption_text' => 'یک انتخاب خلاقانه برای ساختن لحظه‌های متفاوت. برای دیدن جزئیات، کلمه قیمت را کامنت کن.',
    'keyword' => 'قیمت',
    'dm_template' => 'سلام! برای دیدن جزئیات این محصول، لینک محصول را بررسی کن.',
    'status' => 'queued',
    'payload' => ['created_from' => 'console_test'],
]);

$payload = [
    'job_id' => $job->id,
    'product_id' => $job->product_id,
    'source_mode' => $job->source_mode,
    'source_url' => $job->source_url,
    'selected_images' => $job->selected_images,
    'aspect_ratio' => $job->aspect_ratio,
    'hook_text' => $job->hook_text,
    'caption_text' => $job->caption_text,
    'keyword' => $job->keyword,
    'dm_template' => $job->dm_template,
    'chat_id' => config('services.n8n.video_studio_telegram_chat_id'),
    'callback_url' => 'https://aivatan.com/webhooks/video-studio/' . $job->id . '/status',
    'status_secret' => config('services.n8n.video_studio_status_secret'),
];

$response = Illuminate\Support\Facades\Http::retry(3, 300)->timeout(30)
    ->post(config('services.n8n.video_studio_webhook'), $payload);

if ($response->successful()) {
    $job->update([
        'status' => 'processing',
        'n8n_execution_id' => (string) ($response->json('execution_id') ?? ''),
    ]);
} else {
    $job->update(['status' => 'failed', 'error_message' => 'HTTP ' . $response->status()]);
}

echo json_encode([
    'job_id' => $job->id,
    'status' => $job->fresh()->status,
    'http' => $response->status(),
    'body' => $response->json(),
], JSON_UNESCAPED_UNICODE);
